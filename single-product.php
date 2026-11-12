<?php

require_once get_template_directory() . '/utils/Cart.php';

function format_price($price)
{
    if ($price) {
        return number_format($price, 2, '.', ',');
    }
}

$short_desc = get_post_meta(get_the_ID(), '_product_short_desc', true);

$product_metarial = get_post_meta(get_the_ID(), '_product_metarial', true);
$product_print = get_post_meta(get_the_ID(), '_product_print', true);
$product_lamination = get_post_meta(get_the_ID(), '_product_lamination', true);

$product_min_height = floatVal(get_post_meta(get_the_ID(), '_min_height', true));
$product_max_height = floatVal(get_post_meta(get_the_ID(), '_max_height', true));
$product_min_width = floatVal(get_post_meta(get_the_ID(), '_min_width', true));
$product_max_width = floatVal(get_post_meta(get_the_ID(), '_max_width', true));
$product_min_sqft = get_post_meta(get_the_ID(), '_min_sqft', true); //$product_min_height * $product_min_width;
$starting_at_text = get_post_meta(get_the_ID(), "_starting_at_text", true);

$price_per_sqft = floatval(get_post_meta(get_the_ID(), '_price_per_sqft', true));

$product_min_price = round(floatval($product_min_sqft) * floatval($price_per_sqft), 2);
$product_discount_percent = (float) get_post_meta(get_the_ID(), '_discount_percent', true);
// The everyday price is the price after $product_discount_percent; only a dated sale
// (wholesale_product_promotion()) is shown as a discount.
$product_promotion = wholesale_product_promotion(get_the_ID());
$product_promo_percent = $product_promotion ? (float) $product_promotion['percent'] : 0;
$product_stock_status = wholesale_product_stock_status(get_the_ID());
// A size link (?size=36x72, from ads and Shopping) opens on that size, with its price in the HTML (inc/size-chips.php).
$size_link = wholesale_size_link(get_the_ID());

// Group buttons ("Graphic + Frame" / "Graphic Only"), inc/product-group-swap.php: the group of
// the product the URL names (?graphic_only=true shows another member on that URL).
$product_group_members = wholesale_product_group_for_page();
$product_has_group = count($product_group_members) > 1;
$product_attr_json = get_post_meta(get_the_ID(), 'product_attr', true);
$product_attr_array = json_decode((string) $product_attr_json);
if (!is_array($product_attr_array)) {
    $product_attr_array = array(); // Unreadable options: show the page without selectors instead of erroring.
}

$product_trimcap_color = get_post_meta(get_the_ID(), '_trimcap_color', true);
$is_same_return_color = get_post_meta(get_the_ID(), '_return_color', true);
$is_hide_calculator = get_post_meta(get_the_ID(), '_hide_calculator', true);
$has_upload_artwork = get_post_meta(get_the_ID(), '_has_upload_artwork', true);

$terms = get_the_terms(get_the_ID(), 'product_category');
$product_slug = get_post_field('post_name', get_the_ID(), 'raw');

$info_content_face_text = get_post_meta(get_the_ID(), '_info_content_face_text', true);
$info_content_face_image = get_post_meta(get_the_ID(), '_info_content_face_image', true);

$info_content_return_text = get_post_meta(get_the_ID(), '_info_content_return_text', true);
$info_content_return_image = get_post_meta(get_the_ID(), '_info_content_return_image', true);

$info_content_trimcap_text = get_post_meta(get_the_ID(), '_info_content_trimcap_text', true);
$info_content_trimcap_image = get_post_meta(get_the_ID(), '_info_content_trimcap_image', true);

$cl_product_cost = 0;
$product_id = get_the_ID();

$product_price = 0;


$product_category_slug = isset($terms[0]) ? $terms[0]->slug : '';

// Everyday price: after the product discount, before any dated sale.
function get_everyday_amount($totalPrice)
{
    global $product_discount_percent;
    $discountPercent = $product_discount_percent;
    return $totalPrice - (($totalPrice * $discountPercent) / 100);
}

// Price to pay: the everyday price, less the dated sale while one is running.
function get_discount_amount($totalPrice)
{
    global $product_promo_percent;
    $everyday = get_everyday_amount($totalPrice);
    return $product_promo_percent > 0 ? $everyday - (($everyday * $product_promo_percent) / 100) : $everyday;
}

// What the dated sale saves off the everyday price.
function get_saving($totalPrice)
{
    global $product_promo_percent;
    return (get_everyday_amount($totalPrice) * $product_promo_percent) / 100;
}


$design_url = null;
$design_data_array = [];
$design_data_str = '';
$design_id  = null;

if (
    isset($_REQUEST['save_design'], $_REQUEST['design_id'], $_REQUEST['design_data'])
    && Cart::is_session_design($_REQUEST['design_id'])
) {
    wholesale_store_session_cl_design($product_id, $_REQUEST['design_id'], $_REQUEST['design_data']);

    wp_redirect(get_permalink());
}

$has_cl_design = false;

if (isset($_SESSION['design_data_' . $product_id])) {
    $design_data_str = str_replace('\\', '', stripslashes($_SESSION['design_data_' . $product_id]));
    $design_data = stripslashes($_SESSION['design_data_' . $product_id]);
    $design_data_array = json_decode($design_data, true);
    // Re-price the saved design: its stored total goes stale when the price table changes.
    if (is_array($design_data_array) && function_exists('wholesale_cl_design_total')) {
        $fresh_total = wholesale_cl_design_total($design_data_array, $product_id);
        if ($fresh_total > 0) {
            $design_data_array['total_cost'] = $fresh_total;
        }
    }
    $cl_product_cost = round($design_data_array['total_cost'], 2);
    $product_price = round($design_data_array['total_cost'] ? $design_data_array['total_cost'] : 0, 2);
    $design_id = $design_data_array['design_id'];
    $design_url = wp_get_attachment_url($design_id);
    $has_cl_design = true;
}

if (isset($_REQUEST['edit_design'])) {
    wp_redirect(get_permalink() . '/channel-letter-builder/?product_id=' . get_the_ID() . '&edit_design=true');
}

if (isset($_REQUEST['delete_design'])) {
    if (isset($_SESSION['design_data_' . $product_id])) {
        $_SESSION['design_data_' . $product_id] = null;
        wp_redirect(get_permalink());
    }
}


if ($product_category_slug  == 'channel-letters') {

    $product_min_price = 0;
    $product_price =  intval($product_price) > 0 ? $product_price : '0';
}

$initial_product_cost = $product_min_price > 0 ? $product_min_price : $product_price;

$product_gallery_images = get_post_meta(get_the_ID(), '_product_gallery', true);
get_header();

?>

<?php // data-product-page: the block a product group button swaps (js/product-group-swap.js). ?>
<div class="container  product-details" data-product-page="<?php echo (int) get_the_ID(); ?>">
    <?php wholesale_breadcrumbs(); ?>
    <main id="primary">
    <article <?php post_class('product-article'); ?>>
    <?php $product_seo = wholesale_seo_channel_letter_product(get_the_ID()); ?>
    <?php
    // The H1 is the name shoppers search for ("Window Perf Vinyl"); a catalog code
    // ("AV 6 – Adhesive Window Perf", "Type 1 – ...") shows above it for repeat buyers.
    $product_heading = !empty($product_seo['heading']) ? $product_seo['heading'] : wholesale_seo_product_search_name(get_the_ID());
    // Graphic-only, flag-only and hardware-only items say so in the H1 (inc/product-kit.php).
    $product_kit_info = wholesale_product_kit_info(get_the_ID());
    if ($product_kit_info && $product_kit_info['heading']) {
        $product_heading = $product_kit_info['heading'];
    }
    $product_image_label = $product_heading;
    $product_catalog_name = wholesale_seo_product_name(get_the_ID());
    ?>
    <?php
    $product_intro = trim((string) wholesale_product_kit_partial_intro($short_desc, get_the_ID()));
    if (!$product_intro && $terms && !is_wp_error($terms)) {
        $product_intro = trim((string) $terms[0]->description);
    }
    // Channel letters show their feature list under the gallery instead of under the title.
    $product_intro_below_gallery = $product_category_slug === 'channel-letters';
    ?>
    <form action="<?php echo site_url() . '/cart'; ?>" method="POST" enctype="multipart/form-data" class="needs-validation" data-noun="<?php echo esc_attr(wholesale_product_noun($product_id)); ?>">
        <input type="hidden" name="product_id" value="<?php echo get_the_ID(); ?>">
        <?php // .pd-hero: phones stack title, photo, highlights, options; 768px+ puts the photo column beside the rest (css/ui-polish.css section 15). ?>
        <div class="row pd-hero">
            <div class="pd-hero__top">
                <?php if (!empty($product_seo['heading']) || preg_match('/^AV\s*\d+\s/u', $product_catalog_name)) : ?>
                    <p class="product-title-type"><?php echo esc_html($product_catalog_name); ?></p>
                <?php endif; ?>
                <h1 class="fs-2 product-title my-3">
                    <?php echo esc_html($product_heading); ?>
                    <?php echo wholesale_admin_edit_product_link(get_the_ID(), 'page'); // Escaped in helper. ?>
                </h1>
                <?php wholesale_render_product_proof(get_the_ID()); ?>
                <?php // On a phone the price box sits under the photo, so ad visitors would otherwise see no price on the first screen. ?>
                <?php if (trim(wp_strip_all_tags((string) $starting_at_text)) !== '') : ?>
                    <p class="product-mobile-price">
                        <?php if ($size_link) : ?>
                            <span><span class="product-price-label"><?php echo esc_html($size_link['label']); ?></span> <strong>$<?php echo esc_html(number_format($size_link['price'], 2)); ?></strong></span>
                        <?php else : ?>
                        <span><span class="product-price-label">Starting at</span> <?php echo wholesale_emphasize_price($starting_at_text); ?></span>
                        <?php endif; ?>
                        <a href="#product-options"><?php echo $product_category_slug === 'channel-letters' ? 'Get your price' : 'Choose size &amp; price'; ?> <span aria-hidden="true">&darr;</span></a>
                    </p>
                <?php endif; ?>
            </div>
            <div class="pd-hero__info">
                <?php if ($product_kit_info) : ?>
                    <p class="product-kit-note"><?php echo esc_html($product_kit_info['line']); ?></p>
                <?php endif; ?>
                <?php if (!empty($product_seo['intro'])) : ?>
                    <p class="product-seo-intro"><?php echo esc_html($product_seo['intro']); ?></p>
                <?php endif; ?>
                <?php if ($product_intro && !$product_intro_below_gallery) : ?>
                    <div class="product-intro mb-4"><?php echo wp_kses_post($product_intro); ?></div>
                <?php endif; ?>
                <?php wholesale_render_product_uses(get_the_ID()); ?>
            </div>
            <div class="col-md-4">
                <div class="thumbnail-container">
                    <?php wholesale_render_sale_badge(get_the_ID()); ?>
                    <?php
                    // The photo frame keeps the main photo's shape, so gallery photos of a
                    // different shape fit inside it (css: object-fit) instead of resizing it.
                    $main_image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
                    $main_image_ratio = ($main_image && $main_image[1] && $main_image[2]) ? sprintf(' style="aspect-ratio: %d / %d"', $main_image[1], $main_image[2]) : '';
                    ?>
                    <div class="product-image-zoom"<?php echo $main_image_ratio; ?> data-zoom-src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'full')); ?>">
                        <?php
                        the_post_thumbnail('', array(
                            'class' => 'single-product-thumbnail',
                            'alt' => wholesale_product_photo_alt(get_the_post_thumbnail_url(get_the_ID(), 'full'), $product_heading),
                            'loading' => 'eager',
                            'fetchpriority' => 'high',
                            'decoding' => 'async',
                        ));
                        ?>
                        <div class="product-image-zoom__lens" aria-hidden="true"></div>
                    </div>
                </div>
                <?php if ($product_gallery_images) : ?>
                    <div class="product-gallery-container my-3">

                        <?php
                        // Thumbnails are at most 120px wide, so they load a small size;
                        // data-image keeps the full photo for the main image swap.
                        $gallery_ids = function_exists('wholesale_product_image_alt_attachment_ids_by_url') ? wholesale_product_image_alt_attachment_ids_by_url($product_gallery_images) : array();
                        $gallery_number = 0;
                        foreach ($product_gallery_images as $image) {
                            $thumb = !empty($gallery_ids[$image]) ? wp_get_attachment_image_src($gallery_ids[$image], 'medium') : false;
                            $thumb_srcset = $thumb ? wp_get_attachment_image_srcset($gallery_ids[$image], 'medium') : '';
                            $gallery_number++;
                            // Each photo's own description (data/image-alts.php), else "Name, photo 2".
                            $thumb_alt = wholesale_product_photo_alt($image, sprintf('%s, photo %d', $product_heading, $gallery_number));
                        ?>
                            <div data-image="<?php echo $image; ?>" class="gallery-image-item">

                                <img src="<?php echo esc_url($thumb ? $thumb[0] : $image); ?>"<?php if ($thumb) : ?> width="<?php echo (int) $thumb[1]; ?>" height="<?php echo (int) $thumb[2]; ?>"<?php endif; ?><?php if ($thumb_srcset) : ?> srcset="<?php echo esc_attr($thumb_srcset); ?>" sizes="120px"<?php endif; ?> alt="<?php echo esc_attr($thumb_alt); ?>" loading="lazy" decoding="async">
                            </div>

                        <?php
                        } ?>
                    </div>

                <?php endif; ?>
                <?php if ($product_intro && $product_intro_below_gallery) : ?>
                    <div class="product-intro product-intro--below-gallery"><?php echo wp_kses_post($product_intro); ?></div>
                <?php endif; ?>
                <?php if ($product_category_slug != 'channel-letters') { ?>


                    <div class="product-price-per-sqft text-end py-2 text-secondary">
                        <!-- $<?php echo number_format($price_per_sqft, 2, '.', ','); ?> <?php if ($product_slug != 'acrylic-prints' && $product_slug != 'canvas-wrap') : ?> per ft<sup>2</sup> <?php endif; ?> -->

                        <?php if ($size_link) : ?>
                            <span class="product-price-label"><?php echo esc_html(false !== strpos($size_link['label'], 'ft') ? sprintf('%s (%s" × %s")', $size_link['label'], $size_link['width'], $size_link['height']) : $size_link['label']); ?></span>
                            <span class="product-price-value"><strong>$<?php echo esc_html(number_format($size_link['price'], 2)); ?></strong></span>
                        <?php else : ?>
                        <?php if (trim(wp_strip_all_tags((string) $starting_at_text)) !== '') : ?>
                            <span class="product-price-label">Starting at</span>
                        <?php endif; ?>
                        <span class="product-price-value"><?php echo wholesale_emphasize_price($starting_at_text); ?></span>
                        <?php endif; ?>
                        <?php
                        // Size calculator products: the smallest order the cart accepts, the
                        // configuration the structured data and Merchant feed advertise.
                        $smallest = $price_per_sqft > 0 && !$is_hide_calculator ? wholesale_seo_product_lowest_offer(get_the_ID()) : array();
                        // The price box opens on that price; other products (preset sizes, options) on
                        // the default choice's price, the same structured data and feed price, until
                        // the live quote replaces it (the Table Runner opened on $9.53, not $68.92).
                        $initial_box = $smallest ? $smallest : ('channel-letters' !== $product_category_slug ? wholesale_seo_product_lowest_offer(get_the_ID()) : array());
                        if (!empty($smallest['request']['width-ft']) && !empty($smallest['request']['height-ft'])) :
                            $inches = static function ($feet) {
                                return rtrim(rtrim(number_format($feet * 12, 1, '.', ''), '0'), '.');
                            };
                            ?>
                            <small class="d-block product-smallest-order"><?php
                                echo esc_html(sprintf('Smallest order: one %s" x %s" item, ', $inches($smallest['request']['width-ft']), $inches($smallest['request']['height-ft'])));
                                if ($smallest['regular'] > $smallest['price']) {
                                    echo '<del>$' . esc_html(number_format($smallest['regular'], 2)) . '</del> ';
                                }
                                echo '$' . esc_html(number_format($smallest['price'], 2));
                            ?></small>
                        <?php endif; ?>
                    </div>
                    <?php if ($product_kit_info) : ?>
                        <p class="product-kit-link"><a href="<?php echo esc_url(get_permalink($product_kit_info['kit_id'])); ?>"><?php echo esc_html($product_kit_info['kit_link']); ?> <span aria-hidden="true">&rarr;</span></a></p>
                    <?php elseif ($product_kit_included = wholesale_product_kit_included(get_the_ID())) : ?>
                        <div class="product-kit-included">
                            <p class="product-kit-included__title">What&rsquo;s included</p>
                            <ul>
                                <?php foreach ($product_kit_included as $kit_item) : ?>
                                    <li><?php echo esc_html($kit_item); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                <?php } elseif (trim(wp_strip_all_tags((string) $starting_at_text)) !== '') {
					// Channel letters: the computed per-inch starting rate, so visitors
                    // arriving from an ad see a price before they type their sign text.
					$cl_from = function_exists('wholesale_cl_from_inch_rate') ? wholesale_cl_from_inch_rate(get_the_ID()) : null;
                    ?>
                    <div class="product-price-per-sqft text-end py-2 text-secondary">
                        <span class="product-price-label">Starting at</span>
                        <span class="product-price-value"><?php echo wholesale_emphasize_price($starting_at_text); ?></span>
                        <?php if (!empty($cl_from['height'])) : ?>
							<small class="d-block"><?php echo esc_html(sprintf('Effective rate at %d inch letter height. Enter your design for the full price.', $cl_from['height'])); ?></small>
                        <?php endif; ?>
                    </div>
                <?php }; ?>
                <?php if ('outofstock' === $product_stock_status) : ?>
                    <p class="product-stock product-stock--out text-end">Out of stock</p>
                <?php elseif ('backorder' === $product_stock_status) : ?>
                    <p class="product-stock product-stock--backorder text-end">Backordered: ships around <?php echo esc_html((new DateTimeImmutable(wholesale_product_backorder_date(get_the_ID()), wp_timezone()))->format('F j')); ?></p>
                <?php endif; ?>
            </div>
            <div class="col-md-8" id="product-options">

                <?php if ($product_category_slug == 'channel-letters') {
                ?>
                    <div class="product-attibute-box mb-3">
                        <input type="hidden" name="design_id" value="<?php echo $design_id ? $design_id : ''; ?>">
                        <input type="hidden" name="turnaround_cost" value="0" id="turnaroundCost">
                        <input type="hidden" name="discount_percent" value="<?php echo $product_discount_percent; ?>" id="discountPercent">
                        <input type="hidden" value="<?php echo esc_attr($product_promo_percent); ?>" id="promoPercent">

                        <div class="row">
                            <div class="col">
                                <?php if ($design_data_array) {
                                ?>
                                    <input type="hidden" name="total_cost" value="<?php echo $product_min_price > 0 ? $product_min_price : $product_price; ?>" id="totalCost">

                                    <div class="cl-design-container mb-4">
                                        <img src="<?php echo $design_url; ?>" alt="" class="w-100">
                                    </div>
                                    <div class="cl-buttons-container text-center">
                                        <a href="<?php echo get_permalink() . '?edit_design=true'; ?>" class="start-from-scratch">
                                            Edit Current Design
                                        </a>
                                    </div>
                                    <div class="cl-buttons-container text-center">
                                        <a href="<?php echo get_permalink() . '?delete_design=true'; ?>" class="delete-current-design">
                                            Delete Design
                                        </a>
                                    </div>
                                <?php
                                } else {
                                ?>
                                    <div class="cl-buttons-container text-center">
                                        <a href="<?php echo home_url() . '/channel-letter-builder/?product_id=' . get_the_ID(); ?>" class="start-from-scratch">
                                            <span>Start From Scratch</span>
                                            <small>Optional if you don’t have a file</small>
                                        </a>
                                    </div>
                                <?php } ?>



                            </div>
                        </div>

                    </div>
                    <?php if (!$has_cl_design) { ?>
                        <div class="product-attibute-box mb-3">
                            <input type="hidden" value="<?php echo $product_trimcap_color; ?>" name="default_trimcap_color" id="trimcapColor">
                            <input type="hidden" value="<?php echo $is_same_return_color; ?>" name="is_same_return_color" id="returnColorSame">
                            <input type="hidden" name="total_cost" value="<?php echo $product_min_price > 0 ? $product_min_price : $product_price; ?>" id="totalCost">
                            <div class="row">
                                <div class="col mobile-s-0">
                                    <div id="letter-output" class=" text-center fs-1 py-3">
                                        Enter Your Text
                                    </div>

                                </div>
                            </div>
                            <div class="row my-3">
                                <div class="col">
                                    <div class="form-group">

                                    </div>
                                    <input type="text" id="letterInput" name="letters" required placeholder="Enter Your Text Here" class="form-control fw-bold">
                                    <p class="form-text m-0">For an instant quote - under 30 seconds</p>
                                </div>
                            </div>
                            <div class="row d-fex align-items-center  mt-2">
                                <div class="col-md-3 col-4">
                                    <label for="select-font" class="text-capitalize"> font</label>
                                </div>
                                <div class="col-8 col-md-9">
                                    <div class="row">
                                        <div class="col">
                                            <select id="select-font" data-ccost="0" name="font" class="form-select dynamic-select avoid-price">
                                                <option value="Arial">Arial</option>

                                                <option value="Arial Black">Arial Black </option>
                                                <option value="Arial bold" data-fw="900">Arial Bold</option>
                                                <option value="gotham-medium">Gotham Medium</option>
                                                <option value="helvetica">Helvetica</option>
                                                <option value="helvetica-condensed-bold">Helvetica Condensed Bold</option>
                                                <option value="helvetica-rounded-bold">Helvetica Rounded Bold</option>
                                                <option value="anton">Anton</option>
                                            </select>
                                            <div class="form-text">Cursive & complex fonts must send email to request quote.</div>

                                        </div>
                                    </div>

                                </div>
                            </div>
                            <!-- <div class="row d-fex align-items-center  mt-2">
                                <div class="col-md-3 col-4">
                                    <label for="artwork" class="text-capitalize">Upload Artwork</label>

                                </div>
                                <div class="col-8 col-md-9">
                                    <div class="row">
                                        <div class="col">
                                            <input class="form-control" name="custom-artwork" type="file" id="artwork">

                                        </div>
                                    </div>

                                </div>
                            </div> -->
                            <?php
                            if ($product_attr_array == true) {
                                foreach ($product_attr_array as $single_attr) {
                                    $attr_name = $single_attr->name;

                                    if ($attr_name == 'face' || $attr_name == 'trimcap' || $attr_name == 'return') {
                            ?>


                                        <div class="row d-fex align-items-center  mt-2">
                                            <div class="col-md-3 col-4">
                                                <label for="select-sample-color" class="text-capitalize"> <?php echo $attr_name; ?> Color</label>
                                                <?php if ($attr_name == 'face') { ?>
                                                    <span class="option-info-container">
                                                        <span class="option-info-button">i</span>
                                                        <div class="option-info-content">
                                                            <p class="option-info-text">
                                                                <?php echo $info_content_face_text; ?>
                                                            </p>
                                                            <div class="option-info-img-container">
                                                                <img src="<?php echo $info_content_face_image; ?>" alt="Channel letter face" class="option-info-img">
                                                            </div>
                                                        </div>
                                                    </span>
                                                <?php }; ?>

                                                <?php if ($attr_name == 'trimcap') { ?>
                                                    <span class="option-info-container">
                                                        <span class="option-info-button">i</span>
                                                        <div class="option-info-content">
                                                            <p class="option-info-text">
                                                                <?php echo $info_content_trimcap_text; ?>
                                                            </p>
                                                            <div class="option-info-img-container">
                                                                <img src="<?php echo $info_content_trimcap_image; ?>" alt="Channel letter trimcap" class="option-info-img">
                                                            </div>
                                                        </div>
                                                    </span>
                                                <?php }; ?>

                                                <?php if ($attr_name == 'return') { ?>
                                                    <span class="option-info-container">
                                                        <span class="option-info-button">i</span>
                                                        <div class="option-info-content">
                                                            <p class="option-info-text">
                                                                <?php echo $info_content_return_text; ?>
                                                            </p>
                                                            <div class="option-info-img-container">
                                                                <img src="<?php echo $info_content_return_image; ?>" alt="Channel letter return" class="option-info-img">
                                                            </div>
                                                        </div>
                                                    </span>
                                                <?php }; ?>
                                            </div>
                                            <div class="col-8 col-md-9">
                                                <div class="row">
                                                    <div class="col">

                                                        <?php if (count($single_attr->options) !== 1) { ?>

                                                            <div id="custom-select-<?php echo $attr_name; ?>" data-type="<?php echo esc_attr(isset($single_attr->type) ? $single_attr->type : ''); ?>" class="custom-select-container <?php echo $single_attr->cssClass; ?>">
                                                                <span class="color-sample"></span>
                                                                <span class="custom-select-selected">
                                                                    Option two
                                                                </span>
                                                                <ul class="custom-select">
                                                                    <?php foreach ($single_attr->options as $option) {
                                                                        foreach ($option as $name => $price) {

                                                                            $bgColor = '';
                                                                            $bgImg = '';
                                                                            if (array_key_exists('2', explode('/', $price))) {
                                                                                $bgColor = explode('/', $price)[2];
                                                                                if (explode('/', $price)[2] == 'dual-color-white') {
                                                                                    $bgImg = get_template_directory_uri() . '/img/dual-color-white.jpeg';
                                                                                } elseif (explode('/', $price)[2] == 'dual-color-black') {
                                                                                    $bgImg = get_template_directory_uri() . '/img/dual-color-black.jpeg';
                                                                                }
                                                                            } else {
                                                                                $bgColor = $price;
                                                                            }
                                                                    ?>

                                                                            <li data-value="<?php echo esc_attr($price); ?>" data-text="opt one<?php echo esc_attr($name); ?>">
                                                                                <span class="color-sample" style="background-image: url(<?php echo $bgImg; ?>); background-color: <?php echo $bgColor; ?>"></span>
                                                                                <span><?php echo esc_html($name); ?></span>
                                                                            </li>

                                                                    <?php  }
                                                                    }; ?>
                                                                </ul>
                                                            </div>
                                                        <?php } else {
                                                        ?>

                                                            <?php foreach ($single_attr->options as $option) {
                                                                foreach ($option as $name => $price) {
                                                            ?>
                                                                    <input type="hidden" name="<?php echo str_replace(' ', '', $attr_name); ?>" value="<?php echo esc_attr($name); ?>">
                                                                    <span data-value="<?php echo esc_attr($price); ?>" class="<?php echo $attr_name  . '-option-name'; ?>"><?php echo esc_html($name); ?></span>
                                                            <?php  }
                                                            }; ?>
                                                        <?php
                                                        } ?>


                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    <?php
                                    }

                                    ?>

                                    <div class="row d-fex align-items-center select-row mt-2">
                                        <div class="col-md-3 col-4">
                                            <label for="select-<?php echo $attr_name; ?>" class="text-capitalize"><?php echo $attr_name == 'height' ? 'Size - ' : ''; ?><?php echo esc_html(wholesale_attr_label($attr_name)); ?></label><?php $attr_help = wholesale_attr_help($attr_name, isset($single_attr->options) ? $single_attr->options : array()); if ($attr_help) : ?><small class="pb-attr-help" id="help-<?php echo esc_attr($attr_name); ?>"><?php echo esc_html($attr_help); ?></small><?php endif; ?>
                                        </div>
                                        <div class="col-8 col-md-9">
                                            <div class="row">
                                                <div class="col">
                                                    <?php if (count($single_attr->options) !== 1) { ?>
                                                        <select data-type="<?php echo esc_attr(isset($single_attr->type) ? $single_attr->type : ''); ?>" name="<?php echo $attr_name; ?>" id="select-<?php echo $attr_name; ?>" data-cCost="0" class="form-select dynamic-select <?php echo $attr_name  != 'height' ? 'dynamic-price' : ''; ?> <?php echo $single_attr->cssClass; ?>">
                                                            <?php foreach ($single_attr->options as $option) {
                                                                foreach ($option as $name => $price) {
                                                            ?>
                                                                    <option <?php if ($attr_name  == 'height') { ?> data-size="<?php echo intval($name); ?>" <?php }; ?> value="<?php echo esc_attr($price); /* Escaped: B2Sign sizes such as 0/33" x 81" hold inch marks, which cut a raw value short and broke the live price. */ ?>"><?php echo 'height' == $attr_name && intval($name) > 0 ? esc_html(intval($name) . ' Inch') : esc_html($name); /* Heights always read "24 Inch", whatever the stored label's spacing or case. */ ?></option>
                                                            <?php  }
                                                            }; ?>
                                                        </select>

                                                    <?php } else {
                                                    ?>

                                                        <?php foreach ($single_attr->options as $option) {
                                                            foreach ($option as $name => $price) {
                                                        ?>
                                                                <input type="hidden" id="select-<?php echo str_replace('-', ' ', $attr_name); ?>" name="<?php echo str_replace(' ', '-', $attr_name); ?>" value="<?php echo esc_attr($name); ?>">
                                                                <span data-value="<?php echo esc_attr($price); ?>" class="<?php echo $attr_name  . '-option-name'; ?>"><?php echo esc_html($name); ?></span>
                                                        <?php  }
                                                        }; ?>
                                                    <?php
                                                    }
                                                    if ($attr_name == 'height') { ?>
                                                        <span id="hiddenText" style="visibility: hidden; position: absolute; white-space: nowrap; font-size: 16px; font-family: Arial;"></span>
                                                        <span id="widthDisplay" class="form-text">
                                                            Approximate Width: <b>0 Inch</b>
                                                        </span>
                                                    <?Php }

                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                            <?php
                                }
                            }; ?>

                        </div>
                    <?php } else {
                    ?>

                    <?php
                    }; ?>
                <?php }

                if ($product_category_slug != 'channel-letters'): ?>

                    <div class="product-attibute-box mb-3">
                        <input type="hidden" name="total_cost" value="<?php echo $product_min_price > 0 ? $product_min_price : $product_price; ?>" id="totalCost">
                        <input type="hidden" name="discount_percent" value="<?php echo $product_discount_percent; ?>" id="discountPercent">
                        <input type="hidden" value="<?php echo esc_attr($product_promo_percent); ?>" id="promoPercent">

                        <input type="hidden" name="min_sqft" value="<?php echo $product_min_sqft; ?>" id="minSqft">
                        <input type="hidden" name="total_sqft" value="0" id="totalSqft">
                        <input type="hidden" name="turnaround_cost" value="0" id="turnaroundCost">

                        <!-- Product Gruop  -->

                        <?php

                        if ($product_has_group) {
                        ?>


                            <div class="row d-fex align-items-center">

                                <div class="product-group-container" role="group" aria-label="Product options">

                                    <?php
                                    // Kit / partial toggles (Stand + Graphic vs Graphic only) get labels and prices.
                                    $group_is_kit_toggle = (bool) wholesale_product_kit_info($product_id);
                                    foreach ($product_group_members as $group_member) {
                                        if (wholesale_product_kit_info($group_member['id'])) {
                                            $group_is_kit_toggle = true;
                                        }
                                    }

                                    // A button swaps that product into this page without reloading
                                    // (js/product-group-swap.js). Its link is the group's first product,
                                    // with ?graphic_only=true etc. for the others; opened directly, that
                                    // link shows the same product (inc/product-group-swap.php).
                                    // A duplicate page (e.g. straight-velcro-...-graphic-frame-only) marks the product it copies.
                                    $group_current_id = function_exists('wholesale_seo_product_duplicate_of') && !in_array((int) $product_id, wp_list_pluck($product_group_members, 'id'), true)
                                        ? (int) wholesale_seo_product_duplicate_of($product_id)
                                        : 0;
                                    foreach ($product_group_members as $group_member) {
                                        $group_product_id = $group_member['id'];
                                        $is_current_product = $group_product_id == $product_id || $group_product_id == $group_current_id ? 'current' : '';
                                        // Each option shows what it is and its starting price; the complete kit is the primary option.
                                        $group_option = $group_is_kit_toggle
                                            ? wholesale_product_kit_toggle_option($group_product_id, $group_member['title'])
                                            : array('label' => wp_strip_all_tags((string) $group_member['title']), 'price' => 0, 'is_kit' => false);
                                    ?>

                                        <div class="product-group-item <?php echo $is_current_product; ?><?php echo $group_option['is_kit'] ? ' product-group-item--kit' : ''; ?>">
                                            <a href="<?php echo esc_url($group_member['url']); ?>" class="product-group-item-link" data-group-key="<?php echo esc_attr($group_member['key']); ?>" data-product-id="<?php echo (int) $group_product_id; ?>" data-fetch-url="<?php echo esc_url(wholesale_product_group_fetch_url($group_member, $product_group_members)); ?>"<?php echo $is_current_product ? ' aria-current="page"' : ''; ?>><?php echo esc_html($group_option['label']); ?><?php if ($group_option['price'] > 0) : ?> <span class="product-group-item-price">$<?php echo esc_html(number_format($group_option['price'], 2)); ?></span><?php endif; ?></a>
                                        </div>


                                    <?php
                                    } ?>

                                </div>
                            </div>

                        <?php



                        }



                        ?>





                        <?php if ($product_category_slug != 'channel-letters' && $is_hide_calculator != 'on') { ?>
                            <input type="hidden" name="price_per_sqft" value="<?php echo $price_per_sqft; ?>" id="pricePerSqft">

                            <?php
                            $min_area = wholesale_product_min_area(get_the_ID());
                            // Every size calculator product: B2Sign-style size box. Width then height, in inches.
                            // Products with preset sizes (_size_presets) get B2Sign's Size selector instead: preset
                            // sizes with a grey note, then "More Sizes > Custom Sizes", which shows the boxes
                            // (inc/size-chips.php wholesale_size_presets(), js/main.js).
                                $size_presets = wholesale_size_presets(get_the_ID());
                                $rate_shown = $price_per_sqft * (100 - min(100, max(0, $product_discount_percent))) / 100;
                                $min_charge = wholesale_product_min_order(get_the_ID());
                                // Like B2Sign, the size box shows what the customer pays (after any
                                // dated sale), and the page opens on the minimum charge.
                                $rate_pay = wholesale_promotion_price(get_the_ID(), round($rate_shown, 2));
                                $min_charge_pay = wholesale_promotion_price(get_the_ID(), $min_charge);
                                // The B2Sign Sync margin floor can make the smallest order cost more than
                                // _min_order_price ($28 vs $14 on banners): show what the smallest order costs.
                                if (!empty($smallest['price']) && $smallest['price'] > $min_charge_pay) {
                                    $min_charge_pay = (float) $smallest['price'];
                                    $min_charge = max($min_charge, (float) (isset($smallest['regular']) ? $smallest['regular'] : $smallest['price']));
                                }
                                $min_sides = wholesale_product_min_sides(get_the_ID());
                                $max_sides = wholesale_product_max_sides(get_the_ID());
                                // The boxes open on the largest size that still costs the minimum charge.
                                $default_size = wholesale_product_default_size(get_the_ID()) ?: array(0, 0);
                                // Feet-and-inches boxes open on whole feet (17" -> 1 ft 0 in, still the minimum
                                // charge): with "1 ft 5 in" a visitor who typed 5 in the feet box got 5 ft 5 in,
                                // or 5" after clearing it (Visitor Insights, Nov 8 2026).
                                if ('ftin' === get_post_meta(get_the_ID(), '_size_unit', true)) {
                                    $default_size = array_map(static function ($side) {
                                        return $side >= 12 ? floor($side / 12) * 12 : $side;
                                    }, $default_size);
                                }
                                $default_sizes = array('width' => $default_size[0] + 0, 'height' => $default_size[1] + 0);
                                if ($size_link) {
                                    $default_sizes = array('width' => $size_link['width'], 'height' => $size_link['height']);
                                }
                                // The Size selector opens on a preset (the boxes then hold its size), or on Custom Sizes.
                                $size_preset_on = wholesale_size_preset_default($size_presets, $default_sizes, (bool) $size_link);
                                if (null !== $size_preset_on) {
                                    $default_sizes = array('width' => $size_presets[$size_preset_on]['width'], 'height' => $size_presets[$size_preset_on]['height']);
                                }
                                $size_chips = $size_presets ? array() : wholesale_size_chips(get_the_ID());
                            ?>
                                <div class="row d-fex align-items-center mt-2 dimenstion-calculator dim-row pb-size-b2<?php echo null !== $size_preset_on ? ' is-preset' : ''; ?>">
                                    <div class="col-md-3 col-4">
                                        <span class="dim-label" id="dim-label-size">Size</span>
                                    </div>
                                    <div class="col-8 col-md-9">
                                        <?php if ($size_chips) : // One tap fills width and height (js/ui-polish.js). ?>
                                            <div class="pb-size-chips" role="group" aria-label="Popular sizes">
                                                <?php foreach ($size_chips as $chip) :
                                                    $chip_on = $chip['width'] == $default_sizes['width'] && $chip['height'] == $default_sizes['height']; ?>
                                                    <button type="button" class="pb-size-chip<?php echo $chip_on ? ' is-selected' : ''; ?>" aria-pressed="<?php echo $chip_on ? 'true' : 'false'; ?>" data-width="<?php echo esc_attr($chip['width']); ?>" data-height="<?php echo esc_attr($chip['height']); ?>"><span class="pb-size-chip__size"><?php echo esc_html($chip['label']); ?></span> <span class="pb-size-chip__price">$<?php echo esc_html(number_format($chip['price'], 2)); ?></span></button>
                                                <?php endforeach; ?>
                                                <button type="button" class="pb-size-chip pb-size-chip--custom" data-custom="1"><span class="pb-size-chip__size">Custom size</span></button>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($size_presets) :
                                            $custom_size_note = trim(wp_strip_all_tags((string) get_post_meta(get_the_ID(), '_custom_size_note', true)));
                                            // One row's text; the button shows the chosen row's (js/main.js copies it).
                                            $size_row = function ($size, $wh, $note) { ?><span class="pb-size-opt__size"><?php echo esc_html($size); ?></span><?php if ($wh) : ?><span class="pb-size-opt__wh">(w x h)</span><?php endif; ?><?php if ('' !== $note) : ?><span class="pb-size-opt__note"><?php echo esc_html($note); ?></span><?php endif; ?><?php };
                                            $size_on = null !== $size_preset_on ? $size_presets[$size_preset_on] : null; ?>
                                            <div class="pb-size-select">
                                                <button type="button" class="form-select pb-size-select__button" id="pbSizeSelect" aria-haspopup="listbox" aria-expanded="false" aria-controls="pbSizeList" aria-labelledby="dim-label-size pbSizeSelect">
                                                    <span class="pb-size-select__value"><?php $size_on ? $size_row(sprintf('%s" x %s"', $size_on['width'], $size_on['height']), true, $size_on['note']) : $size_row('Custom Sizes', false, $custom_size_note); ?></span>
                                                </button>
                                                <ul class="pb-size-select__list" id="pbSizeList" role="listbox" aria-labelledby="dim-label-size" tabindex="-1" hidden>
                                                    <?php foreach ($size_presets as $i => $preset) : ?>
                                                        <li role="option" id="pbSizeOpt<?php echo (int) $i; ?>" class="pb-size-opt" aria-selected="<?php echo $i === $size_preset_on ? 'true' : 'false'; ?>" data-width="<?php echo esc_attr($preset['width']); ?>" data-height="<?php echo esc_attr($preset['height']); ?>"><?php $size_row(sprintf('%s" x %s"', $preset['width'], $preset['height']), true, $preset['note']); ?></li>
                                                    <?php endforeach; ?>
                                                    <li role="presentation" class="pb-size-select__group">More Sizes</li>
                                                    <li role="option" id="pbSizeOptCustom" class="pb-size-opt pb-size-opt--custom" aria-selected="<?php echo null === $size_preset_on ? 'true' : 'false'; ?>" data-custom="1"><?php $size_row('Custom Sizes', false, $custom_size_note); ?></li>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                        <?php
                                        // Size box units, like B2Sign's page for this product (meta _size_unit, set by SFS B2Sign Sync
                                        // or by hand): inches (rigid boards), or "ftin" = feet and inches boxes (banners, flags, vinyl).
                                        // With feet and inches, the visible boxes feed a hidden width-in / height-in (total inches),
                                        // which the price, the limits and the cart read as before (js/main.js).
                                        $size_unit = 'ftin' === get_post_meta(get_the_ID(), '_size_unit', true) ? 'ftin' : 'in';
                                        ?>
                                        <div class="dim-fields<?php echo 'ftin' === $size_unit ? ' dim-fields--ftin' : ''; ?>" role="group" aria-labelledby="dim-label-size">
                                            <?php foreach (array('width' => 'Width', 'height' => 'Height') as $dim => $dim_label) : ?>
                                                <?php if ('ftin' === $size_unit) :
                                                    $total_in = (float) $default_sizes[$dim];
                                                    $part_ft = $total_in > 0 ? floor($total_in / 12) : '';
                                                    $part_in = $total_in > 0 ? round($total_in - floor($total_in / 12) * 12, 2) + 0 : ''; ?>
                                                    <div class="input-group dim-group dim-group--ftin">
                                                        <span class="input-group-text" aria-hidden="true"><?php echo esc_html($dim_label); ?></span>
                                                        <input type="number" inputmode="numeric" step="1" min="0" name="<?php echo esc_attr($dim); ?>-ft-part" value="<?php echo esc_attr($part_ft); ?>" class="form-control dim-part" data-part="ft" aria-label="<?php echo esc_attr($dim_label . ' in feet'); ?>">
                                                        <span class="input-group-text" aria-hidden="true">ft</span>
                                                        <input type="number" inputmode="decimal" step="any" min="0" name="<?php echo esc_attr($dim); ?>-in-part" value="<?php echo esc_attr($part_in); ?>" class="form-control dim-part" data-part="in" aria-label="<?php echo esc_attr($dim_label . ', extra inches'); ?>">
                                                        <span class="input-group-text" aria-hidden="true">in</span>
                                                        <input type="hidden" name="<?php echo esc_attr($dim); ?>-in" id="input-<?php echo esc_attr($dim); ?>-in" value="<?php echo esc_attr($default_sizes[$dim]); ?>" class="dim-input">
                                                    </div>
                                                <?php else : ?>
                                                <div class="input-group dim-group">
                                                    <span class="input-group-text" aria-hidden="true"><?php echo esc_html($dim_label); ?></span>
                                                    <input type="number" inputmode="decimal" step="any" min="0" name="<?php echo esc_attr($dim); ?>-in" id="input-<?php echo esc_attr($dim); ?>-in" value="<?php echo esc_attr($default_sizes[$dim]); ?>" class="form-control dim-input" aria-label="<?php echo esc_attr($dim_label . ' in inches'); ?>">
                                                    <span class="input-group-text" aria-hidden="true">inch</span>
                                                </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php // data-quote-url: the "Need a bigger size?" link js/main.js adds under a too-big size. ?>
                                        <small class="pb-dim-error" role="alert" data-quote-url="<?php echo esc_url(function_exists('wholesale_quote_url') ? wholesale_quote_url('', get_post_field('post_name', get_the_ID())) : home_url('/contact/')); ?>" hidden></small>
                                    </div>
                                </div>

                                <div class="row mt-2 mb-3">
                                    <div class="col-8 col-md-9 offset-4 offset-md-3">
                                        <?php // Size limits stay hidden until a typed size breaks one (B2Sign style); js/main.js. ?>
                                        <?php if ($max_sides) : ?>
                                            <small class="pb-size-limit pb-max-size" hidden>Max Size: <?php echo esc_html(sprintf('%2$s" x %1$s" or %1$s" x %2$s"', $max_sides[0] + 0, $max_sides[1] + 0)); ?></small>
                                        <?php endif; ?>
                                        <?php if ($min_sides && $min_sides[0] + $min_sides[1] > 0) : ?>
                                            <small class="pb-size-limit pb-min-size" hidden>Min Size: <?php echo esc_html(sprintf('%s" x %s"', $min_sides[0] + 0, $min_sides[1] + 0)); ?></small>
                                        <?php endif; ?>
                                        <div class="pb-size-summary" data-min-width="<?php echo esc_attr($min_sides ? $min_sides[0] : 0); ?>" data-min-height="<?php echo esc_attr($min_sides ? $min_sides[1] : 0); ?>" data-max-short="<?php echo esc_attr($max_sides ? $max_sides[0] : 0); ?>" data-max-long="<?php echo esc_attr($max_sides ? $max_sides[1] : 0); ?>" data-min-everyday="<?php echo esc_attr(round($min_charge, 2)); ?>" data-min-total="<?php echo esc_attr(round($min_charge_pay, 2)); ?>">
                                            <span class="total-size-sqft" data-order="wh" aria-live="polite"><?php echo esc_html(sprintf('%s" x %s" = %s', $default_sizes['width'], $default_sizes['height'], round($default_sizes['width'] * $default_sizes['height'] / 144, 2) + 0)); ?> ft<sup>2</sup></span>
                                            <small class="d-block">$<?php echo esc_html(number_format($rate_pay, 2)); ?>/ft<sup>2</sup><?php if ($min_charge > 0) : ?><span class="pb-min-charge"> <span aria-hidden="true">|</span> min charge $<?php echo esc_html(number_format($min_charge_pay, 2)); ?></span><?php endif; ?></small>
                                        </div>
                                    </div>
                                </div>




                        <?php } else {
                        ?>
                            <input type="hidden" name="price_per_sqft" value="<?php echo $price_per_sqft; ?>" id="pricePerSqft">
                        <?php
                        };

                        if ($product_category_slug == 'channel-letters') {
                        ?>
                            <input type="hidden" value="<?php echo $product_trimcap_color; ?>" name="default_trimcap_color" id="trimcapColor">
                            <input type="hidden" value="<?php echo $is_same_return_color; ?>" name="is_same_return_color" id="returnColorSame">

                        <?php }; ?>

                        <?php
                        if ($product_category_slug != 'channel-letters') {
                            foreach ($product_attr_array as $single_attr) {
                                $attr_name = $single_attr->name;
                                // The width/height calculator above replaces a preset size dropdown.
                                if ($is_hide_calculator != 'on' && wholesale_attr_is_preset_size((array) $single_attr)) {
                                    continue;
                                }

                                if ($attr_name == 'face' || $attr_name == 'trimcap' || $attr_name == 'return') {
                        ?>


                                    <div class="row d-fex align-items-center  mt-2">
                                        <div class="col-md-3 col-4">
                                            <label for="select-sample-color" class="text-capitalize"> <?php echo $attr_name; ?> Color</label>
                                            <?php if ($attr_name == 'face') { ?>
                                                <span class="option-info-container">
                                                    <span class="option-info-button">i</span>
                                                    <div class="option-info-content">
                                                        <p class="option-info-text">
                                                            <?php echo $info_content_face_text; ?>
                                                        </p>
                                                        <div class="option-info-img-container">
                                                            <img src="<?php echo $info_content_face_image; ?>" alt="Channel letter face" class="option-info-img">
                                                        </div>
                                                    </div>
                                                </span>
                                            <?php }; ?>

                                            <?php if ($attr_name == 'trimcap') { ?>
                                                <span class="option-info-container">
                                                    <span class="option-info-button">i</span>
                                                    <div class="option-info-content">
                                                        <p class="option-info-text">
                                                            <?php echo $info_content_trimcap_text; ?>
                                                        </p>
                                                        <div class="option-info-img-container">
                                                            <img src="<?php echo $info_content_trimcap_image; ?>" alt="Channel letter trimcap" class="option-info-img">
                                                        </div>
                                                    </div>
                                                </span>
                                            <?php }; ?>

                                            <?php if ($attr_name == 'return') { ?>
                                                <span class="option-info-container">
                                                    <span class="option-info-button">i</span>
                                                    <div class="option-info-content">
                                                        <p class="option-info-text">
                                                            <?php echo $info_content_return_text; ?>
                                                        </p>
                                                        <div class="option-info-img-container">
                                                            <img src="<?php echo $info_content_return_image; ?>" alt="Channel letter return" class="option-info-img">
                                                        </div>
                                                    </div>
                                                </span>
                                            <?php }; ?>
                                        </div>
                                        <div class="col-8 col-md-9">
                                            <div class="row">
                                                <div class="col">

                                                    <?php if (count($single_attr->options) !== 1) { ?>

                                                        <div id="custom-select-<?php echo $attr_name; ?>" data-type="<?php echo esc_attr(isset($single_attr->type) ? $single_attr->type : ''); ?>" class="custom-select-container <?php echo $single_attr->cssClass; ?>">
                                                            <span class="color-sample"></span>
                                                            <span class="custom-select-selected">
                                                                Option two
                                                            </span>
                                                            <ul class="custom-select">
                                                                <?php foreach ($single_attr->options as $option) {
                                                                    foreach ($option as $name => $price) {

                                                                        $bgColor = '';
                                                                        $bgImg = '';
                                                                        if (array_key_exists('2', explode('/', $price))) {
                                                                            $bgColor = explode('/', $price)[2];
                                                                            if (explode('/', $price)[2] == 'dual-color-white') {
                                                                                $bgImg = get_template_directory_uri() . '/img/dual-color-white.jpeg';
                                                                            } elseif (explode('/', $price)[2] == 'dual-color-black') {
                                                                                $bgImg = get_template_directory_uri() . '/img/dual-color-black.jpeg';
                                                                            }
                                                                        } else {
                                                                            $bgColor = $price;
                                                                        }
                                                                ?>

                                                                        <li data-value="<?php echo esc_attr($price); ?>" data-text="opt one<?php echo esc_attr($name); ?>">
                                                                            <span class="color-sample" style="background-image: url(<?php echo $bgImg; ?>); background-color: <?php echo $bgColor; ?>"></span>
                                                                            <span><?php echo esc_html($name); ?></span>
                                                                        </li>

                                                                <?php  }
                                                                }; ?>
                                                            </ul>
                                                        </div>
                                                    <?php } else {
                                                    ?>

                                                        <?php foreach ($single_attr->options as $option) {
                                                            foreach ($option as $name => $price) {
                                                        ?>
                                                                <input type="hidden" name="<?php echo strtolower(str_replace(' ', '', $attr_name)); ?>" value="<?php echo esc_attr($name); ?>">
                                                                <span data-value="<?php echo esc_attr($price); ?>" class="<?php echo $attr_name  . '-option-name'; ?>"><?php echo esc_html($name); ?></span>
                                                        <?php  }
                                                        }; ?>
                                                    <?php
                                                    } ?>


                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                <?php
                                }

                                ?>

                                <div class="row d-fex align-items-center select-row mt-2">
                                    <div class="col-md-3 col-4">
                                        <label for="select-<?php echo $attr_name; ?>" class="text-capitalize"><?php echo $attr_name == 'height' ? 'Size - ' : ''; ?><?php echo esc_html(wholesale_attr_label($attr_name)); ?></label><?php $attr_help = wholesale_attr_help($attr_name, isset($single_attr->options) ? $single_attr->options : array()); if ($attr_help) : ?><small class="pb-attr-help" id="help-<?php echo esc_attr($attr_name); ?>"><?php echo esc_html($attr_help); ?></small><?php endif; ?>
                                    </div>
                                    <div class="col-8 col-md-9">
                                        <div class="row">
                                            <div class="col">
                                                <?php if (count($single_attr->options) !== 1) { ?>
                                                    <select data-type="<?php echo $single_attr->heading ? $single_attr->heading : ''; ?>" name="<?php echo strtolower($attr_name); ?>" id="select-<?php echo $attr_name; ?>" data-cCost="0" class="form-select dynamic-select <?php echo $attr_name  != 'height' ? 'dynamic-price' : ''; ?> <?php echo $single_attr->cssClass; ?>">
                                                        <?php foreach ($single_attr->options as $option) {
                                                            foreach ($option as $name => $price) {
                                                        ?>
                                                                <option <?php if ($attr_name  == 'height') { ?> data-size="<?php echo intval($name); ?>" <?php }; ?> value="<?php echo esc_attr($price); /* Escaped: B2Sign sizes such as 0/33" x 81" hold inch marks, which cut a raw value short and broke the live price. */ ?>"><?php echo 'height' == $attr_name && intval($name) > 0 ? esc_html(intval($name) . ' Inch') : esc_html($name); /* Heights always read "24 Inch", whatever the stored label's spacing or case. */ ?></option>
                                                        <?php  }
                                                        }; ?>
                                                    </select>
                                                    <?php // Banner stands: on phones the sizes are tap chips with their prices; the select above stays in the form (js/ui-polish.js).
                                                    $option_chips = wholesale_size_option_chips(get_the_ID(), $attr_name);
                                                    if ($option_chips) : ?>
                                                        <div class="pb-option-chips" role="group" aria-label="<?php echo esc_attr(ucfirst($attr_name)); ?>" data-for="select-<?php echo esc_attr($attr_name); ?>">
                                                            <?php foreach ($option_chips as $chip_index => $chip) : ?>
                                                                <button type="button" class="pb-size-chip<?php echo 0 === $chip_index ? ' is-selected' : ''; ?>" aria-pressed="<?php echo 0 === $chip_index ? 'true' : 'false'; ?>" data-value="<?php echo esc_attr($chip['value']); ?>"><span class="pb-size-chip__size"><?php echo esc_html($chip['label']); ?></span> <span class="pb-size-chip__price">$<?php echo esc_html(number_format($chip['price'], 2)); ?></span></button>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                <?php } else {
                                                ?>

                                                    <?php foreach ($single_attr->options as $option) {
                                                        foreach ($option as $name => $price) {
                                                    ?>
                                                            <input type="hidden" id="select-<?php echo str_replace('-', ' ', $attr_name); ?>" name="<?php echo strtolower(str_replace(' ', '-', $attr_name)); ?>" value="<?php echo esc_attr($name); ?>">
                                                            <span data-value="<?php echo esc_attr($price); ?>" class="<?php echo $attr_name  . '-option-name'; ?>"><?php echo esc_html($name); ?></span>
                                                    <?php  }
                                                    }; ?>
                                                <?php
                                                } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                // B2Sign-style custom hole punch: shown when "Custom Hole Punch" or "Custom Grommets" is picked
                                // (js/main.js); Cart.php records the layout. Disabled while hidden.
                                $has_custom_holes = 'holes-punch' === $attr_name && array_filter($single_attr->options, static function ($option) {
                                    return (bool) preg_match('/^Custom (Hole|Grommet)/i', (string) key((array) $option));
                                });
                                if ($has_custom_holes) : ?>
                                    <div class="row mt-2 pb-custom-holes" data-for="select-<?php echo esc_attr($attr_name); ?>" hidden>
                                        <div class="col-8 col-md-9 offset-4 offset-md-3">
                                            <?php foreach (array('top' => 'Top', 'bottom' => 'Bottom') as $edge => $edge_label) : ?>
                                                <fieldset class="pb-custom-holes-edge" disabled>
                                                    <legend><?php echo esc_html($edge_label); ?></legend>
                                                    <?php if ('bottom' === $edge) : ?>
                                                        <label><input type="radio" name="custom-holes-bottom" value="same"> Same as top</label>
                                                    <?php endif; ?>
                                                    <label><input type="radio" name="custom-holes-<?php echo esc_attr($edge); ?>" value="corner"> Corner holes &ndash; edge margin</label>
                                                    <label class="pb-custom-holes-margin"><input type="number" name="custom-holes-<?php echo esc_attr($edge); ?>-margin" value="1" min="0.25" step="0.25" inputmode="decimal" aria-label="<?php echo esc_attr($edge_label . ' edge margin in inches'); ?>"> inches</label>
                                                    <label><input type="radio" name="custom-holes-<?php echo esc_attr($edge); ?>" value="none" checked> No corner holes</label>
                                                    <label><input type="checkbox" name="custom-holes-<?php echo esc_attr($edge); ?>-center" value="1"> Center hole</label>
                                                </fieldset>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                        <?php
                            }
                        }; ?>



                    </div>

                <?php endif; ?>

                <div class="product-attibute-box mb-3">

                    <div class="row d-fex align-items-center  mt-2">
                        <div class="col-md-3 col-4">
                            <label for="jobName" class="text-capitalize"> Job Name</label>
                        </div>
                        <div class="col-8 col-md-9">
                            <div class="row">
                                <div class="col">
                                    <input type="text" id="jobName" name="job_name" class="form-control">
                                </div>
                            </div>

                        </div>
                    </div>

                    <?php if ($has_upload_artwork == 'on') {
                    ?>

                        <div class="row d-fex align-items-center  mt-2">
                            <div class="col-md-3 col-4">
                                <label for="artwork" class="text-capitalize">Upload Artwork</label>

                            </div>
                            <div class="col-8 col-md-9">
                                <div class="row">
                                    <div class="col">
                                        <input class="form-control" name="custom-artwork" type="file" id="artwork" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,image/jpeg,image/png,image/gif,image/webp,application/pdf" data-async-upload="artwork" data-async-field="custom-artwork-token" aria-describedby="artworkHint">
                                        <?php // Not required by the form or utils/Cart.php; visitors without a print file thought it was. ?>
                                        <small id="artworkHint" class="d-block text-muted mt-1">Optional. JPG, PNG, GIF, WebP or PDF, up to <?php echo esc_html(wholesale_artwork_max_text()); ?>. <?php echo esc_html(wholesale_large_file_help()); ?></small>
                                    </div>
                                </div>

                            </div>
                        </div>
                    <?php }; ?>
                    <!-- <div class="row d-fex align-items-center  mt-2">
                        <div class="col-md-3 col-4">
                            <label for="turnAround" class="text-capitalize"> Turnaround</label>
                        </div>
                        <div class="col-8 col-md-9">
                            <div class="row">
                                <div class="col">
                                    <span>

                                        2-4 Business Days (Design finalized) Cut-off time 4pm PST

                                    </span>
                                </div>
                            </div>

                        </div>
                    </div> -->
                    <?php if ($product_category_slug  != 'channel-letters') { ?>
                    <?php // Production times mirrored from B2Sign (SFS B2Sign Sync) replace the default Next Day / Same Day choices.
                    $sfs_turnaround = function_exists('wholesale_product_turnaround_spec') ? wholesale_product_turnaround_spec(get_the_ID()) : array();
                    if ($sfs_turnaround) { wholesale_render_turnaround_choices($sfs_turnaround); } elseif (function_exists('wholesale_product_turnaround_none') && wholesale_product_turnaround_none(get_the_ID())) { /* B2Sign offers no production-time choice (hardware): none here either. */ } else { ?>

                        <div class="row d-fex align-items-center  my-2 mt-3">
                            <div class="col-md-3 col-4">
                                <label for="turnaroundNextDay" class="text-capitalize">Production time</label><small class="pb-attr-help">Time to make your order before it ships. Shipping speed (standard, 3-day, 2-day or overnight) is chosen at checkout.</small>
                            </div>
                            <div class="col-md-9 col-8">
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <div class="row">
                                            <div class="col-md-12 col-12">
                                                <div class="form-check">
                                                    <input class="form-check-input" class="turnaround-option" checked type="radio" name="turnaround_option" id="turnaroundNextDay" value="next_day">
                                                    <label class="form-check-label" for="turnaroundNextDay">
                                                        Next Day
                                                        <p class="form-text mb-0 mt-0 fw-normal">Cut-off time 4pm PST</p>

                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <?php
                                    // Pacific time, without changing PHP's default timezone
                                    // (WordPress expects it to stay UTC for the rest of the page).
                                    $currentTime = new DateTime('now', new DateTimeZone('America/Los_Angeles'));

                                    // Same day ships only on weekdays, for orders before noon. Always printed:
                                    // a page from the page cache can be hours old, so js/ui-polish.js shows or
                                    // hides it by the visitor's current Pacific time.
                                    $same_day_now = $currentTime->format('H') < 12 && $currentTime->format('N') < 6; ?>
                                        <div class="col-12 col-md-6" data-same-day-option<?php echo $same_day_now ? '' : ' hidden'; ?>>
                                            <div class="row">
                                                <div class="col-md-12 col-12">
                                                    <div class="form-check">
                                                        <input class="form-check-input" class="turnaround-option" value="same_day" type="radio" name="turnaround_option" id="trunaroundSameDay">
                                                        <label class="form-check-label" for="trunaroundSameDay">
                                                            Same Day
                                                            <p class="form-text mb-0 mt-0 fw-normal">Cut-off time 12pm PST</p>

                                                        </label>

                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                </div>
                            </div>

                        </div>

                    <?php } /* end of the default Next Day / Same Day choices */ ?>
                    <?php }; ?>

                    <div class="row d-fex align-items-center d-none  my-2">
                        <div class="col-md-3 col-4">
                            <label for="blindDropShip" class="text-capitalize"> Shipping</label>
                        </div>
                        <div class="col-8 col-md-8">
                            <div class="row">
                                <div class="col">
                                    <div class="form-check">
                                        <input class="form-check-input" class="shipping-option" type="radio" name="shipping_type" checked id="blindDropShip" value="blind_drop">
                                        <label class="form-check-label" for="blindDropShip">
                                            Blind Drop Ship
                                        </label>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <!-- <div class="col-4 col-md-4">
                            <div class="row">
                                <div class="col">
                                    <div class="form-check">
                                        <input class="form-check-input" class="shipping-option" value="store_pickup" type="radio" name="shipping_type" id="storePickupShip">
                                        <label class="form-check-label" for="storePickupShip">
                                            Store Pickup
                                            <p class="form-text mb-0 mt-0 fw-normal">( CA Facility Only )</p>

                                        </label>

                                    </div>
                                </div>
                            </div>

                        </div> -->

                        <div class="col-md-12 col-12 text-center" id="shippingDetailContainer">

                        </div>
                    </div>


                </div>


                <?php
                // Channel letters have no price until the sign text is typed; the Grand Total
                // row shows the starting price instead of $0.00 (js/ui-polish.js keeps the class in step).
                $total_from_text = ($product_category_slug == 'channel-letters' && trim(wp_strip_all_tags((string) $starting_at_text)) !== '') ? $starting_at_text : '';
                ?>
                <div class="product-pricing-box<?php echo $total_from_text && !$has_cl_design ? ' pd-total-empty' : ''; ?>">
                    <div class="container">
                        <?php // The everyday price only shows (as "Regular price") during a dated sale. ?>
                        <div class="row subtotal-container"<?php echo $product_promo_percent > 0 ? '' : ' hidden'; ?>>
                            <div class="col-6">
                                <span class="fs-6"><?php echo $product_promo_percent > 0 ? 'Regular price' : 'Total'; ?></span>
                            </div>
                            <div class="col-6 price-subtotal-container">
                                <span class="fs-6 d-block "><span>$</span><span class="price-subtotal"><?php


                                                                                                        if ($product_category_slug == 'channel-letters') {
                                                                                                            if ($has_cl_design) {
                                                                                                                echo format_price(get_everyday_amount($product_min_price > 0 ? $product_min_price : $product_price));
                                                                                                            } else {
                                                                                                                echo '0';
                                                                                                            }
                                                                                                        } elseif (!$size_link && !empty($initial_box['price'])) {
                                                                                                            echo format_price(isset($initial_box['regular']) ? $initial_box['regular'] : $initial_box['price']);
                                                                                                        } else {
                                                                                                            echo format_price(get_everyday_amount($product_min_price > 0 ? $product_min_price : $product_price));
                                                                                                        }

                                                                                                        ?>
                                    </span></span>
                            </div>
                        </div>
                        <?php if ($product_promo_percent > 0) { ?>
                            <div class="row totalSaving-container" style="color: gray">
                                <div class="col-6">
                                    <span class="fs-6"><?php echo esc_html(sprintf('Sale: %s%% off, ends %s', rtrim(rtrim(number_format($product_promo_percent, 2, '.', ''), '0'), '.'), wholesale_promotion_end_text($product_promotion))); ?></span>
                                </div>
                                <div class="col-6 price-subtotal-container">
                                    <span class="fs-6 d-block "><span>You save $</span><span class="price-saving"><?php echo format_price(!$size_link && !empty($initial_box['price']) ? max(0, (isset($initial_box['regular']) ? $initial_box['regular'] : $initial_box['price']) - $initial_box['price']) : get_saving($product_min_price > 0 ? $product_min_price : $product_price)); ?></span></span>
                                </div>
                            </div>
                        <?php } ?>
                        <hr>
                        <div class="row total-container">
                            <div class="col-6">
                                <span class="fs-5" style="font-size: 18px !important;">Grand Total <?php echo $product_discount_percent > 0 ? '' : '' ?></span>
                            </div>
                            <div class="col-6 price-total-container">
                                <span class="fs-4 d-block "><span>$</span><span class="price-total"><?php
                                                                                                    if ($product_category_slug == 'channel-letters' && $has_cl_design) {
                                                                                                        if ($has_cl_design) {
                                                                                                            echo format_price(get_discount_amount($product_min_price > 0 ? $product_min_price : $product_price));
                                                                                                        } else {
                                                                                                            echo '0';
                                                                                                        }
                                                                                                    } elseif ($size_link) {
                                                                                                        echo format_price($size_link['price']);
                                                                                                    } elseif (!empty($initial_box['price'])) {
                                                                                                        // The smallest order or default choice (the structured data and feed price) until the live quote replaces it.
                                                                                                        echo format_price($initial_box['price']);
                                                                                                    } else {
                                                                                                        echo format_price(get_discount_amount($product_min_price > 0 ? $product_min_price : $product_price));
                                                                                                    }


                                                                                                    ?>
                                    </span></span>
                                <?php if ($total_from_text) : ?>
                                    <span class="price-total-from">Starting at <?php echo wholesale_emphasize_price($total_from_text); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                        // Price plus standard shipping below the free minimum (js/ui-polish.js keeps it in step with the total).
                        $delivered_cl = 'channel-letters' === $product_category_slug;
                        $delivered_rates = array_filter(array_map('floatval', explode(',', (string) wholesale_get_setting($delivered_cl ? 'channel_shipping_options' : 'standard_shipping_options'))));
                        $delivered_initial = $delivered_cl && !$has_cl_design ? 0 : ($size_link ? $size_link['price'] : (!empty($initial_box['price']) ? $initial_box['price'] : get_discount_amount($product_min_price > 0 ? $product_min_price : $product_price)));
                        $delivered_text = wholesale_product_delivered_text($delivered_initial, $delivered_cl);
                        ?>
                        <p class="pd-delivered" data-free-min="<?php echo esc_attr((float) wholesale_get_setting($delivered_cl ? 'free_shipping_channel_min' : 'free_shipping_min')); ?>" data-ship="<?php echo esc_attr($delivered_rates ? min($delivered_rates) : 0); ?>"<?php echo $delivered_text ? '' : ' hidden'; ?>><?php echo esc_html($delivered_text); ?></p>

                        <?php
                        // Delivery estimate (filled in by js/ui-polish.js), quantity stepper, Add To Cart
                        // and Buy Now (inc/product-buy-box.php). Guests and signed-in customers get the same buttons.
                        wholesale_render_product_eta($product_id);
                        wholesale_render_product_actions($product_stock_status, $initial_product_cost);
                        wholesale_render_product_pay_row();
                        ?>

                        <ul class="product-assurance">
                            <?php $product_ship_min = wholesale_free_shipping_amount('channel-letters' === $product_category_slug); ?>
                            <?php $product_ship_from = wholesale_shipping_from_amount('channel-letters' === $product_category_slug); ?>
                            <?php // Say what shipping costs below the free minimum: checkout adds it, and a $15 banner reaching checkout as $34.55 was read as a hidden fee. ?>
                            <?php if ($product_ship_min) : ?>
                                <li class="product-assurance__ship"><strong>Free shipping</strong> on orders of <?php echo esc_html($product_ship_min); ?> or more<?php echo $product_ship_from ? esc_html(', ' . $product_ship_from . ' standard shipping below that') : ''; ?> &middot; <a href="<?php echo esc_url(home_url('/shipping-returns/#free-shipping')); ?>">details</a></li>
                            <?php elseif ($product_ship_from) : ?>
                                <li>Standard shipping from <?php echo esc_html($product_ship_from); ?></li>
                            <?php endif; ?>
                            <li>Order online 24/7 &mdash; your price is shown before checkout</li>
                            <li>Checkout adds only shipping<?php echo (float) wholesale_get_setting('tax_rate') > 0 ? ' and sales tax' : ''; ?> &mdash; no other fees</li>
                            <li>Secure checkout<?php echo wholesale_setting_enabled('payment_disabled') ? '' : ' by Elavon Converge'; ?></li>
                            <?php if ('channel-letters' === $product_category_slug) : ?>
                                <li>UL listed, tested before shipping, 5-year LED warranty</li>
                            <?php endif; ?>
                            <li>Questions? Call <a href="tel:+18664362101">866-436-2101</a> or text <a href="sms:+12066186543">206-618-6543</a> &middot; Mon&ndash;Fri 8am&ndash;5pm PT</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <?php wholesale_seo_render_channel_letter_guide(get_the_ID()); ?>
        <?php wholesale_render_product_guide(get_the_ID()); ?>

        <?php
        // Our own specifications, shipping, artwork and FAQ sections replace the
        // supplier's stored description (inc/product-page-content.php).
        $product_details_shown = 'channel-letters' === $product_category_slug
            ? wholesale_render_channel_letter_details(get_the_ID())
            : wholesale_render_product_details(get_the_ID());
        ?>
        <?php if (!$product_details_shown) : ?>
        <!-- product description -->
        <div class="row mt-4 overflow-hidden">
            <div class="col">
                <div class="product-main-desc">
                    <h2 class="fs-3 mb-4">Product Description</h2>

                    <?php

                    if ($product_category_slug  == 'channel-letters') {

                    ?>


                        <ul class="nav nav-tabs mt-3" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="desc-tab" data-bs-toggle="tab" data-bs-target="#desc" type="button" role="tab" aria-controls="desc" aria-selected="true">Descripton</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="component-tab" data-bs-toggle="tab" data-bs-target="#component" type="button" role="tab" aria-controls="component" aria-selected="false">Components</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="warrenty-tab" data-bs-toggle="tab" data-bs-target="#warrenty" type="button" role="tab" aria-controls="contact" aria-selected="false">Warranty</button>
                            </li>

                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="faq-tab" data-bs-toggle="tab" data-bs-target="#faq" type="button" role="tab" aria-controls="faq" aria-selected="false">FAQ</button>
                            </li>

                        </ul>
                        <div class="tab-content" id="myTabContent">

                            <!-- description -->
                            <div class="tab-pane fade show active" id="desc" role="tabpanel" aria-labelledby="desc-tab">

                                <?php echo wholesale_seo_fill_missing_alt(get_post_meta(get_the_ID(), '_product_description', true), $product_image_label); ?>
                            </div>

                            <!-- Components -->
                            <div class="tab-pane fade" id="component" role="tabpanel" aria-labelledby="component-tab">
                                <?php echo wholesale_seo_fill_missing_alt(get_post_meta(get_the_ID(), '_product_component', true), $product_image_label . ' component'); ?>
                            </div>
                            <!-- Warrenty -->
                            <div class="tab-pane fade" id="warrenty" role="tabpanel" aria-labelledby="warrenty-tab">
                                <?php echo wholesale_seo_fill_missing_alt(get_post_meta(get_the_ID(), '_product_warrenty', true), $product_image_label . ' warranty'); ?>

                            </div>
                            <!-- FAQ -->
                            <div class="tab-pane fade" id="faq" role="tabpanel" aria-labelledby="faq-tab">
                                <?php echo wholesale_seo_fill_missing_alt(get_post_meta(get_the_ID(), '_product_faq', true), $product_image_label); ?>

                            </div>
                            <!-- Manual -->
                            <!-- <div class="tab-pane fade" id="manual" role="tabpanel" aria-labelledby="faq-tab">
                            <?php // echo get_post_meta(get_the_ID(),'_product_manual',true); 
                            ?>

                            </div> -->
                        </div>



                    <?php
                    } else {
                        echo wholesale_seo_fill_missing_alt(wholesale_fix_upload_limit_text(wholesale_seo_clean_product_content(get_the_content())), $product_image_label);
                    }

                    ?>
                    <?php  ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </form>
    <?php wholesale_seo_render_related_products($product_id); ?>
    <?php
    $product_review_data = wholesale_product_review_data($product_id);
    $show_product_reviews = wholesale_setting_enabled('show_product_reviews');
    $product_reviews = wholesale_product_reviews($product_id, 6);
    // Google reviews the owner linked to this product (inc/google-review-links.php); never in the schema.
    $product_google_reviews_html = function_exists('wholesale_product_google_reviews_html') ? wholesale_product_google_reviews_html($product_id) : '';
    $review_eligibility = wholesale_setting_enabled('allow_verified_reviews') ? wholesale_review_eligibility_from_request($product_id) : array('order' => 0, 'reason' => 'none');
    $can_review_product = $review_eligibility['order'] > 0;
    $review_status = isset($_GET['review_status']) ? sanitize_key(wp_unslash($_GET['review_status'])) : '';
    // Guests arriving from a review link get the name and email from their order.
    $review_defaults = array('name' => '', 'email' => '');
    if ($can_review_product) {
        $review_billing = wholesale_decode_order_meta_array(get_post_meta($review_eligibility['order'], 'billing_address', true));
        $review_user = wp_get_current_user();
        $review_defaults = array(
            'name' => $review_user->exists() ? $review_user->display_name : trim(($review_billing['billing_fname'] ?? '') . ' ' . ($review_billing['billing_lname'] ?? '')),
            'email' => $review_user->exists() ? $review_user->user_email : ($review_billing['billing_email'] ?? ''),
        );
    }
    ?>
    <?php if ($show_product_reviews) : ?>
    <section class="product-reviews" id="product-reviews" aria-labelledby="product-reviews-title">
        <?php $google_review_url = in_array($review_status, array('sent', 'already_reviewed'), true) ? wholesale_google_write_review_url() : ''; ?>
        <?php if ('sent' === $review_status) : ?>
            <div class="product-review-alert" role="status">Thank you! Your review has been submitted and will appear here once our team approves it.<?php if ($google_review_url) : ?> <a class="product-review-google" href="<?php echo esc_url($google_review_url); ?>" target="_blank" rel="noopener"><?php echo wholesale_google_logo_svg(16); ?> Share it on Google too</a><?php endif; ?></div>
        <?php elseif ('already_reviewed' === $review_status) : ?>
            <div class="product-review-alert" role="status">You have already reviewed this product for this order. Thank you!<?php if ($google_review_url) : ?> <a class="product-review-google" href="<?php echo esc_url($google_review_url); ?>" target="_blank" rel="noopener"><?php echo wholesale_google_logo_svg(16); ?> Review us on Google</a><?php endif; ?></div>
        <?php elseif ('not_eligible' === $review_status) : ?>
            <div class="product-review-alert product-review-alert-error" role="alert">Reviews are available once your order for this product is complete.</div>
        <?php elseif ('disabled' === $review_status) : ?>
            <div class="product-review-alert product-review-alert-error" role="alert">Product reviews are currently turned off.</div>
        <?php elseif ('error' === $review_status) : ?>
            <div class="product-review-alert product-review-alert-error" role="alert">Please fill in every field and choose a star rating, then try again.</div>
        <?php endif; ?>
        <div class="product-reviews-heading">
            <div>
                <p class="product-section-eyebrow">Customer feedback</p>
                <h2 id="product-reviews-title">Reviews for <?php echo esc_html($product_heading); ?></h2>
            </div>
            <?php wholesale_render_product_rating($product_id, 'product-reviews-summary'); ?>
        </div>
        <?php if ($product_review_data['text']) : ?>
            <blockquote class="product-featured-review">
                <span class="product-featured-review-mark" aria-hidden="true">“</span>
                <p><?php echo esc_html($product_review_data['text']); ?></p>
            </blockquote>
        <?php endif; ?>
        <?php if ($product_reviews) : ?>
            <div class="product-review-list">
                <?php foreach ($product_reviews as $product_review) :
                    $review_rating = min(5, max(0, absint(get_post_meta($product_review->ID, '_review_rating', true))));
                    $review_name = get_post_meta($product_review->ID, '_review_name', true);
                    $review_title = get_post_meta($product_review->ID, '_review_title', true);
                    $review_byline = implode(' · ', array_filter(array(
                        get_post_meta($product_review->ID, '_review_company', true),
                        get_post_meta($product_review->ID, '_review_location', true),
                    )));
                ?>
                    <article class="product-review-card">
                        <?php if (wholesale_review_is_sample($product_review->ID)) : ?>
                            <span class="product-review-sample">Sample review · preview only</span>
                        <?php endif; ?>
                        <div class="product-review-card-header">
                            <span class="product-review-stars" aria-label="<?php echo esc_attr($review_rating . ' out of 5 stars'); ?>">
                                <?php echo esc_html(str_repeat('★', $review_rating) . str_repeat('☆', 5 - $review_rating)); ?>
                            </span>
                            <time class="product-review-date" datetime="<?php echo esc_attr(get_the_date('Y-m-d', $product_review)); ?>"><?php echo esc_html(get_the_date('M j, Y', $product_review)); ?></time>
                        </div>
                        <?php if ($review_title) : ?>
                            <h3 class="product-review-title"><?php echo esc_html($review_title); ?></h3>
                        <?php endif; ?>
                        <p><?php echo esc_html($product_review->post_content); ?></p>
                        <div class="product-review-author">
                            <strong><?php echo esc_html($review_name ?: 'Verified customer'); ?></strong>
                            <?php if ($review_byline) : ?>
                                <span><?php echo esc_html($review_byline); ?></span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if ($product_review_data['count'] > count($product_reviews)) : ?>
                <p class="product-review-more"><a href="<?php echo esc_url(wholesale_reviews_page_url(array('review_product' => $product_id))); ?>">See all <?php echo esc_html(number_format_i18n($product_review_data['count'])); ?> reviews for this product &rarr;</a></p>
            <?php endif; ?>
        <?php elseif (!$product_google_reviews_html) : ?>
            <p class="product-review-note">No reviews for this product yet.</p>
        <?php endif; ?>
        <?php echo $product_google_reviews_html; // Escaped in wholesale_product_google_reviews_html(). ?>
        <?php if ($can_review_product) : ?>
            <div class="product-review-form-card" id="write-review">
                <h3>Share your experience</h3>
                <p>Thanks for your order #<?php echo esc_html(wholesale_order_number($review_eligibility['order'])); ?>. Tell other customers how your <?php echo esc_html(wholesale_product_noun($product_id)); ?> turned out. Reviews appear after a quick check by our team.</p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="submit_review">
                    <input type="hidden" name="review_product_id" value="<?php echo esc_attr($product_id); ?>">
                    <input type="hidden" name="review_order" value="<?php echo esc_attr($review_eligibility['order']); ?>">
                    <input type="hidden" name="review_token" value="<?php echo esc_attr(wholesale_order_review_token($review_eligibility['order'], $product_id)); ?>">
                    <?php wp_nonce_field('submit_review', 'review_nonce'); ?>
                    <fieldset class="review-star-input">
                        <legend>Your rating</legend>
                        <span class="review-star-row">
                        <?php foreach (array(5 => 'Excellent', 4 => 'Very good', 3 => 'Good', 2 => 'Fair', 1 => 'Needs improvement') as $stars => $star_label) : ?>
                            <input type="radio" id="review-star-<?php echo esc_attr($stars); ?>" name="review_rating" value="<?php echo esc_attr($stars); ?>" required>
                            <label for="review-star-<?php echo esc_attr($stars); ?>" title="<?php echo esc_attr($star_label); ?>"><span class="review-sr"><?php echo esc_html($stars . ' stars, ' . $star_label); ?></span>&#9733;</label>
                        <?php endforeach; ?>
                        </span>
                    </fieldset>
                    <div class="product-review-form-grid">
                        <label>Name<input type="text" name="review_name" required autocomplete="name" value="<?php echo esc_attr($review_defaults['name']); ?>"></label>
                        <label><span>Email <small>(not published)</small></span><input type="email" name="review_email" required autocomplete="email" value="<?php echo esc_attr($review_defaults['email']); ?>"></label>
                    </div>
                    <label>Your review<textarea name="review_message" rows="4" required placeholder="<?php echo esc_attr(wholesale_product_is_channel_letter($product_id) ? "How did the sign look once it was installed? How was the ordering process?" : "How did the " . wholesale_product_noun($product_id) . " look when it arrived? How was the ordering process?"); ?>"></textarea></label>
                    <button type="submit" class="btn btn-primary">Submit review</button>
                </form>
            </div>
            <script>
            // Review links (My orders, shipping email) open this form. The page
            // shifts while images and the builder load, so the browser's own
            // anchor jump lands short; scroll again once layout settles.
            (function () {
                if (!/^#(write-review|product-reviews)$/.test(location.hash)) return;
                if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
                var toForm = function () {
                    var card = document.getElementById('write-review');
                    // Desktop uses #ssxHeader, phones .main-header; the hidden one measures 0.
                    var offset = 16;
                    document.querySelectorAll('#ssxHeader, .main-header').forEach(function (header) {
                        offset = Math.max(offset, header.getBoundingClientRect().height + 16);
                    });
                    // 'instant' overrides the site-wide smooth scroll, which
                    // gets cut short while the page is still loading.
                    window.scrollTo({ top: card.getBoundingClientRect().top + window.pageYOffset - offset, behavior: 'instant' });
                };
                var stop = false;
                ['wheel', 'touchstart', 'keydown'].forEach(function (type) {
                    window.addEventListener(type, function () { stop = true; }, { once: true, passive: true });
                });
                var settle = function () { if (!stop) toForm(); };
                document.addEventListener('DOMContentLoaded', settle);
                window.addEventListener('load', function () {
                    settle();
                    setTimeout(settle, 400);
                    setTimeout(settle, 1200);
                });
            })();
            </script>
        <?php elseif ('reviewed' === $review_eligibility['reason'] && 'sent' !== $review_status && 'already_reviewed' !== $review_status) : ?>
            <p class="product-review-note">Thanks, you have already reviewed this product. It appears here once approved.</p>
        <?php elseif (!is_user_logged_in()) : ?>
            <p class="product-review-note">Bought this <?php echo esc_html(wholesale_product_noun($product_id)); ?>? Use the review link in your shipping email, or <a href="<?php echo esc_url(add_query_arg('redirect_ulr', rawurlencode(get_permalink() . '#product-reviews'), home_url('/login/'))); ?>" rel="nofollow">sign in</a> with the account used for your order.</p>
        <?php endif; ?>
    </section>
    <?php endif; ?>
    </article>
    </main>
</div>

<?php

get_footer();
