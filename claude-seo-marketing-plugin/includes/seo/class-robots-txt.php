<?php
/**
 * Robots.txt manager.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/seo
 */

/**
 * Manages robots.txt output.
 */
class Claude_SEO_Robots_Txt {

    /**
     * Get robots.txt content.
     *
     * @param string $output  Default output.
     * @param string $public  Blog public status.
     * @return string Modified robots.txt.
     */
    public function get_robots_txt($output, $public) {
        if ($public === '0') {
            return $output; // Site is not public, use default
        }

        $settings = get_option('claude_seo_settings', array());
        $custom_rules = isset($settings['robots_txt_custom']) ? $settings['robots_txt_custom'] : '';

        // Start with default rules
        $robots = "User-agent: *\n";
        $robots .= "Disallow: /wp-admin/\n";
        $robots .= "Allow: /wp-admin/admin-ajax.php\n";
        $robots .= "Disallow: /wp-includes/\n";
        $robots .= "Disallow: /wp-content/plugins/\n";
        $robots .= "Disallow: /wp-content/cache/\n";
        $robots .= "Disallow: /wp-content/themes/\n";
        $robots .= "Disallow: /trackback/\n";
        $robots .= "Disallow: /feed/\n";
        $robots .= "Disallow: /comments/\n";
        $robots .= "Disallow: */trackback/\n";
        $robots .= "Disallow: */feed/\n";
        $robots .= "Disallow: */comments/\n";
        $robots .= "Disallow: /*?*\n";
        $robots .= "Disallow: /*?\n";

        // Add custom rules
        if (!empty($custom_rules)) {
            $robots .= "\n" . $custom_rules . "\n";
        }

        // Add sitemap
        $robots .= "\nSitemap: " . home_url('/sitemap.xml') . "\n";

        return $robots;
    }
}
