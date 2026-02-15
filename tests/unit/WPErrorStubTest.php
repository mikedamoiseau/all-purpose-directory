<?php
/**
 * WP_Error bootstrap stub tests.
 *
 * @package APD\Tests\Unit
 */

declare(strict_types=1);

namespace APD\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifies the unit-test WP_Error stub behavior used by submission flows.
 */
final class WPErrorStubTest extends TestCase {

	/**
	 * Test merge_from copies messages and data from the source error object.
	 */
	public function test_merge_from_copies_messages_and_data(): void {
		$base = new \WP_Error( 'base_code', 'Base message', [ 'source' => 'base' ] );

		$source = new \WP_Error( 'merged_code', 'Merged message', [ 'source' => 'merged' ] );
		$source->add( 'base_code', 'Second base message' );
		$source->add_data( [ 'source' => 'merged-latest' ], 'base_code' );

		$base->merge_from( $source );

		$this->assertContains( 'base_code', $base->get_error_codes() );
		$this->assertContains( 'merged_code', $base->get_error_codes() );
		$this->assertSame(
			[ 'Base message', 'Second base message' ],
			$base->get_error_messages( 'base_code' )
		);
		$this->assertSame( [ 'source' => 'merged-latest' ], $base->get_error_data( 'base_code' ) );
		$this->assertCount( 2, $base->get_all_error_data( 'base_code' ) );
	}

	/**
	 * Test export_to copies all errors and data to the target error object.
	 */
	public function test_export_to_copies_errors_to_target(): void {
		$source = new \WP_Error( 'code_one', 'Message one', [ 'id' => 1 ] );
		$source->add( 'code_two', 'Message two', [ 'id' => 2 ] );

		$target = new \WP_Error();
		$source->export_to( $target );

		$this->assertSame( [ 'code_one', 'code_two' ], $target->get_error_codes() );
		$this->assertSame( 'Message one', $target->get_error_message( 'code_one' ) );
		$this->assertSame( 'Message two', $target->get_error_message( 'code_two' ) );
		$this->assertSame( [ 'id' => 1 ], $target->get_error_data( 'code_one' ) );
		$this->assertSame( [ 'id' => 2 ], $target->get_error_data( 'code_two' ) );
	}
}
