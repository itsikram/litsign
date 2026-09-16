<?php

/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package litsign
 * 
 * this page is designed by ikramul islam
 */

?>


<footer class="footer">
    <p class="text-center text-muted fs-5">
        Storefront Sign’s Samples
    </p>
    <div class="footer-bg-image-2 text-center">
        <div class="button-container">
        </div>

        <img src="<?php echo esc_url(get_template_directory_uri() . '/img/footer-bd-2.webp?ver=' . _S_VERSION); ?>" alt="" loading="lazy" decoding="async">
    </div>
    <div class="container mt-3 text-center">
        <a href="<?php echo home_url(); ?>" class="btn btn-primary mt-3">Shop All Channel Letters</a>

    </div>

    <div class="container py-5">
        <div class="row">
            <div class="col">
                <h2 class="text-center fs-3">
                    Adhesive products Orders placed by 4pm PST will be shipped the next business day
                </h2>
                <p class="text-center fs-5 mt-4">Same-day service is also available if ordered by 12pm PST</p>
            </div>
        </div>
    </div>

    <div class="footer-bg-image-1">
        <img src="<?php echo esc_url(get_template_directory_uri() . '/img/footer-bg-1.png?ver=' . _S_VERSION); ?>" alt="" width="1635" height="325" loading="lazy" decoding="async">
    </div>

    <div class="footer-info py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 p-3">
                    <div class="footer-logo-container">
                        <a href="<?php echo home_url() . '/'; ?>">
                            <?php $logo_id = get_theme_mod('custom_logo');

                            if ($logo_id) {
                            ?>
                                <?php echo wp_get_attachment_image($logo_id, 'header-logo', false, array(
                                    'class' => 'header-logo',
                                    'alt' => get_bloginfo('name'),
                                    'loading' => 'lazy',
                                    'decoding' => 'async',
                                    'sizes' => '240px',
                                )); ?>

                            <?php
                            } else {
                            ?>
                                <img src="<?php echo esc_url(get_template_directory_uri() . '/img/logo.png'); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="header-logo" width="2417" height="261" loading="lazy" decoding="async">

                            <?php
                            } ?>
                        </a>
                    </div>
                </div>
                <div class="col-md-4 p-3">
                    <h3 class="footer-info-title">Headquarters</h3>

                    <div class="footer-text-light  pb-3">
                        Triton Towers Three <br />
                        707 S. Grady Way Suite 600<br />
                        Renton, WA 98057<br />
                    </div>
                    <div class="footer-separator">

                        <small class="footer-text-light">
                            Pick up is NOT available
                        </small>
                    </div>

                    <div class="footer-text-gray">
                        Customer Service Office Hour:
                    </div>
                    <div class="footer-text-light footer-separator pb-3">
                        Mon - Fri: 8:00am - 5:00pm PST
                    </div>


                    <div class="footer-text-gray mt-3">
                        Toll Free:
                    </div>
                    <div class="footer-text-light footer-separator pb-3">
                        866-436-2101
                    </div>

                    <a href="tel:866-436-2101" class="btn btn-primary my-2">Call Now</a>
                    <div class="footer-text-gray mt-3">
                        Live Chat:
                    </div>
                    <div class="footer-text-light footer-separator pb-3">
                        Offline
                    </div>
                    <div class="footer-text-gray mt-3">
                        Email
                    </div>
                    <div class="footer-text-light">
                        <a href="mailto:TR@StorefrontSignOnline.com">TR@StorefrontSignOnline.com</a>
                    </div>
                </div>
                <div class="col-md-4 p-3">
                    <h3 class="footer-info-title">Share Your Thoughts</h3>
                    <div class="footer-text-light">
                        We value your input. If you have suggestions or feedback, let us know. Your message is important
                        to us.
                    </div>
                    <button type="button" class="btn btn-primary my-3" id="feedbackmodalTrigger">
                        Leave Feedback
                    </button>

                    <!-- Modal -->
                    <div class="modal" id="feedbackModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    ...
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary">Save changes</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="footer-cp-text-container">
        <div class="container">
            <div class="row">
                <div class="col">
                    <p class="cp-text text-center">
                        Copyright © 2024 Storefrontsignonline, Inc.
                        All Rights Reserved.
                        <a href="<?php echo site_url(); ?>/terms-conditions/">Terms & Conditions</a>

                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>

</html>