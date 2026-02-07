<?php
/**
 * ListingTypeTaxonomy Unit Tests.
 *
 * @package APD\Tests\Unit\Taxonomy
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Taxonomy;

use APD\Taxonomy\ListingTypeTaxonomy;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;

/**
 * Test class for ListingTypeTaxonomy.
 */
final class ListingTypeTaxonomyTest extends UnitTestCase {

	/**
	 * ListingTypeTaxonomy instance.
	 *
	 * @var ListingTypeTaxonomy
	 */
	private ListingTypeTaxonomy $taxonomy;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->taxonomy = new ListingTypeTaxonomy();
	}

	/**
	 * Test TAXONOMY constant value.
	 */
	public function test_taxonomy_constant(): void {
		$this->assertSame( 'apd_listing_type', ListingTypeTaxonomy::TAXONOMY );
	}

	/**
	 * Test DEFAULT_TERM constant value.
	 */
	public function test_default_term_constant(): void {
		$this->assertSame( 'general', ListingTypeTaxonomy::DEFAULT_TERM );
	}

	/**
	 * Test register calls register_taxonomy with correct arguments.
	 */
	public function test_register_calls_register_taxonomy(): void {
		Functions\expect( 'register_taxonomy' )
			->once()
			->with(
				'apd_listing_type',
				'apd_listing',
				\Mockery::type( 'array' )
			);

		$this->taxonomy->register();
	}

	/**
	 * Test taxonomy args are hidden from public UI.
	 */
	public function test_taxonomy_args_are_hidden(): void {
		$captured_args = null;

		Functions\expect( 'register_taxonomy' )
			->once()
			->andReturnUsing( function ( $taxonomy, $object_type, $args ) use ( &$captured_args ) {
				$captured_args = $args;
			} );

		$this->taxonomy->register();

		$this->assertFalse( $captured_args['public'] );
		$this->assertFalse( $captured_args['publicly_queryable'] );
		$this->assertFalse( $captured_args['show_ui'] );
		$this->assertTrue( $captured_args['show_in_rest'] );
		$this->assertFalse( $captured_args['rewrite'] );
	}

	/**
	 * Test ensure_default_term creates "General" term when it does not exist.
	 */
	public function test_ensure_default_term_creates_general(): void {
		Functions\expect( 'term_exists' )
			->once()
			->with( 'general', 'apd_listing_type' )
			->andReturn( null );

		Functions\expect( 'wp_insert_term' )
			->once()
			->with(
				'General',
				'apd_listing_type',
				\Mockery::on( function ( $args ) {
					return isset( $args['slug'] ) && $args['slug'] === 'general';
				} )
			);

		$this->taxonomy->ensure_default_term();
	}

	/**
	 * Test ensure_default_term skips creation if term already exists.
	 */
	public function test_ensure_default_term_skips_if_exists(): void {
		Functions\expect( 'term_exists' )
			->once()
			->with( 'general', 'apd_listing_type' )
			->andReturn( [ 'term_id' => 1, 'term_taxonomy_id' => 1 ] );

		Functions\expect( 'wp_insert_term' )->never();

		$this->taxonomy->ensure_default_term();
	}

	/**
	 * Test on_module_registered creates term when taxonomy exists and term does not.
	 */
	public function test_on_module_registered_creates_term(): void {
		Functions\expect( 'taxonomy_exists' )
			->once()
			->with( 'apd_listing_type' )
			->andReturn( true );

		Functions\expect( 'term_exists' )
			->once()
			->with( 'url-directory', 'apd_listing_type' )
			->andReturn( null );

		Functions\expect( 'wp_insert_term' )
			->once()
			->with(
				'URL Directory',
				'apd_listing_type',
				\Mockery::on( function ( $args ) {
					return isset( $args['slug'] ) && $args['slug'] === 'url-directory';
				} )
			)
			->andReturn( [ 'term_id' => 5, 'term_taxonomy_id' => 5 ] );

		$this->taxonomy->on_module_registered( 'url-directory', [ 'name' => 'URL Directory' ] );
	}

	/**
	 * Test on_module_registered skips when term already exists.
	 */
	public function test_on_module_registered_skips_existing_term(): void {
		Functions\expect( 'taxonomy_exists' )
			->once()
			->with( 'apd_listing_type' )
			->andReturn( true );

		Functions\expect( 'term_exists' )
			->once()
			->with( 'url-directory', 'apd_listing_type' )
			->andReturn( [ 'term_id' => 5, 'term_taxonomy_id' => 5 ] );

		Functions\expect( 'wp_insert_term' )->never();

		$this->taxonomy->on_module_registered( 'url-directory', [ 'name' => 'URL Directory' ] );
	}

	/**
	 * Test on_module_registered skips if taxonomy does not exist.
	 */
	public function test_on_module_registered_skips_if_taxonomy_not_exists(): void {
		Functions\expect( 'taxonomy_exists' )
			->once()
			->with( 'apd_listing_type' )
			->andReturn( false );

		Functions\expect( 'term_exists' )->never();
		Functions\expect( 'wp_insert_term' )->never();

		$this->taxonomy->on_module_registered( 'url-directory', [ 'name' => 'URL Directory' ] );
	}

	/**
	 * Test assign_default_term assigns "general" when listing has no type.
	 */
	public function test_assign_default_term_assigns_general(): void {
		Functions\expect( 'wp_is_post_revision' )
			->once()
			->with( 42 )
			->andReturn( false );

		Functions\expect( 'wp_is_post_autosave' )
			->once()
			->with( 42 )
			->andReturn( false );

		Functions\expect( 'wp_get_object_terms' )
			->once()
			->with( 42, 'apd_listing_type' )
			->andReturn( [] );

		// Brain\Monkey's hook system intercepts apply_filters and returns the default value.
		Filters\expectApplied( 'apd_default_listing_type' )
			->once()
			->with( 'general', 42 )
			->andReturn( 'general' );

		Functions\expect( 'wp_set_object_terms' )
			->once()
			->with( 42, 'general', 'apd_listing_type' );

		$this->taxonomy->assign_default_term( 42 );
	}

	/**
	 * Test assign_default_term skips if listing already has a type.
	 */
	public function test_assign_default_term_skips_if_already_typed(): void {
		$term       = new \WP_Term();
		$term->slug = 'url-directory';

		Functions\expect( 'wp_is_post_revision' )
			->once()
			->with( 42 )
			->andReturn( false );

		Functions\expect( 'wp_is_post_autosave' )
			->once()
			->with( 42 )
			->andReturn( false );

		Functions\expect( 'wp_get_object_terms' )
			->once()
			->with( 42, 'apd_listing_type' )
			->andReturn( [ $term ] );

		Functions\expect( 'wp_set_object_terms' )->never();

		$this->taxonomy->assign_default_term( 42 );
	}

	/**
	 * Test assign_default_term skips revisions.
	 */
	public function test_assign_default_term_skips_revisions(): void {
		Functions\expect( 'wp_is_post_revision' )
			->once()
			->with( 42 )
			->andReturn( true );

		Functions\expect( 'wp_is_post_autosave' )->never();
		Functions\expect( 'wp_get_object_terms' )->never();
		Functions\expect( 'wp_set_object_terms' )->never();

		$this->taxonomy->assign_default_term( 42 );
	}

	/**
	 * Test assign_default_term uses filtered default type value.
	 */
	public function test_assign_default_term_uses_filtered_default_type(): void {
		Functions\expect( 'wp_is_post_revision' )
			->once()
			->with( 99 )
			->andReturn( false );

		Functions\expect( 'wp_is_post_autosave' )
			->once()
			->with( 99 )
			->andReturn( false );

		Functions\expect( 'wp_get_object_terms' )
			->once()
			->with( 99, 'apd_listing_type' )
			->andReturn( [] );

		// Filter returns a custom type instead of 'general'.
		Filters\expectApplied( 'apd_default_listing_type' )
			->once()
			->with( 'general', 99 )
			->andReturn( 'custom-type' );

		Functions\expect( 'wp_set_object_terms' )
			->once()
			->with( 99, 'custom-type', 'apd_listing_type' );

		$this->taxonomy->assign_default_term( 99 );
	}
}
