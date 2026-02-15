<?php
/**
 * Unit tests for InquiriesEndpoint class.
 *
 * @package APD\Tests\Unit\Api\Endpoints
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Api\Endpoints;

use APD\Api\Endpoints\InquiriesEndpoint;
use APD\Api\RestController;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Class InquiriesEndpointTest
 *
 * Tests for the InquiriesEndpoint class.
 */
class InquiriesEndpointTest extends UnitTestCase {

	/**
	 * Rest controller instance.
	 *
	 * @var RestController
	 */
	private RestController $controller;

	/**
	 * Endpoint instance.
	 *
	 * @var InquiriesEndpoint
	 */
	private InquiriesEndpoint $endpoint;

	/**
	 * Set up test environment.
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
		] );

		$this->controller = RestController::get_instance();
		$this->endpoint   = new InquiriesEndpoint( $this->controller );
	}

	/**
	 * Tear down test environment.
	 */
	protected function tearDown(): void {
		RestController::reset_instance();
		parent::tearDown();
	}

	/**
	 * Create a mock WP_REST_Request.
	 *
	 * @param array $params Request parameters.
	 * @return \WP_REST_Request|Mockery\MockInterface
	 */
	private function create_mock_request( array $params = [] ): \WP_REST_Request {
		$request = Mockery::mock( \WP_REST_Request::class );

		foreach ( $params as $key => $value ) {
			$request->shouldReceive( 'get_param' )
				->with( $key )
				->andReturn( $value );
		}

		// Default to null for any param not explicitly set.
		$request->shouldReceive( 'get_param' )->andReturn( null );

		return $request;
	}

	/**
	 * Create a mock WP_Post.
	 *
	 * @param array $data Post data.
	 * @return \WP_Post
	 */
	private function create_mock_post( array $data = [] ): \WP_Post {
		$defaults = [
			'ID'           => 1,
			'post_title'   => 'Test Listing',
			'post_name'    => 'test-listing',
			'post_type'    => 'apd_listing',
			'post_status'  => 'publish',
			'post_author'  => '1',
		];

		$data = array_merge( $defaults, $data );
		$post = new \WP_Post( (object) $data );
		foreach ( $data as $key => $value ) {
			$post->$key = $value;
		}

		return $post;
	}

	/**
	 * Create sample inquiry data.
	 *
	 * @param array $overrides Override values.
	 * @return array Inquiry data.
	 */
	private function create_inquiry_data( array $overrides = [] ): array {
		return array_merge( [
			'id'           => 1,
			'listing_id'   => 1,
			'sender_name'  => 'John Doe',
			'sender_email' => 'john@example.com',
			'sender_phone' => '555-1234',
			'subject'      => 'Question about listing',
			'message'      => 'I would like more information about this listing.',
			'is_read'      => false,
			'date'         => '2024-01-15 10:30:00',
		], $overrides );
	}

	// =========================================================================
	// Constructor Tests
	// =========================================================================

	/**
	 * Test constructor creates endpoint.
	 */
	public function test_constructor_creates_endpoint(): void {
		$this->assertInstanceOf( InquiriesEndpoint::class, $this->endpoint );
	}

	// =========================================================================
	// Route Registration Tests
	// =========================================================================

	/**
	 * Test register_routes registers routes.
	 */
	public function test_register_routes_registers_inquiry_routes(): void {
		Functions\expect( 'register_rest_route' )
			->times( 5 ) // /inquiries, /inquiries/{id}, /inquiries/{id}/read, /inquiries/{id}/unread, /listings/{id}/inquiries
			->andReturn( true );

		$this->endpoint->register_routes();

		$this->assertTrue( true );
	}

	// =========================================================================
	// Parameters Tests
	// =========================================================================

	/**
	 * Test get_collection_params returns expected keys.
	 */
	public function test_get_collection_params_returns_expected_keys(): void {
		$params = $this->endpoint->get_collection_params();

		$this->assertArrayHasKey( 'status', $params );
		$this->assertArrayHasKey( 'page', $params );
		$this->assertArrayHasKey( 'per_page', $params );
		$this->assertArrayHasKey( 'orderby', $params );
		$this->assertArrayHasKey( 'order', $params );
	}

	/**
	 * Test get_collection_params status enum.
	 */
	public function test_get_collection_params_status_enum(): void {
		$params = $this->endpoint->get_collection_params();

		$this->assertContains( 'all', $params['status']['enum'] );
		$this->assertContains( 'read', $params['status']['enum'] );
		$this->assertContains( 'unread', $params['status']['enum'] );
	}

	/**
	 * Test get_listing_inquiries_params returns expected keys.
	 */
	public function test_get_listing_inquiries_params_returns_expected_keys(): void {
		$params = $this->endpoint->get_listing_inquiries_params();

		$this->assertArrayHasKey( 'listing_id', $params );
		$this->assertArrayHasKey( 'status', $params );
		$this->assertArrayHasKey( 'page', $params );
		$this->assertArrayHasKey( 'per_page', $params );
		$this->assertTrue( $params['listing_id']['required'] );
	}

	// =========================================================================
	// Schema Tests
	// =========================================================================

	/**
	 * Test get_inquiry_schema returns valid schema.
	 */
	public function test_get_inquiry_schema_returns_valid_schema(): void {
		$schema = $this->endpoint->get_inquiry_schema();

		$this->assertArrayHasKey( '$schema', $schema );
		$this->assertArrayHasKey( 'title', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertEquals( 'inquiry', $schema['title'] );
	}

	/**
	 * Test get_inquiry_schema includes all properties.
	 */
	public function test_get_inquiry_schema_includes_all_properties(): void {
		$schema     = $this->endpoint->get_inquiry_schema();
		$properties = $schema['properties'];

		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'listing_id', $properties );
		$this->assertArrayHasKey( 'sender', $properties );
		$this->assertArrayHasKey( 'subject', $properties );
		$this->assertArrayHasKey( 'message', $properties );
		$this->assertArrayHasKey( 'is_read', $properties );
		$this->assertArrayHasKey( 'date', $properties );
	}

	// =========================================================================
	// Permission Tests
	// =========================================================================

	/**
	 * Test permission_view_inquiry requires login.
	 */
	public function test_permission_view_inquiry_requires_login(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );

		Functions\stubs( [
			'get_current_user_id' => 0,
			'__'                  => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->permission_view_inquiry( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_not_logged_in', $result->get_error_code() );
	}

	/**
	 * Test permission_view_inquiry allows admin.
	 */
	public function test_permission_view_inquiry_allows_admin(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );

		Functions\stubs( [
			'get_current_user_id' => 1,
			'current_user_can'    => true,
		] );

		$result = $this->endpoint->permission_view_inquiry( $request );

		$this->assertTrue( $result );
	}

	/**
	 * Test permission_view_inquiry allows authorized user.
	 */
	public function test_permission_view_inquiry_allows_authorized_user(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );

		Functions\stubs( [
			'get_current_user_id' => 5,
			'current_user_can'    => false,
			'apd_can_view_inquiry' => true,
		] );

		$result = $this->endpoint->permission_view_inquiry( $request );

		$this->assertTrue( $result );
	}

	/**
	 * Test permission_view_inquiry denies unauthorized user.
	 */
	public function test_permission_view_inquiry_denies_unauthorized(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );

		Functions\stubs( [
			'get_current_user_id'  => 5,
			'current_user_can'     => false,
			'apd_can_view_inquiry' => false,
			'__'                   => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->permission_view_inquiry( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_forbidden', $result->get_error_code() );
	}

	/**
	 * Test permission_view_listing_inquiries allows owner.
	 */
	public function test_permission_view_listing_inquiries_allows_owner(): void {
		$request = $this->create_mock_request( [ 'listing_id' => 1 ] );
		$post    = $this->create_mock_post( [ 'post_author' => '5' ] );

		Functions\stubs( [
			'get_current_user_id' => 5,
			'current_user_can'    => false,
			'get_post'            => $post,
		] );

		$result = $this->endpoint->permission_view_listing_inquiries( $request );

		$this->assertTrue( $result );
	}

	// =========================================================================
	// Get Inquiries Tests
	// =========================================================================

	/**
	 * Test get_inquiries returns paginated response.
	 */
	public function test_get_inquiries_returns_paginated_response(): void {
		$request = $this->create_mock_request();
		$inquiry = $this->create_inquiry_data( [ 'listing_id' => 0 ] );

		Functions\stubs( [
			'get_current_user_id'        => 1,
			'apd_get_user_inquiries'     => [ $inquiry ],
			'apd_get_user_inquiry_count' => 1,
			'apply_filters'              => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->get_inquiries( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 200, $result->get_status() );

		$data = $result->get_data();
		$this->assertArrayHasKey( 'items', $data );
		$this->assertArrayHasKey( 'total', $data );
		$this->assertArrayHasKey( 'unread_count', $data );
	}

	/**
	 * Test get_inquiries maps page/per_page to number/offset.
	 */
	public function test_get_inquiries_maps_pagination_arguments(): void {
		$request = $this->create_mock_request(
			[
				'status'   => 'read',
				'page'     => 3,
				'per_page' => 15,
				'orderby'  => 'date',
				'order'    => 'ASC',
			]
		);
		$inquiry = $this->create_inquiry_data( [ 'listing_id' => 0 ] );

		Functions\expect( 'apd_get_user_inquiries' )
			->once()
			->with(
				7,
				Mockery::on(
					static function ( $args ): bool {
						return 15 === $args['number']
							&& 30 === $args['offset']
							&& 'read' === $args['status']
							&& 'date' === $args['orderby']
							&& 'ASC' === $args['order']
							&& ! isset( $args['page'] )
							&& ! isset( $args['per_page'] );
					}
				)
			)
			->andReturn( [ $inquiry ] );

		Functions\expect( 'apd_get_user_inquiry_count' )
			->once()
			->with( 7, 'read' )
			->andReturn( 10 );

		Functions\expect( 'apd_get_user_inquiry_count' )
			->once()
			->with( 7, 'unread' )
			->andReturn( 2 );

		Functions\stubs(
			[
				'get_current_user_id' => 7,
				'apply_filters'       => static function ( $hook, $data ) {
					return $data;
				},
			]
		);

		$result = $this->endpoint->get_inquiries( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 200, $result->get_status() );
	}

	// =========================================================================
	// Get Listing Inquiries Tests
	// =========================================================================

	/**
	 * Test get_listing_inquiries returns 404 for non-existent listing.
	 */
	public function test_get_listing_inquiries_returns_404_for_non_existent(): void {
		$request = $this->create_mock_request( [ 'listing_id' => 999 ] );

		Functions\when( 'get_post' )->justReturn( null );
		Functions\stubs( [
			'__' => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->get_listing_inquiries( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_listing_not_found', $result->get_error_code() );
	}

	/**
	 * Test get_listing_inquiries returns inquiries.
	 */
	public function test_get_listing_inquiries_returns_inquiries(): void {
		$request = $this->create_mock_request( [ 'listing_id' => 1 ] );
		$post    = $this->create_mock_post();
		$inquiry = $this->create_inquiry_data();

		Functions\stubs( [
			'get_post'                    => $post,
			'get_permalink'               => 'https://example.com/listing/test/',
			'apd_get_listing_inquiries'   => [ $inquiry ],
			'apd_get_listing_inquiry_count' => 1,
			'apply_filters'               => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->get_listing_inquiries( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$data = $result->get_data();

		$this->assertCount( 1, $data['items'] );
	}

	/**
	 * Test get_listing_inquiries maps pagination args and counts by status.
	 */
	public function test_get_listing_inquiries_maps_pagination_and_filters_total_by_status(): void {
		$request = $this->create_mock_request(
			[
				'listing_id' => 1,
				'status'     => 'read',
				'page'       => 2,
				'per_page'   => 25,
			]
		);
		$post    = $this->create_mock_post();
		$inquiry = $this->create_inquiry_data();

		Functions\expect( 'apd_get_listing_inquiries' )
			->once()
			->with(
				1,
				Mockery::on(
					static function ( $args ): bool {
						return 25 === $args['number']
							&& 25 === $args['offset']
							&& 'read' === $args['status']
							&& ! isset( $args['page'] )
							&& ! isset( $args['per_page'] );
					}
				)
			)
			->andReturn( [ $inquiry ] );

		Functions\expect( 'apd_get_listing_inquiry_count' )
			->once()
			->with( 1, 'read' )
			->andReturn( 7 );

		Functions\stubs(
			[
				'get_post'      => $post,
				'get_permalink' => 'https://example.com/listing/test/',
				'apply_filters' => static function ( $hook, $data ) {
					return $data;
				},
			]
		);

		$result = $this->endpoint->get_listing_inquiries( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$data = $result->get_data();

		$this->assertCount( 1, $data['items'] );
		$this->assertEquals( 7, $data['total'] );
	}

	// =========================================================================
	// Get Single Inquiry Tests
	// =========================================================================

	/**
	 * Test get_inquiry returns 404 for non-existent inquiry.
	 */
	public function test_get_inquiry_returns_404_for_non_existent(): void {
		$request = $this->create_mock_request( [ 'id' => 999 ] );

		Functions\when( 'apd_get_inquiry' )->justReturn( null );
		Functions\stubs( [
			'__' => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->get_inquiry( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_inquiry_not_found', $result->get_error_code() );
	}

	/**
	 * Test get_inquiry returns inquiry data.
	 */
	public function test_get_inquiry_returns_inquiry_data(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );
		$inquiry = $this->create_inquiry_data( [ 'listing_id' => 0 ] );

		Functions\stubs( [
			'apd_get_inquiry' => $inquiry,
			'apply_filters'   => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->get_inquiry( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 200, $result->get_status() );

		$data = $result->get_data();
		$this->assertEquals( 1, $data['id'] );
		$this->assertEquals( 'John Doe', $data['sender']['name'] );
	}

	// =========================================================================
	// Mark Read/Unread Tests
	// =========================================================================

	/**
	 * Test mark_read successfully marks inquiry as read.
	 */
	public function test_mark_read_successfully_marks_read(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );
		$inquiry = $this->create_inquiry_data( [ 'is_read' => true, 'listing_id' => 0 ] );

		Functions\stubs( [
			'apd_mark_inquiry_read' => true,
			'apd_get_inquiry'       => $inquiry,
			'apply_filters'         => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->mark_read( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$data = $result->get_data();
		$this->assertTrue( $data['is_read'] );
	}

	/**
	 * Test mark_read returns error on failure.
	 */
	public function test_mark_read_returns_error_on_failure(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );

		Functions\stubs( [
			'apd_mark_inquiry_read' => false,
			'__'                    => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->mark_read( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_mark_read_failed', $result->get_error_code() );
	}

	/**
	 * Test mark_unread successfully marks inquiry as unread.
	 */
	public function test_mark_unread_successfully_marks_unread(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );
		$inquiry = $this->create_inquiry_data( [ 'is_read' => false, 'listing_id' => 0 ] );

		Functions\stubs( [
			'apd_mark_inquiry_unread' => true,
			'apd_get_inquiry'         => $inquiry,
			'apply_filters'           => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->mark_unread( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$data = $result->get_data();
		$this->assertFalse( $data['is_read'] );
	}

	/**
	 * Test mark_unread returns error on failure.
	 */
	public function test_mark_unread_returns_error_on_failure(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );

		Functions\stubs( [
			'apd_mark_inquiry_unread' => false,
			'__'                      => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->mark_unread( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_mark_unread_failed', $result->get_error_code() );
	}

	// =========================================================================
	// Delete Inquiry Tests
	// =========================================================================

	/**
	 * Test delete_inquiry successfully deletes.
	 */
	public function test_delete_inquiry_successfully_deletes(): void {
		$request = $this->create_mock_request( [
			'id'    => 1,
			'force' => false,
		] );

		Functions\stubs( [
			'apd_delete_inquiry' => true,
		] );

		$result = $this->endpoint->delete_inquiry( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$data = $result->get_data();

		$this->assertTrue( $data['deleted'] );
		$this->assertEquals( 1, $data['inquiry_id'] );
	}

	/**
	 * Test delete_inquiry returns error on failure.
	 */
	public function test_delete_inquiry_returns_error_on_failure(): void {
		$request = $this->create_mock_request( [
			'id'    => 1,
			'force' => false,
		] );

		Functions\stubs( [
			'apd_delete_inquiry' => false,
			'__'                 => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->delete_inquiry( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_delete_failed', $result->get_error_code() );
	}

	// =========================================================================
	// Prepare Inquiry Tests
	// =========================================================================

	/**
	 * Test prepare_inquiry_for_response returns expected keys.
	 */
	public function test_prepare_inquiry_for_response_returns_expected_keys(): void {
		$inquiry = $this->create_inquiry_data( [ 'listing_id' => 0 ] );

		Functions\stubs( [
			'apply_filters' => function ( $hook, $data ) {
				return $data;
			},
		] );

		$data = $this->endpoint->prepare_inquiry_for_response( $inquiry );

		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'listing_id', $data );
		$this->assertArrayHasKey( 'sender', $data );
		$this->assertArrayHasKey( 'subject', $data );
		$this->assertArrayHasKey( 'message', $data );
		$this->assertArrayHasKey( 'is_read', $data );
		$this->assertArrayHasKey( 'date', $data );
	}

	/**
	 * Test prepare_inquiry_for_response includes sender details.
	 */
	public function test_prepare_inquiry_for_response_includes_sender(): void {
		$inquiry = $this->create_inquiry_data( [ 'listing_id' => 0 ] );

		Functions\stubs( [
			'apply_filters' => function ( $hook, $data ) {
				return $data;
			},
		] );

		$data = $this->endpoint->prepare_inquiry_for_response( $inquiry );

		$this->assertEquals( 'John Doe', $data['sender']['name'] );
		$this->assertEquals( 'john@example.com', $data['sender']['email'] );
		$this->assertEquals( '555-1234', $data['sender']['phone'] );
	}

	/**
	 * Test prepare_inquiry_for_response includes listing details.
	 */
	public function test_prepare_inquiry_for_response_includes_listing(): void {
		$inquiry = $this->create_inquiry_data();
		$post    = $this->create_mock_post();

		Functions\stubs( [
			'get_post'      => $post,
			'get_permalink' => 'https://example.com/listing/test/',
			'apply_filters' => function ( $hook, $data ) {
				return $data;
			},
		] );

		$data = $this->endpoint->prepare_inquiry_for_response( $inquiry );

		$this->assertArrayHasKey( 'listing', $data );
		$this->assertEquals( 1, $data['listing']['id'] );
		$this->assertEquals( 'Test Listing', $data['listing']['title'] );
		$this->assertEquals( 'https://example.com/listing/test/', $data['listing']['link'] );
	}

	/**
	 * Test prepare_inquiry_for_response correctly sets is_read.
	 */
	public function test_prepare_inquiry_for_response_sets_is_read(): void {
		$read_inquiry   = $this->create_inquiry_data( [ 'is_read' => true, 'listing_id' => 0 ] );
		$unread_inquiry = $this->create_inquiry_data( [ 'is_read' => false, 'listing_id' => 0 ] );

		Functions\stubs( [
			'apply_filters' => function ( $hook, $data ) {
				return $data;
			},
		] );

		$read_data   = $this->endpoint->prepare_inquiry_for_response( $read_inquiry );
		$unread_data = $this->endpoint->prepare_inquiry_for_response( $unread_inquiry );

		$this->assertTrue( $read_data['is_read'] );
		$this->assertFalse( $unread_data['is_read'] );
	}
}
