<?php
/**
 * Slide-in cart drawer ("mini cart") opened from the header cart icon.
 *
 * Pages are served from the NitroPack cache, so the drawer is printed empty and filled
 * over admin-ajax.php, which is never cached:
 *  - wholesale_mini_cart          returns the drawer contents and a fresh nonce
 *  - wholesale_mini_cart_qty      changes a line's quantity
 *  - wholesale_mini_cart_remove   removes a line (kept in the session so it can be undone)
 *  - wholesale_mini_cart_restore  puts the last removed line back
 *  - wholesale_mini_cart_add      the product page's Add To Cart, without leaving the page
 *
 * Every response carries the rendered drawer HTML, the line count and the subtotal, and
 * re-syncs the sso_cart_count cookie used by the header badge.
 *
 * @package litsign
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * The cart, re-read from the session so changes made in this request are included.
 */
function wholesale_mini_cart_fresh()
{
	global $cart;
	wholesale_get_cart();
	$cart = new Cart();
	return $cart;
}

function wholesale_mini_cart_money($value)
{
	return '$' . number_format((float) $value, 2);
}

/**
 * Drawer body + footer for the current cart.
 */
function wholesale_mini_cart_html($cart)
{
	$items = $cart->get_items();
	$shop_url = home_url('/#product-box-container');

	ob_start();

	if (!$items) :
		?>
		<div class="mc-empty">
			<div class="mc-empty__icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="34" height="34"><path fill="currentColor" d="M7 18a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM1 2v2h2l3.6 7.6-1.35 2.4A2 2 0 0 0 7 17h12v-2H7.4a.25.25 0 0 1-.22-.37l.9-1.63h7.45a2 2 0 0 0 1.75-1.03l3.58-6.5A1 1 0 0 0 20 4H5.2l-.94-2H1zm16 16a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
			</div>
			<h3 class="mc-empty__title">Your cart is empty</h3>
			<p class="mc-empty__text">Choose a sign to see your price instantly, or design your channel letters online.</p>
			<a class="mc-btn mc-btn--primary" href="<?php echo esc_url($shop_url); ?>" data-mc-close-link>Shop Channel Letters</a>
			<a class="mc-btn mc-btn--ghost" href="<?php echo esc_url(home_url('/channel-letter-builder/')); ?>">Design Your Sign Online</a>
		</div>
		<?php
		return array('body' => ob_get_clean(), 'footer' => '');
	endif;
	?>
	<ul class="mc-lines">
		<?php foreach ($items as $item) :
			$details = (array) ($item->product_details ?? array());
			$design_url = !empty($details['Design Url']) ? $details['Design Url'] : '';
			$image = $design_url ?: ($item->product_thumbnail ?? '');
			$quantity = max(1, (int) $item->product_quantity);
			$product_url = get_permalink($item->product_id);
			$is_cl = has_term('channel-letters', 'product_category', $item->product_id);
			$edit_url = $is_cl && !empty($item->design_id)
				? add_query_arg(array('product_id' => absint($item->product_id), 'edit_design' => 'true'), home_url('/channel-letter-builder/'))
				: '';

			// A short, plain-text summary; the cart page has the full details.
			$specs = wholesale_item_specs($item);
			unset($specs['Design Url'], $specs['My Artwork']);
			$spec_lines = array();
			foreach ($specs as $label => $value) {
				$value = trim(wp_strip_all_tags($value));
				if ('' !== $value) {
					$spec_lines[] = array($label, $value);
				}
			}
			$shown_specs = array_slice($spec_lines, 0, 3);
			$more_specs = count($spec_lines) - count($shown_specs);
			$line_id = (string) $item->cart_id;
			$qty_id = 'mc-qty-' . sanitize_html_class($line_id);
			?>
			<li class="mc-line" data-mc-line="<?php echo esc_attr($line_id); ?>">
				<a class="mc-line__media" href="<?php echo esc_url($product_url); ?>" tabindex="-1" aria-hidden="true">
					<?php if ($image) : ?>
						<img src="<?php echo esc_url($image); ?>" alt="" loading="lazy" decoding="async">
					<?php endif; ?>
				</a>
				<div class="mc-line__body">
					<div class="mc-line__head">
						<a class="mc-line__title" href="<?php echo esc_url($product_url); ?>"><?php echo esc_html($item->product_title); ?></a>
						<button type="button" class="mc-line__remove" data-mc-remove aria-label="<?php echo esc_attr('Remove ' . $item->product_title . ' from cart'); ?>">
							<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/></svg>
						</button>
					</div>
					<?php if ($shown_specs) : ?>
						<ul class="mc-line__specs">
							<?php foreach ($shown_specs as $spec) : ?>
								<li><span><?php echo esc_html($spec[0]); ?>:</span> <?php echo esc_html($spec[1]); ?></li>
							<?php endforeach; ?>
							<?php if ($more_specs > 0) : ?>
								<li class="mc-line__more">+<?php echo esc_html($more_specs); ?> more <?php echo esc_html(_n('option', 'options', $more_specs, 'litsign')); ?></li>
							<?php endif; ?>
						</ul>
					<?php endif; ?>
					<?php if ($edit_url) : ?>
						<a class="mc-line__edit" href="<?php echo esc_url($edit_url); ?>">Edit design</a>
					<?php endif; ?>
					<div class="mc-line__foot">
						<div class="mc-qty" role="group" aria-label="<?php echo esc_attr('Quantity for ' . $item->product_title); ?>">
							<button type="button" class="mc-qty__btn" data-mc-step="-1" aria-label="Decrease quantity"<?php echo $quantity <= 1 ? ' disabled' : ''; ?>>
								<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path stroke="currentColor" stroke-width="2.4" stroke-linecap="round" d="M6 12h12"/></svg>
							</button>
							<label class="screen-reader-text" for="<?php echo esc_attr($qty_id); ?>">Quantity</label>
							<input class="mc-qty__input" id="<?php echo esc_attr($qty_id); ?>" type="number" inputmode="numeric" min="1" max="1000" value="<?php echo esc_attr($quantity); ?>" data-mc-qty data-mc-current="<?php echo esc_attr($quantity); ?>">
							<button type="button" class="mc-qty__btn" data-mc-step="1" aria-label="Increase quantity"<?php echo $quantity >= 1000 ? ' disabled' : ''; ?>>
								<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path stroke="currentColor" stroke-width="2.4" stroke-linecap="round" d="M6 12h12M12 6v12"/></svg>
							</button>
						</div>
						<div class="mc-line__price">
							<strong><?php echo esc_html(wholesale_mini_cart_money($item->product_subtotal)); ?></strong>
							<?php if ($quantity > 1) : ?>
								<span><?php echo esc_html(wholesale_mini_cart_money($item->product_subtotal / $quantity)); ?> each</span>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
	$body = ob_get_clean();

	ob_start();
	?>
	<dl class="mc-totals">
		<div>
			<dt>Subtotal <span>(<?php echo esc_html(count($items) . ' ' . _n('item', 'items', count($items), 'litsign')); ?>)</span></dt>
			<dd><?php echo esc_html(wholesale_mini_cart_money($cart->sub_total)); ?></dd>
		</div>
	</dl>
	<p class="mc-note">Shipping and tax are calculated at checkout.</p>
	<a class="mc-btn mc-btn--primary mc-btn--lg" href="<?php echo esc_url(home_url('/checkout/')); ?>">
		Checkout
		<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
	</a>
	<a class="mc-btn mc-btn--ghost" href="<?php echo esc_url(home_url('/cart/')); ?>">View full cart</a>
	<ul class="mc-assurance">
		<li><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6z M9 12l2 2 4-4"/></svg>Secure checkout</li>
		<li><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25c1.1.37 2.3.57 3.6.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z"/></svg><a href="tel:+18664362101">866-436-2101</a></li>
	</ul>
	<?php
	return array('body' => $body, 'footer' => ob_get_clean());
}

/**
 * JSON payload describing the cart after a change.
 *
 * @param array $extra Extra keys (notice, added, undo...).
 */
function wholesale_mini_cart_send($extra = array())
{
	$cart = wholesale_mini_cart_fresh();
	wholesale_sync_cart_cookie();
	$html = wholesale_mini_cart_html($cart);
	$notice = '';
	if (!empty($_SESSION['wholesale_cart_notice'])) {
		$notice = (string) $_SESSION['wholesale_cart_notice'];
		unset($_SESSION['wholesale_cart_notice']);
	}

	nocache_headers();
	wp_send_json_success(array_merge(array(
		'count' => count($cart->get_items()),
		'subtotal' => wholesale_mini_cart_money($cart->sub_total),
		'body' => $html['body'],
		'footer' => $html['footer'],
		'nonce' => wp_create_nonce('wholesale_mini_cart'),
		'notice' => $notice,
	), $extra));
}

function wholesale_mini_cart_check_nonce()
{
	if (!check_ajax_referer('wholesale_mini_cart', 'nonce', false)) {
		// The cached page may hold an old nonce: send a fresh one so the script can retry once.
		nocache_headers();
		wp_send_json_error(array(
			'code' => 'bad_nonce',
			'message' => 'Your session was refreshed. Please try again.',
			'nonce' => wp_create_nonce('wholesale_mini_cart'),
		), 403);
	}
}

function wholesale_ajax_mini_cart()
{
	wholesale_mini_cart_send();
}
add_action('wp_ajax_wholesale_mini_cart', 'wholesale_ajax_mini_cart');
add_action('wp_ajax_nopriv_wholesale_mini_cart', 'wholesale_ajax_mini_cart');

function wholesale_ajax_mini_cart_qty()
{
	wholesale_mini_cart_check_nonce();
	$cart_id = isset($_POST['cart_id']) ? sanitize_text_field(wp_unslash($_POST['cart_id'])) : '';
	$quantity = isset($_POST['quantity']) ? min(1000, max(1, absint($_POST['quantity']))) : 1;
	if ('' !== $cart_id) {
		wholesale_mini_cart_fresh()->update_quantity($cart_id, $quantity);
	}
	wholesale_mini_cart_send();
}
add_action('wp_ajax_wholesale_mini_cart_qty', 'wholesale_ajax_mini_cart_qty');
add_action('wp_ajax_nopriv_wholesale_mini_cart_qty', 'wholesale_ajax_mini_cart_qty');

function wholesale_ajax_mini_cart_remove()
{
	wholesale_mini_cart_check_nonce();
	$cart_id = isset($_POST['cart_id']) ? sanitize_text_field(wp_unslash($_POST['cart_id'])) : '';
	$cart = wholesale_mini_cart_fresh();
	$removed = null;
	foreach ($cart->get_items() as $position => $item) {
		if ((string) $item->cart_id === $cart_id) {
			$removed = array('position' => $position, 'item' => $item);
			break;
		}
	}
	if ($removed) {
		$_SESSION['wholesale_cart_removed'] = wp_json_encode($removed);
		$cart->remove_item($cart_id);
	}
	wholesale_mini_cart_send(array(
		'removed' => $removed ? (string) $removed['item']->product_title : '',
	));
}
add_action('wp_ajax_wholesale_mini_cart_remove', 'wholesale_ajax_mini_cart_remove');
add_action('wp_ajax_nopriv_wholesale_mini_cart_remove', 'wholesale_ajax_mini_cart_remove');

function wholesale_ajax_mini_cart_restore()
{
	wholesale_mini_cart_check_nonce();
	$removed = !empty($_SESSION['wholesale_cart_removed']) ? json_decode((string) $_SESSION['wholesale_cart_removed']) : null;
	unset($_SESSION['wholesale_cart_removed']);
	if ($removed && isset($removed->item->cart_id)) {
		$items = wholesale_mini_cart_fresh()->get_items();
		$already_there = false;
		foreach ($items as $item) {
			$already_there = $already_there || (string) $item->cart_id === (string) $removed->item->cart_id;
		}
		if (!$already_there) {
			array_splice($items, min(count($items), max(0, (int) $removed->position)), 0, array($removed->item));
			$_SESSION['cart_items'] = wp_json_encode(array_values($items));
		}
	}
	wholesale_mini_cart_send();
}
add_action('wp_ajax_wholesale_mini_cart_restore', 'wholesale_ajax_mini_cart_restore');
add_action('wp_ajax_nopriv_wholesale_mini_cart_restore', 'wholesale_ajax_mini_cart_restore');

/**
 * Add To Cart from the product page. Cart::add_item() answers with JSON errors when
 * called over AJAX and returns instead of redirecting on success.
 */
function wholesale_ajax_mini_cart_add()
{
	$before = count(wholesale_mini_cart_fresh()->get_items());
	wholesale_mini_cart_fresh()->add_item();
	$items = wholesale_mini_cart_fresh()->get_items();
	$added = count($items) > $before ? end($items) : null;
	wholesale_mini_cart_send(array(
		'added' => $added ? array(
			'cart_id' => (string) $added->cart_id,
			// get_the_title() is HTML-encoded; the script shows this as plain text.
			'title' => html_entity_decode((string) $added->product_title, ENT_QUOTES, 'UTF-8'),
		) : null,
	));
}
add_action('wp_ajax_wholesale_mini_cart_add', 'wholesale_ajax_mini_cart_add');
add_action('wp_ajax_nopriv_wholesale_mini_cart_add', 'wholesale_ajax_mini_cart_add');

/**
 * Add To Cart from the channel letter builder: saves the design (uploaded first through
 * wholesale_upload_design) as the product's session design, then adds it like the
 * product page does. The design image is the nonce-protected, session-owned part.
 */
function wholesale_ajax_cl_builder_add()
{
	$product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
	if (!$product_id || !wholesale_product_is_channel_letter($product_id) || 'publish' !== get_post_status($product_id)) {
		wp_send_json_error(array('message' => 'This product is no longer available.'));
	}
	$design = wholesale_store_session_cl_design(
		$product_id,
		isset($_POST['design_id']) ? $_POST['design_id'] : 0,
		isset($_POST['design_data']) ? $_POST['design_data'] : '' // The helper strips WordPress's slashes.
	);
	if (is_wp_error($design)) {
		wp_send_json_error(array('message' => $design->get_error_message()));
	}
	$_REQUEST['design_id'] = $design['design_id'];
	wholesale_ajax_mini_cart_add();
}
add_action('wp_ajax_wholesale_cl_builder_add', 'wholesale_ajax_cl_builder_add');
add_action('wp_ajax_nopriv_wholesale_cl_builder_add', 'wholesale_ajax_cl_builder_add');

/**
 * The cart and checkout pages show the cart themselves, so the header icon stays a link there.
 */
function wholesale_mini_cart_enabled()
{
	return !is_admin() && !is_page(array('cart', 'checkout', 'payment', 'pay')) && !get_query_var('wholesale_thank_you');
}

function wholesale_mini_cart_assets()
{
	if (!wholesale_mini_cart_enabled()) {
		return;
	}
	$dir = get_template_directory();
	$uri = get_template_directory_uri();
	wp_enqueue_style('wholesale-mini-cart', $uri . '/css/mini-cart.css', array('wholesale-header'), (string) filemtime($dir . '/css/mini-cart.css'));
	wp_enqueue_script('wholesale-mini-cart', $uri . '/js/mini-cart.js', array('custom-script'), (string) filemtime($dir . '/js/mini-cart.js'), true);
	wp_script_add_data('wholesale-mini-cart', 'strategy', 'defer');
	wp_localize_script('wholesale-mini-cart', 'wholesaleMiniCart', array(
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'cartUrl' => home_url('/cart/'),
	));
}
add_action('wp_enqueue_scripts', 'wholesale_mini_cart_assets', 20);

/**
 * Empty drawer shell; js/mini-cart.js fills it.
 */
function wholesale_mini_cart_markup()
{
	if (!wholesale_mini_cart_enabled()) {
		return;
	}
	?>
	<div class="mc-root" id="miniCart" data-mini-cart hidden>
		<div class="mc-backdrop" data-mc-close></div>
		<aside class="mc-panel" role="dialog" aria-modal="true" aria-labelledby="miniCartTitle" tabindex="-1">
			<header class="mc-head">
				<h2 class="mc-title" id="miniCartTitle">Your Cart <span class="mc-title__count" data-mc-count></span></h2>
				<button type="button" class="mc-close" data-mc-close aria-label="Close cart">
					<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
				</button>
			</header>
			<div class="mc-alert" data-mc-alert hidden></div>
			<div class="mc-body" data-mc-body aria-busy="true">
				<div class="mc-skeleton" aria-hidden="true">
					<?php for ($i = 0; $i < 2; $i++) : ?>
						<div class="mc-skeleton__line"><span class="mc-skeleton__img"></span><span class="mc-skeleton__text"><i></i><i></i><i></i></span></div>
					<?php endfor; ?>
				</div>
				<span class="screen-reader-text">Loading your cart&hellip;</span>
			</div>
			<footer class="mc-foot" data-mc-foot></footer>
			<div class="mc-toast" data-mc-toast hidden></div>
			<p class="screen-reader-text" aria-live="polite" data-mc-live></p>
		</aside>
	</div>
	<?php
}
add_action('wp_footer', 'wholesale_mini_cart_markup', 5);
