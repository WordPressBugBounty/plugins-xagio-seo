<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_REVIEWS')) {

    class XAGIO_MODEL_REVIEWS
    {

        private static function defines()
        {
            define('XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS', filter_var(get_option('XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS'), FILTER_VALIDATE_BOOLEAN));
        }

        public static function initialize()
        {
            XAGIO_MODEL_REVIEWS::defines();

            add_action('admin_enqueue_scripts', [
                'XAGIO_MODEL_REVIEWS',
                'loadAdminAssets'
            ], 10, 1);
            add_action('wp_enqueue_scripts', [
                'XAGIO_MODEL_REVIEWS',
                'loadUserAssets'
            ], 10, 1);

            add_action('admin_post_nopriv_xagio_newReview', [
                'XAGIO_MODEL_REVIEWS',
                'newReview'
            ]);

            if (!XAGIO_HAS_ADMIN_PERMISSIONS) return;

            add_action('admin_post_xagio_bulkReviews', [
                'XAGIO_MODEL_REVIEWS',
                'bulkReview'
            ]);
            add_action('admin_post_xagio_unapproveReview', [
                'XAGIO_MODEL_REVIEWS',
                'unapproveReview'
            ]);
            add_action('admin_post_xagio_approveReview', [
                'XAGIO_MODEL_REVIEWS',
                'approveReview'
            ]);
            add_action('admin_post_xagio_cloneReview', [
                'XAGIO_MODEL_REVIEWS',
                'cloneReview'
            ]);
            add_action('admin_post_xagio_removeReview', [
                'XAGIO_MODEL_REVIEWS',
                'removeReview'
            ]);
            add_action('admin_post_xagio_getReviews', [
                'XAGIO_MODEL_REVIEWS',
                'getReviews_Datatables'
            ]);
            add_action('admin_post_xagio_getReview', [
                'XAGIO_MODEL_REVIEWS',
                'getReview'
            ]);
            add_action('admin_post_xagio_editReview', [
                'XAGIO_MODEL_REVIEWS',
                'editReview'
            ]);
            add_action('admin_post_xagio_newReview', [
                'XAGIO_MODEL_REVIEWS',
                'newReview'
            ]);
            add_action('admin_post_xagio_saveReviewWidget', [
                'XAGIO_MODEL_REVIEWS',
                'saveReviewWidget'
            ]);

        }

        // Enqueue scripts admin
        public static function loadAdminAssets($xagio_hook)
        {
            if ($xagio_hook === 'xagio_page_xagio-reviews') {
                wp_enqueue_style('xagio_review_widget_form');
            }
        }

        // Enqueue scripts user
        public static function loadUserAssets()
        {
            $xagio_url = xagio_get_model_url(__FILE__);

            /** Load global CSS */
            wp_register_style('xagio_icons', XAGIO_URL . 'assets/css/icons.css', [], XAGIO_CURRENT_VERSION);

            if (XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS == FALSE || XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS == 0) {
                wp_register_script('xagio_review_widget_form', $xagio_url . 'review_widget_form.js', ['jquery'], XAGIO_CURRENT_VERSION, TRUE);
            }

            if (XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS == FALSE || XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS == 0) {
                wp_register_style('xagio_review_widget_form', $xagio_url . 'review_widget_form.css', [], XAGIO_CURRENT_VERSION);
                wp_register_style('xagio_review_widget_display', $xagio_url . 'review_widget_display.css', [], XAGIO_CURRENT_VERSION);
            }

            // Enqueue Scripts
            wp_enqueue_script('xagio_review_widget_form');

            // Enqueue Styles
            wp_enqueue_style('xagio_icons');
            wp_enqueue_style('xagio_review_widget_form');
            wp_enqueue_style('xagio_review_widget_display');

            $xagio_unique_id = 'rw-display';
            $xagio_review = stripslashes_deep(get_option('XAGIO_REVIEW'));

	        $style = "
		    ." . esc_attr( $xagio_unique_id ) . " .review-widget {
		        " . ( ( $xagio_review['colors']['background'] ?? null ) ? "background: " . esc_attr( $xagio_review['colors']['background'] ) . ";" : "" ) . "
		        " . ( ( $xagio_review['colors']['text'] ?? null ) ? "color: " . esc_attr( $xagio_review['colors']['text'] ) . ";" : "" ) . "
		        " . ( ( $xagio_review['colors']['border'] ?? null ) ? "border-color: " . esc_attr( $xagio_review['colors']['border'] ) . ";" : "" ) . "
		        " . ( ( $xagio_review['padding']['widget'] ?? null ) ? "padding: " . esc_attr( $xagio_review['padding']['widget'] ) . "px;" : "" ) . "
		    }
		
		    ." . esc_html( $xagio_unique_id ) . " .review-widget-button {
		        " . ( ( $xagio_review['colors']['button_background'] ?? null ) ? "background: " . esc_attr( $xagio_review['colors']['button_background'] ) . ";" : "" ) . "
		        " . ( ( $xagio_review['colors']['button_text'] ?? null ) ? "color: " . esc_attr( $xagio_review['colors']['button_text'] ) . ";" : "" ) . "
		    }
		
		    ." . esc_html( $xagio_unique_id ) . " .review-widget-label, .review-widget-title > h2 {
		        " . ( ( $xagio_review['colors']['text'] ?? null ) ? "color: " . esc_attr( $xagio_review['colors']['text'] ) . ";" : "" ) . "
		    }
		
		    ." . esc_html( $xagio_unique_id ) . " .review-widget-label {
		        " . ( ( $xagio_review['font_size']['label'] ?? null ) ? "font-size: " . absint( $xagio_review['font_size']['label'] ) . "px;" : "" ) . "
		    }
		
		    ." . esc_html( $xagio_unique_id ) . " .review-widget-title > h2 {
		        " . ( ( $xagio_review['font_size']['heading'] ?? null ) ? "font-size: " . absint( $xagio_review['font_size']['heading'] ) . "px;" : "" ) . "
		    }
		
		    ." . esc_html( $xagio_unique_id ) . " .review-widget-text {
		        " . ( ( $xagio_review['font_size']['subheading'] ?? null ) ? "font-size: " . absint( $xagio_review['font_size']['subheading'] ) . "px;" : "" ) . "
		    }
		
		    ." . esc_html( $xagio_unique_id ) . " .review-widget-stars-ratings-sum {
		        " . ( ( $xagio_review['colors']['rating_heading'] ?? null ) ? "color: " . esc_attr( $xagio_review['colors']['rating_heading'] ) . ";" : "" ) . "
		        " . ( ( $xagio_review['details']['rating_heading_size'] ?? null ) ? "font-size: " . absint( $xagio_review['details']['rating_heading_size'] ) . "px;" : "" ) . "
		    }
		
		    ." . esc_html( $xagio_unique_id ) . " .review-widget-stars-ratings-info {
		        " . ( ( $xagio_review['colors']['rating_info'] ?? null ) ? "color: " . esc_attr( $xagio_review['colors']['rating_info'] ) . ";" : "" ) . "
		        " . ( ( $xagio_review['details']['rating_instruction_size'] ?? null ) ? "font-size: " . absint( $xagio_review['details']['rating_instruction_size'] ) . "px;" : "" ) . "
		    }
		
		    ." . esc_html( $xagio_unique_id ) . " .review-widget-input {
		        " . ( ( $xagio_review['colors']['input_background'] ?? null ) ? "background: " . esc_attr( $xagio_review['colors']['input_background'] ) . ";" : "" ) . "
		        " . ( ( $xagio_review['colors']['input_text'] ?? null ) ? "color: " . esc_attr( $xagio_review['colors']['input_text'] ) . ";" : "" ) . "
		        " . ( ( $xagio_review['font_size']['input'] ?? null ) ? "font-size: " . absint( $xagio_review['font_size']['input'] ) . "px;" : "" ) . "
		        " . ( ( $xagio_review['padding']['input'] ?? null ) ? "padding: " . absint( $xagio_review['padding']['input'] ) . "px;" : "" ) . "
		    }
		
		    ." . esc_html( $xagio_unique_id ) . " .review-widget-stars i {
		        " . ( ( $xagio_review['colors']['stars'] ?? null ) ? "color: " . esc_attr( $xagio_review['colors']['stars'] ) . ";" : "" ) . "
		        " . ( ( $xagio_review['font_size']['stars'] ?? null ) ? "font-size: " . absint( $xagio_review['font_size']['stars'] ) . "px !important;" : "" ) . "
		    }
			";

			        $style .= "
		    " . ( ( $xagio_review['details']['heading_size'] ?? null ) ? "
		    .prs-review-display-container .prs-review-display-heading {
		        font-size: " . absint( $xagio_review['details']['heading_size'] ) . "px !important;
		    }
		    " : "" ) . "
		
		    " . ( ( $xagio_review['details']['subheading_size'] ?? null ) ? "
		    .prs-review-display-container .prs-review-container-aggregate {
		        font-size: " . absint( $xagio_review['details']['subheading_size'] ) . "px !important;
		    }
		    " : "" ) . "
		
		    " . ( ( $xagio_review['colors_display']['background'] ?? null ) ? "
		    .prs-review-display-container .prs-review-container-aggregate,
		    .prs-review-display-container .prs-review-container {
		        background: " . esc_attr( $xagio_review['colors_display']['background'] ) . " !important;
		    }
		    " : "" ) . "
		
		    " . ( ( $xagio_review['colors_display']['border'] ?? null ) ? "
		    .prs-review-display-container .prs-review-container-aggregate,
		    .prs-review-display-container .prs-review-container {
		        border-color: " . esc_attr( $xagio_review['colors_display']['border'] ) . " !important;
		    }
		    " : "" ) . "
		
		    " . ( ( $xagio_review['colors_display']['stars'] ?? null ) ? "
		    .prs-review-display-container .prs-review-container .prs-review-stars i {
		        color: " . esc_attr( $xagio_review['colors_display']['stars'] ) . " !important;
		        " . ( ( $xagio_review['details']['display_star_size'] ?? null ) ? "font-size: " . absint( $xagio_review['details']['display_star_size'] ) . "px !important;" : "" ) . "
		    }
		    " : "" ) . "
		
		    " . ( ( $xagio_review['colors_display']['text'] ?? null ) ? "
		    .prs-review-display-container .prs-review-container-aggregate,
		    .prs-review-display-container .prs-review-container,
		    .prs-review-display-container .prs-review-display-heading h2 {
		        color: " . esc_attr( $xagio_review['colors_display']['text'] ) . " !important;
		    }
		    " : "" ) . "
			";
	        wp_add_inline_style('xagio_review_widget_display', $style);

            $xagio_stars            = FALSE;
            $xagio_stars_percentage = FALSE;

	        if (
		        ( $xagio_review['settings']['stars_only'] ?? 0 ) == 1
	        ) {

		        // Set the stars mode to ON
		        $xagio_stars = TRUE;

		        // Check if schema gave us the rating value already
		        if ( isset( $GLOBALS['xagio_currentRatingValue'] ) ) {

			        $xagio_stars_percentage = number_format( $GLOBALS['xagio_currentRatingValue'], 0, '.', '' );

		        } else {

			        // Nope, calculate ourselves
			        $xagio_ratings = [];

			        if ( ( $xagio_review['settings']['per_page_reviews'] ?? 0 ) == 1 ) {
				        $xagio_ratings = XAGIO_MODEL_REVIEWS::getReviewsForPage( $xagio_page_id, TRUE );
			        } else {
				        $xagio_ratings = XAGIO_MODEL_REVIEWS::getReviewsGlobal( TRUE );
			        }

			        $xagioRatingsValue = 0;
			        $xagioTotalRatings = is_array( $xagio_ratings ) ? sizeof( $xagio_ratings ) : 0;

			        foreach ( $xagio_ratings as $xagio_r ) {
				        $xagioRatingsValue = $xagioRatingsValue + $xagio_r['rating'];
			        }

			        if ( ! empty( $xagioRatingsValue ) ) {
				        $xagio_stars_percentage = number_format( ( ( $xagioRatingsValue / $xagioTotalRatings ) / 5 ) * 100, 0, '.', '' );
			        }
		        }

		        if ( empty( $xagio_stars_percentage ) ) {
			        $xagio_stars_percentage = FALSE;
		        }
	        }

			// Prepare the data to be passed to the JavaScript file
	        $localized_data = array(
		        'unique_id'          => esc_attr( $xagio_unique_id ),
		        'ps_admin_url'       => esc_url( admin_url() . 'admin-post.php' ),
		        'ps_thank_you'       => ( $xagio_review['details']['thank_you'] ?? null ) ? esc_html( $xagio_review['details']['thank_you'] ) : 'Thank you for leaving us a review!',
		        'ps_rating_thank_you'=> ( $xagio_review['details']['rating_thank_you'] ?? null ) ? esc_html( $xagio_review['details']['rating_thank_you'] ) : 'Thank you for leaving a rating!',
		        'ps_stars_only'      => esc_html( $xagio_stars ),
		        'ps_stars_init'      => esc_html( $xagio_stars_percentage )
	        );

	        // Localize the script with the data
            wp_localize_script('xagio_review_widget_form', 'xagio_review_data', $localized_data);

        }

        public static function allowedReviewSchemas()
        {
            return [
                'Brand',
                'Product',
                'LocalBusiness',
                'Organization',
                'AccountingService',
                'Attorney',
                'AutoBodyShop',
                'AutoDealer',
                'AutoPartsStore',
                'AutoRental',
                'AutoRepair',
                'AutoWash',
                'Article',
                'Bakery',
                'BarOrPub',
                'BeautySalon',
                'BedAndBreakfast',
                'BikeStore',
                'BookStore',
                'CafeOrCoffeeShop',
                'ChildCare',
                'ClothingStore',
                'ComputerStore',
                'DaySpa',
                'Dentist',
                'DryCleaningOrLaundry',
                'Electrician',
                'ElectronicsStore',
                'EmergencyService',
                'EntertainmentBusiness',
                'EventVenue',
                'ExerciseGym',
                'FinancialService',
                'Florist',
                'FoodEstablishment',
                'FurnitureStore',
                'GardenStore',
                'GeneralContractor',
                'GolfCourse',
                'HVACBusiness',
                'HairSalon',
                'HardwareStore',
                'HealthAndBeautyBusiness',
                'HobbyShop',
                'HobbyShop or Store',
                'HomeAndConstructionBusiness',
                'HomeGoodsStore',
                'Hospital',
                'Hotel',
                'HousePainter',
                'InsuranceAgency',
                'JewelryStore',
                'LiquorStore',
                'Locksmith',
                'LodgingBusiness',
                'MedicalClinic',
                'MensClothingStore',
                'MobilePhoneStore',
                'Motel',
                'MotorcycleDealer',
                'MotorcycleRepair',
                'MovingCompany',
                'MusicStore',
                'NailSalon',
                'NightClub',
                'Notary',
                'OfficeEquipmentStore',
                'Optician',
                'PetStore',
                'Physician',
                'Plumber',
                'ProfessionalService',
                'RVPark',
                'RealEstateAgent',
                'Residence',
                'Restaurant',
                'RoofingContractor',
                'School',
                'See alternate instructions',
                'SelfStorage',
                'ShoeStore',
                'SkiResort',
                'SportingGoodsStore',
                'SportsClub',
                'Store',
                'TattooParlor',
                'Taxi',
                'TennisComplex',
                'TireShop',
                'ToyStore',
                'TravelAgency',
                'VeterinaryCare',
                'WholesaleStore',
                'Winery',
            ];
        }

        public static function reviewsWidgetShortcode($xagio_instance)
        {

            $render = function ($xagio_instance) {
                global $post;
                $isShortcode = TRUE;
                ob_start();
                include(XAGIO_PATH . '/modules/reviews/metabox/review_form.php');
                return ob_get_clean();
            };

            return $render($xagio_instance);
        }

        public static function reviewsDisplayShortcode($xagio_instance)
        {

            $render = function ($xagio_instance) {
                global $post;
                $isShortcode = TRUE;
                ob_start();
                include(XAGIO_PATH . '/modules/reviews/metabox/display_reviews.php');
                return ob_get_clean();
            };

            return $render($xagio_instance);

        }

        public static function saveReviewWidget()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['XAGIO_REVIEW'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            wp_cache_delete('alloptions', 'options');

            if (!isset($_POST['XAGIO_REVIEW'])) {
                xagio_json('error', 'Sorry, something went wrong. Please try again!');
            }

			if (isset($_POST['XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS'])) {
				update_option('XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS', $_POST['XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS']);
			}

            $xagio_review = map_deep(wp_unslash($_POST['XAGIO_REVIEW']), 'sanitize_text_field');

            $psReview = [];
            foreach ($xagio_review as $xagio_key => $xagio_value) {
                if (is_array($xagio_value)) {
                    foreach ($xagio_value as $xagio_k => $v) {
                        if (is_null($v)) {
                            $v = '';
                        }
                        $xagio_value[$xagio_k] = wp_kses_post($v);
                    }
                }
                $psReview[sanitize_title_for_query($xagio_key)] = $xagio_value;
            }

            if (isset($psReview)) {
                update_option('XAGIO_REVIEW', $psReview);
                xagio_json('success', 'Your settings have been saved.');
            }
        }

        public static function bulkReview()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (isset($_POST['ids']) && isset($_POST['type'])) {
                $ids  = array_map('intval', $_POST['ids']);
                $type = sanitize_text_field(wp_unslash($_POST['type']));

                if ($type === 'approve') {

                    $wpdb->update('xag_reviews', [
                        'approved' => 1,
                    ], [
                        'id' => $ids,
                    ]);

                } else if ($type === 'unapprove') {

                    $wpdb->update('xag_reviews', [
                        'approved' => 0,
                    ], [
                        'id' => $ids,
                    ]);

                } else if ($type === 'delete') {

                    $wpdb->delete('xag_reviews', [
                        'id' => $ids,
                    ]);

                } else if ($type === 'move') {

                    if (isset($_POST['post_id'])) {

                        $post_id = intval($_POST['post_id']);
                        $ids     = explode(',', $ids);

                        $wpdb->update('xag_reviews', [
                            'page_id' => $post_id,
                        ], [
                            'id' => $ids,
                        ]);

                    }

                }
            }

        }

        public static function cloneReview()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (isset($_POST['post_ids']) && isset($_POST['review_id'])) {
                $post_ids  = explode(',', sanitize_text_field(wp_unslash($_POST['post_ids'])));
                $review_id = intval($_POST['review_id']);

                $review_data = $wpdb->get_row($wpdb->prepare('SELECT * FROM xag_reviews WHERE id = %d', $review_id), ARRAY_A);

                // remove id
                unset($review_data['id']);

                foreach ($post_ids as $post_id) {
                    $review_data['page_id'] = $post_id;

                    $wpdb->insert('xag_reviews', $review_data);
                }

                wp_send_json([
                    'status'  => 'success',
                    'message' => "All data successfully cloned"
                ]);
            }

        }

        public static function approveReview()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (isset($_POST['id'])) {
                $review_id = trim(sanitize_text_field(wp_unslash($_POST['id'])));

                $wpdb->update('xag_reviews', [
                    'approved' => 1,
                ], [
                    'id' => $review_id,
                ]);
            }
        }

        public static function unapproveReview()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (isset($_POST['id'])) {
                $review_id = trim(sanitize_text_field(wp_unslash($_POST['id'])));

                $wpdb->update('xag_reviews', [
                    'approved' => 0,
                ], [
                    'id' => $review_id,
                ]);
            }
        }

        public static function removeReview()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (isset($_POST['id'])) {
                $review_id = trim(sanitize_text_field(wp_unslash($_POST['id'])));

                $wpdb->delete('xag_reviews', [
                    'id' => $review_id,
                ]);
            }
        }

        public static function countReviews($id = NULL)
        {
            global $wpdb;

            if ($id !== NULL) {
                return $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM xag_reviews WHERE page_id = %d", intval($id)
                    )
                );
            } else {
                return $wpdb->get_var("SELECT COUNT(*) FROM xag_reviews");
            }

        }

        public static function getReviewsGlobal( $stars_only = false, $random_order = false ) {
            global $wpdb;

            $table = 'xag_reviews';

            if ( $random_order ) {
                if ( true === $stars_only ) {
                    return $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$table} WHERE approved = %d AND stars_only = %d ORDER BY RAND()", 1, 1 ), ARRAY_A );
                }

                return $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$table} WHERE approved = %d ORDER BY RAND()", 1 ), ARRAY_A );
            }

            if ( true === $stars_only ) {
                return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE approved = %d AND stars_only = %d ORDER BY rating DESC", 1, 1 ), ARRAY_A );
            }

            return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE approved = %d ORDER BY rating DESC", 1 ), ARRAY_A);
        }



        public static function getReviewsForPage( $xagio_page_id = 0, $stars_only = null, $random_order = false ) {
            global $wpdb;

            $table        = 'xag_reviews';
            $xagio_page_id = absint( $xagio_page_id );
            $is_home_context = (
                XAGIO_MODEL_SEO::is_home_posts_page() ||
                XAGIO_MODEL_SEO::is_posts_page() ||
                XAGIO_MODEL_SEO::is_home_static_page()
            );

            // Random order branch
            if ( $random_order ) {

                // Home/posts/static home: (page_id = 0 OR page_id = X) + optional stars_only
                if ( $is_home_context ) {

                    if ( true === $stars_only ) {
                        return $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT * FROM {$table} WHERE (page_id = %d OR page_id = %d) AND approved = %d AND stars_only = %d ORDER BY RAND()",
                                0,
                                $xagio_page_id,
                                1,
                                1
                            ),
                            ARRAY_A
                        );
                    } elseif ( false === $stars_only ) {
                        return $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT * FROM {$table} WHERE (page_id = %d OR page_id = %d) AND approved = %d AND stars_only = %d ORDER BY RAND()",
                                0,
                                $xagio_page_id,
                                1,
                                0
                            ),
                            ARRAY_A
                        );
                    }

                    return $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$table} WHERE (page_id = %d OR page_id = %d) AND approved = %d ORDER BY RAND()",
                            0,
                            $xagio_page_id,
                            1
                        ),
                        ARRAY_A
                    );
                }

                // Normal page: page_id = X + optional stars_only
                if ( true === $stars_only ) {
                    return $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$table} WHERE page_id = %d AND approved = %d AND stars_only = %d ORDER BY RAND()",
                            $xagio_page_id,
                            1,
                            1
                        ),
                        ARRAY_A
                    );
                } elseif ( false === $stars_only ) {
                    return $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$table} WHERE page_id = %d AND approved = %d AND stars_only = %d ORDER BY RAND()",
                            $xagio_page_id,
                            1,
                            0
                        ),
                        ARRAY_A
                    );
                }

                return $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$table} WHERE page_id = %d AND approved = %d ORDER BY RAND()",
                        $xagio_page_id,
                        1
                    ),
                    ARRAY_A
                );
            }

            // rating DESC branch
            if ( $is_home_context ) {

                if ( true === $stars_only ) {
                    return $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$table} WHERE (page_id = %d OR page_id = %d) AND approved = %d AND stars_only = %d ORDER BY rating DESC",
                            0,
                            $xagio_page_id,
                            1,
                            1
                        ),
                        ARRAY_A
                    );
                } elseif ( false === $stars_only ) {
                    return $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$table} WHERE (page_id = %d OR page_id = %d) AND approved = %d AND stars_only = %d ORDER BY rating DESC",
                            0,
                            $xagio_page_id,
                            1,
                            0
                        ),
                        ARRAY_A
                    );
                }

                return $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$table} WHERE (page_id = %d OR page_id = %d) AND approved = %d ORDER BY rating DESC",
                        0,
                        $xagio_page_id,
                        1
                    ),
                    ARRAY_A
                );
            }

            if ( true === $stars_only ) {
                return $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$table} WHERE page_id = %d AND approved = %d AND stars_only = %d ORDER BY rating DESC",
                        $xagio_page_id,
                        1,
                        1
                    ),
                    ARRAY_A
                );
            } elseif ( false === $stars_only ) {
                return $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$table} WHERE page_id = %d AND approved = %d AND stars_only = %d ORDER BY rating DESC",
                        $xagio_page_id,
                        1,
                        0
                    ),
                    ARRAY_A
                );
            }

            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE page_id = %d AND approved = %d ORDER BY rating DESC",
                    $xagio_page_id,
                    1
                ),
                ARRAY_A
            );
        }



        public static function getReview()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (isset($_POST['id'])) {
                $data           = $wpdb->get_row($wpdb->prepare('SELECT * FROM xag_reviews WHERE id = %d', intval($_POST['id'])), ARRAY_A);
                $data['review'] = wp_kses_post($data['review']);
                wp_send_json($data);
            }
        }

        public static function editReview()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['id'], $_POST['date'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $ID            = intval($_POST['id']);
            $_POST['date'] = sanitize_text_field(wp_unslash($_POST['date']));

            $approved = 0;
            if (isset($_POST['approved'])) {
                if (!empty($_POST['approved'])) {
                    $approved = intval(wp_unslash($_POST['approved']));
                }
            }

            $data = [
                'name'      => isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : 'n/a',
                'review'    => isset($_POST['review']) ? sanitize_text_field(wp_unslash($_POST['review'])) : 'n/a',
                'title'     => isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : 'n/a',
                'rating'    => isset($_POST['rating']) ? intval($_POST['rating']) : 5,
                'email'     => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : 'n/a',
                'website'   => isset($_POST['website']) ? sanitize_url(wp_unslash($_POST['website'])) : 'n/a',
                'telephone' => isset($_POST['telephone']) ? sanitize_text_field(wp_unslash($_POST['telephone'])) : 'n/a',
                'location'  => isset($_POST['location']) ? sanitize_text_field(wp_unslash($_POST['location'])) : 'n/a',
                'age'       => isset($_POST['age']) ? intval($_POST['age']) : 0,
                'approved'  => $approved,
                'date'      => gmdate('Y-m-d H:i:s'),
                'page_id'   => isset($_POST['page_id']) ? intval($_POST['page_id']) : 0
            ];

            if ($ID == 0) {
                $wpdb->insert('xag_reviews', $data);
            } else {
                $wpdb->update('xag_reviews', $data, [
                    'id' => $ID,
                ]);
            }
            xagio_json('success', 'Successfully finished operation.');
        }

        public static function getReviews_Datatables() {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            // Paging
            $start  = isset($_POST['iDisplayStart']) ? absint($_POST['iDisplayStart']) : 0;
            $length = isset($_POST['iDisplayLength']) && $_POST['iDisplayLength'] !== '-1' ? absint($_POST['iDisplayLength']) : 50;
            if ($length < 1) { $length = 50; }
            if ($length > 200) { $length = 200; }

            // ORDER BY (whitelist)
            $sortable = [
                'id'        => 'id',
                'name'      => 'name',
                'title'     => 'title',
                'review'    => 'review',
                'rating'    => 'rating',
                'email'     => 'email',
                'website'   => 'website',
                'telephone' => 'telephone',
                'location'  => 'location',
                'age'       => 'age',
                'date'      => '`date`',
                'approved'  => 'approved',
            ];

            $order_by  = 'id';
            $order_dir = 'DESC';

            if (isset($_POST['iSortCol_0'], $_POST['sSortDir_0'])) {
                $col_index = absint($_POST['iSortCol_0']);
                $dir_raw   = strtolower(sanitize_text_field(wp_unslash($_POST['sSortDir_0'])));
                $order_dir = ($dir_raw === 'asc') ? 'ASC' : 'DESC';

                // DataTables passes mDataProp_{index}
                $key = isset($_POST['mDataProp_' . $col_index])
                    ? sanitize_key(wp_unslash($_POST['mDataProp_' . $col_index]))
                    : '';

                if ($key && isset($sortable[$key])) {
                    $order_by = $sortable[$key];
                }
            }

            // Filters: approved + search (no dynamic WHERE)
            $filter_approved = 0;
            $approved_val    = '';

            $review_state = isset($_POST['ReviewState']) ? wp_unslash($_POST['ReviewState']) : '';
            if ($review_state !== '' && $review_state !== null) {
                $filter_approved = 1;
                $approved_val    = sanitize_text_field($review_state);
            }

            $filter_search = 0;
            $search_like   = '';
            $sSearch = isset($_POST['sSearch']) ? trim((string) wp_unslash($_POST['sSearch'])) : '';
            if ($sSearch !== '') {
                $filter_search = 1;
                $sSearch       = sanitize_text_field($sSearch);
                $search_like   = '%' . $wpdb->esc_like($sSearch) . '%';
            }

            // Main query (fixed SQL, placeholders only)
            $rResult = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT SQL_CALC_FOUND_ROWS
                id, name, title, review, rating, email, website, telephone, location, age, `date`, page_id, approved
             FROM xag_reviews
             WHERE (%d = 0 OR approved = %s)
               AND (%d = 0 OR (name LIKE %s OR review LIKE %s))
             ORDER BY {$order_by} {$order_dir}
             LIMIT %d, %d",
                    $filter_approved,
                    $approved_val,
                    $filter_search,
                    $search_like,
                    $search_like,
                    $start,
                    $length
                ),
                ARRAY_A
            );

            $iFilteredTotal = (int) $wpdb->get_var("SELECT FOUND_ROWS()");
            $iTotal         = (int) $wpdb->get_var("SELECT COUNT(*) FROM xag_reviews");

            $datt = [];
            foreach ((array) $rResult as $d) {
                $d['name']   = stripslashes($d['name']);
                $d['review'] = stripslashes($d['review']);

                if ((int)$d['page_id'] === 0) {
                    $frontpage_id  = get_option('page_on_front');
                    $d['page_url'] = get_site_url();

                    if (empty($frontpage_id)) {
                        $d['page_title'] = 'Global Review';
                        $d['page_edit']  = $d['page_url'];
                    } else {
                        $d['page_title'] = 'Homepage';
                        $d['page_edit']  = get_edit_post_link($frontpage_id);
                    }
                } else {
                    $d['page_title'] = get_the_title((int)$d['page_id']);
                    $d['page_url']   = get_permalink((int)$d['page_id']);
                    $d['page_edit']  = get_edit_post_link((int)$d['page_id']);
                }

                unset($d['page_id']);
                $datt[] = $d;
            }

            echo wp_json_encode([
                "sEcho"                => isset($_POST['sEcho']) ? absint($_POST['sEcho']) : 0,
                "iTotalRecords"        => $iTotal,
                "iTotalDisplayRecords" => $iFilteredTotal,
                "aaData"               => $datt,
            ]);
        }


        public static function newReview()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $allowed_vars = [
                "action",
                "name",
                "review",
                "title",
                "rating",
                "email",
                "website",
                "telephone",
                "location",
                "age",
                "page_id",
                "stars_only",
            ];

            foreach ($allowed_vars as $var) {
                if (!isset($_POST[$var])) {
                    xagio_json('error', 'Invalid Request!');
                }
            }

            // Check if cookie tracking is on
            $xagio_review = get_option('XAGIO_REVIEW');
            if (isset($xagio_review['settings']['prevent_multiple']) && $xagio_review['settings']['prevent_multiple'] == '1') {
                if (!self::isAllowedToPost()) {
                    xagio_json('error', 'You already submitted a review!');
                }
            }

            // Ratings
            $stars_only = isset($_POST['stars_only']) ? intval($_POST['stars_only']) : 5;

            $approved = 0;

            // Check if auto approve is on
            if (@$xagio_review['settings']['stars_approve'] == 1 && $stars_only == 1) {
                $approved = 1;
            }
            if (@$xagio_review['settings']['reviews_approve'] == 1 && $stars_only == 0) {
                $approved = 1;
            }

            $data = [
                'name'       => isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : 'n/a',
                'review'     => isset($_POST['review']) ? sanitize_text_field(wp_unslash($_POST['review'])) : 'n/a',
                'title'      => isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : 'n/a',
                'rating'     => isset($_POST['rating']) ? intval($_POST['rating']) : 5,
                'email'      => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : 'n/a',
                'website'    => isset($_POST['website']) ? sanitize_url(wp_unslash($_POST['website'])) : 'n/a',
                'telephone'  => isset($_POST['telephone']) ? sanitize_text_field(wp_unslash($_POST['telephone'])) : 'n/a',
                'location'   => isset($_POST['location']) ? sanitize_text_field(wp_unslash($_POST['location'])) : 'n/a',
                'age'        => isset($_POST['age']) ? intval($_POST['age']) : 0,
                'approved'   => $approved,
                'date'       => gmdate('Y-m-d H:i:s'),
                'page_id'    => isset($_POST['page_id']) ? intval($_POST['page_id']) : 0,
                'stars_only' => $stars_only,
            ];

            global $wpdb;
            $wpdb->insert('xag_reviews', $data);
            $xagio_result = $wpdb->insert_id;

            if ($xagio_result !== false) {
                xagio_json('success', 'Review added!', $xagio_result);
            } else {
                xagio_json('error', 'Failed to add review.');
            }
        }


        private static function isAllowedToPost()
        {
            if (isset($_COOKIE['_psrl'])) {
                return FALSE;
            } else {
                setcookie('_psrl', TRUE, time() + 86400); // 1 day
            }
            return TRUE;
        }

        public static function createTable()
        {
            global $wpdb;
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $charset_collate = $wpdb->get_charset_collate();
            $creation_query  = 'CREATE TABLE xag_reviews (
			         `id` int(11) NOT NULL AUTO_INCREMENT,
                     `name` varchar(255) DEFAULT NULL,
                     `title` varchar(255) DEFAULT NULL,
                     `review` text,
                     `rating` int(1) DEFAULT NULL,
                     `email` varchar(255) DEFAULT NULL,
                     `website` varchar(255) DEFAULT NULL,
                     `telephone` varchar(255) DEFAULT NULL,
                     `location` text,
                     `age` int(2) DEFAULT NULL,
                     `date` datetime DEFAULT NULL,
                     `page_id` int(11) DEFAULT 0,
                     `approved` int(1) DEFAULT 0,
                     `stars_only` int(1) DEFAULT 0,
			          PRIMARY KEY  (`id`)
			    ) ' . $charset_collate . ';';
            @dbDelta($creation_query);
        }

        public static function removeTable()
        {
            global $wpdb;
            $wpdb->query('DROP TABLE IF EXISTS xag_reviews;');
        }

    }
}
