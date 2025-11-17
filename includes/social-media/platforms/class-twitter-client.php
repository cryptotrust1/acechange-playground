<?php
/**
 * Twitter/X Platform Client
 * API v2 - OAuth 2.0 PKCE
 *
 * API Documentation: https://developer.twitter.com/en/docs/twitter-api
 *
 * Requirements:
 * - Twitter Developer Account
 * - App created in Developer Portal
 * - API Key + API Secret
 * - OAuth 2.0 tokens (Access Token + Refresh Token)
 * - Permissions: tweet.read, tweet.write, users.read, offline.access
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Social_Twitter_Client extends AI_SEO_Social_Platform_Client {

    protected $platform_name = 'twitter';
    private $api_key;
    private $api_secret;
    private $access_token;
    private $refresh_token;
    private $api_url = 'https://api.twitter.com/2';
    private $upload_url = 'https://upload.twitter.com/1.1';

    /**
     * Authenticate with Twitter API v2
     */
    public function authenticate() {
        $this->api_key = $this->get_credential('api_key');
        $this->api_secret = $this->get_credential('api_secret');
        $this->access_token = $this->get_credential('access_token');
        $this->refresh_token = $this->get_credential('refresh_token');

        if (empty($this->api_key) || empty($this->api_secret)) {
            return new WP_Error('no_credentials', 'Twitter API credentials not configured');
        }

        if (empty($this->access_token)) {
            return new WP_Error('no_access_token', 'Twitter not connected. Please complete OAuth 2.0 flow.');
        }

        // Verify credentials
        $verify = $this->verify_credentials();

        if (is_wp_error($verify)) {
            // Try to refresh token
            if (!empty($this->refresh_token)) {
                $refreshed = $this->refresh_access_token();
                if (!is_wp_error($refreshed)) {
                    return $this->authenticate(); // Retry
                }
            }
            return $verify;
        }

        $this->log_action('authenticated', array('user_id' => $verify['id'] ?? 'unknown'));

        return true;
    }

    /**
     * Verify credentials
     */
    private function verify_credentials() {
        $url = "{$this->api_url}/users/me";

        $response = $this->make_authenticated_request($url, array(), 'GET');
        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $data;
        }

        if (!isset($data['data']['id'])) {
            return new WP_Error('invalid_credentials', 'Invalid Twitter credentials');
        }

        return $data['data'];
    }

    /**
     * Refresh access token using refresh token
     */
    private function refresh_access_token() {
        $url = 'https://api.twitter.com/2/oauth2/token';

        $auth = base64_encode($this->api_key . ':' . $this->api_secret);

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . $auth,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refresh_token,
            ),
        ));

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $data;
        }

        if (isset($data['access_token'])) {
            $this->access_token = $data['access_token'];
            $this->set_credential('access_token', $data['access_token']);

            if (isset($data['refresh_token'])) {
                $this->refresh_token = $data['refresh_token'];
                $this->set_credential('refresh_token', $data['refresh_token']);
            }

            $this->log_action('token refreshed');
            return true;
        }

        return new WP_Error('token_refresh_failed', 'Failed to refresh access token');
    }

    /**
     * Publish content to Twitter
     */
    public function publish($content, $media = array()) {
        if (!$this->is_authenticated()) {
            $auth = $this->authenticate();
            if (is_wp_error($auth)) {
                return $auth;
            }
        }

        $this->log_action('publishing', array(
            'content_length' => strlen($content),
            'media_count' => count($media),
        ));

        // Upload media first if provided
        $media_ids = array();
        if (!empty($media)) {
            foreach ($media as $media_url) {
                $media_id = $this->upload_media($media_url);
                if (!is_wp_error($media_id)) {
                    $media_ids[] = $media_id;
                }
            }
        }

        // Create tweet
        return $this->create_tweet($content, $media_ids);
    }

    /**
     * Create a tweet
     */
    private function create_tweet($text, $media_ids = array()) {
        $url = "{$this->api_url}/tweets";

        $tweet_data = array(
            'text' => $text,
        );

        if (!empty($media_ids)) {
            $tweet_data['media'] = array(
                'media_ids' => $media_ids,
            );
        }

        $response = $this->make_authenticated_request($url, array(
            'body' => json_encode($tweet_data),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
        ), 'POST');

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        if (!isset($data['data']['id'])) {
            return $this->handle_error('Failed to create tweet');
        }

        $tweet_id = $data['data']['id'];
        $this->log_action('tweet created', array('tweet_id' => $tweet_id));

        return $tweet_id;
    }

    /**
     * Upload media (uses v1.1 API)
     */
    private function upload_media($media_url) {
        // Download media to temp file
        $temp_file = $this->download_media($media_url);

        if (is_wp_error($temp_file)) {
            return $temp_file;
        }

        // Get file contents
        $file_contents = file_get_contents($temp_file);
        $file_size = filesize($temp_file);

        // Step 1: INIT
        $init_url = "{$this->upload_url}/media/upload.json";

        $init_response = $this->make_authenticated_request($init_url, array(
            'body' => array(
                'command' => 'INIT',
                'total_bytes' => $file_size,
                'media_type' => mime_content_type($temp_file),
            ),
        ), 'POST');

        $init_data = $this->parse_json_response($init_response);

        if (is_wp_error($init_data)) {
            @unlink($temp_file);
            return $init_data;
        }

        $media_id = $init_data['media_id_string'];

        // Step 2: APPEND
        $append_url = "{$this->upload_url}/media/upload.json";

        $append_response = $this->make_authenticated_request($append_url, array(
            'body' => array(
                'command' => 'APPEND',
                'media_id' => $media_id,
                'segment_index' => 0,
                'media' => base64_encode($file_contents),
            ),
        ), 'POST');

        // Step 3: FINALIZE
        $finalize_url = "{$this->upload_url}/media/upload.json";

        $finalize_response = $this->make_authenticated_request($finalize_url, array(
            'body' => array(
                'command' => 'FINALIZE',
                'media_id' => $media_id,
            ),
        ), 'POST');

        $finalize_data = $this->parse_json_response($finalize_response);

        @unlink($temp_file);

        if (is_wp_error($finalize_data)) {
            return $finalize_data;
        }

        // Check if processing is required
        if (isset($finalize_data['processing_info'])) {
            $ready = $this->wait_for_media_processing($media_id);
            if (is_wp_error($ready)) {
                return $ready;
            }
        }

        $this->log_action('media uploaded', array('media_id' => $media_id));

        return $media_id;
    }

    /**
     * Wait for media processing
     */
    private function wait_for_media_processing($media_id, $max_attempts = 30) {
        $attempts = 0;

        while ($attempts < $max_attempts) {
            $status_url = "{$this->upload_url}/media/upload.json";

            $response = $this->make_authenticated_request($status_url, array(
                'body' => array(
                    'command' => 'STATUS',
                    'media_id' => $media_id,
                ),
            ), 'GET');

            $data = $this->parse_json_response($response);

            if (is_wp_error($data)) {
                return $data;
            }

            $state = $data['processing_info']['state'] ?? '';

            if ($state === 'succeeded') {
                return true;
            } elseif ($state === 'failed') {
                return new WP_Error('media_processing_failed', 'Media processing failed');
            }

            $check_after = $data['processing_info']['check_after_secs'] ?? 2;
            sleep($check_after);
            $attempts++;
        }

        return new WP_Error('media_timeout', 'Media processing timeout');
    }

    /**
     * Make authenticated request
     */
    private function make_authenticated_request($url, $args = array(), $method = 'POST') {
        $defaults = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
            ),
        );

        $args = array_merge_recursive($defaults, $args);

        return $this->make_request($url, $args, $method);
    }

    /**
     * Get analytics for a tweet
     */
    public function get_analytics($post_id, $date_range = array()) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', 'Not authenticated');
        }

        // Twitter API v2 doesn't provide analytics in free tier
        // This requires elevated or academic access
        $url = "{$this->api_url}/tweets/{$post_id}";

        $params = array(
            'tweet.fields' => 'public_metrics',
        );

        $response = $this->make_authenticated_request(
            $url . '?' . http_build_query($params),
            array(),
            'GET'
        );

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        $metrics = $data['data']['public_metrics'] ?? array();

        return array(
            'retweets' => $metrics['retweet_count'] ?? 0,
            'likes' => $metrics['like_count'] ?? 0,
            'replies' => $metrics['reply_count'] ?? 0,
            'quotes' => $metrics['quote_count'] ?? 0,
            'impressions' => $metrics['impression_count'] ?? 0,
        );
    }

    /**
     * Validate content
     */
    public function validate_content($content) {
        // Twitter/X text limit: 280 characters (4000 for Twitter Blue)
        // We'll use 280 as the standard limit

        $max_length = 280;

        if (strlen($content) > $max_length) {
            return new WP_Error(
                'content_too_long',
                "Tweets are limited to {$max_length} characters. Current: " . strlen($content)
            );
        }

        return true;
    }

    /**
     * Get platform rate limits
     */
    public function get_rate_limits() {
        return array(
            'minute' => 3,     // Conservative
            'hour' => 50,      // Twitter has complex rate limits
            'day' => 300,      // ~300 tweets per day is reasonable
        );
    }

    /**
     * Get platform capabilities
     */
    public function get_capabilities() {
        return array(
            'text' => true,
            'images' => true,
            'videos' => true,
            'links' => true,
            'hashtags' => true,
            'mentions' => true,
            'scheduling' => true,
            'analytics' => true,
            'polls' => false, // Not implemented yet
            'threads' => false, // Not implemented yet
            'max_text_length' => 280,
            'max_images' => 4,
            'max_videos' => 1,
            'max_video_duration' => 140, // seconds for standard accounts
        );
    }

    /**
     * Delete tweet
     */
    public function delete_post($post_id) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', 'Not authenticated');
        }

        $url = "{$this->api_url}/tweets/{$post_id}";

        $response = $this->make_authenticated_request($url, array(), 'DELETE');
        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $data;
        }

        return isset($data['data']['deleted']) && $data['data']['deleted'];
    }
}
