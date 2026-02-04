<?php
/**
 * ShortcodeManager Unit Tests.
 *
 * @package APD\Tests\Unit\Shortcode
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Shortcode;

use APD\Shortcode\ShortcodeManager;
use APD\Shortcode\AbstractShortcode;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for ShortcodeManager.
 */
final class ShortcodeManagerTest extends UnitTestCase {

	/**
	 * Reset singleton between tests.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset singleton using reflection.
		$reflection = new \ReflectionClass( ShortcodeManager::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setValue( null, null );

		// Also reset initialized flag.
		$initialized = $reflection->getProperty( 'initialized' );
		// We'll need to access after getting instance.
	}

	/**
	 * Test get_instance returns singleton.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = ShortcodeManager::get_instance();
		$instance2 = ShortcodeManager::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test get_instance returns ShortcodeManager.
	 */
	public function test_get_instance_returns_shortcode_manager(): void {
		$manager = ShortcodeManager::get_instance();

		$this->assertInstanceOf( ShortcodeManager::class, $manager );
	}

	/**
	 * Test register adds shortcode.
	 */
	public function test_register_adds_shortcode(): void {
		Functions\when( 'add_shortcode' )->justReturn( null );

		$manager   = ShortcodeManager::get_instance();
		$shortcode = $this->createMockShortcode( 'test_shortcode' );

		$result = $manager->register( $shortcode );

		$this->assertTrue( $result );
		$this->assertTrue( $manager->has( 'test_shortcode' ) );
	}

	/**
	 * Test register returns false for empty tag.
	 */
	public function test_register_returns_false_for_empty_tag(): void {
		$manager   = ShortcodeManager::get_instance();
		$shortcode = $this->createMockShortcode( '' );

		$result = $manager->register( $shortcode );

		$this->assertFalse( $result );
	}

	/**
	 * Test unregister removes shortcode.
	 */
	public function test_unregister_removes_shortcode(): void {
		Functions\when( 'add_shortcode' )->justReturn( null );
		Functions\when( 'remove_shortcode' )->justReturn( null );

		$manager   = ShortcodeManager::get_instance();
		$shortcode = $this->createMockShortcode( 'test_shortcode' );
		$manager->register( $shortcode );

		$result = $manager->unregister( 'test_shortcode' );

		$this->assertTrue( $result );
		$this->assertFalse( $manager->has( 'test_shortcode' ) );
	}

	/**
	 * Test unregister returns false for non-existent shortcode.
	 */
	public function test_unregister_returns_false_for_nonexistent(): void {
		$manager = ShortcodeManager::get_instance();

		$result = $manager->unregister( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test get returns registered shortcode.
	 */
	public function test_get_returns_registered_shortcode(): void {
		Functions\when( 'add_shortcode' )->justReturn( null );

		$manager   = ShortcodeManager::get_instance();
		$shortcode = $this->createMockShortcode( 'test_shortcode' );
		$manager->register( $shortcode );

		$retrieved = $manager->get( 'test_shortcode' );

		$this->assertSame( $shortcode, $retrieved );
	}

	/**
	 * Test get returns null for non-existent shortcode.
	 */
	public function test_get_returns_null_for_nonexistent(): void {
		$manager = ShortcodeManager::get_instance();

		$result = $manager->get( 'nonexistent' );

		$this->assertNull( $result );
	}

	/**
	 * Test has returns true for registered shortcode.
	 */
	public function test_has_returns_true_for_registered(): void {
		Functions\when( 'add_shortcode' )->justReturn( null );

		$manager   = ShortcodeManager::get_instance();
		$shortcode = $this->createMockShortcode( 'test_shortcode' );
		$manager->register( $shortcode );

		$this->assertTrue( $manager->has( 'test_shortcode' ) );
	}

	/**
	 * Test has returns false for non-existent shortcode.
	 */
	public function test_has_returns_false_for_nonexistent(): void {
		$manager = ShortcodeManager::get_instance();

		$this->assertFalse( $manager->has( 'nonexistent' ) );
	}

	/**
	 * Test get_all returns all registered shortcodes.
	 */
	public function test_get_all_returns_all_shortcodes(): void {
		Functions\when( 'add_shortcode' )->justReturn( null );

		$manager    = ShortcodeManager::get_instance();
		$shortcode1 = $this->createMockShortcode( 'shortcode_1' );
		$shortcode2 = $this->createMockShortcode( 'shortcode_2' );

		$manager->register( $shortcode1 );
		$manager->register( $shortcode2 );

		$all = $manager->get_all();

		$this->assertCount( 2, $all );
		$this->assertArrayHasKey( 'shortcode_1', $all );
		$this->assertArrayHasKey( 'shortcode_2', $all );
	}

	/**
	 * Test get_documentation returns docs for all shortcodes.
	 */
	public function test_get_documentation_returns_docs(): void {
		Functions\when( 'add_shortcode' )->justReturn( null );

		$manager   = ShortcodeManager::get_instance();
		$shortcode = $this->createMockShortcode( 'test_shortcode', 'Test description' );
		$manager->register( $shortcode );

		$docs = $manager->get_documentation();

		$this->assertArrayHasKey( 'test_shortcode', $docs );
		$this->assertSame( 'test_shortcode', $docs['test_shortcode']['tag'] );
		$this->assertSame( 'Test description', $docs['test_shortcode']['description'] );
	}

	/**
	 * Create a mock shortcode for testing.
	 *
	 * @param string $tag         Shortcode tag.
	 * @param string $description Shortcode description.
	 * @return AbstractShortcode Mock shortcode.
	 */
	private function createMockShortcode( string $tag, string $description = '' ): AbstractShortcode {
		$shortcode = Mockery::mock( AbstractShortcode::class );
		$shortcode->shouldReceive( 'get_tag' )->andReturn( $tag );
		$shortcode->shouldReceive( 'get_description' )->andReturn( $description );
		$shortcode->shouldReceive( 'get_attribute_docs' )->andReturn( [] );
		$shortcode->shouldReceive( 'get_example' )->andReturn( '[' . $tag . ']' );
		$shortcode->shouldReceive( 'render' )->andReturn( '' );

		return $shortcode;
	}
}
