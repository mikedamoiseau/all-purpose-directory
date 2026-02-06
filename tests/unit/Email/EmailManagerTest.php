<?php
/**
 * EmailManager unit tests.
 *
 * @package APD\Tests\Unit\Email
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Email;

use APD\Email\EmailManager;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test case for EmailManager.
 */
class EmailManagerTest extends TestCase {

	/**
	 * EmailManager instance.
	 *
	 * @var EmailManager
	 */
	private EmailManager $manager;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Common WordPress function mocks.
		Functions\stubs( [
			'get_bloginfo'    => function ( $show ) {
				return $show === 'name' ? 'Test Site' : 'https://example.com';
			},
			'home_url'        => 'https://example.com',
			'get_option'      => function ( $option ) {
				if ( $option === 'admin_email' ) {
					return 'admin@example.com';
				}
				if ( $option === 'date_format' ) {
					return 'F j, Y';
				}
				if ( $option === 'time_format' ) {
					return 'g:i a';
				}
				return '';
			},
			'wp_date'         => function ( $format ) {
				return gmdate( $format );
			},
			'esc_html'        => function ( $text ) {
				return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
			},
			'esc_url'         => function ( $url ) {
				return $url;
			},
			'esc_html__'      => function ( $text ) {
				return $text;
			},
			'__'              => function ( $text ) {
				return $text;
			},
			'apply_filters'   => function ( $hook, $value, ...$args ) {
				return $value;
			},
			'do_action'       => function ( $hook, ...$args ) {},
			'add_action'      => function ( $hook, $callback, $priority = 10, $accepted_args = 1 ) {},
		] );

		// Create new instance for each test.
		$this->manager = EmailManager::get_instance();
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
	 * Test singleton instance.
	 */
	public function test_get_instance_returns_same_instance(): void {
		$instance1 = EmailManager::get_instance();
		$instance2 = EmailManager::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test constructor accepts config.
	 */
	public function test_constructor_accepts_config(): void {
		$config  = [
			'from_name'  => 'Custom Name',
			'from_email' => 'custom@example.com',
		];
		$manager = EmailManager::get_instance( $config );

		$this->assertEquals( 'Custom Name', $manager->get_config( 'from_name' ) );
		$this->assertEquals( 'custom@example.com', $manager->get_config( 'from_email' ) );
	}

	/**
	 * Test default config values.
	 */
	public function test_default_config_values(): void {
		$this->assertEquals( '', $this->manager->get_config( 'from_name' ) );
		$this->assertEquals( '', $this->manager->get_config( 'from_email' ) );
		$this->assertEquals( '', $this->manager->get_config( 'admin_email' ) );
		$this->assertEquals( 'text/html', $this->manager->get_config( 'content_type' ) );
		$this->assertEquals( 'UTF-8', $this->manager->get_config( 'charset' ) );
		$this->assertTrue( $this->manager->get_config( 'enable_html' ) );
		$this->assertTrue( $this->manager->get_config( 'use_templates' ) );
	}

	/**
	 * Test set_config merges values.
	 */
	public function test_set_config_merges_values(): void {
		$this->manager->set_config( [ 'from_name' => 'New Name' ] );

		$this->assertEquals( 'New Name', $this->manager->get_config( 'from_name' ) );
		$this->assertEquals( 'text/html', $this->manager->get_config( 'content_type' ) ); // Unchanged.
	}

	/**
	 * Test register placeholder.
	 */
	public function test_register_placeholder(): void {
		$this->manager->register_placeholder( 'test_placeholder', fn() => 'test_value' );

		$placeholders = $this->manager->get_placeholders();
		$this->assertArrayHasKey( 'test_placeholder', $placeholders );
	}

	/**
	 * Test unregister placeholder.
	 */
	public function test_unregister_placeholder(): void {
		$this->manager->register_placeholder( 'test_placeholder', fn() => 'test_value' );
		$this->manager->unregister_placeholder( 'test_placeholder' );

		$placeholders = $this->manager->get_placeholders();
		$this->assertArrayNotHasKey( 'test_placeholder', $placeholders );
	}

	/**
	 * Test default placeholders are registered.
	 */
	public function test_default_placeholders_registered(): void {
		$placeholders = $this->manager->get_placeholders();

		$this->assertArrayHasKey( 'site_name', $placeholders );
		$this->assertArrayHasKey( 'site_url', $placeholders );
		$this->assertArrayHasKey( 'admin_email', $placeholders );
		$this->assertArrayHasKey( 'current_date', $placeholders );
		$this->assertArrayHasKey( 'current_time', $placeholders );
	}

	/**
	 * Test replace placeholders with context.
	 */
	public function test_replace_placeholders_with_context(): void {
		$text   = 'Hello {name}, welcome to {site_name}!';
		$result = $this->manager->replace_placeholders( $text, [ 'name' => 'John' ] );

		$this->assertEquals( 'Hello John, welcome to Test Site!', $result );
	}

	/**
	 * Test replace placeholders with registered callbacks.
	 */
	public function test_replace_placeholders_with_callbacks(): void {
		$text   = 'Site: {site_name}, URL: {site_url}';
		$result = $this->manager->replace_placeholders( $text );

		$this->assertEquals( 'Site: Test Site, URL: https://example.com', $result );
	}

	/**
	 * Test replace placeholders ignores non-scalar context.
	 */
	public function test_replace_placeholders_ignores_non_scalar(): void {
		$text   = 'Hello {name}!';
		$result = $this->manager->replace_placeholders( $text, [ 'name' => [ 'array' ] ] );

		$this->assertEquals( 'Hello {name}!', $result );
	}

	/**
	 * Test replace placeholders context overrides callbacks.
	 */
	public function test_replace_placeholders_context_overrides_callbacks(): void {
		$text   = 'Site: {site_name}';
		$result = $this->manager->replace_placeholders( $text, [ 'site_name' => 'Custom Site' ] );

		$this->assertEquals( 'Site: Custom Site', $result );
	}

	/**
	 * Test get_from_name with config.
	 */
	public function test_get_from_name_with_config(): void {
		$this->manager->set_config( [ 'from_name' => 'Custom Sender' ] );

		$this->assertEquals( 'Custom Sender', $this->manager->get_from_name() );
	}

	/**
	 * Test get_from_name falls back to site name.
	 */
	public function test_get_from_name_falls_back_to_site_name(): void {
		$this->assertEquals( 'Test Site', $this->manager->get_from_name() );
	}

	/**
	 * Test get_from_email with config.
	 */
	public function test_get_from_email_with_config(): void {
		$this->manager->set_config( [ 'from_email' => 'sender@example.com' ] );

		$this->assertEquals( 'sender@example.com', $this->manager->get_from_email() );
	}

	/**
	 * Test get_from_email falls back to admin_email.
	 */
	public function test_get_from_email_falls_back_to_admin(): void {
		$this->assertEquals( 'admin@example.com', $this->manager->get_from_email() );
	}

	/**
	 * Test get_admin_email with config.
	 */
	public function test_get_admin_email_with_config(): void {
		$this->manager->set_config( [ 'admin_email' => 'custom-admin@example.com' ] );

		$this->assertEquals( 'custom-admin@example.com', $this->manager->get_admin_email() );
	}

	/**
	 * Test get_admin_email falls back to option.
	 */
	public function test_get_admin_email_falls_back_to_option(): void {
		$this->assertEquals( 'admin@example.com', $this->manager->get_admin_email() );
	}

	/**
	 * Test get_default_headers with HTML enabled.
	 */
	public function test_get_default_headers_with_html(): void {
		$headers = $this->manager->get_default_headers();

		$this->assertContains( 'Content-Type: text/html; charset=UTF-8', $headers );
		$this->assertContains( 'From: Test Site <admin@example.com>', $headers );
	}

	/**
	 * Test get_default_headers without HTML.
	 */
	public function test_get_default_headers_without_html(): void {
		$this->manager->set_config( [ 'enable_html' => false ] );
		$headers = $this->manager->get_default_headers();

		// Should not include Content-Type header.
		$has_content_type = false;
		foreach ( $headers as $header ) {
			if ( strpos( $header, 'Content-Type:' ) !== false ) {
				$has_content_type = true;
				break;
			}
		}

		$this->assertFalse( $has_content_type );
	}

	/**
	 * Test is_notification_enabled returns default values.
	 */
	public function test_is_notification_enabled_returns_defaults(): void {
		$this->assertTrue( $this->manager->is_notification_enabled( 'listing_submitted' ) );
		$this->assertTrue( $this->manager->is_notification_enabled( 'listing_approved' ) );
		$this->assertTrue( $this->manager->is_notification_enabled( 'listing_rejected' ) );
		$this->assertTrue( $this->manager->is_notification_enabled( 'listing_expiring' ) );
		$this->assertTrue( $this->manager->is_notification_enabled( 'listing_expired' ) );
		$this->assertTrue( $this->manager->is_notification_enabled( 'new_review' ) );
		$this->assertTrue( $this->manager->is_notification_enabled( 'new_inquiry' ) );
	}

	/**
	 * Test is_notification_enabled returns false for unknown.
	 */
	public function test_is_notification_enabled_returns_false_for_unknown(): void {
		$this->assertFalse( $this->manager->is_notification_enabled( 'unknown_type' ) );
	}

	/**
	 * Test set_notification_enabled.
	 */
	public function test_set_notification_enabled(): void {
		$this->manager->set_notification_enabled( 'listing_submitted', false );

		$this->assertFalse( $this->manager->is_notification_enabled( 'listing_submitted' ) );
	}

	/**
	 * Test send calls wp_mail with correct arguments.
	 */
	public function test_send_calls_wp_mail(): void {
		Functions\expect( 'wp_mail' )
			->once()
			->with(
				'test@example.com',
				'Test Subject from Test Site',
				Mockery::type( 'string' ),
				Mockery::type( 'array' ),
				[]
			)
			->andReturn( true );

		$result = $this->manager->send(
			'test@example.com',
			'Test Subject from {site_name}',
			'Test message',
			[],
			[]
		);

		$this->assertTrue( $result );
	}

	/**
	 * Test send replaces placeholders in subject.
	 */
	public function test_send_replaces_placeholders_in_subject(): void {
		$subject_received = null;

		Functions\expect( 'wp_mail' )
			->once()
			->andReturnUsing( function ( $to, $subject, $message, $headers, $attachments ) use ( &$subject_received ) {
				$subject_received = $subject;
				return true;
			} );

		$this->manager->send(
			'test@example.com',
			'Welcome to {site_name}!',
			'Test message'
		);

		$this->assertEquals( 'Welcome to Test Site!', $subject_received );
	}

	/**
	 * Test send replaces placeholders in message.
	 */
	public function test_send_replaces_placeholders_in_message(): void {
		$message_received = null;

		Functions\expect( 'wp_mail' )
			->once()
			->andReturnUsing( function ( $to, $subject, $message, $headers, $attachments ) use ( &$message_received ) {
				$message_received = $message;
				return true;
			} );

		$this->manager->send(
			'test@example.com',
			'Subject',
			'Hello {user_name}, welcome to {site_name}!',
			[],
			[ 'user_name' => 'John' ]
		);

		$this->assertEquals( 'Hello John, welcome to Test Site!', $message_received );
	}

	/**
	 * Test send includes custom headers.
	 */
	public function test_send_includes_custom_headers(): void {
		$headers_received = null;

		Functions\expect( 'wp_mail' )
			->once()
			->andReturnUsing( function ( $to, $subject, $message, $headers, $attachments ) use ( &$headers_received ) {
				$headers_received = $headers;
				return true;
			} );

		$this->manager->send(
			'test@example.com',
			'Subject',
			'Message',
			[ 'Reply-To: reply@example.com' ]
		);

		$this->assertContains( 'Reply-To: reply@example.com', $headers_received );
	}

	/**
	 * Test get_listing_context returns empty for invalid listing.
	 */
	public function test_get_listing_context_returns_empty_for_invalid(): void {
		Functions\expect( 'get_post' )
			->once()
			->with( 999 )
			->andReturn( null );

		$context = $this->manager->get_listing_context( 999 );

		$this->assertEmpty( $context );
	}

	/**
	 * Test get_listing_context returns context for valid listing.
	 */
	public function test_get_listing_context_returns_context_for_valid(): void {
		$listing               = new \stdClass();
		$listing->ID           = 123;
		$listing->post_title   = 'Test Listing';
		$listing->post_author  = 1;
		$listing->post_status  = 'publish';
		$listing->post_type    = 'apd_listing';

		$user               = new \stdClass();
		$user->display_name = 'Test Author';
		$user->user_email   = 'author@example.com';

		Functions\expect( 'get_post' )
			->once()
			->with( 123 )
			->andReturn( $listing );

		Functions\expect( 'get_userdata' )
			->once()
			->with( 1 )
			->andReturn( $user );

		Functions\expect( 'get_permalink' )
			->once()
			->with( 123 )
			->andReturn( 'https://example.com/listing/test/' );

		Functions\expect( 'get_edit_post_link' )
			->once()
			->with( 123, 'raw' )
			->andReturn( 'https://example.com/wp-admin/post.php?post=123&action=edit' );

		Functions\expect( 'admin_url' )
			->once()
			->andReturn( 'https://example.com/wp-admin/edit.php?post_type=apd_listing' );

		$context = $this->manager->get_listing_context( 123 );

		$this->assertEquals( 123, $context['listing_id'] );
		$this->assertEquals( 'Test Listing', $context['listing_title'] );
		$this->assertEquals( 'https://example.com/listing/test/', $context['listing_url'] );
		$this->assertEquals( 'Test Author', $context['author_name'] );
		$this->assertEquals( 'author@example.com', $context['author_email'] );
	}

	/**
	 * Test get_user_context returns empty for invalid user.
	 */
	public function test_get_user_context_returns_empty_for_invalid(): void {
		Functions\expect( 'get_userdata' )
			->once()
			->with( 999 )
			->andReturn( false );

		$context = $this->manager->get_user_context( 999 );

		$this->assertEmpty( $context );
	}

	/**
	 * Test get_user_context returns context for valid user.
	 */
	public function test_get_user_context_returns_context_for_valid(): void {
		$user               = new \stdClass();
		$user->ID           = 1;
		$user->display_name = 'Test User';
		$user->user_email   = 'user@example.com';
		$user->user_login   = 'testuser';
		$user->first_name   = 'Test';
		$user->last_name    = 'User';

		Functions\expect( 'get_userdata' )
			->once()
			->with( 1 )
			->andReturn( $user );

		$context = $this->manager->get_user_context( 1 );

		$this->assertEquals( 1, $context['user_id'] );
		$this->assertEquals( 'Test User', $context['user_name'] );
		$this->assertEquals( 'user@example.com', $context['user_email'] );
		$this->assertEquals( 'testuser', $context['user_login'] );
		$this->assertEquals( 'Test', $context['user_first_name'] );
		$this->assertEquals( 'User', $context['user_last_name'] );
	}

	/**
	 * Test get_review_context returns empty for invalid review.
	 */
	public function test_get_review_context_returns_empty_for_invalid(): void {
		Functions\expect( 'get_comment' )
			->once()
			->with( 999 )
			->andReturn( null );

		$context = $this->manager->get_review_context( 999 );

		$this->assertEmpty( $context );
	}

	/**
	 * Test get_review_context returns context for valid review.
	 */
	public function test_get_review_context_returns_context_for_valid(): void {
		$review                       = new \stdClass();
		$review->comment_ID           = 1;
		$review->comment_author       = 'Reviewer';
		$review->comment_author_email = 'reviewer@example.com';
		$review->comment_content      = 'Great listing!';

		Functions\expect( 'get_comment' )
			->once()
			->with( 1 )
			->andReturn( $review );

		Functions\expect( 'get_comment_meta' )
			->once()
			->with( 1, '_apd_rating', true )
			->andReturn( '5' );

		Functions\expect( 'get_comment_meta' )
			->once()
			->with( 1, '_apd_review_title', true )
			->andReturn( 'Excellent!' );

		Functions\expect( 'get_comment_date' )
			->once()
			->with( '', $review )
			->andReturn( 'January 1, 2025' );

		$context = $this->manager->get_review_context( 1 );

		$this->assertEquals( 1, $context['review_id'] );
		$this->assertEquals( 'Reviewer', $context['review_author'] );
		$this->assertEquals( 'reviewer@example.com', $context['review_email'] );
		$this->assertEquals( 'Great listing!', $context['review_content'] );
		$this->assertEquals( 5, $context['review_rating'] );
		$this->assertEquals( 'Excellent!', $context['review_title'] );
	}

	/**
	 * Test get_plain_text_message returns message for known template.
	 */
	public function test_get_plain_text_message_returns_message(): void {
		$message = $this->manager->get_plain_text_message( 'listing-submitted', [] );

		$this->assertNotEmpty( $message );
		$this->assertStringContainsString( '{listing_title}', $message );
		$this->assertStringContainsString( '{author_name}', $message );
	}

	/**
	 * Test get_plain_text_message returns empty for unknown template.
	 */
	public function test_get_plain_text_message_returns_empty_for_unknown(): void {
		$message = $this->manager->get_plain_text_message( 'unknown-template', [] );

		$this->assertEmpty( $message );
	}

	/**
	 * Test wrap_html_email includes wrapper structure.
	 */
	public function test_wrap_html_email_includes_structure(): void {
		// Disable template usage to test default wrapper.
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );

		$html = $manager->wrap_html_email( '<p>Test content</p>' );

		$this->assertStringContainsString( '<!DOCTYPE html>', $html );
		$this->assertStringContainsString( '<p>Test content</p>', $html );
		$this->assertStringContainsString( 'Test Site', $html );
		$this->assertStringContainsString( 'email-wrapper', $html );
		$this->assertStringContainsString( 'email-header', $html );
		$this->assertStringContainsString( 'email-body', $html );
		$this->assertStringContainsString( 'email-footer', $html );
	}

	/**
	 * Test on_listing_submitted skips when disabled.
	 */
	public function test_on_listing_submitted_skips_when_disabled(): void {
		$this->manager->set_notification_enabled( 'listing_submitted', false );

		// Verify it returns early without trying to get post.
		// We can check that the disabled state is respected.
		$this->assertFalse( $this->manager->is_notification_enabled( 'listing_submitted' ) );

		// Call the method - it should not throw any errors or call get_post.
		$this->manager->on_listing_submitted( 123, [] );

		// If we got here without errors, the test passes.
		$this->assertTrue( true );
	}

	/**
	 * Test on_listing_status_changed handles approval.
	 */
	public function test_on_listing_status_changed_handles_approval(): void {
		// Create a manager with templates disabled for simpler testing.
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );

		// Create mock listing.
		$listing               = new \stdClass();
		$listing->ID           = 123;
		$listing->post_title   = 'Test';
		$listing->post_author  = 1;
		$listing->post_status  = 'publish';
		$listing->post_type    = 'apd_listing';

		$user               = new \stdClass();
		$user->display_name = 'Author';
		$user->user_email   = 'author@example.com';

		Functions\expect( 'get_post' )->once()->andReturn( $listing );
		Functions\expect( 'get_userdata' )->once()->andReturn( $user );
		Functions\expect( 'get_permalink' )->once()->andReturn( 'https://example.com/listing/test/' );
		Functions\expect( 'get_edit_post_link' )->once()->andReturn( 'https://example.com/wp-admin/post.php?post=123&action=edit' );
		Functions\expect( 'admin_url' )->once()->andReturn( 'https://example.com/wp-admin/' );
		Functions\expect( 'wp_mail' )->once()->andReturn( true );

		$manager->on_listing_status_changed( 123, 'publish', 'pending' );

		// Verify we reached this point without errors.
		$this->assertTrue( true );
	}

	/**
	 * Test on_listing_status_changed handles expiration.
	 */
	public function test_on_listing_status_changed_handles_expiration(): void {
		// Create a manager with templates disabled for simpler testing.
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );

		// Create mock listing.
		$listing               = new \stdClass();
		$listing->ID           = 123;
		$listing->post_title   = 'Test';
		$listing->post_author  = 1;
		$listing->post_status  = 'expired';
		$listing->post_type    = 'apd_listing';

		$user               = new \stdClass();
		$user->display_name = 'Author';
		$user->user_email   = 'author@example.com';

		Functions\expect( 'get_post' )->once()->andReturn( $listing );
		Functions\expect( 'get_userdata' )->once()->andReturn( $user );
		Functions\expect( 'get_permalink' )->once()->andReturn( 'https://example.com/listing/test/' );
		Functions\expect( 'get_edit_post_link' )->once()->andReturn( 'https://example.com/wp-admin/post.php?post=123&action=edit' );
		Functions\expect( 'admin_url' )->once()->andReturn( 'https://example.com/wp-admin/' );
		Functions\expect( 'wp_mail' )->once()->andReturn( true );

		$manager->on_listing_status_changed( 123, 'expired', 'publish' );

		// Verify we reached this point without errors.
		$this->assertTrue( true );
	}

	/**
	 * Test on_review_created skips when disabled.
	 */
	public function test_on_review_created_skips_when_disabled(): void {
		$this->manager->set_notification_enabled( 'new_review', false );

		// Verify it returns early.
		$this->assertFalse( $this->manager->is_notification_enabled( 'new_review' ) );

		// Call the method - it should not throw any errors.
		$this->manager->on_review_created( 123, [] );

		// If we got here without errors, the test passes.
		$this->assertTrue( true );
	}

	/**
	 * Test chained configuration.
	 */
	public function test_chained_configuration(): void {
		$result = $this->manager
			->set_config( [ 'from_name' => 'Test' ] )
			->set_notification_enabled( 'listing_submitted', false )
			->register_placeholder( 'custom', fn() => 'value' )
			->unregister_placeholder( 'custom' );

		$this->assertInstanceOf( EmailManager::class, $result );
	}

	/**
	 * Test get_plain_text_message for listing-approved.
	 */
	public function test_plain_text_listing_approved(): void {
		$message = $this->manager->get_plain_text_message( 'listing-approved', [] );

		$this->assertStringContainsString( '{listing_title}', $message );
		$this->assertStringContainsString( '{listing_url}', $message );
	}

	/**
	 * Test get_plain_text_message for listing-rejected.
	 */
	public function test_plain_text_listing_rejected(): void {
		$message = $this->manager->get_plain_text_message( 'listing-rejected', [] );

		$this->assertStringContainsString( '{listing_title}', $message );
	}

	/**
	 * Test get_plain_text_message for listing-expiring.
	 */
	public function test_plain_text_listing_expiring(): void {
		$message = $this->manager->get_plain_text_message( 'listing-expiring', [] );

		$this->assertStringContainsString( '{listing_title}', $message );
		$this->assertStringContainsString( '{days_left}', $message );
	}

	/**
	 * Test get_plain_text_message for listing-expired.
	 */
	public function test_plain_text_listing_expired(): void {
		$message = $this->manager->get_plain_text_message( 'listing-expired', [] );

		$this->assertStringContainsString( '{listing_title}', $message );
	}

	/**
	 * Test get_plain_text_message for new-review.
	 */
	public function test_plain_text_new_review(): void {
		$message = $this->manager->get_plain_text_message( 'new-review', [] );

		$this->assertStringContainsString( '{listing_title}', $message );
		$this->assertStringContainsString( '{review_author}', $message );
		$this->assertStringContainsString( '{review_rating}', $message );
	}

	/**
	 * Test get_plain_text_message for new-inquiry.
	 */
	public function test_plain_text_new_inquiry(): void {
		$message = $this->manager->get_plain_text_message( 'new-inquiry', [] );

		$this->assertStringContainsString( '{listing_title}', $message );
		$this->assertStringContainsString( '{inquiry_name}', $message );
		$this->assertStringContainsString( '{inquiry_email}', $message );
	}

	/**
	 * Test send_listing_submitted returns false for invalid listing.
	 */
	public function test_send_listing_submitted_returns_false_for_invalid(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );

		Functions\expect( 'get_post' )->once()->andReturn( null );

		$result = $manager->send_listing_submitted( 999 );

		$this->assertFalse( $result );
	}

	/**
	 * Test send_listing_approved returns false for invalid listing.
	 */
	public function test_send_listing_approved_returns_false_for_invalid(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );

		Functions\expect( 'get_post' )->once()->andReturn( null );

		$result = $manager->send_listing_approved( 999 );

		$this->assertFalse( $result );
	}

	/**
	 * Test send_listing_rejected with reason.
	 */
	public function test_send_listing_rejected_with_reason(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );

		$listing               = new \stdClass();
		$listing->ID           = 123;
		$listing->post_title   = 'Test Listing';
		$listing->post_author  = 1;
		$listing->post_status  = 'rejected';
		$listing->post_type    = 'apd_listing';

		$user               = new \stdClass();
		$user->display_name = 'Test User';
		$user->user_email   = 'user@example.com';

		Functions\expect( 'get_post' )->once()->andReturn( $listing );
		Functions\expect( 'get_userdata' )->once()->andReturn( $user );
		Functions\expect( 'get_permalink' )->once()->andReturn( 'https://example.com/listing/' );
		Functions\expect( 'get_edit_post_link' )->once()->andReturn( 'https://example.com/edit/' );
		Functions\expect( 'admin_url' )->once()->andReturn( 'https://example.com/wp-admin/' );
		Functions\expect( 'wp_mail' )->once()->andReturn( true );

		$result = $manager->send_listing_rejected( 123, 'Content violation' );

		$this->assertTrue( $result );
	}

	/**
	 * Test send_listing_expiring.
	 */
	public function test_send_listing_expiring(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );

		$listing               = new \stdClass();
		$listing->ID           = 123;
		$listing->post_title   = 'Test Listing';
		$listing->post_author  = 1;
		$listing->post_status  = 'publish';
		$listing->post_type    = 'apd_listing';

		$user               = new \stdClass();
		$user->display_name = 'Test User';
		$user->user_email   = 'user@example.com';

		Functions\expect( 'get_post' )->once()->andReturn( $listing );
		Functions\expect( 'get_userdata' )->once()->andReturn( $user );
		Functions\expect( 'get_permalink' )->once()->andReturn( 'https://example.com/listing/' );
		Functions\expect( 'get_edit_post_link' )->once()->andReturn( 'https://example.com/edit/' );
		Functions\expect( 'admin_url' )->once()->andReturn( 'https://example.com/wp-admin/' );
		Functions\expect( 'wp_mail' )->once()->andReturn( true );

		$result = $manager->send_listing_expiring( 123, 5 );

		$this->assertTrue( $result );
	}

	/**
	 * Test send_new_review with valid review.
	 */
	public function test_send_new_review_with_valid_review(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );

		// Mock review.
		$review                       = new \stdClass();
		$review->comment_ID           = 1;
		$review->comment_post_ID      = 123;
		$review->comment_author       = 'Reviewer';
		$review->comment_author_email = 'reviewer@example.com';
		$review->comment_content      = 'Great listing!';

		// Mock listing.
		$listing               = new \stdClass();
		$listing->ID           = 123;
		$listing->post_title   = 'Test Listing';
		$listing->post_author  = 1;
		$listing->post_status  = 'publish';
		$listing->post_type    = 'apd_listing';

		$user               = new \stdClass();
		$user->display_name = 'Test User';
		$user->user_email   = 'user@example.com';

		// get_comment is called twice: once directly and once in get_review_context.
		Functions\expect( 'get_comment' )->twice()->andReturn( $review );
		Functions\expect( 'get_post' )->once()->andReturn( $listing );
		Functions\expect( 'get_userdata' )->once()->andReturn( $user );
		Functions\expect( 'get_permalink' )->once()->andReturn( 'https://example.com/listing/' );
		Functions\expect( 'get_edit_post_link' )->once()->andReturn( 'https://example.com/edit/' );
		Functions\expect( 'admin_url' )->once()->andReturn( 'https://example.com/wp-admin/' );
		Functions\expect( 'get_comment_meta' )
			->once()
			->with( 1, '_apd_rating', true )
			->andReturn( '5' );
		Functions\expect( 'get_comment_meta' )
			->once()
			->with( 1, '_apd_review_title', true )
			->andReturn( 'Excellent!' );
		Functions\expect( 'get_comment_date' )->once()->andReturn( 'January 1, 2025' );
		Functions\expect( 'wp_mail' )->once()->andReturn( true );

		$result = $manager->send_new_review( 1 );

		$this->assertTrue( $result );
	}

	/**
	 * Test send_new_review returns false for invalid review.
	 */
	public function test_send_new_review_returns_false_for_invalid(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );

		Functions\expect( 'get_comment' )->once()->andReturn( null );

		$result = $manager->send_new_review( 999 );

		$this->assertFalse( $result );
	}

	/**
	 * Test send_new_inquiry.
	 */
	public function test_send_new_inquiry(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );

		$listing               = new \stdClass();
		$listing->ID           = 123;
		$listing->post_title   = 'Test Listing';
		$listing->post_author  = 1;
		$listing->post_status  = 'publish';
		$listing->post_type    = 'apd_listing';

		$user               = new \stdClass();
		$user->display_name = 'Test User';
		$user->user_email   = 'user@example.com';

		Functions\expect( 'get_post' )->once()->andReturn( $listing );
		Functions\expect( 'get_userdata' )->once()->andReturn( $user );
		Functions\expect( 'get_permalink' )->once()->andReturn( 'https://example.com/listing/' );
		Functions\expect( 'get_edit_post_link' )->once()->andReturn( 'https://example.com/edit/' );
		Functions\expect( 'admin_url' )->once()->andReturn( 'https://example.com/wp-admin/' );
		Functions\expect( 'wp_mail' )->once()->andReturn( true );

		$inquiry = [
			'name'    => 'John Doe',
			'email'   => 'john@example.com',
			'phone'   => '123-456-7890',
			'message' => 'I am interested in this listing.',
		];

		$result = $manager->send_new_inquiry( 123, $inquiry );

		$this->assertTrue( $result );
	}

	/**
	 * Test get_template_html falls back to plain text when templates disabled.
	 */
	public function test_get_template_html_falls_back_to_plain_text(): void {
		$manager = EmailManager::get_instance( [ 'use_templates' => false ] );

		$html = $manager->get_template_html( 'listing-submitted', [
			'listing_title' => 'Test',
			'author_name'   => 'Author',
		] );

		// Should have content from plain text message.
		$this->assertStringContainsString( '{listing_title}', $html );
	}
}
