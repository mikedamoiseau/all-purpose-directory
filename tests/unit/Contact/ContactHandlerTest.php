<?php
/**
 * ContactHandler unit tests.
 *
 * @package All_Purpose_Directory
 */

namespace APD\Tests\Unit\Contact;

use APD\Contact\ContactHandler;
use APD\Contact\ContactForm;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test case for ContactHandler class.
 */
class ContactHandlerTest extends TestCase {

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Reset singleton.
		$reflection = new \ReflectionClass( ContactHandler::class );
		$instance = $reflection->getProperty( 'instance' );
		@$instance->setValue( null, null );

		// Reset $_POST.
		$_POST = [];

		// Mock translation function.
		Functions\when( '__' )->returnArg( 1 );
	}

	/**
	 * Tear down the test environment.
	 */
	protected function tearDown(): void {
		$_POST = [];
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test singleton pattern.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = ContactHandler::get_instance();
		$instance2 = ContactHandler::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test get_instance returns ContactHandler.
	 */
	public function test_get_instance_returns_contact_handler(): void {
		$instance = ContactHandler::get_instance();
		$this->assertInstanceOf( ContactHandler::class, $instance );
	}

	/**
	 * Test default configuration.
	 */
	public function test_default_configuration(): void {
		$handler = ContactHandler::get_instance();

		$this->assertEquals( 10, $handler->get_config( 'min_message_length' ) );
		$this->assertFalse( $handler->get_config( 'phone_required' ) );
		$this->assertFalse( $handler->get_config( 'subject_required' ) );
		$this->assertFalse( $handler->get_config( 'send_admin_copy' ) );
		$this->assertEquals( '', $handler->get_config( 'admin_email' ) );
	}

	/**
	 * Test custom configuration.
	 */
	public function test_custom_configuration(): void {
		$handler = ContactHandler::get_instance( [
			'min_message_length' => 50,
			'phone_required'     => true,
			'subject_required'   => true,
			'send_admin_copy'    => true,
			'admin_email'        => 'admin@test.com',
		] );

		$this->assertEquals( 50, $handler->get_config( 'min_message_length' ) );
		$this->assertTrue( $handler->get_config( 'phone_required' ) );
		$this->assertTrue( $handler->get_config( 'subject_required' ) );
		$this->assertTrue( $handler->get_config( 'send_admin_copy' ) );
		$this->assertEquals( 'admin@test.com', $handler->get_config( 'admin_email' ) );
	}

	/**
	 * Test set_config merges configuration.
	 */
	public function test_set_config_merges(): void {
		$handler = ContactHandler::get_instance();
		$result = $handler->set_config( [ 'min_message_length' => 25 ] );

		$this->assertSame( $handler, $result ); // Fluent interface.
		$this->assertEquals( 25, $handler->get_config( 'min_message_length' ) );
		$this->assertFalse( $handler->get_config( 'phone_required' ) ); // Unchanged.
	}

	/**
	 * Test get_config returns null for missing key.
	 */
	public function test_get_config_returns_default(): void {
		$handler = ContactHandler::get_instance();

		$this->assertNull( $handler->get_config( 'missing' ) );
		$this->assertEquals( 'default', $handler->get_config( 'missing', 'default' ) );
	}

	/**
	 * Test validate fails for missing listing_id.
	 */
	public function test_validate_fails_missing_listing_id(): void {
		Functions\when( 'is_email' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$handler = ContactHandler::get_instance();
		$result = $handler->validate( [
			'listing_id'      => 0,
			'contact_name'    => 'John',
			'contact_email'   => 'john@example.com',
			'contact_phone'   => '',
			'contact_subject' => '',
			'contact_message' => 'This is a test message.',
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertContains( 'listing_id', $result->get_error_codes() );
	}

	/**
	 * Test validate fails for missing name.
	 */
	public function test_validate_fails_missing_name(): void {
		Functions\when( 'is_email' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$handler = ContactHandler::get_instance();
		$result = $handler->validate( [
			'listing_id'      => 123,
			'contact_name'    => '',
			'contact_email'   => 'john@example.com',
			'contact_phone'   => '',
			'contact_subject' => '',
			'contact_message' => 'This is a test message.',
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertContains( 'contact_name', $result->get_error_codes() );
	}

	/**
	 * Test validate fails for missing email.
	 */
	public function test_validate_fails_missing_email(): void {
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$handler = ContactHandler::get_instance();
		$result = $handler->validate( [
			'listing_id'      => 123,
			'contact_name'    => 'John',
			'contact_email'   => '',
			'contact_phone'   => '',
			'contact_subject' => '',
			'contact_message' => 'This is a test message.',
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertContains( 'contact_email', $result->get_error_codes() );
	}

	/**
	 * Test validate fails for invalid email.
	 */
	public function test_validate_fails_invalid_email(): void {
		Functions\when( 'is_email' )->justReturn( false );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$handler = ContactHandler::get_instance();
		$result = $handler->validate( [
			'listing_id'      => 123,
			'contact_name'    => 'John',
			'contact_email'   => 'not-an-email',
			'contact_phone'   => '',
			'contact_subject' => '',
			'contact_message' => 'This is a test message.',
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertContains( 'contact_email', $result->get_error_codes() );
	}

	/**
	 * Test validate fails for missing phone when required.
	 */
	public function test_validate_fails_missing_phone_when_required(): void {
		Functions\when( 'is_email' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$handler = ContactHandler::get_instance( [ 'phone_required' => true ] );
		$result = $handler->validate( [
			'listing_id'      => 123,
			'contact_name'    => 'John',
			'contact_email'   => 'john@example.com',
			'contact_phone'   => '',
			'contact_subject' => '',
			'contact_message' => 'This is a test message.',
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertContains( 'contact_phone', $result->get_error_codes() );
	}

	/**
	 * Test validate passes without phone when optional.
	 */
	public function test_validate_passes_without_phone_when_optional(): void {
		Functions\when( 'is_email' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$handler = ContactHandler::get_instance( [ 'phone_required' => false ] );
		$result = $handler->validate( [
			'listing_id'      => 123,
			'contact_name'    => 'John',
			'contact_email'   => 'john@example.com',
			'contact_phone'   => '',
			'contact_subject' => '',
			'contact_message' => 'This is a valid message.',
		] );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate fails for missing subject when required.
	 */
	public function test_validate_fails_missing_subject_when_required(): void {
		Functions\when( 'is_email' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$handler = ContactHandler::get_instance( [ 'subject_required' => true ] );
		$result = $handler->validate( [
			'listing_id'      => 123,
			'contact_name'    => 'John',
			'contact_email'   => 'john@example.com',
			'contact_phone'   => '',
			'contact_subject' => '',
			'contact_message' => 'This is a test message.',
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertContains( 'contact_subject', $result->get_error_codes() );
	}

	/**
	 * Test validate fails for missing message.
	 */
	public function test_validate_fails_missing_message(): void {
		Functions\when( 'is_email' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$handler = ContactHandler::get_instance();
		$result = $handler->validate( [
			'listing_id'      => 123,
			'contact_name'    => 'John',
			'contact_email'   => 'john@example.com',
			'contact_phone'   => '',
			'contact_subject' => '',
			'contact_message' => '',
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertContains( 'contact_message', $result->get_error_codes() );
	}

	/**
	 * Test validate fails for short message.
	 */
	public function test_validate_fails_short_message(): void {
		Functions\when( 'is_email' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$handler = ContactHandler::get_instance( [ 'min_message_length' => 20 ] );
		$result = $handler->validate( [
			'listing_id'      => 123,
			'contact_name'    => 'John',
			'contact_email'   => 'john@example.com',
			'contact_phone'   => '',
			'contact_subject' => '',
			'contact_message' => 'Too short',
		] );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertContains( 'contact_message', $result->get_error_codes() );
	}

	/**
	 * Test validate passes for valid data.
	 */
	public function test_validate_passes_valid_data(): void {
		Functions\when( 'is_email' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$handler = ContactHandler::get_instance();
		$result = $handler->validate( [
			'listing_id'      => 123,
			'contact_name'    => 'John Doe',
			'contact_email'   => 'john@example.com',
			'contact_phone'   => '555-1234',
			'contact_subject' => 'Inquiry',
			'contact_message' => 'This is a valid test message.',
		] );

		$this->assertTrue( $result );
	}

	/**
	 * Test get_sanitized_data sanitizes POST.
	 */
	public function test_get_sanitized_data(): void {
		$_POST = [
			'listing_id'      => '123',
			'contact_name'    => '  John Doe  ',
			'contact_email'   => 'JOHN@EXAMPLE.COM',
			'contact_phone'   => '555-1234',
			'contact_subject' => 'Test Subject',
			'contact_message' => "Line 1\nLine 2",
		];

		Functions\when( 'absint' )->alias( function( $val ) {
			return abs( (int) $val );
		} );
		Functions\when( 'sanitize_text_field' )->alias( function( $val ) {
			return trim( $val );
		} );
		Functions\when( 'sanitize_email' )->alias( function( $val ) {
			return strtolower( trim( $val ) );
		} );
		Functions\when( 'sanitize_textarea_field' )->alias( function( $val ) {
			return trim( $val );
		} );
		Functions\when( 'wp_unslash' )->returnArg( 1 );

		$handler = ContactHandler::get_instance();
		$data = $handler->get_sanitized_data();

		$this->assertEquals( 123, $data['listing_id'] );
		$this->assertEquals( 'John Doe', $data['contact_name'] );
		$this->assertEquals( 'john@example.com', $data['contact_email'] );
		$this->assertEquals( '555-1234', $data['contact_phone'] );
		$this->assertEquals( 'Test Subject', $data['contact_subject'] );
		$this->assertStringContainsString( 'Line 1', $data['contact_message'] );
	}

	/**
	 * Test should_send_admin_copy returns config value.
	 */
	public function test_should_send_admin_copy(): void {
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$handler1 = ContactHandler::get_instance( [ 'send_admin_copy' => false ] );
		$this->assertFalse( $handler1->should_send_admin_copy() );

		$handler2 = ContactHandler::get_instance( [ 'send_admin_copy' => true ] );
		$this->assertTrue( $handler2->should_send_admin_copy() );
	}

	/**
	 * Test get_admin_email returns config value.
	 */
	public function test_get_admin_email_from_config(): void {
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$handler = ContactHandler::get_instance( [ 'admin_email' => 'custom@admin.com' ] );
		$this->assertEquals( 'custom@admin.com', $handler->get_admin_email() );
	}

	/**
	 * Test get_admin_email falls back to option.
	 */
	public function test_get_admin_email_fallback(): void {
		Functions\when( 'get_option' )->justReturn( 'default@admin.com' );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$handler = ContactHandler::get_instance( [ 'admin_email' => '' ] );
		$this->assertEquals( 'default@admin.com', $handler->get_admin_email() );
	}

	/**
	 * Test build_email_message contains required elements.
	 */
	public function test_build_email_message(): void {
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/listing/123' );

		$listing = Mockery::mock( 'WP_Post' );
		$listing->ID = 123;
		$listing->post_title = 'Test Listing';

		$handler = ContactHandler::get_instance();
		$message = $handler->build_email_message( [
			'contact_name'    => 'John Doe',
			'contact_email'   => 'john@example.com',
			'contact_phone'   => '555-1234',
			'contact_message' => 'Test message content.',
		], $listing );

		$this->assertStringContainsString( 'html', $message );
		$this->assertStringContainsString( 'John Doe', $message );
		$this->assertStringContainsString( 'john@example.com', $message );
		$this->assertStringContainsString( '555-1234', $message );
		$this->assertStringContainsString( 'Test message content', $message );
		$this->assertStringContainsString( 'Test Listing', $message );
	}

	/**
	 * Test init registers AJAX hooks.
	 */
	public function test_init_registers_ajax_hooks(): void {
		$hooks_registered = [];

		Functions\when( 'add_action' )->alias( function( $hook, $callback ) use ( &$hooks_registered ) {
			$hooks_registered[] = $hook;
		} );

		Functions\when( 'do_action' )->justReturn( null );

		$handler = ContactHandler::get_instance();
		$handler->init();

		$this->assertContains( 'wp_ajax_apd_send_contact', $hooks_registered );
		$this->assertContains( 'wp_ajax_nopriv_apd_send_contact', $hooks_registered );
	}

	/**
	 * Test verify_nonce returns false without nonce.
	 */
	public function test_verify_nonce_false_without_nonce(): void {
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'wp_verify_nonce' )->justReturn( false );

		$_POST = [];

		$handler = ContactHandler::get_instance();
		$this->assertFalse( $handler->verify_nonce() );
	}

	/**
	 * Test verify_nonce returns true with valid nonce.
	 */
	public function test_verify_nonce_true_with_valid(): void {
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'wp_verify_nonce' )->justReturn( true );

		$_POST[ ContactForm::NONCE_NAME ] = 'valid_nonce';

		$handler = ContactHandler::get_instance();
		$this->assertTrue( $handler->verify_nonce() );
	}

	/**
	 * Test send_email sends to owner.
	 */
	public function test_send_email_sends_to_owner(): void {
		$email_sent_to = null;

		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/listing/123' );
		Functions\when( 'wp_mail' )->alias( function( $to ) use ( &$email_sent_to ) {
			$email_sent_to = $to;
			return true;
		} );

		$listing = Mockery::mock( 'WP_Post' );
		$listing->ID = 123;
		$listing->post_title = 'Test Listing';

		$owner = Mockery::mock( 'WP_User' );
		$owner->user_email = 'owner@example.com';

		$handler = ContactHandler::get_instance();
		$result = $handler->send_email( [
			'contact_name'    => 'John',
			'contact_email'   => 'john@example.com',
			'contact_phone'   => '',
			'contact_subject' => '',
			'contact_message' => 'Test message.',
		], $listing, $owner );

		$this->assertTrue( $result );
		$this->assertEquals( 'owner@example.com', $email_sent_to );
	}

	/**
	 * Test send_email uses custom subject.
	 */
	public function test_send_email_uses_custom_subject(): void {
		$email_subject = null;

		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/listing/123' );
		Functions\when( 'wp_mail' )->alias( function( $to, $subject ) use ( &$email_subject ) {
			$email_subject = $subject;
			return true;
		} );

		$listing = Mockery::mock( 'WP_Post' );
		$listing->ID = 123;
		$listing->post_title = 'Test Listing';

		$owner = Mockery::mock( 'WP_User' );
		$owner->user_email = 'owner@example.com';

		$handler = ContactHandler::get_instance();
		$handler->send_email( [
			'contact_name'    => 'John',
			'contact_email'   => 'john@example.com',
			'contact_phone'   => '',
			'contact_subject' => 'Custom Subject Line',
			'contact_message' => 'Test message.',
		], $listing, $owner );

		$this->assertEquals( 'Custom Subject Line', $email_subject );
	}

	/**
	 * Test send_email generates subject when not provided.
	 */
	public function test_send_email_generates_subject(): void {
		$email_subject = null;

		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/listing/123' );
		Functions\when( 'wp_mail' )->alias( function( $to, $subject ) use ( &$email_subject ) {
			$email_subject = $subject;
			return true;
		} );

		$listing = Mockery::mock( 'WP_Post' );
		$listing->ID = 123;
		$listing->post_title = 'Test Listing';

		$owner = Mockery::mock( 'WP_User' );
		$owner->user_email = 'owner@example.com';

		$handler = ContactHandler::get_instance();
		$handler->send_email( [
			'contact_name'    => 'John',
			'contact_email'   => 'john@example.com',
			'contact_phone'   => '',
			'contact_subject' => '',
			'contact_message' => 'Test message.',
		], $listing, $owner );

		$this->assertStringContainsString( 'Test Listing', $email_subject );
	}

	/**
	 * Test send_email sends admin copy when configured.
	 */
	public function test_send_email_sends_admin_copy(): void {
		$emails_sent = [];

		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/listing/123' );
		Functions\when( 'wp_mail' )->alias( function( $to ) use ( &$emails_sent ) {
			$emails_sent[] = $to;
			return true;
		} );

		$listing = Mockery::mock( 'WP_Post' );
		$listing->ID = 123;
		$listing->post_title = 'Test Listing';

		$owner = Mockery::mock( 'WP_User' );
		$owner->user_email = 'owner@example.com';

		$handler = ContactHandler::get_instance( [
			'send_admin_copy' => true,
			'admin_email'     => 'admin@example.com',
		] );
		$handler->send_email( [
			'contact_name'    => 'John',
			'contact_email'   => 'john@example.com',
			'contact_phone'   => '',
			'contact_subject' => '',
			'contact_message' => 'Test message.',
		], $listing, $owner );

		$this->assertContains( 'owner@example.com', $emails_sent );
		$this->assertContains( 'admin@example.com', $emails_sent );
	}

	/**
	 * Test send_email returns false when wp_mail fails.
	 */
	public function test_send_email_returns_false_on_failure(): void {
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/listing/123' );
		Functions\when( 'wp_mail' )->justReturn( false );

		$listing = Mockery::mock( 'WP_Post' );
		$listing->ID = 123;
		$listing->post_title = 'Test Listing';

		$owner = Mockery::mock( 'WP_User' );
		$owner->user_email = 'owner@example.com';

		$handler = ContactHandler::get_instance();
		$result = $handler->send_email( [
			'contact_name'    => 'John',
			'contact_email'   => 'john@example.com',
			'contact_phone'   => '',
			'contact_subject' => '',
			'contact_message' => 'Test message.',
		], $listing, $owner );

		$this->assertFalse( $result );
	}

	/**
	 * Test build_email_message without phone.
	 */
	public function test_build_email_message_without_phone(): void {
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/listing/123' );

		$listing = Mockery::mock( 'WP_Post' );
		$listing->ID = 123;
		$listing->post_title = 'Test Listing';

		$handler = ContactHandler::get_instance();
		$message = $handler->build_email_message( [
			'contact_name'    => 'John Doe',
			'contact_email'   => 'john@example.com',
			'contact_phone'   => '',
			'contact_message' => 'Test message content.',
		], $listing );

		$this->assertStringContainsString( 'John Doe', $message );
		$this->assertStringContainsString( 'john@example.com', $message );
		$this->assertStringNotContainsString( 'Phone:', $message );
	}
}
