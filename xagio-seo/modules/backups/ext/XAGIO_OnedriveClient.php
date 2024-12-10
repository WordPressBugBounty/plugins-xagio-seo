<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'XAGIO_OnedriveClient' ) ) {
    class XAGIO_OnedriveClient {

        /** App Limitations **/
        const MAX_UPLOAD_CHUNK_SIZE = 60000000; // 150MB
        const UPLOAD_CHUNK_SIZE = 4000000; // 4MB

        /** App Setting **/
        private $appParams;
        private $accessToken;

        /** Microsoft Graph root URL and version **/
        const GRAPH_URL = "https://graph.microsoft.com/v1.0";

        /** Authorization URL **/
        const AUTH_URL = "https://login.microsoftonline.com/common/oauth2/v2.0/";

        function __construct( ) {
            $this->appParams = [
                'client_id'     => XAGIO_ONEDRIVE_KEY,
                'client_secret' => XAGIO_ONEDRIVE_SECRET
            ];

            $this->accessToken  = null;
        }

        public function SetAccessToken ($token)
        {
            $this->accessToken = $token;
        }

        public function renewAccessToken()
        {
            if (null === $this->appParams['client_id']) {
                throw new XAGIO_OnedriveException('The client ID must be set to call renewAccessToken()');
            }
            if (null === $this->accessToken['refresh_token']) {
                throw new XAGIO_OnedriveException('The refresh token is not set or no permission for \'wl.offline_access\' was given to renew the token');
            }

            $at = $this->authCall('token', array(
                'client_id'     => $this->appParams['client_id'],
                'client_secret' => $this->appParams['client_secret'],
                'refresh_token' => $this->accessToken['refresh_token'],
                'grant_type'    => 'refresh_token'
            ));

            if ( empty( $at ) ) {
                throw new XAGIO_OnedriveException( 'Could not get access token!');
            }

            if (isset($at['error'])) {
                throw new XAGIO_OnedriveException( esc_html($at['error_description']) );
            }

            return ( $this->accessToken = $at );
        }

        /** API Functions **/
        public function CreateFolder()
        {
            $folder = $this->apiCall('/drive/root:/xagio:/children', 'GET');
            if ( isset($folder['error']) ) {
                return $this->apiCall('/drive/root/children', 'POST', array(
                    "name" => "xagio",
                    "folder" => array()
                ));
            } else {
                return false;
            }
        }

        public function GetFileFolder($location)
        {
            return $this->apiCall($location, 'GET');
        }

        public function upload($source_file = '') {
            global $wp_filesystem;
            // Initialize WP_Filesystem
            if (empty($wp_filesystem)) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }

            // Get the filename
            $filename = basename($source_file);

            // Determine if it needs to be chunked down
            $file_size = $wp_filesystem->size($source_file);

            // Chunk it down baby!
            $file_pointer = $wp_filesystem->get_contents($source_file);
            $file_offset = 0;

            $session = $this->apiCall('/drive/root:/xagio/' . $filename . ':/createUploadSession', 'POST', array(
                "item" => array(
                    "@microsoft.graph.conflictBehavior" => "rename",
                    "name" => $filename
                )
            ));

            $file_session = $session;

            while ($file_offset < $file_size) {
                $chunk = substr($file_pointer, $file_offset, self::UPLOAD_CHUNK_SIZE);

                $fragment_size = $file_offset + strlen($chunk) - 1;
                $headers       = array(
                    "Content-Length" => strlen($chunk),
                    "Content-Range"  => "bytes " . $file_offset . '-' . $fragment_size . '/' . $file_size
                );

                $uploadCall = $this->uploadCall($file_session['uploadUrl'], $headers, $chunk);

                $file_offset += strlen($chunk);

                if ($file_offset >= $file_size) {
                    break;
                }
            }

            return $this->cancelUploadSession($file_session['uploadUrl']);
        }

        /** Helper Functions **/

        private function authCall($path, $request_data = null) {
            $url = self::AUTH_URL . $path;

            $headers = array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            );

            $args = array(
                'method'     => 'POST',
                'headers'    => $headers,
                'body'       => http_build_query($request_data),
                'timeout'    => 400,
                'sslverify'  => false,
                'cookies'    => array(),
                'user-agent' => 'Xagio (SSL Connection)'
            );

            $response = wp_remote_post($url, $args);

            if (is_wp_error($response)) {
                return array('error' => $response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);

            return json_decode($body, true);
        }

        public function apiCall($path, $method = "GET", $fields = null)
        {
            $url = self::GRAPH_URL . $path;

            $headers = array(
                'Authorization' => 'Bearer ' . $this->accessToken['access_token'],
                'Content-Type'  => 'application/json'
            );

            $args = array(
                'method'    => $method,
                'headers'   => $headers,
                'sslverify' => false,
                'timeout'   => 400,
            );

            if ($method == "POST") {
                $args['body'] = wp_json_encode($fields, JSON_FORCE_OBJECT);
            }

            $response = wp_remote_request($url, $args);

            if (is_wp_error($response)) {
                return array('error' => $response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);

            return json_decode($body, true);
        }

        public function uploadCall($url, $headers, $file)
        {
            // Prepare the arguments for the request
            $args = array(
                'method'    => 'PUT',
                'headers'   => $headers,
                'body'      => $file,
                'sslverify' => false,
                'timeout'   => 400,
            );

            // Make the request
            $response = wp_remote_request($url, $args);

            // Check for errors
            if (is_wp_error($response)) {
                return array('error' => $response->get_error_message());
            }

            // Retrieve and return the response body
            $body = wp_remote_retrieve_body($response);
            return json_decode($body, true);
        }

        public function cancelUploadSession($url)
        {
            // Prepare the arguments for the request
            $args = array(
                'method'    => 'DELETE',
                'sslverify' => false,
                'timeout'   => 400,
            );

            // Make the request
            $response = wp_remote_request($url, $args);

            // Check for errors
            if (is_wp_error($response)) {
                return array('error' => $response->get_error_message());
            }

            // Retrieve and return the response body
            $body = wp_remote_retrieve_body($response);
            return json_decode($body, true);
        }

        public function downloadCall($path, $file = '')
        {
            global $wp_filesystem;
            // Initialize WP_Filesystem
            if (empty($wp_filesystem)) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }

            $url = self::GRAPH_URL . $path;

            $headers = array(
                'Authorization' => 'Bearer ' . $this->accessToken['access_token'],
                'Content-Type'  => 'application/json'
            );

            $args = array(
                'method'    => 'GET',
                'headers'   => $headers,
                'sslverify' => false,
                'timeout'   => 400,
            );

            $response = wp_remote_get($url, $args);

            if (is_wp_error($response)) {
                return array('error' => $response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);

            if (!$wp_filesystem->put_contents($file, $body, FS_CHMOD_FILE)) {
                return array('error' => 'Failed to write file');
            }

            return json_decode($body, true);
        }

        public function deleteCall($path)
        {
            $url = self::GRAPH_URL . $path;

            $headers = array(
                'Authorization' => 'Bearer ' . $this->accessToken['access_token'],
                'Content-Type'  => 'application/json'
            );

            $args = array(
                'method'    => 'DELETE',
                'headers'   => $headers,
                'sslverify' => false,
                'timeout'   => 400,
            );

            $response = wp_remote_request($url, $args);

            if (is_wp_error($response)) {
                return array('error' => $response->get_error_message());
            }

            $httpCode = wp_remote_retrieve_response_code($response);
            return $httpCode;
        }

    }

    class XAGIO_OnedriveException extends Exception {

        public function __construct($err = null, $isDebug = false)
        {
            if (is_null($err)) {
                $el            = error_get_last();
                $this->message = $el['message'];
                $this->file    = $el['file'];
                $this->line    = $el['line'];
            } else {
                $this->message = $err;
            }
            if ( $isDebug ) {
                self::display_error( $err );
            }
        }

        public static function display_error( $err ) {
            wp_die( wp_kses_post($err) );
        }
    }
}