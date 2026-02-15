<?php
/**
 * Tests for SQL safety contracts in plugin query builders.
 *
 * @package APD\Tests\Unit\Security
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Security;

/**
 * SqlInjectionTest verifies plugin-side query constraints and allowlists.
 *
 * Note: SQL helper behavior in WordPress core is covered by integration tests.
 */
class SqlInjectionTest extends SecurityTestCase {

	/**
	 * Test SearchQuery defines a fixed orderby allowlist.
	 */
	public function test_search_query_uses_orderby_allowlist(): void {
		$source = file_get_contents( __DIR__ . '/../../../src/Search/SearchQuery.php' );

		$this->assertStringContainsString( 'private const ORDERBY_OPTIONS = [', $source );
		$this->assertStringContainsString( '\'date\'', $source );
		$this->assertStringContainsString( '\'title\'', $source );
		$this->assertStringContainsString( '\'views\'', $source );
		$this->assertStringContainsString( '\'random\'', $source );
	}

	/**
	 * Test SearchQuery sanitizes orderby/order request values before usage.
	 */
	public function test_search_query_sanitizes_order_inputs(): void {
		$source = file_get_contents( __DIR__ . '/../../../src/Search/SearchQuery.php' );

		$this->assertStringContainsString( 'sanitize_key( (string) $request[\'apd_orderby\'] )', $source );
		$this->assertStringContainsString( 'sanitize_key( (string) $request[\'apd_order\'] )', $source );
	}

	/**
	 * Test SearchQuery sanitizes meta keys before using them in SQL fragments.
	 */
	public function test_search_query_sanitizes_meta_keys(): void {
		$source = file_get_contents( __DIR__ . '/../../../src/Search/SearchQuery.php' );

		$this->assertStringContainsString( 'sanitize_key( $field_registry->get_meta_key( $field_name ) )', $source );
		$this->assertStringContainsString( 'return array_map( \'sanitize_key\', $filtered_keys );', $source );
	}

	/**
	 * Test review and inquiry manager paths expose bounded pagination args.
	 */
	public function test_endpoint_pagination_contracts_use_number_and_offset(): void {
		$reviews_source   = file_get_contents( __DIR__ . '/../../../src/Api/Endpoints/ReviewsEndpoint.php' );
		$inquiries_source = file_get_contents( __DIR__ . '/../../../src/Api/Endpoints/InquiriesEndpoint.php' );

		$this->assertMatchesRegularExpression( '/\'number\'\s*=>\s*\$per_page/', $reviews_source );
		$this->assertMatchesRegularExpression( '/\'offset\'\s*=>\s*\$this->get_pagination_offset\( \$page, \$per_page \)/', $reviews_source );

		$this->assertMatchesRegularExpression( '/\'number\'\s*=>\s*\$per_page/', $inquiries_source );
		$this->assertMatchesRegularExpression( '/\'offset\'\s*=>\s*\( \$page - 1 \) \* \$per_page/', $inquiries_source );
	}

	/**
	 * Test review manager maps author filter to user_id query arg.
	 */
	public function test_review_manager_maps_author_filter_to_user_id(): void {
		$source = file_get_contents( __DIR__ . '/../../../src/Review/ReviewManager.php' );

		$this->assertStringContainsString( '$args[\'author\']', $source );
		$this->assertStringContainsString( '$author_id = absint( $args[\'author\'] );', $source );
		$this->assertStringContainsString( '$query_args[\'user_id\'] = $author_id;', $source );
	}
}
