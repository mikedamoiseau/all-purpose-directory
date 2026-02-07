<?php
/**
 * Listing Type Helper Functions Unit Tests.
 *
 * Tests the listing type helper functions defined in includes/functions.php.
 *
 * @package APD\Tests\Unit\Taxonomy
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Taxonomy;

use APD\Taxonomy\ListingTypeTaxonomy;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

// Load the listing type helper functions for testing.
// Uses a local copy to avoid loading the full includes/functions.php, which
// would cause Patchwork DefinedTooEarly errors for other test files.
require_once __DIR__ . '/listing-type-test-functions.php';

/**
 * Test class for listing type helper functions.
 */
final class ListingTypeFunctionsTest extends UnitTestCase {

	/**
	 * Test apd_get_listing_type_taxonomy returns the taxonomy constant.
	 */
	public function test_get_listing_type_taxonomy_returns_constant(): void {
		$result = \apd_get_listing_type_taxonomy();

		$this->assertSame( 'apd_listing_type', $result );
	}

	/**
	 * Test apd_get_listing_type returns term slug when terms exist.
	 */
	public function test_get_listing_type_returns_slug(): void {
		$term       = new \WP_Term();
		$term->slug = 'url-directory';

		Functions\expect( 'wp_get_object_terms' )
			->once()
			->with( 42, 'apd_listing_type' )
			->andReturn( [ $term ] );

		$result = \apd_get_listing_type( 42 );

		$this->assertSame( 'url-directory', $result );
	}

	/**
	 * Test apd_get_listing_type returns default when no terms.
	 */
	public function test_get_listing_type_returns_default_when_empty(): void {
		Functions\expect( 'wp_get_object_terms' )
			->once()
			->with( 42, 'apd_listing_type' )
			->andReturn( [] );

		$result = \apd_get_listing_type( 42 );

		$this->assertSame( 'general', $result );
	}

	/**
	 * Test apd_get_listing_type returns default on WP_Error.
	 */
	public function test_get_listing_type_returns_default_on_error(): void {
		$error = new \WP_Error( 'invalid_taxonomy', 'Taxonomy does not exist.' );

		Functions\expect( 'wp_get_object_terms' )
			->once()
			->with( 42, 'apd_listing_type' )
			->andReturn( $error );

		$result = \apd_get_listing_type( 42 );

		$this->assertSame( 'general', $result );
	}

	/**
	 * Test apd_set_listing_type calls wp_set_object_terms and returns true on success.
	 */
	public function test_set_listing_type_calls_wp_set_object_terms(): void {
		// sanitize_key is already stubbed as passthrough in UnitTestCase.
		Functions\expect( 'wp_set_object_terms' )
			->once()
			->with( 42, 'url-directory', 'apd_listing_type' )
			->andReturn( [ 5 ] );

		$result = \apd_set_listing_type( 42, 'url-directory' );

		$this->assertTrue( $result );
	}

	/**
	 * Test apd_set_listing_type returns false on WP_Error.
	 */
	public function test_set_listing_type_returns_false_on_error(): void {
		$error = new \WP_Error( 'invalid_taxonomy', 'Taxonomy does not exist.' );

		// sanitize_key is already stubbed as passthrough in UnitTestCase.
		Functions\expect( 'wp_set_object_terms' )
			->once()
			->with( 42, 'url-directory', 'apd_listing_type' )
			->andReturn( $error );

		$result = \apd_set_listing_type( 42, 'url-directory' );

		$this->assertFalse( $result );
	}

	/**
	 * Test apd_listing_is_type returns true for matching type.
	 */
	public function test_listing_is_type_returns_true_for_match(): void {
		$term       = new \WP_Term();
		$term->slug = 'url-directory';

		Functions\expect( 'wp_get_object_terms' )
			->once()
			->with( 42, 'apd_listing_type' )
			->andReturn( [ $term ] );

		// sanitize_key is already stubbed as passthrough in UnitTestCase.
		$result = \apd_listing_is_type( 42, 'url-directory' );

		$this->assertTrue( $result );
	}

	/**
	 * Test apd_listing_is_type returns false for mismatched type.
	 */
	public function test_listing_is_type_returns_false_for_mismatch(): void {
		$term       = new \WP_Term();
		$term->slug = 'general';

		Functions\expect( 'wp_get_object_terms' )
			->once()
			->with( 42, 'apd_listing_type' )
			->andReturn( [ $term ] );

		// sanitize_key is already stubbed as passthrough in UnitTestCase.
		$result = \apd_listing_is_type( 42, 'url-directory' );

		$this->assertFalse( $result );
	}

	/**
	 * Test apd_get_listing_types returns array of terms.
	 */
	public function test_get_listing_types_returns_terms(): void {
		$term1       = new \WP_Term();
		$term1->slug = 'general';
		$term2       = new \WP_Term();
		$term2->slug = 'url-directory';

		Functions\expect( 'get_terms' )
			->once()
			->with( \Mockery::on( function ( $args ) {
				return $args['taxonomy'] === 'apd_listing_type'
					&& $args['hide_empty'] === false;
			} ) )
			->andReturn( [ $term1, $term2 ] );

		$result = \apd_get_listing_types();

		$this->assertCount( 2, $result );
		$this->assertSame( 'general', $result[0]->slug );
		$this->assertSame( 'url-directory', $result[1]->slug );
	}

	/**
	 * Test apd_get_listing_types returns empty array on WP_Error.
	 */
	public function test_get_listing_types_returns_empty_on_error(): void {
		$error = new \WP_Error( 'invalid_taxonomy', 'Taxonomy does not exist.' );

		Functions\expect( 'get_terms' )
			->once()
			->andReturn( $error );

		$result = \apd_get_listing_types();

		$this->assertSame( [], $result );
	}

	/**
	 * Test apd_get_listing_type_term returns WP_Term when found.
	 */
	public function test_get_listing_type_term_returns_term(): void {
		$term              = new \WP_Term();
		$term->term_id     = 5;
		$term->slug        = 'url-directory';
		$term->name        = 'URL Directory';
		$term->taxonomy    = 'apd_listing_type';

		// sanitize_key is already stubbed as passthrough in UnitTestCase.
		Functions\expect( 'get_term_by' )
			->once()
			->with( 'slug', 'url-directory', 'apd_listing_type' )
			->andReturn( $term );

		$result = \apd_get_listing_type_term( 'url-directory' );

		$this->assertInstanceOf( \WP_Term::class, $result );
		$this->assertSame( 5, $result->term_id );
		$this->assertSame( 'url-directory', $result->slug );
	}

	/**
	 * Test apd_get_listing_type_term returns null when not found.
	 */
	public function test_get_listing_type_term_returns_null_when_not_found(): void {
		// sanitize_key is already stubbed as passthrough in UnitTestCase.
		Functions\expect( 'get_term_by' )
			->once()
			->with( 'slug', 'nonexistent', 'apd_listing_type' )
			->andReturn( false );

		$result = \apd_get_listing_type_term( 'nonexistent' );

		$this->assertNull( $result );
	}

	/**
	 * Test apd_get_listing_type_count returns term count.
	 */
	public function test_get_listing_type_count_returns_count(): void {
		$term          = new \WP_Term();
		$term->term_id = 5;
		$term->slug    = 'url-directory';
		$term->count   = 12;

		// sanitize_key is already stubbed as passthrough in UnitTestCase.
		Functions\expect( 'get_term_by' )
			->once()
			->with( 'slug', 'url-directory', 'apd_listing_type' )
			->andReturn( $term );

		$result = \apd_get_listing_type_count( 'url-directory' );

		$this->assertSame( 12, $result );
	}

	/**
	 * Test apd_get_listing_type_count returns zero when term not found.
	 */
	public function test_get_listing_type_count_returns_zero_when_not_found(): void {
		// sanitize_key is already stubbed as passthrough in UnitTestCase.
		Functions\expect( 'get_term_by' )
			->once()
			->with( 'slug', 'nonexistent', 'apd_listing_type' )
			->andReturn( false );

		$result = \apd_get_listing_type_count( 'nonexistent' );

		$this->assertSame( 0, $result );
	}
}
