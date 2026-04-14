<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$xagio_object  = $GLOBALS['wp_query']->get_queried_object();
$xagio_page_id = 0;

if (
    is_object($xagio_object)
    && !XAGIO_MODEL_SEO::is_home_static_page()
    && !XAGIO_MODEL_SEO::is_home_posts_page()
) {
    $xagio_page_id = $xagio_object->ID;
} else if (isset($isShortcode)) {
    $xagio_page_id = $post->ID;
}

// Unique Identifier
$xagio_unique_id = 'rw-display';
$xagio_review = stripslashes_deep(get_option('XAGIO_REVIEW'));

// Reviews Array
$xagio_reviews = [];

// Should display Random Reviews
$xagioShouldRandom = FALSE;
if ( is_array( $xagio_instance ) ) {
    if ( ( $xagio_instance['random_reviews'] ?? 0 ) == 1 ) {
        $xagioShouldRandom = TRUE;
    }
} else {
    $xagio_instance = [];
}

// Get all Reviews
if ( ( $xagio_review['settings']['per_page_reviews'] ?? 0 ) == 1 ) {
    $xagio_reviews = XAGIO_MODEL_REVIEWS::getReviewsForPage( $xagio_page_id, NULL, $xagioShouldRandom );
} else {
    $xagio_reviews = XAGIO_MODEL_REVIEWS::getReviewsGlobal( FALSE, $xagioShouldRandom );
}

// Count reviews
$xagioReviewCount = sizeof($xagio_reviews);

$xagio_stars            = FALSE;
$xagio_stars_percentage = FALSE;

$xagio_classes = [];
if ( ( $xagio_review['settings']['form_labels'] ?? 0 ) == 1 ) {
    $xagio_classes[] = 'review-widget-labels';
}
if ( ( $xagio_review['settings']['form_labels'] ?? 0 ) == 2 ) {
    $xagio_classes[] = 'review-widget-placeholders';
}
if ( ( $xagio_review['settings']['widget_width'] ?? 0 ) == 1 ) {
    $xagio_classes[] = 'review-widget-auto-width';
}
if ( ( $xagio_review['settings']['widget_theme'] ?? 0 ) == 1 ) {
    $xagio_classes[] = 'review-widget-flat';
}
if ( ( $xagio_review['settings']['widget_theme'] ?? 0 ) == 2 ) {
    $xagio_classes[] = 'review-widget-minimal';
}
if ( ( $xagio_review['settings']['alpha_bg'] ?? 0 ) == 1 || ( $xagio_instance['alpha_mode'] ?? 0 ) == 1 ) {
    $xagio_classes[] = 'review-widget-alpha';
}
if ( ( $xagio_review['settings']['popup'] ?? 0 ) == 1 || ( $xagio_instance['popup_mode'] ?? 0 ) == 1 ) {
    $xagio_classes[] = 'review-widget-popup';
}
if ( ( $xagio_review['settings']['alignment'] ?? null ) === null ) {
    $xagio_classes[] = 'review-widget-left';
} else {
    $xagio_classes[] = 'review-widget-' . esc_attr( $xagio_review['settings']['alignment'] );
}

if (
        ( $xagio_review['settings']['stars_only'] ?? 0 ) == 1
        || ( $xagio_instance['stars_only'] ?? 0 ) == 1
) {

    // Set the stars mode to ON
    $xagio_stars = TRUE;

    // Add the stars mode class
    $xagio_classes[] = 'review-widget-stars-only';

    // Check if schema gave us the rating value already
    if (isset($GLOBALS['xagio_currentRatingValue'])) {

        $xagio_stars_percentage = number_format($GLOBALS['xagio_currentRatingValue'], 0, '.', '');

    } else {

        // Nope, calculate ourselves
        $xagio_ratings = [];

        if ( ( $xagio_review['settings']['per_page_reviews'] ?? 0 ) == 1 ) {
            $xagio_ratings = XAGIO_MODEL_REVIEWS::getReviewsForPage($xagio_page_id, TRUE);
        } else {
            $xagio_ratings = XAGIO_MODEL_REVIEWS::getReviewsGlobal(TRUE);
        }

        $xagioRatingsValue = 0;
        $xagioTotalRatings = sizeof($xagio_ratings);

        foreach ($xagio_ratings as $xagio_r) {
            $xagioRatingsValue = $xagioRatingsValue + $xagio_r['rating'];
        }

        if (!empty($xagioRatingsValue)) {
            $xagio_stars_percentage = number_format((($xagioRatingsValue / $xagioTotalRatings) / 5) * 100, 0, '.', '');
        }
    }

    if (empty($xagio_stars_percentage)) {
        $xagio_stars_percentage = FALSE;
    }

}


// Merge all the classes
$xagio_classes = join(' ', $xagio_classes);
?>

<div class="<?php echo esc_attr($xagio_unique_id); ?>">

    <input type="hidden" name="XAGIO_REVIEW[fields]"
           value="<?php echo esc_attr( $xagio_review['fields'] ?? '' ); ?>"/>

    <?php if ( ( $xagio_review['settings']['popup'] ?? 0 ) == 1 || ( $xagio_instance['popup_mode'] ?? 0 ) == 1 ) { ?>
        <div class="review-widget-popup-container"></div>
    <?php } ?>

    <?php if (!isset($isShortcode)) { ?>
    <aside class="widget">
        <?php } ?>

        <div class="review-widget <?php echo esc_attr($xagio_classes); ?>">
            <form class="ps-submit-review">

                <!-- Stars Only -->
                <?php if ($xagio_stars === TRUE) { ?>
                    <input type="hidden" name="stars_only" value="1"/>
                <?php } else { ?>
                    <input type="hidden" name="stars_only" value="0"/>
                <?php } ?>

                <!-- Action -->
                <input type="hidden" name="action" value="xagio_newReview"/>

                <?php wp_nonce_field('xagio_nonce', '_xagio_nonce'); ?>

                <!-- Page ID -->
                <input type="hidden" name="page_id" value="<?php echo absint($xagio_page_id) ?>"/>

                <div class="review-widget-title">
                    <h2><?php echo ( $xagio_review['details']['title'] ?? null ) ? esc_html( $xagio_review['details']['title'] ) : 'Leave a Review'; ?></h2>
                </div>
                <div class="review-widget-text"><?php echo ( $xagio_review['details']['text'] ?? null ) ? esc_html( $xagio_review['details']['text'] ) : 'Please be kind and leave us a review!'; ?></div>

                <div class="review-widget-stars-ratings-sum">
                    <?php if ($xagio_stars_percentage !== FALSE) {
                        $xagioRatingValue = 0;

                        foreach ($xagio_reviews as $xagio_r) {
                            $xagioRatingValue = $xagioRatingValue + $xagio_r['rating'];
                        }

                        if (!empty($xagioReviewCount)) {

                            $xagioRatingValue = $xagioRatingValue / $xagioReviewCount;
                            $xagioRatingValue = number_format($xagioRatingValue, 1);

                            $xagioDisplayReviewsHeading = (($xagio_review['details']['rating_text'] ?? null) === null) ? '' : str_replace('{num}', '<b>' . $xagio_stars_percentage . '%</b> ', $xagio_review['details']['rating_text'] ?? '');
                            $xagioDisplayReviewsHeading = str_replace('{calc}', '<b>' . $xagioRatingValue . "</b>", $xagioDisplayReviewsHeading);
                            $xagioDisplayReviewsHeading = str_replace('{sum}', '<b>' . $xagioReviewCount . '</b>', $xagioDisplayReviewsHeading);
                        }
                        ?>
                        <?php echo esc_html($xagioDisplayReviewsHeading); ?>
                    <?php } else { ?>
                        <?php echo ( $xagio_review['details']['no_ratings_message'] ?? null ) ? esc_html( $xagio_review['details']['no_ratings_message'] ) : 'Nobody yet left a rating. Be first?'; ?>
                    <?php } ?>
                </div>

                <div class="review-widget-block-container">

                </div>

                <button class="review-widget-button" type="submit"><i class="xagio-icon xagio-icon-send"></i> <?php echo ( $xagio_review['details']['button_title'] ?? null ) ? esc_html( $xagio_review['details']['button_title'] ) : 'Submit Review'; ?>
                </button>

                <span class="review-widget-stars-ratings-info">
                    <?php echo ( $xagio_review['details']['rating_info'] ?? null ) ? esc_html( $xagio_review['details']['rating_info'] ) : 'Click a star to add your rating'; ?>
                </span>

            </form>
        </div>

        <?php if (!isset($isShortcode)) { ?>
    </aside>
<?php } ?>

    <?php if (($xagio_review['settings']['popup'] ?? 0) == 1 || ($xagio_instance['popup_mode'] ?? 0) == 1) { ?>
        <?php if (!isset($isShortcode)) { ?>
            <aside class="widget">
        <?php } ?>
        <?php if (($xagio_review['settings']['popup_text'] ?? 0) == 1 || ($xagio_instance['popup_text'] ?? 0) == 1) { ?>
            <a href="#" id="review-widget-popup-button"
               class="<?php echo (($xagio_review['settings']['exit_popup'] ?? 0) == 1 || ($xagio_instance['exit_popup'] ?? 0) == 1) ? 'exit-popup-window' : ''; ?>">
                <i class="xagio-icon xagio-icon-external-link"></i>
                <?php echo ($xagio_review['details']['popup_button_title'] ?? null) === null ? 'Leave a Review' : esc_html($xagio_review['details']['popup_button_title'] ?? ''); ?>
            </a>
        <?php } else { ?>
            <button type="button" id="review-widget-popup-button"
                    class="<?php echo (($xagio_review['settings']['exit_popup'] ?? 0) == 1 || ($xagio_instance['exit_popup'] ?? 0) == 1) ? 'exit-popup-window' : ''; ?>">
                <i class="xagio-icon xagio-icon-external-link"></i>
                <?php echo ($xagio_review['details']['popup_button_title'] ?? null) === null ? 'Leave a Review' : esc_html($xagio_review['details']['popup_button_title'] ?? ''); ?>
            </button>
        <?php } ?>
        <?php if (!isset($isShortcode)) { ?>
            </aside>
        <?php } ?>
    <?php } ?>

</div>
