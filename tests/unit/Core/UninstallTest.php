<?php
/**
 * Tests for uninstall behavior.
 *
 * Verifies that uninstall.php respects the delete_data setting and
 * uses the correct option name (apd_options).
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use APD\Admin\Settings;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Tests for uninstall logic.
 */
class UninstallTest extends UnitTestCase {

	/**
	 * Path to the uninstall.php file.
	 *
	 * @var string
	 */
	private string $uninstall_file;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->uninstall_file = dirname( __DIR__, 3 ) . '/uninstall.php';
	}

	/**
	 * Test that uninstall.php exists.
	 */
	public function test_uninstall_file_exists(): void {
		$this->assertFileExists( $this->uninstall_file );
	}

	/**
	 * Test uninstall reads from the correct option name (apd_options).
	 */
	public function test_uninstall_uses_correct_option_name(): void {
		$content = file_get_contents( $this->uninstall_file );

		$this->assertStringContainsString(
			"get_option( 'apd_options'",
			$content,
			'uninstall.php should read from apd_options (not apd_settings)'
		);

		$this->assertStringNotContainsString(
			"get_option( 'apd_settings'",
			$content,
			'uninstall.php should not reference apd_settings'
		);
	}

	/**
	 * Test uninstall checks delete_data flag (not keep_data_on_uninstall).
	 */
	public function test_uninstall_checks_delete_data_flag(): void {
		$content = file_get_contents( $this->uninstall_file );

		$this->assertStringContainsString(
			"'delete_data'",
			$content,
			'uninstall.php should check the delete_data setting'
		);

		$this->assertStringNotContainsString(
			'keep_data_on_uninstall',
			$content,
			'uninstall.php should not reference the old keep_data_on_uninstall key'
		);
	}

	/**
	 * Test uninstall returns early when delete_data is false (empty).
	 *
	 * The logic should be: if delete_data is NOT set, return early (keep data).
	 */
	public function test_uninstall_preserves_data_when_delete_data_false(): void {
		$content = file_get_contents( $this->uninstall_file );

		// The check should return early when delete_data is empty.
		$this->assertStringContainsString(
			"empty( \$settings['delete_data'] )",
			$content,
			'uninstall.php should return early when delete_data is empty/false'
		);
	}

	/**
	 * Test uninstall deletes the correct option name.
	 */
	public function test_uninstall_deletes_correct_option(): void {
		$content = file_get_contents( $this->uninstall_file );

		$this->assertStringContainsString(
			"'apd_options'",
			$content,
			'uninstall.php should delete apd_options'
		);
	}

	/**
	 * Test uninstall clears scheduled cron events.
	 */
	public function test_uninstall_clears_cron_events(): void {
		$content = file_get_contents( $this->uninstall_file );

		$this->assertStringContainsString(
			"wp_clear_scheduled_hook( 'apd_check_expired_listings' )",
			$content,
			'uninstall.php should clear the expiration check cron'
		);

		$this->assertStringContainsString(
			"wp_clear_scheduled_hook( 'apd_cleanup_transients' )",
			$content,
			'uninstall.php should clear the transient cleanup cron'
		);
	}

	/**
	 * Test that the Settings class OPTION_NAME matches what uninstall uses.
	 */
	public function test_option_name_matches_settings_class(): void {
		$this->assertSame( 'apd_options', Settings::OPTION_NAME );

		$content = file_get_contents( $this->uninstall_file );
		$this->assertStringContainsString(
			Settings::OPTION_NAME,
			$content,
			'uninstall.php should use the same option name as Settings::OPTION_NAME'
		);
	}
}
