<?php
/**
 * TemplateLoader unit tests.
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use APD\Core\TemplateLoader;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;

/**
 * Test case for TemplateLoader class.
 */
final class TemplateLoaderTest extends TestCase {

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Mock common WordPress functions.
		Functions\when( 'sanitize_key' )->returnArg();
		Functions\when( 'absint' )->alias( function( $value ) {
			return abs( (int) $value );
		});
		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'esc_attr__' )->returnArg();
		Functions\when( '__' )->returnArg();
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'add_query_arg' )->alias( function( $params, $url = '' ) {
			return $url . '?' . http_build_query( $params );
		});
		Functions\when( 'home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'get_option' )->justReturn( 10 );
		Functions\when( 'apd_get_option' )->justReturn( 'grid' );

		// Reset superglobals.
		$_GET = [];
		$_COOKIE = [];
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		$_GET = [];
		$_COOKIE = [];
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test constructor creates instance.
	 */
	public function test_constructor(): void {
		$loader = new TemplateLoader();
		$this->assertInstanceOf( TemplateLoader::class, $loader );
	}

	/**
	 * Test init registers hooks.
	 */
	public function test_init_registers_hooks(): void {
		$loader = new TemplateLoader();

		Functions\expect( 'add_filter' )
			->once()
			->with( 'template_include', [ $loader, 'template_include' ], 10 );

		Functions\expect( 'add_filter' )
			->once()
			->with( 'body_class', [ $loader, 'body_class' ], 10 );

		Functions\expect( 'add_action' )
			->once()
			->with( 'wp_head', [ $loader, 'track_listing_view' ] );

		$loader->init();

		// Verify the function was called (Mockery expectation assertions).
		$this->assertTrue( true );
	}

	/**
	 * Test get_current_view returns grid by default.
	 */
	public function test_get_current_view_returns_grid_by_default(): void {
		$loader = new TemplateLoader();

		$this->assertSame( 'grid', $loader->get_current_view() );
	}

	/**
	 * Test get_current_view returns view from GET parameter.
	 */
	public function test_get_current_view_from_get_parameter(): void {
		$loader = new TemplateLoader();

		$_GET['apd_view'] = 'list';

		$this->assertSame( 'list', $loader->get_current_view() );
	}

	/**
	 * Test get_current_view validates view parameter.
	 */
	public function test_get_current_view_validates_parameter(): void {
		$loader = new TemplateLoader();

		$_GET['apd_view'] = 'invalid';

		$this->assertSame( 'grid', $loader->get_current_view() );
	}

	/**
	 * Test get_current_view falls back to cookie.
	 */
	public function test_get_current_view_from_cookie(): void {
		$loader = new TemplateLoader();

		$_COOKIE['apd_view'] = 'list';

		$this->assertSame( 'list', $loader->get_current_view() );
	}

	/**
	 * Test GET parameter takes precedence over cookie.
	 */
	public function test_get_current_view_get_takes_precedence(): void {
		$loader = new TemplateLoader();

		$_GET['apd_view'] = 'grid';
		$_COOKIE['apd_view'] = 'list';

		$this->assertSame( 'grid', $loader->get_current_view() );
	}

	/**
	 * Test get_grid_columns returns 3 by default.
	 */
	public function test_get_grid_columns_returns_three_by_default(): void {
		$loader = new TemplateLoader();

		Functions\when( 'apd_get_option' )->justReturn( 3 );

		$this->assertSame( 3, $loader->get_grid_columns() );
	}

	/**
	 * Test get_grid_columns from GET parameter.
	 */
	public function test_get_grid_columns_from_get_parameter(): void {
		$loader = new TemplateLoader();

		$_GET['apd_columns'] = '4';

		$this->assertSame( 4, $loader->get_grid_columns() );
	}

	/**
	 * Test get_grid_columns validates 2 columns.
	 */
	public function test_get_grid_columns_accepts_two(): void {
		$loader = new TemplateLoader();

		$_GET['apd_columns'] = '2';

		$this->assertSame( 2, $loader->get_grid_columns() );
	}

	/**
	 * Test get_grid_columns validates parameter - rejects invalid.
	 */
	public function test_get_grid_columns_rejects_invalid(): void {
		$loader = new TemplateLoader();

		$_GET['apd_columns'] = '5'; // Invalid.

		Functions\when( 'apd_get_option' )->justReturn( 3 );

		$this->assertSame( 3, $loader->get_grid_columns() );
	}

	/**
	 * Test get_grid_columns rejects 1 column.
	 */
	public function test_get_grid_columns_rejects_one(): void {
		$loader = new TemplateLoader();

		$_GET['apd_columns'] = '1'; // Invalid.

		Functions\when( 'apd_get_option' )->justReturn( 3 );

		$this->assertSame( 3, $loader->get_grid_columns() );
	}

	/**
	 * Test get_view_url adds view parameter.
	 */
	public function test_get_view_url_adds_view_parameter(): void {
		$loader = new TemplateLoader();

		Functions\when( 'is_post_type_archive' )->justReturn( true );
		Functions\when( 'get_post_type_archive_link' )->justReturn( 'https://example.com/listings/' );

		$url = $loader->get_view_url( 'list' );

		$this->assertStringContainsString( 'apd_view=list', $url );
	}

	/**
	 * Test get_view_url preserves existing parameters.
	 */
	public function test_get_view_url_preserves_parameters(): void {
		$loader = new TemplateLoader();

		$_GET = [ 'apd_category' => '5' ];

		Functions\when( 'is_post_type_archive' )->justReturn( true );
		Functions\when( 'get_post_type_archive_link' )->justReturn( 'https://example.com/listings/' );

		$url = $loader->get_view_url( 'grid' );

		$this->assertStringContainsString( 'apd_category=5', $url );
		$this->assertStringContainsString( 'apd_view=grid', $url );
	}

	/**
	 * Test get_view_url handles taxonomy pages.
	 */
	public function test_get_view_url_handles_taxonomy(): void {
		$loader = new TemplateLoader();

		Functions\when( 'is_post_type_archive' )->justReturn( false );
		Functions\when( 'is_tax' )->justReturn( true );

		$term = new \stdClass();
		$term->taxonomy = 'apd_category';
		$term->term_id = 5;
		Functions\when( 'get_queried_object' )->justReturn( $term );
		// Return a valid URL string (not WP_Error).
		Functions\when( 'get_term_link' )->justReturn( 'https://example.com/category/business/' );

		$url = $loader->get_view_url( 'list' );

		$this->assertStringContainsString( 'apd_view=list', $url );
	}

	/**
	 * Test body_class adds classes for listing archive.
	 */
	public function test_body_class_adds_archive_classes(): void {
		$loader = new TemplateLoader();

		Functions\when( 'is_post_type_archive' )->alias( function( $type ) {
			return $type === 'apd_listing';
		});
		Functions\when( 'is_tax' )->justReturn( false );
		Functions\when( 'is_singular' )->justReturn( false );

		$classes = $loader->body_class( [ 'existing-class' ] );

		$this->assertContains( 'existing-class', $classes );
		$this->assertContains( 'apd-archive', $classes );
		$this->assertContains( 'apd-archive-listing', $classes );
		$this->assertContains( 'apd-view-grid', $classes );
	}

	/**
	 * Test body_class adds list view class when in list mode.
	 */
	public function test_body_class_adds_list_view_class(): void {
		$loader = new TemplateLoader();

		$_GET['apd_view'] = 'list';

		Functions\when( 'is_post_type_archive' )->alias( function( $type ) {
			return $type === 'apd_listing';
		});
		Functions\when( 'is_tax' )->justReturn( false );
		Functions\when( 'is_singular' )->justReturn( false );

		$classes = $loader->body_class( [] );

		$this->assertContains( 'apd-view-list', $classes );
	}

	/**
	 * Test body_class adds classes for category archive.
	 */
	public function test_body_class_adds_category_classes(): void {
		$loader = new TemplateLoader();

		Functions\when( 'is_post_type_archive' )->justReturn( false );
		Functions\when( 'is_tax' )->alias( function( $tax ) {
			return $tax === 'apd_category';
		});
		Functions\when( 'is_singular' )->justReturn( false );

		$classes = $loader->body_class( [] );

		$this->assertContains( 'apd-archive', $classes );
		$this->assertContains( 'apd-archive-category', $classes );
	}

	/**
	 * Test body_class adds classes for tag archive.
	 */
	public function test_body_class_adds_tag_classes(): void {
		$loader = new TemplateLoader();

		Functions\when( 'is_post_type_archive' )->justReturn( false );
		Functions\when( 'is_tax' )->alias( function( $tax ) {
			return $tax === 'apd_tag';
		});
		Functions\when( 'is_singular' )->justReturn( false );

		$classes = $loader->body_class( [] );

		$this->assertContains( 'apd-archive', $classes );
		$this->assertContains( 'apd-archive-tag', $classes );
	}

	/**
	 * Test body_class adds classes for single listing.
	 */
	public function test_body_class_adds_single_classes(): void {
		$loader = new TemplateLoader();

		Functions\when( 'is_post_type_archive' )->justReturn( false );
		Functions\when( 'is_tax' )->justReturn( false );
		Functions\when( 'is_singular' )->alias( function( $type ) {
			return $type === 'apd_listing';
		});

		$classes = $loader->body_class( [] );

		$this->assertContains( 'apd-single', $classes );
		$this->assertContains( 'apd-single-listing', $classes );
	}

	/**
	 * Test body_class does not add classes for non-listing pages.
	 */
	public function test_body_class_skips_non_listing_pages(): void {
		$loader = new TemplateLoader();

		Functions\when( 'is_post_type_archive' )->justReturn( false );
		Functions\when( 'is_tax' )->justReturn( false );
		Functions\when( 'is_singular' )->justReturn( false );

		$classes = $loader->body_class( [ 'some-class' ] );

		$this->assertSame( [ 'some-class' ], $classes );
	}

	/**
	 * Test render_view_switcher returns HTML.
	 */
	public function test_render_view_switcher_returns_html(): void {
		$loader = new TemplateLoader();

		Functions\when( 'is_post_type_archive' )->justReturn( true );
		Functions\when( 'get_post_type_archive_link' )->justReturn( 'https://example.com/listings/' );

		$html = $loader->render_view_switcher();

		$this->assertStringContainsString( 'apd-view-switcher', $html );
		$this->assertStringContainsString( 'apd-view-switcher__btn--grid', $html );
		$this->assertStringContainsString( 'apd-view-switcher__btn--list', $html );
	}

	/**
	 * Test render_view_switcher marks current view as active.
	 */
	public function test_render_view_switcher_marks_current_active(): void {
		$loader = new TemplateLoader();

		$_GET['apd_view'] = 'list';

		Functions\when( 'is_post_type_archive' )->justReturn( true );
		Functions\when( 'get_post_type_archive_link' )->justReturn( 'https://example.com/listings/' );

		$html = $loader->render_view_switcher();

		// List button should be active.
		$this->assertStringContainsString( 'apd-view-switcher__btn--list apd-view-switcher__btn--active', $html );
	}

	/**
	 * Test render_results_count with zero results.
	 */
	public function test_render_results_count_zero_results(): void {
		$loader = new TemplateLoader();

		$query              = Mockery::mock( 'WP_Query' );
		$query->found_posts = 0;
		$query->shouldReceive( 'get' )->with( 'paged' )->andReturn( 1 );
		$query->shouldReceive( 'get' )->with( 'posts_per_page' )->andReturn( 10 );

		$html = $loader->render_results_count( $query );

		$this->assertStringContainsString( 'No listings found', $html );
		$this->assertStringContainsString( 'apd-results-count', $html );
		$this->assertStringContainsString( 'aria-live="polite"', $html );
	}

	/**
	 * Test render_results_count with one result.
	 */
	public function test_render_results_count_one_result(): void {
		$loader = new TemplateLoader();

		$query              = Mockery::mock( 'WP_Query' );
		$query->found_posts = 1;
		$query->shouldReceive( 'get' )->with( 'paged' )->andReturn( 1 );
		$query->shouldReceive( 'get' )->with( 'posts_per_page' )->andReturn( 10 );

		$html = $loader->render_results_count( $query );

		$this->assertStringContainsString( 'Showing 1 listing', $html );
	}

	/**
	 * Test render_results_count with multiple results on one page.
	 */
	public function test_render_results_count_all_on_one_page(): void {
		$loader = new TemplateLoader();

		$query              = Mockery::mock( 'WP_Query' );
		$query->found_posts = 5;
		$query->shouldReceive( 'get' )->with( 'paged' )->andReturn( 1 );
		$query->shouldReceive( 'get' )->with( 'posts_per_page' )->andReturn( 10 );

		$html = $loader->render_results_count( $query );

		$this->assertStringContainsString( 'Showing all 5 listings', $html );
	}

	/**
	 * Test render_results_count with pagination.
	 */
	public function test_render_results_count_with_pagination(): void {
		$loader = new TemplateLoader();

		$query              = Mockery::mock( 'WP_Query' );
		$query->found_posts = 25;
		$query->shouldReceive( 'get' )->with( 'paged' )->andReturn( 2 );
		$query->shouldReceive( 'get' )->with( 'posts_per_page' )->andReturn( 10 );

		$html = $loader->render_results_count( $query );

		// Should show 11-20 of 25.
		$this->assertStringContainsString( 'apd-results-count', $html );
	}

	/**
	 * Test render_pagination with no pages.
	 */
	public function test_render_pagination_no_pages(): void {
		$loader = new TemplateLoader();

		$query                = Mockery::mock( 'WP_Query' );
		$query->max_num_pages = 1;

		$html = $loader->render_pagination( $query );

		$this->assertEmpty( $html );
	}

	/**
	 * Test render_pagination with multiple pages.
	 */
	public function test_render_pagination_multiple_pages(): void {
		$loader = new TemplateLoader();

		$query                = Mockery::mock( 'WP_Query' );
		$query->max_num_pages = 5;
		$query->shouldReceive( 'get' )->with( 'paged' )->andReturn( 2 );

		Functions\expect( 'apply_filters' )
			->with( 'apd_pagination_args', Mockery::type( 'array' ), $query )
			->andReturnUsing( function( $hook, $args ) {
				return $args;
			});

		Functions\when( 'paginate_links' )->justReturn( '<span class="page-numbers current">2</span>' );

		$html = $loader->render_pagination( $query );

		$this->assertStringContainsString( 'apd-pagination', $html );
		$this->assertStringContainsString( 'page-numbers', $html );
		$this->assertStringContainsString( 'role="navigation"', $html );
	}

	/**
	 * Test render_pagination returns empty when paginate_links returns nothing.
	 */
	public function test_render_pagination_empty_links(): void {
		$loader = new TemplateLoader();

		$query                = Mockery::mock( 'WP_Query' );
		$query->max_num_pages = 2;
		$query->shouldReceive( 'get' )->with( 'paged' )->andReturn( 1 );

		Functions\expect( 'apply_filters' )
			->andReturnUsing( function( $hook, $args ) {
				return $args;
			});

		Functions\when( 'paginate_links' )->justReturn( '' );

		$html = $loader->render_pagination( $query );

		$this->assertEmpty( $html );
	}

	/**
	 * Test get_archive_title for post type archive.
	 */
	public function test_get_archive_title_post_type_archive(): void {
		$loader = new TemplateLoader();

		Functions\when( 'is_post_type_archive' )->alias( function( $type ) {
			return $type === 'apd_listing';
		});
		Functions\when( 'is_tax' )->justReturn( false );
		Functions\when( 'post_type_archive_title' )->justReturn( 'Listings' );
		Functions\expect( 'apply_filters' )
			->with( 'apd_archive_title', 'Listings' )
			->andReturn( 'Listings' );

		$title = $loader->get_archive_title();

		$this->assertSame( 'Listings', $title );
	}

	/**
	 * Test get_archive_title falls back when empty.
	 */
	public function test_get_archive_title_fallback(): void {
		$loader = new TemplateLoader();

		$post_type = new \stdClass();
		$post_type->labels = new \stdClass();
		$post_type->labels->name = 'Listings';

		Functions\when( 'is_post_type_archive' )->alias( function( $type ) {
			return $type === 'apd_listing';
		});
		Functions\when( 'is_tax' )->justReturn( false );
		Functions\when( 'post_type_archive_title' )->justReturn( '' );
		Functions\when( 'get_post_type_object' )->justReturn( $post_type );
		Functions\expect( 'apply_filters' )
			->with( 'apd_archive_title', 'Listings' )
			->andReturn( 'Listings' );

		$title = $loader->get_archive_title();

		$this->assertSame( 'Listings', $title );
	}

	/**
	 * Test get_archive_title for taxonomy archive.
	 */
	public function test_get_archive_title_taxonomy_archive(): void {
		$loader = new TemplateLoader();

		Functions\when( 'is_post_type_archive' )->justReturn( false );
		Functions\when( 'is_tax' )->alias( function( $tax ) {
			return $tax === 'apd_category';
		});
		Functions\when( 'single_term_title' )->justReturn( 'Business' );
		Functions\expect( 'apply_filters' )
			->with( 'apd_archive_title', 'Business' )
			->andReturn( 'Business' );

		$title = $loader->get_archive_title();

		$this->assertSame( 'Business', $title );
	}

	/**
	 * Test get_archive_title default fallback.
	 */
	public function test_get_archive_title_default_fallback(): void {
		$loader = new TemplateLoader();

		Functions\when( 'is_post_type_archive' )->justReturn( false );
		Functions\when( 'is_tax' )->justReturn( false );
		Functions\expect( 'apply_filters' )
			->with( 'apd_archive_title', 'Listings' )
			->andReturn( 'Listings' );

		$title = $loader->get_archive_title();

		$this->assertSame( 'Listings', $title );
	}

	/**
	 * Test get_archive_description for post type.
	 */
	public function test_get_archive_description_post_type(): void {
		$loader = new TemplateLoader();

		$post_type              = new \stdClass();
		$post_type->description = 'All listings in our directory.';

		Functions\when( 'is_post_type_archive' )->alias( function( $type ) {
			return $type === 'apd_listing';
		});
		Functions\when( 'is_tax' )->justReturn( false );
		Functions\when( 'get_post_type_object' )->justReturn( $post_type );
		Functions\expect( 'apply_filters' )
			->with( 'apd_archive_description', 'All listings in our directory.' )
			->andReturn( 'All listings in our directory.' );

		$description = $loader->get_archive_description();

		$this->assertSame( 'All listings in our directory.', $description );
	}

	/**
	 * Test get_archive_description for taxonomy.
	 */
	public function test_get_archive_description_taxonomy(): void {
		$loader = new TemplateLoader();

		Functions\when( 'is_post_type_archive' )->justReturn( false );
		Functions\when( 'is_tax' )->alias( function( $tax ) {
			return $tax === 'apd_category';
		});
		Functions\when( 'term_description' )->justReturn( 'Business category description.' );
		Functions\expect( 'apply_filters' )
			->with( 'apd_archive_description', 'Business category description.' )
			->andReturn( 'Business category description.' );

		$description = $loader->get_archive_description();

		$this->assertSame( 'Business category description.', $description );
	}

	/**
	 * Test get_archive_description returns empty for non-archive.
	 */
	public function test_get_archive_description_empty(): void {
		$loader = new TemplateLoader();

		Functions\when( 'is_post_type_archive' )->justReturn( false );
		Functions\when( 'is_tax' )->justReturn( false );
		Functions\expect( 'apply_filters' )
			->with( 'apd_archive_description', '' )
			->andReturn( '' );

		$description = $loader->get_archive_description();

		$this->assertSame( '', $description );
	}

	/**
	 * Test track_listing_view does nothing when not on single listing.
	 */
	public function test_track_listing_view_skips_non_single(): void {
		$loader = new TemplateLoader();

		$called = false;
		Functions\when( 'is_singular' )->alias( function( $type ) use ( &$called ) {
			$called = true;
			return false;
		});

		$loader->track_listing_view();

		$this->assertTrue( $called, 'is_singular should be called' );
	}

	/**
	 * Test track_listing_view skips when no listing ID.
	 */
	public function test_track_listing_view_skips_no_listing_id(): void {
		$loader = new TemplateLoader();

		$increment_called = false;

		Functions\when( 'is_singular' )->alias( function( $type ) {
			return $type === 'apd_listing';
		});
		Functions\when( 'get_queried_object_id' )->justReturn( 0 );
		Functions\when( 'apd_increment_listing_views' )->alias( function() use ( &$increment_called ) {
			$increment_called = true;
		});

		$loader->track_listing_view();

		$this->assertFalse( $increment_called, 'apd_increment_listing_views should NOT be called' );
	}

	/**
	 * Test track_listing_view skips admin users by default.
	 */
	public function test_track_listing_view_skips_admin_users(): void {
		$loader = new TemplateLoader();

		$increment_called = false;

		Functions\when( 'is_singular' )->alias( function( $type ) {
			return $type === 'apd_listing';
		});
		Functions\when( 'get_queried_object_id' )->justReturn( 123 );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		});
		Functions\when( 'current_user_can' )->alias( function( $cap ) {
			return $cap === 'manage_options';
		});
		Functions\when( 'apd_increment_listing_views' )->alias( function() use ( &$increment_called ) {
			$increment_called = true;
		});

		$loader->track_listing_view();

		$this->assertFalse( $increment_called, 'apd_increment_listing_views should NOT be called for admins' );
	}

	/**
	 * Test track_listing_view skips bots.
	 */
	public function test_track_listing_view_skips_bots(): void {
		$loader = new TemplateLoader();

		$_SERVER['HTTP_USER_AGENT'] = 'Googlebot/2.1';

		$increment_called = false;

		Functions\when( 'is_singular' )->alias( function( $type ) {
			return $type === 'apd_listing';
		});
		Functions\when( 'get_queried_object_id' )->justReturn( 123 );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		});
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'apd_increment_listing_views' )->alias( function() use ( &$increment_called ) {
			$increment_called = true;
		});

		$loader->track_listing_view();

		$this->assertFalse( $increment_called, 'apd_increment_listing_views should NOT be called for bots' );

		unset( $_SERVER['HTTP_USER_AGENT'] );
	}

	/**
	 * Test track_listing_view increments for normal visitors.
	 */
	public function test_track_listing_view_increments_for_visitors(): void {
		$loader = new TemplateLoader();

		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

		$increment_called_with = null;

		Functions\when( 'is_singular' )->alias( function( $type ) {
			return $type === 'apd_listing';
		});
		Functions\when( 'get_queried_object_id' )->justReturn( 123 );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		});
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'apd_increment_listing_views' )->alias( function( $id ) use ( &$increment_called_with ) {
			$increment_called_with = $id;
		});

		$loader->track_listing_view();

		$this->assertSame( 123, $increment_called_with, 'apd_increment_listing_views should be called with listing ID' );

		unset( $_SERVER['HTTP_USER_AGENT'] );
	}

	/**
	 * Test track_listing_view skips when user agent is empty.
	 */
	public function test_track_listing_view_skips_empty_user_agent(): void {
		$loader = new TemplateLoader();

		// No HTTP_USER_AGENT set.
		unset( $_SERVER['HTTP_USER_AGENT'] );

		$increment_called = false;

		Functions\when( 'is_singular' )->alias( function( $type ) {
			return $type === 'apd_listing';
		});
		Functions\when( 'get_queried_object_id' )->justReturn( 123 );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		});
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'apd_increment_listing_views' )->alias( function() use ( &$increment_called ) {
			$increment_called = true;
		});

		$loader->track_listing_view();

		$this->assertFalse( $increment_called, 'apd_increment_listing_views should NOT be called with empty user agent' );
	}

	/**
	 * Test track_listing_view allows admin views when filter returns false.
	 */
	public function test_track_listing_view_allows_admin_when_filter_false(): void {
		$loader = new TemplateLoader();

		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';

		$increment_called_with = null;

		Functions\when( 'is_singular' )->alias( function( $type ) {
			return $type === 'apd_listing';
		});
		Functions\when( 'get_queried_object_id' )->justReturn( 123 );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			// Return false to allow admin views.
			if ( $hook === 'apd_skip_admin_view_count' ) {
				return false;
			}
			return $value;
		});
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'apd_increment_listing_views' )->alias( function( $id ) use ( &$increment_called_with ) {
			$increment_called_with = $id;
		});

		$loader->track_listing_view();

		$this->assertSame( 123, $increment_called_with, 'apd_increment_listing_views should be called' );

		unset( $_SERVER['HTTP_USER_AGENT'] );
	}
}
