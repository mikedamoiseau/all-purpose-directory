<?php
/**
 * Tests for the Template class.
 *
 * @package APD\Tests\Unit\Core
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Core;

use APD\Core\Template;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Class TemplateTest
 *
 * @covers \APD\Core\Template
 */
class TemplateTest extends TestCase {

	/**
	 * Template instance.
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * Temp theme directory.
	 *
	 * @var string
	 */
	private string $theme_dir;

	/**
	 * Temp child theme directory.
	 *
	 * @var string
	 */
	private string $child_theme_dir;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();

		// Use temp directories for theme mocking.
		$this->theme_dir       = sys_get_temp_dir() . '/apd_theme_' . uniqid();
		$this->child_theme_dir = sys_get_temp_dir() . '/apd_child_theme_' . uniqid();

		@mkdir( $this->theme_dir . '/all-purpose-directory', 0777, true );
		@mkdir( $this->child_theme_dir . '/all-purpose-directory', 0777, true );

		// Mock WordPress theme functions.
		Functions\when( 'get_stylesheet_directory' )->justReturn( $this->child_theme_dir );
		Functions\when( 'get_template_directory' )->justReturn( $this->theme_dir );

		// Always pass through apply_filters.
		Functions\when( 'apply_filters' )->alias( function( $tag, $value ) {
			return $value;
		} );

		// Stub do_action.
		Functions\when( 'do_action' )->justReturn( null );

		// Get fresh template instance.
		$this->resetSingleton();
		$this->template = Template::get_instance();
		$this->template->clear_cache();
	}

	/**
	 * Reset singleton for testing.
	 */
	private function resetSingleton(): void {
		$reflection = new \ReflectionClass( Template::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setValue( null, null );
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		\Brain\Monkey\tearDown();

		// Clean up temp directories.
		$this->recursive_rmdir( $this->theme_dir );
		$this->recursive_rmdir( $this->child_theme_dir );

		parent::tearDown();
	}

	/**
	 * Recursively remove a directory.
	 *
	 * @param string $dir Directory path.
	 */
	private function recursive_rmdir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$files = array_diff( scandir( $dir ), [ '.', '..' ] );
		foreach ( $files as $file ) {
			$path = $dir . '/' . $file;
			if ( is_dir( $path ) ) {
				$this->recursive_rmdir( $path );
			} else {
				@unlink( $path );
			}
		}
		@rmdir( $dir );
	}

	/**
	 * Test singleton instance.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = Template::get_instance();
		$instance2 = Template::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test locate_template finds plugin template.
	 */
	public function test_locate_template_finds_plugin_template(): void {
		$result = $this->template->locate_template( 'search/no-results.php' );

		$expected = APD_PLUGIN_DIR . 'templates/search/no-results.php';
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test locate_template prefers theme template.
	 */
	public function test_locate_template_prefers_theme_template(): void {
		// Create theme template.
		$theme_template = $this->theme_dir . '/all-purpose-directory/theme-test.php';
		file_put_contents( $theme_template, '<?php echo "theme template"; ?>' );

		$result = $this->template->locate_template( 'theme-test.php' );

		$this->assertSame( $theme_template, $result );
	}

	/**
	 * Test locate_template prefers child theme template.
	 */
	public function test_locate_template_prefers_child_theme_template(): void {
		// Create parent theme template.
		$parent_template = $this->theme_dir . '/all-purpose-directory/child-test.php';
		file_put_contents( $parent_template, '<?php echo "parent theme template"; ?>' );

		// Create child theme template.
		$child_template = $this->child_theme_dir . '/all-purpose-directory/child-test.php';
		file_put_contents( $child_template, '<?php echo "child theme template"; ?>' );

		$result = $this->template->locate_template( 'child-test.php' );

		$this->assertSame( $child_template, $result );
	}

	/**
	 * Test locate_template returns false for non-existent template.
	 */
	public function test_locate_template_returns_false_when_not_found(): void {
		$result = $this->template->locate_template( 'nonexistent-' . uniqid() . '.php' );

		$this->assertFalse( $result );
	}

	/**
	 * Test locate_template normalizes leading slashes.
	 */
	public function test_locate_template_normalizes_leading_slashes(): void {
		$result = $this->template->locate_template( '/search/no-results.php' );

		$expected = APD_PLUGIN_DIR . 'templates/search/no-results.php';
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test locate_template caches results.
	 */
	public function test_locate_template_caches_results(): void {
		// First call.
		$result1 = $this->template->locate_template( 'search/no-results.php' );
		// Second call - should use cache (same result).
		$result2 = $this->template->locate_template( 'search/no-results.php' );

		$this->assertSame( $result1, $result2 );
	}

	/**
	 * Test get_template loads template with args.
	 */
	public function test_get_template_loads_template_with_args(): void {
		// Create template that uses args.
		$template_file = $this->theme_dir . '/all-purpose-directory/args-template.php';
		file_put_contents( $template_file, '<?php echo $title . " - " . $count; ?>' );

		ob_start();
		$this->template->get_template( 'args-template.php', [ 'title' => 'Test', 'count' => 42 ] );
		$output = ob_get_clean();

		$this->assertSame( 'Test - 42', $output );
	}

	/**
	 * Test get_template does nothing for non-existent template.
	 */
	public function test_get_template_does_nothing_for_missing_template(): void {
		ob_start();
		$this->template->get_template( 'missing-' . uniqid() . '.php' );
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}

	/**
	 * Test get_template_html returns template as string.
	 */
	public function test_get_template_html_returns_string(): void {
		$template_file = $this->theme_dir . '/all-purpose-directory/html-template.php';
		file_put_contents( $template_file, '<?php echo "HTML content"; ?>' );

		$result = $this->template->get_template_html( 'html-template.php' );

		$this->assertSame( 'HTML content', $result );
	}

	/**
	 * Test get_template_part with slug and name.
	 */
	public function test_get_template_part_with_slug_and_name(): void {
		// Create specialized template.
		$template_file = $this->theme_dir . '/all-purpose-directory/test-card-grid.php';
		file_put_contents( $template_file, '<?php echo "grid card"; ?>' );

		ob_start();
		$this->template->get_template_part( 'test-card', 'grid' );
		$output = ob_get_clean();

		$this->assertSame( 'grid card', $output );
	}

	/**
	 * Test get_template_part falls back to generic template.
	 */
	public function test_get_template_part_falls_back_to_generic(): void {
		// Create only generic template.
		$template_file = $this->theme_dir . '/all-purpose-directory/generic-card.php';
		file_put_contents( $template_file, '<?php echo "generic card"; ?>' );

		ob_start();
		$this->template->get_template_part( 'generic-card', 'special' );
		$output = ob_get_clean();

		$this->assertSame( 'generic card', $output );
	}

	/**
	 * Test get_template_part with slug only.
	 */
	public function test_get_template_part_with_slug_only(): void {
		$template_file = $this->theme_dir . '/all-purpose-directory/slug-only-card.php';
		file_put_contents( $template_file, '<?php echo "card"; ?>' );

		ob_start();
		$this->template->get_template_part( 'slug-only-card' );
		$output = ob_get_clean();

		$this->assertSame( 'card', $output );
	}

	/**
	 * Test get_template_part_html returns string.
	 */
	public function test_get_template_part_html_returns_string(): void {
		$template_file = $this->theme_dir . '/all-purpose-directory/html-part.php';
		file_put_contents( $template_file, '<?php echo "HTML card"; ?>' );

		$result = $this->template->get_template_part_html( 'html-part' );

		$this->assertSame( 'HTML card', $result );
	}

	/**
	 * Test template_exists returns true for existing template.
	 */
	public function test_template_exists_returns_true(): void {
		$result = $this->template->template_exists( 'search/no-results.php' );

		$this->assertTrue( $result );
	}

	/**
	 * Test template_exists returns false for missing template.
	 */
	public function test_template_exists_returns_false(): void {
		$result = $this->template->template_exists( 'missing-' . uniqid() . '.php' );

		$this->assertFalse( $result );
	}

	/**
	 * Test is_template_overridden returns true when in theme.
	 */
	public function test_is_template_overridden_returns_true(): void {
		$theme_template = $this->theme_dir . '/all-purpose-directory/overridden.php';
		file_put_contents( $theme_template, '<?php echo "overridden"; ?>' );

		$result = $this->template->is_template_overridden( 'overridden.php' );

		$this->assertTrue( $result );
	}

	/**
	 * Test is_template_overridden returns true for child theme.
	 */
	public function test_is_template_overridden_returns_true_for_child_theme(): void {
		$child_template = $this->child_theme_dir . '/all-purpose-directory/child-override.php';
		file_put_contents( $child_template, '<?php echo "child override"; ?>' );

		$result = $this->template->is_template_overridden( 'child-override.php' );

		$this->assertTrue( $result );
	}

	/**
	 * Test is_template_overridden returns false when not in theme.
	 */
	public function test_is_template_overridden_returns_false(): void {
		$result = $this->template->is_template_overridden( 'not-overridden-' . uniqid() . '.php' );

		$this->assertFalse( $result );
	}

	/**
	 * Test get_plugin_template_path returns correct path.
	 */
	public function test_get_plugin_template_path(): void {
		$result = $this->template->get_plugin_template_path();

		$this->assertSame( APD_PLUGIN_DIR . 'templates/', $result );
	}

	/**
	 * Test get_theme_template_dir returns correct dir name.
	 */
	public function test_get_theme_template_dir(): void {
		$result = $this->template->get_theme_template_dir();

		$this->assertSame( 'all-purpose-directory/', $result );
	}

	/**
	 * Test clear_cache clears the cache.
	 */
	public function test_clear_cache(): void {
		// Cache a result.
		$this->template->locate_template( 'search/no-results.php' );

		// Clear cache.
		$this->template->clear_cache();

		// Cache is internal, but we can verify the method runs without error.
		$result = $this->template->locate_template( 'search/no-results.php' );

		$this->assertNotFalse( $result );
	}

	/**
	 * Test args are available as $args array in template.
	 */
	public function test_args_available_as_array(): void {
		$template_file = $this->theme_dir . '/all-purpose-directory/array-args.php';
		file_put_contents( $template_file, '<?php echo $args["key"]; ?>' );

		ob_start();
		$this->template->get_template( 'array-args.php', [ 'key' => 'value' ] );
		$output = ob_get_clean();

		$this->assertSame( 'value', $output );
	}

	/**
	 * Test subdirectory templates work.
	 */
	public function test_subdirectory_templates(): void {
		// Create a template in a subdirectory.
		@mkdir( $this->theme_dir . '/all-purpose-directory/subdir', 0777, true );
		$template_file = $this->theme_dir . '/all-purpose-directory/subdir/test.php';
		file_put_contents( $template_file, '<?php echo "subdirectory template"; ?>' );

		$result = $this->template->get_template_html( 'subdir/test.php' );

		$this->assertSame( 'subdirectory template', $result );
	}

	/**
	 * Test get_template with require_once.
	 */
	public function test_get_template_with_require_once(): void {
		$template_file = $this->theme_dir . '/all-purpose-directory/require-once-template.php';
		file_put_contents( $template_file, '<?php echo "loaded"; ?>' );

		ob_start();
		// First load.
		$this->template->get_template( 'require-once-template.php', [], true );
		// Second load with require_once should not output again.
		$this->template->get_template( 'require-once-template.php', [], true );
		$output = ob_get_clean();

		// With require_once, the template should only be included once.
		$this->assertSame( 'loaded', $output );
	}

	/**
	 * Test get_template_part with args.
	 */
	public function test_get_template_part_with_args(): void {
		$template_file = $this->theme_dir . '/all-purpose-directory/part-with-args.php';
		file_put_contents( $template_file, '<?php echo $listing_id; ?>' );

		ob_start();
		$this->template->get_template_part( 'part-with-args', null, [ 'listing_id' => 123 ] );
		$output = ob_get_clean();

		$this->assertSame( '123', $output );
	}
}
