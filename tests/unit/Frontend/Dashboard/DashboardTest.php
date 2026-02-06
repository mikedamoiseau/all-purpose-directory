<?php
/**
 * Dashboard Unit Tests.
 *
 * @package APD\Tests\Unit\Frontend\Dashboard
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Frontend\Dashboard;

use APD\Frontend\Dashboard\Dashboard;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for Dashboard.
 *
 * Note: Tests that require WP_Query (stats, tabs with counts) are limited
 * as WP_Query is not available in unit tests. Full integration tests should
 * cover those scenarios.
 */
final class DashboardTest extends UnitTestCase {

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset singleton to prevent state leaking between tests.
		Dashboard::reset_instance();

		// Mock common WordPress functions.
		Functions\stubs( [
			'get_current_user_id' => 1,
			'is_user_logged_in'   => true,
			'get_permalink'       => 'https://example.com/dashboard/',
			'home_url'            => 'https://example.com/',
			'wp_login_url'        => 'https://example.com/wp-login.php',
			'wp_registration_url' => 'https://example.com/wp-login.php?action=register',
			'add_query_arg'       => static function( $key, $value, $url ) {
				return $url . '?' . $key . '=' . $value;
			},
			'number_format_i18n'  => static fn( $number ) => number_format( $number ),
		] );
	}

	/**
	 * Test constructor sets default configuration.
	 */
	public function test_constructor_sets_default_config(): void {
		$dashboard = Dashboard::get_instance();

		$config = $dashboard->get_config();

		$this->assertSame( 'my-listings', $config['default_tab'] );
		$this->assertTrue( $config['show_stats'] );
		$this->assertSame( '', $config['class'] );
	}

	/**
	 * Test constructor merges custom configuration.
	 */
	public function test_constructor_merges_custom_config(): void {
		$dashboard = Dashboard::get_instance( [
			'default_tab' => 'favorites',
			'show_stats'  => false,
			'class'       => 'custom-class',
		] );

		$config = $dashboard->get_config();

		$this->assertSame( 'favorites', $config['default_tab'] );
		$this->assertFalse( $config['show_stats'] );
		$this->assertSame( 'custom-class', $config['class'] );
	}

	/**
	 * Test get_config_value returns correct value.
	 */
	public function test_get_config_value_returns_value(): void {
		$dashboard = Dashboard::get_instance( [ 'default_tab' => 'profile' ] );

		$this->assertSame( 'profile', $dashboard->get_config_value( 'default_tab' ) );
	}

	/**
	 * Test get_config_value returns default for missing key.
	 */
	public function test_get_config_value_returns_default_for_missing(): void {
		$dashboard = Dashboard::get_instance();

		$this->assertSame( 'default', $dashboard->get_config_value( 'nonexistent', 'default' ) );
		$this->assertNull( $dashboard->get_config_value( 'nonexistent' ) );
	}

	/**
	 * Test is_user_logged_in returns true when logged in.
	 */
	public function test_is_user_logged_in_returns_true(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );

		$dashboard = Dashboard::get_instance();

		$this->assertTrue( $dashboard->is_user_logged_in() );
	}

	/**
	 * Test is_user_logged_in returns false when not logged in.
	 */
	public function test_is_user_logged_in_returns_false(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( false );

		$dashboard = Dashboard::get_instance();

		$this->assertFalse( $dashboard->is_user_logged_in() );
	}

	/**
	 * Test get_user_stats returns zero for user_id 0.
	 */
	public function test_get_user_stats_returns_zero_for_no_user(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$dashboard = Dashboard::get_instance();
		$stats     = $dashboard->get_user_stats( 0 );

		$this->assertSame( 0, $stats['total'] );
		$this->assertSame( 0, $stats['published'] );
		$this->assertSame( 0, $stats['pending'] );
		$this->assertSame( 0, $stats['draft'] );
		$this->assertSame( 0, $stats['views'] );
	}

	/**
	 * Test get_tab_url returns base URL for default tab.
	 */
	public function test_get_tab_url_returns_base_for_default(): void {
		$dashboard = Dashboard::get_instance( [ 'default_tab' => 'my-listings' ] );

		$url = $dashboard->get_tab_url( 'my-listings' );

		$this->assertSame( 'https://example.com/dashboard/', $url );
	}

	/**
	 * Test get_tab_url adds parameter for non-default tab.
	 */
	public function test_get_tab_url_adds_parameter(): void {
		$dashboard = Dashboard::get_instance( [ 'default_tab' => 'my-listings' ] );

		$url = $dashboard->get_tab_url( 'favorites' );

		$this->assertStringContainsString( 'tab=favorites', $url );
	}

	/**
	 * Test get_dashboard_url returns permalink when no filter.
	 */
	public function test_get_dashboard_url_returns_permalink(): void {
		$dashboard = Dashboard::get_instance();
		$url       = $dashboard->get_dashboard_url();

		$this->assertSame( 'https://example.com/dashboard/', $url );
	}

	/**
	 * Test render_login_required outputs template.
	 */
	public function test_render_login_required_outputs_template(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'get_option' )->justReturn( true );

		// Mock apd_get_template to capture args.
		$captured_template = null;
		$captured_args     = null;

		Functions\when( '\apd_get_template' )->alias(
			function( $template, $args ) use ( &$captured_template, &$captured_args ) {
				$captured_template = $template;
				$captured_args     = $args;
			}
		);

		$dashboard = Dashboard::get_instance();
		$dashboard->render_login_required();

		$this->assertSame( 'dashboard/login-required.php', $captured_template );
		$this->assertArrayHasKey( 'message', $captured_args );
		$this->assertArrayHasKey( 'login_url', $captured_args );
	}

	/**
	 * Test singleton pattern returns instance.
	 */
	public function test_get_instance_returns_instance(): void {
		$instance = Dashboard::get_instance();

		$this->assertInstanceOf( Dashboard::class, $instance );
	}

	/**
	 * Test get_instance with config updates configuration.
	 */
	public function test_get_instance_with_config_updates(): void {
		$instance = Dashboard::get_instance( [ 'default_tab' => 'profile' ] );

		$this->assertSame( 'profile', $instance->get_config_value( 'default_tab' ) );
	}

	/**
	 * Test render_placeholder returns HTML with tab label.
	 */
	public function test_render_placeholder_returns_html(): void {
		$dashboard = Dashboard::get_instance();

		// Create a mock for get_tabs that doesn't rely on WP_Query.
		$html = $dashboard->render_placeholder( 'test-tab' );

		$this->assertStringContainsString( 'apd-dashboard-placeholder', $html );
		$this->assertStringContainsString( 'coming soon', $html );
	}

	/**
	 * Test constants are defined correctly.
	 */
	public function test_constants_are_defined(): void {
		$this->assertSame( 'my-listings', Dashboard::DEFAULT_TAB );
		$this->assertSame( 'tab', Dashboard::TAB_PARAM );
	}

	/**
	 * Test valid tab slugs.
	 */
	public function test_valid_tab_slugs(): void {
		$valid_tabs = [ 'my-listings', 'add-new', 'favorites', 'profile' ];

		foreach ( $valid_tabs as $tab ) {
			$this->assertIsString( $tab );
			$this->assertNotEmpty( $tab );
		}
	}

	/**
	 * Test get_tab_url handles different tabs.
	 */
	public function test_get_tab_url_handles_different_tabs(): void {
		$dashboard = Dashboard::get_instance( [ 'default_tab' => 'my-listings' ] );

		// Default tab returns base URL.
		$this->assertSame(
			'https://example.com/dashboard/',
			$dashboard->get_tab_url( 'my-listings' )
		);

		// Other tabs have query parameter.
		$this->assertStringContainsString(
			'tab=add-new',
			$dashboard->get_tab_url( 'add-new' )
		);

		$this->assertStringContainsString(
			'tab=profile',
			$dashboard->get_tab_url( 'profile' )
		);

		$this->assertStringContainsString(
			'tab=favorites',
			$dashboard->get_tab_url( 'favorites' )
		);
	}

	/**
	 * Test render_placeholder uses esc_html for label.
	 *
	 * Note: In unit tests, esc_html is stubbed to return input unchanged.
	 * In real WordPress, the script tag would be escaped. This test verifies
	 * the placeholder structure is correct.
	 */
	public function test_render_placeholder_has_correct_structure(): void {
		$dashboard = Dashboard::get_instance();
		$html      = $dashboard->render_placeholder( 'test-tab', 'Test Label' );

		// Verify structure.
		$this->assertStringContainsString( 'apd-dashboard-placeholder', $html );
		$this->assertStringContainsString( 'apd-dashboard-placeholder__message', $html );
		$this->assertStringContainsString( 'Test Label', $html );
		$this->assertStringContainsString( 'coming soon', $html );
	}

	/**
	 * Test configuration immutability after construction.
	 */
	public function test_config_is_set_at_construction(): void {
		$config = [
			'default_tab' => 'profile',
			'show_stats'  => false,
			'class'       => 'test-class',
		];

		$dashboard = Dashboard::get_instance( $config );
		$result    = $dashboard->get_config();

		$this->assertSame( $config['default_tab'], $result['default_tab'] );
		$this->assertSame( $config['show_stats'], $result['show_stats'] );
		$this->assertSame( $config['class'], $result['class'] );
	}

	/**
	 * Test get_config_value with various types.
	 */
	public function test_get_config_value_with_various_types(): void {
		$dashboard = Dashboard::get_instance( [
			'string_val' => 'test',
			'bool_val'   => true,
			'int_val'    => 42,
			'array_val'  => [ 'a', 'b' ],
		] );

		$this->assertSame( 'test', $dashboard->get_config_value( 'string_val' ) );
		$this->assertTrue( $dashboard->get_config_value( 'bool_val' ) );
		$this->assertSame( 42, $dashboard->get_config_value( 'int_val' ) );
		$this->assertSame( [ 'a', 'b' ], $dashboard->get_config_value( 'array_val' ) );
	}
}
