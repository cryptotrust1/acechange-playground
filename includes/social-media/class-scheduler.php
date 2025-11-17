<?php
/**
 * Social Media Scheduler & Queue Manager
 *
 * Zodpovedný za:
 * - Plánovanie príspevkov
 * - Queue management
 * - Automatické publikovanie
 * - Retry logic
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Social_Scheduler {

    private static $instance = null;
    private $db;
    private $manager;
    private $logger;
    private $performance;

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
        $this->manager = AI_SEO_Social_Media_Manager::get_instance();

        // Initialize debug tools if available
        if (class_exists('AI_SEO_Manager_Debug_Logger')) {
            $this->logger = AI_SEO_Manager_Debug_Logger::get_instance();
            $this->performance = AI_SEO_Manager_Performance_Monitor::get_instance();
        }

        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Schedule cron job for processing queue
        if (!wp_next_scheduled('ai_seo_social_process_queue')) {
            wp_schedule_event(time(), 'every_minute', 'ai_seo_social_process_queue');
        }

        add_action('ai_seo_social_process_queue', array($this, 'process_queue'));

        // Add custom cron schedule
        add_filter('cron_schedules', array($this, 'add_cron_schedules'));
    }

    /**
     * Add custom cron schedules
     *
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public function add_cron_schedules($schedules) {
        if (!isset($schedules['every_minute'])) {
            $schedules['every_minute'] = array(
                'interval' => 60,
                'display' => __('Every Minute', 'ai-seo-manager'),
            );
        }

        if (!isset($schedules['every_five_minutes'])) {
            $schedules['every_five_minutes'] = array(
                'interval' => 300,
                'display' => __('Every 5 Minutes', 'ai-seo-manager'),
            );
        }

        return $schedules;
    }

    /**
     * Schedule a post for future publishing
     *
     * @param int $post_id Social post ID
     * @param string $scheduled_time DateTime string (Y-m-d H:i:s)
     * @param int $priority Priority 1-10 (10 = highest)
     * @return int|WP_Error Queue ID or error
     */
    public function schedule_post($post_id, $scheduled_time, $priority = 5) {
        if ($this->logger) {
            $this->logger->info("Scheduling post {$post_id} for {$scheduled_time}");
        }

        // Validate post exists
        $post = $this->db->get_post($post_id);
        if (!$post) {
            return new WP_Error('post_not_found', 'Social post not found');
        }

        // Validate scheduled time is in future
        $scheduled_timestamp = strtotime($scheduled_time);
        if ($scheduled_timestamp <= time()) {
            return new WP_Error('invalid_time', 'Scheduled time must be in the future');
        }

        // Add to queue
        global $wpdb;
        $table = $wpdb->prefix . 'ai_seo_social_queue';

        $result = $wpdb->insert(
            $table,
            array(
                'social_post_id' => $post_id,
                'priority' => max(1, min(10, (int)$priority)),
                'scheduled_for' => $scheduled_time,
                'processing' => 0,
                'attempts' => 0,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%d', '%d', '%s')
        );

        if ($result === false) {
            return new WP_Error('queue_insert_failed', $wpdb->last_error);
        }

        $queue_id = $wpdb->insert_id;

        // Update post status
        $this->db->update_post_status($post_id, 'scheduled');

        if ($this->logger) {
            $this->logger->info("Post {$post_id} scheduled successfully", array(
                'queue_id' => $queue_id,
                'scheduled_for' => $scheduled_time,
            ));
        }

        return $queue_id;
    }

    /**
     * Process the queue - publish posts that are due
     *
     * @return array Processing results
     */
    public function process_queue() {
        if ($this->logger) {
            $this->logger->debug('Processing social media queue');
        }

        if ($this->performance) {
            $this->performance->start_tracking('social_queue_processing');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ai_seo_social_queue';

        // Get all posts due for publishing (not currently processing)
        $due_items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE scheduled_for <= %s
             AND processing = 0
             AND (next_retry IS NULL OR next_retry <= NOW())
             ORDER BY priority DESC, scheduled_for ASC
             LIMIT 10",
            current_time('mysql')
        ));

        $results = array(
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'retried' => 0,
        );

        foreach ($due_items as $queue_item) {
            $result = $this->process_queue_item($queue_item);

            $results['processed']++;

            if ($result['status'] === 'success') {
                $results['succeeded']++;
            } elseif ($result['status'] === 'retry') {
                $results['retried']++;
            } else {
                $results['failed']++;
            }
        }

        if ($this->performance) {
            $this->performance->end_tracking('social_queue_processing');
        }

        if ($this->logger && $results['processed'] > 0) {
            $this->logger->info('Queue processing completed', $results);
        }

        return $results;
    }

    /**
     * Process single queue item
     *
     * @param object $queue_item Queue item from database
     * @return array Processing result
     */
    private function process_queue_item($queue_item) {
        $queue_id = $queue_item->id;
        $post_id = $queue_item->social_post_id;
        $attempts = $queue_item->attempts;

        if ($this->logger) {
            $this->logger->debug("Processing queue item {$queue_id}, post {$post_id}");
        }

        // Mark as processing
        $this->mark_processing($queue_id, true);

        // Get post details
        $post = $this->db->get_post($post_id);

        if (!$post) {
            $this->remove_from_queue($queue_id);
            return array('status' => 'error', 'message' => 'Post not found');
        }

        // Get account details
        $account = $this->db->get_account($post->account_id);

        if (!$account) {
            $this->remove_from_queue($queue_id);
            return array('status' => 'error', 'message' => 'Account not found');
        }

        // Publish the post
        $publish_result = $this->manager->publish_now(
            $post->content,
            array($post->platform),
            array(
                'media' => !empty($post->media_urls) ? json_decode($post->media_urls, true) : array(),
                'metadata' => !empty($post->metadata) ? json_decode($post->metadata, true) : array(),
            )
        );

        // Check result
        if (isset($publish_result[$post->platform]) && !is_wp_error($publish_result[$post->platform])) {
            // Success!
            $platform_post_id = $publish_result[$post->platform];

            // Update post
            $this->db->update_post($post_id, array(
                'status' => 'published',
                'platform_post_id' => $platform_post_id,
                'published_at' => current_time('mysql'),
            ));

            // Remove from queue
            $this->remove_from_queue($queue_id);

            if ($this->logger) {
                $this->logger->info("Post {$post_id} published successfully", array(
                    'platform' => $post->platform,
                    'platform_post_id' => $platform_post_id,
                ));
            }

            return array('status' => 'success', 'platform_post_id' => $platform_post_id);

        } else {
            // Failed - check if we should retry
            $error = isset($publish_result[$post->platform]) ? $publish_result[$post->platform] : new WP_Error('unknown', 'Unknown error');
            $error_message = is_wp_error($error) ? $error->get_error_message() : 'Unknown error';

            if ($this->logger) {
                $this->logger->warning("Post {$post_id} publish failed", array(
                    'attempts' => $attempts + 1,
                    'error' => $error_message,
                ));
            }

            $max_retries = get_option('ai_seo_social_max_retries', 3);

            if ($attempts + 1 >= $max_retries) {
                // Max retries reached - mark as failed
                $this->db->update_post_status($post_id, 'failed');
                $this->remove_from_queue($queue_id);

                if ($this->logger) {
                    $this->logger->error("Post {$post_id} failed after {$max_retries} attempts");
                }

                return array('status' => 'failed', 'message' => $error_message);

            } else {
                // Schedule retry
                $retry_delay = $this->calculate_retry_delay($attempts + 1);
                $next_retry = date('Y-m-d H:i:s', time() + $retry_delay);

                global $wpdb;
                $table = $wpdb->prefix . 'ai_seo_social_queue';

                $wpdb->update(
                    $table,
                    array(
                        'processing' => 0,
                        'attempts' => $attempts + 1,
                        'last_attempt' => current_time('mysql'),
                        'next_retry' => $next_retry,
                    ),
                    array('id' => $queue_id),
                    array('%d', '%d', '%s', '%s'),
                    array('%d')
                );

                if ($this->logger) {
                    $this->logger->info("Post {$post_id} scheduled for retry", array(
                        'next_retry' => $next_retry,
                        'delay_seconds' => $retry_delay,
                    ));
                }

                return array('status' => 'retry', 'next_retry' => $next_retry);
            }
        }
    }

    /**
     * Calculate retry delay with exponential backoff
     *
     * @param int $attempt Attempt number (1, 2, 3...)
     * @return int Delay in seconds
     */
    private function calculate_retry_delay($attempt) {
        // Exponential backoff: 2^attempt minutes
        // Attempt 1: 2 min, 2: 4 min, 3: 8 min
        $minutes = pow(2, $attempt);
        return $minutes * 60;
    }

    /**
     * Mark queue item as processing
     *
     * @param int $queue_id Queue ID
     * @param bool $processing Processing status
     */
    private function mark_processing($queue_id, $processing) {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_seo_social_queue';

        $wpdb->update(
            $table,
            array('processing' => $processing ? 1 : 0),
            array('id' => $queue_id),
            array('%d'),
            array('%d')
        );
    }

    /**
     * Remove item from queue
     *
     * @param int $queue_id Queue ID
     */
    private function remove_from_queue($queue_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_seo_social_queue';

        $wpdb->update(
            $table,
            array('processed_at' => current_time('mysql')),
            array('id' => $queue_id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Bulk schedule posts
     *
     * @param array $posts Array of post IDs and schedule times
     * @return array Results
     */
    public function bulk_schedule($posts) {
        $results = array(
            'scheduled' => 0,
            'failed' => 0,
            'errors' => array(),
        );

        foreach ($posts as $post_data) {
            $post_id = $post_data['post_id'];
            $scheduled_time = $post_data['scheduled_time'];
            $priority = isset($post_data['priority']) ? $post_data['priority'] : 5;

            $result = $this->schedule_post($post_id, $scheduled_time, $priority);

            if (is_wp_error($result)) {
                $results['failed']++;
                $results['errors'][] = array(
                    'post_id' => $post_id,
                    'error' => $result->get_error_message(),
                );
            } else {
                $results['scheduled']++;
            }
        }

        return $results;
    }

    /**
     * Cancel scheduled post
     *
     * @param int $post_id Social post ID
     * @return bool Success
     */
    public function cancel_scheduled_post($post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_seo_social_queue';

        // Remove from queue
        $wpdb->delete(
            $table,
            array('social_post_id' => $post_id, 'processed_at' => null),
            array('%d', '%s')
        );

        // Update post status
        $this->db->update_post_status($post_id, 'draft');

        if ($this->logger) {
            $this->logger->info("Scheduled post {$post_id} cancelled");
        }

        return true;
    }

    /**
     * Reschedule a post
     *
     * @param int $post_id Social post ID
     * @param string $new_time New schedule time
     * @return bool|WP_Error Success or error
     */
    public function reschedule_post($post_id, $new_time) {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_seo_social_queue';

        // Find queue item
        $queue_item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE social_post_id = %d
             AND processed_at IS NULL
             LIMIT 1",
            $post_id
        ));

        if (!$queue_item) {
            return new WP_Error('not_scheduled', 'Post is not currently scheduled');
        }

        // Update schedule time
        $wpdb->update(
            $table,
            array(
                'scheduled_for' => $new_time,
                'next_retry' => null,
                'attempts' => 0,
            ),
            array('id' => $queue_item->id),
            array('%s', '%s', '%d'),
            array('%d')
        );

        if ($this->logger) {
            $this->logger->info("Post {$post_id} rescheduled", array(
                'old_time' => $queue_item->scheduled_for,
                'new_time' => $new_time,
            ));
        }

        return true;
    }

    /**
     * Get queue statistics
     *
     * @return array Statistics
     */
    public function get_queue_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_seo_social_queue';

        $stats = array(
            'pending' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                 WHERE processed_at IS NULL
                 AND scheduled_for > %s",
                current_time('mysql')
            )),
            'overdue' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                 WHERE processed_at IS NULL
                 AND scheduled_for <= %s",
                current_time('mysql')
            )),
            'processing' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table}
                 WHERE processing = 1"
            ),
            'failed' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                 WHERE processed_at IS NULL
                 AND attempts >= %d",
                get_option('ai_seo_social_max_retries', 3)
            )),
            'completed_today' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table}
                 WHERE DATE(processed_at) = %s",
                current_time('Y-m-d')
            )),
        );

        return $stats;
    }

    /**
     * Get upcoming scheduled posts
     *
     * @param int $limit Limit
     * @return array Scheduled posts
     */
    public function get_upcoming_posts($limit = 10) {
        global $wpdb;
        $queue_table = $wpdb->prefix . 'ai_seo_social_queue';
        $posts_table = $wpdb->prefix . 'ai_seo_social_posts';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT q.*, p.*
             FROM {$queue_table} q
             INNER JOIN {$posts_table} p ON q.social_post_id = p.id
             WHERE q.processed_at IS NULL
             ORDER BY q.scheduled_for ASC
             LIMIT %d",
            $limit
        ));

        return $results;
    }

    /**
     * Clean up old processed queue items
     *
     * @param int $days Days to keep (default 30)
     * @return int Number of items deleted
     */
    public function cleanup_old_queue_items($days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'ai_seo_social_queue';

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table}
             WHERE processed_at IS NOT NULL
             AND processed_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));

        if ($this->logger && $deleted > 0) {
            $this->logger->info("Cleaned up {$deleted} old queue items");
        }

        return $deleted;
    }
}
