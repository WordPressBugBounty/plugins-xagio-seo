<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('XAGIO_GUTENBERG_RINGROBIN')) {

    /**
     * Registers two dynamic Gutenberg blocks that let users drag-and-drop
     * the RingRobin Form and Text Widget embeds into block-editor pages
     * (stock WP, Kadence Blocks, and Kadence theme — all use the same
     * block API).
     *
     *   - xagio-ringrobin/form — picks a form widget from the site's
     *     linked campaign and outputs the form embed script.
     *   - xagio-ringrobin/text — picks a text widget + display mode
     *     (Floating / Inline button / Embedded phone) and outputs the
     *     matching text-widget embed.
     *
     * Both blocks appear in a custom "Xagio" block-inserter category and
     * pull their picker options from XAGIO_RINGROBIN::OPT_WIDGETS
     * (the cached list refreshed via Settings → Integrations).
     *
     * No build step: blocks.js uses wp.element.createElement directly so
     * the file is shipped as-is.
     */
    class XAGIO_GUTENBERG_RINGROBIN
    {
        const HANDLE       = 'xagio-ringrobin-blocks';
        const BLOCK_FORM   = 'xagio-ringrobin/form';
        const BLOCK_TEXT   = 'xagio-ringrobin/text';
        const CATEGORY     = 'xagio';

        public static function initialize()
        {
            // register_block_type must run on init.
            add_action('init',                [__CLASS__, 'register_blocks']);
            // Custom block category — filter name changed in WP 5.8.
            if (function_exists('get_default_block_categories')) {
                add_filter('block_categories_all', [__CLASS__, 'register_category'], 10, 1);
            } else {
                add_filter('block_categories',     [__CLASS__, 'register_category_legacy'], 10, 1);
            }
        }

        public static function register_category($categories)
        {
            if (!is_array($categories)) {
                return $categories;
            }
            foreach ($categories as $cat) {
                if (isset($cat['slug']) && $cat['slug'] === self::CATEGORY) {
                    return $categories;
                }
            }
            $categories[] = [
                'slug'  => self::CATEGORY,
                'title' => __('Xagio', 'xagio-seo'),
                'icon'  => null,
            ];
            return $categories;
        }

        public static function register_category_legacy($categories)
        {
            // WP < 5.8 fallback.
            return self::register_category($categories);
        }

        public static function register_blocks()
        {
            if (!function_exists('register_block_type')) {
                return;
            }

            $blocks_file = XAGIO_PATH . '/modules/ringrobin/gutenberg/blocks.js';
            if (!file_exists($blocks_file)) {
                // Skip gracefully on a partial upload — the front-end
                // must never go down because one file is missing.
                return;
            }

            wp_register_script(
                self::HANDLE,
                XAGIO_URL . 'modules/ringrobin/gutenberg/blocks.js',
                ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
                defined('XAGIO_CURRENT_VERSION') ? XAGIO_CURRENT_VERSION : '1.0.0',
                true
            );

            wp_localize_script(self::HANDLE, 'xagioRingRobinBlocks', [
                'widgets' => self::get_widgets_for_picker(),
            ]);

            register_block_type(self::BLOCK_FORM, [
                'api_version'     => 2,
                'editor_script'   => self::HANDLE,
                'attributes'      => [
                    'formId' => ['type' => 'string', 'default' => ''],
                ],
                'render_callback' => [__CLASS__, 'render_form'],
            ]);

            register_block_type(self::BLOCK_TEXT, [
                'api_version'     => 2,
                'editor_script'   => self::HANDLE,
                'attributes'      => [
                    'textId' => ['type' => 'string', 'default' => ''],
                    'mode'   => ['type' => 'string', 'default' => 'inline'],
                ],
                'render_callback' => [__CLASS__, 'render_text'],
            ]);
        }

        /**
         * Server-side render for xagio-ringrobin/form. When the saved
         * block has no formId attribute (Gutenberg omits attributes
         * that match JS defaults), falls back to the latest form
         * widget so the live page mirrors the editor preview.
         */
        public static function render_form($attributes)
        {
            $form_id = isset($attributes['formId']) ? trim((string) $attributes['formId']) : '';
            if ($form_id === '') {
                $form_id = self::latest_widget_id('form');
            }
            if ($form_id === '') {
                return '';
            }
            $src    = 'https://auth.ringrobin.net/functions/v1/f/' . rawurlencode($form_id);
            $handle = 'xagio-rr-form-' . sanitize_key($form_id);
            wp_enqueue_script($handle, $src, [], null, true);
            return "\n<!-- RingRobin Form Embed -->\n"
                . '<div class="rr-form-embed" data-form-id="' . esc_attr($form_id) . '"></div>' . "\n";
        }

        /**
         * Server-side render for xagio-ringrobin/text. Output varies by
         * mode (floating omits the div per the RingRobin spec). Same
         * empty-attribute fallback as render_form.
         */
        public static function render_text($attributes)
        {
            $text_id = isset($attributes['textId']) ? trim((string) $attributes['textId']) : '';
            $mode    = isset($attributes['mode'])   ? (string) $attributes['mode']        : 'inline';
            if (!in_array($mode, ['floating', 'inline', 'embedded'], true)) {
                $mode = 'inline';
            }
            if ($text_id === '') {
                $text_id = self::latest_widget_id('text');
            }
            if ($text_id === '') {
                return '';
            }

            $base = 'https://auth.ringrobin.net/functions/v1/tw/' . rawurlencode($text_id);

            if ($mode === 'floating') {
                $handle = 'xagio-rr-text-' . sanitize_key($text_id) . '-floating';
                wp_enqueue_script($handle, $base, [], null, true);
                return "\n<!-- RingRobin Text Widget (Floating) -->\n";
            }

            $src    = $base . '?mode=' . rawurlencode($mode);
            $label  = $mode === 'embedded' ? 'Embedded Phone' : 'Inline Button';
            $handle = 'xagio-rr-text-' . sanitize_key($text_id) . '-' . sanitize_key($mode);
            wp_enqueue_script($handle, $src, [], null, true);
            return "\n<!-- RingRobin Text Widget (" . esc_html($label) . ") -->\n"
                . '<div class="rr-text-widget" data-form-id="' . esc_attr($text_id) . '"></div>' . "\n";
        }

        /**
         * Pick the latest widget of a given type, ordered by created_at
         * (string comparison works for ISO-8601), with the
         * last-encountered as a fallback for empty timestamps. Mirrors
         * the JS-side default-pick logic in blocks.js / Elementor.
         */
        private static function latest_widget_id($type)
        {
            $option  = class_exists('XAGIO_RINGROBIN') ? XAGIO_RINGROBIN::OPT_WIDGETS : 'xagio_ringrobin_widgets';
            $widgets = get_option($option, []);
            if (!is_array($widgets)) {
                return '';
            }
            $latest_id      = '';
            $latest_created = '';
            foreach ($widgets as $w) {
                if (!is_array($w) || empty($w['id']) || empty($w['type']) || $w['type'] !== $type) {
                    continue;
                }
                $created = isset($w['created_at']) ? (string) $w['created_at'] : '';
                if ($latest_id === '' || $created >= $latest_created) {
                    $latest_id      = (string) $w['id'];
                    $latest_created = $created;
                }
            }
            return $latest_id;
        }

        /**
         * Build the slim widget array shipped to the editor JS. Limits
         * fields to what the picker actually needs (id, name, type,
         * created_at for the "default to latest" pick).
         */
        private static function get_widgets_for_picker()
        {
            $option = class_exists('XAGIO_RINGROBIN') ? XAGIO_RINGROBIN::OPT_WIDGETS : 'xagio_ringrobin_widgets';
            $widgets = get_option($option, []);
            if (!is_array($widgets)) {
                return [];
            }
            $out = [];
            foreach ($widgets as $w) {
                if (!is_array($w) || empty($w['id']) || empty($w['type'])) {
                    continue;
                }
                if (!in_array($w['type'], ['form', 'text'], true)) {
                    continue;
                }
                $out[] = [
                    'id'         => (string) $w['id'],
                    'type'       => (string) $w['type'],
                    'name'       => isset($w['name'])       ? (string) $w['name']       : '',
                    'created_at' => isset($w['created_at']) ? (string) $w['created_at'] : '',
                ];
            }
            return $out;
        }
    }
}
