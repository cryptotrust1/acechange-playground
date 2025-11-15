<?php
/**
 * Claude AI Client
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Claude_Client {

    private $api_key;
    private $model;
    private $api_url = 'https://api.anthropic.com/v1/messages';
    private $api_version = '2023-06-01';

    public function __construct() {
        $settings = AI_SEO_Manager_Settings::get_instance();
        $this->api_key = $settings->get('claude_api_key');
        $this->model = $settings->get('claude_model', 'claude-3-5-sonnet-20241022');
    }

    /**
     * Odoslanie požiadavky na Claude API
     */
    public function chat($messages, $args = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Claude API key is not configured', 'ai-seo-manager'));
        }

        $defaults = array(
            'max_tokens' => 4096,
            'temperature' => 0.7,
            'system' => 'You are a professional SEO expert and content strategist.',
        );

        $args = wp_parse_args($args, $defaults);

        // Priprav správy
        $formatted_messages = $this->format_messages($messages);

        $body = array(
            'model' => $this->model,
            'max_tokens' => $args['max_tokens'],
            'temperature' => $args['temperature'],
            'messages' => $formatted_messages,
        );

        if (!empty($args['system'])) {
            $body['system'] = $args['system'];
        }

        $response = wp_remote_post($this->api_url, array(
            'headers' => array(
                'x-api-key' => $this->api_key,
                'anthropic-version' => $this->api_version,
                'content-type' => 'application/json',
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
     * Formátovanie správ pre Claude API
     */
    private function format_messages($messages) {
        if (is_string($messages)) {
            return array(
                array('role' => 'user', 'content' => $messages)
            );
        }

        $formatted = array();
        foreach ($messages as $message) {
            if (is_string($message)) {
                $formatted[] = array('role' => 'user', 'content' => $message);
            } else {
                $formatted[] = $message;
            }
        }

        return $formatted;
    }

    /**
     * Parsovanie odpovede
     */
    private function parse_response($response) {
        if (!isset($response['content']) || empty($response['content'])) {
            return new WP_Error('invalid_response', 'Invalid API response');
        }

        $content = '';
        foreach ($response['content'] as $block) {
            if ($block['type'] === 'text') {
                $content .= $block['text'];
            }
        }

        return array(
            'content' => $content,
            'model' => $response['model'] ?? $this->model,
            'usage' => $response['usage'] ?? array(),
            'stop_reason' => $response['stop_reason'] ?? null,
        );
    }

    /**
     * Analýza SEO obsahu
     */
    public function analyze_seo_content($content, $focus_keyword = '') {
        $prompt = "Analyze this content for SEO optimization:\n\n";
        $prompt .= "Content: " . wp_strip_all_tags($content) . "\n\n";

        if (!empty($focus_keyword)) {
            $prompt .= "Focus Keyword: " . $focus_keyword . "\n\n";
        }

        $prompt .= "Provide:\n";
        $prompt .= "1. SEO Score (0-100)\n";
        $prompt .= "2. Keyword density analysis\n";
        $prompt .= "3. Readability assessment\n";
        $prompt .= "4. Content structure evaluation\n";
        $prompt .= "5. Specific recommendations for improvement\n\n";
        $prompt .= "Return response in JSON format with keys: score, keyword_density, readability, structure, recommendations";

        $response = $this->chat($prompt, array(
            'system' => 'You are an expert SEO analyst. Analyze content and provide actionable insights in JSON format.',
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        // Pokus sa extrahovať JSON z odpovede
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
     * Generovanie SEO odporúčaní
     */
    public function generate_recommendations($analysis_data, $context = array()) {
        $prompt = "Based on this SEO analysis data, generate specific, actionable recommendations:\n\n";
        $prompt .= json_encode($analysis_data, JSON_PRETTY_PRINT) . "\n\n";

        if (!empty($context)) {
            $prompt .= "Additional context:\n" . json_encode($context, JSON_PRETTY_PRINT) . "\n\n";
        }

        $prompt .= "For each recommendation provide:\n";
        $prompt .= "1. Title (short, actionable)\n";
        $prompt .= "2. Description (what and why)\n";
        $prompt .= "3. Priority (critical, high, medium, low)\n";
        $prompt .= "4. Estimated impact (1-10)\n";
        $prompt .= "5. Specific action to take\n\n";
        $prompt .= "Return as JSON array of recommendations";

        $response = $this->chat($prompt, array(
            'system' => 'You are a professional SEO consultant providing strategic recommendations.',
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $content = $response['content'];
        if (preg_match('/\[[\s\S]*\]/', $content, $matches)) {
            $data = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }

        return array();
    }

    /**
     * Generovanie optimalizovaného obsahu
     */
    public function optimize_content($original_content, $recommendations) {
        $prompt = "Optimize this content based on SEO recommendations:\n\n";
        $prompt .= "Original content:\n" . $original_content . "\n\n";
        $prompt .= "Recommendations:\n" . json_encode($recommendations, JSON_PRETTY_PRINT) . "\n\n";
        $prompt .= "Return the optimized version maintaining the original tone and style.";

        $response = $this->chat($prompt, array(
            'system' => 'You are an expert content optimizer. Improve SEO while maintaining quality and readability.',
            'max_tokens' => 8000,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        return $response['content'];
    }

    /**
     * Generovanie meta description
     */
    public function generate_meta_description($content, $focus_keyword = '', $max_length = 160) {
        $prompt = "Generate an SEO-optimized meta description (max {$max_length} characters) for:\n\n";
        $prompt .= wp_strip_all_tags($content) . "\n\n";

        if (!empty($focus_keyword)) {
            $prompt .= "Focus keyword: " . $focus_keyword . "\n\n";
        }

        $prompt .= "Return only the meta description, no explanations.";

        $response = $this->chat($prompt, array(
            'system' => 'You are an SEO copywriter specializing in compelling meta descriptions.',
            'max_tokens' => 200,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        return trim($response['content']);
    }
}
