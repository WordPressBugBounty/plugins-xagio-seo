<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_AI')) {

    class XAGIO_MODEL_AI
    {
        public static function initialize()
        {
            if (!XAGIO_HAS_ADMIN_PERMISSIONS)
                return;

            add_action('admin_post_xagio_get_ai_frontend_output', [
                'XAGIO_MODEL_AI',
                'getAiFrontEndOutput'
            ]);

            add_action('admin_post_xagio_ai_process_text', [
                'XAGIO_MODEL_AI',
                'processTextEdit'
            ]);

            add_action('admin_post_xagio_ai_check_status_text', [
                'XAGIO_MODEL_AI',
                'checkTextStatus'
            ]);

            add_action('admin_post_xagio_ai_process_image', [
                'XAGIO_MODEL_AI',
                'processImageEditByAttachmentID'
            ]);

            add_action('admin_post_xagio_ai_check_status_image', [
                'XAGIO_MODEL_AI',
                'checkImageStatus'
            ]);

            add_action('admin_post_xagio_ai_check_status_cluster', [
                'XAGIO_MODEL_AI',
                'checkClusterStatus'
            ]);

            add_action('admin_post_xagio_ai_get_attachment_id', [
                'XAGIO_MODEL_AI',
                'getAttachmentIdByURL'
            ]);

            add_action('admin_post_xagio_get_ai_history', [
                'XAGIO_MODEL_AI',
                'getHistory'
            ]);
            add_action('admin_post_xagio_get_ai_schema_history', [
                'XAGIO_MODEL_AI',
                'getSchemaHistory'
            ]);

            add_action('admin_post_xagio_ai_schema', [
                'XAGIO_MODEL_AI',
                'getAiSchema'
            ]);
            add_action('admin_post_xagio_ai_suggest', [
                'XAGIO_MODEL_AI',
                'getAiSuggestions'
            ]);
            add_action('admin_post_xagio_ai_content', [
                'XAGIO_MODEL_AI',
                'getAiContent'
            ]);

            add_action('admin_post_xagio_ai_template_content', [
                'XAGIO_MODEL_AI',
                'getAiContentTemplate'
            ]);

            add_action('admin_post_xagio_ai_clustering', [
                'XAGIO_MODEL_AI',
                'getAiClustering'
            ]);


            add_action('admin_post_xagio_ai_use_template_content', [
                'XAGIO_MODEL_AI',
                'useAiContentTemplate'
            ]);

            add_action('admin_post_xagio_ai_undo_template_content', [
                'XAGIO_MODEL_AI',
                'undoAiContentTemplate'
            ]);

            add_action('admin_post_xagio_ai_output', [
                'XAGIO_MODEL_AI',
                'getAiOutput'
            ]);
            add_action('admin_post_xagio_modify_suggestion', [
                'XAGIO_MODEL_AI',
                'modifySuggestion'
            ]);
            add_action('admin_post_xagio_ai_get_average_prices', [
                'XAGIO_MODEL_AI',
                'getAveragePrices'
            ]);

            add_action('admin_post_xagio_copy_template_page', [
                'XAGIO_MODEL_AI',
                'copyTemplatePage'
            ]);

        }

        public static function copyTemplatePage()
        {
            global $wpdb;

            $xagio_page_id    = intval($_POST['page_id']);
            $is_service = sanitize_text_field(wp_unslash($_POST['page_type']));
            $title      = get_post_meta($xagio_page_id, 'XAGIO_SEO_TITLE', true);

            if (empty($title)) {
                return [
                    'status'  => 'error',
                    'message' => 'SEO Title is missing',
                ];
            }

            $page_type = 'Service';
            if ($is_service === 'homepage') {
                $page_type = 'Home';
            }

            // Use WP_Query instead of deprecated get_page_by_title
            $query = new WP_Query([
                'post_type'      => 'page',
                'post_status'    => 'publish',
                'title'          => $page_type,
                'posts_per_page' => 1,
            ]);

            if ($query->have_posts()) {
                $query->the_post();
                $template_page = get_post(get_the_ID());
                wp_reset_postdata();

                if ($template_page) {
                    // Update the target page with the template content.
                    $xagio_post_data = [
                        'ID'           => $xagio_page_id,
                        'post_title'   => $title,
                        // preserving the SEO title from target page meta
                        'post_content' => $template_page->post_content,
                    ];
                    wp_update_post($xagio_post_data);

                    // Now copy post meta from the template page to the target page.
                    // Fetch all meta for the template page.
                    $xagio_meta = get_post_meta($template_page->ID);
                    foreach ($xagio_meta as $meta_key => $meta_values) {
                        // Optionally, skip copying specific meta keys if desired.
                        // For instance, we are preserving the target's SEO title.
                        if ($meta_key === 'XAGIO_SEO_TITLE') {
                            continue;
                        }

                        // Remove existing meta with the same key from the target.
                        delete_post_meta($xagio_page_id, $meta_key);

                        // Loop through and add each meta value.
                        foreach ($meta_values as $meta_value) {
                            // For Elementor data, you can replicate the original low‐level
                            // insertion if needed.
                            if ($meta_key === '_elementor_data') {
                                $wpdb->insert(
                                    $wpdb->postmeta, [
                                    'post_id'    => $xagio_page_id,
                                    'meta_key'   => '_elementor_data',
                                    'meta_value' => $meta_value
                                ], [
                                        '%d',
                                        '%s',
                                        '%s'
                                    ]
                                );
                            } else {
                                add_post_meta($xagio_page_id, $meta_key, maybe_unserialize($meta_value));
                            }
                        }
                    }
                } else {
                    xagio_jsonc([
                        'status'  => 'error',
                        'message' => "Cannot find page for $page_type",
                    ]);
                }
            } else {
                xagio_jsonc([
                    'status'  => 'error',
                    'message' => "Cannot find any pages for $page_type",
                ]);
            }

            XAGIO_MODEL_OCW::clearElementorCache();

            xagio_jsonc([
                'status'  => 'success',
                'message' => "Successfully copied $page_type template"
            ]);

        }

        public static function getAveragePrices()
        {
            $xagio_output = XAGIO_API::apiRequest('ai', 'POST', [], $xagio_http_code);
            if ($xagio_http_code == 203) {
                xagio_jsonc([
                    'status'  => 'success',
                    'message' => 'Average Prices loaded',
                    'data'    => $xagio_output
                ]);
            } elseif ($xagio_http_code == 406) {
                xagio_jsonc([
                    'status'  => 'upgrade',
                    'message' => 'Upgrade your account to use AI features!'
                ]);
            } else {
                xagio_jsonc([
                    'status'  => 'error',
                    'message' => 'Average Prices not loaded!',
                    'data'    => $xagio_output
                ]);
            }
        }

        public static function get_status_for_current_post()
        {
            global $wpdb;

            // Get the current post ID
            $target_id = get_the_ID();

            // Prepare and execute the query using wpdb::prepare
            $outputQuery = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT `output`, `status` 
             FROM `xag_ai` 
             WHERE `input` = %s AND `target_id` = %d 
             ORDER BY `id` DESC 
             LIMIT 1", 'PAGE_CONTENT', $target_id
                ), ARRAY_A
            );

            // Get the status from the query result
            $status = $outputQuery['status'] ?? false;

            // Return true if status is 'running', otherwise false
            return $status === 'running';
        }

        public static function get_status_for_template_current_post()
        {
            global $wpdb;

            // Get the current post ID
            $target_id = get_the_ID();

            // Prepare and execute the query using wpdb::prepare
            $outputQuery = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT `output`, `status` 
             FROM `xag_ai` 
             WHERE `input` = %s AND `target_id` = %d 
             ORDER BY `id` DESC 
             LIMIT 1", 'PAGE_CONTENT_TEMPLATE', $target_id
                ), ARRAY_A
            );

            // Get the status from the query result
            $status = $outputQuery['status'] ?? false;

            // Return true if status is 'running', otherwise false
            return $status === 'running';
        }


        public static function getSchemaHistory()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['post_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // Sanitize the input
            $post_id = intval($_POST['post_id']);

            // Prepare and execute the query using wpdb::prepare
            $historyQuery = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT `id`, `status` FROM `xag_ai` WHERE `input` = %s AND `target_id` = %d ORDER BY `id` DESC", 'SCHEMA', $post_id
                ), ARRAY_A
            );

            $return = [];

            // Process the results into an array
            foreach ($historyQuery as $history) {
                $return[] = [
                    'id'     => $history['id'],
                    'status' => $history['status']
                ];
            }

            // Return the data as a JSON response
            xagio_jsonc([
                'status'  => 'success',
                'message' => 'Schema AI Status loaded',
                'data'    => $return
            ]);
        }


        public static function getHistory()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['post_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // Sanitize input
            $post_id = intval($_POST['post_id']);
            $row_id  = isset($_POST['row_id']) ? intval($_POST['row_id']) : null;

            // Prepare the query depending on whether `row_id` is set or not
            if ($row_id !== null) {
                $historyQuery = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT `id`, `output`, DATE_FORMAT(`date_created`, '%%a, %%e %%b %%Y') as date_created, `status` 
                 FROM `xag_ai` 
                 WHERE `input` = %s AND `target_id` = %d AND `id` = %d 
                 ORDER BY `id` DESC", 'PAGE_CONTENT', $post_id, $row_id
                    ), ARRAY_A
                );
            } else {
                $historyQuery = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT `id`, `output`, DATE_FORMAT(`date_created`, '%%a, %%e %%b %%Y') as date_created, `status` 
                 FROM `xag_ai` 
                 WHERE `input` = %s AND `target_id` = %d 
                 ORDER BY `id` DESC", 'PAGE_CONTENT', $post_id
                    ), ARRAY_A
                );
            }

            $return = [];
            $count  = 0;

            // Loop through the results to build the response
            foreach ($historyQuery as $history) {
                $return[] = [
                    'id'           => $history['id'],
                    'status'       => $history['status'],
                    'output'       => ($count == 0) ? stripslashes($history['output']) : '',
                    'small'        => substr(stripslashes(wp_strip_all_tags($history['output'])), 0, 100),
                    'date_created' => $history['date_created']
                ];

                $count++;
            }

            // Return the history data as JSON
            xagio_jsonc([
                'status'  => 'success',
                'message' => 'History loaded',
                'data'    => $return
            ]);
        }


        public static function modifySuggestion()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['type'], $_POST['text'], $_POST['group_id'], $_POST['ai_input'], $_POST['row_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            global $wpdb;

            $type     = sanitize_text_field(wp_unslash($_POST['type']));
            $text     = sanitize_text_field(wp_unslash($_POST['text']));
            $group_id = intval($_POST['group_id']);
            $ai_input = sanitize_text_field(wp_unslash($_POST['ai_input']));
            $row_id   = intval($_POST['row_id']);

            $type  = explode('-', $type);
            $xagio_key   = $type[0];
            $index = $type[1];

            switch ($xagio_key) {
                case "header":
                    $xagio_key = "h1";
                    break;
                case "desc":
                    $xagio_key = "description";
                    break;
            }

            $find_group = $wpdb->get_row(
                $wpdb->prepare("SELECT output FROM xag_ai WHERE id = %d AND target_id = %d AND input = %s", $row_id, $group_id, $ai_input), ARRAY_A
            );


            if (!isset($find_group['output'])) {
                xagio_jsonc([
                    'status'  => 'success',
                    'message' => 'Group suggestion not found, try again.'
                ]);
            }

            $xagio_output               = json_decode($find_group['output'], true);
            $xagio_output[$index][$xagio_key] = $text;

            $t = $wpdb->update('xag_ai', [
                'output' => wp_json_encode($xagio_output, JSON_PRETTY_PRINT)
            ], [
                'id'        => $row_id,
                'target_id' => $group_id,
                'input'     => $ai_input
            ]);

            xagio_jsonc([
                'status'  => 'success',
                'message' => 'Suggestion updated'
            ]);

        }

        public static function remoteCheckAiStatuses()
        {
            global $wpdb;

            // 1) find every locally-running AI request
            $running = $wpdb->get_results(
                "SELECT id, target_id, input, DATE_FORMAT(date_created, '%Y-%m-%d') AS run_date 
           FROM xag_ai 
          WHERE status = 'running'", ARRAY_A
            );

            if (empty($running)) {
                return;  // nothing to do
            }

            // 2) batch them by input type
            $batches = [];
            foreach ($running as $row) {
                $batches[$row['input']][] = [
                    'local_id'  => (int)$row['id'],
                    'target_id' => (int)$row['target_id'],
                    'run_date'  => $row['run_date'],
                ];
            }

            // 3) for each input, call the remote check once
            foreach ($batches as $xagio_input => $items) {
                $ids = array_column($items, 'target_id');
                // use the earliest run_date so we pull any remote updates since then
                $dates = array_column($items, 'run_date');
                $xagio_date  = min($dates);

                $remote = self::remoteCheckAiStatusByIds($ids, $xagio_input, $xagio_date);
                if (!is_array($remote)) {
                    continue;  // remote call failed or returned nothing useful
                }

                // 4) update each local row that the remote returned
                foreach ($remote as $xagio_r) {

                    $settings = json_decode($xagio_r['settings'], true);
                    unset($settings['args']);
                    unset($settings['fields']['admin_post']);
                    unset($settings['fields']['api_key']);
                    unset($settings['fields']['domain']);
                    $settings           = $settings['fields'];
                    $settings['output'] = $xagio_r['output'];
                    $settings['return'] = true;

                    // 1) instantiate a REST request
                    $request = new WP_REST_Request('POST', "/xagio/v1/ai/{$xagio_r['input']}");

                    // 2) inject all of your settings into it at once
                    foreach ($settings as $xagio_key => $xagio_value) {
                        $request->set_param($xagio_key, $xagio_value);
                    }

                    if (method_exists('XAGIO_MODEL_AI', 'xagio_ai_' . $xagio_r['input'])) {
                        call_user_func([
                            'XAGIO_MODEL_AI',
                            'xagio_ai_' . $xagio_r['input']
                        ], $request);
                    }

                }
            }

        }

        public static function remoteCheckAiStatusByIds($ids = [], $xagio_input = '', $xagio_date = '')
        {
            // nothing to do
            if (empty($ids) || empty($xagio_input)) {
                return false;
            }

            // sanitize inputs
            $ids   = array_map('intval', $ids);
            $xagio_input = sanitize_text_field($xagio_input);

            // build query params
            $params = [
                'target_ids' => implode(',', $ids),
                'input'      => $xagio_input
            ];

            if (!empty($xagio_date)) {
                $params['date'] = $xagio_date;
            }

            // call remote AI API
            $xagio_http_code = 0;
            $xagio_result    = XAGIO_API::apiRequest('ai', 'GET', $params, $xagio_http_code);

            // only accept a 200 + array response
            if ($xagio_http_code !== 200 || !is_array($xagio_result)) {
                return false;
            }

            // return the raw list of rows: each row = ['id'=>…,'target_id'=>…,'status'=>…,'output'=>…,'date_created'=>…]
            return $xagio_result;
        }

        public static function checkAiStatusByIds($ids = [], $xagio_input = '')
        {

            global $wpdb; // Ensure $wpdb is properly referenced

            if (empty($ids)) {
                return false; // Return false if no group IDs are provided
            }

            // Convert array to a comma-separated string for SQL IN clause
            $xagio_placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $xagio_input        = sanitize_text_field(wp_unslash((string) $xagio_input));
            $results      = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT `status` FROM xag_ai WHERE `input` = %s AND `target_id` IN ($xagio_placeholders)", array_merge([$xagio_input], $ids)
                ), ARRAY_A
            );

            if (empty($results)) {
                return false; // Return false if no results are found
            }

            // Check if all statuses are "completed"
            foreach ($results as $row) {
                if ($row['status'] !== 'completed') {
                    return false;
                }
            }

            return true; // Return true only if all statuses are "completed"

        }

        public static function getAiImageEditsByAttachments($attachment_ids = [], $xagio_input = 'IMAGE_EDIT', $prompt_id = 12)
        {
            if (empty($attachment_ids)) {
                return [
                    'status'  => 'error',
                    'message' => 'No attachment IDs provided.'
                ];
            }

            $results = [];
            foreach ($attachment_ids as $attachment_id) {

                $image_url = wp_get_attachment_url($attachment_id);
                $alt_text  = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);

                $xagio_http_code = 0;
                $xagio_result    = self::_sendAiRequest(
                    $xagio_input, $prompt_id, $attachment_id, [
                    $image_url,
                    $alt_text
                ], ['attachment_id' => $attachment_id], $xagio_http_code
                );

                $results[$attachment_id] = [
                    'status'  => ($xagio_http_code == 200) ? 'success' : 'error',
                    'message' => ($xagio_http_code == 406) ? 'Upgrade your account to use AI features.' : $xagio_result['message']
                ];
            }

            return [
                'status'  => 'success',
                'results' => $results
            ];
        }

        public static function getAiContentByPosts($post_ids = [], $xagio_input = 'PAGE_CONTENT', $prompt_id = 2)
        {
            if (empty($post_ids)) {
                return [
                    'status'  => 'error',
                    'message' => 'No post IDs provided.'
                ];
            }

            $results = [];
            foreach ($post_ids as $post_id) {
                $title       = get_post_meta($post_id, 'XAGIO_SEO_TITLE', true);
                $xagio_description = get_post_meta($post_id, 'XAGIO_SEO_DESCRIPTION', true);
                $h1          = get_the_title($post_id);

                $xagio_http_code = 0;
                $xagio_result    = self::_sendAiRequest(
                    $xagio_input, $prompt_id, $post_id, [
                    $title,
                    $xagio_description,
                    $h1
                ], ['post_id' => $post_id], $xagio_http_code
                );

                $results[$post_id] = [
                    'status'  => ($xagio_http_code == 200) ? 'success' : 'error',
                    'message' => ($xagio_http_code == 406) ? 'Upgrade your account to use AI features.' : $xagio_result['message']
                ];
            }

            return [
                'status'  => 'success',
                'results' => $results
            ];
        }

        public static function getAiContentByPostsTemplate($post_ids = [], $xagio_input = 'PAGE_CONTENT_TEMPLATE', $prompt_id = 11)
        {
            if (empty($post_ids)) {
                return [
                    'status'  => 'error',
                    'message' => 'No post IDs provided.'
                ];
            }

            $results = [];
            foreach ($post_ids as $post_id) {

                $xagio_args = self::getContentProfiles($post_id);

                $xagio_http_code = 0;
                $xagio_result    = self::_sendAiRequest(
                    $xagio_input, $prompt_id, $post_id, $xagio_args, ['post_id' => $post_id], $xagio_http_code
                );

                $results[$post_id] = [
                    'status'  => ($xagio_http_code == 200) ? 'success' : 'error',
                    'message' => ($xagio_http_code == 406) ? 'Upgrade your account to use AI features.' : $xagio_result['message']
                ];
            }

            return [
                'status'  => 'success',
                'results' => $results
            ];
        }

        public static function getAiSchemaByPosts($post_ids = [], $xagio_input = 'SCHEMA', $schema_type = 'creative', $prompt_id = 3)
        {
            if (empty($post_ids)) {
                return [
                    'status'  => 'error',
                    'message' => 'No post IDs provided.'
                ];
            }

            $results = [];
            foreach ($post_ids as $post_id) {
                $title       = get_post_meta($post_id, 'XAGIO_SEO_TITLE', true);
                $xagio_description = get_post_meta($post_id, 'XAGIO_SEO_DESCRIPTION', true);
                $h1          = get_the_title($post_id);


                $xagio_args = self::getSchemaProfiles($post_id, $title, $xagio_description, $h1, $schema_type);

                if (sizeof($xagio_args) > 4) {
                    $prompt_id = 10;
                }


                $xagio_http_code = 0;
                $xagio_result    = self::_sendAiRequest(
                    $xagio_input, $prompt_id, $post_id, $xagio_args, ['post_id' => $post_id], $xagio_http_code
                );

                $results[$post_id] = [
                    'status'  => ($xagio_http_code == 200) ? 'success' : 'error',
                    'message' => ($xagio_http_code == 406) ? 'Upgrade your account to use AI features.' : $xagio_result['message']
                ];
            }

            return [
                'status'  => 'success',
                'results' => $results
            ];
        }

        public static function getAiSuggestionsByGroups($group_ids = [], $xagio_input = 'SEO_SUGGESTIONS_MAIN_KW', $prompt_id = 6)
        {
            global $wpdb;

            if (empty($group_ids)) {
                return [
                    'status'  => 'error',
                    'message' => 'No group IDs provided.'
                ];
            }

            // Convert array to a comma-separated string for SQL IN clause
            $xagio_placeholders = implode(',', array_fill(0, count($group_ids), '%d'));
            $keywords     = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM xag_keywords WHERE `group_id` IN ($xagio_placeholders)", ...$group_ids
                ), ARRAY_A
            );

            if (empty($keywords)) {
                return [
                    'status'  => 'error',
                    'message' => 'No keywords found for the provided group IDs.'
                ];
            }

            $grouped_keywords = [];
            foreach ($keywords as $row) {
                $grouped_keywords[$row['group_id']][] = $row;
            }

            $results = [];
            foreach ($grouped_keywords as $group_id => $keywords) {
                $keyword_group = json_encode(self::packKeywords($keywords));
                $xagio_args          = [
                    $keyword_group,
                    ''
                ];
                $additional    = ['group_id' => $group_id];
                $xagio_http_code     = 0;
                $xagio_result        = self::_sendAiRequest($xagio_input, $prompt_id, $group_id, $xagio_args, $additional, $xagio_http_code);

                $results[$group_id] = [
                    'status'  => ($xagio_http_code == 200) ? 'success' : 'error',
                    'message' => ($xagio_http_code == 406) ? 'Upgrade your account to use AI features.' : $xagio_result['message']
                ];
            }

            return [
                'status'  => 'success',
                'results' => $results
            ];
        }

        public static function getAiSuggestions()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['keyword_group']) || !isset($_POST['group_id']) || !isset($_POST['input']) || !isset($_POST['main_keyword'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $group_id      = intval(wp_unslash($_POST['group_id']));
            $xagio_input         = sanitize_text_field(wp_unslash($_POST['input']));
            $keyword_group = sanitize_text_field(wp_unslash($_POST['keyword_group']));
            $main_keyword  = sanitize_text_field(wp_unslash($_POST['main_keyword']));
            $prompt_id     = intval($_POST['prompt_id']);

            if (empty($keyword_group)) {
                xagio_jsonc([
                    'status'  => 'error',
                    'message' => 'No keywords detected for this group'
                ]);
            }

            // Check if there are results for this group
            $group_suggestions = $wpdb->get_row($wpdb->prepare('SELECT `status`, `output` FROM xag_ai WHERE `target_id` = %d AND `input` = %s', $group_id, $xagio_input), ARRAY_A);

            if (empty($_POST['regenerate'])) {
                if (isset($group_suggestions['status']) && $group_suggestions['status'] == 'completed') {
                    $group_suggestions = stripslashes(json_decode($group_suggestions['output'], TRUE));
                    xagio_jsonc([
                        'status'  => 'success',
                        'message' => 'Results are back',
                        'data'    => $group_suggestions
                    ]);
                }
            }

            if ($xagio_input === 'SEO_SUGGESTIONS') {
                $keyword_group = self::removeallslashes($keyword_group);
                $keyword_group = ltrim($keyword_group, '"');
                $keyword_group = rtrim($keyword_group, '"');
                $keyword_group = json_decode($keyword_group, TRUE);

                $keyword_list = "";
                foreach ($keyword_group as $xagio_item) {
                    $keyword_list .= $xagio_item['text'] . "(" . $xagio_item['weight'] . "), ";
                }
                $keyword_list = rtrim($keyword_list, ", ");

                $xagio_args = [
                    $keyword_list
                ];
            } else {
                $xagio_args = [
                    $keyword_group,
                    $main_keyword
                ];
            }

            $additional = [
                'group_id' => $group_id
            ];

            $xagio_http_code = 0;
            $xagio_result    = self::_sendAiRequest($xagio_input, $prompt_id, $group_id, $xagio_args, $additional, $xagio_http_code);

            if ($xagio_http_code == 406) {
                xagio_jsonc([
                    'status'  => 'upgrade',
                    'message' => 'Upgrade your account to use AI features.'
                ]);
            }

            xagio_jsonc([
                'status'  => ($xagio_http_code == 200) ? 'success' : 'error',
                'message' => $xagio_result['message']
            ]);
        }

        public static function getAiClustering()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $project_id = intval($_POST['project_id']);
            $keywords   = join("\n", $_POST['keywords']);
            $xagio_input      = "CLUSTER";

            $xagio_http_code = 0;
            $xagio_result    = self::_sendAiRequest(
                $xagio_input, 15, $project_id, [
                $keywords
            ], ['project_id' => $project_id], $xagio_http_code
            );

            xagio_jsonc([
                'status'  => ($xagio_http_code == 200) ? 'success' : 'error',
                'message' => ($xagio_http_code == 406) ? 'Upgrade your account to use AI features.' : $xagio_result['message']
            ]);
        }

        public static function getAiContentTemplate()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $post_id   = intval($_POST['post_id']);
            $prompt_id = intval($_POST['prompt_id']);
            $xagio_input     = "PAGE_CONTENT_TEMPLATE";

            $xagio_args = self::getContentProfiles($post_id);

            if (isset($xagio_args['status'])) {
                xagio_jsonc([
                    'status'  => $xagio_args['status'],
                    'message' => $xagio_args['message']
                ]);
            }

            $xagio_http_code = 0;
            $xagio_result    = self::_sendAiRequest(
                $xagio_input, $prompt_id, $post_id, $xagio_args, ['post_id' => $post_id], $xagio_http_code
            );

            xagio_jsonc([
                'status'  => ($xagio_http_code == 200) ? 'success' : 'error',
                'message' => ($xagio_http_code == 406) ? 'Upgrade your account to use AI features.' : $xagio_result['message']
            ]);

        }

        public static function undoAiContentTemplate()
        {
            global $wpdb;

            $post_id = intval($_POST['post_id']);

            $originalExists = get_post_meta($post_id, '_elementor_data_xag_original', TRUE);

            if (!empty($originalExists)) {
                $xagio_elementor_data = get_post_meta($post_id, '_elementor_data', TRUE);

                if (empty($xagio_elementor_data)) {
                    $wpdb->insert(
                        $wpdb->postmeta, [
                        'post_id'    => $post_id,
                        'meta_key'   => '_elementor_data',
                        'meta_value' => $originalExists
                    ], [
                            '%d',
                            '%s',
                            '%s'
                        ]
                    );
                } else {
                    $wpdb->update(
                        $wpdb->postmeta, [
                        'meta_value' => $originalExists
                    ], [
                        'post_id'  => $post_id,
                        'meta_key' => '_elementor_data'
                    ], ['%s'], [
                            '%d',
                            '%s'
                        ]
                    );
                }
            }

            XAGIO_MODEL_OCW::clearElementorCache();

            xagio_jsonc([
                'status'  => 'success',
                'message' => 'Undo success'
            ]);

        }

        public static function useAiContentTemplate()
        {
            global $wpdb;

            $post_id = intval($_POST['post_id']);

            $xagio_ai_results = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT `target_id`, `output` FROM xag_ai WHERE `input` = %s AND `target_id` = %d ORDER BY id DESC", 'PAGE_CONTENT_TEMPLATE', $post_id
                ), ARRAY_A
            );

            if (isset($xagio_ai_results['target_id'])) {

                XAGIO_MODEL_ELEMENTOR_BACKUP::set_change_type('ai_content_template');

                $xagio_elementorData = json_decode(get_post_meta($post_id, '_elementor_data', true), true);

                // Modify
                $xagio_modifiedData = json_decode($xagio_ai_results['output'], true);

                $xagio_mergedData = XAGIO_MODEL_OCW::combineFieldsIntoJson($xagio_elementorData, $xagio_modifiedData);
                $xagio_mergedData = json_encode($xagio_mergedData);

                update_post_meta($post_id, '_elementor_data', wp_slash($xagio_mergedData));

                XAGIO_MODEL_OCW::clearElementorCache();

            }


            xagio_jsonc([
                'status'  => 'success',
                'message' => 'Successfully updated new template content'
            ]);

        }

        public static function getAiContent()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['post_id']) || !isset($_POST['title']) || !isset($_POST['description']) || !isset($_POST['h1']) || !isset($_POST['content_style']) || !isset($_POST['content_tone'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $post_id = intval($_POST['post_id']);

            $title       = sanitize_text_field(wp_unslash($_POST['title']));
            $xagio_description = sanitize_text_field(wp_unslash($_POST['description']));
            $h1          = sanitize_text_field(wp_unslash($_POST['h1']));
            $style       = sanitize_text_field(wp_unslash($_POST['content_style']));
            $tone        = sanitize_text_field(wp_unslash($_POST['content_tone']));
            $prompt_id   = intval($_POST['prompt_id']);
            $xagio_input       = "PAGE_CONTENT";

            // Check if AI request is already made
            if (self::getPageStatusAi($post_id, $xagio_input) === 'running') {
                xagio_jsonc([
                    'status'  => 'error',
                    'message' => 'AI request is already made for this page.'
                ]);
            }

            $xagio_http_code = 0;
            $xagio_result    = self::_sendAiRequest(
                $xagio_input, $prompt_id, $post_id, [
                $title,
                $xagio_description,
                $h1,
                $style,
                $tone
            ], ['post_id' => $post_id], $xagio_http_code
            );

            if ($xagio_http_code == 406) {
                xagio_jsonc([
                    'status'  => 'upgrade',
                    'message' => 'Upgrade your account to use AI features.'
                ]);
            }

            xagio_jsonc([
                'status'  => ($xagio_http_code == 200) ? 'success' : 'error',
                'message' => $xagio_result['message']
            ]);
        }

        public static function getAiSchema()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['post_id']) || !isset($_POST['seo_title_profiles']) || !isset($_POST['seo_desc_profiles']) || !isset($_POST['post_title_profiles']) || !isset($_POST['schema'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $post_id = intval($_POST['post_id']);

            $h1          = sanitize_text_field(wp_unslash($_POST['post_title_profiles']));
            $title       = sanitize_text_field(wp_unslash($_POST['seo_title_profiles']));
            $xagio_description = sanitize_text_field(wp_unslash($_POST['seo_desc_profiles']));
            $post_url    = sanitize_url(wp_unslash($_POST['post_url_profiles']));
            $logo        = sanitize_url(wp_unslash($_POST['logo_profiles']));
            $image       = sanitize_url(wp_unslash($_POST['image_profiles']));
            $phone       = sanitize_text_field(wp_unslash($_POST['business_phone']));
            $address     = sanitize_text_field(wp_unslash($_POST['business_address']));
            $city        = sanitize_text_field(wp_unslash($_POST['business_city']));
            $state       = sanitize_text_field(wp_unslash($_POST['business_state']));
            $xagio_country     = sanitize_text_field(wp_unslash($_POST['business_country']));
            $facebook    = sanitize_text_field(wp_unslash($_POST['facebook']));
            $youtube     = sanitize_text_field(wp_unslash($_POST['youtube']));
            $instagram   = sanitize_text_field(wp_unslash($_POST['instagram']));
            $linkedin    = sanitize_text_field(wp_unslash($_POST['linkedin']));
            $x           = sanitize_text_field(wp_unslash($_POST['x']));
            $tiktok      = sanitize_text_field(wp_unslash($_POST['tiktok']));
            $pinterest   = sanitize_text_field(wp_unslash($_POST['pinterest']));

            $schema_type = sanitize_text_field(wp_unslash($_POST['schema']));
            $prompt_id   = intval($_POST['prompt_id']);
            $xagio_input       = "SCHEMA";

            $unset_keys = [
                'post_title_profiles',
                'seo_title_profiles',
                'seo_desc_profiles',
                'post_url_profiles',
                'logo_profiles',
                'image_profiles',
                'business_phone',
                'business_address',
                'business_city',
                'business_state',
                'business_country',
                'facebook',
                'youtube',
                'instagram',
                'linkedin',
                'x',
                'tiktok',
                'pinterest',
                'schema',
                'action',
                'post_id',
                'prompt_id',
                '_xagio_nonce'
            ];

            foreach ($unset_keys as $xagio_key) {
                unset($_POST[$xagio_key]);
            }

            $xagio_profiles            = $_POST;
            $other_profiles_data = "";

            foreach ($xagio_profiles as $xagio_key => $xagio_value) {
                if (empty($xagio_value))
                    continue;
                $xagio_profile_name        = str_replace("_", " ", $xagio_key);
                $other_profiles_data .= "$xagio_profile_name: $xagio_value | ";
            }

            // Check if AI request is already made
            if (self::getPageStatusAi($post_id, $xagio_input) === 'running') {
                self::removeAiRequest($post_id, $xagio_input);
            }

            $xagio_args = [
                $title,
                $xagio_description,
                $h1,
                $schema_type
            ];

            if ($prompt_id == 10) {
                $xagio_args = [
                    $schema_type,
                    $h1,
                    $title,
                    $xagio_description,
                    $post_url,
                    $logo,
                    $image,
                    $phone,
                    $address,
                    $city,
                    $state,
                    $xagio_country,
                    $facebook,
                    $youtube,
                    $tiktok,
                    $linkedin,
                    $instagram,
                    $x,
                    $pinterest,
                    $other_profiles_data
                ];
            }

            $xagio_http_code = 0;
            $xagio_result    = self::_sendAiRequest(
                $xagio_input, $prompt_id, $post_id, $xagio_args, ['post_id' => $post_id], $xagio_http_code
            );

            if ($xagio_http_code == 406) {
                xagio_jsonc([
                    'status'  => 'upgrade',
                    'message' => 'Upgrade your account to use AI features.'
                ]);
            }

            xagio_jsonc([
                'status'  => ($xagio_http_code == 200) ? 'success' : 'error',
                'message' => $xagio_result['message']
            ]);
        }

        public static function getAiOutput()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['target_id']) || !isset($_POST['input'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // Sanitize and validate input
            $target_id = intval($_POST['target_id']);
            $xagio_input     = sanitize_text_field(wp_unslash($_POST['input']));

            // Get the status of the AI request
            $status = self::getPageStatusAi($target_id, $xagio_input);

            global $wpdb;

            // Prepare the query to find the most recent 'running' entry
            $failed_check = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT `id`, `date_created` FROM `xag_ai` WHERE `target_id` = %d AND `input` = %s AND `status` = 'running' ORDER BY `id` DESC", $target_id, $xagio_input
                ), ARRAY_A
            );

            // Check if the request has been running for more than 30 minutes
            $xagio_timestamp           = strtotime($failed_check['date_created'] ?? '');
            $future_date_created = $xagio_timestamp + (30 * 60); // 30 minutes later
            $currentTime         = time();

            if ($currentTime > $future_date_created && $xagio_timestamp) {
                // If more than 30 minutes have passed, mark the request as failed
                $wpdb->update(
                    'xag_ai', [
                    'status' => 'failed',
                    'output' => 'Failed to generate, please try again.'
                ], [
                        'id'        => $failed_check['id'] ?? 0,
                        'target_id' => $target_id,
                        'input'     => $xagio_input,
                    ]
                );
            }

            if ($status === false) {
                // If not in the queue
                xagio_jsonc(['status' => 'none']);
            } elseif ($status === 'completed' || $status === 'failed') {
                // If the request is completed or failed, retrieve the output
                $xagio_output = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT `id`, `output` FROM `xag_ai` WHERE `target_id` = %d AND `input` = %s ORDER BY `id` DESC", $target_id, $xagio_input
                    ), ARRAY_A
                );
                $id     = $xagio_output['id'];

                switch ($xagio_input) {
                    case 'PAGE_CONTENT':
                        $xagio_output = stripslashes($xagio_output['output']);
                        break;
                    case 'SEO_SUGGESTIONS_MAIN_KW':
                    case 'SEO_SUGGESTIONS':
                        $xagio_output['output'] = str_replace('\n', "\n", $xagio_output['output']);
                        $xagio_output['output'] = stripslashes($xagio_output['output']);
                        $xagio_output           = json_decode($xagio_output['output'], true);
                        break;
                }

                xagio_jsonc([
                    'status' => 'completed',
                    'data'   => $xagio_output,
                    'id'     => $id
                ]);
            } else {
                // If the request is still running
                xagio_jsonc(['status' => 'running']);
            }
        }


        /**
         *  AI API results
         */
        public static function aiResults($request)
        {
            if (empty($request->get_param('input'))) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // PAGE_CONTENT | SEO_SUGGESTIONS
            $xagio_input = wp_kses_post(wp_unslash($request->get_param('input')));

            if (method_exists('XAGIO_MODEL_AI', 'xagio_ai_' . $xagio_input)) {
                call_user_func([
                    'XAGIO_MODEL_AI',
                    'xagio_ai_' . $xagio_input
                ], $request);
            }
        }

	        public static function xagio_ai_SCHEMA($request)
        {
            global $wpdb;

            if (empty($request->get_param('post_id')) || empty($request->get_param('output'))) {
                if ($request->get_param('return') !== true) {
                    wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
                } else {
                    return;
                }
            }

            $post_id = intval($request->get_param('post_id'));
            $xagio_output  = sanitize_text_field(wp_unslash($request->get_param('output')));

            if ($xagio_output == 'FAILED') {
                $wpdb->update('xag_ai', [
                    'status'       => 'failed',
                    'output'       => 'Failed to generate schema, please try again.',
                    'date_updated' => gmdate('Y-m-d H:i:s')
                ], [
                    'status'    => 'running',
                    'target_id' => $post_id,
                    'input'     => 'SCHEMA',
                ]);
                if ($request->get_param('return') !== true) {
                    xagio_json('error', 'Schema failed to generate.');
                } else {
                    return;
                }
            }

            // remove escape characters
            $xagio_output = trim(preg_replace('/\\\\/', '', $xagio_output));

            // if $xagio_output contains string "Content:", split by it and use the second part
            $xagio_output = str_replace('```json', '', $xagio_output);
            $xagio_output = str_replace('```', '', $xagio_output);

            // try to convert to array
            $xagio_output = self::safeJsonDecode($xagio_output, TRUE);

            if (!$xagio_output) {
                $wpdb->update('xag_ai', [
                    'status'       => 'failed',
                    'output'       => 'Failed to generate proper JSON schema, please try again.',
                    'date_updated' => gmdate('Y-m-d H:i:s')
                ], [
                    'status'    => 'running',
                    'target_id' => $post_id,
                    'input'     => 'SCHEMA',
                ]);
                if ($request->get_param('return') !== true) {
                    xagio_json('error', 'Schema failed to generate.');
                } else {
                    return;
                }
            }

            if (!defined('XAGIO_DOMAIN') || XAGIO_DOMAIN === '') {
                if ($request->get_param('return') !== true) {
                    xagio_json('error', 'General Error');
                } else {
                    return;
                }
            }

            XAGIO_API::apiRequest('schema_wizard', 'POST', [
                'domain'  => XAGIO_DOMAIN,
                'schema'  => serialize($xagio_output),
                'name'    => 'AI for ' . get_the_title($post_id) . ', ' . gmdate('Y-m-d H:i:s'),
                'post_id' => $post_id,
            ]);

            $wpdb->update('xag_ai', [
                'status'       => 'completed',
                'output'       => wp_json_encode($xagio_output, JSON_PRETTY_PRINT),
                'date_updated' => gmdate('Y-m-d H:i:s')
            ], [
                'status'    => 'running',
                'target_id' => $post_id,
                'input'     => 'SCHEMA',
            ]);

            if ($request->get_param('return') !== true) {
                xagio_json('success', 'Schema generated successfully.');
            } else {
                return;
            }

        }

        public static function xagio_ai_TEXT_CONTENT($request)
        {
            global $wpdb;

            if (empty($request->get_param('output')) || empty($request->get_param('post_id')) || empty($request->get_param('data_id')) || empty($request->get_param('page_type'))) {
                if ($request->get_param('return') !== true) {
                    wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
                } else {
                    return;
                }
            }

            $post_id   = intval($request->get_param('post_id'));
            $data_id   = sanitize_text_field($request->get_param('data_id'));
            $page_type = sanitize_text_field($request->get_param('page_type'));
            $xagio_output    = wp_kses_post(wp_unslash($request->get_param('output')));

            // Optional for Gutenberg/Kadence: class of specific inner element (e.g. kt-blocks-info-box-title)
            $sub_target = $request->get_param('sub_target');
            $sub_target = is_string( $sub_target ) ? sanitize_text_field( wp_unslash( $sub_target ) ) : null;

            if ($xagio_output == 'FAILED') {

                $wpdb->query(
                    $wpdb->prepare(
                        "
        UPDATE xag_ai
        SET status = %s,
            output = %s,
            date_updated = %s
        WHERE status = 'running'
          AND target_id = %d
          AND input = 'TEXT_CONTENT'
          AND settings LIKE %s
    ", 'failed', 'Failed to generate content, please try again.', gmdate('Y-m-d H:i:s'), $post_id, "%$data_id%"
                    )
                );

                if ($request->get_param('return') !== true) {
                    wp_send_json(['error' => 'Failed']);
                } else {
                    return;
                }
            }

            $xagio_result = $wpdb->query(
                $wpdb->prepare(
                    "
    UPDATE xag_ai
    SET status = %s,
        output = %s,
        date_updated = %s
    WHERE status = 'running'
      AND target_id = %d
      AND input = 'TEXT_CONTENT'
      AND settings LIKE %s
", 'completed', $xagio_output, gmdate('Y-m-d H:i:s'), $post_id, "%$data_id%"
                )
            );


            if ($page_type == 'elementor') {

                XAGIO_MODEL_ELEMENTOR_BACKUP::set_change_type('text_content');

                $xagio_elementor_data = get_post_meta($post_id, '_elementor_data', TRUE);
                if (!is_array($xagio_elementor_data)) {
                    $xagio_elementor_data = json_decode($xagio_elementor_data, true);
                }

                self::elementorReplaceTextById($xagio_elementor_data, $data_id, $xagio_output);

                $xagio_elementor_data = json_encode($xagio_elementor_data);

                update_post_meta($post_id, '_elementor_data', wp_slash($xagio_elementor_data));

                XAGIO_MODEL_OCW::clearElementorCache();

            } else {

                self::update_kadence_text_by_unique_id(
                    $post_id,
                    (string) $data_id,  // comes from JS getGutenbergDataId(...)
                    (string) $xagio_output,
                    $sub_target
                );
            }

            if ($request->get_param('return') !== true) {
                wp_send_json(['data' => $xagio_result]);
            } else {
                return;
            }

        }

        public static function xagio_ai_CLUSTER($request)
        {
            global $wpdb;

            /* ──────────────────────────── 1. sanity check ──────────────────────────── */
            if (empty($request->get_param('project_id')) || empty($request->get_param('output'))) {
                if ($request->get_param('return') !== true) {
                    wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
                }
                return;
            }

            $project_id  = intval($request->get_param('project_id'));
            $output_raw  = wp_unslash($request->get_param('output'));
            $output_safe = wp_kses_post($output_raw);

            /* ───────────────────── 3. clone the project (backup) ──────────────────── */
            $orig_proj = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT project_name, status, shared, date_created
             FROM xag_projects
             WHERE id = %d", $project_id
                ), ARRAY_A
            );
            if (!$orig_proj) {
                if ($request->get_param('return') !== true) {
                    wp_die('Original project not found.', 'Backup Error', ['response' => 500]);
                }
                return;
            }
            $backup_name = $orig_proj['project_name'] . ' - AI Clustering Backup';
            $wpdb->insert('xag_projects', [
                'project_name' => $backup_name,
                'status'       => $orig_proj['status'],
                'shared'       => $orig_proj['shared'],
                'date_created' => $orig_proj['date_created'],
            ]);
            $backup_project_id = $wpdb->insert_id;

            /* ─────── 3a. copy every group/keyword to the backup project ─────── */
            $old_groups = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM xag_groups WHERE project_id = %d", $project_id), ARRAY_A
            );
            foreach ($old_groups as $g) {
                $old_gid = $g['id'];
                unset($g['id']);
                $g['project_id'] = $backup_project_id;
                $wpdb->insert('xag_groups', $g);
                $backup_gid = $wpdb->insert_id;

                $kws = $wpdb->get_results(
                    $wpdb->prepare("SELECT * FROM xag_keywords WHERE group_id = %d", $old_gid), ARRAY_A
                );
                foreach ($kws as $kw) {
                    unset($kw['id']);
                    $kw['group_id'] = $backup_gid;
                    $wpdb->insert('xag_keywords', $kw);
                }
            }

            /* ──────────────────────────── 4. parse AI output ───────────────────────── */
            $groups = [];
            $cur    = null;
            foreach (preg_split("/\r\n|\n|\r/", $output_raw) as $line) {
                $line = trim($line);
                if ($line === '')
                    continue;
                if (stripos($line, 'Group name:') === 0) {
                    $cur          = trim(substr($line, strlen('Group name:')));
                    $groups[$cur] = [];
                } elseif ($cur) {
                    $groups[$cur][] = $line;
                }
            }

            /* ─────────── 5. delete originals & build fresh clusters ─────────── */
            // collect original IDs so we can wipe them
            $old_ids = $wpdb->get_col(
                $wpdb->prepare("SELECT id FROM xag_groups WHERE project_id = %d", $project_id)
            );
            $in_old  = !empty($old_ids) ? implode(',', array_map('intval', $old_ids)) : '0';

            $wpdb->query('START TRANSACTION');
            if ($in_old !== '0') {
                $placeholders = implode(',', array_fill(0, count($old_ids), '%d'));

                $wpdb->query( $wpdb->prepare( "DELETE FROM xag_keywords WHERE group_id IN ($placeholders)", $old_ids ) );
                $wpdb->query( $wpdb->prepare( "DELETE FROM xag_groups WHERE id IN ($placeholders)", $old_ids ) );
            }

            /* ──────── 6. insert new groups, fetching metrics live per kw ──────── */
            foreach ($groups as $g_name => $kw_list) {
                $wpdb->insert('xag_groups', [
                    'project_id'   => $project_id,
                    'group_name'   => $g_name,
                    'date_created' => current_time('mysql'),
                ]);
                $new_gid = $wpdb->insert_id;

                foreach ($kw_list as $kw) {
                    // grab metrics directly from the backup project
                    $metric = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT k.volume, k.cpc, k.inurl, k.intitle, k.rank
                     FROM xag_keywords k
                     INNER JOIN xag_groups g ON k.group_id = g.id
                     WHERE g.project_id = %d AND k.keyword = %s
                     LIMIT 1", $backup_project_id, $kw
                        ), ARRAY_A
                    );
                    if (!$metric) {
                        $metric = [
                            'volume'  => '',
                            'cpc'     => '',
                            'inurl'   => '',
                            'intitle' => '',
                            'rank'    => ''
                        ];
                    }

                    $wpdb->insert('xag_keywords', [
                        'group_id'     => $new_gid,
                        'keyword'      => $kw,
                        'volume'       => $metric['volume'],
                        'cpc'          => $metric['cpc'],
                        'inurl'        => $metric['inurl'],
                        'intitle'      => $metric['intitle'],
                        'date_created' => current_time('mysql'),
                        'position'     => 999,
                        'queued'       => 0,
                        'rank'         => $metric['rank'],
                    ]);
                }
            }
            $wpdb->query('COMMIT');

            /* ─────────────────────────── 7. finish AI job ─────────────────────────── */
            $wpdb->update('xag_ai', [
                'status'       => 'completed',
                'output'       => $output_safe,
                'date_updated' => gmdate('Y-m-d H:i:s'),
            ], [
                'status'    => 'running',
                'target_id' => $project_id,
                'input'     => 'CLUSTER',
            ]);

            if ($request->get_param('return') !== true) {
                wp_send_json(['data' => true]);
            }
        }


        public static function xagio_ai_PAGE_CONTENT($request)
        {
            global $wpdb;

            if (empty($request->get_param('post_id')) || empty($request->get_param('output'))) {
                if ($request->get_param('return') !== true) {
                    wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
                } else {
                    return;
                }
            }

            $post_id = intval($request->get_param('post_id'));
            $xagio_output  = wp_kses_post(wp_unslash($request->get_param('output')));

            if ($xagio_output == 'FAILED') {
                $wpdb->update('xag_ai', [
                    'status'       => 'failed',
                    'output'       => 'Failed to generate content, please try again.',
                    'date_updated' => gmdate('Y-m-d H:i:s')
                ], [
                    'status'    => 'running',
                    'target_id' => $post_id,
                    'input'     => 'PAGE_CONTENT',
                ]);
                if ($request->get_param('return') !== true) {
                    wp_send_json(['error' => 'Failed']);
                } else {
                    return;
                }
            }

            // if $xagio_output contains string "Content:", split by it and use the second part
            if (strpos($xagio_output, 'Content:') !== false) {
                $xagio_output = explode('Content:', $xagio_output)[1];
            }

            // if you add esc_sql on output we will have problems with \n
            $xagio_output = $wpdb->update('xag_ai', [
                'status'       => 'completed',
                'output'       => $xagio_output,
                'date_updated' => gmdate('Y-m-d H:i:s')
            ], [
                'status'    => 'running',
                'target_id' => $post_id,
                'input'     => 'PAGE_CONTENT',
            ]);

            if ($request->get_param('return') !== true) {
                wp_send_json(['data' => $xagio_output]);
            } else {
                return;
            }

        }

        public static function xagio_ai_PAGE_CONTENT_TEMPLATE($request)
        {
            global $wpdb;

            if (empty($request->get_param('post_id')) || empty($request->get_param('output'))) {
                if ($request->get_param('return') !== true) {
                    wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
                } else {
                    return;
                }

            }

            $post_id = intval($request->get_param('post_id'));
            $xagio_output  = wp_kses_post(wp_unslash($request->get_param('output')));

            if ($xagio_output == 'FAILED') {
                $wpdb->update('xag_ai', [
                    'status'       => 'failed',
                    'output'       => 'Failed to generate content, please try again.',
                    'date_updated' => gmdate('Y-m-d H:i:s')
                ], [
                    'status'    => 'running',
                    'target_id' => $post_id,
                    'input'     => 'PAGE_CONTENT_TEMPLATE',
                ]);
                if ($request->get_param('return') !== true) {
                    wp_send_json(['error' => 'Failed']);
                } else {
                    return;
                }
            }

            // if $xagio_output contains string "Content:", split by it and use the second part
            $xagio_output = str_replace('```json', '', $xagio_output);
            $xagio_output = str_replace('```', '', $xagio_output);

            // if you add esc_sql on output we will have problems with \n
            $xagio_output = $wpdb->update('xag_ai', [
                'status'       => 'completed',
                'output'       => $xagio_output,
                'date_updated' => gmdate('Y-m-d H:i:s')
            ], [
                'status'    => 'running',
                'target_id' => $post_id,
                'input'     => 'PAGE_CONTENT_TEMPLATE',
            ]);

            if ($request->get_param('return') !== true) {
                wp_send_json(['data' => $xagio_output]);
            } else {
                return;
            }

        }

        public static function xagio_ai_SEO_SUGGESTIONS($request)
        {
            global $wpdb;

            if (empty($request->get_param('group_id')) || empty($request->get_param('output'))) {
                if ($request->get_param('return') !== true) {
                    wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
                } else {
                    return;
                }

            }

            $group_id = intval($request->get_param('group_id'));

            // This is array output from AI API
            $xagio_output = sanitize_text_field(wp_unslash($request->get_param('output')));

            // remove escape characters
            $xagio_output         = trim(preg_replace('/\\\\/', '', $xagio_output));
            $xagio_decoded_output = json_decode($xagio_output, true);

            $xagio_output = $wpdb->update('xag_ai', [
                'status'       => 'completed',
                'output'       => esc_sql(wp_json_encode($xagio_decoded_output, JSON_PRETTY_PRINT)),
                'date_updated' => gmdate('Y-m-d H:i:s')
            ], [
                'status'    => 'running',
                'target_id' => $group_id,
                'input'     => 'SEO_SUGGESTIONS',
            ]);

            if ($request->get_param('return') !== true) {
                wp_send_json(['data' => $xagio_output]);
            } else {
                return;
            }

        }

        public static function xagio_ai_SEO_SUGGESTIONS_MAIN_KW($request)
        {
            global $wpdb;

            if (empty($request->get_param('group_id')) || empty($request->get_param('output'))) {
                if ($request->get_param('return') !== true) {
                    wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
                } else {
                    return;
                }

            }

            $group_id = intval($request->get_param('group_id'));

            // This is array output from AI API
            $xagio_output = sanitize_text_field(wp_unslash($request->get_param('output')));

            // remove escape characters
            $xagio_output         = trim(preg_replace('/\\\\/', '', $xagio_output));
            $xagio_decoded_output = json_decode($xagio_output, true);

            $xagio_output = $wpdb->update('xag_ai', [
                'status'       => 'completed',
                'output'       => esc_sql(wp_json_encode($xagio_decoded_output, JSON_PRETTY_PRINT)),
                'date_updated' => gmdate('Y-m-d H:i:s')
            ], [
                'status'    => 'running',
                'target_id' => $group_id,
                'input'     => 'SEO_SUGGESTIONS_MAIN_KW',
            ]);

            if ($request->get_param('return') !== true) {
                wp_send_json(['data' => $xagio_output]);
            } else {
                return;
            }

        }

        public static function xagio_ai_IMAGE_GEN($request)
        {
            global $wpdb;

            // 0) Load WP media/image APIs
            if (!function_exists('wp_generate_attachment_metadata')) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
            }

            $post_id       = intval($request->get_param('target_id'));
            $attachment_id = intval($request->get_param('attachment_id'));
            $xagio_output        = sanitize_url(wp_unslash($request->get_param('output')));
            $page_type     = sanitize_text_field($request->get_param('page_type'));

            // Mark AI Job as completed
            $marked = self::markAiImageJobAsComplete('IMAGE_GEN', $post_id, $attachment_id, $xagio_output);

            if ( !$marked ) {
                // Someone else already processed it, or the row didn't match (bad attachment_id/target_id)
                return;
            }

            // ——— 1) NEW-ATTACHMENT + PAGE-ONLY REPLACEMENT ———
            if ($post_id > 0) {
                if (empty($xagio_output)) {
                    if ($request->get_param('return') !== true) {
                        wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
                    }
                    return;
                }

                // 1a) Sideload remote AI image into the Media Library, attached to $post_id
                $new_id = media_sideload_image($xagio_output, $post_id, null, 'id');
                if (is_wp_error($new_id)) {
                    if ($request->get_param('return') !== true) {
                        wp_send_json(['error' => 'Failed to sideload generated image.'], 500);
                    }
                    return;
                }
                $new_url      = wp_get_attachment_url($new_id);
                $original_url = $xagio_output;

                // 1c) Replace only on that page/post
                if ($page_type == 'elementor') {

                    if (class_exists('\Elementor\Plugin') && did_action('elementor/loaded')) {
                        XAGIO_MODEL_ELEMENTOR_BACKUP::set_change_type('image_gen');

                        $xagio_elementor_data = get_post_meta($post_id, '_elementor_data', TRUE);
                        if (!is_array($xagio_elementor_data)) {
                            $xagio_elementor_data = json_decode($xagio_elementor_data, true);
                        }

                        self::elementorReplaceImageByUrl($xagio_elementor_data, $attachment_id, $new_id);

                        $xagio_elementor_data = json_encode($xagio_elementor_data);

                        update_post_meta($post_id, '_elementor_data', wp_slash($xagio_elementor_data));

                        XAGIO_MODEL_OCW::clearElementorCache();
                    } else {
                        // Fallback: simple post_content swap
                        $post = get_post($post_id);
                        if ($post && strpos($post->post_content, $original_url) !== false) {
                            $new_content = str_replace($original_url, $new_url, $post->post_content);
                            wp_update_post([
                                'ID'           => $post_id,
                                'post_content' => $new_content
                            ]);
                        }
                    }

                } else {
                    $element_id     = sanitize_text_field($request->get_param('data_id'));
                    $sub_target     = sanitize_text_field($request->get_param('sub_target'));
                    // Kadence / Gutenberg persist
                    self::persist_image_to_gutenberg( $post_id, $attachment_id, $new_id, $element_id, $sub_target );
                }


                // 1d) Return the newly-attached URL & ID
                if ($request->get_param('return') !== true) {
                    wp_send_json([
                        'data' => [
                            'url'           => $new_url,
                            'attachment_id' => $new_id,
                        ],
                    ]);
                }
                return;
            }

            // ——— 3) FALLBACK: global “new AI image” insertion ———
            if (empty($xagio_output)) {
                if ($request->get_param('return') !== true) {
                    wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
                }
                return;
            }

            $tmp_file = download_url($xagio_output);
            if (is_wp_error($tmp_file)) {
                if ($request->get_param('return') !== true) {
                    wp_send_json(['error' => 'Failed to download generated image.'], 500);
                }
                return;
            }

            $filetype = wp_check_filetype($tmp_file);
            $mime     = $filetype['type'];
            $ext      = $filetype['ext'] ?: 'png';
            $upload   = wp_upload_bits("ai-image-gen-" . uniqid() . ".{$ext}", null, file_get_contents($tmp_file));
            wp_delete_file($tmp_file);

            if (!empty($upload['error'])) {
                if ($request->get_param('return') !== true) {
                    wp_send_json(['error' => 'Could not save file to media library.'], 500);
                }
                return;
            }

            $attachment_data = [
                'post_mime_type' => $mime,
                'post_title'     => 'AI Generated Image',
                'post_content'   => '',
                'post_status'    => 'inherit',
            ];
            $new_id          = wp_insert_attachment($attachment_data, $upload['file']);
            if (is_wp_error($new_id)) {
                if ($request->get_param('return') !== true) {
                    wp_send_json(['error' => 'Could not insert attachment.'], 500);
                }
                return;
            }

            $xagio_meta = wp_generate_attachment_metadata($new_id, $upload['file']);
            wp_update_attachment_metadata($new_id, $xagio_meta);
            $xagio_url = wp_get_attachment_url($new_id);

            if ($request->get_param('return') !== true) {
                wp_send_json([
                    'data' => [
                        'url'           => $xagio_url,
                        'attachment_id' => $new_id,
                    ],
                ]);
            }
        }

        public static function xagio_ai_IMAGE_EDIT($request)
        {
            global $wpdb;

            // 0. Load WP media/image APIs
            if (!function_exists('wp_generate_attachment_metadata')) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
            }

            // Params validation
            $post_id       = intval($request->get_param('target_id'));
            $attachment_id = intval($request->get_param('attachment_id'));
            $xagio_output        = sanitize_url(wp_unslash($request->get_param('output')));

            if (!$attachment_id || !$xagio_output) {
                if ($request->get_param('return') !== true) {
                    wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
                }
                return;
            }

            // Original attachment URL
            $original_url = wp_get_attachment_url($attachment_id);

            // Mark AI job completed
            self::markAiImageJobAsComplete('IMAGE_EDIT', $post_id, $attachment_id, $xagio_output);

            //
            // === NEW-ATTACHMENT BRANCH ===
            //
            if ($post_id > 0) {
                $new_attachment_id = media_sideload_image($xagio_output, $post_id, null, 'id');
                if (is_wp_error($new_attachment_id)) {
                    if ($request->get_param('return') !== true) {
                        wp_send_json(['error' => 'Failed to sideload image.'], 500);
                    }
                    return;
                }
                $new_url = wp_get_attachment_url($new_attachment_id);

                if (class_exists('\Elementor\Plugin') && did_action('elementor/loaded')) {
                    XAGIO_MODEL_ELEMENTOR_BACKUP::set_change_type('image_edit');

                    $xagio_elementor_data = get_post_meta($post_id, '_elementor_data', TRUE);
                    if (!is_array($xagio_elementor_data)) {
                        $xagio_elementor_data = json_decode($xagio_elementor_data, true);
                    }

                    self::elementorReplaceImageByUrl($xagio_elementor_data, $attachment_id, $new_attachment_id);

                    $xagio_elementor_data = json_encode($xagio_elementor_data);

                    update_post_meta($post_id, '_elementor_data', wp_slash($xagio_elementor_data));

                    XAGIO_MODEL_OCW::clearElementorCache();
                } else {
                    $post = get_post($post_id);
                    if ($post && strpos($post->post_content, $original_url) !== false) {
                        $new_content = str_replace($original_url, $new_url, $post->post_content);
                        wp_update_post([
                            'ID'           => $post_id,
                            'post_content' => $new_content
                        ]);
                    }
                }

                if ($request->get_param('return') !== true) {
                    wp_send_json(['data' => $new_url]);
                }
                return;
            }

            //
            // === OVERWRITE-ATTACHMENT BRANCH (no resizing) ===
            //
            $tmp_file = download_url($xagio_output);
            if (is_wp_error($tmp_file)) {
                if ($request->get_param('return') !== true) {
                    wp_send_json(['error' => 'Failed to download edited image.'], 500);
                }
                return;
            }

            // Backup original file
            $file_path   = get_attached_file($attachment_id);
            $path_parts  = pathinfo($file_path);
            $backup_path = "{$path_parts['dirname']}/{$path_parts['filename']}-xag-backup.{$path_parts['extension']}";
            if (!file_exists($backup_path)) {
                copy($file_path, $backup_path);
            }

            // Replace file directly
            copy($tmp_file, $file_path);
            wp_delete_file($tmp_file);

            // Regenerate metadata & clear Elementor cache
            $new_meta = wp_generate_attachment_metadata($attachment_id, $file_path);
            if (is_wp_error($new_meta)) {
                if ($request->get_param('return') !== true) {
                    wp_send_json(['error' => 'Failed to regenerate attachment metadata.'], 500);
                }
                return;
            }
            wp_update_attachment_metadata($attachment_id, $new_meta);

            if (class_exists('\Elementor\Plugin') && did_action('elementor/loaded')) {
                try {
                    \Elementor\Plugin::instance()->files_manager->clear_cache();
                } catch (\Throwable $e) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('Xagio SEO: Failed to clear Elementor cache after AI edit: ' . $e->getMessage());
                    }
                }
            }

            // Return new URL
            $new_url = wp_get_attachment_url($attachment_id);
            if ($request->get_param('return') !== true) {
                wp_send_json(['data' => $new_url]);
            }
        }

        /**
         *  AI Helper functions
         */

        public static function markAiImageJobAsComplete($xagio_input, $post_id, $attachment_id, $xagio_output)
        {
            global $wpdb;

            $table   = 'xag_ai';
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT `id`, `settings` FROM {$table} WHERE `input` = %s AND `status` = 'running' AND `target_id` = %d", $xagio_input, $post_id
                ), ARRAY_A
            );

            if (empty($results)) {
                return false;
            }

            $updated = false;

            foreach ($results as $row) {
                $row_id   = intval($row['id']);
                $settings = json_decode($row['settings'], true);

                if (is_array($settings) && isset($settings['attachment_id']) && intval($settings['attachment_id']) === intval($attachment_id)) {
                    $wpdb->update(
                        $table, [
                        'status' => 'completed',
                        'output' => $xagio_output
                    ], [
                            'id' => $row_id
                        ]
                    );
                    $updated = true;
                }
            }

            return $updated;
        }

        public static function elementorReplaceTextById(&$elements, $targetId, $newText)
        {
            foreach ($elements as &$element) {
                // Found the matching element?
                if (isset($element['id']) && $element['id'] === $targetId) {
                    // Replace whichever text field exists
                    if (isset($element['settings']['editor'])) {
                        $element['settings']['editor'] = $newText;
                    } elseif (isset($element['settings']['title'])) {
                        $element['settings']['title'] = $newText;
                    } elseif (isset($element['settings']['text'])) {
                        $element['settings']['text'] = $newText;
                    } elseif (isset($element['settings']['description_text'])) {
	                    $element['settings']['description_text'] = $newText;
                    }
                    return true;
                }
                // Recurse into child elements
                if (!empty($element['elements']) && is_array($element['elements'])) {
                    if (self::elementorReplaceTextById($element['elements'], $targetId, $newText)) {
                        return true;
                    }
                }
            }
            return false;
        }

        public static function elementorReplaceMapAddress(&$data, $newAddress)
        {
            if (is_array($data)) {
                foreach ($data as $xagio_key => &$xagio_value) {
                    // If key matches one of our target fields, replace its value using the current index.
                    if (is_string($xagio_key) && in_array($xagio_key, ['address'])) {
                        $xagio_value = $newAddress;
                    }

                    // If the value is an array, recurse.
                    if (is_array($xagio_value)) {
                        self::elementorReplaceMapAddress($xagio_value, $newAddress);
                    }
                }
            }

            return $data;
        }


        public static function update_kadence_text_by_unique_id(int $post_id, string $data_id, string $new_text, ?string $sub_target = null ): bool {
            $post = get_post( $post_id );
            if ( ! $post ) {
                return false;
            }

            $xagio_blocks  = parse_blocks( $post->post_content );
            $changed = false;

            // pass sub_target down
            $xagio_blocks = self::kadence_replace_text_recursive(
                $xagio_blocks,
                $data_id,
                $new_text,
                $changed,
                $sub_target // <-- NEW
            );

            if ( ! $changed ) {
                return false;
            }

            $new_content = serialize_blocks( $xagio_blocks );

            wp_update_post( [
                'ID'           => $post_id,
                'post_content' => $new_content,
            ] );

            return true;
        }

        /**
         * Depth-first traversal that stops after first successful replacement.
         */
        public static function kadence_replace_text_recursive( array $xagio_blocks, string $data_id, string $new_text, bool &$changed, ?string $sub_target = null ): array {
            foreach ( $xagio_blocks as &$block ) {
                if ( $changed ) break;

                $attrs = isset($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : [];
                $uid   = isset($attrs['uniqueID']) ? (string) $attrs['uniqueID'] : '';

                // Match either exact uniqueID, or if data_id token contains it (e.g., "kt-pane10_6d0327-fb")
                $isMatch = ($uid !== '') && ( $uid === $data_id || strpos( $data_id, $uid ) !== false );

                // ✅ Try to update THIS block first
                if ( $isMatch ) {
                    if ( self::apply_text_update_for_block( $block, $new_text, $sub_target ) ) {
                        $changed = true;
                        continue; // don't touch children of a changed block
                    }
                }

                // Then recurse into children if not changed
                if (!empty( $block['innerBlocks'] )) {
                    $block['innerBlocks'] = self::kadence_replace_text_recursive( $block['innerBlocks'], $data_id, $new_text, $changed, $sub_target );
                }
            }
            return $xagio_blocks;
        }

        /**
         * Apply text update for a single block.
         * 1) Prefer obvious attrs-based text fields (text/title/content/label).
         * 2) Then try structured targets (e.g., Kadence InfoBox title/text).
         * 3) Finally, fallback to generic innerHTML/innerContent single-node text replacement.
         */
        public static function apply_text_update_for_block(array &$block, string $new_text, ?string $sub_target = null): bool
        {
            $xagio_name = isset($block['blockName']) ? $block['blockName'] : '';
            $html_text = wp_kses_post($new_text);
            $plain = wp_strip_all_tags($new_text);
            // 0) If sub_target is provided, do a surgical class-targeted replace first and stop.
            if ($sub_target) {
                // If you want plain vs html per target, you can branch here:
                $use_plain = in_array($sub_target, ['kt-blocks-info-box-title', 'kt-btn-inner-text', 'kt-svg-icon-list-text'], true);

                if (self::replace_first_selector_inner_text($block, $sub_target, $use_plain ? $plain : $html_text)) {
                    return true;
                }
                // If it fails, we continue to the usual heuristics below as a fallback.
            }
            //
            // 1) Direct attrs (text/title/content/label)
            foreach (['text', 'title', 'content', 'label'] as $xagio_k) {
                if (array_key_exists($xagio_k, $block['attrs'] ?? [])) {
                    $block['attrs'][$xagio_k] = ($xagio_k === 'content') ? $html_text : $plain;
                    return true;
                }
            }
            // 2) Block-specific (InfoBox)
            if ($xagio_name === 'kadence/infobox') {
                if (self::replace_first_selector_inner_text($block, 'kt-blocks-info-box-title', $plain)) return true;
                if (self::replace_first_selector_inner_text($block, 'kt-blocks-info-box-text', $html_text)) return true;
            }
            // 3) AdvancedHeading / Core paragraphs/headings
            if ($xagio_name === 'kadence/advancedheading' || in_array($xagio_name, ['core/paragraph', 'core/heading'], true)) {
                if (self::replace_main_text_node($block, $html_text)) return true;
            }
            // 4) Fallback
            return self::replace_main_text_node($block, $html_text);
        }

        /**
         * Replace text inside the first element that has a class token (e.g., 'kt-blocks-info-box-title').
         * Keeps the tag and attributes, overwrites inner HTML with $new_text once.
         */
        public static function replace_first_selector_inner_text( array &$block, string $classToken, string $new_text ): bool {
            $pattern = '/(<([a-zA-Z0-9:-]+)[^>]*class="[^"]*\b' . preg_quote($classToken, '/') . '\b[^"]*"[^>]*>)(.*?)(<\/\2>)/su';

            // A) Prefer surgical edit inside innerContent parts (preserves null placeholders)
            if (isset($block['innerContent']) && is_array($block['innerContent'])) {
                foreach ($block['innerContent'] as $xagio_i => $part) {
                    if (!is_string($part) || $part === '') continue;

                    $new = preg_replace($pattern, '$1' . $new_text . '$4', $part, 1, $count);
                    if ($new !== null && $count > 0 && $new !== $part) {
                        $block['innerContent'][$xagio_i] = $new;
                        // Do NOT touch innerBlocks; structure is preserved
                        // Also, if innerHTML exists, keep it in sync if and only if it exactly equals the joined innerContent.
                        if (isset($block['innerHTML']) && is_string($block['innerHTML'])) {
                            // Rebuild a preview of what innerHTML would be if it mirrored innerContent
                            $joined = implode('', array_map(static function ($xagio_p) {
                                return is_string($xagio_p) ? $xagio_p : '';
                            }, $block['innerContent']));
                            // Only sync when innerHTML used to mirror innerContent (avoid blowing away templates that differ)
                            // If you want strict sync, you can assign $block['innerHTML'] = $joined;
                        }
                        return true;
                    }
                }
            }

            // B) Fallback: work on the collected full HTML
            $html = self::collect_block_html($block);
            if ($html === '') return false;

            $new = preg_replace($pattern, '$1' . $new_text . '$4', $html, 1);
            if ($new !== null && $new !== $html) {
                self::push_block_html($block, $new);
                return true;
            }
            return false;
        }

        /**
         * Replace the main text node between the first pair of tags in innerHTML/innerContent.
         * E.g. '<h2>Old</h2>' → '<h2>NEW</h2>'
         */
        public static function replace_main_text_node( array &$block, string $new_text ): bool {
            $html = self::collect_block_html( $block );
            if ( $html === '' ) {
                return false;
            }

            $new = preg_replace( '/>([^<]*)</u', '>' . $new_text . '<', $html, 1 );
            if ( $new !== null && $new !== $html ) {
                self::push_block_html( $block, $new );
                return true;
            }
            return false;
        }

        /**
         * Collect the block's HTML as a single string from innerHTML or innerContent.
         */
        public static function collect_block_html( array $block ): string {
            if ( isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ) {
                return $block['innerHTML'];
            }
            if ( isset( $block['innerContent'] ) && is_array( $block['innerContent'] ) ) {
                return implode( '', array_map( static function( $xagio_p ) { return is_string( $xagio_p ) ? $xagio_p : ''; }, $block['innerContent'] ) );
            }
            return '';
        }

        /**
         * Push the mutated HTML back into the block (prefer innerHTML, else innerContent[0]).
         */
        public static function push_block_html( array &$block, string $html ): void {
            if ( isset( $block['innerHTML'] ) && is_string( $block['innerHTML'] ) ) {
                $block['innerHTML'] = $html;
            }
            if ( isset( $block['innerContent'] ) && is_array( $block['innerContent'] ) ) {
                $block['innerContent'] = [ $html ];
            }
        }

        public static function replace_kadence_pane_title_html( string $html, string $newTitle ): array {
            // returns [bool $changed, string $newHtml, string $reason]
            if (trim($html) === '') return [false, $html, 'empty_html'];

            // Wrap fragment to make DOMDocument happy
            $frag = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>';

            $prev = libxml_use_internal_errors(true);
            $doc  = new DOMDocument();
            // DO NOT add formatting that may reflow whitespace/zeros
            $loaded = $doc->loadHTML($frag, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();
            libxml_use_internal_errors($prev);

            if (!$loaded) return [false, $html, 'load_failed'];

            $xpath = new DOMXPath($doc);

            // Find the first accordion title span
            $titleNodes = $xpath->query("//span[contains(concat(' ', normalize-space(@class), ' '), ' kt-blocks-accordion-title ')]");
            if ($titleNodes && $titleNodes->length) {
                /** @var DOMElement $n */
                $n = $titleNodes->item(0);

                // Remove ANY child nodes then set text exactly
                while ($n->firstChild) $n->removeChild($n->firstChild);
                $n->appendChild($doc->createTextNode($newTitle));

                // Extract body innerHTML back
                $body = $doc->getElementsByTagName('body')->item(0);
                $new  = '';
                foreach ($body->childNodes as $child) {
                    $new .= $doc->saveHTML($child);
                }
                return [$new !== $html, $new, 'replaced_title_span'];
            }

            // Fallback: find the accordion button and replace JUST its title text portion.
            // The safe approach: locate span wrapper if present; if not, create one.
            $btnNodes = $xpath->query("//button[contains(concat(' ', normalize-space(@class), ' '), ' kt-blocks-accordion-header ')]");
            if ($btnNodes && $btnNodes->length) {
                /** @var DOMElement $btn */
                $btn = $btnNodes->item(0);

                // Try to find title-wrap span and title span again (sometimes markup differs)
                $wrap = null;
                $wrapNodes = $xpath->query(".//span[contains(concat(' ', normalize-space(@class), ' '), ' kt-blocks-accordion-title-wrap ')]", $btn);
                if ($wrapNodes && $wrapNodes->length) {
                    $wrap = $wrapNodes->item(0);
                } else {
                    // create wrapper to be safe, keep icons etc.
                    $wrap = $doc->createElement('span');
                    $wrap->setAttribute('class', 'kt-blocks-accordion-title-wrap');
                    // Insert as the first element in the button (before icon trigger, etc.)
                    $btn->insertBefore($wrap, $btn->firstChild);
                }

                $title = null;
                $titleNodes2 = $xpath->query(".//span[contains(concat(' ', normalize-space(@class), ' '), ' kt-blocks-accordion-title ')]", $wrap);
                if ($titleNodes2 && $titleNodes2->length) {
                    $title = $titleNodes2->item(0);
                } else {
                    $title = $doc->createElement('span');
                    $title->setAttribute('class', 'kt-blocks-accordion-title');
                    $wrap->appendChild($title);
                }

                while ($title->firstChild) $title->removeChild($title->firstChild);
                $title->appendChild($doc->createTextNode($newTitle));

                $body = $doc->getElementsByTagName('body')->item(0);
                $new  = '';
                foreach ($body->childNodes as $child) {
                    $new .= $doc->saveHTML($child);
                }
                return [$new !== $html, $new, 'replaced_button_fallback'];
            }

            return [false, $html, 'no_button_no_title_span'];
        }

        private static function xagio_normalize_uploads_url($image_url, $uploads_baseurl)
        {
            if (!is_string($image_url) || $image_url === '') {
                return $image_url;
            }

            $image_url = trim($image_url);

            // If protocol-relative, add scheme from uploads base
            if (strpos($image_url, '//') === 0) {
                $scheme = wp_parse_url($uploads_baseurl, PHP_URL_SCHEME) ?: 'https';
                $image_url = $scheme . ':' . $image_url;
            }

            // If it's already absolute, we’re done
            if (preg_match('#^https?://#i', $image_url)) {
                return $image_url;
            }

            // Normalize base pieces
            $uploads_baseurl = trailingslashit($uploads_baseurl); // .../uploads/

            // If starts with '/wp-content/uploads' or 'wp-content/uploads', attach to uploads base
            $uploads_needles = [
                '/wp-content/uploads/',   // leading slash
                'wp-content/uploads/',    // no leading slash
                '/wp-content/uploads',    // missing trailing slash
                'wp-content/uploads',     // missing both
            ];

            foreach ($uploads_needles as $needle) {
                if (stripos($image_url, $needle) === 0) {
                    // ensure we only append the part AFTER '/wp-content/uploads/'
                    $pos = stripos($image_url, '/wp-content/uploads/');
                    if ($pos !== false) {
                        $suffix = substr($image_url, $pos + strlen('/wp-content/uploads/'));
                        // guard against accidental leading slash in suffix
                        $suffix = ltrim($suffix, '/');
                        return $uploads_baseurl . $suffix; // absolute URL in current site
                    }

                    // handle cases like 'wp-content/uploads/file.png' (no leading slash and no explicit needle with slash)
                    $pos2 = stripos($image_url, 'wp-content/uploads/');
                    if ($pos2 !== false) {
                        $suffix = substr($image_url, $pos2 + strlen('wp-content/uploads/'));
                        $suffix = ltrim($suffix, '/');
                        return $uploads_baseurl . $suffix;
                    }
                }
            }

            // Not an uploads path—return as-is
            return $image_url;
        }

        public static function persist_image_to_gutenberg( int $post_id, int $old_id, int $new_id, string $element_id = '', string $sub_target = '' ) : bool {


            // Resolve new/old URLs (old can be 0/empty if we only target by element_id)
            $old_url = $old_id ? ( wp_get_attachment_url( $old_id ) ?: '' ) : '';
            $new_url = $new_id ? ( wp_get_attachment_url( $new_id ) ?: '' ) : '';
            if ( ! $new_id || ! $new_url ) return false;

            $new_rel = wp_make_link_relative( $new_url );
            $new_src = $new_rel !== '' ? $new_rel : $new_url;

            $xagio_meta = wp_get_attachment_metadata( $new_id );
            if ( empty( $xagio_meta ) || empty( $xagio_meta['sizes']['thumbnail'] ) || empty( $xagio_meta['sizes']['medium'] ) ) {
                // Regenerate only missing subsizes (faster than full generate)
                if ( function_exists('wp_update_image_subsizes') ) {
                    wp_update_image_subsizes( $new_id );
                } else {
                    // Older WP fallback
                    $xagio_file = get_attached_file( $new_id );
                    if ( $xagio_file && file_exists( $xagio_file ) ) {
                        $xagio_meta = wp_generate_attachment_metadata( $new_id, $xagio_file );
                        if ( $xagio_meta ) wp_update_attachment_metadata( $new_id, $xagio_meta );
                    }
                }
            }

            $post = get_post( $post_id );
            if ( ! $post ) return false;

            $xagio_content = (string) $post->post_content;
            $xagio_blocks  = parse_blocks( $xagio_content );

            if ( ! is_array( $xagio_blocks ) || ! $xagio_blocks ) return false;

            // Helpers
            $size_payload = function( int $attachment_id, string $size ) {
                $src = wp_get_attachment_image_src( $attachment_id, $size );
                if ( ! $src ) return null;
                list( $xagio_url, $width, $height ) = $src;
                return [
                    'url'         => wp_make_link_relative( $xagio_url ),
                    'height'      => (int) $height,
                    'width'       => (int) $width,
                    'orientation' => $width >= $height ? 'landscape' : 'portrait',
                ];
            };

            $rewrite_img_html = function( $html ) use ( $old_id, $new_id, $new_src ) {
                if ( ! is_string( $html ) || $html === '' ) return $html;
                // Force the first <img src="..."> to the new source inside THIS block only
                $html = preg_replace(
                    '/(<img\b[^>]*\bsrc=")[^"]*(")/i',
                    '$1' . preg_quote( $new_src, '/' ) . '$2',
                    $html,
                    1
                );
                // Update wp-image-<id> class if present
                if ( $old_id ) {
                    $html = preg_replace('/\bwp-image-' . preg_quote((string)$old_id, '/') . '\b/', 'wp-image-' . $new_id, $html);
                } else {
                    // If we don't know the old, update first wp-image-<num>
                    $html = preg_replace('/\bwp-image-\d+\b/', 'wp-image-' . $new_id, $html, 1);
                }
                return $html;
            };

            // Match Kadence inspector token to block attrs.uniqueID
            $matches_element = function( array $block, string $needle ) : bool {
                if ( $needle === '' ) return false;
                $uid = isset($block['attrs']['uniqueID']) ? (string)$block['attrs']['uniqueID'] : '';
                if ( $uid === '' ) return false;

                // direct match or anywhere inside the inspector token (e.g. "kb-image12_ce97f5-72")
                if ( $needle === $uid ) return true;
                return strpos($needle, $uid) !== false;
            };

            $updated = false;

            $walk = function( array &$xagio_bs ) use ( &$walk, $matches_element, $element_id, $sub_target, $old_id, $old_url, $new_id, $new_url, $new_rel, $rewrite_img_html, $size_payload, &$updated ) {
                foreach ( $xagio_bs as &$b ) {
                    $xagio_name  = isset($b['blockName']) ? (string)$b['blockName'] : '';
                    $attrs = isset($b['attrs']) && is_array($b['attrs']) ? $b['attrs'] : [];

                    if ( $matches_element( $b, $element_id ) ) {
                        // 1) kadence/testimonial: avatar lives in attrs (id/url/sizes). No innerHTML.
                        if ( $xagio_name === 'kadence/testimonial' ) {
                            // Only proceed if targeting the image-ish part (no sub_target means whole block)
                            if ( $sub_target === '' || in_array( $sub_target, ['kt-testimonial-image','kb-img'], true ) ) {
                                $b['attrs']['id']  = $new_id;
                                $b['attrs']['url'] = ($new_rel !== '' ? $new_rel : $new_url);

                                // Rebuild sizes
                                $sizes = isset($b['attrs']['sizes']) && is_array($b['attrs']['sizes']) ? $b['attrs']['sizes'] : [];
                                if ( $xagio_p = $size_payload( $new_id, 'thumbnail' ) ) $sizes['thumbnail'] = $xagio_p;
                                if ( $xagio_p = $size_payload( $new_id, 'medium' ) )    $sizes['medium']    = $xagio_p;

                                $xagio_meta = wp_get_attachment_metadata( $new_id );
                                $sizes['full'] = [
                                    'url'         => ($new_rel !== '' ? $new_rel : $new_url),
                                    'height'      => isset($xagio_meta['height']) ? (int)$xagio_meta['height'] : 0,
                                    'width'       => isset($xagio_meta['width'])  ? (int)$xagio_meta['width']  : 0,
                                    'orientation' => ( (int)($xagio_meta['width'] ?? 0) >= (int)($xagio_meta['height'] ?? 0) ) ? 'landscape' : 'portrait',
                                ];
                                $b['attrs']['sizes'] = $sizes;
                                $updated = true;
                            }

                            // 2) kadence/image & kadence/advanced-image: id in attrs, markup in innerHTML/innerContent
                        } elseif ( $xagio_name === 'kadence/image' || $xagio_name === 'kadence/advanced-image' ) {
                            // sub_target for images usually 'kb-img' (the <img> class). If given and not image-y, skip.
                            if ( $sub_target === '' || $sub_target === 'kb-img' ) {
                                $b['attrs']['id']  = $new_id;
                                $b['attrs']['url'] = $new_url; // keep attrs in sync if present
                                if ( isset($b['innerHTML']) && is_string($b['innerHTML']) ) {
                                    $b['innerHTML'] = $rewrite_img_html( $b['innerHTML'] );
                                }
                                if ( isset($b['innerContent']) && is_array($b['innerContent']) ) {
                                    foreach ( $b['innerContent'] as $xagio_i => $piece ) {
                                        if ( is_string($piece) && $piece !== '' ) {
                                            $b['innerContent'][$xagio_i] = $rewrite_img_html( $piece );
                                        }
                                    }
                                }
                                $updated = true;
                            }

                            // 3) core/image & core/cover: standard WordPress blocks
                        } elseif ( $xagio_name === 'core/image' || $xagio_name === 'core/cover' ) {
                            if ( $sub_target === '' || $sub_target === 'kb-img' ) {
                                $b['attrs']['id']  = $new_id;
                                $b['attrs']['url'] = $new_url;
                                if ( isset($b['innerHTML']) && is_string($b['innerHTML']) ) {
                                    $b['innerHTML'] = $rewrite_img_html( $b['innerHTML'] );
                                }
                                if ( isset($b['innerContent']) && is_array($b['innerContent']) ) {
                                    foreach ( $b['innerContent'] as $xagio_i => $piece ) {
                                        if ( is_string($piece) && $piece !== '' ) {
                                            $b['innerContent'][$xagio_i] = $rewrite_img_html( $piece );
                                        }
                                    }
                                }
                                $updated = true;
                            }
                        }

                        // We matched this element_id; do not touch unrelated blocks
                    }

                    if ( ! empty($b['innerBlocks']) ) {
                        $walk( $b['innerBlocks'] );
                    }
                }
            };

            $walk( $xagio_blocks );

            if ( $updated ) {
                $new_content = serialize_blocks( $xagio_blocks );
                wp_update_post( [ 'ID' => $post_id, 'post_content' => $new_content ] );
                return true;
            }
            return false;
        }


        public static function elementorReplaceImageByUrl(&$elements, $oldUrlId, $newUrlId)
        {
            $oldUrl = wp_get_attachment_url($oldUrlId);
            $newUrl = wp_get_attachment_url($newUrlId);

            foreach ($elements as &$element) {

                // Replace image
                if (isset($element['settings']['image'])) {
                    if ($element['settings']['image']['url'] == $oldUrl || $element['settings']['image']['id'] == $oldUrlId) {
                        $element['settings']['image']['url'] = $newUrl;
                        $element['settings']['image']['id']  = $newUrlId;
                        return true;
                    }
                }

                // Replace background image
                if (isset($element['settings']['background_image'])) {
                    if ($element['settings']['background_image']['url'] == $oldUrl || $element['settings']['background_image']['id'] == $oldUrlId) {
                        $element['settings']['background_image']['url'] = $newUrl;
                        $element['settings']['background_image']['id']  = $newUrlId;
                        return true;
                    }
                }

                // Recurse into child elements
                if (!empty($element['elements']) && is_array($element['elements'])) {
                    if (self::elementorReplaceImageByUrl($element['elements'], $oldUrlId, $newUrlId)) {
                        return true;
                    }
                }
            }
            return false;
        }

        public static function getAiFrontEndOutput()
        {

            global $wpdb;

            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['ids'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $ids = sanitize_text_field(wp_unslash($_POST['ids']));
            $ids = array_filter(explode(',', $ids), 'intval');

            // Build placeholders and args
            $xagio_placeholders = implode(',', array_fill(0, count($ids), '%d'));

            $ai_statuses = $wpdb->get_results(
                $wpdb->prepare(
                    "
    SELECT *
    FROM `xag_ai`
    WHERE `id` IN ( {$xagio_placeholders} )
      AND `status` = 'completed'
", $ids
                ), ARRAY_A
            );

            if (is_array($ai_statuses)) {

                $xagio_output = [];
                foreach ($ai_statuses as $ai_status) {

                    $xagio_output[] = [
                        'id'     => absint($ai_status['id']),
                        'input'  => $ai_status['input'],
                        'output' => $ai_status['output']
                    ];

                }

                $ai_statuses = $xagio_output;

            }

            xagio_json(
                'success', 'Retrieved outputs.', $ai_statuses
            );

        }

        public static function processTextEdit() {
            global $wpdb;

            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['post_id'], $_POST['data_id'], $_POST['content'], $_POST['page_type'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $post_id           = intval(wp_unslash($_POST['post_id']));
            $data_id           = sanitize_text_field(wp_unslash($_POST['data_id']));
            $xagio_content           = sanitize_text_field(wp_unslash($_POST['content']));
            $page_type         = sanitize_text_field(wp_unslash($_POST['page_type']));
            $additional_prompt = isset($_POST['additional_prompt']) ? sanitize_text_field(wp_unslash($_POST['additional_prompt'])) : '';
            $sub_target        = isset($_POST['sub_target']) && is_string($_POST['sub_target']) ? sanitize_text_field(wp_unslash($_POST['sub_target'])) : null;

            $settings = [
                'post_id'   => $post_id,
                'data_id'   => $data_id,
                'page_type' => $page_type,
            ];
            if ($sub_target) {
                $settings['sub_target'] = $sub_target; // include so the consumer can do surgical replacement
            }

            $xagio_http_code = 0;
            $xagio_result    = self::_sendAiRequest(
                'TEXT_CONTENT',
                14,
                $post_id,
                [
                    $xagio_content,
                    $additional_prompt
                ],
                $settings,
                $xagio_http_code
            );

            $ID = 0;
            if ($xagio_http_code === 200) {
                $ID = $wpdb->insert_id;
            }

            xagio_json(
                ($xagio_http_code === 200) ? 'success' : 'error',
                ($xagio_http_code === 406) ? 'Upgrade your account to use AI features.' : $xagio_result['message'],
                $ID
            );
        }

        public static function checkTextStatus()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['data_id'], $_POST['post_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $data_id = intval(wp_unslash($_POST['data_id']));
            $post_id = intval(wp_unslash($_POST['post_id']));


            if (!$data_id) {
                xagio_json('error', 'Failed to find data id!');
            } else {

                // Prepare and execute the query using wpdb::prepare
                $ai_status = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT `status` FROM `xag_ai` WHERE `target_id` = %d AND `input` = %s AND `status` != 'completed' AND `settings` LIKE %s ORDER BY `id` DESC", $post_id, 'TEXT_CONTENT', "%$data_id%"
                    ), ARRAY_A
                );

                xagio_json('success', 'Text Status retrieved!', isset($ai_status['status']) ? $ai_status['status'] : false);

            }

        }

        public static function processImageEditByAttachmentID()
        {

            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['attachment_id'], $_POST['action_type'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $attachment_id     = intval(wp_unslash($_POST['attachment_id']));
            $action_type       = sanitize_text_field(wp_unslash($_POST['action_type']));
            $page_type         = sanitize_text_field(wp_unslash($_POST['page_type']));
            $additional_prompt = sanitize_text_field(wp_unslash($_POST['additional_prompt']));

            $data_id           = sanitize_text_field(wp_unslash($_POST['data_id']));
            $sub_target        = isset($_POST['sub_target']) && is_string($_POST['sub_target']) ? sanitize_text_field(wp_unslash($_POST['sub_target'])) : null;

            $post_id = 0;
            if (isset($_POST['post_id'])) {
                $post_id = intval(wp_unslash($_POST['post_id']));
            }

            $image_url = wp_get_attachment_url($attachment_id);

            $xagio_input        = 'IMAGE_EDIT';
            $input_prompt = 12;

            $xagio_args = [
                $image_url,
                $additional_prompt
            ];

            if ($action_type == 'generate') {
                $xagio_input        = 'IMAGE_GEN';
                $input_prompt = 13;

                $xagio_args = [
                    $additional_prompt
                ];
            }

            $settings = [
                'attachment_id' => $attachment_id,
                'page_type' => $page_type
            ];

            if ($data_id) {
                $settings['data_id'] = $data_id;
            }

            if ($sub_target) {
                $settings['sub_target'] = $sub_target;
            }


            $xagio_http_code = 0;
            $xagio_result    = self::_sendAiRequest(
                $xagio_input, $input_prompt, $post_id, $xagio_args, $settings, $xagio_http_code
            );

            $ID = 0;
            if ($xagio_http_code == 200) {
                $ID = $wpdb->insert_id;
            }

            xagio_json(
                ($xagio_http_code == 200) ? 'success' : 'error', ($xagio_http_code == 406) ? 'Upgrade your account to use AI features.' : $xagio_result['message'], $ID
            );
        }

        public static function checkClusterStatus()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['project_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $project_id = intval(wp_unslash($_POST['project_id']));

            if (!$project_id) {
                xagio_json('error', 'Failed to find project!');
            } else {

                // Prepare and execute the query using wpdb::prepare
                $ai_status = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT `status` FROM `xag_ai` WHERE `target_id` = %d AND `input` = 'CLUSTER' AND `status` != 'completed' ORDER BY `id` DESC", $project_id
                    ), ARRAY_A
                );

                xagio_json('success', 'Cluster status retrieved!', isset($ai_status['status']) ? $ai_status['status'] : false);

            }

        }

        public static function checkImageStatus()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['attachment_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $attachment_id = intval(wp_unslash($_POST['attachment_id']));


            if (!$attachment_id) {
                xagio_json('error', 'Failed to find attachment!');
            } else {

                // Prepare and execute the query using wpdb::prepare
                $ai_status = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT `status` FROM `xag_ai` WHERE `target_id` = %d AND (`input` = 'IMAGE_EDIT' OR `input` = 'IMAGE_GEN') AND `status` != 'completed' ORDER BY `id` DESC", $attachment_id
                    ), ARRAY_A
                );

                xagio_json('success', 'Image Status retrieved!', isset($ai_status['status']) ? $ai_status['status'] : false);

            }

        }

        public static function getAttachmentIdByUrl()
        {

            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['image_url'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $image_url = sanitize_url(wp_unslash($_POST['image_url']));

            $attachment_id = self::getAttachmentIdByUrlFunction($image_url);

            if (isset($attachment_id['status']) && $attachment_id['status'] === 'error') {
                xagio_json('error', $attachment_id['message'] ?? 'Make sure image is in Media Library.');
            } else {
                xagio_json('success', 'Found attachment!', [
                    'image_url' => wp_get_attachment_url($attachment_id),
                    'id'        => $attachment_id
                ]);
            }

        }

        public static function getAttachmentIdByUrlFunction($image_url)
        {
            global $wpdb;

            // normalize upload base URL (ensure trailing slash)
            $upload_dir = wp_upload_dir();
            $base_url   = trailingslashit(untrailingslashit($upload_dir['baseurl']));

            $image_url = self::xagio_normalize_uploads_url($image_url, $base_url);

            // only proceed if this URL is in our uploads folder
            if (strpos($image_url, $base_url) === false) {
				if (wp_parse_url($image_url, PHP_URL_SCHEME) != wp_parse_url($base_url, PHP_URL_SCHEME)) {
					return [
						'status'    => 'error',
						'message'   => 'SSL not configured correctly! Check site URL in Wordpress settings.'
					];
				} else {
					return ['status'    => 'error'];
				}
            }

            // strip off any query-string or fragment
            $image_url = preg_split('/[#\?]/', $image_url)[0];

            // 0) Try WP's built-in helper first
            if (function_exists('attachment_url_to_postid')) {
                $attachment_id = attachment_url_to_postid($image_url);
                if ($attachment_id) {
                    return (int)$attachment_id;
                }
            }

            // get the path relative to uploads/
            $xagio_relative_path = ltrim(str_replace($base_url, '', $image_url), '/');

            // 1) Exact match on _wp_attached_file
            $attachment_id = $wpdb->get_var(
                $wpdb->prepare(
                    "
        SELECT post_id
          FROM {$wpdb->postmeta}
         WHERE meta_key   = '_wp_attached_file'
           AND meta_value = %s
         LIMIT 1
        ", $xagio_relative_path
                )
            );
            if ($attachment_id) {
                return (int)$attachment_id;
            }

            // parse info
            $info      = pathinfo($xagio_relative_path);
            $dir       = isset($info['dirname']) ? $info['dirname'] : '';
            $filename  = isset($info['filename']) ? $info['filename'] : '';
            $extension = isset($info['extension']) ? $info['extension'] : '';

            // 2a) Handle WP resized images: strip -WxH suffix (e.g., image-300x200.jpg)
            $clean_wpname = preg_replace('/-\d+x\d+$/', '', $filename);
            if ($clean_wpname !== $filename) {
                $orig_path_same_dir = ltrim("{$dir}/{$clean_wpname}.{$extension}", '/');
                $attachment_id      = $wpdb->get_var(
                    $wpdb->prepare(
                        "
            SELECT post_id
              FROM {$wpdb->postmeta}
             WHERE meta_key   = '_wp_attached_file'
               AND meta_value = %s
             LIMIT 1
            ", $orig_path_same_dir
                    )
                );
                if ($attachment_id) {
                    return (int)$attachment_id;
                }
            }

            // 2b) Handle Elementor-style hash suffixes: strip trailing -<hash> where hash is alphanumeric (length >=8)
            $clean_elementor_name = preg_replace('/-[a-z0-9]{8,}$/i', '', $clean_wpname);
            $original_basename    = "{$clean_elementor_name}.{$extension}";

            // 3) Fallback: loose match by basename anywhere in uploads (e.g., 2025/07/Who-We-Are.png)
            $like          = '%' . $wpdb->esc_like($original_basename) . '%';
            $attachment_id = $wpdb->get_var(
                $wpdb->prepare(
                    "
        SELECT post_id
          FROM {$wpdb->postmeta}
         WHERE meta_key   = '_wp_attached_file'
           AND meta_value LIKE %s
         ORDER BY post_id DESC
         LIMIT 1
        ", $like
                )
            );

            return $attachment_id ? (int)$attachment_id : ['status' => 'error'];
        }


        // GET Status AI - this is only for Pages and Posts
        public static function getPageStatusAi($target_id, $xagio_input)
        {
            global $wpdb;

            // Prepare and execute the query using wpdb::prepare
            $ai_status = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT `status` FROM `xag_ai` WHERE `target_id` = %d AND `input` = %s ORDER BY `id` DESC", $target_id, $xagio_input
                ), ARRAY_A
            );

            return $ai_status['status'] ?? false;
        }

        public static function removeAiRequest($target_id, $xagio_input)
        {
            global $wpdb;

            $wpdb->delete('xag_ai', [
                'target_id' => $target_id,
                'input'     => $xagio_input
            ]);

            return $wpdb->rows_affected > 0;
        }

        // UPDATE Status AI - this is only for Pages and Posts
        public static function updatePageStatusAi($target_id, $xagio_input, $status)
        {
            global $wpdb;

            $wpdb->update(
                'xag_ai', ['status' => $status], [
                    'target_id' => $target_id,
                    'input'     => $xagio_input,
                ]
            );
        }

        public static function removeallslashes($string)
        {
            $string = implode("", explode("\\", $string));
            return stripslashes(trim($string));
        }

        public static function safeJsonDecode($json, $assoc = true)
        {
            // Remove trailing commas before closing braces/brackets
            $cleaned = preg_replace('/,\s*([\]}])/', '$1', $json);

            // Decode cleaned JSON
            $decoded = json_decode($cleaned, $assoc);

            // Check for errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                return NULL;
            }

            return $decoded;
        }

        public static function _sendAiRequest($xagio_input = 'PAGE_CONTENT', $prompt_id = 0, $target_id = 0, $xagio_args = [], $additional = [], &$xagio_http_code = 0)
        {
            global $wpdb;

            // Prepare request parameters
            $request_params = [
                'input'      => $xagio_input,
                'api_key'    => XAGIO_API::getAPIKey(),
                'admin_post' => XAGIO_MODEL_SETTINGS::getApiUrl(),
                'args'       => $xagio_args,
                'prompt_id'  => $prompt_id,
                'target_id'  => $target_id
            ];

            if (!empty($additional)) {
                $request_params = array_merge($request_params, $additional);
            }

            // Check if there's already a running AI request
            $status = self::getPageStatusAi($target_id, $xagio_input);

            if ($status === 'running' && $xagio_input !== 'TEXT_CONTENT') {
                self::removeAiRequest($target_id, $xagio_input);
            }

            // Initialize the output and http_code
            $xagio_http_code = 0;
            $xagio_output    = XAGIO_API::apiRequest('ai', 'POST', $request_params, $xagio_http_code);

            // If the request was successful (HTTP 200)
            if ($xagio_http_code == 200) {
                // Check if the table exists
                if (empty($wpdb->get_results("SHOW TABLES LIKE '%xag_ai%'", ARRAY_A))) {
                    self::createTable();
                }

                // Prepare the row data to be inserted
                $row = [
                    'target_id'    => $target_id,
                    'status'       => 'running',
                    'input'        => $xagio_input,
                    'settings'     => wp_json_encode($request_params, JSON_UNESCAPED_SLASHES),
                    'date_created' => gmdate('Y-m-d H:i:s')
                ];

                // Insert the data into the table
                $wpdb->insert('xag_ai', $row);
            }

            return $xagio_output;
        }

        public static function getSchemaProfiles($post_id, $title, $xagio_description, $h1, $schema_type)
        {
            $seo_profiles = get_option('XAGIO_SEO_PROFILES');

            $xagio_args = [];

            if ($seo_profiles) {
                // Create a flattened string of any non-empty values from the option
                $flattened_data = [];
                foreach ($seo_profiles as $xagio_group => $xagio_profiles) {
                    if (is_array($xagio_profiles)) {
                        foreach ($xagio_profiles as $xagio_key => $xagio_value) {
                            if (!empty($xagio_value)) {
                                // Replace underscores with spaces in the profile key
                                $flattened_data[] = str_replace('_', ' ', $xagio_key) . ': ' . $xagio_value;
                            }
                        }
                    }
                }
                $other_profiles_data = implode(' | ', $flattened_data);

                // Extract contact details if available
                $contact_details = $seo_profiles['contact_details'] ?? [];
                $phone           = isset($contact_details['business_phone']) ? sanitize_text_field($contact_details['business_phone']) : '';
                $address         = isset($contact_details['business_address']) ? sanitize_text_field($contact_details['business_address']) : '';
                $city            = isset($contact_details['business_city']) ? sanitize_text_field($contact_details['business_city']) : '';
                $state           = isset($contact_details['business_state']) ? sanitize_text_field($contact_details['business_state']) : '';
                $xagio_country         = isset($contact_details['business_country']) ? sanitize_text_field($contact_details['business_country']) : '';

                // Extract social media details if available
                $social_media = $seo_profiles['social_media'] ?? [];
                $facebook     = isset($social_media['facebook']) ? sanitize_text_field($social_media['facebook']) : '';
                $youtube      = isset($social_media['youtube']) ? sanitize_text_field($social_media['youtube']) : '';
                $instagram    = isset($social_media['instagram']) ? sanitize_text_field($social_media['instagram']) : '';
                $linkedin     = isset($social_media['linkedin']) ? sanitize_text_field($social_media['linkedin']) : '';
                $x            = isset($social_media['x']) ? sanitize_text_field($social_media['x']) : '';
                $tiktok       = isset($social_media['tiktok']) ? sanitize_text_field($social_media['tiktok']) : '';
                $pinterest    = isset($social_media['pinterest']) ? sanitize_text_field($social_media['pinterest']) : '';


                $post_url = get_permalink($post_id); // Add PAGE ID WHEN CREATED
                $logo     = get_site_icon_url();
                $image    = '';

                // Build the $xagio_args array in the same order as before
                $xagio_args = [
                    $schema_type,
                    $h1,
                    $title,
                    $xagio_description,
                    $post_url,
                    $logo,
                    $image,
                    $phone,
                    $address,
                    $city,
                    $state,
                    $xagio_country,
                    $facebook,
                    $youtube,
                    $tiktok,
                    $linkedin,
                    $instagram,
                    $x,
                    $pinterest,
                    $other_profiles_data
                ];
            } else {
                $xagio_args = [
                    $title,
                    $xagio_description,
                    $h1,
                    $schema_type
                ];
            }

            return $xagio_args;
        }


        public static function getContentProfiles($post_id)
        {
            global $wpdb;

            $title       = get_post_meta($post_id, 'XAGIO_SEO_TITLE', true);
            $xagio_description = get_post_meta($post_id, 'XAGIO_SEO_DESCRIPTION', true);

            $xagio_group = $wpdb->get_row($wpdb->prepare("SELECT * FROM xag_groups WHERE id_page_post = %d", $post_id), ARRAY_A);


            if (isset($xagio_group['h1'])) {
                $h1 = $xagio_group['h1'];
            } else {
                $h1 = get_the_title($post_id);
            }

            $list = json_encode(XAGIO_MODEL_OCW::extractFieldsFromJson(json_decode(get_post_meta($post_id, '_elementor_data', true), true)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (empty($title)) {
                return [
                    'status'  => 'error',
                    'message' => 'Title is missing',
                ];
            }

            if (empty($xagio_description)) {
                return [
                    'status'  => 'error',
                    'message' => 'Description is missing',
                ];
            }

            if (empty($h1)) {
                return [
                    'status'  => 'error',
                    'message' => 'H1 is missing',
                ];
            }

            $seo_profiles = get_option('XAGIO_SEO_PROFILES');


            $keywords = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT k.keyword 
         FROM xag_keywords AS k
         INNER JOIN xag_groups AS g ON k.group_id = g.id
         WHERE g.id_page_post = %d", $post_id
                ), ARRAY_A
            );


            if (empty($keywords)) {
                $keywords = '';
            } else {
                $keywords = join(", ", array_column($keywords, 'keyword'));
            }

            $xagio_args = [
                'Based on Title, Description, H1',
                $title,
                $xagio_description,
                $h1,
                $keywords,
                $list,
                'professional',
                'pleasing'
            ];

            if ($seo_profiles) {
                // Create a flattened string of any non-empty values from the option
                $flattened_data = [];
                foreach ($seo_profiles as $xagio_group => $xagio_profiles) {
                    if (is_array($xagio_profiles)) {
                        foreach ($xagio_profiles as $xagio_key => $xagio_value) {
                            if (!empty($xagio_value)) {
                                // Replace underscores with spaces in the profile key
                                $flattened_data[] = str_replace('_', ' ', $xagio_key) . ': ' . $xagio_value;
                            }
                        }
                    }
                }
                $other_profiles_data = implode(' | ', $flattened_data);

                // Extract contact details if available
                $contact_details = $seo_profiles['contact_details'] ?? [];
                $phone           = isset($contact_details['business_phone']) ? sanitize_text_field($contact_details['business_phone']) : '';
                $address         = isset($contact_details['business_address']) ? sanitize_text_field($contact_details['business_address']) : '';
                $city            = isset($contact_details['business_city']) ? sanitize_text_field($contact_details['business_city']) : '';
                $state           = isset($contact_details['business_state']) ? sanitize_text_field($contact_details['business_state']) : '';
                $xagio_country         = isset($contact_details['business_country']) ? sanitize_text_field($contact_details['business_country']) : '';

                // Extract social media details if available
                $social_media = $seo_profiles['social_media'] ?? [];
                $facebook     = isset($social_media['facebook']) ? sanitize_text_field($social_media['facebook']) : '';
                $youtube      = isset($social_media['youtube']) ? sanitize_text_field($social_media['youtube']) : '';
                $instagram    = isset($social_media['instagram']) ? sanitize_text_field($social_media['instagram']) : '';
                $linkedin     = isset($social_media['linkedin']) ? sanitize_text_field($social_media['linkedin']) : '';
                $x            = isset($social_media['x']) ? sanitize_text_field($social_media['x']) : '';
                $tiktok       = isset($social_media['tiktok']) ? sanitize_text_field($social_media['tiktok']) : '';
                $pinterest    = isset($social_media['pinterest']) ? sanitize_text_field($social_media['pinterest']) : '';


                $post_url = get_permalink($post_id); // Add PAGE ID WHEN CREATED
                $logo     = get_site_icon_url();
                $image    = '';


                $xagio_profiles = "Post URL: $post_url, Logo: $logo, Image: $image, Phone: $phone, Address: $address, City: $city, 
                State: $state, Country: $xagio_country, Facebook: $facebook, YouTube: $youtube, Tiktok: $tiktok, LinkedIn: $linkedin, Instagram: $instagram, X: $x, Pintrest: $pinterest, Others Profiles: $other_profiles_data";

                // Build the $xagio_args array in the same order as before
                $xagio_args = [
                    $xagio_profiles,
                    $title,
                    $xagio_description,
                    $h1,
                    $keywords,
                    $list,
                    'professional',
                    'pleasing'
                ];
            }

            return $xagio_args;
        }

        public static function packKeywords($keywords)
        {
            $data = [];
            foreach ($keywords as $row) {
                $kw     = [
                    'keyword'       => $row['keyword'],
                    'search_volume' => $row['volume'],
                    'in_title'      => $row['intitle'],
                    'in_url'        => $row['inurl']
                ];
                $data[] = $kw;
            }
            return $data;
        }

        public static function createTable()
        {
            global $wpdb;
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $charset_collate = $wpdb->get_charset_collate();
            $creation_query  = "CREATE TABLE xag_ai (
                        id int UNSIGNED NOT NULL AUTO_INCREMENT,
                        target_id int UNSIGNED NOT NULL,
                        status enum('queued', 'running', 'completed', 'failed') NOT NULL DEFAULT 'queued',
                        input varchar(65) NOT NULL DEFAULT 'n/a',
                        settings longtext NULL,
                        output longtext NULL,
                        date_created datetime NOT NULL,
                        date_updated datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        INDEX xagio_ai_tar_sta (target_id, status)
                    ) ENGINE=InnoDB {$charset_collate};";

            @dbDelta($creation_query);
        }

        public static function removeTable()
        {
            global $wpdb;
            // Execute the query
            $wpdb->query('DROP TABLE IF EXISTS xag_ai');
        }

    }
}