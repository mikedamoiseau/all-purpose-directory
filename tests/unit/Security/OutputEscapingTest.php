<?php
/**
 * Tests for output escaping contracts in plugin rendering code.
 *
 * @package APD\Tests\Unit\Security
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Security;

/**
 * OutputEscapingTest verifies plugin output paths reference escaping helpers.
 *
 * Note: WordPress escaping helper behavior is covered in integration tests.
 */
class OutputEscapingTest extends SecurityTestCase {

	/**
	 * Test that ListingsShortcode documents template-level escaping.
	 */
	public function test_listings_shortcode_documents_template_escaping(): void {
		$source = file_get_contents( __DIR__ . '/../../../src/Shortcode/ListingsShortcode.php' );

		$this->assertStringContainsString(
			'phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML escaped in view templates',
			$source
		);
	}

	/**
	 * Test that ReviewDisplay documents template-level escaping.
	 */
	public function test_review_display_documents_template_escaping(): void {
		$source = file_get_contents( __DIR__ . '/../../../src/Review/ReviewDisplay.php' );

		$this->assertStringContainsString(
			'phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML escaped in review templates',
			$source
		);
	}

	/**
	 * Test that FavoriteToggle documents and uses escaping in get_button().
	 */
	public function test_favorite_toggle_get_button_uses_escaping_helpers(): void {
		$source = file_get_contents( __DIR__ . '/../../../src/User/FavoriteToggle.php' );

		$this->assertStringContainsString(
			'phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML escaped in get_button()',
			$source
		);
		$this->assertMatchesRegularExpression( '/function get_button.*esc_attr/s', $source );
		$this->assertMatchesRegularExpression( '/function get_button.*esc_html/s', $source );
	}

	/**
	 * Test that contact email template escapes user-provided values.
	 */
	public function test_contact_handler_email_template_escapes_user_content(): void {
		$source = file_get_contents( __DIR__ . '/../../../src/Contact/ContactHandler.php' );

		$this->assertStringContainsString( 'esc_html( $data[\'contact_name\'] )', $source );
		$this->assertStringContainsString( 'esc_attr( $data[\'contact_email\'] )', $source );
		$this->assertStringContainsString( 'nl2br( esc_html( $data[\'contact_message\'] ) )', $source );
	}

	/**
	 * Test that output-heavy classes reference core escaping helpers.
	 */
	public function test_output_classes_reference_wp_escaping_helpers(): void {
		$files = [
			__DIR__ . '/../../../src/Shortcode/ListingsShortcode.php',
			__DIR__ . '/../../../src/Review/ReviewDisplay.php',
			__DIR__ . '/../../../src/User/FavoriteToggle.php',
		];

		foreach ( $files as $file ) {
			$source = file_get_contents( $file );
			$this->assertMatchesRegularExpression( '/esc_(html|attr|url)/', $source, $file );
		}
	}
}
