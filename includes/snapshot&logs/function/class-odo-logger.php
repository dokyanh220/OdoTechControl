<?php
/**
 * Logger: ghi nhật ký các hành động của plugin vào CPT odo_log.
 */
if (!defined('ABSPATH')) { exit; }

class odo_Logger {
    // INIT
    public static function init() {
        // Helper lấy tên plugin
        $get_plugin_name = function($plugin_path) {
            if (!function_exists('get_plugin_data')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            $full_path = WP_PLUGIN_DIR . '/' . $plugin_path;
            if (file_exists($full_path)) {
                $data = get_plugin_data($full_path);
                return $data['Name'] ?? $plugin_path;
            }
            return $plugin_path;
        };

        // 1. Theo dõi khi Plugin được kích hoạt
        add_action('activated_plugin', function($plugin_path) use ($get_plugin_name) {
            $user = wp_get_current_user();
            $role = !empty($user->roles) ? $user->roles[0] : 'unknown';
            $plugin_name = $get_plugin_name($plugin_path);
            
            $msg = sprintf('%s (%s) đã KÍCH HOẠT plugin: %s', $user->user_login, $role, $plugin_name);
            odo_Logger::log('Plugin Activation', $msg, ['user' => $user->user_login, 'role' => $role]);
            odo_Snapshot::create_snapshot("Plugin Activated: $plugin_name");
        });

        // 2. Theo dõi khi Plugin bị hủy kích hoạt
        add_action('deactivated_plugin', function($plugin_path) use ($get_plugin_name) {
            $user = wp_get_current_user();
            $role = !empty($user->roles) ? $user->roles[0] : 'unknown';
            $plugin_name = $get_plugin_name($plugin_path);

            $msg = sprintf('%s (%s) đã HỦY KÍCH HOẠT plugin: %s', $user->user_login, $role, $plugin_name);
            odo_Logger::log('Plugin Deactivation', $msg, ['user' => $user->user_login, 'role' => $role]);
            odo_Snapshot::create_snapshot("Plugin Deactivated: $plugin_name");
        });

        // 3. Theo dõi Cài đặt / Cập nhật Plugin
        add_action('upgrader_process_complete', function($upgrader_object, $options) use ($get_plugin_name) {
            $user = wp_get_current_user();
            $role = !empty($user->roles) ? $user->roles[0] : 'unknown';

            if (isset($options['type']) && $options['type'] === 'plugin') {
                // Cài đặt mới
                if (isset($options['action']) && $options['action'] === 'install') {
                    $msg = sprintf('%s (%s) đã CÀI ĐẶT một plugin mới', $user->user_login, $role);
                    odo_Logger::log('Plugin Install', $msg, ['user' => $user->user_login, 'role' => $role]);
                    odo_Snapshot::create_snapshot("Plugin Installed");
                }
                // Cập nhật
                elseif (isset($options['action']) && $options['action'] === 'update') {
                    if (!empty($options['plugins'])) {
                        foreach ($options['plugins'] as $plugin_path) {
                            $plugin_name = $get_plugin_name($plugin_path);
                            $msg = sprintf('%s (%s) đã CẬP NHẬT plugin: %s', $user->user_login, $role, $plugin_name);
                            odo_Logger::log('Plugin Update', $msg, ['user' => $user->user_login, 'role' => $role]);
                        }
                        odo_Snapshot::create_snapshot("Plugins Updated");
                    }
                }
            }
        }, 10, 2);

        // 4. Theo dõi Xóa Plugin
        add_action('deleted_plugin', function($plugin_path, $is_deleted) {
            if ($is_deleted) {
                $user = wp_get_current_user();
                $role = !empty($user->roles) ? $user->roles[0] : 'unknown';
                
                $msg = sprintf('%s (%s) đã XÓA plugin: %s', $user->user_login, $role, $plugin_path);
                odo_Logger::log('Plugin Delete', $msg, ['user' => $user->user_login, 'role' => $role]);
                odo_Snapshot::create_snapshot("Plugin Deleted: $plugin_path");
            }
        }, 10, 2);
    }

    public static function log($action, $message, $extra = []) {
        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $username = $user ? $user->user_login : 'unknown';
        
        // Lấy role của user
        $roles = $user && !empty($user->roles) ? $user->roles : ['guest'];
        $role = $roles[0];
        
        $title = '[' . sanitize_text_field($action) . '] ' . wp_strip_all_tags($message);
        $content = wp_json_encode([
            'user_id' => $user_id,
            'username' => $username,
            'role' => $role,
            'message' => $message,
            'extra' => $extra,
            'time' => current_time('mysql'),
            'ip' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '',
        ], JSON_UNESCAPED_UNICODE);

        $post_id = wp_insert_post([
            'post_type' => 'odo_log',
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
        ], true);

        return !is_wp_error($post_id);
    }

    public static function get_logs($limit = 50) {
        $q = new WP_Query([
            'post_type' => 'odo_log',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        return $q->posts;
    }

    public static function clear_logs() {
        $q = new WP_Query([
            'post_type' => 'odo_log',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);
        foreach ($q->posts as $pid) {
            wp_delete_post($pid, true);
        }
        return true;
    }
}