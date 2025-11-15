<?php
/**
 * Base REST API controller.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/api
 */

/**
 * Base controller for REST API endpoints.
 */
class Claude_SEO_REST_Controller extends WP_REST_Controller {

    /**
     * Namespace.
     *
     * @var string
     */
    protected $namespace = 'claude-seo/v1';

    /**
     * Check if user can manage SEO.
     *
     * @return bool True if authorized.
     */
    public function permissions_check() {
        return current_user_can('edit_posts');
    }

    /**
     * Check if user can manage settings.
     *
     * @return bool True if authorized.
     */
    public function settings_permissions_check() {
        return current_user_can('manage_options');
    }
}
