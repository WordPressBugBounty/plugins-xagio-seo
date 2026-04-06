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
$xagio_version = get_file_data(XAGIO_PATH . '/' . XAGIO_SLUG_NAME . '.php', ['Version' => 'Version']);
define('XAGIO_CURRENT_VERSION', $xagio_version['Version']);

/**
 * Define the domain name, removing 'www.'
 */
define('XAGIO_DOMAIN', preg_replace('/^www\./', '', wp_parse_url(get_site_url(), PHP_URL_HOST)));

/**
 * Include helpers
 */

if (file_exists(XAGIO_PATH . '/helpers.php')) {
    require_once XAGIO_PATH . '/helpers.php';
} else {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>Xagio Notice:</strong> Some core system files appear to be missing or corrupted. Please reinstall Xagio to restore full functionality.</p>';
        echo '</div>';
    });

    return;
}

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
$xagio_files = glob(XAGIO_PATH . '/inc/xagio_*.php');
foreach ($xagio_files as $xagio_file) {
    require_once $xagio_file;
    $xagio_class = strtoupper(str_replace('.php', '', basename($xagio_file)));
    if (class_exists($xagio_class) && method_exists($xagio_class, 'initialize')) {
        call_user_func([
            $xagio_class,
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
