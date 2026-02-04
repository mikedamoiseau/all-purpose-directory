<?php
/**
 * Unit tests for TextField.
 *
 * Tests text field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\TextField;
use APD\Tests\Unit\UnitTestCase;
use WP_Error;

/**
 * Test case for TextField class.
 *
 * @covers \APD\Fields\Types\TextField
 */
class TextFieldTest extends UnitTestCase {

	/**
	 * The field type instance.
	 *
	 * @var TextField
	 */
	private TextField $field_type;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->field_type = new TextField();
	}

	/**
	 * Test getType returns 'text'.
	 */
	public function testGetTypeReturnsText(): void {
		$this->assertSame( 'text', $this->field_type->getType() );
	}

	/**
	 * Test rendering a basic text field.
	 */
	public function testRenderBasicField(): void {
		$field = [
			'name'  => 'test_field',
			'label' => 'Test Field',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( '<input', $result );
		$this->assertStringContainsString( 'type="text"', $result );
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

		$result = $this->field_type->render( $field, 'Hello World' );

		$this->assertStringContainsString( 'value="Hello World"', $result );
	}

	/**
	 * Test rendering with placeholder.
	 */
	public function testRenderWithPlaceholder(): void {
		$field = [
			'name'        => 'test_field',
			'label'       => 'Test Field',
			'placeholder' => 'Enter text here',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'placeholder="Enter text here"', $result );
	}

	/**
	 * Test rendering required field.
	 */
	public function testRenderRequiredField(): void {
		$field = [
			'name'     => 'test_field',
			'label'    => 'Test Field',
			'required' => true,
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'required', $result );
		$this->assertStringContainsString( 'aria-required="true"', $result );
	}

	/**
	 * Test rendering with custom CSS class.
	 */
	public function testRenderWithCustomClass(): void {
		$field = [
			'name'  => 'test_field',
			'label' => 'Test Field',
			'class' => 'my-custom-class',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'class="my-custom-class"', $result );
	}

	/**
	 * Test rendering with description.
	 */
	public function testRenderWithDescription(): void {
		$field = [
			'name'        => 'test_field',
			'label'       => 'Test Field',
			'description' => 'Help text for this field',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'aria-describedby="apd-field-test_field-description"', $result );
		$this->assertStringContainsString( 'class="apd-field-description"', $result );
		$this->assertStringContainsString( 'Help text for this field', $result );
	}

	/**
	 * Test rendering with max length validation.
	 */
	public function testRenderWithMaxLength(): void {
		$field = [
			'name'       => 'test_field',
			'label'      => 'Test Field',
			'validation' => [
				'max_length' => 100,
			],
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'maxlength="100"', $result );
	}

	/**
	 * Test rendering with min length validation.
	 */
	public function testRenderWithMinLength(): void {
		$field = [
			'name'       => 'test_field',
			'label'      => 'Test Field',
			'validation' => [
				'min_length' => 5,
			],
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'minlength="5"', $result );
	}

	/**
	 * Test rendering with pattern validation.
	 */
	public function testRenderWithPattern(): void {
		$field = [
			'name'       => 'test_field',
			'label'      => 'Test Field',
			'validation' => [
				'pattern' => '/^[A-Z]{3}$/',
			],
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'pattern="^[A-Z]{3}$"', $result );
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
				'autocomplete' => 'off',
			],
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'data-custom="value"', $result );
		$this->assertStringContainsString( 'autocomplete="off"', $result );
	}

	/**
	 * Test sanitization strips tags.
	 */
	public function testSanitizeStripsTags(): void {
		$input = '<script>alert("xss")</script>Hello';

		$result = $this->field_type->sanitize( $input );

		$this->assertStringNotContainsString( '<script>', $result );
		$this->assertStringNotContainsString( '</script>', $result );
	}

	/**
	 * Test sanitization trims whitespace.
	 */
	public function testSanitizeTrimsWhitespace(): void {
		$input = '  Hello World  ';

		$result = $this->field_type->sanitize( $input );

		$this->assertSame( 'Hello World', $result );
	}

	/**
	 * Test sanitization handles arrays.
	 */
	public function testSanitizeHandlesArrays(): void {
		$input = [ '  One  ', '<b>Two</b>', 'Three' ];

		$result = $this->field_type->sanitize( $input );

		$this->assertIsArray( $result );
		$this->assertSame( 'One', $result[0] );
		$this->assertSame( 'Two', $result[1] );
		$this->assertSame( 'Three', $result[2] );
	}

	/**
	 * Test validation passes with valid text.
	 */
	public function testValidateWithValidText(): void {
		$field = [
			'name'     => 'test_field',
			'label'    => 'Test Field',
			'required' => true,
		];

		$result = $this->field_type->validate( 'Valid text', $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validation handles empty value for optional field.
	 */
	public function testValidateEmptyOptionalField(): void {
		$field = [
			'name'     => 'test_field',
			'label'    => 'Test Field',
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
			'name'     => 'test_field',
			'label'    => 'Test Field',
			'required' => true,
		];

		$result = $this->field_type->validate( '', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'required', $result->get_error_codes() );
	}

	/**
	 * Test max length validation.
	 */
	public function testValidateMaxLength(): void {
		$field = [
			'name'       => 'test_field',
			'label'      => 'Test Field',
			'validation' => [
				'max_length' => 5,
			],
		];

		// Too long.
		$result = $this->field_type->validate( 'Too Long Text', $field );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'max_length', $result->get_error_codes() );

		// Within limit.
		$result = $this->field_type->validate( 'OK', $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test min length validation.
	 */
	public function testValidateMinLength(): void {
		$field = [
			'name'       => 'test_field',
			'label'      => 'Test Field',
			'validation' => [
				'min_length' => 5,
			],
		];

		// Too short.
		$result = $this->field_type->validate( 'Hi', $field );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'min_length', $result->get_error_codes() );

		// Long enough.
		$result = $this->field_type->validate( 'Hello World', $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test supports searchable.
	 */
	public function testSupportsSearchable(): void {
		$this->assertTrue( $this->field_type->supports( 'searchable' ) );
	}

	/**
	 * Test supports sortable.
	 */
	public function testSupportsSortable(): void {
		$this->assertTrue( $this->field_type->supports( 'sortable' ) );
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

		$result = $this->field_type->formatValue( 'Hello World', $field );

		$this->assertSame( 'Hello World', $result );
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
		$value = 'test value';

		$result = $this->field_type->prepareValueForStorage( $value );

		$this->assertSame( $value, $result );
	}

	/**
	 * Test prepareValueFromStorage returns unchanged.
	 */
	public function testPrepareValueFromStorageReturnsUnchanged(): void {
		$value = 'test value';

		$result = $this->field_type->prepareValueFromStorage( $value );

		$this->assertSame( $value, $result );
	}
}
