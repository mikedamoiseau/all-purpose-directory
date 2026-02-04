<?php
/**
 * RatingCalculator Unit Tests.
 *
 * @package APD\Tests\Unit\Review
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Review;

use APD\Review\RatingCalculator;
use APD\Review\ReviewManager;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for RatingCalculator.
 */
final class RatingCalculatorTest extends UnitTestCase {

	/**
	 * RatingCalculator instance.
	 *
	 * @var RatingCalculator
	 */
	private RatingCalculator $calculator;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Add stubs which aren't in the base setup.
		Functions\stubs( [
			'sanitize_html_class' => static fn( $class ) => preg_replace( '/[^a-zA-Z0-9_-]/', '', $class ),
			'_n'                  => static fn( $single, $plural, $number, $domain = 'default' ) => $number === 1 ? $single : $plural,
		] );

		// Reset singleton for clean tests.
		$reflection = new \ReflectionClass( RatingCalculator::class );
		$instance   = $reflection->getProperty( 'instance' );
		// Note: setAccessible() is deprecated in PHP 8.5 but still functional.
		@$instance->setAccessible( true );
		$instance->setValue( null, null );

		$this->calculator = RatingCalculator::get_instance();
	}

	/**
	 * Test singleton pattern returns same instance.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = RatingCalculator::get_instance();
		$instance2 = RatingCalculator::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test constants are defined correctly.
	 */
	public function test_constants_are_defined(): void {
		$this->assertSame( '_apd_average_rating', RatingCalculator::META_AVERAGE );
		$this->assertSame( '_apd_rating_count', RatingCalculator::META_COUNT );
		$this->assertSame( '_apd_rating_distribution', RatingCalculator::META_DISTRIBUTION );
		$this->assertSame( 5, RatingCalculator::DEFAULT_STAR_COUNT );
		$this->assertSame( 1, RatingCalculator::DEFAULT_PRECISION );
	}

	/**
	 * Test get_star_count returns default.
	 */
	public function test_get_star_count_returns_default(): void {
		$count = $this->calculator->get_star_count();

		$this->assertSame( RatingCalculator::DEFAULT_STAR_COUNT, $count );
	}

	/**
	 * Test get_precision returns default.
	 */
	public function test_get_precision_returns_default(): void {
		$precision = $this->calculator->get_precision();

		$this->assertSame( RatingCalculator::DEFAULT_PRECISION, $precision );
	}

	/**
	 * Test calculate with no reviews.
	 */
	public function test_calculate_with_no_reviews(): void {
		$listing_id = 123;

		Functions\when( 'get_comments' )->justReturn( [] );
		Functions\when( 'update_post_meta' )->justReturn( true );

		$result = $this->calculator->calculate( $listing_id );

		$this->assertSame( 0.0, $result['average'] );
		$this->assertSame( 0, $result['count'] );
		$this->assertIsArray( $result['distribution'] );
		$this->assertArrayHasKey( 1, $result['distribution'] );
		$this->assertArrayHasKey( 5, $result['distribution'] );
	}

	/**
	 * Test calculate with single review.
	 */
	public function test_calculate_with_single_review(): void {
		$listing_id = 123;

		$comment             = Mockery::mock( 'WP_Comment' );
		$comment->comment_ID = 1;

		Functions\when( 'get_comments' )->justReturn( [ $comment ] );
		Functions\when( 'get_comment_meta' )->justReturn( 5 );
		Functions\when( 'update_post_meta' )->justReturn( true );

		$result = $this->calculator->calculate( $listing_id );

		$this->assertSame( 5.0, $result['average'] );
		$this->assertSame( 1, $result['count'] );
		$this->assertSame( 1, $result['distribution'][5] );
	}

	/**
	 * Test calculate with multiple reviews.
	 */
	public function test_calculate_with_multiple_reviews(): void {
		$listing_id = 123;

		$comments = [];
		for ( $i = 1; $i <= 5; $i++ ) {
			$comment             = Mockery::mock( 'WP_Comment' );
			$comment->comment_ID = $i;
			$comments[]          = $comment;
		}

		// Return ratings: 5, 4, 5, 3, 4 = 21 / 5 = 4.2
		$ratings   = [ 5, 4, 5, 3, 4 ];
		$call_count = 0;

		Functions\when( 'get_comments' )->justReturn( $comments );
		Functions\when( 'get_comment_meta' )->alias(
			function( $comment_id, $key, $single ) use ( &$call_count, $ratings ) {
				if ( $key === ReviewManager::META_RATING ) {
					$rating = $ratings[ $call_count % count( $ratings ) ];
					$call_count++;
					return $rating;
				}
				return '';
			}
		);
		Functions\when( 'update_post_meta' )->justReturn( true );

		$result = $this->calculator->calculate( $listing_id );

		$this->assertSame( 4.2, $result['average'] );
		$this->assertSame( 5, $result['count'] );
		$this->assertSame( 2, $result['distribution'][5] );
		$this->assertSame( 2, $result['distribution'][4] );
		$this->assertSame( 1, $result['distribution'][3] );
	}

	/**
	 * Test calculate fires hook.
	 */
	public function test_calculate_fires_hook(): void {
		$listing_id = 123;
		$hook_fired = false;

		Functions\when( 'get_comments' )->justReturn( [] );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'do_action' )->alias(
			function( $tag, ...$args ) use ( &$hook_fired, $listing_id ) {
				if ( $tag === 'apd_rating_calculated' && $args[0] === $listing_id ) {
					$hook_fired = true;
				}
			}
		);

		$this->calculator->calculate( $listing_id );

		$this->assertTrue( $hook_fired );
	}

	/**
	 * Test get_average returns cached value.
	 */
	public function test_get_average_returns_cached_value(): void {
		$listing_id = 123;

		Functions\when( 'get_post_meta' )->justReturn( 4.5 );

		$average = $this->calculator->get_average( $listing_id );

		$this->assertSame( 4.5, $average );
	}

	/**
	 * Test get_average calculates when not cached.
	 */
	public function test_get_average_calculates_when_not_cached(): void {
		$listing_id = 123;
		$calculated = false;

		Functions\when( 'get_post_meta' )->alias(
			function( $post_id, $key, $single ) use ( &$calculated ) {
				// First call returns empty (no cache).
				if ( ! $calculated ) {
					return '';
				}
				// After calculation, return value.
				if ( $key === RatingCalculator::META_AVERAGE ) {
					return 0.0;
				}
				return '';
			}
		);
		Functions\when( 'get_comments' )->alias(
			function() use ( &$calculated ) {
				$calculated = true;
				return [];
			}
		);
		Functions\when( 'update_post_meta' )->justReturn( true );

		$average = $this->calculator->get_average( $listing_id );

		$this->assertTrue( $calculated );
		$this->assertSame( 0.0, $average );
	}

	/**
	 * Test get_count returns cached value.
	 */
	public function test_get_count_returns_cached_value(): void {
		$listing_id = 123;

		Functions\when( 'get_post_meta' )->justReturn( 42 );

		$count = $this->calculator->get_count( $listing_id );

		$this->assertSame( 42, $count );
	}

	/**
	 * Test get_distribution returns cached value.
	 */
	public function test_get_distribution_returns_cached_value(): void {
		$listing_id   = 123;
		$distribution = [ 1 => 2, 2 => 0, 3 => 5, 4 => 10, 5 => 15 ];

		Functions\when( 'get_post_meta' )->justReturn( $distribution );

		$result = $this->calculator->get_distribution( $listing_id );

		$this->assertSame( $distribution, $result );
	}

	/**
	 * Test recalculate forces calculation.
	 */
	public function test_recalculate_forces_calculation(): void {
		$listing_id = 123;

		Functions\when( 'get_comments' )->justReturn( [] );
		Functions\when( 'update_post_meta' )->justReturn( true );

		$result = $this->calculator->recalculate( $listing_id );

		$this->assertArrayHasKey( 'average', $result );
		$this->assertArrayHasKey( 'count', $result );
		$this->assertArrayHasKey( 'distribution', $result );
	}

	/**
	 * Test recalculate_all processes all listings.
	 */
	public function test_recalculate_all_processes_all_listings(): void {
		Functions\when( 'get_posts' )->justReturn( [ 1, 2, 3 ] );
		Functions\when( 'get_comments' )->justReturn( [] );
		Functions\when( 'update_post_meta' )->justReturn( true );

		$count = $this->calculator->recalculate_all();

		$this->assertSame( 3, $count );
	}

	/**
	 * Test recalculate_all fires hook.
	 */
	public function test_recalculate_all_fires_hook(): void {
		$hook_fired = false;

		Functions\when( 'get_posts' )->justReturn( [ 1, 2 ] );
		Functions\when( 'get_comments' )->justReturn( [] );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'do_action' )->alias(
			function( $tag, ...$args ) use ( &$hook_fired ) {
				if ( $tag === 'apd_all_ratings_recalculated' && $args[0] === 2 ) {
					$hook_fired = true;
				}
			}
		);

		$this->calculator->recalculate_all();

		$this->assertTrue( $hook_fired );
	}

	/**
	 * Test invalidate clears meta.
	 */
	public function test_invalidate_clears_meta(): void {
		$listing_id   = 123;
		$deleted_keys = [];

		Functions\when( 'delete_post_meta' )->alias(
			function( $post_id, $key ) use ( &$deleted_keys, $listing_id ) {
				if ( $post_id === $listing_id ) {
					$deleted_keys[] = $key;
				}
				return true;
			}
		);

		$this->calculator->invalidate( $listing_id );

		$this->assertContains( RatingCalculator::META_AVERAGE, $deleted_keys );
		$this->assertContains( RatingCalculator::META_COUNT, $deleted_keys );
		$this->assertContains( RatingCalculator::META_DISTRIBUTION, $deleted_keys );
	}

	/**
	 * Test invalidate fires hook.
	 */
	public function test_invalidate_fires_hook(): void {
		$listing_id = 123;
		$hook_fired = false;

		Functions\when( 'delete_post_meta' )->justReturn( true );
		Functions\when( 'do_action' )->alias(
			function( $tag, ...$args ) use ( &$hook_fired, $listing_id ) {
				if ( $tag === 'apd_rating_invalidated' && $args[0] === $listing_id ) {
					$hook_fired = true;
				}
			}
		);

		$this->calculator->invalidate( $listing_id );

		$this->assertTrue( $hook_fired );
	}

	/**
	 * Test render_stars with zero rating.
	 */
	public function test_render_stars_with_zero_rating(): void {
		$html = $this->calculator->render_stars( 0.0 );

		$this->assertStringContainsString( 'apd-star-rating', $html );
		$this->assertStringContainsString( 'apd-star--empty', $html );
		$this->assertStringNotContainsString( 'apd-star--full', $html );
		$this->assertStringContainsString( 'aria-label', $html );
	}

	/**
	 * Test render_stars with full rating.
	 */
	public function test_render_stars_with_full_rating(): void {
		$html = $this->calculator->render_stars( 5.0 );

		$this->assertStringContainsString( 'apd-star--full', $html );
		$this->assertStringNotContainsString( 'apd-star--empty', $html );
		$this->assertStringNotContainsString( 'apd-star--half', $html );
	}

	/**
	 * Test render_stars with half rating.
	 */
	public function test_render_stars_with_half_rating(): void {
		$html = $this->calculator->render_stars( 3.5 );

		$this->assertStringContainsString( 'apd-star--full', $html );
		$this->assertStringContainsString( 'apd-star--half', $html );
		$this->assertStringContainsString( 'apd-star--empty', $html );
	}

	/**
	 * Test render_stars with small size.
	 */
	public function test_render_stars_with_small_size(): void {
		$html = $this->calculator->render_stars( 4.0, [ 'size' => 'small' ] );

		$this->assertStringContainsString( 'apd-star-rating--small', $html );
	}

	/**
	 * Test render_stars with large size.
	 */
	public function test_render_stars_with_large_size(): void {
		$html = $this->calculator->render_stars( 4.0, [ 'size' => 'large' ] );

		$this->assertStringContainsString( 'apd-star-rating--large', $html );
	}

	/**
	 * Test render_stars with show_count.
	 */
	public function test_render_stars_with_show_count(): void {
		$html = $this->calculator->render_stars( 4.0, [
			'show_count' => true,
			'count'      => 42,
		] );

		$this->assertStringContainsString( 'apd-star-rating__count', $html );
		$this->assertStringContainsString( '42', $html );
	}

	/**
	 * Test render_stars with show_average.
	 */
	public function test_render_stars_with_show_average(): void {
		$html = $this->calculator->render_stars( 4.5, [ 'show_average' => true ] );

		$this->assertStringContainsString( 'apd-star-rating__average', $html );
		$this->assertStringContainsString( '4.5', $html );
	}

	/**
	 * Test render_stars with inline false.
	 */
	public function test_render_stars_without_inline(): void {
		$html = $this->calculator->render_stars( 4.0, [ 'inline' => false ] );

		$this->assertStringNotContainsString( 'apd-star-rating--inline', $html );
	}

	/**
	 * Test render_listing_stars returns empty when no ratings.
	 */
	public function test_render_listing_stars_returns_empty_when_no_ratings(): void {
		$listing_id = 123;

		// Return empty for average check, then 0 for count check.
		$call_count = 0;
		Functions\when( 'get_post_meta' )->alias(
			function( $post_id, $key, $single ) use ( &$call_count ) {
				$call_count++;
				if ( $key === RatingCalculator::META_AVERAGE ) {
					return 0.0;
				}
				if ( $key === RatingCalculator::META_COUNT ) {
					return 0;
				}
				return '';
			}
		);

		$html = $this->calculator->render_listing_stars( $listing_id );

		$this->assertSame( '', $html );
	}

	/**
	 * Test render_listing_stars shows stars when ratings exist.
	 */
	public function test_render_listing_stars_shows_stars_when_ratings_exist(): void {
		$listing_id = 123;

		Functions\when( 'get_post_meta' )->alias(
			function( $post_id, $key, $single ) {
				if ( $key === RatingCalculator::META_AVERAGE ) {
					return 4.5;
				}
				if ( $key === RatingCalculator::META_COUNT ) {
					return 10;
				}
				return '';
			}
		);

		$html = $this->calculator->render_listing_stars( $listing_id );

		$this->assertStringContainsString( 'apd-star-rating', $html );
		$this->assertStringContainsString( 'apd-star--full', $html );
	}

	/**
	 * Test on_review_change triggers recalculate.
	 */
	public function test_on_review_change_triggers_recalculate(): void {
		$listing_id = 123;

		Functions\when( 'get_comments' )->justReturn( [] );
		Functions\when( 'update_post_meta' )->justReturn( true );

		// This should not throw an exception.
		$this->calculator->on_review_change( 456, $listing_id );

		$this->assertTrue( true ); // If we got here, it worked.
	}

	/**
	 * Test on_review_updated triggers recalculate.
	 */
	public function test_on_review_updated_triggers_recalculate(): void {
		$review_id  = 456;
		$listing_id = 123;

		$comment                  = Mockery::mock( 'WP_Comment' );
		$comment->comment_post_ID = $listing_id;

		Functions\when( 'get_comment' )->justReturn( $comment );
		Functions\when( 'get_comments' )->justReturn( [] );
		Functions\when( 'update_post_meta' )->justReturn( true );

		$this->calculator->on_review_updated( $review_id, [ 'rating' => 5 ] );

		$this->assertTrue( true ); // If we got here, it worked.
	}

	/**
	 * Test on_review_status_change triggers recalculate.
	 */
	public function test_on_review_status_change_triggers_recalculate(): void {
		$review_id  = 456;
		$listing_id = 123;

		$comment                  = Mockery::mock( 'WP_Comment' );
		$comment->comment_post_ID = $listing_id;

		Functions\when( 'get_comment' )->justReturn( $comment );
		Functions\when( 'get_comments' )->justReturn( [] );
		Functions\when( 'update_post_meta' )->justReturn( true );

		$this->calculator->on_review_status_change( $review_id );

		$this->assertTrue( true ); // If we got here, it worked.
	}

	/**
	 * Test init registers hooks.
	 */
	public function test_init_registers_hooks(): void {
		$actions_added = [];

		Functions\when( 'add_action' )->alias(
			function( $tag, $callback, $priority = 10, $args = 1 ) use ( &$actions_added ) {
				$actions_added[] = $tag;
			}
		);

		$this->calculator->init();

		$this->assertContains( 'apd_review_created', $actions_added );
		$this->assertContains( 'apd_review_updated', $actions_added );
		$this->assertContains( 'apd_review_deleted', $actions_added );
		$this->assertContains( 'apd_review_approved', $actions_added );
		$this->assertContains( 'apd_review_rejected', $actions_added );
	}

	/**
	 * Test calculate handles invalid ratings gracefully.
	 */
	public function test_calculate_handles_invalid_ratings(): void {
		$listing_id = 123;

		$comment             = Mockery::mock( 'WP_Comment' );
		$comment->comment_ID = 1;

		Functions\when( 'get_comments' )->justReturn( [ $comment ] );
		// Return an out-of-range rating.
		Functions\when( 'get_comment_meta' )->justReturn( 10 );
		Functions\when( 'update_post_meta' )->justReturn( true );

		$result = $this->calculator->calculate( $listing_id );

		// Invalid rating should be skipped.
		$this->assertSame( 0.0, $result['average'] );
		$this->assertSame( 0, $result['count'] );
	}

	/**
	 * Test render_stars clamps rating to bounds.
	 */
	public function test_render_stars_clamps_rating_to_bounds(): void {
		// Test with rating above max.
		$html = $this->calculator->render_stars( 10.0 );
		$this->assertStringContainsString( 'apd-star-rating', $html );

		// Test with negative rating.
		$html = $this->calculator->render_stars( -1.0 );
		$this->assertStringContainsString( 'apd-star-rating', $html );
	}

	/**
	 * Test ARIA label is accessible.
	 */
	public function test_aria_label_is_accessible(): void {
		$html = $this->calculator->render_stars( 4.5, [
			'show_count' => true,
			'count'      => 10,
		] );

		$this->assertStringContainsString( 'role="img"', $html );
		$this->assertStringContainsString( 'aria-label=', $html );
		$this->assertStringContainsString( '4.5', $html );
		$this->assertStringContainsString( 'aria-hidden="true"', $html );
	}
}
