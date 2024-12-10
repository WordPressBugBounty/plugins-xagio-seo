<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Get the Page ID
$object  = $GLOBALS['wp_query']->get_queried_object();
$page_id = 0;

if (is_object($object) && !XAGIO_MODEL_SEO::is_home_static_page() && !XAGIO_MODEL_SEO::is_home_posts_page()) {
    $page_id = $object->ID;
} else if (isset($isShortcode)) {
    $page_id = $post->ID;
}

// Review Settings
$ps_review = get_option('XAGIO_REVIEW');

// Reviews Array
$reviews = [];

// Should display Random Reviews
$shouldRandom = FALSE;
if(is_array($instance)){
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

// Should Limit Reviews
$limit_reviews = FALSE;
if (@$instance['limit_reviews'] == 1 && @$instance['limit_reviews_number'] > 0) {
    $limit_reviews = $instance['limit_reviews_number'];
}

// Should display AggregateRating
$displayReviewsHeading = NULL;
if (@$instance['aggregate_rating'] == 1) {

    $ratingValue = 0;

    foreach ($reviews as $r) {
        $ratingValue = $ratingValue + $r['rating'];
    }

    if (!empty($reviewCount)) {

        $ratingValue = $ratingValue / $reviewCount;
        $ratingValue = number_format($ratingValue, 1);

        $displayReviewsHeading = (@$ps_review['details']['display_reviews_text'] == NULL) ? '{calc} Rating From {sum} Reviews.' : @$ps_review['details']['display_reviews_text'];
        $displayReviewsHeading = str_replace('{calc}', '<b>' . $ratingValue . "</b>", $displayReviewsHeading);
        $displayReviewsHeading = str_replace('{sum}', '<b>' . $reviewCount . '</b>', $displayReviewsHeading);

    }
}

$displayReviewsTitle = (@$ps_review['details']['display_reviews_heading'] == NULL) ? NULL : @$ps_review['details']['display_reviews_heading'];


?>

<?php if (!isset($isShortcode)) { ?>
<aside class="widget">
    <?php } ?>

    <div class="prs-review-display-container">

        <?php if ($displayReviewsTitle !== NULL) { ?>
            <div class="prs-review-display-heading">
                <h2><?php echo esc_html($displayReviewsTitle); ?></h2>
            </div>
        <?php } ?>

        <?php if ($displayReviewsHeading !== NULL) { ?>
            <div class="prs-review-container-aggregate"><?php echo esc_html($displayReviewsHeading); ?></div>
        <?php } ?>

        <!-- Display Reviews -->
        <?php foreach ($reviews as $r) {

            // Date
            $date = gmdate('d, M Y', strtotime($r['date']));

            // Stars
            $full_stars  = $r['rating'];
            $empty_stars = 5 - $r['rating'];

            // Review Author
            $name = '';
            if (!empty($r['name'])) $name = '<b>' . stripslashes($r['name']) . '</b> <br>';
            if (!empty($r['website'])) $name = '<a href="' . $r['website'] . '" target="_blank">' . $name . '</a>';
            if (!empty($r['age'])) $name .= $r['age'];
            if (!empty($r['location'])) $name .= ' from ' . $r['location'];
            if (!empty($r['email'])) $name .= ' (<a style="color: blue;" href="mailto:' . $r['email'] . '" target="_blank"><i class="xagio-icon xagio-icon-at"></i></a>)';
            if (!empty($r['telephone'])) $name .= ' (<a style="color: blue;" href="tel:' . $r['telephone'] . '" target="_blank"><i class="xagio-icon xagio-icon-phone"></i></a>)';

            // Limit Reviews
            $limit_class = '';
            if ($limit_reviews !== FALSE) {
                if ($limit_reviews === 0) {
                    $limit_class = 'review-hidden';
                } else {
                    $limit_reviews--;
                }
            }

            ?>

            <div class="prs-review-container <?php echo esc_html($limit_class); ?>">

                <?php if (!empty($r['title'])) { ?>
                    <div class="prs-review-title"><?php echo esc_html($r['title']); ?></div>
                <?php } ?>

                <div class="prs-review-spacer"><i class="xagio-icon xagio-icon-quote"></i></div>

                <!-- Print Stars -->
                <div class="prs-review-stars">

                    <?php for ($i = 0; $i < $full_stars; $i++) { ?>
                        <i class="xagio-icon xagio-icon-star"></i>
                    <?php } ?>

                    <?php for ($i = 0; $i < $empty_stars; $i++) { ?>
                        <i class="xagio-icon xagio-icon-star-o"></i>
                    <?php } ?>

                    <span class="prs-review-date"> on <?php echo esc_html($date); ?></span>

                </div>

                <?php if (!empty($r['review'])) { ?>
                    <div class="prs-review-body"><?php echo esc_html(stripslashes($r['review'])); ?></div>
                <?php } ?>

                <div class="prs-review-author"><?php echo esc_html(stripslashes($name)); ?></div>

            </div>

        <?php } ?>

        <?php if ($limit_reviews !== FALSE && !empty($reviewCount)) { ?>
            <div class="prs-review-more"><a href="#" class="prs-show-reviews"><i class="xagio-icon xagio-icon-arrow-down"></i> <span>Show more</span></a>
            </div>
        <?php } ?>


        <!-- No Reviews Message -->
        <?php
        if (empty($reviews)) {
            if (isset($ps_review['details'])) {
                if (isset($ps_review['details']['no_reviews_message'])) {
                    if (!empty($ps_review['details']['no_reviews_message'])) {
                        echo '<p>' . esc_html($ps_review['details']['no_reviews_message']) . '</p>';
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