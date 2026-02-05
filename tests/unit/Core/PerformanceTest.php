<?php
/**
 * Tests for Performance class
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use APD\Core\Performance;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Performance test case
 */
class PerformanceTest extends TestCase {

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        \Brain\Monkey\setUp();

        // Define constants if not defined
        if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
            define( 'HOUR_IN_SECONDS', 3600 );
        }
        if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
            define( 'MINUTE_IN_SECONDS', 60 );
        }

        // Reset singleton for each test
        $this->reset_singleton();

        // Mock hook registration functions
        Functions\stubs( [
            'add_action' => null,
            'add_filter' => null,
        ] );
    }

    /**
     * Tear down test environment
     *
     * @return void
     */
    protected function tearDown(): void {
        \Brain\Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Reset the Performance singleton
     *
     * @return void
     */
    private function reset_singleton(): void {
        $reflection = new \ReflectionClass( Performance::class );
        $property = $reflection->getProperty( 'instance' );
        $property->setValue( null, null );
    }

    /**
     * Test singleton pattern
     *
     * @return void
     */
    public function test_singleton_returns_same_instance(): void {
        $instance1 = Performance::get_instance();
        $instance2 = Performance::get_instance();

        $this->assertSame( $instance1, $instance2 );
    }

    /**
     * Test cache key prefixing
     *
     * @return void
     */
    public function test_get_cache_key_adds_prefix(): void {
        $performance = Performance::get_instance();
        $key = $performance->get_cache_key( 'test_key' );

        $this->assertStringStartsWith( 'apd_cache_', $key );
        $this->assertEquals( 'apd_cache_test_key', $key );
    }

    /**
     * Test set stores in both caches
     *
     * @return void
     */
    public function test_set_stores_in_caches(): void {
        $test_value = [ 'foo' => 'bar' ];

        Functions\expect( 'wp_cache_set' )
            ->once()
            ->with( 'apd_cache_test_key', $test_value, 'apd', HOUR_IN_SECONDS )
            ->andReturn( true );

        Functions\expect( 'set_transient' )
            ->once()
            ->with( 'apd_cache_test_key', $test_value, HOUR_IN_SECONDS )
            ->andReturn( true );

        $performance = Performance::get_instance();
        $result = $performance->set( 'test_key', $test_value );

        $this->assertTrue( $result );
    }

    /**
     * Test get retrieves from object cache first
     *
     * @return void
     */
    public function test_get_retrieves_from_object_cache(): void {
        $test_value = [ 'cached' => 'data' ];

        Functions\expect( 'wp_cache_get' )
            ->once()
            ->with( 'apd_cache_test_key', 'apd' )
            ->andReturn( $test_value );

        $performance = Performance::get_instance();
        $result = $performance->get( 'test_key' );

        $this->assertEquals( $test_value, $result );
    }

    /**
     * Test get falls back to transient when object cache misses
     *
     * @return void
     */
    public function test_get_falls_back_to_transient(): void {
        $test_value = 'transient_value';

        Functions\expect( 'wp_cache_get' )
            ->once()
            ->andReturn( false );

        Functions\expect( 'get_transient' )
            ->once()
            ->with( 'apd_cache_test_key' )
            ->andReturn( $test_value );

        $performance = Performance::get_instance();
        $result = $performance->get( 'test_key' );

        $this->assertEquals( $test_value, $result );
    }

    /**
     * Test get returns false when nothing cached
     *
     * @return void
     */
    public function test_get_returns_false_when_not_cached(): void {
        Functions\expect( 'wp_cache_get' )
            ->once()
            ->andReturn( false );

        Functions\expect( 'get_transient' )
            ->once()
            ->andReturn( false );

        $performance = Performance::get_instance();
        $result = $performance->get( 'missing_key' );

        $this->assertFalse( $result );
    }

    /**
     * Test remember returns cached value without calling callback
     *
     * @return void
     */
    public function test_remember_returns_cached_value(): void {
        $cached_value = [ 'cached' => 'data' ];
        $callback_called = false;

        Functions\expect( 'wp_cache_get' )
            ->once()
            ->andReturn( $cached_value );

        $performance = Performance::get_instance();

        $result = $performance->remember(
            'test_key',
            function () use ( &$callback_called ) {
                $callback_called = true;
                return 'should not be called';
            }
        );

        $this->assertFalse( $callback_called );
        $this->assertEquals( $cached_value, $result );
    }

    /**
     * Test remember executes callback on cache miss
     *
     * @return void
     */
    public function test_remember_executes_callback_on_miss(): void {
        $expected = [ 'computed' => 'value' ];

        Functions\expect( 'wp_cache_get' )
            ->once()
            ->andReturn( false );

        Functions\expect( 'get_transient' )
            ->once()
            ->andReturn( false );

        Functions\expect( 'wp_cache_set' )
            ->once()
            ->andReturn( true );

        Functions\expect( 'set_transient' )
            ->once()
            ->andReturn( true );

        $performance = Performance::get_instance();

        $result = $performance->remember(
            'test_key',
            function () use ( $expected ) {
                return $expected;
            }
        );

        $this->assertEquals( $expected, $result );
    }

    /**
     * Test delete removes from both caches
     *
     * @return void
     */
    public function test_delete_removes_from_caches(): void {
        Functions\expect( 'wp_cache_delete' )
            ->once()
            ->with( 'apd_cache_test_key', 'apd' )
            ->andReturn( true );

        Functions\expect( 'delete_transient' )
            ->once()
            ->with( 'apd_cache_test_key' )
            ->andReturn( true );

        $performance = Performance::get_instance();
        $result = $performance->delete( 'test_key' );

        $this->assertTrue( $result );
    }

    /**
     * Test delete_pattern removes matching transients
     *
     * @return void
     */
    public function test_delete_pattern_removes_matching(): void {
        global $wpdb;
        $wpdb = \Mockery::mock( 'wpdb' );
        $wpdb->options = 'wp_options';

        $wpdb->shouldReceive( 'esc_like' )
            ->once()
            ->andReturnUsing( function ( $text ) {
                return $text;
            } );

        $wpdb->shouldReceive( 'prepare' )
            ->once()
            ->andReturn( "SELECT option_name FROM wp_options WHERE option_name LIKE '...'" );

        $wpdb->shouldReceive( 'get_col' )
            ->once()
            ->andReturn( [
                '_transient_apd_cache_categories_abc',
                '_transient_apd_cache_categories_def',
            ] );

        Functions\expect( 'delete_transient' )
            ->twice()
            ->andReturn( true );

        Functions\expect( 'wp_cache_delete' )
            ->twice()
            ->andReturn( true );

        $performance = Performance::get_instance();
        $deleted = $performance->delete_pattern( 'categories_' );

        $this->assertEquals( 2, $deleted );
    }

    /**
     * Test get_categories_with_counts returns cached data
     *
     * @return void
     */
    public function test_get_categories_with_counts_returns_cached(): void {
        $cached = [ (object) [ 'term_id' => 1, 'name' => 'Test' ] ];

        Functions\expect( 'wp_cache_get' )
            ->once()
            ->andReturn( $cached );

        $performance = Performance::get_instance();
        $result = $performance->get_categories_with_counts();

        $this->assertEquals( $cached, $result );
    }

    /**
     * Test get_related_listings returns cached data
     *
     * @return void
     */
    public function test_get_related_listings_returns_cached(): void {
        $cached = [ (object) [ 'ID' => 2, 'post_title' => 'Related' ] ];

        Functions\expect( 'wp_cache_get' )
            ->once()
            ->andReturn( $cached );

        $performance = Performance::get_instance();
        $result = $performance->get_related_listings( 1 );

        $this->assertEquals( $cached, $result );
    }

    /**
     * Test get_dashboard_stats returns cached data
     *
     * @return void
     */
    public function test_get_dashboard_stats_returns_cached(): void {
        $cached = [
            'listings'  => [ 'published' => 5, 'pending' => 2, 'total' => 7 ],
            'views'     => 100,
            'favorites' => 10,
        ];

        Functions\expect( 'wp_cache_get' )
            ->once()
            ->andReturn( $cached );

        $performance = Performance::get_instance();
        $result = $performance->get_dashboard_stats( 1 );

        $this->assertEquals( $cached, $result );
        $this->assertArrayHasKey( 'listings', $result );
        $this->assertArrayHasKey( 'views', $result );
        $this->assertArrayHasKey( 'favorites', $result );
    }

    /**
     * Test get_popular_listings returns cached data
     *
     * @return void
     */
    public function test_get_popular_listings_returns_cached(): void {
        $cached = [
            (object) [ 'ID' => 1, 'post_title' => 'Popular 1' ],
            (object) [ 'ID' => 2, 'post_title' => 'Popular 2' ],
        ];

        Functions\expect( 'wp_cache_get' )
            ->once()
            ->andReturn( $cached );

        $performance = Performance::get_instance();
        $result = $performance->get_popular_listings( 5 );

        $this->assertEquals( $cached, $result );
        $this->assertCount( 2, $result );
    }

    /**
     * Test clear_all clears all caches
     *
     * @return void
     */
    public function test_clear_all_clears_caches(): void {
        global $wpdb;
        $wpdb = \Mockery::mock( 'wpdb' );
        $wpdb->options = 'wp_options';

        $wpdb->shouldReceive( 'esc_like' )
            ->once()
            ->andReturnUsing( function ( $text ) {
                return $text;
            } );

        $wpdb->shouldReceive( 'prepare' )
            ->once()
            ->andReturn( 'SELECT ...' );

        $wpdb->shouldReceive( 'get_col' )
            ->once()
            ->andReturn( [
                '_transient_apd_cache_item1',
                '_transient_apd_cache_item2',
            ] );

        Functions\expect( 'delete_transient' )
            ->twice()
            ->andReturn( true );

        Functions\expect( 'wp_cache_delete' )
            ->twice()
            ->andReturn( true );

        Functions\expect( 'do_action' )
            ->once()
            ->with( 'apd_cache_cleared', 2 );

        $performance = Performance::get_instance();
        $deleted = $performance->clear_all();

        $this->assertEquals( 2, $deleted );
    }

    /**
     * Test invalidate_category_cache triggers action
     *
     * @return void
     */
    public function test_invalidate_category_cache_triggers_action(): void {
        global $wpdb;
        $wpdb = \Mockery::mock( 'wpdb' );
        $wpdb->options = 'wp_options';

        $wpdb->shouldReceive( 'esc_like' )
            ->once()
            ->andReturn( '_transient_apd_cache_categories_' );

        $wpdb->shouldReceive( 'prepare' )
            ->once()
            ->andReturn( 'SELECT ...' );

        $wpdb->shouldReceive( 'get_col' )
            ->once()
            ->andReturn( [] );

        Functions\expect( 'do_action' )
            ->once()
            ->with( 'apd_category_cache_invalidated' );

        $performance = Performance::get_instance();
        $performance->invalidate_category_cache();

        $this->assertTrue( true );
    }

    /**
     * Test set with custom expiration
     *
     * @return void
     */
    public function test_set_with_custom_expiration(): void {
        $custom_expiration = 7200;

        Functions\expect( 'wp_cache_set' )
            ->once()
            ->with( 'apd_cache_test_key', 'value', 'apd', $custom_expiration )
            ->andReturn( true );

        Functions\expect( 'set_transient' )
            ->once()
            ->with( 'apd_cache_test_key', 'value', $custom_expiration )
            ->andReturn( true );

        $performance = Performance::get_instance();
        $result = $performance->set( 'test_key', 'value', $custom_expiration );

        $this->assertTrue( $result );
    }
}

/**
 * Test helper functions
 */
class PerformanceHelperFunctionsTest extends TestCase {

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        \Brain\Monkey\setUp();

        // Define constants if not defined
        if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
            define( 'HOUR_IN_SECONDS', 3600 );
        }
        if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
            define( 'MINUTE_IN_SECONDS', 60 );
        }

        // Reset singleton for each test
        $this->reset_singleton();

        // Mock hook registration functions
        Functions\stubs( [
            'add_action' => null,
            'add_filter' => null,
        ] );
    }

    /**
     * Tear down test environment
     *
     * @return void
     */
    protected function tearDown(): void {
        \Brain\Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Reset the Performance singleton
     *
     * @return void
     */
    private function reset_singleton(): void {
        $reflection = new \ReflectionClass( Performance::class );
        $property = $reflection->getProperty( 'instance' );
        $property->setValue( null, null );
    }

    /**
     * Test apd_performance() returns instance
     *
     * @return void
     */
    public function test_apd_performance_returns_instance(): void {
        $instance = apd_performance();

        $this->assertInstanceOf( Performance::class, $instance );
    }

    /**
     * Test apd_cache_remember
     *
     * @return void
     */
    public function test_apd_cache_remember(): void {
        $expected = 'test_value';

        Functions\expect( 'wp_cache_get' )
            ->once()
            ->andReturn( $expected );

        $result = apd_cache_remember(
            'test_key',
            function () {
                return 'should not be called';
            }
        );

        $this->assertEquals( $expected, $result );
    }

    /**
     * Test apd_cache_get
     *
     * @return void
     */
    public function test_apd_cache_get(): void {
        $expected = 'cached_value';

        Functions\expect( 'wp_cache_get' )
            ->once()
            ->andReturn( $expected );

        $result = apd_cache_get( 'test_key' );

        $this->assertEquals( $expected, $result );
    }

    /**
     * Test apd_cache_set
     *
     * @return void
     */
    public function test_apd_cache_set(): void {
        Functions\expect( 'wp_cache_set' )
            ->once()
            ->andReturn( true );

        Functions\expect( 'set_transient' )
            ->once()
            ->andReturn( true );

        $result = apd_cache_set( 'test_key', 'test_value' );

        $this->assertTrue( $result );
    }

    /**
     * Test apd_cache_delete
     *
     * @return void
     */
    public function test_apd_cache_delete(): void {
        Functions\expect( 'wp_cache_delete' )
            ->once()
            ->andReturn( true );

        Functions\expect( 'delete_transient' )
            ->once()
            ->andReturn( true );

        $result = apd_cache_delete( 'test_key' );

        $this->assertTrue( $result );
    }

    /**
     * Test apd_cache_clear_all
     *
     * @return void
     */
    public function test_apd_cache_clear_all(): void {
        global $wpdb;
        $wpdb = \Mockery::mock( 'wpdb' );
        $wpdb->options = 'wp_options';

        $wpdb->shouldReceive( 'esc_like' )
            ->once()
            ->andReturn( '_transient_apd_cache_' );

        $wpdb->shouldReceive( 'prepare' )
            ->once()
            ->andReturn( 'SELECT ...' );

        $wpdb->shouldReceive( 'get_col' )
            ->once()
            ->andReturn( [] );

        Functions\expect( 'do_action' )
            ->once();

        $result = apd_cache_clear_all();

        $this->assertEquals( 0, $result );
    }

    /**
     * Test apd_get_cached_categories
     *
     * @return void
     */
    public function test_apd_get_cached_categories(): void {
        $cached = [ (object) [ 'term_id' => 1 ] ];

        Functions\expect( 'wp_cache_get' )
            ->once()
            ->andReturn( $cached );

        $result = apd_get_cached_categories();

        $this->assertEquals( $cached, $result );
    }

    /**
     * Test apd_get_cached_related_listings
     *
     * @return void
     */
    public function test_apd_get_cached_related_listings(): void {
        $cached = [ (object) [ 'ID' => 2 ] ];

        Functions\expect( 'wp_cache_get' )
            ->once()
            ->andReturn( $cached );

        $result = apd_get_cached_related_listings( 1 );

        $this->assertEquals( $cached, $result );
    }

    /**
     * Test apd_get_cached_dashboard_stats
     *
     * @return void
     */
    public function test_apd_get_cached_dashboard_stats(): void {
        $cached = [ 'listings' => [], 'views' => 100, 'favorites' => 5 ];

        Functions\expect( 'wp_cache_get' )
            ->once()
            ->andReturn( $cached );

        $result = apd_get_cached_dashboard_stats( 1 );

        $this->assertEquals( $cached, $result );
    }

    /**
     * Test apd_get_popular_listings
     *
     * @return void
     */
    public function test_apd_get_popular_listings(): void {
        $cached = [ (object) [ 'ID' => 1 ] ];

        Functions\expect( 'wp_cache_get' )
            ->once()
            ->andReturn( $cached );

        $result = apd_get_popular_listings();

        $this->assertEquals( $cached, $result );
    }

    /**
     * Test apd_invalidate_category_cache
     *
     * @return void
     */
    public function test_apd_invalidate_category_cache(): void {
        global $wpdb;
        $wpdb = \Mockery::mock( 'wpdb' );
        $wpdb->options = 'wp_options';

        $wpdb->shouldReceive( 'esc_like' )
            ->once()
            ->andReturn( '_transient_apd_cache_categories_' );

        $wpdb->shouldReceive( 'prepare' )
            ->once()
            ->andReturn( 'SELECT ...' );

        $wpdb->shouldReceive( 'get_col' )
            ->once()
            ->andReturn( [] );

        Functions\expect( 'do_action' )
            ->once();

        apd_invalidate_category_cache();

        $this->assertTrue( true );
    }
}
