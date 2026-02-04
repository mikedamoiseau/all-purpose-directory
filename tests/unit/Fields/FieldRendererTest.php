<?php
/**
 * Unit tests for FieldRenderer.
 *
 * @package APD\Tests\Unit\Fields
 */

declare(strict_types=1);

namespace APD\Tests\Unit\Fields;

use APD\Contracts\FieldTypeInterface;
use APD\Fields\FieldRegistry;
use APD\Fields\FieldRenderer;
use APD\Fields\Types\TextField;
use APD\Fields\Types\SelectField;
use APD\Fields\Types\CheckboxField;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WP_Error;

/**
 * Class FieldRendererTest
 */
class FieldRendererTest extends TestCase {

	/**
	 * Field registry instance.
	 *
	 * @var FieldRegistry
	 */
	private FieldRegistry $registry;

	/**
	 * Field renderer instance.
	 *
	 * @var FieldRenderer
	 */
	private FieldRenderer $renderer;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Mock WordPress functions.
		Functions\stubs( [
			'wp_parse_args' => function ( $args, $defaults ) {
				return array_merge( $defaults, $args );
			},
			'esc_attr'      => function ( $text ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
			},
			'esc_html'      => function ( $text ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
			},
			'esc_html__'    => function ( $text ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
			},
			'__'            => function ( $text ) {
				return $text;
			},
			'sanitize_key'  => function ( $key ) {
				return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
			},
			'absint'        => function ( $value ) {
				return abs( (int) $value );
			},
			'selected'      => function ( $selected, $current, $echo = true ) {
				$result = $selected === $current ? ' selected="selected"' : '';
				if ( $echo ) {
					echo $result;
				}
				return $result;
			},
			'wp_nonce_field' => function ( $action, $name, $referer = true, $echo = true ) {
				$html = '<input type="hidden" name="' . $name . '" value="nonce_value">';
				if ( $echo ) {
					echo $html;
				}
				return $html;
			},
			'get_post_meta' => function ( $post_id, $key, $single = false ) {
				return '';
			},
			'apply_filters' => function ( $tag, $value, ...$args ) {
				return $value;
			},
			'do_action'     => function ( $tag, ...$args ) {},
		] );

		// Get fresh registry instance.
		$this->registry = FieldRegistry::get_instance();
		$this->registry->reset();

		// Register field types.
		$this->registry->register_field_type( new TextField() );
		$this->registry->register_field_type( new SelectField() );
		$this->registry->register_field_type( new CheckboxField() );

		// Create renderer.
		$this->renderer = new FieldRenderer( $this->registry );
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		$this->registry->reset();
		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// Context Tests
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function it_defaults_to_admin_context(): void {
		$this->assertSame( FieldRenderer::CONTEXT_ADMIN, $this->renderer->get_context() );
	}

	/**
	 * @test
	 */
	public function it_can_set_context_to_frontend(): void {
		$this->renderer->set_context( FieldRenderer::CONTEXT_FRONTEND );
		$this->assertSame( FieldRenderer::CONTEXT_FRONTEND, $this->renderer->get_context() );
	}

	/**
	 * @test
	 */
	public function it_can_set_context_to_display(): void {
		$this->renderer->set_context( FieldRenderer::CONTEXT_DISPLAY );
		$this->assertSame( FieldRenderer::CONTEXT_DISPLAY, $this->renderer->get_context() );
	}

	/**
	 * @test
	 */
	public function it_ignores_invalid_context(): void {
		$this->renderer->set_context( 'invalid' );
		$this->assertSame( FieldRenderer::CONTEXT_ADMIN, $this->renderer->get_context() );
	}

	/**
	 * @test
	 */
	public function set_context_returns_self_for_chaining(): void {
		$result = $this->renderer->set_context( FieldRenderer::CONTEXT_FRONTEND );
		$this->assertSame( $this->renderer, $result );
	}

	// -------------------------------------------------------------------------
	// Error Handling Tests
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function it_can_set_errors_as_array(): void {
		$errors = [
			'email' => [ 'Invalid email format' ],
			'name'  => [ 'Name is required', 'Name too short' ],
		];

		$this->renderer->set_errors( $errors );
		$this->assertSame( $errors, $this->renderer->get_errors() );
	}

	/**
	 * @test
	 */
	public function it_can_set_errors_from_wp_error(): void {
		$wp_error = new WP_Error();
		$wp_error->add( 'email', 'Invalid email format' );
		$wp_error->add( 'name', 'Name is required' );

		$this->renderer->set_errors( $wp_error );

		$errors = $this->renderer->get_errors();
		$this->assertArrayHasKey( 'email', $errors );
		$this->assertArrayHasKey( 'name', $errors );
		$this->assertContains( 'Invalid email format', $errors['email'] );
		$this->assertContains( 'Name is required', $errors['name'] );
	}

	/**
	 * @test
	 */
	public function it_can_clear_errors(): void {
		$this->renderer->set_errors( [ 'field' => [ 'Error' ] ] );
		$this->renderer->clear_errors();
		$this->assertEmpty( $this->renderer->get_errors() );
	}

	/**
	 * @test
	 */
	public function set_errors_returns_self_for_chaining(): void {
		$result = $this->renderer->set_errors( [] );
		$this->assertSame( $this->renderer, $result );
	}

	/**
	 * @test
	 */
	public function clear_errors_returns_self_for_chaining(): void {
		$result = $this->renderer->clear_errors();
		$this->assertSame( $this->renderer, $result );
	}

	// -------------------------------------------------------------------------
	// Group Registration Tests
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function it_can_register_group(): void {
		$this->renderer->register_group( 'basic', [
			'title'  => 'Basic Info',
			'fields' => [ 'name', 'email' ],
		] );

		$groups = $this->renderer->get_groups();
		$this->assertArrayHasKey( 'basic', $groups );
		$this->assertSame( 'Basic Info', $groups['basic']['title'] );
	}

	/**
	 * @test
	 */
	public function it_applies_default_group_config(): void {
		$this->renderer->register_group( 'minimal', [
			'title' => 'Minimal',
		] );

		$groups = $this->renderer->get_groups();
		$this->assertSame( '', $groups['minimal']['description'] );
		$this->assertSame( 10, $groups['minimal']['priority'] );
		$this->assertFalse( $groups['minimal']['collapsible'] );
		$this->assertFalse( $groups['minimal']['collapsed'] );
		$this->assertSame( [], $groups['minimal']['fields'] );
	}

	/**
	 * @test
	 */
	public function it_can_unregister_group(): void {
		$this->renderer->register_group( 'test', [ 'title' => 'Test' ] );
		$this->renderer->unregister_group( 'test' );

		$groups = $this->renderer->get_groups();
		$this->assertArrayNotHasKey( 'test', $groups );
	}

	/**
	 * @test
	 */
	public function register_group_returns_self_for_chaining(): void {
		$result = $this->renderer->register_group( 'test', [] );
		$this->assertSame( $this->renderer, $result );
	}

	/**
	 * @test
	 */
	public function groups_are_sorted_by_priority(): void {
		$this->renderer->register_group( 'third', [ 'title' => 'Third', 'priority' => 30 ] );
		$this->renderer->register_group( 'first', [ 'title' => 'First', 'priority' => 10 ] );
		$this->renderer->register_group( 'second', [ 'title' => 'Second', 'priority' => 20 ] );

		$groups = $this->renderer->get_groups();
		$keys   = array_keys( $groups );

		$this->assertSame( [ 'first', 'second', 'third' ], $keys );
	}

	/**
	 * @test
	 */
	public function it_can_reset_groups(): void {
		$this->renderer->register_group( 'test', [ 'title' => 'Test' ] );
		$this->renderer->reset_groups();

		$this->assertEmpty( $this->renderer->get_groups() );
	}

	// -------------------------------------------------------------------------
	// Render Single Field Tests
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function it_renders_empty_string_for_unregistered_field(): void {
		$html = $this->renderer->render_field( 'nonexistent' );
		$this->assertSame( '', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_text_field(): void {
		$this->registry->register_field( 'username', [
			'type'  => 'text',
			'label' => 'Username',
		] );

		$html = $this->renderer->render_field( 'username', 'john_doe' );

		$this->assertStringContainsString( 'apd-field', $html );
		$this->assertStringContainsString( 'data-field-name="username"', $html );
		$this->assertStringContainsString( 'data-field-type="text"', $html );
		$this->assertStringContainsString( 'Username', $html );
		$this->assertStringContainsString( 'value="john_doe"', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_required_field_with_indicator(): void {
		$this->registry->register_field( 'email', [
			'type'     => 'text',
			'label'    => 'Email',
			'required' => true,
		] );

		$html = $this->renderer->render_field( 'email', '' );

		$this->assertStringContainsString( 'apd-field--required', $html );
		$this->assertStringContainsString( 'apd-field__required-indicator', $html );
		$this->assertStringContainsString( '*', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_field_with_errors(): void {
		$this->registry->register_field( 'email', [
			'type'  => 'text',
			'label' => 'Email',
		] );

		$this->renderer->set_errors( [
			'email' => [ 'Invalid email format' ],
		] );

		$html = $this->renderer->render_field( 'email', 'invalid' );

		$this->assertStringContainsString( 'apd-field--has-error', $html );
		$this->assertStringContainsString( 'apd-field__errors', $html );
		$this->assertStringContainsString( 'Invalid email format', $html );
		$this->assertStringContainsString( 'role="alert"', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_admin_field_with_admin_class(): void {
		$this->registry->register_field( 'title', [
			'type'  => 'text',
			'label' => 'Title',
		] );

		$this->renderer->set_context( FieldRenderer::CONTEXT_ADMIN );
		$html = $this->renderer->render_field( 'title', '' );

		$this->assertStringContainsString( 'apd-field--admin', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_frontend_field_with_frontend_class(): void {
		$this->registry->register_field( 'title', [
			'type'  => 'text',
			'label' => 'Title',
		] );

		$this->renderer->set_context( FieldRenderer::CONTEXT_FRONTEND );
		$html = $this->renderer->render_field( 'title', '' );

		$this->assertStringContainsString( 'apd-field--frontend', $html );
	}

	/**
	 * @test
	 */
	public function it_hides_admin_only_fields_in_frontend_context(): void {
		$this->registry->register_field( 'internal_notes', [
			'type'       => 'text',
			'label'      => 'Internal Notes',
			'admin_only' => true,
		] );

		$this->renderer->set_context( FieldRenderer::CONTEXT_FRONTEND );
		$html = $this->renderer->render_field( 'internal_notes', 'secret' );

		$this->assertSame( '', $html );
	}

	/**
	 * @test
	 */
	public function it_shows_admin_only_fields_in_admin_context(): void {
		$this->registry->register_field( 'internal_notes', [
			'type'       => 'text',
			'label'      => 'Internal Notes',
			'admin_only' => true,
		] );

		$this->renderer->set_context( FieldRenderer::CONTEXT_ADMIN );
		$html = $this->renderer->render_field( 'internal_notes', 'secret' );

		$this->assertStringContainsString( 'Internal Notes', $html );
	}

	/**
	 * @test
	 */
	public function it_uses_field_default_when_value_is_null(): void {
		$this->registry->register_field( 'country', [
			'type'    => 'text',
			'label'   => 'Country',
			'default' => 'USA',
		] );

		$html = $this->renderer->render_field( 'country', null );

		$this->assertStringContainsString( 'value="USA"', $html );
	}

	// -------------------------------------------------------------------------
	// Display Context Rendering Tests
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function it_renders_field_for_display(): void {
		$this->registry->register_field( 'company', [
			'type'  => 'text',
			'label' => 'Company',
		] );

		$this->renderer->set_context( FieldRenderer::CONTEXT_DISPLAY );
		$html = $this->renderer->render_field( 'company', 'Acme Inc' );

		$this->assertStringContainsString( 'apd-field-display', $html );
		$this->assertStringContainsString( 'apd-field-display__label', $html );
		$this->assertStringContainsString( 'apd-field-display__value', $html );
		$this->assertStringContainsString( 'Company', $html );
		$this->assertStringContainsString( 'Acme Inc', $html );
	}

	/**
	 * @test
	 */
	public function it_skips_empty_values_in_display_context(): void {
		$this->registry->register_field( 'optional', [
			'type'  => 'text',
			'label' => 'Optional',
		] );

		$this->renderer->set_context( FieldRenderer::CONTEXT_DISPLAY );
		$html = $this->renderer->render_field( 'optional', '' );

		$this->assertSame( '', $html );
	}

	/**
	 * @test
	 */
	public function it_skips_empty_array_values_in_display_context(): void {
		$this->registry->register_field( 'tags', [
			'type'  => 'select',
			'label' => 'Tags',
		] );

		$this->renderer->set_context( FieldRenderer::CONTEXT_DISPLAY );
		$html = $this->renderer->render_field( 'tags', [] );

		$this->assertSame( '', $html );
	}

	// -------------------------------------------------------------------------
	// Render Multiple Fields Tests
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function it_renders_multiple_fields(): void {
		$this->registry->register_field( 'first_name', [
			'type'  => 'text',
			'label' => 'First Name',
		] );
		$this->registry->register_field( 'last_name', [
			'type'  => 'text',
			'label' => 'Last Name',
		] );

		$html = $this->renderer->render_fields( [
			'first_name' => 'John',
			'last_name'  => 'Doe',
		] );

		$this->assertStringContainsString( 'First Name', $html );
		$this->assertStringContainsString( 'John', $html );
		$this->assertStringContainsString( 'Last Name', $html );
		$this->assertStringContainsString( 'Doe', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_only_specified_fields(): void {
		$this->registry->register_field( 'field_a', [ 'type' => 'text', 'label' => 'Field A' ] );
		$this->registry->register_field( 'field_b', [ 'type' => 'text', 'label' => 'Field B' ] );
		$this->registry->register_field( 'field_c', [ 'type' => 'text', 'label' => 'Field C' ] );

		$html = $this->renderer->render_fields( [], [ 'fields' => [ 'field_a', 'field_c' ] ] );

		$this->assertStringContainsString( 'Field A', $html );
		$this->assertStringNotContainsString( 'Field B', $html );
		$this->assertStringContainsString( 'Field C', $html );
	}

	/**
	 * @test
	 */
	public function it_excludes_specified_fields(): void {
		$this->registry->register_field( 'include_me', [ 'type' => 'text', 'label' => 'Include Me' ] );
		$this->registry->register_field( 'exclude_me', [ 'type' => 'text', 'label' => 'Exclude Me' ] );

		$html = $this->renderer->render_fields( [], [ 'exclude' => [ 'exclude_me' ] ] );

		$this->assertStringContainsString( 'Include Me', $html );
		$this->assertStringNotContainsString( 'Exclude Me', $html );
	}

	// -------------------------------------------------------------------------
	// Group Rendering Tests
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function it_renders_field_group(): void {
		$this->registry->register_field( 'contact_email', [ 'type' => 'text', 'label' => 'Email' ] );
		$this->registry->register_field( 'contact_phone', [ 'type' => 'text', 'label' => 'Phone' ] );

		$this->renderer->register_group( 'contact', [
			'title'       => 'Contact Information',
			'description' => 'How to reach you',
			'fields'      => [ 'contact_email', 'contact_phone' ],
		] );

		$html = $this->renderer->render_group( 'contact', $this->renderer->get_groups()['contact'] );

		$this->assertStringContainsString( 'apd-field-group', $html );
		$this->assertStringContainsString( 'Contact Information', $html );
		$this->assertStringContainsString( 'How to reach you', $html );
		$this->assertStringContainsString( 'Email', $html );
		$this->assertStringContainsString( 'Phone', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_collapsible_group(): void {
		$this->registry->register_field( 'advanced_field', [ 'type' => 'text', 'label' => 'Advanced' ] );

		$this->renderer->register_group( 'advanced', [
			'title'       => 'Advanced Settings',
			'collapsible' => true,
			'collapsed'   => false,
			'fields'      => [ 'advanced_field' ],
		] );

		$html = $this->renderer->render_group( 'advanced', $this->renderer->get_groups()['advanced'] );

		$this->assertStringContainsString( 'apd-field-group--collapsible', $html );
		$this->assertStringContainsString( 'apd-field-group__toggle', $html );
		$this->assertStringContainsString( 'aria-expanded="true"', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_collapsed_group(): void {
		$this->registry->register_field( 'hidden_field', [ 'type' => 'text', 'label' => 'Hidden' ] );

		$this->renderer->register_group( 'hidden', [
			'title'       => 'Hidden Section',
			'collapsible' => true,
			'collapsed'   => true,
			'fields'      => [ 'hidden_field' ],
		] );

		$html = $this->renderer->render_group( 'hidden', $this->renderer->get_groups()['hidden'] );

		$this->assertStringContainsString( 'apd-field-group--collapsed', $html );
		$this->assertStringContainsString( 'aria-expanded="false"', $html );
		$this->assertStringContainsString( 'hidden="hidden"', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_grouped_fields_with_ungrouped(): void {
		$this->registry->register_field( 'grouped', [ 'type' => 'text', 'label' => 'Grouped Field' ] );
		$this->registry->register_field( 'ungrouped', [ 'type' => 'text', 'label' => 'Ungrouped Field' ] );

		$this->renderer->register_group( 'main', [
			'title'  => 'Main Group',
			'fields' => [ 'grouped' ],
		] );

		$html = $this->renderer->render_grouped_fields();

		$this->assertStringContainsString( 'Main Group', $html );
		$this->assertStringContainsString( 'Grouped Field', $html );
		$this->assertStringContainsString( 'Ungrouped Field', $html );
	}

	/**
	 * @test
	 */
	public function it_returns_empty_for_group_with_no_fields(): void {
		$html = $this->renderer->render_group( 'empty', [ 'title' => 'Empty', 'fields' => [] ] );
		$this->assertSame( '', $html );
	}

	/**
	 * @test
	 */
	public function it_returns_empty_for_group_with_all_hidden_fields(): void {
		$this->registry->register_field( 'admin_only_field', [
			'type'       => 'text',
			'label'      => 'Admin Only',
			'admin_only' => true,
		] );

		$this->renderer->set_context( FieldRenderer::CONTEXT_FRONTEND );
		$html = $this->renderer->render_group( 'admin_group', [
			'title'  => 'Admin Group',
			'fields' => [ 'admin_only_field' ],
		] );

		$this->assertSame( '', $html );
	}

	// -------------------------------------------------------------------------
	// Admin Fields Rendering Tests
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function it_renders_admin_fields_with_nonce(): void {
		$this->registry->register_field( 'admin_field', [ 'type' => 'text', 'label' => 'Admin Field' ] );

		$html = $this->renderer->render_admin_fields( 123 );

		$this->assertStringContainsString( 'apd_fields_nonce', $html );
		$this->assertStringContainsString( 'nonce_value', $html );
		$this->assertStringContainsString( 'Admin Field', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_admin_fields_with_custom_nonce(): void {
		$this->registry->register_field( 'test', [ 'type' => 'text', 'label' => 'Test' ] );

		$html = $this->renderer->render_admin_fields( 123, [
			'nonce_action' => 'custom_action',
			'nonce_name'   => 'custom_nonce',
		] );

		$this->assertStringContainsString( 'custom_nonce', $html );
	}

	// -------------------------------------------------------------------------
	// Frontend Fields Rendering Tests
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function it_renders_frontend_fields_with_nonce(): void {
		$this->registry->register_field( 'frontend_field', [ 'type' => 'text', 'label' => 'Frontend Field' ] );

		$html = $this->renderer->render_frontend_fields();

		$this->assertStringContainsString( 'apd_submission_nonce', $html );
		$this->assertStringContainsString( 'Frontend Field', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_frontend_fields_with_listing_id_for_editing(): void {
		$this->registry->register_field( 'editable', [ 'type' => 'text', 'label' => 'Editable' ] );

		$html = $this->renderer->render_frontend_fields( 456 );

		$this->assertStringContainsString( 'apd_listing_id', $html );
		$this->assertStringContainsString( 'value="456"', $html );
	}

	/**
	 * @test
	 */
	public function it_excludes_admin_only_fields_from_frontend(): void {
		$this->registry->register_field( 'public_field', [ 'type' => 'text', 'label' => 'Public' ] );
		$this->registry->register_field( 'private_field', [
			'type'       => 'text',
			'label'      => 'Private',
			'admin_only' => true,
		] );

		$html = $this->renderer->render_frontend_fields();

		$this->assertStringContainsString( 'Public', $html );
		$this->assertStringNotContainsString( 'Private', $html );
	}

	/**
	 * @test
	 */
	public function it_uses_submitted_values_for_new_listing(): void {
		$this->registry->register_field( 'submitted', [ 'type' => 'text', 'label' => 'Submitted' ] );

		$html = $this->renderer->render_frontend_fields( 0, [
			'submitted_values' => [ 'submitted' => 'user input' ],
		] );

		$this->assertStringContainsString( 'user input', $html );
	}

	// -------------------------------------------------------------------------
	// Display Fields Rendering Tests
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function it_renders_display_fields_as_definition_list(): void {
		$this->registry->register_field( 'display_field', [ 'type' => 'text', 'label' => 'Display' ] );

		// Mock get_post_meta to return a value.
		Functions\expect( 'get_post_meta' )
			->andReturn( 'stored value' );

		$html = $this->renderer->render_display_fields( 789 );

		$this->assertStringContainsString( 'apd-field-display-list', $html );
		$this->assertStringContainsString( '<dl', $html );
		$this->assertStringContainsString( '</dl>', $html );
	}

	/**
	 * @test
	 */
	public function it_excludes_admin_only_from_display(): void {
		$this->registry->register_field( 'public_display', [ 'type' => 'text', 'label' => 'Public' ] );
		$this->registry->register_field( 'admin_display', [
			'type'       => 'text',
			'label'      => 'Admin',
			'admin_only' => true,
		] );

		// Render display fields with pre-populated values.
		$this->renderer->set_context( FieldRenderer::CONTEXT_DISPLAY );
		$html = $this->renderer->render_fields(
			[
				'public_display' => 'public value',
				'admin_display'  => 'admin value',
			],
			[],
			100
		);

		$this->assertStringContainsString( 'Public', $html );
		$this->assertStringNotContainsString( 'Admin', $html );
	}

	// -------------------------------------------------------------------------
	// Select Field Rendering Tests
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function it_renders_select_field_with_options(): void {
		$this->registry->register_field( 'country', [
			'type'         => 'select',
			'label'        => 'Country',
			'options'      => [
				'us' => 'United States',
				'uk' => 'United Kingdom',
				'ca' => 'Canada',
			],
			'empty_option' => 'Select a country',
		] );

		$html = $this->renderer->render_field( 'country', 'uk' );

		$this->assertStringContainsString( '<select', $html );
		$this->assertStringContainsString( 'Select a country', $html );
		$this->assertStringContainsString( 'United States', $html );
		$this->assertStringContainsString( 'United Kingdom', $html );
		$this->assertStringContainsString( 'Canada', $html );
	}

	// -------------------------------------------------------------------------
	// Checkbox Field Rendering Tests
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function it_renders_checkbox_field(): void {
		$this->registry->register_field( 'subscribe', [
			'type'  => 'checkbox',
			'label' => 'Subscribe to newsletter',
		] );

		$html = $this->renderer->render_field( 'subscribe', '1' );

		$this->assertStringContainsString( 'type="checkbox"', $html );
		$this->assertStringContainsString( 'Subscribe to newsletter', $html );
	}

	// -------------------------------------------------------------------------
	// Edge Cases
	// -------------------------------------------------------------------------

	/**
	 * @test
	 */
	public function it_handles_field_with_auto_generated_label(): void {
		// Note: FieldRegistry auto-generates a label from field name if not provided.
		// 'no_label' becomes 'No Label'.
		$this->registry->register_field( 'my_field', [
			'type'  => 'text',
			'label' => '',
		] );

		$html = $this->renderer->render_field( 'my_field', '' );

		// Should render with auto-generated label.
		$this->assertStringContainsString( 'apd-field__input', $html );
		$this->assertStringContainsString( '<label', $html );
		$this->assertStringContainsString( 'My Field', $html );
	}

	/**
	 * @test
	 */
	public function it_handles_missing_field_type_handler(): void {
		// Register field with type that has no handler.
		$this->registry->register_field( 'custom', [
			'type'  => 'custom_type',
			'label' => 'Custom',
		] );

		$html = $this->renderer->render_field( 'custom', 'value' );

		$this->assertSame( '', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_multiple_errors_for_single_field(): void {
		$this->registry->register_field( 'password', [
			'type'  => 'text',
			'label' => 'Password',
		] );

		$this->renderer->set_errors( [
			'password' => [
				'Password is too short',
				'Password must contain a number',
				'Password must contain a special character',
			],
		] );

		$html = $this->renderer->render_field( 'password', 'weak' );

		$this->assertStringContainsString( 'Password is too short', $html );
		$this->assertStringContainsString( 'Password must contain a number', $html );
		$this->assertStringContainsString( 'Password must contain a special character', $html );
	}

	/**
	 * @test
	 */
	public function it_renders_grouped_fields_without_groups_registered(): void {
		$this->registry->register_field( 'lone_field', [ 'type' => 'text', 'label' => 'Lone Field' ] );

		$html = $this->renderer->render_grouped_fields();

		$this->assertStringContainsString( 'Lone Field', $html );
		$this->assertStringNotContainsString( 'apd-field-group', $html );
	}
}
