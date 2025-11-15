<?php
/**
 * AI Overview Optimizer.
 *
 * Optimizes content for AI-powered search results:
 * - Google AI Overviews
 * - ChatGPT citations
 * - Perplexity AI references
 * - Other AI search engines
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/analytics
 */

/**
 * Optimizes for AI citations and tracks performance.
 */
class Claude_SEO_AI_Overview_Optimizer {

    /**
     * Analyze content for AI Overview optimization.
     *
     * @param string $content Content.
     * @param string $keyword Focus keyword.
     * @return array Analysis and recommendations.
     */
    public function analyze_for_ai_citations($content, $keyword) {
        $text = wp_strip_all_tags($content);

        return array(
            'citation_readiness_score' => $this->score_citation_readiness($content, $text),
            'eeat_strength' => $this->analyze_eeat_for_ai($text),
            'answer_format_score' => $this->score_answer_format($content),
            'statistics' => $this->extract_citable_stats($text),
            'expert_quotes' => $this->extract_expert_quotes($content),
            'structured_answers' => $this->find_structured_answers($content),
            'recommendations' => $this->generate_ai_optimization_recommendations($content, $keyword)
        );
    }

    /**
     * Score content's readiness for AI citations.
     *
     * @param string $content HTML content.
     * @param string $text    Plain text.
     * @return int Score 0-100.
     */
    private function score_citation_readiness($content, $text) {
        $score = 0;

        // Has concise answers (40-60 word summaries)
        if ($this->has_concise_answers($text)) {
            $score += 25;
        }

        // Has statistics with attributions
        if ($this->has_attributed_stats($text)) {
            $score += 20;
        }

        // Has expert quotes
        if ($this->has_expert_quotes($content)) {
            $score += 20;
        }

        // Has structured data (lists, tables)
        if ($this->has_structured_data($content)) {
            $score += 15;
        }

        // Has clear attributions
        if ($this->has_clear_attributions($text)) {
            $score += 20;
        }

        return $score;
    }

    /**
     * Check for concise answers.
     *
     * @param string $text Plain text.
     * @return bool True if has concise answers.
     */
    private function has_concise_answers($text) {
        // Look for paragraphs that are 40-60 words (ideal for AI citations)
        preg_match_all('/[.!?]\s+([^.!?]+[.!?])/', $text, $sentences);

        if (empty($sentences[1])) {
            return false;
        }

        foreach ($sentences[1] as $sentence) {
            $word_count = str_word_count($sentence);
            if ($word_count >= 40 && $word_count <= 60) {
                return true; // Found ideal-length answer
            }
        }

        return false;
    }

    /**
     * Check for attributed statistics.
     *
     * @param string $text Plain text.
     * @return bool True if has stats with sources.
     */
    private function has_attributed_stats($text) {
        // Look for patterns like "According to [source], X%"
        $patterns = array(
            '/according to [^,]+, \d+%/i',
            '/\d+% \(source: [^\)]+\)/i',
            '/[^.]+ found that \d+%/i'
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for expert quotes.
     *
     * @param string $content HTML content.
     * @return bool True if has expert quotes.
     */
    private function has_expert_quotes($content) {
        // Look for blockquotes or quoted text
        if (preg_match('/<blockquote[^>]*>/', $content)) {
            return true;
        }

        if (preg_match('/"[^"]+" - [A-Z][a-z]+ [A-Z][a-z]+/', $content)) {
            return true; // Quoted with attribution
        }

        return false;
    }

    /**
     * Check for structured data.
     *
     * @param string $content HTML content.
     * @return bool True if has lists/tables.
     */
    private function has_structured_data($content) {
        return preg_match('/<(ul|ol|table)[^>]*>/', $content) > 0;
    }

    /**
     * Check for clear attributions.
     *
     * @param string $text Plain text.
     * @return bool True if has attributions.
     */
    private function has_clear_attributions($text) {
        $attribution_phrases = array('according to', 'source:', 'cited by', 'reference:', 'as reported by');

        foreach ($attribution_phrases as $phrase) {
            if (stripos($text, $phrase) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Analyze E-E-A-T for AI.
     *
     * @param string $text Plain text.
     * @return array E-E-A-T scores.
     */
    private function analyze_eeat_for_ai($text) {
        $scorer = new Claude_SEO_Quality_Scorer();
        $quality = $scorer->score_content($text);

        return array(
            'experience' => $quality['scores']['experience'],
            'expertise' => $quality['scores']['expertise'],
            'authority' => $quality['scores']['authority'],
            'trust' => $quality['scores']['trust'],
            'overall' => $quality['score']
        );
    }

    /**
     * Score answer format quality.
     *
     * @param string $content HTML content.
     * @return int Score 0-100.
     */
    private function score_answer_format($content) {
        $score = 0;

        // Has FAQ section
        if (stripos($content, '<h2') !== false && stripos($content, '?') !== false) {
            $score += 30;
        }

        // Has numbered lists (steps, guides)
        if (preg_match('/<ol[^>]*>/', $content)) {
            $score += 25;
        }

        // Has bullet points (key takeaways)
        if (preg_match('/<ul[^>]*>/', $content)) {
            $score += 20;
        }

        // Has summary/conclusion
        if (stripos($content, 'summary') !== false || stripos($content, 'conclusion') !== false) {
            $score += 25;
        }

        return $score;
    }

    /**
     * Extract citable statistics.
     *
     * @param string $text Plain text.
     * @return array Statistics with context.
     */
    private function extract_citable_stats($text) {
        $stats = array();

        // Find percentages with context
        preg_match_all('/([^.!?]*\d+%[^.!?]*[.!?])/', $text, $matches);

        if (!empty($matches[1])) {
            foreach (array_slice($matches[1], 0, 5) as $stat) { // Top 5
                $stats[] = array(
                    'text' => trim($stat),
                    'citable' => $this->is_citable_stat($stat)
                );
            }
        }

        return $stats;
    }

    /**
     * Check if statistic is citable.
     *
     * @param string $stat Statistic text.
     * @return bool True if citable.
     */
    private function is_citable_stat($stat) {
        $citable_phrases = array('according to', 'study', 'research', 'survey', 'data shows', 'report');

        foreach ($citable_phrases as $phrase) {
            if (stripos($stat, $phrase) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract expert quotes.
     *
     * @param string $content HTML content.
     * @return array Expert quotes.
     */
    private function extract_expert_quotes($content) {
        $quotes = array();

        // Find blockquotes
        preg_match_all('/<blockquote[^>]*>(.*?)<\/blockquote>/is', $content, $blocks);

        if (!empty($blocks[1])) {
            foreach ($blocks[1] as $quote) {
                $quotes[] = array(
                    'text' => wp_trim_words(wp_strip_all_tags($quote), 30),
                    'has_attribution' => stripos($quote, 'cite') !== false || stripos($quote, '-') !== false
                );
            }
        }

        return $quotes;
    }

    /**
     * Find structured answers in content.
     *
     * @param string $content HTML content.
     * @return array Structured answers.
     */
    private function find_structured_answers($content) {
        $answers = array();

        // Find H2/H3 questions with following paragraphs
        preg_match_all('/<h[23][^>]*>([^<]*\?[^<]*)<\/h[23]>\s*<p[^>]*>([^<]+)<\/p>/i', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $question = wp_strip_all_tags($match[1]);
            $answer = wp_strip_all_tags($match[2]);
            $word_count = str_word_count($answer);

            $answers[] = array(
                'question' => $question,
                'answer' => $answer,
                'word_count' => $word_count,
                'optimal_length' => ($word_count >= 40 && $word_count <= 60)
            );
        }

        return $answers;
    }

    /**
     * Generate AI optimization recommendations.
     *
     * @param string $content HTML content.
     * @param string $keyword Focus keyword.
     * @return array Recommendations.
     */
    private function generate_ai_optimization_recommendations($content, $keyword) {
        $recommendations = array();

        if (!$this->has_concise_answers(wp_strip_all_tags($content))) {
            $recommendations[] = array(
                'priority' => 'high',
                'action' => 'Add 40-60 word concise answers to key questions',
                'detail' => 'AI models prefer direct, factual answers of this length for citations'
            );
        }

        if (!$this->has_attributed_stats(wp_strip_all_tags($content))) {
            $recommendations[] = array(
                'priority' => 'high',
                'action' => 'Add statistics with clear source attributions',
                'detail' => 'Use format: "According to [Source], X%" or include inline citations'
            );
        }

        if (!$this->has_expert_quotes($content)) {
            $recommendations[] = array(
                'priority' => 'medium',
                'action' => 'Include expert quotes or credentials',
                'detail' => 'Expert opinions increase authority signals for AI'
            );
        }

        if (!$this->has_structured_data($content)) {
            $recommendations[] = array(
                'priority' => 'medium',
                'action' => 'Add bullet points, numbered lists, or tables',
                'detail' => 'Structured data is easier for AI to parse and cite'
            );
        }

        if (!stripos($content, '<h2') || !stripos($content, '?')) {
            $recommendations[] = array(
                'priority' => 'high',
                'action' => 'Add FAQ section with clear question headings',
                'detail' => 'Use H2 tags for questions, followed by concise P tag answers'
            );
        }

        $recommendations[] = array(
            'priority' => 'medium',
            'action' => 'Ensure content is updated and dated',
            'detail' => 'AI models favor recent, timestamped information'
        );

        return $recommendations;
    }

    /**
     * Track AI citation appearance.
     *
     * @param int    $page_id Page ID.
     * @param string $source  Source (chatgpt, perplexity, google_ai).
     * @param string $keyword Keyword.
     * @param bool   $cited   Was cited.
     * @param int    $position Position in results.
     */
    public static function track_citation($page_id, $source, $keyword, $cited, $position = null) {
        global $wpdb;

        $wpdb->replace(
            Claude_SEO_Database::get_table_name('ai_citations'),
            array(
                'page_id' => $page_id,
                'source' => $source,
                'keyword' => $keyword,
                'cited' => $cited ? 1 : 0,
                'position' => $position,
                'date' => current_time('mysql', true)
            ),
            array('%d', '%s', '%s', '%d', '%d', '%s')
        );
    }

    /**
     * Get citation statistics for page.
     *
     * @param int $page_id Page ID.
     * @return array Citation stats.
     */
    public static function get_citation_stats($page_id) {
        global $wpdb;
        $table = Claude_SEO_Database::get_table_name('ai_citations');

        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT source, COUNT(*) as appearances,
                    SUM(cited) as citations,
                    AVG(position) as avg_position
             FROM {$table}
             WHERE page_id = %d
             AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY source",
            $page_id
        ), ARRAY_A);

        return $stats;
    }
}
