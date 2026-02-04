<?php
/**
 * Unit tests for DateRangeField.
 *
 * Tests date range field type rendering, sanitization, validation, and storage.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\DateRangeField;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use WP_Error;

/**
 * Test case for DateRangeField class.
 *
 * @covers \APD\Fields\Types\DateRangeField
 */
class DateRangeFieldTest extends UnitTestCase {

	/**
	 * The field type instance.
	 *
	 * @var DateRangeField
	 */
	private DateRangeField $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new DateRangeField();

		// Mock wp_json_encode if not available.
		Functions\when( 'wp_json_encode' )->alias( function( $data ) {
			return json_encode( $data );
		} );
	}

	/**
	 * Test getType returns 'daterange'.
	 */
	public function testGetTypeReturnsDaterange(): void {
		$this->assertSame( 'daterange', $this->field->getType() );
	}

	/**
	 * Test supports returns correct values.
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
	public function testRenderGeneratesCorrectHtmlStructure(): void {
		$field = [
			'name'  => 'event_dates',
			'label' => 'Event Dates',
		];
		$value = [
			'start' => '2024-06-15',
			'end'   => '2024-06-20',
		];

		$html = $this->field->render( $field, $value );

		$this->assertStringContainsString( 'class="apd-daterange-wrapper"', $html );
		$this->assertStringContainsString( 'id="apd-field-event_dates"', $html );
		$this->assertStringContainsString( 'apd-daterange-start', $html );
		$this->assertStringContainsString( 'apd-daterange-end', $html );
	}

	/**
	 * Test render generates correct start date input.
	 */
	public function testRenderGeneratesCorrectStartDateInput(): void {
		$field = [
			'name' => 'event_dates',
		];
		$value = [
			'start' => '2024-06-15',
			'end'   => '2024-06-20',
		];

		$html = $this->field->render( $field, $value );

		$this->assertStringContainsString( 'type="date"', $html );
		$this->assertStringContainsString( 'id="apd-field-event_dates-start"', $html );
		$this->assertStringContainsString( 'name="apd_field_event_dates[start]"', $html );
		$this->assertStringContainsString( 'value="2024-06-15"', $html );
	}

	/**
	 * Test render generates correct end date input.
	 */
	public function testRenderGeneratesCorrectEndDateInput(): void {
		$field = [
			'name' => 'event_dates',
		];
		$value = [
			'start' => '2024-06-15',
			'end'   => '2024-06-20',
		];

		$html = $this->field->render( $field, $value );

		$this->assertStringContainsString( 'id="apd-field-event_dates-end"', $html );
		$this->assertStringContainsString( 'name="apd_field_event_dates[end]"', $html );
		$this->assertStringContainsString( 'value="2024-06-20"', $html );
	}

	/**
	 * Test render includes min/max attributes when configured.
	 */
	public function testRenderIncludesMinMaxAttributes(): void {
		$field = [
			'name' => 'event_dates',
			'min'  => '2024-01-01',
			'max'  => '2024-12-31',
		];

		$html = $this->field->render( $field, [ 'start' => '', 'end' => '' ] );

		$this->assertStringContainsString( 'min="2024-01-01"', $html );
		$this->assertStringContainsString( 'max="2024-12-31"', $html );
	}

	/**
	 * Test render includes required attributes on both inputs.
	 */
	public function testRenderIncludesRequiredAttributes(): void {
		$field = [
			'name'     => 'event_dates',
			'required' => true,
		];

		$html = $this->field->render( $field, [ 'start' => '', 'end' => '' ] );

		// Should contain 'required ' (the boolean attribute) twice (for both inputs).
		// Also aria-required="true" twice.
		$this->assertSame( 2, substr_count( $html, 'aria-required="true"' ) );
		$this->assertStringContainsString( 'required', $html );
	}

	/**
	 * Test render includes description.
	 */
	public function testRenderIncludesDescription(): void {
		$field = [
			'name'        => 'event_dates',
			'description' => 'Select start and end dates',
		];

		$html = $this->field->render( $field, [ 'start' => '', 'end' => '' ] );

		$this->assertStringContainsString( 'apd-field-description', $html );
		$this->assertStringContainsString( 'Select start and end dates', $html );
	}

	/**
	 * Test render includes labels.
	 */
	public function testRenderIncludesLabels(): void {
		$field = [
			'name' => 'event_dates',
		];

		$html = $this->field->render( $field, [ 'start' => '', 'end' => '' ] );

		$this->assertStringContainsString( 'Start Date', $html );
		$this->assertStringContainsString( 'End Date', $html );
	}

	/**
	 * Test sanitize returns valid date range unchanged.
	 */
	public function testSanitizeReturnsValidDateRangeUnchanged(): void {
		$value = [
			'start' => '2024-06-15',
			'end'   => '2024-06-20',
		];

		$result = $this->field->sanitize( $value );

		$this->assertSame( '2024-06-15', $result['start'] );
		$this->assertSame( '2024-06-20', $result['end'] );
	}

	/**
	 * Test sanitize returns empty for invalid dates.
	 */
	public function testSanitizeReturnsEmptyForInvalidDates(): void {
		$value = [
			'start' => 'not-a-date',
			'end'   => '2024/06/20',
		];

		$result = $this->field->sanitize( $value );

		$this->assertSame( '', $result['start'] );
		$this->assertSame( '', $result['end'] );
	}

	/**
	 * Test sanitize handles non-array value.
	 */
	public function testSanitizeHandlesNonArrayValue(): void {
		$result = $this->field->sanitize( 'not-an-array' );

		$this->assertSame( '', $result['start'] );
		$this->assertSame( '', $result['end'] );
	}

	/**
	 * Test sanitize handles partial values.
	 */
	public function testSanitizeHandlesPartialValues(): void {
		$result = $this->field->sanitize( [ 'start' => '2024-06-15' ] );

		$this->assertSame( '2024-06-15', $result['start'] );
		$this->assertSame( '', $result['end'] );
	}

	/**
	 * Test validate passes for valid date range.
	 */
	public function testValidatePassesForValidDateRange(): void {
		$field = [
			'name'  => 'event_dates',
			'label' => 'Event Dates',
		];
		$value = [
			'start' => '2024-06-15',
			'end'   => '2024-06-20',
		];

		$result = $this->field->validate( $value, $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test validate passes for empty optional field.
	 */
	public function testValidatePassesForEmptyOptionalField(): void {
		$field = [
			'name'     => 'event_dates',
			'required' => false,
		];
		$value = [
			'start' => '',
			'end'   => '',
		];

		$result = $this->field->validate( $value, $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test validate fails for missing dates in required field.
	 */
	public function testValidateFailsForMissingDatesInRequiredField(): void {
		$field = [
			'name'     => 'event_dates',
			'label'    => 'Event Dates',
			'required' => true,
		];
		$value = [
			'start' => '2024-06-15',
			'end'   => '',
		];

		$result = $this->field->validate( $value, $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'required', $result->get_error_codes() );
	}

	/**
	 * Test validate fails when end date is before start date.
	 */
	public function testValidateFailsWhenEndBeforeStart(): void {
		$field = [
			'name'  => 'event_dates',
			'label' => 'Event Dates',
		];
		$value = [
			'start' => '2024-06-20',
			'end'   => '2024-06-15',
		];

		$result = $this->field->validate( $value, $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'end_before_start', $result->get_error_codes() );
	}

	/**
	 * Test validate passes when end date equals start date.
	 */
	public function testValidatePassesWhenEndEqualsStart(): void {
		$field = [
			'name' => 'event_dates',
		];
		$value = [
			'start' => '2024-06-15',
			'end'   => '2024-06-15',
		];

		$result = $this->field->validate( $value, $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test validate fails for invalid start date format.
	 */
	public function testValidateFailsForInvalidStartDateFormat(): void {
		$field = [
			'name'  => 'event_dates',
			'label' => 'Event Dates',
		];
		$value = [
			'start' => 'not-a-date',
			'end'   => '2024-06-20',
		];

		$result = $this->field->validate( $value, $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'invalid_start_date', $result->get_error_codes() );
	}

	/**
	 * Test validate fails for invalid end date format.
	 */
	public function testValidateFailsForInvalidEndDateFormat(): void {
		$field = [
			'name'  => 'event_dates',
			'label' => 'Event Dates',
		];
		$value = [
			'start' => '2024-06-15',
			'end'   => 'not-a-date',
		];

		$result = $this->field->validate( $value, $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'invalid_end_date', $result->get_error_codes() );
	}

	/**
	 * Test validate fails when dates are before min.
	 */
	public function testValidateFailsWhenDatesBeforeMin(): void {
		$field = [
			'name'  => 'event_dates',
			'label' => 'Event Dates',
			'min'   => '2024-06-01',
		];
		$value = [
			'start' => '2024-05-15',
			'end'   => '2024-05-20',
		];

		$result = $this->field->validate( $value, $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'start_too_early', $result->get_error_codes() );
		$this->assertContains( 'end_too_early', $result->get_error_codes() );
	}

	/**
	 * Test validate fails when dates are after max.
	 */
	public function testValidateFailsWhenDatesAfterMax(): void {
		$field = [
			'name'  => 'event_dates',
			'label' => 'Event Dates',
			'max'   => '2024-06-30',
		];
		$value = [
			'start' => '2024-07-15',
			'end'   => '2024-07-20',
		];

		$result = $this->field->validate( $value, $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'start_too_late', $result->get_error_codes() );
		$this->assertContains( 'end_too_late', $result->get_error_codes() );
	}

	/**
	 * Test getDefaultValue returns array with empty strings.
	 */
	public function testGetDefaultValueReturnsEmptyArray(): void {
		$default = $this->field->getDefaultValue();

		$this->assertIsArray( $default );
		$this->assertSame( '', $default['start'] );
		$this->assertSame( '', $default['end'] );
	}

	/**
	 * Test formatValue formats date range correctly.
	 */
	public function testFormatValueFormatsDateRangeCorrectly(): void {
		Functions\when( 'get_option' )->justReturn( 'F j, Y' );
		Functions\when( 'date_i18n' )->alias( function( $format, $timestamp ) {
			return date( $format, $timestamp );
		} );

		$field = [
			'name'        => 'event_dates',
			'date_format' => 'F j, Y',
		];
		$value = [
			'start' => '2024-06-15',
			'end'   => '2024-06-20',
		];

		$result = $this->field->formatValue( $value, $field );
		$this->assertSame( 'June 15, 2024 - June 20, 2024', $result );
	}

	/**
	 * Test formatValue uses custom separator.
	 */
	public function testFormatValueUsesCustomSeparator(): void {
		Functions\when( 'get_option' )->justReturn( 'Y-m-d' );
		Functions\when( 'date_i18n' )->alias( function( $format, $timestamp ) {
			return date( $format, $timestamp );
		} );

		$field = [
			'name'        => 'event_dates',
			'date_format' => 'Y-m-d',
			'separator'   => ' to ',
		];
		$value = [
			'start' => '2024-06-15',
			'end'   => '2024-06-20',
		];

		$result = $this->field->formatValue( $value, $field );
		$this->assertSame( '2024-06-15 to 2024-06-20', $result );
	}

	/**
	 * Test formatValue returns empty string for empty value.
	 */
	public function testFormatValueReturnsEmptyForEmptyValue(): void {
		$field = [ 'name' => 'event_dates' ];
		$value = [ 'start' => '', 'end' => '' ];

		$result = $this->field->formatValue( $value, $field );
		$this->assertSame( '', $result );
	}

	/**
	 * Test formatValue handles only start date.
	 */
	public function testFormatValueHandlesOnlyStartDate(): void {
		Functions\when( 'get_option' )->justReturn( 'Y-m-d' );
		Functions\when( 'date_i18n' )->alias( function( $format, $timestamp ) {
			return date( $format, $timestamp );
		} );

		$field = [ 'name' => 'event_dates' ];
		$value = [ 'start' => '2024-06-15', 'end' => '' ];

		$result = $this->field->formatValue( $value, $field );
		$this->assertSame( '2024-06-15', $result );
	}

	/**
	 * Test formatValue handles only end date.
	 */
	public function testFormatValueHandlesOnlyEndDate(): void {
		Functions\when( 'get_option' )->justReturn( 'Y-m-d' );
		Functions\when( 'date_i18n' )->alias( function( $format, $timestamp ) {
			return date( $format, $timestamp );
		} );

		$field = [ 'name' => 'event_dates' ];
		$value = [ 'start' => '', 'end' => '2024-06-20' ];

		$result = $this->field->formatValue( $value, $field );
		$this->assertSame( '2024-06-20', $result );
	}

	/**
	 * Test prepareValueForStorage JSON encodes the array.
	 */
	public function testPrepareValueForStorageJsonEncodesArray(): void {
		$value = [
			'start' => '2024-06-15',
			'end'   => '2024-06-20',
		];

		$result = $this->field->prepareValueForStorage( $value );

		$this->assertIsString( $result );
		$decoded = json_decode( $result, true );
		$this->assertSame( '2024-06-15', $decoded['start'] );
		$this->assertSame( '2024-06-20', $decoded['end'] );
	}

	/**
	 * Test prepareValueForStorage returns empty string for empty values.
	 */
	public function testPrepareValueForStorageReturnsEmptyForEmptyValues(): void {
		$value = [ 'start' => '', 'end' => '' ];

		$result = $this->field->prepareValueForStorage( $value );
		$this->assertSame( '', $result );
	}

	/**
	 * Test prepareValueFromStorage JSON decodes the string.
	 */
	public function testPrepareValueFromStorageJsonDecodesString(): void {
		$stored = '{"start":"2024-06-15","end":"2024-06-20"}';

		$result = $this->field->prepareValueFromStorage( $stored );

		$this->assertIsArray( $result );
		$this->assertSame( '2024-06-15', $result['start'] );
		$this->assertSame( '2024-06-20', $result['end'] );
	}

	/**
	 * Test prepareValueFromStorage handles empty string.
	 */
	public function testPrepareValueFromStorageHandlesEmptyString(): void {
		$result = $this->field->prepareValueFromStorage( '' );

		$this->assertIsArray( $result );
		$this->assertSame( '', $result['start'] );
		$this->assertSame( '', $result['end'] );
	}

	/**
	 * Test prepareValueFromStorage handles null.
	 */
	public function testPrepareValueFromStorageHandlesNull(): void {
		$result = $this->field->prepareValueFromStorage( null );

		$this->assertIsArray( $result );
		$this->assertSame( '', $result['start'] );
		$this->assertSame( '', $result['end'] );
	}

	/**
	 * Test prepareValueFromStorage handles already array value.
	 */
	public function testPrepareValueFromStorageHandlesArrayValue(): void {
		$value = [
			'start' => '2024-06-15',
			'end'   => '2024-06-20',
		];

		$result = $this->field->prepareValueFromStorage( $value );

		$this->assertSame( '2024-06-15', $result['start'] );
		$this->assertSame( '2024-06-20', $result['end'] );
	}

	/**
	 * Test prepareValueFromStorage handles invalid JSON.
	 */
	public function testPrepareValueFromStorageHandlesInvalidJson(): void {
		$result = $this->field->prepareValueFromStorage( 'not-valid-json' );

		$this->assertIsArray( $result );
		$this->assertSame( '', $result['start'] );
		$this->assertSame( '', $result['end'] );
	}
}
