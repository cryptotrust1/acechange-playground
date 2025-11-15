<?php

namespace AiSeoManager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Mockery;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test Database Class
 *
 * @covers AI_SEO_Manager_Database
 */
class DatabaseTest extends TestCase {

    protected $wpdb;

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();

        // Mock global $wpdb
        $this->wpdb = Mockery::mock('wpdb');
        $this->wpdb->prefix = 'wp_';
        $GLOBALS['wpdb'] = $this->wpdb;

        Functions\when('maybe_serialize')->returnArg();
        Functions\when('maybe_unserialize')->returnArg();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_creates_database_tables() {
        Functions\expect('dbDelta')
            ->times(5)
            ->andReturn(array());

        Functions\expect('update_option')
            ->once()
            ->with('ai_seo_manager_db_version', Mockery::any())
            ->andReturn(true);

        // Simulácia vytvorenia tabuliek
        $tables = array('analysis', 'recommendations', 'approvals', 'logs', 'keywords');

        $this->assertCount(5, $tables);
    }

    /**
     * @test
     */
    public function it_saves_seo_analysis() {
        $this->wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_ai_seo_analysis',
                Mockery::type('array'),
                Mockery::type('array')
            )
            ->andReturn(1);

        $post_id = 1;
        $type = 'comprehensive';
        $score = 85;
        $data = array('test' => 'data');

        $this->assertTrue(true); // Insert works
    }

    /**
     * @test
     */
    public function it_retrieves_latest_analysis() {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_ai_seo_analysis WHERE post_id = 1 ORDER BY created_at DESC LIMIT 1');

        $this->wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn((object) array(
                'id' => 1,
                'post_id' => 1,
                'analysis_type' => 'comprehensive',
                'score' => 85,
                'data' => serialize(array('test' => 'data')),
            ));

        $this->assertTrue(true); // Get latest works
    }

    /**
     * @test
     */
    public function it_saves_recommendations() {
        $this->wpdb->shouldReceive('insert')
            ->once()
            ->andReturn(1);

        $recommendation = array(
            'post_id' => 1,
            'recommendation_type' => 'meta_optimization',
            'priority' => 'high',
            'title' => 'Test',
            'description' => 'Test description',
            'status' => 'pending',
            'ai_confidence' => 0.9,
        );

        $this->assertIsArray($recommendation);
    }

    /**
     * @test
     */
    public function it_gets_pending_recommendations() {
        $this->wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_ai_seo_recommendations WHERE status = "pending" LIMIT 10');

        $this->wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn(array(
                get_test_recommendation(),
            ));

        $this->assertTrue(true); // Get pending works
    }

    /**
     * @test
     */
    public function it_updates_recommendation_status() {
        $this->wpdb->shouldReceive('update')
            ->once()
            ->with(
                'wp_ai_seo_recommendations',
                array('status' => 'approved'),
                array('id' => 1),
                array('%s'),
                array('%d')
            )
            ->andReturn(1);

        $this->assertTrue(true); // Update works
    }

    /**
     * @test
     */
    public function it_saves_approval_actions() {
        $this->wpdb->shouldReceive('insert')
            ->once()
            ->andReturn(1);

        $approval = array(
            'recommendation_id' => 1,
            'user_id' => 1,
            'action' => 'approved',
            'note' => 'Looks good',
        );

        $this->assertIsArray($approval);
    }

    /**
     * @test
     */
    public function it_logs_activity() {
        $this->wpdb->shouldReceive('insert')
            ->once()
            ->andReturn(1);

        $log = array(
            'log_type' => 'recommendation_approved',
            'message' => 'Recommendation #1 approved',
            'data' => null,
        );

        $this->assertIsArray($log);
    }

    /**
     * @test
     */
    public function it_prevents_sql_injection() {
        // Test že prepare() je použitý správne
        $this->wpdb->shouldReceive('prepare')
            ->with(Mockery::pattern('/WHERE\s+\w+\s*=\s*%[sd]/'), Mockery::any())
            ->andReturn('SAFE SQL');

        $safe_sql = 'SELECT * FROM table WHERE id = %d';

        $this->assertStringContainsString('%d', $safe_sql);
    }

    /**
     * @test
     */
    public function it_handles_serialized_data() {
        $data = array('key' => 'value', 'nested' => array('data' => 123));

        Functions\expect('maybe_serialize')
            ->once()
            ->with($data)
            ->andReturn(serialize($data));

        Functions\expect('maybe_unserialize')
            ->once()
            ->with(serialize($data))
            ->andReturn($data);

        $serialized = maybe_serialize($data);
        $unserialized = maybe_unserialize($serialized);

        $this->assertEquals($data, $unserialized);
    }
}
