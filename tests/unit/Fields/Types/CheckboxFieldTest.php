<?php
/**
 * Unit tests for CheckboxField.
 *
 * Tests checkbox field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\CheckboxField;
use APD\Tests\Unit\UnitTestCase;
use WP_Error;

/**
 * Test case for CheckboxField class.
 *
 * @covers \APD\Fields\Types\CheckboxField
 */
class CheckboxFieldTest extends UnitTestCase {

	/**
	 * The checkbox field instance.
	 *
	 * @var CheckboxField
	 */
	private CheckboxField $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new CheckboxField();
	}

	/**
	 * Test getType returns 'checkbox'.
	 */
	public function testGetTypeReturnsCheckbox(): void {
		$this->assertSame( 'checkbox', $this->field->getType() );
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
			'name'           => 'featured',
			'label'          => 'Featured',
			'checkbox_label' => 'Mark as featured',
		];

		$html = $this->field->render( $field, false );

		$this->assertStringContainsString( '<input', $html );
		$this->assertStringContainsString( 'type="checkbox"', $html );
		$this->assertStringContainsString( 'name="apd_field_featured"', $html );
		$this->assertStringContainsString( 'id="apd-field-featured"', $html );
		$this->assertStringContainsString( 'value="1"', $html );
		$this->assertStringContainsString( 'apd-checkbox-label', $html );
		$this->assertStringContainsString( 'Mark as featured', $html );
	}

	/**
	 * Test render with checked value.
	 */
	public function testRenderWithCheckedValue(): void {
		$field = [
			'name'  => 'featured',
			'label' => 'Featured',
		];

		$html = $this->field->render( $field, true );

		$this->assertStringContainsString( 'checked="checked"', $html );
	}

	/**
	 * Test render with unchecked value.
	 */
	public function testRenderWithUncheckedValue(): void {
		$field = [
			'name'  => 'featured',
			'label' => 'Featured',
		];

		$html = $this->field->render( $field, false );

		$this->assertStringNotContainsString( 'checked="checked"', $html );
	}

	/**
	 * Test render with required field.
	 */
	public function testRenderWithRequiredField(): void {
		$field = [
			'name'     => 'terms',
			'label'    => 'Terms',
			'required' => true,
		];

		$html = $this->field->render( $field, false );

		$this->assertStringContainsString( 'required', $html );
		$this->assertStringContainsString( 'aria-required="true"', $html );
	}

	/**
	 * Test render with description.
	 */
	public function testRenderWithDescription(): void {
		$field = [
			'name'        => 'featured',
			'label'       => 'Featured',
			'description' => 'Check to feature this listing.',
		];

		$html = $this->field->render( $field, false );

		$this->assertStringContainsString( 'apd-field-description', $html );
		$this->assertStringContainsString( 'Check to feature this listing.', $html );
		$this->assertStringContainsString( 'aria-describedby', $html );
	}

	/**
	 * Test render uses checkbox_label if provided, otherwise label.
	 */
	public function testRenderUsesCheckboxLabelOrLabel(): void {
		$field = [
			'name'  => 'featured',
			'label' => 'Is Featured',
		];

		$html = $this->field->render( $field, false );

		$this->assertStringContainsString( 'Is Featured', $html );
	}

	/**
	 * Test sanitize casts to boolean true.
	 */
	public function testSanitizeCastsToBooleanTrue(): void {
		$this->assertTrue( $this->field->sanitize( '1' ) );
		$this->assertTrue( $this->field->sanitize( 'true' ) );
		$this->assertTrue( $this->field->sanitize( 'yes' ) );
		$this->assertTrue( $this->field->sanitize( 'on' ) );
		$this->assertTrue( $this->field->sanitize( true ) );
		$this->assertTrue( $this->field->sanitize( 1 ) );
	}

	/**
	 * Test sanitize casts to boolean false.
	 */
	public function testSanitizeCastsToBooleanFalse(): void {
		$this->assertFalse( $this->field->sanitize( '0' ) );
		$this->assertFalse( $this->field->sanitize( 'false' ) );
		$this->assertFalse( $this->field->sanitize( '' ) );
		$this->assertFalse( $this->field->sanitize( false ) );
		$this->assertFalse( $this->field->sanitize( 0 ) );
		$this->assertFalse( $this->field->sanitize( null ) );
	}

	/**
	 * Test validate passes for checked.
	 */
	public function testValidatePassesForChecked(): void {
		$field = [
			'name'     => 'terms',
			'label'    => 'Terms',
			'required' => true,
		];

		$result = $this->field->validate( true, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate passes for unchecked optional field.
	 */
	public function testValidatePassesForUncheckedOptionalField(): void {
		$field = [
			'name'     => 'featured',
			'label'    => 'Featured',
			'required' => false,
		];

		$result = $this->field->validate( false, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate fails for unchecked required field.
	 */
	public function testValidateFailsForUncheckedRequiredField(): void {
		$field = [
			'name'     => 'terms',
			'label'    => 'Terms',
			'required' => true,
		];

		$result = $this->field->validate( false, $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'required', $result->get_error_codes() );
	}

	/**
	 * Test getDefaultValue returns false.
	 */
	public function testGetDefaultValueReturnsFalse(): void {
		$this->assertFalse( $this->field->getDefaultValue() );
	}

	/**
	 * Test formatValue returns Yes for true.
	 */
	public function testFormatValueReturnsYesForTrue(): void {
		$field = [ 'name' => 'featured' ];

		$result = $this->field->formatValue( true, $field );

		$this->assertSame( 'Yes', $result );
	}

	/**
	 * Test formatValue returns No for false.
	 */
	public function testFormatValueReturnsNoForFalse(): void {
		$field = [ 'name' => 'featured' ];

		$result = $this->field->formatValue( false, $field );

		$this->assertSame( 'No', $result );
	}

	/**
	 * Test formatValue returns custom labels.
	 */
	public function testFormatValueReturnsCustomLabels(): void {
		$field = [
			'name'      => 'featured',
			'yes_label' => 'Enabled',
			'no_label'  => 'Disabled',
		];

		$this->assertSame( 'Enabled', $this->field->formatValue( true, $field ) );
		$this->assertSame( 'Disabled', $this->field->formatValue( false, $field ) );
	}

	/**
	 * Test prepareValueForStorage returns '1' for true.
	 */
	public function testPrepareValueForStorageReturnsOneForTrue(): void {
		$result = $this->field->prepareValueForStorage( true );

		$this->assertSame( '1', $result );
	}

	/**
	 * Test prepareValueForStorage returns '0' for false.
	 */
	public function testPrepareValueForStorageReturnsZeroForFalse(): void {
		$result = $this->field->prepareValueForStorage( false );

		$this->assertSame( '0', $result );
	}

	/**
	 * Test prepareValueFromStorage returns boolean true.
	 */
	public function testPrepareValueFromStorageReturnsBooleanTrue(): void {
		$this->assertTrue( $this->field->prepareValueFromStorage( '1' ) );
		$this->assertTrue( $this->field->prepareValueFromStorage( 1 ) );
		$this->assertTrue( $this->field->prepareValueFromStorage( true ) );
	}

	/**
	 * Test prepareValueFromStorage returns boolean false.
	 */
	public function testPrepareValueFromStorageReturnsBooleanFalse(): void {
		$this->assertFalse( $this->field->prepareValueFromStorage( '0' ) );
		$this->assertFalse( $this->field->prepareValueFromStorage( 0 ) );
		$this->assertFalse( $this->field->prepareValueFromStorage( false ) );
		$this->assertFalse( $this->field->prepareValueFromStorage( '' ) );
	}
}
