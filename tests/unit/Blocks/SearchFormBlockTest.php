<?php
/**
 * Search Form Block Unit Tests.
 *
 * @package APD\Tests\Unit\Blocks
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Blocks;

use APD\Blocks\SearchFormBlock;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test class for SearchFormBlock.
 *
 * @covers \APD\Blocks\SearchFormBlock
 */
class SearchFormBlockTest extends TestCase {

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
		$block = new SearchFormBlock();

		$this->assertSame( 'search-form', $block->get_name() );
	}

	/**
	 * Test that the block has the correct full name.
	 */
	public function test_block_has_correct_full_name(): void {
		$block = new SearchFormBlock();

		$this->assertSame( 'apd/search-form', $block->get_full_name() );
	}

	/**
	 * Test that the block has a title.
	 */
	public function test_block_has_title(): void {
		$block = new SearchFormBlock();

		$this->assertSame( 'Listing Search Form', $block->get_title() );
	}

	/**
	 * Test that the block has a description.
	 */
	public function test_block_has_description(): void {
		$block = new SearchFormBlock();

		$this->assertNotEmpty( $block->get_description() );
	}

	/**
	 * Test that the block has expected attributes.
	 */
	public function test_block_has_expected_attributes(): void {
		$block = new SearchFormBlock();
		$attributes = $block->get_attributes();

		// Check for key attributes.
		$expected_attrs = [
			'filters',
			'showKeyword',
			'showCategory',
			'showTag',
			'showSubmit',
			'submitText',
			'action',
			'layout',
			'showActive',
		];

		foreach ( $expected_attrs as $attr ) {
			$this->assertArrayHasKey( $attr, $attributes, "Attribute '$attr' should exist" );
		}
	}

	/**
	 * Test attribute default values.
	 */
	public function test_attribute_default_values(): void {
		$block = new SearchFormBlock();
		$attributes = $block->get_attributes();

		$this->assertSame( '', $attributes['filters']['default'] );
		$this->assertTrue( $attributes['showKeyword']['default'] );
		$this->assertTrue( $attributes['showCategory']['default'] );
		$this->assertFalse( $attributes['showTag']['default'] );
		$this->assertTrue( $attributes['showSubmit']['default'] );
		$this->assertSame( '', $attributes['submitText']['default'] );
		$this->assertSame( '', $attributes['action']['default'] );
		$this->assertSame( 'horizontal', $attributes['layout']['default'] );
		$this->assertFalse( $attributes['showActive']['default'] );
	}

	/**
	 * Test attribute types.
	 */
	public function test_attribute_types(): void {
		$block = new SearchFormBlock();
		$attributes = $block->get_attributes();

		$this->assertSame( 'string', $attributes['filters']['type'] );
		$this->assertSame( 'boolean', $attributes['showKeyword']['type'] );
		$this->assertSame( 'boolean', $attributes['showCategory']['type'] );
		$this->assertSame( 'boolean', $attributes['showTag']['type'] );
		$this->assertSame( 'boolean', $attributes['showSubmit']['type'] );
		$this->assertSame( 'string', $attributes['submitText']['type'] );
		$this->assertSame( 'string', $attributes['action']['type'] );
		$this->assertSame( 'string', $attributes['layout']['type'] );
		$this->assertSame( 'boolean', $attributes['showActive']['type'] );
	}

	/**
	 * Test register method.
	 */
	public function test_register_calls_register_block_type(): void {
		// Mock WordPress functions.
		Functions\when( 'apply_filters' )->returnArg( 2 );

		Functions\expect( 'register_block_type' )
			->once()
			->with( 'apd/search-form', Mockery::type( 'array' ) )
			->andReturn( true );

		$block = new SearchFormBlock();
		$block->register();

		// Verify the block was set up correctly before registration.
		$this->assertSame( 'apd/search-form', $block->get_full_name() );
		$this->assertSame( 'Listing Search Form', $block->get_title() );
	}

	/**
	 * Test block has search icon.
	 */
	public function test_block_has_correct_icon(): void {
		$block = new SearchFormBlock();

		// Use reflection to check the icon.
		$reflection = new \ReflectionClass( $block );
		$property = $reflection->getProperty( 'icon' );
		$property->setAccessible( true );

		$this->assertSame( 'search', $property->getValue( $block ) );
	}

	/**
	 * Test block is in widgets category.
	 */
	public function test_block_is_in_widgets_category(): void {
		$block = new SearchFormBlock();

		// Use reflection to check the category.
		$reflection = new \ReflectionClass( $block );
		$property = $reflection->getProperty( 'category' );
		$property->setAccessible( true );

		$this->assertSame( 'widgets', $property->getValue( $block ) );
	}

	/**
	 * Test block has keywords.
	 */
	public function test_block_has_keywords(): void {
		$block = new SearchFormBlock();

		// Use reflection to check keywords.
		$reflection = new \ReflectionClass( $block );
		$property = $reflection->getProperty( 'keywords' );
		$property->setAccessible( true );

		$keywords = $property->getValue( $block );

		$this->assertIsArray( $keywords );
		$this->assertContains( 'search', $keywords );
		$this->assertContains( 'filter', $keywords );
		$this->assertContains( 'form', $keywords );
		$this->assertContains( 'listings', $keywords );
	}

	/**
	 * Test block uses server-side rendering.
	 */
	public function test_block_uses_server_side_rendering(): void {
		$block = new SearchFormBlock();

		// Use reflection to check uses_ssr.
		$reflection = new \ReflectionClass( $block );
		$property = $reflection->getProperty( 'uses_ssr' );
		$property->setAccessible( true );

		$this->assertTrue( $property->getValue( $block ) );
	}
}
