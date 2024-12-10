<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_RESCUE')) {

    class XAGIO_MODEL_RESCUE
    {

        public static function initialize()
        {
            if (!XAGIO_HAS_ADMIN_PERMISSIONS) return;

            add_action('admin_post_xagio_download_core', [
                'XAGIO_MODEL_RESCUE',
                'downloadCore'
            ]);
            add_action('admin_post_xagio_files_core', [
                'XAGIO_MODEL_RESCUE',
                'previewCoreFiles'
            ]);
            add_action('admin_post_xagio_start_core_rescue', [
                'XAGIO_MODEL_RESCUE',
                'startCoreRescue'
            ]);
            add_action('admin_post_xagio_remove_old_core', [
                'XAGIO_MODEL_RESCUE',
                'removeOldCoreFiles'
            ]);

            add_action('admin_post_xagio_scan_plugins_themes', [
                'XAGIO_MODEL_RESCUE',
                'scanPluginsThemes'
            ]);
            add_action('admin_post_xagio_uninstall_plugin_theme', [
                'XAGIO_MODEL_RESCUE',
                'uninstallPluginTheme'
            ]);
            add_action('admin_post_xagio_normal_rescue_plugin_theme', [
                'XAGIO_MODEL_RESCUE',
                'normalRescuePluginTheme'
            ]);
            add_action('admin_post_xagio_upload_rescue_plugin_theme', [
                'XAGIO_MODEL_RESCUE',
                'uploadRescuePluginTheme'
            ]);

            add_action('admin_post_xagio_scan_uploads', [
                'XAGIO_MODEL_RESCUE',
                'scanUploads'
            ]);
            add_action('admin_post_xagio_remove_uploads', [
                'XAGIO_MODEL_RESCUE',
                'removeUploads'
            ]);
        }

        public static function removeUploads()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['files'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $uploads = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
            $files   = array_map('sanitize_text_field', wp_unslash($_POST['files']));
            foreach ($files as $file) {
                if (strpos($file, $uploads) !== FALSE) {
                    wp_delete_file($file);
                }
            }
            xagio_json('success', 'Successfully removed files.', $files);
        }

        public static function scanUploads()
        {

            // Give user some experience
            sleep(3);

            $allowed_extensions = [
                'xml',
                'dtd',
                'zip',
                'rar',
                'tar.gz',
                'tar',
                '7z',
                'jpg',
                'jpeg',
                'gif',
                'png',
                'avi',
                'mp4',
                'mpeg',
                'mp3',
                'wav',
                'ogg',
                'svg',
                'json',
                'bin',
            ];

            $uploads = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
            $files   = XAGIO_MODEL_RESCUE::getFiles($uploads);

            $suspicious_files = [];

            foreach ($files as $file => $data) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed_extensions)) {
                    $suspicious_files[$file] = $data;
                }
            }

            xagio_json('success', 'Successfully retrieved suspicious files.', $suspicious_files);
        }

        public static function uploadRescuePluginTheme()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['type']) || !isset($_POST['slug']) || !isset($_FILES['file'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $type = sanitize_text_field(wp_unslash($_POST['type']));
            $slug = sanitize_text_field(wp_unslash($_POST['slug']));

            $theme_plugin_dir = dirname($slug);
            if (empty($theme_plugin_dir) || $theme_plugin_dir === '.') {
                $theme_plugin_dir = $slug;
            }

            $unzip_directory = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $type . 's' . DIRECTORY_SEPARATOR;

            // Remove the old plugin
            XAGIO_MODEL_RESCUE::deleteFolder($unzip_directory . $theme_plugin_dir);

            // Move and Unzip
            XAGIO_MODEL_RESCUE::moveUploadUnzip(map_deep($_FILES['file'], 'sanitize_text_field'), $unzip_directory);

            $result = file_exists($unzip_directory . $theme_plugin_dir);

            if ($result !== FALSE) {
                xagio_json('success', 'Rescue operation completed successfully.', $result);
            } else {
                xagio_json('error', 'Failed to perform rescue operation. Please reinstall ' . $type . ' manually.');
            }

        }

        public static function normalRescuePluginTheme()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['type']) || !isset($_POST['slug']) || !isset($_POST['download'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $type     = sanitize_text_field(wp_unslash($_POST['type']));
            $slug     = sanitize_text_field(wp_unslash($_POST['slug']));
            $download = sanitize_text_field(wp_unslash($_POST['download']));

            $theme_plugin_dir = dirname($slug);
            if (empty($theme_plugin_dir) || $theme_plugin_dir === '.') {
                $theme_plugin_dir = $slug;
            }

            $unzip_directory = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $type . 's' . DIRECTORY_SEPARATOR;

            $result = XAGIO_MODEL_RESCUE::downloadUnzip($download, $theme_plugin_dir, $unzip_directory);

            if ($result !== FALSE) {
                xagio_json('success', 'Rescue operation completed successfully.', $result);
            } else {
                xagio_json('error', 'Failed to perform rescue operation. Please reinstall ' . $type . ' manually.');
            }

        }

        public static function uninstallPluginTheme()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['type']) || !isset($_POST['slug'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $type = sanitize_text_field(wp_unslash($_POST['type']));
            $slug = sanitize_text_field(wp_unslash($_POST['slug']));

            if ($type === 'theme') {
                require_once(ABSPATH . 'wp-admin/includes/theme.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                delete_theme($slug);
            } else if ($type === 'plugin') {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                delete_plugins([$slug]);
            }

            xagio_json('success', 'Operation completed.');
        }

        public static function scanPluginsThemes()
        {
            xagio_json('success', 'Retrieved data.', XAGIO_MODEL_RESCUE::getPluginsThemes());
        }

        public static function removeOldCoreFiles()
        {
            // Remove the WordPress core files
            XAGIO_MODEL_RESCUE::deleteFolder(XAGIO_PATH . '/tmp/wordpress');
        }

        public static function regenerateWpConfig($prefix = FALSE, $location = FALSE)
        {
            global $table_prefix;

            if ($prefix != FALSE) {
                $table_prefix = $prefix;
            }

            $t = "<?php\n";
            $t .= "\n";
            $t .= "/* Generated by xagio */\n";
            $t .= "\n";
            $t .= "/* MySQL settings */\n";
            $t .= "define( 'DB_NAME',     '" . DB_NAME . "' );\n";
            $t .= "define( 'DB_USER',     '" . DB_USER . "' );\n";
            $t .= "define( 'DB_PASSWORD', '" . DB_PASSWORD . "' );\n";
            $t .= "define( 'DB_HOST',     '" . DB_HOST . "' );\n";
            $t .= "define( 'DB_CHARSET',  '" . DB_CHARSET . "' );\n";
            $t .= "\n";
            $t .= "/* MySQL database table prefix. */\n";
            $t .= "\$table_prefix = '" . $table_prefix . "';\n";
            $t .= "\n";
            $t .= "/* Authentication Unique Keys and Salts. */\n";
            $t .= "/* https://api.wordpress.org/secret-key/1.1/salt/ */\n";
            $t .= "define( 'AUTH_KEY',         '" . XAGIO_AUTH_KEY . "' );\n";
            $t .= "define( 'SECURE_AUTH_KEY',  '" . XAGIO_AUTH_SALT . "' );\n";
            $t .= "define( 'LOGGED_IN_KEY',    '" . LOGGED_IN_KEY . "' );\n";
            $t .= "define( 'NONCE_KEY',        '" . NONCE_KEY . "' );\n";
            $t .= "define( 'XAGIO_AUTH_SALT',        '" . XAGIO_AUTH_SALT . "' );\n";
            $t .= "define( 'SECURE_XAGIO_AUTH_SALT', '" . XAGIO_AUTH_SALT . "' );\n";
            $t .= "define( 'LOGGED_IN_SALT',   '" . LOGGED_IN_SALT . "' );\n";
            $t .= "define( 'NONCE_SALT',       '" . NONCE_SALT . "' );\n";
            $t .= "\n";
            $t .= "/* Absolute path to the WordPress directory. */\n";
            $t .= "if ( !defined('ABSPATH') )\n";
            $t .= "	define('ABSPATH', dirname(__FILE__) . '/');\n";
            $t .= "\n";
            $t .= "/* Sets up WordPress vars and included files. */\n";
            $t .= "require_once(ABSPATH . 'wp-settings.php');\n";
            $t .= "?>";

            if ($location == FALSE) {
                xagio_file_put_contents(ABSPATH . 'wp-config.php', $t);
            } else {
                xagio_file_put_contents($location . 'wp-config.php', $t);
            }

        }

        public static function startCoreRescue()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // Check if at least one of the file arrays is set
            if (
                !isset($_POST['filesToAdd']) &&
                !isset($_POST['filesToDelete']) &&
                !isset($_POST['filesToOverwrite'])
            ) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // Retrieve and sanitize the inputs
            $filesToAdd = isset($_POST['filesToAdd']) ? map_deep(wp_unslash($_POST['filesToAdd']), 'sanitize_text_field') : [];
            $filesToDelete = isset($_POST['filesToDelete']) ? map_deep(wp_unslash($_POST['filesToDelete']), 'sanitize_text_field') : [];
            $filesToOverwrite = isset($_POST['filesToOverwrite']) ? map_deep(wp_unslash($_POST['filesToOverwrite']), 'sanitize_text_field') : [];

            // Check if any files are present after sanitization
            if (empty($filesToAdd) && empty($filesToDelete) && empty($filesToOverwrite)) {
                xagio_json('Invalid file data received.', 'Your list of files could not be properly parsed. Please contact support.');
                return;
            }

            // Process the file changes
            $files = XAGIO_MODEL_RESCUE::processFiles($filesToDelete, $filesToOverwrite, $filesToAdd);

            // Remove the WordPress core files
            XAGIO_MODEL_RESCUE::deleteFolder(XAGIO_PATH . '/tmp/wordpress');

            // Regenerate the WP-CONFIG
            XAGIO_MODEL_RESCUE::regenerateWpConfig();

            // Output
            xagio_json('Successfully finished WordPress core rescue.', 'Rescue has been successfully performed for your selected WordPress core files. You can leave this page now.', $files);
        }

        public static function downloadCore()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['version'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // Get the version for download
            $version = sanitize_text_field(wp_unslash($_POST['version']));

            // Download the WP core
            $coreDownload = XAGIO_MODEL_RESCUE::downloadUnzip('https://wordpress.org/wordpress-' . $version . '.zip', 'wordpress');

            if (!$coreDownload) {

                xagio_json('error', 'There was a problem while downloading WordPress core files. Please try again later.');
                return;

            } else {

                xagio_json('success', 'WordPress core files successfully downloaded. You may now proceed to view the detected changes between local and remote core files.');
                return;

            }

        }

        public static function getCoreExcludes()
        {
            $excludes = XAGIO_MODEL_RESCUE::getPluginsThemes(TRUE);
            return array_merge($excludes, [
                'uploads',
                'wp-config.php',
                'xagio-api.php',
                'wp-config-sample.php',
                '.htaccess',
            ]);
        }

        public static function previewCoreFiles()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $current_wp_files = XAGIO_MODEL_RESCUE::getFiles(ABSPATH, '', XAGIO_MODEL_RESCUE::getCoreExcludes());
            $new_wp_files     = XAGIO_MODEL_RESCUE::getFiles(XAGIO_PATH . '/tmp/wordpress/', '', XAGIO_MODEL_RESCUE::getCoreExcludes());

            $advanced = TRUE;
            if (isset($_POST['type'])) {
                if ($_POST['type'] === 'easy') {
                    $advanced = FALSE;
                }
            }

            $compiled_list = XAGIO_MODEL_RESCUE::compareFiles($current_wp_files, $new_wp_files, $advanced, ABSPATH);

            ksort($compiled_list);

            xagio_json('success', 'Ready for rescue.', $compiled_list);
        }

        public static function getPluginsThemes($only_slugs = FALSE)
        {
            // URL templates
            $themeUrl  = 'https://downloads.wordpress.org/theme/%s.zip';
            $pluginUrl = 'https://downloads.wordpress.org/plugin/%s.zip';

            // Collect all plugins & themes
            $allPluginsThemes = XAGIO_API::getPluginsThemes(TRUE);

            // Output array
            $results = [
                'plugins' => [
                    'found'   => [],
                    'missing' => [],
                ],
                'themes'  => [
                    'found'   => [],
                    'missing' => [],
                ],
            ];

            if ($only_slugs === TRUE) {
                $results = [];
            }

            // Loop through plugins
            foreach ($allPluginsThemes['plugins'] as $slug => $data) {

                if ($only_slugs === TRUE) {
                    $plugin_dir = dirname($slug);
                    if (!empty($plugin_dir) && $plugin_dir !== '.') {
                        $results[] = $plugin_dir;
                    } else {
                        $results[] = $slug;
                    }
                    continue;
                }

                $plugin_dir = dirname($slug);
                if (empty($plugin_dir) || $plugin_dir === '.') {
                    $plugin_dir = str_replace('.php', '', $slug);
                }

                $downloadUrl = sprintf($pluginUrl, $plugin_dir . '.' . $data['Version']);

                if (XAGIO_MODEL_RESCUE::verifyURL($downloadUrl)) {

                    $data['DownloadUrl']                = $downloadUrl;
                    $results['plugins']['found'][$slug] = $data;

                } else {

                    $results['plugins']['missing'][$slug] = $data;

                }

            }

            // Loop through themes
            foreach ($allPluginsThemes['themes'] as $slug => $data) {

                if ($only_slugs === TRUE) {
                    $results[] = $slug;
                    continue;
                }

                $downloadUrl = sprintf($themeUrl, $slug . '.' . $data['Version']);

                if (XAGIO_MODEL_RESCUE::verifyURL($downloadUrl)) {

                    $data['DownloadUrl']               = $downloadUrl;
                    $results['themes']['found'][$slug] = $data;

                } else {

                    $results['themes']['missing'][$slug] = $data;

                }

            }

            return $results;
        }


        public static function verifyURL($url)
        {
            $response = wp_remote_head($url, [
                'timeout'    => 10,
                // Adjust the timeout as needed
                'sslverify'  => false,
                'user-agent' => 'WordPress/4.8',
            ]);

            if (is_wp_error($response)) {
                return false; // Handle the error as needed
            }

            $http_code = wp_remote_retrieve_response_code($response);

            return $http_code == 200;
        }

        public static function getAvailableCoreVersions()
        {
            $releases_url = 'https://wordpress.org/download/releases/';
            $versions     = [];

            $data = wp_remote_get($releases_url);

            preg_match_all('/wordpress\-([0-9]\.[0-9].*?)?\.[a-z]{3}.*?/', $data['body'], $matches, PREG_SET_ORDER, 0);

            foreach ($matches as $match) {
                if (strpos($match[1], '-') !== FALSE) {
                    continue;
                }
                $versions[] = $match[1];
            }

            $versions = array_unique($versions);

            return $versions;
        }

        public static function downloadUnzip($remote_path, $local_path, $alternative_temp = FALSE)
        {
            global $wp_filesystem;

            // Initialize WP_Filesystem
            if (!function_exists('WP_Filesystem')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }

            WP_Filesystem();

            $tempDir  = $alternative_temp !== FALSE ? $alternative_temp : XAGIO_PATH . '/tmp/';
            $tempFile = $tempDir . md5($remote_path) . '.zip';

            $isSuccessful = FALSE;

            // Check if temp dir exists
            if (!$wp_filesystem->exists($tempDir)) {
                $wp_filesystem->mkdir($tempDir);
            }

            // Check if file already exists
            if ($wp_filesystem->exists($tempFile)) {
                $wp_filesystem->delete($tempFile);
            }

            // Check if the extraction path exists
            if ($wp_filesystem->exists($tempDir . $local_path)) {
                XAGIO_MODEL_RESCUE::deleteFolder($tempDir . $local_path);
            }

            // Download the zip file
            $response = wp_remote_get($remote_path, array('timeout' => 60));
            if (is_wp_error($response)) {
                return FALSE;
            }

            $wp_filesystem->put_contents($tempFile, wp_remote_retrieve_body($response));

            // Unzip it
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                $res = $zip->open($tempFile);
                if ($res === TRUE) {
                    $zip->extractTo($tempDir);
                    $zip->close();
                } else {
                    return FALSE;
                }
            } else {
                return FALSE;
            }

            // check if unzipped
            if ($wp_filesystem->exists($tempDir . $local_path)) {
                $isSuccessful = TRUE;
            }

            $wp_filesystem->delete($tempFile);

            if (!$isSuccessful) {
                return FALSE;
            } else {
                return $tempDir . $local_path;
            }
        }

        public static function moveUploadUnzip($file, $local_path)
        {
            // Include the necessary WordPress file handling functions
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }

            // Check if temp dir exists
            if (!file_exists($local_path)) {
                xagio_mkdir($local_path);
            }

            $upload_overrides = array('test_form' => false);

            // Use wp_handle_upload instead of move_uploaded_file
            $uploaded_file = wp_handle_upload($file, $upload_overrides);

            if ($uploaded_file && !isset($uploaded_file['error'])) {
                $tempFile = $uploaded_file['file'];

                // Unzip it
                if (class_exists('ZipArchive')) {
                    $zip = new xagio_ZipArchiveX();
                    $res = $zip->open($tempFile);
                    if ($res === TRUE) {
                        $zip->extractTo($local_path);
                        $zip->close();
                    }
                } else {
                    return false;
                }

                wp_delete_file($tempFile);
            } else {
                // Handle upload error
                return false;
            }
        }

        public static function processFiles($filesToDelete = [], $filesToOverwrite = [], $filesToAdd = [])
        {

            $filesAdded       = [];
            $filesOverwritten = [];
            $filesDeleted     = [];

            foreach ($filesToAdd as $aFiles) {
                $newFile = $aFiles[0];
                $oldFile = $aFiles[1];

                $oldDir = dirname($oldFile);
                @xagio_mkdir($oldDir, 0777, TRUE);

                copy($newFile, $oldFile);

                $filesAdded[] = $oldFile;
            }

            foreach ($filesToOverwrite as $oFiles) {
                $newFile = $oFiles[0];
                $oldFile = $oFiles[1];

                copy($newFile, $oldFile);

                $filesOverwritten[] = $oldFile;
            }

            foreach ($filesToDelete as $dFile) {
                wp_delete_file($dFile);

                $filesDeleted[] = $dFile;
            }

            return [
                'filesAdded'       => $filesAdded,
                'filesOverwritten' => $filesOverwritten,
                'filesDeleted'     => $filesDeleted,
            ];

        }

        public static function deleteFolder($dir)
        {
            include_once ABSPATH . 'wp-admin/includes/file.php';

            // Initialize the WP Filesystem
            global $wp_filesystem;
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            $creds = request_filesystem_credentials(site_url() . '/wp-admin/', '', false, false, []);
            if (!WP_Filesystem($creds)) {
                return false;
            }

            // Check if the directory exists
            if (!$wp_filesystem->is_dir($dir)) {
                return false;
            }

            // Get the list of files in the directory
            $files = $wp_filesystem->dirlist($dir);

            // Iterate through the files and delete them
            foreach ($files as $file) {
                $file_path = $dir . DIRECTORY_SEPARATOR . $file['name'];
                if ($file['type'] === 'd') {
                    self::deleteFolder($file_path);
                } else {
                    $wp_filesystem->delete($file_path);
                }
            }

            // Delete the directory itself
            return $wp_filesystem->delete($dir, true);
        }

        public static function getFiles($root_path = '', $path = '', $exclusions = [])
        {
            $files = [];

            if (empty($root_path))
                $root_path = ABSPATH;

            $handle = opendir($root_path . $path);

            if (!$handle)
                return $files;

            // loop through dirs/files
            while (FALSE !== ($file = readdir($handle))) {

                // Ignore . and ..
                if ("." == $file || ".." == $file || in_array($file, $exclusions)) {
                    continue;
                }

                $full_file_name     = ltrim($path . DIRECTORY_SEPARATOR . $file, DIRECTORY_SEPARATOR);
                $full_dir_file_name = $root_path . $full_file_name;

                // Directory? else file
                if ('dir' === filetype($full_dir_file_name)) {

                    // We are on a directory lets go one deeper
                    $new_files = XAGIO_MODEL_RESCUE::getFiles($root_path, $full_file_name, $exclusions);
                    $files     = array_merge($files, $new_files);

                } else {

                    $files[$full_file_name] = [
                        'md5'  => md5_file($full_dir_file_name),
                        'path' => $full_dir_file_name,
                    ];

                }
            }

            // Close connection
            closedir($handle);

            return $files;

        }

        public static function compareFiles($old_files, $new_files, $multiDimensional = TRUE, $rootPath = '')
        {

            $compiled_list = [];

            foreach ($new_files as $file => $new_data) {

                if (isset($old_files[$file])) {

                    if ($old_files[$file]['md5'] !== $new_data['md5']) {
                        $old_files[$file]['action'] = 'force-overwrite';
                    } else {
                        $old_files[$file]['action'] = 'overwrite';
                    }

                    $old_files[$file]['new_path'] = $new_data['path'];

                } else {


                    $old_files[$file] = [
                        'action'   => 'add',
                        'path'     => $rootPath . $file,
                        'new_path' => $new_data['path'],
                        'md5'      => $new_data['md5'],
                    ];

                }

            }

            foreach ($old_files as $file => $old_data) {

                if ($multiDimensional) {

                    $parts = explode(DIRECTORY_SEPARATOR, $file);

                    $last_array = &$compiled_list;

                    for ($i = 0; $i < sizeof($parts); $i++) {

                        $last_array =& $last_array[$parts[$i]];

                    }

                    unset($old_data['md5']);
                    if (!isset($old_data['action'])) {
                        $old_data['action'] = 'delete';
                    }
                    $last_array = $old_data;

                } else {

                    unset($old_data['md5']);
                    if (!isset($old_data['action'])) {
                        $old_data['action'] = 'delete';
                    } else if ($old_data['action'] === 'overwrite') {
                        continue;
                    }

                    $compiled_list[$file] = $old_data;

                }

            }


            return $compiled_list;
        }

    }

}