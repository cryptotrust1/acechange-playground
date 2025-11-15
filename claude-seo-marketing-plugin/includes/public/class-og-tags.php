<?php
/**
 * Open Graph tags output.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/public
 */

/**
 * Outputs Open Graph and Twitter Card meta tags.
 */
class Claude_SEO_OG_Tags {

    /**
     * Output OG tags.
     */
    public function output_og_tags() {
        $settings = get_option('claude_seo_settings', array());

        if (empty($settings['og_enabled'])) {
            return;
        }

        if (is_singular()) {
            $this->output_post_og_tags();
        } elseif (is_front_page()) {
            $this->output_homepage_og_tags();
        }
    }

    /**
     * Output post OG tags.
     */
    private function output_post_og_tags() {
        $post = get_queried_object();

        $og_title = get_post_meta($post->ID, '_seo_og_title', true);
        $og_description = get_post_meta($post->ID, '_seo_og_description', true);
        $og_image_id = get_post_meta($post->ID, '_seo_og_image', true);

        if (empty($og_title)) {
            $og_title = get_the_title($post);
        }

        if (empty($og_description)) {
            $og_description = get_the_excerpt($post);
        }

        if (empty($og_image_id) && has_post_thumbnail($post)) {
            $og_image_id = get_post_thumbnail_id($post);
        }

        // Output OG tags
        echo '<meta property="og:type" content="article">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($og_title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($og_description) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_permalink($post)) . '">' . "\n";

        if ($og_image_id) {
            $image = wp_get_attachment_image_src($og_image_id, 'large');
            if ($image) {
                echo '<meta property="og:image" content="' . esc_url($image[0]) . '">' . "\n";
            }
        }

        // Twitter Card
        $this->output_twitter_card($og_title, $og_description, $og_image_id);
    }

    /**
     * Output homepage OG tags.
     */
    private function output_homepage_og_tags() {
        $settings = get_option('claude_seo_settings', array());

        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr(get_bloginfo('description')) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(home_url('/')) . '">' . "\n";

        if (!empty($settings['og_default_image'])) {
            echo '<meta property="og:image" content="' . esc_url($settings['og_default_image']) . '">' . "\n";
        }

        $this->output_twitter_card(get_bloginfo('name'), get_bloginfo('description'), null);
    }

    /**
     * Output Twitter Card tags.
     */
    private function output_twitter_card($title, $description, $image_id) {
        $settings = get_option('claude_seo_settings', array());

        if (empty($settings['twitter_enabled'])) {
            return;
        }

        $card_type = isset($settings['twitter_card_type']) ? $settings['twitter_card_type'] : 'summary_large_image';

        echo '<meta name="twitter:card" content="' . esc_attr($card_type) . '">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";

        if (!empty($settings['twitter_site'])) {
            echo '<meta name="twitter:site" content="' . esc_attr($settings['twitter_site']) . '">' . "\n";
        }

        if ($image_id) {
            $image = wp_get_attachment_image_src($image_id, 'large');
            if ($image) {
                echo '<meta name="twitter:image" content="' . esc_url($image[0]) . '">' . "\n";
            }
        }
    }
}
