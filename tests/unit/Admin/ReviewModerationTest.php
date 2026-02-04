<?php
/**
 * ReviewModeration Unit Tests.
 *
 * @package APD\Tests\Unit\Admin
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Admin;

use APD\Admin\ReviewModeration;
use APD\Review\ReviewManager;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for ReviewModeration.
 */
final class ReviewModerationTest extends UnitTestCase {

	/**
	 * ReviewModeration instance.
	 *
	 * @var ReviewModeration
	 */
	private ReviewModeration $moderation;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset singletons for clean tests.
		$this->reset_singleton( ReviewModeration::class );
		$this->reset_singleton( ReviewManager::class );

		// Define APD constants if not defined.
		if ( ! defined( 'APD_PLUGIN_URL' ) ) {
			define( 'APD_PLUGIN_URL', 'https://example.com/wp-content/plugins/all-purpose-directory/' );
		}
		if ( ! defined( 'APD_VERSION' ) ) {
			define( 'APD_VERSION', '1.0.0' );
		}

		// Common mock setup.
		Functions\stubs( [
			'is_admin'          => true,
			'current_user_can'  => true,
			'get_current_user_id' => 1,
			'get_post'          => function( $id ) {
				if ( $id <= 0 ) {
					return null;
				}
				$post              = new \stdClass();
				$post->ID          = $id;
				$post->post_type   = 'apd_listing';
				$post->post_title  = 'Test Listing';
				$post->post_status = 'publish';
				return $post;
			},
			'get_edit_post_link' => function( $id ) {
				return 'https://example.com/wp-admin/post.php?post=' . $id . '&action=edit';
			},
			'get_permalink'     => function( $id ) {
				return 'https://example.com/listings/' . $id . '/';
			},
			'admin_url'         => function( $path = '' ) {
				return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
			},
			'add_query_arg'     => function( $args, $url = '' ) {
				if ( is_array( $args ) ) {
					return $url . '?' . http_build_query( $args );
				}
				return $url;
			},
			'remove_query_arg'  => function( $key, $url = '' ) {
				return $url;
			},
			'wp_nonce_url'      => function( $url, $action ) {
				return $url . '&_wpnonce=test_nonce';
			},
			'wp_verify_nonce'   => true,
			'wp_die'            => function( $message ) {
				throw new \Exception( $message );
			},
			'wp_safe_redirect'  => function( $url ) {},
			'wp_nonce_field'    => function( $action, $name ) {
				return '<input type="hidden" name="' . $name . '" value="test_nonce">';
			},
			'wp_kses_post'      => function( $content ) {
				return $content;
			},
			'wp_kses'           => function( $content, $allowed ) {
				return $content;
			},
			'wp_trim_words'     => function( $text, $num_words = 55 ) {
				$words = explode( ' ', $text );
				if ( count( $words ) > $num_words ) {
					return implode( ' ', array_slice( $words, 0, $num_words ) ) . '...';
				}
				return $text;
			},
			'number_format_i18n' => function( $number ) {
				return number_format( (float) $number );
			},
			'selected'          => function( $selected, $current, $echo = true ) {
				$result = $selected == $current ? ' selected="selected"' : '';
				if ( $echo ) {
					echo $result;
				}
				return $result;
			},
			'get_posts'         => function( $args = [] ) {
				return [];
			},
			'get_comments'      => function( $args = [] ) {
				if ( isset( $args['count'] ) && $args['count'] ) {
					return 0;
				}
				return [];
			},
			'get_comment_meta'  => function( $id, $key, $single = false ) {
				if ( $key === '_apd_rating' ) {
					return 5;
				}
				if ( $key === '_apd_review_title' ) {
					return 'Test Title';
				}
				return '';
			},
			'get_option'        => function( $option, $default = false ) {
				if ( $option === 'date_format' ) {
					return 'F j, Y';
				}
				return $default;
			},
			'date_i18n'         => function( $format, $timestamp ) {
				return date( 'F j, Y', $timestamp );
			},
			'wp_set_comment_status' => true,
			'wp_enqueue_style'  => function() {},
			'add_submenu_page'  => function() {},
			'_n'                => function( $single, $plural, $number, $domain = 'default' ) {
				return $number === 1 ? $single : $plural;
			},
			'_n_noop'           => function( $singular, $plural, $domain = null ) {
				return [
					'singular' => $singular,
					'plural'   => $plural,
					'domain'   => $domain,
				];
			},
		] );

		$this->moderation = ReviewModeration::get_instance();
	}

	/**
	 * Reset singleton instance.
	 *
	 * @param string $class_name Class name.
	 */
	private function reset_singleton( string $class_name ): void {
		$reflection = new \ReflectionClass( $class_name );
		$instance   = $reflection->getProperty( 'instance' );
		@$instance->setAccessible( true );
		$instance->setValue( null, null );
	}

	/**
	 * Test singleton pattern returns same instance.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = ReviewModeration::get_instance();
		$instance2 = ReviewModeration::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test constants are defined correctly.
	 */
	public function test_constants_are_defined(): void {
		$this->assertSame( 'apd-reviews', ReviewModeration::PAGE_SLUG );
		$this->assertSame( 'apd_review_moderation', ReviewModeration::NONCE_ACTION );
		$this->assertSame( 'apd_review_nonce', ReviewModeration::NONCE_NAME );
		$this->assertSame( 20, ReviewModeration::PER_PAGE );
		$this->assertSame( 'edit.php?post_type=apd_listing', ReviewModeration::PARENT_MENU );
		$this->assertSame( 'moderate_comments', ReviewModeration::CAPABILITY );
	}

	/**
	 * Test init registers hooks in admin context.
	 */
	public function test_init_registers_hooks_in_admin(): void {
		$hooks_added = [];

		Functions\when( 'add_action' )->alias(
			function( $tag, $callback, $priority = 10, $args = 1 ) use ( &$hooks_added ) {
				$hooks_added[] = [
					'tag'      => $tag,
					'callback' => $callback,
					'priority' => $priority,
				];
			}
		);

		Functions\when( 'add_filter' )->alias(
			function( $tag, $callback, $priority = 10, $args = 1 ) use ( &$hooks_added ) {
				$hooks_added[] = [
					'tag'      => $tag,
					'callback' => $callback,
					'priority' => $priority,
				];
			}
		);

		$this->moderation->init();

		$found_admin_menu       = false;
		$found_admin_init       = false;
		$found_enqueue_scripts  = false;
		$found_menu_classes     = false;

		foreach ( $hooks_added as $hook ) {
			if ( $hook['tag'] === 'admin_menu' ) {
				$found_admin_menu = true;
			}
			if ( $hook['tag'] === 'admin_init' ) {
				$found_admin_init = true;
			}
			if ( $hook['tag'] === 'admin_enqueue_scripts' ) {
				$found_enqueue_scripts = true;
			}
			if ( $hook['tag'] === 'add_menu_classes' ) {
				$found_menu_classes = true;
			}
		}

		$this->assertTrue( $found_admin_menu, 'admin_menu hook should be registered' );
		$this->assertTrue( $found_admin_init, 'admin_init hook should be registered' );
		$this->assertTrue( $found_enqueue_scripts, 'admin_enqueue_scripts hook should be registered' );
		$this->assertTrue( $found_menu_classes, 'add_menu_classes filter should be registered' );
	}

	/**
	 * Test init does nothing outside admin context.
	 */
	public function test_init_does_nothing_outside_admin(): void {
		Functions\when( 'is_admin' )->justReturn( false );

		$hooks_added = [];
		Functions\when( 'add_action' )->alias(
			function( $tag ) use ( &$hooks_added ) {
				$hooks_added[] = $tag;
			}
		);

		$this->reset_singleton( ReviewModeration::class );
		$moderation = ReviewModeration::get_instance();
		$moderation->init();

		$this->assertEmpty( $hooks_added, 'No hooks should be registered outside admin' );
	}

	/**
	 * Test register_admin_page adds submenu page.
	 */
	public function test_register_admin_page_adds_submenu(): void {
		$submenu_args = null;

		Functions\when( 'add_submenu_page' )->alias(
			function( $parent, $page_title, $menu_title, $capability, $slug, $callback ) use ( &$submenu_args ) {
				$submenu_args = [
					'parent'     => $parent,
					'page_title' => $page_title,
					'menu_title' => $menu_title,
					'capability' => $capability,
					'slug'       => $slug,
					'callback'   => $callback,
				];
			}
		);

		$this->moderation->register_admin_page();

		$this->assertNotNull( $submenu_args );
		$this->assertSame( ReviewModeration::PARENT_MENU, $submenu_args['parent'] );
		$this->assertSame( ReviewModeration::PAGE_SLUG, $submenu_args['slug'] );
		$this->assertSame( ReviewModeration::CAPABILITY, $submenu_args['capability'] );
		$this->assertIsCallable( $submenu_args['callback'] );
	}

	/**
	 * Test register_admin_page includes pending count when reviews pending.
	 */
	public function test_register_admin_page_includes_pending_count(): void {
		Functions\when( 'get_comments' )->alias(
			function( $args ) {
				if ( isset( $args['status'] ) && $args['status'] === 'hold' && isset( $args['count'] ) && $args['count'] ) {
					return 5;
				}
				return 0;
			}
		);

		$submenu_args = null;

		Functions\when( 'add_submenu_page' )->alias(
			function( $parent, $page_title, $menu_title, $capability, $slug, $callback ) use ( &$submenu_args ) {
				$submenu_args = [
					'menu_title' => $menu_title,
				];
			}
		);

		$this->moderation->register_admin_page();

		$this->assertStringContainsString( 'awaiting-mod', $submenu_args['menu_title'] );
		$this->assertStringContainsString( '5', $submenu_args['menu_title'] );
	}

	/**
	 * Test get_pending_count returns correct count.
	 */
	public function test_get_pending_count_returns_count(): void {
		Functions\when( 'get_comments' )->alias(
			function( $args ) {
				if ( isset( $args['status'] ) && $args['status'] === 'hold' && isset( $args['count'] ) && $args['count'] ) {
					return 7;
				}
				return 0;
			}
		);

		$count = $this->moderation->get_pending_count();

		$this->assertSame( 7, $count );
	}

	/**
	 * Test get_pending_count queries with correct arguments.
	 */
	public function test_get_pending_count_queries_correctly(): void {
		$query_args = null;

		Functions\when( 'get_comments' )->alias(
			function( $args ) use ( &$query_args ) {
				$query_args = $args;
				return 0;
			}
		);

		$this->moderation->get_pending_count();

		$this->assertSame( ReviewManager::COMMENT_TYPE, $query_args['type'] );
		$this->assertSame( 'hold', $query_args['status'] );
		$this->assertTrue( $query_args['count'] );
	}

	/**
	 * Test get_reviews returns formatted reviews.
	 */
	public function test_get_reviews_returns_formatted_reviews(): void {
		$comment                       = Mockery::mock( \WP_Comment::class );
		$comment->comment_ID           = 123;
		$comment->comment_post_ID      = 456;
		$comment->comment_type         = ReviewManager::COMMENT_TYPE;
		$comment->user_id              = 1;
		$comment->comment_author       = 'Test Author';
		$comment->comment_author_email = 'test@example.com';
		$comment->comment_content      = 'Great listing!';
		$comment->comment_approved     = '1';
		$comment->comment_date         = '2024-01-15 10:30:00';

		$call_count = 0;
		Functions\when( 'get_comments' )->alias(
			function( $args ) use ( &$call_count, $comment ) {
				$call_count++;
				if ( $call_count === 1 ) {
					return [ $comment ];
				}
				return 1; // Count query.
			}
		);

		Functions\when( 'get_comment' )->justReturn( $comment );

		$result = $this->moderation->get_reviews( [
			'status' => 'all',
			'paged'  => 1,
		] );

		$this->assertArrayHasKey( 'reviews', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertArrayHasKey( 'pages', $result );
	}

	/**
	 * Test get_reviews applies status filter.
	 */
	public function test_get_reviews_applies_status_filter(): void {
		$query_args = null;

		Functions\when( 'get_comments' )->alias(
			function( $args ) use ( &$query_args ) {
				$query_args = $args;
				if ( isset( $args['count'] ) && $args['count'] ) {
					return 0;
				}
				return [];
			}
		);

		$this->moderation->get_reviews( [
			'status' => 'pending',
		] );

		$this->assertSame( 'hold', $query_args['status'] );
	}

	/**
	 * Test get_reviews applies listing filter.
	 */
	public function test_get_reviews_applies_listing_filter(): void {
		$query_args = null;

		Functions\when( 'get_comments' )->alias(
			function( $args ) use ( &$query_args ) {
				$query_args = $args;
				if ( isset( $args['count'] ) && $args['count'] ) {
					return 0;
				}
				return [];
			}
		);

		$this->moderation->get_reviews( [
			'listing_id' => 456,
		] );

		$this->assertSame( 456, $query_args['post_id'] );
	}

	/**
	 * Test get_reviews applies rating filter.
	 */
	public function test_get_reviews_applies_rating_filter(): void {
		$query_args = null;

		Functions\when( 'get_comments' )->alias(
			function( $args ) use ( &$query_args ) {
				$query_args = $args;
				if ( isset( $args['count'] ) && $args['count'] ) {
					return 0;
				}
				return [];
			}
		);

		$this->moderation->get_reviews( [
			'rating' => 4,
		] );

		$this->assertIsArray( $query_args['meta_query'] );
		$this->assertSame( ReviewManager::META_RATING, $query_args['meta_query'][0]['key'] );
		$this->assertSame( 4, $query_args['meta_query'][0]['value'] );
	}

	/**
	 * Test get_reviews applies search filter.
	 */
	public function test_get_reviews_applies_search_filter(): void {
		$query_args = null;

		Functions\when( 'get_comments' )->alias(
			function( $args ) use ( &$query_args ) {
				$query_args = $args;
				if ( isset( $args['count'] ) && $args['count'] ) {
					return 0;
				}
				return [];
			}
		);

		$this->moderation->get_reviews( [
			'search' => 'great',
		] );

		$this->assertSame( 'great', $query_args['search'] );
	}

	/**
	 * Test get_reviews calculates pagination correctly.
	 */
	public function test_get_reviews_calculates_pagination(): void {
		$call_count = 0;
		Functions\when( 'get_comments' )->alias(
			function( $args ) use ( &$call_count ) {
				$call_count++;
				if ( $call_count === 1 ) {
					return [];
				}
				return 45; // Total count.
			}
		);

		$result = $this->moderation->get_reviews();

		$this->assertSame( 45, $result['total'] );
		$this->assertSame( 3, $result['pages'] ); // 45 / 20 = 2.25, ceil = 3.
	}

	/**
	 * Test enqueue_styles only loads on correct page.
	 */
	public function test_enqueue_styles_only_on_correct_page(): void {
		$enqueued = false;

		Functions\when( 'wp_enqueue_style' )->alias(
			function() use ( &$enqueued ) {
				$enqueued = true;
			}
		);

		// Wrong page.
		$this->moderation->enqueue_styles( 'edit.php' );
		$this->assertFalse( $enqueued );

		// Correct page.
		$this->moderation->enqueue_styles( 'apd_listing_page_' . ReviewModeration::PAGE_SLUG );
		$this->assertTrue( $enqueued );
	}

	/**
	 * Test add_pending_count_bubble modifies menu.
	 */
	public function test_add_pending_count_bubble_modifies_menu(): void {
		Functions\when( 'get_comments' )->alias(
			function( $args ) {
				if ( isset( $args['status'] ) && $args['status'] === 'hold' && isset( $args['count'] ) && $args['count'] ) {
					return 3;
				}
				return 0;
			}
		);

		$menu = [
			[ 'Listings', 'manage_options', 'edit.php?post_type=apd_listing', '', '' ],
			[ 'Posts', 'manage_options', 'edit.php', '', '' ],
		];

		$result = $this->moderation->add_pending_count_bubble( $menu );

		$this->assertStringContainsString( 'awaiting-mod', $result[0][0] );
		$this->assertStringContainsString( '3', $result[0][0] );
		$this->assertSame( 'Posts', $result[1][0] ); // Other menu items unchanged.
	}

	/**
	 * Test add_pending_count_bubble does nothing when no pending reviews.
	 */
	public function test_add_pending_count_bubble_no_pending(): void {
		Functions\when( 'get_comments' )->justReturn( 0 );

		$menu = [
			[ 'Listings', 'manage_options', 'edit.php?post_type=apd_listing', '', '' ],
		];

		$result = $this->moderation->add_pending_count_bubble( $menu );

		$this->assertSame( $menu, $result );
	}

	/**
	 * Test status translation for approved.
	 */
	public function test_status_translation_approved(): void {
		$query_args = null;

		Functions\when( 'get_comments' )->alias(
			function( $args ) use ( &$query_args ) {
				$query_args = $args;
				if ( isset( $args['count'] ) && $args['count'] ) {
					return 0;
				}
				return [];
			}
		);

		$this->moderation->get_reviews( [ 'status' => 'approved' ] );

		$this->assertSame( 'approve', $query_args['status'] );
	}

	/**
	 * Test status translation for spam.
	 */
	public function test_status_translation_spam(): void {
		$query_args = null;

		Functions\when( 'get_comments' )->alias(
			function( $args ) use ( &$query_args ) {
				$query_args = $args;
				if ( isset( $args['count'] ) && $args['count'] ) {
					return 0;
				}
				return [];
			}
		);

		$this->moderation->get_reviews( [ 'status' => 'spam' ] );

		$this->assertSame( 'spam', $query_args['status'] );
	}

	/**
	 * Test status translation for trash.
	 */
	public function test_status_translation_trash(): void {
		$query_args = null;

		Functions\when( 'get_comments' )->alias(
			function( $args ) use ( &$query_args ) {
				$query_args = $args;
				if ( isset( $args['count'] ) && $args['count'] ) {
					return 0;
				}
				return [];
			}
		);

		$this->moderation->get_reviews( [ 'status' => 'trash' ] );

		$this->assertSame( 'trash', $query_args['status'] );
	}

	/**
	 * Test get_reviews uses correct per page limit.
	 */
	public function test_get_reviews_uses_per_page(): void {
		$first_query_args = null;

		Functions\when( 'get_comments' )->alias(
			function( $args ) use ( &$first_query_args ) {
				// Capture the first call (not the count call).
				if ( ! isset( $args['count'] ) && $first_query_args === null ) {
					$first_query_args = $args;
				}
				if ( isset( $args['count'] ) && $args['count'] ) {
					return 0;
				}
				return [];
			}
		);

		$this->moderation->get_reviews( [ 'paged' => 2 ] );

		$this->assertSame( ReviewModeration::PER_PAGE, $first_query_args['number'] );
		$this->assertSame( ReviewModeration::PER_PAGE, $first_query_args['offset'] );
	}

	/**
	 * Test get_reviews handles empty results.
	 */
	public function test_get_reviews_handles_empty_results(): void {
		Functions\when( 'get_comments' )->alias(
			function( $args ) {
				if ( isset( $args['count'] ) && $args['count'] ) {
					return 0;
				}
				return [];
			}
		);

		$result = $this->moderation->get_reviews();

		$this->assertSame( [], $result['reviews'] );
		$this->assertSame( 0, $result['total'] );
		$this->assertSame( 1, $result['pages'] );
	}

	/**
	 * Test capability constant value.
	 */
	public function test_capability_is_moderate_comments(): void {
		$this->assertSame( 'moderate_comments', ReviewModeration::CAPABILITY );
	}

	/**
	 * Test default query order.
	 */
	public function test_default_query_order(): void {
		$query_args = null;

		Functions\when( 'get_comments' )->alias(
			function( $args ) use ( &$query_args ) {
				$query_args = $args;
				if ( isset( $args['count'] ) && $args['count'] ) {
					return 0;
				}
				return [];
			}
		);

		$this->moderation->get_reviews();

		$this->assertSame( 'comment_date', $query_args['orderby'] );
		$this->assertSame( 'DESC', $query_args['order'] );
	}

	/**
	 * Test get_reviews uses correct comment type.
	 */
	public function test_get_reviews_uses_correct_comment_type(): void {
		$query_args = null;

		Functions\when( 'get_comments' )->alias(
			function( $args ) use ( &$query_args ) {
				$query_args = $args;
				if ( isset( $args['count'] ) && $args['count'] ) {
					return 0;
				}
				return [];
			}
		);

		$this->moderation->get_reviews();

		$this->assertSame( ReviewManager::COMMENT_TYPE, $query_args['type'] );
	}
}
