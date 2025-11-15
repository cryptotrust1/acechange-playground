<?php
/**
 * Admin dashboard page.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/admin
 */

/**
 * Handles admin menu and pages.
 */
class Claude_SEO_Admin_Page {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Add admin menu.
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Claude SEO', 'claude-seo'),
            __('Claude SEO', 'claude-seo'),
            'manage_options',
            'claude-seo',
            array($this, 'display_dashboard'),
            'dashicons-chart-line',
            30
        );

        add_submenu_page(
            'claude-seo',
            __('Dashboard', 'claude-seo'),
            __('Dashboard', 'claude-seo'),
            'manage_options',
            'claude-seo',
            array($this, 'display_dashboard')
        );

        add_submenu_page(
            'claude-seo',
            __('Settings', 'claude-seo'),
            __('Settings', 'claude-seo'),
            'manage_options',
            'claude-seo-settings',
            array($this, 'display_settings')
        );
    }

    /**
     * Display dashboard page.
     */
    public function display_dashboard() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'claude-seo'));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Claude SEO Dashboard', 'claude-seo') . '</h1>';
        echo '<div id="claude-seo-dashboard-root"></div>';
        echo '</div>';
    }

    /**
     * Display settings page.
     */
    public function display_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'claude-seo'));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Claude SEO Settings', 'claude-seo') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('claude_seo_settings');
        do_settings_sections('claude-seo-settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'claude-seo') === false && $hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name,
            CLAUDE_SEO_PLUGIN_URL . 'admin/css/admin-style.css',
            array(),
            $this->version
        );

        wp_enqueue_script(
            $this->plugin_name,
            CLAUDE_SEO_PLUGIN_URL . 'admin/js/build/index.js',
            array('wp-element', 'wp-components', 'wp-i18n'),
            $this->version,
            true
        );

        wp_localize_script(
            $this->plugin_name,
            'claudeSeoData',
            array(
                'restUrl' => rest_url('claude-seo/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'ajaxUrl' => admin_url('admin-ajax.php')
            )
        );
    }

    /**
     * AJAX: Analyze content.
     */
    public function ajax_analyze_content() {
        check_ajax_referer('claude_seo_analyze', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'claude-seo')));
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

        if ($post_id === 0) {
            wp_send_json_error(array('message' => __('Invalid post ID', 'claude-seo')));
        }

        $analysis = Claude_SEO_Analyzer::analyze_post($post_id);

        wp_send_json_success($analysis);
    }

    /**
     * AJAX: Generate content with AI.
     */
    public function ajax_generate_content() {
        check_ajax_referer('claude_seo_generate', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'claude-seo')));
        }

        $template = isset($_POST['template']) ? sanitize_key($_POST['template']) : '';
        $args = isset($_POST['args']) ? $_POST['args'] : array();

        if (empty($template)) {
            wp_send_json_error(array('message' => __('Template required', 'claude-seo')));
        }

        $api_client = new Claude_SEO_API_Client();
        $result = $api_client->generate_with_template($template, $args);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('content' => $result));
    }

    /**
     * AJAX: Get internal link suggestions.
     */
    public function ajax_suggest_links() {
        check_ajax_referer('claude_seo_links', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'claude-seo')));
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

        if ($post_id === 0) {
            wp_send_json_error(array('message' => __('Invalid post ID', 'claude-seo')));
        }

        $suggestions = Claude_SEO_Internal_Linking::get_suggestions($post_id);

        wp_send_json_success(array('suggestions' => $suggestions));
    }
}
