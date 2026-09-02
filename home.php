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

$current_category = isset($_REQUEST['category_slug']) ? $_REQUEST['category_slug'] : 'channel-letters';
$is_default_category = isset($_REQUEST['category_slug']) ? false : true;
$current_term = get_term_by('slug', $current_category, 'product_category');

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
                                <a href="<?php echo get_permalink() . '?category_slug=' . $child_slug; ?>" class="d-flex justify-content-between">
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
                <span class="category-filter-toggler text-center">
                    <i class="fa-solid fa-bars"></i>
                </span>
                <?php
                if (has_nav_menu('category-filter-menu')) {
                    wp_nav_menu(array(
                        'theme_location' => 'category-filter-menu',
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

            <?php if ($current_term): ?>
                <h1 class="text-center fs-2"><?php print_r($current_term->name); ?></h1>
                <p class="text-center fs-5 mb-0"><?php print_r($current_term->description); ?></p>
            <?php endif; ?>
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
                    while ($product_query->have_posts()) {
                        $product_query->the_post();

                        $short_desc = get_post_meta(get_the_ID(), "_product_list_desc", true);
                        $price_per_sqft = get_post_meta(get_the_ID(), "_price_per_sqft", true);
                        $starting_at_text = get_post_meta(get_the_ID(), "_starting_at_text", true);
                        $starting_at_options = get_post_meta(get_the_ID(), "_starting_at_options", true);
                        $terms = get_the_terms(get_the_ID(), 'product_category');
                        $product_category_slug = isset($terms[0]) ? $terms[0]->slug : '';
                        $product_thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'full');
                        $product_slug = get_post_field('post_name', get_the_ID(), 'raw');


                ?>
                        <div class="product-box <?php echo $product_slug; ?>" data-product-category="<?php echo $product_category_slug; ?>">
                            <a href="<?php echo get_permalink(); ?>">
                                <div class="pb-image-top">
                                    <img src="<?php echo $product_thumbnail; ?>" alt="">
                                </div>
                                <div class="pb-details">
                                    <h4 class="pb-title fs-6 text-truncate" title="<?php echo get_the_title(); ?>">
                                        <?php echo get_the_title(); ?>
                                    </h4>
                                    <div class="pb-description-list">
                                        <?php echo $short_desc; ?>
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
                        <a href="<?php echo home_url() . '/?category_slug=' . $ref_term->slug; ?>">
                            <div class="pb-image-top">
                                <img src="<?php echo  $ref_term_image; ?>" alt="">
                            </div>
                            <div class="pb-details">
                                <h4 class="pb-title fs-6 text-truncate" title="AV12 – Dry Erase Adhesive Vinyl">
                                    <?php echo $ref_term_title; ?></h4>
                                <div class="pb-description-list">
                                    <?php echo $ref_term_desc; ?></h4>

                                </div>
                                <hr>
                                <p class="pb-pricing d-flex justify-content-between">
                                    <span class="pb-title-short fs-6">

                                        <a href="<?php echo home_url() . '/?category_slug=' . $ref_term->slug; ?>">See All</a>

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
                        $product_thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'full');
                        $product_slug = get_post_field('post_name', get_the_ID(), 'raw');
                ?>
                        <div class="product-box <?php echo $product_slug; ?>" data-product-category="<?php echo $product_category_slug; ?>">
                            <a href="<?php echo get_permalink(); ?>">
                                <div class="pb-image-top">
                                    <img src="<?php echo $product_thumbnail; ?>" alt="">
                                </div>
                                <div class="pb-details">
                                    <h4 class="pb-title fs-6 text-truncate" title="<?php echo get_the_title(); ?>">
                                        <?php echo get_the_title(); ?>
                                    </h4>
                                    <div class="pb-description-list">
                                        <?php echo $short_desc; ?>
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


<?php

get_footer();
