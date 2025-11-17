<?php
/**
 * Social Media Manager
 * Hlavný orchestrator pre všetky social media operácie
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Social_Media_Manager {

    private static $instance = null;
    private $registry;
    private $rate_limiter;
    private $db;
    private $ai_manager;
    private $logger;
    private $performance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = AI_SEO_Social_Database::get_instance();
        $this->registry = AI_SEO_Social_Platform_Registry::get_instance();
        $this->rate_limiter = AI_SEO_Social_Rate_Limiter::get_instance();

        // Get existing AI Manager
        if (class_exists('AI_SEO_Manager_AI_Manager')) {
            $this->ai_manager = AI_SEO_Manager_AI_Manager::get_instance();
        }

        // Initialize debug tools if available
        if (class_exists('AI_SEO_Manager_Debug_Logger')) {
            $this->logger = AI_SEO_Manager_Debug_Logger::get_instance();
            $this->performance = AI_SEO_Manager_Performance_Monitor::get_instance();
        }

        $this->init_hooks();
        $this->register_platforms();

        if ($this->logger) {
            $this->logger->info('Social Media Manager initialized');
        }
    }

    /**
     * Inicializácia WordPress hookov
     */
    private function init_hooks() {
        // Hook pre publikovanie blogov
        add_action('publish_post', array($this, 'auto_share_blog_post'), 10, 2);

        // Cron job pre scheduled posts
        add_action('ai_seo_social_process_queue', array($this, 'process_scheduled_posts'));

        // Fire init hook
        do_action('ai_seo_social_init');
    }

    /**
     * Registrácia všetkých dostupných platforiem
     */
    private function register_platforms() {
        // Register Telegram if available
        if (class_exists('AI_SEO_Social_Telegram_Client')) {
            $telegram = new AI_SEO_Social_Telegram_Client();
            $this->registry->register('telegram', $telegram);
        }

        // Register Facebook if available
        if (class_exists('AI_SEO_Social_Facebook_Client')) {
            $facebook = new AI_SEO_Social_Facebook_Client();
            $this->registry->register('facebook', $facebook);
        }

        // Register Instagram if available
        if (class_exists('AI_SEO_Social_Instagram_Client')) {
            $instagram = new AI_SEO_Social_Instagram_Client();
            $this->registry->register('instagram', $instagram);
        }

        // Add more platforms as they are implemented...

        if ($this->logger) {
            $stats = $this->registry->get_stats();
            $this->logger->info('Platforms registered', $stats);
        }
    }

    /**
     * Okamžité publikovanie na platformy
     */
    public function publish_now($content, $platforms = array(), $options = array()) {
        if ($this->logger) {
            $this->logger->info('Publishing post immediately', array(
                'platforms' => $platforms,
                'content_length' => strlen($content),
            ));
        }

        if ($this->performance) {
            $this->performance->start('publish_now');
        }

        $results = array();

        foreach ($platforms as $platform) {
            $result = $this->publish_to_platform($platform, $content, $options);
            $results[$platform] = $result;
        }

        if ($this->performance) {
            $this->performance->stop('publish_now');
        }

        do_action('ai_seo_social_published', $results);

        return $results;
    }

    /**
     * Publikovanie na konkrétnu platformu
     */
    private function publish_to_platform($platform, $content, $options = array()) {
        // Check if platform is registered
        if (!$this->registry->is_platform_available($platform)) {
            return new WP_Error('platform_not_available', "Platform {$platform} is not registered");
        }

        // Check rate limits
        if (!$this->rate_limiter->check_limit($platform, 'publish')) {
            $wait_time = $this->rate_limiter->should_wait($platform, 'publish');

            if ($this->logger) {
                $this->logger->warning('Rate limit exceeded', array(
                    'platform' => $platform,
                    'wait_seconds' => $wait_time,
                ));
            }

            return new WP_Error('rate_limit_exceeded', "Rate limit exceeded for {$platform}. Wait {$wait_time} seconds.");
        }

        // Get platform client
        $client = $this->registry->get($platform);

        if (!$client) {
            return new WP_Error('client_not_found', "Client for {$platform} not found");
        }

        // Get account for this platform
        $account = $this->db->get_account_by_platform($platform);

        if (!$account) {
            return new WP_Error('account_not_configured', "{$platform} account not configured");
        }

        do_action('ai_seo_social_before_publish', $account->id, $platform, $content);

        // Validate content
        $validation = $client->validate_content($content);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Extract media if provided
        $media = isset($options['media']) ? $options['media'] : array();

        // Publish
        $result = $client->publish($content, $media);

        // Track API call
        $this->rate_limiter->increment($platform, 'publish');

        if (is_wp_error($result)) {
            if ($this->logger) {
                $this->logger->error('Publishing failed', array(
                    'platform' => $platform,
                    'error' => $result->get_error_message(),
                ));
            }

            // Create failed post record
            $this->db->create_post(array(
                'account_id' => $account->id,
                'platform' => $platform,
                'content' => $content,
                'media_urls' => $media,
                'status' => 'failed',
                'error_message' => $result->get_error_message(),
                'created_by' => 'manual',
            ));

            return $result;
        }

        // Success - create post record
        $platform_post_id = $result;
        $social_post_id = $this->db->create_post(array(
            'account_id' => $account->id,
            'platform' => $platform,
            'content' => $content,
            'media_urls' => $media,
            'platform_post_id' => $platform_post_id,
            'status' => 'published',
            'published_at' => current_time('mysql'),
            'created_by' => isset($options['created_by']) ? $options['created_by'] : 'manual',
            'tone' => isset($options['tone']) ? $options['tone'] : 'professional',
            'category' => isset($options['category']) ? $options['category'] : 'general',
        ));

        if ($this->logger) {
            $this->logger->info('Post published successfully', array(
                'platform' => $platform,
                'social_post_id' => $social_post_id,
                'platform_post_id' => $platform_post_id,
            ));
        }

        do_action('ai_seo_social_after_publish', $social_post_id, $platform, $platform_post_id);

        return array(
            'success' => true,
            'social_post_id' => $social_post_id,
            'platform_post_id' => $platform_post_id,
        );
    }

    /**
     * Naplánovanie postu
     */
    public function schedule_post($content, $schedule_time, $platforms = array(), $options = array()) {
        if ($this->logger) {
            $this->logger->info('Scheduling post', array(
                'platforms' => $platforms,
                'schedule_time' => $schedule_time,
            ));
        }

        $scheduled_posts = array();

        foreach ($platforms as $platform) {
            // Get account
            $account = $this->db->get_account_by_platform($platform);

            if (!$account) {
                continue;
            }

            // Create post record
            $social_post_id = $this->db->create_post(array(
                'account_id' => $account->id,
                'platform' => $platform,
                'content' => $content,
                'media_urls' => isset($options['media']) ? $options['media'] : array(),
                'status' => 'scheduled',
                'scheduled_at' => $schedule_time,
                'created_by' => isset($options['created_by']) ? $options['created_by'] : 'manual',
                'tone' => isset($options['tone']) ? $options['tone'] : 'professional',
                'category' => isset($options['category']) ? $options['category'] : 'general',
            ));

            $scheduled_posts[$platform] = $social_post_id;
        }

        if ($this->logger) {
            $this->logger->info('Posts scheduled', array(
                'count' => count($scheduled_posts),
                'scheduled_posts' => $scheduled_posts,
            ));
        }

        return $scheduled_posts;
    }

    /**
     * Spracovanie scheduled posts (cron job)
     */
    public function process_scheduled_posts() {
        if ($this->logger) {
            $this->logger->info('Processing scheduled posts queue');
        }

        // Get due posts
        $due_posts = $this->db->get_posts(array(
            'status' => 'scheduled',
            'limit' => 10,
        ));

        if (empty($due_posts)) {
            return;
        }

        $processed = 0;

        foreach ($due_posts as $post) {
            // Check if due
            if (strtotime($post->scheduled_at) > time()) {
                continue;
            }

            // Publish
            $account = $this->db->get_account($post->account_id);
            $client = $this->registry->get($post->platform);

            if (!$client || !$account) {
                continue;
            }

            $media = maybe_unserialize($post->media_urls);
            $result = $client->publish($post->content, $media);

            if (is_wp_error($result)) {
                // Failed - increment retry
                $retry_count = $post->retry_count + 1;

                if ($retry_count >= $post->max_retries) {
                    // Max retries reached
                    $this->db->update_post($post->id, array(
                        'status' => 'failed',
                        'error_message' => $result->get_error_message(),
                        'retry_count' => $retry_count,
                    ));
                } else {
                    // Retry later
                    $this->db->update_post($post->id, array(
                        'retry_count' => $retry_count,
                        'error_message' => $result->get_error_message(),
                    ));
                }
            } else {
                // Success
                $this->db->update_post($post->id, array(
                    'status' => 'published',
                    'published_at' => current_time('mysql'),
                    'platform_post_id' => $result,
                ));

                $processed++;
            }

            // Track API call
            $this->rate_limiter->increment($post->platform, 'publish');
        }

        if ($this->logger) {
            $this->logger->info('Scheduled posts processed', array(
                'processed' => $processed,
                'total_due' => count($due_posts),
            ));
        }
    }

    /**
     * Automatické zdieľanie blog postu
     */
    public function auto_share_blog_post($post_id, $post) {
        // Check if auto-sharing is enabled
        if (!get_option('ai_seo_social_auto_share_enabled', false)) {
            return;
        }

        // Get enabled platforms for auto-sharing
        $enabled_platforms = get_option('ai_seo_social_auto_share_platforms', array());

        if (empty($enabled_platforms)) {
            return;
        }

        // Generate content from blog post
        $content = $this->generate_blog_share_content($post);

        // Get featured image if available
        $media = array();
        if (has_post_thumbnail($post_id)) {
            $media[] = get_the_post_thumbnail_url($post_id, 'large');
        }

        // Publish or schedule
        $this->publish_now($content, $enabled_platforms, array(
            'media' => $media,
            'created_by' => 'auto_blog_share',
            'post_id' => $post_id,
        ));
    }

    /**
     * Generovanie obsahu pre blog share
     */
    private function generate_blog_share_content($post) {
        $title = $post->post_title;
        $excerpt = has_excerpt($post->ID) ? get_the_excerpt($post) : wp_trim_words($post->post_content, 30);
        $link = get_permalink($post->ID);

        $content = "📝 {$title}\n\n{$excerpt}\n\n👉 {$link}";

        return apply_filters('ai_seo_social_blog_share_content', $content, $post);
    }

    /**
     * Získanie štatistík
     */
    public function get_stats() {
        $db_stats = $this->db->get_stats_summary();
        $registry_stats = $this->registry->get_stats();
        $rate_stats = $this->rate_limiter->get_stats();

        return array(
            'database' => $db_stats,
            'platforms' => $registry_stats,
            'rate_limits' => $rate_stats,
        );
    }

    /**
     * Get platform client (for advanced usage)
     */
    public function get_platform_client($platform) {
        return $this->registry->get($platform);
    }

    /**
     * Check if platform is available
     */
    public function is_platform_available($platform) {
        return $this->registry->is_platform_available($platform);
    }
}
