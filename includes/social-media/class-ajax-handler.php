<?php
/**
 * Social Media AJAX Handler
 *
 * Handles all AJAX requests for Social Media Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Social_AJAX_Handler {

    private static $instance = null;
    private $db;
    private $manager;
    private $content_generator;
    private $scheduler;
    private $analytics;
    private $logger;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = AI_SEO_Social_Database::get_instance();
        $this->manager = AI_SEO_Social_Media_Manager::get_instance();
        $this->content_generator = AI_SEO_Social_AI_Content_Generator::get_instance();
        $this->scheduler = AI_SEO_Social_Scheduler::get_instance();
        $this->analytics = AI_SEO_Social_Analytics::get_instance();

        if (class_exists('AI_SEO_Manager_Debug_Logger')) {
            $this->logger = AI_SEO_Manager_Debug_Logger::get_instance();
        }

        $this->init_hooks();
    }

    private function init_hooks() {
        // Account management
        add_action('wp_ajax_ai_seo_social_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_ai_seo_social_save_account', array($this, 'ajax_save_account'));
        add_action('wp_ajax_ai_seo_social_delete_account', array($this, 'ajax_delete_account'));
        add_action('wp_ajax_ai_seo_social_get_account', array($this, 'ajax_get_account'));

        // Content generation
        add_action('wp_ajax_ai_seo_social_generate_content', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_ai_seo_social_generate_variations', array($this, 'ajax_generate_variations'));

        // Publishing
        add_action('wp_ajax_ai_seo_social_publish_now', array($this, 'ajax_publish_now'));
        add_action('wp_ajax_ai_seo_social_schedule_post', array($this, 'ajax_schedule_post'));
        add_action('wp_ajax_ai_seo_social_save_draft', array($this, 'ajax_save_draft'));

        // Post management
        add_action('wp_ajax_ai_seo_social_delete_post', array($this, 'ajax_delete_post'));
        add_action('wp_ajax_ai_seo_social_cancel_scheduled', array($this, 'ajax_cancel_scheduled'));
        add_action('wp_ajax_ai_seo_social_reschedule_post', array($this, 'ajax_reschedule_post'));

        // Analytics
        add_action('wp_ajax_ai_seo_social_sync_analytics', array($this, 'ajax_sync_analytics'));
        add_action('wp_ajax_ai_seo_social_get_analytics', array($this, 'ajax_get_analytics'));

        // Dashboard
        add_action('wp_ajax_ai_seo_social_get_stats', array($this, 'ajax_get_stats'));
    }

    /**
     * Test platform connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('ai_seo_social_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $platform = sanitize_text_field($_POST['platform']);
        $credentials = $_POST['credentials']; // Will be sanitized in platform client

        // Create temporary account for testing
        $registry = AI_SEO_Social_Platform_Registry::get_instance();
        $client = $registry->get($platform);

        if (!$client) {
            wp_send_json_error(array('message' => "Platform {$platform} not found"));
        }

        // Test authentication with provided credentials
        $test_result = $client->test_credentials($credentials);

        if (is_wp_error($test_result)) {
            wp_send_json_error(array(
                'message' => $test_result->get_error_message(),
                'code' => $test_result->get_error_code()
            ));
        }

        wp_send_json_success(array(
            'message' => 'Connection successful!',
            'account_info' => $test_result
        ));
    }

    /**
     * Save account (create or update)
     */
    public function ajax_save_account() {
        check_ajax_referer('ai_seo_social_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $account_id = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
        $platform = sanitize_text_field($_POST['platform']);
        $account_name = sanitize_text_field($_POST['account_name']);
        $status = sanitize_text_field($_POST['status']);
        $credentials = $_POST['credentials'];

        // Sanitize credentials based on platform
        $credentials = $this->sanitize_credentials($credentials, $platform);

        $data = array(
            'platform' => $platform,
            'account_name' => $account_name,
            'credentials' => $credentials,
            'status' => $status,
        );

        if ($account_id > 0) {
            // Update existing
            $result = $this->db->update_account($account_id, $data);
            $message = 'Account updated successfully!';
        } else {
            // Create new
            $data['account_id'] = sanitize_text_field($_POST['platform_account_id']);
            $result = $this->db->create_account($data);
            $account_id = $result;
            $message = 'Account created successfully!';
        }

        if ($result) {
            wp_send_json_success(array(
                'message' => $message,
                'account_id' => $account_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to save account'));
        }
    }

    /**
     * Delete account
     */
    public function ajax_delete_account() {
        check_ajax_referer('ai_seo_social_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $account_id = (int)$_POST['account_id'];

        $result = $this->db->delete_account($account_id);

        if ($result) {
            wp_send_json_success(array('message' => 'Account deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete account'));
        }
    }

    /**
     * Get account details
     */
    public function ajax_get_account() {
        check_ajax_referer('ai_seo_social_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $account_id = (int)$_POST['account_id'];
        $account = $this->db->get_account($account_id);

        if ($account) {
            // Mask sensitive credentials
            if (!empty($account->credentials)) {
                $account->credentials = $this->mask_credentials($account->credentials);
            }

            wp_send_json_success(array('account' => $account));
        } else {
            wp_send_json_error(array('message' => 'Account not found'));
        }
    }

    /**
     * Generate AI content
     */
    public function ajax_generate_content() {
        check_ajax_referer('ai_seo_social_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $topic = sanitize_text_field($_POST['topic']);
        $platform = sanitize_text_field($_POST['platform']);
        $tone = sanitize_text_field($_POST['tone']);
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : 'general';

        $options = array(
            'tone' => $tone,
            'category' => $category,
            'include_hashtags' => isset($_POST['include_hashtags']) ? (bool)$_POST['include_hashtags'] : true,
            'include_emojis' => isset($_POST['include_emojis']) ? (bool)$_POST['include_emojis'] : true,
        );

        $content = $this->content_generator->generate_content($topic, $platform, $options);

        if (is_wp_error($content)) {
            wp_send_json_error(array('message' => $content->get_error_message()));
        }

        wp_send_json_success(array(
            'content' => $content,
            'message' => 'Content generated successfully!'
        ));
    }

    /**
     * Generate content variations
     */
    public function ajax_generate_variations() {
        check_ajax_referer('ai_seo_social_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $topic = sanitize_text_field($_POST['topic']);
        $platform = sanitize_text_field($_POST['platform']);
        $count = isset($_POST['count']) ? min(5, max(1, (int)$_POST['count'])) : 3;

        $options = array(
            'tone' => sanitize_text_field($_POST['tone']),
            'category' => sanitize_text_field($_POST['category']),
        );

        $variations = $this->content_generator->generate_variations($topic, $platform, $count, $options);

        if (is_wp_error($variations)) {
            wp_send_json_error(array('message' => $variations->get_error_message()));
        }

        wp_send_json_success(array(
            'variations' => $variations,
            'count' => count($variations)
        ));
    }

    /**
     * Publish now
     */
    public function ajax_publish_now() {
        check_ajax_referer('ai_seo_social_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $content = wp_kses_post($_POST['content']);
        $platforms = array_map('sanitize_text_field', $_POST['platforms']);
        $media = isset($_POST['media']) ? array_map('esc_url_raw', $_POST['media']) : array();

        $options = array(
            'media' => $media,
        );

        $results = $this->manager->publish_now($content, $platforms, $options);

        $success = array();
        $errors = array();

        foreach ($results as $platform => $result) {
            if (is_wp_error($result)) {
                $errors[$platform] = $result->get_error_message();
            } else {
                $success[$platform] = $result;
            }
        }

        if (empty($errors)) {
            wp_send_json_success(array(
                'message' => 'Published successfully to all platforms!',
                'results' => $success
            ));
        } elseif (empty($success)) {
            wp_send_json_error(array(
                'message' => 'Failed to publish to any platform',
                'errors' => $errors
            ));
        } else {
            wp_send_json_success(array(
                'message' => 'Published to some platforms with errors',
                'results' => $success,
                'errors' => $errors
            ));
        }
    }

    /**
     * Schedule post
     */
    public function ajax_schedule_post() {
        check_ajax_referer('ai_seo_social_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $content = wp_kses_post($_POST['content']);
        $platforms = array_map('sanitize_text_field', $_POST['platforms']);
        $scheduled_time = sanitize_text_field($_POST['scheduled_time']);
        $media = isset($_POST['media']) ? array_map('esc_url_raw', $_POST['media']) : array();

        $options = array(
            'media' => $media,
        );

        $results = $this->manager->schedule_post($content, $scheduled_time, $platforms, $options);

        if (is_wp_error($results)) {
            wp_send_json_error(array('message' => $results->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => 'Post scheduled successfully!',
            'results' => $results
        ));
    }

    /**
     * Save draft
     */
    public function ajax_save_draft() {
        check_ajax_referer('ai_seo_social_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $content = wp_kses_post($_POST['content']);
        $platforms = array_map('sanitize_text_field', $_POST['platforms']);

        // Create draft posts for each platform
        $post_ids = array();

        foreach ($platforms as $platform) {
            $account = $this->db->get_account_by_platform($platform);
            if (!$account) continue;

            $post_id = $this->db->create_post(array(
                'account_id' => $account->id,
                'platform' => $platform,
                'content' => $content,
                'status' => 'draft',
            ));

            if ($post_id) {
                $post_ids[$platform] = $post_id;
            }
        }

        if (empty($post_ids)) {
            wp_send_json_error(array('message' => 'Failed to save draft'));
        }

        wp_send_json_success(array(
            'message' => 'Draft saved successfully!',
            'post_ids' => $post_ids
        ));
    }

    /**
     * Get statistics
     */
    public function ajax_get_stats() {
        check_ajax_referer('ai_seo_social_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $stats = array(
            'manager' => $this->manager->get_stats(),
            'queue' => $this->scheduler->get_queue_stats(),
            'analytics' => $this->analytics->get_stats(),
        );

        wp_send_json_success($stats);
    }

    /**
     * Sanitize credentials based on platform
     */
    private function sanitize_credentials($credentials, $platform) {
        $sanitized = array();

        foreach ($credentials as $key => $value) {
            $sanitized[sanitize_key($key)] = sanitize_text_field($value);
        }

        return $sanitized;
    }

    /**
     * Mask sensitive credentials for display
     */
    private function mask_credentials($credentials) {
        if (!is_array($credentials)) {
            return $credentials;
        }

        $masked = array();
        $sensitive_keys = array('token', 'secret', 'password', 'key');

        foreach ($credentials as $key => $value) {
            $is_sensitive = false;
            foreach ($sensitive_keys as $sensitive) {
                if (stripos($key, $sensitive) !== false) {
                    $is_sensitive = true;
                    break;
                }
            }

            if ($is_sensitive && !empty($value)) {
                $masked[$key] = substr($value, 0, 4) . '...' . substr($value, -4);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }
}
