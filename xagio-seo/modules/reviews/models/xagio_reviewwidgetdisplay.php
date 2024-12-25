<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_REVIEWWIDGETDISPLAY')) {

    class XAGIO_MODEL_REVIEWWIDGETDISPLAY extends WP_Widget
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

            add_action('widgets_init', ['XAGIO_MODEL_REVIEWWIDGETDISPLAY', 'registerWidget']);
            add_shortcode('xagio_reviews_widget', ['XAGIO_MODEL_REVIEWS', 'reviewsWidgetShortcode']);
        }

        public static function registerWidget()
        {
            register_widget('XAGIO_MODEL_REVIEWWIDGETDISPLAY');
        }

        function __construct()
        {
            $widget_ops = [
                'classname'   => 'XAGIO_MODEL_REVIEWWIDGETDISPLAY',
                'description' => 'Form for submitting reviews for your website.',
            ];
            parent::__construct(FALSE, '[Xagio] - Review Widget', $widget_ops);
        }

        function widget($args, $instance)
        {

            $render = function ($args, $instance) {
                include(XAGIO_PATH . '/modules/reviews/metabox/review_form.php');
            };

            $render($args, $instance);
        }

        function update($new_instance, $old_instance)
        {
            $instance = [];

            if(isset($instance['popup_mode'])) {
                $instance['popup_mode'] = $new_instance['popup_mode'];
            }

            if(isset($instance['stars_only'])) {
                $instance['stars_only'] = $new_instance['stars_only'];
            }

            if(isset($instance['alpha_mode'])) {
                $instance['alpha_mode'] = $new_instance['alpha_mode'];
            }

            return $instance;
        }

        function form($instance)
        {

            $popup_mode = '';
            if (isset($instance['popup_mode'])) {
                if ($instance['popup_mode'] == 1) {
                    $popup_mode = 'checked';
                }
            }

            $alpha_mode = '';
            if (isset($instance['alpha_mode'])) {
                if ($instance['alpha_mode'] == 1) {
                    $alpha_mode = 'checked';
                }
            }

            $stars_only = '';
            if (isset($instance['stars_only'])) {
                if ($instance['stars_only'] == 1) {
                    $stars_only = 'checked';
                }
            }

            ?>

            <p>
                <input type="checkbox" <?php echo esc_attr($popup_mode); ?> value="1" class="checkbox"
                       id="<?php echo esc_attr($this->get_field_id('alpha_mode')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('alpha_mode')); ?>">
                <label for="<?php echo esc_attr($this->get_field_id('alpha_mode')); ?>"><b><i>Alpha Mode</i></b>
            </p>

            <p>
                <input type="checkbox" <?php echo esc_attr($popup_mode); ?> value="1" class="checkbox"
                       id="<?php echo esc_attr($this->get_field_id('popup_mode')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('popup_mode')); ?>">
                <label for="<?php echo esc_attr($this->get_field_id('popup_mode')); ?>"><b><i>Popup Mode</i></b>
            </p>

            <p>
                <input type="checkbox" <?php echo esc_attr($stars_only); ?> value="1" class="checkbox"
                       id="<?php echo esc_attr($this->get_field_id('stars_only')); ?>"
                       name="<?php echo esc_attr($this->get_field_name('stars_only')); ?>">
                <label for="<?php echo esc_attr($this->get_field_id('stars_only')); ?>"><b><i>Widget Ratings
                            Mode</i></b>
            </p>


            <?php

            $admin_url = admin_url();
            echo "<p>To configure more settings for Review widget, please go to <a href='".esc_url($admin_url)."admin.php?page=xagio-reviews' target='_blank'>Reviews</a> page and tweak settings accordingly. </p>";
        }

    }
}