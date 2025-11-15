<?php
/**
 * Cache management utility.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/utilities
 */

/**
 * Provides caching functionality with fallback support.
 */
class Claude_SEO_Cache {

    /**
     * Cache group name.
     *
     * @var string
     */
    const CACHE_GROUP = 'claude_seo';

    /**
     * Get cached data.
     *
     * @param string $key Cache key.
     * @return mixed|false Cached data or false if not found.
     */
    public static function get($key) {
        $data = wp_cache_get($key, self::CACHE_GROUP);

        // If object cache is not persistent, fall back to transients
        if (false === $data && !wp_using_ext_object_cache()) {
            $data = get_transient(self::get_transient_key($key));
        }

        return $data;
    }

    /**
     * Set cached data.
     *
     * @param string $key        Cache key.
     * @param mixed  $data       Data to cache.
     * @param int    $expiration Expiration time in seconds.
     * @return bool True on success.
     */
    public static function set($key, $data, $expiration = DAY_IN_SECONDS) {
        $result = wp_cache_set($key, $data, self::CACHE_GROUP, $expiration);

        // Also set transient as fallback
        if (!wp_using_ext_object_cache()) {
            set_transient(self::get_transient_key($key), $data, $expiration);
        }

        return $result;
    }

    /**
     * Delete cached data.
     *
     * @param string $key Cache key.
     * @return bool True on success.
     */
    public static function delete($key) {
        $result = wp_cache_delete($key, self::CACHE_GROUP);

        // Also delete transient
        delete_transient(self::get_transient_key($key));

        return $result;
    }

    /**
     * Clear all plugin caches.
     *
     * @return bool True on success.
     */
    public static function clear_all() {
        global $wpdb;

        // Clear object cache (if supported)
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group(self::CACHE_GROUP);
        }

        // Clear transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options}
                 WHERE option_name LIKE %s
                 OR option_name LIKE %s",
                '_transient_claude_seo_%',
                '_transient_timeout_claude_seo_%'
            )
        );

        return true;
    }

    /**
     * Get transient key with prefix.
     *
     * @param string $key Base key.
     * @return string Prefixed key.
     */
    private static function get_transient_key($key) {
        return 'claude_seo_' . $key;
    }

    /**
     * Get or set cached data with callback.
     *
     * @param string   $key        Cache key.
     * @param callable $callback   Function to generate data if not cached.
     * @param int      $expiration Expiration time in seconds.
     * @return mixed Cached or generated data.
     */
    public static function remember($key, $callback, $expiration = DAY_IN_SECONDS) {
        $data = self::get($key);

        if (false !== $data) {
            return $data;
        }

        $data = call_user_func($callback);

        if (false !== $data && null !== $data) {
            self::set($key, $data, $expiration);
        }

        return $data;
    }

    /**
     * Check if cache key exists.
     *
     * @param string $key Cache key.
     * @return bool True if exists.
     */
    public static function has($key) {
        return self::get($key) !== false;
    }

    /**
     * Increment cached value.
     *
     * @param string $key    Cache key.
     * @param int    $offset Increment offset.
     * @return int|false New value or false on failure.
     */
    public static function increment($key, $offset = 1) {
        $value = self::get($key);

        if (false === $value) {
            $value = 0;
        }

        $value = intval($value) + intval($offset);
        self::set($key, $value);

        return $value;
    }

    /**
     * Decrement cached value.
     *
     * @param string $key    Cache key.
     * @param int    $offset Decrement offset.
     * @return int|false New value or false on failure.
     */
    public static function decrement($key, $offset = 1) {
        return self::increment($key, -$offset);
    }
}
