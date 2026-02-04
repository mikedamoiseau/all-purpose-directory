<?php
/**
 * Unit tests for RichTextField.
 *
 * Tests rich text (WYSIWYG) field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\RichTextField;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Test case for RichTextField class.
 *
 * @covers \APD\Fields\Types\RichTextField
 */
class RichTextFieldTest extends UnitTestCase
{
    /**
     * The field type instance.
     *
     * @var RichTextField
     */
    private RichTextField $field;

    /**
     * Set up before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->field = new RichTextField();
    }

    /**
     * Test field type returns 'richtext'.
     */
    public function testGetType(): void
    {
        $this->assertSame('richtext', $this->field->getType());
    }

    /**
     * Test field supports searchable.
     */
    public function testSupportsSearchable(): void
    {
        $this->assertTrue($this->field->supports('searchable'));
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
     * Test rendering calls wp_editor with correct parameters.
     */
    public function testRenderCallsWpEditor(): void
    {
        $field_config = [
            'name' => 'test_richtext',
            'label' => 'Test Rich Text',
        ];
        $value = '<p>Test content</p>';

        // Mock wp_editor to capture the call.
        Functions\expect('wp_editor')
            ->once()
            ->with(
                $value,
                'apd-field-test_richtext',
                \Mockery::on(function ($settings) {
                    return $settings['textarea_name'] === 'apd_field_test_richtext'
                        && $settings['textarea_rows'] === 10
                        && $settings['media_buttons'] === true
                        && $settings['teeny'] === false
                        && $settings['quicktags'] === true;
                })
            );

        $this->field->render($field_config, $value);
    }

    /**
     * Test rendering with custom textarea_rows setting.
     */
    public function testRenderWithCustomRows(): void
    {
        $field_config = [
            'name' => 'custom_rows',
            'textarea_rows' => 15,
        ];

        Functions\expect('wp_editor')
            ->once()
            ->with(
                '',
                \Mockery::any(),
                \Mockery::on(function ($settings) {
                    return $settings['textarea_rows'] === 15;
                })
            );

        $this->field->render($field_config, '');
    }

    /**
     * Test rendering with media_buttons disabled.
     */
    public function testRenderWithoutMediaButtons(): void
    {
        $field_config = [
            'name' => 'no_media',
            'media_buttons' => false,
        ];

        Functions\expect('wp_editor')
            ->once()
            ->with(
                '',
                \Mockery::any(),
                \Mockery::on(function ($settings) {
                    return $settings['media_buttons'] === false;
                })
            );

        $this->field->render($field_config, '');
    }

    /**
     * Test rendering with teeny mode enabled.
     */
    public function testRenderWithTeenyMode(): void
    {
        $field_config = [
            'name' => 'teeny_editor',
            'teeny' => true,
        ];

        Functions\expect('wp_editor')
            ->once()
            ->with(
                '',
                \Mockery::any(),
                \Mockery::on(function ($settings) {
                    return $settings['teeny'] === true;
                })
            );

        $this->field->render($field_config, '');
    }

    /**
     * Test rendering with quicktags disabled.
     */
    public function testRenderWithoutQuicktags(): void
    {
        $field_config = [
            'name' => 'no_quicktags',
            'quicktags' => false,
        ];

        Functions\expect('wp_editor')
            ->once()
            ->with(
                '',
                \Mockery::any(),
                \Mockery::on(function ($settings) {
                    return $settings['quicktags'] === false;
                })
            );

        $this->field->render($field_config, '');
    }

    /**
     * Test rendering includes description.
     */
    public function testRenderIncludesDescription(): void
    {
        $field_config = [
            'name' => 'with_desc',
            'description' => 'Enter your content here.',
        ];

        Functions\expect('wp_editor')->once();

        $html = $this->field->render($field_config, '');

        $this->assertStringContainsString('apd-field-description', $html);
        $this->assertStringContainsString('Enter your content here.', $html);
    }

    /**
     * Test sanitize removes dangerous HTML.
     */
    public function testSanitizeRemovesDangerousHtml(): void
    {
        // Mock wp_kses_post to simulate stripping script tags.
        Functions\expect('wp_kses_post')
            ->once()
            ->with('<p>Hello</p><script>alert("xss")</script>')
            ->andReturn('<p>Hello</p>');

        $result = $this->field->sanitize('<p>Hello</p><script>alert("xss")</script>');

        $this->assertSame('<p>Hello</p>', $result);
    }

    /**
     * Test sanitize preserves allowed HTML.
     */
    public function testSanitizePreservesAllowedHtml(): void
    {
        $input = '<p><strong>Bold</strong> and <em>italic</em></p>';

        Functions\expect('wp_kses_post')
            ->once()
            ->with($input)
            ->andReturn($input);

        $result = $this->field->sanitize($input);

        $this->assertSame($input, $result);
    }

    /**
     * Test sanitize returns empty string for non-string input.
     */
    public function testSanitizeReturnsEmptyForNonString(): void
    {
        $this->assertSame('', $this->field->sanitize(['not', 'a', 'string']));
        $this->assertSame('', $this->field->sanitize(123));
        $this->assertSame('', $this->field->sanitize(null));
    }

    /**
     * Test validation passes with valid HTML content.
     */
    public function testValidateWithValidContent(): void
    {
        $field_config = [
            'name' => 'test',
            'required' => false,
        ];

        $result = $this->field->validate('<p>Valid content</p>', $field_config);

        $this->assertTrue($result);
    }

    /**
     * Test validation passes with empty value for optional field.
     */
    public function testValidateEmptyOptionalField(): void
    {
        $field_config = [
            'name' => 'optional',
            'required' => false,
        ];

        $result = $this->field->validate('', $field_config);

        $this->assertTrue($result);
    }

    /**
     * Test validation fails for empty required field.
     */
    public function testValidateEmptyRequiredField(): void
    {
        $field_config = [
            'name' => 'required_field',
            'label' => 'Content',
            'required' => true,
        ];

        $result = $this->field->validate('', $field_config);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('required', $result->get_error_code());
    }

    /**
     * Test getDefaultValue returns empty string.
     */
    public function testGetDefaultValue(): void
    {
        $this->assertSame('', $this->field->getDefaultValue());
    }

    /**
     * Test formatValue returns HTML content as-is.
     */
    public function testFormatValueReturnsHtmlContent(): void
    {
        $html = '<p><strong>Formatted</strong> content</p>';
        $field_config = ['name' => 'test'];

        $result = $this->field->formatValue($html, $field_config);

        $this->assertSame($html, $result);
    }

    /**
     * Test formatValue returns empty string for empty value.
     */
    public function testFormatValueReturnsEmptyForEmptyValue(): void
    {
        $field_config = ['name' => 'test'];

        $this->assertSame('', $this->field->formatValue('', $field_config));
        $this->assertSame('', $this->field->formatValue(null, $field_config));
    }

    /**
     * Test formatValue returns empty string for non-string value.
     */
    public function testFormatValueReturnsEmptyForNonString(): void
    {
        $field_config = ['name' => 'test'];

        $this->assertSame('', $this->field->formatValue(['array'], $field_config));
        $this->assertSame('', $this->field->formatValue(123, $field_config));
    }

    /**
     * Test rendering converts non-string value to empty string.
     */
    public function testRenderHandlesNonStringValue(): void
    {
        $field_config = ['name' => 'test'];

        Functions\expect('wp_editor')
            ->once()
            ->with('', \Mockery::any(), \Mockery::any());

        $this->field->render($field_config, ['not', 'a', 'string']);
    }
}
