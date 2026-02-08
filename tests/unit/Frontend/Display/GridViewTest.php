<?php
/**
 * GridView Unit Tests.
 *
 * @package APD\Tests\Unit\Frontend\Display
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Frontend\Display;

use APD\Frontend\Display\GridView;
use APD\Contracts\ViewInterface;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for GridView.
 */
final class GridViewTest extends UnitTestCase {

	/**
	 * GridView instance.
	 *
	 * @var GridView
	 */
	private GridView $view;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->view = new GridView();
	}

	/**
	 * Test that GridView implements ViewInterface.
	 */
	public function test_implements_view_interface(): void {
		$this->assertInstanceOf( ViewInterface::class, $this->view );
	}

	/**
	 * Test that getType returns 'grid'.
	 */
	public function test_get_type_returns_grid(): void {
		$this->assertSame( 'grid', $this->view->getType() );
	}

	/**
	 * Test that getLabel returns translated label.
	 */
	public function test_get_label_returns_grid(): void {
		$this->assertSame( 'Grid', $this->view->getLabel() );
	}

	/**
	 * Test that getIcon returns correct dashicon.
	 */
	public function test_get_icon_returns_dashicon(): void {
		$this->assertSame( 'dashicons-grid-view', $this->view->getIcon() );
	}

	/**
	 * Test that getTemplate returns correct template name.
	 */
	public function test_get_template_returns_listing_card(): void {
		$this->assertSame( 'listing-card', $this->view->getTemplate() );
	}

	/**
	 * Test default column count.
	 */
	public function test_default_columns_is_three(): void {
		$this->assertSame( 3, $this->view->getColumns() );
	}

	/**
	 * Test setting valid columns.
	 *
	 * @dataProvider valid_columns_provider
	 */
	public function test_set_valid_columns( int $columns ): void {
		$this->view->setColumns( $columns );
		$this->assertSame( $columns, $this->view->getColumns() );
	}

	/**
	 * Provide valid column values.
	 */
	public static function valid_columns_provider(): array {
		return [
			'two columns'   => [ 2 ],
			'three columns' => [ 3 ],
			'four columns'  => [ 4 ],
		];
	}

	/**
	 * Test setting invalid columns keeps default.
	 *
	 * @dataProvider invalid_columns_provider
	 */
	public function test_set_invalid_columns_keeps_default( int $columns ): void {
		$this->view->setColumns( $columns );
		$this->assertSame( 3, $this->view->getColumns() );
	}

	/**
	 * Provide invalid column values.
	 */
	public static function invalid_columns_provider(): array {
		return [
			'zero'     => [ 0 ],
			'one'      => [ 1 ],
			'five'     => [ 5 ],
			'negative' => [ -1 ],
		];
	}

	/**
	 * Test constructor accepts configuration.
	 */
	public function test_constructor_accepts_config(): void {
		$view = new GridView( [ 'columns' => 4 ] );
		$this->assertSame( 4, $view->getColumns() );
	}

	/**
	 * Test setConfig merges with defaults.
	 */
	public function test_set_config_merges_with_defaults(): void {
		$this->view->setConfig( [ 'show_image' => false ] );
		$config = $this->view->getConfig();

		$this->assertFalse( $config['show_image'] );
		$this->assertSame( 3, $config['columns'] ); // Default preserved.
	}

	/**
	 * Test getContainerClasses includes view type and columns.
	 */
	public function test_get_container_classes(): void {
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$classes = $this->view->getContainerClasses();

		$this->assertContains( 'apd-listings', $classes );
		$this->assertContains( 'apd-listings--grid', $classes );
		$this->assertContains( 'apd-listings--columns-3', $classes );
	}

	/**
	 * Test getContainerClasses reflects column changes.
	 */
	public function test_get_container_classes_with_columns(): void {
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$this->view->setColumns( 4 );
		$classes = $this->view->getContainerClasses();

		$this->assertContains( 'apd-listings--columns-4', $classes );
	}

	/**
	 * Test getContainerAttributes includes view and columns.
	 */
	public function test_get_container_attributes(): void {
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$this->view->setColumns( 2 );
		$attributes = $this->view->getContainerAttributes();

		$this->assertSame( 'grid', $attributes['view'] );
		$this->assertSame( '2', $attributes['columns'] );
	}

	/**
	 * Test supports returns true for supported features.
	 *
	 * @dataProvider supported_features_provider
	 */
	public function test_supports_returns_true_for_supported( string $feature ): void {
		$this->assertTrue( $this->view->supports( $feature ) );
	}

	/**
	 * Provide supported features.
	 */
	public static function supported_features_provider(): array {
		return [
			'columns'      => [ 'columns' ],
			'image'        => [ 'image' ],
			'excerpt'      => [ 'excerpt' ],
			'badge'        => [ 'badge' ],
			'hover_effect' => [ 'hover_effect' ],
		];
	}

	/**
	 * Test supports returns false for unsupported features.
	 */
	public function test_supports_returns_false_for_unsupported(): void {
		$this->assertFalse( $this->view->supports( 'tags' ) );
		$this->assertFalse( $this->view->supports( 'date' ) );
		$this->assertFalse( $this->view->supports( 'nonexistent' ) );
	}

	/**
	 * Test getResponsiveColumns returns expected structure.
	 */
	public function test_get_responsive_columns(): void {
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$responsive = $this->view->getResponsiveColumns();

		$this->assertArrayHasKey( 'desktop', $responsive );
		$this->assertArrayHasKey( 'tablet', $responsive );
		$this->assertArrayHasKey( 'mobile', $responsive );
		$this->assertSame( 3, $responsive['desktop'] );
		$this->assertSame( 2, $responsive['tablet'] );
		$this->assertSame( 1, $responsive['mobile'] );
	}

	/**
	 * Test getResponsiveColumns with 4 columns.
	 */
	public function test_get_responsive_columns_with_four(): void {
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$this->view->setColumns( 4 );
		$responsive = $this->view->getResponsiveColumns();

		$this->assertSame( 4, $responsive['desktop'] );
		$this->assertSame( 3, $responsive['tablet'] );
		$this->assertSame( 1, $responsive['mobile'] );
	}

	/**
	 * Test default configuration values.
	 */
	public function test_default_config_values(): void {
		$config = $this->view->getConfig();

		$this->assertSame( 3, $config['columns'] );
		$this->assertTrue( $config['show_image'] );
		$this->assertTrue( $config['show_excerpt'] );
		$this->assertSame( 15, $config['excerpt_length'] );
		$this->assertTrue( $config['show_category'] );
		$this->assertTrue( $config['show_badge'] );
		$this->assertTrue( $config['show_price'] );
		$this->assertTrue( $config['show_rating'] );
		$this->assertTrue( $config['show_favorite'] );
		$this->assertTrue( $config['show_view_details'] );
		$this->assertSame( 'medium', $config['image_size'] );
		$this->assertTrue( $config['card_hover'] );
	}

	/**
	 * Test getConfigValue returns correct value.
	 */
	public function test_get_config_value(): void {
		$this->assertSame( 3, $this->view->getConfigValue( 'columns' ) );
		$this->assertTrue( $this->view->getConfigValue( 'show_image' ) );
	}

	/**
	 * Test getConfigValue returns default for missing key.
	 */
	public function test_get_config_value_default(): void {
		$this->assertNull( $this->view->getConfigValue( 'nonexistent' ) );
		$this->assertSame( 'default', $this->view->getConfigValue( 'nonexistent', 'default' ) );
	}

	/**
	 * Test setConfigValue updates config.
	 */
	public function test_set_config_value(): void {
		$this->view->setConfigValue( 'show_image', false );
		$this->assertFalse( $this->view->getConfigValue( 'show_image' ) );
	}

	/**
	 * Test method chaining with setColumns.
	 */
	public function test_set_columns_returns_self(): void {
		$result = $this->view->setColumns( 4 );
		$this->assertSame( $this->view, $result );
	}

	/**
	 * Test method chaining with setConfig.
	 */
	public function test_set_config_returns_self(): void {
		$result = $this->view->setConfig( [ 'columns' => 2 ] );
		$this->assertSame( $this->view, $result );
	}

	/**
	 * Test method chaining with setConfigValue.
	 */
	public function test_set_config_value_returns_self(): void {
		$result = $this->view->setConfigValue( 'show_image', false );
		$this->assertSame( $this->view, $result );
	}

	// =========================================================================
	// buildRenderArgs Tests
	// =========================================================================

	/**
	 * Test buildRenderArgs returns shared config keys.
	 */
	public function test_build_render_args_returns_shared_keys(): void {
		$ref  = new \ReflectionMethod( $this->view, 'buildRenderArgs' );
		$args = $ref->invoke( $this->view );

		$expected_keys = [
			'show_image',
			'show_excerpt',
			'excerpt_length',
			'show_category',
			'show_price',
			'show_rating',
			'show_favorite',
			'show_view_details',
			'image_size',
		];

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $args, "buildRenderArgs should include '$key'" );
		}
	}

	/**
	 * Test buildRenderArgs uses grid defaults.
	 */
	public function test_build_render_args_uses_grid_defaults(): void {
		$ref  = new \ReflectionMethod( $this->view, 'buildRenderArgs' );
		$args = $ref->invoke( $this->view );

		$this->assertTrue( $args['show_image'] );
		$this->assertSame( 15, $args['excerpt_length'] );
		$this->assertSame( 'medium', $args['image_size'] );
	}

	/**
	 * Test buildRenderArgs reflects config changes.
	 */
	public function test_build_render_args_reflects_config_changes(): void {
		$this->view->setConfigValue( 'show_image', false );
		$this->view->setConfigValue( 'excerpt_length', 25 );

		$ref  = new \ReflectionMethod( $this->view, 'buildRenderArgs' );
		$args = $ref->invoke( $this->view );

		$this->assertFalse( $args['show_image'] );
		$this->assertSame( 25, $args['excerpt_length'] );
	}

	/**
	 * Test buildRenderArgs does not include view-specific keys.
	 */
	public function test_build_render_args_excludes_grid_specific_keys(): void {
		$ref  = new \ReflectionMethod( $this->view, 'buildRenderArgs' );
		$args = $ref->invoke( $this->view );

		// Grid-specific keys should not appear in shared args.
		$this->assertArrayNotHasKey( 'show_badge', $args );
		$this->assertArrayNotHasKey( 'columns', $args );
		$this->assertArrayNotHasKey( 'card_hover', $args );
	}
}
