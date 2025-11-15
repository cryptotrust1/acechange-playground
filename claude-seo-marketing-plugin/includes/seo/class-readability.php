<?php
/**
 * Readability calculator.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/seo
 */

/**
 * Calculates readability scores using Flesch Reading Ease formula.
 */
class Claude_SEO_Readability {

    /**
     * Analyze content readability.
     *
     * @param string $content Content to analyze.
     * @return array Readability metrics.
     */
    public static function analyze($content) {
        $text = wp_strip_all_tags($content);
        $text = self::clean_text($text);

        $sentences = self::count_sentences($text);
        $words = self::count_words($text);
        $syllables = self::count_syllables($text);

        if ($sentences === 0 || $words === 0) {
            return self::get_empty_analysis();
        }

        $flesch_reading_ease = self::calculate_flesch_reading_ease($words, $sentences, $syllables);
        $flesch_kincaid_grade = self::calculate_flesch_kincaid_grade($words, $sentences, $syllables);

        return array(
            'flesch_reading_ease' => round($flesch_reading_ease, 1),
            'flesch_kincaid_grade' => round($flesch_kincaid_grade, 1),
            'interpretation' => self::interpret_flesch_score($flesch_reading_ease),
            'words' => $words,
            'sentences' => $sentences,
            'syllables' => $syllables,
            'avg_sentence_length' => round($words / $sentences, 1),
            'avg_syllables_per_word' => round($syllables / $words, 2)
        );
    }

    /**
     * Clean text for analysis.
     *
     * @param string $text Text to clean.
     * @return string Cleaned text.
     */
    private static function clean_text($text) {
        // Remove multiple spaces
        $text = preg_replace('/\s+/', ' ', $text);
        // Remove extra line breaks
        $text = preg_replace('/\n+/', "\n", $text);
        return trim($text);
    }

    /**
     * Count sentences.
     *
     * @param string $text Text.
     * @return int Sentence count.
     */
    private static function count_sentences($text) {
        // Split on period, exclamation, question mark
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return count($sentences);
    }

    /**
     * Count words.
     *
     * @param string $text Text.
     * @return int Word count.
     */
    private static function count_words($text) {
        return str_word_count($text);
    }

    /**
     * Count syllables in text.
     *
     * @param string $text Text.
     * @return int Syllable count.
     */
    private static function count_syllables($text) {
        $words = str_word_count($text, 1);
        $total_syllables = 0;

        foreach ($words as $word) {
            $total_syllables += self::count_syllables_in_word($word);
        }

        return max(1, $total_syllables);
    }

    /**
     * Count syllables in a single word.
     *
     * @param string $word Word.
     * @return int Syllable count.
     */
    private static function count_syllables_in_word($word) {
        $word = strtolower($word);
        $word = preg_replace('/[^a-z]/', '', $word);

        if (strlen($word) <= 3) {
            return 1;
        }

        // Count vowel groups
        $syllables = preg_match_all('/[aeiouy]+/', $word, $matches);

        // Subtract silent e
        if (substr($word, -1) === 'e') {
            $syllables--;
        }

        // Minimum 1 syllable
        return max(1, $syllables);
    }

    /**
     * Calculate Flesch Reading Ease score.
     *
     * @param int $words     Word count.
     * @param int $sentences Sentence count.
     * @param int $syllables Syllable count.
     * @return float Score (0-100).
     */
    private static function calculate_flesch_reading_ease($words, $sentences, $syllables) {
        return 206.835 - 1.015 * ($words / $sentences) - 84.6 * ($syllables / $words);
    }

    /**
     * Calculate Flesch-Kincaid Grade Level.
     *
     * @param int $words     Word count.
     * @param int $sentences Sentence count.
     * @param int $syllables Syllable count.
     * @return float Grade level.
     */
    private static function calculate_flesch_kincaid_grade($words, $sentences, $syllables) {
        return 0.39 * ($words / $sentences) + 11.8 * ($syllables / $words) - 15.59;
    }

    /**
     * Interpret Flesch Reading Ease score.
     *
     * @param float $score Flesch score.
     * @return string Interpretation.
     */
    private static function interpret_flesch_score($score) {
        if ($score >= 90) {
            return __('Very easy to read', 'claude-seo');
        } elseif ($score >= 80) {
            return __('Easy to read', 'claude-seo');
        } elseif ($score >= 70) {
            return __('Fairly easy to read', 'claude-seo');
        } elseif ($score >= 60) {
            return __('Standard', 'claude-seo');
        } elseif ($score >= 50) {
            return __('Fairly difficult to read', 'claude-seo');
        } elseif ($score >= 30) {
            return __('Difficult to read', 'claude-seo');
        } else {
            return __('Very difficult to read', 'claude-seo');
        }
    }

    /**
     * Get empty analysis.
     *
     * @return array Empty analysis.
     */
    private static function get_empty_analysis() {
        return array(
            'flesch_reading_ease' => 0,
            'flesch_kincaid_grade' => 0,
            'interpretation' => __('No content to analyze', 'claude-seo'),
            'words' => 0,
            'sentences' => 0,
            'syllables' => 0,
            'avg_sentence_length' => 0,
            'avg_syllables_per_word' => 0
        );
    }
}
