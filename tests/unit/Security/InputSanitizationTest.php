<?php
/**
 * Tests for sanitization call paths at plugin entry points.
 *
 * @package APD\Tests\Unit\Security
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Security;

use APD\Contact\ContactHandler;
use APD\Frontend\Submission\SubmissionHandler;
use Brain\Monkey\Functions;

/**
 * InputSanitizationTest verifies plugin-level sanitization contracts.
 *
 * Note: WordPress helper semantics are covered in integration tests.
 */
class InputSanitizationTest extends SecurityTestCase {

	/**
	 * Test ContactHandler get_sanitized_data delegates to expected helpers.
	 */
	public function test_contact_handler_get_sanitized_data_delegates_to_helpers(): void {
		$_POST = [
			'listing_id'      => '42',
			'contact_name'    => 'John',
			'contact_email'   => 'john@example.com',
			'contact_phone'   => '123',
			'contact_subject' => 'Subject',
			'contact_message' => 'Message',
		];

		$calls = [
			'absint'                  => 0,
			'sanitize_text_field'     => 0,
			'sanitize_email'          => 0,
			'sanitize_textarea_field' => 0,
		];

		Functions\when( 'wp_unslash' )->alias(
			static function ( $value ) {
				return $value;
			}
		);
		Functions\when( 'absint' )->alias(
			static function ( $value ) use ( &$calls ) {
				++$calls['absint'];
				return 42;
			}
		);
		Functions\when( 'sanitize_text_field' )->alias(
			static function ( $value ) use ( &$calls ) {
				++$calls['sanitize_text_field'];
				return 'txt:' . (string) $value;
			}
		);
		Functions\when( 'sanitize_email' )->alias(
			static function ( $value ) use ( &$calls ) {
				++$calls['sanitize_email'];
				return 'email:' . (string) $value;
			}
		);
		Functions\when( 'sanitize_textarea_field' )->alias(
			static function ( $value ) use ( &$calls ) {
				++$calls['sanitize_textarea_field'];
				return 'textarea:' . (string) $value;
			}
		);

		$handler = ContactHandler::get_instance();
		$data    = $handler->get_sanitized_data();

		$this->assertSame( 42, $data['listing_id'] );
		$this->assertSame( 'txt:John', $data['contact_name'] );
		$this->assertSame( 'email:john@example.com', $data['contact_email'] );
		$this->assertSame( 'txt:123', $data['contact_phone'] );
		$this->assertSame( 'txt:Subject', $data['contact_subject'] );
		$this->assertSame( 'textarea:Message', $data['contact_message'] );

		$this->assertSame( 1, $calls['absint'] );
		$this->assertSame( 3, $calls['sanitize_text_field'] );
		$this->assertSame( 1, $calls['sanitize_email'] );
		$this->assertSame( 1, $calls['sanitize_textarea_field'] );
	}

	/**
	 * Test SubmissionHandler collect_form_data delegates to expected helpers.
	 */
	public function test_submission_handler_collect_form_data_delegates_to_helpers(): void {
		$_POST = [
			'listing_title'      => 'Title',
			'listing_content'    => '<p>Content</p>',
			'listing_excerpt'    => 'Excerpt',
			'listing_categories' => [ '4' ],
			'listing_tags'       => [ '7' ],
			'featured_image'     => '11',
			'apd_redirect'       => '/thank-you',
		];

		$calls = [
			'sanitize_text_field'     => 0,
			'wp_kses_post'            => 0,
			'sanitize_textarea_field' => 0,
			'absint'                  => 0,
			'esc_url_raw'             => 0,
		];

		Functions\when( 'wp_unslash' )->alias(
			static function ( $value ) {
				return $value;
			}
		);
		Functions\when( 'wp_parse_args' )->alias(
			static function ( $args, $defaults = [] ) {
				if ( is_object( $args ) ) {
					$args = get_object_vars( $args );
				}

				return array_merge( $defaults, $args );
			}
		);
		Functions\when( 'sanitize_text_field' )->alias(
			static function ( $value ) use ( &$calls ) {
				++$calls['sanitize_text_field'];
				return 'title:' . (string) $value;
			}
		);
		Functions\when( 'wp_kses_post' )->alias(
			static function ( $value ) use ( &$calls ) {
				++$calls['wp_kses_post'];
				return 'content:' . (string) $value;
			}
		);
		Functions\when( 'sanitize_textarea_field' )->alias(
			static function ( $value ) use ( &$calls ) {
				++$calls['sanitize_textarea_field'];
				return 'excerpt:' . (string) $value;
			}
		);
		Functions\when( 'absint' )->alias(
			static function ( $value ) use ( &$calls ) {
				++$calls['absint'];
				return (int) $value;
			}
		);
		Functions\when( 'esc_url_raw' )->alias(
			static function ( $value ) use ( &$calls ) {
				++$calls['esc_url_raw'];
				return 'url:' . (string) $value;
			}
		);

		$handler    = new SubmissionHandler();
		$reflection = new \ReflectionClass( $handler );
		$method     = $reflection->getMethod( 'collect_form_data' );
		$data       = $method->invoke( $handler );

		$this->assertSame( 'title:Title', $data['listing_title'] );
		$this->assertSame( 'content:<p>Content</p>', $data['listing_content'] );
		$this->assertSame( 'excerpt:Excerpt', $data['listing_excerpt'] );
		$this->assertSame( [ 4 ], $data['listing_categories'] );
		$this->assertSame( [ 7 ], $data['listing_tags'] );
		$this->assertSame( 11, $data['featured_image'] );
		$this->assertSame( 'url:/thank-you', $data['redirect'] );

		$this->assertSame( 1, $calls['sanitize_text_field'] );
		$this->assertSame( 1, $calls['wp_kses_post'] );
		$this->assertSame( 1, $calls['sanitize_textarea_field'] );
		$this->assertSame( 3, $calls['absint'] );
		$this->assertSame( 1, $calls['esc_url_raw'] );
	}

	/**
	 * Test submission source declares the expected sanitization contract.
	 */
	public function test_submission_source_declares_expected_sanitizers(): void {
		$source = file_get_contents( __DIR__ . '/../../../src/Frontend/Submission/SubmissionHandler.php' );

		$this->assertStringContainsString( 'sanitize_text_field( wp_unslash( $_POST[\'listing_title\'] ) )', $source );
		$this->assertStringContainsString( 'wp_kses_post( wp_unslash( $_POST[\'listing_content\'] ) )', $source );
		$this->assertStringContainsString( 'sanitize_textarea_field( wp_unslash( $_POST[\'listing_excerpt\'] ) )', $source );
		$this->assertStringContainsString( 'array_map( \'absint\', $_POST[\'listing_categories\'] )', $source );
		$this->assertStringContainsString( 'esc_url_raw( wp_unslash( $_POST[\'apd_redirect\'] ) )', $source );
	}

	/**
	 * Clean up after each test.
	 */
	protected function tearDown(): void {
		$_POST = [];
		parent::tearDown();
	}
}
