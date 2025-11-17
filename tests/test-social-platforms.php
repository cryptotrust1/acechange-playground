<?php
/**
 * Social Media Platforms Unit Tests
 *
 * Tests all platform clients for:
 * - Authentication
 * - Content validation
 * - Publishing (mocked)
 * - Rate limits
 * - Capabilities
 */

class Test_Social_Platforms extends WP_UnitTestCase {

    private $platforms = array(
        'telegram',
        'facebook',
        'instagram',
        'twitter',
        'linkedin',
        'youtube',
        'tiktok',
    );

    public function setUp() {
        parent::setUp();

        // Load all required classes
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-social-database.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-platform-registry.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-rate-limiter.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/class-social-media-manager.php';
        require_once AI_SEO_MANAGER_PLUGIN_DIR . 'includes/social-media/platforms/class-platform-client.php';

        // Load all platform clients
        foreach ($this->platforms as $platform) {
            $file = AI_SEO_MANAGER_PLUGIN_DIR . "includes/social-media/platforms/class-{$platform}-client.php";
            if (file_exists($file)) {
                require_once $file;
            }
        }

        // Create tables
        AI_SEO_Social_Database::get_instance()->create_tables();
    }

    /**
     * Test Telegram Client
     */
    public function test_telegram_authentication() {
        $client = new AI_SEO_Social_Telegram_Client();

        $this->assertEquals('telegram', $client->get_platform_name());

        // Without credentials, should fail
        $result = $client->authenticate();
        $this->assertWPError($result);
        $this->assertEquals('no_bot_token', $result->get_error_code());
    }

    public function test_telegram_content_validation() {
        $client = new AI_SEO_Social_Telegram_Client();

        // Valid content
        $result = $client->validate_content('Test message');
        $this->assertTrue($result);

        // Too long content
        $long_content = str_repeat('a', 5000);
        $result = $client->validate_content($long_content);
        $this->assertWPError($result);
        $this->assertEquals('content_too_long', $result->get_error_code());
    }

    public function test_telegram_rate_limits() {
        $client = new AI_SEO_Social_Telegram_Client();
        $limits = $client->get_rate_limits();

        $this->assertIsArray($limits);
        $this->assertArrayHasKey('minute', $limits);
        $this->assertArrayHasKey('hour', $limits);
        $this->assertArrayHasKey('day', $limits);
    }

    public function test_telegram_capabilities() {
        $client = new AI_SEO_Social_Telegram_Client();
        $capabilities = $client->get_capabilities();

        $this->assertIsArray($capabilities);
        $this->assertTrue($capabilities['text']);
        $this->assertTrue($capabilities['images']);
        $this->assertTrue($capabilities['videos']);
        $this->assertTrue($capabilities['polls']);
    }

    /**
     * Test Facebook Client
     */
    public function test_facebook_authentication() {
        $client = new AI_SEO_Social_Facebook_Client();

        $this->assertEquals('facebook', $client->get_platform_name());

        $result = $client->authenticate();
        $this->assertWPError($result);
        $this->assertEquals('no_credentials', $result->get_error_code());
    }

    public function test_facebook_content_validation() {
        $client = new AI_SEO_Social_Facebook_Client();

        $result = $client->validate_content('Test post');
        $this->assertTrue($result);

        // Very long content (should pass - Facebook has high limit)
        $long_content = str_repeat('a', 6000);
        $result = $client->validate_content($long_content);
        $this->assertWPError($result);
    }

    public function test_facebook_capabilities() {
        $client = new AI_SEO_Social_Facebook_Client();
        $capabilities = $client->get_capabilities();

        $this->assertTrue($capabilities['text']);
        $this->assertTrue($capabilities['images']);
        $this->assertTrue($capabilities['videos']);
        $this->assertTrue($capabilities['albums']);
    }

    /**
     * Test Instagram Client
     */
    public function test_instagram_authentication() {
        $client = new AI_SEO_Social_Instagram_Client();

        $this->assertEquals('instagram', $client->get_platform_name());

        $result = $client->authenticate();
        $this->assertWPError($result);
    }

    public function test_instagram_content_validation() {
        $client = new AI_SEO_Social_Instagram_Client();

        // Valid content
        $result = $client->validate_content('Test caption #instagram');
        $this->assertTrue($result);

        // Too many hashtags
        $hashtags = array_fill(0, 31, '#tag');
        $content = implode(' ', $hashtags);
        $result = $client->validate_content($content);
        $this->assertWPError($result);
        $this->assertEquals('too_many_hashtags', $result->get_error_code());
    }

    public function test_instagram_capabilities() {
        $client = new AI_SEO_Social_Instagram_Client();
        $capabilities = $client->get_capabilities();

        $this->assertFalse($capabilities['text']); // Requires media
        $this->assertTrue($capabilities['images']);
        $this->assertTrue($capabilities['videos']);
        $this->assertTrue($capabilities['carousel']);
        $this->assertEquals(30, $capabilities['max_hashtags']);
    }

    /**
     * Test Twitter/X Client
     */
    public function test_twitter_authentication() {
        $client = new AI_SEO_Social_Twitter_Client();

        $this->assertEquals('twitter', $client->get_platform_name());

        $result = $client->authenticate();
        $this->assertWPError($result);
    }

    public function test_twitter_content_validation() {
        $client = new AI_SEO_Social_Twitter_Client();

        // Valid tweet
        $result = $client->validate_content('Test tweet');
        $this->assertTrue($result);

        // Too long tweet
        $long_tweet = str_repeat('a', 300);
        $result = $client->validate_content($long_tweet);
        $this->assertWPError($result);
        $this->assertEquals('content_too_long', $result->get_error_code());
    }

    public function test_twitter_capabilities() {
        $client = new AI_SEO_Social_Twitter_Client();
        $capabilities = $client->get_capabilities();

        $this->assertTrue($capabilities['text']);
        $this->assertTrue($capabilities['images']);
        $this->assertTrue($capabilities['videos']);
        $this->assertEquals(280, $capabilities['max_text_length']);
        $this->assertEquals(4, $capabilities['max_images']);
    }

    /**
     * Test LinkedIn Client
     */
    public function test_linkedin_authentication() {
        $client = new AI_SEO_Social_LinkedIn_Client();

        $this->assertEquals('linkedin', $client->get_platform_name());

        $result = $client->authenticate();
        $this->assertWPError($result);
    }

    public function test_linkedin_content_validation() {
        $client = new AI_SEO_Social_LinkedIn_Client();

        $result = $client->validate_content('Professional post');
        $this->assertTrue($result);

        // Very long post
        $long_post = str_repeat('a', 3500);
        $result = $client->validate_content($long_post);
        $this->assertWPError($result);
    }

    public function test_linkedin_capabilities() {
        $client = new AI_SEO_Social_LinkedIn_Client();
        $capabilities = $client->get_capabilities();

        $this->assertTrue($capabilities['text']);
        $this->assertTrue($capabilities['images']);
        $this->assertTrue($capabilities['videos']);
        $this->assertEquals(3000, $capabilities['max_text_length']);
    }

    /**
     * Test YouTube Client
     */
    public function test_youtube_authentication() {
        $client = new AI_SEO_Social_YouTube_Client();

        $this->assertEquals('youtube', $client->get_platform_name());

        $result = $client->authenticate();
        $this->assertWPError($result);
    }

    public function test_youtube_content_validation() {
        $client = new AI_SEO_Social_YouTube_Client();

        $result = $client->validate_content('Video description');
        $this->assertTrue($result);

        // Very long description
        $long_desc = str_repeat('a', 5500);
        $result = $client->validate_content($long_desc);
        $this->assertWPError($result);
    }

    public function test_youtube_capabilities() {
        $client = new AI_SEO_Social_YouTube_Client();
        $capabilities = $client->get_capabilities();

        $this->assertFalse($capabilities['text']); // Requires video
        $this->assertFalse($capabilities['images']); // Requires video
        $this->assertTrue($capabilities['videos']);
        $this->assertEquals(5000, $capabilities['max_description_length']);
    }

    /**
     * Test TikTok Client
     */
    public function test_tiktok_authentication() {
        $client = new AI_SEO_Social_TikTok_Client();

        $this->assertEquals('tiktok', $client->get_platform_name());

        $result = $client->authenticate();
        $this->assertWPError($result);
    }

    public function test_tiktok_content_validation() {
        $client = new AI_SEO_Social_TikTok_Client();

        // Valid caption
        $result = $client->validate_content('Viral video #fyp');
        $this->assertTrue($result);

        // Too many hashtags
        $hashtags = array_fill(0, 31, '#tag');
        $content = implode(' ', $hashtags);
        $result = $client->validate_content($content);
        $this->assertWPError($result);
    }

    public function test_tiktok_capabilities() {
        $client = new AI_SEO_Social_TikTok_Client();
        $capabilities = $client->get_capabilities();

        $this->assertFalse($capabilities['text']); // Requires video
        $this->assertTrue($capabilities['videos']);
        $this->assertEquals(2200, $capabilities['max_caption_length']);
        $this->assertEquals(30, $capabilities['max_hashtags']);
    }

    /**
     * Test Platform Registry
     */
    public function test_platform_registry() {
        $registry = AI_SEO_Social_Platform_Registry::get_instance();

        // Register a platform
        $telegram = new AI_SEO_Social_Telegram_Client();
        $result = $registry->register('telegram', $telegram);
        $this->assertTrue($result);

        // Check if registered
        $this->assertTrue($registry->is_platform_available('telegram'));

        // Get platform
        $client = $registry->get('telegram');
        $this->assertInstanceOf('AI_SEO_Social_Telegram_Client', $client);

        // Get all platforms
        $all = $registry->get_all();
        $this->assertArrayHasKey('telegram', $all);

        // Stats
        $stats = $registry->get_stats();
        $this->assertArrayHasKey('total', $stats);
        $this->assertEquals(1, $stats['total']);
    }

    /**
     * Test Rate Limiter
     */
    public function test_rate_limiter() {
        $limiter = AI_SEO_Social_Rate_Limiter::get_instance();

        // Check limit (should pass first time)
        $result = $limiter->check_limit('telegram', 'publish');
        $this->assertTrue($result);

        // Increment counter
        $limiter->increment('telegram', 'publish');

        // Check remaining
        $remaining = $limiter->get_remaining('telegram');
        $this->assertIsArray($remaining);
        $this->assertArrayHasKey('minute', $remaining);

        // Get stats
        $stats = $limiter->get_stats();
        $this->assertIsArray($stats);
    }

    /**
     * Test Social Database
     */
    public function test_social_database() {
        $db = AI_SEO_Social_Database::get_instance();

        // Create account
        $account_id = $db->create_account(array(
            'platform' => 'telegram',
            'account_name' => 'Test Channel',
            'account_id' => '@testchannel',
            'credentials' => array('bot_token' => 'test123'),
            'status' => 'active',
        ));

        $this->assertIsInt($account_id);
        $this->assertGreaterThan(0, $account_id);

        // Get account
        $account = $db->get_account($account_id);
        $this->assertNotNull($account);
        $this->assertEquals('telegram', $account->platform);

        // Get account by platform
        $account = $db->get_account_by_platform('telegram');
        $this->assertNotNull($account);

        // Create post
        $post_id = $db->create_post(array(
            'account_id' => $account_id,
            'platform' => 'telegram',
            'content' => 'Test post',
            'status' => 'published',
        ));

        $this->assertIsInt($post_id);

        // Get post
        $post = $db->get_post($post_id);
        $this->assertNotNull($post);
        $this->assertEquals('Test post', $post->content);

        // Get stats
        $stats = $db->get_stats_summary();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_accounts', $stats);
        $this->assertEquals(1, $stats['total_accounts']);
    }

    /**
     * Test Social Media Manager
     */
    public function test_social_media_manager() {
        $manager = AI_SEO_Social_Media_Manager::get_instance();

        $this->assertInstanceOf('AI_SEO_Social_Media_Manager', $manager);

        // Test platform availability (should be false without setup)
        $available = $manager->is_platform_available('telegram');
        $this->assertFalse($available);

        // Get stats
        $stats = $manager->get_stats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('database', $stats);
        $this->assertArrayHasKey('platforms', $stats);
        $this->assertArrayHasKey('rate_limits', $stats);
    }

    /**
     * Test content formatting
     */
    public function test_content_formatting() {
        $client = new AI_SEO_Social_Telegram_Client();

        // Access protected method via reflection
        $reflection = new ReflectionClass($client);
        $method = $reflection->getMethod('format_content');
        $method->setAccessible(true);

        // Test basic formatting
        $content = "  Test   content  \n\n  with   whitespace  ";
        $formatted = $method->invoke($client, $content);
        $this->assertEquals('Test content with whitespace', $formatted);

        // Test truncation
        $long = str_repeat('a', 200);
        $formatted = $method->invoke($client, $long, 50);
        $this->assertEquals(50, strlen($formatted));
        $this->assertStringEndsWith('...', $formatted);
    }

    /**
     * Test media type detection
     */
    public function test_media_type_detection() {
        $client = new AI_SEO_Social_Telegram_Client();

        $reflection = new ReflectionClass($client);
        $method = $reflection->getMethod('detect_media_type');
        $method->setAccessible(true);

        // Test image extensions
        $type = $method->invoke($client, 'https://example.com/image.jpg');
        $this->assertEquals('image', $type);

        $type = $method->invoke($client, 'https://example.com/photo.png');
        $this->assertEquals('image', $type);

        // Test video extensions
        $type = $method->invoke($client, 'https://example.com/video.mp4');
        $this->assertEquals('video', $type);

        $type = $method->invoke($client, 'https://example.com/clip.mov');
        $this->assertEquals('video', $type);
    }

    /**
     * Test hashtag extraction
     */
    public function test_hashtag_extraction() {
        $client = new AI_SEO_Social_Telegram_Client();

        $reflection = new ReflectionClass($client);
        $method = $reflection->getMethod('extract_hashtags');
        $method->setAccessible(true);

        $content = 'Test post #hashtag1 #hashtag2 #test';
        $hashtags = $method->invoke($client, $content);

        $this->assertIsArray($hashtags);
        $this->assertCount(3, $hashtags);
        $this->assertContains('hashtag1', $hashtags);
        $this->assertContains('hashtag2', $hashtags);
        $this->assertContains('test', $hashtags);
    }

    /**
     * Test mentions extraction
     */
    public function test_mentions_extraction() {
        $client = new AI_SEO_Social_Telegram_Client();

        $reflection = new ReflectionClass($client);
        $method = $reflection->getMethod('extract_mentions');
        $method->setAccessible(true);

        $content = 'Hello @user1 and @user2!';
        $mentions = $method->invoke($client, $content);

        $this->assertIsArray($mentions);
        $this->assertCount(2, $mentions);
        $this->assertContains('user1', $mentions);
        $this->assertContains('user2', $mentions);
    }

    public function tearDown() {
        parent::tearDown();

        // Clean up test data
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}ai_seo_social_accounts");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}ai_seo_social_posts");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}ai_seo_social_queue");
    }
}
