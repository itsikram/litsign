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

// Stroke icons for the channel letter landing sections (the icon font is a small subset).
function wholesale_home_icon($name)
{
    $paths = array(
        'flag'    => '<path d="M4 22V4"/><path d="M4 4h13l-2 4 2 4H4"/>',
        'badge'   => '<path d="M12 2l2.4 1.8 3-.2 1 2.8 2.6 1.6-.8 2.9.8 2.9-2.6 1.6-1 2.8-3-.2L12 22l-2.4-1.8-3 .2-1-2.8L3 16l.8-2.9L3 10.2l2.6-1.6 1-2.8 3 .2z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
        'shield'  => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>',
        'plug'    => '<path d="M9 2v6M15 2v6"/><path d="M6 8h12v3a6 6 0 0 1-12 0z"/><path d="M12 17v5"/>',
        'phone'   => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
        'pen'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'message' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'mail'    => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 6L2 7"/>',
        'clock'   => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'pin'     => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    );

    if (!isset($paths[$name])) {
        return '';
    }

    return '<svg class="cl-icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
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
                    <p class="home-hero-lead">Pick your style, see your price online, and receive letters that are tested before shipping and ready to install&nbsp;&mdash; wiring diagram and installation pattern included.</p>
                    <div class="home-hero-actions home-hero-actions--cl">
                        <a class="home-hero-shop-button" href="#product-box-container">See Styles &amp; Prices <span aria-hidden="true">&rarr;</span></a>
                        <a class="home-hero-secondary-button" href="<?php echo esc_url($builder_url); ?>"><?php echo wholesale_home_icon('pen'); ?> Design Your Sign Online</a>
                    </div>
                    <p class="home-hero-help">
                        <?php echo wholesale_home_icon('phone'); ?>
                        Questions? Talk to a sign specialist: <a href="tel:+18664362101">866-436-2101</a>
                        <span class="home-hero-help-hours">Mon&ndash;Fri, 8am&ndash;5pm PST</span>
                    </p>
                <?php else : ?>
                    <p class="home-hero-eyebrow">Premium quality signs &amp; letters</p>
                    <h2 id="home-hero-title" class="home-hero-title">Make Your Brand <br class="home-hero-mobile-break"><span> Stand Out</span></h2>
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
            <h2 id="homeVideoModalTitle">Storefront Sign Online</h2>
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

    document.addEventListener('DOMContentLoaded', () => {
        const reviews = [
            {
                quote: '“Excellent service and high-quality products! Our new channel letters look amazing and brought more customers to our store.”',
                author: '— Michael R., Phoenix, AZ'
            },
            {
                quote: '“The entire process was smooth from start to finish. Our storefront sign looks premium and the team kept us informed every step of the way.”',
                author: '— Amanda S., San Diego, CA'
            },
            {
                quote: '“We upgraded our exterior branding and immediately noticed a stronger curb appeal. The product quality and support were outstanding.”',
                author: '— Daniel T., Austin, TX'
            },
            {
                quote: '“Their custom channel letters made our business stand out day and night. The craftsmanship was exactly what we hoped for.”',
                author: '— Lauren K., Nashville, TN'
            }
        ];

        const quoteEl = document.getElementById('customer-review-quote');
        const authorEl = document.getElementById('customer-review-author');
        const prevButton = document.querySelector('.customer-review-prev');
        const nextButton = document.querySelector('.customer-review-next');
        const dots = document.querySelectorAll('.customer-review-dot');

        if (!quoteEl || !authorEl || !prevButton || !nextButton || dots.length === 0) return;

        let currentIndex = 0;

        const renderReview = (index) => {
            currentIndex = (index + reviews.length) % reviews.length;
            quoteEl.textContent = reviews[currentIndex].quote;
            authorEl.textContent = reviews[currentIndex].author;

            dots.forEach((dot, i) => {
                const isActive = i === currentIndex;
                dot.classList.toggle('is-active', isActive);
                dot.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        };

        prevButton.addEventListener('click', () => renderReview(currentIndex - 1));
        nextButton.addEventListener('click', () => renderReview(currentIndex + 1));

        dots.forEach((dot) => {
            dot.addEventListener('click', () => renderReview(Number(dot.dataset.reviewDot)));
        });

        renderReview(currentIndex);
    });
</script>

<?php if ($is_channel_letters) : ?>
<div class="container">
    <header class="cl-shop-header">
        <p class="cl-kicker">Step 1 &middot; Choose your style</p>
        <h2 class="cl-section-title">Channel Letter Styles &amp; Starting Prices</h2>
        <p class="cl-section-lead">Every style is built to order. Open a style to pick your letter height, colors and wording and see your exact price before checkout.</p>
        <p class="cl-shop-header-help">Not sure which style fits your storefront? <a href="tel:+18664362101">Call 866-436-2101</a> or <a href="<?php echo esc_url($contact_url); ?>">request a free quote</a>.</p>
    </header>
</div>
<?php else : ?>
<div class="container">
    <header class="shop-header">
        <?php $shop_heading_tag = $is_channel_letters ? 'h2' : 'h1'; ?>
        <<?php echo $shop_heading_tag; ?> class="shop-header-title text-center fs-2"><?php echo esc_html($is_channel_letters ? __('Shop Channel Letter Sign Styles', 'litsign') : ($current_term ? $current_term->name : __('Custom Signs', 'litsign'))); ?></<?php echo $shop_heading_tag; ?>>
        <p class="text-center fs-5 mb-0"><?php echo esc_html($is_channel_letters ? __('Create a stronger storefront presence with custom LED channel letters made for retail businesses.', 'litsign') : ($current_term ? $current_term->description : __('Shop custom signage designed and built for your business.', 'litsign'))); ?></p>
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

<?php if ($is_channel_letters) : ?>
    <section class="cl-steps" aria-labelledby="cl-steps-title">
        <div class="container">
            <p class="cl-kicker">Simple ordering</p>
            <h2 id="cl-steps-title" class="cl-section-title">How Ordering Your Sign Works</h2>
            <ol class="cl-steps-list">
                <li>
                    <span class="cl-step-number" aria-hidden="true">1</span>
                    <h3>Choose a style</h3>
                    <p>Front lit, back lit, dual lit, halo lit or acrylic face lit &mdash; compare the styles above.</p>
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
                <details>
                    <summary>Which channel letter style is right for my storefront?</summary>
                    <p>Front-lit letters give bold, direct illumination. Back-lit and halo-lit letters glow onto the wall behind them for a softer, upscale look. Front-and-back lit letters combine both. Not sure? Call us at <a href="tel:+18664362101">866-436-2101</a> and we&rsquo;ll help you choose.</p>
                </details>
                <details>
                    <summary>How do I know what my sign will cost?</summary>
                    <p>Each style lists its starting price. Open a style, enter your wording, letter height and colors, and you&rsquo;ll see your full price before checkout. For large or unusual projects, <a href="<?php echo esc_url($contact_url); ?>">request a free quote</a>.</p>
                </details>
                <details>
                    <summary>What materials and warranty do I get?</summary>
                    <p>Outdoor channel letter signs are UL listed with sign section labels. Listed LED modules, power supplies and qualifying letters carry a five-year warranty.</p>
                </details>
                <details>
                    <summary>How long will it take to get my sign?</summary>
                    <p>Every sign is made to order. Your estimated ship date is shown at checkout, and after manufacturing you can choose standard (3&ndash;6 business days), 3-day, 2-day or overnight shipping.</p>
                </details>
                <details>
                    <summary>Is my sign ready to install when it arrives?</summary>
                    <p>Yes. Every sign is tested before shipment and includes a wiring diagram and an installation pattern for your installer.</p>
                </details>
                <details>
                    <summary>Can I pick up my order?</summary>
                    <p>Pickup isn&rsquo;t available &mdash; every order ships directly to you.</p>
                </details>
            </div>
        </div>
    </section>

    <nav class="cl-mobile-bar" aria-label="Quick actions">
        <a class="cl-mobile-bar-call" href="tel:+18664362101"><?php echo wholesale_home_icon('phone'); ?> Call Us</a>
        <a class="cl-mobile-bar-shop" href="#product-box-container">See Prices</a>
    </nav>
<?php endif; ?>

<?php

get_footer();
