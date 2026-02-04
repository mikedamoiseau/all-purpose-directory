<?php
/**
 * Unit tests for FileField.
 *
 * Tests file field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\FileField;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;

/**
 * Test case for FileField class.
 *
 * @covers \APD\Fields\Types\FileField
 */
class FileFieldTest extends UnitTestCase
{
    /**
     * The field instance being tested.
     *
     * @var FileField
     */
    private FileField $field;

    /**
     * Set up before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->field = new FileField();
    }

    /**
     * Set up additional WordPress function stubs.
     */
    protected function setUpWordPressFunctions(): void
    {
        parent::setUpWordPressFunctions();

        // Add file-specific function stubs.
        Functions\stubs([
            'wp_get_attachment_url'   => static fn($id) => $id > 0 ? 'https://example.com/file.pdf' : false,
            'get_attached_file'       => static fn($id) => $id > 0 ? '/path/to/file.pdf' : false,
            'wp_get_attachment_image_src' => static fn($id, $size = 'thumbnail') => $id > 0 ? ['https://example.com/image.jpg', 150, 150, true] : false,
            'wp_get_attachment_image' => static fn($id, $size = 'thumbnail', $icon = false, $attr = []) => $id > 0 ? '<img src="https://example.com/image.jpg" class="wp-image-' . $id . '">' : '',
            'wp_attachment_is_image'  => static fn($id) => false,
            'get_post_meta'           => static fn($id, $key, $single = false) => '',
        ]);
    }

    /**
     * Test field type returns 'file'.
     */
    public function testGetType(): void
    {
        $this->assertSame('file', $this->field->getType());
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
     * Test rendering a file field without value.
     */
    public function testRenderWithoutValue(): void
    {
        $field_config = [
            'name'  => 'test_file',
            'label' => 'Test File',
        ];

        $html = $this->field->render($field_config, 0);

        // Check wrapper class.
        $this->assertStringContainsString('class="apd-file-field"', $html);
        $this->assertStringContainsString('data-field-name="test_file"', $html);

        // Check hidden input.
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('id="apd-field-test_file"', $html);
        $this->assertStringContainsString('name="apd_field_test_file"', $html);
        $this->assertStringContainsString('data-field-type="file"', $html);

        // Check upload button is visible.
        $this->assertStringContainsString('class="apd-file-upload button"', $html);
        $this->assertStringContainsString('Select File', $html);

        // Check preview is hidden.
        $this->assertStringContainsString('class="apd-file-preview"', $html);
        $this->assertStringContainsString('style="display: none;"', $html);
    }

    /**
     * Test rendering a file field with value.
     */
    public function testRenderWithValue(): void
    {
        $field_config = [
            'name'  => 'test_file',
            'label' => 'Test File',
        ];

        $html = $this->field->render($field_config, 123);

        // Check value is set.
        $this->assertStringContainsString('value="123"', $html);

        // Check preview is visible (no display: none).
        $this->assertStringContainsString('class="apd-file-preview"', $html);
        $this->assertStringContainsString('class="apd-file-name"', $html);
        $this->assertStringContainsString('file.pdf', $html);

        // Check remove button.
        $this->assertStringContainsString('class="apd-file-remove button"', $html);
    }

    /**
     * Test rendering required field.
     */
    public function testRenderRequiredField(): void
    {
        $field_config = [
            'name'     => 'test_file',
            'label'    => 'Test File',
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
            'name'          => 'test_file',
            'label'         => 'Test File',
            'allowed_types' => ['pdf', 'doc', 'txt'],
        ];

        $html = $this->field->render($field_config, 0);

        $this->assertStringContainsString('data-allowed-types="pdf,doc,txt"', $html);
    }

    /**
     * Test rendering with max size.
     */
    public function testRenderWithMaxSize(): void
    {
        $field_config = [
            'name'     => 'test_file',
            'label'    => 'Test File',
            'max_size' => 5242880, // 5MB
        ];

        $html = $this->field->render($field_config, 0);

        $this->assertStringContainsString('data-max-size="5242880"', $html);
    }

    /**
     * Test rendering with description.
     */
    public function testRenderWithDescription(): void
    {
        $field_config = [
            'name'        => 'test_file',
            'label'       => 'Test File',
            'description' => 'Upload a PDF file',
        ];

        $html = $this->field->render($field_config, 0);

        $this->assertStringContainsString('aria-describedby="apd-field-test_file-description"', $html);
        $this->assertStringContainsString('class="apd-field-description"', $html);
        $this->assertStringContainsString('Upload a PDF file', $html);
    }

    /**
     * Test rendering with custom CSS class.
     */
    public function testRenderWithCustomClass(): void
    {
        $field_config = [
            'name'  => 'test_file',
            'label' => 'Test File',
            'class' => 'custom-class',
        ];

        $html = $this->field->render($field_config, 0);

        $this->assertStringContainsString('class="apd-file-field custom-class"', $html);
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
     * Test validation passes with valid attachment.
     */
    public function testValidateWithValidAttachment(): void
    {
        $field_config = [
            'name'  => 'test_file',
            'label' => 'Test File',
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
            'name'     => 'test_file',
            'label'    => 'Test File',
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
            'name'     => 'test_file',
            'label'    => 'Test File',
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
            'name'  => 'test_file',
            'label' => 'Test File',
        ];

        $result = $this->field->validate(999, $field_config);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertTrue($result->has_errors());
        $this->assertContains('invalid_attachment', $result->get_error_codes());
    }

    /**
     * Test validation fails for invalid file type.
     */
    public function testValidateInvalidFileType(): void
    {
        // Override stub to return a .exe file.
        Functions\when('get_attached_file')->justReturn('/path/to/file.exe');

        $field_config = [
            'name'          => 'test_file',
            'label'         => 'Test File',
            'allowed_types' => ['pdf', 'doc'],
        ];

        $result = $this->field->validate(123, $field_config);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertTrue($result->has_errors());
        $this->assertContains('invalid_file_type', $result->get_error_codes());
    }

    /**
     * Test formatValue returns download link.
     */
    public function testFormatValueReturnsDownloadLink(): void
    {
        $field_config = [
            'name'  => 'test_file',
            'label' => 'Test File',
        ];

        $result = $this->field->formatValue(123, $field_config);

        $this->assertStringContainsString('<a href="', $result);
        $this->assertStringContainsString('https://example.com/file.pdf', $result);
        $this->assertStringContainsString('file.pdf', $result);
        $this->assertStringContainsString('class="apd-file-link"', $result);
        $this->assertStringContainsString('target="_blank"', $result);
    }

    /**
     * Test formatValue returns empty string for invalid attachment.
     */
    public function testFormatValueReturnsEmptyForInvalid(): void
    {
        Functions\when('wp_get_attachment_url')->justReturn(false);

        $field_config = [
            'name'  => 'test_file',
            'label' => 'Test File',
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
            'name'  => 'test_file',
            'label' => 'Test File',
        ];

        $result = $this->field->formatValue(0, $field_config);

        $this->assertSame('', $result);
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
