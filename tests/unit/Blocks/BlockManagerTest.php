<?php
/**
 * Block Manager Unit Tests.
 *
 * @package APD\Tests\Unit\Blocks
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Blocks;

use APD\Blocks\BlockManager;
use APD\Blocks\AbstractBlock;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test class for BlockManager.
 *
 * @covers \APD\Blocks\BlockManager
 */
class BlockManagerTest extends TestCase {

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();

		// Reset the singleton for each test.
		$reflection = new \ReflectionClass( BlockManager::class );
		$instance = $reflection->getProperty( 'instance' );
		$instance->setValue( null, null );
	}

	/**
	 * Tear down the test environment.
	 */
	protected function tearDown(): void {
		\Brain\Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test that get_instance returns the same instance.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = BlockManager::get_instance();
		$instance2 = BlockManager::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test that get_instance returns a BlockManager.
	 */
	public function test_get_instance_returns_block_manager(): void {
		$instance = BlockManager::get_instance();

		$this->assertInstanceOf( BlockManager::class, $instance );
	}

	/**
	 * Test register method adds block to registry.
	 */
	public function test_register_adds_block(): void {
		// Mock the do_action function.
		Functions\expect( 'do_action' )
			->once()
			->with( 'apd_block_registered', Mockery::type( AbstractBlock::class ), 'test-block' );

		// Create a mock block.
		$block = Mockery::mock( AbstractBlock::class );
		$block->shouldReceive( 'get_name' )->andReturn( 'test-block' );
		$block->shouldReceive( 'register' )->once();

		$manager = BlockManager::get_instance();
		$result = $manager->register( $block );

		$this->assertTrue( $result );
		$this->assertTrue( $manager->has( 'test-block' ) );
	}

	/**
	 * Test register returns false for empty name.
	 */
	public function test_register_returns_false_for_empty_name(): void {
		$block = Mockery::mock( AbstractBlock::class );
		$block->shouldReceive( 'get_name' )->andReturn( '' );

		$manager = BlockManager::get_instance();
		$result = $manager->register( $block );

		$this->assertFalse( $result );
	}

	/**
	 * Test unregister method removes block from registry.
	 */
	public function test_unregister_removes_block(): void {
		// Mock the functions.
		Functions\expect( 'do_action' )->twice();
		Functions\expect( 'unregister_block_type' )
			->once()
			->with( 'apd/test-block' )
			->andReturn( true );

		// Create and register a mock block.
		$block = Mockery::mock( AbstractBlock::class );
		$block->shouldReceive( 'get_name' )->andReturn( 'test-block' );
		$block->shouldReceive( 'register' )->once();

		$manager = BlockManager::get_instance();
		$manager->register( $block );

		$this->assertTrue( $manager->has( 'test-block' ) );

		$result = $manager->unregister( 'test-block' );

		$this->assertTrue( $result );
		$this->assertFalse( $manager->has( 'test-block' ) );
	}

	/**
	 * Test unregister returns false for non-existent block.
	 */
	public function test_unregister_returns_false_for_nonexistent_block(): void {
		$manager = BlockManager::get_instance();
		$result = $manager->unregister( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test get method returns registered block.
	 */
	public function test_get_returns_registered_block(): void {
		// Mock the do_action function.
		Functions\expect( 'do_action' )->once();

		// Create and register a mock block.
		$block = Mockery::mock( AbstractBlock::class );
		$block->shouldReceive( 'get_name' )->andReturn( 'test-block' );
		$block->shouldReceive( 'register' )->once();

		$manager = BlockManager::get_instance();
		$manager->register( $block );

		$result = $manager->get( 'test-block' );

		$this->assertSame( $block, $result );
	}

	/**
	 * Test get returns null for non-existent block.
	 */
	public function test_get_returns_null_for_nonexistent_block(): void {
		$manager = BlockManager::get_instance();
		$result = $manager->get( 'nonexistent' );

		$this->assertNull( $result );
	}

	/**
	 * Test has method returns correct values.
	 */
	public function test_has_returns_correct_values(): void {
		// Mock the do_action function.
		Functions\expect( 'do_action' )->once();

		// Create and register a mock block.
		$block = Mockery::mock( AbstractBlock::class );
		$block->shouldReceive( 'get_name' )->andReturn( 'test-block' );
		$block->shouldReceive( 'register' )->once();

		$manager = BlockManager::get_instance();

		$this->assertFalse( $manager->has( 'test-block' ) );

		$manager->register( $block );

		$this->assertTrue( $manager->has( 'test-block' ) );
		$this->assertFalse( $manager->has( 'other-block' ) );
	}

	/**
	 * Test get_all returns all registered blocks.
	 */
	public function test_get_all_returns_all_blocks(): void {
		// Mock the do_action function.
		Functions\expect( 'do_action' )->twice();

		// Create and register mock blocks.
		$block1 = Mockery::mock( AbstractBlock::class );
		$block1->shouldReceive( 'get_name' )->andReturn( 'block-1' );
		$block1->shouldReceive( 'register' )->once();

		$block2 = Mockery::mock( AbstractBlock::class );
		$block2->shouldReceive( 'get_name' )->andReturn( 'block-2' );
		$block2->shouldReceive( 'register' )->once();

		$manager = BlockManager::get_instance();
		$manager->register( $block1 );
		$manager->register( $block2 );

		$result = $manager->get_all();

		$this->assertCount( 2, $result );
		$this->assertArrayHasKey( 'block-1', $result );
		$this->assertArrayHasKey( 'block-2', $result );
	}

	/**
	 * Test get_all returns empty array when no blocks registered.
	 */
	public function test_get_all_returns_empty_array_when_no_blocks(): void {
		$manager = BlockManager::get_instance();
		$result = $manager->get_all();

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test register_block_category adds the plugin category.
	 */
	public function test_register_block_category_adds_plugin_category(): void {
		Functions\expect( '__' )
			->once()
			->with( 'All Purpose Directory', 'all-purpose-directory' )
			->andReturn( 'All Purpose Directory' );

		$manager    = BlockManager::get_instance();
		$categories = [
			[
				'slug'  => 'text',
				'title' => 'Text',
			],
		];

		$result = $manager->register_block_category( $categories );

		$this->assertCount( 2, $result );
		$this->assertSame( 'all-purpose-directory', $result[0]['slug'] );
		$this->assertSame( 'All Purpose Directory', $result[0]['title'] );
		$this->assertSame( 'text', $result[1]['slug'] );
	}
}
