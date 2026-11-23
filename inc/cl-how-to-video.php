<?php
/**
 * "How to order" video: home page → Channel Letters → builder → checkout (HeyGen, 2026-11-23).
 *
 * Shown under the channel letter builder (cl_builderr.php) and on channel letter
 * product pages (single-product.php). Each template requires this file before
 * get_header() and calls wholesale_cl_how_to_video_enqueue(), then prints the
 * section with wholesale_render_cl_how_to_video().
 *
 * Nothing loads until the visitor presses play: the poster is a lazy <img>, and the
 * <video> gets its source from js/cl-how-to-video.js (720p on phones, 1080p else).
 * Files live in uploads/2026/11/ and the section hides itself if they are missing
 * (e.g. code pushed to live before the media).
 */

if (!defined('ABSPATH')) {
	exit;
}

/** Upload-relative paths of the video files. */
function wholesale_cl_how_to_video_files()
{
	return array(
		'mp4_1080' => '2026/11/how-to-order-channel-letters-1080p.mp4',
		'mp4_720'  => '2026/11/how-to-order-channel-letters-720p.mp4',
		'poster'   => '2026/11/how-to-order-channel-letters-poster-1280.jpg',
		'poster_s' => '2026/11/how-to-order-channel-letters-poster-640.jpg',
		'webp'     => '2026/11/how-to-order-channel-letters-poster-1280.webp',
		'webp_s'   => '2026/11/how-to-order-channel-letters-poster-640.webp',
	);
}

/** Full URLs of the files, or null when the 1080p video or the poster is not on this server. */
function wholesale_cl_how_to_video_urls()
{
	static $urls = false;
	if (false !== $urls) {
		return $urls;
	}
	$upload = wp_get_upload_dir();
	$files = wholesale_cl_how_to_video_files();
	if (!is_file($upload['basedir'] . '/' . $files['mp4_1080']) || !is_file($upload['basedir'] . '/' . $files['poster'])) {
		$urls = null;
		return $urls;
	}
	$urls = array();
	foreach ($files as $key => $path) {
		$urls[$key] = is_file($upload['basedir'] . '/' . $path) ? set_url_scheme($upload['baseurl'] . '/' . $path) : '';
	}
	if ('' === $urls['mp4_720']) {
		$urls['mp4_720'] = $urls['mp4_1080'];
	}
	return $urls;
}

/** Chapters: start second => label. Cut times measured from the render (ffmpeg scene detection). */
function wholesale_cl_how_to_video_chapters()
{
	return array(
		4  => 'Open Channel Letters from the top menu',
		9  => 'Compare the 7 styles and pick yours',
		17 => 'Click Start From Scratch to open the builder',
		21 => 'Type your name, then pick font, colors and height',
		34 => 'Preview at night and add to cart',
		40 => 'Review your cart and check out',
		51 => 'Approve your proof — we build, test and ship',
	);
}

/** Video length in seconds. */
function wholesale_cl_how_to_video_duration()
{
	return 68;
}

/** Queue the section's CSS and JS (call before get_header()). */
function wholesale_cl_how_to_video_enqueue()
{
	if (!wholesale_cl_how_to_video_urls()) {
		return;
	}
	add_action('wp_enqueue_scripts', function () {
		$dir = get_template_directory();
		$uri = get_template_directory_uri();
		wp_enqueue_style('wholesale-cl-how-to-video', $uri . '/css/cl-how-to-video.css', array(), (string) filemtime($dir . '/css/cl-how-to-video.css'));
		wp_enqueue_script('wholesale-cl-how-to-video', $uri . '/js/cl-how-to-video.js', array(), (string) filemtime($dir . '/js/cl-how-to-video.js'), array('in_footer' => true, 'strategy' => 'defer'));
	}, 120);
}

/**
 * Print the section.
 *
 * @param string $context 'builder' (under the builder) or 'product' (channel letter product page).
 * @param int    $product_id Product for the "Open the builder" link on product pages.
 */
function wholesale_render_cl_how_to_video($context = 'builder', $product_id = 0)
{
	$urls = wholesale_cl_how_to_video_urls();
	if (!$urls) {
		return;
	}
	$duration = wholesale_cl_how_to_video_duration();
	$length = sprintf('%d:%02d', floor($duration / 60), $duration % 60);
	$chapters = wholesale_cl_how_to_video_chapters();
	$title = 'How to order a custom channel letter sign online';
	$description = 'Step by step on StorefrontSignOnline.com: open Channel Letters, pick a style, design your sign in the channel letter builder, add it to your cart and check out. Then you approve your proof and we build, test and ship your sign to any of the 50 states.';
	$builder_url = $product_id ? add_query_arg('product_id', (int) $product_id, home_url('/channel-letter-builder/')) : '';
	$page_url = 'product' === $context && $product_id ? get_permalink($product_id) : ($builder_url ? $builder_url : home_url('/channel-letter-builder/'));
	$section_url = $page_url . '#how-to-order-video';

	// VideoObject with key moments (Clip) for Google video results.
	$schema = array(
		'@context'     => 'https://schema.org',
		'@type'        => 'VideoObject',
		'name'         => $title,
		'description'  => $description,
		'thumbnailUrl' => array($urls['poster']),
		'uploadDate'   => '2026-11-23T12:00:00-08:00',
		'duration'     => 'PT1M8S',
		'contentUrl'   => $urls['mp4_1080'],
		'inLanguage'   => 'en-US',
		'publisher'    => array(
			'@type' => 'Organization',
			'name'  => 'Storefront Sign Online',
			'url'   => home_url('/'),
		),
		'hasPart'      => array(),
	);
	$starts = array_keys($chapters);
	foreach ($starts as $i => $start) {
		$schema['hasPart'][] = array(
			'@type'       => 'Clip',
			'name'        => wp_strip_all_tags($chapters[$start]),
			'startOffset' => $start,
			'endOffset'   => isset($starts[$i + 1]) ? $starts[$i + 1] : $duration,
			'url'         => add_query_arg('t', $start, $page_url) . '#how-to-order-video',
		);
	}
	?>
	<section class="cl-howto cl-howto--<?php echo esc_attr($context); ?>" id="how-to-order-video" aria-labelledby="cl-howto-title" data-cl-howto>
		<div class="cl-howto__inner">
			<div class="cl-howto__copy">
				<p class="cl-howto__eyebrow"><span class="cl-howto__dot" aria-hidden="true"></span>Video guide &middot; <?php echo esc_html($length); ?></p>
				<h2 class="cl-howto__title" id="cl-howto-title"><?php echo esc_html($title); ?></h2>
				<p class="cl-howto__lead">
					<?php if ('builder' === $context) : ?>
						New to the builder? Watch a full order on our real site, from the home page to checkout. Tap a step to jump to it.
					<?php else : ?>
						See a full order on our real site, from this page to checkout, in about a minute. Tap a step to jump to it.
					<?php endif; ?>
				</p>
				<ol class="cl-howto__chapters">
					<?php foreach ($chapters as $start => $label) : ?>
						<li>
							<button type="button" class="cl-howto__chapter" data-cl-howto-seek="<?php echo esc_attr($start); ?>">
								<span class="cl-howto__time"><?php echo esc_html(sprintf('%d:%02d', floor($start / 60), $start % 60)); ?></span>
								<span class="cl-howto__label"><?php echo esc_html($label); ?></span>
							</button>
						</li>
					<?php endforeach; ?>
				</ol>
				<div class="cl-howto__actions">
					<?php if ('builder' === $context) : ?>
						<a class="cl-howto__cta" href="#primary" data-cl-howto-top>Back to the builder <span aria-hidden="true">&uarr;</span></a>
					<?php elseif ($builder_url) : ?>
						<a class="cl-howto__cta" href="<?php echo esc_url($builder_url); ?>">Open the builder <span aria-hidden="true">&rarr;</span></a>
					<?php endif; ?>
					<a class="cl-howto__phone" href="tel:+18664362101">Questions? 866-436-2101</a>
				</div>
			</div>

			<div class="cl-howto__media">
				<div class="cl-howto__frame">
					<video class="cl-howto__video" controls playsinline preload="none" hidden
						width="1280" height="720"
						data-src-hd="<?php echo esc_url($urls['mp4_1080']); ?>"
						data-src-sd="<?php echo esc_url($urls['mp4_720']); ?>"
						aria-label="<?php echo esc_attr($title); ?>"></video>
					<button type="button" class="cl-howto__poster" data-cl-howto-play aria-label="<?php echo esc_attr('Play video: ' . $title . ' (' . $length . ')'); ?>">
						<picture>
							<?php if ($urls['webp']) : ?>
								<source type="image/webp" srcset="<?php echo esc_url($urls['webp_s']); ?> 640w, <?php echo esc_url($urls['webp']); ?> 1280w" sizes="(min-width: 1200px) 680px, (min-width: 992px) 56vw, 100vw">
							<?php endif; ?>
							<img src="<?php echo esc_url($urls['poster']); ?>" srcset="<?php echo esc_url($urls['poster_s']); ?> 640w, <?php echo esc_url($urls['poster']); ?> 1280w" sizes="(min-width: 1200px) 680px, (min-width: 992px) 56vw, 100vw" width="1280" height="720" loading="lazy" decoding="async" alt="The channel letter builder showing the word BAKERY lit up at night, with the price and Add to cart button">
						</picture>
						<span class="cl-howto__shade" aria-hidden="true"></span>
						<span class="cl-howto__badge" aria-hidden="true">
							<span class="cl-howto__playbtn"><svg viewBox="0 0 24 24" width="22" height="22" focusable="false"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.6-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z" fill="currentColor"/></svg></span>
							<span>Watch how to order &middot; <?php echo esc_html($length); ?></span>
						</span>
					</button>
					<noscript>
						<video class="cl-howto__video" controls playsinline preload="none" width="1280" height="720" poster="<?php echo esc_url($urls['poster']); ?>">
							<source src="<?php echo esc_url($urls['mp4_1080']); ?>" type="video/mp4">
						</video>
					</noscript>
				</div>
				<p class="cl-howto__note">Turn the sound on for the voiceover. Captions are built in.</p>
			</div>
		</div>
		<script type="application/ld+json"><?php echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
	</section>
	<?php
}
