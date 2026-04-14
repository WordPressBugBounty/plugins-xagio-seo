<?php
if (!defined('ABSPATH'))
    exit;

if (!class_exists('XAGIO_MODEL_ELEMENTOR_BACKUP')) {
    class XAGIO_MODEL_ELEMENTOR_BACKUP
    {
        const META_KEY_SOURCE = '_elementor_data';
        const META_KEY_BACKUPS = '_elementor_data_backup';
        const MAX_BACKUPS = 25;

        protected static $change_type = 'manual';
        protected static $suspend     = false;

        public static function initialize()
        {
            add_filter('update_post_metadata', [
                __CLASS__,
                'capture_and_backup'
            ], 10, 5);
            add_filter('add_post_metadata', [
                __CLASS__,
                'capture_on_add'
            ], 10, 5);


            // List backups
            add_action('admin_post_xagio_get_elementor_backups', function () {
                $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
                if (!$post_id) {
                    wp_send_json_error(['message' => 'Missing post_id']);
                }

                if (!current_user_can('edit_post', $post_id)) {
                    wp_send_json_error(['message' => 'Permission denied'], 403);
                }

                $xagio_backups = get_post_meta($post_id, '_elementor_data_backup', true);
                if (!is_array($xagio_backups)) {
                    $xagio_backups = [];
                }

                wp_send_json_success($xagio_backups); // JS expects res.data to be the array
            });

            // Restore by index
            add_action('admin_post_xagio_restore_elementor_backup', function () {
                $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
                $index   = isset($_POST['index']) ? intval($_POST['index']) : -1;
                if (!$post_id) {
                    wp_send_json_error(['message' => 'Missing post_id']);
                }

                if (!current_user_can('edit_post', $post_id)) {
                    wp_send_json_error(['message' => 'Permission denied'], 403);
                }

                $ok = class_exists('XAGIO_MODEL_ELEMENTOR_BACKUP') && XAGIO_MODEL_ELEMENTOR_BACKUP::restore_version($post_id, $index);

                if ($ok) {
                    wp_send_json_success(true);
                } else {
                    wp_send_json_error(['message' => 'Restore failed or no backups']);
                }
            });

            // Delete ONE backup by index
            add_action('admin_post_xagio_delete_elementor_backup', function () {
                $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
                $index   = isset($_POST['index']) ? intval($_POST['index']) : -1;
                if (!$post_id) wp_send_json_error(['message' => 'Missing post_id']);
                if (!current_user_can('edit_post', $post_id)) wp_send_json_error(['message'=>'Permission denied'], 403);

                $ok = class_exists('XAGIO_MODEL_ELEMENTOR_BACKUP') && XAGIO_MODEL_ELEMENTOR_BACKUP::delete_version($post_id, $index);

                if ($ok) {
                    wp_send_json_success(true);
                } else {
                    wp_send_json_error(['message' => 'Delete failed or invalid index']);
                }
            });

            // Delete ALL backups (optional)
            add_action('admin_post_xagio_delete_all_elementor_backups', function () {
                $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
                if (!$post_id) wp_send_json_error(['message' => 'Missing post_id']);
                if (!current_user_can('edit_post', $post_id)) wp_send_json_error(['message'=>'Permission denied'], 403);

                $ok = class_exists('XAGIO_MODEL_ELEMENTOR_BACKUP') && XAGIO_MODEL_ELEMENTOR_BACKUP::delete_all($post_id);

                if ($ok) {
                    wp_send_json_success(true);
                } else {
                    wp_send_json_error(['message' => 'Delete-all failed']);
                }
            });


        }

        public static function set_change_type(?string $type): void
        {
            self::$change_type = $type ? trim($type) : null;
        }

        public static function restore_version(int $post_id, int $index): bool
        {
            $xagio_backups = get_post_meta($post_id, self::META_KEY_BACKUPS, true);
            if (!is_array($xagio_backups) || empty($xagio_backups))
                return false;

            if ($index < 0)
                $index = count($xagio_backups) + $index;
            if (!isset($xagio_backups[$index]['data']))
                return false;

            self::$suspend = true;
            $ok            = update_post_meta($post_id, self::META_KEY_SOURCE, $xagio_backups[$index]['data']);
            self::$suspend = false;

            if ($ok) {
                // Purge Elementor caches and rebuild CSS for this post
                self::clear_elementor_cache_for_post($post_id);
            }

            return (bool)$ok;
        }

        public static function restore_latest(int $post_id): bool
        {
            return self::restore_version($post_id, -1);
        }

        public static function get_backups(int $post_id): array
        {
            $xagio_backups = get_post_meta($post_id, self::META_KEY_BACKUPS, true);
            return is_array($xagio_backups) ? $xagio_backups : [];
        }

        protected static function clear_elementor_cache_for_post(int $post_id): void {
            // Bail if Elementor isn't loaded
            if ( ! did_action('elementor/loaded') ) {
                return;
            }

            try {
                // 1) Global cache clear (CSS files etc.)
                $plugin = \Elementor\Plugin::$instance;

                if ( isset($plugin->files_manager) && method_exists($plugin->files_manager, 'clear_cache') ) {
                    // Clears all generated CSS files and related caches
                    $plugin->files_manager->clear_cache();
                }

                // 2) Clear this post's CSS file and regenerate it
                if ( class_exists('\Elementor\Core\Files\CSS\Post') ) {
                    $post_css = \Elementor\Core\Files\CSS\Post::create($post_id);

                    // Delete/clear any existing CSS artifact
                    if ( method_exists($post_css, 'delete') ) {
                        $post_css->delete();
                    } elseif ( method_exists($post_css, 'clear_cache') ) {
                        $post_css->clear_cache();
                    }

                    // Rebuild CSS from the restored content
                    if ( method_exists($post_css, 'update') ) {
                        $post_css->update();
                    }
                }

                // 3) (Optional) Nuke any in-memory caches for the post
                clean_post_cache($post_id);

            } catch ( \Throwable $e ) {
                // Silently ignore; you can log if you want:
                // error_log('[XAGIO Elementor Backup] Cache clear failed: ' . $e->getMessage());
            }
        }


        /**
         * Fires when updating existing meta.
         * Signature: ($check, $object_id, $meta_key, $meta_value, $prev_value)
         */
        public static function capture_and_backup($check, $object_id, $meta_key, $meta_value, $prev_value)
        {
            if (self::$suspend || $meta_key !== self::META_KEY_SOURCE || self::$change_type == 'update')
                return $check;

            $old_json = get_post_meta($object_id, self::META_KEY_SOURCE, true);

            // Normalize to strings to compare
            $new_json = is_string($meta_value) ? $meta_value : (string)$meta_value;
            $old_json = is_string($old_json) ? $old_json : (string)$old_json;

            if ($new_json === $old_json)
                return $check; // no change, no snapshot

            self::append_backup($object_id, $old_json, 'update');

            // return $check to allow WP to continue the update normally
            return $check;
        }

        /**
         * Fires when adding meta for the first time.
         * Signature: ($check, $object_id, $meta_key, $meta_value, $unique)
         */
        public static function capture_on_add($check, $object_id, $meta_key, $meta_value, $unique)
        {
            if (self::$suspend || $meta_key !== self::META_KEY_SOURCE)
                return $check;

            // No previous value yet; store empty/previous as empty string
            $old_json = get_post_meta($object_id, self::META_KEY_SOURCE, true);
            $old_json = is_string($old_json) ? $old_json : (string)$old_json;

            if ($old_json !== '') {
                // In case something already existed (edge case), still snapshot
                self::append_backup($object_id, $old_json, 'add');
            } else {
                // Optionally create a bootstrap entry with empty previous value:
                // self::append_backup($object_id, '', 'add');
            }

            return $check;
        }

        protected static function append_backup(int $post_id, string $previous_json, string $op): void
        {
            $entry = [
                'date' => current_time('mysql'),
                'type' => self::$change_type ?: $op,
                'by'   => get_current_user_id() ?: 0,
                'data' => $previous_json,
            ];

            $xagio_backups = get_post_meta($post_id, self::META_KEY_BACKUPS, true);
            if (!is_array($xagio_backups))
                $xagio_backups = [];
            $xagio_backups[] = $entry;

            if (self::MAX_BACKUPS > 0 && count($xagio_backups) > self::MAX_BACKUPS) {
                $xagio_backups = array_slice($xagio_backups, -self::MAX_BACKUPS);
            }

            update_post_meta($post_id, self::META_KEY_BACKUPS, $xagio_backups);
            self::$change_type = null; // reset after each write
        }

        public static function delete_version(int $post_id, int $index): bool {
            $xagio_backups = get_post_meta($post_id, self::META_KEY_BACKUPS, true);
            if (!is_array($xagio_backups) || empty($xagio_backups)) return false;

            if ($index < 0) $index = count($xagio_backups) + $index;
            if (!isset($xagio_backups[$index])) return false;

            array_splice($xagio_backups, $index, 1);
            return (bool) update_post_meta($post_id, self::META_KEY_BACKUPS, $xagio_backups);
        }

        public static function delete_all(int $post_id): bool {
            return (bool) update_post_meta($post_id, self::META_KEY_BACKUPS, []);
        }

    }
}
