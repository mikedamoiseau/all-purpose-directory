<?php
/**
 * RegisterFormShortcode Unit Tests.
 *
 * @package APD\Tests\Unit\Shortcode
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Shortcode;

use APD\Shortcode\RegisterFormShortcode;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Test class for RegisterFormShortcode.
 */
final class RegisterFormShortcodeTest extends UnitTestCase {

	/**
	 * RegisterFormShortcode instance.
	 *
	 * @var RegisterFormShortcode
	 */
	private RegisterFormShortcode $shortcode;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->shortcode = new RegisterFormShortcode();
	}

	/**
	 * Test shortcode tag is correct.
	 */
	public function test_tag_is_apd_register_form(): void {
		$this->assertSame( 'apd_register_form', $this->shortcode->get_tag() );
	}

	/**
	 * Test has correct default attributes.
	 */
	public function test_has_correct_defaults(): void {
		$defaults = $this->shortcode->get_defaults();

		$this->assertSame( '', $defaults['redirect'] );
		$this->assertSame( 'true', $defaults['show_login'] );
		$this->assertSame( '', $defaults['logged_in_message'] );
	}

	/**
	 * Test attribute documentation exists.
	 */
	public function test_attribute_docs_exist(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertArrayHasKey( 'redirect', $docs );
		$this->assertArrayHasKey( 'show_login', $docs );
		$this->assertArrayHasKey( 'logged_in_message', $docs );
		$this->assertArrayHasKey( 'class', $docs );
	}

	/**
	 * Test example is valid.
	 */
	public function test_example_is_valid(): void {
		$example = $this->shortcode->get_example();

		$this->assertStringContainsString( '[apd_register_form', $example );
	}

	/**
	 * Test description is set.
	 */
	public function test_description_is_set(): void {
		$this->assertNotEmpty( $this->shortcode->get_description() );
	}

	/**
	 * Test show_login attribute is boolean type.
	 */
	public function test_show_login_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_login']['type'] );
	}

	/**
	 * Test redirect attribute is string type.
	 */
	public function test_redirect_is_string_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'string', $docs['redirect']['type'] );
	}

	/**
	 * Test render shows disabled message when registration is disabled.
	 */
	public function test_render_shows_disabled_when_registration_off(): void {
		Functions\when( 'get_option' )->alias( function( $option ) {
			if ( $option === 'users_can_register' ) {
				return false;
			}
			return null;
		} );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'shortcode_atts' )->alias( function( $defaults, $atts ) {
			return array_merge( $defaults, is_array( $atts ) ? $atts : [] );
		} );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$result = $this->shortcode->render( [] );

		$this->assertStringContainsString( 'registration is currently disabled', $result );
	}

	/**
	 * Test render shows logged-in message when user is logged in.
	 */
	public function test_render_shows_logged_in_message(): void {
		Functions\when( 'get_option' )->alias( function( $option ) {
			if ( $option === 'users_can_register' ) {
				return true;
			}
			return null;
		} );
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'wp_get_current_user' )->justReturn( (object) [ 'display_name' => 'Test User' ] );
		Functions\when( 'wp_logout_url' )->justReturn( 'https://example.com/logout' );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/page/' );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'shortcode_atts' )->alias( function( $defaults, $atts ) {
			return array_merge( $defaults, is_array( $atts ) ? $atts : [] );
		} );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$result = $this->shortcode->render( [] );

		$this->assertStringContainsString( 'apd-logged-in', $result );
		$this->assertStringContainsString( 'Test User', $result );
	}
}
