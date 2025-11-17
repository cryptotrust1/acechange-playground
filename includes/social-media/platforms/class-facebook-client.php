<?php
/**
 * Facebook Platform Client
 * Graph API v22.0 - Business Pages only
 *
 * API Documentation: https://developers.facebook.com/docs/graph-api
 *
 * Requirements:
 * - Facebook App (App ID + App Secret)
 * - Facebook Business Page
 * - Page Access Token
 * - Permissions: pages_read_engagement, pages_manage_posts
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Social_Facebook_Client extends AI_SEO_Social_Platform_Client {

    protected $platform_name = 'facebook';
    private $app_id;
    private $app_secret;
    private $page_id;
    private $page_access_token;
    private $graph_api_version = 'v22.0';
    private $graph_api_url = 'https://graph.facebook.com';

    /**
     * Authenticate with Facebook Graph API
     */
    public function authenticate() {
        $this->app_id = $this->get_credential('app_id');
        $this->app_secret = $this->get_credential('app_secret');
        $this->page_id = $this->get_credential('page_id');
        $this->page_access_token = $this->get_credential('page_access_token');

        if (empty($this->app_id) || empty($this->app_secret)) {
            return new WP_Error('no_credentials', 'Facebook App credentials not configured');
        }

        if (empty($this->page_id) || empty($this->page_access_token)) {
            return new WP_Error('no_page_token', 'Facebook Page not connected. Please complete OAuth flow.');
        }

        // Verify page access token
        $verify = $this->verify_access_token();

        if (is_wp_error($verify)) {
            return $verify;
        }

        $this->log_action('authenticated', array('page_id' => $this->page_id));

        return true;
    }

    /**
     * Verify access token validity
     */
    private function verify_access_token() {
        $url = "{$this->graph_api_url}/{$this->graph_api_version}/me?access_token={$this->page_access_token}";

        $response = $this->make_request($url, array(), 'GET');
        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $data;
        }

        if (!isset($data['id'])) {
            return new WP_Error('invalid_token', 'Invalid Facebook access token');
        }

        return $data;
    }

    /**
     * Publish content to Facebook Page
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

        // Choose publishing method based on media
        if (!empty($media)) {
            if (count($media) == 1) {
                $media_type = $this->detect_media_type($media[0]);

                if ($media_type === 'image') {
                    return $this->publish_photo($content, $media[0]);
                } elseif ($media_type === 'video') {
                    return $this->publish_video($content, $media[0]);
                }
            } else {
                // Multiple images - use carousel/album
                return $this->publish_album($content, $media);
            }
        }

        // Text only or link post
        return $this->publish_text($content);
    }

    /**
     * Publish text/link post
     */
    private function publish_text($message) {
        $url = "{$this->graph_api_url}/{$this->graph_api_version}/{$this->page_id}/feed";

        $params = array(
            'message' => $message,
            'access_token' => $this->page_access_token,
        );

        // Detect if message contains URL
        if (preg_match('/https?:\/\/[^\s]+/', $message, $matches)) {
            $params['link'] = $matches[0];
        }

        $response = $this->make_request($url, array('body' => $params));
        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        if (!isset($data['id'])) {
            return $this->handle_error('Failed to publish post');
        }

        $post_id = $data['id'];
        $this->log_action('text post published', array('post_id' => $post_id));

        return $post_id;
    }

    /**
     * Publish photo post
     */
    private function publish_photo($caption, $photo_url) {
        $url = "{$this->graph_api_url}/{$this->graph_api_version}/{$this->page_id}/photos";

        $params = array(
            'url' => $photo_url,
            'caption' => $caption,
            'access_token' => $this->page_access_token,
        );

        $response = $this->make_request($url, array('body' => $params));
        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        if (!isset($data['id'])) {
            return $this->handle_error('Failed to publish photo');
        }

        $post_id = $data['post_id'] ?? $data['id'];
        $this->log_action('photo published', array('post_id' => $post_id));

        return $post_id;
    }

    /**
     * Publish video post
     */
    private function publish_video($description, $video_url) {
        $url = "{$this->graph_api_url}/{$this->graph_api_version}/{$this->page_id}/videos";

        $params = array(
            'file_url' => $video_url,
            'description' => $description,
            'access_token' => $this->page_access_token,
        );

        $response = $this->make_request($url, array(
            'body' => $params,
            'timeout' => 120, // Videos take longer
        ));

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        if (!isset($data['id'])) {
            return $this->handle_error('Failed to publish video');
        }

        $post_id = $data['id'];
        $this->log_action('video published', array('post_id' => $post_id));

        return $post_id;
    }

    /**
     * Publish photo album (multiple images)
     */
    private function publish_album($message, $photos) {
        // First, upload all photos
        $photo_ids = array();

        foreach ($photos as $photo_url) {
            $upload_url = "{$this->graph_api_url}/{$this->graph_api_version}/{$this->page_id}/photos";

            $params = array(
                'url' => $photo_url,
                'published' => 'false', // Don't publish yet
                'access_token' => $this->page_access_token,
            );

            $response = $this->make_request($upload_url, array('body' => $params));
            $data = $this->parse_json_response($response);

            if (!is_wp_error($data) && isset($data['id'])) {
                $photo_ids[] = array('media_fbid' => $data['id']);
            }
        }

        if (empty($photo_ids)) {
            return $this->handle_error('Failed to upload photos for album');
        }

        // Now create the album post
        $feed_url = "{$this->graph_api_url}/{$this->graph_api_version}/{$this->page_id}/feed";

        $params = array(
            'message' => $message,
            'attached_media' => json_encode($photo_ids),
            'access_token' => $this->page_access_token,
        );

        $response = $this->make_request($feed_url, array('body' => $params));
        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        $post_id = $data['id'] ?? null;
        $this->log_action('album published', array('post_id' => $post_id, 'photos' => count($photo_ids)));

        return $post_id;
    }

    /**
     * Get analytics for a post
     */
    public function get_analytics($post_id, $date_range = array()) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', 'Not authenticated');
        }

        $url = "{$this->graph_api_url}/{$this->graph_api_version}/{$post_id}/insights";

        $params = array(
            'metric' => 'post_impressions,post_engaged_users,post_clicks',
            'access_token' => $this->page_access_token,
        );

        $response = $this->make_request($url . '?' . http_build_query($params), array(), 'GET');
        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        // Parse insights
        $analytics = array(
            'impressions' => 0,
            'engaged_users' => 0,
            'clicks' => 0,
        );

        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $metric) {
                $name = $metric['name'] ?? '';
                $value = $metric['values'][0]['value'] ?? 0;

                if ($name === 'post_impressions') {
                    $analytics['impressions'] = $value;
                } elseif ($name === 'post_engaged_users') {
                    $analytics['engaged_users'] = $value;
                } elseif ($name === 'post_clicks') {
                    $analytics['clicks'] = $value;
                }
            }
        }

        return $analytics;
    }

    /**
     * Validate content
     */
    public function validate_content($content) {
        // Facebook post text limit: 63,206 characters (very generous)
        // Practical limit: ~5000 for good UX

        $max_length = 5000;

        if (strlen($content) > $max_length) {
            return new WP_Error(
                'content_too_long',
                "For best results, keep Facebook posts under {$max_length} characters. Current: " . strlen($content)
            );
        }

        return true;
    }

    /**
     * Get platform rate limits
     */
    public function get_rate_limits() {
        return array(
            'minute' => 10,   // Conservative
            'hour' => 200,    // Graph API has complex rate limits
            'day' => 2000,    // Depends on app tier
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
            'albums' => true,
            'max_text_length' => 63206,
            'recommended_text_length' => 5000,
            'max_images' => 10,
            'max_videos' => 1,
        );
    }

    /**
     * Delete post
     */
    public function delete_post($post_id) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', 'Not authenticated');
        }

        $url = "{$this->graph_api_url}/{$this->graph_api_version}/{$post_id}";

        $response = $this->make_request($url, array(
            'body' => array('access_token' => $this->page_access_token),
            'method' => 'DELETE',
        ), 'DELETE');

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $data;
        }

        return isset($data['success']) && $data['success'];
    }
}
