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
