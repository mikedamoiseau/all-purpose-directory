<?php
/**
 * Unit tests for FieldRegistry.
 *
 * Tests the field registration and retrieval system.
 *
 * @package APD\Tests\Unit\Fields
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields;

use APD\Fields\FieldRegistry;
use APD\Fields\AbstractFieldType;
use APD\Contracts\FieldTypeInterface;
use APD\Tests\Unit\UnitTestCase;
use Brain\Monkey\Filters;

/**
 * Test case for FieldRegistry class.
 *
 * @covers \APD\Fields\FieldRegistry
 */
class FieldRegistryTest extends UnitTestCase {

	/**
	 * The field registry instance.
	 *
	 * @var FieldRegistry
	 */
	private FieldRegistry $registry;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->registry = FieldRegistry::get_instance();
		$this->registry->reset();
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		$this->registry->reset();

		parent::tearDown();
	}

	/**
	 * Test singleton returns same instance.
	 */
	public function testGetInstanceReturnsSameInstance(): void {
		$instance1 = FieldRegistry::get_instance();
		$instance2 = FieldRegistry::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test registering a basic field.
	 */
	public function testRegisterFieldBasic(): void {
		$result = $this->registry->register_field( 'test_field', [
			'type'  => 'text',
			'label' => 'Test Field',
		] );

		$this->assertTrue( $result );
		$this->assertTrue( $this->registry->has_field( 'test_field' ) );
	}

	/**
	 * Test registered field has correct configuration.
	 */
	public function testRegisteredFieldHasCorrectConfig(): void {
		$this->registry->register_field( 'my_field', [
			'type'        => 'email',
			'label'       => 'Email Address',
			'description' => 'Enter your email',
			'required'    => true,
			'placeholder' => 'you@example.com',
			'searchable'  => true,
			'priority'    => 5,
		] );

		$field = $this->registry->get_field( 'my_field' );

		$this->assertNotNull( $field );
		$this->assertSame( 'my_field', $field['name'] );
		$this->assertSame( 'email', $field['type'] );
		$this->assertSame( 'Email Address', $field['label'] );
		$this->assertSame( 'Enter your email', $field['description'] );
		$this->assertTrue( $field['required'] );
		$this->assertSame( 'you@example.com', $field['placeholder'] );
		$this->assertTrue( $field['searchable'] );
		$this->assertSame( 5, $field['priority'] );
	}

	/**
	 * Test default values are applied to registered field.
	 */
	public function testRegisterFieldAppliesDefaults(): void {
		$this->registry->register_field( 'minimal_field' );

		$field = $this->registry->get_field( 'minimal_field' );

		$this->assertNotNull( $field );
		$this->assertSame( 'minimal_field', $field['name'] );
		$this->assertSame( 'text', $field['type'] );
		$this->assertSame( 'Minimal Field', $field['label'] ); // Generated from name.
		$this->assertSame( '', $field['description'] );
		$this->assertFalse( $field['required'] );
		$this->assertSame( '', $field['default'] );
		$this->assertSame( '', $field['placeholder'] );
		$this->assertSame( [], $field['options'] );
		$this->assertSame( [], $field['validation'] );
		$this->assertFalse( $field['searchable'] );
		$this->assertFalse( $field['filterable'] );
		$this->assertFalse( $field['admin_only'] );
		$this->assertSame( 10, $field['priority'] );
	}

	/**
	 * Test registering field with empty name fails.
	 */
	public function testRegisterFieldWithEmptyNameFails(): void {
		$result = $this->registry->register_field( '' );

		$this->assertFalse( $result );
	}

	/**
	 * Test registering duplicate field fails.
	 */
	public function testRegisterDuplicateFieldFails(): void {
		$this->registry->register_field( 'duplicate_field' );

		$result = $this->registry->register_field( 'duplicate_field' );

		$this->assertFalse( $result );
	}

	/**
	 * Test field name is sanitized.
	 */
	public function testFieldNameIsSanitized(): void {
		$this->registry->register_field( 'My Field Name!' );

		// sanitize_key removes non-alphanumeric characters (except _ and -).
		$this->assertTrue( $this->registry->has_field( 'myfieldname' ) );

		// The stored field config has the sanitized name.
		$field = $this->registry->get_field( 'myfieldname' );
		$this->assertSame( 'myfieldname', $field['name'] );
	}

	/**
	 * Test unregistering a field.
	 */
	public function testUnregisterField(): void {
		$this->registry->register_field( 'to_remove' );
		$this->assertTrue( $this->registry->has_field( 'to_remove' ) );

		$result = $this->registry->unregister_field( 'to_remove' );

		$this->assertTrue( $result );
		$this->assertFalse( $this->registry->has_field( 'to_remove' ) );
	}

	/**
	 * Test unregistering non-existent field returns false.
	 */
	public function testUnregisterNonExistentFieldReturnsFalse(): void {
		$result = $this->registry->unregister_field( 'nonexistent' );

		$this->assertFalse( $result );
	}

	/**
	 * Test getting non-existent field returns null.
	 */
	public function testGetNonExistentFieldReturnsNull(): void {
		$field = $this->registry->get_field( 'nonexistent' );

		$this->assertNull( $field );
	}

	/**
	 * Test get_fields returns all registered fields.
	 */
	public function testGetFieldsReturnsAllFields(): void {
		$this->registry->register_field( 'field_one' );
		$this->registry->register_field( 'field_two' );
		$this->registry->register_field( 'field_three' );

		$fields = $this->registry->get_fields();

		$this->assertCount( 3, $fields );
		$this->assertArrayHasKey( 'field_one', $fields );
		$this->assertArrayHasKey( 'field_two', $fields );
		$this->assertArrayHasKey( 'field_three', $fields );
	}

	/**
	 * Test get_fields filters by type.
	 */
	public function testGetFieldsFiltersByType(): void {
		$this->registry->register_field( 'text_field', [ 'type' => 'text' ] );
		$this->registry->register_field( 'email_field', [ 'type' => 'email' ] );
		$this->registry->register_field( 'another_text', [ 'type' => 'text' ] );

		$text_fields = $this->registry->get_fields( [ 'type' => 'text' ] );

		$this->assertCount( 2, $text_fields );
		$this->assertArrayHasKey( 'text_field', $text_fields );
		$this->assertArrayHasKey( 'another_text', $text_fields );
		$this->assertArrayNotHasKey( 'email_field', $text_fields );
	}

	/**
	 * Test get_fields filters by searchable.
	 */
	public function testGetFieldsFiltersBySearchable(): void {
		$this->registry->register_field( 'searchable_field', [ 'searchable' => true ] );
		$this->registry->register_field( 'non_searchable', [ 'searchable' => false ] );

		$searchable = $this->registry->get_fields( [ 'searchable' => true ] );

		$this->assertCount( 1, $searchable );
		$this->assertArrayHasKey( 'searchable_field', $searchable );
	}

	/**
	 * Test get_fields filters by filterable.
	 */
	public function testGetFieldsFiltersByFilterable(): void {
		$this->registry->register_field( 'filterable_field', [ 'filterable' => true ] );
		$this->registry->register_field( 'non_filterable', [ 'filterable' => false ] );

		$filterable = $this->registry->get_fields( [ 'filterable' => true ] );

		$this->assertCount( 1, $filterable );
		$this->assertArrayHasKey( 'filterable_field', $filterable );
	}

	/**
	 * Test get_fields filters by admin_only.
	 */
	public function testGetFieldsFiltersByAdminOnly(): void {
		$this->registry->register_field( 'admin_field', [ 'admin_only' => true ] );
		$this->registry->register_field( 'public_field', [ 'admin_only' => false ] );

		$admin_fields = $this->registry->get_fields( [ 'admin_only' => true ] );

		$this->assertCount( 1, $admin_fields );
		$this->assertArrayHasKey( 'admin_field', $admin_fields );
	}

	/**
	 * Test get_fields sorts by priority ascending.
	 */
	public function testGetFieldsSortsByPriorityAscending(): void {
		$this->registry->register_field( 'low_priority', [ 'priority' => 20 ] );
		$this->registry->register_field( 'high_priority', [ 'priority' => 5 ] );
		$this->registry->register_field( 'medium_priority', [ 'priority' => 10 ] );

		$fields = $this->registry->get_fields( [ 'orderby' => 'priority', 'order' => 'ASC' ] );

		$keys = array_keys( $fields );
		$this->assertSame( 'high_priority', $keys[0] );
		$this->assertSame( 'medium_priority', $keys[1] );
		$this->assertSame( 'low_priority', $keys[2] );
	}

	/**
	 * Test get_fields sorts by priority descending.
	 */
	public function testGetFieldsSortsByPriorityDescending(): void {
		$this->registry->register_field( 'low_priority', [ 'priority' => 20 ] );
		$this->registry->register_field( 'high_priority', [ 'priority' => 5 ] );

		$fields = $this->registry->get_fields( [ 'orderby' => 'priority', 'order' => 'DESC' ] );

		$keys = array_keys( $fields );
		$this->assertSame( 'low_priority', $keys[0] );
		$this->assertSame( 'high_priority', $keys[1] );
	}

	/**
	 * Test get_fields sorts by name.
	 */
	public function testGetFieldsSortsByName(): void {
		$this->registry->register_field( 'zebra' );
		$this->registry->register_field( 'apple' );
		$this->registry->register_field( 'banana' );

		$fields = $this->registry->get_fields( [ 'orderby' => 'name', 'order' => 'ASC' ] );

		$keys = array_keys( $fields );
		$this->assertSame( 'apple', $keys[0] );
		$this->assertSame( 'banana', $keys[1] );
		$this->assertSame( 'zebra', $keys[2] );
	}

	/**
	 * Test count method.
	 */
	public function testCount(): void {
		$this->assertSame( 0, $this->registry->count() );

		$this->registry->register_field( 'field_one' );
		$this->assertSame( 1, $this->registry->count() );

		$this->registry->register_field( 'field_two' );
		$this->assertSame( 2, $this->registry->count() );

		$this->registry->unregister_field( 'field_one' );
		$this->assertSame( 1, $this->registry->count() );
	}

	/**
	 * Test get_searchable_fields convenience method.
	 */
	public function testGetSearchableFields(): void {
		$this->registry->register_field( 'searchable', [ 'searchable' => true ] );
		$this->registry->register_field( 'not_searchable', [ 'searchable' => false ] );

		$fields = $this->registry->get_searchable_fields();

		$this->assertCount( 1, $fields );
		$this->assertArrayHasKey( 'searchable', $fields );
	}

	/**
	 * Test get_filterable_fields convenience method.
	 */
	public function testGetFilterableFields(): void {
		$this->registry->register_field( 'filterable', [ 'filterable' => true ] );
		$this->registry->register_field( 'not_filterable', [ 'filterable' => false ] );

		$fields = $this->registry->get_filterable_fields();

		$this->assertCount( 1, $fields );
		$this->assertArrayHasKey( 'filterable', $fields );
	}

	/**
	 * Test get_frontend_fields convenience method.
	 */
	public function testGetFrontendFields(): void {
		$this->registry->register_field( 'public_field', [ 'admin_only' => false ] );
		$this->registry->register_field( 'admin_field', [ 'admin_only' => true ] );

		$fields = $this->registry->get_frontend_fields();

		$this->assertCount( 1, $fields );
		$this->assertArrayHasKey( 'public_field', $fields );
	}

	/**
	 * Test get_admin_fields convenience method.
	 */
	public function testGetAdminFields(): void {
		$this->registry->register_field( 'public_field', [ 'admin_only' => false ] );
		$this->registry->register_field( 'admin_field', [ 'admin_only' => true ] );

		$fields = $this->registry->get_admin_fields();

		$this->assertCount( 1, $fields );
		$this->assertArrayHasKey( 'admin_field', $fields );
	}

	/**
	 * Test get_meta_key generates correct key.
	 */
	public function testGetMetaKey(): void {
		$key = $this->registry->get_meta_key( 'my_field' );

		$this->assertSame( '_apd_my_field', $key );
	}

	/**
	 * Test get_meta_key sanitizes field name.
	 */
	public function testGetMetaKeySanitizesFieldName(): void {
		$key = $this->registry->get_meta_key( 'My Field!' );

		// sanitize_key removes non-alphanumeric characters (except _ and -).
		$this->assertSame( '_apd_myfield', $key );
	}

	/**
	 * Test reset clears all fields and field types.
	 */
	public function testResetClearsAll(): void {
		$this->registry->register_field( 'test_field' );
		$this->registry->register_field_type( $this->createMockFieldType( 'mock' ) );

		$this->registry->reset();

		$this->assertSame( 0, $this->registry->count() );
		$this->assertNull( $this->registry->get_field_type( 'mock' ) );
	}

	/**
	 * Test reset_fields only clears fields.
	 */
	public function testResetFieldsOnlyClearsFields(): void {
		$this->registry->register_field( 'test_field' );
		$this->registry->register_field_type( $this->createMockFieldType( 'mock' ) );

		$this->registry->reset_fields();

		$this->assertSame( 0, $this->registry->count() );
		$this->assertNotNull( $this->registry->get_field_type( 'mock' ) );
	}

	/**
	 * Test registering a field type.
	 */
	public function testRegisterFieldType(): void {
		$field_type = $this->createMockFieldType( 'custom' );

		$result = $this->registry->register_field_type( $field_type );

		$this->assertTrue( $result );
		$this->assertTrue( $this->registry->has_field_type( 'custom' ) );
	}

	/**
	 * Test registering duplicate field type fails.
	 */
	public function testRegisterDuplicateFieldTypeFails(): void {
		$field_type1 = $this->createMockFieldType( 'custom' );
		$field_type2 = $this->createMockFieldType( 'custom' );

		$this->registry->register_field_type( $field_type1 );
		$result = $this->registry->register_field_type( $field_type2 );

		$this->assertFalse( $result );
	}

	/**
	 * Test getting a field type.
	 */
	public function testGetFieldType(): void {
		$field_type = $this->createMockFieldType( 'custom' );
		$this->registry->register_field_type( $field_type );

		$retrieved = $this->registry->get_field_type( 'custom' );

		$this->assertSame( $field_type, $retrieved );
	}

	/**
	 * Test getting non-existent field type returns null.
	 */
	public function testGetNonExistentFieldTypeReturnsNull(): void {
		$result = $this->registry->get_field_type( 'nonexistent' );

		$this->assertNull( $result );
	}

	/**
	 * Test get_field_types returns all types.
	 */
	public function testGetFieldTypesReturnsAll(): void {
		$this->registry->register_field_type( $this->createMockFieldType( 'type_one' ) );
		$this->registry->register_field_type( $this->createMockFieldType( 'type_two' ) );

		$types = $this->registry->get_field_types();

		$this->assertCount( 2, $types );
		$this->assertArrayHasKey( 'type_one', $types );
		$this->assertArrayHasKey( 'type_two', $types );
	}

	/**
	 * Test field with options configuration.
	 */
	public function testFieldWithOptions(): void {
		$this->registry->register_field( 'status', [
			'type'    => 'select',
			'label'   => 'Status',
			'options' => [
				'active'   => 'Active',
				'inactive' => 'Inactive',
				'pending'  => 'Pending',
			],
		] );

		$field = $this->registry->get_field( 'status' );

		$this->assertCount( 3, $field['options'] );
		$this->assertSame( 'Active', $field['options']['active'] );
	}

	/**
	 * Test field with validation rules.
	 */
	public function testFieldWithValidationRules(): void {
		$this->registry->register_field( 'username', [
			'type'       => 'text',
			'label'      => 'Username',
			'validation' => [
				'min_length' => 3,
				'max_length' => 20,
				'pattern'    => '/^[a-z0-9_]+$/',
			],
		] );

		$field = $this->registry->get_field( 'username' );

		$this->assertSame( 3, $field['validation']['min_length'] );
		$this->assertSame( 20, $field['validation']['max_length'] );
		$this->assertSame( '/^[a-z0-9_]+$/', $field['validation']['pattern'] );
	}

	/**
	 * Test combined filters work correctly.
	 */
	public function testCombinedFilters(): void {
		$this->registry->register_field( 'field_a', [
			'type'       => 'text',
			'searchable' => true,
			'filterable' => true,
		] );
		$this->registry->register_field( 'field_b', [
			'type'       => 'text',
			'searchable' => true,
			'filterable' => false,
		] );
		$this->registry->register_field( 'field_c', [
			'type'       => 'select',
			'searchable' => false,
			'filterable' => true,
		] );

		$fields = $this->registry->get_fields( [
			'type'       => 'text',
			'searchable' => true,
		] );

		$this->assertCount( 2, $fields );
		$this->assertArrayHasKey( 'field_a', $fields );
		$this->assertArrayHasKey( 'field_b', $fields );
	}

	/**
	 * Test label is auto-generated from field name.
	 */
	public function testLabelAutoGeneratedFromName(): void {
		$this->registry->register_field( 'business_phone_number' );

		$field = $this->registry->get_field( 'business_phone_number' );

		$this->assertSame( 'Business Phone Number', $field['label'] );
	}

	/**
	 * Test label with underscores is converted properly.
	 */
	public function testLabelWithUnderscoresConverted(): void {
		$this->registry->register_field( 'my_custom_field' );

		$field = $this->registry->get_field( 'my_custom_field' );

		$this->assertSame( 'My Custom Field', $field['label'] );
	}

	/**
	 * Test label with hyphens is converted properly.
	 */
	public function testLabelWithHyphensConverted(): void {
		$this->registry->register_field( 'my-custom-field' );

		$field = $this->registry->get_field( 'my-custom-field' );

		$this->assertSame( 'My Custom Field', $field['label'] );
	}

	/**
	 * Test load_external_fields registers fields from the filter.
	 */
	public function testLoadExternalFieldsRegistersFieldsFromFilter(): void {
		Filters\expectApplied( 'apd_listing_fields' )
			->once()
			->with( [] )
			->andReturn( [
				'website' => [
					'type'  => 'url',
					'label' => 'Website URL',
				],
				'phone'   => [
					'type'  => 'text',
					'label' => 'Phone Number',
				],
			] );

		$this->registry->load_external_fields();

		$this->assertTrue( $this->registry->has_field( 'website' ) );
		$this->assertTrue( $this->registry->has_field( 'phone' ) );
		$this->assertSame( 2, $this->registry->count() );

		$website = $this->registry->get_field( 'website' );
		$this->assertSame( 'url', $website['type'] );
		$this->assertSame( 'Website URL', $website['label'] );
	}

	/**
	 * Test load_external_fields does not overwrite existing fields.
	 */
	public function testLoadExternalFieldsDoesNotOverwriteExistingFields(): void {
		// Register a field directly first.
		$this->registry->register_field( 'phone', [
			'type'  => 'text',
			'label' => 'Direct Phone',
		] );

		Filters\expectApplied( 'apd_listing_fields' )
			->once()
			->andReturn( [
				'phone' => [
					'type'  => 'tel',
					'label' => 'Filter Phone',
				],
				'email' => [
					'type'  => 'email',
					'label' => 'Email',
				],
			] );

		$this->registry->load_external_fields();

		// Existing field should keep its original config.
		$phone = $this->registry->get_field( 'phone' );
		$this->assertSame( 'text', $phone['type'] );
		$this->assertSame( 'Direct Phone', $phone['label'] );

		// New field from filter should be registered.
		$this->assertTrue( $this->registry->has_field( 'email' ) );
		$this->assertSame( 2, $this->registry->count() );
	}

	/**
	 * Test load_external_fields handles non-array filter return gracefully.
	 */
	public function testLoadExternalFieldsHandlesNonArrayReturn(): void {
		Filters\expectApplied( 'apd_listing_fields' )
			->once()
			->andReturn( 'invalid' );

		$this->registry->load_external_fields();

		$this->assertSame( 0, $this->registry->count() );
	}

	/**
	 * Test load_external_fields skips invalid entries.
	 */
	public function testLoadExternalFieldsSkipsInvalidEntries(): void {
		Filters\expectApplied( 'apd_listing_fields' )
			->once()
			->andReturn( [
				0          => [ 'type' => 'text' ], // Numeric key (not a string name).
				''         => [ 'type' => 'text' ], // Empty string key.
				'valid'    => [ 'type' => 'text', 'label' => 'Valid Field' ],
				'bad_conf' => 'not an array',        // Config is not an array.
			] );

		$this->registry->load_external_fields();

		$this->assertSame( 1, $this->registry->count() );
		$this->assertTrue( $this->registry->has_field( 'valid' ) );
	}

	/**
	 * Create a mock field type for testing.
	 *
	 * @param string $type The type identifier.
	 * @return FieldTypeInterface
	 */
	private function createMockFieldType( string $type ): FieldTypeInterface {
		return new class( $type ) extends AbstractFieldType {
			private string $type;

			public function __construct( string $type ) {
				$this->type = $type;
			}

			public function getType(): string {
				return $this->type;
			}

			public function render( array $field, mixed $value ): string {
				return '';
			}
		};
	}
}
