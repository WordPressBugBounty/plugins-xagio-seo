<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_GROUPS')) {

    class XAGIO_MODEL_GROUPS
    {
        public static function initialize()
        {
            if (!XAGIO_HAS_ADMIN_PERMISSIONS)
                return;

            add_action('admin_post_xagio_import_keyword_planner', [
                'XAGIO_MODEL_GROUPS',
                'importKeywordPlanner'
            ]);
            add_action('admin_post_xagio_getGroups', [
                'XAGIO_MODEL_GROUPS',
                'getGroups'
            ]);
            add_action('admin_post_xagio_getGroup', [
                'XAGIO_MODEL_GROUPS',
                'getGroup'
            ]);
            add_action('admin_post_xagio_newGroup', [
                'XAGIO_MODEL_GROUPS',
                'newGroup'
            ]);
            add_action('admin_post_xagio_deleteGroup', [
                'XAGIO_MODEL_GROUPS',
                'deleteGroup'
            ]);
            add_action('admin_post_xagio_deleteEmptyGroups', [
                'XAGIO_MODEL_GROUPS',
                'deleteEmptyGroups'
            ]);
            add_action('admin_post_xagio_deleteGroups', [
                'XAGIO_MODEL_GROUPS',
                'deleteGroups'
            ]);
            add_action('admin_post_xagio_deleteGroupsAll', [
                'XAGIO_MODEL_GROUPS',
                'deleteGroupsAll'
            ]);
            add_action('admin_post_xagio_deleteKeywords', [
                'XAGIO_MODEL_GROUPS',
                'deleteKeywords'
            ]);
            add_action('admin_post_xagio_deleteDuplicate', [
                'XAGIO_MODEL_GROUPS',
                'deleteDuplicate'
            ]);
            add_action('admin_post_xagio_updateGroup', [
                'XAGIO_MODEL_GROUPS',
                'updateGroup'
            ]);
            add_action('admin_post_xagio_moveToProject', [
                'XAGIO_MODEL_GROUPS',
                'moveToProject'
            ]);
            add_action('admin_post_xagio_getAttachedGroup', [
                'XAGIO_MODEL_GROUPS',
                'getAttachedGroup'
            ]);
            add_action('admin_post_xagio_searchGroups', [
                'XAGIO_MODEL_GROUPS',
                'searchGroups'
            ]);
            add_action('admin_post_xagio_groupToProject', [
                'XAGIO_MODEL_GROUPS',
                'groupToProject'
            ]);
            add_action('admin_post_xagio_getCfTemplates', [
                'XAGIO_MODEL_GROUPS',
                'getCfTemplates'
            ]); // Get all Templates
            add_action('admin_post_xagio_saveCfTemplate', [
                'XAGIO_MODEL_GROUPS',
                'saveCfTemplate'
            ]); // Save Template
            add_action('admin_post_xagio_applyCfTemplate', [
                'XAGIO_MODEL_GROUPS',
                'applyCfTemplate'
            ]); // Set Default Template
            add_action('admin_post_xagio_createCfTemplate', [
                'XAGIO_MODEL_GROUPS',
                'createCfTemplate'
            ]); // Create new Template
            add_action('admin_post_xagio_deleteCfTemplate', [
                'XAGIO_MODEL_GROUPS',
                'deleteCfTemplate'
            ]); // Delete Template

            add_action('admin_post_xagio_export_groups', [
                'XAGIO_MODEL_GROUPS',
                'exportGroups'
            ]);
        }

        public static function getAttachedGroup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['group_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $group_id = intval($_POST['group_id']);

            $keywords = XAGIO_MODEL_KEYWORDS::getKeywords(TRUE, $group_id);
            if (!$keywords) {
                $keywords = [];
            }

            wp_send_json($keywords);
        }

        // Download to CSV
        public static function exportGroups()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');


            // Export to csv.
            if (!isset($_GET['group_ids'])) {
                die('Group ID is missing!');
            }

            $group_ids = sanitize_text_field(wp_unslash($_GET['group_ids']));
            $group_ids = explode(",", $group_ids);

            if (sizeof($group_ids) > 0) {
                self::exportGroupsToCsv($group_ids);
            } else {
                die('Group ID is missing!');
            }
        }

        public static function exportGroupsToCsv($group_ids)
        {
            global $wpdb;

            $projectName          = '';
            $groupIdsPlaceholders = implode(", ", array_fill(0, count($group_ids), '%d'));

            $selectedGroups = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM xag_groups WHERE id IN ($groupIdsPlaceholders)", ...array_map('absint', $group_ids)
                ), ARRAY_A
            );

            if (sizeof($selectedGroups) < 1)
                die("No Groups found!");

            if (isset($selectedGroups[0]['project_id'])) {
                $project_id  = $selectedGroups[0]['project_id'];
                $projectData = $wpdb->get_row($wpdb->prepare("SELECT project_name FROM xag_projects WHERE id = %d", $project_id), ARRAY_A);
                if (isset($projectData['project_name'])) {
                    $projectName = $projectData['project_name'];
                }
                unset($projectData);
            }

            $xagio_output = '"Project Name","' . $projectName . '",';
            $xagio_output .= "\n";
            $xagio_output .= '"Total Groups","' . count($selectedGroups) . '",';
            $xagio_output .= "\n";
            foreach ($selectedGroups as $xagio_group) {
                $group_id = $xagio_group['id'];
                $keywords = $wpdb->get_results($wpdb->prepare("SELECT * FROM xag_keywords WHERE group_id = %d", $group_id), ARRAY_A);
                $xagio_output   .= "\n";
                $xagio_output   .= 'Group,Title,URL,DESC,H1,';
                $xagio_output   .= "\n";
                $xagio_output   .= '"' . $xagio_group['group_name'] . '","' . $xagio_group['title'] . '","' . $xagio_group['url'] . '","' . $xagio_group['description'] . '","' . $xagio_group['h1'] . '",';
                $xagio_output   .= "\n";
                $xagio_output   .= 'Keyword,Volume,CPC,inTITLE,inURL,"' . count($keywords) . '",';
                $xagio_output   .= "\n";
                foreach ($keywords as $keyword) {
                    $xagio_output .= '"' . $keyword['keyword'] . '",="' . $keyword['volume'] . '",="' . $keyword['cpc'] . '",="' . $keyword['intitle'] . '",="' . $keyword['inurl'] . '",';
                    $xagio_output .= "\n";
                }
            }
            $filename = $projectName . ".csv";
            header('Content-type: application/csv');
            header('Content-Disposition: attachment; filename=' . $filename);

            echo wp_kses_data($xagio_output);
            exit;

        }

        public static function moveToProject()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['project_id']) || !isset($_POST['group_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            global $wpdb;

            $project_id = sanitize_text_field($_POST['project_id']);
            $group_id   = sanitize_text_field($_POST['group_id']);
            $keep_copy   = sanitize_text_field($_POST['keep_copy']);

            $group_ids = explode(",", $group_id);

            if (sizeof($group_ids) < 1) {
                xagio_json('error', 'Please select at least one group!');
            } else {

                if (!is_numeric($project_id)) {

                    $wpdb->insert('xag_projects', [
                        'project_name' => $project_id,
                        'date_created' => gmdate('Y-m-d H:i:s')
                    ]);

                    $project_id = $wpdb->insert_id;
                }

                foreach ($group_ids as $g_id) {

                    if ($keep_copy) {
                        $group_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM xag_groups WHERE id = %d", $g_id), ARRAY_A);

                        $wpdb->insert('xag_groups', [
                            'project_id'      => $group_row['project_id'],
                            'id_page_post'    => $group_row['id_page_post'],
                            'group_name'      => $group_row['group_name'],
                            'title'           => $group_row['title'],
                            'url'             => $group_row['url'],
                            'description'     => $group_row['description'],
                            'h1'              => $group_row['h1'],
                            'date_created'    => $group_row['date_created'],
                            'position'        => $group_row['position'],
                            'notes'           => $group_row['notes'],
                            'id_taxonomy'     => $group_row['id_taxonomy'],
                            'external_domain' => $group_row['external_domain'],
                        ]);

                        $inserted_group_id = $wpdb->insert_id;

                        $keyword_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM xag_keywords WHERE group_id = %d", $g_id), ARRAY_A);

                        if (sizeof($keyword_rows) > 0) {
                            foreach ($keyword_rows as $k_row) {
                                $wpdb->insert('xag_keywords', [
                                    'group_id'      => $inserted_group_id,
                                    'keyword'       => $k_row['keyword'],
                                    'volume'        => $k_row['volume'],
                                    'cpc'           => $k_row['cpc'],
                                    'inurl'         => $k_row['inurl'],
                                    'intitle'       => $k_row['intitle'],
                                    'date_created'  => $k_row['date_created'],
                                    'position'      => $k_row['position'],
                                    'queued'        => $k_row['queued'],
                                    'rank'          => $k_row['rank']
                                ]);
                            }
                        }
                    }

                    $wpdb->update('xag_groups', [
                        'project_id' => $project_id,
                    ], [
                        'id' => $g_id,
                    ]);
                }


                xagio_json('success', 'Group(s) successfully moved to a selected project.');
            }
        }

        public static function groupToProject()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');
            global $wpdb;

            if (!isset($_POST['group_id']) && !isset($_POST['projectName'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $group_id  = intval($_POST['group_id']);
            $group_ids = explode(",", $group_id);

            if (sizeof($group_ids) < 1) {
                xagio_json('error', 'Please select at least one group!');
            } else {

                $wpdb->insert('xag_projects', [
                    'project_name' => sanitize_text_field(wp_unslash($_POST['projectName'])),
                    'date_created' => gmdate('Y-m-d H:i:s'),
                ]);

                $project_id = $wpdb->insert_id;

                foreach ($group_ids as $g_id) {
                    $wpdb->update('xag_groups', [
                        'project_id' => $project_id,
                    ], [
                        'id' => $g_id,
                    ]);
                }

                xagio_json('success', 'New Project has been created from selecetd groups.');
            }
        }

        public static function prsCsvUploadMimes($existing_mimes = [])
        {
            $existing_mimes['json'] = 'application/csv';
            return $existing_mimes;
        }

        public static function importKeywordPlanner()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['project'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            global $wpdb;
            $projectID = sanitize_text_field(wp_unslash($_POST['project']));

            if (isset($_FILES['file-import'])) {
                // Include the necessary WordPress file handling functions
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }

                $upload_overrides = ['test_form' => false];

                // Use wp_handle_upload to manage file uploads
                $uploaded_file = wp_handle_upload($_FILES['file-import'], $upload_overrides);

                if ($uploaded_file && !isset($uploaded_file['error'])) {
                    $csv_path = $uploaded_file['file'];

                    $file_contents = xagio_file_get_contents($csv_path);

                    wp_delete_file($csv_path);

                    $rows = array_map('str_getcsv', explode("\n", $file_contents));
                    if (empty($rows)) return;

                    $isSurfer = false;

                    // Check if CSV is from SurferSEO
                    if (isset($rows[0][0]) && stripos($rows[0][0], 'Cluster Name') !== false) {
                        $isSurfer = true;
                    }

                    if ($isSurfer) {
                        // Remove header
                        array_shift($rows);

                        $groupMap = [];

                        foreach ($rows as $columns) {
                            $columns = array_map('trim', $columns);
                            if (count($columns) < 3) continue;

                            $groupName = sanitize_text_field($columns[0]);
                            $keyword   = sanitize_text_field($columns[1]);
                            $volume    = str_replace(['"', '='], '', $columns[2]);

                            if (strlen($keyword) < 3) continue;

                            if (!isset($groupMap[$groupName])) {
                                $wpdb->insert('xag_groups', [
                                    'project_id'   => $projectID,
                                    'group_name'   => $groupName,
                                    'title'        => $groupName,
                                    'url'          => strtolower(sanitize_title($groupName)),
                                    'h1'           => $groupName,
                                    'date_created' => gmdate('Y-m-d H:i:s')
                                ]);
                                $group_id = $wpdb->insert_id;
                                $groupMap[$groupName] = $group_id;
                            } else {
                                $group_id = $groupMap[$groupName];
                            }

                            $wpdb->insert('xag_keywords', [
                                'group_id' => $group_id,
                                'keyword'  => $keyword,
                                'volume'   => is_numeric($volume) ? $volume : 0,
                                'cpc'      => '0.00',
                            ]);
                        }

                        return;
                    }

                    // Xagio CSV structure
                    $group_id = 0;
                    $expectGroup = false;

                    foreach ($rows as $columns) {
                        $columns = array_map('trim', $columns);

                        if (empty(array_filter($columns))) {
                            $expectGroup = true;
                            continue;
                        }

                        if (in_array(strtolower($columns[0]), ['project name', 'total groups', 'group'])) {
                            continue;
                        }

                        if ($expectGroup || $group_id === 0) {
                            $expectGroup = false;

                            $group_name = sanitize_text_field($columns[0] ?? '');
                            $title      = sanitize_text_field($columns[1] ?? '');
                            $xagio_url        = sanitize_text_field($columns[2] ?? '');
                            $xagio_desc       = sanitize_text_field($columns[3] ?? '');
                            $h1         = sanitize_text_field($columns[4] ?? '');

                            $wpdb->insert('xag_groups', [
                                'project_id'   => $projectID,
                                'group_name'   => $group_name,
                                'title'        => $title,
                                'url'          => $xagio_url,
                                'description'  => $xagio_desc,
                                'h1'           => $h1,
                                'date_created' => gmdate('Y-m-d H:i:s')
                            ]);
                            $group_id = $wpdb->insert_id;
                            continue;
                        }

                        // Keyword row
                        $keyword = sanitize_text_field(str_replace('"', '', $columns[0] ?? ''));
                        if (strlen($keyword) < 3) continue;

                        if (!is_numeric(str_replace(['"', '='], '', $columns[1] ?? ''))) {
                            continue;
                        }

                        $volume   = str_replace(['"', '='], '', $columns[1] ?? '0');
                        $cpc      = str_replace(['"', '='], '', $columns[2] ?? '0.00');
                        $intitle  = str_replace(['"', '='], '', $columns[3] ?? '');
                        $inurl    = str_replace(['"', '='], '', $columns[4] ?? '');

                        $wpdb->insert('xag_keywords', [
                            'group_id' => $group_id,
                            'keyword'  => $keyword,
                            'volume'   => is_numeric($volume) ? $volume : 0,
                            'cpc'      => is_numeric($cpc) ? $cpc : '0.00',
                            'intitle'  => is_numeric($intitle) ? $intitle : '',
                            'inurl'    => is_numeric($inurl) ? $inurl : '',
                        ]);
                    }
                } else {
                    xagio_jsonc([
                        "status"  => "error",
                        "message" => "Failed to upload the file: " . $uploaded_file['error'],
                    ]);
                }
            }
        }

        public static function newGroup()
        {
            global $wpdb;

            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['project_id']) || !isset($_POST['group_name'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $project_id = intval($_POST['project_id']);
            $group_name = sanitize_text_field(wp_unslash($_POST['group_name']));

            if ($group_name === 'xagio-empty') {
                $wpdb->insert('xag_groups', [
                    'project_id'   => $project_id,
                    'group_name'   => ' ',
                    'date_created' => gmdate('Y-m-d H:i:s'),
                ]);
            } else {
                $group_names = explode(",", $group_name);

                foreach ($group_names as $group_name) {
                    $group_name = trim($group_name);
                    if (empty($group_name)) {
                        continue;
                    }
                    $wpdb->insert('xag_groups', [
                        'project_id'   => $project_id,
                        'group_name'   => $group_name,
                        'date_created' => gmdate('Y-m-d H:i:s'),
                    ]);
                }
            }

        }

        public static function newGroupFromExistingPost($project_id, $group_name, $post_id = '', $title = '', $xagio_url = '', $xagio_description = '', $h1 = '', $notes = '')
        {
            global $wpdb;

            $wpdb->insert('xag_groups', [
                'project_id'   => $project_id,
                'group_name'   => $group_name,
                'id_page_post' => $post_id,
                'title'        => $title,
                'url'          => $xagio_url,
                'description'  => $xagio_description,
                'h1'           => $h1,
                'notes'        => $notes,
                'date_created' => gmdate('Y-m-d H:i:s'),
            ]);
        }

        public static function deleteGroupsAll($project_id = NULL, $return = NULL)
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['project_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            global $wpdb;

            if ($return !== TRUE) {
                $project_id = intval($_POST['project_id']);
            }

            $xagio_r = $wpdb->query($wpdb->prepare("DELETE g, k FROM xag_groups g LEFT JOIN xag_keywords k ON g.id = k.group_id WHERE g.project_id = %d", $project_id));

            if ($return !== TRUE) {
                xagio_json('success', 'All Groups from Project successfully deleted!');
            } else {
                return $xagio_r;
            }

            return FALSE;
        }

        public static function deleteEmptyGroups()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['project_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }
            $skipGroups = false;
            if (isset($_POST['skipGroups'])) {
                $skipGroups = filter_var(wp_unslash($_POST['skipGroups']), FILTER_VALIDATE_BOOLEAN);
            }

            global $wpdb;

            $project_id = intval($_POST['project_id']);


            $groups = $wpdb->get_results($wpdb->prepare("SELECT g.id, COUNT(k.id) as count, g.title, g.description, g.h1 FROM xag_groups as g LEFT JOIN xag_keywords as k ON k.group_id = g.id WHERE g.project_id = %d GROUP BY g.id", $project_id), ARRAY_A);

            $deleteGroupIds = [];
            foreach ($groups as $xagio_group) {
                $keyword_count        = (int)$xagio_group['count'];
                $xagio_group['title']       = $xagio_group['title'] ?? "";
                $xagio_group['description'] = $xagio_group['description'] ?? "";
                $xagio_group['h1']          = $xagio_group['h1'] ?? "";

                // Only check groups with no keywords
                if ($keyword_count === 0) {
                    // Check if user wants to save groups that has no keywords but has title, description, or h1
                    if ($skipGroups) {
                        // Check if group has no title, description, or h1
                        if (empty($xagio_group['title']) && empty($xagio_group['description']) && empty($xagio_group['h1'])) {
                            $deleteGroupIds[] = $xagio_group['id'];
                        }
                    } else {
                        $deleteGroupIds[] = $xagio_group['id'];
                    }
                }
            }

            $xagio_placeholders = implode(',', array_fill(0, count($deleteGroupIds), '%d'));

            if(sizeof($deleteGroupIds) > 0) {
                $wpdb->query($wpdb->prepare("DELETE FROM xag_groups WHERE project_id = %d AND id IN ($xagio_placeholders)", $project_id, ...$deleteGroupIds));
            }

            xagio_json('success', 'Empty groups successfully deleted!');
        }

        public static function deleteGroups($group_ids = NULL, $return = NULL)
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['group_ids']) || !isset($_POST['deleteRanks'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            global $wpdb;

            if ($return !== TRUE) {
                $group_ids    = explode(',', sanitize_text_field(wp_unslash($_POST['group_ids'])));
                $group_ids    = array_map('absint', $group_ids);
                $xagio_placeholders = implode(',', array_fill(0, count($group_ids), '%d'));

                $deleteRanks = filter_var(wp_unslash($_POST['deleteRanks']), FILTER_VALIDATE_BOOLEAN);
                if ($deleteRanks) {
                    $rankedKeywords = $wpdb->get_results($wpdb->prepare("SELECT `keyword` FROM xag_keywords WHERE `group_id` IN ($xagio_placeholders) AND `rank` != '0'", ...$group_ids), ARRAY_A);

                    if (!empty($rankedKeywords)) {
                        $keywordsToDelete = [];
                        foreach ($rankedKeywords as $rankedKeyword)
                            $keywordsToDelete[] = $rankedKeyword['keyword'];
                        self::deleteKeywordRanks($keywordsToDelete);
                    }
                }
            }

            $xagio_r = $wpdb->query($wpdb->prepare("DELETE g, k FROM xag_groups g LEFT JOIN xag_keywords k ON g.id = k.group_id WHERE g.id IN ($xagio_placeholders)", ...$group_ids));

            if ($return !== TRUE) {
                xagio_json('success', 'Groups successfully deleted!');
            } else {
                return $xagio_r;
            }

            return FALSE;
        }

        public static function deleteKeywordRanks($keywords)
        {
            // Send keywords to panel, so we can delete them on our RankTracker
            $xagio_result = XAGIO_API::apiRequest(
                $endpoint = 'delete_rank_tracker', $method = 'POST', [
                'url'      => site_url(),
                'keywords' => $keywords,
            ], $xagio_http_code
            );
        }

        public static function deleteGroup($group_id = NULL, $return = NULL)
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['group_id']) || !isset($_POST['deleteRanks'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            global $wpdb;

            if ($return !== TRUE) {
                $group_id    = intval($_POST['group_id']);
                $deleteRanks = sanitize_text_field(wp_unslash($_POST['deleteRanks']));
                if ($deleteRanks) {
                    $rankedKeywords = $wpdb->get_results($wpdb->prepare("SELECT `keyword` FROM xag_keywords WHERE `group_id` = %d AND `rank` != '0'", $group_id), ARRAY_A);

                    if (!empty($rankedKeywords)) {
                        $keywordsToDelete = [];
                        foreach ($rankedKeywords as $rankedKeyword)
                            $keywordsToDelete[] = $rankedKeyword['keyword'];
                        self::deleteKeywordRanks($keywordsToDelete);
                    }
                }
            }

            $xagio_r = $wpdb->query($wpdb->prepare("DELETE g, k FROM xag_groups g LEFT JOIN xag_keywords k ON g.id = k.group_id WHERE g.id = %d", $group_id));

            if ($return !== TRUE) {
                xagio_json('success', 'Group successfully deleted!');
            } else {
                return $xagio_r;
            }

            return FALSE;
        }

        public static function deleteKeywords()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['keywords']) || !isset($_POST['deleteRanks'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            global $wpdb;

            $keywords    = array_map('sanitize_text_field', wp_unslash($_POST['keywords']));
            $deleteRanks = filter_var(wp_unslash($_POST['deleteRanks']), FILTER_VALIDATE_BOOLEAN);

            // Ensure that each keyword is properly escaped for SQL
            $kwSelectPlaceholders = implode(", ", array_fill(0, count($keywords), '%s'));

            if (!empty($keywords) && $deleteRanks) {
                // Prepare the query with placeholders and variables
                $rankedKeywords = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT `keyword` FROM xag_keywords WHERE `id` IN ($kwSelectPlaceholders) AND `rank` != '0'", ...$keywords
                    ), ARRAY_A
                );

                if (!empty($rankedKeywords)) {
                    $keywordsToDelete = [];
                    foreach ($rankedKeywords as $rankedKeyword) {
                        $keywordsToDelete[] = $rankedKeyword['keyword'];
                    }
                    self::deleteKeywordRanks($keywordsToDelete);
                }
            }


            $xagio_placeholders = implode(',', array_fill(0, count($keywords), '%d'));
            $wpdb->query($wpdb->prepare("DELETE FROM xag_keywords WHERE id IN ($xagio_placeholders)", ...$keywords));

            wp_send_json([
                'status'  => 'success',
                'message' => 'Group successfully deleted!'
            ]);
        }

        public static function deleteDuplicate()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');
            global $wpdb;

            // Validate and sanitize the project_id from the POST request
            $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

            if ($project_id === 0) {
                wp_send_json([
                    'status'  => 'danger',
                    'message' => 'Invalid Project ID!'
                ]);
                return;
            }

            // Query to find keywords that have duplicates within the same project
            $Keywords = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT keyword 
                    FROM xag_keywords 
                    WHERE group_id IN (SELECT id FROM xag_groups WHERE project_id = %d) 
                    GROUP BY keyword 
                    HAVING COUNT(*) > 1", $project_id
                ), ARRAY_A
            );

            $duplicatekeywordIds = [];
            foreach ($Keywords as $keyword) {
                $keyword_value = $keyword['keyword'];

                // Query to find all instances of the duplicate keyword and sort by volume and cpc
                $findkeywords = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT id, volume 
                        FROM xag_keywords 
                        WHERE keyword = %s 
                        AND group_id IN (SELECT id FROM xag_groups WHERE project_id = %d) 
                        ORDER BY volume DESC, cpc DESC", $keyword_value, $project_id
                    ), ARRAY_A
                );

                // Keep the highest volume and cpc keyword and collect the rest for deletion
                array_shift($findkeywords);
                foreach ($findkeywords as $findkeyword) {
                    $duplicatekeywordIds[] = intval($findkeyword['id']);
                }
            }

            if (!empty($duplicatekeywordIds)) {
                // Create a string of placeholders for the IN clause
                $xagio_placeholders = implode(',', array_fill(0, count($duplicatekeywordIds), '%d'));

                // Prepare the query with placeholders

                // Execute the prepared query
                $deletedKeywords = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM xag_keywords WHERE id IN ($xagio_placeholders)", ...$duplicatekeywordIds
                    )
                );

                wp_send_json([
                    'status'  => 'success',
                    'message' => '<b>' . $deletedKeywords . '</b> Duplicate Keywords successfully deleted!'
                ]);
            } else {
                wp_send_json([
                    'status'  => 'danger',
                    'message' => 'No Duplicate Keywords found!'
                ]);
            }
        }

        public static function searchGroups()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');
            global $wpdb;

            if (!isset($_POST['search'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // Sanitize the search term
            $search_term = sanitize_text_field(wp_unslash($_POST['search']));

            $groupsFound = [];

            if (!empty($search_term) && strlen($search_term) > 2) {
                $like_search_term = '%' . $wpdb->esc_like($search_term) . '%';

                $groupsFound = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT g.`project_id`, p.project_name, g.`id`, g.`group_name` 
             FROM xag_groups as g 
             JOIN xag_projects p ON g.project_id = p.id 
             WHERE g.`title` LIKE %s OR g.`group_name` LIKE %s 
             LIMIT 50", $like_search_term, $like_search_term
                    ), ARRAY_A
                );
            }

            xagio_json('success', 'Groups search result', $groupsFound);
        }

        public static function updateGroup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['project_id']) || !isset($_POST['group_id']) || !isset($_POST['oriUrl'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $project_id  = intval($_POST['project_id']);
            $group_id    = intval($_POST['group_id']);
            $originalUrl = sanitize_text_field(wp_unslash($_POST['oriUrl']));

            $xagio_group = $wpdb->get_row($wpdb->prepare('SELECT * FROM xag_groups WHERE `id` = %d', $group_id), ARRAY_A);

            $post_id     = $xagio_group['id_page_post'];
            $taxonomy_id = $xagio_group['id_taxonomy'];

            $update_data = [
                'h1'          => isset($_POST['h1']) ? sanitize_text_field(wp_unslash($_POST['h1'])) : '',
                'url'         => isset($_POST['url']) ? sanitize_text_field(wp_unslash($_POST['url'])) : '',
                'title'       => isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '',
                'description' => isset($_POST['description']) ? sanitize_text_field(wp_unslash($_POST['description'])) : '',
                'notes'       => isset($_POST['notes']) ? base64_encode(wp_unslash($_POST['notes'])) : '',
                'group_name'  => isset($_POST['group_name']) ? sanitize_text_field(wp_unslash($_POST['group_name'])) : '',
            ];

            if (!empty($taxonomy_id)) {

                $term = get_term($taxonomy_id);
                if ($term->taxonomy == 'location') {
                    unset($update_data['h1']);
                    unset($update_data['url']);
                }
            }

            if (intval($post_id) !== 0) {
                // If post-ID is attached to a multiple group, update all groups with the same info
                $attached_groups = $wpdb->get_results($wpdb->prepare("SELECT * FROM xag_groups WHERE id_page_post = %d", $post_id), ARRAY_A);

                if (!empty($attached_groups)) {

                    if (sizeof($attached_groups) == 1) {
                        $attached_groups = $attached_groups[0];
                    }

                    if (isset($attached_groups['id'])) {
                        $wpdb->update('xag_groups', $update_data, [
                            'id'         => $attached_groups['id'],
                            'project_id' => $attached_groups['project_id'],
                        ]);
                    } else {
                        foreach ($attached_groups as $attached) {
                            $wpdb->update('xag_groups', $update_data, [
                                'id'           => $attached['id'],
                                'project_id'   => $attached['project_id'],
                                'id_page_post' => $attached['id_page_post'],
                            ]);
                        }
                    }

                }

            } else {
                $wpdb->update('xag_groups', $update_data, [
                    'id'         => $group_id,
                    'project_id' => $project_id,
                ]);
            }

            if (!empty($post_id)) {

                // Update the Post/Page Data
                $xagio_post_data = [];

                // Set the new URL
                if (isset($_POST['url'])) {

                    // Create redirection if needed
                    $newUrl = sanitize_text_field(wp_unslash($_POST['url']));

                    if ($newUrl != $originalUrl) {
                        XAGIO_MODEL_REDIRECTS::add($originalUrl, $newUrl);
                    }

                    $xagio_post_data['post_name'] = XAGIO_MODEL_SEO::extract_url_name(sanitize_url(wp_unslash($_POST['url'])));

                    update_post_meta($post_id, 'ps_seo_url', $newUrl);
                }

                // Set the new H1
                if (!empty($_POST['h1'])) {

                    $xagio_post_data['post_title'] = sanitize_text_field(wp_unslash($_POST['h1']));

                    $post_type = get_post_type($post_id);
                    $operators = get_option(($post_type == 'page') ? 'xag_silo_pages' : 'xag_silo_posts');

                    if (isset($operators['Default'])) {
                        $operators = $operators['Default'];
                    }

                    $operators = urldecode($operators);
                    $operators = stripslashes($operators);
                    $operators = json_decode($operators, TRUE);

                    // Find the operator
                    $operator_id = XAGIO_MODEL_SILO::_findOperator($operators, $post_type, $post_id);

                    // Modify the operator
                    if ($operator_id !== NULL) {
                        $operators['operators'][$operator_id]['properties']['title'] = sanitize_text_field(wp_unslash($_POST['h1']));
                        update_option(($post_type == 'page') ? 'xag_silo_pages' : 'xag_silo_posts', urlencode(wp_json_encode($operators)));
                    }

                }

                if (sizeof($xagio_post_data) > 0) {
                    if ($xagio_group !== FALSE) {
                        $xagio_post_data['ID'] = $post_id;
                        wp_update_post($xagio_post_data);
                    }
                }


                if (!isset($_POST['title']) || !isset($_POST['description'])) {
                    wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
                }

                // Update SEO Title / Meta
                update_post_meta($post_id, 'XAGIO_SEO_TITLE', sanitize_text_field(wp_unslash($_POST['title'])));
                update_post_meta($post_id, 'XAGIO_SEO_DESCRIPTION', sanitize_textarea_field(wp_unslash($_POST['description'])));
                if (isset($_POST['notes'])) {
                    update_post_meta($post_id, 'XAGIO_SEO_NOTES', base64_encode(wp_unslash($_POST['notes'])));
                }

            }

            if (!empty($taxonomy_id)) {

                // Ensure required parameters are set before proceeding
                if (!isset($_POST['title']) || !isset($_POST['description'])) {
                    wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
                }

                // Retrieve existing term meta to ensure it is not lost
                $existing_meta = get_term_meta($taxonomy_id);

                // Prepare term update arguments
                $update_args = [];

                // Set the new URL slug if provided
                if (!empty($_POST['url'])) {
                    $update_args['slug'] = XAGIO_MODEL_SEO::extract_url_name(sanitize_url(wp_unslash($_POST['url'])));
                }

                // Set the new H1 name if provided
                if (!empty($_POST['h1'])) {
                    $update_args['name'] = sanitize_text_field(wp_unslash($_POST['h1']));
                }

                // Update term if there are changes
                if (!empty($update_args)) {
                    $update_result = wp_update_term($taxonomy_id, $term->taxonomy, $update_args);

                    // Handle possible errors
                    if (is_wp_error($update_result)) {
                        wp_die('Failed to update term: ' . wp_kses_post($update_result->get_error_message()), 'Update Error', ['response' => 500]);
                    }
                }

                // Reapply existing term meta (preserving old values)
                if (!empty($existing_meta)) {
                    foreach ($existing_meta as $xagio_key => $values) {
                        foreach ($values as $xagio_value) {
                            update_term_meta($taxonomy_id, $xagio_key, $xagio_value);
                        }
                    }
                }

                // Update term metadata with new values
                update_term_meta($taxonomy_id, 'XAGIO_SEO_TITLE', sanitize_text_field(wp_unslash($_POST['title'])));
                update_term_meta($taxonomy_id, 'XAGIO_SEO_DESCRIPTION', sanitize_textarea_field(wp_unslash($_POST['description'])));

            }


        }

        public static function getGroups()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');
            global $wpdb, $wp_query;

            if (!isset($_POST['project_id'], $_POST['post_type'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $project_id = intval($_POST['project_id']);
            $post_type  = sanitize_text_field(wp_unslash($_POST['post_type']));

            $results     = $wpdb->get_results($wpdb->prepare("SELECT * FROM xag_groups WHERE project_id = %d", $project_id), ARRAY_A);
            $outputArray = [];

            $group_ai_status = [];

            $ai_optimized_groups = $wpdb->get_results("SELECT target_id, status, input FROM xag_ai WHERE input = 'SEO_SUGGESTIONS' OR input = 'SEO_SUGGESTIONS_MAIN_KW'", ARRAY_A);

            if (isset($ai_optimized_groups['target_id'])) {
                $group_ai_status[$ai_optimized_groups['target_id']] = [
                    'input'  => $ai_optimized_groups['input'],
                    'status' => $ai_optimized_groups['status'],
                ];
            } else {
                foreach ($ai_optimized_groups as $ai_group) {
                    $group_ai_status[$ai_group['target_id']] = [
                        'input'  => $ai_group['input'],
                        'status' => $ai_group['status'],
                    ];
                }
            }

            if ($results !== FALSE) {

                // Magic Page fixes
                if (class_exists('MagicPageShortcodesAndFilters')) {
                    $m = new MagicPageShortcodesAndFilters();
                    $m->initXfields();
                }

                for ($xagio_i = 0; $xagio_i < sizeof($results); $xagio_i++) {

                    $group_post_type = FALSE;
                    $magicPage       = FALSE;

                    $results[$xagio_i]['h1']          = stripslashes($results[$xagio_i]['h1'] ?? '');
                    $results[$xagio_i]['title']       = stripslashes($results[$xagio_i]['title'] ?? '');
                    $results[$xagio_i]['description'] = stripslashes($results[$xagio_i]['description'] ?? '');
                    $results[$xagio_i]['notes']       = xagio_maybe_decode($results[$xagio_i]['notes'] ?? '');

                    if (!empty($results[$xagio_i]['id_page_post'])) {

                        $post = get_post($results[$xagio_i]['id_page_post']);

                        $group_post_type = @$post->post_type;

                        $GLOBALS['post'] = $post;
                        setup_postdata($post);

                        $wp_query = new WP_Query([
                            'p' => $results[$xagio_i]['id_page_post'],
                        ]);
                    }

                    if (!empty($results[$xagio_i]['id_taxonomy'])) {

                        $term = get_term($results[$xagio_i]['id_taxonomy']);

                        $group_post_type = @$term->taxonomy;

                        $wp_query        = new WP_Query();
                        $wp_query->query = [
                            'magicpage' => @$term->slug,
                        ];

                        if (@$term->taxonomy == 'location') {
                            $magicPage = TRUE;
                        }
                    }

                    if ($post_type !== FALSE && !empty($post_type)) {
                        if ($group_post_type !== FALSE) {
                            if ($post_type !== $group_post_type) {
                                continue;
                            }
                        } else {
                            if ($post_type !== 'none') {
                                continue;
                            }
                        }
                    }

                    $keywords = XAGIO_MODEL_KEYWORDS::getKeywords(TRUE, $results[$xagio_i]['id']);
                    if (!$keywords) {
                        $keywords = [];
                    }

                    if (isset($group_ai_status[$results[$xagio_i]['id']])) {
                        $results[$xagio_i]['ai_status'] = $group_ai_status[$results[$xagio_i]['id']]['status'];
                        $results[$xagio_i]['ai_input']  = $group_ai_status[$results[$xagio_i]['id']]['input'];
                    } else {
                        $results[$xagio_i]['ai_status'] = 'none';
                        $results[$xagio_i]['ai_input']  = 'SEO_SUGGESTIONS';
                    }

                    $results[$xagio_i]['keywords']  = $keywords;
                    $results[$xagio_i]['post_type'] = $group_post_type;

                    $results[$xagio_i]['h1_sh']          = xagio_spintax($results[$xagio_i]['h1']);
                    $results[$xagio_i]['title_sh']       = xagio_spintax($results[$xagio_i]['title']);
                    $results[$xagio_i]['description_sh'] = xagio_spintax($results[$xagio_i]['description']);

                    if ($magicPage == TRUE) {
                        $results[$xagio_i]['h1']          = xagio_spintax($results[$xagio_i]['h1']);
                        $results[$xagio_i]['title']       = xagio_spintax($results[$xagio_i]['title']);
                        $results[$xagio_i]['description'] = xagio_spintax($results[$xagio_i]['description']);

                        // Get the magic page
                        $magicpage_id = get_posts([
                            'post_type' => 'magicpage',
                        ]);
                        $magicpage_id = $magicpage_id[0]->ID;

                        if (empty($results[$xagio_i]['title'])) {
                            $results[$xagio_i]['title']    = xagio_spintax(get_post_meta($magicpage_id, 'XAGIO_SEO_TITLE', TRUE));
                            $results[$xagio_i]['title_sh'] = $results[$xagio_i]['title'];
                        }
                        if (empty($results[$xagio_i]['description'])) {
                            $results[$xagio_i]['description']    = xagio_spintax(get_post_meta($magicpage_id, 'XAGIO_SEO_DESCRIPTION', TRUE));
                            $results[$xagio_i]['description_sh'] = $results[$xagio_i]['description'];
                        }
                    }

                    $results[$xagio_i]['id_taxonomy_term'] = get_term($results[$xagio_i]['id_taxonomy']);

                    $outputArray[] = $results[$xagio_i];
                }

            }
            wp_send_json($outputArray);
        }

        public static function getGroup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');
            global $wpdb;

            if (!isset($_POST['project_id']) || !isset($_POST['group_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $project_id = intval($_POST['project_id']);
            $group_id   = intval($_POST['group_id']);
            $results    = $wpdb->get_row($wpdb->prepare("SELECT * FROM xag_groups WHERE project_id = %d AND id = %d", $project_id, $group_id), ARRAY_A);
            if (!$results) {
                $results = [];
            } else {
                $keywords = XAGIO_MODEL_KEYWORDS::getKeywords(TRUE, $results['id']);
                if (!$keywords) {
                    $keywords = [];
                }
                $results['keywords'] = $keywords;
                $results['notes']    = xagio_maybe_decode($results['notes'] ?? '');
            }
            wp_send_json($results);
        }

        public static function getCfTemplates()
        {
            if (!get_option('XAGIO_CF_TEMPLATES')) {
                wp_send_json([
                    'status'  => 'error',
                    'default' => 'Default'
                ]);
            }

            if (!get_option('XAGIO_CF_DEFAULT_TEMPLATE')) {
                $CfTemplates = get_option('XAGIO_CF_TEMPLATES');
                wp_send_json([
                    'status'  => 'success',
                    'data'    => $CfTemplates,
                    'default' => 'Default'
                ]);
            } else {
                $CfTemplates = get_option('XAGIO_CF_TEMPLATES');
                wp_send_json([
                    'status'  => 'success',
                    'data'    => $CfTemplates,
                    'default' => get_option('XAGIO_CF_DEFAULT_TEMPLATE')
                ]);
            }

        }

        public static function saveCfTemplate()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // Check if required parameters exist
            if (!isset($_POST['name']) || !isset($_POST['action'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // Sanitize the 'name' field
            $xagio_name = sanitize_text_field(wp_unslash($_POST['name']));

            // Define and sanitize the required fields from $_POST
            $volume_red         = isset($_POST['volume_red']) ? absint(wp_unslash($_POST['volume_red'])) : 0;
            $volume_green       = isset($_POST['volume_green']) ? absint(wp_unslash($_POST['volume_green'])) : 0;
            $cpc_red            = isset($_POST['cpc_red']) ? absint(wp_unslash($_POST['cpc_red'])) : 0;
            $cpc_green          = isset($_POST['cpc_green']) ? absint(wp_unslash($_POST['cpc_green'])) : 0;
            $intitle_red        = isset($_POST['intitle_red']) ? absint(wp_unslash($_POST['intitle_red'])) : 0;
            $intitle_green      = isset($_POST['intitle_green']) ? absint(wp_unslash($_POST['intitle_green'])) : 0;
            $inurl_red          = isset($_POST['inurl_red']) ? absint(wp_unslash($_POST['inurl_red'])) : 0;
            $inurl_green        = isset($_POST['inurl_green']) ? absint(wp_unslash($_POST['inurl_green'])) : 0;
            $title_ratio_red    = isset($_POST['title_ratio_red']) ? floatval(wp_unslash($_POST['title_ratio_red'])) : 0;
            $title_ratio_green  = isset($_POST['title_ratio_green']) ? floatval(wp_unslash($_POST['title_ratio_green'])) : 0;
            $tr_goldbar_volume  = isset($_POST['tr_goldbar_volume']) ? absint(wp_unslash($_POST['tr_goldbar_volume'])) : 0;
            $tr_goldbar_intitle = isset($_POST['tr_goldbar_intitle']) ? absint(wp_unslash($_POST['tr_goldbar_intitle'])) : 0;
            $url_ratio_red      = isset($_POST['url_ratio_red']) ? floatval(wp_unslash($_POST['url_ratio_red'])) : 0;
            $url_ratio_green    = isset($_POST['url_ratio_green']) ? floatval(wp_unslash($_POST['url_ratio_green'])) : 0;
            $ur_goldbar_volume  = isset($_POST['ur_goldbar_volume']) ? absint(wp_unslash($_POST['ur_goldbar_volume'])) : 0;
            $ur_goldbar_intitle = isset($_POST['ur_goldbar_intitle']) ? absint(wp_unslash($_POST['ur_goldbar_intitle'])) : 0;

            // Check for invalid fields
            $fields = [
                'volume_red'         => $volume_red,
                'volume_green'       => $volume_green,
                'cpc_red'            => $cpc_red,
                'cpc_green'          => $cpc_green,
                'intitle_red'        => $intitle_red,
                'intitle_green'      => $intitle_green,
                'inurl_red'          => $inurl_red,
                'inurl_green'        => $inurl_green,
                'title_ratio_red'    => $title_ratio_red,
                'title_ratio_green'  => $title_ratio_green,
                'tr_goldbar_volume'  => $tr_goldbar_volume,
                'tr_goldbar_intitle' => $tr_goldbar_intitle,
                'url_ratio_red'      => $url_ratio_red,
                'url_ratio_green'    => $url_ratio_green,
                'ur_goldbar_volume'  => $ur_goldbar_volume,
                'ur_goldbar_intitle' => $ur_goldbar_intitle,
            ];

            foreach ($fields as $xagio_key => $val) {
                if ($val < 0 || $val === '') {
                    wp_send_json([
                        'status'  => 'error',
                        'message' => "<i class='uk-icon-exclamation'></i> All fields must be at least 0 and cannot be empty, field $xagio_key"
                    ]);
                }
            }

            // Prepare the option to save
            $option[$xagio_name] = [
                'name' => $xagio_name,
                'data' => $fields,
            ];

            // Save the option
            if (!get_option('XAGIO_CF_TEMPLATES')) {
                update_option('XAGIO_CF_TEMPLATES', $option);

                wp_send_json([
                    'status'  => 'success',
                    'message' => "<i class='uk-icon-check'></i> Successfully saved template",
                    'data'    => get_option('XAGIO_CF_TEMPLATES')
                ]);
            } else {
                $XAGIO_CF_TEMPLATES        = get_option('XAGIO_CF_TEMPLATES');
                $XAGIO_CF_TEMPLATES[$xagio_name] = $option[$xagio_name];
                update_option('XAGIO_CF_TEMPLATES', $XAGIO_CF_TEMPLATES);

                wp_send_json([
                    'status'  => 'success',
                    'message' => "<i class='uk-icon-check'></i> Successfully saved template",
                    'data'    => get_option('XAGIO_CF_TEMPLATES')
                ]);
            }
        }


        public static function applyCfTemplate()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['templateName'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $templateName = sanitize_text_field(wp_unslash($_POST['templateName']));

            if (empty($templateName) || $templateName == "") {
                wp_send_json([
                    'status'  => 'error',
                    'message' => "<i class='uk-icon-exclamation'></i> Template name not defined"
                ]);
            }

            update_option('XAGIO_CF_DEFAULT_TEMPLATE', $templateName);
            wp_send_json([
                'status'  => 'success',
                'message' => "<i class='uk-icon-check'></i> Template successfully applied"
            ]);

        }

        public static function createCfTemplate()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // Check if the required 'name' parameter exists
            if (!isset($_POST['name'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // Sanitize the 'name' field
            $xagio_name = sanitize_text_field(wp_unslash($_POST['name']));

            // Unset unnecessary data
            unset($_POST['action']);
            unset($_POST['name']);

            // Check if the name is empty
            if (empty($xagio_name) || $xagio_name == "") {
                wp_send_json([
                    'status'  => 'error',
                    'message' => "<i class='uk-icon-exclamation'></i> Template name not defined"
                ]);
            }

            // Sanitize and process the required fields
            $volume_red         = isset($_POST['volume_red']) ? absint(wp_unslash($_POST['volume_red'])) : 0;
            $volume_green       = isset($_POST['volume_green']) ? absint(wp_unslash($_POST['volume_green'])) : 0;
            $cpc_red            = isset($_POST['cpc_red']) ? absint(wp_unslash($_POST['cpc_red'])) : 0;
            $cpc_green          = isset($_POST['cpc_green']) ? absint(wp_unslash($_POST['cpc_green'])) : 0;
            $intitle_red        = isset($_POST['intitle_red']) ? absint(wp_unslash($_POST['intitle_red'])) : 0;
            $intitle_green      = isset($_POST['intitle_green']) ? absint(wp_unslash($_POST['intitle_green'])) : 0;
            $inurl_red          = isset($_POST['inurl_red']) ? absint(wp_unslash($_POST['inurl_red'])) : 0;
            $inurl_green        = isset($_POST['inurl_green']) ? absint(wp_unslash($_POST['inurl_green'])) : 0;
            $title_ratio_red    = isset($_POST['title_ratio_red']) ? floatval(wp_unslash($_POST['title_ratio_red'])) : 0;
            $title_ratio_green  = isset($_POST['title_ratio_green']) ? floatval(wp_unslash($_POST['title_ratio_green'])) : 0;
            $tr_goldbar_volume  = isset($_POST['tr_goldbar_volume']) ? absint(wp_unslash($_POST['tr_goldbar_volume'])) : 0;
            $tr_goldbar_intitle = isset($_POST['tr_goldbar_intitle']) ? absint(wp_unslash($_POST['tr_goldbar_intitle'])) : 0;
            $url_ratio_red      = isset($_POST['url_ratio_red']) ? floatval(wp_unslash($_POST['url_ratio_red'])) : 0;
            $url_ratio_green    = isset($_POST['url_ratio_green']) ? floatval(wp_unslash($_POST['url_ratio_green'])) : 0;
            $ur_goldbar_volume  = isset($_POST['ur_goldbar_volume']) ? absint(wp_unslash($_POST['ur_goldbar_volume'])) : 0;
            $ur_goldbar_intitle = isset($_POST['ur_goldbar_intitle']) ? absint(wp_unslash($_POST['ur_goldbar_intitle'])) : 0;

            // Check for invalid fields (negative or empty)
            $fields = [
                'volume_red'         => $volume_red,
                'volume_green'       => $volume_green,
                'cpc_red'            => $cpc_red,
                'cpc_green'          => $cpc_green,
                'intitle_red'        => $intitle_red,
                'intitle_green'      => $intitle_green,
                'inurl_red'          => $inurl_red,
                'inurl_green'        => $inurl_green,
                'title_ratio_red'    => $title_ratio_red,
                'title_ratio_green'  => $title_ratio_green,
                'tr_goldbar_volume'  => $tr_goldbar_volume,
                'tr_goldbar_intitle' => $tr_goldbar_intitle,
                'url_ratio_red'      => $url_ratio_red,
                'url_ratio_green'    => $url_ratio_green,
                'ur_goldbar_volume'  => $ur_goldbar_volume,
                'ur_goldbar_intitle' => $ur_goldbar_intitle,
            ];

            foreach ($fields as $xagio_key => $val) {
                if ($val < 0 || $val === '') {
                    wp_send_json([
                        'status'  => 'error',
                        'message' => "<i class='uk-icon-exclamation'></i> All fields must be at least 0 and cannot be empty, field $xagio_key"
                    ]);
                }
            }

            // Prepare the option to save
            $option[$xagio_name] = [
                'name' => $xagio_name,
                'data' => $fields,
            ];

            // Save or update the options
            if (!get_option('XAGIO_CF_TEMPLATES')) {
                update_option('XAGIO_CF_TEMPLATES', $option);
                if (!get_option('XAGIO_CF_DEFAULT_TEMPLATE')) {
                    update_option('XAGIO_CF_DEFAULT_TEMPLATE', $xagio_name);
                }
                wp_send_json([
                    'status'  => 'success',
                    'message' => "<i class='uk-icon-check'></i> Successfully saved template",
                    'data'    => get_option('XAGIO_CF_TEMPLATES')
                ]);
            } else {
                $XAGIO_CF_TEMPLATES = get_option('XAGIO_CF_TEMPLATES');

                if (isset($XAGIO_CF_TEMPLATES[$xagio_name])) {
                    wp_send_json([
                        'status'  => 'error',
                        'message' => "<i class='uk-icon-exclamation'></i> Template with this name already exists, please choose a different name"
                    ]);
                } else {
                    $XAGIO_CF_TEMPLATES[$xagio_name] = $option[$xagio_name];

                    if (!get_option('XAGIO_CF_DEFAULT_TEMPLATE')) {
                        update_option('XAGIO_CF_DEFAULT_TEMPLATE', $xagio_name);
                    }

                    update_option('XAGIO_CF_TEMPLATES', $XAGIO_CF_TEMPLATES);
                    wp_send_json([
                        'status'  => 'success',
                        'message' => "<i class='uk-icon-check'></i> Successfully saved template",
                        'data'    => get_option('XAGIO_CF_TEMPLATES')
                    ]);
                }
            }
        }

        public static function deleteCfTemplate()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['templateName'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $xagio_name = sanitize_text_field(wp_unslash($_POST['templateName']));

            if (empty($xagio_name) || $xagio_name == "") {
                wp_send_json([
                    'status'  => 'error',
                    'message' => "<i class='uk-icon-exclamation'></i> Template name not defined"
                ]);
            }

            if (!get_option('XAGIO_CF_DEFAULT_TEMPLATE')) {
                if (!get_option('XAGIO_CF_TEMPLATES')) {
                    wp_send_json([
                        'status'  => 'error',
                        'message' => "<i class='uk-icon-exclamation'></i> You cannot delete Default Template"
                    ]);
                }
            } else {
                if (get_option('XAGIO_CF_DEFAULT_TEMPLATE') == $xagio_name) {
                    wp_send_json([
                        'status'  => 'error',
                        'message' => "<i class='uk-icon-exclamation'></i> You cannot delete Default Template"
                    ]);
                }
            }

            $XAGIO_CF_TEMPLATES = get_option('XAGIO_CF_TEMPLATES');
            unset($XAGIO_CF_TEMPLATES[$xagio_name]);
            update_option('XAGIO_CF_TEMPLATES', $XAGIO_CF_TEMPLATES);
            wp_send_json([
                'status'  => 'success',
                'message' => "<i class='uk-icon-check'></i> Successfully deleted",
                'data'    => get_option('XAGIO_CF_TEMPLATES')
            ]);
        }

        public static function createTable()
        {
            global $wpdb;
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $charset_collate = $wpdb->get_charset_collate();
            $creation_query  = 'CREATE TABLE xag_groups (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `project_id` int(11),
                    `id_page_post` int(11),
                    `id_taxonomy` int(11),
                    `external_domain` varchar(255),
                    `group_name` varchar(255),
                    `title` varchar(255),
                    `url` varchar(255),
                    `description` text,
                    `h1` varchar(255),
                    `date_created` datetime,
                    `position` int(11) default 999,
                    `notes` longtext,
                    PRIMARY KEY  (`id`)
                ) ' . $charset_collate . ';';
            @dbDelta($creation_query);
        }

        public static function removeTable()
        {
            global $wpdb;
            $wpdb->query("DROP TABLE IF EXISTS xag_groups;");
        }

    }

}