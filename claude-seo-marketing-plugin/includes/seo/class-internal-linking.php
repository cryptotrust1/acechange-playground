<?php
/**
 * Internal linking AI system.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/seo
 */

/**
 * Suggests and manages internal links using AI.
 */
class Claude_SEO_Internal_Linking {

    /**
     * Get internal link suggestions for post.
     *
     * @param int|WP_Post $post Post ID or object.
     * @return array Link suggestions.
     */
    public static function get_suggestions($post) {
        $post = get_post($post);

        if (!$post) {
            return array();
        }

        // Get available posts for linking
        $available_posts = self::get_linkable_posts($post->ID);

        if (empty($available_posts)) {
            return array();
        }

        // Use AI to suggest links
        $api_client = new Claude_SEO_API_Client();

        $result = $api_client->generate_with_template('internal_links', array(
            'content' => $post->post_content,
            'posts' => $available_posts
        ));

        if (is_wp_error($result)) {
            Claude_SEO_Logger::error('Failed to get internal link suggestions', array(
                'error' => $result->get_error_message()
            ));
            return array();
        }

        // Parse JSON response
        $suggestions = json_decode($result, true);

        if (!is_array($suggestions)) {
            return array();
        }

        return $suggestions;
    }

    /**
     * Get posts available for internal linking.
     *
     * @param int $exclude_id Post ID to exclude.
     * @return array Available posts.
     */
    private static function get_linkable_posts($exclude_id) {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'post__not_in' => array($exclude_id),
            'orderby' => 'date',
            'order' => 'DESC'
        );

        $posts = get_posts($args);
        $linkable = array();

        foreach ($posts as $post) {
            $linkable[] = array(
                'id' => $post->ID,
                'title' => get_the_title($post),
                'url' => get_permalink($post),
                'excerpt' => get_the_excerpt($post)
            );
        }

        return $linkable;
    }

    /**
     * Track internal link in database.
     *
     * @param int    $source_id   Source post ID.
     * @param int    $target_id   Target post ID.
     * @param string $anchor_text Anchor text.
     * @param string $url         Link URL.
     */
    public static function track_link($source_id, $target_id, $anchor_text, $url) {
        Claude_SEO_Database::insert('internal_links', array(
            'source_post_id' => $source_id,
            'target_post_id' => $target_id,
            'anchor_text' => $anchor_text,
            'link_url' => $url,
            'created_at' => current_time('mysql', 1)
        ));
    }

    /**
     * Find orphaned pages (no internal links pointing to them).
     *
     * @return array Orphaned post IDs.
     */
    public static function find_orphaned_pages() {
        global $wpdb;
        $links_table = Claude_SEO_Database::get_table_name('internal_links');

        $orphaned = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_status = 'publish'
             AND post_type IN ('post', 'page')
             AND ID NOT IN (SELECT DISTINCT target_post_id FROM {$links_table})
             ORDER BY post_date DESC"
        );

        return $orphaned;
    }
}
