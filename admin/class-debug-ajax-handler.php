<?php
/**
 * Debug AJAX Handler
 * Handles all AJAX requests for debug dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Debug_AJAX_Handler {

    private static $instance = null;
    private $logger;
    private $performance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->logger = AI_SEO_Manager_Debug_Logger::get_instance();
        $this->performance = AI_SEO_Manager_Performance_Monitor::get_instance();

        $this->init_hooks();
    }

    private function init_hooks() {
        // AJAX endpoints
        add_action('wp_ajax_ai_seo_get_latest_logs', array($this, 'get_latest_logs'));
        add_action('wp_ajax_ai_seo_clear_logs', array($this, 'clear_logs'));
        add_action('wp_ajax_ai_seo_run_test', array($this, 'run_test'));
        add_action('wp_ajax_ai_seo_test_social_post', array($this, 'test_social_post'));
        add_action('wp_ajax_ai_seo_export_debug_report', array($this, 'export_debug_report'));
    }

    /**
     * Get latest logs via AJAX
     */
    public function get_latest_logs() {
        check_ajax_referer('ai_seo_debug', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $logs = $this->logger->get_recent_logs(50);

        wp_send_json_success($logs);
    }

    /**
     * Clear all logs
     */
    public function clear_logs() {
        check_ajax_referer('ai_seo_debug', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $this->logger->clear_all_logs();

        wp_send_json_success('Logs cleared');
    }

    /**
     * Run individual test
     */
    public function run_test() {
        check_ajax_referer('ai_seo_debug', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $test_name = sanitize_text_field($_POST['test']);

        $result = $this->execute_test($test_name);

        wp_send_json($result);
    }

    /**
     * Execute specific test
     */
    private function execute_test($test_name) {
        $this->logger->info("Running test: $test_name");

        switch ($test_name) {
            case 'test-telegram-auth':
                return $this->test_telegram_auth();

            case 'test-facebook-auth':
                return $this->test_facebook_auth();

            case 'test-instagram-auth':
                return $this->test_instagram_auth();

            case 'test-twitter-auth':
                return $this->test_twitter_auth();

            case 'test-social-db':
                return $this->test_social_database();

            case 'test-rate-limiter':
                return $this->test_rate_limiter();

            case 'test-claude-api':
                return $this->test_claude_api();

            case 'test-openai-api':
                return $this->test_openai_api();

            case 'test-content-generation':
                return $this->test_content_generation();

            case 'test-meta-generation':
                return $this->test_meta_generation();

            case 'test-schema-generation':
                return $this->test_schema_generation();

            case 'test-keyword-analysis':
                return $this->test_keyword_analysis();

            case 'test-db-connection':
                return $this->test_db_connection();

            case 'test-db-tables':
                return $this->test_db_tables();

            case 'test-db-migration':
                return $this->test_db_migration();

            default:
                return array(
                    'success' => false,
                    'data' => array('error' => 'Unknown test: ' . $test_name)
                );
        }
    }

    /**
     * Test Telegram authentication
     */
    private function test_telegram_auth() {
        try {
            if (!class_exists('AI_SEO_Social_Telegram_Client')) {
                throw new Exception('Telegram client class not found');
            }

            $telegram = new AI_SEO_Social_Telegram_Client();

            // Try to authenticate
            $result = $telegram->authenticate();

            if (is_wp_error($result)) {
                return array(
                    'success' => false,
                    'data' => array(
                        'error' => $result->get_error_message(),
                        'code' => $result->get_error_code(),
                        'note' => 'Configure Telegram credentials in Social Media settings'
                    )
                );
            }

            return array(
                'success' => true,
                'data' => array(
                    'status' => 'Telegram authentication successful',
                    'platform' => 'telegram',
                    'authenticated' => true
                )
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'data' => array(
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                )
            );
        }
    }

    /**
     * Test Facebook authentication
     */
    private function test_facebook_auth() {
        try {
            if (!class_exists('AI_SEO_Social_Facebook_Client')) {
                throw new Exception('Facebook client class not found');
            }

            $facebook = new AI_SEO_Social_Facebook_Client();
            $result = $facebook->authenticate();

            if (is_wp_error($result)) {
                return array(
                    'success' => false,
                    'data' => array(
                        'error' => $result->get_error_message(),
                        'note' => 'Configure Facebook credentials in Social Media settings'
                    )
                );
            }

            return array(
                'success' => true,
                'data' => array(
                    'status' => 'Facebook authentication successful',
                    'platform' => 'facebook'
                )
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'data' => array('error' => $e->getMessage())
            );
        }
    }

    /**
     * Test Instagram authentication
     */
    private function test_instagram_auth() {
        try {
            if (!class_exists('AI_SEO_Social_Instagram_Client')) {
                throw new Exception('Instagram client class not found');
            }

            $instagram = new AI_SEO_Social_Instagram_Client();
            $result = $instagram->authenticate();

            if (is_wp_error($result)) {
                return array(
                    'success' => false,
                    'data' => array(
                        'error' => $result->get_error_message()
                    )
                );
            }

            return array(
                'success' => true,
                'data' => array(
                    'status' => 'Instagram authentication successful'
                )
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'data' => array('error' => $e->getMessage())
            );
        }
    }

    /**
     * Test Twitter authentication
     */
    private function test_twitter_auth() {
        try {
            if (!class_exists('AI_SEO_Social_Twitter_Client')) {
                throw new Exception('Twitter client class not found');
            }

            $twitter = new AI_SEO_Social_Twitter_Client();
            $result = $twitter->authenticate();

            if (is_wp_error($result)) {
                return array(
                    'success' => false,
                    'data' => array(
                        'error' => $result->get_error_message()
                    )
                );
            }

            return array(
                'success' => true,
                'data' => array(
                    'status' => 'Twitter authentication successful'
                )
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'data' => array('error' => $e->getMessage())
            );
        }
    }

    /**
     * Test Social Media Database
     */
    private function test_social_database() {
        try {
            if (!class_exists('AI_SEO_Social_Database')) {
                throw new Exception('Social Database class not found');
            }

            $db = AI_SEO_Social_Database::get_instance();

            // Check if tables exist
            $tables_exist = $db->check_tables_exist();

            if (!$tables_exist) {
                // Try to create tables
                $db->create_tables();
                $tables_exist = $db->check_tables_exist();
            }

            // Get stats
            $stats = $db->get_stats_summary();

            return array(
                'success' => $tables_exist,
                'data' => array(
                    'tables_exist' => $tables_exist,
                    'stats' => $stats,
                    'status' => $tables_exist ? 'Database OK' : 'Database tables missing'
                )
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'data' => array('error' => $e->getMessage())
            );
        }
    }

    /**
     * Test Rate Limiter
     */
    private function test_rate_limiter() {
        try {
            if (!class_exists('AI_SEO_Social_Rate_Limiter')) {
                throw new Exception('Rate Limiter class not found');
            }

            $limiter = AI_SEO_Social_Rate_Limiter::get_instance();

            // Test limit check
            $can_post = $limiter->check_limit('telegram', 'test');

            // Get stats
            $stats = $limiter->get_stats();

            return array(
                'success' => true,
                'data' => array(
                    'can_post' => $can_post,
                    'stats' => $stats,
                    'status' => 'Rate Limiter OK'
                )
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'data' => array('error' => $e->getMessage())
            );
        }
    }

    /**
     * Test Claude API
     */
    private function test_claude_api() {
        try {
            if (!class_exists('AI_SEO_Manager_Claude_Client')) {
                throw new Exception('Claude Client class not found');
            }

            $claude = new AI_SEO_Manager_Claude_Client();

            // Simple test request
            $response = $claude->generate_content(array(
                'prompt' => 'Say "Hello, I am Claude and I am working!" in exactly 10 words.',
                'max_tokens' => 50
            ));

            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'data' => array(
                        'error' => $response->get_error_message(),
                        'note' => 'Check Claude API credentials in settings'
                    )
                );
            }

            return array(
                'success' => true,
                'data' => array(
                    'status' => 'Claude API OK',
                    'response' => $response
                )
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'data' => array('error' => $e->getMessage())
            );
        }
    }

    /**
     * Test OpenAI API
     */
    private function test_openai_api() {
        try {
            // Similar to Claude test
            return array(
                'success' => false,
                'data' => array('note' => 'OpenAI test not implemented yet')
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'data' => array('error' => $e->getMessage())
            );
        }
    }

    /**
     * Test content generation
     */
    private function test_content_generation() {
        try {
            return array(
                'success' => false,
                'data' => array('note' => 'Content generation test not implemented yet')
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'data' => array('error' => $e->getMessage())
            );
        }
    }

    /**
     * Test meta generation
     */
    private function test_meta_generation() {
        return array(
            'success' => false,
            'data' => array('note' => 'Meta generation test not implemented yet')
        );
    }

    /**
     * Test schema generation
     */
    private function test_schema_generation() {
        return array(
            'success' => false,
            'data' => array('note' => 'Schema generation test not implemented yet')
        );
    }

    /**
     * Test keyword analysis
     */
    private function test_keyword_analysis() {
        return array(
            'success' => false,
            'data' => array('note' => 'Keyword analysis test not implemented yet')
        );
    }

    /**
     * Test database connection
     */
    private function test_db_connection() {
        global $wpdb;

        try {
            $result = $wpdb->query('SELECT 1');

            return array(
                'success' => ($result !== false),
                'data' => array(
                    'status' => 'Database connection OK',
                    'db_version' => $wpdb->db_version()
                )
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'data' => array('error' => $e->getMessage())
            );
        }
    }

    /**
     * Test database tables
     */
    private function test_db_tables() {
        global $wpdb;

        try {
            $prefix = $wpdb->prefix;

            $required_tables = array(
                $prefix . 'ai_seo_social_accounts',
                $prefix . 'ai_seo_social_posts',
                $prefix . 'ai_seo_social_queue',
                $prefix . 'ai_seo_social_analytics',
                $prefix . 'ai_seo_social_trends',
                $prefix . 'ai_seo_social_settings',
                $prefix . 'ai_seo_debug_logs',
                $prefix . 'ai_seo_performance_metrics',
            );

            $existing_tables = array();
            $missing_tables = array();

            foreach ($required_tables as $table) {
                $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;

                if ($exists) {
                    $existing_tables[] = $table;
                } else {
                    $missing_tables[] = $table;
                }
            }

            return array(
                'success' => empty($missing_tables),
                'data' => array(
                    'existing_tables' => $existing_tables,
                    'missing_tables' => $missing_tables,
                    'status' => empty($missing_tables) ? 'All tables exist' : 'Some tables missing'
                )
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'data' => array('error' => $e->getMessage())
            );
        }
    }

    /**
     * Test database migration
     */
    private function test_db_migration() {
        return array(
            'success' => false,
            'data' => array('note' => 'DB migration test not implemented yet')
        );
    }

    /**
     * Test social post
     */
    public function test_social_post() {
        check_ajax_referer('ai_seo_debug', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $platform = sanitize_text_field($_POST['platform']);

        try {
            if (!class_exists('AI_SEO_Social_Media_Manager')) {
                throw new Exception('Social Media Manager not found');
            }

            $manager = AI_SEO_Social_Media_Manager::get_instance();

            $result = $manager->publish_now($content, array($platform), array(
                'created_by' => 'debug_test'
            ));

            if (is_wp_error($result[$platform])) {
                wp_send_json_error(array(
                    'error' => $result[$platform]->get_error_message(),
                    'code' => $result[$platform]->get_error_code()
                ));
            }

            wp_send_json_success(array(
                'status' => 'Posted successfully',
                'result' => $result[$platform],
                'platform' => $platform
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
        }
    }

    /**
     * Export debug report
     */
    public function export_debug_report() {
        check_ajax_referer('ai_seo_debug', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $report = $this->generate_debug_report();

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="ai-seo-debug-report-' . date('Y-m-d-H-i-s') . '.json"');

        echo json_encode($report, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Generate comprehensive debug report
     */
    private function generate_debug_report() {
        global $wpdb;

        $report = array(
            'generated_at' => current_time('mysql'),
            'site_url' => get_site_url(),
            'wordpress' => array(
                'version' => get_bloginfo('version'),
                'multisite' => is_multisite(),
                'language' => get_locale(),
            ),
            'php' => array(
                'version' => PHP_VERSION,
                'memory_limit' => WP_MEMORY_LIMIT,
                'max_execution_time' => ini_get('max_execution_time'),
                'extensions' => get_loaded_extensions(),
            ),
            'debug_config' => array(
                'WP_DEBUG' => defined('WP_DEBUG') && WP_DEBUG,
                'WP_DEBUG_LOG' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
                'AI_SEO_DEBUG' => defined('AI_SEO_DEBUG') && AI_SEO_DEBUG,
                'AI_SEO_DEBUG_LEVEL' => defined('AI_SEO_DEBUG_LEVEL') ? AI_SEO_DEBUG_LEVEL : 'INFO',
            ),
            'logs' => array(
                'errors' => $this->logger->get_errors(20),
                'warnings' => $this->logger->get_warnings(20),
                'recent' => $this->logger->get_recent_logs(50),
            ),
            'performance' => $this->performance->get_stats(),
            'database' => array(
                'version' => $wpdb->db_version(),
                'prefix' => $wpdb->prefix,
                'charset' => $wpdb->charset,
                'collation' => $wpdb->collate,
            ),
        );

        // Add social media stats if available
        if (class_exists('AI_SEO_Social_Media_Manager')) {
            $social_manager = AI_SEO_Social_Media_Manager::get_instance();
            $report['social_media'] = $social_manager->get_stats();
        }

        return $report;
    }
}

// Initialize
AI_SEO_Debug_AJAX_Handler::get_instance();
