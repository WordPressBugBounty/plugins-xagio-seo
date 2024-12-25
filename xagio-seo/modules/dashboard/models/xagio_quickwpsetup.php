<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_QUICKWPSETUP')) {

    class XAGIO_MODEL_QUICKWPSETUP
    {

        public static function initialize()
        {
            if (!XAGIO_HAS_ADMIN_PERMISSIONS) return;

            add_action('admin_post_xagio_search_wp_api', [
                'XAGIO_MODEL_QUICKWPSETUP',
                'search_WP_API'
            ]);
            add_action('admin_post_xagio_fs_perform', [
                'XAGIO_MODEL_QUICKWPSETUP',
                'performFreshStart'
            ]);
        }

        public static function performFreshStart()
        {
            // Sanitize and process input data
            $options = [
                'fs_remove_pages'                     => sanitize_text_field(filter_input(INPUT_POST, 'fs_remove_pages', FILTER_VALIDATE_INT)),
                'fs_remove_posts'                     => sanitize_text_field(filter_input(INPUT_POST, 'fs_remove_posts', FILTER_VALIDATE_INT)),
                'fs_permalinks'                       => sanitize_text_field(filter_input(INPUT_POST, 'fs_permalinks', FILTER_VALIDATE_INT)),
                'fs_remove_comments'                  => sanitize_text_field(filter_input(INPUT_POST, 'fs_remove_comments', FILTER_VALIDATE_INT)),
                'fs_disable_comment_notifications'    => sanitize_text_field(filter_input(INPUT_POST, 'fs_disable_comment_notifications', FILTER_VALIDATE_INT)),
                'fs_disable_comment_moderation'       => sanitize_text_field(filter_input(INPUT_POST, 'fs_disable_comment_moderation', FILTER_VALIDATE_INT)),
                'fs_create_aboutus'                   => sanitize_text_field(filter_input(INPUT_POST, 'fs_create_aboutus', FILTER_VALIDATE_INT)),
                'fs_create_privacypolicy'             => sanitize_text_field(filter_input(INPUT_POST, 'fs_create_privacypolicy', FILTER_VALIDATE_INT)),
                'fs_create_termsofuse'                => sanitize_text_field(filter_input(INPUT_POST, 'fs_create_termsofuse', FILTER_VALIDATE_INT)),
                'fs_create_earningsdisclaimer'        => sanitize_text_field(filter_input(INPUT_POST, 'fs_create_earningsdisclaimer', FILTER_VALIDATE_INT)),
                'fs_create_contactus'                 => sanitize_text_field(filter_input(INPUT_POST, 'fs_create_contactus', FILTER_VALIDATE_INT)),
                'fs_create_amazonassociatedisclosure' => sanitize_text_field(filter_input(INPUT_POST, 'fs_create_amazonassociatedisclosure', FILTER_VALIDATE_INT)),
                'fs_create_affiliatedisclosure'       => sanitize_text_field(filter_input(INPUT_POST, 'fs_create_affiliatedisclosure', FILTER_VALIDATE_INT)),
                'fs_create_copyright'                 => sanitize_text_field(filter_input(INPUT_POST, 'fs_create_copyright', FILTER_VALIDATE_INT)),
                'fs_create_antispam'                  => sanitize_text_field(filter_input(INPUT_POST, 'fs_create_antispam', FILTER_VALIDATE_INT)),
                'fs_create_medicaldisclaimer'         => sanitize_text_field(filter_input(INPUT_POST, 'fs_create_medicaldisclaimer', FILTER_VALIDATE_INT)),
                'fs_create_categories'                => sanitize_text_field(filter_input(INPUT_POST, 'fs_create_categories', FILTER_VALIDATE_INT)),
                'fs_create_categories_list'           => array_map('sanitize_text_field', (array) filter_input(INPUT_POST, 'fs_create_categories_list', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY)),
                'fs_create_blank_pages'               => sanitize_text_field(filter_input(INPUT_POST, 'fs_create_blank_pages', FILTER_VALIDATE_INT)),
                'fs_create_blank_pages_list'          => array_map('sanitize_text_field', (array) filter_input(INPUT_POST, 'fs_create_blank_pages_list', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY)),
                'fs_create_blank_posts'               => sanitize_text_field(filter_input(INPUT_POST, 'fs_create_blank_posts', FILTER_VALIDATE_INT)),
                'fs_create_blank_posts_list'          => array_map('sanitize_text_field', (array) filter_input(INPUT_POST, 'fs_create_blank_posts_list', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY)),
                'fs_remove_plugins'                   => sanitize_text_field(filter_input(INPUT_POST, 'fs_remove_plugins', FILTER_VALIDATE_INT)),
                'fs_remove_themes'                    => sanitize_text_field(filter_input(INPUT_POST, 'fs_remove_themes', FILTER_VALIDATE_INT)),
                'fs_plugins'                          => sanitize_text_field(filter_input(INPUT_POST, 'fs_plugins', FILTER_SANITIZE_STRING)),
                'fs_themes'                           => sanitize_text_field(filter_input(INPUT_POST, 'fs_themes', FILTER_SANITIZE_STRING)),
            ];


            // Remove default plugins
            if ($options['fs_remove_plugins']) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';

                @deactivate_plugins([
                    'hello-dolly/hello.php',
                    'akismet/akismet.php'
                ]);
                @delete_plugins([
                    'hello-dolly/hello.php',
                    'akismet/akismet.php'
                ]);
            }

            // Remove default themes
            if ($options['fs_remove_themes']) {
                require_once ABSPATH . 'wp-admin/includes/theme.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';

                $themes_to_remove = [
                    'twentyfifteen',
                    'twentysixteen'
                ];
                foreach (wp_get_themes() as $theme) {
                    if (in_array($theme->get_template(), $themes_to_remove)) {
                        @delete_theme($theme->get_stylesheet());
                    }
                }
            }

            // Remove pages
            if ($options['fs_remove_pages']) {
                global $wpdb;
                $wpdb->query("DELETE FROM $wpdb->posts WHERE post_type = 'page'");
            }

            // Remove posts
            if ($options['fs_remove_posts']) {
                global $wpdb;
                $wpdb->query("DELETE FROM $wpdb->posts WHERE post_type = 'post'");
            }

            // Set permalinks
            if ($options['fs_permalinks']) {
                global $wp_rewrite;
                $wp_rewrite->set_permalink_structure('/%postname%/');
                $wp_rewrite->flush_rules();
            }

            // Remove comments
            if ($options['fs_remove_comments']) {
                foreach (get_comments() as $comment) {
                    wp_delete_comment($comment->comment_ID);
                }
            }

            // Disable comment notifications
            if ($options['fs_disable_comment_notifications']) {
                update_option('comments_notify', 0);
            }

            // Disable comment moderation notifications
            if ($options['fs_disable_comment_moderation']) {
                update_option('moderation_notify', 0);
            }

            // Create pages
            $pages_to_create = [
                'fs_create_aboutus'                   => 'About Us',
                'fs_create_privacypolicy'             => 'Privacy Policy',
                'fs_create_termsofuse'                => 'Terms of Use',
                'fs_create_earningsdisclaimer'        => 'Earnings Disclaimer',
                'fs_create_contactus'                 => 'Contact Us',
                'fs_create_amazonassociatedisclosure' => 'Amazon Associates Disclosure',
                'fs_create_affiliatedisclosure'       => 'Affiliate Disclosure',
                'fs_create_copyright'                 => 'Copyright Notice',
                'fs_create_antispam'                  => 'Anti Spam Policy',
                'fs_create_medicaldisclaimer'         => 'Medical Disclaimer',
            ];

            foreach ($pages_to_create as $key => $title) {
                if ($options[$key]) {
                    wp_insert_post([
                        'post_type'   => 'page',
                        'post_title'  => $title,
                        'post_status' => 'publish',
                        'post_author' => get_current_user_id(),
                        'post_name'   => sanitize_title($title),
                    ]);
                }
            }

            // Create categories
            if ($options['fs_create_categories'] && is_array($options['fs_create_categories_list'])) {
                foreach ($options['fs_create_categories_list'] as $category) {
                    wp_create_category(sanitize_text_field($category));
                }
            }

            // Create blank pages
            if ($options['fs_create_blank_pages'] && is_array($options['fs_create_blank_pages_list'])) {
                foreach ($options['fs_create_blank_pages_list'] as $page) {
                    wp_insert_post([
                        'post_type'   => 'page',
                        'post_title'  => sanitize_text_field($page),
                        'post_status' => 'publish',
                        'post_author' => get_current_user_id(),
                        'post_name'   => sanitize_title($page),
                    ]);
                }
            }

            // Create blank posts
            if ($options['fs_create_blank_posts'] && is_array($options['fs_create_blank_posts_list'])) {
                foreach ($options['fs_create_blank_posts_list'] as $post) {
                    wp_insert_post([
                        'post_type'   => 'post',
                        'post_title'  => sanitize_text_field($post),
                        'post_status' => 'publish',
                        'post_author' => get_current_user_id(),
                        'post_name'   => sanitize_title($post),
                    ]);
                }
            }

            // Download and install plugins
            $errors = [];
            if (!empty($options['fs_plugins'])) {
                $plugins = explode(',', $options['fs_plugins']);
                foreach ($plugins as $plugin) {
                    $plugin      = sanitize_text_field($plugin);
                    $plugin_path = self::downloadWordPressPlugin($plugin);
                    if (!$plugin_path) {
                        $errors[] = [
                            'type' => 'plugin',
                            'name' => $plugin
                        ];
                    } else {
                        $result = self::installWordPressPlugin($plugin, $plugin_path);
                        if (!$result) {
                            $errors[] = [
                                'type' => 'plugin',
                                'name' => $plugin
                            ];
                        } else {
                            @activate_plugin($result);
                        }
                    }
                }
            }

            // Download and install themes
            if (!empty($options['fs_themes'])) {
                $themes = explode(',', $options['fs_themes']);
                foreach ($themes as $theme) {
                    $theme      = sanitize_text_field($theme);
                    $theme_path = self::downloadWordPressTheme($theme);
                    if (!$theme_path) {
                        $errors[] = [
                            'type' => 'theme',
                            'name' => $theme
                        ];
                    } else {
                        $result = self::installWordPressTheme($theme, $theme_path);
                        if (!$result) {
                            $errors[] = [
                                'type' => 'theme',
                                'name' => $theme
                            ];
                        }
                    }
                }
            }

            // Send output
            if (!empty($errors)) {
                wp_send_json([
                    'status' => 'error',
                    'data'   => $errors
                ]);
            } else {
                wp_send_json([
                    'status' => 'success',
                    'backup' => $backup['data'] ?? null
                ]);
            }
        }


        public static function installWordPressPlugin($plugin_name, $path, &$error = 'none')
        {
            $plugins_directory = str_replace($plugin_name . '.zip', '', $path) . 'wp-content/plugins';

            if (!class_exists('ZipArchive')) {

                $error = 'ZipArchive is not installed.';
                return FALSE;

            } else {

                $zip = new xagio_ZipArchiveX();
                if ($zip->open($path) === TRUE) {

                    $folderName = @$zip->getNameIndex(0);
                    if (!empty($folderName)) {
                        $zip->extractTo($plugins_directory);
                        $zip->close();
                        wp_delete_file($path);

                        $folder = $plugins_directory . '/' . $folderName;
                        if (is_dir($folder)) {
                            chdir($plugins_directory . '/' . $folderName);
                            foreach (glob("*.php") as $filename) {
                                $content = xagio_file_get_contents($filename);
                                if (strpos($content, 'Plugin Name:') !== FALSE) {
                                    return $folderName . $filename;
                                }
                            }
                        }
                        return $folderName;
                    } else {
                        $error = 'Failed to find proper folder structure.';
                        return FALSE;
                    }
                } else {
                    $error = 'Failed to open Zip archive.';
                    return FALSE;
                }

            }
        }

        public static function installWordPressTheme($slug, $path, &$error = 'none')
        {
            $themes_directory = str_replace($slug . '.zip', '', $path) . 'wp-content/themes';

            if (!class_exists('ZipArchive')) {

                $error = 'ZipArchive is not installed.';
                return FALSE;

            } else {

                $zip = new xagio_ZipArchiveX();
                if ($zip->open($path) === TRUE) {
                    $zip->extractTo($themes_directory);
                    $zip->close();
                    wp_delete_file($path);
                    return TRUE;
                } else {
                    $error = 'Failed to open Zip archive.';
                    return FALSE;
                }

            }

        }

        public static function downloadWordPressPlugin($slug)
        {
            include_once ABSPATH . 'wp-admin/includes/file.php';

            $link           = 'https://downloads.wordpress.org/plugin/' . $slug . '.zip';
            $root_directory = get_home_path();
            $plugin_path    = $root_directory . $slug . '.zip';

            $asOptions = [
                'method'      => 'POST',
                'timeout'     => 30,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'sslverify'   => false,
            ];

            $data = wp_remote_get($link, $asOptions);
            if (is_wp_error($data)) {
                return false;
            }
            $data = $data['body'];

            // Initialize the WP Filesystem
            global $wp_filesystem;
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            $creds = request_filesystem_credentials(site_url() . '/wp-admin/', '', false, false, []);
            if (!WP_Filesystem($creds)) {
                return false;
            }

            // Use WP_Filesystem to write the file
            if (!$wp_filesystem->put_contents($plugin_path, $data, FS_CHMOD_FILE)) {
                return false;
            }

            return $plugin_path;
        }

        public static function downloadWordPressTheme($slug)
        {
            include_once ABSPATH . 'wp-admin/includes/file.php';

            $link           = 'https://downloads.wordpress.org/theme/' . $slug . '.zip';
            $root_directory = get_home_path();
            $theme_path     = $root_directory . $slug . '.zip';

            $asOptions = [
                'method'      => 'GET',
                'timeout'     => 30,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'sslverify'   => false,
            ];
            $response  = wp_remote_get($link, $asOptions);
            if (is_wp_error($response)) {
                return false;
            }
            $data = wp_remote_retrieve_body($response);

            // Initialize the WP Filesystem
            global $wp_filesystem;
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            $creds = request_filesystem_credentials(site_url() . '/wp-admin/', '', false, false, []);
            if (!WP_Filesystem($creds)) {
                return false;
            }

            // Use WP_Filesystem to write the file
            if (!$wp_filesystem->put_contents($theme_path, $data, FS_CHMOD_FILE)) {
                return false;
            }

            return $theme_path;
        }


        public static function search_WP_API($return = false)
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $type   = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
            $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';

            if (empty($type) || empty($search)) {
                wp_send_json_error('Invalid search parameters.');
                return;
            }

            $result = self::wp_api_search($type, 'query_' . $type, $search);

            if ($return) {
                return $result;
            } else {
                wp_send_json($result);
            }
        }


        private static function wp_api_search($type, $action, $search)
        {
            $url = 'http://api.wordpress.org/' . $type . '/info/1.2/?action=' . $action . '&request[per_page]=36&request[search]=' . urlencode($search);
            if ($ssl = wp_http_supports(['ssl']))
                $url = set_url_scheme($url, 'https');

            $result = wp_remote_get($url);

            if (is_wp_error($result)) {
                return 'error';
            }

            return json_decode($result['body'], true);
        }

    }
}
