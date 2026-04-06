<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('XAGIO_MODEL_LLMS')) {

    class XAGIO_MODEL_LLMS {

        const OPTION_CONFIG   = 'XAGIO_LLMS_CONFIG';
        const OPTION_TEXT     = 'XAGIO_LLMS_TXT';
        const OPTION_REWRITE  = 'XAGIO_LLMS_REWRITE_READY';
        const QUERY_VAR       = 'xagio_llms';
        const FILE_NAME       = 'llms.txt';

        public static function initialize() {

            // Serve /llms.txt via rewrite (works even if we can't write a real file)
            add_action('init', [__CLASS__, 'add_rewrite']);
            add_filter('query_vars', [__CLASS__, 'register_query_var']);
            add_action('template_redirect', [__CLASS__, 'maybe_serve_llms']);

            // AJAX + admin-post for flexibility with your existing xagio_data.wp_post endpoint
            add_action('wp_ajax_xagio_llms_save', [__CLASS__, 'handle_save']);
            add_action('admin_post_xagio_llms_save', [__CLASS__, 'handle_save']);
        }

        /** -----------------------
         *  Routing / Rewrite
         *  ----------------------*/
        public static function add_rewrite() {
            add_rewrite_rule('^llms\.txt$', 'index.php?' . self::QUERY_VAR . '=1', 'top');
        }

        public static function register_query_var($vars) {
            $vars[] = self::QUERY_VAR;
            return $vars;
        }

        public static function maybe_serve_llms() {
            if (intval(get_query_var(self::QUERY_VAR)) !== 1) return;

            $txt = get_option(self::OPTION_TEXT, '');
            if ($txt === '') {
                // fallback – generate on the fly from stored config if text missing
                $cfg = get_option(self::OPTION_CONFIG, []);
                $txt = self::generate_text(self::sanitize_config($cfg));
            }

            nocache_headers();
            header('Content-Type: text/plain; charset=UTF-8');
            echo esc_html($txt);
            exit;
        }

        /** -----------------------
         *  Save / Update
         *  ----------------------*/
        public static function handle_save() {

            // Nonce (accept either the common xagio nonce or a specific llms nonce)
            if (isset($_POST['_xagio_nonce'])) {
                check_ajax_referer('xagio_nonce', '_xagio_nonce');
            } elseif (isset($_POST['xagio_llms_nonce'])) {
                if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['xagio_llms_nonce'])), 'xagio_llms_save')) {
                    wp_send_json_error(['message' => 'Invalid nonce.'], 403);
                }
            } else {
                wp_send_json_error(['message' => 'Missing nonce.'], 403);
            }

            // Capability
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Unauthorized.'], 403);
            }

            // mode: 'update' (save settings only) or 'save' (also publish)
            $mode = isset($_POST['mode']) ? sanitize_text_field(wp_unslash($_POST['mode'])) : 'update';

            if (!isset($_POST['config'])) {
                wp_send_json_error(['message' => 'Missing config.'], 400);
            }

            $raw = json_decode(wp_unslash($_POST['config']), true);
            if (!is_array($raw)) {
                wp_send_json_error(['message' => 'Invalid JSON.'], 400);
            }

            $cfg = self::sanitize_config($raw);
            $txt = self::generate_text($cfg);

            // Persist settings + generated text
            update_option(self::OPTION_CONFIG, $cfg, false);
            update_option(self::OPTION_TEXT, $txt, false);

            $published = false;
            $publish_msg = '';

            if ($mode === 'save') {
                // Try to write a physical file first
                $reason = '';
                $published = self::write_file($txt, $reason);

                // Ensure rewrite endpoint exists as fallback (and flush once)
                if (!get_option(self::OPTION_REWRITE)) {
                    flush_rewrite_rules(false);
                    update_option(self::OPTION_REWRITE, 1, false);
                }

                if ($published) {
                    $publish_msg = sprintf('Published to %s', esc_url(home_url('/' . self::FILE_NAME)));
                } else {
                    $publish_msg = sprintf(
                        'Settings saved. Could not write %1$s (%2$s). It is still served at %3$s.',
                        esc_html(self::FILE_NAME),
                        esc_html($reason ?: 'permission denied'),
                        esc_url(home_url('/' . self::FILE_NAME))
                    );
                }
            }

            wp_send_json_success([
                'message' => $mode === 'save' ? $publish_msg : 'Settings updated.',
                'url'     => home_url('/' . self::FILE_NAME),
                'published' => (bool) $published
            ]);
        }

        /** -----------------------
         *  Sanitization / Build
         *  ----------------------*/
        private static function clean_path_line($line) {
            $line = trim((string) $line);
            // Strip CR/LF injection and illegal bytes
            $line = preg_replace('/[\r\n\x00]/', '', $line);
            // Keep URL-ish characters
            $line = preg_replace('#[^A-Za-z0-9\-._~!$&\'()*+,;=:@/\\*]#', '', $line);
            // Normalize: allow empty, "/", or startswith "/"
            if ($line !== '' && $line[0] !== '/' && $line[0] !== '*') {
                $line = '/' . $line;
            }
            return $line;
        }

        private static function clean_user_agent($xagio_ua) {
            $xagio_ua = trim((string) $xagio_ua);
            // Allow readable UA tokens; remove control chars
            $xagio_ua = preg_replace('/[\r\n\x00]/', '', $xagio_ua);
            // Strip characters that would break the file
            $xagio_ua = preg_replace('/[^A-Za-z0-9 \-._*\/]/', '', $xagio_ua);
            return $xagio_ua;
        }

        public static function sanitize_config($raw) {
            $out = ['rules' => [], 'extra' => ''];

            if (isset($raw['rules']) && is_array($raw['rules'])) {
                $seen = [];
                foreach ($raw['rules'] as $block) {
                    $xagio_ua = self::clean_user_agent($block['user_agent'] ?? '');
                    if ($xagio_ua === '') continue;

                    // Dedup UA blocks – merge if repeated
                    $xagio_key = strtolower($xagio_ua);
                    if (!isset($seen[$xagio_key])) {
                        $seen[$xagio_key] = ['user_agent' => $xagio_ua, 'allow' => [], 'disallow' => []];
                    }

                    foreach ((array) ($block['allow'] ?? []) as $ln) {
                        $ln = self::clean_path_line($ln);
                        if ($ln !== '') $seen[$xagio_key]['allow'][] = $ln;
                    }
                    foreach ((array) ($block['disallow'] ?? []) as $ln) {
                        $ln = self::clean_path_line($ln);
                        if ($ln !== '') $seen[$xagio_key]['disallow'][] = $ln;
                    }

                    // unique paths
                    $seen[$xagio_key]['allow']    = array_values(array_unique($seen[$xagio_key]['allow']));
                    $seen[$xagio_key]['disallow'] = array_values(array_unique($seen[$xagio_key]['disallow']));
                }
                // reindex
                $out['rules'] = array_values($seen);
            }

            if (!empty($raw['extra'])) {
                // Normalize extra: strip CR, keep single \n
                $extra = str_replace("\r", '', (string) $raw['extra']);
                $lines = array_map('trim', explode("\n", $extra));
                $lines = array_filter($lines, function ($l) {
                    return $l !== '';
                });
                // Prevent header/body injections
                $safe = array_map(function ($l) {
                    return preg_replace('/[\x00-\x1f\x7f]/', '', $l);
                }, $lines);
                $out['extra'] = implode("\n", $safe);
            }

            return $out;
        }

        public static function generate_text($cfg) {
            $buf = [];

            if (!empty($cfg['rules']) && is_array($cfg['rules'])) {
                foreach ($cfg['rules'] as $block) {
                    $xagio_ua = self::clean_user_agent($block['user_agent'] ?? '');
                    if ($xagio_ua === '') continue;

                    $buf[] = 'User-Agent: ' . $xagio_ua;

                    if (!empty($block['allow'])) {
                        foreach ($block['allow'] as $xagio_p) {
                            $xagio_p = self::clean_path_line($xagio_p);
                            if ($xagio_p !== '') $buf[] = 'Allow: ' . $xagio_p;
                        }
                    }
                    if (!empty($block['disallow'])) {
                        foreach ($block['disallow'] as $xagio_p) {
                            $xagio_p = self::clean_path_line($xagio_p);
                            if ($xagio_p !== '') $buf[] = 'Disallow: ' . $xagio_p;
                        }
                    }
                    $buf[] = ''; // blank line between blocks
                }
            }

            if (!empty($cfg['extra'])) {
                $buf[] = '# Extra rules';
                $buf[] = trim($cfg['extra']);
                $buf[] = '';
            }

            // Ensure trailing newline; collapse repeated blanks
            $txt = preg_replace("/\n{3,}/", "\n\n", implode("\n", $buf));
            $txt = rtrim($txt) . "\n";

            return $txt;
        }

        /** -----------------------
         *  File write (best effort)
         *  ----------------------*/
        private static function write_file($contents, &$reason = '') {
            $target = trailingslashit(ABSPATH) . self::FILE_NAME;

            // Try WP_Filesystem first
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            $creds = request_filesystem_credentials('', '', false, false, null);
            WP_Filesystem($creds);
            global $wp_filesystem;

            if ($wp_filesystem && is_object($wp_filesystem)) {
                $ok = $wp_filesystem->put_contents($target, $contents, FS_CHMOD_FILE);
                if ($ok) return true;
                $reason = 'WP_Filesystem failed';
            }

            // Fallback to native write
            $bytes = @file_put_contents($target, $contents);
            if ($bytes !== false) return true;

            if ($reason === '') $reason = 'file_put_contents failed';
            return false;
        }
    }
}
