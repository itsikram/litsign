<?php
/**
 * Media sync: copy Media Library files that one site has and the other is
 * missing, through the Diagnostics Toolkit plugin's Site Migration connection
 * (its signed API, the same one full migrations use). Only files are copied;
 * the attachments themselves come with the database.
 *
 * Pull: attachments in this site's database whose files are missing here are
 *       downloaded from the other site.
 * Push: this site's attachment files that the other site does not have are
 *       uploaded to it (staged there, then moved into its uploads folder).
 *
 * A job runs in small steps (admin-ajax, wholesale_media_sync) so no request
 * runs long, and js/admin-settings.js waits between steps so the live site's
 * firewall does not see a burst of requests.
 */

if (!defined('ABSPATH')) {
	exit;
}

const WHOLESALE_MSYNC_CHUNK = 1048576 + 524288; // 1.5 MB per file segment.
const WHOLESALE_MSYNC_CALL_BYTES = 3145728;     // At most 3 MB of file data per request.
const WHOLESALE_MSYNC_STEP_SECONDS = 15;
const WHOLESALE_MSYNC_CHECK_BATCH = 250;

function wholesale_msync_available()
{
	return class_exists('\WUDT\Modules\Migration\Remote_Client') && class_exists('\WUDT\Modules\Migration\Migration_Engine');
}

/**
 * Connected sites from Diagnostics Toolkit > Site Migration, without keys.
 *
 * @return array<string,array{label:string,url:string}>
 */
function wholesale_msync_sites()
{
	$out = array();
	$sites = get_option('wudt_migration_sites', array());
	foreach (is_array($sites) ? $sites : array() as $id => $site) {
		if (!is_array($site) || empty($site['url']) || empty($site['api_key'])) {
			continue;
		}
		$url = untrailingslashit((string) $site['url']);
		$out[(string) $id] = array(
			'label' => (string) ($site['label'] ?? '') ?: (string) wp_parse_url($url, PHP_URL_HOST),
			'url' => $url,
		);
	}
	return $out;
}

function wholesale_msync_client($site_id)
{
	$sites = get_option('wudt_migration_sites', array());
	$site = is_array($sites) && isset($sites[$site_id]) && is_array($sites[$site_id]) ? $sites[$site_id] : null;
	if (!$site || empty($site['url']) || empty($site['api_key'])) {
		throw new RuntimeException('That site is no longer connected. Add it again under Diagnostics Toolkit > Site Migration.');
	}
	return new \WUDT\Modules\Migration\Remote_Client((string) $site['url'], (string) $site['api_key'], (string) ($site['transport'] ?? ''));
}

function wholesale_msync_uploads_dir()
{
	return untrailingslashit(wp_normalize_path((string) wp_get_upload_dir()['basedir']));
}

/**
 * Every file of every attachment (original, resized copies and the
 * pre-scaling original), relative to the uploads folder.
 *
 * @return string[]
 */
function wholesale_msync_attachment_files()
{
	global $wpdb;

	$files = array();
	$rows = $wpdb->get_results("SELECT p.post_id, p.meta_value AS file, m.meta_value AS meta FROM {$wpdb->postmeta} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.post_id AND m.meta_key = '_wp_attachment_metadata' WHERE p.meta_key = '_wp_attached_file'");
	foreach ((array) $rows as $row) {
		$file = ltrim(str_replace('\\', '/', (string) $row->file), '/');
		if ('' === $file || preg_match('#^[a-z]+:#i', $file)) {
			continue; // Empty, or an absolute path / URL (offloaded media).
		}
		$files[$file] = true;
		$dir = dirname($file);
		$prefix = '.' === $dir ? '' : $dir . '/';
		$meta = maybe_unserialize((string) $row->meta);
		if (is_array($meta)) {
			foreach ((array) ($meta['sizes'] ?? array()) as $size) {
				if (!empty($size['file'])) {
					$files[$prefix . $size['file']] = true;
				}
			}
			if (!empty($meta['original_image'])) {
				$files[$prefix . $meta['original_image']] = true;
			}
		}
	}

	$out = array();
	foreach (array_keys($files) as $rel) {
		if (!preg_match('#(^|/)\.\.(/|$)#', $rel) && (!wholesale_msync_available() || !\WUDT\Modules\Migration\Migration_Engine::is_excluded('uploads', $rel))) {
			$out[] = $rel;
		}
	}
	sort($out);
	return $out;
}

/**
 * Attachment files missing from this site's uploads folder.
 *
 * @return string[]
 */
function wholesale_msync_missing_here(?array $files = null)
{
	$base = wholesale_msync_uploads_dir();
	$missing = array();
	foreach ($files ?? wholesale_msync_attachment_files() as $rel) {
		if (!is_file($base . '/' . $rel)) {
			$missing[] = $rel;
		}
	}
	return $missing;
}

function wholesale_msync_job_key()
{
	return 'wholesale_msync_job_' . get_current_user_id();
}

function wholesale_msync_save_job(array $job)
{
	set_transient(wholesale_msync_job_key(), $job, DAY_IN_SECONDS);
}

function wholesale_msync_public(array $job)
{
	return array(
		'direction' => $job['direction'],
		'site' => $job['site_label'],
		'phase' => $job['phase'],
		'total' => count($job['files']),
		'checked' => (int) $job['checked'],
		'queue' => count($job['queue']),
		'done' => (int) $job['done'],
		'bytes' => (int) $job['bytes'],
		'failed' => array_slice($job['failed'], 0, 50),
		'failed_count' => count($job['failed']),
		'not_found' => array_slice($job['not_found'], 0, 50),
		'not_found_count' => count($job['not_found']),
		'recent' => $job['recent'],
	);
}

/**
 * admin-ajax: op=start (site_id, direction) | step | cancel.
 */
function wholesale_msync_ajax()
{
	if (!current_user_can('manage_options') || !check_ajax_referer('wholesale_media_sync', 'nonce', false)) {
		wp_send_json_error(array('message' => 'Not allowed.'), 403);
	}
	if (!wholesale_msync_available()) {
		wp_send_json_error(array('message' => 'Diagnostics Toolkit is not active.'));
	}

	$op = sanitize_key(wp_unslash($_POST['op'] ?? ''));
	try {
		if ('start' === $op) {
			$site_id = sanitize_key(wp_unslash($_POST['site_id'] ?? ''));
			$direction = 'push' === ($_POST['direction'] ?? '') ? 'push' : 'pull';
			$sites = wholesale_msync_sites();
			if (!isset($sites[$site_id])) {
				throw new RuntimeException('Choose a connected site.');
			}
			$all = wholesale_msync_attachment_files();
			$base = wholesale_msync_uploads_dir();
			if ('pull' === $direction) {
				$files = wholesale_msync_missing_here($all);
			} else {
				$files = array_values(array_filter($all, static function ($rel) use ($base) {
					return is_file($base . '/' . $rel);
				}));
			}
			$job = array(
				'id' => 'sfsmedia' . strtolower(wp_generate_password(12, false)),
				'site_id' => $site_id,
				'site_label' => $sites[$site_id]['label'],
				'direction' => $direction,
				// Push first asks the other site which files it lacks; pull already knows.
				'phase' => 'push' === $direction ? 'check' : 'transfer',
				'files' => $files,
				'checked' => 0,
				'queue' => 'pull' === $direction ? $files : array(),
				'index' => 0,
				'offset' => 0,
				'finalize' => 0,
				'done' => 0,
				'bytes' => 0,
				'failed' => array(),
				'not_found' => array(),
				'recent' => array(),
			);
			if (!$files || ('pull' === $direction && !$job['queue'])) {
				$job['phase'] = 'done';
			}
			wholesale_msync_save_job($job);
			wp_send_json_success(wholesale_msync_public($job));
		}

		$job = get_transient(wholesale_msync_job_key());
		if (!is_array($job)) {
			throw new RuntimeException('No media sync is running. Start it again.');
		}
		if ('cancel' === $op) {
			delete_transient(wholesale_msync_job_key());
			wp_send_json_success(array('phase' => 'cancelled'));
		}
		if ('step' !== $op) {
			throw new RuntimeException('Unknown request.');
		}

		\WUDT\Modules\Migration\Migration_Engine::raise_limits();
		$client = wholesale_msync_client($job['site_id']);
		$client->set_deadline(microtime(true) + WHOLESALE_MSYNC_STEP_SECONDS + 40);
		$job['recent'] = array();
		$started = microtime(true);

		if ('check' === $job['phase']) {
			wholesale_msync_step_check($job, $client);
		} elseif ('transfer' === $job['phase']) {
			do {
				$more = 'pull' === $job['direction'] ? wholesale_msync_step_pull($job, $client) : wholesale_msync_step_push($job, $client);
				// Space out requests to the other site (bursts trip the live site's firewall).
				$more = $more && microtime(true) - $started < WHOLESALE_MSYNC_STEP_SECONDS - 1;
				if ($more) {
					usleep(800000);
				}
			} while ($more);
		} elseif ('finalize' === $job['phase']) {
			$result = $client->call('finalize_files', array('job' => $job['id'], 'offset' => (int) $job['finalize'], 'budget' => 12), 90);
			$job['finalize'] = (int) ($result['next'] ?? $job['finalize']);
			if (!empty($result['done'])) {
				$job['phase'] = 'done';
			}
		}

		wholesale_msync_save_job($job);
		wp_send_json_success(wholesale_msync_public($job));
	} catch (Throwable $e) {
		wp_send_json_error(array('message' => $e->getMessage()));
	}
}
add_action('wp_ajax_wholesale_media_sync', 'wholesale_msync_ajax');

/**
 * Push, phase 1: ask the other site which of our files it lacks. A read of
 * zero bytes answers "missing" or the file's size without sending data.
 */
function wholesale_msync_step_check(array &$job, $client)
{
	$batch = array_slice($job['files'], (int) $job['checked'], WHOLESALE_MSYNC_CHECK_BATCH);
	if ($batch) {
		$requests = array_map(static function ($rel) {
			return array('c' => 'uploads', 'p' => $rel, 'o' => 0, 'l' => 0);
		}, $batch);
		foreach ((array) $client->call('read', array('requests' => $requests), 60) as $answer) {
			if (!empty($answer['missing'])) {
				$job['queue'][] = (string) $answer['p'];
			}
		}
		$job['checked'] += count($batch);
	}
	if ((int) $job['checked'] >= count($job['files'])) {
		$job['phase'] = $job['queue'] ? 'transfer' : 'done';
	}
}

/**
 * Pull: download the next file segments and write them into uploads. A file
 * is assembled in a .part file and only moved into place once its md5 matches.
 *
 * @return bool Whether there is more to do in this phase.
 */
function wholesale_msync_step_pull(array &$job, $client)
{
	$base = wholesale_msync_uploads_dir();
	$requests = array();
	$i = (int) $job['index'];
	$offset = (int) $job['offset'];
	// Up to two files per request; a large file continues where it left off.
	while ($i < count($job['queue']) && count($requests) < 2) {
		$requests[] = array('c' => 'uploads', 'p' => $job['queue'][$i], 'o' => $offset, 'l' => WHOLESALE_MSYNC_CHUNK);
		$i++;
		$offset = 0;
	}
	if (!$requests) {
		$job['phase'] = 'done';
		return false;
	}

	$segments = (array) $client->call('read', array('requests' => $requests), 90);
	foreach ($segments as $k => $seg) {
		$rel = (string) ($seg['p'] ?? $requests[$k]['p']);
		$target = $base . '/' . $rel;
		$part = $target . '.part';
		if (!empty($seg['missing'])) {
			$job['not_found'][] = $rel;
			$job['index']++;
			$job['offset'] = 0;
			continue;
		}
		$data = base64_decode((string) ($seg['d'] ?? ''), true);
		if (false === $data || !wp_mkdir_p(dirname($target))) {
			$job['failed'][] = $rel;
			$job['index']++;
			$job['offset'] = 0;
			continue;
		}
		$seg_offset = (int) ($seg['o'] ?? 0);
		$fh = fopen($part, 0 === $seg_offset ? 'wb' : 'c+b');
		if (!$fh) {
			$job['failed'][] = $rel;
			$job['index']++;
			$job['offset'] = 0;
			continue;
		}
		fseek($fh, $seg_offset);
		fwrite($fh, $data);
		fclose($fh);
		$job['bytes'] += strlen($data);

		if (empty($seg['eof'])) {
			// Only the first request of a call can be a partial file; continue it next time.
			$job['offset'] = $seg_offset + strlen($data);
			break;
		}
		clearstatcache(true, $part);
		if (!empty($seg['h']) && md5_file($part) !== (string) $seg['h']) {
			@unlink($part);
			$job['failed'][] = $rel;
		} elseif (is_file($target) || !@rename($part, $target)) {
			// It appeared meanwhile (another upload), or could not be moved.
			@unlink($part);
			if (!is_file($target)) {
				$job['failed'][] = $rel;
			}
		} else {
			$job['done']++;
			$job['recent'][] = $rel;
		}
		$job['index']++;
		$job['offset'] = 0;
	}

	if ((int) $job['index'] >= count($job['queue'])) {
		$job['phase'] = 'done';
		return false;
	}
	return true;
}

/**
 * Push: send the next file segments to the other site's staging area. Once
 * every file is staged, the finalize phase moves them into its uploads.
 *
 * @return bool Whether there is more to do in this phase.
 */
function wholesale_msync_step_push(array &$job, $client)
{
	$base = wholesale_msync_uploads_dir();
	$segments = array();
	$sent = array();
	$budget = WHOLESALE_MSYNC_CALL_BYTES;
	$i = (int) $job['index'];
	$offset = (int) $job['offset'];

	while ($i < count($job['queue']) && $budget > 0) {
		$rel = $job['queue'][$i];
		$abs = $base . '/' . $rel;
		$size = is_file($abs) ? (int) filesize($abs) : -1;
		if ($size < 0) {
			$job['failed'][] = $rel;
			$i++;
			$offset = 0;
			continue;
		}
		$length = min(WHOLESALE_MSYNC_CHUNK, $budget, max(0, $size - $offset));
		$fh = fopen($abs, 'rb');
		if (!$fh) {
			$job['failed'][] = $rel;
			$i++;
			$offset = 0;
			continue;
		}
		fseek($fh, $offset);
		$data = $length > 0 ? (string) fread($fh, $length) : '';
		fclose($fh);
		$eof = $offset + strlen($data) >= $size;
		$segment = array('c' => 'uploads', 'p' => $rel, 'o' => $offset, 'd' => base64_encode($data), 'eof' => $eof);
		if ($eof) {
			$segment['h'] = 0 === $offset ? md5($data) : (string) md5_file($abs);
		}
		$segments[] = $segment;
		$budget -= max(1, strlen($data));
		if ($eof) {
			$sent[] = $rel;
			$i++;
			$offset = 0;
		} else {
			$offset += strlen($data);
			break; // Send this part now; the rest of the file follows in the next request.
		}
	}

	if ($segments) {
		$result = (array) $client->call('write', array('job' => $job['id'], 'segments' => $segments), 120);
		$failed = array();
		foreach ((array) ($result['failed'] ?? array()) as $f) {
			$failed[(string) ($f['p'] ?? '')] = true;
		}
		foreach ($sent as $rel) {
			if (isset($failed[$rel])) {
				$job['failed'][] = $rel;
			} else {
				$job['done']++;
				$job['recent'][] = $rel;
			}
		}
		$job['bytes'] += WHOLESALE_MSYNC_CALL_BYTES - $budget;
	}
	$job['index'] = $i;
	$job['offset'] = $offset;

	if ($i >= count($job['queue'])) {
		$job['phase'] = $job['done'] ? 'finalize' : 'done';
		return false;
	}
	return true;
}

/**
 * The Media sync panel of Settings > Storefront Sign. Its controls have no
 * name, so saving the settings form ignores them.
 */
function wholesale_msync_panel()
{
	$plugin_page = admin_url('admin.php?page=wudt-site-migration');
	if (!wholesale_msync_available()) {
		echo '<div class="notice notice-warning inline"><p>Media sync uses the <strong>Diagnostics Toolkit</strong> plugin&rsquo;s Site Migration connection. Activate that plugin here and on the other site, then connect the sites under Diagnostics Toolkit &rsaquo; Site Migration.</p></div>';
		return;
	}
	$sites = wholesale_msync_sites();
	$all = wholesale_msync_attachment_files();
	$missing = wholesale_msync_missing_here($all);
	?>
	<div class="wss-msync" data-wss-nosave data-nonce="<?php echo esc_attr(wp_create_nonce('wholesale_media_sync')); ?>">
		<ul class="wss-msync__stats">
			<li><strong><?php echo esc_html(number_format_i18n(count($all))); ?></strong> Media Library files on this site</li>
			<li class="<?php echo $missing ? 'is-warn' : 'is-ok'; ?>"><strong><?php echo esc_html(number_format_i18n(count($missing))); ?></strong> missing from this site&rsquo;s uploads folder</li>
		</ul>
		<?php if (!$sites) : ?>
			<div class="notice notice-info inline"><p>No site is connected yet. Connect the live site under <a href="<?php echo esc_url($plugin_page); ?>">Diagnostics Toolkit &rsaquo; Site Migration</a> (paste its connection key), then come back here.</p></div>
		<?php else : ?>
			<p class="wss-msync__site">
				<label for="wss-msync-site"><strong>Other site</strong></label>
				<select id="wss-msync-site">
					<?php foreach ($sites as $id => $site) : ?>
						<option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($site['label'] . ' — ' . preg_replace('#^https?://#', '', $site['url'])); ?></option>
					<?php endforeach; ?>
				</select>
				<a href="<?php echo esc_url($plugin_page); ?>">Manage connections</a>
			</p>
			<div class="wss-msync__actions">
				<div class="wss-msync__action">
					<button type="button" class="button button-primary" data-wss-msync="pull"><span class="dashicons dashicons-download" aria-hidden="true"></span> Pull missing files</button>
					<p class="description">Downloads the files of this site&rsquo;s Media Library that are missing here from the other site. Nothing on the other site changes.</p>
				</div>
				<div class="wss-msync__action">
					<button type="button" class="button" data-wss-msync="push"><span class="dashicons dashicons-upload" aria-hidden="true"></span> Push files the other site is missing</button>
					<p class="description">Uploads this site&rsquo;s Media Library files that the other site doesn&rsquo;t have. Existing files there are never overwritten. Like a migration, this replaces the other site&rsquo;s Site Migration undo point.</p>
				</div>
			</div>
			<div class="wss-msync__progress" hidden>
				<div class="wss-msync__bar"><span></span></div>
				<p class="wss-msync__status" aria-live="polite"></p>
				<p><button type="button" class="button-link" data-wss-msync-cancel>Stop</button></p>
				<div class="wss-msync__report"></div>
			</div>
		<?php endif; ?>
		<p class="description">Files are copied in small, spaced-out steps so the live site&rsquo;s firewall isn&rsquo;t triggered; keep this page open until it finishes. Only files are copied: images added on one site appear in the other site&rsquo;s Media Library after its database is migrated.</p>
	</div>
	<?php
}
