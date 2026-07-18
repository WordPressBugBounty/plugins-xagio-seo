<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('XAGIO_MODEL_OKF')) {

    /**
     * Open Knowledge Format (OKF, Google v0.1) bundle generator.
     *
     * Serves a small markdown knowledge base under /okf/:
     *   /okf/              -> index listing every concept
     *   /okf/<concept>.md  -> one post rendered as markdown (YAML frontmatter + rewritten links)
     *
     * Serving mirrors XAGIO_MODEL_LLMS (rewrite rule + template_redirect + REQUEST_URI fallback).
     * Caching mirrors the sitemaps build-on-change pattern: the whole bundle is built once into a
     * single non-autoloaded wp_option and echoed on request.
     */
    class XAGIO_MODEL_OKF
    {
        const OPTION_ENABLED       = 'XAGIO_OKF_ENABLED';
        const OPTION_POST_TYPES    = 'XAGIO_OKF_POST_TYPES';
        const OPTION_BUNDLE        = 'XAGIO_OKF_BUNDLE';
        const OPTION_REWRITE       = 'XAGIO_OKF_REWRITE_READY_V1';
        const OPTION_NEEDS_REBUILD = 'XAGIO_OKF_NEEDS_REBUILD';
        const OPTION_LINT_STATUS   = 'XAGIO_OKF_LINT_STATUS';

        const QUERY_VAR_INDEX = 'xagio_okf';
        const QUERY_VAR_FILE  = 'xagio_okf_file';

        const BASE = 'okf';

        // Hard ceiling: the whole bundle lives in one wp_option row.
        const MAX_POSTS = 1000;

        public static function initialize()
        {
            add_action('init', ['XAGIO_MODEL_OKF', 'addRewrite']);
            add_filter('query_vars', ['XAGIO_MODEL_OKF', 'registerQueryVars']);
            add_action('template_redirect', ['XAGIO_MODEL_OKF', 'maybeServe'], 0);

            // Build-on-change: flag on content edits, rebuild once on shutdown (mirrors sitemaps).
            if (get_option(self::OPTION_ENABLED) === '1') {
                add_action('save_post', ['XAGIO_MODEL_OKF', 'onSavePostInvalidate'], 20, 3);
                add_action('transition_post_status', ['XAGIO_MODEL_OKF', 'onTransitionInvalidate'], 10, 3);
                add_action('trashed_post', ['XAGIO_MODEL_OKF', 'invalidate']);
                add_action('untrashed_post', ['XAGIO_MODEL_OKF', 'invalidate']);
                add_action('deleted_post', ['XAGIO_MODEL_OKF', 'invalidate']);
                add_action('shutdown', ['XAGIO_MODEL_OKF', 'maybeRebuild'], 0);
            }

            if (!XAGIO_HAS_ADMIN_PERMISSIONS) return;

            add_action('admin_post_xagio_okf_save', ['XAGIO_MODEL_OKF', 'saveSettings']);
        }

        public static function saveSettings()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $mode = isset($_POST['mode']) ? sanitize_text_field(wp_unslash($_POST['mode'])) : 'save';

            if ($mode === 'reset') {
                delete_option(self::OPTION_ENABLED);
                delete_option(self::OPTION_POST_TYPES);
                $bundle = self::rebuildBundle();
                self::rebuildSitemapsNow();
                // OKF is now disabled; drop any recorded lint failure so the panel/notice
                // starts clean the next time the feature is enabled.
                delete_option(self::OPTION_LINT_STATUS);
                xagio_json('success', 'OKF settings reset to default.', self::statePayload($bundle));
                return;
            }

            if ($mode === 'rebuild') {
                $bundle = self::rebuildBundle();
                self::rebuildSitemapsNow();
                xagio_json('success', 'OKF bundle rebuilt.', self::statePayload($bundle));
                return;
            }

            $enabled = isset($_POST[self::OPTION_ENABLED]) ? (intval($_POST[self::OPTION_ENABLED]) ? '1' : '0') : '0';

            $post_types = [];
            if (isset($_POST[self::OPTION_POST_TYPES]) && is_array($_POST[self::OPTION_POST_TYPES])) {
                foreach ((array) $_POST[self::OPTION_POST_TYPES] as $pt => $on) {
                    $pt_clean = sanitize_key($pt);
                    if ($pt_clean !== '' && post_type_exists($pt_clean) && intval($on)) {
                        $post_types[$pt_clean] = 1;
                    }
                }
            }

            update_option(self::OPTION_ENABLED, $enabled);
            update_option(self::OPTION_POST_TYPES, $post_types, false);

            $bundle = self::rebuildBundle();
            self::rebuildSitemapsNow();

            xagio_json('success', 'OKF settings saved.', self::statePayload($bundle));
        }

        private static function statePayload($bundle)
        {
            $count = isset($bundle['files']) ? count($bundle['files']) : 0;
            $built = isset($bundle['built']) ? (int) $bundle['built'] : 0;

            $lint      = get_option(self::OPTION_LINT_STATUS);
            $lint_ok   = !is_array($lint) || !empty($lint['ok']);
            $errors    = (is_array($lint) && isset($lint['errors'])) ? array_values((array) $lint['errors']) : [];
            $warnings  = (is_array($lint) && isset($lint['warnings'])) ? array_values((array) $lint['warnings']) : [];
            $published = !is_array($lint) || !empty($lint['published']);

            return [
                'count'          => $count,
                'built'          => $built,
                'built_human'    => $built ? date_i18n('Y-m-d H:i', $built) : '',
                'lint_ok'        => $lint_ok,
                'lint_published' => $published,
                'lint_errors'    => array_slice(array_map('strval', $errors), 0, 8),
                'lint_warnings'  => array_slice(array_map('strval', $warnings), 0, 12),
            ];
        }

        public static function invalidate()
        {
            if (get_option(self::OPTION_ENABLED) !== '1') return;

            if (get_transient('xagio_okf_invalidate_debounce')) return;
            set_transient('xagio_okf_invalidate_debounce', 1, 30);

            update_option(self::OPTION_NEEDS_REBUILD, 1, false);
        }

        public static function onSavePostInvalidate($post_id, $post, $update)
        {
            if (get_option(self::OPTION_ENABLED) !== '1') return;
            if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) return;
            if (!$post) return;

            $pt = get_post_type($post_id);
            if (!$pt || !in_array($pt, self::enabledPostTypes(), true)) return;

            if ($post->post_status === 'publish') {
                self::invalidate();
            }
        }

        public static function onTransitionInvalidate($new_status, $old_status, $post)
        {
            if (get_option(self::OPTION_ENABLED) !== '1') return;
            if (!$post || $new_status === $old_status) return;

            $pt = isset($post->post_type) ? $post->post_type : '';
            if (!in_array($pt, self::enabledPostTypes(), true)) return;

            if ($new_status === 'publish' || $old_status === 'publish') {
                self::invalidate();
            }
        }

        public static function maybeRebuild()
        {
            if (get_option(self::OPTION_ENABLED) !== '1') return;

            if (defined('DOING_AJAX') && DOING_AJAX) return;
            if (defined('DOING_CRON') && DOING_CRON) return;
            if (defined('REST_REQUEST') && REST_REQUEST) return;

            if (!(int) get_option(self::OPTION_NEEDS_REBUILD, 0)) return;

            if (get_transient('xagio_okf_rebuild_lock')) return;
            set_transient('xagio_okf_rebuild_lock', 1, 300);

            try {
                self::rebuildBundle();
                update_option(self::OPTION_NEEDS_REBUILD, 0, false);
            } finally {
                delete_transient('xagio_okf_rebuild_lock');
            }
        }

        public static function rebuildBundle()
        {
            $bundle = self::buildBundle();

            // Validate before publishing. A bundle that fails hard checks (missing
            // frontmatter, non-absolute resource, broken internal links, bad UTF-8)
            // must not overwrite a previously-good bundle — we keep serving the last
            // valid one and surface the errors in an admin notice + the OKF panel.
            $lint = self::lintBundle($bundle);

            if (!$lint['ok']) {
                $prev           = get_option(self::OPTION_BUNDLE);
                $has_prev_valid = is_array($prev) && isset($prev['index'], $prev['files']) && !empty($prev['files']);

                self::recordLintStatus($lint, !$has_prev_valid);

                if ($has_prev_valid) {
                    // Fail publishing: keep the last known-good bundle live.
                    return $prev;
                }
                // No prior good bundle exists; an absent /okf/ is worse than a flawed
                // one, so publish this one but leave the errors recorded for the admin.
            } else {
                self::recordLintStatus($lint, true);
            }

            update_option(self::OPTION_BUNDLE, $bundle, false);
            self::invalidateSitemaps();
            return $bundle;
        }

        /**
         * Persist the latest lint outcome so the settings panel + admin notice can
         * report it. $published = whether the just-built bundle was actually served.
         */
        private static function recordLintStatus($lint, $published)
        {
            update_option(self::OPTION_LINT_STATUS, [
                'ok'        => !empty($lint['ok']),
                'errors'    => isset($lint['errors']) ? array_values((array) $lint['errors']) : [],
                'warnings'  => isset($lint['warnings']) ? array_values((array) $lint['warnings']) : [],
                'published' => (bool) $published,
                'checked'   => time(),
            ], false);
        }

        /**
         * OKF content changed -> the OKF child sitemap + sitemap index must refresh too.
         * No-op when the sitemaps module/feature isn't present or sitemaps are disabled.
         */
        private static function invalidateSitemaps()
        {
            if (class_exists('XAGIO_MODEL_SITEMAPS') && method_exists('XAGIO_MODEL_SITEMAPS', 'invalidateSitemaps')) {
                XAGIO_MODEL_SITEMAPS::invalidateSitemaps();
            }
        }

        /**
         * Rebuild the sitemap files/cache synchronously so the OKF child sitemap
         * appears (or disappears) the instant OKF is toggled from the settings page.
         *
         * The deferred shutdown rebuild in the sitemaps module is skipped during
         * admin/AJAX requests, so relying on it alone left the OKF child missing
         * until the user re-saved Sitemaps. Mirrors saveSitemapSettings(), which
         * also calls createSitemap() directly. No-op when sitemaps are disabled.
         */
        private static function rebuildSitemapsNow()
        {
            if (class_exists('XAGIO_MODEL_SITEMAPS')
                && method_exists('XAGIO_MODEL_SITEMAPS', 'createSitemap')
                && get_option('XAGIO_ENABLE_SITEMAPS')) {
                XAGIO_MODEL_SITEMAPS::createSitemap();
            }
        }

        public static function addRewrite()
        {
            // More specific rule first: /okf/<file>.md
            add_rewrite_rule('^' . self::BASE . '/([^/]+)/?$', 'index.php?' . self::QUERY_VAR_FILE . '=$matches[1]', 'top');
            add_rewrite_rule('^' . self::BASE . '/?$', 'index.php?' . self::QUERY_VAR_INDEX . '=1', 'top');

            if (!get_option(self::OPTION_REWRITE)) {
                flush_rewrite_rules(false);
                update_option(self::OPTION_REWRITE, 1, false);
            }
        }

        public static function registerQueryVars($vars)
        {
            $vars[] = self::QUERY_VAR_INDEX;
            $vars[] = self::QUERY_VAR_FILE;
            return $vars;
        }

        /**
         * Resolve the request into one of: 'index', a concept filename, or null (not ours).
         */
        private static function resolveRequest()
        {
            // Query-var path (pretty permalinks).
            if (intval(get_query_var(self::QUERY_VAR_INDEX)) === 1) {
                return ['type' => 'index'];
            }
            $qv_file = get_query_var(self::QUERY_VAR_FILE);
            if (is_string($qv_file) && $qv_file !== '') {
                return ['type' => 'file', 'file' => $qv_file];
            }

            // REQUEST_URI fallback (plain permalinks / edge cases).
            $request_uri = isset($_SERVER['REQUEST_URI'])
                ? wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH)
                : '';
            if (!is_string($request_uri) || $request_uri === '') return null;

            $path = trim($request_uri, '/');
            if ($path === self::BASE) {
                return ['type' => 'index'];
            }
            if (strpos($path, self::BASE . '/') === 0) {
                $file = substr($path, strlen(self::BASE) + 1);
                $file = trim($file, '/');
                if ($file !== '') {
                    return ['type' => 'file', 'file' => $file];
                }
            }

            return null;
        }

        public static function maybeServe()
        {
            $req = self::resolveRequest();
            if ($req === null) return;

            if (get_option(self::OPTION_ENABLED) !== '1') {
                self::send404('disabled');
                return;
            }

            $bundle = self::getBundle();

            if ($req['type'] === 'index') {
                // Canonicalize to a trailing slash so the index's relative links (<id>.md)
                // resolve to /okf/<id>.md instead of /<id>.md.
                $req_path   = isset($_SERVER['REQUEST_URI'])
                    ? wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH)
                    : '';
                $canonical  = wp_parse_url(home_url('/' . self::BASE . '/'), PHP_URL_PATH);
                if (is_string($req_path) && $req_path !== '' && is_string($canonical)
                    && rtrim($req_path, '/') === rtrim($canonical, '/')
                    && substr($req_path, -1) !== '/'
                    && !headers_sent()) {
                    wp_redirect(esc_url_raw(home_url('/' . self::BASE . '/')), 301);
                    exit;
                }

                // The /okf/ index is advertised in the OKF child sitemap, so it must be
                // indexable. Individual .md docs stay noindex (served below).
                self::serveMarkdown($bundle['index'], false);
                return;
            }

            // type === 'file' : whitelist the filename to a safe concept id.
            $file = basename((string) $req['file']);
            if (!preg_match('/^[A-Za-z0-9._-]+\.md$/', $file)) {
                self::send404('badfile');
                return;
            }

            if (!isset($bundle['files'][$file])) {
                self::send404('notfound');
                return;
            }

            self::serveMarkdown($bundle['files'][$file]);
        }

        private static function serveMarkdown($body, $noindex = true)
        {
            nocache_headers();
            status_header(200);
            header('Content-Type: text/markdown; charset=UTF-8');
            if ($noindex) {
                header('X-Robots-Tag: noindex');
            }
            header('X-Xagio-OKF: served');
            // Raw text/markdown body. HTML escaping would corrupt markdown syntax.
            echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            exit;
        }

        private static function send404($reason)
        {
            global $wp_query;
            if ($wp_query) $wp_query->set_404();
            status_header(404);
            nocache_headers();
            header('X-Xagio-OKF: ' . $reason);
        }

        public static function getDefaultPostTypes()
        {
            return ['page' => 1, 'post' => 1];
        }

        /**
         * Return the cached bundle, building it lazily if missing.
         * Bundle shape: ['index' => md, 'files' => [conceptFile => md], 'map' => [postId => conceptFile], 'built' => ts]
         */
        public static function getBundle()
        {
            $bundle = get_option(self::OPTION_BUNDLE);
            if (!is_array($bundle) || !isset($bundle['index'], $bundle['files'])) {
                $bundle = self::buildBundle();
                update_option(self::OPTION_BUNDLE, $bundle, false);
            }
            return $bundle;
        }

        /**
         * URLs to advertise in the OKF child sitemap (sitemap-xagio-okf.xml).
         *
         * Per product decision we expose ONLY the /okf/ index here; the individual
         * .md docs stay noindex and are not promoted. Returns [] when OKF is disabled
         * or the bundle has no documents, so the sitemap layer drops the child entry.
         *
         * Shape: [ ['loc' => url, 'lastmod' => 'Y-m-d'], ... ]
         */
        public static function getSitemapUrls()
        {
            if (get_option(self::OPTION_ENABLED) !== '1') return [];

            $bundle = self::getBundle();
            if (!is_array($bundle) || empty($bundle['files'])) return [];

            $modified = isset($bundle['modified']) ? (int) $bundle['modified'] : 0;
            if (!$modified) $modified = isset($bundle['built']) ? (int) $bundle['built'] : time();

            return [
                [
                    'loc'     => home_url('/' . self::BASE . '/'),
                    'lastmod' => gmdate('Y-m-d', $modified),
                ],
            ];
        }

        // ---- Bundle linter (fail publishing on invalid) ------------------

        /**
         * Validate a freshly-built bundle against the OKF publishing rules.
         * Hard failures (-> errors, block publishing): missing/empty document,
         * missing YAML frontmatter, empty `type:`, non-absolute `resource:`,
         * invalid UTF-8, or an internal .md link that doesn't resolve to a bundle
         * file. Soft issues (-> warnings, non-blocking) are reported but still publish.
         *
         * Returns ['ok' => bool, 'errors' => string[], 'warnings' => string[]].
         */
        public static function lintBundle($bundle)
        {
            $errors   = [];
            $warnings = [];

            if (!is_array($bundle) || !isset($bundle['index'], $bundle['files']) || !is_array($bundle['files'])) {
                return ['ok' => false, 'errors' => ['Bundle structure is invalid (missing index or files).'], 'warnings' => []];
            }

            $known = array_fill_keys(array_keys($bundle['files']), true);

            // --- Index document ---
            $idx = $bundle['index'];
            if (!is_string($idx) || trim($idx) === '') {
                $errors[] = 'Index document is empty.';
            } else {
                if (!self::isValidUtf8($idx)) {
                    $errors[] = 'Index document is not valid UTF-8.';
                }
                $fm = self::extractFrontmatter($idx);
                if ($fm === null) {
                    $errors[] = 'Index is missing a YAML frontmatter block.';
                } else {
                    if (self::yamlField($fm, 'type') === '') {
                        $errors[] = 'Index frontmatter has an empty "type".';
                    }
                    $res = self::yamlField($fm, 'resource');
                    if ($res !== '' && !preg_match('#^https?://#i', $res)) {
                        $errors[] = 'Index "resource" is not an absolute URL.';
                    }
                }
                foreach (self::internalMdLinks($idx) as $link) {
                    if (!isset($known[$link])) {
                        $errors[] = 'Index links to a missing document: ' . $link;
                    }
                }
            }

            // --- Concept documents ---
            foreach ($bundle['files'] as $name => $md) {
                if (!is_string($name) || !preg_match('/^[A-Za-z0-9._-]+\.md$/', $name)) {
                    $errors[] = 'Invalid document filename: ' . (is_string($name) ? $name : '(non-string)');
                    continue;
                }
                if (!is_string($md) || trim($md) === '') {
                    $errors[] = $name . ': document is empty.';
                    continue;
                }
                if (!self::isValidUtf8($md)) {
                    $errors[] = $name . ': not valid UTF-8.';
                }

                $fm = self::extractFrontmatter($md);
                if ($fm === null) {
                    $errors[] = $name . ': missing YAML frontmatter block.';
                } else {
                    if (self::yamlField($fm, 'type') === '') {
                        $errors[] = $name . ': frontmatter has an empty "type".';
                    }
                    $res = self::yamlField($fm, 'resource');
                    if ($res === '') {
                        $warnings[] = $name . ': frontmatter has no "resource" URL.';
                    } elseif (!preg_match('#^https?://#i', $res)) {
                        $errors[] = $name . ': "resource" is not an absolute URL.';
                    }
                }

                foreach (self::internalMdLinks($md) as $link) {
                    if (!isset($known[$link])) {
                        $errors[] = $name . ': broken internal link -> ' . $link;
                    }
                }
            }

            // Content-quality checks (non-blocking warnings): near-duplicate documents.
            // These never block publishing per the OKF spec.
            $warnings = array_merge($warnings, self::duplicateWarnings($bundle['files']));

            return ['ok' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
        }

        // ---- Content-quality warnings (dedup + geo, non-blocking) ---------

        /**
         * Body text with a leading YAML frontmatter block removed.
         */
        private static function docBody($md)
        {
            if (!is_string($md)) return '';
            $body = preg_replace('/\A---\r?\n.*?\r?\n---\r?\n?/s', '', $md, 1);
            return is_string($body) ? $body : $md;
        }

        /**
         * Normalize a doc body into a list of significant lowercase word tokens
         * (link URLs dropped, punctuation stripped, short stopword-ish tokens removed).
         */
        private static function normalizeWords($md)
        {
            $text = self::docBody($md);
            $text = preg_replace('/\[([^\]]*)\]\([^()\s]*\)/', '$1', $text); // keep link text, drop URL
            $text = strtolower(wp_strip_all_tags($text));
            $text = preg_replace('/[^a-z0-9\s]+/u', ' ', $text);
            $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
            return array_values(array_filter($words, function ($w) { return strlen($w) >= 3; }));
        }

        /**
         * Warn on near-identical documents (e.g. templated location pages, thin
         * boilerplate) that dilute the bundle. Jaccard word-set similarity with a 0.90
         * threshold. Docs are size-sorted so pairs that can't reach the threshold are
         * skipped (|A|/|B| bounds Jaccard from above) — keeping this well below O(n^2).
         */
        private static function duplicateWarnings($files)
        {
            $warnings = [];
            if (!is_array($files) || count($files) < 2) return $warnings;

            $docs = [];
            foreach ($files as $name => $md) {
                if (!is_string($name)) continue;
                $words = self::normalizeWords($md);
                if (count($words) < 20) continue; // thin docs: similarity is unreliable
                $set = array_fill_keys($words, true);
                $docs[] = ['name' => $name, 'set' => $set, 'n' => count($set)];
            }

            usort($docs, function ($a, $b) { return $a['n'] - $b['n']; });

            $count      = count($docs);
            $threshold  = 0.90;
            $maxCompare = 200000; // hard safety cap
            $done       = 0;
            $pairs      = [];

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    // Ascending size: once |A|/|B| drops below the threshold, so does the
                    // max possible Jaccard, and every later j is only larger — stop.
                    if ($docs[$j]['n'] > $docs[$i]['n'] / $threshold) break;
                    if (++$done > $maxCompare) break 2;

                    $sim = self::jaccard($docs[$i]['set'], $docs[$j]['set']);
                    if ($sim >= $threshold) {
                        $pairs[] = [$docs[$i]['name'], $docs[$j]['name'], $sim];
                    }
                }
            }

            foreach (array_slice($pairs, 0, 15) as $p) {
                $warnings[] = sprintf('Near-duplicate content: %s and %s (%d%% similar).', $p[0], $p[1], (int) round($p[2] * 100));
            }
            if (count($pairs) > 15) {
                $warnings[] = sprintf('… and %d more near-duplicate pair(s).', count($pairs) - 15);
            }

            return $warnings;
        }

        private static function jaccard($a, $b)
        {
            $na = count($a);
            $nb = count($b);
            if ($na === 0 || $nb === 0) return 0.0;
            if ($na > $nb) { $t = $a; $a = $b; $b = $t; } // iterate the smaller set
            $inter = 0;
            foreach ($a as $w => $_) {
                if (isset($b[$w])) $inter++;
            }
            $union = $na + $nb - $inter;
            return $union > 0 ? $inter / $union : 0.0;
        }

        /**
         * Return the raw text of a leading YAML frontmatter block (between the first
         * "---" line and the next "---"), or null when the document has none.
         */
        private static function extractFrontmatter($md)
        {
            if (!is_string($md)) return null;
            if (!preg_match('/\A---\r?\n(.*?)\r?\n---(\r?\n|$)/s', $md, $m)) {
                return null;
            }
            return $m[1];
        }

        /**
         * Read a top-level scalar field from our own frontmatter. We emit double-quoted
         * scalars via yaml(), so this unquotes them; returns '' when the key is absent.
         */
        private static function yamlField($fm, $key)
        {
            if (!preg_match('/^' . preg_quote($key, '/') . ':[ \t]*(.*)$/m', (string) $fm, $m)) {
                return '';
            }
            $val = trim($m[1]);
            if (strlen($val) >= 2 && $val[0] === '"' && substr($val, -1) === '"') {
                $val = substr($val, 1, -1);
                $val = str_replace(['\\"', '\\\\'], ['"', '\\'], $val);
            }
            return $val;
        }

        /**
         * Collect relative "<id>.md" link targets from a markdown document (the internal
         * bundle links). Absolute URLs, root-relative paths, images and anchors are
         * ignored — only sibling-file references, which must resolve inside the bundle.
         */
        private static function internalMdLinks($md)
        {
            $links = [];
            if (preg_match_all('/(!?)\[[^\]]*\]\(([^()\s]+)\)/', (string) $md, $mm, PREG_SET_ORDER)) {
                foreach ($mm as $m) {
                    if ($m[1] === '!') continue;                       // image
                    $url = $m[2];
                    if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) continue; // absolute
                    if ($url === '' || $url[0] === '/' || $url[0] === '#') continue;
                    if (preg_match('#^(mailto:|tel:)#i', $url)) continue;
                    $url = preg_replace('/#.*$/', '', $url);           // drop anchor
                    if (substr($url, -3) !== '.md') continue;
                    $links[] = basename($url);
                }
            }
            return $links;
        }

        private static function isValidUtf8($s)
        {
            // A well-formed UTF-8 string matches the //u anchor; malformed bytes fail it.
            return (bool) preg_match('//u', (string) $s);
        }

        /**
         * Two-pass build:
         *   Pass 1 — assign every included post a collision-safe concept id.
         *   Pass 2 — render each post to markdown (frontmatter + body).
         * Link rewriting (Phase 3) plugs into pass 2 using the map from pass 1.
         *
         * Bundle: ['index'=>md, 'files'=>[conceptFile=>md], 'map'=>[postId=>conceptId], 'built'=>ts]
         */
        public static function buildBundle()
        {
            $posts = self::collectPosts();

            // Pass 1: concept ids.
            $used   = [];
            $map    = [];   // postId => conceptId (no extension)
            $titles = [];   // postId => display title
            foreach ($posts as $p) {
                $id              = self::conceptId($p, $used);
                $map[$p->ID]     = $id;
                $titles[$p->ID]  = self::conceptLabel($p);
            }

            // Previous bundle: reuse published timestamps when content is unchanged so a
            // no-op re-save (which bumps post_modified) doesn't churn dates.
            $prev        = get_option(self::OPTION_BUNDLE);
            $prev_hashes = (is_array($prev) && isset($prev['hashes']) && is_array($prev['hashes']))
                ? $prev['hashes'] : [];

            // Pass 2: render bodies (rewriting internal links + recording graph edges),
            // then attach frontmatter carrying a content-stable timestamp.
            $files    = [];
            $graph    = [];
            $hashes   = [];
            $modified = 0;

            foreach ($posts as $p) {
                $id   = $map[$p->ID];
                $body = self::renderBody($p, $id, $map, $titles, $graph);

                // Content signature excludes frontmatter/timestamp, so only real content
                // changes advance the timestamp / sitemap lastmod.
                $hash = md5($body);
                if (isset($prev_hashes[$id]['hash'], $prev_hashes[$id]['timestamp'])
                    && $prev_hashes[$id]['hash'] === $hash) {
                    $ts = $prev_hashes[$id]['timestamp'];
                } else {
                    $ts = get_post_modified_time('c', true, $p);
                }

                $md = self::frontmatter($p, $id, $ts) . "\n\n" . $body . "\n";
                $md = preg_replace("/\n{3,}/", "\n\n", $md);
                $files[$id . '.md'] = rtrim($md) . "\n";

                $hashes[$id] = ['hash' => $hash, 'timestamp' => $ts];

                $t = strtotime($ts);
                if ($t && $t > $modified) $modified = $t;
            }

            foreach ($graph as $from => $tos) {
                $graph[$from] = array_values(array_unique($tos));
            }

            $index = self::buildIndex($posts, $map, $titles, $modified);

            return [
                'index'    => $index,
                'files'    => $files,
                'map'      => $map,
                'graph'    => $graph,
                'hashes'   => $hashes,
                'modified' => $modified,
                'built'    => time(),
            ];
        }

        private static function enabledPostTypes()
        {
            $pt = get_option(self::OPTION_POST_TYPES);
            if (!is_array($pt)) $pt = self::getDefaultPostTypes();
            $types = array_keys(array_filter($pt));
            $types = array_filter($types, 'post_type_exists');
            return array_values($types);
        }

        private static function collectPosts()
        {
            $types = self::enabledPostTypes();
            if (empty($types)) return [];

            // Cap = most recent MAX_POSTS across all enabled types (one wp_option row).
            $posts = get_posts([
                'post_type'        => $types,
                'post_status'      => 'publish',
                'posts_per_page'   => self::MAX_POSTS,
                'orderby'          => 'date',
                'order'            => 'DESC',
                'suppress_filters' => true,
            ]);

            return is_array($posts) ? $posts : [];
        }

        private static function conceptId($post, &$used)
        {
            $base = ($post->post_name !== '') ? $post->post_name : sanitize_title($post->post_title);
            if ($base === '') $base = 'post-' . $post->ID;

            $id = $base;
            $n  = 2;
            while (isset($used[$id])) {
                $id = $base . '-' . $n;
                $n++;
            }
            $used[$id] = true;
            return $id;
        }

        private static function renderBody($p, $conceptId, $map, $titles, &$graph)
        {
            $html  = self::postContentHtml($p);

            $body  = self::htmlToMarkdown($html);
            $body  = self::rewriteLinks($body, $map, $p->ID, $graph);
            $title = self::postTitle($p);

            // The concept title is prepended as the single H1 below; demote any heading
            // pulled from the page to H2 and drop ones that just repeat the title, so the
            // doc doesn't open with two competing titles (a page-export tell).
            $body  = self::demoteAndDedupeHeadings($body, $title);

            $out = '# ' . self::cleanInline($title) . "\n\n" . $body;

            // Related Knowledge: the concepts this doc links to, as relative <id>.md links.
            // The graph is keyed by source POST ID (see rewriteLinks), values are target post IDs.
            $related = isset($graph[$p->ID]) ? array_values(array_unique($graph[$p->ID])) : [];
            if (!empty($related)) {
                $rel = [];
                foreach ($related as $targetId) {
                    if (!isset($map[$targetId])) continue;
                    $label = isset($titles[$targetId]) ? $titles[$targetId] : $map[$targetId];
                    $rel[] = '- [' . self::escapeMarkdownText($label) . '](' . $map[$targetId] . '.md)';
                }
                if (!empty($rel)) {
                    $out .= "\n\n## Related Knowledge\n" . implode("\n", $rel);
                }
            }

            return $out;
        }

        /**
         * Get a post's rendered HTML content.
         *
         * Primary path is the_content, which correctly expands classic/Gutenberg
         * content and builders that hook it (Elementor included) — this is what the
         * vast majority of posts use and it must stay untouched.
         *
         * Fallback: some Elementor pages come back empty from the_content in the build
         * context (post_content is empty and the builder filter didn't inject). Only
         * for those do we render the Elementor document directly, so working pages are
         * never rerouted through a different code path.
         */
        private static function postContentHtml($p)
        {
            // Primary: the_content. Save/restore global $post so we don't disturb the loop.
            global $post;
            $saved = $post;
            $post  = $p;
            setup_postdata($p);
            $html = apply_filters('the_content', $p->post_content);
            $post = $saved;
            wp_reset_postdata();

            if (is_string($html) && trim(wp_strip_all_tags($html)) !== '') {
                return $html;
            }

            // Fallback: render the Elementor document directly (content lives in meta).
            if (
                class_exists('\Elementor\Plugin')
                && get_post_meta($p->ID, '_elementor_edit_mode', true) === 'builder'
            ) {
                $elementor = \Elementor\Plugin::$instance;
                if (isset($elementor->frontend) && method_exists($elementor->frontend, 'get_builder_content_for_display')) {
                    $builder = $elementor->frontend->get_builder_content_for_display($p->ID, false);
                    if (is_string($builder) && trim($builder) !== '') {
                        return $builder;
                    }
                }
            }

            return is_string($html) ? $html : '';
        }

        /**
         * Rewrite internal links to sibling concept files (.md) and absolutize the rest.
         * URL group excludes parens/whitespace so tel:/links-with-parens are left fully intact.
         * Records each concept->concept edge in $graph.
         */
        private static function rewriteLinks($md, $map, $fromId, &$graph)
        {
            return preg_replace_callback('/(!?)\[([^\]]*)\]\(([^()\s]+)\)/', function ($m) use ($map, $fromId, &$graph) {
                $is_img = ($m[1] === '!');
                $url    = $m[3];

                // Leave special schemes and pure anchors untouched.
                if (preg_match('#^(mailto:|tel:|javascript:|data:)#i', $url) || strpos($url, '#') === 0) {
                    return $m[0];
                }

                // Absolutize root-relative URLs; bail on other relative forms.
                $abs = $url;
                if (strpos($url, '/') === 0) {
                    $abs = home_url($url);
                } elseif (!preg_match('#^https?://#i', $url)) {
                    return $m[0];
                }

                if (!$is_img) {
                    $target = url_to_postid($abs);
                    if ($target && isset($map[$target])) {
                        $graph[$fromId][] = $target;
                        return '[' . $m[2] . '](' . $map[$target] . '.md)';
                    }
                }

                // Non-concept link or image: emit a portable absolute URL.
                return $m[1] . '[' . $m[2] . '](' . $abs . ')';
            }, $md);
        }

        private static function buildIndex($posts, $map, $titles, $modified = 0)
        {
            $site_title = self::cleanInline(get_bloginfo('name'));
            $tagline    = self::cleanInline(get_bloginfo('description'));

            // Semantic grouping (Services / Company / Policies) via keyword heuristic,
            // falling back to the post-type label for non-page types (e.g. blog Posts).
            $by_group = [];
            foreach ($posts as $p) {
                $by_group[self::classifyGroup($p)][] = $p;
            }

            // Stable order: the three semantic groups first, then any fallback groups.
            $order = ['Services', 'Company', 'Policies'];
            uksort($by_group, function ($a, $b) use ($order) {
                $ia = array_search($a, $order, true);
                $ib = array_search($b, $order, true);
                if ($ia === false && $ib === false) return strcmp($a, $b);
                if ($ia === false) return 1;
                if ($ib === false) return -1;
                return $ia - $ib;
            });

            $lines = [];

            // Index frontmatter (matches per-doc frontmatter shape; type: index).
            $lines[] = '---';
            $lines[] = 'type: index';
            $lines[] = 'title: ' . self::yaml($site_title . ' — Knowledge Base');
            if ($tagline !== '') {
                $lines[] = 'description: ' . self::yaml($tagline);
            }
            $lines[] = 'resource: ' . self::yaml(home_url('/' . self::BASE . '/'));
            $index_tags = [];
            foreach (array_keys($by_group) as $group) {
                $index_tags[] = self::yaml($group);
            }
            if (!empty($index_tags)) {
                $lines[] = 'tags: [' . implode(', ', $index_tags) . ']';
            }
            $lines[] = 'timestamp: ' . self::yaml(gmdate('c', $modified ?: time()));
            $lines[] = '---';
            $lines[] = '';

            $lines[] = '# ' . $site_title . ' — Knowledge Base';
            if ($tagline !== '') $lines[] = '> ' . $tagline;
            $lines[] = '';

            foreach ($by_group as $group => $list) {
                $lines[] = '## ' . $group;
                foreach ($list as $p) {
                    // Relative link — the index is served at /okf/, so "<id>.md" resolves
                    // to /okf/<id>.md without hardcoding the absolute host.
                    $url   = $map[$p->ID] . '.md';
                    $title = self::escapeMarkdownText($titles[$p->ID]);
                    $lines[] = '- [' . $title . '](' . $url . ')';
                }
                $lines[] = '';
            }

            // Link back to the human/agent-oriented llms.txt guide when it's enabled.
            if (get_option('XAGIO_LLMS_ENABLED') === '1') {
                $lines[] = '## Resources';
                $lines[] = '- [LLM guide (llms.txt)](' . esc_url_raw(home_url('/llms.txt')) . ')';
                $lines[] = '';
            }

            $out = implode("\n", $lines);
            $out = preg_replace("/\n{3,}/", "\n\n", $out);
            return rtrim($out) . "\n";
        }

        // ---- Frontmatter -------------------------------------------------

        private static function frontmatter($post, $conceptId, $ts)
        {
            $title = self::postTitle($post);

            $desc = get_post_meta($post->ID, 'XAGIO_SEO_DESCRIPTION', true);
            if (!is_string($desc) || trim($desc) === '') $desc = $post->post_excerpt;
            if (!is_string($desc) || trim($desc) === '') {
                $desc = wp_trim_words(wp_strip_all_tags($post->post_content), 40, '...');
            }
            $desc = self::cleanInline($desc);

            $tags = self::postTags($post);

            $lines   = [];
            $lines[] = '---';
            $lines[] = 'id: ' . self::yaml($conceptId);
            $lines[] = 'type: ' . self::yaml($post->post_type);
            $lines[] = 'title: ' . self::yaml($title);
            if ($desc !== '') {
                $lines[] = 'description: ' . self::yaml($desc);
            }
            $lines[] = 'resource: ' . self::yaml(get_permalink($post->ID));
            $lines[] = 'timestamp: ' . self::yaml($ts);
            if (!empty($tags)) {
                $quoted = [];
                foreach ($tags as $t) {
                    $quoted[] = self::yaml($t);
                }
                $lines[] = 'tags: [' . implode(', ', $quoted) . ']';
            }
            $lines[] = '---';

            return implode("\n", $lines);
        }

        private static function postTags($post)
        {
            $names = [];
            $taxes = get_object_taxonomies($post->post_type, 'objects');
            foreach ($taxes as $tax) {
                if (empty($tax->public)) continue;
                $terms = get_the_terms($post->ID, $tax->name);
                if (is_array($terms)) {
                    foreach ($terms as $t) {
                        $names[] = $t->name;
                    }
                }
            }
            return array_values(array_unique($names));
        }

        private static function postTitle($post)
        {
            $title = get_post_meta($post->ID, 'XAGIO_SEO_TITLE', true);
            if (!is_string($title) || trim($title) === '') {
                $title = $post->post_title;
            }
            return self::cleanInline($title);
        }

        /**
         * Plain, human-readable label for the index (uses the real post title, not the
         * SEO title which often carries brand/boilerplate suffixes).
         */
        private static function conceptLabel($post)
        {
            $title = $post->post_title;
            if (!is_string($title) || trim($title) === '') {
                $title = get_post_meta($post->ID, 'XAGIO_SEO_TITLE', true);
            }
            return self::cleanInline($title);
        }

        private static function postTypeHeading($post_type)
        {
            $obj = get_post_type_object($post_type);
            if ($obj && !empty($obj->labels->name)) {
                return $obj->labels->name;
            }
            return ucfirst($post_type);
        }

        /**
         * Semantic bucket for the index, from a slug/title keyword heuristic:
         * Policies (legal/privacy/terms), Company (about/contact/team), else Services.
         * Non-page post types fall back to their post-type label (e.g. blog "Posts").
         */
        private static function classifyGroup($post)
        {
            $hay = strtolower($post->post_name . ' ' . $post->post_title);

            if (preg_match('~privacy|terms|cookie|disclaimer|refund|policy|policies|gdpr|legal|dmca~', $hay)) {
                return 'Policies';
            }
            if (preg_match('~about|contact|team|our[- ]story|careers|company|mission~', $hay)) {
                return 'Company';
            }
            if ($post->post_type === 'page') {
                return 'Services';
            }
            return self::postTypeHeading($post->post_type);
        }

        // ---- HTML -> Markdown (hand-rolled, zero deps) -------------------

        private static $skipTags = [
            'script', 'style', 'nav', 'form', 'svg', 'header', 'footer',
            'iframe', 'noscript', 'aside', 'input', 'select', 'textarea',
        ];

        private static function htmlToMarkdown($html)
        {
            $html = (string) $html;
            if (trim($html) === '') return '';

            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            // Force UTF-8 so multibyte content survives.
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
            libxml_clear_errors();

            $body = $dom->getElementsByTagName('body')->item(0);
            if (!$body) return '';

            $md = self::renderNodes($body->childNodes);
            // Strip leading horizontal whitespace per line: layout-div whitespace would
            // otherwise indent lines 4+ spaces and markdown would treat them as code blocks.
            $md = preg_replace('/^[^\S\n]+/m', '', $md);
            $md = preg_replace("/[ \t]+\n/", "\n", $md);
            $md = self::stripDecorativeLines($md);
            $md = preg_replace("/\n{3,}/", "\n\n", $md);
            return trim($md);
        }

        private static function renderNodes($nodeList)
        {
            $out = '';
            foreach ($nodeList as $node) {
                $out .= self::renderNode($node);
            }
            return $out;
        }

        private static function renderNode($node)
        {
            if ($node->nodeType === XML_TEXT_NODE) {
                $text = preg_replace('/\s+/u', ' ', $node->nodeValue);
                return $text;
            }
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                return '';
            }

            $tag = strtolower($node->nodeName);

            if (in_array($tag, self::$skipTags, true)) {
                return '';
            }

            switch ($tag) {
                case 'h1':
                case 'h2':
                case 'h3':
                case 'h4':
                case 'h5':
                case 'h6':
                    $level = (int) substr($tag, 1);
                    $htext = trim(self::renderNodes($node->childNodes));
                    // Drop empty headings (text lived in a dropped child) so they don't add noise.
                    return ($htext === '') ? '' : "\n\n" . str_repeat('#', $level) . ' ' . $htext . "\n\n";

                case 'p':   return "\n\n" . trim(self::renderNodes($node->childNodes)) . "\n\n";
                case 'br':  return "  \n";
                case 'hr':  return "\n\n---\n\n";

                case 'strong':
                case 'b':   return '**' . trim(self::renderNodes($node->childNodes)) . '**';
                case 'em':
                case 'i':   return '*' . trim(self::renderNodes($node->childNodes)) . '*';

                case 'a':
                    $href = trim($node->getAttribute('href'));
                    $text = trim(self::renderNodes($node->childNodes));
                    // Phone CTAs ("Call Us: +1 …") carry no knowledge — drop the whole link.
                    if (stripos($href, 'tel:') === 0) return '';
                    if ($text === '') $text = $href;
                    if ($href === '' || strpos($href, '#') === 0) return $text;
                    return '[' . $text . '](' . $href . ')';

                case 'img':
                    // Decorative page imagery is noise in a knowledge doc and the raw
                    // upload URLs aren't portable — drop images entirely.
                    return '';

                case 'ul': return "\n\n" . self::renderList($node, false) . "\n\n";
                case 'ol': return "\n\n" . self::renderList($node, true) . "\n\n";

                case 'blockquote':
                    $content = trim(self::renderNodes($node->childNodes));
                    $quoted  = array_map(function ($l) { return '> ' . $l; }, preg_split('/\n/', $content));
                    return "\n\n" . implode("\n", $quoted) . "\n\n";

                case 'pre':
                    $code = rtrim($node->textContent);
                    return "\n\n```\n" . $code . "\n```\n\n";

                case 'code':
                    return '`' . trim($node->textContent) . '`';

                // Layout containers: treat as block so sibling text blocks don't merge
                // onto one line. Excess blank lines are collapsed afterward.
                case 'div':
                case 'section':
                case 'article':
                case 'main':
                case 'figure':
                case 'figcaption':
                case 'details':
                    return "\n\n" . self::renderNodes($node->childNodes) . "\n\n";

                // FAQ/accordion question lives in <summary> (or a <button> toggle).
                case 'summary':
                    $stext = trim(self::renderNodes($node->childNodes));
                    return ($stext === '') ? '' : "\n\n**" . $stext . "**\n\n";

                default:
                    // span, button, em-like wrappers, etc. — recurse inline.
                    return self::renderNodes($node->childNodes);
            }
        }

        private static function renderList($node, $ordered)
        {
            $items = [];
            $i = 1;
            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE && strtolower($child->nodeName) === 'li') {
                    $marker  = $ordered ? ($i . '. ') : '- ';
                    $content = trim(self::renderNodes($child->childNodes));
                    // Indent any continuation/nested lines under the marker.
                    $content = str_replace("\n", "\n  ", $content);
                    $items[] = $marker . $content;
                    $i++;
                }
            }
            return implode("\n", $items);
        }

        // ---- Page-export noise removal (curated bundle, not a page dump) ---

        /**
         * Concept docs prepend the concept title as the single H1. Any heading pulled
         * from the page body is therefore demoted to H2 (so there's exactly one
         * top-level heading), and a body heading that merely repeats the concept title
         * is dropped so the doc doesn't open with two near-identical titles.
         */
        private static function demoteAndDedupeHeadings($body, $title)
        {
            $titleKey = self::headingKey($title);
            $lines    = preg_split('/\n/', (string) $body);
            $out      = [];
            foreach ($lines as $line) {
                if (preg_match('/^(#{1,6})[ \t]+(.*\S)[ \t]*$/', $line, $m)) {
                    if ($titleKey !== '' && self::headingKey($m[2]) === $titleKey) {
                        continue; // duplicate of the concept title
                    }
                    if ($m[1] === '#') {
                        $line = '## ' . $m[2]; // keep the concept title the only H1
                    }
                }
                $out[] = $line;
            }
            return implode("\n", $out);
        }

        /**
         * Remove page-export noise from the extracted markdown: bare step numbers
         * ("1", "2", …) left over from numbered-graphic sections, and ALL-CAPS marketing
         * kicker labels ("OUR SERVICES", "CLIENT TESTIMONIALS", "GET IN TOUCH").
         * Conservative: only strips short lines with no lowercase letters, and never
         * touches list items or ordered steps.
         */
        private static function stripDecorativeLines($md)
        {
            $lines = preg_split('/\n/', (string) $md);
            $out   = [];
            foreach ($lines as $line) {
                $t = trim($line);

                if (preg_match('/^\d{1,3}$/', $t)) continue; // leftover step number

                $label = ltrim($t, "# \t");
                if ($label !== ''
                    && mb_strlen($label) <= 60
                    && !preg_match('/^[-*>]\s/', $t)
                    && !preg_match('/^\d+[.)]\s/', $t)
                    && preg_match('/\p{Lu}/u', $label)
                    && !preg_match('/\p{Ll}/u', $label)) {
                    continue; // ALL-CAPS decorative kicker
                }

                $out[] = $line;
            }
            return implode("\n", $out);
        }

        /**
         * Normalized comparison key for a heading/title (tags + entities stripped,
         * punctuation folded to spaces, lowercased) so "Gutter Repair" matches
         * regardless of markup or trailing punctuation.
         */
        private static function headingKey($s)
        {
            $s = wp_strip_all_tags((string) $s);
            $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $s = preg_replace('/[^\p{L}\p{N} ]+/u', ' ', $s);
            $s = preg_replace('/\s+/u', ' ', $s);
            return strtolower(trim((string) $s));
        }

        // ---- Small text helpers (mirror xagio_llms.php) ------------------

        private static function cleanInline($text)
        {
            $text = wp_strip_all_tags((string) $text);
            // Decode entities (&amp; -> &, &#8217; -> ') so text reads naturally.
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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

        private static function yaml($value)
        {
            $value = (string) $value;
            $value = str_replace('\\', '\\\\', $value);
            $value = str_replace('"', '\\"', $value);
            $value = str_replace(["\r", "\n"], ' ', $value);
            return '"' . $value . '"';
        }
    }
}
