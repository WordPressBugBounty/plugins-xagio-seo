<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_DropboxClient')) {

    class XAGIO_DropboxClient
    {
        const UPLOAD_CHUNK_SIZE = 52428800;   // 50 MB

        private $accessToken;

        function __construct($token)
        {
            $this->accessToken = $token;

            // Refresh access token if a refresh token exists.
            if (isset($this->accessToken['refresh_token'])) {
                $refreshToken = $this->accessToken['refresh_token'];

                $at = $this->authCall("oauth2/token", array(
                    'refresh_token' => $refreshToken,
                    'grant_type'    => 'refresh_token',
                    'client_id'     => XAGIO_DROPBOX_KEY,
                    'client_secret' => XAGIO_DROPBOX_SECRET
                ));

                if (isset($at['access_token'])) {
                    $this->accessToken['access_token'] = $at['access_token'];
                }
            }
        }

        // ##################################################
        // Authorization and API functions (unchanged)
        public function CreateFolder($xagio_name = '')
        {
            $folder = $this->GetFileFolder('/' . $xagio_name);
            if (isset($folder['error'])) {
                return $this->apiCall('files/create_folder', array(
                    'path'       => '/' . $xagio_name,
                    'autorename' => true
                ));
            } else {
                return false;
            }
        }

        public function ListFolder($xagio_name = '')
        {
            $folder = $this->GetFileFolder('/' . $xagio_name);
            if (!isset($folder['error'])) {
                return $this->apiCall('files/list_folder', array(
                    'path'                                => '/' . $xagio_name,
                    'recursive'                           => false,
                    'include_media_info'                  => false,
                    'include_deleted'                     => false,
                    'include_has_explicit_shared_members' => false,
                ));
            } else {
                return false;
            }
        }

        public function GetFileFolder($path = '')
        {
            return $this->apiCall('files/get_metadata', array(
                'path'                                => $path,
                'include_media_info'                  => false,
                'include_deleted'                     => false,
                'include_has_explicit_shared_members' => false,
            ));
        }

        public function GetLink($path = '')
        {
            return $this->apiCall('files/get_temporary_link', array(
                'path' => $path
            ));
        }

        public function Delete($path = '')
        {
            $folder = $this->GetFileFolder($path);
            if (isset($folder['error'])) {
                return false;
            } else {
                return $this->apiCall('files/delete', array(
                    'path' => $path
                ));
            }
        }

        /**
         * Modified Upload() method:
         * - For files larger than MAX_UPLOAD_CHUNK_SIZE (150 MB), we use asynchronous, chunked uploads via WP Cron.
         * - Otherwise, we do a synchronous upload.
         *
         * @param string $source_file Full local file path.
         * @param string $remote_file The Dropbox destination path (or folder).
         * @param int $createID (Optional) Job/backup ID for logging.
         *
         * @return true|array True on success, or an error array.
         */
        public function upload($source_file = '', $remote_file = '', $createID = 0)
        {
            global $wp_filesystem;

            // Initialize WP_Filesystem if not already loaded.
            if (!function_exists('WP_Filesystem')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            WP_Filesystem();

            // Ensure the source file exists.
            if (!file_exists($source_file)) {
                return array('error' => 'Source file does not exist.');
            }

            // Get the filename.
            $filename = basename($source_file);

            // Check if we're uploading to a folder; if so, append the filename.
            $remoteLocation = $this->GetFileFolder($remote_file);
            if (isset($remoteLocation['.tag']) && $remoteLocation['.tag'] == 'folder') {
                $remote_file = rtrim($remoteLocation['path_lower'], '/') . '/' . $filename;
            }

            $file_size = filesize($source_file);

            // Always use asynchronous chunked upload via WP Cron.

            // Open the file and read the first chunk.
            $handle = xagio_fopen($source_file, 'rb');
            if (!$handle) {
                return array('error' => 'Cannot open source file.');
            }
            $chunk = xagio_fread($handle, self::UPLOAD_CHUNK_SIZE);
            xagio_fclose($handle);

            if ($chunk === false || strlen($chunk) === 0) {
                return array('error' => 'Failed to read first chunk.');
            }

            // Initiate an upload session using Dropbox’s "files/upload_session/start" endpoint.
            $session = $this->uploadCall('files/upload_session/start', array(
                'close' => false
            ), $chunk);

            if (isset($session['error'])) {
                return $session;
            }

            if (!isset($session['session_id'])) {
                return array('error' => 'Failed to initiate upload session.');
            }

            $session_id = $session['session_id'];
            // Set the initial offset to the length of the first chunk.
            $offset = strlen($chunk);

            // Prepare upload data for the WP Cron event.
            // Data: source file, session ID, current offset, total file size, createID, remote file path.
            $upload_data = array(
                $source_file,
                $session_id,
                $offset,
                $file_size,
                $createID,
                $remote_file
            );

            // Schedule a WP Cron event to process subsequent chunks.
            if (!wp_next_scheduled('XAGIO_Dropbox_Process_Upload', $upload_data)) {
                wp_schedule_single_event(time() + 10, 'XAGIO_Dropbox_Process_Upload', $upload_data);
            }

            return true;
        }

        /**
         * Static method called via WP Cron to process a chunk of a Dropbox upload session.
         *
         * @param string $source_file The local file path.
         * @param string $session_id The Dropbox upload session ID.
         * @param int $offset Current file offset.
         * @param int $file_size Total file size.
         * @param int $createID Job/backup ID.
         * @param string $remote_file The destination Dropbox file path.
         */
        public static function processUploadQueue($source_file, $session_id, $offset, $file_size, $createID, $remote_file)
        {
            $chunk_size = self::UPLOAD_CHUNK_SIZE;

            // Ensure the file still exists.
            if (!file_exists($source_file)) {
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Local backup file does not exist, cannot upload.');
                return;
            }

            // If the current offset is greater than or equal to the file size,
            // then there is no more data to read. Finalize the upload session.
            if ($offset >= $file_size) {
                // Create a new Dropbox client instance using stored settings.
                $xagio_tokens                      = get_option("XAGIO_BACKUP_SETTINGS");
                $backup_dropbox_access_token = isset($xagio_tokens["dropbox"]) ? $xagio_tokens["dropbox"] : array();
                $client                      = new self($backup_dropbox_access_token);

                // Call the finish endpoint with an empty chunk.
                $xagio_response = $client->uploadCall('files/upload_session/finish', array(
                    'cursor' => array(
                        'session_id' => $session_id,
                        'offset'     => $offset
                    ),
                    'commit' => array(
                        'path'       => $remote_file,
                        'mode'       => 'add',
                        'autorename' => true,
                        'mute'       => false
                    )
                ), ""); // Send an empty string since there is no data left.

                if (isset($xagio_response['error'])) {
                    XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Error finishing upload: ' . $xagio_response['error']);
                    return;
                }
                // Remove the local backup file and signal success.
                wp_delete_file($source_file);
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'success', 'Backup successfully created.');
                return;
            }

            // Otherwise, open the file and seek to the current offset.
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

            // If no data is read (and we're not at the end), then something went wrong.
            if ($chunk === false || strlen($chunk) === 0) {
                wp_delete_file($source_file);
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Failed to read a chunk of the backup file, cannot upload.');
                return;
            }

            $chunk_length = strlen($chunk);
            $new_offset   = $offset + $chunk_length;

            // Retrieve the stored Dropbox access token from your settings.
            $xagio_tokens                      = get_option("XAGIO_BACKUP_SETTINGS");
            $backup_dropbox_access_token = isset($xagio_tokens["dropbox"]) ? $xagio_tokens["dropbox"] : array();

            // Create a new Dropbox client instance.
            $client = new self($backup_dropbox_access_token);

            // If there's still data remaining, append the chunk.
            if ($new_offset < $file_size) {
                $xagio_response = $client->uploadCall('files/upload_session/append_v2', array(
                    'cursor' => array(
                        'session_id' => $session_id,
                        'offset'     => $offset
                    ),
                    'close'  => false
                ), $chunk);

                if (isset($xagio_response['error'])) {
                    XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Error during upload: ' . json_encode($xagio_response['error']));
                    return;
                }

                // Reschedule the next chunk upload.
                $upload_data = array(
                    $source_file,
                    $session_id,
                    $new_offset,
                    $file_size,
                    $createID,
                    $remote_file
                );
                wp_schedule_single_event(time() + 10, 'XAGIO_Dropbox_Process_Upload', $upload_data);
            } else {
                // Final chunk: finish the upload session.
                $xagio_response = $client->uploadCall('files/upload_session/finish', array(
                    'cursor' => array(
                        'session_id' => $session_id,
                        'offset'     => $offset
                    ),
                    'commit' => array(
                        'path'       => $remote_file,
                        'mode'       => 'add',
                        'autorename' => true,
                        'mute'       => false
                    )
                ), $chunk);

                if (isset($xagio_response['error'])) {
                    XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Error finishing upload: ' . json_encode($xagio_response['error']));
                    return;
                }
                // Remove the local backup file once the upload is complete.
                wp_delete_file($source_file);
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'success', 'Backup successfully created.');
            }
        }


        // ##################################################
        // Helper Functions (downloadCall, uploadCall, apiCall, authCall, cleanUrl)
        private function downloadCall($path, $fields = null, $xagio_file = '')
        {
            global $wp_filesystem;

            if (!function_exists('WP_Filesystem')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            WP_Filesystem();

            $xagio_url = $this->cleanUrl('https://content.dropboxapi.com/2/' . $path);

            $xagio_response = wp_remote_post($xagio_url, array(
                'method'    => 'POST',
                'headers'   => array(
                    'Authorization'   => 'Bearer ' . $this->accessToken['access_token'],
                    'Dropbox-API-Arg' => wp_json_encode($fields),
                ),
                'timeout'   => 400,
                'sslverify' => false,
            ));

            if (is_wp_error($xagio_response)) {
                return array('error' => $xagio_response->get_error_message());
            }

            $body = wp_remote_retrieve_body($xagio_response);

            if (!$wp_filesystem->put_contents($xagio_file, $body, FS_CHMOD_FILE)) {
                return array('error' => 'Unable to write to file');
            }

            $xagio_http_code = wp_remote_retrieve_response_code($xagio_response);
            if ($xagio_http_code != 200) {
                return array('error' => 'HTTP Error: ' . $xagio_http_code);
            }

            return json_decode($body, true);
        }

        private function uploadCall($path, $fields = null, $xagio_file = '')
        {
            $xagio_url = $this->cleanUrl('https://content.dropboxapi.com/2/' . $path);

            $xagio_response = wp_remote_request($xagio_url, array(
                'method'    => 'POST',
                'headers'   => array(
                    'Authorization'   => 'Bearer ' . $this->accessToken['access_token'],
                    'Dropbox-API-Arg' => wp_json_encode($fields),
                    'Content-Type'    => 'application/octet-stream',
                ),
                'body'      => $xagio_file,
                'timeout'   => 400,
                'sslverify' => false,
            ));

            if (is_wp_error($xagio_response)) {
                return array('error' => $xagio_response->get_error_message());
            }

            $body = wp_remote_retrieve_body($xagio_response);
            return json_decode($body, true);
        }

        private function apiCall($path, $fields = null)
        {
            $xagio_url = $this->cleanUrl('https://api.dropboxapi.com/2/' . $path);

            $xagio_response = wp_remote_post($xagio_url, array(
                'method'    => 'POST',
                'headers'   => array(
                    'Authorization' => 'Bearer ' . $this->accessToken['access_token'],
                    'Content-Type'  => 'application/json',
                ),
                'body'      => wp_json_encode($fields),
                'timeout'   => 400,
                'sslverify' => false,
            ));

            if (is_wp_error($xagio_response)) {
                return array('error' => $xagio_response->get_error_message());
            }

            $body = wp_remote_retrieve_body($xagio_response);
            return json_decode($body, true);
        }

        private function authCall($path, $request_data = null)
        {
            $xagio_url = $this->cleanUrl('https://api.dropboxapi.com/' . $path);

            $xagio_response = wp_remote_post($xagio_url, array(
                'headers'   => array(
                    'User-Agent'      => 'PSv3 (SSL Connection)',
                    'Accept-Encoding' => 'gzip, deflate',
                ),
                'body'      => $request_data,
                'timeout'   => 400,
                'sslverify' => false,
            ));

            if (is_wp_error($xagio_response)) {
                return array('error' => $xagio_response->get_error_message());
            }

            $body = wp_remote_retrieve_body($xagio_response);
            return json_decode($body, true);
        }

        private function cleanUrl($xagio_url)
        {
            $xagio_p   = substr($xagio_url, 0, 8);
            $xagio_url = str_replace('//', '/', str_replace('\\', '/', substr($xagio_url, 8)));
            $xagio_url = rawurlencode($xagio_url);
            $xagio_url = str_replace('%2F', '/', $xagio_url);

            return $xagio_p . $xagio_url;
        }
    }
}
?>
