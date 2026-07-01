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
                xagio_json('success', 'OKF settings reset to default.', self::statePayload($bundle));
                return;
            }

            if ($mode === 'rebuild') {
                $bundle = self::rebuildBundle();
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

            xagio_json('success', 'OKF settings saved.', self::statePayload($bundle));
        }

        private static function statePayload($bundle)
        {
            $count = isset($bundle['files']) ? count($bundle['files']) : 0;
            $built = isset($bundle['built']) ? (int) $bundle['built'] : 0;
            return [
                'count'       => $count,
                'built'       => $built,
                'built_human' => $built ? date_i18n('Y-m-d H:i', $built) : '',
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
            update_option(self::OPTION_BUNDLE, $bundle, false);
            return $bundle;
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
                self::serveMarkdown($bundle['index']);
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

        private static function serveMarkdown($body)
        {
            nocache_headers();
            status_header(200);
            header('Content-Type: text/markdown; charset=UTF-8');
            header('X-Robots-Tag: noindex');
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
                $titles[$p->ID]  = self::postTitle($p);
            }

            // Pass 2: render bodies (rewriting internal links + recording graph edges).
            $files = [];
            $graph = [];
            foreach ($posts as $p) {
                $id                 = $map[$p->ID];
                $files[$id . '.md'] = self::renderPost($p, $id, $map, $graph);
            }
            foreach ($graph as $from => $tos) {
                $graph[$from] = array_values(array_unique($tos));
            }

            $index = self::buildIndex($posts, $map, $titles);

            return [
                'index' => $index,
                'files' => $files,
                'map'   => $map,
                'graph' => $graph,
                'built' => time(),
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

        private static function renderPost($p, $conceptId, $map, &$graph)
        {
            $front = self::frontmatter($p, $conceptId);

            // Render through the_content so shortcodes/blocks expand. Save/restore global $post.
            global $post;
            $saved = $post;
            $post  = $p;
            setup_postdata($p);
            $html = apply_filters('the_content', $p->post_content);
            $post = $saved;
            wp_reset_postdata();

            $body  = self::htmlToMarkdown($html);
            $body  = self::rewriteLinks($body, $map, $p->ID, $graph);
            $title = self::postTitle($p);

            $md  = $front . "\n\n# " . self::cleanInline($title) . "\n\n" . $body . "\n";
            $md  = preg_replace("/\n{3,}/", "\n\n", $md);
            return rtrim($md) . "\n";
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

        private static function buildIndex($posts, $map, $titles)
        {
            $site_title = wp_strip_all_tags(get_bloginfo('name'));
            $tagline    = wp_strip_all_tags(get_bloginfo('description'));

            $lines   = [];
            $lines[] = '# ' . $site_title . ' — Knowledge Base';
            if ($tagline !== '') $lines[] = '> ' . $tagline;
            $lines[] = '';

            $by_type = [];
            foreach ($posts as $p) {
                $by_type[$p->post_type][] = $p;
            }

            foreach ($by_type as $type => $list) {
                $lines[] = '## ' . self::postTypeHeading($type);
                foreach ($list as $p) {
                    $url   = esc_url_raw(home_url('/' . self::BASE . '/' . $map[$p->ID] . '.md'));
                    $title = self::escapeMarkdownText($titles[$p->ID]);
                    $lines[] = '- [' . $title . '](' . $url . ')';
                }
                $lines[] = '';
            }

            $out = implode("\n", $lines);
            $out = preg_replace("/\n{3,}/", "\n\n", $out);
            return rtrim($out) . "\n";
        }

        // ---- Frontmatter -------------------------------------------------

        private static function frontmatter($post, $conceptId)
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
            $lines[] = 'url: ' . self::yaml(get_permalink($post->ID));
            $lines[] = 'updated: ' . self::yaml(get_post_modified_time('c', true, $post));
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

        private static function postTypeHeading($post_type)
        {
            $obj = get_post_type_object($post_type);
            if ($obj && !empty($obj->labels->name)) {
                return $obj->labels->name;
            }
            return ucfirst($post_type);
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
                    if ($text === '') $text = $href;
                    if ($href === '' || strpos($href, '#') === 0) return $text;
                    return '[' . $text . '](' . $href . ')';

                case 'img':
                    $src = trim($node->getAttribute('src'));
                    if ($src === '') return '';
                    $alt = trim($node->getAttribute('alt'));
                    return '![' . $alt . '](' . $src . ')';

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

        // ---- Small text helpers (mirror xagio_llms.php) ------------------

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
