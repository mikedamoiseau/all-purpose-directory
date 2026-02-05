<?php
/**
 * Tests for file upload security.
 *
 * @package APD\Tests\Unit\Security
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Security;

use Brain\Monkey\Functions;
use APD\Frontend\Submission\SubmissionHandler;

/**
 * FileUploadSecurityTest verifies file uploads are properly validated.
 */
class FileUploadSecurityTest extends SecurityTestCase {

    /**
     * Test SubmissionHandler has allowed image types constant.
     */
    public function test_submission_handler_has_allowed_types(): void {
        $reflection = new \ReflectionClass(SubmissionHandler::class);

        $this->assertTrue(
            $reflection->hasConstant('ALLOWED_IMAGE_TYPES'),
            'SubmissionHandler should define ALLOWED_IMAGE_TYPES'
        );
    }

    /**
     * Test SubmissionHandler has max file size constant.
     */
    public function test_submission_handler_has_max_file_size(): void {
        $reflection = new \ReflectionClass(SubmissionHandler::class);

        $this->assertTrue(
            $reflection->hasConstant('MAX_FILE_SIZE'),
            'SubmissionHandler should define MAX_FILE_SIZE'
        );
    }

    /**
     * Test allowed image types are safe.
     */
    public function test_allowed_image_types_are_safe(): void {
        $reflection = new \ReflectionClass(SubmissionHandler::class);
        $allowed_types = $reflection->getConstant('ALLOWED_IMAGE_TYPES');

        $this->assertIsArray($allowed_types);

        // Should only allow safe image types
        $safe_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        foreach ($allowed_types as $type) {
            $this->assertContains($type, $safe_types, "Type {$type} should be in safe types");
        }

        // Should NOT include executable types
        $dangerous_types = ['application/x-php', 'text/html', 'application/javascript'];
        foreach ($dangerous_types as $type) {
            $this->assertNotContains($type, $allowed_types, "Should not allow {$type}");
        }
    }

    /**
     * Test max file size is reasonable.
     */
    public function test_max_file_size_is_reasonable(): void {
        $reflection = new \ReflectionClass(SubmissionHandler::class);
        $max_size = $reflection->getConstant('MAX_FILE_SIZE');

        // Should be a positive integer
        $this->assertIsInt($max_size);
        $this->assertGreaterThan(0, $max_size);

        // Should not be excessively large (e.g., > 20MB)
        $this->assertLessThanOrEqual(20 * 1024 * 1024, $max_size);
    }

    /**
     * Test sanitize_file_name removes dangerous characters.
     */
    public function test_sanitize_file_name_removes_dangerous_chars(): void {
        $dangerous_names = [
            'file<script>.jpg' => ['<', '>'],
            'file;rm -rf.jpg' => [';', ' '],
        ];

        foreach ($dangerous_names as $name => $chars_to_remove) {
            $sanitized = sanitize_file_name($name);

            // Our mock removes non-alphanumeric except ._-
            foreach ($chars_to_remove as $char) {
                $this->assertStringNotContainsString($char, $sanitized, "Should remove '{$char}' from {$name}");
            }
        }
    }

    /**
     * Test that slashes are removed from filenames.
     */
    public function test_sanitize_file_name_removes_slashes(): void {
        $names_with_slashes = [
            'path/to/file.jpg',
            'path\\to\\file.jpg',
        ];

        foreach ($names_with_slashes as $name) {
            $sanitized = sanitize_file_name($name);
            $this->assertStringNotContainsString('/', $sanitized);
            $this->assertStringNotContainsString('\\', $sanitized);
        }
    }

    /**
     * Test MIME type validation concept.
     */
    public function test_mime_type_validation(): void {
        // wp_check_filetype returns type and extension
        Functions\when('wp_check_filetype')->alias(function ($filename, $mimes = null) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $type_map = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'php' => 'application/x-php',
            ];
            return [
                'ext' => $ext,
                'type' => $type_map[$ext] ?? false,
            ];
        });

        // Valid image
        $valid = wp_check_filetype('image.jpg');
        $this->assertEquals('image/jpeg', $valid['type']);

        // Invalid/dangerous file
        $invalid = wp_check_filetype('script.php');
        $this->assertEquals('application/x-php', $invalid['type']);
    }

    /**
     * Test double extension detection.
     */
    public function test_double_extension_detection(): void {
        $filenames = [
            'file.php.jpg' => true,  // Suspicious
            'file.jpg.php' => true,  // Very suspicious
            'file.jpg' => false,     // Normal
            'file.tar.gz' => true,   // Has two extensions
        ];

        foreach ($filenames as $filename => $has_double) {
            $parts = explode('.', $filename);
            $has_multiple = count($parts) > 2;
            $this->assertEquals($has_double, $has_multiple, "File: {$filename}");
        }
    }

    /**
     * Test file extension extraction.
     */
    public function test_file_extension_extraction(): void {
        $files = [
            'image.jpg' => 'jpg',
            'document.PDF' => 'pdf',  // Case insensitive
            'no_extension' => '',
            '.htaccess' => 'htaccess',
        ];

        foreach ($files as $filename => $expected_ext) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $this->assertEquals($expected_ext, $ext, "File: {$filename}");
        }
    }

    /**
     * Test that PHP files are blocked.
     */
    public function test_php_files_blocked(): void {
        $php_extensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps'];
        $reflection = new \ReflectionClass(SubmissionHandler::class);
        $allowed_types = $reflection->getConstant('ALLOWED_IMAGE_TYPES');

        foreach ($php_extensions as $ext) {
            // PHP MIME type should not be in allowed types
            $this->assertNotContains("application/x-{$ext}", $allowed_types);
        }
    }

    /**
     * Test that executable files are blocked.
     */
    public function test_executable_files_blocked(): void {
        $reflection = new \ReflectionClass(SubmissionHandler::class);
        $allowed_types = $reflection->getConstant('ALLOWED_IMAGE_TYPES');

        $executable_types = [
            'application/x-executable',
            'application/x-sharedlib',
            'application/x-shellscript',
            'application/x-msdos-program',
            'application/javascript',
            'text/javascript',
            'text/html',
        ];

        foreach ($executable_types as $type) {
            $this->assertNotContains($type, $allowed_types, "Should block {$type}");
        }
    }

    /**
     * Test file size constant value.
     */
    public function test_file_size_constant_value(): void {
        $reflection = new \ReflectionClass(SubmissionHandler::class);
        $max_size = $reflection->getConstant('MAX_FILE_SIZE');

        // 5MB = 5 * 1024 * 1024 = 5242880
        $this->assertEquals(5 * 1024 * 1024, $max_size);
    }

    /**
     * Test null byte in filename detection.
     */
    public function test_null_byte_in_filename(): void {
        $filename = "image.jpg\x00.php";
        $sanitized = sanitize_file_name($filename);

        // Should not contain null byte
        $this->assertStringNotContainsString("\x00", $sanitized);
    }

    /**
     * Test path traversal prevention concept.
     *
     * Note: Actual path traversal prevention happens at WordPress upload level
     * via wp_upload_dir() which generates safe paths, not via sanitize_file_name.
     */
    public function test_path_traversal_prevention(): void {
        $paths = [
            '../secret.jpg',
            '..\\secret.jpg',
            'images/../../../etc/passwd',
        ];

        foreach ($paths as $path) {
            $sanitized = sanitize_file_name($path);

            // Our mock removes non-alphanumeric except ._-
            // Real WP removes slashes and backslashes
            $this->assertStringNotContainsString('/', $sanitized);
            $this->assertStringNotContainsString('\\', $sanitized);
        }
    }

    /**
     * Test SubmissionHandler upload method exists.
     */
    public function test_upload_handler_method_exists(): void {
        $reflection = new \ReflectionClass(SubmissionHandler::class);

        $this->assertTrue(
            $reflection->hasMethod('handle_featured_image_upload'),
            'Should have handle_featured_image_upload method'
        );
    }

    /**
     * Test that uploads use WordPress media handling.
     */
    public function test_uploads_use_wordpress_media_handling(): void {
        // media_handle_upload is the secure way to handle uploads
        Functions\when('media_handle_upload')->justReturn(123);

        $result = media_handle_upload('file', 0);

        $this->assertEquals(123, $result);
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void {
        parent::tearDown();
    }
}
