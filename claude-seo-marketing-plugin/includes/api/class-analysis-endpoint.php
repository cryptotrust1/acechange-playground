<?php
/**
 * SEO analysis REST endpoint.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/api
 */

/**
 * REST API endpoint for SEO analysis.
 */
class Claude_SEO_Analysis_Endpoint extends Claude_SEO_REST_Controller {

    /**
     * Register routes.
     */
    public function register_routes() {
        register_rest_route($this->namespace, '/analyze/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'analyze_post'),
            'permission_callback' => array($this, 'permissions_check'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                )
            )
        ));
    }

    /**
     * Analyze post.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response.
     */
    public function analyze_post($request) {
        $post_id = $request->get_param('id');

        $analysis = Claude_SEO_Analyzer::analyze_post($post_id);

        return new WP_REST_Response($analysis, 200);
    }
}
