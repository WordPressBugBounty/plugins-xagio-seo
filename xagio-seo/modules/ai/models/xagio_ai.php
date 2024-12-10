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

        }

        public static function getAveragePrices()
        {
            $output = XAGIO_API::apiRequest('ai', 'POST', [], $http_code);
            if ($http_code == 203) {
                xagio_jsonc([
                    'status'  => 'success',
                    'message' => 'Average Prices loaded',
                    'data'    => $output
                ]);
            } elseif ($http_code == 406) {
                xagio_jsonc([
                    'status'  => 'upgrade',
                    'message' => 'Upgrade your account to use AI features!'
                ]);
            } else {
                xagio_jsonc([
                    'status'  => 'error',
                    'message' => 'Average Prices not loaded!',
                    'data'    => $output
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
            $key   = $type[0];
            $index = $type[1];

            switch ($key) {
                case "header":
                    $key = "h1";
                    break;
                case "desc":
                    $key = "description";
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

            $output               = json_decode($find_group['output'], true);
            $output[$index][$key] = $text;

            $t = $wpdb->update('xag_ai', [
                'output' => wp_json_encode($output, JSON_PRETTY_PRINT)
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

        public static function getAiSuggestions()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['keyword_group']) || !isset($_POST['group_id']) || !isset($_POST['input']) || !isset($_POST['main_keyword'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $group_id      = intval(wp_unslash($_POST['group_id']));
            $input         = sanitize_text_field(wp_unslash($_POST['input']));
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
            $group_suggestions = $wpdb->get_row($wpdb->prepare('SELECT `status`, `output` FROM xag_ai WHERE `target_id` = %d AND `input` = %s', $group_id, $input), ARRAY_A);

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

            if ($input === 'SEO_SUGGESTIONS') {
                $keyword_group = self::removeallslashes($keyword_group);
                $keyword_group = ltrim($keyword_group, '"');
                $keyword_group = rtrim($keyword_group, '"');
                $keyword_group = json_decode($keyword_group, TRUE);

                $keyword_list = "";
                foreach ($keyword_group as $item) {
                    $keyword_list .= $item['text'] . "(" . $item['weight'] . "), ";
                }
                $keyword_list = rtrim($keyword_list, ", ");

                $args = [
                    $keyword_list
                ];
            } else {
                $args = [
                    $keyword_group,
                    $main_keyword
                ];
            }

            $additional = [
                'group_id' => $group_id
            ];

            $http_code = 0;
            $result    = self::_sendAiRequest($input, $prompt_id, $group_id, $args, $additional, $http_code);

            if ($http_code == 406) {
                xagio_jsonc([
                    'status'  => 'upgrade',
                    'message' => 'Upgrade your account to use AI features.'
                ]);
            }

            xagio_jsonc([
                'status'  => ($http_code == 200) ? 'success' : 'error',
                'message' => $result['message']
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
            $description = sanitize_text_field(wp_unslash($_POST['description']));
            $h1          = sanitize_text_field(wp_unslash($_POST['h1']));
            $style       = sanitize_text_field(wp_unslash($_POST['content_style']));
            $tone        = sanitize_text_field(wp_unslash($_POST['content_tone']));
            $prompt_id   = intval($_POST['prompt_id']);
            $input       = "PAGE_CONTENT";

            // Check if AI request is already made
            if (self::getPageStatusAi($post_id, $input) === 'running') {
                xagio_jsonc([
                    'status'  => 'error',
                    'message' => 'AI request is already made for this page.'
                ]);
            }

            $http_code = 0;
            $result    = self::_sendAiRequest(
                $input, $prompt_id, $post_id, [
                $title,
                $description,
                $h1,
                $style,
                $tone
            ], ['post_id' => $post_id], $http_code
            );

            if ($http_code == 406) {
                xagio_jsonc([
                    'status'  => 'upgrade',
                    'message' => 'Upgrade your account to use AI features.'
                ]);
            }

            xagio_jsonc([
                'status'  => ($http_code == 200) ? 'success' : 'error',
                'message' => $result['message']
            ]);
        }

        public static function getAiSchema()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['post_id']) || !isset($_POST['title']) || !isset($_POST['description']) || !isset($_POST['h1']) || !isset($_POST['schema'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $post_id = intval($_POST['post_id']);

            $title       = sanitize_text_field(wp_unslash($_POST['title']));
            $description = sanitize_text_field(wp_unslash($_POST['description']));
            $h1          = sanitize_text_field(wp_unslash($_POST['h1']));
            $schema_type = sanitize_text_field(wp_unslash($_POST['schema']));
            $prompt_id   = intval($_POST['prompt_id']);
            $input       = "SCHEMA";

            // Check if AI request is already made
            if (self::getPageStatusAi($post_id, $input) === 'running') {
                xagio_jsonc([
                    'status'  => 'error',
                    'message' => 'AI request is already made for this page.'
                ]);
            }

            $http_code = 0;
            $result    = self::_sendAiRequest(
                $input, $prompt_id, $post_id, [
                $title,
                $description,
                $h1,
                $schema_type
            ], ['post_id' => $post_id], $http_code
            );

            if ($http_code == 406) {
                xagio_jsonc([
                    'status'  => 'upgrade',
                    'message' => 'Upgrade your account to use AI features.'
                ]);
            }

            xagio_jsonc([
                'status'  => ($http_code == 200) ? 'success' : 'error',
                'message' => $result['message']
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
            $input     = sanitize_text_field(wp_unslash($_POST['input']));

            // Get the status of the AI request
            $status = self::getPageStatusAi($target_id, $input);

            global $wpdb;

            // Prepare the query to find the most recent 'running' entry
            $failed_check = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT `id`, `date_created` FROM `xag_ai` WHERE `target_id` = %d AND `input` = %s AND `status` = 'running' ORDER BY `id` DESC", $target_id, $input
                ), ARRAY_A
            );

            // Check if the request has been running for more than 30 minutes
            $timestamp           = strtotime($failed_check['date_created'] ?? '');
            $future_date_created = $timestamp + (30 * 60); // 30 minutes later
            $currentTime         = time();

            if ($currentTime > $future_date_created && $timestamp) {
                // If more than 30 minutes have passed, mark the request as failed
                $wpdb->update(
                    'xag_ai', [
                    'status' => 'failed',
                    'output' => 'Failed to generate, please try again.'
                ], [
                        'id'        => $failed_check['id'] ?? 0,
                        'target_id' => $target_id,
                        'input'     => $input,
                    ]
                );
            }

            if ($status === false) {
                // If not in the queue
                xagio_jsonc(['status' => 'none']);
            } elseif ($status === 'completed' || $status === 'failed') {
                // If the request is completed or failed, retrieve the output
                $output = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT `id`, `output` FROM `xag_ai` WHERE `target_id` = %d AND `input` = %s ORDER BY `id` DESC", $target_id, $input
                    ), ARRAY_A
                );
                $id     = $output['id'];

                switch ($input) {
                    case 'PAGE_CONTENT':
                        $output = stripslashes($output['output']);
                        break;
                    case 'SEO_SUGGESTIONS_MAIN_KW':
                    case 'SEO_SUGGESTIONS':
                        $output['output'] = str_replace('\n', "\n", $output['output']);
                        $output['output'] = stripslashes($output['output']);
                        $output           = json_decode($output['output'], true);
                        break;
                }

                xagio_jsonc([
                    'status' => 'completed',
                    'data'   => $output,
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
            $input = wp_kses_post(wp_unslash($request->get_param('input')));

            if (method_exists('XAGIO_MODEL_AI', 'xagio_ai_' . $input)) {
                call_user_func([
                    'XAGIO_MODEL_AI',
                    'xagio_ai_' . $input
                ], $request);
            }
        }

        public static function xagio_ai_SCHEMA($request)
        {
            global $wpdb;

            if (empty($request->get_param('post_id')) || empty($request->get_param('output'))) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $post_id = intval($request->get_param('post_id'));
            $output  = sanitize_text_field(wp_unslash($request->get_param('output')));

            if ($output == 'FAILED') {
                $wpdb->update('xag_ai', [
                    'status'       => 'failed',
                    'output'       => 'Failed to generate schema, please try again.',
                    'date_updated' => gmdate('Y-m-d H:i:s')
                ], [
                    'status'    => 'running',
                    'target_id' => $post_id,
                    'input'     => 'SCHEMA',
                ]);
                xagio_json('error', 'Schema failed to generate.');
                return;
            }

            // remove escape characters
            $output = trim(preg_replace('/\\\\/', '', $output));

            // try to convert to array
            $output = json_decode($output, TRUE);

            if (!$output) {
                $wpdb->update('xag_ai', [
                    'status'       => 'failed',
                    'output'       => 'Failed to generate proper JSON schema, please try again.',
                    'date_updated' => gmdate('Y-m-d H:i:s')
                ], [
                    'status'    => 'running',
                    'target_id' => $post_id,
                    'input'     => 'SCHEMA',
                ]);
                xagio_json('error', 'Schema failed to generate.');
                return;
            }

            if (!isset($_SERVER['SERVER_NAME'])) {
                xagio_json('error', 'General Error');
            }

            XAGIO_API::apiRequest('schema_wizard', 'POST', [
                'domain'  => preg_replace('/^www\./', '', sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME']))),
                'schema'  => serialize($output),
                'name'    => 'AI for ' . get_the_title($post_id) . ', ' . gmdate('Y-m-d H:i:s'),
                'post_id' => $post_id,
            ]);

            $wpdb->update('xag_ai', [
                'status'       => 'completed',
                'output'       => wp_json_encode($output, JSON_PRETTY_PRINT),
                'date_updated' => gmdate('Y-m-d H:i:s')
            ], [
                'status'    => 'running',
                'target_id' => $post_id,
                'input'     => 'SCHEMA',
            ]);

            xagio_json('success', 'Schema generated successfully.');
        }

        public static function xagio_ai_PAGE_CONTENT($request)
        {
            global $wpdb;

            if (empty($request->get_param('post_id')) || empty($request->get_param('output'))) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $post_id = intval($request->get_param('post_id'));
            $output  = wp_kses_post(wp_unslash($request->get_param('output')));

            if ($output == 'FAILED') {
                $wpdb->update('xag_ai', [
                    'status'       => 'failed',
                    'output'       => 'Failed to generate content, please try again.',
                    'date_updated' => gmdate('Y-m-d H:i:s')
                ], [
                    'status'    => 'running',
                    'target_id' => $post_id,
                    'input'     => 'PAGE_CONTENT',
                ]);
                return;
            }

            // if $output contains string "Content:", split by it and use the second part
            if (strpos($output, 'Content:') !== false) {
                $output = explode('Content:', $output)[1];
            }

            // if you add esc_sql on output we will have problems with \n
            $output = $wpdb->update('xag_ai', [
                'status'       => 'completed',
                'output'       => $output,
                'date_updated' => gmdate('Y-m-d H:i:s')
            ], [
                'status'    => 'running',
                'target_id' => $post_id,
                'input'     => 'PAGE_CONTENT',
            ]);

            wp_send_json(['data' => $output]);
        }

        public static function xagio_ai_SEO_SUGGESTIONS($request)
        {
            global $wpdb;

            if (empty($request->get_param('group_id')) || empty($request->get_param('output'))) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $group_id = intval($request->get_param('group_id'));

            // This is array output from AI API
            $output = sanitize_text_field(wp_unslash($request->get_param('output')));

            // remove escape characters
            $output         = trim(preg_replace('/\\\\/', '', $output));
            $decoded_output = json_decode($output, true);

            $output = $wpdb->update('xag_ai', [
                'status'       => 'completed',
                'output'       => esc_sql(wp_json_encode($decoded_output, JSON_PRETTY_PRINT)),
                'date_updated' => gmdate('Y-m-d H:i:s')
            ], [
                'status'    => 'running',
                'target_id' => $group_id,
                'input'     => 'SEO_SUGGESTIONS',
            ]);

            wp_send_json(['data' => $output]);
        }

        public static function xagio_ai_SEO_SUGGESTIONS_MAIN_KW($request)
        {
            global $wpdb;

            if (empty($request->get_param('group_id')) || empty($request->get_param('output'))) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $group_id = intval($request->get_param('group_id'));

            // This is array output from AI API
            $output = sanitize_text_field(wp_unslash($request->get_param('output')));

            // remove escape characters
            $output         = trim(preg_replace('/\\\\/', '', $output));
            $decoded_output = json_decode($output, true);

            $output = $wpdb->update('xag_ai', [
                'status'       => 'completed',
                'output'       => esc_sql(wp_json_encode($decoded_output, JSON_PRETTY_PRINT)),
                'date_updated' => gmdate('Y-m-d H:i:s')
            ], [
                'status'    => 'running',
                'target_id' => $group_id,
                'input'     => 'SEO_SUGGESTIONS_MAIN_KW',
            ]);

            wp_send_json(['data' => $output]);
        }

        /**
         *  AI Helper functions
         */

        // GET Status AI - this is only for Pages and Posts
        public static function getPageStatusAi($target_id, $input)
        {
            global $wpdb;

            // Prepare and execute the query using wpdb::prepare
            $ai_status = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT `status` FROM `xag_ai` WHERE `target_id` = %d AND `input` = %s ORDER BY `id` DESC", $target_id, $input
                ), ARRAY_A
            );

            return $ai_status['status'] ?? false;
        }


        // UPDATE Status AI - this is only for Pages and Posts
        public static function updatePageStatusAi($target_id, $input, $status)
        {
            $wpdb->update(
                'xag_ai', ['status' => $status], [
                    'target_id' => $target_id,
                    'input'     => $input,
                ]
            );
        }

        public static function removeallslashes($string)
        {
            $string = implode("", explode("\\", $string));
            return stripslashes(trim($string));
        }

        public static function _sendAiRequest($input = 'PAGE_CONTENT', $prompt_id = 0, $target_id = 0, $args = [], $additional = [], &$http_code = 0)
        {
            global $wpdb;

            // Prepare request parameters
            $request_params = [
                'input'      => $input,
                'api_key'    => XAGIO_API::getAPIKey(),
                'admin_post' => XAGIO_MODEL_SETTINGS::getApiUrl(),
                'args'       => $args,
                'prompt_id'  => $prompt_id
            ];

            if (!empty($additional)) {
                $request_params = array_merge($request_params, $additional);
            }

            // Check if there's already a running AI request
            $status = self::getPageStatusAi($target_id, $input);

            if ($status === 'running') {
                $http_code = 404;
                return [
                    'message' => 'AI request is already made for this action.'
                ];
            }

            // Initialize the output and http_code
            $http_code = 0;
            $output    = XAGIO_API::apiRequest('ai', 'POST', $request_params, $http_code);

            // If the request was successful (HTTP 200)
            if ($http_code == 200) {
                // Check if the table exists
                if (empty($wpdb->get_results("SHOW TABLES LIKE xag_ai", ARRAY_A))) {
                    self::createTable();
                }

                // Prepare the row data to be inserted
                $row = [
                    'target_id'    => $target_id,
                    'status'       => 'running',
                    'input'        => $input,
                    'settings'     => wp_json_encode($request_params),
                    'date_created' => gmdate('Y-m-d H:i:s')
                ];

                // Insert the data into the table
                $wpdb->insert('xag_ai', $row);
            }

            return $output;
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