<?php
/**
 * Tests for query optimization across the plugin
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Query Optimization test case
 *
 * Tests that performance optimizations are applied to database queries.
 */
class QueryOptimizationTest extends TestCase {

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        \Brain\Monkey\setUp();

        // Define constants if not defined
        if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
            define( 'HOUR_IN_SECONDS', 3600 );
        }
        if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
            define( 'MINUTE_IN_SECONDS', 60 );
        }

        Functions\stubs( [
            'add_action'          => null,
            'add_filter'          => null,
            'wp_cache_get'        => false,
            'wp_cache_set'        => true,
            'get_transient'       => false,
            'set_transient'       => true,
            'wp_cache_delete'     => true,
            'delete_transient'    => true,
            'wp_parse_args'       => function ( $args, $defaults ) {
                return array_merge( $defaults, $args );
            },
            'apply_filters'       => function ( $tag, $value ) {
                return $value;
            },
            'apd_get_listing_post_type' => 'apd_listing',
            'apd_get_category_taxonomy' => 'apd_category',
            'apd_get_tag_taxonomy'      => 'apd_tag',
        ] );
    }

    /**
     * Tear down test environment
     *
     * @return void
     */
    protected function tearDown(): void {
        \Brain\Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test that Performance::get_related_listings includes no_found_rows in source
     *
     * @return void
     */
    public function test_performance_related_listings_uses_no_found_rows(): void {
        $source = file_get_contents( __DIR__ . '/../../../src/Core/Performance.php' );

        // Check that get_related_listings includes no_found_rows
        $this->assertStringContainsString(
            "'no_found_rows'  => true",
            $source,
            'Performance::get_related_listings should include no_found_rows optimization'
        );
    }

    /**
     * Test that Performance::get_dashboard_stats uses no_found_rows
     *
     * @return void
     */
    public function test_performance_dashboard_stats_uses_no_found_rows(): void {
        $source = file_get_contents( __DIR__ . '/../../../src/Core/Performance.php' );

        // Find the get_dashboard_stats method and check for no_found_rows
        $pattern = '/get_dashboard_stats.*?no_found_rows/s';
        $this->assertMatchesRegularExpression(
            $pattern,
            $source,
            'Performance::get_dashboard_stats should include no_found_rows optimization'
        );
    }

    /**
     * Test that Performance::get_popular_listings uses no_found_rows
     *
     * @return void
     */
    public function test_performance_popular_listings_uses_no_found_rows(): void {
        $source = file_get_contents( __DIR__ . '/../../../src/Core/Performance.php' );

        // Find the get_popular_listings method and check for no_found_rows
        $pattern = '/get_popular_listings.*?no_found_rows/s';
        $this->assertMatchesRegularExpression(
            $pattern,
            $source,
            'Performance::get_popular_listings should include no_found_rows optimization'
        );
    }

    /**
     * Test that apd_get_related_listings includes no_found_rows in defaults
     *
     * @return void
     */
    public function test_related_listings_function_has_no_found_rows(): void {
        // Read the source file and check for no_found_rows
        $source = file_get_contents( __DIR__ . '/../../../includes/functions.php' );

        // Check that the apd_get_related_listings function includes no_found_rows
        $this->assertStringContainsString(
            "'no_found_rows'",
            $source,
            'apd_get_related_listings should include no_found_rows optimization'
        );
    }

    /**
     * Test that RatingCalculator::recalculate_all includes no_found_rows
     *
     * @return void
     */
    public function test_rating_calculator_recalculate_all_has_no_found_rows(): void {
        $source = file_get_contents( __DIR__ . '/../../../src/Review/RatingCalculator.php' );

        // Check that recalculate_all includes no_found_rows in its get_posts call
        $this->assertStringContainsString(
            "'no_found_rows'  => true",
            $source,
            'RatingCalculator::recalculate_all should include no_found_rows optimization'
        );
    }

    /**
     * Test that ReviewModeration listing filter includes no_found_rows
     *
     * @return void
     */
    public function test_review_moderation_filter_has_no_found_rows(): void {
        $source = file_get_contents( __DIR__ . '/../../../src/Admin/ReviewModeration.php' );

        // Check that render_listing_filter includes no_found_rows
        $this->assertStringContainsString(
            "'no_found_rows'  => true",
            $source,
            'ReviewModeration::render_listing_filter should include no_found_rows optimization'
        );
    }

    /**
     * Test that FavoritesEndpoint includes no_found_rows
     *
     * @return void
     */
    public function test_favorites_endpoint_has_no_found_rows(): void {
        $source = file_get_contents( __DIR__ . '/../../../src/Api/Endpoints/FavoritesEndpoint.php' );

        // Check that get_favorite_listings includes no_found_rows
        $this->assertStringContainsString(
            "'no_found_rows'  => true",
            $source,
            'FavoritesEndpoint::get_favorite_listings should include no_found_rows optimization'
        );
    }

    /**
     * Test that Dashboard counting uses no_found_rows
     *
     * @return void
     */
    public function test_dashboard_count_uses_single_grouped_query(): void {
        $source = file_get_contents( __DIR__ . '/../../../src/Frontend/Dashboard/Dashboard.php' );

        // Check that dashboard uses a single grouped SQL query instead of multiple WP_Query calls
        $this->assertStringContainsString(
            'GROUP BY post_status',
            $source,
            'Dashboard::count_user_listings_by_status should use a single grouped SQL query'
        );
    }

    /**
     * Test that Dashboard counting uses prepared SQL for safety.
     *
     * @return void
     */
    public function test_dashboard_count_uses_prepared_sql(): void {
        $source = file_get_contents( __DIR__ . '/../../../src/Frontend/Dashboard/Dashboard.php' );

        // Check that the query uses wpdb->prepare for safety
        $this->assertStringContainsString(
            'wpdb->prepare',
            $source,
            'Dashboard::count_user_listings_by_status should use prepared SQL'
        );
    }

    /**
     * Test that apd_increment_listing_views uses atomic SQL UPDATE.
     *
     * @return void
     */
    public function test_increment_views_uses_atomic_sql(): void {
        $source = file_get_contents( __DIR__ . '/../../../includes/functions.php' );

        $this->assertStringContainsString(
            'meta_value = meta_value + 1',
            $source,
            'apd_increment_listing_views should use atomic SQL increment'
        );
    }

    /**
     * Test that apd_increment_listing_views uses prepared SQL.
     *
     * @return void
     */
    public function test_increment_views_uses_prepared_sql(): void {
        $source = file_get_contents( __DIR__ . '/../../../includes/functions.php' );

        // The function should use wpdb->prepare for the atomic update
        $pattern = '/apd_increment_listing_views.*?wpdb->prepare/s';
        $this->assertMatchesRegularExpression(
            $pattern,
            $source,
            'apd_increment_listing_views should use prepared SQL for atomic increment'
        );
    }

    /**
     * Test that apd_increment_listing_views handles first view with add_post_meta.
     *
     * @return void
     */
    public function test_increment_views_handles_first_view(): void {
        $source = file_get_contents( __DIR__ . '/../../../includes/functions.php' );

        // The function should fall back to add_post_meta for first views
        $pattern = '/apd_increment_listing_views.*?add_post_meta/s';
        $this->assertMatchesRegularExpression(
            $pattern,
            $source,
            'apd_increment_listing_views should use add_post_meta for first view fallback'
        );
    }

    /**
     * Test that SearchQuery uses EXISTS subquery instead of LEFT JOIN.
     *
     * @return void
     */
    public function test_search_query_uses_exists_subquery(): void {
        $source = file_get_contents( __DIR__ . '/../../../src/Search/SearchQuery.php' );

        $this->assertStringContainsString(
            'EXISTS (SELECT 1 FROM',
            $source,
            'SearchQuery::add_meta_search should use EXISTS subquery'
        );
    }

    /**
     * Test that SearchQuery add_meta_join does not execute a LEFT JOIN.
     *
     * @return void
     */
    public function test_search_query_no_left_join_in_code(): void {
        $source = file_get_contents( __DIR__ . '/../../../src/Search/SearchQuery.php' );

        // Extract the add_meta_join method body (between the function signature and the next public/private/protected method).
        // The method should just return $join unchanged — no SQL concatenation.
        $this->assertStringNotContainsString(
            '$join .=',
            $source,
            'SearchQuery::add_meta_join should not append to $join (LEFT JOIN replaced by EXISTS subquery)'
        );
    }

    /**
     * Test that RatingCalculator uses fields => ids for bulk operations
     *
     * @return void
     */
    public function test_rating_calculator_uses_fields_ids(): void {
        $source = file_get_contents( __DIR__ . '/../../../src/Review/RatingCalculator.php' );

        // Check that recalculate_all uses fields => 'ids'
        $this->assertStringContainsString(
            "'fields'         => 'ids'",
            $source,
            'RatingCalculator::recalculate_all should use fields=ids optimization'
        );
    }
}
