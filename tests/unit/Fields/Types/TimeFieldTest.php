<?php
/**
 * Unit tests for TimeField.
 *
 * Tests time field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\TimeField;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use WP_Error;

/**
 * Test case for TimeField class.
 *
 * @covers \APD\Fields\Types\TimeField
 */
class TimeFieldTest extends UnitTestCase {

	/**
	 * The field type instance.
	 *
	 * @var TimeField
	 */
	private TimeField $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new TimeField();
	}

	/**
	 * Test getType returns 'time'.
	 */
	public function testGetTypeReturnsTime(): void {
		$this->assertSame( 'time', $this->field->getType() );
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
			'name'  => 'start_time',
			'label' => 'Start Time',
		];

		$html = $this->field->render( $field, '14:30' );

		$this->assertStringContainsString( 'type="time"', $html );
		$this->assertStringContainsString( 'name="apd_field_start_time"', $html );
		$this->assertStringContainsString( 'id="apd-field-start_time"', $html );
		$this->assertStringContainsString( 'value="14:30"', $html );
	}

	/**
	 * Test render includes min/max attributes when configured.
	 */
	public function testRenderIncludesMinMaxAttributes(): void {
		$field = [
			'name' => 'start_time',
			'min'  => '09:00',
			'max'  => '17:00',
		];

		$html = $this->field->render( $field, '' );

		$this->assertStringContainsString( 'min="09:00"', $html );
		$this->assertStringContainsString( 'max="17:00"', $html );
	}

	/**
	 * Test render includes required attributes.
	 */
	public function testRenderIncludesRequiredAttributes(): void {
		$field = [
			'name'     => 'start_time',
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
			'name'        => 'start_time',
			'description' => 'Select the start time',
		];

		$html = $this->field->render( $field, '' );

		$this->assertStringContainsString( 'apd-field-description', $html );
		$this->assertStringContainsString( 'Select the start time', $html );
	}

	/**
	 * Test sanitize returns valid time unchanged (normalized to H:i).
	 */
	public function testSanitizeReturnsValidTimeNormalized(): void {
		$this->assertSame( '14:30', $this->field->sanitize( '14:30' ) );
		$this->assertSame( '14:30', $this->field->sanitize( '14:30:45' ) );
		$this->assertSame( '09:00', $this->field->sanitize( '09:00' ) );
	}

	/**
	 * Test sanitize returns empty string for invalid time.
	 */
	public function testSanitizeReturnsEmptyForInvalidTime(): void {
		$this->assertSame( '', $this->field->sanitize( 'not-a-time' ) );
		$this->assertSame( '', $this->field->sanitize( '2:30 PM' ) );
		$this->assertSame( '', $this->field->sanitize( '25:00' ) );
	}

	/**
	 * Test sanitize returns empty string for invalid time values.
	 */
	public function testSanitizeReturnsEmptyForInvalidTimeValues(): void {
		// Invalid hour.
		$this->assertSame( '', $this->field->sanitize( '24:00' ) );
		// Invalid minute.
		$this->assertSame( '', $this->field->sanitize( '14:60' ) );
		// Invalid second (when seconds included).
		$this->assertSame( '', $this->field->sanitize( '14:30:60' ) );
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
	 * Test validate passes for valid time.
	 */
	public function testValidatePassesForValidTime(): void {
		$field = [
			'name'  => 'start_time',
			'label' => 'Start Time',
		];

		$result = $this->field->validate( '14:30', $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test validate passes for empty optional field.
	 */
	public function testValidatePassesForEmptyOptionalField(): void {
		$field = [
			'name'     => 'start_time',
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
			'name'     => 'start_time',
			'label'    => 'Start Time',
			'required' => true,
		];

		$result = $this->field->validate( '', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'required', $result->get_error_codes() );
	}

	/**
	 * Test validate fails for invalid time format.
	 */
	public function testValidateFailsForInvalidTimeFormat(): void {
		$field = [
			'name'  => 'start_time',
			'label' => 'Start Time',
		];

		$result = $this->field->validate( 'not-a-time', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'invalid_time', $result->get_error_codes() );
	}

	/**
	 * Test validate fails when time is before min.
	 */
	public function testValidateFailsWhenTimeBeforeMin(): void {
		$field = [
			'name'  => 'start_time',
			'label' => 'Start Time',
			'min'   => '09:00',
		];

		$result = $this->field->validate( '08:30', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'time_too_early', $result->get_error_codes() );
	}

	/**
	 * Test validate fails when time is after max.
	 */
	public function testValidateFailsWhenTimeAfterMax(): void {
		$field = [
			'name'  => 'start_time',
			'label' => 'Start Time',
			'max'   => '17:00',
		];

		$result = $this->field->validate( '18:00', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'time_too_late', $result->get_error_codes() );
	}

	/**
	 * Test validate passes when time is within range.
	 */
	public function testValidatePassesWhenTimeWithinRange(): void {
		$field = [
			'name' => 'start_time',
			'min'  => '09:00',
			'max'  => '17:00',
		];

		$result = $this->field->validate( '12:30', $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test validate passes for boundary times.
	 */
	public function testValidatePassesForBoundaryTimes(): void {
		$field = [
			'name' => 'start_time',
			'min'  => '09:00',
			'max'  => '17:00',
		];

		// Min boundary.
		$this->assertTrue( $this->field->validate( '09:00', $field ) );
		// Max boundary.
		$this->assertTrue( $this->field->validate( '17:00', $field ) );
	}

	/**
	 * Test formatValue formats time correctly.
	 */
	public function testFormatValueFormatsTimeCorrectly(): void {
		// Mock WordPress functions.
		Functions\when( 'get_option' )->justReturn( 'g:i A' );
		Functions\when( 'date_i18n' )->alias( function( $format, $timestamp ) {
			return date( $format, $timestamp );
		} );

		$field = [
			'name'        => 'start_time',
			'time_format' => 'g:i A',
		];

		$result = $this->field->formatValue( '14:30', $field );
		$this->assertSame( '2:30 PM', $result );
	}

	/**
	 * Test formatValue uses configured format.
	 */
	public function testFormatValueUsesConfiguredFormat(): void {
		Functions\when( 'date_i18n' )->alias( function( $format, $timestamp ) {
			return date( $format, $timestamp );
		} );

		$field = [
			'name'        => 'start_time',
			'time_format' => 'H:i:s',
		];

		$result = $this->field->formatValue( '14:30', $field );
		$this->assertSame( '14:30:00', $result );
	}

	/**
	 * Test formatValue returns empty string for empty value.
	 */
	public function testFormatValueReturnsEmptyForEmptyValue(): void {
		$field = [ 'name' => 'start_time' ];

		$this->assertSame( '', $this->field->formatValue( '', $field ) );
		$this->assertSame( '', $this->field->formatValue( null, $field ) );
	}

	/**
	 * Test formatValue returns escaped value for invalid time.
	 */
	public function testFormatValueReturnsEscapedValueForInvalidTime(): void {
		$field = [ 'name' => 'start_time' ];

		$result = $this->field->formatValue( 'invalid-time', $field );
		$this->assertSame( 'invalid-time', $result );
	}

	/**
	 * Test getDefaultValue returns empty string.
	 */
	public function testGetDefaultValueReturnsEmptyString(): void {
		$this->assertSame( '', $this->field->getDefaultValue() );
	}
}
