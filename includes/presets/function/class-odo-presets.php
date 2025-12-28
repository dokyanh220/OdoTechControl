<?php
/**
 * odo Presets – Branding System (Clean Version)
 * Quản lý Logo – Header – Branding theo chuẩn WordPress
 */

if (!defined('ABSPATH')) exit;

class odo_Presets {

    public static function init() {
        add_action('wp_loaded', [__CLASS__, 'register_logo_override']); 
        add_action('wp_head', [__CLASS__, 'inject_header_styles'], 20);
        add_action('wp_head', [__CLASS__, 'inject_favicon'], 2); // Inject favicon sớm
    }

    /**
     * Cập nhật logo header dựa trên option đã lưu & override
     */
    public static function register_logo_override() {
        $logo_url = get_option('odo_website_logo_url');
        $override = get_option('odo_override_theme_logo');

        if (!$logo_url || !$override) return;

        $attach_id = attachment_url_to_postid($logo_url);

        if ($attach_id) {
            error_log('OdoTech Logo Attach ID: ' . $attach_id);
            set_theme_mod('site_logo', $attach_id);
            set_theme_mod('header_logo', $attach_id);
            set_theme_mod('custom_logo', $attach_id);
        }
    }

    /**
     * Inject CSS điều khiển vị trí & kích thước logo
     */
    public static function inject_header_styles() {
        $logo_width  = intval(get_option('odo_website_logo_width', 0));
        $logo_pos    = get_option('odo_website_logo_position', 'left');
        $logo_url    = esc_url(get_option('odo_website_logo_url', ''));
        
        ?>
        <style id="odo-branding-style">
            <?php if ($logo_width > 0): ?>
            #header .logo img { max-width: <?=$logo_width?>px !important; height: auto !important; }
            <?php endif; ?>
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pos = <?=json_encode($logo_pos)?>;
            const h = document.querySelector('.header');
            if (h) {
                h.classList.remove('logo-center', 'logo-left');
                if (pos === 'center') {
                    h.classList.add('logo-center');
                } else {
                    h.classList.add('logo-left');
                }
            }
            // Thay đổi logo giao diện bằng JS
            var logoImg = document.querySelector('#header .logo img, .header .logo img, .logo img');
            if (logoImg && <?=$logo_url ? 'true' : 'false' ?>) {
                logoImg.src = <?=json_encode($logo_url)?>;
            }
        });
        </script>
        <?php
    }

    /**
     * Inject favicon từ URL lưu trong option
     */
    public static function inject_favicon() {
        $favicon_url = get_option('odo_branding_favicon_url', '');
        if (!empty($favicon_url)) {
            echo '<link rel="icon" href="' . esc_url($favicon_url) . '" sizes="32x32" />';
        }
    }

    /**
     * Áp dụng preset từ form cấu hình
     */
    public static function apply_branding_preset($params) {
        $defaults = [
            'odo_site_title'             => '',
            'odo_admin_email'            => '',
            'odo_website_logo_url'       => '',
            'odo_website_logo_width'     => '',
            'odo_website_logo_position'  => 'left',
            'odo_branding_favicon_url'   => '',
            'odo_override_theme_logo'    => '',
        ];
        $params = wp_parse_args($params, $defaults);

        // Site title
        if ($params['odo_site_title']) {
            update_option('blogname', sanitize_text_field($params['odo_site_title']));
        }

        // Admin email
        if (is_email($params['odo_admin_email'])) {
            update_option('admin_email', sanitize_email($params['odo_admin_email']));
        }

        // Logo URL
        if ($params['odo_website_logo_url']) {
            update_option('odo_website_logo_url', esc_url_raw($params['odo_website_logo_url']));
        }

        // Xử lý override logo theme (checkbox)
        $override = !empty($params['odo_override_theme_logo']) ? 1 : 0;
        update_option('odo_override_theme_logo', $override);

        // Logo width
        update_option(
            'odo_website_logo_width',
            intval($params['odo_website_logo_width'])
        );

        // Logo position
        update_option(
            'odo_website_logo_position',
            in_array($params['odo_website_logo_position'], ['left', 'center', 'right'])
                ? $params['odo_website_logo_position']
                : 'left'
        );

        // Favicon
        update_option('odo_branding_favicon_url', esc_url_raw($params['odo_branding_favicon_url']));

        update_option('odo_branding_applied', time());
        return true;
    }
}