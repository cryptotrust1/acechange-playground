<?php
/**
 * Competitor Analyzer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Competitor_Analyzer {

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
     * Analýza konkurencie pre keyword
     */
    public function analyze($keyword, $competitor_urls = array()) {
        $results = array(
            'keyword' => $keyword,
            'difficulty' => $this->estimate_difficulty($keyword),
            'competitors' => array(),
            'ai_insights' => null,
        );

        // Analyzuj konkurentov
        foreach ($competitor_urls as $url) {
            $results['competitors'][] = $this->analyze_competitor_page($url, $keyword);
        }

        // AI analýza
        if (!empty($competitor_urls)) {
            $ai_analysis = $this->ai_manager->analyze_competitors($keyword, $competitor_urls);
            if (!is_wp_error($ai_analysis)) {
                $results['ai_insights'] = $ai_analysis;
            }
        }

        return $results;
    }

    /**
     * Odhad náročnosti keyword
     */
    private function estimate_difficulty($keyword) {
        // V produkčnej verzii: integrácia s API ako Ahrefs, SEMrush
        // Teraz: jednoduchý odhad založený na dĺžke a bežnosti

        $word_count = str_word_count($keyword);

        if ($word_count >= 4) {
            return 30; // Long-tail keywords sú ľahšie
        } elseif ($word_count === 3) {
            return 50;
        } elseif ($word_count === 2) {
            return 70;
        } else {
            return 85; // Single keywords sú najťažšie
        }
    }

    /**
     * Analýza konkurenčnej stránky
     */
    private function analyze_competitor_page($url, $keyword) {
        // V produkčnej verzii: fetch a analyzuj stránku
        // Teraz: placeholder

        return array(
            'url' => $url,
            'title' => '',
            'word_count' => 0,
            'headings' => array(),
            'images' => 0,
            'internal_links' => 0,
            'external_links' => 0,
            'has_keyword_in_title' => false,
            'estimated_authority' => 50,
        );
    }

    /**
     * Porovnanie s konkurenciou
     */
    public function compare_with_competitors($post_id, $competitor_urls, $keyword = '') {
        // Analýza vlastnej stránky
        $own_analysis = $this->analyze_own_page($post_id);

        // Analýza konkurencie
        $competitor_analysis = $this->analyze($keyword, $competitor_urls);

        // Porovnanie
        $comparison = array(
            'own' => $own_analysis,
            'competitors' => $competitor_analysis,
            'gaps' => $this->identify_content_gaps($own_analysis, $competitor_analysis),
            'strengths' => $this->identify_strengths($own_analysis, $competitor_analysis),
        );

        return $comparison;
    }

    /**
     * Analýza vlastnej stránky
     */
    private function analyze_own_page($post_id) {
        $post = get_post($post_id);
        $content = $post->post_content;

        return array(
            'word_count' => str_word_count(wp_strip_all_tags($content)),
            'image_count' => substr_count($content, '<img'),
            'heading_count' => preg_match_all('/<h[2-6]/i', $content),
            'has_video' => (stripos($content, '<iframe') !== false) || (stripos($content, '<video') !== false),
        );
    }

    /**
     * Identifikuj content gaps
     */
    private function identify_content_gaps($own, $competitors) {
        $gaps = array();

        // Príklady gaps ktoré AI môže identifikovať
        if (isset($competitors['ai_insights']['content_gaps'])) {
            return $competitors['ai_insights']['content_gaps'];
        }

        return $gaps;
    }

    /**
     * Identifikuj silné stránky
     */
    private function identify_strengths($own, $competitors) {
        $strengths = array();

        // Analýza silných stránok vs konkurencia

        return $strengths;
    }
}
