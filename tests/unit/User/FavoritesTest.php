<?php
/**
 * Favorites Unit Tests.
 *
 * @package APD\Tests\Unit\User
 */

declare(strict_types=1);

namespace APD\Tests\Unit\User;

use APD\User\Favorites;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for Favorites.
 */
final class FavoritesTest extends UnitTestCase {

	/**
	 * Favorites instance.
	 *
	 * @var Favorites
	 */
	private Favorites $favorites;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset singleton for clean tests.
		$reflection = new \ReflectionClass( Favorites::class );
		$instance   = $reflection->getProperty( 'instance' );
		// Note: setAccessible() is deprecated in PHP 8.5 but still functional.
		// The call is needed for PHP < 8.1 compatibility.
		@$instance->setAccessible( true );
		$instance->setValue( null, null );

		// Mock WordPress constants.
		if ( ! defined( 'DAY_IN_SECONDS' ) ) {
			define( 'DAY_IN_SECONDS', 86400 );
		}
		if ( ! defined( 'COOKIEPATH' ) ) {
			define( 'COOKIEPATH', '/' );
		}
		if ( ! defined( 'COOKIE_DOMAIN' ) ) {
			define( 'COOKIE_DOMAIN', '' );
		}

		// Common mock setup.
		Functions\stubs( [
			'get_current_user_id' => 1,
			'is_user_logged_in'   => true,
			'get_post'            => function( $id ) {
				if ( $id <= 0 ) {
					return null;
				}
				$post              = new \stdClass();
				$post->ID          = $id;
				$post->post_type   = 'apd_listing';
				$post->post_status = 'publish';
				$post->post_author = 1;
				return $post;
			},
			'current_user_can'    => false,
			'is_ssl'              => false,
			'wp_json_encode'      => 'json_encode',
			'metadata_exists'     => true,
			'wp_cache_delete'     => true,
		] );

		$this->favorites = Favorites::get_instance();
	}

	/**
	 * Test singleton pattern returns same instance.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = Favorites::get_instance();
		$instance2 = Favorites::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test constants are defined correctly.
	 */
	public function test_constants_are_defined(): void {
		$this->assertSame( '_apd_favorites', Favorites::META_KEY );
		$this->assertSame( '_apd_favorite_count', Favorites::LISTING_META_KEY );
		$this->assertSame( 'apd_guest_favorites', Favorites::COOKIE_NAME );
		$this->assertSame( 30, Favorites::COOKIE_EXPIRY_DAYS );
	}

	/**
	 * Set up a wpdb mock for atomic increment/decrement tests.
	 *
	 * @return void
	 */
	private function mock_wpdb_for_atomic_count(): void {
		global $wpdb;
		$wpdb            = Mockery::mock( 'wpdb' );
		$wpdb->postmeta  = 'wp_postmeta';
		$wpdb->shouldReceive( 'prepare' )->andReturn( 'prepared_query' );
		$wpdb->shouldReceive( 'query' )->andReturn( 1 );
	}

	/**
	 * Test add favorite for logged-in user.
	 */
	public function test_add_favorite_for_logged_in_user(): void {
		$this->mock_wpdb_for_atomic_count();

		$listing_id = 123;
		$user_id    = 1;

		// Mock user meta operations.
		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'update_user_meta' )->justReturn( true );

		$result = $this->favorites->add( $listing_id, $user_id );

		$this->assertTrue( $result );
	}

	/**
	 * Test add favorite fires hook.
	 */
	public function test_add_favorite_fires_hook(): void {
		$this->mock_wpdb_for_atomic_count();

		$listing_id = 123;
		$user_id    = 1;
		$hook_fired = false;

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'update_user_meta' )->justReturn( true );
		Functions\when( 'do_action' )->alias(
			function( $tag, ...$args ) use ( &$hook_fired, $listing_id, $user_id ) {
				if ( $tag === 'apd_favorite_added' && $args[0] === $listing_id && $args[1] === $user_id ) {
					$hook_fired = true;
				}
			}
		);

		$this->favorites->add( $listing_id, $user_id );

		$this->assertTrue( $hook_fired );
	}

	/**
	 * Test add favorite with already favorited listing.
	 */
	public function test_add_favorite_already_favorited(): void {
		$listing_id = 123;
		$user_id    = 1;

		// Already in favorites.
		Functions\when( 'get_user_meta' )->justReturn( [ 123 ] );

		$result = $this->favorites->add( $listing_id, $user_id );

		$this->assertTrue( $result );
	}

	/**
	 * Test add favorite with invalid listing.
	 */
	public function test_add_favorite_with_invalid_listing(): void {
		Functions\when( 'get_post' )->justReturn( null );

		$result = $this->favorites->add( 999, 1 );

		$this->assertFalse( $result );
	}

	/**
	 * Test add favorite with zero listing ID.
	 */
	public function test_add_favorite_with_zero_listing_id(): void {
		$result = $this->favorites->add( 0, 1 );

		$this->assertFalse( $result );
	}

	/**
	 * Test add favorite with non-listing post type.
	 */
	public function test_add_favorite_with_non_listing_post(): void {
		Functions\when( 'get_post' )->alias(
			function( $id ) {
				$post              = new \stdClass();
				$post->ID          = $id;
				$post->post_type   = 'post';
				$post->post_status = 'publish';
				$post->post_author = 1;
				return $post;
			}
		);

		$result = $this->favorites->add( 123, 1 );

		$this->assertFalse( $result );
	}

	/**
	 * Test remove favorite for logged-in user.
	 */
	public function test_remove_favorite_for_logged_in_user(): void {
		$this->mock_wpdb_for_atomic_count();

		$listing_id = 123;
		$user_id    = 1;

		// Has the listing in favorites.
		Functions\when( 'get_user_meta' )->justReturn( [ 123, 456 ] );
		Functions\when( 'update_user_meta' )->justReturn( true );

		$result = $this->favorites->remove( $listing_id, $user_id );

		$this->assertTrue( $result );
	}

	/**
	 * Test remove favorite fires hook.
	 */
	public function test_remove_favorite_fires_hook(): void {
		$this->mock_wpdb_for_atomic_count();

		$listing_id = 123;
		$user_id    = 1;
		$hook_fired = false;

		Functions\when( 'get_user_meta' )->justReturn( [ 123 ] );
		Functions\when( 'update_user_meta' )->justReturn( true );
		Functions\when( 'do_action' )->alias(
			function( $tag, ...$args ) use ( &$hook_fired, $listing_id, $user_id ) {
				if ( $tag === 'apd_favorite_removed' && $args[0] === $listing_id && $args[1] === $user_id ) {
					$hook_fired = true;
				}
			}
		);

		$this->favorites->remove( $listing_id, $user_id );

		$this->assertTrue( $hook_fired );
	}

	/**
	 * Test remove favorite when not favorited.
	 */
	public function test_remove_favorite_not_favorited(): void {
		$listing_id = 123;
		$user_id    = 1;

		// Not in favorites.
		Functions\when( 'get_user_meta' )->justReturn( [ 456 ] );

		$result = $this->favorites->remove( $listing_id, $user_id );

		$this->assertTrue( $result );
	}

	/**
	 * Test toggle favorite adds when not favorited.
	 */
	public function test_toggle_adds_when_not_favorited(): void {
		$this->mock_wpdb_for_atomic_count();

		$listing_id = 123;
		$user_id    = 1;

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'update_user_meta' )->justReturn( true );

		$result = $this->favorites->toggle( $listing_id, $user_id );

		$this->assertTrue( $result );
	}

	/**
	 * Test toggle favorite removes when favorited.
	 */
	public function test_toggle_removes_when_favorited(): void {
		$this->mock_wpdb_for_atomic_count();

		$listing_id    = 123;
		$user_id       = 1;
		$call_count    = 0;

		Functions\when( 'get_user_meta' )->alias(
			function() use ( &$call_count ) {
				$call_count++;
				// First call (is_favorite check) returns with listing.
				// Second call (remove) also returns with listing.
				return [ 123 ];
			}
		);
		Functions\when( 'update_user_meta' )->justReturn( true );

		$result = $this->favorites->toggle( $listing_id, $user_id );

		$this->assertFalse( $result );
	}

	/**
	 * Test toggle with invalid listing returns null.
	 */
	public function test_toggle_with_invalid_listing_returns_null(): void {
		Functions\when( 'get_post' )->justReturn( null );

		$result = $this->favorites->toggle( 999, 1 );

		$this->assertNull( $result );
	}

	/**
	 * Test is_favorite returns true when favorited.
	 */
	public function test_is_favorite_returns_true_when_favorited(): void {
		Functions\when( 'get_user_meta' )->justReturn( [ 123, 456 ] );

		$result = $this->favorites->is_favorite( 123, 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test is_favorite returns false when not favorited.
	 */
	public function test_is_favorite_returns_false_when_not_favorited(): void {
		Functions\when( 'get_user_meta' )->justReturn( [ 456, 789 ] );

		$result = $this->favorites->is_favorite( 123, 1 );

		$this->assertFalse( $result );
	}

	/**
	 * Test get_favorites returns array of listing IDs.
	 */
	public function test_get_favorites_returns_listing_ids(): void {
		Functions\when( 'get_user_meta' )->justReturn( [ 123, 456, 789 ] );

		$favorites = $this->favorites->get_favorites( 1 );

		$this->assertSame( [ 123, 456, 789 ], $favorites );
	}

	/**
	 * Test get_favorites returns empty array when none.
	 */
	public function test_get_favorites_returns_empty_when_none(): void {
		Functions\when( 'get_user_meta' )->justReturn( '' );

		$favorites = $this->favorites->get_favorites( 1 );

		$this->assertSame( [], $favorites );
	}

	/**
	 * Test get_favorites sanitizes values to integers.
	 */
	public function test_get_favorites_sanitizes_to_integers(): void {
		Functions\when( 'get_user_meta' )->justReturn( [ '123', '456', 'abc' ] );

		$favorites = $this->favorites->get_favorites( 1 );

		$this->assertSame( [ 123, 456, 0 ], $favorites );
	}

	/**
	 * Test get_count returns correct count.
	 */
	public function test_get_count_returns_correct_count(): void {
		Functions\when( 'get_user_meta' )->justReturn( [ 123, 456, 789 ] );

		$count = $this->favorites->get_count( 1 );

		$this->assertSame( 3, $count );
	}

	/**
	 * Test get_count returns zero when no favorites.
	 */
	public function test_get_count_returns_zero_when_empty(): void {
		Functions\when( 'get_user_meta' )->justReturn( [] );

		$count = $this->favorites->get_count( 1 );

		$this->assertSame( 0, $count );
	}

	/**
	 * Test get_listing_favorite_count returns count.
	 */
	public function test_get_listing_favorite_count_returns_count(): void {
		Functions\when( 'get_post_meta' )->justReturn( 42 );

		$count = $this->favorites->get_listing_favorite_count( 123 );

		$this->assertSame( 42, $count );
	}

	/**
	 * Test get_listing_favorite_count returns zero when none.
	 */
	public function test_get_listing_favorite_count_returns_zero(): void {
		Functions\when( 'get_post_meta' )->justReturn( '' );

		$count = $this->favorites->get_listing_favorite_count( 123 );

		$this->assertSame( 0, $count );
	}

	/**
	 * Test clear removes all favorites.
	 */
	public function test_clear_removes_all_favorites(): void {
		$this->mock_wpdb_for_atomic_count();

		Functions\when( 'get_user_meta' )->justReturn( [ 123, 456 ] );
		Functions\when( 'delete_user_meta' )->justReturn( true );

		$result = $this->favorites->clear( 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test clear fires hook.
	 */
	public function test_clear_fires_hook(): void {
		$hook_fired = false;
		$user_id    = 1;

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'delete_user_meta' )->justReturn( true );
		Functions\when( 'do_action' )->alias(
			function( $tag, ...$args ) use ( &$hook_fired, $user_id ) {
				if ( $tag === 'apd_favorites_cleared' && $args[0] === $user_id ) {
					$hook_fired = true;
				}
			}
		);

		$this->favorites->clear( $user_id );

		$this->assertTrue( $hook_fired );
	}

	/**
	 * Test requires_login returns true by default.
	 */
	public function test_requires_login_returns_true_by_default(): void {
		// Guest favorites disabled by default.
		$result = $this->favorites->requires_login();

		$this->assertTrue( $result );
	}

	/**
	 * Test requires_login returns false when guest favorites enabled.
	 */
	public function test_requires_login_false_when_guest_enabled(): void {
		Functions\when( 'apply_filters' )->alias(
			function( $tag, $value ) {
				if ( $tag === 'apd_guest_favorites_enabled' ) {
					return true;
				}
				if ( $tag === 'apd_favorites_require_login' ) {
					return false;
				}
				return $value;
			}
		);

		$result = $this->favorites->requires_login();

		$this->assertFalse( $result );
	}

	/**
	 * Test guest_favorites_enabled returns false by default.
	 */
	public function test_guest_favorites_disabled_by_default(): void {
		$result = $this->favorites->guest_favorites_enabled();

		$this->assertFalse( $result );
	}

	/**
	 * Test operations fail for guest when login required.
	 */
	public function test_operations_fail_for_guest_when_login_required(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		// Add should fail.
		$this->assertFalse( $this->favorites->add( 123, 0 ) );

		// Remove should fail.
		$this->assertFalse( $this->favorites->remove( 123, 0 ) );

		// Toggle should return null.
		$this->assertNull( $this->favorites->toggle( 123, 0 ) );

		// is_favorite should return false.
		$this->assertFalse( $this->favorites->is_favorite( 123, 0 ) );

		// get_favorites should return empty.
		$this->assertSame( [], $this->favorites->get_favorites( 0 ) );

		// get_count should return 0.
		$this->assertSame( 0, $this->favorites->get_count( 0 ) );

		// clear should fail.
		$this->assertFalse( $this->favorites->clear( 0 ) );
	}

	/**
	 * Test uses current user when user_id is null.
	 */
	public function test_uses_current_user_when_null(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 42 );
		Functions\when( 'get_user_meta' )->alias(
			function( $user_id, $key, $single ) {
				if ( $user_id === 42 ) {
					return [ 123 ];
				}
				return [];
			}
		);

		$result = $this->favorites->is_favorite( 123, null );

		$this->assertTrue( $result );
	}

	/**
	 * Test add increments listing count atomically.
	 */
	public function test_add_increments_listing_count(): void {
		global $wpdb;
		$wpdb            = Mockery::mock( 'wpdb' );
		$wpdb->postmeta  = 'wp_postmeta';

		$listing_id    = 123;
		$query_run     = false;

		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'update_user_meta' )->justReturn( true );

		$wpdb->shouldReceive( 'prepare' )
			->once()
			->andReturnUsing( function() use ( &$query_run ) {
				$query_run = true;
				return 'UPDATE wp_postmeta SET meta_value = CAST(meta_value AS UNSIGNED) + 1 WHERE post_id = 123 AND meta_key = \'_apd_favorite_count\'';
			} );

		$wpdb->shouldReceive( 'query' )
			->once()
			->andReturn( 1 );

		$this->favorites->add( $listing_id, 1 );

		$this->assertTrue( $query_run, 'Atomic increment query should be executed' );
	}

	/**
	 * Test remove decrements listing count atomically.
	 */
	public function test_remove_decrements_listing_count(): void {
		global $wpdb;
		$wpdb            = Mockery::mock( 'wpdb' );
		$wpdb->postmeta  = 'wp_postmeta';

		$listing_id  = 123;
		$query_run   = false;

		Functions\when( 'get_user_meta' )->justReturn( [ 123 ] );
		Functions\when( 'update_user_meta' )->justReturn( true );

		$wpdb->shouldReceive( 'prepare' )
			->once()
			->andReturnUsing( function() use ( &$query_run ) {
				$query_run = true;
				return 'UPDATE wp_postmeta SET meta_value = GREATEST(CAST(meta_value AS UNSIGNED) - 1, 0) WHERE post_id = 123 AND meta_key = \'_apd_favorite_count\'';
			} );

		$wpdb->shouldReceive( 'query' )
			->once()
			->andReturn( 1 );

		$this->favorites->remove( $listing_id, 1 );

		$this->assertTrue( $query_run, 'Atomic decrement query should be executed' );
	}

	/**
	 * Test remove uses GREATEST to prevent negative counts.
	 */
	public function test_remove_does_not_decrement_below_zero(): void {
		global $wpdb;
		$wpdb            = Mockery::mock( 'wpdb' );
		$wpdb->postmeta  = 'wp_postmeta';

		$listing_id   = 123;
		$sql_executed = null;

		Functions\when( 'get_user_meta' )->justReturn( [ 123 ] );
		Functions\when( 'update_user_meta' )->justReturn( true );

		$wpdb->shouldReceive( 'prepare' )
			->once()
			->andReturnUsing( function( $query ) use ( &$sql_executed ) {
				$sql_executed = $query;
				return 'prepared_query';
			} );

		$wpdb->shouldReceive( 'query' )
			->once()
			->andReturn( 1 );

		$this->favorites->remove( $listing_id, 1 );

		$this->assertStringContainsString( 'GREATEST', $sql_executed, 'SQL should use GREATEST to prevent negative counts' );
	}

	/**
	 * Test clear decrements all listing counts atomically.
	 */
	public function test_clear_decrements_all_listing_counts(): void {
		global $wpdb;
		$wpdb            = Mockery::mock( 'wpdb' );
		$wpdb->postmeta  = 'wp_postmeta';

		$favorites       = [ 123, 456 ];
		$queries_run     = 0;

		Functions\when( 'get_user_meta' )->justReturn( $favorites );
		Functions\when( 'delete_user_meta' )->justReturn( true );

		$wpdb->shouldReceive( 'prepare' )
			->twice()
			->andReturnUsing( function() use ( &$queries_run ) {
				++$queries_run;
				return 'prepared_query';
			} );

		$wpdb->shouldReceive( 'query' )
			->twice()
			->andReturn( 1 );

		$this->favorites->clear( 1 );

		$this->assertSame( 2, $queries_run, 'Should run atomic decrement for each listing' );
	}

	/**
	 * Test is_valid_listing checks post author for unpublished.
	 */
	public function test_validates_author_for_unpublished_listing(): void {
		global $wpdb;
		$wpdb            = Mockery::mock( 'wpdb' );
		$wpdb->postmeta  = 'wp_postmeta';

		$listing_id = 123;

		// Draft listing owned by user 1.
		Functions\when( 'get_post' )->alias(
			function( $id ) {
				$post              = new \stdClass();
				$post->ID          = $id;
				$post->post_type   = 'apd_listing';
				$post->post_status = 'draft';
				$post->post_author = 1;
				return $post;
			}
		);
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'update_user_meta' )->justReturn( true );

		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'prepared_query' );
		$wpdb->shouldReceive( 'query' )->once()->andReturn( 1 );

		// User 1 can add their own draft.
		$result = $this->favorites->add( $listing_id, 1 );
		$this->assertTrue( $result );
	}

	/**
	 * Test rejects draft listing not owned by user.
	 */
	public function test_rejects_draft_listing_not_owned_by_user(): void {
		$listing_id = 123;

		// Draft listing owned by user 2.
		Functions\when( 'get_post' )->alias(
			function( $id ) {
				$post              = new \stdClass();
				$post->ID          = $id;
				$post->post_type   = 'apd_listing';
				$post->post_status = 'draft';
				$post->post_author = 2;
				return $post;
			}
		);
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->justReturn( false );

		// User 1 cannot add user 2's draft.
		$result = $this->favorites->add( $listing_id, 1 );
		$this->assertFalse( $result );
	}

	/**
	 * Test editor can access unpublished listings.
	 */
	public function test_editor_can_access_unpublished_listings(): void {
		global $wpdb;
		$wpdb            = Mockery::mock( 'wpdb' );
		$wpdb->postmeta  = 'wp_postmeta';

		$listing_id = 123;

		// Draft listing owned by user 2.
		Functions\when( 'get_post' )->alias(
			function( $id ) {
				$post              = new \stdClass();
				$post->ID          = $id;
				$post->post_type   = 'apd_listing';
				$post->post_status = 'draft';
				$post->post_author = 2;
				return $post;
			}
		);
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->alias(
			function( $cap ) {
				return $cap === 'edit_others_posts';
			}
		);
		Functions\when( 'get_user_meta' )->justReturn( [] );
		Functions\when( 'update_user_meta' )->justReturn( true );

		$wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'prepared_query' );
		$wpdb->shouldReceive( 'query' )->once()->andReturn( 1 );

		// Editor can add any listing.
		$result = $this->favorites->add( $listing_id, 1 );
		$this->assertTrue( $result );
	}

	/**
	 * Test init hooks login merge action.
	 */
	public function test_init_hooks_login_merge(): void {
		$action_added = false;

		Functions\when( 'add_action' )->alias(
			function( $tag, $callback, $priority = 10 ) use ( &$action_added ) {
				if ( $tag === 'wp_login' && is_array( $callback ) && $callback[1] === 'merge_guest_favorites_on_login' ) {
					$action_added = true;
				}
			}
		);

		$this->favorites->init();

		$this->assertTrue( $action_added );
	}
}
