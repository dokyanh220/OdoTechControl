<?php
/**
 * Presets (Branding & Thông tin)
 * Xử lý các cài đặt nhanh về:
 * - Title website
 * - Email admin
 * - Logo, màu header, favicon
 */
if (!defined('ABSPATH')) { exit; }

class TWC_Presets {
    /**
     * Khởi tạo: thêm hook để thay logo trong frontend
     */
    public static function init() {
        add_action('wp_footer', [__CLASS__, 'inject_logo_script'], 999);
    }

    /**
     * Inject JavaScript để thay logo trong header (class: header_logo hoặc header-logo)
     * Dùng cho theme Flatsome hoặc theme khác
     */
    public static function inject_logo_script() {
        $logo_url = get_option('twc_website_logo_url', '');
        if (empty($logo_url)) {
            return;
        }
        $logo_width = get_option('twc_website_logo_width', '');
        ?>
        <script>
        (function() {
            var logoUrl = <?php echo json_encode($logo_url); ?>;
            var logoWidth = <?php echo json_encode($logo_width); ?>;
            if (!logoUrl) return;
            
            // Đợi DOM load xong
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', replaceLogo);
            } else {
                replaceLogo();
            }
            
            function replaceLogo() {
                // Tìm tất cả thẻ img có class chứa 'header_logo' hoặc 'header-logo'
                var selectors = [
                    '.header_logo img',
                    '.header-logo img',
                    'img.header_logo',
                    'img.header-logo',
                    '.site-logo img',
                    '.custom-logo'
                ];
                
                selectors.forEach(function(selector) {
                    var imgs = document.querySelectorAll(selector);
                    imgs.forEach(function(img) {
                        img.src = logoUrl;
                        img.srcset = logoUrl;
                        // Áp dụng width nếu có
                        if (logoWidth && logoWidth > 0) {
                            img.style.width = logoWidth + 'px';
                            img.style.height = 'auto';
                        }
                    });
                });
            }
        })();
        </script>
        <?php
    }

    /**
     * Áp dụng Branding Preset
     * @param array $params Dữ liệu từ form
     * @return bool True nếu thành công
     */
    public static function apply_branding_preset($params) {
        // Defaults
        $defaults = [
            'twc_site_title' => '',
            'twc_admin_email' => '',
            'twc_admin_locale' => '',
            'twc_website_logo_url' => '',
            'twc_website_logo_width' => '',
            'twc_branding_favicon_url' => '',
            'hide_plugins_client' => 0,
            'twc_client_visible_plugins' => [],
        ];
        $params = wp_parse_args($params, $defaults);

        // Save visible plugins
        $visible_plugins = is_array($params['twc_client_visible_plugins']) ? $params['twc_client_visible_plugins'] : [];
        // Sanitize array
        $visible_plugins = array_map('sanitize_text_field', $visible_plugins);
        update_option('twc_client_visible_plugins', $visible_plugins, false);

        // 1. Cập nhật Title website (blogname)
        if (!empty($params['twc_site_title'])) {
            update_option('blogname', sanitize_text_field($params['twc_site_title']), false);
        }

        // 2. Đổi email admin (WP có thể yêu cầu xác nhận)
        if (!empty($params['twc_admin_email'])) {
            $new_email = sanitize_email($params['twc_admin_email']);
            if (is_email($new_email)) {
                update_option('new_admin_email', $new_email, false);
                update_option('admin_email', $new_email, false);
            }
        }

        // 3. Cập nhật ngôn ngữ admin
        if (!empty($params['twc_admin_locale'])) {
            $locale = sanitize_text_field($params['twc_admin_locale']);
            
            // Thay đổi ngôn ngữ cho user hiện tại (ưu tiên)
            $user_id = get_current_user_id();
            if ($user_id) {
                update_user_meta($user_id, 'locale', $locale);
            }
            
            // Thay đổi ngôn ngữ mặc định của site
            update_option('WPLANG', $locale);
        }

        // 4. Cập nhật Logo website
        if (!empty($params['twc_website_logo_url'])) {
            $logo_url = esc_url_raw($params['twc_website_logo_url']);
            update_option('twc_website_logo_url', $logo_url, false);
        }
        
        // 5. Cập nhật Width logo
        $logo_width = isset($params['twc_website_logo_width']) ? intval($params['twc_website_logo_width']) : 0;
        update_option('twc_website_logo_width', $logo_width, false);

        // 6. Cập nhật favicon và các thiết lập khác
        if (!empty($params['twc_branding_favicon_url'])) {
            update_option('twc_branding_favicon_url', esc_url_raw($params['twc_branding_favicon_url']), false);
        }
        
        $hide_plugins_for_client = isset($params['hide_plugins_client']) ? (bool)$params['hide_plugins_client'] : false;
        update_option('twc_hide_plugins_client', $hide_plugins_for_client, false);

        // Đánh dấu đã áp dụng
        update_option('twc_branding_applied', ['time' => time()], false);
        return true;
    }
}