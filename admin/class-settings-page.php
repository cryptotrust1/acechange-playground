<?php
/**
 * Settings Page
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Settings_Page {

    private static $instance = null;
    private $settings;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->settings = AI_SEO_Manager_Settings::get_instance();
        add_action('admin_post_ai_seo_manager_save_settings', array($this, 'save_settings'));
    }

    /**
     * Render settings page
     */
    public function render() {
        $current_settings = $this->settings->get_all();
        include AI_SEO_MANAGER_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Save settings
     */
    public function save_settings() {
        check_admin_referer('ai_seo_manager_settings');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'ai-seo-manager'));
        }

        $new_settings = array();

        // AI Settings
        $new_settings['ai_provider'] = sanitize_text_field($_POST['ai_provider'] ?? 'claude');
        $new_settings['claude_api_key'] = sanitize_text_field($_POST['claude_api_key'] ?? '');
        $new_settings['claude_model'] = sanitize_text_field($_POST['claude_model'] ?? 'claude-3-5-sonnet-20241022');
        $new_settings['openai_api_key'] = sanitize_text_field($_POST['openai_api_key'] ?? '');
        $new_settings['openai_model'] = sanitize_text_field($_POST['openai_model'] ?? 'gpt-4-turbo-preview');

        // Google Analytics
        $new_settings['ga4_measurement_id'] = sanitize_text_field($_POST['ga4_measurement_id'] ?? '');
        $new_settings['ga4_api_secret'] = sanitize_text_field($_POST['ga4_api_secret'] ?? '');

        // Google Search Console
        $new_settings['gsc_client_id'] = sanitize_text_field($_POST['gsc_client_id'] ?? '');
        $new_settings['gsc_client_secret'] = sanitize_text_field($_POST['gsc_client_secret'] ?? '');

        // SEO Analysis
        $new_settings['auto_analysis'] = isset($_POST['auto_analysis']);
        $new_settings['analysis_frequency'] = sanitize_text_field($_POST['analysis_frequency'] ?? 'daily');
        $new_settings['min_seo_score'] = intval($_POST['min_seo_score'] ?? 70);

        // AI Manager
        $new_settings['ai_manager_enabled'] = isset($_POST['ai_manager_enabled']);
        $new_settings['recommendation_threshold'] = floatval($_POST['recommendation_threshold'] ?? 0.7);
        $new_settings['auto_prioritize'] = isset($_POST['auto_prioritize']);

        // Autopilot
        $new_settings['autopilot_enabled'] = isset($_POST['autopilot_enabled']);
        $new_settings['autopilot_mode'] = sanitize_text_field($_POST['autopilot_mode'] ?? 'approval');
        $new_settings['autopilot_actions'] = array(
            'meta_description' => isset($_POST['autopilot_meta_description']),
            'alt_texts' => isset($_POST['autopilot_alt_texts']),
            'headings' => isset($_POST['autopilot_headings']),
            'internal_links' => isset($_POST['autopilot_internal_links']),
        );

        // Advanced
        $new_settings['max_api_calls_per_day'] = intval($_POST['max_api_calls_per_day'] ?? 100);
        $new_settings['debug_mode'] = isset($_POST['debug_mode']);

        // Merge with existing
        $merged = array_merge($this->settings->get_all(), $new_settings);
        $this->settings->save($merged);

        wp_safe_redirect(add_query_arg('updated', 'true', admin_url('admin.php?page=ai-seo-manager-settings')));
        exit;
    }
}
