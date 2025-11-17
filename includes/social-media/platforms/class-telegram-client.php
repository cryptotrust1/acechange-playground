<?php
/**
 * Telegram Platform Client
 * Najjednoduchšia implementácia - FREE, bez OAuth, veľkorysé limity
 *
 * API Documentation: https://core.telegram.org/bots/api
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Social_Telegram_Client extends AI_SEO_Social_Platform_Client {

    protected $platform_name = 'telegram';
    private $bot_token;
    private $channel_id;
    private $api_url = 'https://api.telegram.org/bot';

    /**
     * Authenticate (Telegram je jednoduché - len token a channel ID)
     */
    public function authenticate() {
        $this->bot_token = $this->get_credential('bot_token');
        $this->channel_id = $this->get_credential('channel_id');

        if (empty($this->bot_token)) {
            return new WP_Error('no_bot_token', 'Telegram bot token not configured');
        }

        if (empty($this->channel_id)) {
            return new WP_Error('no_channel_id', 'Telegram channel ID not configured');
        }

        // Test connection
        $test = $this->test_connection();

        if (is_wp_error($test)) {
            return $test;
        }

        $this->log_action('authenticated');

        return true;
    }

    /**
     * Test connection to Telegram Bot API
     */
    private function test_connection() {
        $response = $this->make_request(
            $this->api_url . $this->bot_token . '/getMe',
            array(),
            'GET'
        );

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $data;
        }

        if (!isset($data['ok']) || !$data['ok']) {
            return new WP_Error('telegram_error', $data['description'] ?? 'Unknown error');
        }

        return $data['result'];
    }

    /**
     * Publish content to Telegram
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

        // Choose method based on media
        if (!empty($media)) {
            $media_type = $this->detect_media_type($media[0]);

            if ($media_type === 'image') {
                return $this->send_photo($content, $media[0]);
            } elseif ($media_type === 'video') {
                return $this->send_video($content, $media[0]);
            }
        }

        // Send as text message
        return $this->send_message($content);
    }

    /**
     * Send text message
     */
    private function send_message($text) {
        $endpoint = $this->api_url . $this->bot_token . '/sendMessage';

        $response = $this->make_request($endpoint, array(
            'body' => array(
                'chat_id' => $this->channel_id,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => false,
            ),
        ));

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        if (!isset($data['ok']) || !$data['ok']) {
            $error = $data['description'] ?? 'Unknown error';
            return $this->handle_error($error);
        }

        $message_id = $data['result']['message_id'];

        $this->log_action('message sent', array(
            'message_id' => $message_id,
        ));

        return (string) $message_id;
    }

    /**
     * Send photo with caption
     */
    private function send_photo($caption, $photo_url) {
        $endpoint = $this->api_url . $this->bot_token . '/sendPhoto';

        // Telegram accepts URL or file upload
        $body = array(
            'chat_id' => $this->channel_id,
            'photo' => $photo_url,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        );

        $response = $this->make_request($endpoint, array('body' => $body));

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        if (!isset($data['ok']) || !$data['ok']) {
            $error = $data['description'] ?? 'Unknown error';
            return $this->handle_error($error);
        }

        $message_id = $data['result']['message_id'];

        $this->log_action('photo sent', array(
            'message_id' => $message_id,
            'photo_url' => $photo_url,
        ));

        return (string) $message_id;
    }

    /**
     * Send video with caption
     */
    private function send_video($caption, $video_url) {
        $endpoint = $this->api_url . $this->bot_token . '/sendVideo';

        $body = array(
            'chat_id' => $this->channel_id,
            'video' => $video_url,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        );

        $response = $this->make_request($endpoint, array('body' => $body));

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        if (!isset($data['ok']) || !$data['ok']) {
            $error = $data['description'] ?? 'Unknown error';
            return $this->handle_error($error);
        }

        $message_id = $data['result']['message_id'];

        $this->log_action('video sent', array(
            'message_id' => $message_id,
            'video_url' => $video_url,
        ));

        return (string) $message_id;
    }

    /**
     * Send poll (bonus feature)
     */
    public function send_poll($question, $options, $is_anonymous = true) {
        if (!$this->is_authenticated()) {
            $auth = $this->authenticate();
            if (is_wp_error($auth)) {
                return $auth;
            }
        }

        $endpoint = $this->api_url . $this->bot_token . '/sendPoll';

        $body = array(
            'chat_id' => $this->channel_id,
            'question' => $question,
            'options' => json_encode($options),
            'is_anonymous' => $is_anonymous,
        );

        $response = $this->make_request($endpoint, array('body' => $body));

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        if (!isset($data['ok']) || !$data['ok']) {
            return $this->handle_error($data['description'] ?? 'Unknown error');
        }

        return (string) $data['result']['message_id'];
    }

    /**
     * Pin message (bonus feature)
     */
    public function pin_message($message_id) {
        if (!$this->is_authenticated()) {
            $auth = $this->authenticate();
            if (is_wp_error($auth)) {
                return $auth;
            }
        }

        $endpoint = $this->api_url . $this->bot_token . '/pinChatMessage';

        $response = $this->make_request($endpoint, array(
            'body' => array(
                'chat_id' => $this->channel_id,
                'message_id' => $message_id,
            ),
        ));

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $this->handle_error($data);
        }

        return !is_wp_error($data) && isset($data['ok']) && $data['ok'];
    }

    /**
     * Get analytics (Telegram doesn't provide detailed analytics via API)
     */
    public function get_analytics($post_id, $date_range = array()) {
        // Telegram Bot API nemá plné analytics
        // Môžeme len vrátiť základné info

        $this->log_action('analytics requested (limited)', array(
            'post_id' => $post_id,
        ));

        return array(
            'views' => 0,
            'forwards' => 0,
            'note' => 'Telegram Bot API does not provide detailed analytics. Use Telegram Analytics web interface.',
        );
    }

    /**
     * Validate content
     */
    public function validate_content($content) {
        // Telegram limits:
        // - Message text: 4096 characters
        // - Caption text: 1024 characters

        $max_length = 4096;

        if (strlen($content) > $max_length) {
            return new WP_Error(
                'content_too_long',
                "Telegram messages are limited to {$max_length} characters. Current: " . strlen($content)
            );
        }

        return true;
    }

    /**
     * Get platform rate limits
     */
    public function get_rate_limits() {
        return array(
            'minute' => 30,  // ~30 messages per second, so 30 per minute is safe
            'hour' => 1000,  // Very generous
            'day' => 10000,  // Very generous
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
            'analytics' => false, // Limited via Bot API
            'polls' => true,
            'pinning' => true,
            'max_text_length' => 4096,
            'max_caption_length' => 1024,
            'max_images' => 10, // Media group
            'max_videos' => 10, // Media group
            'max_file_size' => 2 * 1024 * 1024 * 1024, // 2GB via Bot API
        );
    }

    /**
     * Get channel info
     */
    public function get_channel_info() {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', 'Not authenticated');
        }

        $endpoint = $this->api_url . $this->bot_token . '/getChat';

        $response = $this->make_request($endpoint, array(
            'body' => array(
                'chat_id' => $this->channel_id,
            ),
        ), 'POST');

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $data;
        }

        if (!isset($data['ok']) || !$data['ok']) {
            return new WP_Error('telegram_error', $data['description'] ?? 'Unknown error');
        }

        return $data['result'];
    }

    /**
     * Delete message (bonus feature)
     */
    public function delete_message($message_id) {
        if (!$this->is_authenticated()) {
            return new WP_Error('not_authenticated', 'Not authenticated');
        }

        $endpoint = $this->api_url . $this->bot_token . '/deleteMessage';

        $response = $this->make_request($endpoint, array(
            'body' => array(
                'chat_id' => $this->channel_id,
                'message_id' => $message_id,
            ),
        ));

        $data = $this->parse_json_response($response);

        if (is_wp_error($data)) {
            return $data;
        }

        return !is_wp_error($data) && isset($data['ok']) && $data['ok'];
    }
}
