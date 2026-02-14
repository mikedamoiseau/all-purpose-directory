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

	// =========================================================================
	// Conditional Bootstrapping Tests
	// =========================================================================

	/**
	 * Get the init_hooks() source code for bootstrapping tests.
	 *
	 * @return string
	 */
	private function get_init_hooks_source(): string {
		$source  = file_get_contents( dirname( __DIR__, 3 ) . '/src/Core/Plugin.php' );
		$pattern = '/private function init_hooks\(\): void \{.*?^\t\}/ms';
		preg_match( $pattern, $source, $matches );
		return $matches[0] ?? '';
	}

	/**
	 * Test that admin-only classes are wrapped in is_admin() guard.
	 */
	public function test_admin_classes_guarded_by_is_admin(): void {
		$source = $this->get_init_hooks_source();

		// Extract the is_admin() block content.
		$pattern = '/if \( is_admin\(\) \) \{(.*?)\n\t\t\}/s';
		$this->assertMatchesRegularExpression(
			$pattern,
			$source,
			'init_hooks should contain an is_admin() guard block'
		);

		preg_match( $pattern, $source, $matches );
		$admin_block = $matches[1];

		$admin_classes = [
			'AdminColumns',
			'ListingMetaBox',
			'ReviewModeration',
			'Settings',
			'ModulesAdminPage',
			'DemoDataPage',
		];

		foreach ( $admin_classes as $class ) {
			$this->assertStringContainsString(
				$class,
				$admin_block,
				"$class should be inside the is_admin() guard block"
			);
		}
	}

	/**
	 * Test that frontend-only classes are wrapped in ! is_admin() guard.
	 */
	public function test_frontend_classes_guarded_by_not_is_admin(): void {
		$source = $this->get_init_hooks_source();

		// Extract the ! is_admin() block content.
		$pattern = '/if \( ! is_admin\(\) \) \{(.*?)\n\t\t\}/s';
		$this->assertMatchesRegularExpression(
			$pattern,
			$source,
			'init_hooks should contain a ! is_admin() guard block'
		);

		preg_match( $pattern, $source, $matches );
		$frontend_block = $matches[1];

		$frontend_classes = [
			'TemplateLoader',
			'ReviewDisplay',
			'ReviewForm',
			'ContactForm',
		];

		foreach ( $frontend_classes as $class ) {
			$this->assertStringContainsString(
				$class,
				$frontend_block,
				"$class should be inside the ! is_admin() guard block"
			);
		}
	}

	/**
	 * Test that shared classes with AJAX handlers are NOT inside any guard.
	 */
	public function test_shared_classes_not_guarded(): void {
		$source = $this->get_init_hooks_source();

		// Remove the guarded blocks to get just the shared section.
		$without_admin    = preg_replace( '/if \( is_admin\(\) \) \{.*?\n\t\t\}/s', '', $source );
		$without_frontend = preg_replace( '/if \( ! is_admin\(\) \) \{.*?\n\t\t\}/s', '', $without_admin );

		$shared_classes = [
			'AjaxHandler',
			'FavoriteToggle',
			'ReviewHandler',
			'ContactHandler',
			'EmailManager',
			'RestController',
			'SearchQuery',
		];

		foreach ( $shared_classes as $class ) {
			$this->assertStringContainsString(
				$class,
				$without_frontend,
				"$class should be in the shared (unguarded) section, not inside a context guard"
			);
		}
	}

	/**
	 * Test that admin-only classes are NOT in the shared section.
	 */
	public function test_admin_classes_not_in_shared_section(): void {
		$source = $this->get_init_hooks_source();

		// Remove the guarded blocks to get just the shared section.
		$without_admin    = preg_replace( '/if \( is_admin\(\) \) \{.*?\n\t\t\}/s', '', $source );
		$without_frontend = preg_replace( '/if \( ! is_admin\(\) \) \{.*?\n\t\t\}/s', '', $without_admin );

		// AdminColumns should only appear inside the is_admin() block, not in shared.
		$this->assertStringNotContainsString(
			'AdminColumns',
			$without_frontend,
			'AdminColumns should not be in the shared (unguarded) section'
		);
	}
}
