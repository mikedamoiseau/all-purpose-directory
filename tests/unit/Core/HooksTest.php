<?php
/**
 * Tests for the Hooks class.
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use APD\Core\Hooks;
use APD\Tests\Unit\UnitTestCase;

/**
 * Tests for the Hooks class.
 */
class HooksTest extends UnitTestCase
{
    /**
     * Test that all action hook constants have the correct prefix.
     */
    public function test_action_hooks_have_apd_prefix(): void
    {
        $actions = Hooks::get_action_hooks();

        foreach ($actions as $name => $value) {
            $this->assertStringStartsWith(
                'apd_',
                $value,
                "Action hook {$name} should start with 'apd_' prefix"
            );
        }
    }

    /**
     * Test that all filter hook constants have the correct prefix.
     */
    public function test_filter_hooks_have_apd_prefix(): void
    {
        $filters = Hooks::get_filter_hooks();

        foreach ($filters as $name => $value) {
            $this->assertStringStartsWith(
                'apd_',
                $value,
                "Filter hook {$name} should start with 'apd_' prefix"
            );
        }
    }

    /**
     * Test that get_all_hooks returns combined action and filter hooks.
     */
    public function test_get_all_hooks_combines_actions_and_filters(): void
    {
        $all = Hooks::get_all_hooks();
        $actions = Hooks::get_action_hooks();
        $filters = Hooks::get_filter_hooks();

        $this->assertCount(
            count($actions) + count($filters),
            $all,
            'get_all_hooks should return combined count of actions and filters'
        );

        foreach ($actions as $name => $value) {
            $this->assertArrayHasKey($name, $all);
            $this->assertSame($value, $all[$name]);
        }

        foreach ($filters as $name => $value) {
            $this->assertArrayHasKey($name, $all);
            $this->assertSame($value, $all[$name]);
        }
    }

    /**
     * Test that action hook constants are defined.
     */
    public function test_action_hook_constants_are_defined(): void
    {
        $expectedActions = [
            'INIT',
            'LOADED',
            'ACTIVATED',
            'DEACTIVATED',
            'BEFORE_LISTING_SAVE',
            'AFTER_LISTING_SAVE',
            'LISTING_STATUS_CHANGED',
            'BEFORE_SUBMISSION',
            'AFTER_SUBMISSION',
            'AFTER_REVIEW_SUBMITTED',
            'REVIEW_STATUS_CHANGED',
            'FAVORITE_ADDED',
            'FAVORITE_REMOVED',
        ];

        foreach ($expectedActions as $action) {
            $this->assertTrue(
                defined(Hooks::class . '::' . $action),
                "Hooks::{$action} constant should be defined"
            );
        }
    }

    /**
     * Test that filter hook constants are defined.
     */
    public function test_filter_hook_constants_are_defined(): void
    {
        $expectedFilters = [
            'LISTING_FIELDS',
            'SUBMISSION_FIELDS',
            'SEARCH_FILTERS',
            'LISTING_QUERY_ARGS',
            'LISTING_CARD_DATA',
            'EMAIL_TEMPLATES',
            'SHOULD_LOAD_FRONTEND_ASSETS',
            'IS_PLUGIN_ADMIN_SCREEN',
            'FRONTEND_SCRIPT_DATA',
            'ADMIN_SCRIPT_DATA',
        ];

        foreach ($expectedFilters as $filter) {
            $this->assertTrue(
                defined(Hooks::class . '::' . $filter),
                "Hooks::{$filter} constant should be defined"
            );
        }
    }

    /**
     * Test that hook names are unique.
     */
    public function test_hook_names_are_unique(): void
    {
        $all = Hooks::get_all_hooks();
        $values = array_values($all);
        $uniqueValues = array_unique($values);

        $this->assertCount(
            count($values),
            $uniqueValues,
            'All hook names should be unique'
        );
    }

    /**
     * Test specific hook values.
     */
    public function test_specific_hook_values(): void
    {
        $this->assertSame('apd_init', Hooks::INIT);
        $this->assertSame('apd_loaded', Hooks::LOADED);
        $this->assertSame('apd_activated', Hooks::ACTIVATED);
        $this->assertSame('apd_deactivated', Hooks::DEACTIVATED);
        $this->assertSame('apd_before_listing_save', Hooks::BEFORE_LISTING_SAVE);
        $this->assertSame('apd_after_listing_save', Hooks::AFTER_LISTING_SAVE);
        $this->assertSame('apd_listing_fields', Hooks::LISTING_FIELDS);
        $this->assertSame('apd_submission_fields', Hooks::SUBMISSION_FIELDS);
    }
}
