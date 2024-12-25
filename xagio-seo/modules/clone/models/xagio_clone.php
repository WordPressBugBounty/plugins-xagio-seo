<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_CLONE')) {

    class XAGIO_MODEL_CLONE
    {

        public static function initialize()
        {
            if (!XAGIO_HAS_ADMIN_PERMISSIONS)
                return;

            add_action('admin_post_xagio_verify_connection', [
                'XAGIO_MODEL_CLONE',
                'verifyConnection'
            ]);
            add_action('admin_post_xagio_obtain_api_key', [
                'XAGIO_MODEL_CLONE',
                'obtainApiKey'
            ]);
            add_action('admin_post_xagio_create_clone_backup', [
                'XAGIO_MODEL_CLONE',
                'createCloneBackup'
            ]);
            add_action('admin_post_xagio_download_clone_backup', [
                'XAGIO_MODEL_CLONE',
                'downloadCloneBackup'
            ]);
            add_action('admin_post_xagio_remove_clone_backup', [
                'XAGIO_MODEL_CLONE',
                'removeCloneBackup'
            ]);
            add_action('admin_post_xagio_extract_merge_clone', [
                'XAGIO_MODEL_CLONE',
                'extractAndMerge'
            ]);
        }

        public static function extractAndMerge()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['prefix'], $_POST['backup_path'], $_POST['url'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            global $wpdb;

            $errors = [];
            $PREFIX = sanitize_text_field(wp_unslash($_POST['prefix']));

            $BACKUP_PATH = sanitize_text_field(wp_unslash($_POST['backup_path']));
            if (!file_exists($BACKUP_PATH)) {
                xagio_json('error', 'Backup either did not download properly, or there is a problem with downloaded cloned version of files.');
                return;
            }

            $URL = sanitize_url(wp_unslash($_POST['url']));
            if (!filter_var($URL, FILTER_VALIDATE_URL)) {
                xagio_json('error', 'Provided "url" argument is not an actual URL.');
                return;
            }
            $OLD_URL = wp_parse_url($URL);
            $NEW_URL = wp_parse_url(site_url());

            $OLD_DOMAIN = $OLD_URL['host'];
            $NEW_DOMAIN = $NEW_URL['host'];

            // Get the API key
            $api_key = XAGIO_API::getAPIKey();

            // Save the current user
            $current_user_id = get_current_user_id();

            // Fetch user data
            $current_user = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->users} WHERE ID = %d", $current_user_id), ARRAY_A);
            unset($current_user['ID']);

            // Fetch user meta data
            $current_usermeta = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->usermeta} WHERE user_id = %d", $current_user_id), ARRAY_A);

            // Move the files from temporary directory to root
            self::recurseCopy(rtrim($BACKUP_PATH, DIRECTORY_SEPARATOR), rtrim(ABSPATH, DIRECTORY_SEPARATOR));

            $result = XAGIO_MODEL_BACKUPS::restoreMySQL(ABSPATH . 'mysql.zip');

            if ($result !== true) {
                $errors[] = $result;
            }

            XAGIO_MODEL_RESCUE::deleteFolder(rtrim($BACKUP_PATH, DIRECTORY_SEPARATOR));

            /**
             *  Time to do magic fixes :)
             */

            // Restore the User
            $wpdb->insert($wpdb->users, $current_user);

            if (!empty($wpdb->last_error)) {
                $errors[] = [
                    'Insert User Error',
                    $wpdb->last_error
                ];
                $wpdb->last_error = false;
            }

            $current_user_id = $wpdb->insert_id;

            // Insert user meta data
            foreach ($current_usermeta as $usermeta) {
                unset($usermeta['umeta_id']);
                $usermeta['user_id'] = $current_user_id;
                $wpdb->insert($wpdb->usermeta, $usermeta);

                if (!empty($wpdb->last_error)) {
                    $errors[] = [
                        'Insert Usermeta Error',
                        $wpdb->last_error
                    ];
                    $wpdb->last_error = false;
                }
            }

            // Insert API key
            $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}options (option_name, option_value) VALUES (%s, %s) ON DUPLICATE KEY UPDATE option_value = %s;", 'XAGIO_API', $api_key, $api_key));
            if (!empty($wpdb->last_error)) {
                $errors[]         = [
                    $prepared_query,
                    $wpdb->last_error
                ];
                $wpdb->last_error = false;
            }

            // Update wp_options
            $wp_options = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}options WHERE option_value LIKE %s;", '%' . $wpdb->esc_like($OLD_DOMAIN) . '%'), ARRAY_A);
            if ($wp_options) {
                foreach ($wp_options as $option) {
                    $value = self::recursive_unserialize_replace($OLD_DOMAIN, $NEW_DOMAIN, $option['option_value']);
                    if ($value != $option['option_value']) {
                        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}options SET option_value = %s WHERE option_id = %d;", $value, $option['option_id']));
                    }
                    if (!empty($wpdb->last_error)) {
                        $errors[]         = [
                            $prepared_query,
                            $wpdb->last_error
                        ];
                        $wpdb->last_error = false;
                    }
                }
            }

            // Update wp_postmeta
            $wp_postmeta = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_value LIKE %s;", '%' . $wpdb->esc_like($OLD_DOMAIN) . '%'), ARRAY_A);
            if ($wp_postmeta) {
                foreach ($wp_postmeta as $meta) {
                    $value = self::recursive_unserialize_replace($OLD_DOMAIN, $NEW_DOMAIN, $meta['meta_value']);
                    if ($value != $meta['meta_value']) {
                        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}postmeta SET meta_value = %s WHERE meta_id = %d;", $value, $meta['meta_id']));
                    }
                    if (!empty($wpdb->last_error)) {
                        $errors[]         = [
                            $prepared_query,
                            $wpdb->last_error
                        ];
                        $wpdb->last_error = false;
                    }
                }
            }

            // Update wp_termmeta
            $wp_termmeta = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}termmeta WHERE meta_value LIKE %s;", '%' . $wpdb->esc_like($OLD_DOMAIN) . '%'), ARRAY_A);
            if ($wp_termmeta) {
                foreach ($wp_termmeta as $meta) {
                    $value = self::recursive_unserialize_replace($OLD_DOMAIN, $NEW_DOMAIN, $meta['meta_value']);
                    if ($value != $meta['meta_value']) {
                        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}termmeta SET meta_value = %s WHERE meta_id = %d;", $value, $meta['meta_id']));
                    }
                    if (!empty($wpdb->last_error)) {
                        $errors[]         = [
                            $prepared_query,
                            $wpdb->last_error
                        ];
                        $wpdb->last_error = false;
                    }
                }
            }

            // Update wp_usermeta
            $wp_usermeta = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}usermeta WHERE meta_value LIKE %s;", '%' . $wpdb->esc_like($OLD_DOMAIN) . '%'), ARRAY_A);
            if ($wp_usermeta) {
                foreach ($wp_usermeta as $meta) {
                    $value = self::recursive_unserialize_replace($OLD_DOMAIN, $NEW_DOMAIN, $meta['meta_value']);
                    if ($value != $meta['meta_value']) {
                        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}usermeta SET meta_value = %s WHERE umeta_id = %d;", $value, $meta['umeta_id']));
                    }
                    if (!empty($wpdb->last_error)) {
                        $errors[]         = [
                            $prepared_query,
                            $wpdb->last_error
                        ];
                        $wpdb->last_error = false;
                    }
                }
            }

            if ($result == TRUE && sizeof($errors) == 0) {

                xagio_json('success', 'Successfully performed cloning!');

            } else {

                xagio_json('error', 'Failed to merge databases!', $errors);

            }

        }


        /**
         * Wrapper for str_replace
         *
         * @param string $from
         * @param string $to
         * @param string $data
         * @param string|bool $case_insensitive
         *
         * @return string
         */
        public static function str_replace($from, $to, $data, $case_insensitive = FALSE)
        {
            if ('on' === $case_insensitive) {
                $data = str_ireplace($from, $to, $data);
            } else {
                $data = str_replace($from, $to, $data);
            }

            return $data;
        }

        /**
         * Return unserialized object or array
         *
         * @param string $serialized_string Serialized string.
         * @param string $method The name of the caller method.
         *
         * @return mixed, false on failure
         */
        public static function unserialize($serialized_string)
        {
            if (!is_serialized($serialized_string)) {
                return FALSE;
            }

            $serialized_string   = trim($serialized_string);
            $unserialized_string = @unserialize($serialized_string);

            return $unserialized_string;
        }

        /**
         * Adapated from interconnect/it's search/replace script.
         *
         * @link https://interconnectit.com/products/search-and-replace-for-wordpress-databases/
         *
         * Take a serialised array and unserialise it replacing elements as needed and
         * unserialising any subordinate arrays and performing the replace on those too.
         *
         * @access private
         * @param string $from String we're looking to replace.
         * @param string $to What we want it to be replaced with
         * @param array $data Used to pass any subordinate arrays back to in.
         * @param boolean $serialised Does the array passed via $data need serialising.
         * @param sting|boolean $case_insensitive Set to 'on' if we should ignore case, false otherwise.
         *
         * @return string|array    The original array with all elements replaced as needed.
         */
        public static function recursive_unserialize_replace($from = '', $to = '', $data = '', $serialised = FALSE, $case_insensitive = FALSE)
        {
            try {

                if (is_string($data) && !is_serialized_string($data) && ($unserialized = self::unserialize($data)) !== FALSE) {
                    $data = self::recursive_unserialize_replace($from, $to, $unserialized, TRUE, $case_insensitive);
                } else if (is_array($data)) {
                    $_tmp = [];
                    foreach ($data as $key => $value) {
                        $_tmp[$key] = self::recursive_unserialize_replace($from, $to, $value, FALSE, $case_insensitive);
                    }

                    $data = $_tmp;
                    unset($_tmp);
                } // Submitted by Tina Matter
                else if (is_object($data)) {
                    if ('__PHP_Incomplete_Class' !== get_class($data)) {
                        $_tmp  = $data;
                        $props = get_object_vars($data);
                        foreach ($props as $key => $value) {
                            $_tmp->$key = self::recursive_unserialize_replace($from, $to, $value, FALSE, $case_insensitive);
                        }

                        $data = $_tmp;
                        unset($_tmp);
                    }
                } else if (is_serialized_string($data)) {
                    $unserialized = self::unserialize($data);

                    if ($unserialized !== FALSE) {
                        $data = self::recursive_unserialize_replace($from, $to, $unserialized, TRUE, $case_insensitive);
                    }
                } else {
                    if (is_string($data)) {
                        $data = self::str_replace($from, $to, $data, $case_insensitive);
                    }
                }

                if ($serialised) {
                    return serialize($data);
                }

            } catch (Exception $error) {

            }

            return $data;
        }


        public static function obtainApiKey()
        {

            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            sleep(1.5);

            if (!isset($_POST['url'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $URL = sanitize_url(wp_unslash($_POST['url']));
            if (!filter_var($URL, FILTER_VALIDATE_URL)) {
                xagio_json('error', 'Provided "url" argument is not an actual URL.');
                return;
            }
            $URL = wp_parse_url($URL);

            $http_code = 0;
            $output    = XAGIO_API::apiRequest('key', 'GET', ['domain' => $URL['host']], $http_code);

            if ($http_code == 200) {

                xagio_json('success', 'Successfully obtained API key!', [
                    'key'        => $output['message'],
                    'admin_post' => $output['admin_post'] . 'api'
                ]);

            } else {

                xagio_json('error', $output['message']);

            }

        }

        public static function recurseCopy($src, $dst)
        {
            $dir = opendir($src);
            @xagio_mkdir($dst);
            while (FALSE !== ($file = readdir($dir))) {
                if (($file != '.') && ($file != '..')) {
                    if (is_dir($src . '/' . $file)) {
                        self::recurseCopy($src . '/' . $file, $dst . '/' . $file);
                    } else {
                        copy($src . '/' . $file, $dst . '/' . $file);
                    }
                }
            }
            closedir($dir);
        }

        private static function getBetween($string, $start, $end)
        {
            $string = ' ' . $string;
            $ini    = strpos($string, $start);
            if ($ini == 0)
                return '';
            $ini += strlen($start);
            $len = strpos($string, $end, $ini) - $ini;
            return substr($string, $ini, $len);
        }

        public static function downloadCloneBackup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wp_filesystem;

            if (!isset($_POST['backup'])) {
                xagio_json('error', 'General Error.');
                return;
            }

            $BACKUP   = sanitize_text_field(wp_unslash($_POST['backup']));
            $tempDir  = XAGIO_PATH . '/tmp/';
            $tempFile = $tempDir . md5($BACKUP) . '.zip';
            $extDir   = $tempDir . md5(XAGIO_AUTH_KEY . XAGIO_AUTH_SALT . $tempFile);

            $isSuccessful = FALSE;

            // Check if temp dir exists
            if (!file_exists($tempDir)) {
                wp_mkdir_p($tempDir);
            }

            // Check if ext dir exists
            if (!file_exists($extDir)) {
                wp_mkdir_p($extDir);
            }

            // Check if file already exists
            if (file_exists($tempFile)) {
                wp_delete_file($tempFile);
            }

            // Initialize WP_Filesystem
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            WP_Filesystem();

            // Download the zip file
            $response = wp_remote_get($BACKUP, [
                'timeout'   => 600,
                'stream'    => true,
                'filename'  => $tempFile,
                'sslverify' => false,
            ]);

            if (is_wp_error($response)) {
                xagio_json('error', $response->get_error_message());
                return;
            }

            // Unzip it
            if (class_exists('ZipArchive')) {
                $zip = new xagio_ZipArchiveX();
                $res = $zip->open($tempFile);
                if ($res === TRUE) {
                    $out = $zip->extractTo($extDir);
                    if ($out == FALSE) {
                        xagio_json('error', 'Failed to unzip cloned backup using ZipArchive.');
                        return;
                    }
                    $zip->close();
                }
            } else {
                xagio_json('error', 'ZipArchive is not installed.');
                return;
            }

            // Check if unzipped
            if (file_exists($extDir . DIRECTORY_SEPARATOR . 'index.php')) {
                $isSuccessful = TRUE;
            }

            wp_delete_file($tempFile);

            if (!$isSuccessful) {
                xagio_json('error', 'There was a problem while downloading a copy of the Remote Website.');
            } else {
                // Regenerate wp-config with prefix
                $prefix    = 'wp_';
                $wp_config = $extDir . DIRECTORY_SEPARATOR . 'wp-config.php';
                if (file_exists($wp_config)) {
                    $config_contents = $wp_filesystem->get_contents($wp_config);
                    $lines           = explode("\n", $config_contents);
                    foreach ($lines as $line) {
                        if (strpos($line, 'table_prefix') !== FALSE) {
                            $prefix = self::getBetween($line, "= '", "';");
                            break;
                        }
                    }
                    XAGIO_MODEL_RESCUE::regenerateWpConfig($prefix, $extDir . DIRECTORY_SEPARATOR);
                }

                xagio_json('success', 'Successfully downloaded and unzipped cloned backup.', [
                    'prefix' => $prefix,
                    'extDir' => $extDir
                ]);
            }
        }

        public static function removeCloneBackup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['url'], $_POST['key'], $_POST['backup'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $URL = sanitize_url(wp_unslash($_POST['url']));
            if (!filter_var($URL, FILTER_VALIDATE_URL)) {
                xagio_json('error', 'Provided "url" argument is not an actual URL.');
                return;
            }
            $KEY    = sanitize_text_field(wp_unslash($_POST['key']));
            $BACKUP = sanitize_text_field(wp_unslash($_POST['backup']));

            $RESULT = self::createRequest($URL, [
                'key'         => $KEY,
                'function'    => 'removeCloneBackup',
                'backup_name' => basename($BACKUP),
            ]);

            xagio_jsonc($RESULT);
        }

        public static function createCloneBackup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['url'], $_POST['key'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $URL = sanitize_url(wp_unslash($_POST['url']));
            if (!filter_var($URL, FILTER_VALIDATE_URL)) {
                xagio_json('error', 'Provided "url" argument is not an actual URL.');
                return;
            }
            $KEY = sanitize_text_field(wp_unslash($_POST['key']));

            $RESULT = self::createRequest($URL, [
                'key'      => $KEY,
                'function' => 'createCloneBackup',
            ]);

            xagio_jsonc($RESULT);

        }

        public static function verifyConnection()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            sleep(2);

            if (!isset($_POST['url'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $URL = sanitize_url(wp_unslash($_POST['url']));
            if (!filter_var($URL, FILTER_VALIDATE_URL)) {
                xagio_json('error', 'Provided "url" argument is not an actual URL.');
                return;
            }
            $URL = wp_parse_url($URL);
            $URL = $URL['scheme'] . '://' . $URL['host'];

            // Possible endpoints
            $ENDPOINTS = [
                '/wp-json/xagio-seo/v1/',
                '/?rest_route=/xagio-seo/v1/'
            ];

            foreach ($ENDPOINTS as $ENDPOINT) {

                $RESULT = self::createRequest($URL . $ENDPOINT . 'ping');

                if (isset($RESULT['status'])) {

                    if ($RESULT['status'] == 'success' && $RESULT['message'] == 'pong') {

                        xagio_json('success', 'Communication with ' . $URL . ' is successful. You can proceed with cloning.', $URL . $ENDPOINT . 'api');

                    }

                }

            }

            xagio_json('error', 'There was a problem communicating with ' . $URL . '. Make sure that xagio is updated to the latest version.');

        }

        private static function createRequest($url = '', $data = [], $method = 'POST')
        {
            $auth = false;
            if (isset($data['key'])) {
                $auth = 'Basic ' . $data['key'];
                unset($data['key']);
            }

            $postFields = [
                'user-agent'  => "Xagio - " . XAGIO_CURRENT_VERSION . " (" . site_url() . ")",
                'timeout'     => 600,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => TRUE,
                'method'      => $method,
                'body'        => $data,
                'sslverify'   => FALSE,
            ];

            if ($auth != false) {
                $postFields['headers'] = [
                    'Authorization' => $auth,
                ];
            }

            $response = wp_remote_post($url, $postFields);

            if (is_wp_error($response)) {
                return FALSE;
            } else {
                if (!isset($response['body'])) {
                    return FALSE;
                } else {
                    $data = json_decode($response['body'], TRUE);
                    if (!$data) {
                        return FALSE;
                    } else {
                        return $data;
                    }
                }
            }

        }


    }

}