<?php
/**
 * Tests for listing status transition handling.
 *
 * Verifies that Plugin::handle_listing_status_transition correctly bridges
 * WordPress transition_post_status to the apd_listing_status_changed action.
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use APD\Core\Plugin;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Mockery;

/**
 * Tests for status transition handling.
 */
class StatusTransitionTest extends UnitTestCase {

	/**
	 * Plugin instance created via reflection (bypass singleton constructor).
	 *
	 * @var Plugin
	 */
	private Plugin $plugin;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();

		$reflection   = new \ReflectionClass( Plugin::class );
		$this->plugin = $reflection->newInstanceWithoutConstructor();
	}

	/**
	 * Create a mock WP_Post object.
	 *
	 * @param string $post_type Post type.
	 * @param int    $post_id   Post ID.
	 * @return \WP_Post
	 */
	private function create_mock_post( string $post_type, int $post_id = 1 ): \WP_Post {
		$post            = Mockery::mock( 'WP_Post' );
		$post->post_type = $post_type;
		$post->ID        = $post_id;

		return $post;
	}

	/**
	 * Test that action fires for listing status change.
	 */
	public function test_fires_action_for_listing_status_change(): void {
		$post = $this->create_mock_post( 'apd_listing', 42 );

		Actions\expectDone( 'apd_listing_status_changed' )
			->once()
			->with( 42, 'publish', 'pending' );

		$this->plugin->handle_listing_status_transition( 'publish', 'pending', $post );
	}

	/**
	 * Test that action does not fire for non-listing post types.
	 */
	public function test_skips_non_listing_post_type(): void {
		$post = $this->create_mock_post( 'post', 10 );

		Actions\expectDone( 'apd_listing_status_changed' )->never();

		$this->plugin->handle_listing_status_transition( 'publish', 'draft', $post );
	}

	/**
	 * Test that action does not fire for page post type.
	 */
	public function test_skips_page_post_type(): void {
		$post = $this->create_mock_post( 'page', 20 );

		Actions\expectDone( 'apd_listing_status_changed' )->never();

		$this->plugin->handle_listing_status_transition( 'publish', 'draft', $post );
	}

	/**
	 * Test that action does not fire when status is unchanged.
	 */
	public function test_skips_same_status_transition(): void {
		$post = $this->create_mock_post( 'apd_listing', 42 );

		Actions\expectDone( 'apd_listing_status_changed' )->never();

		$this->plugin->handle_listing_status_transition( 'publish', 'publish', $post );
	}

	/**
	 * Test action fires with correct parameters for pending to publish.
	 */
	public function test_fires_with_correct_params_pending_to_publish(): void {
		$post = $this->create_mock_post( 'apd_listing', 99 );

		Actions\expectDone( 'apd_listing_status_changed' )
			->once()
			->with( 99, 'publish', 'pending' );

		$this->plugin->handle_listing_status_transition( 'publish', 'pending', $post );
	}

	/**
	 * Test action fires for publish to expired transition.
	 */
	public function test_fires_for_publish_to_expired(): void {
		$post = $this->create_mock_post( 'apd_listing', 55 );

		Actions\expectDone( 'apd_listing_status_changed' )
			->once()
			->with( 55, 'expired', 'publish' );

		$this->plugin->handle_listing_status_transition( 'expired', 'publish', $post );
	}

	/**
	 * Test action fires for pending to rejected transition.
	 */
	public function test_fires_for_pending_to_rejected(): void {
		$post = $this->create_mock_post( 'apd_listing', 77 );

		Actions\expectDone( 'apd_listing_status_changed' )
			->once()
			->with( 77, 'rejected', 'pending' );

		$this->plugin->handle_listing_status_transition( 'rejected', 'pending', $post );
	}

	/**
	 * Test action fires for draft to publish transition.
	 */
	public function test_fires_for_draft_to_publish(): void {
		$post = $this->create_mock_post( 'apd_listing', 33 );

		Actions\expectDone( 'apd_listing_status_changed' )
			->once()
			->with( 33, 'publish', 'draft' );

		$this->plugin->handle_listing_status_transition( 'publish', 'draft', $post );
	}
}
