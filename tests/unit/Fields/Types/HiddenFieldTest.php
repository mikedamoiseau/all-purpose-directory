<?php
/**
 * Unit tests for HiddenField.
 *
 * Tests hidden field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\HiddenField;
use APD\Tests\Unit\UnitTestCase;

/**
 * Test case for HiddenField class.
 *
 * @covers \APD\Fields\Types\HiddenField
 */
class HiddenFieldTest extends UnitTestCase {

	/**
	 * The field type instance.
	 *
	 * @var HiddenField
	 */
	private HiddenField $field_type;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->field_type = new HiddenField();
	}

	/**
	 * Test getType returns 'hidden'.
	 */
	public function testGetTypeReturnsHidden(): void {
		$this->assertSame( 'hidden', $this->field_type->getType() );
	}

	/**
	 * Test rendering a basic hidden field.
	 */
	public function testRenderBasicField(): void {
		$field = [
			'name'  => 'test_field',
			'label' => 'Test Field',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( '<input', $result );
		$this->assertStringContainsString( 'type="hidden"', $result );
		$this->assertStringContainsString( 'name="apd_field_test_field"', $result );
		$this->assertStringContainsString( 'id="apd-field-test_field"', $result );
	}

	/**
	 * Test rendering with a value.
	 */
	public function testRenderWithValue(): void {
		$field = [
			'name'  => 'test_field',
			'label' => 'Test Field',
		];

		$result = $this->field_type->render( $field, 'secret_value' );

		$this->assertStringContainsString( 'value="secret_value"', $result );
	}

	/**
	 * Test rendering does not include label or description.
	 */
	public function testRenderDoesNotIncludeLabelOrDescription(): void {
		$field = [
			'name'        => 'test_field',
			'label'       => 'Test Field',
			'description' => 'This should not appear',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringNotContainsString( 'Test Field', $result );
		$this->assertStringNotContainsString( 'This should not appear', $result );
		$this->assertStringNotContainsString( 'apd-field-description', $result );
		$this->assertStringNotContainsString( 'aria-describedby', $result );
	}

	/**
	 * Test rendering does not include required attribute.
	 */
	public function testRenderDoesNotIncludeRequired(): void {
		$field = [
			'name'     => 'test_field',
			'label'    => 'Test Field',
			'required' => true,
		];

		$result = $this->field_type->render( $field, '' );

		// Hidden fields should not have required attribute since users cannot interact.
		$this->assertStringNotContainsString( 'aria-required', $result );
	}

	/**
	 * Test rendering with custom attributes.
	 */
	public function testRenderWithCustomAttributes(): void {
		$field = [
			'name'       => 'test_field',
			'label'      => 'Test Field',
			'attributes' => [
				'data-custom' => 'value',
			],
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'data-custom="value"', $result );
	}

	/**
	 * Test sanitization strips tags.
	 */
	public function testSanitizeStripsTags(): void {
		$input = '<script>alert("xss")</script>value';

		$result = $this->field_type->sanitize( $input );

		$this->assertStringNotContainsString( '<script>', $result );
	}

	/**
	 * Test sanitization trims whitespace.
	 */
	public function testSanitizeTrimsWhitespace(): void {
		$input = '  value  ';

		$result = $this->field_type->sanitize( $input );

		$this->assertSame( 'value', $result );
	}

	/**
	 * Test validation always passes regardless of required setting.
	 */
	public function testValidateAlwaysPasses(): void {
		$field = [
			'name'     => 'test_field',
			'label'    => 'Test Field',
			'required' => true, // This should be ignored for hidden fields.
		];

		// Empty value should pass.
		$result = $this->field_type->validate( '', $field );
		$this->assertTrue( $result );

		// Non-empty value should pass.
		$result = $this->field_type->validate( 'some value', $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test validation passes for empty value even when marked required.
	 */
	public function testValidatePassesForEmptyRequiredField(): void {
		$field = [
			'name'     => 'test_field',
			'label'    => 'Test Field',
			'required' => true,
		];

		$result = $this->field_type->validate( '', $field );

		// Hidden fields cannot be required since users cannot interact.
		$this->assertTrue( $result );
	}

	/**
	 * Test does not support searchable.
	 */
	public function testDoesNotSupportSearchable(): void {
		$this->assertFalse( $this->field_type->supports( 'searchable' ) );
	}

	/**
	 * Test does not support sortable.
	 */
	public function testDoesNotSupportSortable(): void {
		$this->assertFalse( $this->field_type->supports( 'sortable' ) );
	}

	/**
	 * Test does not support filterable.
	 */
	public function testDoesNotSupportFilterable(): void {
		$this->assertFalse( $this->field_type->supports( 'filterable' ) );
	}

	/**
	 * Test does not support repeater.
	 */
	public function testDoesNotSupportRepeater(): void {
		$this->assertFalse( $this->field_type->supports( 'repeater' ) );
	}

	/**
	 * Test getDefaultValue returns empty string.
	 */
	public function testGetDefaultValueReturnsEmptyString(): void {
		$this->assertSame( '', $this->field_type->getDefaultValue() );
	}

	/**
	 * Test formatValue with string.
	 */
	public function testFormatValueWithString(): void {
		$field = [ 'name' => 'test' ];

		$result = $this->field_type->formatValue( 'hidden_value', $field );

		$this->assertSame( 'hidden_value', $result );
	}

	/**
	 * Test formatValue with array.
	 */
	public function testFormatValueWithArray(): void {
		$field = [ 'name' => 'test' ];

		$result = $this->field_type->formatValue( [ 'one', 'two', 'three' ], $field );

		$this->assertSame( 'one, two, three', $result );
	}

	/**
	 * Test prepareValueForStorage returns unchanged.
	 */
	public function testPrepareValueForStorageReturnsUnchanged(): void {
		$value = 'test_value';

		$result = $this->field_type->prepareValueForStorage( $value );

		$this->assertSame( $value, $result );
	}

	/**
	 * Test prepareValueFromStorage returns unchanged.
	 */
	public function testPrepareValueFromStorageReturnsUnchanged(): void {
		$value = 'test_value';

		$result = $this->field_type->prepareValueFromStorage( $value );

		$this->assertSame( $value, $result );
	}
}
