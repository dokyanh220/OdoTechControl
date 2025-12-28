<?php
if (!defined('ABSPATH')) { exit; }

trait odo_Admin_Pages_Security {
    /**
     * Hiển thị trang Security
     * - Phần BASIC: dành cho KHÁCH và KỸ THUẬT
     * - Phần Advanced: chỉ dành cho KỸ THUẬT (manage_options)
     */
    public function render_security() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Security', 'odotech-control') . '</h1>';

        // 1) BASIC – ai có menu Security đều thấy
        $this->render_security_basic();

        // 2) Advanced – chỉ admin/kỹ thuật
        if (current_user_can('manage_options') || current_user_can('odo_manage_security')) {
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
        $basic = get_option('odo_security_basic', []);
        $disable_xmlrpc    = !empty($basic['disable_xmlrpc']);
        $hide_wp_version   = !empty($basic['hide_wp_version']);
        $block_author_enum = !empty($basic['block_author_enum']);

        if (isset($_GET['basic_applied'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' .
                esc_html__('Đã áp dụng Security BASIC.', 'odotech-control') .
            '</p></div>';
        }

        echo '<h2>' . esc_html__('Security BASIC', 'odotech-control') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="odo_apply_security_basic">';
        wp_nonce_field('odo_apply_security_basic');

        echo '<table class="form-table"><tbody>';

        echo '<tr><th scope="row">' . esc_html__('Disable XML-RPC', 'odotech-control') . '</th><td>';
        echo '<label><input type="checkbox" name="disable_xmlrpc" value="1" ' . checked(true, $disable_xmlrpc, false) . '> ' .
            esc_html__('Tắt XML-RPC', 'odotech-control') . '</label>';
        echo '</td></tr>';

        echo '<tr><th scope="row">' . esc_html__('Hide WP Version', 'odotech-control') . '</th><td>';
        echo '<label><input type="checkbox" name="hide_wp_version" value="1" ' . checked(true, $hide_wp_version, false) . '> ' .
            esc_html__('Ẩn phiên bản WordPress', 'odotech-control') . '</label>';
        echo '</td></tr>';

        echo '<tr><th scope="row">' . esc_html__('Block Author Enumeration', 'odotech-control') . '</th><td>';
        echo '<label><input type="checkbox" name="block_author_enum" value="1" ' . checked(true, $block_author_enum, false) . '> ' .
            esc_html__('Chặn dò user qua ?author=1', 'odotech-control') . '</label>';
        echo '</td></tr>';

        echo '</tbody></table>';

        echo '<p><button class="button button-primary">' . esc_html__('Áp dụng BASIC', 'odotech-control') . '</button></p>';
        echo '</form>';
    }

    /**
     * Hiển thị phần Security Advanced (Baseline)
     * Chỉ dành cho KỸ THUẬT
     * Một nút bấm để bật toàn bộ cài đặt bảo mật cơ bản
     */
    private function render_security_advanced() {
        echo '<hr>';
        echo '<h2>' . esc_html__('Security (Advanced)', 'odotech-control') . '</h2>';

        if (isset($_GET['applied'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' .
                esc_html__('Đã áp dụng Security baseline.', 'odotech-control') .
            '</p></div>';
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="odo_apply_security">';
        wp_nonce_field('odo_apply_security');
        echo '<p><button class="button button-secondary">' .
            esc_html__('Áp dụng Security Baseline', 'odotech-control') .
        '</button></p>';
        echo '</form>';
    }
}