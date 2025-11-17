<?php
/**
 * Admin Menu
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Admin_Menu {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_menu_pages'));
    }

    /**
     * Add menu pages
     */
    public function add_menu_pages() {
        // Main menu
        add_menu_page(
            __('AI SEO Manager', 'ai-seo-manager'),
            __('AI SEO Manager', 'ai-seo-manager'),
            'edit_posts',
            'ai-seo-manager',
            array($this, 'render_dashboard_page'),
            'dashicons-chart-line',
            30
        );

        // Dashboard (same as main)
        add_submenu_page(
            'ai-seo-manager',
            __('Dashboard', 'ai-seo-manager'),
            __('Dashboard', 'ai-seo-manager'),
            'edit_posts',
            'ai-seo-manager',
            array($this, 'render_dashboard_page')
        );

        // Approvals
        add_submenu_page(
            'ai-seo-manager',
            __('Approvals', 'ai-seo-manager'),
            __('Approvals', 'ai-seo-manager'),
            'edit_posts',
            'ai-seo-manager-approvals',
            array($this, 'render_approvals_page')
        );

        // Recommendations
        add_submenu_page(
            'ai-seo-manager',
            __('Recommendations', 'ai-seo-manager'),
            __('Recommendations', 'ai-seo-manager'),
            'edit_posts',
            'ai-seo-manager-recommendations',
            array($this, 'render_recommendations_page')
        );

        // Autopilot
        add_submenu_page(
            'ai-seo-manager',
            __('Autopilot', 'ai-seo-manager'),
            __('Autopilot', 'ai-seo-manager'),
            'manage_options',
            'ai-seo-manager-autopilot',
            array($this, 'render_autopilot_page')
        );

        // Settings
        add_submenu_page(
            'ai-seo-manager',
            __('Settings', 'ai-seo-manager'),
            __('Settings', 'ai-seo-manager'),
            'manage_options',
            'ai-seo-manager-settings',
            array($this, 'render_settings_page')
        );

        // Debug Dashboard (only for admins)
        add_submenu_page(
            'ai-seo-manager',
            __('🔧 Debug Dashboard', 'ai-seo-manager'),
            __('🔧 Debug Dashboard', 'ai-seo-manager'),
            'manage_options',
            'ai-seo-manager-debug',
            array($this, 'render_debug_page')
        );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'admin/class-dashboard.php';
        $dashboard = AI_SEO_Manager_Dashboard::get_instance();
        $dashboard->render();
    }

    /**
     * Render approvals page
     */
    public function render_approvals_page() {
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'ai-seo-manager'));
        }

        $workflow = AI_SEO_Manager_Approval_Workflow::get_instance();
        $pending = $workflow->get_pending_approvals(50);

        include AI_SEO_MANAGER_PLUGIN_DIR . 'admin/views/approvals-page.php';
    }

    /**
     * Render recommendations page
     */
    public function render_recommendations_page() {
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'ai-seo-manager'));
        }

        $db = AI_SEO_Manager_Database::get_instance();
        $recommendations = $db->get_pending_recommendations(100);

        include AI_SEO_MANAGER_PLUGIN_DIR . 'admin/views/recommendations-page.php';
    }

    /**
     * Render autopilot page
     */
    public function render_autopilot_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'ai-seo-manager'));
        }

        $autopilot = AI_SEO_Manager_Autopilot_Engine::get_instance();
        $stats = $autopilot->get_stats();

        include AI_SEO_MANAGER_PLUGIN_DIR . 'admin/views/autopilot-page.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'ai-seo-manager'));
        }

        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'admin/class-settings-page.php';
        $settings_page = AI_SEO_Manager_Settings_Page::get_instance();
        $settings_page->render();
    }

    /**
     * Render debug dashboard page
     */
    public function render_debug_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'ai-seo-manager'));
        }

        // Load debug AJAX handler
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'admin/class-debug-ajax-handler.php';

        // Render debug dashboard
        include AI_SEO_MANAGER_PLUGIN_DIR . 'admin/views/debug-real-time.php';
    }
}
