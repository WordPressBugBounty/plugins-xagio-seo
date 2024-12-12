<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_PROJECTS')) {

    class XAGIO_MODEL_PROJECTS
    {

        public static function initialize()
        {
            if (!XAGIO_HAS_ADMIN_PERMISSIONS)
                return;

            add_action('admin_post_xagio_get_top_ten', [
                'XAGIO_MODEL_PROJECTS',
                'getTopTenDomains'
            ]);
            add_action('admin_post_xagio_generate_audit', [
                'XAGIO_MODEL_PROJECTS',
                'generateAudit'
            ]);
            add_action('admin_post_xagio_generate_seed', [
                'XAGIO_MODEL_PROJECTS',
                'generateSeedGroup'
            ]);
            add_action('admin_post_xagio_generate_phrasematch', [
                'XAGIO_MODEL_PROJECTS',
                'generatePhraseMatch'
            ]);
            add_action('admin_post_xagio_preview_phrasematch', [
                'XAGIO_MODEL_PROJECTS',
                'previewPhraseMatch'
            ]);
            add_action('admin_post_xagio_combine_projects', [
                'XAGIO_MODEL_PROJECTS',
                'combineProjects'
            ]);
            add_action('admin_post_xagio_get_projects', [
                'XAGIO_MODEL_PROJECTS',
                'getProjects'
            ]);
            add_action('admin_post_xagio_get_groups', [
                'XAGIO_MODEL_PROJECTS',
                'getGroups'
            ]);
            add_action('admin_post_xagio_get_alert_project_id', [
                'XAGIO_MODEL_PROJECTS',
                'getAlertProjectID'
            ]);
            add_action('admin_post_xagio_remove_alert_project_id', [
                'XAGIO_MODEL_PROJECTS',
                'removeAlertProjectID'
            ]);

            add_action('admin_post_xagio_new_project', [
                'XAGIO_MODEL_PROJECTS',
                'newProject'
            ]);
            add_action('admin_post_xagio_rename_project', [
                'XAGIO_MODEL_PROJECTS',
                'renameProject'
            ]);
            add_action('admin_post_xagio_remove_project', [
                'XAGIO_MODEL_PROJECTS',
                'removeProject'
            ]);
            add_action('admin_post_xagio_duplicate_project', [
                'XAGIO_MODEL_PROJECTS',
                'duplicateProject'
            ]);
            add_action('admin_post_xagio_export_project', [
                'XAGIO_MODEL_PROJECTS',
                'exportProject'
            ]);
            add_action('admin_post_xagio_export_projects', [
                'XAGIO_MODEL_PROJECTS',
                'exportProjects'
            ]);
            add_action('admin_post_xagio_import_project', [
                'XAGIO_MODEL_PROJECTS',
                'importProject'
            ]);
            add_action('admin_post_xagio_import_kws', [
                'XAGIO_MODEL_PROJECTS',
                'importKWS'
            ]);

            add_action('admin_post_xagio_create_page_post', [
                'XAGIO_MODEL_PROJECTS',
                'createPagePost'
            ]);

            add_action('admin_post_xagio_get_page_post_parent', [
                'XAGIO_MODEL_PROJECTS',
                'getPagePostParent'
            ]);
            add_action('admin_post_xagio_get_page_post_status', [
                'XAGIO_MODEL_PROJECTS',
                'getPagePostStatus'
            ]);

            add_action('admin_post_xagio_update_page_parent', [
                'XAGIO_MODEL_PROJECTS',
                'updatePageParent'
            ]);
            add_action('admin_post_xagio_update_page_post_status', [
                'XAGIO_MODEL_PROJECTS',
                'updatePagePostStatus'
            ]);

            add_action('admin_post_xagio_make_groups', [
                'XAGIO_MODEL_PROJECTS',
                'makeGroups'
            ]);
            add_action('admin_post_xagio_make_groups_from_taxonomies', [
                'XAGIO_MODEL_PROJECTS',
                'makeGroupsFromTaxonomies'
            ]);

            add_action('admin_post_xagio_get_posts', [
                'XAGIO_MODEL_PROJECTS',
                'getPosts'
            ]);
            add_action('admin_post_xagio_get_taxonomies', [
                'XAGIO_MODEL_PROJECTS',
                'getTaxonomies'
            ]);
            add_action('admin_post_xagio_get_post_types', [
                'XAGIO_MODEL_PROJECTS',
                'getPostTypes'
            ]);
            add_action('admin_post_xagio_get_taxonomy_types', [
                'XAGIO_MODEL_PROJECTS',
                'getTaxonomyTypes'
            ]);
            add_action('admin_post_xagio_get_tags_categories', [
                'XAGIO_MODEL_PROJECTS',
                'getTagsCategories'
            ]);

            add_action('admin_post_xagio_detach_from_group', [
                'XAGIO_MODEL_PROJECTS',
                'detachFromGroup'
            ]);
            add_action('admin_post_xagio_attach_to_project_group', [
                'XAGIO_MODEL_PROJECTS',
                'attachToProjectGroup'
            ]);
            add_action('admin_post_xagio_attach_to_page_post', [
                'XAGIO_MODEL_PROJECTS',
                'attachToPagePost'
            ]);
            add_action('admin_post_xagio_attach_to_taxonomy', [
                'XAGIO_MODEL_PROJECTS',
                'attachToTaxonomy'
            ]);

            add_action('admin_post_xagio_share_project', [
                'XAGIO_MODEL_PROJECTS',
                'shareProject'
            ]);
            add_action('admin_post_xagio_remove_sharing', [
                'XAGIO_MODEL_PROJECTS',
                'unshareProject'
            ]);

            add_action('admin_post_xagio_get_project_info', [
                'XAGIO_MODEL_PROJECTS',
                'getProjectInfo'
            ]);
        }

        public static function getProjectInfo()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['project_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $project_id = intval($_POST['project_id']);
            $results    = $wpdb->get_row($wpdb->prepare("SELECT id,project_name FROM xag_projects WHERE id = %d", $project_id), ARRAY_A);

            if (!$results) {
                xagio_json('error', 'Project not found!');
            }

            xagio_json('success', 'Received project info', [
                'name' => $results['project_name'],
                'id'   => $results['id']
            ]);
        }

        //--------------------------------------------
        //
        //             MySQL Operations
        //
        //--------------------------------------------


        public static function createTable()
        {
            global $wpdb;
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $charset_collate = $wpdb->get_charset_collate();
            $creation_query  = 'CREATE TABLE xag_projects (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `project_name` varchar(255),
                    `status` varchar(255),
                    `shared` varchar(255),
                    `date_created` datetime,
                    PRIMARY KEY  (`id`)
                ) ' . $charset_collate . ';';
            @dbDelta($creation_query);
        }

        public static function removeTable()
        {
            global $wpdb;
            $wpdb->query('DROP TABLE IF EXISTS xag_projects;');
        }

        public static function getTopTenDomains()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['main-keyword']) || !isset($_POST['location']) || !isset($_POST['keyword']) || !isset($_POST['search_engine']) || !isset($_POST['search_engine_text']) || !isset($_POST['search_location']) || !isset($_POST['search_location_text'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $main_keyword         = sanitize_text_field(wp_unslash($_POST['main-keyword']));
            $location             = sanitize_text_field(wp_unslash($_POST['location']));
            $keyword              = sanitize_text_field(wp_unslash($_POST['keyword']));
            $search_engine        = sanitize_text_field(wp_unslash($_POST['search_engine']));
            $search_engine_text   = sanitize_text_field(wp_unslash($_POST['search_engine_text']));
            $search_location      = sanitize_text_field(wp_unslash($_POST['search_location']));
            $search_location_text = sanitize_text_field(wp_unslash($_POST['search_location_text']));

            $listings = [
                "facebook.com",
                "google.com",
                "facebook.com",
                "instagram.com",
                "linkedin.com",
                "apple.com",
                "yelp.com",
                "bing.com",
                "bbb.org",
                "mapquest.com",
                "foursquare.com",
                "angi.com",
                "thumbtack.com",
                "yellowpages.com",
                "nextdoor.com",
                "manta.com",
                "merchantcircle.com",
                "yellowbook.com",
                "chamberofcommerce.com",
                "dandb.com",
                "brownbook.net",
                "partners.local.com",
                "turbify.com",
                "dashboard.ezlocal.com",
                "elocal.com",
                "ebusinesspages.com",
                "citysquares.com",
                "local.botw.org",
                "ibegin.com",
                "neustarlocaleze.biz",
                "spoke.com",
                "golocal247.com",
                "callupcontact.com",
                "n49.com",
                "cybo.com",
                "directory.justlanded.com",
                "tuugo.us",
                "lacartes.com",
                "citylocalpro.com",
                "yellow.place",
                "hub.biz",
                "cylex.us.com",
                "fyple.com",
                "opendi.us",
                "expressbusinessdirectory.com",
                "myhuckleberry.com",
                "bizhwy.com",
                "dirjournal.com",
                "usdirectory.com",
                "finduslocal.com"
            ];

            update_option('XAGIO_ONBOARDING_LOCATION', $location);
            update_option('XAGIO_ONBOARDING_KEYWORD', $keyword);
            update_option('XAGIO_ONBOARDING_MAIN_KEYWORD', $main_keyword);
            update_option('XAGIO_AI_WIZARD_TOP_TEN', serialize([
                'search_engine'        => $search_engine,
                'search_engine_text'   => $search_engine_text,
                'search_location'      => $search_location,
                'search_location_text' => $search_location_text
            ]));

            $http_code = 0;
            $result    = self::apiRequest(
                $endpoint = 'find_top_ten_domains_free', $method = 'POST', [
                'keyword' => $main_keyword,
                'se_id'   => $search_engine ?? '14',
                'loc_id'  => $search_location ?? '2840',
                'ip'      => XAGIO_MODEL_LOG404::getIp()
            ], $http_code
            );

            if ($http_code == 403) {
                xagio_json('error', $result['message'] ?? "Oups, something happened, please contact support.");
            }

            if (isset($result['data']) && sizeof($result['data']) > 0) {
                $websites = $result['data'];
                $OUT      = [];

                foreach ($websites as $website) {
                    $path                   = wp_parse_url($website['url'], PHP_URL_PATH);
                    $website['recommended'] = false;
                    if (is_null($path)) {
                        $website['recommended'] = true;
                    } else {
                        if (strlen($path) <= 1) {
                            $website['recommended'] = true;
                        }
                    }

                    $website['host'] = wp_parse_url($website['url'], PHP_URL_HOST);

                    $website['listing'] = false;

                    $host_check = str_replace('www.', '', $website['host']);
                    if (in_array($host_check, $listings)) {
                        $website['listing'] = true;
                    }

                    $OUT[] = $website;
                }

                xagio_jsonc([
                    'status' => 'success',
                    'data'   => $OUT
                ]);
            }

        }

        public static function generateAudit()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['type']) || !isset($_POST['website']) || !isset($_POST['keyword_contain']) || !isset($_POST['keyword_contain_text']) || !isset($_POST['is_relative']) || !isset($_POST['lang']) || !isset($_POST['lang_code'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $type                 = sanitize_text_field(wp_unslash($_POST['type']));
            $website              = sanitize_text_field(wp_unslash($_POST['website']));
            $keyword_contain      = sanitize_text_field(wp_unslash($_POST['keyword_contain'])); // 0 or 1
            $keyword_contain_text = sanitize_text_field(wp_unslash($_POST['keyword_contain_text']));
            $is_relative          = sanitize_text_field(wp_unslash($_POST['is_relative'])); // 0 or 1

            $projectName = get_option('xagio_ONBOARDING_MAIN_KEYWORD');

            $lang      = sanitize_text_field(wp_unslash($_POST['lang'])) ?? 'en';
            $lang_code = sanitize_text_field(wp_unslash($_POST['lang_code'])) ?? 'US';

            update_option("xagio_ai_wizard_audit_lang_code", $lang);
            update_option("xagio_ai_wizard_audit_lang_code_code", $lang_code);
            update_option("xagio_ai_wizard_contain_keyword_text", $keyword_contain_text);
            update_option("xagio_ai_wizard_contain_keyword", $keyword_contain);
            update_option("xagio_ai_wizard_is_relative", $is_relative);

            $wp_parse_url = wp_parse_url($website);
            $website      = $wp_parse_url['host'];
            $website      = str_replace("www.", "", $website);
            $path         = $wp_parse_url['path'] ?? '/';

            $filters = [
                'keyword_contain' => $keyword_contain,
                'keyword'         => $keyword_contain_text
            ];

            if ($path !== '/') {
                $filters['is_relative'] = 1;
                $filters['path']        = $path;
            }


            $dateCreated = gmdate('Y-m-d H:i:s');
            $wpdb->insert('xag_projects', [
                'project_name' => "AI WIZARD - $projectName",
                'date_created' => $dateCreated,
            ]);
            $insert_id = $wpdb->insert_id;


            $domain       = $website;
            $limit        = 1000;
            $skip_empty   = 'on';
            $project_id   = $insert_id;
            $ignore_local = 'on';


            $groups   = [];
            $keywords = [];

            // Ignore local pages
            if ($ignore_local !== 'on') {
                // Find all Posts & Pages
                $args  = [
                    'posts_per_page' => -1,
                    'orderby'        => 'ID',
                    'order'          => 'ASC',
                    'post_type'      => [
                        'post',
                        'page',
                    ],
                    'post_status'    => [
                        'publish',
                    ],
                ];
                $posts = get_posts($args);

                // The Loop
                foreach ($posts as $post) {
                    $local_relative_path = XAGIO_MODEL_SEO::extract_url($post->ID);
                    $group_name          = $post->post_title;
                    if ($local_relative_path === "/") {
                        $group_name = "1. HOMEPAGE " . $group_name;
                    }

                    $wpdb->insert('xag_groups', [
                        'group_name'   => $group_name,
                        'h1'           => $post->post_title,
                        'project_id'   => $project_id,
                        'date_created' => gmdate('Y-m-d H:i:s'),
                        'id_page_post' => $post->ID,
                        'url'          => $local_relative_path,
                        'title'        => get_post_meta($post->ID, 'XAGIO_SEO_TITLE', TRUE),
                        'description'  => get_post_meta($post->ID, 'XAGIO_SEO_DESCRIPTION', TRUE),
                    ]);

                    $groups[] = $wpdb->insert_id;

                }
            }

            $http_code = 0;
            $result    = XAGIO_API::apiRequest(
                $endpoint = 'find_ranked_keywords', $method = 'POST', [
                'domain'    => $domain,
                'lang'      => $lang,
                'lang_code' => $lang_code,
                'limit'     => $limit,
                'filters'   => $filters,
                'type'      => $type
            ], $http_code
            );

            $domain = preg_replace('/^(?!https?:\/\/)/', 'http://', $domain);
            $domain = wp_parse_url($domain);
            $domain = str_replace("www.", "", $domain['host']);

            $local_domain = wp_parse_url(admin_url());
            $local_domain = $local_domain['host'];
            $local_domain = str_replace('www.', '', $local_domain);

            if ($http_code == 200) {

                if (is_array($result['data']['ranked'])) {
                    foreach ($result['data']['ranked'] as $row) {
                        // Try to get a group
                        $group = $wpdb->get_row(
                            $wpdb->prepare("SELECT * FROM xag_groups WHERE url = %s AND project_id = %d", $row['relative_url'], $project_id), ARRAY_A
                        );
                        if ($group == FALSE) {
                            $group_name = $row['title'];
                            if ($row['relative_url'] === "/") {
                                $group_name = "1. HOMEPAGE " . $row['title'];
                            }

                            $wpdb->insert('xag_groups', [
                                'group_name'      => $group_name,
                                'h1'              => $row['title'],
                                'title'           => $row['title'],
                                'description'     => $row['snippet'],
                                'project_id'      => $project_id,
                                'date_created'    => gmdate('Y-m-d H:i:s'),
                                'id_page_post'    => 0,
                                'url'             => str_replace(" ", "-", $row['relative_url']),
                                'external_domain' => ($domain != $local_domain) ? $domain : '',
                            ]);

                            $group    = $wpdb->insert_id;
                            $groups[] = $group;

                        } else {
                            $group    = $group['id'];
                            $groups[] = $group;
                        }

                        $keyword_data = [
                            'group_id' => $group,
                            'keyword'  => $row['key'],
                            'volume'   => $row['search_volume'],
                            'cpc'      => number_format($row['cpc'], 2),
                        ];

                        $wpdb->insert('xag_keywords', $keyword_data);

                        $kid = $wpdb->insert_id;

                        $keyword_data['id'] = $kid;
                        $keywords[]         = $keyword_data;

                    }
                } else {
                    $wpdb->delete('xag_projects', [
                        'id' => $project_id
                    ]);
                    xagio_json('error', 'No ranking keywords found for selected website. Audit credits are not deducted. Please try again', $project_id);
                }

            } else if ($http_code == 400) {
                xagio_json('credits', $result['message']);
            } else {
                xagio_json('error', $result['message']);
            }

            // Remove empty groups
            if ($skip_empty == 'on') {
                foreach ($groups as $group_id) {

                    $no_keywords = XAGIO_MODEL_KEYWORDS::getKeywords(TRUE, $group_id);

                    if (empty($no_keywords)) {
                        XAGIO_MODEL_GROUPS::deleteGroup($group_id, TRUE);
                    }

                }
            }
            xagio_jsonc([
                'project_id' => $project_id
            ]);

        }

        public static function generateSeedGroup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['project_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $project_id = intval($_POST['project_id']);

            if (get_option('xagio_ONBOARDING_LOCATION')) {
                $keywords = get_option('xagio_ONBOARDING_LOCATION');
            } else {
                $keywords = get_option('xagio_ONBOARDING_MAIN_KEYWORD');
            }

            if (empty($keywords)) {
                xagio_json('error', 'Please enter any keyword!');
            }

            $keywords = str_replace("-", " ", $keywords);
            $keywords = explode(" ", $keywords);

            $group_name = "Seed Group";

            if (isset($keywords[0]))
                $group_name = $keywords[0];

            $group_ids = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id FROM xag_groups WHERE project_id = %d", $project_id
                ), ARRAY_A
            );

            $project_group_ids = [];
            foreach ($group_ids as $group_id) {
                $project_group_ids[] = $group_id['id'];
            }


            $wpdb->insert('xag_groups', [
                'project_id' => $project_id,
                'group_name' => $group_name
            ]);

            $group_id = $wpdb->insert_id;

            $likeKeywords = [];
            foreach ($keywords as $keyword) {
                $keyword        = '%' . $wpdb->esc_like(trim($keyword)) . '%';
                $likeKeywords[] = $wpdb->prepare("keyword LIKE %s", $keyword);
            }
            unset($keywords);

            $likeKeywords = implode(" OR ", $likeKeywords);
            $likeKeywords = "($likeKeywords)";

            // Create placeholders for project group IDs
            $groupIdsPlaceholders = implode(", ", array_fill(0, count($project_group_ids), '%d'));

            // Merge group IDs with likeKeywords into a single array
            $queryParams = array_merge(array_map('absint', $project_group_ids), [sanitize_text_field($likeKeywords)]);

            // Prepare and execute the query
            $updateKeywords = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id FROM xag_keywords WHERE group_id IN ($groupIdsPlaceholders) AND %s", ...$queryParams
                ), ARRAY_A
            );

            unset($likeKeywords);

            $keywordIDs = [];
            foreach ($updateKeywords as $uk) {
                $keywordIDs[] = $uk['id'];
            }
            unset($updateKeywords);

            $wpdb->update('xag_projects', [
                'group_id' => $group_id
            ], [
                'id' => $keywordIDs
            ]);

            // Ensure $project_group_ids is an array of integers
            $project_group_ids = array_map('absint', (array) $project_group_ids);

            // Create a comma-separated list of placeholders (%d) based on the number of IDs
            $group_placeholders = implode(',', array_fill(0, count($project_group_ids), '%d'));

            // Prepare and execute the queries using the generated placeholders
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM xag_groups WHERE id IN ($group_placeholders)",
                    $project_group_ids
                )
            );

            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM xag_keywords WHERE group_id IN ($group_placeholders)",
                    $project_group_ids
                )
            );



            xagio_jsonc([
                'project_id' => $project_id
            ]);

        }

        public static function previewPhraseMatch()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['keywords']) || !isset($_POST['min_match']) || !isset($_POST['min_kws']) || !isset($_POST['include_prepositions']) || !isset($_POST['excluded_words'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $keywords = map_deep(wp_unslash($_POST['keywords']), 'sanitize_text_field');

            $minMatchingWords    = sanitize_text_field(wp_unslash($_POST['min_match']));
            $minKeywordsInGroup  = sanitize_text_field(wp_unslash($_POST['min_kws']));
            $includePrepositions = filter_var(wp_unslash($_POST['include_prepositions']), FILTER_VALIDATE_BOOLEAN);
            $excludedWords       = sanitize_text_field(wp_unslash($_POST['excluded_words']));

            $wordSimilarity = 80;
            if (!empty($_POST['word_similarity'])) {
                $wordSimilarity = absint(wp_unslash($_POST['word_similarity']));
            }

            if (!empty($excludedWords)) {
                $excludedWords = explode(',', $excludedWords);
            }

            $groups = [];
            $words  = [];
            $used   = [];

            $keywords = array_filter($keywords);

            // Separate the words first
            foreach ($keywords as $k) {
                $raw_words = explode(' ', $k);
                foreach ($raw_words as $raw_word) {
                    if (!isset($words[$raw_word])) {
                        // check if similar word exists
                        foreach ($words as $word => $count) {
                            $similarity_percent = 0;
                            xagio_similar_text($word, $raw_word, $similarity_percent);
                            if ($similarity_percent >= $wordSimilarity) {
                                $words[$word]++;
                            }
                        }
                        $words[$raw_word] = 1;
                    } else {
                        $words[$raw_word]++;
                    }
                }
            }

            // Trim out the prepositions
            if (!$includePrepositions) {
                $prepositions = [
                    'for',
                    'or',
                    'in',
                    'the',
                    'a',
                    'and',
                    'at',
                    'on',
                    'to',
                    'by',
                    'of'
                ];
                foreach ($prepositions as $preposition) {
                    unset($words[$preposition]);
                }
            }

            // Exclude words
            if (is_array($excludedWords)) {
                foreach ($excludedWords as $word) {
                    $tword = trim($word);
                    if (!empty($tword)) {
                        unset($words[$tword]);
                    }
                }
            }

            $new_words = [];
            foreach ($words as $word => $count) {
                if ($count > 1 && !is_int($word)) {
                    $new_words[] = $word;
                }
            }

            $words = $new_words;

            // Create groups
            foreach ($keywords as $k) {

                $group_name = [];
                // Check if keyword contains high volume groups
                $raw_words = explode(' ', $k);

                foreach ($raw_words as $raw_word) {
                    if (in_array($raw_word, $words)) {
                        $group_name[] = $raw_word;
                    }
                }

                if (sizeof($group_name) < $minMatchingWords) {
                    continue;
                }

                $group_name = join(' ', $group_name);
                if (!in_array($group_name, $groups))
                    $groups[] = $group_name;
            }

            // Trim out lonely groups
            $new_groups = [];
            foreach ($groups as $group_name) {

                $group_split = explode(' ', $group_name);
                $kws         = [];

                foreach ($keywords as $k) {

                    if (in_array($k, $used))
                        continue;

                    $keyword_split  = explode(' ', $k);
                    $matchingValues = 0;

                    foreach ($group_split as $gs) {
                        foreach ($keyword_split as $ks) {
                            $similarity_percent = 0;
                            xagio_similar_text($gs, $ks, $similarity_percent);
                            if ($similarity_percent >= $wordSimilarity) {
                                $matchingValues++;
                            }
                        }
                    }

                    if ($matchingValues >= (sizeof($group_split))) {
                        $kws[] = $k;
                    }

                }

                $new_group_name = [];
                foreach ($group_split as $gs) {
                    $new_group_name[] = ucfirst($gs);
                }
                $group_name = join(' ', $new_group_name);

                if (sizeof($kws) >= $minKeywordsInGroup) {
                    $new_groups[$group_name] = $kws;
                    // add all keywords to used
                    foreach ($kws as $kw) {
                        $used[] = $kw;
                    }
                }
            }

            // Put the unsorted keywords into Miscellaneous group
            if (sizeof($keywords) != sizeof($used)) {
                $new_groups['Miscellaneous'] = [];
                foreach ($keywords as $keyword) {
                    if (!in_array($keyword, $used)) {
                        $new_groups['Miscellaneous'][] = $keyword;
                    }
                }
            }

            $groups = $new_groups;

            xagio_json('success', 'Preview', $groups);
        }

        public static function combineProjects()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['project_ids'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $project_ids = intval($_POST['project_ids']);
            $project_ids = explode(',', $project_ids);
            if (sizeof($project_ids) > 0) {

                $final_project_id = $project_ids[0];

                array_shift($project_ids);

                foreach ($project_ids as $project_id) {
                    $wpdb->update('xag_groups', ['project_id' => $final_project_id], ['project_id' => $project_id]);
                    $wpdb->delete('xag_projects', ['id' => $project_id]);
                }

                xagio_jsonc([
                    'status'     => 'success',
                    'project_id' => $final_project_id
                ]);

            } else {
                xagio_json('error', 'You must send some project ids');
            }
        }

        public static function generatePhraseMatch()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['project_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $project_id_old = intval($_POST['project_id']);

            $keywords = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT k.keyword FROM xag_groups g LEFT JOIN xag_keywords k ON g.id = k.group_id WHERE g.project_id = %d", $project_id_old
                ), ARRAY_A
            );

            $cleanKeywords = [];
            foreach ($keywords as $keyword) {
                $cleanKeywords[] = $keyword['keyword'];
            }

            $keywords = $cleanKeywords;

            $projectName = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT project_name FROM xag_projects WHERE id = %d", $project_id_old
                )
            );

            $_POST['project_name'] = $projectName;
            $_POST['keywords']     = $keywords;

            $_POST['min_match']              = 3;
            $_POST['min_kws']                = 2;
            $_POST['include_prepositions']   = false;
            $_POST['excluded_words']         = [];
            $_POST['word_similarity']        = 80;
            $_POST['cluster_in_new_project'] = '1';

            $project_id = XAGIO_MODEL_KEYWORDS::phraseMatch(true);

            XAGIO_MODEL_GROUPS::deleteGroupsAll($project_id_old, TRUE);
            $wpdb->delete('xag_projects', [
                'id' => $project_id_old
            ]);

            xagio_jsonc(['project_id' => $project_id]);
        }

        public static function shareProject()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['project_id']) || !isset($_POST['share'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $project_id = intval($_POST['project_id']);
            $hashed     = md5($project_id);
            $is_active  = sanitize_text_field(wp_unslash($_POST['share']));

            if ((int)$is_active) {
                $wpdb->update('xag_projects', [
                    'shared' => $hashed
                ], [
                    'id' => $project_id
                ]);

                $shared_url = get_site_url() . "/shared-seo-report?hash=" . $hashed;

                xagio_json('success', 'Project successfully shared.', $shared_url);
            } else {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE xag_projects SET shared = NULL WHERE id = %d", $project_id
                    )
                );


                xagio_json('success', 'Successfully removed sharing.');
            }


        }

        // Attach to Taxonomy
        public static function attachToTaxonomy()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['taxonomy_id']) || !isset($_POST['group_id']) || !isset($_POST['attach_type'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $taxonomy_id = intval($_POST['taxonomy_id']);
            $group_id    = intval($_POST['group_id']);
            $attach_type = sanitize_text_field(wp_unslash($_POST['attach_type']));

            $term  = get_term($taxonomy_id);
            $group = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM xag_groups WHERE id = %d", $group_id), ARRAY_A
            );


            $h1    = NULL;
            $title = NULL;
            $desc  = NULL;

            if ($attach_type == 'taxonomy') {

                $h1    = $term->name;
                $title = '';
                $desc  = '';
                $url   = $term->slug;

                $meta = xagio_get_term_meta($term->term_id);

                if ($term->taxonomy == 'location') {

                    // Get the magic page
                    $magicpage_id = get_posts([
                        'post_type' => 'magicpage',
                    ]);
                    $magicpage_id = $magicpage_id[0]->ID;

                    if (empty($meta['XAGIO_SEO_TITLE'])) {
                        $meta['XAGIO_SEO_TITLE'] = get_post_meta($magicpage_id, 'XAGIO_SEO_TITLE', TRUE);
                    }
                    if (empty($meta['XAGIO_SEO_DESCRIPTION'])) {
                        $meta['XAGIO_SEO_DESCRIPTION'] = get_post_meta($magicpage_id, 'XAGIO_SEO_DESCRIPTION', TRUE);
                    }

                }

                if (isset($meta['XAGIO_SEO_TITLE']) && !empty($meta['XAGIO_SEO_TITLE'])) {
                    $title = $meta['XAGIO_SEO_TITLE'];
                }
                if (isset($meta['XAGIO_SEO_DESCRIPTION']) && !empty($meta['XAGIO_SEO_DESCRIPTION'])) {
                    $desc = $meta['XAGIO_SEO_DESCRIPTION'];
                }

            } else if ($attach_type == 'group') {

                $h1    = $group['h1'];
                $title = $group['title'];
                $desc  = $group['description'];
                $url   = $group['url'];

            }

            if (empty($title)) {
                xagio_json('error', "SEO Title from your $attach_type is empty! Please set it up, or choose a different import data source!");
                return;
            }

            if (empty($desc)) {
                xagio_json('error', "SEO Description from your $attach_type is empty! Please set it up, or choose a different import data source!");
                return;
            }

            if (empty($h1)) {
                xagio_json('error', "H1 from your $attach_type is empty! Please set it up, or choose a different import data source!");
                return;
            }

            $wpdb->update('xag_groups', [
                'id_page_post'    => 0,
                'id_taxonomy'     => $taxonomy_id,
                'h1'              => $h1,
                'url'             => $url,
                'title'           => $title,
                'description'     => $desc,
                'external_domain' => '',
            ], [
                'id' => $group_id,
            ]);

            if (isset($meta)) {
                $meta['XAGIO_SEO_TITLE']       = $title;
                $meta['XAGIO_SEO_DESCRIPTION'] = $desc;
            } else {
                $meta = [
                    'XAGIO_SEO_TITLE'       => $title,
                    'XAGIO_SEO_DESCRIPTION' => $desc,
                ];
            }

            if ($term->taxonomy == 'location') {
                $meta['XAGIO_SEO_ROBOTS'] = 0;
            } else {
                $meta['XAGIO_SEO_ROBOTS'] = 1;
            }

            update_term_meta($taxonomy_id, 'XAGIO_SEO_TITLE', $meta['XAGIO_SEO_TITLE']);
            update_term_meta($taxonomy_id, 'XAGIO_SEO_DESCRIPTION', $meta['XAGIO_SEO_DESCRIPTION']);
            update_term_meta($taxonomy_id, 'XAGIO_SEO_ROBOTS', $meta['XAGIO_SEO_ROBOTS']);

            xagio_json('success', 'Successfully attached taxonomy.');
        }

        // Attach Page/Post to group
        public static function attachToProjectGroup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['post_id']) || !isset($_POST['group_id']) || !isset($_POST['type'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $post_id     = intval($_POST['post_id']);
            $group_id    = intval($_POST['group_id']);
            $attach_type = sanitize_text_field(wp_unslash($_POST['type']));

            $post  = get_post($post_id);
            $group = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM xag_groups WHERE id = %d", $group_id), ARRAY_A
            );

            $homepage = false;
            if (get_option('page_on_front') === $post_id) {
                $homepage = true;
            }

            $h1                     = NULL;
            $title                  = NULL;
            $desc                   = NULL;
            $notes                  = NULL;
            $url                    = NULL;
            $page_url               = XAGIO_MODEL_SEO::extract_url($post_id);
            $redirect_added_message = "";

            if ($attach_type == 'page') {
                $h1    = isset($_POST['h1']) ? sanitize_text_field(wp_unslash($_POST['h1'])) : $post->post_title;
                $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : get_post_meta($post_id, 'XAGIO_SEO_TITLE', TRUE);
                $desc  = isset($_POST['desc']) ? sanitize_text_field(wp_unslash($_POST['desc'])) : get_post_meta($post_id, 'XAGIO_SEO_DESCRIPTION', TRUE);
                $notes = isset($_POST['notes']) ? sanitize_text_field(wp_unslash($_POST['notes'])) : get_post_meta($post_id, 'XAGIO_SEO_NOTES', TRUE);
                $url   = isset($_POST['url']) ? sanitize_url(wp_unslash($_POST['url'])) : $page_url;

            } else if ($attach_type == 'group') {
                $h1    = $group['h1'];
                $title = $group['title'];
                $desc  = $group['description'];
                $notes = $group['notes'];
                $url   = $group['url'];

                if (!$homepage) {
                    if ($page_url !== $url) {
                        $redirect_added_message = "Added automatic redirect from: $page_url to new URL: $url in 301 Management";
                        XAGIO_MODEL_REDIRECTS::add($page_url, $url);
                    }
                } else {
                    $url = "/";
                }
            }

            // Validate required fields
            if (empty($title)) {
                xagio_json('error', "SEO Title from your $attach_type is empty! Please set it up, or choose a different import data source!");
                return;
            }

            if (empty($desc)) {
                xagio_json('error', "SEO Description from your $attach_type is empty! Please set it up, or choose a different import data source!");
                return;
            }

            if (empty($h1)) {
                xagio_json('error', "H1 from your $attach_type is empty! Please set it up, or choose a different import data source!");
                return;
            }

            if (!$homepage) {
                if (empty($url)) {
                    xagio_json('error', "Slug/URL from your $attach_type is empty! Please set it up, or choose a different import data source!");
                    return;
                }
            }

            // Clear any existing group associations
            $wpdb->update('xag_groups', [
                'id_page_post' => 0
            ], [
                'id_page_post' => $post_id,
            ]);

            // Update group data
            $wpdb->update('xag_groups', [
                'id_taxonomy'     => 0,
                'id_page_post'    => $post_id,
                'h1'              => $h1,
                'url'             => $url,
                'title'           => $title,
                'description'     => $desc,
                'external_domain' => '',
                'notes'           => $notes
            ], [
                'id' => $group_id,
            ]);

            // Update post meta
            update_post_meta($post_id, 'XAGIO_SEO_TITLE', $title);
            update_post_meta($post_id, 'XAGIO_SEO_DESCRIPTION', $desc);
            update_post_meta($post_id, 'XAGIO_SEO_NOTES', $notes);

            // Update post data
            $post_data = [
                'ID'         => $post_id,
                'post_title' => $h1,
                'post_name'  => sanitize_title($url),
            ];
            wp_update_post($post_data);

            xagio_json('success', 'Successfully attached page/post to group. ' . $redirect_added_message);
        }

        // Attach to Page/Post
        public static function attachToPagePost()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['post_id']) || !isset($_POST['group_id']) || !isset($_POST['attach_type'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $post_id     = intval($_POST['post_id']);
            $group_id    = intval($_POST['group_id']);
            $attach_type = sanitize_text_field(wp_unslash($_POST['attach_type']));

            $post  = get_post($post_id);
            $group = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM xag_groups WHERE id = %d", $group_id), ARRAY_A
            );

            $homepage = false;
            if (get_option('page_on_front') === $post_id) {
                $homepage = true;
            }

            $h1                     = NULL;
            $title                  = NULL;
            $desc                   = NULL;
            $notes                  = NULL;
            $url                    = NULL;
            $page_url               = XAGIO_MODEL_SEO::extract_url($post_id);
            $redirect_added_message = "";

            if ($attach_type == 'page') {

                $h1    = $post->post_title;
                $title = get_post_meta($post_id, 'XAGIO_SEO_TITLE', TRUE);
                $desc  = get_post_meta($post_id, 'XAGIO_SEO_DESCRIPTION', TRUE);
                $notes = get_post_meta($post_id, 'XAGIO_SEO_NOTES', TRUE);
                $url   = $page_url;

            } else if ($attach_type == 'group') {

                $h1    = $group['h1'];
                $title = $group['title'];
                $desc  = $group['description'];
                $notes = $group['notes'];
                $url   = $group['url'];

                if (!$homepage) {
                    if ($page_url !== $url) {
                        $redirect_added_message = "Added automatic redirect from: $page_url to new URL: $url in 301 Management";
                        XAGIO_MODEL_REDIRECTS::add($page_url, $url);
                    }
                } else {
                    $url = "/";
                }
            }

            if (empty($title)) {
                xagio_json('error', "SEO Title from your $attach_type is empty! Please set it up, or choose a different import data source!");
                return;
            }

            if (empty($desc)) {
                xagio_json('error', "SEO Description from your $attach_type is empty! Please set it up, or choose a different import data source!");
                return;
            }

            if (empty($h1)) {
                xagio_json('error', "H1 from your $attach_type is empty! Please set it up, or choose a different import data source!");
                return;
            }

            if (!$homepage) {
                if (empty($url)) {
                    xagio_json('error', "Slug/URL from your $attach_type is empty! Please set it up, or choose a different import data source!");
                    return;
                }
            }

            $wpdb->update('xag_groups', [
                'id_taxonomy'     => 0,
                'id_page_post'    => $post_id,
                'h1'              => $h1,
                'url'             => $url,
                'title'           => $title,
                'description'     => $desc,
                'external_domain' => '',
                'notes'           => $notes,
            ], [
                'id' => $group_id,
            ]);

            update_post_meta($post_id, 'XAGIO_SEO_TITLE', $title);
            update_post_meta($post_id, 'XAGIO_SEO_DESCRIPTION', $desc);
            update_post_meta($post_id, 'XAGIO_SEO_NOTES', $notes);

            $post_data = [
                'ID'         => $post_id,
                'post_title' => $h1,
                'post_name'  => sanitize_title($url),
                // Update the post slug
            ];

            // Update the post into the database
            wp_update_post($post_data);

            xagio_json('success', 'Successfully attached page/post. ' . $redirect_added_message);
        }

        // Detach Page/Post to group
        public static function detachFromGroup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['group_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $group_id = intval($_POST['group_id']);

            $wpdb->update('xag_groups', [
                'id_page_post' => 0
            ], [
                'id' => $group_id,
            ]);

            xagio_json('success', 'Successfully detached from Project Group.');
        }

        public static function getAncestorTree($post_id)
        {
            $ancestors = get_post_ancestors($post_id);

            $parent = (!empty($ancestors)) ? array_pop($ancestors) : (int)$post_id;

            $pages = get_pages(['child_of' => $parent]);

            // Bail if there are no results.
            if (!$pages) {
                return FALSE;
            }

            $page_ids = [];
            foreach ($pages as $page)
                $page_ids[] = (int)$page->ID;

            array_unshift($page_ids, $parent);

            $output = wp_list_pages([
                'include'  => $page_ids,
                'title_li' => FALSE,
                'echo'     => FALSE,
            ]);

            if (!$output) {
                return FALSE;
            } else {
                return $page_ids;
            }
        }

        public static function generateSiloPageArray($page)
        {
            $author = get_user_by('id', $page['post_author']);

            $page['post_title']       = XAGIO_MODEL_PROJECTS::postChildren($page['ID'], $page['post_parent']) . $page['post_title'];
            $page['permalink']        = get_permalink($page['ID']);
            $page['post_author_name'] = isset($author->user_login) ? $author->user_login : 'n/a';
            $page['seo']              = (int)get_post_meta($page['ID'], 'ps_seo_enabled', TRUE);
            $page['reviews']          = (int)XAGIO_MODEL_REVIEWS::countReviews($page['ID']);
            $page['schema']           = XAGIO_MODEL_SCHEMA::getSchemas($page['ID']);
            $page['script_disable']   = get_post_meta($page['ID'], 'ps_seo_disable_global_scripts', TRUE);
            $page['script']           = base64_encode(get_post_meta($page['ID'], 'ps_seo_scripts', TRUE));
            $page['attached']         = XAGIO_MODEL_PROJECTS::isAttachedToGroup($page['ID']);

            return $page;
        }

        public static function postChildren($post_id, $parent_id, $return = "")
        {
            if ($parent_id) {
                if (xagio_has_post_parent($post_id)) {
                    $newReturn   = "— ";
                    $parent_post = get_post($parent_id);
                    $return      .= self::postChildren($parent_post->ID, $parent_post->post_parent, $newReturn);
                    return $return;
                }
            }
            return $return;
        }

        public static function isAttachedToGroup($post_id)
        {
            global $wpdb;
            $groups = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT * FROM `xag_groups` WHERE `id_page_post` = %d', intval($post_id)
                ), ARRAY_A
            );

            if ($groups == FALSE)
                return FALSE;

            if (isset($groups['id']))
                return (int)@$groups['id'];

            if (is_array($groups))
                $groups = $groups[0];
            return (int)@$groups['id'];
        }

        public static function isAttachedToGroupArray($post_id)
        {
            global $wpdb;

            $groups = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT * FROM `xag_groups` WHERE `id_page_post` = %d', intval($post_id)
                ), ARRAY_A
            );

            if ($groups == FALSE)
                return FALSE;

            if (isset($groups['id']))
                return [
                    'project_id' => (int)@$groups['project_id'],
                    'group_id'   => (int)@$groups['id']
                ];

            if (is_array($groups))
                $groups = $groups[0];


            return [
                'project_id' => (int)@$groups['project_id'],
                'group_id'   => (int)@$groups['id']
            ];
        }


        // Search Posts
        public static function getPosts()
        {
            $xagio_request = new XAGIO_REQUEST();
            XAGIO_API::searchPosts($xagio_request);
        }

        // Get Posts
        public static function getPostTypes()
        {
            XAGIO_API::getPostTypes();
        }

        // Search Taxonomies
        public static function getTaxonomies()
        {
            $xagio_request = new XAGIO_REQUEST();
            XAGIO_API::searchTaxonomies($xagio_request);
        }

        // Get Taxonomies
        public static function getTaxonomyTypes()
        {
            $taxonomies = XAGIO_MODEL_SEO::getAllTaxonomies();
            xagio_json('success', 'Retrieved taxonomies.', array_values($taxonomies));
        }

        public static function getTagsCategories()
        {
            $categories = get_categories([
                "hide_empty" => 0,
                "type"       => "post",
                "orderby"    => "name",
                "order"      => "ASC",
            ]);
            $tags       = get_tags([
                "hide_empty" => 0,
                "type"       => "post",
                "orderby"    => "name",
                "order"      => "ASC",
            ]);

            xagio_json('success', 'Successfully got tags and categories!', [
                'categories' => $categories,
                'tags'       => $tags,
            ]);
        }

        // Make Groups from Taxonomies
        public static function makeGroupsFromTaxonomies()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['ids']) || !isset($_POST['project_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $taxonomies = explode(',', sanitize_text_field(wp_unslash($_POST['ids'])));
            $project_id = intval($_POST['project_id']);

            if (!isset($project_id) || empty($project_id)) {
                xagio_json('error', 'Project ID is missing, please enter some project!');
            }

            if (!is_array($taxonomies)) {
                xagio_json('error', 'Please select some taxonomies!');
            }

            foreach ($taxonomies as $taxonomy_id) {
                $term = get_term($taxonomy_id);

                $meta = [
                    'XAGIO_SEO_TITLE'       => get_term_meta($taxonomy_id, 'XAGIO_SEO_TITLE', true),
                    'XAGIO_SEO_DESCRIPTION' => get_term_meta($taxonomy_id, 'XAGIO_SEO_DESCRIPTION', true)
                ];

                if ($term->taxonomy == 'location') {

                    // Get the magic page
                    $magicpage_id = get_posts([
                        'post_type' => 'magicpage',
                    ]);
                    $magicpage_id = $magicpage_id[0]->ID;

                    if (empty($meta['XAGIO_SEO_TITLE'])) {
                        $meta['XAGIO_SEO_TITLE'] = get_post_meta($magicpage_id, 'XAGIO_SEO_TITLE', TRUE);
                    }
                    if (empty($meta['XAGIO_SEO_DESCRIPTION'])) {
                        $meta['XAGIO_SEO_DESCRIPTION'] = get_post_meta($magicpage_id, 'XAGIO_SEO_DESCRIPTION', TRUE);
                    }
                }

                $wpdb->insert('xag_groups', [
                    'project_id'   => $project_id,
                    'group_name'   => $term->name,
                    'id_page_post' => 0,
                    'id_taxonomy'  => $taxonomy_id,
                    'title'        => @$meta['XAGIO_SEO_TITLE'],
                    'url'          => XAGIO_MODEL_SEO::extract_url($taxonomy_id, TRUE),
                    'description'  => @$meta['XAGIO_SEO_DESCRIPTION'],
                    'h1'           => $term->name,
                    'notes'        => '',
                    'date_created' => gmdate('Y-m-d H:i:s'),
                ]);

            }

            xagio_json('success', 'Successfully created Groups from Taxonomies!');
        }

        // Make Groups
        public static function makeGroups()
        {
            global $wpdb;

            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['ids']) || !isset($_POST['project_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $posts      = explode(',', sanitize_text_field(wp_unslash($_POST['ids'])));
            $project_id = intval($_POST['project_id']);

            if (!isset($project_id) || empty($project_id)) {
                xagio_json('error', 'Project ID is missing, please enter some project!');
            }

            if (!is_array($posts)) {
                xagio_json('error', 'Please select some posts!');
            }

            foreach ($posts as $post_id) {
                $post = get_post($post_id);

                $ps_seo_enabled = get_post_meta($post_id, 'ps_seo_enabled', TRUE);
                $wp_post_title  = $post->post_title;
                $wp_post_url    = XAGIO_MODEL_SEO::extract_url($post_id);
                $wp_description = '';

                $XAGIO_SEO_TITLE       = get_post_meta($post_id, 'XAGIO_SEO_TITLE', TRUE);
                $XAGIO_SEO_DESCRIPTION = get_post_meta($post_id, 'XAGIO_SEO_DESCRIPTION', TRUE);
                $XAGIO_SEO_NOTES       = get_post_meta($post_id, 'XAGIO_SEO_NOTES', TRUE);

                XAGIO_MODEL_GROUPS::newGroupFromExistingPost(
                    $project_id, $wp_post_title, $post_id, $XAGIO_SEO_TITLE, $wp_post_url, $XAGIO_SEO_DESCRIPTION, $wp_post_title, $XAGIO_SEO_NOTES
                );

            }

            xagio_json('success', 'Successfully created Groups from Posts!');
        }

        // Get all projects
        public static function getProjects()
        {
            global $wpdb;

            $results = $wpdb->get_results(
                "SELECT xag_projects.id, xag_projects.project_name, xag_projects.shared, xag_projects.status, xag_projects.date_created, COUNT(DISTINCT xag_groups.id) AS `groups`, COUNT(DISTINCT xag_keywords.id) AS keywords
            FROM xag_projects
            LEFT JOIN xag_groups ON xag_projects.id = xag_groups.project_id
            LEFT JOIN xag_keywords ON xag_groups.id = xag_keywords.group_id
            GROUP BY xag_projects.id
            ORDER BY xag_projects.id DESC", ARRAY_A
            );


            wp_send_json(["aaData" => $results]);
        }

        public static function getGroups()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            $results = [];
            if (isset($_POST['project_id']) && is_numeric($_POST['project_id'])) {
                $project_id = intval($_POST['project_id']);
                $results    = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, group_name FROM xag_groups WHERE project_id = %d", $project_id
                    ), ARRAY_A
                );
            }

            wp_send_json(["aaData" => $results]);
        }


        public static function getAlertProjectID()
        {
            $id = get_option('XAGIO_PROJECT_ALERT_ID');
            if ($id) {
                wp_send_json([
                    "status"     => 'success',
                    "project_id" => $id
                ]);
            } else {
                wp_send_json([
                    "status"     => 'success',
                    "project_id" => ''
                ]);
            }

        }

        public static function removeAlertProjectID()
        {
            update_option('XAGIO_PROJECT_ALERT_ID', '');
        }

        // Rename Project
        public static function renameProject()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['project_name']) || !isset($_POST['project_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $project_name = sanitize_text_field(wp_unslash($_POST['project_name']));
            $project_id   = intval($_POST['project_id']);

            $wpdb->update('xag_projects', [
                'project_name' => $project_name,
            ], [
                'id' => $project_id,
            ]);
        }

        // Create new Project
        public static function newProject()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['project_name'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            global $wpdb;

            $projectName = sanitize_text_field(wp_unslash($_POST['project_name']));
            $dateCreated = gmdate('Y-m-d H:i:s');
            $wpdb->insert('xag_projects', [
                'project_name' => $projectName,
                'date_created' => $dateCreated,
            ]);
        }

        // Remove Project
        public static function removeProject()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['project_id']) || !isset($_POST['deleteRanks'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            global $wpdb;

            $projectID   = intval($_POST['project_id']);
            $deleteRanks = filter_var(wp_unslash($_POST['deleteRanks']), FILTER_VALIDATE_BOOLEAN);

            if ($deleteRanks) {
                $groups = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT `id` FROM xag_groups WHERE `project_id` = %d", $projectID
                    ), ARRAY_A
                );

                if (!empty($groups)) {
                    $group_ids = array_map(function ($group) {
                        return intval($group['id']);
                    }, $groups);

                    if (!empty($group_ids)) {
                        $placeholders   = implode(',', array_fill(0, count($group_ids), '%d'));
                        $rankedKeywords = $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT `keyword` FROM xag_keywords WHERE `group_id` IN ($placeholders) AND `rank` != '0'", ...$group_ids
                            ), ARRAY_A
                        );

                        if (!empty($rankedKeywords)) {
                            $keywordsToDelete     = [];
                            $updateKeywordsString = [];

                            foreach ($rankedKeywords as $rankedKeyword) {
                                $keywordsToDelete[]     = $rankedKeyword['keyword'];
                                $updateKeywordsString[] = addslashes($rankedKeyword['keyword']);
                            }

                            // Escape and prepare the keywords array
                            $placeholders = implode(', ', array_fill(0, count($updateKeywordsString), '%s'));

                            // Prepare and execute the query
                            $wpdb->query(
                                $wpdb->prepare(
                                    "UPDATE xag_keywords SET rank = '0' WHERE keyword IN ($placeholders)", ...array_map('sanitize_text_field', $updateKeywordsString)
                                )
                            );

                            XAGIO_MODEL_GROUPS::deleteKeywordRanks($keywordsToDelete);
                        }
                    }
                }
            }

            $wpdb->query(
                $wpdb->prepare(
                    "DELETE p, g, k FROM xag_projects p 
             LEFT JOIN xag_groups g ON p.id = g.project_id 
             LEFT JOIN xag_keywords k ON g.id = k.group_id 
             WHERE p.id = %d", $projectID
                )
            );
        }


        // duplicateProject
        public static function duplicateProject()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['project_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            global $wpdb;
            $originalProjectId = intval($_POST['project_id']);

            try {
                // Begin transaction
                $wpdb->query('START TRANSACTION');

                // Step 1: Duplicate the project
                $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO `xag_projects` (`project_name`, `status`, `shared`, `date_created`)
                     SELECT CONCAT(`project_name`, ' - COPY'), `status`, NULL, NOW()
                     FROM `xag_projects` WHERE `id` = %d", $originalProjectId
                    )
                );

                // Get the new project ID
                $newProjectId = $wpdb->insert_id;

                // Step 2: Duplicate the groups
                $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO `xag_groups` (`project_id`, `id_page_post`, `id_taxonomy`, `external_domain`, `group_name`, `title`, `url`, `description`, `h1`, `date_created`, `position`, `notes`)
            SELECT %d, `id_page_post`, `id_taxonomy`, `external_domain`, `group_name`, `title`, `url`, `description`, `h1`, NOW(), `position`, `notes`
            FROM `xag_groups` WHERE `project_id` = %d", $newProjectId, $originalProjectId
                    )
                );

                // Get the new group IDs and map them to the old group IDs
                $oldGroups = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT `id` FROM `xag_groups` WHERE `project_id` = %d", $originalProjectId
                    )
                );

                $newGroups = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT `id` FROM `xag_groups` WHERE `project_id` = %d", $newProjectId
                    )
                );

                if (count($oldGroups) !== count($newGroups)) {
                    throw new Exception('Mismatch in group count.');
                }

                $groupMap = [];
                for ($i = 0; $i < count($oldGroups); $i++) {
                    $groupMap[$oldGroups[$i]->id] = $newGroups[$i]->id;
                }

                // Step 3: Duplicate the keywords
                foreach ($groupMap as $oldGroupId => $newGroupId) {
                    $keywords = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT `keyword`, `volume`, `cpc`, `inurl`, `intitle`, `position`, `queued`, `rank`
                FROM `xag_keywords` WHERE `group_id` = %d", $oldGroupId
                        )
                    );

                    foreach ($keywords as $keyword) {
                        $wpdb->query(
                            $wpdb->prepare(
                                "INSERT INTO `xag_keywords` (`group_id`, `keyword`, `volume`, `cpc`, `inurl`, `intitle`, `date_created`, `position`, `queued`, `rank`)
                    VALUES (%d, %s, %s, %s, %s, %s, NOW(), %d, %d, %s)", $newGroupId, $keyword->keyword, $keyword->volume, $keyword->cpc, $keyword->inurl, $keyword->intitle, $keyword->position, $keyword->queued, $keyword->rank
                            )
                        );
                    }
                }

                // Commit the transaction
                $wpdb->query('COMMIT');

                xagio_json('success', 'Project successfully duplicated.');
            } catch (Exception $e) {
                // Rollback the transaction in case of error
                $wpdb->query('ROLLBACK');
                xagio_json('error', 'Mismatch in group count while duplicating project.');
            }
        }


        //--------------------------------------------
        //
        //               Functions
        //
        //--------------------------------------------

        public static function getPagePostParent()
        {
            $pages = get_pages();
            for ($i = 0; $i < count($pages); $i++) {
                $page       = (array)$pages[$i];
                $id         = $page['ID'];
                $title      = $page['post_title'];
                $pageList[] = [
                    'id'    => $id,
                    'title' => $title,
                ];
            }

            xagio_jsonc($pageList);
        }

        public static function updatePagePostStatus()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (isset($_POST['page_id']) && isset($_POST['value'])) {
                $res = wp_update_post([
                    'ID'          => intval($_POST['page_id']),
                    'post_status' => sanitize_text_field(wp_unslash($_POST['value'])),
                ]);
            }
            xagio_json('success', 'Status updated!', $res);
        }

        public static function updatePageParent()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (isset($_POST['page_id']) && isset($_POST['value'])) {
                $res = wp_update_post([
                    'ID'          => intval($_POST['page_id']),
                    'post_parent' => sanitize_text_field(wp_unslash($_POST['value'])),
                ]);
            }
            xagio_json('success', 'Parent updated!', $res);
        }

        public static function getPagePostStatus()
        {
            $statusTypes  = array_keys(get_post_statuses());
            $statusValues = get_post_statuses();
            for ($i = 0; $i < count($statusTypes); $i++) {
                $status[] = [
                    'value' => $statusTypes[$i],
                    'title' => $statusValues[$statusTypes[$i]]
                ];
            }
            xagio_jsonc($status);
        }


        public static function createPagePost()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['request_type'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $request_type = sanitize_text_field(wp_unslash($_POST['request_type']));

            if ($request_type == 'single') {

                if (!isset($_POST['group_id']) || !isset($_POST['group_name']) || !isset($_POST['title']) || !isset($_POST['url']) || !isset($_POST['description']) || !isset($_POST['h1']) || !isset($_POST['type']) || !isset($_POST['notes'])) {
                    wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
                }

                $group_id          = intval($_POST['group_id']);
                $group_name        = sanitize_text_field(wp_unslash($_POST['group_name']));
                $group_title       = sanitize_text_field(wp_unslash($_POST['title']));
                $group_url         = sanitize_text_field(wp_unslash($_POST['url']));
                $group_description = sanitize_text_field(wp_unslash($_POST['description']));
                $group_h1          = sanitize_text_field(wp_unslash($_POST['h1']));
                $post_type         = sanitize_text_field(wp_unslash($_POST['type']));
                $post_note         = sanitize_text_field(wp_unslash($_POST['notes']));
            } else {

                if (!isset($_POST['group_id']) || !isset($_POST['type'])) {
                    wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
                }

                $group_id          = intval($_POST['group_id']);
                $post_type         = sanitize_text_field(wp_unslash($_POST['type']));
                $group             = $wpdb->get_row($wpdb->prepare("SELECT * FROM xag_groups WHERE id = %d", $group_id), ARRAY_A);
                $group_name        = sanitize_text_field(wp_unslash($group['group_name']));
                $group_title       = sanitize_text_field(wp_unslash($group['title']));
                $group_url         = sanitize_text_field(wp_unslash($group['url']));
                $group_description = sanitize_text_field(wp_unslash($group['description']));
                $group_h1          = sanitize_text_field(wp_unslash($group['h1']));
                $post_note         = sanitize_text_field(wp_unslash($group['notes']));
            }

            if ($post_type != 'page' && $post_type != 'post') {
                $post_type = 'page';
            }
            if ($group_title == '') {
                xagio_json('error', 'Your SEO title is missing!');
            }

            if ($group_url == '') {
                xagio_json('error', 'Your group URL is missing!');
            }

            if ($group_h1 == '') {
                xagio_json('error', 'Your group header is missing!');
            }

            // Check if the Group is already associated with a Page/Post
            $group = $wpdb->get_row($wpdb->prepare('SELECT * FROM xag_groups WHERE id = %d', $group_id), ARRAY_A);

            if ($group === FALSE) {
                xagio_json('error', 'Specified group does not exist.');
            }

            if (!empty($group['id_page_post'])) {
                xagio_json('warning', 'Already exists!', [
                    'url'     => admin_url() . "post.php?post={$group['id_page_post']}&action=edit",
                    'page_id' => $group['id_page_post']
                ]);
            }

            // Create Page/Post.
            global $user_ID;

            $page['post_type']    = $post_type;
            $page['post_content'] = '';
            $page['post_parent']  = 0;
            $page['post_author']  = $user_ID;
            $page['post_status']  = 'draft';
            $page['post_title']   = $group_h1;
            $page_id              = wp_insert_post($page);

            $data  = [
                'group_name'      => $group_name,
                'title'           => $group_title,
                'url'             => $group_url,
                'description'     => $group_description,
                'h1'              => $group_h1,
                'notes'           => $post_note,
                'id_page_post'    => $page_id,
                'external_domain' => '',
            ];
            $where = [
                'id' => $group_id,
            ];
            $wpdb->update('xag_groups', $data, $where);

            if ($page_id == 0) {
                xagio_json('error', 'Could not create page at the moment!');
            }
            wp_update_post([
                'ID'        => $page_id,
                'post_name' => $group_url,
            ]);
            update_post_meta($page_id, '_yoast_wpseo_title', $group_title);
            update_post_meta($page_id, '_yoast_wpseo_metadesc', $group_description);
            update_post_meta($page_id, 'XAGIO_SEO_TITLE', $group_title);
            update_post_meta($page_id, 'XAGIO_SEO_DESCRIPTION', $group_description);
            update_post_meta($page_id, 'XAGIO_SEO_NOTES', $post_note);

            xagio_json('success', 'Created!', [
                'url'       => admin_url() . "post.php?post={$page_id}&action=edit",
                'page_id'   => $page_id,
                'post_type' => $post_type
            ]);
        }

        // Download to CSV
        public static function exportProject()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // Export to csv.
            if (!isset($_GET['project_id'])) {
                die('Project ID is missing!');
            }

            $project_id = intval($_GET['project_id']);
            if ($project_id > 0) {
                self::exportToCsv($project_id);
            } else {
                die('Project ID is missing!');
            }
        }

        public static function exportToCsv($project_id)
        {
            global $wpdb;

            $projectData = $wpdb->get_row($wpdb->prepare("SELECT project_name FROM xag_projects WHERE id = %d", $project_id), ARRAY_A);
            if (isset($projectData['project_name'])) {
                $projectName = $projectData['project_name'];
            } else {
                $projectName = '';
            }
            unset($projectData);

            $projectGroups = $wpdb->get_results($wpdb->prepare("SELECT * FROM xag_groups WHERE project_id = %d", $project_id), ARRAY_A);

            $output = '"Project Name","' . $projectName . '",';
            $output .= "\n";
            $output .= '"Total Groups","' . count($projectGroups) . '",';
            $output .= "\n";
            foreach ($projectGroups as $group) {
                $group_id = $group['id'];
                $keywords = $wpdb->get_results($wpdb->prepare("SELECT * FROM xag_keywords WHERE group_id = %d", $group_id), ARRAY_A);
                $output   .= "\n";
                $output   .= 'Group,Title,URL,DESC,H1,';
                $output   .= "\n";
                $output   .= '"' . $group['group_name'] . '","' . $group['title'] . '","' . $group['url'] . '","' . $group['description'] . '","' . $group['h1'] . '",';
                $output   .= "\n";
                $output   .= 'Keyword,Volume,CPC,inTITLE,inURL,"' . count($keywords) . '",';
                $output   .= "\n";
                foreach ($keywords as $keyword) {
                    $output .= '"' . $keyword['keyword'] . '",="' . $keyword['volume'] . '",="' . $keyword['cpc'] . '",="' . $keyword['intitle'] . '",="' . $keyword['inurl'] . '",';
                    $output .= "\n";
                }
            }
            $filename = $projectName . ".csv";
            header('Content-type: application/csv');
            header('Content-Disposition: attachment; filename=' . $filename);

            echo wp_kses_data($output);
            exit;


        }

        // Download to CSV
        public static function exportProjects()
        {
            $fileName = wp_parse_url(get_site_url());
            $fileName = strtolower($fileName['host']) . "-" . gmdate("Y_m_d");

            global $wpdb;

            $projects = $wpdb->get_results("SELECT id, project_name FROM xag_projects ORDER BY id DESC", ARRAY_A);
            $output   = "";
            foreach ($projects as $project) {
                $project_id  = $project['id'];
                $projectName = $project['project_name'];

                $projectGroups = $wpdb->get_results($wpdb->prepare("SELECT * FROM xag_groups WHERE project_id = %d", $project_id), ARRAY_A);

                $output .= '"Project Name","' . $projectName . '",';
                $output .= "\n";
                $output .= '"Total Groups","' . count($projectGroups) . '",';
                $output .= "\n";
                foreach ($projectGroups as $group) {
                    $group_id = $group['id'];
                    $keywords = $wpdb->get_results($wpdb->prepare("SELECT * FROM xag_keywords WHERE group_id = %d", $group_id), ARRAY_A);
                    $output   .= "\n";
                    $output   .= 'Group,Title,URL,DESC,H1,';
                    $output   .= "\n";
                    $output   .= '"' . $group['group_name'] . '","' . $group['title'] . '","' . $group['url'] . '","' . $group['description'] . '","' . $group['h1'] . '",';
                    $output   .= "\n";
                    $output   .= 'Keyword,Volume,CPC,inTITLE,inURL,"' . count($keywords) . '",';
                    $output   .= "\n";
                    foreach ($keywords as $keyword) {
                        $output .= '"' . $keyword['keyword'] . '",="' . $keyword['volume'] . '",="' . $keyword['cpc'] . '",="' . $keyword['intitle'] . '",="' . $keyword['inurl'] . '",';
                        $output .= "\n";
                    }
                }
                $output .= "\n";
                $output .= "\n";
            }

            $filename = $fileName . ".csv";
            header('Content-type: application/csv');
            header('Content-Disposition: attachment; filename=' . $filename);

            echo wp_kses_data($output);
            exit;
        }


        public static function importKWS()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['project_id']) || !isset($_POST['data'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $project_id = intval($_POST['project_id']);
            $data       = urldecode(base64_decode(sanitize_text_field(wp_unslash($_POST['data']))));
            $data       = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $data), TRUE);

            $group_name = $data['name'];
            $data       = $data['data'];

            $group_data = [
                'project_id'   => $project_id,
                'group_name'   => $group_name,
                'title'        => $group_name,
                'url'          => '',
                'description'  => '',
                'h1'           => $group_name,
                'date_created' => gmdate('Y-m-d H:i:s'),

            ];
            $wpdb->insert('xag_groups', $group_data);
            $current_group_id   = $wpdb->insert_id;
            $processed_keywords = [];

            foreach ($data as $d) {
                if (in_array($d['Keyword'], $processed_keywords))
                    continue;
                if (strlen($d['Keyword']) < 3)
                    continue;
                $data = [
                    'group_id' => $current_group_id,
                    'keyword'  => $d['Keyword'],
                    'volume'   => $d['Volume'],
                    'cpc'      => $d['Cost Per Click'],
                    'rank'     => 0
                ];
                $wpdb->insert('xag_keywords', $data);
                $processed_keywords[] = $d['Keyword'];
            }

            xagio_json('success', 'KWS data successfully imported!');
        }

        // Import Project from CSV
        public static function importProject()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (isset($_FILES['file-import'])) {
                // Include the necessary WordPress file handling functions
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }

                $upload_overrides = [
                    'test_form' => false,
                    'memes'     => [
                        'csv' => 'text/csv'
                    ]
                ];

                // Use wp_handle_upload to manage file uploads
                $uploaded_file = wp_handle_upload($_FILES['file-import'], $upload_overrides);

                if ($uploaded_file && !isset($uploaded_file['error'])) {
                    $csv_path = $uploaded_file['file'];

                    $file_contents = xagio_file_get_contents($csv_path);

                    wp_delete_file($csv_path);

                    $file_contents = str_replace('=', '', $file_contents);
                    $file_contents = explode("\n", $file_contents);

                    $project_name = preg_split('/(?<!\s),(?!\s)/', $file_contents[0]);
                    $project_name = str_replace('"', '', $project_name[1]);

                    // Create project
                    $data = [
                        'project_name' => trim($project_name, ' ,'),
                        'date_created' => gmdate("Y-m-d H:i:s"),
                    ];

                    $wpdb->insert('xag_projects', $data);
                    $project_id = $wpdb->insert_id;

                    $expectGroup      = false;
                    $current_group_id = 0;
                    for ($i = 2; $i < sizeof($file_contents); $i++) {
                        $current = trim($file_contents[$i]);
                        if ($current == '' || $current == ',,,,,') {
                            $i           += 1;
                            $expectGroup = true;
                            continue;
                        }
                        if ($expectGroup == true) {
                            $expectGroup = false;
                            $data        = [
                                'project_id' => $project_id,
                            ];
                            $wpdb->insert('xag_groups', $data);
                            $current_group_id = $wpdb->insert_id;
                            $x                = preg_split('/(?<!\s),(?!\s)/', $current);
                            $data             = [
                                'group_name'   => esc_sql(str_replace('"', '', $x[0])),
                                'title'        => esc_sql(str_replace('"', '', $x[1])),
                                'url'          => esc_sql(str_replace('"', '', $x[2])),
                                'description'  => esc_sql(str_replace('"', '', $x[3])),
                                'h1'           => trim(esc_sql(str_replace('"', '', $x[4])), ' ,'),
                                'date_created' => gmdate('Y-m-d H:i:s'),
                            ];
                            $wpdb->update('xag_groups', $data, ['id' => $current_group_id]);
                            $i += 1;
                            continue;
                        }
                        $x = preg_split('/(?<!\s),(?!\s)/', $current);
                        $y = [];
                        foreach ($x as $value) {
                            $y[] = str_replace(',', '', $value);
                        }

                        $keyword_name    = trim(str_replace('"', '', $y[0]));
                        $keyword_volume  = str_replace('"', '', $y[1]);
                        $keyword_cpc     = str_replace('"', '', $y[2]);
                        $keyword_intitle = str_replace('"', '', $y[3]);
                        $keyword_inurl   = str_replace('"', '', $y[4]);

                        $data = [
                            'group_id' => $current_group_id,
                        ];

                        $wpdb->insert('xag_keywords', $data);
                        $keyword_id = $wpdb->insert_id;

                        $data = [
                            'volume'  => '',
                            'cpc'     => '',
                            'intitle' => '',
                            'inurl'   => ''
                        ];

                        if (!empty($keyword_name)) {
                            $data['keyword'] = $keyword_name;
                        } else {
                            $data['keyword'] = '';
                        }
                        if (!empty($keyword_volume) && $keyword_volume != 0 && $keyword_volume != '-1') {
                            $data['volume'] = $keyword_volume;
                        }
                        if (!empty($keyword_cpc) && $keyword_cpc != 0 && $keyword_cpc != '-1') {
                            $data['cpc'] = $keyword_cpc;
                        }
                        if (!empty($keyword_intitle) && $keyword_intitle != 0 && $keyword_intitle != '-1') {
                            $data['intitle'] = $keyword_intitle;
                        }
                        if (!empty($keyword_inurl) && $keyword_inurl != 0 && $keyword_inurl != '-1') {
                            $data['inurl'] = $keyword_inurl;
                        }

                        $wpdb->update('xag_keywords', $data, ['id' => $keyword_id]);
                    }
                } else {
                    // Handle upload error
                    xagio_jsonc([
                        "status"  => "error",
                        "message" => "Failed to upload the file: " . $uploaded_file['error'],
                    ]);
                }
            }

            // wp_2redirect(add_query_arg('page', 'xagio-projects', admin_url('admin.php')));
            die();
        }

        public static function apiRequest($apiEndpoint = NULL, $method = 'GET', $args = [], &$http_code = FALSE)
        {
            if ($apiEndpoint == NULL) {
                return FALSE;
            }

            if (!isset($_SERVER['SERVER_NAME'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // Set the domain name
            $domain = preg_replace('/^www\./', '', sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])));

            $response = wp_remote_post(XAGIO_PANEL_URL . "/api/" . $apiEndpoint, [
                'user-agent'  => "Xagio - " . XAGIO_CURRENT_VERSION . " ($domain)",
                'timeout'     => 30,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => TRUE,
                'method'      => $method,
                'body'        => $args,
            ]);

            if (is_wp_error($response)) {
                return FALSE;
            } else {

                $http_code = $response['response']['code'];

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

    }

}
