<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('XAGIO_GoogleDrive')) {

    class XAGIO_GoogleDrive
    {

        // Define a chunk size constant (50 MB, must be a multiple of 256 KB for Google Drive)
        const UPLOAD_CHUNK_SIZE = 52428800; // 50 MB

        const TOKEN_URL = 'https://www.googleapis.com/oauth2/v4/token';

        private $clientId;
        private $clientSecret;
        // For Google Drive we store the token string (after refresh)
        public $token = null;

        public $accessToken;

        // We expect $accessToken to be an array that contains a refresh_token at minimum.
        public function __construct($accessToken)
        {
            $this->clientId     = XAGIO_GOOGLEDRIVE_KEY;
            $this->clientSecret = XAGIO_GOOGLEDRIVE_SECRET;

            $this->accessToken = $accessToken;
        }

        // ##################################################
        // Authorization

        // Refresh the access token using the stored refresh token.
        // (This method may also be enhanced to update your stored settings.)
        private function RefreshAccessToken()
        {
            if ($this->token != null) {
                return $this->token;
            }

            $xagio_url    = self::TOKEN_URL;
            $fields = http_build_query(
                [
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $this->accessToken['refresh_token'],
                    'grant_type'    => 'refresh_token',
                ]
            );

            $headers = array(
                'Content-Length' => strlen($fields),
                'Content-Type'   => 'application/x-www-form-urlencoded',
            );

            $xagio_args = array(
                'method'    => 'POST',
                'headers'   => $headers,
                'body'      => $fields,
                'timeout'   => 400,
                'sslverify' => false,
            );

            $xagio_response = wp_remote_post($xagio_url, $xagio_args);

            if (is_wp_error($xagio_response)) {
                throw new XAGIO_GoogleDriveException('wp_remote_post() failed: ' . esc_html($xagio_response->get_error_message()));
            }

            $body    = wp_remote_retrieve_body($xagio_response);
            $decoded = json_decode($body, true);
            if ($decoded === null) {
                throw new XAGIO_GoogleDriveException('json_decode() failed');
            }

            // Update the access token used by this instance.
            $this->token = $decoded['access_token'];
            return $this->token;
        }

        // #################################################
        // Communication

        private function apiCall($path, $method = 'GET', $fields = null)
        {
            $xagio_url = 'https://www.googleapis.com/drive/v2/' . $path;

            // Always refresh token first.
            $this->RefreshAccessToken();

            $headers = array(
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type'  => 'application/json',
            );

            $xagio_args = array(
                'method'    => $method,
                'headers'   => $headers,
                'sslverify' => false,
            );

            if ($method === 'POST' && $fields !== null) {
                $xagio_args['body'] = wp_json_encode($fields);
            }

            $xagio_response = wp_remote_request($xagio_url, $xagio_args);
            if (is_wp_error($xagio_response)) {
                return array('error' => $xagio_response->get_error_message());
            }

            $body = wp_remote_retrieve_body($xagio_response);
            return json_decode($body, true);
        }

        // Initiate a resumable session. The $fields array should include at least the file name,
        // mimeType, and parents. The source file is used to set the X-Upload-Content-Length header.
        private function initiateResumableSession($fields, $source_file)
        {
            $initUrl = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable';
            $this->RefreshAccessToken();

            $headers = array(
                'Authorization'           => 'Bearer ' . $this->token,
                'Content-Type'            => 'application/json; charset=UTF-8',
                'X-Upload-Content-Type'   => $fields['mimeType'],
                'X-Upload-Content-Length' => filesize($source_file),
            );

            $xagio_args = array(
                'method'    => 'POST',
                'headers'   => $headers,
                'body'      => wp_json_encode($fields),
                'timeout'   => 400,
                'sslverify' => false,
            );

            $xagio_response = wp_remote_post($initUrl, $xagio_args);
            if (is_wp_error($xagio_response)) {
                return false;
            }

            $headersRaw = wp_remote_retrieve_headers($xagio_response);
            $httpCode   = wp_remote_retrieve_response_code($xagio_response);
            // If the session was created successfully, the Location header contains the session URI.
            if ($httpCode == 200 || $httpCode == 201) {
                if (isset($headersRaw['location'])) {
                    return $headersRaw['location'];
                }
            }
            return false;
        }

        private function downloadCall($id = 0, $xagio_file = '')
        {
            $xagio_url = 'https://www.googleapis.com/drive/v2/files/' . $id . '?alt=media';
            $this->RefreshAccessToken();

            $headers = array(
                'Authorization' => 'Bearer ' . $this->token,
            );

            $xagio_args = array(
                'headers'   => $headers,
                'sslverify' => false,
                'timeout'   => 600,
                // Increase timeout to handle large files
            );

            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                WP_Filesystem();
            }

            $xagio_response = wp_remote_get($xagio_url, $xagio_args);
            if (is_wp_error($xagio_response)) {
                return array('error' => $xagio_response->get_error_message());
            }

            $body = wp_remote_retrieve_body($xagio_response);
            if (!$wp_filesystem->put_contents($xagio_file, $body, FS_CHMOD_FILE)) {
                return array('error' => 'Failed to write file to disk');
            }

            return json_decode($body, true);
        }

        // #################################################
        // Folder and file API methods

        public function Delete($id = 0)
        {
            return $this->apiCall('files/' . $id, 'DELETE');
        }

        public function ListFolders()
        {
            return $this->apiCall("files?trashed=false&q=mimeType='application/vnd.google-apps.folder'");
        }

        public function ListFiles($query)
        {
            $folder = $this->GetFolder($query);
            return $this->apiCall('files?trashed=false&q="' . $folder . '"+in+parents+and+mimeType+!=+"application/vnd.google-apps.folder"');
        }

        public function FindFiles($query)
        {
            return $this->apiCall("files?trashed=false&q=title+contains+'" . $query . "'+and+mimeType+!=+'application/vnd.google-apps.folder'");
        }

        private function GetFolder($xagio_name)
        {
            $folders = $this->ListFolders();
            if (isset($folders['items']) && is_array($folders['items'])) {
                foreach ($folders['items'] as $folder) {
                    if ($folder['title'] === $xagio_name) {
                        return $folder['id'];
                    }
                }
            }
            return false;
        }

        public function CreateFolder($xagio_name, $xagio_parent = NULL)
        {
            if ($this->GetFolder($xagio_name) === false) {
                $data = [
                    'title'    => $xagio_name,
                    'mimeType' => 'application/vnd.google-apps.folder',
                ];
                if ($xagio_parent !== NULL) {
                    $xagio_parent = $this->GetFolder($xagio_parent);
                    if ($xagio_parent !== false) {
                        $data['parents'] = [['id' => $xagio_parent]];
                    }
                }
                return $this->apiCall('files', 'POST', $data);
            } else {
                return false;
            }
        }

        // #################################################
        // New chunked upload via WP Cron

        /**
         * Starts an upload by:
         * - Checking that the file exists.
         * - Initiating a resumable upload session with Google Drive.
         * - Storing the upload details and scheduling a WP Cron event to process the file upload in chunks.
         *
         * @param string $source_file Full path to the local file.
         * @param string $folder The name of the destination folder.
         * @param int $createID (Optional) A job or backup ID for logging purposes.
         *
         * @return true|array True on success or an array with an error key.
         */
        public function upload($source_file, $folder, $createID = 0)
        {
            if (!file_exists($source_file)) {
                return ['error' => 'Source file does not exist.'];
            }

            $filename  = basename($source_file);
            $file_size = filesize($source_file);

            $folder_id = $this->GetFolder($folder);
            if (!$folder_id) {
                return ['error' => 'Folder not found.'];
            }

            // Set MIME type (adjust as needed)
            $mimeType = 'application/zip';

            $fields = [
                'name'     => $filename,
                'mimeType' => $mimeType,
                'parents'  => [$folder_id],
            ];

            // Initiate a resumable upload session.
            $sessionUri = $this->initiateResumableSession($fields, $source_file);
            if (!$sessionUri) {
                return ['error' => 'Failed to initiate upload session.'];
            }

            // Prepare the upload data to pass to the WP Cron event.
            // The array contains: source file, session URI, offset (0), file size, createID, and MIME type.
            $upload_data = [
                $source_file,
                $sessionUri,
                0,
                $file_size,
                $createID
            ];

            // Schedule a WP Cron event (hook name 'XAGIO_GoogleDrive_Process_Upload').
            if (!wp_next_scheduled('XAGIO_GoogleDrive_Process_Upload', $upload_data)) {
                wp_schedule_single_event(time() + 10, 'XAGIO_GoogleDrive_Process_Upload', $upload_data);
            }

            return true;
        }

        /**
         * Static method called via WP Cron to process an upload chunk.
         * It:
         * - Reads one chunk from the source file.
         * - Uploads it to the resumable session URI.
         * - Updates the offset.
         * - Reschedules itself until the entire file is uploaded.
         *
         * @param string $source_file The local file to upload.
         * @param string $sessionUri The resumable session URI.
         * @param int $offset The current offset in the file.
         * @param int $file_size The total file size.
         * @param int $createID A job or backup ID for logging purposes.
         */
        public static function processUploadQueue($source_file, $sessionUri, $offset, $file_size, $createID)
        {
            $chunk_size = self::UPLOAD_CHUNK_SIZE;
            $mimeType   = 'application/zip';

            // Ensure the file still exists.
            if (!file_exists($source_file)) {
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Local backup file does not exist, cannot upload.');
                return;
            }

            // Open the file and seek to the current offset.
            $handle = xagio_fopen($source_file, 'rb');
            if (!$handle || xagio_fseek($handle, $offset) !== 0) {
                if ($handle) {
                    xagio_fclose($handle);
                }
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Cannot open local backup file, cannot upload.');
                return;
            }

            // Read the next chunk.
            $chunk = xagio_fread($handle, $chunk_size);
            xagio_fclose($handle);

            if ($chunk === false || strlen($chunk) === 0) {
                wp_delete_file($source_file);
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Failed to read a chunk of the backup file, cannot upload.');
                return;
            }

            $chunk_length = strlen($chunk);
            $fragment_end = $offset + $chunk_length - 1;

            // Retrieve the stored Google Drive token from your settings.
            $xagio_tokens                        = get_option("XAGIO_BACKUP_SETTINGS");
            $backup_GoogleDriveAccessToken = isset($xagio_tokens["googledrive"]) ? $xagio_tokens["googledrive"] : "";

            // Create a new client instance and refresh the token.
            $client = new self($backup_GoogleDriveAccessToken);
            try {
                $client->RefreshAccessToken();
            } catch (Exception $e) {
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Error refreshing token: ' . $e->getMessage());
                return;
            }

            // Build the headers for the chunk upload.
            $headers = [
                "Content-Length" => $chunk_length,
                "Content-Range"  => "bytes {$offset}-{$fragment_end}/{$file_size}",
                "Content-Type"   => $mimeType,
                "Authorization"  => "Bearer " . $client->token,
            ];

            $xagio_args = [
                'method'    => 'PUT',
                'headers'   => $headers,
                'body'      => $chunk,
                'sslverify' => false,
                'timeout'   => 400,
            ];

            $xagio_response = wp_remote_request($sessionUri, $xagio_args);

            if (is_wp_error($xagio_response)) {
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Error during upload: ' . $xagio_response->get_error_message());
                return;
            }

            $httpCode     = wp_remote_retrieve_response_code($xagio_response);
            $bodyResponse = wp_remote_retrieve_body($xagio_response);
            $responseData = json_decode($bodyResponse, true);

            // For resumable uploads the HTTP code 308 ("Resume Incomplete") is expected,
            // as are 200/201 when the upload is complete.
            if ($httpCode != 308 && ($httpCode < 200 || $httpCode > 299)) {
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Upload failed: ' . $bodyResponse);
                return;
            }

            // Update the offset.
            $offset += $chunk_length;

            // Prepare new upload data.
            $upload_data = [
                $source_file,
                $sessionUri,
                $offset,
                $file_size,
                $createID
            ];

            if ($offset < $file_size) {
                // Schedule the next chunk upload.
                wp_schedule_single_event(time() + 10, 'XAGIO_GoogleDrive_Process_Upload', $upload_data);
            } else {
                // Remove the local backup file.
                wp_delete_file($source_file);
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'success', 'Backup successfully created.');
            }
        }

        // Existing Download method remains unchanged.
        public function Download($remote_file, $source_file)
        {
            return $this->downloadCall($remote_file, $source_file);
        }
    }

    class XAGIO_GoogleDriveException extends Exception
    {

        public function __construct($err = NULL, $isDebug = FALSE)
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

