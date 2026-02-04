<?php
/**
 * Unit tests for DateField.
 *
 * Tests date field type rendering, sanitization, and validation.
 *
 * @package APD\Tests\Unit\Fields\Types
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields\Types;

use APD\Fields\Types\DateField;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use WP_Error;

/**
 * Test case for DateField class.
 *
 * @covers \APD\Fields\Types\DateField
 */
class DateFieldTest extends UnitTestCase {

	/**
	 * The field type instance.
	 *
	 * @var DateField
	 */
	private DateField $field;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->field = new DateField();
	}

	/**
	 * Test getType returns 'date'.
	 */
	public function testGetTypeReturnsDate(): void {
		$this->assertSame( 'date', $this->field->getType() );
	}

	/**
	 * Test supports returns correct values.
	 */
	public function testSupportsReturnsCorrectValues(): void {
		$this->assertFalse( $this->field->supports( 'searchable' ) );
		$this->assertTrue( $this->field->supports( 'filterable' ) );
		$this->assertTrue( $this->field->supports( 'sortable' ) );
		$this->assertFalse( $this->field->supports( 'repeater' ) );
	}

	/**
	 * Test render generates correct HTML.
	 */
	public function testRenderGeneratesCorrectHtml(): void {
		$field = [
			'name'  => 'event_date',
			'label' => 'Event Date',
		];

		$html = $this->field->render( $field, '2024-06-15' );

		$this->assertStringContainsString( 'type="date"', $html );
		$this->assertStringContainsString( 'name="apd_field_event_date"', $html );
		$this->assertStringContainsString( 'id="apd-field-event_date"', $html );
		$this->assertStringContainsString( 'value="2024-06-15"', $html );
	}

	/**
	 * Test render includes min/max attributes when configured.
	 */
	public function testRenderIncludesMinMaxAttributes(): void {
		$field = [
			'name' => 'event_date',
			'min'  => '2024-01-01',
			'max'  => '2024-12-31',
		];

		$html = $this->field->render( $field, '' );

		$this->assertStringContainsString( 'min="2024-01-01"', $html );
		$this->assertStringContainsString( 'max="2024-12-31"', $html );
	}

	/**
	 * Test render includes required attributes.
	 */
	public function testRenderIncludesRequiredAttributes(): void {
		$field = [
			'name'     => 'event_date',
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
			'name'        => 'event_date',
			'description' => 'Select the event date',
		];

		$html = $this->field->render( $field, '' );

		$this->assertStringContainsString( 'apd-field-description', $html );
		$this->assertStringContainsString( 'Select the event date', $html );
	}

	/**
	 * Test sanitize returns valid date unchanged.
	 */
	public function testSanitizeReturnsValidDateUnchanged(): void {
		$result = $this->field->sanitize( '2024-06-15' );
		$this->assertSame( '2024-06-15', $result );
	}

	/**
	 * Test sanitize returns empty string for invalid date.
	 */
	public function testSanitizeReturnsEmptyForInvalidDate(): void {
		$this->assertSame( '', $this->field->sanitize( 'not-a-date' ) );
		$this->assertSame( '', $this->field->sanitize( '2024/06/15' ) );
		$this->assertSame( '', $this->field->sanitize( '15-06-2024' ) );
	}

	/**
	 * Test sanitize returns empty string for invalid date values.
	 */
	public function testSanitizeReturnsEmptyForInvalidDateValues(): void {
		// Invalid month.
		$this->assertSame( '', $this->field->sanitize( '2024-13-15' ) );
		// Invalid day.
		$this->assertSame( '', $this->field->sanitize( '2024-06-32' ) );
		// February 30th.
		$this->assertSame( '', $this->field->sanitize( '2024-02-30' ) );
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
	 * Test validate passes for valid date.
	 */
	public function testValidatePassesForValidDate(): void {
		$field = [
			'name'  => 'event_date',
			'label' => 'Event Date',
		];

		$result = $this->field->validate( '2024-06-15', $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test validate passes for empty optional field.
	 */
	public function testValidatePassesForEmptyOptionalField(): void {
		$field = [
			'name'     => 'event_date',
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
			'name'     => 'event_date',
			'label'    => 'Event Date',
			'required' => true,
		];

		$result = $this->field->validate( '', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'required', $result->get_error_codes() );
	}

	/**
	 * Test validate fails for invalid date format.
	 */
	public function testValidateFailsForInvalidDateFormat(): void {
		$field = [
			'name'  => 'event_date',
			'label' => 'Event Date',
		];

		$result = $this->field->validate( 'not-a-date', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'invalid_date', $result->get_error_codes() );
	}

	/**
	 * Test validate fails when date is before min.
	 */
	public function testValidateFailsWhenDateBeforeMin(): void {
		$field = [
			'name'  => 'event_date',
			'label' => 'Event Date',
			'min'   => '2024-06-01',
		];

		$result = $this->field->validate( '2024-05-15', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'date_too_early', $result->get_error_codes() );
	}

	/**
	 * Test validate fails when date is after max.
	 */
	public function testValidateFailsWhenDateAfterMax(): void {
		$field = [
			'name'  => 'event_date',
			'label' => 'Event Date',
			'max'   => '2024-06-30',
		];

		$result = $this->field->validate( '2024-07-15', $field );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertContains( 'date_too_late', $result->get_error_codes() );
	}

	/**
	 * Test validate passes when date is within range.
	 */
	public function testValidatePassesWhenDateWithinRange(): void {
		$field = [
			'name' => 'event_date',
			'min'  => '2024-06-01',
			'max'  => '2024-06-30',
		];

		$result = $this->field->validate( '2024-06-15', $field );
		$this->assertTrue( $result );
	}

	/**
	 * Test formatValue formats date correctly.
	 */
	public function testFormatValueFormatsDateCorrectly(): void {
		// Mock WordPress functions.
		Functions\when( 'get_option' )->justReturn( 'F j, Y' );
		Functions\when( 'date_i18n' )->alias( function( $format, $timestamp ) {
			return date( $format, $timestamp );
		} );

		$field = [
			'name'        => 'event_date',
			'date_format' => 'F j, Y',
		];

		$result = $this->field->formatValue( '2024-06-15', $field );
		$this->assertSame( 'June 15, 2024', $result );
	}

	/**
	 * Test formatValue uses configured format.
	 */
	public function testFormatValueUsesConfiguredFormat(): void {
		Functions\when( 'date_i18n' )->alias( function( $format, $timestamp ) {
			return date( $format, $timestamp );
		} );

		$field = [
			'name'        => 'event_date',
			'date_format' => 'd/m/Y',
		];

		$result = $this->field->formatValue( '2024-06-15', $field );
		$this->assertSame( '15/06/2024', $result );
	}

	/**
	 * Test formatValue returns empty string for empty value.
	 */
	public function testFormatValueReturnsEmptyForEmptyValue(): void {
		$field = [ 'name' => 'event_date' ];

		$this->assertSame( '', $this->field->formatValue( '', $field ) );
		$this->assertSame( '', $this->field->formatValue( null, $field ) );
	}

	/**
	 * Test formatValue returns escaped value for invalid date.
	 */
	public function testFormatValueReturnsEscapedValueForInvalidDate(): void {
		$field = [ 'name' => 'event_date' ];

		$result = $this->field->formatValue( 'invalid-date', $field );
		$this->assertSame( 'invalid-date', $result );
	}

	/**
	 * Test getDefaultValue returns empty string.
	 */
	public function testGetDefaultValueReturnsEmptyString(): void {
		$this->assertSame( '', $this->field->getDefaultValue() );
	}
}
