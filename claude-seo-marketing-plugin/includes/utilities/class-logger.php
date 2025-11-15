<?php
/**
 * Logging utility.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/utilities
 */

/**
 * Provides logging functionality with different severity levels.
 */
class Claude_SEO_Logger {

    /**
     * Log levels.
     */
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    /**
     * Whether debug mode is enabled.
     *
     * @var bool
     */
    private static $debug_mode = null;

    /**
     * Log a message.
     *
     * @param string $level   Log level.
     * @param string $message Log message.
     * @param array  $context Additional context.
     */
    public static function log($level, $message, $context = array()) {
        // Only log if WP_DEBUG is enabled or level is error or higher
        if (!self::should_log($level)) {
            return;
        }

        $log_entry = self::format_log_entry($level, $message, $context);

        // Use error_log for PHP errors
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log($log_entry);
        }

        // Store in database for admin viewing (errors and higher)
        if (self::is_severe($level)) {
            self::store_log($level, $message, $context);
        }

        // Trigger action for custom logging implementations
        do_action('claude_seo_log', $level, $message, $context);
    }

    /**
     * Log an emergency message.
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     */
    public static function emergency($message, $context = array()) {
        self::log(self::EMERGENCY, $message, $context);
    }

    /**
     * Log an alert message.
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     */
    public static function alert($message, $context = array()) {
        self::log(self::ALERT, $message, $context);
    }

    /**
     * Log a critical message.
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     */
    public static function critical($message, $context = array()) {
        self::log(self::CRITICAL, $message, $context);
    }

    /**
     * Log an error message.
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     */
    public static function error($message, $context = array()) {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Log a warning message.
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     */
    public static function warning($message, $context = array()) {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Log a notice message.
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     */
    public static function notice($message, $context = array()) {
        self::log(self::NOTICE, $message, $context);
    }

    /**
     * Log an info message.
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     */
    public static function info($message, $context = array()) {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Log a debug message.
     *
     * @param string $message Log message.
     * @param array  $context Additional context.
     */
    public static function debug($message, $context = array()) {
        self::log(self::DEBUG, $message, $context);
    }

    /**
     * Determine if message should be logged.
     *
     * @param string $level Log level.
     * @return bool True if should log.
     */
    private static function should_log($level) {
        // Always log severe errors
        if (self::is_severe($level)) {
            return true;
        }

        // Otherwise only log if debug mode is enabled
        return self::is_debug_mode();
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool True if enabled.
     */
    private static function is_debug_mode() {
        if (self::$debug_mode === null) {
            self::$debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        }

        return self::$debug_mode;
    }

    /**
     * Check if log level is severe.
     *
     * @param string $level Log level.
     * @return bool True if severe.
     */
    private static function is_severe($level) {
        $severe_levels = array(self::EMERGENCY, self::ALERT, self::CRITICAL, self::ERROR);
        return in_array($level, $severe_levels, true);
    }

    /**
     * Format log entry.
     *
     * @param string $level   Log level.
     * @param string $message Log message.
     * @param array  $context Additional context.
     * @return string Formatted log entry.
     */
    private static function format_log_entry($level, $message, $context) {
        $timestamp = gmdate('Y-m-d H:i:s');
        $level_upper = strtoupper($level);

        // Sanitize context for logging (remove sensitive data)
        $safe_context = self::sanitize_context($context);
        $context_str = !empty($safe_context) ? ' | Context: ' . wp_json_encode($safe_context) : '';

        return "[{$timestamp}] CLAUDE_SEO.{$level_upper}: {$message}{$context_str}";
    }

    /**
     * Remove sensitive data from context.
     *
     * @param array $context Context array.
     * @return array Sanitized context.
     */
    private static function sanitize_context($context) {
        $sensitive_keys = array('api_key', 'password', 'secret', 'token', 'auth');

        foreach ($context as $key => $value) {
            foreach ($sensitive_keys as $sensitive) {
                if (stripos($key, $sensitive) !== false) {
                    $context[$key] = '[REDACTED]';
                }
            }
        }

        return $context;
    }

    /**
     * Store log in database.
     *
     * @param string $level   Log level.
     * @param string $message Log message.
     * @param array  $context Additional context.
     */
    private static function store_log($level, $message, $context) {
        // Use WordPress options table for simplicity
        $logs = get_option('claude_seo_error_logs', array());

        // Keep only last 100 logs
        if (count($logs) >= 100) {
            array_shift($logs);
        }

        $logs[] = array(
            'timestamp' => time(),
            'level' => $level,
            'message' => $message,
            'context' => self::sanitize_context($context)
        );

        update_option('claude_seo_error_logs', $logs, false);
    }

    /**
     * Get stored logs.
     *
     * @param int $limit Number of logs to retrieve.
     * @return array Array of log entries.
     */
    public static function get_logs($limit = 50) {
        $logs = get_option('claude_seo_error_logs', array());

        return array_slice($logs, -$limit);
    }

    /**
     * Clear all stored logs.
     */
    public static function clear_logs() {
        delete_option('claude_seo_error_logs');
    }
}
