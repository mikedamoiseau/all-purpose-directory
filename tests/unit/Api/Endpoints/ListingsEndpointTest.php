<?php
/**
 * Unit tests for ListingsEndpoint class.
 *
 * @package APD\Tests\Unit\Api\Endpoints
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Api\Endpoints;

use APD\Api\Endpoints\ListingsEndpoint;
use APD\Api\RestController;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Class ListingsEndpointTest
 *
 * Tests for the ListingsEndpoint class.
 */
class ListingsEndpointTest extends UnitTestCase {

	/**
	 * Rest controller instance.
	 *
	 * @var RestController
	 */
	private RestController $controller;

	/**
	 * Endpoint instance.
	 *
	 * @var ListingsEndpoint
	 */
	private ListingsEndpoint $endpoint;

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

		// Stub wp_get_object_terms for apd_get_listing_type() calls.
		Functions\when( 'wp_get_object_terms' )->justReturn( [] );

		$this->controller = RestController::get_instance();
		$this->endpoint   = new ListingsEndpoint( $this->controller );
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
		$post = new \WP_Post( (object) [] );

		$defaults = [
			'ID'                => 1,
			'post_title'        => 'Test Listing',
			'post_content'      => 'Test content',
			'post_excerpt'      => 'Test excerpt',
			'post_status'       => 'publish',
			'post_author'       => 1,
			'post_type'         => 'apd_listing',
			'post_name'         => 'test-listing',
			'post_date'         => '2024-01-01 12:00:00',
			'post_date_gmt'     => '2024-01-01 12:00:00',
			'post_modified'     => '2024-01-01 12:00:00',
			'post_modified_gmt' => '2024-01-01 12:00:00',
		];

		$data = array_merge( $defaults, $data );

		foreach ( $data as $key => $value ) {
			$post->$key = $value;
		}

		return $post;
	}

	// =========================================================================
	// Constructor Tests
	// =========================================================================

	/**
	 * Test constructor sets controller.
	 */
	public function test_constructor_creates_endpoint(): void {
		$this->assertInstanceOf( ListingsEndpoint::class, $this->endpoint );
	}

	// =========================================================================
	// Route Registration Tests
	// =========================================================================

	/**
	 * Test register_routes registers routes.
	 */
	public function test_register_routes_registers_collection_route(): void {
		Functions\expect( 'register_rest_route' )
			->twice()
			->andReturn( true );

		$this->endpoint->register_routes();

		$this->assertTrue( true ); // If no exception, routes were registered.
	}

	// =========================================================================
	// Collection Params Tests
	// =========================================================================

	/**
	 * Test get_collection_params returns expected params.
	 */
	public function test_get_collection_params_returns_expected_keys(): void {
		$params = $this->endpoint->get_collection_params();

		$this->assertArrayHasKey( 'page', $params );
		$this->assertArrayHasKey( 'per_page', $params );
		$this->assertArrayHasKey( 'search', $params );
		$this->assertArrayHasKey( 'category', $params );
		$this->assertArrayHasKey( 'tag', $params );
		$this->assertArrayHasKey( 'author', $params );
		$this->assertArrayHasKey( 'status', $params );
		$this->assertArrayHasKey( 'orderby', $params );
		$this->assertArrayHasKey( 'order', $params );
	}

	/**
	 * Test get_collection_params page defaults.
	 */
	public function test_get_collection_params_page_defaults(): void {
		$params = $this->endpoint->get_collection_params();

		$this->assertEquals( 1, $params['page']['default'] );
		$this->assertEquals( 1, $params['page']['minimum'] );
		$this->assertEquals( 'integer', $params['page']['type'] );
	}

	/**
	 * Test get_collection_params per_page defaults.
	 */
	public function test_get_collection_params_per_page_defaults(): void {
		$params = $this->endpoint->get_collection_params();

		$this->assertEquals( 10, $params['per_page']['default'] );
		$this->assertEquals( 1, $params['per_page']['minimum'] );
		$this->assertEquals( 100, $params['per_page']['maximum'] );
	}

	/**
	 * Test get_collection_params orderby enum.
	 */
	public function test_get_collection_params_orderby_enum(): void {
		$params = $this->endpoint->get_collection_params();

		$this->assertContains( 'date', $params['orderby']['enum'] );
		$this->assertContains( 'title', $params['orderby']['enum'] );
		$this->assertContains( 'modified', $params['orderby']['enum'] );
		$this->assertContains( 'rand', $params['orderby']['enum'] );
		$this->assertContains( 'views', $params['orderby']['enum'] );
	}

	/**
	 * Test get_collection_params order enum.
	 */
	public function test_get_collection_params_order_enum(): void {
		$params = $this->endpoint->get_collection_params();

		$this->assertContains( 'ASC', $params['order']['enum'] );
		$this->assertContains( 'DESC', $params['order']['enum'] );
		$this->assertEquals( 'DESC', $params['order']['default'] );
	}

	/**
	 * Test get_collection_params status enum.
	 */
	public function test_get_collection_params_status_enum(): void {
		$params = $this->endpoint->get_collection_params();

		$this->assertContains( 'publish', $params['status']['enum'] );
		$this->assertContains( 'pending', $params['status']['enum'] );
		$this->assertContains( 'draft', $params['status']['enum'] );
		$this->assertContains( 'expired', $params['status']['enum'] );
	}

	// =========================================================================
	// Create Params Tests
	// =========================================================================

	/**
	 * Test get_create_params returns expected params.
	 */
	public function test_get_create_params_returns_expected_keys(): void {
		$params = $this->endpoint->get_create_params();

		$this->assertArrayHasKey( 'title', $params );
		$this->assertArrayHasKey( 'content', $params );
		$this->assertArrayHasKey( 'excerpt', $params );
		$this->assertArrayHasKey( 'status', $params );
		$this->assertArrayHasKey( 'categories', $params );
		$this->assertArrayHasKey( 'tags', $params );
		$this->assertArrayHasKey( 'meta', $params );
		$this->assertArrayHasKey( 'featured_image', $params );
	}

	/**
	 * Test get_create_params title is required.
	 */
	public function test_get_create_params_title_required(): void {
		$params = $this->endpoint->get_create_params();

		$this->assertTrue( $params['title']['required'] );
	}

	/**
	 * Test get_create_params status defaults to pending.
	 */
	public function test_get_create_params_status_default(): void {
		$params = $this->endpoint->get_create_params();

		$this->assertEquals( 'pending', $params['status']['default'] );
	}

	/**
	 * Test get_create_params status enum values.
	 */
	public function test_get_create_params_status_enum(): void {
		$params = $this->endpoint->get_create_params();

		$this->assertContains( 'publish', $params['status']['enum'] );
		$this->assertContains( 'pending', $params['status']['enum'] );
		$this->assertContains( 'draft', $params['status']['enum'] );
	}

	/**
	 * Test get_create_params categories type.
	 */
	public function test_get_create_params_categories_type(): void {
		$params = $this->endpoint->get_create_params();

		$this->assertEquals( 'array', $params['categories']['type'] );
		$this->assertEquals( [ 'type' => 'integer' ], $params['categories']['items'] );
	}

	/**
	 * Test get_create_params tags type.
	 */
	public function test_get_create_params_tags_type(): void {
		$params = $this->endpoint->get_create_params();

		$this->assertEquals( 'array', $params['tags']['type'] );
		$this->assertEquals( [ 'type' => 'integer' ], $params['tags']['items'] );
	}

	/**
	 * Test get_create_params meta type.
	 */
	public function test_get_create_params_meta_type(): void {
		$params = $this->endpoint->get_create_params();

		$this->assertEquals( 'object', $params['meta']['type'] );
	}

	// =========================================================================
	// Update Params Tests
	// =========================================================================

	/**
	 * Test get_update_params includes id.
	 */
	public function test_get_update_params_includes_id(): void {
		$params = $this->endpoint->get_update_params();

		$this->assertArrayHasKey( 'id', $params );
		$this->assertTrue( $params['id']['required'] );
		$this->assertEquals( 'integer', $params['id']['type'] );
	}

	/**
	 * Test get_update_params includes all create params.
	 */
	public function test_get_update_params_includes_create_params(): void {
		$update_params = $this->endpoint->get_update_params();
		$create_params = $this->endpoint->get_create_params();

		foreach ( array_keys( $create_params ) as $key ) {
			$this->assertArrayHasKey( $key, $update_params );
		}
	}

	// =========================================================================
	// Schema Tests
	// =========================================================================

	/**
	 * Test get_item_schema returns valid schema.
	 */
	public function test_get_item_schema_returns_valid_schema(): void {
		$schema = $this->endpoint->get_item_schema();

		$this->assertArrayHasKey( '$schema', $schema );
		$this->assertArrayHasKey( 'title', $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertEquals( 'listing', $schema['title'] );
		$this->assertEquals( 'object', $schema['type'] );
	}

	/**
	 * Test get_item_schema properties.
	 */
	public function test_get_item_schema_has_expected_properties(): void {
		$schema     = $this->endpoint->get_item_schema();
		$properties = $schema['properties'];

		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'title', $properties );
		$this->assertArrayHasKey( 'content', $properties );
		$this->assertArrayHasKey( 'excerpt', $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'author', $properties );
		$this->assertArrayHasKey( 'date', $properties );
		$this->assertArrayHasKey( 'link', $properties );
		$this->assertArrayHasKey( 'featured_image', $properties );
		$this->assertArrayHasKey( 'categories', $properties );
		$this->assertArrayHasKey( 'tags', $properties );
		$this->assertArrayHasKey( 'meta', $properties );
	}

	/**
	 * Test get_item_schema id is readonly.
	 */
	public function test_get_item_schema_id_readonly(): void {
		$schema = $this->endpoint->get_item_schema();

		$this->assertTrue( $schema['properties']['id']['readonly'] );
	}

	/**
	 * Test get_item_schema date is readonly.
	 */
	public function test_get_item_schema_date_readonly(): void {
		$schema = $this->endpoint->get_item_schema();

		$this->assertTrue( $schema['properties']['date']['readonly'] );
	}

	/**
	 * Test get_item_schema link is readonly.
	 */
	public function test_get_item_schema_link_readonly(): void {
		$schema = $this->endpoint->get_item_schema();

		$this->assertTrue( $schema['properties']['link']['readonly'] );
	}

	/**
	 * Test get_item_schema status enum.
	 */
	public function test_get_item_schema_status_enum(): void {
		$schema = $this->endpoint->get_item_schema();

		$this->assertContains( 'publish', $schema['properties']['status']['enum'] );
		$this->assertContains( 'pending', $schema['properties']['status']['enum'] );
		$this->assertContains( 'draft', $schema['properties']['status']['enum'] );
		$this->assertContains( 'expired', $schema['properties']['status']['enum'] );
	}

	// =========================================================================
	// Prepare Item Response Tests
	// =========================================================================

	/**
	 * Test prepare_item_for_response returns expected structure.
	 */
	public function test_prepare_item_for_response_returns_expected_keys(): void {
		$listing = $this->create_mock_post();
		$request = $this->create_mock_request();

		// Mock WordPress functions.
		Functions\stubs( [
			'apd_get_listing_categories' => [],
			'apd_get_listing_tags'       => [],
			'mysql_to_rfc3339'           => '2024-01-01T12:00:00',
			'get_permalink'              => 'https://example.com/listing/test-listing/',
			'get_post_thumbnail_id'      => 0,
			'apd_get_fields'             => [],
			'apply_filters'              => function ( $hook, $data ) {
				return $data;
			},
		] );

		$data = $this->endpoint->prepare_item_for_response( $listing, $request );

		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'title', $data );
		$this->assertArrayHasKey( 'content', $data );
		$this->assertArrayHasKey( 'excerpt', $data );
		$this->assertArrayHasKey( 'status', $data );
		$this->assertArrayHasKey( 'author', $data );
		$this->assertArrayHasKey( 'date', $data );
		$this->assertArrayHasKey( 'link', $data );
		$this->assertArrayHasKey( 'categories', $data );
		$this->assertArrayHasKey( 'tags', $data );
		$this->assertArrayHasKey( 'meta', $data );
	}

	/**
	 * Test prepare_item_for_response returns correct values.
	 */
	public function test_prepare_item_for_response_returns_correct_values(): void {
		$listing = $this->create_mock_post( [
			'ID'           => 42,
			'post_title'   => 'My Test Listing',
			'post_content' => 'This is the content.',
			'post_excerpt' => 'This is the excerpt.',
			'post_status'  => 'publish',
			'post_author'  => 5,
		] );
		$request = $this->create_mock_request();

		// Mock WordPress functions.
		Functions\expect( 'apd_get_listing_categories' )->andReturn( [] );
		Functions\expect( 'apd_get_listing_tags' )->andReturn( [] );
		Functions\expect( 'mysql_to_rfc3339' )->andReturn( '2024-01-01T12:00:00' );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/listing/my-test-listing/' );
		Functions\expect( 'get_post_thumbnail_id' )->andReturn( 100 );
		Functions\expect( 'apd_get_fields' )->andReturn( [] );
		Functions\expect( 'apply_filters' )->andReturnUsing( fn( $hook, $data ) => $data );

		$data = $this->endpoint->prepare_item_for_response( $listing, $request );

		$this->assertEquals( 42, $data['id'] );
		$this->assertEquals( 'My Test Listing', $data['title'] );
		$this->assertEquals( 'This is the content.', $data['content'] );
		$this->assertEquals( 'This is the excerpt.', $data['excerpt'] );
		$this->assertEquals( 'publish', $data['status'] );
		$this->assertEquals( 5, $data['author'] );
		$this->assertEquals( 100, $data['featured_image'] );
	}

	/**
	 * Test prepare_item_for_response includes categories.
	 */
	public function test_prepare_item_for_response_includes_categories(): void {
		$listing = $this->create_mock_post();
		$request = $this->create_mock_request();

		// Create mock term.
		$term            = new \stdClass();
		$term->term_id   = 10;
		$term->name      = 'Test Category';
		$term->slug      = 'test-category';

		Functions\expect( 'apd_get_listing_categories' )->andReturn( [ $term ] );
		Functions\expect( 'apd_get_listing_tags' )->andReturn( [] );
		Functions\expect( 'mysql_to_rfc3339' )->andReturn( '2024-01-01T12:00:00' );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/' );
		Functions\expect( 'get_post_thumbnail_id' )->andReturn( 0 );
		Functions\expect( 'apd_get_fields' )->andReturn( [] );
		Functions\expect( 'apply_filters' )->andReturnUsing( fn( $hook, $data ) => $data );

		$data = $this->endpoint->prepare_item_for_response( $listing, $request );

		$this->assertCount( 1, $data['categories'] );
		$this->assertEquals( 10, $data['categories'][0]['id'] );
		$this->assertEquals( 'Test Category', $data['categories'][0]['name'] );
		$this->assertEquals( 'test-category', $data['categories'][0]['slug'] );
	}

	/**
	 * Test prepare_item_for_response includes tags.
	 */
	public function test_prepare_item_for_response_includes_tags(): void {
		$listing = $this->create_mock_post();
		$request = $this->create_mock_request();

		// Create mock term.
		$term            = new \stdClass();
		$term->term_id   = 20;
		$term->name      = 'Test Tag';
		$term->slug      = 'test-tag';

		Functions\expect( 'apd_get_listing_categories' )->andReturn( [] );
		Functions\expect( 'apd_get_listing_tags' )->andReturn( [ $term ] );
		Functions\expect( 'mysql_to_rfc3339' )->andReturn( '2024-01-01T12:00:00' );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/' );
		Functions\expect( 'get_post_thumbnail_id' )->andReturn( 0 );
		Functions\expect( 'apd_get_fields' )->andReturn( [] );
		Functions\expect( 'apply_filters' )->andReturnUsing( fn( $hook, $data ) => $data );

		$data = $this->endpoint->prepare_item_for_response( $listing, $request );

		$this->assertCount( 1, $data['tags'] );
		$this->assertEquals( 20, $data['tags'][0]['id'] );
		$this->assertEquals( 'Test Tag', $data['tags'][0]['name'] );
		$this->assertEquals( 'test-tag', $data['tags'][0]['slug'] );
	}

	/**
	 * Test prepare_item_for_response includes custom meta.
	 */
	public function test_prepare_item_for_response_includes_meta(): void {
		$listing = $this->create_mock_post();
		$request = $this->create_mock_request();

		$fields = [
			[ 'name' => 'price' ],
			[ 'name' => 'location' ],
		];

		$meta_values = [
			'price'    => '100.00',
			'location' => 'New York',
		];

		Functions\stubs( [
			'apd_get_listing_categories' => [],
			'apd_get_listing_tags'       => [],
			'mysql_to_rfc3339'           => '2024-01-01T12:00:00',
			'get_permalink'              => 'https://example.com/',
			'get_post_thumbnail_id'      => 0,
			'apd_get_fields'             => $fields,
			'apd_get_listing_field'      => function ( $listing_id, $field_name ) use ( $meta_values ) {
				return $meta_values[ $field_name ] ?? null;
			},
			'apply_filters'              => function ( $hook, $data ) {
				return $data;
			},
		] );

		$data = $this->endpoint->prepare_item_for_response( $listing, $request );

		$this->assertArrayHasKey( 'price', $data['meta'] );
		$this->assertArrayHasKey( 'location', $data['meta'] );
		$this->assertEquals( '100.00', $data['meta']['price'] );
		$this->assertEquals( 'New York', $data['meta']['location'] );
	}

	/**
	 * Test prepare_item_for_response returns null featured_image when none set.
	 */
	public function test_prepare_item_for_response_null_featured_image(): void {
		$listing = $this->create_mock_post();
		$request = $this->create_mock_request();

		Functions\expect( 'apd_get_listing_categories' )->andReturn( [] );
		Functions\expect( 'apd_get_listing_tags' )->andReturn( [] );
		Functions\expect( 'mysql_to_rfc3339' )->andReturn( '2024-01-01T12:00:00' );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/' );
		Functions\expect( 'get_post_thumbnail_id' )->andReturn( 0 );
		Functions\expect( 'apd_get_fields' )->andReturn( [] );
		Functions\expect( 'apply_filters' )->andReturnUsing( fn( $hook, $data ) => $data );

		$data = $this->endpoint->prepare_item_for_response( $listing, $request );

		$this->assertNull( $data['featured_image'] );
	}

	// =========================================================================
	// Get Item Tests
	// =========================================================================

	/**
	 * Test get_item returns 404 for non-existent listing.
	 */
	public function test_get_item_returns_404_for_non_existent_listing(): void {
		$request = $this->create_mock_request( [ 'id' => 999 ] );

		Functions\expect( 'get_post' )
			->once()
			->with( 999 )
			->andReturn( null );

		Functions\expect( '__' )
			->andReturnUsing( fn( $text ) => $text );

		$result = $this->endpoint->get_item( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_listing_not_found', $result->get_error_code() );
	}

	/**
	 * Test get_item returns 404 for wrong post type.
	 */
	public function test_get_item_returns_404_for_wrong_post_type(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );
		$post    = $this->create_mock_post( [ 'post_type' => 'post' ] );

		Functions\expect( 'get_post' )
			->once()
			->with( 1 )
			->andReturn( $post );

		Functions\expect( '__' )
			->andReturnUsing( fn( $text ) => $text );

		$result = $this->endpoint->get_item( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_listing_not_found', $result->get_error_code() );
	}

	/**
	 * Test get_item returns listing data for published listing.
	 */
	public function test_get_item_returns_listing_data(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );
		$listing = $this->create_mock_post();

		Functions\expect( 'get_post' )
			->with( 1 )
			->andReturn( $listing );

		// Mock prepare_item_for_response dependencies.
		Functions\expect( 'apd_get_listing_categories' )->andReturn( [] );
		Functions\expect( 'apd_get_listing_tags' )->andReturn( [] );
		Functions\expect( 'mysql_to_rfc3339' )->andReturn( '2024-01-01T12:00:00' );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/' );
		Functions\expect( 'get_post_thumbnail_id' )->andReturn( 0 );
		Functions\expect( 'apd_get_fields' )->andReturn( [] );
		Functions\expect( 'apply_filters' )->andReturnUsing( fn( $hook, $data ) => $data );

		$result = $this->endpoint->get_item( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 200, $result->get_status() );
	}

	/**
	 * Test get_item returns 404 for unpublished listing without permission.
	 */
	public function test_get_item_returns_404_for_unpublished_without_permission(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );
		$listing = $this->create_mock_post( [ 'post_status' => 'draft' ] );

		Functions\stubs( [
			'get_post'         => $listing,
			'current_user_can' => false,
			'__'               => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->get_item( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_listing_not_found', $result->get_error_code() );
	}

	/**
	 * Test get_item returns draft listing with permission.
	 */
	public function test_get_item_returns_draft_with_permission(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );
		$listing = $this->create_mock_post( [ 'post_status' => 'draft' ] );

		Functions\stubs( [
			'get_post'                   => $listing,
			'current_user_can'           => true,
			'apd_get_listing_categories' => [],
			'apd_get_listing_tags'       => [],
			'mysql_to_rfc3339'           => '2024-01-01T12:00:00',
			'get_permalink'              => 'https://example.com/',
			'get_post_thumbnail_id'      => 0,
			'apd_get_fields'             => [],
			'apply_filters'              => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->get_item( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
	}

	// =========================================================================
	// Create Item Tests
	// =========================================================================

	/**
	 * Test create_item returns error for missing title.
	 */
	public function test_create_item_returns_error_for_missing_title(): void {
		$request = $this->create_mock_request( [ 'title' => '' ] );

		Functions\expect( '__' )
			->andReturnUsing( fn( $text ) => $text );

		$result = $this->endpoint->create_item( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_missing_param', $result->get_error_code() );
	}

	/**
	 * Test create_item returns error for null title.
	 */
	public function test_create_item_returns_error_for_null_title(): void {
		$request = $this->create_mock_request( [ 'title' => null ] );

		Functions\expect( '__' )
			->andReturnUsing( fn( $text ) => $text );

		$result = $this->endpoint->create_item( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_missing_param', $result->get_error_code() );
	}

	/**
	 * Test create_item creates listing successfully.
	 */
	public function test_create_item_creates_listing(): void {
		$request = $this->create_mock_request( [
			'title'   => 'New Listing',
			'content' => 'Test content',
			'excerpt' => 'Test excerpt',
			'status'  => 'pending',
		] );

		$listing = $this->create_mock_post( [
			'ID'           => 100,
			'post_title'   => 'New Listing',
			'post_content' => 'Test content',
		] );

		Functions\stubs( [
			'sanitize_text_field'        => function ( $str ) {
				return $str;
			},
			'wp_kses_post'               => function ( $str ) {
				return $str;
			},
			'sanitize_textarea_field'    => function ( $str ) {
				return $str;
			},
			'get_current_user_id'        => 1,
			'current_user_can'           => false,
			'do_action'                  => null,
			'wp_insert_post'             => 100,
			'get_post'                   => $listing,
			'apd_get_listing_categories' => [],
			'apd_get_listing_tags'       => [],
			'mysql_to_rfc3339'           => '2024-01-01T12:00:00',
			'get_permalink'              => 'https://example.com/',
			'get_post_thumbnail_id'      => 0,
			'apd_get_fields'             => [],
			'apply_filters'              => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->create_item( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 201, $result->get_status() );
	}

	/**
	 * Test create_item sets categories.
	 */
	public function test_create_item_sets_categories(): void {
		$request = $this->create_mock_request( [
			'title'      => 'New Listing',
			'categories' => [ 1, 2, 3 ],
		] );

		$listing = $this->create_mock_post( [ 'ID' => 100 ] );

		Functions\expect( 'sanitize_text_field' )->andReturn( 'New Listing' );
		Functions\expect( 'wp_kses_post' )->andReturn( '' );
		Functions\expect( 'sanitize_textarea_field' )->andReturn( '' );
		Functions\expect( 'get_current_user_id' )->andReturn( 1 );
		Functions\expect( 'current_user_can' )->andReturn( false );
		Functions\expect( 'do_action' )->andReturn( null );
		Functions\expect( 'wp_insert_post' )->andReturn( 100 );
		Functions\expect( 'get_post' )->andReturn( $listing );
		Functions\expect( 'apd_get_listing_categories' )->andReturn( [] );
		Functions\expect( 'apd_get_listing_tags' )->andReturn( [] );
		Functions\expect( 'mysql_to_rfc3339' )->andReturn( '2024-01-01T12:00:00' );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/' );
		Functions\expect( 'get_post_thumbnail_id' )->andReturn( 0 );
		Functions\expect( 'apd_get_fields' )->andReturn( [] );
		Functions\expect( 'apply_filters' )->andReturnUsing( fn( $hook, $data ) => $data );

		Functions\expect( 'wp_set_object_terms' )
			->once()
			->with( 100, [ 1, 2, 3 ], 'apd_category' )
			->andReturn( [ 1, 2, 3 ] );

		$this->endpoint->create_item( $request );

		$this->assertTrue( true ); // If no exception, categories were set.
	}

	/**
	 * Test create_item sets featured image.
	 */
	public function test_create_item_sets_featured_image(): void {
		$request = $this->create_mock_request( [
			'title'          => 'New Listing',
			'featured_image' => 50,
		] );

		$listing = $this->create_mock_post( [ 'ID' => 100 ] );

		// Create an attachment for featured image validation.
		$attachment = new \WP_Post( [
			'ID'          => 50,
			'post_type'   => 'attachment',
			'post_author' => 1,
		] );

		Functions\when( 'sanitize_text_field' )->justReturn( 'New Listing' );
		Functions\when( 'wp_kses_post' )->justReturn( '' );
		Functions\when( 'sanitize_textarea_field' )->justReturn( '' );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->alias( function ( $cap ) {
			return $cap === 'manage_options';
		} );
		Functions\when( 'wp_insert_post' )->justReturn( 100 );
		Functions\when( 'absint' )->alias( fn( $val ) => abs( (int) $val ) );
		Functions\when( 'get_post' )->alias( function ( $id ) use ( $listing, $attachment ) {
			return $id === 50 ? $attachment : $listing;
		} );
		Functions\when( 'wp_attachment_is_image' )->justReturn( true );
		Functions\when( 'apd_get_listing_categories' )->justReturn( [] );
		Functions\when( 'apd_get_listing_tags' )->justReturn( [] );
		Functions\when( 'mysql_to_rfc3339' )->justReturn( '2024-01-01T12:00:00' );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/' );
		Functions\when( 'get_post_thumbnail_id' )->justReturn( 50 );
		Functions\when( 'apd_get_fields' )->justReturn( [] );
		Functions\when( 'apply_filters' )->alias( fn( $hook, $data ) => $data );

		Functions\expect( 'set_post_thumbnail' )
			->once()
			->with( 100, 50 )
			->andReturn( true );

		$result = $this->endpoint->create_item( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
	}

	// =========================================================================
	// Delete Item Tests
	// =========================================================================

	/**
	 * Test delete_item returns 404 for non-existent listing.
	 */
	public function test_delete_item_returns_404_for_non_existent_listing(): void {
		$request = $this->create_mock_request( [ 'id' => 999 ] );

		Functions\expect( 'get_post' )
			->once()
			->with( 999 )
			->andReturn( null );

		Functions\expect( '__' )
			->andReturnUsing( fn( $text ) => $text );

		$result = $this->endpoint->delete_item( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_listing_not_found', $result->get_error_code() );
	}

	/**
	 * Test delete_item trashes listing by default.
	 */
	public function test_delete_item_trashes_listing(): void {
		$request = $this->create_mock_request( [
			'id'    => 1,
			'force' => false,
		] );

		$listing = $this->create_mock_post();

		Functions\expect( 'get_post' )
			->with( 1 )
			->andReturn( $listing );

		Functions\expect( 'do_action' )->andReturn( null );

		// Mock prepare_item_for_response dependencies.
		Functions\expect( 'apd_get_listing_categories' )->andReturn( [] );
		Functions\expect( 'apd_get_listing_tags' )->andReturn( [] );
		Functions\expect( 'mysql_to_rfc3339' )->andReturn( '2024-01-01T12:00:00' );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/' );
		Functions\expect( 'get_post_thumbnail_id' )->andReturn( 0 );
		Functions\expect( 'apd_get_fields' )->andReturn( [] );
		Functions\expect( 'apply_filters' )->andReturnUsing( fn( $hook, $data ) => $data );

		Functions\expect( 'wp_trash_post' )
			->once()
			->with( 1 )
			->andReturn( $listing );

		$result = $this->endpoint->delete_item( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
	}

	/**
	 * Test delete_item force deletes listing.
	 */
	public function test_delete_item_force_deletes_listing(): void {
		$request = $this->create_mock_request( [
			'id'    => 1,
			'force' => true,
		] );

		$listing = $this->create_mock_post();

		Functions\expect( 'get_post' )
			->with( 1 )
			->andReturn( $listing );

		Functions\expect( 'do_action' )->andReturn( null );

		// Mock prepare_item_for_response dependencies.
		Functions\expect( 'apd_get_listing_categories' )->andReturn( [] );
		Functions\expect( 'apd_get_listing_tags' )->andReturn( [] );
		Functions\expect( 'mysql_to_rfc3339' )->andReturn( '2024-01-01T12:00:00' );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/' );
		Functions\expect( 'get_post_thumbnail_id' )->andReturn( 0 );
		Functions\expect( 'apd_get_fields' )->andReturn( [] );
		Functions\expect( 'apply_filters' )->andReturnUsing( fn( $hook, $data ) => $data );

		Functions\expect( 'wp_delete_post' )
			->once()
			->with( 1, true )
			->andReturn( $listing );

		$result = $this->endpoint->delete_item( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
	}

	/**
	 * Test delete_item returns error on failure.
	 */
	public function test_delete_item_returns_error_on_failure(): void {
		$request = $this->create_mock_request( [
			'id'    => 1,
			'force' => false,
		] );

		$listing = $this->create_mock_post();

		Functions\expect( 'get_post' )
			->with( 1 )
			->andReturn( $listing );

		Functions\expect( 'do_action' )->andReturn( null );

		// Mock prepare_item_for_response dependencies.
		Functions\expect( 'apd_get_listing_categories' )->andReturn( [] );
		Functions\expect( 'apd_get_listing_tags' )->andReturn( [] );
		Functions\expect( 'mysql_to_rfc3339' )->andReturn( '2024-01-01T12:00:00' );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/' );
		Functions\expect( 'get_post_thumbnail_id' )->andReturn( 0 );
		Functions\expect( 'apd_get_fields' )->andReturn( [] );
		Functions\expect( 'apply_filters' )->andReturnUsing( fn( $hook, $data ) => $data );

		Functions\expect( 'wp_trash_post' )
			->once()
			->with( 1 )
			->andReturn( false );

		Functions\expect( '__' )
			->andReturnUsing( fn( $text ) => $text );

		$result = $this->endpoint->delete_item( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_listing_delete_failed', $result->get_error_code() );
	}
}
