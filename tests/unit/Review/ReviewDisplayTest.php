<?php
/**
 * ReviewDisplay Unit Tests.
 *
 * @package APD\Tests\Unit\Review
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Review;

use APD\Review\ReviewDisplay;
use APD\Review\ReviewManager;
use APD\Review\RatingCalculator;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for ReviewDisplay.
 */
final class ReviewDisplayTest extends UnitTestCase {

	/**
	 * ReviewDisplay instance.
	 *
	 * @var ReviewDisplay
	 */
	private ReviewDisplay $display;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset singletons.
		$this->reset_singleton( ReviewDisplay::class );
		$this->reset_singleton( ReviewManager::class );
		$this->reset_singleton( RatingCalculator::class );

		// Common mock setup.
		Functions\stubs( [
			'wp_parse_args'       => function( $args, $defaults ) {
				return array_merge( $defaults, $args );
			},
			'apply_filters'       => function( $hook, $value, ...$args ) {
				return $value;
			},
			'do_action'           => function() {},
			'add_action'          => function() {},
			'add_filter'          => function() {},
			'get_permalink'       => 'http://example.com/listing/123',
			'add_query_arg'       => function( $key, $value, $url = '' ) {
				if ( is_array( $key ) ) {
					$url   = $value;
					$value = reset( $key );
					$key   = key( $key );
				}
				return $url . '?' . $key . '=' . $value;
			},
			'remove_query_arg'    => function( $key, $url = '' ) {
				return $url;
			},
			'absint'              => function( $value ) {
				return abs( (int) $value );
			},
			'__'                  => function( $text ) {
				return $text;
			},
			'esc_html__'          => function( $text ) {
				return $text;
			},
			'esc_attr__'          => function( $text ) {
				return $text;
			},
			'_n'                  => function( $single, $plural, $count ) {
				return $count === 1 ? $single : $plural;
			},
		] );

		$this->display = ReviewDisplay::get_instance();
	}

	/**
	 * Reset a singleton instance for testing.
	 *
	 * @param string $class_name Fully qualified class name.
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
		$instance1 = ReviewDisplay::get_instance();
		$instance2 = ReviewDisplay::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test constants are defined.
	 */
	public function test_constants_are_defined(): void {
		$this->assertSame( 10, ReviewDisplay::DEFAULT_PER_PAGE );
		$this->assertSame( 'review_page', ReviewDisplay::PAGE_PARAM );
	}

	/**
	 * Test get_per_page returns default value.
	 */
	public function test_get_per_page_returns_default(): void {
		$result = $this->display->get_per_page();

		$this->assertSame( ReviewDisplay::DEFAULT_PER_PAGE, $result );
	}

	/**
	 * Test get_per_page respects filter.
	 */
	public function test_get_per_page_respects_filter(): void {
		Functions\stubs( [
			'apply_filters' => function( $hook, $value ) {
				if ( $hook === 'apd_reviews_per_page' ) {
					return 20;
				}
				return $value;
			},
		] );

		$result = $this->display->get_per_page();

		$this->assertSame( 20, $result );
	}

	/**
	 * Test get_current_page returns 1 when no page param.
	 */
	public function test_get_current_page_returns_1_when_no_param(): void {
		$_GET = [];

		$result = $this->display->get_current_page();

		$this->assertSame( 1, $result );
	}

	/**
	 * Test get_current_page returns page from query string.
	 */
	public function test_get_current_page_returns_from_query_string(): void {
		$_GET[ ReviewDisplay::PAGE_PARAM ] = '3';

		$result = $this->display->get_current_page();

		$this->assertSame( 3, $result );

		// Clean up.
		unset( $_GET[ ReviewDisplay::PAGE_PARAM ] );
	}

	/**
	 * Test get_current_page returns 1 for zero.
	 */
	public function test_get_current_page_returns_1_for_zero(): void {
		$_GET[ ReviewDisplay::PAGE_PARAM ] = '0';

		$result = $this->display->get_current_page();

		$this->assertSame( 1, $result );

		// Clean up.
		unset( $_GET[ ReviewDisplay::PAGE_PARAM ] );
	}

	/**
	 * Test get_current_page returns 1 for negative values.
	 */
	public function test_get_current_page_returns_1_for_negative(): void {
		// absint() converts negative numbers to positive.
		$_GET[ ReviewDisplay::PAGE_PARAM ] = '-5';

		$result = $this->display->get_current_page();

		// absint('-5') returns 5, so max(1, 5) = 5.
		$this->assertSame( 5, $result );

		// Clean up.
		unset( $_GET[ ReviewDisplay::PAGE_PARAM ] );
	}

	/**
	 * Test build_pagination_url for page 1.
	 */
	public function test_build_pagination_url_for_page_1(): void {
		$result = $this->display->build_pagination_url( 'http://example.com/listing/123', 1 );

		$this->assertSame( 'http://example.com/listing/123', $result );
	}

	/**
	 * Test build_pagination_url for page greater than 1.
	 */
	public function test_build_pagination_url_for_page_greater_than_1(): void {
		$result = $this->display->build_pagination_url( 'http://example.com/listing/123', 3 );

		$this->assertStringContainsString( ReviewDisplay::PAGE_PARAM, $result );
		$this->assertStringContainsString( '3', $result );
	}

	/**
	 * Test init method exists and is callable.
	 */
	public function test_init_is_callable(): void {
		$this->assertTrue( method_exists( $this->display, 'init' ) );
	}

	/**
	 * Test render method exists and is callable.
	 */
	public function test_render_is_callable(): void {
		$this->assertTrue( method_exists( $this->display, 'render' ) );
	}

	/**
	 * Test render_summary method exists and is callable.
	 */
	public function test_render_summary_is_callable(): void {
		$this->assertTrue( method_exists( $this->display, 'render_summary' ) );
	}

	/**
	 * Test render_reviews_list method exists and is callable.
	 */
	public function test_render_reviews_list_is_callable(): void {
		$this->assertTrue( method_exists( $this->display, 'render_reviews_list' ) );
	}

	/**
	 * Test render_single_review method exists and is callable.
	 */
	public function test_render_single_review_is_callable(): void {
		$this->assertTrue( method_exists( $this->display, 'render_single_review' ) );
	}

	/**
	 * Test render_pagination method exists and is callable.
	 */
	public function test_render_pagination_is_callable(): void {
		$this->assertTrue( method_exists( $this->display, 'render_pagination' ) );
	}

	/**
	 * Test render_reviews_section method exists and is callable.
	 */
	public function test_render_reviews_section_is_callable(): void {
		$this->assertTrue( method_exists( $this->display, 'render_reviews_section' ) );
	}

	/**
	 * Test render_meta_rating method exists and is callable.
	 */
	public function test_render_meta_rating_is_callable(): void {
		$this->assertTrue( method_exists( $this->display, 'render_meta_rating' ) );
	}

	/**
	 * Test render_pagination returns empty for single page.
	 */
	public function test_render_pagination_returns_empty_for_single_page(): void {
		$result = $this->display->render_pagination( 123, 1, 1 );

		$this->assertSame( '', $result );
	}

	/**
	 * Test render_single_review with avatar URL.
	 */
	public function test_render_single_review_handles_author_avatar(): void {
		$review = [
			'id'             => 1,
			'listing_id'     => 123,
			'author_id'      => 1,
			'author_name'    => 'Test User',
			'author_email'   => 'test@example.com',
			'rating'         => 5,
			'title'          => 'Great listing',
			'content'        => 'This is a great listing.',
			'status'         => 'approved',
			'date'           => '2024-01-15 10:00:00',
			'date_formatted' => 'January 15, 2024',
		];

		Functions\expect( 'get_avatar_url' )
			->once()
			->andReturn( 'http://example.com/avatar.jpg' );

		Functions\expect( 'apd_get_template' )
			->once()
			->with(
				'review/review-item.php',
				Mockery::type( 'array' )
			);

		$this->display->render_single_review( $review );
	}

	/**
	 * Test render_single_review with guest (no author_id).
	 */
	public function test_render_single_review_handles_guest_author(): void {
		$review = [
			'id'             => 1,
			'listing_id'     => 123,
			'author_id'      => 0, // Guest.
			'author_name'    => 'Guest User',
			'author_email'   => 'guest@example.com',
			'rating'         => 4,
			'title'          => '',
			'content'        => 'Nice place!',
			'status'         => 'approved',
			'date'           => '2024-01-15 10:00:00',
			'date_formatted' => 'January 15, 2024',
		];

		Functions\expect( 'get_avatar_url' )
			->once()
			->with( 'guest@example.com', Mockery::type( 'array' ) )
			->andReturn( 'http://example.com/avatar.jpg' );

		Functions\expect( 'apd_get_template' )
			->once();

		$this->display->render_single_review( $review );
	}

	/**
	 * Test render_summary calls template.
	 */
	public function test_render_summary_calls_template(): void {
		// Mock RatingCalculator methods.
		$this->reset_singleton( RatingCalculator::class );

		Functions\stubs( [
			'get_post_meta' => function( $post_id, $key, $single ) {
				if ( strpos( $key, 'average' ) !== false ) {
					return 4.5;
				}
				if ( strpos( $key, 'count' ) !== false ) {
					return 10;
				}
				if ( strpos( $key, 'distribution' ) !== false ) {
					return [ 1 => 0, 2 => 1, 3 => 2, 4 => 3, 5 => 4 ];
				}
				return '';
			},
		] );

		Functions\expect( 'apd_get_template' )
			->once()
			->with(
				'review/rating-summary.php',
				Mockery::type( 'array' )
			);

		$this->display->render_summary( 123 );
	}

	/**
	 * Test render_reviews_list shows empty state when no reviews.
	 */
	public function test_render_reviews_list_shows_empty_state(): void {
		$this->reset_singleton( ReviewManager::class );

		// First call for fetching reviews, second for count.
		Functions\stubs( [
			'get_comments' => function( $args ) {
				if ( ! empty( $args['count'] ) ) {
					return 0; // Total count.
				}
				return []; // Empty reviews array.
			},
		] );

		Functions\expect( 'apd_get_template' )
			->once()
			->with(
				'review/reviews-empty.php',
				Mockery::type( 'array' )
			);

		$this->display->render_reviews_list( 123 );
	}

	/**
	 * Test render_meta_rating shows nothing when no reviews.
	 */
	public function test_render_meta_rating_empty_when_no_reviews(): void {
		$this->reset_singleton( RatingCalculator::class );

		Functions\stubs( [
			'get_post_meta' => function( $post_id, $key, $single ) {
				if ( strpos( $key, 'count' ) !== false ) {
					return 0;
				}
				return '';
			},
		] );

		ob_start();
		$this->display->render_meta_rating( 123 );
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}

	/**
	 * Test render method passes correct args to template.
	 */
	public function test_render_passes_correct_args(): void {
		$this->reset_singleton( RatingCalculator::class );
		$this->reset_singleton( ReviewManager::class );

		Functions\stubs( [
			'get_post_meta' => function( $post_id, $key, $single ) {
				if ( strpos( $key, 'count' ) !== false ) {
					return 5;
				}
				if ( strpos( $key, 'average' ) !== false ) {
					return 4.2;
				}
				return '';
			},
			'get_comments' => function( $args ) {
				if ( ! empty( $args['count'] ) ) {
					return 5; // Total count.
				}
				return []; // Empty reviews array.
			},
		] );

		Functions\expect( 'apd_get_template' )
			->once()
			->with(
				'review/reviews-section.php',
				Mockery::on( function( $args ) {
					return isset( $args['listing_id'] ) &&
						   isset( $args['review_count'] ) &&
						   isset( $args['current_page'] ) &&
						   isset( $args['total_pages'] ) &&
						   isset( $args['reviews'] ) &&
						   isset( $args['args'] ) &&
						   isset( $args['has_reviews'] );
				} )
			);

		$this->display->render( 123 );
	}
}
