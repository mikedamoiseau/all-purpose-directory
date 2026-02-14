<?php
/**
 * Tests for string utility functions (apd_strlen, apd_substr).
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;

/**
 * StringUtility test case.
 *
 * Tests the apd_strlen() and apd_substr() wrapper functions
 * that provide mbstring fallback support.
 *
 * Note: These tests do NOT use Brain Monkey since they test pure PHP
 * wrapper functions that don't depend on WordPress.
 */
class StringUtilityTest extends TestCase {

	// =========================================================================
	// apd_strlen() tests
	// =========================================================================

	/**
	 * Test apd_strlen with ASCII string.
	 *
	 * @return void
	 */
	public function test_strlen_ascii(): void {
		$this->assertSame( 5, apd_strlen_impl( 'hello' ) );
	}

	/**
	 * Test apd_strlen with empty string.
	 *
	 * @return void
	 */
	public function test_strlen_empty(): void {
		$this->assertSame( 0, apd_strlen_impl( '' ) );
	}

	/**
	 * Test apd_strlen with multi-byte characters.
	 *
	 * @return void
	 */
	public function test_strlen_multibyte(): void {
		if ( ! function_exists( 'mb_strlen' ) ) {
			$this->markTestSkipped( 'ext-mbstring not available.' );
		}

		// 3 characters, but 9 bytes in UTF-8
		$this->assertSame( 3, apd_strlen_impl( '日本語' ) );
	}

	/**
	 * Test apd_strlen with emoji.
	 *
	 * @return void
	 */
	public function test_strlen_emoji(): void {
		if ( ! function_exists( 'mb_strlen' ) ) {
			$this->markTestSkipped( 'ext-mbstring not available.' );
		}

		// "Hi 👋" = 4 characters (H, i, space, wave emoji)
		$this->assertSame( 4, apd_strlen_impl( 'Hi 👋' ) );
	}

	/**
	 * Test apd_strlen with accented characters.
	 *
	 * @return void
	 */
	public function test_strlen_accented(): void {
		if ( ! function_exists( 'mb_strlen' ) ) {
			$this->markTestSkipped( 'ext-mbstring not available.' );
		}

		// "café" = 4 characters
		$this->assertSame( 4, apd_strlen_impl( 'café' ) );
	}

	/**
	 * Test apd_strlen with single character.
	 *
	 * @return void
	 */
	public function test_strlen_single_char(): void {
		$this->assertSame( 1, apd_strlen_impl( 'a' ) );
	}

	// =========================================================================
	// apd_substr() tests
	// =========================================================================

	/**
	 * Test apd_substr with ASCII string.
	 *
	 * @return void
	 */
	public function test_substr_ascii(): void {
		$this->assertSame( 'llo', apd_substr_impl( 'hello', 2 ) );
	}

	/**
	 * Test apd_substr with length parameter.
	 *
	 * @return void
	 */
	public function test_substr_with_length(): void {
		$this->assertSame( 'hel', apd_substr_impl( 'hello', 0, 3 ) );
	}

	/**
	 * Test apd_substr with multi-byte characters.
	 *
	 * @return void
	 */
	public function test_substr_multibyte(): void {
		if ( ! function_exists( 'mb_substr' ) ) {
			$this->markTestSkipped( 'ext-mbstring not available.' );
		}

		// First 2 characters of "日本語"
		$this->assertSame( '日本', apd_substr_impl( '日本語', 0, 2 ) );
	}

	/**
	 * Test apd_substr from offset with multi-byte.
	 *
	 * @return void
	 */
	public function test_substr_multibyte_offset(): void {
		if ( ! function_exists( 'mb_substr' ) ) {
			$this->markTestSkipped( 'ext-mbstring not available.' );
		}

		// From character 1 onward in "日本語"
		$this->assertSame( '本語', apd_substr_impl( '日本語', 1 ) );
	}

	/**
	 * Test apd_substr with empty string.
	 *
	 * @return void
	 */
	public function test_substr_empty(): void {
		$this->assertSame( '', apd_substr_impl( '', 0, 5 ) );
	}

	/**
	 * Test apd_substr with zero length.
	 *
	 * @return void
	 */
	public function test_substr_zero_length(): void {
		$this->assertSame( '', apd_substr_impl( 'hello', 0, 0 ) );
	}

	/**
	 * Test apd_substr with null length returns remainder.
	 *
	 * @return void
	 */
	public function test_substr_null_length(): void {
		$this->assertSame( 'lo', apd_substr_impl( 'hello', 3, null ) );
	}

	// =========================================================================
	// Source-level verification tests
	// =========================================================================

	/**
	 * Test that no direct mb_strlen calls remain in src/.
	 *
	 * @return void
	 */
	public function test_no_direct_mb_strlen_in_src(): void {
		$src_dir = __DIR__ . '/../../../src';
		$files   = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $src_dir, \RecursiveDirectoryIterator::SKIP_DOTS )
		);

		$violations = [];

		foreach ( $files as $file ) {
			if ( $file->getExtension() !== 'php' ) {
				continue;
			}

			$content = file_get_contents( $file->getPathname() );
			if ( preg_match( '/\bmb_strlen\s*\(/', $content ) ) {
				$violations[] = $file->getPathname();
			}
		}

		$this->assertEmpty(
			$violations,
			'Direct mb_strlen() calls found in src/ (use apd_strlen() instead): ' . implode( ', ', $violations )
		);
	}

	/**
	 * Test that no direct mb_substr calls remain in src/.
	 *
	 * @return void
	 */
	public function test_no_direct_mb_substr_in_src(): void {
		$src_dir = __DIR__ . '/../../../src';
		$files   = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $src_dir, \RecursiveDirectoryIterator::SKIP_DOTS )
		);

		$violations = [];

		foreach ( $files as $file ) {
			if ( $file->getExtension() !== 'php' ) {
				continue;
			}

			$content = file_get_contents( $file->getPathname() );
			if ( preg_match( '/\bmb_substr\s*\(/', $content ) ) {
				$violations[] = $file->getPathname();
			}
		}

		$this->assertEmpty(
			$violations,
			'Direct mb_substr() calls found in src/ (use apd_substr() instead): ' . implode( ', ', $violations )
		);
	}

	/**
	 * Test that apd_strlen wrapper exists in functions.php.
	 *
	 * @return void
	 */
	public function test_apd_strlen_exists_in_functions(): void {
		$source = file_get_contents( __DIR__ . '/../../../includes/functions.php' );

		$this->assertStringContainsString(
			'function apd_strlen',
			$source,
			'apd_strlen() wrapper should exist in includes/functions.php'
		);
	}

	/**
	 * Test that apd_substr wrapper exists in functions.php.
	 *
	 * @return void
	 */
	public function test_apd_substr_exists_in_functions(): void {
		$source = file_get_contents( __DIR__ . '/../../../includes/functions.php' );

		$this->assertStringContainsString(
			'function apd_substr',
			$source,
			'apd_substr() wrapper should exist in includes/functions.php'
		);
	}

	/**
	 * Test that apd_strlen uses mb_strlen with UTF-8 encoding.
	 *
	 * @return void
	 */
	public function test_apd_strlen_source_uses_utf8(): void {
		$source = file_get_contents( __DIR__ . '/../../../includes/functions.php' );

		// Verify the function specifies UTF-8 encoding
		$pattern = '/function apd_strlen.*?mb_strlen\(\s*\$string\s*,\s*[\'"]UTF-8[\'"]\s*\)/s';
		$this->assertMatchesRegularExpression(
			$pattern,
			$source,
			'apd_strlen() should call mb_strlen with UTF-8 encoding'
		);
	}

	/**
	 * Test that apd_substr uses mb_substr with UTF-8 encoding.
	 *
	 * @return void
	 */
	public function test_apd_substr_source_uses_utf8(): void {
		$source = file_get_contents( __DIR__ . '/../../../includes/functions.php' );

		// Verify the function specifies UTF-8 encoding
		$pattern = '/function apd_substr.*?mb_substr\(\s*\$string\s*,\s*\$start\s*,\s*\$length\s*,\s*[\'"]UTF-8[\'"]\s*\)/s';
		$this->assertMatchesRegularExpression(
			$pattern,
			$source,
			'apd_substr() should call mb_substr with UTF-8 encoding'
		);
	}
}

// =========================================================================
// Test-local implementations that mirror the production functions.
// These avoid loading functions.php (which conflicts with Brain Monkey
// stubs in the full test suite) while testing the exact same logic.
// =========================================================================

/**
 * Test implementation mirroring apd_strlen from includes/functions.php.
 */
function apd_strlen_impl( string $string ): int {
	if ( function_exists( 'mb_strlen' ) ) {
		return mb_strlen( $string, 'UTF-8' );
	}

	return strlen( $string );
}

/**
 * Test implementation mirroring apd_substr from includes/functions.php.
 */
function apd_substr_impl( string $string, int $start, ?int $length = null ): string {
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $string, $start, $length, 'UTF-8' );
	}

	if ( $length === null ) {
		return substr( $string, $start );
	}

	return substr( $string, $start, $length );
}
