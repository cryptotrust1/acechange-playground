<?php
/**
 * SEO Meta Tags trieda
 * Spracováva generovanie a výstup meta tagov, Open Graph a Twitter Cards
 */

if (!defined('ABSPATH')) {
    exit;
}

class AceChange_SEO_Meta {

    /**
     * Výstup základných meta tagov
     */
    public static function output_meta_tags() {
        $settings = get_option('acechange_seo_settings', array());

        // Meta description
        $description = self::get_meta_description();
        if ($description) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }

        // Robots meta tag
        $robots = self::get_robots_meta();
        if ($robots) {
            echo '<meta name="robots" content="' . esc_attr($robots) . '">' . "\n";
        }

        // Viewport (dôležité pre mobile SEO)
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
    }

    /**
     * Získanie meta description
     */
    private static function get_meta_description() {
        $settings = get_option('acechange_seo_settings', array());
        $max_length = isset($settings['meta_description_length']) ? (int)$settings['meta_description_length'] : 160;

        if (is_singular()) {
            global $post;

            // Vlastná meta description (ak používateľ nastaví)
            $custom_desc = get_post_meta($post->ID, '_acechange_meta_description', true);
            if ($custom_desc) {
                return self::truncate_description($custom_desc, $max_length);
            }

            // Excerpt
            if ($post->post_excerpt) {
                return self::truncate_description($post->post_excerpt, $max_length);
            }

            // Prvých X slov z obsahu
            $content = wp_strip_all_tags($post->post_content);
            return self::truncate_description($content, $max_length);
        }

        if (is_category() || is_tag() || is_tax()) {
            $term_desc = term_description();
            if ($term_desc) {
                return self::truncate_description($term_desc, $max_length);
            }
        }

        // Fallback na site description
        return get_bloginfo('description');
    }

    /**
     * Skrátenie description na správnu dĺžku
     */
    private static function truncate_description($text, $max_length = 160) {
        $text = wp_strip_all_tags($text);
        $text = preg_replace('/\s+/', ' ', $text); // Odstránenie viacerých medzier
        $text = trim($text);

        if (strlen($text) <= $max_length) {
            return $text;
        }

        // Skrátenie na slovo
        $text = substr($text, 0, $max_length);
        $last_space = strrpos($text, ' ');
        if ($last_space !== false) {
            $text = substr($text, 0, $last_space);
        }

        return $text . '...';
    }

    /**
     * Získanie robots meta tagu
     */
    private static function get_robots_meta() {
        $settings = get_option('acechange_seo_settings', array());
        $robots = array();

        // Search stránky
        if (is_search() && !empty($settings['noindex_search'])) {
            $robots[] = 'noindex';
            $robots[] = 'follow';
        }

        // Archívne stránky
        if ((is_archive() || is_category() || is_tag()) && !empty($settings['noindex_archives'])) {
            $robots[] = 'noindex';
            $robots[] = 'follow';
        }

        // Vlastné nastavenie pre jednotlivé príspevky
        if (is_singular()) {
            global $post;
            $custom_robots = get_post_meta($post->ID, '_acechange_robots', true);
            if ($custom_robots) {
                return $custom_robots;
            }
        }

        return !empty($robots) ? implode(', ', $robots) : '';
    }

    /**
     * Výstup Open Graph tagov
     */
    public static function output_open_graph_tags() {
        echo '<meta property="og:locale" content="' . esc_attr(get_locale()) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";

        if (is_singular()) {
            global $post;

            echo '<meta property="og:type" content="article">' . "\n";
            echo '<meta property="og:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
            echo '<meta property="og:description" content="' . esc_attr(self::get_meta_description()) . '">' . "\n";
            echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">' . "\n";

            // Obrázok
            $image = self::get_post_image($post->ID);
            if ($image) {
                echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
                echo '<meta property="og:image:width" content="1200">' . "\n";
                echo '<meta property="og:image:height" content="630">' . "\n";
            }

            // Dátumy
            echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c')) . '">' . "\n";
            echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c')) . '">' . "\n";

        } else {
            echo '<meta property="og:type" content="website">' . "\n";
            echo '<meta property="og:title" content="' . esc_attr(wp_get_document_title()) . '">' . "\n";
            echo '<meta property="og:url" content="' . esc_url(home_url($_SERVER['REQUEST_URI'])) . '">' . "\n";
        }
    }

    /**
     * Výstup Twitter Card tagov
     */
    public static function output_twitter_card_tags() {
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";

        if (is_singular()) {
            global $post;

            echo '<meta name="twitter:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
            echo '<meta name="twitter:description" content="' . esc_attr(self::get_meta_description()) . '">' . "\n";

            $image = self::get_post_image($post->ID);
            if ($image) {
                echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
            }
        }
    }

    /**
     * Výstup Canonical URL
     */
    public static function output_canonical_url() {
        $canonical = '';

        if (is_singular()) {
            $canonical = get_permalink();
        } elseif (is_category() || is_tag() || is_tax()) {
            $canonical = get_term_link(get_queried_object());
        } elseif (is_front_page()) {
            $canonical = home_url('/');
        }

        if ($canonical) {
            echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
        }
    }

    /**
     * Získanie obrázku príspevku
     */
    private static function get_post_image($post_id) {
        // Featured image
        if (has_post_thumbnail($post_id)) {
            $image = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'full');
            if ($image) {
                return $image[0];
            }
        }

        // Prvý obrázok v obsahu
        $post = get_post($post_id);
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $post->post_content, $matches)) {
            return $matches[1];
        }

        // Fallback na default obrázok
        $settings = get_option('acechange_seo_settings', array());
        if (!empty($settings['social_share_image'])) {
            return $settings['social_share_image'];
        }

        return '';
    }
}
