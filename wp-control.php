<?php
/**
 * Plugin Name: OdoTech Settings
 * Description: Cấu hình nhanh toàn bộ website WordPress cho khách hàng OdoTech. Chức năng cho QUẢN TRỊ VIÊN (administrator) và BIÊN TẬP VIÊN (editor) theo đúng quyền mặc định WordPress.
 * Version: 2.1.0
 * Author: OdoTech Team dokyanh220
 */

if (!defined('ABSPATH')) { exit; }

// Các hằng số cơ bản
define('TWC_VERSION', '2.1.0');
define('TWC_PLUGIN_FILE', __FILE__);
define('TWC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TWC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Chặn sửa file plugin/theme qua editor (tùy chọn)
if (!defined('DISALLOW_FILE_EDIT')) { 
    define('DISALLOW_FILE_EDIT', true);
}
if (!defined('DISALLOW_FILE_MODS')) { 
    define('DISALLOW_FILE_MODS', false);
}

// Autoload các chức năng liên quan
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
 * - Tạo menu admin (editor chỉ thấy mục thương hiệu, admin thấy full)
 * - Giới hạn menu admin cho Editor role
 */
class OdoTechSettings {
    public function __construct() {
        // add_action('init', [$this, 'register_cpts']);
        // add_action('plugins_loaded', [$this, 'init_modules']);
        // add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        // add_action('admin_menu', [$this, 'register_admin_menu']);
        
        // Giới hạn menu cho Editor
        add_action('admin_menu', [$this, 'limit_editor_menu'], 999);

        // handlers cho các thao tác POST chính của plugin
        add_action('admin_post_twc_apply_preset_branding', [$this, 'handle_apply_preset_branding']);
        add_action('admin_post_twc_apply_security', [$this, 'handle_apply_security']);
        add_action('admin_post_twc_apply_security_basic', [$this, 'handle_apply_security_basic']);
        add_action('admin_post_twc_create_plugin_snapshot', [$this, 'handle_create_plugin_snapshot']);
        add_action('admin_post_twc_restore_plugin_snapshot', [$this, 'handle_restore_plugin_snapshot']);
        add_action('admin_post_twc_clear_logs', [$this, 'handle_clear_logs']);

        // Media library JS cho admin
        add_action('admin_enqueue_scripts', function($hook) {
            wp_enqueue_media();
            wp_enqueue_script(
                'twc-presets-js', 
                plugin_dir_url(__FILE__) . 'assets/js/presets.js',
                array('jquery'),
                '2.1.0',
                true
            );
        });
    }

    // 1. Register custom post type
    public function register_cpts() {
        register_post_type('twc_log', [
            'label' => __('OdoTech Logs', 'odotech-settings'),
            'public' => false,
            'show_ui' => false,
            'capability_type' => 'post',
            'supports' => ['title', 'editor', 'custom-fields'],
        ]);
        register_post_type('twc_snapshot', [
            'label' => __('OdoTech Snapshots', 'odotech-settings'),
            'public' => false,
            'show_ui' => false,
            'capability_type' => 'post',
            'supports' => ['title', 'editor', 'custom-fields'],
        ]);
    }

    // 2. Khởi động các mô-đun chức năng
    public function init_modules() {
        TWC_Security::init();
        TWC_Admin_UI::init();
        TWC_Presets::init();
        TWC_Snapshot::init();
        TWC_Logger::init();
    }

    // 3. Tải các css/js cho admin menu của plugin
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'twc') !== false) {
            wp_enqueue_media();
            wp_enqueue_style('twc-admin', TWC_PLUGIN_URL . 'assets/css/admin.css', [], TWC_VERSION);
            wp_enqueue_script('twc-admin', TWC_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], TWC_VERSION, true);
        }
    }

    // 4. Đăng ký menu admin: Admin full, Editor chỉ Preset
    public function register_admin_menu() {
        $preset_cb     = [new TWC_Admin_Pages(), 'render_presets'];
        $security_cb   = [new TWC_Admin_Pages(), 'render_security'];
        $logs_cb       = [new TWC_Admin_Pages(), 'render_snapshot_logs'];

        // ADMINISTRATOR (toàn quyền)
        if (current_user_can('administrator')) {
            add_menu_page(
                __('OdoTech Settings', 'odotech-settings'),
                __('OdoTech Settings', 'odotech-settings'),
                'administrator',
                'twc-presets',
                $preset_cb,
                'dashicons-admin-tools', 65
            );
            add_submenu_page('twc-presets', __('Security', 'odotech-settings'), __('Security', 'odotech-settings'), 'administrator', 'twc-security', $security_cb);
            add_submenu_page('twc-presets', __('Nhật ký thay đổi', 'odotech-settings'), __('Nhật ký thay đổi', 'odotech-settings'), 'administrator', 'twc-snapshot-logs', $logs_cb);

            // ODOTECH CUSTOM: Thêm menu Odo Settings cho admin nếu muốn
            add_menu_page(
                __('Odo Settings', 'odotech-settings'),
                __('Odo Settings', 'odotech-settings'),
                'administrator',
                'odo-settings',
                $preset_cb, // hoặc callback riêng nếu có: [new TWC_Admin_Pages(), 'render_odo_settings'],
                'dashicons-smiley', 66
            );
        }
        // EDITOR: chỉ thấy Preset (Branding) và Odo Settings
        elseif (current_user_can('editor')) {
            add_menu_page(
                __('OdoTech Settings', 'odotech-settings'),
                __('OdoTech Settings', 'odotech-settings'),
                'editor',
                'twc-presets',
                $preset_cb,
                'dashicons-admin-tools', 65
            );
            // ODOTECH CUSTOM: Thêm menu Odo Settings cho editor
            add_menu_page(
                __('Odo Settings', 'odotech-settings'),
                __('Odo Settings', 'odotech-settings'),
                'editor',
                'odo-settings',
                $preset_cb, // hoặc callback khác
                'dashicons-smiley', 66
            );
        }
    }

    // 4.1. Giới hạn menu cho Editor role
    public function limit_editor_menu() {
        if (!current_user_can('editor') || current_user_can('administrator')) {
            return;
        }

        // Danh sách menu được phép cho Editor
        $allowed_menus = [
            'index.php',                    // Bảng tin (Dashboard)
            'edit.php',                     // Bài viết (Posts)
            'edit.php?post_type=page',      // Trang (Pages) - chỉ xem
            'edit.php?post_type=product',   // Sản phẩm (Products - WooCommerce)
            'edit-comments.php',            // Bình luận (Comments)
            'upload.php',                   // Media
            'plugins.php',                  // Plugin
            'twc-presets',                  // OdoTech Settings
            'odo-settings',                 // Odo Settings Plugin
        ];

        // Lấy tất cả menu global
        global $menu, $submenu;

        // Xóa các menu không được phép
        foreach ($menu as $key => $item) {
            $menu_slug = $item[2];
            if (!in_array($menu_slug, $allowed_menus)) {
                remove_menu_page($menu_slug);
            }
        }

        // Ẩn các nút thêm mới/edit/delete trong trang Pages bằng CSS
        add_action('admin_head', function() {
            if (isset($_GET['post_type']) && $_GET['post_type'] === 'page') {
                echo '<style>
                    .page-title-action, /* Nút "Thêm mới" */
                    .row-actions .edit, 
                    .row-actions .trash,
                    .row-actions .delete,
                    .tablenav .actions,
                    .view-switch { display: none !important; }
                </style>';
            }
        });

        // Chặn truy cập trực tiếp vào page editor
        add_action('admin_init', function() {
            global $pagenow;
            if (current_user_can('editor') && !current_user_can('administrator')) {
                // Nếu đang cố edit page
                if (($pagenow === 'post.php' || $pagenow === 'post-new.php') && 
                    isset($_GET['post_type']) && $_GET['post_type'] === 'page') {
                    wp_die(__('Bạn không có quyền chỉnh sửa trang.', 'odotech-settings'));
                }
                // Nếu đang cố edit page qua post_id
                if ($pagenow === 'post.php' && isset($_GET['post'])) {
                    $post = get_post($_GET['post']);
                    if ($post && $post->post_type === 'page') {
                        wp_die(__('Bạn không có quyền chỉnh sửa trang.', 'odotech-settings'));
                    }
                }
            }
        });
    }

    /**
     * Check quyền cho từng POST handler theo chuẩn phân quyền WP.
     */
    private function check_nonce_and_caps($action, $default_caps = ['manage_options']) {
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field($_POST['_wpnonce']) : '';
        if (!wp_verify_nonce($nonce, $action)) {
            wp_die(__('Nonce không hợp lệ.', 'odotech-settings'));
        }

        $ok = false;
        foreach ($default_caps as $cap) {
            if (current_user_can($cap)) {
                $ok = true;
                break;
            }
        }
        // Editor chỉ được phép branding
        if (current_user_can('editor') && $action === 'twc_apply_preset_branding') {
            $ok = true; // Chỉ branding
        }
        if (!$ok) {
            wp_die(__('Bạn không có quyền thực hiện thao tác này.', 'odotech-settings'));
        }
    }

    // 5. Xử lý form submit & dữ liệu
    public function handle_apply_preset_branding() {
        $this->check_nonce_and_caps('twc_apply_preset_branding', ['manage_options', 'editor']);
        $result = TWC_Presets::apply_branding_preset($_POST);
        TWC_Logger::log('preset_branding', $result ? 'Applied branding preset' : 'Failed branding preset');
        wp_redirect(admin_url('admin.php?page=twc-presets&applied=branding'));
        exit;
    }
    public function handle_apply_security() {
        $this->check_nonce_and_caps('twc_apply_security', ['manage_options']);
        $result = TWC_Security::apply_security_baseline();
        TWC_Logger::log('security_apply', $result ? 'Applied security baseline' : 'Failed security baseline');
        wp_redirect(admin_url('admin.php?page=twc-security&applied=1'));
        exit;
    }
    public function handle_apply_security_basic() {
        $this->check_nonce_and_caps('twc_apply_security_basic', ['manage_options']);
        $params = [
            'disable_xmlrpc'    => $_POST['disable_xmlrpc'] ?? 0,
            'hide_wp_version'   => $_POST['hide_wp_version'] ?? 0,
            'block_author_enum' => $_POST['block_author_enum'] ?? 0,
        ];
        $result = TWC_Security::apply_security_basic($params);
        TWC_Logger::log('security_basic_apply', $result ? 'Applied basic security' : 'Failed basic security');
        wp_redirect(admin_url('admin.php?page=twc-security&basic_applied=1'));
        exit;
    }
    public function handle_create_plugin_snapshot() {
        // Chỉ ADMIN
        $this->check_nonce_and_caps('twc_create_plugin_snapshot', ['manage_options']);
        $result = TWC_Snapshot::create();
        TWC_Logger::log('snapshot_create', $result ? 'Tạo snapshot thành công' : 'Tạo snapshot thất bại');
        wp_redirect(admin_url('admin.php?page=twc-snapshot-logs&created=1'));
        exit;
    }
    public function handle_restore_plugin_snapshot() {
        // Chỉ ADMIN
        $this->check_nonce_and_caps('twc_restore_plugin_snapshot', ['manage_options']);
        $result = TWC_Snapshot::restore($_POST);
        TWC_Logger::log('snapshot_restore', $result ? 'Restore snapshot' : 'Restore thất bại');
        wp_redirect(admin_url('admin.php?page=twc-snapshot-logs&restored=1'));
        exit;
    }
    public function handle_clear_logs() {
        // Chỉ ADMIN
        $this->check_nonce_and_caps('twc_clear_logs', ['manage_options']);
        $result = TWC_Logger::clear();
        TWC_Logger::log('clear_logs', $result ? 'Đã xóa logs' : 'Xóa logs thất bại');
        wp_redirect(admin_url('admin.php?page=twc-snapshot-logs&cleared=1'));
        exit;
    }
}

// Khởi tạo plugin
new OdoTechSettings();

// Kích hoạt/Ngắt kích hoạt plugin: chỉ còn cấu hình hệ thống cơ bản
register_activation_hook(__FILE__, function () {
    // Cấu hình bảo mật mặc định nếu chưa có
    if (get_option('twc_security_basic') === false) {
        update_option('twc_security_basic', [
            'disable_xmlrpc'    => true,
            'hide_wp_version'   => true,
            'block_author_enum' => true,
        ], false);
    }
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    // Khôi phục lại quyền đầy đủ cho Editor khi deactivate
    $editor_role = get_role('editor');
    if ($editor_role) {
        $editor_role->add_cap('edit_pages');
        $editor_role->add_cap('edit_others_pages');
        $editor_role->add_cap('edit_published_pages');
        $editor_role->add_cap('publish_pages');
        $editor_role->add_cap('delete_pages');
        $editor_role->add_cap('delete_others_pages');
        $editor_role->add_cap('delete_published_pages');
    }
    flush_rewrite_rules();
});

// Logo custom trang login
function twc_custom_login_logo() { ?>
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
            background-size: contain;
            background-repeat: no-repeat;
            width: 260px; height: 120px; display: block; margin: 0 auto;
        }
        #wp-submit {
            background: #f16529;
            border: none; height: 46px; width: 100%; margin-top: 18px;
            font-weight: 600; letter-spacing: 1px; transition: 0.25s ease;
        }
        #wp-submit:hover {background: #ff7a3d;}
        #nav, #backtoblog {text-align: center; margin-top: 18px;}
        #nav a, #backtoblog a {
            color: #cfc894ff !important; font-size: 11px;
        }
        .language-switcher {display: none !important;}
    </style>
<?php }
add_action('login_enqueue_scripts', 'twc_custom_login_logo');

// Đổi URL khi click logo login
function twc_custom_login_logo_url() {
    return home_url();
}
add_filter('login_headerurl', 'twc_custom_login_logo_url');