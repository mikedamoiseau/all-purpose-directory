<?php
/**
 * Tests for EmailManager notification handler methods.
 *
 * Covers the on_listing_submitted, on_listing_status_changed, and
 * edge cases not covered by existing EmailManagerTest.
 *
 * @package APD\Tests\Unit\Email
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Email;

use APD\Email\EmailManager;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Tests for notification handler methods.
 */
class EmailNotificationHandlersTest extends TestCase {

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\stubs( [
			'get_bloginfo'  => function ( $show ) {
				return $show === 'name' ? 'Test Site' : 'https://example.com';
			},
			'home_url'      => 'https://example.com',
			'get_option'    => function ( $option ) {
				if ( $option === 'admin_email' ) {
					return 'admin@example.com';
				}
				return '';
			},
			'wp_date'       => function ( $format ) {
				return gmdate( $format );
			},
			'esc_html'      => function ( $text ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
			},
			'esc_url'       => function ( $url ) {
				return $url;
			},
			'esc_html__'    => function ( $text ) {
				return $text;
			},
			'__'            => function ( $text ) {
				return $text;
			},
			'apply_filters' => function ( $hook, $value, ...$args ) {
				return $value;
			},
			'do_action'     => function ( $hook, ...$args ) {},
			'add_action'    => function ( $hook, $callback, $priority = 10, $accepted_args = 1 ) {},
		] );
	}

	/**
	 * Tear down test environment.
	 */
	protected function tearDown(): void {
		EmailManager::reset_instance();
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Helper: Create mock listing and user for email tests.
	 *
	 * @param string $status Post status.
	 * @return void
	 */
	private function setup_listing_mocks( string $status = 'publish' ): void {
		$listing               = new \stdClass();
		$listing->ID           = 123;
		$listing->post_title   = 'Test Listing';
		$listing->post_author  = 1;
		$listing->post_status  = $status;
		$listing->post_type    = 'apd_listing';

		$user               = new \stdClass();
		$user->display_name = 'Test Author';
		$user->user_email   = 'author@example.com';

		Functions\expect( 'get_post' )->once()->andReturn( $listing );
		Functions\expect( 'get_userdata' )->once()->andReturn( $user );
		Functions\expect( 'get_permalink' )->once()->andReturn( 'https://example.com/listing/test/' );
		Functions\expect( 'get_edit_post_link' )->once()->andReturn( 'https://example.com/wp-admin/post.php?post=123' );
		Functions\expect( 'admin_url' )->once()->andReturn( 'https://example.com/wp-admin/' );
	}

	/**
	 * Test on_listing_status_changed handles rejection and sends email.
	 */
	public function test_status_changed_handles_rejection(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );

		$this->setup_listing_mocks( 'rejected' );
		Functions\expect( 'wp_mail' )->once()->andReturn( true );

		$manager->on_listing_status_changed( 123, 'rejected', 'pending' );

		$this->assertTrue( true );
	}

	/**
	 * Test on_listing_status_changed skips approval email when disabled.
	 */
	public function test_status_changed_skips_approval_when_disabled(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );
		$manager->set_notification_enabled( 'listing_approved', false );

		Functions\expect( 'wp_mail' )->never();

		$manager->on_listing_status_changed( 123, 'publish', 'pending' );

		$this->assertTrue( true );
	}

	/**
	 * Test on_listing_status_changed skips rejection email when disabled.
	 */
	public function test_status_changed_skips_rejection_when_disabled(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );
		$manager->set_notification_enabled( 'listing_rejected', false );

		Functions\expect( 'wp_mail' )->never();

		$manager->on_listing_status_changed( 123, 'rejected', 'pending' );

		$this->assertTrue( true );
	}

	/**
	 * Test on_listing_status_changed skips expired email when disabled.
	 */
	public function test_status_changed_skips_expired_when_disabled(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );
		$manager->set_notification_enabled( 'listing_expired', false );

		Functions\expect( 'wp_mail' )->never();

		$manager->on_listing_status_changed( 123, 'expired', 'publish' );

		$this->assertTrue( true );
	}

	/**
	 * Test on_listing_status_changed does not send approval when going from publish to publish.
	 */
	public function test_status_changed_no_approval_for_publish_to_publish(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );

		// This case should never happen (filtered out by Plugin::handle_listing_status_transition)
		// but the handler itself also guards against it.
		Functions\expect( 'wp_mail' )->never();

		$manager->on_listing_status_changed( 123, 'publish', 'publish' );

		$this->assertTrue( true );
	}

	/**
	 * Test on_listing_status_changed does not send expired when already expired.
	 */
	public function test_status_changed_no_expired_for_expired_to_expired(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );

		Functions\expect( 'wp_mail' )->never();

		$manager->on_listing_status_changed( 123, 'expired', 'expired' );

		$this->assertTrue( true );
	}

	/**
	 * Test on_listing_status_changed sends no email for irrelevant transitions.
	 */
	public function test_status_changed_no_email_for_draft_to_pending(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );

		Functions\expect( 'wp_mail' )->never();

		$manager->on_listing_status_changed( 123, 'pending', 'draft' );

		$this->assertTrue( true );
	}

	/**
	 * Test on_listing_submitted sends email when enabled.
	 */
	public function test_listing_submitted_sends_when_enabled(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );
		$manager->set_notification_enabled( 'listing_submitted', true );

		$this->setup_listing_mocks( 'pending' );
		Functions\expect( 'wp_mail' )->once()->andReturn( true );

		$manager->on_listing_submitted( 123, [ 'title' => 'Test' ] );

		$this->assertTrue( true );
	}

	/**
	 * Test on_listing_submitted skips when disabled.
	 */
	public function test_listing_submitted_skips_when_disabled(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );
		$manager->set_notification_enabled( 'listing_submitted', false );

		Functions\expect( 'wp_mail' )->never();

		$manager->on_listing_submitted( 123, [ 'title' => 'Test' ] );

		$this->assertTrue( true );
	}

	/**
	 * Test that all notification types can be individually toggled.
	 */
	public function test_all_notification_types_toggleable(): void {
		$manager = EmailManager::get_instance();

		$types = [
			'listing_submitted',
			'listing_approved',
			'listing_rejected',
			'listing_expiring',
			'listing_expired',
			'new_review',
			'new_inquiry',
		];

		foreach ( $types as $type ) {
			// Should be enabled by default.
			$this->assertTrue(
				$manager->is_notification_enabled( $type ),
				"$type should be enabled by default"
			);

			// Disable and verify.
			$manager->set_notification_enabled( $type, false );
			$this->assertFalse(
				$manager->is_notification_enabled( $type ),
				"$type should be disabled after set_notification_enabled(false)"
			);

			// Re-enable and verify.
			$manager->set_notification_enabled( $type, true );
			$this->assertTrue(
				$manager->is_notification_enabled( $type ),
				"$type should be enabled after set_notification_enabled(true)"
			);
		}
	}
}
