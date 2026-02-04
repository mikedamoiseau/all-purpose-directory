<?php
/**
 * Unit tests for MultiSelectField.
 *
 * Tests multi-select field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\MultiSelectField;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use WP_Error;

/**
 * Test case for MultiSelectField class.
 *
 * @covers \APD\Fields\Types\MultiSelectField
 */
class MultiSelectFieldTest extends UnitTestCase {

	/**
	 * The multi-select field instance.
	 *
	 * @var MultiSelectField
	 */
	private MultiSelectField $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new MultiSelectField();
	}

	/**
	 * Set up WordPress functions specific to MultiSelectField.
	 */
	protected function setUpWordPressFunctions(): void {
		parent::setUpWordPressFunctions();

		// Mock wp_json_encode.
		Functions\stubs([
			'wp_json_encode' => static fn( $data ) => json_encode( $data ),
		]);
	}

	/**
	 * Test getType returns 'multiselect'.
	 */
	public function testGetTypeReturnsMultiselect(): void {
		$this->assertSame( 'multiselect', $this->field->getType() );
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
			'name'    => 'features',
			'label'   => 'Features',
			'options' => [
				'wifi'    => 'WiFi',
				'parking' => 'Parking',
				'pool'    => 'Pool',
			],
		];

		$html = $this->field->render( $field, [] );

		$this->assertStringContainsString( '<select', $html );
		$this->assertStringContainsString( '</select>', $html );
		$this->assertStringContainsString( 'name="apd_field_features[]"', $html );
		$this->assertStringContainsString( 'id="apd-field-features"', $html );
		$this->assertStringContainsString( 'multiple', $html );
		$this->assertStringContainsString( '<option value="wifi">WiFi</option>', $html );
		$this->assertStringContainsString( '<option value="parking">Parking</option>', $html );
		$this->assertStringContainsString( '<option value="pool">Pool</option>', $html );
	}

	/**
	 * Test render with selected values.
	 */
	public function testRenderWithSelectedValues(): void {
		$field = [
			'name'    => 'features',
			'label'   => 'Features',
			'options' => [
				'wifi'    => 'WiFi',
				'parking' => 'Parking',
				'pool'    => 'Pool',
			],
		];

		$html = $this->field->render( $field, [ 'wifi', 'pool' ] );

		$this->assertStringContainsString( '<option value="wifi" selected="selected">WiFi</option>', $html );
		$this->assertStringContainsString( '<option value="parking">Parking</option>', $html );
		$this->assertStringContainsString( '<option value="pool" selected="selected">Pool</option>', $html );
	}

	/**
	 * Test render with required field.
	 */
	public function testRenderWithRequiredField(): void {
		$field = [
			'name'     => 'features',
			'label'    => 'Features',
			'required' => true,
			'options'  => [
				'wifi' => 'WiFi',
			],
		];

		$html = $this->field->render( $field, [] );

		$this->assertStringContainsString( 'required', $html );
		$this->assertStringContainsString( 'aria-required="true"', $html );
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
			'name'    => 'features',
			'label'   => 'Features',
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
			'name'    => 'features',
			'label'   => 'Features',
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
			'name'     => 'features',
			'label'    => 'Features',
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
			'name'     => 'features',
			'label'    => 'Features',
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
			'name'    => 'features',
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
			'name'    => 'features',
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
