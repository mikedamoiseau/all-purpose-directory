<?php
/**
 * ListingQueryBuilder Unit Tests.
 *
 * @package APD\Tests\Unit\Listing
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Listing;

use APD\Listing\ListingQueryBuilder;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Test class for ListingQueryBuilder.
 *
 * @covers \APD\Listing\ListingQueryBuilder
 */
final class ListingQueryBuilderTest extends UnitTestCase {

	/**
	 * Builder instance.
	 *
	 * @var ListingQueryBuilder
	 */
	private ListingQueryBuilder $builder;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->builder = new ListingQueryBuilder();
	}

	/**
	 * Test basic query with defaults.
	 */
	public function test_build_returns_base_query(): void {
		$args = $this->builder->build( [] );

		$this->assertSame( 'apd_listing', $args['post_type'] );
		$this->assertSame( 'publish', $args['post_status'] );
		$this->assertSame( 12, $args['posts_per_page'] );
		$this->assertSame( 1, $args['paged'] );
		$this->assertSame( 'date', $args['orderby'] );
		$this->assertSame( 'DESC', $args['order'] );
	}

	/**
	 * Test count is sanitized with absint.
	 */
	public function test_count_is_sanitized(): void {
		$args = $this->builder->build( [ 'count' => '25' ] );
		$this->assertSame( 25, $args['posts_per_page'] );

		$args = $this->builder->build( [ 'count' => -5 ] );
		$this->assertSame( 5, $args['posts_per_page'] );
	}

	/**
	 * Test paged parameter.
	 */
	public function test_paged_parameter(): void {
		$args = $this->builder->build( [ 'paged' => 3 ] );
		$this->assertSame( 3, $args['paged'] );
	}

	/**
	 * Test post__in with comma-separated string IDs.
	 */
	public function test_ids_string_creates_post_in(): void {
		$args = $this->builder->build( [ 'ids' => '1,2,3' ] );

		$this->assertSame( [ 1, 2, 3 ], $args['post__in'] );
		$this->assertSame( 'post__in', $args['orderby'] );
	}

	/**
	 * Test post__in with array IDs.
	 */
	public function test_ids_array_creates_post_in(): void {
		$args = $this->builder->build( [ 'ids' => [ 10, 20, 30 ] ] );

		$this->assertSame( [ 10, 20, 30 ], $args['post__in'] );
		$this->assertSame( 'post__in', $args['orderby'] );
	}

	/**
	 * Test IDs are sanitized with absint.
	 */
	public function test_ids_are_sanitized(): void {
		$args = $this->builder->build( [ 'ids' => '5,-3,abc,10' ] );

		// absint('-3') = 3, absint('abc') = 0 (filtered out), absint('5') = 5, absint('10') = 10
		$this->assertContains( 5, $args['post__in'] );
		$this->assertContains( 10, $args['post__in'] );
		$this->assertNotContains( 0, $args['post__in'] );
	}

	/**
	 * Test empty IDs are ignored.
	 */
	public function test_empty_ids_ignored(): void {
		$args = $this->builder->build( [ 'ids' => '' ] );

		$this->assertArrayNotHasKey( 'post__in', $args );
	}

	/**
	 * Test post__not_in with string exclude.
	 */
	public function test_exclude_string_creates_post_not_in(): void {
		$args = $this->builder->build( [ 'exclude' => '5,10' ] );

		$this->assertSame( [ 5, 10 ], $args['post__not_in'] );
	}

	/**
	 * Test exclude with array.
	 */
	public function test_exclude_array_creates_post_not_in(): void {
		$args = $this->builder->build( [ 'exclude' => [ 7, 8 ] ] );

		$this->assertSame( [ 7, 8 ], $args['post__not_in'] );
	}

	/**
	 * Test empty exclude is ignored.
	 */
	public function test_empty_exclude_ignored(): void {
		$args = $this->builder->build( [ 'exclude' => '' ] );

		$this->assertArrayNotHasKey( 'post__not_in', $args );
	}

	/**
	 * Test category filter creates tax_query.
	 */
	public function test_category_creates_tax_query(): void {
		$args = $this->builder->build( [ 'category' => 'restaurants,cafes' ] );

		$this->assertArrayHasKey( 'tax_query', $args );
		$this->assertSame( 'apd_category', $args['tax_query'][0]['taxonomy'] );
		$this->assertSame( 'slug', $args['tax_query'][0]['field'] );
		$this->assertSame( [ 'restaurants', 'cafes' ], $args['tax_query'][0]['terms'] );
	}

	/**
	 * Test tag filter creates tax_query.
	 */
	public function test_tag_creates_tax_query(): void {
		$args = $this->builder->build( [ 'tag' => 'featured,popular' ] );

		$this->assertArrayHasKey( 'tax_query', $args );
		$this->assertSame( 'apd_tag', $args['tax_query'][0]['taxonomy'] );
		$this->assertSame( [ 'featured', 'popular' ], $args['tax_query'][0]['terms'] );
	}

	/**
	 * Test type filter creates tax_query.
	 */
	public function test_type_creates_tax_query(): void {
		$args = $this->builder->build( [ 'type' => 'business' ] );

		$this->assertArrayHasKey( 'tax_query', $args );
		$this->assertSame( \APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY, $args['tax_query'][0]['taxonomy'] );
		$this->assertSame( [ 'business' ], $args['tax_query'][0]['terms'] );
	}

	/**
	 * Test multiple taxonomy filters get AND relation.
	 */
	public function test_multiple_taxonomy_filters_use_and_relation(): void {
		$args = $this->builder->build( [
			'category' => 'restaurants',
			'tag'      => 'featured',
		] );

		$this->assertSame( 'AND', $args['tax_query']['relation'] );
		$this->assertCount( 3, $args['tax_query'] ); // 2 queries + 'relation' key
	}

	/**
	 * Test single taxonomy filter has no relation key.
	 */
	public function test_single_taxonomy_filter_no_relation(): void {
		$args = $this->builder->build( [ 'category' => 'restaurants' ] );

		$this->assertArrayNotHasKey( 'relation', $args['tax_query'] );
	}

	/**
	 * Test taxonomy terms are sanitized with sanitize_key.
	 */
	public function test_taxonomy_terms_are_sanitized(): void {
		$args = $this->builder->build( [ 'category' => 'Restaurants, CAFES ' ] );

		// sanitize_key lowercases and strips whitespace.
		$this->assertSame( [ 'restaurants', 'cafes' ], $args['tax_query'][0]['terms'] );
	}

	/**
	 * Test empty taxonomy filters are ignored.
	 */
	public function test_empty_taxonomy_filters_ignored(): void {
		$args = $this->builder->build( [
			'category' => '',
			'tag'      => '',
			'type'     => '',
		] );

		$this->assertArrayNotHasKey( 'tax_query', $args );
	}

	/**
	 * Test order ASC.
	 */
	public function test_order_asc(): void {
		$args = $this->builder->build( [ 'order' => 'ASC' ] );
		$this->assertSame( 'ASC', $args['order'] );
	}

	/**
	 * Test order defaults to DESC for invalid values.
	 */
	public function test_invalid_order_defaults_to_desc(): void {
		$args = $this->builder->build( [ 'order' => 'INVALID' ] );
		$this->assertSame( 'DESC', $args['order'] );
	}

	/**
	 * Test valid orderby values.
	 */
	public function test_valid_orderby_values(): void {
		$valid = [ 'date', 'title', 'modified', 'rand', 'menu_order' ];

		foreach ( $valid as $orderby ) {
			$args = $this->builder->build( [ 'orderby' => $orderby ] );
			$this->assertSame( $orderby, $args['orderby'], "orderby should accept '{$orderby}'" );
		}
	}

	/**
	 * Test invalid orderby defaults to date.
	 */
	public function test_invalid_orderby_defaults_to_date(): void {
		$args = $this->builder->build( [ 'orderby' => 'invalid_field' ] );
		$this->assertSame( 'date', $args['orderby'] );
	}

	/**
	 * Test views orderby uses meta_value_num.
	 */
	public function test_views_orderby_uses_meta(): void {
		$args = $this->builder->build( [ 'orderby' => 'views' ] );

		$this->assertSame( 'meta_value_num', $args['orderby'] );
		$this->assertSame( '_apd_views_count', $args['meta_key'] );
	}

	/**
	 * Test ordering is overridden when IDs are specified.
	 */
	public function test_ids_override_ordering(): void {
		$args = $this->builder->build( [
			'ids'     => '1,2,3',
			'orderby' => 'title',
			'order'   => 'ASC',
		] );

		$this->assertSame( 'post__in', $args['orderby'] );
		$this->assertArrayNotHasKey( 'order', $args );
	}

	/**
	 * Test numeric author ID.
	 */
	public function test_numeric_author(): void {
		$args = $this->builder->build( [ 'author' => '42' ] );

		$this->assertSame( 42, $args['author'] );
	}

	/**
	 * Test string author username lookup.
	 */
	public function test_string_author_lookup(): void {
		$mockUser     = new \stdClass();
		$mockUser->ID = 99;

		Functions\when( 'get_user_by' )->justReturn( $mockUser );
		Functions\when( 'sanitize_user' )->returnArg();

		$args = $this->builder->build( [ 'author' => 'johndoe' ] );

		$this->assertSame( 99, $args['author'] );
	}

	/**
	 * Test string author not found.
	 */
	public function test_string_author_not_found(): void {
		Functions\when( 'get_user_by' )->justReturn( false );
		Functions\when( 'sanitize_user' )->returnArg();

		$args = $this->builder->build( [ 'author' => 'nonexistent' ] );

		$this->assertArrayNotHasKey( 'author', $args );
	}

	/**
	 * Test empty author is ignored.
	 */
	public function test_empty_author_ignored(): void {
		$args = $this->builder->build( [ 'author' => '' ] );

		$this->assertArrayNotHasKey( 'author', $args );
	}

	/**
	 * Test full parameter combination.
	 */
	public function test_full_parameter_combination(): void {
		$args = $this->builder->build( [
			'count'    => 20,
			'paged'    => 2,
			'category' => 'restaurants',
			'tag'      => 'featured',
			'orderby'  => 'title',
			'order'    => 'ASC',
			'exclude'  => '5,10',
		] );

		$this->assertSame( 20, $args['posts_per_page'] );
		$this->assertSame( 2, $args['paged'] );
		$this->assertSame( 'title', $args['orderby'] );
		$this->assertSame( 'ASC', $args['order'] );
		$this->assertSame( [ 5, 10 ], $args['post__not_in'] );
		$this->assertSame( 'AND', $args['tax_query']['relation'] );
	}
}
