<?php
/**
 * SEO Recommendations Engine
 * AI-powered SEO manager ktorý generuje profesionálne odporúčania
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_SEO_Recommendations {

    private static $instance = null;
    private $ai_manager;
    private $db;
    private $technical_seo;
    private $content_analyzer;
    private $search_console;
    private $analytics;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->ai_manager = AI_SEO_Manager_AI_Manager::get_instance();
        $this->db = AI_SEO_Manager_Database::get_instance();
        $this->technical_seo = AI_SEO_Manager_Technical_SEO::get_instance();
        $this->content_analyzer = AI_SEO_Manager_Content_Analyzer::get_instance();
        $this->search_console = AI_SEO_Manager_Search_Console::get_instance();
        $this->analytics = AI_SEO_Manager_Google_Analytics::get_instance();

        $this->init_hooks();
    }

    /**
     * Init hooks
     */
    private function init_hooks() {
        add_action('save_post', array($this, 'auto_generate_recommendations'), 20, 2);
        add_action('ai_seo_manager_daily_analysis', array($this, 'run_daily_analysis'));
    }

    /**
     * Generovanie kompletných SEO odporúčaní ako profesionálny SEO manažér
     */
    public function generate_comprehensive_recommendations($post_id) {
        // Získaj všetky potrebné dáta
        $post = get_post($post_id);
        $focus_keyword = get_post_meta($post_id, '_ai_seo_focus_keyword', true);

        // 1. Technická SEO analýza
        $technical_analysis = $this->technical_seo->analyze($post_id);

        // 2. Content analýza
        $content_analysis = $this->content_analyzer->analyze($post_id, $focus_keyword);

        // 3. Search Console dáta
        $search_data = $this->search_console->get_page_keywords($post_id, 10);
        if (is_wp_error($search_data)) {
            $search_data = array();
        }

        // 4. Analytics dáta
        $performance_data = $this->analytics->get_page_performance($post_id);
        if (is_wp_error($performance_data)) {
            $performance_data = array();
        }

        // 5. Search Console opportunities
        $opportunities = $this->search_console->get_opportunities($post_id);
        if (is_wp_error($opportunities)) {
            $opportunities = array();
        }

        // Skombinuj všetky dáta
        $combined_analysis = array(
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'focus_keyword' => $focus_keyword,
            'technical_seo' => $technical_analysis,
            'content_analysis' => $content_analysis,
            'search_performance' => array(
                'keywords' => $search_data,
                'opportunities' => $opportunities,
            ),
            'analytics' => $performance_data,
        );

        // Ulož analýzu
        $this->db->save_analysis(
            $post_id,
            'comprehensive',
            $this->calculate_overall_score($combined_analysis),
            $combined_analysis
        );

        // AI generovanie odporúčaní
        $ai_recommendations = $this->ai_manager->generate_recommendations(
            $combined_analysis,
            array('post_type' => $post->post_type)
        );

        // Vytvor štruktúrované odporúčania
        $recommendations = $this->structure_recommendations($post_id, $ai_recommendations, $combined_analysis);

        // Ulož do DB
        foreach ($recommendations as $recommendation) {
            $this->db->save_recommendation($recommendation);
        }

        // Loguj akciu
        $this->db->log('recommendations_generated', "Generated " . count($recommendations) . " recommendations for post {$post_id}");

        return array(
            'analysis' => $combined_analysis,
            'recommendations' => $recommendations,
        );
    }

    /**
     * Štruktúrovanie AI odporúčaní do formátu pluginu
     */
    private function structure_recommendations($post_id, $ai_recommendations, $analysis) {
        $structured = array();

        // Pridaj AI recommendations
        if (!empty($ai_recommendations) && is_array($ai_recommendations)) {
            foreach ($ai_recommendations as $rec) {
                $structured[] = array(
                    'post_id' => $post_id,
                    'recommendation_type' => $this->determine_recommendation_type($rec),
                    'priority' => $this->normalize_priority($rec['priority'] ?? 'medium'),
                    'title' => $rec['title'] ?? 'SEO Improvement',
                    'description' => $rec['description'] ?? '',
                    'action_data' => array(
                        'action' => $rec['action'] ?? $rec['specific_action'] ?? '',
                        'estimated_impact' => $rec['estimated_impact'] ?? $rec['impact'] ?? 5,
                        'ai_generated' => true,
                    ),
                    'ai_confidence' => isset($rec['confidence']) ? floatval($rec['confidence']) : 0.8,
                );
            }
        }

        // Pridaj automatické odporúčania založené na analýze
        $structured = array_merge($structured, $this->generate_automatic_recommendations($post_id, $analysis));

        return $structured;
    }

    /**
     * Automatické odporúčania založené na analýze
     */
    private function generate_automatic_recommendations($post_id, $analysis) {
        $recommendations = array();

        // Technical SEO recommendations
        if (isset($analysis['technical_seo']['checks'])) {
            foreach ($analysis['technical_seo']['checks'] as $check_type => $check) {
                if (isset($check['issues']) && !empty($check['issues'])) {
                    foreach ($check['issues'] as $issue) {
                        $recommendations[] = array(
                            'post_id' => $post_id,
                            'recommendation_type' => 'technical_seo',
                            'priority' => $this->severity_to_priority($issue['severity'] ?? 'medium'),
                            'title' => $issue['message'] ?? 'Technical SEO Issue',
                            'description' => $issue['fix'] ?? 'Fix this technical SEO issue',
                            'action_data' => array(
                                'check_type' => $check_type,
                                'auto_fixable' => $this->is_auto_fixable($check_type, $issue),
                            ),
                            'ai_confidence' => 0.95,
                        );
                    }
                }
            }
        }

        // Content recommendations
        if (isset($analysis['content_analysis']['keyword_analysis']['issues'])) {
            foreach ($analysis['content_analysis']['keyword_analysis']['issues'] as $issue) {
                $recommendations[] = array(
                    'post_id' => $post_id,
                    'recommendation_type' => 'keyword_optimization',
                    'priority' => 'high',
                    'title' => 'Keyword Optimization: ' . $issue,
                    'description' => 'Improve keyword usage for better SEO',
                    'action_data' => array(
                        'keyword' => $analysis['focus_keyword'] ?? '',
                    ),
                    'ai_confidence' => 0.9,
                );
            }
        }

        // Search Console opportunities
        if (isset($analysis['search_performance']['opportunities']) && !empty($analysis['search_performance']['opportunities'])) {
            $top_opportunity = $analysis['search_performance']['opportunities'][0];

            $recommendations[] = array(
                'post_id' => $post_id,
                'recommendation_type' => 'search_opportunity',
                'priority' => 'high',
                'title' => 'Opportunity: Optimize for "' . ($top_opportunity['keyword'] ?? 'keyword') . '"',
                'description' => sprintf(
                    'This keyword has %d impressions but only %.2f%% CTR. Improving title/meta could increase traffic.',
                    $top_opportunity['impressions'] ?? 0,
                    ($top_opportunity['ctr'] ?? 0) * 100
                ),
                'action_data' => array(
                    'keyword' => $top_opportunity['keyword'] ?? '',
                    'current_position' => $top_opportunity['position'] ?? 0,
                    'impressions' => $top_opportunity['impressions'] ?? 0,
                ),
                'ai_confidence' => 0.85,
            );
        }

        return $recommendations;
    }

    /**
     * Určenie typu odporúčania
     */
    private function determine_recommendation_type($recommendation) {
        $title_lower = strtolower($recommendation['title'] ?? '');
        $desc_lower = strtolower($recommendation['description'] ?? '');

        if (stripos($title_lower, 'meta') !== false) return 'meta_optimization';
        if (stripos($title_lower, 'keyword') !== false) return 'keyword_optimization';
        if (stripos($title_lower, 'heading') !== false) return 'content_structure';
        if (stripos($title_lower, 'image') !== false || stripos($title_lower, 'alt') !== false) return 'image_optimization';
        if (stripos($title_lower, 'link') !== false) return 'link_optimization';
        if (stripos($title_lower, 'speed') !== false || stripos($title_lower, 'performance') !== false) return 'performance';

        return 'general';
    }

    /**
     * Normalizácia priority
     */
    private function normalize_priority($priority) {
        $priority_lower = strtolower($priority);

        if (in_array($priority_lower, array('critical', 'urgent', 'very high'))) return 'critical';
        if (in_array($priority_lower, array('high', 'important'))) return 'high';
        if (in_array($priority_lower, array('medium', 'moderate', 'normal'))) return 'medium';
        if (in_array($priority_lower, array('low', 'minor'))) return 'low';

        return 'medium';
    }

    /**
     * Convert severity to priority
     */
    private function severity_to_priority($severity) {
        $map = array(
            'critical' => 'critical',
            'high' => 'high',
            'medium' => 'medium',
            'low' => 'low',
            'warning' => 'medium',
        );

        return $map[$severity] ?? 'medium';
    }

    /**
     * Kontrola či je issue auto-fixable
     */
    private function is_auto_fixable($check_type, $issue) {
        $auto_fixable_types = array(
            'meta_tags' => true,
            'images' => true, // ALT texty
        );

        return $auto_fixable_types[$check_type] ?? false;
    }

    /**
     * Vypočítaj celkové SEO skóre
     */
    private function calculate_overall_score($analysis) {
        $scores = array();

        if (isset($analysis['technical_seo']['score'])) {
            $scores[] = $analysis['technical_seo']['score'];
        }
        if (isset($analysis['content_analysis']['score'])) {
            $scores[] = $analysis['content_analysis']['score'];
        }

        return count($scores) > 0 ? round(array_sum($scores) / count($scores)) : 0;
    }

    /**
     * Auto-generovanie odporúčaní pri uložení postu
     */
    public function auto_generate_recommendations($post_id, $post) {
        // Skip autosave, revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Len pre publikované posty
        if ($post->post_status !== 'publish') {
            return;
        }

        // Kontrola nastavení
        $settings = AI_SEO_Manager_Settings::get_instance();
        if (!$settings->get('auto_analysis', true)) {
            return;
        }

        // Generuj odporúčania v pozadí
        wp_schedule_single_event(time() + 60, 'ai_seo_manager_generate_recommendations', array($post_id));
    }

    /**
     * Denná analýza všetkých stránok
     */
    public function run_daily_analysis() {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'orderby' => 'modified',
            'order' => 'DESC',
        );

        $posts = get_posts($args);

        foreach ($posts as $post) {
            $this->generate_comprehensive_recommendations($post->ID);
        }
    }
}
