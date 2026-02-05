<?php
/**
 * Categories Block Unit Tests.
 *
 * @package APD\Tests\Unit\Blocks
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Blocks;

use APD\Blocks\CategoriesBlock;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test class for CategoriesBlock.
 *
 * @covers \APD\Blocks\CategoriesBlock
 */
class CategoriesBlockTest extends TestCase {

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();

		// Mock the __() translation function.
		Functions\stubs( [
			'__' => function( $text, $domain = 'default' ) {
				return $text;
			},
		] );
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
	 * Test that the block has the correct name.
	 */
	public function test_block_has_correct_name(): void {
		$block = new CategoriesBlock();

		$this->assertSame( 'categories', $block->get_name() );
	}

	/**
	 * Test that the block has the correct full name.
	 */
	public function test_block_has_correct_full_name(): void {
		$block = new CategoriesBlock();

		$this->assertSame( 'apd/categories', $block->get_full_name() );
	}

	/**
	 * Test that the block has a title.
	 */
	public function test_block_has_title(): void {
		$block = new CategoriesBlock();

		$this->assertSame( 'Listing Categories', $block->get_title() );
	}

	/**
	 * Test that the block has a description.
	 */
	public function test_block_has_description(): void {
		$block = new CategoriesBlock();

		$this->assertNotEmpty( $block->get_description() );
	}

	/**
	 * Test that the block has expected attributes.
	 */
	public function test_block_has_expected_attributes(): void {
		$block = new CategoriesBlock();
		$attributes = $block->get_attributes();

		// Check for key attributes.
		$expected_attrs = [
			'layout',
			'columns',
			'count',
			'parent',
			'include',
			'exclude',
			'hideEmpty',
			'orderby',
			'order',
			'showCount',
			'showIcon',
			'showDescription',
		];

		foreach ( $expected_attrs as $attr ) {
			$this->assertArrayHasKey( $attr, $attributes, "Attribute '$attr' should exist" );
		}
	}

	/**
	 * Test attribute default values.
	 */
	public function test_attribute_default_values(): void {
		$block = new CategoriesBlock();
		$attributes = $block->get_attributes();

		$this->assertSame( 'grid', $attributes['layout']['default'] );
		$this->assertSame( 4, $attributes['columns']['default'] );
		$this->assertSame( 0, $attributes['count']['default'] );
		$this->assertSame( '', $attributes['parent']['default'] );
		$this->assertSame( '', $attributes['include']['default'] );
		$this->assertSame( '', $attributes['exclude']['default'] );
		$this->assertTrue( $attributes['hideEmpty']['default'] );
		$this->assertSame( 'name', $attributes['orderby']['default'] );
		$this->assertSame( 'ASC', $attributes['order']['default'] );
		$this->assertTrue( $attributes['showCount']['default'] );
		$this->assertTrue( $attributes['showIcon']['default'] );
		$this->assertFalse( $attributes['showDescription']['default'] );
	}

	/**
	 * Test attribute types.
	 */
	public function test_attribute_types(): void {
		$block = new CategoriesBlock();
		$attributes = $block->get_attributes();

		$this->assertSame( 'string', $attributes['layout']['type'] );
		$this->assertSame( 'number', $attributes['columns']['type'] );
		$this->assertSame( 'number', $attributes['count']['type'] );
		$this->assertSame( 'string', $attributes['parent']['type'] );
		$this->assertSame( 'string', $attributes['include']['type'] );
		$this->assertSame( 'string', $attributes['exclude']['type'] );
		$this->assertSame( 'boolean', $attributes['hideEmpty']['type'] );
		$this->assertSame( 'string', $attributes['orderby']['type'] );
		$this->assertSame( 'string', $attributes['order']['type'] );
		$this->assertSame( 'boolean', $attributes['showCount']['type'] );
		$this->assertSame( 'boolean', $attributes['showIcon']['type'] );
		$this->assertSame( 'boolean', $attributes['showDescription']['type'] );
	}

	/**
	 * Test register method.
	 */
	public function test_register_calls_register_block_type(): void {
		// Mock WordPress functions.
		Functions\when( 'apply_filters' )->returnArg( 2 );

		Functions\expect( 'register_block_type' )
			->once()
			->with( 'apd/categories', Mockery::type( 'array' ) )
			->andReturn( true );

		$block = new CategoriesBlock();
		$block->register();

		// Verify the block was set up correctly before registration.
		$this->assertSame( 'apd/categories', $block->get_full_name() );
		$this->assertSame( 'Listing Categories', $block->get_title() );
	}

	/**
	 * Test block has category icon.
	 */
	public function test_block_has_correct_icon(): void {
		$block = new CategoriesBlock();

		// Use reflection to check the icon.
		$reflection = new \ReflectionClass( $block );
		$property = $reflection->getProperty( 'icon' );
		

		$this->assertSame( 'category', $property->getValue( $block ) );
	}

	/**
	 * Test block is in widgets category.
	 */
	public function test_block_is_in_widgets_category(): void {
		$block = new CategoriesBlock();

		// Use reflection to check the category.
		$reflection = new \ReflectionClass( $block );
		$property = $reflection->getProperty( 'category' );
		

		$this->assertSame( 'widgets', $property->getValue( $block ) );
	}

	/**
	 * Test block has keywords.
	 */
	public function test_block_has_keywords(): void {
		$block = new CategoriesBlock();

		// Use reflection to check keywords.
		$reflection = new \ReflectionClass( $block );
		$property = $reflection->getProperty( 'keywords' );
		

		$keywords = $property->getValue( $block );

		$this->assertIsArray( $keywords );
		$this->assertContains( 'categories', $keywords );
		$this->assertContains( 'taxonomy', $keywords );
		$this->assertContains( 'directory', $keywords );
		$this->assertContains( 'grid', $keywords );
	}

	/**
	 * Test block uses server-side rendering.
	 */
	public function test_block_uses_server_side_rendering(): void {
		$block = new CategoriesBlock();

		// Use reflection to check uses_ssr.
		$reflection = new \ReflectionClass( $block );
		$property = $reflection->getProperty( 'uses_ssr' );
		

		$this->assertTrue( $property->getValue( $block ) );
	}
}
