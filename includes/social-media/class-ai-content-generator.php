<?php
/**
 * AI Content Generator for Social Media
 *
 * Generuje AI obsah pre rôzne social media platformy
 * Využíva existujúci AI Manager (Claude + OpenAI)
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_SEO_Social_AI_Content_Generator {

    private static $instance = null;
    private $ai_manager;
    private $db;
    private $logger;

    /**
     * Singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = AI_SEO_Social_Database::get_instance();

        // Get existing AI Manager
        if (class_exists('AI_SEO_Manager_AI_Manager')) {
            $this->ai_manager = AI_SEO_Manager_AI_Manager::get_instance();
        }

        // Initialize debug tools if available
        if (class_exists('AI_SEO_Manager_Debug_Logger')) {
            $this->logger = AI_SEO_Manager_Debug_Logger::get_instance();
        }
    }

    /**
     * Generuje AI obsah pre konkrétnu platformu
     *
     * @param string $topic Téma obsahu
     * @param string $platform Cieľová platforma (telegram, facebook, etc.)
     * @param array $options Dodatočné možnosti
     * @return array|WP_Error Vygenerovaný obsah alebo chyba
     */
    public function generate_content($topic, $platform, $options = array()) {
        if ($this->logger) {
            $this->logger->info("Generating AI content for {$platform}: {$topic}");
        }

        // Validate inputs
        if (empty($topic)) {
            return new WP_Error('missing_topic', 'Topic is required for content generation');
        }

        if (empty($platform)) {
            return new WP_Error('missing_platform', 'Platform is required for content generation');
        }

        // Default options
        $defaults = array(
            'tone' => 'professional',      // professional, casual, humorous, formal
            'category' => 'general',        // general, crypto, tech, fashion, business
            'length' => 'medium',           // short, medium, long
            'include_hashtags' => true,
            'include_emojis' => true,
            'include_cta' => true,
            'language' => 'sk',             // sk, en, cs
        );

        $options = wp_parse_args($options, $defaults);

        // Get platform constraints
        $constraints = $this->get_platform_constraints($platform);

        // Build AI prompt
        $prompt = $this->build_content_prompt($topic, $platform, $options, $constraints);

        // Generate content using AI
        if (!$this->ai_manager) {
            return new WP_Error('ai_manager_not_available', 'AI Manager is not available');
        }

        try {
            $response = $this->ai_manager->chat($prompt);

            if (is_wp_error($response)) {
                if ($this->logger) {
                    $this->logger->error('AI content generation failed', array(
                        'error' => $response->get_error_message(),
                    ));
                }
                return $response;
            }

            // Parse AI response
            $content = $this->parse_ai_response($response, $platform, $constraints);

            if ($this->logger) {
                $this->logger->info('AI content generated successfully', array(
                    'platform' => $platform,
                    'content_length' => strlen($content['text']),
                ));
            }

            return $content;

        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Exception during AI content generation', array(
                    'message' => $e->getMessage(),
                ));
            }
            return new WP_Error('generation_failed', $e->getMessage());
        }
    }

    /**
     * Generuje viacero variantov obsahu
     *
     * @param string $topic Téma
     * @param string $platform Platforma
     * @param int $count Počet variantov (1-5)
     * @param array $options Možnosti
     * @return array|WP_Error Array variantov alebo chyba
     */
    public function generate_variations($topic, $platform, $count = 3, $options = array()) {
        $count = min(max((int)$count, 1), 5); // Limit 1-5
        $variations = array();

        if ($this->logger) {
            $this->logger->info("Generating {$count} content variations for {$platform}");
        }

        for ($i = 0; $i < $count; $i++) {
            $variation_options = $options;
            $variation_options['variation_number'] = $i + 1;

            $content = $this->generate_content($topic, $platform, $variation_options);

            if (is_wp_error($content)) {
                if ($this->logger) {
                    $this->logger->warning("Variation {$i} failed", array(
                        'error' => $content->get_error_message(),
                    ));
                }
                continue;
            }

            $variations[] = $content;
        }

        if (empty($variations)) {
            return new WP_Error('no_variations', 'Failed to generate any content variations');
        }

        return $variations;
    }

    /**
     * Generuje obsah na základe trending topic
     *
     * @param string $category Kategória (crypto, tech, etc.)
     * @param string $platform Platforma
     * @param array $options Možnosti
     * @return array|WP_Error Vygenerovaný obsah alebo chyba
     */
    public function generate_from_trend($category, $platform, $options = array()) {
        if ($this->logger) {
            $this->logger->info("Generating content from trend: {$category}");
        }

        // Get trending topics from database
        global $wpdb;
        $table = $wpdb->prefix . 'ai_seo_social_trends';

        $trend = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE category = %s
             AND status = 'active'
             AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY trend_score DESC
             LIMIT 1",
            $category
        ));

        if (!$trend) {
            return new WP_Error('no_trends', "No active trends found for category: {$category}");
        }

        // Use trend as topic
        $topic = $trend->trend_topic;
        $options['category'] = $category;
        $options['trend_data'] = array(
            'keywords' => json_decode($trend->keywords, true),
            'score' => $trend->trend_score,
            'source' => $trend->source,
        );

        return $this->generate_content($topic, $platform, $options);
    }

    /**
     * Generuje obsah z WordPress postu
     *
     * @param int $post_id WordPress post ID
     * @param string $platform Cieľová platforma
     * @param array $options Možnosti
     * @return array|WP_Error Vygenerovaný obsah alebo chyba
     */
    public function generate_from_post($post_id, $platform, $options = array()) {
        $post = get_post($post_id);

        if (!$post) {
            return new WP_Error('post_not_found', 'WordPress post not found');
        }

        if ($this->logger) {
            $this->logger->info("Generating content from post: {$post->post_title}");
        }

        // Extract post information
        $post_title = $post->post_title;
        $post_excerpt = $post->post_excerpt ?: wp_trim_words($post->post_content, 30);
        $post_url = get_permalink($post_id);

        // Get featured image
        $featured_image = get_the_post_thumbnail_url($post_id, 'large');

        // Build topic from post
        $topic = "Blog post: \"{$post_title}\"";

        $options['post_data'] = array(
            'title' => $post_title,
            'excerpt' => $post_excerpt,
            'url' => $post_url,
            'image' => $featured_image,
        );

        $options['include_link'] = true;

        return $this->generate_content($topic, $platform, $options);
    }

    /**
     * Build AI prompt pre content generation
     *
     * @param string $topic Téma
     * @param string $platform Platforma
     * @param array $options Možnosti
     * @param array $constraints Platform constraints
     * @return string AI prompt
     */
    private function build_content_prompt($topic, $platform, $options, $constraints) {
        $platform_name = ucfirst($platform);
        $tone = $options['tone'];
        $category = $options['category'];
        $length = $options['length'];

        $prompt = "Vygeneruj profesionálny {$platform_name} príspevok na túto tému: \"{$topic}\"\n\n";

        $prompt .= "POŽIADAVKY:\n";
        $prompt .= "- Platforma: {$platform_name}\n";
        $prompt .= "- Tón: {$tone}\n";
        $prompt .= "- Kategória: {$category}\n";
        $prompt .= "- Dĺžka: {$length}\n";

        // Platform specific constraints
        if (isset($constraints['max_length'])) {
            $prompt .= "- Max dĺžka textu: {$constraints['max_length']} znakov\n";
        }

        if (isset($constraints['max_hashtags'])) {
            $prompt .= "- Max hashtags: {$constraints['max_hashtags']}\n";
        }

        if ($options['include_hashtags']) {
            $prompt .= "- Zahrň relevantné hashtags\n";
        }

        if ($options['include_emojis']) {
            $prompt .= "- Použi vhodné emoji\n";
        }

        if ($options['include_cta']) {
            $prompt .= "- Zahrň Call-to-Action\n";
        }

        // Post data if available
        if (isset($options['post_data'])) {
            $post_data = $options['post_data'];
            $prompt .= "\nZDROJOVÝ ČLÁNOK:\n";
            $prompt .= "- Názov: {$post_data['title']}\n";
            $prompt .= "- Obsah: {$post_data['excerpt']}\n";
            if ($options['include_link']) {
                $prompt .= "- URL: {$post_data['url']}\n";
            }
        }

        // Trend data if available
        if (isset($options['trend_data'])) {
            $trend = $options['trend_data'];
            $prompt .= "\nTRENDING INFO:\n";
            $prompt .= "- Trend score: {$trend['score']}\n";
            if (!empty($trend['keywords'])) {
                $prompt .= "- Kľúčové slová: " . implode(', ', $trend['keywords']) . "\n";
            }
        }

        $prompt .= "\nVÝSTUP:\n";
        $prompt .= "Vráť výsledok v tomto formáte:\n";
        $prompt .= "TEXT: [hlavný text príspevku]\n";
        if ($options['include_hashtags']) {
            $prompt .= "HASHTAGS: [zoznam hashtagov oddelených medzerou]\n";
        }
        $prompt .= "\nVytvor engaging a platform-optimalizovaný obsah.";

        return $prompt;
    }

    /**
     * Parse AI response do štruktúrovaného formátu
     *
     * @param string $response AI response
     * @param string $platform Platforma
     * @param array $constraints Platform constraints
     * @return array Parsed content
     */
    private function parse_ai_response($response, $platform, $constraints) {
        $content = array(
            'text' => '',
            'hashtags' => array(),
            'mentions' => array(),
            'media' => array(),
            'raw' => $response,
        );

        // Extract TEXT section
        if (preg_match('/TEXT:\s*(.+?)(?=HASHTAGS:|$)/s', $response, $matches)) {
            $content['text'] = trim($matches[1]);
        } else {
            // If no TEXT: marker, use entire response
            $content['text'] = $response;
        }

        // Extract HASHTAGS section
        if (preg_match('/HASHTAGS:\s*(.+?)$/s', $response, $matches)) {
            $hashtag_string = trim($matches[1]);
            $hashtags = preg_split('/[\s,]+/', $hashtag_string);

            // Clean and validate hashtags
            $hashtags = array_map(function($tag) {
                $tag = trim($tag);
                // Remove # if present
                $tag = ltrim($tag, '#');
                return $tag;
            }, $hashtags);

            $hashtags = array_filter($hashtags);

            // Apply platform limit
            if (isset($constraints['max_hashtags'])) {
                $hashtags = array_slice($hashtags, 0, $constraints['max_hashtags']);
            }

            $content['hashtags'] = array_values($hashtags);

            // Remove HASHTAGS section from text
            $content['text'] = trim(preg_replace('/HASHTAGS:.*$/s', '', $content['text']));
        }

        // Validate length
        if (isset($constraints['max_length'])) {
            if (strlen($content['text']) > $constraints['max_length']) {
                $content['text'] = substr($content['text'], 0, $constraints['max_length'] - 3) . '...';
            }
        }

        return $content;
    }

    /**
     * Get platform constraints
     *
     * @param string $platform Platform name
     * @return array Constraints
     */
    private function get_platform_constraints($platform) {
        $constraints = array(
            'telegram' => array(
                'max_length' => 4096,
                'max_hashtags' => 30,
                'supports_media' => true,
            ),
            'twitter' => array(
                'max_length' => 280,
                'max_hashtags' => 10,
                'supports_media' => true,
            ),
            'facebook' => array(
                'max_length' => 5000,
                'max_hashtags' => 30,
                'supports_media' => true,
            ),
            'instagram' => array(
                'max_length' => 2200,
                'max_hashtags' => 30,
                'supports_media' => true,
                'requires_media' => true,
            ),
            'linkedin' => array(
                'max_length' => 3000,
                'max_hashtags' => 10,
                'supports_media' => true,
            ),
            'youtube' => array(
                'max_length' => 5000,
                'max_hashtags' => 15,
                'supports_media' => true,
                'requires_media' => true,
            ),
            'tiktok' => array(
                'max_length' => 2200,
                'max_hashtags' => 30,
                'supports_media' => true,
                'requires_media' => true,
            ),
        );

        return isset($constraints[$platform]) ? $constraints[$platform] : array();
    }

    /**
     * Optimalizuje obsah pre konkrétnu platformu
     *
     * @param string $content Pôvodný obsah
     * @param string $platform Cieľová platforma
     * @return array Optimalizovaný obsah
     */
    public function optimize_for_platform($content, $platform) {
        $constraints = $this->get_platform_constraints($platform);
        $optimized = array(
            'text' => $content,
            'hashtags' => array(),
            'truncated' => false,
        );

        // Extract existing hashtags
        if (preg_match_all('/#(\w+)/', $content, $matches)) {
            $optimized['hashtags'] = $matches[1];

            // Apply hashtag limit
            if (isset($constraints['max_hashtags']) && count($optimized['hashtags']) > $constraints['max_hashtags']) {
                $optimized['hashtags'] = array_slice($optimized['hashtags'], 0, $constraints['max_hashtags']);
                $optimized['truncated'] = true;
            }
        }

        // Apply length limit
        if (isset($constraints['max_length']) && strlen($content) > $constraints['max_length']) {
            $optimized['text'] = substr($content, 0, $constraints['max_length'] - 3) . '...';
            $optimized['truncated'] = true;
        }

        return $optimized;
    }

    /**
     * Get statistics
     *
     * @return array Statistics
     */
    public function get_stats() {
        return array(
            'ai_manager_available' => $this->ai_manager !== null,
            'supported_platforms' => 7,
            'generation_methods' => array('topic', 'trend', 'post', 'variations'),
        );
    }
}
