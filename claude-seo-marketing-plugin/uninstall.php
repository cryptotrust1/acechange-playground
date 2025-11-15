<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Claude_SEO
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user wants to keep data
$keep_data = get_option('claude_seo_keep_data_on_uninstall', false);

if ($keep_data) {
    return; // Keep all data
}

global $wpdb;

// Delete custom tables
$tables = array(
    $wpdb->prefix . 'claude_seo_analysis',
    $wpdb->prefix . 'claude_seo_redirects',
    $wpdb->prefix . 'claude_seo_404_logs',
    $wpdb->prefix . 'claude_seo_internal_links',
    $wpdb->prefix . 'claude_seo_content_calendar',
    $wpdb->prefix . 'claude_seo_keyword_tracking',
    $wpdb->prefix . 'claude_seo_claude_usage'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Delete options
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'claude_seo_%'");

// Delete post meta
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_seo_%'");

// Delete transients
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_claude_seo_%'
     OR option_name LIKE '_transient_timeout_claude_seo_%'"
);

// Clear scheduled events
$cron_hooks = array(
    'claude_seo_daily_sitemap',
    'claude_seo_daily_404_cleanup',
    'claude_seo_weekly_link_scan',
    'claude_seo_license_check'
);

foreach ($cron_hooks as $hook) {
    wp_clear_scheduled_hook($hook);
}
