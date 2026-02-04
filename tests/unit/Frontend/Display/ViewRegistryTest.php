<?php
/**
 * ViewRegistry Unit Tests.
 *
 * @package APD\Tests\Unit\Frontend\Display
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Frontend\Display;

use APD\Frontend\Display\ViewRegistry;
use APD\Frontend\Display\GridView;
use APD\Frontend\Display\ListView;
use APD\Contracts\ViewInterface;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test class for ViewRegistry.
 */
final class ViewRegistryTest extends UnitTestCase {

	/**
	 * Reset singleton between tests.
	 */
	protected function setUp(): void {
		parent::setUp();
		// Reset singleton using reflection.
		$reflection = new \ReflectionClass( ViewRegistry::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setValue( null, null );

		// Mock apd_get_option to return grid as default.
		Functions\when( 'apd_get_option' )->justReturn( 'grid' );
	}

	/**
	 * Test get_instance returns singleton.
	 */
	public function test_get_instance_returns_singleton(): void {
		$instance1 = ViewRegistry::get_instance();
		$instance2 = ViewRegistry::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test get_instance returns ViewRegistry.
	 */
	public function test_get_instance_returns_view_registry(): void {
		$registry = ViewRegistry::get_instance();

		$this->assertInstanceOf( ViewRegistry::class, $registry );
	}

	/**
	 * Test init registers core views.
	 */
	public function test_init_registers_core_views(): void {
		$registry = ViewRegistry::get_instance();
		$registry->init();

		$this->assertTrue( $registry->has_view( 'grid' ) );
		$this->assertTrue( $registry->has_view( 'list' ) );
	}

	/**
	 * Test register_view registers a view.
	 */
	public function test_register_view(): void {
		$registry = ViewRegistry::get_instance();
		$view     = new GridView();

		$result = $registry->register_view( $view );

		$this->assertTrue( $result );
		$this->assertTrue( $registry->has_view( 'grid' ) );
	}

	/**
	 * Test register_view rejects view with empty type.
	 */
	public function test_register_view_rejects_empty_type(): void {
		$registry = ViewRegistry::get_instance();

		$view = Mockery::mock( ViewInterface::class );
		$view->shouldReceive( 'getType' )->andReturn( '' );

		$result = $registry->register_view( $view );

		$this->assertFalse( $result );
	}

	/**
	 * Test unregister_view unregisters a view.
	 */
	public function test_unregister_view(): void {
		$registry = ViewRegistry::get_instance();
		$view     = new GridView();
		$registry->register_view( $view );

		// Verify it was registered.
		$this->assertTrue( $registry->has_view( 'grid' ) );

		$result = $registry->unregister_view( 'grid' );

		$this->assertTrue( $result );
		$this->assertFalse( $registry->has_view( 'grid' ) );
	}

	/**
	 * Test unregister_view returns false for non-existent view.
	 */
	public function test_unregister_view_returns_false_for_nonexistent(): void {
		$registry = ViewRegistry::get_instance();

		$result = $registry->unregister_view( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test get_view returns registered view.
	 */
	public function test_get_view_returns_registered_view(): void {
		$registry = ViewRegistry::get_instance();
		$view     = $registry->get_view( 'grid' );

		$this->assertInstanceOf( GridView::class, $view );
	}

	/**
	 * Test get_view returns null for non-existent view.
	 */
	public function test_get_view_returns_null_for_nonexistent(): void {
		$registry = ViewRegistry::get_instance();
		$view     = $registry->get_view( 'nonexistent' );

		$this->assertNull( $view );
	}

	/**
	 * Test get_views returns all registered views.
	 */
	public function test_get_views_returns_all_views(): void {
		$registry = ViewRegistry::get_instance();
		$views    = $registry->get_views();

		$this->assertIsArray( $views );
		$this->assertArrayHasKey( 'grid', $views );
		$this->assertArrayHasKey( 'list', $views );
		$this->assertCount( 2, $views );
	}

	/**
	 * Test has_view returns true for registered view.
	 */
	public function test_has_view_returns_true_for_registered(): void {
		$registry = ViewRegistry::get_instance();

		$this->assertTrue( $registry->has_view( 'grid' ) );
		$this->assertTrue( $registry->has_view( 'list' ) );
	}

	/**
	 * Test has_view returns false for non-existent view.
	 */
	public function test_has_view_returns_false_for_nonexistent(): void {
		$registry = ViewRegistry::get_instance();

		$this->assertFalse( $registry->has_view( 'nonexistent' ) );
	}

	/**
	 * Test get_default_view returns configured default.
	 */
	public function test_get_default_view_returns_configured(): void {
		$registry = ViewRegistry::get_instance();
		$default  = $registry->get_default_view();

		$this->assertSame( 'grid', $default );
	}

	/**
	 * Test get_default_view falls back if configured view doesn't exist.
	 */
	public function test_get_default_view_fallback(): void {
		// Override mock to return nonexistent.
		Functions\when( 'apd_get_option' )->justReturn( 'nonexistent' );

		// Reset singleton.
		$reflection = new \ReflectionClass( ViewRegistry::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setValue( null, null );

		$registry = ViewRegistry::get_instance();
		$registry->set_default_view( 'grid' );
		$default = $registry->get_default_view();

		$this->assertSame( 'grid', $default );
	}

	/**
	 * Test set_default_view updates default.
	 */
	public function test_set_default_view(): void {
		// Override mock to return 'list' when called.
		Functions\when( 'apd_get_option' )->justReturn( 'list' );

		// Reset singleton.
		$reflection = new \ReflectionClass( ViewRegistry::class );
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setValue( null, null );

		$registry = ViewRegistry::get_instance();
		$registry->set_default_view( 'list' );
		$default = $registry->get_default_view();

		$this->assertSame( 'list', $default );
	}

	/**
	 * Test get_view_options returns type => label mapping.
	 */
	public function test_get_view_options(): void {
		$registry = ViewRegistry::get_instance();
		$options  = $registry->get_view_options();

		$this->assertIsArray( $options );
		$this->assertArrayHasKey( 'grid', $options );
		$this->assertArrayHasKey( 'list', $options );
		$this->assertSame( 'Grid', $options['grid'] );
		$this->assertSame( 'List', $options['list'] );
	}

	/**
	 * Test create_view returns new instance.
	 */
	public function test_create_view_returns_new_instance(): void {
		$registry = ViewRegistry::get_instance();

		$view1 = $registry->create_view( 'grid' );
		$view2 = $registry->create_view( 'grid' );

		$this->assertInstanceOf( GridView::class, $view1 );
		$this->assertInstanceOf( GridView::class, $view2 );
		$this->assertNotSame( $view1, $view2 );
	}

	/**
	 * Test create_view with configuration.
	 */
	public function test_create_view_with_config(): void {
		$registry = ViewRegistry::get_instance();

		$view = $registry->create_view( 'grid', [ 'columns' => 4 ] );

		$this->assertInstanceOf( GridView::class, $view );
		$this->assertSame( 4, $view->getColumns() );
	}

	/**
	 * Test create_view returns null for non-existent type.
	 */
	public function test_create_view_returns_null_for_nonexistent(): void {
		$registry = ViewRegistry::get_instance();
		$view     = $registry->create_view( 'nonexistent' );

		$this->assertNull( $view );
	}

	/**
	 * Test get_view triggers initialization.
	 */
	public function test_get_view_triggers_init(): void {
		$registry = ViewRegistry::get_instance();
		// Don't call init() directly, let get_view trigger it.
		$view = $registry->get_view( 'grid' );

		$this->assertInstanceOf( GridView::class, $view );
	}

	/**
	 * Test list view is registered.
	 */
	public function test_list_view_registered(): void {
		$registry = ViewRegistry::get_instance();
		$view     = $registry->get_view( 'list' );

		$this->assertInstanceOf( ListView::class, $view );
	}

	/**
	 * Test views have correct types.
	 */
	public function test_views_have_correct_types(): void {
		$registry = ViewRegistry::get_instance();
		$gridView = $registry->get_view( 'grid' );
		$listView = $registry->get_view( 'list' );

		$this->assertSame( 'grid', $gridView->getType() );
		$this->assertSame( 'list', $listView->getType() );
	}
}
