<?php
/**
 * Unit tests for FavoritesEndpoint class.
 *
 * @package APD\Tests\Unit\Api\Endpoints
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Api\Endpoints;

use APD\Api\Endpoints\FavoritesEndpoint;
use APD\Api\RestController;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Class FavoritesEndpointTest
 *
 * Tests for the FavoritesEndpoint class.
 */
class FavoritesEndpointTest extends UnitTestCase {

	/**
	 * Rest controller instance.
	 *
	 * @var RestController
	 */
	private RestController $controller;

	/**
	 * Endpoint instance.
	 *
	 * @var FavoritesEndpoint
	 */
	private FavoritesEndpoint $endpoint;

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
		$this->endpoint   = new FavoritesEndpoint( $this->controller );
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
			'post_excerpt' => 'Test excerpt',
			'post_content' => 'Test content',
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

	// =========================================================================
	// Constructor Tests
	// =========================================================================

	/**
	 * Test constructor creates endpoint.
	 */
	public function test_constructor_creates_endpoint(): void {
		$this->assertInstanceOf( FavoritesEndpoint::class, $this->endpoint );
	}

	// =========================================================================
	// Route Registration Tests
	// =========================================================================

	/**
	 * Test register_routes registers routes.
	 */
	public function test_register_routes_registers_favorite_routes(): void {
		Functions\expect( 'register_rest_route' )
			->times( 4 ) // /favorites (GET+POST), /favorites/listings, /favorites/{id} DELETE, /favorites/toggle/{id}
			->andReturn( true );

		$this->endpoint->register_routes();

		$this->assertTrue( true );
	}

	// =========================================================================
	// Parameters Tests
	// =========================================================================

	/**
	 * Test get_add_params returns expected keys.
	 */
	public function test_get_add_params_returns_listing_id(): void {
		$params = $this->endpoint->get_add_params();

		$this->assertArrayHasKey( 'listing_id', $params );
		$this->assertTrue( $params['listing_id']['required'] );
		$this->assertEquals( 'integer', $params['listing_id']['type'] );
	}

	/**
	 * Test get_listings_params returns pagination params.
	 */
	public function test_get_listings_params_returns_pagination(): void {
		$params = $this->endpoint->get_listings_params();

		$this->assertArrayHasKey( 'page', $params );
		$this->assertArrayHasKey( 'per_page', $params );
		$this->assertEquals( 1, $params['page']['default'] );
		$this->assertEquals( 10, $params['per_page']['default'] );
	}

	// =========================================================================
	// Schema Tests
	// =========================================================================

	/**
	 * Test get_favorites_schema returns valid schema.
	 */
	public function test_get_favorites_schema_returns_valid_schema(): void {
		$schema = $this->endpoint->get_favorites_schema();

		$this->assertArrayHasKey( '$schema', $schema );
		$this->assertArrayHasKey( 'title', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertEquals( 'favorites', $schema['title'] );
	}

	/**
	 * Test get_favorites_schema includes favorites array.
	 */
	public function test_get_favorites_schema_includes_favorites_property(): void {
		$schema = $this->endpoint->get_favorites_schema();

		$this->assertArrayHasKey( 'favorites', $schema['properties'] );
		$this->assertEquals( 'array', $schema['properties']['favorites']['type'] );
	}

	/**
	 * Test get_listing_schema returns valid schema.
	 */
	public function test_get_listing_schema_returns_valid_schema(): void {
		$schema = $this->endpoint->get_listing_schema();

		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'id', $schema['properties'] );
		$this->assertArrayHasKey( 'title', $schema['properties'] );
	}

	// =========================================================================
	// Get Favorites Tests
	// =========================================================================

	/**
	 * Test get_favorites returns favorites array.
	 */
	public function test_get_favorites_returns_favorites_array(): void {
		$request = $this->create_mock_request();

		Functions\stubs( [
			'apd_get_user_favorites' => [ 1, 2, 3 ],
		] );

		$result = $this->endpoint->get_favorites( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 200, $result->get_status() );

		$data = $result->get_data();
		$this->assertArrayHasKey( 'favorites', $data );
		$this->assertArrayHasKey( 'count', $data );
		$this->assertEquals( [ 1, 2, 3 ], $data['favorites'] );
		$this->assertEquals( 3, $data['count'] );
	}

	/**
	 * Test get_favorites returns empty array when no favorites.
	 */
	public function test_get_favorites_returns_empty_when_no_favorites(): void {
		$request = $this->create_mock_request();

		Functions\stubs( [
			'apd_get_user_favorites' => [],
		] );

		$result = $this->endpoint->get_favorites( $request );
		$data   = $result->get_data();

		$this->assertEquals( [], $data['favorites'] );
		$this->assertEquals( 0, $data['count'] );
	}

	// =========================================================================
	// Get Favorite Listings Tests
	// =========================================================================

	/**
	 * Test get_favorite_listings returns empty for no favorites.
	 */
	public function test_get_favorite_listings_returns_empty_for_no_favorites(): void {
		$request = $this->create_mock_request( [
			'page'     => 1,
			'per_page' => 10,
		] );

		Functions\stubs( [
			'apd_get_user_favorites' => [],
		] );

		$result = $this->endpoint->get_favorite_listings( $request );
		$data   = $result->get_data();

		$this->assertEquals( 200, $result->get_status() );
		$this->assertArrayHasKey( 'items', $data );
		$this->assertEquals( [], $data['items'] );
		$this->assertEquals( 0, $data['total'] );
	}

	/**
	 * Test get_favorite_listings returns paginated response.
	 */
	public function test_get_favorite_listings_returns_paginated_response(): void {
		$request = $this->create_mock_request( [
			'page'     => 1,
			'per_page' => 10,
		] );
		$post    = $this->create_mock_post();

		Functions\stubs( [
			'apd_get_user_favorites'       => [ 1 ],
			'get_posts'                    => [ $post ],
			'get_permalink'                => 'https://example.com/listing/test/',
			'get_post_thumbnail_id'        => 0,
			'wp_trim_words'                => function ( $text, $num_words ) {
				return substr( $text, 0, 50 ) . '...';
			},
			'apply_filters'                => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->get_favorite_listings( $request );
		$data   = $result->get_data();

		$this->assertEquals( 200, $result->get_status() );
		$this->assertArrayHasKey( 'items', $data );
		$this->assertCount( 1, $data['items'] );
		$this->assertEquals( 1, $data['total'] );
	}

	/**
	 * Test get_favorite_listings paginates correctly.
	 */
	public function test_get_favorite_listings_paginates_correctly(): void {
		$request = $this->create_mock_request( [
			'page'     => 2,
			'per_page' => 2,
		] );
		$post = $this->create_mock_post( [ 'ID' => 3 ] );

		Functions\stubs( [
			'apd_get_user_favorites'       => [ 1, 2, 3, 4 ],
			'get_posts'                    => [ $post ],
			'get_permalink'                => 'https://example.com/listing/test/',
			'get_post_thumbnail_id'        => 0,
			'wp_trim_words'                => function ( $text ) {
				return $text;
			},
			'apply_filters'                => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->get_favorite_listings( $request );
		$data   = $result->get_data();

		$this->assertEquals( 4, $data['total'] );
		$this->assertEquals( 2, $data['page'] );
		$this->assertEquals( 2, $data['per_page'] );
	}

	// =========================================================================
	// Add Favorite Tests
	// =========================================================================

	/**
	 * Test add_favorite returns 404 for non-existent listing.
	 */
	public function test_add_favorite_returns_404_for_non_existent_listing(): void {
		$request = $this->create_mock_request( [ 'listing_id' => 999 ] );

		Functions\when( 'get_post' )->justReturn( null );
		Functions\stubs( [
			'__' => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->add_favorite( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_listing_not_found', $result->get_error_code() );
	}

	/**
	 * Test add_favorite returns 404 for wrong post type.
	 */
	public function test_add_favorite_returns_404_for_wrong_post_type(): void {
		$request = $this->create_mock_request( [ 'listing_id' => 1 ] );
		$post    = $this->create_mock_post( [ 'post_type' => 'post' ] );

		Functions\stubs( [
			'get_post' => $post,
			'__'       => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->add_favorite( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_listing_not_found', $result->get_error_code() );
	}

	/**
	 * Test add_favorite returns success when already favorite.
	 */
	public function test_add_favorite_returns_success_when_already_favorite(): void {
		$request = $this->create_mock_request( [ 'listing_id' => 1 ] );
		$post    = $this->create_mock_post();

		Functions\stubs( [
			'get_post'        => $post,
			'apd_is_favorite' => true,
			'__'              => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->add_favorite( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 200, $result->get_status() );

		$data = $result->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertTrue( $data['is_favorite'] );
	}

	/**
	 * Test add_favorite successfully adds favorite.
	 */
	public function test_add_favorite_successfully_adds_favorite(): void {
		$request = $this->create_mock_request( [ 'listing_id' => 1 ] );
		$post    = $this->create_mock_post();

		Functions\stubs( [
			'get_post'         => $post,
			'apd_is_favorite'  => false,
			'apd_add_favorite' => true,
			'__'               => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->add_favorite( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 201, $result->get_status() );

		$data = $result->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertTrue( $data['is_favorite'] );
		$this->assertEquals( 1, $data['listing_id'] );
	}

	/**
	 * Test add_favorite returns error on failure.
	 */
	public function test_add_favorite_returns_error_on_failure(): void {
		$request = $this->create_mock_request( [ 'listing_id' => 1 ] );
		$post    = $this->create_mock_post();

		Functions\stubs( [
			'get_post'         => $post,
			'apd_is_favorite'  => false,
			'apd_add_favorite' => false,
			'__'               => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->add_favorite( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_favorite_failed', $result->get_error_code() );
	}

	// =========================================================================
	// Remove Favorite Tests
	// =========================================================================

	/**
	 * Test remove_favorite returns 404 when not in favorites.
	 */
	public function test_remove_favorite_returns_404_when_not_favorite(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );

		Functions\stubs( [
			'apd_is_favorite' => false,
			'__'              => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->remove_favorite( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_favorite_not_found', $result->get_error_code() );
	}

	/**
	 * Test remove_favorite successfully removes favorite.
	 */
	public function test_remove_favorite_successfully_removes(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );

		Functions\stubs( [
			'apd_is_favorite'     => true,
			'apd_remove_favorite' => true,
			'__'                  => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->remove_favorite( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 200, $result->get_status() );

		$data = $result->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertFalse( $data['is_favorite'] );
	}

	/**
	 * Test remove_favorite returns error on failure.
	 */
	public function test_remove_favorite_returns_error_on_failure(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );

		Functions\stubs( [
			'apd_is_favorite'     => true,
			'apd_remove_favorite' => false,
			'__'                  => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->remove_favorite( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_favorite_remove_failed', $result->get_error_code() );
	}

	// =========================================================================
	// Toggle Favorite Tests
	// =========================================================================

	/**
	 * Test toggle_favorite returns 404 for non-existent listing.
	 */
	public function test_toggle_favorite_returns_404_for_non_existent(): void {
		$request = $this->create_mock_request( [ 'id' => 999 ] );

		Functions\when( 'get_post' )->justReturn( null );
		Functions\stubs( [
			'__' => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->toggle_favorite( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_listing_not_found', $result->get_error_code() );
	}

	/**
	 * Test toggle_favorite adds favorite when not favorite.
	 */
	public function test_toggle_favorite_adds_when_not_favorite(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );
		$post    = $this->create_mock_post();

		Functions\stubs( [
			'get_post'            => $post,
			'apd_toggle_favorite' => true,
			'__'                  => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->toggle_favorite( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$data = $result->get_data();

		$this->assertTrue( $data['success'] );
		$this->assertTrue( $data['is_favorite'] );
	}

	/**
	 * Test toggle_favorite removes favorite when favorite.
	 */
	public function test_toggle_favorite_removes_when_favorite(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );
		$post    = $this->create_mock_post();

		Functions\stubs( [
			'get_post'            => $post,
			'apd_toggle_favorite' => false,
			'__'                  => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->toggle_favorite( $request );
		$data   = $result->get_data();

		$this->assertTrue( $data['success'] );
		$this->assertFalse( $data['is_favorite'] );
	}

	/**
	 * Test toggle_favorite returns error on null result.
	 */
	public function test_toggle_favorite_returns_error_on_null(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );
		$post    = $this->create_mock_post();

		Functions\stubs( [
			'get_post' => $post,
			'__'       => function ( $text ) {
				return $text;
			},
		] );
		Functions\when( 'apd_toggle_favorite' )->justReturn( null );

		$result = $this->endpoint->toggle_favorite( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_favorite_toggle_failed', $result->get_error_code() );
	}

	// =========================================================================
	// Prepare Listing Tests
	// =========================================================================

	/**
	 * Test prepare_listing_for_response returns expected keys.
	 */
	public function test_prepare_listing_for_response_returns_expected_keys(): void {
		$post = $this->create_mock_post();

		Functions\stubs( [
			'get_permalink'         => 'https://example.com/listing/test/',
			'get_post_thumbnail_id' => 0,
			'apply_filters'         => function ( $hook, $data ) {
				return $data;
			},
		] );

		$data = $this->endpoint->prepare_listing_for_response( $post );

		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'slug', $data );
		$this->assertArrayHasKey( 'excerpt', $data );
		$this->assertArrayHasKey( 'link', $data );
		$this->assertArrayHasKey( 'status', $data );
	}

	/**
	 * Test prepare_listing_for_response includes thumbnail when present.
	 */
	public function test_prepare_listing_for_response_includes_thumbnail(): void {
		$post = $this->create_mock_post();

		Functions\stubs( [
			'get_permalink'               => 'https://example.com/listing/test/',
			'get_post_thumbnail_id'       => 123,
			'wp_get_attachment_image_url' => 'https://example.com/image.jpg',
			'apply_filters'               => function ( $hook, $data ) {
				return $data;
			},
		] );

		$data = $this->endpoint->prepare_listing_for_response( $post );

		$this->assertArrayHasKey( 'thumbnail', $data );
		$this->assertEquals( 'https://example.com/image.jpg', $data['thumbnail'] );
	}

	/**
	 * Test prepare_listing_for_response uses content when no excerpt.
	 */
	public function test_prepare_listing_for_response_uses_content_for_excerpt(): void {
		$post = $this->create_mock_post( [ 'post_excerpt' => '' ] );

		Functions\stubs( [
			'get_permalink'         => 'https://example.com/listing/test/',
			'get_post_thumbnail_id' => 0,
			'wp_trim_words'         => 'Trimmed content...',
			'apply_filters'         => function ( $hook, $data ) {
				return $data;
			},
		] );

		$data = $this->endpoint->prepare_listing_for_response( $post );

		$this->assertEquals( 'Trimmed content...', $data['excerpt'] );
	}
}
