<?php
/**
 * SEO content analyzer.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/seo
 */

/**
 * Analyzes content for SEO and provides scoring.
 */
class Claude_SEO_Analyzer {

    /**
     * Analyze post content.
     *
     * @param int|WP_Post $post Post ID or object.
     * @return array Analysis results.
     */
    public static function analyze_post($post) {
        $post = get_post($post);

        if (!$post) {
            return self::get_empty_analysis();
        }

        $content = $post->post_content;
        $title = $post->post_title;
        $focus_keyword = get_post_meta($post->ID, '_seo_keywords', true);

        // Perform analysis
        $analysis = array(
            'post_id' => $post->ID,
            'word_count' => self::count_words($content),
            'keyword_analysis' => Claude_SEO_Keyword_Optimizer::analyze_keyword_usage($content, $title, $focus_keyword),
            'readability' => Claude_SEO_Readability::analyze($content),
            'structure' => self::analyze_structure($content),
            'links' => self::analyze_links($content, $post->ID),
            'images' => self::analyze_images($content),
            'meta' => self::analyze_meta($post),
        );

        // Calculate overall SEO score
        $analysis['seo_score'] = self::calculate_seo_score($analysis);
        $analysis['issues'] = self::identify_issues($analysis);
        $analysis['recommendations'] = self::generate_recommendations($analysis);

        return $analysis;
    }

    /**
     * Count words in content.
     *
     * @param string $content Content.
     * @return int Word count.
     */
    private static function count_words($content) {
        $text = wp_strip_all_tags($content);
        return str_word_count($text);
    }

    /**
     * Analyze content structure.
     *
     * @param string $content Content.
     * @return array Structure analysis.
     */
    private static function analyze_structure($content) {
        // Extract headings
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $content, $matches);

        $headings = array();
        $h1_count = 0;

        foreach ($matches[1] as $index => $level) {
            $headings[] = array(
                'level' => 'H' . $level,
                'text' => wp_strip_all_tags($matches[2][$index])
            );

            if ($level == '1') {
                $h1_count++;
            }
        }

        // Count paragraphs
        preg_match_all('/<p[^>]*>.*?<\/p>/is', $content, $p_matches);
        $paragraph_count = count($p_matches[0]);

        // Average paragraph length
        $avg_paragraph_length = 0;
        if ($paragraph_count > 0) {
            $total_p_words = 0;
            foreach ($p_matches[0] as $paragraph) {
                $total_p_words += str_word_count(wp_strip_all_tags($paragraph));
            }
            $avg_paragraph_length = $total_p_words / $paragraph_count;
        }

        return array(
            'headings' => $headings,
            'heading_count' => count($headings),
            'h1_count' => $h1_count,
            'paragraph_count' => $paragraph_count,
            'avg_paragraph_length' => round($avg_paragraph_length, 1),
            'has_proper_hierarchy' => self::check_heading_hierarchy($headings)
        );
    }

    /**
     * Check if heading hierarchy is proper.
     *
     * @param array $headings Headings array.
     * @return bool True if proper.
     */
    private static function check_heading_hierarchy($headings) {
        if (empty($headings)) {
            return false;
        }

        $previous_level = 0;

        foreach ($headings as $heading) {
            $level = (int) str_replace('H', '', $heading['level']);

            // H1 should be first (if present)
            if ($level === 1 && $previous_level > 0) {
                return false;
            }

            // Don't skip levels
            if ($level > $previous_level + 1 && $previous_level > 0) {
                return false;
            }

            $previous_level = $level;
        }

        return true;
    }

    /**
     * Analyze links in content.
     *
     * @param string $content Content.
     * @param int    $post_id Post ID.
     * @return array Link analysis.
     */
    private static function analyze_links($content, $post_id) {
        preg_match_all('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/i', $content, $matches);

        $internal_links = array();
        $external_links = array();
        $home_url = home_url();

        foreach ($matches[1] as $index => $url) {
            $anchor_text = wp_strip_all_tags($matches[2][$index]);

            if (strpos($url, $home_url) === 0 || strpos($url, '/') === 0) {
                $internal_links[] = array(
                    'url' => $url,
                    'anchor' => $anchor_text
                );
            } else {
                $external_links[] = array(
                    'url' => $url,
                    'anchor' => $anchor_text
                );
            }
        }

        return array(
            'internal_count' => count($internal_links),
            'external_count' => count($external_links),
            'total_count' => count($matches[0]),
            'internal_links' => $internal_links,
            'external_links' => $external_links
        );
    }

    /**
     * Analyze images in content.
     *
     * @param string $content Content.
     * @return array Image analysis.
     */
    private static function analyze_images($content) {
        preg_match_all('/<img[^>]+>/i', $content, $matches);

        $total_images = count($matches[0]);
        $images_with_alt = 0;
        $images_without_alt = 0;

        foreach ($matches[0] as $img_tag) {
            if (preg_match('/alt=["\']([^"\']*)["\']/', $img_tag, $alt_match)) {
                if (!empty(trim($alt_match[1]))) {
                    $images_with_alt++;
                } else {
                    $images_without_alt++;
                }
            } else {
                $images_without_alt++;
            }
        }

        return array(
            'total' => $total_images,
            'with_alt' => $images_with_alt,
            'without_alt' => $images_without_alt,
            'alt_percentage' => $total_images > 0 ? round(($images_with_alt / $total_images) * 100, 1) : 0
        );
    }

    /**
     * Analyze meta data.
     *
     * @param WP_Post $post Post object.
     * @return array Meta analysis.
     */
    private static function analyze_meta($post) {
        $meta_title = get_post_meta($post->ID, '_seo_title', true);
        $meta_description = get_post_meta($post->ID, '_seo_description', true);

        if (empty($meta_title)) {
            $meta_title = $post->post_title;
        }

        $title_length = mb_strlen($meta_title);
        $desc_length = mb_strlen($meta_description);

        return array(
            'title' => $meta_title,
            'title_length' => $title_length,
            'title_optimal' => ($title_length >= 50 && $title_length <= 60),
            'description' => $meta_description,
            'description_length' => $desc_length,
            'description_optimal' => ($desc_length >= 150 && $desc_length <= 160),
        );
    }

    /**
     * Calculate overall SEO score (0-100).
     *
     * @param array $analysis Analysis data.
     * @return int SEO score.
     */
    private static function calculate_seo_score($analysis) {
        $score = 0;
        $settings = get_option('claude_seo_settings', array());

        // Keyword in title (10 points)
        if ($analysis['keyword_analysis']['in_title']) {
            $score += 10;
        }

        // Keyword in first 100 words (8 points)
        if ($analysis['keyword_analysis']['in_first_paragraph']) {
            $score += 8;
        }

        // Keyword density optimal (10 points)
        $density = $analysis['keyword_analysis']['density'];
        if ($density >= 0.5 && $density <= 2.5) {
            $score += 10;
        }

        // Meta description (8 points)
        if ($analysis['meta']['description_optimal']) {
            $score += 8;
        }

        // Title length optimal (7 points)
        if ($analysis['meta']['title_optimal']) {
            $score += 7;
        }

        // Content length (10 points)
        $min_length = isset($settings['min_content_length']) ? $settings['min_content_length'] : 300;
        if ($analysis['word_count'] >= $min_length) {
            $score += 10;
        }

        // Readability (12 points)
        $readability_score = $analysis['readability']['flesch_reading_ease'];
        if ($readability_score >= 60) {
            $score += 12;
        } elseif ($readability_score >= 50) {
            $score += 8;
        } elseif ($readability_score >= 30) {
            $score += 4;
        }

        // Heading hierarchy (8 points)
        if ($analysis['structure']['has_proper_hierarchy'] && $analysis['structure']['heading_count'] >= 3) {
            $score += 8;
        }

        // Internal links (7 points)
        $min_internal = isset($settings['min_internal_links']) ? $settings['min_internal_links'] : 2;
        if ($analysis['links']['internal_count'] >= $min_internal) {
            $score += 7;
        }

        // External links (5 points)
        if ($analysis['links']['external_count'] >= 1) {
            $score += 5;
        }

        // Image alt text (5 points)
        if ($analysis['images']['alt_percentage'] >= 80) {
            $score += 5;
        }

        // URL structure (5 points) - basic check
        $score += 5; // Default to yes, can be enhanced

        // H1 count (3 points)
        if ($analysis['structure']['h1_count'] === 1) {
            $score += 3;
        }

        return min(100, $score);
    }

    /**
     * Identify issues from analysis.
     *
     * @param array $analysis Analysis data.
     * @return array Issues array.
     */
    private static function identify_issues($analysis) {
        $issues = array();

        if (!$analysis['keyword_analysis']['in_title']) {
            $issues[] = array(
                'severity' => 'high',
                'message' => __('Focus keyword not found in title', 'claude-seo')
            );
        }

        if ($analysis['keyword_analysis']['density'] > 2.5) {
            $issues[] = array(
                'severity' => 'high',
                'message' => __('Keyword density too high - risk of keyword stuffing', 'claude-seo')
            );
        }

        if ($analysis['word_count'] < 300) {
            $issues[] = array(
                'severity' => 'high',
                'message' => __('Content too short - minimum 300 words recommended', 'claude-seo')
            );
        }

        if (!$analysis['meta']['description_optimal']) {
            $issues[] = array(
                'severity' => 'medium',
                'message' => __('Meta description length not optimal (150-160 characters)', 'claude-seo')
            );
        }

        if ($analysis['structure']['h1_count'] === 0) {
            $issues[] = array(
                'severity' => 'high',
                'message' => __('No H1 heading found', 'claude-seo')
            );
        } elseif ($analysis['structure']['h1_count'] > 1) {
            $issues[] = array(
                'severity' => 'medium',
                'message' => __('Multiple H1 headings found - use only one', 'claude-seo')
            );
        }

        if ($analysis['links']['internal_count'] < 2) {
            $issues[] = array(
                'severity' => 'medium',
                'message' => __('Add more internal links (minimum 2-3 recommended)', 'claude-seo')
            );
        }

        if ($analysis['links']['external_count'] === 0) {
            $issues[] = array(
                'severity' => 'low',
                'message' => __('Consider adding external links to authoritative sources', 'claude-seo')
            );
        }

        if ($analysis['images']['without_alt'] > 0) {
            $issues[] = array(
                'severity' => 'medium',
                'message' => sprintf(__('%d images missing alt text', 'claude-seo'), $analysis['images']['without_alt'])
            );
        }

        if ($analysis['readability']['flesch_reading_ease'] < 50) {
            $issues[] = array(
                'severity' => 'medium',
                'message' => __('Content readability could be improved', 'claude-seo')
            );
        }

        return $issues;
    }

    /**
     * Generate recommendations.
     *
     * @param array $analysis Analysis data.
     * @return array Recommendations.
     */
    private static function generate_recommendations($analysis) {
        $recommendations = array();

        if ($analysis['seo_score'] >= 80) {
            $recommendations[] = __('Great job! Your content is well-optimized.', 'claude-seo');
        }

        if ($analysis['keyword_analysis']['density'] < 0.5 && !empty($analysis['keyword_analysis']['keyword'])) {
            $recommendations[] = sprintf(
                __('Increase usage of focus keyword "%s" naturally throughout content', 'claude-seo'),
                $analysis['keyword_analysis']['keyword']
            );
        }

        if ($analysis['structure']['heading_count'] < 3) {
            $recommendations[] = __('Add more headings to improve content structure and scannability', 'claude-seo');
        }

        if ($analysis['structure']['avg_paragraph_length'] > 150) {
            $recommendations[] = __('Break up long paragraphs for better readability', 'claude-seo');
        }

        if ($analysis['word_count'] < 1000 && $analysis['word_count'] >= 300) {
            $recommendations[] = __('Consider expanding content for better depth and authority', 'claude-seo');
        }

        return $recommendations;
    }

    /**
     * Get empty analysis structure.
     *
     * @return array Empty analysis.
     */
    private static function get_empty_analysis() {
        return array(
            'seo_score' => 0,
            'word_count' => 0,
            'keyword_analysis' => array(),
            'readability' => array(),
            'structure' => array(),
            'links' => array(),
            'images' => array(),
            'meta' => array(),
            'issues' => array(),
            'recommendations' => array()
        );
    }
}
