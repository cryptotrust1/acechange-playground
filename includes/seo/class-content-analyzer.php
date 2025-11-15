<?php
/**
 * Content Analyzer - Analýza obsahu pre SEO
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Content_Analyzer {

    private static $instance = null;
    private $ai_manager;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->ai_manager = AI_SEO_Manager_AI_Manager::get_instance();
    }

    /**
     * Kompletná analýza obsahu
     */
    public function analyze($post_id, $focus_keyword = '') {
        $post = get_post($post_id);
        $content = $post->post_content;
        $title = $post->post_title;

        $results = array(
            'score' => 0,
            'keyword_analysis' => $this->analyze_keyword_usage($content, $title, $focus_keyword),
            'readability' => $this->analyze_readability($content),
            'content_quality' => $this->analyze_content_quality($content),
            'ai_insights' => null,
        );

        // AI analýza
        if (!empty($focus_keyword)) {
            $ai_analysis = $this->ai_manager->analyze_seo_content($content, $focus_keyword);
            if (!is_wp_error($ai_analysis)) {
                $results['ai_insights'] = $ai_analysis;
            }
        }

        // Vypočítaj celkové skóre
        $results['score'] = $this->calculate_content_score($results);

        return $results;
    }

    /**
     * Analýza použitia keywords
     */
    private function analyze_keyword_usage($content, $title, $keyword) {
        if (empty($keyword)) {
            return array('score' => 0, 'message' => 'No focus keyword set');
        }

        $score = 0;
        $issues = array();
        $keyword_lower = strtolower($keyword);

        // Remove HTML tags
        $clean_content = wp_strip_all_tags($content);
        $clean_title = wp_strip_all_tags($title);

        // Keyword v titulku
        if (stripos($clean_title, $keyword) !== false) {
            $score += 25;
        } else {
            $issues[] = 'Keyword not found in title';
        }

        // Keyword density
        $word_count = str_word_count($clean_content);
        $keyword_count = substr_count(strtolower($clean_content), $keyword_lower);

        if ($word_count > 0) {
            $density = ($keyword_count / $word_count) * 100;

            if ($density >= 0.5 && $density <= 2.5) {
                $score += 25;
            } elseif ($density > 2.5) {
                $issues[] = 'Keyword density too high (' . round($density, 2) . '%). Risk of keyword stuffing.';
                $score += 10;
            } else {
                $issues[] = 'Keyword density too low (' . round($density, 2) . '%). Use keyword more naturally.';
                $score += 10;
            }
        }

        // Keyword v prvých 100 slovách
        $first_100_words = implode(' ', array_slice(str_word_count($clean_content, 1), 0, 100));
        if (stripos($first_100_words, $keyword) !== false) {
            $score += 25;
        } else {
            $issues[] = 'Keyword not found in first 100 words';
        }

        // Keyword v URL/slug
        $post_slug = basename(get_permalink());
        if (stripos($post_slug, str_replace(' ', '-', $keyword_lower)) !== false) {
            $score += 25;
        } else {
            $issues[] = 'Keyword not found in URL slug';
        }

        return array(
            'score' => $score,
            'keyword' => $keyword,
            'density' => $density ?? 0,
            'count' => $keyword_count ?? 0,
            'word_count' => $word_count,
            'in_title' => stripos($clean_title, $keyword) !== false,
            'in_first_100' => stripos($first_100_words, $keyword) !== false,
            'in_url' => stripos($post_slug, str_replace(' ', '-', $keyword_lower)) !== false,
            'issues' => $issues,
        );
    }

    /**
     * Analýza čitateľnosti
     */
    private function analyze_readability($content) {
        $clean_content = wp_strip_all_tags($content);
        $score = 0;
        $issues = array();

        // Word count
        $word_count = str_word_count($clean_content);

        if ($word_count >= 300 && $word_count <= 2000) {
            $score += 30;
        } elseif ($word_count > 2000) {
            $score += 20;
            $issues[] = 'Content is quite long (' . $word_count . ' words). Consider breaking into multiple pages.';
        } else {
            $issues[] = 'Content is too short (' . $word_count . ' words). Aim for at least 300 words.';
        }

        // Sentence length
        $sentences = preg_split('/[.!?]+/', $clean_content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);

        $avg_words_per_sentence = $sentence_count > 0 ? $word_count / $sentence_count : 0;

        if ($avg_words_per_sentence <= 20) {
            $score += 20;
        } else {
            $issues[] = 'Average sentence length too long (' . round($avg_words_per_sentence, 1) . ' words). Aim for under 20 words.';
            $score += 10;
        }

        // Paragraph count (approximate)
        $paragraphs = explode("\n\n", $content);
        $paragraph_count = count(array_filter($paragraphs, function($p) {
            return strlen(trim(wp_strip_all_tags($p))) > 0;
        }));

        if ($paragraph_count >= 3) {
            $score += 25;
        } else {
            $issues[] = 'Too few paragraphs. Break content into smaller paragraphs for better readability.';
            $score += 10;
        }

        // Flesch Reading Ease (simplified)
        $flesch_score = $this->calculate_flesch_score($clean_content);
        if ($flesch_score >= 60) {
            $score += 25;
        } else {
            $issues[] = 'Content may be difficult to read. Simplify language.';
            $score += 10;
        }

        return array(
            'score' => $score,
            'word_count' => $word_count,
            'sentence_count' => $sentence_count,
            'paragraph_count' => $paragraph_count,
            'avg_words_per_sentence' => round($avg_words_per_sentence, 1),
            'flesch_score' => $flesch_score,
            'issues' => $issues,
        );
    }

    /**
     * Zjednodušený výpočet Flesch Reading Ease
     */
    private function calculate_flesch_score($text) {
        $words = str_word_count($text);
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);

        // Count syllables (simplified)
        $syllables = 0;
        $word_array = str_word_count($text, 1);
        foreach ($word_array as $word) {
            $syllables += $this->count_syllables($word);
        }

        if ($words == 0 || $sentence_count == 0) {
            return 0;
        }

        $flesch = 206.835 - 1.015 * ($words / $sentence_count) - 84.6 * ($syllables / $words);

        return max(0, min(100, round($flesch, 1)));
    }

    /**
     * Počítanie slabík (zjednodušené)
     */
    private function count_syllables($word) {
        $word = strtolower($word);
        $syllables = 0;
        $vowels = array('a', 'e', 'i', 'o', 'u', 'y');

        $prev_was_vowel = false;
        for ($i = 0; $i < strlen($word); $i++) {
            $is_vowel = in_array($word[$i], $vowels);
            if ($is_vowel && !$prev_was_vowel) {
                $syllables++;
            }
            $prev_was_vowel = $is_vowel;
        }

        // Silent 'e'
        if (substr($word, -1) === 'e') {
            $syllables--;
        }

        return max(1, $syllables);
    }

    /**
     * Analýza kvality obsahu
     */
    private function analyze_content_quality($content) {
        $score = 0;
        $issues = array();

        // Má obrázky?
        $image_count = substr_count($content, '<img');
        if ($image_count > 0) {
            $score += 20;
        } else {
            $issues[] = 'No images found. Add relevant images to improve engagement.';
        }

        // Má video?
        $has_video = (stripos($content, '<iframe') !== false) ||
                     (stripos($content, '<video') !== false) ||
                     (stripos($content, 'youtube.com') !== false) ||
                     (stripos($content, 'vimeo.com') !== false);

        if ($has_video) {
            $score += 15;
        }

        // Má zoznamy (bullets)?
        $has_lists = (stripos($content, '<ul') !== false) || (stripos($content, '<ol') !== false);
        if ($has_lists) {
            $score += 15;
        } else {
            $issues[] = 'No bullet points or numbered lists. Use lists to improve scannability.';
        }

        // Má nadpisy?
        $heading_count = preg_match_all('/<h[2-6]/i', $content);
        if ($heading_count > 0) {
            $score += 25;
        } else {
            $issues[] = 'No subheadings (H2-H6) found. Add headings to structure content.';
        }

        // Má formátovanie (bold, italic)?
        $has_formatting = (stripos($content, '<strong') !== false) ||
                          (stripos($content, '<em') !== false) ||
                          (stripos($content, '<b>') !== false);

        if ($has_formatting) {
            $score += 15;
        }

        // Má interné linky?
        $has_internal_links = preg_match('/<a[^>]+href=["\'](?!http)/i', $content);
        if ($has_internal_links) {
            $score += 10;
        } else {
            $issues[] = 'No internal links found. Link to related content on your site.';
        }

        return array(
            'score' => $score,
            'has_images' => $image_count > 0,
            'has_video' => $has_video,
            'has_lists' => $has_lists,
            'has_headings' => $heading_count > 0,
            'has_formatting' => $has_formatting,
            'has_internal_links' => $has_internal_links,
            'issues' => $issues,
        );
    }

    /**
     * Vypočítaj celkové content skóre
     */
    private function calculate_content_score($results) {
        $scores = array();

        if (isset($results['keyword_analysis']['score'])) {
            $scores[] = $results['keyword_analysis']['score'];
        }
        if (isset($results['readability']['score'])) {
            $scores[] = $results['readability']['score'];
        }
        if (isset($results['content_quality']['score'])) {
            $scores[] = $results['content_quality']['score'];
        }

        return count($scores) > 0 ? round(array_sum($scores) / count($scores)) : 0;
    }
}
