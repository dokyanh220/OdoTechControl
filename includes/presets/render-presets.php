<?php
if (!defined('ABSPATH')) { exit; }

trait TWC_Admin_Pages_Presets {
    public function render_presets() {
        // Lấy giá trị hiện tại từ database
        $site_title           = get_option('blogname', '');
        $current_admin_email  = get_option('admin_email', '');
        $favicon_url          = get_option('twc_branding_favicon_url', '');
        $hide_plugins_client  = get_option('twc_hide_plugins_client', 1);

        // Thông báo thành công
        if (isset($_GET['applied']) && $_GET['applied'] === 'branding') {
            echo '<div class="notice notice-success is-dismissible"><p>Đã lưu thành công Branding!</p></div>';
        }

        echo '<div class="wrap"><h1>Preset (Branding & Thông tin)</h1>';
        echo '<p class="description">Cấu hình nhanh thông tin website, giao diện admin và favicon.</p>';
        
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" enctype="multipart/form-data">';
        echo '<input type="hidden" name="action" value="twc_apply_preset_branding">';
        wp_nonce_field('twc_apply_preset_branding');

        echo '<table class="form-table"><tbody>';

        // Title website
        echo '<tr><th scope="row">Tiêu đề website</th><td>';
        echo '<input type="text" name="twc_site_title" value="' . esc_attr($site_title) . '" class="regular-text">';
        echo '<p class="description">Tên website hiển thị trên tab trình duyệt và kết quả tìm kiếm.</p>';
        echo '</td></tr>';

        // Email admin
        echo '<tr><th scope="row">Email quản trị</th><td>';
        echo '<input type="email" name="twc_admin_email" value="' . esc_attr($current_admin_email) . '" class="regular-text">';
        echo '<p class="description">Email nhận thông báo hệ thống. WordPress có thể yêu cầu xác nhận.</p>';
        echo '</td></tr>';

        // Ngôn ngữ admin
        $current_locale = get_locale();
        echo '<tr><th scope="row">Ngôn ngữ</th><td>';
        echo '<select name="twc_admin_locale" class="regular-text">';
        $languages = [
            'vi'    => 'Tiếng Việt',
            'en_US' => 'English (United States)',
            'en_GB' => 'English (UK)',
            'ja'    => '日本語 (Japanese)',
            'ko_KR' => '한국어 (Korean)',
            'zh_CN' => '简体中文 (Chinese Simplified)',
            'zh_TW' => '繁體中文 (Chinese Traditional)',
        ];
        foreach ($languages as $code => $name) {
            $selected = ($current_locale === $code) ? ' selected' : '';
            echo '<option value="' . esc_attr($code) . '"' . $selected . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Ngôn ngữ hiển thị trong khu vực quản trị (admin panel).</p>';
        echo '</td></tr>';

        echo '<tr><td colspan="2"><hr><h3>Giao diện Website</h3></td></tr>';

        // Logo website URL
        $custom_logo_id   = get_theme_mod('custom_logo');
        $logo_website_url = get_option('twc_website_logo_url', '');
        if (empty($logo_website_url) && $custom_logo_id) {
            $logo_website_url = wp_get_attachment_image_url($custom_logo_id, 'full');
        }
        echo '<tr><th scope="row">Logo header</th><td>';
        // button chọn Media
        echo '<input type="url" name="twc_website_logo_url" id="twc_website_logo_url" value="' . esc_url($logo_website_url) . '" class="regular-text" placeholder="https://example.com/logo.png">';
        echo ' <button type="button" class="button" id="twc_pick_logo">Chọn ảnh</button>';

        // Ảnh xem trước (gắn id để script cập nhật)
        $preview_style = 'max-height:80px;margin-top:10px;border:1px solid #ddd;padding:5px;';
        if (!empty($logo_website_url)) {
            echo '<br><img id="twc_website_logo_url_preview" src="' . esc_url($logo_website_url) . '" style="' . esc_attr($preview_style) . '">';
        } else {
            echo '<br><img id="twc_website_logo_url_preview" src="" style="' . esc_attr($preview_style . 'display:none;') . '">';
        }

        echo '<p class="description">Logo hiển thị ở header website.</p>';
        echo '</td></tr>';

        // echo '<input type="url" name="twc_website_logo_url" id="twc_website_logo_url" value="' . esc_url($logo_website_url) . '" class="regular-text" placeholder="https://example.com/logo.png">';
        // if (!empty($logo_website_url)) {
        //     echo '<br><img src="' . esc_url($logo_website_url) . '" style="max-height:80px;margin-top:10px;border:1px solid #ddd;padding:5px;">';
        // }
        // echo '<p class="description">Dán URL logo từ Media Library hoặc link bên ngoài. Tự động tìm thẻ có class "header_logo" hoặc "header-logo".</p>';
        // echo '</td></tr>';

        // Width logo
        $logo_width = get_option('twc_website_logo_width', '');
        echo '<tr><th scope="row">Kích thước logo (width)</th><td>';
        echo '<input type="number" name="twc_website_logo_width" value="' . esc_attr($logo_width) . '" class="small-text" placeholder="150" min="0" step="1"> px';
        echo '<p class="description">Chiều rộng logo header</p>';
        echo '</td></tr>';

        // Favicon
        echo '<tr><th scope="row">Favicon website</th><td>';
        echo '<input type="url" name="twc_branding_favicon_url" id="twc_branding_favicon" value="' . esc_url($favicon_url) . '" class="regular-text" placeholder="https://example.com/favicon.ico">';
        echo ' <button type="button" class="button" id="twc_pick_favicon">Chọn icon</button>';

        if (!empty($favicon_url)) {
            echo '<br><img id="twc_branding_favicon_preview" src="' . esc_url($favicon_url) . '" style="max-height:32px;margin-top:10px;">';
        } else {
            echo '<br><img id="twc_branding_favicon_preview" src="" style="max-height:32px;margin-top:10px;display:none;">';
        }

        echo '<p class="description">Favicon nhỏ trên tab của trình duyệt (khuyến nghị 32x32px).</p>';
        echo '</td></tr>';

        // Hiển thị Plugin cho Client
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        $visible_plugins = get_option('twc_client_visible_plugins', []);

        echo '<tr><th scope="row">Hiển thị Plugin cho Client</th><td>';
        echo '<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">';
        foreach ($all_plugins as $plugin_path => $plugin_data) {
            $checked = in_array($plugin_path, $visible_plugins) ? 'checked' : '';
            echo '<label style="display:block; margin-bottom: 5px;">';
            echo '<input type="checkbox" name="twc_client_visible_plugins[]" value="' . esc_attr($plugin_path) . '" ' . $checked . '> ';
            echo '<strong>' . esc_html($plugin_data['Name']) . '</strong>';
            echo '</label>';
        }
        echo '</div>';
        echo '<p class="description">Chọn các plugin mà tài khoản Client được phép nhìn thấy trong trang Plugins (Cần tắt tùy chọn "Ẩn menu hệ thống" bên dưới để Client truy cập được trang Plugins).</p>';
        echo '</td></tr>';

        // Ẩn menu cho khách
        echo '<tr><th scope="row">Hiển thị menu plugins</th><td>';
        echo '<label><input type="checkbox" name="hide_plugins_client" value="1" ' . checked(1, $hide_plugins_client, false) . '> ';
        echo 'Ẩn Plugins của Client</label>';
        echo '</td></tr>';

        echo '</tbody></table>';
        echo '<p><button type="submit" class="button button-primary">Lưu cài đặt</button></p>';
        echo '</form>';
        echo '</div>';
    }
}