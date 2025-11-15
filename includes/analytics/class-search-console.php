<?php
/**
 * Google Search Console Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Manager_Search_Console {

    private static $instance = null;
    private $settings;
    private $api_url = 'https://www.googleapis.com/webmasters/v3';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->settings = AI_SEO_Manager_Settings::get_instance();
    }

    /**
     * Získanie access tokenu
     */
    private function get_access_token() {
        $access_token = $this->settings->get('gsc_access_token');
        $refresh_token = $this->settings->get('gsc_refresh_token');

        // Kontrola expirácie a refresh ak je potreba
        $token_expiry = get_option('ai_seo_manager_gsc_token_expiry', 0);

        if (time() > $token_expiry && !empty($refresh_token)) {
            $access_token = $this->refresh_access_token($refresh_token);
        }

        return $access_token;
    }

    /**
     * Refresh access tokenu
     */
    private function refresh_access_token($refresh_token) {
        $client_id = $this->settings->get('gsc_client_id');
        $client_secret = $this->settings->get('gsc_client_secret');

        $response = wp_remote_post('https://oauth2.googleapis.com/token', array(
            'body' => array(
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token',
            ),
        ));

        if (is_wp_error($response)) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['access_token'])) {
            $this->settings->set('gsc_access_token', $body['access_token']);
            update_option('ai_seo_manager_gsc_token_expiry', time() + ($body['expires_in'] ?? 3600));
            return $body['access_token'];
        }

        return null;
    }

    /**
     * API request
     */
    private function api_request($endpoint, $method = 'GET', $body = null) {
        $access_token = $this->get_access_token();

        if (empty($access_token)) {
            return new WP_Error('no_access_token', 'Google Search Console not authenticated');
        }

        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'method' => $method,
            'timeout' => 30,
        );

        if ($body !== null) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($this->api_url . $endpoint, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200) {
            return new WP_Error('api_error', $body['error']['message'] ?? 'Unknown error');
        }

        return $body;
    }

    /**
     * Získanie search analytics dát
     */
    public function get_search_analytics($post_id = null, $days = 30) {
        $site_url = get_site_url();
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        $end_date = date('Y-m-d');

        $dimensions = array('query', 'page', 'country', 'device');

        $body = array(
            'startDate' => $start_date,
            'endDate' => $end_date,
            'dimensions' => $dimensions,
            'rowLimit' => 1000,
        );

        // Filter pre konkrétnu stránku
        if ($post_id) {
            $page_url = get_permalink($post_id);
            $body['dimensionFilterGroups'] = array(
                array(
                    'filters' => array(
                        array(
                            'dimension' => 'page',
                            'expression' => $page_url,
                        )
                    )
                )
            );
        }

        $cache_key = 'ai_seo_gsc_analytics_' . ($post_id ?? 'all') . '_' . $days;
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $endpoint = '/sites/' . urlencode($site_url) . '/searchAnalytics/query';
        $result = $this->api_request($endpoint, 'POST', $body);

        if (is_wp_error($result)) {
            return $result;
        }

        // Cache na 6 hodín
        set_transient($cache_key, $result, 6 * HOUR_IN_SECONDS);

        return $result;
    }

    /**
     * Získanie top keywords pre stránku
     */
    public function get_page_keywords($post_id, $limit = 20) {
        $data = $this->get_search_analytics($post_id);

        if (is_wp_error($data) || !isset($data['rows'])) {
            return array();
        }

        $keywords = array();

        foreach ($data['rows'] as $row) {
            if (isset($row['keys'][0])) { // keys[0] je query
                $keywords[] = array(
                    'keyword' => $row['keys'][0],
                    'clicks' => $row['clicks'] ?? 0,
                    'impressions' => $row['impressions'] ?? 0,
                    'ctr' => $row['ctr'] ?? 0,
                    'position' => $row['position'] ?? 0,
                );
            }

            if (count($keywords) >= $limit) {
                break;
            }
        }

        // Zoraď podľa impressions
        usort($keywords, function($a, $b) {
            return $b['impressions'] - $a['impressions'];
        });

        return $keywords;
    }

    /**
     * Získanie pozícií pre keywords
     */
    public function get_keyword_positions($keywords = array()) {
        if (empty($keywords)) {
            return array();
        }

        $site_url = get_site_url();
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');

        $positions = array();

        foreach ($keywords as $keyword) {
            $body = array(
                'startDate' => $start_date,
                'endDate' => $end_date,
                'dimensions' => array('query'),
                'dimensionFilterGroups' => array(
                    array(
                        'filters' => array(
                            array(
                                'dimension' => 'query',
                                'expression' => $keyword,
                            )
                        )
                    )
                ),
            );

            $endpoint = '/sites/' . urlencode($site_url) . '/searchAnalytics/query';
            $result = $this->api_request($endpoint, 'POST', $body);

            if (!is_wp_error($result) && isset($result['rows'][0])) {
                $positions[$keyword] = array(
                    'position' => $result['rows'][0]['position'] ?? 0,
                    'clicks' => $result['rows'][0]['clicks'] ?? 0,
                    'impressions' => $result['rows'][0]['impressions'] ?? 0,
                    'ctr' => $result['rows'][0]['ctr'] ?? 0,
                );
            }
        }

        return $positions;
    }

    /**
     * Získanie indexačných issues
     */
    public function get_indexing_issues() {
        $site_url = get_site_url();
        $cache_key = 'ai_seo_gsc_indexing_issues';

        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // URL Inspection API
        // V plnej verzii by tu bola integrácia s URL Inspection API

        $issues = array(
            'errors' => array(),
            'warnings' => array(),
            'valid' => 0,
        );

        set_transient($cache_key, $issues, 12 * HOUR_IN_SECONDS);

        return $issues;
    }

    /**
     * Získanie top opportunities (keywords s vysokými impressions ale nízkym CTR)
     */
    public function get_opportunities($post_id = null) {
        $data = $this->get_search_analytics($post_id, 30);

        if (is_wp_error($data) || !isset($data['rows'])) {
            return array();
        }

        $opportunities = array();

        foreach ($data['rows'] as $row) {
            $impressions = $row['impressions'] ?? 0;
            $ctr = $row['ctr'] ?? 0;
            $position = $row['position'] ?? 100;

            // High impressions but low CTR = opportunity
            if ($impressions > 100 && $ctr < 0.05 && $position <= 20) {
                $opportunities[] = array(
                    'keyword' => $row['keys'][0] ?? '',
                    'impressions' => $impressions,
                    'clicks' => $row['clicks'] ?? 0,
                    'ctr' => $ctr,
                    'position' => $position,
                    'opportunity_score' => $impressions * (1 - $ctr) * (20 - $position),
                );
            }
        }

        // Zoraď podľa opportunity score
        usort($opportunities, function($a, $b) {
            return $b['opportunity_score'] - $a['opportunity_score'];
        });

        return array_slice($opportunities, 0, 10);
    }

    /**
     * Submit URL pre indexáciu
     */
    public function submit_url_for_indexing($url) {
        // V plnej verzii: Google Indexing API
        // https://developers.google.com/search/apis/indexing-api/v3/quickstart

        return new WP_Error('not_implemented', 'URL indexing submission not yet implemented');
    }
}
