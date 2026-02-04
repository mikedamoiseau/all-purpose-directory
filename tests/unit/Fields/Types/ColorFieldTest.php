<?php
/**
 * Unit tests for ColorField.
 *
 * Tests color field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\ColorField;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Test case for ColorField class.
 *
 * @covers \APD\Fields\Types\ColorField
 */
class ColorFieldTest extends UnitTestCase
{
    /**
     * The field type instance.
     *
     * @var ColorField
     */
    private ColorField $field;

    /**
     * Set up before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->field = new ColorField();

        // Stub sanitize_hex_color.
        Functions\when('sanitize_hex_color')->alias(function ($color) {
            if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $color)) {
                return $color;
            }
            return null;
        });
    }

    /**
     * Test field type returns 'color'.
     */
    public function testGetType(): void
    {
        $this->assertSame('color', $this->field->getType());
    }

    /**
     * Test field does not support searchable.
     */
    public function testDoesNotSupportSearchable(): void
    {
        $this->assertFalse($this->field->supports('searchable'));
    }

    /**
     * Test field does not support filterable.
     */
    public function testDoesNotSupportFilterable(): void
    {
        $this->assertFalse($this->field->supports('filterable'));
    }

    /**
     * Test field does not support sortable.
     */
    public function testDoesNotSupportSortable(): void
    {
        $this->assertFalse($this->field->supports('sortable'));
    }

    /**
     * Test field does not support repeater.
     */
    public function testDoesNotSupportRepeater(): void
    {
        $this->assertFalse($this->field->supports('repeater'));
    }

    /**
     * Test rendering a color input field.
     */
    public function testRender(): void
    {
        $field_config = [
            'name' => 'test_color',
            'label' => 'Test Color',
        ];

        $html = $this->field->render($field_config, '#FF5733');

        $this->assertStringContainsString('type="color"', $html);
        $this->assertStringContainsString('id="apd-field-test_color"', $html);
        $this->assertStringContainsString('name="apd_field_test_color"', $html);
        $this->assertStringContainsString('value="#FF5733"', $html);
    }

    /**
     * Test rendering uses default value when empty.
     */
    public function testRenderUsesDefaultValue(): void
    {
        $field_config = [
            'name' => 'empty_color',
        ];

        $html = $this->field->render($field_config, '');

        $this->assertStringContainsString('value="#000000"', $html);
    }

    /**
     * Test rendering uses field default when specified.
     */
    public function testRenderUsesFieldDefault(): void
    {
        $field_config = [
            'name' => 'custom_default',
            'default' => '#FFFFFF',
        ];

        $html = $this->field->render($field_config, '');

        $this->assertStringContainsString('value="#FFFFFF"', $html);
    }

    /**
     * Test rendering normalizes 3-character hex to 6-character.
     */
    public function testRenderNormalizesShortHex(): void
    {
        $field_config = [
            'name' => 'short_hex',
        ];

        $html = $this->field->render($field_config, '#F53');

        $this->assertStringContainsString('value="#FF5533"', $html);
    }

    /**
     * Test rendering with required field.
     */
    public function testRenderRequiredField(): void
    {
        $field_config = [
            'name' => 'required_color',
            'required' => true,
        ];

        $html = $this->field->render($field_config, '#000000');

        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('aria-required="true"', $html);
    }

    /**
     * Test rendering with description.
     */
    public function testRenderWithDescription(): void
    {
        $field_config = [
            'name' => 'color_with_desc',
            'description' => 'Choose a brand color.',
        ];

        $html = $this->field->render($field_config, '#000000');

        $this->assertStringContainsString('apd-field-description', $html);
        $this->assertStringContainsString('Choose a brand color.', $html);
    }

    /**
     * Test rendering with custom CSS class.
     */
    public function testRenderWithCustomClass(): void
    {
        $field_config = [
            'name' => 'styled_color',
            'class' => 'my-custom-color-class',
        ];

        $html = $this->field->render($field_config, '#000000');

        $this->assertStringContainsString('class="my-custom-color-class"', $html);
    }

    /**
     * Test sanitize with valid 6-character hex.
     */
    public function testSanitizeValid6CharHex(): void
    {
        $result = $this->field->sanitize('#FF5733');

        $this->assertSame('#FF5733', $result);
    }

    /**
     * Test sanitize with valid 3-character hex.
     */
    public function testSanitizeValid3CharHex(): void
    {
        $result = $this->field->sanitize('#F53');

        $this->assertSame('#F53', $result);
    }

    /**
     * Test sanitize with lowercase hex.
     */
    public function testSanitizeLowercaseHex(): void
    {
        $result = $this->field->sanitize('#ff5733');

        $this->assertSame('#ff5733', $result);
    }

    /**
     * Test sanitize returns empty for invalid color.
     */
    public function testSanitizeInvalidColor(): void
    {
        $this->assertSame('', $this->field->sanitize('not-a-color'));
        $this->assertSame('', $this->field->sanitize('#GGG'));
        $this->assertSame('', $this->field->sanitize('#GGGGGG'));
        $this->assertSame('', $this->field->sanitize('FF5733')); // Missing #
    }

    /**
     * Test sanitize returns empty for non-string input.
     */
    public function testSanitizeNonString(): void
    {
        $this->assertSame('', $this->field->sanitize(['#FF5733']));
        $this->assertSame('', $this->field->sanitize(123));
        $this->assertSame('', $this->field->sanitize(null));
    }

    /**
     * Test validation passes with valid 6-character hex.
     */
    public function testValidateValid6CharHex(): void
    {
        $field_config = ['name' => 'test', 'required' => false];

        $result = $this->field->validate('#FF5733', $field_config);

        $this->assertTrue($result);
    }

    /**
     * Test validation passes with valid 3-character hex.
     */
    public function testValidateValid3CharHex(): void
    {
        $field_config = ['name' => 'test', 'required' => false];

        $result = $this->field->validate('#F53', $field_config);

        $this->assertTrue($result);
    }

    /**
     * Test validation passes with lowercase hex.
     */
    public function testValidateLowercaseHex(): void
    {
        $field_config = ['name' => 'test', 'required' => false];

        $result = $this->field->validate('#ff5733', $field_config);

        $this->assertTrue($result);
    }

    /**
     * Test validation fails for invalid hex format.
     */
    public function testValidateInvalidHexFormat(): void
    {
        $field_config = ['name' => 'test', 'label' => 'Color'];

        // Missing #
        $result = $this->field->validate('FF5733', $field_config);
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('invalid_color', $result->get_error_code());

        // Invalid characters
        $result = $this->field->validate('#GGGGGG', $field_config);
        $this->assertInstanceOf(\WP_Error::class, $result);

        // Wrong length
        $result = $this->field->validate('#FF57', $field_config);
        $this->assertInstanceOf(\WP_Error::class, $result);
    }

    /**
     * Test validation passes with empty value for optional field.
     */
    public function testValidateEmptyOptionalField(): void
    {
        $field_config = ['name' => 'optional', 'required' => false];

        $result = $this->field->validate('', $field_config);

        $this->assertTrue($result);
    }

    /**
     * Test validation fails for empty required field.
     */
    public function testValidateEmptyRequiredField(): void
    {
        $field_config = [
            'name' => 'required_color',
            'label' => 'Brand Color',
            'required' => true,
        ];

        $result = $this->field->validate('', $field_config);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('required', $result->get_error_code());
    }

    /**
     * Test getDefaultValue returns black.
     */
    public function testGetDefaultValue(): void
    {
        $this->assertSame('#000000', $this->field->getDefaultValue());
    }

    /**
     * Test formatValue returns escaped hex color.
     */
    public function testFormatValue(): void
    {
        $field_config = ['name' => 'test'];

        $result = $this->field->formatValue('#FF5733', $field_config);

        $this->assertSame('#FF5733', $result);
    }

    /**
     * Test formatValue returns empty for empty value.
     */
    public function testFormatValueReturnsEmptyForEmptyValue(): void
    {
        $field_config = ['name' => 'test'];

        $this->assertSame('', $this->field->formatValue('', $field_config));
        $this->assertSame('', $this->field->formatValue(null, $field_config));
    }

    /**
     * Test formatValue returns empty for non-string value.
     */
    public function testFormatValueReturnsEmptyForNonString(): void
    {
        $field_config = ['name' => 'test'];

        $this->assertSame('', $this->field->formatValue(['#FF5733'], $field_config));
        $this->assertSame('', $this->field->formatValue(123, $field_config));
    }

    /**
     * Test error message mentions valid format.
     */
    public function testValidationErrorMessageMentionsFormat(): void
    {
        $field_config = ['name' => 'test', 'label' => 'Color'];

        $result = $this->field->validate('invalid', $field_config);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $message = $result->get_error_message('invalid_color');
        $this->assertStringContainsString('#FF5733', $message);
        $this->assertStringContainsString('#F53', $message);
    }
}
