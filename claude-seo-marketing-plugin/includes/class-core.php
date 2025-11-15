<?php
/**
 * The core plugin class.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 */
class Claude_SEO_Core {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @var Claude_SEO_Loader
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @var string
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @var string
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->version = CLAUDE_SEO_VERSION;
        $this->plugin_name = 'claude-seo';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_api_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // Core classes
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/class-loader.php';

        // Utility classes
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/utilities/class-sanitizer.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/utilities/class-cache.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/utilities/class-database.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/utilities/class-logger.php';

        // Claude API classes
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/claude/class-encryption.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/claude/class-rate-limiter.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/claude/class-cache-manager.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/claude/class-api-client.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/claude/class-prompt-templates.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/claude/class-cost-tracker.php';

        // SEO classes
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/seo/class-analyzer.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/seo/class-readability.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/seo/class-keyword-optimizer.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/seo/class-schema-generator.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/seo/class-sitemap.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/seo/class-robots-txt.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/seo/class-internal-linking.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/seo/class-404-monitor.php';

        // Admin classes
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/admin/class-admin-page.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/admin/class-settings.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/admin/class-meta-boxes.php';

        // Public classes
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/public/class-frontend.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/public/class-schema-output.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/public/class-og-tags.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/public/class-redirects.php';

        // API classes
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/api/class-rest-controller.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/api/class-analysis-endpoint.php';
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/api/class-claude-endpoint.php';

        $this->loader = new Claude_SEO_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     */
    private function set_locale() {
        $this->loader->add_action('plugins_loaded', $this, 'load_plugin_textdomain');
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'claude-seo',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }

    /**
     * Register all admin-related hooks.
     */
    private function define_admin_hooks() {
        $admin_page = new Claude_SEO_Admin_Page($this->get_plugin_name(), $this->get_version());
        $settings = new Claude_SEO_Settings();
        $meta_boxes = new Claude_SEO_Meta_Boxes();

        // Admin menu and pages
        $this->loader->add_action('admin_menu', $admin_page, 'add_admin_menu');
        $this->loader->add_action('admin_enqueue_scripts', $admin_page, 'enqueue_admin_assets');

        // Settings
        $this->loader->add_action('admin_init', $settings, 'register_settings');

        // Meta boxes
        $this->loader->add_action('add_meta_boxes', $meta_boxes, 'add_meta_boxes');
        $this->loader->add_action('save_post', $meta_boxes, 'save_meta_boxes', 10, 2);

        // AJAX handlers
        $this->loader->add_action('wp_ajax_claude_seo_analyze_content', $admin_page, 'ajax_analyze_content');
        $this->loader->add_action('wp_ajax_claude_seo_generate_content', $admin_page, 'ajax_generate_content');
        $this->loader->add_action('wp_ajax_claude_seo_suggest_links', $admin_page, 'ajax_suggest_links');
    }

    /**
     * Register all public-facing hooks.
     */
    private function define_public_hooks() {
        $frontend = new Claude_SEO_Frontend();
        $schema_output = new Claude_SEO_Schema_Output();
        $og_tags = new Claude_SEO_OG_Tags();
        $redirects = new Claude_SEO_Redirects();
        $monitor_404 = new Claude_SEO_404_Monitor();
        $sitemap = new Claude_SEO_Sitemap();

        // Frontend head tags
        $this->loader->add_action('wp_head', $frontend, 'output_meta_tags', 1);
        $this->loader->add_action('wp_head', $og_tags, 'output_og_tags', 5);
        $this->loader->add_action('wp_head', $schema_output, 'output_schema_markup', 10);

        // Redirects
        $this->loader->add_action('template_redirect', $redirects, 'handle_redirects', 1);

        // 404 monitoring
        $this->loader->add_action('template_redirect', $monitor_404, 'log_404_errors');

        // Sitemap
        $this->loader->add_action('init', $sitemap, 'add_sitemap_rewrite_rules');
        $this->loader->add_action('template_redirect', $sitemap, 'handle_sitemap_request');

        // Robots.txt
        $this->loader->add_filter('robots_txt', $this, 'modify_robots_txt', 10, 2);
    }

    /**
     * Register all REST API hooks.
     */
    private function define_api_hooks() {
        $analysis_endpoint = new Claude_SEO_Analysis_Endpoint();
        $claude_endpoint = new Claude_SEO_Claude_Endpoint();

        $this->loader->add_action('rest_api_init', $analysis_endpoint, 'register_routes');
        $this->loader->add_action('rest_api_init', $claude_endpoint, 'register_routes');
    }

    /**
     * Modify robots.txt output.
     */
    public function modify_robots_txt($output, $public) {
        require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/seo/class-robots-txt.php';
        $robots_txt = new Claude_SEO_Robots_Txt();
        return $robots_txt->get_robots_txt($output, $public);
    }

    /**
     * Run the loader to execute all hooks.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin.
     *
     * @return string
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks.
     *
     * @return Claude_SEO_Loader
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return string
     */
    public function get_version() {
        return $this->version;
    }
}
