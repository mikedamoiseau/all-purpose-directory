<?php
/**
 * Listing type helper function definitions for unit testing.
 *
 * These mirror the implementations in includes/functions.php. They are defined
 * here with function_exists() guards to avoid loading the full includes/functions.php,
 * which would cause Patchwork DefinedTooEarly errors for other tests that mock
 * functions from that file.
 *
 * @package APD\Tests\Unit\Taxonomy
 */

declare(strict_types=1);

if ( ! function_exists( 'apd_get_listing_type_taxonomy' ) ) {
	/**
	 * Get the listing type taxonomy name.
	 *
	 * @return string
	 */
	function apd_get_listing_type_taxonomy(): string {
		return \APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY;
	}
}

if ( ! function_exists( 'apd_get_listing_type' ) ) {
	/**
	 * Get the listing type for a listing.
	 *
	 * @param int $listing_id Listing post ID.
	 * @return string Listing type slug.
	 */
	function apd_get_listing_type( int $listing_id ): string {
		$terms = wp_get_object_terms(
			$listing_id,
			\APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return \APD\Taxonomy\ListingTypeTaxonomy::DEFAULT_TERM;
		}

		return $terms[0]->slug;
	}
}

if ( ! function_exists( 'apd_set_listing_type' ) ) {
	/**
	 * Set the listing type for a listing.
	 *
	 * @param int    $listing_id Listing post ID.
	 * @param string $type       Listing type slug.
	 * @return bool True on success, false on failure.
	 */
	function apd_set_listing_type( int $listing_id, string $type ): bool {
		$result = wp_set_object_terms(
			$listing_id,
			sanitize_key( $type ),
			\APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY
		);

		return ! is_wp_error( $result );
	}
}

if ( ! function_exists( 'apd_listing_is_type' ) ) {
	/**
	 * Check if a listing is of a specific type.
	 *
	 * @param int    $listing_id Listing post ID.
	 * @param string $type       Listing type slug to check.
	 * @return bool True if the listing is of the given type.
	 */
	function apd_listing_is_type( int $listing_id, string $type ): bool {
		return apd_get_listing_type( $listing_id ) === sanitize_key( $type );
	}
}

if ( ! function_exists( 'apd_get_listing_types' ) ) {
	/**
	 * Get all registered listing types.
	 *
	 * @param bool $hide_empty Whether to hide types with no listings.
	 * @return \WP_Term[] Array of term objects.
	 */
	function apd_get_listing_types( bool $hide_empty = false ): array {
		$terms = get_terms( [
			'taxonomy'   => \APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY,
			'hide_empty' => $hide_empty,
		] );

		return is_wp_error( $terms ) ? [] : $terms;
	}
}

if ( ! function_exists( 'apd_get_listing_type_term' ) ) {
	/**
	 * Get the listing type term object.
	 *
	 * @param string $type_slug Listing type slug.
	 * @return \WP_Term|null Term object or null if not found.
	 */
	function apd_get_listing_type_term( string $type_slug ): ?\WP_Term {
		$term = get_term_by( 'slug', sanitize_key( $type_slug ), \APD\Taxonomy\ListingTypeTaxonomy::TAXONOMY );

		return $term instanceof \WP_Term ? $term : null;
	}
}

if ( ! function_exists( 'apd_get_listing_type_count' ) ) {
	/**
	 * Get the count of listings for a given type.
	 *
	 * @param string $type_slug Listing type slug.
	 * @return int Number of listings with that type.
	 */
	function apd_get_listing_type_count( string $type_slug ): int {
		$term = apd_get_listing_type_term( $type_slug );

		return $term ? $term->count : 0;
	}
}
