<?php
/**
 * Input sanitization utility.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/utilities
 */

/**
 * Provides comprehensive input sanitization for all plugin inputs.
 */
class Claude_SEO_Sanitizer {

    /**
     * Sanitize settings array.
     *
     * @param array $settings Raw settings array.
     * @return array Sanitized settings.
     */
    public static function sanitize_settings($settings) {
        $sanitized = array();

        // Define sanitization rules for each setting
        $rules = array(
            'site_name' => 'sanitize_text_field',
            'separator' => 'sanitize_text_field',
            'homepage_title' => 'sanitize_text_field',
            'homepage_description' => 'sanitize_textarea_field',
            'keyword_density_min' => 'floatval',
            'keyword_density_max' => 'floatval',
            'readability_target' => 'absint',
            'min_internal_links' => 'absint',
            'min_content_length' => 'absint',
            'sitemap_enabled' => 'boolval',
            'og_enabled' => 'boolval',
            'twitter_enabled' => 'boolval',
            '404_monitoring_enabled' => 'boolval',
            '404_log_retention_days' => 'absint',
            '404_email_notifications' => 'boolval',
            'cache_enabled' => 'boolval',
            'cache_duration' => 'absint',
            'claude_rate_limit_rpm' => 'absint',
            'claude_cost_budget_monthly' => 'floatval',
            'claude_cache_enabled' => 'boolval',
        );

        foreach ($rules as $key => $callback) {
            if (isset($settings[$key])) {
                $sanitized[$key] = call_user_func($callback, $settings[$key]);
            }
        }

        // Handle arrays
        if (isset($settings['sitemap_post_types']) && is_array($settings['sitemap_post_types'])) {
            $sanitized['sitemap_post_types'] = array_map('sanitize_key', $settings['sitemap_post_types']);
        }

        if (isset($settings['sitemap_taxonomies']) && is_array($settings['sitemap_taxonomies'])) {
            $sanitized['sitemap_taxonomies'] = array_map('sanitize_key', $settings['sitemap_taxonomies']);
        }

        if (isset($settings['sitemap_exclude_ids']) && is_array($settings['sitemap_exclude_ids'])) {
            $sanitized['sitemap_exclude_ids'] = array_map('absint', $settings['sitemap_exclude_ids']);
        }

        // Handle schema organization data
        if (isset($settings['schema_organization']) && is_array($settings['schema_organization'])) {
            $sanitized['schema_organization'] = self::sanitize_schema_organization($settings['schema_organization']);
        }

        // Sanitize Claude model selection
        if (isset($settings['claude_model_default'])) {
            $allowed_models = array(
                'claude-sonnet-4-5-20250929',
                'claude-haiku-4-5-20250930',
                'claude-opus-4-20250514'
            );
            $sanitized['claude_model_default'] = in_array($settings['claude_model_default'], $allowed_models, true)
                ? $settings['claude_model_default']
                : 'claude-sonnet-4-5-20250929';
        }

        // Sanitize Twitter card type
        if (isset($settings['twitter_card_type'])) {
            $allowed_types = array('summary', 'summary_large_image', 'player');
            $sanitized['twitter_card_type'] = in_array($settings['twitter_card_type'], $allowed_types, true)
                ? $settings['twitter_card_type']
                : 'summary_large_image';
        }

        // Sanitize URLs
        if (isset($settings['og_default_image'])) {
            $sanitized['og_default_image'] = esc_url_raw($settings['og_default_image']);
        }

        if (isset($settings['twitter_site'])) {
            $sanitized['twitter_site'] = sanitize_text_field($settings['twitter_site']);
        }

        // Sanitize robots.txt
        if (isset($settings['robots_txt_custom'])) {
            $sanitized['robots_txt_custom'] = sanitize_textarea_field($settings['robots_txt_custom']);
        }

        return $sanitized;
    }

    /**
     * Sanitize schema organization data.
     *
     * @param array $data Raw organization data.
     * @return array Sanitized data.
     */
    private static function sanitize_schema_organization($data) {
        return array(
            'name' => isset($data['name']) ? sanitize_text_field($data['name']) : '',
            'url' => isset($data['url']) ? esc_url_raw($data['url']) : '',
            'logo' => isset($data['logo']) ? esc_url_raw($data['logo']) : '',
            'social_profiles' => isset($data['social_profiles']) && is_array($data['social_profiles'])
                ? array_map('esc_url_raw', $data['social_profiles'])
                : array()
        );
    }

    /**
     * Sanitize post meta data.
     *
     * @param string $key   Meta key.
     * @param mixed  $value Meta value.
     * @return mixed Sanitized value.
     */
    public static function sanitize_post_meta($key, $value) {
        switch ($key) {
            case '_seo_title':
                return self::sanitize_title($value);

            case '_seo_description':
                return self::sanitize_description($value);

            case '_seo_keywords':
                return sanitize_text_field($value);

            case '_seo_canonical':
                return esc_url_raw($value);

            case '_seo_robots':
                return self::sanitize_robots_meta($value);

            case '_seo_og_title':
            case '_seo_og_description':
                return sanitize_text_field($value);

            case '_seo_og_image':
                return absint($value);

            case '_seo_twitter_card':
                $allowed = array('summary', 'summary_large_image', 'player');
                return in_array($value, $allowed, true) ? $value : 'summary_large_image';

            case '_seo_schema_type':
                return sanitize_key($value);

            case '_seo_score':
            case '_seo_readability_score':
                return min(100, max(0, absint($value)));

            case '_seo_keyword_density':
                return floatval($value);

            case '_seo_ai_enhanced':
                return (bool) $value;

            default:
                return sanitize_text_field($value);
        }
    }

    /**
     * Sanitize SEO title (max 60 chars).
     *
     * @param string $title Raw title.
     * @return string Sanitized title.
     */
    public static function sanitize_title($title) {
        $title = sanitize_text_field($title);
        return mb_substr($title, 0, 60);
    }

    /**
     * Sanitize meta description (max 160 chars).
     *
     * @param string $description Raw description.
     * @return string Sanitized description.
     */
    public static function sanitize_description($description) {
        $description = sanitize_textarea_field($description);
        return mb_substr($description, 0, 160);
    }

    /**
     * Sanitize robots meta directive.
     *
     * @param string $robots Raw robots value.
     * @return string Sanitized robots value.
     */
    private static function sanitize_robots_meta($robots) {
        $allowed = array('index', 'noindex', 'follow', 'nofollow', 'noarchive', 'nosnippet');
        $values = array_map('trim', explode(',', $robots));
        $sanitized = array_filter($values, function($value) use ($allowed) {
            return in_array($value, $allowed, true);
        });
        return implode(',', $sanitized);
    }

    /**
     * Sanitize keyword (remove special chars, max 255 chars).
     *
     * @param string $keyword Raw keyword.
     * @return string Sanitized keyword.
     */
    public static function sanitize_keyword($keyword) {
        $keyword = sanitize_text_field($keyword);
        return mb_substr($keyword, 0, 255);
    }

    /**
     * Sanitize URL for redirect source/target.
     *
     * @param string $url Raw URL.
     * @return string Sanitized URL.
     */
    public static function sanitize_redirect_url($url) {
        // Allow relative URLs
        if (substr($url, 0, 1) === '/') {
            return sanitize_text_field($url);
        }
        return esc_url_raw($url);
    }

    /**
     * Sanitize JSON data.
     *
     * @param mixed $data Data to sanitize.
     * @return array|string Sanitized and encoded JSON.
     */
    public static function sanitize_json($data) {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (!is_array($data)) {
            return '{}';
        }

        return wp_json_encode($data);
    }

    /**
     * Validate and sanitize nonce.
     *
     * @param string $nonce_value Nonce value.
     * @param string $nonce_name  Nonce action name.
     * @return bool True if valid.
     */
    public static function verify_nonce($nonce_value, $nonce_name) {
        if (!isset($nonce_value)) {
            return false;
        }

        return wp_verify_nonce($nonce_value, $nonce_name) !== false;
    }

    /**
     * Validate user capability.
     *
     * @param string $capability Required capability.
     * @param int    $post_id    Optional post ID for post-specific checks.
     * @return bool True if user has capability.
     */
    public static function check_capability($capability, $post_id = 0) {
        if ($post_id > 0) {
            return current_user_can($capability, $post_id);
        }

        return current_user_can($capability);
    }
}
