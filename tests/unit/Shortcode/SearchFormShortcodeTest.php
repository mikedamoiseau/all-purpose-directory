<?php
/**
 * SearchFormShortcode Unit Tests.
 *
 * @package APD\Tests\Unit\Shortcode
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Shortcode;

use APD\Shortcode\SearchFormShortcode;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Test class for SearchFormShortcode.
 */
final class SearchFormShortcodeTest extends UnitTestCase {

	/**
	 * SearchFormShortcode instance.
	 *
	 * @var SearchFormShortcode
	 */
	private SearchFormShortcode $shortcode;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->shortcode = new SearchFormShortcode();
	}

	/**
	 * Test shortcode tag is correct.
	 */
	public function test_tag_is_apd_search_form(): void {
		$this->assertSame( 'apd_search_form', $this->shortcode->get_tag() );
	}

	/**
	 * Test has correct default attributes.
	 */
	public function test_has_correct_defaults(): void {
		$defaults = $this->shortcode->get_defaults();

		$this->assertSame( 'true', $defaults['show_keyword'] );
		$this->assertSame( 'true', $defaults['show_category'] );
		$this->assertSame( 'false', $defaults['show_tag'] );
		$this->assertSame( 'true', $defaults['show_submit'] );
		$this->assertSame( 'horizontal', $defaults['layout'] );
		$this->assertSame( 'false', $defaults['show_active'] );
	}

	/**
	 * Test attribute documentation exists.
	 */
	public function test_attribute_docs_exist(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertArrayHasKey( 'filters', $docs );
		$this->assertArrayHasKey( 'show_keyword', $docs );
		$this->assertArrayHasKey( 'show_category', $docs );
		$this->assertArrayHasKey( 'show_tag', $docs );
		$this->assertArrayHasKey( 'show_submit', $docs );
		$this->assertArrayHasKey( 'layout', $docs );
		$this->assertArrayHasKey( 'action', $docs );
	}

	/**
	 * Test example is valid.
	 */
	public function test_example_is_valid(): void {
		$example = $this->shortcode->get_example();

		$this->assertStringContainsString( '[apd_search_form', $example );
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
	 * Test show_keyword attribute is boolean type.
	 */
	public function test_show_keyword_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_keyword']['type'] );
	}

	/**
	 * Test show_category attribute is boolean type.
	 */
	public function test_show_category_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_category']['type'] );
	}

	/**
	 * Test show_submit attribute is boolean type.
	 */
	public function test_show_submit_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_submit']['type'] );
	}

	/**
	 * Test action attribute is string type.
	 */
	public function test_action_is_string_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'string', $docs['action']['type'] );
	}

	/**
	 * Test submit_text attribute is string type.
	 */
	public function test_submit_text_is_string(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'string', $docs['submit_text']['type'] );
	}
}
