<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_DEFAULT')) {

    class XAGIO_MODEL_DEFAULT
    {

        public static function initialize()
        {
            add_action('admin_init', ['XAGIO_MODEL_DEFAULT', 'loadDefaults']);

            add_action('admin_init', function () {
                if (isset($_GET['page']) && strpos($_GET['page'], 'xagio') !== false) {
                    remove_all_actions('admin_notices');
                }
            });
        }

        public static function loadDefaults()
        {
	        if (get_transient('xagio_deactivating')) {
		        return;  // Skip during deactivation
	        }

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

	        if (!get_option('XAGIO_BACKUP_SPEED')) {

		        // Update option with blank data, just in case if the process below fails
		        update_option('XAGIO_BACKUP_SPEED', [
			        'time_taken' => 0,
			        'grade'      => 0
		        ]);

		        update_option('XAGIO_BACKUP_SPEED', xagio_backup_speed());
		        update_option("XAGIO_BACKUP_SIZE", xagio_calculate_backup_size());
	        }

	        if (get_option('XAGIO_ENABLE_SITEMAPS') === false) {
		        update_option('XAGIO_ENABLE_SITEMAPS', 1);

		        $db_values = get_option("XAGIO_SITEMAP_CONTENT_SETTINGS");

		        $db_values['post_types']['post']['enabled']     = "1";
		        $db_values['post_types']['page']['enabled']     = "1";
		        $db_values['taxonomies']['category']['enabled'] = "1";
		        $db_values['taxonomies']['post_tag']['enabled'] = "1";

		        update_option('XAGIO_SITEMAP_CONTENT_SETTINGS', $db_values);

		        XAGIO_MODEL_SITEMAPS::createSitemap();
	        }

	        if (get_option('XAGIO_SEO_FORCE_ENABLE') === false) {
		        update_option('XAGIO_SEO_FORCE_ENABLE', 1);
	        }

	        if (get_option('XAGIO_ENABLE_404_REF_URL') === false) {
		        update_option('XAGIO_ENABLE_404_REF_URL', 1);
	        }

	        if (get_option('XAGIO_GLOBAL_404_REDIRECTION_URL') === false) {
		        update_option('XAGIO_GLOBAL_404_REDIRECTION_URL', get_site_url());
	        }

	        if (get_option('XAGIO_SEO_PROFILES') === false) {
		        $data = [
			        'contact_details' => [
				        'business_name' => '',
				        'business_address' => '',
				        'business_city' => '',
				        'business_state' => '',
				        'business_country' => '',
				        'business_zip' => '',
				        'business_phone' => '',
				        'business_alternate_phone' => '',
				        'business_email' => '',
			        ],
			        'business_directories' => [
				        'google_business_profile' => '',
				        'yelp' => '',
				        'bing_places' => '',
				        'bbb_org' => '',
				        'angi' => '',
				        'yellow_pages' => '',
				        'foursquare' => '',
			        ],
			        'map_services' => [
				        'apple_maps_connect' => '',
			        ],
			        'professional_networks' => [
				        'indeed' => '',
				        'angel_list' => '',
				        'meetup' => '',
			        ],
			        'industry_specific' => [
				        'healthgrades' => '',
				        'zocdoc' => '',
				        'houzz' => '',
				        'thumbtack' => '',
				        'the_knot' => '',
				        'wedding_wire' => '',
				        'lawyers_com' => '',
				        'avvo' => '',
				        'clutch' => '',
			        ],
			        'social_media' => [
				        'facebook' => '',
				        'youtube' => '',
				        'instagram' => '',
				        'linkedin' => '',
				        'x' => '',
				        'tiktok' => '',
				        'pinterest' => '',
			        ],
			        'review_sites' => [
				        'trustpilot' => '',
				        'glassdoor' => '',
				        'consumer_affairs' => '',
				        'sitejabber' => '',
			        ],
			        'local_listing' => [
				        'trip_advisor' => '',
			        ],
			        'e_commerce' => [
				        'amazon_business' => '',
				        'etsy' => '',
				        'shopify' => '',
				        'walmart_marketplace' => '',
				        'big_commerce' => '',
			        ],
			        'mobile_app' => [
				        'apple_app_store' => '',
				        'google_play_store' => '',
				        'amazon_appstore' => '',
				        'samsung_galaxy_store' => '',
			        ],
		        ];

		        update_option('XAGIO_SEO_PROFILES', $data);
	        }
        }

        public static function generateSEOTemplatesMagicPage()
        {
            // POST TYPES

            $post_templates = [];
            $xagio_post_types     = ['post', 'page'];
            foreach (get_post_types(['_builtin' => FALSE, 'public' => TRUE], 'names') as $xagio_k => $xagio_p) {
                $xagio_post_types[] = $xagio_p;
            }

            foreach ($xagio_post_types as $xagio_p) {
                $pa = [
                    'title'       => ($xagio_p === 'post' || $xagio_p === 'page' || $xagio_p === 'magicpage') ? '' : '%%title%% %%sep%% %%sitename%%',
                    'description' => '',
                    'nofollow'    => !($xagio_p === 'post' || $xagio_p === 'page'),

                    'facebook_title'       => '',
                    'facebook_description' => '',
                    'facebook_image'       => '',

                    'twitter_title'       => '',
                    'twitter_description' => '',
                    'twitter_image'       => '',
                ];

                $post_templates[$xagio_p] = $pa;
            }

            update_option('XAGIO_SEO_DEFAULT_POST_TYPES', $post_templates);

            // TAXONOMIES
            $taxonomy_templates = [];
            $xagio_taxonomies         = get_taxonomies();

            foreach ($xagio_taxonomies as $xagio_p) {
                $pa                     = [
                    'title'       => ($xagio_p === 'category' || $xagio_p === 'post_tag' || $xagio_p === '_magic_page_temp') ? '' : '%%term_title%% %%sep%% %%sitename%%',
                    'description' => '',
                    'nofollow'    => !($xagio_p === 'category' || $xagio_p === 'post_tag' || $xagio_p === '_magic_page_temp'),
                ];
                $taxonomy_templates[$xagio_p] = $pa;
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
            $xagio_post_types     = ['post', 'page'];
            foreach (get_post_types(['_builtin' => FALSE, 'public' => TRUE], 'names') as $xagio_k => $xagio_p) {
                $xagio_post_types[] = $xagio_p;
            }

            foreach ($xagio_post_types as $xagio_p) {
                $pa                 = [
                    'title'       => ($xagio_p === 'post' || $xagio_p === 'page') ? '' : '%%title%% %%sep%% %%sitename%%',
                    'description' => '',
                    'nofollow'    => 0,

                    'facebook_title'       => '',
                    'facebook_description' => '',
                    'facebook_image'       => '',

                    'twitter_title'       => '',
                    'twitter_description' => '',
                    'twitter_image'       => '',
                ];
                $post_templates[$xagio_p] = $pa;
            }

            update_option('XAGIO_SEO_DEFAULT_POST_TYPES', $post_templates);

            // TAXONOMIES
            $taxonomy_templates = [];
            $xagio_taxonomies         = get_taxonomies();

            foreach ($xagio_taxonomies as $xagio_p) {
                $pa                     = [
                    'title'       => ($xagio_p === 'category' || $xagio_p === 'post_tag' || $xagio_p === '_magic_page_temp') ? '' : '%%term_title%% %%sep%% %%sitename%%',
                    'description' => '',
                    'nofollow'    => !($xagio_p === 'category' || $xagio_p === 'post_tag' || $xagio_p === '_magic_page_temp'),
                ];
                $taxonomy_templates[$xagio_p] = $pa;
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