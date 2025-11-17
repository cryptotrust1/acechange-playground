<?php
/**
 * Social Media Admin Menu
 *
 * WordPress admin rozhranie pre Social Media Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Social_Admin_Menu {

    private static $instance = null;
    private $manager;
    private $db;
    private $content_generator;
    private $scheduler;
    private $analytics;

    /**
     * Singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = AI_SEO_Social_Database::get_instance();
        $this->manager = AI_SEO_Social_Media_Manager::get_instance();
        $this->content_generator = AI_SEO_Social_AI_Content_Generator::get_instance();
        $this->scheduler = AI_SEO_Social_Scheduler::get_instance();
        $this->analytics = AI_SEO_Social_Analytics::get_instance();

        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Add submenu under AI SEO Manager
        add_submenu_page(
            'ai-seo-manager',
            __('Social Media Manager', 'ai-seo-manager'),
            __('Social Media', 'ai-seo-manager'),
            'edit_posts',
            'ai-seo-social-media',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            'ai-seo-manager',
            __('Social Media Composer', 'ai-seo-manager'),
            __('↳ Composer', 'ai-seo-manager'),
            'edit_posts',
            'ai-seo-social-composer',
            array($this, 'render_composer_page')
        );

        add_submenu_page(
            'ai-seo-manager',
            __('Social Media Calendar', 'ai-seo-manager'),
            __('↳ Calendar', 'ai-seo-manager'),
            'edit_posts',
            'ai-seo-social-calendar',
            array($this, 'render_calendar_page')
        );

        add_submenu_page(
            'ai-seo-manager',
            __('Social Media Analytics', 'ai-seo-manager'),
            __('↳ Analytics', 'ai-seo-manager'),
            'edit_posts',
            'ai-seo-social-analytics',
            array($this, 'render_analytics_page')
        );

        add_submenu_page(
            'ai-seo-manager',
            __('Social Media Accounts', 'ai-seo-manager'),
            __('↳ Accounts', 'ai-seo-manager'),
            'manage_options',
            'ai-seo-social-accounts',
            array($this, 'render_accounts_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our pages
        if (strpos($hook, 'ai-seo-social') === false) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'ai-seo-social-admin',
            AI_SEO_MANAGER_PLUGIN_URL . 'assets/css/social-media-admin.css',
            array(),
            AI_SEO_MANAGER_VERSION
        );

        // Enqueue JS
        wp_enqueue_script(
            'ai-seo-social-admin',
            AI_SEO_MANAGER_PLUGIN_URL . 'assets/js/social-media-admin.js',
            array('jquery'),
            AI_SEO_MANAGER_VERSION,
            true
        );

        // Localize script
        wp_localize_script('ai-seo-social-admin', 'aiSeoSocial', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_seo_social_nonce'),
            'dashboardUrl' => admin_url('admin.php?page=ai-seo-social-media'),
            'composerUrl' => admin_url('admin.php?page=ai-seo-social-composer'),
            'calendarUrl' => admin_url('admin.php?page=ai-seo-social-calendar'),
            'analyticsUrl' => admin_url('admin.php?page=ai-seo-social-analytics'),
            'accountsUrl' => admin_url('admin.php?page=ai-seo-social-accounts'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this account?', 'ai-seo-manager'),
                'generating' => __('Generating content...', 'ai-seo-manager'),
                'publishing' => __('Publishing...', 'ai-seo-manager'),
                'scheduling' => __('Scheduling...', 'ai-seo-manager'),
            ),
        ));
    }

    /**
     * Render Dashboard Page
     */
    public function render_dashboard_page() {
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get statistics
        $manager_stats = $this->manager->get_stats();
        $queue_stats = $this->scheduler->get_queue_stats();
        $analytics_stats = $this->analytics->get_stats();

        // Get recent posts
        $recent_posts = $this->db->get_posts(array('limit' => 5));

        // Get upcoming scheduled posts
        $upcoming_posts = $this->scheduler->get_upcoming_posts(5);

        include AI_SEO_MANAGER_PLUGIN_DIR . 'admin/views/social-media/dashboard.php';
    }

    /**
     * Render Composer Page
     */
    public function render_composer_page() {
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get active accounts
        $accounts = $this->db->get_accounts(array('status' => 'active'));

        // Group accounts by platform
        $platforms = array();
        foreach ($accounts as $account) {
            if (!isset($platforms[$account->platform])) {
                $platforms[$account->platform] = array();
            }
            $platforms[$account->platform][] = $account;
        }

        include AI_SEO_MANAGER_PLUGIN_DIR . 'admin/views/social-media/composer.php';
    }

    /**
     * Render Calendar Page
     */
    public function render_calendar_page() {
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get scheduled posts for the month
        $month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('Y-m');

        include AI_SEO_MANAGER_PLUGIN_DIR . 'admin/views/social-media/calendar.php';
    }

    /**
     * Render Analytics Page
     */
    public function render_analytics_page() {
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Get date range from request
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $days = max(1, min(365, $days)); // Limit 1-365 days

        // Generate report
        $report = $this->analytics->generate_report($days);

        include AI_SEO_MANAGER_PLUGIN_DIR . 'admin/views/social-media/analytics.php';
    }

    /**
     * Render Accounts Page
     */
    public function render_accounts_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Handle form submissions
        if (isset($_POST['ai_seo_social_save_account'])) {
            check_admin_referer('ai_seo_social_account');
            $this->handle_save_account();
        }

        if (isset($_POST['ai_seo_social_delete_account'])) {
            check_admin_referer('ai_seo_social_account');
            $this->handle_delete_account();
        }

        // Get all accounts
        $accounts = $this->db->get_accounts();

        include AI_SEO_MANAGER_PLUGIN_DIR . 'admin/views/social-media/accounts.php';
    }

    /**
     * Handle save account form submission
     */
    private function handle_save_account() {
        $account_id = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
        $platform = sanitize_text_field($_POST['platform']);
        $account_name = sanitize_text_field($_POST['account_name']);
        $status = sanitize_text_field($_POST['status']);

        // Platform-specific credentials
        $credentials = array();

        switch ($platform) {
            case 'telegram':
                $credentials = array(
                    'bot_token' => sanitize_text_field($_POST['telegram_bot_token']),
                    'channel_id' => sanitize_text_field($_POST['telegram_channel_id']),
                );
                break;

            case 'facebook':
                $credentials = array(
                    'app_id' => sanitize_text_field($_POST['facebook_app_id']),
                    'app_secret' => sanitize_text_field($_POST['facebook_app_secret']),
                    'page_id' => sanitize_text_field($_POST['facebook_page_id']),
                    'page_access_token' => sanitize_text_field($_POST['facebook_access_token']),
                );
                break;

            case 'instagram':
                $credentials = array(
                    'user_id' => sanitize_text_field($_POST['instagram_user_id']),
                    'access_token' => sanitize_text_field($_POST['instagram_access_token']),
                );
                break;

            case 'twitter':
                $credentials = array(
                    'api_key' => sanitize_text_field($_POST['twitter_api_key']),
                    'api_secret' => sanitize_text_field($_POST['twitter_api_secret']),
                    'access_token' => sanitize_text_field($_POST['twitter_access_token']),
                    'access_token_secret' => sanitize_text_field($_POST['twitter_access_token_secret']),
                );
                break;

            case 'linkedin':
                $credentials = array(
                    'client_id' => sanitize_text_field($_POST['linkedin_client_id']),
                    'client_secret' => sanitize_text_field($_POST['linkedin_client_secret']),
                    'access_token' => sanitize_text_field($_POST['linkedin_access_token']),
                    'organization_id' => sanitize_text_field($_POST['linkedin_org_id']),
                );
                break;

            case 'youtube':
                $credentials = array(
                    'client_id' => sanitize_text_field($_POST['youtube_client_id']),
                    'client_secret' => sanitize_text_field($_POST['youtube_client_secret']),
                    'refresh_token' => sanitize_text_field($_POST['youtube_refresh_token']),
                    'channel_id' => sanitize_text_field($_POST['youtube_channel_id']),
                );
                break;

            case 'tiktok':
                $credentials = array(
                    'client_key' => sanitize_text_field($_POST['tiktok_client_key']),
                    'client_secret' => sanitize_text_field($_POST['tiktok_client_secret']),
                    'access_token' => sanitize_text_field($_POST['tiktok_access_token']),
                );
                break;
        }

        $data = array(
            'platform' => $platform,
            'account_name' => $account_name,
            'credentials' => $credentials,
            'status' => $status,
        );

        if ($account_id > 0) {
            // Update existing account
            $result = $this->db->update_account($account_id, $data);
        } else {
            // Create new account
            $data['account_id'] = sanitize_text_field($_POST['platform_account_id']);
            $result = $this->db->create_account($data);
        }

        if ($result) {
            add_settings_error(
                'ai_seo_social_messages',
                'account_saved',
                __('Account saved successfully!', 'ai-seo-manager'),
                'success'
            );
        } else {
            add_settings_error(
                'ai_seo_social_messages',
                'account_save_failed',
                __('Failed to save account.', 'ai-seo-manager'),
                'error'
            );
        }
    }

    /**
     * Handle delete account
     */
    private function handle_delete_account() {
        $account_id = (int)$_POST['account_id'];

        $result = $this->db->delete_account($account_id);

        if ($result) {
            add_settings_error(
                'ai_seo_social_messages',
                'account_deleted',
                __('Account deleted successfully!', 'ai-seo-manager'),
                'success'
            );
        } else {
            add_settings_error(
                'ai_seo_social_messages',
                'account_delete_failed',
                __('Failed to delete account.', 'ai-seo-manager'),
                'error'
            );
        }
    }
}
