<?php
/**
 * Cron expiration handler tests.
 *
 * Tests that cron_check_expired_listings() correctly transitions expired
 * listings based on expiration_days settings.
 *
 * Note: The expiring-soon warning email path requires the global function
 * apd_email_manager() which is defined in includes/functions.php and is
 * not loaded in unit tests. That path is skipped via function_exists() check.
 * The expired listing transition path is fully testable.
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use APD\Core\Plugin;
use APD\Admin\Settings;
use APD\Email\EmailManager;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Test class for cron expiration handler.
 */
final class CronExpirationTest extends TestCase {

	use MockeryPHPUnitIntegration;

	/**
	 * Plugin instance (created without constructor).
	 *
	 * @var Plugin
	 */
	private Plugin $plugin;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Settings::reset_instance();
		EmailManager::reset_instance();

		// Create Plugin instance without running constructor.
		$reflection   = new \ReflectionClass( Plugin::class );
		$this->plugin = $reflection->newInstanceWithoutConstructor();

		// Default stubs.
		Functions\stubs( [
			'esc_html'       => static fn( $text ) => $text,
			'esc_attr'       => static fn( $text ) => $text,
			'esc_url'        => static fn( $url ) => $url,
			'esc_html__'     => static fn( $text, $domain = 'default' ) => $text,
			'__'             => static fn( $text, $domain = 'default' ) => $text,
			'sanitize_key'   => static fn( $key ) => preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ),
			'wp_parse_args'  => static function ( $args, $defaults = [] ) {
				if ( is_object( $args ) ) {
					$args = get_object_vars( $args );
				}
				return array_merge( $defaults, $args );
			},
			'add_action'     => null,
			'add_filter'     => null,
			'do_action'      => null,
			'apply_filters'  => static fn( $tag, $value ) => $value,
			'has_action'     => false,
			'has_filter'     => false,
			'get_option'     => false,
			'update_option'  => true,
			'update_post_meta' => true,
			'get_transient'  => false,
			'set_transient'  => true,
			'delete_transient' => true,
			'add_post_meta'  => true,
			'delete_post_meta' => true,
			'current_time'   => fn( $type ) => $type === 'mysql'
				? gmdate( 'Y-m-d H:i:s' )
				: time(),
			'is_admin'       => false,
		] );
	}

	/**
	 * Tear down.
	 */
	protected function tearDown(): void {
		Settings::reset_instance();
		EmailManager::reset_instance();
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test cron handler exits early when expiration_days is 0 (disabled).
	 */
	public function test_cron_exits_early_when_expiration_disabled(): void {
		// Mock apd_get_setting to return 0 for expiration_days.
		Functions\when( 'apd_get_setting' )->alias( function ( $key, $default = null ) {
			if ( $key === 'expiration_days' ) {
				return 0;
			}
			return $default;
		} );

		// get_posts should never be called if we exit early.
		Functions\expect( 'get_posts' )->never();

		$this->plugin->cron_check_expired_listings();
	}

	/**
	 * Test cron handler exits early when expiration_days is negative.
	 */
	public function test_cron_exits_early_when_expiration_negative(): void {
		Functions\when( 'apd_get_setting' )->alias( function ( $key, $default = null ) {
			if ( $key === 'expiration_days' ) {
				return -1;
			}
			return $default;
		} );

		Functions\expect( 'get_posts' )->never();

		$this->plugin->cron_check_expired_listings();
	}

	/**
	 * Test cron handler queries for expired listings when expiration is configured.
	 */
	public function test_cron_queries_for_expired_listings(): void {
		Functions\when( 'apd_get_setting' )->alias( function ( $key, $default = null ) {
			if ( $key === 'expiration_days' ) {
				return 30;
			}
			return $default;
		} );

		// get_posts called once for expired listings.
		// Since function_exists('\apd_email_manager') returns false in unit tests,
		// the expiring-soon query is skipped.
		Functions\expect( 'get_posts' )
			->once()
			->andReturn( [] );

		$this->plugin->cron_check_expired_listings();
	}

	/**
	 * Test cron handler transitions expired listings to 'expired' status.
	 */
	public function test_cron_transitions_expired_listings(): void {
		Functions\when( 'apd_get_setting' )->alias( function ( $key, $default = null ) {
			if ( $key === 'expiration_days' ) {
				return 30;
			}
			return $default;
		} );

		// Return two expired listing IDs.
		Functions\expect( 'get_posts' )
			->once()
			->andReturn( [ 101, 102 ] );

		// Each listing should be updated to 'expired' status.
		$updated_posts = [];
		Functions\when( 'wp_update_post' )->alias( function ( $args ) use ( &$updated_posts ) {
			$updated_posts[] = $args;
			return $args['ID'];
		} );

		$this->plugin->cron_check_expired_listings();

		$this->assertCount( 2, $updated_posts );

		$this->assertSame( 101, $updated_posts[0]['ID'] );
		$this->assertSame( 'expired', $updated_posts[0]['post_status'] );

		$this->assertSame( 102, $updated_posts[1]['ID'] );
		$this->assertSame( 'expired', $updated_posts[1]['post_status'] );
	}

	/**
	 * Test that wp_update_post is called with exactly the right fields.
	 */
	public function test_cron_update_post_uses_correct_data(): void {
		Functions\when( 'apd_get_setting' )->alias( function ( $key, $default = null ) {
			if ( $key === 'expiration_days' ) {
				return 7;
			}
			return $default;
		} );

		Functions\expect( 'get_posts' )
			->once()
			->andReturn( [ 999 ] );

		$expected_update = null;
		Functions\when( 'wp_update_post' )->alias( function ( $args ) use ( &$expected_update ) {
			$expected_update = $args;
			return $args['ID'];
		} );

		$this->plugin->cron_check_expired_listings();

		$this->assertNotNull( $expected_update );
		$this->assertSame( 999, $expected_update['ID'] );
		$this->assertSame( 'expired', $expected_update['post_status'] );
		$this->assertCount( 2, $expected_update, 'Only ID and post_status should be in the update array' );
	}

	/**
	 * Test cron handler does nothing when no listings are expired.
	 */
	public function test_cron_does_nothing_when_no_expired_listings(): void {
		Functions\when( 'apd_get_setting' )->alias( function ( $key, $default = null ) {
			if ( $key === 'expiration_days' ) {
				return 30;
			}
			return $default;
		} );

		Functions\expect( 'get_posts' )
			->once()
			->andReturn( [] );

		Functions\expect( 'wp_update_post' )->never();

		$this->plugin->cron_check_expired_listings();
	}

	/**
	 * Test cron handler respects the batch limit of 50 posts.
	 */
	public function test_cron_uses_correct_query_args(): void {
		Functions\when( 'apd_get_setting' )->alias( function ( $key, $default = null ) {
			if ( $key === 'expiration_days' ) {
				return 30;
			}
			return $default;
		} );

		$captured_args = null;
		Functions\expect( 'get_posts' )
			->once()
			->andReturnUsing( function ( $args ) use ( &$captured_args ) {
				$captured_args = $args;
				return [];
			} );

		$this->plugin->cron_check_expired_listings();

		$this->assertNotNull( $captured_args );
		$this->assertSame( 'apd_listing', $captured_args['post_type'] );
		$this->assertSame( 'publish', $captured_args['post_status'] );
		$this->assertSame( 50, $captured_args['posts_per_page'] );
		$this->assertSame( 'ids', $captured_args['fields'] );
		$this->assertArrayHasKey( 'date_query', $captured_args );
		$this->assertArrayHasKey( 'before', $captured_args['date_query'][0] );
	}

	/**
	 * Test that the expiration date calculation is correct.
	 *
	 * With 30 day expiration, listings published before (now - 30 days) should expire.
	 */
	public function test_cron_calculates_correct_expiration_date(): void {
		$expiration_days = 30;

		Functions\when( 'apd_get_setting' )->alias( function ( $key, $default = null ) use ( $expiration_days ) {
			if ( $key === 'expiration_days' ) {
				return $expiration_days;
			}
			return $default;
		} );

		$captured_args = null;
		Functions\expect( 'get_posts' )
			->once()
			->andReturnUsing( function ( $args ) use ( &$captured_args ) {
				$captured_args = $args;
				return [];
			} );

		$this->plugin->cron_check_expired_listings();

		// The 'before' date should be approximately 30 days ago.
		$before_date = $captured_args['date_query'][0]['before'];
		$expected    = gmdate( 'Y-m-d', strtotime( "-{$expiration_days} days" ) );

		// Compare just the date part (ignore time).
		$this->assertStringStartsWith( $expected, $before_date );
	}
}
