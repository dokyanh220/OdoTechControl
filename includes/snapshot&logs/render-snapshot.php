<?php
if (!defined('ABSPATH')) { exit; }

trait TWC_Admin_Pages_Snapshot {
    /**
     * Hiển thị trang Snapshot & Logs
     * Chỉ dành cho KỸ THUẬT
     * Quản lý các snapshot và xem nhật ký hoạt động
     */
    public function render_snapshot_logs() {
        echo '<h2>Nhật ký hoạt động chi tiết</h2>';
        $logs = TWC_Logger::get_logs(50);
        if (!empty($logs)) {
            echo '<table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="150">Thời gian</th>
                            <th width="100">User</th>
                            <th width="100">Vai trò</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>';
            foreach ($logs as $log) {
                $data = json_decode($log->post_content, true);
                $role = isset($data['role']) ? $data['role'] : '';
                
                // Highlight màu khác nhau cho dễ nhìn
                $style = ($role === 'administrator') ? 'color: #d63638; font-weight: bold;' : 'color: #2271b1;';
                
                echo '<tr>';
                echo '<td>' . esc_html($data['time']) . '</td>';
                echo '<td>' . esc_html($data['username']) . '</td>';
                echo '<td><span style="' . $style . '">' . strtoupper($role) . '</span></td>';
                echo '<td>' . esc_html($data['message']) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
    }

    /**
     * Hiển thị trang Khôi phục
     * Chỉ dành cho KỸ THUẬT
     * Hướng dẫn khôi phục từ snapshot
     */
    public function render_restore() {
        echo '<div class="wrap"><h1>Nhật ký khôi phục (Kỹ thuật)</h1>';
        echo '<p>Khôi phục trạng thái plugin từ snapshot đã tạo trước đó tại mục "Snapshot & Logs".</p>';
        echo '</div>';
    }
}