<?php
/**
 * Snapshot: chụp/khôi phục trạng thái plugin vào CPT twc_snapshot.
 */
if (!defined('ABSPATH')) { exit; }

class TWC_Snapshot {
    // INIT
    public static function init() {
    }

    public static function create_snapshot($action_name = 'Manual Snapshot') {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);

        $branding_data = [
            'site_title'  => get_option('blogname'),
            'admin_email' => get_option('admin_email'),
            'favicon'     => get_option('twc_branding_favicon_url'),
            'logo_url'    => get_option('twc_website_logo_url'),
        ];

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        $data = [
            'active_plugins' => $active_plugins,
            'branding'       => $branding_data,
            'timestamp'      => current_time('mysql'),
            'created_by'     => [
                'user_id'  => $user_id,
                'username' => $user ? $user->user_login : 'system',
                'role'     => $user ? $user->roles[0] : 'unknown',
            ],
        ];

        $post_id = wp_insert_post([
            'post_type'    => 'twc_snapshot',
            'post_title'   => 'Audit: ' . $action_name . ' [' . date('Y-m-d H:i') . ']',
            'post_content' => wp_json_encode($data, JSON_UNESCAPED_UNICODE),
            'post_status'  => 'publish',
        ]);

        // Ghi log vào TWC_Logger ngay khi tạo snapshot
        // TWC_Logger::log('Snapshot', "Đã tạo snapshot bởi {$data['created_by']['username']} ({$data['created_by']['role']})");

        return $post_id;
    }

    public static function list_snapshots($limit = 50) {
        $q = new WP_Query([
            'post_type' => 'twc_snapshot',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
        ]);
        return $q->posts;
    }

    public static function restore_snapshot($snapshot_id) {
        $post = get_post($snapshot_id);
        if (!$post || $post->post_type !== 'twc_snapshot') { return false; }
        $data = json_decode($post->post_content, true);
        if (!is_array($data) || !isset($data['active_plugins'])) { return false; }
        update_option('active_plugins', $data['active_plugins'], false);
        return true;
    }
}