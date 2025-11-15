<?php
/**
 * SEO XML Sitemap trieda
 * Automaticky generuje XML sitemap pre vyhľadávače
 */

if (!defined('ABSPATH')) {
    exit;
}

class AceChange_SEO_Sitemap {

    public function __construct() {
        add_action('init', array($this, 'add_sitemap_rewrite'));
        add_action('template_redirect', array($this, 'handle_sitemap_request'));
    }

    /**
     * Pridanie rewrite rule pre sitemap
     */
    public function add_sitemap_rewrite() {
        add_rewrite_rule('^sitemap\.xml$', 'index.php?acechange_sitemap=1', 'top');
        add_rewrite_tag('%acechange_sitemap%', '([^&]+)');
    }

    /**
     * Spracovanie požiadavky na sitemap
     */
    public function handle_sitemap_request() {
        if (get_query_var('acechange_sitemap')) {
            $this->output_sitemap();
            exit;
        }
    }

    /**
     * Výstup XML sitemap
     */
    private function output_sitemap() {
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Hlavná stránka
        $this->add_url_to_sitemap(home_url('/'), get_lastpostmodified('gmt'), '1.0', 'daily');

        // Stránky
        $pages = get_pages();
        foreach ($pages as $page) {
            $this->add_url_to_sitemap(
                get_permalink($page->ID),
                $page->post_modified_gmt,
                '0.8',
                'weekly'
            );
        }

        // Príspevky
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ));

        foreach ($posts as $post) {
            $this->add_url_to_sitemap(
                get_permalink($post->ID),
                $post->post_modified_gmt,
                '0.6',
                'monthly'
            );
        }

        // Kategórie
        $categories = get_categories(array('hide_empty' => true));
        foreach ($categories as $category) {
            $this->add_url_to_sitemap(
                get_category_link($category->term_id),
                null,
                '0.4',
                'weekly'
            );
        }

        echo '</urlset>';
    }

    /**
     * Pridanie URL do sitemap
     */
    private function add_url_to_sitemap($url, $modified = null, $priority = '0.5', $changefreq = 'monthly') {
        echo '  <url>' . "\n";
        echo '    <loc>' . esc_url($url) . '</loc>' . "\n";

        if ($modified) {
            echo '    <lastmod>' . mysql2date('Y-m-d\TH:i:s+00:00', $modified, false) . '</lastmod>' . "\n";
        }

        echo '    <changefreq>' . esc_html($changefreq) . '</changefreq>' . "\n";
        echo '    <priority>' . esc_html($priority) . '</priority>' . "\n";
        echo '  </url>' . "\n";
    }
}
