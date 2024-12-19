<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_LICENSE')) {

    class XAGIO_LICENSE
    {

        public static function initialize()
        {
            add_action('XAGIO_CHECK_LICENSE', [
                'XAGIO_LICENSE',
                'checkLicenseRemote'
            ]);
            if (!wp_next_scheduled('XAGIO_CHECK_LICENSE')) {
                wp_schedule_event(time(), 'daily', 'XAGIO_CHECK_LICENSE');
            }
        }

        public static function isLicenseSet(&$XAGIO_LICENSE_EMAIL = FALSE, &$XAGIO_LICENSE_KEY = FALSE)
        {
            $XAGIO_LICENSE_EMAIL = get_option('XAGIO_LICENSE_EMAIL');
            $XAGIO_LICENSE_KEY   = get_option('XAGIO_LICENSE_KEY');
            if ($XAGIO_LICENSE_EMAIL == FALSE || $XAGIO_LICENSE_KEY == FALSE) {
                return FALSE;
            } else {
                return TRUE;
            }
        }

        public static function checkLicenseRemote()
        {
            // Perform remote license deactivation
            $XAGIO_LICENSE_EMAIL = get_option('XAGIO_LICENSE_EMAIL');
            $XAGIO_LICENSE_KEY   = get_option('XAGIO_LICENSE_KEY');

            // Verify if everything is normal
            if (empty($XAGIO_LICENSE_EMAIL) || empty($XAGIO_LICENSE_KEY)) {
                xagio_json('error', 'Invalid request.');
            }

            // Set the domain name
            $domain = wp_parse_url(admin_url(), PHP_URL_HOST);
            $domain = str_replace('www.', '', $domain);

            // Get the API
            $xagio_api = get_option('XAGIO_API');

            // Set the HTTP Query
            $http_query = [
                'license_email' => $XAGIO_LICENSE_EMAIL,
                'license_key'   => $XAGIO_LICENSE_KEY,
                'domain'        => $domain,
                'admin_post'    => XAGIO_MODEL_SETTINGS::getApiUrl(),
                'api_key'       => $xagio_api,
                'blog_name'     => get_bloginfo('name'),
                'blog_desc'     => get_bloginfo('description'),
            ];

            // Build HTTP Query
            $http_query = http_build_query($http_query, '', '&');

            $response = wp_remote_get(XAGIO_PANEL_URL . "/api/license?$http_query", [
                    'user-agent'  => "Xagio - " . XAGIO_CURRENT_VERSION . " ($domain)",
                    'timeout'     => 120,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking'    => TRUE,
                ]);


            // Verify the response
            if (is_wp_error($response)) {
                xagio_json('error', 'There was a problem while communicating with our server. Make sure your server meets all the requirements.');
            } else {
                if (!isset($response['body'])) {
                    xagio_json('error', 'The license information that you submitted is not valid. Please try again.');
                } else {

                    $code     = $response['response']['code'];
                    $response = $response['body'];

                    if (empty($response)) {
                        xagio_json('error', 'The license information that you submitted is not valid. Please try again.');
                    } else {

                        $response = json_decode($response, TRUE);
                        if ($response != FALSE) {

                            $message = $response['message'];
                            if ($code <= 201) {
                                xagio_json('success', 'Operation completed successfully.');
                            } else {
                                self::removeLicense();
                                xagio_json('error', $message);
                            }

                        }

                    }
                }
            }

        }

        public static function removeLicense()
        {
            // Remove license key and license email
            delete_option('XAGIO_LICENSE_EMAIL');
            delete_option('XAGIO_LICENSE_KEY');
        }

    }

}
