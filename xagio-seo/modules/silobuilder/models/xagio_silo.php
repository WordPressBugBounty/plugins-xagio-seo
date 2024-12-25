<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_SILO')) {

    class XAGIO_MODEL_SILO
    {

        public static function initialize()
        {
            if (!XAGIO_HAS_ADMIN_PERMISSIONS) return;

            add_action('admin_post_xagio_add_new_page_post', [
                'XAGIO_MODEL_SILO',
                'addNewPagePost'
            ]);
            add_action('admin_post_xagio_delete_page', [
                'XAGIO_MODEL_SILO',
                'deletePage'
            ]);
            add_action('admin_post_xagio_update_operator_data', [
                'XAGIO_MODEL_SILO',
                'updateOperatorData'
            ]);
            add_action('admin_post_xagio_get_operator_data', [
                'XAGIO_MODEL_SILO',
                'getOperatorData'
            ]);
            add_action('admin_post_xagio_set_page_post_title', [
                'XAGIO_MODEL_SILO',
                'setPagePostTitle'
            ]);
            add_action('admin_post_xagio_new_category', [
                'XAGIO_MODEL_SILO',
                'newCategory'
            ]);
            add_action('admin_post_xagio_new_tag', [
                'XAGIO_MODEL_SILO',
                'newTag'
            ]);
            add_action('admin_post_xagio_delete_tag', [
                'XAGIO_MODEL_SILO',
                'deleteTag'
            ]);
            add_action('admin_post_xagio_delete_category', [
                'XAGIO_MODEL_SILO',
                'deleteCategory'
            ]);

            add_action('admin_post_xagio_save_silo', [
                'XAGIO_MODEL_SILO',
                'saveSilo'
            ]);
            add_action('admin_post_xagio_load_silo', [
                'XAGIO_MODEL_SILO',
                'loadSilo'
            ]);
            add_action('admin_post_xagio_load_silo_names', [
                'XAGIO_MODEL_SILO',
                'loadSiloNames'
            ]);
            add_action('admin_post_xagio_new_silo', [
                'XAGIO_MODEL_SILO',
                'newSILO'
            ]);
            add_action('admin_post_xagio_silo_remove_name', [
                'XAGIO_MODEL_SILO',
                'removeSiloName'
            ]);
            add_action('admin_post_xagio_reset_parents_cats_tags', [
                'XAGIO_MODEL_SILO',
                'resetParentsCategoriesTags'
            ]);

            add_action('admin_post_xagio_generate_silo_links', [
                'XAGIO_MODEL_SILO',
                'generateSiloLinks'
            ]);
            add_action('admin_post_xagio_generate_silo_links_by_id', [
                'XAGIO_MODEL_SILO',
                'generateSiloLinksByID'
            ]);
            add_action('admin_post_xagio_generate_silo', [
                'XAGIO_MODEL_SILO',
                'generateSilo'
            ]);

        }

        private static function _generateGrid($size = 5000, $x_spacing = 280, $y_spacing = 280, $padding = 20)
        {
            $positions_grid = [];
            $columns        = $size / $x_spacing;
            $rows           = $size / $y_spacing;

            for ($i = 0; $i < $rows; $i++) {

                $row = [];

                for ($ii = 0; $ii < $columns; $ii++) {

                    $x = $ii * $x_spacing;
                    $y = $i * $y_spacing;

                    if ($x == 0) {
                        $x = $padding;
                    }
                    if ($y == 0) {
                        $y = $padding;
                    }

                    $row[] = [
                        'x' => $x,
                        'y' => $y,
                        'f' => TRUE,
                    ];

                }

                $positions_grid[] = $row;

            }

            return $positions_grid;
        }

        private static function _createGridSlot_Posts(&$grid, $offset = FALSE, $id = '')
        {
            if ($offset == FALSE) {
                $offset = [
                    'r' => 0,
                    'c' => 0,
                ];
            }

            // Loop through rows
            for ($r = $offset['r']; $r < sizeof($grid); $r++) {

                // Loop through columns
                for ($c = $offset['c']; $c < sizeof($grid[$r]); $c++) {

                    if ($grid[$r][$c]['f'] == TRUE) {
                        $grid[$r][$c]['f'] = FALSE;
                        $grid[$r][$c]['c'] = $c;
                        $grid[$r][$c]['r'] = $r;
                        $grid[$r][$c]['i'] = $id;
                        return $grid[$r][$c];
                    }

                }

            }

            return FALSE;
        }

        private static function _createGridSlot(&$grid, $parented_by = FALSE, $id = '', $vertical = FALSE)
        {
            if ($parented_by != FALSE) {

                if ($vertical == FALSE) {

                    $r = $parented_by['r'] + 1;

                    if ($r > sizeof($grid)) {
                        $r = sizeof($grid);
                    }

                    // Loop through columns
                    for ($c = 0; $c < sizeof($grid[$r]); $c++) {

                        if ($grid[$r][$c]['f'] == TRUE) {
                            $grid[$r][$c]['f'] = FALSE;
                            $grid[$r][$c]['c'] = $c;
                            $grid[$r][$c]['r'] = $r;
                            $grid[$r][$c]['i'] = $id;
                            return $grid[$r][$c];
                        }

                    }

                    $r++;

                } else {

                    $r = $parented_by['r'] + 1;
                    $c = $parented_by['c'];

                    if ($r > sizeof($grid)) {
                        $r = $parented_by['r'];
                        $c = $c + 1;
                    }

                    // Loop through columns
                    for ($rr = $r; $rr < sizeof($grid); $rr++) {

                        if ($grid[$rr][$c]['f'] == TRUE) {
                            $grid[$rr][$c]['f'] = FALSE;
                            $grid[$rr][$c]['c'] = $c;
                            $grid[$rr][$c]['r'] = $r;
                            $grid[$rr][$c]['i'] = $id;
                            return $grid[$rr][$c];
                        }

                    }

                    $c++;
                }

            } else {

                // Loop through rows
                for ($r = 0; $r < sizeof($grid); $r++) {

                    // Loop through columns
                    for ($c = 0; $c < sizeof($grid[$r]); $c++) {

                        if ($grid[$r][$c]['f'] == TRUE) {
                            $grid[$r][$c]['f'] = FALSE;
                            $grid[$r][$c]['c'] = $c;
                            $grid[$r][$c]['r'] = $r;
                            $grid[$r][$c]['i'] = $id;
                            return $grid[$r][$c];
                        }

                    }

                }

            }
            return FALSE;
        }

        private static function _findGridSlot(&$grid, $id)
        {
            // Loop through rows
            for ($r = 0; $r < sizeof($grid); $r++) {

                // Loop through columns
                for ($c = 0; $c < sizeof($grid[$r]); $c++) {
                    if (isset($grid[$r][$c]['i'])) {
                        if ($grid[$r][$c]['i'] == $id) {
                            return $grid[$r][$c];
                        }
                    }
                }

            }
            return FALSE;
        }

        public static function generateSiloLinksByID()
        {
            sleep(1);

            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $postID = intval($_POST['id']);
            $links  = [];

            $grid = self::_generateGrid();

            $silo = [
                'operators' => [],
                'links'     => [],
                'settings'  => [
                    "canvas_size" => "10000",

                    "external_color"          => "#ff3c3c",
                    "external_line_color"     => "#ff3c3c",
                    "external_line_thickness" => "2",
                    "external_line_type"      => "15",

                    "internal_line_color"     => "#6ec2ff",
                    "internal_line_thickness" => "2",
                    "internal_line_type"      => "15",
                ],
                'grid'      => NULL,
            ];

            $args = [
                'posts_per_page' => -1,
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'post_type'      => [
                    'post',
                    'page',
                ],
                'post_status'    => [
                    'publish',
                ],
            ];

            $post = get_post($postID);

            $content_post = get_post($post->ID);
            $content      = $content_post->post_content;
            $content      = apply_filters('the_content', $content);
            $content      = html_entity_decode($content);

            preg_match_all(
                '/<a.*?href="([^"]+)".*?>(.*?)<\/a>/s', stripslashes(do_shortcode($content)), $matches, PREG_SET_ORDER
            );

            // Find the parent ID
            $parent_id = self::_findOperator(
                $silo, $post->post_type, $post->ID
            );

            $parent_slot = FALSE;

            // Create a parent page if not exists
            if ($parent_id === NULL) {

                $parent_slot = self::_createGridSlot(
                    $grid, FALSE, $post->post_type . '-' . $post->ID
                );

                $silo['operators'][] = [
                    'properties' => [
                        'title'     => $post->post_title,
                        'subtitle'  => '',
                        'attached'  => XAGIO_MODEL_PROJECTS::isAttachedToGroup($post->ID),
                        'icon'      => 'xagio-icon xagio-icon-file',
                        'permalink' => get_permalink($post->ID),
                        'inputs'    => [
                            'ins' => [
                                'label'    => '',
                                'multiple' => TRUE,
                            ],
                        ],
                        'outputs'   => [
                            'outs' => [
                                'label'    => '',
                                'multiple' => TRUE,
                            ],
                        ],
                        'class'     => 'operator-page op-' . $post->post_type . '-' . $post->ID,
                        'ID'        => 'op-' . $post->post_type . '-' . $post->ID,
                        'realID'    => $post->ID,
                        'type'      => $post->post_type,
                    ],
                    'left'       => $parent_slot['x'],
                    'top'        => $parent_slot['y'],
                ];
                $parent_id           = sizeof($silo['operators']) - 1;

            } else {
                $parent_slot = self::_findGridSlot(
                    $grid, $post->post_type . '-' . $post->ID
                );
            }

            if (!empty($matches)) {
                foreach ($matches as $url_data) {

                    $url = $url_data[1];

                    $post_id = url_to_postid($url);

                    $post_child = get_post($post_id);

                    if ($post_id == 0) {

                        // External Links
                        if (filter_var($url, FILTER_VALIDATE_URL)) {

                            // Check if image.... -.-
                            if (in_array(substr($url, -4), [
                                '.png',
                                '.jpg',
                                'jpeg',
                                '.gif',
                                'webp',
                            ])) {
                                continue;
                            }

                            // Check if internal
                            $domain = get_site_url();
                            $domain = wp_parse_url($domain);
                            $domain = $domain['host'];

                            if (xagio_string_contains($domain, $url)) {

                                $out = self::curlHead($url);

                                if (isset($out['Location'])) {

                                    $post_id = url_to_postid($out['Location']);

                                    if ($post_id == 0) {

                                        $post_child             = new stdClass();
                                        $post_child->ID         = md5($out['Location']);
                                        $post_child->post_type  = 'page';
                                        $post_child->post_title = wp_strip_all_tags($url_data[2]);
                                        $post_child->permalink  = $out['Location'];

                                    } else {
                                        $post_child = get_post($post_id);
                                    }

                                } else {

                                    if ($out['HTTP'] == '404 Not Found') {

                                        $post_child             = new stdClass();
                                        $post_child->ID         = md5($url);
                                        $post_child->post_type  = 'page';
                                        $post_child->post_title = wp_strip_all_tags($url_data[2]);
                                        $post_child->permalink  = $url;
                                        $post_child->subtitle   = '<span class="subtitle-404">404 Page Not Found</span>';
                                        $post_child->class      = 'operator-404';

                                    } else {

                                        continue;

                                    }

                                }

                            } else {

                                $post_child             = new stdClass();
                                $post_child->ID         = md5($url);
                                $post_child->post_type  = 'external';
                                $post_child->post_title = wp_strip_all_tags($url_data[2]);
                                $post_child->permalink  = $url;
                                $post_child->subtitle   = '<span class="uk-text-success">dofollow</span> <i class="xagio-icon xagio-icon-question-circle" data-xagio-tooltip data-xagio-title="This is a normal, default link which search engines will follow and crawl."></i>';

                                if (strpos($url_data[0], 'rel') !== FALSE && strpos($url_data[0], 'nofollow') !== FALSE) {
                                    $post_child->subtitle = '<span class="uk-text-danger">nofollow</span> <i class="xagio-icon xagio-icon-question-circle" data-xagio-tooltip data-xagio-title="This link will not be followed by search engines."></i>';
                                }

                            }

                        } else {

                            $checkURL = $url;
                            $checkURL = get_site_url(NULL, $checkURL);

                            // Check if image.... -.-
                            if (in_array(substr($checkURL, -4), [
                                '.png',
                                '.jpg',
                                'jpeg',
                                '.gif',
                                'webp',
                            ])) {
                                continue;
                            }

                            $out = self::curlHead($checkURL);

                            if (isset($out['Location'])) {

                                $post_id = url_to_postid($out['Location']);

                                if ($post_id == 0) {

                                    $post_child             = new stdClass();
                                    $post_child->ID         = md5($out['Location']);
                                    $post_child->post_type  = 'page';
                                    $post_child->post_title = wp_strip_all_tags($url_data[2]);
                                    $post_child->permalink  = $out['Location'];

                                } else {
                                    $post_child = get_post($post_id);
                                }

                            } else {

                                if ($out['HTTP'] == '404 Not Found') {

                                    $post_child             = new stdClass();
                                    $post_child->ID         = md5($checkURL);
                                    $post_child->post_type  = 'page';
                                    $post_child->post_title = wp_strip_all_tags($url_data[2]);
                                    $post_child->permalink  = $checkURL;
                                    $post_child->subtitle   = '<span class="subtitle-404">404 Page Not Found</span>';
                                    $post_child->class      = 'operator-404';

                                } else {

                                    continue;

                                }

                            }

                        }
                    }

                    if ($post_child == NULL)
                        continue;

                    $operator_id = self::_findOperator(
                        $silo, $post_child->post_type, $post_child->ID
                    );

                    if ($operator_id === NULL) {

                        $child_slot = self::_createGridSlot(
                            $grid, $parent_slot, $post_child->post_type . '-' . $post_child->ID
                        );

                        $silo['operators'][] = [
                            'properties' => [
                                'title'     => $post_child->post_title,
                                'subtitle'  => isset($post_child->subtitle) ? $post_child->subtitle : '',
                                'attached'  => XAGIO_MODEL_PROJECTS::isAttachedToGroup($post_child->ID),
                                'icon'      => 'xagio-icon-flie',
                                'permalink' => isset($post_child->permalink) ? $post_child->permalink : get_permalink(
                                    $post_child->ID
                                ),
                                'inputs'    => [
                                    'ins' => [
                                        'label'    => '',
                                        'multiple' => TRUE,
                                    ],
                                ],
                                'outputs'   => [
                                    'outs' => [
                                        'label'    => '',
                                        'multiple' => TRUE,
                                    ],
                                ],
                                'class'     => 'operator-' . $post_child->post_type . ' op-' . $post_child->post_type . '-' . $post_child->ID . ' ' . ((isset($post_child->class) ? $post_child->class : '')),
                                'ID'        => 'op-' . $post_child->post_type . '-' . $post_child->ID,
                                'realID'    => $post_child->ID,
                                'type'      => $post_child->post_type,
                            ],
                            'left'       => $child_slot['x'],
                            'top'        => $child_slot['y'],
                        ];
                        $operator_id         = sizeof($silo['operators']) - 1;

                    }

                    if (in_array(
                        md5($parent_id . '/' . $operator_id), $links
                    )) {
                        continue;
                    }

                    // Find the last link
                    $subConnector_from = NULL;
                    $subConnector_to   = NULL;
                    $reversed          = array_reverse($silo['links']);
                    for ($i = 0; $i < sizeof($reversed); $i++) {
                        if ($reversed[$i]['fromOperator'] == $parent_id && $subConnector_from === NULL) {
                            $subConnector_from = $reversed[$i]['fromSubConnector'];
                        }
                        if ($reversed[$i]['toOperator'] == $operator_id && $subConnector_to === NULL) {
                            $subConnector_to = $reversed[$i]['toSubConnector'];
                        }
                    }

                    $links[] = md5($parent_id . '/' . $operator_id);

                    // Add the new link
                    $silo['links'][] = [
                        'fromOperator'     => $parent_id,
                        'fromConnector'    => 'outs',
                        'fromSubConnector' => ($subConnector_from === NULL) ? 0 : $subConnector_from + 1,
                        'toOperator'       => $operator_id,
                        'toConnector'      => 'ins',
                        'toSubConnector'   => ($subConnector_to === NULL) ? 0 : $subConnector_to + 1,
                    ];

                }
            }

            $silo['grid'] = $grid;

            xagio_json(
                'success', 'Successfully built Links SILO by ID!', $silo
            );
        }

        public static function generateSiloLinks()
        {
            sleep(1);

            $links = [];

            $grid = self::_generateGrid();

            $silo = [
                'operators' => [],
                'links'     => [],
                'settings'  => [
                    "canvas_size" => "10000",

                    "external_color"          => "#ff3c3c",
                    "external_line_color"     => "#ff3c3c",
                    "external_line_thickness" => "2",
                    "external_line_type"      => "15",

                    "internal_line_color"     => "#6ec2ff",
                    "internal_line_thickness" => "2",
                    "internal_line_type"      => "15",
                ],
                'grid'      => NULL,
            ];

            $args = [
                'posts_per_page' => -1,
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'post_type'      => [
                    'post',
                    'page',
                ],
                'post_status'    => [
                    'publish',
                ],
            ];

            $posts = get_posts($args);
            foreach ($posts as $post) {

                if (!wp_is_post_revision($post->ID)) {

                    $content_post = get_post($post->ID);
                    $content      = $content_post->post_content;
                    $content      = apply_filters('the_content', $content);
                    $content      = html_entity_decode($content);

                    preg_match_all(
                        '/<a.*?href="([^"]+)".*?>(.*?)<\/a>/s', stripslashes(do_shortcode($content)), $matches, PREG_SET_ORDER
                    );

                    if (!empty($matches)) {

                        // Find the parent ID
                        $parent_id = self::_findOperator(
                            $silo, $post->post_type, $post->ID
                        );

                        $parent_slot = FALSE;

                        // Create a parent page if not exists
                        if ($parent_id === NULL) {

                            $parent_slot = self::_createGridSlot(
                                $grid, FALSE, $post->post_type . '-' . $post->ID
                            );

                            $silo['operators'][] = [
                                'properties' => [
                                    'title'     => $post->post_title,
                                    'subtitle'  => '',
                                    'attached'  => XAGIO_MODEL_PROJECTS::isAttachedToGroup($post->ID),
                                    'icon'      => 'xagio-icon-flie',
                                    'permalink' => get_permalink($post->ID),
                                    'inputs'    => [
                                        'ins' => [
                                            'label'    => '',
                                            'multiple' => TRUE,
                                        ],
                                    ],
                                    'outputs'   => [
                                        'outs' => [
                                            'label'    => '',
                                            'multiple' => TRUE,
                                        ],
                                    ],
                                    'class'     => 'operator-page op-' . $post->post_type . '-' . $post->ID,
                                    'ID'        => 'op-' . $post->post_type . '-' . $post->ID,
                                    'realID'    => $post->ID,
                                    'type'      => $post->post_type,
                                ],
                                'left'       => $parent_slot['x'],
                                'top'        => $parent_slot['y'],
                            ];
                            $parent_id           = sizeof($silo['operators']) - 1;

                        } else {
                            $parent_slot = self::_findGridSlot(
                                $grid, $post->post_type . '-' . $post->ID
                            );
                        }

                        foreach ($matches as $url_data) {

                            $url = $url_data[1];

                            $post_id    = url_to_postid($url);
                            $post_child = get_post($post_id);

                            if ($post_id == 0) {

                                // External Links
                                if (filter_var($url, FILTER_VALIDATE_URL)) {

                                    // Check if image.... -.-
                                    if (in_array(substr($url, -4), [
                                        '.png',
                                        '.jpg',
                                        'jpeg',
                                        '.gif',
                                        'webp',
                                    ])) {
                                        continue;
                                    }

                                    // Check if internal
                                    $domain = get_site_url();
                                    $domain = wp_parse_url($domain);
                                    $domain = $domain['host'];

                                    if (xagio_string_contains($domain, $url)) {

                                        $out = self::curlHead($url);

                                        if (isset($out['Location'])) {

                                            $post_id = url_to_postid($out['Location']);

                                            if ($post_id == 0) {

                                                $post_child             = new stdClass();
                                                $post_child->ID         = md5($out['Location']);
                                                $post_child->post_type  = 'page';
                                                $post_child->post_title = wp_strip_all_tags($url_data[2]);
                                                $post_child->permalink  = $out['Location'];

                                            } else {

                                                $post_child = get_post($post_id);

                                            }

                                        } else {

                                            if ($out['HTTP'] == '404 Not Found') {

                                                $post_child             = new stdClass();
                                                $post_child->ID         = md5($url);
                                                $post_child->post_type  = 'page';
                                                $post_child->post_title = wp_strip_all_tags($url_data[2]);
                                                $post_child->permalink  = $url;
                                                $post_child->subtitle   = '<span class="subtitle-404">404 Page Not Found</span>';
                                                $post_child->class      = 'operator-404';

                                            } else {

                                                continue;

                                            }

                                        }

                                    } else {

                                        $post_child             = new stdClass();
                                        $post_child->ID         = md5($url);
                                        $post_child->post_type  = 'external';
                                        $post_child->post_title = wp_strip_all_tags($url_data[2]);
                                        $post_child->permalink  = $url;
                                        $post_child->subtitle   = '<span class="uk-text-success">dofollow</span> <i class="xagio-icon xagio-icon-question-circle" data-xagio-tooltip data-xagio-title="This is a normal, default link which search engines will follow and crawl."></i>';

                                        if (strpos($url_data[0], 'rel') !== FALSE && strpos($url_data[0], 'nofollow') !== FALSE) {
                                            $post_child->subtitle = '<span class="uk-text-danger">nofollow</span> <i class="xagio-icon xagio-icon-question-circle" data-xagio-tooltip data-xagio-title="This link will not be followed by search engines."></i>';
                                        }

                                    }

                                } else {

                                    $checkURL = $url;
                                    $checkURL = get_site_url(NULL, $checkURL);

                                    // Check if image.... -.-
                                    if (in_array(substr($checkURL, -4), [
                                        '.png',
                                        '.jpg',
                                        'jpeg',
                                        '.gif',
                                        'webp',
                                    ])) {
                                        continue;
                                    }

                                    $out = self::curlHead($checkURL);

                                    if (isset($out['Location'])) {

                                        $post_id = url_to_postid($out['Location']);

                                        if ($post_id == 0) {

                                            $post_child             = new stdClass();
                                            $post_child->ID         = md5($out['Location']);
                                            $post_child->post_type  = 'page';
                                            $post_child->post_title = wp_strip_all_tags($url_data[2]);
                                            $post_child->permalink  = $out['Location'];

                                        } else {

                                            $post_child = get_post($post_id);

                                        }

                                    } else {

                                        if ($out['HTTP'] == '404 Not Found') {

                                            $post_child             = new stdClass();
                                            $post_child->ID         = md5($checkURL);
                                            $post_child->post_type  = 'page';
                                            $post_child->post_title = wp_strip_all_tags($url_data[2]);
                                            $post_child->permalink  = $checkURL;
                                            $post_child->subtitle   = '<span class="subtitle-404">404 Page Not Found</span>';
                                            $post_child->class      = 'operator-404';

                                        } else {

                                            continue;

                                        }

                                    }

                                }
                            }

                            if ($post_child == NULL)
                                continue;

                            $operator_id = self::_findOperator(
                                $silo, $post_child->post_type, $post_child->ID
                            );

                            if ($operator_id === NULL) {

                                $child_slot = self::_createGridSlot(
                                    $grid, $parent_slot, $post_child->post_type . '-' . $post_child->ID
                                );

                                $silo['operators'][] = [
                                    'properties' => [
                                        'title'     => $post_child->post_title,
                                        'subtitle'  => isset($post_child->subtitle) ? $post_child->subtitle : '',
                                        'attached'  => XAGIO_MODEL_PROJECTS::isAttachedToGroup($post_child->ID),
                                        'icon'      => 'xagio-icon-flie',
                                        'permalink' => isset($post_child->permalink) ? $post_child->permalink : get_permalink(
                                            $post_child->ID
                                        ),
                                        'inputs'    => [
                                            'ins' => [
                                                'label'    => '',
                                                'multiple' => TRUE,
                                            ],
                                        ],
                                        'outputs'   => [
                                            'outs' => [
                                                'label'    => '',
                                                'multiple' => TRUE,
                                            ],
                                        ],
                                        'class'     => 'operator-' . $post_child->post_type . ' op-' . $post_child->post_type . '-' . $post_child->ID . ' ' . ((isset($post_child->class) ? $post_child->class : '')),
                                        'ID'        => 'op-' . $post_child->post_type . '-' . $post_child->ID,
                                        'realID'    => $post_child->ID,
                                        'type'      => $post_child->post_type,
                                    ],
                                    'left'       => $child_slot['x'],
                                    'top'        => $child_slot['y'],
                                ];
                                $operator_id         = sizeof($silo['operators']) - 1;

                            }

                            if (in_array(
                                md5($parent_id . '/' . $operator_id), $links
                            )) {
                                continue;
                            }

                            // Find the last link
                            $subConnector_from = NULL;
                            $subConnector_to   = NULL;
                            $reversed          = array_reverse($silo['links']);
                            for ($i = 0; $i < sizeof($reversed); $i++) {
                                if ($reversed[$i]['fromOperator'] == $parent_id && $subConnector_from === NULL) {
                                    $subConnector_from = $reversed[$i]['fromSubConnector'];
                                }
                                if ($reversed[$i]['toOperator'] == $operator_id && $subConnector_to === NULL) {
                                    $subConnector_to = $reversed[$i]['toSubConnector'];
                                }
                            }

                            $links[] = md5($parent_id . '/' . $operator_id);

                            // Add the new link
                            $silo['links'][] = [
                                'fromOperator'     => $parent_id,
                                'fromConnector'    => 'outs',
                                'fromSubConnector' => ($subConnector_from === NULL) ? 0 : $subConnector_from + 1,
                                'toOperator'       => $operator_id,
                                'toConnector'      => 'ins',
                                'toSubConnector'   => ($subConnector_to === NULL) ? 0 : $subConnector_to + 1,
                            ];

                        }
                    }
                }
            }

            $silo['grid'] = $grid;

            xagio_json(
                'success', 'Successfully built Links SILO!', $silo
            );
        }

        private static function curlHead($url)
        {
            $response = wp_remote_head($url);

            if (is_wp_error($response)) {
                return FALSE;
            }

            $headers = wp_remote_retrieve_headers($response);

            if (empty($headers)) {
                return FALSE;
            }

            $return = [];
            foreach ($headers as $key => $value) {
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $return[trim($key)] = trim($value);
            }

            return $return;
        }

        public static function generateSilo()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['name'], $_POST['type'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            sleep(0.5);

            $name      = sanitize_text_field(wp_unslash($_POST['name']));
            $type      = sanitize_text_field(wp_unslash($_POST['type']));
            $importAll = isset($_POST['importAll']) ? sanitize_text_field(wp_unslash($_POST['importAll'])) : false;
            $importAll = $importAll == 'yes';

            $silo = get_option('xagio_silo_' . $type);

            if (!is_array($silo))
                $silo = [];

            if ($type == 'pages') {

                $grid_pages = self::_generateGrid(20000);

                $pages = get_pages();

                $pages_op = [
                    'operators' => [],
                    'links'     => [],
                    'settings'  => [

                        'line_thickness' => '2',
                        'line_type'      => '0',
                        'line_color'     => '#559acc',

                        'canvas_size' => '20000',
                    ],
                ];

                // First, create pages by their parents
                $ids_alone  = [];
                $temp_pages = [];
                foreach ($pages as $page) {
                    if ($page->post_parent != 0) {
                        if (!isset($temp_pages[$page->post_parent])) {
                            $temp_pages[$page->post_parent] = [];
                        }
                        $temp_pages[$page->post_parent][] = $page;
                    } else {
                        $ids_alone[] = $page;
                    }
                }
                $pages = $temp_pages;

                // Do all the alone pages first
                if ($importAll == TRUE) {
                    foreach ($ids_alone as $page) {

                        $alone_slot = self::_createGridSlot(
                            $grid_pages, FALSE, 'page-' . $page->ID
                        );

                        $pages_op['operators'][] = [
                            'properties' => [
                                'title'    => $page->post_title,
                                'subtitle' => '',
                                'attached' => XAGIO_MODEL_PROJECTS::isAttachedToGroup($page->ID),
                                'icon'     => 'xagio-icon-flie',
                                'inputs'   => [
                                    'input_1' => [
                                        'label' => '',
                                    ],
                                ],
                                'outputs'  => [
                                    'outs' => [
                                        'label'    => '',
                                        'multiple' => TRUE,
                                    ],
                                ],
                                'class'    => 'operator-page op-page-' . $page->ID,
                                'ID'       => 'op-page-' . $page->ID,
                                'realID'   => $page->ID,
                                'type'     => 'page',
                            ],
                            'left'       => $alone_slot['x'],
                            'top'        => $alone_slot['y'],
                        ];

                    }
                }

                // Go through each parents pages

                foreach ($pages as $post_parent => $parent_pages) {

                    foreach ($parent_pages as $page) {

                        // Find the parent ID
                        $parent_id = self::_findOperator(
                            $pages_op, 'page', $page->post_parent
                        );

                        $parent_slot = FALSE;

                        // Create a parent page if not exists
                        if ($parent_id === NULL) {

                            $parent = get_post($page->post_parent);

                            $parent_slot = self::_createGridSlot(
                                $grid_pages, FALSE, 'page-' . $page->post_parent
                            );

                            $pages_op['operators'][] = [
                                'properties' => [
                                    'title'    => $parent->post_title,
                                    'subtitle' => '',
                                    'attached' => XAGIO_MODEL_PROJECTS::isAttachedToGroup($page->ID),
                                    'icon'     => 'xagio-icon-flie',
                                    'inputs'   => [
                                        'input_1' => [
                                            'label' => '',
                                        ],
                                    ],
                                    'outputs'  => [
                                        'outs' => [
                                            'label'    => '',
                                            'multiple' => TRUE,
                                        ],
                                    ],
                                    'class'    => 'operator-page op-page-' . $page->post_parent,
                                    'ID'       => 'op-page-' . $page->post_parent,
                                    'realID'   => $page->post_parent,
                                    'type'     => 'page',
                                ],
                                'left'       => $parent_slot['x'],
                                'top'        => $parent_slot['y'],
                            ];
                            $parent_id               = sizeof($pages_op['operators']) - 1;

                        } else {
                            $parent_slot = self::_findGridSlot(
                                $grid_pages, 'page-' . $page->post_parent
                            );
                        }

                        $operator_id = self::_findOperator(
                            $pages_op, 'page', $page->ID
                        );

                        if ($operator_id === NULL) {

                            $child_slot = self::_createGridSlot(
                                $grid_pages, $parent_slot, 'page-' . $page->ID
                            );

                            $pages_op['operators'][] = [
                                'properties' => [
                                    'title'    => $page->post_title,
                                    'subtitle' => '',
                                    'attached' => XAGIO_MODEL_PROJECTS::isAttachedToGroup($page->ID),
                                    'icon'     => 'xagio-icon-flie',
                                    'inputs'   => [
                                        'input_1' => [
                                            'label' => '',
                                        ],
                                    ],
                                    'outputs'  => [
                                        'outs' => [
                                            'label'    => '',
                                            'multiple' => TRUE,
                                        ],
                                    ],
                                    'class'    => 'operator-page op-page-' . $page->ID,
                                    'ID'       => 'op-page-' . $page->ID,
                                    'realID'   => $page->ID,
                                    'type'     => 'page',
                                ],
                                'left'       => $child_slot['x'],
                                'top'        => $child_slot['y'],
                            ];
                            $operator_id             = sizeof($pages_op['operators']) - 1;
                        }

                        // Find the last link
                        $subConnector = NULL;
                        $reversed     = array_reverse($pages_op['links']);
                        for ($i = 0; $i < sizeof($reversed); $i++) {
                            if ($reversed[$i]['fromOperator'] == $parent_id) {
                                $subConnector = $reversed[$i]['fromSubConnector'];
                                break;
                            }
                        }
                        // Add the new link
                        $pages_op['links'][] = [
                            'fromOperator'     => $parent_id,
                            'fromConnector'    => 'outs',
                            'fromSubConnector' => ($subConnector === NULL) ? 0 : $subConnector + 1,
                            'toOperator'       => $operator_id,
                            'toConnector'      => 'input_1',
                            'toSubConnector'   => 0,
                        ];

                    }

                }

                $silo[$name] = urlencode(wp_json_encode($pages_op));

            } else {

                // Tags & Categories

                $grid_posts = self::_generateGrid(20000);

                $posts_op = [
                    'operators' => [],
                    'links'     => [],
                    'settings'  => [
                        'line_category_thickness' => '2',
                        'line_category_type'      => '15',
                        'line_category_color'     => '#6ec2ff',
                        'box_category_color'      => '#d7eeff',

                        'line_tag_thickness' => '2',
                        'line_tag_type'      => '15',
                        'line_tag_color'     => '#989898',
                        'box_tag_color'      => '#f1f1f1',

                        'canvas_size' => '20000',
                    ],
                ];

                $tags = get_tags(
                    [
                        "hide_empty" => 0,
                        "type"       => "post",
                        "orderby"    => "name",
                        "order"      => "ASC",
                    ]
                );

                $categories = get_categories(
                    [
                        "hide_empty" => 0,
                        "type"       => "post",
                        "orderby"    => "name",
                        "order"      => "ASC",
                    ]
                );

                // Do tags first
                foreach ($tags as $tag) {

                    // Create a parent operator / tag
                    $parent_id = self::_findOperator(
                        $posts_op, 'tag', $tag->term_id
                    );

                    // Create a parent page if not exists
                    if ($parent_id === NULL) {

                        $parent_slot = self::_createGridSlot_Posts(
                            $grid_posts, [
                            'r' => 3,
                            'c' => 0
                        ], 'tag-' . $tag->term_id
                        );

                        $posts_op['operators'][] = [
                            'properties' => [
                                'title'    => $tag->name,
                                'subtitle' => '',
                                'attached' => 0,
                                'icon'     => 'xagio-icon-tag',
                                'inputs'   => [
                                    'ins' => [
                                        'label'    => '',
                                        'multiple' => TRUE,
                                    ],
                                ],
                                'outputs'  => [],
                                'class'    => 'operator-tag op-tag-' . $tag->term_id,
                                'ID'       => 'op-tag-' . $tag->term_id,
                                'realID'   => $tag->term_id,
                                'type'     => 'tag',
                            ],
                            'left'       => $parent_slot['x'],
                            'top'        => $parent_slot['y'],
                        ];
                        $parent_id               = sizeof($posts_op['operators']) - 1;

                    } else {
                        $parent_slot = self::_findGridSlot(
                            $grid_posts, 'tag-' . $tag->term_id
                        );
                    }

                    // Get the posts from each tag
                    $args = [
                        'posts_per_page' => -1,
                        'tax_query'      => [
                            [
                                'taxonomy' => 'post_tag',
                                'field'    => 'slug',
                                'terms'    => $tag->slug,
                            ],
                        ],
                    ];

                    // Go through each Post
                    $posts = get_posts($args);

                    foreach ($posts as $post) {

                        $operator_id = self::_findOperator(
                            $posts_op, 'post', $post->ID
                        );

                        if ($operator_id === NULL) {

                            $child_slot = self::_createGridSlot_Posts(
                                $grid_posts, FALSE, 'post-' . $post->ID
                            );

                            $posts_op['operators'][] = [
                                'properties' => [
                                    'title'    => $post->post_title,
                                    'subtitle' => '',
                                    'attached' => XAGIO_MODEL_PROJECTS::isAttachedToGroup($post->ID),
                                    'icon'     => 'xagio-icon-flie',
                                    'inputs'   => [],
                                    'outputs'  => [
                                        'outs' => [
                                            'label'    => '',
                                            'multiple' => TRUE,
                                        ],
                                    ],
                                    'class'    => 'operator-post op-post-' . $post->ID,
                                    'ID'       => 'op-post-' . $post->ID,
                                    'realID'   => $post->ID,
                                    'type'     => 'post',
                                ],
                                'left'       => $child_slot['x'],
                                'top'        => $child_slot['y'],
                            ];
                            $operator_id             = sizeof($posts_op['operators']) - 1;


                        }

                        // Find the last link
                        $subConnector_from = NULL;
                        $subConnector_to   = NULL;
                        $reversed          = array_reverse($posts_op['links']);
                        for ($i = 0; $i < sizeof($reversed); $i++) {
                            if ($reversed[$i]['fromOperator'] == $operator_id && $subConnector_from === NULL) {
                                $subConnector_from = $reversed[$i]['fromSubConnector'];
                            }
                            if ($reversed[$i]['toOperator'] == $parent_id && $subConnector_to === NULL) {
                                $subConnector_to = $reversed[$i]['toSubConnector'];
                            }
                        }

                        // Add the new link
                        $posts_op['links'][] = [
                            'fromOperator'     => $operator_id,
                            'fromConnector'    => 'outs',
                            'fromSubConnector' => ($subConnector_from === NULL) ? 0 : $subConnector_from + 1,
                            'toOperator'       => $parent_id,
                            'toConnector'      => 'ins',
                            'toSubConnector'   => ($subConnector_to === NULL) ? 0 : $subConnector_to + 1,
                        ];

                    }

                }

                // Do categories next
                foreach ($categories as $category) {

                    // Create a parent operator / tag
                    $parent_id = self::_findOperator(
                        $posts_op, 'category', $category->term_id
                    );

                    // Create a parent page if not exists
                    if ($parent_id === NULL) {

                        $parent_slot = self::_createGridSlot_Posts(
                            $grid_posts, [
                            'r' => 4,
                            'c' => 0
                        ], 'category-' . $category->term_id
                        );

                        $posts_op['operators'][] = [
                            'properties' => [
                                'title'    => $category->name,
                                'subtitle' => '',
                                'attached' => 0,
                                'icon'     => 'xagio-icon-align-right',
                                'inputs'   => [
                                    'ins' => [
                                        'label'    => '',
                                        'multiple' => TRUE,
                                    ],
                                ],
                                'outputs'  => [],
                                'class'    => 'operator-category op-category-' . $category->term_id,
                                'ID'       => 'op-category-' . $category->term_id,
                                'realID'   => $category->term_id,
                                'type'     => 'category',
                            ],
                            'left'       => $parent_slot['x'],
                            'top'        => $parent_slot['y'],
                        ];
                        $parent_id               = sizeof($posts_op['operators']) - 1;

                    } else {
                        $parent_slot = self::_findGridSlot(
                            $grid_posts, 'category-' . $category->term_id
                        );
                    }

                    // Get the posts from each tag
                    $args = [
                        'posts_per_page' => -1,
                        'tax_query'      => [
                            [
                                'taxonomy' => 'category',
                                'field'    => 'slug',
                                'terms'    => $category->slug,
                            ],
                        ],
                    ];

                    // Go through each Post
                    $posts = get_posts($args);

                    foreach ($posts as $post) {

                        $operator_id = self::_findOperator(
                            $posts_op, 'post', $post->ID
                        );

                        if ($operator_id === NULL) {

                            $child_slot = self::_createGridSlot_Posts(
                                $grid_posts, FALSE, 'post-' . $post->ID
                            );

                            $posts_op['operators'][] = [
                                'properties' => [
                                    'title'    => $post->post_title,
                                    'subtitle' => '',
                                    'attached' => XAGIO_MODEL_PROJECTS::isAttachedToGroup($post->ID),
                                    'icon'     => 'xagio-icon-flie',
                                    'inputs'   => [],
                                    'outputs'  => [
                                        'outs' => [
                                            'label'    => '',
                                            'multiple' => TRUE,
                                        ],
                                    ],
                                    'class'    => 'operator-post op-post-' . $post->ID,
                                    'ID'       => 'op-post-' . $post->ID,
                                    'realID'   => $post->ID,
                                    'type'     => 'post',
                                ],
                                'left'       => $child_slot['x'],
                                'top'        => $child_slot['y'],
                            ];
                            $operator_id             = sizeof($posts_op['operators']) - 1;

                        }

                        // Find the last link
                        $subConnector_from = NULL;
                        $subConnector_to   = NULL;
                        $reversed          = array_reverse($posts_op['links']);
                        for ($i = 0; $i < sizeof($reversed); $i++) {
                            if ($reversed[$i]['fromOperator'] == $operator_id && $subConnector_from === NULL) {
                                $subConnector_from = $reversed[$i]['fromSubConnector'];
                            }
                            if ($reversed[$i]['toOperator'] == $parent_id && $subConnector_to === NULL) {
                                $subConnector_to = $reversed[$i]['toSubConnector'];
                            }
                        }

                        // Add the new link
                        $posts_op['links'][] = [
                            'fromOperator'     => $operator_id,
                            'fromConnector'    => 'outs',
                            'fromSubConnector' => ($subConnector_from === NULL) ? 0 : $subConnector_from + 1,
                            'toOperator'       => $parent_id,
                            'toConnector'      => 'ins',
                            'toSubConnector'   => ($subConnector_to === NULL) ? 0 : $subConnector_to + 1,
                        ];

                    }

                }

                $silo[$name] = urlencode(wp_json_encode($posts_op));

            }

            update_option(
                'xagio_silo_' . $type, $silo
            );

            xagio_json('success', 'Successfully generated SILO.');

        }

        public static function setPagePostTitle()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['type'], $_POST['id'], $_POST['title'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $type  = sanitize_text_field(wp_unslash($_POST['type']));
            $id    = intval($_POST['id']);
            $title = sanitize_text_field(wp_unslash($_POST['title']));

            if ($type == 'page' || $type == 'post') {

                $post_data = [
                    'ID'         => $id,
                    'post_title' => $title,
                ];

                // Update the post into the database
                wp_update_post($post_data);

            } else if ($type == 'tag' || $type == 'category') {

                wp_update_term(
                    $id, ($type == 'tag') ? 'post_tag' : $type, [
                        'name' => $title,
                    ]
                );

            }

            // Update the Operator as well
            $operators = self::_getOperators($type);

            // Find the operator
            $operator_id = self::_findOperator(
                $operators, $type, $id
            );

            // Modify the operator
            if ($operator_id !== NULL) {
                $operators['operators'][$operator_id]['properties']['title'] = $title;
                self::_updateOperators(
                    $operators, $type
                );
            }

        }

        public static function newCategory()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['name'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $name = sanitize_text_field(wp_unslash($_POST['name']));

            wp_create_category($name);
        }

        public static function newTag()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['name'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $name = sanitize_text_field(wp_unslash($_POST['name']));

            wp_create_tag($name);
        }

        public static function resetParentsCategoriesTags()
        {
            //			$pages = get_pages();
            //			$posts = get_posts();
            //
            //			foreach ($pages as $page) {
            //				$post_data = [
            //					'ID'          => $page->ID,
            //					'post_parent' => 0
            //				];
            //
            //				// Update the post into the database
            //				wp_update_post($post_data);
            //			}
            //
            //			foreach ($posts as $post) {
            //				wp_set_post_categories($post->ID, [], FALSE);
            //				wp_set_post_tags($post->ID, [], FALSE);
            //			}

            update_option('XAGIO_SILO_IDS_PAGES', FALSE);

            update_option('XAGIO_SILO_IDS_POSTS', FALSE);

            update_option('XAGIO_SILO_PAGES', FALSE);
            update_option('XAGIO_SILO_POSTS', FALSE);

        }

        public static function pageSaveSilo($post_id)
        {

            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // Fix for trashing posts/pages
            if (!isset($_POST['post_ID'], $_POST['post_type'])) {
                return $post_id;
            }

            // Fix for Fusion Builder page ID
            if ($_POST['post_ID'] != $post_id) {
                $post_id = intval($_POST['post_ID']);
            }

            if (wp_is_post_revision($post_id)) {
                return $post_id;
            }

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return $post_id;
            }

            $post_type = sanitize_text_field(wp_unslash($_POST['post_type']));

            // Check the user's permissions.
            if ('page' === $post_type) {

                if (!current_user_can(
                    'edit_page', $post_id
                )) {
                    return $post_id;
                }

            } else {

                if (!current_user_can(
                    'edit_post', $post_id
                )) {
                    return $post_id;
                }
            }

            // Only handle Posts & Pages
            if ($post_type != 'page' && $post_type != 'post') {
                return $post_id;
            }

            /**
             *  Prepare the Operators
             */

            $operators = NULL;

            $operators = get_option(($post_type == 'page') ? 'XAGIO_SILO_PAGES' : 'XAGIO_SILO_POSTS');
            $operators = urldecode($operators);
            $operators = stripslashes($operators);
            $operators = json_decode(
                $operators, TRUE
            );


            /**
             *   Update the H1 if found
             */
            $hasChanges = FALSE;

            /**
             *   Update the SILO Builder
             */

            if ($post_type == 'page') {

                $post = get_post($post_id);

                // Find the operator
                $operator_id = self::_findOperator(
                    $operators, $post_type, $post_id
                );

                $hasChildren = self::_hasChildren(
                    $operators, $operator_id
                );

                // Remove operator if doesn't have a parent and is not a parent
                if ($post->post_parent == 0 && $hasChildren == FALSE) {

                    self::_removeOperator(
                        $operators, $operator_id
                    );

                    $hasChanges = TRUE;
                }

                if ($post->post_parent != 0) {

                    // Find the parent ID
                    $parent_id = self::_findOperator(
                        $operators, 'page', $post->post_parent
                    );

                    // Create a parent page if not exists
                    if ($parent_id === NULL) {
                        $parent                   = get_post($post->post_parent);
                        $operators['operators'][] = [
                            'properties' => [
                                'title'    => $parent->post_title,
                                'subtitle' => '',
                                'attached' => XAGIO_MODEL_PROJECTS::isAttachedToGroup($parent->ID),
                                'icon'     => 'xagio-icon-flie',
                                'inputs'   => [
                                    'input_1' => [
                                        'label' => '',
                                    ],
                                ],
                                'outputs'  => [
                                    'outs' => [
                                        'label'    => '',
                                        'multiple' => TRUE,
                                    ],
                                ],
                                'class'    => 'operator-page op-page-' . $parent->ID,
                                'ID'       => 'op-page-' . $parent->ID,
                                'realID'   => $parent->ID,
                                'type'     => 'page',
                            ],
                            'left'       => @$operators['operators'][sizeof($operators['operators']) - 1]['left'],
                            'top'        => @$operators['operators'][sizeof($operators['operators']) - 1]['top'] + 120,
                        ];
                        $parent_id                = sizeof($operators['operators']) - 1;
                    }

                    if ($operator_id === NULL) {
                        $operators['operators'][] = [
                            'properties' => [
                                'title'    => $post->post_title,
                                'subtitle' => '',
                                'attached' => XAGIO_MODEL_PROJECTS::isAttachedToGroup($post->ID),
                                'icon'     => 'xagio-icon-flie',
                                'inputs'   => [
                                    'input_1' => [
                                        'label' => '',
                                    ],
                                ],
                                'outputs'  => [
                                    'outs' => [
                                        'label'    => '',
                                        'multiple' => TRUE,
                                    ],
                                ],
                                'class'    => 'operator-page op-page-' . $post->ID,
                                'ID'       => 'op-page-' . $post->ID,
                                'realID'   => $post->ID,
                                'type'     => 'page',
                            ],
                            'left'       => @$operators['operators'][sizeof($operators['operators']) - 1]['left'],
                            'top'        => @$operators['operators'][sizeof($operators['operators']) - 1]['top'] + 120,
                        ];
                        $operator_id              = sizeof($operators['operators']) - 1;
                    }

                    // Remove the existing links
                    for ($i = 0; $i < sizeof($operators['links']); $i++) {
                        if ($operators['links'][$i]['toOperator'] == $operator_id) {
                            unset($operators['links'][$i]);
                            break;
                        }
                    }

                    $operators['links'] = array_values($operators['links']);

                    // Find the last link
                    $subConnector = NULL;
                    $reversed     = array_reverse($operators['links']);
                    for ($i = 0; $i < sizeof($reversed); $i++) {
                        if ($reversed[$i]['fromOperator'] == $parent_id) {
                            $subConnector = $reversed[$i]['fromSubConnector'];
                            break;
                        }
                    }
                    // Add the new link
                    $operators['links'][] = [
                        'fromOperator'     => $parent_id,
                        'fromConnector'    => 'outs',
                        'fromSubConnector' => ($subConnector === NULL) ? 0 : $subConnector + 1,
                        'toOperator'       => $operator_id,
                        'toConnector'      => 'input_1',
                        'toSubConnector'   => 0,
                    ];

                    $hasChanges = TRUE;
                }


            } else if ($post_type == 'post') {

                $post = get_post($post_id);

                // Find the operator
                $operator_id = self::_findOperator(
                    $operators, $post_type, $post_id
                );

                $tags       = wp_get_post_tags($post_id);
                $categories = wp_get_post_categories($post_id);

                // Create a new operator
                if ($operator_id === NULL && (!empty($categories) || !empty($tags))) {

                    $operators['operators'][] = [
                        'properties' => [
                            'title'    => $post->post_title,
                            'subtitle' => '',
                            'attached' => XAGIO_MODEL_PROJECTS::isAttachedToGroup($post->ID),
                            'icon'     => 'xagio-icon-flie',
                            'inputs'   => [],
                            'outputs'  => [
                                'outs' => [
                                    'label'    => '',
                                    'multiple' => TRUE,
                                ],
                            ],
                            'class'    => 'operator-post op-post-' . $post->ID,
                            'ID'       => 'op-post-' . $post->ID,
                            'realID'   => $post->ID,
                            'type'     => 'post',
                        ],
                        'left'       => @$operators['operators'][sizeof($operators['operators']) - 1]['left'],
                        'top'        => @$operators['operators'][sizeof($operators['operators']) - 1]['top'] + 120,
                    ];

                    $operator_id = sizeof($operators['operators']) - 1;
                    $hasChanges  = TRUE;
                }

                // Remove existing links
                if ($operator_id !== NULL) {
                    self::_removeOperator(
                        $operators, $operator_id, 'fromOperator'
                    );
                }

                // Check if tags are not empty, then create tag operator

                if (!empty($tags)) {
                    foreach ($tags as $tag) {

                        $tag_id = self::_findOperator(
                            $operators, 'tag', $tag->term_id
                        );

                        if ($tag_id === NULL) {
                            $operators['operators'][] = [
                                'properties' => [
                                    'title'    => $tag->name,
                                    'subtitle' => '',
                                    'attached' => 0,
                                    'icon'     => 'xagio-icon-tag',
                                    'inputs'   => [
                                        'ins' => [
                                            'label'    => '',
                                            'multiple' => TRUE,
                                        ],
                                    ],
                                    'outputs'  => [],
                                    'class'    => 'operator-tag op-tag-' . $tag->term_id,
                                    'ID'       => 'op-tag-' . $tag->term_id,
                                    'realID'   => $tag->term_id,
                                    'type'     => 'tag',
                                ],
                                'left'       => @$operators['operators'][sizeof($operators['operators']) - 1]['left'],
                                'top'        => @$operators['operators'][sizeof(
                                                                             $operators['operators']
                                                                         ) - 1]['top'] + 120,
                            ];
                            $tag_id                   = sizeof($operators['operators']) - 1;
                            $hasChanges               = TRUE;
                        }

                        $hasLink = FALSE;

                        foreach ($operators['links'] as $link) {
                            if ($link['fromOperator'] == $operator_id && $link['toOperator'] == $tag_id) {
                                $hasLink = TRUE;
                                break;
                            }
                        }

                        if ($hasLink == FALSE) {
                            $hasChanges            = TRUE;
                            $subConnector_tag      = NULL;
                            $subConnector_operator = NULL;
                            $reversed              = array_reverse($operators['links']);
                            for ($i = 0; $i < sizeof($reversed); $i++) {
                                if ($reversed[$i]['toOperator'] == $tag_id && $subConnector_tag === NULL) {
                                    $subConnector_tag = $reversed[$i]['toSubConnector'];
                                }
                                if ($reversed[$i]['fromOperator'] == $operator_id && $subConnector_operator === NULL) {
                                    $subConnector_operator = $reversed[$i]['fromSubConnector'];
                                }
                            }

                            $operators['links'][] = [
                                'fromOperator'     => $operator_id,
                                'fromConnector'    => 'outs',
                                'fromSubConnector' => ($subConnector_operator === NULL) ? 0 : $subConnector_operator + 1,
                                'toOperator'       => $tag_id,
                                'toConnector'      => 'ins',
                                'toSubConnector'   => ($subConnector_tag === NULL) ? 0 : $subConnector_tag + 1,
                            ];
                        }
                    }
                }

                // Check if categories are not empty, then create categories operator

                if (!empty($categories)) {
                    foreach ($categories as $term_id) {
                        $category = get_term($term_id);

                        $category_id = self::_findOperator(
                            $operators, 'category', $term_id
                        );

                        if ($category_id === NULL) {
                            $operators['operators'][] = [
                                'properties' => [
                                    'title'    => $category->name,
                                    'subtitle' => '',
                                    'attached' => 0,
                                    'icon'     => 'xagio-icon-align-right',
                                    'inputs'   => [
                                        'ins' => [
                                            'label'    => '',
                                            'multiple' => TRUE,
                                        ],
                                    ],
                                    'outputs'  => [],
                                    'class'    => 'operator-category op-category-' . $term_id,
                                    'ID'       => 'op-category-' . $term_id,
                                    'realID'   => $term_id,
                                    'type'     => 'category',
                                ],
                                'left'       => @$operators['operators'][sizeof($operators['operators']) - 1]['left'],
                                'top'        => @$operators['operators'][sizeof(
                                                                             $operators['operators']
                                                                         ) - 1]['top'] + 120,
                            ];
                            $category_id              = sizeof($operators['operators']) - 1;
                            $hasChanges               = TRUE;
                        }

                        $hasLink = FALSE;

                        foreach ($operators['links'] as $link) {
                            if ($link['fromOperator'] == $operator_id && $link['toOperator'] == $category_id) {
                                $hasLink = TRUE;
                                break;
                            }
                        }

                        if ($hasLink == FALSE) {
                            $hasChanges            = TRUE;
                            $subConnector_category = NULL;
                            $subConnector_operator = NULL;
                            $reversed              = array_reverse($operators['links']);
                            for ($i = 0; $i < sizeof($reversed); $i++) {
                                if ($reversed[$i]['toOperator'] == $category_id && $subConnector_category === NULL) {
                                    $subConnector_category = $reversed[$i]['toSubConnector'];
                                }
                                if ($reversed[$i]['fromOperator'] == $operator_id && $subConnector_operator === NULL) {
                                    $subConnector_operator = $reversed[$i]['fromSubConnector'];
                                }
                            }

                            $operators['links'][] = [
                                'fromOperator'     => $operator_id,
                                'fromConnector'    => 'outs',
                                'fromSubConnector' => ($subConnector_operator === NULL) ? 0 : $subConnector_operator + 1,
                                'toOperator'       => $category_id,
                                'toConnector'      => 'ins',
                                'toSubConnector'   => ($subConnector_category === NULL) ? 0 : $subConnector_category + 1,
                            ];
                        }
                    }
                }

            }

            if ($hasChanges == TRUE) {
                update_option(
                    ($post_type == 'page') ? 'XAGIO_SILO_PAGES' : 'XAGIO_SILO_POSTS', urlencode(wp_json_encode($operators))
                );
            }

            return $post_id;


        }

        public static function removeSiloName()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['type'], $_POST['name'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $type = sanitize_text_field(wp_unslash($_POST['type']));
            $name = sanitize_text_field(wp_unslash($_POST['name']));

            $option = get_option('xagio_silo_' . $type);
            if (!is_array($option))
                $option = [];

            unset($option[$name]);

            update_option('xagio_silo_' . $type, $option);
        }

        public static function newSILO()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['type'], $_POST['name'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $type = sanitize_text_field(wp_unslash($_POST['type']));
            $name = sanitize_text_field(wp_unslash($_POST['name']));

            $option = get_option('xagio_silo_' . $type);
            if (!is_array($option))
                $option = [];

            if (isset($option[$name])) {
                xagio_json('error', 'SILO with the same name already exists. Please choose a different name.');
                return;
            }

            $silo = NULL;
            if ($type == 'pages') {
                $silo = [
                    'operators' => [],
                    'links'     => [],
                    'settings'  => [

                        'line_thickness' => '2',
                        'line_type'      => '15',
                        'line_color'     => '#6ec2ff',

                        'canvas_size' => '10000',
                    ],
                ];
            } else if ($type == 'posts') {
                $silo = [
                    'operators' => [],
                    'links'     => [],
                    'settings'  => [
                        'line_category_thickness' => '2',
                        'line_category_type'      => '15',
                        'line_category_color'     => '#6ec2ff',
                        'box_category_color'      => '#d7eeff',

                        'line_tag_thickness' => '2',
                        'line_tag_type'      => '15',
                        'line_tag_color'     => '#989898',
                        'box_tag_color'      => '#f1f1f1',

                        'canvas_size' => '10000',
                    ],
                ];
            } else if ($type == 'links') {
                $silo = [
                    'operators' => [],
                    'links'     => [],
                    'settings'  => [
                        'internal_line_thickness' => '2',
                        'internal_line_type'      => '15',
                        'internal_line_color'     => '#6ec2ff',

                        'external_line_thickness' => '2',
                        'external_line_type'      => '15',
                        'external_line_color'     => '#ff3c3c',
                        'external_color'          => '#ff3c3c',

                        'canvas_size' => '10000',
                    ],
                ];
            }

            $option[$name] = urlencode(wp_json_encode($silo));

            update_option('xagio_silo_' . $type, $option);

            xagio_json('success', 'Successfully created a new SILO.');
        }

        public static function loadSiloNames()
        {
            $option_pages = get_option('XAGIO_SILO_PAGES');
            $option_posts = get_option('XAGIO_SILO_POSTS');
            $option_links = get_option('xagio_silo_links');

            $data = [];

            if (!isset($data['pages']))
                $data['pages'] = [];
            if (!isset($data['posts']))
                $data['posts'] = [];
            if (!isset($data['links']))
                $data['links'] = [];

            if (is_array($option_pages) && !empty($option_pages)) {
                if (!in_array('Default', $data['pages'])) {
                    $data['pages'][] = 'Default';
                }
                $data['pages'] = array_merge($data['pages'], array_keys($option_pages));
                $data['pages'] = array_unique($data['pages']);
                $data['pages'] = array_values($data['pages']);
            } else {
                $data['pages'] = [
                    'Default',
                ];
            }

            if (is_array($option_posts) && !empty($option_posts)) {
                if (!in_array('Default', $data['posts'])) {
                    $data['posts'][] = 'Default';
                }
                $data['posts'] = array_merge($data['posts'], array_keys($option_posts));
                $data['posts'] = array_unique($data['posts']);
                $data['posts'] = array_values($data['posts']);
            } else {
                $data['posts'] = [
                    'Default',
                ];
            }

            if (is_array($option_links) && !empty($option_links)) {
                if (!in_array('Default', $data['links'])) {
                    $data['links'][] = 'Default';
                }
                $data['links'] = array_merge($data['links'], array_keys($option_links));
                $data['links'] = array_unique($data['links']);
                $data['links'] = array_values($data['links']);
            } else {
                $data['links'] = [
                    'Default',
                ];
            }

            xagio_json('success', 'Successfully loaded SILO names.', $data);
        }

        public static function saveSilo()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['name'], $_POST['silo'], $_POST['type'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $name = sanitize_text_field(wp_unslash($_POST['name']));
            $silo = sanitize_text_field(wp_unslash($_POST['silo']));
            $type = sanitize_text_field(wp_unslash($_POST['type']));

            // Get the SILO
            $option = get_option('xagio_silo_' . $type);
            if (!is_array($option)) {
                $option = [];
            }

            $option[$name] = urlencode($silo);

            $silo = json_decode(
                stripslashes($silo), TRUE
            );

            if ($type == 'pages') {

                foreach ($silo['links'] as $link) {

                    $child  = $silo['operators'][$link['toOperator']]['properties'];
                    $parent = $silo['operators'][$link['fromOperator']]['properties'];

                    $childID = explode(
                        '-', $child['ID']
                    );
                    $childID = $childID[sizeof($childID) - 1];

                    $parentID = explode(
                        '-', $parent['ID']
                    );
                    $parentID = $parentID[sizeof($parentID) - 1];

                    $post_data = [
                        'ID'          => $childID,
                        'post_parent' => $parentID,
                    ];

                    // Update the post into the database
                    wp_update_post($post_data);

                }

            } else if ($type == 'posts') {

                $posts_data = [];

                foreach ($silo['links'] as $link) {

                    $child  = $silo['operators'][$link['fromOperator']]['properties'];
                    $parent = $silo['operators'][$link['toOperator']]['properties'];

                    $childID = explode(
                        '-', $child['ID']
                    );
                    $childID = $childID[sizeof($childID) - 1];

                    $parentID   = explode(
                        '-', $parent['ID']
                    );
                    $parentType = $parentID[sizeof($parentID) - 2];
                    $parentID   = $parentID[sizeof($parentID) - 1];

                    if (!isset($posts_data[$childID])) {
                        $posts_data[$childID] = [
                            'categories' => [],
                            'tags'       => [],
                        ];
                    }

                    if ($parentType == 'tag') {
                        $tag                            = get_tag($parentID);
                        $posts_data[$childID]['tags'][] = $tag->name;
                    } else {
                        $posts_data[$childID]['categories'][] = $parentID;
                    }

                }

                foreach ($posts_data as $ID => $data) {

                    wp_set_post_categories(
                        $ID, $data['categories'], FALSE
                    );
                    wp_set_post_tags(
                        $ID, $data['tags'], FALSE
                    );

                }

            }

            if ($type !== 'links') {

                // Loop through the whole silo and find the used IDs
                $_IDS_ = [];

                foreach ($option as $silo) {

                    $silo = json_decode(
                        stripslashes(urldecode($silo)), TRUE
                    );

                    foreach ($silo['operators'] as $operator) {

                        if (!isset($_IDS_[$operator['properties']['type']])) {
                            $_IDS_[$operator['properties']['type']] = [];
                        }

                        $_IDS_[$operator['properties']['type']][] = $operator['properties']['realID'];

                        $_IDS_[$operator['properties']['type']] = array_unique($_IDS_[$operator['properties']['type']]);

                    }

                }

                update_option(
                    'xagio_silo_ids_' . $type, $_IDS_
                );

            }

            update_option(
                'xagio_silo_' . $type, $option
            );

            xagio_json(
                'success', 'Silo changes have been successfully saved.', @$_IDS_
            );
        }

        public static function loadSilo()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['name'], $_POST['type'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $name = sanitize_text_field(wp_unslash($_POST['name']));
            $type = sanitize_text_field(wp_unslash($_POST['type']));

            $silo = get_option('xagio_silo_' . $type);
            if (!is_array($silo))
                $silo = [];

            if (isset($silo[$name])) {

                $silo = $silo[$name];

                $silo = stripslashes(urldecode($silo));

                $silo = json_decode(
                    $silo, TRUE
                );

            } else {

                if ($type == 'pages') {
                    $silo = [
                        'operators' => [],
                        'links'     => [],
                        'settings'  => [

                            'line_thickness' => '2',
                            'line_type'      => '15',
                            'line_color'     => '#6ec2ff',

                            'canvas_size' => '10000',
                        ],
                    ];
                } else if ($type == 'posts') {
                    $silo = [
                        'operators' => [],
                        'links'     => [],
                        'settings'  => [
                            'line_category_thickness' => '2',
                            'line_category_type'      => '15',
                            'line_category_color'     => '#6ec2ff',
                            'box_category_color'      => '#d7eeff',

                            'line_tag_thickness' => '2',
                            'line_tag_type'      => '15',
                            'line_tag_color'     => '#989898',
                            'box_tag_color'      => '#f1f1f1',

                            'canvas_size' => '10000',
                        ],
                    ];
                } else if ($type == 'links') {
                    $silo = [
                        'operators' => [],
                        'links'     => [],
                        'settings'  => [
                            'internal_line_thickness' => '2',
                            'internal_line_type'      => '15',
                            'internal_line_color'     => '#6ec2ff',

                            'external_line_thickness' => '2',
                            'external_line_type'      => '15',
                            'external_line_color'     => '#ff3c3c',
                            'external_color'          => '#ff3c3c',

                            'canvas_size' => '10000',
                        ],
                    ];
                }

            }

            $silo['operators'] = array_values($silo['operators']);

            if ($type == 'pages') {

                for ($i = 0; $i < sizeof($silo['operators']); $i++) {
                    $silo['operators'][$i]['properties']['permalink'] = get_permalink(
                        $silo['operators'][$i]['properties']['realID']
                    );
                    $silo['operators'][$i]['properties']['title']     = get_the_title(
                        $silo['operators'][$i]['properties']['realID']
                    );
                }

            } else if ($type == 'posts') {

                for ($i = 0; $i < sizeof($silo['operators']); $i++) {
                    if ($silo['operators'][$i]['properties']['type'] != 'post') {
                        $silo['operators'][$i]['properties']['title']     = @get_term($silo['operators'][$i]['properties']['realID'])->name;
                        $silo['operators'][$i]['properties']['permalink'] = get_term_link(
                            $silo['operators'][$i]['properties']['realID']
                        );
                    } else {
                        $silo['operators'][$i]['properties']['title']     = get_the_title(
                            $silo['operators'][$i]['properties']['realID']
                        );
                        $silo['operators'][$i]['properties']['permalink'] = get_permalink(
                            $silo['operators'][$i]['properties']['realID']
                        );
                    }

                }

            }

            if ($type !== 'links') {

                $_IDS_ = get_option('xagio_silo_ids_' . $type);

                $silo['IDS'] = $_IDS_;

            }

            xagio_json(
                'success', 'Silo has been loaded successfully.', $silo
            );

        }

        public static function addNewPagePost()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['title'], $_POST['status'], $_POST['type'], $_POST['url'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $user_id = get_current_user_id();
            $title   = sanitize_text_field(wp_unslash($_POST['title']));
            $status  = sanitize_text_field(wp_unslash($_POST['status']));
            $type    = sanitize_text_field(wp_unslash($_POST['type']));
            $url     = sanitize_url(wp_unslash($_POST['url']));

            $post_data = [
                'post_title'  => $title,
                //                'post_content' => $content,
                'post_status' => $status,
                'post_author' => $user_id,
                'post_type'   => $type,
                'post_name'   => $url,
            ];

            wp_insert_post($post_data);
        }

        public static function deletePage()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $id = intval($_POST['id']);
            wp_delete_post($id);
        }

        public static function deleteTag()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $id = intval($_POST['id']);
            wp_delete_term(
                $id, 'post_tag'
            );
        }

        public static function deleteCategory()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $id = intval($_POST['id']);
            wp_delete_category($id);
        }

        public static function getOperatorData()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['id'], $_POST['type'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $id   = intval($_POST['id']);
            $type = sanitize_text_field(wp_unslash($_POST['type']));
            $data = [];

            if ($type == 'category' || $type == 'tag') {

                $term          = get_term($id);
                $data['h1']    = $term->name;
                $data['title'] = get_term_meta($id, 'XAGIO_SEO_TITLE', TRUE);
                $data['desc']  = get_term_meta($id, 'XAGIO_SEO_DESCRIPTION', TRUE);
                $data['slug']  = $term->slug;


            } else {

                $post          = get_post($id);
                $data['h1']    = $post->post_title;
                $data['title'] = get_post_meta($id, 'XAGIO_SEO_TITLE', TRUE);
                $data['desc']  = get_post_meta($id, 'XAGIO_SEO_DESCRIPTION', TRUE);
                $data['slug']  = $post->post_name;


            }

            xagio_json(
                'success', 'Loaded operator data #' . $id, $data
            );
        }

        public static function updateOperatorData()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['id'], $_POST['type'], $_POST['h1'], $_POST['title'], $_POST['desc'], $_POST['slug'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $id   = intval($_POST['id']);
            $type = sanitize_text_field(wp_unslash($_POST['type']));

            $h1    = sanitize_text_field(wp_unslash($_POST['h1']));
            $title = sanitize_text_field(wp_unslash($_POST['title']));
            $desc  = sanitize_text_field(wp_unslash($_POST['desc']));
            $slug  = sanitize_text_field(wp_unslash($_POST['slug']));

            if ($type == 'category' || $type == 'tag') {

                update_term_meta($id, 'XAGIO_SEO_TITLE', $title);
                update_term_meta($id, 'XAGIO_SEO_DESCRIPTION', $desc);

                $term_update = [
                    'name' => $h1,
                ];

                if (!empty($slug)) {
                    $term_update['slug'] = sanitize_title($slug);
                }

                wp_update_term(
                    $id, (($type == 'category') ? $type : 'post_tag'), $term_update
                );

            } else {

                update_post_meta($id, 'XAGIO_SEO_TITLE', $title);
                update_post_meta($id, 'XAGIO_SEO_DESCRIPTION', $desc);

                $post_update = [
                    'ID'         => $id,
                    'post_title' => $h1,
                ];

                if (!empty($slug)) {
                    $post_update['post_name'] = sanitize_title($slug);
                }

                wp_update_post($post_update);

            }
        }

        public static function _updateOperators($operators, $type)
        {
            $operators['operators'] = array_values($operators['operators']);
            $operators['links']     = array_values($operators['links']);
            update_option(
                ($type == 'page') ? 'XAGIO_SILO_PAGES' : 'XAGIO_SILO_POSTS', urlencode(wp_json_encode($operators))
            );
        }

        public static function _getOperators($type)
        {
            $operators = get_option(($type == 'page') ? 'XAGIO_SILO_PAGES' : 'XAGIO_SILO_POSTS');
            $operators = urldecode($operators);
            $operators = stripslashes($operators);
            $operators = json_decode(
                $operators, TRUE
            );
            return $operators;
        }

        public static function _removeOperator(&$operators, $operator_id, $type = 'toOperator')
        {
            $newLinks = [];
            for ($i = 0; $i < sizeof($operators['links']); $i++) {
                if ($operators['links'][$i][$type] != $operator_id) {
                    $newLinks[] = $operators['links'][$i];
                }
            }
            $operators['links'] = $newLinks;

            // can't do this, will cause a shitload of problems
            // unset($operators['operators'][$operator_id]);
        }

        public static function _hasChildren($operators, $operator_id)
        {
            $hasChildren = FALSE;
            for ($i = 0; $i < sizeof($operators['links']); $i++) {
                if ($operators['links'][$i]['toOperator'] == $operator_id) {
                    $hasChildren = TRUE;
                    break;
                }
            }
            return $hasChildren;
        }

        public static function _findOperator($operators, $type, $id)
        {
            if (!isset($operators['operators']))
                return NULL;
            for ($i = 0; $i < sizeof($operators['operators']); $i++) {
                if ($operators['operators'][$i]['properties']['realID'] == $id && $operators['operators'][$i]['properties']['type'] == $type) {
                    return $i;
                }
            }
            return NULL;
        }
    }

}
