<?php
/**
 * Core Web Vitals Processor - Backend.
 *
 * Processes real-time CWV data, stores metrics, calculates aggregates,
 * and triggers alerts when thresholds are violated.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/monitoring
 */

/**
 * Processes and stores Core Web Vitals metrics.
 */
class Claude_SEO_CWV_Processor {

    /**
     * CWV thresholds (Google standards).
     */
    const THRESHOLDS = array(
        'LCP' => array('good' => 2500, 'poor' => 4000),
        'INP' => array('good' => 200, 'poor' => 500),
        'CLS' => array('good' => 0.1, 'poor' => 0.25),
        'FCP' => array('good' => 1800, 'poor' => 3000),
        'TTFB' => array('good' => 800, 'poor' => 1800)
    );

    /**
     * Process batch of metrics.
     *
     * @param array $batch Metrics batch.
     */
    public function process_batch($batch) {
        if (empty($batch['metrics'])) {
            return;
        }

        global $wpdb;
        $table = Claude_SEO_Database::get_table_name('cwv');

        foreach ($batch['metrics'] as $metric) {
            // Store metric
            $this->store_metric($metric);

            // Check for alerts
            $this->check_alerts($metric);
        }

        // Calculate 75th percentile aggregates every 5 minutes
        $this->maybe_calculate_aggregates();
    }

    /**
     * Store individual metric.
     *
     * @param array $metric Metric data.
     */
    private function store_metric($metric) {
        global $wpdb;

        $wpdb->insert(
            Claude_SEO_Database::get_table_name('cwv'),
            array(
                'site_id' => isset($metric['siteId']) ? absint($metric['siteId']) : 1,
                'page_id' => absint($metric['pageId']),
                'metric_name' => sanitize_key($metric['name']),
                'value' => floatval($metric['value']),
                'rating' => sanitize_key($metric['rating']),
                'device_type' => sanitize_key($metric['deviceType']),
                'timestamp' => gmdate('Y-m-d H:i:s', $metric['timestamp'] / 1000),
                'url' => esc_url_raw($metric['url'])
            ),
            array('%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Check if metric violates thresholds and trigger alerts.
     *
     * @param array $metric Metric data.
     */
    private function check_alerts($metric) {
        if ($metric['rating'] !== 'poor') {
            return; // Only alert on poor metrics
        }

        $alert_data = array(
            'type' => 'cwv_degradation',
            'metric' => $metric['name'],
            'page_id' => $metric['pageId'],
            'value' => $metric['value'],
            'rating' => $metric['rating'],
            'device_type' => $metric['deviceType'],
            'diagnosis' => $this->diagnose_issue($metric),
            'timestamp' => current_time('mysql')
        );

        // Check if we already alerted recently (prevent spam)
        $recent_alert = get_transient("cwv_alert_{$metric['pageId']}_{$metric['name']}");

        if (!$recent_alert) {
            $this->trigger_alert($alert_data);

            // Set transient to prevent duplicate alerts for 1 hour
            set_transient("cwv_alert_{$metric['pageId']}_{$metric['name']}", true, HOUR_IN_SECONDS);
        }
    }

    /**
     * Diagnose CWV issue and provide recommendations.
     *
     * @param array $metric Metric data.
     * @return array Diagnosis and recommendations.
     */
    private function diagnose_issue($metric) {
        switch ($metric['name']) {
            case 'LCP':
                return $this->diagnose_lcp($metric);
            case 'INP':
                return $this->diagnose_inp($metric);
            case 'CLS':
                return $this->diagnose_cls($metric);
            case 'FCP':
                return $this->diagnose_fcp($metric);
            case 'TTFB':
                return $this->diagnose_ttfb($metric);
            default:
                return array();
        }
    }

    /**
     * Diagnose LCP issues.
     *
     * @param array $metric Metric data.
     * @return array Diagnosis.
     */
    private function diagnose_lcp($metric) {
        return array(
            'issue' => 'Largest Contentful Paint is slow',
            'impact' => 'Users see content loading slowly',
            'recommendations' => array(
                'Optimize images (use WebP, proper sizing)',
                'Implement lazy loading for below-fold images',
                'Reduce server response time (TTFB)',
                'Remove render-blocking JavaScript and CSS',
                'Use a CDN for static assets',
                'Preload LCP image with <link rel="preload">'
            ),
            'priority' => 'high'
        );
    }

    /**
     * Diagnose INP issues.
     *
     * @param array $metric Metric data.
     * @return array Diagnosis.
     */
    private function diagnose_inp($metric) {
        return array(
            'issue' => 'Interaction to Next Paint is slow',
            'impact' => 'Page feels sluggish when users interact',
            'recommendations' => array(
                'Reduce JavaScript execution time',
                'Break up long tasks into smaller chunks',
                'Use web workers for heavy computations',
                'Defer non-critical JavaScript',
                'Optimize event handlers',
                'Remove unnecessary third-party scripts'
            ),
            'priority' => 'high'
        );
    }

    /**
     * Diagnose CLS issues.
     *
     * @param array $metric Metric data.
     * @return array Diagnosis.
     */
    private function diagnose_cls($metric) {
        return array(
            'issue' => 'Cumulative Layout Shift is high',
            'impact' => 'Content jumps around unexpectedly',
            'recommendations' => array(
                'Set explicit width/height on images and videos',
                'Reserve space for ads and embeds',
                'Avoid inserting content above existing content',
                'Use CSS aspect-ratio for responsive images',
                'Preload fonts to avoid FOIT/FOUT',
                'Avoid animations that cause layout shifts'
            ),
            'priority' => 'medium'
        );
    }

    /**
     * Diagnose FCP issues.
     *
     * @param array $metric Metric data.
     * @return array Diagnosis.
     */
    private function diagnose_fcp($metric) {
        return array(
            'issue' => 'First Contentful Paint is slow',
            'impact' => 'Users wait too long to see any content',
            'recommendations' => array(
                'Reduce server response time',
                'Eliminate render-blocking resources',
                'Minimize critical CSS',
                'Preconnect to required origins',
                'Use HTTP/2 or HTTP/3',
                'Enable text compression (Gzip/Brotli)'
            ),
            'priority' => 'high'
        );
    }

    /**
     * Diagnose TTFB issues.
     *
     * @param array $metric Metric data.
     * @return array Diagnosis.
     */
    private function diagnose_ttfb($metric) {
        return array(
            'issue' => 'Time to First Byte is slow',
            'impact' => 'Server takes too long to respond',
            'recommendations' => array(
                'Use object caching (Redis/Memcached)',
                'Enable page caching',
                'Optimize database queries',
                'Upgrade hosting plan',
                'Use a CDN',
                'Reduce plugin overhead'
            ),
            'priority' => 'critical'
        );
    }

    /**
     * Trigger alert for CWV issue.
     *
     * @param array $alert_data Alert data.
     */
    private function trigger_alert($alert_data) {
        // Store alert in database
        global $wpdb;

        $wpdb->insert(
            Claude_SEO_Database::get_table_name('cwv_alerts'),
            array(
                'page_id' => $alert_data['page_id'],
                'metric_name' => $alert_data['metric'],
                'value' => $alert_data['value'],
                'rating' => $alert_data['rating'],
                'device_type' => $alert_data['device_type'],
                'diagnosis' => wp_json_encode($alert_data['diagnosis']),
                'created_at' => $alert_data['timestamp'],
                'resolved' => 0
            ),
            array('%d', '%s', '%f', '%s', '%s', '%s', '%s', '%d')
        );

        // Send email notification if enabled
        $settings = get_option('claude_seo_settings', array());
        if (!empty($settings['cwv_email_alerts'])) {
            $this->send_alert_email($alert_data);
        }

        // Trigger action hook for custom integrations (Slack, etc.)
        do_action('claude_seo_cwv_alert', $alert_data);

        Claude_SEO_Logger::warning('CWV threshold violated', array(
            'metric' => $alert_data['metric'],
            'value' => $alert_data['value'],
            'page_id' => $alert_data['page_id']
        ));
    }

    /**
     * Send email alert.
     *
     * @param array $alert_data Alert data.
     */
    private function send_alert_email($alert_data) {
        $post = get_post($alert_data['page_id']);
        $post_title = $post ? $post->post_title : 'Unknown Page';
        $post_url = $post ? get_permalink($post) : '';

        $subject = sprintf(
            '[%s] Core Web Vitals Alert: %s on %s',
            get_bloginfo('name'),
            $alert_data['metric'],
            $post_title
        );

        $message = sprintf(
            "Core Web Vitals Alert\n\n" .
            "Page: %s\n" .
            "URL: %s\n" .
            "Metric: %s\n" .
            "Value: %.2f\n" .
            "Rating: %s\n" .
            "Device: %s\n\n" .
            "Issue: %s\n\n" .
            "Recommendations:\n%s\n\n" .
            "View details: %s",
            $post_title,
            $post_url,
            $alert_data['metric'],
            $alert_data['value'],
            $alert_data['rating'],
            $alert_data['device_type'],
            $alert_data['diagnosis']['issue'],
            '- ' . implode("\n- ", $alert_data['diagnosis']['recommendations']),
            admin_url('admin.php?page=claude-seo-cwv')
        );

        $to = get_option('admin_email');
        wp_mail($to, $subject, $message);
    }

    /**
     * Calculate 75th percentile aggregates.
     */
    private function maybe_calculate_aggregates() {
        // Only calculate every 5 minutes
        $last_calc = get_transient('cwv_last_aggregate_calc');
        if ($last_calc) {
            return;
        }

        $this->calculate_aggregates();
        set_transient('cwv_last_aggregate_calc', time(), 5 * MINUTE_IN_SECONDS);
    }

    /**
     * Calculate 75th percentile for all metrics.
     */
    private function calculate_aggregates() {
        global $wpdb;
        $table = Claude_SEO_Database::get_table_name('cwv');

        $metrics = array('LCP', 'INP', 'CLS', 'FCP', 'TTFB');
        $devices = array('mobile', 'desktop', 'tablet');

        foreach ($metrics as $metric) {
            foreach ($devices as $device) {
                $p75 = $this->calculate_percentile($metric, $device, 75);

                if ($p75 !== null) {
                    $this->store_aggregate($metric, $device, $p75);
                }
            }
        }
    }

    /**
     * Calculate percentile for metric.
     *
     * @param string $metric     Metric name.
     * @param string $device     Device type.
     * @param int    $percentile Percentile (75 for 75th).
     * @return float|null Percentile value.
     */
    private function calculate_percentile($metric, $device, $percentile) {
        global $wpdb;
        $table = Claude_SEO_Database::get_table_name('cwv');

        // Get values from last 5 minutes
        $sql = $wpdb->prepare(
            "SELECT value
             FROM {$table}
             WHERE metric_name = %s
             AND device_type = %s
             AND timestamp >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
             ORDER BY value ASC",
            $metric,
            $device
        );

        $values = $wpdb->get_col($sql);

        if (empty($values)) {
            return null;
        }

        $index = ceil((count($values) * $percentile) / 100) - 1;
        return $values[$index];
    }

    /**
     * Store aggregate metric.
     *
     * @param string $metric Metric name.
     * @param string $device Device type.
     * @param float  $value  Percentile value.
     */
    private function store_aggregate($metric, $device, $value) {
        $rating = $this->get_rating($metric, $value);

        update_option("cwv_p75_{$metric}_{$device}", array(
            'value' => $value,
            'rating' => $rating,
            'timestamp' => time()
        ), false);
    }

    /**
     * Get rating for metric value.
     *
     * @param string $metric Metric name.
     * @param float  $value  Metric value.
     * @return string Rating (good/needs-improvement/poor).
     */
    private function get_rating($metric, $value) {
        if (!isset(self::THRESHOLDS[$metric])) {
            return 'unknown';
        }

        $thresholds = self::THRESHOLDS[$metric];

        if ($value <= $thresholds['good']) {
            return 'good';
        } elseif ($value <= $thresholds['poor']) {
            return 'needs-improvement';
        } else {
            return 'poor';
        }
    }

    /**
     * Get current CWV status for page.
     *
     * @param int $page_id Page ID.
     * @return array CWV metrics.
     */
    public static function get_page_cwv_status($page_id) {
        $metrics = array('LCP', 'INP', 'CLS', 'FCP', 'TTFB');
        $status = array();

        foreach ($metrics as $metric) {
            $mobile = get_option("cwv_p75_{$metric}_mobile", array());
            $desktop = get_option("cwv_p75_{$metric}_desktop", array());

            $status[$metric] = array(
                'mobile' => $mobile,
                'desktop' => $desktop
            );
        }

        return $status;
    }
}
