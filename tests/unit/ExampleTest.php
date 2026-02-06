<?php
/**
 * Example unit test to verify test setup.
 *
 * @package APD\Tests\Unit
 */

declare(strict_types=1);

namespace APD\Tests\Unit;

use Brain\Monkey\Functions;

/**
 * Example unit test class.
 */
class ExampleTest extends UnitTestCase
{
    /**
     * Test that the test framework is working.
     */
    public function testFrameworkWorks(): void
    {
        $this->assertTrue(true);
    }

    /**
     * Test that Brain Monkey escaping stubs work.
     */
    public function testEscapingFunctions(): void
    {
        $text = 'Hello World';

        $this->assertEquals($text, esc_html($text));
        $this->assertEquals($text, esc_attr($text));
        $this->assertEquals($text, __($text, 'all-purpose-directory'));
    }

    /**
     * Test that sanitization stubs work.
     */
    public function testSanitizationFunctions(): void
    {
        $this->assertEquals('hello', sanitize_text_field('  hello  '));
        $this->assertEquals('test@example.com', sanitize_email('test@example.com'));
        $this->assertEquals(42, absint(-42));
        $this->assertEquals(42, absint('42'));
    }

    /**
     * Test that apply_filters returns the value by default.
     */
    public function testApplyFiltersReturnsValue(): void
    {
        $value = 'test value';
        $result = apply_filters('some_filter', $value);

        $this->assertEquals($value, $result);
    }

    /**
     * Test that we can use when() to return specific values.
     */
    public function testWhenGetOption(): void
    {
        // Use when() to return a specific value for specific arguments.
        Functions\when('get_option')
            ->justReturn(['key' => 'value']);

        $result = get_option('apd_options', []);

        $this->assertEquals(['key' => 'value'], $result);
    }

    /**
     * Test that add_action stub is called (not a strict expectation).
     */
    public function testAddActionIsCalled(): void
    {
        $callback = static function () {
            // Do something.
        };

        // The stub is already set up, just verify the function exists.
        add_action('init', $callback, 10, 1);

        // If we got here without error, the stub worked.
        $this->assertTrue(true);
    }
}
