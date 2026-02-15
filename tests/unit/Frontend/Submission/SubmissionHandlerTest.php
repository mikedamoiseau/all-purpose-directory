<?php
/**
 * SubmissionHandler Unit Tests.
 *
 * @package APD\Tests\Unit\Frontend\Submission
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Frontend\Submission;

use APD\Fields\FieldRegistry;
use APD\Fields\FieldValidator;
use APD\Fields\Types\TextField;
use APD\Frontend\Submission\SubmissionHandler;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Test class for SubmissionHandler.
 *
 * Tests the submission handler's validation, configuration, and basic logic.
 * Note: Integration tests in tests/integration cover full form submission flow.
 */
final class SubmissionHandlerTest extends UnitTestCase {

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Mock common WordPress functions.
		Functions\stubs( [
			'get_post'             => null,
			'get_current_user_id'  => 1,
			'is_user_logged_in'    => true,
			'current_user_can'     => true,
			'wp_get_referer'       => 'https://example.com/submit/',
			'home_url'             => 'https://example.com/',
			'admin_url'            => fn( $path ) => 'https://example.com/wp-admin/' . $path,
			'get_bloginfo'         => fn( $show ) => $show === 'name' ? 'Test Site' : '',
			'get_the_author_meta' => fn( $field, $user_id ) => 'Test User',
			'wp_max_upload_size'   => 5 * 1024 * 1024,
			'size_format'          => fn( $bytes ) => '5 MB',
			'wp_check_filetype'    => fn( $filename ) => [ 'type' => 'image/jpeg', 'ext' => 'jpg' ],
			'wp_safe_redirect'     => null,
			'esc_url_raw'          => fn( $url ) => $url,
			'add_query_arg'        => fn( $args, $url ) => $url . '?' . http_build_query( $args ),
			'remove_query_arg'     => fn( $keys, $url = '' ) => $url ?: 'https://example.com/submit/',
			'wp_verify_nonce'      => false,
			'wp_mail'              => true,
			'set_transient'        => true,
			'get_transient'        => false,
			'delete_transient'     => true,
			'sanitize_text_field'  => fn( $str ) => trim( strip_tags( (string) $str ) ),
			'sanitize_textarea_field' => fn( $str ) => trim( strip_tags( (string) $str ) ),
			'wp_kses_post'         => fn( $str ) => $str,
		] );
	}

	/**
	 * Test constructor sets default configuration.
	 */
	public function test_constructor_sets_default_config(): void {
		$handler = new SubmissionHandler();

		$this->assertSame( 'pending', $handler->get_config( 'default_status' ) );
		$this->assertTrue( $handler->get_config( 'require_login' ) );
		$this->assertTrue( $handler->get_config( 'require_title' ) );
		$this->assertTrue( $handler->get_config( 'require_content' ) );
		$this->assertFalse( $handler->get_config( 'require_category' ) );
		$this->assertFalse( $handler->get_config( 'require_featured_image' ) );
		$this->assertTrue( $handler->get_config( 'send_admin_notification' ) );
	}

	/**
	 * Test constructor merges custom configuration.
	 */
	public function test_constructor_merges_custom_config(): void {
		$handler = new SubmissionHandler( [
			'default_status'        => 'publish',
			'require_login'         => false,
			'require_category'      => true,
			'send_admin_notification' => false,
		] );

		$this->assertSame( 'publish', $handler->get_config( 'default_status' ) );
		$this->assertFalse( $handler->get_config( 'require_login' ) );
		$this->assertTrue( $handler->get_config( 'require_category' ) );
		$this->assertFalse( $handler->get_config( 'send_admin_notification' ) );
		// Default values should still be set.
		$this->assertTrue( $handler->get_config( 'require_title' ) );
		$this->assertTrue( $handler->get_config( 'require_content' ) );
	}

	/**
	 * Test get_config returns default for missing key.
	 */
	public function test_get_config_returns_default_for_missing_key(): void {
		$handler = new SubmissionHandler();

		$this->assertNull( $handler->get_config( 'nonexistent' ) );
		$this->assertSame( 'fallback', $handler->get_config( 'nonexistent', 'fallback' ) );
	}

	/**
	 * Test is_submission_request returns false for non-POST request.
	 */
	public function test_is_submission_request_false_for_non_post(): void {
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$handler = new SubmissionHandler();

		$this->assertFalse( $handler->is_submission_request() );
	}

	/**
	 * Test is_submission_request returns false without action.
	 */
	public function test_is_submission_request_false_without_action(): void {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		unset( $_POST['apd_action'] );

		$handler = new SubmissionHandler();

		$this->assertFalse( $handler->is_submission_request() );
	}

	/**
	 * Test is_submission_request returns false with wrong action.
	 */
	public function test_is_submission_request_false_with_wrong_action(): void {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['apd_action']       = 'some_other_action';

		$handler = new SubmissionHandler();

		$this->assertFalse( $handler->is_submission_request() );
	}

	/**
	 * Test is_submission_request returns true for valid submission.
	 */
	public function test_is_submission_request_true_for_valid_submission(): void {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['apd_action']       = 'submit_listing';

		$handler = new SubmissionHandler();

		$this->assertTrue( $handler->is_submission_request() );
	}

	/**
	 * Test process returns error for invalid nonce.
	 */
	public function test_process_returns_error_for_invalid_nonce(): void {
		$_SERVER['REQUEST_METHOD']      = 'POST';
		$_POST['apd_action']            = 'submit_listing';
		$_POST['apd_submission_nonce']  = 'invalid';

		Functions\when( 'wp_verify_nonce' )->justReturn( false );

		$handler = new SubmissionHandler();
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertContains( 'invalid_nonce', $result->get_error_codes() );
	}

	/**
	 * Test process returns error when not logged in and login required.
	 */
	public function test_process_returns_error_when_not_logged_in(): void {
		$_SERVER['REQUEST_METHOD']      = 'POST';
		$_POST['apd_action']            = 'submit_listing';
		$_POST['apd_submission_nonce']  = 'valid';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'is_user_logged_in' )->justReturn( false );

		$handler = new SubmissionHandler( [ 'require_login' => true ] );
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertContains( 'not_logged_in', $result->get_error_codes() );
	}

	/**
	 * Test process validates required title.
	 */
	public function test_process_validates_required_title(): void {
		$_SERVER['REQUEST_METHOD']      = 'POST';
		$_POST['apd_action']            = 'submit_listing';
		$_POST['apd_submission_nonce']  = 'valid';
		$_POST['listing_title']         = '';
		$_POST['listing_content']       = 'Test content';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'is_user_logged_in' )->justReturn( true );

		$handler = new SubmissionHandler();
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertContains( 'listing_title', $result->get_error_codes() );
	}

	/**
	 * Test process validates required content.
	 */
	public function test_process_validates_required_content(): void {
		$_SERVER['REQUEST_METHOD']      = 'POST';
		$_POST['apd_action']            = 'submit_listing';
		$_POST['apd_submission_nonce']  = 'valid';
		$_POST['listing_title']         = 'Test Title';
		$_POST['listing_content']       = '';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'is_user_logged_in' )->justReturn( true );

		$handler = new SubmissionHandler();
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertContains( 'listing_content', $result->get_error_codes() );
	}

	/**
	 * Test process validates required category when configured.
	 */
	public function test_process_validates_required_category(): void {
		$_SERVER['REQUEST_METHOD']      = 'POST';
		$_POST['apd_action']            = 'submit_listing';
		$_POST['apd_submission_nonce']  = 'valid';
		$_POST['listing_title']         = 'Test Title';
		$_POST['listing_content']       = 'Test content';
		$_POST['listing_categories']    = [];

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'is_user_logged_in' )->justReturn( true );

		$handler = new SubmissionHandler( [ 'require_category' => true ] );
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertContains( 'listing_categories', $result->get_error_codes() );
	}

	/**
	 * Test process merges featured image upload errors into the main error object.
	 */
	public function test_process_merges_featured_image_upload_errors(): void {
		$_SERVER['REQUEST_METHOD']      = 'POST';
		$_POST['apd_action']            = 'submit_listing';
		$_POST['apd_submission_nonce']  = 'valid';
		$_POST['listing_title']         = 'Test Title';
		$_POST['listing_content']       = 'Test content';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'is_user_logged_in' )->justReturn( true );

		$handler = new SubmissionHandler(
			[
				'require_featured_image' => true,
			]
		);
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertContains( 'featured_image', $result->get_error_codes() );
	}

	/**
	 * Test process merges custom field validation errors into the main error object.
	 */
	public function test_process_merges_custom_field_validation_errors(): void {
		FieldRegistry::reset_instance();
		$field_registry = FieldRegistry::get_instance();
		$field_registry->register_field_type( new TextField() );
		$field_registry->register_field(
			'company_name',
			[
				'type'     => 'text',
				'label'    => 'Company Name',
				'required' => true,
			]
		);

		$field_validator = new FieldValidator( $field_registry );

		$_SERVER['REQUEST_METHOD']      = 'POST';
		$_POST['apd_action']            = 'submit_listing';
		$_POST['apd_submission_nonce']  = 'valid';
		$_POST['listing_title']         = 'Test Title';
		$_POST['listing_content']       = 'Test content';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'is_user_logged_in' )->justReturn( true );

		$handler = new SubmissionHandler(
			[],
			$field_registry,
			$field_validator
		);
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertContains( 'company_name', $result->get_error_codes() );
	}

	/**
	 * Test get_submitted_data returns empty array before processing.
	 */
	public function test_get_submitted_data_returns_empty_before_processing(): void {
		$handler = new SubmissionHandler();

		$this->assertSame( [], $handler->get_submitted_data() );
	}

	/**
	 * Test get_errors returns WP_Error instance.
	 */
	public function test_get_errors_returns_wp_error(): void {
		$handler = new SubmissionHandler();

		$errors = $handler->get_errors();

		$this->assertInstanceOf( \WP_Error::class, $errors );
		$this->assertFalse( $errors->has_errors() );
	}

	/**
	 * Test constants are defined.
	 */
	public function test_constants_are_defined(): void {
		$this->assertSame( 'apd_submit_listing', SubmissionHandler::ACTION );
		$this->assertSame( 'apd_submit_listing', SubmissionHandler::NONCE_ACTION );
		$this->assertSame( 'apd_submission_nonce', SubmissionHandler::NONCE_NAME );
		$this->assertSame( 'apd_listing', SubmissionHandler::POST_TYPE );
	}

	/**
	 * Test init registers hook.
	 */
	public function test_init_registers_hook(): void {
		$hook_added = false;
		Functions\when( 'add_action' )->alias( function ( $hook, $callback ) use ( &$hook_added ) {
			if ( $hook === 'init' && is_array( $callback ) && $callback[1] === 'handle_submission' ) {
				$hook_added = true;
			}
		} );

		$handler = new SubmissionHandler();
		$handler->init();

		$this->assertTrue( $hook_added );
	}

	/**
	 * Test handle_submission does nothing for non-submission request.
	 */
	public function test_handle_submission_does_nothing_for_non_submission(): void {
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$handler = new SubmissionHandler();
		$handler->handle_submission();

		// If we get here without errors, the test passes.
		// The handler should return early without processing.
		$this->assertTrue( true );
	}

	// =========================================================================
	// Edit Mode Tests
	// =========================================================================

	/**
	 * Test process returns error when editing listing without permission.
	 */
	public function test_process_returns_error_when_editing_without_permission(): void {
		$_SERVER['REQUEST_METHOD']      = 'POST';
		$_POST['apd_action']            = 'submit_listing';
		$_POST['apd_submission_nonce']  = 'valid';
		$_POST['apd_listing_id']        = '123';
		$_POST['listing_title']         = 'Test Title';
		$_POST['listing_content']       = 'Test content';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		// Mock post that belongs to another user
		$mock_post = (object) [
			'ID'          => 123,
			'post_type'   => 'apd_listing',
			'post_author' => 999, // Different user
			'post_status' => 'publish',
		];
		Functions\when( 'get_post' )->justReturn( $mock_post );
		Functions\when( 'current_user_can' )->justReturn( false );

		$handler = new SubmissionHandler();
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertContains( 'permission_denied', $result->get_error_codes() );
	}

	/**
	 * Test process allows editing own listing.
	 */
	public function test_process_allows_editing_own_listing(): void {
		$_SERVER['REQUEST_METHOD']      = 'POST';
		$_POST['apd_action']            = 'submit_listing';
		$_POST['apd_submission_nonce']  = 'valid';
		$_POST['apd_listing_id']        = '123';
		$_POST['listing_title']         = 'Updated Title';
		$_POST['listing_content']       = 'Updated content';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		// Mock post that belongs to current user
		$mock_post = (object) [
			'ID'          => 123,
			'post_type'   => 'apd_listing',
			'post_author' => 1, // Same user
			'post_status' => 'publish',
		];
		Functions\when( 'get_post' )->justReturn( $mock_post );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'wp_update_post' )->justReturn( 123 );
		Functions\when( 'wp_set_object_terms' )->justReturn( [] );
		Functions\when( 'set_post_thumbnail' )->justReturn( true );
		Functions\when( 'delete_post_thumbnail' )->justReturn( true );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'wp_safe_redirect' )->alias( function () {
			// Prevent actual redirect
			throw new \Exception( 'Redirect called' );
		} );

		$handler = new SubmissionHandler();

		try {
			$result = $handler->process();
		} catch ( \Exception $e ) {
			if ( $e->getMessage() === 'Redirect called' ) {
				// This means the process was successful and reached the redirect
				$this->assertTrue( true );
				return;
			}
			throw $e;
		}

		// If no exception, check the result
		$this->assertSame( 123, $result );
	}

	/**
	 * Test process allows admin to edit any listing.
	 */
	public function test_process_allows_admin_to_edit_any_listing(): void {
		$_SERVER['REQUEST_METHOD']      = 'POST';
		$_POST['apd_action']            = 'submit_listing';
		$_POST['apd_submission_nonce']  = 'valid';
		$_POST['apd_listing_id']        = '123';
		$_POST['listing_title']         = 'Admin Updated Title';
		$_POST['listing_content']       = 'Admin updated content';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		// Mock post that belongs to another user
		$mock_post = (object) [
			'ID'          => 123,
			'post_type'   => 'apd_listing',
			'post_author' => 999, // Different user
			'post_status' => 'publish',
		];
		Functions\when( 'get_post' )->justReturn( $mock_post );

		// Admin has capability to edit
		Functions\when( 'current_user_can' )->alias( function ( $cap ) {
			return $cap === 'edit_apd_listing';
		} );

		Functions\when( 'wp_update_post' )->justReturn( 123 );
		Functions\when( 'wp_set_object_terms' )->justReturn( [] );
		Functions\when( 'set_post_thumbnail' )->justReturn( true );
		Functions\when( 'delete_post_thumbnail' )->justReturn( true );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'wp_safe_redirect' )->alias( function () {
			throw new \Exception( 'Redirect called' );
		} );

		$handler = new SubmissionHandler();

		try {
			$result = $handler->process();
		} catch ( \Exception $e ) {
			if ( $e->getMessage() === 'Redirect called' ) {
				$this->assertTrue( true );
				return;
			}
			throw $e;
		}

		$this->assertSame( 123, $result );
	}

	/**
	 * Test process returns error when editing non-existent listing.
	 */
	public function test_process_returns_error_for_non_existent_listing(): void {
		$_SERVER['REQUEST_METHOD']      = 'POST';
		$_POST['apd_action']            = 'submit_listing';
		$_POST['apd_submission_nonce']  = 'valid';
		$_POST['apd_listing_id']        = '999';
		$_POST['listing_title']         = 'Test Title';
		$_POST['listing_content']       = 'Test content';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'get_post' )->justReturn( null );

		$handler = new SubmissionHandler();
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertContains( 'permission_denied', $result->get_error_codes() );
	}

	/**
	 * Test process returns error when editing wrong post type.
	 */
	public function test_process_returns_error_for_wrong_post_type(): void {
		$_SERVER['REQUEST_METHOD']      = 'POST';
		$_POST['apd_action']            = 'submit_listing';
		$_POST['apd_submission_nonce']  = 'valid';
		$_POST['apd_listing_id']        = '123';
		$_POST['listing_title']         = 'Test Title';
		$_POST['listing_content']       = 'Test content';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		// Mock a regular post, not a listing
		$mock_post = (object) [
			'ID'          => 123,
			'post_type'   => 'post', // Wrong type
			'post_author' => 1,
		];
		Functions\when( 'get_post' )->justReturn( $mock_post );

		$handler = new SubmissionHandler();
		$result  = $handler->process();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertContains( 'permission_denied', $result->get_error_codes() );
	}

	/**
	 * Clean up after each test.
	 */
	protected function tearDown(): void {
		FieldRegistry::reset_instance();

		unset(
			$_SERVER['REQUEST_METHOD'],
			$_POST['apd_action'],
			$_POST['apd_submission_nonce'],
			$_POST['listing_title'],
			$_POST['listing_content'],
			$_POST['listing_excerpt'],
			$_POST['listing_categories'],
			$_POST['listing_tags'],
			$_POST['featured_image'],
			$_POST['terms_accepted'],
			$_POST['apd_redirect'],
			$_POST['apd_listing_id'],
			$_POST['apd_field'],
			$_FILES['featured_image_file']
		);

		parent::tearDown();
	}
}
