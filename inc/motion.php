<?php
/**
 * Motion layer: scroll reveals now, then hover/tap feedback and the other
 * effects planned in animation-plan/ at the site root (css/motion.css,
 * js/motion.js).
 *
 * It lives in its own files because a scheduled pull from live overwrites
 * edits to existing theme files. Outside these files there is only the
 * require in functions.php, one toggle card in inc/settings-page.php and a
 * one-line guard in js/main.js, so the old desktop reveal there stands down
 * while this layer runs. With the setting off nothing here prints and the
 * site behaves exactly as before.
 *
 * Notes on js/motion.js (kept here because comments in the script would count
 * against its 3 KB budget; PHP comments are never sent to visitors). Reports
 * with the measurements: animation-plan/reports/ at the site root.
 *
 * - Nothing on screen or above it is ever hidden, and any error adds
 *   html.mo-failsafe and shows everything.
 * - Two presentations of the same selection (motion v2, 2026-11-15):
 *   computers (min-width 992px and a hovering pointer) get the timed reveal
 *   below; phones, tablets and touch screens get a scroll-linked rise instead
 *   (class mo-sl, CSS view() timeline): opacity and position follow the
 *   scroll position exactly, over the first 20vh after an element enters at
 *   the bottom edge, so it can never lag behind a fast scroll or leave a blank
 *   gap (why main.js used to keep phones still). Grid cards also scale from
 *   94% (mo-sl-c); footer blocks only fade, over at most their own height
 *   (mo-sl-f), so even the last block on the page always finishes. --mo-lag
 *   (the element's x as a share of the screen width, from the observer entry,
 *   no layout read) starts later columns a little later. Once an element's
 *   top passes 60% of the screen its classes are removed (reveal once).
 *   Browsers without scroll-driven animations (Firefox, Safari before 26) get
 *   no phone tagging at all. Tagging never reads computed styles: a read
 *   after each class change forced a full style recalc and layout per element
 *   (up to 180ms of CPU on phones); the QA run checks instead that no tagged
 *   element has its own animation or transform. The home/CL showcase section (.hc-shop-cl) had
 *   overflow:hidden, which made it the scroll container of every card in it
 *   (their timelines never moved); css/motion.css switches it to overflow:clip
 *   + display:flow-root on these screens, which clips and lays out the same.
 * - GRIDS is looked up in two idle slices (each getElementsByClassName walks
 *   the whole DOM; 23 walks in one task took ~45ms at 4x CPU on Shop). v2
 *   added the category product grid, FAQ lists, product info blocks, the
 *   category guide blocks, guide steps/checklists and search results, and
 *   dropped the classes of the removed ad quote page (cla-*) and the type
 *   rails inside <nav>, which SKIP always excluded anyway (sideways scrollers
 *   on phones). Worst motion.js task at 4x CPU after v2: 34ms (Shop).
 * - Reveal selection (desktop): children of the GRIDS classes stagger 60ms
 *   apart, counted per parent within one observer callback, capped at 300ms;
 *   only the first 24 per grid (Shop prints 130, most collapsed). A section
 *   holding a grid doesn't fade as one block: its other blocks rise in turn,
 *   a kicker (KICK) fades, a block starting with a kicker is a "head" (kicker
 *   first, rest 120ms later). Other sections rise as one block; footer blocks
 *   only fade. SKIP: header, nav, modals, heroes, the ad quote box, the
 *   review carousel and aria-hidden content; anything with a form field is
 *   never hidden. Outermost item only. Grids and sections are looked up with
 *   getElementsByClassName: a 20-part querySelectorAll cost ~5ms on Shop.
 * - Setup runs in idle time (requestIdleCallback) in four short tasks.
 * - The reveal observer's top margin is 99999px, so anything above the
 *   viewport counts as seen and a jump past it (reload, back button) shows it
 *   at once; a page opened at a #hash skips reveals. Reveals start 10% below
 *   the screen; an element already on screen when its callback fires
 *   (rootBounds.bottom / 1.1, no layout read: reading innerHeight or scrollY
 *   in a callback forced a 1.5s layout on Shop) means a jump or a fast
 *   scroll, so it shows at once. When a reveal ends every class and
 *   --mo-delay are removed, so hover styles and will-change are untouched.
 * - "The sign switches on": photos of lit letters (.hc-shop-cl-media, the
 *   channel letter listing's .pb-image-top, .cla-style-image) that start below
 *   the fold get a span.mo-glow (veil + warm glow; a span because some frames
 *   already use ::after). It powers up once the photo is 25% into the screen
 *   and its image has loaded, otherwise it is removed and the photo shows lit.
 * - Product card shine (setupShine, 2026-11-21, owner: "products should
 *   glow on scroll ... attractive"): every other product card photo frame
 *   (Shop, category listing, home cards, related products, B&D/WG, search,
 *   guide/location/storefront type cards; not heroes, nav rails or CL frames,
 *   which keep the light-up) gets a span.mo-shine once 12% into the screen:
 *   a white gloss band sweeps across (::before, translate) while a blue inner
 *   ring + soft light fades in and out (::after, opacity). Composited only,
 *   no layout change; cards in one row stagger 110ms; before window load it
 *   waits 400ms; plays after the image loads. Runs on 'full' pages without
 *   reduced motion, independent of the reveals (so phones without view()
 *   timelines get it too). Desktop hover replays the sweep and the card gets
 *   a blue glow shadow (css/motion.css 6b).
 * - Loops ([data-motion=loop], the green live dot, card flags) pause off
 *   screen and while the tab is hidden; css/motion.css limits them to 3 runs.
 * - html.mo-loaded is set on load; hero effects in css/motion.css wait for it
 *   so they never touch LCP.
 * - Feedback (setupFeedback): price total ease-in (MutationObserver on
 *   .price-total, only when its text really changed; the text is never
 *   touched), quantity nudge, add-to-cart check on retails:cart-added (fired
 *   by js/mini-cart.js only after a successful add), one nudge per field that
 *   fires "invalid" (only on submit checks). Web Animations, skipped under
 *   reduced motion.
 *
 * Notes on css/motion.css (comments there were cut to keep it in its 5 KB
 * budget):
 *
 * 1. Tokens: the shared timings and curves (animation-plan/00-CONTEXT.md).
 * 2. Reveal: every hidden state needs html[data-motion="full"] AND a class
 *    js/motion.js adds, so with JS off or failing nothing is ever hidden.
 *    Hidden states have no transition (tagging below the fold never
 *    animates); only .is-in does. .mo-head: kicker first, rest 120ms later.
 *    The old .scroll-reveal is kept inert in case main.js ever runs too.
 * 3. Phones and touch (< 992px or no hover): the scroll-linked rise for
 *    elements js/motion.js tagged mo-sl (see above); fill "backwards" so tap
 *    and hover states work once it has finished. (Replaced the 10px "settle"
 *    of v1, which visitors could hardly see.)
 * 4b. Desktop photo zoom (v2): card photos that had no hover zoom yet (home
 *    product cards, category cards, related products, guide type cards) scale
 *    to 105% inside their frames; frames that already zoom (Shop, CL cards,
 *    banners & displays cards) keep their own.
 * 4. Micro-interactions: zero-specificity blue focus ring (theme focus styles
 *    win); button press 0.97 on every level, lift on hover/focus for buttons
 *    that had none; the buy box buttons need their own rule (the theme's
 *    hover rule uses ids); lift for cards that had none, press on touch;
 *    chips spring back; add-to-cart check (SVG mask); FAQ answers fade in and
 *    the "+" turns (rotate property, so the icon's translate stays). Home
 *    cards, add to cart, the ad page buttons, the mega menu and the cart
 *    count already animate in their own CSS and are left alone.
 *    Touch: the home cards' own :hover/:focus-within lift (home-catalog.css)
 *    stuck on after a tap; it is cancelled on touch screens unless a child
 *    has keyboard focus.
 * 5. Scroll UI: CSS scroll timelines only. Header shadow (::after, desktop,
 *    0-24px), reading bar (scaleX on scroll(root)), back-to-top (fades in
 *    between 1.5 and 2 screens; moved above the phone bars with translate (72px
 *    for the 69px landing bar, 112px for the product buy bar, which wraps to
 *    115px at 320px wide) so
 *    it is never a layout shift; appears at once under reduced motion).
 * 6. Hero: after load only; the content block drifts up 24px as the hero
 *    leaves (no fill, so nothing applies at the top); one sheen across the
 *    hero button 1.2s after load (desktop). Light-up: veil 0.22 -> 0 and a
 *    warm glow 0 -> 0.35 -> 0.18 -> 0, opacity only, one smooth ramp. Photo
 *    swap: the new product photo stays under the skeleton (.sk-swap) and
 *    fades in once loaded. Loops (live dot, card flags) stop after 3 runs and
 *    pause off screen or in hidden tabs.
 * 7. Failsafe, print and reduced-motion overrides, last on purpose.
 *
 * @package retails
 */

defined('ABSPATH') || exit;

/**
 * Settings > Storefront Sign > Motion effects. On unless saved as 0.
 */
function retails_motion_enabled()
{
	$value = get_option('retails_motion_enabled', null);
	return null === $value || '' === $value ? true : (bool) (int) $value;
}

/**
 * Registered here, like the exit survey switch, so the toggle needs no edit to
 * retails_setting_defaults() in functions.php.
 */
function retails_motion_register_setting()
{
	register_setting('retails_settings', 'retails_motion_enabled', array(
		'type' => 'integer',
		'default' => 1,
		'sanitize_callback' => static function ($value) {
			return is_scalar($value) && '1' === (string) $value ? 1 : 0;
		},
	));
}
add_action('admin_init', 'retails_motion_register_setting');

/**
 * Cached pages carry the head flag, so they must be rebuilt when the switch
 * changes. (The page cache also purges on retails_* saves; this covers a
 * change made any other way.)
 */
function retails_motion_purge_cache()
{
	if (function_exists('sfs_page_cache_purge')) {
		sfs_page_cache_purge();
	}
}
add_action('update_option_retails_motion_enabled', 'retails_motion_purge_cache');
add_action('add_option_retails_motion_enabled', 'retails_motion_purge_cache');

/**
 * How much motion this request gets:
 * - 'full': content, landing, product and shop pages.
 * - 'calm': cart, checkout, account and order screens, where only button press
 *   and focus states apply (the same page list litsign_scripts() uses).
 * - 'off':  setting off, the channel letter builder, the B2 calculator, admin,
 *   AJAX, REST, feeds and embeds.
 *
 * Needs the main query, so call it from wp_head / wp_enqueue_scripts or later.
 */
function retails_motion_level()
{
	static $level = null;
	if (null !== $level) {
		return $level;
	}

	if (!retails_motion_enabled()
		|| is_admin()
		|| wp_doing_ajax()
		|| (defined('REST_REQUEST') && REST_REQUEST)
		|| is_feed()
		|| is_embed()
		|| is_page('channel-letter-builder')
		|| is_page_template('b2calc.php')) {
		$level = 'off';
	} elseif (is_page(array('cart', 'checkout', 'account', 'my-orders', 'track-order', 'login', 'signup', 'pay'))
		|| is_page_template('payment.php')
		|| is_singular('order')
		|| get_query_var('retails_thank_you')) {
		$level = 'calm';
	} else {
		$level = 'full';
	}

	$level = (string) apply_filters('retails_motion_level', $level);
	if (!in_array($level, array('full', 'calm', 'off'), true)) {
		$level = 'off';
	}
	return $level;
}

/**
 * Tags <html data-motion="full|calm|reduce"> before anything paints. js/main.js
 * reads it to leave reveals to js/motion.js, and css/motion.css keys its hidden
 * states on it, so nothing is ever hidden without this flag.
 */
function retails_motion_head_flag()
{
	$level = retails_motion_level();
	if ('off' === $level) {
		return;
	}
	printf(
		'<script id="retails-motion-flag">(function(d,l){try{if(window.matchMedia&&matchMedia("(prefers-reduced-motion: reduce)").matches)l="reduce"}catch(e){}d.setAttribute("data-motion",l)})(document.documentElement,%s);</script>' . "\n",
		wp_json_encode($level)
	);
	if ('full' === $level) {
		echo retails_motion_hero_style() . "\n";
	}
}
add_action('wp_head', 'retails_motion_head_flag', 0);

/**
 * Hero entrance (motion v2, 2026-11-15). Inline in <head> because it has to
 * apply from the very first paint: css/motion.css loads after that, and an
 * entrance that started late would make the hero jump.
 *
 * - The headline, text and buttons only glide up 18px; their opacity never
 *   changes, so they paint (and count for LCP and FCP) at once. Only the small
 *   labels around them (breadcrumbs, kickers, trust rows) also fade in.
 * - Children of the hero copy blocks start 70ms apart, capped at 280ms.
 * - While the page loader covers the page (a link click; never on the first
 *   page of a visit, where it is display:none) the entrance waits, so it plays
 *   as the loader fades away instead of behind it.
 * - Only on 'full' pages, never under reduced motion (the flag then reads
 *   "reduce"), and nothing at all with the setting off.
 */
function retails_motion_hero_style()
{
	$kids = ':is(.hc-hero-copy,.home-hero-copy,.bd-hero-copy,.sc-hero-copy,.contact-hero-copy,.pkg-hero,.reviews-page-head,.srp-hero .srp-container,.error-404-content,.sf-hero>.container)>:not(form,[aria-hidden="true"])';
	$all = $kids . ',.sc-hero-trust,.site-breadcrumbs,.site-main>.container>h1';
	$fade = ':is(.site-breadcrumbs,.home-hero-breadcrumb,.srp-crumbs,.cl-kicker,.hc-eyebrow,.home-hero-eyebrow,.contact-eyebrow,.error-404-eyebrow,.product-section-eyebrow,.sf-byline,.gd-reviewed)';
	$p = 'html[data-motion="full"] ';
	$css = '@media (prefers-reduced-motion:no-preference){'
		. $p . ':is(' . $all . '){animation:mo-in .8s cubic-bezier(.22,1,.36,1) backwards}'
		. $p . ':is(' . $all . ')' . $fade . '{animation-name:mo-in-f}'
		. $p . $kids . ':nth-child(2){animation-delay:70ms}'
		. $p . $kids . ':nth-child(3){animation-delay:140ms}'
		. $p . $kids . ':nth-child(4){animation-delay:210ms}'
		. $p . $kids . ':nth-child(n+5),' . $p . ':is(' . $all . ').sc-hero-trust{animation-delay:280ms}'
		. 'html[data-motion="full"]:not(.retails-page-loader-landing,.retails-page-loader-failsafe):has(.retails-page-loader:is(.is-intro,.is-idle):not(.is-hidden)) :is(' . $all . '){animation-play-state:paused}'
		. '}'
		. '@keyframes mo-in{from{transform:translate3d(0,18px,0)}}'
		. '@keyframes mo-in-f{from{opacity:0;transform:translate3d(0,10px,0)}}';
	return '<style id="retails-motion-hero">' . $css . '</style>';
}

function retails_motion_assets()
{
	if ('off' === retails_motion_level()) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style('retails-motion', $uri . '/css/motion.css', array(), (string) filemtime($dir . '/css/motion.css'));
	wp_enqueue_script('retails-motion', $uri . '/js/motion.js', array(), (string) filemtime($dir . '/js/motion.js'), array('in_footer' => true, 'strategy' => 'defer'));
}
// After retails_ui_polish_assets (100), so motion.css prints after ui-polish.css.
add_action('wp_enqueue_scripts', 'retails_motion_assets', 101);

/**
 * motion.css never holds the first paint back: it only adds effects, and its
 * hidden states need the flag plus js/motion.js anyway. Same swap the theme
 * uses for the home guide styles in inc/performance.php.
 */
function retails_motion_async_style($tag, $handle)
{
	if ('retails-motion' !== $handle) {
		return $tag;
	}
	$async = str_replace("media='all'", "media='print' onload=\"this.media='all'\"", $tag);
	return $async . '<noscript>' . preg_replace("/ id='[^']*'/", '', $tag, 1) . '</noscript>';
}
add_filter('style_loader_tag', 'retails_motion_async_style', 10, 2);

function retails_motion_body_class($classes)
{
	$level = retails_motion_level();
	if ('off' !== $level) {
		$classes[] = 'mo-level-' . $level;
	}
	return $classes;
}
add_filter('body_class', 'retails_motion_body_class');

/**
 * Long reading pages that get the reading progress bar: guide, location and
 * industry pages (inc/guide-pages.php), plus the long content pages. Not the
 * home page, Shop, product, category, cart or checkout pages.
 */
function retails_motion_is_long_page()
{
	if (function_exists('retails_guide_current') && retails_guide_current()) {
		return true;
	}
	return is_page(array('how-to-install-channel-letters', 'warranty', 'shipping-returns', 'channel-letter-cost', 'storefront-signs'));
}

/**
 * Scroll UI (prompt 05), printed on full pages only. Both are position: fixed
 * and hidden until css/motion.css shows them with a scroll timeline, so they
 * never move the page; with JS off (no data-motion flag) they stay hidden.
 * The back-to-top handler is inline so js/motion.js stays inside its budget:
 * it scrolls up (instantly under reduced motion) and moves keyboard focus to
 * <main> (the skip link would pop up visibly after a mouse click).
 */
function retails_motion_scroll_ui()
{
	if ('full' !== retails_motion_level()) {
		return;
	}
	if (retails_motion_is_long_page()) {
		echo '<div class="mo-progress" aria-hidden="true"></div>' . "\n";
	}
	echo '<button type="button" class="mo-top" aria-label="Back to top" onclick="window.scrollTo({top:0,behavior:matchMedia(\'(prefers-reduced-motion: reduce)\').matches?\'instant\':\'smooth\'});var t=document.querySelector(\'main\')||document.querySelector(\'.skip-link\');if(t){t.matches(\'a,[tabindex]\')||t.setAttribute(\'tabindex\',\'-1\');t.focus({preventScroll:true})}">'
		. '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M12 19V5M5 12l7-7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>' . "\n";
}
add_action('wp_footer', 'retails_motion_scroll_ui', 5);

// Animated header logo. Loaded from here so functions.php needs no change.
require_once __DIR__ . '/logo-motion.php';
