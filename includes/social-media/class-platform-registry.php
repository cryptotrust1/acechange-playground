<?php
/**
 * Platform Registry
 * Správa registrácie a získavania platform clients
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Social_Platform_Registry {

    private static $instance = null;
    private $platforms = array();
    private $logger;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize debug logger if available
        if (class_exists('AI_SEO_Manager_Debug_Logger')) {
            $this->logger = AI_SEO_Manager_Debug_Logger::get_instance();
        }
    }

    /**
     * Registrácia platform clienta
     */
    public function register($platform_name, $client_instance) {
        if (!$this->is_valid_client($client_instance)) {
            if ($this->logger) {
                $this->logger->error('Invalid platform client', array(
                    'platform' => $platform_name,
                    'class' => get_class($client_instance),
                ));
            }
            return false;
        }

        $this->platforms[$platform_name] = $client_instance;

        if ($this->logger) {
            $this->logger->info('Platform registered', array(
                'platform' => $platform_name,
                'class' => get_class($client_instance),
            ));
        }

        return true;
    }

    /**
     * Kontrola či je client validný
     */
    private function is_valid_client($client) {
        return $client instanceof AI_SEO_Social_Platform_Client;
    }

    /**
     * Získanie platform clienta
     */
    public function get($platform_name) {
        if (!isset($this->platforms[$platform_name])) {
            if ($this->logger) {
                $this->logger->warning('Platform not found in registry', array(
                    'platform' => $platform_name,
                    'available' => array_keys($this->platforms),
                ));
            }
            return null;
        }

        return $this->platforms[$platform_name];
    }

    /**
     * Získanie všetkých registrovaných platforiem
     */
    public function get_all() {
        return $this->platforms;
    }

    /**
     * Získanie všetkých aktívnych platforiem
     */
    public function get_all_active() {
        $active = array();

        foreach ($this->platforms as $name => $client) {
            if ($client->is_authenticated()) {
                $active[$name] = $client;
            }
        }

        return $active;
    }

    /**
     * Kontrola či je platforma dostupná
     */
    public function is_platform_available($platform_name) {
        return isset($this->platforms[$platform_name]);
    }

    /**
     * Kontrola či je platforma aktívna
     */
    public function is_platform_active($platform_name) {
        if (!$this->is_platform_available($platform_name)) {
            return false;
        }

        return $this->platforms[$platform_name]->is_authenticated();
    }

    /**
     * Získanie capabilities platformy
     */
    public function get_platform_capabilities($platform_name) {
        $client = $this->get($platform_name);

        if (!$client) {
            return array();
        }

        return $client->get_capabilities();
    }

    /**
     * Získanie všetkých názvov platforiem
     */
    public function get_platform_names() {
        return array_keys($this->platforms);
    }

    /**
     * Unregister platformu
     */
    public function unregister($platform_name) {
        if (isset($this->platforms[$platform_name])) {
            unset($this->platforms[$platform_name]);

            if ($this->logger) {
                $this->logger->info('Platform unregistered', array(
                    'platform' => $platform_name,
                ));
            }

            return true;
        }

        return false;
    }

    /**
     * Získanie štatistík registry
     */
    public function get_stats() {
        $total = count($this->platforms);
        $active = count($this->get_all_active());

        return array(
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'platforms' => $this->get_platform_names(),
        );
    }
}
