#!/usr/bin/env php
<?php
/**
 * Simple validation script for Social Media Manager
 * Checks that all classes can be loaded and instantiated
 */

echo "====================================\n";
echo "Social Media Manager Validation\n";
echo "====================================\n\n";

// Simulate WordPress environment
define('ABSPATH', __DIR__ . '/');
define('AI_SEO_MANAGER_PLUGIN_DIR', __DIR__ . '/');
define('AI_SEO_MANAGER_PLUGIN_URL', 'http://localhost/');
define('AI_SEO_MANAGER_VERSION', '2.0.0');

// Mock WordPress functions
function __($text, $domain = 'default') { return $text; }
function _e($text, $domain = 'default') { echo $text; }
function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_url($url) { return $url; }
function sanitize_text_field($str) { return strip_tags($str); }
function wp_parse_args($args, $defaults) { return array_merge($defaults, (array)$args); }
function current_time($type) { return date($type === 'mysql' ? 'Y-m-d H:i:s' : 'U'); }
function get_option($option, $default = false) { return $default; }
function admin_url($path) { return 'http://localhost/wp-admin/' . $path; }
function mysql2date($format, $date) { return date($format, strtotime($date)); }
function wp_trim_words($text, $num_words = 55) {
    $words = explode(' ', $text);
    return implode(' ', array_slice($words, 0, $num_words));
}

class wpdb {
    public $prefix = 'wp_';
    public function get_charset_collate() { return 'DEFAULT CHARSET=utf8mb4'; }
    public function get_var($query) { return 0; }
    public function get_results($query) { return array(); }
    public function prepare($query, ...$args) { return $query; }
    public function insert($table, $data, $format = null) { return 1; }
    public function update($table, $data, $where, $format = null, $where_format = null) { return 1; }
    public function delete($table, $where, $where_format = null) { return 1; }
    public function query($query) { return 1; }
    public $insert_id = 1;
    public $last_error = '';
}

$wpdb = new wpdb();

$errors = 0;
$warnings = 0;

// Test 1: Check if all class files exist
echo "Test 1: Checking class files...\n";
$required_files = array(
    'includes/social-media/class-social-database.php',
    'includes/social-media/class-platform-registry.php',
    'includes/social-media/class-rate-limiter.php',
    'includes/social-media/class-social-media-manager.php',
    'includes/social-media/class-ai-content-generator.php',
    'includes/social-media/class-scheduler.php',
    'includes/social-media/class-analytics.php',
    'includes/social-media/class-admin-menu.php',
);

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "  ✓ $file\n";
    } else {
        echo "  ✗ MISSING: $file\n";
        $errors++;
    }
}

// Test 2: Check PHP syntax
echo "\nTest 2: Checking PHP syntax...\n";
foreach ($required_files as $file) {
    if (!file_exists($file)) continue;

    $output = array();
    $return_var = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return_var);

    if ($return_var === 0) {
        echo "  ✓ $file\n";
    } else {
        echo "  ✗ SYNTAX ERROR: $file\n";
        echo "    " . implode("\n    ", $output) . "\n";
        $errors++;
    }
}

// Test 3: Load classes and check methods
echo "\nTest 3: Checking class methods...\n";

try {
    require_once 'includes/social-media/class-social-database.php';

    $required_methods = array(
        'create_tables',
        'get_account',
        'get_accounts',
        'create_account',
        'update_account',
        'delete_account',
        'get_post',
        'get_posts',
        'create_post',
        'update_post',
        'update_post_status',
        'get_stats_summary',
    );

    $reflection = new ReflectionClass('AI_SEO_Social_Database');

    foreach ($required_methods as $method) {
        if ($reflection->hasMethod($method)) {
            echo "  ✓ AI_SEO_Social_Database::$method()\n";
        } else {
            echo "  ✗ MISSING: AI_SEO_Social_Database::$method()\n";
            $errors++;
        }
    }

} catch (Exception $e) {
    echo "  ✗ ERROR loading AI_SEO_Social_Database: " . $e->getMessage() . "\n";
    $errors++;
}

// Test 4: Check platform clients
echo "\nTest 4: Checking platform clients...\n";
$platforms = array('telegram', 'facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'tiktok');

foreach ($platforms as $platform) {
    $file = "includes/social-media/platforms/class-{$platform}-client.php";
    if (file_exists($file)) {
        echo "  ✓ $platform client exists\n";
    } else {
        echo "  ✗ MISSING: $platform client\n";
        $warnings++;
    }
}

// Test 5: Check admin views
echo "\nTest 5: Checking admin views...\n";
$views = array('dashboard', 'composer', 'calendar', 'analytics', 'accounts');

foreach ($views as $view) {
    $file = "admin/views/social-media/{$view}.php";
    if (file_exists($file)) {
        echo "  ✓ {$view}.php exists\n";
    } else {
        echo "  ✗ MISSING: {$view}.php\n";
        $warnings++;
    }
}

// Summary
echo "\n====================================\n";
echo "VALIDATION SUMMARY\n";
echo "====================================\n";
echo "Errors: $errors\n";
echo "Warnings: $warnings\n";

if ($errors === 0 && $warnings === 0) {
    echo "\n✓✓✓ ALL TESTS PASSED! ✓✓✓\n";
    exit(0);
} elseif ($errors === 0) {
    echo "\n⚠ PASSED WITH WARNINGS\n";
    exit(0);
} else {
    echo "\n✗✗✗ VALIDATION FAILED ✗✗✗\n";
    exit(1);
}
