<?php
/**
 * Enhanced activator with additional tables for advanced features.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes
 */

/**
 * Enhanced plugin activation with ML, CWV, and attribution tables.
 */
class Claude_SEO_Activator_Enhanced {

    /**
     * Activate the plugin with enhanced database schema.
     */
    public static function activate() {
        // Run base activation first
        Claude_SEO_Activator::activate();

        // Add enhanced tables
        self::create_enhanced_tables();
    }

    /**
     * Create enhanced database tables.
     */
    private static function create_enhanced_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Core Web Vitals Real-Time Data
        $sql_cwv = "CREATE TABLE {$prefix}claude_seo_cwv (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            site_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
            page_id BIGINT UNSIGNED NOT NULL,
            metric_name VARCHAR(10) NOT NULL,
            value DECIMAL(10,3) NOT NULL,
            rating ENUM('good', 'needs-improvement', 'poor') NOT NULL,
            device_type ENUM('mobile', 'tablet', 'desktop') NOT NULL,
            timestamp DATETIME NOT NULL,
            url TEXT NOT NULL,
            INDEX idx_page_metric (page_id, metric_name, timestamp),
            INDEX idx_timestamp (timestamp)
        ) $charset_collate;";

        // CWV Alerts
        $sql_cwv_alerts = "CREATE TABLE {$prefix}claude_seo_cwv_alerts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_id BIGINT UNSIGNED NOT NULL,
            metric_name VARCHAR(10) NOT NULL,
            value DECIMAL(10,3) NOT NULL,
            rating VARCHAR(20) NOT NULL,
            device_type VARCHAR(10) NOT NULL,
            diagnosis TEXT,
            created_at DATETIME NOT NULL,
            resolved TINYINT(1) DEFAULT 0,
            resolved_at DATETIME,
            INDEX idx_page_resolved (page_id, resolved),
            INDEX idx_created (created_at DESC)
        ) $charset_collate;";

        // ML Predictions
        $sql_predictions = "CREATE TABLE {$prefix}claude_seo_predictions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_id BIGINT UNSIGNED NOT NULL,
            keyword VARCHAR(255) NOT NULL,
            predicted_position_change DECIMAL(5,2) NOT NULL,
            confidence_lower DECIMAL(5,2),
            confidence_upper DECIMAL(5,2),
            magnitude ENUM('stable', 'moderate_gain', 'significant_gain', 'moderate_drop', 'significant_drop'),
            likelihood ENUM('high', 'medium', 'low'),
            prediction_date DATETIME NOT NULL,
            target_date DATE NOT NULL,
            actual_change DECIMAL(5,2),
            accuracy_score DECIMAL(5,2),
            INDEX idx_target_date (target_date),
            INDEX idx_page (page_id)
        ) $charset_collate;";

        // GSC Data Sync (Enhanced)
        $sql_gsc = "CREATE TABLE {$prefix}claude_seo_gsc_data (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_id BIGINT UNSIGNED,
            keyword VARCHAR(255) NOT NULL,
            date DATE NOT NULL,
            clicks INT UNSIGNED DEFAULT 0,
            impressions INT UNSIGNED DEFAULT 0,
            ctr DECIMAL(5,4) NOT NULL,
            position DECIMAL(5,2) NOT NULL,
            country VARCHAR(2),
            device VARCHAR(10),
            UNIQUE KEY unique_daily (page_id, keyword(100), date, device),
            INDEX idx_date (date),
            INDEX idx_keyword (keyword(100))
        ) $charset_collate;";

        // Revenue Attribution
        $sql_attribution = "CREATE TABLE {$prefix}claude_seo_attribution (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            conversion_id VARCHAR(255) NOT NULL,
            page_id BIGINT UNSIGNED,
            session_start DATETIME NOT NULL,
            touchpoint_order INT UNSIGNED NOT NULL,
            attribution_weight DECIMAL(5,4) NOT NULL,
            conversion_value DECIMAL(10,2),
            conversion_date DATETIME,
            attribution_model VARCHAR(50),
            INDEX idx_conversion (conversion_id),
            INDEX idx_page (page_id),
            INDEX idx_date (conversion_date)
        ) $charset_collate;";

        // Opportunities
        $sql_opportunities = "CREATE TABLE {$prefix}claude_seo_opportunities (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            page_id BIGINT UNSIGNED NOT NULL,
            keyword VARCHAR(255),
            current_position DECIMAL(5,2),
            estimated_traffic_gain INT UNSIGNED,
            estimated_revenue_gain DECIMAL(10,2),
            priority ENUM('critical', 'high', 'medium', 'low'),
            status ENUM('open', 'in_progress', 'completed', 'dismissed') DEFAULT 'open',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            completed_at DATETIME,
            INDEX idx_status_priority (status, priority),
            INDEX idx_created (created_at DESC)
        ) $charset_collate;";

        // Competitor Tracking
        $sql_competitors = "CREATE TABLE {$prefix}claude_seo_competitor_rankings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            competitor_domain VARCHAR(255) NOT NULL,
            keyword VARCHAR(255) NOT NULL,
            position DECIMAL(5,2) NOT NULL,
            date DATE NOT NULL,
            url TEXT,
            UNIQUE KEY unique_tracking (competitor_domain, keyword(100), date),
            INDEX idx_date (date)
        ) $charset_collate;";

        // AI Overview Citations
        $sql_ai_citations = "CREATE TABLE {$prefix}claude_seo_ai_citations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            page_id BIGINT UNSIGNED NOT NULL,
            source VARCHAR(50) NOT NULL,
            keyword VARCHAR(255) NOT NULL,
            cited TINYINT(1) NOT NULL,
            position INT UNSIGNED,
            date DATE NOT NULL,
            UNIQUE KEY unique_citation (page_id, source, keyword(100), date),
            INDEX idx_page (page_id),
            INDEX idx_date (date)
        ) $charset_collate;";

        // Execute table creation
        dbDelta($sql_cwv);
        dbDelta($sql_cwv_alerts);
        dbDelta($sql_predictions);
        dbDelta($sql_gsc);
        dbDelta($sql_attribution);
        dbDelta($sql_opportunities);
        dbDelta($sql_competitors);
        dbDelta($sql_ai_citations);

        // Update database version
        update_option('claude_seo_db_version', '2.0.0');
    }
}
