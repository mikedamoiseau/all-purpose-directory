<?php
/**
 * Unit tests for DemoDataProviderRegistry.
 *
 * Tests the demo data provider registration and retrieval system.
 *
 * @package APD\Tests\Unit\Admin\DemoData
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Admin\DemoData;

use APD\Admin\DemoData\DemoDataProviderRegistry;
use APD\Contracts\DemoDataProviderInterface;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Actions;
use Mockery;

/**
 * Test case for DemoDataProviderRegistry class.
 *
 * @covers \APD\Admin\DemoData\DemoDataProviderRegistry
 */
class DemoDataProviderRegistryTest extends UnitTestCase {

	/**
	 * The provider registry instance.
	 *
	 * @var DemoDataProviderRegistry
	 */
	private DemoDataProviderRegistry $registry;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		DemoDataProviderRegistry::reset_instance();
		$this->registry = DemoDataProviderRegistry::get_instance();
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		DemoDataProviderRegistry::reset_instance();

		parent::tearDown();
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function testGetInstanceReturnsSingleton(): void {
		$instance1 = DemoDataProviderRegistry::get_instance();
		$instance2 = DemoDataProviderRegistry::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test registering a valid provider returns true and stores it.
	 */
	public function testRegisterValidProvider(): void {
		$provider = $this->create_mock_provider( 'test-provider' );

		$result = $this->registry->register( $provider );

		$this->assertTrue( $result );
		$this->assertTrue( $this->registry->has( 'test-provider' ) );
		$this->assertSame( $provider, $this->registry->get( 'test-provider' ) );
	}

	/**
	 * Test registering a provider with empty slug returns false.
	 */
	public function testRegisterRejectsEmptySlug(): void {
		$provider = $this->create_mock_provider( '' );

		// _doing_it_wrong is defined in bootstrap.php and just logs/ignores.
		$result = $this->registry->register( $provider );

		$this->assertFalse( $result );
	}

	/**
	 * Test registering a duplicate slug returns false.
	 */
	public function testRegisterRejectsDuplicateSlug(): void {
		$provider1 = $this->create_mock_provider( 'duplicate' );
		$provider2 = $this->create_mock_provider( 'duplicate' );

		$this->registry->register( $provider1 );

		// _doing_it_wrong is defined in bootstrap.php and just logs/ignores.
		$result = $this->registry->register( $provider2 );

		$this->assertFalse( $result );
	}

	/**
	 * Test unregistering removes a provider.
	 */
	public function testUnregisterRemovesProvider(): void {
		$provider = $this->create_mock_provider( 'to-remove' );
		$this->registry->register( $provider );

		$this->assertTrue( $this->registry->has( 'to-remove' ) );

		$result = $this->registry->unregister( 'to-remove' );

		$this->assertTrue( $result );
		$this->assertFalse( $this->registry->has( 'to-remove' ) );
		$this->assertNull( $this->registry->get( 'to-remove' ) );
	}

	/**
	 * Test unregistering a non-existent provider returns false.
	 */
	public function testUnregisterReturnsFalseForNonexistent(): void {
		$result = $this->registry->unregister( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test get returns the correct provider by slug.
	 */
	public function testGetReturnsProviderBySlug(): void {
		$provider_a = $this->create_mock_provider( 'provider-a' );
		$provider_b = $this->create_mock_provider( 'provider-b' );

		$this->registry->register( $provider_a );
		$this->registry->register( $provider_b );

		$this->assertSame( $provider_a, $this->registry->get( 'provider-a' ) );
		$this->assertSame( $provider_b, $this->registry->get( 'provider-b' ) );
	}

	/**
	 * Test get returns null for a non-existent provider.
	 */
	public function testGetReturnsNullForNonexistent(): void {
		$result = $this->registry->get( 'nonexistent' );

		$this->assertNull( $result );
	}

	/**
	 * Test get_all returns all registered providers.
	 */
	public function testGetAllReturnsAllProviders(): void {
		$provider_a = $this->create_mock_provider( 'provider-a' );
		$provider_b = $this->create_mock_provider( 'provider-b' );
		$provider_c = $this->create_mock_provider( 'provider-c' );

		$this->registry->register( $provider_a );
		$this->registry->register( $provider_b );
		$this->registry->register( $provider_c );

		$all = $this->registry->get_all();

		$this->assertCount( 3, $all );
		$this->assertArrayHasKey( 'provider-a', $all );
		$this->assertArrayHasKey( 'provider-b', $all );
		$this->assertArrayHasKey( 'provider-c', $all );
	}

	/**
	 * Test has returns true when a provider is registered.
	 */
	public function testHasReturnsTrueWhenRegistered(): void {
		$provider = $this->create_mock_provider( 'existing' );
		$this->registry->register( $provider );

		$this->assertTrue( $this->registry->has( 'existing' ) );
	}

	/**
	 * Test has returns false when a provider is not registered.
	 */
	public function testHasReturnsFalseWhenNotRegistered(): void {
		$this->assertFalse( $this->registry->has( 'nonexistent' ) );
	}

	/**
	 * Test count returns the correct number of providers.
	 */
	public function testCountReturnsCorrectCount(): void {
		$this->assertSame( 0, $this->registry->count() );

		$this->registry->register( $this->create_mock_provider( 'first' ) );
		$this->assertSame( 1, $this->registry->count() );

		$this->registry->register( $this->create_mock_provider( 'second' ) );
		$this->assertSame( 2, $this->registry->count() );
	}

	/**
	 * Test init fires the apd_demo_providers_init action.
	 */
	public function testInitFiresAction(): void {
		Actions\expectDone( 'apd_demo_providers_init' )
			->once()
			->with( $this->registry );

		$this->registry->init();
	}

	/**
	 * Test init only fires the action once.
	 */
	public function testInitOnlyFiresOnce(): void {
		Actions\expectDone( 'apd_demo_providers_init' )
			->once();

		$this->registry->init();
		$this->registry->init();
	}

	/**
	 * Test register fires the apd_demo_provider_registered action.
	 */
	public function testRegisterFiresAction(): void {
		$provider = $this->create_mock_provider( 'action-test' );

		Actions\expectDone( 'apd_demo_provider_registered' )
			->once()
			->with( 'action-test', $provider );

		$this->registry->register( $provider );
	}

	/**
	 * Test unregister fires the apd_demo_provider_unregistered action.
	 */
	public function testUnregisterFiresAction(): void {
		$provider = $this->create_mock_provider( 'unregister-test' );
		$this->registry->register( $provider );

		Actions\expectDone( 'apd_demo_provider_unregistered' )
			->once()
			->with( 'unregister-test', $provider );

		$this->registry->unregister( 'unregister-test' );
	}

	/**
	 * Test reset clears all providers and count goes to zero.
	 */
	public function testResetClearsAllProviders(): void {
		$this->registry->register( $this->create_mock_provider( 'provider-a' ) );
		$this->registry->register( $this->create_mock_provider( 'provider-b' ) );

		$this->assertSame( 2, $this->registry->count() );

		$this->registry->reset();

		$this->assertSame( 0, $this->registry->count() );
		$this->assertEmpty( $this->registry->get_all() );
		$this->assertFalse( $this->registry->has( 'provider-a' ) );
		$this->assertFalse( $this->registry->has( 'provider-b' ) );
	}

	/**
	 * Create a mock provider implementing DemoDataProviderInterface.
	 *
	 * @param string $slug The provider slug.
	 * @return DemoDataProviderInterface
	 */
	private function create_mock_provider( string $slug ): DemoDataProviderInterface {
		$mock = Mockery::mock( DemoDataProviderInterface::class );
		$mock->shouldReceive( 'get_slug' )->andReturn( $slug );
		$mock->shouldReceive( 'get_name' )->andReturn( ucfirst( $slug ) . ' Provider' );
		$mock->shouldReceive( 'get_description' )->andReturn( 'Mock provider for ' . $slug );
		$mock->shouldReceive( 'get_icon' )->andReturn( 'dashicons-admin-plugins' );
		$mock->shouldReceive( 'get_form_fields' )->andReturn( [] );
		$mock->shouldReceive( 'generate' )->andReturn( [] );
		$mock->shouldReceive( 'delete' )->andReturn( [] );
		$mock->shouldReceive( 'count' )->andReturn( [] );

		return $mock;
	}
}
