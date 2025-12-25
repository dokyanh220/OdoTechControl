<?php
/**
 * Baseline bảo mật
 * - Tắt XML-RPC
 * - Ẩn WP version
 * - REST API chỉ cho login
 * - Chặn author enumeration
 */
if (!defined('ABSPATH')) { exit; }

class TWC_Security {
    public static function init() {
        $basic = get_option('twc_security_basic', []);

        // Tắt XML-RPC nếu được bật
        if (!empty($basic['disable_xmlrpc'])) {
            add_filter('xmlrpc_enabled', '__return_false');
        }

        // Ẩn phiên bản WordPress
        if (!empty($basic['hide_wp_version'])) {
            remove_action('wp_head', 'wp_generator');
            add_filter('the_generator', '__return_empty_string');
        }

        // Chặn dò tìm username qua ?author=1
        if (!empty($basic['block_author_enum'])) {
            add_action('template_redirect', [__CLASS__, 'block_author_enum']);
        }

        // Bảo vệ REST API
        add_filter('rest_authentication_errors', [__CLASS__, 'rest_auth_errors']);

        // Chặn theme options và plugin installation cho non-adminTigo123
        add_action('admin_init', [__CLASS__, 'restrict_theme_and_plugins']);
    }

    public static function rest_auth_errors($result) {
        if (is_user_logged_in()) return $result;

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';

        // Cho phép oembed
        if (strpos($request_uri, '/wp-json/oembed/') !== false) {
            return $result;
        }

        return new WP_Error(
            'rest_forbidden',
            __('REST API đã được bảo vệ.', 'tigoweb-control'),
            ['status' => 401]
        );
    }

    public static function block_author_enum() {
        if (is_admin()) { return; }
        if (isset($_GET['author']) && is_numeric($_GET['author'])) {
            wp_redirect(home_url(), 301);
            exit;
        }
    }

    public static function apply_security_baseline() {
        update_option('twc_security_basic', [
            'disable_xmlrpc'    => true,
            'hide_wp_version'   => true,
            'block_author_enum' => true,
        ], false);

        return true;
    }

    public static function apply_security_basic($params) {
        $config = [
            'disable_xmlrpc'    => !empty($params['disable_xmlrpc']),
            'hide_wp_version'   => !empty($params['hide_wp_version']),
            'block_author_enum' => !empty($params['block_author_enum']),
        ];

        update_option('twc_security_basic', $config, false);
        return true;
    }

    // Hạn chế truy cập theme và plugin cho client
    public static function restrict_theme_and_plugins() {
        $current_user = wp_get_current_user();
        
        // Kiểm tra role: admin và twc_technical được phép
        $allowed_roles = ['administrator', 'twc_technical'];
        $user_roles = (array) $current_user->roles;
        $has_permission = !empty(array_intersect($user_roles, $allowed_roles));
        
        // Nếu có quyền thì cho phép
        if ($has_permission) {
            return;
        }

        // Chặn UX Builder
        if (isset($_GET['page']) && $_GET['page'] === 'ux_builder') {
            wp_die(__('Bạn không thể truy cập UX Builder. Liên hệ Administrator và Technical để hỗ trợ', 'tigoweb-control'));
        }

        // Chặn theme customizer/options
        global $pagenow;
        if ($pagenow === 'customize.php' || $pagenow === 'themes.php') {
            wp_die(__('Bạn không thể truy cập theme options. Liên hệ Administrator và Technical để hỗ trợ', 'tigoweb-control'));
        }

        // Chặn plugin installation/upload
        if ($pagenow === 'plugin-install.php' || ($pagenow === 'plugins.php' && isset($_GET['action']) && $_GET['action'] === 'upload')) {
            wp_die(__('Bạn không thể tải plugin. Liên hệ Administrator và Technical để hỗ trợ', 'tigoweb-control'));
        }

        // Chặn plugin upload action
        if (isset($_FILES['pluginzip'])) {
            wp_die(__('Bạn không thể tải plugin. Liên hệ Administrator và Technical để hỗ trợ.', 'tigoweb-control'));
        }
    }
}
