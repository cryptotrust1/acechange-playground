<?php
/**
 * Performance Monitor
 * Sledovanie výkonu a profilovanie operácií
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Performance_Monitor {

    private static $instance = null;
    private $timers = array();
    private $metrics = array();
    private $queries_before = 0;
    private $logger;
    private $enabled;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->enabled = defined('AI_SEO_DEBUG') && AI_SEO_DEBUG;
        $this->logger = AI_SEO_Manager_Debug_Logger::get_instance();

        if ($this->enabled) {
            add_action('shutdown', array($this, 'log_performance_summary'));
        }
    }

    /**
     * Spustenie timera
     */
    public function start($name) {
        if (!$this->enabled) {
            return false;
        }

        global $wpdb;

        $this->timers[$name] = array(
            'start' => microtime(true),
            'memory_start' => memory_get_usage(),
            'queries_start' => $wpdb->num_queries ?? 0,
        );

        return true;
    }

    /**
     * Zastavenie timera a uloženie metriky
     */
    public function stop($name) {
        if (!$this->enabled || !isset($this->timers[$name])) {
            return false;
        }

        global $wpdb;

        $end_time = microtime(true);
        $timer = $this->timers[$name];

        $metric = array(
            'name' => $name,
            'duration' => $end_time - $timer['start'],
            'memory_used' => memory_get_usage() - $timer['memory_start'],
            'queries_count' => ($wpdb->num_queries ?? 0) - $timer['queries_start'],
            'timestamp' => current_time('mysql'),
        );

        $this->metrics[$name] = $metric;

        // Log ak operácia trvala príliš dlho
        if ($metric['duration'] > 5) {
            $this->logger->warning("Slow operation detected: {$name}", $metric);
        }

        // Log do debug módu
        $this->logger->debug("Performance: {$name}", array(
            'duration' => round($metric['duration'], 4) . 's',
            'memory' => $this->format_bytes($metric['memory_used']),
            'queries' => $metric['queries_count'],
        ));

        unset($this->timers[$name]);

        return $metric;
    }

    /**
     * Profilovanie funkcie/metódy
     */
    public function profile($callback, $name = null) {
        if (!$this->enabled || !is_callable($callback)) {
            return call_user_func($callback);
        }

        $name = $name ?? $this->get_callback_name($callback);
        $this->start($name);

        try {
            $result = call_user_func($callback);
        } catch (Exception $e) {
            $this->stop($name);
            $this->logger->error("Exception in profiled function: {$name}", array(
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ));
            throw $e;
        }

        $this->stop($name);

        return $result;
    }

    /**
     * Získanie názvu callbacku
     */
    private function get_callback_name($callback) {
        if (is_string($callback)) {
            return $callback;
        }

        if (is_array($callback)) {
            $class = is_object($callback[0]) ? get_class($callback[0]) : $callback[0];
            return $class . '::' . $callback[1];
        }

        return 'anonymous_function';
    }

    /**
     * Tracking API volania
     */
    public function track_api_call($provider, $endpoint, $duration, $success = true, $error = null) {
        $metric = array(
            'type' => 'api_call',
            'provider' => $provider,
            'endpoint' => $endpoint,
            'duration' => $duration,
            'success' => $success,
            'error' => $error,
            'timestamp' => current_time('timestamp'),
        );

        $this->metrics['api_' . $provider . '_' . time()] = $metric;

        // Log podľa výsledku
        if (!$success) {
            $this->logger->error("API call failed: {$provider} - {$endpoint}", $metric);
        } elseif ($duration > 10) {
            $this->logger->warning("Slow API call: {$provider} - {$endpoint}", $metric);
        } else {
            $this->logger->debug("API call: {$provider} - {$endpoint}", $metric);
        }

        // Uloženie do databázy pre štatistiky
        $this->save_api_metric($metric);

        return $metric;
    }

    /**
     * Tracking databázových operácií
     */
    public function track_database_operation($operation, $table, $duration, $rows_affected = 0) {
        $metric = array(
            'type' => 'db_operation',
            'operation' => $operation,
            'table' => $table,
            'duration' => $duration,
            'rows_affected' => $rows_affected,
            'timestamp' => current_time('timestamp'),
        );

        $this->metrics['db_' . $operation . '_' . time()] = $metric;

        // Log pomalých queries
        if ($duration > 1) {
            $this->logger->warning("Slow database operation: {$operation} on {$table}", $metric);
        }

        return $metric;
    }

    /**
     * Uloženie API metriky do databázy
     */
    private function save_api_metric($metric) {
        if (!$this->enabled) {
            return false;
        }

        // Uložíme len základné štatistiky
        $stats = get_option('ai_seo_manager_api_performance', array());

        $key = $metric['provider'];
        if (!isset($stats[$key])) {
            $stats[$key] = array(
                'total_calls' => 0,
                'failed_calls' => 0,
                'total_duration' => 0,
                'avg_duration' => 0,
            );
        }

        $stats[$key]['total_calls']++;
        if (!$metric['success']) {
            $stats[$key]['failed_calls']++;
        }
        $stats[$key]['total_duration'] += $metric['duration'];
        $stats[$key]['avg_duration'] = $stats[$key]['total_duration'] / $stats[$key]['total_calls'];

        update_option('ai_seo_manager_api_performance', $stats);

        return true;
    }

    /**
     * Získanie metriky
     */
    public function get_metric($name) {
        return $this->metrics[$name] ?? null;
    }

    /**
     * Získanie všetkých metrík
     */
    public function get_all_metrics() {
        return $this->metrics;
    }

    /**
     * Získanie API performance štatistík
     */
    public function get_api_performance_stats() {
        return get_option('ai_seo_manager_api_performance', array());
    }

    /**
     * Reset metrík
     */
    public function reset_metrics() {
        $this->metrics = array();
        $this->timers = array();
        return true;
    }

    /**
     * Reset API performance štatistík
     */
    public function reset_api_stats() {
        delete_option('ai_seo_manager_api_performance');
        return true;
    }

    /**
     * Formátovanie bytov
     */
    private function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Log performance summary pri shutdowne
     */
    public function log_performance_summary() {
        if (!$this->enabled || empty($this->metrics)) {
            return;
        }

        $summary = array(
            'total_metrics' => count($this->metrics),
            'peak_memory' => $this->format_bytes(memory_get_peak_usage(true)),
            'current_memory' => $this->format_bytes(memory_get_usage(true)),
            'total_time' => timer_stop(0),
        );

        // Počítaj štatistiky
        $total_duration = 0;
        $slow_operations = 0;

        foreach ($this->metrics as $metric) {
            if (isset($metric['duration'])) {
                $total_duration += $metric['duration'];
                if ($metric['duration'] > 3) {
                    $slow_operations++;
                }
            }
        }

        $summary['operations_total_time'] = round($total_duration, 4) . 's';
        $summary['slow_operations'] = $slow_operations;

        $this->logger->info('Performance Summary', $summary);
    }

    /**
     * Získanie memory usage info
     */
    public function get_memory_info() {
        return array(
            'current' => memory_get_usage(true),
            'current_formatted' => $this->format_bytes(memory_get_usage(true)),
            'peak' => memory_get_peak_usage(true),
            'peak_formatted' => $this->format_bytes(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit'),
        );
    }

    /**
     * Kontrola či je monitoring aktívny
     */
    public function is_enabled() {
        return $this->enabled;
    }

    /**
     * Tracking WordPress hook execution
     */
    public function track_hook($hook_name) {
        if (!$this->enabled) {
            return false;
        }

        $this->start('hook_' . $hook_name);

        return true;
    }

    /**
     * Stop tracking WordPress hook
     */
    public function stop_hook($hook_name) {
        if (!$this->enabled) {
            return false;
        }

        return $this->stop('hook_' . $hook_name);
    }
}
