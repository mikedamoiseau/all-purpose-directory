<?php
/**
 * ReviewForm Unit Tests.
 *
 * @package APD\Tests\Unit\Review
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Review;

use APD\Review\ReviewForm;
use APD\Review\ReviewManager;
use APD\Review\RatingCalculator;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for ReviewForm.
 */
final class ReviewFormTest extends UnitTestCase {

	/**
	 * ReviewForm instance.
	 *
	 * @var ReviewForm
	 */
	private ReviewForm $form;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset ReviewForm singleton.
		$this->reset_singleton( ReviewForm::class );

		// Reset ReviewManager singleton (used by ReviewForm).
		$this->reset_singleton( ReviewManager::class );

		// Reset RatingCalculator singleton (used by ReviewForm).
		$this->reset_singleton( RatingCalculator::class );

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
				$post->post_author = 2; // Different from current user.
				return $post;
			},
			'get_userdata'        => function( $user_id ) {
				if ( $user_id <= 0 ) {
					return false;
				}
				$user               = new \stdClass();
				$user->ID           = $user_id;
				$user->display_name = 'Test User';
				$user->user_email   = 'test@example.com';
				return $user;
			},
			'wp_parse_args'       => function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			},
			'wp_create_nonce'     => 'test_nonce_value',
			'get_transient'       => false,
			'delete_transient'    => true,
			'get_permalink'       => 'http://example.com/listing/123',
			'wp_login_url'        => 'http://example.com/wp-login.php',
			'wp_registration_url' => 'http://example.com/wp-login.php?action=register',
			'__'                  => function( $text ) {
				return $text;
			},
			'esc_html__'          => function( $text ) {
				return $text;
			},
			'esc_attr__'          => function( $text ) {
				return $text;
			},
			'apply_filters'       => function( $hook, $value, ...$args ) {
				return $value;
			},
			'do_action'           => function() {},
			'add_action'          => function() {},
			'add_filter'          => function() {},
		] );

		$this->form = ReviewForm::get_instance();
	}

	/**
	 * Reset a singleton instance for testing.
	 *
	 * @param string $class_name Fully qualified class name.
	 */
	private function reset_singleton( string $class_name ): void {
		$reflection = new \ReflectionClass( $class_name );
		$instance   = $reflection->getProperty( 'instance' );
		@$instance->setAccessible( true );
		$instance->setValue( null, null );
	}

	/**
	 * Test singleton pattern returns same instance.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = ReviewForm::get_instance();
		$instance2 = ReviewForm::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test constants are defined.
	 */
	public function test_constants_are_defined(): void {
		$this->assertSame( 'apd_submit_review', ReviewForm::NONCE_ACTION );
		$this->assertSame( 'apd_review_nonce', ReviewForm::NONCE_NAME );
	}

	/**
	 * Test get_user_review returns null for logged-out users.
	 */
	public function test_get_user_review_returns_null_for_guest(): void {
		Functions\stubs( [
			'get_current_user_id' => 0,
		] );

		$result = $this->form->get_user_review( 123 );

		$this->assertNull( $result );
	}

	/**
	 * Test is_edit_mode returns false when user has no review.
	 */
	public function test_is_edit_mode_returns_false_when_no_review(): void {
		// Mock ReviewManager to return null for user review.
		$this->reset_singleton( ReviewManager::class );

		Functions\expect( 'get_comments' )
			->andReturn( [] );

		$result = $this->form->is_edit_mode( 123 );

		$this->assertFalse( $result );
	}

	/**
	 * Test get_config returns default values.
	 */
	public function test_get_config_returns_defaults(): void {
		$this->assertTrue( $this->form->get_config( 'show_title' ) );
		$this->assertFalse( $this->form->get_config( 'title_required' ) );
		$this->assertTrue( $this->form->get_config( 'ajax_enabled' ) );
		$this->assertTrue( $this->form->get_config( 'show_guidelines' ) );
	}

	/**
	 * Test get_config returns default for unknown key.
	 */
	public function test_get_config_returns_default_for_unknown_key(): void {
		$result = $this->form->get_config( 'unknown_key', 'default_value' );

		$this->assertSame( 'default_value', $result );
	}

	/**
	 * Test add_script_data adds review nonce.
	 */
	public function test_add_script_data_adds_review_nonce(): void {
		$data = [ 'existing' => 'data' ];

		$result = $this->form->add_script_data( $data );

		$this->assertArrayHasKey( 'reviewNonce', $result );
		$this->assertSame( 'test_nonce_value', $result['reviewNonce'] );
	}

	/**
	 * Test add_script_data adds i18n strings.
	 */
	public function test_add_script_data_adds_i18n_strings(): void {
		$data = [ 'i18n' => [ 'existing' => 'string' ] ];

		$result = $this->form->add_script_data( $data );

		$this->assertArrayHasKey( 'i18n', $result );
		$this->assertArrayHasKey( 'existing', $result['i18n'] );
		$this->assertArrayHasKey( 'reviewSubmitting', $result['i18n'] );
		$this->assertArrayHasKey( 'reviewSubmitted', $result['i18n'] );
		$this->assertArrayHasKey( 'reviewUpdated', $result['i18n'] );
		$this->assertArrayHasKey( 'reviewError', $result['i18n'] );
		$this->assertArrayHasKey( 'ratingRequired', $result['i18n'] );
		$this->assertArrayHasKey( 'reviewTooShort', $result['i18n'] );
	}

	/**
	 * Test render_star_input calls template function.
	 */
	public function test_render_star_input_structure(): void {
		Functions\expect( 'apd_get_template' )
			->once()
			->with(
				'review/star-input.php',
				Mockery::type( 'array' )
			);

		$this->form->render_star_input( 3, 5 );
	}

	/**
	 * Test init method exists and is callable.
	 */
	public function test_init_is_callable(): void {
		$this->assertTrue( method_exists( $this->form, 'init' ) );
	}

	/**
	 * Test render_review_section is callable.
	 */
	public function test_render_review_section_is_callable(): void {
		$this->assertTrue( method_exists( $this->form, 'render_review_section' ) );
	}
}
