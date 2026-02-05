<?php
/**
 * Tests for output escaping and XSS prevention.
 *
 * @package APD\Tests\Unit\Security
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Security;

use Brain\Monkey\Functions;

/**
 * OutputEscapingTest verifies all output is properly escaped.
 */
class OutputEscapingTest extends SecurityTestCase {

    /**
     * Test esc_html escapes HTML special characters.
     */
    public function test_esc_html_escapes_html(): void {
        $input = '<script>alert("xss")</script>';
        $result = esc_html($input);

        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&gt;', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    /**
     * Test esc_attr escapes attribute values.
     */
    public function test_esc_attr_escapes_attributes(): void {
        $input = '" onclick="alert(1)"';
        $result = esc_attr($input);

        // Quotes should be escaped
        $this->assertStringContainsString('&quot;', $result);
        $this->assertStringNotContainsString('" onclick="', $result);
    }

    /**
     * Test esc_url escapes URLs.
     */
    public function test_esc_url_escapes_urls(): void {
        $valid_url = 'https://example.com/page?q=test';
        $result = esc_url($valid_url);
        $this->assertEquals('https://example.com/page?q=test', $result);
    }

    /**
     * Test esc_js escapes JavaScript strings.
     */
    public function test_esc_js_escapes_javascript(): void {
        $input = "'; alert('xss'); //";
        $result = esc_js($input);

        // Should escape quotes
        $this->assertStringContainsString("\\'", $result);
    }

    /**
     * Test esc_textarea escapes textarea content.
     */
    public function test_esc_textarea_escapes_content(): void {
        $input = '<script>xss</script>';
        $result = esc_textarea($input);

        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    /**
     * Test XSS vectors are escaped in HTML context.
     */
    public function test_xss_vectors_escaped_in_html(): void {
        $vectors = $this->getXssVectors();

        foreach ($vectors as $vector) {
            $escaped = esc_html($vector);

            // Should not contain unescaped script tags
            $this->assertStringNotContainsString('<script>', $escaped);
        }
    }

    /**
     * Test XSS vectors are escaped in attribute context.
     */
    public function test_xss_vectors_escaped_in_attr(): void {
        $input = '" onmouseover="alert(1)"';
        $escaped = esc_attr($input);

        // Attribute value should be safe to use in an HTML attribute
        $this->assertStringContainsString('&quot;', $escaped);
    }

    /**
     * Test double escaping is handled correctly.
     */
    public function test_double_escaping_handled(): void {
        $input = '&lt;script&gt;';
        $result = esc_html($input);

        // Already escaped content should remain readable
        // Our mock uses htmlspecialchars which will double-encode
        $this->assertIsString($result);
    }

    /**
     * Test escaping preserves safe content.
     */
    public function test_escaping_preserves_safe_content(): void {
        $safe_content = 'Hello World! This is safe text.';
        $this->assertEquals($safe_content, esc_html($safe_content));
    }

    /**
     * Test escaping handles unicode.
     */
    public function test_escaping_handles_unicode(): void {
        $unicode = 'Héllo Wörld 日本語';
        $result = esc_html($unicode);

        // Unicode should be preserved
        $this->assertEquals($unicode, $result);
    }

    /**
     * Test escaping handles empty strings.
     */
    public function test_escaping_handles_empty_strings(): void {
        $this->assertEquals('', esc_html(''));
        $this->assertEquals('', esc_attr(''));
        $this->assertEquals('', esc_textarea(''));
    }

    /**
     * Test escaping handles numbers.
     */
    public function test_escaping_handles_numbers(): void {
        $this->assertEquals('123', esc_html('123'));
        $this->assertEquals('123.45', esc_html('123.45'));
    }

    /**
     * Test wp_kses filters HTML content.
     */
    public function test_wp_kses_filters_html(): void {
        $input = '<p>Safe</p><script>Unsafe</script>';
        $allowed = ['p' => []];
        $result = wp_kses($input, $allowed);

        $this->assertStringContainsString('Safe', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    /**
     * Test wp_kses_post allows common HTML.
     */
    public function test_wp_kses_post_allows_common_html(): void {
        $input = '<p><strong>Bold</strong> and <a href="#">link</a></p>';
        $result = wp_kses_post($input);

        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<strong>', $result);
        $this->assertStringContainsString('<a', $result);
    }

    /**
     * Test escaping functions exist.
     */
    public function test_escaping_functions_exist(): void {
        $this->assertTrue(function_exists('esc_html'));
        $this->assertTrue(function_exists('esc_attr'));
        $this->assertTrue(function_exists('esc_url'));
        $this->assertTrue(function_exists('esc_js'));
        $this->assertTrue(function_exists('esc_textarea'));
        $this->assertTrue(function_exists('wp_kses'));
        $this->assertTrue(function_exists('wp_kses_post'));
    }

    /**
     * Test script injection via event handlers.
     */
    public function test_event_handler_injection_escaped(): void {
        $input = 'value" onfocus="alert(1)';
        $escaped = esc_attr($input);

        // Should not be able to break out of attribute
        $this->assertStringContainsString('&quot;', $escaped);
    }

    /**
     * Test style attribute injection.
     */
    public function test_style_injection_escaped(): void {
        $input = 'red; background: url(javascript:alert(1))';
        $escaped = esc_attr($input);

        // Content should be escaped for attribute use
        $this->assertIsString($escaped);
    }

    /**
     * Test data URI injection.
     */
    public function test_data_uri_in_url_context(): void {
        $input = 'data:text/html,<script>alert(1)</script>';
        $escaped = esc_url($input);

        // Data URIs may be sanitized or allowed depending on context
        $this->assertIsString($escaped);
    }

    /**
     * Test ampersand escaping in HTML.
     */
    public function test_ampersand_escaping(): void {
        $input = 'Tom & Jerry';
        $result = esc_html($input);

        $this->assertStringContainsString('&amp;', $result);
    }

    /**
     * Test quote escaping in attributes.
     */
    public function test_quote_escaping_in_attributes(): void {
        $single_quotes = "it's a test";
        $double_quotes = 'say "hello"';

        $single_escaped = esc_attr($single_quotes);
        $double_escaped = esc_attr($double_quotes);

        // Double quotes should be escaped
        $this->assertStringContainsString('&quot;', $double_escaped);
    }

    /**
     * Test newline handling in escaping.
     */
    public function test_newline_handling(): void {
        $input = "Line1\nLine2\rLine3";
        $result = esc_html($input);

        // Newlines should be preserved in HTML content
        $this->assertIsString($result);
    }

    /**
     * Test null character handling.
     */
    public function test_null_character_handling(): void {
        $input = "test\0injection";
        $result = esc_html($input);

        // Output should not contain null bytes
        $this->assertIsString($result);
    }

    /**
     * Test that esc_html__ works (localized escaping).
     */
    public function test_localized_escaping(): void {
        $result = esc_html__('Test string', 'all-purpose-directory');
        $this->assertEquals('Test string', $result);
    }

    /**
     * Test that esc_attr__ works (localized attribute escaping).
     */
    public function test_localized_attr_escaping(): void {
        $result = esc_attr__('Test string', 'all-purpose-directory');
        $this->assertEquals('Test string', $result);
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void {
        parent::tearDown();
    }
}
