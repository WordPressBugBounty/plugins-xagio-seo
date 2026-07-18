<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_SITEMAPS')) {

    class XAGIO_MODEL_SITEMAPS
    {

        private static function defines()
        {
            define('XAGIO_ENABLE_SITEMAPS', filter_var(get_option('XAGIO_ENABLE_SITEMAPS'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_SITEMAP_COMPRESSION', filter_var(get_option('XAGIO_SITEMAP_COMPRESSION'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_CACHE_SITEMAPS', filter_var(get_option('XAGIO_CACHE_SITEMAPS'), FILTER_VALIDATE_BOOLEAN));
        }

        public static function initialize()
        {
            self::defines();

            add_action('template_redirect', ['XAGIO_MODEL_SITEMAPS', 'displaySitemap'], 99);

            if (get_option('XAGIO_ENABLE_SITEMAPS') == true) {
                add_filter('wp_sitemaps_enabled', '__return_false');

                // Advertise the sitemap via a Link header so header-scanning agents find it without parsing HTML.
                add_action('send_headers', ['XAGIO_MODEL_SITEMAPS', 'sendSitemapLinkHeader']);

                // Claim the conventional /sitemap.xml endpoint (301 -> our index) when it's safe
                // (absent / already Xagio-controlled) or the user explicitly opted in. We hook
                // parse_request (structurally before any template_redirect handler) so we win over
                // other SEO plugins that redirect /sitemap.xml on template_redirect (e.g. Yoast at
                // priority 0). Runs long before the 404 logger (wp_enqueue_scripts) as well.
                add_action('parse_request', ['XAGIO_MODEL_SITEMAPS', 'maybeRedirectRootSitemap'], 0);

                // Content changes -> invalidate sitemap cache
                add_action('save_post', ['XAGIO_MODEL_SITEMAPS', 'onSavePostInvalidate'], 20, 3);
                add_action('transition_post_status', ['XAGIO_MODEL_SITEMAPS', 'onTransitionPostStatusInvalidate'], 10, 3);

                add_action('trashed_post', ['XAGIO_MODEL_SITEMAPS', 'invalidateSitemaps']);
                add_action('untrashed_post', ['XAGIO_MODEL_SITEMAPS', 'invalidateSitemaps']);
                add_action('deleted_post', ['XAGIO_MODEL_SITEMAPS', 'invalidateSitemaps']);

                // Taxonomy changes -> invalidate (only if taxonomy enabled in your sitemap settings)
                add_action('created_term', ['XAGIO_MODEL_SITEMAPS', 'onTermChangeInvalidate'], 10, 3);
                add_action('edited_term', ['XAGIO_MODEL_SITEMAPS', 'onTermChangeInvalidate'], 10, 3);
                add_action('delete_term', ['XAGIO_MODEL_SITEMAPS', 'onTermChangeInvalidate'], 10, 4);

                // Lazy rebuild on next request (NOT in displaySitemap)
                add_action('shutdown', ['XAGIO_MODEL_SITEMAPS', 'maybeRebuildSitemaps'], 0);
            }

            if (!XAGIO_HAS_ADMIN_PERMISSIONS) return;

            add_action('admin_post_xagio_sitemaps_settings', ['XAGIO_MODEL_SITEMAPS', 'saveSitemapSettings']);
            add_action('admin_post_xagio_content_settings', ['XAGIO_MODEL_SITEMAPS', 'saveContentSettings']);
            add_action('admin_post_xagio_get_sitemaps', ['XAGIO_MODEL_SITEMAPS', 'getSitemaps']);

            // /sitemap.xml "make Xagio primary" opt-in (AJAX toggle on the Sitemaps page).
            // The foreign-owner warning lives inside the Sitemaps page (see page.php), not as a
            // global WordPress dashboard notice.
            add_action('admin_post_xagio_sitemap_claim_root', ['XAGIO_MODEL_SITEMAPS', 'saveClaimRoot']);
        }

        public static function sendSitemapLinkHeader()
        {
            if (is_admin() || is_feed() || is_robots()) return;
            if (headers_sent()) return;

            $url = esc_url_raw(home_url('/sitemap-xag.xml'));
            header('Link: <' . $url . '>; rel="sitemap"', false);
        }

        public static function invalidateSitemaps()
        {
            if (!get_option('XAGIO_ENABLE_SITEMAPS')) return;

            if (get_transient('xagio_sitemaps_invalidate_debounce')) return;
            set_transient('xagio_sitemaps_invalidate_debounce', 1, 30);

            update_option('xagio_sitemaps_needs_rebuild', 1, false);
        }

        public static function onSavePostInvalidate($post_id, $post, $update)
        {
            if (!get_option('XAGIO_ENABLE_SITEMAPS')) return;

            if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
            if (!$post) return;

            $pt  = get_post_type($post_id);
            $pto = $pt ? get_post_type_object($pt) : null;

            if (!$pto || empty($pto->public)) return;

            if ($post->post_status === 'publish') {
                self::invalidateSitemaps();
            }
        }

        public static function onTransitionPostStatusInvalidate($new_status, $old_status, $post)
        {
            if (!get_option('XAGIO_ENABLE_SITEMAPS')) return;
            if (!$post) return;
            if ($new_status === $old_status) return;

            if ($new_status === 'publish' || $old_status === 'publish') {
                self::invalidateSitemaps();
            }
        }

        public static function onTermChangeInvalidate($term_id, $tt_id = null, $taxonomy = null, $deleted_term = null)
        {
            if (!get_option('XAGIO_ENABLE_SITEMAPS')) return;

            $settings = get_option('XAGIO_SITEMAP_CONTENT_SETTINGS');
            if (is_array($settings) && isset($settings['taxonomies']) && is_string($taxonomy)) {
                if (empty($settings['taxonomies'][$taxonomy]['enabled'])) {
                    return;
                }
            }

            self::invalidateSitemaps();
        }

        public static function maybeRebuildSitemaps()
        {
            if (!get_option('XAGIO_ENABLE_SITEMAPS')) return;

            if (defined('DOING_AJAX') && DOING_AJAX) return;
            if (defined('DOING_CRON') && DOING_CRON) return;
            if (defined('REST_REQUEST') && REST_REQUEST) return;

            $needs = (int) get_option('xagio_sitemaps_needs_rebuild', 0);
            if (!$needs) return;

            if (get_transient('xagio_sitemaps_rebuild_lock')) return;
            set_transient('xagio_sitemaps_rebuild_lock', 1, 120);

            try {
                $file_mode = filter_var(get_option('XAGIO_CACHE_SITEMAPS'), FILTER_VALIDATE_BOOLEAN);

                if ($file_mode) {
                    $xagio_files = glob(ABSPATH . 'sitemap-xag*.xml');
                    foreach ($xagio_files as $xagio_file) {
                        wp_delete_file($xagio_file);
                    }
                } else {
                    delete_transient('xagio_sitemaps');
                }

                self::createSitemap();

                update_option('xagio_sitemaps_needs_rebuild', 0, false);

            } finally {
                delete_transient('xagio_sitemaps_rebuild_lock');
            }
        }

        public static function saveSitemapSettings()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['XAGIO_ENABLE_SITEMAPS'], $_POST['XAGIO_SITEMAP_COMPRESSION'], $_POST['XAGIO_CACHE_SITEMAPS'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $XAGIO_ENABLE_SITEMAPS     = intval($_POST['XAGIO_ENABLE_SITEMAPS']);
            $XAGIO_SITEMAP_COMPRESSION = intval($_POST['XAGIO_SITEMAP_COMPRESSION']);
            $XAGIO_CACHE_SITEMAPS      = intval($_POST['XAGIO_CACHE_SITEMAPS']);

            update_option('XAGIO_ENABLE_SITEMAPS', $XAGIO_ENABLE_SITEMAPS);
            update_option('XAGIO_SITEMAP_COMPRESSION', $XAGIO_SITEMAP_COMPRESSION);
            update_option('XAGIO_CACHE_SITEMAPS', $XAGIO_CACHE_SITEMAPS);

            if ($XAGIO_ENABLE_SITEMAPS) {
                $db_values = get_option("XAGIO_SITEMAP_CONTENT_SETTINGS");

                $db_values['post_types']['post']['enabled']     = "1";
                $db_values['post_types']['page']['enabled']     = "1";
                $db_values['taxonomies']['category']['enabled'] = "1";
                $db_values['taxonomies']['post_tag']['enabled'] = "1";

                update_option('XAGIO_SITEMAP_CONTENT_SETTINGS', $db_values);
            }

            if ($XAGIO_CACHE_SITEMAPS) {
                delete_transient('xagio_sitemaps');
            } else {
                $xagio_files = glob(ABSPATH . 'sitemap-xag*.xml');
                foreach ($xagio_files as $xagio_file) {
                    wp_delete_file($xagio_file);
                }
            }

            // Detection depends on the live endpoint state, which just changed — drop the
            // cached /sitemap.xml probe so the next page load re-detects the owner.
            delete_option('XAGIO_SITEMAP_ROOT_STATUS');

            if ($XAGIO_ENABLE_SITEMAPS) {
                XAGIO_MODEL_SITEMAPS::createSitemap();
            } else {
                XAGIO_MODEL_SITEMAPS::deleteSitemap();
                // With sitemaps off there is no /sitemap-xag.xml to claim /sitemap.xml for,
                // so also drop the "make Xagio primary" opt-in.
                self::applyClaimRoot('0');
            }

            xagio_json('success', 'Sitemap settings saved successfully.');
        }

        public static function getSitemaps()
        {
            $sitemaps = [];
            $return   = false;

            if (isset($_GET['return'])) {
                $return = true;
            }

            if (XAGIO_ENABLE_SITEMAPS) {
                if (XAGIO_CACHE_SITEMAPS) {
                    // get sitemaps from cache
                    $sitemaps      = glob(ABSPATH . 'sitemap-xag*.xml');
                    $sitemap_array = [];
                    foreach ($sitemaps as $sitemap) {
                        $sitemap_array[basename($sitemap)] = xagio_file_get_contents($sitemap);
                    }
                    $sitemaps = $sitemap_array;
                } else {
                    $sitemaps = get_transient('xagio_sitemaps');
                    if (empty($sitemaps)) {
                        self::createSitemap();
                        $sitemaps = get_transient('xagio_sitemaps');
                    }
                }

                // sort sitemaps by key length
                uksort($sitemaps, function ($a, $b) {
                    return strlen($a) <=> strlen($b);
                });
            }

            if (!$return) {
                return $sitemaps;
            } else {
                xagio_json('success', 'Sitemaps successfully retrieved', $sitemaps);
            }
        }

        public static function saveContentSettings()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['values'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $VALUES = map_deep(wp_unslash($_POST['values']), 'sanitize_text_field');
            update_option('XAGIO_SITEMAP_CONTENT_SETTINGS', $VALUES);

            XAGIO_MODEL_SITEMAPS::createSitemap();

            xagio_json('success', 'Content settings saved successfully.');
        }

        public static function createSitemap()
        {
            $XAGIO_CACHE_SITEMAPS = filter_var(get_option('XAGIO_CACHE_SITEMAPS'), FILTER_VALIDATE_BOOLEAN);

            $sitemaps         = [];
            $CONTENT_SETTINGS = get_option('XAGIO_SITEMAP_CONTENT_SETTINGS');

            if (empty($CONTENT_SETTINGS)) {
                $CONTENT_SETTINGS = [
                    'post_types' => [],
                    'taxonomies' => []
                ];
            }

            foreach ($CONTENT_SETTINGS['post_types'] as $post_type => $settings) {
                if ($settings['enabled']) {

                    $settings['type'] = 'post_type';
                    $sitemap          = self::generateSitemap($post_type, $settings);

                    $filename = 'sitemap-xagio-' . $post_type . '.xml';

                    if (empty($sitemap)) {

                        if ($XAGIO_CACHE_SITEMAPS) {
                            wp_delete_file(ABSPATH . $filename);
                        } else {
                            $db_sitemaps = get_transient('xagio_sitemaps');
                            if (!is_array($db_sitemaps))
                                $db_sitemaps = [];
                            unset($db_sitemaps[$filename]);
                            set_transient('xagio_sitemaps', $db_sitemaps, 60 * 60 * 24);
                        }

                    } else {

                        if ($XAGIO_CACHE_SITEMAPS) {
                            xagio_file_put_contents(ABSPATH . $filename, $sitemap);
                        } else {
                            $db_sitemaps = get_transient('xagio_sitemaps');
                            if (!is_array($db_sitemaps))
                                $db_sitemaps = [];
                            $db_sitemaps[$filename] = $sitemap;
                            set_transient('xagio_sitemaps', $db_sitemaps, 60 * 60 * 24);
                        }

                        $sitemaps[] = get_site_url() . '/' . $filename;

                    }

                } else {
                    $filename = 'sitemap-xagio-' . $post_type . '.xml';

                    if ($XAGIO_CACHE_SITEMAPS) {
                        wp_delete_file(ABSPATH . $filename);
                    } else {
                        $db_sitemaps = get_transient('xagio_sitemaps');
                        if (!is_array($db_sitemaps))
                            $db_sitemaps = [];
                        if (isset($db_sitemaps[$filename])) {
                            unset($db_sitemaps[$filename]);
                            set_transient('xagio_sitemaps', $db_sitemaps, 60 * 60 * 24);
                        }
                    }
                }
            }

            foreach ($CONTENT_SETTINGS['taxonomies'] as $taxonomy => $settings) {
                if ($settings['enabled']) {

                    $settings['type'] = 'taxonomy';
                    $sitemap          = self::generateSitemap($taxonomy, $settings);

                    $filename = 'sitemap-xagio-' . $taxonomy . '.xml';

                    if (empty($sitemap)) {

                        if ($XAGIO_CACHE_SITEMAPS) {
                            wp_delete_file(ABSPATH . $filename);
                        } else {
                            $db_sitemaps = get_transient('xagio_sitemaps');
                            if (!is_array($db_sitemaps))
                                $db_sitemaps = [];
                            unset($db_sitemaps[$filename]);
                            set_transient('xagio_sitemaps', $db_sitemaps, 60 * 60 * 24);
                        }

                    } else {

                        if ($XAGIO_CACHE_SITEMAPS) {
                            xagio_file_put_contents(ABSPATH . $filename, $sitemap);
                        } else {
                            $db_sitemaps = get_transient('xagio_sitemaps');
                            if (!is_array($db_sitemaps))
                                $db_sitemaps = [];
                            $db_sitemaps[$filename] = $sitemap;
                            set_transient('xagio_sitemaps', $db_sitemaps, 60 * 60 * 24);
                        }

                        $sitemaps[] = get_site_url() . '/' . $filename;

                    }

                } else {
                    $filename = 'sitemap-xagio-' . $taxonomy . '.xml';

                    if ($XAGIO_CACHE_SITEMAPS) {
                        wp_delete_file(ABSPATH . $filename);
                    } else {
                        $db_sitemaps = get_transient('xagio_sitemaps');
                        if (!is_array($db_sitemaps))
                            $db_sitemaps = [];
                        if (isset($db_sitemaps[$filename])) {
                            unset($db_sitemaps[$filename]);
                            set_transient('xagio_sitemaps', $db_sitemaps, 60 * 60 * 24);
                        }
                    }
                }
            }

            // OKF child sitemap: present only when OKF is enabled and has eligible URLs.
            $okf_filename = 'sitemap-xagio-okf.xml';
            $okf_sitemap  = self::generateOkfSitemap();

            if (empty($okf_sitemap)) {

                if ($XAGIO_CACHE_SITEMAPS) {
                    wp_delete_file(ABSPATH . $okf_filename);
                } else {
                    $db_sitemaps = get_transient('xagio_sitemaps');
                    if (!is_array($db_sitemaps))
                        $db_sitemaps = [];
                    if (isset($db_sitemaps[$okf_filename])) {
                        unset($db_sitemaps[$okf_filename]);
                        set_transient('xagio_sitemaps', $db_sitemaps, 60 * 60 * 24);
                    }
                }

            } else {

                if ($XAGIO_CACHE_SITEMAPS) {
                    xagio_file_put_contents(ABSPATH . $okf_filename, $okf_sitemap);
                } else {
                    $db_sitemaps = get_transient('xagio_sitemaps');
                    if (!is_array($db_sitemaps))
                        $db_sitemaps = [];
                    $db_sitemaps[$okf_filename] = $okf_sitemap;
                    set_transient('xagio_sitemaps', $db_sitemaps, 60 * 60 * 24);
                }

                $sitemaps[] = get_site_url() . '/' . $okf_filename;

            }

            $sitemapindex          = self::generateSiteMapIndex($sitemaps);
            $sitemapindex_filename = 'sitemap-xag.xml';
            if ($XAGIO_CACHE_SITEMAPS) {
                xagio_file_put_contents(ABSPATH . $sitemapindex_filename, $sitemapindex);
            } else {
                $db_sitemaps                         = get_transient('xagio_sitemaps');
                $db_sitemaps[$sitemapindex_filename] = $sitemapindex;
                set_transient('xagio_sitemaps', $db_sitemaps, 60 * 60 * 24);
            }
        }

        public static function deleteSitemap()
        {
            delete_transient('xagio_sitemaps');
            $xagio_files = glob(ABSPATH . 'sitemap-xag*.xml');
            foreach ($xagio_files as $xagio_file) {
                wp_delete_file($xagio_file);
            }
        }

        public static function displaySitemap()
        {
            $allowed_tags = [
                'sitemapindex' => [
                    'xmlns:xsi'          => [],
                    'xsi:schemalocation' => [],
                    'xmlns'              => []
                ],
                'urlset'       => [
                    'xmlns:xsi'          => [],
                    'xsi:schemalocation' => [],
                    'xmlns'              => []
                ],
                'sitemap'      => [],
                'url'          => [],
                'loc'          => [],
                'lastmod'      => [],
                'changefreq'   => [],
                'priority'     => [],
            ];

            if (!isset($_SERVER['REQUEST_URI'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $request_url = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));


            if (strpos($request_url, 'sitemap') !== false && strpos($request_url, '.xml') !== false && get_option('XAGIO_ENABLE_SITEMAPS')) {
                $sitemaps = self::getSitemaps();
                $filename = basename(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])));

                // Never force a 404 on the conventional /sitemap.xml endpoint. It's either
                // handled by maybeRedirectRootSitemap (301 -> our index) or intentionally
                // left to another SEO plugin that owns it. We only serve our own files.
                if (strtok($filename, '?') === 'sitemap.xml') {
                    return;
                }
                if (isset($sitemaps[$filename])) {

                    http_response_code(200);
                    header('Content-Type: application/xml');

                    // TODO - not sure if this is good solution for outputing XMLs? I guess it is?
                    echo wp_kses($sitemaps[$filename], $allowed_tags);
                    exit;

                } else {

                    http_response_code(404);
                    header('Content-Type: text/plain');
                    echo '404 Not Found';
                    exit;

                }

            }

        }

        // ---- /sitemap.xml root claim (301 to sitemap-xag.xml) -------------

        /**
         * Name a known SEO plugin that commonly owns or redirects /sitemap.xml.
         * Cheap, synchronous — safe to call on the front-end. Returns '' if none.
         * (WordPress core uses /wp-sitemap.xml, so it is NOT a conflict here.)
         */
        public static function knownSitemapPluginActive()
        {
            if (defined('WPSEO_VERSION') || class_exists('WPSEO_Sitemaps'))            return 'Yoast SEO';
            if (defined('RANK_MATH_VERSION') || class_exists('RankMath\\Sitemap\\Sitemap')) return 'Rank Math';
            if (defined('AIOSEO_VERSION') || function_exists('aioseo'))                return 'All in One SEO';
            if (defined('THE_SEO_FRAMEWORK_VERSION') || class_exists('The_SEO_Framework\\Load')) return 'The SEO Framework';
            if (defined('SEOPRESS_VERSION'))                                           return 'SEOPress';
            return '';
        }

        /**
         * Plugin-agnostic: does ANY foreign rewrite rule claim /sitemap.xml?
         * Scans WordPress's registered rewrite rules for a pattern that matches the
         * literal path "sitemap.xml" but is neither ours (xagio_root_sitemap) nor
         * core's (wp-sitemap). Synchronous and front-end safe — catches every plugin
         * that routes /sitemap.xml via a rewrite, not just the named ones above.
         * Returns true when a foreign sitemap rewrite is present.
         */
        public static function foreignSitemapRewrite()
        {
            $rules = get_option('rewrite_rules');
            if (!is_array($rules) || empty($rules)) return false;

            foreach ($rules as $pattern => $query) {
                if (!is_string($pattern) || $pattern === '') continue;

                // Only consider rules that are actually about a sitemap endpoint.
                if (stripos($pattern, 'sitemap') === false) continue;

                $q = is_string($query) ? $query : '';

                // Skip our own claim rule.
                if (stripos($q, 'xagio_root_sitemap') !== false) continue;

                // Skip WordPress core. Core's sitemap rules — including the legacy
                // "sitemap\.xml => index.php?sitemap=index" compat rule that 301s to
                // /wp-sitemap.xml — route to core's own sitemap query vars. Core is not a
                // competing plugin; per WP conventions /wp-sitemap.xml is the real endpoint,
                // so /sitemap.xml is safe for Xagio to claim.
                if (preg_match('#[?&](sitemap|sitemap-stylesheet|sitemap-subtype)=#', $q)) continue;
                if (stripos($pattern, 'wp-sitemap') !== false || stripos($pattern, 'wp_sitemap') !== false) continue;

                // Does this rule actually match the conventional /sitemap.xml request?
                $delimited = '#' . str_replace('#', '\\#', $pattern) . '#';
                if (@preg_match($delimited, 'sitemap.xml')) return true;
            }

            return false;
        }

        /**
         * Any-plugin foreign-owner signal, synchronous and front-end safe.
         * Combines the named-plugin list (fast, gives us a label) with the
         * plugin-agnostic rewrite scan so an unlisted plugin is still detected.
         */
        public static function foreignSitemapOwner()
        {
            return self::knownSitemapPluginActive() !== '' || self::foreignSitemapRewrite();
        }

        /**
         * Decide whether Xagio should take over /sitemap.xml for the current request.
         * Front-end safe: uses only options + cheap plugin signatures (no HTTP).
         *   - Explicit opt-in  -> always claim (force primary, even over another plugin).
         *   - Auto (no opt-in) -> claim only when nobody else owns /sitemap.xml.
         */
        public static function shouldClaimRootSitemap()
        {
            if (!get_option('XAGIO_ENABLE_SITEMAPS')) return false;

            if (get_option('XAGIO_SITEMAP_CLAIM_ROOT') === '1') return true;

            // Auto mode: claim unless another plugin owns /sitemap.xml. Both signals are
            // synchronous and front-end safe — the named-plugin list plus the
            // plugin-agnostic rewrite scan. We deliberately do NOT consult the cached HTTP
            // probe here: core's own /sitemap.xml -> /wp-sitemap.xml redirect is not a
            // competitor, and a stale probe must never suppress the claim.
            return !self::foreignSitemapOwner();
        }

        /**
         * 301 the conventional /sitemap.xml to our sitemap index when we should claim it.
         * Exact-path match only — never touches /sitemap-xag.xml, /wp-sitemap.xml, etc.
         */
        public static function maybeRedirectRootSitemap()
        {
            if (is_admin()) return;
            if (!isset($_SERVER['REQUEST_URI'])) return;

            $path = wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH);
            if (!is_string($path) || $path === '') return;

            $path = '/' . ltrim($path, '/');
            if (strtolower($path) !== '/sitemap.xml') return;

            if (!self::shouldClaimRootSitemap()) return;
            if (headers_sent()) return;

            wp_redirect(esc_url_raw(home_url('/sitemap-xag.xml')), 301);
            exit;
        }

        /**
         * Probe the live /sitemap.xml to determine its current owner and cache the result.
         * Admin-only (issues an HTTP request). owner: none | xagio | foreign | unknown.
         */
        public static function probeRootSitemap()
        {
            $url    = home_url('/sitemap.xml');
            $status = ['owner' => 'none', 'plugin' => '', 'http' => 0, 'checked' => time()];

            $response = wp_remote_get($url, [
                'timeout'     => 8,
                'redirection' => 0,
                'sslverify'   => false,
                'headers'     => ['Cache-Control' => 'no-cache', 'Pragma' => 'no-cache'],
            ]);

            if (is_wp_error($response)) {
                $status['owner'] = 'unknown';
                update_option('XAGIO_SITEMAP_ROOT_STATUS', $status, false);
                return $status;
            }

            $code           = (int) wp_remote_retrieve_response_code($response);
            $location       = (string) wp_remote_retrieve_header($response, 'location');
            $status['http'] = $code;

            if ($code === 404) {
                $status['owner'] = 'none';
            } else if ($code >= 300 && $code < 400 && $location !== '') {
                if (stripos($location, 'sitemap-xag.xml') !== false) {
                    // A redirect to our own index means Xagio already controls it.
                    $status['owner'] = 'xagio';
                } else if (stripos($location, 'wp-sitemap') !== false) {
                    // WordPress core redirects legacy /sitemap.xml to /wp-sitemap.xml.
                    // That's core, not a competing plugin — safe for us to claim.
                    $status['owner'] = 'none';
                } else {
                    $status['owner'] = 'foreign';
                }
            } else if ($code === 200) {
                // We never serve /sitemap.xml directly (we 301 it), so a 200 is a foreign generator.
                $status['owner'] = 'foreign';
            } else {
                $status['owner'] = 'unknown';
            }

            if ($status['owner'] === 'foreign') {
                // Prefer a real name; fall back to a generic label when an unlisted
                // plugin (detected only by its rewrite rule / HTTP response) owns it.
                $named = self::knownSitemapPluginActive();
                $status['plugin'] = $named !== '' ? $named : 'another plugin';
            }

            update_option('XAGIO_SITEMAP_ROOT_STATUS', $status, false);
            return $status;
        }

        /**
         * Refresh the cached probe if it's missing or older than $max_age seconds.
         * Called from the Sitemaps admin page so detection stays current without
         * hitting the network on every admin request.
         */
        public static function maybeProbeRootSitemap($max_age = 43200)
        {
            $status = get_option('XAGIO_SITEMAP_ROOT_STATUS');
            if (is_array($status) && isset($status['checked']) && (time() - (int) $status['checked']) < $max_age) {
                // Guard against a stale "foreign" verdict. If the cache blames a specific
                // named SEO plugin (e.g. "Yoast SEO") that is no longer active, the owner
                // has changed since we last probed — deactivating it left the cached result
                // pointing at a plugin that's gone. Re-probe now instead of showing a false
                // "another plugin controls /sitemap.xml" warning for up to $max_age seconds.
                // The generic "another plugin" label (unlisted, non-rewrite owners) is left
                // cached so we don't re-probe on every admin page load.
                if (($status['owner'] ?? '') === 'foreign'
                    && !empty($status['plugin'])
                    && $status['plugin'] !== 'another plugin'
                    && self::knownSitemapPluginActive() === '') {
                    return self::probeRootSitemap();
                }
                return $status;
            }
            return self::probeRootSitemap();
        }

        /**
         * AJAX toggle (Sitemaps page): enable/disable making Xagio the primary /sitemap.xml.
         */
        public static function saveClaimRoot()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $enable = (isset($_POST['XAGIO_SITEMAP_CLAIM_ROOT']) && intval($_POST['XAGIO_SITEMAP_CLAIM_ROOT'])) ? '1' : '0';
            self::applyClaimRoot($enable);

            $status = self::probeRootSitemap();

            xagio_json('success', $enable === '1'
                ? 'Xagio is now the primary /sitemap.xml.'
                : 'Xagio no longer claims /sitemap.xml.', [
                'claim'  => $enable,
                'status' => $status,
            ]);
        }

        /**
         * Persist the /sitemap.xml "make Xagio primary" opt-in. The redirect itself is
         * handled entirely on parse_request (maybeRedirectRootSitemap), so no rewrite rule
         * is needed. We flush once to drop any stale claim rewrite left by older versions.
         */
        private static function applyClaimRoot($enable)
        {
            update_option('XAGIO_SITEMAP_CLAIM_ROOT', $enable);

            if (get_option('XAGIO_SITEMAP_ROOT_REWRITE')) {
                delete_option('XAGIO_SITEMAP_ROOT_REWRITE');
                flush_rewrite_rules(false);
            }
        }

        public static function generateSitemapIndex($sitemaps)
        {
            // fetch the template from meta folder
            $xagio_template = xagio_file_get_contents(XAGIO_PATH . '/modules/sitemaps/meta/sitemapindex.xml');


            if (XAGIO_DISABLE_HTML_FOOTPRINT == FALSE) {
                // replace {{url}} with the site url and {{date}} with the current date in human-readable format
                $xagio_template = str_replace('{{url}}', get_site_url(), $xagio_template);
                $xagio_template = str_replace('{{date}}', gmdate('H:i:s F j, Y'), $xagio_template);
            } else {
                $xagio_template = explode("\n", $xagio_template);

                unset($xagio_template[2], $xagio_template[3]);

                // Reindex and save back to file
                $xagio_template = join("\n", array_values($xagio_template));
            }

            $sitemap = '';
            foreach ($sitemaps as $data) {
                $sitemap .= '<sitemap>' . "\n";
                $sitemap .= '<loc>' . esc_url($data) . '</loc>' . "\n";
                $sitemap .= '<lastmod>' . gmdate('Y-m-d') . '</lastmod>' . "\n";
                $sitemap .= '</sitemap>' . "\n";
            }

            if (empty($sitemap))
                return null;

            $xagio_template = str_replace('{{urls}}', $sitemap, $xagio_template);

            return $xagio_template;
        }

        public static function generateSitemap($xagio_value = 'post', $settings = [])
        {
            // fetch the template from meta folder
            $xagio_template = xagio_file_get_contents(XAGIO_PATH . '/modules/sitemaps/meta/sitemap.xml');

            if (XAGIO_DISABLE_HTML_FOOTPRINT == FALSE) {
                // replace {{url}} with the site url and {{date}} with the current date in human-readable format
                $xagio_template = str_replace('{{url}}', get_site_url(), $xagio_template);
                $xagio_template = str_replace('{{date}}', gmdate('H:i:s F j, Y'), $xagio_template);
            } else {
                $xagio_template = explode("\n", $xagio_template);

                unset($xagio_template[2], $xagio_template[3]);

                // Reindex and save back to file
                $xagio_template = join("\n", array_values($xagio_template));
            }

            // Set up an array to hold the data for our sitemap
            $sitemap_data = [];

            if ($settings['type'] == 'post_type') {

                // Get a list of all published posts
                $posts_args = array(
                    'post_type'      => $xagio_value,
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                );
                $posts      = get_posts($posts_args);

                // sort posts by url length
                usort($posts, function ($a, $b) {
                    return strlen(get_permalink($a->ID)) - strlen(get_permalink($b->ID));
                });

                if(!empty($settings['exclusions'])) {
                    $settings['exclusions'] = explode(',', $settings['exclusions']);
                } else {
                    $settings['exclusions'] = [];
                }

                // Add each post to the sitemap data array
                foreach ($posts as $post) {
                    if(!in_array($post->ID, $settings['exclusions'])) {
                        $sitemap_data[] = array(
                            'loc'        => get_permalink($post->ID),
                            'lastmod'    => get_the_modified_date('Y-m-d', $post->ID),
                            'priority'   => $settings['priority'] ?? "",
                            'changefreq' => $settings['change_frequency'] ?? ""
                        );
                    }
                }

            } else if ($settings['type'] == 'taxonomy') {

                $terms = get_terms(array(
                    'taxonomy'   => $xagio_value,
                    'hide_empty' => true,
                ));

                if (is_wp_error($terms) || !is_array($terms)) {
                    return null;
                }

                // sort taxomonies by url length
                usort($terms, function ($a, $b) {
                    return strlen(get_term_link($a->term_id)) - strlen(get_term_link($b->term_id));
                });

                foreach ($terms as $term) {
                    $sitemap_data[] = array(
                        'loc'        => get_term_link($term),
                        'priority'   => $settings['priority'] ?? "",
                        'changefreq' => $settings['change_frequency'] ?? ""
                    );
                }

            }

            // Output the sitemap data as an XML document
            $sitemap = '';
            foreach ($sitemap_data as $data) {
                $sitemap .= '<url>' . "\n";
                $sitemap .= '<loc>' . esc_url($data['loc']) . '</loc>' . "\n";
                if (!empty($data['lastmod'])) {
                    $sitemap .= '<lastmod>' . esc_html($data['lastmod']) . '</lastmod>' . "\n";
                }
                if (!empty($data['priority'])) {
                    $sitemap .= '<priority>' . esc_html($data['priority']) . '</priority>' . "\n";
                }
                if (!empty($data['changefreq'])) {
                    $sitemap .= '<changefreq>' . esc_html($data['changefreq']) . '</changefreq>' . "\n";
                }
                $sitemap .= '</url>' . "\n";
            }

            if (empty($sitemap))
                return null;

            $xagio_template = str_replace('{{urls}}', $sitemap, $xagio_template);

            return $xagio_template;
        }

        /**
         * Build the OKF child sitemap (a normal <urlset>) from the OKF model's URL list.
         * Returns null when OKF is unavailable/disabled or has no eligible URLs, so
         * createSitemap() drops the child entry and its index reference.
         */
        public static function generateOkfSitemap()
        {
            if (!class_exists('XAGIO_MODEL_OKF') || !method_exists('XAGIO_MODEL_OKF', 'getSitemapUrls')) {
                return null;
            }

            $urls = XAGIO_MODEL_OKF::getSitemapUrls();
            if (empty($urls) || !is_array($urls)) {
                return null;
            }

            // fetch the template from meta folder
            $xagio_template = xagio_file_get_contents(XAGIO_PATH . '/modules/sitemaps/meta/sitemap.xml');

            if (XAGIO_DISABLE_HTML_FOOTPRINT == FALSE) {
                $xagio_template = str_replace('{{url}}', get_site_url(), $xagio_template);
                $xagio_template = str_replace('{{date}}', gmdate('H:i:s F j, Y'), $xagio_template);
            } else {
                $xagio_template = explode("\n", $xagio_template);
                unset($xagio_template[2], $xagio_template[3]);
                $xagio_template = join("\n", array_values($xagio_template));
            }

            $sitemap = '';
            foreach ($urls as $data) {
                if (empty($data['loc'])) {
                    continue;
                }
                $sitemap .= '<url>' . "\n";
                $sitemap .= '<loc>' . esc_url($data['loc']) . '</loc>' . "\n";
                if (!empty($data['lastmod'])) {
                    $sitemap .= '<lastmod>' . esc_html($data['lastmod']) . '</lastmod>' . "\n";
                }
                $sitemap .= '</url>' . "\n";
            }

            if (empty($sitemap))
                return null;

            $xagio_template = str_replace('{{urls}}', $sitemap, $xagio_template);

            return $xagio_template;
        }

    }
}
