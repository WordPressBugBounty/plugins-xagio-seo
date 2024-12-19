<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_LOG404')) {

    class XAGIO_MODEL_LOG404
    {

        private static function defines()
        {
            define('XAGIO_DISABLE_404_LOGS', filter_var(get_option('XAGIO_DISABLE_404_LOGS'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_ENABLE_SPIDER_404', filter_var(get_option('XAGIO_ENABLE_SPIDER_404'), FILTER_VALIDATE_BOOLEAN));
            define('XAGIO_ENABLE_404_REF_URL', filter_var(get_option('XAGIO_ENABLE_404_REF_URL'), FILTER_VALIDATE_BOOLEAN));
        }

        public static function initialize()
        {
            XAGIO_MODEL_LOG404::defines();

            $listIgnoredUrls = get_option('XAGIO_IGNORE_404_URLS');
            $getLogLmt       = get_option('XAGIO_MAX_LOG_LIMIT');

            if (!is_admin()) {
                $htAccFile = self::chkHtAccExists();
                if ($htAccFile === FALSE) {
                    self::createHtAccFile();
                }

                $defaultIgnoredStr = [
                    '*/pingserver.php',
                    '*/xmlrpc.php'
                ];
                if ($listIgnoredUrls === FALSE) {
                    update_option('XAGIO_IGNORE_404_URLS', $defaultIgnoredStr);
                }
                if ($getLogLmt === FALSE) {
                    update_option('XAGIO_MAX_LOG_LIMIT', 500);
                }

                add_action('wp_enqueue_scripts', [
                    'XAGIO_MODEL_LOG404',
                    'doStuffOn404'
                ], -999999999);
            }

            if (!XAGIO_HAS_ADMIN_PERMISSIONS) return;

            add_action('admin_post_xagio_get_log404s', [
                'XAGIO_MODEL_LOG404',
                'getLog404_Datatables'
            ]);
            add_action('admin_post_xagio_add_log404_redirect', [
                'XAGIO_MODEL_LOG404',
                'addLog404Redirect'
            ]);
            add_action('admin_post_xagio_delete_log404', [
                'XAGIO_MODEL_LOG404',
                'deleteLog404'
            ]);
            add_action('admin_post_xagio_clear_log404', [
                'XAGIO_MODEL_LOG404',
                'clearLog404'
            ]);
            add_action('admin_post_xagio_export_404s_log', [
                'XAGIO_MODEL_LOG404',
                'exportLogs'
            ]);
            add_action('admin_post_xagio_log_404s_settings', [
                'XAGIO_MODEL_LOG404',
                'Log404Settings'
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
            $creation_query  = 'CREATE TABLE xag_log_404s (
			        `id` int(11) NOT NULL AUTO_INCREMENT,
			        `url` varchar(255) NOT NULL,
			        `slug` varchar(255) NOT NULL,
			        `agent` text,
			        `ip` text,
			        `reference` text,
			        `last_hit_counts` int(10) UNSIGNED,
			        `date_created` datetime,
			        `date_updated` datetime NOT NULL DEFAULT NOW(),
			        PRIMARY KEY  (`id`)
			    ) ' . $charset_collate . ';';
            @dbDelta($creation_query);
        }

        public static function removeTable()
        {
            global $wpdb;
            $wpdb->query("DROP TABLE IF EXISTS xag_log_404s");
        }

        public static function chkHtAccExists()
        {
            $htAccPath = ABSPATH . '.htaccess';
            if (!file_exists($htAccPath)) {
                return FALSE;
            } else {
                if (0 == filesize($htAccPath)) {
                    return FALSE;
                }
                return TRUE;
            }
        }

        public static function createHtAccFile()
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

            $htAccPath = ABSPATH . '.htaccess';
            $homeRoot  = wp_parse_url(home_url());

            if (isset($homeRoot['path'])) {
                $homeRoot = trailingslashit($homeRoot['path']);
            } else {
                $homeRoot = '/';
            }

            $rules = "# BEGIN WordPress\n";
            $rules .= "<IfModule mod_rewrite.c>\n";
            $rules .= "RewriteEngine On\n";
            $rules .= "RewriteBase $homeRoot\n";

            /* Prevent -f checks on index.php. */
            $rules .= "RewriteRule ^index\.php$ - [L]\n";

            $rules .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
            $rules .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
            $rules .= "RewriteRule . $homeRoot" . "index.php [L,QSA]\n";
            $rules .= "</IfModule>\n";
            $rules .= "# END WordPress\n";

            // Use WP_Filesystem to write the file
            if (!$wp_filesystem->put_contents($htAccPath, $rules, FS_CHMOD_FILE)) {
                return false;
            }

            return true;
        }

        public static function getIp()
        {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                /* check ip from share internet */
                $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
            } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                /* to check ip is pass from proxy */
                $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
            } else if (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
            }
            return $ip;
        }

        public static function getServerName()
        {
            $host = '';
            if (isset($_SERVER['HTTP_HOST'])) {
                $host = sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST']));
            }

            if (isset($_SERVER['SERVER_NAME'])) {
                $host = sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME']));
            }
            return $host;
        }

        public static function getCurrentUrl()
        {
            $url         = '';
            $urlProtocol = isset($_SERVER['HTTPS']) ? "https" : "http";
            $serverName  = self::getServerName();

            $slug = '';
            if (isset($_SERVER['REQUEST_URI'])) {
                $slug = sanitize_url(wp_unslash($_SERVER['REQUEST_URI']));
            }

            $url = $urlProtocol . '://' . $serverName . $slug;
            return $url;
        }

        public static function getCurrentSlug()
        {
            $slug = '';
            if (isset($_SERVER['REQUEST_URI'])) {
                $slug = sanitize_url(wp_unslash($_SERVER['REQUEST_URI']));
            }
            return $slug;
        }

        public static function doStuffOn404()
        {
            global $wpdb;
            $chk404sStatus = get_option('XAGIO_DISABLE_404_LOGS');

            if ($chk404sStatus != 1) {
                if (is_404()) {
                    $ip               = self::getIp();
                    $url              = self::getCurrentUrl();
                    $slug             = self::getCurrentSlug();
                    $chk404sSpiderLog = get_option('XAGIO_ENABLE_SPIDER_404');
                    $ext              = pathinfo($url, PATHINFO_EXTENSION);

                    if (isset($ext) && !empty($ext)) {
                        $chkBlockedExt = self::chkBlockedExtensions($ext);

                        if ($chkBlockedExt === TRUE) {
                            return FALSE;
                        }
                    }

                    $chkSlug = self::chkSlugStrExists($slug);
                    if ($chkSlug === TRUE) {
                        return FALSE;
                    }

                    $agent = '';
                    if (isset($_SERVER['HTTP_USER_AGENT'])) {
                        $agent = sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']));

                        if ($chk404sSpiderLog != 1) {
                            $checkBot = self::smartIpDetectCrawler($agent);

                            if ($checkBot === TRUE) {
                                return FALSE;
                            }
                        }

                    } else {
                        return FALSE;
                    }

                    $reference = '';
                    if (isset($_SERVER['HTTP_REFERER'])) {

                        $reference = sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER']));
                        $mainUrl   = wp_parse_url($url);
                        $RefUrl    = wp_parse_url($reference);

                        if ($RefUrl === false) {
                            return FALSE; // Invalid URL, stop further processing
                        }

                        if (!isset($RefUrl['scheme']) || !in_array($RefUrl['scheme'], [
                                'http',
                                'https'
                            ])) {
                            return FALSE; // Unsupported scheme, stop further processing
                        }

                        if (empty($RefUrl['host'])) {
                            return FALSE; // Invalid host, stop further processing
                        }

                        if (strtolower($mainUrl['host']) == strtolower($RefUrl['host'])) {
                            return FALSE;
                        }

                        $chkEscRefStr = self::checkEscapeRefStr($reference);

                        if ($chkEscRefStr === TRUE) {
                            return FALSE;
                        }

                    }

                    if (empty($reference)) {
                        $chk404sWithReferenceStatus = get_option('XAGIO_ENABLE_404_REF_URL');
                        if ($chk404sWithReferenceStatus == 1) {
                            return FALSE;
                        }
                    }

                    $getHits = $wpdb->get_row($wpdb->prepare('SELECT id, ip, agent, reference, last_hit_counts FROM xag_log_404s WHERE url = %s', $url), ARRAY_A);

                    if (isset($getHits) && !empty($getHits)) {

                        $ipAr        = json_decode($getHits['ip']);
                        $agentAr     = json_decode($getHits['agent']);
                        $referenceAr = json_decode($getHits['reference']);

                        $ipArRes        = self::insertUpdateJsonVal($ipAr, $ip);
                        $agentArRes     = self::insertUpdateJsonVal($agentAr, $agent);
                        $referenceArRes = self::insertUpdateJsonVal($referenceAr, $reference);

                        if ($ipArRes['method'] === 'update' && $agentArRes['method'] === 'update' && $referenceArRes['method'] === 'update') {
                            $curntHits = $getHits['last_hit_counts'] + 1;
                            $wpdb->update('xag_log_404s', [
                                'ip'              => wp_json_encode($ipArRes['value']),
                                'agent'           => wp_json_encode($agentArRes['value']),
                                'reference'       => wp_json_encode($referenceArRes['value']),
                                'last_hit_counts' => $curntHits,
                            ], [
                                'id' => $getHits['id'],
                            ]);
                        }

                    } else {
                        $ipAddr    = self::insertUpdateJsonVal(NULL, $ip);
                        $userAgent = self::insertUpdateJsonVal(NULL, $agent);
                        $referrer  = self::insertUpdateJsonVal(NULL, $reference);

                        if ($ipAddr['method'] === 'insert' && $userAgent['method'] === 'insert' && $referrer['method'] === 'insert') {
                            $wpdb->insert('xag_log_404s', [
                                'url'             => $url,
                                'slug'            => $slug,
                                'agent'           => wp_json_encode($userAgent['value']),
                                'reference'       => wp_json_encode($referrer['value']),
                                'ip'              => wp_json_encode($ipAddr['value']),
                                'last_hit_counts' => 1,
                                'date_created'    => gmdate('Y-m-d H:i:s'),
                            ]);
                        }
                    }

                    $get404sLogLmt = get_option('XAGIO_MAX_LOG_LIMIT');
                    $logLmt        = intval($get404sLogLmt);

                    if (isset($logLmt) && !empty($logLmt)) {
                        $wpdb->query(
                            $wpdb->prepare(
                                'DELETE tb FROM `xag_log_404s` AS tb
										JOIN
											( SELECT id AS tmp_tb
											  FROM `xag_log_404s`
											  ORDER BY tmp_tb DESC
											  LIMIT 18446744073709551615 OFFSET %d
											) tmp_limit
										ON tb.id <= tmp_limit.tmp_tb', $logLmt
                            )
                        );
                    }

                    $getGlobal301RdirectUrl = get_option('XAGIO_GLOBAL_404_REDIRECTION_URL');

                    if (isset($getGlobal301RdirectUrl) && !empty($getGlobal301RdirectUrl) && !XAGIO_MODEL_SHARED_PROJECT::is_shared_project_page()) {
                        wp_redirect($getGlobal301RdirectUrl);
                        exit;
                    }

                }
            } else {
                return FALSE;
            }
        }

        public static function insertUpdateJsonVal($ArVal, $currVal)
        {
            if (is_array($ArVal)) {

                if (!empty($currVal)) {
                    if (!in_array($currVal, $ArVal)) {
                        $ArVal[] = $currVal;
                        return [
                            'value'  => $ArVal,
                            'method' => 'update'
                        ];
                    }
                }
                return [
                    'value'  => $ArVal,
                    'method' => 'update'
                ];

            } else {

                $defaultAr = [];
                if (!empty($currVal)) {
                    $defaultAr[] = $currVal;
                    return [
                        'value'  => $defaultAr,
                        'method' => 'insert'
                    ];
                }
                return [
                    'value'  => $defaultAr,
                    'method' => 'insert'
                ];

            }
        }

        public static function getLog404_Datatables()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            $aColumns = [
                'id',
                'last_hit_counts',
                'url',
                'date_updated',
                'ip',
                'reference',
                'agent',
                'slug',
            ];

            $sIndexColumn = "id";
            $sTable       = "xag_log_404s";

            // Initialize the query parameters array for dynamic values
            $queryParams = [];

            // Paging
            $sLimit = "LIMIT 0, 3000";
            if (isset($_POST['iDisplayStart']) && isset($_POST['iDisplayLength']) && $_POST['iDisplayLength'] != '-1') {
                $sLimit = "LIMIT %d, %d";
                $queryParams[] = intval($_POST['iDisplayStart']);
                $queryParams[] = intval($_POST['iDisplayLength']);
            }

            // Ordering
            $sOrder = '';
            if (isset($_POST['iSortCol_0'])) {
                $sOrderArr = [];
                if (isset($_POST['iSortingCols'])) {
                    for ($i = 0; $i < intval($_POST['iSortingCols']); $i++) {
                        if (isset($_POST['iSortCol_' . $i]) && isset($_POST['sSortDir_' . $i])) {
                            if (isset($_POST['bSortable_' . intval($_POST['iSortCol_' . $i])]) && $_POST['bSortable_' . intval($_POST['iSortCol_' . $i])] == "true") {
                                $columnName = esc_sql($aColumns[intval($_POST['iSortCol_' . $i])]);
                                $sortDir    = sanitize_text_field(wp_unslash($_POST['sSortDir_' . $i]));
                                $sOrderArr[] = "$columnName $sortDir";
                            }
                        }
                    }
                }
                if (!empty($sOrderArr)) {
                    $sOrder = "ORDER BY " . implode(", ", $sOrderArr);
                }
            }

            // Filtering
            $customFilters = [
                'search' => isset($_POST['sSearch']) ? sanitize_text_field(wp_unslash($_POST['sSearch'])) : '',
            ];

            $customWhere = "";

            foreach ($customFilters as $key => $column) {
                if ($column != '') {
                    if ($customWhere == "") {
                        $customWhere = "WHERE ";
                    } else {
                        $customWhere .= " AND ";
                    }

                    if ($key == 'search') {
                        $customWhere .= "`url` LIKE %s";
                        $queryParams[] = '%' . $wpdb->esc_like($column) . '%';
                    }
                }
            }

            // Combine columns into a string for the query
            $columns = implode(", ", array_map('esc_sql', $aColumns));

            // Execute the query with a single wpdb::prepare()
            $rResult = $wpdb->get_results(
                $wpdb->prepare("
    SELECT SQL_CALC_FOUND_ROWS $columns
    FROM $sTable
    $customWhere
    $sOrder
    $sLimit
", ...$queryParams),
                ARRAY_A
            );


            // Get filtered total
            $iFilteredTotal = $wpdb->get_var("SELECT FOUND_ROWS()");

            // Get total data set length
            $iTotal = $wpdb->get_var($wpdb->prepare("SELECT COUNT(%s) FROM %s", esc_sql($sIndexColumn), esc_sql($sTable)));

            $datt = [];
            foreach ($rResult as $d) {
                // Keep only the relative URL path
                $d['url'] = wp_parse_url($d['url'], PHP_URL_PATH);

                // Format the date
                $d['date_updated'] = gmdate("M dS, Y", strtotime($d['date_updated']));
                $datt[]            = $d;
            }

            // Output
            $output = [
                "sEcho"                => isset($_POST['sEcho']) ? intval($_POST['sEcho']) : 0,
                "iTotalRecords"        => $iTotal,
                "iTotalDisplayRecords" => $iFilteredTotal,
                "aaData"               => $datt,
            ];

            echo wp_json_encode($output);
        }

        public static function addLog404Redirect()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (isset($_POST['old404URL']) && isset($_POST['newURL'])) {
                self::add404Redirect(sanitize_text_field(wp_unslash($_POST['old404URL'])), sanitize_text_field(wp_unslash($_POST['newURL'])));
            }
            xagio_json('error', '404 URL not fetched! Please add this URL from 301 redirects.');
        }

        public static function add404Redirect($old, $new)
        {
            /* Check if empty */
            if (empty($old) || empty($new)) {
                return;
            }

            global $wpdb;

            /* Remove old data */
            $wpdb->delete('xag_', ['new' => $old]);

            $old = ltrim($old, '/');
            $old = rtrim($old, '/');

            $new = ltrim($new, '/');
            $new = rtrim($new, '/');

            $chkExistsUrl = XAGIO_MODEL_REDIRECTS::checkExistsUrl($old);

            if (isset($chkExistsUrl['id']) && $chkExistsUrl['status'] === TRUE) {

                $wpdb->update('xag_redirects',
                    [
                        'old' => $old,
                        'new' => $new,
                    ], [
                        'id' => $chkExistsUrl['id'],
                    ]
                );

            } else {

                $wpdb->insert('xag_redirects', [
                    'old'          => $old,
                    'new'          => $new,
                    'date_created' => gmdate('Y-m-d H:i:s'),
                ]);

            }

            xagio_json('success', 'Redirect successfully added in 301 redirects list.');
        }

        public static function smartIpDetectCrawler($userAgent)
        {
            /* User lowercase string for comparison. */
            if (isset($userAgent)) {
                $userAgent = strtolower($userAgent);
            } else if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $userAgent = strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])));
            } else {
                $userAgent = 'n/a';
            }
            /* A list of some common words used only for bots and crawlers. */
            $botIdentifiers = [
                'bot',
                'slurp',
                'crawler',
                'crawl',
                'spider',
                'curl',
                'facebook',
                'search',
                'fetch',
            ];
            /* See if one of the identifiers is in the UA string. */
            foreach ($botIdentifiers as $identifier) {
                if (strpos($userAgent, $identifier) !== FALSE) {
                    return TRUE;
                }
            }
            return FALSE;
        }

        public static function checkEscapeRefStr($referer)
        {
            if (isset($referer)) {
                $referer = strtolower($referer);
            } else if (isset($_SERVER['HTTP_REFERER'])) {
                $referer = strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_REFERER'])));
            } else {
                $referer = 'n/a';
            }

            $symbolIdentifiers = [
                '{',
                '}',
                '<',
                '>'
            ];

            foreach ($symbolIdentifiers as $identifier) {
                if (strpos($referer, $identifier) !== FALSE) {
                    return TRUE;
                }
            }
            return FALSE;
        }

        public static function chkBlockedExtensions($ext)
        {
            $ext = strtolower($ext);

            $extAr = [
                'jpg',
                'jpeg',
                'gif',
                'png',
                'tiff',
                'psd',
                'pdf',
                'doc',
                'docx',
                'ppt',
                'xls',
                'eps',
                'ai',
                'indd',
                'raw',
                'mp4',
                'm4a',
                'm4v',
                'f4v',
                'f4a',
                'm4b',
                'm4r',
                'f4b',
                'mov',
                '3gp',
                '3gp2',
                '3g2',
                '3gpp',
                '3gpp2',
                'ogg',
                'oga',
                'ogv',
                'ogx',
                'wmv',
                'wma',
                'asf*',
                'webm',
                'flv',
                'avi',
                'hdv',
                'OP1a',
                'OP-Atom',
                'ts',
                'wav',
                'lxf',
                'gxf*',
                'vob',
                'mp3',
                'aac',
                'ac3',
                'eac3',
                'vorbis',
                'pcm',
                'ico',
                'xml',
                'zip',
                'conf',
                'ini',
                'xsd',
                'env',
                'txt',
                'cfg',
                'bsh',
                'json',
                'log',
                'bshservlet',
                'action'
            ];

            if (in_array($ext, $extAr)) {
                return TRUE;
            }
            return FALSE;
        }

        public static function chkSlugStrExists($slugStr)
        {
            if (isset($slugStr) && !empty($slugStr)) {
                /* lowercase string for comparison. */
                $slugStr = strtolower($slugStr);

                $defaultStrAr = [
                    '/wp-content/',
                    '/uploads/',
                    '/vendor/',
                    '/phpmyadmin/',
                    '/mysqladmin/',
                    '/temp/',
                    '/tmp/',
                ];

                foreach ($defaultStrAr as $defaultStr) {
                    if (strpos($slugStr, $defaultStr) !== FALSE) {
                        return TRUE;
                    }
                }

                $listIgnoredUrls = get_option('XAGIO_IGNORE_404_URLS');
                $listIgnoredUrls = implode('', $listIgnoredUrls);

                if (isset($listIgnoredUrls) && !empty($listIgnoredUrls)) {
                    $ignoredUrlAr = explode('*', $listIgnoredUrls);

                    foreach ($ignoredUrlAr as $ignoredUrl) {
                        $ignoredUrl = strtolower($ignoredUrl);
                        if (!empty($ignoredUrl)) {
                            if (strpos($slugStr, $ignoredUrl) !== FALSE) {
                                return TRUE;
                            }
                        }
                    }

                }
                return FALSE;
            }
            return FALSE;
        }

        public static function deleteLog404()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $ID = intval($_POST['id']);

            $RemoveIDs = explode(',', $ID);

            foreach ($RemoveIDs as $i_d) {
                $wpdb->delete('xag_log_404s', ['id' => $i_d]);
            }
        }

        public static function clearLog404()
        {
            global $wpdb;
            $wpdb->query('TRUNCATE TABLE xag_log_404s');
        }

        public static function exportLogs()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['project_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $project_id = abs(intval($_POST['project_id']));
            if (is_admin()) {
                self::exportLogsToCsv($project_id);
            } else {
                exit('You are not auththorized user.');
            }
        }

        public static function exportLogsToCsv($project_id)
        {
            global $wpdb;
            $getLogs = $wpdb->get_results("SELECT * FROM xag_log_404s WHERE slug != ''");

            $output = "";
            $output .= '"Total Entries","' . count($getLogs) . '",';
            $output .= "\n";
            $output .= 'Hits,404 URL,IP Addresses,Referers,User Agents,Last Hit';
            $output .= "\n";

            foreach ($getLogs as $log) {

                $ips        = '';
                $references = '';
                $agents     = '';

                $log['ip']        = json_decode($log['ip']);
                $log['reference'] = json_decode($log['reference']);
                $log['agent']     = json_decode($log['agent']);

                $ips        = implode(" , ", $log['ip']);
                $references = implode(" , ", $log['reference']);
                $agents     = implode(" , ", $log['agent']);

                $output .= '"' . $log['last_hit_counts'] . '","' . $log['url'] . '","' . $ips . '","' . $references . '","' . $agents . '","' . $log['date_updated'] . '",';
                $output .= "\n";

            }

            $filename = "log404s.csv";
            header('Content-type: application/csv');
            header('Content-Disposition: attachment; filename=' . $filename);

            echo wp_kses_data($output);
            exit;
        }

        public static function Log404Settings()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (isset($_POST['XAGIO_DISABLE_404_LOGS'])) {
                if ($_POST['XAGIO_DISABLE_404_LOGS'] == 1) {
                    update_option('XAGIO_DISABLE_404_LOGS', TRUE);
                    $statusMessages[] = '404s Log disabled.';
                } else {
                    update_option('XAGIO_DISABLE_404_LOGS', FALSE);
                    $statusMessages[] = '404s Log enabled.';
                }
            }

            if (isset($_POST['XAGIO_ENABLE_SPIDER_404'])) {
                if ($_POST['XAGIO_ENABLE_SPIDER_404'] == 1) {
                    update_option('XAGIO_ENABLE_SPIDER_404', TRUE);
                    $statusMessages[] = '404s Spider Log enabled.';
                } else {
                    update_option('XAGIO_ENABLE_SPIDER_404', FALSE);
                    $statusMessages[] = '404s Spider Log disabled.';
                }
            }

            if (isset($_POST['XAGIO_MAX_LOG_LIMIT'])) {
                $maxLogLimit = trim(sanitize_text_field(wp_unslash($_POST['XAGIO_MAX_LOG_LIMIT'])));
            } else {
                $maxLogLimit = 10;
            }

            if (isset($maxLogLimit) && ctype_digit($maxLogLimit)) {
                update_option('XAGIO_MAX_LOG_LIMIT', $maxLogLimit);
                $statusMessages[] = 'Updated 404s max log limit.';
            }

            if (isset($_POST['ignored-urls-list'])) {
                $ignoredUrlsList = explode("\n", sanitize_text_field(wp_unslash($_POST['ignored-urls-list'])));
                update_option('XAGIO_IGNORE_404_URLS', $ignoredUrlsList);
                $statusMessages[] = 'Updated 404s ignored URLs.';
            }

            if (isset($_POST['XAGIO_GLOBAL_404_REDIRECTION_URL'])) {
                $global301Redirect = sanitize_text_field(wp_unslash($_POST['XAGIO_GLOBAL_404_REDIRECTION_URL']));
            } else {
                $global301Redirect = false;
            }

            if (!empty($global301Redirect)) {
                update_option('XAGIO_GLOBAL_404_REDIRECTION_URL', $global301Redirect);
                $statusMessages[] = 'Updated global 301 redirect URL.';
            } else {
                update_option('XAGIO_GLOBAL_404_REDIRECTION_URL', '');
                $statusMessages[] = 'Updated global 301 redirect URL.';
            }

            if (isset($_POST['XAGIO_ENABLE_404_REF_URL'])) {
                if ($_POST['XAGIO_ENABLE_404_REF_URL'] == 1) {
                    update_option('XAGIO_ENABLE_404_REF_URL', TRUE);
                    $statusMessages[] = '404s Log with referrers disabled.';
                } else {
                    update_option('XAGIO_ENABLE_404_REF_URL', FALSE);
                    $statusMessages[] = '404s Log with referrers enabled.';
                }
            }

            if (isset($_POST['XAGIO_DISABLE_AUTOMATIC_REDIRECTS'])) {
                if ($_POST['XAGIO_DISABLE_AUTOMATIC_REDIRECTS'] == 1) {
                    update_option('XAGIO_DISABLE_AUTOMATIC_REDIRECTS', TRUE);
                } else {
                    update_option('XAGIO_DISABLE_AUTOMATIC_REDIRECTS', FALSE);
                }
            }

            xagio_json('success', "Operation completed! \n" . join("\n", $statusMessages));

        }

    }
}
