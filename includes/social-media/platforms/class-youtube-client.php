<?php
/**
 * YouTube Platform Client
 * Data API v3 - Google OAuth 2.0
 *
 * API Documentation: https://developers.google.com/youtube/v3
 *
 * Requirements:
 * - Google Cloud Project
 * - YouTube Data API v3 enabled
 * - OAuth 2.0 Client ID + Secret
 * - Access Token + Refresh Token
 * - Scopes: https://www.googleapis.com/auth/youtube.upload,
 *           https://www.googleapis.com/auth/youtube
 *
 * Note: Videos only - YouTube doesn't support text-only posts via API
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Social_YouTube_Client extends AI_SEO_Social_Platform_Client {

    protected $platform_name = 'youtube';
    private $client_id;
    private $client_secret;
    private $access_token;
    private $refresh_token;
    private $channel_id;
    private $api_url = 'https://www.googleapis.com/youtube/v3';
    private $upload_url = 'https://www.googleapis.com/upload/youtube/v3/videos';

    /**
     * Authenticate with YouTube Data API v3
     */
    public function authenticate() {
        $this->client_id = $this->get_credential('client_id');
        $this->client_secret = $this->get_credential('client_secret');
        $this->access_token = $this->get_credential('access_token');
        $this->refresh_token = $this->get_credential('refresh_token');
        $this->channel_id = $this->get_credential('channel_id');

        if (empty($this->client_id) || empty($this->client_secret)) {
            return new WP_Error('no_credentials', 'YouTube API credentials not configured');
        }

        if (empty($this->access_token)) {
            return new WP_Error('no_access_token', 'YouTube not connected. Please complete OAuth 2.0 flow.');
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

        // Store channel ID if not set
        if (empty($this->channel_id) && isset($verify['items'][0]['id'])) {
            $this->channel_id = $verify['items'][0]['id'];
            $this->set_credential('channel_id', $this->channel_id);
        }

        $this->log_action('authenticated', array('channel_id' => $this->channel_id));

        return true;
    }

    /**
     * Verify credentials
     */
    private function verify_credentials() {
        $url = "{$this->api_url}/channels";

        $params = array(
            'part' => 'snippet,contentDetails,statistics',
            'mine' => 'true',
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

        if (!isset($data['items'][0])) {
            return new WP_Error('no_channel', 'No YouTube channel found for this account');
        }

        return $data;
    }

    /**
     * Refresh access token
     */
    private function refresh_access_token() {
        $url = 'https://oauth2.googleapis.com/token';

        $response = wp_remote_post($url, array(
            'body' => array(
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refresh_token,
                'client_id' => $this->client_id,
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

            $this->log_action('token refreshed');
            return true;
        }

        return new WP_Error('token_refresh_failed', 'Failed to refresh access token');
    }

    /**
     * Publish content to YouTube
     *
     * NOTE: YouTube requires video content - text-only posts not supported
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

        // YouTube requires video
        if (empty($media)) {
            return new WP_Error('video_required', 'YouTube requires video content');
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
     * Upload video to YouTube
     */
    private function upload_video($description, $video_url, $title = null) {
        // Download video to temp file
        $temp_file = $this->download_media($video_url);

        if (is_wp_error($temp_file)) {
            return $temp_file;
        }

        // Extract title from description or use default
        if (empty($title)) {
            $title = substr($description, 0, 100);
            if (strlen($description) > 100) {
                $title .= '...';
            }
        }

        // Prepare metadata
        $metadata = array(
            'snippet' => array(
                'title' => $title,
                'description' => $description,
                'categoryId' => '22', // People & Blogs (default)
            ),
            'status' => array(
                'privacyStatus' => 'public', // public, private, or unlisted
                'selfDeclaredMadeForKids' => false,
            ),
        );

        // Upload URL with parameters
        $url = $this->upload_url . '?' . http_build_query(array(
            'part' => 'snippet,status',
            'uploadType' => 'multipart',
        ));

        // Create multipart boundary
        $boundary = uniqid('----YouTubeUpload');

        // Build multipart body
        $file_contents = file_get_contents($temp_file);
        $mime_type = mime_content_type($temp_file);

        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= json_encode($metadata) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: {$mime_type}\r\n\r\n";
        $body .= $file_contents . "\r\n";
        $body .= "--{$boundary}--";

        // Make upload request
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => "multipart/related; boundary={$boundary}",
            ),
            'body' => $body,
            'timeout' => 300, // 5 minutes for video upload
        ));

        @unlink($temp_file);

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        if (!isset($data['id'])) {
            return $this->handle_error('Failed to upload video to YouTube');
        }

        $video_id = $data['id'];

        $this->log_action('video uploaded', array(
            'video_id' => $video_id,
            'title' => $title,
        ));

        return $video_id;
    }

    /**
     * Create community post (YouTube Community tab)
     * Note: This requires YouTube Community API which is still in beta
     */
    public function create_community_post($text, $image_url = null) {
        // Community posts API is not publicly available yet
        // This is a placeholder for future implementation

        return new WP_Error(
            'not_implemented',
            'YouTube Community posts API is not yet publicly available'
        );
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

        $url = "{$this->api_url}/videos";

        $params = array(
            'part' => 'statistics',
            'id' => $post_id,
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

        $stats = $data['items'][0]['statistics'] ?? array();

        return array(
            'views' => $stats['viewCount'] ?? 0,
            'likes' => $stats['likeCount'] ?? 0,
            'comments' => $stats['commentCount'] ?? 0,
            'favorites' => $stats['favoriteCount'] ?? 0,
        );
    }

    /**
     * Validate content
     */
    public function validate_content($content) {
        // YouTube video description limit: 5000 characters
        // Title limit: 100 characters

        $max_description = 5000;

        if (strlen($content) > $max_description) {
            return new WP_Error(
                'content_too_long',
                "YouTube descriptions are limited to {$max_description} characters. Current: " . strlen($content)
            );
        }

        return true;
    }

    /**
     * Get platform rate limits
     */
    public function get_rate_limits() {
        return array(
            'minute' => 1,     // Very conservative - uploads are resource intensive
            'hour' => 10,      // YouTube has quota system (10,000 units/day)
            'day' => 50,       // Video upload = 1600 units each
        );
    }

    /**
     * Get platform capabilities
     */
    public function get_capabilities() {
        return array(
            'text' => false,  // Requires video
            'images' => false, // Requires video (or community posts in beta)
            'videos' => true,
            'links' => true,
            'hashtags' => true,
            'mentions' => false,
            'scheduling' => true,
            'analytics' => true,
            'community_posts' => false, // Beta
            'max_title_length' => 100,
            'max_description_length' => 5000,
            'max_video_size' => 256 * 1024 * 1024 * 1024, // 256 GB
            'max_video_duration' => 12 * 60 * 60, // 12 hours (15 min for unverified)
        );
    }

    /**
     * Delete video
     */
    public function delete_post($post_id) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', 'Not authenticated');
        }

        $url = "{$this->api_url}/videos";

        $params = array(
            'id' => $post_id,
        );

        $response = $this->make_authenticated_request(
            $url . '?' . http_build_query($params),
            array(),
            'DELETE'
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);

        return ($code >= 200 && $code < 300);
    }

    /**
     * Update video metadata
     */
    public function update_video($video_id, $title = null, $description = null, $privacy = null) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', 'Not authenticated');
        }

        // Get current video data
        $current = $this->get_video_details($video_id);

        if (is_wp_error($current)) {
            return $current;
        }

        // Update metadata
        $metadata = $current['items'][0];

        if ($title !== null) {
            $metadata['snippet']['title'] = $title;
        }

        if ($description !== null) {
            $metadata['snippet']['description'] = $description;
        }

        if ($privacy !== null) {
            $metadata['status']['privacyStatus'] = $privacy;
        }

        $url = "{$this->api_url}/videos?" . http_build_query(array('part' => 'snippet,status'));

        $response = $this->make_authenticated_request($url, array(
            'body' => json_encode($metadata),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
        ), 'PUT');

        return $this->parse_json_response($response);
    }

    /**
     * Get video details
     */
    private function get_video_details($video_id) {
        $url = "{$this->api_url}/videos";

        $params = array(
            'part' => 'snippet,status,statistics',
            'id' => $video_id,
        );

        $response = $this->make_authenticated_request(
            $url . '?' . http_build_query($params),
            array(),
            'GET'
        );

        return $this->parse_json_response($response);
    }
}
