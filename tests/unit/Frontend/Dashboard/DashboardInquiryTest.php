<?php
/**
 * Dashboard Inquiry Integration Tests.
 *
 * Tests inquiry stats integration in the Dashboard and MyListings classes.
 *
 * @package All_Purpose_Directory\Tests\Unit\Frontend\Dashboard
 */

namespace APD\Tests\Unit\Frontend\Dashboard;

use APD\Frontend\Dashboard\Dashboard;
use APD\Frontend\Dashboard\MyListings;
use APD\Contact\InquiryTracker;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test case for dashboard inquiry integration.
 */
class DashboardInquiryTest extends TestCase {

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Reset singletons.
		$this->reset_singleton( Dashboard::class );
		$this->reset_singleton( MyListings::class );
		$this->reset_singleton( InquiryTracker::class );

		// Mock wp_parse_args to merge arrays.
		Functions\when( 'wp_parse_args' )->alias( function( $args, $defaults ) {
			return array_merge( $defaults, $args );
		} );
	}

	/**
	 * Tear down the test environment.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Reset a singleton instance via reflection.
	 *
	 * @param string $class Class name.
	 */
	private function reset_singleton( string $class ): void {
		$reflection = new \ReflectionClass( $class );
		$property   = $reflection->getProperty( 'instance' );
		@$property->setValue( null, null );
	}

	/**
	 * Test Dashboard empty stats includes inquiry fields.
	 */
	public function test_dashboard_empty_stats_includes_inquiry_fields(): void {
		$dashboard = new Dashboard();

		// Use reflection to call private method.
		$reflection = new \ReflectionClass( $dashboard );
		$method     = $reflection->getMethod( 'get_empty_stats' );

		$stats = $method->invoke( $dashboard );

		$this->assertArrayHasKey( 'inquiries', $stats );
		$this->assertArrayHasKey( 'unread_inquiries', $stats );
		$this->assertEquals( 0, $stats['inquiries'] );
		$this->assertEquals( 0, $stats['unread_inquiries'] );
	}

	/**
	 * Test MyListings default config includes show_inquiries.
	 */
	public function test_my_listings_config_includes_show_inquiries(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		$my_listings = new MyListings();

		// Access config via reflection.
		$reflection = new \ReflectionClass( $my_listings );
		$property   = $reflection->getProperty( 'config' );
		$config     = $property->getValue( $my_listings );

		$this->assertArrayHasKey( 'show_inquiries', $config );
		$this->assertTrue( $config['show_inquiries'] );
	}

	/**
	 * Test MyListings orderby options includes inquiries.
	 */
	public function test_my_listings_orderby_options_includes_inquiries(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( '__' )->returnArg( 1 );

		$my_listings = new MyListings();
		$options     = $my_listings->get_orderby_options();

		$this->assertArrayHasKey( 'inquiries', $options );
	}

	/**
	 * Test MyListings with show_inquiries disabled.
	 */
	public function test_my_listings_show_inquiries_can_be_disabled(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		$my_listings = new MyListings( [ 'show_inquiries' => false ] );

		// Access config via reflection.
		$reflection = new \ReflectionClass( $my_listings );
		$property   = $reflection->getProperty( 'config' );
		$config     = $property->getValue( $my_listings );

		$this->assertArrayHasKey( 'show_inquiries', $config );
		$this->assertFalse( $config['show_inquiries'] );
	}

	/**
	 * Test inquiries is a valid orderby option.
	 */
	public function test_inquiries_in_valid_orderby_options(): void {
		$reflection = new \ReflectionClass( MyListings::class );
		$constant   = $reflection->getConstant( 'VALID_ORDERBY' );

		$this->assertContains( 'inquiries', $constant );
	}

	/**
	 * Test MyListings get_listing_inquiry_count method exists.
	 */
	public function test_my_listings_has_get_listing_inquiry_count_method(): void {
		$this->assertTrue( method_exists( MyListings::class, 'get_listing_inquiry_count' ) );
	}
}
