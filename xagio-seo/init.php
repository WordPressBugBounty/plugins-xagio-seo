<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Xagio Global Constants
 */
define('XAGIO_PATH', dirname(__FILE__));
define('XAGIO_URL', plugin_dir_url(__FILE__));
define('XAGIO_SLUG', 'xagio-seo/xagio-seo.php');
define('XAGIO_SLUG_NAME', 'xagio-seo');
define('XAGIO_PANEL_URL', 'https://app.xagio.net');
define('XAGIO_LICENSE_EMAIL', get_option('XAGIO_LICENSE_EMAIL') ? sanitize_email(get_option('XAGIO_LICENSE_EMAIL')) : null);
define('XAGIO_LICENSE_KEY', get_option('XAGIO_LICENSE_KEY') ? sanitize_text_field(get_option('XAGIO_LICENSE_KEY')) : null);
define('XAGIO_CONNECTED', !empty(XAGIO_LICENSE_EMAIL) && !empty(XAGIO_LICENSE_KEY));
define('XAGIO_MIGRATION_COMPLETED', filter_var(get_option('XAGIO_MIGRATION_COMPLETED'), FILTER_VALIDATE_BOOLEAN));
define('XAGIO_DEV_MODE', filter_var(get_option('XAGIO_DEV_MODE'), FILTER_VALIDATE_BOOLEAN));
define('XAGIO_HIDDEN', filter_var(get_option('XAGIO_HIDDEN'), FILTER_VALIDATE_BOOLEAN));

/**
 * Extract version info from plugin file
 */
$version = get_file_data(XAGIO_PATH . '/' . XAGIO_SLUG_NAME . '.php', ['Version' => 'Version']);
define('XAGIO_CURRENT_VERSION', $version['Version']);

/**
 * Define the domain name, removing 'www.'
 */
define('XAGIO_DOMAIN', str_replace('www.', '', wp_parse_url(get_site_url(), PHP_URL_HOST)));

/**
 * Include helpers
 */
require_once 'helpers.php';

// Check for Permissions
define('XAGIO_HAS_ADMIN_PERMISSIONS', xagio_current_user_can('manage_options'));

$xagio_global_js = [
    'xagio_main'
];
$xagio_global_css = [
    'xagio_font_outfit',
    'xagio_icons',
    'xagio_main'
];

/**
 * Define IP address
 */
define('XAGIO_IP_ADDRESS', isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR'])) : 'n/a');

/**
 * Fallback for hosts without XAGIO_AUTH_KEY and XAGIO_AUTH_SALT
 */
if (!defined('XAGIO_AUTH_KEY')) {
    define('XAGIO_AUTH_KEY', hash('sha256', XAGIO_PATH));
}
if (!defined('XAGIO_AUTH_SALT')) {
    define('XAGIO_AUTH_SALT', hash('sha256', XAGIO_AUTH_KEY . XAGIO_PATH));
}

/**
 * Load all class dependencies from the 'inc' directory
 */
$files = glob(XAGIO_PATH . '/inc/xagio_*.php');
foreach ($files as $file) {
    require_once $file;
    $class = strtoupper(str_replace('.php', '', basename($file)));
    if (class_exists($class) && method_exists($class, 'initialize')) {
        call_user_func([
            $class,
            'initialize'
        ]);
    }
}

register_activation_hook(XAGIO_SLUG, [
    'XAGIO_CORE',
    'activate'
]);
register_uninstall_hook(XAGIO_SLUG, [
    'XAGIO_CORE',
    'uninstall'
]);

/** Load the plugin core */
add_action('init', [
    'XAGIO_CORE', 'init'
]);
