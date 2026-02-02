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
