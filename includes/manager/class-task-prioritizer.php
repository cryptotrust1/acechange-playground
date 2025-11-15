<?php
/**
 * Task Prioritizer - AI-powered prioritizácia SEO úloh
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Task_Prioritizer {

    private static $instance = null;
    private $db;
    private $ai_manager;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = AI_SEO_Manager_Database::get_instance();
        $this->ai_manager = AI_SEO_Manager_AI_Manager::get_instance();
    }

    /**
     * Získanie prioritizovaných úloh
     */
    public function get_prioritized_tasks($limit = 20, $context = array()) {
        $pending = $this->db->get_pending_recommendations($limit * 2);

        if (empty($pending)) {
            return array();
        }

        // Spočítaj priority score pre každú úlohu
        $scored_tasks = array();

        foreach ($pending as $task) {
            $score = $this->calculate_priority_score($task, $context);
            $task->priority_score = $score;
            $scored_tasks[] = $task;
        }

        // Zoraď podľa priority score
        usort($scored_tasks, function($a, $b) {
            return $b->priority_score - $a->priority_score;
        });

        // Vráť top N tasks
        return array_slice($scored_tasks, 0, $limit);
    }

    /**
     * Výpočet priority score
     */
    private function calculate_priority_score($task, $context) {
        $score = 0;

        // 1. Base priority
        $priority_scores = array(
            'critical' => 100,
            'high' => 75,
            'medium' => 50,
            'low' => 25,
        );

        $score += $priority_scores[$task->priority] ?? 50;

        // 2. AI confidence
        if (!empty($task->ai_confidence)) {
            $score += ($task->ai_confidence * 30); // Max +30
        }

        // 3. Recommendation type priority
        $type_scores = array(
            'meta_optimization' => 20, // Ľahko fixable, vysoký impact
            'keyword_optimization' => 18,
            'search_opportunity' => 25, // Veľký potenciál
            'technical_seo' => 15,
            'image_optimization' => 12,
            'content_structure' => 15,
            'link_optimization' => 10,
            'performance' => 14,
        );

        $score += $type_scores[$task->recommendation_type] ?? 10;

        // 4. Estimated impact (ak je dostupný)
        if (!empty($task->action_data)) {
            $action_data = maybe_unserialize($task->action_data);
            if (isset($action_data['estimated_impact'])) {
                $score += intval($action_data['estimated_impact']) * 2; // Max +20
            }

            // Auto-fixable dostáva bonus
            if (!empty($action_data['auto_fixable'])) {
                $score += 15;
            }
        }

        // 5. Freshness - novšie recommendations sú relevantnejšie
        $created_timestamp = strtotime($task->created_at);
        $days_old = (time() - $created_timestamp) / DAY_IN_SECONDS;

        if ($days_old < 1) {
            $score += 10; // Veľmi fresh
        } elseif ($days_old < 7) {
            $score += 5; // Fresh
        } elseif ($days_old > 30) {
            $score -= 10; // Starý, možno už neaktuálny
        }

        // 6. Kontext-based scoring
        if (!empty($context['focus_on_quick_wins'])) {
            // Prefer auto-fixable a meta optimizations
            if (strpos($task->recommendation_type, 'meta') !== false) {
                $score += 20;
            }
        }

        if (!empty($context['focus_on_traffic'])) {
            // Prefer search opportunities
            if ($task->recommendation_type === 'search_opportunity') {
                $score += 25;
            }
        }

        return $score;
    }

    /**
     * Vytvorenie action planu
     */
    public function create_action_plan($post_id = null) {
        $context = array(
            'focus_on_quick_wins' => true,
        );

        if ($post_id) {
            // Plan pre konkrétny post
            $tasks = $this->get_prioritized_tasks(10, $context);
            $tasks = array_filter($tasks, function($task) use ($post_id) {
                return $task->post_id == $post_id;
            });
        } else {
            // Globálny action plan
            $tasks = $this->get_prioritized_tasks(20, $context);
        }

        // Zoskup tasks podľa typu
        $plan = array(
            'quick_wins' => array(),
            'high_impact' => array(),
            'long_term' => array(),
        );

        foreach ($tasks as $task) {
            $action_data = maybe_unserialize($task->action_data);

            if (!empty($action_data['auto_fixable']) && $task->priority_score >= 70) {
                $plan['quick_wins'][] = $task;
            } elseif ($task->priority === 'critical' || $task->priority === 'high') {
                $plan['high_impact'][] = $task;
            } else {
                $plan['long_term'][] = $task;
            }
        }

        return $plan;
    }

    /**
     * AI-powered strategické odporúčania
     */
    public function get_strategic_recommendations($context = array()) {
        // Získaj všetky pending tasks
        $all_tasks = $this->db->get_pending_recommendations(100);

        // Priprav dáta pre AI
        $task_summary = array(
            'total_pending' => count($all_tasks),
            'by_priority' => array(),
            'by_type' => array(),
            'posts_affected' => array(),
        );

        foreach ($all_tasks as $task) {
            // Count by priority
            if (!isset($task_summary['by_priority'][$task->priority])) {
                $task_summary['by_priority'][$task->priority] = 0;
            }
            $task_summary['by_priority'][$task->priority]++;

            // Count by type
            if (!isset($task_summary['by_type'][$task->recommendation_type])) {
                $task_summary['by_type'][$task->recommendation_type] = 0;
            }
            $task_summary['by_type'][$task->recommendation_type]++;

            // Collect affected posts
            if ($task->post_id && !in_array($task->post_id, $task_summary['posts_affected'])) {
                $task_summary['posts_affected'][] = $task->post_id;
            }
        }

        // AI prompt
        $prompt = "As a professional SEO strategist, analyze this task summary and provide strategic recommendations:\n\n";
        $prompt .= json_encode($task_summary, JSON_PRETTY_PRINT) . "\n\n";
        $prompt .= "Provide:\n";
        $prompt .= "1. Overall strategy (what to focus on first)\n";
        $prompt .= "2. Quick wins (tasks that can be done quickly with high impact)\n";
        $prompt .= "3. Long-term priorities\n";
        $prompt .= "4. Resource allocation recommendations\n";
        $prompt .= "5. Expected timeline and milestones\n\n";
        $prompt .= "Return as JSON with keys: strategy, quick_wins, long_term, resources, timeline";

        $response = $this->ai_manager->chat($prompt, array(
            'system' => 'You are a senior SEO strategist with 10+ years of experience. Provide actionable, data-driven recommendations.',
        ));

        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }

        // Parse JSON response
        $content = $response['content'];
        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            $data = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }

        return array('raw_response' => $content);
    }
}
