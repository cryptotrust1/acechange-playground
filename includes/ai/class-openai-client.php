<?php
/**
 * OpenAI Client
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_OpenAI_Client {

    private $api_key;
    private $model;
    private $api_url = 'https://api.openai.com/v1/chat/completions';

    public function __construct() {
        $settings = AI_SEO_Manager_Settings::get_instance();
        $this->api_key = $settings->get('openai_api_key');
        $this->model = $settings->get('openai_model', 'gpt-4-turbo-preview');
    }

    /**
     * Chat completion
     */
    public function chat($messages, $args = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'ai-seo-manager'));
        }

        $defaults = array(
            'max_tokens' => 4096,
            'temperature' => 0.7,
            'system' => 'You are a professional SEO expert and content strategist.',
        );

        $args = wp_parse_args($args, $defaults);

        // Format messages
        $formatted_messages = $this->format_messages($messages, $args['system']);

        $body = array(
            'model' => $this->model,
            'messages' => $formatted_messages,
            'max_tokens' => $args['max_tokens'],
            'temperature' => $args['temperature'],
        );

        $response = wp_remote_post($this->api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($body),
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200) {
            return new WP_Error(
                'api_error',
                isset($body['error']['message']) ? $body['error']['message'] : 'Unknown API error',
                $body
            );
        }

        return $this->parse_response($body);
    }

    /**
     * Format messages for OpenAI
     */
    private function format_messages($messages, $system_message = '') {
        $formatted = array();

        // Add system message
        if (!empty($system_message)) {
            $formatted[] = array('role' => 'system', 'content' => $system_message);
        }

        // Add user messages
        if (is_string($messages)) {
            $formatted[] = array('role' => 'user', 'content' => $messages);
        } else {
            foreach ($messages as $message) {
                if (is_string($message)) {
                    $formatted[] = array('role' => 'user', 'content' => $message);
                } else {
                    $formatted[] = $message;
                }
            }
        }

        return $formatted;
    }

    /**
     * Parse response
     */
    private function parse_response($response) {
        if (!isset($response['choices'][0]['message']['content'])) {
            return new WP_Error('invalid_response', 'Invalid API response');
        }

        return array(
            'content' => $response['choices'][0]['message']['content'],
            'model' => $response['model'] ?? $this->model,
            'usage' => $response['usage'] ?? array(),
            'finish_reason' => $response['choices'][0]['finish_reason'] ?? null,
        );
    }

    /**
     * Analyze SEO content
     */
    public function analyze_seo_content($content, $focus_keyword = '') {
        $prompt = "Analyze this content for SEO optimization:\n\n";
        $prompt .= "Content: " . wp_strip_all_tags($content) . "\n\n";

        if (!empty($focus_keyword)) {
            $prompt .= "Focus Keyword: " . $focus_keyword . "\n\n";
        }

        $prompt .= "Provide a JSON response with:\n";
        $prompt .= "- score: SEO score (0-100)\n";
        $prompt .= "- keyword_density: keyword usage analysis\n";
        $prompt .= "- readability: readability score and notes\n";
        $prompt .= "- structure: content structure evaluation\n";
        $prompt .= "- recommendations: array of specific improvements";

        $response = $this->chat($prompt, array(
            'system' => 'You are an expert SEO analyst. Provide analysis in valid JSON format only.',
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $content = $response['content'];
        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            $data = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }

        return array('raw_response' => $content);
    }

    /**
     * Generate meta description
     */
    public function generate_meta_description($content, $focus_keyword = '', $max_length = 160) {
        $prompt = "Generate an SEO-optimized meta description (max {$max_length} characters) for this content:\n\n";
        $prompt .= wp_strip_all_tags($content) . "\n\n";

        if (!empty($focus_keyword)) {
            $prompt .= "Include the keyword: " . $focus_keyword . "\n\n";
        }

        $prompt .= "Return only the meta description text, nothing else.";

        $response = $this->chat($prompt, array(
            'system' => 'You are an SEO copywriter. Generate compelling, keyword-rich meta descriptions.',
            'max_tokens' => 200,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        return trim($response['content']);
    }
}
