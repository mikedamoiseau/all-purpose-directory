<?php
/**
 * ReviewHandler Unit Tests.
 *
 * @package APD\Tests\Unit\Review
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Review;

use APD\Review\ReviewHandler;
use APD\Review\ReviewForm;
use APD\Review\ReviewManager;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;
use WP_Error;

/**
 * Test class for ReviewHandler.
 */
final class ReviewHandlerTest extends UnitTestCase {

	/**
	 * ReviewHandler instance.
	 *
	 * @var ReviewHandler
	 */
	private ReviewHandler $handler;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset ReviewHandler singleton.
		$this->reset_singleton( ReviewHandler::class );

		// Reset ReviewManager singleton (used by ReviewHandler).
		$this->reset_singleton( ReviewManager::class );

		// Common mock setup.
		Functions\stubs( [
			'get_current_user_id' => 1,
			'is_user_logged_in'   => true,
			'current_user_can'    => false,
			'get_post'            => function( $id ) {
				if ( $id <= 0 ) {
					return null;
				}
				$post              = new \stdClass();
				$post->ID          = $id;
				$post->post_type   = 'apd_listing';
				$post->post_status = 'publish';
				$post->post_author = 2;
				return $post;
			},
			'absint'              => function( $value ) {
				return abs( (int) $value );
			},
			'sanitize_text_field' => function( $value ) {
				return trim( strip_tags( $value ) );
			},
			'sanitize_email'      => function( $value ) {
				return filter_var( $value, FILTER_SANITIZE_EMAIL );
			},
			'wp_kses_post'        => function( $value ) {
				return $value;
			},
			'wp_unslash'          => function( $value ) {
				return $value;
			},
			'is_email'            => function( $email ) {
				return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false;
			},
			'apply_filters'       => function( $hook, $value, ...$args ) {
				return $value;
			},
			'do_action'           => function() {},
			'add_action'          => function() {},
			'__'                  => function( $text ) {
				return $text;
			},
		] );

		$this->handler = ReviewHandler::get_instance();
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
		$instance1 = ReviewHandler::get_instance();
		$instance2 = ReviewHandler::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test AJAX action constant.
	 */
	public function test_ajax_action_constant(): void {
		$this->assertSame( 'apd_submit_review', ReviewHandler::AJAX_ACTION );
	}

	/**
	 * Test validate returns error for missing listing ID.
	 */
	public function test_validate_returns_error_for_missing_listing_id(): void {
		$data = [
			'rating'  => 5,
			'content' => 'This is a great listing with enough content.',
		];

		$result = $this->handler->validate( $data );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
	}

	/**
	 * Test validate returns error for invalid listing ID.
	 */
	public function test_validate_returns_error_for_invalid_listing_id(): void {
		Functions\stubs( [
			'get_post' => null,
		] );

		$data = [
			'listing_id' => 999,
			'rating'     => 5,
			'content'    => 'This is a great listing with enough content.',
		];

		$result = $this->handler->validate( $data );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test validate returns error for missing rating.
	 */
	public function test_validate_returns_error_for_missing_rating(): void {
		$data = [
			'listing_id' => 123,
			'content'    => 'This is a great listing with enough content.',
		];

		$result = $this->handler->validate( $data );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test validate returns error for invalid rating below minimum.
	 */
	public function test_validate_returns_error_for_rating_below_minimum(): void {
		$data = [
			'listing_id' => 123,
			'rating'     => 0,
			'content'    => 'This is a great listing with enough content.',
		];

		$result = $this->handler->validate( $data );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test validate returns error for invalid rating above maximum.
	 */
	public function test_validate_returns_error_for_rating_above_maximum(): void {
		$data = [
			'listing_id' => 123,
			'rating'     => 6,
			'content'    => 'This is a great listing with enough content.',
		];

		$result = $this->handler->validate( $data );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test validate returns error for missing content.
	 */
	public function test_validate_returns_error_for_missing_content(): void {
		$data = [
			'listing_id' => 123,
			'rating'     => 5,
		];

		$result = $this->handler->validate( $data );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test validate returns error for empty content.
	 */
	public function test_validate_returns_error_for_empty_content(): void {
		$data = [
			'listing_id' => 123,
			'rating'     => 5,
			'content'    => '',
		];

		$result = $this->handler->validate( $data );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test validate returns error for content too short.
	 */
	public function test_validate_returns_error_for_content_too_short(): void {
		$data = [
			'listing_id' => 123,
			'rating'     => 5,
			'content'    => 'Short',
		];

		$result = $this->handler->validate( $data );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Test validate passes for valid data.
	 */
	public function test_validate_passes_for_valid_data(): void {
		$data = [
			'listing_id' => 123,
			'rating'     => 5,
			'content'    => 'This is a sufficiently long review content for testing.',
		];

		$result = $this->handler->validate( $data );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate returns error for rating of exactly 1 (minimum edge case).
	 */
	public function test_validate_accepts_minimum_rating(): void {
		$data = [
			'listing_id' => 123,
			'rating'     => 1,
			'content'    => 'This is a sufficiently long review content for testing.',
		];

		$result = $this->handler->validate( $data );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate accepts maximum rating.
	 */
	public function test_validate_accepts_maximum_rating(): void {
		$data = [
			'listing_id' => 123,
			'rating'     => 5,
			'content'    => 'This is a sufficiently long review content for testing.',
		];

		$result = $this->handler->validate( $data );

		$this->assertTrue( $result );
	}

	/**
	 * Test get_errors returns WP_Error instance.
	 */
	public function test_get_errors_returns_wp_error(): void {
		$errors = $this->handler->get_errors();

		$this->assertInstanceOf( WP_Error::class, $errors );
	}

	/**
	 * Test init method exists and is callable.
	 */
	public function test_init_is_callable(): void {
		$this->assertTrue( method_exists( $this->handler, 'init' ) );
	}

	/**
	 * Test validate method exists and is public.
	 */
	public function test_validate_method_exists(): void {
		$this->assertTrue( method_exists( $this->handler, 'validate' ) );
	}

	/**
	 * Test content exactly at minimum length passes.
	 */
	public function test_validate_content_at_minimum_length(): void {
		$data = [
			'listing_id' => 123,
			'rating'     => 5,
			'content'    => '1234567890', // Exactly 10 characters (default minimum).
		];

		$result = $this->handler->validate( $data );

		$this->assertTrue( $result );
	}

	/**
	 * Test content just below minimum length fails.
	 */
	public function test_validate_content_below_minimum_length(): void {
		$data = [
			'listing_id' => 123,
			'rating'     => 5,
			'content'    => '123456789', // 9 characters (below default minimum of 10).
		];

		$result = $this->handler->validate( $data );

		$this->assertInstanceOf( WP_Error::class, $result );
	}
}
