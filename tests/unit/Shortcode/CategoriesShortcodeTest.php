<?php
/**
 * CategoriesShortcode Unit Tests.
 *
 * @package APD\Tests\Unit\Shortcode
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Shortcode;

use APD\Shortcode\CategoriesShortcode;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Test class for CategoriesShortcode.
 */
final class CategoriesShortcodeTest extends UnitTestCase {

	/**
	 * CategoriesShortcode instance.
	 *
	 * @var CategoriesShortcode
	 */
	private CategoriesShortcode $shortcode;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->shortcode = new CategoriesShortcode();
	}

	/**
	 * Test shortcode tag is correct.
	 */
	public function test_tag_is_apd_categories(): void {
		$this->assertSame( 'apd_categories', $this->shortcode->get_tag() );
	}

	/**
	 * Test has correct default attributes.
	 */
	public function test_has_correct_defaults(): void {
		$defaults = $this->shortcode->get_defaults();

		$this->assertSame( 'grid', $defaults['layout'] );
		$this->assertSame( 4, $defaults['columns'] );
		$this->assertSame( 0, $defaults['count'] );
		$this->assertSame( 'true', $defaults['hide_empty'] );
		$this->assertSame( 'name', $defaults['orderby'] );
		$this->assertSame( 'ASC', $defaults['order'] );
		$this->assertSame( 'true', $defaults['show_count'] );
		$this->assertSame( 'true', $defaults['show_icon'] );
	}

	/**
	 * Test attribute documentation exists.
	 */
	public function test_attribute_docs_exist(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertArrayHasKey( 'layout', $docs );
		$this->assertArrayHasKey( 'columns', $docs );
		$this->assertArrayHasKey( 'count', $docs );
		$this->assertArrayHasKey( 'parent', $docs );
		$this->assertArrayHasKey( 'include', $docs );
		$this->assertArrayHasKey( 'exclude', $docs );
		$this->assertArrayHasKey( 'hide_empty', $docs );
		$this->assertArrayHasKey( 'orderby', $docs );
		$this->assertArrayHasKey( 'show_count', $docs );
		$this->assertArrayHasKey( 'show_icon', $docs );
	}

	/**
	 * Test example is valid.
	 */
	public function test_example_is_valid(): void {
		$example = $this->shortcode->get_example();

		$this->assertStringContainsString( '[apd_categories', $example );
		$this->assertStringContainsString( 'layout=', $example );
	}

	/**
	 * Test description is set.
	 */
	public function test_description_is_set(): void {
		$this->assertNotEmpty( $this->shortcode->get_description() );
	}

	/**
	 * Test layout attribute is slug type.
	 */
	public function test_layout_is_slug_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'slug', $docs['layout']['type'] );
	}

	/**
	 * Test columns attribute is integer type.
	 */
	public function test_columns_is_integer(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'integer', $docs['columns']['type'] );
	}

	/**
	 * Test include attribute is ids type.
	 */
	public function test_include_is_ids_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'ids', $docs['include']['type'] );
	}

	/**
	 * Test exclude attribute is ids type.
	 */
	public function test_exclude_is_ids_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'ids', $docs['exclude']['type'] );
	}

	/**
	 * Test hide_empty attribute is boolean type.
	 */
	public function test_hide_empty_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['hide_empty']['type'] );
	}

	/**
	 * Test show_count attribute is boolean type.
	 */
	public function test_show_count_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_count']['type'] );
	}

	/**
	 * Test show_icon attribute is boolean type.
	 */
	public function test_show_icon_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_icon']['type'] );
	}

	/**
	 * Test show_description attribute is boolean type.
	 */
	public function test_show_description_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_description']['type'] );
	}

	/**
	 * Test orderby attribute is slug type.
	 */
	public function test_orderby_is_slug_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'slug', $docs['orderby']['type'] );
	}

	/**
	 * Test parent attribute is integer type.
	 */
	public function test_parent_is_integer(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'integer', $docs['parent']['type'] );
	}
}
