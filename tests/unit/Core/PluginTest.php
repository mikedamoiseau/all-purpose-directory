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
	 * Test load_textdomain calls load_plugin_textdomain with correct arguments.
	 */
	public function test_load_textdomain_calls_load_plugin_textdomain(): void {
		Functions\expect( 'load_plugin_textdomain' )
			->once()
			->with(
				'all-purpose-directory',
				false,
				'all-purpose-directory/languages'
			)
			->andReturn( true );

		// Create a reflection to call the method directly.
		$reflection = new \ReflectionClass( Plugin::class );
		$method     = $reflection->getMethod( 'load_textdomain' );

		// Create instance through reflection to avoid singleton issues.
		$instance = $reflection->newInstanceWithoutConstructor();
		$method->invoke( $instance );
	}

	/**
	 * Test load_textdomain uses correct text domain string.
	 */
	public function test_load_textdomain_uses_correct_domain(): void {
		$called_domain = null;

		Functions\when( 'load_plugin_textdomain' )->alias(
			function ( $domain, $deprecated, $path ) use ( &$called_domain ) {
				$called_domain = $domain;
				return true;
			}
		);

		$reflection = new \ReflectionClass( Plugin::class );
		$method     = $reflection->getMethod( 'load_textdomain' );

		$instance = $reflection->newInstanceWithoutConstructor();
		$method->invoke( $instance );

		$this->assertSame(
			'all-purpose-directory',
			$called_domain,
			'Text domain should be all-purpose-directory'
		);
	}

	/**
	 * Test load_textdomain uses correct languages path.
	 */
	public function test_load_textdomain_uses_correct_path(): void {
		$called_path = null;

		Functions\when( 'load_plugin_textdomain' )->alias(
			function ( $domain, $deprecated, $path ) use ( &$called_path ) {
				$called_path = $path;
				return true;
			}
		);

		$reflection = new \ReflectionClass( Plugin::class );
		$method     = $reflection->getMethod( 'load_textdomain' );

		$instance = $reflection->newInstanceWithoutConstructor();
		$method->invoke( $instance );

		$this->assertSame(
			'all-purpose-directory/languages',
			$called_path,
			'Languages path should be all-purpose-directory/languages'
		);
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
