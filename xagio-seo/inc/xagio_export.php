<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * XAGIO_EXPORT: what the Xagio panel needs to convert this site to static HTML (the WP -> HTML converter,
 * contract in the office design document §5, export_version 1).
 *
 * Reached only through the panel API route (XAGIO_API::exportSite / exportFile), which lets a super admin in
 * (restPermissionCheck): the same people who can already read every option of this site. The answers carry
 * form recipients, so nothing here is ever logged or cached, and no CAPTCHA secret, SMTP password or API key
 * is ever part of them (only a CAPTCHA's type and public site key).
 *
 * Every list is paged with an opaque cursor so a big site never needs one long request. Nothing here changes
 * the site, except two things the panel asks for on purpose: `prepare` (clears builder and page caches, changes
 * no setting) and `crawl_pass` (stores a short-lived per-job secret in a transient).
 */
if (!class_exists('XAGIO_EXPORT')) {

    class XAGIO_EXPORT
    {
        const VERSION        = 1;
        const DEFAULT_LIMIT  = 200;
        const MAX_LIMIT      = 500;
        const MAX_CHUNK      = 8388608;   // 8 MB per exportFile call
        const CLOCK_SKEW     = 300;       // seconds a signed request or crawl header may be off
        const MAX_PASS_TTL   = 86400;
        const HASH_MAX       = 33554432;  // exportFile sends the sha1 of files up to 32 MB (first chunk); bigger ones are checked by size
        const PASS_TRANSIENT = 'xagio_crawl_pass_';
        const SECTIONS       = ['capabilities', 'site', 'urls', 'redirects', 'seo', 'forms', 'comments', 'menus', 'uploads', 'prepare', 'crawl_pass'];

        public static function initialize()
        {
            // The crawler pass is checked as early as this plugin runs (5.5 of the contract).
            add_action('plugins_loaded', ['XAGIO_EXPORT', 'applyCrawlPass'], 0);
        }

        /* ==============================================================================================
         *  Entry points (called by XAGIO_API after restPermissionCheck)
         * ============================================================================================== */

        public static function handle($request)
        {
            $section = sanitize_key((string) $request->get_param('section'));
            if (!in_array($section, self::SECTIONS, true)) {
                self::emit('error', 'Unknown export section.', ['export_version' => self::VERSION, 'sections' => self::SECTIONS], $section);
            }

            $limit  = (int) $request->get_param('limit');
            // uploads stats every file and checks its folders: a smaller default page keeps slow hosts quick
            $limit  = $limit > 0 ? min($limit, self::MAX_LIMIT) : ($section === 'uploads' ? 100 : self::DEFAULT_LIMIT);
            $cursor = self::decodeCursor((string) $request->get_param('cursor'));

            // Long sites, slow hosts: let the page finish.
            if (function_exists('set_time_limit')) {
                @set_time_limit(120);
            }

            switch ($section) {
                case 'capabilities':
                    $out = ['item' => self::capabilities()];
                    break;
                case 'site':
                    $out = ['item' => self::site()];
                    break;
                case 'urls':
                    $out = self::urls($cursor, $limit);
                    break;
                case 'redirects':
                    $out = self::redirects($cursor, $limit);
                    break;
                case 'seo':
                    $out = self::seo($cursor, $limit);
                    break;
                case 'forms':
                    $out = ['items' => self::forms(), 'next_cursor' => null];
                    break;
                case 'comments':
                    $out = self::comments($cursor, $limit);
                    break;
                case 'menus':
                    $out = ['items' => self::menus(), 'next_cursor' => null];
                    break;
                case 'uploads':
                    $out = self::uploads($cursor, $limit);
                    break;
                case 'prepare':
                    $out = ['item' => self::prepare()];
                    break;
                case 'crawl_pass':
                    $out = ['item' => self::crawlPass((string) $request->get_param('job'), (int) $request->get_param('ttl'))];
                    break;
                default:
                    $out = [];
            }

            if (isset($out['error']) || isset($out['item']['error'])) {
                $out['error'] = $out['error'] ?? $out['item']['error'];
                self::emit('error', $out['error'], ['export_version' => self::VERSION], $section, $request);
            }

            $data = ['export_version' => self::VERSION, 'section' => $section] + $out;
            if (array_key_exists('next_cursor', $data) && is_array($data['next_cursor'])) {
                $data['next_cursor'] = self::encodeCursor($data['next_cursor']);
            }
            self::emit('success', 'Export section ' . $section . '.', $data, $section, $request);
        }

        /** Raw bytes of one upload, or of a range of it (5.4). */
        public static function handleFile($request)
        {
            $file = self::resolveUpload((string) $request->get_param('path'));
            if ($file === null) {
                self::emit('error', 'File not found in the uploads directory.', ['export_version' => self::VERSION], 'file', $request);
            }

            $size   = (int) filesize($file);
            $offset = max(0, (int) $request->get_param('offset'));
            $length = (int) $request->get_param('length');
            $length = $length > 0 ? min($length, self::MAX_CHUNK) : self::MAX_CHUNK;
            if ($offset > $size) {
                self::emit('error', 'Offset is past the end of the file.', ['export_version' => self::VERSION, 'size' => $size], 'file', $request);
            }

            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
            $fh = fopen($file, 'rb');
            if (!$fh) {
                self::emit('error', 'The file cannot be read.', ['export_version' => self::VERSION], 'file', $request);
            }
            fseek($fh, $offset);
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
            $chunk = $length > 0 ? (string) fread($fh, $length) : '';
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            fclose($fh);

            self::noCache();
            header('Content-Type: application/octet-stream');
            header('Content-Length: ' . strlen($chunk));
            header('X-Xagio-Size: ' . $size);
            if ($offset === 0 && $size <= self::HASH_MAX) {
                header('X-Xagio-Sha1: ' . sha1_file($file));   // once per file, on its first chunk
            }
            header('X-Xagio-Offset: ' . $offset);
            self::signatureHeader($request, 'file', $chunk);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw file bytes for the panel, not HTML
            echo $chunk;
            exit;
        }

        /* ==============================================================================================
         *  Output, cursor, signature
         * ============================================================================================== */

        /** The plugin's usual {status, message, data} envelope, built here so the signature covers the exact bytes. */
        private static function emit($status, $message, $data, $section, $request = null)
        {
            $data           = (array) $data;
            $signable       = $request !== null && self::signingParams($request) !== null && self::licenseKey() !== '';
            $data['signed'] = $signable;
            $body           = wp_json_encode(['status' => $status, 'message' => $message, 'data' => $data]);

            self::noCache();
            header('Content-Type: application/json; charset=' . get_option('blog_charset'));
            if ($signable) {
                self::signatureHeader($request, $section, (string) $body);
            }
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a JSON document, not HTML
            echo $body;
            exit;
        }

        private static function noCache()
        {
            nocache_headers();
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('X-Robots-Tag: noindex');
        }

        private static function licenseKey()
        {
            return defined('XAGIO_LICENSE_KEY') && is_string(XAGIO_LICENSE_KEY) ? XAGIO_LICENSE_KEY : '';
        }

        /** [nonce, ts] when the panel asked for a signature and the request is fresh, else null. */
        private static function signingParams($request)
        {
            $nonce = (string) $request->get_param('nonce');
            $ts    = (int) $request->get_param('ts');
            if (!preg_match('/^[A-Za-z0-9_-]{16,64}$/', $nonce) || $ts <= 0 || abs(time() - $ts) > self::CLOCK_SKEW) {
                return null;
            }
            return [$nonce, $ts];
        }

        /** Public: the panel side (and Test harnesses) use the same message layout. */
        public static function signature($key, $domain, $nonce, $ts, $section, $body)
        {
            return hash_hmac('sha256', $domain . '|' . $nonce . '|' . $ts . '|' . $section . '|' . hash('sha256', $body), $key);
        }

        private static function signatureHeader($request, $section, $body)
        {
            $params = $request !== null ? self::signingParams($request) : null;
            $key    = self::licenseKey();
            if ($params === null || $key === '') {
                return;
            }
            // the domain lower-cased: the panel keeps domains in lower case (XAGIO_DOMAIN is the stored host as is)
            header('X-Xagio-Signature: ' . self::signature($key, strtolower(XAGIO_DOMAIN), $params[0], $params[1], $section, $body));
        }

        private static function encodeCursor(array $state)
        {
            return rtrim(strtr(base64_encode((string) wp_json_encode($state)), '+/', '-_'), '=');
        }

        private static function decodeCursor($cursor)
        {
            if ($cursor === '') {
                return [];
            }
            $json  = base64_decode(strtr($cursor, '-_', '+/'), true);
            $state = $json !== false ? json_decode($json, true) : null;
            return is_array($state) ? $state : [];
        }

        /* ==============================================================================================
         *  capabilities, site
         * ============================================================================================== */

        private static function capabilities()
        {
            return [
                'export_version' => self::VERSION,
                'plugin_version' => XAGIO_CURRENT_VERSION,
                'sections'       => self::SECTIONS,
                'file_endpoint'  => 'exportFile',
                'limits'         => ['page_default' => self::DEFAULT_LIMIT, 'page_max' => self::MAX_LIMIT, 'file_chunk_max' => self::MAX_CHUNK],
                'licensed'       => self::licenseKey() !== '',
            ];
        }

        private static function site()
        {
            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $plugins = [];
            foreach (get_plugins() as $file => $p) {
                if (is_plugin_active($file)) {
                    $plugins[] = ['slug' => dirname($file) === '.' ? basename($file, '.php') : dirname($file), 'file' => $file, 'name' => $p['Name'], 'version' => $p['Version']];
                }
            }
            $slugs = array_column($plugins, 'slug');

            $theme  = wp_get_theme();
            $parent = $theme->parent();

            $builders = [];
            if (defined('ELEMENTOR_VERSION')) $builders[] = 'elementor';
            if (defined('ELEMENTOR_PRO_VERSION')) $builders[] = 'elementor-pro';
            if (function_exists('et_setup_theme') || defined('ET_BUILDER_VERSION') || $theme->get_template() === 'Divi') $builders[] = 'divi';
            if (defined('WPB_VC_VERSION')) $builders[] = 'wpbakery';
            if (defined('BRICKS_VERSION') || $theme->get_template() === 'bricks') $builders[] = 'bricks';
            if (function_exists('wp_is_block_theme') && wp_is_block_theme()) $builders[] = 'block-theme';
            $builders[] = 'gutenberg';   // the core editor is always there; the list says what else is

            $seo = 'xagio';
            if (defined('WPSEO_VERSION')) $seo = 'yoast';
            elseif (defined('RANK_MATH_VERSION')) $seo = 'rankmath';
            elseif (defined('AIOSEO_VERSION')) $seo = 'aioseo';

            $languages = null;
            if (defined('ICL_SITEPRESS_VERSION')) {
                $languages = ['plugin' => 'wpml', 'list' => array_keys((array) apply_filters('wpml_active_languages', null, []))];
            } elseif (function_exists('pll_languages_list')) {
                $languages = ['plugin' => 'polylang', 'list' => (array) pll_languages_list()];
            }

            $front = (int) get_option('page_on_front');
            $posts = (int) get_option('page_for_posts');

            return [
                'home_url'            => home_url('/'),
                'site_url'            => site_url('/'),
                'domain'              => XAGIO_DOMAIN,
                'wp_version'          => get_bloginfo('version'),
                'php_version'         => PHP_VERSION,
                'locale'              => get_locale(),
                'multisite'           => is_multisite(),
                'permalink_structure' => (string) get_option('permalink_structure'),
                'posts_per_page'      => (int) get_option('posts_per_page'),
                'show_on_front'       => (string) get_option('show_on_front'),
                'front_page'          => $front > 0 ? ['id' => $front, 'url' => get_permalink($front)] : null,
                'posts_page'          => $posts > 0 ? ['id' => $posts, 'url' => get_permalink($posts)] : null,
                'search_engines_discouraged' => (string) get_option('blog_public') === '0',
                'theme'               => ['name' => $theme->get('Name'), 'slug' => $theme->get_stylesheet(), 'version' => $theme->get('Version'),
                                          'parent' => $parent ? ['name' => $parent->get('Name'), 'slug' => $parent->get_stylesheet(), 'version' => $parent->get('Version')] : null],
                'plugins'             => $plugins,
                'builders'            => array_values(array_unique($builders)),
                'seo_plugin'          => $seo,
                'redirect_plugins'    => array_values(array_intersect(['redirection', 'wordpress-seo-premium', 'seo-by-rank-math'], $slugs)),
                'languages'           => $languages,
                'attachment_pages'    => (string) get_option('wp_attachment_pages_enabled', '1') !== '0',
            ];
        }

        /* ==============================================================================================
         *  urls
         * ============================================================================================== */

        /** The list of URL sources, in the order they are walked; the cursor is [source index, offset]. */
        private static function urlSources()
        {
            $sources = [['kind' => 'home']];
            foreach (self::publicPostTypes() as $pt) {
                $sources[] = ['kind' => 'post_type', 'name' => $pt];
            }
            foreach (self::publicTaxonomies() as $tax) {
                $sources[] = ['kind' => 'term', 'name' => $tax];
            }
            $sources[] = ['kind' => 'author'];
            $sources[] = ['kind' => 'date'];
            return $sources;
        }

        /** Builders' template libraries: registered public, but not pages a visitor reaches. */
        const NOT_CONTENT = ['elementor_library', 'et_pb_layout', 'bricks_template', 'ct_template', 'oxy_user_library', 'fl-builder-template', 'jet-theme-core', 'e-floating-buttons'];

        private static function publicPostTypes()
        {
            $out = [];
            foreach (get_post_types(['public' => true], 'objects') as $name => $pt) {
                if ($name === 'attachment' || in_array($name, self::NOT_CONTENT, true)) continue;
                // a type with no permalinks of its own (rewrite off) is not a page, except the built-in ones
                if (!$pt->_builtin && ($pt->rewrite === false || !$pt->publicly_queryable)) continue;
                $out[] = $name;
            }
            if ((string) get_option('wp_attachment_pages_enabled', '1') !== '0') {
                $out[] = 'attachment';
            }
            return $out;
        }

        private static function publicTaxonomies()
        {
            $types = self::publicPostTypes();
            $out   = [];
            foreach (get_taxonomies(['public' => true, 'publicly_queryable' => true], 'objects') as $name => $tx) {
                if (!array_intersect((array) $tx->object_type, $types)) continue;   // only taxonomies of real content
                $out[] = $name;
            }
            return $out;
        }

        /** The static front page is the home URL: listed once, as home. */
        private static function frontPageId()
        {
            return (string) get_option('show_on_front') === 'page' ? (int) get_option('page_on_front') : 0;
        }

        /**
         * Every public URL. A page may carry more than `limit` items by the /page/N/ rows of its last archive.
         */
        private static function urls($cursor, $limit)
        {
            $sources = self::urlSources();
            $si      = max(0, (int) ($cursor['s'] ?? 0));
            $offset  = max(0, (int) ($cursor['o'] ?? 0));
            $items   = [];
            $ppp     = max(1, (int) get_option('posts_per_page'));

            while ($si < count($sources) && count($items) < $limit) {
                $src  = $sources[$si];
                $want = $limit - count($items);
                $got  = 0;

                switch ($src['kind']) {
                    case 'home':
                        $home  = home_url('/');
                        $paged = 0;
                        if ((string) get_option('show_on_front') !== 'page') {
                            $paged = (int) ceil(self::countPublished('post') / $ppp);
                        }
                        $fp      = self::frontPageId();
                        $fpost   = $fp > 0 ? get_post($fp) : null;
                        $items[] = self::urlRow($home, 'home', $fpost ? $fpost->post_type : null, $fpost ? $fpost->ID : null,
                            $fpost ? get_post_modified_time('c', true, $fpost) : null, $fpost ? self::seoFor('post', $fpost) : self::seoFor('home', 0));
                        self::addPaged($items, $home, $paged, 'home', null);
                        $got = 1;
                        break;

                    case 'post_type':
                        $q = new WP_Query(['post_type' => $src['name'], 'post_status' => $src['name'] === 'attachment' ? 'inherit' : 'publish', 'posts_per_page' => $want,
                                           'offset' => $offset, 'orderby' => 'ID', 'order' => 'ASC', 'no_found_rows' => true, 'has_password' => false,
                                           'ignore_sticky_posts' => true, 'suppress_filters' => true]);
                        foreach ($q->posts as $p) {
                            if ((int) $p->ID === self::frontPageId()) continue;   // already the home row
                            $seo     = self::seoFor('post', $p);
                            $items[] = self::urlRow(get_permalink($p), 'post_type', $p->post_type, $p->ID, get_post_modified_time('c', true, $p), $seo);
                            // the posts page (page_for_posts) carries the blog archive's pagination
                            if ((int) $p->ID === (int) get_option('page_for_posts')) {
                                self::addPaged($items, get_permalink($p), (int) ceil(self::countPublished('post') / $ppp), 'post_type', 'page');
                            }
                        }
                        $got = count($q->posts);
                        break;

                    case 'term':
                        $terms = get_terms(['taxonomy' => $src['name'], 'hide_empty' => true, 'number' => $want, 'offset' => $offset, 'orderby' => 'term_id', 'order' => 'ASC']);
                        if (is_wp_error($terms)) $terms = [];
                        foreach ($terms as $t) {
                            $link = get_term_link($t);
                            if (is_wp_error($link)) continue;
                            $items[] = self::urlRow($link, 'term', $t->taxonomy, $t->term_id, null, self::seoFor('term', $t));
                            self::addPaged($items, $link, (int) ceil(((int) $t->count) / $ppp), 'term', $t->taxonomy);
                        }
                        $got = count($terms);
                        break;

                    case 'author':
                        $users = get_users(['has_published_posts' => ['post'], 'number' => $want, 'offset' => $offset, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => ['ID']]);
                        foreach ($users as $u) {
                            $link    = get_author_posts_url((int) $u->ID);
                            $items[] = self::urlRow($link, 'author', null, (int) $u->ID, null, ['noindex' => self::authorNoindex(), 'canonical' => null]);
                            self::addPaged($items, $link, (int) ceil(count_user_posts((int) $u->ID, 'post', true) / $ppp), 'author', null);
                        }
                        $got = count($users);
                        break;

                    case 'date':
                        global $wpdb;
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        $rows = $wpdb->get_results($wpdb->prepare(
                            "SELECT YEAR(post_date) AS y, MONTH(post_date) AS m, COUNT(*) AS n FROM {$wpdb->posts}
                              WHERE post_type = 'post' AND post_status = 'publish' GROUP BY y, m ORDER BY y, m LIMIT %d OFFSET %d", $want, $offset), ARRAY_A);
                        $noindex = self::dateNoindex();
                        $seenYears = [];
                        foreach ($rows as $r) {
                            $y = (int) $r['y'];
                            if (!isset($seenYears[$y]) && (int) $r['m'] === self::firstMonthOf($y)) {
                                $yl      = get_year_link($y);
                                $items[] = self::urlRow($yl, 'date', 'year', null, null, ['noindex' => $noindex, 'canonical' => null]);
                                self::addPaged($items, $yl, (int) ceil(self::countPublishedInYear($y) / $ppp), 'date', 'year');
                                $seenYears[$y] = true;
                            }
                            $ml      = get_month_link($y, (int) $r['m']);
                            $items[] = self::urlRow($ml, 'date', 'month', null, null, ['noindex' => $noindex, 'canonical' => null]);
                            self::addPaged($items, $ml, (int) ceil(((int) $r['n']) / $ppp), 'date', 'month');
                        }
                        $got = count($rows);
                        break;
                }

                if ($src['kind'] === 'home' || $got < $want) {
                    $si++;
                    $offset = 0;
                } else {
                    $offset += $got;
                }
            }

            return ['items' => $items, 'next_cursor' => $si < count($sources) ? ['s' => $si, 'o' => $offset] : null];
        }

        private static function urlRow($url, $type, $object, $id, $modified, $seo)
        {
            return [
                'url'       => (string) $url,
                'type'      => $type,
                'object'    => $object,
                'id'        => $id,
                'modified'  => $modified,
                'noindex'   => (bool) ($seo['noindex'] ?? false),
                'canonical' => $seo['canonical'] ?? null,
            ];
        }

        /** /page/2/ .. /page/N/ of an archive, with the site's own pagination base. */
        private static function addPaged(array &$items, $base, $pages, $type, $object)
        {
            global $wp_rewrite;
            if ($pages < 2) return;
            $pretty = (string) get_option('permalink_structure') !== '';
            for ($n = 2; $n <= $pages; $n++) {
                $url = $pretty
                    ? trailingslashit($base) . user_trailingslashit($wp_rewrite->pagination_base . '/' . $n, 'paged')
                    : add_query_arg('paged', $n, $base);
                $items[] = ['url' => $url, 'type' => 'paged', 'object' => $type . ($object ? ':' . $object : ''), 'id' => $n, 'modified' => null, 'noindex' => false, 'canonical' => null];
            }
        }

        private static function countPublished($postType)
        {
            $c = wp_count_posts($postType);
            return isset($c->publish) ? (int) $c->publish : 0;
        }

        private static function countPublishedInYear($y)
        {
            global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND YEAR(post_date) = %d", $y));
        }

        private static function firstMonthOf($y)
        {
            global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return (int) $wpdb->get_var($wpdb->prepare("SELECT MIN(MONTH(post_date)) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' AND YEAR(post_date) = %d", $y));
        }

        /* ==============================================================================================
         *  seo (as configured) and the noindex / canonical of one object
         * ============================================================================================== */

        /** noindex + canonical of a post / term / home, from whichever SEO plugin rules the site. */
        private static function seoFor($kind, $obj)
        {
            $full = self::seoDetails($kind, $obj);
            return ['noindex' => $full['noindex'], 'canonical' => $full['canonical']];
        }

        private static function seoDetails($kind, $obj)
        {
            $out = ['title' => null, 'title_resolved' => null, 'description' => null, 'description_resolved' => null,
                    'robots' => null, 'noindex' => false, 'canonical' => null, 'schema' => null, 'source' => 'none'];

            if ((string) get_option('blog_public') === '0') {
                $out['noindex'] = true;   // "discourage search engines": every page is noindex
            }

            if (defined('WPSEO_VERSION')) {
                $out['source'] = 'yoast';
                $opts = (array) get_option('wpseo_titles', []);
                if ($kind === 'post') {
                    // the post's own value, else the template of its post type (Search Appearance)
                    $out['title']       = ((string) get_post_meta($obj->ID, '_yoast_wpseo_title', true) ?: (string) ($opts['title-' . $obj->post_type] ?? '')) ?: null;
                    $out['description'] = ((string) get_post_meta($obj->ID, '_yoast_wpseo_metadesc', true) ?: (string) ($opts['metadesc-' . $obj->post_type] ?? '')) ?: null;
                    $out['canonical']   = (string) get_post_meta($obj->ID, '_yoast_wpseo_canonical', true) ?: null;
                    $ni   = (string) get_post_meta($obj->ID, '_yoast_wpseo_meta-robots-noindex', true);
                    $out['noindex'] = $out['noindex'] || $ni === '1' || ($ni === '' && !empty($opts['noindex-' . $obj->post_type]));
                    $out['robots']  = $out['noindex'] ? 'noindex' : null;
                    if (function_exists('wpseo_replace_vars')) {
                        $out['title_resolved']       = $out['title'] !== null ? self::safe(function () use ($out, $obj) { return wpseo_replace_vars($out['title'], $obj); }) : null;
                        $out['description_resolved'] = $out['description'] !== null ? self::safe(function () use ($out, $obj) { return wpseo_replace_vars($out['description'], $obj); }) : null;
                    }
                } elseif ($kind === 'term') {
                    $tm = (array) get_option('wpseo_taxonomy_meta', []);
                    $m  = (array) ($tm[$obj->taxonomy][$obj->term_id] ?? []);
                    $out['title']       = (($m['wpseo_title'] ?? '') ?: (string) ($opts['title-tax-' . $obj->taxonomy] ?? '')) ?: null;
                    $out['description'] = (($m['wpseo_desc'] ?? '') ?: (string) ($opts['metadesc-tax-' . $obj->taxonomy] ?? '')) ?: null;
                    $out['canonical']   = $m['wpseo_canonical'] ?? null;
                    $ni = (string) ($m['wpseo_noindex'] ?? 'default');
                    $out['noindex'] = $out['noindex'] || $ni === 'noindex' || ($ni === 'default' && !empty($opts['noindex-tax-' . $obj->taxonomy]));
                    $out['robots']  = $out['noindex'] ? 'noindex' : null;
                    if (function_exists('wpseo_replace_vars')) {
                        $out['title_resolved']       = $out['title'] !== null ? self::safe(function () use ($out, $obj) { return wpseo_replace_vars($out['title'], $obj); }) : null;
                        $out['description_resolved'] = $out['description'] !== null ? self::safe(function () use ($out, $obj) { return wpseo_replace_vars($out['description'], $obj); }) : null;
                    }
                } elseif ($kind === 'home') {
                    $out['title']       = (string) ($opts['title-home-wpseo'] ?? '') ?: null;
                    $out['description'] = (string) ($opts['metadesc-home-wpseo'] ?? '') ?: null;
                    if (function_exists('wpseo_replace_vars')) {
                        $out['title_resolved']       = $out['title'] !== null ? self::safe(function () use ($out) { return wpseo_replace_vars($out['title'], []); }) : null;
                        $out['description_resolved'] = $out['description'] !== null ? self::safe(function () use ($out) { return wpseo_replace_vars($out['description'], []); }) : null;
                    }
                }
                return $out;
            }

            if (defined('RANK_MATH_VERSION')) {
                $out['source'] = 'rankmath';
                $get = $kind === 'term' ? function ($k) use ($obj) { return get_term_meta($obj->term_id, $k, true); }
                                        : ($kind === 'post' ? function ($k) use ($obj) { return get_post_meta($obj->ID, $k, true); } : function () { return ''; });
                $rt   = (array) get_option('rank-math-options-titles', []);
                $tkey = $kind === 'post' ? 'pt_' . $obj->post_type : ($kind === 'term' ? 'tax_' . $obj->taxonomy : 'homepage');
                $out['title']       = ((string) $get('rank_math_title') ?: (string) ($rt[$tkey . '_title'] ?? '')) ?: null;
                $out['description'] = ((string) $get('rank_math_description') ?: (string) ($rt[$tkey . '_description'] ?? '')) ?: null;
                $out['canonical']   = (string) $get('rank_math_canonical_url') ?: null;
                $robots = $get('rank_math_robots');
                if (is_array($robots) && $robots) {
                    $out['robots']  = implode(',', $robots);
                    $out['noindex'] = $out['noindex'] || in_array('noindex', $robots, true);
                }
                return $out;
            }

            if (defined('AIOSEO_VERSION') && $kind === 'post') {
                global $wpdb;
                $out['source'] = 'aioseo';
                $table = $wpdb->prefix . 'aioseo_posts';
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $r = $wpdb->get_row($wpdb->prepare("SELECT title, description, canonical_url, robots_default, robots_noindex FROM {$table} WHERE post_id = %d", $obj->ID), ARRAY_A);
                    if ($r) {
                        $out['title']       = $r['title'] ?: null;
                        $out['description'] = $r['description'] ?: null;
                        $out['canonical']   = $r['canonical_url'] ?: null;
                        $out['noindex']     = $out['noindex'] || (!(int) $r['robots_default'] && (int) $r['robots_noindex']);
                        $out['robots']      = $out['noindex'] ? 'noindex' : null;
                    }
                }
                return $out;
            }

            // Xagio's own SEO (the default when no other SEO plugin runs)
            $out['source'] = 'xagio';
            if ($kind === 'post') {
                $pt       = get_option('XAGIO_SEO_DEFAULT_POST_TYPES');
                $front    = (int) get_option('page_on_front') === (int) $obj->ID;
                $default  = is_array($pt) ? ($pt[$front ? 'homepage' : $obj->post_type] ?? []) : [];
                $out['title']       = (string) get_post_meta($obj->ID, 'XAGIO_SEO_TITLE', true) ?: (($default['XAGIO_SEO_TITLE'] ?? '') ?: null);
                $out['description'] = (string) get_post_meta($obj->ID, 'XAGIO_SEO_DESCRIPTION', true) ?: (($default['XAGIO_SEO_DESCRIPTION'] ?? '') ?: null);
                $out['canonical']   = (string) get_post_meta($obj->ID, 'XAGIO_SEO_CANONICAL_URL', true) ?: null;
                $index = (string) get_post_meta($obj->ID, 'XAGIO_SEO_META_ROBOTS_INDEX', true);
                $robotsOn = !metadata_exists('post', $obj->ID, 'XAGIO_SEO_META_ROBOTS_ENABLE') || get_post_meta($obj->ID, 'XAGIO_SEO_META_ROBOTS_ENABLE', true) !== '';
                if ($robotsOn) {
                    $globalNoindex = !empty($default['XAGIO_SEO_ROBOTS']);
                    $noindex = $index === 'noindex' || ($index !== 'index' && $globalNoindex);
                    $out['noindex'] = $out['noindex'] || $noindex;
                    $out['robots']  = $noindex ? 'noindex' : ($index !== '' && $index !== 'default' ? $index : null);
                }
                $schema = get_post_meta($obj->ID, 'XAGIO_SEO_SCHEMA_DATA', true);
                $out['schema'] = $schema !== '' && $schema !== false ? $schema : null;
                if (class_exists('XAGIO_MODEL_SEO')) {
                    $out['title_resolved']       = $out['title'] !== null ? self::safe(function () use ($out, $obj) { return XAGIO_MODEL_SEO::replaceVars($out['title'], $obj->ID); }) : null;
                    $out['description_resolved'] = $out['description'] !== null ? self::safe(function () use ($out, $obj) { return XAGIO_MODEL_SEO::replaceVars($out['description'], $obj->ID); }) : null;
                }
            } elseif ($kind === 'term') {
                $meta = function_exists('xagio_get_term_meta') ? (array) xagio_get_term_meta($obj->term_id) : [];
                $tx   = get_option('XAGIO_SEO_DEFAULT_TAXONOMIES');
                $def  = is_array($tx) ? ($tx[$obj->taxonomy] ?? []) : [];
                $out['title']       = ($meta['XAGIO_SEO_TITLE'] ?? '') ?: (($def['XAGIO_SEO_TITLE'] ?? '') ?: null);
                $out['description'] = ($meta['XAGIO_SEO_DESCRIPTION'] ?? '') ?: (($def['XAGIO_SEO_DESCRIPTION'] ?? '') ?: null);
                $noindex = !empty($meta['XAGIO_SEO_ROBOTS']) || !empty($def['XAGIO_SEO_ROBOTS']);
                $out['noindex'] = $out['noindex'] || $noindex;
                $out['robots']  = $noindex ? 'noindex,follow' : null;
            } elseif ($kind === 'home') {
                $pt = get_option('XAGIO_SEO_DEFAULT_POST_TYPES');
                $hp = is_array($pt) ? ($pt['homepage'] ?? []) : [];
                $out['title']       = ($hp['XAGIO_SEO_TITLE'] ?? '') ?: null;
                $out['description'] = ($hp['XAGIO_SEO_DESCRIPTION'] ?? '') ?: null;
                $out['noindex']     = $out['noindex'] || !empty($hp['XAGIO_SEO_ROBOTS']);
            }
            return $out;
        }

        private static function authorNoindex()
        {
            if ((string) get_option('blog_public') === '0') return true;
            if (defined('WPSEO_VERSION')) {
                $o = (array) get_option('wpseo_titles', []);
                return !empty($o['noindex-author-wpseo']) || !empty($o['disable-author']);
            }
            return false;
        }

        private static function dateNoindex()
        {
            if ((string) get_option('blog_public') === '0') return true;
            if (defined('WPSEO_VERSION')) {
                $o = (array) get_option('wpseo_titles', []);
                return !empty($o['noindex-archive-wpseo']) || !empty($o['disable-date']);
            }
            return false;
        }

        /** Calls into other plugins' code must never take the export down. */
        private static function safe(callable $fn)
        {
            try {
                $v = $fn();
                return is_string($v) ? $v : null;
            } catch (Throwable $e) {
                return null;
            }
        }

        /** The SEO values as configured, per post and per term (the parity reference). */
        private static function seo($cursor, $limit)
        {
            $sources = [];
            foreach (self::publicPostTypes() as $pt) $sources[] = ['kind' => 'post', 'name' => $pt];
            foreach (self::publicTaxonomies() as $tx) $sources[] = ['kind' => 'term', 'name' => $tx];
            $si     = max(0, (int) ($cursor['s'] ?? 0));
            $offset = max(0, (int) ($cursor['o'] ?? 0));
            $items  = [];

            if ($si === 0 && $offset === 0) {
                $fp      = self::frontPageId();
                $fpost   = $fp > 0 ? get_post($fp) : null;
                $items[] = ['url' => home_url('/'), 'id' => $fpost ? $fpost->ID : null, 'object' => 'home'] + ($fpost ? self::seoDetails('post', $fpost) : self::seoDetails('home', 0));
            }

            while ($si < count($sources) && count($items) < $limit) {
                $src  = $sources[$si];
                $want = $limit - count($items);
                if ($src['kind'] === 'post') {
                    $q = new WP_Query(['post_type' => $src['name'], 'post_status' => $src['name'] === 'attachment' ? 'inherit' : 'publish', 'posts_per_page' => $want,
                                       'offset' => $offset, 'orderby' => 'ID', 'order' => 'ASC', 'no_found_rows' => true, 'has_password' => false,
                                       'ignore_sticky_posts' => true, 'suppress_filters' => true]);
                    foreach ($q->posts as $p) {
                        if ((int) $p->ID === self::frontPageId()) continue;   // already the home row
                        $items[] = ['url' => get_permalink($p), 'id' => $p->ID, 'object' => $p->post_type] + self::seoDetails('post', $p);
                    }
                    $got = count($q->posts);
                } else {
                    $terms = get_terms(['taxonomy' => $src['name'], 'hide_empty' => true, 'number' => $want, 'offset' => $offset, 'orderby' => 'term_id', 'order' => 'ASC']);
                    if (is_wp_error($terms)) $terms = [];
                    foreach ($terms as $t) {
                        $link = get_term_link($t);
                        if (is_wp_error($link)) continue;
                        $items[] = ['url' => $link, 'id' => $t->term_id, 'object' => $t->taxonomy] + self::seoDetails('term', $t);
                    }
                    $got = count($terms);
                }
                if ($got < $want) {
                    $si++;
                    $offset = 0;
                } else {
                    $offset += $got;
                }
            }

            return ['items' => $items, 'next_cursor' => $si < count($sources) ? ['s' => $si, 'o' => $offset] : null];
        }

        /* ==============================================================================================
         *  redirects
         * ============================================================================================== */

        /** Every declared redirect, from every source; one list, paged by [source, offset]. */
        private static function redirects($cursor, $limit)
        {
            $sources = ['xagio', 'xagio_404', 'redirection', 'yoast_premium', 'rankmath', 'old_slug'];
            $si      = max(0, (int) ($cursor['s'] ?? 0));
            $offset  = max(0, (int) ($cursor['o'] ?? 0));
            $items   = [];

            while ($si < count($sources) && count($items) < $limit) {
                $want = $limit - count($items);
                $rows = self::redirectSource($sources[$si], $offset, $want);
                foreach ($rows as $r) $items[] = $r;
                if (count($rows) < $want) {
                    $si++;
                    $offset = 0;
                } else {
                    $offset += count($rows);
                }
            }
            return ['items' => $items, 'next_cursor' => $si < count($sources) ? ['s' => $si, 'o' => $offset] : null];
        }

        private static function tableExists($table)
        {
            global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
        }

        private static function redirectRow($from, $to, $code, $regex, $source, $enabled, $extra = [])
        {
            return ['from' => $from, 'to' => $to, 'code' => (int) $code, 'regex' => (bool) $regex, 'source' => $source, 'enabled' => (bool) $enabled] + $extra;
        }

        private static function redirectSource($source, $offset, $limit)
        {
            global $wpdb;
            $out = [];

            switch ($source) {
                case 'xagio':
                    if (!self::tableExists('xag_redirects')) return [];
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $rows = $wpdb->get_results($wpdb->prepare('SELECT id, old, new, is_redirect_active FROM xag_redirects ORDER BY id LIMIT %d OFFSET %d', $limit, $offset), ARRAY_A);
                    foreach ($rows as $r) {
                        // as XAGIO_MODEL_REDIRECTS::doRedirect: a path on this site, or an absolute URL elsewhere
                        $to = strpos((string) $r['new'], 'http') !== false ? $r['new'] : site_url('/' . trim((string) $r['new'], '/'));
                        $out[] = self::redirectRow('/' . ltrim((string) $r['old'], '/'), $to, 301, false, 'xagio', (int) $r['is_redirect_active'] === 1);
                    }
                    return $out;

                case 'xagio_404':
                    if ($offset > 0) return [];
                    $u = (string) get_option('XAGIO_GLOBAL_404_REDIRECTION_URL');
                    return $u !== '' ? [self::redirectRow(null, $u, 301, false, 'xagio-404', true, ['match' => 'every_404'])] : [];

                case 'redirection':
                    $table = $wpdb->prefix . 'redirection_items';
                    if (!self::tableExists($table)) return [];
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $rows = $wpdb->get_results($wpdb->prepare("SELECT id, url, regex, action_type, action_code, action_data, match_type, status FROM {$table} ORDER BY id LIMIT %d OFFSET %d", $limit, $offset), ARRAY_A);
                    foreach ($rows as $r) {
                        $plain = $r['match_type'] === 'url' && $r['action_type'] === 'url';
                        $out[] = self::redirectRow($r['url'], $plain ? $r['action_data'] : null, (int) $r['action_code'], (int) $r['regex'] === 1, 'redirection',
                            $r['status'] === 'enabled', $plain ? [] : ['match' => $r['match_type'], 'action' => $r['action_type']]);
                    }
                    return $out;

                case 'yoast_premium':
                    if ($offset > 0) return [];
                    $base = get_option('wpseo-premium-redirects-base');
                    if (!is_array($base)) return [];
                    foreach ($base as $r) {
                        if (!is_array($r) || !isset($r['origin'])) continue;
                        $isRx  = ($r['format'] ?? 'plain') === 'regex';
                        $out[] = self::redirectRow($isRx ? (string) $r['origin'] : '/' . ltrim((string) $r['origin'], '/'), ($r['url'] ?? '') !== '' ? $r['url'] : null, (int) ($r['type'] ?? 301),
                            $isRx, 'yoast-premium', true);
                    }
                    return $out;   // one option, small: returned whole on the first page

                case 'rankmath':
                    $table = $wpdb->prefix . 'rank_math_redirections';
                    if (!self::tableExists($table)) return [];
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $rows = $wpdb->get_results($wpdb->prepare("SELECT id, sources, url_to, header_code, status FROM {$table} ORDER BY id LIMIT %d OFFSET %d", $limit, $offset), ARRAY_A);
                    foreach ($rows as $r) {
                        foreach ((array) maybe_unserialize($r['sources']) as $src) {
                            if (!is_array($src) || !isset($src['pattern'])) continue;
                            $cmp   = (string) ($src['comparison'] ?? 'exact');
                            // a regex keeps its pattern exactly; a path gets its leading slash
                            $from  = $cmp === 'regex' ? (string) $src['pattern'] : '/' . ltrim((string) $src['pattern'], '/');
                            $out[] = self::redirectRow($from, $r['url_to'] ?: null, (int) $r['header_code'], $cmp === 'regex', 'rankmath',
                                $r['status'] === 'active', $cmp === 'exact' || $cmp === 'regex' ? [] : ['match' => $cmp]);
                        }
                    }
                    return $out;

                case 'old_slug':
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $rows = $wpdb->get_results($wpdb->prepare(
                        "SELECT m.meta_id, m.post_id, m.meta_value FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id
                          WHERE m.meta_key = '_wp_old_slug' AND p.post_status = 'publish' ORDER BY m.meta_id LIMIT %d OFFSET %d", $limit, $offset), ARRAY_A);
                    foreach ($rows as $r) {
                        $post = get_post((int) $r['post_id']);
                        if (!$post || (string) $r['meta_value'] === '' || (string) $r['meta_value'] === $post->post_name) continue;
                        $now  = get_permalink($post);
                        $path = (string) wp_parse_url($now, PHP_URL_PATH);
                        $old  = preg_replace('#/' . preg_quote($post->post_name, '#') . '(/?)$#', '/' . $r['meta_value'] . '$1', $path, 1, $n);
                        if ($n === 1) {
                            $out[] = self::redirectRow($old, $now, 301, false, 'old-slug', true, ['post_id' => (int) $post->ID]);
                        }
                    }
                    return $out;
            }
            return [];
        }

        /* ==============================================================================================
         *  forms (recipients, never secrets)
         * ============================================================================================== */

        private static function forms()
        {
            $forms = [];
            $admin = (string) get_option('admin_email');

            // Contact Form 7
            if (defined('WPCF7_VERSION')) {
                $wpcf7 = (array) get_option('wpcf7', []);
                $cap   = null;
                if (!empty($wpcf7['recaptcha']) && is_array($wpcf7['recaptcha'])) $cap = ['type' => 'recaptcha-v3', 'site_key' => (string) array_key_first($wpcf7['recaptcha'])];
                if (!empty($wpcf7['turnstile']) && is_array($wpcf7['turnstile'])) $cap = ['type' => 'turnstile', 'site_key' => (string) array_key_first($wpcf7['turnstile'])];
                foreach (get_posts(['post_type' => 'wpcf7_contact_form', 'post_status' => 'any', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC']) as $f) {
                    $mail = (array) get_post_meta($f->ID, '_mail', true);
                    $msgs = (array) get_post_meta($f->ID, '_messages', true);
                    $forms[] = [
                        'plugin'       => 'cf7', 'id' => (string) $f->ID, 'title' => $f->post_title,
                        'fields'       => self::cf7Fields((string) get_post_meta($f->ID, '_form', true)),
                        'recipients'   => self::recipients((string) ($mail['recipient'] ?? ''), $admin),
                        'subject'      => (string) ($mail['subject'] ?? ''),
                        'confirmation' => (string) ($msgs['mail_sent_ok'] ?? ''),
                        'captcha'      => $cap,
                    ];
                }
            }

            // WPForms (Lite and Pro)
            if (function_exists('wpforms') || defined('WPFORMS_VERSION')) {
                $ws  = (array) get_option('wpforms_settings', []);
                $prov = (string) ($ws['captcha-provider'] ?? '');
                foreach (get_posts(['post_type' => 'wpforms', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC']) as $f) {
                    $d = json_decode((string) $f->post_content, true);
                    if (!is_array($d)) continue;
                    $fields = [];
                    foreach ((array) ($d['fields'] ?? []) as $fid => $fd) {
                        if (!is_array($fd)) continue;
                        $fields[] = ['name' => 'wpforms[fields][' . $fid . ']', 'type' => (string) ($fd['type'] ?? ''), 'label' => (string) ($fd['label'] ?? ''), 'required' => !empty($fd['required'])];
                    }
                    $st  = (array) ($d['settings'] ?? []);
                    $rec = [];
                    $sub = '';
                    foreach ((array) ($st['notifications'] ?? []) as $n) {
                        if (!is_array($n)) continue;
                        $rec = array_merge($rec, self::recipients((string) ($n['email'] ?? '{admin_email}'), $admin));
                        if ($sub === '') $sub = (string) ($n['subject'] ?? '');
                    }
                    if (empty($st['notification_enable']) && isset($st['notification_enable'])) $rec = [];
                    $conf = '';
                    foreach ((array) ($st['confirmations'] ?? []) as $c) {
                        if (is_array($c) && ($c['type'] ?? 'message') === 'message') { $conf = (string) ($c['message'] ?? ''); break; }
                    }
                    $cap = null;
                    if (!empty($st['recaptcha']) && in_array($prov, ['recaptcha', 'hcaptcha', 'turnstile'], true)) {
                        $cap = ['type' => $prov, 'site_key' => (string) ($ws[$prov . '-site-key'] ?? '')];
                    }
                    $forms[] = ['plugin' => 'wpforms', 'id' => (string) $f->ID, 'title' => $f->post_title, 'fields' => $fields,
                                'recipients' => array_values(array_unique($rec)), 'subject' => $sub, 'confirmation' => $conf, 'captcha' => $cap];
                }
            }

            // Gravity Forms
            if (class_exists('GFAPI')) {
                foreach ((array) self::safeArray(function () { return GFAPI::get_forms(); }) as $f) {
                    $fields = [];
                    $cap    = null;
                    foreach ((array) ($f['fields'] ?? []) as $fd) {
                        $fd   = is_object($fd) ? (array) $fd : (array) $fd;
                        $type = (string) ($fd['type'] ?? '');
                        if ($type === 'captcha') {
                            $cap = ['type' => 'recaptcha', 'site_key' => (string) get_option('rg_gforms_captcha_public_key')];
                            continue;
                        }
                        $fields[] = ['name' => 'input_' . ($fd['id'] ?? ''), 'type' => $type, 'label' => (string) ($fd['label'] ?? ''), 'required' => !empty($fd['isRequired'])];
                    }
                    $rec = [];
                    $sub = '';
                    foreach ((array) ($f['notifications'] ?? []) as $n) {
                        if (!is_array($n) || (isset($n['isActive']) && !$n['isActive'])) continue;
                        $rec = array_merge($rec, self::recipients((string) ($n['to'] ?? '{admin_email}'), $admin));
                        if ($sub === '') $sub = (string) ($n['subject'] ?? '');
                    }
                    $conf = '';
                    foreach ((array) ($f['confirmations'] ?? []) as $c) {
                        if (is_array($c) && ($c['type'] ?? 'message') === 'message') { $conf = (string) ($c['message'] ?? ''); break; }
                    }
                    $forms[] = ['plugin' => 'gravity', 'id' => (string) ($f['id'] ?? ''), 'title' => (string) ($f['title'] ?? ''), 'fields' => $fields,
                                'recipients' => array_values(array_unique($rec)), 'subject' => $sub, 'confirmation' => $conf, 'captcha' => $cap];
                }
            }

            // Elementor Pro form widgets, inside pages built with Elementor
            if (defined('ELEMENTOR_PRO_VERSION') || defined('ELEMENTOR_VERSION')) {
                global $wpdb;
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $ids = $wpdb->get_col("SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_elementor_data'
                                         WHERE p.post_status = 'publish' AND m.meta_value LIKE '%\"widgetType\":\"form\"%' ORDER BY p.ID");
                foreach ($ids as $pid) {
                    $data = json_decode((string) get_post_meta((int) $pid, '_elementor_data', true), true);
                    if (!is_array($data)) continue;
                    foreach (self::elementorForms($data) as $w) {
                        $s      = (array) ($w['settings'] ?? []);
                        $fields = [];
                        $cap    = null;
                        foreach ((array) ($s['form_fields'] ?? []) as $fd) {
                            $type = (string) ($fd['field_type'] ?? 'text');
                            if ($type === 'recaptcha' || $type === 'recaptcha_v3') {
                                $cap = ['type' => $type === 'recaptcha' ? 'recaptcha-v2' : 'recaptcha-v3',
                                        'site_key' => (string) get_option($type === 'recaptcha' ? 'elementor_pro_recaptcha_site_key' : 'elementor_pro_recaptcha_v3_site_key')];
                                continue;
                            }
                            if ($type === 'honeypot') continue;
                            $fields[] = ['name' => 'form_fields[' . ($fd['custom_id'] ?? '') . ']', 'type' => $type, 'label' => (string) ($fd['field_label'] ?? ''), 'required' => ($fd['required'] ?? '') === 'true'];
                        }
                        $sendsMail = in_array('email', (array) ($s['submit_actions'] ?? ['email']), true);
                        $forms[]   = ['plugin' => 'elementor-pro', 'id' => $pid . ':' . ($w['id'] ?? ''), 'title' => (string) ($s['form_name'] ?? 'Form'), 'post_id' => (int) $pid, 'fields' => $fields,
                                      'recipients' => $sendsMail ? self::recipients((string) ($s['email_to'] ?? $admin), $admin) : [],
                                      'subject' => (string) ($s['email_subject'] ?? ''), 'confirmation' => (string) ($s['success_message'] ?? ''), 'captcha' => $cap];
                    }
                }
            }

            return $forms;
        }

        private static function safeArray(callable $fn)
        {
            try {
                $v = $fn();
                return is_array($v) ? $v : [];
            } catch (Throwable $e) {
                return [];
            }
        }

        /** Every Elementor widget of type "form" in a page's element tree. */
        private static function elementorForms(array $elements)
        {
            $out = [];
            foreach ($elements as $el) {
                if (!is_array($el)) continue;
                if (($el['elType'] ?? '') === 'widget' && ($el['widgetType'] ?? '') === 'form') $out[] = $el;
                if (!empty($el['elements']) && is_array($el['elements'])) $out = array_merge($out, self::elementorForms($el['elements']));
            }
            return $out;
        }

        /** CF7's form template: [type* name …] tags. */
        private static function cf7Fields($template)
        {
            $fields = [];
            if (preg_match_all('/\[\s*([a-zA-Z_][\w-]*)(\*?)\s+([\w-]+)[^\]]*\]/', $template, $m, PREG_SET_ORDER)) {
                foreach ($m as $t) {
                    if (in_array($t[1], ['submit', 'response'], true)) continue;
                    $fields[] = ['name' => $t[3], 'type' => $t[1], 'label' => '', 'required' => $t[2] === '*'];
                }
            }
            return $fields;
        }

        /** Mail addresses in a recipient setting, with the plugins' "admin e-mail" tags resolved. */
        private static function recipients($value, $admin)
        {
            $value = str_replace(['{admin_email}', '[_site_admin_email]', '{site_admin_email}'], $admin, $value);
            $out   = [];
            foreach (preg_split('/[,;\s]+/', $value) as $part) {
                $part = trim($part, " <>\"'");
                if ($part !== '' && is_email($part)) $out[] = $part;
            }
            return array_values(array_unique($out));
        }

        /* ==============================================================================================
         *  comments, menus
         * ============================================================================================== */

        private static function comments($cursor, $limit)
        {
            $after = max(0, (int) ($cursor['after'] ?? 0));
            global $wpdb;
            // Approved comments on published posts without a password (as urls / seo); no e-mail, no IP.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT c.comment_ID, c.comment_post_ID, c.comment_parent, c.comment_author, c.comment_date_gmt, c.comment_content, c.comment_type
                   FROM {$wpdb->comments} c JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID
                  WHERE c.comment_approved = '1' AND p.post_status = 'publish' AND p.post_password = '' AND c.comment_ID > %d ORDER BY c.comment_ID LIMIT %d", $after, $limit), ARRAY_A);
            $items = [];
            foreach ($rows as $r) {
                $items[] = ['post_id' => (int) $r['comment_post_ID'], 'id' => (int) $r['comment_ID'], 'parent' => (int) $r['comment_parent'],
                            'author' => (string) $r['comment_author'], 'date' => mysql2date('c', $r['comment_date_gmt'] . ' +0000', false),
                            'type' => (string) ($r['comment_type'] ?: 'comment'), 'content' => (string) $r['comment_content']];
            }
            $last = $rows ? (int) end($rows)['comment_ID'] : $after;
            return ['items' => $items, 'next_cursor' => count($rows) === $limit ? ['after' => $last] : null];
        }

        private static function menus()
        {
            $locations = (array) get_nav_menu_locations();
            $out       = [];
            foreach (wp_get_nav_menus() as $menu) {
                $items = [];
                foreach ((array) wp_get_nav_menu_items($menu->term_id) as $it) {
                    $items[] = ['id' => (int) $it->ID, 'parent' => (int) $it->menu_item_parent, 'title' => (string) $it->title, 'url' => (string) $it->url,
                                'object' => (string) $it->object, 'object_id' => (int) $it->object_id, 'order' => (int) $it->menu_order];
                }
                $out[] = ['id' => (int) $menu->term_id, 'name' => $menu->name, 'locations' => array_keys(array_filter($locations, function ($id) use ($menu) { return (int) $id === (int) $menu->term_id; })),
                          'items' => $items];
            }
            return $out;
        }

        /* ==============================================================================================
         *  uploads: a paged manifest, walked in sorted order so a cursor is just the last path
         * ============================================================================================== */

        private static function uploadsBase()
        {
            $u    = wp_upload_dir(null, false);
            $base = isset($u['basedir']) ? realpath($u['basedir']) : false;
            return $base !== false ? $base : null;
        }

        private static function uploads($cursor, $limit)
        {
            $base = self::uploadsBase();
            if ($base === null) {
                return ['items' => [], 'next_cursor' => null, 'base_url' => null];
            }
            $after   = isset($cursor['after']) ? (string) $cursor['after'] : '';
            $items   = [];
            $skipped = [];
            if (self::privateDirReason($base, '') !== null) {
                // the whole uploads folder is closed by a deny rule: nothing in it is public
                $skipped[] = ['path' => '', 'reason' => 'denied-by-server-rule', 'files' => self::countFiles($base)];
            } else {
                self::walk($base, '', $after, $limit, $items, $skipped);
            }
            $items = self::markAttachments($items);
            $u = wp_upload_dir(null, false);
            return ['items' => $items, 'next_cursor' => count($items) >= $limit ? ['after' => end($items)['path']] : null,
                    'base_url' => (string) ($u['baseurl'] ?? ''), 'skipped' => $skipped];
        }

        /**
         * Folders plugins keep private inside uploads (paid downloads, form attachments, logs, invoices, backups):
         * never listed, never served, whatever their own protection looks like. Matched on any path segment,
         * case-insensitively; PRIVATE_DIR_PREFIXES by prefix.
         */
        const PRIVATE_DIRS = ['woocommerce_uploads', 'wc-logs', 'gravity_forms', 'wpforms', 'wpcf7_uploads', 'fluentform', 'formidable', 'ninja-forms',
                              'wpo_wcpdf', 'edd', 'sucuri', 'wflogs', 'ithemes-security', 'wp-security-audit-log', 'wp-file-manager-pro', 'mainwp',
                              'backup', 'backups'];
        const PRIVATE_DIR_PREFIXES = ['backwpup-', 'updraft', 'ai1wm', 'wp-migrate-db', 'backupbuddy', 'pb_backupbuddy', 'wpvivid', 'duplicator', 'wp-staging', 'snapshots'];
        /** Files that are never page assets: database dumps, logs, keys, backup archives (also compressed). */
        // .csv / .xml: entry and order exports, WordPress export files; a page that links one still gets it by reference (design §3.4)
        const PRIVATE_FILE_RX = '/\.(sql|log|bak|backup|old|orig|swp|env|key|pem|dump|sqlite|db|wpress|daf|csv|xml)(\.(gz|zip|bz2|xz|7z|tgz))?$/i';
        const SKIPPED_MAX = 200;

        /** Why a folder must not be exported, or null. A folder protected by its own deny rule stays protected. */
        private static function privateDirReason($full, $name)
        {
            static $cache = [];
            if (isset($cache[$full])) return $cache[$full];
            $lname  = strtolower($name);
            $reason = null;
            if (in_array($lname, self::PRIVATE_DIRS, true)) {
                $reason = 'private-folder';
            } else {
                foreach (self::PRIVATE_DIR_PREFIXES as $pre) {
                    if (strpos($lname, $pre) === 0) { $reason = 'private-folder'; break; }
                }
            }
            if ($reason === null) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
                $ht = is_file($full . '/.htaccess') ? (string) @file_get_contents($full . '/.htaccess', false, null, 0, 65536) : '';
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
                $wc = is_file($full . '/web.config') ? (string) @file_get_contents($full . '/web.config', false, null, 0, 65536) : '';
                if (self::blanketDeny($ht) || preg_match('/<deny\s+users\s*=\s*"\*"|<authorization>[\s\S]*?<deny/i', $wc)) {
                    $reason = 'denied-by-server-rule';
                }
            }
            return $cache[$full] = $reason;
        }

        /**
         * Does this .htaccess close the whole folder? Rules scoped to some files (<Files *.php>, <FilesMatch>:
         * security plugins put "no PHP in uploads" there) do not count, so they are removed first.
         */
        public static function blanketDeny($htaccess)
        {
            $ht = preg_replace('#<(Files|FilesMatch|Limit|LimitExcept)\b[^>]*>.*?</\1>#is', '', (string) $htaccess);
            return (bool) preg_match('/^\s*(deny\s+from\s+all|require\s+all\s+denied)\b/im', (string) $ht)
                || (bool) preg_match('/^\s*RewriteRule\s+(\^?\.\*\$?|\^)\s+-\s+\[[^\]]*\bF\b/im', (string) $ht);
        }

        private static function privateFile($name)
        {
            return (bool) preg_match(self::PRIVATE_FILE_RX, $name);
        }

        /** Files under a skipped folder, counted for the report (capped, so a huge folder costs little). */
        private static function countFiles($dir, $cap = 10000)
        {
            $n = 0;
            try {
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
                foreach ($it as $f) {
                    if ($f->isFile() && $f->getFilename()[0] !== '.' && ++$n >= $cap) break;   // dotfiles (.htaccess) are not content
                }
            } catch (Throwable $e) {
                // unreadable: reported as 0
            }
            return $n;
        }

        /** Files a converted site may need: no PHP, no dotfiles, nothing that resolves outside the uploads folder. */
        private static function allowedName($name)
        {
            return $name !== '' && $name[0] !== '.' && !preg_match('/\.(php\d?|phtml|phar|phps|cgi|pl|py|sh|ini)$/i', $name);
        }

        /**
         * Sorted, cursor-resumable walk. What is left out on purpose (private folders, folders with a deny rule,
         * dumps / logs / keys) goes into $skipped, each once: a folder is reported on the page that passes it.
         */
        private static function walk($dir, $rel, $after, $limit, array &$items, array &$skipped)
        {
            $names = @scandir($dir);
            if ($names === false) return;
            sort($names, SORT_STRING);
            foreach ($names as $name) {
                if (count($items) >= $limit) return;
                if ($name === '.' || $name === '..' || !self::allowedName($name)) continue;
                $path = $rel === '' ? $name : $rel . '/' . $name;
                $full = $dir . DIRECTORY_SEPARATOR . $name;
                if (is_link($full)) continue;
                if (is_dir($full)) {
                    // skip whole folders that sort before the cursor, unless the cursor is inside them
                    if ($after !== '' && strcmp($path, $after) < 0 && strpos($after, $path . '/') !== 0) continue;
                    $why = self::privateDirReason($full, $name);
                    if ($why !== null) {
                        if (count($skipped) < self::SKIPPED_MAX) $skipped[] = ['path' => $path, 'reason' => $why, 'files' => self::countFiles($full)];
                        continue;
                    }
                    self::walk($full, $path, $after, $limit, $items, $skipped);
                } elseif (is_file($full)) {
                    if ($after !== '' && strcmp($path, $after) <= 0) continue;
                    if (self::privateFile($name)) {
                        if (count($skipped) < self::SKIPPED_MAX) $skipped[] = ['path' => $path, 'reason' => 'private-file', 'files' => 1];
                        continue;
                    }
                    // no hashing here: a page of large files must not time out the customer's site; size + mtime
                    // resume a copy, and exportFile sends the sha1 with a file's first chunk
                    $items[] = ['path' => $path, 'size' => (int) filesize($full), 'mtime' => (int) filemtime($full)];
                }
            }
        }

        /**
         * `attachment_id` on each file of the page that is a media-library attachment's original (also the
         * pre-"-scaled" original) or one of its generated sizes; null for every other file. One query per page:
         * a size's original is found by its name without the -WxH / -scaled / -rotated suffix, then the
         * attachment's metadata confirms the size really belongs to it.
         */
        private static function markAttachments(array $items)
        {
            global $wpdb;
            $cand = [];
            foreach ($items as $i => $it) {
                $items[$i]['attachment_id'] = null;
                $p = $it['path'];
                if (!preg_match('/\.(jpe?g|png|gif|webp|avif|bmp|tiff?|svg|pdf|mp4|m4v|mov|webm|mp3|m4a|ogg|wav|docx?|xlsx?|pptx?|zip)$/i', $p)) continue;
                $base = preg_replace('/-\d+x\d+(?=\.[^.\/]+$)/', '', $p);
                foreach (array_unique([$p, $base, preg_replace('/-(scaled|rotated)(?=\.[^.\/]+$)/', '', $base),
                                       preg_replace('/(?=\.[^.\/]+$)/', '-scaled', $base, 1)]) as $c) {
                    $cand[$c][] = $i;
                }
            }
            if (!$cand) return $items;
            $keys = array_keys($cand);
            $ph   = implode(',', array_fill(0, count($keys), '%s'));
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $rows = $wpdb->get_results($wpdb->prepare("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value IN ($ph)", $keys), ARRAY_A);
            foreach ($rows as $r) {
                $id    = (int) $r['post_id'];
                $orig  = (string) $r['meta_value'];
                $dir   = dirname($orig) === '.' ? '' : dirname($orig) . '/';
                $meta  = wp_get_attachment_metadata($id);
                $files = [$orig];
                if (is_array($meta)) {
                    if (!empty($meta['original_image'])) $files[] = $dir . $meta['original_image'];
                    foreach ((array) ($meta['sizes'] ?? []) as $sz) {
                        if (!empty($sz['file'])) $files[] = $dir . $sz['file'];
                    }
                }
                foreach ($cand[$orig] ?? [] as $i) {
                    if (in_array($items[$i]['path'], $files, true)) $items[$i]['attachment_id'] = $id;
                }
            }
            return $items;
        }

        /** The real path of one upload, or null when it is not a plain file inside the uploads folder. */
        public static function resolveUpload($path)
        {
            $base = self::uploadsBase();
            $path = str_replace('\\', '/', (string) $path);
            if ($base === null || $path === '' || strpos($path, "\0") !== false || $path[0] === '/' || preg_match('#(^|/)\.\.?(/|$)#', $path)) {
                return null;
            }
            foreach (explode('/', $path) as $segment) {
                if (!self::allowedName($segment)) return null;
            }
            $real = realpath($base . DIRECTORY_SEPARATOR . $path);
            if ($real === false || strpos($real, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($real) || self::privateFile(basename($real))) {
                return null;
            }
            // the same folders the manifest leaves out are never served, checked on the real path, folder by folder
            if (self::privateDirReason($base, '') !== null) return null;
            $dir = $base;
            foreach (explode('/', substr(dirname($real), strlen($base) + 1)) as $segment) {
                if ($segment === '') continue;
                $dir .= DIRECTORY_SEPARATOR . $segment;
                if (self::privateDirReason($dir, $segment) !== null) return null;
            }
            return $real;
        }

        /* ==============================================================================================
         *  prepare: fresh, consistent builder CSS and empty page caches before the crawl
         * ============================================================================================== */

        private static function prepare()
        {
            $done = ['elementor_css' => null, 'divi_cache' => null, 'page_caches' => []];

            if (class_exists('\Elementor\Plugin') && isset(\Elementor\Plugin::$instance->files_manager)) {
                self::safe(function () { \Elementor\Plugin::$instance->files_manager->clear_cache(); return ''; });
                $done['elementor_css'] = ['cleared' => true, 'print_method' => (string) get_option('elementor_css_print_method', 'external')];
            }
            if (class_exists('ET_Core_PageResource')) {
                self::safe(function () { ET_Core_PageResource::remove_static_resources('all', 'all'); return ''; });
                $done['divi_cache'] = ['cleared' => true];
            }

            $caches = [
                'object-cache'     => function () { return function_exists('wp_cache_flush') ? wp_cache_flush() : null; },
                'w3-total-cache'   => function () { return function_exists('w3tc_flush_all') ? (w3tc_flush_all() ?? true) : null; },
                'wp-super-cache'   => function () { return function_exists('wp_cache_clear_cache') ? (wp_cache_clear_cache() ?? true) : null; },
                'wp-rocket'        => function () { return function_exists('rocket_clean_domain') ? (rocket_clean_domain() ?? true) : null; },
                'litespeed-cache'  => function () { return defined('LSCWP_V') ? (do_action('litespeed_purge_all') ?? true) : null; },
                'wp-fastest-cache' => function () { return isset($GLOBALS['wp_fastest_cache']) && method_exists($GLOBALS['wp_fastest_cache'], 'deleteCache') ? ($GLOBALS['wp_fastest_cache']->deleteCache(true) ?? true) : null; },
                'autoptimize'      => function () { return class_exists('autoptimizeCache') ? (autoptimizeCache::clearall() ?? true) : null; },
                'sg-optimizer'     => function () { return function_exists('sg_cachepress_purge_cache') ? (sg_cachepress_purge_cache() ?? true) : null; },
                'breeze'           => function () { return defined('BREEZE_VERSION') ? (do_action('breeze_clear_all_cache') ?? true) : null; },
                'cache-enabler'    => function () { return class_exists('Cache_Enabler') ? (do_action('cache_enabler_clear_complete_cache') ?? true) : null; },
            ];
            foreach ($caches as $name => $fn) {
                try {
                    if ($fn() !== null) $done['page_caches'][] = $name;
                } catch (Throwable $e) {
                    // a cache plugin that throws is reported as not cleared
                }
            }
            return $done;
        }

        /* ==============================================================================================
         *  crawl_pass: a per-job secret; requests that prove it are let past WordPress-level firewalls
         * ============================================================================================== */

        private static function crawlPass($job, $ttl)
        {
            if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $job)) {
                return ['error' => 'A job id of 1-64 characters (letters, digits, _ and -) is required.'];
            }
            $ttl     = $ttl > 0 ? min($ttl, self::MAX_PASS_TTL) : 3600;
            $secret  = wp_generate_password(48, false, false);
            $expires = time() + $ttl;
            set_transient(self::PASS_TRANSIENT . md5($job), ['secret' => $secret, 'expires' => $expires], $ttl);
            return ['job' => $job, 'expires' => $expires, 'secret' => $secret, 'header' => 'X-Xagio-Crawl'];
        }

        /** Is this request our crawler, for a job with a live pass? Header: <job>.<ts>.<hex hmac(secret, job|ts)>. */
        public static function isVerifiedCrawler()
        {
            static $verdict = null;
            if ($verdict !== null) return $verdict;
            $verdict = false;
            $h = isset($_SERVER['HTTP_X_XAGIO_CRAWL']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_XAGIO_CRAWL'])) : '';
            if ($h === '' || !preg_match('/^([A-Za-z0-9_-]{1,64})\.(\d{9,11})\.([a-f0-9]{64})$/', $h, $m)) return false;
            if (abs(time() - (int) $m[2]) > self::CLOCK_SKEW) return false;
            $pass = get_transient(self::PASS_TRANSIENT . md5($m[1]));
            if (!is_array($pass) || empty($pass['secret']) || (int) ($pass['expires'] ?? 0) < time()) return false;
            $verdict = hash_equals(hash_hmac('sha256', $m[1] . '|' . $m[2], (string) $pass['secret']), $m[3]);
            return $verdict;
        }

        /**
         * For a verified crawler request only: tell the WordPress-level firewalls that offer a per-request hook
         * not to lock out or ban it. Checked against their code (2026-11-13): Solid Security (iThemes) has
         * `itsec_white_ips` (its allow list) and `itsec_do_lockout`. Wordfence and All-In-One Security have no
         * such hook (their allow lists are stored settings, and their firewalls can run before WordPress), so
         * they are not touched: the pre-flight reports them. Nothing in front of WordPress (Cloudflare, the
         * host) is affected either.
         */
        public static function applyCrawlPass()
        {
            if (empty($_SERVER['HTTP_X_XAGIO_CRAWL']) || !self::isVerifiedCrawler()) {
                return;
            }
            $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
            if ($ip !== '') {
                add_filter('itsec_white_ips', function ($ips) use ($ip) {
                    $ips   = is_array($ips) ? $ips : [];
                    $ips[] = $ip;
                    return $ips;
                }, 99);
            }
            add_filter('itsec_do_lockout', '__return_false', 99);
        }
    }
}
