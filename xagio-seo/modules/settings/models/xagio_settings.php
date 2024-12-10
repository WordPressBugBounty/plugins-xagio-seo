<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_SETTINGS')) {

    class XAGIO_MODEL_SETTINGS
    {

        private static function defines()
        {
            define('XAGIO_DISABLE_UPLOADS', filter_var(get_option('XAGIO_DISABLE_UPLOADS'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_DISABLE_MAINTENANCE', filter_var(get_option('XAGIO_DISABLE_MAINTENANCE'), FILTER_VALIDATE_BOOLEAN));

            define('XAGIO_RECAPTCHA', filter_var(get_option('XAGIO_RECAPTCHA'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_RECAPTCHA_SITE_KEY', get_option('XAGIO_RECAPTCHA_SITE_KEY'));
            define('XAGIO_RECAPTCHA_SECRET_KEY', get_option('XAGIO_RECAPTCHA_SECRET_KEY'));

            if (XAGIO_DISABLE_MAINTENANCE) {
                XAGIO_MODEL_SETTINGS::disableMaintenanceMode();
            }
        }

        public static function getApiUrl()
        {
            return rest_url('xagio-seo/v1/');
        }

        public static function disableMaintenanceMode()
        {
            // Define the path to the .maintenance file
            $maintenance_file = ABSPATH . '.maintenance';

            // Check if the .maintenance file exists
            if (file_exists($maintenance_file)) {
                // Try to delete the .maintenance file to take the site out of maintenance mode
                wp_delete_file($maintenance_file);
            }
        }

        public static function initialize()
        {
            XAGIO_MODEL_SETTINGS::defines();

            // Login Captcha
            add_action('login_enqueue_scripts', [
                'XAGIO_MODEL_SETTINGS',
                'recaptchaLogin'
            ]);
            add_action('login_form', [
                'XAGIO_MODEL_SETTINGS',
                'recaptchaLoginField'
            ]);
            add_filter('wp_authenticate_user', [
                'XAGIO_MODEL_SETTINGS',
                'recaptchaVerify'
            ], 10, 2);

            // Comment Captcha
            add_action('wp_enqueue_scripts', [
                'XAGIO_MODEL_SETTINGS',
                'captchaComment'
            ]);
            add_action('comment_form', [
                'XAGIO_MODEL_SETTINGS',
                'captchaCommentField'
            ]);
            add_filter('preprocess_comment', [
                'XAGIO_MODEL_SETTINGS',
                'captchaCommentVerify'
            ]);

            XAGIO_MODEL_SETTINGS::disableUploads();

            if (!XAGIO_HAS_ADMIN_PERMISSIONS) return;

            add_action('admin_post_xagio_save_settings', [
                'XAGIO_MODEL_SETTINGS',
                'saveSettings'
            ]);
            add_action('admin_post_xagio_save_editors', [
                'XAGIO_MODEL_SETTINGS',
                'saveEditors'
            ]);
            add_action('admin_post_xagio_save_defaults', [
                'XAGIO_MODEL_SETTINGS',
                'saveDefaults'
            ]);
            add_action('admin_post_xagio_save_verifications', [
                'XAGIO_MODEL_SETTINGS',
                'saveVerifications'
            ]);
            add_action('admin_post_xagio_save_separator', [
                'XAGIO_MODEL_SETTINGS',
                'saveSeparator'
            ]);

            add_action('admin_post_xagio_show_tutorial', [
                'XAGIO_MODEL_SETTINGS',
                'showTutorial'
            ]);

            add_action('admin_post_xagio_settings_update_backup_settings', [
                'XAGIO_MODEL_SETTINGS',
                'updateBackupSettings'
            ]);

            add_action('admin_post_xagio_settings_create_backup', [
                'XAGIO_MODEL_SETTINGS',
                'createBackup'
            ]);

            add_action('admin_post_xagio_export_options', [
                'XAGIO_MODEL_SETTINGS',
                'exportOptions'
            ]);
            add_action('admin_post_xagio_import_options', [
                'XAGIO_MODEL_SETTINGS',
                'importOptions'
            ]);

            add_action('admin_post_xagio_settings_troubleshoot_common_issues', [
                'XAGIO_MODEL_SETTINGS',
                'fixCommonIssues'
            ]);

            add_action('admin_post_xagio_set_default_country', [
                'XAGIO_MODEL_SETTINGS',
                'setDefaultCountry'
            ]);
            add_action('admin_post_xagio_get_default_country', [
                'XAGIO_MODEL_SETTINGS',
                'getDefaultCountry'
            ]);

            add_action('admin_post_xagio_set_default_search_engine', [
                'XAGIO_MODEL_SETTINGS',
                'setDefaultSearchEngine'
            ]);
            add_action('admin_post_xagio_get_default_search_engine', [
                'XAGIO_MODEL_SETTINGS',
                'getDefaultSearchEngine'
            ]);
            add_action('admin_post_xagio_set_default_keyword_country', [
                'XAGIO_MODEL_SETTINGS',
                'setDefaultKeywordCountry'
            ]);
            add_action('admin_post_xagio_set_default_keyword_language', [
                'XAGIO_MODEL_SETTINGS',
                'setDefaultKeywordLanguage'
            ]);
            add_action('admin_post_xagio_set_default_audit_location', [
                'XAGIO_MODEL_SETTINGS',
                'setDefaultAuditLocation'
            ]);
            add_action('admin_post_xagio_set_default_ai_wizard_search_engine', [
                'XAGIO_MODEL_SETTINGS',
                'setDefaultAiWizardSearchEngine'
            ]);
            add_action('admin_post_xagio_set_default_ai_wizard_location', [
                'XAGIO_MODEL_SETTINGS',
                'setDefaultAiWizardLocation'
            ]);
        }

        public static function setDefaultAiWizardLocation()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['value'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $value = sanitize_text_field(wp_unslash($_POST['value']));

            if (!empty($value)) {
                update_option('XAGIO_LOCATION_DEFAULT_AI_LOCATION', $value);
            }

            xagio_json('success', 'Default AI Wizard location changed.');
        }

        public static function setDefaultAiWizardSearchEngine()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['value'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $value = sanitize_text_field(wp_unslash($_POST['value']));

            if (!empty($value)) {
                update_option('XAGIO_LOCATION_DEFAULT_AI_SEARCH_ENGINE', $value);
            }

            xagio_json('success', 'Default AI Wizard search engine changed.');
        }

        public static function setDefaultAuditLocation()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['data'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $data = sanitize_text_field(wp_unslash($_POST['data']));

            if (!empty($data)) {
                update_option('XAGIO_LOCATION_DEFAULT_AUDIT_LANGUAGE', $data);
            }

            xagio_json('success', 'Default Audit Location changed.');
        }

        public static function setDefaultKeywordLanguage()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['language'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $language = sanitize_text_field(wp_unslash($_POST['language']));

            if (!empty($language)) {
                update_option('XAGIO_LOCATION_DEFAULT_KEYWORD_LANGUAGE', $language);
            }

            xagio_json('success', 'Default Language changed.');
        }

        public static function setDefaultKeywordCountry()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['country'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $country = sanitize_text_field(wp_unslash($_POST['country']));

            if (!empty($country)) {
                update_option('XAGIO_LOCATION_DEFAULT_KEYWORD_COUNTRY', $country);
            }

            xagio_json('success', 'Default Country changed.');
        }

        public static function setDefaultCountry()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['data'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $data = sanitize_text_field(wp_unslash($_POST['data']));

            if (!empty($data)) {
                update_option('XAGIO_LOCATION_DEFAULT_COUNTRY', $data);
                xagio_json('success', 'Default Country changed.', $data);
            }
        }

        public static function getDefaultCountry()
        {
            $data = get_option('XAGIO_LOCATION_DEFAULT_COUNTRY');
            xagio_json('success', 'Default Country retrieved.', $data);
        }

        public static function setDefaultSearchEngine()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['data'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }
            $data = map_deep(wp_unslash($_POST['data']), 'sanitize_text_field');

            if (!empty($data)) {
                update_option('XAGIO_LOCATION_DEFAULT_SEARCH_ENGINE', $data);
                xagio_json('success', 'Default Search Engine changed.', $data);
            }
        }

        public static function getDefaultSearchEngine()
        {
            $data = get_option('XAGIO_LOCATION_DEFAULT_SEARCH_ENGINE');
            xagio_json('success', 'Default Search Engine retrieved.', $data);
        }

        public static function disableUploads()
        {
            if (XAGIO_DISABLE_UPLOADS) {
                if (!empty($_FILES)) {
                    check_ajax_referer('xagio_nonce', '_xagio_nonce');
                    wp_die('File uploads are disabled by Xagio due to security reasons. To enable them again, please head over to Xagio Settings and turn off "Disable File Uploads".');
                }
            }
        }

        public static function captchaCommentVerify($commentdata)
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['recaptcha_response'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            if (XAGIO_RECAPTCHA && !empty(XAGIO_RECAPTCHA_SITE_KEY) && !empty(XAGIO_RECAPTCHA_SECRET_KEY)) {

                if (isset($_POST['recaptcha_response'])) {
                    $recaptcha_response = sanitize_text_field(wp_unslash($_POST['recaptcha_response']));
                    $response           = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
                        'body' => [
                            'secret'   => XAGIO_RECAPTCHA_SECRET_KEY,
                            'response' => $recaptcha_response
                        ]
                    ]);

                    $response_body = wp_remote_retrieve_body($response);
                    $result        = json_decode($response_body);

                    if (!$result->success || $result->score < 0.5) {
                        wp_die('<strong>ERROR</strong>: reCAPTCHA verification failed, please try again.');
                    }
                }

            }

            return $commentdata;
        }

        public static function captchaCommentField()
        {
            if (XAGIO_RECAPTCHA && !empty(XAGIO_RECAPTCHA_SITE_KEY) && !empty(XAGIO_RECAPTCHA_SECRET_KEY)) {

                echo '<input type="hidden" name="recaptcha_response" id="recaptchaResponse">';

            }
        }

        public static function captchaComment()
        {

            if (XAGIO_RECAPTCHA && !empty(XAGIO_RECAPTCHA_SITE_KEY) && !empty(XAGIO_RECAPTCHA_SECRET_KEY)) {

                if (is_single() && comments_open()) {
                    wp_enqueue_script('recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . esc_html(XAGIO_RECAPTCHA_SITE_KEY), [], '1.0', true);
                    wp_add_inline_script('recaptcha', 'grecaptcha.ready(function() { grecaptcha.execute("' . esc_html(XAGIO_RECAPTCHA_SITE_KEY) . '", {action: "comment"}).then(function(token) { if (document.getElementById("recaptchaResponse")) { document.getElementById("recaptchaResponse").value = token; } }); });');
                }

            }
        }

        public static function recaptchaVerify($user, $password)
        {
            if (XAGIO_RECAPTCHA && !empty(XAGIO_RECAPTCHA_SITE_KEY) && !empty(XAGIO_RECAPTCHA_SECRET_KEY)) {

                check_ajax_referer('xagio_nonce', '_xagio_nonce');

                if (isset($_POST['recaptcha_response'])) {
                    $recaptcha_response = sanitize_text_field(wp_unslash($_POST['recaptcha_response']));
                    $response           = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
                        'body' => [
                            'secret'   => XAGIO_RECAPTCHA_SECRET_KEY,
                            'response' => $recaptcha_response
                        ]
                    ]);

                    $response_body = wp_remote_retrieve_body($response);
                    $result        = json_decode($response_body);

                    if (!$result->success || $result->score < 0.5) {
                        return new WP_Error('recaptcha_error', '<strong>ERROR</strong>: reCAPTCHA verification failed, please try again.');
                    }
                }

            }
            return $user;
        }

        public static function recaptchaLoginField()
        {
            if (XAGIO_RECAPTCHA && !empty(XAGIO_RECAPTCHA_SITE_KEY) && !empty(XAGIO_RECAPTCHA_SECRET_KEY)) {
                echo '<input type="hidden" name="recaptcha_response" id="recaptchaResponse">';
            }
        }

        public static function recaptchaLogin()
        {
            if (XAGIO_RECAPTCHA && !empty(XAGIO_RECAPTCHA_SITE_KEY) && !empty(XAGIO_RECAPTCHA_SECRET_KEY)) {
                wp_enqueue_script('recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . esc_html(XAGIO_RECAPTCHA_SITE_KEY), [], '1.0', true);
                wp_add_inline_script('recaptcha', 'grecaptcha.ready(function() { grecaptcha.execute("' . esc_html(XAGIO_RECAPTCHA_SITE_KEY) . '", {action: "login"}).then(function(token) { var recaptchaResponse = document.getElementById("recaptchaResponse"); recaptchaResponse.value = token; }); });');
            }
        }


        public static function saveSettings_XAGIO_FORCE_HOMEPAGE_SCHEMA($option_value)
        {
            if (!isset($_SERVER['SERVER_NAME'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            if ($option_value) {

                if (XAGIO_CONNECTED) {
                    XAGIO_API::apiRequest(
                        $endpoint = 'toggleForceSchema', $method = 'GET', [
                            'domain'       => preg_replace('/^www\./', '', sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME']))),
                            'force_schema' => 1,
                        ]
                    );
                }

            } else {

                if (XAGIO_CONNECTED) {
                    XAGIO_API::apiRequest(
                        $endpoint = 'toggleForceSchema', $method = 'GET', [
                            'domain'       => preg_replace('/^www\./', '', sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME']))),
                            'force_schema' => 0,
                        ]
                    );
                }

            }
        }

        public static function saveSeparator()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['XAGIO_SEO_TITLE_SEPARATOR'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $separator = sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_TITLE_SEPARATOR']));
            update_option('XAGIO_SEO_TITLE_SEPARATOR', $separator);
            xagio_json('success', $separator);
        }

        public static function saveDefaults()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (isset($_POST['XAGIO_SEO_DEFAULT_POST_TYPES'])) {
                $XAGIO_SEO_DEFAULT_POST_TYPES = map_deep(wp_unslash($_POST['XAGIO_SEO_DEFAULT_POST_TYPES']), 'sanitize_text_field');

                $option_name = "XAGIO_SEO_DEFAULT_POST_TYPES";

                // Get the option or initialize it
                $option = get_option($option_name, []);

                foreach ($XAGIO_SEO_DEFAULT_POST_TYPES as $type => $fields) {
                    if (!isset($option[$type])) {
                        $option[$type] = [];
                    }

                    foreach ($fields as $field => $field_value) {
                        // Sanitize field names and values
                        $field = sanitize_text_field($field);

                        if (empty($field_value)) {
                            unset($option[$type][$field]);
                        } else {
                            // Sanitize as URL if field is an image, otherwise as a text field or boolean
                            if (strpos($field, 'IMAGE') !== false) {
                                $field_value = sanitize_url(wp_unslash($field_value));
                            } else {
                                $field_value = xagio_parse_bool(sanitize_text_field(wp_unslash($field_value)));
                            }
                            $option[$type][$field] = $field_value;
                        }
                    }
                }
                // Update the option in the database
                update_option($option_name, $option);
            }

            if (isset($_POST['XAGIO_SEO_DEFAULT_OG'])) {

                $XAGIO_SEO_DEFAULT_OG = map_deep(wp_unslash($_POST['XAGIO_SEO_DEFAULT_OG']), 'sanitize_text_field');

                $option_name = "XAGIO_SEO_DEFAULT_OG";

                // Get the option or initialize it
                $option = get_option($option_name, []);

                foreach ($XAGIO_SEO_DEFAULT_OG as $type => $fields) {
                    if (!isset($option[$type])) {
                        $option[$type] = [];
                    }

                    foreach ($fields as $field => $field_value) {
                        // Sanitize field names and values
                        $field = sanitize_text_field($field);

                        if (empty($field_value)) {
                            unset($option[$type][$field]);
                        } else {
                            // Sanitize as URL if field is an image, otherwise as a text field or boolean
                            if (strpos($field, 'IMAGE') !== false) {
                                $field_value = sanitize_url(wp_unslash($field_value));
                            } else {
                                $field_value = xagio_parse_bool(sanitize_text_field(wp_unslash($field_value)));
                            }
                            $option[$type][$field] = $field_value;
                        }
                    }
                }
                // Update the option in the database
                update_option($option_name, $option);

            }

            // Send success response
            xagio_json('success', "Operation completed!");
        }


        public static function saveVerifications()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // Process each POST field individually and safely
            if (isset($_POST['XAGIO_SEO_VERIFICATION_GOOGLE'])) {
                $XAGIO_SEO_VERIFICATION_GOOGLE = base64_decode(sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_VERIFICATION_GOOGLE'])));
                update_option('XAGIO_SEO_VERIFICATION_GOOGLE', $XAGIO_SEO_VERIFICATION_GOOGLE);
            }

            if (isset($_POST['XAGIO_SEO_VERIFICATION_BING'])) {
                $XAGIO_SEO_VERIFICATION_BING = base64_decode(sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_VERIFICATION_BING'])));
                update_option('XAGIO_SEO_VERIFICATION_BING', $XAGIO_SEO_VERIFICATION_BING);
            }

            if (isset($_POST['XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS'])) {
                $XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS = base64_decode(sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS'])));
                update_option('XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS', $XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS);
            }

            if (isset($_POST['XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS_4'])) {
                $XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS_4 = base64_decode(sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS_4'])));
                update_option('XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS_4', $XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS_4);
            }

            if (isset($_POST['XAGIO_SEO_VERIFICATION_GOOGLE_TAG_HEAD'])) {
                $XAGIO_SEO_VERIFICATION_GOOGLE_TAG_HEAD = base64_decode(sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_VERIFICATION_GOOGLE_TAG_HEAD'])));
                update_option('XAGIO_SEO_VERIFICATION_GOOGLE_TAG_HEAD', $XAGIO_SEO_VERIFICATION_GOOGLE_TAG_HEAD);
            }

            if (isset($_POST['XAGIO_SEO_VERIFICATION_GOOGLE_TAG_BODY'])) {
                $XAGIO_SEO_VERIFICATION_GOOGLE_TAG_BODY = base64_decode(sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_VERIFICATION_GOOGLE_TAG_BODY'])));
                update_option('XAGIO_SEO_VERIFICATION_GOOGLE_TAG_BODY', $XAGIO_SEO_VERIFICATION_GOOGLE_TAG_BODY);
            }

            if (isset($_POST['XAGIO_SEO_VERIFICATION_PINTEREST'])) {
                $XAGIO_SEO_VERIFICATION_PINTEREST = base64_decode(sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_VERIFICATION_PINTEREST'])));
                update_option('XAGIO_SEO_VERIFICATION_PINTEREST', $XAGIO_SEO_VERIFICATION_PINTEREST);
            }

            if (isset($_POST['XAGIO_SEO_VERIFICATION_YANDEX'])) {
                $XAGIO_SEO_VERIFICATION_YANDEX = base64_decode(sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_VERIFICATION_YANDEX'])));
                update_option('XAGIO_SEO_VERIFICATION_YANDEX', $XAGIO_SEO_VERIFICATION_YANDEX);
            }

            // Return a success message
            xagio_json('success', "Operation completed!");
        }


        public static function saveEditors()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $allowed_tags = [
                'script' => [
                    'type' => true,
                    'src'  => true
                ]
            ];

            // Process each POST field individually and safely
            if (isset($_POST['XAGIO_SEO_GLOBAL_SCRIPTS_HEAD'])) {
                $XAGIO_SEO_GLOBAL_SCRIPTS_HEAD = base64_decode(wp_kses(wp_unslash($_POST['XAGIO_SEO_GLOBAL_SCRIPTS_HEAD']), $allowed_tags));
                update_option('XAGIO_SEO_GLOBAL_SCRIPTS_HEAD', $XAGIO_SEO_GLOBAL_SCRIPTS_HEAD);
            }

            if (isset($_POST['XAGIO_SEO_GLOBAL_SCRIPTS_BODY'])) {
                $XAGIO_SEO_GLOBAL_SCRIPTS_BODY = base64_decode(wp_kses(wp_unslash($_POST['XAGIO_SEO_GLOBAL_SCRIPTS_BODY']), $allowed_tags));
                update_option('XAGIO_SEO_GLOBAL_SCRIPTS_BODY', $XAGIO_SEO_GLOBAL_SCRIPTS_BODY);
            }

            if (isset($_POST['XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER'])) {
                $XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER = base64_decode(wp_kses(wp_unslash($_POST['XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER']), $allowed_tags));
                update_option('XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER', $XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER);
            }

            // Return a success message
            xagio_json('success', "Operation completed!");
        }


        public static function saveSettings()
        {
            // Check nonce
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // Check and sanitize each field individually
            if (isset($_POST['XAGIO_DISABLE_SCRIPTS_LOGGED_IN'])) {
                $disable_scripts_logged_in = sanitize_text_field(wp_unslash($_POST['XAGIO_DISABLE_SCRIPTS_LOGGED_IN']));
                update_option('XAGIO_DISABLE_SCRIPTS_LOGGED_IN', xagio_parse_bool($disable_scripts_logged_in));
            }

            if (isset($_POST['XAGIO_DISABLE_MAINTENANCE'])) {
                $disable_maintenance = sanitize_text_field(wp_unslash($_POST['XAGIO_DISABLE_MAINTENANCE']));
                update_option('XAGIO_DISABLE_MAINTENANCE', xagio_parse_bool($disable_maintenance));
            }

            if (isset($_POST['XAGIO_SEO_FORCE_ENABLE'])) {
                $force_enable = sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_FORCE_ENABLE']));
                update_option('XAGIO_SEO_FORCE_ENABLE', xagio_parse_bool($force_enable));
            }

            if (isset($_POST['XAGIO_DISABLE_HTML_FOOTPRINT'])) {
                $disable_html_footprint = sanitize_text_field(wp_unslash($_POST['XAGIO_DISABLE_HTML_FOOTPRINT']));
                update_option('XAGIO_DISABLE_HTML_FOOTPRINT', xagio_parse_bool($disable_html_footprint));
            }

            if (isset($_POST['XAGIO_DISABLE_WP_CANONICALS'])) {
                $disable_wp_canonical = sanitize_text_field(wp_unslash($_POST['XAGIO_DISABLE_WP_CANONICALS']));
                update_option('XAGIO_DISABLE_WP_CANONICALS', xagio_parse_bool($disable_wp_canonical));
            }

            if (isset($_POST['XAGIO_FORCE_HOMEPAGE_SCHEMA'])) {
                $force_homepage_schema = sanitize_text_field(wp_unslash($_POST['XAGIO_FORCE_HOMEPAGE_SCHEMA']));
                update_option('XAGIO_FORCE_HOMEPAGE_SCHEMA', xagio_parse_bool($force_homepage_schema));
            }

            if (isset($_POST['XAGIO_RENDER_PRETTY_SCHEMAS'])) {
                $render_pretty_schemas = sanitize_text_field(wp_unslash($_POST['XAGIO_RENDER_PRETTY_SCHEMAS']));
                update_option('XAGIO_RENDER_PRETTY_SCHEMAS', xagio_parse_bool($render_pretty_schemas));
            }



            if (isset($_POST['XAGIO_GOOGLE_SEARCH_WINDOW_BROAD'])) {
                $window_broad = sanitize_text_field(wp_unslash($_POST['XAGIO_GOOGLE_SEARCH_WINDOW_BROAD']));
                update_option('XAGIO_GOOGLE_SEARCH_WINDOW_BROAD', xagio_parse_bool($window_broad));
            }
            if (isset($_POST['XAGIO_GOOGLE_SEARCH_WINDOW_PHRASE'])) {
                $window_phrase = sanitize_text_field(wp_unslash($_POST['XAGIO_GOOGLE_SEARCH_WINDOW_PHRASE']));
                update_option('XAGIO_GOOGLE_SEARCH_WINDOW_PHRASE', xagio_parse_bool($window_phrase));
            }
            if (isset($_POST['XAGIO_GOOGLE_SEARCH_WINDOW_INTITLE'])) {
                $window_in_title = sanitize_text_field(wp_unslash($_POST['XAGIO_GOOGLE_SEARCH_WINDOW_INTITLE']));
                update_option('XAGIO_GOOGLE_SEARCH_WINDOW_INTITLE', xagio_parse_bool($window_in_title));
            }
            if (isset($_POST['XAGIO_GOOGLE_SEARCH_WINDOW_INURL'])) {
                $window_in_url = sanitize_text_field(wp_unslash($_POST['XAGIO_GOOGLE_SEARCH_WINDOW_INURL']));
                update_option('XAGIO_GOOGLE_SEARCH_WINDOW_INURL', xagio_parse_bool($window_in_url));
            }







            if (isset($_POST['XAGIO_DEV_MODE'])) {
                $dev_mode = sanitize_text_field(wp_unslash($_POST['XAGIO_DEV_MODE']));
                update_option('XAGIO_DEV_MODE', xagio_parse_bool($dev_mode));
            }

            if (isset($_POST['XAGIO_DISABLE_UPLOADS'])) {
                $disable_uploads = sanitize_text_field(wp_unslash($_POST['XAGIO_DISABLE_UPLOADS']));
                update_option('XAGIO_DISABLE_UPLOADS', xagio_parse_bool($disable_uploads));
            }

            if (isset($_POST['XAGIO_RECAPTCHA'])) {
                $recaptcha = sanitize_text_field(wp_unslash($_POST['XAGIO_RECAPTCHA']));
                update_option('XAGIO_RECAPTCHA', xagio_parse_bool($recaptcha));
            }

            if (isset($_POST['XAGIO_RECAPTCHA_SITE_KEY'])) {
                $recaptcha_site_key = sanitize_text_field(wp_unslash($_POST['XAGIO_RECAPTCHA_SITE_KEY']));
                update_option('XAGIO_RECAPTCHA_SITE_KEY', $recaptcha_site_key);
            }

            if (isset($_POST['XAGIO_RECAPTCHA_SECRET_KEY'])) {
                $recaptcha_secret_key = sanitize_text_field(wp_unslash($_POST['XAGIO_RECAPTCHA_SECRET_KEY']));
                update_option('XAGIO_RECAPTCHA_SECRET_KEY', $recaptcha_secret_key);
            }

            if (isset($_POST['XAGIO_BACKUPS_IGNORE_DOMAINS'])) {
                $backups_ignore_domains = sanitize_text_field(wp_unslash($_POST['XAGIO_BACKUPS_IGNORE_DOMAINS']));
                update_option('XAGIO_BACKUPS_IGNORE_DOMAINS', xagio_parse_bool($backups_ignore_domains));
            }

            // Return success response
            xagio_json('success', "Operation completed!");
        }


        public static function showTutorial()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (isset($_POST['page']) && !empty($_POST['page'])) {

                $page = sanitize_text_field(wp_unslash($_POST['page']));

                $video_codes = XAGIO_API::apiRequest(
                    'get_tutorial', 'POST', [
                        'page' => $page,
                    ]
                );
                xagio_json('success', 'Success.', $video_codes);
            } else {
                xagio_json('error', 'Not allowed.');
            }

        }

        public static function fixCommonIssues()
        {
            // updateAPIKeys
            XAGIO_SYNC::getAPIKeys();
            // updateSharedScripts
            XAGIO_SYNC::getSharedScripts();
            // updateServerAPI
            do_action('xagio_LicenseCheck');

            // Regenerate table structure
            XAGIO_CORE::loadModels('createTable');
            //Fix Keywords stuck in queue

            global $wpdb;
            $wpdb->update('xag_batches', ['queued' => 0], ['queued' => 1]);

            XAGIO_LICENSE::checkLicenseRemote();
        }

        public static function updateBackupSettings()
        {
            XAGIO_SYNC::getBackupSettings();
            xagio_json('success', "Successfully updated Backup settings on this website!");
        }

        public static function createBackup()
        {
            xagio_jsonc(XAGIO_MODEL_BACKUPS::doBackup());
        }

        public static function exportOptions()
        {
            // Set headers for plain text and JSON download
            header("Content-type: text/plain");
            header('Content-Disposition: attachment; filename=Xagio_Export_Settings_' . gmdate('Y-m-d_H:i:s') . '.psexp');
            header('Content-type: application/json');

            global $wpdb;

            // List of options to export
            $all_wp_options = [
                'XAGIO_DISABLE_HTML_FOOTPRINT',
                'XAGIO_DISABLE_WP_CANONICALS',
                'XAGIO_FORCE_HOMEPAGE_SCHEMA',
                'XAGIO_RENDER_PRETTY_SCHEMAS',
                'XAGIO_SEO_GLOBAL_SCRIPTS_HEAD',
                'XAGIO_SEO_GLOBAL_SCRIPTS_BODY',
                'XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER',
                'XAGIO_SEO_DEFAULT_OG',
                'XAGIO_USE_META_KEYWORD',
                'XAGIO_SEO_TITLE_SEPARATOR',
                'XAGIO_SEO_TARGET_KEYWORD',
                'ps_seo_force_noodp',
                'ps_seo_index_subpages',
                'XAGIO_SEO_FORCE_ENABLE',
                'XAGIO_SEO_TITLE',
                'XAGIO_SEO_DESCRIPTION',
                'XAGIO_SEO_DEFAULT_POST_TYPES',
                'XAGIO_SEO_DEFAULT_POST_OG',
                'XAGIO_SEO_DEFAULT_CUSTOM_POST_TYPES',
                'XAGIO_SEO_DEFAULT_TAXONOMIES',
                'XAGIO_SEO_DEFAULT_MISCELLANEOUS',
                'XAGIO_SEO_FACEBOOK_TITLE',
                'XAGIO_SEO_FACEBOOK_DESCRIPTION',
                'XAGIO_SEO_FACEBOOK_IMAGE',
                'XAGIO_SEO_TWITTER_TITLE',
                'XAGIO_SEO_TWITTER_DESCRIPTION',
                'XAGIO_SEO_TWITTER_IMAGE',
                'XAGIO_SEO_VERIFICATION_BING',
                'XAGIO_SEO_VERIFICATION_GOOGLE',
                'XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS',
                'XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS_4',
                'XAGIO_SEO_VERIFICATION_GOOGLE_TAG_HEAD',
                'XAGIO_SEO_VERIFICATION_GOOGLE_TAG_BODY',
                'XAGIO_SEO_VERIFICATION_PINTEREST',
                'XAGIO_SEO_VERIFICATION_YANDEX',
                'XAGIO_SCHEMA_ALWAYS_ON',
                'XAGIO_RECAPTCHA',
                'ps_remove_footprint'
            ];

            $return = [];
            // Collect all options
            foreach ($all_wp_options as $wp_option) {
                $option_value = get_option($wp_option);
                if ($option_value !== false) {
                    $return['options'][$wp_option] = $option_value;
                }
            }

            // List of post meta to export
            $all_post_meta = [
                'XAGIO_SEO',
                'XAGIO_SEO_SEARCH_PREVIEW_ENABLE',
                'XAGIO_SEO_SOCIAL_ENABLE',
                'XAGIO_SEO_META_ROBOTS_ENABLE',
                'XAGIO_SEO_SCHEMA_ENABLE',
                'XAGIO_SEO_SCRIPTS_ENABLE',
                'XAGIO_SEO_TITLE',
                'XAGIO_SEO_URL',
                'XAGIO_SEO_DESCRIPTION',
                'XAGIO_SEO_TARGET_KEYWORD',
                'XAGIO_SEO_META_ROBOTS',
                'XAGIO_SEO_META_ROBOTS_INDEX',
                'XAGIO_SEO_META_ROBOTS_FOLLOW',
                'XAGIO_SEO_META_ROBOTS_ADVANCED',
                'XAGIO_SEO_CANONICAL_URL',
                'XAGIO_SEO_TWITTER_TITLE',
                'XAGIO_SEO_TWITTER_DESCRIPTION',
                'XAGIO_SEO_TWITTER_IMAGE',
                'XAGIO_SEO_FACEBOOK_TITLE',
                'XAGIO_SEO_FACEBOOK_DESCRIPTION',
                'XAGIO_SEO_FACEBOOK_IMAGE',
                'XAGIO_SEO_NOTES',
            ];

            $by_id = [];
            // Collect all post meta
            foreach ($all_post_meta as $meta_key) {
                // Securely prepare the query using wpdb::prepare
                $results = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_key, meta_value FROM wp_postmeta WHERE meta_key = %s", $meta_key), ARRAY_A);

                // Organize results by post ID
                foreach ($results as $result) {
                    $post_id = $result['post_id'];
                    unset($result['post_id']); // Remove post_id from the individual result
                    $by_id[$post_id][] = $result; // Store by post ID
                }
            }

            // Add postmeta data to the return array
            $return['postmeta'] = $by_id;

            // Output the collected options and postmeta as JSON
            echo wp_json_encode($return, JSON_PRETTY_PRINT);

            exit; // Make sure to terminate script execution after outputting the file
        }


        public static function importOptions()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_FILES['import_options_file'])) {
                xagio_json('error', 'File is not sent!');
            }

            $file = map_deep(wp_unslash($_FILES['import_options_file']), 'sanitize_text_field');

            if ($file['error'] == UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {

                $info = pathinfo($file['name']);
                if ($info["extension"] != "psexp") {
                    xagio_json('error', 'File does not contain right extension!');
                }

                $json = xagio_file_get_contents($file['tmp_name']);
                $json = @json_decode($json, TRUE);

                if ($json === NULL && json_last_error() !== JSON_ERROR_NONE) {
                    xagio_json('error', 'File that you uploaded is corrupted.');
                }
                if (!$json['options'])
                    xagio_json('error', 'File that you uploaded is corrupted.');
                if (!$json['postmeta'])
                    xagio_json('error', 'File that you uploaded is corrupted.');

                $options  = $json['options'];
                $postmeta = $json['postmeta'];

                foreach ($options as $key => $option) {
                    update_option($key, $option);
                }

                foreach ($postmeta as $key => $metas) {
                    $post_id = $key;
                    if (get_post_status($post_id) != FALSE) {
                        foreach ($metas as $value) {
                            update_post_meta($post_id, $value['meta_key'], maybe_unserialize($value['meta_value']));
                        }
                    }
                }
                xagio_json('success', 'Successfully imported settings.');

            } else {
                xagio_json('error', 'Error on upload, please contact support.');
            }


        }


    }

}
