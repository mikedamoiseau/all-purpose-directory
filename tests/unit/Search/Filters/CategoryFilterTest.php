<?php
/**
 * Category Filter unit tests.
 *
 * @package APD\Tests\Unit\Search\Filters
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Search\Filters;

use APD\Tests\Unit\UnitTestCase;
use APD\Search\Filters\CategoryFilter;
use Brain\Monkey\Functions;

/**
 * Test CategoryFilter class.
 */
class CategoryFilterTest extends UnitTestCase
{
    /**
     * Set up additional mocks.
     */
    protected function setUpWordPressFunctions(): void
    {
        parent::setUpWordPressFunctions();

        // Mock get_terms to return empty by default.
        Functions\when('get_terms')->justReturn([]);
        // Note: is_wp_error is already defined in bootstrap.php
    }

    /**
     * Test filter type.
     */
    public function test_get_type(): void
    {
        $filter = new CategoryFilter();

        $this->assertSame('select', $filter->getType());
    }

    /**
     * Test filter name.
     */
    public function test_get_name(): void
    {
        $filter = new CategoryFilter();

        $this->assertSame('category', $filter->getName());
    }

    /**
     * Test URL parameter name.
     */
    public function test_get_url_param(): void
    {
        $filter = new CategoryFilter();

        $this->assertSame('apd_category', $filter->getUrlParam());
    }

    /**
     * Test default label.
     */
    public function test_get_label(): void
    {
        $filter = new CategoryFilter();

        $this->assertSame('Category', $filter->getLabel());
    }

    /**
     * Test source is taxonomy.
     */
    public function test_source_is_taxonomy(): void
    {
        $filter = new CategoryFilter();
        $config = $filter->getConfig();

        $this->assertSame('taxonomy', $config['source']);
        $this->assertSame('apd_category', $config['source_key']);
    }

    /**
     * Test sanitize with single value.
     */
    public function test_sanitize_single_value(): void
    {
        $filter = new CategoryFilter();

        $this->assertSame(5, $filter->sanitize('5'));
        $this->assertSame(0, $filter->sanitize(''));
        $this->assertSame(10, $filter->sanitize(10));
    }

    /**
     * Test sanitize with multiple values.
     */
    public function test_sanitize_multiple_values(): void
    {
        $filter = new CategoryFilter(['multiple' => true]);

        $result = $filter->sanitize(['1', '2', '3']);

        $this->assertSame([1, 2, 3], $result);
    }

    /**
     * Test isActive with valid category ID.
     */
    public function test_is_active_with_category(): void
    {
        $filter = new CategoryFilter();

        $this->assertTrue($filter->isActive(5));
        $this->assertTrue($filter->isActive('10'));
    }

    /**
     * Test isActive with empty value.
     */
    public function test_is_active_with_empty_value(): void
    {
        $filter = new CategoryFilter();

        $this->assertFalse($filter->isActive(0));
        $this->assertFalse($filter->isActive(''));
        $this->assertFalse($filter->isActive(null));
    }

    /**
     * Test isActive with multiple values.
     */
    public function test_is_active_with_multiple_values(): void
    {
        $filter = new CategoryFilter(['multiple' => true]);

        $this->assertTrue($filter->isActive([1, 2, 3]));
        $this->assertFalse($filter->isActive([]));
        $this->assertFalse($filter->isActive([0]));
    }

    /**
     * Test render outputs select element.
     */
    public function test_render(): void
    {
        // Use non-hierarchical to avoid complex mocking.
        $filter = new CategoryFilter(['hierarchical' => false]);

        $html = $filter->render(0);

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('name="apd_category"', $html);
        $this->assertStringContainsString('apd-filter--category', $html);
    }

    /**
     * Test render includes empty option.
     */
    public function test_render_empty_option(): void
    {
        $filter = new CategoryFilter(['hierarchical' => false]);

        $html = $filter->render(0);

        $this->assertStringContainsString('All Categories', $html);
    }

    /**
     * Test render with custom empty option.
     */
    public function test_render_custom_empty_option(): void
    {
        $filter = new CategoryFilter(['empty_option' => 'Choose category...', 'hierarchical' => false]);

        $html = $filter->render(0);

        $this->assertStringContainsString('Choose category...', $html);
    }

    /**
     * Test render marks active filter.
     */
    public function test_render_active_state(): void
    {
        $filter = new CategoryFilter(['hierarchical' => false]);

        $html = $filter->render(5);

        $this->assertStringContainsString('apd-filter--active', $html);
    }

    /**
     * Test render marks selected option.
     */
    public function test_render_selected_option(): void
    {
        // For non-hierarchical render, create a filter without hierarchy.
        $filter = new CategoryFilter(['hierarchical' => false]);

        // Mock get_terms to return a category.
        $term = new \stdClass();
        $term->term_id = 5;
        $term->name = 'Test Category';

        Functions\when('get_terms')->justReturn([$term]);

        $html = $filter->render(5);

        $this->assertStringContainsString('value="5"', $html);
        $this->assertStringContainsString('selected', $html);
        $this->assertStringContainsString('Test Category', $html);
    }

    /**
     * Test getConfig returns full configuration.
     */
    public function test_get_config(): void
    {
        $filter = new CategoryFilter();
        $config = $filter->getConfig();

        $this->assertArrayHasKey('source', $config);
        $this->assertArrayHasKey('source_key', $config);
        $this->assertArrayHasKey('hierarchical', $config);
        $this->assertArrayHasKey('hide_empty', $config);
        $this->assertSame('category', $config['name']);
    }

    /**
     * Test getDisplayValue with term mock.
     */
    public function test_get_display_value(): void
    {
        // Create a mock WP_Term object.
        $term = \Mockery::mock('WP_Term');
        $term->term_id = 5;
        $term->name = 'Test Category';

        Functions\when('get_term')->justReturn($term);

        $filter = new CategoryFilter();
        $display = $filter->getDisplayValue(5);

        $this->assertSame('Test Category', $display);
    }
}
