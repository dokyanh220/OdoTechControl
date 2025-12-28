<?php
/**
 * Plugin Name: OdoTech Settings
 * Description: Cấu hình nhanh toàn bộ website WordPress cho khách hàng OdoTech. Chức năng cho QUẢN TRỊ VIÊN (administrator) và BIÊN TẬP VIÊN (editor) theo đúng quyền mặc định WordPress.
 * Version: 2.1.0
 * Author: OdoTech Team dokyanh220
 */

if (!defined('ABSPATH')) { exit; }

// Các hằng số cơ bản
define('odo_VERSION', '2.1.0');
define('odo_PLUGIN_FILE', __FILE__);
define('odo_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('odo_PLUGIN_URL', plugin_dir_url(__FILE__));

// Chặn sửa file plugin/theme qua editor
if (!defined('DISALLOW_FILE_EDIT')) { 
    define('DISALLOW_FILE_EDIT', true);
}

// Autoload (Giả lập nếu không có file, bạn đảm bảo file tồn tại nhé)
// require_once ... (Giữ nguyên các require của bạn)
// Để code chạy được độc lập cho việc test, tôi comment các require chưa có file thực tế
// Bạn hãy uncomment lại các dòng require bên dưới nếu file đã tồn tại:

require_once odo_PLUGIN_DIR . 'includes/security/function/class-odo-security.php';
require_once odo_PLUGIN_DIR . 'includes/snapshot&logs/function/class-odo-logger.php';
require_once odo_PLUGIN_DIR . 'includes/snapshot&logs/function/class-odo-snapshot.php';
require_once odo_PLUGIN_DIR . 'includes/admin-pages/class-odo-admin-ui.php';
require_once odo_PLUGIN_DIR . 'includes/presets/function/class-odo-presets.php';
require_once odo_PLUGIN_DIR . 'includes/admin-pages/class-odo-admin-pages.php';


// --- MOCK CLASSES ĐỂ TRÁNH LỖI FATAL KHI CHƯA CÓ FILE INCLUDE ---
// (Bạn xóa đoạn Mock này khi chạy thực tế nhé)
if (!class_exists('odo_Admin_Pages')) {
    class odo_Admin_Pages { 
        public function render_presets(){ echo '<h1>Presets Page</h1>'; } 
        public function render_security(){ echo '<h1>Security Page</h1>'; }
        public function render_snapshot_logs(){ echo '<h1>Logs Page</h1>'; }
    }
}
// ---------------------------------------------------------------


/**
 * Lớp chính: OdoTechSettings
 */
class OdoTechSettings {
    public function __construct() {
        add_action('init', [$this, 'register_cpts']);
        add_action('plugins_loaded', [$this, 'init_modules']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_menu', [$this, 'register_admin_menu']);

        // Handlers
        add_action('admin_post_odo_apply_preset_branding', [$this, 'handle_apply_preset_branding']);
        add_action('admin_post_odo_apply_security', [$this, 'handle_apply_security']);
        add_action('admin_post_odo_apply_security_basic', [$this, 'handle_apply_security_basic']);
        add_action('admin_post_odo_create_plugin_snapshot', [$this, 'handle_create_plugin_snapshot']);
        add_action('admin_post_odo_restore_plugin_snapshot', [$this, 'handle_restore_plugin_snapshot']);
        add_action('admin_post_odo_clear_logs', [$this, 'handle_clear_logs']);
    }

    public function register_cpts() {
        register_post_type('odo_log', [
            'label' => __('OdoTech Logs', 'odotech-settings'),
            'public' => false, 'show_ui' => false, 'capability_type' => 'post', 'supports' => ['title', 'editor', 'custom-fields'],
        ]);
        register_post_type('odo_snapshot', [
            'label' => __('OdoTech Snapshots', 'odotech-settings'),
            'public' => false, 'show_ui' => false, 'capability_type' => 'post', 'supports' => ['title', 'editor', 'custom-fields'],
        ]);
    }

    public function init_modules() {
        if (class_exists('odo_Security')) odo_Security::init();
        if (class_exists('odo_Admin_UI')) odo_Admin_UI::init();
        if (class_exists('odo_Presets')) odo_Presets::init();
        if (class_exists('odo_Snapshot')) odo_Snapshot::init();
        if (class_exists('odo_Logger')) odo_Logger::init();
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'odo') !== false) {
            wp_enqueue_media();
            // wp_enqueue_style/script...
        }
    }

    public function register_admin_menu() {
        // Sử dụng capability 'odotech_manage' để quyết định ai được thấy menu này
        add_menu_page(
            'OdoTech Settings', 'OdoTech Settings',
            'odotech_manage', // Cap bắt buộc
            'odo-presets',
            [new odo_Admin_Pages(), 'render_presets'],
            'dashicons-admin-tools', 65
        );
        add_submenu_page('odo-presets', 'Security', 'Security', 'odotech_manage', 'odo-security', [new odo_Admin_Pages(), 'render_security']);
        add_submenu_page('odo-presets', 'Nhật ký', 'Nhật ký', 'odotech_manage', 'odo-snapshot-logs', [new odo_Admin_Pages(), 'render_snapshot_logs']);
    }

    /**
     * Check quyền: Chỉ check Capability, không check Role name
     */
    private function check_nonce_and_caps($action, $caps = ['manage_options']) {
        $nonce = $_POST['_wpnonce'] ?? '';
        if (!wp_verify_nonce($nonce, $action)) wp_die('Nonce không hợp lệ');

        foreach ($caps as $cap) {
            if (current_user_can($cap)) return; // User có quyền này là OK
        }
        wp_die('Bạn không có quyền thực hiện thao tác này.');
    }

    // Handlers
    public function handle_apply_preset_branding() {
        // Cho phép admin HOẶC người có quyền odotech_manage (client)
        $this->check_nonce_and_caps('odo_apply_preset_branding', ['manage_options', 'odotech_manage']);
        
        if (class_exists('odo_Presets')) {
            $result = odo_Presets::apply_branding_preset($_POST);
            if (class_exists('odo_Logger')) odo_Logger::log('preset_branding', $result ? 'Applied branding' : 'Failed');
        }
        wp_redirect(admin_url('admin.php?page=odo-presets&applied=branding'));
        exit;
    }

    public function handle_apply_security() {
        $this->check_nonce_and_caps('odo_apply_security', ['manage_options']); // Chỉ Admin
        // ... logic security ...
        wp_redirect(admin_url('admin.php?page=odo-security&applied=1'));
        exit;
    }
    
    // Các handler khác giữ nguyên logic, lưu ý đổi check caps nếu cần thiết...
    public function handle_apply_security_basic() {
        $this->check_nonce_and_caps('odo_apply_security_basic', ['manage_options']);
        wp_redirect(admin_url('admin.php?page=odo-security&basic_applied=1'));
        exit;
    }
    public function handle_create_plugin_snapshot() {
        $this->check_nonce_and_caps('odo_create_plugin_snapshot', ['manage_options']);
        wp_redirect(admin_url('admin.php?page=odo-snapshot-logs&created=1'));
        exit;
    }
    public function handle_restore_plugin_snapshot() {
        $this->check_nonce_and_caps('odo_restore_plugin_snapshot', ['manage_options']);
        wp_redirect(admin_url('admin.php?page=odo-snapshot-logs&restored=1'));
        exit;
    }
    public function handle_clear_logs() {
        $this->check_nonce_and_caps('odo_clear_logs', ['manage_options']);
        wp_redirect(admin_url('admin.php?page=odo-snapshot-logs&cleared=1'));
        exit;
    }
}

new OdoTechSettings();

/**
 * QUAN TRỌNG: Tạo và CẬP NHẬT quyền cho Role
 * Logic: Nếu role đã có, phải update thêm quyền 'odotech_manage' vào DB
 */
function odotech_create_client_role() {
    $role_slug = 'odotech_client';
    $display_name = 'Khách hàng OdoTech';

    $caps = [
        // --- Core ---
        'read' => true,
        'upload_files' => true,
        
        // --- Bài viết ---
        'edit_posts' => true,
        'edit_others_posts' => true,
        'publish_posts' => true,
        'read_private_posts' => true,
        'delete_posts' => true,     
        'delete_others_posts' => true,     
        'delete_published_posts' => true,     
        
        // --- WooCommerce (Sản phẩm & Đơn hàng) ---
        'manage_woocommerce' => true,
        'view_woocommerce_reports' => true,
        'edit_products' => true,
        'edit_others_products' => true,
        'publish_products' => true,
        'read_product' => true,
        'delete_products' => true,
        'delete_others_products' => true,
        'delete_published_products' => true,
        'edit_shop_orders' => true,
        'edit_others_shop_orders' => true,
        'publish_shop_orders' => true,
        'read_shop_order' => true,
        
        // --- Comments ---
        'moderate_comments' => true,
        'edit_comment' => true,
        
        // --- Giao diện (Để thấy menu Appearance, nhưng sẽ ẩn Theme Editor sau) ---
        'edit_theme_options' => true, 
        
        // --- Plugin Cap (QUAN TRỌNG NHẤT) ---
        'odotech_manage' => true, 
    ];

    $role = get_role($role_slug);

    if (null === $role) {
        // Tạo mới nếu chưa có
        add_role($role_slug, $display_name, $caps);
    } else {
        // Nếu role ĐÃ TỒN TẠI, loop qua $caps để ép cập nhật quyền thiếu vào DB
        foreach ($caps as $cap => $grant) {
            if (!$role->has_cap($cap)) {
                $role->add_cap($cap);
            }
        }
    }
}
add_action('init', 'odotech_create_client_role');


/**
 * Ẩn Menu không cần thiết cho OdoTech Client
 */
add_action('admin_menu', function() {
    // Chỉ áp dụng nếu là Odo Client (có quyền odotech_manage và KHÔNG phải Admin)
    if (!current_user_can('administrator') && current_user_can('odotech_manage')) {
        
        // List các menu cần ẩn
        $items_to_remove = [
            'index.php',                  // Dashboard
            // 'edit.php',                // Posts (Giữ lại)
            'edit.php?post_type=page',    // Pages (Tùy chọn)
            'themes.php',                 // Appearance
            'plugins.php',                // Plugins
            'users.php',                  // Users
            'tools.php',                  // Tools
            'options-general.php',        // Settings
            'edit-comments.php',          // Comments (Tùy chọn)
        ];

        foreach ($items_to_remove as $item) {
            remove_menu_page($item);
        }
        
        // Nếu muốn giữ menu Appearance để chỉnh Menu/Widget nhưng ẩn Theme Editor/Themes
        // remove_submenu_page('themes.php', 'themes.php');
        // remove_submenu_page('themes.php', 'theme-editor.php'); 
    }
}, 999);


/**
 * Giới hạn danh sách plugin (Logic cũ của bạn)
 */
add_filter('all_plugins', function ($plugins) {
    if (current_user_can('administrator')) return $plugins;

    // Nếu là client, ẩn hết plugin hoặc chỉ hiện list cho phép
    if (current_user_can('odotech_manage')) {
        $allowed = get_option('odo_client_visible_plugins', []); // Array các plugin path
        if (!is_array($allowed) || empty($allowed)) return []; // Ẩn hết nếu không config

        foreach ($plugins as $path => $plugin) {
            if (!in_array($path, $allowed)) unset($plugins[$path]);
        }
    }
    return $plugins;
});

// Chặn truy cập trực tiếp plugins.php nếu không phải admin/client
add_action('admin_init', function () {
    global $pagenow;
    if ($pagenow === 'plugins.php' && !current_user_can('administrator') && !current_user_can('odotech_manage')) {
        wp_die('Bạn không có quyền quản lý Plugin.');
    }
});


// Logo Custom Login
function odo_custom_login_logo() { ?>
    <style type="text/css">
        body.login {
            background: #011f26;
            margin: 0; height: 100vh;
            display: flex; justify-content: flex-end; align-items: center;
            overflow: hidden; position: relative;
        }
        #login h1 {margin-bottom: 24px;}
        #login h1 a {
            background-image: url('<?php echo plugin_dir_url(__FILE__) . "assets/img/logo.png"; ?>');
            background-size: contain; background-repeat: no-repeat;
            width: 260px; height: 120px; display: block; margin: 0 auto;
        }
        #wp-submit {
            background: #f16529; border: none; height: 46px; width: 100%; margin-top: 18px;
            font-weight: 600; letter-spacing: 1px; transition: 0.25s ease;
        }
        #wp-submit:hover {background: #ff7a3d;}
        #nav, #backtoblog {text-align: center; margin-top: 18px;}
        #nav a, #backtoblog a { color: #cfc894ff !important; font-size: 11px; }
        .language-switcher {display: none !important;}
    </style>
<?php }
add_action('login_enqueue_scripts', 'odo_custom_login_logo');

add_filter('login_headerurl', function() { return home_url(); });