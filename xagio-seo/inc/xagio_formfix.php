<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * One-time cleanup of Elementor form recipients that old Xagio template kits hardcoded to our own
 * addresses (admin@xagio.net, *@templates.xagio.net, old build domains). Those sites sent their
 * leads to us instead of the site owner.
 *
 * An emptied email_to / email_from makes Elementor Pro fall back to the site's admin_email, so the
 * owner gets the leads with nothing to configure. Only our addresses are removed: an address the
 * customer entered and any [shortcode] stay as they are.
 */
if (!class_exists('XAGIO_FORMFIX')) {

    class XAGIO_FORMFIX
    {
        const DONE_OPTION  = 'XAGIO_FORMFIX_DONE';
        const STATE_OPTION = 'XAGIO_FORMFIX_STATE';
        const CRON_HOOK    = 'xagio_formfix';
        const BATCH_SIZE   = 100;
        const TIME_LIMIT   = 20; // seconds per cron run, the rest continues in the next run

        private static $fields = [
            'email_to',
            'email_to_2',
            'email_reply_to',
            'email_reply_to_2',
            'email_from',
            'email_from_2',
        ];

        // Exact addresses
        private static $addresses = [
            'admin@xagio.net',
            'email@xagio.net',
            'admin@xagio.com',
            'ahsaanexperts@gmail.com',
        ];

        // Exact domains
        private static $domains = [
            'templates.xagio.net',
            'templates.net',
        ];

        // Domains together with all of their subdomains (old template build sites)
        private static $domain_families = [
            'tvsizeguide.com',
            'nexagepro.com',
        ];

        public static function initialize()
        {
            add_action(self::CRON_HOOK, [
                'XAGIO_FORMFIX',
                'runFix'
            ]);

            if (!get_option(self::DONE_OPTION) && !wp_next_scheduled(self::CRON_HOOK)) {
                wp_schedule_single_event(time(), self::CRON_HOOK);
            }
        }

        public static function runFix()
        {
            global $wpdb;

            if (get_option(self::DONE_OPTION)) {
                return;
            }

            $state = get_option(self::STATE_OPTION);
            if (!is_array($state)) {
                $state = [
                    'cursor' => 0,
                    'posts'  => 0,
                    'forms'  => 0,
                    'fields' => 0,
                    'errors' => 0,
                ];
            }

            $started = time();

            while (true) {

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $rows = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT pm.meta_id, pm.post_id, pm.meta_value
                         FROM {$wpdb->postmeta} pm
                         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                         WHERE pm.meta_key = '_elementor_data'
                           AND p.post_type <> 'revision'
                           AND pm.meta_value LIKE %s
                           AND pm.meta_id > %d
                         ORDER BY pm.meta_id ASC
                         LIMIT %d",
                        '%' . $wpdb->esc_like('"widgetType":"form"') . '%',
                        $state['cursor'],
                        self::BATCH_SIZE
                    ),
                    ARRAY_A
                );

                if (empty($rows)) {
                    break;
                }

                foreach ($rows as $row) {
                    $state['cursor'] = (int)$row['meta_id'];

                    $data = json_decode($row['meta_value'], true);
                    if (!is_array($data)) {
                        $state['errors']++;
                        continue;
                    }

                    $forms  = 0;
                    $fields = 0;
                    $data   = self::cleanElements($data, $forms, $fields);

                    if ($fields == 0) {
                        continue;
                    }

                    // Keeps the previous version in _elementor_data_backup, restorable from the AI module
                    if (class_exists('XAGIO_MODEL_ELEMENTOR_BACKUP')) {
                        XAGIO_MODEL_ELEMENTOR_BACKUP::set_change_type('form_recipient_fix');
                    }

                    update_post_meta((int)$row['post_id'], '_elementor_data', wp_slash(wp_json_encode($data)));

                    $state['posts']++;
                    $state['forms']  += $forms;
                    $state['fields'] += $fields;
                }

                if (time() - $started >= self::TIME_LIMIT) {
                    // Continue where we stopped in the next cron run
                    update_option(self::STATE_OPTION, $state, false);
                    wp_schedule_single_event(time() + 60, self::CRON_HOOK);
                    return;
                }
            }

            self::removeCachedTemplates();

            if ($state['fields'] > 0) {
                self::clearElementorCache();
            }

            unset($state['cursor']);

            update_option(self::DONE_OPTION, $state);
            delete_option(self::STATE_OPTION);

            xagio_log('info', 'Elementor form recipients fixed: ' . wp_json_encode($state));

            if ($state['fields'] > 0 && XAGIO_LICENSE::isLicenseSet()) {
                $xagio_http_code = 0;
                XAGIO_API::apiRequest('log', 'POST', [
                    'action' => 'elementor_form_fix',
                    'data'   => wp_json_encode($state),
                ], $xagio_http_code);
            }
        }

        /**
         * Walks the Elementor element tree and cleans the email settings of every form widget.
         */
        private static function cleanElements($elements, &$forms, &$fields)
        {
            if (!is_array($elements)) {
                return $elements;
            }

            if (isset($elements['widgetType']) && $elements['widgetType'] === 'form' && isset($elements['settings']) && is_array($elements['settings'])) {
                $changed = 0;

                foreach (self::$fields as $field) {
                    if (!isset($elements['settings'][$field]) || !is_string($elements['settings'][$field])) {
                        continue;
                    }

                    $value = self::cleanValue($elements['settings'][$field]);
                    if ($value !== $elements['settings'][$field]) {
                        $elements['settings'][$field] = $value;
                        $changed++;
                    }
                }

                if ($changed > 0) {
                    $forms++;
                    $fields += $changed;
                }
            }

            foreach ($elements as $key => $child) {
                if (is_array($child)) {
                    $elements[$key] = self::cleanElements($child, $forms, $fields);
                }
            }

            return $elements;
        }

        /**
         * Removes our addresses from a (possibly comma-separated) address field and keeps everything else.
         * Returns the original string when nothing in it is ours.
         */
        private static function cleanValue($value)
        {
            // Shortcodes such as [field id="email"] are never touched
            if (strpos($value, '[') !== false) {
                return $value;
            }

            $parts = array_map('trim', explode(',', $value));
            $kept  = [];
            foreach ($parts as $part) {
                if (!self::isOurAddress($part)) {
                    $kept[] = $part;
                }
            }

            if (count($kept) === count($parts)) {
                return $value;
            }

            return implode(', ', array_filter($kept, 'strlen'));
        }

        private static function isOurAddress($address)
        {
            $address = strtolower(trim($address));

            if (!preg_match('/^[^@\s]+@([a-z0-9.-]+)$/', $address, $matches)) {
                return false;
            }

            if (in_array($address, self::$addresses, true)) {
                return true;
            }

            $domain = $matches[1];
            if (in_array($domain, self::$domains, true)) {
                return true;
            }

            foreach (self::$domain_families as $family) {
                if ($domain === $family || substr($domain, -strlen('.' . $family)) === '.' . $family) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Agent X caches downloaded template ZIPs in the plugin folder. A cached copy from before the
         * template fix would bring the old recipients back on the next install, so it is downloaded again.
         */
        private static function removeCachedTemplates()
        {
            $cached = glob(XAGIO_PATH . '/templates/elementor/*.zip');
            if (!empty($cached)) {
                foreach ($cached as $file) {
                    wp_delete_file($file);
                }
            }
        }

        private static function clearElementorCache()
        {
            if (!did_action('elementor/loaded') || !class_exists('\Elementor\Plugin')) {
                return;
            }

            try {
                $plugin = \Elementor\Plugin::$instance;
                if (isset($plugin->files_manager) && method_exists($plugin->files_manager, 'clear_cache')) {
                    $plugin->files_manager->clear_cache();
                }
            } catch (\Throwable $e) {
                xagio_log('error', 'Elementor cache clear after form fix failed: ' . $e->getMessage());
            }
        }
    }

}
