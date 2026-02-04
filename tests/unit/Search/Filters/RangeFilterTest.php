<?php
/**
 * Range Filter unit tests.
 *
 * @package APD\Tests\Unit\Search\Filters
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Search\Filters;

use APD\Tests\Unit\UnitTestCase;
use APD\Search\Filters\RangeFilter;

/**
 * Test RangeFilter class.
 */
class RangeFilterTest extends UnitTestCase
{
    /**
     * Test filter type.
     */
    public function test_get_type(): void
    {
        $filter = new RangeFilter(['name' => 'price']);

        $this->assertSame('range', $filter->getType());
    }

    /**
     * Test filter name.
     */
    public function test_get_name(): void
    {
        $filter = new RangeFilter(['name' => 'price']);

        $this->assertSame('price', $filter->getName());
    }

    /**
     * Test URL parameter names for min/max.
     */
    public function test_get_url_params(): void
    {
        $filter = new RangeFilter(['name' => 'price']);

        $this->assertSame('apd_price', $filter->getUrlParam());
        $this->assertSame('apd_price_min', $filter->getUrlParamMin());
        $this->assertSame('apd_price_max', $filter->getUrlParamMax());
    }

    /**
     * Test sanitize with both min and max.
     */
    public function test_sanitize_both_values(): void
    {
        $filter = new RangeFilter(['name' => 'price']);

        $result = $filter->sanitize(['min' => '10', 'max' => '100']);

        $this->assertSame(['min' => '10', 'max' => '100'], $result);
    }

    /**
     * Test sanitize with only min.
     */
    public function test_sanitize_min_only(): void
    {
        $filter = new RangeFilter(['name' => 'price']);

        $result = $filter->sanitize(['min' => '50', 'max' => '']);

        $this->assertSame(['min' => '50', 'max' => ''], $result);
    }

    /**
     * Test sanitize with only max.
     */
    public function test_sanitize_max_only(): void
    {
        $filter = new RangeFilter(['name' => 'price']);

        $result = $filter->sanitize(['min' => '', 'max' => '200']);

        $this->assertSame(['min' => '', 'max' => '200'], $result);
    }

    /**
     * Test sanitize with decimal step.
     */
    public function test_sanitize_decimal(): void
    {
        $filter = new RangeFilter(['name' => 'rating', 'step' => 0.1]);

        $result = $filter->sanitize(['min' => '3.5', 'max' => '5.0']);

        $this->assertSame(['min' => '3.5', 'max' => '5'], $result);
    }

    /**
     * Test sanitize with non-array returns empty.
     */
    public function test_sanitize_non_array(): void
    {
        $filter = new RangeFilter(['name' => 'price']);

        $result = $filter->sanitize('invalid');

        $this->assertSame(['min' => '', 'max' => ''], $result);
    }

    /**
     * Test isActive with min only.
     */
    public function test_is_active_min_only(): void
    {
        $filter = new RangeFilter(['name' => 'price']);

        $this->assertTrue($filter->isActive(['min' => '10', 'max' => '']));
    }

    /**
     * Test isActive with max only.
     */
    public function test_is_active_max_only(): void
    {
        $filter = new RangeFilter(['name' => 'price']);

        $this->assertTrue($filter->isActive(['min' => '', 'max' => '100']));
    }

    /**
     * Test isActive with both values.
     */
    public function test_is_active_both(): void
    {
        $filter = new RangeFilter(['name' => 'price']);

        $this->assertTrue($filter->isActive(['min' => '10', 'max' => '100']));
    }

    /**
     * Test isActive with empty values.
     */
    public function test_is_active_empty(): void
    {
        $filter = new RangeFilter(['name' => 'price']);

        $this->assertFalse($filter->isActive(['min' => '', 'max' => '']));
        $this->assertFalse($filter->isActive(null));
        $this->assertFalse($filter->isActive('invalid'));
    }

    /**
     * Test getOptions returns empty array.
     */
    public function test_get_options_empty(): void
    {
        $filter = new RangeFilter(['name' => 'price']);

        $this->assertSame([], $filter->getOptions());
    }

    /**
     * Test getDisplayValue with both values.
     */
    public function test_get_display_value_both(): void
    {
        $filter = new RangeFilter(['name' => 'price', 'prefix' => '$']);

        $display = $filter->getDisplayValue(['min' => '10', 'max' => '100']);

        $this->assertSame('$10 - $100', $display);
    }

    /**
     * Test getDisplayValue with min only.
     */
    public function test_get_display_value_min_only(): void
    {
        $filter = new RangeFilter(['name' => 'price', 'prefix' => '$']);

        $display = $filter->getDisplayValue(['min' => '50', 'max' => '']);

        $this->assertStringContainsString('$50', $display);
        $this->assertStringContainsString('or more', $display);
    }

    /**
     * Test getDisplayValue with max only.
     */
    public function test_get_display_value_max_only(): void
    {
        $filter = new RangeFilter(['name' => 'price', 'prefix' => '$']);

        $display = $filter->getDisplayValue(['min' => '', 'max' => '200']);

        $this->assertStringContainsString('$200', $display);
        $this->assertStringContainsString('Up to', $display);
    }

    /**
     * Test render outputs min/max inputs.
     */
    public function test_render(): void
    {
        $filter = new RangeFilter(['name' => 'price', 'label' => 'Price']);

        $html = $filter->render(['min' => '', 'max' => '']);

        $this->assertStringContainsString('type="number"', $html);
        $this->assertStringContainsString('name="apd_price_min"', $html);
        $this->assertStringContainsString('name="apd_price_max"', $html);
        $this->assertStringContainsString('apd-filter--range', $html);
    }

    /**
     * Test render with values.
     */
    public function test_render_with_values(): void
    {
        $filter = new RangeFilter(['name' => 'price']);

        $html = $filter->render(['min' => '10', 'max' => '100']);

        $this->assertStringContainsString('value="10"', $html);
        $this->assertStringContainsString('value="100"', $html);
    }

    /**
     * Test render with prefix/suffix.
     */
    public function test_render_with_prefix_suffix(): void
    {
        $filter = new RangeFilter(['name' => 'price', 'prefix' => '$', 'suffix' => 'USD']);

        $html = $filter->render(['min' => '', 'max' => '']);

        $this->assertStringContainsString('$', $html);
        $this->assertStringContainsString('USD', $html);
    }

    /**
     * Test render includes min/max/step attributes.
     */
    public function test_render_with_constraints(): void
    {
        $filter = new RangeFilter([
            'name' => 'price',
            'min' => 0,
            'max' => 1000,
            'step' => 10,
        ]);

        $html = $filter->render(['min' => '', 'max' => '']);

        $this->assertStringContainsString('min="0"', $html);
        $this->assertStringContainsString('max="1000"', $html);
        $this->assertStringContainsString('step="10"', $html);
    }

    /**
     * Test getValueFromRequest.
     */
    public function test_get_value_from_request(): void
    {
        $filter = new RangeFilter(['name' => 'price']);

        $request = [
            'apd_price_min' => '50',
            'apd_price_max' => '200',
        ];

        $value = $filter->getValueFromRequest($request);

        $this->assertSame(['min' => '50', 'max' => '200'], $value);
    }

    /**
     * Test getValueFromRequest with partial values.
     */
    public function test_get_value_from_request_partial(): void
    {
        $filter = new RangeFilter(['name' => 'price']);

        $request = ['apd_price_min' => '100'];
        $value = $filter->getValueFromRequest($request);

        $this->assertSame(['min' => '100', 'max' => ''], $value);
    }

    /**
     * Test render marks active state.
     */
    public function test_render_active_state(): void
    {
        $filter = new RangeFilter(['name' => 'price']);

        $html = $filter->render(['min' => '50', 'max' => '']);

        $this->assertStringContainsString('apd-filter--active', $html);
    }
}
