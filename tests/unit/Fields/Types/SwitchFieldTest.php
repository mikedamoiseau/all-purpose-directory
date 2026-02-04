<?php
/**
 * Unit tests for SwitchField.
 *
 * Tests switch field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\SwitchField;
use APD\Tests\Unit\UnitTestCase;
use WP_Error;

/**
 * Test case for SwitchField class.
 *
 * @covers \APD\Fields\Types\SwitchField
 */
class SwitchFieldTest extends UnitTestCase {

	/**
	 * The switch field instance.
	 *
	 * @var SwitchField
	 */
	private SwitchField $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new SwitchField();
	}

	/**
	 * Test getType returns 'switch'.
	 */
	public function testGetTypeReturnsSwitch(): void {
		$this->assertSame( 'switch', $this->field->getType() );
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
			'name'  => 'notifications',
			'label' => 'Notifications',
		];

		$html = $this->field->render( $field, false );

		$this->assertStringContainsString( '<label', $html );
		$this->assertStringContainsString( 'apd-switch', $html );
		$this->assertStringContainsString( '<input', $html );
		$this->assertStringContainsString( 'type="checkbox"', $html );
		$this->assertStringContainsString( 'name="apd_field_notifications"', $html );
		$this->assertStringContainsString( 'id="apd-field-notifications"', $html );
		$this->assertStringContainsString( 'value="1"', $html );
		$this->assertStringContainsString( 'apd-switch-input', $html );
		$this->assertStringContainsString( 'role="switch"', $html );
		$this->assertStringContainsString( 'apd-switch-slider', $html );
		$this->assertStringContainsString( 'apd-switch-on', $html );
		$this->assertStringContainsString( 'apd-switch-off', $html );
	}

	/**
	 * Test render with on value.
	 */
	public function testRenderWithOnValue(): void {
		$field = [
			'name'  => 'notifications',
			'label' => 'Notifications',
		];

		$html = $this->field->render( $field, true );

		$this->assertStringContainsString( 'checked="checked"', $html );
		$this->assertStringContainsString( 'aria-checked="true"', $html );
	}

	/**
	 * Test render with off value.
	 */
	public function testRenderWithOffValue(): void {
		$field = [
			'name'  => 'notifications',
			'label' => 'Notifications',
		];

		$html = $this->field->render( $field, false );

		$this->assertStringNotContainsString( 'checked="checked"', $html );
		$this->assertStringContainsString( 'aria-checked="false"', $html );
	}

	/**
	 * Test render with required field.
	 */
	public function testRenderWithRequiredField(): void {
		$field = [
			'name'     => 'notifications',
			'label'    => 'Notifications',
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
			'name'        => 'notifications',
			'label'       => 'Notifications',
			'description' => 'Enable email notifications.',
		];

		$html = $this->field->render( $field, false );

		$this->assertStringContainsString( 'apd-field-description', $html );
		$this->assertStringContainsString( 'Enable email notifications.', $html );
		$this->assertStringContainsString( 'aria-describedby', $html );
	}

	/**
	 * Test render with custom on/off labels.
	 */
	public function testRenderWithCustomLabels(): void {
		$field = [
			'name'      => 'notifications',
			'label'     => 'Notifications',
			'on_label'  => 'Enabled',
			'off_label' => 'Disabled',
		];

		$html = $this->field->render( $field, false );

		$this->assertStringContainsString( 'Enabled', $html );
		$this->assertStringContainsString( 'Disabled', $html );
	}

	/**
	 * Test render uses default on/off labels.
	 */
	public function testRenderUsesDefaultLabels(): void {
		$field = [
			'name'  => 'notifications',
			'label' => 'Notifications',
		];

		$html = $this->field->render( $field, false );

		$this->assertStringContainsString( 'On', $html );
		$this->assertStringContainsString( 'Off', $html );
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
	 * Test validate passes for on.
	 */
	public function testValidatePassesForOn(): void {
		$field = [
			'name'     => 'notifications',
			'label'    => 'Notifications',
			'required' => true,
		];

		$result = $this->field->validate( true, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate passes for off optional field.
	 */
	public function testValidatePassesForOffOptionalField(): void {
		$field = [
			'name'     => 'notifications',
			'label'    => 'Notifications',
			'required' => false,
		];

		$result = $this->field->validate( false, $field );

		$this->assertTrue( $result );
	}

	/**
	 * Test validate fails for off required field.
	 */
	public function testValidateFailsForOffRequiredField(): void {
		$field = [
			'name'     => 'notifications',
			'label'    => 'Notifications',
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
	 * Test formatValue returns On for true.
	 */
	public function testFormatValueReturnsOnForTrue(): void {
		$field = [ 'name' => 'notifications' ];

		$result = $this->field->formatValue( true, $field );

		$this->assertSame( 'On', $result );
	}

	/**
	 * Test formatValue returns Off for false.
	 */
	public function testFormatValueReturnsOffForFalse(): void {
		$field = [ 'name' => 'notifications' ];

		$result = $this->field->formatValue( false, $field );

		$this->assertSame( 'Off', $result );
	}

	/**
	 * Test formatValue returns custom labels.
	 */
	public function testFormatValueReturnsCustomLabels(): void {
		$field = [
			'name'      => 'notifications',
			'on_label'  => 'Enabled',
			'off_label' => 'Disabled',
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
