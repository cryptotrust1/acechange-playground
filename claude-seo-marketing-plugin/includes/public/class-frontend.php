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
