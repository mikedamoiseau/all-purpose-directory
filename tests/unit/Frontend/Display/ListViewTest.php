<?php
/**
 * ListView Unit Tests.
 *
 * @package APD\Tests\Unit\Frontend\Display
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Frontend\Display;

use APD\Frontend\Display\ListView;
use APD\Contracts\ViewInterface;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for ListView.
 */
final class ListViewTest extends UnitTestCase {

	/**
	 * ListView instance.
	 *
	 * @var ListView
	 */
	private ListView $view;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Mock apd_get_option used in ListView constructor to read admin settings.
		Functions\when( 'apd_get_option' )->alias( function ( $key, $default = null ) {
			return $default;
		} );

		$this->view = new ListView();
	}

	/**
	 * Test that ListView implements ViewInterface.
	 */
	public function test_implements_view_interface(): void {
		$this->assertInstanceOf( ViewInterface::class, $this->view );
	}

	/**
	 * Test that getType returns 'list'.
	 */
	public function test_get_type_returns_list(): void {
		$this->assertSame( 'list', $this->view->getType() );
	}

	/**
	 * Test that getLabel returns translated label.
	 */
	public function test_get_label_returns_list(): void {
		$this->assertSame( 'List', $this->view->getLabel() );
	}

	/**
	 * Test that getIcon returns correct dashicon.
	 */
	public function test_get_icon_returns_dashicon(): void {
		$this->assertSame( 'dashicons-list-view', $this->view->getIcon() );
	}

	/**
	 * Test that getTemplate returns correct template name.
	 */
	public function test_get_template_returns_listing_card_list(): void {
		$this->assertSame( 'listing-card-list', $this->view->getTemplate() );
	}

	/**
	 * Test default image width.
	 */
	public function test_default_image_width(): void {
		$this->assertSame( 280, $this->view->getImageWidth() );
	}

	/**
	 * Test setImageWidth.
	 */
	public function test_set_image_width(): void {
		$this->view->setImageWidth( 200 );
		$this->assertSame( 200, $this->view->getImageWidth() );
	}

	/**
	 * Test setImageWidth clamps to minimum.
	 */
	public function test_set_image_width_minimum(): void {
		$this->view->setImageWidth( 50 );
		$this->assertSame( 100, $this->view->getImageWidth() );
	}

	/**
	 * Test setImageWidth clamps to maximum.
	 */
	public function test_set_image_width_maximum(): void {
		$this->view->setImageWidth( 500 );
		$this->assertSame( 400, $this->view->getImageWidth() );
	}

	/**
	 * Test default max tags.
	 */
	public function test_default_max_tags(): void {
		$this->assertSame( 5, $this->view->getMaxTags() );
	}

	/**
	 * Test setMaxTags.
	 */
	public function test_set_max_tags(): void {
		$this->view->setMaxTags( 10 );
		$this->assertSame( 10, $this->view->getMaxTags() );
	}

	/**
	 * Test setMaxTags clamps to minimum.
	 */
	public function test_set_max_tags_minimum(): void {
		$this->view->setMaxTags( -5 );
		$this->assertSame( 0, $this->view->getMaxTags() );
	}

	/**
	 * Test setMaxTags clamps to maximum.
	 */
	public function test_set_max_tags_maximum(): void {
		$this->view->setMaxTags( 50 );
		$this->assertSame( 20, $this->view->getMaxTags() );
	}

	/**
	 * Test constructor accepts configuration.
	 */
	public function test_constructor_accepts_config(): void {
		$view = new ListView( [ 'max_tags' => 8, 'image_width' => 300 ] );
		$this->assertSame( 8, $view->getMaxTags() );
		$this->assertSame( 300, $view->getImageWidth() );
	}

	/**
	 * Test setConfig merges with defaults.
	 */
	public function test_set_config_merges_with_defaults(): void {
		$this->view->setConfig( [ 'show_image' => false ] );
		$config = $this->view->getConfig();

		$this->assertFalse( $config['show_image'] );
		$this->assertSame( 5, $config['max_tags'] ); // Default preserved.
	}

	/**
	 * Test getContainerClasses includes view type.
	 */
	public function test_get_container_classes(): void {
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$classes = $this->view->getContainerClasses();

		$this->assertContains( 'apd-listings', $classes );
		$this->assertContains( 'apd-listings--list', $classes );
	}

	/**
	 * Test getContainerAttributes includes view type.
	 */
	public function test_get_container_attributes(): void {
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$attributes = $this->view->getContainerAttributes();

		$this->assertSame( 'list', $attributes['view'] );
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
			'image'   => [ 'image' ],
			'excerpt' => [ 'excerpt' ],
			'tags'    => [ 'tags' ],
			'date'    => [ 'date' ],
			'sidebar' => [ 'sidebar' ],
		];
	}

	/**
	 * Test supports returns false for unsupported features.
	 */
	public function test_supports_returns_false_for_unsupported(): void {
		$this->assertFalse( $this->view->supports( 'columns' ) );
		$this->assertFalse( $this->view->supports( 'masonry' ) );
		$this->assertFalse( $this->view->supports( 'nonexistent' ) );
	}

	/**
	 * Test getResponsiveLayout returns expected structure.
	 */
	public function test_get_responsive_layout(): void {
		Functions\when( 'apply_filters' )->alias( function( $hook, $value ) {
			return $value;
		} );

		$layout = $this->view->getResponsiveLayout();

		$this->assertArrayHasKey( 'desktop', $layout );
		$this->assertArrayHasKey( 'tablet', $layout );
		$this->assertArrayHasKey( 'mobile', $layout );
		$this->assertSame( 'horizontal', $layout['desktop'] );
		$this->assertSame( 'vertical', $layout['tablet'] );
		$this->assertSame( 'vertical', $layout['mobile'] );
	}

	/**
	 * Test default configuration values.
	 */
	public function test_default_config_values(): void {
		$config = $this->view->getConfig();

		$this->assertTrue( $config['show_image'] );
		$this->assertTrue( $config['show_excerpt'] );
		$this->assertSame( 30, $config['excerpt_length'] );
		$this->assertTrue( $config['show_category'] );
		$this->assertTrue( $config['show_tags'] );
		$this->assertSame( 5, $config['max_tags'] );
		$this->assertTrue( $config['show_date'] );
		$this->assertTrue( $config['show_price'] );
		$this->assertTrue( $config['show_rating'] );
		$this->assertTrue( $config['show_favorite'] );
		$this->assertTrue( $config['show_view_details'] );
		$this->assertSame( 'medium', $config['image_size'] );
		$this->assertSame( 280, $config['image_width'] );
	}

	/**
	 * Test list view has longer excerpt than grid by default.
	 */
	public function test_default_excerpt_length_longer_than_grid(): void {
		$listConfig = $this->view->getConfig();
		$gridView   = new \APD\Frontend\Display\GridView();
		$gridConfig = $gridView->getConfig();

		$this->assertGreaterThan( $gridConfig['excerpt_length'], $listConfig['excerpt_length'] );
	}

	/**
	 * Test getConfigValue returns correct value.
	 */
	public function test_get_config_value(): void {
		$this->assertTrue( $this->view->getConfigValue( 'show_tags' ) );
		$this->assertSame( 5, $this->view->getConfigValue( 'max_tags' ) );
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
		$this->view->setConfigValue( 'show_tags', false );
		$this->assertFalse( $this->view->getConfigValue( 'show_tags' ) );
	}

	/**
	 * Test method chaining with setImageWidth.
	 */
	public function test_set_image_width_returns_self(): void {
		$result = $this->view->setImageWidth( 200 );
		$this->assertSame( $this->view, $result );
	}

	/**
	 * Test method chaining with setMaxTags.
	 */
	public function test_set_max_tags_returns_self(): void {
		$result = $this->view->setMaxTags( 10 );
		$this->assertSame( $this->view, $result );
	}

	/**
	 * Test method chaining with setConfig.
	 */
	public function test_set_config_returns_self(): void {
		$result = $this->view->setConfig( [ 'show_tags' => false ] );
		$this->assertSame( $this->view, $result );
	}

	/**
	 * Test method chaining with setConfigValue.
	 */
	public function test_set_config_value_returns_self(): void {
		$result = $this->view->setConfigValue( 'show_date', false );
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
	 * Test buildRenderArgs uses list defaults (30 for excerpt_length, not grid's 15).
	 */
	public function test_build_render_args_uses_list_defaults(): void {
		$ref  = new \ReflectionMethod( $this->view, 'buildRenderArgs' );
		$args = $ref->invoke( $this->view );

		$this->assertTrue( $args['show_image'] );
		$this->assertSame( 30, $args['excerpt_length'] );
		$this->assertSame( 'medium', $args['image_size'] );
	}

	/**
	 * Test buildRenderArgs does not include list-specific keys.
	 */
	public function test_build_render_args_excludes_list_specific_keys(): void {
		$ref  = new \ReflectionMethod( $this->view, 'buildRenderArgs' );
		$args = $ref->invoke( $this->view );

		$this->assertArrayNotHasKey( 'show_tags', $args );
		$this->assertArrayNotHasKey( 'max_tags', $args );
		$this->assertArrayNotHasKey( 'show_date', $args );
		$this->assertArrayNotHasKey( 'image_width', $args );
	}
}
