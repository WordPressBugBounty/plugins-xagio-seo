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

            if (!XAGIO_HAS_ADMIN_PERMISSIONS)
                return;

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
            add_action('admin_post_xagio_retrieve_metrics', [
                'XAGIO_MODEL_LOG404',
                'retrieveMetrics'
            ]);
        }

        //--------------------------------------------
        //
        //             MySQL Operations
        //
        //--------------------------------------------


        public static function createTable() {
            global $wpdb;
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $charset_collate = $wpdb->get_charset_collate();
            $table_404       = $wpdb->prefix . 'xag_log_404s';
            $table_ref       = $wpdb->prefix . 'xag_log_404s_referrers';

            // 1) Main 404 log table (no 'reference' column in the new schema)
            $sql_404 = "CREATE TABLE {$table_404} (
        id               INT(11) NOT NULL AUTO_INCREMENT,
        url              VARCHAR(255) NOT NULL,
        slug             VARCHAR(255) NOT NULL,
        agent            TEXT,
        ip               TEXT,
        last_hit_counts  INT(10) UNSIGNED DEFAULT 0,
        date_created     DATETIME DEFAULT CURRENT_TIMESTAMP,
        date_updated     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY slug (slug)
    ) {$charset_collate};";
            @dbDelta($sql_404);

            // 2) Referrers table (fixed trailing comma)
            $sql_ref = "CREATE TABLE {$table_ref} (
        id                INT(11) NOT NULL AUTO_INCREMENT,
        log_id            INT(11),
        reference         VARCHAR(2048),
        reference_domain  VARCHAR(255),
        DR                INT(10),
        UR                INT(10),
        PRIMARY KEY  (id),
        KEY log_id (log_id)
    ) {$charset_collate};";
            @dbDelta($sql_ref);

            // 3) Migrate legacy 'reference' JSON from old column if it exists (one-time)
            $already_migrated = get_option('XAG_MIGRATE_REF', false);
            if ( ! $already_migrated ) {
                // Detect legacy column safely (no INFORMATION_SCHEMA permission needed)
                $has_reference = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'reference'", $table_404
                ) );

                if ( ! $has_reference ) {
                    // Fallback if INFORMATION_SCHEMA is restricted
                    $has_reference = $wpdb->get_var( $wpdb->prepare(
                        "SELECT COUNT(*) FROM (
                    SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s
                ) t WHERE COLUMN_NAME = 'reference'", $table_404
                    ) );
                }

                if ( $has_reference ) {
                    // Only select rows that actually have data to migrate
                    $logs = $wpdb->get_results( "SELECT id, reference FROM `{$wpdb->prefix}xag_log_404s` WHERE reference IS NOT NULL AND reference <> ''", ARRAY_A );
                    if ( ! empty( $logs ) ) {
                        foreach ( $logs as $log ) {
                            $log_id     = intval( $log['id'] );
                            $references = json_decode( $log['reference'], true );

                            if ( is_array( $references ) ) {
                                foreach ( $references as $ref ) {
                                    if ( ! is_string( $ref ) || $ref === '' ) {
                                        continue;
                                    }
                                    $ref_domain = wp_parse_url( $ref, PHP_URL_HOST );
                                    $wpdb->insert(
                                        $table_ref,
                                        array(
                                            'log_id'           => $log_id,
                                            'reference'        => $ref,
                                            'reference_domain' => $ref_domain,
                                        ),
                                        array( '%d', '%s', '%s' )
                                    );
                                }
                            }
                        }
                    }

                    // Drop legacy column now that we've migrated
                    // (Guard in case of permissions / older MySQL)
                    $wpdb->query( "ALTER TABLE `{$wpdb->prefix}xag_log_404s` DROP COLUMN `reference`" );
                }

                update_option( 'XAG_MIGRATE_REF', true );
            }
        }


        public static function removeTable()
        {
            global $wpdb;
            $wpdb->query("DROP TABLE IF EXISTS xag_log_404s");
            $wpdb->query("DROP TABLE IF EXISTS xag_log_404s_referrers");
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

            if (defined('XAGIO_DOMAIN') && XAGIO_DOMAIN !== '') {
                $host = XAGIO_DOMAIN;
            }
            return $host;
        }

        public static function getCurrentUrl()
        {
            $xagio_url         = '';
            $urlProtocol = isset($_SERVER['HTTPS']) ? "https" : "http";
            $serverName  = self::getServerName();

            $slug = '';
            if (isset($_SERVER['REQUEST_URI'])) {
                $slug = sanitize_url(wp_unslash($_SERVER['REQUEST_URI']));
            }

            $xagio_url = $urlProtocol . '://' . $serverName . $slug;
            return $xagio_url;
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
                    $xagio_url              = self::getCurrentUrl();
                    $slug             = self::getCurrentSlug();
                    $chk404sSpiderLog = get_option('XAGIO_ENABLE_SPIDER_404');
                    $ext              = pathinfo($xagio_url, PATHINFO_EXTENSION);

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

                        $reference = sanitize_url(wp_unslash($_SERVER['HTTP_REFERER']));
                        $mainUrl   = wp_parse_url($xagio_url);
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

                    $getHits = $wpdb->get_row($wpdb->prepare('SELECT id, ip, agent, last_hit_counts FROM xag_log_404s WHERE url = %s', $xagio_url), ARRAY_A);

                    if (isset($getHits) && !empty($getHits)) {

                        $ipAr        = json_decode($getHits['ip']);
                        $agentAr     = json_decode($getHits['agent']);


                        $log_id = intval($getHits['id']);
                        $referenceAr = $wpdb->get_results($wpdb->prepare("SELECT reference FROM xag_log_404s_referrers WHERE log_id = %d", $log_id), ARRAY_A);
                        $referenceArRes = array_column($referenceAr, 'reference');
                        $ipArRes        = self::insertUpdateJsonVal($ipAr, $ip);
                        $agentArRes     = self::insertUpdateJsonVal($agentAr, $agent);

                        if ($ipArRes['method'] === 'update' && $agentArRes['method'] === 'update') {
                            $curntHits = $getHits['last_hit_counts'] + 1;
                            $wpdb->update('xag_log_404s', [
                                'ip'              => wp_json_encode($ipArRes['value']),
                                'agent'           => wp_json_encode($agentArRes['value']),
                                'last_hit_counts' => $curntHits,
                            ], [
                                'id' => $log_id,
                            ]);

                            if(!empty($reference) && !in_array($reference, $referenceArRes))
                            {
                                $ref_domain = wp_parse_url($reference, PHP_URL_HOST);
                                $wpdb->insert('xag_log_404s_referrers', [
                                    'log_id'     => $log_id,
                                    'reference'  => $reference,
                                    'reference_domain' => $ref_domain
                                ]);
                            }
                        }

                    } else {
                        $ipAddr    = self::insertUpdateJsonVal(NULL, $ip);
                        $userAgent = self::insertUpdateJsonVal(NULL, $agent);

                        if ($ipAddr['method'] === 'insert' && $userAgent['method'] === 'insert') {
                            $inserted = $wpdb->insert('xag_log_404s', [
                                'url'             => $xagio_url,
                                'slug'            => $slug,
                                'agent'           => wp_json_encode($userAgent['value']),
                                'ip'              => wp_json_encode($ipAddr['value']),
                                'last_hit_counts' => 1,
                                'date_created'    => gmdate('Y-m-d H:i:s'),
                            ]);

                            if( $inserted ) {
                                $log_id = $wpdb->insert_id;
                                if(!empty($reference))
                                {
                                    $ref_domain = wp_parse_url($reference, PHP_URL_HOST);
                                    $wpdb->insert('xag_log_404s_referrers', [
                                        'log_id'     => $log_id,
                                        'reference'  => $reference,
                                        'reference_domain' => $ref_domain
                                    ]);
                                }
                            }
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
                        xagio_redirect($getGlobal301RdirectUrl, 301);
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

        public static function getLog404_Datatables() {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            $start  = isset($_POST['iDisplayStart']) ? absint($_POST['iDisplayStart']) : 0;
            $length = isset($_POST['iDisplayLength']) ? absint($_POST['iDisplayLength']) : 3000;

            if ($length < 1) {
                $length = 3000;
            }
            if ($length > 3000) {
                $length = 3000;
            }

            // Search
            $search = isset($_POST['sSearch']) ? sanitize_text_field(wp_unslash($_POST['sSearch'])) : '';
            $like   = ($search !== '') ? ('%' . $wpdb->esc_like($search) . '%') : '';

            // ORDER BY (whitelist)
            $sortable = [
                0 => 'id',
                1 => 'last_hit_counts',
                2 => 'url',
                3 => 'date_updated',
                4 => 'ip',
                5 => 'agent',
                6 => 'slug',
            ];

            $order_by  = 'id';
            $order_dir = 'DESC';

            if (isset($_POST['iSortCol_0'])) {
                $col_index = absint($_POST['iSortCol_0']);
                if (isset($sortable[$col_index])) {
                    $order_by = $sortable[$col_index];
                }
            }

            if (isset($_POST['sSortDir_0'])) {
                $dir = strtolower(sanitize_text_field(wp_unslash($_POST['sSortDir_0'])));
                $order_dir = ($dir === 'asc') ? 'ASC' : 'DESC';
            }

            // NOTE: to satisfy "no table variables", use table names directly.
            if ($like !== '') {
                $rResult = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT SQL_CALC_FOUND_ROWS id, last_hit_counts, url, date_updated, ip, agent, slug
                 FROM {$wpdb->prefix}xag_log_404s
                 WHERE slug <> '' AND url LIKE %s
                 ORDER BY {$order_by} {$order_dir}
                 LIMIT %d, %d",
                        $like,
                        $start,
                        $length
                    ),
                    ARRAY_A
                );
            } else {
                $rResult = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT SQL_CALC_FOUND_ROWS id, last_hit_counts, url, date_updated, ip, agent, slug
                 FROM {$wpdb->prefix}xag_log_404s
                 WHERE slug <> ''
                 ORDER BY {$order_by} {$order_dir}
                 LIMIT %d, %d",
                        $start,
                        $length
                    ),
                    ARRAY_A
                );
            }

            $iFilteredTotal = (int) $wpdb->get_var('SELECT FOUND_ROWS()');

            $iTotal = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}xag_log_404s WHERE slug <> ''"
            );

            $datt = [];

            foreach ($rResult as $d) {
                if (self::chkSlugStrExists($d['slug']) === true) {
                    continue;
                }

                [ $safe_href, $safe_text ] = self::escapeSequence($d['url']);
                $d['url_href'] = $safe_href;
                $d['url_text'] = $safe_text;

                $d['date_updated'] = gmdate("M dS, Y", strtotime($d['date_updated']));

                $d['agent'] = wp_json_encode(array_map('esc_html', (array) json_decode($d['agent'], true)));
                $d['ip']    = wp_json_encode(array_map('esc_html', (array) json_decode($d['ip'], true)));

                $log_id = absint($d['id']);

                $referrers = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}xag_log_404s_referrers WHERE log_id = %d",
                        $log_id
                    ),
                    ARRAY_A
                );

                $d['reference'] = wp_json_encode($referrers);

                $datt[] = $d;
            }

            $xagio_output = [
                "sEcho"                => isset($_POST['sEcho']) ? absint($_POST['sEcho']) : 0,
                "iTotalRecords"        => $iTotal,
                "iTotalDisplayRecords" => $iFilteredTotal,
                "aaData"               => $datt,
            ];

            wp_send_json($xagio_output);
        }


        public static function escapeSequence($xagio_url)
        {
            // drop fragments just in case
            $xagio_url      = esc_url_raw(strtok($xagio_url, '#'));
            $pathonly = esc_html(wp_parse_url($xagio_url, PHP_URL_PATH)); // visible text
            return [
                $xagio_url,
                $pathonly
            ];
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

                $wpdb->update('xag_redirects', [
                        'old' => $old,
                        'new' => $new,
                    ], [
                        'id' => $chkExistsUrl['id'],
                    ]);

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
                $referer = strtolower(sanitize_url(wp_unslash($_SERVER['HTTP_REFERER'])));
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

            if (is_admin()) {
                self::exportLogsToCsv();
            } else {
                exit('You are not auththorized user.');
            }
        }

        public static function exportLogsToCsv()
        {
            global $wpdb;
            $getLogs = $wpdb->get_results("SELECT * FROM xag_log_404s WHERE slug != ''", ARRAY_A);
            
            $xagio_output = "";
            $xagio_content = "";
            $total = 0;

            foreach ($getLogs as $log) {
                if(self::chkSlugStrExists($log['slug']) !== TRUE) {
                    $ips        = '';
                    $references = '';
                    $agents     = '';
    
                    $log['ip']        = json_decode($log['ip']);

                    $log_id = intval($log['id']);
                    $referenceAr = $wpdb->get_results($wpdb->prepare( "SELECT reference FROM {$wpdb->prefix}xag_log_404s_referrers WHERE log_id = %d", $log_id ), ARRAY_A );
                    $referenceArRes = array_column($referenceAr, 'reference');

                    $log['agent']     = json_decode($log['agent']);
    
                    $ips        = implode(" , ", $log['ip']);
                    $references = implode(" , ", $referenceArRes);
                    $agents     = implode(" , ", $log['agent']);
    
                    $xagio_content .= '"' . $log['last_hit_counts'] . '","' . $log['url'] . '","' . $ips . '","' . $references . '","' . $agents . '","' . $log['date_updated'] . '",';
                    $xagio_content .= "\n";

                    $total++ ;
                }
            }

            $xagio_output .= '"Total Entries","' . $total . '",';
            $xagio_output .= "\n";
            $xagio_output .= 'Hits,404 URL,IP Addresses,Referers,User Agents,Last Hit';
            $xagio_output .= "\n";
            $xagio_output .= $xagio_content;

            $filename = "log404s.csv";
            header('Content-type: application/csv');
            header('Content-Disposition: attachment; filename=' . $filename);

            echo wp_kses_data($xagio_output);
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
                $raw = sanitize_textarea_field( wp_unslash( $_POST['ignored-urls-list'] ) );
                $normalized = str_replace(["\r\n", "\r"], "\n", $raw);
                $ignoredUrlsList = explode("\n", $normalized);
                update_option('XAGIO_IGNORE_404_URLS', array_map('sanitize_text_field', $ignoredUrlsList));
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

        public static function retrieveMetrics ()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');
            global $wpdb;

            if (!isset($_POST['ids'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $ID = sanitize_text_field(wp_unslash($_POST['ids']));
            $RemoveIDs = explode(',', $ID);

            $targets = array();
            foreach ($RemoveIDs as $i_d) {
                $i_d = intval($i_d);
                $referrers = $wpdb->get_results($wpdb->prepare("SELECT * FROM xag_log_404s_referrers WHERE log_id =  %d", $i_d), ARRAY_A);
                foreach ($referrers as $referer) {
                    $targets[] = $referer['reference'];
                }
                
            }

            $temp_targets = [];
            $domain_targets = array();
            $length = 0;
            foreach ($targets as $target) {
                $temp_targets [] = $target;
                $domain = wp_parse_url($target, PHP_URL_HOST);
                $domain_targets[] = $domain;
                $length ++;
                if($length == 1000) {
                    $results = self::getMetrics($temp_targets, 'url');
                    if($results['status'] == 'error') {
                        xagio_json('error', 'Error connecting to API, please try again. You were not charged for this action.');
                    }
                    $results = self::getMetrics($domain_targets, 'host');
                    if($results['status'] == 'error') {
                        xagio_json('error', 'Error connecting to API, please try again. You were not charged for this action.');
                    }
                    $temp_targets = array();
                    $domain_targets = array();
                    $length = 0;
                }
            }
            if(!empty($temp_targets)) {
                $results = self::getMetrics($temp_targets, 'url');
                if($results['status'] == 'error') {
                    xagio_json('error', 'Error connecting to API, please try again. You were not charged for this action.');
                }
                $results = self::getMetrics($domain_targets, 'host');
                if($results['status'] == 'error') {
                    xagio_json('error', 'Error connecting to API, please try again. You were not charged for this action.');
                }
            }
            xagio_json('success', 'Track Referers successfully.');
        }

        static function getMetrics($tasks, $mode){
            global $wpdb;
            $task_array = array();
            $task_array[] = array(
                'targets' => $tasks
            );

            $xagio_response = XAGIO_API::apiRequest($endpoint = 'get_metrics', $method = 'POST', [
                'tasks'         => $task_array,
            ], $xagio_http_code);

            if($xagio_http_code == 200) {
                if( (int)$xagio_response['status_code'] == 20000 ) {
                    foreach ($xagio_response['tasks'] as $task) {
                        if( (int)$task['status_code'] == 20000 ) {
                            foreach($task['result'] as $items) {
                                if($items['items_count'] > 0) {
                                    foreach($items['items'] as $xagio_item) {
                                        $source = $xagio_item['target'];
                                        $rank = $xagio_item['rank'];
                                        if($mode == 'url') {
                                            $wpdb->update('xag_log_404s_referrers', [
                                                'UR'              => intval($rank),
                                            ], [
                                                'reference' => $source,
                                            ]);
                                        } else if($mode == 'host') {
                                            $wpdb->update('xag_log_404s_referrers', [
                                                'DR'              => intval($rank),
                                            ], [
                                                'reference_domain' => $source,
                                            ]);
                                        }
                                    }
                                }
                            }                                        
                            
                        }
                    }
                }

                return array('status' => 'success', 'message' => 'retrived successfully');

            } else {
                $xagio_result = array(
                    'status' => 'error',
                    'message' => $xagio_response['message']
                );
                return $xagio_result;
            }
        }

    }
}
