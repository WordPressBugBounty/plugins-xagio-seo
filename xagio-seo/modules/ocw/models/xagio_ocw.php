<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_OCW')) {

    class XAGIO_MODEL_OCW
    {
        public static $timeout = 7200;

        public static function initialize()
        {
            add_action('xagio_run_ocw_wizard', [
                'XAGIO_MODEL_OCW',
                'runWizard'
            ]);

	        add_filter('http_request_args', [
		        'XAGIO_MODEL_OCW',
		        'requestArgs'
	        ], 10, 2);

            // Add custom cron interval
            add_filter('cron_schedules', function ($schedules) {
                $schedules['minute']      = [
                    'interval' => 60,
                    // 1 minute
                    'display'  => 'Every Minute'
                ];
                $schedules['ten_minutes'] = [
                    'interval' => 600,
                    'display'  => 'Every 10 Minutes'
                ];
                return $schedules;
            });

            if (!wp_next_scheduled('xagio_run_ocw_wizard')) {
                wp_schedule_event(time(), 'ten_minutes', 'xagio_run_ocw_wizard');
            }

            if (!XAGIO_HAS_ADMIN_PERMISSIONS)
                return;

            add_action('admin_post_xagio_ocw_step', [
                'XAGIO_MODEL_OCW',
                'saveStep'
            ]);

            add_action('admin_post_xagio_ocw_get_steps', [
                'XAGIO_MODEL_OCW',
                'getSteps'
            ]);

            add_action('admin_post_xagio_ocw_check_statuses', [
                'XAGIO_MODEL_OCW',
                'checkStatuses'
            ]);

            add_action('admin_post_xagio_ocw_save_project_id', [
                'XAGIO_MODEL_OCW',
                'saveProjectId'
            ]);

            add_action('admin_post_xagio_ocw_get_post_titles', [
                'XAGIO_MODEL_OCW',
                'getPostTitles'
            ]);

            add_action('admin_post_xagio_ocw_set_homepage', [
                'XAGIO_MODEL_OCW',
                'setHomepage'
            ]);

            add_action('admin_post_xagio_ocw_reset_wizard', [
                'XAGIO_MODEL_OCW',
                'resetWizard'
            ]);

            add_action('admin_post_xagio_ocw_get_templates', [
                'XAGIO_MODEL_OCW',
                'getTemplates'
            ]);

            add_action('admin_post_xagio_ocw_get_template', [
                'XAGIO_MODEL_OCW',
                'getTemplate'
            ]);

            add_action('admin_post_xagio_ocw_claim_template', [
                'XAGIO_MODEL_OCW',
                'claimTemplate'
            ]);
            add_action('admin_post_xagio_ocw_update_group_name', [
                'XAGIO_MODEL_OCW',
                'updateGroupLabel'
            ]);

            add_action('admin_post_xagio_ocw_install_elementor', [
                'XAGIO_MODEL_OCW',
                'installElementor'
            ]);

            add_action('admin_post_xagio_ocw_install_kadence', [
                'XAGIO_MODEL_OCW',
                'installKadence'
            ]);

            add_action('admin_post_xagio_kadence_import', [
                'XAGIO_MODEL_OCW',
                'installKadenceImport'
            ]);
        }

		public static function requestArgs ($xagio_args, $xagio_url) {
			if (strpos($xagio_url, 'cdn.xagio.net/') !== false) {
				$xagio_args['user-agent'] = 'XagioUpdater/1.0 (' . home_url('/') . ') WordPress/' . get_bloginfo('version');
			}
			return $xagio_args;
		}

        public static function resetWizard()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $xagio_ocw_steps = get_option('XAGIO_OCW', [
                'step' => 'not_running',
                'data' => []
            ]);

            $xagio_ocw_steps_data = isset($xagio_ocw_steps['data']) && is_array($xagio_ocw_steps['data']) ? $xagio_ocw_steps['data'] : [];

            $xagio_keep = [
                'editor_type'  => $xagio_ocw_steps_data['editor_type']  ?? '',
                'template_key' => $xagio_ocw_steps_data['template_key'] ?? '',
                'templates'    => isset($xagio_ocw_steps_data['templates']) ? (int) $xagio_ocw_steps_data['templates'] : 0,
            ];

            update_option('XAGIO_OCW', [
                'step' => 'not_running',
                'data' => $xagio_keep
            ]);
        }

        public static function getTemplates()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // Send API request.
            $xagio_http_code = 0;
            $templates = XAGIO_API::apiRequest('templates', 'GET', [], $xagio_http_code);

            xagio_json('success', 'Templates successfully retrieved!', $templates);
        }


        public static function getTemplate()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['template_key'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // Sanitize the input
            $template_platform = sanitize_text_field(wp_unslash($_POST['template_platform']));

            if(empty($template_platform)) $template_platform = 'elementor';
            $elementor = 'free';

            if($template_platform === 'elementor') {
                $elementor = self::checkElementorStatus();
                if (!$elementor) {
                    xagio_json('error', 'Elementor is not installed on this website!');
                }
            } else {
                $kadence = self::checkKadenceStatus();
                if (!$kadence) {
                    xagio_json('error', 'Kadence is not installed on this website!');
                }
            }

            // Sanitize the input
            $template_key = sanitize_text_field(wp_unslash($_POST['template_key']));
            $xagio_template     = XAGIO_PATH . '/templates/' . $template_platform . '/' . $template_key . '.zip';
            if (file_exists($xagio_template) && !XAGIO_DEV_MODE) {
                xagio_json('success', 'Template already downloaded.', XAGIO_URL . 'templates/' . $template_platform . '/' . $template_key . '.zip?ver=' . md5(microtime()));
            } else {
                $xagio_http_code = 0;
                $xagio_result    = XAGIO_API::apiRequest('templates', 'GET', [
                    'key'  => $template_key,
                    'type' => $elementor,
                    'platform' => $template_platform
                ], $xagio_http_code, false, $xagio_template);

                if ($xagio_http_code == 200) {
                    xagio_json('success', 'Template downloaded.', XAGIO_URL . 'templates/' . $template_platform . '/' . $template_key . '.zip?ver=' . md5(microtime()));
                } else {
                    xagio_json('error', $xagio_result['message']);
                }
            }
        }

        public static function claimTemplate()
        {

            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['template_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // Sanitize the input
            $template_id = intval($_POST['template_id']);

            // Claim template first
            // Send API request.
            $xagio_http_code = 0;
            $xagio_result    = XAGIO_API::apiRequest('templates', 'POST', [
                'id' => $template_id
            ], $xagio_http_code);

            if ($xagio_http_code == 200) {
                xagio_json('success', 'Template claimed successfully!');
            } else {
                xagio_json('error', $xagio_result['message']);
            }

        }


        public static function runWizard()
        {
            global $wpdb;
            $xagio_ocw_steps = get_option('XAGIO_OCW', [
                'step' => 'not_running',
                'data' => []
            ]);

            // Only run if wizard is active.
            if ($xagio_ocw_steps['step'] !== 'running_wizard') {
                return;
            }

            // Only run if there are no errors
            if (isset($xagio_ocw_steps['data']['error']) && !empty($xagio_ocw_steps['data']['error'])) {
                return;
            }

            // -----------------------------------------------------------
            // Competition Data (Progress 1)
            // -----------------------------------------------------------
            if ($xagio_ocw_steps['data']['progress'] == 1) {
                if (isset($xagio_ocw_steps['data']['batch_id'])) {
                    // Check if batch ID has been processed.
                    $batch_id = $xagio_ocw_steps['data']['batch_id'];
                    // Use the column "id" (as per your current schema) to check.
                    $batch = $wpdb->get_row($wpdb->prepare("SELECT `id` FROM xag_batches WHERE `batch_id` = %d", $batch_id), ARRAY_A);

                    // If waiting is active, check for timeout.
                    if (!empty($xagio_ocw_steps['data']['wait']) && $xagio_ocw_steps['data']['wait'] === true) {
                        if (!isset($xagio_ocw_steps['data']['wait_started'])) {
                            $xagio_ocw_steps['data']['wait_started'] = time();
                            update_option('XAGIO_OCW', $xagio_ocw_steps);
                        } else {
                            if (time() - $xagio_ocw_steps['data']['wait_started'] >= self::$timeout) {
                                $xagio_ocw_steps['data']['error'] = 'Timeout waiting for Competition Data processing.';
                                update_option('XAGIO_OCW', $xagio_ocw_steps);
                                return;
                            }
                        }
                    }
                    // If the batch no longer exists, competition processing is complete.
                    if (!$batch || !isset($batch['id'])) {
                        $xagio_ocw_steps['data']['progress'] = 2;
                        $xagio_ocw_steps['data']['wait']     = false;
                        unset($xagio_ocw_steps['data']['wait_started']);
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                    } else {
                        // If the batch exists and wait flag is not set, start waiting.
                        if (empty($xagio_ocw_steps['data']['wait'])) {
                            $xagio_ocw_steps['data']['wait']         = true;
                            $xagio_ocw_steps['data']['wait_started'] = time();
                            update_option('XAGIO_OCW', $xagio_ocw_steps);
                        }
                    }
                } else {
                    // Push keywords to check competition and get Batch_ID.
                    $project_id = $xagio_ocw_steps['data']['project_id'] ?? 0;
                    $xagio_language   = $xagio_ocw_steps['data']['competition_language'] ?? '';
                    $xagio_location   = $xagio_ocw_steps['data']['competition_location'] ?? '';

                    // Rank Tracker
                    $rank_tracker_search_engine   = $xagio_ocw_steps['data']['rank_tracker']['search_engines'];
                    $rank_tracker_search_country  = $xagio_ocw_steps['data']['rank_tracker']['search_country'];
                    $rank_tracker_search_location = $xagio_ocw_steps['data']['rank_tracker']['search_location'];
                    $rank_tracker_search_locname  = $xagio_ocw_steps['data']['rank_tracker']['search_location_name'];

                    // Get group IDs
                    $groups    = $wpdb->get_results($wpdb->prepare("SELECT id FROM xag_groups WHERE project_id = %d", $project_id), ARRAY_A);
                    $group_ids = array_column($groups, 'id');

                    if (empty($group_ids)) {
                        $xagio_ocw_steps['data']['error'] = 'No groups found for project.';
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                        return;
                    }

                    // Get keywords
                    $xagio_placeholders    = implode(',', array_fill(0, count($group_ids), '%d'));
                    $keywords_result = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT id, keyword, intitle, inurl FROM xag_keywords WHERE group_id IN ($xagio_placeholders)", ...$group_ids
                        ), ARRAY_A
                    );

                    $keyword_text             = [];
                    $keyword_ids              = [];
                    $incomplete_keyword_ids   = [];
                    $incomplete_keyword_texts = [];

                    if ($keywords_result) {
                        foreach ($keywords_result as $keyword) {
                            $keyword_text[] = $keyword['keyword'];
                            $keyword_ids[]  = $keyword['id'];

                            $has_valid_metrics = ($keyword['intitle'] !== null && is_numeric($keyword['intitle']) && $keyword['inurl'] !== null && is_numeric($keyword['inurl']));

                            if (!$has_valid_metrics) {
                                $incomplete_keyword_ids[]   = $keyword['id'];
                                $incomplete_keyword_texts[] = $keyword['keyword'];
                            }
                        }
                    }

                    if (empty($keyword_text) || empty($keyword_ids)) {
                        $xagio_ocw_steps['data']['error'] = 'You must send at least one keyword to analysis.';
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                        return;
                    }

                    if (XAGIO_CONNECTED) {

                        // Always send to Rank Tracker (all keywords)
                        $r_http_code = 0;
                        $xagio_result      = XAGIO_API::apiRequest('rank_tracker', 'POST', [
                            'url'             => site_url(),
                            'keywords'        => $keyword_text,
                            'search_engines'  => $rank_tracker_search_engine,
                            'search_country'  => $rank_tracker_search_country,
                            'search_location' => $rank_tracker_search_location,
                            'location_name'   => $rank_tracker_search_locname
                        ], $r_http_code);

                        if ($r_http_code != 200) {
                            XAGIO_API::apiRequest('log', 'POST', [
                                'action' => 'Agent X - Rank Tracker ERROR',
                                'data'   => json_encode($xagio_result, JSON_PRETTY_PRINT)
                            ]);
                        }

                    }

                    // Skip Competition step if nothing is incomplete
                    if (empty($incomplete_keyword_ids)) {
                        $xagio_ocw_steps['data']['progress'] = 2;
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                        return;
                    }

                    // Else send only incomplete keywords to Competition API
                    $xagio_http_code = 0;
                    $xagio_result    = XAGIO_API::apiRequest('keywords', 'POST', [
                        'keywords' => $incomplete_keyword_texts,
                        'ids'      => $incomplete_keyword_ids,
                        'language' => $xagio_language,
                        'location' => $xagio_location,
                    ], $xagio_http_code);

                    if ($xagio_http_code == 200 && isset($xagio_result['message'])) {
                        // Mark all these as queued and with -1 to signal "processing"
                        foreach ($incomplete_keyword_ids as $id) {
                            $wpdb->update('xag_keywords', [
                                'queued'  => 1,
                                'inurl'   => -1,
                                'intitle' => -1,
                            ], ['id' => $id]);
                        }

                        $BATCH_ID = $xagio_result['message'];
                        $wpdb->insert('xag_batches', [
                            'batch_id'     => $BATCH_ID,
                            'date_created' => gmdate('Y-m-d H:i:s'),
                        ]);

                        $xagio_ocw_steps['data']['batch_id']     = $BATCH_ID;
                        $xagio_ocw_steps['data']['wait']         = true;
                        $xagio_ocw_steps['data']['wait_started'] = time();
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                    } else {
                        $xagio_ocw_steps['data']['error'] = isset($xagio_result['message']) ? $xagio_result['message'] : 'API request failed.';
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                    }
                }
            }

            // -----------------------------------------------------------
            // SEO Suggestions (Progress 2)
            // -----------------------------------------------------------
            if ($xagio_ocw_steps['data']['progress'] == 2) {
                $project_id = $xagio_ocw_steps['data']['project_id'] ?? 0;
                $groups     = $wpdb->get_results($wpdb->prepare("SELECT id FROM xag_groups WHERE project_id = %d", $project_id), ARRAY_A);
                $group_ids  = [];
                foreach ($groups as $xagio_group) {
                    $group_ids[] = $xagio_group['id'];
                }

                if (empty($group_ids)) {
                    $xagio_ocw_steps['data']['error'] = 'No groups found for project.';
                    update_option('XAGIO_OCW', $xagio_ocw_steps);
                    return;
                }

                // Check if waiting for AI response.
                if (!empty($xagio_ocw_steps['data']['wait']) && $xagio_ocw_steps['data']['wait'] === true) {
                    // Initialize wait_started if not set.
                    if (!isset($xagio_ocw_steps['data']['wait_started'])) {
                        $xagio_ocw_steps['data']['wait_started'] = time();
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                    } else {
                        // Check if more than 30 minutes have passed.
                        if (time() - $xagio_ocw_steps['data']['wait_started'] >= self::$timeout) {
                            $xagio_ocw_steps['data']['error'] = 'Timeout waiting for SEO Suggestions.';
                            update_option('XAGIO_OCW', $xagio_ocw_steps);
                        }
                    }
                    if (XAGIO_MODEL_AI::checkAiStatusByIds($group_ids, 'SEO_SUGGESTIONS_MAIN_KW')) {
                        $xagio_ocw_steps['data']['progress'] = 3;
                        $xagio_ocw_steps['data']['wait']     = false;
                        unset($xagio_ocw_steps['data']['wait_started']);
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                    }
                } else {

                    // Truncate all "completed" AI Requests
                    $wpdb->query("DELETE FROM xag_ai WHERE `status` = 'completed'");

                    $xagio_output = XAGIO_MODEL_AI::getAiSuggestionsByGroups($group_ids);

                    if (isset($xagio_output['status']) && $xagio_output['status'] == 'error') {
                        $xagio_ocw_steps['data']['error'] = $xagio_output['message'];
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                    } else {
                        $xagio_ocw_steps['data']['wait'] = true;
                        // Set the wait_started timer.
                        $xagio_ocw_steps['data']['wait_started'] = time();
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                    }
                }
            }

            // -----------------------------------------------------------
            // Create Posts (Progress 3)
            // -----------------------------------------------------------
            if ($xagio_ocw_steps['data']['progress'] == 3) {
                $project_id = $xagio_ocw_steps['data']['project_id'] ?? 0;
                $groups     = $wpdb->get_results($wpdb->prepare("SELECT id FROM xag_groups WHERE project_id = %d", $project_id), ARRAY_A);
                $group_ids  = [];
                foreach ($groups as $xagio_group) {
                    $group_ids[] = $xagio_group['id'];
                }
                if (empty($group_ids)) {
                    $xagio_ocw_steps['data']['error'] = 'No groups found for project.';
                    update_option('XAGIO_OCW', $xagio_ocw_steps);
                    return;
                }
                $xagio_input        = 'SEO_SUGGESTIONS_MAIN_KW';
                $xagio_placeholders = implode(',', array_fill(0, count($group_ids), '%d'));

                $xagio_ai_results = $wpdb->get_results(
                    $wpdb->prepare(
                        "
    SELECT x1.output, x1.target_id
    FROM xag_ai x1
    INNER JOIN (
        SELECT MAX(id) AS max_id
        FROM xag_ai
        WHERE input = %s AND target_id IN ($xagio_placeholders)
        GROUP BY target_id
    ) x2 ON x1.id = x2.max_id
", $xagio_input, ...$group_ids
                    ), ARRAY_A
                );

                if (!$xagio_ai_results) {
                    $xagio_ocw_steps['data']['error'] = 'No AI results found.';
                    update_option('XAGIO_OCW', $xagio_ocw_steps);
                    return;
                }

                $posts = [];

                foreach ($xagio_ai_results as $ai_result) {
                    $group_id  = $ai_result['target_id'];
                    $ai_result = $ai_result['output'];
                    $ai_result = str_replace("\\n", "\n", $ai_result);
                    $ai_result = stripslashes_deep($ai_result);
                    $data      = json_decode($ai_result, true);
                    if (!$data || !isset($data[0])) {
                        // Skip invalid JSON or unexpected structure.
                        continue;
                    }
                    $data_item = $data[0];
                    if (!isset($data_item['h1'], $data_item['title'], $data_item['description'])) {
                        continue;
                    }

                    $post_id = 0;

                    $page_type = 'Service';
                    if (isset($xagio_ocw_steps['data']['homepage_group'])) {
                        if ($xagio_ocw_steps['data']['homepage_group'] == $group_id) {
                            $page_type = 'Home';
                        }
                    }

                    $template_page = self::getTemplatePage($page_type);

                    if ((!empty($xagio_ocw_steps['data']['templates']) && $xagio_ocw_steps['data']['templates'] == 1) || $template_page !== false) {

                        // Duplicate the "Service" page
                        $xagio_post_data = [
                            'post_title'   => $data_item['h1'],
                            'post_content' => $template_page->post_content,
                            'post_status'  => 'publish',
                            'post_type'    => 'page',
                        ];
                        $post_id   = wp_insert_post($xagio_post_data);

                        if ($post_id && !is_wp_error($post_id)) {
                            // Copy all post meta
                            $xagio_meta = get_post_meta($template_page->ID);
                            foreach ($xagio_meta as $xagio_key => $values) {
                                foreach ($values as $xagio_value) {
                                    if ('_elementor_data' === $xagio_key) {
                                        $wpdb->insert(
                                            $wpdb->postmeta, [
                                            'post_id'    => $post_id,
                                            'meta_key'   => '_elementor_data',
                                            'meta_value' => $xagio_value
                                        ], [
                                                '%d',
                                                '%s',
                                                '%s'
                                            ]
                                        );
                                    } else {
                                        add_post_meta($post_id, $xagio_key, maybe_unserialize($xagio_value));
                                    }
                                }
                            }

                            // Custom SEO fields
                            update_post_meta($post_id, 'XAGIO_SEO_TITLE', $data_item['title']);
                            update_post_meta($post_id, 'XAGIO_SEO_DESCRIPTION', $data_item['description']);
                        } else {
                            $xagio_ocw_steps['data']['error'] = 'Failed to create Elementor page with H1: ' . $data_item['h1'];
                            update_option('XAGIO_OCW', $xagio_ocw_steps);
                            return;
                        }

                    } else {
                        // Default logic
                        $post_id = xagio_create_post($data_item['h1'], $data_item['title'], $data_item['description']);
                    }

                    if ($post_id && !is_wp_error($post_id)) {

	                    $posts[] = $post_id;
	                    $wpdb->update('xag_groups', [
		                    'id_page_post' => $post_id,
		                    'title'        => $data_item['title'],
		                    'description'  => $data_item['description'],
		                    'h1'           => $data_item['h1'],
		                    'url'          => get_post_field('post_name', $post_id),
	                    ], [
		                    'id' => $group_id
	                    ]);

                        if (isset($xagio_ocw_steps['data']['homepage_group'])) {
                            if ($xagio_ocw_steps['data']['homepage_group'] == $group_id) {
                                $xagio_ocw_steps['data']['homepage_post_id'] = $post_id;

                                // Set WordPress to show a static page on the front
                                update_option('show_on_front', 'page');

                                // Set the front page to the specified post ID
                                update_option('page_on_front', $post_id);

	                            $wpdb->update('xag_groups', [
		                            'group_name'    => "Home",
		                            'url'           => '',
	                            ], [
		                            'id' => $group_id
	                            ]);
                            }
                        }
                    }
                }

                self::clearElementorCache();

                if (empty($posts)) {
                    $xagio_ocw_steps['data']['error'] = 'No posts were created.';
                    update_option('XAGIO_OCW', $xagio_ocw_steps);
                    return;
                }


                $xagio_is_gutenberg = isset($xagio_ocw_steps['data']['editor_type']) && $xagio_ocw_steps['data']['editor_type'] === 'gutenberg';

                if($xagio_is_gutenberg) {
                    $posts_without_homepage = [];
                    foreach ($posts as $post_id) {
                        if (!isset($xagio_ocw_steps['data']['homepage_post_id']) || $post_id != $xagio_ocw_steps['data']['homepage_post_id']) {
                            $posts_without_homepage[] = $post_id;
                        }
                    }

                    if(empty($posts_without_homepage)) {
                        $posts_without_homepage = $posts;
                    }

                    self::xagio_add_service_links($posts_without_homepage);
                } else {
                    // Add new pages to menu under 'Service', excluding homepage
                    $menu = wp_get_nav_menu_object('main-menu');

                    if (!$menu) {
                        $menu = wp_get_nav_menu_object('primary');
                    }

                    if (!$menu) {
                        $menus = wp_get_nav_menus(); // get all menus
                        if (!empty($menus)) {
                            $menu = reset($menus); // take the first one
                        }
                    }

                    $menu_id = $menu ? $menu->term_id : 0;

                    $menu_items      = wp_get_nav_menu_items($menu_id);
                    $service_menu_id = 0;

                    if ($menu_items) {
                        foreach ($menu_items as $xagio_item) {
                            if (stripos($xagio_item->title, 'Service') !== false) {
                                $service_menu_id = $xagio_item->ID;
                                break;
                            }
                        }
                    }

                    if ($menu_id && $service_menu_id && !empty($posts)) {
                        foreach ($posts as $post_id) {
                            if (!isset($xagio_ocw_steps['data']['homepage_post_id']) || $post_id != $xagio_ocw_steps['data']['homepage_post_id']) {
                                wp_update_nav_menu_item($menu_id, 0, [
                                    'menu-item-title'     => get_the_title($post_id),
                                    'menu-item-object'    => 'page',
                                    'menu-item-object-id' => $post_id,
                                    'menu-item-type'      => 'post_type',
                                    'menu-item-status'    => 'publish',
                                    'menu-item-parent-id' => $service_menu_id,
                                ]);
                            }
                        }
                    }
                }


                $xagio_ocw_steps['data']['posts']    = $posts;
                $xagio_ocw_steps['data']['progress'] = 4;
                update_option('XAGIO_OCW', $xagio_ocw_steps);
            }

            // -----------------------------------------------------------
            // Create Content (Progress 4)
            // -----------------------------------------------------------
            if ($xagio_ocw_steps['data']['progress'] == 4) {
                $posts = $xagio_ocw_steps['data']['posts'] ?? [];
                if (empty($posts)) {
                    $xagio_ocw_steps['data']['error'] = 'No posts available for content creation.';
                    update_option('XAGIO_OCW', $xagio_ocw_steps);
                    return;
                }
                $xagio_input       = 'PAGE_CONTENT';
                $xagio_useTemplate = ((!empty($xagio_ocw_steps['data']['templates']) && $xagio_ocw_steps['data']['templates'] == 1) || self::getTemplatePage() !== false);
                if ($xagio_useTemplate) {
                    $xagio_input = 'PAGE_CONTENT_TEMPLATE';
                }

                if (!empty($xagio_ocw_steps['data']['wait']) && $xagio_ocw_steps['data']['wait'] === true) {
                    // Initialize or check wait timer.
                    if (!isset($xagio_ocw_steps['data']['wait_started'])) {
                        $xagio_ocw_steps['data']['wait_started'] = time();
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                    } else {
                        if (time() - $xagio_ocw_steps['data']['wait_started'] >= self::$timeout) {
                            $xagio_ocw_steps['data']['error'] = 'Timeout waiting for Page Content generation.';
                            update_option('XAGIO_OCW', $xagio_ocw_steps);
                            return;
                        }
                    }

                    if (XAGIO_MODEL_AI::checkAiStatusByIds($posts, $xagio_input)) {
                        $xagio_ocw_steps['data']['progress'] = 5;
                        $xagio_ocw_steps['data']['wait']     = false;
                        unset($xagio_ocw_steps['data']['wait_started']);
                        update_option('XAGIO_OCW', $xagio_ocw_steps);

                        // Retrieve generated content.
                        $xagio_placeholders = implode(',', array_fill(0, count($posts), '%d'));
                        $xagio_ai_results   = $wpdb->get_results(
                            $wpdb->prepare(
                                "SELECT `target_id`, `output` FROM xag_ai WHERE `input` = %s AND `target_id` IN ($xagio_placeholders)", $xagio_input, ...$posts
                            ), ARRAY_A
                        );

                        $xagio_is_gutenberg = $xagio_ocw_steps['data']['editor_type'] === 'gutenberg';

                        foreach ($xagio_ai_results as $xagio_result) {
                            $post_id = $xagio_result['target_id'];

                            if ($xagio_useTemplate) {

                                if($xagio_is_gutenberg) {

                                    $xagio_decoded_output = XAGIO_MODEL_AI::safeJsonDecode($xagio_result['output']);

                                    if (!is_array($xagio_decoded_output)) {
                                        $xagio_ocw_steps['data']['error'] = 'Failed to decode Gutenberg AI response for post ID ' . $post_id . '.';
                                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                                        return;
                                    }

                                    $post = get_post($post_id);

                                    if (!$post) {
                                        $xagio_ocw_steps['data']['error'] = 'Unable to load Gutenberg post with ID ' . $post_id . '.';
                                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                                        return;
                                    }

                                    $xagio_content = $post->post_content;

                                    if (!function_exists('has_blocks') || !function_exists('parse_blocks') || !function_exists('serialize_blocks')) {
                                        $xagio_ocw_steps['data']['error'] = 'Gutenberg functions are unavailable on this site.';
                                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                                        return;
                                    }

                                    $xagio_blocks        = parse_blocks($xagio_content);
                                    $xagio_updatedBlocks = self::gutenbergApplyTexts($xagio_blocks, $xagio_decoded_output);
                                    $xagio_newContent    = serialize_blocks($xagio_updatedBlocks);

                                    self::xagio_fix_agentx_gutenberg_menu();
                                    self::xagio_fix_kadence_map_location();
                                    self::xagio_fix_kadence_footer_map_location();
                                    self::xagio_update_footer_social_links_from_profiles();

                                    wp_update_post([
                                        'ID'           => $post_id,
                                        'post_content' => $xagio_newContent,
                                    ]);

                                } else {
                                    $xagio_elementorData = json_decode(get_post_meta($post_id, '_elementor_data', true), true);

                                    // Modify
                                    $xagio_modifiedData = json_decode($xagio_result['output'], true);

	                                if ($xagio_modifiedData === null) {
		                                $output = str_replace(['“','”','„','‟'], '"', $xagio_result['output']);
		                                $output = preg_replace_callback(
			                                '/<([a-zA-Z0-9]+)(.*?)>(.*?)<\/\1>/s',
			                                function ($matches) {
				                                $content = $matches[3];
				                                $content = preg_replace('/^"(.*)"$/s', '$1', $content);
				                                $content = str_replace('\\', '\\\\', $content);
				                                $content = str_replace('"', '\\"', $content);
				                                return "<{$matches[1]}{$matches[2]}>$content</{$matches[1]}>";
			                                },
			                                $output
		                                );
		                                $output = preg_replace('/(?<!^)"{2}(?!$)/', '"', $output);

		                                $output = preg_replace('/[\x00-\x1F\x7F]/', '', $output);
		                                $output = preg_replace_callback('/<[^>]+>/', function($match) {
			                                return str_replace('"', '\\"', $match[0]);
		                                }, $output);

		                                $xagio_modifiedData = json_decode($output, true);
	                                }

                                    // Merge
                                    $xagio_mergedData = self::combineFieldsIntoJson($xagio_elementorData, $xagio_modifiedData);
                                    $xagio_mergedData = wp_json_encode($xagio_mergedData);

                                    // Update
                                    update_post_meta($post_id, '_elementor_data', wp_slash($xagio_mergedData));

                                    self::clearElementorCache();
                                }

                            } else {
                                $xagio_post_content = $xagio_result['output'];
                                // Update the post with the generated content.
                                $xagio_post_data = [
                                    'ID'           => $post_id,
                                    'post_content' => $xagio_post_content,
                                ];
                                wp_update_post($xagio_post_data);
                            }
                        }

                    }
                } else {

                    $xagio_output = null;

                    if ($xagio_useTemplate) {

                        if (isset($xagio_ocw_steps['data']['gutenberg_posts'])) {
                            unset($xagio_ocw_steps['data']['gutenberg_posts']);
                        }

                        $xagio_elementor_posts = [];
                        $xagio_gutenberg_posts = [];

                        foreach ($posts as $post_id) {
                            $xagio_elementor_data = get_post_meta($post_id, '_elementor_data', true);

                            if (!empty($xagio_elementor_data)) {
                                $xagio_elementor_posts[] = $post_id;
                                continue;
                            }

                            $post = get_post($post_id);

                            if ($post && function_exists('has_blocks') && has_blocks($post->post_content)) {
                                $xagio_gutenberg_posts[] = $post_id;
                            } else {
                                $xagio_elementor_posts[] = $post_id;
                            }
                        }

                        $xagio_aggregate_results = [
                            'status'  => 'success',
                            'results' => []
                        ];

                        if (!empty($xagio_elementor_posts)) {
                            $xagio_output = XAGIO_MODEL_AI::getAiContentByPostsTemplate($xagio_elementor_posts);

                            if (isset($xagio_output['status']) && $xagio_output['status'] == 'error') {
                                $xagio_aggregate_results = $xagio_output;
                            } else {
                                $xagio_aggregate_results['results'] = array_merge($xagio_aggregate_results['results'], $xagio_output['results']);
                            }
                        }

                        if (!empty($xagio_gutenberg_posts) && $xagio_aggregate_results['status'] !== 'error') {
                            $xagio_gutenberg_posts = array_unique($xagio_gutenberg_posts);

                            foreach ($xagio_gutenberg_posts as $post_id) {
                                $post = get_post($post_id);

                                if (!$post) {
                                    $xagio_aggregate_results = [
                                        'status'  => 'error',
                                        'message' => 'Unable to load Gutenberg post with ID ' . $post_id . '.',
                                    ];
                                    break;
                                }

                                if (!function_exists('parse_blocks')) {
                                    $xagio_aggregate_results = [
                                        'status'  => 'error',
                                        'message' => 'Gutenberg block parser is unavailable on this site.',
                                    ];
                                    break;
                                }

                                $xagio_blocks = parse_blocks($post->post_content);
                                $xagio_texts  = self::gutenbergExtractTexts($xagio_blocks);

                                $xagio_args = XAGIO_MODEL_AI::getContentProfiles($post_id);

                                if (!is_array($xagio_args) || (isset($xagio_args['status']) && $xagio_args['status'] === 'error')) {
                                    $xagio_aggregate_results = [
                                        'status'  => 'error',
                                        'message' => isset($xagio_args['message']) ? $xagio_args['message'] : 'Failed to prepare Gutenberg content profile for post ID ' . $post_id . '.',
                                    ];
                                    break;
                                }

                                if (is_array($xagio_args) && isset($xagio_args[5])) {
                                    $xagio_args[5] = wp_json_encode($xagio_texts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                }

                                $xagio_http_code = 0;
                                $xagio_response  = XAGIO_MODEL_AI::_sendAiRequest('PAGE_CONTENT_TEMPLATE', 11, $post_id, $xagio_args, ['post_id' => $post_id], $xagio_http_code);

                                $xagio_aggregate_results['results'][$post_id] = [
                                    'status'  => ($xagio_http_code == 200) ? 'success' : 'error',
                                    'message' => ($xagio_http_code == 406) ? 'Upgrade your account to use AI features.' : ($xagio_response['message'] ?? ''),
                                ];

                                if ($xagio_http_code != 200) {
                                    $xagio_aggregate_results['status']  = 'error';
                                    $xagio_aggregate_results['message'] = $xagio_aggregate_results['results'][$post_id]['message'];
                                    break;
                                }
                            }
                        }

                        if (!empty($xagio_gutenberg_posts) && $xagio_aggregate_results['status'] !== 'error') {
                            $xagio_ocw_steps['data']['gutenberg_posts'] = array_map('intval', $xagio_gutenberg_posts);
                        }

                        $xagio_output = $xagio_aggregate_results;

                    } else {
                        $xagio_output = XAGIO_MODEL_AI::getAiContentByPosts($posts);
                    }

                    if (isset($xagio_output['status']) && $xagio_output['status'] == 'error') {
                        $xagio_ocw_steps['data']['error'] = $xagio_output['message'];
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                    } else {
                        $xagio_ocw_steps['data']['wait']         = true;
                        $xagio_ocw_steps['data']['wait_started'] = time();
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                    }
                }
            }

            // -----------------------------------------------------------
            // SEO Suggestions for Schema (Progress 5)
            // -----------------------------------------------------------
            if ($xagio_ocw_steps['data']['progress'] == 5) {
                $posts = $xagio_ocw_steps['data']['posts'] ?? [];
                if (empty($posts)) {
                    $xagio_ocw_steps['data']['error'] = 'No posts available for schema generation.';
                    update_option('XAGIO_OCW', $xagio_ocw_steps);
                    return;
                }
                if (!empty($xagio_ocw_steps['data']['wait']) && $xagio_ocw_steps['data']['wait'] === true) {
                    if (!isset($xagio_ocw_steps['data']['wait_started'])) {
                        $xagio_ocw_steps['data']['wait_started'] = time();
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                    } else {
                        if (time() - $xagio_ocw_steps['data']['wait_started'] >= self::$timeout) {
                            $xagio_ocw_steps['data']['error'] = 'Timeout waiting for Schema generation.';
                            update_option('XAGIO_OCW', $xagio_ocw_steps);
                            return;
                        }
                    }
                    if (XAGIO_MODEL_AI::checkAiStatusByIds($posts, 'SCHEMA')) {
                        $xagio_ocw_steps['data']['progress'] = 6;
                        $xagio_ocw_steps['data']['wait']     = false;
                        unset($xagio_ocw_steps['data']['wait_started']);
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                    }
                } else {
                    $xagio_output = XAGIO_MODEL_AI::getAiSchemaByPosts($posts);
                    if (isset($xagio_output['status']) && $xagio_output['status'] == 'error') {
                        $xagio_ocw_steps['data']['error'] = $xagio_output['message'];
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                    } else {
                        $xagio_ocw_steps['data']['wait']         = true;
                        $xagio_ocw_steps['data']['wait_started'] = time();
                        update_option('XAGIO_OCW', $xagio_ocw_steps);
                    }
                }
            }

            // -----------------------------------------------------------
            // Finish
            // -----------------------------------------------------------
            if ($xagio_ocw_steps['data']['progress'] == 6) {

                // Define email recipient.
                // You can replace this with a dynamic email address (e.g., the project owner's email) if needed.
                $to = get_option('admin_email');

                // Construct the email subject and message.
                $subject  = 'Xagio Agent X Completed Successfully';
                $site_url = get_site_url();
                $message  = 'Hello,<br><br>' . "Your Agent X has successfully completed all steps on $site_url. " . 'You can now review the generated posts and content on your site.<br><br>' . 'Best regards,<br>Xagio Plugin';

                // Set email headers for HTML email.
                $headers = ['Content-Type: text/html; charset=UTF-8'];

                // Log
                XAGIO_API::apiRequest(
                    'log', 'POST', [
                        'action' => 'Agent X - Finished',
                        'data'   => json_encode($xagio_ocw_steps, JSON_PRETTY_PRINT)
                    ]
                );

                $xagio_ocw_steps['step'] = 'wizard_finished';

                // Send the email.
                if (!wp_mail($to, $subject, $message, $headers)) {
                    $xagio_ocw_steps['data']['error'] = 'Failed to send completion email.';
                }

                if ((!empty($xagio_ocw_steps['data']['templates']) && $xagio_ocw_steps['data']['templates'] == 1)) {
                    $pages = ['Service'];
                    if (isset($xagio_ocw_steps['data']['homepage_group'])) {
                        $pages[] = 'Home';
                    }
                    self::convertToDrafts($pages);
                    self::injectContactUsAddress();
                    self::xagio_fix_agentx_menu_labels_and_urls();
                }

                update_option('XAGIO_OCW', $xagio_ocw_steps);
            }
        }

        public static function setHomepage()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['group_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $ocw = get_option('XAGIO_OCW', [
                'step' => 'not_running',
                'data' => []
            ]);

            // Sanitize the input
            $group_id = absint(wp_unslash($_POST['group_id']));

            if ($group_id !== 0) {

                if (isset($ocw['data']['homepage_group'])) {
                    if ($ocw['data']['homepage_group'] == $group_id) {
                        unset($ocw['data']['homepage_group']);
                        $group_id = 0;
                    } else {
                        $ocw['data']['homepage_group'] = $group_id;
                    }
                } else {
                    $ocw['data']['homepage_group'] = $group_id;
                }

                update_option('XAGIO_OCW', $ocw);

                xagio_json('success', 'Homepage updated successfully!', $group_id);
            } else {

                xagio_json('error', 'Failed to set Homepage');

            }
        }


        public static function getPostTitles()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['post_ids'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $post_ids = sanitize_text_field(wp_unslash($_POST['post_ids']));
            $post_ids = explode(',', $post_ids);

            // Query only pages with those IDs (since we want page titles)
            $xagio_args = array(
                'post_type'   => 'page',
                'post__in'    => $post_ids,
                'orderby'     => 'post__in',
                // Keep them in the same order as the IDs
                'numberposts' => -1
                // Get them all
            );

            $pages  = get_posts($xagio_args);
            $xagio_output = [];
            foreach ($pages as $page) {
                $xagio_output[] = [
                    'id'    => $page->ID,
                    'title' => $page->post_title
                ];
            }


            xagio_json('success', 'Post data retrieved!', $xagio_output);
        }

        public static function saveProjectId()
        {

            $project_id = absint(wp_unslash($_POST['project_id']));

            $xagio_ocw_steps = get_option('XAGIO_OCW', [
                'step' => 'not_running',
                'data' => []
            ]);

            $xagio_ocw_steps['step'] = 'project_created';
            $xagio_ocw_steps['data']['project_id'] = $project_id;

            update_option('XAGIO_OCW', $xagio_ocw_steps);
        }

        public static function getSteps()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');
            $default_steps = [
                'step' => 'not_running',
                'data' => []
            ];

            if (isset($_POST['run_wizard'])) {
                XAGIO_MODEL_OCW::runWizard();
                xagio_json('success', 'Wizard ran successfully.', get_option('XAGIO_OCW', $default_steps));
            } else {
                xagio_json('success', 'Steps data retrieved!', get_option('XAGIO_OCW', $default_steps));
            }

        }

        public static function checkStatuses()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            XAGIO_MODEL_AI::remoteCheckAiStatuses();

            xagio_json('success', 'No completed statuses.');
        }

        public static function saveStep()
        {
            global $wpdb;
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $current_step = sanitize_text_field(wp_unslash($_POST['step']));
            $template_key = sanitize_text_field(wp_unslash($_POST['template_key']));
            $template = absint(wp_unslash($_POST['templates']));

            $xagio_ocw_steps = get_option('XAGIO_OCW', [
                'step' => $current_step,
                'data' => []
            ]);

            $xagio_ocw_steps['step'] = $current_step;

            if(isset($_POST['editor_type'])) {
                $editor_type = sanitize_text_field(wp_unslash($_POST['editor_type']));
                if(empty($editor_type)) $editor_type = 'elementor';
                $xagio_ocw_steps['data']['editor_type'] = $editor_type;
            }


            if(isset($template_key) && !empty($template_key)) {
                $xagio_ocw_steps['data']['template_key'] = $template_key;
            }
            if(isset($template) && !empty($template)) {
                $xagio_ocw_steps['data']['templates'] = $template;
            }

            $remove_pages = absint(wp_unslash($_POST['remove_pages']));

            if ($remove_pages) {
                global $wpdb;

                $pages = get_posts([
                    'post_type'      => 'page',
                    'posts_per_page' => -1,
                    'post_status'    => 'publish',
                    'fields'         => 'ids',
                ]);

                foreach ($pages as $xagio_page_id) {
                    $wpdb->query($wpdb->prepare("UPDATE xag_groups SET id_page_post = 0 WHERE id_page_post = %d", $xagio_page_id));
                }

                // pause counters & caches for speed
                wp_suspend_cache_invalidation( true );

                // Delete attachment FILES
                $attachment_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment'" );
                if ( $attachment_ids ) {
                    foreach ( $attachment_ids as $att_id ) {
                        // true = force delete; also removes generated sizes and meta
                        wp_delete_attachment( (int)$att_id, true );
                    }
                }

                // small GC pause after many file deletes
                if ( function_exists( 'gc_collect_cycles' ) ) { gc_collect_cycles(); }

                // TRUNCATE the heavy tables (fast + clean)
                $tables = [
                    $wpdb->posts,
                    $wpdb->postmeta
                ];

                foreach ( $tables as $table ) {
                    $wpdb->query( "TRUNCATE TABLE {$table}" );
                }

                self::xagio_clear_elementor_and_menu_terms();

                // clear object cache
                if ( function_exists( 'wp_cache_flush' ) ) {
                    wp_cache_flush();
                }

                // Resume counters/caches
                wp_suspend_cache_invalidation( false );

            }

            if ($current_step == 'project_created') {

                $project_id                      = absint(wp_unslash($_POST['project_id']));
                $xagio_ocw_steps['data']['project_id'] = $project_id;

            } else if ($current_step == 'running_wizard') {

                $xagio_language = sanitize_text_field(wp_unslash($_POST['language']));
                $xagio_location = sanitize_text_field(wp_unslash($_POST['location']));

                // Rank Tracker
                $rank_tracker_search_engine   = map_deep(wp_unslash($_POST['rank_tracker_search_engine']), 'absint');
                $rank_tracker_search_country  = sanitize_text_field(wp_unslash($_POST['rank_tracker_search_country']));
                $rank_tracker_search_location = sanitize_text_field(wp_unslash($_POST['rank_tracker_search_location']));
                $rank_tracker_search_locname  = sanitize_text_field(wp_unslash($_POST['locname']));


                $progress                                  = absint(wp_unslash($_POST['progress']));
                $xagio_ocw_steps['data']['progress']             = $progress;
                $xagio_ocw_steps['data']['competition_language'] = $xagio_language;
                $xagio_ocw_steps['data']['competition_location'] = $xagio_location;
                $xagio_ocw_steps['data']['rank_tracker']         = [
                    'search_engines'       => $rank_tracker_search_engine,
                    'search_country'       => $rank_tracker_search_country,
                    'search_location'      => $rank_tracker_search_location,
                    'search_location_name' => $rank_tracker_search_locname,
                ];
                $project_id                                = $xagio_ocw_steps['data']['project_id'];

                $group_ids_delete = [];
                if (!empty($_POST['delete_groups'])) {
                    $group_ids_delete = array_map('intval', explode(',', sanitize_text_field(wp_unslash($_POST['delete_groups']))));
                }

                if (!empty($group_ids_delete)) {
                    $xagio_placeholders = implode(',', array_fill(0, count($group_ids_delete), '%d'));
                    $wpdb->query($wpdb->prepare("DELETE FROM xag_groups WHERE project_id = %d AND id IN ($xagio_placeholders)", $project_id, ...$group_ids_delete));

                    foreach ($group_ids_delete as $group_id) {
                        $wpdb->query($wpdb->prepare("DELETE FROM xag_keywords WHERE group_id = %d", $group_id));
                    }
                }

                // Log
                XAGIO_API::apiRequest(
                    'log', 'POST', [
                        'action' => 'Agent X - Start',
                        'data'   => json_encode($xagio_ocw_steps, JSON_PRETTY_PRINT)
                    ]
                );
            }

            update_option('XAGIO_OCW', $xagio_ocw_steps);
        }

        public static function updateGroupLabel()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['project_id']) || !isset($_POST['group_id'])) {
                xagio_json('error', 'Please check the group id');
            }

            $project_id = intval($_POST['project_id']);
            $group_id   = intval($_POST['group_id']);

            $update_data = [
                'group_name' => isset($_POST['group_name']) ? sanitize_text_field(wp_unslash($_POST['group_name'])) : ''
            ];


            $wpdb->update('xag_groups', $update_data, [
                'id'         => $group_id,
                'project_id' => $project_id
            ]);

            xagio_json('success', 'Group Name successfully saved!');

        }

        public static function checkElementorStatus()
        {
            if (is_plugin_active('elementor/elementor.php')) {
                if (is_plugin_active('elementor-pro/elementor-pro.php')) {
                    return 'pro';
                }
                return 'free';
            }
            return false;
        }

        public static function installKadenceImport() {
            global $wp_filesystem;
            if ( ! $wp_filesystem ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem(); // sets $wp_filesystem
            }

            // Permission + nonce
            if ( ! current_user_can( 'manage_options' ) ) {
                xagio_json('error', __('Permission denied.', 'xagio-seo'));
            }

            // Accept import_url (server downloads) OR uploaded file xagio_template_file (FormData)
            $tmp_path    = '';
            $cleanup_tmp = false;
            $ext         = '';

            $import_url = isset($_POST['import_url']) ? esc_url_raw($_POST['import_url']) : '';

            if ($import_url) {
                if (!function_exists('download_url')) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                }
                $dl = download_url($import_url, 60);
                if (is_wp_error($dl)) {
                    /* translators: %s: error message returned when downloading the import file. */
                    xagio_json('error', sprintf(__('Failed to download import file: %s', 'xagio-seo'), $dl->get_error_message()));
                }
                $tmp_path    = $dl;
                $cleanup_tmp = true;
                $path_from   = wp_parse_url($import_url, PHP_URL_PATH);
                $ext         = strtolower(pathinfo($path_from ?: '', PATHINFO_EXTENSION));
            } elseif (!empty($_FILES['xagio_template_file']['tmp_name'])) {
                $tmp_path = $_FILES['xagio_template_file']['tmp_name'];
                $ext      = strtolower(pathinfo($_FILES['xagio_template_file']['name'], PATHINFO_EXTENSION));
            } else {
                xagio_json('error', __('No import file provided. Pass import_url or upload xagio_template_file.', 'xagio-seo'));
            }

            // === Legacy JSON manifest ===
            if ($ext === 'json') {
                $manifest = json_decode(file_get_contents($tmp_path), true);
                if (!is_array($manifest)) {
                    if ($cleanup_tmp) @wp_delete_file($tmp_path);
                    xagio_json('error', __('Invalid JSON manifest.', 'xagio-seo'));
                }

                self::xagio_process_manifest_array($manifest, null);

                if ($cleanup_tmp) @wp_delete_file($tmp_path);

                $thumbs_warning = (bool) get_transient('xagio_missing_image_editor');
                if ($thumbs_warning) {
                    delete_transient('xagio_missing_image_editor');
                }

                xagio_json('success', __('Import completed successfully (JSON).', 'xagio-seo'), [
                    'thumbnails_notice' => $thumbs_warning ? __('Media imported, but thumbnails could not be regenerated (enable GD or Imagick).', 'xagio-seo') : '',
                ]);
            }

            // === ZIP bundle (recommended) ===
            if ($ext !== 'zip') {
                if ($cleanup_tmp) @wp_delete_file($tmp_path);
                xagio_json('error', __('Unsupported file type. Use ZIP or JSON.', 'xagio-seo'));
            }
            if (!class_exists('ZipArchive')) {
                if ($cleanup_tmp) @wp_delete_file($tmp_path);
                xagio_json('error', __('ZipArchive PHP extension is required for import.', 'xagio-seo'));
            }

            $zip = new ZipArchive();
            if (true !== $zip->open($tmp_path)) {
                if ($cleanup_tmp) @wp_delete_file($tmp_path);
                xagio_json('error', __('Could not open ZIP archive.', 'xagio-seo'));
            }

            // Find manifest.json anywhere
            $manifest_json = self::xagio_get_first_entry_by_name_suffix($zip, 'manifest.json');
            if ($manifest_json === null) {
                $zip->close();
                if ($cleanup_tmp) @wp_delete_file($tmp_path);
                xagio_json('error', __('manifest.json not found in ZIP.', 'xagio-seo'));
            }
            $manifest = json_decode($manifest_json, true);
            if (!is_array($manifest)) {
                $zip->close();
                if ($cleanup_tmp) @wp_delete_file($tmp_path);
                xagio_json('error', __('Invalid manifest.json in ZIP.', 'xagio-seo'));
            }

            // Extract uploads subtree to temp dir
            $extract_dir = self::xagio_make_temp_dir();
            $uploads_prefixes = ['uploads/', './uploads/', 'content/uploads/', 'wp-content/uploads/'];

            for ( $xagio_i = 0; $xagio_i < $zip->numFiles; $xagio_i++ ) {
                $xagio_name = $zip->getNameIndex($xagio_i);
                if ( ! $xagio_name ) {
                    continue;
                }

                $norm = ltrim(str_replace('\\', '/', $xagio_name), '/');

                foreach ( $uploads_prefixes as $pre ) {
                    if ( stripos( $norm, $pre ) === 0 ) {

                        $relative = substr( $norm, strlen( $pre ) );
                        $target   = $extract_dir . $relative;

                        wp_mkdir_p( dirname( $target ) );

                        // Read entry contents without streams (avoids fclose() entirely)
                        $data = $zip->getFromName( $xagio_name );
                        if ( $data !== false ) {
                            $wp_filesystem->put_contents( $target, $data, FS_CHMOD_FILE );
                        }

                        break;
                    }
                }
            }


            $zip->close();

            // Process manifest
            self::xagio_process_manifest_array($manifest, $extract_dir);

            // Cleanup
            self::xagio_cleanup_dir($extract_dir);
            if ($cleanup_tmp) @wp_delete_file($tmp_path);

            // Surface thumbnails warning if image editor is missing
            $thumbs_warning = (bool) get_transient('xagio_missing_image_editor');
            if ($thumbs_warning) {
                delete_transient('xagio_missing_image_editor');
            }

            xagio_json('success', __('Import completed successfully (ZIP).', 'xagio-seo'), [
                'thumbnails_notice' => $thumbs_warning ? __('Media imported, but thumbnails could not be regenerated (enable GD or Imagick).', 'xagio-seo') : '',
            ]);
        }

        public static function installKadence() {
            // Ensure the current user can install plugins.
            // Ensure the current user can install plugins/themes
            if (!current_user_can('install_plugins')) {
                xagio_json('error', 'Insufficient permissions to install plugins.');
            }

            // Core includes
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/misc.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            require_once ABSPATH . 'wp-admin/includes/theme.php';
            require_once ABSPATH . 'wp-admin/includes/theme-install.php';

            // === 1) Install + Activate Kadence Blocks (free) ===
            $kadence_blocks_slug     = 'kadence-blocks';
            $kadence_blocks_mainfile = 'kadence-blocks/kadence-blocks.php';

            $already_installed = false;
            if (function_exists('get_plugins')) {
                $all_plugins = get_plugins();
                $already_installed = isset($all_plugins[$kadence_blocks_mainfile]);
            }

            // If not installed, fetch from WP.org and install
            if (!$already_installed) {
                $api = plugins_api('plugin_information', [
                    'slug'   => $kadence_blocks_slug,
                    'fields' => ['sections' => false],
                ]);

                if (is_wp_error($api)) {
                    xagio_json('error', 'Failed to retrieve Kadence Blocks plugin info.');
                }

                $skin     = new Xagio_Silent_Upgrader_Skin();
                $upgrader = new Plugin_Upgrader($skin);
                $xagio_result   = $upgrader->install($api->download_link);

                if (is_wp_error($xagio_result)) {
                    xagio_json('error', 'Kadence Blocks installation failed: ' . $xagio_result->get_error_message());
                }
            }

            // Activate if not active
            if (!is_plugin_active($kadence_blocks_mainfile)) {
                $activate = activate_plugin($kadence_blocks_mainfile);
                if (is_wp_error($activate)) {
                    xagio_json('error', 'Kadence Blocks activation failed: ' . $activate->get_error_message());
                }
            }

            // === 2) Ensure Kadence theme is installed + active ===
            self::ensureKadenceTheme();

            // === 3) Return status ===
            $status = self::checkKadenceStatus();

            xagio_json('success', 'Kadence Blocks and Kadence theme installed and activated successfully.', [
                'installed' => true,
                'version'   => $status, // e.g. "3.2.1" or false
            ]);
        }

        private static function ensureKadenceTheme()
        {
            $theme_slug = 'kadence'; // Kadence theme slug

            // If theme not installed, install from WP.org
            if (!wp_get_theme($theme_slug)->exists()) {
                require_once ABSPATH . 'wp-admin/includes/theme-install.php';
                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

                $api = themes_api('theme_information', [
                    'slug'   => $theme_slug,
                    'fields' => ['sections' => false],
                ]);

                if (is_wp_error($api)) {
                    xagio_json('error', 'Failed to retrieve Kadence theme info.');
                }

                $skin     = new Xagio_Silent_Upgrader_Skin();
                $upgrader = new Theme_Upgrader($skin);
                $xagio_result   = $upgrader->install($api->download_link);

                if (is_wp_error($xagio_result)) {
                    xagio_json('error', 'Kadence theme installation failed: ' . $xagio_result->get_error_message());
                }
            }

            // Activate if not active
            if (get_stylesheet() !== $theme_slug) {
                switch_theme($theme_slug);
            }
        }

        public static function checkKadenceStatus()
        {
            // Return the Kadence Blocks version if active, else false
            if (is_plugin_active('kadence-blocks/kadence-blocks.php')) {
                // Plugin defines KADENCE_BLOCKS_VERSION; fall back to plugin data if needed
                if (defined('KADENCE_BLOCKS_VERSION')) {
                    return KADENCE_BLOCKS_VERSION;
                }
                // Fallback: read plugin header version
                if (!function_exists('get_plugins')) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
                $all = get_plugins();
                if (isset($all['kadence-blocks/kadence-blocks.php']) && !empty($all['kadence-blocks/kadence-blocks.php']['Version'])) {
                    return $all['kadence-blocks/kadence-blocks.php']['Version'];
                }
                return true; // Active, version unknown
            }
            return false;
        }

        public static function installElementor()
        {
            // Ensure the current user can install plugins.
            if (!current_user_can('install_plugins')) {
                xagio_json('error', 'Insufficient permissions to install plugins.');
            }

            // Include necessary upgrade files.
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/misc.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            self::clearElementorCache();

            // First install Elementor Free
            if (!defined('ELEMENTOR_VERSION')) {
                $plugin_slug = 'elementor';

                $api = plugins_api('plugin_information', array(
                    'slug'   => $plugin_slug,
                    'fields' => array('sections' => false),
                ));

                if (is_wp_error($api)) {
                    xagio_json('error', 'Failed to retrieve Elementor plugin info.');
                }

                $skin     = new Xagio_Silent_Upgrader_Skin();
                $upgrader = new Plugin_Upgrader($skin);
                $xagio_result   = $upgrader->install($api->download_link);

                if (is_wp_error($xagio_result)) {
                    xagio_json('error', 'Elementor free installation failed: ' . $xagio_result->get_error_message());
                }

                $activate = activate_plugin('elementor/elementor.php');
                if (is_wp_error($activate)) {
                    xagio_json('error', 'Elementor free activation failed: ' . $activate->get_error_message());
                }
            }

            // Then install Elementor Pro if not present
            if (!defined('ELEMENTOR_PRO_VERSION')) {
                $pro_url = 'https://cdn.xagio.net/elementor-pro.zip';

                $skin     = new Xagio_Silent_Upgrader_Skin();
                $upgrader = new Plugin_Upgrader($skin);
                $xagio_result   = $upgrader->install($pro_url);

                if (is_wp_error($xagio_result)) {
                    xagio_json('error', 'Elementor Pro installation failed: ' . $xagio_result->get_error_message());
                }

                $activate = activate_plugin('elementor-pro/elementor-pro.php');

                if (is_wp_error($activate)) {
                    xagio_json('error', 'Elementor Pro activation failed: ' . $activate->get_error_message());
                }
            }

            // Ensure Hello Elementor theme is installed and activated
            self::ensureHelloElementorTheme();

            // Return success response
            $status = self::checkElementorStatus();

            xagio_json('success', 'Elementor Free and Pro installed and activated successfully.', [
                'installed' => true,
                'version'   => $status,
            ]);
        }

        private static function ensureHelloElementorTheme()
        {
            $theme_slug = 'hello-elementor';

            // Check if theme is already installed
            if (!wp_get_theme($theme_slug)->exists()) {
                include_once ABSPATH . 'wp-admin/includes/theme-install.php';
                include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

                $api = themes_api('theme_information', [
                    'slug'   => $theme_slug,
                    'fields' => ['sections' => false]
                ]);
                if (is_wp_error($api)) {
                    xagio_json('error', 'Failed to retrieve Hello Elementor theme info.');
                }

                $skin     = new Xagio_Silent_Upgrader_Skin();
                $upgrader = new Theme_Upgrader($skin);
                $xagio_result   = $upgrader->install($api->download_link);

                if (is_wp_error($xagio_result)) {
                    xagio_json('error', 'Hello Elementor theme installation failed: ' . $xagio_result->get_error_message());
                }
            }

            // Activate theme if not already active
            if (get_stylesheet() !== $theme_slug) {
                switch_theme($theme_slug);
            }
        }

        // Function to extract specified fields into a flat array.
        public static function extractFieldsFromJson($data)
        {
            $xagio_result = [];
            self::recursiveExtract($data, $xagio_result);
            return $xagio_result;
        }

        // Helper recursive function to extract values.
        public static function recursiveExtract($data, &$xagio_result)
        {
            // List of fields to extract.
            $fields = array(
                'title',
                'title_text',
                'description',
                'description_text',
                'text',
                'editor',
                'tab_title',
                'tab_content',
                'testimonial_content',
                'item_title'
            );

            if (is_array($data)) {
                foreach ($data as $xagio_key => $xagio_value) {

                    // If the current key is one of our target fields, store its value.
                    if (is_string($xagio_key) && in_array($xagio_key, $fields)) {

                        // If the value is a string and appears to contain HTML,
                        // remove all attributes from the HTML tags.
                        if (is_string($xagio_value) && strpos($xagio_value, '<') !== false) {
                            $xagio_value = self::stripHtmlAttributes($xagio_value);
                        }

                        if (!empty($xagio_value)) {
                            if (isset($data['header_size']) && $data['header_size'] === 'h1') {
                                $xagio_value = '[Heading1] ' . $xagio_value;
                            }

                            $xagio_value    = trim($xagio_value);
                            $xagio_result[] = $xagio_value;
                        }
                    }
                    // If the value itself is an array, search inside it.
                    if (is_array($xagio_value)) {
                        self::recursiveExtract($xagio_value, $xagio_result);
                    }
                }
            }
        }

        public static function stripHtmlAttributes($html)
        {
            // Decode HTML entities so &lt;/p&gt; becomes a real </p>
            $html = html_entity_decode($html);

            $doc = new DOMDocument();
            // Suppress errors due to invalid HTML fragments.
            libxml_use_internal_errors(true);

            // Load the HTML. Using mb_convert_encoding to ensure proper handling of UTF-8.
            $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            libxml_clear_errors();

            // Remove attributes from every element.
            $nodes = $doc->getElementsByTagName('*');
            foreach ($nodes as $node) {
                while ($node->attributes->length) {
                    $node->removeAttribute($node->attributes->item(0)->nodeName);
                }
            }

            // Get the content of the <body> tag to avoid including <html> and <body> wrappers.
            $body      = $doc->getElementsByTagName('body')->item(0);
            $cleanHtml = '';
            foreach ($body->childNodes as $child) {
                $cleanHtml .= $doc->saveHTML($child);
            }
            return $cleanHtml;
        }

        // Function to replace extracted field values with new ones and return updated JSON.
        public static function combineFieldsIntoJson($data, $fieldsArray)
        {
            // Start with index 0 for the fields array.
            $index = 0;
            self::recursiveReplace($data, $fieldsArray, $index);
            // Return the re-encoded JSON (pretty printed for clarity).
            return $data;
        }

        // Helper recursive function to replace field values.
        public static function recursiveReplace(&$data, $fieldsArray, &$index)
        {
            $fields = array(
                'title',
                'title_text',
                'description',
                'description_text',
                'text',
                'editor',
                'tab_title',
                'tab_content',
                'testimonial_content',
                'item_title'
            );

            if (is_array($data)) {
                foreach ($data as $xagio_key => &$xagio_value) {
                    // If key matches one of our target fields, replace its value using the current index.
                    if (is_string($xagio_key) && in_array($xagio_key, $fields)) {

                        if (empty($xagio_value))
                            continue;

                        $xagio_value = trim($xagio_value);

                        if (isset($fieldsArray[$index])) {
                            $xagio_value = $fieldsArray[$index];
                            $index++;
                        }
                    }
                    // If the value is an array, recurse.
                    if (is_array($xagio_value)) {
                        self::recursiveReplace($xagio_value, $fieldsArray, $index);
                    }
                }
                unset($xagio_value); // break the reference.
            }
        }

        public static function gutenbergExtractTexts(array $xagio_blocks, int $minLen = 3): array
        {
            $out = [];

            $collectFromHtml = function (string $html) use (&$out, $minLen) {
                $dom = new DOMDocument('1.0', 'UTF-8');
                libxml_use_internal_errors(true);
                $loaded = $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
                libxml_clear_errors();
                if (!$loaded) return;

                $xp = new DOMXPath($dom);

                // Prefer block-ish nodes first
                $block = 'self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6 or self::p or self::li or self::figcaption or self::label';
                // Inline-ish nodes used only when they’re not inside a block
                $inline = 'self::a or self::button or self::span or self::strong or self::em';

                $found = 0;

                // Pass 1: top-most block nodes
                $q1 = '//*['.$block.'][not(ancestor::*['.$block.'])]';
                foreach ($xp->query($q1) as $el) {
                    $txt = trim($el->textContent ?? '');
                    if ($txt !== '' && mb_strlen($txt) >= $minLen) {
                        if (strtolower($el->nodeName) === 'h1') {
                            $txt = '[Heading1] ' . $txt;
                        }

                        $out[] = $txt;
                        $found++;
                    }
                }

                // Pass 2: inline nodes that are not inside a block (standalone badges, buttons, etc.)
                if ($found === 0) {
                    $q2 = '//*['.$inline.'][not(ancestor::*['.$block.'])][not(ancestor::*['.$inline.'])]';
                    foreach ($xp->query($q2) as $el) {
                        $txt = trim($el->textContent ?? '');
                        if ($txt !== '' && mb_strlen($txt) >= $minLen) {
                            $out[] = $txt;
                            $found++;
                        }
                    }
                }

                // Pass 3: per-fragment fallback (raw/plain text)
                if ($found === 0) {
                    $txt = trim($dom->textContent ?? '');
                    if ($txt !== '' && mb_strlen($txt) >= $minLen) {
                        $out[] = $txt;
                    }
                }
            };

            // Keys in attrs that commonly hold human-visible text across core/Kadence blocks
            $ATTR_TEXT_KEYS = [
                'content','name','occupation','title','subtitle','text','description',
                'label','alt','caption','btnText','buttonText','linkText'
            ];

            $isProbablyNonText = function (string $v): bool {
                // Skip obvious non-text values (URLs, hex colors, short IDs)
                if (preg_match('~^https?://~i', $v)) return true;
                if (preg_match('~^#?[0-9a-f]{3,8}$~i', $v)) return true;
                if (mb_strlen($v) < 3) return true;
                return false;
            };

            $walk = function ($node) use (&$walk, $collectFromHtml, $ATTR_TEXT_KEYS, $isProbablyNonText) {
                if (!is_array($node)) return;

                // 1) HTML content on the block node
                if (!empty($node['innerHTML']) && is_string($node['innerHTML'])) {
                    $collectFromHtml($node['innerHTML']);
                }

                // 2) Raw 'content' field sometimes present at the block root
                if (!empty($node['content']) && is_string($node['content']) && !$isProbablyNonText($node['content'])) {
                    $collectFromHtml($node['content']);
                }

                // 3) Text-bearing attributes (e.g., kadence/testimonial stores quote, name in attrs)
                if (!empty($node['attrs']) && is_array($node['attrs'])) {
                    foreach ($ATTR_TEXT_KEYS as $xagio_k) {
                        if (!empty($node['attrs'][$xagio_k]) && is_string($node['attrs'][$xagio_k]) && !$isProbablyNonText($node['attrs'][$xagio_k])) {
                            $collectFromHtml($node['attrs'][$xagio_k]);
                        }
                    }
                }

                // 4) Recurse
                if (!empty($node['innerBlocks']) && is_array($node['innerBlocks'])) {
                    foreach ($node['innerBlocks'] as $child) {
                        $walk($child);
                    }
                }
            };

            foreach ($xagio_blocks as $b) {
                $walk($b);
            }

            return $out;
        }


        public static function gutenbergApplyTexts(array $xagio_blocks, array $xagio_texts, int $minLen = 3): array
        {
            $idx = 0;

            // Must match the extractor’s whitelist & order
            $ATTR_TEXT_KEYS = [
                'content','name','occupation','title','subtitle','text','description',
                'label','alt','caption','btnText','buttonText','linkText'
            ];

            $replaceFirst = function (string $haystack, string $search, string $replace) {
                if ($search === '') return $haystack;
                $pos = mb_stripos($haystack, $search);
                if ($pos === false) return $haystack;
                return mb_substr($haystack, 0, $pos) . $replace . mb_substr($haystack, $pos + mb_strlen($search));
            };

            $decodeEntities = function(string $s): string {
                return html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            };

            $applyToHtml = function (string $html, array &$consumedOldNew) use (&$idx, $xagio_texts, $minLen, $decodeEntities) {
                $dom = new DOMDocument('1.0', 'UTF-8');
                libxml_use_internal_errors(true);
                $loaded = $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
                libxml_clear_errors();
                if (!$loaded) return $html;

                $xp = new DOMXPath($dom);

                $block = 'self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6 or self::p or self::li or self::figcaption or self::label';
	            $inline = 'self::a or self::button or self::span or self::strong or self::em or (self::div[contains(@class, "kb-count-up-title")])';

                // Same selection strategy as the extractor
                $nodes = [];

                // Pass 1: top-most blocks
                foreach ($xp->query('//*[' . $block . '][not(ancestor::*[' . $block . '])]') as $el) {
                    $nodes[] = $el;
                }

                // Pass 2: top-most inlines ONLY if no blocks matched
                if (!$nodes) {
                    foreach ($xp->query('//*[' . $inline . '][not(ancestor::*[' . $block . '])][not(ancestor::*[' . $inline . '])]') as $el) {
                        $nodes[] = $el;
                    }
                }

                foreach ($nodes as $el) {
                    $old = trim($el->textContent ?? '');
                    if ($old === '' || mb_strlen($old) < $minLen) continue;
                    if (!array_key_exists($idx, $xagio_texts)) break; // no more replacements

                    $new = $decodeEntities((string)$xagio_texts[$idx++]);
                    if ($old === $new) continue;

                    // Replace the whole content of the element with the new plain text
                    // (this drops inner <strong> etc.; see note below)
                    while ($el->firstChild) {
                        $el->removeChild($el->firstChild);
                    }
                    $el->appendChild($dom->createTextNode($new));
                    $consumedOldNew[] = [$old, $new];
                }

                $body = $dom->getElementsByTagName('body')->item(0);
                if (!$body) return $html;
                $newHtml = '';
                foreach ($body->childNodes as $child) {
                    $newHtml .= $dom->saveHTML($child);
                }
                return $newHtml;
            };

            $walk = function (&$node) use (&$walk, $applyToHtml, $replaceFirst, &$idx, $xagio_texts, $minLen, $ATTR_TEXT_KEYS, $decodeEntities) {
                if (!is_array($node)) return;

                // --- 1) innerHTML (same as extractor order)
                if (!empty($node['innerHTML']) && is_string($node['innerHTML'])) {
                    $oldNewPairs = [];
                    $oldHTML = $node['innerHTML'];
                    $node['innerHTML'] = $applyToHtml($oldHTML, $oldNewPairs);

                    // Best-effort sync of innerContent fragments
                    if (!empty($oldNewPairs) && !empty($node['innerContent']) && is_array($node['innerContent'])) {
                        foreach ($node['innerContent'] as $xagio_k => $frag) {
                            if (!is_string($frag) || $frag === '') continue;
                            foreach ($oldNewPairs as [$old, $new]) {
                                if ($old === $new || $old === '') continue;
                                $node['innerContent'][$xagio_k] = $replaceFirst($node['innerContent'][$xagio_k], $old, $new);
                            }
                        }
                    }

                    // For leaf blocks like core/list-item and Kadence headings:
                    // if there are no innerBlocks and innerContent has exactly one
                    // non-null fragment, force it to match innerHTML so Gutenberg
                    // actually serializes our changes.
                    if (
                        isset($node['innerContent']) && is_array($node['innerContent']) &&
                        (empty($node['innerBlocks']) || !is_array($node['innerBlocks']) || count($node['innerBlocks']) === 0)
                    ) {
                        $nonNull = array_filter(
                            $node['innerContent'],
                            static function ($v) { return $v !== null; }
                        );
                        if (count($nonNull) === 1) {
                            $node['innerContent'] = [ $node['innerHTML'] ];
                        }
                    }
                }

                // --- 2) root 'content' (the extractor collects this next)
                if (!empty($node['content']) && is_string($node['content'])) {
                    $old = trim($node['content']);
                    if ($old !== '' && mb_strlen($old) >= $minLen && array_key_exists($idx, $xagio_texts)) {
                        $new = $decodeEntities((string)$xagio_texts[$idx++]);
                        if ($old !== $new) {
                            // If it looks like HTML, you can run it through the HTML applier;
                            // else, replace wholesale.
                            if (strpos($old, '<') !== false && strpos($old, '>') !== false) {
                                $dummy = [];
                                $node['content'] = $applyToHtml($old, $dummy);
                            } else {
                                $node['content'] = $new;
                            }
                        }
                    }
                }

                // --- 3) attrs text-bearing keys (Kadence/core quote/testimonial etc.)
                if (!empty($node['attrs']) && is_array($node['attrs'])) {
                    foreach ($ATTR_TEXT_KEYS as $xagio_k) {
                        if (!empty($node['attrs'][$xagio_k]) && is_string($node['attrs'][$xagio_k])) {
                            $old = trim($node['attrs'][$xagio_k]);
                            if ($old === '' || mb_strlen($old) < $minLen) continue;
                            if (!array_key_exists($idx, $xagio_texts)) break;

                            $new = $decodeEntities((string)$xagio_texts[$idx++]);
                            if ($old === $new) continue;

                            // If attr text contains simple HTML, you could DOM-apply it,
                            // but most Kadence/core attrs are plain text—replace directly.
                            $node['attrs'][$xagio_k] = $new;
                        }
                    }
                }

                // --- 4) recurse
                if (!empty($node['innerBlocks']) && is_array($node['innerBlocks'])) {
                    foreach ($node['innerBlocks'] as &$child) $walk($child);
                    unset($child);
                }
            };

            foreach ($xagio_blocks as &$b) $walk($b);
            unset($b);

            return $xagio_blocks;
        }

        public static function clearElementorCache()
        {
            $wp_upload_dir = wp_upload_dir(null, false);
            $path          = $wp_upload_dir['basedir'] . '/elementor/css/*';

            foreach (glob($path) as $file_path) {
                wp_delete_file($file_path);
            }

            delete_post_meta_by_key('_elementor_css');
            delete_post_meta_by_key('_elementor_element_cache');
            delete_post_meta_by_key('_elementor_page_assets');

            delete_option('elementor-custom-breakpoints-files');

            delete_option('_elementor_assets_data');
        }

        public static function getTemplatePage($templateName = 'Service')
        {
            $statuses = [
                'publish',
                'draft'
            ]; // Include both statuses

            // First try with the original name
            $query = new WP_Query([
                'post_type'      => 'page',
                'title'          => $templateName,
                'posts_per_page' => 1,
                'post_status'    => $statuses,
            ]);

            if (!$query->have_posts()) {
                // If not found, try with the prefixed version
                wp_reset_postdata();
                $query = new WP_Query([
                    'post_type'      => 'page',
                    'title'          => 'Agent X - ' . $templateName,
                    'posts_per_page' => 1,
                    'post_status'    => $statuses,
                ]);
            }

            if ($query->have_posts()) {
                $query->the_post();
                $template_page = get_post(get_the_ID());
                wp_reset_postdata();

                if ($template_page) {
                    return $template_page;
                }
            }

            return false;
        }

        public static function injectContactUsAddress()
        {
            $contact_us = XAGIO_MODEL_OCW::getTemplatePage("Contact");

            if (!isset($contact_us->ID)) {
                $contact_us = XAGIO_MODEL_OCW::getTemplatePage("Contact Us");
            }

            if (isset($contact_us->ID)) {
                $location = self::xagio_get_location_string();
                if ($location === '') return false;

                $xagio_elementor_data = get_post_meta($contact_us->ID, '_elementor_data', TRUE);
                if (!is_array($xagio_elementor_data)) {
                    $xagio_elementor_data = json_decode($xagio_elementor_data, true);
                }

                $data           = XAGIO_MODEL_AI::elementorReplaceMapAddress($xagio_elementor_data, $location);
                $xagio_elementor_data = json_encode($data);

                update_post_meta($contact_us->ID, '_elementor_data', wp_slash($xagio_elementor_data));

                XAGIO_MODEL_OCW::clearElementorCache();
            }
        }

        public static function convertToDrafts($titles = ['Service'])
        {
            foreach ($titles as $title) {
                $query = new WP_Query([
                    'post_type'      => 'page',
                    'title'          => $title,
                    'post_status'    => [
                        'publish',
                        'private',
                        'pending'
                    ],
                    // exclude 'draft' to avoid re-processing
                    'posts_per_page' => 1,
                ]);

                if ($query->have_posts()) {
                    $query->the_post();
                    $xagio_page_id = get_the_ID();

                    // Convert to draft
                    wp_update_post([
                        'ID'          => $xagio_page_id,
                        'post_status' => 'draft',
                        'post_title'  => 'Agent X - ' . $title
                    ]);

                    wp_reset_postdata();
                }
            }
        }

        /** ====================== INTERNAL IMPORT HELPERS ====================== */

        private static function xagio_fix_agentx_menu_labels_and_urls() {

            // Pick menu: main-menu -> primary -> first available
            $menu = wp_get_nav_menu_object('main-menu');
            if (!$menu) $menu = wp_get_nav_menu_object('primary');
            if (!$menu) {
                $menus = wp_get_nav_menus();
                if (!empty($menus)) $menu = reset($menus);
            }

            $menu_id = $menu ? (int) $menu->term_id : 0;
            if (!$menu_id) return;

            $menu_items = wp_get_nav_menu_items($menu_id);
            if (empty($menu_items)) return;

            foreach ($menu_items as $xagio_item) {

                // Remove "Agent X - " or "Agent X – " prefix (dash or en-dash)
                $clean_title = preg_replace('/^\s*Agent\s*X[^\p{L}]*/iu', '', (string) $xagio_item->title);
                $normalized  = strtolower(trim($clean_title));

                // Update Home menu item
                if ($normalized === 'home') {
                    wp_update_nav_menu_item($menu_id, $xagio_item->ID, [
                        'menu-item-title'     => 'Home',
                        'menu-item-type'      => 'custom',
                        'menu-item-url'       => '/',
                        'menu-item-status'    => 'publish',
                        'menu-item-parent-id' => (int) $xagio_item->menu_item_parent,
                        'menu-item-position'  => (int) $xagio_item->menu_order,
                    ]);
                    continue;
                }

                // Update Service / Services menu item
                if ($normalized === 'service' || $normalized === 'services') {
                    wp_update_nav_menu_item($menu_id, $xagio_item->ID, [
                        'menu-item-title'     => ($normalized === 'services') ? 'Services' : 'Service',
                        'menu-item-type'      => 'custom',
                        'menu-item-url'       => '#',
                        'menu-item-status'    => 'publish',
                        'menu-item-parent-id' => (int) $xagio_item->menu_item_parent,
                        'menu-item-position'  => (int) $xagio_item->menu_order,
                    ]);
                }
            }
        }

        private static function xagio_fix_agentx_gutenberg_menu() {
            global $wpdb;

            // Find latest published kadence_navigation with exact post_title
            $post_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID
             FROM {$wpdb->posts}
             WHERE post_type = %s
               AND post_status = 'publish'
               AND post_title = %s
             ORDER BY post_date_gmt DESC
             LIMIT 1",
                    'kadence_navigation',
                    'Header Menu'
                )
            );

            if (!$post_id) {
                $post_id = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT ID
             FROM {$wpdb->posts}
             WHERE post_type = %s
               AND post_status = 'publish'
               AND post_title = %s
             ORDER BY post_date_gmt DESC
             LIMIT 1",
                        'kadence_navigation',
                        'nav_menu'
                    )
                );
            }

            if (!$post_id) return false;

            $header = get_post($post_id);
            if (!$header || $header->post_type !== 'kadence_navigation' || $header->post_status !== 'publish') return false;

            if (!function_exists('parse_blocks') || !function_exists('serialize_blocks')) return false;

            $blocks = parse_blocks($header->post_content);
            if (!is_array($blocks) || empty($blocks)) return false;

            $modified = false;

            $decode_kadence_label = function(string $s): string {
                $s = (string) $s;
                $s = str_replace(['\\u0026#038;', 'u0026#038;'], '&#038;', $s);
                $s = str_replace(['\\u0026', 'u0026'], '&', $s);
                $s = preg_replace_callback('/\\\\?u([0-9a-fA-F]{4})/', function($m) {
                    $code = hexdec($m[1]);
                    if ($code < 0x80) return chr($code);
                    if ($code < 0x800) return chr(0xC0 | ($code >> 6)) . chr(0x80 | ($code & 0x3F));
                    return chr(0xE0 | ($code >> 12)) . chr(0x80 | (($code >> 6) & 0x3F)) . chr(0x80 | ($code & 0x3F));
                }, $s);

                $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $s = trim(preg_replace('/\s+/', ' ', $s));

                return $s;
            };



            $clean_label = function($label) use ($decode_kadence_label) {
                $label = (string) $label;
                $label = preg_replace('/^\s*Agent\s*X[^\p{L}]*/iu', '', $label);
                $label = $decode_kadence_label($label);
                return trim($label);
            };

            $normalize_url = function($url) {
                $url = trim((string) $url);
                if ($url === '/home' || $url === '/home/') return '/';
                return $url;
            };

            $to_relative = function($url) use ($normalize_url) {
                $url = trim((string) $url);
                if ($url === '') return '';

                // Keep anchors as-is
                if ($url === '#' || str_starts_with($url, '#')) return $url;

                if (function_exists('wp_make_link_relative')) {
                    $url = wp_make_link_relative($url);
                } else {
                    // Fallback: strip scheme/host if present
                    $parts = wp_parse_url($url);
                    if (is_array($parts) && !empty($parts['path'])) {
                        $url = $parts['path'];
                        if (!empty($parts['query'])) $url .= '?' . $parts['query'];
                        if (!empty($parts['fragment'])) $url .= '#' . $parts['fragment'];
                    }
                }

                return $normalize_url($url);
            };

            $force_custom = function(array &$attrs, $new_label, $new_url) {
                $attrs['label'] = $new_label;
                $attrs['url']   = $new_url;
                $attrs['kind']  = 'custom';

                foreach (['id','ID','postId','postID','objectId','objectID','type','subtype','taxonomy','termId','termID','slug','link','href','uuid'] as $k) {
                    if (array_key_exists($k, $attrs)) unset($attrs[$k]);
                }
            };

            $get_target_post_id = function(array $attrs): int {
                foreach (['id','ID','postId','postID','objectId','objectID'] as $k) {
                    if (!empty($attrs[$k]) && is_numeric($attrs[$k])) {
                        return (int) $attrs[$k];
                    }
                }
                return 0;
            };

            $resolve_by_label = function(string $label) use ($wpdb, $decode_kadence_label) {
                $label = $decode_kadence_label($label);
                if ($label === '') return '';
                $slug = sanitize_title($label);

                foreach (["services/{$slug}", "service/{$slug}", $slug] as $path) {
                    $p = get_page_by_path($path, 'OBJECT', ['page', 'post']);
                    if ($p instanceof WP_Post) {
                        $u = get_permalink($p);
                        if ($u) return $u;
                    }
                }

                // Exact title match (no deprecated get_page_by_title)
                $id = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT ID
             FROM {$wpdb->posts}
             WHERE post_title = %s
               AND post_status = 'publish'
               AND post_type IN ('page','post')
             ORDER BY post_date_gmt DESC
             LIMIT 1",
                        $label
                    )
                );

                return ($id > 0) ? (string) get_permalink($id) : '';
            };

            $walk = function (&$b, $in_service = false) use (
                &$walk,
                &$modified,
                $clean_label,
                $normalize_url,
                $to_relative,
                $force_custom,
                $get_target_post_id,
                $resolve_by_label
            ) {
                if (!is_array($b)) return;

                if (($b['blockName'] ?? '') === 'kadence/navigation-link') {
                    $label_raw = $b['attrs']['label'] ?? '';
                    $url_raw   = $b['attrs']['url'] ?? '';

                    $label = $clean_label($label_raw);
                    $norm  = strtolower($label);

                    // Remove Agent X prefix if present
                    if ($label !== (string)$label_raw) {
                        $old = (string)$label_raw;
                        $new = (string)$label;

                        $b['attrs']['label'] = $new;
                        $modified = true;

                        if (isset($b['innerHTML']) && is_string($b['innerHTML'])) {
                            $b['innerHTML'] = str_replace($old, $new, $b['innerHTML']);
                        }
                        if (isset($b['innerContent']) && is_array($b['innerContent'])) {
                            foreach ($b['innerContent'] as &$ic) {
                                if (is_string($ic)) {
                                    $ic = str_replace($old, $new, $ic);
                                }
                            }
                            unset($ic);
                        }
                    }

                    // Home: force custom "/" and label "Home"
                    if ($norm === 'home') {
                        $force_custom($b['attrs'], 'Home', '/');
                        $modified = true;
                    }

                    // Service parent: force custom "#", and mark subtree
                    if ($norm === 'service' || $norm === 'services') {
                        $desired = ($norm === 'services') ? 'Services' : 'Service';
                        $force_custom($b['attrs'], $desired, '#');
                        $modified = true;
                        $in_service = true;
                    } else {
                        if ($in_service) {
                            $cur_label = (string) ($b['attrs']['label'] ?? $label_raw);

                            $existing_url_raw  = (string) ($b['attrs']['url'] ?? '');
                            $existing_url_norm = $normalize_url($existing_url_raw);

                            $resolved_url = $existing_url_norm;
                            if ($resolved_url === '' || $resolved_url === '#') {
                                $target_id = $get_target_post_id($b['attrs']);
                                if ($target_id > 0) {
                                    $u = get_permalink($target_id);
                                    if ($u) $resolved_url = $to_relative($u);
                                }
                            }

                            if ($resolved_url === '' || $resolved_url === '#') {
                                $u = $resolve_by_label($cur_label);
                                if ($u) $resolved_url = $to_relative($u);
                            }

                            if ($resolved_url !== '' && $resolved_url !== '#') {
                                if ($existing_url_raw !== $resolved_url) {
                                    $b['attrs']['url'] = $resolved_url;
                                    $modified = true;
                                }
                            }

                        } else {
                            // Normalize /home/ => /
                            $url_norm = $normalize_url($url_raw);
                            if ($url_norm !== (string) $url_raw) {
                                $b['attrs']['url'] = $url_norm;
                                $modified = true;
                            }
                        }
                    }
                }

                if (!empty($b['innerBlocks']) && is_array($b['innerBlocks'])) {
                    foreach ($b['innerBlocks'] as &$child) {
                        $walk($child, $in_service);
                    }
                    unset($child);
                }
            };

            foreach ($blocks as &$root) {
                $walk($root, false);
            }
            unset($root);

            if (!$modified) return false;

            $new_content = serialize_blocks($blocks);

            $upd = wp_update_post([
                'ID'           => $post_id,
                'post_content' => $new_content,
            ], true);

            if (is_wp_error($upd)) return false;

            // Optional: help invalidate caches
            clean_post_cache($post_id);
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }

            return true;
        }

        public static function xagio_update_footer_social_links_from_profiles() {

            // 1) Build normalized social URL map
            $profiles = get_option('XAGIO_SEO_PROFILES', []);
            $social   = is_array($profiles) ? ($profiles['social_media'] ?? []) : [];

            if (!is_array($social) || empty($social)) {
                return false;
            }

            $urls = [];
            foreach ($social as $k => $v) {
                $v = trim((string) $v);
                if ($v === '') continue;

                if (!preg_match('~^https?://~i', $v)) {
                    $v = 'https://' . ltrim($v, '/');
                }

                $v = esc_url_raw($v);
                if ($v) $urls[$k] = $v;
            }

            if (empty($urls)) {
                return false;
            }

            // 2) Map Kadence icon names -> social key
            $icon_to_social = [
                'fa_facebook-n'  => 'facebook',
                'fa_facebook'    => 'facebook',
                'fa_facebook-f'  => 'facebook',

                'fa_twitter'     => 'x',
                'ic_twitterX'    => 'x',
                'fa_x-twitter'   => 'x',

                'fa_youtube'     => 'youtube',

                'fa_instagram'   => 'instagram',
                'fe_instagram'   => 'instagram',

                'fa_linkedin'    => 'linkedin',
                'fa_linkedin-in' => 'linkedin',

                'fa_tiktok'      => 'tiktok',

                'fa_pinterest'   => 'pinterest',
                'fa_pinterest-p' => 'pinterest',
            ];

            // 3) Transformer:
            //    - Ensures JSON "link" exists (if icon maps + URL exists)
            //    - Wraps inner <span> with <a href="..."> if not already wrapped
            $transform = function(string $content) use ($icon_to_social, $urls) : string {

                $pattern = '/(<!--\s*wp:kadence\/single-icon\s+(\{.*?\})\s*-->)(\s*<div\b[^>]*class="[^"]*\bwp-block-kadence-single-icon\b[^"]*"[^>]*>)(.*?)(<\/div>)/is';

                return (string) preg_replace_callback($pattern, function($mm) use ($icon_to_social, $urls) {

                    $comment = $mm[1];
                    $json    = $mm[2];
                    $open    = $mm[3];
                    $inner   = $mm[4];
                    $close   = $mm[5];

                    $data = json_decode($json, true);
                    if (!is_array($data)) {
                        return $mm[0];
                    }

                    $icon = $data['icon'] ?? '';
                    if ($icon === '' || empty($icon_to_social[$icon])) {
                        return $mm[0];
                    }

                    $social_key = $icon_to_social[$icon];
                    $url = $urls[$social_key] ?? '';
                    if ($url === '') {
                        return $mm[0];
                    }

                    $url = esc_url($url);
                    if (!$url) {
                        return $mm[0];
                    }

                    // A) Ensure JSON has link
                    if (($data['link'] ?? '') !== $url) {
                        $data['link'] = $url;
                        $new_json = wp_json_encode($data, JSON_UNESCAPED_SLASHES);
                        if ($new_json && $new_json !== $json) {
                            $comment = str_replace($json, $new_json, $comment);
                        }
                    }

                    // B) Ensure HTML has anchor wrapper
                    if (stripos($inner, '<a ') === false) {

                        if (preg_match('/(<span\b[^>]*\bkb-svg-icon-wrap\b[^>]*>.*?<\/span>)/is', $inner, $sm)) {
                            $span = $sm[1];
                            $wrapped = '<a class="xagio-template-social-link" href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $span . '</a>';
                            $inner = str_replace($span, $wrapped, $inner);
                        } else if (preg_match('/(<span\b[^>]*>.*?<\/span>)/is', $inner, $sm)) {
                            $span = $sm[1];
                            $wrapped = '<a class="xagio-template-social-link" href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $span . '</a>';
                            $inner = str_replace($span, $wrapped, $inner);
                        } else {
                            $inner = '<a class="xagio-template-social-link" href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $inner . '</a>';
                        }
                    }

                    return $comment . $open . $inner . $close;

                }, $content);
            };

            $changed_anything = false;

            // =========================
            // PART A) FOOTER (ALL footer* sidebars)
            // =========================
            $sidebars = get_option('sidebars_widgets', []);
            if (is_array($sidebars)) {

                $widget_block = get_option('widget_block', []);
                if (is_array($widget_block)) {

                    $footer_changed = false;

                    foreach ($sidebars as $sidebar_id => $widgets) {

                        if (!is_string($sidebar_id)) continue;
                        if (strpos($sidebar_id, 'footer') !== 0) continue;
                        if (!is_array($widgets) || empty($widgets)) continue;

                        foreach ($widgets as $wid) {
                            if (!preg_match('/^block-(\d+)$/', (string)$wid, $m)) continue;
                            $n = (int)$m[1];

                            $content = $widget_block[$n]['content'] ?? '';
                            if ($content === '') continue;

                            $new = $transform((string)$content);
                            if ($new !== $content) {
                                $widget_block[$n]['content'] = $new;
                                $footer_changed = true;
                                $changed_anything = true;
                            }
                        }
                    }

                    if ($footer_changed) {
                        update_option('widget_block', $widget_block);
                    }
                }
            }

            // =========================
            // PART B) HEADER (kadence_header posts)
            // =========================
            $header_ids = get_posts([
                'post_type'     => 'kadence_header',
                'post_status'   => ['publish', 'draft', 'private'],
                'numberposts'   => -1,
                'fields'        => 'ids',
                'no_found_rows' => true,
            ]);

            foreach ((array)$header_ids as $post_id) {
                $post = get_post($post_id);
                if (!$post) continue;

                $content = (string) $post->post_content;
                if ($content === '') continue;

                $new = $transform($content);
                if ($new !== $content) {
                    wp_update_post([
                        'ID'           => $post_id,
                        'post_content' => $new,
                    ]);
                    $changed_anything = true;
                }
            }

            return $changed_anything;
        }

        public static function xagio_get_location_string() {

            $location = trim((string) get_option('XAGIO_ONBOARDING_LOCATION', ''));

            if ($location === '') {
                $seo_profiles = get_option('XAGIO_SEO_PROFILES', []);

                if (is_array($seo_profiles)) {
                    $contact_details = $seo_profiles['contact_details'] ?? [];
                    if (is_array($contact_details)) {
                        $parts = [];

                        $city    = trim((string) ($contact_details['business_city'] ?? ''));
                        $state   = trim((string) ($contact_details['business_state'] ?? ''));
                        $country = trim((string) ($contact_details['business_country'] ?? ''));

                        if ($city !== '')    $parts[] = $city;
                        if ($state !== '')   $parts[] = $state;
                        if ($country !== '') $parts[] = $country;

                        if ($parts) {
                            $location = implode(', ', $parts);
                        }
                    }
                }
            }

            return $location;
        }

        public static function xagio_fix_kadence_footer_map_location()
        {
            $location = self::xagio_get_location_string();
            if ($location === '') return false;

            $sidebars = get_option('sidebars_widgets', []);
            if (!is_array($sidebars) || !$sidebars) return false;

            $widget_block = get_option('widget_block', []);
            if (!is_array($widget_block)) return false;

            // 1) pick first footer* with >= 2 widgets
            $target_sidebar = null;
            $target_widgets = [];

            foreach ($sidebars as $sidebar_id => $widgets) {
                if (!is_string($sidebar_id)) continue;
                if (strpos($sidebar_id, 'footer') !== 0) continue;

                if (!is_array($widgets)) continue;
                if (count($widgets) < 2) continue;

                $target_sidebar = $sidebar_id;
                $target_widgets = $widgets;
                break;
            }

            if (!$target_sidebar) return false;

            // 2) update location in kadence/googlemaps blocks inside those widgets
            $changed = false;

            foreach ($target_widgets as $wid) {
                if (!is_string($wid)) continue;
                if (!preg_match('/^block-(\d+)$/', $wid, $m)) continue;

                $n = (int) $m[1];
                if (empty($widget_block[$n]['content'])) continue;

                $content = $widget_block[$n]['content'];

                $new_content = preg_replace(
                    '/(<!--\s*wp:kadence\/googlemaps\s+\{[^}]*"location"\s*:\s*")[^"]*(")/i',
                    '$1' . addslashes($location) . '$2',
                    $content,
                    -1,
                    $count
                );

                if ($count > 0 && $new_content !== $content) {
                    $widget_block[$n]['content'] = $new_content;
                    $changed = true;
                }
            }

            if ($changed) {
                update_option('widget_block', $widget_block);
                return true;
            }

            return false;
        }


        public static function xagio_fix_kadence_map_location() {
            global $wpdb;

            $location = self::xagio_get_location_string();
            if ($location === '') return false;

            // 2. Find wp_block post
            $post_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID
                     FROM {$wpdb->posts}
                     WHERE post_type = %s
                       AND post_status = 'publish'
                       AND post_title = %s
                     LIMIT 1",
                    'wp_block',
                    'map'
                )
            );

            if (!$post_id) {
                // Try post_name as fallback
                $post_id = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT ID
                         FROM {$wpdb->posts}
                         WHERE post_type = %s
                           AND post_status = 'publish'
                           AND post_name = %s
                         LIMIT 1",
                        'wp_block',
                        'map'
                    )
                );
            }

            if (!$post_id) {
                return false;
            }

            // 3. Get and verify the post
            $post = get_post($post_id);
            if (!$post || $post->post_type !== 'wp_block' || $post->post_status !== 'publish') {
                return false;
            }

            if (!function_exists('parse_blocks') || !function_exists('serialize_blocks')) {
                return false;
            }

            // 4. Parse blocks
            $blocks = parse_blocks($post->post_content);
            if (!is_array($blocks) || empty($blocks)) {
                return false;
            }

            $modified = false;

            // 5. Walk through blocks to find and update kadence/googlemaps
            $walk = function (&$b) use (&$walk, &$modified, $location) {
                if (!is_array($b)) {
                    return;
                }

                // Target kadence/googlemaps blocks
                if (($b['blockName'] ?? '') === 'kadence/googlemaps') {
                    $attrs = &$b['attrs'];

                    if (isset($attrs['location'])) {
                        // Update existing location
                        if ($attrs['location'] !== $location) {
                            $attrs['location'] = $location;
                            $modified = true;
                        }
                    } else {
                        // Add location attribute
                        $attrs['location'] = $location;
                        $modified = true;
                    }
                }

                // Recursively walk inner blocks
                if (!empty($b['innerBlocks']) && is_array($b['innerBlocks'])) {
                    foreach ($b['innerBlocks'] as &$child) {
                        $walk($child);
                    }
                    unset($child);
                }
            };

            foreach ($blocks as &$root) {
                $walk($root);
            }
            unset($root);

            // 6. Serialize and update if modified
            if (!$modified) {
                return false;
            }

            $new_content = serialize_blocks($blocks);

            $upd = wp_update_post([
                'ID'           => $post_id,
                'post_content' => $new_content,
            ], true);

            if (is_wp_error($upd)) {
                return false;
            }

            // Invalidate caches
            clean_post_cache($post_id);
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }

            return true;
        }




        private static function xagio_process_manifest_array(array $manifest, ?string $extracted_uploads_dir) {
            $posts       = is_array($manifest['posts'] ?? null) ? $manifest['posts'] : [];
            $attachments = is_array($manifest['attachments'] ?? null) ? $manifest['attachments'] : [];
            $menus       = is_array($manifest['menus']['terms'] ?? null) ? $manifest['menus']['terms'] : [];
            $locations   = is_array($manifest['menus']['locations'] ?? null) ? $manifest['menus']['locations'] : [];
            $options     = is_array($manifest['options'] ?? null) ? $manifest['options'] : [];
            $theme_mods  = is_array($manifest['theme_mods'] ?? null) ? $manifest['theme_mods'] : [];

            // --- Normalize absolute uploads URLs to relative (keep GUIDs & attached_file intact) ---
            self::xagio_relativize_upload_urls_deep($posts);
            self::xagio_relativize_upload_urls_deep($attachments); // skips 'attached_file' & 'guid'
            self::xagio_relativize_upload_urls_deep($options);
            self::xagio_relativize_upload_urls_deep($theme_mods);

            // 1) Menus (preserve term_id)
            foreach ($menus as $menu) {
                self::xagio_import_menu_term_preserving_id($menu);
            }

            // 2) Posts in two passes (roots first)
            $allowed_post_types = ['page','post','kadence_header','kadence_navigation','nav_menu_item','wp_block'];
            $roots = []; $kids = [];
            foreach ($posts as $xagio_item) {
                if (!in_array(($xagio_item['post_type'] ?? ''), $allowed_post_types, true)) continue;
                if (empty($xagio_item['post_parent'])) $roots[] = $xagio_item; else $kids[] = $xagio_item;
            }
            foreach ([$roots, $kids] as $batch) {
                foreach ($batch as $xagio_item) {
                    self::xagio_import_single_post_preserving_id($xagio_item);
                }
            }

            // 3) Attachments (after posts exist)
            $uploads = wp_get_upload_dir();
            $baseDir = trailingslashit($uploads['basedir']);
            $baseUrl = trailingslashit($uploads['baseurl']);

            foreach ($attachments as $att) {
                self::xagio_import_single_attachment_preserving_id(
                    $att,
                    $extracted_uploads_dir ? rtrim($extracted_uploads_dir, '/\\') . '/' : null,
                    $baseDir,
                    $baseUrl
                );
            }

            // 4) Re-attach menu items and restore locations
            foreach ($menus as $menu) {
                $term_id = (int)($menu['term_id'] ?? 0);
                if ($term_id && !empty($menu['items'])) {
                    foreach ((array)$menu['items'] as $item_id) {
                        if (get_post((int)$item_id)) {
                            wp_set_object_terms((int)$item_id, $term_id, 'nav_menu', true);
                        }
                    }
                }
            }
            if (!empty($locations)) {
                set_theme_mod('nav_menu_locations', $locations);
            }

            // 5) Options (Kadence + front page + widgets)
            $option_keys = [
                // Kadence / Kadence Blocks
                'kadenceblocks_data_settings',
                'theme_mods_kadence',
                'kadence_blocks_colors',
                'kadence_blocks_config_blocks',
                'kadence_global_palette',
                'kadence_starter_plugin_notice',
                'kadence_blocks_schema_version',

                // Front page settings
                'show_on_front',
                'page_on_front',

                // Widgets (block widgets + sidebar mapping)
                'widget_block',
                'sidebars_widgets',
            ];

            if (!empty($options)) {
                // sidebars first
                if (array_key_exists('sidebars_widgets', $options) && is_array($options['sidebars_widgets'])) {
                    self::xagio_safe_set_sidebars_widgets($options['sidebars_widgets']);
                }
                // widget instances
                if (array_key_exists('widget_block', $options)) {
                    update_option('widget_block', $options['widget_block']);
                }
                // rest
                foreach ($option_keys as $xagio_key) {
                    if (in_array($xagio_key, ['sidebars_widgets', 'widget_block'], true)) continue;
                    if (array_key_exists($xagio_key, $options)) {
                        update_option($xagio_key, $options[$xagio_key]);
                    }
                }
            }

            // 6) Theme mods (e.g., site logo) after attachments
            if (isset($theme_mods['custom_logo'])) {
                $logo_id = (int)$theme_mods['custom_logo'];
                if ($logo_id > 0 && get_post($logo_id)) {
                    set_theme_mod('custom_logo', $logo_id);
                }
            }

            // Kadence caches, widget caches
            delete_option('kadence_global_colors');
            delete_option('kadence_global_typography');
            wp_cache_delete('alloptions', 'options');
            wp_cache_flush();
        }

        private static function xagio_import_single_post_preserving_id(array $xagio_item) {
            global $wpdb;

            $target_id = isset($xagio_item['ID']) ? (int)$xagio_item['ID'] : 0;
            if ($target_id <= 0) return;

            $exists = get_post($target_id);

            $postarr = [
                'ID'                => $target_id,
                'post_type'         => $xagio_item['post_type'],
                'post_title'        => isset($xagio_item['post_title']) ? wp_slash($xagio_item['post_title']) : '',
                'post_name'         => $xagio_item['post_name'] ?? '',
                'post_status'       => $xagio_item['post_status'] ?? 'draft',
                'post_author'       => isset($xagio_item['post_author']) ? (int)$xagio_item['post_author'] : get_current_user_id(),
                'post_excerpt'      => isset($xagio_item['post_excerpt']) ? wp_slash($xagio_item['post_excerpt']) : '',
                'post_content'      => isset($xagio_item['post_content']) ? wp_slash($xagio_item['post_content']) : '',
                'post_parent'       => isset($xagio_item['post_parent']) ? (int)$xagio_item['post_parent'] : 0,
                'menu_order'        => isset($xagio_item['menu_order']) ? (int)$xagio_item['menu_order'] : 0,
                'comment_status'    => $xagio_item['comment_status'] ?? 'closed',
                'ping_status'       => $xagio_item['ping_status'] ?? 'closed',
                'post_password'     => $xagio_item['post_password'] ?? '',
                'post_date'         => $xagio_item['post_date'] ?? current_time('mysql'),
                'post_date_gmt'     => $xagio_item['post_date_gmt'] ?? get_gmt_from_date(current_time('mysql')),
                'post_modified'     => $xagio_item['post_modified'] ?? current_time('mysql'),
                'post_modified_gmt' => $xagio_item['post_modified_gmt'] ?? get_gmt_from_date(current_time('mysql')),
            ];

            if (!$exists) {
                $wpdb->insert(
                    $wpdb->posts,
                    [
                        'ID'                => $target_id,
                        'post_author'       => $postarr['post_author'],
                        'post_date'         => $postarr['post_date'],
                        'post_date_gmt'     => $postarr['post_date_gmt'],
                        'post_content'      => '',
                        'post_title'        => '',
                        'post_excerpt'      => '',
                        'post_status'       => ($xagio_item['post_type'] === 'nav_menu_item' ? 'publish' : 'auto-draft'),
                        'comment_status'    => 'closed',
                        'ping_status'       => 'closed',
                        'post_name'         => '',
                        'post_modified'     => $postarr['post_modified'],
                        'post_modified_gmt' => $postarr['post_modified_gmt'],
                        'post_parent'       => 0,
                        'guid'              => $xagio_item['guid'] ?? '',
                        'menu_order'        => 0,
                        'post_type'         => $postarr['post_type'],
                        'post_mime_type'    => $xagio_item['post_mime_type'] ?? '',
                        'comment_count'     => 0,
                    ],
                    ['%d','%d','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%d','%s','%s','%d']
                );
            }

            $updated_id = wp_update_post($postarr, true);
            if (is_wp_error($updated_id)) return;

            if (!empty($xagio_item['guid'])) {
                $wpdb->update($wpdb->posts, ['guid' => $xagio_item['guid']], ['ID' => $target_id], ['%s'], ['%d']);
            }

            if (!empty($xagio_item['meta']) && is_array($xagio_item['meta'])) {
                foreach ($xagio_item['meta'] as $meta_key => $values) {
                    delete_post_meta($target_id, $meta_key);
                    foreach ((array)$values as $v) {
                        add_post_meta($target_id, $meta_key, maybe_unserialize($v));
                    }
                }
            }
        }

        private static function xagio_import_single_attachment_preserving_id(array $att, ?string $extract_dir, string $uploads_basedir, string $uploads_baseurl) {
            global $wpdb;

            $target_id = isset($att['ID']) ? (int)$att['ID'] : 0;
            if ($target_id <= 0) return;

            $exists = get_post($target_id);

            $rel  = $att['attached_file'] ?? '';
            $src  = ($rel && $extract_dir) ? $extract_dir . ltrim($rel, '/\\') : null;
            $dest = $rel ? $uploads_basedir . $rel : null;

            // Copy original file into uploads
            if ($src && file_exists($src)) {
                wp_mkdir_p(dirname($dest));
                @copy($src, $dest);
            }

            // Mime + guid
            $mime = $att['post_mime_type'] ?? ($rel ? (wp_check_filetype($dest)['type'] ?? '') : '');
            $guid = $rel ? trailingslashit($uploads_baseurl) . $rel : ($att['guid'] ?? '');

            // Create placeholder row with explicit ID if needed
            if (!$exists) {
                $wpdb->insert(
                    $wpdb->posts,
                    [
                        'ID'                => $target_id,
                        'post_author'       => isset($att['post_author']) ? (int)$att['post_author'] : get_current_user_id(),
                        'post_date'         => $att['post_date'] ?? current_time('mysql'),
                        'post_date_gmt'     => $att['post_date_gmt'] ?? get_gmt_from_date(current_time('mysql')),
                        'post_content'      => '',
                        'post_title'        => '',
                        'post_excerpt'      => '',
                        'post_status'       => 'inherit',
                        'comment_status'    => 'closed',
                        'ping_status'       => 'closed',
                        'post_name'         => $att['post_name'] ?? '',
                        'post_modified'     => $att['post_modified'] ?? current_time('mysql'),
                        'post_modified_gmt' => $att['post_modified_gmt'] ?? get_gmt_from_date(current_time('mysql')),
                        'post_parent'       => isset($att['post_parent']) ? (int)$att['post_parent'] : 0,
                        'guid'              => $guid,
                        'menu_order'        => 0,
                        'post_type'         => 'attachment',
                        'post_mime_type'    => $mime ?: '',
                        'comment_count'     => 0,
                    ],
                    ['%d','%d','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%d','%s','%s','%d']
                );
            }

            // Bring record up to date
            $postarr = [
                'ID'                => $target_id,
                'post_type'         => 'attachment',
                'post_title'        => isset($att['post_title']) ? wp_slash($att['post_title']) : '',
                'post_name'         => $att['post_name'] ?? '',
                'post_status'       => 'inherit',
                'post_author'       => isset($att['post_author']) ? (int)$att['post_author'] : get_current_user_id(),
                'post_excerpt'      => isset($att['post_excerpt']) ? wp_slash($att['post_excerpt']) : '',
                'post_content'      => isset($att['post_content']) ? wp_slash($att['post_content']) : '',
                'post_parent'       => isset($att['post_parent']) ? (int)$att['post_parent'] : 0,
                'menu_order'        => isset($att['menu_order']) ? (int)$att['menu_order'] : 0,
                'post_date'         => $att['post_date'] ?? current_time('mysql'),
                'post_date_gmt'     => $att['post_date_gmt'] ?? get_gmt_from_date(current_time('mysql')),
                'post_modified'     => $att['post_modified'] ?? current_time('mysql'),
                'post_modified_gmt' => $att['post_modified_gmt'] ?? get_gmt_from_date(current_time('mysql')),
                'post_mime_type'    => $mime ?: '',
                'guid'              => $guid,
            ];
            $updated_id = wp_update_post($postarr, true);
            if (is_wp_error($updated_id)) return;

            // Critical metas first
            if ($rel) {
                update_post_meta($target_id, '_wp_attached_file', $rel);
            }

            // Restore other metas (excluding regenerated ones)
            if (!empty($att['meta']) && is_array($att['meta'])) {
                foreach ($att['meta'] as $meta_key => $values) {
                    if (in_array($meta_key, ['_wp_attached_file', '_wp_attachment_metadata'], true)) continue;
                    delete_post_meta($target_id, $meta_key);
                    foreach ((array)$values as $v) {
                        add_post_meta($target_id, $meta_key, maybe_unserialize($v));
                    }
                }
            }

            // Always regenerate metadata/subsizes to fix blank thumbnails
            if ($rel && $dest && file_exists($dest)) {
                self::xagio_regenerate_and_update_attachment_metadata($target_id, $dest);
            } else {
                // If missing file, try to reuse exported metadata
                $meta_exported = $att['meta']['_wp_attachment_metadata'][0] ?? null;
                $xagio_meta = maybe_unserialize($meta_exported);
                if (!empty($xagio_meta) && is_array($xagio_meta)) {
                    update_post_meta($target_id, '_wp_attachment_metadata', $xagio_meta);
                }
            }
        }

        private static function xagio_import_menu_term_preserving_id(array $menu) {
            global $wpdb;

            $term_id     = (int)($menu['term_id'] ?? 0);
            $xagio_name        = $menu['name'] ?? '';
            $slug        = $menu['slug'] ?? '';
            $xagio_description = $menu['description'] ?? '';
            $term_group  = isset($menu['term_group']) ? (int)$menu['term_group'] : 0;

            if ($term_id <= 0) return;

            $existing = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM {$wpdb->terms} WHERE term_id=%d", $term_id));

            if (!$existing) {
                // Insert raw with explicit term_id
                $wpdb->insert(
                    $wpdb->terms,
                    ['term_id' => $term_id, 'name' => $xagio_name, 'slug' => $slug, 'term_group' => $term_group],
                    ['%d','%s','%s','%d']
                );
                // Ensure taxonomy row for nav_menu
                $wpdb->insert(
                    $wpdb->term_taxonomy,
                    ['term_id' => $term_id, 'taxonomy' => 'nav_menu', 'description' => $xagio_description, 'parent' => 0, 'count' => 0],
                    ['%d','%s','%s','%d','%d']
                );
            } else {
                // Update term & taxonomy
                $wpdb->update(
                    $wpdb->terms,
                    ['name' => $xagio_name, 'slug' => $slug, 'term_group' => $term_group],
                    ['term_id' => $term_id],
                    ['%s','%s','%d'],
                    ['%d']
                );

                $tt_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id=%d AND taxonomy='nav_menu' LIMIT 1",
                    $term_id
                ));
                if ($tt_id) {
                    $wpdb->update(
                        $wpdb->term_taxonomy,
                        ['description' => $xagio_description],
                        ['term_taxonomy_id' => $tt_id],
                        ['%s'],
                        ['%d']
                    );
                } else {
                    $wpdb->insert(
                        $wpdb->term_taxonomy,
                        ['term_id' => $term_id, 'taxonomy' => 'nav_menu', 'description' => $xagio_description, 'parent' => 0, 'count' => 0],
                        ['%d','%s','%s','%d','%d']
                    );
                }
            }

            // Restore term meta
            if (!empty($menu['meta']) && is_array($menu['meta'])) {
                foreach ($menu['meta'] as $meta_key => $values) {
                    delete_term_meta($term_id, $meta_key);
                    foreach ((array)$values as $v) {
                        add_term_meta($term_id, $meta_key, maybe_unserialize($v));
                    }
                }
            }
        }

        private static function xagio_safe_set_sidebars_widgets(array $map): void {
            if (!isset($map['array_version'])) {
                $existing = get_option('sidebars_widgets');
                $map['array_version'] = is_array($existing) && isset($existing['array_version']) ? (int)$existing['array_version'] : 3;
            }
            if (function_exists('wp_set_sidebars_widgets')) {
                wp_set_sidebars_widgets($map);
            } else {
                update_option('sidebars_widgets', $map);
            }
        }

        private static function xagio_make_temp_dir(): string {
            $base = wp_tempnam('xagio_import_' . time());
            @wp_delete_file($base);
            wp_mkdir_p($base);
            return trailingslashit($base);
        }

        private static function xagio_get_first_entry_by_name_suffix(ZipArchive $zip, string $suffix): ?string {
            $suffix = ltrim(str_replace('\\', '/', $suffix), '/');
            for ($xagio_i = 0; $xagio_i < $zip->numFiles; $xagio_i++) {
                $xagio_name = $zip->getNameIndex($xagio_i);
                if (!$xagio_name) continue;
                $norm = ltrim(str_replace('\\', '/', $xagio_name), '/');
                if (substr($norm, -strlen($suffix)) === $suffix) {
                    $data = $zip->getFromIndex($xagio_i);
                    if ($data !== false) return $data;
                }
            }
            return null;
        }

        private static function xagio_cleanup_dir( string $dir ) : void {
            if ( empty($dir) ) {
                return;
            }

            // Normalize path a bit
            $dir = untrailingslashit($dir);

            // Ensure filesystem is initialized
            global $wp_filesystem;
            if ( ! $wp_filesystem ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }
            if ( ! $wp_filesystem ) {
                return; // couldn't init
            }

            // If it's not a directory (as far as WP_Filesystem can tell), bail
            if ( ! $wp_filesystem->is_dir($dir) ) {
                return;
            }

            // Get all files/dirs under $dir
            $items = list_files($dir, true); // true = recursive
            if ( is_array($items) ) {
                /*
                 * list_files() order isn't guaranteed deepest-first.
                 * Sorting longest-path-first helps ensure children are deleted before parents.
                 */
                usort($items, static function($a, $b) {
                    return strlen($b) <=> strlen($a);
                });

                foreach ( $items as $path ) {
                    // Delete file or directory via WP_Filesystem (true = recursive for dirs)
                    $wp_filesystem->delete($path, true);
                }
            }

            // Finally delete the root directory itself
            $wp_filesystem->delete($dir, true);
        }

        /**
         * Regenerate and update attachment metadata (thumbnails/subsizes).
         * Sets a transient notice if both GD and Imagick are missing.
         */
        private static function xagio_regenerate_and_update_attachment_metadata(int $attachment_id, string $abs_path): bool {
            if (!file_exists($abs_path)) return false;

            $has_editor = class_exists('Imagick') || extension_loaded('gd');

            require_once ABSPATH . 'wp-admin/includes/image.php';

            $xagio_meta = @wp_generate_attachment_metadata($attachment_id, $abs_path);
            if (empty($xagio_meta) || !is_array($xagio_meta)) {
                if (!$has_editor) {
                    set_transient('xagio_missing_image_editor', 1, HOUR_IN_SECONDS);
                }
                return false;
            }

            wp_update_attachment_metadata($attachment_id, $xagio_meta);
            return true;
        }

        private static function xagio_relativize_upload_urls_deep(&$node) {
            $skip_keys = ['guid', 'attached_file'];

            $walk = function (&$val, $xagio_key = null) use (&$walk, $skip_keys) {
                if (is_array($val)) {
                    foreach ($val as $xagio_k => &$v) { $walk($v, $xagio_k); }
                    return;
                }
                if (!is_string($val)) { return; }
                if ($xagio_key && in_array($xagio_key, $skip_keys, true)) { return; }

                // 1) Normal URLs: https://host/.../wp-content/uploads/...
                $val = preg_replace(
                    '#https?://[^"\')\s]*(?=/wp-content/uploads/)#i',
                    '',
                    $val
                );

                // 2) JSON-escaped URLs (in strings that hold JSON): https:\/\/host\/...\/wp-content\/uploads\/...
                //    Keep escaped slashes intact so embedded JSON stays valid.
                $val = preg_replace(
                    '#https?:\\\\/\\\\/[^"\'\)\s]*(?=\\\\/wp-content\\\\/uploads\\\\/)#i',
                    '',
                    $val
                );

                // 3) Safety: collapse any accidental double-slash to single (e.g., //wp-content/...)
                if (strpos($val, '//wp-content/uploads/') !== false) {
                    $val = str_replace('//wp-content/uploads/', '/wp-content/uploads/', $val);
                }
            };

            $walk($node, null);
        }

        private static function xagio_clear_elementor_and_menu_terms(): void {
            global $wpdb;

            // 1) Terms from Elementor import session meta
            $term_ids_from_meta = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT DISTINCT term_id
             FROM {$wpdb->termmeta}
             WHERE meta_key = %s",
                    '_elementor_import_session_id'
                )
            );

            // 2) Terms + TT IDs from specific taxonomies (literals)
            $rows_tax = $wpdb->get_results(
                "SELECT term_id, term_taxonomy_id
         FROM {$wpdb->term_taxonomy}
         WHERE taxonomy IN ('elementor_library_type','nav_menu')",
                ARRAY_A
            );

            $term_ids_from_tax = [];
            $tt_ids_from_tax   = [];

            foreach ( (array) $rows_tax as $xagio_r ) {
                $term_ids_from_tax[] = (int) $xagio_r['term_id'];
                $tt_ids_from_tax[]   = (int) $xagio_r['term_taxonomy_id'];
            }

            $term_ids_to_delete = array_values( array_unique( array_merge(
                array_map( 'intval', (array) $term_ids_from_meta ),
                array_map( 'intval', (array) $term_ids_from_tax )
            ) ) );

            if ( empty( $term_ids_to_delete ) ) {
                return;
            }

            // Build IN (%d, %d, ...) placeholders once
            $in_terms = implode( ',', array_fill( 0, count( $term_ids_to_delete ), '%d' ) );

            // Also delete relationships by term_taxonomy_id
            $tt_ids_for_terms = $wpdb->get_col($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id IN ($in_terms)", $term_ids_to_delete ));

            $tt_ids_to_delete = array_values( array_unique( array_merge(
                array_map( 'intval', (array) $tt_ids_for_terms ),
                array_map( 'intval', (array) $tt_ids_from_tax )
            ) ) );

            if ( ! empty( $tt_ids_to_delete ) ) {
                $in_tt = implode( ',', array_fill( 0, count( $tt_ids_to_delete ), '%d' ) );
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ($in_tt)",$tt_ids_to_delete));
            }

            // termmeta -> term_taxonomy -> terms
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->termmeta} WHERE term_id IN ($in_terms)",$term_ids_to_delete));
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->term_taxonomy} WHERE term_id IN ($in_terms)",$term_ids_to_delete));
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->terms} WHERE term_id IN ($in_terms)",$term_ids_to_delete));
        }



        public static function xagio_add_service_links($pages) {
            global $wpdb;

            // --- NEW: allow $pages to be a simple array of post IDs ---
            if (is_array($pages) && !empty($pages)) {
                // If first element is NOT an array, assume a list of post IDs
                $first = reset($pages);
                if (!is_array($first)) {
                    $built_pages = [];
                    foreach ($pages as $pid) {
                        $pid = (int) $pid;
                        if ($pid <= 0) { continue; }

                        $xagio_p = get_post($pid);
                        if (!$xagio_p || !isset($xagio_p->post_status)) { continue; }

                        $title = get_the_title($xagio_p);
                        if ($title === '' || $title === null) { continue; }

                        $permalink = get_permalink($xagio_p);
                        if (!$permalink) { continue; }

                        if (function_exists('wp_make_link_relative')) {
                            $permalink = wp_make_link_relative($permalink);
                        }

                        $built_pages[] = [
                            'label' => $title,
                            'url'   => $permalink,
                        ];
                    }
                    // Replace input with built array if we built anything
                    if (!empty($built_pages)) {
                        $pages = $built_pages;
                    } else {
                        return false;
                    }
                }
            } else {
                return false;
            }

            // 1) Find latest published kadence_navigation with exact post_title
            $post_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "
            SELECT ID
            FROM {$wpdb->posts}
            WHERE post_type = %s
              AND post_status = 'publish'
              AND post_title = %s
            ORDER BY post_date_gmt DESC
            LIMIT 1
            ",
                    'kadence_navigation',
                    'Header Menu'
                )
            );

            if (!$post_id) {
                $post_id = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT ID
             FROM {$wpdb->posts}
             WHERE post_type = %s
               AND post_status = 'publish'
               AND post_title = %s
             ORDER BY post_date_gmt DESC
             LIMIT 1",
                        'kadence_navigation',
                        'nav_menu'
                    )
                );
            }

            if (!$post_id) {
                return false; // not found
            }

            $header = get_post($post_id);
            if (!$header || $header->post_type !== 'kadence_navigation' || $header->post_status !== 'publish') {
                return false;
            }

            $xagio_blocks = parse_blocks($header->post_content);

            // Helper: Kadence-like uniqueID keeping parent's numeric prefix (e.g. "27_")
            $make_unique = function($parent_unique_id = '') {
                $prefix = 'kb_';
                if (is_string($parent_unique_id) && strpos($parent_unique_id, '_') !== false) {
                    $prefix = preg_replace('/_.*/', '_', $parent_unique_id); // keep "27_"
                }
                $chunk1 = substr(bin2hex(random_bytes(6)), 0, 6);
                $chunk2 = substr(bin2hex(random_bytes(2)), 0, 2);
                return $prefix . $chunk1 . '-' . $chunk2;
            };

            // Helper: rebuild wrapper innerContent with a NULL placeholder per child
            $build_inner_content_shell = function($children_count) {
                $ic = [];
                $ic[] = "\n";
                for ($xagio_i = 0; $xagio_i < $children_count; $xagio_i++) {
                    $ic[] = NULL;
                    $ic[] = ($xagio_i === $children_count - 1) ? "\n" : "\n\n";
                }
                return $ic;
            };

            $modified = false;

            foreach ($xagio_blocks as &$rootBlock) {
                if (($rootBlock['blockName'] ?? null) !== 'kadence/navigation') {
                    continue;
                }

                $rootBlock['innerBlocks'] = $rootBlock['innerBlocks'] ?? [];

                foreach ($rootBlock['innerBlocks'] as &$navItem) {
                    $is_service =
                        ($navItem['blockName'] ?? null) === 'kadence/navigation-link' &&
                        isset($navItem['attrs']['label']) &&
                        $navItem['attrs']['label'] === 'Service';

                    if (!$is_service) {
                        continue;
                    }

                    // Ensure children container
                    $navItem['innerBlocks'] = $navItem['innerBlocks'] ?? [];

                    // Avoid duplicates
                    $existing_labels = [];
                    $existing_urls   = [];
                    foreach ($navItem['innerBlocks'] as $child) {
                        if (($child['blockName'] ?? null) !== 'kadence/navigation-link') {
                            continue;
                        }
                        $existing_labels[] = $child['attrs']['label'] ?? '';
                        $existing_urls[]   = $child['attrs']['url']   ?? '';
                    }

                    foreach ($pages as $xagio_p) {
                        $label = isset($xagio_p['label']) ? trim($xagio_p['label']) : '';
                        $xagio_url   = isset($xagio_p['url'])   ? trim($xagio_p['url'])   : '';
                        if ($label === '' || $xagio_url === '') {
                            continue;
                        }
                        if (in_array($label, $existing_labels, true) || in_array($xagio_url, $existing_urls, true)) {
                            continue;
                        }

                        $navItem['innerBlocks'][] = [
                            'blockName'    => 'kadence/navigation-link',
                            'attrs'        => [
                                'uniqueID' => $make_unique($navItem['attrs']['uniqueID'] ?? ''),
                                'label'    => $label,
                                'url'      => $xagio_url,
                                'kind'     => 'custom',
                            ],
                            'innerBlocks'  => [],
                            'innerHTML'    => '',
                            'innerContent' => [],
                        ];
                        $modified = true;
                    }

                    if ($modified) {
                        $count = count($navItem['innerBlocks']);
                        $navItem['innerContent'] = $build_inner_content_shell($count);
                        $navItem['innerHTML']    = implode('', array_map(
                            function ($v) {
                                return is_string($v) ? $v : '';
                            },
                            $navItem['innerContent']
                        ));
                    }

                    break 2;
                }
            }
            unset($rootBlock, $navItem);

            if ($modified) {
                $new_content = serialize_blocks($xagio_blocks);

                wp_update_post([
                    'ID'           => $post_id,
                    'post_content' => $new_content,
                ]);

                return true;
            }

            return false;
        }


        public static function createTable()
        {
            global $wpdb;
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $charset_collate = $wpdb->get_charset_collate();
            $creation_query  = 'CREATE TABLE xag_batches (
			        `id` int(11) NOT NULL AUTO_INCREMENT,
			        `batch_id` int(11),
			        `status` varchar(255) default "pending",		  
			        `date_created` datetime,			        
			        PRIMARY KEY  (`id`)
			    ) ' . $charset_collate . ';';
            @dbDelta($creation_query);
        }

        public static function removeTable()
        {
            global $wpdb;
            $wpdb->query('DROP TABLE IF EXISTS xag_batches;');
        }

    }

}