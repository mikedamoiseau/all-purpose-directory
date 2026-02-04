<?php
/**
 * Unit tests for PhoneField.
 *
 * Tests phone field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\PhoneField;
use APD\Tests\Unit\UnitTestCase;
use WP_Error;

/**
 * Test case for PhoneField class.
 *
 * @covers \APD\Fields\Types\PhoneField
 */
class PhoneFieldTest extends UnitTestCase {

	/**
	 * The field type instance.
	 *
	 * @var PhoneField
	 */
	private PhoneField $field_type;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->field_type = new PhoneField();
	}

	/**
	 * Test field type returns 'phone'.
	 */
	public function testGetType(): void {
		$this->assertSame( 'phone', $this->field_type->getType() );
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
	 * Test rendering a phone field.
	 */
	public function testRender(): void {
		$field = [
			'name'  => 'phone_number',
			'label' => 'Phone Number',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'type="tel"', $result );
		$this->assertStringContainsString( 'name="apd_field_phone_number"', $result );
		$this->assertStringContainsString( 'id="apd-field-phone_number"', $result );
	}

	/**
	 * Test rendering with a value.
	 */
	public function testRenderWithValue(): void {
		$field = [
			'name'  => 'phone_number',
			'label' => 'Phone Number',
		];

		$result = $this->field_type->render( $field, '+1 (555) 123-4567' );

		$this->assertStringContainsString( 'value="+1 (555) 123-4567"', $result );
	}

	/**
	 * Test rendering with placeholder.
	 */
	public function testRenderWithPlaceholder(): void {
		$field = [
			'name'        => 'phone_number',
			'label'       => 'Phone Number',
			'placeholder' => '(555) 123-4567',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'placeholder="(555) 123-4567"', $result );
	}

	/**
	 * Test rendering required field.
	 */
	public function testRenderRequiredField(): void {
		$field = [
			'name'     => 'phone_number',
			'label'    => 'Phone Number',
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
			'name'        => 'phone_number',
			'label'       => 'Phone Number',
			'description' => 'Include country code for international numbers',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'aria-describedby="apd-field-phone_number-description"', $result );
		$this->assertStringContainsString( 'class="apd-field-description"', $result );
		$this->assertStringContainsString( 'Include country code for international numbers', $result );
	}

	/**
	 * Test sanitization preserves allowed characters.
	 */
	public function testSanitizePreservesAllowedCharacters(): void {
		$result = $this->field_type->sanitize( '+1 (555) 123-4567' );

		$this->assertSame( '+1 (555) 123-4567', $result );
	}

	/**
	 * Test sanitization removes invalid characters.
	 */
	public function testSanitizeRemovesInvalidCharacters(): void {
		$result = $this->field_type->sanitize( '+1 (555) 123-4567 abc!' );

		$this->assertSame( '+1 (555) 123-4567', $result );
	}

	/**
	 * Test sanitization preserves dots.
	 */
	public function testSanitizePreservesDots(): void {
		$result = $this->field_type->sanitize( '555.123.4567' );

		$this->assertSame( '555.123.4567', $result );
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
	 * Test sanitization trims whitespace.
	 */
	public function testSanitizeTrimsWhitespace(): void {
		$result = $this->field_type->sanitize( '  555-123-4567  ' );

		$this->assertSame( '555-123-4567', $result );
	}

	/**
	 * Test validation passes with valid US phone.
	 */
	public function testValidateWithValidUsPhone(): void {
		$field = [
			'name'  => 'phone_number',
			'label' => 'Phone Number',
		];

		$result = $this->field_type->validate( '555-123-4567', $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validation passes with valid international phone.
	 */
	public function testValidateWithValidInternationalPhone(): void {
		$field = [
			'name'  => 'phone_number',
			'label' => 'Phone Number',
		];

		$result = $this->field_type->validate( '+1 (555) 123-4567', $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validation passes with various phone formats.
	 */
	public function testValidateWithVariousFormats(): void {
		$field = [
			'name'  => 'phone_number',
			'label' => 'Phone Number',
		];

		// Different valid formats.
		$this->assertTrue( $this->field_type->validate( '5551234567', $field ) );
		$this->assertTrue( $this->field_type->validate( '555.123.4567', $field ) );
		$this->assertTrue( $this->field_type->validate( '(555) 123-4567', $field ) );
		$this->assertTrue( $this->field_type->validate( '+44 20 7946 0958', $field ) );
	}

	/**
	 * Test validation passes with empty value for optional field.
	 */
	public function testValidateEmptyOptionalField(): void {
		$field = [
			'name'     => 'phone_number',
			'label'    => 'Phone Number',
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
			'name'     => 'phone_number',
			'label'    => 'Phone Number',
			'required' => true,
		];

		$result = $this->field_type->validate( '', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'required', $result->get_error_codes() );
	}

	/**
	 * Test validation fails with too few digits.
	 */
	public function testValidateWithTooFewDigits(): void {
		$field = [
			'name'  => 'phone_number',
			'label' => 'Phone Number',
		];

		$result = $this->field_type->validate( '123456', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'invalid_phone', $result->get_error_codes() );
	}

	/**
	 * Test validation fails with too many digits.
	 */
	public function testValidateWithTooManyDigits(): void {
		$field = [
			'name'  => 'phone_number',
			'label' => 'Phone Number',
		];

		// More than 15 digits.
		$result = $this->field_type->validate( '1234567890123456', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'invalid_phone', $result->get_error_codes() );
	}

	/**
	 * Test formatValue creates tel link.
	 */
	public function testFormatValueCreatesTelLink(): void {
		$field = [
			'name'  => 'phone_number',
			'label' => 'Phone Number',
		];

		$result = $this->field_type->formatValue( '555-123-4567', $field );

		$this->assertStringContainsString( 'href="tel:555-123-4567"', $result );
		$this->assertStringContainsString( '>555-123-4567</a>', $result );
	}

	/**
	 * Test formatValue removes spaces and parentheses from href.
	 */
	public function testFormatValueRemovesSpacesFromHref(): void {
		$field = [
			'name'  => 'phone_number',
			'label' => 'Phone Number',
		];

		$result = $this->field_type->formatValue( '+1 (555) 123-4567', $field );

		$this->assertStringContainsString( 'href="tel:+1555123-4567"', $result );
		$this->assertStringContainsString( '>+1 (555) 123-4567</a>', $result );
	}

	/**
	 * Test formatValue handles empty value.
	 */
	public function testFormatValueHandlesEmptyValue(): void {
		$field = [
			'name'  => 'phone_number',
			'label' => 'Phone Number',
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
