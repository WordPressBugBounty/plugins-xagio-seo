<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('XAGIO_MODEL_ROBOTS')) {

    class XAGIO_MODEL_ROBOTS
    {
        const OPTION_AI_RULES     = 'XAGIO_AI_CRAWLER_RULES';
        const OPTION_CS_ENABLED   = 'XAGIO_CONTENT_SIGNAL_ENABLED';
        const OPTION_CS_SIGNALS   = 'XAGIO_CONTENT_SIGNAL';

        public static function contentSignalKeys()
        {
            // Order matters: this is the order signals appear in the directive line.
            return [
                'search'   => 'Search',
                'ai-input' => 'AI input',
                'ai-train' => 'AI training',
            ];
        }

        public static function contentSignalDefaults()
        {
            // Recommended default for most publishers: be found and cited, don't train.
            return ['search' => 'yes', 'ai-input' => 'yes', 'ai-train' => 'no'];
        }

        public static function getContentSignal()
        {
            $enabled  = (get_option(self::OPTION_CS_ENABLED) === '1') ? '1' : '0';
            $stored   = get_option(self::OPTION_CS_SIGNALS);
            if (!is_array($stored)) $stored = [];

            $defaults = self::contentSignalDefaults();
            $signals  = [];
            foreach (array_keys(self::contentSignalKeys()) as $key) {
                if (isset($stored[$key])) {
                    $signals[$key] = ($stored[$key] === 'yes') ? 'yes' : 'no';
                } else {
                    $signals[$key] = $defaults[$key];
                }
            }

            return ['enabled' => $enabled, 'signals' => $signals];
        }

        public static function buildContentSignalLine($cfg = null)
        {
            if (!is_array($cfg)) {
                $cfg = self::getContentSignal();
            }
            if (($cfg['enabled'] ?? '0') !== '1') return '';

            $signals = isset($cfg['signals']) && is_array($cfg['signals']) ? $cfg['signals'] : [];

            $parts = [];
            foreach (array_keys(self::contentSignalKeys()) as $key) {
                $val = (isset($signals[$key]) && $signals[$key] === 'yes') ? 'yes' : 'no';
                $parts[] = $key . '=' . $val;
            }

            return 'Content-Signal: ' . implode(', ', $parts);
        }

        public static function stripContentSignal($content)
        {
            if (!is_string($content) || $content === '') return $content;

            $lines = preg_split('/\r\n|\r|\n/', $content);
            $out   = [];
            foreach ($lines as $line) {
                if (preg_match('/^\s*Content-Signal\s*:/i', $line)) continue;
                $out[] = $line;
            }
            return implode("\n", $out);
        }

        public static function injectContentSignal($output, $cfg = null)
        {
            $line = self::buildContentSignalLine($cfg);
            if ($line === '') return $output;

            $lines    = preg_split('/\r\n|\r|\n/', $output);
            $result   = [];
            $injected = false;
            foreach ($lines as $line_text) {
                $result[] = $line_text;
                if (!$injected && preg_match('/^\s*User-agent\s*:\s*\*\s*$/i', $line_text)) {
                    $result[] = $line;
                    $injected = true;
                }
            }

            if (!$injected) {
                array_unshift($result, 'User-agent: *', $line, '');
            }

            return implode("\n", $result);
        }

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

            $output = self::stripContentSignal($output);
            $output = self::injectContentSignal($output);

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
                delete_option(self::OPTION_CS_ENABLED);
                delete_option(self::OPTION_CS_SIGNALS);
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
                $content = self::stripContentSignal($content);
                update_option('XAGIO_ROBOTS_TXT_CUSTOM', $content);
            }

            $cs = self::parsePostedContentSignal($_POST);
            update_option(self::OPTION_CS_ENABLED, $cs['enabled']);
            update_option(self::OPTION_CS_SIGNALS, $cs['signals']);

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

        private static function parsePostedContentSignal($post)
        {
            $enabled = (isset($post[self::OPTION_CS_ENABLED]) && intval($post[self::OPTION_CS_ENABLED])) ? '1' : '0';

            $defaults = self::contentSignalDefaults();
            $posted   = (isset($post[self::OPTION_CS_SIGNALS]) && is_array($post[self::OPTION_CS_SIGNALS]))
                ? $post[self::OPTION_CS_SIGNALS]
                : [];

            $signals = [];
            foreach (array_keys(self::contentSignalKeys()) as $key) {
                if (isset($posted[$key])) {
                    $signals[$key] = intval($posted[$key]) ? 'yes' : 'no';
                } else {
                    $signals[$key] = $defaults[$key];
                }
            }

            return ['enabled' => $enabled, 'signals' => $signals];
        }

        private static function renderEffectiveFromPost($post)
        {
            $base = '';
            if (isset($post['XAGIO_ROBOTS_TXT_CUSTOM'])) {
                $base = sanitize_textarea_field(wp_unslash($post['XAGIO_ROBOTS_TXT_CUSTOM']));
                $base = self::stripManagedAiBlocks($base);
                $base = self::stripContentSignal($base);
            }

            if (trim($base) === '') {
                $blog_public = get_option('blog_public');
                $base = ('0' === (string) $blog_public)
                    ? "User-agent: *\nDisallow: /\n"
                    : self::getDefaultRobots();
            }

            $output = self::injectContentSignal($base, self::parsePostedContentSignal($post));

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
