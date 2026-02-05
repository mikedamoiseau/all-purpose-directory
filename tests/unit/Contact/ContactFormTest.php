<?php
/**
 * ContactForm unit tests.
 *
 * @package All_Purpose_Directory
 */

namespace APD\Tests\Unit\Contact;

use APD\Contact\ContactForm;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test case for ContactForm class.
 */
class ContactFormTest extends TestCase {

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Reset singleton.
		$reflection = new \ReflectionClass( ContactForm::class );
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
	 * Test singleton pattern.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = ContactForm::get_instance();
		$instance2 = ContactForm::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test get_instance returns ContactForm.
	 */
	public function test_get_instance_returns_contact_form(): void {
		$instance = ContactForm::get_instance();
		$this->assertInstanceOf( ContactForm::class, $instance );
	}

	/**
	 * Test default configuration.
	 */
	public function test_default_configuration(): void {
		$form = new ContactForm();

		$this->assertTrue( $form->show_phone() );
		$this->assertFalse( $form->is_phone_required() );
		$this->assertFalse( $form->show_subject() );
		$this->assertFalse( $form->is_subject_required() );
		$this->assertEquals( 10, $form->get_min_message_length() );
	}

	/**
	 * Test custom configuration.
	 */
	public function test_custom_configuration(): void {
		$form = new ContactForm( [
			'show_phone'      => false,
			'phone_required'  => true,
			'show_subject'    => true,
			'subject_required' => true,
			'min_message_length' => 50,
		] );

		$this->assertFalse( $form->show_phone() );
		$this->assertTrue( $form->is_phone_required() );
		$this->assertTrue( $form->show_subject() );
		$this->assertTrue( $form->is_subject_required() );
		$this->assertEquals( 50, $form->get_min_message_length() );
	}

	/**
	 * Test set_config merges configuration.
	 */
	public function test_set_config_merges(): void {
		$form = new ContactForm();
		$form->set_config( [ 'show_phone' => false ] );

		$this->assertFalse( $form->show_phone() );
		$this->assertEquals( 10, $form->get_min_message_length() ); // Unchanged.
	}

	/**
	 * Test set and get listing_id.
	 */
	public function test_set_and_get_listing_id(): void {
		$form = new ContactForm();

		$this->assertEquals( 0, $form->get_listing_id() );

		$result = $form->set_listing_id( 123 );

		$this->assertSame( $form, $result ); // Fluent interface.
		$this->assertEquals( 123, $form->get_listing_id() );
	}

	/**
	 * Test set and get errors.
	 */
	public function test_set_and_get_errors(): void {
		$form = new ContactForm();
		$errors = [ 'Name is required', 'Email is invalid' ];

		$result = $form->set_errors( $errors );

		$this->assertSame( $form, $result );
		$this->assertEquals( $errors, $form->get_errors() );
	}

	/**
	 * Test set and get values.
	 */
	public function test_set_and_get_values(): void {
		$form = new ContactForm();
		$values = [
			'contact_name' => 'John Doe',
			'contact_email' => 'john@example.com',
		];

		$result = $form->set_values( $values );

		$this->assertSame( $form, $result );
		$this->assertEquals( $values, $form->get_values() );
	}

	/**
	 * Test get_value returns specific value.
	 */
	public function test_get_value_returns_specific(): void {
		$form = new ContactForm();
		$form->set_values( [
			'contact_name' => 'John Doe',
			'contact_email' => 'john@example.com',
		] );

		$this->assertEquals( 'John Doe', $form->get_value( 'contact_name' ) );
		$this->assertEquals( 'john@example.com', $form->get_value( 'contact_email' ) );
	}

	/**
	 * Test get_value returns default for missing key.
	 */
	public function test_get_value_returns_default(): void {
		$form = new ContactForm();

		$this->assertEquals( '', $form->get_value( 'missing' ) );
		$this->assertEquals( 'default', $form->get_value( 'missing', 'default' ) );
	}

	/**
	 * Test get_config returns value.
	 */
	public function test_get_config_returns_value(): void {
		$form = new ContactForm();

		$this->assertTrue( $form->get_config( 'show_phone' ) );
		$this->assertEquals( 10, $form->get_config( 'min_message_length' ) );
	}

	/**
	 * Test get_config returns default for missing.
	 */
	public function test_get_config_returns_default(): void {
		$form = new ContactForm();

		$this->assertNull( $form->get_config( 'missing' ) );
		$this->assertEquals( 'default', $form->get_config( 'missing', 'default' ) );
	}

	/**
	 * Test get_css_classes returns base class.
	 */
	public function test_get_css_classes_base(): void {
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );
		Functions\when( 'sanitize_html_class' )->returnArg( 1 );

		$form = new ContactForm();
		$classes = $form->get_css_classes();

		$this->assertStringContainsString( 'apd-contact-form', $classes );
	}

	/**
	 * Test get_css_classes includes custom class.
	 */
	public function test_get_css_classes_custom(): void {
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );
		Functions\when( 'sanitize_html_class' )->returnArg( 1 );

		$form = new ContactForm( [ 'class' => 'my-custom-class' ] );
		$classes = $form->get_css_classes();

		$this->assertStringContainsString( 'apd-contact-form', $classes );
		$this->assertStringContainsString( 'my-custom-class', $classes );
	}

	/**
	 * Test nonce constants are defined.
	 */
	public function test_nonce_constants(): void {
		$this->assertEquals( 'apd_contact_form', ContactForm::NONCE_ACTION );
		$this->assertEquals( 'apd_contact_nonce', ContactForm::NONCE_NAME );
	}

	/**
	 * Test get_html returns empty for invalid listing.
	 */
	public function test_get_html_empty_for_invalid_listing(): void {
		$form = new ContactForm();

		$this->assertEquals( '', $form->get_html( 0 ) );
		$this->assertEquals( '', $form->get_html( -1 ) );
	}

	/**
	 * Test get_html returns empty when listing not found.
	 */
	public function test_get_html_empty_when_listing_not_found(): void {
		Functions\when( 'get_post' )->justReturn( null );

		$form = new ContactForm();
		$form->set_listing_id( 123 );

		$this->assertEquals( '', $form->get_html() );
	}

	/**
	 * Test get_html returns empty for wrong post type.
	 */
	public function test_get_html_empty_for_wrong_post_type(): void {
		$post = Mockery::mock( 'WP_Post' );
		$post->post_type = 'post';

		Functions\when( 'get_post' )->justReturn( $post );

		$form = new ContactForm();
		$form->set_listing_id( 123 );

		$this->assertEquals( '', $form->get_html() );
	}

	/**
	 * Test get_html returns empty when owner not found.
	 */
	public function test_get_html_empty_when_owner_not_found(): void {
		$post = Mockery::mock( 'WP_Post' );
		$post->post_type = 'apd_listing';
		$post->post_author = 1;

		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'get_userdata' )->justReturn( false );

		$form = new ContactForm();
		$form->set_listing_id( 123 );

		$this->assertEquals( '', $form->get_html() );
	}

	/**
	 * Test can_receive_contact returns false for invalid listing.
	 */
	public function test_can_receive_contact_false_invalid(): void {
		Functions\when( 'get_post' )->justReturn( null );

		$form = new ContactForm();
		$this->assertFalse( $form->can_receive_contact( 0 ) );
	}

	/**
	 * Test can_receive_contact returns false for wrong post type.
	 */
	public function test_can_receive_contact_false_wrong_type(): void {
		$post = Mockery::mock( 'WP_Post' );
		$post->post_type = 'post';

		Functions\when( 'get_post' )->justReturn( $post );

		$form = new ContactForm();
		$this->assertFalse( $form->can_receive_contact( 123 ) );
	}

	/**
	 * Test can_receive_contact returns false for unpublished.
	 */
	public function test_can_receive_contact_false_unpublished(): void {
		$post = Mockery::mock( 'WP_Post' );
		$post->post_type = 'apd_listing';
		$post->post_status = 'draft';

		Functions\when( 'get_post' )->justReturn( $post );

		$form = new ContactForm();
		$this->assertFalse( $form->can_receive_contact( 123 ) );
	}

	/**
	 * Test can_receive_contact returns false for invalid owner email.
	 */
	public function test_can_receive_contact_false_invalid_email(): void {
		$post = Mockery::mock( 'WP_Post' );
		$post->post_type = 'apd_listing';
		$post->post_status = 'publish';
		$post->post_author = 1;

		$user = Mockery::mock( 'WP_User' );
		$user->user_email = 'invalid-email';

		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'get_userdata' )->justReturn( $user );
		Functions\when( 'is_email' )->justReturn( false );

		$form = new ContactForm();
		$this->assertFalse( $form->can_receive_contact( 123 ) );
	}

	/**
	 * Test can_receive_contact returns true for valid listing.
	 */
	public function test_can_receive_contact_true_valid(): void {
		$post = Mockery::mock( 'WP_Post' );
		$post->post_type = 'apd_listing';
		$post->post_status = 'publish';
		$post->post_author = 1;

		$user = Mockery::mock( 'WP_User' );
		$user->user_email = 'owner@example.com';

		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'get_userdata' )->justReturn( $user );
		Functions\when( 'is_email' )->justReturn( true );
		// returnArg is 1-indexed: arg 2 is the $value passed to apply_filters.
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$form = new ContactForm();
		$this->assertTrue( $form->can_receive_contact( 123 ) );
	}

	/**
	 * Test init registers hooks.
	 */
	public function test_init_registers_hooks(): void {
		$add_action_called = false;
		$do_action_called = false;

		Functions\when( 'add_action' )->alias( function( $hook ) use ( &$add_action_called ) {
			if ( 'apd_single_listing_contact_form' === $hook ) {
				$add_action_called = true;
			}
		} );

		Functions\when( 'do_action' )->alias( function( $hook ) use ( &$do_action_called ) {
			if ( 'apd_contact_form_init' === $hook ) {
				$do_action_called = true;
			}
		} );

		$form = new ContactForm();
		$form->init();

		$this->assertTrue( $add_action_called, 'add_action should be called for apd_single_listing_contact_form' );
		$this->assertTrue( $do_action_called, 'do_action should be called for apd_contact_form_init' );
	}
}
