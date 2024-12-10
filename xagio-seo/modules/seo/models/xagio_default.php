<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_DEFAULT')) {

    class XAGIO_MODEL_DEFAULT
    {

        public static function initialize()
        {
            add_action('admin_init', ['XAGIO_MODEL_DEFAULT', 'loadDefaults']);
        }

        public static function loadDefaults()
        {
            if (!get_option('XAGIO_DEFAULTS')) {
                update_option('XAGIO_DEFAULTS', TRUE);
                if (is_plugin_active('magic-page/magic-page.php')) {
                    self::generateSEOTemplatesMagicPage();
                } else {
                    self::generateSEOTemplates();
                }
            }

            if (is_plugin_active('magic-page/magic-page.php') && get_option('_magicpage_api_key') !== FALSE) {
                if (!get_option('XAGIO_DEFAULTS_MAGICPAGE_' . XAGIO_CURRENT_VERSION)) {
                    update_option('XAGIO_DEFAULTS_MAGICPAGE_' . XAGIO_CURRENT_VERSION, TRUE);
                    self::generateSEOTemplatesMagicPage();
                }
            }

            // Default Backup Date
            if (!get_option('XAGIO_BACKUP_DATE')) {
                update_option('XAGIO_BACKUP_DATE', 'never');
            }
        }

        public static function generateSEOTemplatesMagicPage()
        {
            // POST TYPES

            $post_templates = [];
            $post_types     = ['post', 'page'];
            foreach (get_post_types(['_builtin' => FALSE, 'public' => TRUE], 'names') as $k => $p) {
                $post_types[] = $p;
            }

            foreach ($post_types as $p) {
                $pa = [
                    'title'       => ($p === 'post' || $p === 'page' || $p === 'magicpage') ? '' : '%%title%% %%sep%% %%sitename%%',
                    'description' => '',
                    'nofollow'    => !($p === 'post' || $p === 'page'),

                    'facebook_title'       => '',
                    'facebook_description' => '',
                    'facebook_image'       => '',

                    'twitter_title'       => '',
                    'twitter_description' => '',
                    'twitter_image'       => '',
                ];

                $post_templates[$p] = $pa;
            }

            update_option('XAGIO_SEO_DEFAULT_POST_TYPES', $post_templates);

            // TAXONOMIES
            $taxonomy_templates = [];
            $taxonomies         = get_taxonomies();

            foreach ($taxonomies as $p) {
                $pa                     = [
                    'title'       => ($p === 'category' || $p === 'post_tag' || $p === '_magic_page_temp') ? '' : '%%term_title%% %%sep%% %%sitename%%',
                    'description' => '',
                    'nofollow'    => !($p === 'category' || $p === 'post_tag' || $p === '_magic_page_temp'),
                ];
                $taxonomy_templates[$p] = $pa;
            }

            update_option('XAGIO_SEO_DEFAULT_TAXONOMIES', $taxonomy_templates);


            // MISCELLANEOUS
            $miscellaneous_templates                 = [];
            $miscellaneous_templates['search']       = [
                'title'       => '',
                'description' => '',
                'nofollow'    => FALSE,
            ];
            $miscellaneous_templates['author']       = [
                'title'       => '',
                'description' => '',
                'nofollow'    => FALSE,
            ];
            $miscellaneous_templates['archive']      = [
                'title'       => '%%pretty_date%% %%sep%% %%sitename%%',
                'description' => '',
                'nofollow'    => TRUE,
            ];
            $miscellaneous_templates['archive_post'] = [
                'title'       => '%%pretty_date%% %%sep%% %%sitename%%',
                'description' => '',
                'nofollow'    => TRUE,
            ];
            $miscellaneous_templates['not_found']    = [
                'title'       => 'Page not Found %%sep%% %%sitename%%',
                'description' => '',
                'nofollow'    => TRUE,
            ];
            update_option('XAGIO_SEO_DEFAULT_MISCELLANEOUS', $miscellaneous_templates);
        }

        public static function generateSEOTemplates()
        {
            // POST TYPES

            $post_templates = [];
            $post_types     = ['post', 'page'];
            foreach (get_post_types(['_builtin' => FALSE, 'public' => TRUE], 'names') as $k => $p) {
                $post_types[] = $p;
            }

            foreach ($post_types as $p) {
                $pa                 = [
                    'title'       => ($p === 'post' || $p === 'page') ? '' : '%%title%% %%sep%% %%sitename%%',
                    'description' => '',
                    'nofollow'    => 0,

                    'facebook_title'       => '',
                    'facebook_description' => '',
                    'facebook_image'       => '',

                    'twitter_title'       => '',
                    'twitter_description' => '',
                    'twitter_image'       => '',
                ];
                $post_templates[$p] = $pa;
            }

            update_option('XAGIO_SEO_DEFAULT_POST_TYPES', $post_templates);

            // TAXONOMIES
            $taxonomy_templates = [];
            $taxonomies         = get_taxonomies();

            foreach ($taxonomies as $p) {
                $pa                     = [
                    'title'       => ($p === 'category' || $p === 'post_tag' || $p === '_magic_page_temp') ? '' : '%%term_title%% %%sep%% %%sitename%%',
                    'description' => '',
                    'nofollow'    => !($p === 'category' || $p === 'post_tag' || $p === '_magic_page_temp'),
                ];
                $taxonomy_templates[$p] = $pa;
            }

            update_option('XAGIO_SEO_DEFAULT_TAXONOMIES', $taxonomy_templates);


            // MISCELLANEOUS
            $miscellaneous_templates                 = [];
            $miscellaneous_templates['search']       = [
                'title'       => '',
                'description' => '',
                'nofollow'    => FALSE,
            ];
            $miscellaneous_templates['author']       = [
                'title'       => '',
                'description' => '',
                'nofollow'    => FALSE,
            ];
            $miscellaneous_templates['archive']      = [
                'title'       => '%%pretty_date%% %%sep%% %%sitename%%',
                'description' => '',
                'nofollow'    => TRUE,
            ];
            $miscellaneous_templates['archive_post'] = [
                'title'       => '%%pretty_date%% %%sep%% %%sitename%%',
                'description' => '',
                'nofollow'    => TRUE,
            ];
            $miscellaneous_templates['not_found']    = [
                'title'       => 'Page not Found %%sep%% %%sitename%%',
                'description' => '',
                'nofollow'    => TRUE,
            ];
            update_option('XAGIO_SEO_DEFAULT_MISCELLANEOUS', $miscellaneous_templates);
        }

    }

}