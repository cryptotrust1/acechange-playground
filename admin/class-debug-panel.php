<?php
/**
 * Admin Debug Panel
 * Admin rozhranie pre debug logy a performance monitoring
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Debug_Panel {

    private static $instance = null;
    private $logger;
    private $performance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->logger = AI_SEO_Manager_Debug_Logger::get_instance();
        $this->performance = AI_SEO_Manager_Performance_Monitor::get_instance();

        add_action('admin_menu', array($this, 'add_debug_menu'), 99);
        add_action('admin_init', array($this, 'handle_debug_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_debug_assets'));
    }

    /**
     * Pridanie debug menu
     */
    public function add_debug_menu() {
        // Pridaj submenu len ak je debug aktívny
        if (!$this->logger->is_enabled()) {
            return;
        }

        add_submenu_page(
            'ai-seo-manager',
            __('Debug Logs', 'ai-seo-manager'),
            __('Debug Logs', 'ai-seo-manager'),
            'manage_options',
            'ai-seo-manager-debug',
            array($this, 'render_debug_page')
        );
    }

    /**
     * Načítanie debug assets
     */
    public function enqueue_debug_assets($hook) {
        if (strpos($hook, 'ai-seo-manager-debug') === false) {
            return;
        }

        wp_enqueue_style(
            'ai-seo-debug-panel',
            AI_SEO_MANAGER_PLUGIN_URL . 'assets/css/debug-panel.css',
            array(),
            AI_SEO_MANAGER_VERSION
        );

        wp_enqueue_script(
            'ai-seo-debug-panel',
            AI_SEO_MANAGER_PLUGIN_URL . 'assets/js/debug-panel.js',
            array('jquery'),
            AI_SEO_MANAGER_VERSION,
            true
        );

        wp_localize_script('ai-seo-debug-panel', 'aiSeoDebug', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_seo_debug_nonce'),
        ));
    }

    /**
     * Spracovanie debug akcií
     */
    public function handle_debug_actions() {
        if (!isset($_GET['ai_seo_debug_action']) || !current_user_can('manage_options')) {
            return;
        }

        check_admin_referer('ai_seo_debug_action');

        $action = sanitize_text_field($_GET['ai_seo_debug_action']);

        switch ($action) {
            case 'clear_logs':
                $this->logger->clear_all_logs();
                wp_safe_redirect(add_query_arg(array(
                    'page' => 'ai-seo-manager-debug',
                    'message' => 'logs_cleared',
                ), admin_url('admin.php')));
                exit;

            case 'clean_old_logs':
                $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
                $this->logger->clean_old_logs($days);
                wp_safe_redirect(add_query_arg(array(
                    'page' => 'ai-seo-manager-debug',
                    'message' => 'old_logs_cleaned',
                ), admin_url('admin.php')));
                exit;

            case 'export_logs':
                $this->export_logs();
                exit;

            case 'reset_performance':
                $this->performance->reset_api_stats();
                wp_safe_redirect(add_query_arg(array(
                    'page' => 'ai-seo-manager-debug',
                    'message' => 'performance_reset',
                ), admin_url('admin.php')));
                exit;
        }
    }

    /**
     * Export logov do CSV
     */
    private function export_logs() {
        $csv_data = $this->logger->export_logs_csv();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=ai-seo-debug-logs-' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');

        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
    }

    /**
     * Render debug stránky
     */
    public function render_debug_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Získaj filtre z requestu
        $level = isset($_GET['level']) ? sanitize_text_field($_GET['level']) : null;
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : null;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 50;

        // Získaj logy
        $logs = $this->logger->get_logs(array(
            'level' => $level,
            'search' => $search,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
        ));

        $total_logs = $this->logger->get_logs_count(array(
            'level' => $level,
            'search' => $search,
        ));

        $stats = $this->logger->get_stats();
        $memory_info = $this->performance->get_memory_info();
        $api_stats = $this->performance->get_api_performance_stats();

        include AI_SEO_MANAGER_PLUGIN_DIR . 'admin/views/debug-page.php';
    }

    /**
     * Získanie badge class pre úroveň logu
     */
    public static function get_level_badge_class($level) {
        $level = strtolower($level);
        $classes = array(
            'error' => 'error',
            'warning' => 'warning',
            'info' => 'info',
            'debug' => 'secondary',
        );

        return $classes[$level] ?? 'secondary';
    }

    /**
     * Formátovanie log typu
     */
    public static function format_log_type($log_type) {
        // Extract level from log_type (e.g., 'debug_error' -> 'ERROR')
        $parts = explode('_', $log_type);
        if (count($parts) >= 2 && $parts[0] === 'debug') {
            return strtoupper($parts[1]);
        }

        return strtoupper($log_type);
    }
}
