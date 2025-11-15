<?php
/**
 * XML Sitemap generator.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/seo
 */

/**
 * Generates and serves XML sitemaps.
 */
class Claude_SEO_Sitemap {

    /**
     * Add sitemap rewrite rules.
     */
    public function add_sitemap_rewrite_rules() {
        add_rewrite_rule('^sitemap\.xml$', 'index.php?claude_sitemap=index', 'top');
        add_rewrite_rule('^sitemap-([^/]+)\.xml$', 'index.php?claude_sitemap=$matches[1]', 'top');

        add_filter('query_vars', array($this, 'add_query_vars'));
    }

    /**
     * Add custom query vars.
     *
     * @param array $vars Query vars.
     * @return array Modified vars.
     */
    public function add_query_vars($vars) {
        $vars[] = 'claude_sitemap';
        return $vars;
    }

    /**
     * Handle sitemap request.
     */
    public function handle_sitemap_request() {
        $sitemap = get_query_var('claude_sitemap');

        if (empty($sitemap)) {
            return;
        }

        // Check cache
        $cache_key = 'sitemap_' . $sitemap;
        $xml = get_transient($cache_key);

        if ($xml === false) {
            // Generate sitemap
            if ($sitemap === 'index') {
                $xml = $this->generate_sitemap_index();
            } else {
                $xml = $this->generate_sitemap($sitemap);
            }

            // Cache for 24 hours
            set_transient($cache_key, $xml, DAY_IN_SECONDS);
        }

        // Output XML
        header('Content-Type: application/xml; charset=utf-8');
        header('X-Robots-Tag: noindex, follow', true);
        echo $xml;
        exit;
    }

    /**
     * Generate sitemap index.
     *
     * @return string XML content.
     */
    private function generate_sitemap_index() {
        $settings = get_option('claude_seo_settings', array());
        $post_types = isset($settings['sitemap_post_types']) ? $settings['sitemap_post_types'] : array('post', 'page');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($post_types as $post_type) {
            $xml .= sprintf(
                "\t<sitemap>\n\t\t<loc>%s</loc>\n\t\t<lastmod>%s</lastmod>\n\t</sitemap>\n",
                esc_url(home_url("/sitemap-{$post_type}.xml")),
                gmdate('Y-m-d\TH:i:s+00:00')
            );
        }

        $xml .= '</sitemapindex>';

        return $xml;
    }

    /**
     * Generate sitemap for post type.
     *
     * @param string $post_type Post type.
     * @return string XML content.
     */
    private function generate_sitemap($post_type) {
        $settings = get_option('claude_seo_settings', array());
        $exclude_ids = isset($settings['sitemap_exclude_ids']) ? $settings['sitemap_exclude_ids'] : array();

        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 50000,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'post__not_in' => $exclude_ids,
            'orderby' => 'modified',
            'order' => 'DESC'
        );

        $posts = get_posts($args);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($posts as $post) {
            $xml .= $this->generate_url_entry($post);
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Generate URL entry for post.
     *
     * @param WP_Post $post Post object.
     * @return string XML entry.
     */
    private function generate_url_entry($post) {
        $permalink = get_permalink($post);
        $modified = get_post_modified_time('Y-m-d\TH:i:s+00:00', true, $post);

        // Determine priority based on post type
        $priority = $post->post_type === 'page' ? '0.8' : '0.6';

        // Determine change frequency
        $changefreq = $this->get_changefreq($post);

        $entry = "\t<url>\n";
        $entry .= "\t\t<loc>" . esc_url($permalink) . "</loc>\n";
        $entry .= "\t\t<lastmod>{$modified}</lastmod>\n";
        $entry .= "\t\t<changefreq>{$changefreq}</changefreq>\n";
        $entry .= "\t\t<priority>{$priority}</priority>\n";
        $entry .= "\t</url>\n";

        return $entry;
    }

    /**
     * Get change frequency for post.
     *
     * @param WP_Post $post Post object.
     * @return string Change frequency.
     */
    private function get_changefreq($post) {
        $now = time();
        $modified = get_post_modified_time('U', true, $post);
        $age_days = ($now - $modified) / DAY_IN_SECONDS;

        if ($age_days < 7) {
            return 'daily';
        } elseif ($age_days < 30) {
            return 'weekly';
        } else {
            return 'monthly';
        }
    }

    /**
     * Clear sitemap cache.
     */
    public static function clear_cache() {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_sitemap_%'
             OR option_name LIKE '_transient_timeout_sitemap_%'"
        );
    }

    /**
     * Ping search engines.
     */
    public static function ping_search_engines() {
        $sitemap_url = home_url('/sitemap.xml');

        // Ping Google
        wp_remote_get('https://www.google.com/ping?sitemap=' . urlencode($sitemap_url));

        // Ping Bing
        wp_remote_get('https://www.bing.com/ping?sitemap=' . urlencode($sitemap_url));
    }
}
