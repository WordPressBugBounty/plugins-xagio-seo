<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_KEYWORDS')) {

    class XAGIO_MODEL_KEYWORDS
    {

        public static function initialize()
        {
            add_action('wp_ajax_nopriv_xagio_queued_keywords_completed', [
                'XAGIO_MODEL_KEYWORDS',
                'queuedKeywordsCompleted'
            ]);
            add_action('wp_ajax_nopriv_xagio_queued_groups_completed', [
                'XAGIO_MODEL_KEYWORDS',
                'queuedGroupsCompleted'
            ]);

            if (!XAGIO_HAS_ADMIN_PERMISSIONS)
                return;

            add_action('admin_post_xagio_refreshXags', [
                'XAGIO_MODEL_KEYWORDS',
                'refreshXags'
            ]);
            add_action('admin_post_xagio_getVolumeAndCPC', [
                'XAGIO_MODEL_KEYWORDS',
                'getVolumeAndCPC'
            ]);
            add_action('admin_post_xagio_getKeywordData', [
                'XAGIO_MODEL_KEYWORDS',
                'getKeywordData'
            ]);
            add_action('admin_post_xagio_getKeyword', [
                'XAGIO_MODEL_KEYWORDS',
                'getKeyword'
            ]);
            add_action('admin_post_xagio_getKeywords', [
                'XAGIO_MODEL_KEYWORDS',
                'getKeywords'
            ]);
            add_action('admin_post_xagio_addKeyword', [
                'XAGIO_MODEL_KEYWORDS',
                'addKeyword'
            ]);
            add_action('admin_post_xagio_updateKeywords', [
                'XAGIO_MODEL_KEYWORDS',
                'updateKeywords'
            ]);
            add_action('admin_post_xagio_keywordChangeGroup', [
                'XAGIO_MODEL_KEYWORDS',
                'keywordChangeGroup'
            ]);
            add_action('admin_post_xagio_autoGenerateGroups', [
                'XAGIO_MODEL_KEYWORDS',
                'autoGenerateGroups'
            ]);
            add_action('admin_post_xagio_auditWebsite', [
                'XAGIO_MODEL_KEYWORDS',
                'auditWebsite'
            ]);
            add_action('admin_post_xagio_phraseMatch', [
                'XAGIO_MODEL_KEYWORDS',
                'phraseMatch'
            ]);
            add_action('admin_post_xagio_seedKeywords', [
                'XAGIO_MODEL_KEYWORDS',
                'seedKeywords'
            ]);
            add_action('admin_post_xagio_resetKeywordNotification', [
                'XAGIO_MODEL_KEYWORDS',
                'resetKeywordNotification'
            ]);
            add_action('admin_notices', [
                'XAGIO_MODEL_KEYWORDS',
                'KeywordNotification'
            ]);

            add_action('admin_post_xagio_consolidateKeywords', [
                'XAGIO_MODEL_KEYWORDS',
                'consolidateKeywords'
            ]);

            // Pro Rank Tracker
            add_action('admin_post_xagio_track_keywords_add', [
                'XAGIO_MODEL_KEYWORDS',
                'trackKeywordsAdd'
            ]);
            add_action('admin_post_xagio_track_keywords_get', [
                'XAGIO_MODEL_KEYWORDS',
                'trackKeywordsget'
            ]);
            add_action('admin_post_xagio_track_keywords_delete', [
                'XAGIO_MODEL_KEYWORDS',
                'trackKeywordsdelete'
            ]);

            add_action('admin_post_xagio_export_keywords', [
                'XAGIO_MODEL_KEYWORDS',
                'exportKeywords'
            ]);

        }

        // Download to CSV
        public static function exportKeywords()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // Export to csv.
            if (!isset($_GET['keyword_ids'])) {
                die('Keyword ID is missing!');
            }

            $keyword_ids = sanitize_text_field(wp_unslash($_GET['keyword_ids']));
            $keyword_ids = explode(",", $keyword_ids);

            if (sizeof($keyword_ids) > 0) {
                self::exportKeywordsToCsv($keyword_ids);
            } else {
                die('Keyword ID is missing!');
            }
        }

        public static function exportKeywordsToCsv($keyword_ids)
        {
            global $wpdb;

            // Prepare the keyword IDs for the query
            $keywordIdsJoined = $wpdb->prepare(implode(',', array_fill(0, count($keyword_ids), '%d')), ...$keyword_ids);

            // Fetch selected keywords
            $selectedKeywords = $wpdb->get_results(
                "SELECT * FROM xag_keywords WHERE id IN ($keywordIdsJoined) ORDER BY group_id", ARRAY_A
            );

            // Fetch total number of groups
            $totalGroups = $wpdb->get_results(
                "SELECT group_id FROM xag_keywords WHERE id IN ($keywordIdsJoined) GROUP BY group_id", ARRAY_A
            );
            $totalGroups = count($totalGroups);

            if (empty($selectedKeywords)) {
                die("No Keywords found!");
            }

            $projectName = '';

            if (isset($selectedKeywords[0]['group_id'])) {
                $groupID        = $selectedKeywords[0]['group_id'];
                $selectedGroups = $wpdb->get_row(
                    $wpdb->prepare("SELECT * FROM xag_groups WHERE id = %d", $groupID), ARRAY_A
                );
                $project_id     = $selectedGroups['project_id'];

                $projectData = $wpdb->get_row(
                    $wpdb->prepare("SELECT project_name FROM xag_projects WHERE id = %d", $project_id), ARRAY_A
                );

                if (isset($projectData['project_name'])) {
                    $projectName = $projectData['project_name'];
                } else {
                    die("Project not found");
                }
            } else {
                die("Group not found");
            }

            $output = '"Project Name","' . $projectName . '",';
            $output .= "\n";
            $output .= '"Total Groups","' . $totalGroups . '",';
            $output .= "\n";

            $current_group = 0;

            foreach ($selectedKeywords as $keyword) {
                if ($current_group != $keyword['group_id']) {
                    $group = $wpdb->get_row(
                        $wpdb->prepare("SELECT * FROM xag_groups WHERE id = %d", $keyword['group_id']), ARRAY_A
                    );

                    $output        .= "\n";
                    $output        .= 'Group,Title,URL,DESC,H1,';
                    $output        .= "\n";
                    $output        .= '"' . $group['group_name'] . '","' . $group['title'] . '","' . $group['url'] . '","' . $group['description'] . '","' . $group['h1'] . '",';
                    $output        .= "\n";
                    $current_group = $keyword['group_id'];
                }

                $output .= '"' . $keyword['keyword'] . '",="' . $keyword['volume'] . '",="' . $keyword['cpc'] . '",="' . $keyword['intitle'] . '",="' . $keyword['inurl'] . '",';
                $output .= "\n";
            }

            $filename = sanitize_file_name($projectName) . ".csv";

            header('Content-type: application/csv');
            header('Content-Disposition: attachment; filename=' . $filename);

            echo wp_kses_data($output);
            exit;
        }


        public static function customFromName($name)
        {
            return "Xagio";
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
            $creation_query  = 'CREATE TABLE xag_keywords (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `group_id` int(11),
                    `keyword` varchar(255),
                    `volume` varchar(255),
                    `cpc` varchar(255),
                    `inurl` varchar(255),
                    `intitle` varchar(255),    
                    `date_created` datetime,
                    `position` int(11) default 999,
                    `queued` int(1) default 0,
                    `rank` longtext,
                    PRIMARY KEY  (`id`)
                ) ' . $charset_collate . ';';
            return @dbDelta($creation_query);
        }

        public static function removeTable()
        {
            global $wpdb;
            $wpdb->query('DROP TABLE IF EXISTS xag_keywords;');
        }



        //--------------------------------------------
        //
        //               Functions
        //
        //--------------------------------------------

        public static function resetKeywordNotification()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['projectId'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            update_option('XAGIO_PROJECT_ALERT_ID', intval($_POST['projectId']));
            update_option('XAGIO_KEYWORD_ERROR', '');
        }

        public static function KeywordNotification()
        {
            $XAGIO_KEYWORD_ERROR = get_option('XAGIO_KEYWORD_ERROR');
            if ($XAGIO_KEYWORD_ERROR) {
                $class   = 'logo-nag-xagio logo-nag-block-xagio notice notice-error is-dismissible keyword_notification';
                $message = $XAGIO_KEYWORD_ERROR;

                printf('<div data-id="' . esc_html($message['project_id']) . '" class="%1$s"><p>%2$s</p></div>', esc_attr($class), '<b>Xagio</b> - Auto Generate Groups for the keyword " <b>' . esc_html($message['keyword']) . '</b> " failed.<br> Sorry, but Adwords will not report data to us for this keyword. Please try with another seed keyword.');
            }
        }

        public static function refreshXags()
        {
            $http_code = 0;
            $result    = XAGIO_API::apiRequest($endpoint = 'info', $method = 'GET', [
                'type' => 'xags',
            ], $http_code);

            if ($http_code == 200) {

                $result = [
                    'xags'           => (float)$result['xags'] ?? 0,
                    'xags_allowance' => (float)$result['xags_allowance'] ?? 0,
                    'xags_total'     => (float)$result['xags_allowance_max'] ?? 0,
                    'xags_cost'      => $result['xags_cost'] ?? [],
                ];

                xagio_json('success', 'Successfully retrieved XAGS.', $result);
            } else {
                xagio_json('error', $result);
            }
        }

        public static function jsonPostRequest($url, $postData)
        {
            $postDataEncoded = wp_json_encode($postData);

            $response = wp_remote_post($url, [
                'body'        => $postDataEncoded,
                'headers'     => [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept'       => 'application/json',
                ],
                'timeout'     => 30,
                'httpversion' => '1.1',
                'compress'    => true,
            ]);

            if (is_wp_error($response)) {
                return false; // Handle the error as needed
            }

            $result = wp_remote_retrieve_body($response);
            return json_decode($result, true);
        }

        public static function sanitazeKeyword($string, $type)
        {
            $string = str_replace([
                "\r",
                "\n",
                "\t",
                "\v"
            ], ' ', $string);
            $string = trim($string);
            switch ($type) {
                case 'phrase':
                    $string = '"' . $string . '"';
                    break;
                case 'intitle':
                    $string = 'intitle:"' . $string . '"';
                    break;
                case 'inurl':
                    $string = 'inurl:"' . $string . '"';
                    break;
            }
            return urlencode(str_replace(' ', '+', $string));
        }

        public static function parseResults($string)
        {
            $result_string = trim($string);
            $result_string = preg_replace('~<nobr(.*?)</nobr>~', "", $result_string);
            $result_string = preg_replace("/[^0-9]/", "", $result_string);
            $value         = doubleval($result_string);
            return $value;
        }

        public static function getVolumeAndCPC()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (isset($_POST['keywords'], $_POST['ids'], $_POST['language'], $_POST['location'], $_POST['disable_cache'])) {

                $real_ids = sanitize_text_field(wp_unslash($_POST['ids']));
                $language = sanitize_text_field(wp_unslash($_POST['language']));
                $location = sanitize_text_field(wp_unslash($_POST['location']));
                $cache    = sanitize_text_field(wp_unslash($_POST['disable_cache']));

                $keywords = explode(',', sanitize_text_field(wp_unslash($_POST['keywords'])));
                $ids      = explode(',', sanitize_text_field(wp_unslash($_POST['ids'])));

                if (sizeof($keywords) < 1 || sizeof($ids) < 1) {
                    xagio_json('error', 'You must send at least one keyword to analysis.');
                    return;
                }

                $output_array    = [];
                $filter_keywords = [];

                for ($i = 0; $i < sizeof($keywords); $i++) {

                    $keyword = $keywords[$i];
                    $id      = $ids[$i];

                    // Removed special characters and html entity from keyword
                    if (preg_match("/[^a-zA-Z0-9_' \p{L}&-]/u", $keyword)) {
                        $keyword = html_entity_decode($keyword);
                        $keyword = preg_replace("/[^a-zA-Z0-9_' \p{L}-]/u", '', $keyword);
                        $wpdb->update('xag_keywords', [
                            'keyword' => $keyword
                        ], [
                            'id' => $id
                        ]);
                    }

                    $keyword        = strtolower($keyword);
                    $output_array[] = [
                        'id'             => $id,
                        'keyword'        => $keyword,
                        'search_volume'  => 0,
                        'cost_per_click' => 0.0,
                    ];

                    $filter_keywords[] = $keyword;
                }

                $http_code = 0;
                $result    = XAGIO_API::apiRequest($endpoint = 'keywords_volume', $method = 'POST', [
                    'keywords' => join(',', $filter_keywords),
                    'ids'      => $real_ids,
                    'language' => $language,
                    'location' => $location,
                    'cache'    => $cache
                ], $http_code);

                if ($http_code == 200) {
                    // Mark them as scraped
                    for ($i = 0; $i < sizeof($ids); $i++) {
                        $wpdb->update('xag_keywords', [
                            'queued' => 2,
                            'volume' => -1,
                            'cpc'    => -1,
                        ], [
                            'id' => $ids[$i],
                        ]);
                    }
                    // Store batch ID
                    $BATCH_ID = $result['message'];
                    $wpdb->insert('xag_volume_batches', [
                        'batch_id'     => $BATCH_ID,
                        'date_created' => gmdate('Y-m-d H:i:s'),
                    ]);

                    xagio_json('success', 'Successfully pushed keywords into analysis queue.');
                } else {
                    xagio_json('error', $result['message']);
                }
            }
        }

        public static function getKeywordData()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (isset($_POST['keywords'], $_POST['ids'], $_POST['language'], $_POST['location'])) {

                $keywords = sanitize_text_field(wp_unslash($_POST['keywords']));
                $ids      = sanitize_text_field(wp_unslash($_POST['ids']));
                $language = sanitize_text_field(wp_unslash($_POST['language']));
                $location = sanitize_text_field(wp_unslash($_POST['location']));

                $keywords = explode(',', $keywords);
                $ids      = explode(',', $ids);

                if (sizeof($keywords) < 1 || sizeof($ids) < 1) {
                    xagio_json('error', 'You must send at least one keyword to analysis.');
                    return;
                }

                $http_code = 0;
                $result    = XAGIO_API::apiRequest($endpoint = 'keywords', $method = 'POST', [
                    'keywords' => $keywords,
                    'ids'      => $ids,
                    'language' => $language,
                    'location' => $location,
                ], $http_code);

                if ($http_code == 200) {
                    // Mark them as scraped
                    for ($i = 0; $i < sizeof($ids); $i++) {
                        $wpdb->update('xag_keywords', [
                            'queued'  => 1,
                            'inurl'   => -1,
                            'intitle' => -1,
                        ], [
                            'id' => $ids[$i],
                        ]);
                    }
                    // Store batch ID
                    $BATCH_ID = $result['message'];
                    $wpdb->insert('xag_batches', [
                        'batch_id'     => $BATCH_ID,
                        'date_created' => gmdate('Y-m-d H:i:s'),
                    ]);

                    xagio_json('success', 'Successfully pushed keywords into analysis queue.');
                } else {
                    xagio_json('error', $result);
                }
            } else {
                xagio_json('error', 'Invalid request type.');
            }
        }

        public static function updateKeywords()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['group_id'], $_POST['keywords'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $group_id = intval($_POST['group_id']);
            $keywords = map_deep(wp_unslash($_POST['keywords']), 'sanitize_text_field');

            foreach ($keywords as $keyword) {
                $id = $keyword['id'];
                unset($keyword['id']);

                $fields = [
                    'volume',
                    'inurl',
                    'intitle'
                ];

                foreach ($fields as $f) {
                    if (isset($keyword[$f])) {
                        $keyword[$f] = str_replace(',', '', $keyword[$f]);
                    }
                }

                $wpdb->update('xag_keywords', $keyword, [
                    'group_id' => $group_id,
                    'id'       => $id,
                ]);
            }
        }

        public static function keywordChangeGroup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['original_group_id'], $_POST['target_group_id'], $_POST['keyword_ids'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $original_group_id = intval($_POST['original_group_id']);
            $target_group_id   = intval($_POST['target_group_id']);
            $keyword_ids       = sanitize_text_field(wp_unslash($_POST['keyword_ids']));

            $keyword_ids = explode(',', $keyword_ids);
            foreach ($keyword_ids as $keyword_id) {
                $wpdb->update('xag_keywords', [
                    'group_id' => $target_group_id,
                ], [
                    'group_id' => $original_group_id,
                    'id'       => $keyword_id,
                ]);
            }
        }

        public static function consolidateKeywords()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['project_id'], $_POST['group_name'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $projectId  = abs(intval($_POST['project_id']));
            $group_name = sanitize_text_field(wp_unslash($_POST['group_name']));

            if (empty($group_name)) {
                $group_name = "New Group - " . gmdate("Y-m-d");
            }

            if ($projectId < 1) {
                xagio_json('error', 'Project ID is missing');
            }

            // Fetch group IDs for the project
            $groups = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id FROM xag_groups WHERE project_id = %d", $projectId
                ), ARRAY_A
            );

            $oldGroupIds = array_map(function ($group) {
                return intval($group['id']);
            }, $groups);

            if (empty($oldGroupIds)) {
                xagio_json('error', 'No groups found for the project');
            }

            // Insert the new group
            $data = [
                'project_id' => $projectId,
                'group_name' => $group_name
            ];

            $wpdb->insert('xag_groups', $data);
            $group_id = $wpdb->insert_id;

            if (!$group_id) {
                xagio_json('error', 'Failed to create new group');
            }

            // Create placeholders for each old group ID
            $placeholders = implode(', ', array_fill(0, count($oldGroupIds), '%d'));

            // Merge the new group ID with the old group IDs for the prepared statement
            $prepare_values = array_merge([$group_id], $oldGroupIds);

            // Prepare and execute the query securely
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE xag_keywords SET group_id = %d WHERE group_id IN ($placeholders)", ...$prepare_values
                )
            );

            xagio_json('success', 'Successfully consolidated keywords into a new group');
        }


        public static function seedKeywords()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['project_id'], $_POST['group_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            global $wpdb;

            $project_id = intval($_POST['project_id']);
            $group_id   = intval($_POST['group_id']);

            $keywords = false;
            if (!empty($_POST['seed_keywords'])) {
                $keywords = sanitize_text_field(wp_unslash($_POST['seed_keywords']));
            }

            $group_name = false;
            if (!empty($_POST['seed_group_name'])) {
                $group_name = sanitize_text_field(wp_unslash($_POST['seed_group_name']));
            }

            $word_match = false;
            if (!empty($_POST['word_match'])) {
                $word_match = filter_var(wp_unslash($_POST['word_match']), FILTER_VALIDATE_BOOLEAN);
            }

            if (empty($keywords)) {
                xagio_json('error', 'Please enter any keyword!');
            }

            $keywords = explode(",", $keywords);

            if (empty($group_name)) {
                $group_name = "Seed Group";

                if (isset($keywords[0]))
                    $group_name = $keywords[0];
            }

            if ($group_id === 0) {
                $group_ids = $wpdb->get_results(
                    $wpdb->prepare("SELECT id FROM xag_groups WHERE project_id = %d", $project_id), ARRAY_A
                );
            } else {
                $group_ids = $wpdb->get_results(
                    $wpdb->prepare("SELECT id FROM xag_groups WHERE id = %d", $group_id), ARRAY_A
                );
            }


            $project_group_ids = [];
            foreach ($group_ids as $group_id) {
                $project_group_ids[] = $group_id['id'];
            }

            $likeKeywords = [];
            $likeValues   = [];

            foreach ($keywords as $keyword) {
                $keyword = trim($keyword);
                if (isset($word_match) && $word_match) {
                    $likeKeywords[] = "keyword REGEXP %s";
                    $likeValues[]   = "\\b{$keyword}\\b";
                } else {
                    // Use CHAR(37) to represent '%'
                    $likeKeywords[] = "keyword LIKE CONCAT(CHAR(37), %s, CHAR(37))";
                    $likeValues[]   = $wpdb->esc_like($keyword);
                }
            }
            $likeKeywords = implode(" OR ", $likeKeywords);

            $groupIdsPlaceholders = implode(", ", array_fill(0, count($project_group_ids), '%d'));
            $sql                  = "SELECT id FROM xag_keywords WHERE group_id IN ($groupIdsPlaceholders)";
            if (!empty($likeKeywords)) {
                $sql .= " AND ($likeKeywords)";
            }

            $queryParams    = array_merge(array_map('absint', $project_group_ids), $likeValues);
            $updateKeywords = $wpdb->get_results($wpdb->prepare("$sql", ...$queryParams), ARRAY_A);

            $keywordIDs = [];
            foreach ($updateKeywords as $uk) {
                $keywordIDs[] = $uk['id'];
            }

            if (sizeof($keywordIDs) < 1) {
                xagio_json('error', 'No keywords found in this project. Please change your seed keywords.');
            }

            $wpdb->insert('xag_groups', [
                'project_id' => $project_id,
                'group_name' => $group_name
            ]);

            $group_id = $wpdb->insert_id;

            foreach ($keywordIDs as $kwid) {
                $wpdb->update('xag_keywords', [
                    'group_id' => $group_id
                ], [
                    'id' => $kwid
                ]);
            }

            xagio_json('success', 'Successfully created new group with seed keyword(s) found in this Project');
        }

        public static function phraseMatch($return_output = false)
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            $project_name   = isset($_POST['project_name']) ? sanitize_text_field(wp_unslash($_POST['project_name'])) : 'n/a';
            $modal_group_id = isset($_POST['group_id']) ? @intval($_POST['group_id']) : 0;
            $keywords       = isset($_POST['keywords']) ? array_map('sanitize_text_field', wp_unslash($_POST['keywords'])) : [];

            $minMatchingWords         = isset($_POST['min_match']) ? sanitize_text_field(wp_unslash($_POST['min_match'])) : 2;
            $minKeywordsInGroup       = isset($_POST['min_kws']) ? sanitize_text_field(wp_unslash($_POST['min_kws'])) : 2;
            $includePrepositions      = isset($_POST['include_prepositions']) ? filter_var(wp_unslash($_POST['include_prepositions']), FILTER_VALIDATE_BOOLEAN) : FALSE;
            $excludedWords            = isset($_POST['excluded_words']) ? sanitize_text_field(wp_unslash($_POST['excluded_words'])) : '';
            $wordSimilarity           = isset($_POST['word_similarity']) ? sanitize_text_field(wp_unslash($_POST['word_similarity'])) : 80;
            $cluster_into_new_project = isset($_POST['cluster_in_new_project']) ? @sanitize_text_field(wp_unslash($_POST['cluster_in_new_project'])) : '';
            $new_project              = false;

            if (isset($cluster_into_new_project) && $cluster_into_new_project == '1') {
                $new_project = true;
            }

            if (!empty($excludedWords)) {
                $excludedWords = explode(',', $excludedWords);
            }

            $groups = [];
            $words  = [];
            $used   = [];

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

            $groups          = $new_groups;
            $group_ids_array = [];

            if (!$new_project) {
                $group_ids_array = explode(',', $modal_group_id);
                // Update the existing Project with new groups
                $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
            } else {
                // Create a new Project
                $dateCreated = gmdate('Y-m-d H:i:s');
                $wpdb->insert('xag_projects', [
                    'project_name' => $project_name,
                    'date_created' => $dateCreated,
                ]);
                $project_id = $wpdb->insert_id;
            }

            foreach ($groups as $group => $keywords) {

                $data = [
                    'project_id' => $project_id,
                    'group_name' => $group,
                    'title'      => '',
                    'url'        => self::stripSymbols($group),
                    'h1'         => '',
                ];
                $wpdb->insert('xag_groups', $data);
                $group_id = $wpdb->insert_id;

                foreach ($keywords as $keyword) {

                    $kw_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM xag_keywords WHERE keyword = %s", $keyword), ARRAY_A);

                    if (empty($kw_data))
                        continue;

                    $keyword_data = [
                        'group_id' => $group_id,
                        'keyword'  => $keyword,
                        'volume'   => $kw_data['volume'],
                        'cpc'      => $kw_data['cpc'],
                        'inurl'    => $kw_data['inurl'],
                        'intitle'  => $kw_data['intitle'],
                        'rank'     => $kw_data['rank'],
                    ];


                    $wpdb->insert('xag_keywords', $keyword_data);
                }

            }

            if (!$new_project) {
                foreach ($group_ids_array as $group_id) {
                    $wpdb->query($wpdb->prepare("DELETE g, k FROM xag_groups g LEFT JOIN xag_keywords k ON g.id = k.group_id WHERE g.id = %d", $group_id));
                }
            }

            XAGIO_MODEL_KEYWORDS::removeDuplicatedKeywords($project_id);

            if ($return_output === true) {
                return $project_id;
            } else {
                $message = 'Your new project with clustered keywords has been successfully created!';
                if (!$new_project) {
                    $message = 'Your new groups with clustered keywords has been successfully created and added to current project!';
                }

                xagio_json('success', $message, [
                    'name' => $project_name,
                    'id'   => $project_id
                ]);
            }
        }

        public static function auditWebsite($type = false)
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            // get the current domain if not set

            if (!isset($_POST['domain']) && !isset($_SERVER['HTTP_HOST'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $domain = isset($_POST['domain']) ? sanitize_text_field(wp_unslash($_POST['domain'])) : sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST']));

            if ($type === false || $type === "") {
                $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
            }

            $lang                 = isset($_POST['lang']) ? sanitize_text_field(wp_unslash($_POST['lang'])) : 'en';
            $lang_code            = isset($_POST['lang_code']) ? sanitize_text_field(wp_unslash($_POST['lang_code'])) : 'US';
            $limit                = isset($_POST['limit']) ? intval($_POST['limit']) : 1000;  // Use intval for numeric values
            $track                = isset($_POST['track']) ? sanitize_text_field(wp_unslash($_POST['track'])) : 'off';
            $skip_empty           = isset($_POST['skip_empty']) ? sanitize_text_field(wp_unslash($_POST['skip_empty'])) : 'off';
            $ignore_local         = isset($_POST['ignore_local']) ? sanitize_text_field(wp_unslash($_POST['ignore_local'])) : 'off';
            $current_project_id   = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;  // Use intval for numeric values
            $keyword_contain_text = isset($_POST['audit_main_keyword_contain']) ? sanitize_text_field(wp_unslash($_POST['audit_main_keyword_contain'])) : '';
            $internal             = isset($_POST['audit_type']) ? sanitize_text_field(wp_unslash($_POST['audit_type'])) : '';
            $match_case_url       = isset($_POST['match_case_url']) ? intval($_POST['match_case_url']) : 0;
            $volume_min           = isset($_POST['volume-min']) ? intval($_POST['volume-min']) : 0;
            $volume_max           = isset($_POST['volume-max']) ? intval($_POST['volume-max']) : 10000;
            $rank_min             = isset($_POST['rank-min']) ? intval($_POST['rank-min']) : 1;
            $rank_max             = isset($_POST['rank-max']) ? intval($_POST['rank-max']) : 100;


            $volume_words = "";
            if ($volume_min !== 0 && $volume_max !== 10000) {
                $volume_words = "Vol ($volume_min - $volume_max) ";
            } else if ($volume_min === 0 && $volume_max < 10000) {
                $volume_words = "Vol ( < $volume_max ) ";
            } else if ($volume_min > 0 && $volume_max === 10000) {
                $volume_words = "Vol ( > $volume_min ) ";
            }


            $rank_words = "";
            if ($rank_min !== 1 && $rank_max !== 100) {
                $rank_words = " Rank ($rank_min - $rank_max) ";
            } else if ($rank_min === 1 && $rank_max < 100) {
                $rank_words = " Rank ( < $rank_max ) ";
            } else if ($rank_min > 1 && $rank_max === 100) {
                $rank_words = " Rank ( > $rank_min ) ";
            }

            $domain = trim($domain);
            $scheme = wp_parse_url($domain);
            if (!isset($scheme['scheme']))
                $domain = "https://{$domain}";

            $wp_parse_url = wp_parse_url($domain);

            $path = $wp_parse_url['path'] ?? '/';

            $filters = [
                'keyword_contain' => 0,
                'keyword'         => '',
                'search_volume'   => [
                    $volume_min,
                    $volume_max
                ],
                'rank'            => [
                    $rank_min,
                    $rank_max
                ],
            ];

            if (strlen($keyword_contain_text) > 0) {
                $filters['keyword_contain'] = 1;
                $filters['keyword']         = $keyword_contain_text;
            }

            if ($path !== '/') {
                $filters['is_relative']    = 1;
                $filters['path']           = $path;
                $filters['match_case_url'] = $match_case_url;
            } else {
                // If it's Homepage check if match case is ON and filter only for Homepage
                if($match_case_url === 1) {
                    $filters['is_relative']     = "1";
                    $filters['path']            = $path;
                    $filters['match_case_url']  = $match_case_url;
                }
            }

            if ($current_project_id != 0) {
                $project_id = $current_project_id;
            } else {
                $project_name = "AUDIT " . $volume_words . $rank_words . " - " . $domain;

                if ($internal === 'internal') {
                    $project_name = "IMPORT - " . $domain;
                }

                $wpdb->insert('xag_projects', [
                    'project_name' => $project_name,
                    'date_created' => gmdate('Y-m-d H:i:s')
                ]);

                $project_id = $wpdb->insert_id;
            }

            $groups   = [];
            $keywords = [];

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

                // The Loop for Posts & Pages
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
                        'title'        => XAGIO_MODEL_SEO::replaceVars(get_post_meta($post->ID, 'XAGIO_SEO_TITLE', TRUE), $post->ID),
                        'description'  => XAGIO_MODEL_SEO::replaceVars(get_post_meta($post->ID, 'XAGIO_SEO_DESCRIPTION', TRUE), $post->ID)
                    ]);

                    $groups[] = $wpdb->insert_id;
                }

                // Now, create groups for each "location" taxonomy term
                $location_terms = get_terms([
                    'taxonomy'   => 'location',
                    'hide_empty' => false,
                    // Get all terms, even those without posts
                ]);


                // The Loop for Location Terms
                if (!($location_terms instanceof WP_Error)) {

                    foreach ($location_terms as $term) {
                        $term_link  = str_replace(home_url(), '', get_term_link($term)); // Get the link for the location term
                        $group_name = $term->name;

                        // Check if title and description are set in term meta
                        $term_title       = get_term_meta($term->term_id, 'XAGIO_SEO_TITLE', true);
                        $term_description = get_term_meta($term->term_id, 'XAGIO_SEO_DESCRIPTION', true);

                        // If either the title or description is empty, retrieve them from the first 'magicpage' post
                        if (empty($term_title) || empty($term_description)) {
                            // Query for the first post of post type 'magicpage'
                            $magicpage_post_args = [
                                'posts_per_page' => 1,
                                'post_type'      => 'magicpage',
                                'orderby'        => 'ID',
                                'order'          => 'ASC',
                                'post_status'    => 'publish',
                            ];
                            $magicpage_posts     = get_posts($magicpage_post_args);

                            if (!empty($magicpage_posts)) {
                                $magicpage_post = $magicpage_posts[0]; // Get the first magicpage post

                                // If the term title is empty, use the magicpage post title
                                if (empty($term_title)) {
                                    $term_title = get_post_meta($magicpage_post->ID, 'XAGIO_SEO_TITLE', true);
                                }

                                // If the term description is empty, use the magicpage post meta description
                                if (empty($term_description)) {
                                    $term_description = get_post_meta($magicpage_post->ID, 'XAGIO_SEO_DESCRIPTION', true);
                                }
                            }
                        }

                        $wpdb->insert('xag_groups', [
                            'group_name'   => $group_name,
                            'h1'           => $term->name,
                            'project_id'   => $project_id,
                            'date_created' => gmdate('Y-m-d H:i:s'),
                            'id_taxonomy'  => $term->term_id,
                            'url'          => $term_link,
                            'title'        => XAGIO_MODEL_SEO::replaceVars($term_title, $term->term_id),
                            'description'  => XAGIO_MODEL_SEO::replaceVars($term_description, $term->term_id)
                        ]);

                        $group    = $wpdb->insert_id;
                        $groups[] = $group;
                    }

                }

            }

            $http_code = 0;
            $result    = XAGIO_API::apiRequest($endpoint = 'find_ranked_keywords', $method = 'POST', [
                'domain'    => $domain,
                'lang'      => $lang,
                'lang_code' => $lang_code,
                'limit'     => $limit,
                'filters'   => $filters,
                'type'      => $type
            ], $http_code);

            $domain = preg_replace('/^(?!https?:\/\/)/', 'http://', $domain);
            $domain = wp_parse_url($domain);
            $domain = str_replace("www.", "", $domain['host']);

            $local_domain = wp_parse_url(admin_url());
            $local_domain = $local_domain['host'];
            $local_domain = str_replace('www.', '', $local_domain);

            if ($http_code == 200) {

                if (isset($result['data']['ranked']) && $result['data']['ranked'] === 'No data') {
                    if ($type == 'migration') {
                        return;
                    }

                    if (empty($groups)) {
                        xagio_json('error', 'Audit completed but no ranking keywords found. Please try another domain.');
                    }
                } else {
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
                                'url'             => $row['relative_url'],
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
                            'rank'     => $row['position'],
                        ];

                        $wpdb->insert('xag_keywords', $keyword_data);
                        $kid = $wpdb->insert_id;

                        $keyword_data['id'] = $kid;
                        $keywords[]         = $keyword_data;

                    }
                }

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

            // Track Keywords
            if ($track == 'on') {

                $search_engines = isset($_POST['auditWebsiteSearchEngine']) ? sanitize_text_field(wp_unslash($_POST['auditWebsiteSearchEngine'])) : false;
                $location       = isset($_POST['auditWebsiteSearchLocation']) ? sanitize_text_field(wp_unslash($_POST['auditWebsiteSearchLocation'])) : false;

                if (!empty($keywords) || !empty($search_engines)) {
                    $rankKeywords = [];
                    $ranks        = [];
                    foreach ($keywords as $keyword) {
                        $rankKeywords[] = $keyword['keyword'];
                        $ranks[]        = $keyword['rank'];
                    }

                    $result = XAGIO_API::apiRequest($endpoint = 'rank_tracker', $method = 'POST', [
                        'url'             => site_url(),
                        'keywords'        => $rankKeywords,
                        'search_engines'  => $search_engines,
                        'search_location' => $location,
                        'ranks'           => $ranks,
                    ], $http_code);

                }
            }
            if ($type == 'migration') {
                return;
            }
            xagio_json('success', 'Audit completed.', $project_id);

        }

        public static function autoGenerateGroups()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            $seed_keyword      = isset($_POST['seed_keyword']) ? sanitize_text_field(wp_unslash($_POST['seed_keyword'])) : '';
            $projectID         = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
            $min_search_volume = isset($_POST['min_search_volume']) ? sanitize_text_field(wp_unslash($_POST['min_search_volume'])) : '';
            $min_cpc           = isset($_POST['min_cpc']) ? sanitize_text_field(wp_unslash($_POST['min_cpc'])) : '';
            $language          = isset($_POST['language']) ? sanitize_text_field(wp_unslash($_POST['language'])) : '';
            $location          = isset($_POST['location']) ? sanitize_text_field(wp_unslash($_POST['location'])) : '';
            $max_keywords      = isset($_POST['max_keywords']) ? sanitize_text_field(wp_unslash($_POST['max_keywords'])) : '';
            $cache             = isset($_POST['disable_cache']) ? sanitize_text_field(wp_unslash($_POST['disable_cache'])) : '';

            if (empty($cache)) {
                $cache = 'off';
            }

            // Set or retrieve the API key transient
            $api_key_name      = 'XAGIO_API_TEMP_' . wp_hash(microtime());
            $api_key_transient = get_transient($api_key_name);
            if (!$api_key_transient) {
                $api_key_transient = wp_hash(md5(XAGIO_API::getAPIKey() . microtime())); // Retrieve the API key from wherever it's stored.
                set_transient($api_key_name, $api_key_transient, DAY_IN_SECONDS); // Set transient for 24 hours
            }

            $http_code = 0;
            $result    = XAGIO_API::apiRequest($endpoint = 'keywords_generate_groups', $method = 'POST', [
                'seed_keyword' => $seed_keyword,
                'max_keywords' => $max_keywords,
                'language'     => $language,
                'location'     => $location,
                'callback'     => XAGIO_MODEL_SETTINGS::getApiUrl() . 'api-external/?' . http_build_query([
                        'class'             => 'XAGIO_MODEL_KEYWORDS',
                        'function'          => 'queuedGroupsCompleted',
                        'min_search_volume' => $min_search_volume,
                        'min_cpc'           => $min_cpc,
                        'project_id'        => $projectID,
                        'max_keywords'      => $max_keywords,
                        'api_key_name'      => $api_key_name,
                        'api_key_value'     => $api_key_transient
                    ]),
                'cache'        => $cache,
            ], $http_code);


            if ($http_code == 200) {

                if ($result['status'] == 'queued') {

                    $wpdb->update('xag_projects', ['status' => 'queued'], ['id' => $projectID]);
                    xagio_json('success', 'Auto-Generate Groups successfully queued! Please check back later for results.', $result);

                } else if ($result['status'] == 'results') {

                    $wpdb->update('xag_projects', ['status' => 'completed'], ['id' => $projectID]);
                    self::processQueuedGroups($result['data'], $min_search_volume, $min_cpc, $projectID, $max_keywords);

                } else {
                    xagio_json('error', $result['message']);
                }

            } else {
                xagio_json('error', $result);
            }

        }

        public static function processQueuedGroups($results, $min_search_volume, $min_cpc, $project_id, $max_keywords)
        {
            global $wpdb;

            if (empty($project_id) || empty($max_keywords)) {
                xagio_json('error', 'Something went wrong, please contact support!');
            }

            $formattedGroupData = [];

            foreach ($results as $d) {
                if (!isset($formattedGroupData[$d['category']])) {
                    $formattedGroupData[$d['category']] = [];
                }

                $volume_value = $d['search_volume'];
                $cpc_value    = $d['cost_per_click'];

                if ($volume_value < $min_search_volume) {
                    continue;
                }
                if ($cpc_value < $min_cpc) {
                    continue;
                }

                $formattedGroupData[$d['category']][] = [
                    'keyword' => str_replace("'", '', $d['keyword']),
                    'volume'  => $volume_value,
                    'cpc'     => $cpc_value,
                ];

                if (sizeof($formattedGroupData) >= $max_keywords) {
                    break;
                }
            }

            // Remove all keywords from the project
            $groups = $wpdb->get_results($wpdb->prepare("SELECT * FROM xag_groups WHERE project_id = %d and group_name = '' and title IS NULL;", $project_id), ARRAY_A);
            if (is_array($groups)) {
                foreach ($groups as $group) {
                    $wpdb->query($wpdb->prepare("DELETE FROM xag_keywords WHERE group_id = %d", $group['id']));
                }
            }

            // Remove all groups from the project
            $wpdb->query($wpdb->prepare("DELETE FROM xag_groups WHERE project_id = %d;", $project_id));

            $keywords = [];
            foreach ($formattedGroupData as $groupName => $groupData) {
                if (sizeof($groupData) < 1) {
                    continue;
                }

                $data = [
                    'project_id' => $project_id,
                    'group_name' => $groupName,
                    'title'      => '',
                    'url'        => self::stripSymbols($groupName),
                    'h1'         => '',
                ];
                $wpdb->insert('xag_groups', $data);
                $group_id = $wpdb->insert_id;

                foreach ($groupData as $keyword) {
                    $keyword_data = [
                        'group_id' => $group_id,
                        'keyword'  => $keyword['keyword'],
                        'volume'   => $keyword['volume'],
                        'cpc'      => number_format($keyword['cpc'], 2),
                    ];
                    $wpdb->insert('xag_keywords', $keyword_data);
                    $keywords[] = $wpdb->insert_id;
                }
            }

            // Number of credits to deduct
            $keyword_credits = sizeof($formattedGroupData);

            if (sizeof($keywords) == 0) {
                update_option('XAGIO_KEYWORD_ERROR', [
                    'project_id' => $project_id,
                    'keyword'    => $results[0]['keyword']
                ]);
            }

            xagio_json('success', 'Successfully generated groups from seed keyword.');
        }

        public static function queuedGroupsCompleted()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            $min_search_volume = isset($_GET['min_search_volume']) ? sanitize_text_field(wp_unslash($_GET['min_search_volume'])) : '';
            $min_cpc           = isset($_GET['min_cpc']) ? sanitize_text_field(wp_unslash($_GET['min_cpc'])) : '';
            $project_id        = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
            $max_keywords      = isset($_GET['max_keywords']) ? sanitize_text_field(wp_unslash($_GET['max_keywords'])) : '';
            $data              = false;

            if (isset($_POST['data'])) {
                if (!empty($_POST['data'])) {
                    $data = map_deep(wp_unslash($_POST['data']), 'sanitize_text_field');
                }
            }

            if (isset($_POST['message'])) {
                update_option('XAGIO_KEYWORD_ERROR', [
                    'project_id' => $project_id,
                    'keyword'    => $data[0]['keyword']
                ]);
            }

            if (!$data) {
                xagio_json('error', 'Nothing to see here!');
            }

            $wpdb->update('xag_projects', ['status' => 'completed'], ['id' => $project_id]);

            self::processQueuedGroups($data, $min_search_volume, $min_cpc, $project_id, $max_keywords);
        }


        public static function addKeyword()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['group_id'], $_POST['keywords'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $group_id = intval($_POST['group_id']);
            $keywords = sanitize_textarea_field(wp_unslash($_POST['keywords']));

            $keywords = explode("\n", $keywords);

            foreach ($keywords as $keyword) {

                $wpdb->insert('xag_keywords', [
                    'keyword'      => stripslashes($keyword),
                    'group_id'     => $group_id,
                    'rank'         => '0',
                    'date_created' => gmdate('Y-m-d H:i:s'),
                ]);

            }
        }

        public static function getKeyword($return = FALSE, $gid = 0, $kid = 0)
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['keyword_id'], $_POST['group_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $keyword_id = ($kid == 0) ? intval($_POST['keyword_id']) : $kid;
            $group_id   = ($gid == 0) ? intval($_POST['group_id']) : $gid;
            $results    = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM xag_keywords WHERE group_id = %d AND id = %d", $group_id, $keyword_id
                ), ARRAY_A
            );
            if (!$results) {
                $results = [];
            }
            if ($return) {
                return $results;
            } else {
                wp_send_json($results);
            }
        }

        public static function getKeywords($return = FALSE, $gid = 0)
        {
            global $wpdb;

            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : $gid;

            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM xag_keywords WHERE group_id = %d ORDER BY position ASC", $group_id
                ), ARRAY_A
            );


            if (!$results) {
                $results = [];
            }
            if ($return) {
                return $results;
            } else {
                xagio_jsonc($results);
            }
            return FALSE;
        }

        // Pro Rank Tracker Add
        public static function trackKeywordsAdd()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // Unset _POST
            unset($_POST['action']);

            // Verify _POST before sending to panel
            if (!isset($_POST['keywords']) || $_POST['keywords'] == '') {
                wp_send_json([
                    'status'  => 'error',
                    'message' => "<i class='uk-icon-exclamation'></i> Please select some keywords!"
                ]);
            }

            // Check if Search engine is set
            if (!isset($_POST['search_engine'])) {
                wp_send_json([
                    'status'  => 'error',
                    'message' => "<i class='uk-icon-exclamation'></i> Please enter search engine!"
                ]);
            }

            // Check if Search engine is set
            if (!isset($_POST['search_location'])) {
                wp_send_json([
                    'status'  => 'error',
                    'message' => "<i class='uk-icon-exclamation'></i> Please enter search location!"
                ]);
            }

            // Store keywords
            $keywords = explode(',', sanitize_text_field(wp_unslash($_POST['keywords'])));

            $result = XAGIO_API::apiRequest($endpoint = 'rank_tracker', $method = 'POST', [
                'url'             => site_url(),
                'keywords'        => $keywords,
                'search_engines'  => map_deep(wp_unslash($_POST['search_engine']), 'absint'),
                'search_location' => absint(wp_unslash($_POST['search_location'])),
            ], $http_code);

            if ($http_code == 200) {
                xagio_json('success', 'Keywords are added successfully!');
            } else {
                xagio_json('error', $result);
            }

            /////////////////////////////////////////////// NEW CALL


        }

        //PRO Rank Tracker Get
        public static function trackKeywordsget()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // Unset data
            unset($_POST['action']);

            // Verify data before sending to panel
            if (!isset($_POST['keywords']) || $_POST['keywords'] == '') {
                wp_send_json([
                    'status'  => 'error',
                    'message' => "<i class='uk-icon-exclamation'></i> Please select some keywords!"
                ]);
            }

            // Store keywords
            $keywords = explode(',', sanitize_text_field(wp_unslash($_POST['keywords'])));

            $result = XAGIO_API::apiRequest($endpoint = 'check_rank_tracker', $method = 'POST', [
                'url'      => site_url(),
                'keywords' => $keywords,
            ], $http_code);

            if ($http_code == 200) {
                xagio_json('success', 'Keywords found.');
            } else {
                xagio_json('error', $result);
            }
        }

        //PRO Rank Tracker Delete
        public static function trackKeywordsdelete()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // Unset data
            unset($_POST['action']);

            // Verify data before sending to panel
            if (!isset($_POST['keywords']) || $_POST['keywords'] == '') {
                wp_send_json([
                    'status'  => 'error',
                    'message' => "<i class='uk-icon-exclamation'></i> Please select some keywords!"
                ]);
            }

            // Store keywords
            $keywords = explode(',', sanitize_text_field(wp_unslash($_POST['keywords'])));

            $result = XAGIO_API::apiRequest($endpoint = 'delete_rank_tracker', $method = 'POST', [
                'url'      => site_url(),
                'keywords' => $keywords,
            ], $http_code);

            if ($http_code == 200) {
                xagio_json('success', 'Keywords are deleted successfully!');
            } else {
                xagio_json('error', $result);
            }

        }

        static function stripSymbols($string)
        {
            return strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '-', str_replace(' ', '-', $string)));
        }

        public static function removeDuplicatedKeywords($project_id)
        {
            global $wpdb;

            $ids_to_keep = $wpdb->get_col(
                $wpdb->prepare(
                    "
            SELECT MIN(k.id)
            FROM xag_keywords k
            JOIN xag_groups g ON k.group_id = g.id
            WHERE g.project_id = %d
            GROUP BY k.keyword
            ", $project_id
                )
            );

            if (empty($ids_to_keep)) {
                return;
            }

            // Create placeholders for each ID
            $placeholders = implode(', ', array_fill(0, count($ids_to_keep), '%d'));

            // Prepare the arguments for the prepared statement
            $prepare_args = array_merge([$project_id], $ids_to_keep);

            // Execute the query
            $wpdb->query(
                $wpdb->prepare(
                    "
            DELETE k
            FROM xag_keywords k
            JOIN xag_groups g ON k.group_id = g.id
            WHERE g.project_id = %d
            AND k.id NOT IN ($placeholders)
            ", ...$prepare_args
                )
            );
        }


    }

}