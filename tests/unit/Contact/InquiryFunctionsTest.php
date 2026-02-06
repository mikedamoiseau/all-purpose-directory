<?php
/**
 * Inquiry helper functions unit tests.
 *
 * Tests the inquiry helper functions by verifying they delegate
 * to the underlying InquiryTracker class.
 *
 * @package All_Purpose_Directory
 */

namespace APD\Tests\Unit\Contact;

use APD\Contact\InquiryTracker;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test case for inquiry helper functions.
 *
 * Note: These tests verify the InquiryTracker class works as expected
 * when called the way the helper functions use them. The actual
 * functions.php file is tested via integration tests.
 */
class InquiryFunctionsTest extends TestCase {

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Reset singleton.
		$reflection = new \ReflectionClass( InquiryTracker::class );
		$instance   = $reflection->getProperty( 'instance' );
		@$instance->setValue( null, null );
	}

	/**
	 * Tear down the test environment.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test InquiryTracker singleton for apd_inquiry_tracker().
	 */
	public function test_inquiry_tracker_singleton_behavior(): void {
		$tracker1 = InquiryTracker::get_instance();
		$tracker2 = InquiryTracker::get_instance();

		$this->assertSame( $tracker1, $tracker2 );
		$this->assertInstanceOf( InquiryTracker::class, $tracker1 );
	}

	/**
	 * Test get_listing_inquiry_count for apd_get_listing_inquiry_count().
	 */
	public function test_get_listing_inquiry_count(): void {
		Functions\when( 'get_post_meta' )->justReturn( 5 );

		$tracker = InquiryTracker::get_instance();
		$count   = $tracker->get_listing_inquiry_count( 123 );

		$this->assertEquals( 5, $count );
	}

	/**
	 * Test count_user_inquiries method exists.
	 */
	public function test_count_user_inquiries_method_exists(): void {
		$this->assertTrue( method_exists( InquiryTracker::class, 'count_user_inquiries' ) );
	}

	/**
	 * Test mark_as_read returns true for valid inquiry.
	 */
	public function test_mark_as_read_success(): void {
		$post            = Mockery::mock( 'WP_Post' );
		$post->post_type = InquiryTracker::POST_TYPE;

		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'do_action' )->justReturn( null );

		$tracker = InquiryTracker::get_instance();
		$result  = $tracker->mark_as_read( 123 );

		$this->assertTrue( $result );
	}

	/**
	 * Test mark_as_read returns false for non-inquiry post.
	 */
	public function test_mark_as_read_fails_for_wrong_post_type(): void {
		$post            = Mockery::mock( 'WP_Post' );
		$post->post_type = 'post';

		Functions\when( 'get_post' )->justReturn( $post );

		$tracker = InquiryTracker::get_instance();
		$result  = $tracker->mark_as_read( 123 );

		$this->assertFalse( $result );
	}

	/**
	 * Test mark_as_unread success.
	 */
	public function test_mark_as_unread_success(): void {
		$post            = Mockery::mock( 'WP_Post' );
		$post->post_type = InquiryTracker::POST_TYPE;

		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'do_action' )->justReturn( null );

		$tracker = InquiryTracker::get_instance();
		$result  = $tracker->mark_as_unread( 123 );

		$this->assertTrue( $result );
	}

	/**
	 * Test can_user_view for admin.
	 */
	public function test_can_user_view_admin_can_view_all(): void {
		$post            = Mockery::mock( 'WP_Post' );
		$post->post_type = InquiryTracker::POST_TYPE;
		$post->post_author = 2;

		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'user_can' )->alias( function( $user_id, $cap ) {
			return $cap === 'manage_options';
		} );

		$tracker = InquiryTracker::get_instance();
		$result  = $tracker->can_user_view( 123, 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test can_user_view for inquiry author (listing owner).
	 */
	public function test_can_user_view_listing_owner_can_view(): void {
		$post              = Mockery::mock( 'WP_Post' );
		$post->post_type   = InquiryTracker::POST_TYPE;
		$post->post_author = 5;

		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'user_can' )->justReturn( false );
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$tracker = InquiryTracker::get_instance();
		$result  = $tracker->can_user_view( 123, 5 );

		$this->assertTrue( $result );
	}

	/**
	 * Test can_user_view returns false for other users.
	 */
	public function test_can_user_view_other_user_cannot_view(): void {
		$post              = Mockery::mock( 'WP_Post' );
		$post->post_type   = InquiryTracker::POST_TYPE;
		$post->post_author = 5;

		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'user_can' )->justReturn( false );
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$tracker = InquiryTracker::get_instance();
		$result  = $tracker->can_user_view( 123, 99 );

		$this->assertFalse( $result );
	}

	/**
	 * Test increment_listing_count increments count.
	 */
	public function test_increment_listing_count(): void {
		Functions\when( 'get_post_meta' )->justReturn( 5 );
		Functions\when( 'update_post_meta' )->justReturn( true );

		$tracker = InquiryTracker::get_instance();
		$count   = $tracker->increment_listing_count( 123 );

		$this->assertEquals( 6, $count );
	}

	/**
	 * Test decrement_listing_count decrements count.
	 */
	public function test_decrement_listing_count(): void {
		Functions\when( 'get_post_meta' )->justReturn( 5 );
		Functions\when( 'update_post_meta' )->justReturn( true );

		$tracker = InquiryTracker::get_instance();
		$count   = $tracker->decrement_listing_count( 123 );

		$this->assertEquals( 4, $count );
	}

	/**
	 * Test decrement_listing_count does not go below zero.
	 */
	public function test_decrement_listing_count_not_below_zero(): void {
		Functions\when( 'get_post_meta' )->justReturn( 0 );
		Functions\when( 'update_post_meta' )->justReturn( true );

		$tracker = InquiryTracker::get_instance();
		$count   = $tracker->decrement_listing_count( 123 );

		$this->assertEquals( 0, $count );
	}
}
