<?php
/**
 * Redirect handler.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/public
 */

/**
 * Handles URL redirects.
 */
class Claude_SEO_Redirects {

    /**
     * Handle redirects.
     */
    public function handle_redirects() {
        $current_url = $_SERVER['REQUEST_URI'];

        // Get matching redirect
        $redirect = $this->get_matching_redirect($current_url);

        if (!$redirect) {
            return;
        }

        // Track hit
        $this->track_redirect_hit($redirect->id);

        // Perform redirect
        wp_redirect($redirect->target_url, $redirect->redirect_type);
        exit;
    }

    /**
     * Get matching redirect for URL.
     *
     * @param string $url URL to match.
     * @return object|null Redirect or null.
     */
    private function get_matching_redirect($url) {
        global $wpdb;
        $table = Claude_SEO_Database::get_table_name('redirects');

        // Try exact match first
        $redirect = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE source_url = %s
             AND status = 'active'
             AND regex = 0
             LIMIT 1",
            $url
        ));

        if ($redirect) {
            return $redirect;
        }

        // Try regex matches
        $regex_redirects = $wpdb->get_results(
            "SELECT * FROM {$table}
             WHERE status = 'active'
             AND regex = 1"
        );

        foreach ($regex_redirects as $redirect) {
            if (preg_match('/' . $redirect->source_url . '/', $url)) {
                return $redirect;
            }
        }

        return null;
    }

    /**
     * Track redirect hit.
     *
     * @param int $redirect_id Redirect ID.
     */
    private function track_redirect_hit($redirect_id) {
        global $wpdb;
        $table = Claude_SEO_Database::get_table_name('redirects');

        $wpdb->query($wpdb->prepare(
            "UPDATE {$table}
             SET hit_count = hit_count + 1,
                 last_hit = NOW()
             WHERE id = %d",
            $redirect_id
        ));
    }
}
