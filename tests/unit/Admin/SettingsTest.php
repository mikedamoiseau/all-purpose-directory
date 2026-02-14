<?php
/**
 * Settings Unit Tests.
 *
 * @package APD\Tests\Unit\Admin
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Admin;

use APD\Admin\Settings;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for Settings.
 */
final class SettingsTest extends UnitTestCase {

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset singleton for clean tests.
		Settings::reset_instance();

		// Define APD constants if not defined.
		if ( ! defined( 'APD_PLUGIN_URL' ) ) {
			define( 'APD_PLUGIN_URL', 'https://example.com/wp-content/plugins/all-purpose-directory/' );
		}
		if ( ! defined( 'APD_VERSION' ) ) {
			define( 'APD_VERSION', '1.0.0' );
		}

		// Common mock setup.
		Functions\stubs( [
			'is_admin'         => true,
			'current_user_can' => true,
			'apply_filters'    => function( $hook, $value ) {
				return $value;
			},
			'get_option'       => function( $option, $default = false ) {
				if ( $option === Settings::OPTION_NAME ) {
					return [];
				}
				return $default;
			},
			'update_option'    => true,
			'admin_url'        => function( $path = '' ) {
				return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
			},
			'add_query_arg'    => function( $args, $url = '' ) {
				if ( is_array( $args ) ) {
					return $url . '?' . http_build_query( $args );
				}
				// Single key-value pair.
				return $url . ( strpos( $url, '?' ) !== false ? '&' : '?' ) . $args . '=' . $url;
			},
			'sanitize_key'     => function( $key ) {
				return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
			},
			'sanitize_text_field' => function( $str ) {
				return trim( strip_tags( $str ) );
			},
			'sanitize_email'   => function( $email ) {
				return filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : '';
			},
			'wp_strip_all_tags' => function( $str ) {
				return strip_tags( $str );
			},
			'absint'           => function( $val ) {
				return abs( (int) $val );
			},
			'esc_html'         => function( $text ) {
				return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
			},
			'esc_html__'       => function( $text, $domain = 'default' ) {
				return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
			},
			'esc_attr'         => function( $text ) {
				return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
			},
			'esc_attr_e'       => function( $text, $domain = 'default' ) {
				echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
			},
			'esc_url'          => function( $url ) {
				return filter_var( $url, FILTER_SANITIZE_URL );
			},
			'__'               => function( $text, $domain = 'default' ) {
				return $text;
			},
			'_e'               => function( $text, $domain = 'default' ) {
				echo $text;
			},
		] );

		$this->settings = Settings::get_instance();
		// Manually register tabs since they're now deferred to init hook.
		$this->settings->register_tabs();
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		Settings::reset_instance();
		parent::tearDown();
	}

	/**
	 * Test singleton pattern returns same instance.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = Settings::get_instance();
		$instance2 = Settings::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test reset_instance clears the singleton.
	 */
	public function test_reset_instance_clears_singleton(): void {
		$instance1 = Settings::get_instance();
		Settings::reset_instance();
		$instance2 = Settings::get_instance();

		$this->assertNotSame( $instance1, $instance2 );
	}

	/**
	 * Test constants are defined.
	 */
	public function test_constants_are_defined(): void {
		$this->assertSame( 'apd-settings', Settings::PAGE_SLUG );
		$this->assertSame( 'apd_settings', Settings::OPTION_GROUP );
		$this->assertSame( 'apd_options', Settings::OPTION_NAME );
		$this->assertSame( 'apd_settings_save', Settings::NONCE_ACTION );
		$this->assertSame( 'apd_settings_nonce', Settings::NONCE_NAME );
		$this->assertSame( 'edit.php?post_type=apd_listing', Settings::PARENT_MENU );
		$this->assertSame( 'manage_options', Settings::CAPABILITY );
	}

	/**
	 * Test get_tabs returns all registered tabs.
	 */
	public function test_get_tabs_returns_all_tabs(): void {
		$tabs = $this->settings->get_tabs();

		$this->assertIsArray( $tabs );
		$this->assertArrayHasKey( 'general', $tabs );
		$this->assertArrayHasKey( 'listings', $tabs );
		$this->assertArrayHasKey( 'submission', $tabs );
		$this->assertArrayHasKey( 'display', $tabs );
		$this->assertArrayHasKey( 'email', $tabs );
		$this->assertArrayHasKey( 'advanced', $tabs );
	}

	/**
	 * Test tab structure is correct.
	 */
	public function test_tab_structure_is_correct(): void {
		$tabs = $this->settings->get_tabs();

		foreach ( $tabs as $tab_id => $tab ) {
			$this->assertArrayHasKey( 'label', $tab, "Tab '$tab_id' should have 'label' key" );
			$this->assertArrayHasKey( 'callback', $tab, "Tab '$tab_id' should have 'callback' key" );
			$this->assertIsString( $tab['label'], "Tab '$tab_id' label should be a string" );
			$this->assertTrue( is_callable( $tab['callback'] ), "Tab '$tab_id' callback should be callable" );
		}
	}

	/**
	 * Test has_tab returns true for existing tab.
	 */
	public function test_has_tab_returns_true_for_existing_tab(): void {
		$this->assertTrue( $this->settings->has_tab( 'general' ) );
		$this->assertTrue( $this->settings->has_tab( 'listings' ) );
		$this->assertTrue( $this->settings->has_tab( 'advanced' ) );
	}

	/**
	 * Test has_tab returns false for non-existing tab.
	 */
	public function test_has_tab_returns_false_for_non_existing_tab(): void {
		$this->assertFalse( $this->settings->has_tab( 'nonexistent' ) );
		$this->assertFalse( $this->settings->has_tab( '' ) );
		$this->assertFalse( $this->settings->has_tab( 'General' ) ); // Case-sensitive.
	}

	/**
	 * Test get_current_tab returns default when no GET param.
	 */
	public function test_get_current_tab_returns_default_when_no_get_param(): void {
		$_GET = [];
		$this->assertSame( 'general', $this->settings->get_current_tab() );
	}

	/**
	 * Test get_current_tab returns valid tab from GET param.
	 */
	public function test_get_current_tab_returns_valid_tab_from_get(): void {
		$_GET['tab'] = 'listings';
		$this->assertSame( 'listings', $this->settings->get_current_tab() );

		$_GET['tab'] = 'email';
		$this->assertSame( 'email', $this->settings->get_current_tab() );
	}

	/**
	 * Test get_current_tab returns default for invalid tab.
	 */
	public function test_get_current_tab_returns_default_for_invalid_tab(): void {
		$_GET['tab'] = 'invalid';
		$this->assertSame( 'general', $this->settings->get_current_tab() );

		$_GET['tab'] = '<script>alert(1)</script>';
		$this->assertSame( 'general', $this->settings->get_current_tab() );
	}

	/**
	 * Test get_defaults returns all default settings.
	 */
	public function test_get_defaults_returns_all_defaults(): void {
		$defaults = $this->settings->get_defaults();

		$this->assertIsArray( $defaults );

		// General defaults.
		$this->assertSame( '$', $defaults['currency_symbol'] );
		$this->assertSame( 'before', $defaults['currency_position'] );
		$this->assertSame( 'default', $defaults['date_format'] );
		$this->assertSame( 'km', $defaults['distance_unit'] );

		// Listings defaults.
		$this->assertSame( 12, $defaults['listings_per_page'] );
		$this->assertSame( 'pending', $defaults['default_status'] );
		$this->assertSame( 0, $defaults['expiration_days'] );
		$this->assertTrue( $defaults['enable_reviews'] );
		$this->assertTrue( $defaults['enable_favorites'] );
		$this->assertTrue( $defaults['enable_contact_form'] );

		// Submission defaults.
		$this->assertSame( 'logged_in', $defaults['who_can_submit'] );
		$this->assertFalse( $defaults['guest_submission'] );

		// Display defaults.
		$this->assertSame( 'grid', $defaults['default_view'] );
		$this->assertSame( 3, $defaults['grid_columns'] );

		// Email defaults.
		$this->assertSame( '', $defaults['from_name'] );
		$this->assertTrue( $defaults['notify_submission'] );

		// Advanced defaults.
		$this->assertFalse( $defaults['delete_data'] );
		$this->assertFalse( $defaults['debug_mode'] );
	}

	/**
	 * Test get returns default when option not set.
	 */
	public function test_get_returns_default_when_option_not_set(): void {
		$this->assertSame( '$', $this->settings->get( 'currency_symbol' ) );
		$this->assertSame( 12, $this->settings->get( 'listings_per_page' ) );
	}

	/**
	 * Test get returns custom default when provided.
	 */
	public function test_get_returns_custom_default_when_provided(): void {
		$this->assertSame( 'custom', $this->settings->get( 'nonexistent_key', 'custom' ) );
	}

	/**
	 * Test get returns stored value when set.
	 */
	public function test_get_returns_stored_value_when_set(): void {
		Functions\when( 'get_option' )->justReturn( [
			'currency_symbol'   => '€',
			'listings_per_page' => 24,
		] );

		Settings::reset_instance();
		$settings = Settings::get_instance();

		$this->assertSame( '€', $settings->get( 'currency_symbol' ) );
		$this->assertSame( 24, $settings->get( 'listings_per_page' ) );
	}

	/**
	 * Test get_all merges defaults with stored options.
	 */
	public function test_get_all_merges_defaults_with_stored_options(): void {
		Functions\when( 'get_option' )->justReturn( [
			'currency_symbol' => '€',
		] );

		Settings::reset_instance();
		$settings = Settings::get_instance();
		$all      = $settings->get_all();

		// Stored value should override default.
		$this->assertSame( '€', $all['currency_symbol'] );

		// Non-stored values should use defaults.
		$this->assertSame( 12, $all['listings_per_page'] );
		$this->assertSame( 'km', $all['distance_unit'] );
	}

	/**
	 * Test sanitize_settings sanitizes currency_symbol.
	 */
	public function test_sanitize_settings_sanitizes_currency_symbol(): void {
		$input     = [ 'currency_symbol' => '  $  ' ];
		$sanitized = $this->settings->sanitize_settings( $input );

		$this->assertSame( '$', $sanitized['currency_symbol'] );
	}

	/**
	 * Test sanitize_settings validates currency_position.
	 */
	public function test_sanitize_settings_validates_currency_position(): void {
		$input     = [ 'currency_position' => 'before' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'before', $sanitized['currency_position'] );

		$input     = [ 'currency_position' => 'after' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'after', $sanitized['currency_position'] );

		$input     = [ 'currency_position' => 'invalid' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'before', $sanitized['currency_position'] );
	}

	/**
	 * Test sanitize_settings validates distance_unit.
	 */
	public function test_sanitize_settings_validates_distance_unit(): void {
		$input     = [ 'distance_unit' => 'km' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'km', $sanitized['distance_unit'] );

		$input     = [ 'distance_unit' => 'miles' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'miles', $sanitized['distance_unit'] );

		$input     = [ 'distance_unit' => 'invalid' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'km', $sanitized['distance_unit'] );
	}

	/**
	 * Test sanitize_settings clamps listings_per_page.
	 */
	public function test_sanitize_settings_clamps_listings_per_page(): void {
		$input     = [ 'listings_per_page' => 50 ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 50, $sanitized['listings_per_page'] );

		$input     = [ 'listings_per_page' => 0 ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 1, $sanitized['listings_per_page'] );

		$input     = [ 'listings_per_page' => 200 ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 100, $sanitized['listings_per_page'] );
	}

	/**
	 * Test sanitize_settings validates default_status.
	 */
	public function test_sanitize_settings_validates_default_status(): void {
		$input     = [ 'default_status' => 'publish' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'publish', $sanitized['default_status'] );

		$input     = [ 'default_status' => 'invalid' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'pending', $sanitized['default_status'] );
	}

	/**
	 * Test sanitize_settings handles boolean fields.
	 */
	public function test_sanitize_settings_handles_boolean_fields(): void {
		// When saving the listings tab with enable_reviews checked.
		$input     = [ '_active_tab' => 'listings', 'enable_reviews' => '1' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertTrue( $sanitized['enable_reviews'] );

		// When saving the listings tab with enable_reviews unchecked (absent).
		$input     = [ '_active_tab' => 'listings' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertFalse( $sanitized['enable_reviews'] );

		// When saving a different tab, checkbox preserves existing value (false when no existing).
		$input     = [ '_active_tab' => 'general' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertFalse( $sanitized['enable_reviews'] );
	}

	/**
	 * Test sanitize_settings clamps grid_columns.
	 */
	public function test_sanitize_settings_clamps_grid_columns(): void {
		$input     = [ 'grid_columns' => 3 ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 3, $sanitized['grid_columns'] );

		$input     = [ 'grid_columns' => 1 ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 2, $sanitized['grid_columns'] );

		$input     = [ 'grid_columns' => 10 ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 4, $sanitized['grid_columns'] );
	}

	/**
	 * Test sanitize_settings validates email fields.
	 */
	public function test_sanitize_settings_validates_email_fields(): void {
		$input     = [ 'from_email' => 'test@example.com' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'test@example.com', $sanitized['from_email'] );

		$input     = [ 'from_email' => 'invalid-email' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( '', $sanitized['from_email'] );
	}

	/**
	 * Test sanitize_settings strips tags from custom_css.
	 */
	public function test_sanitize_settings_strips_tags_from_custom_css(): void {
		$input     = [ 'custom_css' => '.test { color: red; }<script>alert(1)</script>' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( '.test { color: red; }alert(1)', $sanitized['custom_css'] );
	}

	/**
	 * Test get_settings_url returns base URL without tab.
	 */
	public function test_get_settings_url_returns_base_url_without_tab(): void {
		$url = $this->settings->get_settings_url();

		$this->assertStringContainsString( 'edit.php?post_type=apd_listing', $url );
		$this->assertStringContainsString( 'page=apd-settings', $url );
	}

	/**
	 * Test get_settings_url appends tab when valid.
	 */
	public function test_get_settings_url_appends_tab_when_valid(): void {
		// Mock add_query_arg for this test.
		Functions\when( 'add_query_arg' )->alias( function( $args, $url = '' ) {
			if ( is_string( $args ) ) {
				return $url . '&' . $args . '=' . $url;
			}
			return $url . '?' . http_build_query( $args );
		} );

		$url = $this->settings->get_settings_url( 'email' );

		// Just verify it's called with the tab.
		$this->assertIsString( $url );
	}

	/**
	 * Test init registers hooks when in admin context.
	 */
	public function test_init_registers_hooks_in_admin_context(): void {
		$hooks_registered = [];

		Functions\when( 'add_action' )->alias( function( $hook, $callback ) use ( &$hooks_registered ) {
			$hooks_registered[] = $hook;
		} );

		Settings::reset_instance();
		$settings = Settings::get_instance();
		$settings->init();

		$this->assertContains( 'admin_menu', $hooks_registered );
		$this->assertContains( 'admin_init', $hooks_registered );
		$this->assertContains( 'admin_enqueue_scripts', $hooks_registered );
	}

	/**
	 * Test init does nothing when not in admin context.
	 */
	public function test_init_does_nothing_when_not_in_admin(): void {
		Functions\when( 'is_admin' )->justReturn( false );

		Functions\expect( 'add_action' )
			->with( 'admin_menu', Mockery::any() )
			->never();

		Settings::reset_instance();
		$settings = Settings::get_instance();
		$settings->init();
	}

	/**
	 * Test enqueue_assets only loads on settings page.
	 */
	public function test_enqueue_assets_only_loads_on_settings_page(): void {
		Functions\expect( 'wp_enqueue_style' )->never();
		Functions\expect( 'wp_enqueue_script' )->never();

		$this->settings->enqueue_assets( 'edit.php' );
	}

	/**
	 * Test enqueue_assets loads on correct page.
	 */
	public function test_enqueue_assets_loads_on_settings_page(): void {
		Functions\expect( 'wp_enqueue_style' )
			->with( 'apd-admin-settings', Mockery::any(), [], APD_VERSION )
			->once();

		Functions\expect( 'wp_enqueue_script' )
			->with( 'apd-admin-settings', Mockery::any(), [ 'jquery' ], APD_VERSION, true )
			->once();

		Functions\expect( 'wp_enqueue_style' )
			->with( 'wp-color-picker' )
			->once();

		Functions\expect( 'wp_enqueue_script' )
			->with( 'wp-color-picker' )
			->once();

		$this->settings->enqueue_assets( 'apd_listing_page_apd-settings' );
	}

	/**
	 * Test render_text_field outputs correct HTML.
	 */
	public function test_render_text_field_outputs_html(): void {
		ob_start();
		$this->settings->render_text_field( [
			'field'       => 'currency_symbol',
			'description' => 'Test description',
		] );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<input type="text"', $output );
		$this->assertStringContainsString( 'name="apd_options[currency_symbol]"', $output );
		$this->assertStringContainsString( 'id="currency_symbol"', $output );
		$this->assertStringContainsString( 'Test description', $output );
	}

	/**
	 * Test render_text_field with custom class.
	 */
	public function test_render_text_field_with_custom_class(): void {
		ob_start();
		$this->settings->render_text_field( [
			'field' => 'currency_symbol',
			'class' => 'small-text',
		] );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'class="small-text"', $output );
	}

	/**
	 * Test render_number_field outputs correct HTML.
	 */
	public function test_render_number_field_outputs_html(): void {
		ob_start();
		$this->settings->render_number_field( [
			'field'       => 'listings_per_page',
			'min'         => 1,
			'max'         => 100,
			'description' => 'Number of items',
		] );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<input type="number"', $output );
		$this->assertStringContainsString( 'name="apd_options[listings_per_page]"', $output );
		$this->assertStringContainsString( 'min="1"', $output );
		$this->assertStringContainsString( 'max="100"', $output );
		$this->assertStringContainsString( 'Number of items', $output );
	}

	/**
	 * Test render_email_field outputs correct HTML.
	 */
	public function test_render_email_field_outputs_html(): void {
		ob_start();
		$this->settings->render_email_field( [
			'field'       => 'admin_email',
			'description' => 'Admin email address',
		] );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<input type="email"', $output );
		$this->assertStringContainsString( 'name="apd_options[admin_email]"', $output );
		$this->assertStringContainsString( 'Admin email address', $output );
	}

	/**
	 * Test render_textarea_field outputs correct HTML.
	 */
	public function test_render_textarea_field_outputs_html(): void {
		Functions\stubs( [
			'esc_textarea' => function( $text ) {
				return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
			},
		] );

		ob_start();
		$this->settings->render_textarea_field( [
			'field'       => 'custom_css',
			'rows'        => 10,
			'description' => 'Custom styles',
		] );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<textarea', $output );
		$this->assertStringContainsString( 'name="apd_options[custom_css]"', $output );
		$this->assertStringContainsString( 'rows="10"', $output );
		$this->assertStringContainsString( 'Custom styles', $output );
	}

	/**
	 * Test render_select_field outputs correct HTML.
	 */
	public function test_render_select_field_outputs_html(): void {
		Functions\stubs( [
			'selected' => function( $selected, $current, $echo = true ) {
				$result = $selected == $current ? ' selected="selected"' : '';
				if ( $echo ) {
					echo $result;
				}
				return $result;
			},
		] );

		ob_start();
		$this->settings->render_select_field( [
			'field'       => 'default_view',
			'options'     => [
				'grid' => 'Grid View',
				'list' => 'List View',
			],
			'description' => 'Choose view',
		] );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<select', $output );
		$this->assertStringContainsString( 'name="apd_options[default_view]"', $output );
		$this->assertStringContainsString( '<option value="grid"', $output );
		$this->assertStringContainsString( '<option value="list"', $output );
		$this->assertStringContainsString( 'Choose view', $output );
	}

	/**
	 * Test render_checkbox_field outputs correct HTML.
	 */
	public function test_render_checkbox_field_outputs_html(): void {
		Functions\stubs( [
			'checked' => function( $checked, $current, $echo = true ) {
				$result = $checked == $current ? ' checked="checked"' : '';
				if ( $echo ) {
					echo $result;
				}
				return $result;
			},
		] );

		ob_start();
		$this->settings->render_checkbox_field( [
			'field'       => 'enable_reviews',
			'label'       => 'Enable review system',
			'description' => 'Allow reviews',
		] );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<input type="checkbox"', $output );
		$this->assertStringContainsString( 'name="apd_options[enable_reviews]"', $output );
		$this->assertStringContainsString( 'value="1"', $output );
		$this->assertStringContainsString( 'Enable review system', $output );
		$this->assertStringContainsString( 'Allow reviews', $output );
	}

	/**
	 * Test render_page_select_field calls wp_dropdown_pages.
	 */
	public function test_render_page_select_field_calls_wp_dropdown_pages(): void {
		$called_args = null;

		Functions\when( 'wp_dropdown_pages' )->alias( function( $args ) use ( &$called_args ) {
			$called_args = $args;
			echo '<select name="' . $args['name'] . '"></select>';
		} );

		ob_start();
		$this->settings->render_page_select_field( [
			'field'       => 'terms_page',
			'description' => 'Select terms page',
		] );
		$output = ob_get_clean();

		$this->assertNotNull( $called_args );
		$this->assertSame( 'apd_options[terms_page]', $called_args['name'] );
		$this->assertSame( 'terms_page', $called_args['id'] );
		$this->assertStringContainsString( 'Select terms page', $output );
	}

	/**
	 * Test sanitize_settings validates who_can_submit.
	 */
	public function test_sanitize_settings_validates_who_can_submit(): void {
		$input     = [ 'who_can_submit' => 'anyone' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'anyone', $sanitized['who_can_submit'] );

		$input     = [ 'who_can_submit' => 'logged_in' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'logged_in', $sanitized['who_can_submit'] );

		$input     = [ 'who_can_submit' => 'invalid' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'logged_in', $sanitized['who_can_submit'] );
	}

	/**
	 * Test sanitize_settings validates redirect_after.
	 */
	public function test_sanitize_settings_validates_redirect_after(): void {
		$input     = [ 'redirect_after' => 'listing' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'listing', $sanitized['redirect_after'] );

		$input     = [ 'redirect_after' => 'dashboard' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'dashboard', $sanitized['redirect_after'] );

		$input     = [ 'redirect_after' => 'custom' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'custom', $sanitized['redirect_after'] );

		$input     = [ 'redirect_after' => 'invalid' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'listing', $sanitized['redirect_after'] );
	}

	/**
	 * Test sanitize_settings validates single_layout.
	 */
	public function test_sanitize_settings_validates_single_layout(): void {
		$input     = [ 'single_layout' => 'full' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'full', $sanitized['single_layout'] );

		$input     = [ 'single_layout' => 'sidebar' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'sidebar', $sanitized['single_layout'] );

		$input     = [ 'single_layout' => 'invalid' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 'sidebar', $sanitized['single_layout'] );
	}

	/**
	 * Test sanitize_settings handles terms_page.
	 */
	public function test_sanitize_settings_handles_terms_page(): void {
		$input     = [ 'terms_page' => '123' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 123, $sanitized['terms_page'] );

		$input     = [ 'terms_page' => '-5' ];
		$sanitized = $this->settings->sanitize_settings( $input );
		$this->assertSame( 5, $sanitized['terms_page'] ); // absint
	}

	/**
	 * Test set method updates option.
	 */
	public function test_set_updates_option(): void {
		$updated_options = null;

		Functions\when( 'update_option' )->alias( function( $option, $value ) use ( &$updated_options ) {
			$updated_options = $value;
			return true;
		} );

		$result = $this->settings->set( 'currency_symbol', '€' );

		$this->assertTrue( $result );
		$this->assertIsArray( $updated_options );
		$this->assertArrayHasKey( 'currency_symbol', $updated_options );
		$this->assertSame( '€', $updated_options['currency_symbol'] );
	}
}
