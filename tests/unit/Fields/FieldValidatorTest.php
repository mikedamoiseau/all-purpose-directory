<?php
/**
 * Unit tests for FieldValidator.
 *
 * Tests field validation rules without WordPress.
 *
 * @package APD\Tests\Unit\Fields
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields;

use APD\Fields\FieldValidator;
use APD\Fields\FieldRegistry;
use APD\Fields\Types\TextField;
use APD\Fields\Types\EmailField;
use APD\Fields\Types\UrlField;
use APD\Fields\Types\PhoneField;
use APD\Fields\Types\NumberField;
use APD\Fields\Types\SelectField;
use APD\Fields\Types\CheckboxField;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use WP_Error;

/**
 * Test case for FieldValidator class.
 *
 * @covers \APD\Fields\FieldValidator
 */
class FieldValidatorTest extends UnitTestCase
{
    /**
     * Field validator instance.
     *
     * @var FieldValidator
     */
    private FieldValidator $validator;

    /**
     * Field registry instance.
     *
     * @var FieldRegistry
     */
    private FieldRegistry $registry;

    /**
     * Set up before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Get fresh registry instance.
        $this->registry = FieldRegistry::get_instance();
        $this->registry->reset();

        // Register field types.
        $this->registry->register_field_type(new TextField());
        $this->registry->register_field_type(new EmailField());
        $this->registry->register_field_type(new UrlField());
        $this->registry->register_field_type(new PhoneField());
        $this->registry->register_field_type(new NumberField());
        $this->registry->register_field_type(new SelectField());
        $this->registry->register_field_type(new CheckboxField());

        // Create validator with registry.
        $this->validator = new FieldValidator($this->registry);

        // Mock is_email for EmailField validation.
        Functions\when('is_email')->alias(function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        });

        // Mock esc_url_raw for UrlField sanitization.
        Functions\when('esc_url_raw')->alias(function ($url) {
            return filter_var($url, FILTER_SANITIZE_URL);
        });
    }

    /**
     * Tear down after each test.
     */
    protected function tearDown(): void
    {
        $this->registry->reset();
        parent::tearDown();
    }

    // =========================================================================
    // Context Tests
    // =========================================================================

    /**
     * Test setting and getting context.
     */
    public function testSetAndGetContext(): void
    {
        $this->assertEquals('form', $this->validator->get_context());

        $this->validator->set_context('admin');
        $this->assertEquals('admin', $this->validator->get_context());

        $result = $this->validator->set_context('api');
        $this->assertInstanceOf(FieldValidator::class, $result);
        $this->assertEquals('api', $this->validator->get_context());
    }

    // =========================================================================
    // Single Field Validation Tests
    // =========================================================================

    /**
     * Test validating unknown field returns error.
     */
    public function testValidateUnknownFieldReturnsError(): void
    {
        $result = $this->validator->validate_field('unknown_field', 'value');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertTrue($result->has_errors());
        $this->assertContains('unknown_field', $result->get_error_codes());
    }

    /**
     * Test validating field with unknown type returns error.
     */
    public function testValidateFieldWithUnknownTypeReturnsError(): void
    {
        $this->registry->register_field('test_field', [
            'type' => 'nonexistent_type',
        ]);

        $result = $this->validator->validate_field('test_field', 'value');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertContains('unknown_field_type', $result->get_error_codes());
    }

    /**
     * Test required field validation passes with value.
     */
    public function testRequiredFieldWithValuePasses(): void
    {
        $this->registry->register_field('name', [
            'type'     => 'text',
            'required' => true,
        ]);

        $result = $this->validator->validate_field('name', 'John Doe');

        $this->assertTrue($result);
    }

    /**
     * Test required field validation fails without value.
     */
    public function testRequiredFieldWithoutValueFails(): void
    {
        $this->registry->register_field('name', [
            'type'     => 'text',
            'label'    => 'Name',
            'required' => true,
        ]);

        $result = $this->validator->validate_field('name', '');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertTrue($result->has_errors());
        $this->assertStringContainsString('required', $result->get_error_message());
    }

    /**
     * Test required field fails with whitespace-only value.
     */
    public function testRequiredFieldWithWhitespaceOnlyFails(): void
    {
        $this->registry->register_field('name', [
            'type'     => 'text',
            'required' => true,
        ]);

        $result = $this->validator->validate_field('name', '   ');

        $this->assertInstanceOf(WP_Error::class, $result);
    }

    /**
     * Test optional field validation passes without value.
     */
    public function testOptionalFieldWithoutValuePasses(): void
    {
        $this->registry->register_field('nickname', [
            'type'     => 'text',
            'required' => false,
        ]);

        $result = $this->validator->validate_field('nickname', '');

        $this->assertTrue($result);
    }

    // =========================================================================
    // Length Validation Tests
    // =========================================================================

    /**
     * Test minimum length validation passes.
     */
    public function testMinLengthValidationPasses(): void
    {
        $this->registry->register_field('username', [
            'type'       => 'text',
            'validation' => ['min_length' => 3],
        ]);

        $result = $this->validator->validate_field('username', 'john');

        $this->assertTrue($result);
    }

    /**
     * Test minimum length validation fails.
     */
    public function testMinLengthValidationFails(): void
    {
        $this->registry->register_field('username', [
            'type'       => 'text',
            'label'      => 'Username',
            'validation' => ['min_length' => 5],
        ]);

        $result = $this->validator->validate_field('username', 'joe');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertStringContainsString('at least', $result->get_error_message());
    }

    /**
     * Test maximum length validation passes.
     */
    public function testMaxLengthValidationPasses(): void
    {
        $this->registry->register_field('title', [
            'type'       => 'text',
            'validation' => ['max_length' => 100],
        ]);

        $result = $this->validator->validate_field('title', 'Short title');

        $this->assertTrue($result);
    }

    /**
     * Test maximum length validation fails.
     */
    public function testMaxLengthValidationFails(): void
    {
        $this->registry->register_field('title', [
            'type'       => 'text',
            'label'      => 'Title',
            'validation' => ['max_length' => 10],
        ]);

        $result = $this->validator->validate_field('title', 'This title is way too long');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertStringContainsString('not exceed', $result->get_error_message());
    }

    /**
     * Test combined min and max length validation.
     */
    public function testMinMaxLengthValidation(): void
    {
        $this->registry->register_field('password', [
            'type'       => 'text',
            'validation' => [
                'min_length' => 8,
                'max_length' => 20,
            ],
        ]);

        // Too short.
        $result = $this->validator->validate_field('password', 'short');
        $this->assertInstanceOf(WP_Error::class, $result);

        // Too long.
        $result = $this->validator->validate_field('password', 'this_password_is_way_too_long_for_the_field');
        $this->assertInstanceOf(WP_Error::class, $result);

        // Just right.
        $result = $this->validator->validate_field('password', 'perfectpass123');
        $this->assertTrue($result);
    }

    // =========================================================================
    // Numeric Value Validation Tests
    // =========================================================================

    /**
     * Test minimum value validation passes.
     */
    public function testMinValueValidationPasses(): void
    {
        $this->registry->register_field('age', [
            'type' => 'number',
            'min'  => 18,
        ]);

        $result = $this->validator->validate_field('age', 25);

        $this->assertTrue($result);
    }

    /**
     * Test minimum value validation fails.
     */
    public function testMinValueValidationFails(): void
    {
        $this->registry->register_field('age', [
            'type'  => 'number',
            'label' => 'Age',
            'min'   => 18,
        ]);

        $result = $this->validator->validate_field('age', 16);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertStringContainsString('at least', $result->get_error_message());
    }

    /**
     * Test maximum value validation passes.
     */
    public function testMaxValueValidationPasses(): void
    {
        $this->registry->register_field('quantity', [
            'type' => 'number',
            'max'  => 100,
        ]);

        $result = $this->validator->validate_field('quantity', 50);

        $this->assertTrue($result);
    }

    /**
     * Test maximum value validation fails.
     */
    public function testMaxValueValidationFails(): void
    {
        $this->registry->register_field('quantity', [
            'type'  => 'number',
            'label' => 'Quantity',
            'max'   => 100,
        ]);

        $result = $this->validator->validate_field('quantity', 150);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertStringContainsString('no more than', $result->get_error_message());
    }

    /**
     * Test number field validates non-numeric values.
     */
    public function testNumberFieldRejectsNonNumeric(): void
    {
        $this->registry->register_field('count', [
            'type'     => 'number',
            'required' => true,
        ]);

        $result = $this->validator->validate_field('count', 'not a number', false);

        $this->assertInstanceOf(WP_Error::class, $result);
    }

    // =========================================================================
    // Email Format Validation Tests
    // =========================================================================

    /**
     * Test email format validation with valid email.
     */
    public function testValidEmailFormatPasses(): void
    {
        $this->registry->register_field('email', [
            'type' => 'email',
        ]);

        $result = $this->validator->validate_field('email', 'test@example.com');

        $this->assertTrue($result);
    }

    /**
     * Test email format validation with invalid email.
     */
    public function testInvalidEmailFormatFails(): void
    {
        $this->registry->register_field('email', [
            'type'  => 'email',
            'label' => 'Email Address',
        ]);

        $result = $this->validator->validate_field('email', 'not-an-email');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertStringContainsString('valid email', $result->get_error_message());
    }

    /**
     * Test various invalid email formats.
     *
     * Note: We test without sanitization because sanitize_email() strips
     * characters, which would change the test input.
     *
     * @dataProvider invalidEmailProvider
     */
    public function testVariousInvalidEmails(string $email): void
    {
        $this->registry->register_field('email', [
            'type' => 'email',
        ]);

        // Test without sanitization to check actual format validation.
        $result = $this->validator->validate_field('email', $email, false);

        $this->assertInstanceOf(WP_Error::class, $result);
    }

    /**
     * Provider for invalid email addresses.
     */
    public static function invalidEmailProvider(): array
    {
        return [
            'missing @' => ['testexample.com'],
            'missing domain' => ['test@'],
            'missing local' => ['@example.com'],
            'double @' => ['test@@example.com'],
        ];
    }

    // =========================================================================
    // URL Format Validation Tests
    // =========================================================================

    /**
     * Test URL format validation with valid URL.
     */
    public function testValidUrlFormatPasses(): void
    {
        $this->registry->register_field('website', [
            'type' => 'url',
        ]);

        $result = $this->validator->validate_field('website', 'https://example.com');

        $this->assertTrue($result);
    }

    /**
     * Test URL format validation with invalid URL.
     */
    public function testInvalidUrlFormatFails(): void
    {
        $this->registry->register_field('website', [
            'type'  => 'url',
            'label' => 'Website',
        ]);

        $result = $this->validator->validate_field('website', 'not-a-url', false);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertStringContainsString('valid URL', $result->get_error_message());
    }

    /**
     * Test various valid URL formats.
     *
     * @dataProvider validUrlProvider
     */
    public function testVariousValidUrls(string $url): void
    {
        $this->registry->register_field('website', [
            'type' => 'url',
        ]);

        $result = $this->validator->validate_field('website', $url, false);

        $this->assertTrue($result);
    }

    /**
     * Provider for valid URLs.
     */
    public static function validUrlProvider(): array
    {
        return [
            'http' => ['http://example.com'],
            'https' => ['https://example.com'],
            'with path' => ['https://example.com/path/to/page'],
            'with query' => ['https://example.com?foo=bar'],
            'with port' => ['https://example.com:8080'],
            'subdomain' => ['https://sub.example.com'],
        ];
    }

    // =========================================================================
    // Phone Format Validation Tests
    // =========================================================================

    /**
     * Test phone format validation with valid phone.
     */
    public function testValidPhoneFormatPasses(): void
    {
        $this->registry->register_field('phone', [
            'type' => 'phone',
        ]);

        $result = $this->validator->validate_field('phone', '+1 (555) 123-4567');

        $this->assertTrue($result);
    }

    /**
     * Test phone format validation with too few digits.
     */
    public function testPhoneWithTooFewDigitsFails(): void
    {
        $this->registry->register_field('phone', [
            'type'  => 'phone',
            'label' => 'Phone',
        ]);

        $result = $this->validator->validate_field('phone', '123');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertStringContainsString('valid phone', $result->get_error_message());
    }

    /**
     * Test phone format validation with too many digits.
     */
    public function testPhoneWithTooManyDigitsFails(): void
    {
        $this->registry->register_field('phone', [
            'type' => 'phone',
        ]);

        $result = $this->validator->validate_field('phone', '12345678901234567890');

        $this->assertInstanceOf(WP_Error::class, $result);
    }

    // =========================================================================
    // Pattern Validation Tests
    // =========================================================================

    /**
     * Test regex pattern validation passes.
     */
    public function testRegexPatternValidationPasses(): void
    {
        $this->registry->register_field('zip_code', [
            'type'       => 'text',
            'validation' => [
                'pattern' => '/^\d{5}(-\d{4})?$/',
            ],
        ]);

        $result = $this->validator->validate_field('zip_code', '12345');
        $this->assertTrue($result);

        $result = $this->validator->validate_field('zip_code', '12345-6789');
        $this->assertTrue($result);
    }

    /**
     * Test regex pattern validation fails.
     */
    public function testRegexPatternValidationFails(): void
    {
        $this->registry->register_field('zip_code', [
            'type'       => 'text',
            'label'      => 'ZIP Code',
            'validation' => [
                'pattern' => '/^\d{5}(-\d{4})?$/',
            ],
        ]);

        $result = $this->validator->validate_field('zip_code', 'ABCDE');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertStringContainsString('invalid', $result->get_error_message());
    }

    /**
     * Test custom pattern message.
     */
    public function testCustomPatternMessage(): void
    {
        $this->registry->register_field('zip_code', [
            'type'       => 'text',
            'validation' => [
                'pattern'         => '/^\d{5}$/',
                'pattern_message' => 'Please enter a 5-digit ZIP code.',
            ],
        ]);

        $result = $this->validator->validate_field('zip_code', 'invalid');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('Please enter a 5-digit ZIP code.', $result->get_error_message());
    }

    // =========================================================================
    // Custom Callback Validation Tests
    // =========================================================================

    /**
     * Test custom validation callback passes.
     */
    public function testCustomValidationCallbackPasses(): void
    {
        $this->registry->register_field('even_number', [
            'type'       => 'number',
            'validation' => [
                'callback' => function ($value) {
                    return (int) $value % 2 === 0;
                },
            ],
        ]);

        $result = $this->validator->validate_field('even_number', 4);

        $this->assertTrue($result);
    }

    /**
     * Test custom validation callback fails with false.
     */
    public function testCustomValidationCallbackFailsWithFalse(): void
    {
        $this->registry->register_field('even_number', [
            'type'       => 'number',
            'label'      => 'Even Number',
            'validation' => [
                'callback' => function ($value) {
                    return (int) $value % 2 === 0;
                },
            ],
        ]);

        $result = $this->validator->validate_field('even_number', 3);

        $this->assertInstanceOf(WP_Error::class, $result);
    }

    /**
     * Test custom validation callback fails with WP_Error.
     */
    public function testCustomValidationCallbackFailsWithWpError(): void
    {
        $this->registry->register_field('custom_field', [
            'type'       => 'text',
            'validation' => [
                'callback' => function ($value) {
                    if ($value === 'forbidden') {
                        return new WP_Error('forbidden_value', 'This value is forbidden.');
                    }
                    return true;
                },
            ],
        ]);

        $result = $this->validator->validate_field('custom_field', 'forbidden');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('This value is forbidden.', $result->get_error_message());
    }

    /**
     * Test callback receives field configuration.
     */
    public function testCallbackReceivesFieldConfig(): void
    {
        $receivedField = null;

        $this->registry->register_field('test_field', [
            'type'        => 'text',
            'custom_data' => 'test_value',
            'validation'  => [
                'callback' => function ($value, $field) use (&$receivedField) {
                    $receivedField = $field;
                    return true;
                },
            ],
        ]);

        $this->validator->validate_field('test_field', 'any value');

        $this->assertNotNull($receivedField);
        $this->assertEquals('test_value', $receivedField['custom_data']);
    }

    // =========================================================================
    // Multiple Fields Validation Tests
    // =========================================================================

    /**
     * Test validating multiple fields all pass.
     */
    public function testValidateMultipleFieldsAllPass(): void
    {
        $this->registry->register_field('name', ['type' => 'text', 'required' => true]);
        $this->registry->register_field('email', ['type' => 'email', 'required' => true]);

        $values = [
            'name'  => 'John Doe',
            'email' => 'john@example.com',
        ];

        $result = $this->validator->validate_fields($values);

        $this->assertTrue($result);
    }

    /**
     * Test validating multiple fields with some failing.
     */
    public function testValidateMultipleFieldsSomeFail(): void
    {
        $this->registry->register_field('name', ['type' => 'text', 'required' => true]);
        $this->registry->register_field('email', ['type' => 'email', 'required' => true]);

        $values = [
            'name'  => '',  // Fails required.
            'email' => 'invalid-email',  // Fails email format.
        ];

        $result = $this->validator->validate_fields($values);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertContains('name', $result->get_error_codes());
        $this->assertContains('email', $result->get_error_codes());
    }

    /**
     * Test validate specific fields only.
     */
    public function testValidateSpecificFieldsOnly(): void
    {
        $this->registry->register_field('name', ['type' => 'text', 'required' => true]);
        $this->registry->register_field('email', ['type' => 'email', 'required' => true]);
        $this->registry->register_field('phone', ['type' => 'phone', 'required' => true]);

        $values = [
            'name'  => 'John',
            'email' => '',  // Would fail if validated.
            'phone' => '',  // Would fail if validated.
        ];

        $result = $this->validator->validate_fields($values, [
            'fields' => ['name'],
        ]);

        $this->assertTrue($result);
    }

    /**
     * Test exclude fields from validation.
     */
    public function testExcludeFieldsFromValidation(): void
    {
        $this->registry->register_field('name', ['type' => 'text', 'required' => true]);
        $this->registry->register_field('email', ['type' => 'email', 'required' => true]);

        $values = [
            'name'  => 'John',
            'email' => '',  // Would fail if not excluded.
        ];

        $result = $this->validator->validate_fields($values, [
            'exclude' => ['email'],
        ]);

        $this->assertTrue($result);
    }

    /**
     * Test skip unregistered fields by default.
     */
    public function testSkipUnregisteredFieldsByDefault(): void
    {
        $this->registry->register_field('name', ['type' => 'text']);

        $values = [
            'name'          => 'John',
            'unknown_field' => 'some value',
        ];

        $result = $this->validator->validate_fields($values);

        $this->assertTrue($result);
    }

    /**
     * Test error on unregistered fields when configured.
     */
    public function testErrorOnUnregisteredFieldsWhenConfigured(): void
    {
        $this->registry->register_field('name', ['type' => 'text']);

        $values = [
            'name'          => 'John',
            'unknown_field' => 'some value',
        ];

        $result = $this->validator->validate_fields($values, [
            'skip_unregistered' => false,
        ]);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertContains('unknown_field', $result->get_error_codes());
    }

    // =========================================================================
    // Sanitization Tests
    // =========================================================================

    /**
     * Test sanitize single field.
     */
    public function testSanitizeSingleField(): void
    {
        $this->registry->register_field('name', ['type' => 'text']);

        $result = $this->validator->sanitize_field('name', '  <script>alert("xss")</script>John  ');

        $this->assertIsString($result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    /**
     * Test sanitize unknown field returns original value.
     */
    public function testSanitizeUnknownFieldReturnsOriginal(): void
    {
        $value = 'original value';
        $result = $this->validator->sanitize_field('unknown_field', $value);

        $this->assertEquals($value, $result);
    }

    /**
     * Test sanitize multiple fields.
     */
    public function testSanitizeMultipleFields(): void
    {
        $this->registry->register_field('name', ['type' => 'text']);
        $this->registry->register_field('email', ['type' => 'email']);

        $values = [
            'name'  => '  John Doe  ',
            'email' => 'JOHN@EXAMPLE.COM',
        ];

        $result = $this->validator->sanitize_fields($values);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertEquals('John Doe', $result['name']);
    }

    /**
     * Test sanitize with field filter.
     */
    public function testSanitizeWithFieldFilter(): void
    {
        $this->registry->register_field('name', ['type' => 'text']);
        $this->registry->register_field('email', ['type' => 'email']);

        $values = [
            'name'  => 'John',
            'email' => 'john@example.com',
        ];

        $result = $this->validator->sanitize_fields($values, [
            'fields' => ['name'],
        ]);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('email', $result);
    }

    // =========================================================================
    // Process Fields Tests
    // =========================================================================

    /**
     * Test process fields returns correct structure.
     */
    public function testProcessFieldsReturnsCorrectStructure(): void
    {
        $this->registry->register_field('name', ['type' => 'text', 'required' => true]);

        $values = ['name' => 'John'];

        $result = $this->validator->process_fields($values);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('values', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    /**
     * Test process fields with valid data.
     */
    public function testProcessFieldsWithValidData(): void
    {
        $this->registry->register_field('name', ['type' => 'text', 'required' => true]);

        $values = ['name' => '  John  '];

        $result = $this->validator->process_fields($values);

        $this->assertTrue($result['valid']);
        $this->assertEquals('John', $result['values']['name']);
        $this->assertNull($result['errors']);
    }

    /**
     * Test process fields with invalid data.
     */
    public function testProcessFieldsWithInvalidData(): void
    {
        $this->registry->register_field('name', ['type' => 'text', 'required' => true]);

        $values = ['name' => ''];

        $result = $this->validator->process_fields($values);

        $this->assertFalse($result['valid']);
        $this->assertInstanceOf(WP_Error::class, $result['errors']);
    }

    // =========================================================================
    // Required Fields Quick Check Tests
    // =========================================================================

    /**
     * Test validate required passes when all required present.
     */
    public function testValidateRequiredPassesWhenAllPresent(): void
    {
        $this->registry->register_field('name', ['type' => 'text', 'required' => true]);
        $this->registry->register_field('email', ['type' => 'email', 'required' => true]);
        $this->registry->register_field('phone', ['type' => 'phone', 'required' => false]);

        $values = [
            'name'  => 'John',
            'email' => 'john@example.com',
        ];

        $result = $this->validator->validate_required($values);

        $this->assertTrue($result);
    }

    /**
     * Test validate required fails when missing.
     */
    public function testValidateRequiredFailsWhenMissing(): void
    {
        $this->registry->register_field('name', ['type' => 'text', 'label' => 'Name', 'required' => true]);
        $this->registry->register_field('email', ['type' => 'email', 'label' => 'Email', 'required' => true]);

        $values = [
            'name' => 'John',
            // email is missing.
        ];

        $result = $this->validator->validate_required($values);

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertContains('email', $result->get_error_codes());
    }

    // =========================================================================
    // Error Helper Tests
    // =========================================================================

    /**
     * Test errors to array conversion.
     */
    public function testErrorsToArray(): void
    {
        $errors = new WP_Error();
        $errors->add('name', 'Name is required');
        $errors->add('name', 'Name is too short');
        $errors->add('email', 'Invalid email');

        $result = $this->validator->errors_to_array($errors);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertCount(2, $result['name']);
        $this->assertCount(1, $result['email']);
    }

    /**
     * Test field has error.
     */
    public function testFieldHasError(): void
    {
        $errors = new WP_Error();
        $errors->add('name', 'Name is required');

        $this->assertTrue($this->validator->field_has_error($errors, 'name'));
        $this->assertFalse($this->validator->field_has_error($errors, 'email'));
    }

    /**
     * Test get field errors.
     */
    public function testGetFieldErrors(): void
    {
        $errors = new WP_Error();
        $errors->add('name', 'Error 1');
        $errors->add('name', 'Error 2');

        $result = $this->validator->get_field_errors($errors, 'name');

        $this->assertCount(2, $result);
        $this->assertContains('Error 1', $result);
        $this->assertContains('Error 2', $result);
    }

    // =========================================================================
    // Select Field Validation Tests
    // =========================================================================

    /**
     * Test select field validates option exists.
     */
    public function testSelectFieldValidatesOptionExists(): void
    {
        $this->registry->register_field('status', [
            'type'    => 'select',
            'options' => [
                'draft'     => 'Draft',
                'published' => 'Published',
                'archived'  => 'Archived',
            ],
        ]);

        $result = $this->validator->validate_field('status', 'published');
        $this->assertTrue($result);

        $result = $this->validator->validate_field('status', 'invalid_option', false);
        $this->assertInstanceOf(WP_Error::class, $result);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    /**
     * Test validation with null value.
     */
    public function testValidationWithNullValue(): void
    {
        $this->registry->register_field('optional', [
            'type'     => 'text',
            'required' => false,
        ]);

        $result = $this->validator->validate_field('optional', null);

        $this->assertTrue($result);
    }

    /**
     * Test validation with array value.
     */
    public function testValidationWithArrayValue(): void
    {
        $this->registry->register_field('tags', [
            'type'     => 'text',  // Using text as a simple test.
            'required' => true,
        ]);

        $result = $this->validator->validate_field('tags', ['tag1', 'tag2']);

        // Text field should handle this gracefully.
        $this->assertTrue($result === true || is_wp_error($result));
    }

    /**
     * Test validation without sanitization.
     */
    public function testValidationWithoutSanitization(): void
    {
        $this->registry->register_field('name', ['type' => 'text', 'required' => true]);

        $result = $this->validator->validate_field('name', '  John  ', false);

        $this->assertTrue($result);
    }

    /**
     * Test empty field array validation.
     */
    public function testEmptyFieldArrayValidation(): void
    {
        $result = $this->validator->validate_fields([]);

        $this->assertTrue($result);
    }
}
