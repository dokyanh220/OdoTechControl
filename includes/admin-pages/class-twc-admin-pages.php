<?php
if (!defined('ABSPATH')) { exit; }

require_once dirname(__DIR__) . '/presets/render-presets.php';
require_once dirname(__DIR__) . '/security/render-security.php';
require_once dirname(__DIR__) . '/snapshot&logs/render-snapshot.php';

class TWC_Admin_Pages {
    use TWC_Admin_Pages_Presets;
    use TWC_Admin_Pages_Security;
    use TWC_Admin_Pages_Snapshot;

    private function button($action, $label, $nonce_action, $extra_fields = []) {
        $html = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;margin-right:8px;">';
        $html .= '<input type="hidden" name="action" value="' . esc_attr($action) . '">';
        $html .= wp_nonce_field($nonce_action, '_wpnonce', true, false);
        foreach ($extra_fields as $name => $val) {
            $html .= '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($val) . '">';
        }
        $html .= '<button class="button button-primary">' . esc_html($label) . '</button>';
        $html .= '</form>';
        return $html;
    }
}
