<?php
/**
 * Google Analytics 4 Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Google_Analytics {

    private static $instance = null;
    private $settings;
    private $measurement_id;
    private $api_secret;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->settings = AI_SEO_Manager_Settings::get_instance();
        $this->measurement_id = $this->settings->get('ga4_measurement_id');
        $this->api_secret = $this->settings->get('ga4_api_secret');

        $this->init_hooks();
    }

    /**
     * Init hooks
     */
    private function init_hooks() {
        add_action('wp_head', array($this, 'add_tracking_code'));
        add_action('ai_seo_manager_recommendation_approved', array($this, 'track_recommendation_event'), 10, 2);
        add_action('ai_seo_manager_content_optimized', array($this, 'track_optimization_event'));
    }

    /**
     * Pridanie GA4 tracking kódu
     */
    public function add_tracking_code() {
        if (empty($this->measurement_id)) {
            return;
        }

        // Nepridávaj tracking pre adminov ak je to nastavené
        if (current_user_can('manage_options') && apply_filters('ai_seo_manager_exclude_admin_tracking', true)) {
            return;
        }

        ?>
        <!-- Google Analytics 4 - AI SEO Manager -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($this->measurement_id); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?php echo esc_js($this->measurement_id); ?>');
        </script>
        <?php
    }

    /**
     * Získanie performance dát
     */
    public function get_page_performance($post_id) {
        $url = get_permalink($post_id);

        // Pre produkčné použitie by tu bola integrácia s GA4 Data API
        // https://developers.google.com/analytics/devguides/reporting/data/v1

        // Simulácia dát (v produkčnej verzii nahradiť skutočným API callom)
        $cached = get_transient('ai_seo_ga4_performance_' . $post_id);
        if ($cached !== false) {
            return $cached;
        }

        // Placeholder data structure
        $performance = array(
            'pageviews' => 0,
            'unique_pageviews' => 0,
            'avg_time_on_page' => 0,
            'bounce_rate' => 0,
            'sessions' => 0,
            'conversions' => 0,
            'period' => '30_days',
        );

        // Cache na 1 hodinu
        set_transient('ai_seo_ga4_performance_' . $post_id, $performance, HOUR_IN_SECONDS);

        return $performance;
    }

    /**
     * Získanie top performing stránok
     */
    public function get_top_pages($limit = 10, $days = 30) {
        $cached = get_transient('ai_seo_ga4_top_pages_' . $days);
        if ($cached !== false) {
            return $cached;
        }

        // V produkčnej verzii: GA4 Data API call
        $top_pages = array();

        set_transient('ai_seo_ga4_top_pages_' . $days, $top_pages, HOUR_IN_SECONDS);

        return $top_pages;
    }

    /**
     * Track custom event - odporúčanie schválené
     */
    public function track_recommendation_event($recommendation_id, $user_id) {
        if (empty($this->measurement_id) || empty($this->api_secret)) {
            return;
        }

        $this->send_event('seo_recommendation_approved', array(
            'recommendation_id' => $recommendation_id,
            'user_id' => $user_id,
        ));
    }

    /**
     * Track custom event - obsah optimalizovaný
     */
    public function track_optimization_event($post_id) {
        if (empty($this->measurement_id) || empty($this->api_secret)) {
            return;
        }

        $this->send_event('content_optimized', array(
            'post_id' => $post_id,
            'post_type' => get_post_type($post_id),
        ));
    }

    /**
     * Odoslanie custom eventu do GA4
     */
    private function send_event($event_name, $params = array()) {
        if (empty($this->measurement_id) || empty($this->api_secret)) {
            return false;
        }

        $url = sprintf(
            'https://www.google-analytics.com/mp/collect?measurement_id=%s&api_secret=%s',
            $this->measurement_id,
            $this->api_secret
        );

        $body = array(
            'client_id' => $this->get_client_id(),
            'events' => array(
                array(
                    'name' => $event_name,
                    'params' => $params,
                )
            )
        );

        $response = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($body),
            'timeout' => 10,
        ));

        return !is_wp_error($response);
    }

    /**
     * Získanie alebo vytvorenie client ID
     */
    private function get_client_id() {
        $client_id = get_option('ai_seo_manager_ga4_client_id');

        if (!$client_id) {
            $client_id = wp_generate_uuid4();
            update_option('ai_seo_manager_ga4_client_id', $client_id);
        }

        return $client_id;
    }

    /**
     * Získanie conversion dát
     */
    public function get_conversion_data($post_id = null) {
        // V produkčnej verzii: GA4 Data API call pre conversion data

        return array(
            'total_conversions' => 0,
            'conversion_rate' => 0,
            'revenue' => 0,
        );
    }
}
