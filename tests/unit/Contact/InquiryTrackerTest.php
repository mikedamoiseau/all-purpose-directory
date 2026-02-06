<?php
/**
 * InquiryTracker unit tests.
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
 * Test case for InquiryTracker class.
 */
class InquiryTrackerTest extends TestCase {

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Reset singleton.
		$reflection = new \ReflectionClass( InquiryTracker::class );
		$instance = $reflection->getProperty( 'instance' );
		@$instance->setValue( null, null );

		// Mock translation functions.
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( '_x' )->returnArg( 1 );
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
	 * Test singleton pattern.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = InquiryTracker::get_instance();
		$instance2 = InquiryTracker::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test get_instance returns InquiryTracker.
	 */
	public function test_get_instance_returns_inquiry_tracker(): void {
		$instance = InquiryTracker::get_instance();
		$this->assertInstanceOf( InquiryTracker::class, $instance );
	}

	/**
	 * Test constants are defined.
	 */
	public function test_constants_defined(): void {
		$this->assertEquals( 'apd_inquiry', InquiryTracker::POST_TYPE );
		$this->assertEquals( '_apd_inquiry_listing_id', InquiryTracker::META_LISTING_ID );
		$this->assertEquals( '_apd_inquiry_sender_name', InquiryTracker::META_SENDER_NAME );
		$this->assertEquals( '_apd_inquiry_sender_email', InquiryTracker::META_SENDER_EMAIL );
		$this->assertEquals( '_apd_inquiry_sender_phone', InquiryTracker::META_SENDER_PHONE );
		$this->assertEquals( '_apd_inquiry_subject', InquiryTracker::META_SUBJECT );
		$this->assertEquals( '_apd_inquiry_read', InquiryTracker::META_READ );
		$this->assertEquals( '_apd_inquiry_count', InquiryTracker::LISTING_INQUIRY_COUNT );
	}

	/**
	 * Test init registers hooks.
	 */
	public function test_init_registers_hooks(): void {
		$actions = [];

		Functions\when( 'add_action' )->alias( function( $hook, $callback, $priority = 10 ) use ( &$actions ) {
			$actions[] = [ 'hook' => $hook, 'priority' => $priority ];
		} );

		Functions\when( 'do_action' )->justReturn( null );

		$tracker = InquiryTracker::get_instance();
		$tracker->init();

		$hooks = array_column( $actions, 'hook' );
		$this->assertContains( 'init', $hooks );
		$this->assertContains( 'apd_contact_sent', $hooks );
	}

	/**
	 * Test register_post_type registers inquiry type.
	 */
	public function test_register_post_type(): void {
		$registered_type = null;

		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );
		Functions\when( 'register_post_type' )->alias( function( $type, $args ) use ( &$registered_type ) {
			$registered_type = $type;
		} );

		$tracker = InquiryTracker::get_instance();
		$tracker->register_post_type();

		$this->assertEquals( 'apd_inquiry', $registered_type );
	}

	/**
	 * Test save_inquiry returns false for invalid listing_id.
	 */
	public function test_save_inquiry_fails_invalid_listing(): void {
		$tracker = InquiryTracker::get_instance();
		$result = $tracker->save_inquiry( [ 'listing_id' => 0 ] );

		$this->assertFalse( $result );
	}

	/**
	 * Test save_inquiry returns false when listing not found.
	 */
	public function test_save_inquiry_fails_listing_not_found(): void {
		Functions\when( 'get_post' )->justReturn( null );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->save_inquiry( [ 'listing_id' => 123 ] );

		$this->assertFalse( $result );
	}

	/**
	 * Test save_inquiry creates inquiry post.
	 */
	public function test_save_inquiry_creates_post(): void {
		$listing = Mockery::mock( 'WP_Post' );
		$listing->ID = 123;
		$listing->post_title = 'Test Listing';
		$listing->post_author = 1;

		Functions\when( 'get_post' )->justReturn( $listing );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'sanitize_email' )->returnArg( 1 );
		Functions\when( 'sanitize_textarea_field' )->returnArg( 1 );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );
		// Return int (success) - is_wp_error() from bootstrap will return false.
		Functions\when( 'wp_insert_post' )->justReturn( 456 );
		Functions\when( 'update_post_meta' )->justReturn( true );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->save_inquiry( [
			'listing_id'   => 123,
			'sender_name'  => 'John Doe',
			'sender_email' => 'john@example.com',
			'message'      => 'Test message',
		] );

		$this->assertEquals( 456, $result );
	}

	/**
	 * Test save_inquiry returns false on wp_insert_post error.
	 */
	public function test_save_inquiry_fails_on_insert_error(): void {
		$listing = Mockery::mock( 'WP_Post' );
		$listing->ID = 123;
		$listing->post_title = 'Test Listing';
		$listing->post_author = 1;

		// Use real WP_Error from bootstrap - is_wp_error() will return true.
		$error = new \WP_Error( 'insert_failed', 'Failed to insert post' );

		Functions\when( 'get_post' )->justReturn( $listing );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'sanitize_email' )->returnArg( 1 );
		Functions\when( 'sanitize_textarea_field' )->returnArg( 1 );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );
		Functions\when( 'wp_insert_post' )->justReturn( $error );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->save_inquiry( [
			'listing_id'   => 123,
			'sender_name'  => 'John Doe',
			'sender_email' => 'john@example.com',
			'message'      => 'Test message',
		] );

		$this->assertFalse( $result );
	}

	/**
	 * Test get_inquiry returns null for invalid ID.
	 */
	public function test_get_inquiry_returns_null_invalid(): void {
		Functions\when( 'get_post' )->justReturn( null );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->get_inquiry( 0 );

		$this->assertNull( $result );
	}

	/**
	 * Test get_inquiry returns null for wrong post type.
	 */
	public function test_get_inquiry_returns_null_wrong_type(): void {
		$post = Mockery::mock( 'WP_Post' );
		$post->post_type = 'post';

		Functions\when( 'get_post' )->justReturn( $post );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->get_inquiry( 123 );

		$this->assertNull( $result );
	}

	/**
	 * Test get_inquiry returns formatted data.
	 */
	public function test_get_inquiry_returns_data(): void {
		$inquiry = Mockery::mock( 'WP_Post' );
		$inquiry->ID = 456;
		$inquiry->post_type = 'apd_inquiry';
		$inquiry->post_content = 'Test message';
		$inquiry->post_date = '2024-01-15 10:30:00';

		$listing = Mockery::mock( 'WP_Post' );
		$listing->ID = 123;
		$listing->post_title = 'Test Listing';

		$call_count = 0;
		Functions\when( 'get_post' )->alias( function( $id ) use ( $inquiry, $listing, &$call_count ) {
			$call_count++;
			if ( $call_count === 1 ) {
				return $inquiry;
			}
			return $listing;
		} );

		Functions\when( 'get_post_meta' )->alias( function( $id, $key, $single ) {
			$meta = [
				'_apd_inquiry_listing_id'   => 123,
				'_apd_inquiry_sender_name'  => 'John Doe',
				'_apd_inquiry_sender_email' => 'john@example.com',
				'_apd_inquiry_sender_phone' => '555-1234',
				'_apd_inquiry_subject'      => 'Test Subject',
				'_apd_inquiry_read'         => 0,
			];
			return $meta[ $key ] ?? '';
		} );

		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/listing/123' );
		Functions\when( 'get_option' )->justReturn( 'F j, Y' );
		Functions\when( 'date_i18n' )->justReturn( 'January 15, 2024' );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->get_inquiry( 456 );

		$this->assertIsArray( $result );
		$this->assertEquals( 456, $result['id'] );
		$this->assertEquals( 123, $result['listing_id'] );
		$this->assertEquals( 'Test Listing', $result['listing_title'] );
		$this->assertEquals( 'John Doe', $result['sender_name'] );
		$this->assertEquals( 'john@example.com', $result['sender_email'] );
		$this->assertEquals( 'Test message', $result['message'] );
		$this->assertFalse( $result['is_read'] );
	}

	/**
	 * Test mark_as_read returns false for invalid inquiry.
	 */
	public function test_mark_as_read_fails_invalid(): void {
		Functions\when( 'get_post' )->justReturn( null );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->mark_as_read( 0 );

		$this->assertFalse( $result );
	}

	/**
	 * Test mark_as_read returns false for wrong post type.
	 */
	public function test_mark_as_read_fails_wrong_type(): void {
		$post = Mockery::mock( 'WP_Post' );
		$post->post_type = 'post';

		Functions\when( 'get_post' )->justReturn( $post );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->mark_as_read( 123 );

		$this->assertFalse( $result );
	}

	/**
	 * Test mark_as_read updates meta.
	 */
	public function test_mark_as_read_updates_meta(): void {
		$post = Mockery::mock( 'WP_Post' );
		$post->post_type = 'apd_inquiry';

		$meta_updated = false;

		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'update_post_meta' )->alias( function( $id, $key, $value ) use ( &$meta_updated ) {
			if ( $key === '_apd_inquiry_read' && $value === 1 ) {
				$meta_updated = true;
			}
		} );
		Functions\when( 'do_action' )->justReturn( null );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->mark_as_read( 456 );

		$this->assertTrue( $result );
		$this->assertTrue( $meta_updated );
	}

	/**
	 * Test mark_as_unread updates meta.
	 */
	public function test_mark_as_unread_updates_meta(): void {
		$post = Mockery::mock( 'WP_Post' );
		$post->post_type = 'apd_inquiry';

		$meta_updated = false;

		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'update_post_meta' )->alias( function( $id, $key, $value ) use ( &$meta_updated ) {
			if ( $key === '_apd_inquiry_read' && $value === 0 ) {
				$meta_updated = true;
			}
		} );
		Functions\when( 'do_action' )->justReturn( null );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->mark_as_unread( 456 );

		$this->assertTrue( $result );
		$this->assertTrue( $meta_updated );
	}

	/**
	 * Test delete_inquiry returns false for invalid inquiry.
	 */
	public function test_delete_inquiry_fails_invalid(): void {
		Functions\when( 'get_post' )->justReturn( null );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->delete_inquiry( 0 );

		$this->assertFalse( $result );
	}

	/**
	 * Test delete_inquiry returns false for wrong post type.
	 */
	public function test_delete_inquiry_fails_wrong_type(): void {
		$post = Mockery::mock( 'WP_Post' );
		$post->post_type = 'post';

		Functions\when( 'get_post' )->justReturn( $post );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->delete_inquiry( 123 );

		$this->assertFalse( $result );
	}

	/**
	 * Test delete_inquiry calls wp_delete_post.
	 */
	public function test_delete_inquiry_deletes_post(): void {
		$post = Mockery::mock( 'WP_Post' );
		$post->post_type = 'apd_inquiry';

		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'get_post_meta' )->justReturn( 123 );
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'wp_delete_post' )->justReturn( $post );
		Functions\when( 'update_post_meta' )->justReturn( true );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->delete_inquiry( 456, true );

		$this->assertTrue( $result );
	}

	/**
	 * Test get_listing_inquiry_count returns count.
	 */
	public function test_get_listing_inquiry_count(): void {
		Functions\when( 'get_post_meta' )->justReturn( 5 );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->get_listing_inquiry_count( 123 );

		$this->assertEquals( 5, $result );
	}

	/**
	 * Test get_listing_inquiry_count returns 0 for empty.
	 */
	public function test_get_listing_inquiry_count_empty(): void {
		Functions\when( 'get_post_meta' )->justReturn( '' );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->get_listing_inquiry_count( 123 );

		$this->assertEquals( 0, $result );
	}

	/**
	 * Test increment_listing_count increases count.
	 */
	public function test_increment_listing_count(): void {
		$new_count = null;

		Functions\when( 'get_post_meta' )->justReturn( 5 );
		Functions\when( 'update_post_meta' )->alias( function( $id, $key, $value ) use ( &$new_count ) {
			$new_count = $value;
		} );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->increment_listing_count( 123 );

		$this->assertEquals( 6, $result );
		$this->assertEquals( 6, $new_count );
	}

	/**
	 * Test decrement_listing_count decreases count.
	 */
	public function test_decrement_listing_count(): void {
		$new_count = null;

		Functions\when( 'get_post_meta' )->justReturn( 5 );
		Functions\when( 'update_post_meta' )->alias( function( $id, $key, $value ) use ( &$new_count ) {
			$new_count = $value;
		} );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->decrement_listing_count( 123 );

		$this->assertEquals( 4, $result );
		$this->assertEquals( 4, $new_count );
	}

	/**
	 * Test decrement_listing_count does not go below zero.
	 */
	public function test_decrement_listing_count_min_zero(): void {
		Functions\when( 'get_post_meta' )->justReturn( 0 );
		Functions\when( 'update_post_meta' )->justReturn( true );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->decrement_listing_count( 123 );

		$this->assertEquals( 0, $result );
	}

	/**
	 * Test can_user_view returns false for invalid inquiry.
	 */
	public function test_can_user_view_fails_invalid(): void {
		Functions\when( 'get_post' )->justReturn( null );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->can_user_view( 0, 1 );

		$this->assertFalse( $result );
	}

	/**
	 * Test can_user_view returns true for admin.
	 */
	public function test_can_user_view_admin(): void {
		$post = Mockery::mock( 'WP_Post' );
		$post->post_type = 'apd_inquiry';
		$post->post_author = 2;

		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'user_can' )->alias( function( $user_id, $cap ) {
			return $cap === 'manage_options';
		} );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->can_user_view( 456, 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test can_user_view returns true for owner.
	 */
	public function test_can_user_view_owner(): void {
		$post = Mockery::mock( 'WP_Post' );
		$post->post_type = 'apd_inquiry';
		$post->post_author = 5;

		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'user_can' )->justReturn( false );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->can_user_view( 456, 5 );

		$this->assertTrue( $result );
	}

	/**
	 * Test can_user_view returns false for non-owner.
	 */
	public function test_can_user_view_non_owner(): void {
		$post = Mockery::mock( 'WP_Post' );
		$post->post_type = 'apd_inquiry';
		$post->post_author = 5;

		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'user_can' )->justReturn( false );
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->can_user_view( 456, 10 );

		$this->assertFalse( $result );
	}

	/**
	 * Test log_inquiry respects filter.
	 */
	public function test_log_inquiry_respects_filter(): void {
		$listing = Mockery::mock( 'WP_Post' );
		$listing->ID = 123;

		$owner = Mockery::mock( 'WP_User' );

		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			if ( $hook === 'apd_track_inquiry' ) {
				return false;
			}
			return $value;
		} );

		$tracker = InquiryTracker::get_instance();
		$result = $tracker->log_inquiry( [], $listing, $owner );

		$this->assertFalse( $result );
	}
}
