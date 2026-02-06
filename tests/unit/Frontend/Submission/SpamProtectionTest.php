<?php
/**
 * Spam Protection Unit Tests.
 *
 * @package APD\Tests\Unit\Frontend\Submission
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Frontend\Submission;

use APD\Frontend\Submission\SubmissionForm;
use APD\Frontend\Submission\SubmissionHandler;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Test class for Spam Protection functionality.
 *
 * Tests honeypot detection, rate limiting, time-based protection,
 * and custom spam check filters.
 */
final class SpamProtectionTest extends UnitTestCase {

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Define WordPress constants if not defined.
		if ( ! defined( 'DAY_IN_SECONDS' ) ) {
			define( 'DAY_IN_SECONDS', 86400 );
		}
		if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
			define( 'HOUR_IN_SECONDS', 3600 );
		}

		// Mock common WordPress functions.
		Functions\stubs( [
			'get_current_user_id'     => 1,
			'is_user_logged_in'       => true,
			'wp_verify_nonce'         => true,
			'current_user_can'        => true,
			'get_post'                => null,
			'wp_get_referer'          => 'https://example.com/submit/',
			'home_url'                => 'https://example.com/',
			'sanitize_text_field'     => fn( $str ) => trim( strip_tags( (string) $str ) ),
			'sanitize_textarea_field' => fn( $str ) => trim( strip_tags( (string) $str ) ),
			'wp_kses_post'            => fn( $str ) => $str,
			'wp_unslash'              => fn( $val ) => $val,
			'get_transient'           => false,
			'set_transient'           => true,
			'delete_transient'        => true,
			'esc_attr'                => fn( $str ) => htmlspecialchars( (string) $str, ENT_QUOTES, 'UTF-8' ),
			'esc_html'                => fn( $str ) => htmlspecialchars( (string) $str, ENT_QUOTES, 'UTF-8' ),
			'esc_html__'              => fn( $str ) => $str,
			'__'                      => fn( $str ) => $str,
			'get_terms'               => [],
			'wp_salt'                 => 'test-salt-value',
		] );
	}

	/**
	 * Generate a signed form token for testing.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return string Signed base64-encoded token.
	 */
	private function generate_signed_token( int $timestamp ): string {
		$ts        = (string) $timestamp;
		$signature = hash_hmac( 'sha256', $ts, 'test-salt-value' );

		return base64_encode( $ts . '|' . $signature );
	}

	// =========================================================================
	// Honeypot Field Tests
	// =========================================================================

	/**
	 * Test honeypot field is rendered when spam protection is enabled.
	 */
	public function test_honeypot_field_rendered_when_enabled(): void {
		$form = new SubmissionForm( [ 'enable_spam_protection' => true ] );
		$html = $form->render_honeypot_field();

		$this->assertStringContainsString( 'apd-field--hp', $html );
		$this->assertStringContainsString( 'website_url', $html );
		$this->assertStringContainsString( 'aria-hidden="true"', $html );
		$this->assertStringContainsString( 'tabindex="-1"', $html );
	}

	/**
	 * Test honeypot field is not rendered when spam protection is disabled.
	 */
	public function test_honeypot_field_not_rendered_when_disabled(): void {
		$form = new SubmissionForm( [ 'enable_spam_protection' => false ] );
		$html = $form->render_honeypot_field();

		$this->assertEmpty( $html );
	}

	/**
	 * Test honeypot field name is filterable.
	 */
	public function test_honeypot_field_name_is_filterable(): void {
		// The filter is applied via apply_filters, which Brain\Monkey tracks.
		Functions\when( 'apply_filters' )->alias( function ( $hook, $value, ...$args ) {
			if ( $hook === 'apd_honeypot_field_name' ) {
				return 'custom_field_name';
			}
			return $value;
		} );

		$form       = new SubmissionForm( [ 'enable_spam_protection' => true ] );
		$field_name = $form->get_honeypot_field_name();

		$this->assertSame( 'custom_field_name', $field_name );
	}

	/**
	 * Test submission fails when honeypot is filled.
	 */
	public function test_submission_fails_when_honeypot_filled(): void {
		$_SERVER['REQUEST_METHOD']     = 'POST';
		$_POST['apd_action']           = 'submit_listing';
		$_POST['apd_submission_nonce'] = 'valid';
		$_POST['listing_title']        = 'Test Title';
		$_POST['listing_content']      = 'Test content';
		$_POST['website_url']          = 'https://spam-site.com'; // Bot filled honeypot.
		$_POST['apd_form_token']       = $this->generate_signed_token( time() - 10 );

		$handler = new SubmissionHandler( [ 'enable_spam_protection' => true ] );
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertContains( 'submission_failed', $result->get_error_codes() );
	}

	/**
	 * Test submission passes when honeypot is empty.
	 */
	public function test_submission_passes_when_honeypot_empty(): void {
		$_SERVER['REQUEST_METHOD']     = 'POST';
		$_POST['apd_action']           = 'submit_listing';
		$_POST['apd_submission_nonce'] = 'valid';
		$_POST['listing_title']        = 'Test Title';
		$_POST['listing_content']      = 'Test content';
		$_POST['website_url']          = ''; // Empty honeypot.
		$_POST['apd_form_token']       = $this->generate_signed_token( time() - 10 );

		Functions\when( 'wp_insert_post' )->justReturn( 123 );
		Functions\when( 'wp_set_object_terms' )->justReturn( [] );
		Functions\when( 'delete_post_thumbnail' )->justReturn( true );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'wp_safe_redirect' )->alias( function () {
			throw new \Exception( 'Redirect called' );
		} );

		$handler = new SubmissionHandler( [ 'enable_spam_protection' => true ] );

		try {
			$result = $handler->process();
		} catch ( \Exception $e ) {
			if ( $e->getMessage() === 'Redirect called' ) {
				// Success - the form processed and redirected.
				$this->assertTrue( true );
				return;
			}
			throw $e;
		}

		$this->assertSame( 123, $result );
	}

	// =========================================================================
	// Timestamp/Timing Tests
	// =========================================================================

	/**
	 * Test timestamp field is rendered when spam protection is enabled.
	 */
	public function test_timestamp_field_rendered_when_enabled(): void {
		$form = new SubmissionForm( [ 'enable_spam_protection' => true ] );
		$html = $form->render_timestamp_field();

		$this->assertStringContainsString( 'type="hidden"', $html );
		$this->assertStringContainsString( 'name="apd_form_token"', $html );
		$this->assertStringContainsString( 'value=', $html );
	}

	/**
	 * Test timestamp field is not rendered when spam protection is disabled.
	 */
	public function test_timestamp_field_not_rendered_when_disabled(): void {
		$form = new SubmissionForm( [ 'enable_spam_protection' => false ] );
		$html = $form->render_timestamp_field();

		$this->assertEmpty( $html );
	}

	/**
	 * Test form timestamp is base64 encoded.
	 */
	public function test_form_timestamp_is_encoded(): void {
		$form      = new SubmissionForm( [ 'enable_spam_protection' => true ] );
		$timestamp = $form->get_form_timestamp();

		// Should be base64 encoded.
		$decoded = base64_decode( $timestamp, true );
		$this->assertNotFalse( $decoded );

		// Should decode to a recent timestamp.
		$time = (int) $decoded;
		$this->assertGreaterThan( time() - 60, $time );
		$this->assertLessThanOrEqual( time(), $time );
	}

	/**
	 * Test submission fails when submitted too quickly.
	 */
	public function test_submission_fails_when_too_fast(): void {
		$_SERVER['REQUEST_METHOD']     = 'POST';
		$_POST['apd_action']           = 'submit_listing';
		$_POST['apd_submission_nonce'] = 'valid';
		$_POST['listing_title']        = 'Test Title';
		$_POST['listing_content']      = 'Test content';
		$_POST['website_url']          = '';
		// Submit immediately (0 seconds elapsed).
		$_POST['apd_form_token'] = $this->generate_signed_token( time() );

		$handler = new SubmissionHandler( [ 'enable_spam_protection' => true ] );
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertContains( 'submission_failed', $result->get_error_codes() );
	}

	/**
	 * Test submission passes when enough time has elapsed.
	 */
	public function test_submission_passes_with_normal_timing(): void {
		$_SERVER['REQUEST_METHOD']     = 'POST';
		$_POST['apd_action']           = 'submit_listing';
		$_POST['apd_submission_nonce'] = 'valid';
		$_POST['listing_title']        = 'Test Title';
		$_POST['listing_content']      = 'Test content';
		$_POST['website_url']          = '';
		// 10 seconds elapsed (more than 3 second minimum).
		$_POST['apd_form_token'] = $this->generate_signed_token( time() - 10 );

		Functions\when( 'wp_insert_post' )->justReturn( 456 );
		Functions\when( 'wp_set_object_terms' )->justReturn( [] );
		Functions\when( 'delete_post_thumbnail' )->justReturn( true );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'wp_safe_redirect' )->alias( function () {
			throw new \Exception( 'Redirect called' );
		} );

		$handler = new SubmissionHandler( [ 'enable_spam_protection' => true ] );

		try {
			$result = $handler->process();
		} catch ( \Exception $e ) {
			if ( $e->getMessage() === 'Redirect called' ) {
				$this->assertTrue( true );
				return;
			}
			throw $e;
		}

		$this->assertSame( 456, $result );
	}

	/**
	 * Test minimum time is filterable.
	 */
	public function test_minimum_time_is_filterable(): void {
		$_SERVER['REQUEST_METHOD']     = 'POST';
		$_POST['apd_action']           = 'submit_listing';
		$_POST['apd_submission_nonce'] = 'valid';
		$_POST['listing_title']        = 'Test Title';
		$_POST['listing_content']      = 'Test content';
		$_POST['website_url']          = '';
		// 2 seconds elapsed.
		$_POST['apd_form_token'] = $this->generate_signed_token( time() - 2 );

		// Set minimum time to 1 second via filter.
		Functions\when( 'apply_filters' )->alias( function ( $hook, $value, ...$args ) {
			if ( $hook === 'apd_submission_min_time' ) {
				return 1; // 1 second minimum.
			}
			return $value;
		} );

		Functions\when( 'wp_insert_post' )->justReturn( 789 );
		Functions\when( 'wp_set_object_terms' )->justReturn( [] );
		Functions\when( 'delete_post_thumbnail' )->justReturn( true );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'wp_safe_redirect' )->alias( function () {
			throw new \Exception( 'Redirect called' );
		} );

		$handler = new SubmissionHandler( [ 'enable_spam_protection' => true ] );

		try {
			$result = $handler->process();
		} catch ( \Exception $e ) {
			if ( $e->getMessage() === 'Redirect called' ) {
				$this->assertTrue( true );
				return;
			}
			throw $e;
		}

		$this->assertSame( 789, $result );
	}

	/**
	 * Test submission fails with invalid timestamp (future date).
	 */
	public function test_submission_fails_with_future_timestamp(): void {
		$_SERVER['REQUEST_METHOD']     = 'POST';
		$_POST['apd_action']           = 'submit_listing';
		$_POST['apd_submission_nonce'] = 'valid';
		$_POST['listing_title']        = 'Test Title';
		$_POST['listing_content']      = 'Test content';
		$_POST['website_url']          = '';
		// Timestamp in the future (invalid).
		$_POST['apd_form_token'] = $this->generate_signed_token( time() + 3600 );

		$handler = new SubmissionHandler( [ 'enable_spam_protection' => true ] );
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
	}

	/**
	 * Test submission fails with invalid base64 token.
	 */
	public function test_submission_fails_with_invalid_token(): void {
		$_SERVER['REQUEST_METHOD']     = 'POST';
		$_POST['apd_action']           = 'submit_listing';
		$_POST['apd_submission_nonce'] = 'valid';
		$_POST['listing_title']        = 'Test Title';
		$_POST['listing_content']      = 'Test content';
		$_POST['website_url']          = '';
		$_POST['apd_form_token']       = 'invalid_not_base64!!!';

		$handler = new SubmissionHandler( [ 'enable_spam_protection' => true ] );
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
	}

	// =========================================================================
	// Rate Limiting Tests
	// =========================================================================

	/**
	 * Test rate limit check passes when under limit.
	 */
	public function test_rate_limit_passes_when_under_limit(): void {
		$_SERVER['REQUEST_METHOD']     = 'POST';
		$_POST['apd_action']           = 'submit_listing';
		$_POST['apd_submission_nonce'] = 'valid';
		$_POST['listing_title']        = 'Test Title';
		$_POST['listing_content']      = 'Test content';
		$_POST['website_url']          = '';
		$_POST['apd_form_token']       = $this->generate_signed_token( time() - 10 );

		// Simulate 2 previous submissions (under default limit of 5).
		Functions\when( 'get_transient' )->justReturn( 2 );

		Functions\when( 'wp_insert_post' )->justReturn( 111 );
		Functions\when( 'wp_set_object_terms' )->justReturn( [] );
		Functions\when( 'delete_post_thumbnail' )->justReturn( true );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'wp_safe_redirect' )->alias( function () {
			throw new \Exception( 'Redirect called' );
		} );

		$handler = new SubmissionHandler( [ 'enable_spam_protection' => true ] );

		try {
			$result = $handler->process();
		} catch ( \Exception $e ) {
			if ( $e->getMessage() === 'Redirect called' ) {
				$this->assertTrue( true );
				return;
			}
			throw $e;
		}

		$this->assertSame( 111, $result );
	}

	/**
	 * Test rate limit fails when at limit.
	 */
	public function test_rate_limit_fails_when_at_limit(): void {
		$_SERVER['REQUEST_METHOD']     = 'POST';
		$_POST['apd_action']           = 'submit_listing';
		$_POST['apd_submission_nonce'] = 'valid';
		$_POST['listing_title']        = 'Test Title';
		$_POST['listing_content']      = 'Test content';
		$_POST['website_url']          = '';
		$_POST['apd_form_token']       = $this->generate_signed_token( time() - 10 );

		// Simulate 5 previous submissions (at default limit of 5).
		Functions\when( 'get_transient' )->justReturn( 5 );

		$handler = new SubmissionHandler( [ 'enable_spam_protection' => true ] );
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertContains( 'rate_limited', $result->get_error_codes() );
	}

	/**
	 * Test rate limit is filterable.
	 */
	public function test_rate_limit_is_filterable(): void {
		$_SERVER['REQUEST_METHOD']     = 'POST';
		$_POST['apd_action']           = 'submit_listing';
		$_POST['apd_submission_nonce'] = 'valid';
		$_POST['listing_title']        = 'Test Title';
		$_POST['listing_content']      = 'Test content';
		$_POST['website_url']          = '';
		$_POST['apd_form_token']       = $this->generate_signed_token( time() - 10 );

		// Simulate 3 previous submissions.
		Functions\when( 'get_transient' )->justReturn( 3 );

		// Set limit to 2 via filter (lower than current count).
		Functions\when( 'apply_filters' )->alias( function ( $hook, $value, ...$args ) {
			if ( $hook === 'apd_submission_rate_limit' ) {
				return 2; // Lower limit.
			}
			return $value;
		} );

		$handler = new SubmissionHandler( [ 'enable_spam_protection' => true ] );
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertContains( 'rate_limited', $result->get_error_codes() );
	}

	// =========================================================================
	// Bypass Tests
	// =========================================================================

	/**
	 * Test spam protection can be bypassed via filter.
	 */
	public function test_spam_protection_can_be_bypassed(): void {
		$_SERVER['REQUEST_METHOD']     = 'POST';
		$_POST['apd_action']           = 'submit_listing';
		$_POST['apd_submission_nonce'] = 'valid';
		$_POST['listing_title']        = 'Test Title';
		$_POST['listing_content']      = 'Test content';
		$_POST['website_url']          = 'https://spam.com'; // Honeypot filled.
		$_POST['apd_form_token']       = $this->generate_signed_token( time() ); // Too fast.

		// Bypass spam protection using apply_filters stub.
		Functions\when( 'apply_filters' )->alias( function ( $hook, $value, ...$args ) {
			if ( $hook === 'apd_bypass_spam_protection' ) {
				return true; // Bypass.
			}
			return $value;
		} );

		Functions\when( 'wp_insert_post' )->justReturn( 222 );
		Functions\when( 'wp_set_object_terms' )->justReturn( [] );
		Functions\when( 'delete_post_thumbnail' )->justReturn( true );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'wp_safe_redirect' )->alias( function () {
			throw new \Exception( 'Redirect called' );
		} );

		$handler = new SubmissionHandler( [ 'enable_spam_protection' => true ] );

		try {
			$result = $handler->process();
		} catch ( \Exception $e ) {
			if ( $e->getMessage() === 'Redirect called' ) {
				// Spam checks bypassed, form processed.
				$this->assertTrue( true );
				return;
			}
			throw $e;
		}

		$this->assertSame( 222, $result );
	}

	/**
	 * Test spam protection is disabled via config.
	 */
	public function test_spam_protection_disabled_via_config(): void {
		$_SERVER['REQUEST_METHOD']     = 'POST';
		$_POST['apd_action']           = 'submit_listing';
		$_POST['apd_submission_nonce'] = 'valid';
		$_POST['listing_title']        = 'Test Title';
		$_POST['listing_content']      = 'Test content';
		$_POST['website_url']          = 'https://spam.com'; // Honeypot filled.
		// No timestamp token.

		Functions\when( 'wp_insert_post' )->justReturn( 333 );
		Functions\when( 'wp_set_object_terms' )->justReturn( [] );
		Functions\when( 'delete_post_thumbnail' )->justReturn( true );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'wp_safe_redirect' )->alias( function () {
			throw new \Exception( 'Redirect called' );
		} );

		// Disable spam protection via config.
		$handler = new SubmissionHandler( [ 'enable_spam_protection' => false ] );

		try {
			$result = $handler->process();
		} catch ( \Exception $e ) {
			if ( $e->getMessage() === 'Redirect called' ) {
				// Spam checks disabled, form processed.
				$this->assertTrue( true );
				return;
			}
			throw $e;
		}

		$this->assertSame( 333, $result );
	}

	// =========================================================================
	// Custom Spam Check Filter Tests
	// =========================================================================

	/**
	 * Test custom spam check filter can block submission.
	 */
	public function test_custom_spam_check_filter_can_block(): void {
		$_SERVER['REQUEST_METHOD']     = 'POST';
		$_POST['apd_action']           = 'submit_listing';
		$_POST['apd_submission_nonce'] = 'valid';
		$_POST['listing_title']        = 'Test Title';
		$_POST['listing_content']      = 'Test content';
		$_POST['website_url']          = '';
		$_POST['apd_form_token']       = $this->generate_signed_token( time() - 10 );

		// Return WP_Error from custom filter.
		Functions\when( 'apply_filters' )->alias( function ( $hook, $value, ...$args ) {
			if ( $hook === 'apd_submission_spam_check' ) {
				return new \WP_Error( 'custom_spam', 'Custom spam detected' );
			}
			return $value;
		} );

		$handler = new SubmissionHandler( [ 'enable_spam_protection' => true ] );
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertContains( 'custom_spam', $result->get_error_codes() );
	}

	/**
	 * Test custom spam check filter receives correct parameters.
	 */
	public function test_custom_spam_check_filter_receives_parameters(): void {
		$_SERVER['REQUEST_METHOD']     = 'POST';
		$_POST['apd_action']           = 'submit_listing';
		$_POST['apd_submission_nonce'] = 'valid';
		$_POST['listing_title']        = 'Test Title';
		$_POST['listing_content']      = 'Test content';
		$_POST['website_url']          = '';
		$_POST['apd_form_token']       = $this->generate_signed_token( time() - 10 );

		$received_params = [];

		Functions\when( 'apply_filters' )->alias( function ( $hook, $value, ...$args ) use ( &$received_params ) {
			if ( $hook === 'apd_submission_spam_check' ) {
				$received_params = [
					'result'    => $value,
					'post_data' => $args[0] ?? null,
					'user_id'   => $args[1] ?? null,
				];
				return true;
			}
			return $value;
		} );

		Functions\when( 'wp_insert_post' )->justReturn( 444 );
		Functions\when( 'wp_set_object_terms' )->justReturn( [] );
		Functions\when( 'delete_post_thumbnail' )->justReturn( true );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'wp_safe_redirect' )->alias( function () {
			throw new \Exception( 'Redirect called' );
		} );

		$handler = new SubmissionHandler( [ 'enable_spam_protection' => true ] );

		try {
			$handler->process();
		} catch ( \Exception $e ) {
			// Expected.
		}

		$this->assertTrue( $received_params['result'] );
		$this->assertIsArray( $received_params['post_data'] );
		$this->assertSame( 1, $received_params['user_id'] );
	}

	// =========================================================================
	// Spam Attempt Logging Tests
	// =========================================================================

	/**
	 * Test spam attempt action is fired for honeypot.
	 */
	public function test_spam_attempt_action_fired_for_honeypot(): void {
		$_SERVER['REQUEST_METHOD']     = 'POST';
		$_POST['apd_action']           = 'submit_listing';
		$_POST['apd_submission_nonce'] = 'valid';
		$_POST['listing_title']        = 'Test Title';
		$_POST['listing_content']      = 'Test content';
		$_POST['website_url']          = 'filled-by-bot';
		$_POST['apd_form_token']       = $this->generate_signed_token( time() - 10 );

		$action_fired = false;
		$action_type  = null;

		Functions\when( 'do_action' )->alias( function ( $hook, ...$args ) use ( &$action_fired, &$action_type ) {
			if ( $hook === 'apd_spam_attempt_detected' ) {
				$action_fired = true;
				$action_type  = $args[0] ?? null;
			}
		} );

		$handler = new SubmissionHandler( [ 'enable_spam_protection' => true ] );
		$handler->process();

		$this->assertTrue( $action_fired );
		$this->assertSame( 'honeypot', $action_type );
	}

	// =========================================================================
	// Form Configuration Tests
	// =========================================================================

	/**
	 * Test spam protection enabled config is passed to template.
	 */
	public function test_spam_protection_config_in_form_args(): void {
		$received_args = null;

		Functions\when( 'apd_get_template' )->alias( function ( $template, $args ) use ( &$received_args ) {
			$received_args = $args;
		} );

		// Stub additional functions needed for render.
		Functions\when( 'get_terms' )->justReturn( [] );
		Functions\when( 'get_post_meta' )->justReturn( '' );

		$form = new SubmissionForm( [ 'enable_spam_protection' => true ] );
		$form->render();

		$this->assertNotNull( $received_args );
		$this->assertArrayHasKey( 'spam_protection_enabled', $received_args );
		$this->assertTrue( $received_args['spam_protection_enabled'] );
		$this->assertArrayHasKey( 'honeypot_field_html', $received_args );
		$this->assertArrayHasKey( 'timestamp_field_html', $received_args );
	}

	/**
	 * Clean up after each test.
	 */
	protected function tearDown(): void {
		unset(
			$_SERVER['REQUEST_METHOD'],
			$_POST['apd_action'],
			$_POST['apd_submission_nonce'],
			$_POST['listing_title'],
			$_POST['listing_content'],
			$_POST['website_url'],
			$_POST['apd_form_token']
		);

		parent::tearDown();
	}
}
