<?php
/**
 * LinkedIn Platform Client
 * Posts API - OAuth 2.0
 *
 * API Documentation: https://learn.microsoft.com/en-us/linkedin/marketing/integrations/community-management/shares/posts-api
 *
 * Requirements:
 * - LinkedIn App (Client ID + Client Secret)
 * - OAuth 2.0 tokens (Access Token + Refresh Token)
 * - Permissions: w_member_social, r_basicprofile
 * - For organization posts: w_organization_social
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Social_LinkedIn_Client extends AI_SEO_Social_Platform_Client {

    protected $platform_name = 'linkedin';
    private $client_id;
    private $client_secret;
    private $access_token;
    private $refresh_token;
    private $person_urn; // urn:li:person:XXXXX
    private $organization_urn; // Optional: urn:li:organization:XXXXX
    private $api_url = 'https://api.linkedin.com/v2';
    private $api_url_rest = 'https://api.linkedin.com/rest';

    /**
     * Authenticate with LinkedIn API
     */
    public function authenticate() {
        $this->client_id = $this->get_credential('client_id');
        $this->client_secret = $this->get_credential('client_secret');
        $this->access_token = $this->get_credential('access_token');
        $this->refresh_token = $this->get_credential('refresh_token');
        $this->person_urn = $this->get_credential('person_urn');
        $this->organization_urn = $this->get_credential('organization_urn');

        if (empty($this->client_id) || empty($this->client_secret)) {
            return new WP_Error('no_credentials', 'LinkedIn API credentials not configured');
        }

        if (empty($this->access_token)) {
            return new WP_Error('no_access_token', 'LinkedIn not connected. Please complete OAuth 2.0 flow.');
        }

        // If person URN is not stored, fetch it
        if (empty($this->person_urn)) {
            $user_info = $this->get_user_info();
            if (is_wp_error($user_info)) {
                return $user_info;
            }
            $this->person_urn = $user_info['sub'] ?? '';
            $this->set_credential('person_urn', $this->person_urn);
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

        $this->log_action('authenticated', array('person_urn' => $this->person_urn));

        return true;
    }

    /**
     * Get user info
     */
    private function get_user_info() {
        $url = 'https://api.linkedin.com/v2/userinfo';

        $response = $this->make_authenticated_request($url, array(), 'GET');
        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $data;
        }

        return $data;
    }

    /**
     * Verify credentials
     */
    private function verify_credentials() {
        $url = "{$this->api_url}/me";

        $response = $this->make_authenticated_request($url, array(), 'GET');
        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $data;
        }

        if (!isset($data['id'])) {
            return new WP_Error('invalid_credentials', 'Invalid LinkedIn credentials');
        }

        return $data;
    }

    /**
     * Refresh access token
     */
    private function refresh_access_token() {
        $url = 'https://www.linkedin.com/oauth/v2/accessToken';

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
     * Publish content to LinkedIn
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

        // Determine author (person or organization)
        $author = !empty($this->organization_urn) ? $this->organization_urn : $this->person_urn;

        if (empty($media)) {
            return $this->create_text_post($content, $author);
        } else {
            return $this->create_media_post($content, $media, $author);
        }
    }

    /**
     * Create text-only post
     */
    private function create_text_post($text, $author) {
        $url = "{$this->api_url_rest}/posts";

        $post_data = array(
            'author' => $author,
            'commentary' => $text,
            'visibility' => 'PUBLIC',
            'distribution' => array(
                'feedDistribution' => 'MAIN_FEED',
                'targetEntities' => array(),
                'thirdPartyDistributionChannels' => array(),
            ),
            'lifecycleState' => 'PUBLISHED',
            'isReshareDisabledByAuthor' => false,
        );

        $response = $this->make_authenticated_request($url, array(
            'body' => json_encode($post_data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0',
                'LinkedIn-Version' => '202401',
            ),
        ), 'POST');

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        // Extract post ID from response header or body
        $post_id = $data['id'] ?? '';

        if (empty($post_id)) {
            return $this->handle_error('Failed to create LinkedIn post');
        }

        $this->log_action('text post created', array('post_id' => $post_id));

        return $post_id;
    }

    /**
     * Create post with media
     */
    private function create_media_post($text, $media, $author) {
        // Step 1: Upload media and get asset URNs
        $media_assets = array();

        foreach ($media as $media_url) {
            $media_type = $this->detect_media_type($media_url);
            $asset = $this->upload_media($media_url, $media_type, $author);

            if (!is_wp_error($asset)) {
                $media_assets[] = $asset;
            }
        }

        if (empty($media_assets)) {
            return $this->handle_error('Failed to upload media');
        }

        // Step 2: Create post with media
        $url = "{$this->api_url_rest}/posts";

        $post_data = array(
            'author' => $author,
            'commentary' => $text,
            'visibility' => 'PUBLIC',
            'distribution' => array(
                'feedDistribution' => 'MAIN_FEED',
                'targetEntities' => array(),
                'thirdPartyDistributionChannels' => array(),
            ),
            'content' => array(
                'media' => array(
                    'title' => substr($text, 0, 100),
                    'id' => $media_assets[0], // First media asset
                ),
            ),
            'lifecycleState' => 'PUBLISHED',
            'isReshareDisabledByAuthor' => false,
        );

        $response = $this->make_authenticated_request($url, array(
            'body' => json_encode($post_data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0',
                'LinkedIn-Version' => '202401',
            ),
        ), 'POST');

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        $post_id = $data['id'] ?? '';

        if (empty($post_id)) {
            return $this->handle_error('Failed to create LinkedIn post with media');
        }

        $this->log_action('media post created', array('post_id' => $post_id));

        return $post_id;
    }

    /**
     * Upload media to LinkedIn
     */
    private function upload_media($media_url, $media_type, $author) {
        // Step 1: Register upload
        $register_url = "{$this->api_url}/assets?action=registerUpload";

        $register_data = array(
            'registerUploadRequest' => array(
                'owner' => $author,
                'recipes' => array('urn:li:digitalmediaRecipe:feedshare-image'),
                'serviceRelationships' => array(
                    array(
                        'identifier' => 'urn:li:userGeneratedContent',
                        'relationshipType' => 'OWNER',
                    ),
                ),
            ),
        );

        $response = $this->make_authenticated_request($register_url, array(
            'body' => json_encode($register_data),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
        ), 'POST');

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $data;
        }

        $asset = $data['value']['asset'] ?? '';
        $upload_url = $data['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'] ?? '';

        if (empty($asset) || empty($upload_url)) {
            return new WP_Error('upload_registration_failed', 'Failed to register media upload');
        }

        // Step 2: Download media file
        $temp_file = $this->download_media($media_url);

        if (is_wp_error($temp_file)) {
            return $temp_file;
        }

        // Step 3: Upload to LinkedIn
        $file_contents = file_get_contents($temp_file);
        $mime_type = mime_content_type($temp_file);

        $upload_response = wp_remote_post($upload_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => $mime_type,
            ),
            'body' => $file_contents,
            'timeout' => 120,
        ));

        @unlink($temp_file);

        if (is_wp_error($upload_response)) {
            return $upload_response;
        }

        $upload_code = wp_remote_retrieve_response_code($upload_response);

        if ($upload_code < 200 || $upload_code >= 300) {
            return new WP_Error('upload_failed', 'Failed to upload media to LinkedIn');
        }

        $this->log_action('media uploaded', array('asset' => $asset));

        return $asset;
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
     * Get analytics for a post
     */
    public function get_analytics($post_id, $date_range = array()) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', 'Not authenticated');
        }

        // LinkedIn analytics require additional permissions
        // For now, return basic structure
        $this->log_action('analytics requested (requires additional permissions)', array(
            'post_id' => $post_id,
        ));

        return array(
            'impressions' => 0,
            'clicks' => 0,
            'likes' => 0,
            'comments' => 0,
            'shares' => 0,
            'note' => 'Full analytics require LinkedIn Marketing API access',
        );
    }

    /**
     * Validate content
     */
    public function validate_content($content) {
        // LinkedIn post limit: 3000 characters
        // Recommended: 150-250 for best engagement

        $max_length = 3000;

        if (strlen($content) > $max_length) {
            return new WP_Error(
                'content_too_long',
                "LinkedIn posts are limited to {$max_length} characters. Current: " . strlen($content)
            );
        }

        return true;
    }

    /**
     * Get platform rate limits
     */
    public function get_rate_limits() {
        return array(
            'minute' => 5,     // Conservative
            'hour' => 100,     // LinkedIn has generous rate limits
            'day' => 500,      // Depends on app tier
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
            'analytics' => false, // Requires Marketing API
            'articles' => false,  // Not implemented yet
            'max_text_length' => 3000,
            'recommended_text_length' => 200,
            'max_images' => 9,
            'max_videos' => 1,
            'max_hashtags' => 30,
        );
    }

    /**
     * Delete post
     */
    public function delete_post($post_id) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', 'Not authenticated');
        }

        $url = "{$this->api_url_rest}/posts/{$post_id}";

        $response = $this->make_authenticated_request($url, array(
            'headers' => array(
                'X-Restli-Protocol-Version' => '2.0.0',
                'LinkedIn-Version' => '202401',
            ),
        ), 'DELETE');

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);

        return ($code >= 200 && $code < 300);
    }
}
