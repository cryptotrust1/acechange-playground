<?php
/**
 * Core Web Vitals REST API endpoint.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/api
 */

/**
 * REST API endpoint for receiving CWV metrics from frontend.
 */
class Claude_SEO_CWV_Endpoint extends Claude_SEO_REST_Controller {

    /**
     * Register routes.
     */
    public function register_routes() {
        register_rest_route($this->namespace, '/cwv', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'receive_metrics'),
            'permission_callback' => '__return_true', // Public endpoint
            'args' => array(
                'metrics' => array(
                    'required' => true,
                    'type' => 'array'
                )
            )
        ));

        register_rest_route($this->namespace, '/cwv/status/(?P<page_id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_status'),
            'permission_callback' => array($this, 'permissions_check'),
            'args' => array(
                'page_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                )
            )
        ));
    }

    /**
     * Receive metrics batch from frontend.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response.
     */
    public function receive_metrics($request) {
        $batch = $request->get_json_params();

        if (empty($batch['metrics'])) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'No metrics provided'
            ), 400);
        }

        try {
            $processor = new Claude_SEO_CWV_Processor();
            $processor->process_batch($batch);

            return new WP_REST_Response(array(
                'success' => true,
                'processed' => count($batch['metrics'])
            ), 200);

        } catch (Exception $e) {
            Claude_SEO_Logger::error('CWV processing failed', array(
                'error' => $e->getMessage()
            ));

            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Processing failed'
            ), 500);
        }
    }

    /**
     * Get CWV status for page.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response.
     */
    public function get_status($request) {
        $page_id = $request->get_param('page_id');

        $status = Claude_SEO_CWV_Processor::get_page_cwv_status($page_id);

        return new WP_REST_Response($status, 200);
    }
}
