<?php
/**
 * Unit tests for NumberField.
 *
 * Tests number field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\NumberField;
use APD\Tests\Unit\UnitTestCase;
use WP_Error;

/**
 * Test case for NumberField class.
 *
 * @covers \APD\Fields\Types\NumberField
 */
class NumberFieldTest extends UnitTestCase {

	/**
	 * The field type instance.
	 *
	 * @var NumberField
	 */
	private NumberField $field_type;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field_type = new NumberField();
	}

	/**
	 * Test getType returns 'number'.
	 */
	public function testGetTypeReturnsNumber(): void {
		$this->assertSame( 'number', $this->field_type->getType() );
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
	 * Test getDefaultValue returns 0.
	 */
	public function testGetDefaultValueReturnsZero(): void {
		$this->assertSame( 0, $this->field_type->getDefaultValue() );
	}

	/**
	 * Test render generates correct HTML.
	 */
	public function testRenderGeneratesCorrectHtml(): void {
		$field = [
			'name'  => 'quantity',
			'label' => 'Quantity',
		];

		$result = $this->field_type->render( $field, 5 );

		$this->assertStringContainsString( 'type="number"', $result );
		$this->assertStringContainsString( 'name="apd_field_quantity"', $result );
		$this->assertStringContainsString( 'id="apd-field-quantity"', $result );
		$this->assertStringContainsString( 'value="5"', $result );
		$this->assertStringContainsString( 'step="1"', $result );
	}

	/**
	 * Test render with min attribute.
	 */
	public function testRenderWithMinAttribute(): void {
		$field = [
			'name' => 'quantity',
			'min'  => 1,
		];

		$result = $this->field_type->render( $field, 5 );

		$this->assertStringContainsString( 'min="1"', $result );
	}

	/**
	 * Test render with max attribute.
	 */
	public function testRenderWithMaxAttribute(): void {
		$field = [
			'name' => 'quantity',
			'max'  => 100,
		];

		$result = $this->field_type->render( $field, 5 );

		$this->assertStringContainsString( 'max="100"', $result );
	}

	/**
	 * Test render with step attribute.
	 */
	public function testRenderWithStepAttribute(): void {
		$field = [
			'name' => 'quantity',
			'step' => 5,
		];

		$result = $this->field_type->render( $field, 10 );

		$this->assertStringContainsString( 'step="5"', $result );
	}

	/**
	 * Test render with min, max, and step.
	 */
	public function testRenderWithMinMaxStep(): void {
		$field = [
			'name' => 'quantity',
			'min'  => 0,
			'max'  => 100,
			'step' => 10,
		];

		$result = $this->field_type->render( $field, 50 );

		$this->assertStringContainsString( 'min="0"', $result );
		$this->assertStringContainsString( 'max="100"', $result );
		$this->assertStringContainsString( 'step="10"', $result );
	}

	/**
	 * Test render with empty value.
	 */
	public function testRenderWithEmptyValue(): void {
		$field = [
			'name' => 'quantity',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'value=""', $result );
	}

	/**
	 * Test render with description.
	 */
	public function testRenderWithDescription(): void {
		$field = [
			'name'        => 'quantity',
			'description' => 'Enter quantity between 1 and 100',
		];

		$result = $this->field_type->render( $field, 5 );

		$this->assertStringContainsString( 'apd-field-description', $result );
		$this->assertStringContainsString( 'Enter quantity between 1 and 100', $result );
		$this->assertStringContainsString( 'aria-describedby', $result );
	}

	/**
	 * Test render required field.
	 */
	public function testRenderRequiredField(): void {
		$field = [
			'name'     => 'quantity',
			'required' => true,
		];

		$result = $this->field_type->render( $field, 5 );

		$this->assertStringContainsString( 'required', $result );
		$this->assertStringContainsString( 'aria-required="true"', $result );
	}

	/**
	 * Test sanitize with integer value.
	 */
	public function testSanitizeWithInteger(): void {
		$result = $this->field_type->sanitize( 42 );

		$this->assertSame( 42, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize with string integer.
	 */
	public function testSanitizeWithStringInteger(): void {
		$result = $this->field_type->sanitize( '42' );

		$this->assertSame( 42, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize with float truncates to integer.
	 */
	public function testSanitizeWithFloatTruncates(): void {
		$result = $this->field_type->sanitize( 42.7 );

		$this->assertSame( 42, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize with negative value.
	 */
	public function testSanitizeWithNegativeValue(): void {
		$result = $this->field_type->sanitize( -15 );

		$this->assertSame( -15, $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Test sanitize with empty string returns zero.
	 */
	public function testSanitizeWithEmptyStringReturnsZero(): void {
		$result = $this->field_type->sanitize( '' );

		$this->assertSame( 0, $result );
	}

	/**
	 * Test sanitize with null returns zero.
	 */
	public function testSanitizeWithNullReturnsZero(): void {
		$result = $this->field_type->sanitize( null );

		$this->assertSame( 0, $result );
	}

	/**
	 * Test sanitize with non-numeric string.
	 */
	public function testSanitizeWithNonNumericString(): void {
		$result = $this->field_type->sanitize( 'abc' );

		$this->assertSame( 0, $result );
	}

	/**
	 * Test validate with valid integer.
	 */
	public function testValidateWithValidInteger(): void {
		$field = [
			'name'  => 'quantity',
			'label' => 'Quantity',
		];

		$result = $this->field_type->validate( 42, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate empty optional field passes.
	 */
	public function testValidateEmptyOptionalFieldPasses(): void {
		$field = [
			'name'     => 'quantity',
			'label'    => 'Quantity',
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
			'name'     => 'quantity',
			'label'    => 'Quantity',
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
			'name'     => 'quantity',
			'label'    => 'Quantity',
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
			'name'  => 'quantity',
			'label' => 'Quantity',
			'min'   => 10,
		];

		$result = $this->field_type->validate( 5, $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'min_value', $result->get_error_codes() );
	}

	/**
	 * Test validate value at minimum passes.
	 */
	public function testValidateValueAtMinimumPasses(): void {
		$field = [
			'name'  => 'quantity',
			'label' => 'Quantity',
			'min'   => 10,
		];

		$result = $this->field_type->validate( 10, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate value above maximum fails.
	 */
	public function testValidateValueAboveMaximumFails(): void {
		$field = [
			'name'  => 'quantity',
			'label' => 'Quantity',
			'max'   => 100,
		];

		$result = $this->field_type->validate( 150, $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'max_value', $result->get_error_codes() );
	}

	/**
	 * Test validate value at maximum passes.
	 */
	public function testValidateValueAtMaximumPasses(): void {
		$field = [
			'name'  => 'quantity',
			'label' => 'Quantity',
			'max'   => 100,
		];

		$result = $this->field_type->validate( 100, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate value within range passes.
	 */
	public function testValidateValueWithinRangePasses(): void {
		$field = [
			'name'  => 'quantity',
			'label' => 'Quantity',
			'min'   => 1,
			'max'   => 100,
		];

		$result = $this->field_type->validate( 50, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate zero is valid when allowed.
	 */
	public function testValidateZeroIsValidWhenAllowed(): void {
		$field = [
			'name'  => 'quantity',
			'label' => 'Quantity',
			'min'   => 0,
		];

		$result = $this->field_type->validate( 0, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate negative value with negative min.
	 */
	public function testValidateNegativeValueWithNegativeMin(): void {
		$field = [
			'name'  => 'temperature',
			'label' => 'Temperature',
			'min'   => -50,
			'max'   => 50,
		];

		$result = $this->field_type->validate( -20, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test formatValue with integer.
	 */
	public function testFormatValueWithInteger(): void {
		$field = [
			'name' => 'quantity',
		];

		$result = $this->field_type->formatValue( 42, $field );

		$this->assertSame( '42', $result );
	}

	/**
	 * Test formatValue with empty value.
	 */
	public function testFormatValueWithEmptyValue(): void {
		$field = [
			'name' => 'quantity',
		];

		$result = $this->field_type->formatValue( '', $field );

		$this->assertSame( '', $result );
	}
}
