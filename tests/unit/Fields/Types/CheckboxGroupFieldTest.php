<?php
/**
 * Unit tests for CheckboxGroupField.
 *
 * Tests checkbox group field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\CheckboxGroupField;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use WP_Error;

/**
 * Test case for CheckboxGroupField class.
 *
 * @covers \APD\Fields\Types\CheckboxGroupField
 */
class CheckboxGroupFieldTest extends UnitTestCase {

	/**
	 * The checkbox group field instance.
	 *
	 * @var CheckboxGroupField
	 */
	private CheckboxGroupField $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new CheckboxGroupField();
	}

	/**
	 * Set up WordPress functions specific to CheckboxGroupField.
	 */
	protected function setUpWordPressFunctions(): void {
		parent::setUpWordPressFunctions();

		// Mock wp_json_encode.
		Functions\stubs([
			'wp_json_encode' => static fn( $data ) => json_encode( $data ),
		]);
	}

	/**
	 * Test getType returns 'checkboxgroup'.
	 */
	public function testGetTypeReturnsCheckboxgroup(): void {
		$this->assertSame( 'checkboxgroup', $this->field->getType() );
	}

	/**
	 * Test supports returns correct values for features.
	 */
	public function testSupportsReturnsCorrectValues(): void {
		$this->assertFalse( $this->field->supports( 'searchable' ) );
		$this->assertTrue( $this->field->supports( 'filterable' ) );
		$this->assertFalse( $this->field->supports( 'sortable' ) );
		$this->assertTrue( $this->field->supports( 'repeater' ) );
	}

	/**
	 * Test render generates correct HTML structure.
	 */
	public function testRenderGeneratesCorrectHtml(): void {
		$field = [
			'name'    => 'amenities',
			'label'   => 'Amenities',
			'options' => [
				'wifi'    => 'WiFi',
				'parking' => 'Parking',
				'pool'    => 'Pool',
			],
		];

		$html = $this->field->render( $field, [] );

		$this->assertStringContainsString( '<fieldset', $html );
		$this->assertStringContainsString( '</fieldset>', $html );
		$this->assertStringContainsString( 'apd-checkbox-group', $html );
		$this->assertStringContainsString( 'id="apd-field-amenities"', $html );
		$this->assertStringContainsString( '<legend', $html );
		$this->assertStringContainsString( 'Amenities', $html );
		$this->assertStringContainsString( 'type="checkbox"', $html );
		$this->assertStringContainsString( 'name="apd_field_amenities[]"', $html );
		$this->assertStringContainsString( 'value="wifi"', $html );
		$this->assertStringContainsString( 'WiFi', $html );
		$this->assertStringContainsString( 'value="parking"', $html );
		$this->assertStringContainsString( 'Parking', $html );
		$this->assertStringContainsString( 'value="pool"', $html );
		$this->assertStringContainsString( 'Pool', $html );
	}

	/**
	 * Test render with checked values.
	 */
	public function testRenderWithCheckedValues(): void {
		$field = [
			'name'    => 'amenities',
			'label'   => 'Amenities',
			'options' => [
				'wifi'    => 'WiFi',
				'parking' => 'Parking',
				'pool'    => 'Pool',
			],
		];

		$html = $this->field->render( $field, [ 'wifi', 'pool' ] );

		// Count checked attributes - should be exactly 2.
		$this->assertSame( 2, substr_count( $html, 'checked="checked"' ) );
	}

	/**
	 * Test render with required field.
	 */
	public function testRenderWithRequiredField(): void {
		$field = [
			'name'     => 'amenities',
			'label'    => 'Amenities',
			'required' => true,
			'options'  => [
				'wifi' => 'WiFi',
			],
		];

		$html = $this->field->render( $field, [] );

		$this->assertStringContainsString( 'aria-required="true"', $html );
	}

	/**
	 * Test render with description.
	 */
	public function testRenderWithDescription(): void {
		$field = [
			'name'        => 'amenities',
			'label'       => 'Amenities',
			'description' => 'Select available amenities.',
			'options'     => [
				'wifi' => 'WiFi',
			],
		];

		$html = $this->field->render( $field, [] );

		$this->assertStringContainsString( 'apd-field-description', $html );
		$this->assertStringContainsString( 'Select available amenities.', $html );
		$this->assertStringContainsString( 'aria-describedby', $html );
	}

	/**
	 * Test render generates unique IDs for each option.
	 */
	public function testRenderGeneratesUniqueIds(): void {
		$field = [
			'name'    => 'amenities',
			'label'   => 'Amenities',
			'options' => [
				'wifi'    => 'WiFi',
				'parking' => 'Parking',
			],
		];

		$html = $this->field->render( $field, [] );

		$this->assertStringContainsString( 'id="apd-field-amenities-0"', $html );
		$this->assertStringContainsString( 'id="apd-field-amenities-1"', $html );
	}

	/**
	 * Test sanitize returns array of clean values.
	 */
	public function testSanitizeReturnsCleanArray(): void {
		$result = $this->field->sanitize( [ '  wifi  ', '<b>parking</b>', 'pool' ] );

		$this->assertIsArray( $result );
		$this->assertSame( [ 'wifi', 'parking', 'pool' ], $result );
	}

	/**
	 * Test sanitize returns empty array for non-array input.
	 */
	public function testSanitizeReturnsEmptyArrayForNonArray(): void {
		$result = $this->field->sanitize( 'not an array' );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Test validate passes with valid options.
	 */
	public function testValidatePassesWithValidOptions(): void {
		$field = [
			'name'    => 'amenities',
			'label'   => 'Amenities',
			'options' => [
				'wifi'    => 'WiFi',
				'parking' => 'Parking',
				'pool'    => 'Pool',
			],
		];

		$result = $this->field->validate( [ 'wifi', 'pool' ], $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate fails with invalid option.
	 */
	public function testValidateFailsWithInvalidOption(): void {
		$field = [
			'name'    => 'amenities',
			'label'   => 'Amenities',
			'options' => [
				'wifi'    => 'WiFi',
				'parking' => 'Parking',
			],
		];

		$result = $this->field->validate( [ 'wifi', 'invalid' ], $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'invalid_option', $result->get_error_codes() );
	}

	/**
	 * Test validate passes for empty optional field.
	 */
	public function testValidatePassesForEmptyOptionalField(): void {
		$field = [
			'name'     => 'amenities',
			'label'    => 'Amenities',
			'required' => false,
			'options'  => [
				'wifi' => 'WiFi',
			],
		];

		$result = $this->field->validate( [], $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate fails for empty required field.
	 */
	public function testValidateFailsForEmptyRequiredField(): void {
		$field = [
			'name'     => 'amenities',
			'label'    => 'Amenities',
			'required' => true,
			'options'  => [
				'wifi' => 'WiFi',
			],
		];

		$result = $this->field->validate( [], $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'required', $result->get_error_codes() );
	}

	/**
	 * Test formatValue returns comma-separated labels.
	 */
	public function testFormatValueReturnsCommaSeparatedLabels(): void {
		$field = [
			'name'    => 'amenities',
			'options' => [
				'wifi'    => 'WiFi',
				'parking' => 'Parking',
				'pool'    => 'Pool',
			],
		];

		$result = $this->field->formatValue( [ 'wifi', 'pool' ], $field );

		$this->assertSame( 'WiFi, Pool', $result );
	}

	/**
	 * Test formatValue returns empty string for empty value.
	 */
	public function testFormatValueReturnsEmptyForEmptyValue(): void {
		$field = [
			'name'    => 'amenities',
			'options' => [
				'wifi' => 'WiFi',
			],
		];

		$result = $this->field->formatValue( [], $field );

		$this->assertSame( '', $result );
	}

	/**
	 * Test getDefaultValue returns empty array.
	 */
	public function testGetDefaultValueReturnsEmptyArray(): void {
		$this->assertSame( [], $this->field->getDefaultValue() );
	}

	/**
	 * Test prepareValueForStorage returns JSON encoded array.
	 */
	public function testPrepareValueForStorageReturnsJson(): void {
		$result = $this->field->prepareValueForStorage( [ 'wifi', 'pool' ] );

		$this->assertSame( '["wifi","pool"]', $result );
	}

	/**
	 * Test prepareValueForStorage returns empty JSON array for non-array.
	 */
	public function testPrepareValueForStorageReturnsEmptyJsonForNonArray(): void {
		$result = $this->field->prepareValueForStorage( 'not an array' );

		$this->assertSame( '[]', $result );
	}

	/**
	 * Test prepareValueFromStorage returns decoded array.
	 */
	public function testPrepareValueFromStorageReturnsArray(): void {
		$result = $this->field->prepareValueFromStorage( '["wifi","pool"]' );

		$this->assertSame( [ 'wifi', 'pool' ], $result );
	}

	/**
	 * Test prepareValueFromStorage returns array if already array.
	 */
	public function testPrepareValueFromStorageReturnsArrayIfAlreadyArray(): void {
		$result = $this->field->prepareValueFromStorage( [ 'wifi', 'pool' ] );

		$this->assertSame( [ 'wifi', 'pool' ], $result );
	}

	/**
	 * Test prepareValueFromStorage returns empty array for invalid JSON.
	 */
	public function testPrepareValueFromStorageReturnsEmptyArrayForInvalidJson(): void {
		$result = $this->field->prepareValueFromStorage( 'invalid json' );

		$this->assertSame( [], $result );
	}

	/**
	 * Test prepareValueFromStorage returns empty array for empty string.
	 */
	public function testPrepareValueFromStorageReturnsEmptyArrayForEmptyString(): void {
		$result = $this->field->prepareValueFromStorage( '' );

		$this->assertSame( [], $result );
	}
}
