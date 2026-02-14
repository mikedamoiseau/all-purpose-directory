<?php
/**
 * SearchQuery Unit Tests.
 *
 * @package APD\Tests\Unit\Search
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Search;

use APD\Search\SearchQuery;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test case for SearchQuery class.
 *
 * @covers \APD\Search\SearchQuery
 */
class SearchQueryTest extends UnitTestCase {

	/**
	 * The SearchQuery instance.
	 *
	 * @var SearchQuery
	 */
	private SearchQuery $search_query;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->search_query = new SearchQuery();
	}

	/**
	 * Create a mock WP_Query with specified query vars.
	 *
	 * @param array $vars Query vars.
	 * @return \Mockery\MockInterface
	 */
	private function create_query_mock( array $vars = [] ): \Mockery\MockInterface {
		$query = Mockery::mock( 'WP_Query' );
		$query->shouldReceive( 'get' )->andReturnUsing( function ( $key ) use ( $vars ) {
			return $vars[ $key ] ?? '';
		} );

		return $query;
	}

	/**
	 * Set up wpdb mock for meta search tests.
	 *
	 * @param string $keyword The search keyword.
	 * @return void
	 */
	private function setup_wpdb_for_meta_search( string $keyword = 'pizza' ): void {
		global $wpdb;
		$wpdb           = Mockery::mock( 'wpdb' );
		$wpdb->postmeta = 'wp_postmeta';
		$wpdb->posts    = 'wp_posts';

		$wpdb->shouldReceive( 'esc_like' )->andReturnUsing( function ( $text ) {
			return addcslashes( $text, '_%\\' );
		} );

		$wpdb->shouldReceive( 'prepare' )->andReturnUsing( function ( $query, $args ) {
			// Simple mock: replace %s placeholders with quoted args.
			$args  = (array) $args;
			$index = 0;
			return preg_replace_callback( '/%s/', function () use ( $args, &$index ) {
				return "'" . ( $args[ $index++ ] ?? '' ) . "'";
			}, $query );
		} );
	}

	// =========================================================================
	// add_meta_search Tests
	// =========================================================================

	/**
	 * Test add_meta_search returns search unchanged when not a meta search.
	 */
	public function test_add_meta_search_returns_unchanged_without_flag(): void {
		$query  = $this->create_query_mock( [ 'apd_meta_search' => false ] );
		$search = " AND ((wp_posts.post_title LIKE '%test%'))";

		$result = $this->search_query->add_meta_search( $search, $query );

		$this->assertSame( $search, $result );
	}

	/**
	 * Test add_meta_search returns search unchanged when empty search.
	 */
	public function test_add_meta_search_returns_unchanged_when_empty(): void {
		$query = $this->create_query_mock( [
			'apd_meta_search' => true,
			'apd_keyword'     => 'test',
		] );

		$result = $this->search_query->add_meta_search( '', $query );

		$this->assertSame( '', $result );
	}

	/**
	 * Test add_meta_search returns search unchanged when no keyword.
	 */
	public function test_add_meta_search_returns_unchanged_without_keyword(): void {
		$this->setup_wpdb_for_meta_search();

		// Set searchable meta keys via reflection.
		$ref = new \ReflectionProperty( $this->search_query, 'searchable_meta_keys' );
		// Property is accessible since PHP 8.1.
		$ref->setValue( $this->search_query, [ '_apd_address' ] );

		$query  = $this->create_query_mock( [
			'apd_meta_search' => true,
			'apd_keyword'     => '',
		] );
		$search = " AND ((wp_posts.post_title LIKE '%test%'))";

		$result = $this->search_query->add_meta_search( $search, $query );

		$this->assertSame( $search, $result );
	}

	/**
	 * Test add_meta_search injects meta OR into single-term search clause.
	 */
	public function test_add_meta_search_injects_or_single_term(): void {
		$this->setup_wpdb_for_meta_search( 'pizza' );

		$ref = new \ReflectionProperty( $this->search_query, 'searchable_meta_keys' );
		// Property is accessible since PHP 8.1.
		$ref->setValue( $this->search_query, [ '_apd_address' ] );

		$query = $this->create_query_mock( [
			'apd_meta_search' => true,
			'apd_keyword'     => 'pizza',
		] );

		$search = " AND ((wp_posts.post_title LIKE '%pizza%' OR wp_posts.post_excerpt LIKE '%pizza%' OR wp_posts.post_content LIKE '%pizza%'))";

		$result = $this->search_query->add_meta_search( $search, $query );

		// Should contain the original conditions.
		$this->assertStringContainsString( "post_title LIKE", $result );
		$this->assertStringContainsString( "post_content LIKE", $result );

		// Should contain the meta EXISTS subquery.
		$this->assertStringContainsString( 'OR EXISTS', $result );
		$this->assertStringContainsString( 'apd_pm.meta_key IN', $result );
		$this->assertStringContainsString( 'apd_pm.meta_value LIKE', $result );

		// Should still start with AND.
		$this->assertStringStartsWith( ' AND ', $result );

		// Parentheses should be balanced.
		$opens  = substr_count( $result, '(' );
		$closes = substr_count( $result, ')' );
		$this->assertSame( $opens, $closes, 'Parentheses should be balanced' );
	}

	/**
	 * Test add_meta_search with multiple meta keys.
	 */
	public function test_add_meta_search_multiple_meta_keys(): void {
		$this->setup_wpdb_for_meta_search( 'test' );

		$ref = new \ReflectionProperty( $this->search_query, 'searchable_meta_keys' );
		// Property is accessible since PHP 8.1.
		$ref->setValue( $this->search_query, [ '_apd_address', '_apd_phone', '_apd_description' ] );

		$query = $this->create_query_mock( [
			'apd_meta_search' => true,
			'apd_keyword'     => 'test',
		] );

		$search = " AND ((wp_posts.post_title LIKE '%test%'))";

		$result = $this->search_query->add_meta_search( $search, $query );

		// Should contain meta_key IN clause with all three keys.
		$this->assertStringContainsString( '_apd_address', $result );
		$this->assertStringContainsString( '_apd_phone', $result );
		$this->assertStringContainsString( '_apd_description', $result );
	}

	/**
	 * Test add_meta_search preserves search clause structure (no ungrouped OR).
	 */
	public function test_add_meta_search_no_ungrouped_or(): void {
		$this->setup_wpdb_for_meta_search( 'pizza' );

		$ref = new \ReflectionProperty( $this->search_query, 'searchable_meta_keys' );
		// Property is accessible since PHP 8.1.
		$ref->setValue( $this->search_query, [ '_apd_address' ] );

		$query = $this->create_query_mock( [
			'apd_meta_search' => true,
			'apd_keyword'     => 'pizza',
		] );

		$search = " AND ((wp_posts.post_title LIKE '%pizza%'))";

		$result = $this->search_query->add_meta_search( $search, $query );

		// The meta condition should NOT be appended as a bare OR outside parens.
		// It should be injected inside the search clause's parentheses.
		$this->assertStringStartsWith( ' AND (', $result );

		// The result should end with closing parens, not a bare condition.
		$trimmed = rtrim( $result );
		$this->assertStringEndsWith( '))', $trimmed );
	}

	/**
	 * Test add_meta_search returns unchanged when no searchable meta keys.
	 */
	public function test_add_meta_search_returns_unchanged_without_meta_keys(): void {
		$query  = $this->create_query_mock( [
			'apd_meta_search' => true,
			'apd_keyword'     => 'test',
		] );
		$search = " AND ((wp_posts.post_title LIKE '%test%'))";

		// searchable_meta_keys is empty by default.
		$result = $this->search_query->add_meta_search( $search, $query );

		$this->assertSame( $search, $result );
	}

	// =========================================================================
	// add_meta_join Tests
	// =========================================================================

	/**
	 * Test add_meta_join returns unchanged when not a meta search.
	 */
	public function test_add_meta_join_returns_unchanged_without_flag(): void {
		$query = $this->create_query_mock( [ 'apd_meta_search' => false ] );

		$result = $this->search_query->add_meta_join( '', $query );

		$this->assertSame( '', $result );
	}

	/**
	 * Test add_meta_join returns unchanged (EXISTS replaces LEFT JOIN).
	 */
	public function test_add_meta_join_returns_unchanged(): void {
		$ref = new \ReflectionProperty( $this->search_query, 'searchable_meta_keys' );
		// Property is accessible since PHP 8.1.
		$ref->setValue( $this->search_query, [ '_apd_address' ] );

		$query = $this->create_query_mock( [ 'apd_meta_search' => true ] );

		$result = $this->search_query->add_meta_join( '', $query );

		// EXISTS subquery eliminates need for LEFT JOIN.
		$this->assertSame( '', $result );
	}

	// =========================================================================
	// add_distinct Tests
	// =========================================================================

	/**
	 * Test add_distinct returns unchanged (EXISTS eliminates duplicates).
	 */
	public function test_add_distinct_returns_unchanged_for_meta_search(): void {
		$ref = new \ReflectionProperty( $this->search_query, 'searchable_meta_keys' );
		// Property is accessible since PHP 8.1.
		$ref->setValue( $this->search_query, [ '_apd_address' ] );

		$query = $this->create_query_mock( [ 'apd_meta_search' => true ] );

		$result = $this->search_query->add_distinct( '', $query );

		// EXISTS subquery eliminates need for DISTINCT.
		$this->assertSame( '', $result );
	}

	/**
	 * Test add_distinct returns unchanged when not a meta search.
	 */
	public function test_add_distinct_returns_unchanged_without_flag(): void {
		$query = $this->create_query_mock( [ 'apd_meta_search' => false ] );

		$result = $this->search_query->add_distinct( '', $query );

		$this->assertSame( '', $result );
	}

	// =========================================================================
	// Orderby Tests
	// =========================================================================

	/**
	 * Test get_orderby_options returns expected keys.
	 */
	public function test_get_orderby_options_returns_expected_keys(): void {
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$options = $this->search_query->get_orderby_options();

		$this->assertArrayHasKey( 'date', $options );
		$this->assertArrayHasKey( 'title', $options );
		$this->assertArrayHasKey( 'views', $options );
		$this->assertArrayHasKey( 'random', $options );
	}

	/**
	 * Test get_current_orderby returns default when not set.
	 */
	public function test_get_current_orderby_returns_default(): void {
		$_GET = [];

		$result = $this->search_query->get_current_orderby();

		$this->assertSame( 'date', $result );
	}

	/**
	 * Test get_current_order returns default when not set.
	 */
	public function test_get_current_order_returns_default(): void {
		$_GET = [];

		$result = $this->search_query->get_current_order();

		$this->assertSame( 'DESC', $result );
	}

	/**
	 * Test get_current_order sanitizes invalid values.
	 */
	public function test_get_current_order_sanitizes_invalid(): void {
		$_GET = [ 'apd_order' => 'INVALID' ];

		$result = $this->search_query->get_current_order();

		$this->assertSame( 'DESC', $result );
	}

	/**
	 * Test get_current_keyword returns empty when not set.
	 */
	public function test_get_current_keyword_returns_empty(): void {
		$_GET = [];

		$result = $this->search_query->get_current_keyword();

		$this->assertSame( '', $result );
	}

	/**
	 * Clean up after tests.
	 */
	protected function tearDown(): void {
		global $wpdb;
		$wpdb = null;
		$_GET = [];
		parent::tearDown();
	}
}
