<?php
/**
 * Module Functions Unit Tests.
 *
 * @package APD\Tests\Unit\Module
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Module;

use APD\Module\ModuleRegistry;
use APD\Module\ModulesAdminPage;
use APD\Module\ModuleInterface;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for module helper functions.
 */
final class ModuleFunctionsTest extends UnitTestCase {

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset singletons for clean tests.
		ModuleRegistry::reset_instance();
		ModulesAdminPage::reset_instance();

		// Define APD constants if not defined.
		if ( ! defined( 'APD_VERSION' ) ) {
			define( 'APD_VERSION', '1.0.0' );
		}

		// Common mock setup.
		Functions\stubs( [
			'sanitize_key' => function( $key ) {
				return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
			},
			'apply_filters' => function( $hook, $value ) {
				return $value;
			},
			'wp_parse_args' => function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			},
			'absint' => function( $val ) {
				return abs( (int) $val );
			},
			'admin_url' => function( $path = '' ) {
				return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
			},
			'esc_html' => function( $text ) {
				return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
			},
			'esc_html__' => function( $text, $domain = 'default' ) {
				return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
			},
			'__' => function( $text, $domain = 'default' ) {
				return $text;
			},
		] );
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		ModuleRegistry::reset_instance();
		ModulesAdminPage::reset_instance();
		parent::tearDown();
	}

	/**
	 * Test apd_module_registry returns registry instance.
	 */
	public function test_apd_module_registry_returns_instance(): void {
		$registry = apd_module_registry();

		$this->assertInstanceOf( ModuleRegistry::class, $registry );
	}

	/**
	 * Test apd_module_registry returns singleton.
	 */
	public function test_apd_module_registry_returns_singleton(): void {
		$registry1 = apd_module_registry();
		$registry2 = apd_module_registry();

		$this->assertSame( $registry1, $registry2 );
	}

	/**
	 * Test apd_register_module registers successfully.
	 */
	public function test_apd_register_module_registers_successfully(): void {
		$result = apd_register_module( 'test-module', [
			'name'        => 'Test Module',
			'description' => 'A test module',
			'version'     => '1.0.0',
		] );

		$this->assertTrue( $result );
		$this->assertTrue( apd_has_module( 'test-module' ) );
	}

	/**
	 * Test apd_register_module returns false on failure.
	 */
	public function test_apd_register_module_returns_false_on_failure(): void {
		$result = apd_register_module( '', [ 'name' => 'Test' ] );

		$this->assertFalse( $result );
		$this->assertSame( 0, apd_module_count() );
	}

	/**
	 * Test apd_register_module_class registers class module.
	 */
	public function test_apd_register_module_class_registers_class_module(): void {
		$mock = Mockery::mock( ModuleInterface::class );
		$mock->shouldReceive( 'get_slug' )->andReturn( 'class-module' );
		$mock->shouldReceive( 'get_name' )->andReturn( 'Class Module' );
		$mock->shouldReceive( 'get_description' )->andReturn( 'A class module' );
		$mock->shouldReceive( 'get_version' )->andReturn( '1.0.0' );
		$mock->shouldReceive( 'get_config' )->andReturn( [] );
		$mock->shouldReceive( 'init' )->once();

		$result = apd_register_module_class( $mock );

		$this->assertTrue( $result );
		$this->assertTrue( apd_has_module( 'class-module' ) );
	}

	/**
	 * Test apd_unregister_module unregisters module.
	 */
	public function test_apd_unregister_module_unregisters_module(): void {
		apd_register_module( 'test-module', [ 'name' => 'Test Module' ] );
		$this->assertTrue( apd_has_module( 'test-module' ) );

		$result = apd_unregister_module( 'test-module' );

		$this->assertTrue( $result );
		$this->assertFalse( apd_has_module( 'test-module' ) );
	}

	/**
	 * Test apd_unregister_module returns false for nonexistent.
	 */
	public function test_apd_unregister_module_returns_false_for_nonexistent(): void {
		$result = apd_unregister_module( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test apd_get_module returns module config.
	 */
	public function test_apd_get_module_returns_module_config(): void {
		apd_register_module( 'test-module', [
			'name'        => 'Test Module',
			'description' => 'A test',
		] );

		$module = apd_get_module( 'test-module' );

		$this->assertIsArray( $module );
		$this->assertSame( 'Test Module', $module['name'] );
		$this->assertSame( 'A test', $module['description'] );
	}

	/**
	 * Test apd_get_module returns null for nonexistent.
	 */
	public function test_apd_get_module_returns_null_for_nonexistent(): void {
		$module = apd_get_module( 'nonexistent' );

		$this->assertNull( $module );
	}

	/**
	 * Test apd_get_modules returns all modules.
	 */
	public function test_apd_get_modules_returns_all_modules(): void {
		apd_register_module( 'module-a', [ 'name' => 'Module A' ] );
		apd_register_module( 'module-b', [ 'name' => 'Module B' ] );

		$modules = apd_get_modules();

		$this->assertCount( 2, $modules );
		$this->assertArrayHasKey( 'module-a', $modules );
		$this->assertArrayHasKey( 'module-b', $modules );
	}

	/**
	 * Test apd_get_modules accepts filter args.
	 */
	public function test_apd_get_modules_accepts_filter_args(): void {
		apd_register_module( 'module-a', [
			'name'     => 'Module A',
			'features' => [ 'feature1' ],
		] );
		apd_register_module( 'module-b', [
			'name'     => 'Module B',
			'features' => [ 'feature2' ],
		] );

		$modules = apd_get_modules( [ 'feature' => 'feature1' ] );

		$this->assertCount( 1, $modules );
		$this->assertArrayHasKey( 'module-a', $modules );
	}

	/**
	 * Test apd_has_module returns correct value.
	 */
	public function test_apd_has_module_returns_correct_value(): void {
		$this->assertFalse( apd_has_module( 'test-module' ) );

		apd_register_module( 'test-module', [ 'name' => 'Test' ] );

		$this->assertTrue( apd_has_module( 'test-module' ) );
	}

	/**
	 * Test apd_module_count returns correct count.
	 */
	public function test_apd_module_count_returns_correct_count(): void {
		$this->assertSame( 0, apd_module_count() );

		apd_register_module( 'module-1', [ 'name' => 'Module 1' ] );
		$this->assertSame( 1, apd_module_count() );

		apd_register_module( 'module-2', [ 'name' => 'Module 2' ] );
		$this->assertSame( 2, apd_module_count() );
	}

	/**
	 * Test apd_module_requirements_met returns empty for met requirements.
	 */
	public function test_apd_module_requirements_met_returns_empty_for_met(): void {
		$unmet = apd_module_requirements_met( [ 'core' => '1.0.0' ] );

		$this->assertSame( [], $unmet );
	}

	/**
	 * Test apd_module_requirements_met returns unmet requirements.
	 */
	public function test_apd_module_requirements_met_returns_unmet(): void {
		$unmet = apd_module_requirements_met( [ 'core' => '99.0.0' ] );

		$this->assertArrayHasKey( 'core', $unmet );
	}

	/**
	 * Test apd_get_modules_by_feature filters correctly.
	 */
	public function test_apd_get_modules_by_feature_filters_correctly(): void {
		apd_register_module( 'module-a', [
			'name'     => 'Module A',
			'features' => [ 'link_checker', 'favicon' ],
		] );
		apd_register_module( 'module-b', [
			'name'     => 'Module B',
			'features' => [ 'link_checker' ],
		] );
		apd_register_module( 'module-c', [
			'name'     => 'Module C',
			'features' => [ 'other' ],
		] );

		$modules = apd_get_modules_by_feature( 'link_checker' );

		$this->assertCount( 2, $modules );
		$this->assertArrayHasKey( 'module-a', $modules );
		$this->assertArrayHasKey( 'module-b', $modules );
	}

	/**
	 * Test apd_get_modules_page_url returns URL.
	 */
	public function test_apd_get_modules_page_url_returns_url(): void {
		$url = apd_get_modules_page_url();

		$this->assertStringContainsString( 'wp-admin', $url );
		$this->assertStringContainsString( 'apd-modules', $url );
	}
}
