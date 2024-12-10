<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$object  = $GLOBALS['wp_query']->get_queried_object();
$page_id = 0;

if (
    is_object($object)
    && !XAGIO_MODEL_SEO::is_home_static_page()
    && !XAGIO_MODEL_SEO::is_home_posts_page()
) {
    $page_id = $object->ID;
} else if (isset($isShortcode)) {
    $page_id = $post->ID;
}

// Unique Identifier
$unique_id = 'rw-display';
$ps_review = stripslashes_deep(get_option('XAGIO_REVIEW'));

// Reviews Array
$reviews = [];

// Should display Random Reviews
$shouldRandom = FALSE;
if(is_array($instance)) {
    if (@$instance['random_reviews'] == 1) {
        $shouldRandom = TRUE;
    }
} else {
    $instance = [];
}

// Get all Reviews
if (@$ps_review['settings']['per_page_reviews'] == 1) {
    $reviews = XAGIO_MODEL_REVIEWS::getReviewsForPage($page_id, NULL, $shouldRandom);
} else {
    $reviews = XAGIO_MODEL_REVIEWS::getReviewsGlobal(FALSE, $shouldRandom);
}

// Count reviews
$reviewCount = sizeof($reviews);

$ps_stars            = FALSE;
$ps_stars_percentage = FALSE;

$classes = [];
if (@$ps_review['settings']['form_labels'] == 1) {
    $classes[] = 'review-widget-labels';
}
if (@$ps_review['settings']['form_labels'] == 2) {
    $classes[] = 'review-widget-placeholders';
}
if (@$ps_review['settings']['widget_width'] == 1) {
    $classes[] = 'review-widget-auto-width';
}
if (@$ps_review['settings']['widget_theme'] == 1) {
    $classes[] = 'review-widget-flat';
}
if (@$ps_review['settings']['widget_theme'] == 2) {
    $classes[] = 'review-widget-minimal';
}
if (@$ps_review['settings']['alpha_bg'] == 1 || @$instance['alpha_mode'] == 1) {
    $classes[] = 'review-widget-alpha';
}

if (@$ps_review['settings']['popup'] == 1 || @$instance['popup_mode'] == 1) {
    $classes[] = 'review-widget-popup';
}
if (@$ps_review['settings']['alignment'] == NULL) {
    $classes[] = 'review-widget-left';
} else {
    $classes[] = 'review-widget-' . $ps_review['settings']['alignment'];
}

if (
    @$ps_review['settings']['stars_only'] == 1
    || @$instance['stars_only'] == 1
) {

    // Set the stars mode to ON
    $ps_stars = TRUE;

    // Add the stars mode class
    $classes[] = 'review-widget-stars-only';

    // Check if schema gave us the rating value already
    if (isset($GLOBALS['xagio_currentRatingValue'])) {

        $ps_stars_percentage = number_format($GLOBALS['xagio_currentRatingValue'], 0, '.', '');

    } else {

        // Nope, calculate ourselves
        $ratings = [];

        if (@$ps_review['settings']['per_page_reviews'] == 1) {
            $ratings = XAGIO_MODEL_REVIEWS::getReviewsForPage($page_id, TRUE);
        } else {
            $ratings = XAGIO_MODEL_REVIEWS::getReviewsGlobal(TRUE);
        }

        $ratingsValue = 0;
        $totalRatings = sizeof($ratings);

        foreach ($ratings as $r) {
            $ratingsValue = $ratingsValue + $r['rating'];
        }

        if (!empty($ratingsValue)) {
            $ps_stars_percentage = number_format((($ratingsValue / $totalRatings) / 5) * 100, 0, '.', '');
        }
    }

    if (empty($ps_stars_percentage)) {
        $ps_stars_percentage = FALSE;
    }

}


// Merge all the classes
$classes = join(' ', $classes);
?>

<div class="<?php echo esc_attr($unique_id); ?>">

    <input type="hidden" name="XAGIO_REVIEW[fields]"
           value="<?php echo (@$ps_review['fields'] != NULL) ? esc_attr($ps_review['fields']) : '' ?>"/>

    <?php if (@$ps_review['settings']['popup'] == 1 || @$instance['popup_mode'] == 1) { ?>
        <div class="review-widget-popup-container"></div>
    <?php } ?>

    <?php if (!isset($isShortcode)) { ?>
    <aside class="widget">
        <?php } ?>

        <div class="review-widget <?php echo esc_attr($classes); ?>">
            <form class="ps-submit-review">

                <!-- Stars Only -->
                <?php if ($ps_stars === TRUE) { ?>
                    <input type="hidden" name="stars_only" value="1"/>
                <?php } else { ?>
                    <input type="hidden" name="stars_only" value="0"/>
                <?php } ?>

                <!-- Action -->
                <input type="hidden" name="action" value="xagio_newReview"/>

                <?php wp_nonce_field('xagio_nonce', '_xagio_nonce'); ?>

                <!-- Page ID -->
                <input type="hidden" name="page_id" value="<?php echo absint($page_id) ?>"/>

                <div class="review-widget-title">
                    <h2><?php echo (@$ps_review['details']['title'] == NULL) ? 'Leave a Review' : esc_html(@$ps_review['details']['title']); ?></h2>
                </div>
                <div class="review-widget-text"><?php echo (@$ps_review['details']['text'] == NULL) ? 'Please be kind and leave us a review!' : esc_html(@$ps_review['details']['text']); ?></div>

                <div class="review-widget-stars-ratings-sum">
                    <?php if ($ps_stars_percentage !== FALSE) {
                        $ratingValue = 0;

                        foreach ($reviews as $r) {
                            $ratingValue = $ratingValue + $r['rating'];
                        }

                        if (!empty($reviewCount)) {

                            $ratingValue = $ratingValue / $reviewCount;
                            $ratingValue = number_format($ratingValue, 1);

                            $displayReviewsHeading = (@$ps_review['details']['rating_text'] == NULL) ? '' : str_replace('{num}', '<b>' . $ps_stars_percentage . '%</b> ', @$ps_review['details']['rating_text']);
                            $displayReviewsHeading = str_replace('{calc}', '<b>' . $ratingValue . "</b>", $displayReviewsHeading);
                            $displayReviewsHeading = str_replace('{sum}', '<b>' . $reviewCount . '</b>', $displayReviewsHeading);
                        }
                        ?>
                        <?php echo esc_html($displayReviewsHeading); ?>
                    <?php } else { ?>
                        <?php echo (@$ps_review['details']['no_ratings_message'] == NULL) ? 'Nobody yet left a rating. Be first?' : esc_html(@$ps_review['details']['no_ratings_message']); ?>
                    <?php } ?>
                </div>

                <div class="review-widget-block-container">

                </div>

                <button class="review-widget-button" type="submit"><i class="xagio-icon xagio-icon-send"></i> <?php echo (@$ps_review['details']['button_title'] == NULL) ? 'Submit Review' : esc_html(@$ps_review['details']['button_title']); ?>
                </button>

                <span class="review-widget-stars-ratings-info">
            <?php echo (@$ps_review['details']['rating_info'] == NULL) ? 'Click a star to add your rating' : esc_html(@$ps_review['details']['rating_info']); ?>
        </span>

            </form>
        </div>

        <?php if (!isset($isShortcode)) { ?>
    </aside>
<?php } ?>

    <?php if (@$ps_review['settings']['popup'] == 1 || @$instance['popup_mode'] == 1) { ?>
        <?php if (!isset($isShortcode)) { ?>
            <aside class="widget">
        <?php } ?>
        <?php if (@$ps_review['settings']['popup_text'] == 1 || @$instance['popup_text'] == 1) { ?>
            <a href="#" id="review-widget-popup-button"
               class="<?php echo (@$ps_review['settings']['exit_popup'] == 1 || @$instance['exit_popup'] == 1) ? 'exit-popup-window' : ''; ?>"><i
                        class="xagio-icon xagio-icon-external-link"></i> <?php echo (@$ps_review['details']['popup_button_title'] == NULL) ? 'Leave a Review' : esc_html(@$ps_review['details']['popup_button_title']); ?>
            </a>
        <?php } else { ?>
            <button type="button" id="review-widget-popup-button"
                    class="<?php echo (@$ps_review['settings']['exit_popup'] == 1 || @$instance['exit_popup'] == 1) ? 'exit-popup-window' : ''; ?>">
                <i class="xagio-icon xagio-icon-external-link"></i> <?php echo (@$ps_review['details']['popup_button_title'] == NULL) ? 'Leave a Review' : esc_html(@$ps_review['details']['popup_button_title']); ?>
            </button>
        <?php } ?>
        <?php if (!isset($isShortcode)) { ?>
            </aside>
        <?php } ?>
    <?php } ?>

</div>
