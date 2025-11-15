<?php
/**
 * Cost tracking for Claude API usage.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/claude
 */

/**
 * Tracks API usage and costs.
 */
class Claude_SEO_Cost_Tracker {

    /**
     * Model pricing (per million tokens).
     */
    const PRICING = array(
        'claude-sonnet-4-5-20250929' => array(
            'input' => 3.00,
            'output' => 15.00
        ),
        'claude-haiku-4-5-20250930' => array(
            'input' => 1.00,
            'output' => 5.00
        ),
        'claude-opus-4-20250514' => array(
            'input' => 15.00,
            'output' => 75.00
        )
    );

    /**
     * Track API usage from response.
     *
     * @param array $response API response.
     */
    public static function track_usage($response) {
        if (!isset($response['usage']) || !isset($response['model'])) {
            return;
        }

        $user_id = get_current_user_id();
        $date = gmdate('Y-m-d');
        $model = $response['model'];

        $input_tokens = isset($response['usage']['input_tokens']) ? $response['usage']['input_tokens'] : 0;
        $output_tokens = isset($response['usage']['output_tokens']) ? $response['usage']['output_tokens'] : 0;

        // Calculate cost
        $cost = self::calculate_cost($model, $input_tokens, $output_tokens);

        // Update database
        global $wpdb;
        $table = $wpdb->prefix . 'claude_seo_claude_usage';

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$table} (user_id, date, requests, tokens_input, tokens_output, cost_usd)
                 VALUES (%d, %s, 1, %d, %d, %f)
                 ON DUPLICATE KEY UPDATE
                 requests = requests + 1,
                 tokens_input = tokens_input + %d,
                 tokens_output = tokens_output + %d,
                 cost_usd = cost_usd + %f",
                $user_id,
                $date,
                $input_tokens,
                $output_tokens,
                $cost,
                $input_tokens,
                $output_tokens,
                $cost
            )
        );

        // Check budget limits
        self::check_budget_limit($user_id);
    }

    /**
     * Calculate cost for tokens.
     *
     * @param string $model         Model name.
     * @param int    $input_tokens  Input tokens.
     * @param int    $output_tokens Output tokens.
     * @return float Cost in USD.
     */
    public static function calculate_cost($model, $input_tokens, $output_tokens) {
        if (!isset(self::PRICING[$model])) {
            Claude_SEO_Logger::warning('Unknown model for pricing', array('model' => $model));
            return 0.0;
        }

        $pricing = self::PRICING[$model];

        $input_cost = ($input_tokens / 1000000) * $pricing['input'];
        $output_cost = ($output_tokens / 1000000) * $pricing['output'];

        return round($input_cost + $output_cost, 6);
    }

    /**
     * Get usage statistics.
     *
     * @param int    $user_id User ID (0 for all users).
     * @param string $period  Period (today, week, month, all).
     * @return array Usage stats.
     */
    public static function get_usage_stats($user_id = 0, $period = 'month') {
        global $wpdb;
        $table = $wpdb->prefix . 'claude_seo_claude_usage';

        $where = array();
        $values = array();

        if ($user_id > 0) {
            $where[] = 'user_id = %d';
            $values[] = $user_id;
        }

        // Date filter
        switch ($period) {
            case 'today':
                $where[] = 'date = %s';
                $values[] = gmdate('Y-m-d');
                break;

            case 'week':
                $where[] = 'date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
                break;

            case 'month':
                $where[] = 'date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
                break;

            case 'all':
            default:
                // No date filter
                break;
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT
                    SUM(requests) as total_requests,
                    SUM(tokens_input) as total_input_tokens,
                    SUM(tokens_output) as total_output_tokens,
                    SUM(cost_usd) as total_cost
                FROM {$table}
                {$where_clause}";

        if (!empty($values)) {
            $result = $wpdb->get_row($wpdb->prepare($sql, ...$values));
        } else {
            $result = $wpdb->get_row($sql);
        }

        return array(
            'requests' => (int) ($result->total_requests ?? 0),
            'input_tokens' => (int) ($result->total_input_tokens ?? 0),
            'output_tokens' => (int) ($result->total_output_tokens ?? 0),
            'total_tokens' => (int) (($result->total_input_tokens ?? 0) + ($result->total_output_tokens ?? 0)),
            'cost_usd' => (float) ($result->total_cost ?? 0)
        );
    }

    /**
     * Check if budget limit is exceeded.
     *
     * @param int $user_id User ID.
     * @return bool True if within budget.
     */
    private static function check_budget_limit($user_id) {
        $settings = get_option('claude_seo_settings', array());
        $monthly_budget = isset($settings['claude_cost_budget_monthly'])
            ? floatval($settings['claude_cost_budget_monthly'])
            : 0;

        if ($monthly_budget <= 0) {
            return true; // No limit
        }

        $stats = self::get_usage_stats($user_id, 'month');

        if ($stats['cost_usd'] >= $monthly_budget) {
            Claude_SEO_Logger::warning('Monthly budget limit reached', array(
                'user_id' => $user_id,
                'cost' => $stats['cost_usd'],
                'budget' => $monthly_budget
            ));

            // Send notification
            do_action('claude_seo_budget_limit_reached', $user_id, $stats['cost_usd'], $monthly_budget);

            return false;
        }

        // Warning at 80%
        if ($stats['cost_usd'] >= ($monthly_budget * 0.8)) {
            do_action('claude_seo_budget_warning', $user_id, $stats['cost_usd'], $monthly_budget);
        }

        return true;
    }

    /**
     * Get usage history by date.
     *
     * @param int $user_id User ID (0 for all users).
     * @param int $days    Number of days to retrieve.
     * @return array Usage history.
     */
    public static function get_usage_history($user_id = 0, $days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'claude_seo_claude_usage';

        $where = 'date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)';
        $values = array($days);

        if ($user_id > 0) {
            $where .= ' AND user_id = %d';
            $values[] = $user_id;
        }

        $sql = "SELECT date, SUM(requests) as requests, SUM(tokens_input) as input_tokens,
                       SUM(tokens_output) as output_tokens, SUM(cost_usd) as cost
                FROM {$table}
                WHERE {$where}
                GROUP BY date
                ORDER BY date DESC";

        return $wpdb->get_results($wpdb->prepare($sql, ...$values));
    }

    /**
     * Estimate cost for a request.
     *
     * @param string $model         Model name.
     * @param int    $input_tokens  Estimated input tokens.
     * @param int    $output_tokens Estimated output tokens.
     * @return array Cost estimate.
     */
    public static function estimate_cost($model, $input_tokens, $output_tokens) {
        $cost = self::calculate_cost($model, $input_tokens, $output_tokens);

        return array(
            'model' => $model,
            'input_tokens' => $input_tokens,
            'output_tokens' => $output_tokens,
            'estimated_cost_usd' => $cost,
            'formatted_cost' => '$' . number_format($cost, 4)
        );
    }
}
