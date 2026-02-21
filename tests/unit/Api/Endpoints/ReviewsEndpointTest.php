<?php
/**
 * Unit tests for ReviewsEndpoint class.
 *
 * @package APD\Tests\Unit\Api\Endpoints
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Api\Endpoints;

use APD\Api\Endpoints\ReviewsEndpoint;
use APD\Api\RestController;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Class ReviewsEndpointTest
 *
 * Tests for the ReviewsEndpoint class.
 */
class ReviewsEndpointTest extends UnitTestCase {

	/**
	 * Rest controller instance.
	 *
	 * @var RestController
	 */
	private RestController $controller;

	/**
	 * Endpoint instance.
	 *
	 * @var ReviewsEndpoint
	 */
	private ReviewsEndpoint $endpoint;

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
		$this->endpoint   = new ReviewsEndpoint( $this->controller );
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
	private function create_mock_request( array $params = [], bool $with_nonce = false ): \WP_REST_Request {
		$request = Mockery::mock( \WP_REST_Request::class );

		foreach ( $params as $key => $value ) {
			$request->shouldReceive( 'get_param' )
				->with( $key )
				->andReturn( $value );
		}

		// Default to null for any param not explicitly set.
		$request->shouldReceive( 'get_param' )->andReturn( null );

		// Mock get_header for nonce verification.
		if ( $with_nonce ) {
			$request->shouldReceive( 'get_header' )
				->with( 'X-WP-Nonce' )
				->andReturn( 'test-nonce' );
		}
		$request->shouldReceive( 'get_header' )->andReturn( null );

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
	 * Create sample review data.
	 *
	 * @param array $overrides Override values.
	 * @return array Review data.
	 */
	private function create_review_data( array $overrides = [] ): array {
		return array_merge( [
			'id'         => 1,
			'listing_id' => 1,
			'author_id'  => 2,
			'rating'     => 5,
			'title'      => 'Great listing!',
			'content'    => 'This is a wonderful listing. Highly recommended.',
			'status'     => 'approved',
			'date'       => '2024-01-15 10:30:00',
		], $overrides );
	}

	// =========================================================================
	// Constructor Tests
	// =========================================================================

	/**
	 * Test constructor creates endpoint.
	 */
	public function test_constructor_creates_endpoint(): void {
		$this->assertInstanceOf( ReviewsEndpoint::class, $this->endpoint );
	}

	// =========================================================================
	// Route Registration Tests
	// =========================================================================

	/**
	 * Test register_routes registers routes.
	 */
	public function test_register_routes_registers_review_routes(): void {
		Functions\expect( 'register_rest_route' )
			->times( 3 ) // /reviews, /reviews/{id}, /listings/{id}/reviews
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

		$this->assertArrayHasKey( 'listing_id', $params );
		$this->assertArrayHasKey( 'author', $params );
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

		$this->assertContains( 'approved', $params['status']['enum'] );
		$this->assertContains( 'pending', $params['status']['enum'] );
		$this->assertContains( 'all', $params['status']['enum'] );
	}

	/**
	 * Test get_create_params returns required fields.
	 */
	public function test_get_create_params_returns_required_fields(): void {
		$params = $this->endpoint->get_create_params();

		$this->assertArrayHasKey( 'listing_id', $params );
		$this->assertArrayHasKey( 'rating', $params );
		$this->assertArrayHasKey( 'content', $params );
		$this->assertTrue( $params['listing_id']['required'] );
		$this->assertTrue( $params['rating']['required'] );
		$this->assertTrue( $params['content']['required'] );
	}

	/**
	 * Test get_create_params rating constraints.
	 */
	public function test_get_create_params_rating_constraints(): void {
		$params = $this->endpoint->get_create_params();

		$this->assertEquals( 1, $params['rating']['minimum'] );
		$this->assertEquals( 5, $params['rating']['maximum'] );
	}

	/**
	 * Test get_update_params returns expected keys.
	 */
	public function test_get_update_params_returns_expected_keys(): void {
		$params = $this->endpoint->get_update_params();

		$this->assertArrayHasKey( 'id', $params );
		$this->assertArrayHasKey( 'rating', $params );
		$this->assertArrayHasKey( 'title', $params );
		$this->assertArrayHasKey( 'content', $params );
	}

	// =========================================================================
	// Schema Tests
	// =========================================================================

	/**
	 * Test get_review_schema returns valid schema.
	 */
	public function test_get_review_schema_returns_valid_schema(): void {
		$schema = $this->endpoint->get_review_schema();

		$this->assertArrayHasKey( '$schema', $schema );
		$this->assertArrayHasKey( 'title', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertEquals( 'review', $schema['title'] );
	}

	/**
	 * Test get_review_schema includes all properties.
	 */
	public function test_get_review_schema_includes_all_properties(): void {
		$schema     = $this->endpoint->get_review_schema();
		$properties = $schema['properties'];

		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'listing_id', $properties );
		$this->assertArrayHasKey( 'author_id', $properties );
		$this->assertArrayHasKey( 'rating', $properties );
		$this->assertArrayHasKey( 'title', $properties );
		$this->assertArrayHasKey( 'content', $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'date', $properties );
	}

	// =========================================================================
	// Get Reviews Tests
	// =========================================================================

	/**
	 * Test get_reviews returns paginated response.
	 */
	public function test_get_reviews_returns_paginated_response(): void {
		$request = $this->create_mock_request( [
			'status' => 'approved',
		] );

		$review = $this->create_review_data();

		Functions\stubs( [
			'apd_get_listing_reviews' => [
				'reviews' => [ $review ],
				'total'   => 1,
			],
			'apd_get_review_count'    => 1,
			'get_user_by'             => false,
			'apply_filters'           => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->get_reviews( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 200, $result->get_status() );

		$data = $result->get_data();
		$this->assertArrayHasKey( 'items', $data );
		$this->assertArrayHasKey( 'total', $data );
	}

	/**
	 * Test get_reviews filters by listing_id.
	 */
	public function test_get_reviews_filters_by_listing_id(): void {
		$request = $this->create_mock_request( [
			'listing_id' => 5,
			'status'     => 'approved',
		] );

		$review = $this->create_review_data( [ 'listing_id' => 5 ] );

		Functions\stubs( [
			'apd_get_listing_reviews' => [
				'reviews' => [ $review ],
				'total'   => 1,
			],
			'apd_get_review_count'    => 1,
			'get_user_by'             => false,
			'apply_filters'           => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->get_reviews( $request );
		$data   = $result->get_data();

		$this->assertCount( 1, $data['items'] );
		$this->assertEquals( 5, $data['items'][0]['listing_id'] );
	}

	/**
	 * Test get_reviews passes author filter to manager args.
	 */
	public function test_get_reviews_passes_author_filter_to_manager(): void {
		$request       = $this->create_mock_request(
			[
				'author' => 9,
				'status' => 'approved',
			]
		);
		$captured_args = [];

		Functions\when( 'apd_get_listing_reviews' )->alias(
			static function ( int $listing_id, array $args ) use ( &$captured_args ): array {
				$captured_args = $args;
				return [
					'reviews' => [],
					'total'   => 0,
				];
			}
		);

		$result = $this->endpoint->get_reviews( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( 9, $captured_args['author'] ?? null );
	}

	/**
	 * Test get_reviews forces approved status for non-admin users.
	 */
	public function test_get_reviews_forces_approved_status_for_non_admin(): void {
		$request         = $this->create_mock_request( [ 'status' => 'pending' ] );
		$captured_status = null;

		Functions\when( 'apd_get_listing_reviews' )->alias(
			static function ( int $listing_id, array $args ) use ( &$captured_status ): array {
				$captured_status = $args['status'] ?? null;
				return [
					'reviews' => [],
					'total'   => 0,
				];
			}
		);

		Functions\stubs( [
			'current_user_can' => false,
		] );

		$result = $this->endpoint->get_reviews( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( 'approved', $captured_status );
	}

	/**
	 * Test get_reviews keeps requested status for admin users.
	 */
	public function test_get_reviews_keeps_status_for_admin(): void {
		$request         = $this->create_mock_request( [ 'status' => 'pending' ] );
		$captured_status = null;

		Functions\when( 'apd_get_listing_reviews' )->alias(
			static function ( int $listing_id, array $args ) use ( &$captured_status ): array {
				$captured_status = $args['status'] ?? null;
				return [
					'reviews' => [],
					'total'   => 0,
				];
			}
		);

		Functions\stubs( [
			'current_user_can' => true,
		] );

		$result = $this->endpoint->get_reviews( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( 'pending', $captured_status );
	}

	/**
	 * Test get_reviews maps page/per_page to number/offset.
	 */
	public function test_get_reviews_maps_pagination_args_for_manager(): void {
		$request       = $this->create_mock_request(
			[
				'page'     => 3,
				'per_page' => 7,
				'status'   => 'approved',
			]
		);
		$captured_args = [];

		Functions\when( 'apd_get_listing_reviews' )->alias(
			static function ( int $listing_id, array $args ) use ( &$captured_args ): array {
				$captured_args = $args;
				return [
					'reviews' => [],
					'total'   => 0,
				];
			}
		);

		$result = $this->endpoint->get_reviews( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( 7, $captured_args['number'] ?? null );
		$this->assertSame( 14, $captured_args['offset'] ?? null );
	}

	// =========================================================================
	// Get Listing Reviews Tests
	// =========================================================================

	/**
	 * Test get_listing_reviews returns 404 for non-existent listing.
	 */
	public function test_get_listing_reviews_returns_404_for_non_existent(): void {
		$request = $this->create_mock_request( [ 'listing_id' => 999 ] );

		Functions\when( 'get_post' )->justReturn( null );
		Functions\stubs( [
			'__' => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->get_listing_reviews( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_listing_not_found', $result->get_error_code() );
	}

	/**
	 * Test get_listing_reviews includes rating summary.
	 */
	public function test_get_listing_reviews_includes_rating_summary(): void {
		$request = $this->create_mock_request( [
			'listing_id' => 1,
			'status'     => 'approved',
		] );
		$post = $this->create_mock_post();

		Functions\stubs( [
			'get_post'                => $post,
			'apd_get_listing_reviews' => [],
			'apd_get_review_count'    => 5,
			'apd_get_listing_rating'  => 4.5,
		] );

		$result = $this->endpoint->get_listing_reviews( $request );
		$data   = $result->get_data();

		$this->assertArrayHasKey( 'listing_rating', $data );
		$this->assertEquals( 4.5, $data['listing_rating']['rating'] );
		$this->assertEquals( 5, $data['listing_rating']['review_count'] );
	}

	/**
	 * Test get_listing_reviews forces approved status for non-admin users.
	 */
	public function test_get_listing_reviews_forces_approved_status_for_non_admin(): void {
		$request         = $this->create_mock_request(
			[
				'listing_id' => 1,
				'status'     => 'pending',
			]
		);
		$post            = $this->create_mock_post();
		$captured_status = null;

		Functions\when( 'apd_get_listing_reviews' )->alias(
			static function ( int $listing_id, array $args ) use ( &$captured_status ): array {
				$captured_status = $args['status'] ?? null;
				return [
					'reviews' => [],
					'total'   => 0,
				];
			}
		);

		Functions\stubs( [
			'get_post'               => $post,
			'current_user_can'       => false,
			'apd_get_listing_rating' => 0,
		] );

		$result = $this->endpoint->get_listing_reviews( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( 'approved', $captured_status );
	}

	/**
	 * Test get_listing_reviews maps page/per_page to number/offset.
	 */
	public function test_get_listing_reviews_maps_pagination_args_for_manager(): void {
		$request       = $this->create_mock_request(
			[
				'listing_id' => 1,
				'page'       => 2,
				'per_page'   => 15,
				'status'     => 'approved',
			]
		);
		$post          = $this->create_mock_post();
		$captured_args = [];

		Functions\when( 'apd_get_listing_reviews' )->alias(
			static function ( int $listing_id, array $args ) use ( &$captured_args ): array {
				$captured_args = $args;
				return [
					'reviews' => [],
					'total'   => 0,
				];
			}
		);

		Functions\stubs(
			[
				'get_post'               => $post,
				'apd_get_listing_rating' => 0,
			]
		);

		$result = $this->endpoint->get_listing_reviews( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( 15, $captured_args['number'] ?? null );
		$this->assertSame( 15, $captured_args['offset'] ?? null );
	}

	// =========================================================================
	// Get Single Review Tests
	// =========================================================================

	/**
	 * Test get_review returns 404 for non-existent review.
	 */
	public function test_get_review_returns_404_for_non_existent(): void {
		$request = $this->create_mock_request( [ 'id' => 999 ] );

		Functions\when( 'apd_get_review' )->justReturn( null );
		Functions\stubs( [
			'__' => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->get_review( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_review_not_found', $result->get_error_code() );
	}

	/**
	 * Test get_review returns review data.
	 */
	public function test_get_review_returns_review_data(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );
		$review  = $this->create_review_data();

		Functions\stubs( [
			'apd_get_review' => $review,
			'get_user_by'    => false,
			'apply_filters'  => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->get_review( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 200, $result->get_status() );

		$data = $result->get_data();
		$this->assertEquals( 1, $data['id'] );
		$this->assertEquals( 5, $data['rating'] );
	}

	/**
	 * Test get_review hides pending review from unrelated public users.
	 */
	public function test_get_review_hides_pending_review_from_public(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );
		$review  = $this->create_review_data(
			[
				'status'    => 'pending',
				'author_id' => 5,
			]
		);

		Functions\stubs( [
			'apd_get_review'      => $review,
			'current_user_can'    => false,
			'get_current_user_id' => 0,
			'__'                  => static function ( string $text ): string {
				return $text;
			},
		] );

		$result = $this->endpoint->get_review( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_review_not_found', $result->get_error_code() );
	}

	/**
	 * Test get_review allows review author to view pending review.
	 */
	public function test_get_review_allows_author_to_view_pending_review(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );
		$review  = $this->create_review_data(
			[
				'status'    => 'pending',
				'author_id' => 5,
			]
		);

		Functions\stubs( [
			'apd_get_review'      => $review,
			'current_user_can'    => false,
			'get_current_user_id' => 5,
			'get_user_by'         => false,
			'apply_filters'       => static function ( string $hook, array $data ): array {
				return $data;
			},
		] );

		$result = $this->endpoint->get_review( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( 200, $result->get_status() );
	}

	// =========================================================================
	// Create Review Tests
	// =========================================================================

	/**
	 * Test create_review returns 404 for non-existent listing.
	 */
	public function test_create_review_returns_404_for_non_existent_listing(): void {
		$request = $this->create_mock_request( [
			'listing_id' => 999,
			'rating'     => 5,
			'content'    => 'Great listing!',
		] );

		Functions\when( 'get_post' )->justReturn( null );
		Functions\stubs( [
			'__' => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->create_review( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_listing_not_found', $result->get_error_code() );
	}

	/**
	 * Test create_review returns error when user has already reviewed.
	 */
	public function test_create_review_returns_error_when_already_reviewed(): void {
		$request = $this->create_mock_request( [
			'listing_id' => 1,
			'rating'     => 5,
			'content'    => 'Great listing!',
		] );
		$post = $this->create_mock_post();

		Functions\stubs( [
			'get_post'             => $post,
			'get_current_user_id'  => 2,
			'apd_has_user_reviewed' => true,
			'__'                   => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->create_review( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_review_exists', $result->get_error_code() );
	}

	/**
	 * Test create_review successfully creates review.
	 */
	public function test_create_review_successfully_creates_review(): void {
		$request = $this->create_mock_request( [
			'listing_id' => 1,
			'rating'     => 5,
			'title'      => 'Awesome!',
			'content'    => 'This is a great listing!',
		] );
		$post   = $this->create_mock_post();
		$review = $this->create_review_data();

		Functions\stubs( [
			'get_post'              => $post,
			'get_current_user_id'   => 2,
			'apd_has_user_reviewed' => false,
			'apd_create_review'     => 1,
			'apd_get_review'        => $review,
			'get_user_by'           => false,
			'apply_filters'         => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->create_review( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 201, $result->get_status() );
	}

	/**
	 * Test create_review handles WP_Error from apd_create_review.
	 */
	public function test_create_review_handles_error(): void {
		$request = $this->create_mock_request( [
			'listing_id' => 1,
			'rating'     => 5,
			'content'    => 'Great!',
		] );
		$post  = $this->create_mock_post();
		$error = new \WP_Error( 'review_error', 'Failed to create review' );

		Functions\stubs( [
			'get_post'              => $post,
			'get_current_user_id'   => 2,
			'apd_has_user_reviewed' => false,
			'apd_create_review'     => $error,
			'__'                    => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->create_review( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'review_error', $result->get_error_code() );
	}

	// =========================================================================
	// Update Review Tests
	// =========================================================================

	/**
	 * Test update_review returns error when no data provided.
	 */
	public function test_update_review_returns_error_when_no_data(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );

		Functions\stubs( [
			'__' => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->update_review( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_no_update_data', $result->get_error_code() );
	}

	/**
	 * Test update_review successfully updates review.
	 */
	public function test_update_review_successfully_updates(): void {
		$request = $this->create_mock_request( [
			'id'      => 1,
			'rating'  => 4,
			'content' => 'Updated content',
		] );
		$review = $this->create_review_data( [ 'rating' => 4 ] );

		Functions\stubs( [
			'apd_update_review' => true,
			'apd_get_review'    => $review,
			'get_user_by'       => false,
			'apply_filters'     => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->update_review( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 200, $result->get_status() );
	}

	/**
	 * Test update_review handles WP_Error.
	 */
	public function test_update_review_handles_error(): void {
		$request = $this->create_mock_request( [
			'id'     => 1,
			'rating' => 4,
		] );
		$error = new \WP_Error( 'update_error', 'Failed to update' );

		Functions\stubs( [
			'apd_update_review' => $error,
			'__'                => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->update_review( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'update_error', $result->get_error_code() );
	}

	// =========================================================================
	// Delete Review Tests
	// =========================================================================

	/**
	 * Test delete_review successfully deletes.
	 */
	public function test_delete_review_successfully_deletes(): void {
		$request = $this->create_mock_request( [
			'id'    => 1,
			'force' => false,
		] );

		Functions\stubs( [
			'apd_delete_review' => true,
		] );

		$result = $this->endpoint->delete_review( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$data = $result->get_data();

		$this->assertTrue( $data['deleted'] );
		$this->assertEquals( 1, $data['review_id'] );
	}

	/**
	 * Test delete_review returns error on failure.
	 */
	public function test_delete_review_returns_error_on_failure(): void {
		$request = $this->create_mock_request( [
			'id'    => 1,
			'force' => false,
		] );

		Functions\stubs( [
			'apd_delete_review' => false,
			'__'                => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->delete_review( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_delete_failed', $result->get_error_code() );
	}

	// =========================================================================
	// Permission Tests
	// =========================================================================

	/**
	 * Test permission_edit_review returns error for non-existent review.
	 */
	public function test_permission_edit_review_returns_error_for_non_existent(): void {
		$request = $this->create_mock_request( [ 'id' => 999 ], true );

		Functions\when( 'apd_get_review' )->justReturn( null );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\stubs( [
			'__' => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->permission_edit_review( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_review_not_found', $result->get_error_code() );
	}

	/**
	 * Test permission_edit_review allows admin.
	 */
	public function test_permission_edit_review_allows_admin(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ], true );
		$review  = $this->create_review_data();

		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\stubs( [
			'apd_get_review'     => $review,
			'get_current_user_id' => 99,
			'current_user_can'   => true,
		] );

		$result = $this->endpoint->permission_edit_review( $request );

		$this->assertTrue( $result );
	}

	/**
	 * Test permission_edit_review allows author.
	 */
	public function test_permission_edit_review_allows_author(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ], true );
		$review  = $this->create_review_data( [ 'author_id' => 5 ] );

		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\stubs( [
			'apd_get_review'      => $review,
			'get_current_user_id' => 5,
			'current_user_can'    => false,
		] );

		$result = $this->endpoint->permission_edit_review( $request );

		$this->assertTrue( $result );
	}

	/**
	 * Test permission_edit_review denies non-author.
	 */
	public function test_permission_edit_review_denies_non_author(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ], true );
		$review  = $this->create_review_data( [ 'author_id' => 5 ] );

		Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
		Functions\stubs( [
			'apd_get_review'      => $review,
			'get_current_user_id' => 10,
			'current_user_can'    => false,
			'__'                  => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->permission_edit_review( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_forbidden', $result->get_error_code() );
	}

	// =========================================================================
	// Prepare Review Tests
	// =========================================================================

	/**
	 * Test prepare_review_for_response returns expected keys.
	 */
	public function test_prepare_review_for_response_returns_expected_keys(): void {
		$review = $this->create_review_data();

		Functions\stubs( [
			'get_user_by'   => false,
			'apply_filters' => function ( $hook, $data ) {
				return $data;
			},
		] );

		$data = $this->endpoint->prepare_review_for_response( $review );

		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'listing_id', $data );
		$this->assertArrayHasKey( 'author_id', $data );
		$this->assertArrayHasKey( 'rating', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'content', $data );
		$this->assertArrayHasKey( 'status', $data );
		$this->assertArrayHasKey( 'date', $data );
	}

	/**
	 * Test prepare_review_for_response includes author details.
	 */
	public function test_prepare_review_for_response_includes_author(): void {
		$review = $this->create_review_data( [ 'author_id' => 5 ] );
		$user   = (object) [
			'ID'           => 5,
			'display_name' => 'John Doe',
		];

		Functions\stubs( [
			'get_user_by'     => $user,
			'get_avatar_url'  => 'https://example.com/avatar.jpg',
			'apply_filters'   => function ( $hook, $data ) {
				return $data;
			},
		] );

		$data = $this->endpoint->prepare_review_for_response( $review );

		$this->assertArrayHasKey( 'author', $data );
		$this->assertEquals( 5, $data['author']['id'] );
		$this->assertEquals( 'John Doe', $data['author']['name'] );
		$this->assertEquals( 'https://example.com/avatar.jpg', $data['author']['avatar'] );
	}
}
