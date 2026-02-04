<?php
/**
 * Unit tests for UrlField.
 *
 * Tests URL field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\UrlField;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use WP_Error;

/**
 * Test case for UrlField class.
 *
 * @covers \APD\Fields\Types\UrlField
 */
class UrlFieldTest extends UnitTestCase {

	/**
	 * The field type instance.
	 *
	 * @var UrlField
	 */
	private UrlField $field_type;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Add esc_url_raw stub.
		Functions\stubs([
			'esc_url_raw' => static function ( $url ) {
				// Simple sanitization - filter out invalid URLs.
				if ( filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
					// Allow URLs that might be missing scheme.
					if ( strpos( $url, '.' ) !== false && strpos( $url, ' ' ) === false ) {
						return $url;
					}
					return '';
				}
				return $url;
			},
			'wp_parse_url' => static function ( $url, $component = -1 ) {
				return parse_url( $url, $component );
			},
			'home_url' => static function () {
				return 'https://example.com';
			},
		]);

		$this->field_type = new UrlField();
	}

	/**
	 * Test field type returns 'url'.
	 */
	public function testGetType(): void {
		$this->assertSame( 'url', $this->field_type->getType() );
	}

	/**
	 * Test supports returns correct values.
	 */
	public function testSupports(): void {
		$this->assertTrue( $this->field_type->supports( 'searchable' ) );
		$this->assertFalse( $this->field_type->supports( 'filterable' ) );
		$this->assertFalse( $this->field_type->supports( 'sortable' ) );
		$this->assertFalse( $this->field_type->supports( 'repeater' ) );
	}

	/**
	 * Test rendering a URL field.
	 */
	public function testRender(): void {
		$field = [
			'name'  => 'website',
			'label' => 'Website',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'type="url"', $result );
		$this->assertStringContainsString( 'name="apd_field_website"', $result );
		$this->assertStringContainsString( 'id="apd-field-website"', $result );
	}

	/**
	 * Test rendering with a value.
	 */
	public function testRenderWithValue(): void {
		$field = [
			'name'  => 'website',
			'label' => 'Website',
		];

		$result = $this->field_type->render( $field, 'https://example.com' );

		$this->assertStringContainsString( 'value="https://example.com"', $result );
	}

	/**
	 * Test rendering with placeholder.
	 */
	public function testRenderWithPlaceholder(): void {
		$field = [
			'name'        => 'website',
			'label'       => 'Website',
			'placeholder' => 'https://yoursite.com',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'placeholder="https://yoursite.com"', $result );
	}

	/**
	 * Test rendering required field.
	 */
	public function testRenderRequiredField(): void {
		$field = [
			'name'     => 'website',
			'label'    => 'Website',
			'required' => true,
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'required', $result );
		$this->assertStringContainsString( 'aria-required="true"', $result );
	}

	/**
	 * Test rendering with description.
	 */
	public function testRenderWithDescription(): void {
		$field = [
			'name'        => 'website',
			'label'       => 'Website',
			'description' => 'Enter your website URL',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'aria-describedby="apd-field-website-description"', $result );
		$this->assertStringContainsString( 'class="apd-field-description"', $result );
		$this->assertStringContainsString( 'Enter your website URL', $result );
	}

	/**
	 * Test sanitization returns sanitized URL.
	 */
	public function testSanitize(): void {
		$result = $this->field_type->sanitize( 'https://example.com/path?query=1' );

		$this->assertSame( 'https://example.com/path?query=1', $result );
	}

	/**
	 * Test sanitization handles non-string values.
	 */
	public function testSanitizeHandlesNonString(): void {
		$this->assertSame( '', $this->field_type->sanitize( null ) );
		$this->assertSame( '', $this->field_type->sanitize( [] ) );
		$this->assertSame( '', $this->field_type->sanitize( 123 ) );
	}

	/**
	 * Test validation passes with valid URL.
	 */
	public function testValidateWithValidUrl(): void {
		$field = [
			'name'  => 'website',
			'label' => 'Website',
		];

		$result = $this->field_type->validate( 'https://example.com', $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validation passes with HTTP URL.
	 */
	public function testValidateWithHttpUrl(): void {
		$field = [
			'name'  => 'website',
			'label' => 'Website',
		];

		$result = $this->field_type->validate( 'http://example.com', $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validation passes with URL with path and query.
	 */
	public function testValidateWithUrlPathAndQuery(): void {
		$field = [
			'name'  => 'website',
			'label' => 'Website',
		];

		$result = $this->field_type->validate( 'https://example.com/path/to/page?query=value', $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validation passes with empty value for optional field.
	 */
	public function testValidateEmptyOptionalField(): void {
		$field = [
			'name'     => 'website',
			'label'    => 'Website',
			'required' => false,
		];

		$result = $this->field_type->validate( '', $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validation fails for empty required field.
	 */
	public function testValidateEmptyRequiredField(): void {
		$field = [
			'name'     => 'website',
			'label'    => 'Website',
			'required' => true,
		];

		$result = $this->field_type->validate( '', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'required', $result->get_error_codes() );
	}

	/**
	 * Test validation fails with invalid URL.
	 */
	public function testValidateWithInvalidUrl(): void {
		$field = [
			'name'  => 'website',
			'label' => 'Website',
		];

		$result = $this->field_type->validate( 'not-a-url', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'invalid_url', $result->get_error_codes() );
	}

	/**
	 * Test validation fails with plain text (not a URL).
	 */
	public function testValidateWithPlainText(): void {
		$field = [
			'name'  => 'website',
			'label' => 'Website',
		];

		$result = $this->field_type->validate( 'just some text', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'invalid_url', $result->get_error_codes() );
	}

	/**
	 * Test formatValue creates clickable link.
	 */
	public function testFormatValueCreatesLink(): void {
		$field = [
			'name'  => 'website',
			'label' => 'Website',
		];

		$result = $this->field_type->formatValue( 'https://example.com', $field );

		$this->assertStringContainsString( 'href="https://example.com"', $result );
		$this->assertStringContainsString( 'target="_blank"', $result );
	}

	/**
	 * Test formatValue strips protocol from display.
	 */
	public function testFormatValueStripsProtocol(): void {
		$field = [
			'name'  => 'website',
			'label' => 'Website',
		];

		$result = $this->field_type->formatValue( 'https://example.com/', $field );

		$this->assertStringContainsString( '>example.com</a>', $result );
	}

	/**
	 * Test formatValue adds noopener noreferrer for external URLs.
	 */
	public function testFormatValueAddsNoopenerForExternalUrl(): void {
		$field = [
			'name'  => 'website',
			'label' => 'Website',
		];

		$result = $this->field_type->formatValue( 'https://external-site.com', $field );

		$this->assertStringContainsString( 'rel="noopener noreferrer"', $result );
	}

	/**
	 * Test formatValue does not add rel for internal URLs.
	 */
	public function testFormatValueNoRelForInternalUrl(): void {
		$field = [
			'name'  => 'website',
			'label' => 'Website',
		];

		// Internal URL (same host as home_url mock).
		$result = $this->field_type->formatValue( 'https://example.com/internal-page', $field );

		$this->assertStringNotContainsString( 'rel="noopener noreferrer"', $result );
	}

	/**
	 * Test formatValue handles empty value.
	 */
	public function testFormatValueHandlesEmptyValue(): void {
		$field = [
			'name'  => 'website',
			'label' => 'Website',
		];

		$this->assertSame( '', $this->field_type->formatValue( '', $field ) );
		$this->assertSame( '', $this->field_type->formatValue( null, $field ) );
	}

	/**
	 * Test getDefaultValue returns empty string.
	 */
	public function testGetDefaultValue(): void {
		$this->assertSame( '', $this->field_type->getDefaultValue() );
	}
}
