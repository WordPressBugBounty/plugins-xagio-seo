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
        }

        public static function enqueue_assets($hook)
        {
            if ('plugins.php' !== $hook) {
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
                    $apiEndpoint = 'license', $method = 'DELETE', $args = ['delete' => 'all'], $http_code, $without_license = FALSE
                );

                delete_option('XAGIO_LICENSE_EMAIL');
                delete_option('XAGIO_LICENSE_KEY');

                $alreadyRemoved = true;
            }

            if (isset($_POST['xagio_remove_license']) && !$alreadyRemoved) {
                XAGIO_API::apiRequest(
                    $apiEndpoint = 'license', $method = 'DELETE', $args = [], $http_code, $without_license = FALSE
                );
                delete_option('XAGIO_LICENSE_EMAIL');
                delete_option('XAGIO_LICENSE_KEY');
            }

            if (isset($_POST['xagio_remove_data'])) {
                XAGIO_CORE::loadModels('removeTable');
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

            $url      = sanitize_url(wp_unslash($_POST['url']));
            $redirect = sanitize_text_field(wp_unslash($_POST['redirect']));
            $email    = sanitize_email(wp_unslash($_POST['email']));
            $api_key  = XAGIO_API::getAPIKey(true);

            if ($api_key != false) {

                xagio_jsonc([
                    'status'  => 'success',
                    'message' => 'API key generated.',
                    'data'    => [
                        'url'      => $url,
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
                $apiEndpoint = 'license', $method = 'DELETE', $args = [], $http_code, $without_license = FALSE
            );

            if ($http_code == 200) {
                update_option('XAGIO_LICENSE_EMAIL', false);
                update_option('XAGIO_LICENSE_KEY', false);

                xagio_json("success", "Account disconnected successfully.");
            } else {

                update_option('XAGIO_LICENSE_EMAIL', false);
                update_option('XAGIO_LICENSE_KEY', false);
                xagio_json("error", "Failed to disconnect account!", $disconnect);
            }
        }

        public static function getXagioLinksDashboard()
        {
            $result = XAGIO_API::apiRequest($endpoint = 'info', $method = 'GET', [
                'type' => 'xagio_links',
            ], $http_code);

            // Cache the result for 1 day (86400 seconds)
            set_transient('xagio_links_transient', $result, 86400);

            // Set content type to JSON
            xagio_jsonc($result);
        }

        public static function getXagioLinks()
        {
            $transient_key = 'xagio_links_transient';
            $cached_result = get_transient($transient_key);

            if ($cached_result != false) {
                // If we have a cached result, use it
                $result = $cached_result;
            } else {
                // Otherwise, make the API request
                $http_code = 0;
                $result    = XAGIO_API::apiRequest($endpoint = 'info', $method = 'GET', [
                    'type' => 'xagio_links',
                ], $http_code);

                // Cache the result for 1 day (86400 seconds)
                set_transient($transient_key, $result, 86400);
            }

            // Set content type to JSON
            xagio_jsonc($result);
        }

        public static function getAnnouncements()
        {
            $announcements = XAGIO_API::apiRequest(
                $apiEndpoint = 'announcements', $method = 'GET', $args = [], $http_code, $without_license = TRUE
            );

            // set content type to json
            xagio_output($announcements, 'application/json');
        }

        public static function checkRequirements()
        {
            // Define requirements
            $requirements = [
                'PHP Version is too low'                              => version_compare(PHP_VERSION, '7.0.0', '>='),
                'PHP Post Max Size is too low'                        => self::convertToBytes(ini_get('post_max_size')) >= 8 * 1024 * 1024,
                'PHP Max Upload Size is too low'                      => self::convertToBytes(ini_get('upload_max_filesize')) >= 2 * 1024 * 1024,
                'PHP Time Limit is too low'                           => ini_get('max_execution_time') == 0 || ini_get('max_execution_time') >= 15,
                'PHP Max Input Vars is too low'                       => ini_get('max_input_vars') >= 500,
                'PHP Memory Limit is too low'                         => self::convertToBytes(ini_get('memory_limit')) >= 64 * 1024 * 1024 || ini_get('memory_limit') == -1,
                'OpenSSL not installed'                               => extension_loaded('openssl'),
                'cURL not installed'                                  => extension_loaded('curl'),
                'ZipArchive not installed'                            => class_exists('ZipArchive'),
                'DOMDocument not installed'                           => class_exists('DOMDocument'),
                'WP Remote Get not found'                             => function_exists('wp_remote_get'),
                'WP Remote Post not found'                            => function_exists('wp_remote_post'),
                'WordPress Permalinks not set to Post name or Custom' => self::isPermalinkStructureCorrect(),
            ];

            // Check if there are any warnings
            $warnings = array_filter($requirements, function ($status) {
                return !$status;
            });

            if (empty($warnings)) {
                // Return null if no warnings
                return null;
            }

            // Prepare warning results
            $result = '<div class="xagio-panel xagio-margin-bottom-medium">';
            $result .= '<div class="xagio-panel-title">Your server does not meet all requirements to run Xagio properly!</div>';
            $result .= '<div class="xagio-table-responsive">';
            $result .= '<table class="xagio-system-status-table">';
            $result .= '<tbody>';

            foreach ($requirements as $requirement => $status) {
                if (!$status) {
                    $result .= '<tr>';
                    $result .= '<td><i class="xagio-icon xagio-icon-warning xagio-warning"></i> ' . esc_html($requirement) . '</td>';
                    $result .= '</tr>';
                }
            }

            $result .= '</tbody>';
            $result .= '</table>';
            $result .= '</div>';
            $result .= '<p>The Xagio plugin may not function correctly or communicate with the Dashboard as intended. Please address the above issues before using Xagio.</p>';
            $result .= '</div>';

            echo wp_kses_post($result);
        }

        private static function convertToBytes($value)
        {
            $unit  = strtolower(substr($value, -1));
            $bytes = (int)$value;

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