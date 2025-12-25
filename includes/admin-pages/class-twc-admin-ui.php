<?php
if (!defined('ABSPATH')) { exit; }

class TWC_Admin_UI {
    public static function init() {
        add_action('admin_head', [__CLASS__, 'render_styles'], 20);
        add_action('in_admin_header', [__CLASS__, 'render_custom_header']);
        add_action('admin_head', [__CLASS__, 'render_favicon']);
        add_action('wp_head', [__CLASS__, 'render_favicon']);
        add_action('admin_menu', [__CLASS__, 'hide_menus_for_roles'], 999);
        add_filter('all_plugins', [__CLASS__, 'filter_plugins_for_client']);
    }

    public static function filter_plugins_for_client($all_plugins) {
        $user = wp_get_current_user();
        // Check for odo_client or twc_client
        if (in_array('odo_client', (array)$user->roles, true) || in_array('twc_client', (array)$user->roles, true)) {
            
            // Nếu "Ẩn menu hệ thống" bị tắt (tức là cho phép hiện), thì hiển thị tất cả plugin
            if (!get_option('twc_hide_plugins_client', true)) {
                return $all_plugins;
            }

            $visible_plugins = get_option('twc_client_visible_plugins', []);
            
            // If list is empty, maybe show nothing?
            if (empty($visible_plugins)) {
                return []; 
            }

            $filtered = [];
            foreach ($all_plugins as $path => $data) {
                if (in_array($path, $visible_plugins)) {
                    $filtered[$path] = $data;
                }
            }
            return $filtered;
        }
        return $all_plugins;
    }

    public static function apply_admin_ui_controls($params = []) {
        $branding_title  = isset($params['twc_branding_title']) ? sanitize_text_field($params['twc_branding_title']) : '';
        $branding_logo   = isset($params['twc_branding_logo']) ? esc_url_raw($params['twc_branding_logo']) : '';
        $branding_color  = isset($params['twc_branding_color']) ? sanitize_text_field($params['twc_branding_color']) : '#1e1e1e';
        $favicon_url     = isset($params['twc_branding_favicon_url']) ? esc_url_raw($params['twc_branding_favicon_url']) : '';

        update_option('twc_branding_title', $branding_title, false);
        update_option('twc_branding_logo', $branding_logo, false);
        update_option('twc_branding_color', $branding_color, false);
        update_option('twc_branding_favicon_url', $favicon_url, false);

        $hide_plugins_for_client = isset($params['hide_plugins_client']) ? (bool)$params['hide_plugins_client'] : true;
        update_option('twc_hide_plugins_client', $hide_plugins_for_client, false);

        return true;
    }

    public static function render_custom_header() {
        $branding_title = get_option('twc_branding_title', 'OdoTech');

        $logo_url = get_option('twc_branding_logo', '');
        if (empty($logo_url)) {
            $logo_url = plugins_url('../../assets/img/logo.png', __FILE__);
        }
        ?>
        <div class="twc-topbar">
            <div class="twc-topbar-left">
                <div class="brand">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($branding_title); ?>">
                </div>

                <nav class="twc-nav">
                    <a href="https://odotech.com.vn">Trang chủ</a><span class="twc-sep">|</span>
                    <a href="https://odotech.com.vn">Nghành hàng</a><span class="twc-sep">|</span>
                    <a href="https://odotech.com.vn">Điểm mạnh</a><span class="twc-sep">|</span>
                    <a href="https://odotech.com.vn">Công nghệ</a><span class="twc-sep">|</span>
                    <a href="https://odotech.com.vn">Liên hệ</a>
                </nav>
            </div>

            <div class="twc-topbar-right">
                <a href="https://tigoweb.com/kho-giao-dien/" class="twc-button">KHO GIAO DIỆN</a>
            </div>
        </div>
        <?php
    }

    public static function render_styles() {
        echo '<style>
        .twc-topbar {
            width: 100%;
            margin-right: 0;
            box-sizing: border-box;
            margin:0;
            padding:0 32px;
            height:100px;
            background:#222222;
            border-bottom:2px solid #ffc57aff;
            display:flex;
            align-items:center;
            justify-content:space-between;
            box-sizing:border-box;
            position: relative;
            left: -20px;
            width: calc(100% + 20px);
        }

        #wpbody-content{
            padding-top:0 !important;
            padding-right: 0 !important;
        }

        .twc-topbar-left{
            display:flex;
            align-items:center;
            gap:32px;
            min-width:0;
            flex:1;
            position: relative;
        }

        .brand img{
            max-height:140px;
            width:auto;
        }

        .brand .name{
            font-size:22px;
            font-weight:700;
            color:#1f4c8f;
            white-space:nowrap;
        }

        .twc-nav{
            display:flex;
            align-items:center;
            position:absolute;
            left:50%;
            transform:translateX(-50%);
            gap:14px;
            flex:1;
        }
        .twc-nav a{
            color:#f3e248;
            text-decoration:none;
            font-weight:600;
            font-size:16px;
            cursor:pointer;
            text-transform:uppercase;
        }
        .twc-nav a:hover{
            color:#44a696;
        }

        .twc-button{
            width:165px;
            background:#17a31a;
            color:#fff;
            border-radius:6px;
            padding:10px;
            font-weight:700;
            text-align:center;
            text-decoration:none;
        }
        </style>';
    }

    public static function render_favicon() {
        $favicon = get_option('twc_branding_favicon_url', '');
        if (!empty($favicon)) { echo '<link rel="icon" href="' . esc_url($favicon) . '" sizes="any">'; }
    }

    public static function hide_menus_for_roles() {
        // Ẩn Tools cho tất cả user không có manage_options
        if (!current_user_can('manage_options')) {
            remove_menu_page('tools.php');
        }
        
        // Ẩn menu Plugins, Themes, Settings cho twc_client
        $user = wp_get_current_user();
        if (in_array('twc_client', (array)$user->roles, true) || in_array('odo_client', (array)$user->roles, true)) {
            if (get_option('twc_hide_plugins_client', true)) {
                // Check if there are visible plugins
                $visible_plugins = get_option('twc_client_visible_plugins', []);
                if (empty($visible_plugins)) {
                    remove_menu_page('plugins.php');
                }
                remove_menu_page('themes.php');
                remove_menu_page('options-general.php');
                remove_menu_page('edit.php?post_type=blocks');
            }
        }
    }
}