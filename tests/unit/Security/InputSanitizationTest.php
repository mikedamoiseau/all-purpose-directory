<?php
/**
 * Tests for input sanitization across the plugin.
 *
 * @package APD\Tests\Unit\Security
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Security;

use Brain\Monkey\Functions;
use APD\Contact\ContactHandler;
use APD\Contact\ContactForm;

/**
 * InputSanitizationTest verifies sanitization functions are called correctly.
 *
 * Note: These tests verify that sanitization functions are used properly.
 * The actual sanitization implementation is WordPress core and is not tested here.
 */
class InputSanitizationTest extends SecurityTestCase {

    /**
     * Test sanitize_text_field is called and strips tags.
     */
    public function test_sanitize_text_field_strips_tags(): void {
        $input = '<script>alert("xss")</script>Hello';
        $result = sanitize_text_field($input);

        // Our mock strips tags
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('Hello', $result);
    }

    /**
     * Test sanitize_text_field trims whitespace.
     */
    public function test_sanitize_text_field_trims_whitespace(): void {
        $input = '  Hello World  ';
        $result = sanitize_text_field($input);

        // Our mock trims whitespace
        $this->assertEquals('Hello World', $result);
    }

    /**
     * Test absint returns non-negative integer.
     */
    public function test_absint_returns_non_negative_integer(): void {
        $this->assertEquals(5, absint(5));
        $this->assertEquals(5, absint(-5));
        $this->assertEquals(5, absint('5'));
        $this->assertEquals(5, absint('-5'));
        $this->assertEquals(0, absint('not-a-number'));
    }

    /**
     * Test sanitize_key lowercases and strips invalid characters.
     */
    public function test_sanitize_key_strips_invalid_chars(): void {
        $input = 'My_Key-123!@#';
        $result = sanitize_key($input);

        $this->assertEquals('my_key-123', $result);
        $this->assertStringNotContainsString('!', $result);
        $this->assertStringNotContainsString('@', $result);
    }

    /**
     * Test XSS vectors with script tags are sanitized.
     */
    public function test_script_tags_are_removed(): void {
        $vectors = [
            '<script>alert("xss")</script>',
            '<SCRIPT>alert("xss")</SCRIPT>',
            '<script src="evil.js"></script>',
        ];

        foreach ($vectors as $vector) {
            $sanitized = sanitize_text_field($vector);
            $this->assertStringNotContainsString('<script', strtolower($sanitized));
        }
    }

    /**
     * Test ContactHandler uses sanitization on listing_id.
     */
    public function test_contact_handler_sanitizes_listing_id(): void {
        $_POST = [
            'listing_id' => '123',
            'contact_name' => 'John',
            'contact_email' => 'john@example.com',
            'contact_message' => 'Hello World',
        ];

        Functions\when('wp_verify_nonce')->justReturn(true);

        $handler = new ContactHandler();
        $reflection = new \ReflectionClass($handler);
        $method = $reflection->getMethod('get_sanitized_data');
        // setAccessible not needed in PHP 8.1+

        $data = $method->invoke($handler);

        // listing_id should be an integer
        $this->assertIsInt($data['listing_id']);
        $this->assertEquals(123, $data['listing_id']);
    }

    /**
     * Test ContactHandler strips tags from name.
     */
    public function test_contact_handler_sanitizes_name(): void {
        $_POST = [
            'listing_id' => '123',
            'contact_name' => '<b>John</b>',
            'contact_email' => 'john@example.com',
            'contact_message' => 'Hello',
        ];

        Functions\when('wp_verify_nonce')->justReturn(true);

        $handler = new ContactHandler();
        $reflection = new \ReflectionClass($handler);
        $method = $reflection->getMethod('get_sanitized_data');
        // setAccessible not needed in PHP 8.1+

        $data = $method->invoke($handler);

        $this->assertStringNotContainsString('<b>', $data['contact_name']);
        $this->assertStringContainsString('John', $data['contact_name']);
    }

    /**
     * Test wp_kses_post allows safe HTML.
     */
    public function test_wp_kses_post_allows_safe_html(): void {
        $input = '<p>Hello <strong>World</strong></p>';
        $result = wp_kses_post($input);

        // Our mock allows common tags
        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<strong>', $result);
    }

    /**
     * Test wp_kses_post strips script tags.
     */
    public function test_wp_kses_post_strips_script_tags(): void {
        $input = '<script>alert("xss")</script><p>Safe content</p>';
        $result = wp_kses_post($input);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('<p>', $result);
    }

    /**
     * Test sanitize_textarea_field strips tags.
     */
    public function test_sanitize_textarea_field_strips_tags(): void {
        $input = "Line 1\n<script>bad</script>Line 2";
        $result = sanitize_textarea_field($input);

        $this->assertStringNotContainsString('<script>', $result);
    }

    /**
     * Test numeric input handling.
     */
    public function test_numeric_input_handling(): void {
        $this->assertEquals(42, absint('42'));
        $this->assertEquals(0, absint('abc'));
        $this->assertEquals(42, absint('42.5'));
    }

    /**
     * Test empty input handling.
     */
    public function test_empty_input_handling(): void {
        $this->assertEquals('', sanitize_text_field(''));
        $this->assertEquals('', sanitize_email(''));
        $this->assertEquals(0, absint(''));
    }

    /**
     * Test array input sanitization with array_map.
     */
    public function test_array_input_sanitization(): void {
        $input = ['<script>xss</script>', 'normal text', '123'];

        $sanitized = array_map('sanitize_text_field', $input);

        $this->assertStringNotContainsString('<script>', $sanitized[0]);
        $this->assertEquals('normal text', $sanitized[1]);
        $this->assertEquals('123', $sanitized[2]);
    }

    /**
     * Test unicode input is preserved.
     */
    public function test_unicode_input_preserved(): void {
        $input = 'Héllo Wörld 日本語';
        $result = sanitize_text_field($input);

        $this->assertEquals('Héllo Wörld 日本語', $result);
    }

    /**
     * Test esc_html encodes special characters.
     */
    public function test_esc_html_encodes_special_chars(): void {
        $input = '<div>Test & "quote"</div>';
        $result = esc_html($input);

        // Should encode < > & "
        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&gt;', $result);
    }

    /**
     * Test esc_attr encodes special characters.
     */
    public function test_esc_attr_encodes_special_chars(): void {
        $input = '"onclick="alert(1)"';
        $result = esc_attr($input);

        // Should encode quotes
        $this->assertStringNotContainsString('"onclick="', $result);
    }

    /**
     * Test oversized input handling.
     */
    public function test_oversized_input(): void {
        $input = str_repeat('A', 100000);
        $result = sanitize_text_field($input);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test special characters in text fields.
     */
    public function test_special_characters_in_text(): void {
        $special_chars = "Test's \"quoted\" & chars";
        $result = sanitize_text_field($special_chars);

        // Quotes and ampersand should remain
        $this->assertStringContainsString("'", $result);
        $this->assertStringContainsString('&', $result);
    }

    /**
     * Test sanitization preserves valid content.
     */
    public function test_sanitization_preserves_valid_content(): void {
        $valid_inputs = [
            'Simple text' => 'Simple text',
            'Email: test@example.com' => 'Email: test@example.com',
            'Number: 12345' => 'Number: 12345',
            'Special: & % $ #' => 'Special: & % $ #',
        ];

        foreach ($valid_inputs as $input => $expected) {
            $result = sanitize_text_field($input);
            $this->assertEquals($expected, $result, "Input: {$input}");
        }
    }

    /**
     * Test that sanitization functions are defined.
     */
    public function test_sanitization_functions_exist(): void {
        $this->assertTrue(function_exists('sanitize_text_field'));
        $this->assertTrue(function_exists('sanitize_textarea_field'));
        $this->assertTrue(function_exists('sanitize_email'));
        $this->assertTrue(function_exists('sanitize_key'));
        $this->assertTrue(function_exists('absint'));
        $this->assertTrue(function_exists('esc_html'));
        $this->assertTrue(function_exists('esc_attr'));
        $this->assertTrue(function_exists('esc_url'));
        $this->assertTrue(function_exists('wp_kses_post'));
    }

    /**
     * Test wp_unslash removes slashes.
     */
    public function test_wp_unslash_removes_slashes(): void {
        $input = "It\\'s a test";
        $result = wp_unslash($input);

        $this->assertEquals("It's a test", $result);
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void {
        $_POST = [];
        parent::tearDown();
    }
}
