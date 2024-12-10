<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_MODEL_BATCHES')) {

    class XAGIO_MODEL_BATCHES
    {

        public static function initialize()
        {
            add_filter('cron_schedules', [
                'XAGIO_MODEL_BATCHES',
                'customSchedules'
            ]);
            add_action('xagio_checkKeywordBatches', [
                'XAGIO_MODEL_BATCHES',
                'checkKeywordBatches'
            ]);
            if (!wp_next_scheduled('xagio_checkKeywordBatches')) {
                wp_schedule_event(time(), 'minute', 'xagio_checkKeywordBatches');
            }

            if (!XAGIO_HAS_ADMIN_PERMISSIONS) return;

            add_action('admin_post_xagio_checkBatchCron', [
                'XAGIO_MODEL_BATCHES',
                'checkKeywordBatches'
            ]);
        }

        public static function customSchedules($schedules)
        {
            if (!isset($schedules["minute"])) {
                $schedules["minute"] = [
                    'interval' => 60,
                    'display'  => 'Once every minute',
                ];
            }
            return $schedules;
        }

        public static function checkKeywordBatches()
        {
            global $wpdb;

            $batches = $wpdb->get_results('SELECT * FROM xag_batches', ARRAY_A);

            if (sizeof($batches) == 0) {
                xagio_json('done', 'No more batches.');
            }

            $change = FALSE;

            foreach ($batches as $batch) {

                $http_code = 0;
                $result    = XAGIO_API::apiRequest(
                    $endpoint = 'keywords', $method = 'GET', [
                        'batch_id' => $batch['batch_id'],
                    ], $http_code
                );

                // Check the status
                if ($http_code == 200) {

                    $change = TRUE;

                    // They're all completed
                    foreach ($result as $keyword) {
                        $wpdb->update('xag_keywords', [
                            'intitle' => $keyword['intitle'],
                            'inurl'   => $keyword['inurl'],
                            'queued'  => 0,
                        ], [
                            'id' => $keyword['real_id'],
                        ]);
                    }

                    // Remove the batch
                    $wpdb->delete('xag_batches', [
                        'id' => $batch['id'],
                    ]);

                } else if ($http_code == 500) {
                    $wpdb->delete('xag_batches', [
                        'id' => $batch['id'],
                    ]);
                }

            }

            if ($change == TRUE) {
                xagio_json('change', 'Batch finished.');
            } else {
                xagio_json('pending', 'Batch still running.');
            }
        }

        public static function createTable()
        {
            global $wpdb;
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            $charset_collate = $wpdb->get_charset_collate();
            $creation_query  = 'CREATE TABLE xag_batches (
			        `id` int(11) NOT NULL AUTO_INCREMENT,
			        `batch_id` int(11),
			        `status` varchar(255) default "pending",		  
			        `date_created` datetime,			        
			        PRIMARY KEY  (`id`)
			    ) ' . $charset_collate . ';';
            @dbDelta($creation_query);
        }

        public static function removeTable()
        {
            global $wpdb;
            $wpdb->query('DROP TABLE IF EXISTS xag_batches;');
        }

    }

}