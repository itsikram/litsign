<?php

// Template Name: home


function get_product_attribute_data($id, $attr)
{

    if ($id && $attr) {
        $product_attr_json = get_post_meta($id, 'product_attr', true);
        $product_attr_array = json_decode($product_attr_json);

        $selected_items = [];

        if (is_array($product_attr_array)) {
            foreach ($product_attr_array as $single_attr) {
                $attr_name = $single_attr->name;

                if ($attr_name == $attr) {
                    foreach ($single_attr->options as $option) {
                        foreach ($option as $name => $price) {
                            $selected_items[$name] = $price;
                            // array_push($selected_items,array(
                            //     $name => $price
                            // ));
                            //return $name . ': ' . ;
                        }

                    }
                }
            }
        }

        return $selected_items;
    }
}

function get_variant_cost($product_cost, $variable_cost)
{
    return round(floatval($product_cost) + floatval($variable_cost), 2);
}

function wholesale_render_home_product_gallery($product_id, $product_title, $thumbnail_id, $loading = 'lazy')
{
    $gallery_images = get_post_meta($product_id, '_product_gallery', true);
    $gallery_images = is_array($gallery_images) ? array_values(array_filter($gallery_images, 'is_string')) : array();
    $images = array();

    if ($thumbnail_id) {
        $featured_image = wp_get_attachment_image_url($thumbnail_id, 'product-card');
        if ($featured_image) {
            $images[] = $featured_image;
        }
    }

    foreach ($gallery_images as $gallery_image) {
        $gallery_image = esc_url_raw($gallery_image);
        $gallery_host = wp_parse_url($gallery_image, PHP_URL_HOST);
        if ($gallery_host === 'localhost' || $gallery_host === '127.0.0.1') {
            $site_url_parts = wp_parse_url(home_url('/'));
            $site_origin = ($site_url_parts['scheme'] ?? 'http') . '://' . ($site_url_parts['host'] ?? '');
            if (!empty($site_url_parts['port'])) {
                $site_origin .= ':' . $site_url_parts['port'];
            }
            $gallery_image = $site_origin . wp_parse_url($gallery_image, PHP_URL_PATH);
        }
        if ($gallery_image && !in_array($gallery_image, $images, true)) {
            $images[] = $gallery_image;
        }
    }

    if (empty($images)) {
        echo '<div class="pb-image-top"><span class="pb-image-placeholder" aria-hidden="true"><i class="fa-regular fa-image"></i></span></div>';
        return;
    }

    $has_gallery = count($images) > 1;
    ?>
    <div class="pb-image-top<?php echo $has_gallery ? ' has-gallery' : ''; ?>" data-gallery>
        <div class="pb-gallery-track">
            <?php foreach ($images as $index => $image) : ?>
                <?php $image = wholesale_webp_url($image); ?>
                <img class="pb-gallery-image<?php echo $index === 0 ? ' is-active' : ''; ?>"
                    <?php echo $index === 0 ? 'src="' . esc_url($image) . '"' : ''; ?>
                    data-gallery-src="<?php echo esc_url($image); ?>"
                    alt="<?php echo esc_attr($product_title); ?>"
                    loading="<?php echo $index === 0 ? esc_attr($loading) : 'lazy'; ?>"
                    decoding="async"
                    data-gallery-index="<?php echo esc_attr($index); ?>">
            <?php endforeach; ?>
        </div>
        <?php if ($has_gallery) : ?>
            <button type="button" class="pb-gallery-arrow pb-gallery-prev" aria-label="Previous product image">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>
            <button type="button" class="pb-gallery-arrow pb-gallery-next" aria-label="Next product image">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
            <span class="pb-gallery-count" aria-live="polite">1 / <?php echo esc_html(count($images)); ?></span>
        <?php endif; ?>
    </div>
    <?php
}

$requested_category = get_query_var('category_slug');
$requested_category = $requested_category ? $requested_category : (isset($_REQUEST['category_slug']) ? wp_unslash($_REQUEST['category_slug']) : '');
$current_category = $requested_category ? sanitize_title($requested_category) : 'channel-letters';
$current_term = get_term_by('slug', $current_category, 'product_category');
$is_channel_letters = 'channel-letters' === $current_category;
$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');
$builder_url = home_url('/channel-letter-builder/');
$GLOBALS['wholesale_channel_letter_landing'] = $is_channel_letters;

$current_term_id = $current_term ?  $current_term->term_id : 0;
$current_term_ref = get_term_meta($current_term_id, 'taxonomy-ref', true);

$ref_term = null;
if ($current_term_ref) {
    $ref_term = get_term_by('slug', $current_term_ref, 'product_category');
    $ref_term_id = $ref_term->term_id;

    $ref_term_title = $ref_term->name;
    $ref_term_desc = $ref_term->description;
    $ref_term_image = get_term_meta($ref_term_id, 'taxonomy-image', true);
}

function get_nested_terms($taxonomy = 'product_category', $args = array())
{
    // Default arguments for get_terms().
    $default_args = array(
        'taxonomy'   => 'product_category',
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
        'parent'     => 0, // Start with top-level terms.
    );

    // Merge passed arguments with defaults.
    $args = wp_parse_args($args, $default_args);

    // Get top-level terms.
    $parent_terms = get_terms($args);
    $nested_terms = array();

    if (is_wp_error($parent_terms)) {
        return $nested_terms;
    }

    foreach ($parent_terms as $parent) {
        // Get child terms.
        $child_args = array(
            'taxonomy'   => 'product_category',
            'hide_empty' => true,
            'parent'     => $parent->term_id, // Get terms with the current parent.
            'orderby'    => 'name',
            'order'      => 'ASC',
        );
        $children = get_terms($child_args);

        // Add parent and its children to the nested array.
        $nested_terms[] = array(
            'term'     => $parent,
            'children' => $children,
        );
    }

    return $nested_terms;
}

$all_categories = get_nested_terms('product_category');

$cl_heights = wholesale_seo_letter_height_range('standard-channel-letter-front-lit');
$cl_min_height = $cl_heights ? $cl_heights[0] : 8;
$cl_max_height = $cl_heights ? $cl_heights[1] : 0;
$storefront_signs_url = function_exists('wholesale_seo_storefront_signs_url') ? wholesale_seo_storefront_signs_url() : '';

// The last dollar amount in a product's "starting at" text (after any struck-through price), e.g. "$11.70".
function wholesale_home_from_price($slug)
{
    if (!preg_match_all('/\$\s?([\d,]+(?:\.\d+)?)/', wp_strip_all_tags(wholesale_seo_product_starting_text($slug)), $prices)) {
        return '';
    }

    return '$' . end($prices[1]);
}

// Hero proof points: the lowest channel letter price and the Google rating.
$cl_from_price = wholesale_home_from_price('standard-channel-letter-front-lit');
$google_reviews = function_exists('wholesale_google_reviews') ? wholesale_google_reviews() : null;
$google_rating = $google_reviews && !empty($google_reviews['count']) ? $google_reviews : null;

// More product lines for storefronts: product slug (image and starting price), link, name, summary.
// Listed in the "Shop by sign type" row under the hero and the "More for your storefront" section.
$more_products = array(
    array('adhesive-window-perf', wholesale_category_url('adhesive-products'), 'Window Graphics', 'Printed vinyl, see-through window perf, frosted film and clings for your storefront glass.'),
    array('deluxe-signicade-graphic-frame', wholesale_category_url('signicade-a-frames'), 'Sidewalk A-Frame Signs', 'Double-sided sidewalk signs that pull foot traffic in from the street.'),
    array('13oz-vinyl-banner', wholesale_category_url('banners'), 'Banners', 'Vinyl, mesh and fabric banners for grand openings, sales and events.'),
    array('feather-angled-flag-pole', wholesale_category_url('advertising-flags'), 'Advertising Flags', 'Feather and teardrop flags that catch drivers&rsquo; attention from the road.'),
    array('aluminum-sign', wholesale_category_url('rigid-signs-and-magnets'), 'Rigid Signs', 'Aluminum, PVC and Coroplast signs for doors, store hours and parking.'),
    array('standard-retractable-insert-stand', wholesale_category_url('banner-stands'), 'Banner Stands', 'Retractable and X-stands for lobbies, trade shows and in-store promotions.'),
    array('straight-tension-fabric-displays-graphic-frame', wholesale_category_url('trade-show-products'), 'Trade Show Displays', 'Tension fabric and pop up backwalls for your booth.'),
    array('event-tent-full-canopy-graphic-frame', wholesale_category_url('custom-event-tents'), 'Event Tents', 'Printed 10x10 canopy tents with walls and flags for outdoor events.'),
);

if ($is_channel_letters) {
    // Shown in the FAQ section below and described as FAQPage structured data in the head.
    wholesale_seo_set_page_faq(array(
        array(
            'q' => 'Which channel letter style is right for my storefront?',
            'a' => 'Front-lit letters give bold, direct illumination. Back-lit and halo-lit letters glow onto the wall behind them for a softer, upscale look. Front-and-back lit letters combine both. Not sure? Call us at <a href="tel:+18664362101">866-436-2101</a> and we&rsquo;ll help you choose.',
        ),
        array(
            'q' => 'How much do channel letters cost?',
            'a' => 'Channel letters are priced by letter height, so each style lists a starting price per inch. Open a style, enter your wording, letter height and colors, and you&rsquo;ll see your full price before checkout. For large or unusual projects, <a href="' . esc_url($contact_url) . '">request a free quote</a>.',
        ),
        array(
            'q' => 'What are channel letters made of?',
            'a' => 'Standard channel letters have .040 aluminum returns (the sides of each letter), a colored acrylic face held by a trimcap, and LED modules with a power supply inside. Halo lit letters use welded stainless steel faces and returns so the light shines out of the back.',
        ),
        array(
            'q' => 'What is the difference between halo lit and reverse lit channel letters?',
            'a' => 'Both glow onto the wall behind the letters. Halo lit letters have a hidden back with welded stainless steel faces and returns. Reverse lit letters have an exposed acrylic back that the light shines through. Standard back lit letters give a similar glow with acrylic faces and trimcaps.',
        ),
        array(
            'q' => 'What are trimless channel letters?',
            'a' => 'Trimless letters have no plastic trimcap around the acrylic face. Choose an inset face with a metal border, or a borderless exposed face for the sleekest look. Both use welded stainless steel returns.',
        ),
        array(
            'q' => 'Should I mount my letters on a raceway or directly on the wall?',
            'a' => 'A raceway is a metal box that holds the wiring and mounts the letters as one unit, so fewer holes go into your building. You can add a raceway to front lit, back lit and dual lit letters, or add one in the <a href="' . esc_url($builder_url) . '">online sign builder</a>. Letters without a raceway mount directly to the wall using the included installation pattern.',
        ),
        array(
            'q' => 'What letter heights can I order?',
            'a' => sprintf('Letter heights start at %d inches%s. The largest size depends on the style; choose a height on any product page to see what&rsquo;s available.', $cl_min_height, $cl_max_height ? sprintf(' and go up to %d inches for front lit letters', $cl_max_height) : ''),
        ),
        array(
            'q' => 'How tall should my channel letters be?',
            'a' => 'A common sign industry rule of thumb is about 1 inch of letter height for every 10 feet of viewing distance, so letters seen from 120 feet away work best at about 12 inches or taller. Also check the space on your fascia and any size limits in your lease or local sign code.',
        ),
        array(
            'q' => 'What materials and warranty do I get?',
            'a' => 'Outdoor channel letter signs are UL listed with sign section labels. Listed LED modules, power supplies and qualifying letters carry a five-year warranty.',
        ),
        array(
            'q' => 'How long will it take to get my sign?',
            'a' => 'Every sign is made to order. Your estimated ship date is shown at checkout, and after manufacturing you can choose standard (3&ndash;6 business days), 3-day, 2-day or overnight shipping.',
        ),
        array(
            'q' => 'Is my sign ready to install when it arrives?',
            'a' => 'Yes. Every sign is tested before shipment and includes a wiring diagram and an installation pattern for your installer.',
        ),
        array(
            'q' => 'Can I pick up my order?',
            'a' => 'Pickup isn&rsquo;t available &mdash; every order ships directly to you.',
        ),
    ));
} elseif ($current_term) {
    // Buyer questions shown under the products and described as FAQPage data in the head.
    wholesale_seo_set_page_faq(wholesale_seo_category_faq($current_term->slug));
}

get_header();



// Print the array for testing

?>

<section class="home-hero<?php echo $is_channel_letters ? ' home-hero--cl' : ''; ?>" aria-labelledby="home-hero-title">
    <div class="home-hero-content">
        <div class="container home-hero-inner">
            <div class="home-hero-copy">
                <?php if ($is_channel_letters) : ?>
                    <p class="home-hero-eyebrow">Made in USA &middot; UL listed &middot; 5-year LED warranty</p>
                    <h1 id="home-hero-title" class="home-hero-title">Custom LED Channel Letter Signs <span>for Your Storefront</span></h1>
                    <p class="home-hero-proof">
                        <?php if ($google_rating) : ?>
                            <a class="home-hero-rating" href="#customer-reviews"><?php echo wholesale_review_stars($google_rating['rating']); ?> <strong><?php echo esc_html(number_format((float) $google_rating['rating'], 1)); ?></strong> on Google (<?php echo esc_html(number_format_i18n((int) $google_rating['count'])); ?>)</a>
                        <?php endif; ?>
                        <span><?php echo wholesale_home_icon('flag'); ?> Ships to Washington &amp; all 50 states</span>
                    </p>
                    <p class="home-hero-lead">Pick your style, see your exact price online, and get letters that are tested and ready to install&nbsp;&mdash; wiring diagram included.</p>
                    <div class="home-hero-actions home-hero-actions--cl">
                        <a class="home-hero-shop-button" href="#product-box-container"><span class="home-hero-button-text">See Styles &amp; Prices<?php if ($cl_from_price) : ?><small>From <?php echo esc_html($cl_from_price); ?> per inch</small><?php endif; ?></span> <span aria-hidden="true">&rarr;</span></a>
                        <a class="home-hero-secondary-button" href="<?php echo esc_url($contact_url); ?>"><?php echo wholesale_home_icon('message'); ?> Get a Free Quote</a>
                    </div>
                    <p class="home-hero-help">
                        <?php echo wholesale_home_icon('phone'); ?>
                        Talk to a sign specialist: <a href="tel:+18664362101">866-436-2101</a>
                        <span class="home-hero-help-hours">Mon&ndash;Fri, 8am&ndash;5pm PST &middot; or <a href="<?php echo esc_url($builder_url); ?>">design your sign online</a></span>
                    </p>
                <?php else : ?>
                    <p class="home-hero-eyebrow">Premium quality signs &amp; letters</p>
                    <p id="home-hero-title" class="home-hero-title">Make Your Brand <br class="home-hero-mobile-break"><span> Stand Out</span></p>
                <p class="home-hero-lead">Custom LED channel letters, storefront signs, acrylic signs and more.<br class="home-hero-desktop-break"> Built for businesses that want to be seen.</p>
                <div class="home-hero-actions">
                    <a class="home-hero-shop-button" href="#product-box-container">Shop Now <span aria-hidden="true">&rarr;</span></a>
                    <button class="home-hero-video-link" type="button" data-youtube-video="https://www.youtube-nocookie.com/embed?listType=search&amp;list=channel%20letter%20signs" aria-controls="homeVideoModal" aria-haspopup="dialog">
                        <span class="home-hero-play" aria-hidden="true"><i class="fa-solid fa-play"></i></span>
                        <span>Watch Video</span>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="home-hero-features" aria-label="Our service benefits">
        <?php if ($is_channel_letters) : ?>
        <div class="container home-hero-feature-grid">
            <div class="home-hero-feature">
                <span class="home-hero-feature-icon" aria-hidden="true"><?php echo wholesale_home_icon('flag'); ?></span>
                <span><strong>Made in USA</strong><small>Built to order for your business.</small></span>
            </div>
            <div class="home-hero-feature">
                <span class="home-hero-feature-icon" aria-hidden="true"><?php echo wholesale_home_icon('badge'); ?></span>
                <span><strong>UL Listed</strong><small>Outdoor signs ship with UL labels.</small></span>
            </div>
            <div class="home-hero-feature">
                <span class="home-hero-feature-icon" aria-hidden="true"><?php echo wholesale_home_icon('shield'); ?></span>
                <span><strong>5-Year LED Warranty</strong><small>On listed LED modules &amp; power supplies.</small></span>
            </div>
            <div class="home-hero-feature">
                <span class="home-hero-feature-icon" aria-hidden="true"><?php echo wholesale_home_icon('plug'); ?></span>
                <span><strong>Tested Before Shipping</strong><small>Arrives ready to install.</small></span>
            </div>
        </div>
        <?php else : ?>
        <div class="container home-hero-feature-grid">
            <div class="home-hero-feature">
                <span class="home-hero-feature-icon" aria-hidden="true"><i class="fa-solid fa-truck-fast"></i></span>
                <span><strong>Fast &amp; Reliable Shipping</strong><small>Get your signs on time.</small></span>
            </div>
            <div class="home-hero-feature">
                <span class="home-hero-feature-icon" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></span>
                <span><strong>High Quality Products</strong><small>Built to last.</small></span>
            </div>
            <div class="home-hero-feature">
                <span class="home-hero-feature-icon" aria-hidden="true"><i class="fa-solid fa-gear"></i></span>
                <span><strong>Expert Support</strong><small>We're here to help.</small></span>
            </div>
            <div class="home-hero-feature">
                <span class="home-hero-feature-icon" aria-hidden="true"><i class="fa-solid fa-star"></i></span>
                <span><strong>100% Satisfaction</strong><small>Your success is our priority.</small></span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<div class="home-video-modal" id="homeVideoModal" hidden aria-hidden="true">
    <div class="home-video-modal-backdrop" data-video-modal-close></div>
    <div class="home-video-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="homeVideoModalTitle">
        <div class="home-video-modal-header">
            <p id="homeVideoModalTitle" class="h5 mb-0">Storefront Sign Online</p>
            <button type="button" class="home-video-modal-close" data-video-modal-close aria-label="Close video">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <div class="home-video-modal-frame">
            <iframe title="Storefront Sign Online video" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
        </div>
    </div>
</div>

<script>
    (() => {
        const modal = document.getElementById('homeVideoModal');
        const trigger = document.querySelector('.home-hero-video-link');
        const frame = modal ? modal.querySelector('iframe') : null;
        if (!modal || !trigger || !frame) return;

        const closeModal = () => {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            frame.removeAttribute('src');
            document.body.classList.remove('home-video-modal-open');
        };

        trigger.addEventListener('click', () => {
            frame.src = trigger.dataset.youtubeVideo;
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('home-video-modal-open');
            modal.querySelector('.home-video-modal-close').focus();
        });

        modal.querySelectorAll('[data-video-modal-close]').forEach((element) => {
            element.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) closeModal();
        });
    })();

</script>

<?php if ($is_channel_letters) : ?>
<nav class="home-types" aria-label="Shop by sign type">
    <div class="container">
        <p class="home-types-title">Shop by sign type</p>
        <ul class="home-types-list">
            <?php
            $type_links = array_merge(
                array(array('standard-channel-letter-front-lit', '#product-box-container', 'Channel Letters', '', '/inch')),
                array_map(static function ($item) {
                    return array($item[0], $item[1], $item[2], '', '');
                }, $more_products)
            );
            foreach ($type_links as $type_link) :
                $type_product = wholesale_seo_product($type_link[0]);
                $type_price = wholesale_home_from_price($type_link[0]);
                ?>
                <li>
                    <a class="home-types-item" href="<?php echo esc_url($type_link[1]); ?>">
                        <span class="home-types-image">
                            <?php
                            if ($type_product && has_post_thumbnail($type_product)) {
                                echo get_the_post_thumbnail($type_product, 'thumbnail', array('loading' => 'lazy', 'decoding' => 'async', 'alt' => ''));
                            }
                            ?>
                        </span>
                        <span class="home-types-text">
                            <strong><?php echo esc_html($type_link[2]); ?></strong>
                            <?php if ($type_price) : ?><small>From <?php echo esc_html($type_price . $type_link[4]); ?></small><?php endif; ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>

<div class="container">
    <header class="cl-shop-header">
        <p class="cl-kicker">Step 1 &middot; Choose your style</p>
        <h2 class="cl-section-title">Channel Letter Styles &amp; Starting Prices</h2>
        <p class="cl-section-lead">Every custom storefront sign is built to order. Open a style to pick your letter height, colors and wording and see your exact price before checkout.</p>
        <p class="cl-shop-header-help">Not sure which style fits your storefront? <a href="tel:+18664362101">Call 866-436-2101</a> or <a href="<?php echo esc_url($contact_url); ?>">request a free quote</a>.</p>
    </header>
</div>
<?php else : ?>
<div class="container">
    <header class="shop-header">
        <?php
        $shop_heading_tag = $is_channel_letters ? 'h2' : 'h1';
        $category_seo = $current_term ? wholesale_seo_category_meta() : array();
        $category_seo = $current_term && isset($category_seo[$current_term->slug]) ? $category_seo[$current_term->slug] : array();
        $shop_heading = $current_term ? (!empty($category_seo['h1']) ? $category_seo['h1'] : $current_term->name) : __('Custom Signs', 'litsign');
        ?>
        <<?php echo $shop_heading_tag; ?> class="shop-header-title text-center fs-2"><?php echo esc_html($is_channel_letters ? __('Shop Channel Letter Sign Styles', 'litsign') : $shop_heading); ?></<?php echo $shop_heading_tag; ?>>
        <?php if (!$is_channel_letters && !empty($category_seo['intro'])) : ?>
            <p class="shop-header-intro text-center mb-0"><?php echo wp_kses_post($category_seo['intro']); ?></p>
        <?php else : ?>
            <p class="text-center fs-5 mb-0"><?php echo esc_html($is_channel_letters ? __('Create a stronger storefront presence with custom LED channel letters made for retail businesses.', 'litsign') : ($current_term ? $current_term->description : __('Shop custom signage designed and built for your business.', 'litsign'))); ?></p>
        <?php endif; ?>
        <?php if ($is_channel_letters) : ?>
            <p class="text-center mt-3"><a class="btn btn-primary" href="<?php echo esc_url($contact_url); ?>">Request a Channel Letter Sign Quote</a></p>
        <?php endif; ?>
    </header>
</div>
<?php endif; ?>

<div class="container my-3 product-listing-container<?php echo $is_channel_letters ? ' product-listing-container--cl' : ''; ?>">

    <div class="row">
        <?php if (!$is_channel_letters) : ?>
        <div class="col-md-2">
            <!-- <div class="category-filter-container">

                <?php

                foreach ($all_categories as $category) {
                    $parent_category = $category['term'];
                    $parent_id = $parent_category->term_id;
                    $parent_name = $parent_category->name;
                    $parent_slug = $parent_category->slug;
                ?>

                    <h3 class="category-heading text-truncate">
                        <?php echo $parent_name; ?>
                    </h3>
                    <ul class="category-filter">

                        <?php

                        $childrens = $category['children'];

                        foreach ($childrens as $child) {
                            $child_id = $child->term_id;
                            $child_slug = $child->slug;
                            $child_name = $child->name;
                            $is_current_item = $child_slug == $current_category ? true : false;

                        ?>

                            <li class="filter-item text-truncate <?php echo $is_current_item ? 'active' : ''; ?>">
                                <a href="<?php echo esc_url(wholesale_category_url($child_slug)); ?>" class="d-flex justify-content-between">
                                    <span class="text"><?php echo $child_name; ?></span>
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </li>

                        <?php
                        }
                        ?>
                    </ul>

                <?php
                }

                ?>

            </div> -->


            <div class="category-filter-menu-wrapper">
                <button type="button" class="category-filter-toggler" aria-expanded="false" aria-controls="category-filter-menu">
                    <span class="category-filter-toggler-label">Browse categories</span>
                    <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                </button>
                <?php
                if (has_nav_menu('category-filter-menu')) {
                    wp_nav_menu(array(
                        'theme_location' => 'category-filter-menu',
                        'container_id' => 'category-filter-menu',
                        //'container_class' => 'header-menu-container',
                        'menu_class' => 'filter-menu text-center',

                    ));
                }

                ?>
            </div>

        </div>
        <?php endif; ?>
        <div class="<?php echo $is_channel_letters ? 'col-12' : 'col-md-10'; ?>">

            <!-- <div class="category-selecteor-container container mt-3">
                <div class="row">
                    <div class="col-md-8 offset-md-2 px-1">
                        <div class="category-selector active" data-cat="all">All Products</div>
                        <div id="channelLetterFilterBtn" class="category-selector" data-cat="channel-letters">Channel Letter Products</div>
                        <div id="adhesiveLetterFilterBtn" class="category-selector" data-cat="adhesive-products">Adhesive Products</div>
                    </div>
                </div>
            </div> -->

            <div id="product-box-container" class="product-box-container d-flex flex-wrap">

                <?php
                // for chennel letters
                $product_query = new WP_Query(array(
                    "post_type" => "product",
                    "post_per_page" => 99,
                    'order' => 'ASC',
                    'meta_key' => '_order_by_index',
                    'orderby' => 'meta_value_num',
                    'nopaging' => true,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'product_category',
                            'field' => 'slug',
                            'terms' => $current_category,
                        )
                    ),
                    'meta_query' => array(
                        array(
                            'key'     => '_show_in_list', // The custom field key
                            'value'   => 'on', // The custom field value you want to match
                            'compare' => '=', // Comparison operator (default is '=')
                        ),
                    ),
                ));
                if ($product_query->have_posts()) {
                    $is_first_product = true;
                    while ($product_query->have_posts()) {
                        $product_query->the_post();

                        $short_desc = get_post_meta(get_the_ID(), "_product_list_desc", true);
                        $price_per_sqft = get_post_meta(get_the_ID(), "_price_per_sqft", true);
                        $starting_at_text = get_post_meta(get_the_ID(), "_starting_at_text", true);
                        $starting_at_options = get_post_meta(get_the_ID(), "_starting_at_options", true);
                        $terms = get_the_terms(get_the_ID(), 'product_category');
                        $product_category_slug = isset($terms[0]) ? $terms[0]->slug : '';
                        $product_thumbnail_id = get_post_thumbnail_id(get_the_ID());
                        $product_slug = get_post_field('post_name', get_the_ID(), 'raw');

                ?>
                        <div class="product-box <?php echo $product_slug; ?>" data-product-category="<?php echo $product_category_slug; ?>">
                            <?php wholesale_render_home_product_gallery(
                                get_the_ID(),
                                $is_channel_letters ? 'Custom channel letter sign: ' . get_the_title() : get_the_title(),
                                $product_thumbnail_id,
                                $is_first_product ? 'eager' : 'lazy'
                            ); ?>
                            <?php $is_first_product = false; ?>
                            <a href="<?php echo get_permalink(); ?>">
                                <div class="pb-details">
                                    <h2 class="pb-title fs-6 text-truncate" title="<?php echo esc_attr(get_the_title()); ?>">
                                        <?php echo esc_html(get_the_title()); ?>
                                    </h2>
                                    <?php wholesale_render_product_rating(get_the_ID()); ?>
                                    <div class="pb-description-list">
                                        <?php echo wp_kses_post($short_desc); ?>
                                    </div>
                                    <hr>
                                    <div class="start-at-pricing">

                                        <?php if ($starting_at_options) {
                                            $product_size_data = get_product_attribute_data(get_the_ID(), $starting_at_options);

                                        ?>
                                            <table class="table">
                                                <tbody>

                                                    <?php
                                                    foreach ($product_size_data as $name => $value) {
                                                    ?>

                                                        <tr>
                                                            <th><a href="<?php echo get_permalink(get_the_ID()) . '?' . $starting_at_options . '=' . $value; ?>">
                                                                    <?php
                                                                    echo $name;

                                                                    ?>
                                                                </a></th>
                                                            <td>
                                                                <a href="<?php echo get_permalink(get_the_ID()) . '?' . $starting_at_options . '=' . $value; ?>">
                                                                    $<?php


                                                                        if (str_contains($value, '/')) {
                                                                            echo get_variant_cost($price_per_sqft, explode('/', $value)[0]);
                                                                        } else {
                                                                            echo get_variant_cost($price_per_sqft, $value);
                                                                        }

                                                                        ?>
                                                                </a>
                                                            </td>

                                                        </tr>


                                                    <?php
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        <?php

                                        } else {
                                        ?>
                                            <span class="pb-title-short fs-6">
                                                Starting at
                                            </span>
                                            <span class="pb-price">
                                                <?php echo $starting_at_text; ?>
                                            </span>
                                        <?php
                                        }; ?>

                                    </div>
                                    <?php if ($is_channel_letters) : ?>
                                        <span class="pb-cta">Get your price <?php echo wholesale_home_icon('arrow'); ?></span>
                                    <?php endif; ?>

                                </div>
                            </a>

                        </div>
                    <?php
                    }
                } else { ?>
                    <p class="text-muted text-center w-100">No Product Found</p> <?php
                                                                                } ?>

                <?php if ($ref_term && !$is_channel_letters) : ?>
                    <div class="product-box" data-product-category="adhesive-products">
                        <a href="<?php echo esc_url(wholesale_category_url($ref_term->slug)); ?>">
                            <div class="pb-image-top">
                                <?php
                                $ref_term_image_id = attachment_url_to_postid($ref_term_image);
                                if ($ref_term_image_id) {
                                    echo wp_get_attachment_image($ref_term_image_id, 'product-card', false, array(
                                        'alt' => esc_attr($ref_term_title),
                                        'loading' => 'lazy',
                                        'decoding' => 'async',
                                        'sizes' => '(max-width: 767px) 290px, 290px',
                                    ));
                                } else {
                                    echo '<img src="' . esc_url($ref_term_image) . '" alt="' . esc_attr($ref_term_title) . '" loading="lazy" decoding="async">';
                                }
                                ?>
                            </div>
                            <div class="pb-details">
                                <h2 class="pb-title fs-6 text-truncate" title="<?php echo esc_attr($ref_term_title); ?>">
                                    <?php echo esc_html($ref_term_title); ?></h2>
                                <div class="pb-description-list">
                                    <?php echo wp_kses_post($ref_term_desc); ?>

                                </div>
                                <hr>
                                <p class="pb-pricing d-flex justify-content-between">
                                    <span class="pb-title-short fs-6">

                                        <a href="<?php echo esc_url(wholesale_category_url($ref_term->slug)); ?>">See All</a>

                                    </span>
                                    <span class="pb-price">
                                    </span>
                                </p>
                            </div>
                        </a>

                    </div>
                <?php endif; ?>


            </div>

        </div>
    </div>
</div>

<?php if (!$is_channel_letters && !empty($GLOBALS['wholesale_page_faq'])) : ?>
    <section class="cl-faq" aria-labelledby="category-faq-title">
        <div class="container">
            <p class="cl-kicker">Before you order</p>
            <h2 id="category-faq-title" class="cl-section-title"><?php echo esc_html(sprintf(__('%s: Questions & Answers', 'litsign'), $shop_heading)); ?></h2>
            <div class="cl-faq-list">
                <?php wholesale_seo_render_faq(); ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($is_channel_letters) : ?>
    <?php
    // Lighting guide rows. Links and starting prices come from each product, so they
    // stay in step with the price the customer sees on the product page.
    $cl_compare_styles = array(
        'standard-channel-letter-front-lit' => array('Front lit (face lit)', 'Light shines through the colored acrylic face.', 'The brightest, easiest-to-read storefront sign at night.'),
        'standard-channel-letter-back-lit' => array('Back lit', 'Light glows onto the wall behind the letters.', 'A softer glow around each letter.'),
        'standard-channel-letter-front-back-lit' => array('Front &amp; back lit (dual lit)', 'Light shines through the face and onto the wall.', 'A lit face plus a halo for maximum presence.'),
        'hidden-back-halo-lit' => array('Halo lit', 'Solid stainless steel face; light shines out the back.', 'An upscale look for salons, offices and boutiques.'),
        'halo-reverse-acrylic-lit-channel-letters' => array('Reverse lit', 'Exposed acrylic back casts a halo on the wall.', 'A halo glow with a painted metal face.'),
        'inset-acrylic-face-lit-with-border-no-trimcap' => array('Trimless with border', 'Inset acrylic face with a metal border, no trimcap.', 'A clean face lit look without plastic trimcap.'),
        'exposed-acrylic-face-lit-borderless-no-trimcap' => array('Borderless', 'Exposed acrylic face with no trimcap or border.', 'The sleekest modern face lit letter.'),
    );
    $cl_compare_rows = array();
    foreach ($cl_compare_styles as $cl_slug => $cl_style) {
        $cl_product = get_page_by_path($cl_slug, OBJECT, 'product');
        if (!$cl_product || 'publish' !== $cl_product->post_status) {
            continue;
        }
        $cl_compare_rows[] = array(
            'style' => $cl_style,
            'url' => get_permalink($cl_product),
            'price' => get_post_meta($cl_product->ID, '_starting_at_text', true),
        );
    }
    ?>
    <?php if ($cl_compare_rows) : ?>
    <section class="cl-compare" aria-labelledby="cl-compare-title">
        <div class="container">
            <p class="cl-kicker">Compare lighting styles</p>
            <h2 id="cl-compare-title" class="cl-section-title">Front Lit, Back Lit, Halo Lit or Reverse Lit Channel Letters?</h2>
            <p class="cl-section-lead">Every custom channel letter sign is priced by letter height. Here is how the styles differ and where each one starts.</p>
            <div class="cl-compare-table-wrap">
                <table class="cl-compare-table">
                    <thead>
                        <tr>
                            <th scope="col">Style</th>
                            <th scope="col">How it lights</th>
                            <th scope="col">Best for</th>
                            <th scope="col">Starting at</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cl_compare_rows as $cl_row) : ?>
                            <tr>
                                <th scope="row"><a href="<?php echo esc_url($cl_row['url']); ?>"><?php echo wp_kses_post($cl_row['style'][0]); ?></a></th>
                                <td><?php echo esc_html($cl_row['style'][1]); ?></td>
                                <td><?php echo esc_html($cl_row['style'][2]); ?></td>
                                <td class="cl-compare-price"><?php echo wp_kses_post($cl_row['price']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="cl-compare-note">Your exact price depends on letter height, number of letters and options such as a raceway. You&rsquo;ll see it before checkout.</p>
        </div>
    </section>
    <?php endif; ?>

    <section class="sf-section cl-guide" aria-labelledby="cl-guide-title">
        <div class="container sf-prose">
            <p class="cl-kicker">Channel letter guide</p>
            <h2 id="cl-guide-title" class="cl-section-title">What Are Channel Letters?</h2>
            <p>Channel letters are individually built, three-dimensional letters used as exterior business signs. Each letter is a shallow metal &ldquo;channel&rdquo; with LED lighting inside, which is why they are the most common lit sign on retail stores, restaurants, salons and offices. They read clearly by day and glow at night, and every letter is cut to your wording, font and colors.<?php if ($storefront_signs_url) : ?> Comparing sign types for your business? See our <a href="<?php echo esc_url($storefront_signs_url); ?>">storefront signs guide</a>.<?php endif; ?></p>

            <h3>How channel letters are built</h3>
            <ul class="sf-facts">
                <li><strong>Returns</strong>The sides of each letter give it depth: .040 aluminum on standard letters, welded stainless steel on halo, reverse lit, trimless and borderless styles.</li>
                <li><strong>Faces</strong>Colored acrylic that the light shines through, or a solid metal face on halo lit letters so the light glows out of the back.</li>
                <li><strong>Trimcap &amp; LEDs</strong>A trimcap holds the face on standard letters (trimless styles skip it). LED modules and a power supply light each letter.</li>
            </ul>

            <h3>Choosing your letter height</h3>
            <p>Start with how far away your customers are. A common sign industry rule of thumb is about 1 inch of letter height for every 10 feet of viewing distance, so letters seen from 120 feet away work best at about 12 inches or taller. Our letters start at <?php echo esc_html($cl_min_height); ?> inches<?php if ($cl_max_height) : ?> and go up to <?php echo esc_html($cl_max_height); ?> inches for front lit letters<?php endif; ?>. Measure the space on your fascia, and check any sign criteria in your lease before you choose.</p>

            <h3>Mounting and installation</h3>
            <p>Letters mount directly to the wall or on a raceway, a metal box that holds the wiring and mounts the sign as one unit so fewer holes go into your building. Every sign is tested before it ships and arrives with an installation pattern and a wiring diagram for your installer. In most areas the electrical connection must be made by a licensed electrician or sign contractor.</p>

            <h3>How channel letter pricing works</h3>
            <p>Channel letters are priced per letter by letter height. Your total is the number of letters times the price for the height you choose, plus options such as a raceway, and you see it before checkout. The table above shows where each style starts; <a href="<?php echo esc_url($builder_url); ?>">design your sign online</a> for an exact price, or <a href="<?php echo esc_url($contact_url); ?>">send your logo for a free quote</a>.</p>
        </div>
    </section>

    <section class="sf-section" aria-labelledby="home-more-title">
        <div class="container">
            <p class="cl-kicker">More for your storefront</p>
            <h2 id="home-more-title" class="cl-section-title">Window Graphics, Banners, Flags &amp; Displays</h2>
            <p class="cl-section-lead">Pair your channel letters with signs at eye level. Every product shows its price online.<?php if ($storefront_signs_url) : ?> Compare them all in our <a href="<?php echo esc_url($storefront_signs_url); ?>">storefront signs guide</a>.<?php endif; ?></p>
            <div class="sf-type-grid">
                <?php foreach ($more_products as $item) : ?>
                    <?php
                    $item_product = wholesale_seo_product($item[0]);
                    $item_price = wholesale_seo_product_starting_text($item[0]);
                    ?>
                    <article class="sf-type-card">
                        <a class="sf-type-image" href="<?php echo esc_url($item[1]); ?>" tabindex="-1" aria-hidden="true">
                            <?php
                            if ($item_product && has_post_thumbnail($item_product)) {
                                echo get_the_post_thumbnail($item_product, 'medium_large', array(
                                    'loading' => 'lazy',
                                    'decoding' => 'async',
                                    'alt' => wp_strip_all_tags($item[2]),
                                    'sizes' => '(max-width: 575px) 100vw, (max-width: 991px) 50vw, 25vw',
                                ));
                            }
                            ?>
                        </a>
                        <div class="sf-type-body">
                            <h3><a href="<?php echo esc_url($item[1]); ?>"><?php echo esc_html($item[2]); ?></a></h3>
                            <p><?php echo wp_kses_post($item[3]); ?></p>
                            <?php if ($item_price) : ?>
                                <p class="sf-type-price"><small>Starting at</small> <?php echo wp_kses_post($item_price); ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="cl-steps" aria-labelledby="cl-steps-title">
        <div class="container">
            <p class="cl-kicker">Simple ordering</p>
            <h2 id="cl-steps-title" class="cl-section-title">How Ordering Your Sign Works</h2>
            <ol class="cl-steps-list">
                <li>
                    <span class="cl-step-number" aria-hidden="true">1</span>
                    <h3>Choose a style</h3>
                    <p>Front lit, back lit, dual lit, halo lit, reverse lit or trimless &mdash; compare the styles above.</p>
                </li>
                <li>
                    <span class="cl-step-number" aria-hidden="true">2</span>
                    <h3>Customize &amp; see your price</h3>
                    <p>Enter your wording, letter height and colors, or <a href="<?php echo esc_url($builder_url); ?>">use the online sign builder</a>. Your price updates as you go.</p>
                </li>
                <li>
                    <span class="cl-step-number" aria-hidden="true">3</span>
                    <h3>We build &amp; test it</h3>
                    <p>Your letters are made in the USA, and every sign is tested before it ships.</p>
                </li>
                <li>
                    <span class="cl-step-number" aria-hidden="true">4</span>
                    <h3>Ready to install</h3>
                    <p>Your sign arrives with a wiring diagram and installation pattern. Choose standard, 3-day, 2-day or overnight shipping at checkout.</p>
                </li>
            </ol>
        </div>
    </section>

    <section class="cl-quality" aria-labelledby="cl-quality-title">
        <div class="container cl-quality-inner">
            <div class="cl-quality-copy">
                <p class="cl-kicker">Built to last</p>
                <h2 id="cl-quality-title" class="cl-section-title">Commercial-Grade Signs for Your Storefront</h2>
                <p class="cl-section-lead">Custom channel letters give your business a polished look, with bright, energy-efficient LED lighting that stands out day and night.</p>
                <a class="cl-button" href="#product-box-container">Compare Styles &amp; Prices <?php echo wholesale_home_icon('arrow'); ?></a>
            </div>
            <ul class="cl-quality-list">
                <li><?php echo wholesale_home_icon('badge'); ?><span><strong>UL listed</strong> outdoor channel letter signs with sign section labels.</span></li>
                <li><?php echo wholesale_home_icon('shield'); ?><span><strong>5-year warranty</strong> on listed LED modules, power supplies and qualifying letters.</span></li>
                <li><?php echo wholesale_home_icon('flag'); ?><span><strong>Made in USA</strong> with .040 aluminum or welded stainless steel returns and acrylic faces.</span></li>
                <li><?php echo wholesale_home_icon('plug'); ?><span><strong>Tested before shipping</strong>, with a wiring diagram and installation pattern included.</span></li>
            </ul>
        </div>
    </section>

    <section class="cl-help" aria-labelledby="cl-help-title">
        <div class="container cl-help-inner">
            <div class="cl-help-copy">
                <h2 id="cl-help-title">Talk to a Real Sign Specialist</h2>
                <p>Have a logo, a storefront photo or a question about sizing? We&rsquo;ll help you pick the right sign before you order.</p>
                <ul class="cl-help-details">
                    <li><?php echo wholesale_home_icon('clock'); ?> Mon&ndash;Fri, 8:00am&ndash;5:00pm PST</li>
                    <li><?php echo wholesale_home_icon('pin'); ?> 707 S. Grady Way, Suite 600, Renton, WA 98057</li>
                </ul>
            </div>
            <div class="cl-help-actions">
                <a class="cl-help-action cl-help-action--primary" href="tel:+18664362101"><?php echo wholesale_home_icon('phone'); ?><span><small>Call toll free</small>866-436-2101</span></a>
                <a class="cl-help-action" href="sms:+12066186543"><?php echo wholesale_home_icon('message'); ?><span><small>Text us</small>206-618-6543</span></a>
                <a class="cl-help-action" href="mailto:TR@StorefrontSignOnline.com"><?php echo wholesale_home_icon('mail'); ?><span><small>Email</small>TR@StorefrontSignOnline.com</span></a>
                <a class="cl-help-quote" href="<?php echo esc_url($contact_url); ?>">Request a free quote <?php echo wholesale_home_icon('arrow'); ?></a>
            </div>
        </div>
    </section>

    <section class="cl-faq" aria-labelledby="cl-faq-title">
        <div class="container">
            <p class="cl-kicker">Before you order</p>
            <h2 id="cl-faq-title" class="cl-section-title">Frequently Asked Questions</h2>
            <div class="cl-faq-list">
                <?php wholesale_seo_render_faq(); ?>
            </div>
        </div>
    </section>

    <nav class="cl-mobile-bar" aria-label="Quick actions">
        <a class="cl-mobile-bar-call" href="tel:+18664362101"><?php echo wholesale_home_icon('phone'); ?> Call</a>
        <a class="cl-mobile-bar-quote" href="<?php echo esc_url($contact_url); ?>">Free Quote</a>
        <a class="cl-mobile-bar-shop" href="#product-box-container">See Prices</a>
    </nav>
<?php endif; ?>

<?php

get_footer();
