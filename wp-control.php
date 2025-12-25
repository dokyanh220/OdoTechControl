<?php
/**
 * Plugin Name: OdoTech Settings
 * Description: Cấu hình nhanh toàn bộ website WordPress cho khách hàng OdoTech.
 * Version: 1.0.0
 * Author: OdoTech Team dokyanh220
 */

if (!defined('ABSPATH')) { exit; }

// Phiên bản + đường dẫn
define('TWC_VERSION', '1.0.0');           // Phiên bản plugin hiện tại
define('TWC_PLUGIN_FILE', __FILE__);       // Đường dẫn đầy đủ tới file plugin chính
define('TWC_PLUGIN_DIR', plugin_dir_path(__FILE__));  // Đường dẫn thư mục plugin (có dấu / cuối)
define('TWC_PLUGIN_URL', plugin_dir_url(__FILE__));   // URL thư mục plugin (có dấu / cuối)

// Chặn sửa file plugin/theme qua editor (tùy chọn)
if (!defined('DISALLOW_FILE_EDIT')) { 
    define('DISALLOW_FILE_EDIT', true);  // Ẩn trình soạn thảo file trong WordPress admin
}
if (!defined('DISALLOW_FILE_MODS')) { 
    define('DISALLOW_FILE_MODS', false); // Vẫn cho phép cài đặt/cập nhật plugin/theme
}

require_once TWC_PLUGIN_DIR . 'includes/roles/class-twc-roles.php';
require_once TWC_PLUGIN_DIR . 'includes/security/function/class-twc-security.php';
require_once TWC_PLUGIN_DIR . 'includes/snapshot&logs/function/class-twc-logger.php';
require_once TWC_PLUGIN_DIR . 'includes/snapshot&logs/function/class-twc-snapshot.php';
require_once TWC_PLUGIN_DIR . 'includes/admin-pages/class-twc-admin-ui.php';
require_once TWC_PLUGIN_DIR . 'includes/presets/function/class-twc-presets.php';
require_once TWC_PLUGIN_DIR . 'includes/admin-pages/class-twc-admin-pages.php';

/**
 * Lớp chính: OdoTechSettings
 * - Đăng ký CPT (twc_log, twc_snapshot)
 * - Khởi tạo module
 * - Tạo menu admin (capability twc_manage_branding cho KHÁCH)
 * - Handlers admin-post (đổi prefix twc_*)
 */
class OdoTechSettings {
    public function __construct() {
        // Hook 'init' để đăng ký Custom Post Types
        add_action('init', [$this, 'register_cpts']);
        // Hook 'plugins_loaded' để khởi tạo các module sau khi tất cả plugin đã load
        add_action('plugins_loaded', [$this, 'init_modules']);
        // Hook 'admin_enqueue_scripts' để load CSS/JS cho admin
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        // Hook 'admin_menu' để tạo menu trong WordPress admin
        add_action('admin_menu', [$this, 'register_admin_menu']);

        // One-click handlers - xử lý các form submit từ admin
        add_action('admin_post_twc_apply_preset_branding', [$this, 'handle_apply_preset_branding']);  // Xử lý form cài đặt branding
        add_action('admin_post_twc_apply_security', [$this, 'handle_apply_security']);  // Xử lý form bảo mật nâng cao
        add_action('admin_post_twc_apply_security_basic', [$this, 'handle_apply_security_basic']);  // Xử lý form bảo mật cơ bản
        add_action('admin_post_twc_create_plugin_snapshot', [$this, 'handle_create_plugin_snapshot']);  // Tạo snapshot cấu hình
        add_action('admin_post_twc_restore_plugin_snapshot', [$this, 'handle_restore_plugin_snapshot']);  // Khôi phục snapshot
        add_action('admin_post_twc_clear_logs', [$this, 'handle_clear_logs']);  // Xóa logs

        // Add javascript cho render_presets mở Media Library
        add_action('admin_enqueue_scripts', function($hook) {
            // 1. Kích hoạt Media Library của WordPress (Bắt buộc để gọi wp.media)
            wp_enqueue_media();

            // 2. Xác định đường dẫn file presets.js
            // Nếu đặt ở file chính plugin:
            $js_url = plugin_dir_url(__FILE__) . 'assets/js/presets.js';
            
            // Nếu đặt ở includes/admin-pages/class-twc-admin-pages.php thì dùng:
            // $js_url = plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/presets.js';

            // 3. Đăng ký script với dependency là 'jquery'
            wp_enqueue_script(
                'twc-presets-js', 
                $js_url, 
                array('jquery'), // Yêu cầu WordPress nạp jQuery trước file này
                '1.1.0', 
                true
            );
        });
    }

    /**
     * Đăng ký Custom Post Types (CPT) cho logs và snapshots
     * - twc_log: Lưu nhật ký hoạt động của plugin
     * - twc_snapshot: Lưu các snapshot cấu hình plugin
     */
    public function register_cpts() {
        // Đăng ký CPT 'twc_log' để lưu nhật ký hoạt động
        register_post_type('twc_log', [
            'label' => __('OdoTech Logs', 'odotech-settings'),  // Tên hiển thị
            'public' => false,           // Không hiển thị ở frontend
            'show_ui' => false,          // Không hiển thị UI trong admin
            'capability_type' => 'post', // Sử dụng quyền như 'post'
            'supports' => ['title', 'editor', 'custom-fields'],  // Các tính năng hỗ trợ
        ]);
        // Đăng ký CPT 'twc_snapshot' để lưu các bản snapshot cấu hình
        register_post_type('twc_snapshot', [
            'label' => __('OdoTech Snapshots', 'odotech-settings'),
            'public' => false,           // Không hiển thị ở frontend
            'show_ui' => false,          // Không hiển thị UI trong admin
            'capability_type' => 'post', // Sử dụng quyền như 'post'
            'supports' => ['title', 'editor', 'custom-fields'],  // Các tính năng hỗ trợ
        ]);
    }

    /**
     * Khởi tạo tất cả các module của plugin
     * - Roles: Quản lý vai trò người dùng
     * - Security: Bảo mật website
     * - Admin UI: Giao diện admin tùy chỉnh
     * - Presets: Cài đặt nhanh branding
     * - Snapshot: Sao lưu cấu hình
     * - Logger: Ghi nhật ký hoạt động
     */
    public function init_modules() {
        TWC_Roles::init();
        TWC_Security::init();
        TWC_Admin_UI::init();
        TWC_Presets::init();
        TWC_Snapshot::init();
        TWC_Logger::init();
    }

    /**
     * Tải CSS và JavaScript cho trang admin của plugin
     * Chỉ load khi đang ở trang OdoTech Settings
     */
    public function enqueue_admin_assets($hook) {
        // Chỉ load assets khi đang ở trang OdoTech Settings (hook chứa 'twc')
        if (strpos($hook, 'twc') !== false) {
            // Load WordPress Media Library - cần cho chức năng upload logo/favicon
            wp_enqueue_media();
            
            // Load file CSS tùy chỉnh cho trang admin
            wp_enqueue_style('twc-admin', TWC_PLUGIN_URL . 'assets/css/admin.css', [], TWC_VERSION);
            // Load file JavaScript cho trang admin, phụ thuộc jQuery
            wp_enqueue_script('twc-admin', TWC_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], TWC_VERSION, true);
        }
    }

    /**
     * Menu:
     * - Cha + Presets dùng capability 'twc_manage_branding' (KHÁCH thấy được)
     * - Phần kỹ thuật dùng 'manage_options'
     */
    public function register_admin_menu() {
        // Tạo menu cha trong WordPress admin sidebar
        add_menu_page(
            __('OdoTech Settings', 'odotech-settings'),  // Tiêu đề trang (page title)
            __('OdoTech Settings', 'odotech-settings'),  // Tên menu (menu title)
            'twc_manage_branding',                     // Capability cần thiết (KHÁCH được xem)
            'twc-presets',                             // Menu slug (ID duy nhất)
            [new TWC_Admin_Pages(), 'render_presets'], // Callback function để render nội dung
            'dashicons-admin-tools',                   // Icon menu (dashicon)
            65                                         // Vị trí trong menu (số càng cao càng xuống dưới)
        );

        // Submenu: Security - KHÁCH được xem (twc_manage_branding)
        add_submenu_page('twc-presets', __('Security', 'odotech-settings'), __('Security', 'odotech-settings'), 'twc_manage_branding', 'twc-security', [new TWC_Admin_Pages(), 'render_security']);
        // Submenu: Snapshot & Logs - CHỈ KỸ THUẬT (manage_options)
        add_submenu_page('twc-presets', __('Nhật ký thay đổi', 'odotech-settings'), __('Nhật ký thay đổi', 'odotech-settings'), 'manage_options', 'twc-snapshot-logs', [new TWC_Admin_Pages(), 'render_snapshot_logs']);
    }

    private function check_nonce_and_caps($action, $allowed_caps = ['manage_options']) {
        // Lấy nonce từ POST data và làm sạch
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field($_POST['_wpnonce']) : '';
        // Xác thực nonce để chống CSRF (Cross-Site Request Forgery)
        if (!wp_verify_nonce($nonce, $action)) {
            wp_die(__('Nonce không hợp lệ.', 'tigoweb-Settings'));  // Dừng nếu nonce không đúng
        }
        
        // Kiểm tra xem user hiện tại có một trong các capabilities được phép không
        $ok = false;
        foreach ($allowed_caps as $cap) {
            if (current_user_can($cap)) {  // Kiểm tra từng capability
                $ok = true; 
                break;  // Tìm thấy 1 cap đúng là đủ
            }
        }
        
        // Dừng nếu user không có quyền nào phù hợp
        if (!$ok) {
            wp_die(__('Bạn không thểthực hiện hành động này.', 'tigoweb-Settings'));
        }
    }

    /**
     * Xử lý form Presets (Branding)
     * Cho phép KHÁCH và KỸ THUẬT thay đổi:
     * - Email admin
     * - Title website/header
     * - Logo header
     * - Favicon
     * - Màu header
     */
    public function handle_apply_preset_branding() {
        // Kiểm tra nonce và quyền (cho phép cả KHÁCH và KỸ THUẬT)
        $this->check_nonce_and_caps('twc_apply_preset_branding', ['manage_options', 'twc_manage_branding']);
        // Áp dụng cài đặt branding từ dữ liệu POST
        $result = TWC_Presets::apply_branding_preset($_POST);
        // Ghi log kết quả
        TWC_Logger::log('preset_branding', $result ? 'Applied branding preset' : 'Failed branding preset');
        // Redirect về trang presets với thông báo
        wp_redirect(admin_url('admin.php?page=twc-presets&applied=branding'));
        exit;  // Dừng script sau khi redirect
    }

    /**
     * Áp dụng Security Baseline (Advanced)
     * CHỈ dành cho KỸ THUẬT (manage_options)
     * Bật toàn bộ cài đặt bảo mật cơ bản
     */
    public function handle_apply_security() {
        // Kiểm tra nonce và quyền (CHỈ KỸ THUẬT)
        $this->check_nonce_and_caps('twc_apply_security', ['manage_options']);

        // Áp dụng toàn bộ cài đặt bảo mật nâng cao
        $result = TWC_Security::apply_security_baseline();

        // Ghi log kết quả
        TWC_Logger::log(
            'security_apply',
            $result ? 'Applied security baseline' : 'Failed security baseline'
        );

        // Redirect về trang security với thông báo
        wp_redirect(admin_url('admin.php?page=twc-security&applied=1'));
        exit;  // Dừng script sau khi redirect
    }

    /**
     * Xử lý form Security BASIC
     * Cho phép KHÁCH và KỸ THUẬT bật/tắt:
     * - Disable XML-RPC
     * - Hide WP Version
     * - Block Author Enumeration
     */
    public function handle_apply_security_basic() {
        // Kiểm tra nonce và quyền (cho phép cả KHÁCH và KỸ THUẬT)
        $this->check_nonce_and_caps(
            'twc_apply_security_basic',
            ['twc_manage_branding', 'manage_options']
        );

        // Lấy giá trị từ POST, mặc định = 0 nếu không có
        $params = [
            'disable_xmlrpc'    => $_POST['disable_xmlrpc'] ?? 0,     // Tắt XML-RPC
            'hide_wp_version'   => $_POST['hide_wp_version'] ?? 0,    // Ẩn phiên bản WP
            'block_author_enum' => $_POST['block_author_enum'] ?? 0,  // Chặn dò tìm author
        ];

        // Áp dụng các cài đặt bảo mật cơ bản
        $result = TWC_Security::apply_security_basic($params);

        // Ghi log kết quả
        TWC_Logger::log(
            'security_basic_apply',
            $result ? 'Applied basic security' : 'Failed basic security'
        );

        // Redirect về trang security với thông báo
        wp_redirect(admin_url('admin.php?page=twc-security&basic_applied=1'));
        exit;  // Dừng script sau khi redirect
    }


    // CHỈ KỸ THUẬT: Snapshot
    // public function handle_create_plugin_snapshot() {
    //     // Kiểm tra nonce và quyền (CHỈ KỸ THUẬT)
    //     $this->check_nonce_and_caps('twc_create_snapshot', ['manage_options']);
    //     // Tạo snapshot mới và lấy ID
    //     $snapshot_id = TWC_Snapshot::create_snapshot();
    //     // Ghi log kết quả
    //     TWC_Logger::log('snapshot_create', $snapshot_id ? 'Snapshot created: ' . $snapshot_id : 'Snapshot failed');
    //     // Redirect về trang snapshot-logs
    //     wp_redirect(admin_url('admin.php?page=twc-snapshot-logs&snapshot=created'));
    //     exit;
    // }

    // CHỈ KỸ THUẬT: Restore snapshot
    // public function handle_restore_plugin_snapshot() {
    //     // Kiểm tra nonce và quyền (CHỈ KỸ THUẬT)
    //     $this->check_nonce_and_caps('twc_restore_snapshot', ['manage_options']);
    //     // Lấy ID snapshot từ POST và chuyển sang số nguyên
    //     $snapshot_id = isset($_POST['snapshot_id']) ? intval($_POST['snapshot_id']) : 0;
    //     // Khôi phục snapshot nếu có ID hợp lệ
    //     $ok = $snapshot_id ? TWC_Snapshot::restore_snapshot($snapshot_id) : false;
    //     // Ghi log kết quả
    //     TWC_Logger::log('snapshot_restore', $ok ? 'Snapshot restored: ' . $snapshot_id : 'Snapshot restore failed');
    //     // Redirect về trang restore với kết quả
    //     wp_redirect(admin_url('admin.php?page=twc-restore&restored=' . ($ok ? '1' : '0')));
    //     exit;
    // }

    // // CHỈ KỸ THUẬT: Clear logs
    // public function handle_clear_logs() {
    //     // Kiểm tra nonce và quyền (CHỈ KỸ THUẬT)
    //     $this->check_nonce_and_caps('twc_clear_logs', ['manage_options']);
    //     // Xóa tất cả logs
    //     $ok = TWC_Logger::clear_logs();
    //     // Redirect về trang snapshot-logs với kết quả
    //     wp_redirect(admin_url('admin.php?page=twc-snapshot-logs&logs_cleared=' . ($ok ? '1' : '0')));
    //     exit;
    // }
}

// Khởi tạo plugin - tạo instance của class OdoTechSettings
new OdoTechSettings();

/**
 * Kích hoạt plugin
 * - Tạo/cập nhật roles (twc_client, twc_technical)
 * - Cấp quyền cho administrator
 * - Tạo tài khoản adminTigo (user/pass: adminTigo/adminTigo123)
 * - Thiết lập cấu hình bảo mật mặc định
 */
register_activation_hook(__FILE__, function () {
    // Tạo/cập nhật các role tùy chỉnh (twc_client, twc_technical)
    TWC_Roles::ensure_roles();

    // Cấp custom capabilities cho administrator
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('odo_client', true);   // Quyền quản lý branding
        $admin_role->add_cap('odo_technical', true);   // Quyền quản lý security
    }

    // Thiết lập cấu hình bảo mật mặc định (chỉ khi chưa có)
    if (get_option('twc_security_basic') === false) {
        update_option('twc_security_basic', [
            'disable_xmlrpc'    => true,   // Tắt XML-RPC mặc định
            'hide_wp_version'   => true,   // Ẩn phiên bản WP mặc định
            'block_author_enum' => true,   // Chặn dò tìm author mặc định
        ], false);  // false = không autoload
    }

    // Làm mới rewrite rules để áp dụng các thay đổi permalink
    flush_rewrite_rules();
});

/**
 * Hủy kích hoạt plugin
 * Làm mới rewrite rules
 */
register_deactivation_hook(__FILE__, function () {
    // Làm mới rewrite rules khi tắt plugin
    flush_rewrite_rules();
});