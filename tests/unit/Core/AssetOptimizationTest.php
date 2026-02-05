<?php
/**
 * Tests for asset optimization
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;

/**
 * Asset Optimization test case
 *
 * Tests that assets are conditionally loaded for performance.
 */
class AssetOptimizationTest extends TestCase {

    /**
     * Source directory path
     *
     * @var string
     */
    private string $src_dir;

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->src_dir = dirname( __DIR__, 3 ) . '/src';
    }

    /**
     * Test Assets class has conditional loading for frontend
     *
     * @return void
     */
    public function test_assets_has_conditional_frontend_loading(): void {
        $source = file_get_contents( $this->src_dir . '/Core/Assets.php' );

        // Should have a method to check if frontend assets should load
        $this->assertStringContainsString(
            'should_load_frontend_assets',
            $source,
            'Assets class should have conditional loading for frontend assets'
        );
    }

    /**
     * Test Assets class checks for post type archive
     *
     * @return void
     */
    public function test_assets_checks_post_type_archive(): void {
        $source = file_get_contents( $this->src_dir . '/Core/Assets.php' );

        // Should check if we're on a listing post type archive
        $this->assertStringContainsString(
            "is_post_type_archive( 'apd_listing' )",
            $source,
            'Assets class should check for listing post type archive'
        );
    }

    /**
     * Test Assets class checks for single listing
     *
     * @return void
     */
    public function test_assets_checks_single_listing(): void {
        $source = file_get_contents( $this->src_dir . '/Core/Assets.php' );

        // Should check if we're on a single listing
        $this->assertStringContainsString(
            "is_singular( 'apd_listing' )",
            $source,
            'Assets class should check for single listing'
        );
    }

    /**
     * Test Assets class checks for taxonomy archives
     *
     * @return void
     */
    public function test_assets_checks_taxonomy_archives(): void {
        $source = file_get_contents( $this->src_dir . '/Core/Assets.php' );

        // Should check for category taxonomy
        $this->assertStringContainsString(
            "is_tax( 'apd_category' )",
            $source,
            'Assets class should check for category taxonomy archive'
        );

        // Should check for tag taxonomy
        $this->assertStringContainsString(
            "is_tax( 'apd_tag' )",
            $source,
            'Assets class should check for tag taxonomy archive'
        );
    }

    /**
     * Test Assets class has conditional loading for admin
     *
     * @return void
     */
    public function test_assets_has_conditional_admin_loading(): void {
        $source = file_get_contents( $this->src_dir . '/Core/Assets.php' );

        // Should have a method to check if admin assets should load
        $this->assertStringContainsString(
            'is_plugin_admin_screen',
            $source,
            'Assets class should have conditional loading for admin assets'
        );
    }

    /**
     * Test Assets class has filter for extending frontend conditions
     *
     * @return void
     */
    public function test_assets_has_extendable_frontend_filter(): void {
        $source = file_get_contents( $this->src_dir . '/Core/Assets.php' );

        // Should have a filter for extending conditions
        $this->assertStringContainsString(
            'apd_should_load_frontend_assets',
            $source,
            'Assets class should have filter for extending frontend asset loading conditions'
        );
    }

    /**
     * Test frontend scripts are loaded in footer
     *
     * @return void
     */
    public function test_frontend_scripts_loaded_in_footer(): void {
        $source = file_get_contents( $this->src_dir . '/Core/Assets.php' );

        // Scripts should be loaded in footer (4th parameter = true)
        // Look for wp_enqueue_script calls with true at the end
        $pattern = "/wp_enqueue_script\s*\([^)]+,\s*true\s*\)/";
        $this->assertMatchesRegularExpression(
            $pattern,
            $source,
            'Frontend scripts should be loaded in footer for performance'
        );
    }

    /**
     * Test Assets uses plugin version for cache busting
     *
     * @return void
     */
    public function test_assets_use_version_for_cache_busting(): void {
        $source = file_get_contents( $this->src_dir . '/Core/Assets.php' );

        // Should use $this->version for scripts and styles
        $this->assertStringContainsString(
            '$this->version',
            $source,
            'Assets should use plugin version for cache busting'
        );
    }
}
