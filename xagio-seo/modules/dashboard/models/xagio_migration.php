<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_MIGRATION')) {

    class XAGIO_MODEL_MIGRATION
    {

        public static function initialize()
        {
            if (!XAGIO_HAS_ADMIN_PERMISSIONS) return;

            add_action('admin_post_xagio_migrate_rankmath', ['XAGIO_MODEL_MIGRATION', 'migration_RANKMATH']);
            add_action('admin_post_xagio_migrate_yoast', ['XAGIO_MODEL_MIGRATION', 'migration_YOAST']);
            add_action('admin_post_xagio_migrate_aio', ['XAGIO_MODEL_MIGRATION', 'migration_AIO']);

            if (get_transient('XAGIO_MIGRATE_SEO_NOTICE')) {
                add_action('admin_notices', ['XAGIO_MODEL_MIGRATION', 'migration_notice']);
            }

            add_action('XAGIO_AUTO_MIGRATION', ['XAGIO_MODEL_MIGRATION', 'autoMigration']);
        }

        public static function migration_notice() {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo 'Xagio has automatically migrated data from other SEO plugins and created a FREE Audit of your website, you can see it in Xagio > Projects, or simply click <a href="/wp-admin/admin.php?page=xagio-projectplanner">here</a>.'; ?></p>
            </div>
            <?php
            update_option('XAGIO_MIGRATE_SEO_NOTICE', true);
        }

        public static function autoMigration ()
        {
            // detect rankmath/yoast/aio and migrate if found
            if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
                self::migration_RANKMATH();
            }
            if (is_plugin_active('wordpress-seo/wp-seo.php') || is_plugin_active('wordpress-seo-premium/wp-seo-premium.php')) {
                self::migration_YOAST();
            }
            if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') || is_plugin_active('all-in-one-seo-pack-pro/all_in_one_seo_pack.php')) {
                self::migration_AIO();
            }

            if (!get_option('XAGIO_MIGRATE_SEO')) {
                // Run a Free audit, but only first time
                XAGIO_MODEL_KEYWORDS::auditWebsite('migration');

                set_transient('XAGIO_MIGRATE_SEO_NOTICE', true, 5 * MINUTE_IN_SECONDS);
                update_option('XAGIO_MIGRATE_SEO', true);
            }
        }

        public static function migration_RANKMATH()
        {
            global $wpdb;
            $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '%rank_math_%'", ARRAY_A);
            foreach ($results as $r) {

                $id = $r['post_id'];

                // replace %shortcodes% with {shortcode}
                $r['meta_value'] = preg_replace('/%([^%]+)%/', '{$1}', $r['meta_value']);

                update_post_meta($id, 'XAGIO_SEO', 1);

                if ($r['meta_key'] == 'rank_math_title') {
                    update_post_meta($id, 'XAGIO_SEO_TITLE', $r['meta_value']);
                }
                if ($r['meta_key'] == 'rank_math_description') {
                    update_post_meta($id, 'XAGIO_SEO_DESCRIPTION', $r['meta_value']);
                }
                if ($r['meta_key'] == 'rank_math_focus_keyword') {
                    update_post_meta($id, 'XAGIO_SEO_TARGET_KEYWORD', $r['meta_value']);
                }
                if ($r['meta_key'] == 'rank_math_facebook_title') {
                    update_post_meta($id, 'XAGIO_SEO_FACEBOOK_TITLE', $r['meta_value']);
                    update_post_meta($id, 'XAGIO_SEO_TWITTER_TITLE', $r['meta_value']);
                }
                if ($r['meta_key'] == 'rank_math_facebook_description') {
                    update_post_meta($id, 'XAGIO_SEO_FACEBOOK_DESCRIPTION', $r['meta_value']);
                    update_post_meta($id, 'XAGIO_SEO_TWITTER_DESCRIPTION', $r['meta_value']);
                }
                if ($r['meta_key'] == 'rank_math_facebook_image') {
                    update_post_meta($id, 'XAGIO_SEO_FACEBOOK_IMAGE', $r['meta_value']);
                    update_post_meta($id, 'XAGIO_SEO_TWITTER_IMAGE', $r['meta_value']);
                }
            }

            update_option('XAGIO_MIGRATE_RANKMATH', true);
        }

        public static function migration_YOAST()
        {
            global $wpdb;
            $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '%_yoast_wpseo_%'", ARRAY_A);

            foreach ($results as $r) {

                // Array mapping fields from the first list to the replacements from the second list
                $fieldMappings = [
                    'date'               => 'date',
                    'title'              => 'title',
                    'parent_title'       => 'parent_title',
                    'archive_title'      => '',                // No direct mapping provided
                    'sitename'           => 'sitename',
                    'sitedesc'           => 'tagline',
                    'excerpt'            => 'excerpt',
                    'excerpt_only'       => '',                // No direct mapping provided
                    'tag'                => 'tag',
                    'category'           => 'category',
                    'primary_category'   => 'category_primary',
                    'category_description' => '',              // No direct mapping provided
                    'tag_description'    => '',                // No direct mapping provided
                    'term_description'   => '',                // No direct mapping provided
                    'term_title'         => 'term_title',
                    'searchphrase'       => 'search_query',
                    'sep'                => 'sep',
                    'pt_single'          => '',                // No direct mapping provided
                    'pt_plural'          => '',                // No direct mapping provided
                    'modified'           => '',                // No direct mapping provided
                    'id'                 => '',                // No direct mapping provided
                    'name'               => 'author_name',
                    'user_description'   => '',                // No direct mapping provided
                    'page'               => 'page',
                    'pagetotal'          => '',                // No direct mapping provided
                    'pagenumber'         => '',                // No direct mapping provided
                    'caption'            => '',                // No direct mapping provided
                    'focuskw'            => '',                // No direct mapping provided
                    'term404'            => ''                 // No direct mapping provided
                ];

                foreach ($fieldMappings as $from => $to) {
                    $r['meta_value'] = str_replace('%%' . $from . '%%', '{' . $to . '}', $r['meta_value']);
                }

                if ($r['meta_key'] == '_yoast_wpseo_title') {
                    $id = $r['post_id'];
                    update_post_meta($id, 'XAGIO_SEO_TITLE', $r['meta_value']);
                    // Enable Meta Robots
                    update_post_meta($id, 'ps_meta_robots_enabled', 1);
                }
                if ($r['meta_key'] == '_yoast_wpseo_metadesc') {
                    $id = $r['post_id'];
                    update_post_meta($id, 'XAGIO_SEO_DESCRIPTION', $r['meta_value']);
                }
                if ($r['meta_key'] == '_yoast_wpseo_focuskw') {
                    $id = $r['post_id'];
                    update_post_meta($id, 'XAGIO_SEO_TARGET_KEYWORD', $r['meta_value']);
                }
                if ($r['meta_key'] == '_yoast_wpseo_canonical') {
                    $id = $r['post_id'];
                    update_post_meta($id, 'XAGIO_SEO_CANONICAL_URL', $r['meta_value']);
                }
                if ($r['meta_key'] == '_yoast_wpseo_opengraph-title') {
                    $id = $r['post_id'];
                    update_post_meta($id, 'XAGIO_SEO_FACEBOOK_TITLE', $r['meta_value']);
                }
                if ($r['meta_key'] == '_yoast_wpseo_opengraph-description') {
                    $id = $r['post_id'];
                    update_post_meta($id, 'XAGIO_SEO_FACEBOOK_DESCRIPTION', $r['meta_value']);
                }
                if ($r['meta_key'] == '_yoast_wpseo_opengraph-image') {
                    $id = $r['post_id'];
                    update_post_meta($id, 'XAGIO_SEO_FACEBOOK_IMAGE', $r['meta_value']);
                }
                if ($r['meta_key'] == '_yoast_wpseo_twitter-title') {
                    $id = $r['post_id'];
                    update_post_meta($id, 'XAGIO_SEO_TWITTER_TITLE', $r['meta_value']);
                }
                if ($r['meta_key'] == '_yoast_wpseo_twitter-description') {
                    $id = $r['post_id'];
                    update_post_meta($id, 'XAGIO_SEO_TWITTER_DESCRIPTION', $r['meta_value']);
                }
                if ($r['meta_key'] == '_yoast_wpseo_twitter-image') {
                    $id = $r['post_id'];
                    update_post_meta($id, 'XAGIO_SEO_TWITTER_IMAGE', $r['meta_value']);
                }
            }

            // Do the settings migration
            $yoast_settings = get_option('wpseo_titles');

            // Container Arrays
            $ps_seo_post_types = get_option('XAGIO_SEO_DEFAULT_POST_TYPES');
            $ps_seo_taxonomies = get_option('XAGIO_SEO_DEFAULT_TAXONOMIES');

            $ps_seo_post_types['post'] = [
                'title'       => str_replace('%page%', '', $yoast_settings['title-post']),
                'description' => $yoast_settings['metadesc-post'],
                'nofollow'    => $yoast_settings['noindex-post'],
            ];

            $ps_seo_post_types['page'] = [
                'title'       => str_replace('%page%', '', $yoast_settings['title-page']),
                'description' => $yoast_settings['metadesc-page'],
                'nofollow'    => $yoast_settings['noindex-page'],
            ];

            $ps_seo_taxonomies['category'] = [
                'title'       => str_replace('%page%', '', $yoast_settings['title-tax-category']),
                'description' => $yoast_settings['metadesc-tax-category'],
                'nofollow'    => $yoast_settings['noindex-tax-category'],
            ];

            $ps_seo_taxonomies['post_tag'] = [
                'title'       => str_replace('%page%', '', $yoast_settings['title-tax-post_tag']),
                'description' => $yoast_settings['metadesc-tax-post_tag'],
                'nofollow'    => $yoast_settings['noindex-tax-post_tag'],
            ];

            $ps_seo_taxonomies['post_format'] = [
                'title'       => str_replace('%page%', '', $yoast_settings['title-tax-post_format']),
                'description' => $yoast_settings['metadesc-tax-post_format'],
                'nofollow'    => $yoast_settings['noindex-tax-post_format'],
            ];

            update_option('XAGIO_SEO_DEFAULT_POST_TYPES', $ps_seo_post_types);
            update_option('XAGIO_SEO_DEFAULT_TAXONOMIES', $ps_seo_taxonomies);
            update_option('XAGIO_MIGRATE_YOAST', true);

        }

        public static function migration_AIO()
        {
            global $wpdb;
            $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '%_aioseo%'", ARRAY_A);
            foreach ($results as $r) {

                $fieldMappings = [
                    '#author_first_name' => 'author_name',    // Assuming author_name is correct here
                    '#author_last_name'  => 'author_name',    // Assuming author_name is correct here
                    '#author_name'       => 'author_name',
                    '#current_date'      => 'date',
                    '#current_day'       => 'date',
                    '#current_month'     => 'date',
                    '#current_year'      => 'date',
                    '#custom_field'      => '',               // No direct equivalent found
                    '#post_content'      => 'excerpt',
                    '#post_date'         => 'date',
                    '#post_day'          => 'date',
                    '#post_month'        => 'date',
                    '#post_title'        => 'title',
                    '#post_year'         => 'date',
                    '#permalink'         => 'currurl',
                    '#separator_sa'      => 'sep',
                    '#site_title'        => 'sitename',
                    '#tagline'           => 'tagline',
                    '#tax_name'          => 'term_title'
                ];

                foreach ($fieldMappings as $from => $to) {
                    $r['meta_value'] = str_replace($from, '{' . $to . '}', $r['meta_value']);
                }

                if ($r['meta_key'] == '_aioseo_title' || $r['meta_key'] == '_aioseop_title') {
                    $id = $r['post_id'];
                    update_post_meta($id, 'XAGIO_SEO_TITLE', $r['meta_value']);
                }
                if ($r['meta_key'] == '_aioseo_description' || $r['meta_key'] == '_aioseop_description') {
                    $id = $r['post_id'];
                    update_post_meta($id, 'XAGIO_SEO_DESCRIPTION', $r['meta_value']);
                }
                if ($r['meta_key'] == '_aioseo_custom_link' || $r['meta_key'] == '_aioseop_custom_link') {
                    $id = $r['post_id'];
                    update_post_meta($id, 'XAGIO_SEO_CANONICAL_URL', $r['meta_value']);
                }
            }
            update_option('XAGIO_MIGRATE_AIO', true);
        }

    }

}