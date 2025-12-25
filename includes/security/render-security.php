<?php
if (!defined('ABSPATH')) { exit; }

trait TWC_Admin_Pages_Security {
    /**
     * Hiển thị trang Security
     * - Phần BASIC: dành cho KHÁCH và KỸ THUẬT
     * - Phần Advanced: chỉ dành cho KỸ THUẬT (manage_options)
     */
    public function render_security() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Security', 'tigoweb-control') . '</h1>';

        // 1) BASIC – ai có menu Security đều thấy
        $this->render_security_basic();

        // 2) Advanced – chỉ admin/kỹ thuật
        if (current_user_can('manage_options') || current_user_can('twc_manage_security')) {
            $this->render_security_advanced();
        }

        echo '</div>';
    }

    /**
     * Hiển thị phần Security BASIC
     * Cho phép bật/tắt:
     * - Disable XML-RPC
     * - Hide WP Version
     * - Block Author Enumeration
     */
    private function render_security_basic() {
        $basic = get_option('twc_security_basic', []);
        $disable_xmlrpc    = !empty($basic['disable_xmlrpc']);
        $hide_wp_version   = !empty($basic['hide_wp_version']);
        $block_author_enum = !empty($basic['block_author_enum']);

        if (isset($_GET['basic_applied'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' .
                esc_html__('Đã áp dụng Security BASIC.', 'tigoweb-control') .
            '</p></div>';
        }

        echo '<h2>' . esc_html__('Security BASIC', 'tigoweb-control') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="twc_apply_security_basic">';
        wp_nonce_field('twc_apply_security_basic');

        echo '<table class="form-table"><tbody>';

        echo '<tr><th scope="row">' . esc_html__('Disable XML-RPC', 'tigoweb-control') . '</th><td>';
        echo '<label><input type="checkbox" name="disable_xmlrpc" value="1" ' . checked(true, $disable_xmlrpc, false) . '> ' .
            esc_html__('Tắt XML-RPC', 'tigoweb-control') . '</label>';
        echo '</td></tr>';

        echo '<tr><th scope="row">' . esc_html__('Hide WP Version', 'tigoweb-control') . '</th><td>';
        echo '<label><input type="checkbox" name="hide_wp_version" value="1" ' . checked(true, $hide_wp_version, false) . '> ' .
            esc_html__('Ẩn phiên bản WordPress', 'tigoweb-control') . '</label>';
        echo '</td></tr>';

        echo '<tr><th scope="row">' . esc_html__('Block Author Enumeration', 'tigoweb-control') . '</th><td>';
        echo '<label><input type="checkbox" name="block_author_enum" value="1" ' . checked(true, $block_author_enum, false) . '> ' .
            esc_html__('Chặn dò user qua ?author=1', 'tigoweb-control') . '</label>';
        echo '</td></tr>';

        echo '</tbody></table>';

        echo '<p><button class="button button-primary">' . esc_html__('Áp dụng BASIC', 'tigoweb-control') . '</button></p>';
        echo '</form>';
    }

    /**
     * Hiển thị phần Security Advanced (Baseline)
     * Chỉ dành cho KỸ THUẬT
     * Một nút bấm để bật toàn bộ cài đặt bảo mật cơ bản
     */
    private function render_security_advanced() {
        echo '<hr>';
        echo '<h2>' . esc_html__('Security (Advanced)', 'tigoweb-control') . '</h2>';

        if (isset($_GET['applied'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' .
                esc_html__('Đã áp dụng Security baseline.', 'tigoweb-control') .
            '</p></div>';
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="twc_apply_security">';
        wp_nonce_field('twc_apply_security');
        echo '<p><button class="button button-secondary">' .
            esc_html__('Áp dụng Security Baseline', 'tigoweb-control') .
        '</button></p>';
        echo '</form>';
    }
}