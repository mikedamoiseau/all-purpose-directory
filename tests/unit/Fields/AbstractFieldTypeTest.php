<?php
/**
 * Unit tests for AbstractFieldType.
 *
 * Tests the base field type class default implementations.
 *
 * @package APD\Tests\Unit\Fields
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields;

use APD\Fields\AbstractFieldType;
use APD\Tests\Unit\UnitTestCase;
use WP_Error;

/**
 * Test case for AbstractFieldType class.
 *
 * @covers \APD\Fields\AbstractFieldType
 */
class AbstractFieldTypeTest extends UnitTestCase {

	/**
	 * Concrete implementation for testing.
	 *
	 * @var AbstractFieldType
	 */
	private AbstractFieldType $field_type;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Create a concrete implementation for testing.
		$this->field_type = new class() extends AbstractFieldType {
			public function getType(): string {
				return 'test';
			}

			public function render( array $field, mixed $value ): string {
				$attrs = $this->buildAttributes( $this->getCommonAttributes( $field ) );
				return sprintf(
					'<input type="text" %s value="%s">%s',
					$attrs,
					esc_attr( (string) $value ),
					$this->renderDescription( $field )
				);
			}

			// Expose protected methods for testing.
			public function testIsRequired( array $field ): bool {
				return $this->isRequired( $field );
			}

			public function testIsEmpty( mixed $value ): bool {
				return $this->isEmpty( $value );
			}

			public function testGetLabel( array $field ): string {
				return $this->getLabel( $field );
			}

			public function testGetFieldName( array $field ): string {
				return $this->getFieldName( $field );
			}

			public function testGetFieldId( array $field ): string {
				return $this->getFieldId( $field );
			}

			public function testBuildAttributes( array $attributes ): string {
				return $this->buildAttributes( $attributes );
			}

			public function testRenderDescription( array $field ): string {
				return $this->renderDescription( $field );
			}

			public function testApplyValidationRules( mixed $value, array $field ): bool|WP_Error {
				return $this->applyValidationRules( $value, $field );
			}
		};
	}

	/**
	 * Test getType returns the correct type.
	 */
	public function testGetTypeReturnsCorrectType(): void {
		$this->assertSame( 'test', $this->field_type->getType() );
	}

	/**
	 * Test default sanitization strips tags.
	 *
	 * Note: The mock sanitize_text_field uses PHP's strip_tags() which
	 * removes tags but keeps text content. Real WordPress would fully sanitize.
	 */
	public function testSanitizeStripsTags(): void {
		$input = '<b>Bold</b> text';

		$result = $this->field_type->sanitize( $input );

		// Mock uses strip_tags() which removes tags but keeps content.
		$this->assertSame( 'Bold text', $result );
		$this->assertStringNotContainsString( '<b>', $result );
	}

	/**
	 * Test sanitization trims whitespace.
	 */
	public function testSanitizeTrimsWhitespace(): void {
		$input    = '  Hello World  ';
		$expected = 'Hello World';

		$result = $this->field_type->sanitize( $input );

		$this->assertSame( $expected, $result );
	}

	/**
	 * Test sanitization handles arrays.
	 *
	 * Note: The mock sanitize_text_field uses PHP's strip_tags() which
	 * removes tags but keeps text content.
	 */
	public function testSanitizeHandlesArrays(): void {
		$input = [ '<b>One</b>', '  Two  ', 'Three' ];

		$result = $this->field_type->sanitize( $input );

		// Mock uses strip_tags() and trim().
		$this->assertSame( [ 'One', 'Two', 'Three' ], $result );
		$this->assertStringNotContainsString( '<b>', $result[0] );
	}

	/**
	 * Test validation passes for optional empty field.
	 */
	public function testValidatePassesForOptionalEmptyField(): void {
		$field = [
			'name'     => 'test_field',
			'label'    => 'Test Field',
			'required' => false,
		];

		$result = $this->field_type->validate( '', $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validation fails for required empty field.
	 */
	public function testValidateFailsForRequiredEmptyField(): void {
		$field = [
			'name'     => 'test_field',
			'label'    => 'Test Field',
			'required' => true,
		];

		$result = $this->field_type->validate( '', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertTrue( $result->has_errors() );
		$this->assertContains( 'required', $result->get_error_codes() );
	}

	/**
	 * Test validation passes for required field with value.
	 */
	public function testValidatePassesForRequiredFieldWithValue(): void {
		$field = [
			'name'     => 'test_field',
			'label'    => 'Test Field',
			'required' => true,
		];

		$result = $this->field_type->validate( 'test value', $field );

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
		$result = $this->field_type->validate( 'abc', $field );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'min_length', $result->get_error_codes() );

		// Long enough.
		$result = $this->field_type->validate( 'abcdef', $field );
		$this->assertTrue( $result );
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
		$result = $this->field_type->validate( 'abcdefghij', $field );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'max_length', $result->get_error_codes() );

		// Short enough.
		$result = $this->field_type->validate( 'abc', $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test pattern validation.
	 */
	public function testValidatePattern(): void {
		$field = [
			'name'       => 'test_field',
			'label'      => 'Test Field',
			'validation' => [
				'pattern' => '/^[A-Z]{3}[0-9]{3}$/',
			],
		];

		// Invalid pattern.
		$result = $this->field_type->validate( 'abc123', $field );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'pattern', $result->get_error_codes() );

		// Valid pattern.
		$result = $this->field_type->validate( 'ABC123', $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test custom callback validation.
	 */
	public function testValidateCallback(): void {
		$field = [
			'name'       => 'test_field',
			'label'      => 'Test Field',
			'validation' => [
				'callback' => function ( $value ) {
					return $value === 'valid' ? true : false;
				},
			],
		];

		// Invalid.
		$result = $this->field_type->validate( 'invalid', $field );
		$this->assertInstanceOf( WP_Error::class, $result );

		// Valid.
		$result = $this->field_type->validate( 'valid', $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test getDefaultValue returns empty string.
	 */
	public function testGetDefaultValueReturnsEmptyString(): void {
		$this->assertSame( '', $this->field_type->getDefaultValue() );
	}

	/**
	 * Test supports method for searchable feature.
	 */
	public function testSupportsSearchable(): void {
		$this->assertTrue( $this->field_type->supports( 'searchable' ) );
	}

	/**
	 * Test supports method for unsupported feature.
	 */
	public function testSupportsUnsupportedFeature(): void {
		$this->assertFalse( $this->field_type->supports( 'nonexistent' ) );
	}

	/**
	 * Test formatValue with string.
	 *
	 * Note: The mock esc_html returns input unchanged.
	 * In real WordPress, HTML entities would be escaped.
	 */
	public function testFormatValueWithString(): void {
		$field = [ 'name' => 'test' ];

		$result = $this->field_type->formatValue( 'Hello World', $field );

		// Mock esc_html returns input unchanged.
		$this->assertSame( 'Hello World', $result );
		$this->assertIsString( $result );
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
	 * Test prepareValueForStorage returns unchanged value.
	 */
	public function testPrepareValueForStorageReturnsUnchanged(): void {
		$value = 'test value';

		$result = $this->field_type->prepareValueForStorage( $value );

		$this->assertSame( $value, $result );
	}

	/**
	 * Test prepareValueFromStorage returns unchanged value.
	 */
	public function testPrepareValueFromStorageReturnsUnchanged(): void {
		$value = 'test value';

		$result = $this->field_type->prepareValueFromStorage( $value );

		$this->assertSame( $value, $result );
	}

	/**
	 * Test isRequired returns true when required is set.
	 */
	public function testIsRequiredReturnsTrue(): void {
		$field = [ 'required' => true ];

		$this->assertTrue( $this->field_type->testIsRequired( $field ) );
	}

	/**
	 * Test isRequired returns false when not set.
	 */
	public function testIsRequiredReturnsFalse(): void {
		$field = [];

		$this->assertFalse( $this->field_type->testIsRequired( $field ) );
	}

	/**
	 * Test isEmpty with empty string.
	 */
	public function testIsEmptyWithEmptyString(): void {
		$this->assertTrue( $this->field_type->testIsEmpty( '' ) );
	}

	/**
	 * Test isEmpty with whitespace only string.
	 */
	public function testIsEmptyWithWhitespaceString(): void {
		$this->assertTrue( $this->field_type->testIsEmpty( '   ' ) );
	}

	/**
	 * Test isEmpty with null.
	 */
	public function testIsEmptyWithNull(): void {
		$this->assertTrue( $this->field_type->testIsEmpty( null ) );
	}

	/**
	 * Test isEmpty with empty array.
	 */
	public function testIsEmptyWithEmptyArray(): void {
		$this->assertTrue( $this->field_type->testIsEmpty( [] ) );
	}

	/**
	 * Test isEmpty with non-empty value.
	 */
	public function testIsEmptyWithNonEmptyValue(): void {
		$this->assertFalse( $this->field_type->testIsEmpty( 'value' ) );
	}

	/**
	 * Test getLabel returns label when set.
	 */
	public function testGetLabelReturnsLabel(): void {
		$field = [ 'label' => 'My Label' ];

		$this->assertSame( 'My Label', $this->field_type->testGetLabel( $field ) );
	}

	/**
	 * Test getLabel falls back to name.
	 */
	public function testGetLabelFallsBackToName(): void {
		$field = [ 'name' => 'field_name' ];

		$this->assertSame( 'field_name', $this->field_type->testGetLabel( $field ) );
	}

	/**
	 * Test getFieldName generates correct name.
	 */
	public function testGetFieldNameGeneratesCorrectName(): void {
		$field = [ 'name' => 'my_field' ];

		$this->assertSame( 'apd_field_my_field', $this->field_type->testGetFieldName( $field ) );
	}

	/**
	 * Test getFieldId generates correct ID.
	 */
	public function testGetFieldIdGeneratesCorrectId(): void {
		$field = [ 'name' => 'my_field' ];

		$this->assertSame( 'apd-field-my_field', $this->field_type->testGetFieldId( $field ) );
	}

	/**
	 * Test buildAttributes creates correct string.
	 */
	public function testBuildAttributesCreatesCorrectString(): void {
		$attributes = [
			'id'       => 'my-id',
			'class'    => 'my-class',
			'required' => true,
			'disabled' => false,
		];

		$result = $this->field_type->testBuildAttributes( $attributes );

		$this->assertStringContainsString( 'id="my-id"', $result );
		$this->assertStringContainsString( 'class="my-class"', $result );
		$this->assertStringContainsString( 'required', $result );
		$this->assertStringNotContainsString( 'disabled', $result );
	}

	/**
	 * Test render generates correct HTML.
	 */
	public function testRenderGeneratesCorrectHtml(): void {
		$field = [
			'name'        => 'test_field',
			'label'       => 'Test Field',
			'required'    => true,
			'placeholder' => 'Enter text',
			'description' => 'Help text here',
		];
		$value = 'test value';

		$result = $this->field_type->render( $field, $value );

		$this->assertStringContainsString( 'type="text"', $result );
		$this->assertStringContainsString( 'name="apd_field_test_field"', $result );
		$this->assertStringContainsString( 'id="apd-field-test_field"', $result );
		$this->assertStringContainsString( 'required', $result );
		$this->assertStringContainsString( 'aria-required="true"', $result );
		$this->assertStringContainsString( 'placeholder="Enter text"', $result );
		$this->assertStringContainsString( 'value="test value"', $result );
		$this->assertStringContainsString( 'apd-field-description', $result );
		$this->assertStringContainsString( 'Help text here', $result );
	}

	/**
	 * Test renderDescription returns empty when no description.
	 */
	public function testRenderDescriptionReturnsEmptyWhenNoDescription(): void {
		$field = [ 'name' => 'test' ];

		$result = $this->field_type->testRenderDescription( $field );

		$this->assertSame( '', $result );
	}

	/**
	 * Test renderDescription generates correct HTML.
	 */
	public function testRenderDescriptionGeneratesCorrectHtml(): void {
		$field = [
			'name'        => 'test_field',
			'description' => 'This is help text',
		];

		$result = $this->field_type->testRenderDescription( $field );

		$this->assertStringContainsString( 'class="apd-field-description"', $result );
		$this->assertStringContainsString( 'id="apd-field-test_field-description"', $result );
		$this->assertStringContainsString( 'This is help text', $result );
	}
}
