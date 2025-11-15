<?php
/**
 * Fired during plugin activation.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class Claude_SEO_Activator {

    /**
     * Activate the plugin.
     *
     * Checks requirements, creates database tables, sets default options,
     * schedules cron jobs, and flushes rewrite rules.
     */
    public static function activate() {
        // Check PHP version requirement
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(CLAUDE_SEO_PLUGIN_FILE));
            wp_die(
                esc_html__('Claude SEO Pro requires PHP 7.4 or higher. Please upgrade PHP.', 'claude-seo'),
                esc_html__('Plugin Activation Error', 'claude-seo'),
                array('back_link' => true)
            );
        }

        // Check WordPress version requirement
        global $wp_version;
        if (version_compare($wp_version, '6.0', '<')) {
            deactivate_plugins(plugin_basename(CLAUDE_SEO_PLUGIN_FILE));
            wp_die(
                esc_html__('Claude SEO Pro requires WordPress 6.0 or higher. Please upgrade WordPress.', 'claude-seo'),
                esc_html__('Plugin Activation Error', 'claude-seo'),
                array('back_link' => true)
            );
        }

        // Create custom database tables
        self::create_database_tables();

        // Set default options
        self::set_default_options();

        // Schedule cron jobs
        self::schedule_cron_jobs();

        // Flush rewrite rules for sitemap
        flush_rewrite_rules();

        // Set onboarding flag
        add_option('claude_seo_onboarding_complete', false);
        add_option('claude_seo_activation_time', time());
        add_option('claude_seo_version', CLAUDE_SEO_VERSION);
    }

    /**
     * Create custom database tables.
     */
    private static function create_database_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;

        // Required for dbDelta
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // SEO Analysis History Table
        $sql_analysis = "CREATE TABLE {$prefix}claude_seo_analysis (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT UNSIGNED NOT NULL,
            analyzed_at DATETIME NOT NULL,
            seo_score TINYINT UNSIGNED NOT NULL,
            readability_score TINYINT UNSIGNED NOT NULL,
            keyword_density DECIMAL(5,2) DEFAULT 0.00,
            word_count INT UNSIGNED DEFAULT 0,
            internal_links SMALLINT UNSIGNED DEFAULT 0,
            external_links SMALLINT UNSIGNED DEFAULT 0,
            focus_keyword VARCHAR(255) DEFAULT '',
            issues JSON,
            recommendations JSON,
            INDEX idx_post_date (post_id, analyzed_at),
            INDEX idx_score (seo_score DESC)
        ) $charset_collate;";

        // Redirects Management Table
        $sql_redirects = "CREATE TABLE {$prefix}claude_seo_redirects (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            source_url VARCHAR(2048) NOT NULL,
            target_url VARCHAR(2048) NOT NULL,
            redirect_type SMALLINT UNSIGNED DEFAULT 301,
            regex TINYINT(1) DEFAULT 0,
            hit_count INT UNSIGNED DEFAULT 0,
            last_hit DATETIME,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            INDEX idx_source (source_url(191)),
            INDEX idx_status_created (status, created_at)
        ) $charset_collate;";

        // 404 Error Logs Table
        $sql_404 = "CREATE TABLE {$prefix}claude_seo_404_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            url VARCHAR(2048) NOT NULL,
            referrer VARCHAR(2048),
            user_agent VARCHAR(512),
            ip_address VARCHAR(45),
            hit_count INT UNSIGNED DEFAULT 1,
            first_seen DATETIME NOT NULL,
            last_seen DATETIME NOT NULL,
            resolved TINYINT(1) DEFAULT 0,
            INDEX idx_url (url(191)),
            INDEX idx_last_seen (last_seen DESC),
            INDEX idx_resolved (resolved, hit_count DESC)
        ) $charset_collate;";

        // Internal Link Mapping Table
        $sql_links = "CREATE TABLE {$prefix}claude_seo_internal_links (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            source_post_id BIGINT UNSIGNED NOT NULL,
            target_post_id BIGINT UNSIGNED NOT NULL,
            anchor_text VARCHAR(512),
            link_url VARCHAR(2048),
            created_at DATETIME NOT NULL,
            INDEX idx_source (source_post_id),
            INDEX idx_target (target_post_id),
            INDEX idx_relationship (source_post_id, target_post_id),
            UNIQUE KEY unique_link (source_post_id, target_post_id, link_url(191))
        ) $charset_collate;";

        // Content Calendar Table
        $sql_calendar = "CREATE TABLE {$prefix}claude_seo_content_calendar (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT UNSIGNED NULL,
            scheduled_date DATE NOT NULL,
            topic VARCHAR(512) NOT NULL,
            focus_keyword VARCHAR(255),
            target_word_count INT UNSIGNED,
            status ENUM('planned', 'in_progress', 'published') DEFAULT 'planned',
            ai_brief TEXT,
            created_at DATETIME NOT NULL,
            INDEX idx_date_status (scheduled_date, status)
        ) $charset_collate;";

        // Keyword Tracking Table
        $sql_keywords = "CREATE TABLE {$prefix}claude_seo_keyword_tracking (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT UNSIGNED NOT NULL,
            keyword VARCHAR(255) NOT NULL,
            position SMALLINT UNSIGNED,
            search_volume INT UNSIGNED,
            difficulty TINYINT UNSIGNED,
            checked_at DATE NOT NULL,
            INDEX idx_post_keyword (post_id, keyword),
            INDEX idx_keyword_date (keyword, checked_at),
            UNIQUE KEY unique_tracking (post_id, keyword, checked_at)
        ) $charset_collate;";

        // Claude API Usage Tracking Table
        $sql_usage = "CREATE TABLE {$prefix}claude_seo_claude_usage (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            date DATE NOT NULL,
            requests INT UNSIGNED DEFAULT 0,
            tokens_input INT UNSIGNED DEFAULT 0,
            tokens_output INT UNSIGNED DEFAULT 0,
            cost_usd DECIMAL(10,6) DEFAULT 0,
            UNIQUE KEY idx_user_date (user_id, date),
            INDEX idx_date (date DESC)
        ) $charset_collate;";

        // Execute table creation
        dbDelta($sql_analysis);
        dbDelta($sql_redirects);
        dbDelta($sql_404);
        dbDelta($sql_links);
        dbDelta($sql_calendar);
        dbDelta($sql_keywords);
        dbDelta($sql_usage);

        // Store database version for future migrations
        add_option('claude_seo_db_version', '1.0.0');
    }

    /**
     * Set default plugin options.
     */
    private static function set_default_options() {
        $default_settings = array(
            // General Settings
            'site_name' => get_bloginfo('name'),
            'separator' => '|',
            'homepage_title' => '',
            'homepage_description' => '',

            // Schema Settings
            'schema_organization' => array(
                'name' => get_bloginfo('name'),
                'url' => home_url(),
                'logo' => '',
                'social_profiles' => array()
            ),

            // SEO Settings
            'keyword_density_min' => 0.5,
            'keyword_density_max' => 2.5,
            'readability_target' => 60,
            'min_internal_links' => 2,
            'min_content_length' => 300,

            // Sitemap Settings
            'sitemap_enabled' => true,
            'sitemap_post_types' => array('post', 'page'),
            'sitemap_taxonomies' => array('category', 'post_tag'),
            'sitemap_exclude_ids' => array(),

            // Robots.txt
            'robots_txt_custom' => '',

            // Open Graph
            'og_enabled' => true,
            'og_default_image' => '',

            // Twitter Cards
            'twitter_enabled' => true,
            'twitter_card_type' => 'summary_large_image',
            'twitter_site' => '',

            // 404 Monitoring
            '404_monitoring_enabled' => true,
            '404_log_retention_days' => 30,
            '404_email_notifications' => false,

            // Performance
            'cache_enabled' => true,
            'cache_duration' => DAY_IN_SECONDS,

            // Claude API
            'claude_model_default' => 'claude-sonnet-4-5-20250929',
            'claude_rate_limit_rpm' => 50,
            'claude_cost_budget_monthly' => 0, // 0 = unlimited
            'claude_cache_enabled' => true
        );

        add_option('claude_seo_settings', $default_settings);
    }

    /**
     * Schedule cron jobs.
     */
    private static function schedule_cron_jobs() {
        // Daily sitemap regeneration
        if (!wp_next_scheduled('claude_seo_daily_sitemap')) {
            wp_schedule_event(time(), 'daily', 'claude_seo_daily_sitemap');
        }

        // Daily 404 log cleanup
        if (!wp_next_scheduled('claude_seo_daily_404_cleanup')) {
            wp_schedule_event(time(), 'daily', 'claude_seo_daily_404_cleanup');
        }

        // Weekly broken link scan
        if (!wp_next_scheduled('claude_seo_weekly_link_scan')) {
            wp_schedule_event(time(), 'weekly', 'claude_seo_weekly_link_scan');
        }

        // Hourly license check
        if (!wp_next_scheduled('claude_seo_license_check')) {
            wp_schedule_event(time(), 'hourly', 'claude_seo_license_check');
        }
    }
}
