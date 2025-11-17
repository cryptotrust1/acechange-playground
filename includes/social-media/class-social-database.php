<?php
/**
 * Social Media Database Manager
 * Správa databázových tabuliek pre Social Media Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Social_Database {

    private static $instance = null;
    private $wpdb;
    private $tables = array();
    private $logger;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;

        // Initialize debug logger if available
        if (class_exists('AI_SEO_Manager_Debug_Logger')) {
            $this->logger = AI_SEO_Manager_Debug_Logger::get_instance();
        }

        // Define table names
        $this->tables = array(
            'accounts' => $wpdb->prefix . 'ai_seo_social_accounts',
            'posts' => $wpdb->prefix . 'ai_seo_social_posts',
            'queue' => $wpdb->prefix . 'ai_seo_social_queue',
            'analytics' => $wpdb->prefix . 'ai_seo_social_analytics',
            'trends' => $wpdb->prefix . 'ai_seo_social_trends',
            'settings' => $wpdb->prefix . 'ai_seo_social_settings',
        );
    }

    /**
     * Vytvorenie všetkých databázových tabuliek
     */
    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $this->wpdb->get_charset_collate();

        if ($this->logger) {
            $this->logger->info('Creating Social Media database tables');
        }

        // 1. Social Accounts Table
        $sql_accounts = "CREATE TABLE IF NOT EXISTS {$this->tables['accounts']} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            platform VARCHAR(50) NOT NULL,
            account_name VARCHAR(255) NOT NULL,
            account_id VARCHAR(255),
            access_token TEXT,
            refresh_token TEXT,
            token_expires_at DATETIME,
            credentials LONGTEXT,
            settings LONGTEXT,
            status VARCHAR(20) DEFAULT 'active',
            last_sync DATETIME,
            error_message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY platform_account (platform, account_id),
            KEY status (status),
            KEY platform (platform)
        ) $charset_collate;";

        // 2. Social Posts Table
        $sql_posts = "CREATE TABLE IF NOT EXISTS {$this->tables['posts']} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20),
            account_id BIGINT(20) NOT NULL,
            platform VARCHAR(50) NOT NULL,
            content LONGTEXT NOT NULL,
            media_urls LONGTEXT,
            hashtags TEXT,
            mentions TEXT,
            tone VARCHAR(50),
            category VARCHAR(50),
            platform_post_id VARCHAR(255),
            platform_url TEXT,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            scheduled_at DATETIME,
            published_at DATETIME,
            error_message TEXT,
            retry_count INT DEFAULT 0,
            max_retries INT DEFAULT 3,
            analytics LONGTEXT,
            created_by VARCHAR(50) DEFAULT 'ai',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY account_id (account_id),
            KEY platform (platform),
            KEY status (status),
            KEY scheduled_at (scheduled_at),
            KEY category (category),
            FOREIGN KEY (account_id) REFERENCES {$this->tables['accounts']}(id) ON DELETE CASCADE
        ) $charset_collate;";

        // 3. Social Queue Table
        $sql_queue = "CREATE TABLE IF NOT EXISTS {$this->tables['queue']} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            social_post_id BIGINT(20) NOT NULL,
            priority INT DEFAULT 5,
            scheduled_for DATETIME NOT NULL,
            processing TINYINT(1) DEFAULT 0,
            processed_at DATETIME,
            attempts INT DEFAULT 0,
            last_attempt DATETIME,
            next_retry DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY social_post_id (social_post_id),
            KEY scheduled_for (scheduled_for),
            KEY processing (processing),
            KEY priority (priority),
            FOREIGN KEY (social_post_id) REFERENCES {$this->tables['posts']}(id) ON DELETE CASCADE
        ) $charset_collate;";

        // 4. Social Analytics Table
        $sql_analytics = "CREATE TABLE IF NOT EXISTS {$this->tables['analytics']} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            social_post_id BIGINT(20) NOT NULL,
            platform VARCHAR(50) NOT NULL,
            metric_date DATE NOT NULL,
            impressions INT DEFAULT 0,
            reach INT DEFAULT 0,
            likes INT DEFAULT 0,
            comments INT DEFAULT 0,
            shares INT DEFAULT 0,
            saves INT DEFAULT 0,
            clicks INT DEFAULT 0,
            engagement_rate DECIMAL(5,2),
            data LONGTEXT,
            synced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_date (social_post_id, metric_date),
            KEY platform (platform),
            KEY metric_date (metric_date),
            FOREIGN KEY (social_post_id) REFERENCES {$this->tables['posts']}(id) ON DELETE CASCADE
        ) $charset_collate;";

        // 5. Social Trends Table
        $sql_trends = "CREATE TABLE IF NOT EXISTS {$this->tables['trends']} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            category VARCHAR(50) NOT NULL,
            trend_topic VARCHAR(255) NOT NULL,
            keywords TEXT,
            description TEXT,
            trend_score DECIMAL(5,2),
            source VARCHAR(100),
            data LONGTEXT,
            first_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at DATETIME,
            status VARCHAR(20) DEFAULT 'active',
            PRIMARY KEY (id),
            KEY category (category),
            KEY trend_score (trend_score),
            KEY status (status),
            KEY last_updated (last_updated)
        ) $charset_collate;";

        // 6. Social Settings Table
        $sql_settings = "CREATE TABLE IF NOT EXISTS {$this->tables['settings']} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            setting_key VARCHAR(255) NOT NULL,
            setting_value LONGTEXT,
            setting_type VARCHAR(50) DEFAULT 'string',
            category VARCHAR(100) DEFAULT 'general',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key),
            KEY category (category)
        ) $charset_collate;";

        // Execute all CREATE TABLE statements
        dbDelta($sql_accounts);
        dbDelta($sql_posts);
        dbDelta($sql_queue);
        dbDelta($sql_analytics);
        dbDelta($sql_trends);
        dbDelta($sql_settings);

        // Store DB version
        update_option('ai_seo_social_db_version', '1.0.0');

        if ($this->logger) {
            $this->logger->info('Social Media database tables created successfully');
        }

        return true;
    }

    /**
     * Získanie názvu tabuľky
     */
    public function get_table($name) {
        return isset($this->tables[$name]) ? $this->tables[$name] : null;
    }

    /**
     * Kontrola či tabuľky existujú
     */
    public function tables_exist() {
        $table = $this->tables['accounts'];
        $query = $this->wpdb->prepare('SHOW TABLES LIKE %s', $table);
        return $this->wpdb->get_var($query) === $table;
    }

    /**
     * Vymazanie všetkých tabuliek (use with caution!)
     */
    public function drop_tables() {
        if ($this->logger) {
            $this->logger->warning('Dropping all Social Media database tables');
        }

        foreach (array_reverse($this->tables) as $table) {
            $this->wpdb->query("DROP TABLE IF EXISTS {$table}");
        }

        delete_option('ai_seo_social_db_version');

        return true;
    }

    // =================================================================
    // SOCIAL ACCOUNTS METHODS
    // =================================================================

    /**
     * Vytvorenie nového social account
     */
    public function create_account($data) {
        $defaults = array(
            'platform' => '',
            'account_name' => '',
            'account_id' => '',
            'access_token' => '',
            'refresh_token' => '',
            'token_expires_at' => null,
            'credentials' => '',
            'settings' => '',
            'status' => 'active',
        );

        $data = wp_parse_args($data, $defaults);

        // Serialize complex data
        if (is_array($data['credentials'])) {
            $data['credentials'] = maybe_serialize($data['credentials']);
        }
        if (is_array($data['settings'])) {
            $data['settings'] = maybe_serialize($data['settings']);
        }

        $result = $this->wpdb->insert(
            $this->tables['accounts'],
            $data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result && $this->logger) {
            $this->logger->info('Social account created', array(
                'platform' => $data['platform'],
                'account_id' => $this->wpdb->insert_id,
            ));
        }

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Získanie account podľa ID
     */
    public function get_account($account_id) {
        $account = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->tables['accounts']} WHERE id = %d",
            $account_id
        ));

        if ($account) {
            $account->credentials = maybe_unserialize($account->credentials);
            $account->settings = maybe_unserialize($account->settings);
        }

        return $account;
    }

    /**
     * Získanie account podľa platformy
     */
    public function get_account_by_platform($platform, $account_id = null) {
        if ($account_id) {
            $account = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->tables['accounts']}
                WHERE platform = %s AND account_id = %s",
                $platform,
                $account_id
            ));
        } else {
            $account = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM {$this->tables['accounts']}
                WHERE platform = %s AND status = 'active'
                ORDER BY created_at DESC LIMIT 1",
                $platform
            ));
        }

        if ($account) {
            $account->credentials = maybe_unserialize($account->credentials);
            $account->settings = maybe_unserialize($account->settings);
        }

        return $account;
    }

    /**
     * Získanie všetkých aktívnych accounts
     */
    public function get_all_active_accounts() {
        $accounts = $this->wpdb->get_results(
            "SELECT * FROM {$this->tables['accounts']}
            WHERE status = 'active'
            ORDER BY platform, created_at DESC"
        );

        foreach ($accounts as $account) {
            $account->credentials = maybe_unserialize($account->credentials);
            $account->settings = maybe_unserialize($account->settings);
        }

        return $accounts;
    }

    /**
     * Update account
     */
    public function update_account($account_id, $data) {
        // Serialize complex data
        if (isset($data['credentials']) && is_array($data['credentials'])) {
            $data['credentials'] = maybe_serialize($data['credentials']);
        }
        if (isset($data['settings']) && is_array($data['settings'])) {
            $data['settings'] = maybe_serialize($data['settings']);
        }

        return $this->wpdb->update(
            $this->tables['accounts'],
            $data,
            array('id' => $account_id),
            null,
            array('%d')
        );
    }

    /**
     * Delete account
     */
    public function delete_account($account_id) {
        return $this->wpdb->delete(
            $this->tables['accounts'],
            array('id' => $account_id),
            array('%d')
        );
    }

    // =================================================================
    // SOCIAL POSTS METHODS
    // =================================================================

    /**
     * Vytvorenie nového social post
     */
    public function create_post($data) {
        $defaults = array(
            'post_id' => null,
            'account_id' => 0,
            'platform' => '',
            'content' => '',
            'media_urls' => '',
            'hashtags' => '',
            'mentions' => '',
            'tone' => 'professional',
            'category' => 'general',
            'platform_post_id' => '',
            'platform_url' => '',
            'status' => 'draft',
            'scheduled_at' => null,
            'published_at' => null,
            'error_message' => '',
            'retry_count' => 0,
            'max_retries' => 3,
            'analytics' => '',
            'created_by' => 'ai',
        );

        $data = wp_parse_args($data, $defaults);

        // Serialize arrays
        if (is_array($data['media_urls'])) {
            $data['media_urls'] = maybe_serialize($data['media_urls']);
        }
        if (is_array($data['analytics'])) {
            $data['analytics'] = maybe_serialize($data['analytics']);
        }

        $result = $this->wpdb->insert(
            $this->tables['posts'],
            $data
        );

        if ($result && $this->logger) {
            $this->logger->info('Social post created', array(
                'social_post_id' => $this->wpdb->insert_id,
                'platform' => $data['platform'],
                'status' => $data['status'],
            ));
        }

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Získanie post podľa ID
     */
    public function get_post($post_id) {
        $post = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->tables['posts']} WHERE id = %d",
            $post_id
        ));

        if ($post) {
            $post->media_urls = maybe_unserialize($post->media_urls);
            $post->analytics = maybe_unserialize($post->analytics);
        }

        return $post;
    }

    /**
     * Update post
     */
    public function update_post($post_id, $data) {
        // Serialize arrays
        if (isset($data['media_urls']) && is_array($data['media_urls'])) {
            $data['media_urls'] = maybe_serialize($data['media_urls']);
        }
        if (isset($data['analytics']) && is_array($data['analytics'])) {
            $data['analytics'] = maybe_serialize($data['analytics']);
        }

        return $this->wpdb->update(
            $this->tables['posts'],
            $data,
            array('id' => $post_id),
            null,
            array('%d')
        );
    }

    /**
     * Získanie posts podľa filtrov
     */
    public function get_posts($args = array()) {
        $defaults = array(
            'status' => null,
            'platform' => null,
            'category' => null,
            'account_id' => null,
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $prepare_values = array();

        if ($args['status']) {
            $where[] = 'status = %s';
            $prepare_values[] = $args['status'];
        }

        if ($args['platform']) {
            $where[] = 'platform = %s';
            $prepare_values[] = $args['platform'];
        }

        if ($args['category']) {
            $where[] = 'category = %s';
            $prepare_values[] = $args['category'];
        }

        if ($args['account_id']) {
            $where[] = 'account_id = %d';
            $prepare_values[] = $args['account_id'];
        }

        $where_clause = implode(' AND ', $where);

        $prepare_values[] = (int) $args['limit'];
        $prepare_values[] = (int) $args['offset'];

        $sql = "SELECT * FROM {$this->tables['posts']}
                WHERE {$where_clause}
                ORDER BY {$args['orderby']} {$args['order']}
                LIMIT %d OFFSET %d";

        if (!empty($prepare_values)) {
            $sql = $this->wpdb->prepare($sql, $prepare_values);
        }

        $posts = $this->wpdb->get_results($sql);

        foreach ($posts as $post) {
            $post->media_urls = maybe_unserialize($post->media_urls);
            $post->analytics = maybe_unserialize($post->analytics);
        }

        return $posts;
    }

    // =================================================================
    // ADDITIONAL METHODS for other tables will be added as needed
    // =================================================================

    /**
     * Get statistics summary
     */
    public function get_stats_summary() {
        $stats = array(
            'total_accounts' => $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['accounts']}"),
            'active_accounts' => $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['accounts']} WHERE status = 'active'"),
            'total_posts' => $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['posts']}"),
            'published_posts' => $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['posts']} WHERE status = 'published'"),
            'scheduled_posts' => $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['posts']} WHERE status = 'scheduled'"),
            'failed_posts' => $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['posts']} WHERE status = 'failed'"),
        );

        return $stats;
    }
}
