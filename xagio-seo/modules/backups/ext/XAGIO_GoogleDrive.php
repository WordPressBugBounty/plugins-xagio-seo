<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('XAGIO_GoogleDrive')) {

    class XAGIO_GoogleDrive
    {

        const TOKEN_URL = 'https://www.googleapis.com/oauth2/v4/token';

        private $clientId;

        private $clientSecret;

        private $accessToken;

        function __construct ($token)
        {
            $this->clientId     = XAGIO_GoogleDrive_KEY;
            $this->clientSecret = XAGIO_GoogleDrive_SECRET;

            $this->accessToken = $token;

        }

        // ##################################################
        // Authorization

        private $token = null;

        private function RefreshAccessToken() {
            $url = self::TOKEN_URL;
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

            $args = array(
                'method' => 'POST',
                'headers' => $headers,
                'body' => $fields,
                'timeout' => 400,
                'sslverify' => false
            );

            $response = wp_remote_post($url, $args);

            if (is_wp_error($response)) {
                throw new XAGIO_GoogleDriveException('wp_remote_post() failed: ' . esc_html($response->get_error_message()));
            }

            $body = wp_remote_retrieve_body($response);

            $decoded = json_decode($body, true);

            if ($decoded === null) {
                throw new XAGIO_GoogleDriveException('json_decode() failed');
            }

            $this->token = $decoded['access_token'];
            return $decoded['access_token'];
        }

        // #################################################
        // Communication

        private function apiCall($path, $method = 'GET', $fields = null) {
            $url = 'https://www.googleapis.com/drive/v2/' . $path;

            // Get a new Access Token
            $token = $this->RefreshAccessToken();

            $headers = array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            );

            $args = array(
                'method' => $method,
                'headers' => $headers,
                'sslverify' => false
            );

            if ($method === 'POST' && $fields !== null) {
                $args['body'] = wp_json_encode($fields);
            }

            $response = wp_remote_request($url, $args);

            if (is_wp_error($response)) {
                return array('error' => $response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);

            return json_decode($body, true);
        }

        private function initiateResumableSession($fields, $source_file) {
            $initUrl = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable';
            $token = $this->RefreshAccessToken();

            $headers = array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json; charset=UTF-8',
                'X-Upload-Content-Type' => $fields['mimeType'],
                'X-Upload-Content-Length' => filesize($source_file),
            );

            $args = array(
                'method' => 'POST',
                'headers' => $headers,
                'body' => wp_json_encode($fields),
                'timeout' => 400,
                'sslverify' => false
            );

            $response = wp_remote_post($initUrl, $args);

            if (is_wp_error($response)) {
                return false;
            }

            $headersRaw = wp_remote_retrieve_headers($response);
            $httpCode = wp_remote_retrieve_response_code($response);

            // Extract the resumable session URI from Location header
            if ($httpCode == 200 && isset($headersRaw['location'])) {
                $sessionUri = $headersRaw['location'];
            } else {
                return false;
            }

            return $sessionUri;
        }

        private function uploadCall($fields, $source_file) {

            // Start by initiating a resumable session
            $sessionUri = $this->initiateResumableSession($fields, $source_file);

            if (!$sessionUri) {
                return ['error' => 'Failed to initiate resumable session!'];
            }

            // Initialize WP_Filesystem
            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                WP_Filesystem();
            }

            if (!$wp_filesystem->exists($source_file)) {
                return ['error' => 'Source file does not exist!'];
            }

            // Upload File in Chunks
            $chunkSize = 10 * (256 * 1024); // 2.5 MB per chunk
            $fileSize = $wp_filesystem->size($source_file);
            $handle = $wp_filesystem->get_contents($source_file);  // Changed to get_contents

            if (!$handle) {
                return ['error' => 'Failed to read source file!'];
            }

            $headers = array(
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => $fields['mimeType']
            );

            for ($pos = 0; $pos < $fileSize; $pos += $chunkSize) {
                $chunkData = substr($handle, $pos, $chunkSize);
                $chunkEnd = $pos + strlen($chunkData) - 1;

                $args = array(
                    'method' => 'PUT',
                    'headers' => array_merge($headers, array(
                        "Content-Length" => strlen($chunkData),
                        "Content-Range" => "bytes $pos-$chunkEnd/$fileSize"
                    )),
                    'body' => $chunkData,
                    'sslverify' => false
                );

                $response = wp_remote_request($sessionUri, $args);
                $httpCode = wp_remote_retrieve_response_code($response);

                if ($httpCode != 308 && ($httpCode < 200 || $httpCode > 299)) {  // 308 means "Resume Incomplete" which is expected
                    return json_decode(wp_remote_retrieve_body($response), true);  // Error handling
                }
            }

            // Finish the Upload
            return json_decode(wp_remote_retrieve_body($response), true);
        }

        private function downloadCall($id = 0, $file = '') {
            $url = 'https://www.googleapis.com/drive/v2/files/' . $id . '?alt=media';

            // Get a new Access Token
            $token = $this->RefreshAccessToken();

            $headers = array(
                'Authorization' => 'Bearer ' . $token,
            );

            $args = array(
                'headers' => $headers,
                'sslverify' => false,
                'timeout' => 600,  // Increase timeout to handle large files
            );

            // Initialize WP_Filesystem
            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                WP_Filesystem();
            }

            $response = wp_remote_get($url, $args);

            if (is_wp_error($response)) {
                return array('error' => $response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);

            if (!$wp_filesystem->put_contents($file, $body, FS_CHMOD_FILE)) {
                return array('error' => 'Failed to write file to disk');
            }

            return json_decode($body, true);
        }

        // #################################################
        // APIs

        public function Delete ($id = 0)
        {
            return $this->apiCall('files/' . $id, 'DELETE');
        }

        public function ListFolders ()
        {
            return $this->apiCall('files?trashed=false&q=mimeType=\'application/vnd.google-apps.folder\'');
        }

        public function ListFiles ($query)
        {
            $folder = $this->GetFolder($query);
            return $this->apiCall('files?trashed=false&q="' . $folder . '"+in+parents+and+mimeType+!=+"application/vnd.google-apps.folder"');
        }

        public function FindFiles ($query)
        {
            return $this->apiCall('files?trashed=false&q=title+contains+\'' . $query . '\'+and+mimeType+!=+\'application/vnd.google-apps.folder\'');
        }

        private function GetFolder ($name)
        {
            $folders = $this->ListFolders();
            foreach ($folders['items'] as $folder) {
                if ($folder['title'] === $name) {
                    return $folder['id'];
                }
            }
            return FALSE;
        }

        public function CreateFolder ($name, $parent = NULL)
        {
            if ($this->GetFolder($name) === FALSE) {
                $data = [
                    'title'    => $name,
                    'mimeType' => 'application/vnd.google-apps.folder',
                ];
                if ($parent !== NULL) {
                    $parent = $this->GetFolder($parent);
                    if ($parent !== FALSE) {
                        $data['parents'] = [['id' => $parent]];
                    }
                }
                return $this->apiCall('files', 'POST', $data);
            } else {
                return FALSE;
            }
        }

        public function Upload ($source_file, $folder)
        {
            $folder_id = $this->GetFolder($folder);
            if ($folder_id !== FALSE) {
                $data = [
                    'name'     => basename($source_file),
                    'mimeType' => 'application/zip',
                    'parents'  => [$folder_id],
                ];
                return $this->uploadCall($data, $source_file);
            } else {
                return FALSE;
            }
        }

        public function Download ($remote_file, $source_file)
        {
            return $this->downloadCall($remote_file, $source_file);
        }

    }

    class XAGIO_GoogleDriveException extends Exception
    {

        public function __construct ($err = NULL, $isDebug = FALSE)
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

        public static function display_error ($err)
        {
            wp_die(wp_kses_post($err));
        }
    }

}