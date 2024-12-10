<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('XAGIO_DropboxClient')) {

    class XAGIO_DropboxClient
    {

        const MAX_UPLOAD_CHUNK_SIZE = 150000000; // 150MB

        const UPLOAD_CHUNK_SIZE = 4000000; // 4MB

        private $accessToken;

        function __construct($token)
        {
            $this->accessToken = $token;

            // get refresh token
            if (isset($this->accessToken['refresh_token'])) {
                $refreshToken = $this->accessToken['refresh_token'];

                $at = $this->authCall("oauth2/token", array(
                    'refresh_token' => $refreshToken,
                    'grant_type'    => 'refresh_token',
                    'client_id'     => XAGIO_DROPBOX_KEY,
                    'client_secret' => XAGIO_DROPBOX_SECRET
                ));

                $this->accessToken['access_token'] = $at['access_token'];
            }
        }

        // ##################################################
        // Authorization


        // ##################################################
        // API Functions

        public function CreateFolder($name = '')
        {
            $folder = $this->GetFileFolder('/' . $name);
            if (isset($folder['error'])) {
                return $this->apiCall('files/create_folder', array(
                    'path'       => '/' . $name,
                    'autorename' => true
                ));
            } else {
                return false;
            }
        }

        public function ListFolder($name = '')
        {
            $folder = $this->GetFileFolder('/' . $name);
            if (!isset($folder['error'])) {
                return $this->apiCall('files/list_folder', array(
                    'path'                                => '/' . $name,
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

        public function Upload($source_file = '', $remote_file = '')
        {
            global $wp_filesystem;

            // Initialize WP_Filesystem
            if (!function_exists('WP_Filesystem')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }

            WP_Filesystem();

            // Get the filename
            $filename = basename($source_file);

            // Check if we're uploading to folder or a file
            $remoteLocation = $this->GetFileFolder($remote_file);
            if ($remoteLocation['.tag'] == 'folder') {
                $remote_file = $remoteLocation['path_lower'] . '/' . $filename;
            }

            // Determine if it needs to be chunked down
            $file_size = $wp_filesystem->size($source_file);
            if ($file_size > XAGIO_DropboxClient::MAX_UPLOAD_CHUNK_SIZE) {

                // Chunk it down
                $file_pointer = $wp_filesystem->get_contents($source_file, false, 'rb');
                $file_session = null;
                $file_offset  = 0;
                while ($file_pointer !== false && $file_offset < $file_size) {
                    $chunk = substr($file_pointer, $file_offset, self::UPLOAD_CHUNK_SIZE);
                    if ($file_session === null) {
                        $session = $this->uploadCall('files/upload_session/start', array(
                            'close' => false
                        ), $chunk);
                        $file_session = $session['session_id'];
                    } else {
                        $this->uploadCall('files/upload_session/append_v2', array(
                            'cursor' => array(
                                'session_id' => $file_session,
                                'offset'     => $file_offset
                            ),
                            'close'  => false
                        ), $chunk);
                    }
                    $file_offset += strlen($chunk);
                }
                // Finish the chunk upload session
                return $this->uploadCall('files/upload_session/finish', array(
                    'cursor' => array(
                        'session_id' => $file_session,
                        'offset'     => $file_offset
                    ),
                    'commit' => array(
                        'path'       => $remote_file,
                        'mode'       => 'add',
                        'autorename' => true,
                        'mute'       => false
                    )
                ), '');

            } else {
                // Regular upload
                $file_contents = $wp_filesystem->get_contents($source_file);
                return $this->uploadCall('files/upload', array(
                    'path'       => $remote_file,
                    'mode'       => 'add',
                    'autorename' => true,
                    'mute'       => false
                ), $file_contents);
            }
        }


        public function Download($remote_file = '', $source_file = '')
        {
            return $this->downloadCall('files/download', array(
                'path' => $remote_file
            ), $source_file);
        }

        // ##################################################
        // Helper Functions

        private function downloadCall($path, $fields = null, $file = '')
        {
            global $wp_filesystem;

            // Initialize WP_Filesystem
            if (!function_exists('WP_Filesystem')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }

            WP_Filesystem();

            $url = $this->cleanUrl('https://content.dropboxapi.com/2/' . $path);

            $response = wp_remote_post($url, array(
                'method'    => 'POST',
                'headers'   => array(
                    'Authorization'   => 'Bearer ' . $this->accessToken['access_token'],
                    'Dropbox-API-Arg' => wp_json_encode($fields),
                ),
                'timeout'   => 45,
                'sslverify' => false,
            ));

            if (is_wp_error($response)) {
                return array('error' => $response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);

            if (!$wp_filesystem->put_contents($file, $body, FS_CHMOD_FILE)) {
                return array('error' => 'Unable to write to file');
            }

            $http_code = wp_remote_retrieve_response_code($response);
            if ($http_code != 200) {
                return array('error' => 'HTTP Error: ' . $http_code);
            }

            return json_decode($body, true);
        }


        private function uploadCall($path, $fields = null, $file = '')
        {
            $url = $this->cleanUrl('https://content.dropboxapi.com/2/' . $path);

            $response = wp_remote_request($url, array(
                'method'    => 'POST',
                'headers'   => array(
                    'Authorization'    => 'Bearer ' . $this->accessToken['access_token'],
                    'Dropbox-API-Arg'  => wp_json_encode($fields),
                    'Content-Type'     => 'application/octet-stream',
                ),
                'body'      => $file,
                'timeout'   => 45,
                'sslverify' => false, // Set to true in production for better security
            ));

            if (is_wp_error($response)) {
                // Handle the error appropriately
                return array('error' => $response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);
            return json_decode($body, true);
        }

        private function apiCall($path, $fields = null)
        {
            $url = $this->cleanUrl('https://api.dropboxapi.com/2/' . $path);

            $response = wp_remote_post($url, array(
                'method'    => 'POST',
                'headers'   => array(
                    'Authorization' => 'Bearer ' . $this->accessToken['access_token'],
                    'Content-Type'  => 'application/json',
                ),
                'body'      => wp_json_encode($fields),
                'timeout'   => 45,
                'sslverify' => false, // Set to true in production for better security
            ));

            if (is_wp_error($response)) {
                // Handle the error appropriately
                return array('error' => $response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);
            return json_decode($body, true);
        }

        private function authCall($path, $request_data = null)
        {
            $url = $this->cleanUrl('https://api.dropboxapi.com/' . $path);

            $response = wp_remote_post($url, array(
                'headers' => array(
                    'User-Agent' => 'PSv3 (SSL Connection)',
                    'Accept-Encoding' => 'gzip, deflate',
                ),
                'body'      => $request_data,
                'timeout'   => 400,
                'sslverify' => false, // Set to true in production for better security
            ));

            if (is_wp_error($response)) {
                // Handle the error appropriately
                return array('error' => $response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);
            return json_decode($body, true);
        }

        private function cleanUrl($url)
        {
            $p   = substr($url, 0, 8);
            $url = str_replace('//', '/', str_replace('\\', '/', substr($url, 8)));
            $url = rawurlencode($url);
            $url = str_replace('%2F', '/', $url);

            return $p . $url;
        }
    }

}