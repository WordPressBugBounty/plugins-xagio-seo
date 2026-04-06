<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_NICHEHUNTER')) {

    class XAGIO_MODEL_NICHEHUNTER
    {

        private static function defines()
        {
            define('XAGIO_GOOGLE_SEARCH_WINDOW_BROAD', filter_var(get_option('XAGIO_GOOGLE_SEARCH_WINDOW_BROAD'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_GOOGLE_SEARCH_WINDOW_PHRASE', filter_var(get_option('XAGIO_GOOGLE_SEARCH_WINDOW_PHRASE'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_GOOGLE_SEARCH_WINDOW_INTITLE', filter_var(get_option('XAGIO_GOOGLE_SEARCH_WINDOW_INTITLE'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_GOOGLE_SEARCH_WINDOW_INURL', filter_var(get_option('XAGIO_GOOGLE_SEARCH_WINDOW_INURL'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_NICHE_HUNTER_TLDS', get_option('XAGIO_NICHE_HUNTER_TLDS'));
        }

        public static function initialize()
        {
            XAGIO_MODEL_NICHEHUNTER::defines();

            if (!XAGIO_HAS_ADMIN_PERMISSIONS) return;

            add_action('admin_post_xagio_niche_hunter_results', [
                'XAGIO_MODEL_NICHEHUNTER',
                'getResults'
            ]);

            add_action('admin_post_xagio_get_niche_competition', [
                'XAGIO_MODEL_NICHEHUNTER',
                'getCompetition'
            ]);

            add_action('admin_post_xagio_check_if_niche_batch_is_done', [
                'XAGIO_MODEL_NICHEHUNTER',
                'checkIfNicheBatchIsDone'
            ]);

            add_action('admin_post_xagio_niche_hunter_history', [
                'XAGIO_MODEL_NICHEHUNTER',
                'getHistory'
            ]);

            add_action('admin_post_xagio_niche_hunter_keywords', [
                'XAGIO_MODEL_NICHEHUNTER',
                'getKeywords'
            ]);

            add_action('admin_post_xagio_niche_hunter_save_tld', [
                'XAGIO_MODEL_NICHEHUNTER',
                'saveTLDS'
            ]);

            add_action('admin_post_xagio_niche_hunter_get_windows', [
                'XAGIO_MODEL_NICHEHUNTER',
                'getGoogleSearchWindows'
            ]);

            add_action('admin_post_xagio_niche_hunter_check_domain', [
                'XAGIO_MODEL_NICHEHUNTER',
                'checkDomain'
            ]);

            add_action('admin_post_xagio_submit_niche_keywords', [
                'XAGIO_MODEL_NICHEHUNTER',
                'saveNicheKeywords'
            ]);

            add_action('admin_post_xagio_delete_keywords', [
                'XAGIO_MODEL_NICHEHUNTER',
                'deleteKeywords'
            ]);

            add_action('admin_post_xagio_delete_history_group', [
                'XAGIO_MODEL_NICHEHUNTER',
                'deleteHistoryGroup'
            ]);

			add_action('admin_post_xagio_copy_to_clipboard', [
                'XAGIO_MODEL_NICHEHUNTER',
                'copyToClipboard'
            ]);
        }

        public static function saveNicheKeywords()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['project_id']) || !isset($_POST['group_id']) || !isset($_POST['keywords'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $project_id = isset($_POST['project_id']) ? sanitize_text_field($_POST['project_id']) : '';
            $group_id   = isset($_POST['group_id']) ? sanitize_text_field($_POST['group_id']) : '';
            $keywords   = [];

            if (isset($_POST['keywords']) && is_array($_POST['keywords'])) {
                $keywords = array_map(function($keyword_data) {
                    return [
                        'keyword' => isset($keyword_data['keyword']) ? sanitize_text_field($keyword_data['keyword']) : '',
                        'volume' => isset($keyword_data['volume']) ? intval($keyword_data['volume']) : 0,
                        'cpc' => isset($keyword_data['cpc']) ? floatval($keyword_data['cpc']) : 0.0,
                        'intitle' => isset($keyword_data['intitle']) ? intval($keyword_data['intitle']) : 0,
                        'inurl' => isset($keyword_data['inurl']) ? intval($keyword_data['inurl']) : 0,
                    ];
                }, $_POST['keywords']);
            }

            $project_name = 'NicheHunter Keywords - ' . gmdate('Y-m-d');
            if (!is_numeric($project_id)) {
                if (!empty($project_id)) {
                    $project_name = $project_id;
                }
                $wpdb->insert('xag_projects', [
                    'project_name' => $project_name
                ]);

                $project_id = $wpdb->insert_id;
            }

            $group_name = 'NicheHunter Group';
            if (!is_numeric($group_id)) {
                if (!empty($group_id)) {
                    $group_name = $group_id;
                }
                $wpdb->insert('xag_groups', [
                    'project_id' => $project_id,
                    'group_name' => $group_name
                ]);
                $group_id = $wpdb->insert_id;
            }


            foreach ($keywords as $keyword) {
                $keyword_data = [
                    'group_id' => $group_id,
                    'keyword'  => $keyword['keyword'],
                    'volume'   => $keyword['volume'],
                    'cpc'      => $keyword['cpc'],
                    'intitle'  => $keyword['intitle'],
                    'inurl'    => $keyword['inurl']
                ];
                $wpdb->insert('xag_keywords', $keyword_data);
            }


            xagio_jsonc([
                'status'  => 'success',
                'message' => 'Successfully added selected keywords to Project Planner'
            ]);

        }


        public static function checkDomain()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['domain'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $tlds = get_option('XAGIO_NICHE_HUNTER_TLDS');

            if (!$tlds)
                $tlds = [
                    '.com',
                    '.net',
                    '.org'
                ];

            $xagio_output = XAGIO_API::apiRequest(
                $apiEndpoint = 'live_database', $method = 'GET', $xagio_args = [
                'type'   => 'check_domain',
                'tlds'   => $tlds,
                'domain' => sanitize_text_field(wp_unslash($_POST['domain']))
            ], $xagio_http_code, $without_license = FALSE
            );

            xagio_jsonc($xagio_output);
        }

        public static function getHistory()
        {
            $xagio_output = XAGIO_API::apiRequest(
                $apiEndpoint = 'live_database', $method = 'GET', $xagio_args = ['type' => 'get_history'], $xagio_http_code, $without_license = FALSE
            );

            $OUT = [];
            if($xagio_output) {
                foreach ($xagio_output as $o) {
                    $o['filters'] = unserialize($o['filters']);
                    $OUT[]        = $o;
                }
            }

            xagio_jsonc($OUT);
        }

        public static function getKeywords()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $xagio_output = XAGIO_API::apiRequest(
                $apiEndpoint = 'live_database', $method = 'GET', $xagio_args = [
                'type' => 'get_keywords',
                'id'   => sanitize_text_field(wp_unslash($_POST['id']))
            ], $xagio_http_code, $without_license = FALSE
            );

            if (is_array($xagio_output) && sizeof($xagio_output) > 0) {
                for ($xagio_i = 0; $xagio_i < sizeof($xagio_output); $xagio_i++) {
                    $xagio_output[$xagio_i]['history'] = maybe_unserialize($xagio_output[$xagio_i]['history']);
                }
            }

            xagio_jsonc($xagio_output);
        }

        public static function saveTLDS()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['mytld'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $tlds = map_deep(wp_unslash($_POST['mytld']), 'sanitize_text_field');
            update_option('XAGIO_NICHE_HUNTER_TLDS', $tlds);
        }

        public static function getGoogleSearchWindows()
        {
            $data = [
                'broad'   => filter_var(get_option('XAGIO_GOOGLE_SEARCH_WINDOW_BROAD'), FILTER_VALIDATE_BOOLEAN),
                'phrase'  => filter_var(get_option('XAGIO_GOOGLE_SEARCH_WINDOW_PHRASE'), FILTER_VALIDATE_BOOLEAN),
                'intitle' => filter_var(get_option('XAGIO_GOOGLE_SEARCH_WINDOW_INTITLE'), FILTER_VALIDATE_BOOLEAN),
                'inurl'   => filter_var(get_option('XAGIO_GOOGLE_SEARCH_WINDOW_INURL'), FILTER_VALIDATE_BOOLEAN),
            ];

            xagio_jsonc($data);

        }

        public static function getResults()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // Sanitize and prepare the data from POST fields
            $xagio_args = array(
                'keyword_like'         => sanitize_text_field( wp_unslash( $_POST['keyword_like'] ?? '' ) ),
                'filters'              => array(
                    'keyword'          => sanitize_text_field( wp_unslash( $_POST['filters']['keyword'] ?? '' ) ),
                    'location'         => sanitize_text_field( wp_unslash( $_POST['filters']['location'] ?? '' ) ),
                    'keyword_exclude'  => sanitize_text_field( wp_unslash( $_POST['filters']['keyword_exclude'] ?? '' ) ),
                ),
                'gms-min'              => absint( wp_unslash( $_POST['gms-min'] ?? 0 ) ),
                'gms-max'              => absint( wp_unslash( $_POST['gms-max'] ?? 10000 ) ),
                'cpc-min'              => floatval( wp_unslash( $_POST['cpc-min'] ?? 0 ) ),
                'cpc-max'              => floatval( wp_unslash( $_POST['cpc-max'] ?? 100 ) ),
                'cpm-min'              => floatval( wp_unslash( $_POST['cpm-min'] ?? 0 ) ),
                'cpm-max'              => floatval( wp_unslash( $_POST['cpm-max'] ?? 1 ) ),
            );

            // Make the API request
            $xagio_output = XAGIO_API::apiRequest('live_database', 'POST', $xagio_args, $xagio_http_code, false);

            // Send the JSON response
            xagio_jsonc($xagio_output);
        }

        public static function getCompetition()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (isset($_POST['ids'], $_POST['language'], $_POST['location'])) {

                $ids      = explode(',', sanitize_text_field(wp_unslash($_POST['ids'])));
                $xagio_language = sanitize_text_field(wp_unslash($_POST['language']));
                $xagio_location = sanitize_text_field(wp_unslash($_POST['location']));

                if (sizeof($ids) < 1) {
                    xagio_json('error', 'You must send at least one keyword to analysis.');
                    return;
                }

                if (sizeof($ids) >= 1000) {
                    xagio_json('error', 'Maximum number of keywords allowed is 1000 per request. Please split your keywords into multiple requests.');
                    return;
                }

                $xagio_http_code = 0;
                $xagio_result    = XAGIO_API::apiRequest($endpoint = 'niche_competition', $method = 'POST', [
                    'ids'      => $ids,
                    'language' => $xagio_language,
                    'location' => $xagio_location,
                ], $xagio_http_code);

                if ($xagio_http_code == 200) {
                    xagio_json('success', $xagio_result['message']);
                } else {
                    xagio_json('error', $xagio_result['message']);
                }
            }
        }

        public static function checkIfNicheBatchIsDone()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $xagio_output = XAGIO_API::apiRequest(
                $apiEndpoint = 'niche_competition', $method = 'GET', $xagio_args = [], $xagio_http_code, $without_license = FALSE
            );

            if ($xagio_http_code == 200) {
                xagio_json('success', $xagio_output['message']);
            } else {
                xagio_json('running', $xagio_output['message']);
            }
        }

        public static function deleteKeywords()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $ids = $_POST['ids'];

            if (isset($ids) && !empty($ids)) {
                $ids = explode(',', $ids);

                $xagio_http_code = 0;
                $xagio_result    = XAGIO_API::apiRequest($endpoint = 'niche_delete_keywords', $method = 'POST', [
                    'ids'      => $ids
                ], $xagio_http_code);

                if ($xagio_http_code == 200) {
                    xagio_json('success', $xagio_result['message']);
                } else {
                    xagio_json('error', $xagio_result['message']);
                }
            }
        }

        public static function deleteHistoryGroup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $group_id = $_POST['group_id'];

            if (isset($group_id) && !empty($group_id)) {
                $xagio_http_code = 0;
                $xagio_result    = XAGIO_API::apiRequest($endpoint = 'niche_delete_history_group', $method = 'POST', [
                    'group_id'      => $group_id
                ], $xagio_http_code);

                if ($xagio_http_code == 200) {
                    xagio_json('success', $xagio_result['message']);
                } else {
                    xagio_json('error', $xagio_result['message']);
                }
            }
        }

		public static function copyToClipboard()
		{
			check_ajax_referer('xagio_nonce', '_xagio_nonce');

			$ids = $_POST['ids'];

			if (isset($ids) && !empty($ids)) {
				$ids = explode(',', $ids);
				$xagio_http_code = 0;
				$xagio_result    = XAGIO_API::apiRequest($endpoint = 'niche_keywords_clipboard', $method = 'POST', [
					'ids'      => $ids
				], $xagio_http_code);

				if ($xagio_http_code == 200) {
					xagio_json('success', 'Keywords retrieved successfully!',  $xagio_result['message']);
				} else {
					xagio_json('error', $xagio_result['message']);
				}
			} else {
				xagio_json('error', 'Keywords array is empty!');
			}
		}
    }

}