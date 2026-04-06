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

            $xagio_http_code = 0;
            $xagio_result    = XAGIO_API::apiRequest(
                $endpoint = 'info', $method = 'GET', [
                'type' => 'account',
            ], $xagio_http_code
            );

            if ($xagio_http_code == 200) {
                update_option('XAGIO_ACCOUNT_DETAILS', $xagio_result);
            }

        }

        public static function getSharedScripts()
        {
            $xagio_http_code = 0;
            $xagio_result    = XAGIO_API::apiRequest(
                $endpoint = 'info', $method = 'GET', [
                'type' => 'shared_scripts',
            ], $xagio_http_code
            );

            if ($xagio_http_code == 200) {
                update_option('XAGIO_SHARED_SCRIPTS', $xagio_result['shared_scripts']);
            }
        }

        public static function getBackupSettings()
        {
            $xagio_http_code = 0;
            $xagio_result    = XAGIO_API::apiRequest(
                $endpoint = 'info', $method = 'GET', [
                'type' => 'backup_settings'
            ], $xagio_http_code
            );

            if ($xagio_http_code == 200) {
                update_option('XAGIO_BACKUP_SETTINGS', $xagio_result['backup_settings']);
            }

            return $xagio_result;
        }

        public static function updateBackupSettings()
        {
            $xagio_http_code = 0;
            $xagio_result    = XAGIO_API::apiRequest(
                $endpoint = 'info', $method = 'POST', [

                'type'            => 'backup_settings',
                'settings'        => get_option('XAGIO_BACKUP_SETTINGS'),
                'domain'          => XAGIO_DOMAIN,
                'domain_settings' => [
                    'location' => get_option("XAGIO_BACKUP_LOCATION"),
                    'limit'    => get_option("XAGIO_BACKUP_LIMIT"),
                    'date'     => get_option("XAGIO_BACKUP_DATE"),
                    'next'     => wp_next_scheduled('xagio_doBackup')
                ]

            ], $xagio_http_code
            );

            return $xagio_result;
        }

        public static function getAPIKeys()
        {
            $xagio_http_code = 0;
            $xagio_result    = XAGIO_API::apiRequest(
                $endpoint = 'info', $method = 'GET', [
                'type' => 'api_settings',
            ], $xagio_http_code
            );

            if ($xagio_http_code == 200) {
                update_option('XAGIO_API_SETTINGS', $xagio_result['api_settings']);
            }
        }

    }
}