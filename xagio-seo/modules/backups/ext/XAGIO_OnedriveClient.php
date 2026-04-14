<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_OnedriveClient')) {
    class XAGIO_OnedriveClient
    {
        // Set the chunk size to 50 MB (50 × 1024 × 1024 bytes)
        const UPLOAD_CHUNK_SIZE = 52428800;

        /** App Setting **/
        private $appParams;
        private $accessToken;

        /** Microsoft Graph root URL and version **/
        const GRAPH_URL = "https://graph.microsoft.com/v1.0";

        /** Authorization URL **/
        const AUTH_URL = "https://login.microsoftonline.com/common/oauth2/v2.0/";

        public function __construct()
        {
            $this->appParams   = [
                'client_id'     => XAGIO_ONEDRIVE_KEY,
                'client_secret' => XAGIO_ONEDRIVE_SECRET
            ];
            $this->accessToken = null;
        }

        public function SetAccessToken($token)
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

            if (empty($at)) {
                throw new XAGIO_OnedriveException('Could not get access token!');
            }

            if (isset($at['error'])) {
                throw new XAGIO_OnedriveException(esc_html($at['error_description']));
            }

            $BACKUP_SETTINGS             = get_option("XAGIO_BACKUP_SETTINGS");
            $BACKUP_SETTINGS['onedrive'] = $at;
            update_option('XAGIO_BACKUP_SETTINGS', $BACKUP_SETTINGS);

            $this->accessToken = $at;
        }

        /** API Functions **/

        public function CreateFolder()
        {
            $folder = $this->apiCall('/drive/root:/xagio:/children', 'GET');
            if (isset($folder['error'])) {
                // Folder doesn't exist; create it.
                return $this->apiCall('/drive/root/children', 'POST', array(
                    "name"   => "xagio",
                    "folder" => array()
                ));
            } else {
                return false;
            }
        }

        public function GetFileFolder($xagio_location)
        {
            return $this->apiCall($xagio_location, 'GET');
        }

        /**
         * Starts an upload by:
         * - Creating an upload session with OneDrive.
         * - Storing the upload details as an option.
         * - Scheduling a WP Cron event to process the file upload in chunks.
         */
        public function upload($source_file = '', $createID = 0)
        {
            if (!file_exists($source_file)) {
                return array('error' => 'Source file does not exist.');
            }

            // Get the filename and file size.
            $filename  = basename($source_file);
            $file_size = filesize($source_file);

            // Create an upload session.
            $session = $this->apiCall('/drive/root:/xagio/' . $filename . ':/createUploadSession', 'POST', array(
                "item" => array(
                    "@microsoft.graph.conflictBehavior" => "rename",
                    "name"                              => $filename
                )
            ));

            if (empty($session) || !isset($session['uploadUrl'])) {
                return array('error' => 'Failed to create upload session');
            }

            $upload_url = $session['uploadUrl'];

            // Save the job details as an option.
            $upload_data = array(
                $source_file,
                $upload_url,
                0,
                $file_size,
                $createID
            );

            // Schedule a WP Cron event to process the upload.
            if (!wp_next_scheduled('XAGIO_OnedriveClient_Process_Upload', $upload_data)) {
                wp_schedule_single_event(time() + 10, 'XAGIO_OnedriveClient_Process_Upload', $upload_data);
            }

            return true;
        }

        /** Helper Functions **/

        private function authCall($path, $request_data = null)
        {
            $xagio_url     = self::AUTH_URL . $path;
            $headers = array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            );
            $xagio_args    = array(
                'method'     => 'POST',
                'headers'    => $headers,
                'body'       => http_build_query($request_data),
                'timeout'    => 400,
                'sslverify'  => false,
                'cookies'    => array(),
                'user-agent' => 'Xagio (SSL Connection)'
            );

            $xagio_response = wp_remote_post($xagio_url, $xagio_args);

            if (is_wp_error($xagio_response)) {
                return array('error' => $xagio_response->get_error_message());
            }

            $body = wp_remote_retrieve_body($xagio_response);
            return json_decode($body, true);
        }

        public function apiCall($path, $method = "GET", $fields = null)
        {
            $xagio_url     = self::GRAPH_URL . $path;
            $headers = array(
                'Authorization' => 'Bearer ' . $this->accessToken['access_token'],
                'Content-Type'  => 'application/json'
            );
            $xagio_args    = array(
                'method'    => $method,
                'headers'   => $headers,
                'sslverify' => false,
                'timeout'   => 400,
            );

            if ($method === "POST") {
                $xagio_args['body'] = wp_json_encode($fields, JSON_FORCE_OBJECT);
            }

            $xagio_response = wp_remote_request($xagio_url, $xagio_args);

            if (is_wp_error($xagio_response)) {
                return array('error' => $xagio_response->get_error_message());
            }

            $body = wp_remote_retrieve_body($xagio_response);
            return json_decode($body, true);
        }

        public function uploadCall($xagio_url, $headers, $xagio_file)
        {
            $xagio_args = array(
                'method'    => 'PUT',
                'headers'   => $headers,
                'body'      => $xagio_file,
                'sslverify' => false,
                'timeout'   => 400,
            );

            $xagio_response = wp_remote_request($xagio_url, $xagio_args);

            if (is_wp_error($xagio_response)) {
                return array('error' => $xagio_response->get_error_message());
            }

            $body = wp_remote_retrieve_body($xagio_response);
            return json_decode($body, true);
        }

        public function cancelUploadSession($xagio_url)
        {
            $xagio_args = array(
                'method'    => 'DELETE',
                'sslverify' => false,
                'timeout'   => 400,
            );

            $xagio_response = wp_remote_request($xagio_url, $xagio_args);

            if (is_wp_error($xagio_response)) {
                return array('error' => $xagio_response->get_error_message());
            }

            $body = wp_remote_retrieve_body($xagio_response);
            return json_decode($body, true);
        }

        public function downloadCall($path, $xagio_file = '')
        {
            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }

            $xagio_url     = self::GRAPH_URL . $path;
            $headers = array(
                'Authorization' => 'Bearer ' . $this->accessToken['access_token'],
                'Content-Type'  => 'application/json'
            );
            $xagio_args    = array(
                'method'    => 'GET',
                'headers'   => $headers,
                'sslverify' => false,
                'timeout'   => 400,
            );

            $xagio_response = wp_remote_get($xagio_url, $xagio_args);

            if (is_wp_error($xagio_response)) {
                return array('error' => $xagio_response->get_error_message());
            }

            $body = wp_remote_retrieve_body($xagio_response);

            if (!$wp_filesystem->put_contents($xagio_file, $body, FS_CHMOD_FILE)) {
                return array('error' => 'Failed to write file');
            }

            return json_decode($body, true);
        }

        public function deleteCall($path)
        {
            $xagio_url     = self::GRAPH_URL . $path;
            $headers = array(
                'Authorization' => 'Bearer ' . $this->accessToken['access_token'],
                'Content-Type'  => 'application/json'
            );
            $xagio_args    = array(
                'method'    => 'DELETE',
                'headers'   => $headers,
                'sslverify' => false,
                'timeout'   => 400,
            );

            $xagio_response = wp_remote_request($xagio_url, $xagio_args);

            if (is_wp_error($xagio_response)) {
                return array('error' => $xagio_response->get_error_message());
            }

            return wp_remote_retrieve_response_code($xagio_response);
        }

        /**
         * Static method called via WP Cron to process an upload chunk.
         * It reads one chunk from the source file, uploads it, updates the offset,
         * and reschedules itself until the entire file is uploaded.
         */
        public static function processUploadQueue($source_file, $upload_url, $offset, $file_size, $createID)
        {
            $chunk_size = self::UPLOAD_CHUNK_SIZE;

            // Ensure the file still exists.
            if (!file_exists($source_file)) {
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Local Backup file does not exist, cannot upload.');
                return;
            }

            // Open the file and seek to the current offset.
            $handle = xagio_fopen($source_file, 'rb');
            if (!$handle || xagio_fseek($handle, $offset) !== 0) {
                if ($handle) {
                    xagio_fclose($handle);
                }
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Cannot open Local Backup file, cannot upload.');
                return;
            }

            // Read the next chunk.
            $chunk = xagio_fread($handle, $chunk_size);
            xagio_fclose($handle);

            if ($chunk === false || strlen($chunk) === 0) {
                wp_delete_file($source_file);
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Failed to chunk the backup, cannot upload.');
                return;
            }

            $chunk_length = strlen($chunk);
            $fragment_end = $offset + $chunk_length - 1;
            $headers      = array(
                "Content-Length" => $chunk_length,
                "Content-Range"  => "bytes {$offset}-{$fragment_end}/{$file_size}"
            );

            // Get Access Tokens
            $xagio_tokens                     = get_option("XAGIO_BACKUP_SETTINGS");
            $backup_OnedriveAccessToken = $xagio_tokens["onedrive"] ?? "";

            // Create a new client instance.
            $client = new self();
            $client->SetAccessToken($backup_OnedriveAccessToken);
            $client->renewAccessToken();

            // Upload the current chunk.
            $xagio_response = $client->uploadCall($upload_url, $headers, $chunk);
            if (isset($xagio_response['error'])) {
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'There was a problem while uploading backup to OneDrive: ' . $xagio_response['error']);
                return;
            }
            // (Optional: inspect $xagio_response for errors and handle them.)

            // Update the offset.
            $offset += $chunk_length;

            // Save the job details as an option.
            $upload_data = [
                $source_file,
                $upload_url,
                $offset,
                $file_size,
                $createID
            ];

            if ($offset < $file_size) {
                // Save updated upload data and reschedule the next chunk.
                wp_schedule_single_event(time() + 10, 'XAGIO_OnedriveClient_Process_Upload', $upload_data);
            } else {
                // Remove the files backup
                wp_delete_file($source_file);

                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'success', 'Backup successfully created.');
            }
        }
    }

    class XAGIO_OnedriveException extends Exception
    {
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
            if ($isDebug) {
                self::display_error($err);
            }
        }

        public static function display_error($err)
        {
            wp_die(wp_kses_post($err));
        }
    }
}

