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
            global $wpdb;

            $shortcodes = $wpdb->get_results('SELECT * FROM xag_shortcodes');

            if ($post_type === 'page' || $post_type === 'post' || $post_type === 'product') {
                wp_localize_script('xagio_admin', 'xagio_tinymce_data', [
                        'shortcodes' => $shortcodes,
                        'keywords'   => self::getKeywords(),
                    ]);
            }
        }

        public static function pixabayDownloadImage()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // Block Subscribers / low-privilege users
            if ( ! is_user_logged_in() || ! current_user_can('upload_files') ) {
                xagio_json('error', 'Forbidden.');
            }

            if (empty($_POST['img']) || empty($_POST['title'])) {
                xagio_json('error', 'Required parameters are missing.');
            }

            $uploads   = wp_upload_dir();
            $image_url = esc_url_raw(wp_unslash($_POST['img']));
            $title     = sanitize_text_field(wp_unslash($_POST['title']));

            if (empty($image_url) || empty($title)) {
                xagio_json('error', 'Invalid parameters.');
            }

            // Allow Pixabay only
            $allowed_hosts = [
                'pixabay.com',
                'www.pixabay.com',
                'cdn.pixabay.com'
            ];

            $parts  = wp_parse_url($image_url);
            $host   = strtolower($parts['host'] ?? '');
            $scheme = strtolower($parts['scheme'] ?? '');

            if (!$host || !in_array($host, $allowed_hosts, true)) {
                xagio_json('error', 'Invalid image source.');
            }

            if (!in_array($scheme, ['http', 'https'], true)) {
                xagio_json('error', 'Invalid image URL.');
            }

            $xagio_name = $title . '.jpg';
            $filename   = wp_unique_filename($uploads['path'], $xagio_name, NULL);

            $wp_file_type        = wp_check_filetype($filename, NULL);
            $full_path_file_name = trailingslashit($uploads['path']) . $filename;

            $image_string = self::fetch_image($image_url);

            if ($image_string === false || $image_string === '') {
                xagio_json('error', 'Failed to download image.');
            }

            $fileSaved = xagio_file_put_contents($full_path_file_name, $image_string);
            if (!$fileSaved) {
                xagio_json('error', 'Cannot save this selected image to server. Please contact support.');
            }

            $attachment = [
                'post_mime_type' => $wp_file_type['type'] ?: 'image/jpeg',
                'post_title'     => preg_replace('/\.[^.]+$/', '', $filename),
                'post_content'   => '',
                'post_status'    => 'inherit',
                'guid'           => trailingslashit($uploads['url']) . $filename,
            ];

            $attach_id = wp_insert_attachment($attachment, $full_path_file_name, 0);
            if (!$attach_id) {
                wp_delete_file($full_path_file_name);
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
        public static function fetch_image($xagio_url)
        {
            if (function_exists("curl_init")) {
                return self::curl_fetch_image($xagio_url);
            }

            return false;
        }

        public static function curl_fetch_image($xagio_url)
        {
            $resp = wp_safe_remote_get($xagio_url, [
                'timeout' => 10,
                'redirection' => 2,
                'headers' => ['Accept' => 'image/*'],
            ]);

            if (is_wp_error($resp)) {
                return false;
            }

            $content_type = wp_remote_retrieve_header($resp, 'content-type');
            if (!$content_type || stripos($content_type, 'image/') !== 0) {
                return false;
            }

            return wp_remote_retrieve_body($resp);
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

                    if (!isset($d['url'])) {
                        $d['url'] = '';
                    }

                    if ($d['keyword'] != '') {
                        $d['url']                      = isset($d['url']) ? sanitize_title_with_dashes($d['url']) : '';
                        $formatted[$d['group_name']][] = $d;
                    }
                }
            }

            return $formatted;
        }


    }
}