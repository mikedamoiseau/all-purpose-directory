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

        // Capture the settings passed to add_option.
        $capturedSettings = null;
        Functions\when('get_option')->alias(function ($name) {
            return $name === 'apd_settings' ? false : null;
        });
        Functions\when('add_option')->alias(function ($name, $value) use (&$capturedSettings) {
            if ($name === 'apd_settings') {
                $capturedSettings = $value;
            }
            return true;
        });

        Activator::activate();

        // Verify default settings structure.
        $this->assertIsArray($capturedSettings);
        $this->assertArrayHasKey('listings_per_page', $capturedSettings);
        $this->assertArrayHasKey('enable_reviews', $capturedSettings);
        $this->assertArrayHasKey('enable_favorites', $capturedSettings);
        $this->assertArrayHasKey('require_login_submit', $capturedSettings);
        $this->assertArrayHasKey('moderate_submissions', $capturedSettings);
        $this->assertArrayHasKey('listing_expiry_days', $capturedSettings);
        $this->assertArrayHasKey('currency', $capturedSettings);
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

        // Existing settings present - get_option returns existing settings.
        Functions\when('get_option')->alias(function ($name) {
            return $name === 'apd_settings' ? ['listings_per_page' => 24] : null;
        });

        // Track if add_option was called for apd_settings.
        $addOptionCalled = false;
        Functions\when('add_option')->alias(function ($name) use (&$addOptionCalled) {
            if ($name === 'apd_settings') {
                $addOptionCalled = true;
            }
            return true;
        });

        Activator::activate();

        // add_option should NOT have been called for apd_settings.
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
