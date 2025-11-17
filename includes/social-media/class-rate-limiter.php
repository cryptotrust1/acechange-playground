<?php
/**
 * Rate Limiter
 * Sledovanie a enforcement API rate limits pre každú platformu
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Social_Rate_Limiter {

    private static $instance = null;
    private $logger;
    private $transient_prefix = 'ai_seo_social_rate_';

    // Default limits (can be overridden by platform)
    private $default_limits = array(
        'calls_per_minute' => 30,
        'calls_per_hour' => 1000,
        'calls_per_day' => 10000,
    );

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize debug logger if available
        if (class_exists('AI_SEO_Manager_Debug_Logger')) {
            $this->logger = AI_SEO_Manager_Debug_Logger::get_instance();
        }
    }

    /**
     * Kontrola či môžeme vykonať API call
     */
    public function check_limit($platform, $action = 'default') {
        $limits = $this->get_platform_limits($platform);

        // Check each time window
        foreach ($limits as $window => $max_calls) {
            $current = $this->get_current_count($platform, $action, $window);

            if ($current >= $max_calls) {
                if ($this->logger) {
                    $this->logger->warning('Rate limit reached', array(
                        'platform' => $platform,
                        'action' => $action,
                        'window' => $window,
                        'current' => $current,
                        'limit' => $max_calls,
                    ));
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Increment counter po API call
     */
    public function increment($platform, $action = 'default') {
        $windows = array('minute', 'hour', 'day');

        foreach ($windows as $window) {
            $key = $this->get_transient_key($platform, $action, $window);
            $current = (int) get_transient($key);
            $new_value = $current + 1;

            $expiration = $this->get_window_expiration($window);
            set_transient($key, $new_value, $expiration);

            if ($this->logger) {
                $this->logger->debug('Rate limit incremented', array(
                    'platform' => $platform,
                    'action' => $action,
                    'window' => $window,
                    'count' => $new_value,
                ));
            }
        }

        return true;
    }

    /**
     * Získanie aktuálneho počtu volaní
     */
    public function get_current_count($platform, $action, $window) {
        $key = $this->get_transient_key($platform, $action, $window);
        return (int) get_transient($key);
    }

    /**
     * Získanie zostávajúcich volaní
     */
    public function get_remaining($platform, $action = 'default') {
        $limits = $this->get_platform_limits($platform);
        $remaining = array();

        foreach ($limits as $window => $max_calls) {
            $current = $this->get_current_count($platform, $action, $window);
            $remaining[$window] = max(0, $max_calls - $current);
        }

        return $remaining;
    }

    /**
     * Reset counters pre platformu
     */
    public function reset($platform, $action = 'default') {
        $windows = array('minute', 'hour', 'day');

        foreach ($windows as $window) {
            $key = $this->get_transient_key($platform, $action, $window);
            delete_transient($key);
        }

        if ($this->logger) {
            $this->logger->info('Rate limits reset', array(
                'platform' => $platform,
                'action' => $action,
            ));
        }

        return true;
    }

    /**
     * Získanie času do resetu
     */
    public function get_reset_time($platform, $action = 'default', $window = 'minute') {
        $key = $this->get_transient_key($platform, $action, $window);
        $timeout = get_option('_transient_timeout_' . $key);

        if (!$timeout) {
            return 0;
        }

        return max(0, $timeout - time());
    }

    /**
     * Získanie platform limits
     */
    private function get_platform_limits($platform) {
        // Get custom limits from platform settings or use defaults
        $custom_limits = get_option('ai_seo_social_limits_' . $platform, array());

        return wp_parse_args($custom_limits, array(
            'minute' => $this->default_limits['calls_per_minute'],
            'hour' => $this->default_limits['calls_per_hour'],
            'day' => $this->default_limits['calls_per_day'],
        ));
    }

    /**
     * Nastavenie custom limits pre platformu
     */
    public function set_platform_limits($platform, $limits) {
        $valid_limits = array();

        if (isset($limits['minute']) && $limits['minute'] > 0) {
            $valid_limits['minute'] = (int) $limits['minute'];
        }
        if (isset($limits['hour']) && $limits['hour'] > 0) {
            $valid_limits['hour'] = (int) $limits['hour'];
        }
        if (isset($limits['day']) && $limits['day'] > 0) {
            $valid_limits['day'] = (int) $limits['day'];
        }

        update_option('ai_seo_social_limits_' . $platform, $valid_limits);

        if ($this->logger) {
            $this->logger->info('Platform limits updated', array(
                'platform' => $platform,
                'limits' => $valid_limits,
            ));
        }

        return true;
    }

    /**
     * Získanie transient key
     */
    private function get_transient_key($platform, $action, $window) {
        return $this->transient_prefix . $platform . '_' . $action . '_' . $window;
    }

    /**
     * Získanie expirácie podľa window
     */
    private function get_window_expiration($window) {
        switch ($window) {
            case 'minute':
                return 60;
            case 'hour':
                return 3600;
            case 'day':
                return 86400;
            default:
                return 3600;
        }
    }

    /**
     * Získanie štatistík pre dashboard
     */
    public function get_stats($platform = null) {
        if ($platform) {
            return $this->get_platform_stats($platform);
        }

        // Get stats for all platforms
        global $wpdb;
        $platforms = $wpdb->get_col(
            "SELECT DISTINCT platform FROM {$wpdb->prefix}ai_seo_social_accounts WHERE status = 'active'"
        );

        $stats = array();
        foreach ($platforms as $platform) {
            $stats[$platform] = $this->get_platform_stats($platform);
        }

        return $stats;
    }

    /**
     * Získanie štatistík pre konkrétnu platformu
     */
    private function get_platform_stats($platform) {
        $limits = $this->get_platform_limits($platform);
        $remaining = $this->get_remaining($platform);
        $reset_times = array();

        foreach (array('minute', 'hour', 'day') as $window) {
            $reset_times[$window] = $this->get_reset_time($platform, 'default', $window);
        }

        return array(
            'limits' => $limits,
            'remaining' => $remaining,
            'reset_in' => $reset_times,
            'usage_percent' => $this->calculate_usage_percent($limits, $remaining),
        );
    }

    /**
     * Výpočet percentuálneho využitia
     */
    private function calculate_usage_percent($limits, $remaining) {
        $usage = array();

        foreach ($limits as $window => $limit) {
            if ($limit > 0) {
                $used = $limit - ($remaining[$window] ?? $limit);
                $usage[$window] = round(($used / $limit) * 100, 2);
            } else {
                $usage[$window] = 0;
            }
        }

        return $usage;
    }

    /**
     * Kontrola či je potrebné počkať
     */
    public function should_wait($platform, $action = 'default') {
        if ($this->check_limit($platform, $action)) {
            return false; // No need to wait
        }

        // Need to wait - return seconds to wait
        return $this->get_reset_time($platform, $action, 'minute');
    }
}
