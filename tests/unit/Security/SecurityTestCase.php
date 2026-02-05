<?php
/**
 * Base test case for security tests.
 *
 * @package APD\Tests\Unit\Security
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Security;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * SecurityTestCase provides common setup for security tests.
 */
abstract class SecurityTestCase extends TestCase {

    use MockeryPHPUnitIntegration;

    /**
     * Set up test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        $this->setup_common_mocks();
    }

    /**
     * Tear down test environment.
     */
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Set up common WordPress function mocks.
     */
    protected function setup_common_mocks(): void {
        // Translation functions
        Functions\stubs([
            '__' => function ($text, $domain = 'default') {
                return $text;
            },
            '_e' => function ($text, $domain = 'default') {
                echo $text;
            },
            'esc_html__' => function ($text, $domain = 'default') {
                return $text;
            },
            'esc_attr__' => function ($text, $domain = 'default') {
                return $text;
            },
            '_x' => function ($text, $context, $domain = 'default') {
                return $text;
            },
            '_n' => function ($single, $plural, $number, $domain = 'default') {
                return $number === 1 ? $single : $plural;
            },
        ]);

        // Sanitization functions
        Functions\stubs([
            'sanitize_text_field' => function ($str) {
                return trim(strip_tags((string) $str));
            },
            'sanitize_textarea_field' => function ($str) {
                return trim(strip_tags((string) $str));
            },
            'sanitize_email' => function ($email) {
                return filter_var($email, FILTER_SANITIZE_EMAIL);
            },
            'sanitize_key' => function ($key) {
                return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key));
            },
            'sanitize_title' => function ($title) {
                return trim(strip_tags((string) $title));
            },
            'sanitize_file_name' => function ($name) {
                return preg_replace('/[^a-zA-Z0-9._-]/', '', (string) $name);
            },
            'sanitize_html_class' => function ($class) {
                return preg_replace('/[^A-Za-z0-9_-]/', '', (string) $class);
            },
            'sanitize_user' => function ($user) {
                return preg_replace('/[^a-zA-Z0-9 _.\-@]/', '', (string) $user);
            },
            'sanitize_mime_type' => function ($mime) {
                return preg_replace('/[^-+*.a-zA-Z0-9\/]/', '', (string) $mime);
            },
            'absint' => function ($val) {
                return abs((int) $val);
            },
            'wp_unslash' => function ($value) {
                return is_array($value) ? array_map('stripslashes', $value) : stripslashes((string) $value);
            },
        ]);

        // Escaping functions
        Functions\stubs([
            'esc_html' => function ($text) {
                return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
            },
            'esc_attr' => function ($text) {
                return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
            },
            'esc_url' => function ($url) {
                return filter_var($url, FILTER_SANITIZE_URL);
            },
            'esc_url_raw' => function ($url) {
                return filter_var($url, FILTER_SANITIZE_URL);
            },
            'esc_js' => function ($text) {
                return addslashes((string) $text);
            },
            'esc_textarea' => function ($text) {
                return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
            },
            'wp_kses' => function ($content, $allowed_html) {
                return strip_tags((string) $content, '<p><br><a><strong><em><ul><ol><li>');
            },
            'wp_kses_post' => function ($content) {
                return strip_tags((string) $content, '<p><br><a><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><img>');
            },
        ]);

        // Nonce functions - only define basic stubs here
        // Individual tests should use Functions\expect() for specific verification scenarios
        Functions\when('wp_create_nonce')->justReturn('test_nonce_12345');
        Functions\when('wp_nonce_field')->justReturn('<input type="hidden" name="_wpnonce" value="test_nonce" />');

        // User functions
        Functions\when('is_user_logged_in')->justReturn(false);
        Functions\when('get_current_user_id')->justReturn(0);
        Functions\when('current_user_can')->justReturn(false);
        Functions\when('user_can')->justReturn(false);

        // Misc WordPress functions
        Functions\when('wp_doing_ajax')->justReturn(false);
        Functions\when('is_admin')->justReturn(false);
        Functions\when('wp_die')->alias(function ($message = '', $title = '', $args = []) {
            throw new \RuntimeException($message);
        });
        Functions\when('wp_send_json_error')->alias(function ($data = null, $status_code = null) {
            throw new \RuntimeException(is_array($data) ? ($data['message'] ?? 'error') : 'error');
        });
        Functions\when('wp_send_json_success')->alias(function ($data = null, $status_code = null) {
            return true;
        });
    }

    /**
     * Mock a valid nonce verification.
     *
     * @param string $nonce_value The nonce value to validate.
     * @param string $action      The nonce action.
     */
    protected function mock_valid_nonce(string $nonce_value, string $action): void {
        Functions\expect('wp_verify_nonce')
            ->with($nonce_value, $action)
            ->andReturn(1);
    }

    /**
     * Mock an invalid nonce verification.
     *
     * @param string $nonce_value The nonce value to validate.
     * @param string $action      The nonce action.
     */
    protected function mock_invalid_nonce(string $nonce_value, string $action): void {
        Functions\expect('wp_verify_nonce')
            ->with($nonce_value, $action)
            ->andReturn(false);
    }

    /**
     * Mock a logged-in user with specific capabilities.
     *
     * @param int   $user_id      The user ID.
     * @param array $capabilities Array of capabilities the user has.
     */
    protected function mock_logged_in_user(int $user_id, array $capabilities = []): void {
        Functions\when('is_user_logged_in')->justReturn(true);
        Functions\when('get_current_user_id')->justReturn($user_id);

        Functions\when('current_user_can')->alias(function ($cap, ...$args) use ($capabilities) {
            return in_array($cap, $capabilities, true);
        });
    }

    /**
     * Mock a guest user (not logged in).
     */
    protected function mock_guest_user(): void {
        Functions\when('is_user_logged_in')->justReturn(false);
        Functions\when('get_current_user_id')->justReturn(0);
        Functions\when('current_user_can')->justReturn(false);
    }

    /**
     * Assert that a string contains XSS attack vectors that should be escaped.
     *
     * @param string $input    The input containing XSS.
     * @param string $output   The sanitized output.
     * @param string $message  Optional assertion message.
     */
    protected function assertXssSanitized(string $input, string $output, string $message = ''): void {
        // Check that script tags are removed or escaped
        $this->assertStringNotContainsString('<script>', $output, $message ?: 'Script tags should be removed');
        $this->assertStringNotContainsString('javascript:', strtolower($output), $message ?: 'Javascript protocol should be removed');
        $this->assertStringNotContainsString('onerror=', strtolower($output), $message ?: 'Event handlers should be removed');
        $this->assertStringNotContainsString('onclick=', strtolower($output), $message ?: 'Event handlers should be removed');
    }

    /**
     * Assert that SQL injection vectors are not present in output.
     *
     * @param string $output  The query or output to check.
     * @param string $message Optional assertion message.
     */
    protected function assertSqlSafe(string $output, string $message = ''): void {
        // These should be parameterized, not concatenated
        $this->assertStringNotContainsString("'; DROP TABLE", $output, $message ?: 'SQL injection should be prevented');
        $this->assertStringNotContainsString('1=1', $output, $message ?: 'SQL injection pattern should be prevented');
        $this->assertStringNotContainsString('OR 1=1', strtoupper($output), $message ?: 'SQL injection pattern should be prevented');
    }

    /**
     * Get common XSS test vectors.
     *
     * @return array Array of XSS attack strings.
     */
    protected function getXssVectors(): array {
        return [
            '<script>alert("xss")</script>',
            '<img src="x" onerror="alert(1)">',
            '<a href="javascript:alert(1)">click</a>',
            '"><script>alert(document.cookie)</script>',
            "' onclick='alert(1)'",
            '<svg onload="alert(1)">',
            '<body onload="alert(1)">',
            '<iframe src="javascript:alert(1)">',
            '<input onfocus="alert(1)" autofocus>',
            '<marquee onstart="alert(1)">',
            '<div style="background:url(javascript:alert(1))">',
            "';alert(String.fromCharCode(88,83,83))//",
        ];
    }

    /**
     * Get common SQL injection test vectors.
     *
     * @return array Array of SQL injection strings.
     */
    protected function getSqlInjectionVectors(): array {
        return [
            "'; DROP TABLE wp_posts; --",
            "1' OR '1'='1",
            "1; DELETE FROM wp_users WHERE 1=1; --",
            "' UNION SELECT * FROM wp_users --",
            "1' AND '1'='1",
            "admin'--",
            "1/**/OR/**/1=1",
            "' OR ''='",
            "; EXEC xp_cmdshell('dir'); --",
            "1' ORDER BY 1--+",
        ];
    }
}
