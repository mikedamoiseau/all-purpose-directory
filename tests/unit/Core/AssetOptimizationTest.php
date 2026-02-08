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

    // =========================================================================
    // CSS color-mix() Fallback Tests
    // =========================================================================

    /**
     * Test every color-mix() usage in frontend.css has an rgba fallback on the preceding line.
     *
     * Progressive enhancement: browsers that don't support color-mix() use the
     * preceding rgba() value; modern browsers override with color-mix().
     */
    public function test_color_mix_has_rgba_fallback(): void {
        $css_path = dirname( __DIR__, 3 ) . '/assets/css/frontend.css';
        $this->assertFileExists( $css_path );

        $lines = file( $css_path, FILE_IGNORE_NEW_LINES );
        $color_mix_lines = [];

        foreach ( $lines as $index => $line ) {
            $trimmed = ltrim( $line );
            // Skip CSS comments.
            if ( str_starts_with( $trimmed, '/*' ) || str_starts_with( $trimmed, '*' ) || str_starts_with( $trimmed, '//' ) ) {
                continue;
            }
            if ( str_contains( $line, 'color-mix(' ) ) {
                $color_mix_lines[] = $index;
            }
        }

        $this->assertNotEmpty( $color_mix_lines, 'CSS should contain color-mix() usages' );

        foreach ( $color_mix_lines as $line_num ) {
            // The preceding line should contain an rgba() fallback for the same property.
            $preceding = $lines[ $line_num - 1 ] ?? '';

            // Extract the CSS property from the color-mix line.
            preg_match( '/^\s*([\w-]+)\s*:/', $lines[ $line_num ], $prop_match );
            $property = $prop_match[1] ?? '';

            $this->assertNotEmpty(
                $property,
                sprintf( 'Could not extract CSS property from line %d: %s', $line_num + 1, $lines[ $line_num ] )
            );

            // Preceding line should declare the same property with an rgba fallback.
            $this->assertStringContainsString(
                $property,
                $preceding,
                sprintf( 'color-mix() on line %d (%s) should have same-property fallback on preceding line', $line_num + 1, $property )
            );

            $this->assertStringContainsString(
                'rgba(',
                $preceding,
                sprintf( 'color-mix() on line %d should have an rgba() fallback on line %d', $line_num + 1, $line_num )
            );
        }
    }

    // =========================================================================
    // JS Module Guard Check Tests
    // =========================================================================

    /**
     * Test all frontend JS modules have an initialized guard.
     */
    public function test_js_modules_have_initialized_guard(): void {
        $js_path = dirname( __DIR__, 3 ) . '/assets/js/frontend.js';
        $this->assertFileExists( $js_path );

        $content = file_get_contents( $js_path );

        $modules = [
            'APDFilter',
            'APDSubmission',
            'APDMyListings',
            'APDFavorites',
            'APDReviewForm',
            'APDProfile',
            'APDCharCounter',
            'APDContactForm',
        ];

        foreach ( $modules as $module ) {
            // Each module should have an 'initialized' property.
            $this->assertStringContainsString(
                'initialized',
                $this->get_module_source( $content, $module ),
                sprintf( '%s should have an initialized guard flag', $module )
            );
        }
    }

    /**
     * Test all frontend JS modules have DOM guard checks in init().
     */
    public function test_js_modules_have_dom_guard_in_init(): void {
        $js_path = dirname( __DIR__, 3 ) . '/assets/js/frontend.js';
        $content = file_get_contents( $js_path );

        // Modules that should have DOM-element guard checks.
        $dom_guarded_modules = [
            'APDFilter'      => '.apd-search-form',
            'APDSubmission'  => '.apd-submission-form',
            'APDMyListings'  => '.apd-my-listings',
            'APDProfile'     => '.apd-profile-form',
            'APDContactForm' => '.apd-contact-form',
        ];

        foreach ( $dom_guarded_modules as $module => $selector ) {
            $source = $this->get_module_source( $content, $module );
            $this->assertStringContainsString(
                $selector,
                $source,
                sprintf( '%s should query for %s DOM element', $module, $selector )
            );
        }
    }

    /**
     * Extract approximate source of a JS module from the frontend.js content.
     *
     * @param string $content Full JS file content.
     * @param string $module  Module name (e.g., 'APDFilter').
     * @return string Module source section.
     */
    private function get_module_source( string $content, string $module ): string {
        $start = strpos( $content, "const $module = {" );
        if ( $start === false ) {
            return '';
        }

        // Find the next const declaration or end of IIFE as boundary.
        $end = strpos( $content, "\n    const ", $start + 10 );
        if ( $end === false ) {
            $end = strlen( $content );
        }

        return substr( $content, $start, $end - $start );
    }
}
