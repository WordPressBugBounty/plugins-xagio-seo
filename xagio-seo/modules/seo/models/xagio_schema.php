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
            $xagio_meta    = isset($_POST['meta']) ? array_map('sanitize_text_field', wp_unslash($_POST['meta'])) : NULL;

            $xagio_schemas = [];
            if ($type == 'post') {
                if ($post_id == NULL) {
                    $xagio_schemas = get_option('XAGIO_SEO_SCHEMA_META');
                } else {
                    if (XAGIO_MODEL_SEO::is_homepage($post_id)) {
                        $xagio_schemas = get_option('XAGIO_SEO_SCHEMA_META');
                    } else {
                        $xagio_schemas = get_post_meta($post_id, 'XAGIO_SEO_SCHEMA_META', TRUE);
                        $xagio_schemas = maybe_unserialize($xagio_schemas);
                    }
                }
            } else if ($type == 'term') {

                if (!isset($xagio_meta['XAGIO_SEO_SCHEMA_META'])) {
                    $xagio_schemas = FALSE;
                } else if (empty($xagio_meta['XAGIO_SEO_SCHEMA_META'])) {
                    $xagio_schemas = FALSE;
                } else {
                    $xagio_schemas = $xagio_meta['XAGIO_SEO_SCHEMA_META'];
                }

            }

            xagio_json('success', 'Per page schema.', $xagio_schemas);
        }

        public static function schemaWizard()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['post_id'], $_POST['name'], $_POST['type'], $_POST['swFields']) || (!defined('XAGIO_DOMAIN') || XAGIO_DOMAIN === '')) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $post_id = intval($_POST['post_id']);
            $xagio_name    = sanitize_text_field(wp_unslash($_POST['name']));
            $schema  = map_deep(wp_unslash($_POST['swFields']), 'sanitize_text_field');

            // Finish the schema
            $schema["@context"] = "http://schema.org";
            $schema["@type"]    = sanitize_text_field(wp_unslash($_POST['type']));

            XAGIO_API::apiRequest('schema_wizard', 'POST', [
                'domain'  => XAGIO_DOMAIN,
                'schema'  => base64_encode(serialize($schema)),
                'name'    => $xagio_name,
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

            if (XAGIO_MODEL_SEO::is_homepage($post_id)) {
                $schema = get_option('XAGIO_SEO_SCHEMA_DATA');
            } else {
                $schema  = get_post_meta($post_id, 'XAGIO_SEO_SCHEMA_DATA', TRUE);
            }

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
                $XAGIO_URL                = sanitize_url(wp_unslash($_POST['url']));
                $structuredData_URL = 'https://validator.schema.org/validate';

                $postdata = self::buildQuery([
                    'url' => $XAGIO_URL,
                ]);

                $xagio_args = [
                    'method'  => 'POST',
                    'body'    => $postdata,
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'timeout' => 30,
                ];

                $xagio_response = wp_remote_post($structuredData_URL, $xagio_args);

                if (is_wp_error($xagio_response)) {
                    xagio_json('error', $xagio_response->get_error_message());
                } else {
                    $xagio_result = wp_remote_retrieve_body($xagio_response);

                    $xagio_result = str_replace(")]}'", '', $xagio_result);
                    $xagio_result = str_replace("\n", '', $xagio_result);
                    $xagio_result = json_decode($xagio_result, TRUE);

                    if (!$xagio_result) {
                        xagio_json('error', 'Failed to decode JSON response.');
                    } else {
                        xagio_json('success', 'Done.', $xagio_result);
                    }
                }
            } else {
                xagio_json('error', 'URL is missing from your query.');
            }
        }

        public static function getRemoteRenderedSchemas($ids = [], $xagio_page_id = NULL, $type = 'post', &$xagio_output = NULL)
        {
            $license_email = '';
            $license_key   = '';
            if (!$license_set = XAGIO_LICENSE::isLicenseSet($license_email, $license_key)) {
                return FALSE;
            }

            if (!defined('XAGIO_DOMAIN') || XAGIO_DOMAIN === '') {
                return FALSE;
            }

            // Set the domain name
            $domain = XAGIO_DOMAIN;

            // Set the HTTP Query
            $http_query = [
                'license_email' => $license_email,
                'license_key'   => $license_key,
                'schema_id'     => join(',', $ids),
                'domain'        => $domain,
            ];

            if ($xagio_page_id !== NULL) {
                $http_query['page_id'] = $xagio_page_id;
                $http_query['type']    = $type;
            }

            $xagio_response = wp_remote_post(XAGIO_PANEL_URL . "/api/schema", [
                'user-agent'  => "Xagio - " . XAGIO_CURRENT_VERSION . " ($domain)",
                'timeout'     => 30,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => TRUE,
                'method'      => 'POST',
                'body'        => $http_query,
            ]);

            $xagio_output = $xagio_response;

            if (is_wp_error($xagio_response)) {
                return FALSE;
            } else {
                if (!isset($xagio_response['body'])) {
                    return FALSE;
                } else {
                    $data = json_decode($xagio_response['body'], TRUE);
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

            if (!defined('XAGIO_DOMAIN') || XAGIO_DOMAIN === '') {
                xagio_json('error', 'General Error!');
            }

            // Set the domain name
            $domain = XAGIO_DOMAIN;

            // Set the HTTP Query
            $http_query = [
                'license_email' => $license_email,
                'license_key'   => $license_key,
            ];

            // Build HTTP Query
            $http_query = self::buildQuery($http_query);

            $xagio_response = wp_remote_get(XAGIO_PANEL_URL . "/api/schema_groups?$http_query", [
                'user-agent'  => "Xagio - " . XAGIO_CURRENT_VERSION . " ($domain)",
                'timeout'     => 30,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => TRUE,
            ]);

            if (is_wp_error($xagio_response)) {
                xagio_json('error', 'The license information that you submitted is not valid. Please try again.');
            } else {
                if (!isset($xagio_response['body'])) {
                    xagio_json('error', 'We are experiencing temporary problems with our servers. Please try again later.');
                } else {
                    $data = json_decode($xagio_response['body'], TRUE);
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

            if (!defined('XAGIO_DOMAIN') || XAGIO_DOMAIN === '') {
                xagio_json('error', 'General Error!');
            }

            // Set the domain name
            $domain = XAGIO_DOMAIN;

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

            $xagio_response = wp_remote_get(XAGIO_PANEL_URL . "/api/schema?$http_query", [
                'user-agent'  => "Xagio - " . XAGIO_CURRENT_VERSION . " ($domain)",
                'timeout'     => 30,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => TRUE,
            ]);

            if (is_wp_error($xagio_response)) {
                xagio_json('error', 'The license information that you submitted is not valid. Please try again.');
            } else {
                if (!isset($xagio_response['body'])) {
                    xagio_json('error', 'We are experiencing temporary problems with our servers. Please try again later.');
                } else {

                    $data = json_decode($xagio_response['body'], TRUE);
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
                    $xagio_schemas = get_option('XAGIO_SEO_SCHEMA_META');
                } else {
                    if (XAGIO_MODEL_SEO::is_homepage($post_id)) {
                        $xagio_schemas = get_option('XAGIO_SEO_SCHEMA_META');
                    } else {
                        $xagio_schemas = get_post_meta($post_id, 'XAGIO_SEO_SCHEMA_META', TRUE);
                        $xagio_schemas = maybe_unserialize($xagio_schemas);
                    }
                }
            } else if ($type == 'term') {

                $xagio_schemas = get_term_meta($post_id, 'XAGIO_SEO_SCHEMA_META', true);

            }
            if (!$xagio_schemas) {
                return FALSE;
            } else {
                return $xagio_schemas;
            }
        }

        public static function applyShortcodes($xagio_schemas)
        {
            $new_schemas = [];
            foreach ($xagio_schemas as $schema) {
                $new_schemas[] = self::_applyRecursive($schema);
            }

            return $new_schemas;
        }

        private static function _applyRecursive($schema = [])
        {
            global $post;

            // Resolve a safe post ID for replacements
            $ID = 0;
            if (
                !XAGIO_MODEL_SEO::is_home_posts_page() &&
                !XAGIO_MODEL_SEO::is_posts_page() &&
                !XAGIO_MODEL_SEO::is_home_static_page()
            ) {
                if ($post instanceof \WP_Post) {
                    $ID = (int) $post->ID;
                } else {
                    $maybe_id = function_exists('get_the_ID') ? get_the_ID() : 0;
                    $ID = $maybe_id ? (int) $maybe_id : 0;
                }
            }

            // If it's a scalar/string, just process and return the same type
            if (!is_array($schema) && !is_object($schema)) {
                return (is_scalar($schema) || is_string($schema))
                    ? do_shortcode(XAGIO_MODEL_SEO::replaceVars((string) $schema, $ID))
                    : [];
            }

            // Normalize to array so foreach is safe
            $schema = (array) $schema;

            $new_schema = [];
            foreach ($schema as $xagio_key => $xagio_value) {
                if (is_array($xagio_value) || is_object($xagio_value)) {
                    $new_schema[$xagio_key] = self::_applyRecursive($xagio_value);
                } else {
                    // Replace vars and run shortcodes
                    $new_schema[$xagio_key] = do_shortcode(
                        XAGIO_MODEL_SEO::replaceVars((string) $xagio_value, $ID)
                    );
                }
            }

            return $new_schema;
        }


        public static function applyReviews($xagio_reviews = [], $natural_reviews = FALSE, &$xagio_schemas = [])
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
            if (sizeof($xagio_reviews) > 0 && is_array($xagio_reviews)) {

                // Calculate how much reviews / ratings and their sum value
                $xagioReviewCount = 0;
                $ratingCount = 0;

                // Store all values
                $ratingValues = [];

                // Array to hold all reviews
                $reviewSchemas = [];

                // Loop through all reviews / ratings
                foreach ($xagio_reviews as $review) {

                    $ratingValues[] = $review['rating'];

                    // Fix for old versions
                    if (!isset($review['stars_only'])) {
                        $review['stars_only'] = 0;
                    }

                    if ($review['stars_only'] == 1) {

                        $ratingCount++;

                    } else {

                        $xagioReviewCount++;

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

                for ($xagio_i = 0; $xagio_i < sizeof($xagio_schemas); $xagio_i++) {

                    // If current schema supports reviews
                    if (in_array($xagio_schemas[$xagio_i]['@type'], XAGIO_MODEL_REVIEWS::allowedReviewSchemas()) && sizeof($reviewSchemas) > 0) {

                        $xagio_schemas[$xagio_i]['review'] = $reviewSchemas;

                    }

                    // If reviews should be injected into AggregateRating Schemas
                    if ($natural_reviews == TRUE) {

                        $aggregateRating;

                        if ($xagio_schemas[$xagio_i]['@type'] == 'AggregateRating') {
                            $aggregateRating = &$xagio_schemas[$xagio_i];
                        } else if (isset($xagio_schemas[$xagio_i]['aggregateRating'])) {
                            $aggregateRating = &$xagio_schemas[$xagio_i]['aggregateRating'];
                        }

                        if (($xagioReviewCount > 0 || $ratingCount > 0) && sizeof($ratingValues) > 0) {

                            // I HATE THIS, but it has to be here
                            if ($xagioReviewCount > 0) {
                                $aggregateRating['reviewCount'] = $xagioReviewCount;
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
                            $xagioRatingValue = 0;

                            // Best Rating Calculation
                            $bestRating  = $aggregateRating['bestRating'];
                            $worstRating = $aggregateRating['worstRating'];

                            // Check for Reviews
                            if (isset($xagio_schemas[$xagio_i]['review'])) {
                                if (is_array($xagio_schemas[$xagio_i]['review'])) {

                                    for ($xagio_r = 0; $xagio_r < sizeof($xagio_schemas[$xagio_i]['review']); $xagio_r++) {

                                        $tempRating = $xagio_schemas[$xagio_i]['review'][$xagio_r]['reviewRating']['ratingValue'];
                                        $starTemp   = (($bestRating - $worstRating) / 4);
                                        $tempSum    = ($starTemp * $tempRating) + ($worstRating - $starTemp);

                                        // I don't know why I even try
                                        $xagio_schemas[$xagio_i]['review'][$xagio_r]['reviewRating']['worstRating'] = $worstRating;
                                        $xagio_schemas[$xagio_i]['review'][$xagio_r]['reviewRating']['bestRating']  = $bestRating;
                                        $xagio_schemas[$xagio_i]['review'][$xagio_r]['reviewRating']['ratingValue'] = number_format($tempSum, 2, '.', '');
                                    }
                                }
                            }

                            // Calculate the ratings
                            foreach ($ratingValues as $tempRatingValue) {

                                $starTemp    = (($bestRating - $worstRating) / 4);
                                $tempSum     = ($starTemp * $tempRatingValue) + ($worstRating - $starTemp);
                                $xagioRatingValue += $tempSum;

                            }

                            // Total rating sum
                            $xagioRatingValue = $xagioRatingValue / ($xagioReviewCount + $ratingCount);

                            // Format number
                            $xagioRatingValue = number_format($xagioRatingValue, 2, '.', '');

                            // Set the temp rating value
                            $GLOBALS['xagio_currentRatingValue'] = $xagioRatingValue;

                            // Set the Rating Value
                            $aggregateRating['ratingValue'] = $xagioRatingValue;
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
                $xagio_review = get_option('XAGIO_REVIEW');

                // Homepage Schema
                if (XAGIO_MODEL_SEO::is_homepage()) {

                    $xagio_schemas = get_option('XAGIO_SEO_SCHEMA_DATA');

                    // Taxonomy Schema
                } else if (is_category() || is_tag() || is_tax()) {

                    $xagio_schemas = FALSE;

                    $xagio_object = $GLOBALS['wp_query']->get_queried_object();
                    if (is_object($xagio_object)) {

                        $xagio_schemas = get_term_meta($xagio_object->term_id, 'XAGIO_SEO_SCHEMA_DATA', true);

                    }

                    // Use global taxonomy schema
                    if ($xagio_schemas == FALSE) {
                        // TODO --- need to add global taxonomies
                    }

                    // Post Schema
                } else {
                    $xagio_schemas = get_post_meta($post->ID, 'XAGIO_SEO_SCHEMA_DATA', TRUE);

                    // See if we have XAGIO_FORCE_HOMEPAGE_SCHEMA activated
                    if ($xagio_schemas == FALSE && XAGIO_FORCE_HOMEPAGE_SCHEMA == TRUE) {
                        $xagio_schemas = get_option('XAGIO_SEO_SCHEMA_DATA');
                    }
                }
                if ($xagio_schemas != FALSE) {

                    // Get all Reviews
	                if (($xagio_review['settings']['per_page_reviews'] ?? 0) == 1) {
		                $xagio_reviews = XAGIO_MODEL_REVIEWS::getReviewsForPage($review_page_id, NULL);
                    } else {
                        $xagio_reviews = XAGIO_MODEL_REVIEWS::getReviewsGlobal(NULL);
                    }

                    // Before applying reviews
                    if (is_string($xagio_schemas)) {
                        $xagio_schemas = maybe_unserialize($xagio_schemas);
                    }

                    // Apply the reviews
	                self::applyReviews($xagio_reviews, ($xagio_review['settings']['natural_reviews'] ?? 0) == 1, $xagio_schemas);

	                $xagio_schemas = maybe_unserialize($xagio_schemas);

                    // Apply shortcodes and templates to schemas
                    $xagio_schemas = self::applyShortcodes($xagio_schemas);

                    if (is_array($xagio_schemas) && sizeof($xagio_schemas) == 1) {
                        $xagio_schemas = $xagio_schemas[0];
                    }

                    // Check if we should render pretty schemas
                    $PRETTY_SCHEMAS = 0;
                    if (XAGIO_RENDER_PRETTY_SCHEMAS == TRUE) {
                        $PRETTY_SCHEMAS = 128;
                    }

                    $generatedSchema = wp_json_encode($xagio_schemas, $PRETTY_SCHEMAS);

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