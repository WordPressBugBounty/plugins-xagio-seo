<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_API')) {

    class XAGIO_API
    {

        public static function initialize()
        {
            add_action('rest_api_init', [
                'XAGIO_API',
                'registerXagioRoutes'
            ]);
            // Plugin Triggers
            add_action('deleted_plugin', [
                'XAGIO_API',
                'pluginDeleted'
            ], 10, 2);

            add_action('deactivated_plugin', [
                'XAGIO_API',
                'pluginDeactivated'
            ], 10, 2);

            add_action('activated_plugin', [
                'XAGIO_API',
                'pluginActivated'
            ], 10, 2);

            // Theme Trigger
            add_action('after_switch_theme', [
                'XAGIO_API',
                'themeSwitch'
            ]);
        }

        public static function registerXagioRoutes()
        {
            // General API request route
            register_rest_route('xagio-seo/v1', '/api', [
                'methods'             => 'POST',
                'callback'            => [
                    'XAGIO_API',
                    'handleRequest'
                ],
                'permission_callback' => function ($request = null) {
                    return is_user_logged_in() && is_super_admin();
                }
            ]);

            // General API request route
            register_rest_route('xagio-seo/v1', '/ping', [
                'methods'             => 'POST',
                'callback'            => [
                    'XAGIO_API',
                    'ping'
                ],
                'permission_callback' => function ($request = null) {
                    return true;
                }
            ]);

            // Handle remote login
            register_rest_route('xagio-seo/v1', '/remote-login', [
                'methods'             => 'GET',
                'callback'            => [
                    'XAGIO_API',
                    'remoteLogin'
                ],
                'permission_callback' => function ($request = null) {
                    if (!empty($request->get_param('xagio_remoteLoginToken'))) {

                        // Sanitize the token
                        $xagio_remoteLoginToken = sanitize_text_field(wp_unslash($request->get_param('xagio_remoteLoginToken')));

                        // Compare the temporary token with the stored transient
                        if ($xagio_remoteLoginToken !== get_transient('xagio_remoteLoginToken')) {
                            return false;
                        }

                        return true;

                    } else {
                        return false;
                    }
                }
            ]);

        }

        public static function ping($request = null)
        {
            xagio_json("success", "pong");
        }

        public static function syncWithPanel($request = null)
        {
            if (!!empty($_SERVER['SERVER_NAME'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            XAGIO_API::apiRequest(
                $endpoint = 'sync', $method = 'GET', [
                    'domain' => preg_replace('/^www\./', '', sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME']))),
                ]
            );
        }

        public static function themeSwitch($old_name)
        {
            if (!!empty($_SERVER['SERVER_NAME'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $new_theme = wp_get_theme();
            XAGIO_API::apiRequest(
                $endpoint = 'themes', $method = 'POST', [
                    'domain' => preg_replace('/^www\./', '', sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME']))),
                    'new'    => $new_theme->get('Name'),
                    'old'    => $old_name,
                    'event'  => 'switched',
                ]
            );
        }

        public static function pluginDeleted($root_name, $success)
        {
            if (!!empty($_SERVER['SERVER_NAME'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            if (!$success) {
                return FALSE;
            }
            XAGIO_API::apiRequest(
                $endpoint = 'plugins', $method = 'POST', [
                    'domain'    => preg_replace('/^www\./', '', sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME']))),
                    'root_name' => $root_name,
                    'event'     => 'deleted',
                ]
            );
        }

        public static function pluginDeactivated($root_name, $network_activation)
        {
            if (!!empty($_SERVER['SERVER_NAME'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            XAGIO_API::apiRequest(
                $endpoint = 'plugins', $method = 'POST', [
                    'domain'    => preg_replace('/^www\./', '', sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME']))),
                    'root_name' => $root_name,
                    'event'     => 'deactivated',
                ]
            );
        }

        public static function pluginActivated($root_name, $network_activation)
        {
            if (!!empty($_SERVER['SERVER_NAME'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            XAGIO_API::apiRequest(
                $endpoint = 'plugins', $method = 'POST', [
                    'domain'    => preg_replace('/^www\./', '', sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME']))),
                    'root_name' => $root_name,
                    'event'     => 'activated',
                ]
            );
        }

        /***************************************************************************************************************
         *
         *   Remote Datatables
         *
         **************************************************************************************************************/

        public static function getPostTypes($request = null)
        {
            $post_types = XAGIO_MODEL_SEO::getAllPostTypes();
            xagio_json('success', 'Retrieved post types.', $post_types);
        }

        public static function getTaxonomies($request = null)
        {
            $taxonomies = XAGIO_MODEL_SEO::getAllTaxonomies(FALSE);
            xagio_json('success', 'Retrieved taxonomies.', $taxonomies);
        }

        public static function searchTaxonomies($request = null)
        {
            global $wpdb;

            $aColumns = [
                "{$wpdb->prefix}terms.term_id",
                "{$wpdb->prefix}terms.name",
                "{$wpdb->prefix}term_taxonomy.description",
                "{$wpdb->prefix}term_taxonomy.count",
                "{$wpdb->prefix}term_taxonomy.taxonomy",
            ];

            $sIndexColumn = "{$wpdb->prefix}terms.term_id";
            $sTable       = "{$wpdb->prefix}terms";

            // Initialize parameters array for placeholders
            $queryParams = [];

            // Paging
            $sLimit = "LIMIT 0, 50";
            if (!empty($request->get_param('iDisplayStart')) && !empty($request->get_param('iDisplayLength'))) {
                $sLimit        = "LIMIT %d, %d";
                $queryParams[] = intval($request->get_param('iDisplayStart'));
                $queryParams[] = intval($request->get_param('iDisplayLength'));
            }

            // Ordering
            $sOrder = '';
            if (!empty($request->get_param('iSortCol_0')) && !empty($request->get_param('iSortingCols'))) {
                $orderArr = [];
                for ($i = 0; $i < intval($request->get_param('iSortingCols')); $i++) {
                    if (!empty($request->get_param('iSortCol_' . $i)) && !empty($request->get_param('bSortable_' . $request->get_param('iSortCol_' . $i)))) {
                        if ($request->get_param('bSortable_' . $request->get_param('iSortCol_' . $i)) === "true") {
                            if (!empty($request->get_param('mDataProp_' . $request->get_param('iSortCol_' . $i)))) {
                                $column = sanitize_text_field(wp_unslash($request->get_param('mDataProp_' . $request->get_param('iSortCol_' . $i))));
                                if (!empty($request->get_param('sSortDir_' . $i))) {
                                    $direction = sanitize_text_field(wp_unslash($request->get_param('sSortDir_' . $i)));
                                }
                                $orderArr[] = esc_sql($column) . " " . esc_sql($direction);
                            }
                        }
                    }
                }
                if (!empty($orderArr)) {
                    $sOrder = "ORDER BY " . implode(", ", $orderArr);
                }
            }

            // Taxonomy filter
            $sWhere = '';
            if (!empty($request->get_param('taxonomy'))) {
                $safe_taxonomy = sanitize_text_field(wp_unslash($request->get_param('taxonomy')));
                $sWhere        .= " WHERE {$wpdb->prefix}term_taxonomy.taxonomy = %s ";
                $queryParams[] = $safe_taxonomy;
            }

            // Search filter
            if (!empty($request->get_param('sSearch'))) {
                $safeSearch      = '%' . sanitize_text_field(wp_unslash($request->get_param('sSearch'))) . '%';
                $searchCondition = "{$wpdb->prefix}terms.name LIKE %s OR {$wpdb->prefix}terms.term_id LIKE %s OR {$wpdb->prefix}term_taxonomy.description LIKE %s";

                if (empty($sWhere)) {
                    $sWhere .= " WHERE ";
                } else {
                    $sWhere .= " AND ";
                }

                $sWhere        .= "($searchCondition)";
                $queryParams[] = $safeSearch;
                $queryParams[] = $safeSearch;
                $queryParams[] = $safeSearch;
            }

            // Build final SQL query
            $columns = implode(", ", array_map('esc_sql', $aColumns));


            // Execute query with a single prepare call
            $rResult = $wpdb->get_results(
                $wpdb->prepare(
                    "
    SELECT SQL_CALC_FOUND_ROWS {$columns}
    FROM {$sTable}
    JOIN {$wpdb->prefix}term_taxonomy ON {$wpdb->prefix}term_taxonomy.term_id = {$wpdb->prefix}terms.term_id
    {$sWhere}
    {$sOrder}
    {$sLimit}
", ...$queryParams
                ), ARRAY_A
            );

            // Get total records after filtering
            $iFilteredTotal = $wpdb->get_var("SELECT FOUND_ROWS()");

            // Total data set length
            $iTotal = $wpdb->get_var("SELECT COUNT({$sIndexColumn}) FROM {$sTable}");

            // Additional processing for schema and other information
            foreach ($rResult as $i => $row) {
                $term = get_term($row['term_id']);
                if ($term && !is_wp_error($term)) {
                    $id                    = $term->term_id;
                    $rResult[$i]['schema'] = XAGIO_MODEL_SCHEMA::getSchemas($row['term_id'], 'term');
                } else {
                    $rResult[$i]['schema'] = false;
                }
            }

            // Output
            $output = [
                "sEcho"                => !empty($request->get_param('sEcho')) ? intval($request->get_param('sEcho')) : 0,
                "iTotalRecords"        => $iTotal,
                "iTotalDisplayRecords" => $iFilteredTotal,
                "aaData"               => $rResult,
            ];

            wp_send_json($output);
            wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
        }

        public static function searchPosts($request = null)
        {
            global $wpdb;

            $aColumns = [
                'ID',
                'post_author',
                'post_date',
                'post_title',
                'post_status',
                'comment_status',
                'post_name',
                'post_parent',
                'guid',
                'post_type',
                'comment_count',
            ];

            $sIndexColumn = "ID";
            $sTable       = $wpdb->prefix . 'posts';

            // Initialize parameters array for placeholders
            $queryParams = [];

            // Paging
            $sLimit = "LIMIT 0, 50";

            // Ordering
            $sOrder = '';
            if (!empty($request->get_param('iSortCol_0')) && !empty($request->get_param('iSortingCols'))) {
                $orderArr = [];
                for ($i = 0; $i < intval($request->get_param('iSortingCols')); $i++) {
                    if (!empty($request->get_param('iSortCol_' . $i)) && !empty($request->get_param('bSortable_' . $request->get_param('iSortCol_' . $i))) && $request->get_param('bSortable_' . $request->get_param('iSortCol_' . $i)) == "true") {
                        if (!empty($request->get_param('mDataProp_' . $request->get_param('iSortCol_' . $i))) && !empty($request->get_param('sSortDir_' . $i))) {
                            $column     = sanitize_text_field(wp_unslash($request->get_param('mDataProp_' . $request->get_param('iSortCol_' . $i))));
                            $direction  = sanitize_text_field(wp_unslash($request->get_param('sSortDir_' . $i)));
                            $orderArr[] = esc_sql($column) . " " . esc_sql($direction);
                        }
                    }
                }
                if (!empty($orderArr)) {
                    $sOrder = "ORDER BY " . implode(", ", $orderArr);
                }
            }

            if (!empty($request->get_param('PostsType'))) {
                $sWhere        = " WHERE post_type = %s ";
                $queryParams[] = sanitize_text_field(wp_unslash($request->get_param('PostsType')));
            } else {
                // Determine Post Types
                $allowedPostTypes = XAGIO_MODEL_SEO::getAllPostTypes();
                $sWhere           = " WHERE post_type IN (" . implode(",", array_fill(0, count($allowedPostTypes), '%s')) . ") ";
                $queryParams      = array_merge($queryParams, $allowedPostTypes);
            }

            // Add post status condition
            $sWhere .= " AND post_status IN ('publish', 'future', 'draft', 'pending') ";

            // Search filter
            if (!empty($request->get_param('sSearch'))) {
                $safeSearch    = sanitize_text_field(wp_unslash($request->get_param('sSearch')));
                $sWhere        .= " AND (post_title LIKE CONCAT(CHAR(37), %s, CHAR(37)) OR ID LIKE CONCAT(CHAR(37), %s, CHAR(37)) OR post_name LIKE CONCAT(CHAR(37), %s, CHAR(37))) ";
                $queryParams[] = $safeSearch;
                $queryParams[] = $safeSearch;
                $queryParams[] = $safeSearch;
            }

            if ($request->get_param('iDisplayStart') !== null && !empty($request->get_param('iDisplayLength')) && $request->get_param('iDisplayLength') != '-1') {
                $sLimit        = "LIMIT %d, %d";
                $queryParams[] = intval($request->get_param('iDisplayStart'));
                $queryParams[] = intval($request->get_param('iDisplayLength'));
            }

            // Build final SQL query
            $columns = implode(", ", array_map('esc_sql', $aColumns));

            // Execute query with a single prepare call
            $rResult = $wpdb->get_results(
                $wpdb->prepare(
                    "
    SELECT SQL_CALC_FOUND_ROWS {$columns}
    FROM {$sTable}
    {$sWhere}
    {$sOrder}
    {$sLimit}
", ...$queryParams
                ), ARRAY_A
            );

            // Get total records after filtering
            $iFilteredTotal = $wpdb->get_var("SELECT FOUND_ROWS()");

            // Total data set length
            $iTotal = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(%s) FROM {$sTable}", esc_sql($sIndexColumn),
                )
            );

            // Additional processing for schema and other information
            $aChildren = [];
            foreach ($rResult as &$row) {
                $children        = get_pages(['child_of' => $row['ID']]);
                $row['page_url'] = esc_url(get_permalink($row['ID']));

                if ($children) {
                    $aChildren[] = XAGIO_MODEL_PROJECTS::getAncestorTree($row['ID']);
                }

                $row = XAGIO_MODEL_PROJECTS::generateSiloPageArray($row);
            }

            // Reordering children elements
            $tempResults = [];
            foreach ($aChildren as $tree) {
                foreach ($tree as $child_id) {
                    foreach ($rResult as $key => $rows) {
                        if ((int)$child_id === (int)$rows['ID']) {
                            $tempResults[] = $rows;
                            array_splice($rResult, $key, 1);
                            break;
                        }
                    }
                }
            }

            $rResult = array_merge($rResult, $tempResults);

            // Output
            $output = [
                "sEcho"                => !empty($request->get_param('sEcho')) ? intval($request->get_param('sEcho')) : 0,
                "iTotalRecords"        => $iTotal,
                "iTotalDisplayRecords" => $iFilteredTotal,
                "aaData"               => $rResult,
            ];

            wp_send_json($output);
            wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
        }


        public static function allComments($request = null)
        {

            global $wpdb;

            $aColumns = [
                'comment_ID',
                'comment_post_ID',
                'comment_author',
                'comment_author_email',
                'comment_author_url',
                'comment_date',
                'comment_content',
                'comment_approved',
                'comment_parent',
            ];

            $sIndexColumn = "comment_ID";
            $sTable       = $wpdb->prefix . 'comments';

            // Initialize parameters array for placeholders
            $queryParams = [];

            // Paging
            $sLimit = "LIMIT 0, 50";
            if (!empty($request->get_param('iDisplayStart')) && !empty($request->get_param('iDisplayLength')) && $request->get_param('iDisplayLength') != '-1') {
                $sLimit        = "LIMIT %d, %d";
                $queryParams[] = intval($request->get_param('iDisplayStart'));
                $queryParams[] = intval($request->get_param('iDisplayLength'));
            }

            // Ordering
            $sOrder = '';
            if (!empty($request->get_param('iSortCol_0')) && !empty($request->get_param('iSortingCols'))) {
                $orderArr = [];
                for ($i = 0; $i < intval($request->get_param('iSortingCols')); $i++) {
                    if (!empty($request->get_param('iSortCol_' . $i)) && !empty($request->get_param('bSortable_' . $request->get_param('iSortCol_' . $i))) && $request->get_param('bSortable_' . $request->get_param('iSortCol_' . $i)) == "true") {
                        if (!empty($request->get_param('mDataProp_' . $request->get_param('iSortCol_' . $i))) && !empty($request->get_param('sSortDir_' . $i))) {
                            $column     = sanitize_text_field(wp_unslash($request->get_param('mDataProp_' . $request->get_param('iSortCol_' . $i))));
                            $direction  = sanitize_text_field(wp_unslash($request->get_param('sSortDir_' . $i)));
                            $orderArr[] = esc_sql($column) . " " . esc_sql($direction);
                        }
                    }
                }
                if (!empty($orderArr)) {
                    $sOrder = "ORDER BY " . implode(", ", $orderArr);
                }
            }

            // Filtering
            $sWhere = " WHERE comment_type = 'comment'";

            // Comment state filter
            if (!empty($request->get_param('CommentState')) && $request->get_param('CommentState') != '') {
                $commentState  = sanitize_text_field(wp_unslash($request->get_param('CommentState')));
                $sWhere        .= " AND comment_approved = %s";
                $queryParams[] = $commentState;
            }

            // Search filter
            if (!empty($request->get_param('sSearch')) && !empty($request->get_param('sSearch'))) {
                $safeSearch    = '%' . sanitize_text_field(wp_unslash($request->get_param('sSearch'))) . '%';
                $sWhere        .= " AND (comment_content LIKE %s OR comment_ID LIKE %s OR comment_author LIKE %s)";
                $queryParams[] = $safeSearch;
                $queryParams[] = $safeSearch;
                $queryParams[] = $safeSearch;
            }

            // Build final SQL query
            $columns = implode(", ", array_map('esc_sql', $aColumns));

            // Execute query with a single prepare call
            $rResult = $wpdb->get_results(
                $wpdb->prepare(
                    "
    SELECT SQL_CALC_FOUND_ROWS {$columns}
    FROM {$sTable}
    {$sWhere}
    {$sOrder}
    {$sLimit}
", ...$queryParams
                ), ARRAY_A
            );

            // Get total records after filtering
            $iFilteredTotal = $wpdb->get_var("SELECT FOUND_ROWS()");

            // Total data set length
            $iTotal = $wpdb->get_var(
                "SELECT COUNT({$sIndexColumn}) FROM {$sTable} WHERE comment_type = 'comment'"
            );

            // Additional processing for schema and other information
            foreach ($rResult as &$row) {
                $row['author_email_hash'] = md5(strtolower(trim($row['comment_author_email'])));

                if ($row['comment_parent'] != 0) {
                    $parent_comment        = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT comment_author FROM {$sTable} WHERE comment_ID = %d", $row['comment_parent']
                        ), ARRAY_A
                    );
                    $row['parent_comment'] = $parent_comment['comment_author'] ?? false;
                } else {
                    $row['parent_comment'] = false;
                }

                $row['post_title'] = esc_html(get_the_title($row['comment_post_ID']));
                $row['post_url']   = esc_url(get_permalink($row['comment_post_ID']));
            }

            // Count comments by state
            $commentCount = $wpdb->get_results(
                "SELECT comment_approved, COUNT(comment_approved) as num 
     FROM {$sTable} 
     WHERE comment_type = 'comment' 
     GROUP BY comment_approved", ARRAY_A
            );


            $temp = [
                'pending'  => 0,
                'approved' => 0,
                'spam'     => 0,
                'trash'    => 0,
            ];
            foreach ($commentCount as $c) {
                switch ($c['comment_approved']) {
                    case "0":
                        $temp['pending'] = $c['num'];
                        break;
                    case "1":
                        $temp['approved'] = $c['num'];
                        break;
                    default:
                        $temp[$c['comment_approved']] = $c['num'];
                        break;
                }
            }

            // Output
            $output = [
                "sEcho"                => !empty($request->get_param('sEcho')) ? intval($request->get_param('sEcho')) : 1,
                "iTotalRecords"        => $iTotal,
                "iTotalDisplayRecords" => $iFilteredTotal,
                "aaData"               => $rResult,
                "commentCount"         => $temp,
            ];

            wp_send_json($output);
            wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
        }


        /***************************************************************************************************************
         *
         *   Handlers
         *
         **************************************************************************************************************/

        static $class_renames = [
            'MXAG_Ai' => 'XAGIO_MODEL_AI',
        ];

        // Handle Request
        public static function handleRequest($request = null)
        {
            if ($request == null) {
                wp_die('Invalid request type.');
            }

            $function = sanitize_text_field(wp_unslash($request->get_param('function')));
            $class    = sanitize_text_field(wp_unslash($request->get_param('class')));

            // Perform class renaming (old Plugin)
            if (!empty(self::$class_renames[$class])) {
                $class = self::$class_renames[$class];
            }

            if (!empty($class)) {

                if (method_exists($class, $function)) {

                    call_user_func([
                        $class,
                        $function
                    ], $request);

                } else {
                    xagio_json('error', 'Requested class/method does not exist! Please update your plugin on this website to the latest version!');
                }

            } else {

                if ($function === 'syncServerAPI') {

                    call_user_func([
                        'XAGIO_API',
                        'syncServerAPI'
                    ], $request);

                } else {

                    if (method_exists('XAGIO_API', $function)) {
                        call_user_func([
                            'XAGIO_API',
                            $function
                        ], $request);
                    } else {
                        xagio_json('error', 'Requested API function does not exist! Please update your plugin on this website to the latest version!');
                    }

                }

            }
        }

        public static function getAPIKey($regenerate = false)
        {
            // Define the length of the password
            $pw_length = 24; // You can define this as static::PW_LENGTH elsewhere

            // Get the first administrator user
            $admin_users = get_users(array(
                'role'    => 'administrator',
                'orderby' => 'ID',
                'order'   => 'ASC',
                'number'  => 1
            ));

            if (!empty($admin_users)) {
                $admin_user = $admin_users[0];
                $username   = $admin_user->user_login;  // Get the username

                // Get existing application passwords from user meta
                $app_passwords = get_user_meta($admin_user->ID, '_application_passwords', true);

                // Check if there is already an API key entry for 'XAGIO_API'
                $existing_api_key = null;
                if (is_array($app_passwords)) {
                    foreach ($app_passwords as $password_entry) {
                        if ($password_entry['name'] === 'XAGIO_API') {
                            $existing_api_key = $password_entry;
                            break;
                        }
                    }
                } else {
                    $app_passwords = array();
                }
                // If there is no existing app password key and old XAGIO_API is present, regenerate
                $send_to_panel = false;
                $XAGIO_API     = get_option('XAGIO_API');
                if ($XAGIO_API != false) {
                    if (!xagio_is_base64($XAGIO_API) && $existing_api_key == null) {
                        $send_to_panel = true;
                        $regenerate    = true;
                    }
                }

                // If an API key for 'XAGIO_API' does not exist, generate a new one
                if ($regenerate == true) {
                    // Generate a new application password
                    $new_password    = wp_generate_password($pw_length, false); // 24 characters, no special characters
                    $hashed_password = wp_hash_password($new_password);

                    // Prepare new application password entry
                    $new_item = array(
                        'uuid'      => wp_generate_uuid4(),
                        // Generate a unique UUID
                        'app_id'    => '',
                        // Can set or pass custom app_id if needed
                        'name'      => 'XAGIO_API',
                        // Name of the application password
                        'password'  => $hashed_password,
                        // Hashed password stored
                        'created'   => time(),
                        // Time of creation (Unix timestamp)
                        'last_used' => null,
                        // Last used time (null until used)
                        'last_ip'   => null,
                        // Last used IP (null until used)
                    );

                    if (!empty($existing_api_key)) {

                        // If an existing API key is found, replace the current entry with the new one
                        foreach ($app_passwords as $key => $password_entry) {
                            if ($password_entry['name'] == 'XAGIO_API') {
                                // Update the existing entry with new password and timestamp
                                $app_passwords[$key] = $new_item;
                                break;
                            }
                        }

                    } else {

                        // Add the new application password entry to the array
                        $app_passwords[] = $new_item;

                    }

                    // Prepare for sending
                    $encoded_password = base64_encode($username . ":" . $new_password . ':' . $hashed_password);

                    // Update the user meta with the new password list
                    update_user_meta($admin_user->ID, '_application_passwords', $app_passwords);

                    // Update the panel api key
                    if ($send_to_panel) {
                        self::apiRequest('migrate_license', 'POST', [
                            'api_key'    => $encoded_password,
                            'admin_post' => rest_url() . 'xagio-seo/v1/'
                        ]);
                    }

                    update_option('XAGIO_API', $hashed_password);
                    update_option('using_application_passwords', true);

                    if ($send_to_panel) {
                        return $hashed_password;
                    }

                    return $encoded_password;
                } else {
                    return $existing_api_key['password'] ?? '';
                }
            } else {
                return false; // No administrator users found
            }
        }

        public static function downloadFile($location, $file)
        {
            include_once ABSPATH . 'wp-admin/includes/file.php';

            // Initialize the WP Filesystem
            global $wp_filesystem;
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            $creds = request_filesystem_credentials(site_url() . '/wp-admin/', '', false, false, []);
            if (!WP_Filesystem($creds)) {
                return false;
            }

            // Delete the existing file if it exists
            if ($wp_filesystem->exists($location)) {
                $wp_filesystem->delete($location);
            }

            // Download the file using wp_remote_get
            $response = wp_remote_get($file, [
                'timeout'  => 60,
                'stream'   => true,
                'filename' => $location
            ]);

            // Check for errors
            if (is_wp_error($response)) {
                return false;
            }

            return true;
        }

        /***************************************************************************************************************
         *
         *   API Functions
         *
         **************************************************************************************************************/

        /**
         *  Install Plugin or Theme from Upload
         */

        public static function installFromUpload($request = null)
        {


            // Check if required POST parameters are set
            if (empty($request->get_param('slug')) || empty($request->get_param('type')) || empty($request->get_param('package'))) {
                xagio_json('error', 'Invalid request!');
            }

            // Sanitize input data
            $slug    = sanitize_text_field(wp_unslash($request->get_param('slug')));
            $type    = sanitize_text_field(wp_unslash($request->get_param('type')));
            $package = esc_url_raw(wp_unslash($request->get_param('package')));

            // Validate the type (either 'plugins' or 'themes')
            if (!in_array($type, [
                'plugins',
                'themes'
            ], true)) {
                xagio_json('error', 'Invalid type specified!');
            }

            // Handle the downloaded file
            $root_directory = get_home_path();
            $plugin_path    = $root_directory . sanitize_file_name($slug) . '.zip';

            // Download the file
            if (!XAGIO_API::downloadFile($plugin_path, $package)) {
                xagio_json('error', 'Failed to download the package.');
            }

            $result = false;
            $error  = '';

            // Install the plugin or theme based on the type
            if ($type === 'plugins') {
                $result = XAGIO_MODEL_QUICKWPSETUP::installWordPressPlugin($slug, $plugin_path, $error);
                if ($result) {
                    @activate_plugin($result);
                }
            } elseif ($type === 'themes') {
                $result = XAGIO_MODEL_QUICKWPSETUP::installWordPressTheme($slug, $plugin_path, $error);
            }

            // Handle the installation result
            if (!$result) {
                xagio_json('error', 'Managed to upload, but failed to install specified file.', $error);
            } else {
                xagio_json('success', 'Successfully installed specified file.');
                self::syncWithPanel();
            }
        }


        /**
         *  Install Plugins from WordPress Repository
         */
        public static function installPluginFromRepo($request = null)
        {


            // Check if the 'slug' parameter is set in the POST request
            if (!!empty($request->get_param('slug'))) {
                xagio_json('error', 'Invalid request!');
            }

            // Sanitize the slug
            $slug = sanitize_text_field(wp_unslash($request->get_param('slug')));

            // Download the plugin from the WordPress repository
            $plugin_path = XAGIO_MODEL_QUICKWPSETUP::downloadWordPressPlugin($slug);
            if (!$plugin_path) {
                xagio_json('error', 'Failed to download specified plugin.');
            } else {
                // Attempt to install the downloaded plugin
                $result = XAGIO_MODEL_QUICKWPSETUP::installWordPressPlugin($slug, $plugin_path);
                if (!$result) {
                    xagio_json('error', 'Managed to download, but failed to install specified plugin.');
                } else {
                    // Activate the plugin after successful installation
                    @activate_plugin($result);
                    xagio_json('success', 'Successfully installed specified plugin.');
                    self::syncWithPanel();
                }
            }
        }


        /**
         *  Install Themes from WordPress Repository
         */
        public static function installThemeFromRepo($request = null)
        {


            // Check if the 'slug' parameter is set in the POST request
            if (!!empty($request->get_param('slug'))) {
                xagio_json('error', 'Invalid request!');
            }

            // Sanitize the slug
            $slug = sanitize_text_field(wp_unslash($request->get_param('slug')));

            // Download the theme from the WordPress repository
            $theme_path = XAGIO_MODEL_QUICKWPSETUP::downloadWordPressTheme($slug);
            if (!$theme_path) {
                xagio_json('error', 'Failed to download specified theme.');
            } else {
                // Attempt to install the downloaded theme
                $result = XAGIO_MODEL_QUICKWPSETUP::installWordPressTheme($slug, $theme_path);
                if (!$result) {
                    xagio_json('error', 'Managed to download, but failed to install specified theme.');
                } else {
                    xagio_json('success', 'Successfully installed specified theme.');
                    self::syncWithPanel();
                }
            }
        }


        /**
         *   Restore a Backup
         */
        public static function restoreBackup($request = null)
        {


            // Sanitize and validate the input data
            $storage   = !empty($request->get_param('storage')) ? sanitize_text_field(wp_unslash($request->get_param('storage'))) : '';
            $backup    = !empty($request->get_param('backup')) ? sanitize_text_field(wp_unslash($request->get_param('backup'))) : '';
            $backup_id = !empty($request->get_param('backup_id')) ? intval($request->get_param('backup_id')) : 0;

            // Validate that required parameters are provided
            if (empty($storage) || empty($backup) || $backup_id === 0) {
                xagio_json('error', 'Invalid request parameters.');
                return;
            }

            // Since this is async, store the result
            $result = XAGIO_MODEL_BACKUPS::restoreBackupHandler($storage, $backup, $backup_id);

            // Send the notification to the panel
            xagio_jsonc($result);
        }

        /**
         *   Create a Backup for Cloning purposes
         */
        public static function createCloneBackup($request = null)
        {
            xagio_jsonc(XAGIO_MODEL_BACKUPS::doCloneBackup());
        }

        /**
         *   Download cloned backup
         */
        public static function removeCloneBackup($request = null)
        {


            // Sanitize the backup name
            $backup_name = !empty($request->get_param('backup_name')) ? sanitize_file_name(wp_unslash($request->get_param('backup_name'))) : '';

            // Construct the backup path
            $backup_path = XAGIO_PATH . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $backup_name;

            // Check if the backup file exists and is a file
            if (!empty($backup_name) && file_exists($backup_path) && is_file($backup_path)) {
                wp_delete_file($backup_path);
                xagio_json('success', 'Successfully removed backup file!');
            } else {
                xagio_json('error', 'Specified backup file does not exist or is not a valid file!');
            }
        }

        /**
         *   Create a Backup manually
         */
        public static function createBackup($request = null)
        {
            xagio_jsonc(XAGIO_MODEL_BACKUPS::doBackup());
        }

        /**
         * Set Backup Limit
         */
        public static function setBackupLimit($request = null)
        {


            XAGIO_SYNC::getBackupSettings();

            // Sanitize and validate the backup limit
            $backup_limit = !empty($request->get_param('backup_limit')) ? intval($request->get_param('backup_limit')) : 0;

            if ($backup_limit > 0) {
                update_option('XAGIO_BACKUP_LIMIT', $backup_limit);
                xagio_json('success', 'Backup Limit has been updated!');
            } else {
                xagio_json('error', 'Invalid Backup Limit value!');
            }
        }

        /**
         * Set Backup Location
         */
        public static function setBackupLocation($request = null)
        {


            XAGIO_SYNC::getBackupSettings();

            // Sanitize the backup location
            $backup_location = !empty($request->get_param('backup_location')) ? sanitize_text_field(wp_unslash($request->get_param('backup_location'))) : '';

            if (!empty($backup_location)) {
                update_option('XAGIO_BACKUP_LOCATION', $backup_location);
                xagio_json('success', 'Backup Storage has been updated!');
            } else {
                xagio_json('error', 'Invalid Backup Location value!');
            }
        }


        /**
         * Set Backup Date
         */
        public static function setBackupDate($request = null)
        {


            // Sanitize the backup date
            $backup_date = !empty($request->get_param('backup_date')) ? sanitize_text_field(wp_unslash($request->get_param('backup_date'))) : '';

            if (!empty($backup_date)) {
                update_option('XAGIO_BACKUP_DATE', $backup_date);

                // Init the cronjob
                wp_unschedule_event(wp_next_scheduled('xagio_doBackup'), 'xagio_doBackup');

                if ($backup_date !== 'never') {
                    if (!wp_next_scheduled('xagio_doBackup')) {
                        XAGIO_SYNC::getBackupSettings();
                        wp_schedule_event(time(), $backup_date, 'xagio_doBackup');
                    }
                }

                xagio_json('success', 'Backup Schedule has been updated!');
            } else {
                xagio_json('error', 'Invalid Backup Date value!');
            }
        }


        /**
         * Deactivate Plugin - From Panel
         * This will only deactivate plugin
         */
        public static function deactivatePlugin($request = null)
        {
            deactivate_plugins("xagio-seo/xagio-seo.php");
            xagio_json('success', 'Plugin has been successfully deactivated!');
        }


        /**
         *   Hidden Plugin Status
         */
        public static function getPluginHiddenStatus()
        {
            $status = get_option('XAGIO_HIDDEN');
            xagio_json('success', 'Successfully retrieved data.', $status);
        }

        /**
         *   Hide Plugin
         */
        public static function hidePlugin($request = null)
        {
            update_option('XAGIO_HIDDEN', TRUE);
            xagio_json('success', 'Plugin has been successfully hidden from Admin Area!');
        }

        /**
         *   Show Plugin
         */
        public static function showPlugin($request = null)
        {
            update_option('XAGIO_HIDDEN', FALSE);
            xagio_json('success', 'Plugin has been successfully shown on Admin Area!');
        }

        /**
         * Toggle Schema Force ON
         */
        public static function toggleSchemaAlwaysOn($request = null)
        {


            // Sanitize and validate the input value
            $value = !empty($request->get_param('value')) ? intval($request->get_param('value')) : 0;

            // Update the option
            update_option('XAGIO_SCHEMA_ALWAYS_ON', $value);

            // Provide a success message based on the value
            if ($value === 1) {
                xagio_json('success', 'Force Homepage Schemas has been enabled!');
            } else {
                xagio_json('success', 'Force Homepage Schemas has been disabled!');
            }
        }


        /**
         * Toggle reCAPTCHA
         */
        public static function toggleRecaptcha($request = null)
        {


            $value = !empty($request->get_param('value')) ? intval($request->get_param('value')) : 0;
            update_option('XAGIO_RECAPTCHA', $value);
            if ($value === 1) {
                XAGIO_SYNC::getAPIKeys();
                xagio_json('success', 'reCAPTCHA has been enabled!');
            } else {
                xagio_json('success', 'reCAPTCHA has been disabled!');
            }
        }


        public static function wipePlugin($request = null)
        {
            XAGIO_LICENSE::removeLicense();
            xagio_json('success', 'Plugin has been successfully wiped!');
        }

        /**
         * Download WordPress Plugin to Panel
         */
        public static function downloadPlugin($request = null)
        {
            try {
                // Check if plugin name is set
                if (empty($request->get_param('plugin'))) {
                    xagio_json('error', 'Invalid request!');
                }

                $plugin_slug_with_file = sanitize_text_field(wp_unslash($request->get_param('plugin')));
                $plugin_slug_parts = explode('/', $plugin_slug_with_file); // Split by '/' to extract the directory
                $plugin_slug = $plugin_slug_parts[0]; // The first part is the directory name

                $temp_dir    = XAGIO_PATH . '/tmp/';
                $plugin_dir  = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_slug . DIRECTORY_SEPARATOR;
                $zip_name    = basename($plugin_slug);
                $zip_path    = $temp_dir . $zip_name . '.zip';


                // Check if temp dir exists, create if not
                if (!file_exists($temp_dir)) {
                    xagio_mkdir($temp_dir, 0777, true);
                }

                // Validate plugin directory
                if (!file_exists($plugin_dir) || !is_dir($plugin_dir)) {
                    xagio_json('error', 'Plugin directory does not exist or is not a valid directory!');
                }

                // Check if ZipArchive is available
                if (!class_exists('ZipArchive')) {
                    xagio_json('error', 'ZipArchive is not installed.');
                }

                // Initialize ZipArchive
                $archive = new ZipArchive();
                if ($archive->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    xagio_json('error', 'Cannot create zip archive! Ensure write permissions are set.');
                }

                // Add plugin files to the ZIP archive
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($plugin_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($files as $file) {
                    $file_path = $file->getRealPath();
                    $relative_path = substr($file_path, strlen($plugin_dir));
                    if ($file->isDir()) {
                        $archive->addEmptyDir($plugin_slug . '/' . $relative_path);
                    } else {
                        $archive->addFile($file_path, $plugin_slug . '/' . $relative_path);
                    }
                }

                $archive->close();

                // Check if the zipping has been successful
                if (!file_exists($zip_path)) {
                    xagio_json('error', 'Failed to create zip file from plugin directory!');
                }

                // Upload the zipped plugin
                $result = XAGIO_API::apiRequestUpload('plugins_upload', $zip_path);
                if ($result && !empty($result['message'])) {
                    xagio_json('success', $result['message']);
                } else {
                    xagio_json('error', 'Failed to upload the plugin zip file.');
                }
            } catch (Exception $e) {
                xagio_json('error', 'An error occurred. Please pass this down to support: ' . $e->getMessage());
            }
        }


        /**
         * Activate WordPress Plugins
         */
        public static function activateWpPlugins($request = null)
        {


            try {
                // Check if plugin names are set
                if (empty($request->get_param('pluginNames'))) {
                    xagio_json('error', 'Invalid request!');
                }

                $plugins     = sanitize_text_field(wp_unslash($request->get_param('pluginNames')));
                $pluginNames = is_array($plugins) ? array_map('sanitize_text_field', wp_unslash($plugins)) : [sanitize_text_field(wp_unslash($plugins))];

                $error   = [];
                $success = [];

                foreach ($pluginNames as $pluginName) {
                    $result = activate_plugin($pluginName);
                    if (is_wp_error($result)) {
                        $error[] = $pluginName;
                    } else {
                        $success[] = $pluginName;
                    }
                }

                $error_status = !empty($error);

                xagio_json('success', 'Operation successfully finished.', [
                    'success'      => $success,
                    'error'        => $error,
                    'error_status' => $error_status
                ]);
            } catch (Exception $e) {
                xagio_json('error', 'Plugin error occurred. Please pass this down to support: ' . $e->getMessage());
            }
        }

        /**
         * Deactivate WordPress Plugins - From Panel
         */
        public static function deactivateWpPlugins($request = null)
        {


            try {
                // Check if plugin names are set
                if (empty($request->get_param('pluginNames'))) {
                    xagio_json('error', 'Invalid request!');
                }

                $plugins     = map_deep(wp_unslash($request->get_param('pluginNames')), 'sanitize_text_field');
                $pluginNames = is_array($plugins) ? array_map('sanitize_text_field', wp_unslash($plugins)) : [sanitize_text_field(wp_unslash($plugins))];

                foreach ($pluginNames as $pluginName) {
                    deactivate_plugins($pluginName);
                }

                xagio_json('success', 'Operation successfully finished.');
            } catch (Exception $e) {
                xagio_json('error', 'Plugin error occurred. Please pass this down to support: ' . $e->getMessage());
            }
        }

        /**
         * Delete WordPress Plugins
         */
        public static function deleteWpPlugins($request = null)
        {


            try {
                // Check if plugin names are set
                if (empty($request->get_param('pluginNames'))) {
                    xagio_json('error', 'Invalid request!');
                }

                $plugins     = sanitize_text_field(wp_unslash($request->get_param('pluginNames')));
                $pluginNames = is_array($plugins) ? array_map('sanitize_text_field', wp_unslash($plugins)) : [sanitize_text_field(wp_unslash($plugins))];

                require_once ABSPATH . 'wp-admin/includes/plugin.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';

                $status = delete_plugins($pluginNames);

                xagio_json('success', 'Operation successfully finished.', $status);
            } catch (Exception $e) {
                xagio_json('error', 'Plugin error occurred. Please pass this down to support: ' . $e->getMessage());
            }
        }

        /**
         * Activate WordPress Theme
         */
        public static function activateWpTheme($request = null)
        {


            try {
                // Check if theme slug is set
                if (empty($request->get_param('slug'))) {
                    xagio_json('error', 'Invalid request!');
                }

                $slug  = sanitize_text_field(wp_unslash($request->get_param('slug')));
                $theme = wp_get_theme($slug);

                if ($theme->exists()) {
                    switch_theme($slug);
                    xagio_json('success', 'Theme successfully changed.');
                } else {
                    xagio_json('error', 'Theme doesn\'t exist.');
                }
            } catch (Exception $e) {
                xagio_json('error', 'An error occurred. Please pass this down to support: ' . $e->getMessage());
            }
        }

        /**
         * Download WordPress Theme to Panel
         */
        public static function downloadTheme($request = null)
        {
            try {
                // Check if theme name is set
                if (empty($request->get_param('theme'))) {
                    xagio_json('error', 'Invalid request!');
                }

                $theme_slug = sanitize_text_field(wp_unslash($request->get_param('theme')));
                $temp_dir   = XAGIO_PATH . '/tmp/';

                // Create temp directory if it doesn't exist
                if (!file_exists($temp_dir)) {
                    xagio_mkdir($temp_dir, 0777, true);
                }

                $theme_dir   = get_theme_root() . DIRECTORY_SEPARATOR . $theme_slug . DIRECTORY_SEPARATOR;
                $zip_name    = basename($theme_slug);
                $zip_path    = $temp_dir . $zip_name . '.zip';

                // Validate theme directory
                if (!file_exists($theme_dir) || !is_dir($theme_dir)) {
                    xagio_json('error', 'Theme directory does not exist or is not a valid directory!');
                }

                if (!class_exists('ZipArchive')) {
                    xagio_json('error', 'ZipArchive is not installed.');
                }

                // Initialize ZipArchive
                $archive = new ZipArchive();
                if ($archive->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    xagio_json('error', 'Cannot create zip archive! Ensure write permissions are set.');
                }

                // Add theme files to the ZIP archive
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($theme_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($files as $file) {
                    $file_path = $file->getRealPath();
                    $relative_path = substr($file_path, strlen($theme_dir));
                    if ($file->isDir()) {
                        $archive->addEmptyDir($theme_slug . '/' . $relative_path);
                    } else {
                        $archive->addFile($file_path, $theme_slug . '/' . $relative_path);
                    }
                }

                $archive->close();

                // Check if the zipping was successful
                if (!file_exists($zip_path)) {
                    xagio_json('error', 'Failed to create zip file out of theme directory!');
                }

                // Upload the zipped theme
                $result = XAGIO_API::apiRequestUpload('themes_upload', $zip_path);
                if ($result && !empty($result['message'])) {
                    xagio_json('success', $result['message']);
                } else {
                    xagio_json('error', 'Failed to upload the theme zip file.');
                }
            } catch (Exception $e) {
                xagio_json('error', 'An error occurred. Please pass this down to support: ' . $e->getMessage());
            }
        }


        /**
         * Remote Login
         */
        public static function remoteLogin($request = null)
        {
            if (is_user_logged_in()) {
                wp_redirect(admin_url('admin.php?page=xagio-dashboard'));
                exit;
            }

            // Get all admin users
            $admins = get_users([
                'role'   => 'administrator',
                'fields' => ['ID'],
            ]);

            // Determine the user to login
            if (!empty($request->get_param('ID')) && intval($request->get_param('ID')) !== 0) {
                $remoteLoginUserID = intval($request->get_param('ID'));
                $user_info         = get_userdata($remoteLoginUserID);
            } else {
                $user_info = get_userdata($admins[0]->ID);
            }

            if ($user_info) {
                $username = $user_info->user_login;

                // Log the user in
                $user = get_user_by('login', $username);
                if ($user) {
                    wp_set_current_user($user->ID, $username);
                    wp_set_auth_cookie($user->ID);
                    do_action('wp_login', $username, $user);
                }
            } else {
                xagio_json('error', 'User not found.');
            }

            // Clean up
            delete_transient('xagio_remoteLoginToken');

            // Redirect to the admin dashboard
            wp_redirect(admin_url());
            exit;
        }


        /**
         *   Get Temp Token
         */
        public static function getTempToken($request = null)
        {
            set_transient('xagio_remoteLoginToken', md5(gmdate('Y-m-d H:i:s') . microtime() . XAGIO_AUTH_KEY . XAGIO_AUTH_SALT), 30);

            //Get all admin Users
            $users = get_users([
                'role'   => 'administrator',
                'fields' => [
                    'ID',
                    'user_login'
                ],
            ]);

            $response                           = [];
            $response['xagio_remoteLoginToken'] = get_transient('xagio_remoteLoginToken');
            $response['remoteLoginUsers']       = $users;

            xagio_json('success', $response);
        }

        /**
         *   Get Available Updates
         * @param boolean $array Set crone to true to return array
         * @return void|array
         */
        public static function getUpdates($array = FALSE)
        {
            // Ensure that update functions are available
            if (!function_exists('get_core_updates')) {
                require_once(ABSPATH . 'wp-admin/includes/update.php');
            }

            wp_update_plugins(array());
            wp_update_themes(array());

            // Skip paid plugins/themes
            $plugins_raw = get_plugin_updates();
            $themes_raw  = get_theme_updates();

            $plugins = [];
            $themes  = [];

            foreach ($plugins_raw as $plugin => $data) {
                if (!empty($data->update)) {
                    if (!empty($data->update->package)) {
                        if (strpos($data->update->package, 'downloads.wordpress.org') !== false) {
                            $plugins[$plugin] = $data;
                        }
                    }
                }
            }

            foreach ($themes_raw as $theme => $data) {
                if (!empty($data->update)) {
                    if (!empty($data->update['package'])) {
                        if (strpos($data->update['package'], 'downloads.wordpress.org') !== false) {
                            $themes[$theme] = $data;
                        }
                    }
                }
            }

            $data = [
                'core'    => get_core_updates(),
                'plugins' => $plugins,
                'themes'  => $themes
            ];

            /*Returning results*/
            if ($array === TRUE) {
                return $data;
            } else {
                xagio_json('success', 'Successfully retrieved updates.', $data);
            }
        }

        /**
         * get plugins update if site_transient not work in get_plugin_updates wp function
         * @return array
         */
        public static function get_plugins_update($request = null)
        {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            $all_plugins     = get_plugins();
            $upgrade_plugins = [];
            $current         = get_option('_site' . '_transient_update' . '_plugins');
            foreach ((array)$all_plugins as $plugin_file => $plugin_data) {
                if (!empty($current->response[$plugin_file])) {
                    $upgrade_plugins[$plugin_file]         = (object)$plugin_data;
                    $upgrade_plugins[$plugin_file]->update = $current->response[$plugin_file];
                }
            }
            return $upgrade_plugins;
        }

        /**
         *   Get Plugins & Themes
         * @param boolean $array Set crone to true to return array
         * @return void|array
         */
        public static function getPluginsThemes($array = FALSE)
        {
            require_once ABSPATH . 'wp-admin/includes/theme.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            $themes       = wp_get_themes();
            $themes_temp  = [];
            $themes2_temp = [];
            $themes3_temp = [];
            foreach ($themes as $key => $value) {
                $screenshot          = $value->get_screenshot();
                $value               = (array)$value;
                $value['screenshot'] = $screenshot;
                $themes_temp[$key]   = $value;
            }
            foreach ($themes_temp as $key => $theme) {
                if (!!empty($themes2_temp[$key])) {
                    $themes2_temp[$key] = [];
                }
                foreach ($theme as $k => $v) {
                    $k                      = str_replace('WP_Theme', '', $k);
                    $k                      = preg_replace('/[^A-Za-z0-9\-]/', '', $k);
                    $themes2_temp[$key][$k] = $v;
                }
            }
            foreach ($themes2_temp as $key => $theme) {
                $theme['headers']['screenshot'] = $theme['screenshot'];
                $themes3_temp[$key]             = $theme['headers'];
            }

            $data = [
                'plugins' => get_plugins(),
                'themes'  => $themes3_temp,
            ];
            /*Returning results*/
            if ($array === TRUE) {
                return $data;
            } else {
                xagio_json('success', 'Successfully retrieved plugins and themes.', $data);
            }
        }

        /**
         *   Get blog info
         * @param boolean $array Set crone to true to return array
         * @return void|array
         */
        public static function getBlogInfo($array = FALSE)
        {
            $data = [
                'name'        => get_bloginfo('name'),
                'description' => get_bloginfo('description'),
                'admin_email' => get_bloginfo('admin_email'),
                'version'     => get_bloginfo('version'),
            ];
            /*Returning results*/
            if ($array === TRUE) {
                return $data;
            } else {
                xagio_json('success', 'Successfully retrieved blog info.', $data);
            }
        }

        /**
         *  Sync comments
         * @param boolean $array Set to true to return array
         * @return void|array
         */
        public static function getComments($array = FALSE)
        {

            $pending_coments = get_comments([
                'status' => 'hold',
                'number' => 2000
            ]);
            $spam_comments   = get_comments([
                'status' => 'spam',
                'number' => 2000
            ]);

            $data = array_merge($pending_coments, $spam_comments);

            $comments = [];
            foreach ($data as $comment) {

                $comments[] = [
                    'name'           => $comment->comment_author,
                    'comment'        => $comment->comment_content,
                    'comment_id'     => $comment->comment_ID,
                    'comment_status' => $comment->comment_approved,
                    'page_id'        => $comment->comment_post_ID,
                    'date'           => $comment->comment_date,
                ];

            }

            /*Returning results*/
            if ($array === TRUE) {
                return $comments;
            } else {
                xagio_json('success', 'Successfully retrieved comments.', $comments);
            }

        }

        /**
         * Delete all unapproved and spam comments
         */
        public static function deleteAllComments($request = null)
        {
            global $wpdb;

            // Fetch pending comments
            $pending_comments = get_comments([
                'status' => 'hold',
                'number' => 10000
            ]);

            // Determine whether to fetch spam or trash comments based on count
            $spam_comments = [];
            if (count($pending_comments) <= 5000) {
                $spam_comments = get_comments([
                    'status' => 'spam',
                    'number' => 5000
                ]);

                if (empty($spam_comments)) {
                    $spam_comments = get_comments([
                        'status' => 'trash',
                        'number' => 5000
                    ]);
                }
            }

            // Merge comments to delete
            $comments_to_delete = array_merge($pending_comments, $spam_comments);

            // If there are comments to delete, proceed with deletion
            if (!empty($comments_to_delete)) {
                $comment_ids             = wp_list_pluck($comments_to_delete, 'comment_ID');
                $comment_ids_placeholder = implode(',', array_fill(0, count($comment_ids), '%d'));

                // Prepare and execute the deletion query
                $deleted = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$wpdb->prefix}comments WHERE comment_ID IN ($comment_ids_placeholder)", ...$comment_ids
                    )
                );

                if ($deleted) {
                    xagio_json('success', 'Successfully deleted comments.');
                } else {
                    xagio_json('error', 'Could not delete comments at the moment.');
                }
            } else {
                xagio_json('error', 'No comments to delete.');
            }
        }

        /**
         * Delete all spam comments
         */
        public static function deleteAllSpamComments($request = null)
        {
            global $wpdb;

            // Fetch all spam comments
            $spam_comments = get_comments([
                'status' => 'spam',
                'number' => 10000
            ]);

            if (!empty($spam_comments)) {
                // Extract comment IDs
                $comment_ids             = wp_list_pluck($spam_comments, 'comment_ID');
                $comment_ids_placeholder = implode(',', array_fill(0, count($comment_ids), '%d'));

                // Prepare and execute the deletion query
                $deleted = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$wpdb->prefix}comments WHERE comment_ID IN ($comment_ids_placeholder)", ...$comment_ids
                    )
                );

                if ($deleted) {
                    xagio_json('success', 'Successfully deleted spam comments.');
                } else {
                    xagio_json('error', 'Could not delete comments at the moment.');
                }
            } else {
                xagio_json('error', 'No spam comments to delete.');
            }
        }

        /**
         * Delete all trash comments
         */
        public static function deleteAllTrashComments($request = null)
        {
            global $wpdb;

            // Fetch all trash comments
            $trash_comments = get_comments([
                'status' => 'trash',
                'number' => 10000
            ]);

            if (!empty($trash_comments)) {
                // Extract comment IDs
                $comment_ids             = wp_list_pluck($trash_comments, 'comment_ID');
                $comment_ids_placeholder = implode(',', array_fill(0, count($comment_ids), '%d'));

                // Prepare and execute the deletion query
                $deleted = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$wpdb->prefix}comments WHERE comment_ID IN ($comment_ids_placeholder)", ...$comment_ids
                    )
                );

                if ($deleted) {
                    xagio_json('success', 'Successfully deleted trash comments.');
                } else {
                    xagio_json('error', 'Could not delete comments at the moment.');
                }
            } else {
                xagio_json('error', 'No trash comments to delete.');
            }
        }


        /**
         *  Sync reviews
         * @param boolean $array Set crone to true to return array
         * @return void|array
         */
        public static function getReviews($array = FALSE)
        {
            global $wpdb;

            $results = $wpdb->get_results('SELECT * FROM xag_reviews', ARRAY_A);

            for ($i = 0; $i < sizeof($results); $i++) {
                if ($results[$i]['page_id'] !== 0) {
                    $results[$i]['page_name'] = get_the_title($results[$i]['page_id']);
                } else {
                    $results[$i]['page_name'] = 'Global Review';
                }
            }

            /*Returning results*/
            if ($array === TRUE) {
                return $results;
            } else {
                xagio_json('success', 'Successfully retrieved reviews.', $results);
            }

        }

        public static function getComment($request = null)
        {


            if (empty($request->get_param('comment_id'))) {
                xagio_json('error', 'Invalid request!');
            }

            $comment_id = intval($request->get_param('comment_id'));
            $comment    = get_comment($comment_id, ARRAY_A);

            if (!$comment) {
                xagio_json('error', 'Comment not found!');
            } else {
                xagio_json('success', 'Comment retrieved successfully.', $comment);
            }
        }

        public static function editComment($request = null)
        {


            if (empty($request->get_param('args')) || !is_array($request->get_param('args'))) {
                xagio_json('error', 'Invalid request!');
            }

            $args   = array_map('sanitize_text_field', wp_unslash($request->get_param('args')));
            $result = wp_update_comment($args);

            if ($result) {
                xagio_json('success', 'Comment edited successfully.');
            } else {
                xagio_json('error', 'Could not edit comment at the moment.');
            }
        }

        public static function replyOnComment($request = null)
        {


            if (empty($request->get_param('comment_id')) || empty($request->get_param('content'))) {
                xagio_json('error', 'Invalid request!');
            }

            $comment_id = intval($request->get_param('comment_id'));
            $content    = sanitize_textarea_field(wp_unslash($request->get_param('content')));

            $comment = get_comment($comment_id, ARRAY_A);

            if (!$comment) {
                xagio_json('error', 'Comment not found!');
            } else {
                $admins = get_users([
                    'role'   => 'administrator',
                    'fields' => ['ID'],
                ]);

                if (empty($admins)) {
                    xagio_json('error', 'No admin users found!');
                }

                $user_info = get_userdata($admins[0]->ID);

                $data = [
                    'comment_post_ID'      => $comment['comment_post_ID'],
                    'comment_author'       => $user_info->user_login,
                    'comment_author_email' => $user_info->user_email,
                    'comment_content'      => $content,
                    'comment_type'         => '',
                    'comment_parent'       => $comment['comment_ID'],
                    'user_id'              => $user_info->ID,
                    'comment_date'         => current_time('mysql'),
                ];

                $result = wp_insert_comment($data);

                if ($result) {
                    xagio_json('success', 'Successfully replied to comment.');
                } else {
                    xagio_json('error', 'Could not reply to comment at the moment.');
                }
            }
        }

        public static function updateComments($request = null)
        {


            global $wpdb;

            // Validate request
            if (empty($request->get_param('method')) || empty($request->get_param('comment_id'))) {
                xagio_json('error', 'Invalid request!');
            }

            $method      = sanitize_text_field(wp_unslash($request->get_param('method')));
            $comment_ids = is_array($request->get_param('comment_id')) ? array_map('intval', $request->get_param('comment_id')) : [intval($request->get_param('comment_id'))];

            $methods = [
                'PUT'    => 'approve',
                'POST'   => 'hold',
                'GET'    => 'spam',
                'DELETE' => 'trash',
            ];

            if (!array_key_exists($method, $methods)) {
                xagio_json('error', 'Invalid method!');
            }

            $status = $methods[$method];

            // Update comments in bulk or individually
            if (!empty($comment_ids)) {
                if (count($comment_ids) > 1) {
                    $placeholders = implode(',', array_fill(0, count($comment_ids), '%d'));
                    $result       = $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE {$wpdb->prefix}comments SET comment_approved = %s WHERE comment_ID IN ($placeholders)", array_merge([$status], $comment_ids)
                        )
                    );
                } else {
                    $result = wp_set_comment_status($comment_ids[0], $status);
                }

                if ($result !== false) {
                    $message = 'Successfully ' . (($status === 'approve') ? 'approved' : (($status === 'hold') ? 'unapproved' : (($status === 'spam') ? 'moved to spam' : 'deleted'))) . ' comment(s).';
                    xagio_json('success', $message);
                } else {
                    xagio_json('error', 'Could not update comment(s) at the moment.');
                }
            } else {
                xagio_json('error', 'No valid comment IDs provided.');
            }
        }

        /**
         *   Get Posts
         * @param boolean $array Set cron to true to return array
         * @return void|array
         */
        public static function getPosts($array = FALSE)
        {

            global $wpdb;

            $aColumns = [
                'ID',
                'post_author',
                'post_date',
                'post_title',
                'post_status',
                'comment_status',
                'post_name',
                'post_parent',
                'guid',
                'post_type',
                'comment_count',
            ];

            /* Indexed column (used for fast and accurate table cardinality) */
            $sIndexColumn = "ID";

            /* DB table to use */
            $sTable = $wpdb->prefix . 'posts';

            // Determine Post Types
            $allowedPostTypes = XAGIO_MODEL_SEO::getAllPostTypes();

            // Create placeholders for the allowed post types
            $postTypePlaceholders = implode(', ', array_fill(0, count($allowedPostTypes), '%s'));

            // Prepare the WHERE clause for post types and status
            $sWhere = " WHERE post_type IN ($postTypePlaceholders) AND post_status IN ('publish', 'future', 'draft', 'pending') ";

            // Combine columns into a string for the query
            $columns = implode(", ", array_map('esc_sql', $aColumns)); // Escaping the column names for safety

            // Create placeholders for the allowed post types
            $postTypePlaceholders = implode(', ', array_fill(0, count($allowedPostTypes), '%s'));

            // Prepare the WHERE clause for post types and status
            $sWhere = "WHERE post_type IN ($postTypePlaceholders) AND post_status IN ('publish', 'future', 'draft', 'pending')";

            // Execute the query
            $rResult = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT SQL_CALC_FOUND_ROWS $columns
        FROM $sTable
        $sWhere", ...$allowedPostTypes
                ), ARRAY_A
            );


            $posts_data = [];

            for ($i = 0; $i < count($rResult); $i++) {
                $posts_data[$i]['id']            = $rResult[$i]['ID'];
                $posts_data[$i]['post_type']     = $rResult[$i]['post_type'];
                $posts_data[$i]['post_title']    = $rResult[$i]['post_title'];
                $posts_data[$i]['post_status']   = $rResult[$i]['post_status'];
                $posts_data[$i]['guid']          = $rResult[$i]['guid'];
                $posts_data[$i]['post_author']   = get_the_author_meta('display_name', $rResult[$i]['post_author']);
                $posts_data[$i]['comment_count'] = $rResult[$i]['comment_count'];
            }

            /*Returning results*/
            if ($array === TRUE) {
                return $posts_data;
            } else {
                xagio_json('success', 'Successfully retrieved all post revisions.', $posts_data);
            }

        }

        /**
         *   Get Post Revisions
         * @param boolean $array Set cron to true to return array
         * @return void|array
         */
        public static function getPostRevisions($array = FALSE)
        {
            $posts = get_posts();

            $post_ids = [];
            foreach ($posts as $post) {
                $post_ids[] = $post->ID;
            }

            $revisions = [];
            foreach ($post_ids as $id) {
                $revision_count = sizeof(wp_get_post_revisions($id));

                if ($revision_count > 0) {
                    $revisions[$id]['count'] = $revision_count;
                }
            }

            /*Returning results*/
            if ($array === TRUE) {
                return $revisions;
            } else {
                xagio_json('success', 'Successfully retrieved all post revisions.', $revisions);
            }

        }

        public static function deleteRevisions($request = null)
        {


            if (empty($request->get_param('method')) || empty($request->get_param('post_id'))) {
                xagio_json('error', 'Invalid request!');
            }

            $method  = sanitize_text_field(wp_unslash($request->get_param('method')));
            $post_id = intval(wp_unslash($request->get_param('post_id')));

            if ($method !== 'DELETE') {
                xagio_json('error', 'Invalid request!');
            }

            $revisions = wp_get_post_revisions($post_id);
            $errors    = [];

            foreach ($revisions as $revision) {
                $result = wp_delete_post_revision($revision->ID);
                if (is_wp_error($result)) {
                    $errors[] = $revision->ID;
                }
            }

            if (empty($errors)) {
                xagio_json('success', 'Successfully deleted all revisions.');
            } else {
                xagio_json('error', 'Could not delete all revisions.');
            }
        }

        /**
         * Update Features
         */
        public static function updateFeatures($request = null)
        {


            // Update features if provided
            if (!empty($request->get_param('features'))) {
                $features = sanitize_text_field(wp_unslash($request->get_param('features')));
                update_option('XAGIO_FEATURES', $features);
                xagio_json('success', 'Successfully updated features!');
            } else {
                xagio_json('error', 'No features detected!');
            }
        }

        /**
         * Daily Synchronization
         */
        public static function dailySync($request = null)
        {

            try {
                $received_features = false;

                // Update features if detected
                if (!empty($request->get_param('features'))) {
                    $received_features = sanitize_text_field(wp_unslash($request->get_param('features')));
                    update_option('XAGIO_FEATURES', $received_features);
                }

                // Update membership if detected
                if (!empty($request->get_param('membership'))) {
                    $membership = sanitize_text_field(wp_unslash($request->get_param('membership')));
                    update_option('XAGIO_MEMBERSHIP', $membership);
                }

                // Deactivate on panel if license not set
                if (!XAGIO_LICENSE::isLicenseSet()) {
                    XAGIO_CORE::deactivate();
                }

                xagio_json('success', 'Successfully retrieved blog description.', [
                    'posts'            => self::getPosts(true),
                    'pluginsAndThemes' => self::storePluginsThemes(true),
                    'availableUpdates' => self::getUpdates(true),
                    'settings'         => self::getSettings(true),
                    'comments'         => self::getComments(true),
                    'revisions'        => self::getPostRevisions(true),
                    'reviews'          => self::getReviews(true),
                    'coreVersion'      => get_bloginfo('version'),
                    'new_features'     => $received_features,
                    'old_features'     => get_option('XAGIO_FEATURES'),
                    'admin_post'       => XAGIO_MODEL_SETTINGS::getApiUrl(),
                    'ip_address'       => XAGIO_IP_ADDRESS,
                ]);

            } catch (Exception $e) {
                xagio_json('error', 'Plugin error occurred. Please pass this down to support: ' . $e->getMessage());
            }
        }

        /**
         * Rank Tracker Daily Synchronization
         */
        public static function dailySyncPRT($request = null)
        {


            try {
                // Check if prtData is set and is an array
                if (empty($request->get_param('prtData')) || !is_array($request->get_param('prtData'))) {
                    xagio_json('error', 'Invalid request!');
                }

                // Clear all ranks
                global $wpdb;
                $wpdb->query('UPDATE xag_keywords SET rank = "0"');

                $prtData = map_deep(wp_unslash($request->get_param('prtData')), 'sanitize_text_field');

                // Sanitize and update existing fields with the new rank data
                foreach ($prtData as $termName => $termData) {
                    $sanitizedTermName = sanitize_text_field($termName);
                    $sanitizedTermData = wp_json_encode(
                        array_map(function ($item) {
                            $item['term_id'] = absint($item['term_id']);
                            $item['url_id']  = absint($item['url_id']);
                            $item['rank']    = is_numeric($item['rank']) ? absint($item['rank']) : $item['rank'];
                            $item['engine']  = esc_url_raw($item['engine']);
                            return $item;
                        }, $termData)
                    );

                    $wpdb->update(
                        'xag_keywords', ['rank' => $sanitizedTermData], ['keyword' => $sanitizedTermName]
                    );
                }

                xagio_json('success', 'Rank tracker data synchronized successfully.');
            } catch (Exception $e) {
                xagio_json('error', 'Plugin error occurred. Please pass this down to support: ' . $e->getMessage());
            }
        }


        /**
         * Rank Tracker update remove
         */
        public static function removeTermPRT($request = null)
        {


            global $wpdb;
            // Check if term_ids are set
            if (empty($request->get_param('term_ids'))) {
                xagio_json('error', 'Invalid request!');
            }

            $term_ids_raw = sanitize_text_field(wp_unslash($request->get_param('term_ids')));
            $term_ids     = array_map('intval', array_map('sanitize_text_field', explode(',', $term_ids_raw)));

            foreach ($term_ids as $term_id) {
                // Prepare and execute the query to fetch the relevant keyword and rank data
                $results = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT keyword, rank FROM xag_keywords WHERE rank != '0' AND rank != '501' AND rank LIKE %s LIMIT 1", '%' . $wpdb->esc_like($term_id) . '%'
                    )
                );

                if ($results) {
                    $data    = json_decode($results->rank, true);
                    $newData = [];

                    foreach ($data as $key => $val) {
                        if ($val['term_id'] != $term_id) {
                            $newData[] = $val;
                        }
                    }

                    $newData = !empty($newData) ? wp_json_encode($newData) : '0';

                    $wpdb->update(
                        'xag_keywords', ['rank' => $newData], ['keyword' => sanitize_text_field($results->keyword)]
                    );
                }
            }

            xagio_json('success', 'Term(s) removed successfully.');
        }

        /**
         *   Store Plugins and Themes on register
         * @param boolean $array Set crone to true to return array
         * @return void|array
         */
        public static function storePluginsThemes($array = FALSE)
        {

            $pluginsThemes = self::getPluginsThemes(TRUE);

            // Check if plugins are activated
            $plugins = $pluginsThemes['plugins'];

            foreach ($plugins as $name => $plugin) {
                $pluginsThemes['plugins'][$name]['active'] = is_plugin_active($name);
            }

            // Check which theme is activated
            $themes = $pluginsThemes['themes'];

            $current_theme = get_option('current_theme');

            foreach ($themes as $name => $theme) {
                if ($theme['Name'] == $current_theme) {
                    $pluginsThemes['themes'][$name]['active'] = TRUE;
                } else {
                    $pluginsThemes['themes'][$name]['active'] = FALSE;
                }
            }

            /*Returning results*/
            if ($array === TRUE) {
                return $pluginsThemes;
            } else {
                xagio_json('success', 'Successfully retrieved blog description.', $pluginsThemes);
            }

        }

        /**
         *  Get site Environment
         */
        public static function getEnvironment($request = null)
        {
            $wpDebugMode = (defined('WP_DEBUG') && TRUE === WP_DEBUG) ? TRUE : FALSE;
            $data        = [
                'wpEnvironment'     => [
                    'homeURL'       => get_home_url(),
                    'siteURL'       => get_site_url(),
                    'wpVersion'     => get_bloginfo('version'),
                    'wpMultisite'   => is_multisite(),
                    'wpMemoryLimit' => WP_MEMORY_LIMIT,
                    'wpDebugMode'   => $wpDebugMode,
                    'wpLanguage'    => get_bloginfo('language'),
                ],
                'serverEnvironment' => [
                    'serverInfo'       => sanitize_text_field(wp_unslash(!empty($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown')),
                    'phpVersion'       => phpversion(),
                    'phpPostMaxSize'   => ini_get('post_max_size'),
                    'phpMaxUploadSize' => ini_get('upload_max_filesize'),
                    'phpTimeLimit'     => ini_get('max_execution_time'),
                    'phpMaxInputVars'  => ini_get('max_input_vars'),
                    'cURL'             => function_exists('curl_init'),
                    'ZipArchive'       => class_exists('ZipArchive'),
                    'DOMDocument'      => class_exists('DOMDocument'),
                    'wpRemoteGet'      => function_exists('wp_remote_get'),
                    'wpRemotePost'     => function_exists('wp_remote_post'),
                ],
            ];

            xagio_json('success', 'Successfully retrieved Environment info.', $data);
        }

        /**
         * Save Global Scripts
         */
        public static function renderGlobalScripts($request = null)
        {


            // Check if page_id and value are set
            if (empty($request->get_param('page_id')) || !!empty($request->get_param('value'))) {
                xagio_json('error', 'Invalid request!');
            }

            // Sanitize inputs
            $page_id = intval($request->get_param('page_id'));
            $value   = sanitize_text_field(wp_unslash($request->get_param('value')));

            // Update post meta
            update_post_meta($page_id, 'ps_seo_disable_global_scripts', $value);

            xagio_json('success', 'Successfully updated render global script option.');
        }


        /**
         *  Get Scripts per page
         */
        public static function getPerPageScript($request = null)
        {


            if (!!empty($request->get_param('page_id'))) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $page_id = intval($request->get_param('page_id'));
            $scripts = get_post_meta($page_id);

            $filtered_value = array();
            $required_key   = array(
                'ps_seo_scripts',
                'ps_seo_body_scripts',
                'ps_seo_footer_scripts',
                'ps_seo_disable_global_scripts',
                'ps_seo_disable_global_body_scripts',
                'ps_seo_disable_global_footer_scripts',
                'ps_seo_disable_page_scripts',
                'ps_seo_disable_page_body_scripts',
                'ps_seo_disable_page_footer_scripts'
            );

            foreach ($scripts as $key => $val) {
                if (in_array($key, $required_key)) {
                    $filtered_value[$key] = $val[0];
                }
            }

            $filtered_value = wp_json_encode($filtered_value);
            $filtered_value = base64_encode($filtered_value);

            xagio_json('success', 'Successfully retrieved scripts.', $filtered_value);
        }

        /**
         * Save Global Scripts
         */
        public static function savePerPageScript($request = null)
        {


            if (!!empty($request->get_param('page_id'))) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // Sanitize the page ID
            $page_id = intval($request->get_param('page_id'));

            // Sanitize and decode the scripts
            $ps_seo_scripts        = !empty($request->get_param('script')['seo_scripts']) ? base64_decode(sanitize_textarea_field(wp_unslash($request->get_param('script')['seo_scripts']))) : '';
            $ps_seo_body_scripts   = !empty($request->get_param('script')['seo_body_scripts']) ? base64_decode(sanitize_textarea_field(wp_unslash($request->get_param('script')['seo_body_scripts']))) : '';
            $ps_seo_footer_scripts = !empty($request->get_param('script')['seo_footer_scripts']) ? base64_decode(sanitize_textarea_field(wp_unslash($request->get_param('script')['seo_footer_scripts']))) : '';

            // Sanitize the disable script options
            $ps_seo_disable_global_scripts        = !empty($request->get_param('script')['seo_disable_global_scripts']) ? sanitize_text_field(wp_unslash($request->get_param('script')['seo_disable_global_scripts'])) : '';
            $ps_seo_disable_global_body_scripts   = !empty($request->get_param('script')['seo_disable_global_body_scripts']) ? sanitize_text_field(wp_unslash($request->get_param('script')['seo_disable_global_body_scripts'])) : '';
            $ps_seo_disable_global_footer_scripts = !empty($request->get_param('script')['seo_disable_global_footer_scripts']) ? sanitize_text_field(wp_unslash($request->get_param('script')['seo_disable_global_footer_scripts'])) : '';

            $ps_seo_disable_page_scripts        = !empty($request->get_param('script')['seo_disable_page_scripts']) ? sanitize_text_field(wp_unslash($request->get_param('script')['seo_disable_page_scripts'])) : '';
            $ps_seo_disable_page_body_scripts   = !empty($request->get_param('script')['seo_disable_page_body_scripts']) ? sanitize_text_field(wp_unslash($request->get_param('script')['seo_disable_page_body_scripts'])) : '';
            $ps_seo_disable_page_footer_scripts = !empty($request->get_param('script')['seo_disable_page_footer_scripts']) ? sanitize_text_field(wp_unslash($request->get_param('script')['seo_disable_page_footer_scripts'])) : '';

            // Update post meta with sanitized values
            update_post_meta($page_id, 'ps_seo_scripts', trim($ps_seo_scripts));
            update_post_meta($page_id, 'ps_seo_body_scripts', trim($ps_seo_body_scripts));
            update_post_meta($page_id, 'ps_seo_footer_scripts', trim($ps_seo_footer_scripts));

            update_post_meta($page_id, 'ps_seo_disable_global_scripts', trim($ps_seo_disable_global_scripts));
            update_post_meta($page_id, 'ps_seo_disable_global_body_scripts', trim($ps_seo_disable_global_body_scripts));
            update_post_meta($page_id, 'ps_seo_disable_global_footer_scripts', trim($ps_seo_disable_global_footer_scripts));

            update_post_meta($page_id, 'ps_seo_disable_page_scripts', trim($ps_seo_disable_page_scripts));
            update_post_meta($page_id, 'ps_seo_disable_page_body_scripts', trim($ps_seo_disable_page_body_scripts));
            update_post_meta($page_id, 'ps_seo_disable_page_footer_scripts', trim($ps_seo_disable_page_footer_scripts));

            xagio_json('success', 'Successfully updated global script.');
        }


        /**
         *  Get global header Scripts
         */
        public static function getGlobalScripts($request = null)
        {

            $scripts = get_option('XAGIO_SEO_GLOBAL_SCRIPTS_HEAD');

            $scripts = base64_encode($scripts);

            xagio_json('success', 'Successfully retrieved header scripts.', $scripts);
        }

        /**
         *  Get global footer Scripts
         */
        public static function getGlobalBodyScripts($request = null)
        {

            $scripts = get_option('XAGIO_SEO_GLOBAL_SCRIPTS_BODY');

            $scripts = base64_encode($scripts);

            xagio_json('success', 'Successfully retrieved body scripts.', $scripts);
        }

        /**
         *  Get global footer Scripts
         */
        public static function getGlobalFooterScripts($request = null)
        {

            $scripts = get_option('XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER');

            $scripts = base64_encode($scripts);

            xagio_json('success', 'Successfully retrieved footer scripts.', $scripts);
        }

        /**
         * Save Global Header Scripts
         */
        public static function saveGlobalScript($request = null)
        {


            // Check and sanitize inputs
            $encoded_header = !empty($request->get_param('script')['global_header_scripts']) ? sanitize_textarea_field(wp_unslash($request->get_param('script')['global_header_scripts'])) : '';
            $encoded_footer = !empty($request->get_param('script')['global_footer_scripts']) ? sanitize_textarea_field(wp_unslash($request->get_param('script')['global_footer_scripts'])) : '';
            $encoded_body   = !empty($request->get_param('script')['global_body_scripts']) ? sanitize_textarea_field(wp_unslash($request->get_param('script')['global_body_scripts'])) : '';

            // Decode the base64 encoded scripts
            $header_script = base64_decode($encoded_header);
            $footer_script = base64_decode($encoded_footer);
            $body_script   = base64_decode($encoded_body);

            // Update options with sanitized and decoded scripts
            update_option('XAGIO_SEO_GLOBAL_SCRIPTS_HEAD', trim($header_script));
            update_option('XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER', trim($footer_script));
            update_option('XAGIO_SEO_GLOBAL_SCRIPTS_BODY', trim($body_script));

            xagio_json('success', 'Successfully updated global scripts.');
        }

        /**
         * Save Global Footer Scripts
         */
        public static function saveGlobalFooterScript($request = null)
        {


            // Check and sanitize the input
            if (!empty($request->get_param('footerScript'))) {
                $encoded = sanitize_textarea_field(wp_unslash($request->get_param('footerScript')));
                $script  = base64_decode($encoded);

                // Update the option with the sanitized and decoded script
                update_option('XAGIO_SEO_GLOBAL_SCRIPTS_FOOTER', trim($script));

                xagio_json('success', 'Successfully updated global footer script.');
            } else {
                xagio_json('error', 'No footer script provided.');
            }
        }

        /**
         *  Update WordPress Core
         */
        public static function updateWpCore($request = null)
        {


            try {

                // Remove the lock
                delete_option('core_updater.lock');

                // Including required class for updates
                require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

                $reinstall = !empty($request->get_param('reinstall'));
                $version   = !empty($request->get_param('version')) ? sanitize_text_field(wp_unslash($request->get_param('version'))) : FALSE;

                // Check if already updated
                if ($version == get_bloginfo('version')) {
                    xagio_json('success', 'Successfully updated WordPress core.');
                    exit;
                }

                $update = find_core_update($version, 'en_US');

                if (!$update) {
                    xagio_json('error', 'Cannot find any new updates for WordPress core.');
                    exit;
                }

                $allow_relaxed_file_ownership = !$reinstall && !empty($update->new_files) && !$update->new_files;

                if ($reinstall) {
                    $update->response = 'reinstall';
                }

                ob_start();
                $skin     = new Xagio_Silent_Upgrader_Skin();
                $upgrader = new Core_Upgrader($skin);
                $status   = $upgrader->upgrade($update, [
                    'allow_relaxed_file_ownership' => $allow_relaxed_file_ownership,
                ]);

                ob_end_clean();

                if (is_wp_error($status)) {
                    xagio_json('error', 'Failed to update WordPress core.', $status);
                } else {
                    xagio_json('success', 'Successfully updated WordPress core.');
                }

            } catch (Exception $error) {
                xagio_json('error', 'Plugin error occurred. Please pass this down to support: ' . $error->getMessage());
            }

        }

        /**
         * Update one or more plugins
         * receive plugin root_name
         */
        public static function updateWpPlugins($request = null)
        {


            try {
                // Check if plugin names are set
                if (empty($request->get_param('pluginNames'))) {
                    xagio_json('error', 'Invalid request!');
                }

                $pluginNames = is_array($request->get_param('pluginNames')) ? array_map('sanitize_text_field', wp_unslash($request->get_param('pluginNames'))) : [sanitize_text_field(wp_unslash($request->get_param('pluginNames')))];

                // Include required class for updates
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

                $status      = [];
                $all_plugins = get_plugins();

                foreach ($pluginNames as $pluginName) {
                    $updates        = self::getUpdates(true);
                    $plugins_update = $updates['plugins'];

                    if (!!empty($all_plugins[$pluginName])) {
                        $status[$pluginName] = 'Plugin is not installed on this website. Please synchronize.';
                    } else {
                        if (!!empty($plugins_update[$pluginName])) {
                            $status[$pluginName] = true;
                        } else {
                            $plugin_active = is_plugin_active($pluginName);
                            $skin          = new Xagio_Silent_Upgrader_Skin();
                            $upgrader      = new Plugin_Upgrader($skin);
                            $temp_check    = $upgrader->upgrade($pluginName);

                            if (!$temp_check || is_wp_error($temp_check)) {
                                $status[$pluginName] = false;
                            } else {
                                if ($plugin_active) {
                                    activate_plugin($pluginName, '', false, true);
                                }
                                $status[$pluginName] = true;
                            }
                        }
                    }
                }

                xagio_json('success', 'Operation successfully finished.', ['success' => $status]);

            } catch (Exception $e) {
                xagio_json('error', 'Plugin error occurred. Please pass this down to support: ' . $e->getMessage());
            }
        }

        /**
         * Update one or more themes
         * Theme slug required
         */
        public static function updateWpThemes($request = null)
        {


            try {
                // Check if theme names are set
                if (empty($request->get_param('themeNames'))) {
                    xagio_json('error', 'Invalid request!');
                }

                $themeNames = is_array($request->get_param('themeNames')) ? array_map('sanitize_text_field', wp_unslash($request->get_param('themeNames'))) : [sanitize_text_field(wp_unslash($request->get_param('themeNames')))];

                // Include required class for updates
                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

                $status         = [];
                $updates        = self::getUpdates(true);
                $themes_updates = $updates['themes'];

                foreach ($themeNames as $themeName) {
                    if (!!empty($themes_updates[$themeName])) {
                        $status[$themeName] = true;
                    } else {
                        $skin     = new Xagio_Silent_Upgrader_Skin();
                        $upgrader = new Theme_Upgrader($skin);
                        $result   = $upgrader->upgrade($themeName);

                        if (!$result || is_wp_error($result)) {
                            $status[$themeName] = false;
                        } else {
                            $status[$themeName] = true;
                        }
                    }
                }

                xagio_json('success', 'Operation successfully finished.', $status);

            } catch (Exception $e) {
                xagio_json('error', 'Theme error occurred. Please pass this down to support: ' . $e->getMessage());
            }
        }

        /**
         * Removing theme from WordPress
         * Theme slug required
         */
        public static function removeWpTheme($request = null)
        {


            try {
                // Check if theme slug is set
                if (empty($request->get_param('slug'))) {
                    xagio_json('error', 'Invalid request!');
                }

                $slug = sanitize_text_field(wp_unslash($request->get_param('slug')));

                require_once ABSPATH . 'wp-admin/includes/theme.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';

                $remove = delete_theme($slug);

                if ($remove) {
                    xagio_json('success', 'Theme successfully removed.');
                } else {
                    xagio_json('error', 'There was a problem while removing the theme.');
                }

            } catch (Exception $e) {
                xagio_json('error', 'Theme error occurred. Please pass this down to support: ' . $e->getMessage());
            }
        }


        /**
         *  Approve / Edit / Remove Comment
         */
        public static function deleteComment($request = null)
        {


            if (!!empty($request->get_param('id'))) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $comment_id = intval($request->get_param('id'));

            $data = wp_delete_comment($comment_id);

            if ($data) {
                xagio_json('success', 'Successfully deleted Comment.');
            } else {
                xagio_json('error', 'Comment doesn\'t exist!');
            }
        }

        public static function approveComment($request = null)
        {


            if (!!empty($request->get_param('id'))) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $comment_id = intval($request->get_param('id'));

            $data = wp_set_comment_status($comment_id, 'approve');

            if ($data) {
                xagio_json('success', 'Successfully approved Comment.');
            } else {
                xagio_json('error', 'Comment doesn\'t exist!');
            }
        }

        /**
         *  Edit / Add Review
         */
        public static function editAddReview($request = null)
        {


            global $wpdb;

            // Sanitize and validate the ID
            $ID = !empty($request->get_param('id')) ? intval($request->get_param('id')) : 0;

            // Sanitize the input data with !empty() checks
            $data = [
                'name'      => !empty($request->get_param('name')) ? sanitize_text_field(wp_unslash($request->get_param('name'))) : '',
                'review'    => !empty($request->get_param('review')) ? sanitize_textarea_field(wp_unslash($request->get_param('review'))) : '',
                'rating'    => !empty($request->get_param('rating')) ? intval($request->get_param('rating')) : 0,
                'email'     => !empty($request->get_param('email')) ? sanitize_email(wp_unslash($request->get_param('email'))) : '',
                'website'   => !empty($request->get_param('website')) ? esc_url_raw(wp_unslash($request->get_param('website'))) : '',
                'telephone' => !empty($request->get_param('telephone')) ? sanitize_text_field(wp_unslash($request->get_param('telephone'))) : '',
                'location'  => !empty($request->get_param('location')) ? sanitize_text_field(wp_unslash($request->get_param('location'))) : '',
                'age'       => !empty($request->get_param('age')) ? intval($request->get_param('age')) : 0,
                // Corrected to use 'age'
            ];

            // Determine if we are inserting or updating based on the ID
            if ($ID == 0) {
                $wpdb->insert('xag_reviews', $data);
            } else {
                $wpdb->update('xag_reviews', $data, ['id' => $ID]);
            }

            xagio_json('success', 'Successfully finished operation.');
        }


        /**
         * Sync reviews
         */
        public static function updateReviews($request = null)
        {
            global $wpdb;

            // Validate the required parameters
            if (empty($request->get_param('method')) || empty($request->get_param('review_ids'))) {
                xagio_json('error', 'Invalid request!');
            }

            // Sanitize and process input
            $method     = sanitize_text_field(wp_unslash($request->get_param('method')));
            $review_ids = array_map('intval', explode(',', sanitize_text_field(wp_unslash($request->get_param('review_ids')))));

            // Ensure that review IDs are provided
            if (empty($review_ids)) {
                xagio_json('error', 'No valid review IDs provided.');
            }

            // Perform the requested operation
            switch ($method) {
                case "approve":
                    $wpdb->update('xag_reviews', ['approved' => 1], ['id' => $review_ids]);
                    break;
                case "unapprove":
                    $wpdb->update('xag_reviews', ['approved' => 0], ['id' => $review_ids]);
                    break;
                case "remove":
                    $wpdb->delete('xag_reviews', ['id' => $review_ids]);
                    break;
                default:
                    xagio_json('error', 'Invalid request method.');
                    break;
            }

            xagio_json('success', 'Operation completed successfully.');
        }

        /**
         * Sync posts
         */
        public static function updatePosts($request = null)
        {


            // Validate the required parameters
            if (empty($request->get_param('method')) || empty($request->get_param('post_ids'))) {
                xagio_json('error', 'Invalid request!');
            }

            // Sanitize and process input
            $method   = sanitize_text_field(wp_unslash($request->get_param('method')));
            $post_ids = array_map('intval', explode(',', sanitize_text_field(wp_unslash($request->get_param('post_ids')))));

            // Ensure that post IDs are provided
            if (empty($post_ids)) {
                xagio_json('error', 'No valid post IDs provided.');
            }

            // Perform the requested operation
            switch ($method) {
                case "seo-enable":
                    foreach ($post_ids as $id) {
                        update_post_meta($id, 'XAGIO_SEO', true);
                    }
                    break;
                case "seo-disable":
                    foreach ($post_ids as $id) {
                        update_post_meta($id, 'XAGIO_SEO', false);
                    }
                    break;
                case "remove":
                    foreach ($post_ids as $id) {
                        wp_trash_post($id);
                    }
                    break;
                default:
                    xagio_json('error', 'Invalid request method.');
                    break;
            }

            xagio_json('success', 'Operation completed successfully.');
        }


        /**
         *  Sync settings
         * @param boolean $array Set crone to true to return array
         * @return void|array
         */
        public static function getSettings($array = FALSE)
        {
            $settings = [
                "blogname",
                "blogdescription",
                "users_can_register",
                "default_role",
                "default_category",
                "default_post_format",
                "show_on_front",
                "page_on_front",
                "page_for_posts",
                "posts_per_page",
                "rss_use_excerpt",
                "blog_public",
                "default_pingback_flag",
                "default_ping_status",
                "default_comment_status",
                "require_name_email",
                "comment_registration",
                "close_comments_for_old_posts",
                "close_comments_days_old",
                "thread_comments",
                "thread_comments_depth",
                "page_comments",
                "comments_per_page",
                "default_comments_page",
                "comment_order",
                "comments_notify",
                "moderation_notify",
                "comment_moderation",
                "comment_whitelist",
                "permalink_structure"
            ];

            $setting_for_panel = [];

            foreach ($settings as $setting) {
                $setting_for_panel[$setting] = @get_option($setting);
            }

            $categories = get_categories([
                "hide_empty" => 0,
                "type"       => "post",
                "orderby"    => "name",
                "order"      => "ASC",
            ]);
            $cat_data   = [];
            foreach ($categories as $category) {
                $cat_data[] = [
                    "term_id"         => $category->term_id,
                    "name"            => $category->name,
                    "slug"            => $category->slug,
                    "category_parent" => $category->category_parent,
                ];
            }

            $setting_for_panel["categories"] = wp_json_encode($cat_data, TRUE);

            /*Returning results*/
            if ($array === TRUE) {
                return $setting_for_panel;
            } else {
                xagio_json('success', 'Successfully retrieved blog description.', $setting_for_panel);
            }

        }

        public static function postLicense($request = null)
        {
            // Sanitize and process input
            $email = !empty($request->get_param('license_email')) ? sanitize_email(wp_unslash($request->get_param('license_email'))) : '';
            $key   = !empty($request->get_param('license_key')) ? sanitize_text_field(wp_unslash($request->get_param('license_key'))) : '';

            // Update the license information
            update_option('XAGIO_LICENSE_EMAIL', $email);
            update_option('XAGIO_LICENSE_KEY', $key);

            // Return success response
            xagio_json('success', 'License successfully updated.', [
                'blog_name' => get_bloginfo('name'),
                'blog_desc' => get_bloginfo('description'),
            ]);
        }

        public static function getSystemStatus()
        {

            $output = [
                "xagio_version"          => xagio_get_version(),
                "xagio_panel_url"        => XAGIO_PANEL_URL,
                "xagio_plugin_path"      => XAGIO_PATH,
                "xagio_plugin_url_path"  => XAGIO_URL,
                "xagio_plugin_api_url"   => XAGIO_MODEL_SETTINGS::getApiUrl(),
                "wordpress_home_url"     => get_home_url(),
                "wordpress_site_url"     => get_site_url(),
                "wordpress_version"      => get_bloginfo('version'),
                "wordpress_multisite"    => is_multisite() ? '1' : '0',
                "wordpress_memory_limit" => WP_MEMORY_LIMIT,
                "wordpress_debug_mode"   => (defined('WP_DEBUG') && TRUE === WP_DEBUG) ? '1' : '0',
                "language"               => get_bloginfo('language'),
                "server_info"            => sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])),
                "php_version"            => phpversion(),
                "php_post_max_size"      => ini_get('post_max_size'),
                "php_max_upload_size"    => ini_get('upload_max_filesize'),
                "php_time_limit"         => ini_get('max_execution_time'),
                "php_max_input_vars"     => ini_get('max_input_vars'),
                "php_memory_limit"       => ini_get('memory_limit'),
                "open_ssl"               => OPENSSL_VERSION_NUMBER >= 268439647 ? "1" : "0",
                "curl"                   => function_exists('curl_init') ? "1" : "0",
                "zip_archive"            => class_exists('ZipArchive') ? "1" : "0",
                "dom_document"           => class_exists('DOMDocument') ? "1" : "0",
                "wp_remote_get"          => function_exists('wp_remote_get') ? "1" : "0",
                "wp_remote_post"         => function_exists('wp_remote_post') ? "1" : "0",
            ];

            wp_send_json($output);
            wp_die();
        }

        public static function postSettings($request = null)
        {


            // Validate and sanitize the input data
            $data = !empty($request->get_param('data')) ? map_deep(wp_unslash($request->get_param('data')), 'sanitize_text_field') : null;

            if (is_null($data)) {
                xagio_json('error', 'DATA is not properly being sent.');
            } else {
                foreach ($data as $option => $val) {
                    // Sanitize option name and value before updating
                    $option = sanitize_text_field($option);
                    $val    = maybe_serialize($val); // Handles potential arrays or objects
                    update_option($option, $val);
                }
                xagio_json('success', 'DATA successfully updated.');
            }
        }

        public static function pushSchema($request = null)
        {


            $schemaIDs = !empty($request->get_param('schema_ids')) ? array_map('sanitize_text_field', explode(',', sanitize_text_field(wp_unslash($request->get_param('schema_ids'))))) : null;
            $ID        = !empty($request->get_param('id')) ? intval($request->get_param('id')) : 0;
            $TYPE      = !empty($request->get_param('type')) ? sanitize_text_field(wp_unslash($request->get_param('type'))) : 'post';

            if (!empty($schemaIDs)) {
                $output          = null;
                $renderedSchemas = XAGIO_MODEL_SCHEMA::getRemoteRenderedSchemas($schemaIDs, null, $TYPE, $output);

                if ($renderedSchemas !== false) {
                    if ($TYPE === 'post') {
                        if ($ID !== 0) {
                            update_post_meta($ID, 'XAGIO_SEO_SCHEMA_META', $renderedSchemas['meta']);
                            update_post_meta($ID, 'XAGIO_SEO_SCHEMA_DATA', $renderedSchemas['data']);
                        } else {
                            update_option('XAGIO_SEO_SCHEMA_META', $renderedSchemas['meta']);
                            update_option('XAGIO_SEO_SCHEMA_DATA', $renderedSchemas['data']);
                        }
                    } elseif ($TYPE === 'term') {
                        if ($ID !== 0) {
                            $tag = get_term($ID);
                            if ($tag && !is_wp_error($tag)) {
                                $id = $tag->term_id;
                                update_term_meta($id, 'XAGIO_SEO_SCHEMA_META', $renderedSchemas['meta']);
                                update_term_meta($id, 'XAGIO_SEO_SCHEMA_DATA', $renderedSchemas['data']);
                            } else {
                                xagio_json('error', 'Invalid term ID.');
                            }
                        } else {
                            // TODO: Add global term schema if needed
                        }
                    }

                    xagio_json('success', 'Operation successfully finished.');
                } else {
                    xagio_json('error', 'A problem occurred.', $output);
                }
            } else {
                if ($ID !== 0) {
                    update_post_meta($ID, 'XAGIO_SEO_SCHEMA_META', false);
                    update_post_meta($ID, 'XAGIO_SEO_SCHEMA_DATA', false);
                } else {
                    update_option('XAGIO_SEO_SCHEMA_META', false);
                    update_option('XAGIO_SEO_SCHEMA_DATA', false);
                }
                xagio_json('success', 'Operation successfully finished.');
            }
        }

        public static function getSchema($request = null)
        {


            $ID   = !empty($request->get_param('id')) && !empty($request->get_param('id')) ? intval($request->get_param('id')) : 0;
            $TYPE = !empty($request->get_param('type')) && !empty($request->get_param('type')) ? sanitize_text_field(wp_unslash($request->get_param('type'))) : 'post';

            $SCHEMA = null;

            if ($TYPE === 'post') {
                $SCHEMA = ($ID !== 0) ? get_post_meta($ID, 'XAGIO_SEO_SCHEMA_META', true) : get_option('XAGIO_SEO_SCHEMA_META');
            } elseif ($TYPE === 'term' && $ID !== 0) {
                $tag = get_term($ID);
                if ($tag && !is_wp_error($tag)) {
                    $SCHEMA = get_term_meta($tag->term_id, 'XAGIO_SEO_SCHEMA_META', true);
                }
            }

            xagio_json('success', 'Retrieved schema(s).', $SCHEMA);
        }

        public static function removeSchema($request = null)
        {


            $schema_id = !empty($request->get_param('schema_id')) && !empty($request->get_param('schema_id')) ? sanitize_text_field(wp_unslash($request->get_param('schema_id'))) : null;
            $page_ids  = !empty($request->get_param('page_ids')) && !empty($request->get_param('page_ids')) ? array_map('intval', wp_unslash($request->get_param('page_ids'))) : [];

            if (is_null($schema_id) || empty($page_ids)) {
                xagio_json('error', 'Not a valid ID or no pages selected.');
            } else {
                foreach ($page_ids as $page_id) {
                    if ($page_id === 0) {
                        self::removeSchemaFromGlobal($schema_id);
                    } else {
                        self::removeSchemaFromPage($schema_id, $page_id);
                    }
                }
                xagio_json('success', 'Removed successfully');
            }
        }

        private static function removeSchemaFromGlobal($schema_id)
        {
            $schema_meta = get_option('XAGIO_SEO_SCHEMA_META', []);

            if (!empty($schema_meta)) {
                foreach ($schema_meta as $index => $meta) {
                    if ($meta['id'] === $schema_id) {
                        unset($schema_meta[$index]);
                        update_option('XAGIO_SEO_SCHEMA_META', array_values($schema_meta));
                        break;
                    }
                }

                $schema_data = get_option('XAGIO_SEO_SCHEMA_DATA', []);
                if (!empty($schema_data[$index])) {
                    unset($schema_data[$index]);
                    update_option('XAGIO_SEO_SCHEMA_DATA', array_values($schema_data));
                }
            }
        }

        private static function removeSchemaFromPage($schema_id, $page_id)
        {
            $schema_meta = get_post_meta($page_id, 'XAGIO_SEO_SCHEMA_META', true);

            if (!empty($schema_meta)) {
                foreach ($schema_meta as $index => $meta) {
                    if ($meta['id'] === $schema_id) {
                        unset($schema_meta[$index]);
                        update_post_meta($page_id, 'XAGIO_SEO_SCHEMA_META', array_values($schema_meta));
                        break;
                    }
                }

                $schema_data = get_post_meta($page_id, 'XAGIO_SEO_SCHEMA_DATA', true);
                if (!empty($schema_data[$index])) {
                    unset($schema_data[$index]);
                    update_post_meta($page_id, 'XAGIO_SEO_SCHEMA_DATA', array_values($schema_data));
                }
            }
        }


        public static function getPost($request = null)
        {


            $ID = !empty($request->get_param('id')) && !empty($request->get_param('id')) ? intval($request->get_param('id')) : 0;

            if ($ID === 0) {
                xagio_json('error', 'ID is not properly being sent.');
            } else {
                $page = get_post($ID);

                if ($page) {
                    xagio_json('success', 'Successfully retrieved post content.', $page);
                } else {
                    xagio_json('error', 'Post not found.');
                }
            }
        }

        public static function updatePost($request = null)
        {


            $DATA = !empty($request->get_param('data')) ? map_deep(wp_unslash($request->get_param('data')), 'sanitize_text_field') : null;

            if (is_null($DATA)) {
                xagio_json('error', 'DATA is not properly being sent.');
            } else {
                $DATA   = array_map('sanitize_text_field', $DATA);
                $result = wp_update_post($DATA, true);

                if (is_wp_error($result)) {
                    xagio_json('error', 'Failed to update post content.', $result->get_error_message());
                } else {
                    xagio_json('success', 'Successfully updated post content.');
                }
            }
        }

        public static function createPost($request = null)
        {


            $DATA = !empty($request->get_param('data')) ? map_deep(wp_unslash($request->get_param('data')), 'sanitize_text_field') : null;

            if (is_null($DATA)) {
                xagio_json('error', 'DATA is not properly being sent.');
            } else {
                $DATA    = array_map('sanitize_text_field', $DATA);
                $post_id = wp_insert_post($DATA, true);

                if (is_wp_error($post_id)) {
                    xagio_json('error', 'Failed to create new post.', $post_id->get_error_message());
                } else {
                    xagio_json('success', 'Successfully created new ' . esc_html($DATA['post_type']) . '.');
                }
            }
        }


        public static function toggleSEO($request = null)
        {
            $ID    = !empty($request->get_param('id')) ? intval($request->get_param('id')) : 0;
            $VALUE = !empty($request->get_param('value')) ? sanitize_text_field(wp_unslash($request->get_param('value'))) : '';

            if ($ID === 0) {
                xagio_json('error', 'ID is not properly being sent.');
            } else {
                update_post_meta($ID, 'XAGIO_SEO', $VALUE);
                xagio_json('success', 'Successfully toggled SEO.');
            }
        }

        public static function apiRequestUpload($apiEndpoint = null, $fileToUpload = null)
        {
            $license_email = '';
            $license_key   = '';
            if (!XAGIO_LICENSE::isLicenseSet($license_email, $license_key)) {
                return false;
            }

            if ($apiEndpoint == null || $fileToUpload == null) {
                return false;
            }

            if (empty($_SERVER['SERVER_NAME'])) {
                return ['status' => 'error', 'message'=> 'Required parameters are missing.'];
            }

            // Set the domain name
            $domain     = preg_replace('/^www\./', '', sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])));
            $user_agent = "Xagio - " . XAGIO_CURRENT_VERSION . " ($domain)";

            // Ensure the file exists
            if (!file_exists($fileToUpload)) {
                return ['status' => 'error', 'message'=> 'File to upload does not exist.'];
            }

            // Prepare the file data for multipart/form-data
            $file_name = basename($fileToUpload);
            $file_data = xagio_file_get_contents($fileToUpload);
            $boundary  = wp_generate_password(24, false); // Unique boundary string for multipart
            $body      = "--$boundary\r\n";
            $body     .= "Content-Disposition: form-data; name=\"license_email\"\r\n\r\n$license_email\r\n";
            $body     .= "--$boundary\r\n";
            $body     .= "Content-Disposition: form-data; name=\"license_key\"\r\n\r\n$license_key\r\n";
            $body     .= "--$boundary\r\n";
            $body     .= "Content-Disposition: form-data; name=\"file_contents\"; filename=\"$file_name\"\r\n";
            $body     .= "Content-Type: " . mime_content_type($fileToUpload) . "\r\n\r\n";
            $body     .= $file_data . "\r\n";
            $body     .= "--$boundary--\r\n";

            // Make the request
            $response = wp_remote_post(XAGIO_PANEL_URL . "/api/" . $apiEndpoint, [
                'method'    => 'POST',
                'timeout'   => 60,
                'headers'   => [
                    'User-Agent'    => $user_agent,
                    'Content-Type'  => "multipart/form-data; boundary=$boundary",
                ],
                'body'      => $body,
            ]);

            // Clean up temporary file after upload
            wp_delete_file($fileToUpload);

            if (is_wp_error($response)) {
                return false;
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (!$data) {
                return false;
            } else {
                return $data;
            }
        }

        public static function apiRequest($apiEndpoint = NULL, $method = 'GET', $args = [], &$http_code = FALSE, $without_license = FALSE)
        {
            if ($apiEndpoint == NULL || XAGIO_CONNECTED == FALSE) {
                if (!$without_license) {
                    return FALSE;
                }
            }

            if (!!empty($_SERVER['SERVER_NAME'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // Set the domain name
            $domain = preg_replace('/^www\./', '', sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])));

            if ($without_license === FALSE) {

                $license_email = null;
                $license_key   = null;

                XAGIO_LICENSE::isLicenseSet($license_email, $license_key);

                // Set the HTTP Query
                $http_query = [
                    'license_email' => $license_email,
                    'license_key'   => $license_key,
                    'domain'        => $domain,
                ];

                $http_query = array_merge($http_query, $args);

            } else {
                $http_query = $args;
            }

            $data = [
                'user-agent'  => "Xagio - " . XAGIO_CURRENT_VERSION . " ($domain)",
                'timeout'     => 30,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => TRUE,
                'method'      => $method
            ];

            if ($method !== 'POST') {
                $apiEndpoint .= '?' . http_build_query($http_query);
            } else {
                $data['body'] = $http_query;
            }

            $response = wp_remote_request(XAGIO_PANEL_URL . "/api/" . $apiEndpoint, $data);
            if (is_wp_error($response)) {
                return FALSE;
            } else {
                $http_code = $response['response']['code'];
                if (empty($response['body'])) {
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

        public static function syncServerAPI($request = null)
        {
            do_action('XAGIO_CHECK_LICENSE');
        }

        private function removeDirectory($path)
        {
            include_once ABSPATH . 'wp-admin/includes/file.php';

            // Initialize the WP Filesystem
            global $wp_filesystem;
            if (!function_exists('WP_Filesystem')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            $creds = request_filesystem_credentials(site_url() . '/wp-admin/', '', false, false, []);
            if (!WP_Filesystem($creds)) {
                return false;
            }

            // Check if the directory exists
            if (!$wp_filesystem->is_dir($path)) {
                return false;
            }

            // Get the list of files in the directory
            $files = $wp_filesystem->dirlist($path);

            // Iterate through the files and delete them
            foreach ($files as $file) {
                $file_path = $path . '/' . $file['name'];
                if ($file['type'] === 'd') {
                    self::removeDirectory($file_path);
                } else {
                    $wp_filesystem->delete($file_path);
                }
            }

            // Delete the directory itself
            $wp_filesystem->rmdir($path);
            return true;
        }
    }
}
