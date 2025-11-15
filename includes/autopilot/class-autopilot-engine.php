<?php
/**
 * Autopilot Engine
 * Automatizuje SEO optimalizácie s approval workflow
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Autopilot_Engine {

    private static $instance = null;
    private $settings;
    private $ai_manager;
    private $db;
    private $approval_workflow;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->settings = AI_SEO_Manager_Settings::get_instance();
        $this->ai_manager = AI_SEO_Manager_AI_Manager::get_instance();
        $this->db = AI_SEO_Manager_Database::get_instance();
        $this->approval_workflow = AI_SEO_Manager_Approval_Workflow::get_instance();

        $this->init_hooks();
    }

    /**
     * Init hooks
     */
    private function init_hooks() {
        // Auto-pilot scheduled task
        add_action('ai_seo_manager_autopilot_run', array($this, 'run_autopilot'));

        // Hook when recommendations are approved
        add_action('ai_seo_manager_recommendation_approved', array($this, 'execute_approved_recommendation'), 10, 2);
    }

    /**
     * Kontrola či je autopilot enabled
     */
    public function is_enabled() {
        return $this->settings->get('autopilot_enabled', false);
    }

    /**
     * Získanie autopilot mode
     */
    public function get_mode() {
        return $this->settings->get('autopilot_mode', 'approval');
    }

    /**
     * Spustenie autopilot
     */
    public function run_autopilot() {
        if (!$this->is_enabled()) {
            return;
        }

        $mode = $this->get_mode();

        // Získaj pending recommendations
        $recommendations = $this->db->get_pending_recommendations(20);

        foreach ($recommendations as $rec) {
            $this->process_recommendation($rec, $mode);
        }
    }

    /**
     * Spracovanie odporúčania podľa režimu
     */
    private function process_recommendation($recommendation, $mode) {
        $action_data = maybe_unserialize($recommendation->action_data);

        // Kontrola či je akcia enabled v nastaveniach
        if (!$this->is_action_enabled($recommendation->recommendation_type)) {
            return;
        }

        switch ($mode) {
            case 'auto':
                // Úplne automatický režim - vykonaj hneď
                if ($this->is_safe_to_auto_execute($recommendation)) {
                    $this->execute_recommendation($recommendation);
                } else {
                    // Pošli na approval aj v auto mode pre nebezpečné zmeny
                    $this->approval_workflow->create_approval_request($recommendation);
                }
                break;

            case 'approval':
            default:
                // Approval režim - čakaj na schválenie
                $this->approval_workflow->create_approval_request($recommendation);
                break;
        }
    }

    /**
     * Kontrola či je akcia enabled
     */
    private function is_action_enabled($recommendation_type) {
        $actions = $this->settings->get('autopilot_actions', array());

        $type_to_action_map = array(
            'meta_optimization' => 'meta_description',
            'image_optimization' => 'alt_texts',
            'content_structure' => 'headings',
            'link_optimization' => 'internal_links',
        );

        $action_key = $type_to_action_map[$recommendation_type] ?? null;

        return $action_key && !empty($actions[$action_key]);
    }

    /**
     * Kontrola či je bezpečné auto-execute
     */
    private function is_safe_to_auto_execute($recommendation) {
        // Len high-confidence recommendations
        if ($recommendation->ai_confidence < 0.85) {
            return false;
        }

        // Len určité typy
        $safe_types = array(
            'meta_optimization',
            'image_optimization',
        );

        if (!in_array($recommendation->recommendation_type, $safe_types)) {
            return false;
        }

        // Nie critical/high priority bez approval
        if (in_array($recommendation->priority, array('critical', 'high'))) {
            return false;
        }

        return true;
    }

    /**
     * Vykonanie odporúčania
     */
    public function execute_recommendation($recommendation) {
        $post_id = $recommendation->post_id;
        $type = $recommendation->recommendation_type;
        $action_data = maybe_unserialize($recommendation->action_data);

        $result = false;

        switch ($type) {
            case 'meta_optimization':
                $result = $this->optimize_meta($post_id, $action_data);
                break;

            case 'image_optimization':
                $result = $this->optimize_images($post_id, $action_data);
                break;

            case 'content_structure':
                $result = $this->optimize_headings($post_id, $action_data);
                break;

            case 'keyword_optimization':
                $result = $this->optimize_keywords($post_id, $action_data);
                break;

            default:
                $this->db->log('autopilot_warning', "Unknown recommendation type: {$type}");
                break;
        }

        if ($result) {
            // Update recommendation status
            $this->db->update_recommendation_status($recommendation->id, 'completed');

            // Log success
            $this->db->log('autopilot_success', "Executed recommendation #{$recommendation->id} for post {$post_id}");

            // Fire action
            do_action('ai_seo_manager_content_optimized', $post_id);

            return true;
        }

        return false;
    }

    /**
     * Optimalizácia meta tags
     */
    private function optimize_meta($post_id, $action_data) {
        $post = get_post($post_id);
        $keyword = $action_data['keyword'] ?? get_post_meta($post_id, '_ai_seo_focus_keyword', true);

        // Generuj meta description
        $meta_description = $this->ai_manager->generate_meta_description(
            $post->post_content,
            $keyword
        );

        if (is_wp_error($meta_description)) {
            return false;
        }

        // Ulož meta description (Yoast SEO compatible)
        update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_description);

        // Backup original
        $original = get_post_meta($post_id, '_ai_seo_original_metadesc', true);
        if (empty($original)) {
            update_post_meta($post_id, '_ai_seo_original_metadesc', get_post_meta($post_id, '_yoast_wpseo_metadesc', true));
        }

        return true;
    }

    /**
     * Optimalizácia obrázkov (ALT texty)
     */
    private function optimize_images($post_id, $action_data) {
        $post = get_post($post_id);
        $content = $post->post_content;
        $keyword = $action_data['keyword'] ?? get_post_meta($post_id, '_ai_seo_focus_keyword', true);

        // Nájdi všetky obrázky bez ALT
        preg_match_all('/<img[^>]+>/i', $content, $images);

        $updated_content = $content;
        $changes_made = false;

        foreach ($images[0] as $img_tag) {
            // Skontroluj či má ALT
            if (preg_match('/alt=["\'][^"\']*["\']/i', $img_tag)) {
                continue; // Už má ALT
            }

            // Získaj kontext obrázka
            $context = $this->get_image_context($content, $img_tag, $post->post_title);

            // Generuj ALT text
            $alt_text = $this->ai_manager->generate_alt_text($context, $keyword);

            if (!empty($alt_text) && !is_wp_error($alt_text)) {
                // Pridaj ALT do img tagu
                $new_img_tag = str_replace('<img', '<img alt="' . esc_attr($alt_text) . '"', $img_tag);
                $updated_content = str_replace($img_tag, $new_img_tag, $updated_content);
                $changes_made = true;
            }
        }

        if ($changes_made) {
            // Backup original
            $original = get_post_meta($post_id, '_ai_seo_original_content', true);
            if (empty($original)) {
                update_post_meta($post_id, '_ai_seo_original_content', $content);
            }

            // Update post
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $updated_content,
            ));

            return true;
        }

        return false;
    }

    /**
     * Získanie kontextu obrázka
     */
    private function get_image_context($content, $img_tag, $title) {
        // Získaj text okolo obrázka
        $pos = strpos($content, $img_tag);
        $context_before = substr($content, max(0, $pos - 200), 200);
        $context_after = substr($content, $pos + strlen($img_tag), 200);

        $context = wp_strip_all_tags($context_before . ' ' . $context_after);
        $context = "Page title: {$title}\n\nContext: {$context}";

        return $context;
    }

    /**
     * Optimalizácia nadpisov
     */
    private function optimize_headings($post_id, $action_data) {
        $post = get_post($post_id);
        $keyword = $action_data['keyword'] ?? get_post_meta($post_id, '_ai_seo_focus_keyword', true);

        // Generuj optimalizované nadpisy
        $headings = $this->ai_manager->generate_headings($post->post_content, $keyword, 3);

        if (!empty($headings) && !is_wp_error($headings)) {
            // Ulož ako suggestions (neaplikuj automaticky)
            update_post_meta($post_id, '_ai_seo_heading_suggestions', $headings);
            return true;
        }

        return false;
    }

    /**
     * Optimalizácia keywords
     */
    private function optimize_keywords($post_id, $action_data) {
        $keyword = $action_data['keyword'] ?? '';

        if (empty($keyword)) {
            return false;
        }

        // Ulož focus keyword
        update_post_meta($post_id, '_ai_seo_focus_keyword', $keyword);

        return true;
    }

    /**
     * Handler pre approved recommendations
     */
    public function execute_approved_recommendation($recommendation_id, $user_id) {
        global $wpdb;

        $table = esc_sql($this->db->get_table('recommendations'));
        $recommendation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $recommendation_id
        ));

        if ($recommendation) {
            $this->execute_recommendation($recommendation);
        }
    }

    /**
     * Rollback zmien
     */
    public function rollback_changes($post_id) {
        $original_content = get_post_meta($post_id, '_ai_seo_original_content', true);
        $original_metadesc = get_post_meta($post_id, '_ai_seo_original_metadesc', true);

        if (!empty($original_content)) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $original_content,
            ));
        }

        if (!empty($original_metadesc)) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $original_metadesc);
        }

        $this->db->log('autopilot_rollback', "Rolled back changes for post {$post_id}");

        return true;
    }

    /**
     * Získanie štatistík autopilota
     */
    public function get_stats() {
        global $wpdb;
        $table = $this->db->get_table('recommendations');

        $stats = array(
            'total_executed' => $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'completed' AND created_by = 'ai'"),
            'pending_approval' => $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'pending'"),
            'success_rate' => 0,
        );

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE created_by = 'ai'");
        if ($total > 0) {
            $stats['success_rate'] = round(($stats['total_executed'] / $total) * 100, 2);
        }

        return $stats;
    }
}
