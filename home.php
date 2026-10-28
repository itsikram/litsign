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
        $featured_image = wp_get_attachment_image_url($thumbnail_id, 'full');
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
        $default_cover = !empty($GLOBALS['wholesale_channel_letter_landing']) ? wholesale_channel_letter_default_cover_url() : '';
        if ($default_cover) {
            echo '<a class="pb-image-top d-block" href="' . esc_url(get_permalink($product_id)) . '" tabindex="-1">';
            echo '<img class="pb-gallery-image is-active" src="' . esc_url(wholesale_webp_url($default_cover)) . '" alt="' . esc_attr($product_title) . '" loading="' . esc_attr($loading) . '" decoding="async" width="800" height="600">';
            wholesale_render_sale_badge($product_id);
            echo '</a>';
            return;
        }

        echo '<a class="pb-image-top d-block" href="' . esc_url(get_permalink($product_id)) . '" tabindex="-1"><span class="pb-image-placeholder" aria-hidden="true"><i class="fa-regular fa-image"></i></span>';
        wholesale_render_sale_badge($product_id);
        echo '</a>';
        return;
    }

    $has_gallery = count($images) > 1;
    // Card-sized copies instead of the 1000px originals; alt text per photo.
    $card_images = wholesale_card_images($images);
    ?>
    <div class="pb-image-top<?php echo $has_gallery ? ' has-gallery' : ''; ?>" data-gallery>
        <?php wholesale_render_sale_badge($product_id); ?>
        <a class="pb-gallery-track" href="<?php echo esc_url(get_permalink($product_id)); ?>" tabindex="-1">
            <?php foreach ($images as $index => $image) : ?>
                <?php
                $image_alt = wholesale_product_photo_alt($image, $product_title);
                $card_image = $card_images[$image];
                $image = wholesale_webp_url($index === 0 ? $card_image['src'] : $card_image['large']);
                ?>
                <img class="pb-gallery-image<?php echo $index === 0 ? ' is-active' : ''; ?>"
                    <?php echo $index === 0 ? 'src="' . esc_url($image) . '"' : ''; ?>
                    <?php echo $index === 0 && $card_image['srcset'] ? 'srcset="' . esc_attr($card_image['srcset']) . '" sizes="(max-width: 767px) 100vw, 300px" width="300" height="300"' : ''; ?>
                    data-gallery-src="<?php echo esc_url($image); ?>"
                    alt="<?php echo esc_attr($image_alt); ?>"
                    loading="<?php echo $index === 0 ? esc_attr($loading) : 'lazy'; ?>"
                    decoding="async"
                    data-gallery-index="<?php echo esc_attr($index); ?>">
            <?php endforeach; ?>
        </a>
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
// The front page is the brand and catalog hub; /custom-channel-letters/ is the
// in-depth channel letters page. The front page summarizes and links to it
// instead of repeating its comparison table and FAQ, so Google doesn't see two
// near-identical pages competing for the same searches.
$is_front_home = $is_channel_letters && is_front_page();
if ($is_channel_letters) {
    // These pages end with the phone quick-action bar (.cl-mobile-bar), which
    // replaces the header's second row on phones. The body class applies that
    // from the first paint; body:has(.cl-mobile-bar) only matched once the bar,
    // at the very end of the HTML, had arrived, so the header shrank 67px
    // mid-load and PageSpeed counted a layout shift.
    add_filter('body_class', static function ($classes) {
        $classes[] = 'has-cl-mobile-bar';
        return $classes;
    });
}
// The front page is the catalog hub led by banners & displays
// (template-parts/home-catalog.php); the listing below serves the category pages.
if ($is_front_home) {
    get_template_part('template-parts/home', 'catalog');
    return;
}
$cl_landing_id = function_exists('wholesale_channel_letters_landing_id') ? wholesale_channel_letters_landing_id() : 0;
$cl_landing_url = $cl_landing_id ? get_permalink($cl_landing_id) : home_url('/custom-channel-letters/');
$home_page_url = function ($slug) {
    $page = get_page_by_path($slug);

    return $page && 'publish' === $page->post_status ? get_permalink($page) : '';
};

$current_term_id = $current_term ?  $current_term->term_id : 0;
$current_term_image = $current_term_id ? get_term_meta($current_term_id, 'taxonomy-image', true) : '';
$current_term_ref = get_term_meta($current_term_id, 'taxonomy-ref', true);

$ref_term = null;
$ref_term_image = '';
if ($current_term_ref) {
    $ref_term = get_term_by('slug', $current_term_ref, 'product_category');
    if ($ref_term && !is_wp_error($ref_term)) {
        $ref_term_id = $ref_term->term_id;

        $ref_term_title = $ref_term->name;
        $ref_term_desc = $ref_term->description;
        $ref_term_image = get_term_meta($ref_term_id, 'taxonomy-image', true);
    }
}

// The linked hardware category's image is a product close-up, not a scene, so
// it is not used as the hero background; the default hero photo is used instead.
$category_hero_image = $current_term_image;
// The home page hero uses the home page's own featured image. Without one it
// falls back to the /custom-channel-letters/ hero photo.
if ($is_channel_letters && is_front_page()) {
    $front_page_hero_image = get_the_post_thumbnail_url((int) get_option('page_on_front'), '2048x2048');
    if (!$front_page_hero_image && wholesale_channel_letters_landing_id()) {
        $front_page_hero_image = get_the_post_thumbnail_url(wholesale_channel_letters_landing_id(), '2048x2048');
    }
    $category_hero_image = $front_page_hero_image ? $front_page_hero_image : $category_hero_image;
}
$category_hero_image = $category_hero_image ? wholesale_webp_url(esc_url_raw($category_hero_image)) : '';
$default_category_hero_image = wholesale_default_hero_url();
$category_hero_background = wholesale_hero_background_style($category_hero_image, $default_category_hero_image);

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

// The unit a "starting at" price is quoted in, so "$1.98" reads as "$1.98/sq ft".
function wholesale_home_price_unit($slug)
{
    $text = strtolower(wp_strip_all_tags(wholesale_seo_product_starting_text($slug)));
    if (false !== strpos($text, 'letter')) {
        return '/letter';
    }
    if (false !== strpos($text, 'inch')) {
        return '/inch';
    }

    return false !== strpos($text, 'ft') ? '/sq ft' : '';
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

if ($is_front_home) {
    // Company and catalog questions. Channel letter details (styles, heights,
    // raceways, warranty) are answered on /custom-channel-letters/.
    $home_cost_url = $home_page_url('channel-letter-cost');
    $home_install_url = $home_page_url('how-to-install-channel-letters');
    $home_permits_url = $home_page_url('sign-permits');
    $home_industries_url = $home_page_url('industries');
    $home_locations_url = $home_page_url('locations');
    $home_link = function ($url, $text) {
        return $url ? '<a href="' . esc_url($url) . '">' . $text . '</a>' : $text;
    };
    wholesale_seo_set_page_faq(array_values(array_filter(array(
        array(
            'q' => 'What does Storefront Sign Online make?',
            'a' => 'Custom business signs, built to order: LED ' . $home_link($cl_landing_url, 'channel letters') . ' in seven lighting styles, plus ' . $home_link(wholesale_category_url('banners'), 'banners') . ', ' . $home_link(wholesale_category_url('adhesive-products'), 'window graphics') . ', sidewalk ' . $home_link(wholesale_category_url('signicade-a-frames'), 'A-frame signs') . ', ' . $home_link(wholesale_category_url('advertising-flags'), 'advertising flags') . ', banner stands, trade show displays, rigid signs and event tents. ' . $home_link(wholesale_shop_url(), 'Browse every product') . '.',
        ),
        array(
            'q' => 'Where are your signs made, and where do you ship?',
            'a' => 'Channel letters are built in Washington and California; banners, window graphics and our other printed products are made in Renton, WA. Everything ships to all 50 states, with standard, 3-day, 2-day or overnight delivery. Pickup isn&rsquo;t available.',
        ),
        array(
            'q' => 'Do you install signs?',
            'a' => 'No. We manufacture and ship. Every lit sign is tested before it leaves and comes with a wiring diagram and install pattern, so your licensed electrician or sign installer can mount it.' . ($home_install_url ? ' Our ' . $home_link($home_install_url, 'channel letter installation guide') . ' explains what they will do.' : ''),
        ),
        array(
            'q' => 'How do I find out what my sign will cost?',
            'a' => 'Every product page shows your price as you choose a size and options, before checkout. Channel letters are priced per letter by height' . ($home_cost_url ? '; the ' . $home_link($home_cost_url, 'channel letter cost guide') . ' shows real examples' : '') . '. For a logo or a large project, ' . $home_link($contact_url, 'request a free quote') . '.',
        ),
        array(
            'q' => 'Can you work from my logo?',
            'a' => 'Yes. Upload it with a ' . $home_link($contact_url, 'quote request') . ' or text it to <a href="sms:+12066186543">206-618-6543</a>, and a sign specialist will reply within one business day. You can also ' . $home_link($builder_url, 'design your channel letters online') . '.',
        ),
        $home_permits_url ? array(
            'q' => 'Do I need a permit for an outdoor sign?',
            'a' => 'Usually. Most cities require a permit for exterior signs, and many leases have their own sign rules. Our ' . $home_link($home_permits_url, 'sign permit guide') . ' explains the process and who applies; we don&rsquo;t pull permits.',
        ) : null,
        ($home_industries_url || $home_locations_url) ? array(
            'q' => 'Do you make signs for my type of business?',
            'a' => 'We make signs for retail stores, restaurants, salons, medical offices, franchises and trade show exhibitors across the country.' . ($home_industries_url ? ' Our ' . $home_link($home_industries_url, 'industry guides') . ' suggest the right signs for your business' : '') . ($home_locations_url ? ($home_industries_url ? ', and our ' : ' Our ') . $home_link($home_locations_url, 'location guides') . ' cover local sign rules' : '') . '.',
        ) : null,
        array(
            'q' => 'Where can I compare channel letter styles?',
            'a' => 'On our ' . $home_link($cl_landing_url, 'custom channel letters page') . ', which sets all seven lighting styles side by side and answers questions about letter heights, raceways, materials and the five-year warranty.',
        ),
    ))));
} elseif ($is_channel_letters) {
    // Shown in the FAQ section below and described as FAQPage structured data in the head.
    wholesale_seo_set_page_faq(array(
        array(
            'q' => 'Which channel letter style is right for my storefront?',
            'a' => 'Front-lit letters give bold, direct illumination. Back-lit and halo-lit letters glow onto the wall behind them for a softer, upscale look. Front-and-back lit letters combine both. Not sure? Call us at <a href="tel:+18664362101">866-436-2101</a> and we&rsquo;ll help you choose.',
        ),
        array(
            'q' => 'How much do channel letters cost?',
            'a' => 'Channel letters are priced by letter height, so each style lists its starting price for one letter at the smallest height. Open a style, enter your wording, letter height and colors, and you&rsquo;ll see your full price before checkout. For large or unusual projects, <a href="' . esc_url($contact_url) . '">request a free quote</a>.',
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
            'a' => 'Outdoor channel letter signs are UL listed with sign section labels. All 7 channel letter styles carry a five-year warranty, and the LED modules and power supplies each carry their own five-year warranty.',
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



// Category heading and copy for the hero. The heading and description are
// managed under Products > Categories; the SEO intro is a fallback.
$category_seo = $current_term ? wholesale_seo_category_meta() : array();
$category_seo = $current_term && isset($category_seo[$current_term->slug]) ? $category_seo[$current_term->slug] : array();
$shop_heading = $current_term ? $current_term->name : __('Custom Signs', 'litsign');
$category_description = $current_term ? trim((string) $current_term->description) : '';
$category_description_text = trim(wp_strip_all_tags($category_description));
$category_intro = '' === $category_description_text && !empty($category_seo['intro']) ? $category_seo['intro'] : '';
if ('' === $category_description_text && '' === $category_intro) {
    $category_description = __('Shop custom signage designed and built for your business.', 'litsign');
    $category_description_text = $category_description;
}

?>

<main id="primary" class="site-main">

<section class="home-hero<?php echo $is_channel_letters ? ' home-hero--cl' : ' home-hero--category'; ?>" aria-labelledby="home-hero-title">
    <div class="home-hero-content" style="<?php echo esc_attr($category_hero_background); ?>">
        <div class="container home-hero-inner">
            <div class="home-hero-copy">
                <?php if ($is_channel_letters) : ?>
                    <p class="home-hero-eyebrow">Made in USA &middot; UL listed &middot; 5-year LED warranty</p>
                    <?php if ($is_front_home) : ?>
                    <h1 id="home-hero-title" class="home-hero-title">Custom Business Signs, <span>Built to Order</span></h1>
                    <?php else : ?>
                    <h1 id="home-hero-title" class="home-hero-title">Custom LED Channel Letter Signs <span>for Your Storefront</span></h1>
                    <?php endif; ?>
                    <p class="home-hero-proof">
                        <?php if ($google_rating) : ?>
                            <a class="home-hero-rating" href="#customer-reviews"><?php echo wholesale_review_stars($google_rating['rating']); ?> <strong><?php echo esc_html(number_format((float) $google_rating['rating'], 1)); ?></strong> on Google (<?php echo esc_html(number_format_i18n((int) $google_rating['count'])); ?>)</a>
                        <?php endif; ?>
                        <span><?php echo wholesale_home_icon('flag'); ?> Made in Washington &amp; California &middot; ships to all 50 states</span>
                    </p>
                    <?php if ($is_front_home) : ?>
                    <p class="home-hero-lead">Storefront Sign Online builds UL listed LED channel letters in Washington and California, and prints banners, window graphics, A-frames and flags in Renton, WA. Every product shows its price online, or send your logo and a sign specialist will quote it within one business day.</p>
                    <p class="home-hero-install">We don&rsquo;t install. Each lit sign ships tested, with a wiring diagram and install pattern for your licensed electrician or sign installer.</p>
                    <?php else : ?>
                    <p class="home-hero-lead">UL listed LED channel letters, built to order in Washington and California. Choose your style and see your exact price now, or send your logo and a sign specialist will quote it within one business day.</p>
                    <p class="home-hero-install">We build, test and ship. Your licensed electrician or sign installer mounts it with the included wiring diagram and install pattern.</p>
                    <?php endif; ?>
                    <div class="home-hero-actions home-hero-actions--cl">
                        <a class="home-hero-shop-button" href="#product-box-container"><span class="home-hero-button-text">See Styles &amp; Prices<?php if ($cl_from_price) : ?><small>From <?php echo esc_html($cl_from_price); ?> per letter</small><?php endif; ?></span> <span aria-hidden="true">&rarr;</span></a>
                        <a class="home-hero-secondary-button" href="<?php echo esc_url($contact_url); ?>"><?php echo wholesale_home_icon('message'); ?> Get a Free Quote</a>
                    </div>
                    <?php $cl_example_text = function_exists('wholesale_cl_worked_example_text') ? wholesale_cl_worked_example_text() : ''; ?><?php if ($cl_example_text) : ?><p class="home-hero-example"><?php echo wp_kses_post($cl_example_text); ?></p><?php endif; ?>
                    <p class="home-hero-logo"><a href="<?php echo esc_url($contact_url); ?>">Have a logo? Send it for a free quote</a> <span>or <a href="sms:+12066186543">text your logo to 206-618-6543</a></span></p>
                    <p class="home-hero-help">
                        <?php echo wholesale_home_icon('phone'); ?>
                        Talk to a sign specialist: <a href="tel:+18664362101">866-436-2101</a>
                        <span class="home-hero-help-hours">Mon&ndash;Fri, 8am&ndash;5pm PT &middot; or <a href="<?php echo esc_url($builder_url); ?>">design your sign online</a></span>
                    </p>
                <?php else : ?>
                    <nav class="home-hero-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'litsign'); ?>">
                        <ol>
                            <li><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'litsign'); ?></a></li>
                            <?php if ($current_term) : ?>
                                <li aria-current="page"><?php echo esc_html($current_term->name); ?></li>
                            <?php endif; ?>
                        </ol>
                    </nav>
                    <h1 id="home-hero-title" class="home-hero-title"><?php echo esc_html($shop_heading); ?></h1>
                <?php if ('' !== $category_description_text) : ?>
                    <div class="home-hero-lead"><?php echo wp_kses_post(wpautop($category_description)); ?></div>
                <?php endif; ?>
                <?php if ('' !== $category_intro) : ?>
                    <p class="home-hero-intro<?php echo '' === $category_description_text ? ' home-hero-intro--lead' : ''; ?>"><?php echo wp_kses_post($category_intro); ?></p>
                <?php endif; ?>
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
    <?php if ($is_channel_letters) : ?>
    <div class="cll-trust" aria-label="Why our channel letters">
        <ul class="container cll-trust-list">
            <li><?php echo wholesale_home_icon('badge'); ?><span><strong>UL listed</strong><small>Labels on every sign section</small></span></li>
            <li><?php echo wholesale_home_icon('flag'); ?><span><strong>Made in the USA</strong><small>Built in Washington &amp; California</small></span></li>
            <li><?php echo wholesale_home_icon('shield'); ?><span><strong>5-year warranty</strong><small>All 7 channel letter styles</small></span></li>
            <li><?php echo wholesale_home_icon('plug'); ?><span><strong>Tested before shipping</strong><small>Wiring diagram included</small></span></li>
        </ul>
    </div>
    <?php endif; ?>
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

<nav class="home-types" aria-label="Shop by sign type">
    <div class="container">
        <div class="home-types-head">
            <p class="home-types-title">Shop by sign type</p>
            <a class="home-types-all" href="<?php echo esc_url(wholesale_shop_url()); ?>">View all products <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="home-types-rail" data-types-rail>
        <button type="button" class="home-types-arrow home-types-arrow--prev" data-types-prev aria-label="Scroll sign types left" hidden><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
        <ul class="home-types-list" data-types-list>
            <?php
            $type_links = array_merge(
                array(array('standard-channel-letter-front-lit', $is_channel_letters ? '#product-box-container' : wholesale_category_url('channel-letters'), 'Channel Letters')),
                $more_products
            );
            foreach ($type_links as $type_link) :
                $type_product = wholesale_seo_product($type_link[0]);
                $type_price = wholesale_home_from_price($type_link[0]);
                $type_price .= $type_price ? wholesale_home_price_unit($type_link[0]) : '';
                ?>
                <li>
                    <a class="home-types-item" href="<?php echo esc_url($type_link[1]); ?>">
                        <span class="home-types-image">
                            <?php
                            if ($type_product && has_post_thumbnail($type_product)) {
                                echo get_the_post_thumbnail($type_product, 'product-card', array('sizes' => '(max-width: 575px) 104px, 140px', 'loading' => 'lazy', 'decoding' => 'async', 'alt' => ''));
                            }
                            ?>
                        </span>
                        <span class="home-types-text">
                            <strong><?php echo esc_html($type_link[2]); ?></strong>
                            <?php if ($type_price) : ?><small><span>From</span> <?php echo esc_html($type_price); ?></small><?php endif; ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="home-types-arrow home-types-arrow--next" data-types-next aria-label="Scroll sign types right" hidden><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
        </div>
    </div>
</nav>

<?php if ($is_channel_letters) : ?>
<div class="container">
    <header class="cl-shop-header">
        <p class="cl-kicker">Step 1 &middot; Choose your style</p>
        <h2 class="cl-section-title">Channel Letter Styles &amp; Starting Prices</h2>
        <p class="cl-section-lead">Every custom storefront sign is built to order. Open a style to pick your letter height, colors and wording and see your exact price before checkout.</p>
        <p class="cl-shop-header-help">Not sure which style fits your storefront? <a href="tel:+18664362101">Call 866-436-2101</a> or <a href="<?php echo esc_url($contact_url); ?>">request a free quote</a>.</p>
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

            <?php if (!$is_channel_letters && $current_term) {
                wholesale_render_category_lead($current_term->slug, $shop_heading);
            } ?>
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
                            <?php echo wholesale_admin_edit_product_link(get_the_ID()); // Escaped in helper. ?>
                            <a href="<?php echo get_permalink(); ?>">
                                <div class="pb-details">
                                    <?php $card_name = $is_channel_letters ? get_the_title() : wholesale_seo_product_search_name(get_the_ID()); ?>
                                    <h3 class="pb-title fs-6<?php echo $is_channel_letters ? ' text-truncate' : ''; ?>" title="<?php echo esc_attr($card_name); ?>">
                                        <?php echo esc_html($card_name); ?>
                                    </h3>
                                    <?php wholesale_render_product_rating(get_the_ID()); ?>
                                    <div class="pb-description-list">
                                        <?php echo wp_kses_post($short_desc); ?>
                                    </div>
                                    <hr>
                                    <div class="start-at-pricing<?php echo $starting_at_options ? ' start-at-pricing--table' : ''; ?>">

                                        <?php if ($starting_at_options) {
                                            $product_size_data = get_product_attribute_data(get_the_ID(), $starting_at_options);
                                            // Phones show one "Starting at" line instead of the size table.
                                            $size_prices = array();
                                            foreach ($product_size_data as $size_value) {
                                                $size_prices[] = get_variant_cost($price_per_sqft, str_contains($size_value, '/') ? explode('/', $size_value)[0] : $size_value);
                                            }
                                            $size_prices = array_filter($size_prices);
                                        ?>
                                            <?php if ($size_prices || trim(wp_strip_all_tags((string) $starting_at_text)) !== '') : ?>
                                                <p class="pb-price-summary">
                                                    <span class="pb-title-short">Starting at</span>
                                                    <span class="pb-price"><?php echo trim(wp_strip_all_tags((string) $starting_at_text)) !== '' ? wholesale_emphasize_price(wholesale_price_no_cents($starting_at_text)) : '<strong>' . esc_html(wholesale_whole_dollars(min($size_prices))) . '</strong>'; ?></span>
                                                </p>
                                            <?php endif; ?>
                                            <table class="table">
                                                <tbody>

                                                    <?php
                                                    foreach ($product_size_data as $name => $value) {
                                                    ?>

                                                        <tr>
                                                            <th><a href="<?php echo esc_url(add_query_arg($starting_at_options, $value, get_permalink(get_the_ID()))); ?>" rel="nofollow">
                                                                    <?php
                                                                    echo esc_html($name);

                                                                    ?>
                                                                </a></th>
                                                            <td>
                                                                <a href="<?php echo esc_url(add_query_arg($starting_at_options, $value, get_permalink(get_the_ID()))); ?>" rel="nofollow">
                                                                    <?php echo esc_html(wholesale_whole_dollars(get_variant_cost($price_per_sqft, str_contains($value, '/') ? explode('/', $value)[0] : $value))); ?>
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
                                                <?php echo $is_channel_letters ? wholesale_price_no_cents($starting_at_text) : wholesale_emphasize_price(wholesale_price_no_cents($starting_at_text)); ?>
                                            </span>
                                        <?php
                                        }; ?>

                                    </div>
                                    <?php if ($is_channel_letters) : ?>
                                        <span class="pb-cta">Get your price <?php echo wholesale_home_icon('arrow'); ?></span>
                                    <?php else : ?>
                                        <span class="pb-shop-cta" aria-hidden="true">Choose size &amp; options <?php echo wholesale_home_icon('arrow'); ?></span>
                                    <?php endif; ?>

                                </div>
                            </a>

                        </div>
                    <?php
                    }
                    if ($is_channel_letters) : ?>
                        <div class="cl-help-card">
                            <span class="cl-help-card-eyebrow">Need help choosing?</span>
                            <h3 class="cl-help-card-title">Not sure which channel letter is right for you?</h3>
                            <p class="cl-help-card-text">Tell us your wall size and logo. A sign specialist will recommend a style and quote it for you.</p>
                            <ul class="cl-help-card-list">
                                <li><?php echo wholesale_home_icon('badge'); ?> UL listed for outdoor use</li>
                                <li><?php echo wholesale_home_icon('shield'); ?> 5-year warranty on all 7 styles</li>
                                <li><?php echo wholesale_home_icon('plug'); ?> Tested, with a wiring diagram and install pattern</li>
                                <li><?php echo wholesale_home_icon('flag'); ?> Made in USA, ships to all 50 states</li>
                            </ul>
                            <div class="cl-help-card-actions">
                                <a class="cl-help-card-primary" href="<?php echo esc_url($builder_url); ?>"><?php echo wholesale_home_icon('pen'); ?> Design Your Sign</a>
                                <a class="cl-help-card-secondary" href="<?php echo esc_url($contact_url); ?>"><?php echo wholesale_home_icon('message'); ?> Get a Free Quote</a>
                                <a class="cl-help-card-phone" href="tel:+18664362101"><?php echo wholesale_home_icon('phone'); ?> 866-436-2101</a>
                            </div>
                        </div>
                    <?php endif;
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
                                <h3 class="pb-title fs-6 text-truncate" title="<?php echo esc_attr($ref_term_title); ?>">
                                    <?php echo esc_html($ref_term_title); ?></h3>
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

<?php if (!$is_channel_letters && $current_term) {
    wholesale_render_category_guide($current_term->slug);
} ?>

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
            'price' => wholesale_price_no_cents(get_post_meta($cl_product->ID, '_starting_at_text', true)),
        );
    }
    ?>
    <?php if ($cl_compare_rows && !$is_front_home) : ?>
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

    <?php if ($is_front_home) : ?>
    <?php // A summary only: the full guide lives on /custom-channel-letters/, which owns these searches. ?>
    <section class="sf-section cl-guide" aria-labelledby="cl-guide-title">
        <div class="container sf-prose">
            <p class="cl-kicker">Channel letter guide</p>
            <h2 id="cl-guide-title" class="cl-section-title">Choosing Your Channel Letters</h2>
            <p>Channel letters are individually built, lit letters mounted on your building. Each one is a metal &ldquo;channel&rdquo; with LEDs inside, cut to your wording, font and colors, and they are the most common lit sign on retail stores, restaurants, salons and offices. Ours start at <?php echo esc_html($cl_min_height); ?> inches tall, ship tested with a wiring diagram and install pattern, and carry a five-year warranty on all seven styles.</p>
            <ul class="sf-facts">
                <li><strong>Compare the styles</strong>Front lit, back lit, halo lit, reverse lit and trimless letters side by side on our <a href="<?php echo esc_url($cl_landing_url); ?>">custom channel letters page</a>.</li>
                <?php if ($home_cost_url) : ?><li><strong>Plan your budget</strong>Real price examples by letter height and count in the <a href="<?php echo esc_url($home_cost_url); ?>">channel letter cost guide</a>.</li><?php endif; ?>
                <?php if ($storefront_signs_url) : ?><li><strong>Weigh other options</strong>Lit and non-lit choices for every part of your storefront, compared in our <a href="<?php echo esc_url($storefront_signs_url); ?>">storefront signs guide</a>.</li><?php endif; ?>
            </ul>
            <p><a href="<?php echo esc_url($builder_url); ?>">Design your sign online</a> for an exact price, or <a href="<?php echo esc_url($contact_url); ?>">send your logo for a free quote</a>.</p>
        </div>
    </section>
    <?php else : ?>
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
            <p>Start with how far away your customers are. Sign makers often size letters at roughly 1 inch of height per 10 feet of viewing distance: if drivers see your storefront from 120 feet away, plan on letters about 12 inches tall or more. Our letters start at <?php echo esc_html($cl_min_height); ?> inches<?php if ($cl_max_height) : ?> and go up to <?php echo esc_html($cl_max_height); ?> inches for front lit letters<?php endif; ?>. Measure the space on your fascia, and check any sign criteria in your lease before you choose.</p>

            <h3>Mounting and installation</h3>
            <p>Letters mount directly to the wall or on a raceway, a metal box that holds the wiring and mounts the sign as one unit so fewer holes go into your building. Every sign is tested before it ships and arrives with an installation pattern and a wiring diagram for your installer. In most areas the electrical connection must be made by a licensed electrician or sign contractor.</p>

            <h3>How channel letter pricing works</h3>
            <?php $cl_price_note = function_exists('wholesale_cl_starting_price_note') ? wholesale_cl_starting_price_note() : ''; ?>
            <p><?php if ($cl_price_note) : ?><?php echo wp_kses_post($cl_price_note); ?> You see your total before checkout. <?php else : ?>Channel letters are priced per letter by letter height. Your total is the number of letters times the price for the height you choose, plus options such as a raceway, and you see it before checkout. <?php endif; ?><?php if ($is_front_home) : ?>Each style above lists its starting price; <?php else : ?>The table above shows where each style starts; <?php endif; ?><a href="<?php echo esc_url($builder_url); ?>">design your sign online</a> for an exact price, or <a href="<?php echo esc_url($contact_url); ?>">send your logo for a free quote</a>.</p>
            <?php if ($is_front_home) : ?>
            <h3>Compare every lighting style</h3>
            <p>Front lit, back lit, dual lit, halo lit, reverse lit, trimless and borderless letters each suit a different storefront. Our <a href="<?php echo esc_url($cl_landing_url); ?>">custom channel letters page</a> compares all seven side by side, with how each one lights, what it is best for and where its price starts.</p>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="sf-section" aria-labelledby="home-more-title">
        <div class="container">
            <p class="cl-kicker">More for your storefront</p>
            <h2 id="home-more-title" class="cl-section-title">Window Graphics, Banners, Flags &amp; Displays</h2>
            <p class="cl-section-lead">Pair your channel letters with signs at eye level. Every product shows its price online.<?php if ($storefront_signs_url) : ?> Compare them all in our <a href="<?php echo esc_url($storefront_signs_url); ?>">storefront signs guide</a>.<?php endif; ?></p>
            <div class="sf-type-grid">
                <?php foreach ($more_products as $item) : ?>
                    <?php
                    $item_product = wholesale_seo_product($item[0]);
                    $item_price = wholesale_price_no_cents(wholesale_seo_product_starting_text($item[0]));
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
                <li><?php echo wholesale_home_icon('shield'); ?><span><strong>5-year warranty</strong> on all 7 channel letter styles, plus the LED modules and power supplies.</span></li>
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
            <h2 id="cl-faq-title" class="cl-section-title"><?php echo $is_front_home ? 'Questions About Storefront Sign Online' : 'Frequently Asked Questions'; ?></h2>
            <div class="cl-faq-list">
                <?php wholesale_seo_render_faq(); ?>
            </div>
            <?php if ($is_front_home) : ?>
                <p class="cl-section-lead cl-faq-note">Questions about channel letter styles, heights or raceways? See the <a href="<?php echo esc_url($cl_landing_url); ?>#cl-faq-title">channel letter FAQ</a>.</p>
            <?php endif; ?>
        </div>
    </section>

    <nav class="cl-mobile-bar" aria-label="Quick actions">
        <button type="button" class="cl-mobile-bar-products" aria-controls="megaMenu"><?php echo wholesale_home_icon('grid'); ?> All Products</button>
        <a class="cl-mobile-bar-quote" href="<?php echo esc_url($contact_url); ?>">Free Quote</a>
        <a class="cl-mobile-bar-shop" href="<?php echo esc_url(home_url('/channel-letter-builder/')); ?>">Start Building</a>
    </nav>
<?php endif; ?>

</main>

<?php

get_footer();
