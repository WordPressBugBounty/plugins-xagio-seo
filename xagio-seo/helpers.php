<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_REQUEST')) {
    class XAGIO_REQUEST
    {
        public function get_param($param)
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');
            if (isset($_GET[$param])) {
                return sanitize_text_field(wp_unslash($_GET[$param]));
            } else if (isset($_POST[$param])) {
                return sanitize_text_field(wp_unslash($_POST[$param]));
            }
            return false;
        }
    }

}

if (!function_exists('xagio_is_base64')) {
    function xagio_is_base64($string)
    {
        // Check if the string length is not equal to 32 (to rule out MD5 hashes)
        if (strlen($string) === 32) {
            return false;
        }

        // Check if the string length is a multiple of 4 and contains only valid base64 characters
        if (preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $string) && strlen($string) % 4 === 0) {
            return true;
        }

        return false;
    }
}

if (!function_exists('xagio_current_user_can')) {

    function xagio_current_user_can($capability, ...$args)
    {
        // Ensure the user is initialized if it hasn't been already
        if (!function_exists('wp_get_current_user')) {
            require_once ABSPATH . 'wp-includes/pluggable.php';
        }

        // Fetch the current user
        $current_user = wp_get_current_user();

        // If no user is found, assume an unauthenticated request (user ID 0)
        if (!isset($current_user->ID) || 0 == $current_user->ID) {
            return false;
        }

        // Check if the current user can perform the capability
        return user_can($current_user, $capability, ...$args);
    }

}

if (defined('ABSPATH')) {
    if (file_exists(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php')) {
        require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
    }
}

if (!class_exists('Xagio_Silent_Upgrader_Skin') && class_exists('WP_Upgrader_Skin')) {

    if (!function_exists('request_filesystem_credentials')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    class Xagio_Silent_Upgrader_Skin extends WP_Upgrader_Skin
    {
        public function header()
        {
            // Override to suppress the header output
        }

        public function footer()
        {
            // Override to suppress the footer output
        }

        public function error($errors)
        {
            // Override to suppress error messages
            // You can log errors here if needed
        }

        public function feedback($string, ...$args)
        {
            // Override to suppress feedback messages
            // You can log feedback here if needed
        }

        public function before()
        {
            // Override to suppress before upgrade output
        }

        public function after()
        {
            // Override to suppress after upgrade output
        }
    }
}

if (!function_exists('xagio_preZipAdd')) {
    function xagio_preZipAdd($p_event, &$p_header)
    {
        $info = pathinfo($p_header['stored_filename']);
        // ----- zip files are skipped
        if (isset($info['extension'])) {
            if (in_array($info['extension'], [
                'zip',
                'rar',
                '7z',
                'tar.gz',
                'tar',
                'gz'
            ])) {
                return 0;
            } else {
                return 1;
            }
        } else {
            return 1;
        }
    }
}

if (!class_exists('xagio_ZipArchiveX') && class_exists('ZipArchive')) {

    class xagio_ZipArchiveX
    {
        private $zipArchive;

        public function __construct()
        {
            $this->zipArchive = new ZipArchive();
        }

        // Open a zip file
        public function open($filename, $flags = 0)
        {
            return $this->zipArchive->open($filename, $flags);
        }

        public function getNameIndex($index, $flags = null)
        {
            return $this->zipArchive->getNameIndex($index, $flags);
        }

        // Extract the contents of the zip file to a directory
        public function extractTo($destination)
        {
            return $this->zipArchive->extractTo($destination);
        }

        // Close the zip file
        public function close()
        {
            return $this->zipArchive->close();
        }

        // Add a file to the zip archive
        public function addFile($file, $localname = null)
        {
            return $this->zipArchive->addFile($file, $localname);
        }

        public function addEmptyDir($dirname, $flags = false)
        {
            return $this->zipArchive->addEmptyDir($dirname, $flags);
        }

        // Custom packing method with the provided logic
        public function pack($files = [], $rootDirectory = '', array $excludedPaths = [
            'error_log',
            'wp-cron.php',
            'wp-links-opml.php',
            'wp-load.php',
            'wp-login.php',
            'wp-mail.php',
            'wp-settings.php',
            'wp-signup.php',
            'wp-trackback.php',
            'xmlrpc.php',
            'wp-config-sample.php',
            'wp-comments-post.php',
            'wp-blog-header.php',
            'wp-activate.php',
            'readme.html',
            'license.txt',
            'index.php',
        ], array             $excludedExtensions = [
            'zip',
            'rar',
            '7z',
            'tar.gz',
            'tar',
            'gz'
        ], array             $excludedFolders = [
            '.git',
            '.svn',
            'node_modules',
            'vendor',
            'wp-content/cache',
            'wp-content/upgrade',
            'wp-content/backup',
            'wp-content/uploads/backups',
            'wp-content/plugins/xagio-seo/backups',
            'nc_assets',
            '.well-known',
            'wp-admin',
            'wp-includes'
        ])
        {
            // Normalize root directory (ensure trailing slash)
            $rootDirectory = rtrim($rootDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            // Ensure $files is array
            if (is_string($files)) {
                $files = [$files];
            } elseif (is_bool($files)) {
                $files = [];
            }

            // Convert excluded lists to sets for O(1) lookups
            $excludedPathsSet      = array_flip($excludedPaths);
            $excludedFoldersSet    = array_flip($excludedFolders);
            $excludedExtensionsSet = array_flip($excludedExtensions);

            foreach ($files as $file) {
                // Normalize full path
                $fullPath     = $rootDirectory . ltrim(str_replace($rootDirectory, '', $file), DIRECTORY_SEPARATOR);
                $relativePath = str_replace($rootDirectory, '', $fullPath);

                // Check if the current path is excluded
                if (isset($excludedPathsSet[$relativePath])) {
                    continue;
                }

                // If it's a directory, iterate through its contents
                if (is_dir($fullPath)) {
                    if (isset($excludedFoldersSet[$relativePath])) {
                        continue;
                    }

                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($fullPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST
                    );

                    foreach ($iterator as $f) {
                        $subPath = str_replace($rootDirectory, '', $f->getPathname());

                        // Check for path exclusions
                        if (isset($excludedPathsSet[$subPath])) {
                            continue;
                        }

                        // Check for folder exclusions
                        if ($f->isDir()) {
                            // Check if this directory is in excludedFolders
                            if (isset($excludedFoldersSet[$subPath])) {
                                // Skip entire directory subtree
                                $iterator->next();
                                continue;
                            }

                            $this->zipArchive->addEmptyDir($subPath);
                        } else if ($f->isFile()) {
                            // Get file extension
                            $extension = $f->getExtension();

                            // Check if extension is excluded
                            if (isset($excludedExtensionsSet[$extension])) {
                                continue;
                            }

                            // Add the file to the ZIP
                            $this->zipArchive->addFile($f->getPathname(), $subPath);
                        }
                    }

                } elseif (is_file($fullPath)) {
                    // Add individual file if not excluded
                    $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
                    if (!isset($excludedExtensionsSet[$extension]) || basename($relativePath) == 'mysql.zip') {
                        $this->zipArchive->addFile($fullPath, $relativePath);
                    }
                }
            }
        }


    }

}


if (!function_exists('xagio_calculate_backup_size')) {
    function xagio_calculate_backup_size()
    {
        // Define the path to the WordPress installation
        $path = ABSPATH;

        // Initialize total size
        $totalSize = 0;

        // Recursive function to calculate total size of directory
        function folderSize($dir)
        {
            $size = 0;
            // Glob all files and directories
            foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
                // Check if it's a directory
                if (is_dir($each)) {
                    $size += folderSize($each);
                } else {
                    // Skip archive files
                    if (!preg_match('/\.(zip|tar|gz|rar|7z)$/', $each)) {
                        // Sum file sizes
                        $size += filesize($each);
                    }
                }
            }
            return $size;
        }

        // Calculate uncompressed total size
        $totalSize = folderSize($path);

        // Estimate the compression ratio (here assumed as 60% of original size)
        $compressedSize = $totalSize * 0.6;

        // Convert to megabytes
        $compressedSizeMB = $compressedSize / 1024 / 1024;

        // Return size in MB rounded to two decimals
        return round($compressedSizeMB, 2);
    }
}

if (!function_exists('xagio_backup_speed')) {
    function xagio_backup_speed()
    {
        global $wp_filesystem;

        // Initialize WP_Filesystem
        if (!function_exists('WP_Filesystem')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        WP_Filesystem();

        $filePath    = 'temp_10mb_file.txt';
        $zipFilePath = 'temp_10mb_file.zip';

        // Measure the time taken to zip the file
        $startTime = microtime(true);

        // Create a 10 MB file
        if (!$wp_filesystem->put_contents($filePath, str_repeat('A', 1024 * 1024 * 10), FS_CHMOD_FILE)) {
            return "Could not create the file.";
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE) !== TRUE) {
            return "Could not create zip file.";
        }

        $zip->addFile($filePath, basename($filePath));
        $zip->close();

        $endTime   = microtime(true);
        $timeTaken = $endTime - $startTime;

        // Delete the temporary files
        $wp_filesystem->delete($filePath);
        $wp_filesystem->delete($zipFilePath);

        // Define the grading thresholds (in seconds)
        $grades = [
            10 => 0.5,
            9  => 1,
            8  => 1.5,
            7  => 2,
            6  => 2.5,
            5  => 3,
            4  => 4,
            3  => 6,
            2  => 8,
            1  => 10
        ];

        // Determine the grade based on time taken
        $grade = 1; // Default to grade 1 if above all thresholds
        foreach ($grades as $g => $threshold) {
            if ($timeTaken <= $threshold) {
                $grade = $g;
                break;
            }
        }

        return [
            'time_taken' => $timeTaken,
            'grade'      => $grade
        ];
    }
}

if (!function_exists('xagio_rename')) {
    function xagio_rename($from, $to)
    {
        include_once ABSPATH . 'wp-admin/includes/file.php';

        // Initialize the WP Filesystem
        global $wp_filesystem;
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // Check if the path is writable
        return $wp_filesystem->move($from, $to);
    }
}

if (!function_exists('xagio_is_writable')) {
    function xagio_is_writable($path)
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

        // Check if the path is writable
        return $wp_filesystem->is_writable($path);
    }
}

if (!function_exists('xagio_mkdir')) {
    function xagio_mkdir($path, $permissions = 0755, $recursive = true)
    {
        global $wp_filesystem;

        if (empty($wp_filesystem)) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            WP_Filesystem();
        }

        if (!$wp_filesystem->exists($path)) {
            return $wp_filesystem->mkdir($path, $permissions);
        }

        return true; // Directory already exists
    }
}

if (!function_exists('xagio_file_get_contents')) {
    function xagio_file_get_contents($file_path)
    {
        global $wp_filesystem;

        if (empty($wp_filesystem)) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            WP_Filesystem();
        }

        $file_contents = $wp_filesystem->get_contents($file_path);
        if ($file_contents === false) {
            return false; // Handle error if needed
        }

        return $file_contents;
    }
}

if (!function_exists('xagio_file_put_contents')) {
    function xagio_file_put_contents($file_path, $contents)
    {
        global $wp_filesystem;

        if (empty($wp_filesystem)) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            WP_Filesystem();
        }

        $result = $wp_filesystem->put_contents($file_path, $contents);
        if ($result === false) {
            return false; // Handle error if needed
        }

        return true;
    }
}

if (!function_exists('xagio_similar_text')) {
    function xagio_similar_text($first, $second, &$percent = 0)
    {
        // check if strings beging with the same character
        if (substr($first, 0, 1) != substr($second, 0, 1)) {
            $percent = 0;
            return 0;
        }
        similar_text($first, $second, $percent);
        return $percent;
    }
}

if (!function_exists('xagio_is_plugin_active')) {
    function xagio_is_plugin_active($plugin)
    {
        return in_array($plugin, (array)get_option('active_plugins', array()), true);
    }
}

if (!function_exists('xagio_get_model_url')) {
    function xagio_get_model_url($file)
    {
        $current_dir = rtrim(dirname($file), 'models');
        $current_dir = str_replace(XAGIO_PATH . DIRECTORY_SEPARATOR, '', $current_dir);
        $module_url  = XAGIO_URL . $current_dir;
        return $module_url;
    }
}

if (!function_exists('xagio_get_models')) {
    function xagio_get_models()
    {
        $models = glob(XAGIO_PATH . '/modules/*/models/xagio_*.php');
        sort($models);
        return $models;
    }
}

if (!function_exists('xagio_load_page')) {
    function xagio_load_page()
    {
        if (!isset($_GET['page'])) {
            return;
        }

        $page = sanitize_text_field(wp_unslash($_GET['page']));
        $page = str_replace('xagio-', '', $page);
        $page = str_replace('-', '_', $page);

        $path = XAGIO_PATH . '/modules/' . $page . '/page.php';

        if (file_exists($path)) {

            require_once($path);
            xagio_load_static();

        } else {

            echo wp_kses_post("<div class='wrap'><h1>Oops, it's a 404!</h1> <p>xagio page that you've requested cannot be found. Please contact support!</p></div>");

        }
    }
}

if (!function_exists('xagio_load_static')) {
    function xagio_load_static()
    {
        echo "\n<!-- Static Classes -->\n";
        $staticClasses = glob(XAGIO_PATH . '/pages/ext/xagio_*');
        sort($staticClasses);
        foreach ($staticClasses as $s) {
            require_once($s);
        }
        echo "\n<!-- Static Classes -->\n";
    }
}

if (!function_exists('xagio_output')) {
    function xagio_output($data, $type = false)
    {
        if ($type !== false) {
            header('Content-Type: ' . $type);
        }
        echo wp_kses_post($data);
        wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
    }
}

if (!function_exists('xagio_jsonc')) {
    function xagio_jsonc($array = [])
    {
        wp_send_json($array);
        wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
    }
}

if (!function_exists('xagio_json')) {
    function xagio_json($type, $message, $data = NULL)
    {
        wp_send_json([
            'status'  => $type,
            'message' => $message,
            'data'    => $data,
        ]);
        wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
    }
}

if (!function_exists('xagio_parse_bool')) {
    function xagio_parse_bool($str)
    {
        $lowerStr = strtolower($str ?? '');

        if ($lowerStr === "true" || $lowerStr === "1" || $lowerStr === "yes" || $lowerStr === "on" || $lowerStr === 1) {
            return true;
        } elseif ($lowerStr === "false" || $lowerStr === "0" || $lowerStr === "no" || $lowerStr === "off" || $lowerStr === 0) {
            return false;
        } else {
            return $str;
        }
    }
}

if (!function_exists('xagio_log')) {
    function xagio_log($type = 'info', $message = '')
    {
        global $wp_filesystem;

        // Ensure the WP_Filesystem is initialized
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        // Define the log path
        $log_path = XAGIO_PATH . '/logs/xagio-' . gmdate('Y-m-d') . '.log';

        // Prepare the log message
        $log_message = '[' . gmdate('Y-m-d H:i:s') . '] [' . $type . '] ' . $message . PHP_EOL;

        // Check if log directory exists, if not create it
        $log_dir = dirname($log_path);
        if (!$wp_filesystem->is_dir($log_dir)) {
            $wp_filesystem->mkdir($log_dir);
        }

        // Append the log message to the log file
        $wp_filesystem->put_contents($log_path, $log_message, 0777);
    }
}

if (!function_exists('xagio_parse_page')) {
    function xagio_parse_page($page)
    {
        global $wp_filesystem;

        // Ensure the WP_Filesystem is initialized
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        // Check if the file exists
        if (!$wp_filesystem->exists($page)) {
            return false;
        }

        // Read the file contents
        $file_contents = $wp_filesystem->get_contents($page);
        if ($file_contents === false) {
            return false;
        }

        // Parse the file contents
        $lines = explode("\n", $file_contents);
        $data  = [];
        for ($c = 0; $c < 12 && $c < count($lines); $c++) {
            $line = trim($lines[$c]);
            if ($c > 1) {
                $line       = str_replace('* ', '', $line);
                $line_parts = explode(':', $line, 2);
                if (count($line_parts) == 2) {
                    $data[trim($line_parts[0])] = trim($line_parts[1]);
                }
            }
        }

        return $data;
    }
}

if (!function_exists('xagio_get_version')) {
    function xagio_get_version()
    {
        return XAGIO_CURRENT_VERSION;
    }
}

if (!function_exists('xagio_domain')) {
    function xagio_domain()
    {
        $domain = NULL;
        if (isset($_SERVER['SERVER_NAME'])) {
            $domain = sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME']));
        } else {
            $domain = wp_parse_url(get_site_url());
            $domain = $domain['host'];
        }
        return preg_replace('/^www\./', '', $domain);
    }
}

if (!function_exists('xagio_removeSlashes')) {
    function xagio_removeSlashes($string)
    {
        $string = implode("", explode("\\", $string));
        return stripslashes(trim($string));
    }
}

if (!function_exists('xagio_stripAllSlashes')) {
    function xagio_stripAllSlashes($value)
    {
        $value = is_array($value) ? array_map('xagio_stripAllSlashes', $value) : xagio_removeSlashes($value);

        return $value;
    }
}

if (!function_exists('xagio_stripUnwantedCharTag')) {
    function xagio_stripUnwantedCharTag($string = NULL)
    {
        $string = str_replace('"', '', trim($string ?? ''));
        return wp_strip_all_tags($string);
    }
}

if (!function_exists('xagio_string_contains')) {
    function xagio_string_contains($what, $where)
    {
        if (strpos($where, $what) !== FALSE) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}

if (!function_exists('xagio_ajax')) {
    function xagio_ajax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}

if (!function_exists('xagio_empty')) {
    function xagio_empty($value, $default = false)
    {
        return empty($value) ? $default : $value;
    }
}

if (!function_exists('xagio_has_post_parent')) {
    function xagio_has_post_parent($post = NULL)
    {
        $wp_post = get_post($post);
        return !empty($wp_post->post_parent) ? get_post($wp_post->post_parent) : NULL;
    }
}

if (!function_exists('xagio_contains')) {
    function xagio_contains($what, $where)
    {
        if (strpos($where, $what) !== FALSE) {
            return TRUE;
        }
        return FALSE;
    }
}

if (!function_exists('xagio_spintax')) {
    function xagio_spintax($text)
    {
        // Check if magic page spintax stuff
        if (xagio_contains('{spintax_', $text)) {

            preg_match_all('/\{spintax_(((?>[^\{\}]+)|(?R))*)\}/ix', $text, $matches);

            foreach ($matches[1] as $i => $match) {

                $label = $match;

                $spintaxes = get_option('_magic_page_spintax_expressions');

                foreach ($spintaxes as $spintax) {
                    if ($spintax['label'] == $label) {
                        $options = array_values($spintax['options']);
                        $text    = str_replace($matches[0][$i], $options[array_rand($options)], $text);
                        break;
                    }
                }

            }
        }

        return preg_replace_callback(
            '/\{(((?>[^\{\}]+)|(?R))*)\}/x', 'xagio_spintax_replace', do_shortcode($text)
        );
    }

    function xagio_spintax_replace($text)
    {
        $text  = xagio_spintax($text[1]);
        $parts = explode('|', $text);
        return $parts[array_rand($parts)];
    }
}

if (!function_exists('xagio_filesize')) {
    function xagio_filesize($path, $decimals = 2)
    {
        $s      = filesize($path);
        $sz     = 'BKMGTP';
        $factor = floor((strlen($s) - 1) / 3);
        return sprintf("%.{$decimals}f", $s / pow(1024, $factor)) . @$sz[$factor];
    }
}

if (!function_exists('xagio_enable_maintenance')) {
    function xagio_enable_maintenance()
    {
        global $wp_filesystem;

        // Ensure the WP_Filesystem is initialized
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        // Define the maintenance file path
        $maintenance_file = ABSPATH . '.maintenance';
        $timestamp        = time(); // Current time

        // Maintenance file content
        $maintenance_content = "<?php \$upgrading = {$timestamp}; ?>";

        // Write the maintenance content to the file
        $wp_filesystem->put_contents($maintenance_file, $maintenance_content, FS_CHMOD_FILE);
    }
}

if (!function_exists('xagio_disable_maintenance')) {
    function xagio_disable_maintenance()
    {
        $maintenance_file = ABSPATH . '.maintenance';
        if (file_exists($maintenance_file)) {
            wp_delete_file($maintenance_file);
        }
    }
}

if (!function_exists('xagio_get_term_meta')) {
    function xagio_get_term_meta($term_id)
    {
        $meta = get_term_meta($term_id);
        if (empty($meta)) {
            return [];
        }

        foreach ($meta as $key => $value) {
            $meta[$key] = $value[0];
        }

        return $meta;
    }
}

if (!function_exists('xagio_is_alternate_api')) {
    function xagio_is_alternate_api()
    {
        if (file_exists(ABSPATH . 'xagio-api.php')) {
            return TRUE;
        }

        if (file_exists(XAGIO_PATH . '/xagio-api.php')) {
            return TRUE;
        }

        return FALSE;
    }
}

if (!function_exists('xagio_removeRecursiveDir')) {

    function xagio_removeRecursiveDir($dir)
    {
        global $wp_filesystem;

        // Initialize WP_Filesystem
        if (!function_exists('WP_Filesystem')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        WP_Filesystem();

        if ($wp_filesystem->is_dir($dir)) {
            $objects = @array_diff(
                $wp_filesystem->dirlist($dir), [
                    '..',
                    '.'
                ]
            );
            foreach ($objects as $object => $info) {
                $objectPath = $dir . "/" . $object;
                if ($wp_filesystem->is_dir($objectPath)) {
                    xagio_removeRecursiveDir($objectPath);
                } else {
                    $wp_filesystem->delete($objectPath);
                }
            }
            $wp_filesystem->rmdir($dir);
        }
    }

}

