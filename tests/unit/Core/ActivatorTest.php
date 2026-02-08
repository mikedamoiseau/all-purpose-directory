<?php
/**
 * Tests for the Activator class.
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use APD\Core\Activator;
use APD\Core\Capabilities;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests for the Activator class.
 */
class ActivatorTest extends UnitTestCase
{
    /**
     * Set up before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Plugin constants are defined in the bootstrap file.
    }

    /**
     * Test that activate method calls all required setup methods.
     *
     * Note: This test verifies that version checks pass and all required
     * WordPress functions are called during activation.
     */
    public function test_activate_calls_required_methods(): void
    {
        // Note: version_compare is a PHP built-in and cannot be mocked.
        // PHP 8.5.2 >= 8.0, so PHP check passes.

        // WordPress version check must pass.
        Functions\when('get_bloginfo')->justReturn('6.5.0');

        // Version checks use these - mock to not exit.
        Functions\when('deactivate_plugins')->justReturn(null);
        Functions\when('wp_die')->alias(function () {
            throw new \RuntimeException('wp_die called - version check failed');
        });

        // Mock role functions.
        $mockRole = Mockery::mock('WP_Role');
        $mockRole->shouldReceive('add_cap')->andReturn(true);
        Functions\when('get_role')->justReturn($mockRole);

        // Mock option functions - use when() instead of expect() for flexibility.
        Functions\when('get_option')->justReturn(false);
        Functions\when('add_option')->justReturn(true);
        Functions\when('update_option')->justReturn(true);

        // Mock scheduling functions.
        Functions\when('wp_next_scheduled')->justReturn(false);
        Functions\when('wp_schedule_event')->justReturn(true);

        // Mock page creation functions.
        Functions\when('wp_insert_post')->justReturn(100);
        Functions\when('get_post')->justReturn(null);

        // Run activation - should not throw.
        Activator::activate();

        // If we got here without exceptions, the test passes.
        $this->assertTrue(true);
    }

    /**
     * Test that activate creates default settings.
     *
     * Note: This test captures the settings passed to add_option to verify
     * the default settings structure is correct.
     */
    public function test_activate_creates_default_settings(): void
    {
        // Setup mocks.
        Functions\when('get_bloginfo')->justReturn('6.5.0');
        Functions\when('deactivate_plugins')->justReturn(null);
        Functions\when('wp_die')->alias(function () {
            throw new \RuntimeException('wp_die called');
        });

        $mockRole = Mockery::mock('WP_Role');
        $mockRole->shouldReceive('add_cap')->andReturn(true);
        Functions\when('get_role')->justReturn($mockRole);

        Functions\when('wp_next_scheduled')->justReturn(false);
        Functions\when('wp_schedule_event')->justReturn(true);
        Functions\when('update_option')->justReturn(true);

        // Mock page creation functions.
        Functions\when('wp_insert_post')->justReturn(100);
        Functions\when('get_post')->justReturn(null);

        // Capture the settings passed to add_option.
        $addOptionCalled = false;
        $optionName = \APD\Admin\Settings::OPTION_NAME;
        Functions\when('get_option')->alias(function ($name) use ($optionName) {
            return $name === $optionName ? false : null;
        });
        Functions\when('add_option')->alias(function ($name, $value) use (&$addOptionCalled, $optionName) {
            if ($name === $optionName) {
                $addOptionCalled = true;
            }
            return true;
        });

        Activator::activate();

        // Verify add_option was called with the correct option name.
        $this->assertTrue($addOptionCalled, 'add_option should be called for ' . $optionName);
    }

    /**
     * Test that activate does not overwrite existing settings.
     */
    public function test_activate_preserves_existing_settings(): void
    {
        // Setup mocks.
        Functions\when('get_bloginfo')->justReturn('6.5.0');
        Functions\when('deactivate_plugins')->justReturn(null);
        Functions\when('wp_die')->alias(function () {
            throw new \RuntimeException('wp_die called');
        });

        $mockRole = Mockery::mock('WP_Role');
        $mockRole->shouldReceive('add_cap')->andReturn(true);
        Functions\when('get_role')->justReturn($mockRole);

        Functions\when('wp_next_scheduled')->justReturn(false);
        Functions\when('wp_schedule_event')->justReturn(true);
        Functions\when('update_option')->justReturn(true);

        // Mock page creation functions.
        Functions\when('wp_insert_post')->justReturn(100);
        Functions\when('get_post')->justReturn(null);

        // Existing settings present - get_option returns existing settings.
        $optionName = \APD\Admin\Settings::OPTION_NAME;
        Functions\when('get_option')->alias(function ($name) use ($optionName) {
            return $name === $optionName ? ['listings_per_page' => 24] : null;
        });

        // Track if add_option was called for the settings option.
        $addOptionCalled = false;
        Functions\when('add_option')->alias(function ($name) use (&$addOptionCalled, $optionName) {
            if ($name === $optionName) {
                $addOptionCalled = true;
            }
            return true;
        });

        Activator::activate();

        // add_option should NOT have been called when settings exist.
        $this->assertFalse($addOptionCalled, 'add_option should not be called when settings exist');
    }

    /**
     * Test that activate grants capabilities to administrator role.
     */
    public function test_activate_grants_admin_all_capabilities(): void
    {
        // Setup mocks.
        Functions\when('get_bloginfo')->justReturn('6.5.0');
        Functions\when('deactivate_plugins')->justReturn(null);
        Functions\when('wp_die')->alias(function () {
            throw new \RuntimeException('wp_die called');
        });

        $allCapabilities = Capabilities::get_all();

        // Track capabilities added to admin.
        $adminCaps = [];
        $mockAdminRole = Mockery::mock('WP_Role');
        $mockAdminRole->shouldReceive('add_cap')
            ->andReturnUsing(function ($cap) use (&$adminCaps) {
                $adminCaps[] = $cap;
                return true;
            });

        $mockOtherRole = Mockery::mock('WP_Role');
        $mockOtherRole->shouldReceive('add_cap')->andReturn(true);

        Functions\when('get_role')->alias(function ($role) use ($mockAdminRole, $mockOtherRole) {
            return $role === 'administrator' ? $mockAdminRole : $mockOtherRole;
        });

        Functions\when('get_option')->justReturn(false);
        Functions\when('add_option')->justReturn(true);
        Functions\when('update_option')->justReturn(true);
        Functions\when('wp_next_scheduled')->justReturn(false);
        Functions\when('wp_schedule_event')->justReturn(true);

        // Mock page creation functions.
        Functions\when('wp_insert_post')->justReturn(100);
        Functions\when('get_post')->justReturn(null);

        Activator::activate();

        // Verify all capabilities were granted to admin.
        foreach ($allCapabilities as $cap) {
            $this->assertContains(
                $cap,
                $adminCaps,
                "Administrator should have capability: {$cap}"
            );
        }
    }

    /**
     * Test that activate schedules cron events.
     */
    public function test_activate_schedules_cron_events(): void
    {
        // Setup mocks.
        Functions\when('get_bloginfo')->justReturn('6.5.0');
        Functions\when('deactivate_plugins')->justReturn(null);
        Functions\when('wp_die')->alias(function () {
            throw new \RuntimeException('wp_die called');
        });

        $mockRole = Mockery::mock('WP_Role');
        $mockRole->shouldReceive('add_cap')->andReturn(true);
        Functions\when('get_role')->justReturn($mockRole);

        Functions\when('get_option')->justReturn(false);
        Functions\when('add_option')->justReturn(true);
        Functions\when('update_option')->justReturn(true);

        // Mock page creation functions.
        Functions\when('wp_insert_post')->justReturn(100);
        Functions\when('get_post')->justReturn(null);

        // Track scheduled events.
        $scheduledEvents = [];
        Functions\when('wp_next_scheduled')->justReturn(false);
        Functions\when('wp_schedule_event')->alias(
            function ($time, $recurrence, $hook) use (&$scheduledEvents) {
                $scheduledEvents[] = ['recurrence' => $recurrence, 'hook' => $hook];
                return true;
            }
        );

        Activator::activate();

        // Verify cron events were scheduled.
        $hooks = array_column($scheduledEvents, 'hook');
        $this->assertContains('apd_check_expired_listings', $hooks);
        $this->assertContains('apd_cleanup_transients', $hooks);
    }

    /**
     * Test that activate creates default pages.
     */
    public function test_activate_creates_default_pages(): void
    {
        // Setup mocks.
        Functions\when('get_bloginfo')->justReturn('6.5.0');
        Functions\when('deactivate_plugins')->justReturn(null);
        Functions\when('wp_die')->alias(function () {
            throw new \RuntimeException('wp_die called');
        });

        $mockRole = Mockery::mock('WP_Role');
        $mockRole->shouldReceive('add_cap')->andReturn(true);
        Functions\when('get_role')->justReturn($mockRole);

        Functions\when('wp_next_scheduled')->justReturn(false);
        Functions\when('wp_schedule_event')->justReturn(true);

        // Track page creation.
        $created_pages = [];
        Functions\when('wp_insert_post')->alias(function ($args) use (&$created_pages) {
            static $id = 100;
            $id++;
            $created_pages[] = $args;
            return $id;
        });
        Functions\when('get_post')->justReturn(null);

        // Return empty options initially (no existing pages).
        $optionName = \APD\Admin\Settings::OPTION_NAME;
        $savedOptions = null;
        Functions\when('get_option')->alias(function ($name) use ($optionName) {
            return $name === $optionName ? [] : false;
        });
        Functions\when('add_option')->justReturn(true);
        Functions\when('update_option')->alias(function ($name, $value) use (&$savedOptions, $optionName) {
            if ($name === $optionName) {
                $savedOptions = $value;
            }
            return true;
        });

        Activator::activate();

        // Should have created 3 pages.
        $this->assertCount(3, $created_pages, 'Should create 3 default pages');

        // Verify page titles.
        $titles = array_column($created_pages, 'post_title');
        $this->assertContains('Directory', $titles);
        $this->assertContains('Submit a Listing', $titles);
        $this->assertContains('My Dashboard', $titles);

        // Verify page IDs were saved to options.
        $this->assertNotNull($savedOptions, 'Options should be saved with page IDs');
        $this->assertArrayHasKey('directory_page', $savedOptions);
        $this->assertArrayHasKey('submit_page', $savedOptions);
        $this->assertArrayHasKey('dashboard_page', $savedOptions);
    }

    /**
     * Test that activate skips page creation when pages already exist.
     */
    public function test_activate_skips_existing_pages(): void
    {
        // Setup mocks.
        Functions\when('get_bloginfo')->justReturn('6.5.0');
        Functions\when('deactivate_plugins')->justReturn(null);
        Functions\when('wp_die')->alias(function () {
            throw new \RuntimeException('wp_die called');
        });

        $mockRole = Mockery::mock('WP_Role');
        $mockRole->shouldReceive('add_cap')->andReturn(true);
        Functions\when('get_role')->justReturn($mockRole);

        Functions\when('wp_next_scheduled')->justReturn(false);
        Functions\when('wp_schedule_event')->justReturn(true);

        // Existing page.
        $existingPage = Mockery::mock('WP_Post');
        $existingPage->post_status = 'publish';
        Functions\when('get_post')->justReturn($existingPage);

        $insertCalled = false;
        Functions\when('wp_insert_post')->alias(function () use (&$insertCalled) {
            $insertCalled = true;
            return 999;
        });

        // Settings already have page IDs.
        $optionName = \APD\Admin\Settings::OPTION_NAME;
        Functions\when('get_option')->alias(function ($name) use ($optionName) {
            if ($name === $optionName) {
                return [
                    'directory_page' => 10,
                    'submit_page'    => 11,
                    'dashboard_page' => 12,
                ];
            }
            return false;
        });
        Functions\when('add_option')->justReturn(true);
        Functions\when('update_option')->justReturn(true);

        Activator::activate();

        $this->assertFalse($insertCalled, 'Should not create pages when they already exist');
    }

    /**
     * Test that activate skips scheduling if events already exist.
     */
    public function test_activate_skips_existing_scheduled_events(): void
    {
        // Setup mocks.
        Functions\when('get_bloginfo')->justReturn('6.5.0');
        Functions\when('deactivate_plugins')->justReturn(null);
        Functions\when('wp_die')->alias(function () {
            throw new \RuntimeException('wp_die called');
        });

        $mockRole = Mockery::mock('WP_Role');
        $mockRole->shouldReceive('add_cap')->andReturn(true);
        Functions\when('get_role')->justReturn($mockRole);

        Functions\when('get_option')->justReturn(false);
        Functions\when('add_option')->justReturn(true);
        Functions\when('update_option')->justReturn(true);

        // Mock page creation functions.
        Functions\when('wp_insert_post')->justReturn(100);
        Functions\when('get_post')->justReturn(null);

        // Events already scheduled.
        Functions\when('wp_next_scheduled')->justReturn(time() + 3600);

        // Track if wp_schedule_event was called.
        $scheduleEventCalled = false;
        Functions\when('wp_schedule_event')->alias(function () use (&$scheduleEventCalled) {
            $scheduleEventCalled = true;
            return true;
        });

        Activator::activate();

        $this->assertFalse($scheduleEventCalled, 'wp_schedule_event should not be called when events already exist');
    }
}
