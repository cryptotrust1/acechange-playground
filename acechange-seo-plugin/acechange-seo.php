<?php
/**
 * Plugin Name: AceChange SEO Plugin
 * Plugin URI: https://github.com/cryptotrust1/acechange-playground
 * Description: Profesionálny SEO plugin pre automatickú optimalizáciu meta tagov, Open Graph, Schema.org a ďalších SEO prvkov. 100% White Hat - bezpečný pre Google.
 * Version: 1.0.0
 * Author: AceChange
 * Author URI: https://acechange.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: acechange-seo
 * Domain Path: /languages
 */

// Zabránenie priamemu prístupu
if (!defined('ABSPATH')) {
    exit;
}

// Definície konštánt
define('ACECHANGE_SEO_VERSION', '1.0.0');
define('ACECHANGE_SEO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACECHANGE_SEO_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Hlavná trieda AceChange SEO Plugin
 */
class AceChange_SEO {

    private static $instance = null;

    /**
     * Singleton pattern
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Konštruktor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Načítanie závislostí
     */
    private function load_dependencies() {
        require_once ACECHANGE_SEO_PLUGIN_DIR . 'includes/class-seo-meta.php';
        require_once ACECHANGE_SEO_PLUGIN_DIR . 'includes/class-seo-schema.php';
        require_once ACECHANGE_SEO_PLUGIN_DIR . 'includes/class-seo-sitemap.php';
        require_once ACECHANGE_SEO_PLUGIN_DIR . 'admin/class-admin-interface.php';
    }

    /**
     * Inicializácia hookov
     */
    private function init_hooks() {
        // Aktivácia/deaktivácia pluginu
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Admin rozhranie
        if (is_admin()) {
            new AceChange_SEO_Admin();
        }

        // Frontend SEO funkcie
        add_action('wp_head', array($this, 'output_seo_tags'), 1);
        add_action('init', array($this, 'init_seo_features'));
    }

    /**
     * Aktivácia pluginu
     */
    public function activate() {
        // Vytvorenie predvolených nastavení
        $default_settings = array(
            'auto_meta_tags' => true,
            'auto_open_graph' => true,
            'auto_schema' => true,
            'auto_sitemap' => true,
            'meta_description_length' => 160,
            'auto_keywords' => false, // Google keywords nepoužíva, ale môže byť užitočné pre iné vyhľadávače
            'social_share_image' => '',
            'twitter_card' => true,
            'canonical_urls' => true,
            'noindex_archives' => false,
            'noindex_search' => true
        );

        add_option('acechange_seo_settings', $default_settings);

        // Flush rewrite rules pre sitemap
        flush_rewrite_rules();
    }

    /**
     * Deaktivácia pluginu
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Inicializácia SEO funkcií
     */
    public function init_seo_features() {
        $settings = get_option('acechange_seo_settings', array());

        // Inicializácia sitemap
        if (!empty($settings['auto_sitemap'])) {
            new AceChange_SEO_Sitemap();
        }
    }

    /**
     * Výstup SEO tagov do <head>
     */
    public function output_seo_tags() {
        $settings = get_option('acechange_seo_settings', array());

        // Meta tagy
        if (!empty($settings['auto_meta_tags'])) {
            AceChange_SEO_Meta::output_meta_tags();
        }

        // Open Graph tagy
        if (!empty($settings['auto_open_graph'])) {
            AceChange_SEO_Meta::output_open_graph_tags();
        }

        // Twitter Card tagy
        if (!empty($settings['twitter_card'])) {
            AceChange_SEO_Meta::output_twitter_card_tags();
        }

        // Schema.org markup
        if (!empty($settings['auto_schema'])) {
            AceChange_SEO_Schema::output_schema_markup();
        }

        // Canonical URL
        if (!empty($settings['canonical_urls'])) {
            AceChange_SEO_Meta::output_canonical_url();
        }
    }
}

/**
 * Spustenie pluginu
 */
function acechange_seo_init() {
    return AceChange_SEO::get_instance();
}

// Spustenie pluginu
acechange_seo_init();
