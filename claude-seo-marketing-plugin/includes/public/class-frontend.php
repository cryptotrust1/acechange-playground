<?php
/**
 * Frontend functionality.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/public
 */

/**
 * Handles frontend SEO output.
 */
class Claude_SEO_Frontend {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_cwv_monitoring'));
    }

    /**
     * Enqueue Core Web Vitals monitoring script.
     */
    public function enqueue_cwv_monitoring() {
        $settings = get_option('claude_seo_settings', array());

        // Only enqueue if CWV monitoring is enabled
        if (empty($settings['cwv_monitoring_enabled'])) {
            return;
        }

        // Enqueue web-vitals library from CDN
        wp_enqueue_script(
            'web-vitals',
            'https://unpkg.com/web-vitals@3/dist/web-vitals.iife.js',
            array(),
            '3.0.0',
            true
        );

        // Enqueue our monitoring script
        wp_enqueue_script(
            'claude-seo-cwv-monitor',
            CLAUDE_SEO_PLUGIN_URL . 'public/js/cwv-monitor.js',
            array('web-vitals'),
            CLAUDE_SEO_VERSION,
            true
        );

        // Pass configuration to JavaScript
        wp_localize_script(
            'claude-seo-cwv-monitor',
            'claudeSeoConfig',
            array(
                'endpoint' => rest_url('claude-seo/v1/cwv'),
                'pageId' => get_queried_object_id(),
                'siteId' => get_current_blog_id(),
                'cwvMonitoring' => true
            )
        );
    }

    /**
     * Output meta tags.
     */
    public function output_meta_tags() {
        if (is_singular()) {
            $post = get_queried_object();
            $this->output_post_meta_tags($post);
        } elseif (is_front_page()) {
            $this->output_homepage_meta_tags();
        }
    }

    /**
     * Output post meta tags.
     */
    private function output_post_meta_tags($post) {
        $title = get_post_meta($post->ID, '_seo_title', true);
        $description = get_post_meta($post->ID, '_seo_description', true);
        $robots = get_post_meta($post->ID, '_seo_robots', true);
        $canonical = get_post_meta($post->ID, '_seo_canonical', true);

        // Title
        if (!empty($title)) {
            echo '<title>' . esc_html($title) . '</title>' . "\n";
        }

        // Description
        if (!empty($description)) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }

        // Robots
        if (!empty($robots)) {
            echo '<meta name="robots" content="' . esc_attr($robots) . '">' . "\n";
        }

        // Canonical
        if (!empty($canonical)) {
            echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
        } else {
            echo '<link rel="canonical" href="' . esc_url(get_permalink($post)) . '">' . "\n";
        }
    }

    /**
     * Output homepage meta tags.
     */
    private function output_homepage_meta_tags() {
        $settings = get_option('claude_seo_settings', array());

        $title = isset($settings['homepage_title']) ? $settings['homepage_title'] : '';
        $description = isset($settings['homepage_description']) ? $settings['homepage_description'] : '';

        if (!empty($title)) {
            echo '<title>' . esc_html($title) . '</title>' . "\n";
        }

        if (!empty($description)) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }

        echo '<link rel="canonical" href="' . esc_url(home_url('/')) . '">' . "\n";
    }
}
