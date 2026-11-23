<?php
/**
 * Trade site home page: wholesale channel letter manufacturer for sign companies.
 *
 * Primary keyword: "wholesale channel letter manufacturer" (keyword map in
 * wholesale-plan/keyword-map.md). Order of the page follows how a sign shop
 * decides on a new supplier: what you make -> the 7 types -> try a lit preview
 * -> will you compete with me -> how ordering works -> what ships in the box
 * -> how to sell it -> FAQ -> apply.
 *
 * Styles: css/trade-home.css (th- classes) on top of css/trade.css (tw- tokens
 * and buttons). The lit preview is js/trade-home.js; without it the stage shows
 * a static front lit sample. Only owner-confirmed facts appear here: no
 * turnaround days, prices, review counts or customer names.
 *
 * @package litsign
 */

$brand = wholesale_trade_brand();
$approved = wholesale_trade_can_order();
$apply_url = wholesale_trade_apply_url();
$builder_url = home_url('/channel-letter-builder/');
$hub_url = home_url('/channel-letters/');
$types = wholesale_trade_cl_types();

/** Lighting styles in the lit preview, each tied to the letter type it shows. */
$preview_styles = array(
	'front' => array('label' => 'Front lit', 'slug' => 'standard-channel-letter-front-lit'),
	'back' => array('label' => 'Back lit', 'slug' => 'standard-channel-letter-back-lit'),
	'dual' => array('label' => 'Front & back lit', 'slug' => 'standard-channel-letter-front-back-lit'),
	'halo' => array('label' => 'Halo stainless', 'slug' => 'hidden-back-halo-lit'),
);

/** Small line icons (24px grid, drawn with currentColor). */
$icon = static function ($name, $class = 'th-icon') {
	$paths = array(
		'since' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'shield' => '<path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6z"/><path d="m9 12 2 2 4-4"/>',
		'pin' => '<path d="M12 21s-6-5.6-6-11a6 6 0 0 1 12 0c0 5.4-6 11-6 11z"/><circle cx="12" cy="10" r="2.2"/>',
		'truck' => '<path d="M3 6h11v10H3z"/><path d="M14 9h4l3 3v4h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
		'award' => '<circle cx="12" cy="9" r="5.5"/><path d="m8.5 13.5-1.5 7 5-2.5 5 2.5-1.5-7"/>',
		'store' => '<path d="M4 10v10h16V10"/><path d="M3 10l2-6h14l2 6z"/><path d="M9 20v-5h6v5"/>',
		'plug' => '<path d="M9 3v5M15 3v5"/><path d="M6 8h12v3a6 6 0 0 1-12 0z"/><path d="M12 17v4"/>',
		'file' => '<path d="M14 3H6v18h12V7z"/><path d="M14 3v4h4"/><path d="m9 14 2 2 4-4"/>',
		'layers' => '<path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/>',
		'calc' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01"/>',
		'check' => '<path d="m5 12 5 5 9-10"/>',
		'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
		'text' => '<path d="M4 5h16v11H8l-4 4z"/>',
		'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
		'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'download' => '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
		'flag' => '<path d="M5 21V4"/><path d="M5 4h12l-2.5 4.5L17 13H5"/>',
		'easel' => '<path d="M9.5 3h5L20 21M9.5 3 4 21"/><path d="M6.4 13h11.2"/>',
		'tent' => '<path d="M3 20 12 4l9 16z"/><path d="m9.5 20 2.5-5 2.5 5"/>',
		'print' => '<path d="M7 9V3h10v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M7 14h10v7H7z"/>',
		'grid' => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
	);
	if (empty($paths[$name])) {
		return '';
	}
	return '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
};

$proof_icons = array('since', 'shield', 'pin', 'truck', 'award');
$faq = wholesale_trade_faq();
wholesale_trade_register_faq($faq);

get_header();
?>
<main id="primary" class="tw-page tw-home th-home">

	<section class="th-hero" aria-labelledby="th-hero-title">
		<?php $hero = wholesale_trade_hero_image(); ?>
		<picture class="th-hero__bg">
			<?php if ($hero['avif']) : ?>
				<source type="image/avif" srcset="<?php echo esc_url($hero['avif']); ?>">
			<?php endif; ?>
			<img src="<?php echo esc_url($hero['src']); ?>"<?php echo $hero['srcset'] ? ' srcset="' . esc_attr($hero['srcset']) . '" sizes="100vw"' : ''; ?> alt="Lit channel letter sign on a storefront at dusk" width="<?php echo (int) $hero['width']; ?>" height="<?php echo (int) $hero['height']; ?>" fetchpriority="high" decoding="async">
		</picture>
		<div class="tw-wrap th-hero__in">
			<div class="th-hero__copy">
				<p class="th-chip"><span class="th-chip__dot" aria-hidden="true"></span>Trade only · Sign companies &amp; installers</p>
				<h1 class="th-hero__title" id="th-hero-title">Wholesale channel letters, <em>built for sign shops</em></h1>
				<p class="th-hero__text">The channel letter manufacturer behind your sign jobs. UL listed, tested and shipped nationwide with a full-size mounting pattern.</p>
				<ul class="th-ticks">
					<li><?php echo $icon('check'); ?>UL listed</li>
					<li><?php echo $icon('check'); ?>Built in WA &amp; CA since <?php echo (int) $brand['since']; ?></li>
					<li><?php echo $icon('check'); ?>5-year warranty</li>
				</ul>
				<div class="th-hero__actions">
					<?php if ($approved) : ?>
						<a class="tw-btn tw-btn--glow th-btn-lg" href="<?php echo esc_url($builder_url); ?>">Price a set in the builder <?php echo $icon('arrow', 'th-icon th-icon--sm'); ?></a>
						<a class="tw-btn tw-btn--ghost-light th-btn-lg" href="<?php echo esc_url(home_url('/my-orders/')); ?>">My orders</a>
					<?php else : ?>
						<a class="tw-btn tw-btn--glow th-btn-lg" href="<?php echo esc_url($apply_url); ?>">Apply for a trade account <?php echo $icon('arrow', 'th-icon th-icon--sm'); ?></a>
						<a class="tw-btn tw-btn--ghost-light th-btn-lg" href="#th-types">See the 7 letter types</a>
					<?php endif; ?>
				</div>
				<?php if ($approved) : ?>
					<p class="th-hero__micro">Trade pricing is on for your account. Questions on a job? Call <a href="<?php echo esc_url($brand['phone_href']); ?>"><?php echo esc_html($brand['phone']); ?></a>.</p>
				<?php else : ?>
					<p class="th-hero__micro"><?php echo wholesale_trade_auto_approve() ? 'Takes about 3 minutes · Trade pricing opens as soon as you apply' : 'Takes about 3 minutes · Every application reviewed by a real person'; ?></p>
					<p class="th-hero__note">Already approved? <a href="<?php echo esc_url(wholesale_trade_login_url()); ?>">Log in to see your trade pricing</a></p>
				<?php endif; ?>
			</div>
			<a class="th-hero__call" href="<?php echo esc_url($brand['phone_href']); ?>">
				<span class="th-hero__call-icon"><?php echo $icon('phone'); ?></span>
				<span><small>Talk to trade sales</small><strong><?php echo esc_html($brand['phone']); ?></strong><small><?php echo esc_html($brand['hours']); ?></small></span>
			</a>
		</div>
	</section>

	<div class="tw-wrap">
		<ul class="th-proof" aria-label="Why sign companies trust us">
			<?php foreach (wholesale_trade_proof_points() as $i => $point) : ?>
				<li>
					<span class="th-proof__icon"><?php echo $icon(isset($proof_icons[$i]) ? $proof_icons[$i] : 'check'); ?></span>
					<span><strong><?php echo esc_html($point[0]); ?></strong><?php echo esc_html($point[1]); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="th-audience"><span>Built for</span> Sign companies · Sign installers · Print shops · Ad &amp; marketing agencies · General contractors &amp; architects · Multi-location brands</p>
	</div>

	<section class="tw-section th-types" id="th-types" aria-labelledby="th-types-title">
		<div class="tw-wrap">
			<div class="th-head">
				<div>
					<p class="tw-eyebrow">The product line</p>
					<h2 class="tw-h2" id="th-types-title">Seven channel letter types for every job and budget</h2>
				</div>
				<a class="th-link" href="<?php echo esc_url($hub_url); ?>">Compare all 7 side by side <?php echo $icon('arrow', 'th-icon th-icon--sm'); ?></a>
			</div>

			<ul class="th-cards" aria-label="Channel letter types">
				<?php
				foreach ($types as $slug => $type) :
					$url = wholesale_trade_type_url($slug);
					$is_aluminum = 'aluminum' === $type['family'];
					?>
					<li class="th-card th-card--<?php echo esc_attr($type['family']); ?>">
						<a class="th-card__media" href="<?php echo esc_url($url); ?>" tabindex="-1" aria-hidden="true">
							<?php echo wholesale_trade_type_image($slug, 'medium_large'); // Escaped in core. ?>
							<span class="th-card__num">Type <?php echo (int) $type['type']; ?></span>
						</a>
						<div class="th-card__body">
							<p class="th-card__family"><?php echo $is_aluminum ? 'Aluminum · acrylic face' : 'Welded stainless steel'; ?></p>
							<h3 class="th-card__name"><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($type['name']); ?></a></h3>
							<p class="th-card__short"><?php echo esc_html($type['short']); ?></p>
							<div class="th-card__foot">
								<a class="th-card__more" href="<?php echo esc_url($url); ?>">Specs<?php echo $approved ? ' &amp; pricing' : ''; ?> <?php echo $icon('arrow', 'th-icon th-icon--sm'); ?></a>
								<?php if ($approved) : ?>
									<a class="th-card__build" href="<?php echo esc_url(wholesale_trade_builder_url($slug)); ?>">Build</a>
								<?php else : ?>
									<span class="th-card__lock"><?php echo $icon('lock', 'th-icon th-icon--sm'); ?>Trade price</span>
								<?php endif; ?>
							</div>
						</div>
					</li>
				<?php endforeach; ?>
				<li class="th-card th-card--help">
					<div class="th-card__body">
						<p class="th-card__family">Not sure which type?</p>
						<h3 class="th-card__name">Send the job, we’ll help you spec it</h3>
						<p class="th-card__short">Share the shop drawing or vector art, letter height, colors and mounting. Trade sales recommends the type and lighting that fits the site.</p>
						<div class="th-card__help-actions">
							<a class="tw-btn tw-btn--primary" href="<?php echo esc_url($hub_url); ?>">Compare all 7 types</a>
							<a class="tw-btn tw-btn--ghost" href="<?php echo esc_url($brand['phone_href']); ?>"><?php echo $icon('phone', 'th-icon th-icon--sm'); ?><?php echo esc_html($brand['phone']); ?></a>
						</div>
					</div>
				</li>
			</ul>
			<p class="th-swipe" aria-hidden="true">Swipe to see all 7 types →</p>
		</div>
	</section>

	<?php
	// Print, display and event categories: featured tiles, then every category by group.
	$catalog = wholesale_trade_home_catalog();
	if ($catalog['groups']) :
		$img_attrs = static function ($alt, $sizes, $loading = 'lazy') {
			return array('alt' => $alt, 'sizes' => $sizes, 'loading' => $loading, 'decoding' => 'async');
		};
		?>
	<section class="tw-section tw-section--soft th-cats" id="th-catalog" aria-labelledby="th-cats-title">
		<div class="tw-wrap">
			<div class="th-head">
				<div>
					<p class="tw-eyebrow">Beyond channel letters</p>
					<h2 class="tw-h2" id="th-cats-title">Print, display and event products for your clients</h2>
					<p class="tw-lead">Banners, flags, trade show displays and large format print, ordered on the same trade account as your channel letters.</p>
				</div>
				<a class="th-link" href="#th-cats-all"><?php echo $icon('grid', 'th-icon th-icon--sm'); ?>Browse all <?php echo (int) $catalog['total']; ?> categories</a>
			</div>

			<?php if ($catalog['featured']) : ?>
				<ul class="th-feature" aria-label="Popular product categories">
					<?php
					$n = 0;
					foreach ($catalog['featured'] as $slug => $item) :
						$n++;
						$is_lead = 1 === $n;
						?>
						<li class="th-feature__item<?php echo $is_lead ? ' th-feature__item--lead' : ''; ?>" style="--tint:<?php echo esc_attr($item['tint']); ?>">
							<a class="th-feature__link" href="<?php echo esc_url($item['url']); ?>">
								<span class="th-feature__media">
									<?php
									if ($item['image']) {
										echo wp_get_attachment_image($item['image'], $is_lead ? 'large' : 'medium_large', false, $img_attrs('', $is_lead ? '(min-width: 992px) 560px, 100vw' : '(min-width: 992px) 280px, 50vw'));
									}
									?>
								</span>
								<span class="th-feature__body">
									<span class="th-feature__count"><?php echo esc_html(sprintf(_n('%d product', '%d products', $item['count'], 'litsign'), $item['count'])); ?></span>
									<h3 class="th-feature__name"><?php echo esc_html($item['name']); ?></h3>
									<span class="th-feature__blurb"><?php echo esc_html($item['blurb']); ?></span>
									<span class="th-feature__cta">Shop now <?php echo $icon('arrow', 'th-icon th-icon--sm'); ?></span>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<div class="th-dir" id="th-cats-all">
				<?php foreach ($catalog['groups'] as $key => $group) : ?>
					<section class="th-dir__group" aria-labelledby="th-dir-<?php echo esc_attr($key); ?>">
						<div class="th-dir__head">
							<span class="th-dir__icon"><?php echo $icon($group['icon']); ?></span>
							<div>
								<h3 class="th-dir__title" id="th-dir-<?php echo esc_attr($key); ?>"><?php echo esc_html($group['title']); ?></h3>
								<p class="th-dir__meta"><?php echo esc_html(sprintf('%d categories · %d products', count($group['items']), $group['count'])); ?></p>
							</div>
						</div>
						<ul class="th-dir__list">
							<?php foreach ($group['items'] as $item) : ?>
								<li>
									<a class="th-dir__item" href="<?php echo esc_url($item['url']); ?>">
										<span class="th-dir__thumb">
											<?php
											if ($item['image']) {
												echo wp_get_attachment_image($item['image'], 'thumbnail', false, $img_attrs('', '64px'));
											}
											?>
										</span>
										<span class="th-dir__text">
											<span class="th-dir__name"><?php echo esc_html($item['name']); ?></span>
											<span class="th-dir__count"><?php echo esc_html(sprintf(_n('%d product', '%d products', $item['count'], 'litsign'), $item['count'])); ?></span>
										</span>
										<?php echo $icon('arrow', 'th-icon th-icon--sm th-dir__arrow'); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
						<?php if ($group['all']) : ?>
							<a class="th-dir__all" href="<?php echo esc_url($group['all']); ?>">View all <?php echo esc_html(strtolower($group['title'])); ?> <?php echo $icon('arrow', 'th-icon th-icon--sm'); ?></a>
						<?php endif; ?>
					</section>
				<?php endforeach; ?>
			</div>

			<div class="th-cats__band">
				<span class="th-cats__band-icon"><?php echo $icon($approved ? 'check' : 'lock'); ?></span>
				<p class="th-cats__band-text">
					<?php if ($approved) : ?>
						<strong>Your trade pricing applies across the catalog.</strong> Need a size or material you don’t see? Trade sales can quote it.
					<?php else : ?>
						<strong>Trade pricing on every category.</strong> Prices open on your login once your business is approved.
					<?php endif; ?>
				</p>
				<div class="th-cats__band-actions">
					<?php if (!$approved) : ?>
						<a class="tw-btn tw-btn--glow" href="<?php echo esc_url($apply_url); ?>">Apply for a trade account</a>
					<?php endif; ?>
					<a class="tw-btn tw-btn--ghost-light" href="<?php echo esc_url($brand['phone_href']); ?>"><?php echo $icon('phone', 'th-icon th-icon--sm'); ?><?php echo esc_html($brand['phone']); ?></a>
				</div>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<section class="th-preview" aria-labelledby="th-preview-title" data-th-preview>
		<div class="tw-wrap th-preview__in">
			<div class="th-preview__head">
				<p class="tw-eyebrow">Try your name/brand on this wall</p>
				<h2 class="tw-h2" id="th-preview-title">See your client’s name lit up</h2>
				<p class="th-preview__text">Type a name and flip between lighting styles to show a client the difference. <?php echo $approved ? 'When it looks right, price the real set in the builder.' : 'Approved accounts price the real set in the builder: font, height, colors and trade pricing.'; ?></p>
			</div>

			<div class="th-stage" data-style="front" data-time="night" style="--face:#ffffff">
				<div class="th-stage__wall" aria-hidden="true"></div>
				<p class="th-stage__sign"><span data-preview-text>OPEN LATE</span></p>
				<p class="th-stage__cap"><span data-preview-label>Type 1 · Front Lit</span> · Preview only, glow and colors are approximate</p>
			</div>

			<div class="th-preview__panel">
				<div class="th-controls" hidden>
					<label class="th-controls__label" for="th-preview-name">Enter your text here</label>
					<input class="th-controls__input" id="th-preview-name" type="text" maxlength="16" value="OPEN LATE" autocomplete="off" spellcheck="false">

					<p class="th-controls__label" id="th-style-label">Lighting types</p>
					<div class="th-seg" role="radiogroup" aria-labelledby="th-style-label">
						<?php
						$first = true;
						foreach ($preview_styles as $key => $style) :
							?>
							<button type="button" role="radio" class="th-seg__btn" data-style="<?php echo esc_attr($key); ?>" data-type-url="<?php echo esc_url(wholesale_trade_type_url($style['slug'])); ?>" data-builder-url="<?php echo esc_url(wholesale_trade_builder_url($style['slug'])); ?>" data-type-label="<?php echo esc_attr('Type ' . $types[$style['slug']]['type'] . ' · ' . $types[$style['slug']]['name']); ?>" aria-checked="<?php echo $first ? 'true' : 'false'; ?>"><?php echo esc_html($style['label']); ?></button>
							<?php
							$first = false;
						endforeach;
						?>
					</div>

					<p class="th-controls__label" id="th-color-label">Face color options</p>
					<div class="th-swatches" role="radiogroup" aria-labelledby="th-color-label">
						<?php
						$colors = array(
								'#ffffff' => 'White',
								'#111111' => 'Black',
								'#ffe2a8' => 'Warm white',
								'#ffd400' => 'Yellow',
								'#ff8a1f' => 'Orange',
								'#ff3b30' => 'Red',
								'#2fd8ff' => 'Light blue',
								'#2f8cff' => 'Blue',
								'#0b3a9e' => 'Dark blue',
								'#22c55e' => 'Green',
							);
						$first = true;
						foreach ($colors as $hex => $name) :
							?>
							<button type="button" role="radio" class="th-swatch" data-color="<?php echo esc_attr($hex); ?>" style="--sw:<?php echo esc_attr($hex); ?>" aria-label="<?php echo esc_attr($name); ?>" aria-checked="<?php echo $first ? 'true' : 'false'; ?>"></button>
							<?php
							$first = false;
						endforeach;
						?>
					</div>

					<button type="button" class="th-daynight" aria-pressed="false" data-daynight><span class="th-daynight__knob" aria-hidden="true"></span><span data-daynight-label>Show daytime</span></button>
				</div>

				<div class="th-preview__actions">
					<?php if ($approved) : ?>
						<a class="tw-btn tw-btn--glow" href="<?php echo esc_url(wholesale_trade_builder_url('standard-channel-letter-front-lit')); ?>" data-preview-cta>Price this set in the builder</a>
					<?php else : ?>
						<a class="tw-btn tw-btn--glow" href="<?php echo esc_url($apply_url); ?>">Apply to see trade pricing</a>
					<?php endif; ?>
					<a class="th-link th-link--light" href="<?php echo esc_url(wholesale_trade_type_url('standard-channel-letter-front-lit')); ?>" data-preview-type>Type 1 · Front Lit specs <?php echo $icon('arrow', 'th-icon th-icon--sm'); ?></a>
				</div>
			</div>
		</div>
	</section>

	<section class="tw-section" aria-labelledby="th-why-title">
		<div class="tw-wrap">
			<div class="th-head th-head--center">
				<div>
					<p class="tw-eyebrow">Why sign companies outsource to us</p>
					<h2 class="tw-h2" id="th-why-title">A fabrication partner, not a competitor</h2>
					<p class="tw-lead">Your shop sells, permits and installs. We build the letters. Everything about how we work keeps the job, the client and the margin yours.</p>
				</div>
			</div>
			<ul class="th-values">
				<li><span class="th-values__icon"><?php echo $icon('store'); ?></span><div><h3 class="tw-h3">We never install</h3><p>No install crews and no selling to your end client on this site. Trade accounts only, so you never bid against your own supplier.</p></div></li>
				<li><span class="th-values__icon"><?php echo $icon('plug'); ?></span><div><h3 class="tw-h3">Ready for your installer</h3><p>Each set ships wired and tested, with a wiring diagram and a full-size pattern that marks studs and wire holes. Mount, connect, done.</p></div></li>
				<li><span class="th-values__icon"><?php echo $icon('file'); ?></span><div><h3 class="tw-h3">UL labels for the inspector</h3><p>All 7 types are UL listed with a sign section label for outdoor installation, which keeps permit sign-off simple.</p></div></li>
				<li><span class="th-values__icon"><?php echo $icon('layers'); ?></span><div><h3 class="tw-h3">Seven types, one supplier</h3><p>Aluminum face, back and dual lit, plus stainless halo, reverse, trimless and borderless. Quote the upgrade without finding a second shop.</p></div></li>
				<li><span class="th-values__icon"><?php echo $icon('calc'); ?></span><div><h3 class="tw-h3">Price it yourself, any time</h3><p>Approved accounts price full sets in the online builder: type, font, height and colors. Quote your client in minutes, not days.</p></div></li>
				<li><span class="th-values__icon"><?php echo $icon('award'); ?></span><div><h3 class="tw-h3">Covered for five years</h3><p>A 5-year warranty on every channel letter type, so the sign you sell carries a warranty you can stand behind.</p></div></li>
			</ul>
		</div>
	</section>

	<section class="tw-section tw-section--soft" aria-labelledby="th-how-title">
		<div class="tw-wrap">
			<div class="th-head th-head--center">
				<div>
					<p class="tw-eyebrow">How trade ordering works</p>
					<h2 class="tw-h2" id="th-how-title">From application to crated letters</h2>
					<p class="tw-lead">Once your business is approved, ordering works like any web order.</p>
				</div>
			</div>
			<ol class="th-steps">
				<li class="th-step<?php echo $approved ? ' is-done' : ''; ?>">
					<h3 class="tw-h3">Apply</h3>
					<p>Tell us about your business in about three minutes. Add a resale certificate if you buy tax-exempt.</p>
				</li>
				<li class="th-step<?php echo $approved ? ' is-done' : ''; ?>">
					<?php if (wholesale_trade_auto_approve()) : ?>
						<h3 class="tw-h3">Get instant access</h3>
						<p>Your account opens the moment you apply and you are logged in, with trade pricing on every page.</p>
					<?php else : ?>
						<h3 class="tw-h3">Get approved</h3>
						<p>A real person reviews every application and emails you the decision. Trade pricing opens on your login.</p>
					<?php endif; ?>
				</li>
				<li class="th-step">
					<h3 class="tw-h3">Design &amp; price</h3>
					<p>Build the set in the online builder, or send your shop drawings and vector art to trade sales.</p>
				</li>
				<li class="th-step">
					<h3 class="tw-h3">We build &amp; ship</h3>
					<p>Fabricated, wired, tested and UL labeled, then crated and shipped with the pattern and wiring diagram.</p>
				</li>
			</ol>
			<div class="th-steps__cta">
				<?php if ($approved) : ?>
					<a class="tw-btn tw-btn--primary th-btn-lg" href="<?php echo esc_url($builder_url); ?>">You’re approved: open the builder</a>
				<?php else : ?>
					<a class="tw-btn tw-btn--primary th-btn-lg" href="<?php echo esc_url($apply_url); ?>">Start step 1: apply now</a>
					<span><?php echo wholesale_trade_auto_approve() ? 'About three minutes. Pricing opens right away.' : 'About three minutes. Reviewed by a real person.'; ?></span>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="tw-section" aria-labelledby="th-box-title">
		<div class="tw-wrap th-box">
			<figure class="th-box__media">
				<img src="<?php echo esc_url(content_url('uploads/storefront-files/channel-letter-raceway-assembly.jpg')); ?>" alt="Cutaway drawing of an aluminum channel letter raceway with mounting tabs" width="2000" height="1500" loading="lazy" decoding="async">
			</figure>
			<div>
				<p class="tw-eyebrow">In every box</p>
				<h2 class="tw-h2" id="th-box-title">Shipped ready for your install crew</h2>
				<ul class="tw-checks th-box__list">
					<li>Letters with LED modules wired inside every letter</li>
					<li>UL sign section label for outdoor installation</li>
					<li>3 ft power lead per set</li>
					<li>Wiring diagram for your electrician</li>
					<li>Full-size mounting pattern with stud and wire locations</li>
					<li>Installation manual</li>
				</ul>
				<p class="th-box__note">Mounting fasteners are not included, so your crew can use what suits the wall.</p>
				<a class="tw-btn tw-btn--ghost" href="<?php echo esc_url(content_url('uploads/storefront-files/channel-letter-installation-manual.pdf')); ?>"><?php echo $icon('download', 'th-icon th-icon--sm'); ?>Install manual (PDF)</a>
			</div>
		</div>
	</section>

	<section class="tw-section th-tiers-wrap" aria-labelledby="th-tiers-title">
		<div class="tw-wrap">
			<div class="th-head th-head--center">
				<div>
					<p class="tw-eyebrow">Win more bids</p>
					<h2 class="tw-h2" id="th-tiers-title">Quote a good, better and best option on every job</h2>
					<p class="tw-lead">Clients say yes faster when they choose between options. One supplier for all seven types makes a three-tier quote easy.</p>
				</div>
			</div>
			<ol class="th-tiers">
				<li class="th-tier">
					<p class="th-tier__level"><span class="th-tier__bars" data-level="1" aria-hidden="true"><i></i><i></i><i></i></span>Good</p>
					<h3 class="th-tier__type"><a href="<?php echo esc_url(wholesale_trade_type_url('standard-channel-letter-front-lit')); ?>">Type 1 · Front lit</a></h3>
					<p>Bright, legible face lit letters that meet most landlord criteria.</p>
				</li>
				<li class="th-tier th-tier--mid">
					<p class="th-tier__level"><span class="th-tier__bars" data-level="2" aria-hidden="true"><i></i><i></i><i></i></span>Better</p>
					<h3 class="th-tier__type"><a href="<?php echo esc_url(wholesale_trade_type_url('standard-channel-letter-front-back-lit')); ?>">Type 3 · Front &amp; back lit</a></h3>
					<p>The same bright face plus a halo on the wall, so the storefront stands out.</p>
				</li>
				<li class="th-tier th-tier--top">
					<p class="th-tier__level"><span class="th-tier__bars" data-level="3" aria-hidden="true"><i></i><i></i><i></i></span>Best</p>
					<h3 class="th-tier__type"><a href="<?php echo esc_url(wholesale_trade_type_url('hidden-back-halo-lit')); ?>">Type 4 · Halo lit stainless</a> <span>or</span> <a href="<?php echo esc_url(wholesale_trade_type_url('inset-acrylic-face-lit-with-border-no-trimcap')); ?>">Type 6 · Trimless</a></h3>
					<p>A premium metal finish by day and a halo or trimless glow by night.</p>
				</li>
			</ol>
			<p class="tw-center th-tiers__more"><a class="th-link" href="<?php echo esc_url(home_url('/for-sign-companies/')); ?>">How outsourcing channel letter fabrication works for your shop <?php echo $icon('arrow', 'th-icon th-icon--sm'); ?></a></p>
		</div>
	</section>

	<section class="tw-section tw-section--soft th-faq" aria-labelledby="tw-faq-title">
		<div class="tw-wrap th-faq__in">
			<div class="th-faq__side">
				<p class="tw-eyebrow">Questions</p>
				<h2 id="tw-faq-title" class="tw-h2">Trade FAQ</h2>
				<p class="th-faq__intro">Straight answers for sign shops vetting a new fabricator. Anything else, ask trade sales.</p>
				<div class="th-contact">
					<p class="th-contact__title">Talk to trade sales</p>
					<ul>
						<li><?php echo $icon('phone', 'th-icon th-icon--sm'); ?><a href="<?php echo esc_url($brand['phone_href']); ?>">Call <?php echo esc_html($brand['phone']); ?></a></li>
						<li><?php echo $icon('text', 'th-icon th-icon--sm'); ?><a href="<?php echo esc_url($brand['text_href']); ?>">Text <?php echo esc_html($brand['text']); ?></a></li>
						<li><?php echo $icon('mail', 'th-icon th-icon--sm'); ?><a href="<?php echo esc_url('mailto:' . $brand['email']); ?>"><?php echo esc_html($brand['email']); ?></a></li>
						<li><?php echo $icon('since', 'th-icon th-icon--sm'); ?><span><?php echo esc_html($brand['hours']); ?></span></li>
					</ul>
				</div>
			</div>
			<div class="th-faq__list">
				<?php foreach ($faq as $i => $item) : ?>
					<details class="tw-faq__item th-faq__item"<?php echo 0 === $i ? ' open' : ''; ?>><summary><?php echo esc_html($item['q']); ?></summary><p><?php echo esc_html($item['a']); ?></p></details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php wholesale_trade_render_cta_band(); ?>

	<p class="tw-crosslink">Buying a sign for your own business? <a href="<?php echo esc_url($brand['retail_url']); ?>">Shop our retail site, <?php echo esc_html($brand['retail_name']); ?></a>.</p>
</main>
<?php
get_footer();
