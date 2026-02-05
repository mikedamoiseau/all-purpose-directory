<?php
/**
 * Unit tests for TaxonomiesEndpoint class.
 *
 * @package APD\Tests\Unit\Api\Endpoints
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Api\Endpoints;

use APD\Api\Endpoints\TaxonomiesEndpoint;
use APD\Api\RestController;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Class TaxonomiesEndpointTest
 *
 * Tests for the TaxonomiesEndpoint class.
 */
class TaxonomiesEndpointTest extends UnitTestCase {

	/**
	 * Rest controller instance.
	 *
	 * @var RestController
	 */
	private RestController $controller;

	/**
	 * Endpoint instance.
	 *
	 * @var TaxonomiesEndpoint
	 */
	private TaxonomiesEndpoint $endpoint;

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
		$this->endpoint   = new TaxonomiesEndpoint( $this->controller );
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
	 * Create a mock WP_Term.
	 *
	 * @param array $data Term data.
	 * @return \WP_Term
	 */
	private function create_mock_term( array $data = [] ): \WP_Term {
		$defaults = [
			'term_id'     => 1,
			'name'        => 'Test Term',
			'slug'        => 'test-term',
			'description' => 'Test description',
			'parent'      => 0,
			'count'       => 5,
			'taxonomy'    => 'apd_category',
		];

		$data = array_merge( $defaults, $data );

		$term = new \WP_Term( (object) $data );
		foreach ( $data as $key => $value ) {
			$term->$key = $value;
		}

		return $term;
	}

	// =========================================================================
	// Constructor Tests
	// =========================================================================

	/**
	 * Test constructor creates endpoint.
	 */
	public function test_constructor_creates_endpoint(): void {
		$this->assertInstanceOf( TaxonomiesEndpoint::class, $this->endpoint );
	}

	// =========================================================================
	// Route Registration Tests
	// =========================================================================

	/**
	 * Test register_routes registers routes.
	 */
	public function test_register_routes_registers_taxonomy_routes(): void {
		Functions\expect( 'register_rest_route' )
			->times( 4 ) // categories, categories/{id}, tags, tags/{id}
			->andReturn( true );

		$this->endpoint->register_routes();

		$this->assertTrue( true );
	}

	// =========================================================================
	// Taxonomy Params Tests
	// =========================================================================

	/**
	 * Test get_taxonomy_params returns expected params.
	 */
	public function test_get_taxonomy_params_returns_expected_keys(): void {
		$params = $this->endpoint->get_taxonomy_params();

		$this->assertArrayHasKey( 'page', $params );
		$this->assertArrayHasKey( 'per_page', $params );
		$this->assertArrayHasKey( 'search', $params );
		$this->assertArrayHasKey( 'parent', $params );
		$this->assertArrayHasKey( 'hide_empty', $params );
		$this->assertArrayHasKey( 'include', $params );
		$this->assertArrayHasKey( 'exclude', $params );
		$this->assertArrayHasKey( 'orderby', $params );
		$this->assertArrayHasKey( 'order', $params );
	}

	/**
	 * Test get_taxonomy_params page defaults.
	 */
	public function test_get_taxonomy_params_page_defaults(): void {
		$params = $this->endpoint->get_taxonomy_params();

		$this->assertEquals( 1, $params['page']['default'] );
		$this->assertEquals( 1, $params['page']['minimum'] );
	}

	/**
	 * Test get_taxonomy_params per_page defaults.
	 */
	public function test_get_taxonomy_params_per_page_defaults(): void {
		$params = $this->endpoint->get_taxonomy_params();

		$this->assertEquals( 100, $params['per_page']['default'] );
		$this->assertEquals( 1, $params['per_page']['minimum'] );
		$this->assertEquals( 100, $params['per_page']['maximum'] );
	}

	/**
	 * Test get_taxonomy_params hide_empty default.
	 */
	public function test_get_taxonomy_params_hide_empty_default(): void {
		$params = $this->endpoint->get_taxonomy_params();

		$this->assertTrue( $params['hide_empty']['default'] );
	}

	/**
	 * Test get_taxonomy_params orderby enum.
	 */
	public function test_get_taxonomy_params_orderby_enum(): void {
		$params = $this->endpoint->get_taxonomy_params();

		$this->assertContains( 'name', $params['orderby']['enum'] );
		$this->assertContains( 'slug', $params['orderby']['enum'] );
		$this->assertContains( 'count', $params['orderby']['enum'] );
		$this->assertContains( 'id', $params['orderby']['enum'] );
	}

	/**
	 * Test get_taxonomy_params order enum.
	 */
	public function test_get_taxonomy_params_order_enum(): void {
		$params = $this->endpoint->get_taxonomy_params();

		$this->assertContains( 'ASC', $params['order']['enum'] );
		$this->assertContains( 'DESC', $params['order']['enum'] );
		$this->assertEquals( 'ASC', $params['order']['default'] );
	}

	// =========================================================================
	// Schema Tests
	// =========================================================================

	/**
	 * Test get_category_schema returns valid schema.
	 */
	public function test_get_category_schema_returns_valid_schema(): void {
		$schema = $this->endpoint->get_category_schema();

		$this->assertArrayHasKey( '$schema', $schema );
		$this->assertArrayHasKey( 'title', $schema );
		$this->assertArrayHasKey( 'type', $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertEquals( 'category', $schema['title'] );
		$this->assertEquals( 'object', $schema['type'] );
	}

	/**
	 * Test get_category_schema includes icon and color.
	 */
	public function test_get_category_schema_includes_meta_fields(): void {
		$schema     = $this->endpoint->get_category_schema();
		$properties = $schema['properties'];

		$this->assertArrayHasKey( 'icon', $properties );
		$this->assertArrayHasKey( 'color', $properties );
	}

	/**
	 * Test get_tag_schema returns valid schema.
	 */
	public function test_get_tag_schema_returns_valid_schema(): void {
		$schema = $this->endpoint->get_tag_schema();

		$this->assertArrayHasKey( '$schema', $schema );
		$this->assertArrayHasKey( 'title', $schema );
		$this->assertEquals( 'tag', $schema['title'] );
	}

	/**
	 * Test get_tag_schema does not include icon and color.
	 */
	public function test_get_tag_schema_no_meta_fields(): void {
		$schema     = $this->endpoint->get_tag_schema();
		$properties = $schema['properties'];

		$this->assertArrayNotHasKey( 'icon', $properties );
		$this->assertArrayNotHasKey( 'color', $properties );
	}

	// =========================================================================
	// Prepare Term Response Tests
	// =========================================================================

	/**
	 * Test prepare_term_for_response returns expected keys.
	 */
	public function test_prepare_term_for_response_returns_expected_keys(): void {
		$term = $this->create_mock_term();

		Functions\stubs( [
			'get_term_link'  => 'https://example.com/category/test-term/',
			'apply_filters'  => function ( $hook, $data ) {
				return $data;
			},
		] );

		$data = $this->endpoint->prepare_term_for_response( $term, true );

		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'name', $data );
		$this->assertArrayHasKey( 'slug', $data );
		$this->assertArrayHasKey( 'description', $data );
		$this->assertArrayHasKey( 'parent', $data );
		$this->assertArrayHasKey( 'count', $data );
		$this->assertArrayHasKey( 'link', $data );
	}

	/**
	 * Test prepare_term_for_response returns correct values.
	 */
	public function test_prepare_term_for_response_returns_correct_values(): void {
		$term = $this->create_mock_term( [
			'term_id'     => 42,
			'name'        => 'My Category',
			'slug'        => 'my-category',
			'description' => 'A test category',
			'parent'      => 0,
			'count'       => 10,
		] );

		Functions\stubs( [
			'get_term_link' => 'https://example.com/category/my-category/',
			'apply_filters' => function ( $hook, $data ) {
				return $data;
			},
		] );

		$data = $this->endpoint->prepare_term_for_response( $term, true );

		$this->assertEquals( 42, $data['id'] );
		$this->assertEquals( 'My Category', $data['name'] );
		$this->assertEquals( 'my-category', $data['slug'] );
		$this->assertEquals( 'A test category', $data['description'] );
		$this->assertEquals( 0, $data['parent'] );
		$this->assertEquals( 10, $data['count'] );
		$this->assertEquals( 'https://example.com/category/my-category/', $data['link'] );
	}

	/**
	 * Test prepare_term_for_response includes category meta.
	 */
	public function test_prepare_term_for_response_includes_category_meta(): void {
		$term = $this->create_mock_term();

		Functions\stubs( [
			'get_term_link'        => 'https://example.com/category/test-term/',
			'apd_get_category_icon'  => 'dashicons-location',
			'apd_get_category_color' => '#ff0000',
			'apply_filters'        => function ( $hook, $data ) {
				return $data;
			},
		] );

		$data = $this->endpoint->prepare_term_for_response( $term, true );

		$this->assertArrayHasKey( 'icon', $data );
		$this->assertArrayHasKey( 'color', $data );
		$this->assertEquals( 'dashicons-location', $data['icon'] );
		$this->assertEquals( '#ff0000', $data['color'] );
	}

	/**
	 * Test prepare_term_for_response excludes category meta for tags.
	 */
	public function test_prepare_term_for_response_excludes_category_meta_for_tags(): void {
		$term = $this->create_mock_term( [ 'taxonomy' => 'apd_tag' ] );

		Functions\stubs( [
			'get_term_link' => 'https://example.com/tag/test-term/',
			'apply_filters' => function ( $hook, $data ) {
				return $data;
			},
		] );

		$data = $this->endpoint->prepare_term_for_response( $term, false );

		$this->assertArrayNotHasKey( 'icon', $data );
		$this->assertArrayNotHasKey( 'color', $data );
	}

	// =========================================================================
	// Get Categories Tests
	// =========================================================================

	/**
	 * Test get_categories returns paginated response.
	 */
	public function test_get_categories_returns_paginated_response(): void {
		$request = $this->create_mock_request();
		$term    = $this->create_mock_term();

		Functions\stubs( [
			'get_terms'              => function ( $args ) use ( $term ) {
				if ( isset( $args['count'] ) && $args['count'] ) {
					return 1;
				}
				return [ $term ];
			},
			'get_term_link'          => 'https://example.com/category/test-term/',
			'apd_get_category_icon'  => '',
			'apd_get_category_color' => '',
			'apply_filters'          => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->get_categories( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 200, $result->get_status() );

		$data = $result->get_data();
		$this->assertArrayHasKey( 'items', $data );
		$this->assertArrayHasKey( 'total', $data );
		$this->assertArrayHasKey( 'page', $data );
		$this->assertArrayHasKey( 'per_page', $data );
	}

	/**
	 * Test get_categories includes category meta.
	 */
	public function test_get_categories_includes_category_meta(): void {
		$request = $this->create_mock_request();
		$term    = $this->create_mock_term();

		Functions\stubs( [
			'get_terms'              => function ( $args ) use ( $term ) {
				if ( isset( $args['count'] ) && $args['count'] ) {
					return 1;
				}
				return [ $term ];
			},
			'get_term_link'          => 'https://example.com/category/test-term/',
			'apd_get_category_icon'  => 'dashicons-category',
			'apd_get_category_color' => '#0000ff',
			'apply_filters'          => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->get_categories( $request );
		$data   = $result->get_data();

		$this->assertCount( 1, $data['items'] );
		$this->assertArrayHasKey( 'icon', $data['items'][0] );
		$this->assertArrayHasKey( 'color', $data['items'][0] );
	}

	// =========================================================================
	// Get Tags Tests
	// =========================================================================

	/**
	 * Test get_tags returns paginated response.
	 */
	public function test_get_tags_returns_paginated_response(): void {
		$request = $this->create_mock_request();
		$term    = $this->create_mock_term( [ 'taxonomy' => 'apd_tag' ] );

		Functions\stubs( [
			'get_terms'     => function ( $args ) use ( $term ) {
				if ( isset( $args['count'] ) && $args['count'] ) {
					return 1;
				}
				return [ $term ];
			},
			
			'get_term_link' => 'https://example.com/tag/test-term/',
			'apply_filters' => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->get_tags( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 200, $result->get_status() );
	}

	/**
	 * Test get_tags does not include category meta.
	 */
	public function test_get_tags_excludes_category_meta(): void {
		$request = $this->create_mock_request();
		$term    = $this->create_mock_term( [ 'taxonomy' => 'apd_tag' ] );

		Functions\stubs( [
			'get_terms'     => function ( $args ) use ( $term ) {
				if ( isset( $args['count'] ) && $args['count'] ) {
					return 1;
				}
				return [ $term ];
			},
			
			'get_term_link' => 'https://example.com/tag/test-term/',
			'apply_filters' => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->get_tags( $request );
		$data   = $result->get_data();

		$this->assertCount( 1, $data['items'] );
		$this->assertArrayNotHasKey( 'icon', $data['items'][0] );
		$this->assertArrayNotHasKey( 'color', $data['items'][0] );
	}

	// =========================================================================
	// Get Single Category Tests
	// =========================================================================

	/**
	 * Test get_category returns 404 for non-existent category.
	 */
	public function test_get_category_returns_404_for_non_existent(): void {
		$request = $this->create_mock_request( [ 'id' => 999 ] );

		Functions\when( 'get_term' )->justReturn( null );
		Functions\stubs( [
			'__' => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->get_category( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_category_not_found', $result->get_error_code() );
	}

	/**
	 * Test get_category returns category data.
	 */
	public function test_get_category_returns_category_data(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );
		$term    = $this->create_mock_term();

		Functions\stubs( [
			'get_term'               => $term,
			'get_term_link'          => 'https://example.com/category/test-term/',
			'apd_get_category_icon'  => 'dashicons-location',
			'apd_get_category_color' => '#ff0000',
			'apply_filters'          => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->get_category( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 200, $result->get_status() );

		$data = $result->get_data();
		$this->assertEquals( 1, $data['id'] );
		$this->assertEquals( 'Test Term', $data['name'] );
	}

	// =========================================================================
	// Get Single Tag Tests
	// =========================================================================

	/**
	 * Test get_tag returns 404 for non-existent tag.
	 */
	public function test_get_tag_returns_404_for_non_existent(): void {
		$request = $this->create_mock_request( [ 'id' => 999 ] );

		Functions\when( 'get_term' )->justReturn( null );
		Functions\stubs( [
			'__' => function ( $text ) {
				return $text;
			},
		] );

		$result = $this->endpoint->get_tag( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_tag_not_found', $result->get_error_code() );
	}

	/**
	 * Test get_tag returns tag data.
	 */
	public function test_get_tag_returns_tag_data(): void {
		$request = $this->create_mock_request( [ 'id' => 1 ] );
		$term    = $this->create_mock_term( [ 'taxonomy' => 'apd_tag' ] );

		Functions\stubs( [
			'get_term'      => $term,
			
			'get_term_link' => 'https://example.com/tag/test-term/',
			'apply_filters' => function ( $hook, $data ) {
				return $data;
			},
		] );

		$result = $this->endpoint->get_tag( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertEquals( 200, $result->get_status() );

		$data = $result->get_data();
		$this->assertEquals( 1, $data['id'] );
		$this->assertArrayNotHasKey( 'icon', $data );
	}
}
