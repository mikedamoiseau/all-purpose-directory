<?php
/**
 * Tests for cron handler methods in Plugin class.
 *
 * Verifies cron_check_expired_listings and cron_cleanup_transients
 * behave correctly under various conditions.
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use APD\Core\Plugin;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests for cron handlers.
 */
class CronHandlersTest extends UnitTestCase {

	/**
	 * Plugin instance created via reflection (bypass singleton constructor).
	 *
	 * @var Plugin
	 */
	private Plugin $plugin;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();

		$reflection   = new \ReflectionClass( Plugin::class );
		$this->plugin = $reflection->newInstanceWithoutConstructor();

		Functions\stubs(
			[
				'get_transient'    => false,
				'set_transient'    => true,
				'delete_transient' => true,
				'add_post_meta'    => true,
				'delete_post_meta' => true,
				'current_time'     => '2026-02-06 12:00:00',
			]
		);
	}

	/*
	|--------------------------------------------------------------------------
	| cron_check_expired_listings tests
	|--------------------------------------------------------------------------
	*/

	/**
	 * Test expiration check returns early when expiration_days is 0 (disabled).
	 */
	public function test_expired_check_skips_when_expiration_disabled(): void {
		Functions\when( 'apd_get_setting' )->justReturn( 0 );

		// get_posts should never be called when expiration is disabled.
		Functions\expect( 'get_posts' )->never();
		Functions\expect( 'wp_update_post' )->never();

		$this->plugin->cron_check_expired_listings();
	}

	/**
	 * Test expiration check returns early when expiration_days is negative.
	 */
	public function test_expired_check_skips_when_expiration_negative(): void {
		Functions\when( 'apd_get_setting' )->justReturn( -1 );

		Functions\expect( 'get_posts' )->never();
		Functions\expect( 'wp_update_post' )->never();

		$this->plugin->cron_check_expired_listings();
	}

	/**
	 * Test expiration check returns early when another cron run holds the lock.
	 */
	public function test_expired_check_skips_when_lock_active(): void {
		Functions\when( 'apd_get_setting' )->justReturn( 30 );
		Functions\when( 'get_transient' )->justReturn( 1 );

		Functions\expect( 'set_transient' )->never();
		Functions\expect( 'delete_transient' )->never();
		Functions\expect( 'get_posts' )->never();
		Functions\expect( 'wp_update_post' )->never();

		$this->plugin->cron_check_expired_listings();
	}

	/**
	 * Test expiration check transitions expired listings.
	 */
	public function test_expired_check_transitions_expired_listings(): void {
		Functions\when( 'apd_get_setting' )->justReturn( 30 );
		Functions\when( 'current_time' )->justReturn( '2026-02-06 12:00:00' );

		// First call to get_posts returns expired listing IDs.
		Functions\expect( 'get_posts' )
			->once()
			->andReturn( [ 101, 102, 103 ] );

		// Expect wp_update_post called for each expired listing.
		Functions\expect( 'wp_update_post' )
			->times( 3 )
			->andReturn( 1 );

		$this->plugin->cron_check_expired_listings();
	}

	/**
	 * Test expiration check does nothing when no listings are expired.
	 */
	public function test_expired_check_no_action_when_none_expired(): void {
		Functions\when( 'apd_get_setting' )->justReturn( 30 );
		Functions\when( 'current_time' )->justReturn( '2026-02-06 12:00:00' );

		// No expired listings found.
		Functions\expect( 'get_posts' )
			->once()
			->andReturn( [] );

		Functions\expect( 'wp_update_post' )->never();

		$this->plugin->cron_check_expired_listings();
	}

	/**
	 * Test expired listings are updated with correct status.
	 */
	public function test_expired_check_sets_expired_status(): void {
		Functions\when( 'apd_get_setting' )->justReturn( 30 );
		Functions\when( 'current_time' )->justReturn( '2026-02-06 12:00:00' );

		Functions\expect( 'get_posts' )
			->once()
			->andReturn( [ 42 ] );

		$captured_args = null;
		Functions\expect( 'wp_update_post' )
			->once()
			->andReturnUsing( function ( $args ) use ( &$captured_args ) {
				$captured_args = $args;
				return 1;
			} );

		$this->plugin->cron_check_expired_listings();

		$this->assertSame( 42, $captured_args['ID'] );
		$this->assertSame( 'expired', $captured_args['post_status'] );
	}

	/**
	 * Test expiration check processes multiple batches until exhausted.
	 */
	public function test_expired_check_processes_multiple_batches(): void {
		Functions\when( 'apd_get_setting' )->justReturn( 30 );
		Functions\when( 'current_time' )->justReturn( '2026-02-06 12:00:00' );

		$first_batch  = range( 1, 50 );
		$second_batch = [ 51 ];

		Functions\expect( 'get_posts' )
			->twice()
			->andReturn( $first_batch, $second_batch );

		Functions\expect( 'wp_update_post' )
			->times( 51 )
			->andReturn( 1 );

		$this->plugin->cron_check_expired_listings();
	}

	/*
	|--------------------------------------------------------------------------
	| cron_cleanup_transients tests
	|--------------------------------------------------------------------------
	*/

	/**
	 * Test transient cleanup deletes expired transients.
	 */
	public function test_cleanup_deletes_expired_transients(): void {
		$mock_wpdb          = Mockery::mock( 'wpdb' );
		$mock_wpdb->options = 'wp_options';

		$mock_wpdb->shouldReceive( 'esc_like' )
			->once()
			->with( '_transient_timeout_apd_cache_' )
			->andReturn( '_transient_timeout_apd_cache_' );

		$mock_wpdb->shouldReceive( 'prepare' )
			->once()
			->andReturn( 'prepared_query' );

		$mock_wpdb->shouldReceive( 'get_col' )
			->once()
			->andReturn( [
				'_transient_timeout_apd_cache_categories',
				'_transient_timeout_apd_cache_related_5',
			] );

		$GLOBALS['wpdb'] = $mock_wpdb;

		$deleted_transients = [];
		Functions\when( 'delete_transient' )->alias( function( $name ) use ( &$deleted_transients ) {
			$deleted_transients[] = $name;
			return true;
		} );

		$this->plugin->cron_cleanup_transients();

		$this->assertCount( 2, $deleted_transients );

		unset( $GLOBALS['wpdb'] );
	}

	/**
	 * Test transient cleanup handles no expired transients gracefully.
	 */
	public function test_cleanup_handles_no_expired_transients(): void {
		$mock_wpdb          = Mockery::mock( 'wpdb' );
		$mock_wpdb->options = 'wp_options';

		$mock_wpdb->shouldReceive( 'esc_like' )
			->once()
			->andReturn( '_transient_timeout_apd_cache_' );

		$mock_wpdb->shouldReceive( 'prepare' )
			->once()
			->andReturn( 'prepared_query' );

		$mock_wpdb->shouldReceive( 'get_col' )
			->once()
			->andReturn( [] );

		$GLOBALS['wpdb'] = $mock_wpdb;

		$deleted_transients = [];
		Functions\when( 'delete_transient' )->alias( function( $name ) use ( &$deleted_transients ) {
			$deleted_transients[] = $name;
			return true;
		} );

		$this->plugin->cron_cleanup_transients();

		$this->assertCount( 0, $deleted_transients );

		unset( $GLOBALS['wpdb'] );
	}

	/**
	 * Test transient cleanup extracts correct transient name.
	 */
	public function test_cleanup_extracts_correct_transient_name(): void {
		$mock_wpdb          = Mockery::mock( 'wpdb' );
		$mock_wpdb->options = 'wp_options';

		$mock_wpdb->shouldReceive( 'esc_like' )->andReturn( '_transient_timeout_apd_cache_' );
		$mock_wpdb->shouldReceive( 'prepare' )->andReturn( 'prepared_query' );
		$mock_wpdb->shouldReceive( 'get_col' )
			->andReturn( [ '_transient_timeout_apd_cache_my_key' ] );

		$GLOBALS['wpdb'] = $mock_wpdb;

		$deleted_transients = [];
		Functions\when( 'delete_transient' )->alias( function( $name ) use ( &$deleted_transients ) {
			$deleted_transients[] = $name;
			return true;
		} );

		$this->plugin->cron_cleanup_transients();

		$this->assertSame( [ 'apd_cache_my_key' ], $deleted_transients );

		unset( $GLOBALS['wpdb'] );
	}
}
