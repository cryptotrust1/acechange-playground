<?php
/**
 * AI Manager - centrálna správa AI služieb
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_AI_Manager {

    private static $instance = null;
    private $claude_client;
    private $openai_client;
    private $settings;
    private $active_provider;
    private $db;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->settings = AI_SEO_Manager_Settings::get_instance();
        $this->active_provider = $this->settings->get('ai_provider', 'claude');

        // Initialize clients
        $this->claude_client = new AI_SEO_Manager_Claude_Client();
        $this->openai_client = new AI_SEO_Manager_OpenAI_Client();
        $this->db = AI_SEO_Manager_Database::get_instance();
    }

    /**
     * Získanie aktívneho AI klienta
     */
    private function get_client($prefer_provider = null) {
        $provider = $prefer_provider ?? $this->active_provider;

        switch ($provider) {
            case 'openai':
                return $this->openai_client;
            case 'claude':
            default:
                return $this->claude_client;
        }
    }

    /**
     * Fallback na alternatívneho providera
     */
    private function get_fallback_client($current_provider) {
        if ($this->active_provider !== 'both') {
            return null;
        }

        return $current_provider === 'claude' ? $this->openai_client : $this->claude_client;
    }

    /**
     * Analýza SEO obsahu s automatickým fallbackom
     */
    public function analyze_seo_content($content, $focus_keyword = '', $provider = null) {
        $client = $this->get_client($provider);
        $result = $client->analyze_seo_content($content, $focus_keyword);

        // Pokus o fallback pri chybe
        if (is_wp_error($result)) {
            $primary_error = $result->get_error_message();
            $fallback_client = $this->get_fallback_client($provider ?? $this->active_provider);
            if ($fallback_client) {
                $result = $fallback_client->analyze_seo_content($content, $focus_keyword);

                // Log ak aj fallback zlyhal
                if (is_wp_error($result)) {
                    $this->db->log('ai_provider_failure',
                        'Both AI providers failed for analyze_seo_content',
                        array(
                            'primary_error' => $primary_error,
                            'fallback_error' => $result->get_error_message(),
                            'keyword' => $focus_keyword
                        )
                    );
                }
            } else {
                // Log ak nie je dostupný fallback
                $this->db->log('ai_provider_failure',
                    'AI provider failed and no fallback available',
                    array('error' => $primary_error, 'keyword' => $focus_keyword)
                );
            }
        }

        return $result;
    }

    /**
     * Generovanie SEO odporúčaní
     */
    public function generate_recommendations($analysis_data, $context = array(), $provider = null) {
        $client = $this->get_client($provider);

        if (method_exists($client, 'generate_recommendations')) {
            $result = $client->generate_recommendations($analysis_data, $context);

            if (is_wp_error($result)) {
                $fallback_client = $this->get_fallback_client($provider ?? $this->active_provider);
                if ($fallback_client && method_exists($fallback_client, 'generate_recommendations')) {
                    $result = $fallback_client->generate_recommendations($analysis_data, $context);
                }
            }

            return $result;
        }

        return array();
    }

    /**
     * Generovanie meta description
     */
    public function generate_meta_description($content, $focus_keyword = '', $max_length = 160) {
        $client = $this->get_client();
        $result = $client->generate_meta_description($content, $focus_keyword, $max_length);

        if (is_wp_error($result)) {
            $fallback_client = $this->get_fallback_client($this->active_provider);
            if ($fallback_client) {
                $result = $fallback_client->generate_meta_description($content, $focus_keyword, $max_length);
            }
        }

        return $result;
    }

    /**
     * Optimalizácia obsahu
     */
    public function optimize_content($original_content, $recommendations) {
        $client = $this->get_client();

        if (method_exists($client, 'optimize_content')) {
            $result = $client->optimize_content($original_content, $recommendations);

            if (is_wp_error($result)) {
                $fallback_client = $this->get_fallback_client($this->active_provider);
                if ($fallback_client && method_exists($fallback_client, 'optimize_content')) {
                    $result = $fallback_client->optimize_content($original_content, $recommendations);
                }
            }

            return $result;
        }

        return $original_content;
    }

    /**
     * Generovanie ALT textov pre obrázky
     */
    public function generate_alt_text($image_context, $focus_keyword = '') {
        $prompt = "Generate a concise, SEO-friendly ALT text for an image in this context:\n\n";
        $prompt .= $image_context . "\n\n";

        if (!empty($focus_keyword)) {
            $prompt .= "Consider including the keyword: " . $focus_keyword . "\n\n";
        }

        $prompt .= "Return only the ALT text (max 125 characters), no explanations.";

        $client = $this->get_client();
        $result = $client->chat($prompt, array(
            'system' => 'You are an SEO specialist. Generate descriptive, keyword-rich ALT texts for images.',
            'max_tokens' => 100,
        ));

        if (is_wp_error($result)) {
            return '';
        }

        return trim($result['content']);
    }

    /**
     * Analýza konkurencie
     */
    public function analyze_competitors($keyword, $competitor_urls = array()) {
        $prompt = "Analyze SEO strategy for keyword: '{$keyword}'\n\n";

        if (!empty($competitor_urls)) {
            $prompt .= "Competitor URLs:\n";
            foreach ($competitor_urls as $url) {
                $prompt .= "- {$url}\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "Provide:\n";
        $prompt .= "1. Keyword difficulty estimate (0-100)\n";
        $prompt .= "2. Content gaps we should fill\n";
        $prompt .= "3. Backlink strategy recommendations\n";
        $prompt .= "4. Content length and depth recommendations\n";
        $prompt .= "5. Technical SEO considerations\n\n";
        $prompt .= "Return as JSON with keys: difficulty, content_gaps, backlink_strategy, content_recommendations, technical_seo";

        $client = $this->get_client();
        $result = $client->chat($prompt, array(
            'system' => 'You are a competitive SEO analyst with deep market knowledge.',
        ));

        if (is_wp_error($result)) {
            return $result;
        }

        $content = $result['content'];
        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            $data = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }

        return array('raw_response' => $content);
    }

    /**
     * Generovanie nadpisov
     */
    public function generate_headings($content, $focus_keyword = '', $count = 3) {
        $prompt = "Generate {$count} SEO-optimized H2 or H3 headings for this content:\n\n";
        $prompt .= wp_strip_all_tags($content) . "\n\n";

        if (!empty($focus_keyword)) {
            $prompt .= "Focus keyword: " . $focus_keyword . "\n\n";
        }

        $prompt .= "Return as JSON array of headings with keys: text, level (2 or 3)";

        $client = $this->get_client();
        $result = $client->chat($prompt, array(
            'system' => 'You are an SEO content strategist. Create engaging, keyword-rich headings.',
        ));

        if (is_wp_error($result)) {
            return array();
        }

        $content = $result['content'];
        if (preg_match('/\[[\s\S]*\]/', $content, $matches)) {
            $data = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }

        return array();
    }

    /**
     * Public wrapper pre chat metódu s automatickým fallbackom
     */
    public function chat($prompt, $options = array(), $provider = null) {
        $client = $this->get_client($provider);
        $result = $client->chat($prompt, $options);

        if (is_wp_error($result)) {
            $fallback_client = $this->get_fallback_client($provider ?? $this->active_provider);
            if ($fallback_client) {
                $result = $fallback_client->chat($prompt, $options);
            }
        }

        return $result;
    }

    /**
     * Získanie štatistík použitia API
     */
    public function get_usage_stats() {
        $stats = get_option('ai_seo_manager_api_usage', array(
            'total_calls' => 0,
            'calls_today' => 0,
            'last_reset' => date('Y-m-d'),
            'by_provider' => array(
                'claude' => 0,
                'openai' => 0,
            ),
        ));

        // Reset daily counter
        if ($stats['last_reset'] !== date('Y-m-d')) {
            $stats['calls_today'] = 0;
            $stats['last_reset'] = date('Y-m-d');
            update_option('ai_seo_manager_api_usage', $stats);
        }

        return $stats;
    }

    /**
     * Tracking API volania
     */
    public function track_api_call($provider = null) {
        $provider = $provider ?? $this->active_provider;
        $stats = $this->get_usage_stats();

        $stats['total_calls']++;
        $stats['calls_today']++;

        if (isset($stats['by_provider'][$provider])) {
            $stats['by_provider'][$provider]++;
        }

        update_option('ai_seo_manager_api_usage', $stats);

        return $stats;
    }

    /**
     * Kontrola limitu API volaní
     */
    public function check_api_limit() {
        $stats = $this->get_usage_stats();
        $max_calls = $this->settings->get('max_api_calls_per_day', 100);

        return $stats['calls_today'] < $max_calls;
    }
}
