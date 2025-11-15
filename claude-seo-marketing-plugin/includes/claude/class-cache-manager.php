<?php
/**
 * Cache manager for Claude API responses.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/claude
 */

/**
 * Manages caching of Claude API responses.
 */
class Claude_SEO_Claude_Cache_Manager {

    /**
     * Get cached response.
     *
     * @param string $prompt     The prompt.
     * @param array  $parameters Additional parameters.
     * @return mixed|false Cached response or false.
     */
    public static function get_cached_response($prompt, $parameters = array()) {
        $settings = get_option('claude_seo_settings', array());

        if (empty($settings['claude_cache_enabled'])) {
            return false;
        }

        $cache_key = self::generate_cache_key($prompt, $parameters);
        return Claude_SEO_Cache::get($cache_key);
    }

    /**
     * Cache a response.
     *
     * @param string $prompt     The prompt.
     * @param array  $parameters Additional parameters.
     * @param mixed  $response   The response to cache.
     * @param int    $duration   Cache duration in seconds.
     * @return bool True on success.
     */
    public static function cache_response($prompt, $parameters, $response, $duration = DAY_IN_SECONDS) {
        $settings = get_option('claude_seo_settings', array());

        if (empty($settings['claude_cache_enabled'])) {
            return false;
        }

        $cache_key = self::generate_cache_key($prompt, $parameters);
        return Claude_SEO_Cache::set($cache_key, $response, $duration);
    }

    /**
     * Generate cache key from prompt and parameters.
     *
     * @param string $prompt     The prompt.
     * @param array  $parameters Additional parameters.
     * @return string Cache key.
     */
    private static function generate_cache_key($prompt, $parameters) {
        $key_data = array(
            'prompt' => $prompt,
            'params' => $parameters
        );

        return 'claude_response_' . md5(wp_json_encode($key_data));
    }

    /**
     * Clear all Claude cache.
     *
     * @return bool True on success.
     */
    public static function clear_all_cache() {
        global $wpdb;

        // Clear transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_claude_seo_claude_response_%'
             OR option_name LIKE '_transient_timeout_claude_seo_claude_response_%'"
        );

        return true;
    }

    /**
     * Get cache statistics.
     *
     * @return array Cache stats.
     */
    public static function get_cache_stats() {
        global $wpdb;

        $count = $wpdb->get_var(
            "SELECT COUNT(*)
             FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_claude_seo_claude_response_%'"
        );

        return array(
            'cached_responses' => (int) $count,
            'cache_enabled' => self::is_cache_enabled()
        );
    }

    /**
     * Check if caching is enabled.
     *
     * @return bool True if enabled.
     */
    public static function is_cache_enabled() {
        $settings = get_option('claude_seo_settings', array());
        return !empty($settings['claude_cache_enabled']);
    }
}
