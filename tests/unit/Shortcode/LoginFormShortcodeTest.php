<?php
/**
 * LoginFormShortcode Unit Tests.
 *
 * @package APD\Tests\Unit\Shortcode
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Shortcode;

use APD\Shortcode\LoginFormShortcode;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for LoginFormShortcode.
 */
final class LoginFormShortcodeTest extends UnitTestCase {

	/**
	 * LoginFormShortcode instance.
	 *
	 * @var LoginFormShortcode
	 */
	private LoginFormShortcode $shortcode;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->shortcode = new LoginFormShortcode();
	}

	/**
	 * Test shortcode tag is correct.
	 */
	public function test_tag_is_apd_login_form(): void {
		$this->assertSame( 'apd_login_form', $this->shortcode->get_tag() );
	}

	/**
	 * Test has correct default attributes.
	 */
	public function test_has_correct_defaults(): void {
		$defaults = $this->shortcode->get_defaults();

		$this->assertSame( '', $defaults['redirect'] );
		$this->assertSame( 'true', $defaults['show_remember'] );
		$this->assertSame( 'true', $defaults['show_register'] );
		$this->assertSame( 'true', $defaults['show_lost_password'] );
	}

	/**
	 * Test attribute documentation exists.
	 */
	public function test_attribute_docs_exist(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertArrayHasKey( 'redirect', $docs );
		$this->assertArrayHasKey( 'show_remember', $docs );
		$this->assertArrayHasKey( 'show_register', $docs );
		$this->assertArrayHasKey( 'show_lost_password', $docs );
		$this->assertArrayHasKey( 'label_username', $docs );
		$this->assertArrayHasKey( 'label_password', $docs );
	}

	/**
	 * Test example is valid.
	 */
	public function test_example_is_valid(): void {
		$example = $this->shortcode->get_example();

		$this->assertStringContainsString( '[apd_login_form', $example );
	}

	/**
	 * Test description is set.
	 */
	public function test_description_is_set(): void {
		$this->assertNotEmpty( $this->shortcode->get_description() );
	}

	/**
	 * Test show_remember attribute is boolean type.
	 */
	public function test_show_remember_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_remember']['type'] );
	}

	/**
	 * Test show_register attribute is boolean type.
	 */
	public function test_show_register_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_register']['type'] );
	}

	/**
	 * Test redirect attribute is string type.
	 */
	public function test_redirect_is_string_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'string', $docs['redirect']['type'] );
	}

	/**
	 * Test label_username attribute is string type.
	 */
	public function test_label_username_is_string(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'string', $docs['label_username']['type'] );
	}

	/**
	 * Test render shows logged-in message when user is logged in.
	 */
	public function test_render_shows_logged_in_message(): void {
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
		$this->assertStringContainsString( 'Log Out', $result );
	}
}
