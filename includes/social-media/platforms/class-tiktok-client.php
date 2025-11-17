<?php
/**
 * TikTok Platform Client
 * Content Posting API - OAuth 2.0
 *
 * API Documentation: https://developers.tiktok.com/doc/content-posting-api-get-started
 *
 * Requirements:
 * - TikTok Developer Account
 * - App created and approved (requires audit)
 * - Client Key + Client Secret
 * - OAuth 2.0 tokens (Access Token + Refresh Token)
 * - Scopes: video.upload, video.publish
 *
 * Note: TikTok requires app audit before posting capabilities are enabled
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Social_TikTok_Client extends AI_SEO_Social_Platform_Client {

    protected $platform_name = 'tiktok';
    private $client_key;
    private $client_secret;
    private $access_token;
    private $refresh_token;
    private $open_id; // User's TikTok Open ID
    private $api_url = 'https://open.tiktokapis.com/v2';

    /**
     * Authenticate with TikTok API
     */
    public function authenticate() {
        $this->client_key = $this->get_credential('client_key');
        $this->client_secret = $this->get_credential('client_secret');
        $this->access_token = $this->get_credential('access_token');
        $this->refresh_token = $this->get_credential('refresh_token');
        $this->open_id = $this->get_credential('open_id');

        if (empty($this->client_key) || empty($this->client_secret)) {
            return new WP_Error('no_credentials', 'TikTok API credentials not configured');
        }

        if (empty($this->access_token)) {
            return new WP_Error('no_access_token', 'TikTok not connected. Please complete OAuth 2.0 flow.');
        }

        if (empty($this->open_id)) {
            return new WP_Error('no_open_id', 'TikTok user Open ID not configured');
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

        $this->log_action('authenticated', array('open_id' => $this->open_id));

        return true;
    }

    /**
     * Verify credentials
     */
    private function verify_credentials() {
        $url = "{$this->api_url}/user/info/";

        $params = array(
            'fields' => 'open_id,union_id,avatar_url,display_name',
        );

        $response = $this->make_authenticated_request(
            $url . '?' . http_build_query($params),
            array(),
            'GET'
        );

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $data;
        }

        if (!isset($data['data']['user']['open_id'])) {
            return new WP_Error('invalid_credentials', 'Invalid TikTok credentials');
        }

        return $data['data']['user'];
    }

    /**
     * Refresh access token
     */
    private function refresh_access_token() {
        $url = 'https://open.tiktokapis.com/v2/oauth/token/';

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refresh_token,
                'client_key' => $this->client_key,
                'client_secret' => $this->client_secret,
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
     * Publish content to TikTok
     *
     * NOTE: TikTok requires video content
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

        // TikTok requires video
        if (empty($media)) {
            return new WP_Error('video_required', 'TikTok requires video content');
        }

        $video_url = null;
        foreach ($media as $media_item) {
            if ($this->detect_media_type($media_item) === 'video') {
                $video_url = $media_item;
                break;
            }
        }

        if (empty($video_url)) {
            return new WP_Error('video_required', 'No video found in media array');
        }

        return $this->upload_video($content, $video_url);
    }

    /**
     * Upload video to TikTok
     */
    private function upload_video($caption, $video_url) {
        // Step 1: Initialize video upload
        $init_result = $this->initialize_upload();

        if (is_wp_error($init_result)) {
            return $init_result;
        }

        $publish_id = $init_result['publish_id'];
        $upload_url = $init_result['upload_url'];

        // Step 2: Download video to temp file
        $temp_file = $this->download_media($video_url);

        if (is_wp_error($temp_file)) {
            return $temp_file;
        }

        // Step 3: Upload video to TikTok's upload URL
        $upload_result = $this->upload_video_file($upload_url, $temp_file);

        @unlink($temp_file);

        if (is_wp_error($upload_result)) {
            return $upload_result;
        }

        // Step 4: Publish the video with metadata
        return $this->publish_video($publish_id, $caption);
    }

    /**
     * Initialize video upload
     */
    private function initialize_upload() {
        $url = "{$this->api_url}/post/publish/video/init/";

        $post_info = array(
            'title' => substr($this->format_content($caption ?? ''), 0, 150),
            'privacy_level' => 'SELF_ONLY', // PUBLIC_TO_EVERYONE, MUTUAL_FOLLOW_FRIENDS, SELF_ONLY
            'disable_duet' => false,
            'disable_comment' => false,
            'disable_stitch' => false,
            'video_cover_timestamp_ms' => 1000,
        );

        $body = array(
            'post_info' => $post_info,
            'source_info' => array(
                'source' => 'PULL_FROM_URL',
                'video_url' => '', // Will be uploaded separately
            ),
        );

        $response = $this->make_authenticated_request($url, array(
            'body' => json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json; charset=UTF-8',
            ),
        ), 'POST');

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        if (!isset($data['data']['publish_id'])) {
            return $this->handle_error('Failed to initialize TikTok upload');
        }

        return array(
            'publish_id' => $data['data']['publish_id'],
            'upload_url' => $data['data']['upload_url'],
        );
    }

    /**
     * Upload video file to TikTok's upload URL
     */
    private function upload_video_file($upload_url, $temp_file) {
        $file_contents = file_get_contents($temp_file);
        $file_size = filesize($temp_file);

        $response = wp_remote_put($upload_url, array(
            'headers' => array(
                'Content-Type' => 'video/mp4',
                'Content-Length' => $file_size,
            ),
            'body' => $file_contents,
            'timeout' => 300, // 5 minutes
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code < 200 || $code >= 300) {
            return new WP_Error('upload_failed', 'Failed to upload video to TikTok');
        }

        return true;
    }

    /**
     * Publish the video with metadata
     */
    private function publish_video($publish_id, $caption) {
        // Check upload status first
        $status = $this->check_upload_status($publish_id);

        if (is_wp_error($status)) {
            return $status;
        }

        // If upload is complete, the video is automatically published
        // Return the publish_id as the post ID

        $this->log_action('video published', array('publish_id' => $publish_id));

        return $publish_id;
    }

    /**
     * Check upload status
     */
    private function check_upload_status($publish_id, $max_attempts = 30) {
        $attempts = 0;

        while ($attempts < $max_attempts) {
            $url = "{$this->api_url}/post/publish/status/fetch/";

            $body = array(
                'publish_id' => $publish_id,
            );

            $response = $this->make_authenticated_request($url, array(
                'body' => json_encode($body),
                'headers' => array(
                    'Content-Type' => 'application/json; charset=UTF-8',
                ),
            ), 'POST');

            $data = $this->parse_json_response($response);

            if (is_wp_error($data)) {
                return $data;
            }

            $status = $data['data']['status'] ?? '';

            if ($status === 'PUBLISH_COMPLETE') {
                return true;
            } elseif ($status === 'FAILED') {
                $error = $data['data']['fail_reason'] ?? 'Unknown error';
                return new WP_Error('publish_failed', 'TikTok publish failed: ' . $error);
            }

            // Wait before next check
            sleep(2);
            $attempts++;
        }

        return new WP_Error('publish_timeout', 'TikTok publish timeout');
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
     * Get analytics for a video
     */
    public function get_analytics($post_id, $date_range = array()) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', 'Not authenticated');
        }

        // TikTok analytics require separate API access
        // For now, return placeholder

        $this->log_action('analytics requested (requires separate API access)', array(
            'post_id' => $post_id,
        ));

        return array(
            'views' => 0,
            'likes' => 0,
            'comments' => 0,
            'shares' => 0,
            'note' => 'TikTok analytics require separate API access and permissions',
        );
    }

    /**
     * Validate content
     */
    public function validate_content($content) {
        // TikTok caption limit: 2200 characters (for video description)
        // Title limit: 150 characters

        $max_length = 2200;

        if (strlen($content) > $max_length) {
            return new WP_Error(
                'content_too_long',
                "TikTok captions are limited to {$max_length} characters. Current: " . strlen($content)
            );
        }

        // Check hashtag limit (max 30)
        $hashtags = $this->extract_hashtags($content);
        if (count($hashtags) > 30) {
            return new WP_Error(
                'too_many_hashtags',
                'TikTok allows maximum 30 hashtags per post. Current: ' . count($hashtags)
            );
        }

        return true;
    }

    /**
     * Get platform rate limits
     */
    public function get_rate_limits() {
        return array(
            'minute' => 1,     // Very conservative - video uploads are intensive
            'hour' => 5,       // TikTok has strict rate limits
            'day' => 20,       // Recommended not to exceed 20 videos/day
        );
    }

    /**
     * Get platform capabilities
     */
    public function get_capabilities() {
        return array(
            'text' => false,  // Requires video
            'images' => false, // Requires video
            'videos' => true,
            'links' => false, // Only in bio
            'hashtags' => true,
            'mentions' => true,
            'scheduling' => true,
            'analytics' => false, // Requires separate API access
            'max_title_length' => 150,
            'max_caption_length' => 2200,
            'max_hashtags' => 30,
            'max_video_duration' => 10 * 60, // 10 minutes
            'max_video_size' => 4 * 1024 * 1024 * 1024, // 4 GB
            'supported_formats' => array('mp4', 'mov', 'webm'),
        );
    }

    /**
     * Delete video
     */
    public function delete_post($post_id) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', 'Not authenticated');
        }

        // TikTok delete endpoint
        $url = "{$this->api_url}/post/publish/video/delete/";

        $body = array(
            'video_id' => $post_id,
        );

        $response = $this->make_authenticated_request($url, array(
            'body' => json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json; charset=UTF-8',
            ),
        ), 'POST');

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $data;
        }

        return isset($data['data']['success']) && $data['data']['success'];
    }
}
