<?php
/**
 * ReviewManager Unit Tests.
 *
 * @package APD\Tests\Unit\Review
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Review;

use APD\Review\ReviewManager;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for ReviewManager.
 */
final class ReviewManagerTest extends UnitTestCase {

	/**
	 * ReviewManager instance.
	 *
	 * @var ReviewManager
	 */
	private ReviewManager $manager;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset singleton for clean tests.
		$reflection = new \ReflectionClass( ReviewManager::class );
		$instance   = $reflection->getProperty( 'instance' );
		// Note: setAccessible() is deprecated in PHP 8.5 but still functional.
		@$instance->setAccessible( true );
		$instance->setValue( null, null );

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
			'get_userdata'        => function( $user_id ) {
				if ( $user_id <= 0 ) {
					return false;
				}
				$user                = new \stdClass();
				$user->ID            = $user_id;
				$user->display_name  = 'Test User';
				$user->user_email    = 'test@example.com';
				$user->user_url      = 'https://example.com';
				return $user;
			},
			'is_email'            => function( $email ) {
				return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false;
			},
			'sanitize_email'      => function( $email ) {
				return filter_var( $email, FILTER_SANITIZE_EMAIL );
			},
			'wp_kses_post'        => function( $content ) {
				return $content;
			},
			'date_i18n'           => function( $format, $timestamp ) {
				return date( 'F j, Y', $timestamp );
			},
			'get_option'          => function( $option, $default = false ) {
				if ( $option === 'date_format' ) {
					return 'F j, Y';
				}
				return $default;
			},
		] );

		$this->manager = ReviewManager::get_instance();
	}

	/**
	 * Test singleton pattern returns same instance.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = ReviewManager::get_instance();
		$instance2 = ReviewManager::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test constants are defined correctly.
	 */
	public function test_constants_are_defined(): void {
		$this->assertSame( 'apd_review', ReviewManager::COMMENT_TYPE );
		$this->assertSame( '_apd_rating', ReviewManager::META_RATING );
		$this->assertSame( '_apd_review_title', ReviewManager::META_TITLE );
		$this->assertSame( 1, ReviewManager::MIN_RATING );
		$this->assertSame( 5, ReviewManager::MAX_RATING );
		$this->assertSame( 10, ReviewManager::DEFAULT_MIN_CONTENT_LENGTH );
	}

	/**
	 * Test create review with valid data.
	 */
	public function test_create_review_with_valid_data(): void {
		$listing_id = 123;
		$user_id    = 1;
		$comment_id = 456;

		// Return 0 for count query (has_user_reviewed check).
		Functions\when( 'get_comments' )->justReturn( 0 );
		Functions\when( 'wp_insert_comment' )->justReturn( $comment_id );
		Functions\when( 'update_comment_meta' )->justReturn( true );

		$result = $this->manager->create( $listing_id, [
			'rating'  => 5,
			'content' => 'This is a great listing with excellent service!',
			'title'   => 'Great!',
			'user_id' => $user_id,
		] );

		$this->assertSame( $comment_id, $result );
	}

	/**
	 * Test create review fires hook.
	 */
	public function test_create_review_fires_hook(): void {
		$listing_id = 123;
		$comment_id = 456;
		$hook_fired = false;

		// Return 0 for count query (has_user_reviewed check).
		Functions\when( 'get_comments' )->justReturn( 0 );
		Functions\when( 'wp_insert_comment' )->justReturn( $comment_id );
		Functions\when( 'update_comment_meta' )->justReturn( true );
		Functions\when( 'do_action' )->alias(
			function( $tag, ...$args ) use ( &$hook_fired, $comment_id, $listing_id ) {
				if ( $tag === 'apd_review_created' && $args[0] === $comment_id && $args[1] === $listing_id ) {
					$hook_fired = true;
				}
			}
		);

		$this->manager->create( $listing_id, [
			'rating'  => 5,
			'content' => 'This is a great listing with excellent service!',
		] );

		$this->assertTrue( $hook_fired );
	}

	/**
	 * Test create review with invalid listing.
	 */
	public function test_create_review_with_invalid_listing(): void {
		Functions\when( 'get_post' )->justReturn( null );

		$result = $this->manager->create( 999, [
			'rating'  => 5,
			'content' => 'This is a great listing!',
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'invalid_listing', $result->get_error_code() );
	}

	/**
	 * Test create review with non-listing post type.
	 */
	public function test_create_review_with_non_listing_post(): void {
		Functions\when( 'get_post' )->alias(
			function( $id ) {
				$post              = new \stdClass();
				$post->ID          = $id;
				$post->post_type   = 'post';
				$post->post_status = 'publish';
				return $post;
			}
		);

		$result = $this->manager->create( 123, [
			'rating'  => 5,
			'content' => 'This is a great listing!',
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'invalid_listing', $result->get_error_code() );
	}

	/**
	 * Test create review with missing rating.
	 */
	public function test_create_review_with_missing_rating(): void {
		$result = $this->manager->create( 123, [
			'content' => 'This is a great listing!',
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rating_required', $result->get_error_code() );
	}

	/**
	 * Test create review with invalid rating below minimum.
	 */
	public function test_create_review_with_rating_below_minimum(): void {
		$result = $this->manager->create( 123, [
			'rating'  => 0,
			'content' => 'This is a great listing!',
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'invalid_rating', $result->get_error_code() );
	}

	/**
	 * Test create review with invalid rating above maximum.
	 */
	public function test_create_review_with_rating_above_maximum(): void {
		$result = $this->manager->create( 123, [
			'rating'  => 6,
			'content' => 'This is a great listing!',
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'invalid_rating', $result->get_error_code() );
	}

	/**
	 * Test create review with missing content.
	 */
	public function test_create_review_with_missing_content(): void {
		$result = $this->manager->create( 123, [
			'rating' => 5,
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'content_required', $result->get_error_code() );
	}

	/**
	 * Test create review with content too short.
	 */
	public function test_create_review_with_content_too_short(): void {
		$result = $this->manager->create( 123, [
			'rating'  => 5,
			'content' => 'Short',
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'content_too_short', $result->get_error_code() );
	}

	/**
	 * Test create review when user already reviewed.
	 */
	public function test_create_review_when_user_already_reviewed(): void {
		Functions\when( 'get_comments' )->justReturn( 1 ); // Count query returns 1.

		$result = $this->manager->create( 123, [
			'rating'  => 5,
			'content' => 'This is a great listing with excellent service!',
			'user_id' => 1,
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'already_reviewed', $result->get_error_code() );
	}

	/**
	 * Test create review requires login by default.
	 */
	public function test_create_review_requires_login(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$result = $this->manager->create( 123, [
			'rating'  => 5,
			'content' => 'This is a great listing with excellent service!',
			'user_id' => 0,
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'login_required', $result->get_error_code() );
	}

	/**
	 * Test create review with guest when allowed.
	 */
	public function test_create_review_with_guest_when_allowed(): void {
		$comment_id = 456;

		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		Functions\when( 'apply_filters' )->alias(
			function( $tag, $value ) {
				if ( $tag === 'apd_reviews_require_login' ) {
					return false; // Allow guest reviews.
				}
				if ( $tag === 'apd_review_default_status' ) {
					return 'pending';
				}
				return $value;
			}
		);
		Functions\when( 'get_comments' )->justReturn( [] );
		Functions\when( 'wp_insert_comment' )->justReturn( $comment_id );
		Functions\when( 'update_comment_meta' )->justReturn( true );

		$result = $this->manager->create( 123, [
			'rating'       => 5,
			'content'      => 'This is a great listing with excellent service!',
			'author_name'  => 'John Doe',
			'author_email' => 'john@example.com',
			'user_id'      => 0,
		] );

		$this->assertSame( $comment_id, $result );
	}

	/**
	 * Test update review with valid data.
	 */
	public function test_update_review_with_valid_data(): void {
		$review_id = 456;

		// Create mock comment.
		$comment                   = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID       = $review_id;
		$comment->comment_post_ID  = 123;
		$comment->comment_type     = ReviewManager::COMMENT_TYPE;
		$comment->user_id          = 1;
		$comment->comment_author   = 'Test User';
		$comment->comment_author_email = 'test@example.com';
		$comment->comment_content  = 'Original content';
		$comment->comment_approved = '1';
		$comment->comment_date     = '2024-01-15 10:30:00';

		Functions\when( 'get_comment' )->justReturn( $comment );
		Functions\when( 'get_comment_meta' )->alias(
			function( $comment_id, $key, $single ) {
				if ( $key === ReviewManager::META_RATING ) {
					return 4;
				}
				if ( $key === ReviewManager::META_TITLE ) {
					return 'Original Title';
				}
				return '';
			}
		);
		Functions\when( 'wp_update_comment' )->justReturn( 1 );
		Functions\when( 'update_comment_meta' )->justReturn( true );

		$result = $this->manager->update( $review_id, [
			'rating'  => 5,
			'content' => 'Updated review content that is long enough!',
			'title'   => 'Updated Title',
		] );

		$this->assertTrue( $result );
	}

	/**
	 * Test update review fires hook.
	 */
	public function test_update_review_fires_hook(): void {
		$review_id  = 456;
		$hook_fired = false;

		// Create mock comment.
		$comment                   = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID       = $review_id;
		$comment->comment_post_ID  = 123;
		$comment->comment_type     = ReviewManager::COMMENT_TYPE;
		$comment->user_id          = 1;
		$comment->comment_author   = 'Test User';
		$comment->comment_author_email = 'test@example.com';
		$comment->comment_content  = 'Original content';
		$comment->comment_approved = '1';
		$comment->comment_date     = '2024-01-15 10:30:00';

		Functions\when( 'get_comment' )->justReturn( $comment );
		Functions\when( 'get_comment_meta' )->justReturn( 4 );
		Functions\when( 'wp_update_comment' )->justReturn( 1 );
		Functions\when( 'update_comment_meta' )->justReturn( true );
		Functions\when( 'do_action' )->alias(
			function( $tag, ...$args ) use ( &$hook_fired, $review_id ) {
				if ( $tag === 'apd_review_updated' && $args[0] === $review_id ) {
					$hook_fired = true;
				}
			}
		);

		$this->manager->update( $review_id, [
			'rating' => 5,
		] );

		$this->assertTrue( $hook_fired );
	}

	/**
	 * Test update review with invalid review ID.
	 */
	public function test_update_review_with_invalid_id(): void {
		Functions\when( 'get_comment' )->justReturn( null );

		$result = $this->manager->update( 999, [
			'rating' => 5,
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'invalid_review', $result->get_error_code() );
	}

	/**
	 * Test update review with invalid rating.
	 */
	public function test_update_review_with_invalid_rating(): void {
		$comment                   = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID       = 456;
		$comment->comment_post_ID  = 123;
		$comment->comment_type     = ReviewManager::COMMENT_TYPE;
		$comment->user_id          = 1;
		$comment->comment_author   = 'Test User';
		$comment->comment_author_email = 'test@example.com';
		$comment->comment_content  = 'Content';
		$comment->comment_approved = '1';
		$comment->comment_date     = '2024-01-15 10:30:00';

		Functions\when( 'get_comment' )->justReturn( $comment );
		Functions\when( 'get_comment_meta' )->justReturn( 4 );

		$result = $this->manager->update( 456, [
			'rating' => 10,
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'invalid_rating', $result->get_error_code() );
	}

	/**
	 * Test delete review.
	 */
	public function test_delete_review(): void {
		$review_id = 456;

		$comment                   = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID       = $review_id;
		$comment->comment_post_ID  = 123;
		$comment->comment_type     = ReviewManager::COMMENT_TYPE;
		$comment->user_id          = 1;
		$comment->comment_author   = 'Test User';
		$comment->comment_author_email = 'test@example.com';
		$comment->comment_content  = 'Content';
		$comment->comment_approved = '1';
		$comment->comment_date     = '2024-01-15 10:30:00';

		Functions\when( 'get_comment' )->justReturn( $comment );
		Functions\when( 'get_comment_meta' )->justReturn( 4 );
		Functions\when( 'wp_delete_comment' )->justReturn( true );

		$result = $this->manager->delete( $review_id );

		$this->assertTrue( $result );
	}

	/**
	 * Test delete review fires hook.
	 */
	public function test_delete_review_fires_hook(): void {
		$review_id  = 456;
		$hook_fired = false;

		$comment                   = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID       = $review_id;
		$comment->comment_post_ID  = 123;
		$comment->comment_type     = ReviewManager::COMMENT_TYPE;
		$comment->user_id          = 1;
		$comment->comment_author   = 'Test User';
		$comment->comment_author_email = 'test@example.com';
		$comment->comment_content  = 'Content';
		$comment->comment_approved = '1';
		$comment->comment_date     = '2024-01-15 10:30:00';

		Functions\when( 'get_comment' )->justReturn( $comment );
		Functions\when( 'get_comment_meta' )->justReturn( 4 );
		Functions\when( 'wp_delete_comment' )->justReturn( true );
		Functions\when( 'do_action' )->alias(
			function( $tag, ...$args ) use ( &$hook_fired, $review_id ) {
				if ( $tag === 'apd_review_deleted' && $args[0] === $review_id ) {
					$hook_fired = true;
				}
			}
		);

		$this->manager->delete( $review_id );

		$this->assertTrue( $hook_fired );
	}

	/**
	 * Test delete review with invalid ID.
	 */
	public function test_delete_review_with_invalid_id(): void {
		Functions\when( 'get_comment' )->justReturn( null );

		$result = $this->manager->delete( 999 );

		$this->assertFalse( $result );
	}

	/**
	 * Test get review.
	 */
	public function test_get_review(): void {
		$review_id = 456;

		$comment                   = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID       = $review_id;
		$comment->comment_post_ID  = 123;
		$comment->comment_type     = ReviewManager::COMMENT_TYPE;
		$comment->user_id          = 1;
		$comment->comment_author   = 'Test User';
		$comment->comment_author_email = 'test@example.com';
		$comment->comment_content  = 'Great listing!';
		$comment->comment_approved = '1';
		$comment->comment_date     = '2024-01-15 10:30:00';

		Functions\when( 'get_comment' )->justReturn( $comment );
		Functions\when( 'get_comment_meta' )->alias(
			function( $comment_id, $key, $single ) {
				if ( $key === ReviewManager::META_RATING ) {
					return 5;
				}
				if ( $key === ReviewManager::META_TITLE ) {
					return 'Excellent!';
				}
				return '';
			}
		);
		Functions\when( 'get_option' )->justReturn( 'F j, Y' );

		$review = $this->manager->get( $review_id );

		$this->assertIsArray( $review );
		$this->assertSame( $review_id, $review['id'] );
		$this->assertSame( 123, $review['listing_id'] );
		$this->assertSame( 1, $review['author_id'] );
		$this->assertSame( 'Test User', $review['author_name'] );
		$this->assertSame( 5, $review['rating'] );
		$this->assertSame( 'Excellent!', $review['title'] );
		$this->assertSame( 'Great listing!', $review['content'] );
		$this->assertSame( 'approved', $review['status'] );
	}

	/**
	 * Test get review with invalid ID.
	 */
	public function test_get_review_with_invalid_id(): void {
		Functions\when( 'get_comment' )->justReturn( null );

		$review = $this->manager->get( 999 );

		$this->assertNull( $review );
	}

	/**
	 * Test get review with wrong comment type.
	 */
	public function test_get_review_with_wrong_comment_type(): void {
		$comment               = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID   = 456;
		$comment->comment_type = 'comment'; // Not apd_review.

		Functions\when( 'get_comment' )->justReturn( $comment );

		$review = $this->manager->get( 456 );

		$this->assertNull( $review );
	}

	/**
	 * Test get listing reviews.
	 */
	public function test_get_listing_reviews(): void {
		$listing_id = 123;

		$comment1                       = Mockery::mock( \WP_Comment::class );
		$comment1->comment_ID           = 1;
		$comment1->comment_post_ID      = $listing_id;
		$comment1->comment_type         = ReviewManager::COMMENT_TYPE;
		$comment1->user_id              = 1;
		$comment1->comment_author       = 'User 1';
		$comment1->comment_author_email = 'user1@example.com';
		$comment1->comment_content      = 'Review 1';
		$comment1->comment_approved     = '1';
		$comment1->comment_date         = '2024-01-15 10:30:00';

		$comment2                       = Mockery::mock( \WP_Comment::class );
		$comment2->comment_ID           = 2;
		$comment2->comment_post_ID      = $listing_id;
		$comment2->comment_type         = ReviewManager::COMMENT_TYPE;
		$comment2->user_id              = 2;
		$comment2->comment_author       = 'User 2';
		$comment2->comment_author_email = 'user2@example.com';
		$comment2->comment_content      = 'Review 2';
		$comment2->comment_approved     = '1';
		$comment2->comment_date         = '2024-01-14 09:00:00';

		$call_count = 0;
		Functions\when( 'get_comments' )->alias(
			function( $args ) use ( &$call_count, $comment1, $comment2 ) {
				$call_count++;
				// First call: get comments.
				if ( $call_count === 1 ) {
					return [ $comment1, $comment2 ];
				}
				// Second call: count query.
				return 2;
			}
		);
		Functions\when( 'get_comment_meta' )->justReturn( 5 );
		Functions\when( 'get_option' )->justReturn( 'F j, Y' );

		$result = $this->manager->get_listing_reviews( $listing_id );

		$this->assertArrayHasKey( 'reviews', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertArrayHasKey( 'pages', $result );
		$this->assertCount( 2, $result['reviews'] );
		$this->assertSame( 2, $result['total'] );
	}

	/**
	 * Test get user review.
	 */
	public function test_get_user_review(): void {
		$listing_id = 123;
		$user_id    = 1;

		$comment                       = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID           = 456;
		$comment->comment_post_ID      = $listing_id;
		$comment->comment_type         = ReviewManager::COMMENT_TYPE;
		$comment->user_id              = $user_id;
		$comment->comment_author       = 'Test User';
		$comment->comment_author_email = 'test@example.com';
		$comment->comment_content      = 'My review';
		$comment->comment_approved     = '1';
		$comment->comment_date         = '2024-01-15 10:30:00';

		Functions\when( 'get_comments' )->justReturn( [ $comment ] );
		Functions\when( 'get_comment_meta' )->justReturn( 5 );
		Functions\when( 'get_option' )->justReturn( 'F j, Y' );

		$review = $this->manager->get_user_review( $listing_id, $user_id );

		$this->assertIsArray( $review );
		$this->assertSame( 456, $review['id'] );
		$this->assertSame( $user_id, $review['author_id'] );
	}

	/**
	 * Test get user review returns null for invalid user.
	 */
	public function test_get_user_review_returns_null_for_invalid_user(): void {
		$review = $this->manager->get_user_review( 123, 0 );

		$this->assertNull( $review );
	}

	/**
	 * Test has user reviewed returns true.
	 */
	public function test_has_user_reviewed_returns_true(): void {
		Functions\when( 'get_comments' )->justReturn( 1 );

		$result = $this->manager->has_user_reviewed( 123, 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test has user reviewed returns false.
	 */
	public function test_has_user_reviewed_returns_false(): void {
		Functions\when( 'get_comments' )->justReturn( 0 );

		$result = $this->manager->has_user_reviewed( 123, 1 );

		$this->assertFalse( $result );
	}

	/**
	 * Test has user reviewed returns false for invalid user.
	 */
	public function test_has_user_reviewed_returns_false_for_invalid_user(): void {
		$result = $this->manager->has_user_reviewed( 123, 0 );

		$this->assertFalse( $result );
	}

	/**
	 * Test get review count.
	 */
	public function test_get_review_count(): void {
		Functions\when( 'get_comments' )->justReturn( 5 );

		$count = $this->manager->get_review_count( 123 );

		$this->assertSame( 5, $count );
	}

	/**
	 * Test get review count with specific status.
	 */
	public function test_get_review_count_with_status(): void {
		$call_args = null;
		Functions\when( 'get_comments' )->alias(
			function( $args ) use ( &$call_args ) {
				$call_args = $args;
				return 3;
			}
		);

		$count = $this->manager->get_review_count( 123, 'pending' );

		$this->assertSame( 3, $count );
		$this->assertSame( 'hold', $call_args['status'] );
	}

	/**
	 * Test approve review.
	 */
	public function test_approve_review(): void {
		$review_id = 456;

		$comment                       = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID           = $review_id;
		$comment->comment_post_ID      = 123;
		$comment->comment_type         = ReviewManager::COMMENT_TYPE;
		$comment->user_id              = 1;
		$comment->comment_author       = 'Test User';
		$comment->comment_author_email = 'test@example.com';
		$comment->comment_content      = 'Content';
		$comment->comment_approved     = '0';
		$comment->comment_date         = '2024-01-15 10:30:00';

		Functions\when( 'get_comment' )->justReturn( $comment );
		Functions\when( 'get_comment_meta' )->justReturn( 5 );
		Functions\when( 'wp_set_comment_status' )->justReturn( true );

		$result = $this->manager->approve( $review_id );

		$this->assertTrue( $result );
	}

	/**
	 * Test approve review fires hook.
	 */
	public function test_approve_review_fires_hook(): void {
		$review_id  = 456;
		$hook_fired = false;

		$comment                       = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID           = $review_id;
		$comment->comment_post_ID      = 123;
		$comment->comment_type         = ReviewManager::COMMENT_TYPE;
		$comment->user_id              = 1;
		$comment->comment_author       = 'Test User';
		$comment->comment_author_email = 'test@example.com';
		$comment->comment_content      = 'Content';
		$comment->comment_approved     = '0';
		$comment->comment_date         = '2024-01-15 10:30:00';

		Functions\when( 'get_comment' )->justReturn( $comment );
		Functions\when( 'get_comment_meta' )->justReturn( 5 );
		Functions\when( 'wp_set_comment_status' )->justReturn( true );
		Functions\when( 'do_action' )->alias(
			function( $tag, ...$args ) use ( &$hook_fired, $review_id ) {
				if ( $tag === 'apd_review_approved' && $args[0] === $review_id ) {
					$hook_fired = true;
				}
			}
		);

		$this->manager->approve( $review_id );

		$this->assertTrue( $hook_fired );
	}

	/**
	 * Test approve review with invalid ID.
	 */
	public function test_approve_review_with_invalid_id(): void {
		Functions\when( 'get_comment' )->justReturn( null );

		$result = $this->manager->approve( 999 );

		$this->assertFalse( $result );
	}

	/**
	 * Test reject review.
	 */
	public function test_reject_review(): void {
		$review_id = 456;

		$comment                       = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID           = $review_id;
		$comment->comment_post_ID      = 123;
		$comment->comment_type         = ReviewManager::COMMENT_TYPE;
		$comment->user_id              = 1;
		$comment->comment_author       = 'Test User';
		$comment->comment_author_email = 'test@example.com';
		$comment->comment_content      = 'Content';
		$comment->comment_approved     = '1';
		$comment->comment_date         = '2024-01-15 10:30:00';

		Functions\when( 'get_comment' )->justReturn( $comment );
		Functions\when( 'get_comment_meta' )->justReturn( 5 );
		Functions\when( 'wp_set_comment_status' )->justReturn( true );

		$result = $this->manager->reject( $review_id );

		$this->assertTrue( $result );
	}

	/**
	 * Test reject review fires hook.
	 */
	public function test_reject_review_fires_hook(): void {
		$review_id  = 456;
		$hook_fired = false;

		$comment                       = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID           = $review_id;
		$comment->comment_post_ID      = 123;
		$comment->comment_type         = ReviewManager::COMMENT_TYPE;
		$comment->user_id              = 1;
		$comment->comment_author       = 'Test User';
		$comment->comment_author_email = 'test@example.com';
		$comment->comment_content      = 'Content';
		$comment->comment_approved     = '1';
		$comment->comment_date         = '2024-01-15 10:30:00';

		Functions\when( 'get_comment' )->justReturn( $comment );
		Functions\when( 'get_comment_meta' )->justReturn( 5 );
		Functions\when( 'wp_set_comment_status' )->justReturn( true );
		Functions\when( 'do_action' )->alias(
			function( $tag, ...$args ) use ( &$hook_fired, $review_id ) {
				if ( $tag === 'apd_review_rejected' && $args[0] === $review_id ) {
					$hook_fired = true;
				}
			}
		);

		$this->manager->reject( $review_id );

		$this->assertTrue( $hook_fired );
	}

	/**
	 * Test requires login returns true by default.
	 */
	public function test_requires_login_returns_true_by_default(): void {
		$result = $this->manager->requires_login();

		$this->assertTrue( $result );
	}

	/**
	 * Test requires login can be filtered.
	 */
	public function test_requires_login_can_be_filtered(): void {
		Functions\when( 'apply_filters' )->alias(
			function( $tag, $value ) {
				if ( $tag === 'apd_reviews_require_login' ) {
					return false;
				}
				return $value;
			}
		);

		$result = $this->manager->requires_login();

		$this->assertFalse( $result );
	}

	/**
	 * Test get min content length returns default.
	 */
	public function test_get_min_content_length_returns_default(): void {
		$length = $this->manager->get_min_content_length();

		$this->assertSame( ReviewManager::DEFAULT_MIN_CONTENT_LENGTH, $length );
	}

	/**
	 * Test get min content length can be filtered.
	 */
	public function test_get_min_content_length_can_be_filtered(): void {
		Functions\when( 'apply_filters' )->alias(
			function( $tag, $value ) {
				if ( $tag === 'apd_review_min_content_length' ) {
					return 50;
				}
				return $value;
			}
		);

		$length = $this->manager->get_min_content_length();

		$this->assertSame( 50, $length );
	}

	/**
	 * Test init hooks are registered.
	 */
	public function test_init_registers_hooks(): void {
		$filter_added  = false;
		$action_added = false;

		Functions\when( 'add_filter' )->alias(
			function( $tag, $callback, $priority = 10, $args = 1 ) use ( &$filter_added ) {
				if ( $tag === 'comments_clauses' ) {
					$filter_added = true;
				}
			}
		);

		Functions\when( 'add_action' )->alias(
			function( $tag, $callback, $priority = 10, $args = 1 ) use ( &$action_added ) {
				if ( $tag === 'init' ) {
					$action_added = true;
				}
			}
		);

		$this->manager->init();

		$this->assertTrue( $filter_added );
		$this->assertTrue( $action_added );
	}

	/**
	 * Test exclude reviews from comments filter.
	 */
	public function test_exclude_reviews_from_comments(): void {
		global $wpdb;
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->shouldReceive( 'prepare' )
			->with( ' AND comment_type != %s', ReviewManager::COMMENT_TYPE )
			->andReturn( " AND comment_type != 'apd_review'" );

		$query              = Mockery::mock( \WP_Comment_Query::class );
		$query->query_vars  = [ 'type' => '' ];

		$clauses = [
			'where' => 'comment_approved = 1',
		];

		$result = $this->manager->exclude_reviews_from_comments( $clauses, $query );

		$this->assertStringContainsString( 'comment_type !=', $result['where'] );
	}

	/**
	 * Test exclude reviews does not affect review queries.
	 */
	public function test_exclude_reviews_does_not_affect_review_queries(): void {
		$query             = Mockery::mock( \WP_Comment_Query::class );
		$query->query_vars = [ 'type' => ReviewManager::COMMENT_TYPE ];

		$clauses = [
			'where' => 'comment_approved = 1',
		];

		$result = $this->manager->exclude_reviews_from_comments( $clauses, $query );

		// Should not modify the where clause for review queries.
		$this->assertSame( 'comment_approved = 1', $result['where'] );
	}

	/**
	 * Test review status formatting for pending.
	 */
	public function test_review_status_formatting_pending(): void {
		$comment                   = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID       = 456;
		$comment->comment_post_ID  = 123;
		$comment->comment_type     = ReviewManager::COMMENT_TYPE;
		$comment->user_id          = 1;
		$comment->comment_author   = 'Test User';
		$comment->comment_author_email = 'test@example.com';
		$comment->comment_content  = 'Content';
		$comment->comment_approved = '0'; // Pending.
		$comment->comment_date     = '2024-01-15 10:30:00';

		Functions\when( 'get_comment' )->justReturn( $comment );
		Functions\when( 'get_comment_meta' )->justReturn( 5 );
		Functions\when( 'get_option' )->justReturn( 'F j, Y' );

		$review = $this->manager->get( 456 );

		$this->assertSame( 'pending', $review['status'] );
	}

	/**
	 * Test review status formatting for spam.
	 */
	public function test_review_status_formatting_spam(): void {
		$comment                   = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID       = 456;
		$comment->comment_post_ID  = 123;
		$comment->comment_type     = ReviewManager::COMMENT_TYPE;
		$comment->user_id          = 1;
		$comment->comment_author   = 'Test User';
		$comment->comment_author_email = 'test@example.com';
		$comment->comment_content  = 'Content';
		$comment->comment_approved = 'spam';
		$comment->comment_date     = '2024-01-15 10:30:00';

		Functions\when( 'get_comment' )->justReturn( $comment );
		Functions\when( 'get_comment_meta' )->justReturn( 5 );
		Functions\when( 'get_option' )->justReturn( 'F j, Y' );

		$review = $this->manager->get( 456 );

		$this->assertSame( 'spam', $review['status'] );
	}

	/**
	 * Test review status formatting for trash.
	 */
	public function test_review_status_formatting_trash(): void {
		$comment                   = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID       = 456;
		$comment->comment_post_ID  = 123;
		$comment->comment_type     = ReviewManager::COMMENT_TYPE;
		$comment->user_id          = 1;
		$comment->comment_author   = 'Test User';
		$comment->comment_author_email = 'test@example.com';
		$comment->comment_content  = 'Content';
		$comment->comment_approved = 'trash';
		$comment->comment_date     = '2024-01-15 10:30:00';

		Functions\when( 'get_comment' )->justReturn( $comment );
		Functions\when( 'get_comment_meta' )->justReturn( 5 );
		Functions\when( 'get_option' )->justReturn( 'F j, Y' );

		$review = $this->manager->get( 456 );

		$this->assertSame( 'trash', $review['status'] );
	}

	/**
	 * Test delete review with force delete.
	 */
	public function test_delete_review_with_force_delete(): void {
		$review_id  = 456;
		$force_used = null;

		$comment                       = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID           = $review_id;
		$comment->comment_post_ID      = 123;
		$comment->comment_type         = ReviewManager::COMMENT_TYPE;
		$comment->user_id              = 1;
		$comment->comment_author       = 'Test User';
		$comment->comment_author_email = 'test@example.com';
		$comment->comment_content      = 'Content';
		$comment->comment_approved     = '1';
		$comment->comment_date         = '2024-01-15 10:30:00';

		Functions\when( 'get_comment' )->justReturn( $comment );
		Functions\when( 'get_comment_meta' )->justReturn( 5 );
		Functions\when( 'wp_delete_comment' )->alias(
			function( $id, $force ) use ( &$force_used ) {
				$force_used = $force;
				return true;
			}
		);

		$this->manager->delete( $review_id, true );

		$this->assertTrue( $force_used );
	}
}
