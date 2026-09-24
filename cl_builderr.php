<?php

// Template Name: Channel Letter builder 


// if(!is_user_logged_in()) {
//     wp_redirect(site_url().'/login?redirect_ulr='.get_permalink());
// }


$channel_letter_products = new WP_Query(array(
    'post_type' => 'product',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'order' => 'ASC',
    'meta_key' => '_order_by_index',
    'orderby' => 'meta_value_num',
    'tax_query' => array(
        array(
            'taxonomy' => 'product_category',
            'field' => 'slug',
            'terms' => 'channel-letters',
        ),
    ),
    'meta_query' => array(
        array(
            'key' => '_show_in_list',
            'value' => 'on',
            'compare' => '=',
        ),
    ),
));

$product_id = isset($_REQUEST['product_id']) ? absint($_REQUEST['product_id']) : 0;
if (!$product_id && !empty($channel_letter_products->posts)) {
    $product_id = (int) $channel_letter_products->posts[0]->ID;
}

$ticket_builder = null;
if ($product_id > 0) {
    $product_title = get_the_title($product_id);
    $product_cl_data = get_post_meta($product_id, 'product_cl_data', true);

    $edit_product_data = null;
    $is_lit_option = get_post_meta($product_id, '_is_lit_option', true) ? get_post_meta($product_id, '_is_lit_option', true) : 0;
    $is_ps_option = get_post_meta($product_id, '_is_ps_option', true) ? get_post_meta($product_id, '_is_ps_option', true) : 1;
    $is_cable_option = get_post_meta($product_id, '_is_cable_option', true) ? get_post_meta($product_id, '_is_cable_option', true) : 1;
    $standard_ps_cost = get_post_meta($product_id, '_standard_ps_cost', true) ? get_post_meta($product_id, '_standard_ps_cost', true) : 0;
    $backlit_cost = get_post_meta($product_id, '_backlit_cost', true) ? get_post_meta($product_id, '_backlit_cost', true) : 0;
    $eight_ft_cable_cost = get_post_meta($product_id, '_eight_ft_cable_cost', true) ? get_post_meta($product_id, '_eight_ft_cable_cost', true) : 0;

    $has_trimcap = get_post_meta($product_id, '_has_trimcap', true) ? get_post_meta($product_id, '_has_trimcap', true) : 0;
    $has_return = get_post_meta($product_id, '_has_return', true) ? get_post_meta($product_id, '_has_return', true) : 0;
    $has_face = get_post_meta($product_id, '_has_face', true) ? get_post_meta($product_id, '_has_face', true) : 0;

    $default_face = get_post_meta($product_id, '_default_face', true) ? get_post_meta($product_id, '_default_face', true) : 0;
    $default_return = get_post_meta($product_id, '_default_return', true) ? get_post_meta($product_id, '_default_return', true) : 0;
    $default_trimcap = get_post_meta($product_id, '_default_trimcap', true) ? get_post_meta($product_id, '_default_trimcap', true) : 0;
    $default_color_cost = get_post_meta($product_id, '_default_color_cost', true) ? get_post_meta($product_id, '_default_color_cost', true) : 0;

    $default_data_json = json_encode(array(
        'trimcap' => $default_trimcap,
        'face' => $default_face,
        'return' => $default_return,
        'color_cost' => $default_color_cost
    ));


    // Admin designing for a payment ticket line (wp-admin → Payment Tickets).
    $ticket_builder = function_exists('wholesale_ticket_builder_context') ? wholesale_ticket_builder_context($product_id) : null;

    if ($ticket_builder) {
        $edit_product_data = $ticket_builder['design_data'];
    } else {
        if (isset($_REQUEST['edit_design'])) {
            if (!isset($_SESSION['design_data_' . $product_id])) {
                wp_redirect(get_permalink($product_id));
            }
        }
        if (isset($_SESSION['design_data_' . $product_id])) {
            $product_data = $_SESSION['design_data_' . $product_id];
            $edit_product_data = stripslashes($_SESSION['design_data_' . $product_id]);
            $product_data_array = json_decode($edit_product_data, true);
        }
    }
} else {
    wp_redirect(home_url());
}


get_header();

?>

<input type="hidden" id="faceColorPicker" value="#ffffff">
<input type="hidden" id="productPermalink" value="<?php echo esc_url($ticket_builder ? $ticket_builder['save_url'] : get_permalink($product_id)); ?>">

<?php if ($ticket_builder) : ?>
    <div class="wpt-builder-bar" role="status">
        <span><strong>Payment ticket <?php echo esc_html($ticket_builder['ticket']['number']); ?></strong> · line <?php echo esc_html((string) $ticket_builder['line_number']); ?> · <?php echo esc_html($ticket_builder['ticket']['customer_name'] ?: $ticket_builder['ticket']['title']); ?></span>
        <span>Design the letters, then click <strong>Save design to ticket</strong>.</span>
        <a href="<?php echo esc_url($ticket_builder['back_url']); ?>">Back to ticket without saving</a>
    </div>
    <style>
        .wpt-builder-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 18px; padding: 10px 16px; background: #14202c; color: #fff; font-size: 14px; }
        .wpt-builder-bar a { margin-left: auto; color: #8fd6f5; font-weight: 600; }
    </style>
<?php endif; ?>


<input type="hidden" name="hasTrimcap" value="<?php echo $has_trimcap; ?>" id="hasTrimcap">
<input type="hidden" name="hasReturn" value="<?php echo $has_return; ?>" id="hasReturn">
<input type="hidden" name="hasFace" value="<?php echo $has_face; ?>" id="hasFace">
<input type="hidden" name="defaultColorData" value='<?php echo $default_data_json; ?>' id="defaultColorData">

<input type="hidden" id="trimcapColorPicker" value="#000000">
<input type="hidden" id="trimcapSizeInput" value="2">

<input type="hidden" id="returnColorPicker" value="#000000">

<input type="hidden" id="returnSizeInput" value="3" min="0" step="1">
<input type="hidden" name="product_cl_data" value='<?php echo $product_cl_data; ?>' id="productClData">
<input type="hidden" name="edit_design_data" value="<?php echo esc_attr((string) $edit_product_data); ?>" id="editDesignData">


<input type="hidden" id="standarPsCost" value="<?php echo $standard_ps_cost ? $standard_ps_cost : 90; ?>">
<input type="hidden" id="backLitCost" value="<?php echo $backlit_cost ? $backlit_cost : 100; ?>">
<input type="hidden" id="eightFtcableCost" value="<?php echo $eight_ft_cable_cost ? $eight_ft_cable_cost : 70; ?>">

<!-- <button id="addPatternButton">Add Pattern</button> -->

<div class="dt-container" id="dtContainer">
    <aside class="left-sidebar" id="leftSidebar" aria-label="Selected element settings">
        <div id="leftSidebarSlider" class="left-sidebar-slider" role="dialog" aria-label="Choose an option">
            <button id="sliderCloseBtn" type="button" aria-label="Close">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
            <div class="left-slider-container">

            </div>

        </div>
        <div class="left-sidebar-container">
            <ul class="sidebar-item-container">
                <li class="sidebar-item item-preview">
                    <div class="cl-panel-heading">
                        <label class="cl-panel-title">Selected element</label>
                        <span class="element-dimenstion-container"><span class="element-index">#0</span> <span class="element-dimenstion"> H:0 x W:0</span></span>
                    </div>
                    <div id="previewContainer"></div>
                    <p class="cl-panel-hint">Tap a letter or shape on the canvas to edit it. Drag to move, use the handles to resize.</p>
                </li>
                <li class="sidebar-item item-textInput">
                    <label for="textInput" class="cl-field-label">Letters</label>
                    <input type="text" id="textInput" class="form-control" placeholder="Type your sign text" autocomplete="off">
                </li>
                <li class="item-font sidebar-item cl-option-row">
                    <div class="cl-option-label">Font</div>
                    <div class="select-font cl-option-value" role="button" tabindex="0" aria-label="Choose font"
                        data-current-font="Arial" data-active="Arial/Arial">
                        <span class="name">
                            Arial
                        </span>
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </div>
                </li>
                <li class="item-face sidebar-item cl-option-row">
                    <div class="cl-option-label">
                        Face
                        <span class="info-btn-container">
                            <span class="info-btn" role="button" tabindex="0" aria-label="What is the face?">i</span>
                            <div class="info-btn-content">
                                <small class="text-muted">The face is the colored acrylic front of each letter that lights up.</small>
                                <img class="mt-2 w-100" src="<?php echo esc_url(get_template_directory_uri() . '/img/face-info.png'); ?>" alt="">
                            </div>
                        </span>
                    </div>
                    <div class="select-face cl-option-value" role="button" tabindex="0" aria-label="Choose face color"
                        data-current-face="White" data-active="White/rgb(255, 255, 255)">
                        <span class="name">
                            White
                        </span>
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </div>
                </li>
                <li class="item-return sidebar-item cl-option-row">
                    <div class="cl-option-label">
                        Return
                        <span class="info-btn-container">
                            <span class="info-btn" role="button" tabindex="0" aria-label="What is the return?">i</span>
                            <div class="info-btn-content">
                                <small class="text-muted">
                                    The return is the metal side wall of each channel letter.
                                </small>
                                <img class="mt-2 w-100" src="<?php echo esc_url(get_template_directory_uri() . '/img/return-info.png'); ?>" alt="">
                            </div>
                        </span>
                    </div>
                    <div class="select-return cl-option-value" role="button" tabindex="0" aria-label="Choose return color"
                        data-current-return="Black" data-active="Black/rgb(0, 0, 0)">
                        <span class="name">
                            Black
                        </span>
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </div>
                </li>
                <li class="item-trimcap sidebar-item cl-option-row">
                    <div class="cl-option-label">
                        Trimcap
                        <span class="info-btn-container">
                            <span class="info-btn" role="button" tabindex="0" aria-label="What is the trimcap?">i</span>
                            <div class="info-btn-content">
                                <small class="text-muted">
                                    Trimcap is a plastic molding that surrounds the acrylic channel letter face.
                                </small>
                                <img class="mt-2 w-100" src="<?php echo esc_url(get_template_directory_uri() . '/img/trimcap-info.png'); ?>" alt="">

                            </div>
                        </span>
                    </div>
                    <div class="select-trimcap cl-option-value" role="button" tabindex="0" aria-label="Choose trimcap color"
                        data-current-trimcap="Black" data-active="Black/rgb(0, 0, 0)">
                        <span class="name">
                            Black
                        </span>
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </div>
                </li>
                <li class="sidebar-item item-heightWidthInput">
                    <span class="cl-field-label">Size</span>
                    <div class="cl-size-grid">
                        <label for="sizeHeightInput" class="cl-size-field">
                            <span>Height</span>
                            <span class="cl-unit-input"><input type="number" max="45" step="0.1" min="8" id="sizeHeightInput" placeholder="0" inputmode="decimal" class="form-control"><em>in</em></span>
                        </label>
                        <label for="sizeWidthInput" class="cl-size-field">
                            <span>Width</span>
                            <span class="cl-unit-input"><input type="number" step="0.1" id="sizeWidthInput" placeholder="0" inputmode="decimal" class="form-control"><em>in</em></span>
                        </label>
                    </div>
                </li>

                <li id="cornerRadiusContainer" class="sidebar-item item-radius">
                    <label for="cornerRadius" class="cl-size-field">
                        <span>Corner radius</span>
                        <span class="cl-unit-input"><input type="number" max="5" value="0.8" step="0.1" min="0.8" id="cornerRadius" placeholder="0.8" inputmode="decimal" class="form-control"><em>in</em></span>
                    </label>
                </li>

            </ul>
        </div>
    </aside>
    <div class="cl-sheet-backdrop" id="clSheetBackdrop" aria-hidden="true"></div>
    <div class="editor-container" id="editorContainer">
        <div class="editor-topbar">
            <div class="middle">
                <div class="product-heading">
                    <span class="product-heading-label">Designing</span>
                    <h1 class="product-title text-truncate"><?php echo esc_html($product_title); ?></h1>
                    <button type="button" class="change-product-btn" data-bs-toggle="modal" data-bs-target="#changeProductModal">
                        <span>Change product</span>
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <div class="cl-toolbar">
                <div class="left" role="group" aria-label="Add to design">
                    <button id="addTextBtn" type="button" class="topbar-left-button">
                        <img width="20" height="20" src="<?php echo esc_url(get_template_directory_uri() . '/img/text-icon.png'); ?>" alt="">
                        <span>Text</span>
                    </button>
                    <button id="addRacewayButton" type="button" class="topbar-left-button">
                        <img width="20" height="20" src="<?php echo esc_url(get_template_directory_uri() . '/img/raceway-icon.png'); ?>" alt="">
                        <span>Raceway</span>
                    </button>

                    <div class="topbar-left-button shape-dropdown" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false">
                        <img width="20" height="20" src="<?php echo esc_url(get_template_directory_uri() . '/img/star-icon.png'); ?>" alt="">
                        <span>Shape</span>
                        <i class="fa-solid fa-chevron-down shape-arrow-icon" aria-hidden="true"></i>
                        <ul class="shapes-container" role="menu">
                            <li class="shape" data-shape="rectangle" role="menuitem" tabindex="-1">
                                <img width="20" height="20" src="<?php echo esc_url(get_template_directory_uri() . '/img/rect-icon.png'); ?>" alt="">
                                <span>Rectangle</span>
                            </li>
                            <li class="shape" data-shape="circle" role="menuitem" tabindex="-1">
                                <img width="20" height="20" src="<?php echo esc_url(get_template_directory_uri() . '/img/circle-icon.png'); ?>" alt="">
                                <span>Oval</span>
                            </li>
                            <li class="shape" data-shape="triangle" role="menuitem" tabindex="-1">
                                <img width="20" height="20" src="<?php echo esc_url(get_template_directory_uri() . '/img/triangle-icon.png'); ?>" alt="">
                                <span>Triangle</span>
                            </li>
                            <li class="shape" data-shape="arrow" role="menuitem" tabindex="-1">
                                <img width="20" height="20" src="<?php echo esc_url(get_template_directory_uri() . '/img/arrow-icon.png'); ?>" alt="">
                                <span>Arrow</span>
                            </li>
                            <li class="shape" data-shape="star" role="menuitem" tabindex="-1">
                                <img width="20" height="20" src="<?php echo esc_url(get_template_directory_uri() . '/img/star-icon.png'); ?>" alt="">
                                <span>Starburst</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="right">
                    <div class="button-group" role="group" aria-label="Edit">
                        <button type="button" title="Undo (Ctrl+Z)" aria-label="Undo" id="undoBtn" disabled><i class="fa-solid fa-rotate-left" aria-hidden="true"></i></button>
                        <button type="button" title="Redo (Ctrl+Y)" aria-label="Redo" id="redoBtn" disabled><i class="fa-solid fa-rotate-right" aria-hidden="true"></i></button>
                        <span class="cl-divider" aria-hidden="true"></span>
                        <button type="button" title="Duplicate" aria-label="Duplicate selected" id="duplicateBtn"><i class="fa-solid fa-clone" aria-hidden="true"></i></button>
                        <button type="button" title="Zoom in" aria-label="Zoom in" id="zoomInBtn"><i class="fa-solid fa-magnifying-glass-plus" aria-hidden="true"></i></button>
                        <button type="button" title="Zoom out" aria-label="Zoom out" id="zoomOutBtn"><i class="fa-solid fa-magnifying-glass-minus" aria-hidden="true"></i></button>
                        <button type="button" title="Delete" aria-label="Delete selected" id="deleteBtn"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
                        <button type="button" title="Clear builder" aria-label="Clear builder" id="clearBuilderBtn"><i class="fa-solid fa-broom" aria-hidden="true"></i></button>
                    </div>
                </div>
            </div>
        </div>
        <div id="container" aria-label="Design canvas"></div>

        <div class="editor-bottombar">
            <div class="bottom-left">
                <?php if ($is_ps_option) : ?>

                    <div id="powerSupply" class="bottombar-left-item" role="button" tabindex="0" aria-haspopup="true">
                        <span class="current-item">
                            <span class="cl-chip-label">Power supply</span><span class="value"><b>Standard </b></span>
                        </span>
                        <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>

                        <div class="bottombar-item-list-container">

                            <ul class="item-list-container">
                                <li class="bottombar-list-item active" data-type="ps" data-value="standard">
                                    <span class="item-name active">
                                        Power Supply: Standard

                                    </span>
                                    <div class="info-btn-container">
                                        <span class="info-btn" role="button" tabindex="0" aria-label="About the standard power supply">i</span>
                                        <div class="info-btn-content top">
                                            <small class="text-muted">
                                                Fang Hua HMA-60NU-RX 12V / 5A 60W, Class 2, constant voltage and current, IP67, 5-year warranty. Non-brand transformer boxes, for dry locations only. $90 each.
                                            </small>
                                            <img class="mt-2 w-100" src="<?php echo esc_url(get_template_directory_uri() . '/img/standard-ps-info.png'); ?>" alt="">
                                        </div>
                                    </div>
                                </li>
                                <li class="bottombar-list-item" data-type="ps" data-value="none">
                                    <span class="item-name">
                                        Power Supply: None

                                    </span>
                                    <div class="info-btn-container">
                                        <span class="info-btn force-transparent">i</span>
                                    </div>

                                </li>
                            </ul>

                        </div>
                    </div> <?php endif ?>
                <?php if ($is_lit_option): ?>
                    <div id="ledLit" class="bottombar-left-item" role="button" tabindex="0" aria-haspopup="true">
                        <span class="current-item">
                            <span class="cl-chip-label">Lighting</span><span class="value"><b>Front Lit </b></span>
                        </span>
                        <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>

                        <div class="bottombar-item-list-container">

                            <ul class="item-list-container">
                                <li class="bottombar-list-item active" data-type="lit" data-value="front">
                                    <span class="item-name ">
                                        Front Lit

                                    </span>
                                </li>
                                <li class="bottombar-list-item" data-type="lit" data-value="both">
                                    <span class="item-name">
                                        Front and Back Lit

                                    </span>

                                </li>
                            </ul>

                        </div>
                    </div>

                <?php endif; ?>
                <?php if ($is_cable_option) : ?>
                    <div id="ledCable" class="bottombar-left-item" role="button" tabindex="0" aria-haspopup="true">
                        <span class="current-item">
                            <span class="cl-chip-label">LED lights</span><span class="value"><b>3ft Cable</b></span>
                        </span>
                        <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>

                        <div class="bottombar-item-list-container">

                            <ul class="item-list-container">
                                <li class="bottombar-list-item active" data-type="cable" data-value="3">
                                    <span class="item-name ">
                                        LED Lights: 3ft Cable

                                    </span>
                                </li>
                                <li class="bottombar-list-item" data-type="cable" data-value="8">
                                    <span class="item-name">
                                        LED Lights: 8ft Cable

                                    </span>

                                </li>
                                <li class="bottombar-list-item" data-type="cable" data-value="0">
                                    <span class="item-name">
                                        No LED <span class="text-danger">
                                            (No UL label)
                                        </span>

                                    </span>

                                </li>
                            </ul>

                        </div>
                    </div>
                <?php endif; ?>
                <div class="bottombar-overlay" title="Add letters or a shape first">

                </div>
            </div>
            <div class="bottom-right">
                <div id="totalObject" class="cl-stat">
                    <span>Elements</span>
                    <span class="value main-color">
                        0
                    </span>
                </div>
                <div id="totalCost" class="cl-stat cl-stat--price">
                    <span>Total</span>
                    <span class="value">
                        <span class="main-color" id="displayCost"> $0</span>

                    </span>
                </div>
                <button type="button" data-bs-toggle="modal" data-bs-target="#costModal" id="detailBtn"
                    class="btn cl-btn-secondary">Price details</button>
                <button type="button" class="btn btn-primary" id="saveBtn"><?php echo $ticket_builder ? 'Save design to ticket' : 'Save design'; ?></button>
            </div>
        </div>
    </div>
</div>

<p id="fontFamilyLoader" style="color: transparent" class="text-center mt-3">
    Loading Fonts.....
</p>
<!-- Modal -->
<div class="modal fade" id="costModal" tabindex="-1" aria-labelledby="costModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="costModalLabel">Cost Breakdown</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive cost-breakdown-table">
                    <table class="table table-bordered text-center">
                        <thead>
                            <tr>
                                <th scope="col">Id</th>
                                <th scope="col">Type</th>
                                <th scope="col">Dimension</th>
                                <th scope="col">Cost</th>
                                <th scope="col">Face Cost</th>
                                <th scope="col">Price</th>
                            </tr>
                        </thead>
                        <tbody id="detailTableBody" data-haslitoption="<?php echo $is_lit_option ?>">

                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade change-product-modal" id="changeProductModal" tabindex="-1" aria-labelledby="changeProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <span class="change-product-eyebrow">Channel letter collection</span>
                    <h2 class="modal-title" id="changeProductModalLabel">Choose a different product</h2>
                    <p class="change-product-intro">Select a product to start a new design with the right materials and options.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="change-product-notice">
                    <span>Changing products starts a fresh configuration. Your current design will stay unsaved.</span>
                </div>
                <div class="change-product-grid">
                    <?php if ($channel_letter_products->have_posts()) : ?>
                        <?php while ($channel_letter_products->have_posts()) : $channel_letter_products->the_post(); ?>
                            <?php
                            $change_product_id = get_the_ID();
                            $is_current_product = ((int) $change_product_id === (int) $product_id);
                            $change_product_image = get_post_thumbnail_id($change_product_id);
                            $change_product_price = get_post_meta($change_product_id, '_starting_at_text', true);
                            $change_product_url = trailingslashit(get_permalink($change_product_id)) . 'channel-letter-builder/?product_id=' . $change_product_id;
                            if ($ticket_builder) {
                                $change_product_url = add_query_arg($ticket_builder['switch_query'], $change_product_url);
                            }
                            ?>
                            <a class="change-product-card<?php echo $is_current_product ? ' is-current' : ''; ?>"
                                href="<?php echo esc_url($change_product_url); ?>"
                                <?php echo $is_current_product ? 'aria-current="true"' : ''; ?>>
                                <span class="change-product-image">
                                    <?php if ($change_product_image) : ?>
                                        <?php echo wp_get_attachment_image($change_product_image, 'product-card', false, array('alt' => esc_attr(get_the_title($change_product_id)))); ?>
                                    <?php else : ?>
                                        <i class="fa-regular fa-image" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </span>
                                <span class="change-product-card-content">
                                    <strong><?php echo esc_html(get_the_title($change_product_id)); ?></strong>
                                    <span><?php echo $is_current_product ? 'Current product' : 'Start new design'; ?></span>
                                    <?php if ($change_product_price) : ?>
                                        <span class="change-product-card-price"><?php echo wp_kses_post($change_product_price); ?></span>
                                    <?php else : ?>
                                        <span class="change-product-card-price">Price available after configuration</span>
                                    <?php endif; ?>
                                </span>
                                <?php if (!$is_current_product) : ?><i class="fa-solid fa-chevron-right change-product-card-icon" aria-hidden="true"></i><?php endif; ?>
                            </a>
                        <?php endwhile; wp_reset_postdata(); ?>
                    <?php else : ?>
                        <p class="change-product-empty">No other channel letter products are available right now.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<form action="<?php echo get_permalink($product_id); ?>" enctype="multipart/form-data" class="designForm">
    <input type="file" name="cl_design" id="clDesignInput">
    <input type="hidden" name="upload_design" value="yes">
</form>



<?php get_footer(); ?>