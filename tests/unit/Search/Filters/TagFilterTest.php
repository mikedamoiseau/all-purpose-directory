<?php
/**
 * Tag Filter unit tests.
 *
 * @package APD\Tests\Unit\Search\Filters
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Search\Filters;

use APD\Tests\Unit\UnitTestCase;
use APD\Search\Filters\TagFilter;
use Brain\Monkey\Functions;

/**
 * Test TagFilter class.
 */
class TagFilterTest extends UnitTestCase
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
        $filter = new TagFilter();

        $this->assertSame('checkbox', $filter->getType());
    }

    /**
     * Test filter name.
     */
    public function test_get_name(): void
    {
        $filter = new TagFilter();

        $this->assertSame('tag', $filter->getName());
    }

    /**
     * Test URL parameter name.
     */
    public function test_get_url_param(): void
    {
        $filter = new TagFilter();

        $this->assertSame('apd_tag', $filter->getUrlParam());
    }

    /**
     * Test default label.
     */
    public function test_get_label(): void
    {
        $filter = new TagFilter();

        $this->assertSame('Tags', $filter->getLabel());
    }

    /**
     * Test source is taxonomy.
     */
    public function test_source_is_taxonomy(): void
    {
        $filter = new TagFilter();
        $config = $filter->getConfig();

        $this->assertSame('taxonomy', $config['source']);
        $this->assertSame('apd_tag', $config['source_key']);
    }

    /**
     * Test default config values.
     */
    public function test_default_config(): void
    {
        $filter = new TagFilter();
        $config = $filter->getConfig();

        $this->assertTrue($config['multiple']);
        $this->assertTrue($config['hide_empty']);
        $this->assertSame(20, $config['max_items']);
    }

    /**
     * Test sanitize with single value.
     */
    public function test_sanitize_single_value(): void
    {
        $filter = new TagFilter();

        $this->assertSame([5], $filter->sanitize('5'));
        $this->assertSame([], $filter->sanitize(''));
        $this->assertSame([10], $filter->sanitize(10));
    }

    /**
     * Test sanitize with multiple values.
     */
    public function test_sanitize_multiple_values(): void
    {
        $filter = new TagFilter();

        $result = $filter->sanitize(['1', '2', '3']);

        $this->assertSame([1, 2, 3], $result);
    }

    /**
     * Test sanitize with invalid values.
     */
    public function test_sanitize_invalid_values(): void
    {
        $filter = new TagFilter();

        // Strings become 0 via absint, but then get filtered as falsy.
        $this->assertSame([0], $filter->sanitize(['abc']));
        $this->assertSame([], $filter->sanitize(null));
        $this->assertSame([], $filter->sanitize(0));
    }

    /**
     * Test isActive with valid tag IDs.
     */
    public function test_is_active_with_tags(): void
    {
        $filter = new TagFilter();

        $this->assertTrue($filter->isActive([5]));
        $this->assertTrue($filter->isActive([1, 2, 3]));
    }

    /**
     * Test isActive with empty values.
     */
    public function test_is_active_with_empty_value(): void
    {
        $filter = new TagFilter();

        $this->assertFalse($filter->isActive([]));
        $this->assertFalse($filter->isActive([0]));
        $this->assertFalse($filter->isActive(null));
    }

    /**
     * Test isActive with non-array values.
     */
    public function test_is_active_with_non_array(): void
    {
        $filter = new TagFilter();

        $this->assertFalse($filter->isActive(5));
        $this->assertFalse($filter->isActive('5'));
    }

    /**
     * Test render returns empty string when no options.
     */
    public function test_render_empty_when_no_options(): void
    {
        $filter = new TagFilter();

        $html = $filter->render([]);

        $this->assertEmpty($html);
    }

    /**
     * Test render outputs fieldset with legend.
     */
    public function test_render_fieldset_structure(): void
    {
        // Mock get_terms to return tags.
        $term = new \stdClass();
        $term->term_id = 5;
        $term->name = 'Test Tag';

        Functions\when('get_terms')->justReturn([$term]);

        $filter = new TagFilter();

        $html = $filter->render([]);

        $this->assertStringContainsString('<fieldset', $html);
        $this->assertStringContainsString('<legend', $html);
        $this->assertStringContainsString('Tags', $html);
    }

    /**
     * Test render outputs checkboxes.
     */
    public function test_render_checkboxes(): void
    {
        $term = new \stdClass();
        $term->term_id = 5;
        $term->name = 'Test Tag';

        Functions\when('get_terms')->justReturn([$term]);

        $filter = new TagFilter();

        $html = $filter->render([]);

        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('name="apd_tag[]"', $html);
        $this->assertStringContainsString('value="5"', $html);
        $this->assertStringContainsString('Test Tag', $html);
    }

    /**
     * Test render marks checked tags.
     */
    public function test_render_checked_state(): void
    {
        $term1 = new \stdClass();
        $term1->term_id = 5;
        $term1->name = 'Tag One';

        $term2 = new \stdClass();
        $term2->term_id = 10;
        $term2->name = 'Tag Two';

        Functions\when('get_terms')->justReturn([$term1, $term2]);

        $filter = new TagFilter();

        $html = $filter->render([5]);

        // Tag 5 should be checked.
        $this->assertMatchesRegularExpression('/value="5"[^>]*checked/', $html);
        // Tag 10 should not be checked.
        $this->assertDoesNotMatchRegularExpression('/value="10"[^>]*checked/', $html);
    }

    /**
     * Test render active state class.
     */
    public function test_render_active_state(): void
    {
        $term = new \stdClass();
        $term->term_id = 5;
        $term->name = 'Test Tag';

        Functions\when('get_terms')->justReturn([$term]);

        $filter = new TagFilter();

        $html = $filter->render([5]);

        $this->assertStringContainsString('apd-filter--active', $html);
    }

    /**
     * Test render respects max_items.
     */
    public function test_render_max_items(): void
    {
        $terms = [];
        for ($i = 1; $i <= 25; $i++) {
            $term = new \stdClass();
            $term->term_id = $i;
            $term->name = "Tag $i";
            $terms[] = $term;
        }

        Functions\when('get_terms')->justReturn($terms);

        // Filter with max_items of 5.
        $filter = new TagFilter(['max_items' => 5]);

        $html = $filter->render([]);

        // Should only render 5 checkboxes.
        $this->assertSame(5, substr_count($html, 'type="checkbox"'));
    }

    /**
     * Test render with custom config.
     */
    public function test_render_custom_label(): void
    {
        $term = new \stdClass();
        $term->term_id = 1;
        $term->name = 'Test';

        Functions\when('get_terms')->justReturn([$term]);

        $filter = new TagFilter(['label' => 'Custom Tags']);

        $html = $filter->render([]);

        $this->assertStringContainsString('Custom Tags', $html);
    }

    /**
     * Test getConfig returns full configuration.
     */
    public function test_get_config(): void
    {
        $filter = new TagFilter();
        $config = $filter->getConfig();

        $this->assertArrayHasKey('source', $config);
        $this->assertArrayHasKey('source_key', $config);
        $this->assertArrayHasKey('multiple', $config);
        $this->assertArrayHasKey('hide_empty', $config);
        $this->assertArrayHasKey('max_items', $config);
        $this->assertSame('tag', $config['name']);
    }

    /**
     * Test getDisplayValue with term mock.
     */
    public function test_get_display_value_single(): void
    {
        // Create a mock WP_Term object.
        $term = \Mockery::mock('WP_Term');
        $term->term_id = 5;
        $term->name = 'Test Tag';

        Functions\when('get_term')->justReturn($term);

        $filter = new TagFilter();
        $display = $filter->getDisplayValue([5]);

        $this->assertSame('Test Tag', $display);
    }

    /**
     * Test getDisplayValue with multiple terms.
     */
    public function test_get_display_value_multiple(): void
    {
        $term1 = \Mockery::mock('WP_Term');
        $term1->term_id = 5;
        $term1->name = 'Tag One';

        $term2 = \Mockery::mock('WP_Term');
        $term2->term_id = 10;
        $term2->name = 'Tag Two';

        Functions\when('get_term')->alias(function ($term_id) use ($term1, $term2) {
            if ($term_id === 5) {
                return $term1;
            }
            if ($term_id === 10) {
                return $term2;
            }
            return null;
        });

        $filter = new TagFilter();
        $display = $filter->getDisplayValue([5, 10]);

        $this->assertSame('Tag One, Tag Two', $display);
    }

    /**
     * Test getDisplayValue with non-array value.
     */
    public function test_get_display_value_non_array(): void
    {
        $term = \Mockery::mock('WP_Term');
        $term->term_id = 5;
        $term->name = 'Test Tag';

        Functions\when('get_term')->justReturn($term);

        $filter = new TagFilter();
        $display = $filter->getDisplayValue(5);

        $this->assertSame('Test Tag', $display);
    }

    /**
     * Test getOptions returns empty array on error.
     */
    public function test_get_options_returns_empty_on_error(): void
    {
        $error = new \WP_Error('test', 'Test error');
        Functions\when('get_terms')->justReturn($error);

        $filter = new TagFilter();
        $options = $filter->getOptions();

        $this->assertSame([], $options);
    }

    /**
     * Test getOptions returns terms keyed by ID.
     */
    public function test_get_options_returns_terms(): void
    {
        $term1 = new \stdClass();
        $term1->term_id = 5;
        $term1->name = 'Tag One';

        $term2 = new \stdClass();
        $term2->term_id = 10;
        $term2->name = 'Tag Two';

        Functions\when('get_terms')->justReturn([$term1, $term2]);

        $filter = new TagFilter();
        $options = $filter->getOptions();

        $this->assertSame(['5' => 'Tag One', '10' => 'Tag Two'], $options);
    }
}
