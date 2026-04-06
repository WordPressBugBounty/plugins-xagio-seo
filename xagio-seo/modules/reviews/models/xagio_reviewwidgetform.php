<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_REVIEWWIDGETFORM')) {

    class XAGIO_MODEL_REVIEWWIDGETFORM extends WP_Widget
    {

        public static function initialize()
        {
            // Check if feature is enabled
            $XAGIO_FEATURES = get_option('XAGIO_FEATURES');
            if ($XAGIO_FEATURES != FALSE && is_array($XAGIO_FEATURES)) {
                if (!in_array('reviews', $XAGIO_FEATURES)) {
                    return;
                }
            }
            if ($XAGIO_FEATURES == 'none') return;

            add_action('widgets_init', ['XAGIO_MODEL_REVIEWWIDGETFORM', 'registerWidget']);
            add_shortcode('xagio_reviews', ['XAGIO_MODEL_REVIEWS', 'reviewsDisplayShortcode']);
        }

        public static function registerWidget()
        {
            register_widget('XAGIO_MODEL_REVIEWWIDGETFORM');
        }

        function __construct()
        {
            $widget_ops = [
                'classname'   => 'XAGIO_MODEL_REVIEWWIDGETFORM',
                'description' => 'Widget that displays reviews from Xagio.',
            ];
            parent::__construct(FALSE, '[Xagio] - Display Reviews', $widget_ops);
        }

        function widget($xagio_args, $xagio_instance)
        {

            $render = function ($xagio_instance) {
                include(XAGIO_PATH . '/modules/reviews/metabox/display_reviews.php');
            };

            $render($xagio_instance);
        }

        function update($new_instance, $old_instance)
        {
            $xagio_instance = [];

            if(isset($xagio_instance['limit_reviews'])) {
                $xagio_instance['limit_reviews']        = $new_instance['limit_reviews'];
            }

            if(isset($xagio_instance['random_reviews'])) {
                $xagio_instance['random_reviews']       = $new_instance['random_reviews'];
            }

            if(isset($xagio_instance['limit_reviews_number'])) {
                $xagio_instance['limit_reviews_number'] = $new_instance['limit_reviews_number'];
            }

            if(isset($xagio_instance['aggregate_rating'])) {
                $xagio_instance['aggregate_rating']     = $new_instance['aggregate_rating'];
            }




            return $xagio_instance;
        }

        function form($xagio_instance)
        {

            $xagio_limit_reviews = '';
            if (isset($xagio_instance['limit_reviews'])) {
                if ($xagio_instance['limit_reviews'] == 1) {
                    $xagio_limit_reviews = 'checked';
                }
            }

            $limit_reviews_number = 5;
            if (isset($xagio_instance['limit_reviews_number'])) {
                if (!empty($xagio_instance['limit_reviews_number'])) {
                    $limit_reviews_number = $xagio_instance['limit_reviews_number'];
                }
            }

            $random_reviews = '';
            if (isset($xagio_instance['random_reviews'])) {
                if ($xagio_instance['random_reviews'] == 1) {
                    $random_reviews = 'checked';
                }
            }

            $aggregate_rating = '';
            if (isset($xagio_instance['aggregate_rating'])) {
                if ($xagio_instance['aggregate_rating'] == 1) {
                    $aggregate_rating = 'checked';
                }
            }

            ?>

            <p>
                <input type="checkbox" <?php echo esc_attr($xagio_limit_reviews); ?> value="1" class="checkbox"
                       id="<?php echo esc_attr($this->get_field_id('limit_reviews')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('limit_reviews')); ?>">
                <label for="<?php echo esc_attr($this->get_field_id('limit_reviews')); ?>"><b><i>Limit Displayed
                            Reviews</i></b> - <input placeholder="5"
                                                     style="width: 43px;text-align: center;padding: 0 !important;height: 21px;"
                                                     id="<?php echo esc_attr($this->get_field_id('limit_reviews_number')); ?>"
                                                     name="<?php echo esc_attr($this->get_field_name('limit_reviews_number')); ?>"
                                                     type="number" value="<?php echo esc_attr($limit_reviews_number); ?>">
            </p>

            <p>
                <input type="checkbox" <?php echo esc_attr($random_reviews); ?> value="1" class="checkbox"
                       id="<?php echo esc_attr($this->get_field_id('random_reviews')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('random_reviews')); ?>">
                <label for="<?php echo esc_attr($this->get_field_id('random_reviews')); ?>"><b><i>Display Random
                            Reviews</i></b>
            </p>

            <p>
                <input type="checkbox" <?php echo esc_attr($aggregate_rating); ?> value="1" class="checkbox"
                       id="<?php echo esc_attr($this->get_field_id('aggregate_rating')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('aggregate_rating')); ?>">
                <label for="<?php echo esc_attr($this->get_field_id('aggregate_rating')); ?>"><b><i>Display Aggregate
                            Rating on the top of Reviews</i></b>
            </p>


            <?php
        }

    }
}