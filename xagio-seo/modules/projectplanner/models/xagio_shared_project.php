<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_SHARED_PROJECT')) {

    class XAGIO_MODEL_SHARED_PROJECT
    {
        public static function initialize()
        {
            add_action('template_redirect', [
                'XAGIO_MODEL_SHARED_PROJECT',
                'loadSharedProject'
            ], -999);

            add_action('wp_enqueue_scripts', [
                'XAGIO_MODEL_SHARED_PROJECT',
                'conditionally_enqueue_shared_project_assets'
            ]);

        }

        public static function conditionally_enqueue_shared_project_assets()
        {
            if (self::is_shared_project_page()) {
                self::xagio_enqueue_shared_project_assets();
            }
        }

        public static function xagio_enqueue_shared_project_assets()
        {
            // Enqueue Styles
            // Register Fonts
            wp_enqueue_style('xagio-font-outfit', XAGIO_URL . 'assets/css/fonts/Outfit/outfit.css', [], '1.0');
            wp_enqueue_style('xagio-global', XAGIO_URL . 'assets/css/global.css');
            wp_enqueue_style('xagio-main', XAGIO_URL . 'assets/css/main.css');
            wp_enqueue_style('xagio-shared-project', XAGIO_URL . 'modules/projectplanner/shared_project.css');

            // Enqueue Scripts
            wp_enqueue_script('jquery');
            wp_enqueue_script('xagio-tablesorter', XAGIO_URL . 'assets/js/vendor/tablesorter.js', array('jquery'), null, true);
            wp_enqueue_script('xagio-shared-project', XAGIO_URL . 'modules/projectplanner/shared_project.js', array('jquery'), null, true);

            // Get the current URL
            // Logic to determine if we're on the shared project page
            if (!isset($_SERVER['HTTP_HOST'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }
            $domain = XAGIO_MODEL_BACKUPS::getName(site_url());

            global $wpdb;

            // If hash parameter is not set exit;
            if (!isset($_GET['hash'])) {
                header("HTTP/1.1 404 Not found");
                exit;
            }

            $hash = sanitize_text_field(wp_unslash($_GET['hash']));

            // If $hash value is not md5 exit;
            if (!preg_match('/^[a-f0-9]{32}$/', $hash)) {
                header("HTTP/1.1 404 Not found");
                exit;
            }

            $results = $wpdb->get_row($wpdb->prepare("SELECT * FROM `xag_projects` WHERE `shared` = %s", $hash), ARRAY_A);

            if (!isset($results)) {
                header("HTTP/1.1 404 Not found");
                exit;
            }

            $response = wp_remote_get(sprintf(XAGIO_PANEL_URL . "/api/info?license_email=%s&license_key=%s&type=%s", XAGIO_LICENSE_EMAIL, XAGIO_LICENSE_KEY, "shared_project"), [
                'user-agent'  => "Xagio - " . XAGIO_CURRENT_VERSION . " ($domain)",
                'timeout'     => 30,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => TRUE,
            ]);

            $user_details = [];
            if (!is_wp_error($response)) {
                if (isset($response['body'])) {
                    if(isset($response['response']['code']) && $response['response']['code'] === 200) {
                        $user_details = json_decode($response['body'], TRUE);
                        $user_details = $user_details['user_details'];
                    }
                }
            }

            $project_id   = $results['id'];
            $project_name = $results['project_name'];
            $groups       = self::getSharedGroups($project_id);

            wp_localize_script('xagio-shared-project', 'xagio_shared_data', [
                'groups'        => esc_html(base64_encode(wp_json_encode($groups))),
                'project_name'  => esc_html($project_name),
                'plugins_url'   => esc_url(XAGIO_URL),
                'user_details'  => $user_details
            ]);


        }

        public static function is_shared_project_page()
        {
            // Logic to determine if we're on the shared project page
            if (!isset($_SERVER['REQUEST_URI'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $url = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
            return preg_match("/\/shared-seo-report(.*)/", $url);
        }

        public static function loadSharedProject()
        {
            if (!isset($_SERVER['REQUEST_URI']) || !isset($_SERVER['HTTP_HOST'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // Get the current URL
            $url    = sanitize_url(wp_unslash($_SERVER['REQUEST_URI']));
            $domain = XAGIO_MODEL_BACKUPS::getName(site_url());

            if (preg_match("/\/shared-seo-report(.*)/", $url, $matches)) {
                // If hash parameter is not set exit;
                if (!isset($_GET['hash'])) {
                    header("HTTP/1.1 404 Not found");
                    exit;
                }

                $hash = sanitize_text_field(wp_unslash($_GET['hash']));

                // If $hash value is not md5 exit;
                if (!preg_match('/^[a-f0-9]{32}$/', $hash)) {
                    header("HTTP/1.1 404 Not found");
                    exit;
                }

                header("HTTP/1.1 202 Created");
                require_once(dirname(__FILE__) . '/../shared_project.php');
                exit;
            }


        }

        public static function getSharedGroups($project_id)
        {
            global $wpdb;

            // Prepare and execute the first query
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, project_id, group_name, title, url, description, h1, notes, external_domain 
        FROM `xag_groups` 
        WHERE `project_id` = %d", $project_id
                ), ARRAY_A
            );

            $outputArray = [];

            if (!empty($results)) {
                $group_ids = [];

                // Process the results
                foreach ($results as &$result) {
                    $result['h1']          = stripslashes($result['h1'] ?? "");
                    $result['title']       = stripslashes($result['title'] ?? "");
                    $result['description'] = stripslashes($result['description'] ?? "");

                    $group_ids[] = $result['id'];
                }

                // If there are group IDs, prepare and execute the second query
                if (!empty($group_ids)) {
                    // Create placeholders for the group IDs
                    $placeholders = implode(',', array_fill(0, count($group_ids), '%d'));

                    // Prepare the second query using the placeholders
                    $keywords = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM `xag_keywords` WHERE group_id IN ($placeholders) ORDER BY position ASC", ...$group_ids
                        ), ARRAY_A
                    );

                    $keywords_temp = [];
                    foreach ($keywords as $keyword) {
                        $keywords_temp[$keyword['group_id']][] = $keyword;
                    }

                    // Combine the keywords with the original results
                    foreach ($results as $resulte) {
                        $resulte['keywords'] = $keywords_temp[$resulte['id']] ?? [];
                        $outputArray[]       = $resulte;
                    }
                }
            }

            return $outputArray;
        }


    }
}