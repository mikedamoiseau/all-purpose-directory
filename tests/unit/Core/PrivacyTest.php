<?php
/**
 * Tests for Privacy class.
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use APD\Core\Privacy;
use APD\Review\RatingCalculator;
use APD\Tests\Unit\UnitTestCase;
use APD\User\Favorites;
use Brain\Monkey\Functions;

/**
 * Privacy test case.
 */
class PrivacyTest extends UnitTestCase {

	/**
	 * Privacy instance under test.
	 *
	 * @var Privacy
	 */
	private Privacy $privacy;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->privacy = new Privacy();
	}

	/**
	 * Override base stubs: Privacy tests mock hooks, meta, and post
	 * functions per-test, so we only set up translation/escaping stubs.
	 *
	 * @return void
	 */
	protected function setUpWordPressFunctions(): void {
		Functions\stubs( [
			'esc_html'       => static fn( $text ) => $text,
			'esc_attr'       => static fn( $text ) => $text,
			'esc_url'        => static fn( $url ) => $url,
			'esc_html__'     => static fn( $text, $domain = 'default' ) => $text,
			'esc_attr__'     => static fn( $text, $domain = 'default' ) => $text,
			'__'             => static fn( $text, $domain = 'default' ) => $text,
			'_e'             => static fn( $text, $domain = 'default' ) => print( $text ),
			'_x'             => static fn( $text, $context, $domain = 'default' ) => $text,
			'esc_html_x'     => static fn( $text, $context, $domain = 'default' ) => $text,
			'esc_attr_x'     => static fn( $text, $context, $domain = 'default' ) => $text,
			'absint'         => static fn( $val ) => abs( (int) $val ),
		] );
	}

	/**
	 * Tear down test environment.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		// Reset singletons that may have been injected with mocks.
		Favorites::reset_instance();
		RatingCalculator::reset_instance();

		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// Hook registration tests
	// -------------------------------------------------------------------------

	/**
	 * Test init registers privacy hooks.
	 *
	 * @return void
	 */
	public function test_init_registers_hooks(): void {
		$registered = [];

		Functions\expect( 'add_action' )
			->andReturnUsing( function ( $hook, $callback ) use ( &$registered ) {
				$registered[] = [ 'action', $hook ];
			} );

		Functions\expect( 'add_filter' )
			->andReturnUsing( function ( $hook, $callback ) use ( &$registered ) {
				$registered[] = [ 'filter', $hook ];
			} );

		$this->privacy->init();

		$this->assertContains( [ 'action', 'admin_init' ], $registered );
		$this->assertContains( [ 'filter', 'wp_privacy_personal_data_exporters' ], $registered );
		$this->assertContains( [ 'filter', 'wp_privacy_personal_data_erasers' ], $registered );
	}

	// -------------------------------------------------------------------------
	// Exporter registration
	// -------------------------------------------------------------------------

	/**
	 * Test register_exporter adds exporter entry.
	 *
	 * @return void
	 */
	public function test_register_exporter_adds_entry(): void {
		$result = $this->privacy->register_exporter( [] );

		$this->assertArrayHasKey( 'all-purpose-directory', $result );
		$this->assertEquals( 'All Purpose Directory', $result['all-purpose-directory']['exporter_friendly_name'] );
		$this->assertIsCallable( $result['all-purpose-directory']['callback'] );
	}

	/**
	 * Test register_exporter preserves existing exporters.
	 *
	 * @return void
	 */
	public function test_register_exporter_preserves_existing(): void {
		$existing = [ 'other-plugin' => [ 'exporter_friendly_name' => 'Other' ] ];
		$result   = $this->privacy->register_exporter( $existing );

		$this->assertArrayHasKey( 'other-plugin', $result );
		$this->assertArrayHasKey( 'all-purpose-directory', $result );
	}

	// -------------------------------------------------------------------------
	// Eraser registration
	// -------------------------------------------------------------------------

	/**
	 * Test register_eraser adds eraser entry.
	 *
	 * @return void
	 */
	public function test_register_eraser_adds_entry(): void {
		$result = $this->privacy->register_eraser( [] );

		$this->assertArrayHasKey( 'all-purpose-directory', $result );
		$this->assertEquals( 'All Purpose Directory', $result['all-purpose-directory']['eraser_friendly_name'] );
		$this->assertIsCallable( $result['all-purpose-directory']['callback'] );
	}

	// -------------------------------------------------------------------------
	// Privacy policy content
	// -------------------------------------------------------------------------

	/**
	 * Test add_policy_content calls wp_add_privacy_policy_content.
	 *
	 * @return void
	 */
	public function test_add_policy_content_registers_content(): void {
		Functions\stubs( [
			'wp_kses_post' => function ( $text ) { return $text; },
		] );

		$captured_content = '';

		Functions\expect( 'wp_add_privacy_policy_content' )
			->once()
			->andReturnUsing( function ( $plugin_name, $content ) use ( &$captured_content ) {
				$captured_content = $content;
			} );

		$this->privacy->add_policy_content();

		$this->assertStringContainsString( 'Directory Listings', $captured_content );
		$this->assertStringContainsString( 'Reviews', $captured_content );
		$this->assertStringContainsString( 'Contact Inquiries', $captured_content );
		$this->assertStringContainsString( 'Favorites', $captured_content );
		$this->assertStringContainsString( 'Profile Data', $captured_content );
		$this->assertStringContainsString( 'apd_guest_favorites', $captured_content );
	}

	// -------------------------------------------------------------------------
	// Export: no user found
	// -------------------------------------------------------------------------

	/**
	 * Test export returns done when no user and no guest data.
	 *
	 * @return void
	 */
	public function test_export_returns_done_for_unknown_email(): void {
		Functions\stubs( [
			'get_user_by'  => false,
			'get_comments' => [],
			'get_posts'    => [],
		] );

		$result = $this->privacy->export_user_data( 'nobody@example.com', 1 );

		$this->assertTrue( $result['done'] );
		$this->assertEmpty( $result['data'] );
	}

	// -------------------------------------------------------------------------
	// Export: profile data
	// -------------------------------------------------------------------------

	/**
	 * Test export includes profile phone on page 1.
	 *
	 * @return void
	 */
	public function test_export_profile_data_on_page_1(): void {
		$user = $this->create_mock_user( 42, 'test@example.com' );
		$this->stub_user_lookup( $user );

		Functions\stubs( [
			'get_posts'    => [],
			'get_comments' => [],
		] );

		Functions\expect( 'get_user_meta' )
			->andReturnUsing( function ( $user_id, $key, $single ) {
				if ( '_apd_phone' === $key ) {
					return '555-1234';
				}
				if ( '_apd_avatar' === $key ) {
					return 0;
				}
				return '';
			} );

		$result = $this->privacy->export_user_data( 'test@example.com', 1 );

		$this->assertNotEmpty( $result['data'] );

		$profile_item = $result['data'][0];
		$this->assertEquals( 'apd-profile', $profile_item['group_id'] );
		$this->assertEquals( 'Directory Profile', $profile_item['group_label'] );

		$names = array_column( $profile_item['data'], 'name' );
		$this->assertContains( 'Phone', $names );
	}

	// -------------------------------------------------------------------------
	// Export: listings
	// -------------------------------------------------------------------------

	/**
	 * Test export includes listings.
	 *
	 * @return void
	 */
	public function test_export_listings(): void {
		$user = $this->create_mock_user( 42, 'test@example.com' );
		$this->stub_user_lookup( $user );
		$this->stub_empty_profile( 42 );

		$listing = $this->create_mock_post( 100, 'Test Listing', 'Description', 'publish' );

		Functions\stubs( [
			'get_comments' => [],
		] );

		Functions\expect( 'get_posts' )
			->andReturnUsing( function ( $args ) use ( $listing ) {
				if ( isset( $args['post_type'] ) && 'apd_listing' === $args['post_type'] ) {
					return [ $listing ];
				}
				return [];
			} );

		Functions\expect( 'get_post_meta' )
			->andReturn( '' );

		$result = $this->privacy->export_user_data( 'test@example.com', 1 );

		$listings = array_filter( $result['data'], fn( $item ) => 'apd-listings' === $item['group_id'] );
		$this->assertCount( 1, $listings );

		$listing_data = array_values( $listings )[0];
		$this->assertEquals( 'apd-listing-100', $listing_data['item_id'] );
	}

	// -------------------------------------------------------------------------
	// Export: pagination
	// -------------------------------------------------------------------------

	/**
	 * Test export returns done=false when batch is full.
	 *
	 * @return void
	 */
	public function test_export_pagination_returns_not_done_when_full_batch(): void {
		$user = $this->create_mock_user( 42, 'test@example.com' );
		$this->stub_user_lookup( $user );
		$this->stub_empty_profile( 42 );

		$listings = [];
		for ( $i = 0; $i < 10; $i++ ) {
			$listings[] = $this->create_mock_post( 100 + $i, "Listing {$i}", '', 'publish' );
		}

		Functions\stubs( [
			'__'            => function ( $text ) { return $text; },
			'get_comments'  => [],
			'get_post_meta' => '',
		] );

		Functions\expect( 'get_posts' )
			->andReturnUsing( function ( $args ) use ( $listings ) {
				if ( isset( $args['post_type'] ) && 'apd_listing' === $args['post_type'] ) {
					return $listings;
				}
				return [];
			} );

		$result = $this->privacy->export_user_data( 'test@example.com', 1 );

		$this->assertFalse( $result['done'] );
	}

	// -------------------------------------------------------------------------
	// Export: reviews
	// -------------------------------------------------------------------------

	/**
	 * Test export includes reviews.
	 *
	 * @return void
	 */
	public function test_export_reviews(): void {
		$user = $this->create_mock_user( 42, 'test@example.com' );
		$this->stub_user_lookup( $user );
		$this->stub_empty_profile( 42 );

		$review = $this->create_mock_comment( 200, 100, 'Great listing!', 'Test User', 'test@example.com', 42 );

		Functions\stubs( [
			'__'        => function ( $text ) { return $text; },
			'get_posts' => [],
		] );

		Functions\expect( 'get_comments' )
			->andReturnUsing( function ( $args ) use ( $review ) {
				if ( isset( $args['user_id'] ) && 42 === $args['user_id'] ) {
					return [ $review ];
				}
				return [];
			} );

		Functions\expect( 'get_the_title' )
			->with( 100 )
			->andReturn( 'Test Listing' );

		Functions\expect( 'get_comment_meta' )
			->with( 200, '_apd_rating', true )
			->andReturn( '5' );

		Functions\expect( 'get_comment_meta' )
			->with( 200, '_apd_review_title', true )
			->andReturn( 'Excellent' );

		$result = $this->privacy->export_user_data( 'test@example.com', 1 );

		$reviews = array_filter( $result['data'], fn( $item ) => 'apd-reviews' === $item['group_id'] );
		$this->assertCount( 1, $reviews );
	}

	// -------------------------------------------------------------------------
	// Export: inquiries sent
	// -------------------------------------------------------------------------

	/**
	 * Test export includes inquiries sent by email.
	 *
	 * @return void
	 */
	public function test_export_inquiries_sent(): void {
		Functions\stubs( [
			'get_user_by'  => false,
			'get_comments' => [],
		] );

		$inquiry = $this->create_mock_post( 300, 'Inquiry about listing', 'Hello there', 'publish' );

		Functions\expect( 'get_posts' )
			->andReturnUsing( function ( $args ) use ( $inquiry ) {
				if ( isset( $args['meta_query'] ) ) {
					return [ $inquiry ];
				}
				return [];
			} );

		Functions\expect( 'get_post_meta' )
			->andReturnUsing( function ( $post_id, $key, $single ) {
				$meta = [
					'_apd_inquiry_listing_id'   => '100',
					'_apd_inquiry_sender_name'  => 'John',
					'_apd_inquiry_sender_email' => 'john@example.com',
					'_apd_inquiry_sender_phone' => '555-9999',
					'_apd_inquiry_subject'      => 'Question',
				];
				return $meta[ $key ] ?? '';
			} );

		Functions\expect( 'get_the_title' )
			->with( 100 )
			->andReturn( 'Test Listing' );

		$result = $this->privacy->export_user_data( 'john@example.com', 1 );

		$sent = array_filter( $result['data'], fn( $item ) => 'apd-inquiries-sent' === $item['group_id'] );
		$this->assertCount( 1, $sent );
	}

	// -------------------------------------------------------------------------
	// Erase: no user found
	// -------------------------------------------------------------------------

	/**
	 * Test erase returns done for unknown email with no guest data.
	 *
	 * @return void
	 */
	public function test_erase_returns_done_for_unknown_email(): void {
		Functions\stubs( [
			'get_user_by'  => false,
			'get_comments' => [],
			'get_posts'    => [],
		] );

		$result = $this->privacy->erase_user_data( 'nobody@example.com', 1 );

		$this->assertTrue( $result['done'] );
		$this->assertFalse( $result['items_removed'] );
		$this->assertFalse( $result['items_retained'] );
		$this->assertEmpty( $result['messages'] );
	}

	// -------------------------------------------------------------------------
	// Erase: profile
	// -------------------------------------------------------------------------

	/**
	 * Test erase deletes profile meta and avatar.
	 *
	 * @return void
	 */
	public function test_erase_profile_deletes_meta_and_avatar(): void {
		$user = $this->create_mock_user( 42, 'test@example.com' );
		$this->stub_user_lookup( $user );
		$this->inject_favorites_mock( 42, [ 1, 2, 3 ], true );

		Functions\stubs( [
			'__'           => function ( $text ) { return $text; },
			'get_comments' => [],
			'get_posts'    => [],
		] );

		Functions\expect( 'get_user_meta' )
			->with( 42, '_apd_avatar', true )
			->andReturn( 99 );

		Functions\expect( 'wp_delete_attachment' )
			->once()
			->with( 99, true );

		Functions\expect( 'metadata_exists' )
			->andReturn( true );

		Functions\expect( 'delete_user_meta' )
			->andReturn( true );

		$result = $this->privacy->erase_user_data( 'test@example.com', 1 );

		$this->assertTrue( $result['items_removed'] );
	}

	// -------------------------------------------------------------------------
	// Erase: listings (anonymize)
	// -------------------------------------------------------------------------

	/**
	 * Test erase anonymizes listings.
	 *
	 * @return void
	 */
	public function test_erase_listings_anonymizes_author_and_contact(): void {
		$user = $this->create_mock_user( 42, 'test@example.com' );
		$this->stub_user_lookup( $user );
		$this->stub_erase_profile_no_data( 42 );

		Functions\stubs( [
			'get_comments' => [],
		] );

		Functions\expect( 'get_posts' )
			->andReturnUsing( function ( $args ) {
				if ( isset( $args['post_type'] ) && 'apd_listing' === $args['post_type'] ) {
					return [ 100 ]; // fields=ids returns int array.
				}
				return [];
			} );

		Functions\expect( 'wp_update_post' )
			->once()
			->with( \Mockery::on( function ( $data ) {
				return 100 === $data['ID'] && 0 === $data['post_author'];
			} ) );

		Functions\expect( 'delete_post_meta' )
			->times( 9 ) // 9 LISTING_CONTACT_META_KEYS.
			->with( 100, \Mockery::type( 'string' ) );

		$result = $this->privacy->erase_user_data( 'test@example.com', 1 );

		$this->assertTrue( $result['items_removed'] );
		$this->assertTrue( $result['items_retained'] );
		$this->assertNotEmpty( $result['messages'] );
	}

	// -------------------------------------------------------------------------
	// Erase: reviews (delete + recalculate)
	// -------------------------------------------------------------------------

	/**
	 * Test erase deletes reviews and recalculates ratings.
	 *
	 * @return void
	 */
	public function test_erase_reviews_deletes_and_recalculates(): void {
		$user = $this->create_mock_user( 42, 'test@example.com' );
		$this->stub_user_lookup( $user );
		$this->stub_erase_profile_no_data( 42 );
		$this->inject_rating_calculator_mock( [ 100 ] );

		$review = $this->create_mock_comment( 200, 100, 'Great!', 'Test User', 'test@example.com', 42 );

		Functions\stubs( [
			'__'        => function ( $text ) { return $text; },
			'get_posts' => [],
		] );

		Functions\expect( 'get_comments' )
			->andReturnUsing( function ( $args ) use ( $review ) {
				if ( isset( $args['author_email'] ) && 'test@example.com' === $args['author_email'] ) {
					return [ $review ];
				}
				if ( isset( $args['user_id'] ) && 42 === $args['user_id'] ) {
					return [ $review ]; // Same review, will be deduplicated.
				}
				return [];
			} );

		Functions\expect( 'wp_delete_comment' )
			->once()
			->with( 200, true );

		$result = $this->privacy->erase_user_data( 'test@example.com', 1 );

		$this->assertTrue( $result['items_removed'] );
	}

	// -------------------------------------------------------------------------
	// Erase: inquiries sent (anonymize)
	// -------------------------------------------------------------------------

	/**
	 * Test erase anonymizes sent inquiries.
	 *
	 * @return void
	 */
	public function test_erase_inquiries_sent_anonymizes_sender(): void {
		$user = $this->create_mock_user( 42, 'test@example.com' );
		$this->stub_user_lookup( $user );
		$this->stub_erase_profile_no_data( 42 );

		Functions\stubs( [
			'get_comments' => [],
		] );

		Functions\expect( 'get_posts' )
			->andReturnUsing( function ( $args ) {
				if ( isset( $args['post_type'] ) && 'apd_inquiry' === $args['post_type'] && isset( $args['meta_query'] ) ) {
					return [ 300 ]; // fields=ids.
				}
				return [];
			} );

		Functions\expect( 'delete_post_meta' )
			->with( 300, '_apd_inquiry_sender_name' )->once();
		Functions\expect( 'delete_post_meta' )
			->with( 300, '_apd_inquiry_sender_email' )->once();
		Functions\expect( 'delete_post_meta' )
			->with( 300, '_apd_inquiry_sender_phone' )->once();

		Functions\expect( 'wp_update_post' )
			->once()
			->with( \Mockery::on( function ( $data ) {
				return 300 === $data['ID'] && '[Anonymized]' === $data['post_title'];
			} ) );

		$result = $this->privacy->erase_user_data( 'test@example.com', 1 );

		$this->assertTrue( $result['items_removed'] );
		$this->assertTrue( $result['items_retained'] );
	}

	// -------------------------------------------------------------------------
	// Erase: inquiries received (delete)
	// -------------------------------------------------------------------------

	/**
	 * Test erase deletes received inquiries.
	 *
	 * @return void
	 */
	public function test_erase_inquiries_received_deletes(): void {
		$user = $this->create_mock_user( 42, 'test@example.com' );
		$this->stub_user_lookup( $user );
		$this->stub_erase_profile_no_data( 42 );

		Functions\stubs( [
			'get_comments' => [],
		] );

		Functions\expect( 'get_posts' )
			->andReturnUsing( function ( $args ) {
				if ( isset( $args['post_type'] ) && 'apd_inquiry' === $args['post_type']
					&& isset( $args['author'] ) && 42 === $args['author'] ) {
					return [ 400 ];
				}
				return [];
			} );

		Functions\expect( 'wp_delete_post' )
			->once()
			->with( 400, true );

		$result = $this->privacy->erase_user_data( 'test@example.com', 1 );

		$this->assertTrue( $result['items_removed'] );
	}

	// -------------------------------------------------------------------------
	// Erase: review deduplication
	// -------------------------------------------------------------------------

	/**
	 * Test erase deduplicates reviews found by both email and user_id.
	 *
	 * @return void
	 */
	public function test_erase_reviews_deduplicates(): void {
		$user = $this->create_mock_user( 42, 'test@example.com' );
		$this->stub_user_lookup( $user );
		$this->stub_erase_profile_no_data( 42 );
		$this->inject_rating_calculator_mock( [ 100 ] );

		$review = $this->create_mock_comment( 200, 100, 'Great!', 'Test', 'test@example.com', 42 );

		Functions\stubs( [
			'__'        => function ( $text ) { return $text; },
			'get_posts' => [],
		] );

		// Both queries return the same review.
		Functions\expect( 'get_comments' )
			->andReturn( [ $review ] );

		// Should only be deleted once despite appearing in both queries.
		Functions\expect( 'wp_delete_comment' )
			->once()
			->with( 200, true );

		$result = $this->privacy->erase_user_data( 'test@example.com', 1 );

		$this->assertTrue( $result['items_removed'] );
	}

	// -------------------------------------------------------------------------
	// Return structure validation
	// -------------------------------------------------------------------------

	/**
	 * Test export return structure matches WordPress API contract.
	 *
	 * @return void
	 */
	public function test_export_return_structure(): void {
		Functions\stubs( [
			'get_user_by'  => false,
			'get_comments' => [],
			'get_posts'    => [],
		] );

		$result = $this->privacy->export_user_data( 'test@example.com', 1 );

		$this->assertArrayHasKey( 'data', $result );
		$this->assertArrayHasKey( 'done', $result );
		$this->assertIsBool( $result['done'] );
		$this->assertIsArray( $result['data'] );
	}

	/**
	 * Test erase return structure matches WordPress API contract.
	 *
	 * @return void
	 */
	public function test_erase_return_structure(): void {
		Functions\stubs( [
			'get_user_by'  => false,
			'get_comments' => [],
			'get_posts'    => [],
		] );

		$result = $this->privacy->erase_user_data( 'test@example.com', 1 );

		$this->assertArrayHasKey( 'items_removed', $result );
		$this->assertArrayHasKey( 'items_retained', $result );
		$this->assertArrayHasKey( 'messages', $result );
		$this->assertArrayHasKey( 'done', $result );
		$this->assertIsBool( $result['items_removed'] );
		$this->assertIsBool( $result['items_retained'] );
		$this->assertIsBool( $result['done'] );
		$this->assertIsArray( $result['messages'] );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Create a mock WP_User object.
	 *
	 * @param int    $id    User ID.
	 * @param string $email User email.
	 * @return \WP_User
	 */
	private function create_mock_user( int $id, string $email ): \WP_User {
		$user             = \Mockery::mock( '\WP_User' );
		$user->ID         = $id;
		$user->user_email = $email;
		return $user;
	}

	/**
	 * Stub get_user_by to return the given user.
	 *
	 * @param \WP_User $user User object.
	 * @return void
	 */
	private function stub_user_lookup( \WP_User $user ): void {
		Functions\expect( 'get_user_by' )
			->with( 'email', $user->user_email )
			->andReturn( $user );
	}

	/**
	 * Stub profile meta as empty for export tests.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	private function stub_empty_profile( int $user_id ): void {
		Functions\expect( 'get_user_meta' )
			->with( $user_id, \Mockery::type( 'string' ), true )
			->andReturn( '' );
	}

	/**
	 * Stub profile erase path to return no data.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	private function stub_erase_profile_no_data( int $user_id ): void {
		Functions\expect( 'get_user_meta' )
			->with( $user_id, '_apd_avatar', true )
			->andReturn( 0 );

		Functions\expect( 'metadata_exists' )
			->andReturn( false );

		$this->inject_favorites_mock( $user_id, [], false );
	}

	/**
	 * Inject a mock Favorites singleton via reflection.
	 *
	 * @param int   $user_id          Expected user ID.
	 * @param int[] $favorites        Favorites to return.
	 * @param bool  $expect_clear     Whether clear() should be called.
	 * @return void
	 */
	private function inject_favorites_mock( int $user_id, array $favorites, bool $expect_clear ): void {
		$mock = \Mockery::mock( Favorites::class );
		$mock->shouldReceive( 'get_favorites' )->with( $user_id )->andReturn( $favorites );
		if ( $expect_clear ) {
			$mock->shouldReceive( 'clear' )->with( $user_id )->once();
		}

		$reflection = new \ReflectionClass( Favorites::class );
		$property   = $reflection->getProperty( 'instance' );
		$property->setValue( null, $mock );
	}

	/**
	 * Inject a mock RatingCalculator singleton via reflection.
	 *
	 * @param int[] $expected_listing_ids Listing IDs expected for recalculate calls.
	 * @return void
	 */
	private function inject_rating_calculator_mock( array $expected_listing_ids ): void {
		$mock = \Mockery::mock( RatingCalculator::class );
		foreach ( $expected_listing_ids as $listing_id ) {
			$mock->shouldReceive( 'recalculate' )->with( $listing_id )->once();
		}

		$reflection = new \ReflectionClass( RatingCalculator::class );
		$property   = $reflection->getProperty( 'instance' );
		$property->setValue( null, $mock );
	}

	/**
	 * Create a mock WP_Post object.
	 *
	 * @param int    $id      Post ID.
	 * @param string $title   Post title.
	 * @param string $content Post content.
	 * @param string $status  Post status.
	 * @return object
	 */
	private function create_mock_post( int $id, string $title, string $content, string $status ): object {
		return (object) [
			'ID'           => $id,
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => $status,
			'post_date'    => '2024-01-01 12:00:00',
			'post_author'  => 42,
		];
	}

	/**
	 * Create a mock WP_Comment object.
	 *
	 * @param int    $id      Comment ID.
	 * @param int    $post_id Post ID.
	 * @param string $content Comment content.
	 * @param string $author  Author name.
	 * @param string $email   Author email.
	 * @param int    $user_id User ID.
	 * @return object
	 */
	private function create_mock_comment( int $id, int $post_id, string $content, string $author, string $email, int $user_id ): object {
		return (object) [
			'comment_ID'           => $id,
			'comment_post_ID'      => $post_id,
			'comment_content'      => $content,
			'comment_author'       => $author,
			'comment_author_email' => $email,
			'comment_date'         => '2024-01-01 12:00:00',
			'user_id'              => $user_id,
		];
	}
}
