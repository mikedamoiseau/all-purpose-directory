<?php
/**
 * AbstractShortcode Unit Tests.
 *
 * @package APD\Tests\Unit\Shortcode
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Shortcode;

use APD\Shortcode\AbstractShortcode;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for AbstractShortcode.
 */
final class AbstractShortcodeTest extends UnitTestCase {

	/**
	 * Test shortcode for testing.
	 *
	 * @var TestShortcode
	 */
	private TestShortcode $shortcode;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->shortcode = new TestShortcode();
	}

	/**
	 * Test get_tag returns tag.
	 */
	public function test_get_tag(): void {
		$this->assertSame( 'test_shortcode', $this->shortcode->get_tag() );
	}

	/**
	 * Test get_description returns description.
	 */
	public function test_get_description(): void {
		$this->assertSame( 'A test shortcode.', $this->shortcode->get_description() );
	}

	/**
	 * Test get_defaults returns defaults.
	 */
	public function test_get_defaults(): void {
		$defaults = $this->shortcode->get_defaults();

		$this->assertArrayHasKey( 'title', $defaults );
		$this->assertArrayHasKey( 'count', $defaults );
		$this->assertSame( '', $defaults['title'] );
		$this->assertSame( 10, $defaults['count'] );
	}

	/**
	 * Test get_attribute_docs returns documentation.
	 */
	public function test_get_attribute_docs(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertArrayHasKey( 'title', $docs );
		$this->assertArrayHasKey( 'count', $docs );
		$this->assertSame( 'string', $docs['title']['type'] );
		$this->assertSame( 'integer', $docs['count']['type'] );
	}

	/**
	 * Test get_example returns example.
	 */
	public function test_get_example(): void {
		$this->assertSame( '[test_shortcode title="Hello"]', $this->shortcode->get_example() );
	}

	/**
	 * Test render parses attributes with defaults.
	 */
	public function test_render_parses_attributes(): void {
		Functions\when( 'shortcode_atts' )->alias( function( $defaults, $atts ) {
			return wp_parse_args( $atts, $defaults );
		} );
		Functions\when( 'wp_parse_args' )->alias( function( $args, $defaults ) {
			return array_merge( $defaults, $args );
		} );
		Functions\when( 'sanitize_text_field' )->alias( function( $value ) {
			return $value;
		} );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$result = $this->shortcode->render( [ 'title' => 'Test' ] );

		$this->assertStringContainsString( 'Title: Test', $result );
		$this->assertStringContainsString( 'Count: 10', $result ); // Default value.
	}

	/**
	 * Test render handles empty attributes.
	 */
	public function test_render_handles_empty_atts(): void {
		Functions\when( 'shortcode_atts' )->alias( function( $defaults, $atts ) {
			return wp_parse_args( $atts, $defaults );
		} );
		Functions\when( 'wp_parse_args' )->alias( function( $args, $defaults ) {
			return array_merge( $defaults, $args );
		} );
		Functions\when( 'sanitize_text_field' )->alias( function( $value ) {
			return $value;
		} );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$result = $this->shortcode->render( '' );

		$this->assertStringContainsString( 'Title: ', $result );
		$this->assertStringContainsString( 'Count: 10', $result );
	}

	/**
	 * Test render sanitizes string attributes.
	 */
	public function test_render_sanitizes_string_attributes(): void {
		Functions\when( 'shortcode_atts' )->alias( function( $defaults, $atts ) {
			return wp_parse_args( $atts, $defaults );
		} );
		Functions\when( 'wp_parse_args' )->alias( function( $args, $defaults ) {
			return array_merge( $defaults, $args );
		} );
		Functions\when( 'sanitize_text_field' )->alias( function( $value ) {
			return strip_tags( (string) $value );
		} );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$result = $this->shortcode->render( [ 'title' => '<script>alert(1)</script>Test' ] );

		$this->assertStringContainsString( 'Title: alert(1)Test', $result );
		$this->assertStringNotContainsString( '<script>', $result );
	}

	/**
	 * Test render sanitizes integer attributes.
	 */
	public function test_render_sanitizes_integer_attributes(): void {
		Functions\when( 'shortcode_atts' )->alias( function( $defaults, $atts ) {
			return wp_parse_args( $atts, $defaults );
		} );
		Functions\when( 'wp_parse_args' )->alias( function( $args, $defaults ) {
			return array_merge( $defaults, $args );
		} );
		Functions\when( 'sanitize_text_field' )->alias( function( $value ) {
			return $value;
		} );
		Functions\when( 'absint' )->alias( function( $value ) {
			return abs( (int) $value );
		} );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$result = $this->shortcode->render( [ 'count' => '-5abc' ] );

		$this->assertStringContainsString( 'Count: 5', $result );
	}

	/**
	 * Test render sanitizes boolean attributes.
	 */
	public function test_render_sanitizes_boolean_attributes(): void {
		Functions\when( 'shortcode_atts' )->alias( function( $defaults, $atts ) {
			return wp_parse_args( $atts, $defaults );
		} );
		Functions\when( 'wp_parse_args' )->alias( function( $args, $defaults ) {
			return array_merge( $defaults, $args );
		} );
		Functions\when( 'sanitize_text_field' )->alias( function( $value ) {
			return $value;
		} );
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		// Test with various boolean-like values.
		$shortcode = new BooleanTestShortcode();

		$result = $shortcode->render( [ 'enabled' => 'true' ] );
		$this->assertStringContainsString( 'Enabled: 1', $result );

		$result = $shortcode->render( [ 'enabled' => 'yes' ] );
		$this->assertStringContainsString( 'Enabled: 1', $result );

		$result = $shortcode->render( [ 'enabled' => '1' ] );
		$this->assertStringContainsString( 'Enabled: 1', $result );

		$result = $shortcode->render( [ 'enabled' => 'false' ] );
		$this->assertStringContainsString( 'Enabled: 0', $result );

		$result = $shortcode->render( [ 'enabled' => 'no' ] );
		$this->assertStringContainsString( 'Enabled: 0', $result );
	}

	/**
	 * Test error method returns empty for non-editors.
	 */
	public function test_error_returns_empty_for_non_editors(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$result = $this->shortcode->publicError( 'Test error' );

		$this->assertSame( '', $result );
	}

	/**
	 * Test error method returns message for editors.
	 */
	public function test_error_returns_message_for_editors(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );

		$result = $this->shortcode->publicError( 'Test error' );

		$this->assertStringContainsString( 'apd-shortcode-error', $result );
		$this->assertStringContainsString( 'Test error', $result );
	}

	/**
	 * Test coming_soon returns placeholder.
	 */
	public function test_coming_soon_returns_placeholder(): void {
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );

		$result = $this->shortcode->publicComingSoon( 'Test feature' );

		$this->assertStringContainsString( 'apd-coming-soon', $result );
		$this->assertStringContainsString( 'Test feature', $result );
	}

	/**
	 * Test require_login returns empty for logged in users.
	 */
	public function test_require_login_returns_empty_for_logged_in(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );

		$result = $this->shortcode->publicRequireLogin();

		$this->assertSame( '', $result );
	}

	/**
	 * Test require_login returns message for guests.
	 */
	public function test_require_login_returns_message_for_guests(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'wp_login_url' )->justReturn( 'https://example.com/wp-login.php' );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/page/' );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );

		$result = $this->shortcode->publicRequireLogin();

		$this->assertStringContainsString( 'apd-login-required', $result );
		$this->assertStringContainsString( 'Log In', $result );
	}
}

/**
 * Test shortcode implementation.
 */
class TestShortcode extends AbstractShortcode {

	protected string $tag = 'test_shortcode';
	protected string $description = 'A test shortcode.';

	protected array $defaults = [
		'title' => '',
		'count' => 10,
	];

	protected array $attribute_docs = [
		'title' => [
			'type'        => 'string',
			'description' => 'The title.',
		],
		'count' => [
			'type'        => 'integer',
			'description' => 'The count.',
		],
	];

	public function get_example(): string {
		return '[test_shortcode title="Hello"]';
	}

	protected function output( array $atts, ?string $content ): string {
		return sprintf( 'Title: %s, Count: %d', $atts['title'], $atts['count'] );
	}

	// Expose protected methods for testing.
	public function publicError( string $message ): string {
		return $this->error( $message );
	}

	public function publicComingSoon( string $feature ): string {
		return $this->coming_soon( $feature );
	}

	public function publicRequireLogin( string $message = '' ): string {
		return $this->require_login( $message );
	}
}

/**
 * Boolean test shortcode implementation.
 */
class BooleanTestShortcode extends AbstractShortcode {

	protected string $tag = 'boolean_test';

	protected array $defaults = [
		'enabled' => false,
	];

	protected array $attribute_docs = [
		'enabled' => [
			'type' => 'boolean',
		],
	];

	protected function output( array $atts, ?string $content ): string {
		return sprintf( 'Enabled: %d', (int) $atts['enabled'] );
	}
}
