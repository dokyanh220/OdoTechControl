<?php
if (!defined('ABSPATH')) { exit; }

class odo_Admin_UI {
    public static function init() {
        add_action('admin_head', [__CLASS__, 'render_styles']);
        add_action('in_admin_header', [__CLASS__, 'render_custom_header']);
        add_action('wp_head', [__CLASS__, 'render_favicon']);
        add_action('admin_head', [__CLASS__, 'render_favicon']);
    }

    public static function apply_admin_ui_controls($params = []) {
        $branding_title  = isset($params['odo_branding_title']) ? sanitize_text_field($params['odo_branding_title']) : '';
        $branding_logo   = isset($params['odo_branding_logo']) ? esc_url_raw($params['odo_branding_logo']) : '';
        $branding_color  = isset($params['odo_branding_color']) ? sanitize_text_field($params['odo_branding_color']) : '#1e1e1e';
        $favicon_url     = isset($params['odo_branding_favicon_url']) ? esc_url_raw($params['odo_branding_favicon_url']) : '';

        update_option('odo_branding_title', $branding_title, false);
        update_option('odo_branding_logo', $branding_logo, false);
        update_option('odo_branding_color', $branding_color, false);
        update_option('odo_branding_favicon_url', $favicon_url, false);

        return true;
    }

    public static function render_custom_header() {
        $branding_title = get_option('odo_branding_title', 'OdoTech');

        $logo_url = get_option('odo_branding_logo', '');
        if (empty($logo_url)) {
            $logo_url = plugins_url('../../assets/img/logo.png', __FILE__);
        }
        ?>
        <div class="odo-topbar">
            <div class="odo-topbar-left">
                <div class="brand">
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($branding_title); ?>">
                </div>

                <nav class="odo-nav">
                    <a href="https://odotech.com.vn">Trang chủ</a><span class="odo-sep">|</span>
                    <a href="https://odotech.com.vn">Nghành hàng</a><span class="odo-sep">|</span>
                    <a href="https://odotech.com.vn">Điểm mạnh</a><span class="odo-sep">|</span>
                    <a href="https://odotech.com.vn">Công nghệ</a><span class="odo-sep">|</span>
                    <a href="https://odotech.com.vn">Liên hệ</a>
                </nav>
            </div>

            <div class="odo-topbar-right">
                <a href="https://odotech.com/kho-giao-dien/" class="odo-button">KHO GIAO DIỆN</a>
            </div>
        </div>
        <?php
    }

    public static function render_styles() {
        echo '<style>
        .odo-topbar {
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

        .odo-topbar-left{
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

        .odo-nav{
            display:flex;
            align-items:center;
            position:absolute;
            left:50%;
            transform:translateX(-50%);
            gap:14px;
            flex:1;
        }
        .odo-nav a{
            color:#f3e248;
            text-decoration:none;
            font-weight:600;
            font-size:16px;
            cursor:pointer;
            text-transform:uppercase;
        }
        .odo-nav a:hover{
            color:#44a696;
        }

        .odo-button{
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
        $favicon = get_option('odo_branding_favicon_url', '');
        if (!empty($favicon)) { echo '<link rel="icon" href="' . esc_url($favicon) . '" sizes="any">'; }
    }
}