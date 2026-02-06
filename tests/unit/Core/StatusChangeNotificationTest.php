<?php
/**
 * Status change notification tests.
 *
 * Verifies that listing status transitions fire the apd_listing_status_changed
 * action with correct parameters, and that EmailManager handlers respond
 * appropriately.
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use APD\Core\Plugin;
use APD\Email\EmailManager;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Test class for status change notification flow.
 */
final class StatusChangeNotificationTest extends TestCase {

	use MockeryPHPUnitIntegration;

	/**
	 * Plugin instance (created without constructor).
	 *
	 * @var Plugin
	 */
	private Plugin $plugin;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		EmailManager::reset_instance();

		// Minimal stubs - do NOT stub do_action so Brain Monkey can intercept it.
		Functions\stubs( [
			'esc_html'       => static fn( $text ) => $text,
			'esc_attr'       => static fn( $text ) => $text,
			'esc_url'        => static fn( $url ) => $url,
			'esc_html__'     => static fn( $text, $domain = 'default' ) => $text,
			'__'             => static fn( $text, $domain = 'default' ) => $text,
			'sanitize_key'   => static fn( $key ) => preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ),
			'wp_parse_args'  => static function ( $args, $defaults = [] ) {
				if ( is_object( $args ) ) {
					$args = get_object_vars( $args );
				}
				return array_merge( $defaults, $args );
			},
			'add_action'     => null,
			'add_filter'     => null,
			'has_action'     => false,
			'has_filter'     => false,
		] );

		// Create Plugin instance without running constructor.
		$reflection   = new \ReflectionClass( Plugin::class );
		$this->plugin = $reflection->newInstanceWithoutConstructor();
	}

	/**
	 * Tear down.
	 */
	protected function tearDown(): void {
		EmailManager::reset_instance();
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Create a mock WP_Post object.
	 *
	 * @param array $overrides Property overrides.
	 * @return \WP_Post
	 */
	private function create_listing_post( array $overrides = [] ): \WP_Post {
		$defaults = [
			'ID'          => 123,
			'post_type'   => 'apd_listing',
			'post_status' => 'publish',
			'post_author' => 1,
			'post_title'  => 'Test Listing',
		];

		return new \WP_Post( array_merge( $defaults, $overrides ) );
	}

	/**
	 * Get a fresh EmailManager instance configured for testing.
	 *
	 * @return EmailManager
	 */
	private function get_test_email_manager(): EmailManager {
		Functions\stubs( [
			'get_bloginfo'  => fn( $show ) => $show === 'name' ? 'Test Site' : 'https://example.com',
			'home_url'      => 'https://example.com',
			'wp_date'       => fn( $format ) => gmdate( $format ),
			'apply_filters' => fn( $hook, $value ) => $value,
			'get_option'    => function ( $option ) {
				if ( $option === 'admin_email' ) {
					return 'admin@example.com';
				}
				if ( $option === 'date_format' ) {
					return 'F j, Y';
				}
				if ( $option === 'time_format' ) {
					return 'g:i a';
				}
				return '';
			},
		] );

		EmailManager::reset_instance();
		$manager = EmailManager::get_instance();
		$manager->set_config( [ 'use_templates' => false ] );

		return $manager;
	}

	/**
	 * Set up common mocks for listing email sending.
	 *
	 * @param string $status Post status for the mock listing.
	 */
	private function mock_listing_for_email( string $status = 'publish' ): void {
		$listing               = new \stdClass();
		$listing->ID           = 123;
		$listing->post_title   = 'Test Listing';
		$listing->post_author  = 1;
		$listing->post_status  = $status;
		$listing->post_type    = 'apd_listing';

		$user               = new \stdClass();
		$user->display_name = 'Author';
		$user->user_email   = 'author@example.com';

		Functions\expect( 'get_post' )->once()->andReturn( $listing );
		Functions\expect( 'get_userdata' )->once()->andReturn( $user );
		Functions\expect( 'get_permalink' )->once()->andReturn( 'https://example.com/listing/test/' );
		Functions\expect( 'get_edit_post_link' )->once()->andReturn( 'https://example.com/wp-admin/post.php?post=123' );
		Functions\expect( 'admin_url' )->once()->andReturn( 'https://example.com/wp-admin/' );
	}

	// ========================================================================
	// Plugin::handle_listing_status_transition tests
	// ========================================================================

	/**
	 * Test handle_listing_status_transition fires apd_listing_status_changed.
	 */
	public function test_transition_fires_status_changed_action(): void {
		$post = $this->create_listing_post();

		Actions\expectDone( 'apd_listing_status_changed' )
			->once()
			->with( 123, 'publish', 'pending' );

		$this->plugin->handle_listing_status_transition( 'publish', 'pending', $post );
	}

	/**
	 * Test transition does not fire for non-listing post types.
	 */
	public function test_transition_ignores_non_listing_post_types(): void {
		$post            = $this->create_listing_post();
		$post->post_type = 'post';

		Actions\expectDone( 'apd_listing_status_changed' )->never();

		$this->plugin->handle_listing_status_transition( 'publish', 'pending', $post );
	}

	/**
	 * Test transition does not fire when status does not change.
	 */
	public function test_transition_ignores_same_status(): void {
		$post = $this->create_listing_post();

		Actions\expectDone( 'apd_listing_status_changed' )->never();

		$this->plugin->handle_listing_status_transition( 'publish', 'publish', $post );
	}

	/**
	 * Test transition fires with correct parameters for rejection.
	 */
	public function test_transition_fires_for_rejection(): void {
		$post = $this->create_listing_post( [ 'post_status' => 'rejected' ] );

		Actions\expectDone( 'apd_listing_status_changed' )
			->once()
			->with( 123, 'rejected', 'pending' );

		$this->plugin->handle_listing_status_transition( 'rejected', 'pending', $post );
	}

	/**
	 * Test transition fires with correct parameters for expiration.
	 */
	public function test_transition_fires_for_expiration(): void {
		$post = $this->create_listing_post( [ 'post_status' => 'expired' ] );

		Actions\expectDone( 'apd_listing_status_changed' )
			->once()
			->with( 123, 'expired', 'publish' );

		$this->plugin->handle_listing_status_transition( 'expired', 'publish', $post );
	}

	// ========================================================================
	// EmailManager::on_listing_status_changed tests
	// ========================================================================

	/**
	 * Test EmailManager sends approved email when status changes to publish.
	 */
	public function test_email_manager_sends_approved_on_publish(): void {
		$manager = $this->get_test_email_manager();
		$this->mock_listing_for_email( 'publish' );

		Functions\expect( 'wp_mail' )->once()->andReturn( true );

		$manager->on_listing_status_changed( 123, 'publish', 'pending' );
	}

	/**
	 * Test EmailManager does not send approved email when notification is disabled.
	 */
	public function test_email_manager_skips_approved_when_disabled(): void {
		$manager = $this->get_test_email_manager();
		$manager->set_notification_enabled( 'listing_approved', false );

		Functions\expect( 'wp_mail' )->never();

		$manager->on_listing_status_changed( 123, 'publish', 'pending' );
	}

	/**
	 * Test EmailManager sends rejected email on rejection.
	 */
	public function test_email_manager_sends_rejected_on_rejection(): void {
		$manager = $this->get_test_email_manager();
		$this->mock_listing_for_email( 'rejected' );

		Functions\expect( 'wp_mail' )->once()->andReturn( true );

		$manager->on_listing_status_changed( 123, 'rejected', 'pending' );
	}

	/**
	 * Test EmailManager sends expired email on expiration.
	 */
	public function test_email_manager_sends_expired_on_expiration(): void {
		$manager = $this->get_test_email_manager();
		$this->mock_listing_for_email( 'expired' );

		Functions\expect( 'wp_mail' )->once()->andReturn( true );

		$manager->on_listing_status_changed( 123, 'expired', 'publish' );
	}

	/**
	 * Test that publish-to-publish does not trigger approval email.
	 */
	public function test_email_manager_ignores_publish_to_publish(): void {
		$manager = $this->get_test_email_manager();

		Functions\expect( 'wp_mail' )->never();

		$manager->on_listing_status_changed( 123, 'publish', 'publish' );
	}

	/**
	 * Test that draft-to-pending does not trigger any email.
	 */
	public function test_email_manager_ignores_draft_to_pending(): void {
		$manager = $this->get_test_email_manager();

		Functions\expect( 'wp_mail' )->never();

		$manager->on_listing_status_changed( 123, 'pending', 'draft' );
	}

	/**
	 * Test approved email is sent to the listing author, not admin.
	 */
	public function test_approved_email_sent_to_author(): void {
		$manager = $this->get_test_email_manager();

		$listing               = new \stdClass();
		$listing->ID           = 123;
		$listing->post_title   = 'Test Listing';
		$listing->post_author  = 1;
		$listing->post_status  = 'publish';
		$listing->post_type    = 'apd_listing';

		$user               = new \stdClass();
		$user->display_name = 'Author';
		$user->user_email   = 'author@example.com';

		Functions\expect( 'get_post' )->once()->andReturn( $listing );
		Functions\expect( 'get_userdata' )->once()->andReturn( $user );
		Functions\expect( 'get_permalink' )->once()->andReturn( 'https://example.com/listing/' );
		Functions\expect( 'get_edit_post_link' )->once()->andReturn( 'https://example.com/edit/' );
		Functions\expect( 'admin_url' )->once()->andReturn( 'https://example.com/wp-admin/' );

		$to_received = null;
		Functions\expect( 'wp_mail' )
			->once()
			->andReturnUsing( function ( $to ) use ( &$to_received ) {
				$to_received = $to;
				return true;
			} );

		$manager->on_listing_status_changed( 123, 'publish', 'pending' );

		$this->assertSame( 'author@example.com', $to_received );
	}
}
