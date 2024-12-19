<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_CORE')) {

    class XAGIO_CORE
    {

        // Init the plugin
        public static function init()
        {
            // Init the models
            XAGIO_CORE::loadModels();

            // Register hooks
            XAGIO_CORE::registerHooks();

            // Perform a version check
            XAGIO_CORE::checkVersion();
        }

        // Load all available Modules and init them
        public static function loadModels($method = 'initialize')
        {
            $models = xagio_get_models();
            foreach ($models as $model) {

                if (file_exists($model)) {

                    $class = 'XAGIO_MODEL_' . strtoupper(str_replace('xagio_', '', str_replace('.php', '', basename($model))));

                    if (!class_exists($class)) {
                        require_once($model);
                    }

                    // Init the model method
                    if (method_exists($class, $method)) {
                        call_user_func([
                            $class,
                            $method
                        ]);
                    }
                }
            }
        }

        // Init hooks
        public static function registerHooks()
        {
            add_action('admin_init', [
                'XAGIO_CORE',
                'registerAssets'
            ]);
            add_action('admin_menu', [
                'XAGIO_CORE',
                'createPages'
            ]);
            add_action('admin_enqueue_scripts', [
                'XAGIO_CORE',
                'loadAdminAssets'
            ], 1, 1);
            add_action('wp_enqueue_scripts', [
                'XAGIO_CORE',
                'loadUserAssets'
            ], 1, 1);
            add_filter('plugin_action_links', [
                'XAGIO_CORE',
                'customActionLinks'
            ], 1, 2);
        }

        // When plugin is activated
        public static function activate()
        {
            // Update tables
            XAGIO_CORE::loadModels('createTable');
        }

        // When plugin is uninstalled
        public static function uninstall()
        {
            // Update tables
            XAGIO_CORE::loadModels('removeTable');
        }

        // Create Menu Pages
        public static function createPages()
        {
            if (XAGIO_HIDDEN || !XAGIO_HAS_ADMIN_PERMISSIONS)
                return;

            global $xagio_global_js, $xagio_global_css;

            $modules = glob(XAGIO_PATH . '/modules/*');
            array_unshift($modules, 'ADMIN_MENU');

            $pages = [];

            foreach ($modules as $m) {

                $page = false;
                if ($m == 'ADMIN_MENU') {
                    $page = [
                        "Type"       => "MENU",
                        "Page_Title" => "Xagio",
                        "Menu_Title" => "Xagio",
                        "Capability" => "manage_options",
                        "Slug"       => "xagio-dashboard",
                        "Icon"       => "/assets/img/logo-menu-xagio.webp",
                        "JavaScript" => 'xagio_wizard,xagio_dashboard',
                        "Css"        => 'xagio_animate,xagio_wizard,xagio_dashboard',
                        "Position"   => 0
                    ];
                } else {
                    $page = $m . DIRECTORY_SEPARATOR . 'page.php';

                    if (!file_exists($page)) {
                        $page = false;
                    } else {
                        $page = xagio_parse_page($page);
                    }
                }

                if ($page !== false) {
                    $pages[] = $page;
                }

            }

            usort($pages, function ($page1, $page2) {
                return $page1['Position'] <=> $page2['Position'];
            });


            foreach ($pages as $page) {

                $page_hook_suffix = NULL;

                if ($page['Type'] == 'MENU') {

                    $page_hook_suffix = add_menu_page($page['Page_Title'], $page['Menu_Title'], $page['Capability'], $page['Slug'], 'xagio_load_page', XAGIO_URL . $page['Icon'], 2);

                } else {
                    $page_hook_suffix = add_submenu_page($page['Parent_Slug'], $page['Page_Title'], $page['Menu_Title'], $page['Capability'], $page['Slug'], 'xagio_load_page');

                }

                add_action('admin_print_scripts-' . $page_hook_suffix, function () use ($page, $xagio_global_js) {

                    foreach ($xagio_global_js as $js) {

                        wp_enqueue_script($js);

                    }

                    foreach (explode(',', $page['JavaScript']) as $enqueueName) {

                        wp_enqueue_script($enqueueName);

                    }
                });

                add_action('admin_print_styles-' . $page_hook_suffix, function () use ($page, $xagio_global_css) {

                    foreach ($xagio_global_css as $css) {

                        wp_enqueue_style($css);

                    }

                    foreach (explode(',', $page['Css']) as $enqueueName) {

                        wp_enqueue_style($enqueueName);

                    }
                });

            }
        }

        // Register all Scripts and Styles that are being used in Admin Area
        public static function registerAssets()
        {
            // Register Fonts
            wp_register_style('xagio_font_outfit', XAGIO_URL . 'assets/css/fonts/Outfit/outfit.css', [], '1.0');

            // Register all scripts that we'll load
            $vendor_scripts = glob(XAGIO_PATH . '/assets/js/vendor/*.js');
            $global_scripts = glob(XAGIO_PATH . '/assets/js/*.js');
            $page_scripts   = glob(XAGIO_PATH . '/modules/*/*.js');
            foreach (array_merge($vendor_scripts, $global_scripts, $page_scripts) as $script) {
                $script      = str_replace(XAGIO_PATH . '/', '', $script);
                $script_name = str_replace('.js', '', basename($script));
                wp_register_script('xagio_' . $script_name, XAGIO_URL . $script, ['jquery'], '1.0', true);
            }

            /**
             *  Add a global JS object to main script
             */

            foreach ([
                         'xagio_main',
                         'xagio_user',
                         'xagio_global'
                     ] as $script) {
                wp_localize_script($script, 'xagio_data', [
                    'wp_get'      => admin_url('admin-ajax.php'),
                    'wp_post'     => admin_url('admin-post.php'),
                    'wp_admin'    => admin_url(),
                    'plugins_url' => plugins_url('/', dirname(__FILE__)),
                    'site_name'   => get_bloginfo('name'),
                    'site_url'    => get_site_url(),
                    'panel_url'   => XAGIO_PANEL_URL,
                    'domain'      => XAGIO_DOMAIN,
                    'uploads_dir' => wp_upload_dir(),
                    'connected'   => XAGIO_CONNECTED,
                    'api_key'     => XAGIO_API::getAPIKey(),
                    'nonce'       => wp_create_nonce('xagio_nonce')
                ]);
            }

            // Register all styles that we'll load
            $vendor_styles = glob(XAGIO_PATH . '/assets/css/vendor/*.css');
            $global_styles = glob(XAGIO_PATH . '/assets/css/*.css');
            $page_styles   = glob(XAGIO_PATH . '/modules/*/*.css');
            foreach (array_merge($vendor_styles, $global_styles, $page_styles) as $style) {
                $style      = str_replace(XAGIO_PATH . '/', '', $style);
                $style_name = str_replace('.css', '', basename($style));
                wp_register_style('xagio_' . $style_name, XAGIO_URL . $style, [], '1.0');
            }
        }

        // Create/Modify Tables on new Update
        public static function checkVersion()
        {
            $current_version = xagio_get_version();

            if (get_option('XAGIO_CURRENT_VERSION') != $current_version) {

                // Update tables
                XAGIO_CORE::loadModels('createTable');

                // Update the new version
                update_option('XAGIO_CURRENT_VERSION', $current_version);
            }

            // If Xagio is not connected / remove hidden
            if (XAGIO_HIDDEN && !XAGIO_CONNECTED) {
                update_option('XAGIO_HIDDEN', false);
            }
        }

        // Enqueue scripts admin scripts
        public static function loadAdminAssets($hook)
        {

            if ($hook == 'post-new.php' || $hook == 'post.php' || $hook == 'term.php') {

                wp_enqueue_style('xagio_chosen');
                wp_enqueue_style('xagio_admin');

                wp_enqueue_script('xagio_datatables');
                wp_enqueue_script('xagio_admin');
                wp_enqueue_script('xagio_global');
                wp_enqueue_style('xagio_icons');
                wp_enqueue_script('xagio_multisortable');

                // CodeMirror
                $cm_settings['codeEditor'] = wp_enqueue_code_editor(['type' => 'text/x-php']);
                wp_localize_script('jquery', 'cm_settings', $cm_settings);

                wp_enqueue_script('wp-theme-plugin-editor');
                wp_enqueue_style('wp-codemirror');

                // File upload scripts
                wp_enqueue_script('media-upload');
                wp_enqueue_script('thickbox');

                // Chosen
                wp_enqueue_script('xagio_chosen');

                wp_enqueue_style('thickbox');

            } else {

                if ($hook === 'xagio_page_xagio-projectplanner') {
                    wp_enqueue_media();
                }


                wp_enqueue_style('xagio_font_outfit');
                wp_enqueue_script('xagio_global');
                wp_enqueue_style('xagio_global');
                wp_enqueue_style('xagio_icons');

            }


        }

        // Register all Scripts and Styles that are being used in User Area
        public static function loadUserAssets()
        {
            // Enqueue Scripts
            wp_enqueue_script('xagio_user');
        }

        // Add custom action links
        public static function customActionLinks($links, $file)
        {
            if ($file == XAGIO_SLUG) {
                $custom_links = [];
                $custom_links[] = '<a target="popup" rel="noopener noreferrer" onclick="window.open(\'https://tawk.to/chat/5f9af4237f0a8e57c2d8421e/default\',\'popup\',\'width=600,height=600\'); return false;" href="https://tawk.to/chat/5f9af4237f0a8e57c2d8421e/default">Get Support</a>';
                $links = array_merge($custom_links, $links);
            }
            return $links;
        }

    }

}
