<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('XAGIO_ELEMENTOR_RINGROBIN')) {

    /**
     * Registers two Elementor widgets that let users drag-and-drop the
     * RingRobin Form and Text Widget embeds into pages:
     *
     *   - Xagio_RingRobin_Form_Widget — picks a form widget from the
     *     site's linked campaign and outputs the form embed script.
     *   - Xagio_RingRobin_Text_Widget — picks a text widget + display
     *     mode (Floating / Inline button / Embedded phone) and outputs
     *     the matching text-widget embed.
     *
     * Both widgets appear in a custom "Xagio" Elementor panel category
     * and pull their picker options from XAGIO_RINGROBIN::OPT_WIDGETS
     * (the cached widget list refreshed via the Settings → Integrations
     * panel).
     */
    class XAGIO_ELEMENTOR_RINGROBIN
    {
        public static function initialize()
        {
            // Defer until Elementor has had a chance to load.
            add_action('plugins_loaded', [__CLASS__, 'maybe_bootstrap'], 20);
        }

        public static function maybe_bootstrap()
        {
            if (!did_action('elementor/loaded')) {
                return;
            }

            add_action('elementor/elements/categories_registered', [__CLASS__, 'register_category']);
            add_action('elementor/widgets/register',               [__CLASS__, 'register_widgets']);
        }

        public static function register_category($elements_manager)
        {
            $elements_manager->add_category('xagio', [
                'title' => esc_html__('Xagio', 'xagio-seo'),
                'icon'  => 'fa fa-plug',
            ]);
        }

        public static function register_widgets($widgets_manager)
        {
            // Parent class must be available before our widget classes can be
            // defined (they extend \Elementor\Widget_Base).
            if (!class_exists('\Elementor\Widget_Base')) {
                return;
            }

            $form_file = XAGIO_PATH . '/modules/ringrobin/elementor/form-widget.php';
            $text_file = XAGIO_PATH . '/modules/ringrobin/elementor/text-widget.php';

            // Skip gracefully if the widget files were not uploaded — a partial
            // deploy must not bring the whole frontend down.
            if (file_exists($form_file)) {
                require_once $form_file;
                if (class_exists('Xagio_RingRobin_Form_Widget')) {
                    $widgets_manager->register(new Xagio_RingRobin_Form_Widget());
                }
            }
            if (file_exists($text_file)) {
                require_once $text_file;
                if (class_exists('Xagio_RingRobin_Text_Widget')) {
                    $widgets_manager->register(new Xagio_RingRobin_Text_Widget());
                }
            }
        }
    }
}
