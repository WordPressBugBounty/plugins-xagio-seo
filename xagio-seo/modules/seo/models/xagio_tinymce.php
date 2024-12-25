<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_TINYMCE')) {

    class XAGIO_MODEL_TINYMCE
    {

        public static function initialize()
        {
            // Fix shitty elementor editor on frontend - don't load TinyMCE plugin when using Elementor editor
            if (isset($_GET['elementor']))
                return;
            if (isset($_GET['action'])) {
                if ($_GET['action'] == 'elementor') {
                    return;
                }
            }

            // Fix for Thrive editor (/?tve=true) when editing page
            // if tve is not set and if it's not on true, load plugin
            if (isset($_GET['tve']))
                return;

            // Check if the page is set
            if (isset($_GET['page'])) {

                // Fix for Optimize Press page builder - don't load our tinymce plugin on their Live Editor
                if ($_GET['page'] == 'optimizepress-page-builder')
                    return;

                // Again Thrive Apprentice conflict
                if ($_GET['page'] == 'tva_dashboard')
                    return;

            }


            add_filter("mce_external_plugins", [
                'XAGIO_MODEL_TINYMCE',
                'addPlugin'
            ]);
            add_filter('mce_buttons', [
                'XAGIO_MODEL_TINYMCE',
                'registerButtons'
            ]);
            add_action('admin_enqueue_scripts', [
                'XAGIO_MODEL_TINYMCE',
                'loadAssets'
            ], 10, 1);
            add_action('admin_post_xagio_pixabay_download', [
                'XAGIO_MODEL_TINYMCE',
                'pixabayDownloadImage'
            ]);

        }

        public static function loadAssets()
        {
            global $post_type;

            if ($post_type === 'page' || $post_type === 'post' || $post_type === 'product') {
                wp_localize_script('xagio_admin', 'xagio_tinymce_data', [
                        'shortcodes' => [],
                        // XAGIO_MODEL_SHORTCODES::getAllData('xag_shortcodes'),
                        'keywords'   => self::getKeywords(),
                    ]);
            }
        }

        public static function pixabayDownloadImage()
        {

            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['img'], $_POST['title'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $uploads   = wp_upload_dir();
            $image_url = sanitize_text_field(wp_unslash($_POST['img']));
            $name      = sanitize_text_field(wp_unslash($_POST['title'])) . '.jpg';

            $filename            = wp_unique_filename($uploads['path'], $name, $unique_filename_callback = NULL);
            $wp_file_type        = wp_check_filetype($filename, NULL);
            $full_path_file_name = $uploads['path'] . "/" . $filename;

            $image_string = self::fetch_image($image_url);

            $fileSaved = xagio_file_put_contents($uploads['path'] . "/" . $filename, $image_string);
            if (!$fileSaved) {
                xagio_json('error', 'Cannot save this selected image to server. Please contact support.');
            }

            $attachment = [
                'post_mime_type' => $wp_file_type['type'],
                'post_title'     => preg_replace('/\.[^.]+$/', '', $filename),
                'post_content'   => '',
                'post_status'    => 'inherit',
                'guid'           => $uploads['url'] . "/" . $filename,
            ];
            $attach_id  = wp_insert_attachment($attachment, $full_path_file_name, 0);
            if (!$attach_id) {
                xagio_json('error', 'Failed save this selected image into the database. Please contact support.');
            }
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $full_path_file_name);
            wp_update_attachment_metadata($attach_id, $attach_data);

            xagio_jsonc([
                'status'  => 'success',
                'message' => 'Image successfully downloaded.',
                'data'    => $attach_data,
                'id'      => $attach_id,
            ]);
        }

        /**
         * Function for downloading Pixabay images
         */
        public static function fetch_image($url)
        {
            if (function_exists("curl_init")) {
                return self::curl_fetch_image($url);
            } else if (ini_get("allow_url_fopen")) {
                return self::fopen_fetch_image($url);
            }
        }

        public static function curl_fetch_image($url)
        {
            $response = wp_remote_get($url, [
                'timeout' => 10,
                // Adjust the timeout as needed
            ]);

            if (is_wp_error($response)) {
                return false; // Or handle the error as needed
            }

            $image = wp_remote_retrieve_body($response);
            return $image;
        }

        public static function fopen_fetch_image($url)
        {
            $image = xagio_file_get_contents($url, FALSE);
            return $image;
        }


        public static function addPlugin($plugin_array)
        {
            global $post_type;

            if ($post_type === 'page' || $post_type === 'post' || $post_type === 'product') {
                $plugin_array['xag_keywords']     = XAGIO_URL . 'modules/seo/tinymce/keywords.js';
                $plugin_array['xagio_shortcodes'] = XAGIO_URL . 'modules/seo/tinymce/shortcodes.js';
                $plugin_array['xagio_youtube']    = XAGIO_URL . 'modules/seo/tinymce/youtube.js';
                $plugin_array['xagio_pixabay']    = XAGIO_URL . 'modules/seo/tinymce/pixabay.js';
            }

            return $plugin_array;
        }

        public static function registerButtons($buttons)
        {

            global $post_type;

            if ($post_type === 'page' || $post_type === 'post' || $post_type === 'product') {
                $buttons[] = "xag_keywords";
                $buttons[] = "xagio_shortcodes";
                $buttons[] = "xagio_youtube";
                $buttons[] = "xagio_pixabay";
            }

            return $buttons;
        }

        public static function getKeywords()
        {
            global $wpdb;
            $formatted = [];
            $data      = $wpdb->get_results(
                "
						SELECT
							xag_keywords.id,
							xag_keywords.keyword,
							xag_groups.url,
							xag_groups.group_name												
						FROM xag_keywords JOIN xag_groups ON xag_groups.id = xag_keywords.group_id
						ORDER BY xag_keywords.keyword ASC
					", ARRAY_A
            );
            if (sizeof($data) > 0) {
                foreach ($data as $d) {
                    if ($d['keyword'] != '') {
                        $d['url']                      = sanitize_title_with_dashes($d['url']);
                        $formatted[$d['group_name']][] = $d;
                    }
                }
            }

            return $formatted;
        }


    }
}