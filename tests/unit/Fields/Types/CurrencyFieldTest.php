<?php
/**
 * Unit tests for CurrencyField.
 *
 * Tests currency field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\CurrencyField;
use APD\Tests\Unit\UnitTestCase;
use WP_Error;

/**
 * Test case for CurrencyField class.
 *
 * @covers \APD\Fields\Types\CurrencyField
 */
class CurrencyFieldTest extends UnitTestCase {

	/**
	 * The field type instance.
	 *
	 * @var CurrencyField
	 */
	private CurrencyField $field_type;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field_type = new CurrencyField();
	}

	/**
	 * Test getType returns 'currency'.
	 */
	public function testGetTypeReturnsCurrency(): void {
		$this->assertSame( 'currency', $this->field_type->getType() );
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
	 * Test render generates correct HTML with default settings.
	 */
	public function testRenderGeneratesCorrectHtml(): void {
		$field = [
			'name'  => 'price',
			'label' => 'Price',
		];

		$result = $this->field_type->render( $field, 99.99 );

		$this->assertStringContainsString( 'type="number"', $result );
		$this->assertStringContainsString( 'name="apd_field_price"', $result );
		$this->assertStringContainsString( 'id="apd-field-price"', $result );
		$this->assertStringContainsString( 'value="99.99"', $result );
		$this->assertStringContainsString( 'step="0.01"', $result );
		$this->assertStringContainsString( 'min="0"', $result ); // Default non-negative.
	}

	/**
	 * Test render with currency symbol before (default).
	 */
	public function testRenderWithCurrencySymbolBefore(): void {
		$field = [
			'name'            => 'price',
			'currency_symbol' => '$',
		];

		$result = $this->field_type->render( $field, 99.99 );

		$this->assertStringContainsString( 'apd-currency-before', $result );
		$this->assertStringContainsString( 'apd-currency-symbol', $result );
		$this->assertStringContainsString( '$', $result );
	}

	/**
	 * Test render with currency symbol after.
	 */
	public function testRenderWithCurrencySymbolAfter(): void {
		$field = [
			'name'              => 'price',
			'currency_symbol'   => '€',
			'currency_position' => 'after',
		];

		$result = $this->field_type->render( $field, 99.99 );

		$this->assertStringContainsString( 'apd-currency-after', $result );
		$this->assertStringContainsString( '€', $result );
	}

	/**
	 * Test render with custom precision.
	 */
	public function testRenderWithCustomPrecision(): void {
		$field = [
			'name'      => 'price',
			'precision' => 4,
		];

		$result = $this->field_type->render( $field, 99.9999 );

		$this->assertStringContainsString( 'value="99.9999"', $result );
		$this->assertStringContainsString( 'step="0.0001"', $result );
	}

	/**
	 * Test render with min attribute.
	 */
	public function testRenderWithMinAttribute(): void {
		$field = [
			'name' => 'price',
			'min'  => 10.00,
		];

		$result = $this->field_type->render( $field, 99.99 );

		$this->assertStringContainsString( 'min="10"', $result );
	}

	/**
	 * Test render with max attribute.
	 */
	public function testRenderWithMaxAttribute(): void {
		$field = [
			'name' => 'price',
			'max'  => 1000.00,
		];

		$result = $this->field_type->render( $field, 99.99 );

		$this->assertStringContainsString( 'max="1000"', $result );
	}

	/**
	 * Test render with allow_negative removes min=0 default.
	 */
	public function testRenderWithAllowNegativeRemovesMinDefault(): void {
		$field = [
			'name'           => 'balance',
			'allow_negative' => true,
		];

		$result = $this->field_type->render( $field, -50.00 );

		// Should not have min="0" when negative is allowed and no min set.
		$this->assertStringNotContainsString( 'min="0"', $result );
	}

	/**
	 * Test render with empty value.
	 */
	public function testRenderWithEmptyValue(): void {
		$field = [
			'name' => 'price',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'value=""', $result );
	}

	/**
	 * Test render with description.
	 */
	public function testRenderWithDescription(): void {
		$field = [
			'name'        => 'price',
			'description' => 'Enter the product price',
		];

		$result = $this->field_type->render( $field, 99.99 );

		$this->assertStringContainsString( 'apd-field-description', $result );
		$this->assertStringContainsString( 'Enter the product price', $result );
		$this->assertStringContainsString( 'aria-describedby', $result );
	}

	/**
	 * Test render required field.
	 */
	public function testRenderRequiredField(): void {
		$field = [
			'name'     => 'price',
			'required' => true,
		];

		$result = $this->field_type->render( $field, 99.99 );

		$this->assertStringContainsString( 'required', $result );
		$this->assertStringContainsString( 'aria-required="true"', $result );
	}

	/**
	 * Test render includes aria-hidden on currency symbol.
	 */
	public function testRenderCurrencySymbolHasAriaHidden(): void {
		$field = [
			'name' => 'price',
		];

		$result = $this->field_type->render( $field, 99.99 );

		$this->assertStringContainsString( 'aria-hidden="true"', $result );
	}

	/**
	 * Test sanitize with float value.
	 */
	public function testSanitizeWithFloat(): void {
		$result = $this->field_type->sanitize( 99.999 );

		$this->assertSame( 100.0, $result );
		$this->assertIsFloat( $result );
	}

	/**
	 * Test sanitize with string float.
	 */
	public function testSanitizeWithStringFloat(): void {
		$result = $this->field_type->sanitize( '99.99' );

		$this->assertSame( 99.99, $result );
		$this->assertIsFloat( $result );
	}

	/**
	 * Test sanitize with custom precision.
	 */
	public function testSanitizeWithCustomPrecision(): void {
		$result = $this->field_type->sanitize( 99.9999, 4 );

		$this->assertSame( 99.9999, $result );
	}

	/**
	 * Test sanitize with negative value.
	 */
	public function testSanitizeWithNegativeValue(): void {
		$result = $this->field_type->sanitize( -50.50 );

		$this->assertSame( -50.50, $result );
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
	 * Test sanitizeWithField uses field precision.
	 */
	public function testSanitizeWithFieldUsesPrecision(): void {
		$field = [
			'name'      => 'price',
			'precision' => 4,
		];

		$result = $this->field_type->sanitizeWithField( 99.99999, $field );

		$this->assertSame( 100.0, $result );
	}

	/**
	 * Test validate with valid currency value.
	 */
	public function testValidateWithValidCurrency(): void {
		$field = [
			'name'  => 'price',
			'label' => 'Price',
		];

		$result = $this->field_type->validate( 99.99, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate empty optional field passes.
	 */
	public function testValidateEmptyOptionalFieldPasses(): void {
		$field = [
			'name'     => 'price',
			'label'    => 'Price',
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
			'name'     => 'price',
			'label'    => 'Price',
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
			'name'     => 'price',
			'label'    => 'Price',
			'required' => true,
		];

		$result = $this->field_type->validate( 'abc', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'not_numeric', $result->get_error_codes() );
	}

	/**
	 * Test validate negative value fails by default.
	 */
	public function testValidateNegativeValueFailsByDefault(): void {
		$field = [
			'name'  => 'price',
			'label' => 'Price',
		];

		$result = $this->field_type->validate( -10.00, $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'negative_value', $result->get_error_codes() );
	}

	/**
	 * Test validate negative value passes when allowed.
	 */
	public function testValidateNegativeValuePassesWhenAllowed(): void {
		$field = [
			'name'           => 'balance',
			'label'          => 'Balance',
			'allow_negative' => true,
		];

		$result = $this->field_type->validate( -50.00, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate value below minimum fails.
	 */
	public function testValidateValueBelowMinimumFails(): void {
		$field = [
			'name'  => 'price',
			'label' => 'Price',
			'min'   => 10.00,
		];

		$result = $this->field_type->validate( 5.00, $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'min_value', $result->get_error_codes() );
	}

	/**
	 * Test validate value at minimum passes.
	 */
	public function testValidateValueAtMinimumPasses(): void {
		$field = [
			'name'  => 'price',
			'label' => 'Price',
			'min'   => 10.00,
		];

		$result = $this->field_type->validate( 10.00, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate value above maximum fails.
	 */
	public function testValidateValueAboveMaximumFails(): void {
		$field = [
			'name'  => 'price',
			'label' => 'Price',
			'max'   => 100.00,
		];

		$result = $this->field_type->validate( 150.00, $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'max_value', $result->get_error_codes() );
	}

	/**
	 * Test validate value at maximum passes.
	 */
	public function testValidateValueAtMaximumPasses(): void {
		$field = [
			'name'  => 'price',
			'label' => 'Price',
			'max'   => 100.00,
		];

		$result = $this->field_type->validate( 100.00, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate value within range passes.
	 */
	public function testValidateValueWithinRangePasses(): void {
		$field = [
			'name'  => 'price',
			'label' => 'Price',
			'min'   => 10.00,
			'max'   => 100.00,
		];

		$result = $this->field_type->validate( 50.00, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate zero is valid.
	 */
	public function testValidateZeroIsValid(): void {
		$field = [
			'name'  => 'price',
			'label' => 'Price',
		];

		$result = $this->field_type->validate( 0, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test formatValue with currency symbol before (default).
	 */
	public function testFormatValueWithSymbolBefore(): void {
		$field = [
			'name'            => 'price',
			'currency_symbol' => '$',
		];

		$result = $this->field_type->formatValue( 99.99, $field );

		$this->assertSame( '$99.99', $result );
	}

	/**
	 * Test formatValue with currency symbol after.
	 */
	public function testFormatValueWithSymbolAfter(): void {
		$field = [
			'name'              => 'price',
			'currency_symbol'   => '€',
			'currency_position' => 'after',
		];

		$result = $this->field_type->formatValue( 99.99, $field );

		$this->assertSame( '99.99€', $result );
	}

	/**
	 * Test formatValue with custom precision.
	 */
	public function testFormatValueWithCustomPrecision(): void {
		$field = [
			'name'            => 'price',
			'currency_symbol' => '$',
			'precision'       => 4,
		];

		$result = $this->field_type->formatValue( 99.9999, $field );

		$this->assertSame( '$99.9999', $result );
	}

	/**
	 * Test formatValue with large number has comma separators.
	 */
	public function testFormatValueWithLargeNumberHasCommas(): void {
		$field = [
			'name'            => 'price',
			'currency_symbol' => '$',
		];

		$result = $this->field_type->formatValue( 1234567.89, $field );

		$this->assertSame( '$1,234,567.89', $result );
	}

	/**
	 * Test formatValue with empty value.
	 */
	public function testFormatValueWithEmptyValue(): void {
		$field = [
			'name' => 'price',
		];

		$result = $this->field_type->formatValue( '', $field );

		$this->assertSame( '', $result );
	}

	/**
	 * Test formatValue with null value.
	 */
	public function testFormatValueWithNullValue(): void {
		$field = [
			'name' => 'price',
		];

		$result = $this->field_type->formatValue( null, $field );

		$this->assertSame( '', $result );
	}

	/**
	 * Test formatValue default currency symbol is $.
	 */
	public function testFormatValueDefaultSymbolIsDollar(): void {
		$field = [
			'name' => 'price',
		];

		$result = $this->field_type->formatValue( 50.00, $field );

		$this->assertSame( '$50.00', $result );
	}

	/**
	 * Test formatValue default position is before.
	 */
	public function testFormatValueDefaultPositionIsBefore(): void {
		$field = [
			'name'            => 'price',
			'currency_symbol' => '£',
		];

		$result = $this->field_type->formatValue( 50.00, $field );

		$this->assertSame( '£50.00', $result );
	}
}
