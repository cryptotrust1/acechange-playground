<?php
/**
 * Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Dashboard {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Render dashboard
     */
    public function render() {
        $db = AI_SEO_Manager_Database::get_instance();
        $autopilot = AI_SEO_Manager_Autopilot_Engine::get_instance();
        $ai_manager = AI_SEO_Manager_AI_Manager::get_instance();
        $workflow = AI_SEO_Manager_Approval_Workflow::get_instance();

        // Získaj štatistiky
        global $wpdb;
        $rec_table = $db->get_table('recommendations');

        $stats = array(
            'pending' => $wpdb->get_var("SELECT COUNT(*) FROM {$rec_table} WHERE status = 'pending'"),
            'awaiting_approval' => $wpdb->get_var("SELECT COUNT(*) FROM {$rec_table} WHERE status = 'awaiting_approval'"),
            'completed' => $wpdb->get_var("SELECT COUNT(*) FROM {$rec_table} WHERE status = 'completed'"),
            'rejected' => $wpdb->get_var("SELECT COUNT(*) FROM {$rec_table} WHERE status = 'rejected'"),
        );

        $autopilot_stats = $autopilot->get_stats();
        $api_usage = $ai_manager->get_usage_stats();
        $pending_approvals = $workflow->get_pending_approvals(5);
        $recent_recommendations = $db->get_pending_recommendations(10);

        include AI_SEO_MANAGER_PLUGIN_DIR . 'admin/views/dashboard-page.php';
    }
}
