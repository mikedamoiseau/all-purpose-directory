<?php
/**
 * Unit tests for TextareaField.
 *
 * Tests textarea field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\TextareaField;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use WP_Error;

/**
 * Test case for TextareaField class.
 *
 * @covers \APD\Fields\Types\TextareaField
 */
class TextareaFieldTest extends UnitTestCase {

	/**
	 * The field type instance.
	 *
	 * @var TextareaField
	 */
	private TextareaField $field_type;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Add sanitize_textarea_field stub.
		Functions\stubs([
			'sanitize_textarea_field' => static function ( $str ) {
				// Preserve newlines but strip tags and trim.
				$str = strip_tags( $str );
				// Normalize line endings.
				$str = str_replace( [ "\r\n", "\r" ], "\n", $str );
				return trim( $str );
			},
		]);

		$this->field_type = new TextareaField();
	}

	/**
	 * Test getType returns 'textarea'.
	 */
	public function testGetTypeReturnsTextarea(): void {
		$this->assertSame( 'textarea', $this->field_type->getType() );
	}

	/**
	 * Test rendering a basic textarea field.
	 */
	public function testRenderBasicField(): void {
		$field = [
			'name'  => 'test_field',
			'label' => 'Test Field',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( '<textarea', $result );
		$this->assertStringContainsString( '</textarea>', $result );
		$this->assertStringContainsString( 'name="apd_field_test_field"', $result );
		$this->assertStringContainsString( 'id="apd-field-test_field"', $result );
	}

	/**
	 * Test rendering with default rows.
	 */
	public function testRenderWithDefaultRows(): void {
		$field = [
			'name'  => 'test_field',
			'label' => 'Test Field',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'rows="5"', $result );
	}

	/**
	 * Test rendering with custom rows.
	 */
	public function testRenderWithCustomRows(): void {
		$field = [
			'name'  => 'test_field',
			'label' => 'Test Field',
			'rows'  => 10,
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'rows="10"', $result );
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

		$this->assertStringContainsString( '>Hello World</textarea>', $result );
	}

	/**
	 * Test rendering with multiline value.
	 */
	public function testRenderWithMultilineValue(): void {
		$field = [
			'name'  => 'test_field',
			'label' => 'Test Field',
		];
		$value = "Line 1\nLine 2\nLine 3";

		$result = $this->field_type->render( $field, $value );

		$this->assertStringContainsString( $value, $result );
	}

	/**
	 * Test rendering with placeholder.
	 */
	public function testRenderWithPlaceholder(): void {
		$field = [
			'name'        => 'test_field',
			'label'       => 'Test Field',
			'placeholder' => 'Enter description here',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'placeholder="Enter description here"', $result );
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
			'class' => 'my-custom-textarea',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'class="my-custom-textarea"', $result );
	}

	/**
	 * Test rendering with description.
	 */
	public function testRenderWithDescription(): void {
		$field = [
			'name'        => 'test_field',
			'label'       => 'Test Field',
			'description' => 'Enter a detailed description',
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'aria-describedby="apd-field-test_field-description"', $result );
		$this->assertStringContainsString( 'class="apd-field-description"', $result );
		$this->assertStringContainsString( 'Enter a detailed description', $result );
	}

	/**
	 * Test rendering with max length validation.
	 */
	public function testRenderWithMaxLength(): void {
		$field = [
			'name'       => 'test_field',
			'label'      => 'Test Field',
			'validation' => [
				'max_length' => 500,
			],
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'maxlength="500"', $result );
	}

	/**
	 * Test rendering with min length validation.
	 */
	public function testRenderWithMinLength(): void {
		$field = [
			'name'       => 'test_field',
			'label'      => 'Test Field',
			'validation' => [
				'min_length' => 10,
			],
		];

		$result = $this->field_type->render( $field, '' );

		$this->assertStringContainsString( 'minlength="10"', $result );
	}

	/**
	 * Test sanitization preserves newlines.
	 */
	public function testSanitizePreservesNewlines(): void {
		$input = "Line 1\nLine 2\nLine 3";

		$result = $this->field_type->sanitize( $input );

		$this->assertStringContainsString( "\n", $result );
		$this->assertSame( $input, $result );
	}

	/**
	 * Test sanitization strips tags.
	 */
	public function testSanitizeStripsTags(): void {
		$input = '<script>alert("xss")</script>Hello';

		$result = $this->field_type->sanitize( $input );

		$this->assertStringNotContainsString( '<script>', $result );
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
		$input = [ "  One  ", "Two\nThree", '<b>Four</b>' ];

		$result = $this->field_type->sanitize( $input );

		$this->assertIsArray( $result );
		$this->assertSame( 'One', $result[0] );
		$this->assertStringContainsString( "\n", $result[1] );
		$this->assertSame( 'Four', $result[2] );
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

		$result = $this->field_type->validate( "Line 1\nLine 2", $field );

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
				'max_length' => 10,
			],
		];

		// Too long.
		$result = $this->field_type->validate( 'This is a very long text', $field );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'max_length', $result->get_error_codes() );

		// Within limit.
		$result = $this->field_type->validate( 'Short', $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test supports searchable.
	 */
	public function testSupportsSearchable(): void {
		$this->assertTrue( $this->field_type->supports( 'searchable' ) );
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
	 * Test formatValue converts newlines to br tags.
	 */
	public function testFormatValueConvertsNewlinesToBr(): void {
		$field = [ 'name' => 'test' ];
		$value = "Line 1\nLine 2\nLine 3";

		$result = $this->field_type->formatValue( $value, $field );

		$this->assertStringContainsString( '<br', $result );
		$this->assertStringContainsString( 'Line 1', $result );
		$this->assertStringContainsString( 'Line 2', $result );
		$this->assertStringContainsString( 'Line 3', $result );
	}

	/**
	 * Test formatValue with array returns comma-separated string.
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
		$value = "Line 1\nLine 2";

		$result = $this->field_type->prepareValueForStorage( $value );

		$this->assertSame( $value, $result );
	}

	/**
	 * Test prepareValueFromStorage returns unchanged.
	 */
	public function testPrepareValueFromStorageReturnsUnchanged(): void {
		$value = "Line 1\nLine 2";

		$result = $this->field_type->prepareValueFromStorage( $value );

		$this->assertSame( $value, $result );
	}
}
