<?php
/**
 * Template Name: Customer Reviews
 *
 * Every approved customer review (Review Submissions → Published), newest first, with
 * filters for product and star rating. Pending reviews never show here.
 *
 * @package litsign
 */

$summary = wholesale_reviews_page_summary();
$filter_product = isset($_GET['product']) ? absint($_GET['product']) : 0;
$filter_product = isset($summary['products'][$filter_product]) ? $filter_product : 0;
$filter_rating = isset($_GET['rating']) ? absint($_GET['rating']) : 0;
$filter_rating = $filter_rating >= 1 && $filter_rating <= 5 ? $filter_rating : 0;
$current_page = isset($_GET['pg']) ? max(1, absint($_GET['pg'])) : 1;
$results = wholesale_reviews_page_query($filter_product, $filter_rating, $current_page);
$google = function_exists('wholesale_google_reviews') ? wholesale_google_reviews() : null;
$filter_url = static function ($args) use ($filter_product, $filter_rating) {
    $args = array_merge(array('product' => $filter_product, 'rating' => $filter_rating), $args);
    return wholesale_reviews_page_url(array_filter($args));
};

get_header();
?>

<main id="primary" class="reviews-page">
    <div class="container">
        <header class="reviews-page-head">
            <div>
                <p class="product-section-eyebrow">Customer feedback</p>
                <h1>Customer Reviews</h1>
                <p>Reviews from businesses that ordered their signs from us. Product reviews come from customers with a completed order.</p>
            </div>
            <?php if ($summary['count']) : ?>
                <div class="reviews-page-score">
                    <strong><?php echo esc_html(number_format($summary['average'], 1)); ?></strong>
                    <?php echo wholesale_review_stars($summary['average']); ?>
                    <span><?php echo esc_html(sprintf(_n('%s review', '%s reviews', $summary['count'], 'litsign'), number_format_i18n($summary['count']))); ?></span>
                    <?php if ($google && !empty($google['count'])) : ?>
                        <a class="reviews-page-google" href="<?php echo esc_url($google['url']); ?>" target="_blank" rel="noopener">
                            <?php echo wholesale_google_logo_svg(16); ?> <?php echo esc_html(number_format((float) $google['rating'], 1)); ?> on Google (<?php echo esc_html(number_format_i18n((int) $google['count'])); ?>)
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </header>

        <?php if (!$summary['count']) : ?>
            <section class="product-reviews reviews-page-empty">
                <h2>No reviews yet</h2>
                <p>Reviews will appear here after customers share their experience and our team approves them.</p>
            </section>
        <?php else : ?>
            <div class="reviews-page-layout">
                <aside class="reviews-page-filters" aria-label="Filter reviews">
                    <h2>Rating</h2>
                    <ul class="reviews-page-bars">
                        <?php foreach ($summary['stars'] as $stars => $count) :
                            $share = $summary['count'] ? round($count / $summary['count'] * 100) : 0;
                            $is_active = $filter_rating === $stars;
                            ?>
                            <li>
                                <a class="<?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url($filter_url(array('rating' => $is_active ? 0 : $stars, 'pg' => 0))); ?>"<?php echo $is_active ? ' aria-current="true"' : ''; ?>>
                                    <span><?php echo esc_html($stars); ?> &#9733;</span>
                                    <span class="reviews-page-bar" aria-hidden="true"><span style="width:<?php echo esc_attr($share); ?>%"></span></span>
                                    <span><?php echo esc_html(number_format_i18n($count)); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($summary['products']) : ?>
                        <form method="get" action="<?php echo esc_url(wholesale_reviews_page_url()); ?>" class="reviews-page-product">
                            <label for="reviews-product">Product</label>
                            <select id="reviews-product" name="product" onchange="this.form.submit()">
                                <option value="">All products</option>
                                <?php foreach ($summary['products'] as $product_id => $title) : ?>
                                    <option value="<?php echo esc_attr($product_id); ?>" <?php selected($filter_product, $product_id); ?>><?php echo esc_html(html_entity_decode($title, ENT_QUOTES) . ' (' . $summary['product_counts'][$product_id] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($filter_rating) : ?>
                                <input type="hidden" name="rating" value="<?php echo esc_attr($filter_rating); ?>">
                            <?php endif; ?>
                            <noscript><button type="submit" class="btn btn-primary">Filter</button></noscript>
                        </form>
                    <?php endif; ?>

                    <?php if ($filter_product || $filter_rating) : ?>
                        <p><a class="reviews-page-clear" href="<?php echo esc_url(wholesale_reviews_page_url()); ?>">Clear filters</a></p>
                    <?php endif; ?>
                </aside>

                <section aria-label="Reviews">
                    <p class="reviews-page-count">
                        <?php
                        echo esc_html(sprintf(_n('Showing %s review', 'Showing %s reviews', $results['total'], 'litsign'), number_format_i18n($results['total'])));
                        if ($filter_product) {
                            echo ' for <a href="' . esc_url(get_permalink($filter_product)) . '">' . esc_html(get_the_title($filter_product)) . '</a>';
                        }
                        if ($filter_rating) {
                            echo esc_html(sprintf(' with %d stars', $filter_rating));
                        }
                        ?>
                    </p>

                    <?php if (!$results['reviews']) : ?>
                        <p class="product-review-note">No reviews match these filters.</p>
                    <?php else : ?>
                        <div class="product-review-list reviews-page-list">
                            <?php foreach ($results['reviews'] as $review) :
                                $rating = min(5, max(1, absint(get_post_meta($review->ID, '_review_rating', true))));
                                $product_id = absint(get_post_meta($review->ID, '_review_product_id', true));
                                $product_live = $product_id && 'publish' === get_post_status($product_id);
                                $title = get_post_meta($review->ID, '_review_title', true);
                                $byline = implode(' · ', array_filter(array(
                                    get_post_meta($review->ID, '_review_company', true),
                                    get_post_meta($review->ID, '_review_location', true),
                                )));
                                $verified = $product_id > 0 && '' === get_post_meta($review->ID, '_review_source', true);
                                ?>
                                <article class="product-review-card">
                                    <div class="product-review-card-header">
                                        <span class="product-review-stars" role="img" aria-label="<?php echo esc_attr($rating . ' out of 5 stars'); ?>"><?php echo esc_html(str_repeat('★', $rating) . str_repeat('☆', 5 - $rating)); ?></span>
                                        <time class="product-review-date" datetime="<?php echo esc_attr(get_the_date('Y-m-d', $review)); ?>"><?php echo esc_html(get_the_date('M j, Y', $review)); ?></time>
                                    </div>
                                    <?php if ($title) : ?>
                                        <h3 class="product-review-title"><?php echo esc_html($title); ?></h3>
                                    <?php endif; ?>
                                    <p><?php echo nl2br(esc_html($review->post_content)); ?></p>
                                    <div class="product-review-author">
                                        <strong><?php echo esc_html(wholesale_review_display_name(get_post_meta($review->ID, '_review_name', true))); ?></strong>
                                        <?php if ($byline) : ?>
                                            <span><?php echo esc_html($byline); ?></span>
                                        <?php endif; ?>
                                        <?php if ($verified) : ?>
                                            <span class="reviews-page-verified">Verified buyer</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($product_live) : ?>
                                        <a class="reviews-page-product-link" href="<?php echo esc_url(get_permalink($product_id)); ?>#product-reviews"><?php echo esc_html(get_the_title($product_id)); ?> &rarr;</a>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($results['pages'] > 1) : ?>
                            <nav class="reviews-page-pagination" aria-label="Review pages">
                                <?php for ($page = 1; $page <= $results['pages']; $page++) : ?>
                                    <?php if ($page === $current_page) : ?>
                                        <span aria-current="page"><?php echo esc_html($page); ?></span>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url($filter_url(array('pg' => $page > 1 ? $page : 0))); ?>"><?php echo esc_html($page); ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
