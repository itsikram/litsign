<?php


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
$product_discount_percent = intVal(get_post_meta(get_the_ID(), '_discount_percent', true));

$product_group_json = get_post_meta(get_the_ID(), '_product_group_data', true);
$product_group_array = json_decode($product_group_json, true);

$product_has_group = $product_group_array[0]['slug'] == 'null' ? false : true;
$product_attr_json = get_post_meta(get_the_ID(), 'product_attr', true);
$product_attr_array = json_decode($product_attr_json);

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

function get_discount_amount($totalPrice)
{
    global $product_discount_percent;
    $discountPercent = $product_discount_percent;
    return $totalPrice - (($totalPrice * $discountPercent) / 100);
}

function get_saving($totalPrice)
{
    global $product_discount_percent;

    $discountPercent = $product_discount_percent;
    return (($totalPrice * $discountPercent) / 100);
}


$design_url = null;
$design_data_array = [];
$design_data_str = '';
$design_id  = null;

if (isset($_REQUEST['save_design']) && $_REQUEST['design_id'] && $_REQUEST['design_data']) {

    if (isset($_SESSION['design_data_' . $product_id])) {
        $old_design_data = stripslashes($_SESSION['design_data_' . $product_id]);
        $old_design_data_array = json_decode($old_design_data, true);
        $old_design_id = $old_design_data_array['design_id'];
        wp_delete_attachment($old_design_id, true);
        $_SESSION['design_data_' . $product_id] = '';
    }

    $design_id = $_REQUEST['design_id'];
    $design_url = wp_get_attachment_url($design_id);
    $design_data_query = $_REQUEST['design_data'];
    update_post_meta($design_id, '_cl_data', stripslashes($design_data_query));

    $design_data_array = json_decode(stripslashes($design_data_query), true);
    $extras = $design_data_array['extras'];
    $elements = $design_data_array['elements'];
    $product_cost = 0;

    for ($i = 0; count($elements) > $i; $i++) {
        $dimenstion_cost =  $elements[$i]['cost'];
        $face_cost = $elements[$i]['colorCost'];
        $product_cost = ($dimenstion_cost + $face_cost) + $product_cost;
    }

    $design_data_array['design_url'] = $design_url;
    $design_data_array['design_id'] = $design_id;

    $extras_cost = 0;

    $extras_cost +=  $extras['powerSupply']['cost'];
    $extra_lit_percent = floatval($extras['lit']['cost']);
    $extras_cost +=  ($product_cost * $extra_lit_percent) / 100;
    $extras_cost +=  $extras['cable']['cost'];

    $product_cost += $extras_cost;

    $design_data_array['total_cost'] = $product_cost;
    $design_data_array['product_id'] = $product_id;


    $_SESSION['design_data_' . $product_id] = stripslashes(json_encode($design_data_array));

    wp_redirect(get_permalink());
}

$has_cl_design = false;

if (isset($_SESSION['design_data_' . $product_id])) {
    $design_data_str = str_replace('\\', '', stripslashes($_SESSION['design_data_' . $product_id]));
    $design_data = stripslashes($_SESSION['design_data_' . $product_id]);
    $design_data_array = json_decode($design_data, true);
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

$product_gallery_images = get_post_meta(get_the_ID(), '_product_gallery', true);
get_header();

?>

<div class="container  product-details">
    <h2 class="fs-5 product-title my-3">
        <?php echo get_the_title(); ?>
    </h2>
    <form action="<?php echo site_url() . '/cart'; ?>" method="POST" enctype="multipart/form-data" class="needs-validation">
        <input type="hidden" name="product_id" value="<?php echo get_the_ID(); ?>">
        <div class="row">
            <div class="col-md-4">
                <div class="thumbnail-container">
                    <?php
                    the_post_thumbnail('', array('class' => 'single-product-thumbnail'));
                    ?>
                </div>
                <?php if ($product_gallery_images) : ?>
                    <div class="product-gallery-container my-3">

                        <?php foreach ($product_gallery_images as $image) {
                        ?>
                            <div data-image="<?php echo $image; ?>" class="gallery-image-item">

                                <img src="<?php echo $image; ?>" alt="">
                            </div>

                        <?php
                        } ?>
                    </div>

                <?php endif; ?>
                <?php if ($product_category_slug != 'channel-letters') { ?>


                    <div class="product-price-per-sqft text-end py-2 text-secondary">
                        <!-- $<?php echo number_format($price_per_sqft, 2, '.', ','); ?> <?php if ($product_slug != 'acrylic-prints' && $product_slug != 'canvas-wrap') : ?> per ft<sup>2</sup> <?php endif; ?> -->

                        <?php echo $starting_at_text; ?>
                    </div>

                <?php }; ?>
                <div class="prodcut-short-desc-container my-3">
                    <?php echo $short_desc; ?>
                </div>
            </div>
            <div class="col-md-8">

                <?php if ($product_category_slug == 'channel-letters') {
                ?>
                    <div class="product-attibute-box mb-3">
                        <input type="hidden" name="design_id" value="<?php echo $design_id ? $design_id : ''; ?>">
                        <input type="hidden" name="turnaround_cost" value="0" id="turnaroundCost">
                        <input type="hidden" name="discount_percent" value="<?php echo $product_discount_percent; ?>" id="discountPercent">

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
                                            Start From Scratch
                                            <br />
                                            <small> (Optional - if you don’t have a file)</small>
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
                            <input type="hidden" name="total_cost" value="<?php $product_min_price > 0 ? $product_min_price : $product_price; ?>" id="totalCost">
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
                                                                <img src="<?php echo $info_content_face_image; ?>" alt="" class="option-info-img">
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
                                                                <img src="<?php echo $info_content_trimcap_image; ?>" alt="" class="option-info-img">
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
                                                                <img src="<?php echo $info_content_return_image; ?>" alt="" class="option-info-img">
                                                            </div>
                                                        </div>
                                                    </span>
                                                <?php }; ?>
                                            </div>
                                            <div class="col-8 col-md-9">
                                                <div class="row">
                                                    <div class="col">

                                                        <?php if (count($single_attr->options) !== 1) { ?>

                                                            <div id="custom-select-<?php echo $attr_name; ?>" data-type="<?php echo $single_attr->type; ?>" class="custom-select-container <?php echo $single_attr->cssClass; ?>">
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

                                                                            <li data-value="<?php echo $price; ?>" data-text="opt one<?php echo $name; ?>">
                                                                                <span class="color-sample" style="background-image: url(<?php echo $bgImg; ?>); background-color: <?php echo $bgColor; ?>"></span>
                                                                                <span><?php echo $name;; ?></span>
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
                                                                    <input type="hidden" name="<?php echo str_replace(' ', '', $attr_name); ?>" value="<?php echo $name; ?>">
                                                                    <span data-value="<?php echo $price ?>" class="<?php echo $attr_name  . '-option-name'; ?>"><?php echo $name; ?></span>
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
                                            <label for="select-<?php echo $attr_name; ?>" class="text-capitalize"><?php echo $attr_name == 'height' ? 'Size - ' : ''; ?><?php echo str_replace('-', ' ', $attr_name); ?></label>
                                        </div>
                                        <div class="col-8 col-md-9">
                                            <div class="row">
                                                <div class="col">
                                                    <?php if (count($single_attr->options) !== 1) { ?>
                                                        <select data-type="<?php echo $single_attr->type; ?>" name="<?php echo $attr_name; ?>" id="select-<?php echo $attr_name; ?>" data-cCost="0" class="form-select dynamic-select <?php echo $attr_name  != 'height' ? 'dynamic-price' : ''; ?> <?php echo $single_attr->cssClass; ?>">
                                                            <?php foreach ($single_attr->options as $option) {
                                                                foreach ($option as $name => $price) {
                                                            ?>
                                                                    <option <?php if ($attr_name  == 'height') { ?> data-size="<?php echo intval($name); ?>" <?php }; ?> value="<?php echo $price; ?>"><?php echo $name; ?></option>
                                                            <?php  }
                                                            }; ?>
                                                        </select>

                                                    <?php } else {
                                                    ?>

                                                        <?php foreach ($single_attr->options as $option) {
                                                            foreach ($option as $name => $price) {
                                                        ?>
                                                                <input type="hidden" id="select-<?php echo str_replace('-', ' ', $attr_name); ?>" name="<?php echo str_replace(' ', '-', $attr_name); ?>" value="<?php echo $name; ?>">
                                                                <span data-value="<?php echo $price ?>" class="<?php echo $attr_name  . '-option-name'; ?>"><?php echo $name; ?></span>
                                                        <?php  }
                                                        }; ?>
                                                    <?php
                                                    }
                                                    if ($attr_name == 'height') { ?>
                                                        <span id="hiddenText" style="visibility: hidden; position: absolute; white-space: nowrap; font-size: 16px; font-family: Arial;"></span>
                                                        <span id="widthDisplay" class="form-text">
                                                            Aproximate Width: <b>0 Inch</b>
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

                        <input type="hidden" name="min_sqft" value="<?php echo $product_min_sqft; ?>" id="minSqft">
                        <input type="hidden" name="total_sqft" value="<?php echo $product_min_sqft; ?>" id="totalSqft">
                        <input type="hidden" name="turnaround_cost" value="0" id="turnaroundCost">

                        <!-- Product Gruop  -->

                        <?php

                        if ($product_has_group) {
                        ?>


                            <div class="row d-fex align-items-center">

                                <div class="product-group-container">

                                    <?php

                                    foreach ($product_group_array as $product_group) {
                                        $group_item_slug = $product_group['slug'];
                                        $group_item_title = $product_group['title'];
                                        if ($group_item_slug == true) {
                                            $group_product = get_page_by_path($group_item_slug, OBJECT, 'product');
                                            if ($group_product) {
                                                $group_product_id = $group_product->ID;
                                            }
                                        } else {
                                            $group_product_id = get_the_ID();
                                        }

                                        $group_product_permalink = get_permalink($group_product_id);
                                        $is_current_product = $group_product_id == $product_id ? 'current' : '';
                                        if ($is_current_product == 'current') {
                                            $group_product_permalink = 'javascript:void(0)';
                                        }
                                    ?>

                                        <div class="product-group-item <?php echo $is_current_product; ?>">
                                            <a href="<?php echo $group_product_permalink; ?>" class="product-group-item-link"><?php echo $group_item_title; ?></a>
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

                            <div class="row d-fex align-items-center dimenstion-calculator">
                                <div class="col-3">
                                    <label>Height</label>
                                </div>
                                <div class="col-9">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">ft</div>
                                                </div>
                                                <input placeholder="Ft" value="<?php echo round(sqrt($product_min_sqft), 2); ?>" name="height-ft" min="<?php echo $product_min_height; ?>" max="<?php echo $product_max_height; ?>" step="0.1" type="number" id="input-height-ft" class="form-control text-right">

                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">in</div>
                                                </div>
                                                <input type="number" step="0.1" name="height-in" value="0" max="12" placeholder="In" id="input-height-in" class="form-control text-right" />

                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="row d-fex align-items-center dimenstion-calculator">
                                <div class="col-3">
                                    <label>Width</label>
                                </div>
                                <div class="col-9 mt-2">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">ft</div>
                                                </div>
                                                <input placeholder="Ft" step="0.1" min="<?php echo $product_min_width; ?>" value="<?php echo round(sqrt($product_min_sqft), 2); ?>" max="<?php echo $product_max_width; ?>" name="width-ft" type="number" id="input-width-ft" class="form-control text-right">


                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">in</div>
                                                </div>
                                                <input type="number" step="0.1" max="12" value="0" name="width-in" placeholder="In" id="input-width-in" class="form-control text-right">


                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if ($product_category_slug != 'channel-letters') { ?>

                                <div class="row mb-3">
                                    <div class="col-3">

                                    </div>
                                    <div class="col-9">
                                        <span class="total-size-sqft"><?php echo round(sqrt($product_min_sqft) * 12, 1); ?>" x <?php echo round(sqrt($product_min_sqft) * 12, 1); ?>" = <?php echo $product_min_sqft; ?> ft<sup>2</sup></span>

                                    </div>
                                </div>
                            <?php }; ?>




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
                                                            <img src="<?php echo $info_content_face_image; ?>" alt="" class="option-info-img">
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
                                                            <img src="<?php echo $info_content_trimcap_image; ?>" alt="" class="option-info-img">
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
                                                            <img src="<?php echo $info_content_return_image; ?>" alt="" class="option-info-img">
                                                        </div>
                                                    </div>
                                                </span>
                                            <?php }; ?>
                                        </div>
                                        <div class="col-8 col-md-9">
                                            <div class="row">
                                                <div class="col">

                                                    <?php if (count($single_attr->options) !== 1) { ?>

                                                        <div id="custom-select-<?php echo $attr_name; ?>" data-type="<?php echo $single_attr->type; ?>" class="custom-select-container <?php echo $single_attr->cssClass; ?>">
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

                                                                        <li data-value="<?php echo $price; ?>" data-text="opt one<?php echo $name; ?>">
                                                                            <span class="color-sample" style="background-image: url(<?php echo $bgImg; ?>); background-color: <?php echo $bgColor; ?>"></span>
                                                                            <span><?php echo $name;; ?></span>
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
                                                                <input type="hidden" name="<?php echo strtolower(str_replace(' ', '', $attr_name)); ?>" value="<?php echo $name; ?>">
                                                                <span data-value="<?php echo $price ?>" class="<?php echo $attr_name  . '-option-name'; ?>"><?php echo $name; ?></span>
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
                                        <label for="select-<?php echo $attr_name; ?>" class="text-capitalize"><?php echo $attr_name == 'height' ? 'Size - ' : ''; ?><?php echo str_replace('-', ' ', $attr_name); ?></label>
                                    </div>
                                    <div class="col-8 col-md-9">
                                        <div class="row">
                                            <div class="col">
                                                <?php if (count($single_attr->options) !== 1) { ?>
                                                    <select data-type="<?php echo $single_attr->heading ? $single_attr->heading : ''; ?>" name="<?php echo strtolower($attr_name); ?>" id="select-<?php echo $attr_name; ?>" data-cCost="0" class="form-select dynamic-select <?php echo $attr_name  != 'height' ? 'dynamic-price' : ''; ?> <?php echo $single_attr->cssClass; ?>">
                                                        <?php foreach ($single_attr->options as $option) {
                                                            foreach ($option as $name => $price) {
                                                        ?>
                                                                <option <?php if ($attr_name  == 'height') { ?> data-size="<?php echo intval($name); ?>" <?php }; ?> value="<?php echo $price; ?>"><?php echo $name; ?></option>
                                                        <?php  }
                                                        }; ?>
                                                    </select>

                                                <?php } else {
                                                ?>

                                                    <?php foreach ($single_attr->options as $option) {
                                                        foreach ($option as $name => $price) {
                                                    ?>
                                                            <input type="hidden" id="select-<?php echo str_replace('-', ' ', $attr_name); ?>" name="<?php echo strtolower(str_replace(' ', '-', $attr_name)); ?>" value="<?php echo $name; ?>">
                                                            <span data-value="<?php echo $price ?>" class="<?php echo $attr_name  . '-option-name'; ?>"><?php echo $name; ?></span>
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
                                <label for="select-font" class="text-capitalize">Upload Artwork</label>

                            </div>
                            <div class="col-8 col-md-9">
                                <div class="row">
                                    <div class="col">
                                        <input class="form-control" name="custom-artwork" type="file" id="artwork">
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

                        <div class="row d-fex align-items-center  my-2 mt-3">
                            <div class="col-md-3 col-4">
                                <label for="turnaroundNextDay" class="text-capitalize"> Turnaround</label>
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
                                    date_default_timezone_set('America/Los_Angeles');

                                    // Get the current time
                                    $currentTime = new DateTime();

                                    // Check if the current time is before noon (12 PM)
                                    if ($currentTime->format('H') < 12) { ?>
                                        <div class="col-12 col-md-6">
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
                                    <?php }; ?>

                                </div>
                            </div>

                        </div>

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


                <div class="product-pricing-box">
                    <div class="container">
                        <div class="row subtotal-container">
                            <div class="col-6">
                                <span class="fs-6">Total <?php echo $product_discount_percent > 0 ? '' : '' ?></span>
                            </div>
                            <div class="col-6 price-subtotal-container">
                                <span class="fs-6 d-block "><span>$</span><span class="price-subtotal"><?php


                                                                                                        if ($product_category_slug == 'channel-letters') {
                                                                                                            if ($has_cl_design) {
                                                                                                                echo format_price($product_min_price > 0 ? $product_min_price : $product_price);
                                                                                                            } else {
                                                                                                                echo '0';
                                                                                                            }
                                                                                                        } else {
                                                                                                            echo format_price($product_min_price > 0 ? $product_min_price : $product_price);
                                                                                                        }

                                                                                                        ?>
                                    </span></span>
                            </div>
                        </div>
                        <?php if ($product_discount_percent) { ?>
                            <div class="row totalSaving-container" style="color: gray">
                                <div class="col-6">
                                    <span class="fs-6"><?php echo $product_discount_percent ?>% discount</span>
                                </div>
                                <div class="col-6 price-subtotal-container">
                                    <span class="fs-6 d-block "><span>-$</span><span class="price-saving"><?php echo format_price(get_saving($product_min_price > 0 ? $product_min_price : $product_price)); ?></span></span>
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
                                                                                                    } else {
                                                                                                        echo format_price(get_discount_amount($product_min_price > 0 ? $product_min_price : $product_price));
                                                                                                    }


                                                                                                    ?>
                                    </span></span>
                            </div>
                        </div>

                        <?php if (is_user_logged_in()) { ?>
                            <div class="row d-flex align-items-end">
                                <div class="col-4 col-md-2">
                                    <div class="form-group">
                                        <label for="productQuantity" class="form-label">Quantity</label>
                                        <input type="number" name="product_quantity" data-current-qty="1" value="1" id="productQuantity" class="form-control ">
                                    </div>
                                </div>
                                <div class="col-8 col-md-10 text-center">
                                    <!-- <a href="#" id="payNowBtn" class="btn btn-success">Order Now</a> -->
                                    <button id="addToCartBtn" type="submit" class="btn btn-danger d-block w-100 fw-bold">Add To Cart</button>
                                </div>
                            </div>

                        <?php } else {
                        ?>
                            <!-- <div class="row d-flex align-items-end">
                                <div class="col text-center">
                                    <a href="<?php echo site_url() . '/login?redirect_ulr=' . get_permalink(); ?>" class="btn btn-primary">Login to Order This Product</a>
                                </div>
                            </div> -->
                            <div class="row d-flex align-items-end">
                                <div class="col-4 col-md-2">
                                    <div class="form-group">
                                        <label for="productQuantity" class="form-label">Quantity</label>
                                        <input type="number" name="product_quantity" data-current-qty="1" value="1" id="productQuantity" class="form-control ">
                                    </div>
                                </div>
                                <div class="col-8 col-md-10 text-center">
                                    <!-- <a href="#" id="payNowBtn" class="btn btn-success">Order Now</a> -->
                                    <button id="addToCartBtn" type="submit" class="btn btn-danger d-block w-100 fw-bold">Add To Cart</button>
                                </div>
                            </div>


                        <?php
                        } ?>

                    </div>
                </div>
            </div>
        </div>

        <!-- product description -->
        <div class="row mt-4 overflow-hidden">
            <div class="col">
                <div class="product-main-desc">
                    <h3 class="fs-3 mb-4">Product Description</h3>

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

                                <?php echo get_post_meta(get_the_ID(), '_product_description', true); ?>
                            </div>

                            <!-- Components -->
                            <div class="tab-pane fade" id="component" role="tabpanel" aria-labelledby="component-tab">
                                <?php echo get_post_meta(get_the_ID(), '_product_component', true); ?>
                            </div>
                            <!-- Warrenty -->
                            <div class="tab-pane fade" id="warrenty" role="tabpanel" aria-labelledby="warrenty-tab">
                                <?php echo get_post_meta(get_the_ID(), '_product_warrenty', true); ?>

                            </div>
                            <!-- FAQ -->
                            <div class="tab-pane fade" id="faq" role="tabpanel" aria-labelledby="faq-tab">
                                <?php echo get_post_meta(get_the_ID(), '_product_faq', true); ?>

                            </div>
                            <!-- Manual -->
                            <!-- <div class="tab-pane fade" id="manual" role="tabpanel" aria-labelledby="faq-tab">
                            <?php // echo get_post_meta(get_the_ID(),'_product_manual',true); 
                            ?>

                            </div> -->
                        </div>



                    <?php
                    } else {
                        echo get_the_content();
                    }

                    ?>
                    <?php  ?>
                </div>
            </div>
        </div>
    </form>
</div>

<?php

get_footer();
