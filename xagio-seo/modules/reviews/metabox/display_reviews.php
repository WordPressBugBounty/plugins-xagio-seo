<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Get the Page ID
$xagio_object  = $GLOBALS['wp_query']->get_queried_object();
$xagio_page_id = 0;


if (is_object($xagio_object) && !XAGIO_MODEL_SEO::is_home_static_page() && !XAGIO_MODEL_SEO::is_home_posts_page()) {
    $xagio_page_id = $xagio_object->ID;
} else if (isset($isShortcode)) {
    $xagio_page_id = $post->ID;
}

// Review Settings
$xagio_review = get_option('XAGIO_REVIEW');
// Reviews Array
$xagio_reviews = [];

// Should display Random Reviews
$xagioShouldRandom = FALSE;
if(is_array($xagio_instance)){
    if (@$xagio_instance['random_reviews'] == 1) {
        $xagioShouldRandom = TRUE;
    }
} else {
    $xagio_instance = [];
}

// Get all Reviews
if (@$xagio_review['settings']['per_page_reviews'] == 1) {
    $xagio_reviews = XAGIO_MODEL_REVIEWS::getReviewsForPage($xagio_page_id, NULL, $xagioShouldRandom);
} else {
    $xagio_reviews = XAGIO_MODEL_REVIEWS::getReviewsGlobal(FALSE, $xagioShouldRandom);
}



// Count reviews
$xagioReviewCount = sizeof($xagio_reviews);

// Should Limit Reviews
$xagio_limit_reviews = FALSE;
if (@$xagio_instance['limit_reviews'] == 1 && @$xagio_instance['limit_reviews_number'] > 0) {
    $xagio_limit_reviews = $xagio_instance['limit_reviews_number'];
}

// Should display AggregateRating
$xagioDisplayReviewsHeading = NULL;
if (@$xagio_instance['aggregate_rating'] == 1) {

    $xagioRatingValue = 0;

    foreach ($xagio_reviews as $xagio_r) {
        $xagioRatingValue = $xagioRatingValue + $xagio_r['rating'];
    }

    if (!empty($xagioReviewCount)) {

        $xagioRatingValue = $xagioRatingValue / $xagioReviewCount;
        $xagioRatingValue = number_format($xagioRatingValue, 1);

        $xagioDisplayReviewsHeading = (@$xagio_review['details']['display_reviews_text'] == NULL) ? '{calc} Rating From {sum} Reviews.' : @$xagio_review['details']['display_reviews_text'];
        $xagioDisplayReviewsHeading = str_replace('{calc}', '<b>' . $xagioRatingValue . "</b>", $xagioDisplayReviewsHeading);
        $xagioDisplayReviewsHeading = str_replace('{sum}', '<b>' . $xagioReviewCount . '</b>', $xagioDisplayReviewsHeading);

    }
}

$xagioDisplayReviewsTitle = (@$xagio_review['details']['display_reviews_heading'] == NULL) ? NULL : @$xagio_review['details']['display_reviews_heading'];


?>

<?php if (!isset($isShortcode)) { ?>
<aside class="widget">
    <?php } ?>

    <div class="prs-review-display-container">

        <?php if ($xagioDisplayReviewsTitle !== NULL) { ?>
            <div class="prs-review-display-heading">
                <h2><?php echo esc_html($xagioDisplayReviewsTitle); ?></h2>
            </div>
        <?php } ?>

        <?php if ($xagioDisplayReviewsHeading !== NULL) { ?>
            <div class="prs-review-container-aggregate"><?php echo wp_kses_post($xagioDisplayReviewsHeading); ?></div>
        <?php } ?>

        <!-- Display Reviews -->
        <?php foreach ($xagio_reviews as $xagio_r) {

            // Date
            $xagio_date = gmdate('d, M Y', strtotime($xagio_r['date']));

            // Stars
            $xagio_full_stars  = $xagio_r['rating'];
            $xagio_empty_stars = 5 - $xagio_r['rating'];

            // Review Author
            $xagio_name = '';
            if (!empty($xagio_r['name'])) $xagio_name = '<b>' . stripslashes($xagio_r['name']) . '</b> <br>';
            if (!empty($xagio_r['website'])) $xagio_name = '<a href="' . $xagio_r['website'] . '" target="_blank">' . $xagio_name . '</a>';
            if (!empty($xagio_r['age'])) $xagio_name .= $xagio_r['age'];
            if (!empty($xagio_r['location'])) $xagio_name .= ' from ' . $xagio_r['location'];
            if (!empty($xagio_r['email'])) $xagio_name .= ' (<a style="color: blue;" href="mailto:' . $xagio_r['email'] . '" target="_blank"><i class="xagio-icon xagio-icon-at"></i></a>)';
            if (!empty($xagio_r['telephone'])) $xagio_name .= ' (<a style="color: blue;" href="tel:' . $xagio_r['telephone'] . '" target="_blank"><i class="xagio-icon xagio-icon-phone"></i></a>)';

            // Limit Reviews
            $xagio_limit_class = '';
            if ($xagio_limit_reviews !== FALSE) {
                if ($xagio_limit_reviews === 0) {
                    $xagio_limit_class = 'review-hidden';
                } else {
                    $xagio_limit_reviews--;
                }
            }

            ?>

            <div class="prs-review-container <?php echo esc_html($xagio_limit_class); ?>">

                <?php if (!empty($xagio_r['title'])) { ?>
                    <div class="prs-review-title"><?php echo esc_html($xagio_r['title']); ?></div>
                <?php } ?>

                <div class="prs-review-spacer"><i class="xagio-icon xagio-icon-quote"></i></div>

                <!-- Print Stars -->
                <div class="prs-review-stars">

                    <?php for ($xagio_i = 0; $xagio_i < $xagio_full_stars; $xagio_i++) { ?>
                        <i class="xagio-icon xagio-icon-star"></i>
                    <?php } ?>

                    <?php for ($xagio_i = 0; $xagio_i < $xagio_empty_stars; $xagio_i++) { ?>
                        <i class="xagio-icon xagio-icon-star-o"></i>
                    <?php } ?>

                    <span class="prs-review-date"> on <?php echo esc_html($xagio_date); ?></span>

                </div>

                <?php if (!empty($xagio_r['review'])) { ?>
                    <div class="prs-review-body"><?php echo esc_html(stripslashes($xagio_r['review'])); ?></div>
                <?php } ?>

                <div class="prs-review-author"><?php echo wp_kses_post(stripslashes($xagio_name)); ?></div>

            </div>

        <?php } ?>

        <?php if ($xagio_limit_reviews !== FALSE && !empty($xagioReviewCount)) { ?>
            <div class="prs-review-more"><a href="#" class="prs-show-reviews"><i class="xagio-icon xagio-icon-arrow-down"></i> <span>Show more</span></a>
            </div>
        <?php } ?>


        <!-- No Reviews Message -->
        <?php
        if (empty($xagio_reviews)) {
            if (isset($xagio_review['details'])) {
                if (isset($xagio_review['details']['no_reviews_message'])) {
                    if (!empty($xagio_review['details']['no_reviews_message'])) {
                        echo '<p>' . esc_html($xagio_review['details']['no_reviews_message']) . '</p>';
                    } else {
                        echo '<p><i class="xagio-icon xagio-icon-frown"></i> Nobody yet left a review. Be first?</p>';
                    }
                } else {
                    echo '<p><i class="xagio-icon xagio-icon-frown"></i> Nobody yet left a review. Be first?</p>';
                }
            } else {
                echo '<p><i class="xagio-icon xagio-icon-frown"></i> Nobody yet left a review. Be first?</p>';
            }
        }
        ?>

    </div>

    <?php if (!isset($isShortcode)) { ?>
</aside>
<?php } ?>