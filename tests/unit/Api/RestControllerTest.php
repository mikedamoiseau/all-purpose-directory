<?php
/**
 * RestController Unit Tests.
 *
 * @package APD\Tests\Unit\Api
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Api;

use APD\Api\RestController;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for RestController.
 */
final class RestControllerTest extends UnitTestCase {

	/**
	 * RestController instance.
	 *
	 * @var RestController
	 */
	private RestController $controller;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset singleton for clean tests.
		RestController::reset_instance();

		// Common mock setup.
		Functions\stubs( [
			'rest_url' => function ( $path = '' ) {
				return 'https://example.com/wp-json/' . ltrim( $path, '/' );
			},
			'wp_verify_nonce' => function ( $nonce, $action ) {
				return $nonce === 'valid_nonce' ? 1 : false;
			},
			'get_current_user_id' => 0,
			'get_userdata'        => false,
			'get_post'            => null,
			'current_user_can'    => false,
		] );

		$this->controller = RestController::get_instance();
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		RestController::reset_instance();
		parent::tearDown();
	}

	// =========================================================================
	// Singleton Tests
	// =========================================================================

	/**
	 * Test singleton pattern returns same instance.
	 */
	public function test_get_instance_returns_same_instance(): void {
		$instance1 = RestController::get_instance();
		$instance2 = RestController::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test reset_instance creates new instance.
	 */
	public function test_reset_instance_creates_new_instance(): void {
		$instance1 = RestController::get_instance();

		RestController::reset_instance();

		$instance2 = RestController::get_instance();

		$this->assertNotSame( $instance1, $instance2 );
	}

	// =========================================================================
	// Constants Tests
	// =========================================================================

	/**
	 * Test namespace constant.
	 */
	public function test_namespace_constant(): void {
		$this->assertSame( 'apd/v1', RestController::NAMESPACE );
	}

	/**
	 * Test version constant.
	 */
	public function test_version_constant(): void {
		$this->assertSame( 'v1', RestController::VERSION );
	}

	/**
	 * Test nonce action constant.
	 */
	public function test_nonce_action_constant(): void {
		$this->assertSame( 'wp_rest', RestController::NONCE_ACTION );
	}

	// =========================================================================
	// Initialization Tests
	// =========================================================================

	/**
	 * Test init marks controller as initialized.
	 */
	public function test_init_marks_initialized(): void {
		$this->assertFalse( $this->controller->is_initialized() );

		$this->controller->init();

		$this->assertTrue( $this->controller->is_initialized() );
	}

	/**
	 * Test init only runs once (verified by is_initialized).
	 */
	public function test_init_only_runs_once(): void {
		$this->controller->init();
		$first_state = $this->controller->is_initialized();

		$this->controller->init(); // Second call should not re-initialize.
		$second_state = $this->controller->is_initialized();

		$this->assertTrue( $first_state );
		$this->assertTrue( $second_state );
	}

	/**
	 * Test is_initialized returns false before init.
	 */
	public function test_is_initialized_returns_false_before_init(): void {
		$this->assertFalse( $this->controller->is_initialized() );
	}

	/**
	 * Test is_initialized returns true after init.
	 */
	public function test_is_initialized_returns_true_after_init(): void {
		$this->controller->init();

		$this->assertTrue( $this->controller->is_initialized() );
	}

	// =========================================================================
	// Namespace/Version Tests
	// =========================================================================

	/**
	 * Test get_namespace returns correct value.
	 */
	public function test_get_namespace(): void {
		$this->assertSame( 'apd/v1', $this->controller->get_namespace() );
	}

	/**
	 * Test get_version returns correct value.
	 */
	public function test_get_version(): void {
		$this->assertSame( 'v1', $this->controller->get_version() );
	}

	// =========================================================================
	// REST URL Tests
	// =========================================================================

	/**
	 * Test get_rest_url without route.
	 */
	public function test_get_rest_url_without_route(): void {
		$url = $this->controller->get_rest_url();

		$this->assertSame( 'https://example.com/wp-json/apd/v1', $url );
	}

	/**
	 * Test get_rest_url with route.
	 */
	public function test_get_rest_url_with_route(): void {
		$url = $this->controller->get_rest_url( 'listings' );

		$this->assertSame( 'https://example.com/wp-json/apd/v1/listings', $url );
	}

	/**
	 * Test get_rest_url strips leading slash.
	 */
	public function test_get_rest_url_strips_leading_slash(): void {
		$url = $this->controller->get_rest_url( '/listings/123' );

		$this->assertSame( 'https://example.com/wp-json/apd/v1/listings/123', $url );
	}

	// =========================================================================
	// Endpoint Registration Tests
	// =========================================================================

	/**
	 * Test register_endpoint adds endpoint.
	 */
	public function test_register_endpoint(): void {
		$endpoint = new \stdClass();

		$this->controller->register_endpoint( 'listings', $endpoint );

		$this->assertTrue( $this->controller->has_endpoint( 'listings' ) );
		$this->assertSame( $endpoint, $this->controller->get_endpoint( 'listings' ) );
	}

	/**
	 * Test unregister_endpoint removes endpoint.
	 */
	public function test_unregister_endpoint(): void {
		$endpoint = new \stdClass();
		$this->controller->register_endpoint( 'listings', $endpoint );

		$result = $this->controller->unregister_endpoint( 'listings' );

		$this->assertTrue( $result );
		$this->assertFalse( $this->controller->has_endpoint( 'listings' ) );
	}

	/**
	 * Test unregister_endpoint returns false for non-existent endpoint.
	 */
	public function test_unregister_endpoint_returns_false_for_nonexistent(): void {
		$result = $this->controller->unregister_endpoint( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test get_endpoint returns null for non-existent endpoint.
	 */
	public function test_get_endpoint_returns_null_for_nonexistent(): void {
		$this->assertNull( $this->controller->get_endpoint( 'nonexistent' ) );
	}

	/**
	 * Test get_endpoints returns all endpoints.
	 */
	public function test_get_endpoints(): void {
		$endpoint1 = new \stdClass();
		$endpoint2 = new \stdClass();

		$this->controller->register_endpoint( 'listings', $endpoint1 );
		$this->controller->register_endpoint( 'categories', $endpoint2 );

		$endpoints = $this->controller->get_endpoints();

		$this->assertCount( 2, $endpoints );
		$this->assertSame( $endpoint1, $endpoints['listings'] );
		$this->assertSame( $endpoint2, $endpoints['categories'] );
	}

	/**
	 * Test has_endpoint returns false for non-existent.
	 */
	public function test_has_endpoint_returns_false_for_nonexistent(): void {
		$this->assertFalse( $this->controller->has_endpoint( 'nonexistent' ) );
	}

	/**
	 * Test register_routes calls endpoint register_routes.
	 */
	public function test_register_routes_calls_endpoint_register_routes(): void {
		// Create a mock endpoint that tracks if register_routes was called.
		$endpoint = new class {
			public bool $register_routes_called = false;

			public function register_routes(): void {
				$this->register_routes_called = true;
			}
		};

		$this->controller->register_endpoint( 'listings', $endpoint );
		$this->controller->register_routes();

		$this->assertTrue( $endpoint->register_routes_called );
	}

	// =========================================================================
	// Nonce Verification Tests
	// =========================================================================

	/**
	 * Test verify_nonce with valid nonce.
	 */
	public function test_verify_nonce_with_valid_nonce(): void {
		$request = new \WP_REST_Request();
		$request->set_header( 'X-WP-Nonce', 'valid_nonce' );

		$result = $this->controller->verify_nonce( $request );

		$this->assertTrue( $result );
	}

	/**
	 * Test verify_nonce with invalid nonce.
	 */
	public function test_verify_nonce_with_invalid_nonce(): void {
		$request = new \WP_REST_Request();
		$request->set_header( 'X-WP-Nonce', 'invalid_nonce' );

		$result = $this->controller->verify_nonce( $request );

		$this->assertFalse( $result );
	}

	/**
	 * Test verify_nonce with missing nonce.
	 */
	public function test_verify_nonce_with_missing_nonce(): void {
		$request = new \WP_REST_Request();
		// No nonce header set.

		$result = $this->controller->verify_nonce( $request );

		$this->assertFalse( $result );
	}

	// =========================================================================
	// Authentication Tests
	// =========================================================================

	/**
	 * Test is_authenticated returns false when not logged in.
	 */
	public function test_is_authenticated_returns_false_when_not_logged_in(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$request = new \WP_REST_Request();

		$this->assertFalse( $this->controller->is_authenticated( $request ) );
	}

	/**
	 * Test is_authenticated returns true when logged in.
	 */
	public function test_is_authenticated_returns_true_when_logged_in(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		$request = new \WP_REST_Request();

		$this->assertTrue( $this->controller->is_authenticated( $request ) );
	}

	/**
	 * Test get_current_user returns null when not logged in.
	 */
	public function test_get_current_user_returns_null_when_not_logged_in(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$request = new \WP_REST_Request();

		$this->assertNull( $this->controller->get_current_user( $request ) );
	}

	/**
	 * Test get_current_user returns user when logged in.
	 */
	public function test_get_current_user_returns_user_when_logged_in(): void {
		$user = Mockery::mock( 'WP_User' );

		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'get_userdata' )->justReturn( $user );

		$request = new \WP_REST_Request();

		$result = $this->controller->get_current_user( $request );

		$this->assertSame( $user, $result );
	}

	// =========================================================================
	// Permission Callback Tests
	// =========================================================================

	/**
	 * Test permission_public always returns true.
	 */
	public function test_permission_public_returns_true(): void {
		$request = new \WP_REST_Request();

		$this->assertTrue( $this->controller->permission_public( $request ) );
	}

	/**
	 * Test permission_authenticated returns error when not logged in.
	 */
	public function test_permission_authenticated_returns_error_when_not_logged_in(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$request = new \WP_REST_Request();

		$result = $this->controller->permission_authenticated( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_not_logged_in', $result->get_error_code() );
	}

	/**
	 * Test permission_authenticated returns true when logged in.
	 */
	public function test_permission_authenticated_returns_true_when_logged_in(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		$request = new \WP_REST_Request();

		$this->assertTrue( $this->controller->permission_authenticated( $request ) );
	}

	/**
	 * Test permission_create_listing returns error when not logged in.
	 */
	public function test_permission_create_listing_returns_error_when_not_logged_in(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$request = new \WP_REST_Request();

		$result = $this->controller->permission_create_listing( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_not_logged_in', $result->get_error_code() );
		$this->assertSame( 'You must be logged in to create listings.', $result->get_error_message() );
	}

	/**
	 * Test permission_create_listing returns nonce error when nonce is missing.
	 */
	public function test_permission_create_listing_returns_nonce_error_when_missing_nonce(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		$request = new \WP_REST_Request();

		$result = $this->controller->permission_create_listing( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_nonce_invalid', $result->get_error_code() );
	}

	/**
	 * Test permission_create_listing returns error when user lacks capability.
	 */
	public function test_permission_create_listing_returns_error_when_no_capability(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->justReturn( false );

		$request = new \WP_REST_Request();
		$request->set_header( 'X-WP-Nonce', 'valid_nonce' );

		$result = $this->controller->permission_create_listing( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_forbidden', $result->get_error_code() );
	}

	/**
	 * Test permission_create_listing returns true when user has capability.
	 */
	public function test_permission_create_listing_returns_true_with_capability(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->justReturn( true );

		$request = new \WP_REST_Request();
		$request->set_header( 'X-WP-Nonce', 'valid_nonce' );

		$this->assertTrue( $this->controller->permission_create_listing( $request ) );
	}

	/**
	 * Test permission_edit_listing returns error when not logged in.
	 */
	public function test_permission_edit_listing_returns_error_when_not_logged_in(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 123 );

		$result = $this->controller->permission_edit_listing( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_not_logged_in', $result->get_error_code() );
		$this->assertSame( 'You must be logged in to edit listings.', $result->get_error_message() );
	}

	/**
	 * Test permission_edit_listing returns nonce error when nonce is missing.
	 */
	public function test_permission_edit_listing_returns_nonce_error_when_missing_nonce(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 123 );

		$result = $this->controller->permission_edit_listing( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_nonce_invalid', $result->get_error_code() );
	}

	/**
	 * Test permission_edit_listing returns error for invalid ID.
	 */
	public function test_permission_edit_listing_returns_error_for_invalid_id(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		$request = new \WP_REST_Request();
		$request->set_header( 'X-WP-Nonce', 'valid_nonce' );
		$request->set_param( 'id', 0 );

		$result = $this->controller->permission_edit_listing( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_invalid_param', $result->get_error_code() );
	}

	/**
	 * Test permission_edit_listing returns error when listing not found.
	 */
	public function test_permission_edit_listing_returns_error_when_listing_not_found(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'get_post' )->justReturn( null );

		$request = new \WP_REST_Request();
		$request->set_header( 'X-WP-Nonce', 'valid_nonce' );
		$request->set_param( 'id', 123 );

		$result = $this->controller->permission_edit_listing( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_listing_not_found', $result->get_error_code() );
	}

	/**
	 * Test permission_edit_listing returns error when post is wrong type.
	 */
	public function test_permission_edit_listing_returns_error_for_wrong_post_type(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		$post            = new \stdClass();
		$post->post_type = 'post';
		Functions\when( 'get_post' )->justReturn( $post );

		$request = new \WP_REST_Request();
		$request->set_header( 'X-WP-Nonce', 'valid_nonce' );
		$request->set_param( 'id', 123 );

		$result = $this->controller->permission_edit_listing( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_listing_not_found', $result->get_error_code() );
	}

	/**
	 * Test permission_edit_listing returns error when user cannot edit.
	 */
	public function test_permission_edit_listing_returns_error_when_cannot_edit(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->justReturn( false );

		$post            = new \stdClass();
		$post->post_type = 'apd_listing';
		Functions\when( 'get_post' )->justReturn( $post );

		$request = new \WP_REST_Request();
		$request->set_header( 'X-WP-Nonce', 'valid_nonce' );
		$request->set_param( 'id', 123 );

		$result = $this->controller->permission_edit_listing( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_forbidden', $result->get_error_code() );
	}

	/**
	 * Test permission_edit_listing returns true when user can edit.
	 */
	public function test_permission_edit_listing_returns_true_when_can_edit(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->justReturn( true );

		$post            = new \stdClass();
		$post->post_type = 'apd_listing';
		Functions\when( 'get_post' )->justReturn( $post );

		$request = new \WP_REST_Request();
		$request->set_header( 'X-WP-Nonce', 'valid_nonce' );
		$request->set_param( 'id', 123 );

		$this->assertTrue( $this->controller->permission_edit_listing( $request ) );
	}

	/**
	 * Test permission_delete_listing returns error when not logged in.
	 */
	public function test_permission_delete_listing_returns_error_when_not_logged_in(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 123 );

		$result = $this->controller->permission_delete_listing( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_not_logged_in', $result->get_error_code() );
		$this->assertSame( 'You must be logged in to delete listings.', $result->get_error_message() );
	}

	/**
	 * Test permission_delete_listing returns nonce error when nonce is missing.
	 */
	public function test_permission_delete_listing_returns_nonce_error_when_missing_nonce(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		$request = new \WP_REST_Request();
		$request->set_param( 'id', 123 );

		$result = $this->controller->permission_delete_listing( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_nonce_invalid', $result->get_error_code() );
	}

	/**
	 * Test permission_admin returns error when not logged in.
	 */
	public function test_permission_admin_returns_error_when_not_logged_in(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$request = new \WP_REST_Request();

		$result = $this->controller->permission_admin( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_not_logged_in', $result->get_error_code() );
	}

	/**
	 * Test permission_admin returns error when user is not admin.
	 */
	public function test_permission_admin_returns_error_when_not_admin(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->justReturn( false );

		$request = new \WP_REST_Request();

		$result = $this->controller->permission_admin( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_forbidden', $result->get_error_code() );
	}

	/**
	 * Test permission_admin returns true when user is admin.
	 */
	public function test_permission_admin_returns_true_for_admin(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->justReturn( true );

		$request = new \WP_REST_Request();

		$this->assertTrue( $this->controller->permission_admin( $request ) );
	}

	/**
	 * Test permission_manage_listings returns error when not logged in.
	 */
	public function test_permission_manage_listings_returns_error_when_not_logged_in(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$request = new \WP_REST_Request();

		$result = $this->controller->permission_manage_listings( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_not_logged_in', $result->get_error_code() );
	}

	/**
	 * Test permission_manage_listings returns error when lacking capability.
	 */
	public function test_permission_manage_listings_returns_error_without_capability(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->justReturn( false );

		$request = new \WP_REST_Request();

		$result = $this->controller->permission_manage_listings( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_forbidden', $result->get_error_code() );
	}

	/**
	 * Test permission_manage_listings returns true with capability.
	 */
	public function test_permission_manage_listings_returns_true_with_capability(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->justReturn( true );

		$request = new \WP_REST_Request();

		$this->assertTrue( $this->controller->permission_manage_listings( $request ) );
	}

	// =========================================================================
	// Response Helper Tests
	// =========================================================================

	/**
	 * Test create_response returns WP_REST_Response.
	 */
	public function test_create_response(): void {
		$data     = [ 'test' => 'value' ];
		$response = $this->controller->create_response( $data, 201 );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame( $data, $response->get_data() );
		$this->assertSame( 201, $response->get_status() );
	}

	/**
	 * Test create_response with headers.
	 */
	public function test_create_response_with_headers(): void {
		$response = $this->controller->create_response(
			[ 'test' => 'value' ],
			200,
			[ 'X-Custom-Header' => 'test' ]
		);

		$headers = $response->get_headers();
		$this->assertSame( 'test', $headers['X-Custom-Header'] );
	}

	/**
	 * Test create_error returns WP_Error.
	 */
	public function test_create_error(): void {
		$error = $this->controller->create_error( 'test_error', 'Test message', 400 );

		$this->assertInstanceOf( \WP_Error::class, $error );
		$this->assertSame( 'test_error', $error->get_error_code() );
		$this->assertSame( 'Test message', $error->get_error_message() );

		$data = $error->get_error_data();
		$this->assertSame( 400, $data['status'] );
	}

	/**
	 * Test create_error with additional data.
	 */
	public function test_create_error_with_additional_data(): void {
		$error = $this->controller->create_error(
			'test_error',
			'Test message',
			422,
			[ 'field' => 'email' ]
		);

		$data = $error->get_error_data();
		$this->assertSame( 422, $data['status'] );
		$this->assertSame( 'email', $data['field'] );
	}

	/**
	 * Test create_paginated_response structure.
	 */
	public function test_create_paginated_response(): void {
		$items    = [ [ 'id' => 1 ], [ 'id' => 2 ] ];
		$response = $this->controller->create_paginated_response(
			$items,
			25,  // total
			1,   // page
			10   // per_page
		);

		$data = $response->get_data();

		$this->assertSame( $items, $data['items'] );
		$this->assertSame( 25, $data['total'] );
		$this->assertSame( 1, $data['page'] );
		$this->assertSame( 10, $data['per_page'] );
		$this->assertSame( 3, $data['max_pages'] );
	}

	/**
	 * Test create_paginated_response headers.
	 */
	public function test_create_paginated_response_headers(): void {
		$response = $this->controller->create_paginated_response(
			[],
			50,
			2,
			10
		);

		$headers = $response->get_headers();

		$this->assertSame( '50', $headers['X-WP-Total'] );
		$this->assertSame( '5', $headers['X-WP-TotalPages'] );
	}

	/**
	 * Test create_paginated_response with extra data.
	 */
	public function test_create_paginated_response_with_extra_data(): void {
		$response = $this->controller->create_paginated_response(
			[],
			10,
			1,
			10,
			[ 'custom' => 'data' ]
		);

		$data = $response->get_data();

		$this->assertSame( 'data', $data['custom'] );
	}

	/**
	 * Test create_paginated_response handles zero per_page.
	 */
	public function test_create_paginated_response_handles_zero_per_page(): void {
		$response = $this->controller->create_paginated_response(
			[],
			10,
			1,
			0  // Edge case: zero per_page
		);

		$data = $response->get_data();

		// Should not cause division by zero.
		$this->assertSame( 10, $data['max_pages'] );
	}
}
