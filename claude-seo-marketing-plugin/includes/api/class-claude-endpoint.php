<?php
/**
 * Claude AI REST endpoint.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/api
 */

/**
 * REST API endpoint for Claude AI features.
 */
class Claude_SEO_Claude_Endpoint extends Claude_SEO_REST_Controller {

    /**
     * Register routes.
     */
    public function register_routes() {
        register_rest_route($this->namespace, '/claude/generate', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'generate_content'),
            'permission_callback' => array($this, 'permissions_check'),
            'args' => array(
                'template' => array(
                    'required' => true,
                    'type' => 'string'
                ),
                'args' => array(
                    'required' => true,
                    'type' => 'object'
                )
            )
        ));
    }

    /**
     * Generate content with Claude.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response.
     */
    public function generate_content($request) {
        $template = $request->get_param('template');
        $args = $request->get_param('args');

        $api_client = new Claude_SEO_API_Client();
        $result = $api_client->generate_with_template($template, $args);

        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'error' => $result->get_error_message()
            ), 400);
        }

        return new WP_REST_Response(array(
            'content' => $result
        ), 200);
    }
}
