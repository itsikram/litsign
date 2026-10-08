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
        <a href="<?php echo esc_url(wholesale_category_url('channel-letters')); ?>" class="btn btn-primary mt-3">Shop All Channel Letters</a>

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

    <?php
    $footer_phone_display = '866-436-2101';
    $footer_phone_href = 'tel:+18664362101';
    $footer_text_display = '206-618-6543';
    $footer_text_href = 'sms:+12066186543';
    $footer_email = 'TR@StorefrontSignOnline.com';
    // Inline icons: the theme's icon font is a small subset without these glyphs.
    $footer_icon = static function ($name) {
        $paths = array(
            'phone' => '<path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25c1.1.37 2.3.57 3.6.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1z"/>',
            'message' => '<path fill="currentColor" d="M4 3h16a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H8l-4.3 3.4A.45.45 0 0 1 3 21V5a2 2 0 0 1 1-2zm3 6.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm5 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3zm5 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z"/>',
            'mail' => '<path fill="currentColor" d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1zm1.4 2 7.6 5.6L19.6 7H4.4zM20 8.7l-7.4 5.5a1 1 0 0 1-1.2 0L4 8.7V17h16V8.7z"/>',
            'clock' => '<path fill="currentColor" d="M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20zm0 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16zm1 3v4.6l3.2 1.9-1 1.7L11 12.7V7h2z"/>',
            'pin' => '<path fill="currentColor" d="M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7zm0 4a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/>',
            'check' => '<path fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7.5"/>',
            'star' => '<path fill="currentColor" d="m12 2.8 2.8 5.8 6.3.9-4.6 4.4 1.1 6.3L12 17.2l-5.6 3 1.1-6.3-4.6-4.4 6.3-.9z"/>',
        );
        return '<svg class="sf-svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
    };

    // Footer pages are listed only once they exist and are published.
    $footer_page_links = static function ($pages) {
        $links = array();
        foreach ($pages as $slug => $label) {
            $page = get_page_by_path($slug);
            if ($page && 'publish' === $page->post_status) {
                $links[get_permalink($page)] = $label;
            }
        }
        return $links;
    };
    $footer_company_links = $footer_page_links(array(
        'about' => 'About Us',
        'contact' => 'Contact',
        'track-order' => 'Track Your Order',
    ));
    // Guide pages (shipping, warranty, locations...) appear once approved.
    $footer_company_links += wholesale_guide_footer_links();
    $footer_legal_links = $footer_page_links(array(
        'terms-conditions' => 'Terms & Conditions',
    ));
    if (get_privacy_policy_url()) {
        $footer_legal_links[get_privacy_policy_url()] = 'Privacy Policy';
    }

    $newsletter_status = isset($_GET['newsletter']) ? sanitize_key(wp_unslash($_GET['newsletter'])) : '';
    $newsletter_messages = array(
        'success' => array('success', 'Thank you! Your email has been added to our newsletter list.'),
        'exists' => array('info', 'This email is already subscribed to our newsletter.'),
        'invalid' => array('error', 'Please enter a valid email address.'),
        'error' => array('error', 'There was a problem saving your email. Please try again.'),
    );
    ?>

    <div class="sf-footer">
        <div class="container">
            <section class="sf-newsletter" aria-labelledby="sf-newsletter-title">
                <div class="sf-newsletter__copy">
                    <span class="sf-newsletter__icon"><?php echo $footer_icon('mail'); // Static SVG. ?></span>
                    <div>
                        <h2 id="sf-newsletter-title">Get sign deals and new product news</h2>
                        <p>Special offers, new styles and production updates. Unsubscribe anytime.</p>
                    </div>
                </div>
                <div class="sf-newsletter__actions">
                    <?php if (isset($newsletter_messages[$newsletter_status])) : ?>
                        <div class="sf-newsletter__message sf-newsletter__message--<?php echo esc_attr($newsletter_messages[$newsletter_status][0]); ?>" role="status">
                            <?php echo esc_html($newsletter_messages[$newsletter_status][1]); ?>
                        </div>
                    <?php endif; ?>
                    <form class="sf-newsletter__form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                        <?php wp_nonce_field('wholesale_newsletter_subscribe', 'newsletter_nonce'); ?>
                        <input type="hidden" name="action" value="wholesale_newsletter_subscribe">
                        <label class="screen-reader-text" for="newsletter-email">Email address</label>
                        <input id="newsletter-email" type="email" name="newsletter_email" placeholder="Your email address" autocomplete="email" required>
                        <button type="submit">Subscribe</button>
                    </form>
                </div>
            </section>

            <div class="sf-grid">
                <div class="sf-brand">
                    <a class="sf-brand__logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name') . ' home'); ?>">
                        <?php
                        $logo_id = get_theme_mod('custom_logo');
                        if ($logo_id) {
                            echo wp_get_attachment_image($logo_id, 'header-logo', false, array(
                                'alt' => get_bloginfo('name'),
                                'loading' => 'lazy',
                                'decoding' => 'async',
                                'sizes' => '260px',
                            ));
                        } else {
                            echo '<img src="' . esc_url(get_template_directory_uri() . '/img/logo.png') . '" alt="' . esc_attr(get_bloginfo('name')) . '" width="2417" height="261" loading="lazy" decoding="async">';
                        }
                        ?>
                    </a>
                    <p class="sf-brand__text">Custom LED channel letters and large format prints for storefronts across the USA, built and tested before they ship since 2002.</p>
                    <ul class="sf-badges">
                        <li><?php echo $footer_icon('check'); // Static SVG. ?>Made in USA</li>
                        <li><?php echo $footer_icon('check'); // Static SVG. ?>UL Listed</li>
                        <li><?php echo $footer_icon('check'); // Static SVG. ?>5-Year LED Warranty</li>
                    </ul>
                </div>

                <nav class="sf-col" aria-labelledby="sf-products-title">
                    <h3 class="sf-col__title" id="sf-products-title">Products</h3>
                    <?php wp_nav_menu(array(
                        'theme_location' => 'header-bottom-menu',
                        'container' => false,
                        'menu_class' => 'sf-links',
                        'depth' => 1,
                        'fallback_cb' => false,
                    )); ?>
                </nav>

                <nav class="sf-col" aria-labelledby="sf-company-title">
                    <h3 class="sf-col__title" id="sf-company-title">Company</h3>
                    <ul class="sf-links">
                        <li><a href="<?php echo esc_url(home_url('/channel-letter-builder/')); ?>">Design Your Sign</a></li>
                        <?php foreach ($footer_company_links as $url => $label) : ?>
                            <li><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="sf-review-btn" id="feedbackmodalTrigger" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                        <?php echo $footer_icon('star'); // Static SVG. ?>
                        Leave a Review
                    </button>
                </nav>

                <div class="sf-col sf-contact">
                    <h3 class="sf-col__title">Get in Touch</h3>
                    <ul class="sf-contact__list">
                        <li>
                            <span class="sf-contact__icon"><?php echo $footer_icon('phone'); // Static SVG. ?></span>
                            <span>
                                <span class="sf-contact__label">Toll free</span>
                                <a href="<?php echo esc_attr($footer_phone_href); ?>"><?php echo esc_html($footer_phone_display); ?></a>
                            </span>
                        </li>
                        <li>
                            <span class="sf-contact__icon"><?php echo $footer_icon('message'); // Static SVG. ?></span>
                            <span>
                                <span class="sf-contact__label">Cell &middot; texts welcome</span>
                                <a href="<?php echo esc_attr($footer_text_href); ?>"><?php echo esc_html($footer_text_display); ?></a>
                            </span>
                        </li>
                        <li>
                            <span class="sf-contact__icon"><?php echo $footer_icon('mail'); // Static SVG. ?></span>
                            <span>
                                <span class="sf-contact__label">Email</span>
                                <a href="mailto:<?php echo esc_attr($footer_email); ?>"><?php echo esc_html($footer_email); ?></a>
                            </span>
                        </li>
                        <li>
                            <span class="sf-contact__icon"><?php echo $footer_icon('clock'); // Static SVG. ?></span>
                            <span>
                                <span class="sf-contact__label">Customer service</span>
                                Mon&ndash;Fri, 8:00am&ndash;5:00pm PST
                                <span class="sf-contact__note"><span class="sf-live-dot" aria-hidden="true"></span>Live chat online</span>
                            </span>
                        </li>
                        <li>
                            <span class="sf-contact__icon"><?php echo $footer_icon('pin'); // Static SVG. ?></span>
                            <span>
                                <span class="sf-contact__label">Headquarters</span>
                                Triton Towers Three, 707 S. Grady Way Suite 600, Renton, WA 98057
                                <span class="sf-contact__note">Pick up is not available</span>
                            </span>
                        </li>
                    </ul>
                    <div class="sf-contact__actions">
                        <a class="sf-btn sf-btn--primary" href="<?php echo esc_attr($footer_phone_href); ?>"><?php echo $footer_icon('phone'); // Static SVG. ?>Call Now</a>
                        <a class="sf-btn sf-btn--ghost" href="<?php echo esc_attr($footer_text_href); ?>"><?php echo $footer_icon('message'); // Static SVG. ?>Send a Text</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="sf-bottom footer-cp-text-container">
            <div class="container sf-bottom__inner">
                <p class="sf-bottom__copy">&copy; <?php echo esc_html(wp_date('Y')); ?> Storefront Sign Online LLC. All rights reserved.</p>
                <?php if ($footer_legal_links) : ?>
                    <nav class="sf-bottom__links" aria-label="<?php esc_attr_e('Legal', 'litsign'); ?>">
                        <?php foreach ($footer_legal_links as $url => $label) : ?>
                            <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Review modal: main.js moves it to <body> so it opens over the viewport. -->
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