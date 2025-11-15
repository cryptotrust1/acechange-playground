<?php
/**
 * Technical SEO Analyzer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Technical_SEO {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Komplexná technická SEO analýza
     */
    public function analyze($post_id) {
        $results = array(
            'score' => 0,
            'issues' => array(),
            'checks' => array(
                'meta_tags' => $this->check_meta_tags($post_id),
                'headings' => $this->check_headings($post_id),
                'images' => $this->check_images($post_id),
                'links' => $this->check_links($post_id),
                'mobile' => $this->check_mobile_optimization($post_id),
                'speed' => $this->check_page_speed($post_id),
                'schema' => $this->check_schema_markup($post_id),
                'sitemap' => $this->check_sitemap_presence($post_id),
            ),
        );

        // Vypočítaj celkové skóre
        $results['score'] = $this->calculate_technical_score($results['checks']);

        // Zber všetky issues
        foreach ($results['checks'] as $check) {
            if (isset($check['issues'])) {
                $results['issues'] = array_merge($results['issues'], $check['issues']);
            }
        }

        return $results;
    }

    /**
     * Kontrola meta tagov
     */
    private function check_meta_tags($post_id) {
        $post = get_post($post_id);
        $issues = array();
        $score = 100;

        // Title tag
        $title = get_the_title($post_id);
        if (empty($title)) {
            $issues[] = array(
                'severity' => 'critical',
                'message' => 'Missing page title',
                'fix' => 'Add a descriptive title tag'
            );
            $score -= 30;
        } elseif (strlen($title) > 60) {
            $issues[] = array(
                'severity' => 'warning',
                'message' => 'Title tag too long (' . strlen($title) . ' characters)',
                'fix' => 'Keep title under 60 characters'
            );
            $score -= 10;
        }

        // Meta description
        $description = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        if (empty($description)) {
            $description = get_post_meta($post_id, '_aioseop_description', true);
        }

        if (empty($description)) {
            $issues[] = array(
                'severity' => 'high',
                'message' => 'Missing meta description',
                'fix' => 'Add a compelling meta description (150-160 characters)'
            );
            $score -= 20;
        } elseif (strlen($description) > 160) {
            $issues[] = array(
                'severity' => 'warning',
                'message' => 'Meta description too long (' . strlen($description) . ' characters)',
                'fix' => 'Keep meta description under 160 characters'
            );
            $score -= 5;
        }

        // Canonical URL
        $canonical = get_post_meta($post_id, '_yoast_wpseo_canonical', true);
        if (empty($canonical)) {
            $issues[] = array(
                'severity' => 'medium',
                'message' => 'No canonical URL set',
                'fix' => 'Add canonical URL to prevent duplicate content issues'
            );
            $score -= 10;
        }

        return array(
            'score' => max(0, $score),
            'issues' => $issues,
            'data' => array(
                'title_length' => strlen($title),
                'description_length' => strlen($description),
                'has_canonical' => !empty($canonical),
            )
        );
    }

    /**
     * Kontrola nadpisov (H1-H6)
     */
    private function check_headings($post_id) {
        $content = get_post_field('post_content', $post_id);
        $issues = array();
        $score = 100;

        // Parse headings
        $headings = array();
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $i => $level) {
                $headings[] = array(
                    'level' => $level,
                    'text' => wp_strip_all_tags($matches[2][$i]),
                );
            }
        }

        // Check H1
        $h1_count = count(array_filter($headings, function($h) { return $h['level'] == 1; }));

        if ($h1_count === 0) {
            $issues[] = array(
                'severity' => 'high',
                'message' => 'No H1 heading found',
                'fix' => 'Add exactly one H1 heading to your content'
            );
            $score -= 25;
        } elseif ($h1_count > 1) {
            $issues[] = array(
                'severity' => 'medium',
                'message' => 'Multiple H1 headings (' . $h1_count . ')',
                'fix' => 'Use only one H1 heading per page'
            );
            $score -= 15;
        }

        // Check heading hierarchy
        $prev_level = 0;
        foreach ($headings as $heading) {
            if ($heading['level'] - $prev_level > 1) {
                $issues[] = array(
                    'severity' => 'low',
                    'message' => 'Heading hierarchy skip detected',
                    'fix' => 'Use proper heading hierarchy (H1 → H2 → H3, etc.)'
                );
                $score -= 5;
                break;
            }
            $prev_level = $heading['level'];
        }

        return array(
            'score' => max(0, $score),
            'issues' => $issues,
            'data' => array(
                'total_headings' => count($headings),
                'h1_count' => $h1_count,
                'headings' => $headings,
            )
        );
    }

    /**
     * Kontrola obrázkov
     */
    private function check_images($post_id) {
        $content = get_post_field('post_content', $post_id);
        $issues = array();
        $score = 100;

        preg_match_all('/<img[^>]+>/i', $content, $images);
        $total_images = count($images[0]);
        $missing_alt = 0;

        foreach ($images[0] as $img) {
            if (!preg_match('/alt=["\'][^"\']*["\']/i', $img)) {
                $missing_alt++;
            }
        }

        if ($missing_alt > 0) {
            $issues[] = array(
                'severity' => 'high',
                'message' => $missing_alt . ' images missing ALT text',
                'fix' => 'Add descriptive ALT text to all images'
            );
            $score -= min(50, $missing_alt * 10);
        }

        return array(
            'score' => max(0, $score),
            'issues' => $issues,
            'data' => array(
                'total_images' => $total_images,
                'missing_alt' => $missing_alt,
            )
        );
    }

    /**
     * Kontrola linkov
     */
    private function check_links($post_id) {
        $content = get_post_field('post_content', $post_id);
        $issues = array();
        $score = 100;

        // Internal links
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\']/i', $content, $links);
        $site_url = get_site_url();

        $internal_links = 0;
        $external_links = 0;

        foreach ($links[1] as $url) {
            if (strpos($url, $site_url) !== false || strpos($url, '/') === 0) {
                $internal_links++;
            } else {
                $external_links++;
            }
        }

        if ($internal_links === 0) {
            $issues[] = array(
                'severity' => 'medium',
                'message' => 'No internal links found',
                'fix' => 'Add 2-5 relevant internal links'
            );
            $score -= 20;
        }

        return array(
            'score' => max(0, $score),
            'issues' => $issues,
            'data' => array(
                'internal_links' => $internal_links,
                'external_links' => $external_links,
            )
        );
    }

    /**
     * Kontrola mobile optimalizácie
     */
    private function check_mobile_optimization($post_id) {
        $issues = array();
        $score = 100;

        // Check responsive theme
        if (!current_theme_supports('responsive-embeds')) {
            $issues[] = array(
                'severity' => 'medium',
                'message' => 'Theme may not be fully mobile responsive',
                'fix' => 'Ensure theme is mobile-friendly'
            );
            $score -= 15;
        }

        return array(
            'score' => max(0, $score),
            'issues' => $issues,
            'data' => array(
                'responsive_theme' => current_theme_supports('responsive-embeds'),
            )
        );
    }

    /**
     * Kontrola rýchlosti stránky (základná)
     */
    private function check_page_speed($post_id) {
        $issues = array();
        $score = 100;

        // Basic checks
        $content = get_post_field('post_content', $post_id);
        $content_size = strlen($content);

        if ($content_size > 100000) { // 100KB
            $issues[] = array(
                'severity' => 'medium',
                'message' => 'Large content size (' . round($content_size / 1024, 2) . ' KB)',
                'fix' => 'Optimize content size and compress images'
            );
            $score -= 15;
        }

        return array(
            'score' => max(0, $score),
            'issues' => $issues,
            'data' => array(
                'content_size' => $content_size,
            )
        );
    }

    /**
     * Kontrola schema markup
     */
    private function check_schema_markup($post_id) {
        $content = get_post_field('post_content', $post_id);
        $issues = array();
        $score = 100;

        $has_schema = (strpos($content, 'schema.org') !== false) ||
                      (strpos($content, 'application/ld+json') !== false);

        if (!$has_schema) {
            $issues[] = array(
                'severity' => 'low',
                'message' => 'No structured data found',
                'fix' => 'Add Schema.org markup for better rich snippets'
            );
            $score -= 20;
        }

        return array(
            'score' => max(0, $score),
            'issues' => $issues,
            'data' => array(
                'has_schema' => $has_schema,
            )
        );
    }

    /**
     * Kontrola sitemap
     */
    private function check_sitemap_presence($post_id) {
        $issues = array();
        $score = 100;

        // Check if URL is in sitemap (basic check)
        $sitemap_url = get_sitemap_url('posts', 'post');

        if (empty($sitemap_url)) {
            $issues[] = array(
                'severity' => 'low',
                'message' => 'Sitemap may not be configured',
                'fix' => 'Ensure XML sitemap is generated and submitted to search engines'
            );
            $score -= 15;
        }

        return array(
            'score' => max(0, $score),
            'issues' => $issues,
            'data' => array(
                'has_sitemap' => !empty($sitemap_url),
            )
        );
    }

    /**
     * Vypočítaj celkové technické skóre
     */
    private function calculate_technical_score($checks) {
        $total = 0;
        $count = 0;

        foreach ($checks as $check) {
            if (isset($check['score'])) {
                $total += $check['score'];
                $count++;
            }
        }

        return $count > 0 ? round($total / $count) : 0;
    }
}
