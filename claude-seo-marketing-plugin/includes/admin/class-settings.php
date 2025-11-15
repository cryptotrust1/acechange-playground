<?php
/**
 * Settings management.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/admin
 */

/**
 * Handles plugin settings.
 */
class Claude_SEO_Settings {

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting(
            'claude_seo_settings',
            'claude_seo_settings',
            array($this, 'sanitize_settings')
        );

        // General Section
        add_settings_section(
            'claude_seo_general',
            __('General Settings', 'claude-seo'),
            array($this, 'section_general_callback'),
            'claude-seo-settings'
        );

        // Claude API Section
        add_settings_section(
            'claude_seo_api',
            __('Claude API Settings', 'claude-seo'),
            array($this, 'section_api_callback'),
            'claude-seo-settings'
        );

        // Add fields
        $this->add_settings_fields();
    }

    /**
     * Add settings fields.
     */
    private function add_settings_fields() {
        // API Key field
        add_settings_field(
            'claude_api_key',
            __('Claude API Key', 'claude-seo'),
            array($this, 'field_api_key'),
            'claude-seo-settings',
            'claude_seo_api'
        );

        // Model Selection
        add_settings_field(
            'claude_model_default',
            __('Default Model', 'claude-seo'),
            array($this, 'field_model'),
            'claude-seo-settings',
            'claude_seo_api'
        );

        // Cache Enable
        add_settings_field(
            'claude_cache_enabled',
            __('Enable Caching', 'claude-seo'),
            array($this, 'field_cache_enabled'),
            'claude-seo-settings',
            'claude_seo_api'
        );
    }

    /**
     * General section callback.
     */
    public function section_general_callback() {
        echo '<p>' . esc_html__('Configure general plugin settings.', 'claude-seo') . '</p>';
    }

    /**
     * API section callback.
     */
    public function section_api_callback() {
        echo '<p>' . esc_html__('Configure Claude API integration.', 'claude-seo') . '</p>';
    }

    /**
     * API Key field.
     */
    public function field_api_key() {
        $api_key = Claude_SEO_Encryption::get_api_key();
        $has_key = !empty($api_key);

        echo '<input type="password" name="claude_api_key" value="" placeholder="' . esc_attr__('sk-ant-api03-...', 'claude-seo') . '" class="regular-text">';

        if ($has_key) {
            echo '<p class="description">' . esc_html__('API key is set. Enter a new key to update.', 'claude-seo') . '</p>';
        } else {
            echo '<p class="description">' . esc_html__('Enter your Claude API key from console.anthropic.com', 'claude-seo') . '</p>';
        }
    }

    /**
     * Model field.
     */
    public function field_model() {
        $settings = get_option('claude_seo_settings', array());
        $model = isset($settings['claude_model_default']) ? $settings['claude_model_default'] : 'claude-sonnet-4-5-20250929';

        $models = array(
            'claude-sonnet-4-5-20250929' => 'Claude Sonnet 4.5 (Recommended)',
            'claude-haiku-4-5-20250930' => 'Claude Haiku 4.5 (Fast & Cheap)',
            'claude-opus-4-20250514' => 'Claude Opus 4 (Premium)'
        );

        echo '<select name="claude_seo_settings[claude_model_default]">';
        foreach ($models as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($model, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';
    }

    /**
     * Cache enabled field.
     */
    public function field_cache_enabled() {
        $settings = get_option('claude_seo_settings', array());
        $enabled = isset($settings['claude_cache_enabled']) ? $settings['claude_cache_enabled'] : true;

        printf(
            '<input type="checkbox" name="claude_seo_settings[claude_cache_enabled]" value="1" %s>',
            checked($enabled, true, false)
        );
        echo '<p class="description">' . esc_html__('Cache API responses to reduce costs (recommended)', 'claude-seo') . '</p>';
    }

    /**
     * Sanitize settings.
     */
    public function sanitize_settings($input) {
        // Handle API key separately (encrypt)
        if (isset($_POST['claude_api_key']) && !empty($_POST['claude_api_key'])) {
            $api_key = sanitize_text_field($_POST['claude_api_key']);

            if (Claude_SEO_Encryption::validate_api_key_format($api_key)) {
                Claude_SEO_Encryption::store_api_key($api_key);
            } else {
                add_settings_error(
                    'claude_seo_settings',
                    'invalid_api_key',
                    __('Invalid API key format', 'claude-seo')
                );
            }
        }

        // Sanitize other settings
        return Claude_SEO_Sanitizer::sanitize_settings($input);
    }
}
