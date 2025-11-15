<?php
/**
 * Plugin Name: Claude SEO Pro
 * Plugin URI: https://claudeseo.pro
 * Description: AI-powered WordPress SEO & Marketing Plugin with Claude AI integration. Delivers maximum SEO effectiveness with 100% stability following Google Search Central guidelines.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Claude SEO Team
 * Author URI: https://claudeseo.pro
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: claude-seo
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define('CLAUDE_SEO_VERSION', '1.0.0');
define('CLAUDE_SEO_PLUGIN_FILE', __FILE__);
define('CLAUDE_SEO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CLAUDE_SEO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CLAUDE_SEO_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_claude_seo() {
    require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/class-activator.php';
    Claude_SEO_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_claude_seo() {
    require_once CLAUDE_SEO_PLUGIN_DIR . 'includes/class-deactivator.php';
    Claude_SEO_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_claude_seo');
register_deactivation_hook(__FILE__, 'deactivate_claude_seo');

/**
 * The core plugin class.
 */
require CLAUDE_SEO_PLUGIN_DIR . 'includes/class-core.php';

/**
 * Begins execution of the plugin.
 */
function run_claude_seo() {
    $plugin = new Claude_SEO_Core();
    $plugin->run();
}

run_claude_seo();
