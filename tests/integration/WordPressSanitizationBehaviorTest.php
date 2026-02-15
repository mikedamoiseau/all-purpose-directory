<?php
/**
 * Integration tests for WordPress helper behavior.
 *
 * @package APD\Tests\Integration
 */

declare(strict_types=1);

namespace APD\Tests\Integration;

use APD\Tests\TestCase;

/**
 * Verifies behavior-sensitive sanitization/escaping helpers in real WP runtime.
 */
class WordPressSanitizationBehaviorTest extends TestCase {

	/**
	 * Test sanitize_text_field strips HTML tags in WordPress runtime.
	 */
	public function test_sanitize_text_field_strips_tags_in_wp_runtime(): void {
		$input  = '<script>alert("xss")</script>Hello';
		$result = sanitize_text_field( $input );

		$this->assertStringNotContainsString( '<script>', $result );
		$this->assertStringContainsString( 'Hello', $result );
	}

	/**
	 * Test esc_html escapes brackets in WordPress runtime.
	 */
	public function test_esc_html_escapes_html_in_wp_runtime(): void {
		$input  = '<b>Test</b>';
		$result = esc_html( $input );

		$this->assertStringContainsString( '&lt;', $result );
		$this->assertStringContainsString( '&gt;', $result );
	}

	/**
	 * Test wp_kses_post keeps safe markup and removes script tags.
	 */
	public function test_wp_kses_post_filters_unsafe_markup_in_wp_runtime(): void {
		$input  = '<p>Safe</p><script>alert(1)</script>';
		$result = wp_kses_post( $input );

		$this->assertStringContainsString( '<p>Safe</p>', $result );
		$this->assertStringNotContainsString( '<script>', $result );
	}

	/**
	 * Test wp_unslash removes escaped slashes.
	 */
	public function test_wp_unslash_removes_slashes_in_wp_runtime(): void {
		$input  = "It\\'s a test";
		$result = wp_unslash( $input );

		$this->assertSame( "It's a test", $result );
	}
}
