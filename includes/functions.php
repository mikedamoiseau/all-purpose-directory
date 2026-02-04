<?php
/**
 * Global helper functions for All Purpose Directory.
 *
 * @package APD
 */

declare(strict_types=1);

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get the main plugin instance.
 *
 * @return \APD\Core\Plugin
 */
function apd(): \APD\Core\Plugin {
    return \APD\Core\Plugin::get_instance();
}

/**
 * Get a plugin option value.
 *
 * @param string $key     Option key.
 * @param mixed  $default Default value if option doesn't exist.
 * @return mixed
 */
function apd_get_option( string $key, mixed $default = null ): mixed {
    $options = get_option( 'apd_settings', [] );

    return $options[ $key ] ?? $default;
}

/**
 * Check if the current user can manage listings.
 *
 * @return bool
 */
function apd_current_user_can_manage_listings(): bool {
    return current_user_can( 'edit_apd_listings' );
}

/**
 * Get the listing post type name.
 *
 * @return string
 */
function apd_get_listing_post_type(): string {
    return 'apd_listing';
}

/**
 * Get the category taxonomy name.
 *
 * @return string
 */
function apd_get_category_taxonomy(): string {
    return 'apd_category';
}

/**
 * Get the tag taxonomy name.
 *
 * @return string
 */
function apd_get_tag_taxonomy(): string {
    return 'apd_tag';
}

/**
 * Check if a post is a listing.
 *
 * @param int|\WP_Post|null $post Post ID or post object.
 * @return bool
 */
function apd_is_listing( int|\WP_Post|null $post = null ): bool {
    $post = get_post( $post );

    if ( ! $post ) {
        return false;
    }

    return $post->post_type === apd_get_listing_post_type();
}

/**
 * Get listing meta value.
 *
 * @param int    $listing_id Listing post ID.
 * @param string $key        Meta key without prefix.
 * @param mixed  $default    Default value.
 * @return mixed
 */
function apd_get_listing_meta( int $listing_id, string $key, mixed $default = '' ): mixed {
    $value = get_post_meta( $listing_id, "_apd_{$key}", true );

    return $value !== '' ? $value : $default;
}

/**
 * Update listing meta value.
 *
 * @param int    $listing_id Listing post ID.
 * @param string $key        Meta key without prefix.
 * @param mixed  $value      Meta value.
 * @return int|bool
 */
function apd_update_listing_meta( int $listing_id, string $key, mixed $value ): int|bool {
    return update_post_meta( $listing_id, "_apd_{$key}", $value );
}

/**
 * Delete listing meta value.
 *
 * @param int    $listing_id Listing post ID.
 * @param string $key        Meta key without prefix.
 * @return bool
 */
function apd_delete_listing_meta( int $listing_id, string $key ): bool {
    return delete_post_meta( $listing_id, "_apd_{$key}" );
}

/**
 * Log a message for debugging.
 *
 * @param mixed  $message Message to log.
 * @param string $level   Log level (debug, info, warning, error).
 * @return void
 */
function apd_log( mixed $message, string $level = 'debug' ): void {
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
        return;
    }

    if ( is_array( $message ) || is_object( $message ) ) {
        $message = print_r( $message, true );
    }

    error_log( sprintf( '[APD %s] %s', strtoupper( $level ), $message ) );
}

/**
 * Get categories assigned to a listing.
 *
 * @param int $listing_id Listing post ID.
 * @return \WP_Term[] Array of WP_Term objects, or empty array if none.
 */
function apd_get_listing_categories( int $listing_id ): array {
    $terms = get_the_terms( $listing_id, apd_get_category_taxonomy() );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return [];
    }

    return $terms;
}

/**
 * Get tags assigned to a listing.
 *
 * @param int $listing_id Listing post ID.
 * @return \WP_Term[] Array of WP_Term objects, or empty array if none.
 */
function apd_get_listing_tags( int $listing_id ): array {
    $terms = get_the_terms( $listing_id, apd_get_tag_taxonomy() );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return [];
    }

    return $terms;
}

/**
 * Get listings in a specific category.
 *
 * @param int   $category_id   Category term ID.
 * @param array $args          Optional. Additional WP_Query args.
 * @return \WP_Post[] Array of WP_Post objects.
 */
function apd_get_category_listings( int $category_id, array $args = [] ): array {
    $defaults = [
        'post_type'      => apd_get_listing_post_type(),
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'tax_query'      => [
            [
                'taxonomy' => apd_get_category_taxonomy(),
                'field'    => 'term_id',
                'terms'    => $category_id,
            ],
        ],
    ];

    $query_args = wp_parse_args( $args, $defaults );

    // Allow filtering the query args.
    $query_args = apply_filters( 'apd_category_listings_query_args', $query_args, $category_id );

    $query = new \WP_Query( $query_args );

    return $query->posts;
}

/**
 * Get all categories with their listing counts.
 *
 * @param array $args Optional. Additional get_terms args.
 * @return \WP_Term[] Array of WP_Term objects with 'count' property.
 */
function apd_get_categories_with_count( array $args = [] ): array {
    $defaults = [
        'taxonomy'   => apd_get_category_taxonomy(),
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ];

    $query_args = wp_parse_args( $args, $defaults );

    // Allow filtering the query args.
    $query_args = apply_filters( 'apd_categories_with_count_args', $query_args );

    $terms = get_terms( $query_args );

    if ( is_wp_error( $terms ) ) {
        return [];
    }

    return $terms;
}

/**
 * Get category icon class (dashicon).
 *
 * @param int|\WP_Term $category Category term ID or object.
 * @return string Dashicon class or empty string.
 */
function apd_get_category_icon( int|\WP_Term $category ): string {
    $term_id = $category instanceof \WP_Term ? $category->term_id : $category;

    return \APD\Taxonomy\CategoryTaxonomy::get_icon( $term_id );
}

/**
 * Get category color (hex).
 *
 * @param int|\WP_Term $category Category term ID or object.
 * @return string Hex color or empty string.
 */
function apd_get_category_color( int|\WP_Term $category ): string {
    $term_id = $category instanceof \WP_Term ? $category->term_id : $category;

    return \APD\Taxonomy\CategoryTaxonomy::get_color( $term_id );
}

// ============================================================================
// Field Registry Functions
// ============================================================================

/**
 * Get the field registry instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Fields\FieldRegistry
 */
function apd_field_registry(): \APD\Fields\FieldRegistry {
    return \APD\Fields\FieldRegistry::get_instance();
}

/**
 * Register a custom field for listings.
 *
 * @since 1.0.0
 *
 * @param string $name   Unique field identifier (will be sanitized).
 * @param array  $config Field configuration array.
 *                       - type: (string) Field type (text, select, etc.).
 *                       - label: (string) Display label.
 *                       - description: (string) Help text.
 *                       - required: (bool) Whether field is required.
 *                       - default: (mixed) Default value.
 *                       - placeholder: (string) Placeholder text.
 *                       - options: (array) Options for select/radio/checkbox types.
 *                       - validation: (array) Validation rules.
 *                       - searchable: (bool) Include in search queries.
 *                       - filterable: (bool) Show in filter UI.
 *                       - admin_only: (bool) Hide from frontend.
 *                       - priority: (int) Display order (lower = earlier).
 * @return bool True if registered successfully.
 */
function apd_register_field( string $name, array $config = [] ): bool {
    return apd_field_registry()->register_field( $name, $config );
}

/**
 * Unregister a custom field.
 *
 * @since 1.0.0
 *
 * @param string $name Field name to unregister.
 * @return bool True if unregistered successfully.
 */
function apd_unregister_field( string $name ): bool {
    return apd_field_registry()->unregister_field( $name );
}

/**
 * Get a registered field configuration.
 *
 * @since 1.0.0
 *
 * @param string $name Field name.
 * @return array|null Field configuration or null if not found.
 */
function apd_get_field( string $name ): ?array {
    return apd_field_registry()->get_field( $name );
}

/**
 * Get all registered fields.
 *
 * @since 1.0.0
 *
 * @param array $args Optional. Arguments to filter fields.
 *                    - type: (string) Filter by field type.
 *                    - searchable: (bool) Filter by searchable flag.
 *                    - filterable: (bool) Filter by filterable flag.
 *                    - admin_only: (bool) Filter by admin_only flag.
 *                    - orderby: (string) Order by 'priority' or 'name'.
 *                    - order: (string) 'ASC' or 'DESC'.
 * @return array Array of field configurations keyed by name.
 */
function apd_get_fields( array $args = [] ): array {
    return apd_field_registry()->get_fields( $args );
}

/**
 * Check if a field is registered.
 *
 * @since 1.0.0
 *
 * @param string $name Field name.
 * @return bool True if registered.
 */
function apd_has_field( string $name ): bool {
    return apd_field_registry()->has_field( $name );
}

/**
 * Register a field type handler.
 *
 * @since 1.0.0
 *
 * @param \APD\Contracts\FieldTypeInterface $field_type The field type handler instance.
 * @return bool True if registered successfully.
 */
function apd_register_field_type( \APD\Contracts\FieldTypeInterface $field_type ): bool {
    return apd_field_registry()->register_field_type( $field_type );
}

/**
 * Get a field type handler.
 *
 * @since 1.0.0
 *
 * @param string $type Field type identifier.
 * @return \APD\Contracts\FieldTypeInterface|null Field type handler or null.
 */
function apd_get_field_type( string $type ): ?\APD\Contracts\FieldTypeInterface {
    return apd_field_registry()->get_field_type( $type );
}

/**
 * Get the meta key for a field.
 *
 * @since 1.0.0
 *
 * @param string $field_name Field name.
 * @return string The meta key (prefixed with _apd_).
 */
function apd_get_field_meta_key( string $field_name ): string {
    return apd_field_registry()->get_meta_key( $field_name );
}

/**
 * Get a listing field value.
 *
 * Retrieves the value of a custom field for a listing, applying
 * any necessary transformations from the field type handler.
 *
 * @since 1.0.0
 *
 * @param int    $listing_id Listing post ID.
 * @param string $field_name Field name (without _apd_ prefix).
 * @param mixed  $default    Default value if not set.
 * @return mixed The field value.
 */
function apd_get_listing_field( int $listing_id, string $field_name, mixed $default = '' ): mixed {
    $field = apd_get_field( $field_name );

    if ( $field === null ) {
        // Field not registered, fall back to direct meta retrieval.
        return apd_get_listing_meta( $listing_id, $field_name, $default );
    }

    $meta_key = apd_get_field_meta_key( $field_name );
    $value    = get_post_meta( $listing_id, $meta_key, true );

    // Use field default if no value stored.
    if ( $value === '' || $value === null ) {
        $value = $field['default'] ?? $default;
    }

    // Apply field type transformation if available.
    $field_type = apd_get_field_type( $field['type'] );
    if ( $field_type !== null ) {
        $value = $field_type->prepareValueFromStorage( $value );
    }

    /**
     * Filter the listing field value.
     *
     * @since 1.0.0
     *
     * @param mixed  $value      The field value.
     * @param int    $listing_id Listing post ID.
     * @param string $field_name Field name.
     * @param array  $field      Field configuration.
     */
    return apply_filters( 'apd_listing_field_value', $value, $listing_id, $field_name, $field );
}

/**
 * Set a listing field value.
 *
 * Saves a custom field value for a listing, applying any necessary
 * sanitization and transformation from the field type handler.
 *
 * @since 1.0.0
 *
 * @param int    $listing_id Listing post ID.
 * @param string $field_name Field name (without _apd_ prefix).
 * @param mixed  $value      Value to save.
 * @return int|bool Meta ID on success, false on failure.
 */
function apd_set_listing_field( int $listing_id, string $field_name, mixed $value ): int|bool {
    $field = apd_get_field( $field_name );

    if ( $field !== null ) {
        // Apply field type sanitization and transformation.
        $field_type = apd_get_field_type( $field['type'] );
        if ( $field_type !== null ) {
            $value = $field_type->sanitize( $value );
            $value = $field_type->prepareValueForStorage( $value );
        }
    }

    $meta_key = apd_get_field_meta_key( $field_name );

    /**
     * Filter the value before saving to post meta.
     *
     * @since 1.0.0
     *
     * @param mixed  $value      The value to save.
     * @param int    $listing_id Listing post ID.
     * @param string $field_name Field name.
     * @param array  $field      Field configuration (or null if not registered).
     */
    $value = apply_filters( 'apd_set_listing_field_value', $value, $listing_id, $field_name, $field );

    return update_post_meta( $listing_id, $meta_key, $value );
}

// ============================================================================
// Field Renderer Functions
// ============================================================================

/**
 * Get the field renderer instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Fields\FieldRenderer
 */
function apd_field_renderer(): \APD\Fields\FieldRenderer {
    static $renderer = null;

    if ( $renderer === null ) {
        $renderer = new \APD\Fields\FieldRenderer();
    }

    return $renderer;
}

/**
 * Render a single field.
 *
 * @since 1.0.0
 *
 * @param string $field_name Field name.
 * @param mixed  $value      Current value.
 * @param string $context    Render context (admin, frontend, display).
 * @param int    $listing_id Optional. Listing ID for context.
 * @return string Rendered HTML.
 */
function apd_render_field( string $field_name, mixed $value = null, string $context = 'admin', int $listing_id = 0 ): string {
    return apd_field_renderer()
        ->set_context( $context )
        ->render_field( $field_name, $value, $listing_id );
}

/**
 * Render multiple fields.
 *
 * @since 1.0.0
 *
 * @param array  $values     Field values keyed by name.
 * @param array  $args       Optional. Arguments (fields, exclude).
 * @param string $context    Render context (admin, frontend, display).
 * @param int    $listing_id Optional. Listing ID for context.
 * @return string Rendered HTML.
 */
function apd_render_fields( array $values = [], array $args = [], string $context = 'admin', int $listing_id = 0 ): string {
    return apd_field_renderer()
        ->set_context( $context )
        ->render_fields( $values, $args, $listing_id );
}

/**
 * Render admin meta box fields for a listing.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $args       Optional. Arguments (nonce_action, nonce_name).
 * @return string Rendered HTML.
 */
function apd_render_admin_fields( int $listing_id, array $args = [] ): string {
    return apd_field_renderer()->render_admin_fields( $listing_id, $args );
}

/**
 * Render frontend submission form fields.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Optional. Listing ID for editing.
 * @param array $args       Optional. Arguments (nonce_action, nonce_name, submitted_values).
 * @return string Rendered HTML.
 */
function apd_render_frontend_fields( int $listing_id = 0, array $args = [] ): string {
    return apd_field_renderer()->render_frontend_fields( $listing_id, $args );
}

/**
 * Render fields for display (single listing view).
 *
 * @since 1.0.0
 *
 * @param int   $listing_id Listing post ID.
 * @param array $args       Optional. Arguments (fields, exclude).
 * @return string Rendered HTML.
 */
function apd_render_display_fields( int $listing_id, array $args = [] ): string {
    return apd_field_renderer()->render_display_fields( $listing_id, $args );
}

/**
 * Register a field group/section.
 *
 * @since 1.0.0
 *
 * @param string $group_id Group identifier.
 * @param array  $config   Group configuration.
 *                         - title: (string) Group title.
 *                         - description: (string) Group description.
 *                         - priority: (int) Display order.
 *                         - collapsible: (bool) Whether group can collapse.
 *                         - collapsed: (bool) Initial collapsed state.
 *                         - fields: (array) Field names in this group.
 * @return void
 */
function apd_register_field_group( string $group_id, array $config ): void {
    apd_field_renderer()->register_group( $group_id, $config );
}

/**
 * Unregister a field group.
 *
 * @since 1.0.0
 *
 * @param string $group_id Group identifier.
 * @return void
 */
function apd_unregister_field_group( string $group_id ): void {
    apd_field_renderer()->unregister_group( $group_id );
}

/**
 * Set field validation errors to display.
 *
 * @since 1.0.0
 *
 * @param array|\WP_Error $errors Errors keyed by field name, or WP_Error.
 * @return void
 */
function apd_set_field_errors( array|\WP_Error $errors ): void {
    apd_field_renderer()->set_errors( $errors );
}

/**
 * Clear field validation errors.
 *
 * @since 1.0.0
 *
 * @return void
 */
function apd_clear_field_errors(): void {
    apd_field_renderer()->clear_errors();
}

// ============================================================================
// Field Validator Functions
// ============================================================================

/**
 * Get the field validator instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Fields\FieldValidator
 */
function apd_field_validator(): \APD\Fields\FieldValidator {
    static $validator = null;

    if ( $validator === null ) {
        $validator = new \APD\Fields\FieldValidator();
    }

    return $validator;
}

/**
 * Validate a single field value.
 *
 * @since 1.0.0
 *
 * @param string $field_name The field name.
 * @param mixed  $value      The value to validate.
 * @param bool   $sanitize   Optional. Whether to sanitize before validation. Default true.
 * @return bool|\WP_Error True if valid, WP_Error on failure.
 */
function apd_validate_field( string $field_name, mixed $value, bool $sanitize = true ): bool|\WP_Error {
    return apd_field_validator()->validate_field( $field_name, $value, $sanitize );
}

/**
 * Validate multiple field values.
 *
 * @since 1.0.0
 *
 * @param array $values Field values keyed by field name.
 * @param array $args   Optional. Arguments.
 *                      - 'fields': (array) Specific field names to validate.
 *                      - 'exclude': (array) Field names to exclude.
 *                      - 'sanitize': (bool) Whether to sanitize. Default true.
 *                      - 'skip_unregistered': (bool) Skip unknown fields. Default true.
 * @return bool|\WP_Error True if all valid, WP_Error with all errors on failure.
 */
function apd_validate_fields( array $values, array $args = [] ): bool|\WP_Error {
    return apd_field_validator()->validate_fields( $values, $args );
}

/**
 * Sanitize a single field value.
 *
 * @since 1.0.0
 *
 * @param string $field_name The field name.
 * @param mixed  $value      The value to sanitize.
 * @return mixed The sanitized value.
 */
function apd_sanitize_field( string $field_name, mixed $value ): mixed {
    return apd_field_validator()->sanitize_field( $field_name, $value );
}

/**
 * Sanitize multiple field values.
 *
 * @since 1.0.0
 *
 * @param array $values Field values keyed by field name.
 * @param array $args   Optional. Arguments.
 *                      - 'fields': (array) Specific field names to sanitize.
 *                      - 'exclude': (array) Field names to exclude.
 *                      - 'skip_unregistered': (bool) Skip unknown fields. Default true.
 * @return array Sanitized values.
 */
function apd_sanitize_fields( array $values, array $args = [] ): array {
    return apd_field_validator()->sanitize_fields( $values, $args );
}

/**
 * Validate and sanitize field values in one operation.
 *
 * @since 1.0.0
 *
 * @param array $values Field values keyed by field name.
 * @param array $args   Optional. Arguments (same as validate_fields).
 * @return array{valid: bool, values: array, errors: \WP_Error|null}
 */
function apd_process_fields( array $values, array $args = [] ): array {
    return apd_field_validator()->process_fields( $values, $args );
}

// ============================================================================
// Filter Registry Functions
// ============================================================================

/**
 * Get the filter registry instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Search\FilterRegistry
 */
function apd_filter_registry(): \APD\Search\FilterRegistry {
    return \APD\Search\FilterRegistry::get_instance();
}

/**
 * Register a search filter.
 *
 * @since 1.0.0
 *
 * @param \APD\Contracts\FilterInterface $filter The filter instance.
 * @return bool True if registered successfully.
 */
function apd_register_filter( \APD\Contracts\FilterInterface $filter ): bool {
    return apd_filter_registry()->register_filter( $filter );
}

/**
 * Unregister a search filter.
 *
 * @since 1.0.0
 *
 * @param string $name Filter name to unregister.
 * @return bool True if unregistered successfully.
 */
function apd_unregister_filter( string $name ): bool {
    return apd_filter_registry()->unregister_filter( $name );
}

/**
 * Get a registered filter by name.
 *
 * @since 1.0.0
 *
 * @param string $name Filter name.
 * @return \APD\Contracts\FilterInterface|null Filter instance or null.
 */
function apd_get_filter( string $name ): ?\APD\Contracts\FilterInterface {
    return apd_filter_registry()->get_filter( $name );
}

/**
 * Get all registered filters.
 *
 * @since 1.0.0
 *
 * @param array $args Optional. Filter arguments.
 *                    - 'type': Filter by filter type.
 *                    - 'source': Filter by source (taxonomy, field, custom).
 *                    - 'active_only': Only return filters marked as active.
 *                    - 'orderby': Order by 'priority' or 'name'.
 *                    - 'order': 'ASC' or 'DESC'.
 * @return array Array of filter instances keyed by name.
 */
function apd_get_filters( array $args = [] ): array {
    return apd_filter_registry()->get_filters( $args );
}

/**
 * Check if a filter is registered.
 *
 * @since 1.0.0
 *
 * @param string $name Filter name.
 * @return bool True if registered.
 */
function apd_has_filter( string $name ): bool {
    return apd_filter_registry()->has_filter( $name );
}

// ============================================================================
// Search Query Functions
// ============================================================================

/**
 * Get the search query instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Search\SearchQuery
 */
function apd_search_query(): \APD\Search\SearchQuery {
    static $search_query = null;

    if ( $search_query === null ) {
        $search_query = new \APD\Search\SearchQuery();
    }

    return $search_query;
}

/**
 * Get filtered listings query.
 *
 * Runs a WP_Query with active filters applied.
 *
 * @since 1.0.0
 *
 * @param array $args Additional query arguments.
 * @return \WP_Query The query result.
 */
function apd_get_filtered_listings( array $args = [] ): \WP_Query {
    return apd_search_query()->get_filtered_listings( $args );
}

/**
 * Get orderby options for listing queries.
 *
 * @since 1.0.0
 *
 * @return array Orderby options with labels.
 */
function apd_get_orderby_options(): array {
    return apd_search_query()->get_orderby_options();
}

// ============================================================================
// Filter Renderer Functions
// ============================================================================

/**
 * Get the filter renderer instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Search\FilterRenderer
 */
function apd_filter_renderer(): \APD\Search\FilterRenderer {
    static $renderer = null;

    if ( $renderer === null ) {
        $renderer = new \APD\Search\FilterRenderer();
    }

    return $renderer;
}

/**
 * Render the search form with filters.
 *
 * @since 1.0.0
 *
 * @param array $args Render arguments.
 *                    - 'filters': Array of filter names to include.
 *                    - 'exclude': Array of filter names to exclude.
 *                    - 'show_orderby': Whether to show orderby dropdown.
 *                    - 'show_submit': Whether to show submit button.
 *                    - 'action': Form action URL.
 *                    - 'method': Form method (get/post).
 *                    - 'ajax': Whether to use AJAX.
 *                    - 'class': Additional CSS classes.
 * @return string The rendered form HTML.
 */
function apd_render_search_form( array $args = [] ): string {
    return apd_filter_renderer()->render_search_form( $args );
}

/**
 * Render a single filter control.
 *
 * @since 1.0.0
 *
 * @param string     $name    Filter name.
 * @param array|null $request Request data for value.
 * @return string The rendered filter HTML.
 */
function apd_render_filter( string $name, ?array $request = null ): string {
    return apd_filter_renderer()->render_filter( $name, $request );
}

/**
 * Render active filter chips.
 *
 * @since 1.0.0
 *
 * @param array|null $request Request data.
 * @return string The rendered HTML.
 */
function apd_render_active_filters( ?array $request = null ): string {
    return apd_filter_renderer()->render_active_filters( $request );
}

/**
 * Render the no results message.
 *
 * @since 1.0.0
 *
 * @param array $args Render arguments.
 * @return string The rendered HTML.
 */
function apd_render_no_results( array $args = [] ): string {
    return apd_filter_renderer()->render_no_results( $args );
}

// ============================================================================
// Template Functions
// ============================================================================

/**
 * Get the template loader instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Core\Template
 */
function apd_template(): \APD\Core\Template {
    return \APD\Core\Template::get_instance();
}

/**
 * Locate a template file.
 *
 * Searches for templates in the following order:
 * 1. Theme: `{theme}/all-purpose-directory/{template_name}`
 * 2. Plugin: `{plugin}/templates/{template_name}`
 *
 * @since 1.0.0
 *
 * @param string $template_name Template file name (e.g., 'listing-card.php').
 * @return string|false Full path to template file or false if not found.
 */
function apd_locate_template( string $template_name ): string|false {
    return apd_template()->locate_template( $template_name );
}

/**
 * Load a template file with variables.
 *
 * Variables are extracted into the template's scope as individual variables.
 * They are also available as an $args array within the template.
 *
 * @since 1.0.0
 *
 * @param string $template_name Template file name (e.g., 'listing-card.php').
 * @param array  $args          Variables to pass to the template.
 * @param bool   $require_once  Whether to use require_once (default: false).
 * @return void
 */
function apd_get_template( string $template_name, array $args = [], bool $require_once = false ): void {
    apd_template()->get_template( $template_name, $args, $require_once );
}

/**
 * Load and return a template as HTML.
 *
 * @since 1.0.0
 *
 * @param string $template_name Template file name.
 * @param array  $args          Variables to pass to the template.
 * @return string The template HTML.
 */
function apd_get_template_html( string $template_name, array $args = [] ): string {
    return apd_template()->get_template_html( $template_name, $args );
}

/**
 * Load a template part.
 *
 * Works similarly to WordPress's get_template_part() but with theme override support.
 * Will try to load templates in this order:
 * 1. `{slug}-{name}.php`
 * 2. `{slug}.php`
 *
 * Example: apd_get_template_part('listing-card', 'grid') will look for:
 * - `{theme}/all-purpose-directory/listing-card-grid.php`
 * - `{theme}/all-purpose-directory/listing-card.php`
 * - `{plugin}/templates/listing-card-grid.php`
 * - `{plugin}/templates/listing-card.php`
 *
 * @since 1.0.0
 *
 * @param string      $slug The slug name for the generic template.
 * @param string|null $name The name of the specialized template (optional).
 * @param array       $args Variables to pass to the template.
 * @return void
 */
function apd_get_template_part( string $slug, ?string $name = null, array $args = [] ): void {
    apd_template()->get_template_part( $slug, $name, $args );
}

/**
 * Load and return a template part as HTML.
 *
 * @since 1.0.0
 *
 * @param string      $slug The slug name for the generic template.
 * @param string|null $name The name of the specialized template (optional).
 * @param array       $args Variables to pass to the template.
 * @return string The template HTML.
 */
function apd_get_template_part_html( string $slug, ?string $name = null, array $args = [] ): string {
    return apd_template()->get_template_part_html( $slug, $name, $args );
}

/**
 * Check if a template exists.
 *
 * @since 1.0.0
 *
 * @param string $template_name Template file name.
 * @return bool True if template exists.
 */
function apd_template_exists( string $template_name ): bool {
    return apd_template()->template_exists( $template_name );
}

/**
 * Check if a template is being overridden by the theme.
 *
 * @since 1.0.0
 *
 * @param string $template_name Template file name.
 * @return bool True if template is overridden in theme.
 */
function apd_is_template_overridden( string $template_name ): bool {
    return apd_template()->is_template_overridden( $template_name );
}

/**
 * Get the plugin's template path.
 *
 * @since 1.0.0
 *
 * @return string Plugin template path.
 */
function apd_get_plugin_template_path(): string {
    return apd_template()->get_plugin_template_path();
}

/**
 * Get the theme template directory name.
 *
 * @since 1.0.0
 *
 * @return string Theme template directory (e.g., 'all-purpose-directory/').
 */
function apd_get_theme_template_dir(): string {
    return apd_template()->get_theme_template_dir();
}

// ============================================================================
// Template Loader Functions
// ============================================================================

/**
 * Get the template loader instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Core\TemplateLoader
 */
function apd_template_loader(): \APD\Core\TemplateLoader {
    static $loader = null;

    if ( $loader === null ) {
        $loader = new \APD\Core\TemplateLoader();
    }

    return $loader;
}

/**
 * Get the current view mode (grid or list).
 *
 * @since 1.0.0
 *
 * @return string View mode ('grid' or 'list').
 */
function apd_get_current_view(): string {
    return apd_template_loader()->get_current_view();
}

/**
 * Get the current grid columns setting.
 *
 * @since 1.0.0
 *
 * @return int Number of columns (2, 3, or 4).
 */
function apd_get_grid_columns(): int {
    return apd_template_loader()->get_grid_columns();
}

/**
 * Get URL for switching to a specific view.
 *
 * @since 1.0.0
 *
 * @param string $view The view to switch to ('grid' or 'list').
 * @return string URL with view parameter.
 */
function apd_get_view_url( string $view ): string {
    return apd_template_loader()->get_view_url( $view );
}

/**
 * Render the view switcher HTML.
 *
 * @since 1.0.0
 *
 * @return string The HTML for the view switcher.
 */
function apd_render_view_switcher(): string {
    return apd_template_loader()->render_view_switcher();
}

/**
 * Render results count.
 *
 * @since 1.0.0
 *
 * @param \WP_Query|null $query Optional. Query to get count from.
 * @return string The HTML for the results count.
 */
function apd_render_results_count( ?\WP_Query $query = null ): string {
    return apd_template_loader()->render_results_count( $query );
}

/**
 * Render pagination.
 *
 * @since 1.0.0
 *
 * @param \WP_Query|null $query Optional. Query to paginate.
 * @return string The pagination HTML.
 */
function apd_render_pagination( ?\WP_Query $query = null ): string {
    return apd_template_loader()->render_pagination( $query );
}

/**
 * Get the archive title.
 *
 * @since 1.0.0
 *
 * @return string The archive title.
 */
function apd_get_archive_title(): string {
    return apd_template_loader()->get_archive_title();
}

/**
 * Get the archive description.
 *
 * @since 1.0.0
 *
 * @return string The archive description.
 */
function apd_get_archive_description(): string {
    return apd_template_loader()->get_archive_description();
}

// ============================================================================
// Single Listing Functions
// ============================================================================

/**
 * Get related listings for a single listing.
 *
 * Related listings are determined by shared categories first,
 * then by shared tags if not enough are found.
 *
 * @since 1.0.0
 *
 * @param int   $listing_id The listing to get related posts for.
 * @param int   $limit      Maximum number of related listings. Default 4.
 * @param array $args       Optional. Additional query arguments.
 * @return \WP_Post[] Array of related listing posts.
 */
function apd_get_related_listings( int $listing_id, int $limit = 4, array $args = [] ): array {
    $categories = apd_get_listing_categories( $listing_id );
    $tags       = apd_get_listing_tags( $listing_id );

    // If no categories or tags, return empty.
    if ( empty( $categories ) && empty( $tags ) ) {
        return [];
    }

    $category_ids = array_map( fn( $term ) => $term->term_id, $categories );
    $tag_ids      = array_map( fn( $term ) => $term->term_id, $tags );

    // Build tax query.
    $tax_query = [
        'relation' => 'OR',
    ];

    if ( ! empty( $category_ids ) ) {
        $tax_query[] = [
            'taxonomy' => apd_get_category_taxonomy(),
            'field'    => 'term_id',
            'terms'    => $category_ids,
        ];
    }

    if ( ! empty( $tag_ids ) ) {
        $tax_query[] = [
            'taxonomy' => apd_get_tag_taxonomy(),
            'field'    => 'term_id',
            'terms'    => $tag_ids,
        ];
    }

    $defaults = [
        'post_type'           => apd_get_listing_post_type(),
        'post_status'         => 'publish',
        'posts_per_page'      => $limit,
        'post__not_in'        => [ $listing_id ],
        'ignore_sticky_posts' => true,
        'orderby'             => 'rand',
        'tax_query'           => $tax_query,
    ];

    $query_args = wp_parse_args( $args, $defaults );

    /**
     * Filter the related listings query arguments.
     *
     * @since 1.0.0
     *
     * @param array $query_args The query arguments.
     * @param int   $listing_id The current listing ID.
     * @param int   $limit      The limit of related listings.
     */
    $query_args = apply_filters( 'apd_related_listings_args', $query_args, $listing_id, $limit );

    $query = new \WP_Query( $query_args );

    /**
     * Filter the related listings.
     *
     * @since 1.0.0
     *
     * @param \WP_Post[] $posts      The related posts.
     * @param int        $listing_id The current listing ID.
     * @param int        $limit      The limit of related listings.
     */
    return apply_filters( 'apd_related_listings', $query->posts, $listing_id, $limit );
}

/**
 * Get the listing view count.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return int View count.
 */
function apd_get_listing_views( int $listing_id ): int {
    return absint( get_post_meta( $listing_id, '_apd_views_count', true ) );
}

/**
 * Increment the listing view count.
 *
 * @since 1.0.0
 *
 * @param int $listing_id Listing post ID.
 * @return int The new view count.
 */
function apd_increment_listing_views( int $listing_id ): int {
    $views = apd_get_listing_views( $listing_id ) + 1;
    update_post_meta( $listing_id, '_apd_views_count', $views );

    /**
     * Fires after a listing's view count is incremented.
     *
     * @since 1.0.0
     *
     * @param int $listing_id The listing post ID.
     * @param int $views      The new view count.
     */
    do_action( 'apd_listing_viewed', $listing_id, $views );

    return $views;
}

// ============================================================================
// View Registry Functions
// ============================================================================

/**
 * Get the view registry instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Frontend\Display\ViewRegistry
 */
function apd_view_registry(): \APD\Frontend\Display\ViewRegistry {
    return \APD\Frontend\Display\ViewRegistry::get_instance();
}

/**
 * Register a listing display view.
 *
 * @since 1.0.0
 *
 * @param \APD\Contracts\ViewInterface $view The view instance.
 * @return bool True if registered successfully.
 */
function apd_register_view( \APD\Contracts\ViewInterface $view ): bool {
    return apd_view_registry()->register_view( $view );
}

/**
 * Unregister a listing display view.
 *
 * @since 1.0.0
 *
 * @param string $type View type to unregister.
 * @return bool True if unregistered successfully.
 */
function apd_unregister_view( string $type ): bool {
    return apd_view_registry()->unregister_view( $type );
}

/**
 * Get a registered view by type.
 *
 * @since 1.0.0
 *
 * @param string $type View type (e.g., 'grid', 'list').
 * @return \APD\Contracts\ViewInterface|null View instance or null.
 */
function apd_get_view( string $type ): ?\APD\Contracts\ViewInterface {
    return apd_view_registry()->get_view( $type );
}

/**
 * Get all registered views.
 *
 * @since 1.0.0
 *
 * @return array<string, \APD\Contracts\ViewInterface> Array of views.
 */
function apd_get_views(): array {
    return apd_view_registry()->get_views();
}

/**
 * Check if a view type is registered.
 *
 * @since 1.0.0
 *
 * @param string $type View type.
 * @return bool True if registered.
 */
function apd_has_view( string $type ): bool {
    return apd_view_registry()->has_view( $type );
}

/**
 * Create a new view instance with configuration.
 *
 * @since 1.0.0
 *
 * @param string $type   View type (e.g., 'grid', 'list').
 * @param array  $config Configuration options.
 * @return \APD\Contracts\ViewInterface|null View instance or null.
 */
function apd_create_view( string $type, array $config = [] ): ?\APD\Contracts\ViewInterface {
    return apd_view_registry()->create_view( $type, $config );
}

/**
 * Get available view options for select fields.
 *
 * @since 1.0.0
 *
 * @return array<string, string> Type => label mapping.
 */
function apd_get_view_options(): array {
    return apd_view_registry()->get_view_options();
}

/**
 * Get the grid view instance.
 *
 * @since 1.0.0
 *
 * @param array $config Optional. Configuration options.
 * @return \APD\Frontend\Display\GridView
 */
function apd_grid_view( array $config = [] ): \APD\Frontend\Display\GridView {
    return new \APD\Frontend\Display\GridView( $config );
}

/**
 * Get the list view instance.
 *
 * @since 1.0.0
 *
 * @param array $config Optional. Configuration options.
 * @return \APD\Frontend\Display\ListView
 */
function apd_list_view( array $config = [] ): \APD\Frontend\Display\ListView {
    return new \APD\Frontend\Display\ListView( $config );
}

/**
 * Render listings in grid view.
 *
 * @since 1.0.0
 *
 * @param \WP_Query|array<int> $listings WP_Query or array of listing IDs.
 * @param array                $args     Render arguments.
 *                                       - columns: (int) Number of columns (2, 3, 4).
 *                                       - show_image: (bool) Show featured image.
 *                                       - show_excerpt: (bool) Show excerpt.
 *                                       - excerpt_length: (int) Excerpt word count.
 *                                       - show_category: (bool) Show categories.
 *                                       - show_price: (bool) Show price field.
 *                                       - show_rating: (bool) Show rating.
 *                                       - show_favorite: (bool) Show favorite button.
 *                                       - show_container: (bool) Wrap in container.
 *                                       - show_no_results: (bool) Show no results message.
 * @return string Rendered HTML.
 */
function apd_render_grid( \WP_Query|array $listings, array $args = [] ): string {
    // Extract view config from args.
    $config = [];
    $config_keys = [
        'columns', 'show_image', 'show_excerpt', 'excerpt_length',
        'show_category', 'show_badge', 'show_price', 'show_rating',
        'show_favorite', 'show_view_details', 'image_size',
    ];
    foreach ( $config_keys as $key ) {
        if ( isset( $args[ $key ] ) ) {
            $config[ $key ] = $args[ $key ];
        }
    }

    $view = apd_grid_view( $config );
    return $view->renderListings( $listings, $args );
}

/**
 * Render listings in list view.
 *
 * @since 1.0.0
 *
 * @param \WP_Query|array<int> $listings WP_Query or array of listing IDs.
 * @param array                $args     Render arguments.
 *                                       - show_image: (bool) Show featured image.
 *                                       - show_excerpt: (bool) Show excerpt.
 *                                       - excerpt_length: (int) Excerpt word count.
 *                                       - show_category: (bool) Show categories.
 *                                       - show_tags: (bool) Show tags.
 *                                       - max_tags: (int) Maximum tags to show.
 *                                       - show_date: (bool) Show date.
 *                                       - show_price: (bool) Show price field.
 *                                       - show_rating: (bool) Show rating.
 *                                       - show_favorite: (bool) Show favorite button.
 *                                       - show_container: (bool) Wrap in container.
 *                                       - show_no_results: (bool) Show no results message.
 * @return string Rendered HTML.
 */
function apd_render_list( \WP_Query|array $listings, array $args = [] ): string {
    // Extract view config from args.
    $config = [];
    $config_keys = [
        'show_image', 'show_excerpt', 'excerpt_length', 'show_category',
        'show_tags', 'max_tags', 'show_date', 'show_price', 'show_rating',
        'show_favorite', 'show_view_details', 'image_size', 'image_width',
    ];
    foreach ( $config_keys as $key ) {
        if ( isset( $args[ $key ] ) ) {
            $config[ $key ] = $args[ $key ];
        }
    }

    $view = apd_list_view( $config );
    return $view->renderListings( $listings, $args );
}

/**
 * Render listings in the specified view.
 *
 * @since 1.0.0
 *
 * @param \WP_Query|array<int> $listings WP_Query or array of listing IDs.
 * @param string               $view_type View type ('grid' or 'list').
 * @param array                $args      Render arguments.
 * @return string Rendered HTML.
 */
function apd_render_listings( \WP_Query|array $listings, string $view_type = 'grid', array $args = [] ): string {
    $view = apd_create_view( $view_type, $args );

    if ( $view === null ) {
        // Fall back to grid view.
        $view = apd_grid_view( $args );
    }

    return $view->renderListings( $listings, $args );
}

// ============================================================================
// SHORTCODE FUNCTIONS
// ============================================================================

/**
 * Get the shortcode manager instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Shortcode\ShortcodeManager
 */
function apd_shortcode_manager(): \APD\Shortcode\ShortcodeManager {
    return \APD\Shortcode\ShortcodeManager::get_instance();
}

/**
 * Get a registered shortcode.
 *
 * @since 1.0.0
 *
 * @param string $tag Shortcode tag.
 * @return \APD\Shortcode\AbstractShortcode|null
 */
function apd_get_shortcode( string $tag ): ?\APD\Shortcode\AbstractShortcode {
    return apd_shortcode_manager()->get( $tag );
}

/**
 * Check if a shortcode is registered.
 *
 * @since 1.0.0
 *
 * @param string $tag Shortcode tag.
 * @return bool
 */
function apd_has_shortcode( string $tag ): bool {
    return apd_shortcode_manager()->has( $tag );
}

/**
 * Get all registered shortcodes.
 *
 * @since 1.0.0
 *
 * @return array<string, \APD\Shortcode\AbstractShortcode>
 */
function apd_get_shortcodes(): array {
    return apd_shortcode_manager()->get_all();
}

/**
 * Get shortcode documentation.
 *
 * @since 1.0.0
 *
 * @return array<string, array>
 */
function apd_get_shortcode_docs(): array {
    return apd_shortcode_manager()->get_documentation();
}

// =============================================================================
// Block Manager Functions
// =============================================================================

/**
 * Get the block manager instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Blocks\BlockManager
 */
function apd_block_manager(): \APD\Blocks\BlockManager {
    return \APD\Blocks\BlockManager::get_instance();
}

/**
 * Get a registered block.
 *
 * @since 1.0.0
 *
 * @param string $name Block name (without namespace).
 * @return \APD\Blocks\AbstractBlock|null
 */
function apd_get_block( string $name ): ?\APD\Blocks\AbstractBlock {
    return apd_block_manager()->get( $name );
}

/**
 * Check if a block is registered.
 *
 * @since 1.0.0
 *
 * @param string $name Block name (without namespace).
 * @return bool
 */
function apd_has_block( string $name ): bool {
    return apd_block_manager()->has( $name );
}

/**
 * Get all registered blocks.
 *
 * @since 1.0.0
 *
 * @return array<string, \APD\Blocks\AbstractBlock>
 */
function apd_get_blocks(): array {
    return apd_block_manager()->get_all();
}
