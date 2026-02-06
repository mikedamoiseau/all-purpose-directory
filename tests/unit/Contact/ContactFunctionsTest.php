<?php
/**
 * Contact helper functions unit tests.
 *
 * Tests the contact helper functions by verifying they delegate
 * to the underlying ContactForm and ContactHandler classes.
 *
 * @package All_Purpose_Directory
 */

namespace APD\Tests\Unit\Contact;

use APD\Contact\ContactForm;
use APD\Contact\ContactHandler;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test case for contact helper functions.
 *
 * Note: These tests verify the ContactForm and ContactHandler classes
 * work as expected when called the way the helper functions use them.
 * The actual functions.php file is tested via integration tests.
 */
class ContactFunctionsTest extends TestCase {

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Reset singletons.
		$reflection = new \ReflectionClass( ContactForm::class );
		$instance = $reflection->getProperty( 'instance' );
		@$instance->setValue( null, null );

		$reflection = new \ReflectionClass( ContactHandler::class );
		$instance = $reflection->getProperty( 'instance' );
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
	 * Test ContactForm singleton for apd_contact_form().
	 */
	public function test_contact_form_singleton_behavior(): void {
		$form1 = ContactForm::get_instance();
		$form2 = ContactForm::get_instance();

		$this->assertSame( $form1, $form2 );
		$this->assertInstanceOf( ContactForm::class, $form1 );
	}

	/**
	 * Test ContactForm new instance with config for apd_contact_form($config).
	 */
	public function test_contact_form_new_instance_with_config(): void {
		$singleton = ContactForm::get_instance();
		$custom = ContactForm::get_instance( [ 'show_phone' => false ] );

		$this->assertNotSame( $singleton, $custom );
		$this->assertFalse( $custom->show_phone() );
	}

	/**
	 * Test ContactHandler singleton for apd_contact_handler().
	 */
	public function test_contact_handler_singleton_behavior(): void {
		$handler1 = ContactHandler::get_instance();
		$handler2 = ContactHandler::get_instance();

		$this->assertSame( $handler1, $handler2 );
		$this->assertInstanceOf( ContactHandler::class, $handler1 );
	}

	/**
	 * Test ContactHandler new instance with config for apd_contact_handler($config).
	 */
	public function test_contact_handler_new_instance_with_config(): void {
		$singleton = ContactHandler::get_instance();
		$custom = ContactHandler::get_instance( [ 'min_message_length' => 50 ] );

		$this->assertNotSame( $singleton, $custom );
		$this->assertEquals( 50, $custom->get_config( 'min_message_length' ) );
	}

	/**
	 * Test can_receive_contact for apd_can_receive_contact().
	 */
	public function test_can_receive_contact_true_for_valid_listing(): void {
		$post = Mockery::mock( 'WP_Post' );
		$post->post_type = 'apd_listing';
		$post->post_status = 'publish';
		$post->post_author = 1;

		$user = Mockery::mock( 'WP_User' );
		$user->user_email = 'owner@example.com';

		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'get_userdata' )->justReturn( $user );
		Functions\when( 'is_email' )->justReturn( true );
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$form = ContactForm::get_instance();
		$this->assertTrue( $form->can_receive_contact( 123 ) );
	}

	/**
	 * Test can_receive_contact returns false for invalid listing.
	 */
	public function test_can_receive_contact_false_for_invalid_listing(): void {
		Functions\when( 'get_post' )->justReturn( null );

		$form = ContactForm::get_instance();
		$this->assertFalse( $form->can_receive_contact( 0 ) );
	}

	/**
	 * Test send_email behavior for apd_send_contact_email().
	 */
	public function test_send_email_returns_true_on_success(): void {
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_attr' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/listing/123' );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'wp_mail' )->justReturn( true );

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
	}

	/**
	 * Test send_email returns false on failure.
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
		Functions\when( '__' )->returnArg( 1 );
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
}
