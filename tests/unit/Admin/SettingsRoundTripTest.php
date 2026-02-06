<?php
/**
 * Settings round-trip tests.
 *
 * Verifies that settings saved via the Settings class can be read back
 * by helper functions, and that uninstall.php reads the correct option name.
 *
 * @package APD\Tests\Unit\Admin
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Admin;

use APD\Admin\Settings;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Test class for Settings round-trip behavior.
 */
final class SettingsRoundTripTest extends UnitTestCase {

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Simulated options storage.
	 *
	 * @var array<string, mixed>
	 */
	private array $options_store = [];

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		Settings::reset_instance();

		$this->options_store = [];

		// Simulate get_option / update_option with in-memory store.
		Functions\when( 'get_option' )->alias( function ( $option, $default = false ) {
			if ( $option === Settings::OPTION_NAME ) {
				return $this->options_store[ $option ] ?? $default;
			}
			return $default;
		} );

		Functions\when( 'update_option' )->alias( function ( $option, $value ) {
			$this->options_store[ $option ] = $value;
			return true;
		} );

		Functions\stubs( [
			'is_admin'            => true,
			'current_user_can'    => true,
			'admin_url'           => fn( $path = '' ) => 'https://example.com/wp-admin/' . ltrim( $path, '/' ),
			'add_query_arg'       => fn( $args, $url = '' ) => is_array( $args ) ? $url . '?' . http_build_query( $args ) : $url,
			'wp_strip_all_tags'   => fn( $str ) => strip_tags( $str ),
		] );

		$this->settings = Settings::get_instance();
		$this->settings->register_tabs();
	}

	/**
	 * Tear down.
	 */
	protected function tearDown(): void {
		Settings::reset_instance();
		parent::tearDown();
	}

	/**
	 * Test that set() persists a value that get() returns.
	 */
	public function test_set_then_get_returns_same_value(): void {
		$this->settings->set( 'currency_symbol', 'EUR' );

		// Reset instance to force re-reading from the option store.
		Settings::reset_instance();
		$fresh = Settings::get_instance();
		$fresh->register_tabs();

		$this->assertSame( 'EUR', $fresh->get( 'currency_symbol' ) );
	}

	/**
	 * Test that multiple settings can be set independently.
	 */
	public function test_multiple_settings_persist_independently(): void {
		$this->settings->set( 'currency_symbol', 'GBP' );
		$this->settings->set( 'listings_per_page', 50 );

		Settings::reset_instance();
		$fresh = Settings::get_instance();
		$fresh->register_tabs();

		$this->assertSame( 'GBP', $fresh->get( 'currency_symbol' ) );
		$this->assertSame( 50, $fresh->get( 'listings_per_page' ) );
	}

	/**
	 * Test that set() does not overwrite other existing settings.
	 */
	public function test_set_preserves_existing_settings(): void {
		// Store initial settings.
		$this->settings->set( 'currency_symbol', 'JPY' );
		$this->settings->set( 'distance_unit', 'miles' );

		// Update one setting.
		Settings::reset_instance();
		$fresh = Settings::get_instance();
		$fresh->register_tabs();
		$fresh->set( 'currency_symbol', 'CHF' );

		// Verify the other setting is still there.
		Settings::reset_instance();
		$final = Settings::get_instance();
		$final->register_tabs();

		$this->assertSame( 'CHF', $final->get( 'currency_symbol' ) );
		$this->assertSame( 'miles', $final->get( 'distance_unit' ) );
	}

	/**
	 * Test that get_all() merges stored values with defaults.
	 */
	public function test_get_all_round_trip(): void {
		$this->settings->set( 'currency_symbol', 'AUD' );
		$this->settings->set( 'grid_columns', 4 );

		Settings::reset_instance();
		$fresh = Settings::get_instance();
		$fresh->register_tabs();
		$all = $fresh->get_all();

		// Stored values.
		$this->assertSame( 'AUD', $all['currency_symbol'] );
		$this->assertSame( 4, $all['grid_columns'] );

		// Default values for keys not explicitly set.
		$this->assertSame( 12, $all['listings_per_page'] );
		$this->assertSame( 'km', $all['distance_unit'] );
	}

	/**
	 * Test that sanitize_settings round-trips correctly through set/get.
	 *
	 * Simulates what happens when WordPress calls sanitize_callback on save.
	 */
	public function test_sanitized_settings_round_trip(): void {
		$raw_input = [
			'currency_symbol'   => '  EUR  ',
			'listings_per_page' => '24',
			'enable_reviews'    => '1',
			'delete_data'       => '',
			'grid_columns'      => '3',
			'default_view'      => 'list',
		];

		Functions\stubs( [
			'wp_roles' => function () {
				$roles       = new \stdClass();
				$roles->roles = [
					'administrator' => [ 'name' => 'Administrator' ],
					'editor'        => [ 'name' => 'Editor' ],
					'author'        => [ 'name' => 'Author' ],
				];
				return $roles;
			},
		] );

		$sanitized = $this->settings->sanitize_settings( $raw_input );

		// Store the sanitized values.
		$this->options_store[ Settings::OPTION_NAME ] = $sanitized;

		// Read back with a fresh instance.
		Settings::reset_instance();
		$fresh = Settings::get_instance();
		$fresh->register_tabs();

		$this->assertSame( 'EUR', $fresh->get( 'currency_symbol' ) );
		$this->assertSame( 24, $fresh->get( 'listings_per_page' ) );
		$this->assertTrue( $fresh->get( 'enable_reviews' ) );
		$this->assertFalse( $fresh->get( 'delete_data' ) );
		$this->assertSame( 3, $fresh->get( 'grid_columns' ) );
		$this->assertSame( 'list', $fresh->get( 'default_view' ) );
	}

	/**
	 * Test that uninstall.php reads from the same option name as Settings.
	 *
	 * Verifies the option name constant matches what uninstall.php uses.
	 */
	public function test_option_name_matches_uninstall(): void {
		// The Settings class uses this option name.
		$this->assertSame( 'apd_options', Settings::OPTION_NAME );

		// Read uninstall.php to verify it uses the same option name.
		$uninstall_path = dirname( __DIR__, 3 ) . '/uninstall.php';
		$uninstall_code = file_get_contents( $uninstall_path );

		$this->assertStringContainsString(
			"get_option( 'apd_options'",
			$uninstall_code,
			'uninstall.php must read from the same option name as Settings::OPTION_NAME'
		);
	}

	/**
	 * Test that uninstall.php respects the delete_data flag from settings.
	 *
	 * When delete_data is false/empty, uninstall.php should return early.
	 */
	public function test_uninstall_reads_delete_data_flag(): void {
		$uninstall_path = dirname( __DIR__, 3 ) . '/uninstall.php';
		$uninstall_code = file_get_contents( $uninstall_path );

		// Verify uninstall.php checks the delete_data setting.
		$this->assertStringContainsString(
			"settings['delete_data']",
			$uninstall_code,
			'uninstall.php must check the delete_data setting'
		);

		// Verify it returns early if delete_data is empty.
		$this->assertMatchesRegularExpression(
			"/empty\(\s*\\\$settings\['delete_data'\]\s*\)/",
			$uninstall_code,
			'uninstall.php should check if delete_data is empty before proceeding'
		);
	}

	/**
	 * Test that boolean settings round-trip correctly.
	 *
	 * Checkboxes submit '1' when checked and nothing when unchecked.
	 */
	public function test_boolean_settings_round_trip(): void {
		Functions\stubs( [
			'wp_roles' => function () {
				$roles       = new \stdClass();
				$roles->roles = [
					'administrator' => [ 'name' => 'Administrator' ],
				];
				return $roles;
			},
		] );

		// Simulate checked checkboxes.
		$checked = $this->settings->sanitize_settings( [
			'enable_reviews'    => '1',
			'enable_favorites'  => '1',
			'delete_data'       => '1',
			'debug_mode'        => '1',
		] );

		$this->options_store[ Settings::OPTION_NAME ] = $checked;

		Settings::reset_instance();
		$fresh = Settings::get_instance();
		$fresh->register_tabs();

		$this->assertTrue( $fresh->get( 'enable_reviews' ) );
		$this->assertTrue( $fresh->get( 'enable_favorites' ) );
		$this->assertTrue( $fresh->get( 'delete_data' ) );
		$this->assertTrue( $fresh->get( 'debug_mode' ) );

		// Simulate unchecked checkboxes (keys absent from input).
		$unchecked = $this->settings->sanitize_settings( [] );

		$this->options_store[ Settings::OPTION_NAME ] = $unchecked;

		Settings::reset_instance();
		$fresh2 = Settings::get_instance();
		$fresh2->register_tabs();

		$this->assertFalse( $fresh2->get( 'enable_reviews' ) );
		$this->assertFalse( $fresh2->get( 'enable_favorites' ) );
		$this->assertFalse( $fresh2->get( 'delete_data' ) );
		$this->assertFalse( $fresh2->get( 'debug_mode' ) );
	}
}
