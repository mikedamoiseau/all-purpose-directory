<?php
/**
 * Tests for image optimization across templates
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;

/**
 * Image Optimization test case
 *
 * Tests that images have proper lazy loading and decoding attributes.
 */
class ImageOptimizationTest extends TestCase {

    /**
     * Template directory path
     *
     * @var string
     */
    private string $templates_dir;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->templates_dir = dirname( __DIR__, 3 ) . '/templates';
    }

    /**
     * Test listing-card.php has lazy loading and decoding attributes
     *
     * @return void
     */
    public function test_listing_card_has_lazy_loading(): void {
        $source = file_get_contents( $this->templates_dir . '/listing-card.php' );

        $this->assertStringContainsString(
            "'loading' => 'lazy'",
            $source,
            'listing-card.php should include loading="lazy" for thumbnails'
        );

        $this->assertStringContainsString(
            "'decoding' => 'async'",
            $source,
            'listing-card.php should include decoding="async" for thumbnails'
        );
    }

    /**
     * Test listing-card-list.php has lazy loading and decoding attributes
     *
     * @return void
     */
    public function test_listing_card_list_has_lazy_loading(): void {
        $source = file_get_contents( $this->templates_dir . '/listing-card-list.php' );

        $this->assertStringContainsString(
            "'loading' => 'lazy'",
            $source,
            'listing-card-list.php should include loading="lazy" for thumbnails'
        );

        $this->assertStringContainsString(
            "'decoding' => 'async'",
            $source,
            'listing-card-list.php should include decoding="async" for thumbnails'
        );
    }

    /**
     * Test dashboard listing row has lazy loading and decoding
     *
     * @return void
     */
    public function test_dashboard_listing_row_has_lazy_loading(): void {
        $source = file_get_contents( $this->templates_dir . '/dashboard/listing-row.php' );

        $this->assertStringContainsString(
            "'loading' => 'lazy'",
            $source,
            'dashboard/listing-row.php should include loading="lazy" for thumbnails'
        );

        $this->assertStringContainsString(
            "'decoding' => 'async'",
            $source,
            'dashboard/listing-row.php should include decoding="async" for thumbnails'
        );
    }

    /**
     * Test review item has lazy loading and decoding for avatars
     *
     * @return void
     */
    public function test_review_item_has_lazy_loading(): void {
        $source = file_get_contents( $this->templates_dir . '/review/review-item.php' );

        $this->assertStringContainsString(
            'loading="lazy"',
            $source,
            'review/review-item.php should include loading="lazy" for avatars'
        );

        $this->assertStringContainsString(
            'decoding="async"',
            $source,
            'review/review-item.php should include decoding="async" for avatars'
        );
    }

    /**
     * Test review avatars have width and height to prevent layout shift
     *
     * @return void
     */
    public function test_review_avatar_has_dimensions(): void {
        $source = file_get_contents( $this->templates_dir . '/review/review-item.php' );

        // Avatar images should have explicit width and height
        $this->assertStringContainsString(
            'width="48"',
            $source,
            'review/review-item.php avatar should have explicit width'
        );

        $this->assertStringContainsString(
            'height="48"',
            $source,
            'review/review-item.php avatar should have explicit height'
        );
    }

    /**
     * Test single listing image is NOT lazy loaded (above the fold)
     *
     * @return void
     */
    public function test_single_listing_main_image_not_lazy(): void {
        $source = file_get_contents( $this->templates_dir . '/single-listing.php' );

        // The main featured image should NOT be lazy loaded since it's above the fold
        // Check that the the_post_thumbnail call doesn't have lazy loading
        $pattern = "/the_post_thumbnail\s*\(\s*'large'\s*,\s*\[.*'loading'\s*=>\s*'lazy'/s";
        $this->assertDoesNotMatchRegularExpression(
            $pattern,
            $source,
            'single-listing.php main image should NOT use lazy loading (above the fold)'
        );
    }
}
