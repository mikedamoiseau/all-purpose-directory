<?php
/**
 * Unit tests for SelectField.
 *
 * Tests select field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\SelectField;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use WP_Error;

/**
 * Test case for SelectField class.
 *
 * @covers \APD\Fields\Types\SelectField
 */
class SelectFieldTest extends UnitTestCase {

	/**
	 * The select field instance.
	 *
	 * @var SelectField
	 */
	private SelectField $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new SelectField();
	}

	/**
	 * Set up WordPress functions specific to SelectField.
	 */
	protected function setUpWordPressFunctions(): void {
		parent::setUpWordPressFunctions();

		// Mock the selected() function.
		Functions\stubs([
			'selected' => static function ( $selected, $current, $echo = true ) {
				$result = (string) $selected === (string) $current ? ' selected="selected"' : '';
				if ( $echo ) {
					echo $result;
				}
				return $result;
			},
		]);
	}

	/**
	 * Test getType returns 'select'.
	 */
	public function testGetTypeReturnsSelect(): void {
		$this->assertSame( 'select', $this->field->getType() );
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
			'name'    => 'status',
			'label'   => 'Status',
			'options' => [
				'active'   => 'Active',
				'inactive' => 'Inactive',
				'pending'  => 'Pending',
			],
		];

		$html = $this->field->render( $field, '' );

		$this->assertStringContainsString( '<select', $html );
		$this->assertStringContainsString( '</select>', $html );
		$this->assertStringContainsString( 'name="apd_field_status"', $html );
		$this->assertStringContainsString( 'id="apd-field-status"', $html );
		$this->assertStringContainsString( '<option value="active">Active</option>', $html );
		$this->assertStringContainsString( '<option value="inactive">Inactive</option>', $html );
		$this->assertStringContainsString( '<option value="pending">Pending</option>', $html );
	}

	/**
	 * Test render with empty option.
	 */
	public function testRenderWithEmptyOption(): void {
		$field = [
			'name'         => 'status',
			'label'        => 'Status',
			'empty_option' => 'Select a status...',
			'options'      => [
				'active'   => 'Active',
				'inactive' => 'Inactive',
			],
		];

		$html = $this->field->render( $field, '' );

		$this->assertStringContainsString( '<option value="">Select a status...</option>', $html );
	}

	/**
	 * Test render with selected value.
	 */
	public function testRenderWithSelectedValue(): void {
		$field = [
			'name'    => 'status',
			'label'   => 'Status',
			'options' => [
				'active'   => 'Active',
				'inactive' => 'Inactive',
			],
		];

		$html = $this->field->render( $field, 'inactive' );

		$this->assertStringContainsString( '<option value="inactive" selected="selected">Inactive</option>', $html );
	}

	/**
	 * Test render with required field.
	 */
	public function testRenderWithRequiredField(): void {
		$field = [
			'name'     => 'status',
			'label'    => 'Status',
			'required' => true,
			'options'  => [
				'active' => 'Active',
			],
		];

		$html = $this->field->render( $field, '' );

		$this->assertStringContainsString( 'required', $html );
		$this->assertStringContainsString( 'aria-required="true"', $html );
	}

	/**
	 * Test render with description.
	 */
	public function testRenderWithDescription(): void {
		$field = [
			'name'        => 'status',
			'label'       => 'Status',
			'description' => 'Choose the listing status.',
			'options'     => [
				'active' => 'Active',
			],
		];

		$html = $this->field->render( $field, '' );

		$this->assertStringContainsString( 'apd-field-description', $html );
		$this->assertStringContainsString( 'Choose the listing status.', $html );
		$this->assertStringContainsString( 'aria-describedby', $html );
	}

	/**
	 * Test sanitize returns text field sanitized value.
	 */
	public function testSanitizeReturnsCleanValue(): void {
		$result = $this->field->sanitize( '  active  ' );

		$this->assertSame( 'active', $result );
	}

	/**
	 * Test sanitize strips HTML tags.
	 */
	public function testSanitizeStripsHtmlTags(): void {
		$result = $this->field->sanitize( '<script>alert("xss")</script>active' );

		$this->assertStringNotContainsString( '<script>', $result );
	}

	/**
	 * Test validate passes with valid option.
	 */
	public function testValidatePassesWithValidOption(): void {
		$field = [
			'name'    => 'status',
			'label'   => 'Status',
			'options' => [
				'active'   => 'Active',
				'inactive' => 'Inactive',
			],
		];

		$result = $this->field->validate( 'active', $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate fails with invalid option.
	 */
	public function testValidateFailsWithInvalidOption(): void {
		$field = [
			'name'    => 'status',
			'label'   => 'Status',
			'options' => [
				'active'   => 'Active',
				'inactive' => 'Inactive',
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
			'name'     => 'status',
			'label'    => 'Status',
			'required' => false,
			'options'  => [
				'active' => 'Active',
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
			'name'     => 'status',
			'label'    => 'Status',
			'required' => true,
			'options'  => [
				'active' => 'Active',
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
			'name'    => 'status',
			'options' => [
				'active'   => 'Active',
				'inactive' => 'Inactive',
			],
		];

		$result = $this->field->formatValue( 'active', $field );

		$this->assertSame( 'Active', $result );
	}

	/**
	 * Test formatValue returns empty string for empty value.
	 */
	public function testFormatValueReturnsEmptyForEmptyValue(): void {
		$field = [
			'name'    => 'status',
			'options' => [
				'active' => 'Active',
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
			'name'    => 'status',
			'options' => [
				'active' => 'Active',
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
		$result = $this->field->prepareValueForStorage( 'active' );

		$this->assertSame( 'active', $result );
	}

	/**
	 * Test prepareValueFromStorage returns value unchanged.
	 */
	public function testPrepareValueFromStorageReturnsUnchanged(): void {
		$result = $this->field->prepareValueFromStorage( 'active' );

		$this->assertSame( 'active', $result );
	}
}
