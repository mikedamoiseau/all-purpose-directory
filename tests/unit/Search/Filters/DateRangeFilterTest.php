<?php
/**
 * Date Range Filter unit tests.
 *
 * @package APD\Tests\Unit\Search\Filters
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Search\Filters;

use APD\Tests\Unit\UnitTestCase;
use APD\Search\Filters\DateRangeFilter;
use Brain\Monkey\Functions;

/**
 * Test DateRangeFilter class.
 */
class DateRangeFilterTest extends UnitTestCase
{
    /**
     * Set up additional mocks.
     */
    protected function setUpWordPressFunctions(): void
    {
        parent::setUpWordPressFunctions();

        Functions\when('get_option')->justReturn('Y-m-d');
        Functions\when('date_i18n')->alias(function ($format, $timestamp) {
            return date($format, $timestamp);
        });
    }

    /**
     * Test filter type.
     */
    public function test_get_type(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $this->assertSame('date_range', $filter->getType());
    }

    /**
     * Test filter name.
     */
    public function test_get_name(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $this->assertSame('event_date', $filter->getName());
    }

    /**
     * Test URL parameter names for start/end.
     */
    public function test_get_url_params(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $this->assertSame('apd_event_date', $filter->getUrlParam());
        $this->assertSame('apd_event_date_start', $filter->getUrlParamStart());
        $this->assertSame('apd_event_date_end', $filter->getUrlParamEnd());
    }

    /**
     * Test sanitize with valid dates.
     */
    public function test_sanitize_valid_dates(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $result = $filter->sanitize(['start' => '2024-01-15', 'end' => '2024-12-31']);

        $this->assertSame(['start' => '2024-01-15', 'end' => '2024-12-31'], $result);
    }

    /**
     * Test sanitize with start only.
     */
    public function test_sanitize_start_only(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $result = $filter->sanitize(['start' => '2024-06-01', 'end' => '']);

        $this->assertSame(['start' => '2024-06-01', 'end' => ''], $result);
    }

    /**
     * Test sanitize with invalid date format.
     */
    public function test_sanitize_invalid_format(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $result = $filter->sanitize(['start' => 'not-a-date', 'end' => '01/15/2024']);

        $this->assertSame(['start' => '', 'end' => ''], $result);
    }

    /**
     * Test sanitize with invalid date values.
     */
    public function test_sanitize_invalid_date(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        // February 30 doesn't exist.
        $result = $filter->sanitize(['start' => '2024-02-30', 'end' => '']);

        $this->assertSame(['start' => '', 'end' => ''], $result);
    }

    /**
     * Test sanitize with non-array returns empty.
     */
    public function test_sanitize_non_array(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $result = $filter->sanitize('2024-01-15');

        $this->assertSame(['start' => '', 'end' => ''], $result);
    }

    /**
     * Test isActive with start only.
     */
    public function test_is_active_start_only(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $this->assertTrue($filter->isActive(['start' => '2024-01-15', 'end' => '']));
    }

    /**
     * Test isActive with end only.
     */
    public function test_is_active_end_only(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $this->assertTrue($filter->isActive(['start' => '', 'end' => '2024-12-31']));
    }

    /**
     * Test isActive with both dates.
     */
    public function test_is_active_both(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $this->assertTrue($filter->isActive(['start' => '2024-01-01', 'end' => '2024-12-31']));
    }

    /**
     * Test isActive with empty values.
     */
    public function test_is_active_empty(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $this->assertFalse($filter->isActive(['start' => '', 'end' => '']));
        $this->assertFalse($filter->isActive(null));
    }

    /**
     * Test getOptions returns empty array.
     */
    public function test_get_options_empty(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $this->assertSame([], $filter->getOptions());
    }

    /**
     * Test getDisplayValue with both dates.
     */
    public function test_get_display_value_both(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $display = $filter->getDisplayValue(['start' => '2024-01-15', 'end' => '2024-12-31']);

        $this->assertStringContainsString('2024-01-15', $display);
        $this->assertStringContainsString('2024-12-31', $display);
    }

    /**
     * Test getDisplayValue with start only.
     */
    public function test_get_display_value_start_only(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $display = $filter->getDisplayValue(['start' => '2024-06-01', 'end' => '']);

        $this->assertStringContainsString('2024-06-01', $display);
        $this->assertStringContainsString('From', $display);
    }

    /**
     * Test getDisplayValue with end only.
     */
    public function test_get_display_value_end_only(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $display = $filter->getDisplayValue(['start' => '', 'end' => '2024-12-31']);

        $this->assertStringContainsString('2024-12-31', $display);
        $this->assertStringContainsString('Until', $display);
    }

    /**
     * Test render outputs date inputs.
     */
    public function test_render(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date', 'label' => 'Event Date']);

        $html = $filter->render(['start' => '', 'end' => '']);

        $this->assertStringContainsString('type="date"', $html);
        $this->assertStringContainsString('name="apd_event_date_start"', $html);
        $this->assertStringContainsString('name="apd_event_date_end"', $html);
        $this->assertStringContainsString('apd-filter--date_range', $html);
    }

    /**
     * Test render with values.
     */
    public function test_render_with_values(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $html = $filter->render(['start' => '2024-01-15', 'end' => '2024-12-31']);

        $this->assertStringContainsString('value="2024-01-15"', $html);
        $this->assertStringContainsString('value="2024-12-31"', $html);
    }

    /**
     * Test render includes min/max constraints.
     */
    public function test_render_with_constraints(): void
    {
        $filter = new DateRangeFilter([
            'name' => 'event_date',
            'min' => '2024-01-01',
            'max' => '2024-12-31',
        ]);

        $html = $filter->render(['start' => '', 'end' => '']);

        $this->assertStringContainsString('min="2024-01-01"', $html);
        $this->assertStringContainsString('max="2024-12-31"', $html);
    }

    /**
     * Test render includes labels.
     */
    public function test_render_with_labels(): void
    {
        $filter = new DateRangeFilter([
            'name' => 'event_date',
            'start_label' => 'Start Date',
            'end_label' => 'End Date',
        ]);

        $html = $filter->render(['start' => '', 'end' => '']);

        $this->assertStringContainsString('Start Date', $html);
        $this->assertStringContainsString('End Date', $html);
    }

    /**
     * Test getValueFromRequest.
     */
    public function test_get_value_from_request(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $request = [
            'apd_event_date_start' => '2024-06-01',
            'apd_event_date_end' => '2024-06-30',
        ];

        $value = $filter->getValueFromRequest($request);

        $this->assertSame(['start' => '2024-06-01', 'end' => '2024-06-30'], $value);
    }

    /**
     * Test getValueFromRequest with partial values.
     */
    public function test_get_value_from_request_partial(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $request = ['apd_event_date_start' => '2024-01-01'];
        $value = $filter->getValueFromRequest($request);

        $this->assertSame(['start' => '2024-01-01', 'end' => ''], $value);
    }

    /**
     * Test render marks active state.
     */
    public function test_render_active_state(): void
    {
        $filter = new DateRangeFilter(['name' => 'event_date']);

        $html = $filter->render(['start' => '2024-01-15', 'end' => '']);

        $this->assertStringContainsString('apd-filter--active', $html);
    }
}
