<?php
/**
 * Unit tests for DateTimeField.
 *
 * Tests datetime field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\DateTimeField;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use WP_Error;

/**
 * Test case for DateTimeField class.
 *
 * @covers \APD\Fields\Types\DateTimeField
 */
class DateTimeFieldTest extends UnitTestCase {

	/**
	 * The field type instance.
	 *
	 * @var DateTimeField
	 */
	private DateTimeField $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new DateTimeField();
	}

	/**
	 * Test getType returns 'datetime'.
	 */
	public function testGetTypeReturnsDatetime(): void {
		$this->assertSame( 'datetime', $this->field->getType() );
	}

	/**
	 * Test supports returns correct values.
	 */
	public function testSupportsReturnsCorrectValues(): void {
		$this->assertFalse( $this->field->supports( 'searchable' ) );
		$this->assertFalse( $this->field->supports( 'filterable' ) );
		$this->assertTrue( $this->field->supports( 'sortable' ) );
		$this->assertFalse( $this->field->supports( 'repeater' ) );
	}

	/**
	 * Test render generates correct HTML.
	 */
	public function testRenderGeneratesCorrectHtml(): void {
		$field = [
			'name'  => 'event_datetime',
			'label' => 'Event Date & Time',
		];

		$html = $this->field->render( $field, '2024-06-15T14:30' );

		$this->assertStringContainsString( 'type="datetime-local"', $html );
		$this->assertStringContainsString( 'name="apd_field_event_datetime"', $html );
		$this->assertStringContainsString( 'id="apd-field-event_datetime"', $html );
		$this->assertStringContainsString( 'value="2024-06-15T14:30"', $html );
	}

	/**
	 * Test render includes min/max attributes when configured.
	 */
	public function testRenderIncludesMinMaxAttributes(): void {
		$field = [
			'name' => 'event_datetime',
			'min'  => '2024-01-01T00:00',
			'max'  => '2024-12-31T23:59',
		];

		$html = $this->field->render( $field, '' );

		$this->assertStringContainsString( 'min="2024-01-01T00:00"', $html );
		$this->assertStringContainsString( 'max="2024-12-31T23:59"', $html );
	}

	/**
	 * Test render includes required attributes.
	 */
	public function testRenderIncludesRequiredAttributes(): void {
		$field = [
			'name'     => 'event_datetime',
			'required' => true,
		];

		$html = $this->field->render( $field, '' );

		$this->assertStringContainsString( 'required', $html );
		$this->assertStringContainsString( 'aria-required="true"', $html );
	}

	/**
	 * Test render includes description.
	 */
	public function testRenderIncludesDescription(): void {
		$field = [
			'name'        => 'event_datetime',
			'description' => 'Select the event date and time',
		];

		$html = $this->field->render( $field, '' );

		$this->assertStringContainsString( 'apd-field-description', $html );
		$this->assertStringContainsString( 'Select the event date and time', $html );
	}

	/**
	 * Test sanitize returns valid datetime unchanged (normalized to Y-m-d\TH:i).
	 */
	public function testSanitizeReturnsValidDatetimeNormalized(): void {
		$this->assertSame( '2024-06-15T14:30', $this->field->sanitize( '2024-06-15T14:30' ) );
		$this->assertSame( '2024-06-15T14:30', $this->field->sanitize( '2024-06-15T14:30:45' ) );
	}

	/**
	 * Test sanitize returns empty string for invalid datetime.
	 */
	public function testSanitizeReturnsEmptyForInvalidDatetime(): void {
		$this->assertSame( '', $this->field->sanitize( 'not-a-datetime' ) );
		$this->assertSame( '', $this->field->sanitize( '2024-06-15 14:30' ) );
		$this->assertSame( '', $this->field->sanitize( '2024/06/15T14:30' ) );
	}

	/**
	 * Test sanitize returns empty string for invalid datetime values.
	 */
	public function testSanitizeReturnsEmptyForInvalidDatetimeValues(): void {
		// Invalid month.
		$this->assertSame( '', $this->field->sanitize( '2024-13-15T14:30' ) );
		// Invalid day.
		$this->assertSame( '', $this->field->sanitize( '2024-06-32T14:30' ) );
		// Invalid hour.
		$this->assertSame( '', $this->field->sanitize( '2024-06-15T25:30' ) );
		// Invalid minute.
		$this->assertSame( '', $this->field->sanitize( '2024-06-15T14:60' ) );
	}

	/**
	 * Test sanitize returns empty string for empty or non-string values.
	 */
	public function testSanitizeReturnsEmptyForEmptyValues(): void {
		$this->assertSame( '', $this->field->sanitize( '' ) );
		$this->assertSame( '', $this->field->sanitize( null ) );
		$this->assertSame( '', $this->field->sanitize( [] ) );
	}

	/**
	 * Test validate passes for valid datetime.
	 */
	public function testValidatePassesForValidDatetime(): void {
		$field = [
			'name'  => 'event_datetime',
			'label' => 'Event Date & Time',
		];

		$result = $this->field->validate( '2024-06-15T14:30', $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test validate passes for empty optional field.
	 */
	public function testValidatePassesForEmptyOptionalField(): void {
		$field = [
			'name'     => 'event_datetime',
			'required' => false,
		];

		$result = $this->field->validate( '', $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test validate fails for empty required field.
	 */
	public function testValidateFailsForEmptyRequiredField(): void {
		$field = [
			'name'     => 'event_datetime',
			'label'    => 'Event Date & Time',
			'required' => true,
		];

		$result = $this->field->validate( '', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'required', $result->get_error_codes() );
	}

	/**
	 * Test validate fails for invalid datetime format.
	 */
	public function testValidateFailsForInvalidDatetimeFormat(): void {
		$field = [
			'name'  => 'event_datetime',
			'label' => 'Event Date & Time',
		];

		$result = $this->field->validate( 'not-a-datetime', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'invalid_datetime', $result->get_error_codes() );
	}

	/**
	 * Test validate fails when datetime is before min.
	 */
	public function testValidateFailsWhenDatetimeBeforeMin(): void {
		$field = [
			'name'  => 'event_datetime',
			'label' => 'Event Date & Time',
			'min'   => '2024-06-01T09:00',
		];

		$result = $this->field->validate( '2024-05-15T14:30', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'datetime_too_early', $result->get_error_codes() );
	}

	/**
	 * Test validate fails when datetime is after max.
	 */
	public function testValidateFailsWhenDatetimeAfterMax(): void {
		$field = [
			'name'  => 'event_datetime',
			'label' => 'Event Date & Time',
			'max'   => '2024-06-30T17:00',
		];

		$result = $this->field->validate( '2024-07-15T14:30', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'datetime_too_late', $result->get_error_codes() );
	}

	/**
	 * Test validate passes when datetime is within range.
	 */
	public function testValidatePassesWhenDatetimeWithinRange(): void {
		$field = [
			'name' => 'event_datetime',
			'min'  => '2024-06-01T09:00',
			'max'  => '2024-06-30T17:00',
		];

		$result = $this->field->validate( '2024-06-15T14:30', $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test formatValue formats datetime correctly.
	 */
	public function testFormatValueFormatsDatetimeCorrectly(): void {
		// Mock WordPress functions.
		Functions\when( 'get_option' )->alias( function( $option, $default = '' ) {
			return match ( $option ) {
				'date_format' => 'F j, Y',
				'time_format' => 'g:i A',
				default => $default,
			};
		} );
		Functions\when( 'date_i18n' )->alias( function( $format, $timestamp ) {
			return date( $format, $timestamp );
		} );

		$field = [
			'name'            => 'event_datetime',
			'datetime_format' => 'F j, Y g:i A',
		];

		$result = $this->field->formatValue( '2024-06-15T14:30', $field );
		$this->assertSame( 'June 15, 2024 2:30 PM', $result );
	}

	/**
	 * Test formatValue uses configured format.
	 */
	public function testFormatValueUsesConfiguredFormat(): void {
		Functions\when( 'date_i18n' )->alias( function( $format, $timestamp ) {
			return date( $format, $timestamp );
		} );

		$field = [
			'name'            => 'event_datetime',
			'datetime_format' => 'd/m/Y H:i',
		];

		$result = $this->field->formatValue( '2024-06-15T14:30', $field );
		$this->assertSame( '15/06/2024 14:30', $result );
	}

	/**
	 * Test formatValue returns empty string for empty value.
	 */
	public function testFormatValueReturnsEmptyForEmptyValue(): void {
		$field = [ 'name' => 'event_datetime' ];

		$this->assertSame( '', $this->field->formatValue( '', $field ) );
		$this->assertSame( '', $this->field->formatValue( null, $field ) );
	}

	/**
	 * Test formatValue returns escaped value for invalid datetime.
	 */
	public function testFormatValueReturnsEscapedValueForInvalidDatetime(): void {
		$field = [ 'name' => 'event_datetime' ];

		$result = $this->field->formatValue( 'invalid-datetime', $field );
		$this->assertSame( 'invalid-datetime', $result );
	}

	/**
	 * Test prepareValueForStorage returns value unchanged.
	 */
	public function testPrepareValueForStorageReturnsValueUnchanged(): void {
		$value = '2024-06-15T14:30';
		$this->assertSame( $value, $this->field->prepareValueForStorage( $value ) );
	}

	/**
	 * Test prepareValueForStorage returns empty string for empty value.
	 */
	public function testPrepareValueForStorageReturnsEmptyForEmptyValue(): void {
		$this->assertSame( '', $this->field->prepareValueForStorage( '' ) );
		$this->assertSame( '', $this->field->prepareValueForStorage( null ) );
	}

	/**
	 * Test prepareValueFromStorage converts space to T separator.
	 */
	public function testPrepareValueFromStorageConvertsSpaceToT(): void {
		$this->assertSame( '2024-06-15T14:30', $this->field->prepareValueFromStorage( '2024-06-15 14:30' ) );
	}

	/**
	 * Test prepareValueFromStorage keeps T separator unchanged.
	 */
	public function testPrepareValueFromStorageKeepsTSeparator(): void {
		$this->assertSame( '2024-06-15T14:30', $this->field->prepareValueFromStorage( '2024-06-15T14:30' ) );
	}

	/**
	 * Test prepareValueFromStorage returns empty string for empty value.
	 */
	public function testPrepareValueFromStorageReturnsEmptyForEmptyValue(): void {
		$this->assertSame( '', $this->field->prepareValueFromStorage( '' ) );
		$this->assertSame( '', $this->field->prepareValueFromStorage( null ) );
	}

	/**
	 * Test getDefaultValue returns empty string.
	 */
	public function testGetDefaultValueReturnsEmptyString(): void {
		$this->assertSame( '', $this->field->getDefaultValue() );
	}
}
