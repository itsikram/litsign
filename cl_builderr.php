<?php

// Template Name: Channel Letter builder 


// if(!is_user_logged_in()) {
//     wp_redirect(site_url().'/login?redirect_ulr='.get_permalink());
// }


if (isset($_REQUEST['product_id']) && $_REQUEST['product_id'] > 0) {
    $product_id = $_REQUEST['product_id'];
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
} else {
    wp_redirect(home_url());
}


get_header();

?>

<input type="hidden" id="faceColorPicker" value="#ffffff">
<input type="hidden" id="productPermalink" value="<?php echo get_permalink($product_id); ?>">


<input type="hidden" name="hasTrimcap" value="<?php echo $has_trimcap; ?>" id="hasTrimcap">
<input type="hidden" name="hasReturn" value="<?php echo $has_return; ?>" id="hasReturn">
<input type="hidden" name="hasFace" value="<?php echo $has_face; ?>" id="hasFace">
<input type="hidden" name="defaultColorData" value='<?php echo $default_data_json; ?>' id="defaultColorData">

<input type="hidden" id="trimcapColorPicker" value="#000000">
<input type="hidden" id="trimcapSizeInput" value="2">

<input type="hidden" id="returnColorPicker" value="#000000">

<input type="hidden" id="returnSizeInput" value="3" min="0" step="1">
<input type="hidden" name="product_cl_data" value='<?php echo $product_cl_data; ?>' id="productClData">
<input type="hidden" name="edit_design_data" value='<?php echo $edit_product_data; ?>' id="editDesignData">


<input type="hidden" id="standarPsCost" value="<?php echo $standard_ps_cost ? $standard_ps_cost : 90; ?>">
<input type="hidden" id="backLitCost" value="<?php echo $backlit_cost ? $backlit_cost : 100; ?>">
<input type="hidden" id="eightFtcableCost" value="<?php echo $eight_ft_cable_cost ? $eight_ft_cable_cost : 70; ?>">

<!-- <button id="addPatternButton">Add Pattern</button> -->

<div class="dt-container" id="dtContainer">
    <div class="left-sidebar" id="leftSidebar">
        <div id="leftSidebarSlider" class="left-sidebar-slider">
            <button id="sliderCloseBtn">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="left-slider-container">

            </div>

        </div>
        <div class="left-sidebar-container">
            <ul class="sidebar-item-container">
                <li class="sidebar-item item-preview">
                    <label class="fw-bold">Channel
                    </label>
                    <div id="previewContainer"></div>
                    <div class="d-flex element-dimenstion-container align-items-center justify-content-between mt-2"> <span class="element-index">#0</span> <span class="element-dimenstion"> H:0 x W:0</span> </div>
                </li>
                <li class="sidebar-item item-textInput">
                    <input type="text" id="textInput" class="form-control" placeholder="Input Text">
                </li>
                <li class="item-font sidebar-item d-flex align-items-center justify-content-between">
                    <div class="fw-bold">
                        Font

                    </div>
                    <div class="select-font d-flex align-items-center justify-content-between"
                        data-current-font="Arial" data-active="Arial/Arial">
                        <span class="name mr-2">
                            Arial
                        </span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>
                </li>
                <li class="item-face sidebar-item d-flex align-items-center justify-content-between">
                    <div class="fw-bold">
                        Face

                        <span class="info-btn-container">
                            <span class="info-btn">i</span>
                            <div class="info-btn-content">
                                <small class="text-muted">Trimcap is a plastic molding that surrounds the acrylic channel letter face.</small>
                                <img class="mt-2 w-100" src="<?php echo get_template_directory_uri() . '/img/face-info.png'; ?>" alt="">
                            </div>
                        </span>
                    </div>
                    <div class="select-face  d-flex align-items-center justify-content-between"
                        data-current-face="White" data-active="White/rgb(255, 255, 255)">
                        <span class="name mr-2">
                            White
                        </span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>
                </li>
                <li class="item-return sidebar-item d-flex align-items-center justify-content-between">
                    <div class="fw-bold">
                        Return
                        <span class="info-btn-container">
                            <span class="info-btn">i</span>
                            <div class="info-btn-content">
                                <small class="text-muted">
                                    Return is the side of a channel letter
                                </small>

                                <img src="<?php echo get_template_directory_uri() . '/img/return-info.png'; ?>" alt="">
                            </div>
                        </span>
                    </div>
                    <div class="select-return  d-flex align-items-center justify-content-between"
                        data-current-return="Black" data-active="Black/rgb(0, 0, 0)">
                        <span class="name mr-2">
                            Black
                        </span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>
                </li>
                <li class="item-trimcap sidebar-item d-flex align-items-center justify-content-between">
                    <div class="fw-bold">
                        Trimcap
                        <span class="info-btn-container">
                            <span class="info-btn">i</span>
                            <div class="info-btn-content">
                                <small class="text-muted">
                                    Trimcap is a plastic molding that surrounds the acrylic channel letter face.
                                </small>
                                <img class="mt-2 w-100" src="<?php echo get_template_directory_uri() . '/img/trimcap-info.png'; ?>" alt="">

                            </div>
                        </span>
                    </div>
                    <div class="select-trimcap  d-flex align-items-center justify-content-between"
                        data-current-trimcap="Black" data-active="Black/rgb(0, 0, 0)">
                        <span class="name mr-2">
                            Black
                        </span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>
                </li>
                <li class="sidebar-item item-heightWidthInput mt-3">
                    <div class="row w-100 d-flex align-items-center">
                        <div class="col-3">

                            <label for="sizeHeightInput" class="form-label">Height</label>
                        </div>
                        <div class="col-9">
                            <input type="number" max="45" step="0.1" min="8" id="sizeHeightInput" placeholder="Height"
                                class="form-control">
                        </div>

                    </div>
                    <div class="row w-100 d-flex align-items-center  mt-2">
                        <div class="col-3">
                            <label for="sizeWidthInput" class="form-label">Width</label>
                        </div>
                        <div class="col-9">
                            <input type="number" step="0.1" id="sizeWidthInput" placeholder="Width" class="form-control">
                        </div>

                    </div>



                </li>

                <li id="cornerRadiusContainer" class="sidebar-item item-radius mt-3 border-top">
                    <div class="row w-100 d-flex align-items-center">
                        <div class="col-3">

                            <label for="cornerRadius" class="form-label">Corner Radius</label>
                        </div>
                        <div class="col-9">
                            <input type="number" max="5" value="0.8" step="0.1" min="0.8" id="cornerRadius" placeholder="Corner Radius" class="form-control">
                        </div>

                    </div>
                </li>

            </ul>
        </div>
    </div>
    <div class="editor-container" id="editorContainer">
        <div class="editor-topbar">
            <div class="left">
                <button id="addTextBtn" class="topbar-left-button">
                    <img width="20" src="<?php echo get_template_directory_uri() ?>/img/text-icon.png" alt="Text">
                    Text
                </button>
                <button id="addRacewayButton" class="topbar-left-button">
                    <img width="20" src="<?php echo get_template_directory_uri(); ?>/img/raceway-icon.png" alt="Text">
                    Raceway
                </button>

                <button class="topbar-left-button shape-dropdown">
                    <img width="20" src="<?php echo get_template_directory_uri() ?>/img/star-icon.png" alt="Text">
                    Shape
                    <i class="shape-arrow-icon"></i>
                    <ul class="shapes-container">
                        <li class="shape" data-shape="rectangle">
                            <img width="20" src="<?php echo get_template_directory_uri() ?>/img/rect-icon.png" alt="Rect">
                            <span>
                                Rect
                            </span>

                        </li>
                        <li class="shape" data-shape="circle">
                            <img width="20" src="<?php echo get_template_directory_uri() ?>/img/circle-icon.png" alt="Circle">
                            <span>Oval</span>
                        </li>
                        <li class="shape" data-shape="triangle">
                            <img width="20" src="<?php echo get_template_directory_uri() ?>/img/triangle-icon.png" alt="Triangle">
                            <span>Triangle</span>
                        </li>
                        <li class="shape" data-shape="arrow">
                            <img width="20" src="<?php echo get_template_directory_uri() ?>/img/arrow-icon.png" alt="Arrow">
                            <span>Arrow</span>
                        </li>
                        <li class="shape" data-shape="star">
                            <img width="20" src="<?php echo get_template_directory_uri() ?>/img/star-icon.png" alt="Star">
                            <span>Starburst</span>
                        </li>
                    </ul>
                </button>
            </div>
            <div class="middle">
                <h1 class="product-title text-truncate"> <?php echo $product_title; ?></h1>
            </div>
            <div class="right">
                <div class="button-group mt-0 d-flex justify-content-end">
                    <button title="Clone" id="duplicateBtn"><i class="fa-solid fa-clone"></i></button>
                    <!-- <button title="Undo" id="undoBtn"><i class="fa-solid fa-rotate-left"></i></button>
                    <button title="Redo" id="redoBtn"><i class="fa-solid fa-rotate-right"></i></button> -->
                    <button title="Zoom In" id="zoomInBtn"><i
                            class="fa-solid fa-magnifying-glass-plus"></i></button>
                    <button title="Zoom Out" id="zoomOutBtn"><i
                            class="fa-solid fa-magnifying-glass-minus"></i></button>
                    <button title="Delete" id="deleteBtn"><i class="fa-solid fa-trash-can"></i></button>
                </div>
            </div>
        </div>
        <div id="container"></div>

        <div class="editor-bottombar d-flex justify-content-between align-items-center">
            <div class="bottom-left d-flex justify-content-start">
                <?php if ($is_ps_option) : ?>

                    <div id="powerSupply" class="bottombar-left-item p-1">
                        <span class="current-item text-center">
                            Power Supply: <br /> <span class="value"><b>Standard </b></span>
                        </span>
                        <i class="fa-solid fa-chevron-up"></i>

                        <div class="bottombar-item-list-container">

                            <ul class="item-list-container">
                                <li class="bottombar-list-item active" data-type="ps" data-value="standard">
                                    <span class="item-name active">
                                        Power Supply: Standard

                                    </span>
                                    <div class="info-btn-container">
                                        <span class="info-btn">i</span>
                                        <div class="info-btn-content top">
                                            <small class="text-muted">
                                                - Fang Hua HMA-60NU-RX 12V/ 5A 60W, Class 2, Constant Voltage, Constant Current, IP67, Power Supplies (5 Year Warranty)
                                                - Non-brand Transformer Boxes
                                                - For dry location only
                                                - $90 for each
                                            </small>
                                            <img class="mt-2 w-100" src="<?php echo get_template_directory_uri() . '/img/standard-ps-info.png'; ?>" alt="">
                                        </div>
                                    </div>
                                </li>
                                <li class="bottombar-list-item" data-type="ps" data-value="none">
                                    <span class="item-name">
                                        Power Supply: None

                                    </span>
                                    <!-- <small class="text-danger">(No UL Label)</small> -->
                                    <div class="info-btn-container">
                                        <span class="info-btn force-transparent">i</span>
                                    </div>

                                </li>
                            </ul>

                        </div>
                    </div> <?php endif ?>
                <?php if ($is_lit_option): ?>
                    <div id="ledLit" class="bottombar-left-item p-1">
                        <span class="current-item text-center">
                            Lit: <br /> <span class="value"><b>Front Lit </b></span>
                        </span>
                        <i class="fa-solid fa-chevron-up"></i>

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
                    <div id="ledCable" class="bottombar-left-item p-1">
                        <span class="current-item text-center">
                            LED Lights: <br /> <span class="value"><b>3ft Cable</b></span>
                        </span>
                        <i class="fa-solid fa-chevron-up"></i>

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
                                        No Led: <span class="text-danger">
                                            (No UL Label)
                                        </span>

                                    </span>

                                </li>
                            </ul>

                        </div>
                    </div>
                <?php endif; ?>
                <div class="bottombar-overlay">

                </div>
            </div>
            <div class="bottom-right d-flex justify-content-between  align-items-center">
                <div id="totalObject" class="mx-2">
                    <span>Total Object:</span>
                    <span class="value main-color">
                        0
                    </span>
                </div>
                <div id="totalCost" class="mx-2">
                    <span>Total Cost:</span>
                    <span class="value">
                        <span class="main-color" id="displayCost"> $0</span>

                    </span>
                </div>
                <button data-bs-toggle="modal" data-bs-target="#costModal" id="detailBtn"
                    class="btn bg-transparent btn-secondary text-dark">Details</button>
                <!-- <button id="helpBtn" class="btn bg-transparent btn-secondary text-dark">Help</button> -->
                <button class="btn btn-primary" id="saveBtn">Save Design</button>
            </div>
        </div>
    </div>
</div>

<p id="fontFamilyLoader" style="color: transparent" class="text-center mt-3">
    Loading Fonts.....
</p>
<!-- Modal -->
<div class="modal fade" id="costModal" tabindex="-1" aria-labelledby="costModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="costModalLabel">Cost Breakdown</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
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
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<form action="<?php echo get_permalink($product_id); ?>" enctype="multipart/form-data" class="designForm">
    <input type="file" name="cl_design" id="clDesignInput">
    <input type="hidden" name="upload_design" value="yes">
</form>



<?php get_footer(); ?>