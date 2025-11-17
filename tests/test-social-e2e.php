<?php
/**
 * Social Media E2E Tests
 *
 * End-to-end tests for complete publishing workflows:
 * - Account setup
 * - Content publishing
 * - Scheduling
 * - Multi-platform posting
 * - Error handling
 * - Auto-sharing
 */

class Test_Social_E2E extends WP_UnitTestCase {

    private $manager;
    private $db;
    private $test_account_ids = array();

    public function setUp() {
        parent::setUp();

        // Load all required classes
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-social-database.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-platform-registry.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-rate-limiter.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-social-media-manager.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-platform-client.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-telegram-client.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-facebook-client.php';

        // Create tables
        $this->db = AI_SEO_Social_Database::get_instance();
        $this->db->create_tables();

        // Initialize manager
        $this->manager = AI_SEO_Social_Media_Manager::get_instance();

        // Create test accounts (with invalid credentials for mocking)
        $this->setup_test_accounts();
    }

    /**
     * Setup test accounts for all platforms
     */
    private function setup_test_accounts() {
        $platforms = array(
            'telegram' => array(
                'bot_token' => 'test_bot_token_123',
                'channel_id' => '@testchannel',
            ),
            'facebook' => array(
                'app_id' => 'test_app_id',
                'app_secret' => 'test_app_secret',
                'page_id' => '123456789',
                'page_access_token' => 'test_access_token',
            ),
        );

        foreach ($platforms as $platform => $credentials) {
            $account_id = $this->db->create_account(array(
                'platform' => $platform,
                'account_name' => ucfirst($platform) . ' Test Account',
                'account_id' => $credentials['channel_id'] ?? $credentials['page_id'] ?? 'test',
                'credentials' => $credentials,
                'status' => 'active',
            ));

            $this->test_account_ids[$platform] = $account_id;
        }
    }

    /**
     * Test: Complete publishing workflow
     */
    public function test_complete_publishing_workflow() {
        $content = 'Test post for E2E workflow';
        $platforms = array('telegram');

        // Mock the actual API call
        add_filter('pre_http_request', array($this, 'mock_telegram_publish'), 10, 3);

        // Publish
        $results = $this->manager->publish_now($content, $platforms);

        // Verify results
        $this->assertIsArray($results);
        $this->assertArrayHasKey('telegram', $results);

        // Clean up
        remove_filter('pre_http_request', array($this, 'mock_telegram_publish'));
    }

    /**
     * Test: Scheduling workflow
     */
    public function test_scheduling_workflow() {
        $content = 'Scheduled post test';
        $schedule_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $platforms = array('telegram');

        // Schedule post
        $results = $this->manager->schedule_post($content, $schedule_time, $platforms);

        // Verify scheduled
        $this->assertIsArray($results);
        $this->assertArrayHasKey('telegram', $results);
        $this->assertGreaterThan(0, $results['telegram']);

        // Check database
        $post = $this->db->get_post($results['telegram']);
        $this->assertEquals('scheduled', $post->status);
        $this->assertEquals($schedule_time, $post->scheduled_at);

        // Process queue (should not publish yet)
        $this->manager->process_scheduled_posts();

        // Verify still scheduled
        $post = $this->db->get_post($results['telegram']);
        $this->assertEquals('scheduled', $post->status);
    }

    /**
     * Test: Multi-platform publishing
     */
    public function test_multi_platform_publishing() {
        $content = 'Multi-platform test post';
        $platforms = array('telegram', 'facebook');

        // Mock API calls
        add_filter('pre_http_request', array($this, 'mock_multi_platform_publish'), 10, 3);

        // Publish to multiple platforms
        $results = $this->manager->publish_now($content, $platforms);

        // Verify all platforms got results
        $this->assertArrayHasKey('telegram', $results);
        $this->assertArrayHasKey('facebook', $results);

        remove_filter('pre_http_request', array($this, 'mock_multi_platform_publish'));
    }

    /**
     * Test: Publishing with media
     */
    public function test_publishing_with_media() {
        $content = 'Post with image';
        $platforms = array('telegram');
        $options = array(
            'media' => array('https://example.com/image.jpg'),
        );

        add_filter('pre_http_request', array($this, 'mock_telegram_photo'), 10, 3);

        $results = $this->manager->publish_now($content, $platforms, $options);

        $this->assertArrayHasKey('telegram', $results);

        remove_filter('pre_http_request', array($this, 'mock_telegram_photo'));
    }

    /**
     * Test: Error handling
     */
    public function test_error_handling() {
        $content = 'Error test';
        $platforms = array('telegram');

        // Mock failed API call
        add_filter('pre_http_request', array($this, 'mock_failed_publish'), 10, 3);

        $results = $this->manager->publish_now($content, $platforms);

        // Should return WP_Error
        $this->assertArrayHasKey('telegram', $results);
        $this->assertWPError($results['telegram']);

        remove_filter('pre_http_request', array($this, 'mock_failed_publish'));
    }

    /**
     * Test: Rate limiting
     */
    public function test_rate_limiting() {
        $limiter = AI_SEO_Social_Rate_Limiter::get_instance();

        // Manually set rate limit to 1 for testing
        $limiter->set_platform_limits('telegram', array(
            'minute' => 1,
            'hour' => 10,
            'day' => 100,
        ));

        // First publish should work
        $limiter->increment('telegram', 'publish');

        // Second should hit limit
        $can_publish = $limiter->check_limit('telegram', 'publish');
        $this->assertFalse($can_publish);

        // Wait time should be positive
        $wait = $limiter->should_wait('telegram', 'publish');
        $this->assertGreaterThan(0, $wait);
    }

    /**
     * Test: Auto-sharing blog posts
     */
    public function test_auto_share_blog_post() {
        // Enable auto-sharing
        update_option('ai_seo_social_auto_share_enabled', true);
        update_option('ai_seo_social_auto_share_platforms', array('telegram'));

        // Create a blog post
        $post_id = $this->factory->post->create(array(
            'post_title' => 'Test Blog Post',
            'post_content' => 'This is a test blog post for auto-sharing.',
            'post_status' => 'draft',
        ));

        // Mock API call
        add_filter('pre_http_request', array($this, 'mock_telegram_publish'), 10, 3);

        // Publish the post (triggers auto-share)
        wp_publish_post($post_id);

        // Verify social post was created
        $posts = $this->db->get_posts(array(
            'platform' => 'telegram',
            'limit' => 1,
        ));

        $this->assertNotEmpty($posts);

        remove_filter('pre_http_request', array($this, 'mock_telegram_publish'));

        // Clean up
        update_option('ai_seo_social_auto_share_enabled', false);
    }

    /**
     * Test: Retry logic for failed posts
     */
    public function test_retry_logic() {
        $content = 'Retry test';

        // Create a scheduled post
        $account_id = $this->test_account_ids['telegram'];
        $post_id = $this->db->create_post(array(
            'account_id' => $account_id,
            'platform' => 'telegram',
            'content' => $content,
            'status' => 'scheduled',
            'scheduled_at' => date('Y-m-d H:i:s', strtotime('-1 hour')), // Past time
            'retry_count' => 0,
            'max_retries' => 3,
        ));

        // Mock failed publish
        add_filter('pre_http_request', array($this, 'mock_failed_publish'), 10, 3);

        // Process queue (should fail and increment retry)
        $this->manager->process_scheduled_posts();

        // Check retry count
        $post = $this->db->get_post($post_id);
        $this->assertEquals(1, $post->retry_count);
        $this->assertEquals('scheduled', $post->status); // Still scheduled for retry

        remove_filter('pre_http_request', array($this, 'mock_failed_publish'));
    }

    /**
     * Test: Max retries reached
     */
    public function test_max_retries_reached() {
        $content = 'Max retries test';
        $account_id = $this->test_account_ids['telegram'];

        // Create post with max retries almost reached
        $post_id = $this->db->create_post(array(
            'account_id' => $account_id,
            'platform' => 'telegram',
            'content' => $content,
            'status' => 'scheduled',
            'scheduled_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'retry_count' => 2,
            'max_retries' => 3,
        ));

        add_filter('pre_http_request', array($this, 'mock_failed_publish'), 10, 3);

        // Process queue
        $this->manager->process_scheduled_posts();

        // Should be marked as failed
        $post = $this->db->get_post($post_id);
        $this->assertEquals('failed', $post->status);
        $this->assertEquals(3, $post->retry_count);

        remove_filter('pre_http_request', array($this, 'mock_failed_publish'));
    }

    /**
     * Test: Content validation before publish
     */
    public function test_content_validation_before_publish() {
        // Too long content for Twitter
        $long_content = str_repeat('a', 300);
        $platforms = array('twitter');

        // Create Twitter account
        $this->db->create_account(array(
            'platform' => 'twitter',
            'account_name' => 'Twitter Test',
            'account_id' => 'test_user',
            'credentials' => array('api_key' => 'test', 'api_secret' => 'test', 'access_token' => 'test'),
            'status' => 'active',
        ));

        // Try to publish (should fail validation)
        $results = $this->manager->publish_now($long_content, $platforms);

        $this->assertArrayHasKey('twitter', $results);
        $this->assertWPError($results['twitter']);
        $this->assertEquals('content_too_long', $results['twitter']->get_error_code());
    }

    /**
     * Test: Get statistics
     */
    public function test_get_statistics() {
        // Create some posts
        $account_id = $this->test_account_ids['telegram'];

        for ($i = 0; $i < 5; $i++) {
            $this->db->create_post(array(
                'account_id' => $account_id,
                'platform' => 'telegram',
                'content' => "Test post {$i}",
                'status' => 'published',
            ));
        }

        // Get stats
        $stats = $this->manager->get_stats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('database', $stats);

        $db_stats = $stats['database'];
        $this->assertGreaterThanOrEqual(5, $db_stats['total_posts']);
        $this->assertGreaterThanOrEqual(5, $db_stats['published_posts']);
    }

    // ==================================================================
    // MOCK FUNCTIONS
    // ==================================================================

    /**
     * Mock successful Telegram publish
     */
    public function mock_telegram_publish($response, $args, $url) {
        if (strpos($url, 'api.telegram.org') !== false) {
            if (strpos($url, '/getMe') !== false) {
                return array(
                    'response' => array('code' => 200),
                    'body' => json_encode(array(
                        'ok' => true,
                        'result' => array('id' => 123456, 'username' => 'testbot'),
                    )),
                );
            }

            if (strpos($url, '/sendMessage') !== false || strpos($url, '/sendPhoto') !== false) {
                return array(
                    'response' => array('code' => 200),
                    'body' => json_encode(array(
                        'ok' => true,
                        'result' => array('message_id' => 12345),
                    )),
                );
            }
        }

        return $response;
    }

    /**
     * Mock Telegram photo
     */
    public function mock_telegram_photo($response, $args, $url) {
        if (strpos($url, 'sendPhoto') !== false) {
            return array(
                'response' => array('code' => 200),
                'body' => json_encode(array(
                    'ok' => true,
                    'result' => array('message_id' => 12346),
                )),
            );
        }

        return $this->mock_telegram_publish($response, $args, $url);
    }

    /**
     * Mock failed publish
     */
    public function mock_failed_publish($response, $args, $url) {
        if (strpos($url, 'api.telegram.org') !== false) {
            if (strpos($url, '/getMe') !== false) {
                return array(
                    'response' => array('code' => 401),
                    'body' => json_encode(array(
                        'ok' => false,
                        'description' => 'Unauthorized',
                    )),
                );
            }

            return array(
                'response' => array('code' => 500),
                'body' => json_encode(array(
                    'ok' => false,
                    'description' => 'Internal Server Error',
                )),
            );
        }

        return $response;
    }

    /**
     * Mock multi-platform publish
     */
    public function mock_multi_platform_publish($response, $args, $url) {
        // Telegram
        if (strpos($url, 'api.telegram.org') !== false) {
            return $this->mock_telegram_publish($response, $args, $url);
        }

        // Facebook
        if (strpos($url, 'graph.facebook.com') !== false) {
            if (strpos($url, '/me') !== false) {
                return array(
                    'response' => array('code' => 200),
                    'body' => json_encode(array('id' => '123456')),
                );
            }

            if (strpos($url, '/feed') !== false || strpos($url, '/photos') !== false) {
                return array(
                    'response' => array('code' => 200),
                    'body' => json_encode(array('id' => '123456_789')),
                );
            }
        }

        return $response;
    }

    public function tearDown() {
        parent::tearDown();

        // Clean up
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}ai_seo_social_accounts");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}ai_seo_social_posts");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}ai_seo_social_queue");

        // Clear options
        delete_option('ai_seo_social_auto_share_enabled');
        delete_option('ai_seo_social_auto_share_platforms');
    }
}
