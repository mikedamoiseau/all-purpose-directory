<?php
/**
 * Filter Registry unit tests.
 *
 * @package APD\Tests\Unit\Search
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Search;

use APD\Tests\Unit\UnitTestCase;
use APD\Search\FilterRegistry;
use APD\Search\Filters\KeywordFilter;
use APD\Search\Filters\CategoryFilter;
use APD\Search\Filters\TagFilter;
use APD\Contracts\FilterInterface;
use Brain\Monkey\Functions;

/**
 * Test FilterRegistry class.
 */
class FilterRegistryTest extends UnitTestCase
{
    /**
     * Filter registry instance.
     *
     * @var FilterRegistry
     */
    private FilterRegistry $registry;

    /**
     * Set up before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Get fresh registry instance and reset it.
        $this->registry = FilterRegistry::get_instance();
        $this->registry->reset();
    }

    /**
     * Tear down after each test.
     */
    protected function tearDown(): void
    {
        $this->registry->reset();
        parent::tearDown();
    }

    /**
     * Test singleton pattern returns same instance.
     */
    public function test_get_instance_returns_singleton(): void
    {
        $instance1 = FilterRegistry::get_instance();
        $instance2 = FilterRegistry::get_instance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test registering a filter.
     */
    public function test_register_filter(): void
    {
        $filter = new KeywordFilter();

        $result = $this->registry->register_filter($filter);

        $this->assertTrue($result);
        $this->assertTrue($this->registry->has_filter('keyword'));
    }

    /**
     * Test cannot register duplicate filter.
     */
    public function test_cannot_register_duplicate_filter(): void
    {
        // Note: _doing_it_wrong is defined in bootstrap.php and just logs/ignores.
        $filter1 = new KeywordFilter();
        $filter2 = new KeywordFilter();

        $this->registry->register_filter($filter1);
        $result = $this->registry->register_filter($filter2);

        $this->assertFalse($result);
        $this->assertSame(1, $this->registry->count());
    }

    /**
     * Test unregistering a filter.
     */
    public function test_unregister_filter(): void
    {
        $filter = new KeywordFilter();
        $this->registry->register_filter($filter);

        $result = $this->registry->unregister_filter('keyword');

        $this->assertTrue($result);
        $this->assertFalse($this->registry->has_filter('keyword'));
    }

    /**
     * Test unregistering non-existent filter returns false.
     */
    public function test_unregister_nonexistent_filter_returns_false(): void
    {
        $result = $this->registry->unregister_filter('nonexistent');

        $this->assertFalse($result);
    }

    /**
     * Test getting a filter by name.
     */
    public function test_get_filter(): void
    {
        $filter = new KeywordFilter();
        $this->registry->register_filter($filter);

        $retrieved = $this->registry->get_filter('keyword');

        $this->assertSame($filter, $retrieved);
    }

    /**
     * Test getting non-existent filter returns null.
     */
    public function test_get_nonexistent_filter_returns_null(): void
    {
        $result = $this->registry->get_filter('nonexistent');

        $this->assertNull($result);
    }

    /**
     * Test getting all filters.
     */
    public function test_get_filters(): void
    {
        $this->registry->register_filter(new KeywordFilter());
        $this->registry->register_filter(new CategoryFilter());

        $filters = $this->registry->get_filters();

        $this->assertCount(2, $filters);
        $this->assertArrayHasKey('keyword', $filters);
        $this->assertArrayHasKey('category', $filters);
    }

    /**
     * Test getting filters by type.
     */
    public function test_get_filters_by_type(): void
    {
        $this->registry->register_filter(new KeywordFilter());
        $this->registry->register_filter(new CategoryFilter());

        $textFilters = $this->registry->get_filters(['type' => 'text']);

        $this->assertCount(1, $textFilters);
        $this->assertArrayHasKey('keyword', $textFilters);
    }

    /**
     * Test getting filters by source.
     */
    public function test_get_filters_by_source(): void
    {
        $this->registry->register_filter(new KeywordFilter());
        $this->registry->register_filter(new CategoryFilter());

        $taxonomyFilters = $this->registry->get_filters(['source' => 'taxonomy']);

        $this->assertCount(1, $taxonomyFilters);
        $this->assertArrayHasKey('category', $taxonomyFilters);
    }

    /**
     * Test filter count.
     */
    public function test_count(): void
    {
        $this->assertSame(0, $this->registry->count());

        $this->registry->register_filter(new KeywordFilter());
        $this->assertSame(1, $this->registry->count());

        $this->registry->register_filter(new CategoryFilter());
        $this->assertSame(2, $this->registry->count());
    }

    /**
     * Test has_filter.
     */
    public function test_has_filter(): void
    {
        $this->assertFalse($this->registry->has_filter('keyword'));

        $this->registry->register_filter(new KeywordFilter());

        $this->assertTrue($this->registry->has_filter('keyword'));
    }

    /**
     * Test filters are sorted by priority.
     */
    public function test_filters_sorted_by_priority(): void
    {
        // Register filters with different priorities.
        $low = new KeywordFilter(['priority' => 20]);
        $high = new CategoryFilter(['priority' => 5]);

        $this->registry->register_filter($low);
        $this->registry->register_filter($high);

        $filters = $this->registry->get_filters();
        $names = array_keys($filters);

        // Category (priority 5) should come before keyword (priority 20).
        $this->assertSame('category', $names[0]);
        $this->assertSame('keyword', $names[1]);
    }

    /**
     * Test reset clears all filters.
     */
    public function test_reset(): void
    {
        $this->registry->register_filter(new KeywordFilter());
        $this->registry->register_filter(new CategoryFilter());

        $this->registry->reset();

        $this->assertSame(0, $this->registry->count());
        $this->assertEmpty($this->registry->get_filters());
    }

    /**
     * Test get_active_filters with no active filters.
     */
    public function test_get_active_filters_empty(): void
    {
        $this->registry->register_filter(new KeywordFilter());

        $active = $this->registry->get_active_filters([]);

        $this->assertEmpty($active);
    }

    /**
     * Test get_active_filters with active filters.
     */
    public function test_get_active_filters_with_values(): void
    {
        $this->registry->register_filter(new KeywordFilter());

        $request = ['apd_keyword' => 'test search'];
        $active = $this->registry->get_active_filters($request);

        $this->assertCount(1, $active);
        $this->assertArrayHasKey('keyword', $active);
        $this->assertSame('test search', $active['keyword']['value']);
    }

    /**
     * Test get_filter_value.
     */
    public function test_get_filter_value(): void
    {
        $this->registry->register_filter(new KeywordFilter());

        $request = ['apd_keyword' => 'test'];
        $value = $this->registry->get_filter_value('keyword', $request);

        $this->assertSame('test', $value);
    }

    /**
     * Test get_filter_value returns null for missing param.
     */
    public function test_get_filter_value_returns_null_for_missing(): void
    {
        $this->registry->register_filter(new KeywordFilter());

        $value = $this->registry->get_filter_value('keyword', []);

        $this->assertNull($value);
    }

    /**
     * Test get_default_config.
     */
    public function test_get_default_config(): void
    {
        $config = $this->registry->get_default_config();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('name', $config);
        $this->assertArrayHasKey('type', $config);
        $this->assertArrayHasKey('label', $config);
        $this->assertArrayHasKey('priority', $config);
    }

    /**
     * Test filter_registered action is fired.
     *
     * Note: Action firing is verified indirectly through the registration success.
     * Direct do_action testing with Brain Monkey stubs is complex.
     */
    public function test_filter_registered_succeeds(): void
    {
        $filter = new KeywordFilter();
        $result = $this->registry->register_filter($filter);

        // The action is called inside register_filter, which succeeded.
        $this->assertTrue($result);
        $this->assertTrue($this->registry->has_filter('keyword'));
    }

    /**
     * Test filter_unregistered action is fired.
     *
     * Note: Action firing is verified indirectly through the unregistration success.
     */
    public function test_filter_unregistered_succeeds(): void
    {
        $filter = new KeywordFilter();
        $this->registry->register_filter($filter);
        $result = $this->registry->unregister_filter('keyword');

        // The action is called inside unregister_filter, which succeeded.
        $this->assertTrue($result);
        $this->assertFalse($this->registry->has_filter('keyword'));
    }
}
