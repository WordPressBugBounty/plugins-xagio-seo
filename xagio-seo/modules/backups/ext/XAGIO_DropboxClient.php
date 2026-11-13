<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_DropboxClient')) {

    class XAGIO_DropboxClient
    {
        const UPLOAD_CHUNK_SIZE = 52428800;   // 50 MB

        private $accessToken;

        /** Why the last token refresh failed (readable, never a token), or null. */
        private $refreshError = null;

        /** TRUE when Dropbox itself refused the refresh (revoked, malformed): trying again cannot help. */
        private $refreshFinal = false;

        function __construct($token)
        {
            $this->accessToken = $token;
            $this->refresh();
        }

        /**
         * Swap the stored refresh token for a fresh 4-hour access token. A failure used to be
         * silent: the client then went on with the access token stored at connect time, which
         * Dropbox had expired long ago, and the upload failed as "Info: Array" (T-138). The reason
         * is now kept (lastRefreshError) and a network failure is tried once more.
         */
        public function refresh()
        {
            if (!is_array($this->accessToken) || empty($this->accessToken['refresh_token'])) {
                $this->refreshError = 'the stored Dropbox connection has no refresh token; reconnect Dropbox in Xagio';
                $this->refreshFinal = true;
                return false;
            }
            if (!XAGIO_DROPBOX_KEY || !XAGIO_DROPBOX_SECRET) {
                $this->refreshError = 'the Dropbox app keys are missing; synchronize the website with Xagio';
                $this->refreshFinal = true;
                return false;
            }

            $at = null;
            for ($attempt = 0; $attempt < 2; $attempt++) {
                $at = $this->authCall("oauth2/token", array(
                    'refresh_token' => $this->accessToken['refresh_token'],
                    'grant_type'    => 'refresh_token',
                    'client_id'     => XAGIO_DROPBOX_KEY,
                    'client_secret' => XAGIO_DROPBOX_SECRET
                ));
                if (isset($at['access_token'])) {
                    $this->accessToken['access_token'] = $at['access_token'];
                    $this->refreshError = null;
                    $this->refreshFinal = false;
                    return true;
                }
                // Only a transport failure (no HTTP answer) is worth a second try; invalid_grant is final.
                if (isset($at['_http']) && (int) $at['_http'] > 0 && (int) $at['_http'] < 500) {
                    $this->refreshFinal = true;
                    break;
                }
                if ($attempt === 0) {
                    sleep(3);
                }
            }
            $this->refreshError = 'token refresh failed: ' . self::describe($at);
            return false;
        }

        /** Why the last refresh failed, or null when the client holds a fresh token. */
        public function lastRefreshError()
        {
            return $this->refreshError;
        }

        /**
         * One line for a Dropbox answer, for the stored backup message: the HTTP status and
         * Dropbox's error_summary (e.g. "HTTP 401 expired_access_token/"), else the error as JSON.
         * Dropbox error answers carry no token, and nothing from the request is added here.
         */
        public static function describe($xagio_response)
        {
            if (!is_array($xagio_response)) {
                return 'no readable answer from Dropbox';
            }
            $parts = array();
            if (!empty($xagio_response['_http'])) {
                $parts[] = 'HTTP ' . (int) $xagio_response['_http'];
            }
            if (isset($xagio_response['error_summary']) && is_string($xagio_response['error_summary'])) {
                $parts[] = $xagio_response['error_summary'];
            } elseif (isset($xagio_response['error_description']) && is_string($xagio_response['error_description'])) {
                $parts[] = (is_string($xagio_response['error'] ?? null) ? $xagio_response['error'] . ': ' : '') . $xagio_response['error_description'];
            } elseif (isset($xagio_response['error'])) {
                $parts[] = is_string($xagio_response['error']) ? $xagio_response['error'] : (string) wp_json_encode($xagio_response['error']);
            }
            $text = trim(implode(' ', $parts));
            if ($text === '') {
                $text = 'unknown Dropbox error';
            }
            return function_exists('mb_substr') ? mb_substr($text, 0, 300) : substr($text, 0, 300);
        }

        /** Is this answer an access token Dropbox no longer accepts? */
        private static function isTokenError($xagio_response)
        {
            $summary = is_array($xagio_response) && isset($xagio_response['error_summary']) ? (string) $xagio_response['error_summary'] : '';
            return strpos($summary, 'expired_access_token') === 0 || strpos($summary, 'invalid_access_token') === 0
                || (is_array($xagio_response) && (int) ($xagio_response['_http'] ?? 0) === 401);
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

            // One more try for the two failures that are not final: a token Dropbox refuses
            // (refresh again, it may have failed for a moment) and "too many writes" (wait).
            if (isset($session['error'])) {
                $retry = false;
                if (self::isTokenError($session) && !$this->refreshFinal && $this->refresh()) {
                    $retry = true;
                } elseif ((int) ($session['_http'] ?? 0) === 429) {
                    $wait = (int) ($session['error']['retry_after'] ?? 5);
                    sleep(max(1, min(30, $wait)));
                    $retry = true;
                }
                if ($retry) {
                    $session = $this->uploadCall('files/upload_session/start', array(
                        'close' => false
                    ), $chunk);
                }
            }

            if (isset($session['error'])) {
                $session['error_text'] = self::describe($session)
                    . ($this->refreshError !== null ? ' (' . $this->refreshError . ')' : '');
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

            // Ensure the file still exists. If it is gone, remove every event for it so this
            // does not fire (and report) again on every page load.
            if (!file_exists($source_file)) {
                XAGIO_MODEL_BACKUPS::purgeDeadUploadEvents($source_file);
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Local backup file does not exist, cannot upload. Upload cancelled.');
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
                    XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Error finishing upload: ' . self::describe($xagio_response) . self::refreshNote($client));
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

                // Dropbox append is idempotent for the same cursor offset, so one retry is safe.
                if (isset($xagio_response['error'])) {
                    sleep(5);
                    $xagio_response = $client->uploadCall('files/upload_session/append_v2', array(
                        'cursor' => array(
                            'session_id' => $session_id,
                            'offset'     => $offset
                        ),
                        'close'  => false
                    ), $chunk);
                }

                if (isset($xagio_response['error'])) {
                    XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Error during upload (at offset ' . $offset . ' of ' . $file_size . '): ' . self::describe($xagio_response) . self::refreshNote($client));
                    return;
                }

                // Schedule the next chunk upload.
                $upload_data = array(
                    $source_file,
                    $session_id,
                    $new_offset,
                    $file_size,
                    $createID,
                    $remote_file
                );
                XAGIO_MODEL_BACKUPS::continueUpload('XAGIO_Dropbox_Process_Upload', $upload_data);
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
                    XAGIO_MODEL_BACKUPS::handleOutput($createID, 'error', 'Error finishing upload: ' . self::describe($xagio_response) . self::refreshNote($client));
                    return;
                }
                // Remove the local backup file once the upload is complete.
                wp_delete_file($source_file);
                XAGIO_MODEL_BACKUPS::handleOutput($createID, 'success', 'Backup successfully created.');
            }
        }


        /** " (token refresh failed: …)" when the client could not refresh, else ''. */
        private static function refreshNote($client)
        {
            $why = $client->lastRefreshError();
            return $why !== null ? ' (' . $why . ')' : '';
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

            return self::decodeAnswer($xagio_response);
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

            return self::decodeAnswer($xagio_response);
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

            return self::decodeAnswer($xagio_response);
        }

        /**
         * Dropbox's JSON answer with its HTTP status added as '_http'. A non-JSON error page
         * (proxy, 5xx) becomes ['error' => 'HTTP n: <start of the page>'] instead of null, so the
         * caller sees a failure with a reason. Callers read the same keys as before.
         */
        private static function decodeAnswer($xagio_response)
        {
            $code = (int) wp_remote_retrieve_response_code($xagio_response);
            $body = wp_remote_retrieve_body($xagio_response);
            $data = json_decode($body, true);
            if (is_array($data)) {
                $data['_http'] = $code;
                return $data;
            }
            if ($code !== 200) {
                $text = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $body)));
                return array('error' => $text !== '' ? substr($text, 0, 200) : 'no body', '_http' => $code);
            }
            return $data;
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
