<?php
/**
 * Approval Workflow
 * Schvaľovací proces pre AI-generované zmeny
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Approval_Workflow {

    private static $instance = null;
    private $db;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = AI_SEO_Manager_Database::get_instance();
        $this->init_hooks();
    }

    /**
     * Init hooks
     */
    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_ai_seo_approve_recommendation', array($this, 'ajax_approve_recommendation'));
        add_action('wp_ajax_ai_seo_reject_recommendation', array($this, 'ajax_reject_recommendation'));
        add_action('wp_ajax_ai_seo_get_pending_approvals', array($this, 'ajax_get_pending_approvals'));

        // Email notifications
        add_action('ai_seo_manager_approval_request_created', array($this, 'send_approval_notification'));
    }

    /**
     * Vytvorenie approval request
     */
    public function create_approval_request($recommendation) {
        // Update status to 'awaiting_approval'
        global $wpdb;
        $table = $this->db->get_table('recommendations');

        $wpdb->update(
            $table,
            array('status' => 'awaiting_approval'),
            array('id' => $recommendation->id),
            array('%s'),
            array('%d')
        );

        // Log
        $this->db->log('approval_request_created', "Approval request for recommendation #{$recommendation->id}");

        // Fire action for notifications
        do_action('ai_seo_manager_approval_request_created', $recommendation);

        return true;
    }

    /**
     * Schválenie odporúčania
     */
    public function approve_recommendation($recommendation_id, $user_id, $note = '') {
        // Save approval
        $this->db->save_approval($recommendation_id, $user_id, 'approved', $note);

        // Update recommendation status
        $this->db->update_recommendation_status($recommendation_id, 'approved');

        // Log
        $this->db->log('recommendation_approved', "Recommendation #{$recommendation_id} approved by user {$user_id}");

        // Fire action (autopilot engine will execute)
        do_action('ai_seo_manager_recommendation_approved', $recommendation_id, $user_id);

        return true;
    }

    /**
     * Zamietnutie odporúčania
     */
    public function reject_recommendation($recommendation_id, $user_id, $note = '') {
        // Save rejection
        $this->db->save_approval($recommendation_id, $user_id, 'rejected', $note);

        // Update recommendation status
        $this->db->update_recommendation_status($recommendation_id, 'rejected');

        // Log
        $this->db->log('recommendation_rejected', "Recommendation #{$recommendation_id} rejected by user {$user_id}");

        return true;
    }

    /**
     * Získanie pending approvals
     */
    public function get_pending_approvals($limit = 10) {
        global $wpdb;
        $table = $this->db->get_table('recommendations');

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table}
            WHERE status IN ('awaiting_approval', 'pending')
            ORDER BY
                FIELD(priority, 'critical', 'high', 'medium', 'low'),
                ai_confidence DESC,
                created_at DESC
            LIMIT %d",
            $limit
        );

        $results = $wpdb->get_results($sql);

        // Unserialize action_data
        foreach ($results as $result) {
            if (!empty($result->action_data)) {
                $result->action_data = maybe_unserialize($result->action_data);
            }

            // Add post data
            if ($result->post_id) {
                $post = get_post($result->post_id);
                $result->post_title = $post ? $post->post_title : '';
                $result->post_url = $post ? get_permalink($post) : '';
            }
        }

        return $results;
    }

    /**
     * AJAX: Approve recommendation
     */
    public function ajax_approve_recommendation() {
        check_ajax_referer('ai_seo_manager_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $recommendation_id = intval($_POST['recommendation_id'] ?? 0);
        $note = sanitize_textarea_field($_POST['note'] ?? '');

        if (!$recommendation_id) {
            wp_send_json_error(array('message' => 'Invalid recommendation ID'));
        }

        $result = $this->approve_recommendation($recommendation_id, get_current_user_id(), $note);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Recommendation approved and executed!', 'ai-seo-manager')
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to approve recommendation'));
        }
    }

    /**
     * AJAX: Reject recommendation
     */
    public function ajax_reject_recommendation() {
        check_ajax_referer('ai_seo_manager_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $recommendation_id = intval($_POST['recommendation_id'] ?? 0);
        $note = sanitize_textarea_field($_POST['note'] ?? '');

        if (!$recommendation_id) {
            wp_send_json_error(array('message' => 'Invalid recommendation ID'));
        }

        $result = $this->reject_recommendation($recommendation_id, get_current_user_id(), $note);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Recommendation rejected', 'ai-seo-manager')
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to reject recommendation'));
        }
    }

    /**
     * AJAX: Get pending approvals
     */
    public function ajax_get_pending_approvals() {
        check_ajax_referer('ai_seo_manager_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $approvals = $this->get_pending_approvals(20);

        wp_send_json_success(array(
            'approvals' => $approvals,
            'count' => count($approvals),
        ));
    }

    /**
     * Odoslanie notification emailu
     */
    public function send_approval_notification($recommendation) {
        $settings = AI_SEO_Manager_Settings::get_instance();

        // Check if notifications are enabled
        if (!apply_filters('ai_seo_manager_send_approval_notifications', true)) {
            return;
        }

        // Get admin email
        $to = get_option('admin_email');
        $subject = sprintf(
            __('[%s] New SEO Recommendation Awaiting Approval', 'ai-seo-manager'),
            get_bloginfo('name')
        );

        $post_title = '';
        if ($recommendation->post_id) {
            $post = get_post($recommendation->post_id);
            $post_title = $post ? $post->post_title : '';
        }

        $message = sprintf(
            __("A new SEO recommendation is awaiting your approval:\n\n", 'ai-seo-manager')
        );
        $message .= sprintf(__("Title: %s\n", 'ai-seo-manager'), $recommendation->title);
        $message .= sprintf(__("Priority: %s\n", 'ai-seo-manager'), ucfirst($recommendation->priority));
        $message .= sprintf(__("AI Confidence: %d%%\n", 'ai-seo-manager'), round($recommendation->ai_confidence * 100));

        if ($post_title) {
            $message .= sprintf(__("Affected Post: %s\n", 'ai-seo-manager'), $post_title);
        }

        $message .= sprintf(__("\nDescription: %s\n\n", 'ai-seo-manager'), $recommendation->description);

        $message .= sprintf(
            __("Review and approve at: %s\n", 'ai-seo-manager'),
            admin_url('admin.php?page=ai-seo-manager-approvals')
        );

        wp_mail($to, $subject, $message);
    }

    /**
     * Bulk approve
     */
    public function bulk_approve($recommendation_ids, $user_id) {
        $results = array(
            'success' => 0,
            'failed' => 0,
        );

        foreach ($recommendation_ids as $rec_id) {
            if ($this->approve_recommendation($rec_id, $user_id)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Bulk reject
     */
    public function bulk_reject($recommendation_ids, $user_id, $note = '') {
        $results = array(
            'success' => 0,
            'failed' => 0,
        );

        foreach ($recommendation_ids as $rec_id) {
            if ($this->reject_recommendation($rec_id, $user_id, $note)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Získanie approval history
     */
    public function get_approval_history($limit = 50) {
        global $wpdb;
        $table = $this->db->get_table('approvals');

        $sql = $wpdb->prepare(
            "SELECT a.*, r.title as recommendation_title, r.priority
            FROM {$table} a
            LEFT JOIN {$this->db->get_table('recommendations')} r ON a.recommendation_id = r.id
            ORDER BY a.created_at DESC
            LIMIT %d",
            $limit
        );

        return $wpdb->get_results($sql);
    }
}
