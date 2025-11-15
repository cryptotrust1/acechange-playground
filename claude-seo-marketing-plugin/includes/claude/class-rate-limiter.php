<?php
/**
 * Rate limiter for Claude API requests.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/claude
 */

/**
 * Implements token bucket algorithm for rate limiting.
 */
class Claude_SEO_Rate_Limiter {

    /**
     * Check if request is allowed.
     *
     * @param int $cost Token cost of the request.
     * @return bool True if allowed.
     */
    public static function allow_request($cost = 1) {
        $bucket = self::get_bucket();
        $settings = get_option('claude_seo_settings', array());
        $capacity = isset($settings['claude_rate_limit_rpm']) ? $settings['claude_rate_limit_rpm'] : 50;

        // Refill tokens based on time elapsed
        $now = time();
        $elapsed = $now - $bucket['last_refill'];
        $refill_rate = $capacity / 60.0; // tokens per second
        $tokens_to_add = $elapsed * $refill_rate;

        $bucket['tokens'] = min($capacity, $bucket['tokens'] + $tokens_to_add);
        $bucket['last_refill'] = $now;

        // Check if enough tokens available
        if ($bucket['tokens'] >= $cost) {
            $bucket['tokens'] -= $cost;
            self::save_bucket($bucket);
            return true;
        }

        Claude_SEO_Logger::warning('Rate limit exceeded', array(
            'tokens_available' => $bucket['tokens'],
            'tokens_required' => $cost
        ));

        return false;
    }

    /**
     * Get current bucket state.
     *
     * @return array Bucket state.
     */
    private static function get_bucket() {
        $settings = get_option('claude_seo_settings', array());
        $capacity = isset($settings['claude_rate_limit_rpm']) ? $settings['claude_rate_limit_rpm'] : 50;

        $bucket = get_transient('claude_seo_rate_limit_bucket');

        if ($bucket === false) {
            $bucket = array(
                'tokens' => $capacity,
                'last_refill' => time()
            );
        }

        return $bucket;
    }

    /**
     * Save bucket state.
     *
     * @param array $bucket Bucket state.
     */
    private static function save_bucket($bucket) {
        set_transient('claude_seo_rate_limit_bucket', $bucket, 60);
    }

    /**
     * Reset rate limiter.
     */
    public static function reset() {
        delete_transient('claude_seo_rate_limit_bucket');
    }

    /**
     * Get time until next token available.
     *
     * @return int Seconds until next token.
     */
    public static function get_retry_after() {
        $bucket = self::get_bucket();
        $settings = get_option('claude_seo_settings', array());
        $capacity = isset($settings['claude_rate_limit_rpm']) ? $settings['claude_rate_limit_rpm'] : 50;
        $refill_rate = $capacity / 60.0;

        if ($bucket['tokens'] >= 1) {
            return 0;
        }

        return ceil((1 - $bucket['tokens']) / $refill_rate);
    }
}
