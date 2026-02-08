<?php
/**
 * Listings Block Unit Tests.
 *
 * @package APD\Tests\Unit\Blocks
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Blocks;

use APD\Blocks\ListingsBlock;
use APD\Frontend\Display\ViewRegistry;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test class for ListingsBlock.
 *
 * @covers \APD\Blocks\ListingsBlock
 */
class ListingsBlockTest extends TestCase {

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();

		// Mock the __() translation function.
		Functions\stubs( [
			'__'  => function( $text, $domain = 'default' ) {
				return $text;
			},
			'_n'  => function( $single, $plural, $number, $domain = 'default' ) {
				return $number === 1 ? $single : $plural;
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
		$block = new ListingsBlock();

		$this->assertSame( 'listings', $block->get_name() );
	}

	/**
	 * Test that the block has the correct full name.
	 */
	public function test_block_has_correct_full_name(): void {
		$block = new ListingsBlock();

		$this->assertSame( 'apd/listings', $block->get_full_name() );
	}

	/**
	 * Test that the block has a title.
	 */
	public function test_block_has_title(): void {
		$block = new ListingsBlock();

		$this->assertSame( 'Listings', $block->get_title() );
	}

	/**
	 * Test that the block has a description.
	 */
	public function test_block_has_description(): void {
		$block = new ListingsBlock();

		$this->assertNotEmpty( $block->get_description() );
	}

	/**
	 * Test that the block has expected attributes.
	 */
	public function test_block_has_expected_attributes(): void {
		$block = new ListingsBlock();
		$attributes = $block->get_attributes();

		// Check for key attributes.
		$expected_attrs = [
			'view',
			'columns',
			'count',
			'category',
			'tag',
			'orderby',
			'order',
			'ids',
			'exclude',
			'showImage',
			'showExcerpt',
			'excerptLength',
			'showCategory',
			'showPagination',
		];

		foreach ( $expected_attrs as $attr ) {
			$this->assertArrayHasKey( $attr, $attributes, "Attribute '$attr' should exist" );
		}
	}

	/**
	 * Test attribute default values.
	 */
	public function test_attribute_default_values(): void {
		$block = new ListingsBlock();
		$attributes = $block->get_attributes();

		$this->assertSame( 'grid', $attributes['view']['default'] );
		$this->assertSame( 3, $attributes['columns']['default'] );
		$this->assertSame( 12, $attributes['count']['default'] );
		$this->assertSame( 'date', $attributes['orderby']['default'] );
		$this->assertSame( 'DESC', $attributes['order']['default'] );
		$this->assertTrue( $attributes['showImage']['default'] );
		$this->assertTrue( $attributes['showExcerpt']['default'] );
		$this->assertTrue( $attributes['showCategory']['default'] );
		$this->assertTrue( $attributes['showPagination']['default'] );
	}

	/**
	 * Test attribute types.
	 */
	public function test_attribute_types(): void {
		$block = new ListingsBlock();
		$attributes = $block->get_attributes();

		$this->assertSame( 'string', $attributes['view']['type'] );
		$this->assertSame( 'number', $attributes['columns']['type'] );
		$this->assertSame( 'number', $attributes['count']['type'] );
		$this->assertSame( 'string', $attributes['category']['type'] );
		$this->assertSame( 'string', $attributes['tag']['type'] );
		$this->assertSame( 'string', $attributes['orderby']['type'] );
		$this->assertSame( 'string', $attributes['order']['type'] );
		$this->assertSame( 'boolean', $attributes['showImage']['type'] );
		$this->assertSame( 'boolean', $attributes['showExcerpt']['type'] );
		$this->assertSame( 'number', $attributes['excerptLength']['type'] );
		$this->assertSame( 'boolean', $attributes['showCategory']['type'] );
		$this->assertSame( 'boolean', $attributes['showPagination']['type'] );
	}

	/**
	 * Test register method.
	 */
	public function test_register_calls_register_block_type(): void {
		// Mock WordPress functions.
		Functions\when( 'apply_filters' )->returnArg( 2 );

		Functions\expect( 'register_block_type' )
			->once()
			->with( 'apd/listings', Mockery::type( 'array' ) )
			->andReturn( true );

		$block = new ListingsBlock();
		$block->register();

		// Verify the block was set up correctly before registration.
		$this->assertSame( 'apd/listings', $block->get_full_name() );
		$this->assertSame( 'Listings', $block->get_title() );
	}

	/**
	 * Test block has grid-view icon.
	 */
	public function test_block_has_correct_icon(): void {
		$block = new ListingsBlock();

		// Use reflection to check the icon.
		$reflection = new \ReflectionClass( $block );
		$property = $reflection->getProperty( 'icon' );
		

		$this->assertSame( 'grid-view', $property->getValue( $block ) );
	}

	/**
	 * Test block is in plugin category.
	 */
	public function test_block_is_in_plugin_category(): void {
		$block = new ListingsBlock();

		// Use reflection to check the category.
		$reflection = new \ReflectionClass( $block );
		$property = $reflection->getProperty( 'category' );


		$this->assertSame( 'all-purpose-directory', $property->getValue( $block ) );
	}

	/**
	 * Test block has keywords.
	 */
	public function test_block_has_keywords(): void {
		$block = new ListingsBlock();

		// Use reflection to check keywords.
		$reflection = new \ReflectionClass( $block );
		$property = $reflection->getProperty( 'keywords' );
		

		$keywords = $property->getValue( $block );

		$this->assertIsArray( $keywords );
		$this->assertContains( 'listings', $keywords );
		$this->assertContains( 'directory', $keywords );
		$this->assertContains( 'grid', $keywords );
		$this->assertContains( 'list', $keywords );
	}

	/**
	 * Test block uses server-side rendering.
	 */
	public function test_block_uses_server_side_rendering(): void {
		$block = new ListingsBlock();

		// Use reflection to check uses_ssr.
		$reflection = new \ReflectionClass( $block );
		$property = $reflection->getProperty( 'uses_ssr' );
		

		$this->assertTrue( $property->getValue( $block ) );
	}
}
