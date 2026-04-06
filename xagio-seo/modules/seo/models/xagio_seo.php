<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_SEO')) {

    class XAGIO_MODEL_SEO
    {

        private static function defines()
        {
            define('XAGIO_SEO_FORCE_ENABLE', filter_var(get_option('XAGIO_SEO_FORCE_ENABLE'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_DISABLE_WP_CANONICALS', filter_var(get_option('XAGIO_DISABLE_WP_CANONICALS'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_DISABLE_HTML_FOOTPRINT', filter_var(get_option('XAGIO_DISABLE_HTML_FOOTPRINT'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_DISABLE_SCRIPTS_LOGGED_IN', filter_var(get_option('XAGIO_DISABLE_SCRIPTS_LOGGED_IN'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_DONT_INDEX_SUBPAGES', filter_var(get_option('XAGIO_DONT_INDEX_SUBPAGES'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_USE_META_KEYWORD', filter_var(get_option('XAGIO_USE_META_KEYWORD'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_SEO_FORCE_NOODP', filter_var(get_option('XAGIO_SEO_FORCE_NOODP'), FILTER_VALIDATE_BOOLEAN));
        }

        public static function initialize()
        {
            XAGIO_MODEL_SEO::defines();

            // Default Settings
            add_action('xagio_set_default_post_settings', [
                'XAGIO_MODEL_SEO',
                'setDefaultPostSettings'
            ]);
            add_action('xagio_set_default_taxonomy_settings', [
                'XAGIO_MODEL_SEO',
                'setDefaultTaxonomySettings'
            ]);

            // Trash post
            add_action('wp_trash_post', [
                'XAGIO_MODEL_SEO',
                'trashPost'
            ]);

            add_action('admin_post_xagio_save_seo_search', [
                'XAGIO_MODEL_SEO',
                'saveSeoSearch'
            ]);

            add_filter('post_row_actions', [
                'XAGIO_MODEL_SEO',
                'duplicatePostButton'
            ], 10, 2);

            add_filter('page_row_actions', [
                'XAGIO_MODEL_SEO',
                'duplicatePostButton'
            ], 10, 2);

            add_action('admin_action_xagio_duplicate_post', [
                'XAGIO_MODEL_SEO',
                'duplicatePost'
            ]);


            if (XAGIO_HAS_ADMIN_PERMISSIONS) {

                wp_schedule_single_event(time() + 5, 'xagio_set_default_post_settings');
                wp_schedule_single_event(time() + 5, 'xagio_set_default_taxonomy_settings');

                // Save Post
                add_action('save_post', [
                    'XAGIO_MODEL_SEO',
                    'savePost'
                ]);

                add_filter('wp_insert_post_data', [
                    'XAGIO_MODEL_SEO', 'filterPostSlug'
                ], 10, 2);

                add_action('quick_edit_custom_box', [
                    'XAGIO_MODEL_SEO',
                    'extendQuickEdit'
                ], 10, 2);

                // Magic Page URL
                add_action('update_option__magic_page_url', [
                    'XAGIO_MODEL_SEO',
                    'magicPageSaveUrl'
                ], 11, 3);

                add_action('admin_post_xagio_save_posttypes', [
                    'XAGIO_MODEL_SEO',
                    'savePostTypes'
                ]);
                add_action('admin_post_xagio_save_taxonomies', [
                    'XAGIO_MODEL_SEO',
                    'saveTaxonomies'
                ]);
                add_action('admin_post_xagio_save_miscellaneous', [
                    'XAGIO_MODEL_SEO',
                    'saveMiscellaneous'
                ]);
                add_action('admin_post_xagio_change_seo_status', [
                    'XAGIO_MODEL_SEO',
                    'saveSEOStatus'
                ]);
                add_action('admin_post_xagio_keyword_suggestions', [
                    'XAGIO_MODEL_SEO',
                    'getKeywordSuggestions'
                ]);

                // Render blocks
                add_action('admin_post_xagio_render_blocks', [
                    'XAGIO_MODEL_SEO',
                    'renderBlocks'
                ]);

                // Meta Box
                add_action('add_meta_boxes', [
                    'XAGIO_MODEL_SEO',
                    'addMetaBoxes'
                ]);

                // Add Custom Columns for SEO enabled posts
                add_filter('manage_posts_columns', [
                    'XAGIO_MODEL_SEO',
                    'addCustomColumn'
                ]);

                add_action('manage_posts_custom_column', [
                    'XAGIO_MODEL_SEO',
                    'renderCustomColumn'
                ], 11, 2);

                add_filter('manage_pages_columns', [
                    'XAGIO_MODEL_SEO',
                    'addCustomColumn'
                ]);

                add_action('manage_pages_custom_column', [
                    'XAGIO_MODEL_SEO',
                    'renderCustomColumn'
                ], 11, 2);

                add_action('admin_action_xagio_seo_enable', [
                    'XAGIO_MODEL_SEO',
                    'handleBulkAction'
                ]);

                add_action('admin_action_xagio_seo_disable', [
                    'XAGIO_MODEL_SEO',
                    'handleBulkAction'
                ]);
            }

            // Meta Box for Terms
            add_action('init', [
                'XAGIO_MODEL_SEO',
                'getCustomTaxonomies'
            ], 9999);

            // Titles
            add_filter('wp_title', [
                'XAGIO_MODEL_SEO',
                'changeTitle'
            ], 9999999997, 3);

            add_filter('pre_get_document_title', [
                'XAGIO_MODEL_SEO',
                'changeTitle'
            ], 9999999998);

            add_filter('woocommerce_page_title', [
                'XAGIO_MODEL_SEO',
                'changeTitle'
            ], 9999999999, 1);

            // Description
            add_action('wp_head', [
                'XAGIO_MODEL_SEO',
                'changeDescription'
            ], -9999999999);

            // Open Graph
            add_action('wp_head', [
                'XAGIO_MODEL_SEO',
                'changeOpenGraph'
            ]);

            // Meta Robots
            add_action('wp_head', [
                'XAGIO_MODEL_SEO',
                'changeMetaRobots'
            ]);

            // Disable WordPress Canonicals
            if (XAGIO_DISABLE_WP_CANONICALS) {

                // Remove Canonicals
                remove_action('wp_head', 'rel_canonical');

                // remove HTML meta tag
                // <link rel='shortlink' href='http://example.com/?p=25' />
                remove_action('wp_head', 'wp_shortlink_wp_head', 10);

                // remove HTTP header
                // Link: <https://example.com/?p=25>; rel=shortlink
                remove_action('template_redirect', 'wp_shortlink_header', 11);

            }

            // Canonical
            add_action('wp_head', [
                'XAGIO_MODEL_SEO',
                'changeCanonical'
            ]);

            // Webmaster Verification
            // Enqueue scripts for verification
            add_action('wp_enqueue_scripts', [
                'XAGIO_MODEL_SEO',
                'enqueue_verification_scripts'
            ]);
            add_action('wp_head', [
                'XAGIO_MODEL_SEO',
                'webmasterVerification'
            ]);
            add_action('wp_body_open', [
                'XAGIO_MODEL_SEO',
                'webmasterVerificationBody'
            ], -1000);

            // Target Keyword
            add_action('wp_head', [
                'XAGIO_MODEL_SEO',
                'forceMetaKeywords'
            ], -1000);

            // Global Scripts
            add_action('wp_head', [
                'XAGIO_MODEL_SEO',
                'renderCustomHeaderScripts'
            ]);
            add_action('wp_footer', [
                'XAGIO_MODEL_SEO',
                'renderCustomFooterScripts'
            ]);
            add_action('wp_body_open', [
                'XAGIO_MODEL_SEO',
                'renderCustomBodyScripts'
            ], -1);

            add_action('admin_enqueue_scripts', [
                'XAGIO_MODEL_SEO',
                'loadAdminAssets'
            ], 10, 1);

            add_action('admin_enqueue_scripts', [
                'XAGIO_MODEL_SEO',
                'localizeJS'
            ], 10, 1);
            add_action('wp_enqueue_scripts', [
                'XAGIO_MODEL_SEO',
                'localizeJS'
            ], 10, 1);
        }

        public static function duplicatePostButton($actions, $post) {
            if (current_user_can('edit_posts')) {
                $xagio_url = wp_nonce_url(
                    admin_url('admin.php?action=xagio_duplicate_post&post=' . $post->ID),
                    'duplicate_post_' . $post->ID
                );
                $actions['duplicate'] = '<a href="' . esc_url($xagio_url) . '">Duplicate</a>';
            }
            return $actions;
        }

        public static function duplicatePost() {
            if (empty($_GET['post']) || !current_user_can('edit_pages')) {
                wp_die('Access denied.');
            }

            $post_id = absint($_GET['post']);
            check_admin_referer('duplicate_post_' . $post_id);

            $post = get_post($post_id);
            if (isset($post) && $post != null) {
                $new_post = [
                    'comment_status' => $post->comment_status,
                    'post_title' => $post->post_title . ' (Copy)',
                    'post_content' => $post->post_content,
                    'post_status' => 'draft',
                    'post_type' => $post->post_type,
                    'post_author' => get_current_user_id(),
                    'post_parent' => $post->post_parent,
                    'post_excerpt' => $post->post_excerpt,
                    'post_password' => $post->post_password,
                    'menu_order' => $post->menu_order,
                    'ping_status' => $post->ping_status,
                    'to_ping' => $post->to_ping,
                ];
                $new_post_id = wp_insert_post($new_post);

                // get all current post terms ad set them to the new post draft
                $xagio_taxonomies = array_map('sanitize_text_field', get_object_taxonomies($post->post_type));
                if (!empty($xagio_taxonomies) && is_array($xagio_taxonomies)) {
                    foreach ($xagio_taxonomies as $taxonomy) {
                        $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
                        wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
                    }
                }

                // duplicate all post meta
                $post_meta_keys = get_post_custom_keys( $post_id );
                if(!empty($post_meta_keys)){
                    foreach ( $post_meta_keys as $meta_key ) {
                        $meta_values = get_post_custom_values( $meta_key, $post_id );
                        foreach ( $meta_values as $meta_value ) {
                            $meta_value = maybe_unserialize( $meta_value );
                            update_post_meta( $new_post_id, $meta_key, wp_slash( $meta_value ) );
                        }
                    }
                }

                XAGIO_MODEL_OCW::clearElementorCache();

                if ($post->post_type !== 'post') {
                    xagio_redirect(admin_url('edit.php?post_type=' . $post->post_type));
                    exit;
                }

                xagio_redirect(admin_url('edit.php'));
                exit;
            }

            wp_die('Failed to duplicate page.');
        }

        public static function extendQuickEdit($column_name, $post_type)
        {
            wp_nonce_field('xagio_nonce', '_xagio_nonce');
        }

        // Enqueues admin
        public static function loadAdminAssets($xagio_hook)
        {
            if ($xagio_hook == 'edit.php') {

                wp_enqueue_script('xagio_seo');
                wp_enqueue_style('xagio_font_outfit');

            }

            if ($xagio_hook == 'post-new.php' || $xagio_hook == 'post.php') {

                wp_enqueue_script('xagio_tablesorter');
                wp_enqueue_script('xagio_jqcloud');
                wp_enqueue_script('xagio_seo-flesch');
                wp_enqueue_script('xagio_seo');
                wp_enqueue_style('xagio_seo');
                wp_enqueue_style('xagio_font_outfit');

            }

            if ($xagio_hook == 'term.php') {
                wp_enqueue_script('xagio_seo-terms');
                wp_enqueue_style('xagio_seo');
                wp_enqueue_style('xagio_font_outfit');

            }

            if ($xagio_hook == 'xagio_page_xagio-seo') {
                // CodeMirror
                $cm_settings['codeEditor'] = wp_enqueue_code_editor(['type' => 'text/x-php']);
                wp_localize_script('jquery', 'cm_settings', $cm_settings);

                wp_enqueue_script('wp-theme-plugin-editor');
                wp_enqueue_style('wp-codemirror');

                wp_enqueue_style('xagio_font_outfit');
            }
        }

        private static $xagio_blocks = [

            'sitename' => [
                'name' => 'Site Name',
                'desc' => 'The site name as configured in the WordPress settings',
            ],

            'siteurl' => [
                'name' => 'Site URL',
                'desc' => 'The site url as configured in the WordPress settings',
            ],

            'currurl' => [
                'name' => 'Current URL',
                'desc' => 'The current url user is visiting',
            ],

            'tagline' => [
                'name' => 'Tagline',
                'desc' => 'The site tagline / description set in the WordPress settings',
            ],

            'sep' => [
                'name' => 'Separator',
                'desc' => 'The separator defined in your SEO settings'
            ],

            'title' => [
                'name' => 'Title',
                'desc' => 'Title of the post/page being viewed',
            ],

            'parent_title' => [
                'name' => 'Parent Title',
                'desc' => 'Title of the parent page of the current page being viewed',
            ],

            'term_title' => [
                'name' => 'Term Title',
                'desc' => 'Term name of the current taxonomy being viewed',
            ],

            'date' => [
                'name' => 'Date',
                'desc' => 'Date of the post/page being viewed',
            ],

            'pretty_date' => [
                'name' => 'Pretty Date',
                'desc' => 'Date of the post/page in format ex. June 2017'
            ],

            'search_query' => [
                'name' => 'Search Query',
                'desc' => 'Current search query being viewed',
            ],

            'author_name' => [
                'name' => 'Author Name',
                'desc' => 'Author name of the post/page being viewed',
            ],

            'content' => [
                'name' => 'Content',
                'desc' => 'The post/page content being viewed',
            ],

            'excerpt' => [
                'name' => 'Excerpt',
                'desc' => 'The post/page excerpt being viewed',
            ],

            'tag' => [
                'name' => 'Tag',
                'desc' => 'Current tag/tags of the post/page being viewed',
            ],

            'category_primary' => [
                'name' => 'Primary Category',
                'desc' => 'Primary Category of the post/page being viewed',
            ],

            'category' => [
                'name' => 'Categories',
                'desc' => 'Post categories (comma separated) of the post/page being viewed',
            ],

        ];

        public static function filterPostSlug($data, $postarr) {

            if (isset($postarr['XAGIO_SEO_URL'])) {
                $newUrl = sanitize_title(wp_unslash($postarr['XAGIO_SEO_URL']));
                $oriUrl = isset($postarr['XAGIO_SEO_ORIGINAL_URL']) ? sanitize_title(wp_unslash($postarr['XAGIO_SEO_ORIGINAL_URL'])) : '';
                $pstUrl = isset($postarr['post_name']) ? sanitize_title(wp_unslash($postarr['post_name'])) : '';

                if ($newUrl == $oriUrl && $newUrl != $pstUrl) {
                    $newUrl = $pstUrl;
                }

                $data['post_name'] = $newUrl;
            }

            return $data;
        }

        public static function localizeJS()
        {
            foreach ([
                         'xagio_main',
                         'xagio_user',
                         'xagio_admin'
                     ] as $script) {
                wp_localize_script($script, 'xagio_replaces', self::$xagio_blocks);

                // get the post id if we are on a post or page or get post id of front page
                $post_id = get_the_ID() ? get_the_ID() : abs(intval(get_option('page_on_front')));

                wp_localize_script($script, 'xagio_post_id', [
                    'value' => $post_id
                ]);
            }
        }

        public static function saveSEOStatus()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['post_id'], $_POST['status'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // get post_id with sanitized value
            $post_id = abs(intval($_POST['post_id']));
            $status  = abs(intval($_POST['status']));

            update_post_meta($post_id, 'XAGIO_SEO', $status);

        }

        public static function setDefaultPostSettings()
        {

            // POSTS
            $xagio_post_types = get_option('XAGIO_SEO_DEFAULT_POST_TYPES');

            foreach (self::getAllPostObjects() as $post_type) {
                $post_type = (is_array($post_type) ? $post_type['name'] : $post_type);
                if ($post_type !== 'post' && $post_type !== 'page') {
                    if (@array_key_exists($post_type, $xagio_post_types)) {
                        continue;
                    } else {
                        $pa                         = [
                            'title'       => '%%title%% %%sep%% %%sitename%%',
                            'description' => '',
                            'nofollow'    => TRUE,
                        ];
                        $post_templates[$post_type] = $pa;
                    }
                }
            }

            if (!empty($post_templates) && !empty($xagio_post_types)) {
                $xagio_post_data = array_merge($xagio_post_types, $post_templates);
            } else if (!empty($post_templates)) {
                $xagio_post_data = $post_templates;
            }

            if (!empty($xagio_post_data)) {
                update_option('XAGIO_SEO_DEFAULT_POST_TYPES', $xagio_post_data);
            }
        }

        public static function setDefaultTaxonomySettings()
        {

            // TAXONOMIES
            $taxonomy_templates = [];
            $xagio_taxonomies         = get_option('XAGIO_SEO_DEFAULT_TAXONOMIES');

            foreach (self::getAllTaxonomies() as $taxonomy) {
                if (@!array_key_exists($taxonomy, $xagio_taxonomies)) {
                    $pa                            = [
                        'title'       => '%%term_title%% %%sep%% %%sitename%%',
                        'description' => '',
                        'nofollow'    => TRUE,
                    ];
                    $taxonomy_templates[$taxonomy] = $pa;
                }
            }

            if (!empty($taxonomy_templates) && !empty($xagio_taxonomies)) {
                $taxonomy_data = array_merge($xagio_taxonomies, $taxonomy_templates);
            } else if (!empty($taxonomy_templates)) {
                $taxonomy_data = $taxonomy_templates;
            }

            if (!empty($taxonomy_data)) {
                update_option('XAGIO_SEO_DEFAULT_TAXONOMIES', $taxonomy_data);
            }
        }

        public static function getCustomTaxonomies()
        {
            $xagio_taxonomies = get_taxonomies();
            unset($xagio_taxonomies['nav_menu']);
            unset($xagio_taxonomies['link_category']);
            unset($xagio_taxonomies['post_format']);
            //            unset($xagio_taxonomies['location']);

            foreach ($xagio_taxonomies as $taxonomy) {

                add_action($taxonomy . '_edit_form_fields', [
                    'XAGIO_MODEL_SEO',
                    'renderSEO_Terms'
                ], 10, 2);
                add_action('edited_' . $taxonomy, [
                    'XAGIO_MODEL_SEO',
                    'saveExtraTermFields'
                ], 10, 2);
            }

        }

        public static function saveExtraTermFields($term_id = 0, $tt_id = 0)
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            // This needs to work only when meta is present otherwise continue with default function
            if (isset($_POST['meta'])) {

                $term_meta = map_deep(wp_unslash($_POST['meta']), 'sanitize_text_field');
                foreach ($term_meta as $xagio_key => $xagio_value) {
                    if (str_contains($xagio_key, 'XAGIO')) {
                        update_term_meta($term_id, $xagio_key, $xagio_value);
                    }
                }

                $cat_meta = xagio_get_term_meta($term_id);

                if (class_exists('XAGIO_MODEL_GROUPS')) {

                    $wpdb->update('xag_groups', [
                        'url'         => self::extract_url($term_id, TRUE),
                        'title'       => @$cat_meta['XAGIO_SEO_TITLE'],
                        'description' => @$cat_meta['XAGIO_SEO_DESCRIPTION'],
                        'h1'          => isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : 'n/a',
                    ], [
                        'id_taxonomy' => $term_id,
                    ]);

                }

                /** Schema */
                if (class_exists('XAGIO_MODEL_SCHEMA')) {
                    if (isset($_POST['XAGIO_SEO_SCHEMAS'])) {
                        $schemaIDs = explode(',', sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_SCHEMAS'])));
                        if (!empty($schemaIDs)) {
                            $renderedSchemas = XAGIO_MODEL_SCHEMA::getRemoteRenderedSchemas($schemaIDs, $term_id, 'term');

                            if ($renderedSchemas != FALSE) {
                                $cat_meta['XAGIO_SEO_SCHEMA_META'] = @$renderedSchemas['meta'];
                                $cat_meta['XAGIO_SEO_SCHEMA_DATA'] = @$renderedSchemas['data'];
                            }
                        } else {
                            $cat_meta['XAGIO_SEO_SCHEMA_META'] = FALSE;
                            $cat_meta['XAGIO_SEO_SCHEMA_DATA'] = FALSE;
                        }
                    }
                }

            }
        }

        public static function renderCustomHeaderScripts()
        {
            if (XAGIO_DISABLE_SCRIPTS_LOGGED_IN == TRUE) {
                if (is_user_logged_in()) {
                    if (is_super_admin()) {
                        return;
                    }
                }
            }

            $xagio_object = $GLOBALS['wp_query']->get_queried_object();
            if (is_object($xagio_object) && isset($xagio_object->ID)) {
                $disable_page = get_post_meta($xagio_object->ID, 'XAGIO_SEO_DISABLE_PAGE_HEADER_SCRIPTS', TRUE);

                $disable_global = get_post_meta($xagio_object->ID, 'XAGIO_SEO_DISABLE_GLOBAL_HEADER_SCRIPTS', TRUE);

                if (isset($disable_page) && $disable_page != 1) {

                    // If meta does not exist SEO SEARCH is turned on by default
                    if (metadata_exists('post', $xagio_object->ID, 'XAGIO_SEO_SCRIPTS_ENABLE')) {
                        // If metadata exists we are checking if it's empty string(TURNED OFF) or 1(TUNED ON)
                        $XAGIO_SEO_SCRIPTS_ENABLE = get_post_meta($xagio_object->ID, 'XAGIO_SEO_SCRIPTS_ENABLE', TRUE);
                        if ($XAGIO_SEO_SCRIPTS_ENABLE === "") {
                            $scripts = '';
                        } else {
                            $scripts = get_post_meta($xagio_object->ID, 'XAGIO_SEO_SCRIPTS_HEADER', TRUE);
                        }
                    } else {
                        $scripts = get_post_meta($xagio_object->ID, 'XAGIO_SEO_SCRIPTS_HEADER', TRUE);
                    }


                    if ($disable_global != 1) {
                        $scripts = get_option('XAGIO_SEO_GLOBAL_SCRIPTS_HEAD') . "\n" . $scripts;
                    }
                } else if (isset($disable_global) && $disable_global != 1) {
                    $scripts = get_option('XAGIO_SEO_GLOBAL_SCRIPTS_HEAD');
                }

            } else {
                $scripts = get_option('XAGIO_SEO_GLOBAL_SCRIPTS_HEAD');
            }

            if (!empty($scripts)) {
                echo do_shortcode(stripslashes_deep($scripts)) . "\n";
            }

            // Check if there are shared scripts
            $shared_scripts = get_option('XAGIO_SHARED_SCRIPTS');
            if ($shared_scripts !== FALSE && $shared_scripts !== '') {
                echo do_shortcode(stripslashes_deep(base64_decode($shared_scripts))) . "\n";
            }
        }

        public static function renderCustomFooterScripts()
        {
            if (XAGIO_DISABLE_SCRIPTS_LOGGED_IN == TRUE) {
                if (is_user_logged_in()) {
                    if (is_super_admin()) {
                        return;
                    }
                }
            }

            $xagio_object = $GLOBALS['wp_query']->get_queried_object();
            if (is_object($xagio_object) && isset($xagio_object->ID)) {

                $disable_page = get_post_meta($xagio_object->ID, 'XAGIO_SEO_DISABLE_PAGE_FOOTER_SCRIPTS', TRUE);

                $disable_global = get_post_meta($xagio_object->ID, 'XAGIO_SEO_DISABLE_GLOBAL_FOOTER_SCRIPTS', TRUE);

                if (isset($disable_page) && $disable_page != 1) {

                    // If meta does not exist SEO SEARCH is turned on by default
                    if (metadata_exists('post', $xagio_object->ID, 'XAGIO_SEO_SCRIPTS_ENABLE')) {
                        // If metadata exists we are checking if it's empty string(TURNED OFF) or 1(TUNED ON)
                        $XAGIO_SEO_SCRIPTS_ENABLE = get_post_meta($xagio_object->ID, 'XAGIO_SEO_SCRIPTS_ENABLE', TRUE);
                        if ($XAGIO_SEO_SCRIPTS_ENABLE === "") {
                            $scripts = '';
                        } else {
                            $scripts = get_post_meta($xagio_object->ID, 'XAGIO_SEO_SCRIPTS_FOOTER', TRUE);
                        }
                    } else {
                        $scripts = get_post_meta($xagio_object->ID, 'XAGIO_SEO_SCRIPTS_FOOTER', TRUE);
                    }

                    if ($disable_global != 1) {
                        $scripts = get_option('XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER') . "\n" . $scripts;
                    }
                } else if (isset($disable_global) && $disable_global != 1) {
                    $scripts = get_option('XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER');
                }

            } else {
                $scripts = get_option('XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER');
            }

            if (!empty($scripts)) {
                echo do_shortcode(stripslashes_deep($scripts)) . "\n";
            }

            // Check if there are shared scripts
            $shared_scripts = get_option('XAGIO_SHARED_SCRIPTS');
            if ($shared_scripts !== FALSE && $shared_scripts !== '') {
                echo do_shortcode(stripslashes_deep(base64_decode($shared_scripts))) . "\n";
            }
        }

        public static function renderCustomBodyScripts()
        {
            if (XAGIO_DISABLE_SCRIPTS_LOGGED_IN == TRUE) {
                if (is_user_logged_in()) {
                    if (is_super_admin()) {
                        return;
                    }
                }
            }

            $xagio_object = $GLOBALS['wp_query']->get_queried_object();
            if (is_object($xagio_object) && isset($xagio_object->ID)) {

                $disable_page = get_post_meta($xagio_object->ID, 'XAGIO_SEO_DISABLE_PAGE_BODY_SCRIPTS', TRUE);

                $disable_global = get_post_meta($xagio_object->ID, 'XAGIO_SEO_DISABLE_GLOBAL_BODY_SCRIPTS', TRUE);

                if (isset($disable_page) && $disable_page != 1) {

                    // If meta does not exist SEO SEARCH is turned on by default
                    if (metadata_exists('post', $xagio_object->ID, 'XAGIO_SEO_SCRIPTS_ENABLE')) {
                        // If metadata exists we are checking if it's empty string(TURNED OFF) or 1(TUNED ON)
                        $XAGIO_SEO_SCRIPTS_ENABLE = get_post_meta($xagio_object->ID, 'XAGIO_SEO_SCRIPTS_ENABLE', TRUE);
                        if ($XAGIO_SEO_SCRIPTS_ENABLE === "") {
                            $scripts = '';
                        } else {
                            $scripts = get_post_meta($xagio_object->ID, 'XAGIO_SEO_SCRIPTS_BODY', TRUE);
                        }
                    } else {
                        $scripts = get_post_meta($xagio_object->ID, 'XAGIO_SEO_SCRIPTS_BODY', TRUE);
                    }

                    if ($disable_global != 1) {
                        $scripts = get_option('XAGIO_SEO_GLOBAL_SCRIPTS_BODY') . "\n" . $scripts;
                    }
                } else if (isset($disable_global) && $disable_global != 1) {
                    $scripts = get_option('XAGIO_SEO_GLOBAL_SCRIPTS_BODY');
                }

            } else {
                $scripts = get_option('XAGIO_SEO_GLOBAL_SCRIPTS_BODY');
            }

            if (!empty($scripts)) {
                echo do_shortcode(stripslashes_deep($scripts)) . "\n";
            }
        }

        public static function handleBulkAction()
        {
            check_ajax_referer('bulk-posts', '_wpnonce');

            if (!isset($_GET['action'], $_GET['post_type'], $_GET['post'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $action    = sanitize_text_field(wp_unslash($_GET['action']));
            $post_type = sanitize_text_field(wp_unslash($_GET['post_type']));

            $sendback = admin_url("edit.php?post_type=$post_type");

            $allowed_actions = [
                "xagio_seo_enable",
                "xagio_seo_disable"
            ];
            if (!in_array($action, $allowed_actions)) {
                xagio_redirect($sendback);
                exit;
            }

            $post_ids = array_map('sanitize_text_field', wp_unslash($_GET['post']));

            if (empty($post_ids)) {
                xagio_redirect($sendback);
                exit;
            }

            switch ($action) {

                case 'xagio_seo_enable':

                    foreach ($post_ids as $post_id) {
                        update_post_meta($post_id, 'XAGIO_SEO_SEARCH_PREVIEW_ENABLE', 1);
                    }

                    break;
                case 'xagio_seo_disable':

                    foreach ($post_ids as $post_id) {
                        update_post_meta($post_id, 'XAGIO_SEO_SEARCH_PREVIEW_ENABLE', 0);
                    }

                    break;
                default:
                    xagio_redirect($sendback);
                    exit;
            }

            xagio_redirect($sendback);
            exit;
        }

        public static function addCustomColumn($columns)
        {
            if (!get_option('XAGIO_HIDDEN')) {
                return array_merge($columns, [
                    'xagio_seo_column' => '<img title="Indicates the status of Xagio SEO on this post." src="' . XAGIO_URL . 'assets/img/logo-menu-xagio.webp"/> Xagio SEO',
                ]);
            } else {
                return $columns;
            }
        }

        public static function renderCustomColumn($column, $post_id)
        {
            if ($column == 'xagio_seo_column') {
                $xagio_meta                            = XAGIO_MODEL_SEO::formatMetaVariables(get_post_meta($post_id));
                $XAGIO_SEO_SEARCH_PREVIEW_ENABLE = !isset($xagio_meta['XAGIO_SEO_SEARCH_PREVIEW_ENABLE']) ? 1 : $xagio_meta['XAGIO_SEO_SEARCH_PREVIEW_ENABLE'];
                ob_start();
                include XAGIO_PATH . '/modules/seo/metabox/column.php';

                $accepted_tags = array(
                    'div'   => array(
                        'class' => array()
                    ),
                    'input' => array(
                        'type'  => array(),
                        'name'  => array(),
                        'class' => array(),
                        'value' => array()
                    ),
                    'span'  => array(
                        'class'        => array(),
                        'data-element' => array(),
                        'data-page'    => array()
                    )
                );


                echo wp_kses(ob_get_clean(), $accepted_tags);
            }
        }

        public static function forceMetaKeywords()
        {
            if (!$keyword = XAGIO_MODEL_SEO::getMeta('XAGIO_SEO_TARGET_KEYWORD')) {
                return;
            }

            if (XAGIO_DISABLE_HTML_FOOTPRINT == FALSE) {
                echo "\n<!-- xagio – Meta Keywords -->\n";
            }

            echo '<meta name="keywords" content="' . esc_attr($keyword) . '">';

            if (XAGIO_DISABLE_HTML_FOOTPRINT == FALSE) {
                echo "\n<!-- xagio – Meta Keywords -->\n\n";
            }
        }

        public static function savePostTypes()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (isset($_POST['XAGIO_SEO_DEFAULT_POST_TYPES'])) {

                $xagio_post_types = sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_DEFAULT_POST_TYPES']));
                if (is_array($xagio_post_types) && !empty($xagio_post_types)) {
                    update_option('XAGIO_SEO_DEFAULT_POST_TYPES', $xagio_post_types);
                }
                xagio_json('success', 'Your post type settings have been saved.');

            }
            xagio_json('error', 'Sorry, you are not authorized user.');
        }

        public static function saveCustomPostTypesOG()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (isset($_POST['XAGIO_SEO_DEFAULT_CUSTOM_POST_TYPES'])) {

                $xagio_post_types = sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_DEFAULT_CUSTOM_POST_TYPES']));
                if (is_array($xagio_post_types) && !empty($xagio_post_types)) {
                    update_option('XAGIO_SEO_DEFAULT_CUSTOM_POST_TYPES', $xagio_post_types);
                }
                xagio_json('success', 'Your post type Open Graph settings have been saved.');

            }
            xagio_json('error', 'Sorry, you are not authorized user.');
        }

        public static function saveDefaultPostOG()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (isset($_POST['XAGIO_SEO_DEFAULT_POST_OG'])) {

                $xagio_post_types = sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_DEFAULT_POST_OG']));
                if (is_array($xagio_post_types) && !empty($xagio_post_types)) {
                    update_option('XAGIO_SEO_DEFAULT_POST_OG', $xagio_post_types);
                }
                xagio_json('success', 'Your Default Open Graph settings have been saved.');

            }
            xagio_json('error', 'Sorry, you are not authorized user.');
        }

        public static function saveTaxonomies()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (isset($_POST['XAGIO_SEO_DEFAULT_TAXONOMIES'])) {

                $xagio_taxonomies = sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_DEFAULT_TAXONOMIES']));
                if (is_array($xagio_taxonomies) && !empty($xagio_taxonomies)) {
                    update_option('XAGIO_SEO_DEFAULT_TAXONOMIES', $xagio_taxonomies);
                }
                xagio_json('success', 'Your taxonomy settings have been saved.');

            }
            xagio_json('error', 'Sorry, you are not authorized user.');
        }

        public static function saveMiscellaneous()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (isset($_POST['XAGIO_SEO_DEFAULT_MISCELLANEOUS'])) {

                $xagio_miscellaneous = sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_DEFAULT_MISCELLANEOUS']));
                if (is_array($xagio_miscellaneous) && !empty($xagio_miscellaneous)) {
                    update_option('XAGIO_SEO_DEFAULT_MISCELLANEOUS', $xagio_miscellaneous);
                }
                xagio_json('success', 'Your miscellaneous settings have been saved.');

            }
            xagio_json('error', 'Sorry, you are not authorized user.');
        }

        public static function saveSeoSearch()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['post_id'], $_POST['status'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // get post_id with sanitized value
            $post_id = abs(intval($_POST['post_id']));
            $status  = abs(intval($_POST['status']));

            update_post_meta($post_id, 'XAGIO_SEO_SEARCH_PREVIEW_ENABLE', $status);

        }

        public static function webmasterVerificationBody()
        {
            $webmaster_template = [];

            $google_tag = get_option('XAGIO_SEO_VERIFICATION_GOOGLE_TAG_BODY');
            if (!empty($google_tag)) {
                wp_register_script('google-tag-manager-body', false, [], XAGIO_CURRENT_VERSION); // We register it without a source
                wp_enqueue_script('google-tag-manager-body', false, [], XAGIO_CURRENT_VERSION); // Enqueue the script

                $xagio_comment_start = '';
                $xagio_comment_end   = '';
                if (defined('XAGIO_DISABLE_HTML_FOOTPRINT') && XAGIO_DISABLE_HTML_FOOTPRINT == false) {
                    $xagio_comment_start = "\n<!-- xagio – Webmaster Verification -->\n";
                }
                if (defined('XAGIO_DISABLE_HTML_FOOTPRINT') && XAGIO_DISABLE_HTML_FOOTPRINT == false) {
                    $xagio_comment_end = "\n<!-- xagio – Webmaster Verification -->\n\n";
                }

                if (strlen($google_tag) > 15) {

                    $allowed_tags = [
                        'script' => [
                            'type' => true,
                            'src'  => true
                        ]
                    ];

                    wp_add_inline_script('google-tag-manager-body', $xagio_comment_start . wp_kses($google_tag, $allowed_tags) . $xagio_comment_end);
                } else {
                    $noscript = $xagio_comment_start . '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr($google_tag) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . $xagio_comment_end;
                    echo wp_kses(stripslashes_deep($noscript), [
                        'noscript' => [],
                        'iframe'   => [
                            'src'    => [],
                            'height' => [],
                            'width'  => [],
                            'style'  => [],
                        ]
                    ]);
                }
            }

        }

        public static function enqueue_verification_scripts()
        {
            $google_analytics   = get_option('XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS');
            $google_analytics_4 = get_option('XAGIO_SEO_VERIFICATION_GOOGLE_ANALYTICS_4');
            $google_tag         = get_option('XAGIO_SEO_VERIFICATION_GOOGLE_TAG_HEAD');

            // Enqueue Google Tag Manager script
            if (!empty($google_tag)) {
                wp_register_script('google-tag-manager', false, [], XAGIO_CURRENT_VERSION); // We register it without a source
                wp_enqueue_script('google-tag-manager', false, [], XAGIO_CURRENT_VERSION); // Enqueue the script

                // Add the inline script depending on the condition
                if (strlen($google_tag) > 15) {

                    $allowed_tags = [
                        'script' => [
                            'type' => true,
                            'src'  => true
                        ]
                    ];

                    wp_add_inline_script('google-tag-manager', wp_kses($google_tag, $allowed_tags));
                } else {
                    $inline_script = "
                (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                })(window,document,'script','dataLayer','" . esc_attr($google_tag) . "');
            ";
                    wp_add_inline_script('google-tag-manager', $inline_script);
                }
            }

            // Enqueue Google Analytics script
            if (!empty($google_analytics)) {
                wp_enqueue_script('google-analytics', 'https://www.googletagmanager.com/gtag/js?id=' . esc_attr($google_analytics), [], null, true);
                wp_add_inline_script(
                    'google-analytics', "
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '" . esc_attr($google_analytics) . "');
        "
                );
            }

            // Enqueue Google Analytics 4 script
            if (!empty($google_analytics_4)) {
                wp_enqueue_script('google-analytics-4', 'https://www.googletagmanager.com/gtag/js?id=' . esc_attr($google_analytics_4), [], null, true);
                wp_add_inline_script(
                    'google-analytics-4', "
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '" . esc_attr($google_analytics_4) . "');
        "
                );
            }
        }

        public static function webmasterVerification()
        {
            $webmaster_template = [];

            $bing      = get_option('XAGIO_SEO_VERIFICATION_BING');
            $google    = get_option('XAGIO_SEO_VERIFICATION_GOOGLE');
            $pinterest = get_option('XAGIO_SEO_VERIFICATION_PINTEREST');
            $yandex    = get_option('XAGIO_SEO_VERIFICATION_YANDEX');

            if (!empty($bing)) {
                $webmaster_template[] = '<meta name="msvalidate.01" content="' . esc_attr($bing) . '" />';
            }
            if (!empty($google)) {
                $webmaster_template[] = '<meta name="google-site-verification" content="' . esc_attr($google) . '"/>';
            }
            if (!empty($pinterest)) {
                $webmaster_template[] = '<meta name="p:domain_verify" content="' . esc_attr($pinterest) . '"/>';
            }
            if (!empty($yandex)) {
                $webmaster_template[] = '<meta name="yandex-verification" content="' . esc_attr($yandex) . '" />';
            }
            if (sizeof($webmaster_template) > 0) {
                if (defined('XAGIO_DISABLE_HTML_FOOTPRINT') && XAGIO_DISABLE_HTML_FOOTPRINT == false) {
                    echo "\n<!-- xagio – Webmaster Verification - Header -->\n";
                }


                echo wp_kses(stripslashes_deep(join("\n", $webmaster_template)), [
                    'meta' => [
                        'content'  => [],
                        'property' => [],
                        'name'     => []
                    ]
                ]);

                if (defined('XAGIO_DISABLE_HTML_FOOTPRINT') && XAGIO_DISABLE_HTML_FOOTPRINT == false) {
                    echo "\n<!-- xagio – Webmaster Verification - Header -->\n\n";
                }
            }
        }

        public static function changeCanonical()
        {
            $xagio_object = $GLOBALS['wp_query']->get_queried_object();

            if (!is_object($xagio_object))
                return FALSE;

            if (!isset($xagio_object->ID))
                return FALSE;

            $canonical = '';
            if (XAGIO_DISABLE_WP_CANONICALS) {

                if (!isset($_SERVER['REQUEST_URI'])) {
                    wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
                }

                $canonical = get_site_url() . sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
            }

            /**
             *  Try to get a custom canonical if set
             */
            $canonical = XAGIO_MODEL_SEO::getMeta('XAGIO_SEO_CANONICAL_URL');
            $canonical = self::generatePermalinkFromURL($canonical);

            // Turn off robots per page
            // If meta does not exist SEO_META_ROBOTS_ENABLE is turned on by default
            if (metadata_exists('post', $xagio_object->ID, 'XAGIO_SEO_META_ROBOTS_ENABLE')) {
                // If metadata exists we are checking if it's empty string(TURNED OFF) or 1(TUNED ON)
                $XAGIO_SEO_META_ROBOTS_ENABLE = get_post_meta($xagio_object->ID, 'XAGIO_SEO_META_ROBOTS_ENABLE', TRUE);
                if ($XAGIO_SEO_META_ROBOTS_ENABLE === "") {
                    $canonical = '';
                }
            }

            if (!empty($canonical)) {
                if (!XAGIO_DISABLE_HTML_FOOTPRINT) {
                    echo "\n<!-- xagio – Canonical URL -->\n";
                }

                echo wp_kses('<link rel="canonical" href="' . esc_attr($canonical) . '" />', [
                    'link' => [
                        'rel'  => ['canonical'],
                        'href' => []
                    ]
                ]);

                if (!XAGIO_DISABLE_HTML_FOOTPRINT) {
                    echo "\n<!-- xagio – Canonical URL -->\n";
                }
            }

            return TRUE;

        }

        private static function generatePermalinkFromURL($xagio_url)
        {
            // Parse the URL and extract the query string
            $parsedUrl   = wp_parse_url($xagio_url);
            $queryString = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';

            // Parse the query string into variables
            parse_str($queryString, $queryArray);

            // Check if 'page_id' is present in the URL
            if (isset($queryArray['page_id']) || isset($queryArray['p'])) {

                $pageId = null;

                if (isset($queryArray['page_id'])) {
                    $pageId = $queryArray['page_id'];
                }

                if (isset($queryArray['p'])) {
                    $pageId = $queryArray['p'];
                }

                // Generate a permalink using the page ID
                $permalink = get_permalink($pageId);

                if ($permalink) {
                    return $permalink;
                } else {
                    return false;
                }
            } else {
                return $xagio_url;
            }
        }

        public static function changeMetaRobots()
        {
            $xagio_object = $GLOBALS['wp_query']->get_queried_object();
            $xagio_robots = '';

            if (is_feed()) {
                return FALSE;
            }

            if (is_object($xagio_object)) {
                if (is_singular()) {
                    $xagio_robots = XAGIO_MODEL_SEO::getRobots($xagio_object);

                } else if (is_category() || is_tag() || is_tax()) {
                    $xagio_robots = XAGIO_MODEL_SEO::getRobotsTaxonomy($xagio_object);

                }
            } else {
                if (is_search()) {
                    $xagio_robots = XAGIO_MODEL_SEO::getRobotsMisc();

                } else if (is_author()) {
                    $xagio_robots = XAGIO_MODEL_SEO::getRobotsMisc();

                } else if (is_post_type_archive()) {
                    $xagio_robots = XAGIO_MODEL_SEO::getRobotsMisc();

                } else if (is_archive()) {
                    $xagio_robots = XAGIO_MODEL_SEO::getRobotsMisc();

                } else if (is_404()) {
                    $xagio_robots = XAGIO_MODEL_SEO::getRobotsMisc();

                }
            }


            if (get_option('XAGIO_SEO_FORCE_NOODP') == "1") {
                if (empty($xagio_robots)) {
                    $xagio_robots = 'noodp';
                } else {
                    $xagio_robots   = explode(',', $xagio_robots);
                    $xagio_robots[] = 'noodp';
                    $xagio_robots   = join(',', $xagio_robots);
                }

            }

            if (get_option('XAGIO_DONT_INDEX_SUBPAGES') == "1") {
                if (XAGIO_MODEL_SEO::is_sub_page()) {
                    if (empty($xagio_robots)) {
                        $xagio_robots = 'noindex';
                    } else {
                        $xagio_robots   = explode(',', $xagio_robots);
                        $xagio_robots   = array_diff($xagio_robots, [
                            "index",
                            "noindex"
                        ]);
                        $xagio_robots[] = 'noindex';
                        $xagio_robots   = join(',', $xagio_robots);
                    }
                }

            }

            if (!empty($xagio_robots)) {
                if (!XAGIO_DISABLE_HTML_FOOTPRINT) {
                    echo "\n<!-- xagio – Meta Robots -->\n";
                }

                echo "<meta name='robots' content='" . esc_attr($xagio_robots) . "'/>";

                if (!XAGIO_DISABLE_HTML_FOOTPRINT) {
                    echo "\n<!-- xagio – Meta Robots -->\n";
                }
            }

            return TRUE;
        }

        public static function getRobots($xagio_object, $xagio_output = false)
        {
            if (!is_object($xagio_object) || !isset($xagio_object->ID)) {
                return false;
            }

            $xagio_robots        = [];
            $global_robots = false;
            $post_type     = 'homepage';

            // Load all variables
            $xagio_meta = XAGIO_MODEL_SEO::formatMetaVariables(get_post_meta($xagio_object->ID));

            // Check if Meta Robots is Enabled in page
            if (!empty($xagio_meta['XAGIO_SEO_META_ROBOTS_ENABLE'])) {

                // First check the global settings and apply changes
                $post_type     = $xagio_object->post_type ?? $xagio_object->query_var;
                $xagio_page_id       = $xagio_object->ID;
                $front_page_id = get_option('page_on_front');
                $front_page_id = intval($front_page_id);

                if (is_front_page())
                    $post_type = 'homepage';
                if ($front_page_id === $xagio_page_id)
                    $post_type = 'homepage';


                $xagio_post_types = get_option('XAGIO_SEO_DEFAULT_POST_TYPES');

                if (!empty($xagio_post_types[$post_type]['XAGIO_SEO_ROBOTS'])) {
                    $xagio_robots[]      = 'noindex';
                    $xagio_robots[]      = 'follow';
                    $global_robots = true;
                }

                // Check advanced robots from the page and apply them to global
                $advanced_robots = maybe_unserialize(!empty($xagio_meta['XAGIO_SEO_META_ROBOTS_ADVANCED']) ? $xagio_meta['XAGIO_SEO_META_ROBOTS_ADVANCED'] : []);

                foreach ($advanced_robots as $a_robot) {
                    $xagio_robots[] = $a_robot;
                }

                // Then if page settings for meta robots are not set to default, overwrite the global setting
                if ($xagio_meta['XAGIO_SEO_META_ROBOTS_INDEX'] !== 'default') {
                    if (($xagio_key = array_search('noindex', $xagio_robots, true)) !== false) {
                        unset($xagio_robots[$xagio_key]);
                    }

                    $xagio_robots[] = $xagio_meta['XAGIO_SEO_META_ROBOTS_INDEX'] ?? '';
                }

                if ($xagio_meta['XAGIO_SEO_META_ROBOTS_FOLLOW'] !== 'default') {
                    if (($xagio_key = array_search('follow', $xagio_robots, true)) !== false) {
                        unset($xagio_robots[$xagio_key]);
                    }
                    $xagio_robots[] = $xagio_meta['XAGIO_SEO_META_ROBOTS_FOLLOW'] ?? '';
                }

                $xagio_robots = implode(',', array_filter($xagio_robots));
            }

            if ($xagio_output) {
                return [
                    'robots'    => $xagio_robots,
                    'global'    => $global_robots,
                    'post_type' => $post_type
                ];
            }

            return $xagio_robots;
        }


        public static function getRobotsTaxonomy($xagio_object)
        {
            if (is_object($xagio_object) && isset($xagio_object->taxonomy) && isset($xagio_object->term_id)) {

                $taxonomy   = $xagio_object->taxonomy;
                $xagio_taxonomies = get_option('XAGIO_SEO_DEFAULT_TAXONOMIES');

                $xagio_robots = @$xagio_taxonomies[$taxonomy]['XAGIO_SEO_ROBOTS'];

                $xagio_meta = xagio_get_term_meta($xagio_object->term_id);
                if (@$xagio_meta['XAGIO_SEO_ROBOTS'] == TRUE) {

                    return 'noindex,follow';

                } else if ($xagio_robots == TRUE) {

                    return 'noindex,follow';

                } else {

                    return FALSE;

                }

            } else {
                return FALSE;
            }
        }

        private static function detectSpecialPages()
        {
            if (is_search()) {
                return 'search';
            } else if (is_author()) {
                return 'author';
            } else if (is_post_type_archive()) {
                return 'archive_post';
            } else if (is_archive()) {
                return 'archive';
            } else if (is_404()) {
                return 'not_found';
            } else {
                return FALSE;
            }
        }

        public static function getRobotsMisc()
        {
            $xagio_miscellaneous = get_option('XAGIO_SEO_DEFAULT_MISCELLANEOUS');
            $misc          = self::detectSpecialPages();
            if (isset($xagio_miscellaneous[$misc])) {
                $xagio_robots = @$xagio_miscellaneous[$misc]['XAGIO_SEO_ROBOTS'];
                if ($xagio_robots == TRUE) {
                    return 'noindex,follow';
                } else {
                    return FALSE;
                }
            } else {
                return FALSE;
            }
        }

        public static function changeTitle($title, $separator = '', $separator_location = '')
        {
            // Ignore Feeds
            if (is_feed()) {
                return $title;
            }

            // Original Title
            $original_title = $title;

            $title = XAGIO_MODEL_SEO::getMeta('XAGIO_SEO_TITLE');

            if (!is_string($title) || '' === $title) {
                $title = $original_title;
            }

            return do_shortcode(xagio_spintax($title));
        }

        public static function changeDescription()
        {
            global $wp_query;

            if (is_feed()) {
                return FALSE;
            }

            $xagio_description = XAGIO_MODEL_SEO::getMeta('XAGIO_SEO_DESCRIPTION');

            if (!empty($xagio_description)) {

                // Perform spintax
                $xagio_description = do_shortcode(xagio_spintax($xagio_description));

                if (!XAGIO_DISABLE_HTML_FOOTPRINT) {
                    echo "\n<!-- xagio – Meta Description -->\n";
                }

                echo '<meta name="description" content="' . esc_attr($xagio_description) . '">';

                if (!XAGIO_DISABLE_HTML_FOOTPRINT) {
                    echo "\n<!-- xagio – Meta Description -->\n";
                }
            }

            return TRUE;
        }

        public static function changeOpenGraph()
        {
            if (is_feed()) {
                return FALSE;
            }

            $xagio_og = XAGIO_MODEL_SEO::getOG();

            if (!empty($xagio_og)) {
                if (!XAGIO_DISABLE_HTML_FOOTPRINT) {
                    echo "\n<!-- xagio – Open Graph -->\n";
                }

                echo wp_kses($xagio_og, [
                    'meta' => [
                        'content'  => [],
                        'property' => [],
                        'name'     => []
                    ]
                ]);

                if (!XAGIO_DISABLE_HTML_FOOTPRINT) {
                    echo "\n<!-- xagio – Open Graph -->\n";
                }
            }

            return TRUE;
        }

        public static function getMeta($xagio_key = '')
        {
            $xagio_object   = $GLOBALS['wp_query']->get_queried_object();
            $xagio_meta     = null;
            $defaults = null;
            $type     = null;

            // If MagicPage
            if (isset($GLOBALS['wp_query']->query_vars['magic_page_term']) || isset($GLOBALS['wp_query']->query['magicpage'])) {

                $term = NULL;
                if (isset($GLOBALS['wp_query']->query_vars['magic_page_term'])) {
                    $term = $GLOBALS['wp_query']->query_vars['magic_page_term'];
                } else if (isset($GLOBALS['wp_query']->query['magicpage'])) {
                    $term = get_term_by('name', $GLOBALS['wp_query']->query['magicpage'], 'location');
                }

                $type     = $term->taxonomy;
                $xagio_meta     = xagio_get_term_meta($term->term_id);
                $defaults = get_option('XAGIO_SEO_DEFAULT_TAXONOMIES');

                if (empty($xagio_meta) || (empty($xagio_meta[$xagio_key]))) {
                    $type     = 'magicpage';
                    $defaults = get_option('XAGIO_SEO_DEFAULT_POST_TYPES');
                    $xagio_meta     = XAGIO_MODEL_SEO::formatMetaVariables(get_post_meta($xagio_object->ID));
                }

            } // If Post
            else if ($xagio_object instanceof WP_Post) {

                $defaults = get_option('XAGIO_SEO_DEFAULT_POST_TYPES');
                $xagio_meta     = XAGIO_MODEL_SEO::formatMetaVariables(get_post_meta($xagio_object->ID));

                if (is_front_page() || is_home() || $xagio_object->ID == get_option('page_on_front')) {
                    switch ($xagio_key) {
                        case "XAGIO_SEO_DESCRIPTION":
                        case "XAGIO_SEO_TITLE":
                            if (empty($xagio_meta[$xagio_key])) {
                                $type = 'homepage';
                                $xagio_meta = isset($defaults[$type]) ? $defaults[$type] : [];
                            }
                            break;
                        default:
                            $type = 'homepage';
                            $xagio_meta = isset($defaults[$type]) ? $defaults[$type] : [];
                            break;
                    }

                } else {
                    $type = $xagio_object->post_type ?? $xagio_object->query_var;
                    $xagio_meta = XAGIO_MODEL_SEO::formatMetaVariables(get_post_meta($xagio_object->ID));
                }

            } // If Term
            else if ($xagio_object instanceof WP_Term) {
                $type     = $xagio_object->taxonomy;
                $xagio_meta     = xagio_get_term_meta($xagio_object->term_id);
                $defaults = get_option('XAGIO_SEO_DEFAULT_TAXONOMIES');
            } // If Misc
            else if ($currentMisc = self::detectSpecialPages()) {
                $defaults = get_option('XAGIO_SEO_DEFAULT_MISCELLANEOUS');
                if (isset($defaults[$currentMisc])) {
                    $xagio_meta = $defaults[$currentMisc];
                }
            }

            if (empty($xagio_meta)) {
                return FALSE;
            }

            // If XAGIO SEO SEARCH is turned OFF
            if (($xagio_key === 'XAGIO_SEO_TITLE' || $xagio_key === 'XAGIO_SEO_DESCRIPTION')) {
                // If meta does not exist SEO SEARCH is turned on by default
                if (metadata_exists('post', @$xagio_object->ID, 'XAGIO_SEO_SEARCH_PREVIEW_ENABLE')) {
                    // If metadata exists we are checking if it's empty string(TURNED OFF) or 1(TUNED ON)
                    $XAGIO_SEO_SEARCH_PREVIEW_ENABLE = get_post_meta(@$xagio_object->ID, 'XAGIO_SEO_SEARCH_PREVIEW_ENABLE', TRUE);
                    if ($XAGIO_SEO_SEARCH_PREVIEW_ENABLE === "")
                        $XAGIO_SEO_SEARCH_PREVIEW_ENABLE = 0;
                    if (abs(intval($XAGIO_SEO_SEARCH_PREVIEW_ENABLE)) === 0 && XAGIO_SEO_FORCE_ENABLE == 0) {
                        return FALSE;
                    }
                }
            }

	        if ( ! empty( $xagio_meta[ $xagio_key ] ) ) {
		        return self::replaceVars( $xagio_meta[ $xagio_key ], $xagio_object->ID ?? 0 );
	        } else {

		        $xagio_template = $defaults[ $type ][ $xagio_key ] ?? '';
		        $xagio_template = self::replaceVars( $xagio_template, $xagio_object->ID ?? 0 );

		        if ( ! empty( $xagio_template ) ) {
			        return $xagio_template;
		        }
	        }
	        return FALSE;

        }

        public static function getOG()
        {
            $xagio_object = $GLOBALS['wp_query']->get_queried_object();
            if (is_object($xagio_object) && isset($xagio_object->ID)) {

                $defaults = get_option('XAGIO_SEO_DEFAULT_OG');
                $xagio_meta     = XAGIO_MODEL_SEO::formatMetaVariables(get_post_meta($xagio_object->ID));

                if (is_front_page() || is_home() || $xagio_object->ID == get_option('page_on_front')) {
                    $type = 'homepage';
                } else {
                    $type = $xagio_object->post_type ?? $xagio_object->query_var;
                }

                if (isset($xagio_meta['XAGIO_SEO']) && !$xagio_meta['XAGIO_SEO']) {
                    return FALSE;
                }

                // If meta does not exist XAGIO_SEO_SOCIAL_ENABLE is turned on by default
                if (metadata_exists('post', $xagio_object->ID, 'XAGIO_SEO_SOCIAL_ENABLE')) {
                    // If metadata exists we are checking if it's empty string(TURNED OFF) or 1(TUNED ON)
                    $XAGIO_SEO_SOCIAL_ENABLE = get_post_meta($xagio_object->ID, 'XAGIO_SEO_SOCIAL_ENABLE', TRUE);
                    if ($XAGIO_SEO_SOCIAL_ENABLE === "") {
                        return FALSE;
                    }
                }

                $xagio_og = '';

                if (!isset($_SERVER['REQUEST_URI'])) {
                    wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
                }

                $xagio_og .= '<meta property="og:locale" content="' . esc_attr(get_locale()) . '"/>' . "\n";
                $xagio_og .= '<meta property="og:type" content="article"/>' . "\n";
                $xagio_og .= '<meta property="og:url" content="' . get_site_url() . sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) . '"/>' . "\n";
                $xagio_og .= '<meta property="og:site_name" content="' . get_bloginfo('name') . '"/>' . "\n";

                // Facebook Vars
	            $fbId    = $xagio_meta['XAGIO_SEO_FACEBOOK_APP_ID'] ?? '';
	            $fbTitle = $xagio_meta['XAGIO_SEO_FACEBOOK_TITLE'] ?? '';
	            $fbDesc  = $xagio_meta['XAGIO_SEO_FACEBOOK_DESCRIPTION'] ?? '';
	            $fbImg   = $xagio_meta['XAGIO_SEO_FACEBOOK_IMAGE'] ?? '';

				// if any of the OG tags are empty, we will use defaults
	            if ( empty( $fbId ) ) {
		            $fbId = $defaults[ $type ]['XAGIO_SEO_FACEBOOK_APP_ID'] ?? '';
	            }
	            if ( empty( $fbTitle ) ) {
		            $fbTitle = $defaults[ $type ]['XAGIO_SEO_FACEBOOK_TITLE'] ?? '';
	            }
	            if ( empty( $fbDesc ) ) {
		            $fbDesc = $defaults[ $type ]['XAGIO_SEO_FACEBOOK_DESCRIPTION'] ?? '';
	            }
	            if ( empty( $fbImg ) ) {
		            $fbImg = $defaults[ $type ]['XAGIO_SEO_FACEBOOK_IMAGE'] ?? '';
	            }

	            /**
                 *   Facebook AppId
                 */
                if (!empty($fbId)) {
                    $fbId = self::replaceVars($fbId);
                    $xagio_og   .= '<meta property="fb:app_id" content="' . $fbId . '"/>' . "\n";
                }

                /**
                 *   Facebook Title
                 */
	            if ( xagio_parse_bool( $xagio_meta['XAGIO_SEO_FACEBOOK_TITLE_USE_FROM_SEO'] ?? false ) || empty( $fbTitle ) ) {
		            $fbTitle = self::getMeta( 'XAGIO_SEO_TITLE' );
	            }
	            $fbTitle = self::replaceVars(xagio_spintax($fbTitle));
                $xagio_og      .= '<meta property="og:title" content="' . $fbTitle . '"/>' . "\n";

	            /**
	             *   Facebook Description
	             */
	            if ( xagio_parse_bool( $xagio_meta['XAGIO_SEO_FACEBOOK_DESCRIPTION_USE_FROM_SEO'] ?? false ) || empty( $fbDesc ) ) {
		            $fbDesc = self::getMeta( 'XAGIO_SEO_DESCRIPTION' );
	            }
	            $fbDesc = self::replaceVars( xagio_spintax( $fbDesc ) );
	            $xagio_og     .= '<meta property="og:description" content="' . esc_attr( $fbDesc ) . '"/>' . "\n";

	            /**
	             *   Facebook Image
	             */
	            if ( xagio_parse_bool( $xagio_meta['XAGIO_SEO_FACEBOOK_USE_FEATURED_IMAGE'] ?? false ) || empty( $fbImg ) ) {
		            $attachment_id = get_post_meta( $xagio_object->ID ?? 0, '_thumbnail_id', true );
		            $fbImg         = wp_get_attachment_image_src( $attachment_id, 'full' );
		            $fbImg         = is_array( $fbImg ) ? $fbImg[0] : '';
	            }
	            $fbImg = self::replaceVars( $fbImg );
	            if ( ! empty( $fbImg ) ) {
		            $xagio_og .= '<meta property="og:image" content="' . esc_url( $fbImg ) . '"/>' . "\n";
	            }

	            // Twitter Vars
	            $twTitle = $xagio_meta['XAGIO_SEO_TWITTER_TITLE'] ?? '';
	            $twDesc  = $xagio_meta['XAGIO_SEO_TWITTER_DESCRIPTION'] ?? '';
	            $twImg   = $xagio_meta['XAGIO_SEO_TWITTER_IMAGE'] ?? '';

				// if any of the OG tags are empty, we will use defaults
	            if ( empty( $twTitle ) ) {
		            $twTitle = $defaults[ $type ]['XAGIO_SEO_TWITTER_TITLE'] ?? '';
	            }
	            if ( empty( $twDesc ) ) {
		            $twDesc = $defaults[ $type ]['XAGIO_SEO_TWITTER_DESCRIPTION'] ?? '';
	            }
	            if ( empty( $twImg ) ) {
		            $twImg = $defaults[ $type ]['XAGIO_SEO_TWITTER_IMAGE'] ?? '';
	            }

	            $xagio_og .= '<meta name="twitter:card" content="summary"/>' . "\n";

	            /**
	             *   Twitter Title
	             */
	            if ( xagio_parse_bool( $xagio_meta['XAGIO_SEO_TWITTER_TITLE_USE_FROM_SEO'] ?? false ) || empty( $twTitle ) ) {
		            $twTitle = self::getMeta( 'XAGIO_SEO_TITLE' );
	            }
	            $twTitle = self::replaceVars( xagio_spintax( $twTitle ) );
	            $xagio_og      .= '<meta name="twitter:title" content="' . esc_attr( $twTitle ) . '"/>' . "\n";

	            /**
	             *   Twitter Description
	             */
	            if ( xagio_parse_bool( $xagio_meta['XAGIO_SEO_TWITTER_DESCRIPTION_USE_FROM_SEO'] ?? false ) || empty( $twDesc ) ) {
		            $twDesc = self::getMeta( 'XAGIO_SEO_DESCRIPTION' );
	            }
	            $twDesc = self::replaceVars( xagio_spintax( $twDesc ) );
	            $xagio_og     .= '<meta name="twitter:description" content="' . esc_attr( $twDesc ) . '"/>' . "\n";

	            /**
	             *   Twitter Image
	             */
	            if ( xagio_parse_bool( $xagio_meta['XAGIO_SEO_TWITTER_USE_FEATURED_IMAGE'] ?? false ) || empty( $twImg ) ) {
		            $attachment_id = get_post_meta( $xagio_object->ID ?? 0, '_thumbnail_id', true );
		            $twImg         = wp_get_attachment_image_src( $attachment_id, 'full' );
		            $twImg         = is_array( $twImg ) ? $twImg[0] : '';
	            }
	            $twImg = self::replaceVars( $twImg );
	            if ( ! empty( $twImg ) ) {
		            $xagio_og .= '<meta name="twitter:image" content="' . esc_url( $twImg ) . '"/>';
	            }

	            return $xagio_og;
            }
            return FALSE;
        }

        public static function renderBlocks()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['html'], $_POST['page'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $html = sanitize_text_field(wp_unslash($_POST['html']));
            $page = abs(intval($_POST['page']));

            xagio_json('success', 'Render done.', self::replaceVars($html, $page));
        }

        public static function replaceVars($string, $post_id = 0, $pre_replace = [])
        {
            if (empty($string))
                return '';

            global $wp, $wp_query;

            if (is_array($string)) {
                if (sizeof($string) > 0) {
                    $string = $string[0];
                } else {
                    $string = '';
                }
            }

            if ($post_id === 0) {
                $post_id = get_option('page_on_front');
            }

            if (!xagio_string_contains('spintax_', $string)) {

                $string = str_replace('{', '%%', $string);
                $string = str_replace('}', '%%', $string);

            }

            $vars = [
                '%%page%%'             => '',
                '%%sitename%%'         => get_bloginfo('name'),
                '%%tagline%%'          => get_bloginfo('description'),
                '%%siteurl%%'          => get_site_url(),
                '%%currurl%%'          => home_url(add_query_arg([], $wp->request)),
                '%%sep%%'              => (!get_option('XAGIO_SEO_TITLE_SEPARATOR')) ? '-' : get_option('XAGIO_SEO_TITLE_SEPARATOR'),
                '%%title%%'            => get_the_title($post_id),
                '%%parent_title%%'     => get_the_title(wp_get_post_parent_id($post_id)),
                '%%date%%'             => get_the_date('', $post_id),
                '%%pretty_date%%'      => get_the_date('F Y', $post_id),
                '%%excerpt%%'          => has_excerpt($post_id) ? get_the_excerpt($post_id) : '',
                '%%tag%%'              => self::getPostTags($post_id),
                '%%category%%'         => self::getPostCategories($post_id),
                '%%category_primary%%' => self::getPostCategoryPrimary($post_id),
                '%%term_title%%'       => (is_category() || is_tag() || is_tax()) ? @$GLOBALS['wp_query']->get_queried_object()->name : '',
                '%%search_query%%'     => get_search_query(TRUE),
                '%%author_name%%'      => get_the_author()
            ];

            // trim down the content to 160 characters
            $xagio_content = get_the_content(null, false, $post_id);
            $xagio_content = wp_strip_all_tags($xagio_content);
            if (strlen($xagio_content) > 160) {
                $xagio_content = substr($xagio_content, 0, 157) . '...';
            }
            $vars['%%content%%'] = $xagio_content;

            foreach ($pre_replace as $xagio_name => $xagio_value) {
                if (is_array($xagio_name)) {
                    $xagio_name = $xagio_name[0];
                }
                if (is_array($xagio_value)) {
                    $xagio_value = $xagio_value[0];
                }
                $string = str_replace($xagio_name, $xagio_value, $string);
            }
            foreach ($vars as $xagio_name => $xagio_value) {
                if (is_array($xagio_name)) {
                    $xagio_name = $xagio_name[0];
                }
                if (is_array($xagio_value)) {
                    $xagio_value = $xagio_value[0];
                }
                $string = str_replace($xagio_name, $xagio_value, $string);
            }

            return do_shortcode($string);
        }

        public static function magicPageSaveUrl($old_value, $xagio_value, $option)
        {
            global $wpdb;

            $groups = $wpdb->get_results($wpdb->prepare('SELECT * FROM xag_groups WHERE `url` = %s', $old_value), ARRAY_A);

            if (!empty($groups)) {

                if (isset($groups['id'])) {
                    $groups = [$groups];
                }

                foreach ($groups as $xagio_group) {

                    $wpdb->update('xag_groups', [
                        'url' => str_replace($old_value, $xagio_value, $xagio_group['url']),
                    ], [
                        'id' => $xagio_group['id'],
                    ]);

                }

            }

        }

        public static function savePost($post_id)
        {
            global $wpdb;

            if(get_option('XAGIO_HIDDEN')) {
                return $post_id;
            }

            $post_type = get_post_type($post_id);
            if ($post_type === 'wpcf7_contact_form') {
                return $post_id;
            }

            // Fix for trashing posts/pages
            if (!isset($_POST['post_ID'])) {
                return $post_id;
            }

            // Fix for Fusion Builder page ID
            if ($_POST['post_ID'] != $post_id) {
                $post_id = intval($_POST['post_ID']);
            }

            if (wp_is_post_revision($post_id)) {
                return $post_id;
            }

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return $post_id;
            }

            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // Check the user's permissions.
            $post_type = isset($_POST['post_type']) ? sanitize_text_field(wp_unslash($_POST['post_type'])) : false;
            if ($post_type == 'page') {
                if (!current_user_can('edit_page', $post_id)) {
                    return $post_id;
                }
            } else {
                if (!current_user_can('edit_post', $post_id)) {
                    return $post_id;
                }
            }

            /**
             *  BEGIN THE SAVING PROCESS
             */
            // Handle URL changes only if the relevant fields are set
            if (isset($_POST['XAGIO_SEO_URL']) && isset($_POST['XAGIO_SEO_ORIGINAL_URL']) && isset($_POST['post_name'])) {
                $newUrl = sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_URL']));
                $oriUrl = sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_ORIGINAL_URL']));
                $pstUrl = sanitize_text_field(wp_unslash($_POST['post_name']));

                if ($newUrl == $oriUrl && $newUrl != $pstUrl) {
                    $newUrl = $pstUrl;
                }

                // Handle redirects only if all required fields are present
                if (class_exists('XAGIO_MODEL_REDIRECTS') && !empty($newUrl) && !empty($oriUrl) && isset($_POST['post_status']) && isset($_POST['original_post_status']) && isset($_POST['save'])) {

                    $post_status          = sanitize_text_field(wp_unslash($_POST['post_status']));
                    $original_post_status = sanitize_text_field(wp_unslash($_POST['original_post_status']));
                    $save                 = sanitize_text_field(wp_unslash($_POST['save']));

                    if ($post_status === 'publish' && $original_post_status === 'publish' && $save === 'Update' && !get_option('XAGIO_DISABLE_AUTOMATIC_REDIRECTS')) {
                        if ($newUrl != $oriUrl) {
                            if (!XAGIO_DISABLE_AUTOMATIC_REDIRECTS) {
                                XAGIO_MODEL_REDIRECTS::add($oriUrl, $newUrl);
                            }
                        }
                    }
                }
            } else if (isset($_POST['post_name'])) {
                $newUrl = sanitize_text_field(wp_unslash($_POST['post_name']));
            } else {
                $newUrl = get_post_field('post_name', $post_id);
            }

            // Update groups if the class exists
            if (class_exists('XAGIO_MODEL_GROUPS')) {
                $post_title = get_the_title($post_id);

                $update_data = [
                    'url' => $newUrl,
                    'h1'  => $post_title
                ];

                // Only add fields that are set in $_POST
                if (isset($_POST['post_title'])) {
                    $update_data['h1'] = sanitize_text_field(wp_unslash($_POST['post_title']));
                }
                if (isset($_POST['XAGIO_SEO_TITLE'])) {
                    $update_data['title'] = sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_TITLE']));
                }
                if (isset($_POST['XAGIO_SEO_DESCRIPTION'])) {
                    $update_data['description'] = sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_DESCRIPTION']));
                }
                if (isset($_POST['XAGIO_SEO_NOTES'])) {
                    $update_data['notes'] = base64_encode(wp_unslash($_POST['XAGIO_SEO_NOTES']));
                }

                $wpdb->update('xag_groups', $update_data, ['id_page_post' => $post_id]);
            }

            /** Schema */
            if (class_exists('XAGIO_MODEL_SCHEMA') && isset($_POST['XAGIO_SEO_SCHEMAS'])) {
                $empty_schema = TRUE;
                if (!empty($_POST['XAGIO_SEO_SCHEMAS'])) {
                    $schemaIDs = explode(',', sanitize_text_field(wp_unslash($_POST['XAGIO_SEO_SCHEMAS'])));
                    unset($_POST['XAGIO_SEO_SCHEMAS']);
                    if (!empty($schemaIDs)) {
                        $renderedSchemas = XAGIO_MODEL_SCHEMA::getRemoteRenderedSchemas($schemaIDs, $post_id);
                        if ($renderedSchemas !== FALSE) {
                            $empty_schema = FALSE;
                            if (XAGIO_MODEL_SEO::is_homepage($post_id)) {
                                update_option('XAGIO_SEO_SCHEMA_META', @$renderedSchemas['meta']);
                                update_option('XAGIO_SEO_SCHEMA_DATA', @$renderedSchemas['data']);
                            }
                            update_post_meta($post_id, 'XAGIO_SEO_SCHEMA_META', @$renderedSchemas['meta']);
                            update_post_meta($post_id, 'XAGIO_SEO_SCHEMA_DATA', @$renderedSchemas['data']);
                        }
                    }
                }

                if ($empty_schema) {
                    if (XAGIO_MODEL_SEO::is_homepage($post_id)) {
                        update_option('XAGIO_SEO_SCHEMA_META', FALSE);
                        update_option('XAGIO_SEO_SCHEMA_DATA', FALSE);
                    }

                    update_post_meta($post_id, 'XAGIO_SEO_SCHEMA_META', FALSE);
                    update_post_meta($post_id, 'XAGIO_SEO_SCHEMA_DATA', FALSE);
                }
            }

            $allowed_tags = [
                'script' => [
                    'src'            => true,
                    'type'           => true,
                    'async'          => true,
                    'defer'          => true,
                    'crossorigin'    => true,
                    'integrity'      => true,
                    'nomodule'       => true,
                    'charset'        => true,
                    'referrerpolicy' => true,
                    'id'             => true,
                    'class'          => true,
                    'data-*'         => true
                ]
            ];

            // Define field configurations with their sanitization functions
            $field_configs = [
                'XAGIO_SEO_TARGET_KEYWORD' => [
                    'sanitize_text_field',
                    false
                ],
                'XAGIO_SEO_SEARCH_PREVIEW_ENABLE' => [
                    'absint',
                    false
                ],
                'XAGIO_SEO_ORIGINAL_URL' => [
                    'sanitize_url',
                    false
                ],
                'XAGIO_SEO_URL' => [
                    'sanitize_url',
                    false
                ],
                'XAGIO_SEO_TITLE' => [
                    'sanitize_text_field',
                    false
                ],
                'XAGIO_SEO_DESCRIPTION' => [
                    'sanitize_textarea_field',
                    false
                ],
                'XAGIO_SEO_SOCIAL_ENABLE' => [
                    'absint',
                    false
                ],
                'XAGIO_SEO_FACEBOOK_TITLE_USE_FROM_SEO' => [
                    'filter_var',
                    FILTER_VALIDATE_BOOLEAN
                ],
                'XAGIO_SEO_FACEBOOK_DESCRIPTION_USE_FROM_SEO' => [
                    'filter_var',
                    FILTER_VALIDATE_BOOLEAN
                ],
                'XAGIO_SEO_FACEBOOK_USE_FEATURED_IMAGE' => [
                    'filter_var',
                    FILTER_VALIDATE_BOOLEAN
                ],
                'XAGIO_SEO_TWITTER_TITLE_USE_FROM_SEO' => [
                    'filter_var',
                    FILTER_VALIDATE_BOOLEAN
                ],
                'XAGIO_SEO_TWITTER_DESCRIPTION_USE_FROM_SEO' => [
                    'filter_var',
                    FILTER_VALIDATE_BOOLEAN
                ],
                'XAGIO_SEO_TWITTER_USE_FEATURED_IMAGE' => [
                    'filter_var',
                    FILTER_VALIDATE_BOOLEAN
                ],
                'XAGIO_SEO_FACEBOOK_TITLE' => [
                    'sanitize_text_field',
                    false
                ],
                'XAGIO_SEO_FACEBOOK_DESCRIPTION' => [
                    'sanitize_textarea_field',
                    false
                ],
                'XAGIO_SEO_FACEBOOK_IMAGE' => [
                    'sanitize_url',
                    false
                ],
                'XAGIO_SEO_FACEBOOK_APP_ID' => [
                    'sanitize_text_field',
                    false
                ],
                'XAGIO_SEO_TWITTER_TITLE' => [
                    'sanitize_text_field',
                    false
                ],
                'XAGIO_SEO_TWITTER_DESCRIPTION' => [
                    'sanitize_textarea_field',
                    false
                ],
                'XAGIO_SEO_TWITTER_IMAGE' => [
                    'sanitize_url',
                    false
                ],
                'XAGIO_SEO_META_ROBOTS_ENABLE' => [
                    'absint',
                    false
                ],
                'XAGIO_SEO_META_ROBOTS_INDEX' => [
                    'sanitize_text_field',
                    false
                ],
                'XAGIO_SEO_META_ROBOTS_FOLLOW' => [
                    'sanitize_text_field',
                    false
                ],
                'XAGIO_SEO_CANONICAL_URL' => [
                    'sanitize_url',
                    false
                ],
                'XAGIO_SEO_SCHEMA_ENABLE' => [
                    'absint',
                    false
                ],
                'XAGIO_SEO_SCHEMAS' => [
                    'sanitize_textarea_field',
                    false
                ],
                'XAGIO_SEO_SCRIPTS_ENABLE' => [
                    'absint',
                    false
                ],
                'XAGIO_SEO_SCRIPTS_HEADER' => [
                    'wp_kses',
                    $allowed_tags
                ],
                'XAGIO_SEO_DISABLE_PAGE_HEADER_SCRIPTS' => [
                    'filter_var',
                    FILTER_VALIDATE_BOOLEAN
                ],
                'XAGIO_SEO_DISABLE_GLOBAL_HEADER_SCRIPTS' => [
                    'filter_var',
                    FILTER_VALIDATE_BOOLEAN
                ],
                'XAGIO_SEO_SCRIPTS_BODY' => [
                    'wp_kses',
                    $allowed_tags
                ],
                'XAGIO_SEO_DISABLE_PAGE_BODY_SCRIPTS' => [
                    'filter_var',
                    FILTER_VALIDATE_BOOLEAN
                ],
                'XAGIO_SEO_DISABLE_GLOBAL_BODY_SCRIPTS' => [
                    'filter_var',
                    FILTER_VALIDATE_BOOLEAN
                ],
                'XAGIO_SEO_SCRIPTS_FOOTER' => [
                    'wp_kses',
                    $allowed_tags
                ],
                'XAGIO_SEO_DISABLE_PAGE_FOOTER_SCRIPTS' => [
                    'filter_var',
                    FILTER_VALIDATE_BOOLEAN
                ],
                'XAGIO_SEO_DISABLE_GLOBAL_FOOTER_SCRIPTS' => [
                    'filter_var',
                    FILTER_VALIDATE_BOOLEAN
                ],
                'XAGIO_SEO_NOTES' => [
                    'base64_encode',
                    false
                ]
            ];

            // Only process fields that are actually set in $_POST
            foreach ($field_configs as $field => $config) {
                if (isset($_POST[$field])) {
                    $xagio_value = wp_unslash($_POST[$field]);

                    // Handle special cases for filter_var and wp_kses
                    if ($config[0] === 'filter_var') {
                        $xagio_value = filter_var($xagio_value, $config[1]);
                    } elseif ($config[0] === 'wp_kses') {
                        $xagio_value = wp_kses($xagio_value, $config[1]);
                        // Keep slashes for script fields
                        $xagio_value = wp_slash($xagio_value);
                    } else {
                        $xagio_value = $config[0]($xagio_value);
                    }

                    update_post_meta($post_id, $field, $xagio_value);
                }
            }

            // Handle META_ROBOTS_ADVANCED separately as it's an array
            if (isset($_POST['XAGIO_SEO_META_ROBOTS_ADVANCED'])) {
                $robots_advanced = map_deep(wp_unslash($_POST['XAGIO_SEO_META_ROBOTS_ADVANCED']), 'sanitize_text_field');
                update_post_meta($post_id, 'XAGIO_SEO_META_ROBOTS_ADVANCED', $robots_advanced);
            } else {
                update_post_meta($post_id, 'XAGIO_SEO_META_ROBOTS_ADVANCED', []);
            }

            return $post_id;
        }

        public static function trashPost($post_id)
        {
            global $wpdb;

            if (!empty($post_id)) {
                $wpdb->update(
                    'xag_groups', [
                    'id_page_post' => 0,
                ], [
                        'id_page_post' => $post_id,
                    ]
                );
            }
        }

        public static function addMetaBoxes($post_type)
        {
            if (!get_option('XAGIO_HIDDEN')) {
                add_meta_box(
                    'xagio_seo', '<img class="logo-image-seo" src="' . XAGIO_URL . 'assets/img/logo-xagio-smaller.webp"> <b class="xagio-bold">Xagio</b>', [
                    'XAGIO_MODEL_SEO',
                    'renderSEO'
                ], $post_type, 'advanced', 'core'
                );
            }
        }

        public static function renderSEO($post)
        {
            require_once(dirname(__FILE__) . '/../metabox/seo.php');
        }

        public static function renderOptimizationSEO($post)
        {
            require_once(dirname(__FILE__) . '/../metabox/optimization.php');
        }

        public static function renderSEO_Terms($tag, $taxonomy)
        {
            require_once(dirname(__FILE__) . '/../metabox/terms.php');
        }

        public static function formatMetaVariables($xagio_meta)
        {
            $tmp = [];
            if (empty($xagio_meta) || !$xagio_meta) {
                return $tmp;
            }
            foreach ($xagio_meta as $xagio_key => $xagio_value) {
                $tmp[$xagio_key] = $xagio_value[0];
            }
            return $tmp;
        }

        public static function extract_url_parts($id, $check_terms_first = false)
        {
            $xagio_url  = null;
            $post = null;

            if ($check_terms_first) {
                // Check terms first
                if (term_exists($id)) {
                    $xagio_url = get_term_link($id);
                    if (is_wp_error($xagio_url)) {
                        return false;
                    }
                } else {
                    // Fallback to post check
                    $post = get_post($id);
                    if ($post) {
                        $xagio_url = get_permalink($id);
                    }
                }
            } else {
                // Check posts first (default behavior)
                $post = get_post($id);
                if ($post) {
                    $xagio_url = get_permalink($id);
                } else if (term_exists($id)) {
                    $xagio_url = get_term_link($id);
                    if (is_wp_error($xagio_url)) {
                        return false;
                    }
                }
            }

            // If no valid URL was found, return false
            if (!$xagio_url) {
                return false;
            }

            $xagio_url  = wp_parse_url($xagio_url);
            $host = $xagio_url['scheme'] . "://" . $xagio_url['host'];

            $final = [
                'host' => $host
            ];

            // If post is in draft or pending
            if ($post && in_array(get_post_status($id), [
                    'draft',
                    'pending'
                ])) {
                $sample_permalink = get_sample_permalink($id);

                $url_structure = wp_parse_url($sample_permalink[0]);
                $url_structure = $url_structure['path'];
                $url_structure = explode("/", $url_structure);
                $url_structure = array_values(array_filter($url_structure));
                array_pop($url_structure);
                $permalink = $sample_permalink[1];

                $final['parts']        = $url_structure;
                $final['editable_url'] = $permalink;
            } else {
                $path = explode('/', $xagio_url['path']);
                $path = array_values(array_filter($path));

                $xagio_name = "";
                if (sizeof($path) > 0) {
                    if (sizeof($path) === 1) {
                        $xagio_name = $path[0];
                        $path = [];
                    } else {
                        $xagio_name = end($path);
                        array_pop($path);
                    }
                }

                $final['parts']        = $path;
                $final['editable_url'] = $xagio_name;
            }

            return $final;
        }

        public static function extract_url_name($xagio_url)
        {
            $xagio_url = explode("/", $xagio_url);
            if (isset($xagio_url[sizeof($xagio_url) - 2])) {
                $xagio_url = $xagio_url[sizeof($xagio_url) - 2];
            } else {
                $xagio_url = $xagio_url[0];
            }
            return $xagio_url;
        }

        public static function extract_url($id, $taxonomy = FALSE)
        {
            if ($taxonomy == FALSE) {
                $xagio_url = get_permalink($id);
            } else {
                $term = get_term($id);
                $xagio_url  = get_term_link($term);
            }

            $site_url = get_site_url();

            if (!isset($_SERVER['HTTP_HOST'])) {
                return $xagio_url;
            }

            $xagio_url = str_replace($site_url, '', $xagio_url);
            $xagio_url = str_replace(sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])), '', $xagio_url);
            $xagio_url = str_replace('http://', '', $xagio_url);
            $xagio_url = str_replace('https://', '', $xagio_url);
            return $xagio_url;
        }

        public static function is_homepage($post_id = null) {
            $show_on_front = get_option('show_on_front');

            if ($post_id === null) {
                return (is_front_page() && 'page' == $show_on_front && is_page(get_option('page_on_front'))) || // static homepage
                    (is_home() && 'page' == $show_on_front) || // posts page
                    (is_home() && 'posts' == $show_on_front);  // blog homepage
            }

            return ('page' == $show_on_front && $post_id == get_option('page_on_front')) || // static homepage
                ('page' == $show_on_front && $post_id == get_option('page_for_posts')) || // posts page
                ('posts' == $show_on_front && $post_id == get_option('page_on_front'));   // blog homepage
        }

        public static function is_home_static_page()
        {
            return self::is_homepage();
        }

        public static function is_posts_page()
        {
            return self::is_homepage();
        }

        public static function is_home_posts_page()
        {
            return self::is_homepage();
        }

        public static function is_sub_page()
        {
            $xagio_object = $GLOBALS['wp_query']->get_queried_object();
            if (is_object($xagio_object) && isset($xagio_object->post_parent)) {
                if (is_page() && $xagio_object->post_parent > 0) {
                    return TRUE;
                }
                return FALSE;
            }

            return FALSE;
        }

        public static function getPostTags($id)
        {
            $post_tags = wp_get_post_tags($id);
            $tags      = [];
            foreach ($post_tags as $tag) {
                $tags[] = $tag->name;
            }
            return join(', ', $tags);
        }

        public static function getPostCategories($id)
        {
            $post_categories = wp_get_post_categories($id);
            $cats            = [];
            foreach ($post_categories as $c) {
                $cat    = get_category($c);
                $cats[] = $cat->name;
            }
            return join(', ', $cats);
        }

        public static function getPostCategoryPrimary($id)
        {
            $category_primary_id = get_post_meta($id, '_category_permalink', TRUE);
            if (!empty($category_primary_id)) {
                $category = get_category($category_primary_id);
                return $category->name;
            } else {
                return '';
            }
        }

        public static function getAllTaxonomies($all = TRUE)
        {
            if ($all == TRUE) {
                return get_taxonomies();
            } else {
                $xagio_taxonomies = get_taxonomies([
                    'public'   => TRUE,
                    '_builtin' => TRUE,
                ]);
                return $xagio_taxonomies;
            }
        }

        public static function getAllPostTypes()
        {
            $xagio_post_types = [
                'post',
                'page'
            ];
            foreach (get_post_types([
                '_builtin' => FALSE,
                'public'   => TRUE
            ], 'names') as $xagio_k => $xagio_p) {
                $xagio_post_types[] = $xagio_p;
            }
            return $xagio_post_types;
        }

        public static function getAllCategoriesAndTags()
        {
            $data = [];

            // Get categories as id => name
            $categories = get_terms([
                'taxonomy'   => 'category',
                'hide_empty' => false,
            ]);

            $data['categories'] = [];
            foreach ($categories as $category) {
                $data['categories'][$category->term_id] = $category->name;
            }

            // Get tags as id => name
            $tags = get_terms([
                'taxonomy'   => 'post_tag',
                'hide_empty' => false,
            ]);

            $data['tags'] = [];
            foreach ($tags as $tag) {
                $data['tags'][$tag->name] = $tag->name;
            }

            return $data;
        }

        public static function getAllPostObjects()
        {
            $xagio_post_types = [
                'post',
                'page'
            ];
            foreach (get_post_types([
                '_builtin' => FALSE,
                'public'   => TRUE
            ], 'objects') as $xagio_k => $xagio_p) {
                if (is_object($xagio_p)) {
                    if (isset($xagio_p->name) && isset($xagio_p->label)) {
                        $xagio_post_types[] = [
                            'name'  => $xagio_p->name,
                            'label' => $xagio_p->label,
                        ];
                    }
                }
            }
            return $xagio_post_types;
        }

        public static function getOtherPostObjects()
        {
            $xagio_post_types = [];
            foreach (get_post_types([
                '_builtin' => FALSE,
                'public'   => TRUE
            ], 'objects') as $xagio_k => $xagio_p) {
                if (is_object($xagio_p)) {
                    if (isset($xagio_p->name) && isset($xagio_p->label)) {
                        $xagio_post_types[] = [
                            'name'  => $xagio_p->name,
                            'label' => $xagio_p->label,
                        ];
                    }
                }
            }
            return $xagio_post_types;
        }

        public static function getAllCustomPostObjects()
        {
            $xagio_post_types = [];
            foreach (get_post_types([
                '_builtin' => FALSE,
                'public'   => TRUE
            ], 'objects') as $xagio_k => $xagio_p) {
                if (is_object($xagio_p)) {
                    if (isset($xagio_p->name) && isset($xagio_p->label)) {
                        $xagio_post_types[] = [
                            'name'  => $xagio_p->name,
                            'label' => $xagio_p->label,
                        ];
                    }
                }
            }
            return $xagio_post_types;
        }

        public static function getAllPosts($ONLY_IDS = FALSE)
        {
            global $wpdb;
            $xagio_post_types = self::getAllPostTypes();

            // Create placeholders for the post types
            $xagio_placeholders = implode(', ', array_fill(0, count($xagio_post_types), '%s'));

            // Execute the query
            $out = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $wpdb->posts WHERE post_status = %s AND post_type IN ($xagio_placeholders)", ...array_merge(['publish'], $xagio_post_types)
                ), ARRAY_A
            );

            // If only IDs are requested, extract and return them
            if ($ONLY_IDS) {
                $n = [];
                foreach ($out as $xagio_p) {
                    $n[] = intval($xagio_p['ID']);
                }
                $out = $n;
            }

            return $out;
        }

        public static function getKeywordSuggestions()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['keyword'], $_POST['post_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $keyword = sanitize_text_field(wp_unslash($_POST['keyword']));
            $post_id = abs(intval($_POST['post_id']));

            if (empty($keyword) || empty($post_id)) {
                xagio_json('error', 'Required fields are missing.');
            }

            $originalKeyword = $keyword;

            if ($suggestion_data = get_transient('xagio_keyword_suggestions_' . $post_id)) {
                if (isset($suggestion_data[$originalKeyword])) {
                    xagio_json('success', 'Cached!', $suggestion_data[$originalKeyword]);
                }
            }

            $letters = [
                '',
                'a',
                'b',
                'c',
                'd',
                'e',
                'f',
                'g',
                'h',
                'i',
                'j',
                'k',
                'l',
                'm',
                'n',
                'o',
                'p',
                'q',
                'r',
                's',
                't',
                'u',
                'v',
                'w',
                'x',
                'y',
                'z',
                '0',
                '1',
                '2',
                '3',
                '4',
                '5',
                '6',
                '7',
                '8',
                '9'
            ];

            $keywords = [];

            foreach ($letters as $letter) {

                $pos = strpos($keyword, '%');

                if ($pos !== false) {
                    $pos++;
                } else {
                    $pos     = 0;
                    $keyword = trim($keyword);
                    $a       = $keyword . ' ' . $letter;
                }

                $data = xagio_file_get_contents('http://www.google.com/complete/search?hl=en&client=firefox&cp=' . $pos . '&q=' . urlencode($a));
                if (($data = json_decode($data, true)) !== null) {
                    $keywords = array_merge($keywords, $data[1]);
                }

            }

            $keywords_new = [];
            if (!empty($keywords)) {
                $keywords = array_values(array_unique($keywords));

                $keywords_data = XAGIO_API::apiRequest(
                    $apiEndpoint = 'get_volume_cpc', $method = 'POST', $xagio_args = [
                    'keywords' => join(',', $keywords),
                ], $xagio_http_code, $without_license = TRUE
                );

                foreach ($keywords as $keyword) {
                    foreach ($keywords_data as $keyword_data) {
                        if ($keyword_data['keyword'] == $keyword) {
                            $keywords_new[] = $keyword_data;
                            continue 2;
                        }
                    }

                    $keywords_new[] = [
                        'keyword' => $keyword,
                        'volume'  => 0,
                        'cpc'     => '0.00',
                    ];
                }

                usort($keywords_new, function ($a, $b) {
                    return $b['volume'] - $a['volume'];
                });
            }

            $suggestion_data[$originalKeyword] = $keywords_new;
            set_transient('xagio_keyword_suggestions_' . $post_id, $suggestion_data, 604800);

            xagio_json('success', 'Keyword Suggestions completed!', $keywords_new);

        }

        public static function xagio_file_get_contents_utf8($fn)
        {
            $xagio_content = xagio_file_get_contents($fn);
            return mb_convert_encoding(
                $xagio_content, 'UTF-8', mb_detect_encoding($xagio_content, 'UTF-8, ISO-8859-1', true)
            );
        }

    }

}
