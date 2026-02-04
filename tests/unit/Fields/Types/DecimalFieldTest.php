<?php
/**
 * Unit tests for DecimalField.
 *
 * Tests decimal field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\DecimalField;
use APD\Tests\Unit\UnitTestCase;
use WP_Error;

/**
 * Test case for DecimalField class.
 *
 * @covers \APD\Fields\Types\DecimalField
 */
class DecimalFieldTest extends UnitTestCase {

	/**
	 * The field type instance.
	 *
	 * @var DecimalField
	 */
	private DecimalField $field_type;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field_type = new DecimalField();
	}

	/**
	 * Test getType returns 'decimal'.
	 */
	public function testGetTypeReturnsDecimal(): void {
		$this->assertSame( 'decimal', $this->field_type->getType() );
	}

	/**
	 * Test supports filterable.
	 */
	public function testSupportsFilterable(): void {
		$this->assertTrue( $this->field_type->supports( 'filterable' ) );
	}

	/**
	 * Test supports sortable.
	 */
	public function testSupportsSortable(): void {
		$this->assertTrue( $this->field_type->supports( 'sortable' ) );
	}

	/**
	 * Test does not support searchable.
	 */
	public function testDoesNotSupportSearchable(): void {
		$this->assertFalse( $this->field_type->supports( 'searchable' ) );
	}

	/**
	 * Test does not support repeater.
	 */
	public function testDoesNotSupportRepeater(): void {
		$this->assertFalse( $this->field_type->supports( 'repeater' ) );
	}

	/**
	 * Test getDefaultValue returns 0.0.
	 */
	public function testGetDefaultValueReturnsZeroFloat(): void {
		$result = $this->field_type->getDefaultValue();

		$this->assertSame( 0.0, $result );
		$this->assertIsFloat( $result );
	}

	/**
	 * Test render generates correct HTML with default precision.
	 */
	public function testRenderGeneratesCorrectHtml(): void {
		$field = [
			'name'  => 'rating',
			'label' => 'Rating',
		];

		$result = $this->field_type->render( $field, 4.5 );

		$this->assertStringContainsString( 'type="number"', $result );
		$this->assertStringContainsString( 'name="apd_field_rating"', $result );
		$this->assertStringContainsString( 'id="apd-field-rating"', $result );
		$this->assertStringContainsString( 'value="4.50"', $result );
		$this->assertStringContainsString( 'step="0.01"', $result );
	}

	/**
	 * Test render with custom precision.
	 */
	public function testRenderWithCustomPrecision(): void {
		$field = [
			'name'      => 'weight',
			'precision' => 3,
		];

		$result = $this->field_type->render( $field, 2.5 );

		$this->assertStringContainsString( 'value="2.500"', $result );
		$this->assertStringContainsString( 'step="0.001"', $result );
	}

	/**
	 * Test render with precision of 1.
	 */
	public function testRenderWithPrecisionOne(): void {
		$field = [
			'name'      => 'score',
			'precision' => 1,
		];

		$result = $this->field_type->render( $field, 8.7 );

		$this->assertStringContainsString( 'value="8.7"', $result );
		$this->assertStringContainsString( 'step="0.1"', $result );
	}

	/**
	 * Test render with min attribute.
	 */
	public function testRenderWithMinAttribute(): void {
		$field = [
			'name' => 'rating',
			'min'  => 0.0,
		];

		$result = $this->field_type->render( $field, 4.5 );

		$this->assertStringContainsString( 'min="0"', $result );
	}

	/**
	 * Test render with max attribute.
	 */
	public function testRenderWithMaxAttribute(): void {
		$field = [
			'name' => 'rating',
			'max'  => 5.0,
		];

		$result = $this->field_type->render( $field, 4.5 );

		$this->assertStringContainsString( 'max="5"', $result );
	}

	/**
	 * Test render with empty value.
	 */
	public function testRenderWithEmptyValue(): void {
		$field = [
			'name' => 'rating',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'value=""', $result );
	}

	/**
	 * Test render with description.
	 */
	public function testRenderWithDescription(): void {
		$field = [
			'name'        => 'rating',
			'description' => 'Enter a rating between 0 and 5',
		];

		$result = $this->field_type->render( $field, 4.5 );

		$this->assertStringContainsString( 'apd-field-description', $result );
		$this->assertStringContainsString( 'Enter a rating between 0 and 5', $result );
		$this->assertStringContainsString( 'aria-describedby', $result );
	}

	/**
	 * Test render required field.
	 */
	public function testRenderRequiredField(): void {
		$field = [
			'name'     => 'rating',
			'required' => true,
		];

		$result = $this->field_type->render( $field, 4.5 );

		$this->assertStringContainsString( 'required', $result );
		$this->assertStringContainsString( 'aria-required="true"', $result );
	}

	/**
	 * Test sanitize with float value.
	 */
	public function testSanitizeWithFloat(): void {
		$result = $this->field_type->sanitize( 42.567 );

		$this->assertSame( 42.57, $result );
		$this->assertIsFloat( $result );
	}

	/**
	 * Test sanitize with string float.
	 */
	public function testSanitizeWithStringFloat(): void {
		$result = $this->field_type->sanitize( '42.567' );

		$this->assertSame( 42.57, $result );
		$this->assertIsFloat( $result );
	}

	/**
	 * Test sanitize with custom precision.
	 */
	public function testSanitizeWithCustomPrecision(): void {
		$result = $this->field_type->sanitize( 42.5678, 3 );

		$this->assertSame( 42.568, $result );
	}

	/**
	 * Test sanitize rounds correctly.
	 */
	public function testSanitizeRoundsCorrectly(): void {
		// Round down.
		$this->assertSame( 42.56, $this->field_type->sanitize( 42.564 ) );

		// Round up.
		$this->assertSame( 42.57, $this->field_type->sanitize( 42.565 ) );
	}

	/**
	 * Test sanitize with negative value.
	 */
	public function testSanitizeWithNegativeValue(): void {
		$result = $this->field_type->sanitize( -15.75 );

		$this->assertSame( -15.75, $result );
		$this->assertIsFloat( $result );
	}

	/**
	 * Test sanitize with empty string returns zero.
	 */
	public function testSanitizeWithEmptyStringReturnsZero(): void {
		$result = $this->field_type->sanitize( '' );

		$this->assertSame( 0.0, $result );
		$this->assertIsFloat( $result );
	}

	/**
	 * Test sanitize with null returns zero.
	 */
	public function testSanitizeWithNullReturnsZero(): void {
		$result = $this->field_type->sanitize( null );

		$this->assertSame( 0.0, $result );
		$this->assertIsFloat( $result );
	}

	/**
	 * Test sanitize with integer.
	 */
	public function testSanitizeWithInteger(): void {
		$result = $this->field_type->sanitize( 42 );

		$this->assertSame( 42.0, $result );
		$this->assertIsFloat( $result );
	}

	/**
	 * Test sanitizeWithField uses field precision.
	 */
	public function testSanitizeWithFieldUsesPrecision(): void {
		$field = [
			'name'      => 'weight',
			'precision' => 4,
		];

		$result = $this->field_type->sanitizeWithField( 42.56789, $field );

		$this->assertSame( 42.5679, $result );
	}

	/**
	 * Test validate with valid decimal.
	 */
	public function testValidateWithValidDecimal(): void {
		$field = [
			'name'  => 'rating',
			'label' => 'Rating',
		];

		$result = $this->field_type->validate( 4.5, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate empty optional field passes.
	 */
	public function testValidateEmptyOptionalFieldPasses(): void {
		$field = [
			'name'     => 'rating',
			'label'    => 'Rating',
			'required' => false,
		];

		$result = $this->field_type->validate( '', $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate empty required field fails.
	 */
	public function testValidateEmptyRequiredFieldFails(): void {
		$field = [
			'name'     => 'rating',
			'label'    => 'Rating',
			'required' => true,
		];

		$result = $this->field_type->validate( '', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'required', $result->get_error_codes() );
	}

	/**
	 * Test validate non-numeric value fails.
	 */
	public function testValidateNonNumericValueFails(): void {
		$field = [
			'name'     => 'rating',
			'label'    => 'Rating',
			'required' => true,
		];

		$result = $this->field_type->validate( 'abc', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'not_numeric', $result->get_error_codes() );
	}

	/**
	 * Test validate value below minimum fails.
	 */
	public function testValidateValueBelowMinimumFails(): void {
		$field = [
			'name'  => 'rating',
			'label' => 'Rating',
			'min'   => 1.0,
		];

		$result = $this->field_type->validate( 0.5, $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'min_value', $result->get_error_codes() );
	}

	/**
	 * Test validate value at minimum passes.
	 */
	public function testValidateValueAtMinimumPasses(): void {
		$field = [
			'name'  => 'rating',
			'label' => 'Rating',
			'min'   => 1.0,
		];

		$result = $this->field_type->validate( 1.0, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate value above maximum fails.
	 */
	public function testValidateValueAboveMaximumFails(): void {
		$field = [
			'name'  => 'rating',
			'label' => 'Rating',
			'max'   => 5.0,
		];

		$result = $this->field_type->validate( 5.5, $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'max_value', $result->get_error_codes() );
	}

	/**
	 * Test validate value at maximum passes.
	 */
	public function testValidateValueAtMaximumPasses(): void {
		$field = [
			'name'  => 'rating',
			'label' => 'Rating',
			'max'   => 5.0,
		];

		$result = $this->field_type->validate( 5.0, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate value within range passes.
	 */
	public function testValidateValueWithinRangePasses(): void {
		$field = [
			'name'  => 'rating',
			'label' => 'Rating',
			'min'   => 0.0,
			'max'   => 5.0,
		];

		$result = $this->field_type->validate( 3.75, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate zero is valid when allowed.
	 */
	public function testValidateZeroIsValidWhenAllowed(): void {
		$field = [
			'name'  => 'rating',
			'label' => 'Rating',
			'min'   => 0.0,
		];

		$result = $this->field_type->validate( 0, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test formatValue with decimal.
	 */
	public function testFormatValueWithDecimal(): void {
		$field = [
			'name'      => 'rating',
			'precision' => 2,
		];

		$result = $this->field_type->formatValue( 4.5, $field );

		$this->assertSame( '4.50', $result );
	}

	/**
	 * Test formatValue with custom precision.
	 */
	public function testFormatValueWithCustomPrecision(): void {
		$field = [
			'name'      => 'weight',
			'precision' => 3,
		];

		$result = $this->field_type->formatValue( 2.5, $field );

		$this->assertSame( '2.500', $result );
	}

	/**
	 * Test formatValue with empty value.
	 */
	public function testFormatValueWithEmptyValue(): void {
		$field = [
			'name' => 'rating',
		];

		$result = $this->field_type->formatValue( '', $field );

		$this->assertSame( '', $result );
	}

	/**
	 * Test formatValue with null value.
	 */
	public function testFormatValueWithNullValue(): void {
		$field = [
			'name' => 'rating',
		];

		$result = $this->field_type->formatValue( null, $field );

		$this->assertSame( '', $result );
	}

	/**
	 * Test formatValue default precision is 2.
	 */
	public function testFormatValueDefaultPrecisionIsTwo(): void {
		$field = [
			'name' => 'rating',
		];

		$result = $this->field_type->formatValue( 4.5, $field );

		$this->assertSame( '4.50', $result );
	}
}
