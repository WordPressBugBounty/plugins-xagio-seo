<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_SHORTCODES')) {

    class XAGIO_MODEL_SHORTCODES
    {

        public static function scriptData()
        {
            $xagio_redirect_mask = get_option('XAGIO_REDIRECT_MASK');
            if (!$xagio_redirect_mask)
                $xagio_redirect_mask = 'xredirect';

            wp_localize_script('xagio_linkmanagement', 'xagio_linkmanagement', [
                'redirect_mask' => esc_attr($xagio_redirect_mask)
            ]);

        }

        public static function initialize()
        {
            add_action('admin_print_scripts', [
                'XAGIO_MODEL_SHORTCODES',
                'scriptData'
            ]);

            add_action('parse_request', [
                'XAGIO_MODEL_SHORTCODES',
                'maskedShortcode'
            ]);

            self::initShortcodes();

            add_action('admin_post_nopriv_xagio_trackShortcode', [
                'XAGIO_MODEL_SHORTCODES',
                'trackShortcode'
            ]);

            if (!XAGIO_HAS_ADMIN_PERMISSIONS)
                return;

            add_action('admin_post_xagio_saveShortcode', [
                'XAGIO_MODEL_SHORTCODES',
                'saveShortcode'
            ]);
            add_action('admin_post_xagio_loadShortcodes', [
                'XAGIO_MODEL_SHORTCODES',
                'loadShortcodes'
            ]);
            add_action('admin_post_xagio_getTrackingBoxes', [
                'XAGIO_MODEL_SHORTCODES',
                'getTrackingBoxes'
            ]);
            add_action('admin_post_xagio_getTrackingCharts', [
                'XAGIO_MODEL_SHORTCODES',
                'getTrackingCharts'
            ]);
            add_action('admin_post_xagio_getTrackingUrlCharts', [
                'XAGIO_MODEL_SHORTCODES',
                'getTrackingUrlCharts'
            ]);
            add_action('admin_post_xagio_truncateTrackingData', [
                'XAGIO_MODEL_SHORTCODES',
                'truncateTrackingData'
            ]);
            add_action('admin_post_xagio_urlTruncateTrackingData', [
                'XAGIO_MODEL_SHORTCODES',
                'urlTruncateTrackingData'
            ]);
            add_action('admin_post_xagio_duplicateShortcode', [
                'XAGIO_MODEL_SHORTCODES',
                'duplicateShortcode'
            ]);
            add_action('admin_post_xagio_getShortcode', [
                'XAGIO_MODEL_SHORTCODES',
                'getShortcode'
            ]);
            add_action('admin_post_xagio_deleteShortcode', [
                'XAGIO_MODEL_SHORTCODES',
                'deleteShortcode'
            ]);
            add_action('admin_post_xagio_exportLinks', [
                'XAGIO_MODEL_SHORTCODES',
                'exportLinks'
            ]);
            add_action('admin_post_xagio_importLinks', [
                'XAGIO_MODEL_SHORTCODES',
                'importLinks'
            ]);

            add_action('admin_post_xagio_save_shortcode_setup', [
                'XAGIO_MODEL_SHORTCODES',
                'saveShortcodeSetup'
            ]);

            add_action('admin_post_xagio_trackShortcode', [
                'XAGIO_MODEL_SHORTCODES',
                'trackShortcode'
            ]);
        }

        public static function saveShortcodeSetup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (isset($_POST['redirect_mask'])) {
                if (!empty($_POST['redirect_mask'])) {
                    $xagio_redirect_mask = sanitize_text_field(wp_unslash($_POST['redirect_mask']));
                    update_option('XAGIO_REDIRECT_MASK', $xagio_redirect_mask);

                    xagio_json('success', 'Successfully updated masked URL!');
                } else {
                    xagio_json('error', 'Redirect Mask cannot be empty!');
                }
            } else {
                xagio_json('error', 'Redirect Mask must be sent!');
            }
        }

        public static function deleteShortcode()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (isset($_POST['id'])) {
                $ID = intval($_POST['id']);

                // Execute the query and retrieve the results
                $shortcode = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM xag_shortcodes WHERE id = %d", $ID
                    ), ARRAY_A
                );

                if ($shortcode !== FALSE) {

                    $wpdb->delete('xag_shortcodes', ['id' => $ID]);
                    $wpdb->delete('xag_shortcodes_tracking', ['shortcode_id' => $ID]);

                    xagio_json('success', 'Successfully deleted shortcode!');

                } else {
                    xagio_json('error', 'No such shortcode exists!');
                }
            } else {
                xagio_json('error', 'ID must be sent!');
            }
        }

        public static function getShortcode()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (isset($_POST['id'])) {
                $ID        = intval($_POST['id']);
                $shortcode = $wpdb->get_row($wpdb->prepare("SELECT * FROM xag_shortcodes WHERE id = %d", $ID), ARRAY_A);
                if ($shortcode !== FALSE) {

                    xagio_json('success', 'Shortcode successfully retrieved!', $shortcode);

                } else {
                    xagio_json('error', 'No such shortcode exists!');
                }
            } else {
                xagio_json('error', 'ID must be sent!');
            }
        }

        public static function duplicateShortcode()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (isset($_POST['id'])) {
                $ID        = intval($_POST['id']);
                $shortcode = $wpdb->get_row($wpdb->prepare("SELECT * FROM xag_shortcodes WHERE id = %d", $ID), ARRAY_A);
                if ($shortcode !== FALSE) {

                    $newName = $shortcode['shortcode'];
                    if (substr($newName, -1) != '_') {
                        $newName .= '_';
                    }

                    $counter = 0;

                    $counter++;

                    $xagio_name   = $newName . $counter;
                    $exists = $wpdb->get_row($wpdb->prepare('SELECT id FROM xag_shortcodes WHERE shortcode = %s', $xagio_name), ARRAY_A);

                    if ($exists == FALSE) {
                        $shortcode['shortcode'] = $xagio_name;
                        $shortcode['name']      = $shortcode['name'] . 'Copy';
                        $shortcode['title']     = $shortcode['title'] . 'Copy';
                        unset($shortcode['id']);
                        $wpdb->insert('xag_shortcodes', $shortcode);
                    }

                    xagio_json('success', 'Shortcode successfully duplicated!');

                } else {
                    xagio_json('error', 'No such shortcode exists!');
                }
            } else {
                xagio_json('error', 'ID must be sent!');
            }
        }


        public static function importLinks()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            $message = 'Skipped existing shortcode: ';
            $skipped = 0;

            if (isset($_FILES['file-import'])) {
                // Include the necessary WordPress file handling functions
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }

                // Handle the uploaded file
                $upload_overrides = array('test_form' => false);

                $uploaded_file = wp_handle_upload($_FILES['file-import'], $upload_overrides);

                if ($uploaded_file && !isset($uploaded_file['error'])) {
                    $csv_path = $uploaded_file['file'];

                    $file_contents = xagio_file_get_contents($csv_path);
                    wp_delete_file($csv_path);

                    $file_lines = explode("\n", $file_contents);

                    for ($xagio_i = 1; $xagio_i < count($file_lines); $xagio_i++) {
                        $current = trim($file_lines[$xagio_i]);

                        if (empty($current)) {
                            continue;
                        }

                        if ($current === 'XagioMask') {
                            update_option('XAGIO_REDIRECT_MASK', trim(@$file_lines[$xagio_i + 1]));
                            break;
                        }

                        $x = preg_split('/(?<!\s),(?!\s)/', $current);
                        $y = array_map(function ($xagio_value) {
                            return str_replace(',', '', $xagio_value);
                        }, $x);

                        list($xagio_name, $shortcode, $xagio_url, $title, $xagio_group, $target_blank, $nofollow, $mask, $image) = array_map('esc_sql', array_map('str_replace', array_fill(0, 9, '"'), array_fill(0, 9, ''), $y));

                        $shortcode_exists = $wpdb->query($wpdb->prepare("SELECT shortcode FROM xag_shortcodes WHERE shortcode = %s", $shortcode));
                        if (isset($shortcode_exists['shortcode'])) {
                            $skipped++;
                            $message .= $shortcode_exists['shortcode'] . ', ';
                            continue;
                        }

                        $data = [
                            'name'         => $xagio_name,
                            'shortcode'    => $shortcode,
                            'url'          => $xagio_url,
                            'title'        => $title,
                            'group'        => $xagio_group,
                            'target_blank' => $target_blank,
                            'nofollow'     => $nofollow,
                            'mask'         => $mask,
                            'image'        => $image
                        ];

                        $wpdb->insert('xag_shortcodes', $data);
                    }

                    $message = $skipped > 0 ? rtrim($message, ', ') : '';
                } else {
                    $message = 'Failed to upload the file: ' . $uploaded_file['error'];
                }
            }

            // Optionally, display the message if needed
            echo esc_html($message);
        }

        // Download to CSV
        public static function exportLinks()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // Check if `link_ids` is set and sanitize the input
            if (isset($_GET['link_ids'])) {
                $link_ids = sanitize_text_field(wp_unslash($_GET['link_ids']));
                $link_ids = array_map('absint', explode(',', $link_ids));
            } else {
                $link_ids = 'all';
            }

            // Call the export function with the sanitized link IDs
            self::exportLinksToCsv($link_ids);
        }


        public static function exportLinksToCsv($link_ids)
        {
            $xagio_redirect_mask = get_option('XAGIO_REDIRECT_MASK');

            global $wpdb;

            if ($link_ids === 'all') {
                $links = $wpdb->get_results("SELECT * FROM xag_shortcodes", ARRAY_A);
            } else {
                // Ensure that each ID is an integer to prevent SQL injection
                $link_ids     = array_map('absint', $link_ids);
                $xagio_placeholders = implode(',', array_fill(0, count($link_ids), '%d'));
                $links        = $wpdb->get_results($wpdb->prepare("SELECT * FROM xag_shortcodes WHERE id IN ($xagio_placeholders)", ...$link_ids));
            }

            $projectName = 'Link Manager Export - ' . gmdate('H:i:s');

            $xagio_output = '';
            $xagio_output .= 'Name,Shortcode,URL,Title,Group,Target Blank, NoFollow,Mask, Image';
            $xagio_output .= "\n";
            foreach ($links as $link) {
                $xagio_output .= '"' . $link['name'] . '","' . $link['shortcode'] . '","' . $link['url'] . '","' . $link['title'] . '","' . $link['group'] . '",' . $link['target_blank'] . '",' . $link['nofollow'] . '",' . $link['mask'] . '",' . $link['image'] . '",';
                $xagio_output .= "\n";
            }
            $xagio_output   .= "XagioMask";
            $xagio_output   .= "\n";
            $xagio_output   .= $xagio_redirect_mask;
            $filename = $projectName . ".csv";
            header('Content-type: application/csv');
            header('Content-Disposition: attachment; filename=' . $filename);

            echo wp_kses_post($xagio_output);
            exit;
        }

        public static function truncateTrackingData()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (isset($_POST['id'])) {
                $ID = intval($_POST['id']);
                $wpdb->delete('xag_shortcodes_tracking', ['shortcode_id' => $ID]);
                xagio_json('success', 'Truncated tracking data successfully!');
            } else {
                xagio_json('error', 'ID must be sent!');
            }
        }

        public static function urlTruncateTrackingData()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (isset($_POST['id'])) {
                $ID = intval($_POST['id']);
                $wpdb->delete('xag_shortcodes_url_tracking', ['shortcode_id' => $ID]);
                xagio_json('success', 'Truncated tracking data successfully!');
            } else {
                xagio_json('error', 'ID must be sent!');
            }
        }

        public static function getTrackingCharts()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (isset($_POST['id'])) {
                $ID = intval($_POST['id']);

                // Impressions
                $impressions = $wpdb->get_results(
                    $wpdb->prepare("SELECT CAST(date AS DATE) AS DATE_TRACKED, COUNT(*) AS TOTAL FROM xag_shortcodes_tracking WHERE shortcode_id = %d GROUP BY DATE_TRACKED", $ID), ARRAY_A
                );

                // Unique Clicks
                $unique_clicks = $wpdb->get_results(
                    $wpdb->prepare("SELECT CAST(date AS DATE) AS DATE_TRACKED, COUNT(DISTINCT ip_address) AS TOTAL FROM xag_shortcodes_tracking WHERE shortcode_id = %d AND clicked = 1 GROUP BY DATE_TRACKED", $ID), ARRAY_A
                );

                // Chart Data
                $chart_data = [];

                foreach ($impressions as $d) {
                    $chart_data[$d['DATE_TRACKED']]['IMPRESSIONS']   = (int)$d['TOTAL'];
                    $chart_data[$d['DATE_TRACKED']]['UNIQUE_CLICKS'] = 0;
                }

                foreach ($unique_clicks as $d) {
                    $chart_data[$d['DATE_TRACKED']]['UNIQUE_CLICKS'] = (int)$d['TOTAL'];
                }

                $final_chart_data = [
                    [
                        'Date',
                        'Impressions',
                        'Unique Clicks'
                    ]
                ];
                foreach ($chart_data as $xagio_date => $data) {
                    $final_chart_data[] = [
                        $xagio_date,
                        $data['IMPRESSIONS'],
                        $data['UNIQUE_CLICKS'],
                    ];
                }

                xagio_json('success', 'Retrieved tracking charts data successfully!', $final_chart_data);

            } else {
                xagio_json('error', 'ID must be sent!');
            }
        }

        public static function getTrackingUrlCharts()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            if (isset($_POST['id'])) {
                $ID = intval($_POST['id']);

                // Impressions
                $impressions = $wpdb->get_results(
                    $wpdb->prepare("SELECT CAST(date AS DATE) AS DATE_TRACKED, COUNT(*) AS TOTAL FROM xag_shortcodes_url_tracking WHERE shortcode_id = %d GROUP BY DATE_TRACKED", $ID), ARRAY_A
                );

                // Unique Clicks
                $unique_clicks = $wpdb->get_results(
                    $wpdb->prepare("SELECT CAST(date AS DATE) AS DATE_TRACKED, COUNT(DISTINCT ip_address) AS TOTAL FROM xag_shortcodes_url_tracking WHERE shortcode_id = %d AND clicked = 1 GROUP BY DATE_TRACKED", $ID), ARRAY_A
                );

                // Chart Data
                $chart_data = [];

                foreach ($impressions as $d) {
                    $chart_data[$d['DATE_TRACKED']]['IMPRESSIONS']   = (int)$d['TOTAL'];
                    $chart_data[$d['DATE_TRACKED']]['UNIQUE_CLICKS'] = 0;
                }

                foreach ($unique_clicks as $d) {
                    $chart_data[$d['DATE_TRACKED']]['UNIQUE_CLICKS'] = (int)$d['TOTAL'];
                }

                $final_chart_data = [
                    [
                        'Date',
                        'Impressions',
                        'Unique Clicks'
                    ]
                ];
                foreach ($chart_data as $xagio_date => $data) {
                    $final_chart_data[] = [
                        $xagio_date,
                        $data['IMPRESSIONS'],
                        $data['UNIQUE_CLICKS'],
                    ];
                }

                xagio_json('success', 'Retrieved tracking charts data successfully!', $final_chart_data);

            } else {
                xagio_json('error', 'ID must be sent!');
            }
        }

        public static function maskedShortcode()
        {
            global $wpdb;

            if (!isset($_SERVER['REMOTE_ADDR'])) {
                return;
            }

            $u             = get_site_url();
            $xagio_redirect_mask = get_option('XAGIO_REDIRECT_MASK');
            $xagio_date          = gmdate('Y-m-d');
            if (!$xagio_redirect_mask)
                $xagio_redirect_mask = 'xredirect';

            if (isset($_GET[$xagio_redirect_mask])) {
                if (!empty($_GET[$xagio_redirect_mask])) {

                    $ID = sanitize_text_field(wp_unslash($_GET[$xagio_redirect_mask]));
                    if (is_numeric($ID)) {
                        $shortcode = $wpdb->get_row($wpdb->prepare("SELECT * FROM xag_shortcodes WHERE id = %d", $ID), ARRAY_A);
                    } else {
                        $shortcode = $wpdb->get_row($wpdb->prepare("SELECT * FROM xag_shortcodes WHERE name = %s", $ID), ARRAY_A);
                    }

                    if (!empty($shortcode)) {

                        $shortcode_tracking = $wpdb->get_row(
                            $wpdb->prepare(
                                "SELECT * FROM xag_shortcodes_url_tracking WHERE shortcode_id = %d AND ip_address = %s", $shortcode['id'], sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
                            ), ARRAY_A
                        );

                        if ($shortcode_tracking !== FALSE && $shortcode_tracking !== NULL) {
                            $wpdb->update('xag_shortcodes_url_tracking', ['clicked' => 1], [
                                'shortcode_id' => $shortcode['id'],
                                'ip_address'   => sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
                            ]);


                        } else {
                            $wpdb->insert('xag_shortcodes_url_tracking', [
                                'ip_address'   => sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])),
                                'shortcode_id' => $shortcode['id'],
                                'date'         => $xagio_date,
                                'clicked'      => 1,
                            ]);
                            sleep(0.2);
                        }

                        xagio_redirect($shortcode['url']);
                        exit;
                    } else {
                        xagio_redirect($u);
                        exit;
                    }
                } else {
                    xagio_redirect($u);
                    exit;
                }
            } else if (isset($_GET['xredirect'])) {
                if (!empty($_GET['xredirect'])) {

                    $ID = sanitize_text_field(wp_unslash($_GET['xredirect']));
                    if (is_numeric($ID)) {
                        $shortcode = $wpdb->get_row($wpdb->prepare("SELECT * FROM xag_shortcodes WHERE id = %d", $ID), ARRAY_A);
                    } else {
                        $shortcode = $wpdb->get_row($wpdb->prepare("SELECT * FROM xag_shortcodes WHERE name = %s", $ID), ARRAY_A);
                    }

                    if (!empty($shortcode)) {

                        $shortcode_tracking = $wpdb->get_row(
                            $wpdb->prepare(
                                "SELECT * FROM xag_shortcodes_url_tracking WHERE date = %s AND shortcode_id = %d AND ip_address = %s", $xagio_date, $shortcode['id'], sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
                            ), ARRAY_A
                        );

                        if ($shortcode_tracking !== FALSE && $shortcode_tracking !== NULL) {
                            $wpdb->update('xag_shortcodes_url_tracking', ['clicked' => 1], [
                                'date'         => $xagio_date,
                                'shortcode_id' => $shortcode['id'],
                                'ip_address'   => sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
                            ]);
                        } else {
                            $wpdb->insert('xag_shortcodes_url_tracking', [
                                'ip_address'   => sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])),
                                'shortcode_id' => $shortcode['id'],
                                'date'         => $xagio_date,
                                'clicked'      => 1,
                            ]);
                            sleep(0.2);
                        }

                        xagio_redirect($shortcode['url']);
                        exit;
                    } else {
                        xagio_redirect($u);
                        exit;
                    }
                } else {
                    xagio_redirect($u);
                    exit;
                }
            }
        }

        public static function trackShortcode()
        {
            global $wpdb;

            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_SERVER['REMOTE_ADDR'])) {
                return;
            }

            // Initialize ID and retrieve redirect mask option
            $ID            = 0;
            $xagio_redirect_mask = sanitize_text_field(get_option('XAGIO_REDIRECT_MASK', 'xredirect'));

            // Determine the ID from the request
            if (isset($_POST['id'])) {
                $ID = sanitize_text_field(wp_unslash($_POST['id']));
            } elseif (isset($_REQUEST[$xagio_redirect_mask])) {
                $ID = sanitize_text_field(wp_unslash($_REQUEST[$xagio_redirect_mask]));
            } elseif (isset($_REQUEST['xredirect'])) {
                $ID = sanitize_text_field(wp_unslash($_REQUEST['xredirect']));
            }

            // Determine which table to use
            $table = isset($_REQUEST['masked']) ? 'xag_shortcodes_url_tracking' : 'xag_shortcodes_tracking';

            // Get the current date
            $xagio_date = gmdate('Y-m-d');

            // Fetch the shortcode data
            $shortcode = false;

            if (is_numeric($ID)) {
                $shortcode = $wpdb->get_row($wpdb->prepare("SELECT * FROM xag_shortcodes WHERE id = %d", absint($ID)), ARRAY_A);
            } else {
                $shortcode = $wpdb->get_row($wpdb->prepare("SELECT * FROM xag_shortcodes WHERE name = %s", sanitize_text_field($ID)), ARRAY_A);
            }

            if ($shortcode !== false) {

                // Check if the shortcode tracking already exists for today
                $shortcode_tracking = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $table WHERE `date` = %s AND shortcode_id = %d AND ip_address = %s", $xagio_date, $shortcode['id'], sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
                    ), ARRAY_A
                );

                // Update or insert the tracking record
                if ($shortcode_tracking != NULL) {
                    $wpdb->update($table, ['clicked' => 1], [
                        'date'         => $xagio_date,
                        'shortcode_id' => $shortcode['id'],
                        'ip_address'   => sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
                    ]);
                } else {
                    $wpdb->insert($table, [
                        'ip_address'   => sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])),
                        'shortcode_id' => $shortcode['id'],
                        'date'         => $xagio_date,
                        'clicked'      => 1,
                    ]);
                }
            }
        }


        public static function initShortcodes()
        {
            global $wpdb;

            $shortcodes = $wpdb->get_results('SELECT * FROM xag_shortcodes', ARRAY_A);
            foreach ($shortcodes as $s) {
                add_shortcode($s['shortcode'], [
                    'XAGIO_MODEL_SHORTCODES',
                    'renderShortcode'
                ]);
            }

            add_shortcode('xagio_project_keyword', [
                'XAGIO_MODEL_SHORTCODES',
                'renderShortcode'
            ]);

        }

        public static function renderShortcode($atts = [], $xagio_content = '', $tag = '')
        {
            if (!isset($_SERVER['REMOTE_ADDR'])) {
                return '';
            }

            global $wpdb;

            if ($tag === 'aff') {

                $shortcode = $wpdb->get_row($wpdb->prepare("SELECT * FROM xag_shortcodes WHERE shortcode = %s", str_replace(' ', '_', $atts['name'])), ARRAY_A);

            } else if ($tag === 'xagio_project_keyword') {
                // Render!
                $a = '<a class="prs-group-keyword" href="' . esc_url($atts['url']) . '"';

                if ($atts['target'] === 'true')
                    $a .= ' target="_blank"';

                $a .= '>';

                if ($atts['capitalize'] === 'true')
                    $atts['keyword'] = ucfirst(strtolower($atts['keyword']));

                $a .= sanitize_text_field(wp_unslash($atts['keyword']));
                $a .= '</a>';
                return $a;
            } else {
                $shortcode = $wpdb->get_row($wpdb->prepare("SELECT * FROM xag_shortcodes WHERE shortcode = %s", $tag), ARRAY_A);
            }

            if ($shortcode != NULL) {
                // if multiple shortcodes
                if (!isset($shortcode['shortcode'])) {
                    $shortcode = $shortcode[0];
                }

                if (empty($atts))
                    $atts = ['mask' => 0];

                // Track!
                $ip_address   = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
                $shortcode_id = $shortcode['id'];
                $xagio_date         = gmdate('Y-m-d');
                $isMasked     = '';
                // Get tracking ID if visited today, if not, create
                $tracking_id = NULL;

                $xagio_redirect_mask = get_option('XAGIO_REDIRECT_MASK');
                if (!$xagio_redirect_mask)
                    $xagio_redirect_mask = 'xredirect';

                // Check if URL needs to be masked
                if ($shortcode['mask'] == 1 || $atts['mask'] == 1) {
                    $isMasked         = 'masked';
                    $shortcode['url'] = '/?' . $xagio_redirect_mask . '=' . $shortcode_id;

                    $tracking = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT * FROM xag_shortcodes_url_tracking WHERE ip_address = %s AND date = %s AND shortcode_id = %d", $ip_address, $xagio_date, $shortcode_id
                        ), ARRAY_A
                    );

                    if ($tracking != NULL) {
                        $tracking_id = $tracking['id'];
                    } else {
                        $wpdb->insert('xag_shortcodes_url_tracking', [
                            'ip_address'   => $ip_address,
                            'shortcode_id' => $shortcode_id,
                            'date'         => $xagio_date,
                        ]);
                        $tracking_id = $wpdb->insert_id;
                    }

                } else {

                    $tracking = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT * FROM xag_shortcodes_tracking WHERE ip_address = %s AND date = %s AND shortcode_id = %d", $ip_address, $xagio_date, $shortcode_id
                        ), ARRAY_A
                    );


                    if ($tracking != NULL) {
                        $tracking_id = $tracking['id'];
                    } else {
                        $wpdb->insert('xag_shortcodes_tracking', [
                            'ip_address'   => $ip_address,
                            'shortcode_id' => $shortcode_id,
                            'date'         => $xagio_date,
                        ]);
                        $tracking_id = $wpdb->insert_id;
                    }

                }

                if (isset($atts['title'])) {
                    $shortcode['title'] = $atts['title'];
                }

                // Render!
                $a = '<a class="xagio-tracking ' . $isMasked . '" data-id="' . $shortcode_id . '" href="' . $shortcode['url'] . '"';
                if ($shortcode['target_blank'] == 1) {
                    $a .= ' target="_blank"';
                }
                if ($shortcode['nofollow'] == 1) {
                    $a .= ' rel="nofollow"';
                }
                $a .= '>';

                if (empty($shortcode['image'])) {
                    $a .= $shortcode['title'];
                } else {
                    $a .= '<img src="' . $shortcode['image'] . '" title="' . $shortcode['title'] . '"/>';
                }
                $a .= '</a>';
                return $a;
            }
            return '';
        }

        public static function loadShortcodes() {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            $page   = isset($_POST['page']) ? max(0, intval($_POST['page'])) : 0;
            $length = isset($_POST['total_entries']) ? intval($_POST['total_entries']) : 100;

            if ($length <= 0) {
                $length = 100;
            }

            $xagio_group  = isset($_POST['group']) ? sanitize_text_field(wp_unslash($_POST['group'])) : 'all';
            $shortcode    = isset($_POST['shortcode']) ? sanitize_text_field(wp_unslash($_POST['shortcode'])) : '';
            $title        = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
            $xagio_url    = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';

            // Calculate offset
            $offset = $page * $length;

            // "all" means no filter
            if ($xagio_group === 'all') {
                $xagio_group = '';
            }

            // Build LIKE values (values only, NOT SQL)
            $shortcode_like = ($shortcode !== '') ? ('%' . $wpdb->esc_like($shortcode) . '%') : '';
            $title_like     = ($title !== '') ? ('%' . $wpdb->esc_like($title) . '%') : '';
            $url_like       = ($xagio_url !== '') ? ('%' . $wpdb->esc_like($xagio_url) . '%') : '';

            // Filter flags + values (THIS is what your variables are for)
            $filter_group     = ($xagio_group !== '') ? 1 : 0;
            $filter_shortcode = ($shortcode_like !== '') ? 1 : 0;
            $filter_title     = ($title_like !== '') ? 1 : 0;
            $filter_url       = ($url_like !== '') ? 1 : 0;

            $target_blank_raw = isset($_POST['target_blank']) ? (int) wp_unslash($_POST['target_blank']) : 0;
            $mask_raw         = isset($_POST['mask']) ? (int) wp_unslash($_POST['mask']) : 0;
            $nofollow_raw     = isset($_POST['nofollow']) ? (int) wp_unslash($_POST['nofollow']) : 0;

            $filter_target_blank = ($target_blank_raw === 1) ? 1 : 0;
            $filter_mask         = ($mask_raw === 1) ? 1 : 0;
            $filter_nofollow     = ($nofollow_raw === 1) ? 1 : 0;

            $target_blank = 1;
            $mask         = 1;
            $nofollow     = 1;

            // ---- COUNT (no dynamic WHERE clause, only placeholders) ----
            $total_items = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*)
             FROM xag_shortcodes
             WHERE (%d = 0 OR `group` = %s)
               AND (%d = 0 OR shortcode LIKE %s)
               AND (%d = 0 OR title LIKE %s)
               AND (%d = 0 OR url LIKE %s)
               AND (%d = 0 OR target_blank = %d)
               AND (%d = 0 OR mask = %d)
               AND (%d = 0 OR nofollow = %d)",
                    $filter_group, $xagio_group,
                    $filter_shortcode, $shortcode_like,
                    $filter_title, $title_like,
                    $filter_url, $url_like,
                    $filter_target_blank, $target_blank,
                    $filter_mask, $mask,
                    $filter_nofollow, $nofollow
                )
            );

            // ---- RESULTS (same filters + LIMIT) ----
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT *
             FROM xag_shortcodes
             WHERE (%d = 0 OR `group` = %s)
               AND (%d = 0 OR shortcode LIKE %s)
               AND (%d = 0 OR title LIKE %s)
               AND (%d = 0 OR url LIKE %s)
               AND (%d = 0 OR target_blank = %d)
               AND (%d = 0 OR mask = %d)
               AND (%d = 0 OR nofollow = %d)
             ORDER BY id DESC
             LIMIT %d, %d",
                    $filter_group, $xagio_group,
                    $filter_shortcode, $shortcode_like,
                    $filter_title, $title_like,
                    $filter_url, $url_like,
                    $filter_target_blank, $target_blank,
                    $filter_mask, $mask,
                    $filter_nofollow, $nofollow,
                    absint($offset),
                    absint($length)
                ),
                ARRAY_A
            );

            $pages = ($length > 0) ? (int) ceil($total_items / $length) : 1;

            if (isset($results['id'])) {
                $results = [$results];
            } elseif (empty($results)) {
                $results = [];
            }

            foreach ($results as &$xagio_result) {
                if (!isset($xagio_result['id'])) {
                    continue;
                }

                $ID = absint($xagio_result['id']);

                $impressions_data = $wpdb->get_results(
                    $wpdb->prepare("SELECT id FROM xag_shortcodes_tracking WHERE shortcode_id = %d", $ID),
                    ARRAY_A
                );
                $impressions = is_array($impressions_data) ? count($impressions_data) : 0;

                $unique_clicks_data = $wpdb->get_results(
                    $wpdb->prepare("SELECT id, ip_address FROM xag_shortcodes_tracking WHERE shortcode_id = %d AND clicked = %d", $ID, 1),
                    ARRAY_A
                );
                $unique_clicks = is_array($unique_clicks_data) ? count($unique_clicks_data) : 0;

                $ctr = ($impressions > 0 && $unique_clicks > 0) ? (float) ($unique_clicks / $impressions) * 100 : 0;

                $url_impressions_data = $wpdb->get_results(
                    $wpdb->prepare("SELECT id FROM xag_shortcodes_url_tracking WHERE shortcode_id = %d", $ID),
                    ARRAY_A
                );
                $url_impressions = is_array($url_impressions_data) ? count($url_impressions_data) : 0;

                $url_unique_clicks_data = $wpdb->get_results(
                    $wpdb->prepare("SELECT id, ip_address FROM xag_shortcodes_url_tracking WHERE shortcode_id = %d AND clicked = %d", $ID, 1),
                    ARRAY_A
                );
                $url_unique_clicks = is_array($url_unique_clicks_data) ? count($url_unique_clicks_data) : 0;

                $url_ctr = ($url_impressions > 0 && $url_unique_clicks > 0) ? (float) ($url_unique_clicks / $url_impressions) * 100 : 0;

                $xagio_result['impressions']       = $impressions;
                $xagio_result['unique_clicks']     = $unique_clicks;
                $xagio_result['ctr']               = $ctr;
                $xagio_result['url_impressions']   = $url_impressions;
                $xagio_result['url_unique_clicks'] = $url_unique_clicks;
                $xagio_result['url_ctr']           = $url_ctr;
            }
            unset($xagio_result);

            $mask_option = sanitize_text_field(get_option('XAGIO_REDIRECT_MASK', ''));

            wp_send_json_success([
                'rows'  => $results,
                'mask'  => $mask_option,
                'pages' => $pages,
                'total' => $total_items,
            ]);
        }



        public static function saveShortcode()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;

            // Sanitize and validate the ID and other inputs
            $id           = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $xagio_group        = isset($_POST['group']) ? sanitize_text_field(wp_unslash($_POST['group'])) : '';
            $title        = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
            $shortcode    = isset($_POST['shortcode']) ? sanitize_text_field(wp_unslash($_POST['shortcode'])) : '';
            $xagio_url          = isset($_POST['url']) ? sanitize_url(wp_unslash($_POST['url'])) : '';
            $target_blank = isset($_POST['target_blank']) ? intval($_POST['target_blank']) : 0;
            $nofollow     = isset($_POST['nofollow']) ? intval($_POST['nofollow']) : 0;
            $mask         = isset($_POST['mask']) ? intval($_POST['mask']) : 0;
            $xagio_name         = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
            $image        = isset($_POST['image']) ? sanitize_url(wp_unslash($_POST['image'])) : '';

            // Prepare data for insertion or update
            $data = [
                'group'         => $xagio_group,
                'title'         => $title,
                'shortcode'     => $shortcode,
                'url'           => $xagio_url,
                'target_blank'  => $target_blank,
                'nofollow'      => $nofollow,
                'mask'          => $mask,
                'name'          => $xagio_name,
                'image'         => $image
            ];

            // Determine whether to insert or update
            if ($id === 0) {
                $wpdb->insert('xag_shortcodes', $data);
                $xagio_result = $wpdb->insert_id;
            } else {
                $xagio_result = $wpdb->update('xag_shortcodes', $data, ['id' => $id]);
            }

            // Handle the result and send a JSON response
            if (is_wp_error($xagio_result)) {
                xagio_json('error', $xagio_result->get_error_message());
            } else {
                xagio_json('success', 'Shortcode successfully saved!', $xagio_result);
            }
        }


        public static function createTable()
        {
            global $wpdb;
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $charset_collate = $wpdb->get_charset_collate();
            $creation_query  = 'CREATE TABLE xag_shortcodes (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`name` varchar(255),
					`shortcode` varchar(255),
					`url` text,
					`title` varchar(255),
					`group` varchar(255),				
					`target_blank` int(1) DEFAULT 0,
					`nofollow` int(1) DEFAULT 0,
					`mask` int(1) DEFAULT 0,
					`image` text,										
				PRIMARY KEY  (`id`)
			) ' . $charset_collate . ';';

            @dbDelta($creation_query);

            $creation_query = 'CREATE TABLE xag_shortcodes_tracking (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`shortcode_id` int(11),
					`clicked` int(1) DEFAULT 0,
					`ip_address` varchar(255),										
					`date` date,										
				PRIMARY KEY  (`id`)
			) ' . $charset_collate . ';';

            @dbDelta($creation_query);

            $creation_query = 'CREATE TABLE xag_shortcodes_url_tracking (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`shortcode_id` int(11),
					`clicked` int(1) DEFAULT 0,
					`ip_address` varchar(255),										
					`date` date,										
				PRIMARY KEY  (`id`)
			) ' . $charset_collate . ';';

            @dbDelta($creation_query);
        }

        public static function removeTable()
        {
            global $wpdb;
            $wpdb->query('DROP TABLE IF EXISTS xag_shortcodes;');
            $wpdb->query('DROP TABLE IF EXISTS xag_shortcodes_tracking;');
            $wpdb->query('DROP TABLE IF EXISTS xag_shortcodes_url_tracking;');
        }

    }

}