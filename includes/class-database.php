<?php
/**
 * Databázová vrstva
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Database {

    private static $instance = null;
    private $wpdb;
    private $tables = array();

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;

        // Definície tabuliek
        $this->tables = array(
            'analysis' => $wpdb->prefix . 'ai_seo_analysis',
            'recommendations' => $wpdb->prefix . 'ai_seo_recommendations',
            'approvals' => $wpdb->prefix . 'ai_seo_approvals',
            'logs' => $wpdb->prefix . 'ai_seo_logs',
            'keywords' => $wpdb->prefix . 'ai_seo_keywords',
        );
    }

    /**
     * Vytvorenie databázových tabuliek
     */
    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $this->wpdb->get_charset_collate();

        // Tabuľka pre SEO analýzu
        $sql_analysis = "CREATE TABLE IF NOT EXISTS {$this->tables['analysis']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            analysis_type varchar(50) NOT NULL,
            score int(3) NOT NULL DEFAULT 0,
            data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY analysis_type (analysis_type)
        ) $charset_collate;";

        // Tabuľka pre odporúčania
        $sql_recommendations = "CREATE TABLE IF NOT EXISTS {$this->tables['recommendations']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) DEFAULT NULL,
            recommendation_type varchar(50) NOT NULL,
            priority varchar(20) NOT NULL DEFAULT 'medium',
            title text NOT NULL,
            description longtext,
            action_data longtext,
            status varchar(20) NOT NULL DEFAULT 'pending',
            ai_confidence float DEFAULT NULL,
            created_by varchar(50) DEFAULT 'ai',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY priority (priority),
            KEY status_priority (status, priority)
        ) $charset_collate;";

        // Tabuľka pre schvaľovací workflow
        $sql_approvals = "CREATE TABLE IF NOT EXISTS {$this->tables['approvals']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            recommendation_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            action varchar(20) NOT NULL,
            note text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY recommendation_id (recommendation_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Tabuľka pre logy
        $sql_logs = "CREATE TABLE IF NOT EXISTS {$this->tables['logs']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            log_type varchar(50) NOT NULL,
            message text,
            data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY log_type (log_type),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Tabuľka pre keywords
        $sql_keywords = "CREATE TABLE IF NOT EXISTS {$this->tables['keywords']} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            search_volume int(11) DEFAULT NULL,
            difficulty int(3) DEFAULT NULL,
            cpc decimal(10,2) DEFAULT NULL,
            post_id bigint(20) DEFAULT NULL,
            position int(3) DEFAULT NULL,
            last_checked datetime DEFAULT CURRENT_TIMESTAMP,
            data longtext,
            PRIMARY KEY (id),
            UNIQUE KEY keyword (keyword),
            KEY post_id (post_id)
        ) $charset_collate;";

        dbDelta($sql_analysis);
        dbDelta($sql_recommendations);
        dbDelta($sql_approvals);
        dbDelta($sql_logs);
        dbDelta($sql_keywords);

        update_option('ai_seo_manager_db_version', AI_SEO_MANAGER_VERSION);
    }

    /**
     * Získanie tabuľky
     */
    public function get_table($name) {
        return isset($this->tables[$name]) ? $this->tables[$name] : null;
    }

    /**
     * Uloženie SEO analýzy
     */
    public function save_analysis($post_id, $type, $score, $data) {
        return $this->wpdb->insert(
            $this->tables['analysis'],
            array(
                'post_id' => $post_id,
                'analysis_type' => $type,
                'score' => $score,
                'data' => maybe_serialize($data),
            ),
            array('%d', '%s', '%d', '%s')
        );
    }

    /**
     * Získanie najnovšej analýzy
     */
    public function get_latest_analysis($post_id, $type = null) {
        $sql = "SELECT * FROM {$this->tables['analysis']} WHERE post_id = %d";
        $args = array($post_id);

        if ($type) {
            $sql .= " AND analysis_type = %s";
            $args[] = $type;
        }

        $sql .= " ORDER BY created_at DESC LIMIT 1";

        $result = $this->wpdb->get_row($this->wpdb->prepare($sql, $args));

        if ($result && !empty($result->data)) {
            $result->data = maybe_unserialize($result->data);
        }

        return $result;
    }

    /**
     * Uloženie odporúčania
     */
    public function save_recommendation($data) {
        $defaults = array(
            'post_id' => null,
            'recommendation_type' => 'general',
            'priority' => 'medium',
            'title' => '',
            'description' => '',
            'action_data' => '',
            'status' => 'pending',
            'ai_confidence' => null,
            'created_by' => 'ai',
        );

        $data = wp_parse_args($data, $defaults);

        if (!empty($data['action_data']) && is_array($data['action_data'])) {
            $data['action_data'] = maybe_serialize($data['action_data']);
        }

        return $this->wpdb->insert(
            $this->tables['recommendations'],
            $data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s')
        );
    }

    /**
     * Získanie pending odporúčaní
     */
    public function get_pending_recommendations($limit = 10) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->tables['recommendations']}
            WHERE status = 'pending'
            ORDER BY
                FIELD(priority, 'critical', 'high', 'medium', 'low'),
                ai_confidence DESC,
                created_at DESC
            LIMIT %d",
            $limit
        );

        $results = $this->wpdb->get_results($sql);

        foreach ($results as $result) {
            if (!empty($result->action_data)) {
                $result->action_data = maybe_unserialize($result->action_data);
            }
        }

        return $results;
    }

    /**
     * Update recommendation status
     */
    public function update_recommendation_status($id, $status) {
        return $this->wpdb->update(
            $this->tables['recommendations'],
            array('status' => $status),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Uloženie approval akcie
     */
    public function save_approval($recommendation_id, $user_id, $action, $note = '') {
        return $this->wpdb->insert(
            $this->tables['approvals'],
            array(
                'recommendation_id' => $recommendation_id,
                'user_id' => $user_id,
                'action' => $action,
                'note' => $note,
            ),
            array('%d', '%d', '%s', '%s')
        );
    }

    /**
     * Log akcie
     */
    public function log($type, $message, $data = null) {
        return $this->wpdb->insert(
            $this->tables['logs'],
            array(
                'log_type' => $type,
                'message' => $message,
                'data' => $data ? maybe_serialize($data) : null,
            ),
            array('%s', '%s', '%s')
        );
    }
}
