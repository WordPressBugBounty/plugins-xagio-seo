<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_REDIRECTS')) {

    class XAGIO_MODEL_REDIRECTS
    {

        private static function defines()
        {
            define('XAGIO_DISABLE_AUTOMATIC_REDIRECTS', filter_var(get_option('XAGIO_DISABLE_AUTOMATIC_REDIRECTS'), FILTER_VALIDATE_BOOLEAN));
        }

        public static function initialize()
        {
            XAGIO_MODEL_REDIRECTS::defines();

            add_action('wp_loaded', [
                'XAGIO_MODEL_REDIRECTS',
                'doRedirect'
            ], -999999999);

            if (!XAGIO_HAS_ADMIN_PERMISSIONS) return;

            add_action('admin_post_xagio_get_redirects', [
                'XAGIO_MODEL_REDIRECTS',
                'getRedirects'
            ]);
            add_action('admin_post_xagio_add_redirect', [
                'XAGIO_MODEL_REDIRECTS',
                'addRedirect'
            ]);
            add_action('admin_post_xagio_edit_redirect', [
                'XAGIO_MODEL_REDIRECTS',
                'editRedirect'
            ]);
            add_action('admin_post_xagio_delete_redirect', [
                'XAGIO_MODEL_REDIRECTS',
                'deleteRedirect'
            ]);
            add_action('admin_post_xagio_delete_all_redirects', [
                'XAGIO_MODEL_REDIRECTS',
                'deleteAllRedirects'
            ]);
            add_action('admin_post_xagio_toggle_redirect', [
                'XAGIO_MODEL_REDIRECTS',
                'toggleRedirect'
            ]);

        }


        public static function add($old, $new)
        {
            // Check if empty
            if (empty($old) || empty($new)) {
                return;
            }

            global $wpdb;

            if($old === $new) {
                return;
            }

            // Remove old data
            //$wpdb->delete(array( 'new' => $new ));
            $wpdb->delete('xag_redirects', ['new' => $old]);

            $old = ltrim($old, '/');
            $old = rtrim($old, '/');

            $new = ltrim($new, '/');
            $new = rtrim($new, '/');

            $chkExistsUrl = self::checkExistsUrl($old);

            if (isset($chkExistsUrl['id']) && $chkExistsUrl['status'] === TRUE) {

                $wpdb->update(
                    'xag_redirects', [
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

        }

        public static function addRedirect()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['oldURL']) || !isset($_POST['newURL'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $oldURL = sanitize_text_field(wp_unslash($_POST['oldURL']));
            $newURL = sanitize_text_field(wp_unslash($_POST['newURL']));

            self::add($oldURL, $newURL);
        }

        public static function editRedirect()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['id']) || !isset($_POST['oldURL']) || !isset($_POST['newURL'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $id     = intval($_POST['id']);
            $oldURL = sanitize_text_field(wp_unslash($_POST['oldURL']));
            $newURL = sanitize_text_field(wp_unslash($_POST['newURL']));

            $wpdb->update(
                'xag_redirects', [
                'old' => $oldURL,
                'new' => $newURL,
            ], [
                    'id' => $id,
                ]
            );
        }

        public static function doRedirect()
        {
            if (XAGIO_DISABLE_AUTOMATIC_REDIRECTS === true) return;

            global $wpdb;

            $redirects   = $wpdb->get_results('SELECT * FROM xag_redirects', ARRAY_A);
            $current_url = isset($_SERVER['REQUEST_URI']) ? sanitize_url(wp_unslash($_SERVER['REQUEST_URI'])) : '';

            foreach ($redirects as $r) {
                if ($r['is_redirect_active'] == 0) continue;

                // Clean up the current URL and redirect URL
                $request_uri = strtok($current_url, '?');
                $request_uri = rtrim($request_uri, '/');
                $request_uri_ltrim = ltrim($request_uri, '/');

                // Clean up the old redirect URL
                $old_url = strtok($r['old'], '?');
                $old_url = rtrim($old_url, '/');
                $old_url = ltrim($old_url, '/');

                // Check if URLs match after normalization
                $isRedirectMatch = ($request_uri_ltrim === $old_url);
                $isFrontPageOrHome = $r['old'] === FALSE && (is_front_page() || is_home());

                if ($isRedirectMatch || $isFrontPageOrHome) {
                    // Determine redirect type once
                    $isExternal = strpos($r['new'], 'http') !== FALSE;

                    if ($isExternal) {
                        $redirectUrl = $r['new'];
                    } else {
                        // Ensure internal redirects maintain a consistent trailing slash
                        $redirectUrl = site_url('/' . trim($r['new'], '/') . '/');
                    }

                    wp_redirect($redirectUrl, 301);
                    exit;
                }
            }
        }

        public static function deleteRedirect()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (!isset($_POST['id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $ID = intval($_POST['id']);

            $RemoveIDs = explode(',', $ID);

            foreach ($RemoveIDs as $i_d) {
                $wpdb->delete('xag_redirects', ['id' => $i_d]);
            }

        }

        public static function deleteAllRedirects()
        {
            global $wpdb;
            $wpdb->query('TRUNCATE TABLE xag_redirects');
        }


        public static function getRedirects()
        {
            global $wpdb;

            // Prepare the query using wpdb::prepare()

            // Execute the query and fetch results
            $results = $wpdb->get_results(
                "SELECT id, old, new, is_redirect_active, DATE_FORMAT(date_created, '%b %D, %Y') as date_created 
         FROM xag_redirects", ARRAY_A
            );

            // Return the results in JSON format
            xagio_json('success', 'Retrieved all redirects.', $results);
        }


        public static function toggleRedirect()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (isset($_POST['id']) && isset($_POST['value'])) {

                $wpdb->update('xag_redirects', [
                    'is_redirect_active' => intval(wp_unslash($_POST['value'])),
                ], [
                    'id' => intval($_POST['id']),
                ]);
            }
        }

        public static function checkExistsUrl($oldUrl)
        {
            global $wpdb;

            if (isset($oldUrl)) {
                $redirects = $wpdb->get_results('SELECT * FROM xag_redirects', ARRAY_A);

                foreach ($redirects as $r) {

                    $id   = $r['id'];
                    $slug = $r['old'];
                    $slug = ltrim($slug, '/');
                    $slug = rtrim($slug, '/');

                    if ($slug === $oldUrl) {
                        return [
                            'id'     => $id,
                            'status' => TRUE
                        ];
                    }

                }
            }
            return [
                'id'     => '',
                'status' => FALSE
            ];
        }

        public static function createTable()
        {
            global $wpdb;
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $charset_collate = $wpdb->get_charset_collate();
            $creation_query  = 'CREATE TABLE xag_redirects (
			        `id` int(11) NOT NULL AUTO_INCREMENT,
			        `old` varchar(255),
			        `new` varchar(255),
			        `qry_str_url` int(11) NOT NULL DEFAULT 1,
			        `is_redirect_active` int(1) NOT NULL DEFAULT 1,
			        `date_created` datetime,
			        PRIMARY KEY  (`id`)
			    ) ' . $charset_collate . ';';
            @dbDelta($creation_query);
        }

        public static function removeTable()
        {
            global $wpdb;
            $wpdb->query('DROP TABLE IF EXISTS xag_redirects');
        }


    }

}
