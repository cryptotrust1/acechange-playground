<?php
/**
 * Instagram Platform Client
 * Graph API - Business/Creator accounts only
 *
 * API Documentation: https://developers.facebook.com/docs/instagram-api
 *
 * Requirements:
 * - Instagram Business or Creator account
 * - Facebook Page connected to Instagram account
 * - Facebook App (App ID + App Secret)
 * - Instagram Business Account ID
 * - Access Token with permissions: instagram_basic, instagram_content_publish
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Social_Instagram_Client extends AI_SEO_Social_Platform_Client {

    protected $platform_name = 'instagram';
    private $app_id;
    private $app_secret;
    private $instagram_account_id;
    private $access_token;
    private $graph_api_version = 'v22.0';
    private $graph_api_url = 'https://graph.facebook.com';

    /**
     * Authenticate with Instagram Graph API
     */
    public function authenticate() {
        $this->app_id = $this->get_credential('app_id');
        $this->app_secret = $this->get_credential('app_secret');
        $this->instagram_account_id = $this->get_credential('instagram_account_id');
        $this->access_token = $this->get_credential('access_token');

        if (empty($this->app_id) || empty($this->app_secret)) {
            return new WP_Error('no_credentials', 'Instagram App credentials not configured');
        }

        if (empty($this->instagram_account_id) || empty($this->access_token)) {
            return new WP_Error('no_account_token', 'Instagram account not connected. Please complete OAuth flow.');
        }

        // Verify access token
        $verify = $this->verify_access_token();

        if (is_wp_error($verify)) {
            return $verify;
        }

        $this->log_action('authenticated', array('account_id' => $this->instagram_account_id));

        return true;
    }

    /**
     * Verify access token validity
     */
    private function verify_access_token() {
        $url = "{$this->graph_api_url}/{$this->graph_api_version}/{$this->instagram_account_id}?fields=id,username&access_token={$this->access_token}";

        $response = $this->make_request($url, array(), 'GET');
        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $data;
        }

        if (!isset($data['id'])) {
            return new WP_Error('invalid_token', 'Invalid Instagram access token');
        }

        return $data;
    }

    /**
     * Publish content to Instagram
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

        // Instagram REQUIRES media (no text-only posts)
        if (empty($media)) {
            return new WP_Error('media_required', 'Instagram requires at least one image or video');
        }

        // Determine media type
        if (count($media) == 1) {
            $media_type = $this->detect_media_type($media[0]);

            if ($media_type === 'image') {
                return $this->publish_photo($content, $media[0]);
            } elseif ($media_type === 'video') {
                return $this->publish_video($content, $media[0]);
            }
        } else {
            // Multiple images - carousel
            return $this->publish_carousel($content, $media);
        }

        return new WP_Error('unsupported_media', 'Unsupported media type');
    }

    /**
     * Publish single photo
     */
    private function publish_photo($caption, $image_url) {
        // Step 1: Create media container
        $container_url = "{$this->graph_api_url}/{$this->graph_api_version}/{$this->instagram_account_id}/media";

        $container_params = array(
            'image_url' => $image_url,
            'caption' => $caption,
            'access_token' => $this->access_token,
        );

        $response = $this->make_request($container_url, array('body' => $container_params));
        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        if (!isset($data['id'])) {
            return $this->handle_error('Failed to create media container');
        }

        $container_id = $data['id'];

        // Step 2: Publish the container
        return $this->publish_container($container_id);
    }

    /**
     * Publish single video
     */
    private function publish_video($caption, $video_url) {
        // Step 1: Create video container
        $container_url = "{$this->graph_api_url}/{$this->graph_api_version}/{$this->instagram_account_id}/media";

        $container_params = array(
            'media_type' => 'VIDEO',
            'video_url' => $video_url,
            'caption' => $caption,
            'access_token' => $this->access_token,
        );

        $response = $this->make_request($container_url, array(
            'body' => $container_params,
            'timeout' => 120,
        ));

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        if (!isset($data['id'])) {
            return $this->handle_error('Failed to create video container');
        }

        $container_id = $data['id'];

        // Step 2: Wait for video processing (poll status)
        $ready = $this->wait_for_video_processing($container_id);

        if (is_wp_error($ready)) {
            return $ready;
        }

        // Step 3: Publish the container
        return $this->publish_container($container_id);
    }

    /**
     * Publish carousel (multiple images)
     */
    private function publish_carousel($caption, $images) {
        // Step 1: Create containers for each image
        $children = array();

        foreach ($images as $image_url) {
            $container_url = "{$this->graph_api_url}/{$this->graph_api_version}/{$this->instagram_account_id}/media";

            $container_params = array(
                'is_carousel_item' => 'true',
                'image_url' => $image_url,
                'access_token' => $this->access_token,
            );

            $response = $this->make_request($container_url, array('body' => $container_params));
            $data = $this->parse_json_response($response);

            if (!is_wp_error($data) && isset($data['id'])) {
                $children[] = $data['id'];
            }
        }

        if (empty($children)) {
            return $this->handle_error('Failed to create carousel items');
        }

        // Step 2: Create carousel container
        $carousel_url = "{$this->graph_api_url}/{$this->graph_api_version}/{$this->instagram_account_id}/media";

        $carousel_params = array(
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $children),
            'caption' => $caption,
            'access_token' => $this->access_token,
        );

        $response = $this->make_request($carousel_url, array('body' => $carousel_params));
        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        if (!isset($data['id'])) {
            return $this->handle_error('Failed to create carousel container');
        }

        $container_id = $data['id'];

        // Step 3: Publish the carousel
        return $this->publish_container($container_id);
    }

    /**
     * Publish a media container
     */
    private function publish_container($container_id) {
        $publish_url = "{$this->graph_api_url}/{$this->graph_api_version}/{$this->instagram_account_id}/media_publish";

        $publish_params = array(
            'creation_id' => $container_id,
            'access_token' => $this->access_token,
        );

        $response = $this->make_request($publish_url, array('body' => $publish_params));
        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        if (!isset($data['id'])) {
            return $this->handle_error('Failed to publish media');
        }

        $media_id = $data['id'];
        $this->log_action('media published', array('media_id' => $media_id));

        return $media_id;
    }

    /**
     * Wait for video processing to complete
     */
    private function wait_for_video_processing($container_id, $max_attempts = 30) {
        $attempts = 0;

        while ($attempts < $max_attempts) {
            $status_url = "{$this->graph_api_url}/{$this->graph_api_version}/{$container_id}?fields=status_code&access_token={$this->access_token}";

            $response = $this->make_request($status_url, array(), 'GET');
            $data = $this->parse_json_response($response);

            if (is_wp_error($data)) {
                return $data;
            }

            $status = $data['status_code'] ?? '';

            if ($status === 'FINISHED') {
                return true;
            } elseif ($status === 'ERROR') {
                return new WP_Error('video_processing_error', 'Video processing failed');
            }

            // Wait 2 seconds before next check
            sleep(2);
            $attempts++;
        }

        return new WP_Error('video_timeout', 'Video processing timeout');
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
            'metric' => 'impressions,reach,engagement,saved,video_views',
            'access_token' => $this->access_token,
        );

        $response = $this->make_request($url . '?' . http_build_query($params), array(), 'GET');
        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        // Parse insights
        $analytics = array(
            'impressions' => 0,
            'reach' => 0,
            'engagement' => 0,
            'saved' => 0,
            'video_views' => 0,
        );

        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $metric) {
                $name = $metric['name'] ?? '';
                $value = $metric['values'][0]['value'] ?? 0;

                if (isset($analytics[$name])) {
                    $analytics[$name] = $value;
                }
            }
        }

        return $analytics;
    }

    /**
     * Validate content
     */
    public function validate_content($content) {
        // Instagram caption limit: 2,200 characters
        // Practical limit: ~125 characters in feed preview

        $max_length = 2200;

        if (strlen($content) > $max_length) {
            return new WP_Error(
                'content_too_long',
                "Instagram captions are limited to {$max_length} characters. Current: " . strlen($content)
            );
        }

        // Check hashtag limit (max 30)
        $hashtags = $this->extract_hashtags($content);
        if (count($hashtags) > 30) {
            return new WP_Error(
                'too_many_hashtags',
                'Instagram allows maximum 30 hashtags per post. Current: ' . count($hashtags)
            );
        }

        return true;
    }

    /**
     * Get platform rate limits
     */
    public function get_rate_limits() {
        return array(
            'minute' => 5,    // Conservative - Instagram has content publishing rate limits
            'hour' => 25,     // 25 posts per day is the practical limit
            'day' => 25,      // Instagram recommends not more than 25 posts/day
        );
    }

    /**
     * Get platform capabilities
     */
    public function get_capabilities() {
        return array(
            'text' => false,  // Requires media
            'images' => true,
            'videos' => true,
            'links' => false, // Only in bio or stories
            'hashtags' => true,
            'mentions' => true,
            'scheduling' => true,
            'analytics' => true,
            'carousel' => true,
            'stories' => false, // Not implemented yet
            'max_text_length' => 2200,
            'max_hashtags' => 30,
            'max_images' => 10, // Carousel
            'max_videos' => 1,
            'max_video_duration' => 60, // seconds for feed
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
            'body' => array('access_token' => $this->access_token),
            'method' => 'DELETE',
        ), 'DELETE');

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $data;
        }

        return isset($data['success']) && $data['success'];
    }
}
