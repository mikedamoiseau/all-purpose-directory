<?php
/**
 * Unit tests for EmailField.
 *
 * Tests email field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\EmailField;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use WP_Error;

/**
 * Test case for EmailField class.
 *
 * @covers \APD\Fields\Types\EmailField
 */
class EmailFieldTest extends UnitTestCase {

	/**
	 * The field type instance.
	 *
	 * @var EmailField
	 */
	private EmailField $field_type;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Add is_email stub.
		Functions\stubs([
			'is_email' => static function ( $email ) {
				return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false;
			},
		]);

		$this->field_type = new EmailField();
	}

	/**
	 * Test field type returns 'email'.
	 */
	public function testGetType(): void {
		$this->assertSame( 'email', $this->field_type->getType() );
	}

	/**
	 * Test supports returns correct values.
	 */
	public function testSupports(): void {
		$this->assertTrue( $this->field_type->supports( 'searchable' ) );
		$this->assertFalse( $this->field_type->supports( 'filterable' ) );
		$this->assertTrue( $this->field_type->supports( 'sortable' ) );
		$this->assertFalse( $this->field_type->supports( 'repeater' ) );
	}

	/**
	 * Test rendering an email field.
	 */
	public function testRender(): void {
		$field = [
			'name'  => 'contact_email',
			'label' => 'Contact Email',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'type="email"', $result );
		$this->assertStringContainsString( 'name="apd_field_contact_email"', $result );
		$this->assertStringContainsString( 'id="apd-field-contact_email"', $result );
	}

	/**
	 * Test rendering with a value.
	 */
	public function testRenderWithValue(): void {
		$field = [
			'name'  => 'contact_email',
			'label' => 'Contact Email',
		];

		$result = $this->field_type->render( $field, 'test@example.com' );

		$this->assertStringContainsString( 'value="test@example.com"', $result );
	}

	/**
	 * Test rendering with placeholder.
	 */
	public function testRenderWithPlaceholder(): void {
		$field = [
			'name'        => 'contact_email',
			'label'       => 'Contact Email',
			'placeholder' => 'Enter your email',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'placeholder="Enter your email"', $result );
	}

	/**
	 * Test rendering required field.
	 */
	public function testRenderRequiredField(): void {
		$field = [
			'name'     => 'contact_email',
			'label'    => 'Contact Email',
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
			'name'        => 'contact_email',
			'label'       => 'Contact Email',
			'description' => 'Enter a valid email address',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'aria-describedby="apd-field-contact_email-description"', $result );
		$this->assertStringContainsString( 'class="apd-field-description"', $result );
		$this->assertStringContainsString( 'Enter a valid email address', $result );
	}

	/**
	 * Test sanitization returns sanitized email.
	 */
	public function testSanitize(): void {
		$result = $this->field_type->sanitize( 'test@example.com' );

		$this->assertSame( 'test@example.com', $result );
	}

	/**
	 * Test sanitization removes invalid characters.
	 */
	public function testSanitizeRemovesInvalidCharacters(): void {
		$result = $this->field_type->sanitize( 'test<script>@example.com' );

		$this->assertStringNotContainsString( '<script>', $result );
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
	 * Test validation passes with valid email.
	 */
	public function testValidateWithValidEmail(): void {
		$field = [
			'name'  => 'contact_email',
			'label' => 'Contact Email',
		];

		$result = $this->field_type->validate( 'test@example.com', $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validation passes with empty value for optional field.
	 */
	public function testValidateEmptyOptionalField(): void {
		$field = [
			'name'     => 'contact_email',
			'label'    => 'Contact Email',
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
			'name'     => 'contact_email',
			'label'    => 'Contact Email',
			'required' => true,
		];

		$result = $this->field_type->validate( '', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'required', $result->get_error_codes() );
	}

	/**
	 * Test validation fails with invalid email.
	 */
	public function testValidateWithInvalidEmail(): void {
		$field = [
			'name'  => 'contact_email',
			'label' => 'Contact Email',
		];

		$result = $this->field_type->validate( 'not-an-email', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'invalid_email', $result->get_error_codes() );
	}

	/**
	 * Test validation fails with incomplete email.
	 */
	public function testValidateWithIncompleteEmail(): void {
		$field = [
			'name'  => 'contact_email',
			'label' => 'Contact Email',
		];

		$result = $this->field_type->validate( 'test@', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'invalid_email', $result->get_error_codes() );
	}

	/**
	 * Test formatValue creates mailto link.
	 */
	public function testFormatValueCreatesMailtoLink(): void {
		$field = [
			'name'  => 'contact_email',
			'label' => 'Contact Email',
		];

		$result = $this->field_type->formatValue( 'test@example.com', $field );

		$this->assertStringContainsString( 'href="mailto:test@example.com"', $result );
		$this->assertStringContainsString( '>test@example.com</a>', $result );
	}

	/**
	 * Test formatValue handles empty value.
	 */
	public function testFormatValueHandlesEmptyValue(): void {
		$field = [
			'name'  => 'contact_email',
			'label' => 'Contact Email',
		];

		$this->assertSame( '', $this->field_type->formatValue( '', $field ) );
		$this->assertSame( '', $this->field_type->formatValue( null, $field ) );
	}

	/**
	 * Test formatValue handles invalid email.
	 */
	public function testFormatValueHandlesInvalidEmail(): void {
		$field = [
			'name'  => 'contact_email',
			'label' => 'Contact Email',
		];

		// sanitize_email returns empty string for invalid email.
		Functions\when( 'sanitize_email' )->justReturn( '' );

		$result = $this->field_type->formatValue( 'not-valid', $field );

		$this->assertSame( '', $result );
	}

	/**
	 * Test getDefaultValue returns empty string.
	 */
	public function testGetDefaultValue(): void {
		$this->assertSame( '', $this->field_type->getDefaultValue() );
	}
}
