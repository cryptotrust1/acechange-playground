<?php
/**
 * PHPUnit Bootstrap File
 */

// Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Brain Monkey for WordPress function mocking
\Brain\Monkey\setUp();

// Define WordPress constants for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', dirname(__DIR__));
}

// Define plugin constants
define('AI_SEO_MANAGER_VERSION', '1.0.0');
define('AI_SEO_MANAGER_PLUGIN_DIR', dirname(__DIR__) . '/');
define('AI_SEO_MANAGER_PLUGIN_URL', 'http://localhost/wp-content/plugins/ai-seo-manager/');
define('AI_SEO_MANAGER_PLUGIN_FILE', dirname(__DIR__) . '/ai-seo-manager.php');

// Mock WordPress database
global $wpdb;
$wpdb = Mockery::mock('wpdb');
$wpdb->prefix = 'wp_';

// Test helper functions
function get_test_post_data() {
    return array(
        'ID' => 1,
        'post_title' => 'Test Post',
        'post_content' => 'This is test content for SEO analysis. It contains multiple paragraphs and keywords.',
        'post_name' => 'test-post',
        'post_type' => 'post',
        'post_status' => 'publish',
    );
}

function get_test_recommendation() {
    return (object) array(
        'id' => 1,
        'post_id' => 1,
        'recommendation_type' => 'meta_optimization',
        'priority' => 'high',
        'title' => 'Optimize meta description',
        'description' => 'Add a compelling meta description',
        'action_data' => serialize(array('action' => 'generate_meta')),
        'status' => 'pending',
        'ai_confidence' => 0.95,
        'created_at' => '2025-01-15 10:00:00',
    );
}
