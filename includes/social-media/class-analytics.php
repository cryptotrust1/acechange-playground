<?php
/**
 * Social Media Analytics Component
 *
 * Zodpovedný za:
 * - Získavanie analytics z platforiem
 * - Ukladanie metrics do databázy
 * - Generovanie reportov
 * - Performance tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Social_Analytics {

    private static $instance = null;
    private $db;
    private $registry;
    private $logger;

    /**
     * Singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = AI_SEO_Social_Database::get_instance();
        $this->registry = AI_SEO_Social_Platform_Registry::get_instance();

        // Initialize debug tools if available
        if (class_exists('AI_SEO_Manager_Debug_Logger')) {
            $this->logger = AI_SEO_Manager_Debug_Logger::get_instance();
        }

        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Schedule daily analytics sync
        if (!wp_next_scheduled('ai_seo_social_sync_analytics')) {
            wp_schedule_event(time(), 'daily', 'ai_seo_social_sync_analytics');
        }

        add_action('ai_seo_social_sync_analytics', array($this, 'sync_all_analytics'));
    }

    /**
     * Sync analytics for a specific post
     *
     * @param int $post_id Social post ID
     * @return array|WP_Error Analytics data or error
     */
    public function sync_post_analytics($post_id) {
        if ($this->logger) {
            $this->logger->info("Syncing analytics for post {$post_id}");
        }

        // Get post details
        $post = $this->db->get_post($post_id);

        if (!$post) {
            return new WP_Error('post_not_found', 'Social post not found');
        }

        // Only sync published posts
        if ($post->status !== 'published') {
            return new WP_Error('post_not_published', 'Post is not published yet');
        }

        // Get platform client
        $client = $this->registry->get($post->platform);

        if (!$client) {
            return new WP_Error('platform_not_found', "Platform {$post->platform} not registered");
        }

        // Get analytics from platform
        try {
            $analytics = $client->get_analytics($post->platform_post_id);

            if (is_wp_error($analytics)) {
                if ($this->logger) {
                    $this->logger->warning("Failed to get analytics for post {$post_id}", array(
                        'error' => $analytics->get_error_message(),
                    ));
                }
                return $analytics;
            }

            // Save to database
            $this->save_analytics($post_id, $post->platform, $analytics);

            if ($this->logger) {
                $this->logger->info("Analytics synced successfully for post {$post_id}", $analytics);
            }

            return $analytics;

        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Exception syncing analytics for post {$post_id}", array(
                    'message' => $e->getMessage(),
                ));
            }
            return new WP_Error('sync_failed', $e->getMessage());
        }
    }

    /**
     * Sync analytics for all recent published posts
     *
     * @param int $days Number of days back to sync (default 7)
     * @return array Sync results
     */
    public function sync_all_analytics($days = 7) {
        if ($this->logger) {
            $this->logger->info("Starting bulk analytics sync (last {$days} days)");
        }

        global $wpdb;
        $posts_table = $wpdb->prefix . 'ai_seo_social_posts';

        // Get all published posts from last N days
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$posts_table}
             WHERE status = 'published'
             AND platform_post_id IS NOT NULL
             AND published_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             ORDER BY published_at DESC",
            $days
        ));

        $results = array(
            'total' => count($posts),
            'synced' => 0,
            'failed' => 0,
            'errors' => array(),
        );

        foreach ($posts as $post) {
            $result = $this->sync_post_analytics($post->id);

            if (is_wp_error($result)) {
                $results['failed']++;
                $results['errors'][] = array(
                    'post_id' => $post->id,
                    'error' => $result->get_error_message(),
                );
            } else {
                $results['synced']++;
            }

            // Rate limiting - small delay between syncs
            usleep(500000); // 0.5 second
        }

        if ($this->logger) {
            $this->logger->info('Bulk analytics sync completed', $results);
        }

        return $results;
    }

    /**
     * Save analytics data to database
     *
     * @param int $post_id Social post ID
     * @param string $platform Platform name
     * @param array $analytics Analytics data
     * @return int|false Insert ID or false on failure
     */
    private function save_analytics($post_id, $platform, $analytics) {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_seo_social_analytics';

        $data = array(
            'social_post_id' => $post_id,
            'platform' => $platform,
            'metric_date' => current_time('Y-m-d'),
            'impressions' => isset($analytics['impressions']) ? (int)$analytics['impressions'] : 0,
            'reach' => isset($analytics['reach']) ? (int)$analytics['reach'] : 0,
            'likes' => isset($analytics['likes']) ? (int)$analytics['likes'] : 0,
            'comments' => isset($analytics['comments']) ? (int)$analytics['comments'] : 0,
            'shares' => isset($analytics['shares']) ? (int)$analytics['shares'] : 0,
            'saves' => isset($analytics['saves']) ? (int)$analytics['saves'] : 0,
            'clicks' => isset($analytics['clicks']) ? (int)$analytics['clicks'] : 0,
            'engagement_rate' => isset($analytics['engagement_rate']) ? (float)$analytics['engagement_rate'] : 0,
            'data' => json_encode($analytics),
            'synced_at' => current_time('mysql'),
        );

        // Check if analytics for this post and date already exist
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table}
             WHERE social_post_id = %d
             AND metric_date = %s",
            $post_id,
            $data['metric_date']
        ));

        if ($existing) {
            // Update existing record
            $result = $wpdb->update(
                $table,
                $data,
                array('id' => $existing),
                array('%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%f', '%s', '%s'),
                array('%d')
            );
        } else {
            // Insert new record
            $result = $wpdb->insert(
                $table,
                $data,
                array('%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%f', '%s', '%s')
            );
        }

        return $result !== false ? $wpdb->insert_id : false;
    }

    /**
     * Get analytics for a specific post
     *
     * @param int $post_id Social post ID
     * @param int $days Number of days to retrieve (default 30)
     * @return array Analytics data
     */
    public function get_post_analytics($post_id, $days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_seo_social_analytics';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE social_post_id = %d
             AND metric_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
             ORDER BY metric_date ASC",
            $post_id,
            $days
        ), ARRAY_A);

        // Calculate totals
        $totals = array(
            'impressions' => 0,
            'reach' => 0,
            'likes' => 0,
            'comments' => 0,
            'shares' => 0,
            'saves' => 0,
            'clicks' => 0,
            'engagement_rate' => 0,
        );

        foreach ($results as $row) {
            $totals['impressions'] += $row['impressions'];
            $totals['reach'] += $row['reach'];
            $totals['likes'] += $row['likes'];
            $totals['comments'] += $row['comments'];
            $totals['shares'] += $row['shares'];
            $totals['saves'] += $row['saves'];
            $totals['clicks'] += $row['clicks'];
        }

        // Calculate average engagement rate
        if (count($results) > 0) {
            $totals['engagement_rate'] = array_sum(array_column($results, 'engagement_rate')) / count($results);
        }

        return array(
            'daily' => $results,
            'totals' => $totals,
            'period_days' => $days,
        );
    }

    /**
     * Get analytics summary for all platforms
     *
     * @param int $days Number of days to analyze (default 30)
     * @return array Platform-wise analytics
     */
    public function get_platform_summary($days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_seo_social_analytics';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                platform,
                COUNT(DISTINCT social_post_id) as total_posts,
                SUM(impressions) as total_impressions,
                SUM(reach) as total_reach,
                SUM(likes) as total_likes,
                SUM(comments) as total_comments,
                SUM(shares) as total_shares,
                SUM(saves) as total_saves,
                SUM(clicks) as total_clicks,
                AVG(engagement_rate) as avg_engagement_rate
             FROM {$table}
             WHERE metric_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
             GROUP BY platform
             ORDER BY total_impressions DESC",
            $days
        ), ARRAY_A);

        return $results;
    }

    /**
     * Get top performing posts
     *
     * @param string $metric Metric to sort by (impressions, likes, engagement_rate, etc.)
     * @param int $limit Number of posts to return
     * @param int $days Number of days to analyze
     * @return array Top posts
     */
    public function get_top_posts($metric = 'engagement_rate', $limit = 10, $days = 30) {
        global $wpdb;
        $analytics_table = $wpdb->prefix . 'ai_seo_social_analytics';
        $posts_table = $wpdb->prefix . 'ai_seo_social_posts';

        // Validate metric
        $allowed_metrics = array('impressions', 'reach', 'likes', 'comments', 'shares', 'saves', 'clicks', 'engagement_rate');
        if (!in_array($metric, $allowed_metrics)) {
            $metric = 'engagement_rate';
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT
                p.id,
                p.platform,
                p.content,
                p.published_at,
                SUM(a.impressions) as total_impressions,
                SUM(a.likes) as total_likes,
                SUM(a.comments) as total_comments,
                SUM(a.shares) as total_shares,
                AVG(a.engagement_rate) as avg_engagement_rate
             FROM {$posts_table} p
             INNER JOIN {$analytics_table} a ON p.id = a.social_post_id
             WHERE a.metric_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
             GROUP BY p.id
             ORDER BY {$metric} DESC
             LIMIT %d",
            $days,
            $limit
        ), ARRAY_A);

        return $results;
    }

    /**
     * Get engagement trends over time
     *
     * @param string $platform Optional platform filter
     * @param int $days Number of days to analyze
     * @return array Trend data
     */
    public function get_engagement_trends($platform = null, $days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_seo_social_analytics';

        $where = "WHERE metric_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)";
        $params = array($days);

        if ($platform) {
            $where .= " AND platform = %s";
            $params[] = $platform;
        }

        $query = "SELECT
                    metric_date,
                    platform,
                    SUM(impressions) as impressions,
                    SUM(likes) as likes,
                    SUM(comments) as comments,
                    SUM(shares) as shares,
                    AVG(engagement_rate) as engagement_rate
                 FROM {$table}
                 {$where}
                 GROUP BY metric_date, platform
                 ORDER BY metric_date ASC";

        $results = $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );

        return $results;
    }

    /**
     * Get platform comparison
     *
     * @param int $days Number of days to analyze
     * @return array Comparison data
     */
    public function get_platform_comparison($days = 30) {
        $summary = $this->get_platform_summary($days);
        $comparison = array();

        foreach ($summary as $platform_data) {
            $platform = $platform_data['platform'];

            // Calculate engagement metrics
            $total_interactions = $platform_data['total_likes'] +
                                  $platform_data['total_comments'] +
                                  $platform_data['total_shares'];

            $engagement_per_post = $platform_data['total_posts'] > 0
                ? $total_interactions / $platform_data['total_posts']
                : 0;

            $comparison[$platform] = array(
                'posts' => (int)$platform_data['total_posts'],
                'impressions' => (int)$platform_data['total_impressions'],
                'reach' => (int)$platform_data['total_reach'],
                'total_engagement' => (int)$total_interactions,
                'engagement_per_post' => round($engagement_per_post, 2),
                'avg_engagement_rate' => round($platform_data['avg_engagement_rate'], 2),
                'clicks' => (int)$platform_data['total_clicks'],
            );
        }

        return $comparison;
    }

    /**
     * Get best posting times analysis
     *
     * @param string $platform Optional platform filter
     * @param int $days Number of days to analyze
     * @return array Best times data
     */
    public function get_best_posting_times($platform = null, $days = 30) {
        global $wpdb;
        $analytics_table = $wpdb->prefix . 'ai_seo_social_analytics';
        $posts_table = $wpdb->prefix . 'ai_seo_social_posts';

        $where = "WHERE a.metric_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)";
        $params = array($days);

        if ($platform) {
            $where .= " AND p.platform = %s";
            $params[] = $platform;
        }

        $query = "SELECT
                    HOUR(p.published_at) as hour,
                    DAYOFWEEK(p.published_at) as day_of_week,
                    AVG(a.engagement_rate) as avg_engagement,
                    COUNT(*) as post_count
                 FROM {$posts_table} p
                 INNER JOIN {$analytics_table} a ON p.id = a.social_post_id
                 {$where}
                 GROUP BY HOUR(p.published_at), DAYOFWEEK(p.published_at)
                 ORDER BY avg_engagement DESC";

        $results = $wpdb->get_results(
            $wpdb->prepare($query, $params),
            ARRAY_A
        );

        // Group by hour and day
        $by_hour = array();
        $by_day = array();

        foreach ($results as $row) {
            $hour = (int)$row['hour'];
            $day = (int)$row['day_of_week'];

            if (!isset($by_hour[$hour])) {
                $by_hour[$hour] = array('engagement' => 0, 'posts' => 0);
            }
            $by_hour[$hour]['engagement'] += $row['avg_engagement'];
            $by_hour[$hour]['posts'] += $row['post_count'];

            if (!isset($by_day[$day])) {
                $by_day[$day] = array('engagement' => 0, 'posts' => 0);
            }
            $by_day[$day]['engagement'] += $row['avg_engagement'];
            $by_day[$day]['posts'] += $row['post_count'];
        }

        return array(
            'by_hour' => $by_hour,
            'by_day_of_week' => $by_day,
            'raw' => $results,
        );
    }

    /**
     * Generate analytics report
     *
     * @param int $days Number of days to include
     * @return array Complete analytics report
     */
    public function generate_report($days = 30) {
        if ($this->logger) {
            $this->logger->info("Generating analytics report for last {$days} days");
        }

        $report = array(
            'period' => array(
                'days' => $days,
                'start_date' => date('Y-m-d', strtotime("-{$days} days")),
                'end_date' => date('Y-m-d'),
            ),
            'platform_summary' => $this->get_platform_summary($days),
            'platform_comparison' => $this->get_platform_comparison($days),
            'top_posts' => array(
                'by_engagement' => $this->get_top_posts('engagement_rate', 10, $days),
                'by_impressions' => $this->get_top_posts('impressions', 10, $days),
                'by_likes' => $this->get_top_posts('likes', 10, $days),
            ),
            'trends' => $this->get_engagement_trends(null, $days),
            'best_times' => $this->get_best_posting_times(null, $days),
        );

        return $report;
    }

    /**
     * Clean up old analytics data
     *
     * @param int $days Days to keep (default 90)
     * @return int Number of records deleted
     */
    public function cleanup_old_analytics($days = 90) {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_seo_social_analytics';

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table}
             WHERE metric_date < DATE_SUB(CURDATE(), INTERVAL %d DAY)",
            $days
        ));

        if ($this->logger && $deleted > 0) {
            $this->logger->info("Cleaned up {$deleted} old analytics records");
        }

        return $deleted;
    }

    /**
     * Get statistics
     *
     * @return array Statistics
     */
    public function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_seo_social_analytics';

        return array(
            'total_records' => $wpdb->get_var("SELECT COUNT(*) FROM {$table}"),
            'platforms_tracked' => $wpdb->get_var("SELECT COUNT(DISTINCT platform) FROM {$table}"),
            'date_range' => array(
                'first' => $wpdb->get_var("SELECT MIN(metric_date) FROM {$table}"),
                'last' => $wpdb->get_var("SELECT MAX(metric_date) FROM {$table}"),
            ),
        );
    }
}
