<?php
/**
 * Opportunity Detection Engine.
 *
 * Automatically detects SEO opportunities:
 * - Quick wins (positions 4-10 with high impressions)
 * - High impression, low CTR pages
 * - Declining pages
 * - Keyword cannibalization
 * - Content gaps
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/analytics
 */

/**
 * Detects and prioritizes SEO opportunities.
 */
class Claude_SEO_Opportunity_Detector {

    /**
     * Scan all opportunities.
     *
     * @return array Prioritized opportunities.
     */
    public function scan_all_opportunities() {
        $opportunities = array();

        $opportunities = array_merge($opportunities, $this->find_quick_wins());
        $opportunities = array_merge($opportunities, $this->find_high_impression_low_ctr());
        $opportunities = array_merge($opportunities, $this->find_declining_pages());
        $opportunities = array_merge($opportunities, $this->find_cannibalization());
        $opportunities = array_merge($opportunities, $this->find_missing_featured_snippets());

        // Sort by estimated revenue gain
        usort($opportunities, function($a, $b) {
            return $b['estimated_revenue_gain'] <=> $a['estimated_revenue_gain'];
        });

        // Store in database
        $this->store_opportunities($opportunities);

        return $opportunities;
    }

    /**
     * Find quick win opportunities (positions 4-10).
     *
     * @return array Quick wins.
     */
    private function find_quick_wins() {
        global $wpdb;
        $table = Claude_SEO_Database::get_table_name('gsc_data');

        $sql = "
            SELECT page_id, keyword, AVG(position) as avg_position,
                   SUM(impressions) as total_impressions,
                   AVG(ctr) as avg_ctr
            FROM {$table}
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND position BETWEEN 4 AND 10
            GROUP BY page_id, keyword
            HAVING total_impressions > 1000
            ORDER BY total_impressions DESC
            LIMIT 50
        ";

        $results = $wpdb->get_results($sql);
        $opportunities = array();

        foreach ($results as $row) {
            $potential_clicks = $this->estimate_traffic_gain(
                $row->avg_position,
                3, // Target position 3
                $row->total_impressions
            );

            $opportunities[] = array(
                'type' => 'quick_win',
                'page_id' => $row->page_id,
                'keyword' => $row->keyword,
                'current_position' => round($row->avg_position, 1),
                'target_position' => 3,
                'current_impressions' => $row->total_impressions,
                'current_ctr' => round($row->avg_ctr * 100, 2),
                'estimated_traffic_gain' => $potential_clicks,
                'estimated_revenue_gain' => $this->estimate_revenue($potential_clicks),
                'priority' => $this->calculate_priority($potential_clicks),
                'recommendations' => $this->get_quick_win_recommendations($row)
            );
        }

        return $opportunities;
    }

    /**
     * Find high impression, low CTR pages.
     *
     * @return array Opportunities.
     */
    private function find_high_impression_low_ctr() {
        global $wpdb;
        $table = Claude_SEO_Database::get_table_name('gsc_data');

        $sql = "
            SELECT page_id, keyword, AVG(position) as avg_position,
                   SUM(impressions) as total_impressions,
                   AVG(ctr) as avg_ctr
            FROM {$table}
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND position <= 10
            GROUP BY page_id, keyword
            HAVING total_impressions > 500
            AND avg_ctr < 0.02
            ORDER BY total_impressions DESC
            LIMIT 30
        ";

        $results = $wpdb->get_results($sql);
        $opportunities = array();

        foreach ($results as $row) {
            $expected_ctr = $this->get_expected_ctr($row->avg_position);
            $ctr_gap = $expected_ctr - $row->avg_ctr;

            if ($ctr_gap > 0.01) { // At least 1% CTR improvement potential
                $potential_clicks = $row->total_impressions * $ctr_gap;

                $opportunities[] = array(
                    'type' => 'low_ctr',
                    'page_id' => $row->page_id,
                    'keyword' => $row->keyword,
                    'current_position' => round($row->avg_position, 1),
                    'current_ctr' => round($row->avg_ctr * 100, 2),
                    'expected_ctr' => round($expected_ctr * 100, 2),
                    'ctr_gap' => round($ctr_gap * 100, 2),
                    'estimated_traffic_gain' => round($potential_clicks),
                    'estimated_revenue_gain' => $this->estimate_revenue($potential_clicks),
                    'priority' => $this->calculate_priority($potential_clicks),
                    'recommendations' => array(
                        'Improve title tag to increase CTR',
                        'Add compelling meta description',
                        'Use power words and numbers in title',
                        'Test emotional triggers',
                        'Add current year to title if relevant'
                    )
                );
            }
        }

        return $opportunities;
    }

    /**
     * Find declining pages.
     *
     * @return array Opportunities.
     */
    private function find_declining_pages() {
        global $wpdb;
        $table = Claude_SEO_Database::get_table_name('gsc_data');

        // Compare last 7 days vs previous 7 days
        $sql = "
            SELECT
                page_id,
                keyword,
                AVG(CASE WHEN date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    THEN position END) as recent_position,
                AVG(CASE WHEN date BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAY)
                    AND DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    THEN position END) as previous_position,
                SUM(CASE WHEN date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    THEN clicks END) as recent_clicks
            FROM {$table}
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
            GROUP BY page_id, keyword
            HAVING recent_position IS NOT NULL
            AND previous_position IS NOT NULL
            AND (recent_position - previous_position) > 3
            AND recent_clicks > 10
            ORDER BY (recent_position - previous_position) DESC
            LIMIT 20
        ";

        $results = $wpdb->get_results($sql);
        $opportunities = array();

        foreach ($results as $row) {
            $position_drop = $row->recent_position - $row->previous_position;

            $opportunities[] = array(
                'type' => 'declining',
                'page_id' => $row->page_id,
                'keyword' => $row->keyword,
                'previous_position' => round($row->previous_position, 1),
                'current_position' => round($row->recent_position, 1),
                'position_drop' => round($position_drop, 1),
                'estimated_traffic_loss' => round($row->recent_clicks * 0.3),
                'estimated_revenue_gain' => $this->estimate_revenue($row->recent_clicks * 0.5),
                'priority' => 'high',
                'recommendations' => array(
                    'Audit for technical issues',
                    'Check for content freshness',
                    'Review competitor changes',
                    'Update content with latest information',
                    'Add new E-E-A-T signals',
                    'Improve internal linking to this page'
                )
            );
        }

        return $opportunities;
    }

    /**
     * Find keyword cannibalization.
     *
     * @return array Opportunities.
     */
    private function find_cannibalization() {
        global $wpdb;
        $table = Claude_SEO_Database::get_table_name('gsc_data');

        $sql = "
            SELECT keyword, COUNT(DISTINCT page_id) as page_count,
                   GROUP_CONCAT(DISTINCT page_id) as page_ids,
                   SUM(clicks) as total_clicks,
                   AVG(position) as avg_position
            FROM {$table}
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY keyword
            HAVING page_count > 1
            AND total_clicks > 50
            ORDER BY total_clicks DESC
            LIMIT 20
        ";

        $results = $wpdb->get_results($sql);
        $opportunities = array();

        foreach ($results as $row) {
            $page_ids = explode(',', $row->page_ids);

            $opportunities[] = array(
                'type' => 'cannibalization',
                'page_ids' => array_map('intval', $page_ids),
                'keyword' => $row->keyword,
                'competing_pages' => $row->page_count,
                'avg_position' => round($row->avg_position, 1),
                'total_clicks' => $row->total_clicks,
                'estimated_revenue_gain' => $this->estimate_revenue($row->total_clicks * 0.3),
                'priority' => 'medium',
                'recommendations' => array(
                    'Consolidate content into one authoritative page',
                    '301 redirect weaker pages to strongest',
                    'Use canonical tags if pages must remain',
                    'Differentiate keyword targeting',
                    'Add internal links to preferred page'
                )
            );
        }

        return $opportunities;
    }

    /**
     * Find missing featured snippet opportunities.
     *
     * @return array Opportunities.
     */
    private function find_missing_featured_snippets() {
        global $wpdb;
        $table = Claude_SEO_Database::get_table_name('gsc_data');

        // Pages ranking 2-5 (competitors might have snippet at #1)
        $sql = "
            SELECT page_id, keyword, AVG(position) as avg_position,
                   SUM(impressions) as total_impressions
            FROM {$table}
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND position BETWEEN 2 AND 5
            GROUP BY page_id, keyword
            HAVING total_impressions > 1000
            ORDER BY total_impressions DESC
            LIMIT 30
        ";

        $results = $wpdb->get_results($sql);
        $opportunities = array();

        foreach ($results as $row) {
            // Check if keyword likely triggers featured snippet (question words)
            if ($this->is_snippet_keyword($row->keyword)) {
                $potential_clicks = $row->total_impressions * 0.30; // Featured snippets get ~30% CTR

                $opportunities[] = array(
                    'type' => 'featured_snippet',
                    'page_id' => $row->page_id,
                    'keyword' => $row->keyword,
                    'current_position' => round($row->avg_position, 1),
                    'estimated_traffic_gain' => round($potential_clicks),
                    'estimated_revenue_gain' => $this->estimate_revenue($potential_clicks),
                    'priority' => 'high',
                    'recommendations' => array(
                        'Add structured FAQ section',
                        'Use clear H2 questions with concise answers',
                        'Format answers in 40-60 words',
                        'Add numbered/bulleted lists',
                        'Include summary tables',
                        'Use <strong> tags for key points'
                    )
                );
            }
        }

        return $opportunities;
    }

    /**
     * Estimate traffic gain from ranking improvement.
     *
     * @param float $current_position Current position.
     * @param float $target_position  Target position.
     * @param int   $impressions      Monthly impressions.
     * @return int Estimated click gain.
     */
    private function estimate_traffic_gain($current_position, $target_position, $impressions) {
        $current_ctr = $this->get_expected_ctr($current_position);
        $target_ctr = $this->get_expected_ctr($target_position);

        $ctr_increase = $target_ctr - $current_ctr;
        return max(0, round($impressions * $ctr_increase));
    }

    /**
     * Get expected CTR by position.
     *
     * @param float $position Position.
     * @return float Expected CTR.
     */
    private function get_expected_ctr($position) {
        // Industry average CTRs by position
        $ctr_curve = array(
            1 => 0.316,
            2 => 0.158,
            3 => 0.105,
            4 => 0.078,
            5 => 0.062,
            6 => 0.050,
            7 => 0.042,
            8 => 0.035,
            9 => 0.030,
            10 => 0.025
        );

        $pos = round($position);
        return isset($ctr_curve[$pos]) ? $ctr_curve[$pos] : 0.01;
    }

    /**
     * Estimate revenue from traffic.
     *
     * @param int $clicks Estimated clicks.
     * @return float Estimated revenue.
     */
    private function estimate_revenue($clicks) {
        $settings = get_option('claude_seo_settings', array());
        $conversion_rate = isset($settings['avg_conversion_rate']) ? $settings['avg_conversion_rate'] : 0.02;
        $avg_order_value = isset($settings['avg_order_value']) ? $settings['avg_order_value'] : 100;

        return round($clicks * $conversion_rate * $avg_order_value, 2);
    }

    /**
     * Calculate priority based on revenue potential.
     *
     * @param int $potential_clicks Potential clicks.
     * @return string Priority.
     */
    private function calculate_priority($potential_clicks) {
        if ($potential_clicks >= 500) {
            return 'critical';
        } elseif ($potential_clicks >= 200) {
            return 'high';
        } elseif ($potential_clicks >= 50) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Check if keyword likely triggers featured snippet.
     *
     * @param string $keyword Keyword.
     * @return bool True if snippet keyword.
     */
    private function is_snippet_keyword($keyword) {
        $snippet_triggers = array('what', 'why', 'how', 'when', 'where', 'who', 'which', 'best', 'top', 'vs');

        $keyword_lower = strtolower($keyword);

        foreach ($snippet_triggers as $trigger) {
            if (strpos($keyword_lower, $trigger) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get recommendations for quick win.
     *
     * @param object $data Row data.
     * @return array Recommendations.
     */
    private function get_quick_win_recommendations($data) {
        return array(
            'Optimize on-page SEO (title, headings, content)',
            'Add internal links from high-authority pages',
            'Improve E-E-A-T signals (expertise, authority)',
            'Update content with fresh information',
            'Add relevant images with optimized alt text',
            'Improve page speed (target LCP < 2.5s)',
            sprintf('Target position: #3 (currently #%.1f)', $data->avg_position)
        );
    }

    /**
     * Store opportunities in database.
     *
     * @param array $opportunities Opportunities.
     */
    private function store_opportunities($opportunities) {
        global $wpdb;
        $table = Claude_SEO_Database::get_table_name('opportunities');

        // Clear old open opportunities
        $wpdb->query("UPDATE {$table} SET status = 'dismissed' WHERE status = 'open' AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");

        foreach ($opportunities as $opp) {
            // Check if already exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table}
                 WHERE type = %s
                 AND page_id = %d
                 AND keyword = %s
                 AND status IN ('open', 'in_progress')",
                $opp['type'],
                isset($opp['page_id']) ? $opp['page_id'] : 0,
                isset($opp['keyword']) ? $opp['keyword'] : ''
            ));

            if (!$exists) {
                $wpdb->insert($table, array(
                    'type' => $opp['type'],
                    'page_id' => isset($opp['page_id']) ? $opp['page_id'] : 0,
                    'keyword' => isset($opp['keyword']) ? $opp['keyword'] : '',
                    'current_position' => isset($opp['current_position']) ? $opp['current_position'] : null,
                    'estimated_traffic_gain' => isset($opp['estimated_traffic_gain']) ? $opp['estimated_traffic_gain'] : 0,
                    'estimated_revenue_gain' => $opp['estimated_revenue_gain'],
                    'priority' => $opp['priority'],
                    'status' => 'open',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ));
            }
        }
    }

    /**
     * Get top opportunities.
     *
     * @param int $limit Limit.
     * @return array Opportunities.
     */
    public static function get_top_opportunities($limit = 10) {
        return Claude_SEO_Database::get_results(
            'opportunities',
            array('status' => array('open', 'in_progress')),
            'priority ASC, estimated_revenue_gain DESC',
            $limit
        );
    }
}
