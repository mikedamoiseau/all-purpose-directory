<?php
/**
 * Unit tests for GalleryField.
 *
 * Tests gallery field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\GalleryField;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Test case for GalleryField class.
 *
 * @covers \APD\Fields\Types\GalleryField
 */
class GalleryFieldTest extends UnitTestCase
{
    /**
     * The field instance being tested.
     *
     * @var GalleryField
     */
    private GalleryField $field;

    /**
     * Set up before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->field = new GalleryField();
    }

    /**
     * Set up additional WordPress function stubs.
     */
    protected function setUpWordPressFunctions(): void
    {
        parent::setUpWordPressFunctions();

        // Add gallery-specific function stubs.
        Functions\stubs([
            'wp_get_attachment_url' => static fn($id) => $id > 0 ? "https://example.com/image-{$id}.jpg" : false,
            'get_attached_file' => static fn($id) => $id > 0 ? "/path/to/image-{$id}.jpg" : false,
            'wp_get_attachment_image_src' => static fn($id, $size = 'thumbnail') => $id > 0 ? ["https://example.com/image-{$id}-150x150.jpg", 150, 150, true] : false,
            'wp_get_attachment_image' => static fn($id, $size = 'thumbnail', $icon = false, $attr = []) => $id > 0 ? "<img src=\"https://example.com/image-{$id}.jpg\" class=\"apd-gallery-image\">" : '',
            'wp_attachment_is_image' => static fn($id) => $id > 0,
            'get_post_meta' => static fn($id, $key, $single = false) => $key === '_wp_attachment_image_alt' ? "Alt for image {$id}" : '',
            'wp_json_encode' => static fn($data) => json_encode($data),
        ]);
    }

    /**
     * Test field type returns 'gallery'.
     */
    public function testGetType(): void
    {
        $this->assertSame('gallery', $this->field->getType());
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
     * Test field supports repeater.
     */
    public function testSupportsRepeater(): void
    {
        $this->assertTrue($this->field->supports('repeater'));
    }

    /**
     * Test default value is empty array.
     */
    public function testGetDefaultValue(): void
    {
        $this->assertSame([], $this->field->getDefaultValue());
    }

    /**
     * Test rendering a gallery field without value.
     */
    public function testRenderWithoutValue(): void
    {
        $field_config = [
            'name'  => 'test_gallery',
            'label' => 'Test Gallery',
        ];

        $html = $this->field->render($field_config, []);

        // Check wrapper class and data attributes.
        $this->assertStringContainsString('class="apd-gallery-field"', $html);
        $this->assertStringContainsString('data-field-type="gallery"', $html);
        $this->assertStringContainsString('data-field-name="test_gallery"', $html);

        // Check hidden input.
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('id="apd-field-test_gallery"', $html);
        $this->assertStringContainsString('name="apd_field_test_gallery"', $html);
        $this->assertStringContainsString('value=""', $html);

        // Check gallery preview container.
        $this->assertStringContainsString('class="apd-gallery-preview"', $html);
        $this->assertStringContainsString('data-sortable="true"', $html);

        // Check add button.
        $this->assertStringContainsString('class="apd-gallery-add button"', $html);
        $this->assertStringContainsString('Add Images', $html);
    }

    /**
     * Test rendering a gallery field with values.
     */
    public function testRenderWithValues(): void
    {
        $field_config = [
            'name'  => 'test_gallery',
            'label' => 'Test Gallery',
        ];

        $html = $this->field->render($field_config, [123, 456, 789]);

        // Check value is comma-separated.
        $this->assertStringContainsString('value="123,456,789"', $html);

        // Check gallery items.
        $this->assertStringContainsString('class="apd-gallery-item"', $html);
        $this->assertStringContainsString('data-id="123"', $html);
        $this->assertStringContainsString('data-id="456"', $html);
        $this->assertStringContainsString('data-id="789"', $html);

        // Check thumbnails.
        $this->assertStringContainsString('class="apd-gallery-thumbnail"', $html);

        // Check remove buttons.
        $this->assertStringContainsString('class="apd-gallery-remove"', $html);
    }

    /**
     * Test rendering required field.
     */
    public function testRenderRequiredField(): void
    {
        $field_config = [
            'name'     => 'test_gallery',
            'label'    => 'Test Gallery',
            'required' => true,
        ];

        $html = $this->field->render($field_config, []);

        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('aria-required="true"', $html);
    }

    /**
     * Test rendering with max images limit.
     */
    public function testRenderWithMaxImages(): void
    {
        $field_config = [
            'name'       => 'test_gallery',
            'label'      => 'Test Gallery',
            'max_images' => 5,
        ];

        $html = $this->field->render($field_config, []);

        $this->assertStringContainsString('data-max-images="5"', $html);
        $this->assertStringContainsString('of 5 images', $html);
    }

    /**
     * Test rendering with max images reached disables button.
     */
    public function testRenderWithMaxImagesReachedDisablesButton(): void
    {
        $field_config = [
            'name'       => 'test_gallery',
            'label'      => 'Test Gallery',
            'max_images' => 3,
        ];

        $html = $this->field->render($field_config, [1, 2, 3]);

        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('3 of 3 images', $html);
    }

    /**
     * Test rendering with custom preview size.
     */
    public function testRenderWithPreviewSize(): void
    {
        $field_config = [
            'name'         => 'test_gallery',
            'label'        => 'Test Gallery',
            'preview_size' => 'medium',
        ];

        $html = $this->field->render($field_config, []);

        $this->assertStringContainsString('data-preview-size="medium"', $html);
    }

    /**
     * Test rendering with allowed types.
     */
    public function testRenderWithAllowedTypes(): void
    {
        $field_config = [
            'name'          => 'test_gallery',
            'label'         => 'Test Gallery',
            'allowed_types' => ['jpg', 'png'],
        ];

        $html = $this->field->render($field_config, []);

        $this->assertStringContainsString('data-allowed-types="jpg,png"', $html);
    }

    /**
     * Test rendering with description.
     */
    public function testRenderWithDescription(): void
    {
        $field_config = [
            'name'        => 'test_gallery',
            'label'       => 'Test Gallery',
            'description' => 'Upload multiple images',
        ];

        $html = $this->field->render($field_config, []);

        $this->assertStringContainsString('aria-describedby="apd-field-test_gallery-description"', $html);
        $this->assertStringContainsString('class="apd-field-description"', $html);
        $this->assertStringContainsString('Upload multiple images', $html);
    }

    /**
     * Test rendering with custom CSS class.
     */
    public function testRenderWithCustomClass(): void
    {
        $field_config = [
            'name'  => 'test_gallery',
            'label' => 'Test Gallery',
            'class' => 'custom-gallery-class',
        ];

        $html = $this->field->render($field_config, []);

        $this->assertStringContainsString('class="apd-gallery-field custom-gallery-class"', $html);
    }

    /**
     * Test sanitization converts string to array of integers.
     */
    public function testSanitizeStringToArray(): void
    {
        $this->assertSame([123, 456, 789], $this->field->sanitize('123,456,789'));
        $this->assertSame([123, 456], $this->field->sanitize('123, 456'));
        $this->assertSame([123], $this->field->sanitize('123'));
    }

    /**
     * Test sanitization handles array input.
     */
    public function testSanitizeArrayInput(): void
    {
        $this->assertSame([123, 456], $this->field->sanitize([123, 456]));
        $this->assertSame([123, 456], $this->field->sanitize(['123', '456']));
        $this->assertSame([123], $this->field->sanitize([123, 0, '']));
    }

    /**
     * Test sanitization handles JSON string.
     */
    public function testSanitizeJsonString(): void
    {
        $this->assertSame([123, 456], $this->field->sanitize('[123,456]'));
        $this->assertSame([1, 2, 3], $this->field->sanitize('[1, 2, 3]'));
    }

    /**
     * Test sanitization handles empty values.
     */
    public function testSanitizeEmptyValues(): void
    {
        $this->assertSame([], $this->field->sanitize(''));
        $this->assertSame([], $this->field->sanitize(null));
        $this->assertSame([], $this->field->sanitize([]));
    }

    /**
     * Test sanitization filters invalid values.
     */
    public function testSanitizeFiltersInvalidValues(): void
    {
        // absint converts -5 to 5, so it's not filtered out.
        $this->assertSame([123, 5], $this->field->sanitize(['abc', 123, -5]));
        $this->assertSame([], $this->field->sanitize('abc,def'));
    }

    /**
     * Test validation passes with valid images.
     */
    public function testValidateWithValidImages(): void
    {
        $field_config = [
            'name'  => 'test_gallery',
            'label' => 'Test Gallery',
        ];

        $result = $this->field->validate([123, 456], $field_config);

        $this->assertTrue($result);
    }

    /**
     * Test validation handles empty value for optional field.
     */
    public function testValidateEmptyOptionalField(): void
    {
        $field_config = [
            'name'     => 'test_gallery',
            'label'    => 'Test Gallery',
            'required' => false,
        ];

        $result = $this->field->validate([], $field_config);

        $this->assertTrue($result);
    }

    /**
     * Test validation fails for empty required field.
     */
    public function testValidateEmptyRequiredField(): void
    {
        $field_config = [
            'name'     => 'test_gallery',
            'label'    => 'Test Gallery',
            'required' => true,
        ];

        $result = $this->field->validate([], $field_config);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertTrue($result->has_errors());
        $this->assertContains('required', $result->get_error_codes());
    }

    /**
     * Test validation fails when max images exceeded.
     */
    public function testValidateMaxImagesExceeded(): void
    {
        $field_config = [
            'name'       => 'test_gallery',
            'label'      => 'Test Gallery',
            'max_images' => 2,
        ];

        $result = $this->field->validate([1, 2, 3, 4], $field_config);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertTrue($result->has_errors());
        $this->assertContains('max_images_exceeded', $result->get_error_codes());
    }

    /**
     * Test validation fails for invalid attachment.
     */
    public function testValidateInvalidAttachment(): void
    {
        Functions\when('wp_get_attachment_url')->alias(static function ($id) {
            return $id === 999 ? false : "https://example.com/image-{$id}.jpg";
        });

        $field_config = [
            'name'  => 'test_gallery',
            'label' => 'Test Gallery',
        ];

        $result = $this->field->validate([123, 999], $field_config);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertTrue($result->has_errors());
        $this->assertContains('invalid_attachment', $result->get_error_codes());
    }

    /**
     * Test validation fails for non-image attachment.
     */
    public function testValidateNonImageAttachment(): void
    {
        Functions\when('wp_attachment_is_image')->alias(static function ($id) {
            return $id !== 456; // 456 is not an image.
        });

        $field_config = [
            'name'  => 'test_gallery',
            'label' => 'Test Gallery',
        ];

        $result = $this->field->validate([123, 456], $field_config);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertTrue($result->has_errors());
        $this->assertContains('not_an_image', $result->get_error_codes());
    }

    /**
     * Test validation fails for invalid image type.
     */
    public function testValidateInvalidImageType(): void
    {
        Functions\when('get_attached_file')->alias(static function ($id) {
            return $id === 456 ? '/path/to/image.bmp' : "/path/to/image-{$id}.jpg";
        });

        $field_config = [
            'name'          => 'test_gallery',
            'label'         => 'Test Gallery',
            'allowed_types' => ['jpg', 'png'],
        ];

        $result = $this->field->validate([123, 456], $field_config);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertTrue($result->has_errors());
        $this->assertContains('invalid_image_type', $result->get_error_codes());
    }

    /**
     * Test formatValue returns image grid HTML.
     */
    public function testFormatValueReturnsImageGrid(): void
    {
        $field_config = [
            'name'  => 'test_gallery',
            'label' => 'Test Gallery',
        ];

        $result = $this->field->formatValue([123, 456], $field_config);

        $this->assertStringContainsString('class="apd-gallery-display"', $result);
        $this->assertStringContainsString('<a href="', $result);
        $this->assertStringContainsString('class="apd-gallery-link"', $result);
        $this->assertStringContainsString('<img', $result);
    }

    /**
     * Test formatValue returns gallery shortcode when configured.
     */
    public function testFormatValueReturnsShortcode(): void
    {
        $field_config = [
            'name'          => 'test_gallery',
            'label'         => 'Test Gallery',
            'use_shortcode' => true,
        ];

        $result = $this->field->formatValue([123, 456], $field_config);

        $this->assertStringContainsString('[gallery ids="123,456"]', $result);
    }

    /**
     * Test formatValue returns empty string for empty array.
     */
    public function testFormatValueReturnsEmptyForEmptyArray(): void
    {
        $field_config = [
            'name'  => 'test_gallery',
            'label' => 'Test Gallery',
        ];

        $result = $this->field->formatValue([], $field_config);

        $this->assertSame('', $result);
    }

    /**
     * Test prepareValueForStorage returns JSON string.
     */
    public function testPrepareValueForStorageReturnsJson(): void
    {
        $result = $this->field->prepareValueForStorage([123, 456, 789]);

        $this->assertSame('[123,456,789]', $result);
    }

    /**
     * Test prepareValueForStorage handles string input.
     */
    public function testPrepareValueForStorageHandlesStringInput(): void
    {
        $result = $this->field->prepareValueForStorage('123,456');

        $this->assertSame('[123,456]', $result);
    }

    /**
     * Test prepareValueForStorage handles empty value.
     */
    public function testPrepareValueForStorageHandlesEmpty(): void
    {
        $result = $this->field->prepareValueForStorage([]);

        $this->assertSame('[]', $result);
    }

    /**
     * Test prepareValueFromStorage returns array from JSON.
     */
    public function testPrepareValueFromStorageReturnsArrayFromJson(): void
    {
        $result = $this->field->prepareValueFromStorage('[123,456,789]');

        $this->assertSame([123, 456, 789], $result);
    }

    /**
     * Test prepareValueFromStorage handles comma-separated string.
     */
    public function testPrepareValueFromStorageHandlesCommaSeparated(): void
    {
        $result = $this->field->prepareValueFromStorage('123,456,789');

        $this->assertSame([123, 456, 789], $result);
    }

    /**
     * Test prepareValueFromStorage handles array input.
     */
    public function testPrepareValueFromStorageHandlesArray(): void
    {
        $result = $this->field->prepareValueFromStorage([123, 456]);

        $this->assertSame([123, 456], $result);
    }

    /**
     * Test prepareValueFromStorage handles empty values.
     */
    public function testPrepareValueFromStorageHandlesEmpty(): void
    {
        $this->assertSame([], $this->field->prepareValueFromStorage(''));
        $this->assertSame([], $this->field->prepareValueFromStorage(null));
        $this->assertSame([], $this->field->prepareValueFromStorage('[]'));
    }
}
