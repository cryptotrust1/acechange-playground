<?php
/**
 * Plugin Name: AI SEO Manager Pro
 * Plugin URI: https://github.com/cryptotrust1/acechange-playground
 * Description: Inteligentný AI SEO Manažér s Claude AI, Google Analytics, automatickou analýzou a approval workflow
 * Version: 1.0.0
 * Author: AceChange
 * Author URI: https://acechange.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-seo-manager
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Zabráni priamemu prístupu
if (!defined('ABSPATH')) {
    exit;
}

// Definície konštánt
define('AI_SEO_MANAGER_VERSION', '1.0.0');
define('AI_SEO_MANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_SEO_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_SEO_MANAGER_PLUGIN_FILE', __FILE__);

// Autoloader
require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/class-autoloader.php';

// Debug System (načíta sa pred všetkým ostatným)
if (defined('WP_DEBUG') && WP_DEBUG || defined('AI_SEO_DEBUG') && AI_SEO_DEBUG) {
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/class-debug-logger.php';
    require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/class-performance-monitor.php';
}

/**
 * Hlavná trieda pluginu
 */
class AI_SEO_Manager {

    private static $instance = null;

    /**
     * Singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Konštruktor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }

    /**
     * Inicializácia hookov
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }

    /**
     * Načítanie závislostí
     */
    private function load_dependencies() {
        // Core
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/class-database.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/class-settings.php';

        // AI Integrácia
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/ai/class-claude-client.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/ai/class-openai-client.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/ai/class-ai-manager.php';

        // Analytics
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/analytics/class-google-analytics.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/analytics/class-search-console.php';

        // SEO Analysis
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/seo/class-technical-seo.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/seo/class-content-analyzer.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/seo/class-competitor-analyzer.php';

        // AI SEO Manager
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/manager/class-seo-recommendations.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/manager/class-task-prioritizer.php';

        // Auto-pilot
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/autopilot/class-autopilot-engine.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/autopilot/class-approval-workflow.php';

        // Admin
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'admin/class-admin-menu.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'admin/class-dashboard.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'admin/class-settings-page.php';

        // Debug Panel
        if (defined('WP_DEBUG') && WP_DEBUG || defined('AI_SEO_DEBUG') && AI_SEO_DEBUG) {
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'admin/class-debug-panel.php';
        }

        // API Endpoints
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/api/class-rest-api.php';

        // Social Media Manager
        if (get_option('ai_seo_social_enabled', true)) {
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-social-database.php';
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-platform-registry.php';
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-rate-limiter.php';
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-social-media-manager.php';
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-ai-content-generator.php';
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-scheduler.php';
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-analytics.php';
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-admin-menu.php';
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-ajax-handler.php';

            // Platform Clients
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-platform-client.php';
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-telegram-client.php';
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-facebook-client.php';
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-instagram-client.php';
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-twitter-client.php';
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-linkedin-client.php';
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-youtube-client.php';
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-tiktok-client.php';
        }
    }

    /**
     * Inicializácia pluginu
     */
    public function init() {
        // Inicializácia debug systému
        if (defined('WP_DEBUG') && WP_DEBUG || defined('AI_SEO_DEBUG') && AI_SEO_DEBUG) {
            AI_SEO_Manager_Debug_Logger::get_instance();
            AI_SEO_Manager_Performance_Monitor::get_instance();
            AI_SEO_Manager_Debug_Panel::get_instance();

            // Log plugin initialization
            AI_SEO_Manager_Debug_Logger::get_instance()->info('AI SEO Manager plugin initialized', array(
                'version' => AI_SEO_MANAGER_VERSION,
                'debug_mode' => defined('AI_SEO_DEBUG') && AI_SEO_DEBUG,
            ));
        }

        // Inicializácia komponentov
        AI_SEO_Manager_Database::get_instance();
        AI_SEO_Manager_Settings::get_instance();
        AI_SEO_Manager_AI_Manager::get_instance();
        AI_SEO_Manager_Admin_Menu::get_instance();
        AI_SEO_Manager_REST_API::get_instance();
        AI_SEO_Manager_Autopilot_Engine::get_instance();

        // Social Media Manager komponenty
        if (get_option('ai_seo_social_enabled', true)) {
            AI_SEO_Social_Database::get_instance();
            AI_SEO_Social_Media_Manager::get_instance();
            AI_SEO_Social_Scheduler::get_instance();
            AI_SEO_Social_Analytics::get_instance();
            AI_SEO_Social_Admin_Menu::get_instance();
            AI_SEO_Social_AJAX_Handler::get_instance();
        }

        // Načítanie prekladov
        load_plugin_textdomain('ai-seo-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Trigger init hook
        do_action('ai_seo_manager_init');
    }

    /**
     * Aktivácia pluginu
     */
    public function activate() {
        AI_SEO_Manager_Database::get_instance()->create_tables();
        AI_SEO_Manager_Settings::get_instance()->set_default_settings();

        // Social Media Manager tabuľky
        if (get_option('ai_seo_social_enabled', true)) {
            require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-social-database.php';
            AI_SEO_Social_Database::get_instance()->create_tables();
        }

        // Naplánuj cron joby
        if (!wp_next_scheduled('ai_seo_manager_daily_analysis')) {
            wp_schedule_event(time(), 'daily', 'ai_seo_manager_daily_analysis');
        }

        flush_rewrite_rules();
    }

    /**
     * Deaktivácia pluginu
     */
    public function deactivate() {
        wp_clear_scheduled_hook('ai_seo_manager_daily_analysis');
        flush_rewrite_rules();
    }

    /**
     * Načítanie admin assetov
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'ai-seo-manager') === false) {
            return;
        }

        wp_enqueue_style(
            'ai-seo-manager-admin',
            AI_SEO_MANAGER_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AI_SEO_MANAGER_VERSION
        );

        wp_enqueue_script(
            'ai-seo-manager-admin',
            AI_SEO_MANAGER_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-api'),
            AI_SEO_MANAGER_VERSION,
            true
        );

        wp_localize_script('ai-seo-manager-admin', 'aiSeoManager', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('ai-seo-manager/v1'),
            'nonce' => wp_create_nonce('ai_seo_manager_nonce'),
            'i18n' => array(
                'analyzing' => __('Analyzing...', 'ai-seo-manager'),
                'generating' => __('Generating recommendations...', 'ai-seo-manager'),
                'saving' => __('Saving...', 'ai-seo-manager'),
                'success' => __('Success!', 'ai-seo-manager'),
                'error' => __('An error occurred', 'ai-seo-manager'),
            )
        ));
    }

    /**
     * Načítanie frontend assetov
     */
    public function enqueue_frontend_assets() {
        // Pre budúce použitie
    }
}

/**
 * Spustenie pluginu
 */
function ai_seo_manager() {
    return AI_SEO_Manager::get_instance();
}

// Inicializácia
ai_seo_manager();
