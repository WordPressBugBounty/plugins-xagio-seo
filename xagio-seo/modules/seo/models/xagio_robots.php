<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('XAGIO_MODEL_ROBOTS')) {

    class XAGIO_MODEL_ROBOTS
    {
        const OPTION_AI_RULES = 'XAGIO_AI_CRAWLER_RULES';

        public static function aiCrawlers()
        {
            return [
                'GPTBot'               => 'OpenAI (GPTBot)',
                'ChatGPT-User'         => 'OpenAI (ChatGPT-User)',
                'OAI-SearchBot'        => 'OpenAI (OAI-SearchBot)',
                'ClaudeBot'            => 'Anthropic (ClaudeBot)',
                'Claude-User'          => 'Anthropic (Claude-User)',
                'Google-Extended'      => 'Google (Google-Extended)',
                'PerplexityBot'        => 'Perplexity (PerplexityBot)',
                'Perplexity-User'      => 'Perplexity (Perplexity-User)',
                'Meta-ExternalAgent'   => 'Meta (External Agent)',
                'Applebot-Extended'    => 'Apple (Applebot-Extended)',
                'CCBot'                => 'Common Crawl (CCBot)',
                'Bytespider'           => 'ByteDance (Bytespider)',
            ];
        }

        public static function getAiRules()
        {
            $stored = get_option(self::OPTION_AI_RULES);
            if (!is_array($stored)) $stored = [];

            $rules = [];
            foreach (array_keys(self::aiCrawlers()) as $ua) {
                $rules[$ua] = (isset($stored[$ua]) && $stored[$ua] === 'disallow') ? 'disallow' : 'allow';
            }
            return $rules;
        }

        public static function initialize()
        {
            add_filter('robots_txt', ['XAGIO_MODEL_ROBOTS', 'fixRobotsTxt'], 20, 2);

            if (!XAGIO_HAS_ADMIN_PERMISSIONS) return;

            add_action('admin_post_xagio_robots_save', ['XAGIO_MODEL_ROBOTS', 'saveRobotsSettings']);
            add_action('admin_post_xagio_robots_get',  ['XAGIO_MODEL_ROBOTS', 'getRobotsContent']);
            add_action('admin_post_xagio_robots_ai_check', ['XAGIO_MODEL_ROBOTS', 'checkUpstreamRobots']);
        }

        public static function getDefaultRobots()
        {
            return "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n";
        }

        public static function getEffectiveRobots()
        {
            $blog_public = get_option('blog_public');
            $default     = ('0' === (string) $blog_public)
                ? "User-agent: *\nDisallow: /\n"
                : self::getDefaultRobots();

            return apply_filters('robots_txt', $default, $blog_public);
        }

        public static function buildAiBlocks($rules = null)
        {
            if (!is_array($rules)) {
                $rules = self::getAiRules();
            }
            $blocks = [];
            foreach ($rules as $ua => $state) {
                if ($state === 'disallow') {
                    $blocks[] = "User-agent: " . $ua;
                    $blocks[] = "Disallow: /";
                    $blocks[] = '';
                }
            }
            return $blocks ? implode("\n", $blocks) : '';
        }

        public static function stripManagedAiBlocks($content)
        {
            if (!is_string($content) || $content === '') return $content;
            $marker = '# AI crawlers';
            $pos = stripos($content, $marker);
            if ($pos === false) return $content;
            return rtrim(substr($content, 0, $pos));
        }

        public static function fixRobotsTxt($output, $public)
        {
            if (!$public) return $output;

            $custom = get_option('XAGIO_ROBOTS_TXT_CUSTOM');
            if (!empty($custom)) {
                $custom = self::stripManagedAiBlocks($custom);
                $output = $custom;
            }

            if (defined('XAGIO_ENABLE_SITEMAPS') && XAGIO_ENABLE_SITEMAPS) {
                $output  = preg_replace('#^Sitemap:\s*\S*wp-sitemap\.xml\s*$#mi', '', $output);
                $sitemap = home_url('/sitemap-xag.xml');
                if (stripos($output, $sitemap) === false) {
                    $output = rtrim($output) . "\n\nSitemap: " . esc_url($sitemap) . "\n";
                }
            }
	
            $ai_blocks = self::buildAiBlocks();
            if ($ai_blocks !== '') {
                $output = rtrim($output) . "\n\n# AI crawlers\n" . $ai_blocks;
            }

            return $output;
        }

        public static function getRobotsContent()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');
            xagio_json('success', '', self::getEffectiveRobots());
        }

        public static function saveRobotsSettings()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $mode = isset($_POST['mode']) ? sanitize_text_field(wp_unslash($_POST['mode'])) : 'save';

            if ($mode === 'reset') {
                delete_option('XAGIO_ROBOTS_TXT_CUSTOM');
                delete_option(self::OPTION_AI_RULES);
                xagio_json('success', 'Robots.txt reset to default.', self::getEffectiveRobots());
                return;
            }

            if ($mode === 'preview') {
                $txt = self::renderEffectiveFromPost($_POST);
                xagio_json('success', '', $txt);
                return;
            }

            if (isset($_POST['XAGIO_ROBOTS_TXT_CUSTOM'])) {
                $content = sanitize_textarea_field(wp_unslash($_POST['XAGIO_ROBOTS_TXT_CUSTOM']));
                $content = self::stripManagedAiBlocks($content);
                update_option('XAGIO_ROBOTS_TXT_CUSTOM', $content);
            }

            if (isset($_POST[self::OPTION_AI_RULES]) && is_array($_POST[self::OPTION_AI_RULES])) {
                $known = self::aiCrawlers();
                $clean = [];
                foreach ((array) $_POST[self::OPTION_AI_RULES] as $ua => $state) {
                    if (!isset($known[$ua])) continue;
                    if (is_string($state) && ($state === 'disallow' || $state === 'allow')) {
                        $clean[$ua] = $state;
                    } else {
                        $clean[$ua] = intval($state) ? 'allow' : 'disallow';
                    }
                }
                update_option(self::OPTION_AI_RULES, $clean);
            }

            xagio_json('success', 'Robots.txt saved successfully.', self::getEffectiveRobots());
        }

        private static function renderEffectiveFromPost($post)
        {
            $base = '';
            if (isset($post['XAGIO_ROBOTS_TXT_CUSTOM'])) {
                $base = sanitize_textarea_field(wp_unslash($post['XAGIO_ROBOTS_TXT_CUSTOM']));
                $base = self::stripManagedAiBlocks($base);
            }

            if (trim($base) === '') {
                $blog_public = get_option('blog_public');
                $base = ('0' === (string) $blog_public)
                    ? "User-agent: *\nDisallow: /\n"
                    : self::getDefaultRobots();
            }

            $output = $base;

            if (defined('XAGIO_ENABLE_SITEMAPS') && XAGIO_ENABLE_SITEMAPS) {
                $output  = preg_replace('#^Sitemap:\s*\S*wp-sitemap\.xml\s*$#mi', '', $output);
                $sitemap = home_url('/sitemap-xag.xml');
                if (stripos($output, $sitemap) === false) {
                    $output = rtrim($output) . "\n\nSitemap: " . esc_url($sitemap) . "\n";
                }
            }

            $posted_rules = [];
            if (isset($post[self::OPTION_AI_RULES]) && is_array($post[self::OPTION_AI_RULES])) {
                $known = self::aiCrawlers();
                foreach ((array) $post[self::OPTION_AI_RULES] as $ua => $state) {
                    if (!isset($known[$ua])) continue;
                    if (is_string($state) && ($state === 'disallow' || $state === 'allow')) {
                        $posted_rules[$ua] = $state;
                    } else {
                        $posted_rules[$ua] = intval($state) ? 'allow' : 'disallow';
                    }
                }
            }

            $ai_blocks = self::buildAiBlocks($posted_rules);
            if ($ai_blocks !== '') {
                $output = rtrim($output) . "\n\n# AI crawlers\n" . $ai_blocks;
            }

            return $output;
        }

        public static function checkUpstreamRobots()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $url = home_url('/robots.txt');
            $response = wp_remote_get($url, [
                'timeout'     => 8,
                'redirection' => 3,
                'sslverify'   => false,
                'headers'     => [
                    'Cache-Control' => 'no-cache',
                    'Pragma'        => 'no-cache',
                ],
            ]);

            if (is_wp_error($response)) {
                xagio_json('danger', 'Could not fetch /robots.txt: ' . $response->get_error_message());
                return;
            }

            $code = (int) wp_remote_retrieve_response_code($response);
            $body = (string) wp_remote_retrieve_body($response);

            if ($code !== 200) {
                xagio_json('danger', sprintf('Live /robots.txt returned HTTP %d.', $code), [
                    'url'      => $url,
                    'http'     => $code,
                    'mismatch' => [],
                    'body'     => substr($body, 0, 1000),
                ]);
                return;
            }

            $upstream_blocks = self::parseRobotsBlocks($body);
            $xagio_rules     = self::getAiRules();

            $mismatch = [];
            foreach (array_keys(self::aiCrawlers()) as $ua) {
                $ua_lc = strtolower($ua);
                $up_blocked = false;
                if (isset($upstream_blocks[$ua_lc])) {
                    foreach ($upstream_blocks[$ua_lc]['disallow'] as $path) {
                        if ($path === '/' || $path === '') {
                            $up_blocked = true;
                            break;
                        }
                    }
                }

                $xa_blocked = ($xagio_rules[$ua] === 'disallow');

                if ($up_blocked && !$xa_blocked) {
                    $mismatch[] = [
                        'user_agent' => $ua,
                        'kind'       => 'upstream_blocks',
                        'message'    => sprintf('%s is blocked upstream (likely CDN), but Xagio says allow.', $ua),
                    ];
                } else if (!$up_blocked && $xa_blocked) {
                    $mismatch[] = [
                        'user_agent' => $ua,
                        'kind'       => 'upstream_allows',
                        'message'    => sprintf('Xagio disallows %s but the live robots.txt does not — your block is not being served.', $ua),
                    ];
                }
            }

            xagio_json('success', $mismatch ? 'Upstream mismatch detected.' : 'Live robots.txt matches Xagio settings.', [
                'url'       => $url,
                'http'      => $code,
                'mismatch'  => $mismatch,
                'body'      => $body,
            ]);
        }

        private static function parseRobotsBlocks($body)
        {
            $body = str_replace("\r\n", "\n", (string) $body);
            $lines = explode("\n", $body);

            $blocks = [];
            $current = [];

            $flush = function () use (&$blocks, &$current) {
                if (empty($current['agents'])) {
                    $current = [];
                    return;
                }
                foreach ($current['agents'] as $agent) {
                    $key = strtolower($agent);
                    if (!isset($blocks[$key])) {
                        $blocks[$key] = ['user_agent' => $agent, 'allow' => [], 'disallow' => []];
                    }
                    if (!empty($current['allow'])) {
                        $blocks[$key]['allow'] = array_values(array_unique(array_merge($blocks[$key]['allow'], $current['allow'])));
                    }
                    if (!empty($current['disallow'])) {
                        $blocks[$key]['disallow'] = array_values(array_unique(array_merge($blocks[$key]['disallow'], $current['disallow'])));
                    }
                }
                $current = [];
            };

            $in_rules = false;
            foreach ($lines as $line) {
                $trim = trim($line);
                if ($trim === '' || $trim[0] === '#') {
                    if ($in_rules) {
                        $flush();
                        $in_rules = false;
                    }
                    continue;
                }

                if (!preg_match('/^([A-Za-z\-]+)\s*:\s*(.*)$/', $trim, $m)) continue;

                $key   = strtolower($m[1]);
                $value = trim($m[2]);

                if ($key === 'user-agent') {
                    if ($in_rules) {
                        $flush();
                        $in_rules = false;
                    }
                    if (!isset($current['agents'])) $current['agents'] = [];
                    $current['agents'][] = $value;
                } else if ($key === 'allow') {
                    if (!isset($current['allow'])) $current['allow'] = [];
                    $current['allow'][] = $value;
                    $in_rules = true;
                } else if ($key === 'disallow') {
                    if (!isset($current['disallow'])) $current['disallow'] = [];
                    $current['disallow'][] = $value;
                    $in_rules = true;
                }
            }

            $flush();
            return $blocks;
        }
    }
}
