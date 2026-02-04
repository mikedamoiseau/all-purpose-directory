<?php
/**
 * Keyword Filter unit tests.
 *
 * @package APD\Tests\Unit\Search\Filters
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Search\Filters;

use APD\Tests\Unit\UnitTestCase;
use APD\Search\Filters\KeywordFilter;

/**
 * Test KeywordFilter class.
 */
class KeywordFilterTest extends UnitTestCase
{
    /**
     * Test filter type.
     */
    public function test_get_type(): void
    {
        $filter = new KeywordFilter();

        $this->assertSame('text', $filter->getType());
    }

    /**
     * Test filter name.
     */
    public function test_get_name(): void
    {
        $filter = new KeywordFilter();

        $this->assertSame('keyword', $filter->getName());
    }

    /**
     * Test URL parameter name.
     */
    public function test_get_url_param(): void
    {
        $filter = new KeywordFilter();

        $this->assertSame('apd_keyword', $filter->getUrlParam());
    }

    /**
     * Test default label.
     */
    public function test_get_label(): void
    {
        $filter = new KeywordFilter();

        $this->assertSame('Search', $filter->getLabel());
    }

    /**
     * Test custom label.
     */
    public function test_custom_label(): void
    {
        $filter = new KeywordFilter(['label' => 'Find Listings']);

        $this->assertSame('Find Listings', $filter->getLabel());
    }

    /**
     * Test sanitize.
     */
    public function test_sanitize(): void
    {
        $filter = new KeywordFilter();

        $this->assertSame('test query', $filter->sanitize('  test query  '));
        $this->assertSame('clean text', $filter->sanitize('<script>clean text</script>'));
    }

    /**
     * Test isActive with non-empty value.
     */
    public function test_is_active_with_value(): void
    {
        $filter = new KeywordFilter();

        $this->assertTrue($filter->isActive('search term'));
        $this->assertTrue($filter->isActive('ab')); // Min length 2.
    }

    /**
     * Test isActive with empty value.
     */
    public function test_is_active_with_empty_value(): void
    {
        $filter = new KeywordFilter();

        $this->assertFalse($filter->isActive(''));
        $this->assertFalse($filter->isActive('   '));
        $this->assertFalse($filter->isActive('a')); // Below min length.
    }

    /**
     * Test isActive respects min_length config.
     */
    public function test_is_active_min_length(): void
    {
        $filter = new KeywordFilter(['min_length' => 3]);

        $this->assertFalse($filter->isActive('ab'));
        $this->assertTrue($filter->isActive('abc'));
    }

    /**
     * Test getOptions returns empty array.
     */
    public function test_get_options_empty(): void
    {
        $filter = new KeywordFilter();

        $this->assertSame([], $filter->getOptions());
    }

    /**
     * Test getDisplayValue.
     */
    public function test_get_display_value(): void
    {
        $filter = new KeywordFilter();

        $display = $filter->getDisplayValue('test search');

        $this->assertStringContainsString('test search', $display);
    }

    /**
     * Test render outputs search input.
     */
    public function test_render(): void
    {
        $filter = new KeywordFilter();

        $html = $filter->render('test query');

        $this->assertStringContainsString('type="search"', $html);
        $this->assertStringContainsString('name="apd_keyword"', $html);
        $this->assertStringContainsString('value="test query"', $html);
        $this->assertStringContainsString('apd-filter--keyword', $html);
    }

    /**
     * Test render with custom placeholder.
     */
    public function test_render_with_placeholder(): void
    {
        $filter = new KeywordFilter(['placeholder' => 'Find something...']);

        $html = $filter->render('');

        $this->assertStringContainsString('placeholder="Find something..."', $html);
    }

    /**
     * Test render marks active filter.
     */
    public function test_render_active_state(): void
    {
        $filter = new KeywordFilter();

        $html = $filter->render('search');

        $this->assertStringContainsString('apd-filter--active', $html);
    }

    /**
     * Test getConfig returns full configuration.
     */
    public function test_get_config(): void
    {
        $filter = new KeywordFilter();

        $config = $filter->getConfig();

        $this->assertArrayHasKey('name', $config);
        $this->assertArrayHasKey('type', $config);
        $this->assertArrayHasKey('label', $config);
        $this->assertArrayHasKey('placeholder', $config);
        $this->assertArrayHasKey('min_length', $config);
        $this->assertSame('keyword', $config['name']);
    }
}
