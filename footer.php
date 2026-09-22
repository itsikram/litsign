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
        <a href="<?php echo home_url() . '/channel-letters'; ?>" class="btn btn-primary mt-3">Shop All Channel Letters</a>

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

    <section class="customer-review-section" aria-labelledby="customer-review-title">
        <div class="container">
            <div class="customer-review-header">
                <h2 id="customer-review-title">What Our Customers Say</h2>
                <p>Real Reviews. Real Results.</p>
            </div>

            <div class="customer-review-slider" aria-live="polite">
                <button class="customer-review-nav customer-review-prev" type="button" aria-label="Previous customer review">
                    <span aria-hidden="true">&#8249;</span>
                </button>

                <div class="customer-review-card">
                    <div class="customer-review-brand-row">
                        <span class="customer-review-google" aria-hidden="true">
                            <svg viewBox="0 0 48 48" role="img" aria-label="Google logo" xmlns="http://www.w3.org/2000/svg">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.73 1.22 9.24 3.6l6.9-6.9C35.77 2.2 30.4 0 24 0 14.64 0 6.48 5.38 2.54 13.2l8.02 6.22C12.06 14.08 17.38 9.5 24 9.5Z"/>
                                <path fill="#4285F4" d="M46.5 24.5c0-1.6-.15-3.13-.42-4.61H24v8.73h12.78c-.55 2.96-2.24 5.47-4.77 7.15l7.71 5.98C43.7 35.02 46.5 30.14 46.5 24.5Z"/>
                                <path fill="#FBBC05" d="M32.01 35.77c-2.16 1.46-4.93 2.32-8.01 2.32-6.62 0-12.23-4.46-14.2-10.46l-8.07 6.25C4.9 42.42 13.58 48 24 48c7.19 0 13.24-2.36 17.66-6.41l-9.65-5.82Z"/>
                                <path fill="#34A853" d="M9.8 27.63A14.48 14.48 0 0 1 9.2 24c0-1.66.29-3.27.8-4.78L2.54 13c-1.56 3.11-2.54 6.66-2.54 10.98s.98 7.87 2.54 10.98l7.46-5.79Z"/>
                                <path fill="#EA4335" d="M24 9.5c3.86 0 7.31 1.33 10.04 3.94l7.52-7.52C37.31 2.07 31.47 0 24 0 14.64 0 6.48 5.38 2.54 13.2l8.02 6.22C12.06 14.08 17.38 9.5 24 9.5Z" opacity="0.2"/>
                            </svg>
                        </span>
                        <span class="customer-review-stars" aria-label="5 out of 5 stars">★★★★★</span>
                    </div>

                    <blockquote class="customer-review-quote" id="customer-review-quote">
                        “Excellent service and high-quality products! Our new channel letters look amazing and brought more customers to our store.”
                    </blockquote>
                    <p class="customer-review-author" id="customer-review-author">— Michael R., Phoenix, AZ</p>
                </div>

                <button class="customer-review-nav customer-review-next" type="button" aria-label="Next customer review">
                    <span aria-hidden="true">&#8250;</span>
                </button>
            </div>

            <div class="customer-review-dots" aria-label="Customer review navigation">
                <button class="customer-review-dot is-active" type="button" aria-label="Show review 1" data-review-dot="0"></button>
                <button class="customer-review-dot" type="button" aria-label="Show review 2" data-review-dot="1"></button>
                <button class="customer-review-dot" type="button" aria-label="Show review 3" data-review-dot="2"></button>
                <button class="customer-review-dot" type="button" aria-label="Show review 4" data-review-dot="3"></button>
            </div>
        </div>
    </section>

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
                    <button type="button" class="btn btn-primary my-3" id="feedbackmodalTrigger" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                        Leave a Review
                    </button>

                    <!-- Modal -->
                    <div class="modal fade" id="feedbackModal" tabindex="-1" role="dialog" aria-labelledby="feedbackModalTitle" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header justify-content-between">
                                    <h5 class="modal-title" id="feedbackModalTitle">Share your experience</h5>
                                    <button type="button" class="close"  style="font-size: 24px; background: transparent !important; border: 0 !important; outline: 0 !important; color: red;" data-bs-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true" class="btn fs-1 text-danger">&times;</span>
                                    </button>
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