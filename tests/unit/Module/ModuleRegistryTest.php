<?php
/**
 * Module Registry Unit Tests.
 *
 * @package APD\Tests\Unit\Module
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Module;

use APD\Module\ModuleRegistry;
use APD\Module\ModuleInterface;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for ModuleRegistry.
 */
final class ModuleRegistryTest extends UnitTestCase {

	/**
	 * Module registry instance.
	 *
	 * @var ModuleRegistry
	 */
	private ModuleRegistry $registry;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset singleton for clean tests.
		ModuleRegistry::reset_instance();

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

		$this->registry = ModuleRegistry::get_instance();
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		ModuleRegistry::reset_instance();
		parent::tearDown();
	}

	/**
	 * Test singleton pattern returns same instance.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = ModuleRegistry::get_instance();
		$instance2 = ModuleRegistry::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test reset_instance clears the singleton.
	 */
	public function test_reset_instance_clears_singleton(): void {
		$instance1 = ModuleRegistry::get_instance();
		ModuleRegistry::reset_instance();
		$instance2 = ModuleRegistry::get_instance();

		$this->assertNotSame( $instance1, $instance2 );
	}

	/**
	 * Test reset clears all modules.
	 */
	public function test_reset_clears_all_modules(): void {
		$this->registry->register( 'test-module', [
			'name'        => 'Test Module',
			'description' => 'A test module',
			'version'     => '1.0.0',
		] );

		$this->assertSame( 1, $this->registry->count() );

		$this->registry->reset();

		$this->assertSame( 0, $this->registry->count() );
		$this->assertFalse( $this->registry->is_initialized() );
	}

	/**
	 * Test registering a module with array config.
	 */
	public function test_register_with_array_config(): void {
		$result = $this->registry->register( 'url-directory', [
			'name'        => 'URL Directory',
			'description' => 'Turn your directory into a website/link directory.',
			'version'     => '1.0.0',
			'author'      => 'Test Author',
		] );

		$this->assertTrue( $result );
		$this->assertTrue( $this->registry->has( 'url-directory' ) );
		$this->assertSame( 1, $this->registry->count() );
	}

	/**
	 * Test register fails with empty slug.
	 */
	public function test_register_fails_with_empty_slug(): void {
		$result = $this->registry->register( '', [
			'name' => 'Test Module',
		] );

		$this->assertFalse( $result );
		$this->assertSame( 0, $this->registry->count() );
	}

	/**
	 * Test register fails without name.
	 */
	public function test_register_fails_without_name(): void {
		$result = $this->registry->register( 'test-module', [
			'description' => 'No name provided',
		] );

		$this->assertFalse( $result );
		$this->assertSame( 0, $this->registry->count() );
	}

	/**
	 * Test register fails with duplicate slug.
	 */
	public function test_register_fails_with_duplicate_slug(): void {
		$this->registry->register( 'test-module', [
			'name' => 'Test Module',
		] );

		$result = $this->registry->register( 'test-module', [
			'name' => 'Another Module',
		] );

		$this->assertFalse( $result );
		$this->assertSame( 1, $this->registry->count() );
	}

	/**
	 * Test register merges with defaults.
	 */
	public function test_register_merges_with_defaults(): void {
		$this->registry->register( 'test-module', [
			'name' => 'Test Module',
		] );

		$module = $this->registry->get( 'test-module' );

		$this->assertSame( 'Test Module', $module['name'] );
		$this->assertSame( '', $module['description'] );
		$this->assertSame( '1.0.0', $module['version'] );
		$this->assertSame( '', $module['author'] );
		$this->assertSame( 'dashicons-admin-plugins', $module['icon'] );
		$this->assertSame( 10, $module['priority'] );
		$this->assertSame( [], $module['requires'] );
		$this->assertSame( [], $module['features'] );
		$this->assertSame( 'test-module', $module['slug'] );
	}

	/**
	 * Test registering a class-based module.
	 */
	public function test_register_module_with_class(): void {
		$mock = Mockery::mock( ModuleInterface::class );
		$mock->shouldReceive( 'get_slug' )->andReturn( 'class-module' );
		$mock->shouldReceive( 'get_name' )->andReturn( 'Class Module' );
		$mock->shouldReceive( 'get_description' )->andReturn( 'A class-based module' );
		$mock->shouldReceive( 'get_version' )->andReturn( '2.0.0' );
		$mock->shouldReceive( 'get_config' )->andReturn( [
			'author'   => 'Test Author',
			'features' => [ 'feature1' ],
		] );
		$mock->shouldReceive( 'init' )->once();

		$result = $this->registry->register_module( $mock );

		$this->assertTrue( $result );
		$this->assertTrue( $this->registry->has( 'class-module' ) );

		$module = $this->registry->get( 'class-module' );
		$this->assertSame( 'Class Module', $module['name'] );
		$this->assertSame( 'A class-based module', $module['description'] );
		$this->assertSame( '2.0.0', $module['version'] );
		$this->assertSame( $mock, $module['instance'] );
	}

	/**
	 * Test unregister removes module.
	 */
	public function test_unregister_removes_module(): void {
		$this->registry->register( 'test-module', [
			'name' => 'Test Module',
		] );

		$this->assertTrue( $this->registry->has( 'test-module' ) );

		$result = $this->registry->unregister( 'test-module' );

		$this->assertTrue( $result );
		$this->assertFalse( $this->registry->has( 'test-module' ) );
	}

	/**
	 * Test unregister returns false for non-existent module.
	 */
	public function test_unregister_returns_false_for_nonexistent(): void {
		$result = $this->registry->unregister( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test get returns null for non-existent module.
	 */
	public function test_get_returns_null_for_nonexistent(): void {
		$result = $this->registry->get( 'nonexistent' );

		$this->assertNull( $result );
	}

	/**
	 * Test get_all returns all modules.
	 */
	public function test_get_all_returns_all_modules(): void {
		$this->registry->register( 'module-a', [ 'name' => 'Module A', 'priority' => 20 ] );
		$this->registry->register( 'module-b', [ 'name' => 'Module B', 'priority' => 10 ] );
		$this->registry->register( 'module-c', [ 'name' => 'Module C', 'priority' => 15 ] );

		$modules = $this->registry->get_all();

		$this->assertCount( 3, $modules );
		$this->assertArrayHasKey( 'module-a', $modules );
		$this->assertArrayHasKey( 'module-b', $modules );
		$this->assertArrayHasKey( 'module-c', $modules );
	}

	/**
	 * Test get_all sorts by priority.
	 */
	public function test_get_all_sorts_by_priority(): void {
		$this->registry->register( 'module-a', [ 'name' => 'Module A', 'priority' => 20 ] );
		$this->registry->register( 'module-b', [ 'name' => 'Module B', 'priority' => 10 ] );
		$this->registry->register( 'module-c', [ 'name' => 'Module C', 'priority' => 15 ] );

		$modules = $this->registry->get_all( [ 'orderby' => 'priority', 'order' => 'ASC' ] );
		$slugs   = array_keys( $modules );

		$this->assertSame( [ 'module-b', 'module-c', 'module-a' ], $slugs );
	}

	/**
	 * Test get_all sorts by name.
	 */
	public function test_get_all_sorts_by_name(): void {
		$this->registry->register( 'module-c', [ 'name' => 'Zebra Module' ] );
		$this->registry->register( 'module-a', [ 'name' => 'Alpha Module' ] );
		$this->registry->register( 'module-b', [ 'name' => 'Beta Module' ] );

		$modules = $this->registry->get_all( [ 'orderby' => 'name', 'order' => 'ASC' ] );
		$names   = array_column( $modules, 'name' );

		$this->assertSame( [ 'Alpha Module', 'Beta Module', 'Zebra Module' ], $names );
	}

	/**
	 * Test get_all sorts descending.
	 */
	public function test_get_all_sorts_descending(): void {
		$this->registry->register( 'module-a', [ 'name' => 'Module A', 'priority' => 20 ] );
		$this->registry->register( 'module-b', [ 'name' => 'Module B', 'priority' => 10 ] );

		$modules = $this->registry->get_all( [ 'orderby' => 'priority', 'order' => 'DESC' ] );
		$slugs   = array_keys( $modules );

		$this->assertSame( [ 'module-a', 'module-b' ], $slugs );
	}

	/**
	 * Test get_all filters by feature.
	 */
	public function test_get_all_filters_by_feature(): void {
		$this->registry->register( 'module-a', [
			'name'     => 'Module A',
			'features' => [ 'feature1', 'feature2' ],
		] );
		$this->registry->register( 'module-b', [
			'name'     => 'Module B',
			'features' => [ 'feature2' ],
		] );
		$this->registry->register( 'module-c', [
			'name'     => 'Module C',
			'features' => [ 'feature3' ],
		] );

		$modules = $this->registry->get_all( [ 'feature' => 'feature2' ] );

		$this->assertCount( 2, $modules );
		$this->assertArrayHasKey( 'module-a', $modules );
		$this->assertArrayHasKey( 'module-b', $modules );
	}

	/**
	 * Test get_by_feature returns correct modules.
	 */
	public function test_get_by_feature_returns_correct_modules(): void {
		$this->registry->register( 'module-a', [
			'name'     => 'Module A',
			'features' => [ 'link_checker' ],
		] );
		$this->registry->register( 'module-b', [
			'name'     => 'Module B',
			'features' => [ 'other_feature' ],
		] );

		$modules = $this->registry->get_by_feature( 'link_checker' );

		$this->assertCount( 1, $modules );
		$this->assertArrayHasKey( 'module-a', $modules );
	}

	/**
	 * Test has returns correct values.
	 */
	public function test_has_returns_correct_values(): void {
		$this->assertFalse( $this->registry->has( 'test-module' ) );

		$this->registry->register( 'test-module', [ 'name' => 'Test' ] );

		$this->assertTrue( $this->registry->has( 'test-module' ) );
		$this->assertFalse( $this->registry->has( 'other-module' ) );
	}

	/**
	 * Test count returns correct value.
	 */
	public function test_count_returns_correct_value(): void {
		$this->assertSame( 0, $this->registry->count() );

		$this->registry->register( 'module-1', [ 'name' => 'Module 1' ] );
		$this->assertSame( 1, $this->registry->count() );

		$this->registry->register( 'module-2', [ 'name' => 'Module 2' ] );
		$this->assertSame( 2, $this->registry->count() );

		$this->registry->unregister( 'module-1' );
		$this->assertSame( 1, $this->registry->count() );
	}

	/**
	 * Test check_requirements passes for core version.
	 */
	public function test_check_requirements_passes_for_core_version(): void {
		$unmet = $this->registry->check_requirements( [ 'core' => '1.0.0' ] );

		$this->assertSame( [], $unmet );
	}

	/**
	 * Test check_requirements fails for higher core version.
	 */
	public function test_check_requirements_fails_for_higher_core_version(): void {
		$unmet = $this->registry->check_requirements( [ 'core' => '2.0.0' ] );

		$this->assertArrayHasKey( 'core', $unmet );
	}

	/**
	 * Test check_requirements fails for missing module.
	 */
	public function test_check_requirements_fails_for_missing_module(): void {
		$unmet = $this->registry->check_requirements( [ 'other-module' => '1.0.0' ] );

		$this->assertArrayHasKey( 'other-module', $unmet );
		$this->assertStringContainsString( 'not installed', $unmet['other-module'] );
	}

	/**
	 * Test check_requirements fails for low module version.
	 */
	public function test_check_requirements_fails_for_low_module_version(): void {
		$this->registry->register( 'base-module', [
			'name'    => 'Base Module',
			'version' => '1.0.0',
		] );

		$unmet = $this->registry->check_requirements( [ 'base-module' => '2.0.0' ] );

		$this->assertArrayHasKey( 'base-module', $unmet );
		$this->assertStringContainsString( '2.0.0', $unmet['base-module'] );
	}

	/**
	 * Test check_requirements passes for installed module.
	 */
	public function test_check_requirements_passes_for_installed_module(): void {
		$this->registry->register( 'base-module', [
			'name'    => 'Base Module',
			'version' => '2.0.0',
		] );

		$unmet = $this->registry->check_requirements( [ 'base-module' => '1.5.0' ] );

		$this->assertSame( [], $unmet );
	}

	/**
	 * Test is_initialized returns correct state.
	 */
	public function test_is_initialized_returns_correct_state(): void {
		$this->assertFalse( $this->registry->is_initialized() );

		$this->registry->init();

		$this->assertTrue( $this->registry->is_initialized() );
	}

	/**
	 * Test init only runs once.
	 */
	public function test_init_only_runs_once(): void {
		$this->assertFalse( $this->registry->is_initialized() );

		$this->registry->init();

		$this->assertTrue( $this->registry->is_initialized() );

		// Second call should not change state.
		$this->registry->init();

		$this->assertTrue( $this->registry->is_initialized() );
	}

	/**
	 * Test slug is sanitized.
	 */
	public function test_slug_is_sanitized(): void {
		$this->registry->register( 'Test-Module_123', [
			'name' => 'Test Module',
		] );

		$this->assertTrue( $this->registry->has( 'test-module_123' ) );
	}

	/**
	 * Test priority is converted to integer.
	 */
	public function test_priority_is_converted_to_integer(): void {
		$this->registry->register( 'test-module', [
			'name'     => 'Test Module',
			'priority' => '15',
		] );

		$module = $this->registry->get( 'test-module' );
		$this->assertSame( 15, $module['priority'] );
	}

	/**
	 * Test requires is always an array.
	 */
	public function test_requires_is_always_array(): void {
		$this->registry->register( 'test-module', [
			'name'     => 'Test Module',
			'requires' => 'invalid',
		] );

		$module = $this->registry->get( 'test-module' );
		$this->assertSame( [], $module['requires'] );
	}

	/**
	 * Test features is always an array.
	 */
	public function test_features_is_always_array(): void {
		$this->registry->register( 'test-module', [
			'name'     => 'Test Module',
			'features' => 'invalid',
		] );

		$module = $this->registry->get( 'test-module' );
		$this->assertSame( [], $module['features'] );
	}
}
