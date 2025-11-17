<?php
/**
 * Base Platform Client (Abstract Class)
 * Základná trieda pre všetky social media platform clients
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class AI_SEO_Social_Platform_Client {

    // Platform properties
    protected $platform_name;
    protected $account_id;
    protected $credentials = array();
    protected $is_authenticated = false;

    // Services
    protected $db;
    protected $logger;
    protected $performance;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = AI_SEO_Social_Database::get_instance();

        // Initialize debug tools if available
        if (class_exists('AI_SEO_Manager_Debug_Logger')) {
            $this->logger = AI_SEO_Manager_Debug_Logger::get_instance();
            $this->performance = AI_SEO_Manager_Performance_Monitor::get_instance();
        }

        // Try to load credentials
        $this->load_credentials();
    }

    // =================================================================
    // ABSTRACT METHODS - Must be implemented by each platform
    // =================================================================

    /**
     * Authenticate with platform API
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    abstract public function authenticate();

    /**
     * Publish content to platform
     * @param string $content Post content
     * @param array $media Media URLs (images, videos)
     * @return string|WP_Error Platform post ID on success, WP_Error on failure
     */
    abstract public function publish($content, $media = array());

    /**
     * Get analytics for a post
     * @param string $post_id Platform post ID
     * @param array $date_range Date range for analytics
     * @return array|WP_Error Analytics data or WP_Error
     */
    abstract public function get_analytics($post_id, $date_range = array());

    /**
     * Validate content before publishing
     * @param string $content Content to validate
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    abstract public function validate_content($content);

    /**
     * Get platform rate limits
     * @return array Rate limits configuration
     */
    abstract public function get_rate_limits();

    // =================================================================
    // COMMON METHODS - Available to all platforms
    // =================================================================

    /**
     * Load credentials from database
     */
    protected function load_credentials() {
        $account = $this->db->get_account_by_platform($this->platform_name);

        if ($account && $account->status === 'active') {
            $this->account_id = $account->id;
            $this->credentials = $account->credentials;

            // Try to authenticate
            $auth_result = $this->authenticate();

            if (!is_wp_error($auth_result)) {
                $this->is_authenticated = true;
            }
        }
    }

    /**
     * Check if authenticated
     */
    public function is_authenticated() {
        return $this->is_authenticated;
    }

    /**
     * Refresh authentication token
     */
    public function refresh_token() {
        // Default implementation - override if needed
        return $this->authenticate();
    }

    /**
     * Handle API error
     */
    protected function handle_error($error, $context = array()) {
        $error_message = is_wp_error($error) ? $error->get_error_message() : $error;

        if ($this->logger) {
            $this->logger->error("{$this->platform_name} API error", array_merge($context, array(
                'error' => $error_message,
            )));
        }

        // Update account with error
        if ($this->account_id) {
            $this->db->update_account($this->account_id, array(
                'error_message' => $error_message,
                'last_sync' => current_time('mysql'),
            ));
        }

        return is_wp_error($error) ? $error : new WP_Error('api_error', $error_message, $context);
    }

    /**
     * Log action
     */
    protected function log_action($action, $data = array()) {
        if ($this->logger) {
            $this->logger->info("{$this->platform_name}: {$action}", $data);
        }
    }

    /**
     * Get platform name
     */
    public function get_platform_name() {
        return $this->platform_name;
    }

    /**
     * Get platform capabilities
     */
    public function get_capabilities() {
        return array(
            'text' => true,
            'images' => false,
            'videos' => false,
            'links' => true,
            'hashtags' => true,
            'mentions' => false,
            'scheduling' => true,
            'analytics' => false,
            'max_text_length' => 280,
            'max_images' => 0,
            'max_videos' => 0,
        );
    }

    /**
     * Format content for platform
     */
    protected function format_content($content, $max_length = null) {
        // Strip tags
        $formatted = wp_strip_all_tags($content);

        // Trim to max length if specified
        if ($max_length && strlen($formatted) > $max_length) {
            $formatted = substr($formatted, 0, $max_length - 3) . '...';
        }

        // Normalize whitespace
        $formatted = preg_replace('/\s+/', ' ', $formatted);
        $formatted = trim($formatted);

        return $formatted;
    }

    /**
     * Detect media type from URL
     */
    protected function detect_media_type($url) {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

        $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $video_extensions = array('mp4', 'mov', 'avi', 'mkv', 'webm');

        if (in_array($extension, $image_extensions)) {
            return 'image';
        } elseif (in_array($extension, $video_extensions)) {
            return 'video';
        }

        // Try to detect from content type
        $response = wp_remote_head($url);
        if (!is_wp_error($response)) {
            $content_type = wp_remote_retrieve_header($response, 'content-type');

            if (strpos($content_type, 'image') !== false) {
                return 'image';
            } elseif (strpos($content_type, 'video') !== false) {
                return 'video';
            }
        }

        return 'unknown';
    }

    /**
     * Download remote media to temp file
     */
    protected function download_media($url) {
        $tmp_file = download_url($url);

        if (is_wp_error($tmp_file)) {
            return $tmp_file;
        }

        return $tmp_file;
    }

    /**
     * Extract hashtags from content
     */
    protected function extract_hashtags($content) {
        preg_match_all('/#(\w+)/', $content, $matches);
        return $matches[1] ?? array();
    }

    /**
     * Extract mentions from content
     */
    protected function extract_mentions($content) {
        preg_match_all('/@(\w+)/', $content, $matches);
        return $matches[1] ?? array();
    }

    /**
     * Make HTTP request with error handling
     */
    protected function make_request($url, $args = array(), $method = 'POST') {
        $defaults = array(
            'timeout' => 60,
            'headers' => array(),
        );

        $args = wp_parse_args($args, $defaults);

        if ($this->performance) {
            $this->performance->start('api_request_' . $this->platform_name);
        }

        $start_time = microtime(true);

        if ($method === 'POST') {
            $response = wp_remote_post($url, $args);
        } elseif ($method === 'GET') {
            $response = wp_remote_get($url, $args);
        } else {
            $args['method'] = $method;
            $response = wp_remote_request($url, $args);
        }

        $duration = microtime(true) - $start_time;

        if ($this->performance) {
            $success = !is_wp_error($response) && wp_remote_retrieve_response_code($response) < 400;
            $error = is_wp_error($response) ? $response->get_error_message() : null;

            $this->performance->track_api_call(
                $this->platform_name,
                $method . ' ' . parse_url($url, PHP_URL_PATH),
                $duration,
                $success,
                $error
            );

            $this->performance->stop('api_request_' . $this->platform_name);
        }

        return $response;
    }

    /**
     * Parse JSON response
     */
    protected function parse_json_response($response, $expected_code = 200) {
        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code !== $expected_code) {
            $body = wp_remote_retrieve_body($response);
            return new WP_Error(
                'api_error',
                "API returned code {$code}",
                array('body' => $body, 'code' => $code)
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Failed to parse JSON response', array('body' => $body));
        }

        return $data;
    }

    /**
     * Save credentials to database
     */
    protected function save_credentials($credentials) {
        $this->credentials = $credentials;

        if ($this->account_id) {
            return $this->db->update_account($this->account_id, array(
                'credentials' => $credentials,
                'last_sync' => current_time('mysql'),
            ));
        }

        return false;
    }

    /**
     * Get credential value
     */
    protected function get_credential($key, $default = null) {
        return isset($this->credentials[$key]) ? $this->credentials[$key] : $default;
    }

    /**
     * Set credential value
     */
    protected function set_credential($key, $value) {
        $this->credentials[$key] = $value;
        return $this->save_credentials($this->credentials);
    }
}
