<?php
/**
 * Base test case for unit tests.
 *
 * Uses Brain Monkey to mock WordPress functions for fast unit testing
 * without requiring a WordPress installation.
 *
 * @package APD\Tests\Unit
 */

declare(strict_types=1);

namespace APD\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Base test case for unit tests.
 */
abstract class UnitTestCase extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Set up before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Monkey\setUp();

        // Set up common WordPress function stubs.
        $this->setUpWordPressFunctions();
    }

    /**
     * Tear down after each test.
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();

        parent::tearDown();
    }

    /**
     * Set up common WordPress function stubs.
     *
     * Override this method in child classes to add more stubs.
     */
    protected function setUpWordPressFunctions(): void
    {
        // Common escaping functions - return input unchanged.
        Functions\stubs([
            'esc_html'       => static fn($text) => $text,
            'esc_attr'       => static fn($text) => $text,
            'esc_url'        => static fn($url) => $url,
            'esc_html__'     => static fn($text, $domain = 'default') => $text,
            'esc_attr__'     => static fn($text, $domain = 'default') => $text,
            '__'             => static fn($text, $domain = 'default') => $text,
            '_e'             => static fn($text, $domain = 'default') => print($text),
            '_x'             => static fn($text, $context, $domain = 'default') => $text,
            'esc_html_x'     => static fn($text, $context, $domain = 'default') => $text,
            'esc_attr_x'     => static fn($text, $context, $domain = 'default') => $text,
        ]);

        // Sanitization functions.
        Functions\stubs([
            'sanitize_text_field'   => static fn($str) => trim(strip_tags($str)),
            'sanitize_email'        => static fn($email) => filter_var($email, FILTER_SANITIZE_EMAIL),
            'sanitize_title'        => static fn($title) => strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', $title)),
            'sanitize_key'          => static fn($key) => preg_replace('/[^a-z0-9_\-]/', '', strtolower($key)),
            'absint'                => static fn($val) => abs((int) $val),
            'wp_unslash'            => static fn($value) => is_string($value) ? stripslashes($value) : $value,
        ]);

        // Utility functions.
        Functions\stubs([
            'wp_parse_args' => static function ($args, $defaults = []) {
                if (is_object($args)) {
                    $args = get_object_vars($args);
                }
                return array_merge($defaults, $args);
            },
            'wp_generate_uuid4' => static fn() => sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            ),
        ]);

        // Hook functions - do nothing by default.
        // Note: do_action and apply_filters are handled by Brain\Monkey's
        // hook system (via Monkey\setUp). Do NOT stub them here or
        // Actions\expectDone / Filters\expectApplied will not work.
        Functions\stubs([
            'add_action'       => null,
            'add_filter'       => null,
            'remove_action'    => null,
            'remove_filter'    => null,
            'has_action'       => false,
            'has_filter'       => false,
            'did_action'       => 0,
        ]);

        // Option functions.
        Functions\stubs([
            'get_option'    => false,
            'update_option' => true,
            'delete_option' => true,
            'add_option'    => true,
        ]);

        // Post meta functions.
        Functions\stubs([
            'get_post_meta'    => '',
            'update_post_meta' => true,
            'delete_post_meta' => true,
            'add_post_meta'    => 1,
        ]);

        // User meta functions.
        Functions\stubs([
            'get_user_meta'    => '',
            'update_user_meta' => true,
            'delete_user_meta' => true,
            'add_user_meta'    => 1,
        ]);

        // Current user functions.
        Functions\stubs([
            'get_current_user_id' => 0,
            'is_user_logged_in'   => false,
            'current_user_can'    => false,
        ]);

        // Plugin functions.
        Functions\stubs([
            'plugin_dir_path' => static fn($file) => dirname($file) . '/',
            'plugin_dir_url'  => static fn($file) => 'https://example.com/wp-content/plugins/' . basename(dirname($file)) . '/',
            'plugins_url'     => static fn($path = '', $plugin = '') => 'https://example.com/wp-content/plugins/' . $path,
        ]);
    }

    /**
     * Expect a WordPress action to be added.
     *
     * @param string   $action   The action name.
     * @param callable $callback The callback.
     * @param int      $priority Optional. Priority.
     * @param int      $args     Optional. Number of arguments.
     */
    protected function expectActionAdded(string $action, callable $callback, int $priority = 10, int $args = 1): void
    {
        Functions\expect('add_action')
            ->once()
            ->with($action, $callback, $priority, $args);
    }

    /**
     * Expect a WordPress filter to be added.
     *
     * @param string   $filter   The filter name.
     * @param callable $callback The callback.
     * @param int      $priority Optional. Priority.
     * @param int      $args     Optional. Number of arguments.
     */
    protected function expectFilterAdded(string $filter, callable $callback, int $priority = 10, int $args = 1): void
    {
        Functions\expect('add_filter')
            ->once()
            ->with($filter, $callback, $priority, $args);
    }

    /**
     * Mock get_option to return a specific value.
     *
     * Note: Use when() to override the default stub set in setUpWordPressFunctions().
     *
     * @param string $option_name The option name (currently ignored - returns for all calls).
     * @param mixed  $value       The value to return.
     */
    protected function mockOption(string $option_name, mixed $value): void
    {
        Functions\when('get_option')->justReturn($value);
    }

    /**
     * Mock get_post_meta to return a specific value.
     *
     * Note: Use when() to override the default stub set in setUpWordPressFunctions().
     *
     * @param int    $post_id  The post ID (currently ignored - returns for all calls).
     * @param string $meta_key The meta key (currently ignored - returns for all calls).
     * @param mixed  $value    The value to return.
     */
    protected function mockPostMeta(int $post_id, string $meta_key, mixed $value): void
    {
        Functions\when('get_post_meta')->justReturn($value);
    }
}
