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

        <img src="<?php echo esc_url(get_template_directory_uri() . '/img/footer-bd-2.webp?ver=' . _S_VERSION); ?>" alt="Samples of storefront signs and channel letters" loading="lazy" decoding="async">
    </div>
    <div class="container my-3 text-center">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary mt-3">Shop All Channel Letters</a>

    </div>

    <?php
    // The adhesive shipping note stays off channel letter pages: home.php sets the
    // global for the channel letter landing page, and made-to-order letters ship later.
    $is_channel_letter_page = !empty($GLOBALS['wholesale_channel_letter_landing'])
        || is_page(array('channel-letter-builder', 'storefront-signs'))
        || (is_singular('product') && has_term('channel-letters', 'product_category', get_queried_object_id()));
    ?>
    <?php if (!$is_channel_letter_page) : ?>
    <div class="container py-5">
        <div class="row">
            <div class="col">
                <p class="text-center fs-3 fw-bold mb-0">
                    Adhesive products: orders placed by 4pm PST ship the next business day
                </p>
                <p class="text-center fs-5 mt-4">Same-day service is also available if ordered by 12pm PST</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="footer-bg-image-1">
        <img src="<?php echo esc_url(get_template_directory_uri() . '/img/footer-bg-1.png?ver=' . _S_VERSION); ?>" alt="" width="1635" height="325" loading="lazy" decoding="async">
    </div>

    <?php wholesale_render_review_slider(); ?>

    <section class="newsletter-section" aria-label="Newsletter signup">
        <div class="container">
            <div class="newsletter-inner">
                <div class="newsletter-copy">
                    <div class="newsletter-icon" aria-hidden="true">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <div>
                        <h3>Subscribe to Our Newsletter</h3>
                        <p>Get the latest updates, special offers and new product releases.</p>
                    </div>
                </div>

                <div class="newsletter-actions">
                    <?php
                    $newsletter_status = isset($_GET['newsletter']) ? sanitize_key(wp_unslash($_GET['newsletter'])) : '';
                    if ('success' === $newsletter_status) {
                        echo '<div class="newsletter-message newsletter-message-success">Thank you! Your email has been added to our newsletter list.</div>';
                    } elseif ('exists' === $newsletter_status) {
                        echo '<div class="newsletter-message newsletter-message-info">This email is already subscribed to our newsletter.</div>';
                    } elseif ('invalid' === $newsletter_status) {
                        echo '<div class="newsletter-message newsletter-message-error">Please enter a valid email address.</div>';
                    } elseif ('error' === $newsletter_status) {
                        echo '<div class="newsletter-message newsletter-message-error">There was a problem saving your email. Please try again.</div>';
                    }
                    ?>
                    <form class="newsletter-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                        <?php wp_nonce_field('wholesale_newsletter_subscribe', 'newsletter_nonce'); ?>
                        <input type="hidden" name="action" value="wholesale_newsletter_subscribe">
                        <label class="screen-reader-text" for="newsletter-email">Email address</label>
                        <input id="newsletter-email" type="email" name="newsletter_email" placeholder="Enter your email address" aria-label="Email address" required>
                        <button type="submit">Subscribe</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

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
                        Cell Phone:
                    </div>
                    <div class="footer-text-light footer-separator pb-3">
                        206-618-6543 <br />
                        <small style="font-size: 0.85em; color: #888;">Sending text messages is ok via this number</small>
                    </div>

                    <a href="sms:2066186543" class="btn btn-primary my-2">Send Message</a>
                    
                    <div class="footer-text-gray mt-3">
                        Live Chat:
                    </div>
                    <div class="footer-text-light footer-separator pb-3">
                        Online
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
                    <button type="button" class="btn btn-primary my-3" id="feedbackmodalTrigger" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                        Leave a Review
                    </button>

                    <!-- Modal -->
                    <div class="modal fade" id="feedbackModal" tabindex="-1" role="dialog" aria-labelledby="feedbackModalTitle" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header justify-content-between">
                                    <h5 class="modal-title" id="feedbackModalTitle">Share your experience</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <?php
                                    $review_status = isset($_GET['review_status']) ? sanitize_key(wp_unslash($_GET['review_status'])) : '';
                                    if ('sent' === $review_status) :
                                    ?>
                                        <div class="alert alert-success" role="status">Thank you for sharing your review. Our team will review it shortly.</div>
                                    <?php elseif ('error' === $review_status) : ?>
                                        <div class="alert alert-danger" role="alert">Please complete every field and try again.</div>
                                    <?php endif; ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <input type="hidden" name="action" value="submit_review">
                                        <?php wp_nonce_field('submit_review', 'review_nonce'); ?>
                                        <div class="form-group">
                                            <label for="review-name">Your name</label>
                                            <input class="form-control" id="review-name" name="review_name" type="text" required autocomplete="name">
                                        </div>
                                        <div class="form-group">
                                            <label for="review-email">Email address</label>
                                            <input class="form-control" id="review-email" name="review_email" type="email" required autocomplete="email">
                                        </div>
                                        <div class="form-group">
                                            <label for="review-rating">Your rating</label>
                                            <select class="form-control" id="review-rating" name="review_rating" required>
                                                <option value="">Select a rating</option>
                                                <option value="5">5 - Excellent</option>
                                                <option value="4">4 - Very good</option>
                                                <option value="3">3 - Good</option>
                                                <option value="2">2 - Fair</option>
                                                <option value="1">1 - Needs improvement</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="review-message">Your review</label>
                                            <textarea class="form-control" id="review-message" name="review_message" rows="5" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary mt-3">Submit review</button>
                                    </form>
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
                    <?php
                    $footer_links = array(
                        home_url('/') => 'Channel Letters',
                        'storefront-signs' => 'Storefront Signs',
                        'about' => 'About Us',
                        'contact' => 'Contact',
                        'shipping-returns' => 'Shipping & Returns',
                        'terms-conditions' => 'Terms & Conditions',
                    );
                    ?>
                    <nav class="footer-links text-center" aria-label="<?php esc_attr_e('Footer', 'litsign'); ?>">
                        <?php foreach ($footer_links as $target => $label) : ?>
                            <?php
                            if (0 !== strpos($target, 'http')) {
                                $footer_page = get_page_by_path($target);
                                if (!$footer_page || 'publish' !== $footer_page->post_status) {
                                    continue;
                                }
                                $target = get_permalink($footer_page);
                            }
                            ?>
                            <a href="<?php echo esc_url($target); ?>"><?php echo esc_html($label); ?></a>
                        <?php endforeach; ?>
                        <?php if (get_privacy_policy_url()) : ?>
                            <a href="<?php echo esc_url(get_privacy_policy_url()); ?>">Privacy Policy</a>
                        <?php endif; ?>
                    </nav>
                    <p class="cp-text text-center">
                        Copyright © <?php echo esc_html(wp_date('Y')); ?> Storefrontsignonline, Inc.
                        All Rights Reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>

</div><!-- #page -->

<?php wp_footer(); ?>

<?php if (isset($_GET['review_status']) && in_array(sanitize_key(wp_unslash($_GET['review_status'])), array('sent', 'error'), true)) : ?>
    <script>
        jQuery(function ($) {
            var feedbackModal = document.getElementById('feedbackModal');

            if (feedbackModal && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(feedbackModal).show();
            }
        });
    </script>
<?php endif; ?>

</body>

</html>