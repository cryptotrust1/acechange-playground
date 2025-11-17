<?php
/**
 * Debug Logger System
 * Komplexný debug a logging systém s podporou WP_DEBUG
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Debug_Logger {

    private static $instance = null;
    private $db;
    private $log_file;
    private $enabled;
    private $debug_mode;
    private $log_levels = array(
        'ERROR' => 1,
        'WARNING' => 2,
        'INFO' => 3,
        'DEBUG' => 4,
    );

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = AI_SEO_Manager_Database::get_instance();

        // Detekcia debug režimu
        $this->debug_mode = defined('AI_SEO_DEBUG') && AI_SEO_DEBUG;
        $this->enabled = $this->debug_mode || (defined('WP_DEBUG') && WP_DEBUG);

        // Log súbor v wp-content/uploads/ai-seo-manager/logs/
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/ai-seo-manager/logs';

        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            // Ochrana proti priamemu prístupu
            file_put_contents($log_dir . '/.htaccess', 'Deny from all');
            file_put_contents($log_dir . '/index.php', '<?php // Silence is golden');
        }

        $this->log_file = $log_dir . '/debug-' . date('Y-m-d') . '.log';
    }

    /**
     * Hlavná logovacia metóda
     */
    public function log($level, $message, $context = array()) {
        if (!$this->should_log($level)) {
            return false;
        }

        $log_entry = $this->format_log_entry($level, $message, $context);

        // Log do databázy
        $this->log_to_database($level, $message, $context);

        // Log do súboru
        $this->log_to_file($log_entry);

        // Log do WordPress debug.log ak je WP_DEBUG_LOG aktívny
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('[AI SEO Manager] ' . $log_entry);
        }

        // Fire hook pre custom loggery
        do_action('ai_seo_manager_log', $level, $message, $context);

        return true;
    }

    /**
     * Určenie či loggovať podľa úrovne
     */
    private function should_log($level) {
        if (!$this->enabled) {
            return false;
        }

        $current_level = defined('AI_SEO_DEBUG_LEVEL') ? AI_SEO_DEBUG_LEVEL : 'INFO';
        $current_level_value = $this->log_levels[$current_level] ?? 3;
        $log_level_value = $this->log_levels[$level] ?? 3;

        return $log_level_value <= $current_level_value;
    }

    /**
     * Formátovanie log záznamu
     */
    private function format_log_entry($level, $message, $context) {
        $timestamp = current_time('mysql');
        $formatted = sprintf(
            "[%s] [%s] %s",
            $timestamp,
            $level,
            $message
        );

        if (!empty($context)) {
            $formatted .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        // Pridaj backtrace pre ERROR úroveň
        if ($level === 'ERROR' && $this->debug_mode) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            $formatted .= ' | Trace: ' . json_encode($this->sanitize_backtrace($trace));
        }

        return $formatted;
    }

    /**
     * Sanitácia backtrace
     */
    private function sanitize_backtrace($trace) {
        $sanitized = array();
        foreach ($trace as $item) {
            $sanitized[] = array(
                'file' => $item['file'] ?? 'unknown',
                'line' => $item['line'] ?? 0,
                'function' => $item['function'] ?? 'unknown',
                'class' => $item['class'] ?? null,
            );
        }
        return $sanitized;
    }

    /**
     * Log do databázy
     */
    private function log_to_database($level, $message, $context) {
        try {
            $this->db->log(
                'debug_' . strtolower($level),
                $message,
                array(
                    'level' => $level,
                    'context' => $context,
                    'timestamp' => current_time('timestamp'),
                    'user_id' => get_current_user_id(),
                    'url' => isset($_SERVER['REQUEST_URI']) ? esc_url_raw($_SERVER['REQUEST_URI']) : '',
                    'ip' => $this->get_user_ip(),
                )
            );
        } catch (Exception $e) {
            // Ak zlyhá DB log, aspoň zaloguj do súboru
            error_log('[AI SEO Manager] Failed to log to database: ' . $e->getMessage());
        }
    }

    /**
     * Log do súboru
     */
    private function log_to_file($log_entry) {
        if (!is_writable(dirname($this->log_file))) {
            return false;
        }

        // Limit veľkosti log súboru (10MB)
        if (file_exists($this->log_file) && filesize($this->log_file) > 10 * 1024 * 1024) {
            $this->rotate_log_file();
        }

        return error_log($log_entry . PHP_EOL, 3, $this->log_file);
    }

    /**
     * Rotácia log súborov
     */
    private function rotate_log_file() {
        $backup_file = $this->log_file . '.old';
        if (file_exists($backup_file)) {
            unlink($backup_file);
        }
        rename($this->log_file, $backup_file);
    }

    /**
     * Získanie IP adresy používateľa
     */
    private function get_user_ip() {
        $ip = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }
        return $ip;
    }

    /**
     * Shortcut metódy pre rôzne úrovne
     */
    public function error($message, $context = array()) {
        return $this->log('ERROR', $message, $context);
    }

    public function warning($message, $context = array()) {
        return $this->log('WARNING', $message, $context);
    }

    public function info($message, $context = array()) {
        return $this->log('INFO', $message, $context);
    }

    public function debug($message, $context = array()) {
        return $this->log('DEBUG', $message, $context);
    }

    /**
     * Získanie logov z databázy
     */
    public function get_logs($args = array()) {
        global $wpdb;

        $defaults = array(
            'level' => null,
            'limit' => 100,
            'offset' => 0,
            'date_from' => null,
            'date_to' => null,
            'search' => null,
        );

        $args = wp_parse_args($args, $defaults);
        $table = $this->db->get_table('logs');
        $where = array('1=1');
        $prepare_args = array();

        // Filter podľa úrovne
        if ($args['level']) {
            $where[] = 'log_type LIKE %s';
            $prepare_args[] = 'debug_' . strtolower($args['level']) . '%';
        }

        // Filter podľa dátumu
        if ($args['date_from']) {
            $where[] = 'created_at >= %s';
            $prepare_args[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where[] = 'created_at <= %s';
            $prepare_args[] = $args['date_to'];
        }

        // Vyhľadávanie
        if ($args['search']) {
            $where[] = 'message LIKE %s';
            $prepare_args[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }

        $where_clause = implode(' AND ', $where);

        $prepare_args[] = (int) $args['limit'];
        $prepare_args[] = (int) $args['offset'];

        $sql = "SELECT * FROM {$table}
                WHERE {$where_clause}
                ORDER BY created_at DESC
                LIMIT %d OFFSET %d";

        if (!empty($prepare_args)) {
            $sql = $wpdb->prepare($sql, $prepare_args);
        }

        $logs = $wpdb->get_results($sql);

        // Unserialize data
        foreach ($logs as $log) {
            if (!empty($log->data)) {
                $log->data = maybe_unserialize($log->data);
            }
        }

        return $logs;
    }

    /**
     * Získanie počtu logov
     */
    public function get_logs_count($args = array()) {
        global $wpdb;

        $table = $this->db->get_table('logs');
        $where = array('1=1');
        $prepare_args = array();

        if (!empty($args['level'])) {
            $where[] = 'log_type LIKE %s';
            $prepare_args[] = 'debug_' . strtolower($args['level']) . '%';
        }

        if (!empty($args['date_from'])) {
            $where[] = 'created_at >= %s';
            $prepare_args[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = 'created_at <= %s';
            $prepare_args[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";

        if (!empty($prepare_args)) {
            $sql = $wpdb->prepare($sql, $prepare_args);
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Vymazanie starých logov
     */
    public function clean_old_logs($days = 30) {
        global $wpdb;

        $table = $this->db->get_table('logs');
        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE created_at < %s AND log_type LIKE 'debug_%'",
            $date_threshold
        ));

        $this->info("Cleaned {$deleted} old debug logs", array('days' => $days));

        return $deleted;
    }

    /**
     * Vymazanie všetkých debug logov
     */
    public function clear_all_logs() {
        global $wpdb;

        $table = $this->db->get_table('logs');
        $deleted = $wpdb->query("DELETE FROM {$table} WHERE log_type LIKE 'debug_%'");

        return $deleted;
    }

    /**
     * Export logov do CSV
     */
    public function export_logs_csv($args = array()) {
        $logs = $this->get_logs(array_merge($args, array('limit' => 10000)));

        $csv_data = array();
        $csv_data[] = array('Timestamp', 'Level', 'Message', 'Context', 'User ID', 'IP');

        foreach ($logs as $log) {
            $data = maybe_unserialize($log->data);
            $csv_data[] = array(
                $log->created_at,
                $data['level'] ?? 'INFO',
                $log->message,
                json_encode($data['context'] ?? array()),
                $data['user_id'] ?? '',
                $data['ip'] ?? '',
            );
        }

        return $csv_data;
    }

    /**
     * Kontrola či je debug mód aktívny
     */
    public function is_enabled() {
        return $this->enabled;
    }

    /**
     * Kontrola či je debug mód pluginu aktívny
     */
    public function is_debug_mode() {
        return $this->debug_mode;
    }

    /**
     * Získanie štatistík logov
     */
    public function get_stats() {
        global $wpdb;

        $table = $this->db->get_table('logs');
        $stats = array(
            'total' => 0,
            'by_level' => array(
                'error' => 0,
                'warning' => 0,
                'info' => 0,
                'debug' => 0,
            ),
            'today' => 0,
            'this_week' => 0,
        );

        // Celkový počet
        $stats['total'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE log_type LIKE 'debug_%'"
        );

        // Počet podľa úrovne
        foreach (array('error', 'warning', 'info', 'debug') as $level) {
            $stats['by_level'][$level] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE log_type LIKE %s",
                'debug_' . $level . '%'
            ));
        }

        // Dnešné logy
        $stats['today'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
             WHERE log_type LIKE 'debug_%'
             AND DATE(created_at) = %s",
            current_time('Y-m-d')
        ));

        // Týždenné logy
        $stats['this_week'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
             WHERE log_type LIKE 'debug_%'
             AND created_at >= %s",
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ));

        return $stats;
    }
}
