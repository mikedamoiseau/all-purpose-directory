<?php
/**
 * Abstract Block Unit Tests.
 *
 * @package APD\Tests\Unit\Blocks
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Blocks;

use APD\Blocks\AbstractBlock;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test class for AbstractBlock.
 *
 * @covers \APD\Blocks\AbstractBlock
 */
class AbstractBlockTest extends TestCase {

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();
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
	 * Create a concrete implementation of AbstractBlock for testing.
	 *
	 * @return AbstractBlock
	 */
	private function create_test_block(): AbstractBlock {
		return new class extends AbstractBlock {
			protected string $name = 'test-block';
			protected string $title = 'Test Block';
			protected string $description = 'A test block';
			protected string $category = 'widgets';
			protected string $icon = 'smiley';
			protected array $keywords = [ 'test', 'block' ];
			protected array $attributes = [
				'message' => [
					'type'    => 'string',
					'default' => 'Hello',
				],
				'count'   => [
					'type'    => 'number',
					'default' => 5,
				],
			];

			protected function output( array $attributes, string $content, \WP_Block $block ): string {
				return sprintf(
					'<div>%s (%d)</div>',
					$attributes['message'],
					$attributes['count']
				);
			}
		};
	}

	/**
	 * Test get_name returns the block name.
	 */
	public function test_get_name_returns_block_name(): void {
		$block = $this->create_test_block();

		$this->assertSame( 'test-block', $block->get_name() );
	}

	/**
	 * Test get_full_name returns namespaced name.
	 */
	public function test_get_full_name_returns_namespaced_name(): void {
		$block = $this->create_test_block();

		$this->assertSame( 'apd/test-block', $block->get_full_name() );
	}

	/**
	 * Test get_title returns the block title.
	 */
	public function test_get_title_returns_block_title(): void {
		$block = $this->create_test_block();

		$this->assertSame( 'Test Block', $block->get_title() );
	}

	/**
	 * Test get_description returns the block description.
	 */
	public function test_get_description_returns_block_description(): void {
		$block = $this->create_test_block();

		$this->assertSame( 'A test block', $block->get_description() );
	}

	/**
	 * Test get_attributes returns the block attributes.
	 */
	public function test_get_attributes_returns_block_attributes(): void {
		$block = $this->create_test_block();
		$attributes = $block->get_attributes();

		$this->assertIsArray( $attributes );
		$this->assertArrayHasKey( 'message', $attributes );
		$this->assertArrayHasKey( 'count', $attributes );
		$this->assertSame( 'string', $attributes['message']['type'] );
		$this->assertSame( 'Hello', $attributes['message']['default'] );
	}

	/**
	 * Test register method calls register_block_type.
	 */
	public function test_register_calls_register_block_type(): void {
		// Mock WordPress functions.
		Functions\when( 'apply_filters' )->returnArg( 2 );

		Functions\expect( 'register_block_type' )
			->once()
			->with( 'apd/test-block', Mockery::type( 'array' ) )
			->andReturn( true );

		$block = $this->create_test_block();
		$block->register();

		// Verify the block was set up correctly before registration.
		$this->assertSame( 'apd/test-block', $block->get_full_name() );
		$this->assertSame( 'Test Block', $block->get_title() );
	}

	/**
	 * Test get_supports returns default support configuration.
	 */
	public function test_get_supports_returns_defaults(): void {
		$block = $this->create_test_block();

		// Use reflection to access protected method.
		$reflection = new \ReflectionClass( $block );
		$method = $reflection->getMethod( 'get_supports' );

		$supports = $method->invoke( $block );

		$this->assertArrayHasKey( 'html', $supports );
		$this->assertFalse( $supports['html'] );
		$this->assertArrayHasKey( 'align', $supports );
		$this->assertContains( 'wide', $supports['align'] );
		$this->assertContains( 'full', $supports['align'] );
		$this->assertArrayHasKey( 'anchor', $supports );
		$this->assertTrue( $supports['anchor'] );
	}

	/**
	 * Test block attributes have correct structure.
	 */
	public function test_attributes_have_correct_structure(): void {
		$block = $this->create_test_block();
		$attributes = $block->get_attributes();

		foreach ( $attributes as $name => $config ) {
			$this->assertArrayHasKey( 'type', $config, "Attribute '$name' should have a type" );
			$this->assertArrayHasKey( 'default', $config, "Attribute '$name' should have a default" );
		}
	}

	/**
	 * Test block has proper namespace.
	 */
	public function test_block_has_proper_namespace(): void {
		$block = $this->create_test_block();

		$this->assertStringStartsWith( 'apd/', $block->get_full_name() );
	}

	/**
	 * Test uses_ssr property exists and is boolean.
	 */
	public function test_uses_ssr_is_boolean(): void {
		$block = $this->create_test_block();

		// Use reflection to check uses_ssr.
		$reflection = new \ReflectionClass( $block );
		$property = $reflection->getProperty( 'uses_ssr' );

		$value = $property->getValue( $block );

		$this->assertIsBool( $value );
	}

	/**
	 * Test block icon property exists.
	 */
	public function test_block_has_icon(): void {
		$block = $this->create_test_block();

		// Use reflection to check icon.
		$reflection = new \ReflectionClass( $block );
		$property = $reflection->getProperty( 'icon' );

		$icon = $property->getValue( $block );

		$this->assertNotEmpty( $icon );
		$this->assertSame( 'smiley', $icon );
	}

	/**
	 * Test block category property exists.
	 */
	public function test_block_has_category(): void {
		$block = $this->create_test_block();

		// Use reflection to check category.
		$reflection = new \ReflectionClass( $block );
		$property = $reflection->getProperty( 'category' );

		$category = $property->getValue( $block );

		$this->assertNotEmpty( $category );
		$this->assertSame( 'widgets', $category );
	}

	/**
	 * Test block keywords property exists.
	 */
	public function test_block_has_keywords(): void {
		$block = $this->create_test_block();

		// Use reflection to check keywords.
		$reflection = new \ReflectionClass( $block );
		$property = $reflection->getProperty( 'keywords' );

		$keywords = $property->getValue( $block );

		$this->assertIsArray( $keywords );
		$this->assertContains( 'test', $keywords );
		$this->assertContains( 'block', $keywords );
	}
}
