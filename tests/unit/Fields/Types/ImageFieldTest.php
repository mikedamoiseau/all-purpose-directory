<?php
/**
 * Unit tests for ImageField.
 *
 * Tests image field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\ImageField;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Test case for ImageField class.
 *
 * @covers \APD\Fields\Types\ImageField
 */
class ImageFieldTest extends UnitTestCase
{
    /**
     * The field instance being tested.
     *
     * @var ImageField
     */
    private ImageField $field;

    /**
     * Set up before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->field = new ImageField();
    }

    /**
     * Set up additional WordPress function stubs.
     */
    protected function setUpWordPressFunctions(): void
    {
        parent::setUpWordPressFunctions();

        // Add image-specific function stubs.
        Functions\stubs([
            'wp_get_attachment_url'   => static fn($id) => $id > 0 ? 'https://example.com/image.jpg' : false,
            'get_attached_file'       => static fn($id) => $id > 0 ? '/path/to/image.jpg' : false,
            'wp_get_attachment_image_src' => static fn($id, $size = 'thumbnail') => $id > 0 ? ['https://example.com/image-150x150.jpg', 150, 150, true] : false,
            'wp_get_attachment_image' => static fn($id, $size = 'thumbnail', $icon = false, $attr = []) => $id > 0 ? '<img src="https://example.com/image.jpg" class="apd-image-display">' : '',
            'wp_attachment_is_image'  => static fn($id) => $id > 0,
            'get_post_meta'           => static fn($id, $key, $single = false) => $key === '_wp_attachment_image_alt' ? 'Test image alt' : '',
        ]);
    }

    /**
     * Test field type returns 'image'.
     */
    public function testGetType(): void
    {
        $this->assertSame('image', $this->field->getType());
    }

    /**
     * Test field does not support searchable.
     */
    public function testSupportsSearchable(): void
    {
        $this->assertFalse($this->field->supports('searchable'));
    }

    /**
     * Test field does not support filterable.
     */
    public function testSupportsFilterable(): void
    {
        $this->assertFalse($this->field->supports('filterable'));
    }

    /**
     * Test field does not support sortable.
     */
    public function testSupportsSortable(): void
    {
        $this->assertFalse($this->field->supports('sortable'));
    }

    /**
     * Test field does not support repeater.
     */
    public function testSupportsRepeater(): void
    {
        $this->assertFalse($this->field->supports('repeater'));
    }

    /**
     * Test default value is 0.
     */
    public function testGetDefaultValue(): void
    {
        $this->assertSame(0, $this->field->getDefaultValue());
    }

    /**
     * Test rendering an image field without value.
     */
    public function testRenderWithoutValue(): void
    {
        $field_config = [
            'name'  => 'test_image',
            'label' => 'Test Image',
        ];

        $html = $this->field->render($field_config, 0);

        // Check wrapper class.
        $this->assertStringContainsString('class="apd-image-field"', $html);
        $this->assertStringContainsString('data-field-name="test_image"', $html);

        // Check hidden input.
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('id="apd-field-test_image"', $html);
        $this->assertStringContainsString('name="apd_field_test_image"', $html);
        $this->assertStringContainsString('data-field-type="image"', $html);
        $this->assertStringContainsString('data-preview-size="thumbnail"', $html);

        // Check upload button is visible.
        $this->assertStringContainsString('class="apd-image-upload button"', $html);
        $this->assertStringContainsString('Select Image', $html);

        // Check preview is hidden.
        $this->assertStringContainsString('class="apd-image-preview"', $html);
        $this->assertStringContainsString('style="display: none;"', $html);
    }

    /**
     * Test rendering an image field with value.
     */
    public function testRenderWithValue(): void
    {
        $field_config = [
            'name'  => 'test_image',
            'label' => 'Test Image',
        ];

        $html = $this->field->render($field_config, 123);

        // Check value is set.
        $this->assertStringContainsString('value="123"', $html);

        // Check preview contains image.
        $this->assertStringContainsString('class="apd-image-preview"', $html);
        $this->assertStringContainsString('<img src="', $html);
        $this->assertStringContainsString('class="apd-image-thumbnail"', $html);
        $this->assertStringContainsString('alt="Test image alt"', $html);

        // Check remove button.
        $this->assertStringContainsString('class="apd-image-remove button"', $html);
    }

    /**
     * Test rendering required field.
     */
    public function testRenderRequiredField(): void
    {
        $field_config = [
            'name'     => 'test_image',
            'label'    => 'Test Image',
            'required' => true,
        ];

        $html = $this->field->render($field_config, 0);

        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('aria-required="true"', $html);
    }

    /**
     * Test rendering with allowed types.
     */
    public function testRenderWithAllowedTypes(): void
    {
        $field_config = [
            'name'          => 'test_image',
            'label'         => 'Test Image',
            'allowed_types' => ['jpg', 'png'],
        ];

        $html = $this->field->render($field_config, 0);

        $this->assertStringContainsString('data-allowed-types="jpg,png"', $html);
    }

    /**
     * Test rendering with custom preview size.
     */
    public function testRenderWithPreviewSize(): void
    {
        $field_config = [
            'name'         => 'test_image',
            'label'        => 'Test Image',
            'preview_size' => 'medium',
        ];

        $html = $this->field->render($field_config, 0);

        $this->assertStringContainsString('data-preview-size="medium"', $html);
    }

    /**
     * Test rendering with description.
     */
    public function testRenderWithDescription(): void
    {
        $field_config = [
            'name'        => 'test_image',
            'label'       => 'Test Image',
            'description' => 'Upload a profile picture',
        ];

        $html = $this->field->render($field_config, 0);

        $this->assertStringContainsString('aria-describedby="apd-field-test_image-description"', $html);
        $this->assertStringContainsString('class="apd-field-description"', $html);
        $this->assertStringContainsString('Upload a profile picture', $html);
    }

    /**
     * Test rendering with custom CSS class.
     */
    public function testRenderWithCustomClass(): void
    {
        $field_config = [
            'name'  => 'test_image',
            'label' => 'Test Image',
            'class' => 'custom-image-class',
        ];

        $html = $this->field->render($field_config, 0);

        $this->assertStringContainsString('class="apd-image-field custom-image-class"', $html);
    }

    /**
     * Test sanitization converts to integer.
     */
    public function testSanitizeConvertsToInt(): void
    {
        $this->assertSame(123, $this->field->sanitize('123'));
        $this->assertSame(123, $this->field->sanitize(123));
        $this->assertSame(0, $this->field->sanitize('abc'));
        $this->assertSame(5, $this->field->sanitize(-5)); // absint returns absolute value.
        $this->assertSame(0, $this->field->sanitize(null));
        $this->assertSame(0, $this->field->sanitize(''));
    }

    /**
     * Test validation passes with valid image attachment.
     */
    public function testValidateWithValidImage(): void
    {
        $field_config = [
            'name'  => 'test_image',
            'label' => 'Test Image',
        ];

        $result = $this->field->validate(123, $field_config);

        $this->assertTrue($result);
    }

    /**
     * Test validation handles empty value for optional field.
     */
    public function testValidateEmptyOptionalField(): void
    {
        $field_config = [
            'name'     => 'test_image',
            'label'    => 'Test Image',
            'required' => false,
        ];

        $result = $this->field->validate(0, $field_config);

        $this->assertTrue($result);
    }

    /**
     * Test validation fails for empty required field.
     */
    public function testValidateEmptyRequiredField(): void
    {
        $field_config = [
            'name'     => 'test_image',
            'label'    => 'Test Image',
            'required' => true,
        ];

        $result = $this->field->validate(0, $field_config);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertTrue($result->has_errors());
        $this->assertContains('required', $result->get_error_codes());
    }

    /**
     * Test validation fails for invalid attachment.
     */
    public function testValidateInvalidAttachment(): void
    {
        // Override stub to return false for this specific test.
        Functions\when('wp_get_attachment_url')->justReturn(false);

        $field_config = [
            'name'  => 'test_image',
            'label' => 'Test Image',
        ];

        $result = $this->field->validate(999, $field_config);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertTrue($result->has_errors());
        $this->assertContains('invalid_attachment', $result->get_error_codes());
    }

    /**
     * Test validation fails for non-image attachment.
     */
    public function testValidateNonImageAttachment(): void
    {
        // Override stub to indicate not an image.
        Functions\when('wp_attachment_is_image')->justReturn(false);

        $field_config = [
            'name'  => 'test_image',
            'label' => 'Test Image',
        ];

        $result = $this->field->validate(123, $field_config);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertTrue($result->has_errors());
        $this->assertContains('not_an_image', $result->get_error_codes());
    }

    /**
     * Test validation fails for invalid image type.
     */
    public function testValidateInvalidImageType(): void
    {
        // Override stub to return a .bmp file (not in allowed types).
        Functions\when('get_attached_file')->justReturn('/path/to/image.bmp');

        $field_config = [
            'name'          => 'test_image',
            'label'         => 'Test Image',
            'allowed_types' => ['jpg', 'png'],
        ];

        $result = $this->field->validate(123, $field_config);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertTrue($result->has_errors());
        $this->assertContains('invalid_image_type', $result->get_error_codes());
    }

    /**
     * Test formatValue returns image HTML.
     */
    public function testFormatValueReturnsImageHtml(): void
    {
        $field_config = [
            'name'  => 'test_image',
            'label' => 'Test Image',
        ];

        $result = $this->field->formatValue(123, $field_config);

        $this->assertStringContainsString('<img', $result);
        $this->assertStringContainsString('apd-image-display', $result);
    }

    /**
     * Test formatValue returns empty string for invalid attachment.
     */
    public function testFormatValueReturnsEmptyForInvalid(): void
    {
        Functions\when('wp_get_attachment_image')->justReturn('');
        Functions\when('wp_get_attachment_url')->justReturn(false);

        $field_config = [
            'name'  => 'test_image',
            'label' => 'Test Image',
        ];

        $result = $this->field->formatValue(999, $field_config);

        $this->assertSame('', $result);
    }

    /**
     * Test formatValue returns empty string for zero value.
     */
    public function testFormatValueReturnsEmptyForZero(): void
    {
        $field_config = [
            'name'  => 'test_image',
            'label' => 'Test Image',
        ];

        $result = $this->field->formatValue(0, $field_config);

        $this->assertSame('', $result);
    }

    /**
     * Test formatValue falls back to URL when image HTML fails.
     */
    public function testFormatValueFallsBackToUrl(): void
    {
        Functions\when('wp_get_attachment_image')->justReturn('');
        Functions\when('wp_get_attachment_url')->justReturn('https://example.com/image.jpg');

        $field_config = [
            'name'  => 'test_image',
            'label' => 'Test Image',
        ];

        $result = $this->field->formatValue(123, $field_config);

        $this->assertSame('https://example.com/image.jpg', $result);
    }

    /**
     * Test prepareValueFromStorage returns integer.
     */
    public function testPrepareValueFromStorageReturnsInt(): void
    {
        $this->assertSame(123, $this->field->prepareValueFromStorage('123'));
        $this->assertSame(456, $this->field->prepareValueFromStorage(456));
        $this->assertSame(0, $this->field->prepareValueFromStorage(''));
        $this->assertSame(0, $this->field->prepareValueFromStorage(null));
    }
}
