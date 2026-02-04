<?php
/**
 * Unit tests for RadioField.
 *
 * Tests radio field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\RadioField;
use APD\Tests\Unit\UnitTestCase;
use WP_Error;

/**
 * Test case for RadioField class.
 *
 * @covers \APD\Fields\Types\RadioField
 */
class RadioFieldTest extends UnitTestCase {

	/**
	 * The radio field instance.
	 *
	 * @var RadioField
	 */
	private RadioField $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new RadioField();
	}

	/**
	 * Test getType returns 'radio'.
	 */
	public function testGetTypeReturnsRadio(): void {
		$this->assertSame( 'radio', $this->field->getType() );
	}

	/**
	 * Test supports returns correct values for features.
	 */
	public function testSupportsReturnsCorrectValues(): void {
		$this->assertFalse( $this->field->supports( 'searchable' ) );
		$this->assertTrue( $this->field->supports( 'filterable' ) );
		$this->assertFalse( $this->field->supports( 'sortable' ) );
		$this->assertFalse( $this->field->supports( 'repeater' ) );
	}

	/**
	 * Test render generates correct HTML structure.
	 */
	public function testRenderGeneratesCorrectHtml(): void {
		$field = [
			'name'    => 'priority',
			'label'   => 'Priority',
			'options' => [
				'low'    => 'Low',
				'medium' => 'Medium',
				'high'   => 'High',
			],
		];

		$html = $this->field->render( $field, '' );

		$this->assertStringContainsString( '<fieldset', $html );
		$this->assertStringContainsString( '</fieldset>', $html );
		$this->assertStringContainsString( 'apd-radio-group', $html );
		$this->assertStringContainsString( 'role="radiogroup"', $html );
		$this->assertStringContainsString( 'id="apd-field-priority"', $html );
		$this->assertStringContainsString( '<legend', $html );
		$this->assertStringContainsString( 'Priority', $html );
		$this->assertStringContainsString( 'type="radio"', $html );
		$this->assertStringContainsString( 'name="apd_field_priority"', $html );
		$this->assertStringContainsString( 'value="low"', $html );
		$this->assertStringContainsString( 'Low', $html );
		$this->assertStringContainsString( 'value="medium"', $html );
		$this->assertStringContainsString( 'Medium', $html );
		$this->assertStringContainsString( 'value="high"', $html );
		$this->assertStringContainsString( 'High', $html );
	}

	/**
	 * Test render with selected value.
	 */
	public function testRenderWithSelectedValue(): void {
		$field = [
			'name'    => 'priority',
			'label'   => 'Priority',
			'options' => [
				'low'    => 'Low',
				'medium' => 'Medium',
				'high'   => 'High',
			],
		];

		$html = $this->field->render( $field, 'medium' );

		// Count checked attributes - should be exactly 1.
		$this->assertSame( 1, substr_count( $html, 'checked="checked"' ) );
		// The medium option should be checked.
		$this->assertMatchesRegularExpression( '/value="medium"[^>]*checked/', $html );
	}

	/**
	 * Test render with required field.
	 */
	public function testRenderWithRequiredField(): void {
		$field = [
			'name'     => 'priority',
			'label'    => 'Priority',
			'required' => true,
			'options'  => [
				'low'  => 'Low',
				'high' => 'High',
			],
		];

		$html = $this->field->render( $field, '' );

		$this->assertStringContainsString( 'aria-required="true"', $html );
		// Only the first radio should have required attribute.
		$this->assertSame( 1, substr_count( $html, ' required' ) );
	}

	/**
	 * Test render with description.
	 */
	public function testRenderWithDescription(): void {
		$field = [
			'name'        => 'priority',
			'label'       => 'Priority',
			'description' => 'Select the priority level.',
			'options'     => [
				'low' => 'Low',
			],
		];

		$html = $this->field->render( $field, '' );

		$this->assertStringContainsString( 'apd-field-description', $html );
		$this->assertStringContainsString( 'Select the priority level.', $html );
		$this->assertStringContainsString( 'aria-describedby', $html );
	}

	/**
	 * Test render generates unique IDs for each option.
	 */
	public function testRenderGeneratesUniqueIds(): void {
		$field = [
			'name'    => 'priority',
			'label'   => 'Priority',
			'options' => [
				'low'  => 'Low',
				'high' => 'High',
			],
		];

		$html = $this->field->render( $field, '' );

		$this->assertStringContainsString( 'id="apd-field-priority-0"', $html );
		$this->assertStringContainsString( 'id="apd-field-priority-1"', $html );
	}

	/**
	 * Test sanitize returns sanitized text.
	 */
	public function testSanitizeReturnsCleanValue(): void {
		$result = $this->field->sanitize( '  medium  ' );

		$this->assertSame( 'medium', $result );
	}

	/**
	 * Test sanitize strips HTML tags.
	 */
	public function testSanitizeStripsHtmlTags(): void {
		$result = $this->field->sanitize( '<script>alert("xss")</script>medium' );

		$this->assertStringNotContainsString( '<script>', $result );
	}

	/**
	 * Test validate passes with valid option.
	 */
	public function testValidatePassesWithValidOption(): void {
		$field = [
			'name'    => 'priority',
			'label'   => 'Priority',
			'options' => [
				'low'    => 'Low',
				'medium' => 'Medium',
				'high'   => 'High',
			],
		];

		$result = $this->field->validate( 'medium', $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate fails with invalid option.
	 */
	public function testValidateFailsWithInvalidOption(): void {
		$field = [
			'name'    => 'priority',
			'label'   => 'Priority',
			'options' => [
				'low'  => 'Low',
				'high' => 'High',
			],
		];

		$result = $this->field->validate( 'invalid', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'invalid_option', $result->get_error_codes() );
	}

	/**
	 * Test validate passes for empty optional field.
	 */
	public function testValidatePassesForEmptyOptionalField(): void {
		$field = [
			'name'     => 'priority',
			'label'    => 'Priority',
			'required' => false,
			'options'  => [
				'low' => 'Low',
			],
		];

		$result = $this->field->validate( '', $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate fails for empty required field.
	 */
	public function testValidateFailsForEmptyRequiredField(): void {
		$field = [
			'name'     => 'priority',
			'label'    => 'Priority',
			'required' => true,
			'options'  => [
				'low' => 'Low',
			],
		];

		$result = $this->field->validate( '', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'required', $result->get_error_codes() );
	}

	/**
	 * Test formatValue returns option label.
	 */
	public function testFormatValueReturnsLabel(): void {
		$field = [
			'name'    => 'priority',
			'options' => [
				'low'    => 'Low',
				'medium' => 'Medium',
				'high'   => 'High',
			],
		];

		$result = $this->field->formatValue( 'medium', $field );

		$this->assertSame( 'Medium', $result );
	}

	/**
	 * Test formatValue returns empty string for empty value.
	 */
	public function testFormatValueReturnsEmptyForEmptyValue(): void {
		$field = [
			'name'    => 'priority',
			'options' => [
				'low' => 'Low',
			],
		];

		$result = $this->field->formatValue( '', $field );

		$this->assertSame( '', $result );
	}

	/**
	 * Test formatValue returns value if option not found.
	 */
	public function testFormatValueReturnsValueIfOptionNotFound(): void {
		$field = [
			'name'    => 'priority',
			'options' => [
				'low' => 'Low',
			],
		];

		$result = $this->field->formatValue( 'unknown', $field );

		$this->assertSame( 'unknown', $result );
	}

	/**
	 * Test getDefaultValue returns empty string.
	 */
	public function testGetDefaultValueReturnsEmptyString(): void {
		$this->assertSame( '', $this->field->getDefaultValue() );
	}

	/**
	 * Test prepareValueForStorage returns value unchanged.
	 */
	public function testPrepareValueForStorageReturnsUnchanged(): void {
		$result = $this->field->prepareValueForStorage( 'medium' );

		$this->assertSame( 'medium', $result );
	}

	/**
	 * Test prepareValueFromStorage returns value unchanged.
	 */
	public function testPrepareValueFromStorageReturnsUnchanged(): void {
		$result = $this->field->prepareValueFromStorage( 'medium' );

		$this->assertSame( 'medium', $result );
	}
}
