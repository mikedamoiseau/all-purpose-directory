<?php
/**
 * Listing Meta Box.
 *
 * Handles the meta box for custom listing fields on the apd_listing post type
 * edit screen. Renders registered fields and saves field values with proper
 * validation and sanitization.
 *
 * @package APD\Admin
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Admin;

use WP_Post;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ListingMetaBox
 *
 * Manages the listing fields meta box in the WordPress admin.
 *
 * @since 1.0.0
 */
final class ListingMetaBox {

	/**
	 * Meta box ID.
	 */
	public const META_BOX_ID = 'apd_listing_fields';

	/**
	 * Nonce action for saving fields.
	 */
	public const NONCE_ACTION = 'apd_save_listing_fields';

	/**
	 * Nonce field name.
	 */
	public const NONCE_NAME = 'apd_fields_nonce';

	/**
	 * Post type for the listing.
	 */
	public const POST_TYPE = 'apd_listing';

	/**
	 * Initialize the meta box hooks.
	 *
	 * Registers the meta box and save handlers.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		// Only run in admin context.
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ $this, 'save_meta_box' ], 10, 2 );
	}

	/**
	 * Register the listing fields meta box.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_meta_box(): void {
		add_meta_box(
			self::META_BOX_ID,
			__( 'Listing Fields', 'all-purpose-directory' ),
			[ $this, 'render_meta_box' ],
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box content.
	 *
	 * Outputs all registered listing fields using the field renderer.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post The current post object.
	 * @return void
	 */
	public function render_meta_box( WP_Post $post ): void {
		// Check if any fields are registered.
		$fields = apd_get_fields();

		if ( empty( $fields ) ) {
			printf(
				'<p class="apd-no-fields">%s</p>',
				esc_html__( 'No custom fields have been registered for listings.', 'all-purpose-directory' )
			);
			return;
		}

		// Render the admin fields with nonce.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output escaped in apd_render_admin_fields().
		echo apd_render_admin_fields(
			$post->ID,
			[
				'nonce_action' => self::NONCE_ACTION,
				'nonce_name'   => self::NONCE_NAME,
			]
		);
	}

	/**
	 * Save the meta box field values.
	 *
	 * Validates, sanitizes, and saves all submitted field values.
	 * Includes nonce verification, autosave handling, and capability checks.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $post_id The post ID being saved.
	 * @param WP_Post $post    The post object being saved.
	 * @return void
	 */
	public function save_meta_box( int $post_id, WP_Post $post ): void {
		// Verify nonce.
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification.
		if ( ! wp_verify_nonce( wp_unslash( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION ) ) {
			return;
		}

		// Skip autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check user capabilities.
		if ( ! current_user_can( 'edit_apd_listing', $post_id ) ) {
			return;
		}

		// Verify this is the correct post type.
		if ( $post->post_type !== self::POST_TYPE ) {
			return;
		}

		// Get registered fields.
		$fields = apd_get_fields();

		if ( empty( $fields ) ) {
			return;
		}

		// Extract field values from POST data.
		$values = $this->extract_field_values( $fields );

		/**
		 * Fires before listing field values are saved.
		 *
		 * Allows modification of values or additional processing before save.
		 *
		 * @since 1.0.0
		 *
		 * @param int                  $post_id The listing post ID.
		 * @param array<string, mixed> $values  Field values keyed by field name.
		 */
		do_action( 'apd_before_listing_save', $post_id, $values );

		// Process (sanitize and validate) field values.
		$result = apd_process_fields( $values );

		// If validation fails, store errors for display.
		// Note: WordPress admin doesn't easily support displaying validation errors
		// on post save, but we set them for hooks that might use them.
		if ( ! $result['valid'] && $result['errors'] !== null ) {
			apd_set_field_errors( $result['errors'] );
		}

		// Save sanitized values (even if some validation failed, save what we can).
		$sanitized_values = $result['values'];

		foreach ( $sanitized_values as $field_name => $value ) {
			apd_set_listing_field( $post_id, $field_name, $value );
		}

		/**
		 * Fires after listing field values are saved.
		 *
		 * Allows additional processing after fields have been saved.
		 *
		 * @since 1.0.0
		 *
		 * @param int                  $post_id The listing post ID.
		 * @param array<string, mixed> $values  Sanitized field values keyed by field name.
		 */
		do_action( 'apd_after_listing_save', $post_id, $sanitized_values );
	}

	/**
	 * Extract field values from POST data.
	 *
	 * Looks for field values in the POST data based on registered field names.
	 * Field values are expected in the format apd_field[field_name] or
	 * directly as the field name.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, array<string, mixed>> $fields Registered fields.
	 * @return array<string, mixed> Field values keyed by field name.
	 */
	private function extract_field_values( array $fields ): array {
		$values = [];

		// Check for apd_field array format first.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce already verified in save_meta_box.
		$apd_fields = isset( $_POST['apd_field'] ) && is_array( $_POST['apd_field'] )
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified, sanitization happens in apd_process_fields.
			? wp_unslash( $_POST['apd_field'] )
			: [];

		foreach ( $fields as $field_name => $field_config ) {
			// Try apd_field[field_name] format first.
			if ( isset( $apd_fields[ $field_name ] ) ) {
				$values[ $field_name ] = $apd_fields[ $field_name ];
				continue;
			}

			// Try direct field name (for checkbox fields that might not be set).
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce already verified.
			if ( isset( $_POST[ $field_name ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified, sanitization happens in apd_process_fields.
				$values[ $field_name ] = wp_unslash( $_POST[ $field_name ] );
				continue;
			}

			// Handle unchecked checkboxes - they don't appear in POST data.
			// Set to empty string so the field type can handle it.
			if ( isset( $field_config['type'] ) && in_array( $field_config['type'], [ 'checkbox', 'switch' ], true ) ) {
				$values[ $field_name ] = '';
			}
		}

		return $values;
	}
}
