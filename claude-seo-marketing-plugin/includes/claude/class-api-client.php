<?php
/**
 * Claude API client.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/claude
 */

/**
 * Handles communication with Claude API.
 */
class Claude_SEO_API_Client {

    /**
     * API base URL.
     */
    const API_BASE_URL = 'https://api.anthropic.com/v1';

    /**
     * API version.
     */
    const API_VERSION = '2023-06-01';

    /**
     * Max retries for failed requests.
     */
    const MAX_RETRIES = 5;

    /**
     * Create a message.
     *
     * @param array $params Message parameters.
     * @return array|WP_Error Response or error.
     */
    public function create_message($params) {
        // Check rate limit
        if (!Claude_SEO_Rate_Limiter::allow_request()) {
            $retry_after = Claude_SEO_Rate_Limiter::get_retry_after();
            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(__('Rate limit exceeded. Try again in %d seconds.', 'claude-seo'), $retry_after),
                array('retry_after' => $retry_after)
            );
        }

        // Check cache first
        $cache_key = $this->get_cache_key($params);
        $cached = Claude_SEO_Claude_Cache_Manager::get_cached_response($cache_key, $params);
        if ($cached !== false) {
            Claude_SEO_Logger::debug('Using cached Claude response');
            return $cached;
        }

        // Make API request with retry logic
        $response = $this->call_with_retry('/messages', $params);

        if (is_wp_error($response)) {
            return $response;
        }

        // Track usage
        Claude_SEO_Cost_Tracker::track_usage($response);

        // Cache response
        $cache_duration = isset($params['cache_duration']) ? $params['cache_duration'] : DAY_IN_SECONDS;
        Claude_SEO_Claude_Cache_Manager::cache_response($cache_key, $params, $response, $cache_duration);

        return $response;
    }

    /**
     * Call API with exponential backoff retry logic.
     *
     * @param string $endpoint API endpoint.
     * @param array  $body     Request body.
     * @param int    $attempt  Current attempt number.
     * @return array|WP_Error Response or error.
     */
    private function call_with_retry($endpoint, $body, $attempt = 1) {
        $api_key = Claude_SEO_Encryption::get_api_key();

        if (empty($api_key)) {
            return new WP_Error(
                'no_api_key',
                __('Claude API key not configured. Please add your API key in settings.', 'claude-seo')
            );
        }

        $url = self::API_BASE_URL . $endpoint;

        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => self::API_VERSION
            ),
            'body' => wp_json_encode($body)
        );

        $response = wp_remote_post($url, $args);

        // Handle errors
        if (is_wp_error($response)) {
            return $this->handle_request_error($response, $endpoint, $body, $attempt);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        // Success
        if ($status_code === 200) {
            return $data;
        }

        // Handle API errors
        return $this->handle_api_error($status_code, $data, $endpoint, $body, $attempt);
    }

    /**
     * Handle request errors with retry logic.
     *
     * @param WP_Error $error    The error.
     * @param string   $endpoint API endpoint.
     * @param array    $body     Request body.
     * @param int      $attempt  Current attempt.
     * @return array|WP_Error Response or error.
     */
    private function handle_request_error($error, $endpoint, $body, $attempt) {
        Claude_SEO_Logger::error('Claude API request error', array(
            'error' => $error->get_error_message(),
            'attempt' => $attempt
        ));

        if ($attempt >= self::MAX_RETRIES) {
            return $error;
        }

        // Exponential backoff with jitter
        $delay = $this->calculate_backoff_delay($attempt);
        sleep($delay);

        return $this->call_with_retry($endpoint, $body, $attempt + 1);
    }

    /**
     * Handle API error responses.
     *
     * @param int    $status_code HTTP status code.
     * @param array  $data        Response data.
     * @param string $endpoint    API endpoint.
     * @param array  $body        Request body.
     * @param int    $attempt     Current attempt.
     * @return array|WP_Error Response or error.
     */
    private function handle_api_error($status_code, $data, $endpoint, $body, $attempt) {
        $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
        $error_type = isset($data['error']['type']) ? $data['error']['type'] : 'unknown_error';

        Claude_SEO_Logger::error('Claude API error', array(
            'status_code' => $status_code,
            'error_type' => $error_type,
            'error_message' => $error_message,
            'attempt' => $attempt
        ));

        // Don't retry client errors (400, 401, 403, 404)
        if ($status_code >= 400 && $status_code < 500 && $status_code !== 429) {
            return new WP_Error(
                $error_type,
                sprintf(__('Claude API error: %s', 'claude-seo'), $error_message),
                array('status_code' => $status_code)
            );
        }

        // Retry server errors (500+) and rate limits (429)
        if ($attempt >= self::MAX_RETRIES) {
            return new WP_Error(
                $error_type,
                sprintf(__('Claude API error after %d attempts: %s', 'claude-seo'), $attempt, $error_message),
                array('status_code' => $status_code)
            );
        }

        // Calculate delay (longer for rate limits)
        $delay = $status_code === 429
            ? $this->calculate_backoff_delay($attempt) * 2
            : $this->calculate_backoff_delay($attempt);

        sleep($delay);

        return $this->call_with_retry($endpoint, $body, $attempt + 1);
    }

    /**
     * Calculate exponential backoff delay with jitter.
     *
     * @param int $attempt Current attempt number.
     * @return int Delay in seconds.
     */
    private function calculate_backoff_delay($attempt) {
        // Exponential backoff: 1s, 2s, 4s, 8s, 16s (max 60s)
        $base_delay = min(pow(2, $attempt - 1), 60);

        // Add 30% jitter to prevent thundering herd
        $jitter = $base_delay * 0.3 * (mt_rand() / mt_getrandmax());

        return (int) ($base_delay + $jitter);
    }

    /**
     * Quick generate text (simplified interface).
     *
     * @param string $prompt The prompt.
     * @param string $model  Model to use.
     * @param int    $max_tokens Maximum tokens.
     * @return string|WP_Error Generated text or error.
     */
    public function quick_generate($prompt, $model = null, $max_tokens = 1024) {
        if ($model === null) {
            $settings = get_option('claude_seo_settings', array());
            $model = isset($settings['claude_model_default'])
                ? $settings['claude_model_default']
                : 'claude-sonnet-4-5-20250929';
        }

        $params = array(
            'model' => $model,
            'max_tokens' => $max_tokens,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'system' => Claude_SEO_Prompt_Templates::get_system_prompt()
        );

        $response = $this->create_message($params);

        if (is_wp_error($response)) {
            return $response;
        }

        if (!isset($response['content'][0]['text'])) {
            return new WP_Error(
                'invalid_response',
                __('Invalid response from Claude API', 'claude-seo')
            );
        }

        return $response['content'][0]['text'];
    }

    /**
     * Generate content with template.
     *
     * @param string $template Template name.
     * @param array  $args     Template arguments.
     * @param string $model    Model to use.
     * @return string|WP_Error Generated content or error.
     */
    public function generate_with_template($template, $args, $model = null) {
        $prompt = '';

        switch ($template) {
            case 'blog_post':
                $prompt = Claude_SEO_Prompt_Templates::blog_post($args);
                $max_tokens = 4096;
                break;

            case 'meta_title':
                $prompt = Claude_SEO_Prompt_Templates::meta_title($args['content'], $args['keyword']);
                $max_tokens = 256;
                $model = $model ?? 'claude-haiku-4-5-20250930'; // Use Haiku for simple tasks
                break;

            case 'meta_description':
                $prompt = Claude_SEO_Prompt_Templates::meta_description($args['content'], $args['keyword']);
                $max_tokens = 256;
                $model = $model ?? 'claude-haiku-4-5-20250930';
                break;

            case 'image_alt':
                $prompt = Claude_SEO_Prompt_Templates::image_alt_text(
                    $args['filename'],
                    $args['context'] ?? '',
                    $args['post_title'] ?? ''
                );
                $max_tokens = 128;
                $model = $model ?? 'claude-haiku-4-5-20250930';
                break;

            case 'internal_links':
                $prompt = Claude_SEO_Prompt_Templates::internal_linking($args['content'], $args['posts']);
                $max_tokens = 1024;
                break;

            case 'faq_schema':
                $prompt = Claude_SEO_Prompt_Templates::faq_schema($args['content']);
                $max_tokens = 2048;
                break;

            case 'improvements':
                $prompt = Claude_SEO_Prompt_Templates::content_improvements(
                    $args['content'],
                    $args['keyword'],
                    $args['seo_score']
                );
                $max_tokens = 1024;
                break;

            case 'topic_ideas':
                $prompt = Claude_SEO_Prompt_Templates::topic_ideas(
                    $args['niche'],
                    $args['count'] ?? 10,
                    $args['audience'] ?? ''
                );
                $max_tokens = 2048;
                break;

            default:
                return new WP_Error('invalid_template', __('Invalid template specified', 'claude-seo'));
        }

        return $this->quick_generate($prompt, $model, $max_tokens);
    }

    /**
     * Get cache key for parameters.
     *
     * @param array $params Parameters.
     * @return string Cache key.
     */
    private function get_cache_key($params) {
        // Remove varying parameters for consistent caching
        $cache_params = $params;
        unset($cache_params['cache_duration']);

        return md5(wp_json_encode($cache_params));
    }

    /**
     * Test API connection.
     *
     * @return bool|WP_Error True if successful, error otherwise.
     */
    public function test_connection() {
        $response = $this->quick_generate('Test', 'claude-haiku-4-5-20250930', 10);

        if (is_wp_error($response)) {
            return $response;
        }

        return true;
    }
}
