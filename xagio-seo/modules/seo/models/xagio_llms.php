<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('XAGIO_MODEL_LLMS')) {

    class XAGIO_MODEL_LLMS
    {
        const OPTION_ENABLED    = 'XAGIO_LLMS_ENABLED';
        const OPTION_INTRO      = 'XAGIO_LLMS_INTRO';
        const OPTION_POST_TYPES = 'XAGIO_LLMS_POST_TYPES';
        const OPTION_INCLUDE_SM = 'XAGIO_LLMS_INCLUDE_SITEMAP';
        const OPTION_MAX_ITEMS  = 'XAGIO_LLMS_MAX_ITEMS';
        const OPTION_CACHED_TXT = 'XAGIO_LLMS_TXT';
        const OPTION_REWRITE    = 'XAGIO_LLMS_REWRITE_READY_V2';
        const OPTION_CLEANED    = 'XAGIO_LLMS_DISK_CLEANED';
        const QUERY_VAR         = 'xagio_llms';
        const FILE_NAME         = 'llms.txt';

        public static function initialize()
        {
            add_action('init', ['XAGIO_MODEL_LLMS', 'addRewrite']);
            add_filter('query_vars', ['XAGIO_MODEL_LLMS', 'registerQueryVar']);
            add_action('template_redirect', ['XAGIO_MODEL_LLMS', 'maybeServeLlms']);
            add_action('send_headers', ['XAGIO_MODEL_LLMS', 'sendLinkHeader']);

            if (!XAGIO_HAS_ADMIN_PERMISSIONS) return;

            add_action('admin_post_xagio_llms_save', ['XAGIO_MODEL_LLMS', 'saveLlmsSettings']);
            add_action('admin_post_xagio_llms_get',  ['XAGIO_MODEL_LLMS', 'getLlmsContent']);
        }

        public static function addRewrite()
        {
            add_rewrite_rule('^llms\.txt$', 'index.php?' . self::QUERY_VAR . '=1', 'top');

            if (!get_option(self::OPTION_REWRITE)) {
                flush_rewrite_rules(false);
                update_option(self::OPTION_REWRITE, 1, false);
            }

            if (!get_option(self::OPTION_CLEANED)) {
                self::deleteDiskFile();
                self::cleanupLegacyOptions();
                update_option(self::OPTION_CLEANED, 1, false);
            }
        }

        private static function cleanupLegacyOptions()
        {
            $legacy_txt = get_option(self::OPTION_CACHED_TXT);
            if (is_string($legacy_txt) && stripos(ltrim($legacy_txt), 'User-Agent:') === 0) {
                delete_option(self::OPTION_CACHED_TXT);
            }

            delete_option('XAGIO_LLMS_CONFIG');
            delete_option('XAGIO_LLMS_REWRITE_READY');
        }

        public static function diskFilePath()
        {
            return trailingslashit(ABSPATH) . self::FILE_NAME;
        }

        public static function diskFileExists()
        {
            return file_exists(self::diskFilePath());
        }

        public static function deleteDiskFile()
        {
            $target = self::diskFilePath();
            if (!file_exists($target)) return true;

            wp_delete_file($target);
            return !file_exists($target);
        }

        public static function registerQueryVar($vars)
        {
            $vars[] = self::QUERY_VAR;
            return $vars;
        }

        public static function sendLinkHeader()
        {
            if (is_admin() || is_feed() || is_robots()) return;
            if (headers_sent()) return;
            if (get_option(self::OPTION_ENABLED) !== '1') return;

            // Don't advertise llms.txt on the llms.txt response itself.
            $request_uri = isset($_SERVER['REQUEST_URI'])
                ? wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH)
                : '';
            if (is_string($request_uri) && trim($request_uri, '/') === self::FILE_NAME) return;

            $url = esc_url_raw(home_url('/' . self::FILE_NAME));
            header('Link: <' . $url . '>; rel="describedby"; type="text/markdown"', false);
        }

        public static function maybeServeLlms()
        {
            $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
            $matched_path = ($request_uri && trim($request_uri, '/') === self::FILE_NAME);
            $matched_query = (intval(get_query_var(self::QUERY_VAR)) === 1);

            if (!$matched_query && !$matched_path) return;

            if (get_option(self::OPTION_ENABLED) !== '1') {
                global $wp_query;
                if ($wp_query) $wp_query->set_404();
                status_header(404);
                nocache_headers();
                header('X-Xagio-LLMS: disabled');
                return;
            }

            $txt = self::generateMarkdown();

            nocache_headers();
            status_header(200);
            header('Content-Type: text/markdown; charset=UTF-8');
            header('X-Robots-Tag: noindex');
            header('X-Xagio-LLMS: served');
            // Output is a raw text/markdown file body. HTML escaping would corrupt markdown syntax
            // (blockquote '>', URLs with '&'). Input is already sanitized by cleanInline()/esc_url_raw().
            echo $txt; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }

        public static function getDefaultConfig()
        {
            return [
                'enabled'          => '0',
                'intro'            => '',
                'post_types'       => ['page' => 1, 'post' => 1],
                'include_sitemap'  => '1',
                'max_items'        => 100,
            ];
        }

        public static function getConfig()
        {
            $defaults = self::getDefaultConfig();
            return [
                'enabled'         => (string) get_option(self::OPTION_ENABLED, $defaults['enabled']),
                'intro'           => (string) get_option(self::OPTION_INTRO, $defaults['intro']),
                'post_types'      => (array)  get_option(self::OPTION_POST_TYPES, $defaults['post_types']),
                'include_sitemap' => (string) get_option(self::OPTION_INCLUDE_SM, $defaults['include_sitemap']),
                'max_items'       => (int)    get_option(self::OPTION_MAX_ITEMS, $defaults['max_items']),
            ];
        }

        public static function generateMarkdown($cfg = null)
        {
            if (!is_array($cfg)) {
                $cfg = self::getConfig();
            } else {
                $cfg = array_merge(self::getDefaultConfig(), $cfg);
            }

            $site_title = wp_strip_all_tags(get_bloginfo('name'));
            $tagline    = wp_strip_all_tags(get_bloginfo('description'));
            $intro      = trim((string) $cfg['intro']);
            if ($intro === '') {
                $intro = $tagline;
            }

            $lines = [];
            $lines[] = '# ' . $site_title;
            if ($intro !== '') {
                foreach (preg_split('/\r\n|\r|\n/', $intro) as $intro_line) {
                    $lines[] = '> ' . $intro_line;
                }
            }
            $lines[] = '';

            $max_items = max(1, min(1000, (int) $cfg['max_items']));
            $post_types = array_keys(array_filter((array) $cfg['post_types']));

            if (!empty($post_types)) {
                foreach ($post_types as $post_type) {
                    if (!post_type_exists($post_type)) continue;

                    $items = self::collectPostsForType($post_type, $max_items);
                    if (empty($items)) continue;

                    $heading = self::postTypeHeading($post_type);
                    $lines[] = '## ' . $heading;

                    foreach ($items as $item) {
                        $title = $item['title'] !== '' ? $item['title'] : $item['url'];
                        $row   = '- [' . self::escapeMarkdownText($title) . '](' . $item['url'] . ')';
                        if ($item['description'] !== '') {
                            $row .= ': ' . self::escapeMarkdownText($item['description']);
                        }
                        $lines[] = $row;
                    }
                    $lines[] = '';
                }
            }

            if ($cfg['include_sitemap'] === '1' && defined('XAGIO_ENABLE_SITEMAPS') && XAGIO_ENABLE_SITEMAPS) {
                $lines[] = '## Key resources';
                $lines[] = '- [Sitemap](' . esc_url(home_url('/sitemap-xag.xml')) . ')';
                $lines[] = '';
            }

            $output = implode("\n", $lines);
            $output = preg_replace("/\n{3,}/", "\n\n", $output);
            $output = rtrim($output) . "\n";

            return $output;
        }

        private static function collectPostsForType($post_type, $max_items)
        {
            $items = [];

            $posts = get_posts([
                'post_type'      => $post_type,
                'post_status'    => 'publish',
                'posts_per_page' => $max_items,
                'orderby'        => 'menu_order title',
                'order'          => 'ASC',
                'suppress_filters' => true,
            ]);

            if (empty($posts)) return $items;

            usort($posts, function ($a, $b) {
                return strlen(get_permalink($a->ID)) - strlen(get_permalink($b->ID));
            });

            foreach ($posts as $post) {
                $url = get_permalink($post->ID);
                if (!$url) continue;

                $title = get_post_meta($post->ID, 'XAGIO_SEO_TITLE', true);
                if (!is_string($title) || trim($title) === '') {
                    $title = $post->post_title;
                }
                $title = self::cleanInline($title);

                $description = get_post_meta($post->ID, 'XAGIO_SEO_DESCRIPTION', true);
                if (!is_string($description) || trim($description) === '') {
                    $description = $post->post_excerpt;
                }
                if (!is_string($description) || trim($description) === '') {
                    $description = wp_trim_words(wp_strip_all_tags($post->post_content), 30, '...');
                }
                $description = self::cleanInline($description);

                $items[] = [
                    'url'         => esc_url_raw($url),
                    'title'       => $title,
                    'description' => $description,
                ];
            }

            return $items;
        }

        private static function postTypeHeading($post_type)
        {
            $obj = get_post_type_object($post_type);
            if ($obj && !empty($obj->labels->name)) {
                return $obj->labels->name;
            }
            return ucfirst($post_type);
        }

        private static function cleanInline($text)
        {
            $text = wp_strip_all_tags((string) $text);
            $text = preg_replace('/\s+/u', ' ', $text);
            return trim($text);
        }

        private static function escapeMarkdownText($text)
        {
            $text = (string) $text;
            $text = str_replace(["\r", "\n"], ' ', $text);
            $text = str_replace(['[', ']'], ['\[', '\]'], $text);
            return trim($text);
        }

        public static function getLlmsContent()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');
            xagio_json('success', '', self::generateMarkdown());
        }

        public static function saveLlmsSettings()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $mode = isset($_POST['mode']) ? sanitize_text_field(wp_unslash($_POST['mode'])) : 'save';

            if ($mode === 'reset') {
                delete_option(self::OPTION_ENABLED);
                delete_option(self::OPTION_INTRO);
                delete_option(self::OPTION_POST_TYPES);
                delete_option(self::OPTION_INCLUDE_SM);
                delete_option(self::OPTION_MAX_ITEMS);
                self::deleteDiskFile();
                $txt = self::generateMarkdown();
                update_option(self::OPTION_CACHED_TXT, $txt, false);
                xagio_json('success', 'llms.txt reset to default.', $txt);
                return;
            }

            $cfg = self::parsePostedConfig($_POST);

            if ($mode === 'preview') {
                $txt = self::generateMarkdown($cfg);
                xagio_json('success', '', $txt);
                return;
            }

            update_option(self::OPTION_ENABLED, $cfg['enabled']);
            update_option(self::OPTION_INTRO, $cfg['intro']);
            update_option(self::OPTION_POST_TYPES, $cfg['post_types']);
            update_option(self::OPTION_INCLUDE_SM, $cfg['include_sitemap']);
            update_option(self::OPTION_MAX_ITEMS, $cfg['max_items']);

            self::deleteDiskFile();

            $txt = self::generateMarkdown($cfg);
            update_option(self::OPTION_CACHED_TXT, $txt, false);

            xagio_json('success', 'llms.txt settings saved.', $txt);
        }

        private static function parsePostedConfig($post)
        {
            $enabled         = isset($post[self::OPTION_ENABLED]) ? (intval($post[self::OPTION_ENABLED]) ? '1' : '0') : '0';
            $intro           = isset($post[self::OPTION_INTRO]) ? sanitize_textarea_field(wp_unslash($post[self::OPTION_INTRO])) : '';
            $include_sitemap = isset($post[self::OPTION_INCLUDE_SM]) ? (intval($post[self::OPTION_INCLUDE_SM]) ? '1' : '0') : '0';
            $max_items       = isset($post[self::OPTION_MAX_ITEMS]) ? max(1, min(1000, intval($post[self::OPTION_MAX_ITEMS]))) : 100;

            $post_types = [];
            if (isset($post[self::OPTION_POST_TYPES]) && is_array($post[self::OPTION_POST_TYPES])) {
                foreach ((array) $post[self::OPTION_POST_TYPES] as $pt => $on) {
                    $pt_clean = sanitize_key($pt);
                    if ($pt_clean !== '' && post_type_exists($pt_clean) && intval($on)) {
                        $post_types[$pt_clean] = 1;
                    }
                }
            }

            return [
                'enabled'         => $enabled,
                'intro'           => $intro,
                'include_sitemap' => $include_sitemap,
                'max_items'       => $max_items,
                'post_types'      => $post_types,
            ];
        }
    }
}
