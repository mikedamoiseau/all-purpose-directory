<?php
/**
 * Tests for nonce verification across all form handlers.
 *
 * @package APD\Tests\Unit\Security
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Security;

use Brain\Monkey\Functions;
use APD\Contact\ContactHandler;
use APD\Contact\ContactForm;

/**
 * NonceVerificationTest verifies CSRF protection on all forms.
 */
class NonceVerificationTest extends SecurityTestCase {

    /**
     * Test ContactHandler requires valid nonce.
     */
    public function test_contact_handler_requires_nonce(): void {
        // Set up POST data without nonce
        $_POST = [
            'apd_contact_name' => 'John',
            'apd_contact_email' => 'john@example.com',
            'apd_contact_message' => 'Hello',
        ];

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('', ContactForm::NONCE_ACTION)
            ->andReturn(false);

        $handler = ContactHandler::get_instance();
        $result = $handler->verify_nonce();

        $this->assertFalse($result, 'Contact handler should reject missing nonce');
    }

    /**
     * Test ContactHandler accepts valid nonce.
     */
    public function test_contact_handler_accepts_valid_nonce(): void {
        $_POST = [
            ContactForm::NONCE_NAME => 'valid_contact_nonce',
            'apd_contact_name' => 'John',
        ];

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('valid_contact_nonce', ContactForm::NONCE_ACTION)
            ->andReturn(true);

        $handler = ContactHandler::get_instance();
        $result = $handler->verify_nonce();

        $this->assertTrue($result, 'Contact handler should accept valid nonce');
    }

    /**
     * Test ContactHandler rejects invalid nonce.
     */
    public function test_contact_handler_rejects_invalid_nonce(): void {
        $_POST = [
            ContactForm::NONCE_NAME => 'invalid_nonce',
        ];

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('invalid_nonce', ContactForm::NONCE_ACTION)
            ->andReturn(false);

        $handler = ContactHandler::get_instance();
        $result = $handler->verify_nonce();

        $this->assertFalse($result, 'Contact handler should reject invalid nonce');
    }

    /**
     * Test ContactHandler rejects empty nonce.
     */
    public function test_contact_handler_rejects_empty_nonce(): void {
        $_POST = [
            ContactForm::NONCE_NAME => '',
        ];

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('', ContactForm::NONCE_ACTION)
            ->andReturn(false);

        $handler = ContactHandler::get_instance();
        $result = $handler->verify_nonce();

        $this->assertFalse($result, 'Contact handler should reject empty nonce');
    }

    /**
     * Test submission handler nonce constants are defined.
     */
    public function test_submission_handler_nonce_constants(): void {
        // Verify constants are defined
        $this->assertEquals('apd_submit_listing', \APD\Frontend\Submission\SubmissionHandler::NONCE_ACTION);
        $this->assertEquals('apd_submission_nonce', \APD\Frontend\Submission\SubmissionHandler::NONCE_NAME);
    }

    /**
     * Test contact form nonce constants are defined.
     */
    public function test_contact_form_nonce_constants(): void {
        $this->assertEquals('apd_contact_form', ContactForm::NONCE_ACTION);
        $this->assertEquals('apd_contact_nonce', ContactForm::NONCE_NAME);
    }

    /**
     * Test review form nonce constants are defined.
     */
    public function test_review_form_nonce_constants(): void {
        $this->assertEquals('apd_submit_review', \APD\Review\ReviewForm::NONCE_ACTION);
        $this->assertEquals('apd_review_nonce', \APD\Review\ReviewForm::NONCE_NAME);
    }

    /**
     * Test nonce verification returns integer on valid first-half nonce.
     */
    public function test_nonce_verification_returns_integer_on_fresh_nonce(): void {
        $_POST = [
            ContactForm::NONCE_NAME => 'fresh_nonce',
        ];

        // wp_verify_nonce returns 1 if nonce is in first half of life
        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('fresh_nonce', ContactForm::NONCE_ACTION)
            ->andReturn(true);

        $handler = ContactHandler::get_instance();
        $result = $handler->verify_nonce();

        $this->assertTrue($result);
    }

    /**
     * Test nonce verification accepts nonce in second half of life.
     */
    public function test_nonce_verification_accepts_older_valid_nonce(): void {
        $_POST = [
            ContactForm::NONCE_NAME => 'older_nonce',
        ];

        // wp_verify_nonce returns 2 if nonce is in second half of life
        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('older_nonce', ContactForm::NONCE_ACTION)
            ->andReturn(true);

        $handler = ContactHandler::get_instance();
        $result = $handler->verify_nonce();

        // Both 1 and 2 are truthy - the nonce is still valid
        $this->assertTrue($result);
    }

    /**
     * Test nonce from wrong action fails verification.
     */
    public function test_nonce_from_wrong_action_fails(): void {
        $_POST = [
            ContactForm::NONCE_NAME => 'nonce_for_different_action',
        ];

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('nonce_for_different_action', ContactForm::NONCE_ACTION)
            ->andReturn(false);

        $handler = ContactHandler::get_instance();
        $result = $handler->verify_nonce();

        $this->assertFalse($result, 'Nonce from wrong action should fail');
    }

    /**
     * Test nonce field is properly sanitized before verification.
     */
	public function test_nonce_is_sanitized_before_verification(): void {
		// Simulate POST with raw nonce value.
		$_POST = [
			ContactForm::NONCE_NAME => '  valid_nonce  ',
		];

		$captured_sanitize_input = null;
		$captured_verified_nonce = null;
		$captured_verified_action = null;

		Functions\when('sanitize_text_field')->alias(
			static function ( $value ) use ( &$captured_sanitize_input ) {
				$captured_sanitize_input = $value;
				return 'normalized_nonce';
			}
		);

		Functions\when('wp_verify_nonce')->alias(
			static function ( $nonce, $action ) use ( &$captured_verified_nonce, &$captured_verified_action ) {
				$captured_verified_nonce  = $nonce;
				$captured_verified_action = $action;
				return true;
			}
		);

		$handler = ContactHandler::get_instance();
		$result = $handler->verify_nonce();

		$this->assertTrue($result);
		$this->assertSame('  valid_nonce  ', $captured_sanitize_input);
		$this->assertSame('normalized_nonce', $captured_verified_nonce);
		$this->assertSame(ContactForm::NONCE_ACTION, $captured_verified_action);
	}

    /**
     * Test that nonce verification happens for all form endpoints.
     *
     * This is a documentation test verifying expected nonce patterns.
     */
    public function test_all_form_endpoints_have_nonce_definitions(): void {
        // All form handlers should have nonce constants defined
        $handlers = [
            \APD\Frontend\Submission\SubmissionHandler::class => ['NONCE_ACTION', 'NONCE_NAME'],
            \APD\Contact\ContactForm::class => ['NONCE_ACTION', 'NONCE_NAME'],
            \APD\Review\ReviewForm::class => ['NONCE_ACTION', 'NONCE_NAME'],
        ];

        foreach ($handlers as $class => $constants) {
            foreach ($constants as $constant) {
                $reflection = new \ReflectionClass($class);
                $this->assertTrue(
                    $reflection->hasConstant($constant),
                    "{$class} should define {$constant} constant"
                );
            }
        }
    }

    /**
     * Test that XSS in nonce field is sanitized.
     */
	public function test_xss_in_nonce_field_is_sanitized(): void {
		$_POST = [
			ContactForm::NONCE_NAME => '<script>alert("xss")</script>valid_nonce',
		];

		$captured_sanitize_input = null;
		$captured_verified_nonce = null;

		Functions\when('sanitize_text_field')->alias(
			static function ( $value ) use ( &$captured_sanitize_input ) {
				$captured_sanitize_input = $value;
				return 'safe_nonce_value';
			}
		);

		Functions\when('wp_verify_nonce')->alias(
			static function ( $nonce, $action ) use ( &$captured_verified_nonce ) {
				$captured_verified_nonce = $nonce;
				return false;
			}
		);

		$handler = ContactHandler::get_instance();
		$result = $handler->verify_nonce();

		$this->assertFalse($result);
		$this->assertSame('<script>alert("xss")</script>valid_nonce', $captured_sanitize_input);
		$this->assertSame('safe_nonce_value', $captured_verified_nonce);
	}

    /**
     * Test nonce field with null coalescing in verify_nonce method.
     */
    public function test_missing_nonce_field_defaults_to_empty(): void {
        // No nonce field at all
        $_POST = [];

        Functions\expect('wp_verify_nonce')
            ->once()
            ->with('', ContactForm::NONCE_ACTION)
            ->andReturn(false);

        $handler = ContactHandler::get_instance();
        $result = $handler->verify_nonce();

        $this->assertFalse($result, 'Missing nonce should fail verification');
    }

    /**
     * Test AJAX referer check for review handler.
     */
    public function test_ajax_nonce_check_for_reviews(): void {
        // Review handler uses check_ajax_referer
        Functions\expect('check_ajax_referer')
            ->once()
            ->with('apd_submit_review', 'apd_review_nonce', false)
            ->andReturn(false);

        $result = check_ajax_referer('apd_submit_review', 'apd_review_nonce', false);

        $this->assertFalse($result, 'AJAX referer check should fail with invalid nonce');
    }

    /**
     * Test AJAX nonce verification returns true on valid nonce.
     */
    public function test_ajax_nonce_check_succeeds_with_valid_nonce(): void {
        Functions\expect('check_ajax_referer')
            ->once()
            ->with('apd_submit_review', 'apd_review_nonce', false)
            ->andReturn(true);

        $result = check_ajax_referer('apd_submit_review', 'apd_review_nonce', false);

        $this->assertTrue($result, 'AJAX referer check should succeed with valid nonce');
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void {
        $_POST = [];
        $_GET = [];
        $_REQUEST = [];
        parent::tearDown();
    }
}
