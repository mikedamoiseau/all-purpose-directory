<?php
/**
 * MyListings Unit Tests.
 *
 * @package APD\Tests\Unit\Frontend\Dashboard
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Frontend\Dashboard;

use APD\Frontend\Dashboard\MyListings;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for MyListings.
 */
final class MyListingsTest extends UnitTestCase {

	/**
	 * Store original $_GET to restore later.
	 *
	 * @var array
	 */
	private array $original_get = [];

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Store and clear $_GET to prevent test pollution.
		$this->original_get = $_GET;
		$_GET               = [];

		// Mock common WordPress functions.
		Functions\stubs( [
			'get_current_user_id'  => 1,
			'is_user_logged_in'    => true,
			'sanitize_key'         => static fn( $key ) => strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', $key ) ),
			'wp_parse_args'        => static fn( $args, $defaults ) => array_merge( $defaults, $args ),
			'absint'               => static fn( $val ) => abs( (int) $val ),
			'wp_create_nonce'      => static fn( $action ) => 'test_nonce_' . $action,
			'wp_verify_nonce'      => static fn( $nonce, $action ) => strpos( $nonce, $action ) !== false,
			'add_query_arg'        => static fn( $args, $url = '' ) => $url . '?' . http_build_query( $args ),
			'remove_query_arg'     => static fn( $keys, $url = '' ) => $url,
			'number_format_i18n'   => static fn( $number ) => number_format( $number ),
			'get_post'             => static fn( $id ) => null,
			'get_post_status'      => static fn( $id ) => 'publish',
			'current_user_can'     => static fn( $cap ) => true,
			'user_can'             => static fn( $user_id, $cap ) => true,
		] );
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		// Restore original $_GET.
		$_GET = $this->original_get;

		// Reset singleton instance using reflection.
		$reflection = new \ReflectionClass( MyListings::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setValue( null, null );

		parent::tearDown();
	}

	/**
	 * Test constructor sets default configuration.
	 */
	public function test_constructor_sets_default_config(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$my_listings = new MyListings();

		$this->assertSame( 0, $my_listings->get_user_id() );
	}

	/**
	 * Test constructor accepts custom configuration.
	 */
	public function test_constructor_accepts_custom_config(): void {
		$config      = [ 'per_page' => 20 ];
		$my_listings = new MyListings( $config );

		// Configuration is internal, but we can verify it works through behavior.
		$this->assertInstanceOf( MyListings::class, $my_listings );
	}

	/**
	 * Test set_user_id and get_user_id.
	 */
	public function test_set_and_get_user_id(): void {
		$my_listings = new MyListings();

		$my_listings->set_user_id( 42 );
		$this->assertSame( 42, $my_listings->get_user_id() );

		$my_listings->set_user_id( 0 );
		$this->assertSame( 0, $my_listings->get_user_id() );
	}

	/**
	 * Test get_status_filter returns valid status.
	 */
	public function test_get_status_filter_returns_all_by_default(): void {
		$my_listings = new MyListings();

		$status = $my_listings->get_status_filter();

		$this->assertSame( 'all', $status );
	}

	/**
	 * Test get_status_filter validates input.
	 */
	public function test_get_status_filter_validates_input(): void {
		$_GET['status'] = 'invalid_status';

		$my_listings = new MyListings();
		$status      = $my_listings->get_status_filter();

		$this->assertSame( 'all', $status );

		unset( $_GET['status'] );
	}

	/**
	 * Test get_status_filter accepts valid statuses.
	 */
	public function test_get_status_filter_accepts_valid_statuses(): void {
		$valid_statuses = [ 'all', 'publish', 'pending', 'draft', 'expired' ];
		$my_listings    = new MyListings();

		foreach ( $valid_statuses as $status ) {
			$_GET['status'] = $status;
			$result         = $my_listings->get_status_filter();
			$this->assertSame( $status, $result );
		}

		unset( $_GET['status'] );
	}

	/**
	 * Test get_orderby_filter returns date by default.
	 */
	public function test_get_orderby_filter_returns_date_by_default(): void {
		$my_listings = new MyListings();

		$orderby = $my_listings->get_orderby_filter();

		$this->assertSame( 'date', $orderby );
	}

	/**
	 * Test get_orderby_filter validates input.
	 */
	public function test_get_orderby_filter_validates_input(): void {
		$_GET['orderby'] = 'invalid_orderby';

		$my_listings = new MyListings();
		$orderby     = $my_listings->get_orderby_filter();

		$this->assertSame( 'date', $orderby );

		unset( $_GET['orderby'] );
	}

	/**
	 * Test get_orderby_filter accepts valid values.
	 */
	public function test_get_orderby_filter_accepts_valid_values(): void {
		$valid_orderby = [ 'date', 'title', 'views' ];
		$my_listings   = new MyListings();

		foreach ( $valid_orderby as $orderby ) {
			$_GET['orderby'] = $orderby;
			$result          = $my_listings->get_orderby_filter();
			$this->assertSame( $orderby, $result );
		}

		unset( $_GET['orderby'] );
	}

	/**
	 * Test get_order_filter returns DESC by default.
	 */
	public function test_get_order_filter_returns_desc_by_default(): void {
		$my_listings = new MyListings();

		$order = $my_listings->get_order_filter();

		$this->assertSame( 'DESC', $order );
	}

	/**
	 * Test get_order_filter validates and normalizes input.
	 */
	public function test_get_order_filter_validates_input(): void {
		$_GET['order'] = 'invalid';

		$my_listings = new MyListings();
		$order       = $my_listings->get_order_filter();

		$this->assertSame( 'DESC', $order );

		unset( $_GET['order'] );
	}

	/**
	 * Test get_order_filter accepts ASC and DESC.
	 */
	public function test_get_order_filter_accepts_asc_and_desc(): void {
		$my_listings = new MyListings();

		$_GET['order'] = 'asc';
		$this->assertSame( 'ASC', $my_listings->get_order_filter() );

		$_GET['order'] = 'DESC';
		$this->assertSame( 'DESC', $my_listings->get_order_filter() );

		unset( $_GET['order'] );
	}

	/**
	 * Test get_current_page returns 1 by default.
	 */
	public function test_get_current_page_returns_1_by_default(): void {
		$my_listings = new MyListings();

		$page = $my_listings->get_current_page();

		$this->assertSame( 1, $page );
	}

	/**
	 * Test get_current_page enforces minimum of 1 for zero values.
	 */
	public function test_get_current_page_enforces_minimum(): void {
		// Test with paged = 0 (should return 1).
		$_GET        = [ 'paged' => '0' ];
		$my_listings = new MyListings();
		$page        = $my_listings->get_current_page();
		$this->assertSame( 1, $page, 'paged=0 should return 1' );

		// Test with empty string (should return 1).
		$_GET        = [ 'paged' => '' ];
		$my_listings = new MyListings();
		$page        = $my_listings->get_current_page();
		$this->assertSame( 1, $page, 'paged="" should return 1' );

		$_GET = [];
	}

	/**
	 * Test get_current_page uses absint for negative values.
	 */
	public function test_get_current_page_uses_absint_for_negative(): void {
		// Negative values are converted via absint (absolute value).
		$_GET        = [ 'paged' => '-5' ];
		$my_listings = new MyListings();
		$page        = $my_listings->get_current_page();
		// absint(-5) = 5, so max(1, 5) = 5.
		$this->assertSame( 5, $page, 'paged=-5 converts to 5 via absint' );

		$_GET = [];
	}

	/**
	 * Test get_current_page parses valid page numbers.
	 */
	public function test_get_current_page_parses_valid_numbers(): void {
		$my_listings = new MyListings();

		$_GET['paged'] = '5';
		$this->assertSame( 5, $my_listings->get_current_page() );

		$_GET['paged'] = '100';
		$this->assertSame( 100, $my_listings->get_current_page() );

		unset( $_GET['paged'] );
	}

	/**
	 * Test get_status_options returns expected structure.
	 */
	public function test_get_status_options_returns_expected_structure(): void {
		Functions\when( '\apd_get_user_listing_stats' )->justReturn( [
			'total'     => 10,
			'published' => 5,
			'pending'   => 2,
			'draft'     => 2,
			'expired'   => 1,
		] );

		$my_listings = new MyListings();
		$options     = $my_listings->get_status_options();

		$this->assertArrayHasKey( 'all', $options );
		$this->assertArrayHasKey( 'publish', $options );
		$this->assertArrayHasKey( 'pending', $options );
		$this->assertArrayHasKey( 'draft', $options );
		$this->assertArrayHasKey( 'expired', $options );

		foreach ( $options as $status => $data ) {
			$this->assertArrayHasKey( 'label', $data );
			$this->assertArrayHasKey( 'count', $data );
			$this->assertIsString( $data['label'] );
			$this->assertIsInt( $data['count'] );
		}
	}

	/**
	 * Test get_orderby_options returns expected options.
	 */
	public function test_get_orderby_options_returns_expected_options(): void {
		$my_listings = new MyListings();
		$options     = $my_listings->get_orderby_options();

		$this->assertArrayHasKey( 'date', $options );
		$this->assertArrayHasKey( 'title', $options );
		$this->assertArrayHasKey( 'views', $options );

		foreach ( $options as $key => $label ) {
			$this->assertIsString( $label );
		}
	}

	/**
	 * Test can_delete_listing returns false for invalid input.
	 */
	public function test_can_delete_listing_returns_false_for_invalid_input(): void {
		$my_listings = new MyListings();

		$this->assertFalse( $my_listings->can_delete_listing( 0 ) );
		$this->assertFalse( $my_listings->can_delete_listing( -1 ) );
		$this->assertFalse( $my_listings->can_delete_listing( 1, 0 ) );
	}

	/**
	 * Test can_edit_listing delegates to helper function.
	 */
	public function test_can_edit_listing_delegates_to_helper(): void {
		Functions\when( '\apd_user_can_edit_listing' )->justReturn( true );

		$my_listings = new MyListings();
		$my_listings->set_user_id( 1 );

		$result = $my_listings->can_edit_listing( 123 );

		$this->assertTrue( $result );
	}

	/**
	 * Test get_action_url generates correct URL.
	 */
	public function test_get_action_url_generates_correct_url(): void {
		$my_listings = new MyListings();

		$url = $my_listings->get_action_url( 123, 'delete' );

		$this->assertStringContainsString( 'apd_action=delete', $url );
		$this->assertStringContainsString( 'listing_id=123', $url );
		$this->assertStringContainsString( '_apd_nonce=', $url );
	}

	/**
	 * Test get_edit_url delegates to helper function.
	 */
	public function test_get_edit_url_delegates_to_helper(): void {
		Functions\when( '\apd_get_edit_listing_url' )->justReturn( 'https://example.com/edit?id=123' );

		$my_listings = new MyListings();

		$url = $my_listings->get_edit_url( 123 );

		$this->assertSame( 'https://example.com/edit?id=123', $url );
	}

	/**
	 * Test get_status_badge returns HTML with correct class.
	 */
	public function test_get_status_badge_returns_html_with_correct_class(): void {
		$my_listings = new MyListings();

		$publish_badge = $my_listings->get_status_badge( 'publish' );
		$this->assertStringContainsString( 'apd-status-badge--success', $publish_badge );
		$this->assertStringContainsString( 'Published', $publish_badge );

		$pending_badge = $my_listings->get_status_badge( 'pending' );
		$this->assertStringContainsString( 'apd-status-badge--warning', $pending_badge );
		$this->assertStringContainsString( 'Pending', $pending_badge );

		$draft_badge = $my_listings->get_status_badge( 'draft' );
		$this->assertStringContainsString( 'apd-status-badge--default', $draft_badge );
		$this->assertStringContainsString( 'Draft', $draft_badge );

		$expired_badge = $my_listings->get_status_badge( 'expired' );
		$this->assertStringContainsString( 'apd-status-badge--error', $expired_badge );
		$this->assertStringContainsString( 'Expired', $expired_badge );
	}

	/**
	 * Test get_status_badge handles unknown status.
	 */
	public function test_get_status_badge_handles_unknown_status(): void {
		$my_listings = new MyListings();

		$unknown_badge = $my_listings->get_status_badge( 'custom_status' );

		$this->assertStringContainsString( 'apd-status-badge--default', $unknown_badge );
		$this->assertStringContainsString( 'Custom_status', $unknown_badge );
	}

	/**
	 * Test get_message returns null when no message.
	 */
	public function test_get_message_returns_null_when_no_message(): void {
		Functions\when( 'get_transient' )->justReturn( false );

		$my_listings = new MyListings();
		$my_listings->set_user_id( 1 );

		$message = $my_listings->get_message();

		$this->assertNull( $message );
	}

	/**
	 * Test get_message returns and clears message.
	 */
	public function test_get_message_returns_and_clears_message(): void {
		$stored_message = [
			'type'    => 'success',
			'message' => 'Test message',
		];

		Functions\when( 'get_transient' )->justReturn( $stored_message );
		Functions\when( 'delete_transient' )->justReturn( true );

		$my_listings = new MyListings();
		$my_listings->set_user_id( 1 );

		$message = $my_listings->get_message();

		$this->assertIsArray( $message );
		$this->assertSame( 'success', $message['type'] );
		$this->assertSame( 'Test message', $message['message'] );
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function test_singleton_returns_same_instance(): void {
		$instance1 = MyListings::get_instance();
		$instance2 = MyListings::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test singleton accepts config updates.
	 */
	public function test_singleton_accepts_config_updates(): void {
		$instance = MyListings::get_instance( [ 'per_page' => 50 ] );

		$this->assertInstanceOf( MyListings::class, $instance );
	}

	/**
	 * Test render returns empty string for no user.
	 */
	public function test_render_returns_empty_for_no_user(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$my_listings = new MyListings();
		$my_listings->set_user_id( 0 ); // Ensure user_id is 0

		$result = $my_listings->render();

		$this->assertSame( '', $result );
	}

	/**
	 * Test constants are defined.
	 */
	public function test_constants_are_defined(): void {
		$this->assertSame( 10, MyListings::PER_PAGE );
		$this->assertSame( 'apd_my_listings_action', MyListings::NONCE_ACTION );
	}

	/**
	 * Test get_listing_actions returns array structure.
	 *
	 * Note: This test requires a WP_Post object which is not available
	 * in unit tests. The method works correctly in integration tests.
	 */
	public function test_get_listing_actions_returns_array(): void {
		$this->markTestIncomplete(
			'This test requires WP_Post class which is not available in unit tests.'
		);
	}

	/**
	 * Test update_listing_status validates status.
	 */
	public function test_update_listing_status_validates_status(): void {
		Functions\when( '\apd_user_can_edit_listing' )->justReturn( true );

		$my_listings = new MyListings();
		$my_listings->set_user_id( 1 );

		// Invalid status should return false.
		$result = $my_listings->update_listing_status( 123, 'invalid_status' );

		$this->assertFalse( $result );
	}

	/**
	 * Test delete_listing checks permission.
	 */
	public function test_delete_listing_returns_false_without_permission(): void {
		// Mock get_post to return a post not owned by the user.
		$post              = new \stdClass();
		$post->ID          = 123;
		$post->post_author = 999; // Different user.
		$post->post_type   = 'apd_listing';

		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'user_can' )->justReturn( false );
		Functions\when( 'apply_filters' )->alias( function( $tag, $value ) {
			return $value;
		} );

		$my_listings = new MyListings();
		$my_listings->set_user_id( 1 );

		$result = $my_listings->delete_listing( 123 );

		$this->assertFalse( $result );
	}
}
