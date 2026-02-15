<?php
/**
 * Tests for file upload security contracts.
 *
 * @package APD\Tests\Unit\Security
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Security;

use APD\Frontend\Submission\SubmissionHandler;

/**
 * FileUploadSecurityTest verifies plugin-side upload guardrails.
 *
 * Note: WordPress file helper behavior is covered by integration tests.
 */
class FileUploadSecurityTest extends SecurityTestCase {

	/**
	 * Test SubmissionHandler defines allowed image types constant.
	 */
	public function test_submission_handler_has_allowed_image_types_constant(): void {
		$reflection = new \ReflectionClass( SubmissionHandler::class );

		$this->assertTrue( $reflection->hasConstant( 'ALLOWED_IMAGE_TYPES' ) );
	}

	/**
	 * Test allowed image types include only expected safe image MIME types.
	 */
	public function test_allowed_image_types_match_expected_safe_set(): void {
		$reflection    = new \ReflectionClass( SubmissionHandler::class );
		$allowed_types = $reflection->getConstant( 'ALLOWED_IMAGE_TYPES' );

		$this->assertSame(
			[
				'image/jpeg',
				'image/png',
				'image/gif',
				'image/webp',
			],
			$allowed_types
		);
	}

	/**
	 * Test SubmissionHandler defines a bounded max file size.
	 */
	public function test_submission_handler_has_reasonable_max_file_size(): void {
		$reflection = new \ReflectionClass( SubmissionHandler::class );
		$max_size   = $reflection->getConstant( 'MAX_FILE_SIZE' );

		$this->assertIsInt( $max_size );
		$this->assertGreaterThan( 0, $max_size );
		$this->assertLessThanOrEqual( 20 * 1024 * 1024, $max_size );
	}

	/**
	 * Test upload handler method exists and is non-public.
	 */
	public function test_featured_image_upload_handler_exists_and_is_not_public(): void {
		$reflection = new \ReflectionClass( SubmissionHandler::class );
		$method     = $reflection->getMethod( 'handle_featured_image_upload' );

		$this->assertTrue( $method->isPrivate() || $method->isProtected() );
	}

	/**
	 * Test upload handler source includes MIME and size validation checks.
	 */
	public function test_upload_handler_source_contains_mime_and_size_validation(): void {
		$source = file_get_contents( __DIR__ . '/../../../src/Frontend/Submission/SubmissionHandler.php' );

		$this->assertStringContainsString( 'wp_check_filetype( $file[\'name\'] )', $source );
		$this->assertStringContainsString( 'in_array( $file_type[\'type\'], self::ALLOWED_IMAGE_TYPES, true )', $source );
		$this->assertStringContainsString( '$file[\'size\'] > $max_size', $source );
	}
}
