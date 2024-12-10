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
            $bs = get_option('XAGIO_BACKUP_SPEED', []);
            $bz = get_option('XAGIO_BACKUP_SIZE', '');

            $backup_speed = [
                'grade'      => isset($bs['grade']) ? esc_html($bs['grade']) : '',
                'time_taken' => isset($bs['time_taken']) ? esc_html($bs['time_taken']) : ''
            ];

            wp_localize_script('xagio_backup', 'xagio_backup', [
                'backup_speed' => $backup_speed,
                'backup_size'  => esc_html($bz)
            ]);
        }

        public static function initialize()
        {
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

            // check if action is scheduled
            if (!wp_next_scheduled('xagio_calculate_backup_size')) {
                wp_schedule_event(time(), 'daily', 'xagio_calculate_backup_size');
            }

            // Load Backup Keys
            self::loadKeys();

            // Measure backup speeds
            self::measureBackupSpeed();

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

        public static function measureBackupSpeed()
        {
            if (!get_option('XAGIO_BACKUP_SPEED')) {

                // Update option with blank data, just in case if the process below fails
                update_option('XAGIO_BACKUP_SPEED', [
                    'time_taken' => 0,
                    'grade'      => 0
                ]);

                update_option('XAGIO_BACKUP_SPEED', xagio_backup_speed());
                update_option("XAGIO_BACKUP_SIZE", xagio_calculate_backup_size());
            }
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
            $backup  = sanitize_text_field(wp_unslash($_POST['backup']));
            $id      = sanitize_text_field(wp_unslash($_POST['id']));

            if (empty($storage) || $backup == NULL || $id == NULL) {
                xagio_json('error', 'Fields are empty. Bye.');
                exit;
            }

            // Get domain of current website
            $domain = sanitize_url(sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])));

            try {

                switch ($storage) {

                    case 'dropbox':
                        xagio_jsonc(self::downloadBackup_Dropbox($domain, $backup));
                        break;

                    case 'onedrive':
                        xagio_jsonc(self::downloadBackup_Onedrive($domain, $backup));
                        break;

                    case 'googledrive':
                        xagio_jsonc(self::downloadBackup_GoogleDrive($backup));
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
            $backup  = sanitize_text_field(wp_unslash($_POST['backup']));
            $id      = sanitize_text_field(wp_unslash($_POST['id']));

            if (empty($storage) || $backup == NULL) {
                xagio_json('error', 'Fields are empty. Bye.');
                exit;
            }

            // Get domain of current website
            $domain = self::getName(site_url());

            try {

                switch ($storage) {

                    case 'dropbox':
                        xagio_jsonc(self::deleteBackup_Dropbox($domain, $backup));
                        break;

                    case 'onedrive':
                        xagio_jsonc(self::deleteBackup_Onedrive($domain, $backup));
                        break;

                    case 'googledrive':
                        xagio_jsonc(self::deleteBackup_GoogleDrive($id));
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
        private static function downloadBackup_Dropbox($domain, $backup)
        {
            $tokens = self::loadClassAndTokens('XAGIO_DropboxClient');

            // check the token
            $backup_DropboxAccessToken = isset($tokens["dropbox"]) ? $tokens["dropbox"] : "";

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

            $result = $XAGIO_DropboxClient->GetLink('/' . $folder . '/' . $backup);

            if (isset($result['link'])) {
                return array(
                    'status'  => 'success',
                    'message' => 'Successfully retrieved backup temporary URL!',
                    'data'    => $result['link']
                );
            } else {
                return array(
                    'status'  => 'error',
                    'message' => 'There was a problem while requesting temporary download URL from Dropbox, you will have to download this backup manually.'
                );
            }

        }

        private static function downloadBackup_Onedrive($domain, $backup)
        {
            $tokens = self::loadClassAndTokens('XAGIO_OnedriveClient');

            // check the token
            $backup_OnedriveAccessToken = isset($tokens["onedrive"]) ? $tokens["onedrive"] : "";

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

            $result = $XAGIO_OnedriveClient->GetFileFolder('/drive/root:/xagio/' . $backup);

            if (isset($result["@microsoft.graph.downloadUrl"])) {
                return array(
                    'status'  => 'success',
                    'message' => 'Successfully retrieved backup temporary URL!',
                    'data'    => $result["@microsoft.graph.downloadUrl"]
                );
            } else {
                return array(
                    'status'  => 'error',
                    'message' => 'There was a problem while requesting temporary download URL from OneDrive, you will have to download this backup manually.'
                );
            }


        }

        private static function downloadBackup_GoogleDrive($backup)
        {
            $tokens = self::loadClassAndTokens('XAGIO_GoogleDrive');

            // check the token
            $backup_XAGIO_GoogleDriveAccessToken = isset($tokens["googledrive"]) ? $tokens["googledrive"] : "";

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

            $result = $XAGIO_GoogleDriveClient->FindFiles($backup);

            if (isset($result['items'][0])) {
                return array(
                    'status'  => 'redirect',
                    'message' => 'Successfully retrieved backup temporary URL!',
                    'data'    => $result['items'][0]['webContentLink']
                );
            } else {
                return array(
                    'status'  => 'error',
                    'message' => 'There was a problem while requesting temporary download URL from Google Drive, you will have to download this backup manually.'
                );
            }

        }

        /*
         *  Deleting Backups
         */
        private static function deleteBackup_Dropbox($domain, $backup)
        {
            $tokens = self::loadClassAndTokens('XAGIO_DropboxClient');

            // check the token
            $backup_DropboxAccessToken = isset($tokens["dropbox"]) ? $tokens["dropbox"] : "";

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
            $result = $XAGIO_DropboxClient->Delete('/' . $folder . '/' . $backup);

            if (isset($result['name'])) {
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

        private static function deleteBackup_Onedrive($domain, $backup)
        {
            $tokens = self::loadClassAndTokens('XAGIO_OnedriveClient');

            // check the token
            $backup_OnedriveAccessToken = isset($tokens["onedrive"]) ? $tokens["onedrive"] : "";

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

            $result = $XAGIO_OnedriveClient->deleteCall('/drive/root:/xagio/' . $backup);

            if ($result === 204) {
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
            $tokens = self::loadClassAndTokens('XAGIO_GoogleDrive');

            // check the token
            $backup_XAGIO_GoogleDriveAccessToken = isset($tokens["googledrive"]) ? $tokens["googledrive"] : "";

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

        /*
         *  Listing Backups
         */
        private static function getBackups_Dropbox($domain)
        {
            $tokens = self::loadClassAndTokens('XAGIO_DropboxClient');

            // check the token
            $backup_DropboxAccessToken = isset($tokens["dropbox"]) ? $tokens["dropbox"] : "";

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

            $files = $XAGIO_DropboxClient->ListFolder($folder);

            if ($files == false) {
                return array(
                    'status'  => 'info',
                    'message' => 'There are still no backups for this website. Please try again later.'
                );
            } else {
                $output = array();
                foreach ($files['entries'] as $file) {
                    if ($file['.tag'] == 'file' && strpos($file['name'], $folder) !== false) {
                        $output[] = array(
                            'file' => $file['name'],
                            'size' => $file['size'],
                            'date' => $file['client_modified'],
                            'id'   => $file['id']
                        );
                    }
                }
                usort($output, function ($a, $b) {
                    if ($a['date'] == $b['date']) {
                        return 0;
                    }
                    return ($a['date'] > $b['date']) ? -1 : 1;
                });
                return array(
                    'status'  => 'success',
                    'message' => 'Retrieved backups from Dropbox.',
                    'files'   => $output
                );
            }
        }

        private static function getBackups_Onedrive($domain)
        {
            $tokens = self::loadClassAndTokens('XAGIO_OnedriveClient');

            // check the token
            $backup_OnedriveAccessToken = isset($tokens["onedrive"]) ? $tokens["onedrive"] : "";

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
            $files  = $XAGIO_OnedriveClient->GetFileFolder('/drive/root:/xagio:/children');

            if (isset($files['error']) || sizeof(@$files["value"]) == 0) {
                return array(
                    'status'  => 'info',
                    'message' => 'There are still no backups for this website. Please try again later.'
                );
            } else {
                $backups = $files["value"];
                $output  = array();
                foreach ($backups as $backup) {
                    $mystring = $backup["name"];
                    $pos      = strpos($mystring, $folder);
                    if ($pos === false) {
                        continue;
                    } else {
                        if ($pos == 0) {
                            $output[] = array(
                                'file' => $backup["name"],
                                'size' => $backup['size'],
                                'date' => gmdate('Y-m-d H:i:s', strtotime($backup["lastModifiedDateTime"])),
                                'id'   => $backup['id']
                            );
                        } else {
                            continue;
                        }
                    }
                }
                if (sizeof($output) == 0) {
                    return array(
                        'status'  => 'info',
                        'message' => 'There are still no backups for this website. Please try again later.'
                    );
                } else {
                    usort($output, function ($a, $b) {
                        if ($a['date'] == $b['date']) {
                            return 0;
                        }
                        return ($a['date'] > $b['date']) ? -1 : 1;
                    });
                    return array(
                        'status'  => 'success',
                        'message' => 'Retrieved backups from OneDrive.',
                        'files'   => $output
                    );
                }
            }

        }

        private static function getBackups_GoogleDrive($domain)
        {
            $tokens = self::loadClassAndTokens('XAGIO_GoogleDrive');

            // check the token
            $backup_GoogleDriveAccessToken = isset($tokens["googledrive"]) ? $tokens["googledrive"] : "";

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
            $files  = $XAGIO_GoogleDriveClient->ListFiles($folder);

            if (isset($files['error']) || $files == false) {
                return array(
                    'status'  => 'info',
                    'message' => 'There are still no backups for this website. Please try again later.'
                );
            } else {
                $output = array();

                foreach ($files['items'] as $file) {
                    $output[] = array(
                        'file' => $file['title'],
                        'size' => $file['fileSize'],
                        'date' => $file['createdDate'],
                        'id'   => $file['id']
                    );
                }
                usort($output, function ($a, $b) {
                    if ($a['date'] == $b['date']) {
                        return 0;
                    }
                    return ($a['date'] > $b['date']) ? -1 : 1;
                });
                return array(
                    'status'  => 'success',
                    'message' => 'Retrieved backups from Google Drive.',
                    'files'   => $output
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

        private static function loadClassAndTokens($class, $classFile = false)
        {
            $class_dir = dirname(__FILE__) . "/../ext/";

            if (!$classFile) {
                $classFile = $class;
            }

            $location = $class_dir . $classFile . ".php";
            if (!class_exists($class)) {
                require_once $location;
            }

            return get_option("XAGIO_BACKUP_SETTINGS");
        }

        private static function checkIfBackupLocationIsSet($location)
        {

            $tokens = get_option("XAGIO_BACKUP_SETTINGS");

            if ($location === "dropbox") {
                if (empty($tokens["dropbox"]["access_token"])) {
                    return false;
                }
            } else if ($location === "onedrive") {
                if (empty($tokens["onedrive"]["access_token"])) {
                    return false;
                }
            } else if ($location === "googledrive") {
                if (empty($tokens["googledrive"]['access_token'])) {
                    return false;
                }
            }

            return true;
        }

        public static function saveSettings()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['location'], $_POST["copies"], $_POST["frequency"])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $location  = sanitize_text_field(wp_unslash($_POST["location"]));
            $copies    = sanitize_text_field(wp_unslash($_POST["copies"]));
            $frequency = sanitize_text_field(wp_unslash($_POST["frequency"]));

            if (empty($location) || empty($copies) || empty($frequency)) {
                return;
            }

            if (!self::checkIfBackupLocationIsSet($location)) {
                xagio_json('error', 'This location is not set up yet. Please set it up first.');
            }

            update_option("XAGIO_BACKUP_LOCATION", $location);
            update_option("XAGIO_BACKUP_LIMIT", $copies);
            update_option("XAGIO_BACKUP_DATE", $frequency);

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
        public static function restoreBackupHandler($storage, $backup, $backup_id)
        {
            // Get the needed AccessTokens
            $tokens = get_option("XAGIO_BACKUP_SETTINGS");

            if (!function_exists('WP_Filesystem')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            WP_Filesystem();
            global $wp_filesystem;

            // Check whether the folders exist (if not, make them)
            $backupFolder = XAGIO_PATH . "/backups";
            if (!$wp_filesystem->is_dir($backupFolder)) {
                $wp_filesystem->mkdir($backupFolder);
            }

            // Create Backup Name (both folder and file name)
            $siteName = self::getName(site_url());

            // get the real site name
            $realSiteName = self::getName(site_url(), true);

            $class_dir = dirname(__FILE__) . "/../ext/";

            /* DROPBOX */
            if ($storage === "dropbox") {
                // Include the DropBox Class
                $dropBoxClass = $class_dir . "XAGIO_DropboxClient.php";
                if (!class_exists("XAGIO_DropboxClient")) {
                    require_once $dropBoxClass;
                }

                // check the token
                $backup_DropboxAccessToken = isset(
                    $tokens["dropbox"]["access_token"]
                ) ? $tokens["dropbox"]["access_token"] : "";

                if (!empty($backup_DropboxAccessToken)) {
                    if (!XAGIO_DROPBOX_KEY || !XAGIO_DROPBOX_SECRET) {
                        return [
                            "status"  => "error",
                            "message" => "Please synchronize your Backup Settings to obtain latest updated settings.",
                        ];
                    }

                    // Initialize the class
                    $XAGIO_DropboxClient = new XAGIO_DropboxClient(
                        $tokens["dropbox"]
                    );

                    // Try to create a folder
                    $XAGIO_DropboxClient->Download(
                        "/" . $siteName . "/" . $backup, $backupFolder . "/" . $backup
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
                // Include the OneDrive Class
                $oneDriveClass = $class_dir . "XAGIO_OnedriveClient.php";
                if (!class_exists("XAGIO_OnedriveClient")) {
                    require_once $oneDriveClass;
                }

                // check the token
                $backup_OnedriveAccessToken = isset(
                    $tokens["onedrive"]["access_token"]
                ) ? $tokens["onedrive"]["access_token"] : "";

                if (!empty($backup_OnedriveAccessToken)) {
                    if (!XAGIO_ONEDRIVE_KEY || !XAGIO_ONEDRIVE_SECRET) {
                        return [
                            "status"  => "error",
                            "message" => "Please synchronize your Backup Settings to obtain latest updated settings.",
                        ];
                    }

                    // Initialize the class
                    $XAGIO_OnedriveClient = new XAGIO_OnedriveClient();

                    $XAGIO_OnedriveClient->SetAccessToken($tokens["onedrive"]);
                    $XAGIO_OnedriveClient->renewAccessToken();

                    // Try to create a folder
                    $XAGIO_OnedriveClient->downloadCall(
                        "/drive/root:/xagio/" . $backup . ":/content", $backupFolder . "/" . $backup
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
                // Include the DropBox Class
                $XAGIO_GoogleDriveClass = $class_dir . "XAGIO_GoogleDrive.php";
                if (!class_exists("XAGIO_GoogleDrive")) {
                    require_once $XAGIO_GoogleDriveClass;
                }

                // check the token
                $backup_XAGIO_GoogleDriveAccessToken = isset($tokens["googledrive"]) ? $tokens["googledrive"] : "";

                if (!empty($backup_XAGIO_GoogleDriveAccessToken)) {
                    if (!XAGIO_GoogleDrive_KEY || !XAGIO_GoogleDrive_SECRET) {
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
                        $backup_id, $backupFolder . "/" . $backup
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
            if (!file_exists($backupFolder . "/" . $backup)) {
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
                $res = $zip->open($backupFolder . "/" . $backup);
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
            wp_delete_file($backupFolder . "/" . $backup);

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
                "message" => "Successfully restored " . $backup . " on " . $realSiteName . "!",
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

            for ($i = 0; $i < $length; $i++) {
                $char     = $sql[$i];
                $nextChar = ($i + 1 < $length) ? $sql[$i + 1] : null;

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
                    while ($char !== "\n" && $i < $length) {
                        $char = $sql[++$i];
                    }
                    $buffer .= "\n";
                    continue;
                } elseif ($char === '/' && $nextChar === '*') {
                    // Multi-line comment
                    $i += 2; // Skip '/*'
                    while (!($sql[$i] === '*' && $sql[$i + 1] === '/') && $i < $length) {
                        $i++;
                    }
                    $i++; // Skip '*/'
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
        public static function restoreMySQL($location, &$log = [])
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
            $isZip = pathinfo($location, PATHINFO_EXTENSION) === "zip";

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
                    $res = $zip->open($location);
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
                        $query = str_replace('%', '%%', $query);
                        if (!empty($query)) { // Ensure the query isn't empty

                            // Send Query
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

            $url = sanitize_url(wp_unslash($_POST["url"]));
            if (empty($url)) {
                xagio_jsonc([
                    "status"  => "error",
                    "message" => "Please provide a valid backup.",
                ]);
            }

            $file_name = basename($url);
            // check if file exists
            $file = XAGIO_PATH . "/backups/" . $file_name;
            if (!file_exists($file)) {
                xagio_jsonc([
                    "status"  => "error",
                    "message" => "Backup file does not exist.",
                ]);
            }

            // Determine the type of backup
            $backupType = pathinfo($file);
            $backupType = $backupType["filename"];

            // Check if the backup is a full backup
            if (strpos($backupType, "full") !== false) {
                XAGIO_MODEL_BACKUPS::restoreFullBackup($file);
            } elseif (strpos($backupType, "files") !== false) {
                XAGIO_MODEL_BACKUPS::restoreFileBackup($file);
            } elseif (strpos($backupType, "mysql") !== false) {
                XAGIO_MODEL_BACKUPS::restoreMySQLBackup($file);
            }
        }

        public static function restoreFileBackup($preuploaded_file = null)
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            $backup = null;

            if ($preuploaded_file != null) {

                $backup = $preuploaded_file;

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
                    $backup = $uploaded_file['file'];
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
                $res = $zip->open($backup);
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
            $backup     = null;

            if ($preuploaded_file != null) {

                $backup = $preuploaded_file;

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
                    $backup = $uploaded_file['file'];
                } else {
                    // Handle upload error
                    xagio_jsonc([
                        "status"  => "error",
                        "message" => "Failed to upload the file: " . $uploaded_file['error'],
                    ]);
                }

            }


            $file_size = xagio_filesize($backup);

            // Restore MySQL
            $log          = [];
            $mysql_result = XAGIO_MODEL_BACKUPS::restoreMySQL(
                $backup, $log
            );

            // Delete the uploaded file after processing
            wp_delete_file($backup);

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

            $backup = null;

            if ($preuploaded_file != null) {

                $backup = $preuploaded_file;

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
                    $backup = $uploaded_file['file'];

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
                $res = $zip->open($backup);
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
                "wp-admin",
                "wp-content",
                "wp-includes",
                "index.php",
                "mysql.zip",
                "wp-activate.php",
                "wp-blog-header.php",
                "wp-comments-post.php",
                "wp-config.php",
                "wp-cron.php",
                "wp-links-opml.php",
                "wp-load.php",
                "wp-login.php",
                "wp-mail.php",
                "wp-settings.php",
                "wp-signup.php",
                "wp-trackback.php",
                "xmlrpc.php",
            ];

            foreach ($core_files as $core_file) {
                if (!file_exists($restoreFolder . "/" . $core_file)) {
                    xagio_jsonc([
                        "status"  => "error",
                        "message" => "Backup archive is damaged! Backup core file " . $core_file . " is missing from this archive. Either add it back in, or use a different valid backup archive!",
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
            $limit = get_option("XAGIO_BACKUP_LIMIT");
            if (!$limit || empty($limit)) {
                return false;
            }

            // Get the preferred backup location
            $backupLocation = get_option("XAGIO_BACKUP_LOCATION");

            // Get the needed AccessTokens
            $tokens = get_option("XAGIO_BACKUP_SETTINGS");

            // Domain folder name
            $folder = self::getName(site_url());

            $class_dir = dirname(__FILE__) . "/../ext/";

            /* DROPBOX */
            if ($backupLocation === "dropbox") {
                // Include the DropBox Class
                $dropBoxClass = $class_dir . "XAGIO_DropboxClient.php";
                if (!class_exists("XAGIO_DropboxClient")) {
                    require_once $dropBoxClass;
                }

                // check the token
                $backup_DropboxAccessToken = isset(
                    $tokens["dropbox"]["access_token"]
                ) ? $tokens["dropbox"]["access_token"] : "";

                if (!empty($backup_DropboxAccessToken)) {
                    if (!XAGIO_DROPBOX_KEY || !XAGIO_DROPBOX_SECRET) {
                        return false;
                    }

                    // Initialize the class
                    $XAGIO_DropboxClient = new XAGIO_DropboxClient(
                        $tokens["dropbox"]
                    );

                    $files = $XAGIO_DropboxClient->ListFolder($folder);
                    if ($files !== false) {
                        $output = [];
                        foreach ($files["entries"] as $file) {
                            if ($file[".tag"] == "file" && strpos($file["name"], $folder) !== false) {
                                $output[] = [
                                    "file" => $file["name"],
                                    "size" => $file["size"],
                                    "date" => $file["client_modified"],
                                    "id"   => $file["id"],
                                ];
                            }
                        }
                        usort($output, function ($a, $b) {
                            if ($a["date"] == $b["date"]) {
                                return false;
                            }
                            return $a["date"] > $b["date"] ? -1 : 1;
                        });

                        if (sizeof($output) > $limit) {
                            $trimBy            = sizeof($output) - $limit;
                            $backupsForRemoval = array_slice(
                                $output, -$trimBy, $trimBy, true
                            );

                            // Remove backups
                            foreach ($backupsForRemoval as $backup) {
                                $XAGIO_DropboxClient->Delete(
                                    "/" . $folder . "/" . $backup["file"]
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
                // Include the DropBox Class
                $oneDriveClass = $class_dir . "XAGIO_OnedriveClient.php";
                if (!class_exists("XAGIO_OnedriveClient")) {
                    require_once $oneDriveClass;
                }

                // check the token
                $backup_OnedriveAccessToken = isset(
                    $tokens["onedrive"]["access_token"]
                ) ? $tokens["onedrive"]["access_token"] : "";

                if (!empty($backup_OnedriveAccessToken)) {
                    if (!XAGIO_ONEDRIVE_KEY || !XAGIO_ONEDRIVE_SECRET) {
                        return;
                    }

                    // Initialize the class
                    $XAGIO_OnedriveClient = new XAGIO_OnedriveClient();

                    $XAGIO_OnedriveClient->SetAccessToken($tokens["onedrive"]);
                    $XAGIO_OnedriveClient->renewAccessToken();

                    $files = $XAGIO_OnedriveClient->GetFileFolder(
                        "/drive/root:/xagio:/children"
                    );

                    // Find all backups
                    if (sizeof($files["value"]) !== 0) {
                        $backups = $files["value"];
                        $output  = [];
                        foreach ($backups as $backup) {
                            $mystring = $backup["name"];
                            $pos      = strpos($mystring, $folder);
                            if ($pos === false) {
                                continue;
                            } else {
                                if ($pos == 0) {
                                    $output[] = [
                                        "file" => $backup["name"],
                                        "size" => $backup["size"],
                                        "date" => gmdate(
                                            "Y-m-d H:i:s", strtotime(
                                                $backup["lastModifiedDateTime"]
                                            )
                                        ),
                                        "id"   => $backup["id"],
                                    ];
                                } else {
                                    continue;
                                }
                            }
                        }
                        if (sizeof($output) !== 0) {
                            usort($output, function ($a, $b) {
                                if ($a["date"] == $b["date"]) {
                                    return 0;
                                }
                                return $a["date"] > $b["date"] ? -1 : 1;
                            });

                            if (sizeof($output) > $limit) {
                                $trimBy            = sizeof($output) - $limit;
                                $backupsForRemoval = array_slice(
                                    $output, -$trimBy, $trimBy, true
                                );

                                // Remove backups
                                foreach ($backupsForRemoval as $backup) {
                                    $XAGIO_OnedriveClient->deleteCall(
                                        "/drive/root:/xagio/" . $backup["file"]
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
                // Include the DropBox Class
                $XAGIO_GoogleDriveClass = $class_dir . "XAGIO_GoogleDrive.php";
                if (!class_exists("XAGIO_GoogleDrive")) {
                    require_once $XAGIO_GoogleDriveClass;
                }

                // check the token
                $backup_XAGIO_GoogleDriveAccessToken = isset($tokens["googledrive"]) ? $tokens["googledrive"] : "";

                if (!empty($backup_XAGIO_GoogleDriveAccessToken)) {
                    if (!XAGIO_GoogleDrive_KEY || !XAGIO_GoogleDrive_SECRET) {
                        return;
                    }

                    // Initialize the class
                    $XAGIO_GoogleDriveClient = new XAGIO_GoogleDrive(
                        $backup_XAGIO_GoogleDriveAccessToken
                    );

                    $files = $XAGIO_GoogleDriveClient->ListFiles($folder);
                    if ($files !== false) {
                        $output = [];

                        foreach ($files["items"] as $file) {
                            $output[] = [
                                "file" => $file["title"],
                                "size" => $file["fileSize"],
                                "date" => $file["createdDate"],
                                "id"   => $file["id"],
                            ];
                        }
                        usort($output, function ($a, $b) {
                            if ($a["date"] == $b["date"]) {
                                return 0;
                            }
                            return $a["date"] > $b["date"] ? -1 : 1;
                        });

                        if (sizeof($output) > $limit) {
                            $trimBy            = sizeof($output) - $limit;
                            $backupsForRemoval = array_slice(
                                $output, -$trimBy, $trimBy, true
                            );

                            // Remove backups
                            foreach ($backupsForRemoval as $backup) {
                                $XAGIO_GoogleDriveClient->Delete($backup["id"]);
                            }
                        }
                        return true;
                    }
                }
            }
            /* XAGIO_GoogleDrive */

            return false;
        }

        private static function excludeFromFiles($listOfFiles)
        {
            if (XAGIO_BACKUPS_IGNORE_DOMAINS == true) {
                $domains  = [];
                $new_list = [];
                foreach ($listOfFiles as $file) {
                    if (is_dir($file)) {
                        $test = str_replace(ABSPATH, "", $file);
                        if (preg_match(
                            "/(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]/", $test
                        )) {
                            $domains[] = $test;
                        } else {
                            $new_list[] = $file;
                        }
                    } else {
                        foreach ($domains as $domain) {
                            if (strpos($file, $domain) !== false) {
                                continue 2;
                            }
                        }

                        $new_list[] = $file;
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

            $output = null;

            if ($backup_destination == "local") {
                $output = self::doCloneBackup($backup_type);
            } else {
                if (!self::checkIfBackupLocationIsSet($backup_destination)) {
                    xagio_json('error', 'This location is not set up yet. Please set it up first.');
                }

                // Change backup location temporarily
                $backupLocation = get_option("XAGIO_BACKUP_LOCATION");
                update_option("XAGIO_BACKUP_LOCATION", $backup_destination);
                $_POST['create_id'] = 0;
                $output             = self::doBackup($backup_type);
                update_option("XAGIO_BACKUP_LOCATION", $backupLocation);
            }

            xagio_jsonc($output);
        }

        public static function removeBackup()
        {
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['name'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            $name         = sanitize_text_field(wp_unslash($_POST["name"]));
            $backupFolder = XAGIO_PATH . "/backups/";
            if (file_exists($backupFolder . $name)) {
                wp_delete_file($backupFolder . $name);
            }
        }

        public static function doCloneBackup($type = "full")
        {
            // Check whether the folders exist (if not, make them)
            $backupFolder = XAGIO_PATH . "/backups";

            if (!file_exists($backupFolder)) {
                xagio_mkdir($backupFolder);
            }

            // WordPress Directory
            $wordPressHomeDir = ABSPATH;

            $backupName = wp_parse_url(get_site_url());
            $backupName = strtolower($backupName["host"]);
            $backupName = str_replace(".", "_", $backupName) . "_" . $type . "_" . gmdate("H_i_s__Y_m_d");

            // Backup ZIP File
            $backupFile = $backupFolder . DIRECTORY_SEPARATOR . $backupName . ".zip";

            // Backup SQL File
            $backupSQL = $wordPressHomeDir . "mysql.zip";

            if (file_exists($backupSQL)) {
                wp_delete_file($backupSQL);
            }
            // Start the MySQL backup process
            if ($type == "mysql") {

                $output = self::doBackupMySQL($backupFile);
                if (!$output) {
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

                $output = self::doBackupMySQL($backupSQL);
                if (!$output) {
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
            check_ajax_referer('xagio_nonce', '_xagio_nonce');

            if (!isset($_POST['create_id'])) {
                wp_die('Required parameters are missing.', 'Missing Parameters', ['response' => 400]);
            }

            // Update keys first
            XAGIO_SYNC::getBackupSettings();

            $createID       = intval($_POST["create_id"]);
            $backupLocation = get_option("XAGIO_BACKUP_LOCATION");
            $tokens         = get_option("XAGIO_BACKUP_SETTINGS");

            // Check if tokens and location are set
            if (empty($tokens) || empty($backupLocation) || $backupLocation == "none") {
                $message = "Please set your Storage Method and credentials for the preferred method.";
                return self::handleError($createID, $message);
            }

            // Check and create backup folder if necessary
            $backupFolder = XAGIO_PATH . "/backups";
            if (!file_exists($backupFolder)) {
                xagio_mkdir($backupFolder);
            }

            // WordPress Directory and Backup Names
            $wordPressHomeDir = ABSPATH;
            $siteName         = self::getName(site_url());
            $backupName       = $siteName . "_backup_{$type}_" . gmdate("m-d-Y_H-i");
            $backupFile       = $backupFolder . DIRECTORY_SEPARATOR . $backupName . ".zip";
            $backupSQL        = $wordPressHomeDir . "mysql.zip";

            if ($type == "full" || $type == "mysql") {

                if (file_exists($backupSQL)) {
                    wp_delete_file($backupSQL);
                }

                // Start the MySQL backup process
                $output = self::doBackupMySQL($backupSQL);
                if (!$output) {
                    return [
                        "status"  => "error",
                        "message" => "Failed to create backup. Make sure you have enough space on the disk, or write permissions."
                    ];
                }

            }

            if ($type == "full" || $type == "files") {
                $listOfFilesAndFolders = self::excludeFromFiles(glob($wordPressHomeDir . "*"));

                if (!class_exists("xagio_ZipArchiveX")) {
                    return [
                        "status"  => "error",
                        "message" => "ZipArchive is not installed."
                    ];
                }

                $archive = new xagio_ZipArchiveX();
                if ($archive->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    return self::handleError($createID, "Cannot create zip archive! Make sure that you have write permissions!");
                }

                $archive->pack($listOfFilesAndFolders, $wordPressHomeDir);
                $archive->close();

            } else if ($type == "mysql") {

                xagio_rename($backupSQL, $backupFile);

            }

            if (!file_exists($backupFile)) {
                return self::handleError($createID, "Failed to create a backup. Could not zip the files.");
            }

            if ($type == "full" || $type == "mysql") {
                // Remove the MySQL backup from Root Dir
                wp_delete_file($backupSQL);
            }

            // Start uploading the backup to the remote server
            self::uploadToRemoteServer($backupLocation, $tokens, $backupFile, $siteName, $createID);

            // Remove the files backup
            wp_delete_file($backupFile);

            // Trim backups
            self::trimBackups();

            if ($createID == "0") {
                self::sendCronBackupLogs("success | Backup successfully created.", "Backup successfully created.");
                return [
                    "status"  => "success",
                    "message" => "Backup successfully created."
                ];
            } else {
                XAGIO_API::apiRequest("backups", "POST", [
                    "message"   => "Backup successfully created.",
                    "create_id" => $createID
                ]);
            }

            return false;
        }

        private static function handleError($createID, $message)
        {
            if ($createID === "0") {
                self::sendCronBackupLogs("Error", $message);
                return [
                    "status"  => "error",
                    "message" => $message
                ];
            } else {
                XAGIO_API::apiRequest("backups", "POST", [
                    "message"   => $message,
                    "create_id" => $createID
                ]);
            }
            return $message;
        }

        private static function uploadToRemoteServer($backupLocation, $tokens, $backupFile, $siteName, $createID)
        {
            switch ($backupLocation) {
                case "dropbox":
                    self::uploadToDropbox($tokens, $backupFile, $siteName, $createID);
                    break;
                case "onedrive":
                    self::uploadToOnedrive($tokens, $backupFile, $siteName, $createID);
                    break;
                case "googledrive":
                    self::uploadToXAGIO_GoogleDrive($tokens, $backupFile, $siteName, $createID);
                    break;
                default:
                    self::handleError($createID, "Unsupported backup location.");
                    break;
            }
        }

        private static function uploadToDropbox($tokens, $backupFile, $siteName, $createID)
        {
            $dropBoxClass = dirname(__FILE__) . "/../ext/XAGIO_DropboxClient.php";
            if (!class_exists("XAGIO_DropboxClient")) {
                require_once $dropBoxClass;
            }

            $backup_DropboxAccessToken = $tokens["dropbox"] ?? "";

            if (!empty($backup_DropboxAccessToken) && XAGIO_DROPBOX_KEY && XAGIO_DROPBOX_SECRET) {
                $XAGIO_DropboxClient = new XAGIO_DropboxClient($backup_DropboxAccessToken);

                $XAGIO_DropboxClient->CreateFolder($siteName);
                $dropboxOutput = $XAGIO_DropboxClient->Upload($backupFile, "/" . $siteName);

                if (isset($dropboxOutput["error_summary"])) {
                    self::handleError($createID, "Failed to upload backup to Dropbox. Info: " . $dropboxOutput["error_summary"]);
                }
            } else {
                self::handleError($createID, "Please synchronize backup settings on this website before trying again.");
            }
        }

        private static function uploadToOnedrive($tokens, $backupFile, $siteName, $createID)
        {
            $oneDriveClass = dirname(__FILE__) . "/../ext/XAGIO_OnedriveClient.php";
            if (!class_exists("XAGIO_OnedriveClient")) {
                require_once $oneDriveClass;
            }

            $backup_OnedriveAccessToken = $tokens["onedrive"] ?? "";

            if (!empty($backup_OnedriveAccessToken) && XAGIO_ONEDRIVE_KEY && XAGIO_ONEDRIVE_SECRET) {
                $XAGIO_OnedriveClient = new XAGIO_OnedriveClient();

                $XAGIO_OnedriveClient->SetAccessToken($backup_OnedriveAccessToken);
                $XAGIO_OnedriveClient->renewAccessToken();
                $XAGIO_OnedriveClient->CreateFolder();
                $onedriveOutput = $XAGIO_OnedriveClient->upload($backupFile);

                if (isset($onedriveOutput["error"])) {
                    self::handleError($createID, "Failed to upload backup to OneDrive. Info: " . $onedriveOutput["error"]["message"]);
                }
            } else {
                self::handleError($createID, "Please synchronize backup settings on this website before trying again.");
            }
        }

        private static function uploadToXAGIO_GoogleDrive($tokens, $backupFile, $siteName, $createID)
        {
            $XAGIO_GoogleDriveClass = dirname(__FILE__) . "/../ext/XAGIO_GoogleDrive.php";
            if (!class_exists("XAGIO_GoogleDrive")) {
                require_once $XAGIO_GoogleDriveClass;
            }

            $backup_XAGIO_GoogleDriveAccessToken = $tokens["googledrive"] ?? "";

            if (!empty($backup_XAGIO_GoogleDriveAccessToken) && XAGIO_GoogleDrive_KEY && XAGIO_GoogleDrive_SECRET) {
                $XAGIO_GoogleDriveClient = new XAGIO_GoogleDrive($backup_XAGIO_GoogleDriveAccessToken);

                $XAGIO_GoogleDriveClient->CreateFolder("Xagio");
                $XAGIO_GoogleDriveClient->CreateFolder($siteName, "Xagio");
                $XAGIO_GoogleDriveOutput = $XAGIO_GoogleDriveClient->Upload($backupFile, $siteName);

                if (isset($XAGIO_GoogleDriveOutput["error"])) {
                    self::handleError($createID, "Failed to upload backup to Google Drive. Info: " . $XAGIO_GoogleDriveOutput["errors"][0]["message"]);
                }
            } else {
                self::handleError($createID, "Please synchronize backup settings on this website before trying again.");
            }
        }

        // Function that will send Cron backup logs to panel
        public static function sendCronBackupLogs($status, $message)
        {
            $domainName = self::getName(site_url(), "only_host");

            XAGIO_API::apiRequest($endpoint = "backups", $method = "POST", [
                "status"  => $status,
                "message" => $message,
                "domain"  => $domainName,
            ]);
        }

        // Function that handle MySQL backup
        public static function doBackupMySQL($location)
        {
            if (!xagio_is_writable(dirname($location))) {
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

            $i = 0;

            foreach ($tables as $table) {
                $table = $table[0];

                $i_file = str_pad($i, 6, "0", STR_PAD_LEFT);

                $tableDump = "DROP TABLE IF EXISTS `{$table}`;\n";

                // Get the table creation statement
                $createTable = $wpdb->get_row("SHOW CREATE TABLE `$table`", ARRAY_N);
                $tableDump   .= $createTable[1] . ";\n\n";

                $wp_filesystem->put_contents(
                    $backupFolder . "/" . $i_file . "_" . $table . ".sql", $tableDump, 777
                );

                // Prepare the CSV content
                $sqlFilePath = $backupFolder . "/" . $i_file . "_" . $table . "_data.sql";

                $rows = $wpdb->get_results("SELECT * FROM `$table`", ARRAY_A);

                $insertData = "";
                foreach ($rows as $row) {
                    $values = array_map(function ($value) use ($wpdb) {
                        return isset($value) ? "'" . addslashes($value) . "'" : "NULL";
                    }, array_values($row));

                    $insertData .= "INSERT INTO `$table` VALUES (" . implode(", ", $values) . ");\n";
                }

                // Append data to the file
                $wp_filesystem->put_contents($sqlFilePath, $insertData, 777);

                $i++;

            }

            $listOfFilesAndFolders = glob($backupFolder . "/*");

            // Zip the SQL/CSV files
            if (!class_exists("ZipArchive")) {
                return false;
            } else {
                // Start the ZIP creation process
                $archive = new xagio_ZipArchiveX();
                if ($archive->open(
                        $location, ZipArchive::CREATE | ZipArchive::OVERWRITE
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

            return $location;
        }

        // Functions that handle SQL files
        public static function remove_comments(&$output)
        {
            $lines  = explode("\n", $output);
            $output = "";

            // try to keep mem. use down
            $linecount = count($lines);

            $in_comment = false;
            for ($i = 0; $i < $linecount; $i++) {
                if (preg_match("/^\/\*/", preg_quote($lines[$i]))) {
                    $in_comment = true;
                }

                if (!$in_comment) {
                    $output .= $lines[$i] . "\n";
                }

                if (preg_match("/\*\/$/", preg_quote($lines[$i]))) {
                    $in_comment = false;
                }
            }

            unset($lines);
            return $output;
        }

        public static function remove_remarks($sql)
        {
            $lines = explode("\n", $sql);

            // try to keep mem. use down
            $sql = "";

            $linecount = count($lines);
            $output    = "";

            for ($i = 0; $i < $linecount; $i++) {
                if ($i != $linecount - 1 || strlen($lines[$i]) > 0) {
                    if (isset($lines[$i][0]) && $lines[$i][0] != "#") {
                        $output .= $lines[$i] . "\n";
                    } else {
                        $output .= "\n";
                    }
                    // Trading a bit of speed for lower mem. use here.
                    $lines[$i] = "";
                }
            }

            return $output;
        }

        public static function split_sql_file($sql, $delimiter)
        {
            // Split up our string into "possible" SQL statements.
            $tokens = explode($delimiter, $sql);

            // try to save mem.
            $sql    = "";
            $output = [];

            // we don't actually care about the matches preg gives us.
            $matches = [];

            // this is faster than calling count($oktens) every time thru the loop.
            $token_count = count($tokens);
            for ($i = 0; $i < $token_count; $i++) {
                // Don't wanna add an empty string as the last thing in the array.
                if ($i != $token_count - 1 || strlen($tokens[$i] > 0)) {
                    // This is the total number of single quotes in the token.
                    $total_quotes = preg_match_all(
                        "/'/", $tokens[$i], $matches
                    );
                    // Counts single quotes that are preceded by an odd number of backslashes,
                    // which means they're escaped quotes.
                    $escaped_quotes = preg_match_all(
                        "/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$i], $matches
                    );

                    $unescaped_quotes = $total_quotes - $escaped_quotes;

                    // If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
                    if ($unescaped_quotes % 2 == 0) {
                        // It's a complete sql statement.
                        $output[] = $tokens[$i];
                        // save memory.
                        $tokens[$i] = "";
                    } else {
                        // incomplete sql statement. keep adding tokens until we have a complete one.
                        // $temp will hold what we have so far.
                        $temp = $tokens[$i] . $delimiter;
                        // save memory..
                        $tokens[$i] = "";

                        // Do we have a complete statement yet?
                        $complete_stmt = false;

                        for ($j = $i + 1; !$complete_stmt && $j < $token_count; $j++) {
                            // This is the total number of single quotes in the token.
                            $total_quotes = preg_match_all(
                                "/'/", $tokens[$j], $matches
                            );
                            // Counts single quotes that are preceded by an odd number of backslashes,
                            // which means they're escaped quotes.
                            $escaped_quotes = preg_match_all(
                                "/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$j], $matches
                            );

                            $unescaped_quotes = $total_quotes - $escaped_quotes;

                            if ($unescaped_quotes % 2 == 1) {
                                // odd number of unescaped quotes. In combination with the previous incomplete
                                // statement(s), we now have a complete statement. (2 odds always make an even)
                                $output[] = $temp . $tokens[$j];

                                // save memory.
                                $tokens[$j] = "";
                                $temp       = "";

                                // exit the loop.
                                $complete_stmt = true;
                                // make sure the outer loop continues at the right point.
                                $i = $j;
                            } else {
                                // even number of unescaped quotes. We still don't have a complete statement.
                                // (1 odd and 1 even always make an odd)
                                $temp .= $tokens[$j] . $delimiter;
                                // save memory.
                                $tokens[$j] = "";
                            }
                        } // for..
                    } // else
                }
            }

            return $output;
        }

        public static function getName($url, $only_host = false)
        {
            $parts = wp_parse_url($url);
            if ($only_host) {
                $name = $parts["host"];
            } else {
                $name = str_replace(".", "_", $parts["host"]);
            }
            return $name;
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
            if (!defined("XAGIO_GoogleDrive_KEY")) {
                define(
                    "XAGIO_GoogleDrive_KEY", isset($secret_keys["googledrive"]) ? $secret_keys["googledrive"]["public"] : false
                );
            }
            if (!defined("XAGIO_GoogleDrive_SECRET")) {
                define(
                    "XAGIO_GoogleDrive_SECRET", isset($secret_keys["googledrive"]) ? $secret_keys["googledrive"]["private"] : false
                );
            }
            /** Backup Application Keys */
        }
    }
}
