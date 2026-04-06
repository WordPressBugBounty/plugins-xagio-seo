<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_DASHBOARD')) {

    class XAGIO_MODEL_DASHBOARD
    {
        public static function initialize()
        {
            if (!XAGIO_HAS_ADMIN_PERMISSIONS)
                return;

            add_action('wp_ajax_xagio_announcements', [
                'XAGIO_MODEL_DASHBOARD',
                'getAnnouncements'
            ]);
            add_action('admin_post_xagio_disconnect_account', [
                'XAGIO_MODEL_DASHBOARD',
                'disconnectAccount'
            ]);
            add_action('admin_post_xagio_get_links', [
                'XAGIO_MODEL_DASHBOARD',
                'getXagioLinks'
            ]);
            add_action('admin_post_xagio_get_links_dashboard', [
                'XAGIO_MODEL_DASHBOARD',
                'getXagioLinksDashboard'
            ]);
            add_action('admin_post_xagio_fix_requirement', [
                'XAGIO_MODEL_DASHBOARD',
                'xagioFixRequirement'
            ]);

            add_action('admin_post_xagio_activate', [
                'XAGIO_MODEL_DASHBOARD',
                'activate'
            ]);

            add_action('admin_enqueue_scripts', [
                'XAGIO_MODEL_DASHBOARD',
                'enqueue_assets'
            ]);
            add_action('admin_post_xagio_deactivate', [
                'XAGIO_MODEL_DASHBOARD',
                'deactivate'
            ]);

			if (XAGIO_CONNECTED) {
		        if (!get_option('XAGIO_ACCOUNT_DETAILS')) {
			        XAGIO_SYNC::getMembershipInfo();
		        }
			}
        }

        public static function enqueue_assets($xagio_hook)
        {
            if ('plugins.php' !== $xagio_hook) {
                return;
            }

            // Add modal HTML template
            add_action('admin_footer', [
                'XAGIO_MODEL_DASHBOARD',
                'deactivation_modal'
            ]);
        }

        public static function deactivation_modal()
        {
            require_once XAGIO_PATH . '/modules/dashboard/metabox/deactivate.php';
        }

        public static function deactivate()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $alreadyRemoved = false;

            if (isset($_POST['xagio_remove_data_remote'])) {
                $disconnect = XAGIO_API::apiRequest(
                    $apiEndpoint = 'license', $method = 'DELETE', $xagio_args = ['delete' => 'all'], $xagio_http_code, $without_license = FALSE
                );

                delete_option('XAGIO_LICENSE_EMAIL');
                delete_option('XAGIO_LICENSE_KEY');

                $alreadyRemoved = true;
            }

            if (isset($_POST['xagio_remove_license']) && !$alreadyRemoved) {
                XAGIO_API::apiRequest(
                    $apiEndpoint = 'license', $method = 'DELETE', $xagio_args = [], $xagio_http_code, $without_license = FALSE
                );
                delete_option('XAGIO_LICENSE_EMAIL');
                delete_option('XAGIO_LICENSE_KEY');
            }

            if (isset($_POST['xagio_remove_data'])) {
                XAGIO_CORE::loadModels('removeTable');

	            global $wpdb;
				$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%XAGIO%'");

	            // 10 sec transient to prevent xagio default options save again
	            set_transient('xagio_deactivating', true, 10);
            }

            xagio_jsonc([
                'status'  => 'success',
                'message' => 'Operation completed.'
            ]);
        }

        public static function activate()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['url'], $_POST['redirect'], $_POST['email'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $xagio_url = sanitize_url(wp_unslash($_POST['url']));
            $redirect  = sanitize_text_field(wp_unslash($_POST['redirect']));
            $email     = sanitize_email(wp_unslash($_POST['email']));
            $api_key   = XAGIO_API::getAPIKey(true);

            if ($api_key != false) {

                xagio_jsonc([
                    'status'  => 'success',
                    'message' => 'API key generated.',
                    'data'    => [
                        'url'      => $xagio_url,
                        'redirect' => $redirect . $api_key,
                        'email'    => $email
                    ]
                ]);

            } else {

                xagio_jsonc([
                    'status'  => 'error',
                    'message' => 'There was a problem while generating your API key!'
                ]);

            }
        }

        public static function disconnectAccount()
        {
            $disconnect = XAGIO_API::apiRequest(
                $apiEndpoint = 'license', $method = 'DELETE', $xagio_args = [], $xagio_http_code, $without_license = FALSE
            );

            if ($xagio_http_code == 200) {
                update_option('XAGIO_LICENSE_EMAIL', false);
                update_option('XAGIO_LICENSE_KEY', false);
                delete_option('XAGIO_ACCOUNT_DETAILS');

                xagio_json("success", "Account disconnected successfully.");
            } else {

                update_option('XAGIO_LICENSE_EMAIL', false);
                update_option('XAGIO_LICENSE_KEY', false);
                xagio_json("error", "Failed to disconnect account!", $disconnect);
            }
        }

        public static function xagioFixRequirement()
        {
            $action = sanitize_text_field(wp_unslash($_POST['fix_action']));

            $requirements_array = [
                "xagio_requirements_php_version",
                "xagio_requirements_php_post_max_size",
                "xagio_requirements_php_max_upload_size",
                "xagio_requirements_php_time_limit",
                "xagio_requirements_php_max_input_vars",
                "xagio_requirements_php_memory_limit",
                "xagio_requirements_openssl",
                "xagio_requirements_curl",
                "xagio_requirements_ziparchive",
                "xagio_requirements_domdocument",
                "xagio_requirements_wp_remote_get",
                "xagio_requirements_wp_remote_post",
                "xagio_requirements_wordpress_permalinks",
                "xagio_requirements_syntax_highlighting"
            ];

            if (in_array($action, $requirements_array)) {
                if (method_exists(self::class, $action)) {
                    self::$action();
                } else {
                    xagio_json('redirect', 'Check out help docs for more information.', 'https://docs.xagio.com/');
                }
            }
        }

        public static function xagio_requirements_syntax_highlighting()
        {
            $user_id = get_current_user_id();
            update_user_meta($user_id, 'syntax_highlighting', 'true');
            xagio_json('success', 'Syntax Highlighting Enabled. Refreshing Page');
        }

        public static function xagio_requirements_wordpress_permalinks()
        {
            global $wp_rewrite;
            $wp_rewrite->set_permalink_structure('/%postname%/');
            $wp_rewrite->flush_rules();
            xagio_json('success', 'Permalinks are set to /%postname%/. Refreshing Page');
        }

        public static function getXagioLinksDashboard()
        {
            $xagio_result = XAGIO_API::apiRequest($endpoint = 'info', $method = 'GET', [
                'type' => 'xagio_links',
            ], $xagio_http_code);

            // Cache the result for 1 day (86400 seconds)
            set_transient('xagio_links_transient', $xagio_result, 86400);

            // Set content type to JSON
            xagio_jsonc($xagio_result);
        }

        public static function getXagioLinks()
        {
            $transient_key = 'xagio_links_transient';
            $cached_result = get_transient($transient_key);

            if ($cached_result != false) {
                // If we have a cached result, use it
                $xagio_result = $cached_result;
            } else {
                // Otherwise, make the API request
                $xagio_http_code = 0;
                $xagio_result    = XAGIO_API::apiRequest($endpoint = 'info', $method = 'GET', [
                    'type' => 'xagio_links',
                ], $xagio_http_code);

                // Cache the result for 1 day (86400 seconds)
                set_transient($transient_key, $xagio_result, 86400);
            }

            // Set content type to JSON
            xagio_jsonc($xagio_result);
        }

        public static function getAnnouncements()
        {
            $announcements = XAGIO_API::apiRequest(
                $apiEndpoint = 'announcements', $method = 'GET', $xagio_args = [], $xagio_http_code, $without_license = TRUE
            );

            // set content type to json
            xagio_output($announcements, 'application/json');
        }

        public static function checkRequirements()
        {
            // Define requirements
            $requirements = [
                'PHP Version is too low'                                         => [
                    'condition'   => version_compare(PHP_VERSION, '7.0.0', '>='),
                    'action'      => 'xagio_requirements_php_version',
                    'button_text' => 'Visit Help Docs'
                ],
                'PHP Post Max Size is too low'                                   => [
                    'condition'   => (self::convertToBytes(ini_get('post_max_size')) >= 8 * 1024 * 1024),
                    'action'      => 'xagio_requirements_php_post_max_size',
                    'button_text' => 'Visit Help Docs'
                ],
                'PHP Max Upload Size is too low'                                 => [
                    'condition'   => (self::convertToBytes(ini_get('upload_max_filesize')) >= 2 * 1024 * 1024),
                    'action'      => 'xagio_requirements_php_max_upload_size',
                    'button_text' => 'Visit Help Docs'
                ],
                'PHP Time Limit is too low'                                      => [
                    'condition'   => (ini_get('max_execution_time') == 0 || ini_get('max_execution_time') >= 15),
                    'action'      => 'xagio_requirements_php_time_limit',
                    'button_text' => 'Visit Help Docs'
                ],
                'PHP Max Input Vars is too low'                                  => [
                    'condition'   => (ini_get('max_input_vars') >= 500),
                    'action'      => 'xagio_requirements_php_max_input_vars',
                    'button_text' => 'Visit Help Docs'
                ],
                'PHP Memory Limit is too low'                                    => [
                    'condition'   => (self::convertToBytes(ini_get('memory_limit')) >= 64 * 1024 * 1024 || ini_get('memory_limit') == -1),
                    'action'      => 'xagio_requirements_php_memory_limit',
                    'button_text' => 'Visit Help Docs'
                ],
                'OpenSSL not installed'                                          => [
                    'condition'   => extension_loaded('openssl'),
                    'action'      => 'xagio_requirements_openssl',
                    'button_text' => 'Visit Help Docs'
                ],
                'cURL not installed'                                             => [
                    'condition'   => extension_loaded('curl'),
                    'action'      => 'xagio_requirements_curl',
                    'button_text' => 'Visit Help Docs'
                ],
                'ZipArchive not installed'                                       => [
                    'condition'   => class_exists('ZipArchive'),
                    'action'      => 'xagio_requirements_ziparchive',
                    'button_text' => 'Visit Help Docs'
                ],
                'DOMDocument not installed'                                      => [
                    'condition'   => class_exists('DOMDocument'),
                    'action'      => 'xagio_requirements_domdocument',
                    'button_text' => 'Visit Help Docs'
                ],
                'WP Remote Get not found'                                        => [
                    'condition'   => function_exists('wp_remote_get'),
                    'action'      => 'xagio_requirements_wp_remote_get',
                    'button_text' => 'Visit Help Docs'
                ],
                'WP Remote Post not found'                                       => [
                    'condition'   => function_exists('wp_remote_post'),
                    'action'      => 'xagio_requirements_wp_remote_post',
                    'button_text' => 'Visit Help Docs'
                ],
                'WordPress Permalinks not set to Post name or Custom'            => [
                    'condition'   => self::isPermalinkStructureCorrect(),
                    'action'      => 'xagio_requirements_wordpress_permalinks',
                    'button_text' => 'Fix This Issue'
                ],
                'Syntax Highlighting is disabled, please check Profile Settings' => [
                    'condition'   => ('false' !== wp_get_current_user()->syntax_highlighting),
                    'action'      => 'xagio_requirements_syntax_highlighting',
                    'button_text' => 'Fix This Issue'
                ],
            ];

            // Check if there are any warnings
            $warnings = array_filter($requirements, function ($status) {
                return !$status['condition'];
            });

            if (empty($warnings)) {
                // Return null if no warnings
                return null;
            }

            // Prepare warning results
            $xagio_result = '<div class="xagio-panel xagio-margin-bottom-medium">';
            $xagio_result .= '<div class="xagio-panel-title">Your server does not meet all requirements to run Xagio properly!</div>';
            $xagio_result .= '<div class="xagio-table-responsive">';
            $xagio_result .= '<table class="xagio-system-status-table">';
            $xagio_result .= '<tbody>';

            foreach ($requirements as $requirement => $status) {
                $action_name = $status['action'];

                if (!$status['condition']) {
                    $xagio_result .= '<tr>';
                    $xagio_result .= '<td class="xagio-requirement-cell"><i class="xagio-icon xagio-icon-warning xagio-warning"></i> ' . esc_html($requirement) . '</td>';
                    $xagio_result .= '<td class="xagio-requirement-button"><button class="xagio-button xagio-button-small xagio-button-primary xagio-fix-requirement" data-xagio-action="' . esc_attr($action_name) . '">' . esc_attr($status['button_text']) . '</button></td>';
                    $xagio_result .= '</tr>';
                }
            }

            $xagio_result .= '</tbody>';
            $xagio_result .= '</table>';
            $xagio_result .= '</div>';
            $xagio_result .= '<p>The Xagio plugin may not function correctly or communicate with the Dashboard as intended. Please address the above issues before using Xagio.</p>';
            $xagio_result .= '</div>';

            echo wp_kses_post($xagio_result);
        }

        private static function convertToBytes($xagio_value)
        {
            $unit  = strtolower(substr($xagio_value, -1));
            $bytes = (int)$xagio_value;

            switch ($unit) {
                case 'g':
                    $bytes *= 1024;
                case 'm':
                    $bytes *= 1024;
                case 'k':
                    $bytes *= 1024;
            }

            return $bytes;
        }

        private static function isPermalinkStructureCorrect()
        {
            if (function_exists('get_option')) {
                $permalink_structure = get_option('permalink_structure');

                // Check if the structure is not empty (custom or specific like /%postname%/)
                if (!empty($permalink_structure)) {
                    // Specific check for "Post name" structure
                    return true;
                }
            }

            // Return false if not a custom structure or function is unavailable
            return false;
        }

    }

}