<?php
/**
 * Quản lý vai trò người dùng
 * - odo_client (Khách hàng): Quản lý branding, nội dung
 * - twc_technical (Kỹ thuật): Tất cả quyền như administrator
 */
if (!defined('ABSPATH')) { exit; }

class TWC_Roles {
    /**
     * Khởi tạo: đăng ký hook để đảm bảo roles tồn tại
     */
    public static function init() {
        add_action('init', [__CLASS__, 'ensure_roles']);
    }

    /**
     * Tạo/cập nhật các vai trò tùy chỉnh
     * - odo_client: Khách hàng (quản lý nội dung + branding)
     * - twc_technical: Kỹ thuật (toàn quyền như administrator)
     */
    public static function ensure_roles() {
        // Khách hàng
        $client_caps = [
            'read' => true, // cho phép đăng nhập
            'upload_files' => true, // cho phép upload media
            'edit_pages' => false, // không cho phép chỉnh sửa trang
            'publish_pages' => true, // cho phép xuất bản trang
            'moderate_comments' => true, // cho phép duyệt bình luận
            'manage_categories' => true, // cho phép quản lý chuyên mục
            'twc_manage_branding' => true, // cho phép quản lý branding qua Presets

            // Quyền quản lý sản phẩm (WooCommerce)
            'edit_products' => true,
            'read_products' => true,
            'delete_products' => true,
            'edit_others_products' => true,
            'publish_products' => true,
            'read_private_products' => true,
            'delete_others_products' => true,
            'delete_private_products' => true,
            'delete_published_products' => true,
            'edit_private_products' => true,
            'edit_published_products' => true,
            'manage_product_terms' => true,

            // Quyền đăng bài, chỉnh sửa bài viết
            'edit_posts' => true,
            'publish_posts' => true,
            'delete_posts' => true,
            'edit_published_posts' => true,
            'delete_published_posts' => true,
            'edit_others_posts' => true,
            'delete_others_posts' => true,
            'read_private_posts' => true,
            'manage_post_terms' => true,

            // Quyền hệ thống
            'manage_options' => false, // không cho phép quản lý tùy chọn chung
            'activate_plugins' => true, // cho phép kích hoạt plugin
            'install_plugins' => false, // không cho phép cài đặt plugin
            'switch_themes' => false, // không cho phép đổi giao diện
        ];

        if (!get_role('odo_client')) {
            add_role('odo_client', __('Client Manager', 'odotech-settings'), $client_caps);
        } else {
            $role = get_role('odo_client');
            foreach ($client_caps as $cap => $grant) {
                $role->add_cap($cap, $grant);
            }
        }

        // Kỹ thuật
        $tech_caps = [];
        $admin_role = get_role('administrator');

        if ($admin_role instanceof WP_Role && is_array($admin_role->capabilities)) {
            // Sao chép toàn bộ capabilities của administrator
            $tech_caps = $admin_role->capabilities;
        } else {
            // Các quyền admin phổ biến
            $tech_caps = [
                'read' => true,
                'manage_options' => true,
                'activate_plugins' => true,
                'install_plugins' => true,
                'update_plugins' => true,
                'delete_plugins' => true,
                'edit_plugins' => true,
                'switch_themes' => true,
                'edit_theme_options' => true,
                'install_themes' => true,
                'update_themes' => true,
                'delete_themes' => true,
                'edit_users' => true,
                'create_users' => true,
                'delete_users' => true,
                'list_users' => true,
                'promote_users' => true,
                'manage_categories' => true,
                'moderate_comments' => true,
                'edit_pages' => true,
                'publish_pages' => true,
                'delete_pages' => true,
                'edit_posts' => true,
                'publish_posts' => true,
                'delete_posts' => true,
                'upload_files' => true,
                'unfiltered_html' => true,
                'unfiltered_upload' => true,
                'update_core' => true,
                'manage_options' => true,
            ];
        }


        // Đảm bảo technical cũng có quyền branding riêng
        $tech_caps['twc_manage_branding'] = true;

        if (!get_role('twc_technical')) {
            add_role('twc_technical', __('Technical Manager', 'odotech-settings'), $tech_caps);
        } else {
            $role = get_role('twc_technical');
            // Gán toàn bộ capabilities của administrator cho twc_technical
            foreach ($tech_caps as $cap => $grant) {
                $role->add_cap($cap, (bool)$grant);
            }
        }

        return true;
    }
}