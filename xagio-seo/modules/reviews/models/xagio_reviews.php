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

            add_action('admin_init', [
                'XAGIO_MODEL_REVIEWS',
                'registerAssets'
            ]);
            add_action('admin_enqueue_scripts', [
                'XAGIO_MODEL_REVIEWS',
                'loadAdminAssets'
            ], 10, 1);
            add_action('wp_enqueue_scripts', [
                'XAGIO_MODEL_REVIEWS',
                'loadUserAssets'
            ], 10, 1);

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
            add_action('admin_post_nopriv_xagio_newReview', [
                'XAGIO_MODEL_REVIEWS',
                'newReview'
            ]);
            add_action('admin_post_xagio_saveReviewWidget', [
                'XAGIO_MODEL_REVIEWS',
                'saveReviewWidget'
            ]);

        }

        public static function registerAssets()
        {

        }

        // Enqueue scripts admin
        public static function loadAdminAssets($hook)
        {
            if ($hook === 'xagio_page_xagio-reviews') {
                wp_enqueue_style('xagio_review_widget_form');
            }
        }

        // Enqueue scripts user
        public static function loadUserAssets()
        {
            $url = xagio_get_model_url(__FILE__);

            /** Load global CSS */
            wp_register_style('xagio_icons', XAGIO_URL . 'assets/css/icons.css', [], '1.0');

            if (XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS == FALSE || XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS == 0) {
                wp_register_script('xagio_review_widget_form', $url . 'review_widget_form.js', ['jquery'], '1.0', TRUE);
            }

            if (XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS == FALSE || XAGIO_DISABLE_REVIEW_WIDGET_CSS_JS == 0) {
                wp_register_style('xagio_review_widget_form', $url . 'review_widget_form.css', [], '1.0');
                wp_register_style('xagio_review_widget_display', $url . 'review_widget_display.css', [], '1.0');
            }

            // Enqueue Scripts
            wp_enqueue_script('xagio_review_widget_form');

            // Enqueue Styles
            wp_enqueue_style('xagio_icons');
            wp_enqueue_style('xagio_review_widget_form');
            wp_enqueue_style('xagio_review_widget_display');

            $unique_id = 'rw-display';
            $ps_review = stripslashes_deep(get_option('XAGIO_REVIEW'));

            $style = "
    ." . esc_attr($unique_id) . " .review-widget {
        " . (@$ps_review['colors']['background'] != NULL ? "background: " . esc_attr($ps_review['colors']['background']) . ";" : "") . "
        " . (@$ps_review['colors']['text'] != NULL ? "color: " . esc_attr($ps_review['colors']['text']) . ";" : "") . "
        " . (@$ps_review['colors']['border'] != NULL ? "border-color: " . esc_attr($ps_review['colors']['border']) . ";" : "") . "
        " . (@$ps_review['padding']['widget'] != NULL ? "padding: " . esc_attr($ps_review['padding']['widget']) . "px;" : "") . "
    }

    ." . esc_html($unique_id) . " .review-widget-button {
        " . (@$ps_review['colors']['button_background'] != NULL ? "background: " . esc_attr($ps_review['colors']['button_background']) . ";" : "") . "
        " . (@$ps_review['colors']['button_text'] != NULL ? "color: " . esc_attr($ps_review['colors']['button_text']) . ";" : "") . "
    }

    ." . esc_html($unique_id) . " .review-widget-label, .review-widget-title > h2 {
        " . (@$ps_review['colors']['text'] != NULL ? "color: " . esc_attr($ps_review['colors']['text']) . ";" : "") . "
    }

    ." . esc_html($unique_id) . " .review-widget-label {
        " . (@$ps_review['font_size']['label'] != NULL ? "font-size: " . absint($ps_review['font_size']['label']) . "px;" : "") . "
    }

    ." . esc_html($unique_id) . " .review-widget-title > h2 {
        " . (@$ps_review['font_size']['heading'] != NULL ? "font-size: " . absint($ps_review['font_size']['heading']) . "px;" : "") . "
    }

    ." . esc_html($unique_id) . " .review-widget-text {
        " . (@$ps_review['font_size']['subheading'] != NULL ? "font-size: " . absint($ps_review['font_size']['subheading']) . "px;" : "") . "
    }

    ." . esc_html($unique_id) . " .review-widget-stars-ratings-sum {
        " . (@$ps_review['colors']['rating_heading'] != NULL ? "color: " . esc_attr($ps_review['colors']['rating_heading']) . ";" : "") . "
        " . (@$ps_review['details']['rating_heading_size'] != NULL ? "font-size: " . absint($ps_review['details']['rating_heading_size']) . "px;" : "") . "
    }

    ." . esc_html($unique_id) . " .review-widget-stars-ratings-info {
        " . (@$ps_review['colors']['rating_info'] != NULL ? "color: " . esc_attr($ps_review['colors']['rating_info']) . ";" : "") . "
        " . (@$ps_review['details']['rating_instruction_size'] != NULL ? "font-size: " . absint($ps_review['details']['rating_instruction_size']) . "px;" : "") . "
    }

    ." . esc_html($unique_id) . " .review-widget-input {
        " . (@$ps_review['colors']['input_background'] != NULL ? "background: " . esc_attr($ps_review['colors']['input_background']) . ";" : "") . "
        " . (@$ps_review['colors']['input_text'] != NULL ? "color: " . esc_attr($ps_review['colors']['input_text']) . ";" : "") . "
        " . (@$ps_review['font_size']['input'] != NULL ? "font-size: " . absint($ps_review['font_size']['input']) . "px;" : "") . "
        " . (@$ps_review['padding']['input'] != NULL ? "padding: " . absint($ps_review['padding']['input']) . "px;" : "") . "
    }

    ." . esc_html($unique_id) . " .review-widget-stars i {
        " . (@$ps_review['colors']['stars'] != NULL ? "color: " . esc_attr($ps_review['colors']['stars']) . ";" : "") . "
        " . (@$ps_review['font_size']['stars'] != NULL ? "font-size: " . absint($ps_review['font_size']['stars']) . "px !important;" : "") . "
    }
";

            $style .= "
    " . (@$ps_review['details']['heading_size'] != NULL ? "
    .prs-review-display-container .prs-review-display-heading {
        font-size: " . absint($ps_review['details']['heading_size']) . "px !important;
    }
    " : "") . "

    " . (@$ps_review['details']['subheading_size'] != NULL ? "
    .prs-review-display-container .prs-review-container-aggregate {
        font-size: " . absint($ps_review['details']['subheading_size']) . "px !important;
    }
    " : "") . "

    " . (@$ps_review['colors_display']['background'] != NULL ? "
    .prs-review-display-container .prs-review-container-aggregate,
    .prs-review-display-container .prs-review-container {
        background: " . esc_attr($ps_review['colors_display']['background']) . " !important;
    }
    " : "") . "

    " . (@$ps_review['colors_display']['border'] != NULL ? "
    .prs-review-display-container .prs-review-container-aggregate,
    .prs-review-display-container .prs-review-container {
        border-color: " . esc_attr($ps_review['colors_display']['border']) . " !important;
    }
    " : "") . "

    " . (@$ps_review['colors_display']['stars'] != NULL ? "
    .prs-review-display-container .prs-review-container .prs-review-stars i {
        color: " . esc_attr($ps_review['colors_display']['stars']) . " !important;
        " . (@$ps_review['details']['display_star_size'] != NULL ? "font-size: " . absint($ps_review['details']['display_star_size']) . "px !important;" : "") . "
    }
    " : "") . "

    " . (@$ps_review['colors_display']['text'] != NULL ? "
    .prs-review-display-container .prs-review-container-aggregate,
    .prs-review-display-container .prs-review-container,
    .prs-review-display-container .prs-review-display-heading h2 {
        color: " . esc_attr($ps_review['colors_display']['text']) . " !important;
    }
    " : "") . "
";
            wp_add_inline_style('xagio_review_widget_display', $style);

            $ps_stars            = FALSE;
            $ps_stars_percentage = FALSE;

            if (
                @$ps_review['settings']['stars_only'] == 1
            ) {

                // Set the stars mode to ON
                $ps_stars = TRUE;

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
                    $totalRatings = is_array($ratings) ? sizeof($ratings) : 0;

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

            // Prepare the data to be passed to the JavaScript file
            $localized_data = array(
                'unique_id'          => esc_attr($unique_id),
                'ps_admin_url'       => esc_url(admin_url() . 'admin-post.php'),
                'ps_thank_you'       => (@$ps_review['details']['thank_you'] == NULL) ? 'Thank you for leaving us a review!' : esc_html($ps_review['details']['thank_you']),
                'ps_rating_thank_you'=> (@$ps_review['details']['rating_thank_you'] == NULL) ? 'Thank you for leaving a rating!' : esc_html($ps_review['details']['rating_thank_you']),
                'ps_stars_only'      => esc_html($ps_stars),
                'ps_stars_init'      => esc_html($ps_stars_percentage)
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

        public static function reviewsWidgetShortcode($instance)
        {

            $render = function ($instance) {
                global $post;
                $isShortcode = TRUE;
                ob_start();
                include(XAGIO_PATH . '/modules/reviews/metabox/review_form.php');
                return ob_get_clean();
            };

            return $render($instance);
        }

        public static function reviewsDisplayShortcode($instance)
        {

            $render = function ($instance) {
                global $post;
                $isShortcode = TRUE;
                ob_start();
                include(XAGIO_PATH . '/modules/reviews/metabox/display_reviews.php');
                return ob_get_clean();
            };

            return $render($instance);

        }

        public static function saveReviewWidget()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['XAGIO_REVIEW'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            wp_cache_delete('alloptions', 'options');

            if (!isset($_POST['XAGIO_REVIEW'])) {
                xagio_json('error', 'Sorry, something went worng. Please try again!');
            }

            $xagio_review = map_deep(wp_unslash($_POST['XAGIO_REVIEW']), 'sanitize_text_field');

            $psReview = [];
            foreach ($xagio_review as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if (is_null($v)) {
                            $v = '';
                        }
                        $value[$k] = wp_kses_post($v);
                    }
                }
                $psReview[sanitize_title_for_query($key)] = $value;
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

                $review_data = $wpdb->get_results($wpdb->prepare('SELECT * FROM xag_reviews WHERE id = %d', $review_id), ARRAY_A);

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

        public static function getReviewsGlobal($stars_only = false, $random_order = false)
        {
            global $wpdb;

            $stars_condition = '';
            if ($stars_only === true) {
                $stars_condition = 'AND stars_only = %d';
            }

            $order_by = 'rating DESC';
            if ($random_order === true) {
                $order_by = 'RAND()';
            }

            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM xag_reviews WHERE approved = %d {$stars_condition} ORDER BY {$order_by}", 1
                ), ARRAY_A
            );
        }


        public static function getReviewsForPage($page_id = 0, $stars_only = null, $random_order = false)
        {
            global $wpdb;

            $stars_condition = '';
            if ($stars_only === true) {
                $stars_condition = 'AND stars_only = %d';
            } elseif ($stars_only === false) {
                $stars_condition = 'AND stars_only = %d';
            }

            $order_by = 'rating DESC';
            if ($random_order === true) {
                $order_by = 'RAND()';
            }

            if (XAGIO_MODEL_SEO::is_home_posts_page() || XAGIO_MODEL_SEO::is_posts_page() || XAGIO_MODEL_SEO::is_home_static_page()) {
                return $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM xag_reviews WHERE (page_id = %d OR page_id = %d) AND approved = %d {$stars_condition} ORDER BY {$order_by}", 0, $page_id, 1
                    ), ARRAY_A
                );
            } else {
                return $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM xag_reviews WHERE page_id = %d AND approved = %d {$stars_condition} ORDER BY {$order_by}", $page_id, 1
                    ), ARRAY_A
                );
            }


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

        public static function getReviews_Datatables()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            $aColumns = [
                'id',
                'name',
                'title',
                'review',
                'rating',
                'email',
                'website',
                'telephone',
                'location',
                'age',
                'date',
                'page_id',
                'approved',
            ];

            $sIndexColumn = "id";
            $sTable       = 'xag_reviews';

            // Initialize parameters array for placeholders
            $queryParams = [];

            // Paging
            $sLimit = "LIMIT 0, 50";
            if (isset($_POST['iDisplayStart']) && isset($_POST['iDisplayLength']) && $_POST['iDisplayLength'] != '-1') {
                $sLimit = "LIMIT %d, %d";
                $queryParams[] = intval($_POST['iDisplayStart']);
                $queryParams[] = intval($_POST['iDisplayLength']);
            }

            // Ordering
            $sOrder = '';
            if (isset($_POST['iSortCol_0'])) {
                $orderArr = [];
                if (isset($_POST['iSortingCols'])) {
                    for ($i = 0; $i < intval($_POST['iSortingCols']); $i++) {
                        if (isset($_POST['bSortable_' . intval($_POST['iSortCol_' . $i])]) && $_POST['bSortable_' . intval($_POST['iSortCol_' . $i])] == "true") {
                            if (isset($_POST['mDataProp_' . intval($_POST['iSortCol_' . $i])], $_POST['sSortDir_' . $i])) {
                                $column    = sanitize_text_field(wp_unslash($_POST['mDataProp_' . intval($_POST['iSortCol_' . $i])]));
                                $direction = sanitize_text_field(wp_unslash($_POST['sSortDir_' . $i]));
                                $orderArr[] = esc_sql($column) . " " . esc_sql($direction);
                            }
                        }
                    }
                }

                if (!empty($orderArr)) {
                    $sOrder = "ORDER BY " . implode(", ", $orderArr);
                }
            }

            // Filtering
            $customFilters = [
                'approved' => isset($_POST['ReviewState']) ? sanitize_text_field(wp_unslash($_POST['ReviewState'])) : '',
                'search'   => isset($_POST['sSearch']) ? sanitize_text_field(wp_unslash($_POST['sSearch'])) : '',
            ];

            $customWhere = "";
            foreach ($customFilters as $key => $column) {
                if (!empty($column)) {
                    if (empty($customWhere)) {
                        $customWhere = "WHERE ";
                    } else {
                        $customWhere .= " AND ";
                    }

                    if ($key == 'search') {
                        $customWhere .= " (`name` LIKE %s OR `review` LIKE %s) ";
                        $queryParams[] = '%' . $wpdb->esc_like($column) . '%';
                        $queryParams[] = '%' . $wpdb->esc_like($column) . '%';
                    } else {
                        $customWhere .= "$key = %s";
                        $queryParams[] = $column;
                    }
                }
            }

            // Build final SQL query
            $columns = implode(", ", array_map('esc_sql', $aColumns));

            // Execute query with a single prepare call
            $rResult = $wpdb->get_results(
                $wpdb->prepare("
    SELECT SQL_CALC_FOUND_ROWS {$columns}
    FROM {$sTable}
    {$customWhere}
    {$sOrder}
    {$sLimit}
", ...$queryParams),
                ARRAY_A
            );


            $iFilteredTotal = $wpdb->get_var("SELECT FOUND_ROWS()");

            // Total data set length
            $iTotal = $wpdb->get_var($wpdb->prepare("SELECT COUNT(%s) FROM %s", esc_sql($sIndexColumn), $sTable));

            $datt = [];
            foreach ($rResult as $d) {
                $d['name']   = stripslashes($d['name']);
                $d['review'] = stripslashes($d['review']);

                if ($d['page_id'] == 0) {
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
                    $d['page_title'] = get_the_title($d['page_id']);
                    $d['page_url']   = get_permalink($d['page_id']);
                    $d['page_edit']  = get_edit_post_link($d['page_id']);
                }

                unset($d['page_id']);
                $datt[] = $d;
            }

            // Output
            $output = [
                "sEcho"                => isset($_POST['sEcho']) ? intval($_POST['sEcho']) : 0,
                "iTotalRecords"        => $iTotal,
                "iTotalDisplayRecords" => $iFilteredTotal,
                "aaData"               => $datt,
            ];

            echo wp_json_encode($output);
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
            $ps_review = get_option('XAGIO_REVIEW');
            if (isset($ps_review['settings']['prevent_multiple']) && $ps_review['settings']['prevent_multiple'] == '1') {
                if (!self::isAllowedToPost()) {
                    xagio_json('error', 'You already submitted a review!');
                }
            }

            // Ratings
            $stars_only = isset($_POST['stars_only']) ? intval($_POST['stars_only']) : 5;

            $approved = 0;

            // Check if auto approve is on
            if (@$ps_review['settings']['stars_approve'] == 1 && $stars_only == 1) {
                $approved = 1;
            }
            if (@$ps_review['settings']['reviews_approve'] == 1 && $stars_only == 0) {
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
            $result = $wpdb->insert_id;

            if ($result !== false) {
                xagio_json('success', 'Review added!', $result);
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
