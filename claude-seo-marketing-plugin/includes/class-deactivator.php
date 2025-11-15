<?php
/**
 * Fired during plugin deactivation.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class Claude_SEO_Deactivator {

    /**
     * Deactivate the plugin.
     *
     * Unschedules cron jobs and flushes rewrite rules.
     * Data is kept intact for potential reactivation.
     */
    public static function deactivate() {
        // Unschedule all cron jobs
        self::unschedule_cron_jobs();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Clear transients
        self::clear_transients();
    }

    /**
     * Unschedule all cron jobs.
     */
    private static function unschedule_cron_jobs() {
        $cron_hooks = array(
            'claude_seo_daily_sitemap',
            'claude_seo_daily_404_cleanup',
            'claude_seo_weekly_link_scan',
            'claude_seo_license_check'
        );

        foreach ($cron_hooks as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
        }
    }

    /**
     * Clear plugin transients.
     */
    private static function clear_transients() {
        global $wpdb;

        // Delete all plugin transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_claude_seo_%'
             OR option_name LIKE '_transient_timeout_claude_seo_%'"
        );
    }
}
