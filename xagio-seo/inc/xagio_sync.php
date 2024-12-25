<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

if (!class_exists('XAGIO_SYNC')) {

    class XAGIO_SYNC
    {

        public static function initialize()
        {
            add_action('xagio_getAPIKeys', [
                'XAGIO_SYNC',
                'getAPIKeys'
            ]);
            if (!wp_next_scheduled('xagio_getAPIKeys')) {
                wp_schedule_event(time(), 'daily', 'xagio_getAPIKeys');
            }

            add_action('xagio_getBackupSettings', [
                'XAGIO_SYNC',
                'getBackupSettings'
            ]);
            if (!wp_next_scheduled('xagio_getBackupSettings')) {
                wp_schedule_event(time(), 'daily', 'xagio_getBackupSettings');
            }

            add_action('xagio_getSharedScripts', [
                'XAGIO_SYNC',
                'getSharedScripts'
            ]);
            if (!wp_next_scheduled('xagio_getSharedScripts')) {
                wp_schedule_event(time(), 'daily', 'xagio_getSharedScripts');
            }

            if (!get_option('XAGIO_ACCOUNT_DETAILS')) {
                self::getMembershipInfo();
            }

            add_action('xagio_getMembershipInfo', [
                'XAGIO_SYNC',
                'getMembershipInfo'
            ]);
            if (!wp_next_scheduled('xagio_getMembershipInfo')) {
                wp_schedule_event(time(), 'daily', 'xagio_getMembershipInfo');
            }
        }

        public static function getMembershipInfo()
        {

            $http_code = 0;
            $result    = XAGIO_API::apiRequest(
                $endpoint = 'info', $method = 'GET', [
                    'type' => 'account',
                ], $http_code
            );

            if ($http_code == 200) {
                update_option('XAGIO_ACCOUNT_DETAILS', $result);
            }

        }

        public static function getSharedScripts()
        {
            $http_code = 0;
            $result    = XAGIO_API::apiRequest(
                $endpoint = 'info', $method = 'GET', [
                    'type' => 'shared_scripts',
                ], $http_code
            );

            if ($http_code == 200) {
                update_option('XAGIO_SHARED_SCRIPTS', $result['shared_scripts']);
            }
        }

        public static function getBackupSettings()
        {
            $http_code = 0;
            $result    = XAGIO_API::apiRequest(
                $endpoint = 'info', $method = 'GET', [
                    'type' => 'backup_settings',
                ], $http_code
            );

            if ($http_code == 200) {
                update_option('XAGIO_BACKUP_SETTINGS', $result['backup_settings']);
            }

            return $result;
        }

        public static function updateBackupSettings()
        {
            $http_code = 0;
            $result    = XAGIO_API::apiRequest(
                $endpoint = 'info', $method = 'POST', [
                    'type'            => 'backup_settings',
                    'settings'        => get_option('XAGIO_BACKUP_SETTINGS'),
                    'domain_settings' => [
                        'location' => get_option("XAGIO_BACKUP_LOCATION"),
                        'limit'    => get_option("XAGIO_BACKUP_LIMIT"),
                        'date'     => get_option("XAGIO_BACKUP_DATE")
                    ]

                ], $http_code
            );

            return $result;
        }

        public static function getAPIKeys()
        {
            $http_code = 0;
            $result    = XAGIO_API::apiRequest(
                $endpoint = 'info', $method = 'GET', [
                    'type' => 'api_settings',
                ], $http_code
            );

            if ($http_code == 200) {
                update_option('XAGIO_API_SETTINGS', $result['api_settings']);
            }
        }

    }
}