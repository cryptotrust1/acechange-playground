<?php
/**
 * 404 error monitor.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/seo
 */

/**
 * Tracks and logs 404 errors.
 */
class Claude_SEO_404_Monitor {

    /**
     * Log 404 errors.
     */
    public function log_404_errors() {
        if (!is_404()) {
            return;
        }

        $settings = get_option('claude_seo_settings', array());

        if (empty($settings['404_monitoring_enabled'])) {
            return;
        }

        $url = esc_url_raw($_SERVER['REQUEST_URI']);
        $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        $ip_address = $this->get_anonymized_ip();

        // Check if already logged today
        global $wpdb;
        $table = Claude_SEO_Database::get_table_name('404_logs');

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, hit_count FROM {$table}
             WHERE url = %s AND DATE(last_seen) = CURDATE()
             LIMIT 1",
            $url
        ));

        $now = current_time('mysql', 1);

        if ($existing) {
            // Update existing record
            $wpdb->update(
                $table,
                array(
                    'hit_count' => $existing->hit_count + 1,
                    'last_seen' => $now,
                    'referrer' => $referrer,
                    'user_agent' => $user_agent
                ),
                array('id' => $existing->id),
                array('%d', '%s', '%s', '%s'),
                array('%d')
            );
        } else {
            // Insert new record
            $wpdb->insert(
                $table,
                array(
                    'url' => $url,
                    'referrer' => $referrer,
                    'user_agent' => $user_agent,
                    'ip_address' => $ip_address,
                    'hit_count' => 1,
                    'first_seen' => $now,
                    'last_seen' => $now,
                    'resolved' => 0
                ),
                array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d')
            );
        }
    }

    /**
     * Get anonymized IP address (GDPR compliant).
     *
     * @return string Anonymized IP.
     */
    private function get_anonymized_ip() {
        $ip = $_SERVER['REMOTE_ADDR'];

        // Anonymize by removing last octet for IPv4 or last 80 bits for IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return substr($ip, 0, strrpos($ip, ':')) . ':0000:0000:0000:0000';
        }

        return '0.0.0.0';
    }

    /**
     * Get top 404 errors.
     *
     * @param int $limit Limit.
     * @return array 404 errors.
     */
    public static function get_top_errors($limit = 10) {
        return Claude_SEO_Database::get_results(
            '404_logs',
            array('resolved' => 0),
            'hit_count DESC',
            $limit
        );
    }

    /**
     * Mark error as resolved.
     *
     * @param int $id Error ID.
     * @return bool Success.
     */
    public static function mark_resolved($id) {
        return Claude_SEO_Database::update(
            '404_logs',
            array('resolved' => 1),
            array('id' => $id)
        );
    }

    /**
     * Clean old 404 logs.
     */
    public static function cleanup_old_logs() {
        $settings = get_option('claude_seo_settings', array());
        $retention_days = isset($settings['404_log_retention_days']) ? $settings['404_log_retention_days'] : 30;

        global $wpdb;
        $table = Claude_SEO_Database::get_table_name('404_logs');

        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table}
             WHERE last_seen < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $retention_days
        ));
    }
}
