<?php
/**
 * Keyword optimization analyzer.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/seo
 */

/**
 * Analyzes keyword usage and density.
 */
class Claude_SEO_Keyword_Optimizer {

    /**
     * Analyze keyword usage.
     *
     * @param string $content Content.
     * @param string $title   Title.
     * @param string $keyword Focus keyword.
     * @return array Keyword analysis.
     */
    public static function analyze_keyword_usage($content, $title, $keyword) {
        if (empty($keyword)) {
            return array(
                'keyword' => '',
                'density' => 0,
                'count' => 0,
                'in_title' => false,
                'in_first_paragraph' => false,
                'in_headings' => false,
                'keyword_variations' => array()
            );
        }

        $text = wp_strip_all_tags($content);
        $keyword_lower = strtolower($keyword);

        // Count keyword occurrences
        $count = self::count_keyword_occurrences($text, $keyword);

        // Calculate density
        $word_count = str_word_count($text);
        $keyword_word_count = str_word_count($keyword);
        $density = $word_count > 0 ? (($count * $keyword_word_count) / $word_count) * 100 : 0;

        // Check keyword positions
        $in_title = stripos($title, $keyword) !== false;
        $in_first_paragraph = self::is_in_first_paragraph($content, $keyword);
        $in_headings = self::is_in_headings($content, $keyword);

        // Find keyword variations (LSI keywords)
        $variations = self::find_keyword_variations($text, $keyword);

        return array(
            'keyword' => $keyword,
            'density' => round($density, 2),
            'count' => $count,
            'in_title' => $in_title,
            'in_first_paragraph' => $in_first_paragraph,
            'in_headings' => $in_headings,
            'keyword_variations' => $variations,
            'is_stuffed' => $density > 3.0
        );
    }

    /**
     * Count keyword occurrences (case-insensitive).
     *
     * @param string $text    Text to search.
     * @param string $keyword Keyword to find.
     * @return int Count.
     */
    private static function count_keyword_occurrences($text, $keyword) {
        return substr_count(strtolower($text), strtolower($keyword));
    }

    /**
     * Check if keyword is in first paragraph.
     *
     * @param string $content Content.
     * @param string $keyword Keyword.
     * @return bool True if in first paragraph.
     */
    private static function is_in_first_paragraph($content, $keyword) {
        preg_match('/<p[^>]*>(.*?)<\/p>/is', $content, $match);

        if (empty($match)) {
            // No paragraph tags, check first 100 words
            $text = wp_strip_all_tags($content);
            $words = explode(' ', $text, 101);
            $first_100 = implode(' ', array_slice($words, 0, 100));
            return stripos($first_100, $keyword) !== false;
        }

        return stripos($match[1], $keyword) !== false;
    }

    /**
     * Check if keyword is in any heading.
     *
     * @param string $content Content.
     * @param string $keyword Keyword.
     * @return bool True if in headings.
     */
    private static function is_in_headings($content, $keyword) {
        preg_match_all('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i', $content, $matches);

        foreach ($matches[1] as $heading_text) {
            if (stripos($heading_text, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find keyword variations in text.
     *
     * @param string $text    Text.
     * @param string $keyword Main keyword.
     * @return array Variations found.
     */
    private static function find_keyword_variations($text, $keyword) {
        // Simple variation detection based on word roots
        $variations = array();
        $keyword_words = explode(' ', strtolower($keyword));

        foreach ($keyword_words as $word) {
            if (strlen($word) < 4) {
                continue; // Skip short words
            }

            // Find root (simple stemming)
            $root = substr($word, 0, -2);

            if (empty($root)) {
                continue;
            }

            // Count variations
            $pattern = '/\b' . preg_quote($root, '/') . '\w*/i';
            preg_match_all($pattern, $text, $matches);

            if (!empty($matches[0])) {
                $unique_matches = array_unique(array_map('strtolower', $matches[0]));
                foreach ($unique_matches as $match) {
                    if ($match !== strtolower($word) && !isset($variations[$match])) {
                        $variations[$match] = substr_count(strtolower($text), $match);
                    }
                }
            }
        }

        // Sort by frequency
        arsort($variations);

        // Return top 5
        return array_slice($variations, 0, 5, true);
    }

    /**
     * Suggest keyword placement improvements.
     *
     * @param array $analysis Keyword analysis.
     * @return array Suggestions.
     */
    public static function get_keyword_suggestions($analysis) {
        $suggestions = array();

        if (!$analysis['in_title']) {
            $suggestions[] = array(
                'priority' => 'high',
                'message' => sprintf(
                    __('Add the focus keyword "%s" to your title', 'claude-seo'),
                    $analysis['keyword']
                )
            );
        }

        if (!$analysis['in_first_paragraph']) {
            $suggestions[] = array(
                'priority' => 'high',
                'message' => sprintf(
                    __('Include "%s" in the first paragraph', 'claude-seo'),
                    $analysis['keyword']
                )
            );
        }

        if (!$analysis['in_headings']) {
            $suggestions[] = array(
                'priority' => 'medium',
                'message' => sprintf(
                    __('Use "%s" in at least one heading', 'claude-seo'),
                    $analysis['keyword']
                )
            );
        }

        if ($analysis['density'] < 0.5) {
            $suggestions[] = array(
                'priority' => 'medium',
                'message' => sprintf(
                    __('Keyword density is low (%.2f%%). Consider using "%s" more naturally', 'claude-seo'),
                    $analysis['density'],
                    $analysis['keyword']
                )
            );
        }

        if ($analysis['density'] > 2.5) {
            $suggestions[] = array(
                'priority' => 'high',
                'message' => sprintf(
                    __('Keyword density is too high (%.2f%%). Reduce usage of "%s" to avoid keyword stuffing', 'claude-seo'),
                    $analysis['density'],
                    $analysis['keyword']
                )
            );
        }

        if (empty($analysis['keyword_variations'])) {
            $suggestions[] = array(
                'priority' => 'low',
                'message' => __('Use keyword variations and synonyms for more natural content', 'claude-seo')
            );
        }

        return $suggestions;
    }
}
