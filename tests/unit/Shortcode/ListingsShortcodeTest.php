<?php
/**
 * ListingsShortcode Unit Tests.
 *
 * @package APD\Tests\Unit\Shortcode
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Shortcode;

use APD\Shortcode\ListingsShortcode;
use APD\Frontend\Display\ViewRegistry;
use APD\Frontend\Display\GridView;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for ListingsShortcode.
 */
final class ListingsShortcodeTest extends UnitTestCase {

	/**
	 * ListingsShortcode instance.
	 *
	 * @var ListingsShortcode
	 */
	private ListingsShortcode $shortcode;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->shortcode = new ListingsShortcode();

		// Reset ViewRegistry singleton.
		$reflection = new \ReflectionClass( ViewRegistry::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setValue( null, null );
	}

	/**
	 * Test shortcode tag is correct.
	 */
	public function test_tag_is_apd_listings(): void {
		$this->assertSame( 'apd_listings', $this->shortcode->get_tag() );
	}

	/**
	 * Test has correct default attributes.
	 */
	public function test_has_correct_defaults(): void {
		$defaults = $this->shortcode->get_defaults();

		$this->assertSame( 'grid', $defaults['view'] );
		$this->assertSame( 3, $defaults['columns'] );
		$this->assertSame( 12, $defaults['count'] );
		$this->assertSame( 'date', $defaults['orderby'] );
		$this->assertSame( 'DESC', $defaults['order'] );
		$this->assertSame( 'true', $defaults['show_image'] );
		$this->assertSame( 'true', $defaults['show_pagination'] );
	}

	/**
	 * Test attribute documentation exists.
	 */
	public function test_attribute_docs_exist(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertArrayHasKey( 'view', $docs );
		$this->assertArrayHasKey( 'columns', $docs );
		$this->assertArrayHasKey( 'count', $docs );
		$this->assertArrayHasKey( 'category', $docs );
		$this->assertArrayHasKey( 'orderby', $docs );
	}

	/**
	 * Test example is valid.
	 */
	public function test_example_is_valid(): void {
		$example = $this->shortcode->get_example();

		$this->assertStringContainsString( '[apd_listings', $example );
		$this->assertStringContainsString( 'view=', $example );
	}

	/**
	 * Test description is set.
	 */
	public function test_description_is_set(): void {
		$this->assertNotEmpty( $this->shortcode->get_description() );
	}

	/**
	 * Test count attribute validation.
	 */
	public function test_count_is_limited(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'integer', $docs['count']['type'] );
	}

	/**
	 * Test columns attribute validation.
	 */
	public function test_columns_is_integer(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'integer', $docs['columns']['type'] );
	}

	/**
	 * Test view attribute is slug type.
	 */
	public function test_view_is_slug_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'slug', $docs['view']['type'] );
	}

	/**
	 * Test orderby attribute is slug type.
	 */
	public function test_orderby_is_slug_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'slug', $docs['orderby']['type'] );
	}

	/**
	 * Test ids attribute is ids type.
	 */
	public function test_ids_is_ids_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'ids', $docs['ids']['type'] );
	}

	/**
	 * Test exclude attribute is ids type.
	 */
	public function test_exclude_is_ids_type(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'ids', $docs['exclude']['type'] );
	}

	/**
	 * Test show_image attribute is boolean type.
	 */
	public function test_show_image_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_image']['type'] );
	}

	/**
	 * Test show_pagination attribute is boolean type.
	 */
	public function test_show_pagination_is_boolean(): void {
		$docs = $this->shortcode->get_attribute_docs();

		$this->assertSame( 'boolean', $docs['show_pagination']['type'] );
	}
}
