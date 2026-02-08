<?php
/**
 * Unit tests for ListingTypeMetaBox.
 *
 * @package APD\Tests\Unit\Admin
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Admin;

use APD\Admin\ListingTypeMetaBox;
use APD\Fields\FieldRegistry;
use APD\Module\ModuleRegistry;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WP_Post;
use WP_Term;

// Load listing type helper functions (real implementations that call stubbed WP functions).
require_once dirname( __DIR__ ) . '/Taxonomy/listing-type-test-functions.php';

/**
 * Class ListingTypeMetaBoxTest
 *
 * Tests for the ListingTypeMetaBox class.
 */
class ListingTypeMetaBoxTest extends TestCase {

	/**
	 * ListingTypeMetaBox instance.
	 *
	 * @var ListingTypeMetaBox
	 */
	private ListingTypeMetaBox $meta_box;

	/**
	 * Field registry instance.
	 *
	 * @var FieldRegistry
	 */
	private FieldRegistry $registry;

	/**
	 * Module registry instance.
	 *
	 * @var ModuleRegistry
	 */
	private ModuleRegistry $module_registry;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\stubs( [
			'wp_parse_args' => function ( $args, $defaults ) {
				return array_merge( $defaults, $args );
			},
			'esc_attr' => function ( $text ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
			},
			'esc_html' => function ( $text ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
			},
			'esc_html__' => function ( $text, $domain = 'default' ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
			},
			'__' => function ( $text, $domain = 'default' ) {
				return $text;
			},
			'sanitize_key' => function ( $key ) {
				return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
			},
			'absint' => function ( $value ) {
				return abs( (int) $value );
			},
			'is_admin' => true,
			'add_action' => function () {},
			'add_filter' => function () {},
			'add_meta_box' => function () {},
			'do_action' => function ( $tag, ...$args ) {},
			'apply_filters' => function ( $tag, $value, ...$args ) {
				return $value;
			},
			'wp_nonce_field' => function ( $action, $name, $referer = true, $echo = true ) {
				$html = '<input type="hidden" name="' . $name . '" value="nonce_value">';
				if ( $echo ) {
					echo $html;
				}
				return $html;
			},
			'wp_verify_nonce' => true,
			'wp_unslash' => function ( $value ) {
				return is_string( $value ) ? stripslashes( $value ) : $value;
			},
			'current_user_can' => true,
			'wp_json_encode' => function ( $data ) {
				return json_encode( $data );
			},
			'checked' => function ( $checked, $current, $echo = true ) {
				$result = (string) $checked === (string) $current ? ' checked="checked"' : '';
				if ( $echo ) {
					echo $result;
				}
				return $result;
			},
			'term_exists' => function ( $term, $taxonomy = '' ) {
				// Default: terms exist for general, url-directory, venue.
				$valid = [ 'general', 'url-directory', 'venue' ];
				return in_array( $term, $valid, true ) ? 1 : null;
			},
			'wp_set_object_terms' => function ( $post_id, $terms, $taxonomy ) {
				return [ 1 ];
			},
			'wp_get_object_terms' => function ( $post_id, $taxonomy ) {
				$term       = Mockery::mock( WP_Term::class );
				$term->slug = 'general';
				$term->name = 'General';
				return [ $term ];
			},
			'get_terms' => function () {
				return [];
			},
		] );

		// Get fresh registry instances.
		$this->registry = FieldRegistry::get_instance();
		$this->registry->reset();

		$this->module_registry = ModuleRegistry::get_instance();
		$this->module_registry->reset();

		$this->meta_box = new ListingTypeMetaBox();
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		$this->registry->reset();
		$this->module_registry->reset();
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Create a mock WP_Post object.
	 */
	private function create_mock_post( array $args = [] ): WP_Post {
		$defaults = [
			'ID'          => 123,
			'post_type'   => 'apd_listing',
			'post_title'  => 'Test Listing',
			'post_status' => 'draft',
		];

		$args = array_merge( $defaults, $args );
		$post = Mockery::mock( WP_Post::class );
		foreach ( $args as $key => $value ) {
			$post->$key = $value;
		}

		return $post;
	}

	/**
	 * Create mock listing type terms.
	 *
	 * @param array $types Array of [slug => name] pairs.
	 * @return WP_Term[]
	 */
	private function create_mock_terms( array $types ): array {
		$terms = [];
		foreach ( $types as $slug => $name ) {
			$term       = Mockery::mock( WP_Term::class );
			$term->slug = $slug;
			$term->name = $name;
			$terms[]    = $term;
		}
		return $terms;
	}

	/**
	 * Set up multiple listing types (so meta box appears).
	 */
	private function setup_multiple_types(): void {
		$types = $this->create_mock_terms( [
			'general'       => 'General',
			'url-directory' => 'URL Directory',
		] );
		Functions\when( 'get_terms' )->justReturn( $types );
	}

	// =========================================================================
	// Hook Registration
	// =========================================================================

	/**
	 * Test init() registers hooks in admin context.
	 */
	public function test_init_registers_hooks_in_admin(): void {
		$hooks_added = [];

		Functions\when( 'add_action' )->alias( function ( $hook, $callback, $priority = 10, $args = 1 ) use ( &$hooks_added ) {
			$hooks_added[] = [
				'hook'     => $hook,
				'callback' => $callback,
				'priority' => $priority,
			];
		} );

		Functions\when( 'add_filter' )->alias( function ( $hook, $callback, $priority = 10, $args = 1 ) use ( &$hooks_added ) {
			$hooks_added[] = [
				'hook'     => $hook,
				'callback' => $callback,
				'priority' => $priority,
			];
		} );

		$this->meta_box->init();

		$hook_names = array_column( $hooks_added, 'hook' );
		$this->assertContains( 'add_meta_boxes', $hook_names );
		$this->assertContains( 'save_post_apd_listing', $hook_names );
		$this->assertContains( 'apd_should_display_field', $hook_names );

		// Verify save priority is 20.
		foreach ( $hooks_added as $hook ) {
			if ( $hook['hook'] === 'save_post_apd_listing' ) {
				$this->assertSame( 20, $hook['priority'] );
			}
		}
	}

	/**
	 * Test init() does nothing outside admin context.
	 */
	public function test_init_does_nothing_outside_admin(): void {
		Functions\when( 'is_admin' )->justReturn( false );

		$hooks_added = [];
		Functions\when( 'add_action' )->alias( function ( $hook ) use ( &$hooks_added ) {
			$hooks_added[] = $hook;
		} );

		$this->meta_box->init();

		$this->assertEmpty( $hooks_added );
	}

	// =========================================================================
	// Conditional Registration
	// =========================================================================

	/**
	 * Test meta box not registered with only 1 type.
	 */
	public function test_register_meta_box_skips_with_one_type(): void {
		$terms = $this->create_mock_terms( [ 'general' => 'General' ] );
		Functions\when( 'get_terms' )->justReturn( $terms );

		$meta_box_registered = false;
		Functions\when( 'add_meta_box' )->alias( function () use ( &$meta_box_registered ) {
			$meta_box_registered = true;
		} );

		$this->meta_box->register_meta_box();

		$this->assertFalse( $meta_box_registered );
	}

	/**
	 * Test meta box registered with 2+ types.
	 */
	public function test_register_meta_box_registers_with_multiple_types(): void {
		$this->setup_multiple_types();

		$meta_box_args = null;
		Functions\when( 'add_meta_box' )->alias( function ( $id, $title, $callback, $screen, $context, $priority ) use ( &$meta_box_args ) {
			$meta_box_args = compact( 'id', 'title', 'screen', 'context', 'priority' );
		} );

		$this->meta_box->register_meta_box();

		$this->assertNotNull( $meta_box_args );
		$this->assertSame( 'apd_listing_type_selector', $meta_box_args['id'] );
		$this->assertSame( 'apd_listing', $meta_box_args['screen'] );
		$this->assertSame( 'side', $meta_box_args['context'] );
		$this->assertSame( 'default', $meta_box_args['priority'] );
	}

	// =========================================================================
	// Render
	// =========================================================================

	/**
	 * Test render outputs nonce, fieldset, and radio buttons.
	 */
	public function test_render_meta_box_outputs_radios(): void {
		$this->setup_multiple_types();

		$post = $this->create_mock_post();

		ob_start();
		$this->meta_box->render_meta_box( $post );
		$output = ob_get_clean();

		// Nonce field.
		$this->assertStringContainsString( 'apd_listing_type_nonce', $output );
		// Fieldset with screen-reader legend.
		$this->assertStringContainsString( '<fieldset>', $output );
		$this->assertStringContainsString( 'screen-reader-text', $output );
		// Radio buttons for each type.
		$this->assertStringContainsString( 'name="apd_listing_type"', $output );
		$this->assertStringContainsString( 'value="general"', $output );
		$this->assertStringContainsString( 'value="url-directory"', $output );
	}

	/**
	 * Test render preselects current type.
	 */
	public function test_render_meta_box_preselects_current_type(): void {
		$this->setup_multiple_types();

		// Set listing type to url-directory.
		Functions\when( 'wp_get_object_terms' )->alias( function () {
			$term       = Mockery::mock( WP_Term::class );
			$term->slug = 'url-directory';
			$term->name = 'URL Directory';
			return [ $term ];
		} );

		$post = $this->create_mock_post();

		ob_start();
		$this->meta_box->render_meta_box( $post );
		$output = ob_get_clean();

		// url-directory radio should be checked.
		$this->assertMatchesRegularExpression(
			'/value="url-directory"\s+checked="checked"/',
			$output
		);
	}

	/**
	 * Test render outputs field-type mapping when type-specific fields exist.
	 */
	public function test_render_outputs_field_type_mapping(): void {
		$this->setup_multiple_types();

		$this->registry->register_field( 'website_url', [
			'type'         => 'url',
			'listing_type' => 'url-directory',
		] );

		$post = $this->create_mock_post();

		ob_start();
		$this->meta_box->render_meta_box( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="apd-field-type-mapping"', $output );
		$this->assertStringContainsString( 'data-field-types=', $output );
		$this->assertStringContainsString( 'website_url', $output );
	}

	// =========================================================================
	// Save
	// =========================================================================

	/**
	 * Test save returns early without nonce.
	 */
	public function test_save_returns_without_nonce(): void {
		$_POST = [];

		$set_type_called = false;
		Functions\when( 'wp_set_object_terms' )->alias( function () use ( &$set_type_called ) {
			$set_type_called = true;
			return [ 1 ];
		} );

		$post = $this->create_mock_post();
		$this->meta_box->save_meta_box( 123, $post );

		$this->assertFalse( $set_type_called );
	}

	/**
	 * Test save returns early with invalid nonce.
	 */
	public function test_save_returns_with_invalid_nonce(): void {
		$_POST = [
			'apd_listing_type_nonce' => 'invalid',
			'apd_listing_type'       => 'url-directory',
		];

		Functions\when( 'wp_verify_nonce' )->justReturn( false );

		$set_type_called = false;
		Functions\when( 'wp_set_object_terms' )->alias( function () use ( &$set_type_called ) {
			$set_type_called = true;
			return [ 1 ];
		} );

		$post = $this->create_mock_post();
		$this->meta_box->save_meta_box( 123, $post );

		$this->assertFalse( $set_type_called );
	}

	/**
	 * Test save returns with wrong post type.
	 */
	public function test_save_returns_with_wrong_post_type(): void {
		$_POST = [
			'apd_listing_type_nonce' => 'valid',
			'apd_listing_type'       => 'url-directory',
		];

		$set_type_called = false;
		Functions\when( 'wp_set_object_terms' )->alias( function () use ( &$set_type_called ) {
			$set_type_called = true;
			return [ 1 ];
		} );

		$post = $this->create_mock_post( [ 'post_type' => 'post' ] );
		$this->meta_box->save_meta_box( 123, $post );

		$this->assertFalse( $set_type_called );
	}

	/**
	 * Test save sets valid listing type.
	 *
	 * Runs in separate process because ListingMetaBoxTest defines DOING_AUTOSAVE
	 * as a PHP constant which cannot be undefined and would trigger the autosave guard.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_save_sets_valid_listing_type(): void {
		$_POST = [
			'apd_listing_type_nonce' => 'valid',
			'apd_listing_type'       => 'url-directory',
		];

		$saved_terms = null;
		Functions\when( 'wp_set_object_terms' )->alias( function ( $post_id, $terms, $taxonomy ) use ( &$saved_terms ) {
			$saved_terms = [
				'post_id'  => $post_id,
				'terms'    => $terms,
				'taxonomy' => $taxonomy,
			];
			return [ 1 ];
		} );

		$post = $this->create_mock_post();
		$this->meta_box->save_meta_box( 123, $post );

		$this->assertNotNull( $saved_terms );
		$this->assertSame( 123, $saved_terms['post_id'] );
		$this->assertSame( 'url-directory', $saved_terms['terms'] );
		$this->assertSame( 'apd_listing_type', $saved_terms['taxonomy'] );
	}

	/**
	 * Test save rejects invalid listing type.
	 */
	public function test_save_rejects_invalid_listing_type(): void {
		$_POST = [
			'apd_listing_type_nonce' => 'valid',
			'apd_listing_type'       => 'nonexistent-type',
		];

		Functions\when( 'term_exists' )->justReturn( null );

		$set_type_called = false;
		Functions\when( 'wp_set_object_terms' )->alias( function () use ( &$set_type_called ) {
			$set_type_called = true;
			return [ 1 ];
		} );

		$post = $this->create_mock_post();
		$this->meta_box->save_meta_box( 123, $post );

		$this->assertFalse( $set_type_called );
	}

	// =========================================================================
	// Field Display Filter
	// =========================================================================

	/**
	 * Test filter shows all fields for new listings (ID 0).
	 */
	public function test_filter_shows_all_fields_for_new_listings(): void {
		$this->setup_multiple_types();

		$field = [
			'name'         => 'website_url',
			'listing_type' => 'url-directory',
		];

		$result = $this->meta_box->filter_field_display( true, $field, 'admin', 0 );

		$this->assertTrue( $result );
	}

	/**
	 * Test filter shows global fields (listing_type null) for any type.
	 */
	public function test_filter_shows_global_fields(): void {
		$this->setup_multiple_types();

		$field = [
			'name'         => 'phone',
			'listing_type' => null,
		];

		$result = $this->meta_box->filter_field_display( true, $field, 'admin', 123 );

		$this->assertTrue( $result );
	}

	/**
	 * Test filter always shows fields in admin context (JS handles toggling).
	 */
	public function test_filter_always_shows_fields_in_admin(): void {
		$this->setup_multiple_types();

		// Listing is 'general', field is for 'url-directory'.
		$field = [
			'name'         => 'website_url',
			'listing_type' => 'url-directory',
		];

		// Admin context: always true so JS can toggle visibility.
		$result = $this->meta_box->filter_field_display( true, $field, 'admin', 123 );

		$this->assertTrue( $result );
	}

	/**
	 * Test filter hides type-specific field for wrong type in frontend context.
	 */
	public function test_filter_hides_field_for_wrong_type_frontend(): void {
		$this->setup_multiple_types();

		// Listing is 'general', field is for 'url-directory'.
		$field = [
			'name'         => 'website_url',
			'listing_type' => 'url-directory',
		];

		$result = $this->meta_box->filter_field_display( true, $field, 'frontend', 123 );

		$this->assertFalse( $result );
	}

	/**
	 * Test filter shows type-specific field for matching type.
	 */
	public function test_filter_shows_field_for_matching_type(): void {
		$this->setup_multiple_types();

		// Make listing type url-directory.
		Functions\when( 'wp_get_object_terms' )->alias( function () {
			$term       = Mockery::mock( WP_Term::class );
			$term->slug = 'url-directory';
			$term->name = 'URL Directory';
			return [ $term ];
		} );

		$field = [
			'name'         => 'website_url',
			'listing_type' => 'url-directory',
		];

		$result = $this->meta_box->filter_field_display( true, $field, 'admin', 123 );

		$this->assertTrue( $result );
	}

	/**
	 * Test filter handles array listing_type in frontend context.
	 */
	public function test_filter_handles_array_listing_type_frontend(): void {
		$this->setup_multiple_types();

		$field = [
			'name'         => 'shared_field',
			'listing_type' => [ 'url-directory', 'venue' ],
		];

		// Current type is 'general' - should not match in frontend.
		$result = $this->meta_box->filter_field_display( true, $field, 'frontend', 123 );
		$this->assertFalse( $result );

		// Admin context always returns true.
		$result = $this->meta_box->filter_field_display( true, $field, 'admin', 123 );
		$this->assertTrue( $result );
	}

	/**
	 * Test filter respects already-hidden fields.
	 */
	public function test_filter_respects_already_hidden(): void {
		$field = [
			'name'         => 'test_field',
			'listing_type' => null,
		];

		$result = $this->meta_box->filter_field_display( false, $field, 'admin', 123 );

		$this->assertFalse( $result );
	}

	/**
	 * Test filter skips when only one type exists.
	 */
	public function test_filter_skips_with_single_type(): void {
		$terms = $this->create_mock_terms( [ 'general' => 'General' ] );
		Functions\when( 'get_terms' )->justReturn( $terms );

		$field = [
			'name'         => 'website_url',
			'listing_type' => 'url-directory',
		];

		$result = $this->meta_box->filter_field_display( true, $field, 'admin', 123 );

		$this->assertTrue( $result );
	}

	// =========================================================================
	// Hidden Fields by Module
	// =========================================================================

	/**
	 * Test is_field_hidden_by_module returns true when module hides the field.
	 */
	public function test_field_hidden_by_module(): void {
		$this->module_registry->register( 'url-directory', [
			'name'          => 'URL Directory',
			'hidden_fields' => [ 'website' ],
		] );

		$result = $this->meta_box->is_field_hidden_by_module( 'website', 'url-directory' );

		$this->assertTrue( $result );
	}

	/**
	 * Test is_field_hidden_by_module returns false for different type.
	 */
	public function test_field_not_hidden_for_different_type(): void {
		$this->module_registry->register( 'url-directory', [
			'name'          => 'URL Directory',
			'hidden_fields' => [ 'website' ],
		] );

		$result = $this->meta_box->is_field_hidden_by_module( 'website', 'general' );

		$this->assertFalse( $result );
	}

	/**
	 * Test filter_field_display shows field in admin even when module hides it
	 * (JS handles visibility), but hides in frontend context.
	 */
	public function test_filter_module_hidden_fields_admin_vs_frontend(): void {
		$this->setup_multiple_types();

		$this->module_registry->register( 'url-directory', [
			'name'          => 'URL Directory',
			'hidden_fields' => [ 'website' ],
		] );

		// Make listing type url-directory.
		Functions\when( 'wp_get_object_terms' )->alias( function () {
			$term       = Mockery::mock( WP_Term::class );
			$term->slug = 'url-directory';
			$term->name = 'URL Directory';
			return [ $term ];
		} );

		// Global field 'website' should be hidden for url-directory.
		$field = [
			'name'         => 'website',
			'listing_type' => null,
		];

		// Admin context: always returns true (JS handles toggling).
		$result = $this->meta_box->filter_field_display( true, $field, 'admin', 123 );
		$this->assertTrue( $result );

		// Frontend context: hidden by module.
		$result = $this->meta_box->filter_field_display( true, $field, 'frontend', 123 );
		$this->assertFalse( $result );
	}

	// =========================================================================
	// Field Type Mapping
	// =========================================================================

	/**
	 * Test build_field_type_mapping includes type-specific fields.
	 */
	public function test_build_mapping_includes_type_specific(): void {
		$this->registry->register_field( 'global_phone', [
			'type'         => 'phone',
			'listing_type' => null,
		] );
		$this->registry->register_field( 'website_url', [
			'type'         => 'url',
			'listing_type' => 'url-directory',
		] );

		$mapping = $this->meta_box->build_field_type_mapping();

		$this->assertArrayHasKey( 'website_url', $mapping );
		$this->assertSame( 'url-directory', $mapping['website_url'] );
		$this->assertArrayNotHasKey( 'global_phone', $mapping );
	}

	/**
	 * Test build_field_type_mapping includes hidden-by-module entries.
	 */
	public function test_build_mapping_includes_hidden_by_module(): void {
		$this->registry->register_field( 'website', [
			'type'         => 'url',
			'listing_type' => null,
		] );

		$this->module_registry->register( 'url-directory', [
			'name'          => 'URL Directory',
			'hidden_fields' => [ 'website' ],
		] );

		$mapping = $this->meta_box->build_field_type_mapping();

		$this->assertArrayHasKey( 'website', $mapping );
		$this->assertIsArray( $mapping['website'] );
		$this->assertSame( [ 'url-directory' ], $mapping['website']['hidden_by'] );
	}

	// =========================================================================
	// Constants
	// =========================================================================

	/**
	 * Test class constants.
	 */
	public function test_class_constants(): void {
		$this->assertSame( 'apd_listing_type_selector', ListingTypeMetaBox::META_BOX_ID );
		$this->assertSame( 'apd_save_listing_type', ListingTypeMetaBox::NONCE_ACTION );
		$this->assertSame( 'apd_listing_type_nonce', ListingTypeMetaBox::NONCE_NAME );
		$this->assertSame( 'apd_listing', ListingTypeMetaBox::POST_TYPE );
	}

	// =========================================================================
	// has_multiple_listing_types
	// =========================================================================

	/**
	 * Test has_multiple_listing_types returns false with 1 type.
	 */
	public function test_has_multiple_types_false_with_one(): void {
		$terms = $this->create_mock_terms( [ 'general' => 'General' ] );
		Functions\when( 'get_terms' )->justReturn( $terms );

		$this->assertFalse( $this->meta_box->has_multiple_listing_types() );
	}

	/**
	 * Test has_multiple_listing_types returns true with 2+ types.
	 */
	public function test_has_multiple_types_true_with_two(): void {
		$this->setup_multiple_types();

		$this->assertTrue( $this->meta_box->has_multiple_listing_types() );
	}
}
