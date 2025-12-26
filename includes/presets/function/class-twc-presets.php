<?php
/**
 * TWC Presets – Branding System (Clean Version)
 * Quản lý Logo – Header – Branding theo chuẩn WordPress
 */

if (!defined('ABSPATH')) exit;

class TWC_Presets {

    public static function init() {
        add_action('after_setup_theme', [__CLASS__, 'register_logo_override']);
        add_action('wp_head', [__CLASS__, 'inject_header_styles'], 20);
    }

    /**
     * Override logo thông qua core WordPress
     * Flatsome sẽ tự render logo này
     */
    public static function register_logo_override() {
        $logo = get_option('twc_website_logo_url');
        if (!$logo) return;

        add_filter('theme_mod_custom_logo', function () use ($logo) {
            return attachment_url_to_postid($logo);
        });
    }

    /**
     * Inject CSS điều khiển vị trí & kích thước logo
     */
    public static function inject_header_styles() {
        $logo_width  = intval(get_option('twc_website_logo_width', 0));
        $logo_pos    = get_option('twc_website_logo_position', 'left');
        ?>
        <style id="twc-branding-style">
            <?php if ($logo_width > 0): ?>
            .custom-logo, .header-logo img { max-width: <?=$logo_width?>px !important; height: auto !important; }
            <?php endif; ?>
            <?php
            // Nếu dùng Flatsome—header thực sự giữa là phải có .logo-center trên <header>
            if ($logo_pos == 'center'): ?>
            /* Chỉ áp dụng khi logo-center *được bật* (cách JS dưới) */
            <?php endif;?>
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Đảm bảo Flatsome có .header và .header-main
            const pos = <?=json_encode($logo_pos)?>;
            const h = document.querySelector('.header');
            if (!h) return;

            // Remove cả 2 để tránh bug
            h.classList.remove('logo-center', 'logo-left');
            if (pos === 'center') {
                h.classList.add('logo-center');
            } else {
                h.classList.add('logo-left');
            }
        });
        </script>
        <?php
    }

    /**
     * Áp dụng preset từ form cấu hình
     */
    public static function apply_branding_preset($params) {

        $defaults = [
            'twc_site_title'          => '',
            'twc_admin_email'         => '',
            'twc_website_logo_url'    => '',
            'twc_website_logo_width'  => '',
            'twc_website_logo_position' => 'left',
        ];
        $params = wp_parse_args($params, $defaults);

        // Site title
        if ($params['twc_site_title']) {
            update_option('blogname', sanitize_text_field($params['twc_site_title']));
        }

        // Admin email
        if (is_email($params['twc_admin_email'])) {
            update_option('admin_email', sanitize_email($params['twc_admin_email']));
        }

        // Logo URL
        if ($params['twc_website_logo_url']) {
            update_option('twc_website_logo_url', esc_url_raw($params['twc_website_logo_url']));
        }

        // Logo width
        update_option(
            'twc_website_logo_width',
            intval($params['twc_website_logo_width'])
        );

        // Logo position
        update_option(
            'twc_website_logo_position',
            in_array($params['twc_website_logo_position'], ['left', 'center', 'right'])
                ? $params['twc_website_logo_position']
                : 'left'
        );

        update_option('twc_branding_applied', time());
        return true;
    }
}
