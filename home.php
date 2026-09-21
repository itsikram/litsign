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

$requested_category = get_query_var('category_slug');
$requested_category = $requested_category ? $requested_category : (isset($_REQUEST['category_slug']) ? wp_unslash($_REQUEST['category_slug']) : '');
$current_category = $requested_category ? sanitize_title($requested_category) : 'channel-letters';
$is_default_category = !$requested_category;
$current_term = get_term_by('slug', $current_category, 'product_category');
$is_channel_letters = 'channel-letters' === $current_category;
$contact_page = get_page_by_path('contact');
$contact_url = $contact_page ? get_permalink($contact_page) : home_url('/contact/');

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

<section class="home-hero" aria-labelledby="home-hero-title">
    <div class="home-hero-content">
        <div class="container home-hero-inner">
            <div class="home-hero-copy">
                <p class="home-hero-eyebrow">Premium quality signs &amp; letters</p>
                <h1 id="home-hero-title">Make Your Brand<br><span>Stand Out</span></h1>
                <p class="home-hero-lead">Custom channel letters, LED signs, acrylic signs and more.<br class="home-hero-desktop-break"> Built for businesses that want to be seen.</p>
                <div class="home-hero-actions">
                    <a class="home-hero-shop-button" href="#product-box-container">Shop Now <span aria-hidden="true">&rarr;</span></a>
                    <a class="home-hero-video-link" href="#product-box-container">
                        <span class="home-hero-play" aria-hidden="true"><i class="fa-solid fa-play"></i></span>
                        <span>Watch Video</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="home-hero-features" aria-label="Our service benefits">
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
    </div>
</section>

<div class="container">
    <header class="shop-header">
        <h1 class="text-center fs-2"><?php echo esc_html($is_channel_letters ? __('Custom Channel Letter Signs', 'litsign') : ($current_term ? $current_term->name : __('Custom Signs', 'litsign'))); ?></h1>
        <p class="text-center fs-5 mb-0"><?php echo esc_html($is_channel_letters ? __('Create a stronger storefront presence with custom LED channel letters made for retail businesses.', 'litsign') : ($current_term ? $current_term->description : __('Shop custom signage designed and built for your business.', 'litsign'))); ?></p>
        <?php if ($is_channel_letters) : ?>
            <p class="text-center mt-3"><a class="btn btn-primary" href="<?php echo esc_url($contact_url); ?>">Request a Channel Letter Sign Quote</a></p>
        <?php endif; ?>
    </header>
</div>

<div class="container my-3">

    <div class="row">
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
        <div class="col-md-10">

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
                            <a href="<?php echo get_permalink(); ?>">
                                <div class="pb-image-top">
                                    <?php echo wp_get_attachment_image($product_thumbnail_id, 'product-card', false, array(
                                        'alt' => esc_attr($is_channel_letters ? 'Custom channel letter sign: ' . get_the_title() : get_the_title()),
                                        'loading' => $is_first_product ? 'eager' : 'lazy',
                                        'fetchpriority' => $is_first_product ? 'high' : 'auto',
                                        'decoding' => 'async',
                                        'sizes' => '(max-width: 767px) 290px, 290px',
                                    )); ?>
                                </div>
                                <?php $is_first_product = false; ?>
                                <div class="pb-details">
                                    <h2 class="pb-title fs-6 text-truncate" title="<?php echo esc_attr(get_the_title()); ?>">
                                        <?php echo esc_html(get_the_title()); ?>
                                    </h2>
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

                                </div>
                            </a>

                        </div>
                    <?php
                    }
                } else { ?>
                    <p class="text-muted text-center w-100">No Product Found</p> <?php
                                                                                } ?>

                <?php if ($ref_term == true): ?>
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

                <?php
                // Adhesive products
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
                            'terms' => 'adhesive-products',
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

                if ($is_default_category):

                    while ($product_query->have_posts()) {
                        $product_query->the_post();
                        global $post;

                        $short_desc = get_post_meta(get_the_ID(), "_product_list_desc", true);
                        $price_per_sqft = get_post_meta(get_the_ID(), "_price_per_sqft", true);
                        $starting_at_text = get_post_meta(get_the_ID(), "_starting_at_text", true);
                        $terms = get_the_terms(get_the_ID(), 'product_category');

                        $product_category_slug = isset($terms[0]) ? $terms[0]->slug : '';
                        $product_thumbnail_id = get_post_thumbnail_id(get_the_ID());
                        $product_slug = get_post_field('post_name', get_the_ID(), 'raw');
                ?>
                        <div class="product-box <?php echo $product_slug; ?>" data-product-category="<?php echo $product_category_slug; ?>">
                            <a href="<?php echo get_permalink(); ?>">
                                <div class="pb-image-top">
                                    <?php echo wp_get_attachment_image($product_thumbnail_id, 'product-card', false, array(
                                        'alt' => esc_attr(get_the_title()),
                                        'loading' => 'lazy',
                                        'decoding' => 'async',
                                        'sizes' => '(max-width: 767px) 290px, 290px',
                                    )); ?>
                                </div>
                                <div class="pb-details">
                                    <h2 class="pb-title fs-6 text-truncate" title="<?php echo esc_attr(get_the_title()); ?>">
                                        <?php echo esc_html(get_the_title()); ?>
                                    </h2>
                                    <div class="pb-description-list">
                                        <?php echo wp_kses_post($short_desc); ?>
                                    </div>
                                    <hr>
                                    <p class="pb-pricing d-flex justify-content-between">
                                        <span class="pb-title-short fs-6">
                                            Starting at
                                        </span>
                                        <span class="pb-price">
                                            <?php echo $starting_at_text; ?>
                                        </span>
                                    </p>
                                </div>
                            </a>

                        </div>


                <?php
                    }
                endif; ?>

            </div>

        </div>
    </div>
</div>

<?php if ($is_channel_letters) : ?>
    <section class="channel-letters-seo-content" aria-labelledby="channel-letters-overview">
        <div class="channel-letters-seo-intro">
            <p class="channel-letters-seo-kicker">Designed for storefront visibility</p>
            <h2 id="channel-letters-overview">Make Your Storefront Stand Out Day and Night</h2>
            <p>Custom channel letter signs give retail businesses a polished identity with bright, energy-efficient LED illumination and a clean, dimensional finish.</p>
            <a class="channel-letters-seo-cta" href="<?php echo esc_url($contact_url); ?>">Talk to a Sign Specialist <span aria-hidden="true">&rarr;</span></a>
        </div>
        <div class="channel-letters-seo-features">
            <article>
                <span class="channel-letters-seo-icon" aria-hidden="true">&#9733;</span>
                <h3>Choose Your Look</h3>
                <p>Front-lit, reverse-lit halo, and front-and-back lit options help you create the right storefront presence.</p>
            </article>
            <article>
                <span class="channel-letters-seo-icon" aria-hidden="true">&#9670;</span>
                <h3>Built to Last</h3>
                <p>Stainless steel faces and returns, clear Lexan backs, and high-efficiency LED modules deliver lasting quality.</p>
            </article>
            <article>
                <span class="channel-letters-seo-icon" aria-hidden="true">&#10003;</span>
                <h3>Ready for Installation</h3>
                <p>Every sign is tested before shipment and includes wiring diagrams and an installation pattern.</p>
            </article>
        </div>
        <div class="channel-letters-seo-details">
            <h2>Everything You Need to Plan Your Sign</h2>
            <div class="channel-letters-seo-accordion">
                <details>
                    <summary>Materials, LED illumination, and warranty</summary>
                    <p>Outdoor channel letter signs are UL listed with sign section labels. Listed LED modules and qualifying letters carry a five-year warranty.</p>
                </details>
                <details>
                    <summary>Which channel letter style is right for my storefront?</summary>
                    <p>Front-lit letters provide bold direct illumination, while reverse-lit letters create a halo effect. Front-and-back lit combinations offer both looks.</p>
                </details>
                <details>
                    <summary>How do I start a custom channel letter project?</summary>
                    <p>Share your storefront details through our quote request and our team will help you choose the sign configuration and next steps.</p>
                </details>
            </div>
        </div>
    </section>

<?php endif; ?>


<?php

get_footer();
