<?php
/**
 * Správa nastavení pluginu
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Settings {

    private static $instance = null;
    private $option_name = 'ai_seo_manager_settings';
    private $settings = array();

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_settings();
    }

    /**
     * Načítanie nastavení
     */
    private function load_settings() {
        $this->settings = get_option($this->option_name, array());
    }

    /**
     * Nastavenie default hodnôt
     */
    public function set_default_settings() {
        $defaults = array(
            // AI Nastavenia
            'ai_provider' => 'claude', // claude, openai, both
            'claude_api_key' => '',
            'claude_model' => 'claude-3-5-sonnet-20241022',
            'openai_api_key' => '',
            'openai_model' => 'gpt-4-turbo-preview',

            // Google Analytics
            'ga4_measurement_id' => '',
            'ga4_api_secret' => '',

            // Google Search Console
            'gsc_client_id' => '',
            'gsc_client_secret' => '',
            'gsc_access_token' => '',
            'gsc_refresh_token' => '',

            // SEO Analysis
            'auto_analysis' => true,
            'analysis_frequency' => 'daily', // daily, weekly, manual
            'min_seo_score' => 70,

            // AI Manager
            'ai_manager_enabled' => true,
            'recommendation_threshold' => 0.7, // AI confidence threshold
            'auto_prioritize' => true,

            // Auto-pilot
            'autopilot_enabled' => false,
            'autopilot_mode' => 'approval', // approval, auto, disabled
            'autopilot_actions' => array(
                'meta_description' => true,
                'alt_texts' => true,
                'headings' => true,
                'internal_links' => false,
            ),

            // Advanced
            'max_api_calls_per_day' => 100,
            'cache_duration' => 3600,
            'debug_mode' => false,
        );

        $current = $this->get_all();
        $merged = wp_parse_args($current, $defaults);

        update_option($this->option_name, $merged);
        $this->settings = $merged;

        return true;
    }

    /**
     * Získanie hodnoty nastavenia
     */
    public function get($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    /**
     * Nastavenie hodnoty
     */
    public function set($key, $value) {
        $this->settings[$key] = $value;
        return update_option($this->option_name, $this->settings);
    }

    /**
     * Získanie všetkých nastavení
     */
    public function get_all() {
        return $this->settings;
    }

    /**
     * Uloženie všetkých nastavení
     */
    public function save($settings) {
        $this->settings = $settings;
        return update_option($this->option_name, $settings);
    }

    /**
     * Validácia API kľúča
     */
    public function validate_api_key($provider, $api_key) {
        if (empty($api_key)) {
            return false;
        }

        switch ($provider) {
            case 'claude':
                return $this->validate_claude_api_key($api_key);
            case 'openai':
                return $this->validate_openai_api_key($api_key);
            default:
                return false;
        }
    }

    /**
     * Validácia Claude API kľúča
     */
    private function validate_claude_api_key($api_key) {
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'claude-3-5-sonnet-20241022',
                'max_tokens' => 10,
                'messages' => array(
                    array('role' => 'user', 'content' => 'Hi')
                ),
            )),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        return $code === 200;
    }

    /**
     * Validácia OpenAI API kľúča
     */
    private function validate_openai_api_key($api_key) {
        $response = wp_remote_get('https://api.openai.com/v1/models', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'timeout' => 15,
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        return $code === 200;
    }

    /**
     * Kontrola, či sú API kľúče nastavené
     */
    public function has_valid_api_keys() {
        $provider = $this->get('ai_provider', 'claude');

        if ($provider === 'claude' || $provider === 'both') {
            if (empty($this->get('claude_api_key'))) {
                return false;
            }
        }

        if ($provider === 'openai' || $provider === 'both') {
            if (empty($this->get('openai_api_key'))) {
                return false;
            }
        }

        return true;
    }
}
