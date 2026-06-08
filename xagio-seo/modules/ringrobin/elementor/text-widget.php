<?php
if (!defined('ABSPATH')) {
    exit;
}

// Defensive guard: this file is only ever included from the
// elementor/widgets/register hook, but if it ever gets reached before
// Elementor's autoloader has the parent class available, bail out
// instead of fataling at class-definition time.
if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

if (!class_exists('Xagio_RingRobin_Text_Widget')) {

    class Xagio_RingRobin_Text_Widget extends \Elementor\Widget_Base
    {
        public function get_name()
        {
            return 'xagio-ringrobin-text';
        }

        public function get_title()
        {
            return esc_html__('RingRobin Text Widget', 'xagio-seo');
        }

        public function get_icon()
        {
            return 'eicon-call-to-action';
        }

        public function get_categories()
        {
            return ['xagio'];
        }

        public function get_keywords()
        {
            return ['ringrobin', 'text', 'sms', 'click-to-text', 'xagio'];
        }

        protected function register_controls()
        {
            $this->start_controls_section('section_text', [
                'label' => esc_html__('RingRobin Text Widget', 'xagio-seo'),
            ]);

            $options = ['' => esc_html__('— Select a text widget —', 'xagio-seo')];
            $widgets = get_option(class_exists('XAGIO_RINGROBIN') ? XAGIO_RINGROBIN::OPT_WIDGETS : 'xagio_ringrobin_widgets', []);

            // Track the latest text widget so a freshly-dragged Elementor
            // widget defaults to it — saves a click when the user only has
            // one text widget or just created a new one.
            $latest_id      = '';
            $latest_created = '';
            if (is_array($widgets)) {
                foreach ($widgets as $w) {
                    if (!is_array($w) || empty($w['id']) || empty($w['type'])) {
                        continue;
                    }
                    if ($w['type'] !== 'text') {
                        continue;
                    }
                    $options[$w['id']] = !empty($w['name']) ? $w['name'] : $w['id'];

                    $created = isset($w['created_at']) ? (string) $w['created_at'] : '';
                    if ($latest_id === '' || $created >= $latest_created) {
                        $latest_id      = $w['id'];
                        $latest_created = $created;
                    }
                }
            }

            $this->add_control('text_id', [
                'label'   => esc_html__('Text widget', 'xagio-seo'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => $options,
                'default' => $latest_id,
            ]);

            $this->add_control('mode', [
                'label'   => esc_html__('Display mode', 'xagio-seo'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'floating' => esc_html__('Floating (bottom of page)', 'xagio-seo'),
                    'inline'   => esc_html__('Inline button', 'xagio-seo'),
                    'embedded' => esc_html__('Embedded phone', 'xagio-seo'),
                ],
                'default' => 'inline',
            ]);

            $this->add_control('refresh_hint', [
                'type'            => \Elementor\Controls_Manager::RAW_HTML,
                'raw'             => '<em style="color:#9ca3af;">' . esc_html__('Don\'t see your widget? Open Xagio Settings → Integrations and create one — it will appear here after a page refresh.', 'xagio-seo') . '</em>',
                'content_classes' => 'elementor-control-field-description',
            ]);

            $this->end_controls_section();
        }

        protected function render()
        {
            $settings = $this->get_settings_for_display();
            $text_id  = isset($settings['text_id']) ? trim($settings['text_id']) : '';
            $mode     = isset($settings['mode']) ? $settings['mode'] : 'inline';
            if (!in_array($mode, ['floating', 'inline', 'embedded'], true)) {
                $mode = 'inline';
            }

            $is_edit_mode = (\Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode())
                || (isset(\Elementor\Plugin::$instance->preview) && \Elementor\Plugin::$instance->preview->is_preview_mode());

            if ($text_id === '') {
                if ($is_edit_mode) {
                    echo '<div style="padding:24px; background:#f5f7fb; border:1px dashed #c5c5c5; border-radius:8px; text-align:center; color:#545454;">';
                    echo '<strong>' . esc_html__('RingRobin Text Widget', 'xagio-seo') . '</strong><br>';
                    echo esc_html__('Select a text widget from the panel on the left.', 'xagio-seo');
                    echo '</div>';
                }
                return;
            }

            if ($is_edit_mode) {
                $name       = $this->lookup_widget_name($text_id);
                $mode_label = $this->mode_label($mode);
                echo '<div style="padding:24px; background:#eef5ff; border:1px solid #b8d4ff; border-radius:8px; text-align:center; color:#1a4674;">';
                echo '<strong>' . esc_html__('RingRobin Text Widget', 'xagio-seo') . '</strong><br>';
                echo '<span style="color:#545454; font-size:13px;">' . esc_html($name) . ' · ' . esc_html($mode_label) . '</span><br>';
                echo '<span style="color:#9ca3af; font-size:12px;">' . esc_html__('(rendered on the live page)', 'xagio-seo') . '</span>';
                echo '</div>';
                return;
            }

            $base = 'https://auth.ringrobin.net/functions/v1/tw/' . rawurlencode($text_id);

            if ($mode === 'floating') {
                $handle = 'xagio-rr-text-' . sanitize_key($text_id) . '-floating';
                wp_enqueue_script($handle, $base, [], null, true);
                ?>
                <!-- RingRobin Text Widget (Floating) -->
                <?php
                return;
            }

            $src    = $base . '?mode=' . rawurlencode($mode);
            $label  = $mode === 'embedded' ? 'Embedded Phone' : 'Inline Button';
            $handle = 'xagio-rr-text-' . sanitize_key($text_id) . '-' . sanitize_key($mode);
            wp_enqueue_script($handle, $src, [], null, true);
            ?>
            <!-- RingRobin Text Widget (<?php echo esc_html($label); ?>) -->
            <div class="rr-text-widget" data-form-id="<?php echo esc_attr($text_id); ?>"></div>
            <?php
        }

        private function lookup_widget_name($id)
        {
            $widgets = get_option(class_exists('XAGIO_RINGROBIN') ? XAGIO_RINGROBIN::OPT_WIDGETS : 'xagio_ringrobin_widgets', []);
            if (!is_array($widgets)) {
                return $id;
            }
            foreach ($widgets as $w) {
                if (is_array($w) && !empty($w['id']) && $w['id'] === $id) {
                    return !empty($w['name']) ? $w['name'] : $id;
                }
            }
            return $id;
        }

        private function mode_label($mode)
        {
            switch ($mode) {
                case 'floating':
                    return esc_html__('Floating', 'xagio-seo');
                case 'embedded':
                    return esc_html__('Embedded phone', 'xagio-seo');
                case 'inline':
                default:
                    return esc_html__('Inline button', 'xagio-seo');
            }
        }
    }
}
