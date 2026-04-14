<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists("XAGIO_MODEL_BACKUPS")) {
    class XAGIO_MODEL_BACKUPS
    {
        private static function defines()
        {
            define('XAGIO_BACKUPS_IGNORE_DOMAINS', filter_var(get_option('XAGIO_BACKUPS_IGNORE_DOMAINS'), FILTER_VALIDATE_BOOLEAN));
        }

        public static function scriptData()
        {
            $xagio_bs = get_option('XAGIO_BACKUP_SPEED', []);
            $bz = get_option('XAGIO_BACKUP_SIZE', '');

            $backup_speed = [
                'grade'      => isset($xagio_bs['grade']) ? esc_html($xagio_bs['grade']) : '',
                'time_taken' => isset($xagio_bs['time_taken']) ? esc_html($xagio_bs['time_taken']) : ''
            ];

            wp_localize_script('xagio_backup', 'xagio_backup', [
                'backup_speed' => $backup_speed,
                'backup_size'  => esc_html($bz)
            ]);
        }

        public static function processUploadQueues()
        {
            // List of upload action hooks to check.
            $upload_hooks = array(
                'XAGIO_OnedriveClient_Process_Upload',
                'XAGIO_GoogleDrive_Process_Upload',
                'XAGIO_Dropbox_Process_Upload',
                'XAGIO_S3_Process_Upload',
            );

            // Retrieve the current cron array.
            $cron_array = _get_cron_array();
            if (empty($cron_array)) {
                return;
            }

            // Use the current time (in GMT) to compare with scheduled timestamps.
            $current_time    = time();
            $delay_threshold = 600; // 10 minutes in seconds

            // Loop through each scheduled timestamp.
            foreach ($cron_array as $xagio_timestamp => $cron_hooks) {
                // Only process events that are at least 10 minutes overdue.
                if ($xagio_timestamp > $current_time - $delay_threshold) {
                    continue;
                }

                // Loop through each of our defined hooks.
                foreach ($upload_hooks as $xagio_hook) {
                    if (!empty($cron_hooks[$xagio_hook])) {
                        foreach ($cron_hooks[$xagio_hook] as $xagio_unique_id => $xagio_event) {
                            // Retrieve the event's arguments, if any.
                            $xagio_args = isset($xagio_event['args']) ? $xagio_event['args'] : array();

                            // Unschedule the event so it doesn't run again.
                            wp_unschedule_event($xagio_timestamp, $xagio_hook, $xagio_args);

                            // Run the action immediately with its arguments.
                            do_action($xagio_hook, ...$xagio_args);
                        }
                    }
                }
            }
        }


        public static function initialize()
        {
            self::loadClasses();

            add_action('admin_enqueue_scripts', function () {
                if (isset($_GET['page'])) {
                    $current_screen = sanitize_text_field(wp_unslash($_GET['page']));
                    if ($current_screen === 'xagio-clone') {
                        // Disable the Heartbeat API
                        wp_deregister_script('heartbeat');
                    }
                }
            });

            add_action('admin_print_scripts', [
                'XAGIO_MODEL_BACKUPS',
                'scriptData'
            ]);

            XAGIO_MODEL_BACKUPS::defines();

            // Add cron schedules
            add_filter("cron_schedules", [
                "XAGIO_MODEL_BACKUPS",
                "customSchedules"
            ]);

            // Schedule backups
            if (!wp_next_scheduled('xagio_doBackup')) {
                $backup_date = get_option('XAGIO_BACKUP_DATE');
                wp_schedule_event(time(), $backup_date, 'xagio_doBackup');
            }

            // Set Crons
            add_action("xagio_doBackup", [
                "XAGIO_MODEL_BACKUPS",
                "doBackup"
            ]);

            add_action('XAGIO_OnedriveClient_Process_Upload', [
                'XAGIO_OnedriveClient',
                'processUploadQueue'
            ], 10, 5);

            add_action('XAGIO_GoogleDrive_Process_Upload', array(
                'XAGIO_GoogleDrive',
                'processUploadQueue'
            ), 10, 5);

            add_action('XAGIO_Dropbox_Process_Upload', array(
                'XAGIO_DropboxClient',
                'processUploadQueue'
            ), 10, 6);

            add_action('XAGIO_S3_Process_Upload', array(
                'XAGIO_S3',
                'processUploadQueue'
            ), 10, 7);


            // check if action is scheduled
            if (!wp_next_scheduled('xagio_calculate_backup_size')) {
                wp_schedule_event(time(), 'daily', 'xagio_calculate_backup_size');
            }

            // Load Backup Keys
            self::loadKeys();

            self::processUploadQueues();

            if (!XAGIO_HAS_ADMIN_PERMISSIONS)
                return;

            add_action("admin_post_xagio_create_backup", [
                "XAGIO_MODEL_BACKUPS",
                "createBackup"
            ]);
            add_action("admin_post_xagio_remove_backup", [
                "XAGIO_MODEL_BACKUPS",
                "removeBackup"
            ]);

            // Restore
            add_action("wp_ajax_xagio_restore_full_backup", [
                "XAGIO_MODEL_BACKUPS",
                "restoreFullBackup"
            ]);
            add_action("wp_ajax_xagio_restore_file_backup", [
                "XAGIO_MODEL_BACKUPS",
                "restoreFileBackup"
            ]);
            add_action("wp_ajax_xagio_restore_mysql_backup", [
                "XAGIO_MODEL_BACKUPS",
                "restoreMySQLBackup"
            ]);
            add_action("admin_post_xagio_restore_backup", [
                "XAGIO_MODEL_BACKUPS",
                "restoreBackup"
            ]);
            add_action("admin_post_xagio_save_backup_settings", [
                "XAGIO_MODEL_BACKUPS",
                "saveSettings"
            ]);

            add_action("admin_post_xagio_save_backup_amazons3_settings", [
                "XAGIO_MODEL_BACKUPS",
                "saveAmazonS3Settings"
            ]);

            add_action("admin_post_xagio_get_backups", [
                "XAGIO_MODEL_BACKUPS",
                "getBackups"
            ]);
            add_action("admin_post_xagio_download_backup", [
                "XAGIO_MODEL_BACKUPS",
                "downloadBackup"
            ]);
            add_action("admin_post_xagio_delete_backup", [
                "XAGIO_MODEL_BACKUPS",
                "deleteBackup"
            ]);

            add_action("admin_post_xagio_check_backup_speed", [
                "XAGIO_MODEL_BACKUPS",
                "checkSpeed"
            ]);
            add_action("admin_post_xagio_check_backup_size", [
                "XAGIO_MODEL_BACKUPS",
                "calculateBackupSize"
            ]);

            add_action('xagio_calculate_backup_size', [
                'XAGIO_MODEL_BACKUPS',
                'calculateBackupSize'
            ]);
        }

        public static function checkSpeed()
        {
            update_option('XAGIO_BACKUP_SPEED', xagio_backup_speed());
        }

        public static function calculateBackupSize()
        {
            update_option("XAGIO_BACKUP_SIZE", xagio_calculate_backup_size());
        }

        public static function getBackups()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['storage'], $_SERVER['HTTP_HOST'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $storage = sanitize_text_field(wp_unslash($_POST["storage"]));

            // Get domain of current website
            $domain = self::getName(site_url());

            try {

                switch ($storage) {

                    case 'dropbox':
                        xagio_jsonc(self::getBackups_Dropbox($domain));
                        break;

                    case 'onedrive':
                        xagio_jsonc(self::getBackups_Onedrive($domain));
                        break;

                    case 'googledrive':
                        xagio_jsonc(self::getBackups_GoogleDrive($domain));
                        break;

                    case 'amazons3':
                        xagio_jsonc(self::getBackups_AmazonS3($domain));
                        break;

                    default:
                        xagio_json('error', 'Unknown storage method.');
                        break;
                }

            } catch (Exception $ex) {

                xagio_json('error', $ex->getMessage());

            }

        }

        public static function downloadBackup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['storage'], $_POST['backup'], $_POST['id'], $_SERVER['HTTP_HOST'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $storage = sanitize_text_field(wp_unslash($_POST['storage']));
            $xagio_backup  = sanitize_text_field(wp_unslash($_POST['backup']));
            $id      = sanitize_text_field(wp_unslash($_POST['id']));

            if (empty($storage) || $xagio_backup == NULL || $id == NULL) {
                xagio_json('error', 'Fields are empty. Bye.');
                exit;
            }

            // Get domain of current website
            $domain = sanitize_url(sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])));

            try {

                switch ($storage) {

                    case 'dropbox':
                        xagio_jsonc(self::downloadBackup_Dropbox($domain, $xagio_backup));
                        break;

                    case 'onedrive':
                        xagio_jsonc(self::downloadBackup_Onedrive($domain, $xagio_backup));
                        break;

                    case 'googledrive':
                        xagio_jsonc(self::downloadBackup_GoogleDrive($xagio_backup));
                        break;

                    case 'amazons3':
                        xagio_jsonc(self::downloadBackup_AmazonS3($id));
                        break;

                    default:
                        xagio_json('error', 'Unknown storage method.');
                        break;
                }

            } catch (Exception $ex) {

                xagio_json('error', $ex->getMessage());

            }

        }

        public static function deleteBackup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['storage'], $_POST['backup'], $_POST['id'], $_SERVER['HTTP_HOST'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $storage = sanitize_text_field(wp_unslash($_POST['storage']));
            $xagio_backup  = sanitize_text_field(wp_unslash($_POST['backup']));
            $id      = sanitize_text_field(wp_unslash($_POST['id']));

            if (empty($storage) || $xagio_backup == NULL) {
                xagio_json('error', 'Fields are empty. Bye.');
                exit;
            }

            // Get domain of current website
            $domain = self::getName(site_url());

            try {

                switch ($storage) {

                    case 'dropbox':
                        xagio_jsonc(self::deleteBackup_Dropbox($domain, $xagio_backup));
                        break;

                    case 'onedrive':
                        xagio_jsonc(self::deleteBackup_Onedrive($domain, $xagio_backup));
                        break;

                    case 'googledrive':
                        xagio_jsonc(self::deleteBackup_GoogleDrive($id));
                        break;

                    case 'amazons3':
                        xagio_jsonc(self::deleteBackup_AmazonS3($id));
                        break;

                    default:
                        xagio_json('error', 'Unknown storage method.');
                        break;
                }

            } catch (Exception $ex) {

                xagio_json('error', $ex->getMessage());

            }

        }

        /*
         *  Downloading Backups
         */
        private static function downloadBackup_Dropbox($domain, $xagio_backup)
        {
            $xagio_tokens = self::loadTokens('XAGIO_DropboxClient');

            // check the token
            $backup_DropboxAccessToken = isset($xagio_tokens["dropbox"]) ? $xagio_tokens["dropbox"] : "";

            // check if empty
            if (empty($backup_DropboxAccessToken)) {
                return array(
                    'status'  => 'error',
                    'message' => 'Missing Dropbox credentials. Please fill your Dropbox credentials on the Settings page.'
                );
            }

            // Initialize the class
            $XAGIO_DropboxClient = new XAGIO_DropboxClient(
                $backup_DropboxAccessToken
            );

            $folder = self::getName(site_url());

            $xagio_result = $XAGIO_DropboxClient->GetLink('/' . $folder . '/' . $xagio_backup);

            if (isset($xagio_result['link'])) {
                return array(
                    'status'  => 'success',
                    'message' => 'Successfully retrieved backup temporary URL!',
                    'data'    => $xagio_result['link']
                );
            } else {
                return array(
                    'status'  => 'error',
                    'message' => 'There was a problem while requesting temporary download URL from Dropbox, you will have to download this backup manually.'
                );
            }

        }

        private static function downloadBackup_Onedrive($domain, $xagio_backup)
        {
            $xagio_tokens = self::loadTokens('XAGIO_OnedriveClient');

            // check the token
            $backup_OnedriveAccessToken = isset($xagio_tokens["onedrive"]) ? $xagio_tokens["onedrive"] : "";

            // check if empty
            if (empty($backup_OnedriveAccessToken)) {
                return array(
                    'status'  => 'error',
                    'message' => 'Missing OneDrive credentials. Please fill your OneDrive credentials on the Settings page.'
                );
            }

            // Initialize the class
            $XAGIO_OnedriveClient = new XAGIO_OnedriveClient();

            $XAGIO_OnedriveClient->SetAccessToken(
                $backup_OnedriveAccessToken
            );

            $XAGIO_OnedriveClient->renewAccessToken();

            $xagio_result = $XAGIO_OnedriveClient->GetFileFolder('/drive/root:/xagio/' . $xagio_backup);

            if (isset($xagio_result["@microsoft.graph.downloadUrl"])) {
                return array(
                    'status'  => 'success',
                    'message' => 'Successfully retrieved backup temporary URL!',
                    'data'    => $xagio_result["@microsoft.graph.downloadUrl"]
                );
            } else {
                return array(
                    'status'  => 'error',
                    'message' => 'There was a problem while requesting temporary download URL from OneDrive, you will have to download this backup manually.'
                );
            }


        }

        private static function downloadBackup_GoogleDrive($xagio_backup)
        {
            $xagio_tokens = self::loadTokens('XAGIO_GoogleDrive');

            // check the token
            $backup_XAGIO_GoogleDriveAccessToken = isset($xagio_tokens["googledrive"]) ? $xagio_tokens["googledrive"] : "";

            // check if empty
            if (empty($backup_XAGIO_GoogleDriveAccessToken)) {
                return array(
                    'status'  => 'error',
                    'message' => 'Missing Google Drive credentials. Please fill your Google Drive credentials on the Settings page.'
                );
            }

            // Initialize the class
            $XAGIO_GoogleDriveClient = new XAGIO_GoogleDrive(
                $backup_XAGIO_GoogleDriveAccessToken
            );

            $xagio_result = $XAGIO_GoogleDriveClient->FindFiles($xagio_backup);

            if (isset($xagio_result['items'][0])) {
                return array(
                    'status'  => 'redirect',
                    'message' => 'Successfully retrieved backup temporary URL!',
                    'data'    => $xagio_result['items'][0]['webContentLink']
                );
            } else {
                return array(
                    'status'  => 'error',
                    'message' => 'There was a problem while requesting temporary download URL from Google Drive, you will have to download this backup manually.'
                );
            }

        }

        private static function downloadBackup_AmazonS3($id)
        {
            $xagio_tokens = self::loadTokens('XAGIO_S3');

            $backup_AmazonAccessKey = isset($xagio_tokens["amazon"]["access_key"]) ? $xagio_tokens["amazon"]["access_key"] : "";
            $backup_AmazonSecretKey = isset($xagio_tokens["amazon"]["secret_key"]) ? $xagio_tokens["amazon"]["secret_key"] : "";
            $backup_AmazonBucket    = isset($xagio_tokens["amazon"]["bucket"]) ? $xagio_tokens["amazon"]["bucket"] : "";
            $backup_AmazonRegion    = isset($xagio_tokens["amazon"]["region"]) ? $xagio_tokens["amazon"]["region"] : "";

            // if any of these is empty, return error
            if (empty($backup_AmazonAccessKey) || empty($backup_AmazonSecretKey) || empty($backup_AmazonBucket) || empty($backup_AmazonRegion)) {
                return array(
                    'status'  => 'error',
                    'message' => 'Missing Amazon S3 credentials. Please fill your Amazon S3 credentials on the Settings page.'
                );
            }

            $S3 = new XAGIO_S3(
                $backup_AmazonAccessKey, $backup_AmazonSecretKey, $backup_AmazonRegion, $backup_AmazonBucket
            );

            return array(
                'status'  => 'redirect',
                'message' => 'Successfully retrieved backup temporary URL!',
                'data'    => $S3->get_download_link($id)
            );


        }


        /*
         *  Deleting Backups
         */
        private static function deleteBackup_Dropbox($domain, $xagio_backup)
        {
            $xagio_tokens = self::loadTokens('XAGIO_DropboxClient');

            // check the token
            $backup_DropboxAccessToken = isset($xagio_tokens["dropbox"]) ? $xagio_tokens["dropbox"] : "";

            // check if empty
            if (empty($backup_DropboxAccessToken)) {
                return array(
                    'status'  => 'error',
                    'message' => 'Missing Dropbox credentials. Please fill your Dropbox credentials on the Settings page.'
                );
            }

            // Initialize the class
            $XAGIO_DropboxClient = new XAGIO_DropboxClient(
                $backup_DropboxAccessToken
            );

            $folder = self::getName(site_url());
            $xagio_result = $XAGIO_DropboxClient->Delete('/' . $folder . '/' . $xagio_backup);

            if (isset($xagio_result['name'])) {
                return array(
                    'status'  => 'success',
                    'message' => 'Successfully deleted backup!'
                );
            } else {
                return array(
                    'status'  => 'error',
                    'message' => 'There was a problem while deleting this backup! You will have to manually delete it from your specified storage.'
                );
            }

        }

        private static function deleteBackup_Onedrive($domain, $xagio_backup)
        {
            $xagio_tokens = self::loadTokens('XAGIO_OnedriveClient');

            // check the token
            $backup_OnedriveAccessToken = isset($xagio_tokens["onedrive"]) ? $xagio_tokens["onedrive"] : "";

            // check if empty
            if (empty($backup_OnedriveAccessToken)) {
                return array(
                    'status'  => 'error',
                    'message' => 'Missing OneDrive credentials. Please fill your OneDrive credentials on the Settings page.'
                );
            }

            // Initialize the class
            $XAGIO_OnedriveClient = new XAGIO_OnedriveClient();

            $XAGIO_OnedriveClient->SetAccessToken(
                $backup_OnedriveAccessToken
            );
            $XAGIO_OnedriveClient->renewAccessToken();

            $xagio_result = $XAGIO_OnedriveClient->deleteCall('/drive/root:/xagio/' . $xagio_backup);

            if ($xagio_result === 204) {
                return array(
                    'status'  => 'success',
                    'message' => 'Successfully deleted backup!'
                );
            } else {
                return array(
                    'status'  => 'error',
                    'message' => 'There was a problem while deleting this backup! You will have to manually delete it from your specified storage.'
                );
            }


        }

        private static function deleteBackup_GoogleDrive($id)
        {
            $xagio_tokens = self::loadTokens('XAGIO_GoogleDrive');

            // check the token
            $backup_XAGIO_GoogleDriveAccessToken = isset($xagio_tokens["googledrive"]) ? $xagio_tokens["googledrive"] : "";

            // check if empty
            if (empty($backup_XAGIO_GoogleDriveAccessToken)) {
                return array(
                    'status'  => 'error',
                    'message' => 'Missing Google Drive credentials. Please fill your Google Drive credentials on the Settings page.'
                );
            }

            // Initialize the class
            $XAGIO_GoogleDriveClient = new XAGIO_GoogleDrive(
                $backup_XAGIO_GoogleDriveAccessToken
            );

            $out = $XAGIO_GoogleDriveClient->Delete($id);
            if (isset($out['error'])) {
                return array(
                    'status'  => 'error',
                    'message' => $out['error']['message']
                );
            }

            return array(
                'status'  => 'success',
                'message' => 'Successfully deleted backup!'
            );

        }

        private static function deleteBackup_AmazonS3($id)
        {
            $xagio_tokens = self::loadTokens('XAGIO_S3');

            $backup_AmazonAccessKey = isset($xagio_tokens["amazon"]["access_key"]) ? $xagio_tokens["amazon"]["access_key"] : "";
            $backup_AmazonSecretKey = isset($xagio_tokens["amazon"]["secret_key"]) ? $xagio_tokens["amazon"]["secret_key"] : "";
            $backup_AmazonBucket    = isset($xagio_tokens["amazon"]["bucket"]) ? $xagio_tokens["amazon"]["bucket"] : "";
            $backup_AmazonRegion    = isset($xagio_tokens["amazon"]["region"]) ? $xagio_tokens["amazon"]["region"] : "";

            // if any of these is empty, return error
            if (empty($backup_AmazonAccessKey) || empty($backup_AmazonSecretKey) || empty($backup_AmazonBucket) || empty($backup_AmazonRegion)) {
                return array(
                    'status'  => 'error',
                    'message' => 'Missing Amazon S3 credentials. Please fill your Amazon S3 credentials on the Settings page.'
                );
            }

            $S3 = new XAGIO_S3(
                $backup_AmazonAccessKey, $backup_AmazonSecretKey, $backup_AmazonRegion, $backup_AmazonBucket
            );

            if ($S3->remove_file($id)) {
                return array(
                    'status'  => 'success',
                    'message' => 'Successfully deleted selected backup!'
                );
            } else {
                return array(
                    'status'  => 'error',
                    'message' => 'Failed to delete selected backup! Please remove it manually!'
                );
            }


        }

        /*
         *  Listing Backups
         */
        private static function getBackups_Dropbox($domain)
        {
            $xagio_tokens = self::loadTokens('XAGIO_DropboxClient');

            // check the token
            $backup_DropboxAccessToken = isset($xagio_tokens["dropbox"]) ? $xagio_tokens["dropbox"] : "";

            // check if empty
            if (empty($backup_DropboxAccessToken)) {
                return array(
                    'status'  => 'error',
                    'message' => 'Missing Dropbox credentials. Please fill your Dropbox credentials on the Settings page.'
                );
            }

            // Initialize the class
            $XAGIO_DropboxClient = new XAGIO_DropboxClient(
                $backup_DropboxAccessToken
            );

            $folder = self::getName(site_url());

            $xagio_files = $XAGIO_DropboxClient->ListFolder($folder);

            if ($xagio_files == false) {
                return array(
                    'status'  => 'info',
                    'message' => 'There are still no backups for this website. Please try again later.'
                );
            } else {
                $xagio_output = array();
                foreach ($xagio_files['entries'] as $xagio_file) {
                    if ($xagio_file['.tag'] == 'file' && strpos($xagio_file['name'], $folder) !== false) {
                        $xagio_output[] = array(
                            'file' => $xagio_file['name'],
                            'size' => $xagio_file['size'],
                            'date' => $xagio_file['client_modified'],
                            'id'   => $xagio_file['id']
                        );
                    }
                }
                usort($xagio_output, function ($a, $b) {
                    if ($a['date'] == $b['date']) {
                        return 0;
                    }
                    return ($a['date'] > $b['date']) ? -1 : 1;
                });
                return array(
                    'status'  => 'success',
                    'message' => 'Retrieved backups from Dropbox.',
                    'files'   => $xagio_output
                );
            }
        }

        private static function getBackups_Onedrive($domain)
        {
            $xagio_tokens = self::loadTokens('XAGIO_OnedriveClient');

            // check the token
            $backup_OnedriveAccessToken = isset($xagio_tokens["onedrive"]) ? $xagio_tokens["onedrive"] : "";

            // check if empty
            if (empty($backup_OnedriveAccessToken)) {
                return array(
                    'status'  => 'error',
                    'message' => 'Missing OneDrive credentials. Please fill your OneDrive credentials on the Settings page.'
                );
            }

            // Initialize the class
            $XAGIO_OnedriveClient = new XAGIO_OnedriveClient();

            $XAGIO_OnedriveClient->SetAccessToken(
                $backup_OnedriveAccessToken
            );
            $XAGIO_OnedriveClient->renewAccessToken();

            $folder = self::getName(site_url());
            $xagio_files  = $XAGIO_OnedriveClient->GetFileFolder("/drive/root:/xagio:/children?\$filter=startswith(name, '$folder')");

            if (isset($xagio_files['error']) || sizeof(@$xagio_files["value"]) == 0) {
                return array(
                    'status'  => 'info',
                    'message' => 'There are still no backups for this website. Please try again later.'
                );
            } else {
                $xagio_backups = $xagio_files["value"];
                $xagio_output  = array();
                foreach ($xagio_backups as $xagio_backup) {
                    $mystring = $xagio_backup["name"];
                    $pos      = strpos($mystring, $folder);
                    if ($pos === false) {
                        continue;
                    } else {
                        if ($pos == 0) {
                            $xagio_output[] = array(
                                'file' => $xagio_backup["name"],
                                'size' => $xagio_backup['size'],
                                'date' => gmdate('Y-m-d H:i:s', strtotime($xagio_backup["lastModifiedDateTime"])),
                                'id'   => $xagio_backup['id']
                            );
                        } else {
                            continue;
                        }
                    }
                }
                if (sizeof($xagio_output) == 0) {
                    return array(
                        'status'  => 'info',
                        'message' => 'There are still no backups for this website. Please try again later.'
                    );
                } else {
                    usort($xagio_output, function ($a, $b) {
                        if ($a['date'] == $b['date']) {
                            return 0;
                        }
                        return ($a['date'] > $b['date']) ? -1 : 1;
                    });
                    return array(
                        'status'  => 'success',
                        'message' => 'Retrieved backups from OneDrive.',
                        'files'   => $xagio_output
                    );
                }
            }

        }

        private static function getBackups_GoogleDrive($domain)
        {
            $xagio_tokens = self::loadTokens('XAGIO_GoogleDrive');

            // check the token
            $backup_GoogleDriveAccessToken = isset($xagio_tokens["googledrive"]) ? $xagio_tokens["googledrive"] : "";

            // check if empty
            if (empty($backup_GoogleDriveAccessToken)) {
                return array(
                    'status'  => 'error',
                    'message' => 'Missing Google Drive credentials. Please fill your Google Drive credentials on the Settings page.'
                );
            }

            // Initialize the class
            $XAGIO_GoogleDriveClient = new XAGIO_GoogleDrive(
                $backup_GoogleDriveAccessToken
            );

            $folder = self::getName(site_url());
            $xagio_files  = $XAGIO_GoogleDriveClient->ListFiles($folder);

            if (isset($xagio_files['error']) || $xagio_files == false) {
                return array(
                    'status'  => 'info',
                    'message' => 'There are still no backups for this website. Please try again later.'
                );
            } else {
                $xagio_output = array();

                foreach ($xagio_files['items'] as $xagio_file) {
                    $xagio_output[] = array(
                        'file' => $xagio_file['title'],
                        'size' => $xagio_file['fileSize'],
                        'date' => $xagio_file['createdDate'],
                        'id'   => $xagio_file['id']
                    );
                }
                usort($xagio_output, function ($a, $b) {
                    if ($a['date'] == $b['date']) {
                        return 0;
                    }
                    return ($a['date'] > $b['date']) ? -1 : 1;
                });
                return array(
                    'status'  => 'success',
                    'message' => 'Retrieved backups from Google Drive.',
                    'files'   => $xagio_output
                );
            }


        }

        private static function getBackups_AmazonS3($domain)
        {
            $xagio_tokens = self::loadTokens('XAGIO_S3');

            $backup_AmazonAccessKey = isset($xagio_tokens["amazon"]["access_key"]) ? $xagio_tokens["amazon"]["access_key"] : "";
            $backup_AmazonSecretKey = isset($xagio_tokens["amazon"]["secret_key"]) ? $xagio_tokens["amazon"]["secret_key"] : "";
            $backup_AmazonBucket    = isset($xagio_tokens["amazon"]["bucket"]) ? $xagio_tokens["amazon"]["bucket"] : "";
            $backup_AmazonRegion    = isset($xagio_tokens["amazon"]["region"]) ? $xagio_tokens["amazon"]["region"] : "";

            // if any of these is empty, return error
            if (empty($backup_AmazonAccessKey) || empty($backup_AmazonSecretKey) || empty($backup_AmazonBucket) || empty($backup_AmazonRegion)) {
                return array(
                    'status'  => 'error',
                    'message' => 'Missing Amazon S3 credentials. Please fill your Amazon S3 credentials on the Settings page.'
                );
            }

            $S3 = new XAGIO_S3(
                $backup_AmazonAccessKey, $backup_AmazonSecretKey, $backup_AmazonRegion, $backup_AmazonBucket
            );

            $folder   = self::getFolderName($domain);
            $contents = $S3->list_files($folder);

            if (!is_wp_error($contents)) {

                $xagio_output = array();

                foreach ($contents as $xagio_file) {

                    $xagio_name = str_replace($domain . '/', '', $xagio_file['Key']);
                    if (empty($xagio_name))
                        continue;

                    $xagio_output[] = array(
                        'file' => str_replace($domain . '/', '', $xagio_file['Key']),
                        'size' => $xagio_file['Size'],
                        'date' => gmdate('Y-m-d H:i:s', strtotime($xagio_file['LastModified'])),
                        'id'   => $xagio_file['Key']
                    );
                }

                usort($xagio_output, function ($a, $b) {
                    if ($a['date'] == $b['date']) {
                        return 0;
                    }
                    return ($a['date'] > $b['date']) ? -1 : 1;
                });

                return array(
                    'status'  => 'success',
                    'message' => 'Retrieved backups from Amazon S3.',
                    'files'   => $xagio_output
                );

            } else {
                return array(
                    'status'  => 'error',
                    'message' => 'Failed to list files from ' . $bucket[0] . ' Amazon S3 bucket. Please verify that your bucket exists!'
                );
            }


        }


        /*
         *  Utilities
         */
        private static function getFolderName($domain)
        {
            return str_replace(".", "_", $domain);
        }

        public static function loadTokens($class)
        {
            return get_option("XAGIO_BACKUP_SETTINGS");
        }

        public static function loadClasses()
        {
            $class_dir = dirname(__FILE__) . "/../ext/";

            require_once $class_dir . 'XAGIO_DropboxClient.php';
            require_once $class_dir . 'XAGIO_GoogleDrive.php';
            require_once $class_dir . 'XAGIO_OnedriveClient.php';
            require_once $class_dir . 'XAGIO_S3.php';
        }

        private static function checkIfBackupLocationIsSet($xagio_location)
        {

            $xagio_tokens = get_option("XAGIO_BACKUP_SETTINGS");

            if ($xagio_location === "dropbox") {
                if (empty($xagio_tokens["dropbox"]["access_token"])) {
                    return false;
                }
            } else if ($xagio_location === "onedrive") {
                if (empty($xagio_tokens["onedrive"]["access_token"])) {
                    return false;
                }
            } else if ($xagio_location === "googledrive") {
                if (empty($xagio_tokens["googledrive"]['access_token'])) {
                    return false;
                }
            }

            return true;
        }

        public static function saveAmazonS3Settings()
        {
            // amazon_s3_key
            // amazon_s3_secret
            // amazon_s3_bucket
            // amazon_s3_region

            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            // check if AmazonS3 configuration is valid by trying to connect to the bucket
            $backup_AmazonAccessKey = sanitize_text_field(wp_unslash($_POST["amazon_s3_key"]));
            $backup_AmazonSecretKey = sanitize_text_field(wp_unslash($_POST["amazon_s3_secret"]));
            $backup_AmazonBucket    = sanitize_text_field(wp_unslash($_POST["amazon_s3_bucket"]));
            $backup_AmazonRegion    = sanitize_text_field(wp_unslash($_POST["amazon_s3_region"]));

            if (empty($backup_AmazonAccessKey) || empty($backup_AmazonSecretKey) || empty($backup_AmazonBucket) || empty($backup_AmazonRegion)) {
                xagio_json('error', 'Please fill in all the fields.');
            }

            $S3 = new XAGIO_S3(
                $backup_AmazonAccessKey, $backup_AmazonSecretKey, $backup_AmazonRegion, $backup_AmazonBucket
            );

            $domain = wp_parse_url(get_site_url(), PHP_URL_HOST);

            $folder   = self::getFolderName($domain);
            $contents = $S3->list_files($folder);

            if (is_wp_error($contents)) {
                xagio_json('error', 'Failed to connect to Amazon S3. Please check your credentials and try again.');
            }

            $xagio_tokens = get_option("XAGIO_BACKUP_SETTINGS");

            // Update the settings
            $xagio_tokens["amazon"] = [
                "access_key" => $backup_AmazonAccessKey,
                "secret_key" => $backup_AmazonSecretKey,
                "bucket"     => $backup_AmazonBucket,
                "region"     => $backup_AmazonRegion
            ];

            update_option("XAGIO_BACKUP_SETTINGS", $xagio_tokens);

            XAGIO_SYNC::updateBackupSettings();

            xagio_json('success', 'Amazon S3 settings have been saved.');

        }

        public static function saveSettings()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['location'], $_POST["copies"], $_POST["frequency"])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $xagio_location  = sanitize_text_field(wp_unslash($_POST["location"]));
            $xagio_copies    = sanitize_text_field(wp_unslash($_POST["copies"]));
            $xagio_frequency = sanitize_text_field(wp_unslash($_POST["frequency"]));

            if (empty($xagio_location) || empty($xagio_copies) || empty($xagio_frequency)) {
                return;
            }

            if (!self::checkIfBackupLocationIsSet($xagio_location)) {
                xagio_json('error', 'This location is not set up yet. Please set it up first.');
            }

            update_option("XAGIO_BACKUP_LOCATION", $xagio_location);
            update_option("XAGIO_BACKUP_LIMIT", $xagio_copies);
            update_option("XAGIO_BACKUP_DATE", $xagio_frequency);


            if ($xagio_frequency == 'never') {
                $next_timestamp = wp_next_scheduled('xagio_doBackup');
                if ($next_timestamp !== false) {
                    wp_unschedule_event($next_timestamp, 'xagio_doBackup');
                }
            } else {
                if (!wp_next_scheduled('xagio_doBackup')) {
                    wp_schedule_event(time(), $xagio_frequency, 'xagio_doBackup');
                }
            }

            XAGIO_SYNC::updateBackupSettings();

            xagio_json('success', 'Backup settings have been saved.');
        }

        public static function customSchedules($schedules)
        {
            if (!isset($schedules["biweekly"])) {
                $schedules["biweekly"] = [
                    "interval" => 1209600,
                    "display"  => "Once every two weeks",
                ];
            }
            if (!isset($schedules["monthly"])) {
                $schedules["monthly"] = [
                    "interval" => 2419200,
                    "display"  => "Once every month",
                ];
            }
            return $schedules;
        }

        // Function that handles the restoration of backups remotely
        public static function restoreBackupHandler($storage, $xagio_backup, $backup_id)
        {
            // Get the needed AccessTokens
            $xagio_tokens = get_option("XAGIO_BACKUP_SETTINGS");

            if (!function_exists('WP_Filesystem')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            WP_Filesystem();
            global $wp_filesystem;

            // Check whether the folders exist (if not, make them)
            $backupFolder = XAGIO_PATH . "/backups";
            if (!$wp_filesystem->is_dir($backupFolder)) {
                $wp_filesystem->mkdir($backupFolder);
                xagio_file_put_contents($backupFolder . '/index.html', 'Access Denied');
            }

            // Create Backup Name (both folder and file name)
            $siteName = self::getName(site_url());

            // get the real site name
            $realSiteName = self::getName(site_url(), true);

            /* DROPBOX */
            if ($storage === "dropbox") {

                // check the token
                $backup_DropboxAccessToken = isset(
                    $xagio_tokens["dropbox"]["access_token"]
                ) ? $xagio_tokens["dropbox"]["access_token"] : "";

                if (!empty($backup_DropboxAccessToken)) {
                    if (!XAGIO_DROPBOX_KEY || !XAGIO_DROPBOX_SECRET) {
                        return [
                            "status"  => "error",
                            "message" => "Please synchronize your Backup Settings to obtain latest updated settings.",
                        ];
                    }

                    // Initialize the class
                    $XAGIO_DropboxClient = new XAGIO_DropboxClient(
                        $xagio_tokens["dropbox"]
                    );

                    // Try to create a folder
                    $XAGIO_DropboxClient->Download(
                        "/" . $siteName . "/" . $xagio_backup, $backupFolder . "/" . $xagio_backup
                    );
                } else {
                    return [
                        "status"  => "error",
                        "message" => "Please synchronize your Backup Settings to obtain your Dropbox Access Token on " . $realSiteName . " website.",
                    ];
                }
            }
            /* DROPBOX */

            /* ONEDRIVE */
            if ($storage === "onedrive") {

                // check the token
                $backup_OnedriveAccessToken = isset(
                    $xagio_tokens["onedrive"]["access_token"]
                ) ? $xagio_tokens["onedrive"]["access_token"] : "";

                if (!empty($backup_OnedriveAccessToken)) {
                    if (!XAGIO_ONEDRIVE_KEY || !XAGIO_ONEDRIVE_SECRET) {
                        return [
                            "status"  => "error",
                            "message" => "Please synchronize your Backup Settings to obtain latest updated settings.",
                        ];
                    }

                    // Initialize the class
                    $XAGIO_OnedriveClient = new XAGIO_OnedriveClient();

                    $XAGIO_OnedriveClient->SetAccessToken($xagio_tokens["onedrive"]);
                    $XAGIO_OnedriveClient->renewAccessToken();

                    // Try to create a folder
                    $XAGIO_OnedriveClient->downloadCall(
                        "/drive/root:/xagio/" . $xagio_backup . ":/content", $backupFolder . "/" . $xagio_backup
                    );
                } else {
                    return [
                        "status"  => "error",
                        "message" => "Please synchronize your Backup Settings to obtain your OneDrive Access Token on " . $realSiteName . " website.",
                    ];
                }
            }
            /* ONEDRIVE */

            /* XAGIO_GoogleDrive */
            if ($storage === "googledrive") {

                // check the token
                $backup_XAGIO_GoogleDriveAccessToken = isset($xagio_tokens["googledrive"]) ? $xagio_tokens["googledrive"] : "";

                if (!empty($backup_XAGIO_GoogleDriveAccessToken)) {
                    if (!XAGIO_GOOGLEDRIVE_KEY || !XAGIO_GOOGLEDRIVE_SECRET) {
                        return [
                            "status"  => "error",
                            "message" => "Please synchronize your Backup Settings to obtain latest updated settings.",
                        ];
                    }

                    // Initialize the class
                    $XAGIO_GoogleDriveClient = new XAGIO_GoogleDrive(
                        $backup_XAGIO_GoogleDriveAccessToken
                    );

                    // Try to create a folder
                    $XAGIO_GoogleDriveClient->Download(
                        $backup_id, $backupFolder . "/" . $xagio_backup
                    );
                } else {
                    return [
                        "status"  => "error",
                        "message" => "Please synchronize your Backup Settings to obtain your Google Drive Access Token on " . $realSiteName . " website.",
                    ];
                }
            }
            /* XAGIO_GoogleDrive */

            // Check if backup was successful
            if (!file_exists($backupFolder . "/" . $xagio_backup)) {
                return [
                    "status"  => "error",
                    "message" => "Failed to restore the backup! Unable to download it to " . $realSiteName . " website!",
                ];
            }

            // unzip the backup to the root path

            if (!class_exists("ZipArchive")) {
                return [
                    "status"  => "error",
                    "message" => "ZipArchive is not installed.",
                ];
            } else {
                $zip = new xagio_ZipArchiveX();
                $res = $zip->open($backupFolder . "/" . $xagio_backup);
                if ($res === true) {
                    $res = $zip->extractTo(ABSPATH);
                    $zip->close();
                } else {
                    return [
                        "status"  => "error",
                        "message" => "ZipArchive failed to unzip the backup zip file.",
                    ];
                }
            }

            // if we fail to extract the zip, return the error
            if ($res == false) {
                return [
                    "status"  => "error",
                    "message" => "Downloaded but failed to unzip the backup on " . $realSiteName . " website!",
                ];
            }

            // remove the backup zip file
            wp_delete_file($backupFolder . "/" . $xagio_backup);

            // most tricky part, restore MySQL
            $mysql_result = XAGIO_MODEL_BACKUPS::restoreMySQL(
                ABSPATH . "mysql.zip"
            );

            // remove the backup mysql file
            wp_delete_file(ABSPATH . "mysql.zip");

            if ($mysql_result !== true) {
                return $mysql_result;
            }

            return [
                "status"  => "success",
                "message" => "Successfully restored " . $xagio_backup . " on " . $realSiteName . "!",
            ];
        }

        public static function parseSQLStatements($sql)
        {
            $queries    = [];
            $buffer     = '';
            $inString   = false;
            $stringChar = '';
            $escapeNext = false;

            $length = strlen($sql);

            for ($xagio_i = 0; $xagio_i < $length; $xagio_i++) {
                $char     = $sql[$xagio_i];
                $nextChar = ($xagio_i + 1 < $length) ? $sql[$xagio_i + 1] : null;

                // Handle string literals
                if ($inString) {
                    if ($escapeNext) {
                        $escapeNext = false;
                    } elseif ($char === '\\') {
                        $escapeNext = true;
                    } elseif ($char === $stringChar) {
                        $inString = false;
                    }
                    $buffer .= $char;
                    continue;
                }

                // Start of a string literal
                if ($char === '\'' || $char === '"' || $char === '`') {
                    $inString   = true;
                    $stringChar = $char;
                    $buffer     .= $char;
                    continue;
                }

                // Handle comments (single-line and multi-line)
                if ($char === '-' && $nextChar === '-') {
                    // Single-line comment
                    while ($char !== "\n" && $xagio_i < $length) {
                        $char = $sql[++$xagio_i];
                    }
                    $buffer .= "\n";
                    continue;
                } elseif ($char === '/' && $nextChar === '*') {
                    // Multi-line comment
                    $xagio_i += 2; // Skip '/*'
                    while (!($sql[$xagio_i] === '*' && $sql[$xagio_i + 1] === '/') && $xagio_i < $length) {
                        $xagio_i++;
                    }
                    $xagio_i++; // Skip '*/'
                    continue;
                }

                // Statement delimiter
                if ($char === ';') {
                    $queries[] = trim($buffer) . ';';
                    $buffer    = '';
                } else {
                    $buffer .= $char;
                }
            }

            // Add any remaining SQL
            if (trim($buffer) !== '') {
                $queries[] = trim($buffer);
            }

            return $queries;
        }

        // Function that restores MySQL backup
        public static function restoreMySQL($xagio_location, &$log = [])
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            global $wpdb;
            $log["performance"] = [];
            $log["queries"]     = [];

            if (!function_exists('WP_Filesystem')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            WP_Filesystem();
            global $wp_filesystem;

            // Check if the restore folder exists
            $restoreFolder = XAGIO_PATH . "/restore_mysql";
            if ($wp_filesystem->is_dir($restoreFolder)) {
                xagio_removeRecursiveDir($restoreFolder);
            }
            $wp_filesystem->mkdir($restoreFolder);

            // Determine if the backup is a zip file
            $isZip = pathinfo($xagio_location, PATHINFO_EXTENSION) === "zip";

            // If necessary, verify file type
            if (!$isZip && !empty($_FILES["file"]["type"][0])) {
                if ($_FILES["file"]["type"][0] === "application/zip") {
                    $isZip = true;
                }
            }

            if ($isZip) {
                if (!class_exists("ZipArchive")) {
                    return [
                        "status"  => "error",
                        "message" => "ZipArchive is not installed.",
                    ];
                } else {
                    $zip = new xagio_ZipArchiveX();
                    $res = $zip->open($xagio_location);
                    if ($res === true) {
                        $zip->extractTo($restoreFolder);
                        $zip->close();
                    } else {
                        return [
                            "status"  => "error",
                            "message" => "ZipArchive failed to unzip the backup zip file.",
                        ];
                    }
                }
            } else {
                return [
                    "status"  => "error",
                    "message" => "File is not in ZipArchive format.",
                ];
            }

            $errors = [];

            // Improve query performance
            $wpdb->query("SET unique_checks = 0");
            $wpdb->query("SET foreign_key_checks = 0");
            $wpdb->query("SET sql_log_bin = 0");

            $wpdb->last_error = false;

            $listOfFilesAndFolders = glob($restoreFolder . "/*");
            $tables                = [];

            foreach ($listOfFilesAndFolders as $listOfFilesAndFolder) {
                $listOfFilesAndFolder = str_replace(
                    [
                        ".sql"
                    ], "", $listOfFilesAndFolder
                );
                $tables[]             = basename($listOfFilesAndFolder);
            }

            $tables = array_unique($tables);

            sort($tables);

            $log["total_tables"] = sizeof($tables);
            $log["path"]         = $restoreFolder;

            // Restore each table
            foreach ($tables as $table) {
                $start_time = new DateTime();

                $SQL_file = $restoreFolder . "/" . $table . ".sql";

                // Run the SQL file first
                $sql_contents = $wp_filesystem->get_contents($SQL_file);

                if ($sql_contents) {

                    $queries = self::parseSQLStatements($sql_contents);

                    foreach ($queries as $query) {

                        $query = trim($query); // Remove extra whitespace
                        // $wpdb->prepare() treats % as placeholders; this SQL comes from our own backup format.
                        $query = str_replace('%', '%%', $query);
                        if (!empty($query)) { // Ensure the query isn't empty

                            // Send Query
                            /*
                                * Executing SQL statements from a plugin-generated backup file.
                                * These statements are not user input and are required to restore the database dump.
                                * $wpdb->prepare() is intentionally used only to neutralize % placeholders, not to inject values.
                                */
                            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                            $wpdb->query($wpdb->prepare("$query"));

                            if ($wpdb->last_error) {
                                $errors[]         = $query . '... Error: ' . $wpdb->last_error;
                                $wpdb->last_error = false;
                            }
                        }
                    }

                    $end_time = new DateTime();

                    $total_time = $start_time->diff($end_time);
                    $total_time = $total_time->format("%i minutes %s seconds");

                    $log["performance"][$table] = [
                        "time"    => $total_time,
                        "queries" => $wpdb->num_rows,
                        "size"    => $wp_filesystem->exists($SQL_file) ? xagio_filesize($SQL_file) : 0,
                    ];

                }

            }

            // Enable foreign checks
            $wpdb->query("SET unique_checks = 1");
            $wpdb->query("SET foreign_key_checks = 1");
            $wpdb->query("SET sql_log_bin = 1");

            $wpdb->last_error = false;

            $log["errors"] = $errors;

            if (!empty($errors)) {
                return [
                    "status"  => "error",
                    "message" => "MySQL errors occurred.",
                    "errors"  => $errors
                ];
            }

            xagio_removeRecursiveDir($restoreFolder);

            return true;
        }

        public static function restoreBackup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['url'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $xagio_url = sanitize_url(wp_unslash($_POST["url"]));
            if (empty($xagio_url)) {
                xagio_jsonc([
                    "status"  => "error",
                    "message" => "Please provide a valid backup.",
                ]);
            }

            $file_name = basename($xagio_url);
            // check if file exists
            $xagio_file = XAGIO_PATH . "/backups/" . $file_name;
            if (!file_exists($xagio_file)) {
                xagio_jsonc([
                    "status"  => "error",
                    "message" => "Backup file does not exist.",
                ]);
            }

            // Determine the type of backup
            $backupType = pathinfo($xagio_file);
            $backupType = $backupType["filename"];

            // Check if the backup is a full backup
            if (strpos($backupType, "full") !== false) {
                XAGIO_MODEL_BACKUPS::restoreFullBackup($xagio_file);
            } elseif (strpos($backupType, "files") !== false) {
                XAGIO_MODEL_BACKUPS::restoreFileBackup($xagio_file);
            } elseif (strpos($backupType, "mysql") !== false) {
                XAGIO_MODEL_BACKUPS::restoreMySQLBackup($xagio_file);
            }
        }

        public static function restoreFileBackup($preuploaded_file = null)
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $xagio_backup = null;

            if ($preuploaded_file != null) {

                $xagio_backup = $preuploaded_file;

            } else {

                // Include the necessary WordPress file handling functions
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }

                if (!isset($_FILES["file"])) {
                    xagio_jsonc([
                        "status"  => "error",
                        "message" => "Failed to upload file backup file! Make sure that PHP uploads are allowed on your server and maximum upload size is enough to support file backup size.",
                    ]);
                }

                // Check if the restore folder exists
                $restoreFolder = XAGIO_PATH . "/restore_files";
                if (file_exists($restoreFolder)) {
                    xagio_removeRecursiveDir($restoreFolder);
                }

                xagio_mkdir($restoreFolder);

                $upload_overrides = array('test_form' => false);

                // Use wp_handle_upload to manage file uploads
                $uploaded_file = wp_handle_upload($_FILES['file'], $upload_overrides);

                if ($uploaded_file && !isset($uploaded_file['error'])) {
                    $xagio_backup = $uploaded_file['file'];
                } else {
                    // Handle upload error
                    xagio_jsonc([
                        "status"  => "error",
                        "message" => "File upload failed: " . $uploaded_file['error'],
                    ]);
                }

            }

            if (!class_exists("ZipArchive")) {
                xagio_jsonc([
                    "status"  => "error",
                    "message" => "ZipArchive is not installed.",
                ]);
            } else {
                $zip = new xagio_ZipArchiveX();
                $res = $zip->open($xagio_backup);
                if ($res === true) {
                    $zip->extractTo($restoreFolder);
                    $zip->close();
                } else {
                    xagio_jsonc([
                        "status"  => "error",
                        "message" => "ZipArchive failed to unzip the backup zip file.",
                    ]);
                }
            }

            if (!file_exists($restoreFolder . "/index.php")) {
                xagio_jsonc([
                    "status"  => "error",
                    "message" => "Failed to upload file backup. Make sure that you have write permissions on your web root.",
                ]);
            }

            // Copy over the files
            XAGIO_MODEL_CLONE::recurseCopy(
                rtrim($restoreFolder, DIRECTORY_SEPARATOR), rtrim(ABSPATH, DIRECTORY_SEPARATOR)
            );

            // Remove MySQL Backup if any
            wp_delete_file(ABSPATH . "mysql.zip");

            xagio_removeRecursiveDir($restoreFolder);

            xagio_jsonc([
                "status"  => "success",
                "message" => "File backup has been restored.",
            ]);


        }

        public static function restoreMySQLBackup($preuploaded_file = null)
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $start_time = new DateTime();
            $xagio_backup     = null;

            if ($preuploaded_file != null) {

                $xagio_backup = $preuploaded_file;

            } else {

                if (!isset($_FILES["file"])) {
                    xagio_jsonc([
                        "status"  => "error",
                        "message" => "Failed to upload database backup file! Make sure that PHP uploads are allowed on your server and maximum upload size is enough to support database backup size.",
                    ]);
                }

                // Include the necessary WordPress file handling functions
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }

                $upload_overrides = array('test_form' => false);

                // Use wp_handle_upload to manage file uploads
                $uploaded_file = wp_handle_upload($_FILES['file'], $upload_overrides);

                if ($uploaded_file && !isset($uploaded_file['error'])) {
                    $xagio_backup = $uploaded_file['file'];
                } else {
                    // Handle upload error
                    xagio_jsonc([
                        "status"  => "error",
                        "message" => "Failed to upload the file: " . $uploaded_file['error'],
                    ]);
                }

            }


            $file_size = xagio_filesize($xagio_backup);

            // Restore MySQL
            $log          = [];
            $mysql_result = XAGIO_MODEL_BACKUPS::restoreMySQL(
                $xagio_backup, $log
            );

            // Delete the uploaded file after processing
            wp_delete_file($xagio_backup);

            if ($mysql_result !== true) {
                xagio_jsonc($mysql_result);
            }

            wp_logout();

            $end_time = new DateTime();

            $total_time = $start_time->diff($end_time);
            $total_time = $total_time->format("%i minutes %s seconds");

            xagio_jsonc([
                "status"  => "success",
                "message" => "Database backup has been restored. It took $total_time to import $file_size of data into the database. Refreshing this page...",
                "log"     => $log,
            ]);

        }

        public static function restoreFullBackup($preuploaded_file = null)
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $xagio_backup = null;

            if ($preuploaded_file != null) {

                $xagio_backup = $preuploaded_file;

            } else {

                if (!isset($_FILES["file"])) {
                    xagio_jsonc([
                        "status"  => "error",
                        "message" => "Failed to upload backup file! Make sure that PHP uploads are allowed on your server and maximum upload size is enough to support backup size.",
                    ]);
                }

                // Include the necessary WordPress file handling functions
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }

                $upload_overrides = array('test_form' => false);

                // Use wp_handle_upload to manage file uploads
                $uploaded_file = wp_handle_upload($_FILES['file'], $upload_overrides);

                if ($uploaded_file && !isset($uploaded_file['error'])) {
                    $xagio_backup = $uploaded_file['file'];

                } else {
                    // Handle upload error
                    xagio_jsonc([
                        "status"  => "error",
                        "message" => "Failed to upload the file: " . $uploaded_file['error'],
                    ]);
                }

            }

            // Check if the restore folder exists
            $restoreFolder = XAGIO_PATH . "/restore_full";
            if (file_exists($restoreFolder)) {
                xagio_removeRecursiveDir($restoreFolder);
            }
            xagio_mkdir($restoreFolder);

            // Unzip the backup to the restore folder
            if (!class_exists("ZipArchive")) {
                xagio_jsonc([
                    "status"  => "error",
                    "message" => "ZipArchive is not installed.",
                ]);
            } else {
                $zip = new xagio_ZipArchiveX();
                $res = $zip->open($xagio_backup);
                if ($res === true) {
                    $res = $zip->extractTo($restoreFolder);
                    $zip->close();
                } else {
                    xagio_jsonc([
                        "status"  => "error",
                        "message" => "ZipArchive failed to unzip the backup zip file.",
                    ]);
                }
            }

            // If we fail to extract the zip, return the error
            if ($res == false) {
                xagio_jsonc([
                    "status"  => "error",
                    "message" => "Uploaded but failed to unzip the backup! Make sure that file is not damaged and all backup related files are inside!",
                ]);
            }

            // Check the file structure
            $core_files = [
                "wp-content",
                "mysql.zip",
                "wp-config.php"
            ];

            foreach ($core_files as $core_file) {
                if (!file_exists($restoreFolder . "/" . $core_file)) {
                    xagio_jsonc([
                        "status"  => "error",
                        "message" => "Backup archive is damaged! Backup core file << " . $core_file . " >> is missing from this archive. Either add it back in, or use a different valid backup archive!",
                    ]);
                }
            }

            // Copy over the files
            XAGIO_MODEL_CLONE::recurseCopy(
                rtrim($restoreFolder, DIRECTORY_SEPARATOR), rtrim(ABSPATH, DIRECTORY_SEPARATOR)
            );

            // Restore MySQL
            $mysql_result = XAGIO_MODEL_BACKUPS::restoreMySQL(
                ABSPATH . "mysql.zip"
            );
            wp_delete_file(ABSPATH . "mysql.zip");

            // Remove files
            xagio_removeRecursiveDir($restoreFolder);

            if ($mysql_result !== true) {
                xagio_jsonc($mysql_result);
            }

            wp_logout();

            xagio_jsonc([
                "status"  => "success",
                "message" => "Successfully restored full backup! Refreshing this page...",
            ]);

        }

        // Function that truncates backups based on a set limit
        public static function trimBackups()
        {
            try {
                $limit = get_option("XAGIO_BACKUP_LIMIT");
                if (!$limit || empty($limit)) {
                    $limit = 5;
                    update_option("XAGIO_BACKUP_LIMIT", $limit);
                }

                // Get the preferred backup location
                $backupLocation = get_option("XAGIO_BACKUP_LOCATION");

                // Get the needed AccessTokens
                $xagio_tokens = get_option("XAGIO_BACKUP_SETTINGS");

                // Domain folder name
                $folder = self::getName(site_url());

                /* DROPBOX */
                if ($backupLocation === "dropbox") {

                    // check the token
                    $backup_DropboxAccessToken = isset(
                        $xagio_tokens["dropbox"]["access_token"]
                    ) ? $xagio_tokens["dropbox"]["access_token"] : "";

                    if (!empty($backup_DropboxAccessToken)) {
                        if (!XAGIO_DROPBOX_KEY || !XAGIO_DROPBOX_SECRET) {
                            return false;
                        }

                        // Initialize the class
                        $XAGIO_DropboxClient = new XAGIO_DropboxClient(
                            $xagio_tokens["dropbox"]
                        );

                        $xagio_files = $XAGIO_DropboxClient->ListFolder($folder);
                        if ($xagio_files !== false) {
                            $xagio_output = [];
                            foreach ($xagio_files["entries"] as $xagio_file) {
                                if ($xagio_file[".tag"] == "file" && strpos($xagio_file["name"], $folder) !== false) {
                                    $xagio_output[] = [
                                        "file" => $xagio_file["name"],
                                        "size" => $xagio_file["size"],
                                        "date" => $xagio_file["client_modified"],
                                        "id"   => $xagio_file["id"],
                                    ];
                                }
                            }
                            usort($xagio_output, function ($a, $b) {
                                if ($a["date"] == $b["date"]) {
                                    return false;
                                }
                                return $a["date"] > $b["date"] ? -1 : 1;
                            });

                            if (sizeof($xagio_output) > $limit) {
                                $trimBy            = sizeof($xagio_output) - $limit;
                                $backupsForRemoval = array_slice(
                                    $xagio_output, -$trimBy, $trimBy, true
                                );

                                // Remove backups
                                foreach ($backupsForRemoval as $xagio_backup) {
                                    $XAGIO_DropboxClient->Delete(
                                        "/" . $folder . "/" . $xagio_backup["file"]
                                    );
                                }
                            }
                            return true;
                        }
                    }
                }
                /* DROPBOX */

                /* ONEDRIVE */
                if ($backupLocation === "onedrive") {

                    // check the token
                    $backup_OnedriveAccessToken = isset(
                        $xagio_tokens["onedrive"]["access_token"]
                    ) ? $xagio_tokens["onedrive"]["access_token"] : "";

                    if (!empty($backup_OnedriveAccessToken)) {
                        if (!XAGIO_ONEDRIVE_KEY || !XAGIO_ONEDRIVE_SECRET) {
                            return false;
                        }

                        // Initialize the class
                        $XAGIO_OnedriveClient = new XAGIO_OnedriveClient();

                        $XAGIO_OnedriveClient->SetAccessToken($xagio_tokens["onedrive"]);
                        $XAGIO_OnedriveClient->renewAccessToken();

                        $xagio_files = $XAGIO_OnedriveClient->GetFileFolder(
                            "/drive/root:/xagio:/children"
                        );

                        // Find all backups
                        if (sizeof($xagio_files["value"]) !== 0) {
                            $xagio_backups = $xagio_files["value"];
                            $xagio_output  = [];
                            foreach ($xagio_backups as $xagio_backup) {
                                $mystring = $xagio_backup["name"];
                                $pos      = strpos($mystring, $folder);
                                if ($pos === false) {
                                    continue;
                                } else {
                                    if ($pos == 0) {
                                        $xagio_output[] = [
                                            "file" => $xagio_backup["name"],
                                            "size" => $xagio_backup["size"],
                                            "date" => gmdate(
                                                "Y-m-d H:i:s", strtotime(
                                                    $xagio_backup["lastModifiedDateTime"]
                                                )
                                            ),
                                            "id"   => $xagio_backup["id"],
                                        ];
                                    } else {
                                        continue;
                                    }
                                }
                            }
                            if (sizeof($xagio_output) !== 0) {
                                usort($xagio_output, function ($a, $b) {
                                    if ($a["date"] == $b["date"]) {
                                        return 0;
                                    }
                                    return $a["date"] > $b["date"] ? -1 : 1;
                                });

                                if (sizeof($xagio_output) > $limit) {
                                    $trimBy            = sizeof($xagio_output) - $limit;
                                    $backupsForRemoval = array_slice(
                                        $xagio_output, -$trimBy, $trimBy, true
                                    );

                                    // Remove backups
                                    foreach ($backupsForRemoval as $xagio_backup) {
                                        $XAGIO_OnedriveClient->deleteCall(
                                            "/drive/root:/xagio/" . $xagio_backup["file"]
                                        );
                                    }
                                }
                                return true;
                            }
                        }
                    }
                }
                /* ONEDRIVE */

                /* XAGIO_GoogleDrive */
                if ($backupLocation === "googledrive") {

                    // check the token
                    $backup_XAGIO_GoogleDriveAccessToken = isset($xagio_tokens["googledrive"]) ? $xagio_tokens["googledrive"] : "";

                    if (!empty($backup_XAGIO_GoogleDriveAccessToken)) {
                        if (!XAGIO_GOOGLEDRIVE_KEY || !XAGIO_GOOGLEDRIVE_SECRET) {
                            return false;
                        }

                        // Initialize the class
                        $XAGIO_GoogleDriveClient = new XAGIO_GoogleDrive(
                            $backup_XAGIO_GoogleDriveAccessToken
                        );

                        $xagio_files = $XAGIO_GoogleDriveClient->ListFiles($folder);

                        if ($xagio_files !== false) {
                            $xagio_output = [];

                            foreach ($xagio_files["items"] as $xagio_file) {
                                $xagio_output[] = [
                                    "file" => $xagio_file["title"],
                                    "size" => $xagio_file["fileSize"],
                                    "date" => $xagio_file["createdDate"],
                                    "id"   => $xagio_file["id"],
                                ];
                            }
                            usort($xagio_output, function ($a, $b) {
                                if ($a["date"] == $b["date"]) {
                                    return 0;
                                }
                                return $a["date"] > $b["date"] ? -1 : 1;
                            });


                            if (sizeof($xagio_output) > $limit) {
                                $trimBy            = sizeof($xagio_output) - $limit;
                                $backupsForRemoval = array_slice(
                                    $xagio_output, -$trimBy, $trimBy, true
                                );

                                // Remove backups
                                foreach ($backupsForRemoval as $xagio_backup) {
                                    $XAGIO_GoogleDriveClient->Delete($xagio_backup["id"]);
                                }
                            }
                            return true;
                        }
                    }
                }
                /* XAGIO_GoogleDrive */

                /* AMAZON S3 */
                if ($backupLocation === "amazons3") {

                    $backup_AmazonAccessKey = isset($xagio_tokens["amazon"]["access_key"]) ? $xagio_tokens["amazon"]["access_key"] : "";
                    $backup_AmazonSecretKey = isset($xagio_tokens["amazon"]["secret_key"]) ? $xagio_tokens["amazon"]["secret_key"] : "";
                    $backup_AmazonBucket    = isset($xagio_tokens["amazon"]["bucket"]) ? $xagio_tokens["amazon"]["bucket"] : "";
                    $backup_AmazonRegion    = isset($xagio_tokens["amazon"]["region"]) ? $xagio_tokens["amazon"]["region"] : "";

                    if (!empty($backup_AmazonAccessKey) && !empty($backup_AmazonSecretKey) && !empty($backup_AmazonRegion) && !empty($backup_AmazonBucket)) {

                        $S3 = new XAGIO_S3(
                            $backup_AmazonAccessKey, $backup_AmazonSecretKey, $backup_AmazonRegion, $backup_AmazonBucket
                        );

                        $domain   = wp_parse_url(get_site_url(), PHP_URL_HOST);
                        $folder   = self::getFolderName($domain);
                        $contents = $S3->list_files($folder);

                        if (!is_wp_error($contents)) {
                            $xagio_output = [];

                            foreach ($contents as $xagio_file) {
                                $xagio_name = str_replace(
                                    $folder . "/", "", $xagio_file["Key"]
                                );
                                if (empty($xagio_name))
                                    continue;
                                if (strpos($xagio_file["Key"], $folder) !== false) {
                                    $xagio_output[] = [
                                        "file" => $xagio_name,
                                        "size" => $xagio_file["Size"],
                                        "date" => gmdate(
                                            "Y-m-d H:i:s", strtotime($xagio_file['LastModified'])
                                        ),
                                        "id"   => $xagio_file["Key"],
                                    ];
                                }
                            }

                            usort($xagio_output, function ($a, $b) {
                                if ($a["date"] == $b["date"]) {
                                    return 0;
                                }
                                return $a["date"] > $b["date"] ? -1 : 1;
                            });

                            if (sizeof($xagio_output) > $limit) {
                                $trimBy            = sizeof($xagio_output) - $limit;
                                $backupsForRemoval = array_slice(
                                    $xagio_output, -$trimBy, $trimBy, true
                                );

                                // Remove backups
                                foreach ($backupsForRemoval as $xagio_backup) {
                                    $S3->remove_file($xagio_backup["id"]);
                                }
                            }
                        }
                    }
                }
                /* AMAZON S3 */

                return false;
            } catch (Exception $ex) {
                return false;
            }

        }

        private static function excludeFromFiles($listOfFiles)
        {
            if (XAGIO_BACKUPS_IGNORE_DOMAINS == true) {
                $domains  = [];
                $new_list = [];
                foreach ($listOfFiles as $xagio_file) {
                    if (is_dir($xagio_file)) {
                        $test = str_replace(ABSPATH, "", $xagio_file);
                        if (preg_match(
                            "/(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]/", $test
                        )) {
                            $domains[] = $test;
                        } else {
                            $new_list[] = $xagio_file;
                        }
                    } else {
                        foreach ($domains as $domain) {
                            if (strpos($xagio_file, $domain) !== false) {
                                continue 2;
                            }
                        }

                        $new_list[] = $xagio_file;
                    }
                }

                return $new_list;
            } else {
                return $listOfFiles;
            }
        }

        public static function createBackup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['type']) || !isset($_POST["destination"])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $backup_type        = sanitize_text_field(wp_unslash($_POST["type"]));
            $backup_destination = sanitize_text_field(wp_unslash($_POST["destination"]));

            $xagio_output = null;

            if ($backup_destination == "local") {
                $xagio_output = self::doCloneBackup($backup_type);
            } else {
                if (!self::checkIfBackupLocationIsSet($backup_destination)) {
                    xagio_json('error', 'This location is not set up yet. Please set it up first.');
                }

                // Change backup location temporarily
                $backupLocation = get_option("XAGIO_BACKUP_LOCATION");
                update_option("XAGIO_BACKUP_LOCATION", $backup_destination);
                $_POST['create_id'] = 0;
                $xagio_output             = self::doBackup($backup_type);
                update_option("XAGIO_BACKUP_LOCATION", $backupLocation);
            }

            xagio_jsonc($xagio_output);
        }

        public static function removeBackup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['name'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $xagio_name         = sanitize_text_field(wp_unslash($_POST["name"]));
            $backupFolder = XAGIO_PATH . "/backups/";
            if (file_exists($backupFolder . $xagio_name)) {
                wp_delete_file($backupFolder . $xagio_name);
            }
        }

        public static function doCloneBackup($type = "full")
        {
            // Check whether the folders exist (if not, make them)
            $backupFolder = XAGIO_PATH . "/backups";

            if (!file_exists($backupFolder)) {
                xagio_mkdir($backupFolder);
                xagio_file_put_contents($backupFolder . '/index.html', 'Access Denied');
            }

            // WordPress Directory
            $wordPressHomeDir = ABSPATH;

            $backupName = wp_parse_url(get_site_url());
            $backupName = strtolower($backupName["host"]);
            $backupName = str_replace(".", "_", $backupName) . "_" . $type . "_" . gmdate("H_i_s__Y_m_d");

            // Append a short secure random string to make it unique
            $randomSuffix = hash('sha256', bin2hex(random_bytes(4))); // 8 hex characters
            $backupName   .= "_" . $randomSuffix;

            // Backup ZIP File
            $backupFile = $backupFolder . DIRECTORY_SEPARATOR . $backupName . ".zip";

            // Backup SQL File
            $backupSQL = $wordPressHomeDir . "mysql.zip";

            if (file_exists($backupSQL)) {
                wp_delete_file($backupSQL);
            }
            // Start the MySQL backup process
            if ($type == "mysql") {

                $xagio_output = self::doBackupMySQL($backupFile);
                if (!$xagio_output) {
                    return [
                        "status"  => "error",
                        "message" => "Failed to create backup. Make sure you have enough space on the disk, or write permissions."
                    ];
                }

                return [
                    "status"  => "success",
                    "message" => "Successfully created database backup.",
                    "data"    => content_url(
                        DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR . "xagio-seo" . DIRECTORY_SEPARATOR . "backups" . DIRECTORY_SEPARATOR . basename($backupFile)
                    ),
                ];
            } elseif ($type == "full") {

                $xagio_output = self::doBackupMySQL($backupSQL);
                if (!$xagio_output) {
                    return [
                        "status"  => "error",
                        "message" => "Failed to create backup. Make sure you have enough space on the disk, or write permissions."
                    ];
                }

            }

            if ($type == "full" || $type == "files") {
                $listOfFilesAndFolders = self::excludeFromFiles(
                    glob($wordPressHomeDir . "*")
                );
            } else {
                $listOfFilesAndFolders = [$backupSQL];
            }

            if (!class_exists("ZipArchive")) {
                return [
                    "status"  => "error",
                    "message" => "ZipArchive is not installed.",
                ];
            } else {
                // Start the ZIP creation process
                $archive = new xagio_ZipArchiveX();
                if ($archive->open(
                        $backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE
                    ) !== true) {
                    return [
                        "status"  => "error",
                        "message" => "Cannot create zip archive! Make sure that you have write permissions!",
                    ];
                }

                // Zip the whole thing
                $archive->pack($listOfFilesAndFolders, $wordPressHomeDir);

                $archive->close();
            }

            // Check if the zipping has been successful
            if (!file_exists($backupFile)) {
                return [
                    "status"  => "error",
                    "message" => "Failed to create a backup. Could not zip the files.",
                ];
            }

            // Remote the MySQL backup from Root Dir (VERY IMPORTANT)
            wp_delete_file($backupSQL);

            return [
                "status"  => "success",
                "message" => "Successfully created backup.",
                "data"    => content_url(
                    DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR . "xagio-seo" . DIRECTORY_SEPARATOR . "backups" . DIRECTORY_SEPARATOR . basename($backupFile)
                ),
            ];
        }

        // Function that handles the overall backup process
        public static function doBackup($type = "full")
        {
            try {
                // Update keys first
                XAGIO_SYNC::getBackupSettings();

                $createID       = isset($_POST['create_id']) ? intval($_POST["create_id"]) : 0;
                $backupLocation = get_option("XAGIO_BACKUP_LOCATION");
                $xagio_tokens         = get_option("XAGIO_BACKUP_SETTINGS");

                // Check if tokens and location are set
                if (empty($xagio_tokens) || empty($backupLocation) || $backupLocation == "none") {
                    $message = "Please set your Storage Method and credentials for the preferred method.";
                    return self::handleOutput($createID, 'error', $message);
                }

                // Check and create backup folder if necessary
                $backupFolder = XAGIO_PATH . "/backups";
                if (!file_exists($backupFolder)) {
                    xagio_mkdir($backupFolder);
                    xagio_file_put_contents($backupFolder . '/index.html', 'Access Denied');
                }

                // WordPress Directory and Backup Names
                $wordPressHomeDir = ABSPATH;
                $siteName         = self::getName(site_url());
                $backupName       = $siteName . "_backup_{$type}_" . gmdate("m-d-Y_H-i");

                // Append a short secure random string to make it unique
                $randomSuffix = hash('sha256', bin2hex(random_bytes(4))); // 8 hex characters
                $backupName   .= "_" . $randomSuffix;

                $backupFile = $backupFolder . DIRECTORY_SEPARATOR . $backupName . ".zip";
                $backupSQL  = $wordPressHomeDir . "mysql.zip";

                if ($type == "full" || $type == "mysql") {

                    if (file_exists($backupSQL)) {
                        wp_delete_file($backupSQL);
                    }

                    // Start the MySQL backup process
                    $xagio_output = self::doBackupMySQL($backupSQL);
                    if (!$xagio_output) {
                        return self::handleOutput($createID, "error", "Failed to create database backup. Make sure you have enough space on the disk, or write permissions.");
                    }

                }

                if ($type == "full" || $type == "files") {
                    $listOfFilesAndFolders = self::excludeFromFiles(glob($wordPressHomeDir . "*"));

                    if (!class_exists("xagio_ZipArchiveX")) {
                        return self::handleOutput($createID, "error", "ZipArchive is not installed.");
                    }

                    $archive = new xagio_ZipArchiveX();
                    if ($archive->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                        return self::handleOutput($createID, "error", "Cannot create zip archive! Make sure that you have write permissions!");
                    }

                    $archive->pack($listOfFilesAndFolders, $wordPressHomeDir);
                    $archive->close();

                } else if ($type == "mysql") {

                    xagio_rename($backupSQL, $backupFile);

                }


                if (!file_exists($backupFile)) {
                    return self::handleOutput($createID, "error", "Failed to create a backup. Could not zip the files.");
                }

                if ($type == "full" || $type == "mysql") {
                    // Remove the MySQL backup from Root Dir
                    wp_delete_file($backupSQL);
                }

                // Start uploading the backup to the remote server
                $upload_status = self::uploadToRemoteServer($backupLocation, $xagio_tokens, $backupFile, $siteName, $createID);

                // Check if upload was successful
                if (is_string($upload_status)) {
                    return self::handleOutput($createID, 'error', $upload_status);
                }

                // Trim backups
                self::trimBackups();

                return $upload_status;

            } catch (Exception $ex) {

                return self::handleOutput($createID, 'error', 'Backup failed. Error: ' . $ex->getMessage());

            }
        }

        public static function handleOutput($createID, $status, $message)
        {
            $domain = wp_parse_url(site_url(), PHP_URL_HOST);
            $data   = [
                "status"  => $status,
                "message" => $message,
                "domain"  => $domain
            ];

            if ($createID != "0") {
                $data["create_id"] = $createID;
            }

            XAGIO_API::apiRequest("backups", "POST", $data);

            return [
                "status"  => $status,
                "message" => $message
            ];
        }

        private static function uploadToRemoteServer($backupLocation, $xagio_tokens, $backupFile, $siteName, $createID)
        {
            switch ($backupLocation) {
                case "dropbox":
                    return self::uploadToDropbox($xagio_tokens, $backupFile, $siteName, $createID);
                case "onedrive":
                    return self::uploadToOnedrive($xagio_tokens, $backupFile, $siteName, $createID);
                case "googledrive":
                    return self::uploadToGoogleDrive($xagio_tokens, $backupFile, $siteName, $createID);
                case "amazons3":
                    return self::uploadToAmazonS3($xagio_tokens, $backupFile, $siteName, $createID);
                default:
                    return self::handleOutput($createID, "error", "Unsupported backup location.");
            }
        }

        private static function uploadToAmazonS3($xagio_tokens, $backupFile, $siteName, $createID)
        {
            $backup_AmazonAccessKey = $xagio_tokens["amazon"]["access_key"] ?? "";
            $backup_AmazonSecretKey = $xagio_tokens["amazon"]["secret_key"] ?? "";
            $backup_AmazonBucket    = $xagio_tokens["amazon"]["bucket"] ?? "";
            $backup_AmazonRegion    = $xagio_tokens["amazon"]["region"] ?? "";

            if (!empty($backup_AmazonAccessKey) && !empty($backup_AmazonSecretKey) && !empty($backup_AmazonBucket) && !empty($backup_AmazonRegion)) {

                $S3 = new XAGIO_S3(
                    $backup_AmazonAccessKey, $backup_AmazonSecretKey, $backup_AmazonRegion, $backup_AmazonBucket
                );

                $folder       = self::getFolderName($siteName);
                $amazonOutput = $S3->upload($backupFile, $folder . '/' . basename($backupFile), $createID);

                if (is_wp_error($amazonOutput)) {
                    return $amazonOutput->get_error_message();
                }

                return [
                    'status'  => 'success',
                    'message' => 'Local Backup is created and enqueued to be uploaded to Amazon S3, please check back later.'
                ];

            } else {
                return "Amazon S3 credentials are invalid. Make sure your website is synchronized, or try to reauthorize Amazon S3 on Xagio Cloud.";
            }
        }

        private static function uploadToDropbox($xagio_tokens, $backupFile, $siteName, $createID)
        {
            $backup_DropboxAccessToken = $xagio_tokens["dropbox"] ?? "";

            if (!empty($backup_DropboxAccessToken) && XAGIO_DROPBOX_KEY && XAGIO_DROPBOX_SECRET) {
                $XAGIO_DropboxClient = new XAGIO_DropboxClient($backup_DropboxAccessToken);

                $XAGIO_DropboxClient->CreateFolder($siteName);
                $dropboxOutput = $XAGIO_DropboxClient->upload($backupFile, "/" . $siteName, $createID);

                if (isset($dropboxOutput["error"])) {
                    return "Failed to upload backup to Dropbox. Info: " . $dropboxOutput["error"];
                }

                return [
                    'status'  => 'success',
                    'message' => 'Local Backup is created and enqueued to be uploaded to DropBox, please check back later.'
                ];

            } else {
                return "Dropbox credentials are invalid. Make sure your website is synchronized, or try to reauthorize Dropbox on Xagio Cloud.";
            }
        }

        private static function uploadToOnedrive($xagio_tokens, $backupFile, $siteName, $createID)
        {
            $backup_OnedriveAccessToken = $xagio_tokens["onedrive"] ?? "";

            if (!empty($backup_OnedriveAccessToken) && XAGIO_ONEDRIVE_KEY && XAGIO_ONEDRIVE_SECRET) {
                $XAGIO_OnedriveClient = new XAGIO_OnedriveClient();

                $XAGIO_OnedriveClient->SetAccessToken($backup_OnedriveAccessToken);
                $XAGIO_OnedriveClient->renewAccessToken();

                $XAGIO_OnedriveClient->CreateFolder();
                $onedriveOutput = $XAGIO_OnedriveClient->upload($backupFile, $createID);

                if (isset($onedriveOutput["error"])) {
                    return "Failed to upload backup to OneDrive. Info: " . $onedriveOutput["error"];
                }

                return [
                    'status'  => 'success',
                    'message' => 'Local Backup is created and enqueued to be uploaded to OneDrive, please check back later.'
                ];

            } else {
                return "OneDrive credentials are invalid. Make sure your website is synchronized, or try to reauthorize OneDrive on Xagio Cloud.";
            }
        }

        private static function uploadToGoogleDrive($xagio_tokens, $backupFile, $siteName, $createID)
        {
            $backup_XAGIO_GoogleDriveAccessToken = $xagio_tokens["googledrive"] ?? "";

            if (!empty($backup_XAGIO_GoogleDriveAccessToken) && XAGIO_GOOGLEDRIVE_KEY && XAGIO_GOOGLEDRIVE_SECRET) {
                $XAGIO_GoogleDriveClient = new XAGIO_GoogleDrive($backup_XAGIO_GoogleDriveAccessToken);

                $XAGIO_GoogleDriveClient->CreateFolder("Xagio");
                $XAGIO_GoogleDriveClient->CreateFolder($siteName, "Xagio");
                $XAGIO_GoogleDriveOutput = $XAGIO_GoogleDriveClient->upload($backupFile, $siteName, $createID);

                if (isset($XAGIO_GoogleDriveOutput["error"])) {
                    return "Failed to upload backup to Google Drive. Info: " . $XAGIO_GoogleDriveOutput["error"];
                }

                return [
                    'status'  => 'success',
                    'message' => 'Local Backup is created and enqueued to be uploaded to GoogleDrive, please check back later.'
                ];

            } else {
                return "GoogleDrive credentials are invalid. Make sure your website is synchronized, or try to reauthorize GoogleDrive on Xagio Cloud.";
            }
        }

        // Function that handle MySQL backup
        public static function doBackupMySQL($xagio_location)
        {
            if (!xagio_is_writable(dirname($xagio_location))) {
                return false;
            }

            global $wpdb, $wp_filesystem;

            // Initialize WP_Filesystem if not already done
            if (empty($wp_filesystem)) {
                require_once ABSPATH . '/wp-admin/includes/file.php';
                WP_Filesystem();
            }

            // Create the folder for storing MySQL backup
            $backupFolder = XAGIO_PATH . "/backups_mysql";

            if ($wp_filesystem->exists($backupFolder)) {
                xagio_removeRecursiveDir($backupFolder);
            }

            xagio_mkdir($backupFolder);

            // Get all tables in the current database
            $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);

            $xagio_i = 0;

            foreach ($tables as $table) {
                $table  = $table[0];
                $i_file = str_pad($xagio_i, 6, "0", STR_PAD_LEFT);

                // Create table structure file
                $tableDump   = "DROP TABLE IF EXISTS `{$table}`;\n";
                $createTable = $wpdb->get_row("SHOW CREATE TABLE `$table`", ARRAY_N);
                $tableDump   .= $createTable[1] . ";\n\n";

                $wp_filesystem->put_contents(
                    $backupFolder . "/" . $i_file . "_" . $table . ".sql", $tableDump, 0777
                );

                // Create data file and handle rows in batches
                $sqlFilePath = $backupFolder . "/" . $i_file . "_" . $table . "_data.sql";
                $batchSize   = 1000; // Adjust based on your needs
                $offset      = 0;

                // Initialize the file using wp_filesystem
                $wp_filesystem->put_contents($sqlFilePath, '', 0777);

                // Open the file for appending using call_user_func_array
                $fileHandle = call_user_func_array('fopen', array(
                    $sqlFilePath,
                    'a'
                ));

                if (!$fileHandle) {
                    return false;
                }

                while (true) {
                    // Get a batch of rows
                    $rows = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM `$table` LIMIT %d OFFSET %d", $batchSize, $offset
                        ), ARRAY_A
                    );

                    if (empty($rows)) {
                        break;
                    }

                    $insertData = '';
                    foreach ($rows as $row) {
                        $values = array_map(function ($xagio_value) use ($wpdb) {
                            return isset($xagio_value) ? "'" . addslashes($xagio_value) . "'" : "NULL";
                        }, array_values($row));

                        $insertData .= "INSERT INTO `$table` VALUES (" . implode(", ", $values) . ");\n";

                        // Write to file when batch reaches 1MB to prevent memory buildup
                        if (strlen($insertData) > 1024 * 1024) {
                            call_user_func_array('fwrite', array(
                                $fileHandle,
                                $insertData
                            ));
                            $insertData = '';
                        }
                    }

                    // Write any remaining data
                    if (!empty($insertData)) {
                        call_user_func_array('fwrite', array(
                            $fileHandle,
                            $insertData
                        ));
                    }

                    $offset += $batchSize;
                }

                // Close the file handle
                call_user_func_array('fclose', array($fileHandle));
                $xagio_i++;
            }

            $listOfFilesAndFolders = glob($backupFolder . "/*");

            // Zip the SQL/CSV files
            if (!class_exists("ZipArchive")) {
                return false;
            } else {
                // Start the ZIP creation process
                $archive = new xagio_ZipArchiveX();
                if ($archive->open(
                        $xagio_location, ZipArchive::CREATE | ZipArchive::OVERWRITE
                    ) !== true) {
                    return false;
                }

                // Zip the whole thing
                $archive->pack($listOfFilesAndFolders, $backupFolder);
                if (!$archive->close()) {
                    return false;
                }
            }

            xagio_removeRecursiveDir($backupFolder);

            return $xagio_location;
        }

        // Functions that handle SQL files
        public static function remove_comments(&$xagio_output)
        {
            $lines  = explode("\n", $xagio_output);
            $xagio_output = "";

            // try to keep mem. use down
            $linecount = count($lines);

            $in_comment = false;
            for ($xagio_i = 0; $xagio_i < $linecount; $xagio_i++) {
                if (preg_match("/^\/\*/", preg_quote($lines[$xagio_i]))) {
                    $in_comment = true;
                }

                if (!$in_comment) {
                    $xagio_output .= $lines[$xagio_i] . "\n";
                }

                if (preg_match("/\*\/$/", preg_quote($lines[$xagio_i]))) {
                    $in_comment = false;
                }
            }

            unset($lines);
            return $xagio_output;
        }

        public static function remove_remarks($sql)
        {
            $lines = explode("\n", $sql);

            // try to keep mem. use down
            $sql = "";

            $linecount = count($lines);
            $xagio_output    = "";

            for ($xagio_i = 0; $xagio_i < $linecount; $xagio_i++) {
                if ($xagio_i != $linecount - 1 || strlen($lines[$xagio_i]) > 0) {
                    if (isset($lines[$xagio_i][0]) && $lines[$xagio_i][0] != "#") {
                        $xagio_output .= $lines[$xagio_i] . "\n";
                    } else {
                        $xagio_output .= "\n";
                    }
                    // Trading a bit of speed for lower mem. use here.
                    $lines[$xagio_i] = "";
                }
            }

            return $xagio_output;
        }

        public static function split_sql_file($sql, $delimiter)
        {
            // Split up our string into "possible" SQL statements.
            $xagio_tokens = explode($delimiter, $sql);

            // try to save mem.
            $sql    = "";
            $xagio_output = [];

            // we don't actually care about the matches preg gives us.
            $matches = [];

            // this is faster than calling count($oktens) every time thru the loop.
            $token_count = count($xagio_tokens);
            for ($xagio_i = 0; $xagio_i < $token_count; $xagio_i++) {
                // Don't wanna add an empty string as the last thing in the array.
                if ($xagio_i != $token_count - 1 || strlen($xagio_tokens[$xagio_i] > 0)) {
                    // This is the total number of single quotes in the token.
                    $total_quotes = preg_match_all(
                        "/'/", $xagio_tokens[$xagio_i], $matches
                    );
                    // Counts single quotes that are preceded by an odd number of backslashes,
                    // which means they're escaped quotes.
                    $escaped_quotes = preg_match_all(
                        "/(?<!\\\\)(\\\\\\\\)*\\\\'/", $xagio_tokens[$xagio_i], $matches
                    );

                    $unescaped_quotes = $total_quotes - $escaped_quotes;

                    // If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
                    if ($unescaped_quotes % 2 == 0) {
                        // It's a complete sql statement.
                        $xagio_output[] = $xagio_tokens[$xagio_i];
                        // save memory.
                        $xagio_tokens[$xagio_i] = "";
                    } else {
                        // incomplete sql statement. keep adding tokens until we have a complete one.
                        // $temp will hold what we have so far.
                        $temp = $xagio_tokens[$xagio_i] . $delimiter;
                        // save memory..
                        $xagio_tokens[$xagio_i] = "";

                        // Do we have a complete statement yet?
                        $complete_stmt = false;

                        for ($j = $xagio_i + 1; !$complete_stmt && $j < $token_count; $j++) {
                            // This is the total number of single quotes in the token.
                            $total_quotes = preg_match_all(
                                "/'/", $xagio_tokens[$j], $matches
                            );
                            // Counts single quotes that are preceded by an odd number of backslashes,
                            // which means they're escaped quotes.
                            $escaped_quotes = preg_match_all(
                                "/(?<!\\\\)(\\\\\\\\)*\\\\'/", $xagio_tokens[$j], $matches
                            );

                            $unescaped_quotes = $total_quotes - $escaped_quotes;

                            if ($unescaped_quotes % 2 == 1) {
                                // odd number of unescaped quotes. In combination with the previous incomplete
                                // statement(s), we now have a complete statement. (2 odds always make an even)
                                $xagio_output[] = $temp . $xagio_tokens[$j];

                                // save memory.
                                $xagio_tokens[$j] = "";
                                $temp       = "";

                                // exit the loop.
                                $complete_stmt = true;
                                // make sure the outer loop continues at the right point.
                                $xagio_i = $j;
                            } else {
                                // even number of unescaped quotes. We still don't have a complete statement.
                                // (1 odd and 1 even always make an odd)
                                $temp .= $xagio_tokens[$j] . $delimiter;
                                // save memory.
                                $xagio_tokens[$j] = "";
                            }
                        } // for..
                    } // else
                }
            }

            return $xagio_output;
        }

        public static function getName($xagio_url, $only_host = false)
        {
            $parts = wp_parse_url($xagio_url);
            if ($only_host) {
                $xagio_name = $parts["host"];
            } else {
                $xagio_name = str_replace(".", "_", $parts["host"]);
            }
            return $xagio_name;
        }

        private static function loadKeys()
        {
            $backup_settings = get_option("XAGIO_BACKUP_SETTINGS");
            $secret_keys     = $backup_settings["secret_keys"] ?? false;

            /** Backup Application Keys */
            if (!defined("XAGIO_DROPBOX_KEY")) {
                define(
                    "XAGIO_DROPBOX_KEY", isset($secret_keys["dropbox"]) ? $secret_keys["dropbox"]["public"] : false
                );
            }
            if (!defined("XAGIO_DROPBOX_SECRET")) {
                define(
                    "XAGIO_DROPBOX_SECRET", isset($secret_keys["dropbox"]) ? $secret_keys["dropbox"]["private"] : false
                );
            }
            if (!defined("XAGIO_ONEDRIVE_KEY")) {
                define(
                    "XAGIO_ONEDRIVE_KEY", isset($secret_keys["onedrive"]) ? $secret_keys["onedrive"]["public"] : false
                );
            }
            if (!defined("XAGIO_ONEDRIVE_SECRET")) {
                define(
                    "XAGIO_ONEDRIVE_SECRET", isset($secret_keys["onedrive"]) ? $secret_keys["onedrive"]["private"] : false
                );
            }
            if (!defined("XAGIO_GOOGLEDRIVE_KEY")) {
                define(
                    "XAGIO_GOOGLEDRIVE_KEY", isset($secret_keys["googledrive"]) ? $secret_keys["googledrive"]["public"] : false
                );
            }
            if (!defined("XAGIO_GOOGLEDRIVE_SECRET")) {
                define(
                    "XAGIO_GOOGLEDRIVE_SECRET", isset($secret_keys["googledrive"]) ? $secret_keys["googledrive"]["private"] : false
                );
            }
            /** Backup Application Keys */
        }
    }
}
