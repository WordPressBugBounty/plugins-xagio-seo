<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_SITEMAPS')) {

    class XAGIO_MODEL_SITEMAPS
    {

        private static function defines()
        {
            define('XAGIO_ENABLE_SITEMAPS', filter_var(get_option('XAGIO_ENABLE_SITEMAPS'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_SITEMAP_COMPRESSION', filter_var(get_option('XAGIO_SITEMAP_COMPRESSION'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_CACHE_SITEMAPS', filter_var(get_option('XAGIO_CACHE_SITEMAPS'), FILTER_VALIDATE_BOOLEAN));
        }

        public static function initialize()
        {
            XAGIO_MODEL_SITEMAPS::defines();

            add_action('template_redirect', [
                'XAGIO_MODEL_SITEMAPS',
                'displaySitemap'
            ], 99);

            if (get_option('XAGIO_ENABLE_SITEMAPS') == true) {
                add_filter('wp_sitemaps_enabled', '__return_false');
            }

            if (!XAGIO_HAS_ADMIN_PERMISSIONS)
                return;

            add_action('admin_post_xagio_sitemaps_settings', [
                'XAGIO_MODEL_SITEMAPS',
                'saveSitemapSettings'
            ]);
            add_action('admin_post_xagio_content_settings', [
                'XAGIO_MODEL_SITEMAPS',
                'saveContentSettings'
            ]);
            add_action('admin_post_xagio_get_sitemaps', [
                'XAGIO_MODEL_SITEMAPS',
                'getSitemaps'
            ]);

        }

        public static function saveSitemapSettings()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['XAGIO_ENABLE_SITEMAPS'], $_POST['XAGIO_SITEMAP_COMPRESSION'], $_POST['XAGIO_CACHE_SITEMAPS'], $_POST['XAGIO_DONT_INDEX_SUBPAGES'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $XAGIO_ENABLE_SITEMAPS     = intval($_POST['XAGIO_ENABLE_SITEMAPS']);
            $XAGIO_SITEMAP_COMPRESSION = intval($_POST['XAGIO_SITEMAP_COMPRESSION']);
            $XAGIO_CACHE_SITEMAPS      = intval($_POST['XAGIO_CACHE_SITEMAPS']);
            $XAGIO_DONT_INDEX_SUBPAGES = intval($_POST['XAGIO_DONT_INDEX_SUBPAGES']);

            update_option('XAGIO_ENABLE_SITEMAPS', $XAGIO_ENABLE_SITEMAPS);
            update_option('XAGIO_SITEMAP_COMPRESSION', $XAGIO_SITEMAP_COMPRESSION);
            update_option('XAGIO_CACHE_SITEMAPS', $XAGIO_CACHE_SITEMAPS);
            update_option('XAGIO_DONT_INDEX_SUBPAGES', $XAGIO_DONT_INDEX_SUBPAGES);

            if ($XAGIO_ENABLE_SITEMAPS) {
                $db_values = get_option("XAGIO_SITEMAP_CONTENT_SETTINGS");

                $db_values['post_types']['post']['enabled']     = "1";
                $db_values['post_types']['page']['enabled']     = "1";
                $db_values['taxonomies']['category']['enabled'] = "1";
                $db_values['taxonomies']['post_tag']['enabled'] = "1";

                update_option('XAGIO_SITEMAP_CONTENT_SETTINGS', $db_values);
            }

            if ($XAGIO_CACHE_SITEMAPS) {
                delete_transient('xagio_sitemaps');
            } else {
                $files = glob(ABSPATH . 'sitemap-xag*.xml');
                foreach ($files as $file) {
                    wp_delete_file($file);
                }
            }

            if ($XAGIO_ENABLE_SITEMAPS) {
                XAGIO_MODEL_SITEMAPS::createSitemap();
            } else {
                XAGIO_MODEL_SITEMAPS::deleteSitemap();
            }

            xagio_json('success', 'Sitemap settings saved successfully.');
        }

        public static function getSitemaps()
        {
            $sitemaps = [];
            $return   = false;

            if (isset($_GET['return'])) {
                $return = true;
            }

            if (XAGIO_ENABLE_SITEMAPS) {
                if (XAGIO_CACHE_SITEMAPS) {
                    // get sitemaps from cache
                    $sitemaps      = glob(ABSPATH . 'sitemap-xag*.xml');
                    $sitemap_array = [];
                    foreach ($sitemaps as $sitemap) {
                        $sitemap_array[basename($sitemap)] = xagio_file_get_contents($sitemap);
                    }
                    $sitemaps = $sitemap_array;
                } else {
                    $sitemaps = get_transient('xagio_sitemaps');
                    if (empty($sitemaps)) {
                        self::createSitemap();
                        $sitemaps = get_transient('xagio_sitemaps');
                    }
                }

                // sort sitemaps by key length
                uksort($sitemaps, function ($a, $b) {
                    return strlen($a) <=> strlen($b);
                });
            }

            if (!$return) {
                return $sitemaps;
            } else {
                xagio_json('success', 'Sitemaps successfully retrieved', $sitemaps);
            }
        }

        public static function saveContentSettings()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['values'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $VALUES = map_deep(wp_unslash($_POST['values']), 'sanitize_text_field');
            update_option('XAGIO_SITEMAP_CONTENT_SETTINGS', $VALUES);

            XAGIO_MODEL_SITEMAPS::createSitemap();

            xagio_json('success', 'Content settings saved successfully.');
        }

        public static function createSitemap()
        {
            $XAGIO_CACHE_SITEMAPS = filter_var(get_option('XAGIO_CACHE_SITEMAPS'), FILTER_VALIDATE_BOOLEAN);

            $sitemaps         = [];
            $CONTENT_SETTINGS = get_option('XAGIO_SITEMAP_CONTENT_SETTINGS');

            if (empty($CONTENT_SETTINGS)) {
                $CONTENT_SETTINGS = [
                    'post_types' => [],
                    'taxonomies' => []
                ];
            }

            foreach ($CONTENT_SETTINGS['post_types'] as $post_type => $settings) {
                if ($settings['enabled']) {

                    $settings['type'] = 'post_type';
                    $sitemap          = self::generateSitemap($post_type, $settings);

                    $filename = 'sitemap-xagio-' . $post_type . '.xml';

                    if (empty($sitemap)) {

                        if ($XAGIO_CACHE_SITEMAPS) {
                            wp_delete_file(ABSPATH . $filename);
                        } else {
                            $db_sitemaps = get_transient('xagio_sitemaps');
                            if (!is_array($db_sitemaps))
                                $db_sitemaps = [];
                            unset($db_sitemaps[$filename]);
                            set_transient('xagio_sitemaps', $db_sitemaps, 60 * 60 * 24);
                        }

                    } else {

                        if ($XAGIO_CACHE_SITEMAPS) {
                            xagio_file_put_contents(ABSPATH . $filename, $sitemap);
                        } else {
                            $db_sitemaps = get_transient('xagio_sitemaps');
                            if (!is_array($db_sitemaps))
                                $db_sitemaps = [];
                            $db_sitemaps[$filename] = $sitemap;
                            set_transient('xagio_sitemaps', $db_sitemaps, 60 * 60 * 24);
                        }

                        $sitemaps[] = get_site_url() . '/' . $filename;

                    }

                } else {
                    $filename = 'sitemap-xagio-' . $post_type . '.xml';

                    if ($XAGIO_CACHE_SITEMAPS) {
                        wp_delete_file(ABSPATH . $filename);
                    } else {
                        $db_sitemaps = get_transient('xagio_sitemaps');
                        if (!is_array($db_sitemaps))
                            $db_sitemaps = [];
                        if (isset($db_sitemaps[$filename])) {
                            unset($db_sitemaps[$filename]);
                            set_transient('xagio_sitemaps', $db_sitemaps, 60 * 60 * 24);
                        }
                    }
                }
            }

            foreach ($CONTENT_SETTINGS['taxonomies'] as $taxonomy => $settings) {
                if ($settings['enabled']) {

                    $settings['type'] = 'taxonomy';
                    $sitemap          = self::generateSitemap($taxonomy, $settings);

                    $filename = 'sitemap-xagio-' . $taxonomy . '.xml';

                    if (empty($sitemap)) {

                        if ($XAGIO_CACHE_SITEMAPS) {
                            wp_delete_file(ABSPATH . $filename);
                        } else {
                            $db_sitemaps = get_transient('xagio_sitemaps');
                            if (!is_array($db_sitemaps))
                                $db_sitemaps = [];
                            unset($db_sitemaps[$filename]);
                            set_transient('xagio_sitemaps', $db_sitemaps, 60 * 60 * 24);
                        }

                    } else {

                        if ($XAGIO_CACHE_SITEMAPS) {
                            xagio_file_put_contents(ABSPATH . $filename, $sitemap);
                        } else {
                            $db_sitemaps = get_transient('xagio_sitemaps');
                            if (!is_array($db_sitemaps))
                                $db_sitemaps = [];
                            $db_sitemaps[$filename] = $sitemap;
                            set_transient('xagio_sitemaps', $db_sitemaps, 60 * 60 * 24);
                        }

                        $sitemaps[] = get_site_url() . '/' . $filename;

                    }

                } else {
                    $filename = 'sitemap-xagio-' . $taxonomy . '.xml';

                    if ($XAGIO_CACHE_SITEMAPS) {
                        wp_delete_file(ABSPATH . $filename);
                    } else {
                        $db_sitemaps = get_transient('xagio_sitemaps');
                        if (!is_array($db_sitemaps))
                            $db_sitemaps = [];
                        if (isset($db_sitemaps[$filename])) {
                            unset($db_sitemaps[$filename]);
                            set_transient('xagio_sitemaps', $db_sitemaps, 60 * 60 * 24);
                        }
                    }
                }
            }

            $sitemapindex          = self::generateSiteMapIndex($sitemaps);
            $sitemapindex_filename = 'sitemap-xag.xml';
            if ($XAGIO_CACHE_SITEMAPS) {
                xagio_file_put_contents(ABSPATH . $sitemapindex_filename, $sitemapindex);
            } else {
                $db_sitemaps                         = get_transient('xagio_sitemaps');
                $db_sitemaps[$sitemapindex_filename] = $sitemapindex;
                set_transient('xagio_sitemaps', $db_sitemaps, 60 * 60 * 24);
            }
        }

        public static function deleteSitemap()
        {
            delete_transient('xagio_sitemaps');
            $files = glob(ABSPATH . 'sitemap-xag*.xml');
            foreach ($files as $file) {
                wp_delete_file($file);
            }
        }

        public static function displaySitemap()
        {
            $allowed_tags = [
                'sitemapindex' => [
                    'xmlns:xsi'          => [],
                    'xsi:schemalocation' => [],
                    'xmlns'              => []
                ],
                'urlset'       => [
                    'xmlns:xsi'          => [],
                    'xsi:schemalocation' => [],
                    'xmlns'              => []
                ],
                'sitemap'      => [],
                'url'          => [],
                'loc'          => [],
                'lastmod'      => [],
                'changefreq'   => [],
                'priority'     => [],
            ];

            if (!isset($_SERVER['REQUEST_URI'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $request_url = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));


            if (strpos($request_url, 'sitemap') !== false && strpos($request_url, '.xml') !== false && get_option('XAGIO_ENABLE_SITEMAPS')) {
                $sitemaps = self::getSitemaps();
                $filename = basename(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])));
                if (isset($sitemaps[$filename])) {

                    http_response_code(200);
                    header('Content-Type: application/xml');

                    // TODO - not sure if this is good solution for outputing XMLs? I guess it is?
                    echo wp_kses($sitemaps[$filename], $allowed_tags);
                    exit;

                } else {

                    http_response_code(404);
                    header('Content-Type: text/plain');
                    echo '404 Not Found';
                    exit;

                }

            }

        }

        public static function generateSitemapIndex($sitemaps)
        {
            // fetch the template from meta folder
            $template = xagio_file_get_contents(XAGIO_PATH . '/modules/sitemaps/meta/sitemapindex.xml');

            // replace {{url}} with the site url and {{date}} with the current date in human-readable format
            $template = str_replace('{{url}}', get_site_url(), $template);
            $template = str_replace('{{date}}', gmdate('H:i:s F j, Y'), $template);

            $sitemap = '';
            foreach ($sitemaps as $data) {
                $sitemap .= '<sitemap>' . "\n";
                $sitemap .= '<loc>' . esc_url($data) . '</loc>' . "\n";
                $sitemap .= '<lastmod>' . gmdate('Y-m-d') . '</lastmod>' . "\n";
                $sitemap .= '</sitemap>' . "\n";
            }

            if (empty($sitemap))
                return null;

            $template = str_replace('{{urls}}', $sitemap, $template);

            return $template;
        }

        public static function generateSitemap($value = 'post', $settings = [])
        {
            // fetch the template from meta folder
            $template = xagio_file_get_contents(XAGIO_PATH . '/modules/sitemaps/meta/sitemap.xml');

            // replace {{url}} with the site url and {{date}} with the current date in human-readable format
            $template = str_replace('{{url}}', get_site_url(), $template);
            $template = str_replace('{{date}}', gmdate('H:i:s F j, Y'), $template);

            // Set up an array to hold the data for our sitemap
            $sitemap_data = [];

            if ($settings['type'] == 'post_type') {

                // Get a list of all published posts
                $posts_args = array(
                    'post_type'      => $value,
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                );
                $posts      = get_posts($posts_args);

                // sort posts by url length
                usort($posts, function ($a, $b) {
                    return strlen(get_permalink($a->ID)) - strlen(get_permalink($b->ID));
                });

                if(!empty($settings['exclusions'])) {
                    $settings['exclusions'] = explode(',', $settings['exclusions']);
                } else {
                    $settings['exclusions'] = [];
                }

                // Add each post to the sitemap data array
                foreach ($posts as $post) {
                    if(!in_array($post->ID, $settings['exclusions'])) {
                        $sitemap_data[] = array(
                            'loc'        => get_permalink($post->ID),
                            'lastmod'    => get_the_modified_date('Y-m-d', $post->ID),
                            'priority'   => $settings['priority'],
                            'changefreq' => $settings['change_frequency']
                        );
                    }
                }

            } else if ($settings['type'] == 'taxonomy') {

                $terms = get_terms(array(
                    'taxonomy'   => $value,
                    'hide_empty' => true,
                ));

                // sort taxomonies by url length
                usort($terms, function ($a, $b) {
                    return strlen(get_term_link($a->term_id)) - strlen(get_term_link($b->term_id));
                });

                foreach ($terms as $term) {
                    $sitemap_data[] = array(
                        'loc'        => get_term_link($term),
                        'priority'   => $settings['priority'],
                        'changefreq' => $settings['change_frequency']
                    );
                }

            }

            // Output the sitemap data as an XML document
            $sitemap = '';
            foreach ($sitemap_data as $data) {
                $sitemap .= '<url>' . "\n";
                $sitemap .= '<loc>' . esc_url($data['loc']) . '</loc>' . "\n";
                if (isset($data['lastmod'])) {
                    $sitemap .= '<lastmod>' . esc_html($data['lastmod']) . '</lastmod>' . "\n";
                }
                if (isset($data['priority'])) {
                    $sitemap .= '<priority>' . esc_html($data['priority']) . '</priority>' . "\n";
                }
                if (isset($data['changefreq'])) {
                    $sitemap .= '<changefreq>' . esc_html($data['changefreq']) . '</changefreq>' . "\n";
                }
                $sitemap .= '</url>' . "\n";
            }

            if (empty($sitemap))
                return null;

            $template = str_replace('{{urls}}', $sitemap, $template);

            return $template;
        }

    }
}
