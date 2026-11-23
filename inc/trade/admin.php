<?php
/**
 * wp-admin > Trade Accounts: review applications, approve or reject, notes,
 * documents, and the trade settings.
 *
 * Every action checks the `list_users` capability and a nonce, and is logged in
 * the account's history (inc/trade/accounts.php).
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('admin_menu', static function () {
	$count = wholesale_trade_pending_count();
	$bubble = $count ? ' <span class="awaiting-mod count-' . (int) $count . '"><span class="pending-count">' . (int) $count . '</span></span>' : '';
	add_menu_page('Trade Accounts', 'Trade Accounts' . $bubble, 'list_users', 'wholesale-trade', 'wholesale_trade_admin_page', 'dashicons-id-alt', 26);
	add_submenu_page('wholesale-trade', 'Trade Accounts', 'Applications', 'list_users', 'wholesale-trade', 'wholesale_trade_admin_page');
	add_submenu_page('wholesale-trade', 'Trade Settings', 'Settings', 'manage_options', 'wholesale-trade-settings', 'wholesale_trade_settings_page');
});

/** Actions offered for each status. */
function wholesale_trade_admin_actions($status)
{
	$all = array(
		'approved' => 'Approve',
		'info_requested' => 'Request info',
		'rejected' => 'Reject',
		'suspended' => 'Suspend',
		'pending_review' => 'Re-open for review',
	);
	$by_status = array(
		'email_unverified' => array('approved', 'rejected', 'pending_review'),
		'pending_review' => array('approved', 'info_requested', 'rejected'),
		'info_requested' => array('approved', 'rejected', 'pending_review'),
		'approved' => array('suspended'),
		'rejected' => array('pending_review', 'approved'),
		'suspended' => array('approved', 'pending_review'),
		'legacy_unreviewed' => array('approved', 'info_requested', 'rejected'),
	);
	return array_intersect_key($all, array_flip(isset($by_status[$status]) ? $by_status[$status] : array()));
}

function wholesale_trade_admin_url($args = array())
{
	return add_query_arg($args, admin_url('admin.php?page=wholesale-trade'));
}

/** Number of orders a user has placed (legacy report). */
function wholesale_trade_user_order_count($user_id)
{
	global $wpdb;
	return (int) $wpdb->get_var($wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = 'user_id' WHERE p.post_type = 'order' AND m.meta_value = %s",
		(string) (int) $user_id
	));
}

function wholesale_trade_admin_page()
{
	if (!current_user_can('list_users')) {
		wp_die('Sorry, you are not allowed to manage trade accounts.');
	}
	if (!empty($_GET['user'])) {
		wholesale_trade_admin_detail(absint($_GET['user']));
		return;
	}

	$statuses = wholesale_trade_statuses();
	$types = wholesale_trade_business_types();
	$view = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'review';
	$type = isset($_GET['type']) ? sanitize_key(wp_unslash($_GET['type'])) : '';
	$search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
	$paged = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
	$per_page = 40;

	$args = array('number' => $per_page, 'paged' => $paged, 'orderby' => 'registered', 'order' => 'DESC', 'count_total' => true);
	$meta = array();
	if ('review' === $view) {
		$meta[] = array('key' => '_trade_status', 'value' => array('pending_review', 'info_requested'), 'compare' => 'IN');
	} elseif ('legacy_unreviewed' === $view) {
		$meta[] = array('key' => '_trade_status', 'compare' => 'NOT EXISTS');
		$args['role__not_in'] = array('administrator');
	} elseif (isset($statuses[$view])) {
		$meta[] = array('key' => '_trade_status', 'value' => $view);
	} else {
		$meta[] = array('key' => '_trade_status', 'compare' => 'EXISTS');
	}
	if ($type && isset($types[$type])) {
		$meta[] = array('key' => '_trade_profile', 'value' => '"business_type";s:' . strlen($type) . ':"' . $type . '"', 'compare' => 'LIKE');
	}
	if ($meta) {
		$args['meta_query'] = array_merge(array('relation' => 'AND'), $meta);
	}
	if ($search) {
		$args['search'] = '*' . $search . '*';
		$args['search_columns'] = array('user_login', 'user_email', 'user_url', 'display_name');
	}
	$query = new WP_User_Query($args);
	$users = $query->get_results();
	$total = (int) $query->get_total();

	$views = array('review' => 'Needs review') + array_diff_key($statuses, array('pending_review' => 1)) + array('all' => 'All applicants');
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">Trade Accounts</h1>
		<?php if (!empty($_GET['done'])) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html(sanitize_text_field(wp_unslash($_GET['done']))); ?></p></div>
		<?php endif; ?>
		<p>Only <strong>approved</strong> accounts see trade prices and can order. Applicants confirm their email first, then appear under <em>Needs review</em>. Accounts registered before the trade site are under <em>Legacy (unreviewed)</em>: nobody was approved automatically.</p>
		<ul class="subsubsub">
			<?php
			$links = array();
			foreach ($views as $key => $label) {
				$links[] = '<li><a href="' . esc_url(wholesale_trade_admin_url(array('status' => $key))) . '"' . ($view === $key ? ' class="current"' : '') . '>' . esc_html($label) . ('review' === $key ? ' <span class="count">(' . (int) wholesale_trade_pending_count() . ' pending)</span>' : '') . '</a>';
			}
			echo implode(' | </li>', $links) . '</li>'; // Escaped above.
			?>
		</ul>
		<form method="get" style="clear:both;padding-top:8px">
			<input type="hidden" name="page" value="wholesale-trade">
			<input type="hidden" name="status" value="<?php echo esc_attr($view); ?>">
			<select name="type">
				<option value="">All business types</option>
				<?php foreach ($types as $key => $label) : ?>
					<option value="<?php echo esc_attr($key); ?>" <?php selected($type, $key); ?>><?php echo esc_html($label); ?></option>
				<?php endforeach; ?>
			</select>
			<input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Business, email, website">
			<?php submit_button('Filter', '', '', false); ?>
			<span style="margin-left:12px;color:#646970"><?php echo esc_html(number_format_i18n($total)); ?> accounts</span>
		</form>
		<table class="widefat striped" style="margin-top:12px">
			<thead><tr><th>Business</th><th>Type</th><th>Location</th><th>Contact</th><th>Applied</th><th>Docs</th><th>Orders</th><th>Status</th></tr></thead>
			<tbody>
			<?php if (!$users) : ?>
				<tr><td colspan="8">No accounts in this view.</td></tr>
			<?php endif; ?>
			<?php foreach ($users as $user) :
				$profile = wholesale_trade_profile($user->ID);
				$status = wholesale_trade_status($user->ID);
				$docs = (array) get_user_meta($user->ID, '_trade_docs', true);
				$flags = (array) get_user_meta($user->ID, '_trade_flags', true);
				$applied = (int) get_user_meta($user->ID, '_trade_applied_at', true);
				$attacker = in_array($user->ID, wholesale_trade_attacker_user_ids(), true);
				?>
				<tr>
					<td>
						<strong><a href="<?php echo esc_url(wholesale_trade_admin_url(array('user' => $user->ID))); ?>"><?php echo esc_html(!empty($profile['legal_name']) ? $profile['legal_name'] : $user->display_name); ?></a></strong>
						<?php if (!empty($profile['website'])) : ?><br><span style="color:#646970"><?php echo esc_html(wholesale_trade_site_domain($profile['website'])); ?></span><?php endif; ?>
						<?php if ($attacker) : ?><br><strong style="color:#b32d2e">Attacker account (2024–26 hack)</strong><?php endif; ?>
						<?php if (array_filter($flags)) : ?><br><span style="color:#996800">⚑ <?php echo esc_html(implode(' ', array_filter($flags))); ?></span><?php endif; ?>
					</td>
					<td><?php echo esc_html($types[$profile['business_type'] ?? ''] ?? '—'); ?></td>
					<td><?php echo esc_html(trim(($profile['city'] ?? '') . ', ' . ($profile['state'] ?? ''), ', ') ?: '—'); ?></td>
					<td><?php echo esc_html($user->display_name); ?><br><a href="mailto:<?php echo esc_attr($user->user_email); ?>"><?php echo esc_html($user->user_email); ?></a></td>
					<td><?php echo esc_html($applied ? wp_date('M j, Y', $applied) : wp_date('M j, Y', strtotime($user->user_registered))); ?></td>
					<td><?php echo (int) count(array_filter($docs)); ?></td>
					<td><?php echo (int) wholesale_trade_user_order_count($user->ID); ?></td>
					<td><?php echo esc_html(wholesale_trade_status_label($status)); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		$pages = (int) ceil($total / $per_page);
		if ($pages > 1) {
			echo '<div class="tablenav"><div class="tablenav-pages">' . paginate_links(array(
				'base' => add_query_arg('paged', '%#%'),
				'format' => '',
				'current' => $paged,
				'total' => $pages,
			)) . '</div></div>'; // paginate_links() escapes.
		}
		?>
	</div>
	<?php
}

function wholesale_trade_admin_detail($user_id)
{
	$user = get_userdata($user_id);
	if (!$user) {
		echo '<div class="wrap"><p>Account not found.</p></div>';
		return;
	}
	$profile = wholesale_trade_profile($user_id);
	$status = wholesale_trade_status($user_id);
	$docs = array_filter((array) get_user_meta($user_id, '_trade_docs', true));
	$flags = array_filter((array) get_user_meta($user_id, '_trade_flags', true));
	$notes = get_user_meta($user_id, '_trade_notes', true);
	$notes = is_array($notes) ? array_reverse($notes) : array();
	$fields = wholesale_trade_form_fields();
	$types = wholesale_trade_business_types();
	$volumes = wholesale_trade_volume_options();
	$attacker = in_array($user_id, wholesale_trade_attacker_user_ids(), true);
	?>
	<div class="wrap">
		<p><a href="<?php echo esc_url(wholesale_trade_admin_url()); ?>">&larr; All applications</a></p>
		<h1><?php echo esc_html(!empty($profile['legal_name']) ? $profile['legal_name'] : $user->display_name); ?> <span style="font-size:14px;font-weight:400;background:#f0f0f1;border-radius:3px;padding:3px 8px;vertical-align:middle"><?php echo esc_html(wholesale_trade_status_label($status)); ?></span></h1>
		<?php if (!empty($_GET['done'])) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html(sanitize_text_field(wp_unslash($_GET['done']))); ?></p></div>
		<?php endif; ?>
		<?php if ($attacker) : ?>
			<div class="notice notice-error"><p><strong>This account was created by the attacker in the 2024–2026 hack.</strong> It can never be approved. Delete it on live (attribute its content to your admin first).</p></div>
		<?php endif; ?>
		<?php foreach ($flags as $flag) : ?>
			<div class="notice notice-warning"><p>⚑ <?php echo esc_html($flag); ?></p></div>
		<?php endforeach; ?>

		<div style="display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:24px;align-items:start">
			<div>
				<table class="widefat striped">
					<tbody>
						<tr><th style="width:240px">Login / email</th><td><?php echo esc_html($user->user_login); ?> · <a href="mailto:<?php echo esc_attr($user->user_email); ?>"><?php echo esc_html($user->user_email); ?></a></td></tr>
						<tr><th>Registered</th><td><?php echo esc_html(wp_date('M j, Y g:i a', strtotime($user->user_registered . ' UTC'))); ?> · <?php echo (int) wholesale_trade_user_order_count($user_id); ?> orders</td></tr>
						<?php foreach ($fields as $key => $field) :
							if (!isset($profile[$key]) || '' === $profile[$key]) {
								continue;
							}
							$value = $profile[$key];
							if ('business_type' === $key) {
								$value = $types[$value] ?? $value;
							} elseif ('monthly_volume' === $key) {
								$value = $volumes[$value] ?? $value;
							}
							?>
							<tr><th><?php echo esc_html($field[0]); ?></th><td><?php echo 'website' === $key ? '<a href="' . esc_url($value) . '" target="_blank" rel="noopener noreferrer">' . esc_html($value) . '</a>' : nl2br(esc_html($value)); ?></td></tr>
						<?php endforeach; ?>
						<?php if (!$profile) : ?>
							<tr><th>Application</th><td>No trade application on file (registered before the trade site). Ask them to apply, or request info.</td></tr>
						<?php endif; ?>
					</tbody>
				</table>

				<h2>Documents</h2>
				<?php if (!$docs) : ?>
					<p>No documents uploaded.</p>
				<?php else : ?>
					<ul>
						<?php foreach ($docs as $field => $doc) :
							$labels = wholesale_trade_doc_fields();
							$url = wp_nonce_url(admin_url('admin-post.php?action=wholesale_trade_doc&user=' . $user_id . '&doc=' . rawurlencode($field)), 'wholesale_trade_doc_' . $user_id . '_' . $field);
							?>
							<li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($labels[$field] ?? $field); ?></a> — <?php echo esc_html($doc['original'] . ', ' . size_format($doc['size'])); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<h2>History</h2>
				<table class="widefat striped">
					<thead><tr><th>When</th><th>Status</th><th>By</th><th>Note</th></tr></thead>
					<tbody>
					<?php foreach (wholesale_trade_history($user_id) as $entry) :
						$by = !empty($entry['by']) ? get_userdata((int) $entry['by']) : null;
						?>
						<tr>
							<td><?php echo esc_html(wp_date('M j, Y g:i a', (int) $entry['time'])); ?></td>
							<td><?php echo esc_html(wholesale_trade_status_label($entry['status'])); ?></td>
							<td><?php echo esc_html($by ? $by->display_name : 'Applicant'); ?></td>
							<td><?php echo esc_html($entry['note'] ?? ''); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<div>
				<div class="postbox" style="padding:12px 16px">
					<h2 style="padding:0;margin:0 0 8px">Decision</h2>
					<?php $actions = $attacker ? array() : wholesale_trade_admin_actions($status); ?>
					<?php if ($actions) : ?>
						<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
							<input type="hidden" name="action" value="wholesale_trade_action">
							<input type="hidden" name="user" value="<?php echo (int) $user_id; ?>">
							<?php wp_nonce_field('wholesale_trade_action_' . $user_id); ?>
							<p><label for="tradeMessage">Message to the applicant (reason, or what info you need). Sent by email.</label></p>
							<textarea id="tradeMessage" name="message" rows="4" class="widefat"></textarea>
							<p>
								<?php foreach ($actions as $key => $label) : ?>
									<button type="submit" name="status" value="<?php echo esc_attr($key); ?>" class="button<?php echo 'approved' === $key ? ' button-primary' : ''; ?>"><?php echo esc_html($label); ?></button>
								<?php endforeach; ?>
							</p>
							<p><label><input type="checkbox" name="silent" value="1"> Don't email the applicant</label></p>
						</form>
					<?php else : ?>
						<p>No actions available.</p>
					<?php endif; ?>
				</div>
				<div class="postbox" style="padding:12px 16px">
					<h2 style="padding:0;margin:0 0 8px">Internal notes</h2>
					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<input type="hidden" name="action" value="wholesale_trade_note">
						<input type="hidden" name="user" value="<?php echo (int) $user_id; ?>">
						<?php wp_nonce_field('wholesale_trade_note_' . $user_id); ?>
						<textarea name="note" rows="3" class="widefat" required></textarea>
						<p><?php submit_button('Add note', 'secondary', 'submit', false); ?></p>
					</form>
					<?php foreach ($notes as $note) :
						$by = get_userdata((int) $note['by']);
						?>
						<p style="border-top:1px solid #dcdcde;padding-top:8px"><?php echo nl2br(esc_html($note['text'])); ?><br><span style="color:#646970"><?php echo esc_html(($by ? $by->display_name : '') . ' · ' . wp_date('M j, Y g:i a', (int) $note['time'])); ?></span></p>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
	<?php
}

add_action('admin_post_wholesale_trade_action', static function () {
	$user_id = isset($_POST['user']) ? absint($_POST['user']) : 0;
	if (!current_user_can('list_users') || !check_admin_referer('wholesale_trade_action_' . $user_id)) {
		wp_die('Not allowed.', 403);
	}
	$status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : '';
	$message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
	if (!array_key_exists($status, wholesale_trade_admin_actions(wholesale_trade_status($user_id)))) {
		wp_die('That action is not available for this account.', 400);
	}
	if (in_array($status, array('info_requested', 'rejected'), true) && '' === $message) {
		wp_safe_redirect(wholesale_trade_admin_url(array('user' => $user_id, 'done' => rawurlencode('Add a message first: the applicant needs to know what to send or why.'))));
		exit;
	}
	$ok = wholesale_trade_set_status($user_id, $status, $message, empty($_POST['silent']));
	$label = wholesale_trade_status_label($status);
	wp_safe_redirect(wholesale_trade_admin_url(array('user' => $user_id, 'done' => rawurlencode($ok ? 'Status set to ' . $label . '.' : 'Could not change the status.'))));
	exit;
});

add_action('admin_post_wholesale_trade_note', static function () {
	$user_id = isset($_POST['user']) ? absint($_POST['user']) : 0;
	if (!current_user_can('list_users') || !check_admin_referer('wholesale_trade_note_' . $user_id) || !get_userdata($user_id)) {
		wp_die('Not allowed.', 403);
	}
	$text = isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '';
	if ('' !== $text) {
		$notes = get_user_meta($user_id, '_trade_notes', true);
		$notes = is_array($notes) ? $notes : array();
		$notes[] = array('text' => mb_substr($text, 0, 2000), 'by' => get_current_user_id(), 'time' => time());
		update_user_meta($user_id, '_trade_notes', $notes);
	}
	wp_safe_redirect(wholesale_trade_admin_url(array('user' => $user_id, 'done' => rawurlencode('Note added.'))));
	exit;
});

/** Staff-only document download (files sit behind a deny rule). */
add_action('admin_post_wholesale_trade_doc', static function () {
	$user_id = isset($_GET['user']) ? absint($_GET['user']) : 0;
	$field = isset($_GET['doc']) ? sanitize_key(wp_unslash($_GET['doc'])) : '';
	if (!current_user_can('list_users') || !check_admin_referer('wholesale_trade_doc_' . $user_id . '_' . $field)) {
		wp_die('Not allowed.', 403);
	}
	$docs = (array) get_user_meta($user_id, '_trade_docs', true);
	$doc = isset($docs[$field]) && is_array($docs[$field]) ? $docs[$field] : null;
	$name = $doc ? (string) $doc['file'] : '';
	$path = wholesale_trade_docs_dir() . '/' . $name;
	if (!$doc || !preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png)$/', $name) || !is_file($path)) {
		wp_die('File not found.', 404);
	}
	nocache_headers();
	header('Content-Type: ' . (in_array($doc['mime'], array('application/pdf', 'image/jpeg', 'image/png'), true) ? $doc['mime'] : 'application/octet-stream'));
	header('Content-Disposition: attachment; filename="' . sanitize_file_name($doc['original'] ? $doc['original'] : $name) . '"');
	header('X-Content-Type-Options: nosniff');
	header('Content-Length: ' . filesize($path));
	readfile($path);
	exit;
});

function wholesale_trade_settings_page()
{
	if (!current_user_can('manage_options')) {
		wp_die('Not allowed.');
	}
	?>
	<div class="wrap">
		<h1>Trade Settings</h1>
		<form method="post" action="options.php">
			<?php settings_fields('wholesale_trade_settings'); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="wholesale_trade_email">Trade contact email</label></th>
					<td><input type="email" class="regular-text" id="wholesale_trade_email" name="wholesale_trade_email" value="<?php echo esc_attr(get_option('wholesale_trade_email', '')); ?>" placeholder="TR@StorefrontSignOnline.com">
					<p class="description">Shown in the header and footer, and the reply-to address of trade emails.</p></td>
				</tr>
				<tr>
					<th scope="row"><label for="wholesale_trade_notify_email">New application alerts go to</label></th>
					<td><input type="email" class="regular-text" id="wholesale_trade_notify_email" name="wholesale_trade_notify_email" value="<?php echo esc_attr(get_option('wholesale_trade_notify_email', '')); ?>" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>"></td>
				</tr>
				<tr>
					<th scope="row">Resale certificate</th>
					<td><label><input type="checkbox" name="wholesale_trade_require_resale" value="1" <?php checked((int) get_option('wholesale_trade_require_resale', 0), 1); ?>> Require a resale certificate to apply</label>
					<p class="description">Off by default: it is only needed for tax-exempt orders.</p></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
