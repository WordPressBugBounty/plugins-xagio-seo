<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_SCHEMA')) {

    class XAGIO_MODEL_SCHEMA
    {

        private static function defines()
        {
            define('XAGIO_FORCE_HOMEPAGE_SCHEMA', filter_var(get_option('XAGIO_FORCE_HOMEPAGE_SCHEMA'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_RENDER_PRETTY_SCHEMAS', filter_var(get_option('XAGIO_RENDER_PRETTY_SCHEMAS'), FILTER_VALIDATE_BOOLEAN));
        }

        public static function initialize()
        {
            XAGIO_MODEL_SCHEMA::defines();

            add_action('wp_head', [
                'XAGIO_MODEL_SCHEMA',
                'generateSchema'
            ]);

            if (!XAGIO_HAS_ADMIN_PERMISSIONS)
                return;

            add_action('admin_post_xagio_validate_schema', [
                'XAGIO_MODEL_SCHEMA',
                'validateSchema'
            ]);
            add_action('admin_post_xagio_render_schema', [
                'XAGIO_MODEL_SCHEMA',
                'renderSchema'
            ]);
            add_action('admin_post_xagio_get_remote_schema', [
                'XAGIO_MODEL_SCHEMA',
                'getRemoteSchema'
            ]);
            add_action('admin_post_xagio_get_remote_schema_groups', [
                'XAGIO_MODEL_SCHEMA',
                'getRemoteSchemaGroups'
            ]);
            add_action('admin_post_xagio_save_schema', [
                'XAGIO_MODEL_SCHEMA',
                'saveSchema'
            ]);
            add_action('admin_post_xagio_schema_wizard', [
                'XAGIO_MODEL_SCHEMA',
                'schemaWizard'
            ]);
            add_action('admin_post_xagio_get_schemas', [
                'XAGIO_MODEL_SCHEMA',
                'getPageSchemas'
            ]);
        }

        public static function getPageSchemas()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['post_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $post_id = intval($_POST['post_id']);
            $type    = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : 'post';
            $meta    = isset($_POST['meta']) ? array_map('sanitize_text_field', wp_unslash($_POST['meta'])) : NULL;

            $schemas = [];
            if ($type == 'post') {
                if ($post_id == NULL) {
                    $schemas = get_option('XAGIO_SEO_SCHEMA_META');
                } else {
                    $schemas = get_post_meta($post_id, 'XAGIO_SEO_SCHEMA_META', TRUE);
                    $schemas = maybe_unserialize($schemas);
                }
            } else if ($type == 'term') {

                if (!isset($meta['XAGIO_SEO_SCHEMA_META'])) {
                    $schemas = FALSE;
                } else if (empty($meta['XAGIO_SEO_SCHEMA_META'])) {
                    $schemas = FALSE;
                } else {
                    $schemas = $meta['XAGIO_SEO_SCHEMA_META'];
                }

            }

            xagio_json('success', 'Per page schema.', $schemas);
        }

        public static function schemaWizard()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['post_id'], $_POST['name'], $_POST['type'], $_POST['swFields'], $_SERVER['SERVER_NAME'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $post_id = intval($_POST['post_id']);
            $name    = sanitize_text_field(wp_unslash($_POST['name']));
            $schema  = map_deep(wp_unslash($_POST['swFields']), 'sanitize_text_field');

            // Finish the schema
            $schema["@context"] = "http://schema.org";
            $schema["@type"]    = sanitize_text_field(wp_unslash($_POST['type']));

            XAGIO_API::apiRequest('schema_wizard', 'POST', [
                'domain'  => preg_replace('/^www\./', '', sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME']))),
                'schema'  => base64_encode(serialize($schema)),
                'name'    => $name,
                'post_id' => $post_id,
            ]);

            xagio_json('success', 'Schema has been generated and assigned to this page/post. Refreshing this window in order for you to see the changes.', $schema);
        }

        public static function renderSchema()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $post_id = intval($_POST['id']);
            $schema  = get_post_meta($post_id, 'XAGIO_SEO_SCHEMA_DATA', TRUE);

            if ($schema === FALSE || empty($schema)) {
                xagio_json('error', 'Schema is not assigned for this page/post. Please save your page/post changes and try again.');
            } else {
                xagio_json('success', 'Schema rendered.', $schema);
            }
        }

        public static function validateSchema()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['url'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            if (isset($_POST['url'])) {
                $URL                = sanitize_url(wp_unslash($_POST['url']));
                $structuredData_URL = 'https://validator.schema.org/validate';

                $postdata = self::buildQuery([
                    'url' => $URL,
                ]);

                $args = [
                    'method'  => 'POST',
                    'body'    => $postdata,
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                ];

                $response = wp_remote_post($structuredData_URL, $args);

                if (is_wp_error($response)) {
                    xagio_json('error', $response->get_error_message());
                } else {
                    $result = wp_remote_retrieve_body($response);

                    $result = str_replace(")]}'", '', $result);
                    $result = str_replace("\n", '', $result);
                    $result = json_decode($result, TRUE);

                    if (!$result) {
                        xagio_json('error', 'Failed to decode JSON response.');
                    } else {
                        xagio_json('success', 'Done.', $result);
                    }
                }
            } else {
                xagio_json('error', 'URL is missing from your query.');
            }
        }

        public static function getRemoteRenderedSchemas($ids = [], $page_id = NULL, $type = 'post', &$output = NULL)
        {
            $license_email = '';
            $license_key   = '';
            if (!$license_set = XAGIO_LICENSE::isLicenseSet($license_email, $license_key)) {
                return FALSE;
            }

            if (!isset($_SERVER['SERVER_NAME'])) {
                return FALSE;
            }

            // Set the domain name
            $domain = preg_replace('/^www\./', '', sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])));

            // Set the HTTP Query
            $http_query = [
                'license_email' => $license_email,
                'license_key'   => $license_key,
                'schema_id'     => join(',', $ids),
                'domain'        => $domain,
            ];

            if ($page_id !== NULL) {
                $http_query['page_id'] = $page_id;
                $http_query['type']    = $type;
            }

            $response = wp_remote_post(XAGIO_PANEL_URL . "/api/schema", [
                'user-agent'  => "Xagio - " . XAGIO_CURRENT_VERSION . " ($domain)",
                'timeout'     => 30,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => TRUE,
                'method'      => 'POST',
                'body'        => $http_query,
            ]);

            $output = $response;

            if (is_wp_error($response)) {
                return FALSE;
            } else {
                if (!isset($response['body'])) {
                    return FALSE;
                } else {
                    $data = json_decode($response['body'], TRUE);
                    if (!$data) {
                        return FALSE;
                    } else {
                        return $data;
                    }
                }
            }
        }

        public static function getRemoteSchemaGroups()
        {
            $license_email = '';
            $license_key   = '';
            if (!$license_set = XAGIO_LICENSE::isLicenseSet($license_email, $license_key)) {
                xagio_json('success', 'Your license is invalid. Please go to Panel and troubleshoot this issue.', []);
            }

            if (!isset($_SERVER['SERVER_NAME'])) {
                xagio_json('error', 'General Error!');
            }

            // Set the domain name
            $domain = preg_replace('/^www\./', '', sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])));

            // Set the HTTP Query
            $http_query = [
                'license_email' => $license_email,
                'license_key'   => $license_key,
            ];

            // Build HTTP Query
            $http_query = self::buildQuery($http_query);

            $response = wp_remote_get(XAGIO_PANEL_URL . "/api/schema_groups?$http_query", [
                'user-agent'  => "Xagio - " . XAGIO_CURRENT_VERSION . " ($domain)",
                'timeout'     => 30,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => TRUE,
            ]);

            if (is_wp_error($response)) {
                xagio_json('error', 'The license information that you submitted is not valid. Please try again.');
            } else {
                if (!isset($response['body'])) {
                    xagio_json('error', 'We are experiencing temporary problems with our servers. Please try again later.');
                } else {
                    $data = json_decode($response['body'], TRUE);
                    if (!$data) {
                        xagio_json('error', 'Failed to decode JSON response!');
                    } else {
                        header('Content-Type: application/json');
                        echo wp_json_encode($data);
                    }
                }
            }
        }

        public static function getRemoteSchema()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $license_email = '';
            $license_key   = '';
            if (!$license_set = XAGIO_LICENSE::isLicenseSet($license_email, $license_key)) {
                xagio_json('success', 'Your license is invalid. Please go to Panel and troubleshoot this issue.', []);
            }

            if (!isset($_SERVER['SERVER_NAME'])) {
                xagio_json('error', 'General Error!');
            }

            // Set the domain name
            $domain = preg_replace('/^www\./', '', sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])));

            // Set the HTTP Query
            $http_query = [
                'license_email' => $license_email,
                'license_key'   => $license_key,
            ];

            if (isset($_POST['schema_group'])) {
                $http_query['schema_group'] = sanitize_text_field(wp_unslash($_POST['schema_group']));
            }

            // Build HTTP Query
            $http_query = self::buildQuery($http_query);

            $response = wp_remote_get(XAGIO_PANEL_URL . "/api/schema?$http_query", [
                'user-agent'  => "Xagio - " . XAGIO_CURRENT_VERSION . " ($domain)",
                'timeout'     => 30,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => TRUE,
            ]);

            if (is_wp_error($response)) {
                xagio_json('error', 'The license information that you submitted is not valid. Please try again.');
            } else {
                if (!isset($response['body'])) {
                    xagio_json('error', 'We are experiencing temporary problems with our servers. Please try again later.');
                } else {

                    $data = json_decode($response['body'], TRUE);
                    if (!$data) {
                        xagio_json('error', 'Failed to decode JSON response!');
                    } else {
                        header('Content-Type: application/json');
                        echo wp_json_encode($data);
                    }
                }
            }
        }

        public static function buildQuery($params)
        {
            return http_build_query($params, '', '&');
        }

        public static function getSchemas($post_id = NULL, $type = 'post')
        {
            if ($type == 'post') {
                if ($post_id == NULL) {
                    $schemas = get_option('XAGIO_SEO_SCHEMA_META');
                } else {
                    if ($post_id == get_option('page_on_front')) {
                        $schemas = get_option('XAGIO_SEO_SCHEMA_META');
                    } else {
                        $schemas = get_post_meta($post_id, 'XAGIO_SEO_SCHEMA_META', TRUE);
                        $schemas = maybe_unserialize($schemas);
                    }
                }
            } else if ($type == 'term') {

                $schemas = get_term_meta($post_id, 'XAGIO_SEO_SCHEMA_META', true);

            }
            if (!$schemas) {
                return FALSE;
            } else {
                return $schemas;
            }
        }

        public static function applyShortcodes($schemas)
        {
            $new_schemas = [];
            foreach ($schemas as $schema) {
                $new_schemas[] = self::_applyRecursive($schema);
            }

            return $new_schemas;
        }

        private static function _applyRecursive($schema = [])
        {
            global $post;
            $ID = NULL;
            if (XAGIO_MODEL_SEO::is_home_posts_page() || XAGIO_MODEL_SEO::is_posts_page() || XAGIO_MODEL_SEO::is_home_static_page()) {
                $ID = 0;
            } else {
                $ID = $post->ID;
            }

            $new_schema = [];
            foreach ($schema as $key => $value) {
                if (is_array($value)) {
                    unset($schema[$key]);
                    $new_schema[$key] = self::_applyRecursive($value);
                } else {
                    // Replace :amp; for & as the & would split into different vars.
                    $new_schema[$key] = do_shortcode(XAGIO_MODEL_SEO::replaceVars($value, $ID));
                    unset($schema[$key]);
                }
            }

            return $new_schema;
        }

        public static function applyReviews($reviews = [], $natural_reviews = FALSE, &$schemas = [])
        {

            // Check if feature is enabled
            $XAGIO_FEATURES = get_option('XAGIO_FEATURES');
            if ($XAGIO_FEATURES != FALSE && is_array($XAGIO_FEATURES)) {
                if (!in_array('reviews', $XAGIO_FEATURES)) {
                    return;
                }
            }
            if ($XAGIO_FEATURES == 'none')
                return;

            // Loop through all Schemas and inject Ratings if there are reviews
            if (sizeof($reviews) > 0 && is_array($reviews)) {

                // Calculate how much reviews / ratings and their sum value
                $reviewCount = 0;
                $ratingCount = 0;

                // Store all values
                $ratingValues = [];

                // Array to hold all reviews
                $reviewSchemas = [];

                // Loop through all reviews / ratings
                foreach ($reviews as $review) {

                    $ratingValues[] = $review['rating'];

                    // Fix for old versions
                    if (!isset($review['stars_only'])) {
                        $review['stars_only'] = 0;
                    }

                    if ($review['stars_only'] == 1) {

                        $ratingCount++;

                    } else {

                        $reviewCount++;

                        $reviewSchemas[] = [
                            '@type'         => 'Review',
                            'author'        => $review['name'],
                            'datePublished' => $review['date'],
                            'description'   => $review['review'],
                            'reviewRating'  => [
                                '@type'       => 'Rating',
                                'ratingValue' => $review['rating'],
                            ],
                        ];

                    }
                }

                for ($i = 0; $i < sizeof($schemas); $i++) {

                    // If current schema supports reviews
                    if (in_array($schemas[$i]['@type'], XAGIO_MODEL_REVIEWS::allowedReviewSchemas()) && sizeof($reviewSchemas) > 0) {

                        $schemas[$i]['review'] = $reviewSchemas;

                    }

                    // If reviews should be injected into AggregateRating Schemas
                    if ($natural_reviews == TRUE) {

                        $aggregateRating;

                        if ($schemas[$i]['@type'] == 'AggregateRating') {
                            $aggregateRating = &$schemas[$i];
                        } else if (isset($schemas[$i]['aggregateRating'])) {
                            $aggregateRating = &$schemas[$i]['aggregateRating'];
                        }

                        if (($reviewCount > 0 || $ratingCount > 0) && sizeof($ratingValues) > 0) {

                            // I HATE THIS, but it has to be here
                            if ($reviewCount > 0) {
                                $aggregateRating['reviewCount'] = $reviewCount;
                            } else {
                                unset($aggregateRating['reviewCount']);
                            }
                            if ($ratingCount > 0) {
                                $aggregateRating['ratingCount'] = $ratingCount;
                            } else {
                                unset($aggregateRating['ratingCount']);
                            }

                            if (!isset($aggregateRating['bestRating'])) {
                                $aggregateRating['bestRating'] = 5;
                            }

                            if (empty($aggregateRating['worstRating'])) {
                                $aggregateRating['worstRating'] = 1;
                            }

                            // Current Rating Value
                            $ratingValue = 0;

                            // Best Rating Calculation
                            $bestRating  = $aggregateRating['bestRating'];
                            $worstRating = $aggregateRating['worstRating'];

                            // Check for Reviews
                            if (isset($schemas[$i]['review'])) {
                                if (is_array($schemas[$i]['review'])) {

                                    for ($r = 0; $r < sizeof($schemas[$i]['review']); $r++) {

                                        $tempRating = $schemas[$i]['review'][$r]['reviewRating']['ratingValue'];
                                        $starTemp   = (($bestRating - $worstRating) / 4);
                                        $tempSum    = ($starTemp * $tempRating) + ($worstRating - $starTemp);

                                        // I don't know why I even try
                                        $schemas[$i]['review'][$r]['reviewRating']['worstRating'] = $worstRating;
                                        $schemas[$i]['review'][$r]['reviewRating']['bestRating']  = $bestRating;
                                        $schemas[$i]['review'][$r]['reviewRating']['ratingValue'] = number_format($tempSum, 2, '.', '');
                                    }
                                }
                            }

                            // Calculate the ratings
                            foreach ($ratingValues as $tempRatingValue) {

                                $starTemp    = (($bestRating - $worstRating) / 4);
                                $tempSum     = ($starTemp * $tempRatingValue) + ($worstRating - $starTemp);
                                $ratingValue += $tempSum;

                            }

                            // Total rating sum
                            $ratingValue = $ratingValue / ($reviewCount + $ratingCount);

                            // Format number
                            $ratingValue = number_format($ratingValue, 2, '.', '');

                            // Set the temp rating value
                            $GLOBALS['xagio_currentRatingValue'] = $ratingValue;

                            // Set the Rating Value
                            $aggregateRating['ratingValue'] = $ratingValue;
                        }

                    }
                }
            }

        }

        public static function generateSchema()
        {
            global $post;

            if (!empty($post->ID)) {

                $review_page_id = $post->ID;

                // If meta does not exist SEO SEARCH is turned on by default
                if (metadata_exists('post', $post->ID, 'XAGIO_SEO_SCHEMA_ENABLE')) {
                    // If metadata exists we are checking if it's empty string(TURNED OFF) or 1(TUNED ON)
                    $XAGIO_SEO_SCRIPTS_ENABLE = get_post_meta($post->ID, 'XAGIO_SEO_SCHEMA_ENABLE', TRUE);
                    if ($XAGIO_SEO_SCRIPTS_ENABLE === "") {
                        return FALSE;
                    }
                }

                // Review Settings
                $ps_review = get_option('XAGIO_REVIEW');

                // Homepage Schema
                if (XAGIO_MODEL_SEO::is_home_posts_page() || XAGIO_MODEL_SEO::is_posts_page() || XAGIO_MODEL_SEO::is_home_static_page()) {
                    $schemas = get_option('XAGIO_SEO_SCHEMA_DATA');

                    if (empty($schemas)) {
                        $schemas = get_post_meta($post->ID, 'XAGIO_SEO_SCHEMA_DATA', TRUE);
                    }

                    // Taxonomy Schema
                } else if (is_category() || is_tag() || is_tax()) {

                    $schemas = FALSE;

                    $object = $GLOBALS['wp_query']->get_queried_object();
                    if (is_object($object)) {

                        $schemas = get_term_meta($object->term_id, 'XAGIO_SEO_SCHEMA_DATA', true);

                    }

                    // Use global taxonomy schema
                    if ($schemas == FALSE) {
                        // TODO --- need to add global taxonomies
                    }

                    // Post Schema
                } else {
                    $schemas = get_post_meta($post->ID, 'XAGIO_SEO_SCHEMA_DATA', TRUE);

                    // See if we have XAGIO_FORCE_HOMEPAGE_SCHEMA activated
                    if ($schemas == FALSE && XAGIO_FORCE_HOMEPAGE_SCHEMA == TRUE) {
                        $schemas = get_option('XAGIO_SEO_SCHEMA_DATA');
                    }
                }
                if ($schemas != FALSE) {

                    // Get all Reviews
                    if (@$ps_review['settings']['per_page_reviews'] == 1) {
                        $reviews = XAGIO_MODEL_REVIEWS::getReviewsForPage($review_page_id, NULL);
                    } else {
                        $reviews = XAGIO_MODEL_REVIEWS::getReviewsGlobal(NULL);
                    }

                    // Apply the reviews
                    self::applyReviews($reviews, @$ps_review['settings']['natural_reviews'] == 1, $schemas);

                    $schemas = maybe_unserialize($schemas);

                    // Apply shortcodes and templates to schemas
                    $schemas = self::applyShortcodes($schemas);

                    if (is_array($schemas) && sizeof($schemas) == 1) {
                        $schemas = $schemas[0];
                    }

                    // Check if we should render pretty schemas
                    $PRETTY_SCHEMAS = 0;
                    if (XAGIO_RENDER_PRETTY_SCHEMAS == TRUE) {
                        $PRETTY_SCHEMAS = 128;
                    }

                    $generatedSchema = wp_json_encode($schemas, $PRETTY_SCHEMAS);

                    // Replace \\ with \
                    $generatedSchema = str_replace("\\\\", "", $generatedSchema);

                    if (XAGIO_DISABLE_HTML_FOOTPRINT == FALSE) {
                        echo "\n\n<!-- xagio – Schema.org -->\n";
                    }

                    echo wp_kses("<script type=\"application/ld+json\">\n" . $generatedSchema . "\n</script>", [
                        'script' => [
                            'type' => ['application/ld+json']
                        ],
                        'post'
                    ]);

                    if (XAGIO_DISABLE_HTML_FOOTPRINT == FALSE) {
                        echo "\n<!-- xagio – Schema.org -->\n\n";
                    }

                }

            }
        }
    }

}