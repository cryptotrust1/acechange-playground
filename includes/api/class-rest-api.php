<?php
/**
 * REST API Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_REST_API {

    private static $instance = null;
    private $namespace = 'ai-seo-manager/v1';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Analysis endpoints
        register_rest_route($this->namespace, '/analyze/(?P<id>\d+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'analyze_post'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        // Recommendations
        register_rest_route($this->namespace, '/recommendations', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_recommendations'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route($this->namespace, '/recommendations/(?P<id>\d+)/approve', array(
            'methods' => 'POST',
            'callback' => array($this, 'approve_recommendation'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route($this->namespace, '/recommendations/(?P<id>\d+)/reject', array(
            'methods' => 'POST',
            'callback' => array($this, 'reject_recommendation'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        // Dashboard stats
        register_rest_route($this->namespace, '/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_stats'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        // Autopilot
        register_rest_route($this->namespace, '/autopilot/toggle', array(
            'methods' => 'POST',
            'callback' => array($this, 'toggle_autopilot'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        // Settings
        register_rest_route($this->namespace, '/settings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_settings'),
            'permission_callback' => array($this, 'check_permissions'),
        ));

        register_rest_route($this->namespace, '/settings', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_settings'),
            'permission_callback' => array($this, 'check_admin_permissions'),
        ));
    }

    /**
     * Check permissions
     */
    public function check_permissions() {
        return current_user_can('edit_posts');
    }

    /**
     * Check admin permissions
     */
    public function check_admin_permissions() {
        return current_user_can('manage_options');
    }

    /**
     * Analyze post
     */
    public function analyze_post($request) {
        $post_id = $request['id'];
        $recommendations_engine = AI_SEO_Manager_SEO_Recommendations::get_instance();

        $result = $recommendations_engine->generate_comprehensive_recommendations($post_id);

        return rest_ensure_response($result);
    }

    /**
     * Get recommendations
     */
    public function get_recommendations($request) {
        $db = AI_SEO_Manager_Database::get_instance();
        $limit = intval($request->get_param('limit') ?? 20);
        $status = sanitize_text_field($request->get_param('status') ?? 'pending');

        // Whitelist validation for status parameter (Security fix)
        $allowed_statuses = array('pending', 'approved', 'rejected', 'completed', 'awaiting_approval');
        if (!in_array($status, $allowed_statuses, true)) {
            return new WP_Error('invalid_status', 'Invalid status parameter', array('status' => 400));
        }

        // Validate limit
        if ($limit < 1 || $limit > 100) {
            $limit = 20;
        }

        if ($status === 'pending') {
            $recommendations = $db->get_pending_recommendations($limit);
        } else {
            // Custom query for other statuses
            global $wpdb;
            $table = esc_sql($db->get_table('recommendations'));
            $recommendations = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE status = %s LIMIT %d",
                $status,
                $limit
            ));
        }

        return rest_ensure_response($recommendations);
    }

    /**
     * Approve recommendation
     */
    public function approve_recommendation($request) {
        $rec_id = $request['id'];
        $note = $request->get_param('note') ?? '';

        $workflow = AI_SEO_Manager_Approval_Workflow::get_instance();
        $result = $workflow->approve_recommendation($rec_id, get_current_user_id(), $note);

        if ($result) {
            return rest_ensure_response(array('success' => true));
        }

        return new WP_Error('approval_failed', 'Failed to approve recommendation', array('status' => 500));
    }

    /**
     * Reject recommendation
     */
    public function reject_recommendation($request) {
        $rec_id = $request['id'];
        $note = $request->get_param('note') ?? '';

        $workflow = AI_SEO_Manager_Approval_Workflow::get_instance();
        $result = $workflow->reject_recommendation($rec_id, get_current_user_id(), $note);

        if ($result) {
            return rest_ensure_response(array('success' => true));
        }

        return new WP_Error('rejection_failed', 'Failed to reject recommendation', array('status' => 500));
    }

    /**
     * Get dashboard stats
     */
    public function get_stats() {
        $db = AI_SEO_Manager_Database::get_instance();
        $autopilot = AI_SEO_Manager_Autopilot_Engine::get_instance();

        global $wpdb;
        $rec_table = $db->get_table('recommendations');

        $stats = array(
            'pending_recommendations' => $wpdb->get_var("SELECT COUNT(*) FROM {$rec_table} WHERE status = 'pending'"),
            'awaiting_approval' => $wpdb->get_var("SELECT COUNT(*) FROM {$rec_table} WHERE status = 'awaiting_approval'"),
            'completed' => $wpdb->get_var("SELECT COUNT(*) FROM {$rec_table} WHERE status = 'completed'"),
            'autopilot_stats' => $autopilot->get_stats(),
            'api_usage' => AI_SEO_Manager_AI_Manager::get_instance()->get_usage_stats(),
        );

        return rest_ensure_response($stats);
    }

    /**
     * Toggle autopilot
     */
    public function toggle_autopilot($request) {
        $enabled = $request->get_param('enabled') ?? false;

        $settings = AI_SEO_Manager_Settings::get_instance();
        $settings->set('autopilot_enabled', (bool) $enabled);

        return rest_ensure_response(array('success' => true, 'enabled' => (bool) $enabled));
    }

    /**
     * Get settings
     */
    public function get_settings() {
        $settings = AI_SEO_Manager_Settings::get_instance();
        $all_settings = $settings->get_all();

        // Security: Remove all sensitive credentials from response
        $sensitive_keys = array(
            'claude_api_key',
            'openai_api_key',
            'ga4_api_secret',
            'gsc_client_secret',
            'gsc_access_token',
            'gsc_refresh_token',
        );

        foreach ($sensitive_keys as $key) {
            if (isset($all_settings[$key])) {
                // Indicate if key is set without exposing value
                $all_settings[$key] = !empty($all_settings[$key]) ? '***SET***' : '';
            }
        }

        return rest_ensure_response($all_settings);
    }

    /**
     * Save settings
     */
    public function save_settings($request) {
        $settings = AI_SEO_Manager_Settings::get_instance();
        $new_settings = $request->get_json_params();

        // Validate API keys if provided
        foreach ($new_settings as $key => $value) {
            if (in_array($key, array('claude_api_key', 'openai_api_key')) && !empty($value)) {
                $provider = strpos($key, 'claude') !== false ? 'claude' : 'openai';
                if (!$settings->validate_api_key($provider, $value)) {
                    return new WP_Error('invalid_api_key', "Invalid {$provider} API key", array('status' => 400));
                }
            }
        }

        $settings->save(array_merge($settings->get_all(), $new_settings));

        return rest_ensure_response(array('success' => true));
    }
}
