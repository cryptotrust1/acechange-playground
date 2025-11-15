<?php
/**
 * Autoloader pre triedy pluginu
 */

if (!defined('ABSPATH')) {
    exit;
}

spl_autoload_register(function ($class) {
    $prefix = 'AI_SEO_Manager_';
    $base_dir = AI_SEO_MANAGER_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $relative_class = strtolower($relative_class);
    $relative_class = str_replace('_', '-', $relative_class);

    $file = $base_dir . 'class-' . $relative_class . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
