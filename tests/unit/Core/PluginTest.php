<?php
/**
 * Unit tests for Plugin class.
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use APD\Core\Plugin;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Class PluginTest
 *
 * Tests for the Plugin class, focusing on text domain loading.
 */
class PluginTest extends UnitTestCase {

	/**
	 * Test load_textdomain method exists.
	 */
	public function test_load_textdomain_method_exists(): void {
		$this->assertTrue(
			method_exists( Plugin::class, 'load_textdomain' ),
			'Plugin class should have load_textdomain method'
		);
	}

	/**
	 * Test load_textdomain method exists and is callable.
	 *
	 * Since WordPress 4.6, translations for WordPress.org hosted plugins are
	 * loaded automatically. The load_textdomain method fires an action hook
	 * that extensions can use to add custom translation loading.
	 */
	public function test_load_textdomain_is_callable(): void {
		$reflection = new \ReflectionClass( Plugin::class );
		$method     = $reflection->getMethod( 'load_textdomain' );

		$instance = $reflection->newInstanceWithoutConstructor();

		// Should execute without errors.
		$method->invoke( $instance );

		$this->assertTrue( true );
	}

	/**
	 * Test load_textdomain does not call load_plugin_textdomain.
	 *
	 * Since WordPress 4.6, translations for WordPress.org hosted plugins are
	 * loaded automatically. We don't call load_plugin_textdomain directly.
	 */
	public function test_load_textdomain_not_called_directly(): void {
		// load_plugin_textdomain should never be called directly.
		Functions\expect( 'load_plugin_textdomain' )->never();

		$reflection = new \ReflectionClass( Plugin::class );
		$method     = $reflection->getMethod( 'load_textdomain' );

		$instance = $reflection->newInstanceWithoutConstructor();
		$method->invoke( $instance );
	}

	/**
	 * Test text domain constant is correctly defined in plugin header.
	 */
	public function test_text_domain_is_all_purpose_directory(): void {
		// Read the main plugin file header.
		$plugin_file    = dirname( __DIR__, 3 ) . '/all-purpose-directory.php';
		$plugin_content = file_get_contents( $plugin_file );

		$this->assertStringContainsString(
			'Text Domain:       all-purpose-directory',
			$plugin_content,
			'Plugin header should define text domain as all-purpose-directory'
		);
	}

	/**
	 * Test domain path is correctly defined in plugin header.
	 */
	public function test_domain_path_is_languages(): void {
		$plugin_file    = dirname( __DIR__, 3 ) . '/all-purpose-directory.php';
		$plugin_content = file_get_contents( $plugin_file );

		$this->assertStringContainsString(
			'Domain Path:       /languages',
			$plugin_content,
			'Plugin header should define domain path as /languages'
		);
	}

	/**
	 * Test languages directory exists.
	 */
	public function test_languages_directory_exists(): void {
		$languages_dir = dirname( __DIR__, 3 ) . '/languages';

		$this->assertDirectoryExists(
			$languages_dir,
			'Languages directory should exist'
		);
	}
}
