<?php
/**
 * FavoritesPage Unit Tests.
 *
 * @package APD\Tests\Unit\Frontend\Dashboard
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Frontend\Dashboard;

use APD\Frontend\Dashboard\FavoritesPage;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for FavoritesPage.
 */
final class FavoritesPageTest extends UnitTestCase {

	/**
	 * Store original $_GET to restore later.
	 *
	 * @var array
	 */
	private array $original_get = [];

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Store and clear $_GET to prevent test pollution.
		$this->original_get = $_GET;
		$_GET               = [];

		// Mock common WordPress functions.
		Functions\stubs( [
			'get_current_user_id'  => 1,
			'is_user_logged_in'    => true,
			'sanitize_key'         => static fn( $key ) => strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', $key ) ),
			'wp_parse_args'        => static fn( $args, $defaults ) => array_merge( $defaults, $args ),
			'absint'               => static fn( $val ) => abs( (int) $val ),
			'add_query_arg'        => static fn( $args, $url = '' ) => $url . '?' . http_build_query( $args ),
			'get_user_meta'        => static fn( $user_id, $key, $single ) => '',
			'update_user_meta'     => static fn( $user_id, $key, $value ) => true,
			'get_post_type_archive_link' => static fn( $post_type ) => 'https://example.com/listings/',
			'home_url'             => static fn( $path = '' ) => 'https://example.com' . $path,
		] );
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		// Restore original $_GET.
		$_GET = $this->original_get;

		// Reset singleton instance.
		FavoritesPage::reset_instance();

		parent::tearDown();
	}

	/**
	 * Test constructor sets default configuration.
	 */
	public function test_constructor_sets_default_config(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$favorites_page = FavoritesPage::get_instance();

		$this->assertSame( 0, $favorites_page->get_user_id() );
	}

	/**
	 * Test constructor accepts custom configuration.
	 */
	public function test_constructor_accepts_custom_config(): void {
		FavoritesPage::reset_instance();
		$config         = [ 'per_page' => 24 ];
		$favorites_page = FavoritesPage::get_instance( $config );

		$result = $favorites_page->get_config();
		$this->assertSame( 24, $result['per_page'] );
	}

	/**
	 * Test set_user_id and get_user_id.
	 */
	public function test_set_and_get_user_id(): void {
		$favorites_page = FavoritesPage::get_instance();

		$favorites_page->set_user_id( 42 );
		$this->assertSame( 42, $favorites_page->get_user_id() );

		$favorites_page->set_user_id( 0 );
		$this->assertSame( 0, $favorites_page->get_user_id() );
	}

	/**
	 * Test get_current_page returns 1 by default.
	 */
	public function test_get_current_page_returns_1_by_default(): void {
		$favorites_page = FavoritesPage::get_instance();

		$page = $favorites_page->get_current_page();

		$this->assertSame( 1, $page );
	}

	/**
	 * Test get_current_page parses URL parameter.
	 */
	public function test_get_current_page_parses_url_parameter(): void {
		$_GET['fav_page'] = '3';

		$favorites_page = FavoritesPage::get_instance();
		$page           = $favorites_page->get_current_page();

		$this->assertSame( 3, $page );

		unset( $_GET['fav_page'] );
	}

	/**
	 * Test get_current_page enforces minimum of 1.
	 */
	public function test_get_current_page_enforces_minimum(): void {
		$_GET['fav_page'] = '0';

		$favorites_page = FavoritesPage::get_instance();
		$page           = $favorites_page->get_current_page();

		$this->assertSame( 1, $page );

		$_GET['fav_page'] = '-5';
		$page             = $favorites_page->get_current_page();
		// absint(-5) = 5, so max(1, 5) = 5.
		$this->assertSame( 5, $page );

		unset( $_GET['fav_page'] );
	}

	/**
	 * Test get_view_mode returns grid by default.
	 */
	public function test_get_view_mode_returns_grid_by_default(): void {
		$favorites_page = FavoritesPage::get_instance();

		$view_mode = $favorites_page->get_view_mode();

		$this->assertSame( 'grid', $view_mode );
	}

	/**
	 * Test get_view_mode accepts URL parameter.
	 */
	public function test_get_view_mode_accepts_url_parameter(): void {
		$_GET['view'] = 'list';

		$favorites_page = FavoritesPage::get_instance();
		$view_mode      = $favorites_page->get_view_mode();

		$this->assertSame( 'list', $view_mode );

		unset( $_GET['view'] );
	}

	/**
	 * Test get_view_mode validates URL parameter.
	 */
	public function test_get_view_mode_validates_url_parameter(): void {
		$_GET['view'] = 'invalid_view';

		$favorites_page = FavoritesPage::get_instance();
		$view_mode      = $favorites_page->get_view_mode();

		$this->assertSame( 'grid', $view_mode );

		unset( $_GET['view'] );
	}

	/**
	 * Test get_view_mode reads from user meta.
	 */
	public function test_get_view_mode_reads_from_user_meta(): void {
		Functions\when( 'get_user_meta' )->justReturn( 'list' );

		$favorites_page = FavoritesPage::get_instance();
		$favorites_page->set_user_id( 1 );
		$view_mode = $favorites_page->get_view_mode();

		$this->assertSame( 'list', $view_mode );
	}

	/**
	 * Test save_view_mode saves to user meta.
	 */
	public function test_save_view_mode_saves_to_user_meta(): void {
		$favorites_page = FavoritesPage::get_instance();
		$favorites_page->set_user_id( 1 );

		$result = $favorites_page->save_view_mode( 'list' );

		// The function returns true from the stub.
		$this->assertTrue( $result );
	}

	/**
	 * Test save_view_mode rejects invalid view mode.
	 */
	public function test_save_view_mode_rejects_invalid_view_mode(): void {
		$favorites_page = FavoritesPage::get_instance();
		$favorites_page->set_user_id( 1 );

		$result = $favorites_page->save_view_mode( 'invalid' );

		$this->assertFalse( $result );
	}

	/**
	 * Test save_view_mode returns false for no user.
	 */
	public function test_save_view_mode_returns_false_for_no_user(): void {
		$favorites_page = FavoritesPage::get_instance();
		$favorites_page->set_user_id( 0 );

		$result = $favorites_page->save_view_mode( 'list' );

		$this->assertFalse( $result );
	}

	/**
	 * Test get_favorites_count delegates to helper.
	 */
	public function test_get_favorites_count_delegates_to_helper(): void {
		Functions\when( '\apd_get_favorites_count' )->justReturn( 5 );

		$favorites_page = FavoritesPage::get_instance();
		$favorites_page->set_user_id( 1 );

		$count = $favorites_page->get_favorites_count();

		$this->assertSame( 5, $count );
	}

	/**
	 * Test has_favorites returns true when user has favorites.
	 */
	public function test_has_favorites_returns_true_when_has_favorites(): void {
		Functions\when( '\apd_get_favorites_count' )->justReturn( 3 );

		$favorites_page = FavoritesPage::get_instance();
		$favorites_page->set_user_id( 1 );

		$this->assertTrue( $favorites_page->has_favorites() );
	}

	/**
	 * Test has_favorites returns false when user has no favorites.
	 */
	public function test_has_favorites_returns_false_when_no_favorites(): void {
		Functions\when( '\apd_get_favorites_count' )->justReturn( 0 );

		$favorites_page = FavoritesPage::get_instance();
		$favorites_page->set_user_id( 1 );

		$this->assertFalse( $favorites_page->has_favorites() );
	}

	/**
	 * Test get_view_mode_url generates correct URL.
	 */
	public function test_get_view_mode_url_generates_correct_url(): void {
		$favorites_page = FavoritesPage::get_instance();

		$url = $favorites_page->get_view_mode_url( 'list' );

		$this->assertStringContainsString( 'view=list', $url );
		$this->assertStringContainsString( 'fav_page=1', $url );
	}

	/**
	 * Test get_listings_archive_url returns archive URL.
	 */
	public function test_get_listings_archive_url_returns_archive_url(): void {
		Functions\when( 'apply_filters' )->alias( function ( $tag, $value ) {
			return $value;
		} );

		$favorites_page = FavoritesPage::get_instance();

		$url = $favorites_page->get_listings_archive_url();

		$this->assertSame( 'https://example.com/listings/', $url );
	}

	/**
	 * Test get_listings_archive_url falls back to home_url.
	 */
	public function test_get_listings_archive_url_falls_back_to_home_url(): void {
		Functions\when( 'get_post_type_archive_link' )->justReturn( false );
		Functions\when( 'apply_filters' )->alias( function ( $tag, $value ) {
			return $value;
		} );

		$favorites_page = FavoritesPage::get_instance();

		$url = $favorites_page->get_listings_archive_url();

		$this->assertSame( 'https://example.com', $url );
	}

	/**
	 * Test get_grid_config returns expected structure.
	 */
	public function test_get_grid_config_returns_expected_structure(): void {
		$favorites_page = FavoritesPage::get_instance();

		$config = $favorites_page->get_grid_config();

		$this->assertArrayHasKey( 'columns', $config );
		$this->assertArrayHasKey( 'show_favorite', $config );
		$this->assertArrayHasKey( 'show_image', $config );
		$this->assertArrayHasKey( 'show_excerpt', $config );
		$this->assertArrayHasKey( 'show_category', $config );

		$this->assertSame( 4, $config['columns'] );
		$this->assertTrue( $config['show_favorite'] );
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function test_singleton_returns_same_instance(): void {
		$instance1 = FavoritesPage::get_instance();
		$instance2 = FavoritesPage::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test singleton accepts config updates.
	 */
	public function test_singleton_accepts_config_updates(): void {
		$instance = FavoritesPage::get_instance( [ 'per_page' => 50 ] );

		$config = $instance->get_config();
		$this->assertSame( 50, $config['per_page'] );
	}

	/**
	 * Test render returns empty string for no user.
	 */
	public function test_render_returns_empty_for_no_user(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$favorites_page = FavoritesPage::get_instance();
		$favorites_page->set_user_id( 0 );

		$result = $favorites_page->render();

		$this->assertSame( '', $result );
	}

	/**
	 * Test constants are defined.
	 */
	public function test_constants_are_defined(): void {
		$this->assertSame( 12, FavoritesPage::PER_PAGE );
		$this->assertSame( '_apd_favorites_view_mode', FavoritesPage::VIEW_MODE_META_KEY );
	}

	/**
	 * Test get_config returns configuration.
	 */
	public function test_get_config_returns_configuration(): void {
		$favorites_page = FavoritesPage::get_instance();

		$config = $favorites_page->get_config();

		$this->assertIsArray( $config );
		$this->assertArrayHasKey( 'per_page', $config );
		$this->assertArrayHasKey( 'show_view_toggle', $config );
		$this->assertArrayHasKey( 'columns', $config );

		$this->assertSame( 12, $config['per_page'] );
		$this->assertTrue( $config['show_view_toggle'] );
		$this->assertSame( 4, $config['columns'] );
	}

	/**
	 * Test URL parameter takes precedence over user meta.
	 */
	public function test_url_parameter_takes_precedence_over_user_meta(): void {
		Functions\when( 'get_user_meta' )->justReturn( 'list' );
		$_GET['view'] = 'grid';

		$favorites_page = FavoritesPage::get_instance();
		$favorites_page->set_user_id( 1 );
		$view_mode = $favorites_page->get_view_mode();

		$this->assertSame( 'grid', $view_mode );

		unset( $_GET['view'] );
	}
}
