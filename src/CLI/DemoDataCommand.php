<?php
/**
 * WP-CLI Demo Data Command.
 *
 * Provides CLI commands for generating and managing demo data.
 *
 * @package APD\CLI
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\CLI;

use APD\Admin\DemoData\DemoDataGenerator;
use APD\Admin\DemoData\DemoDataProviderRegistry;
use APD\Admin\DemoData\DemoDataTracker;
use WP_CLI;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage demo data for the All Purpose Directory plugin.
 *
 * ## EXAMPLES
 *
 *     # Generate all demo data with defaults
 *     $ wp apd demo generate
 *
 *     # Generate only listings and reviews
 *     $ wp apd demo generate --types=listings,reviews
 *
 *     # Generate 50 listings
 *     $ wp apd demo generate --listings=50
 *
 *     # Show current demo data counts
 *     $ wp apd demo status
 *
 *     # Delete all demo data
 *     $ wp apd demo delete
 *
 * @since 1.0.0
 */
final class DemoDataCommand {

	/**
	 * Ensure the DemoDataProviderRegistry is initialized.
	 *
	 * In WP-CLI context, the registry's init() is not triggered by
	 * DemoDataPage (which requires is_admin()). This method ensures
	 * the `apd_demo_providers_init` action fires so module plugins
	 * can register their providers.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function ensure_providers_initialized(): void {
		DemoDataProviderRegistry::get_instance()->init();
	}

	/**
	 * Generate demo data.
	 *
	 * ## OPTIONS
	 *
	 * [--types=<types>]
	 * : Comma-separated list of data types to generate.
	 * Options: users,categories,tags,listings,reviews,inquiries,favorites,all
	 * ---
	 * default: all
	 * ---
	 *
	 * [--users=<count>]
	 * : Number of users to create (max 20).
	 * ---
	 * default: 5
	 * ---
	 *
	 * [--tags=<count>]
	 * : Number of tags to create (max 10).
	 * ---
	 * default: 10
	 * ---
	 *
	 * [--listings=<count>]
	 * : Number of listings to create (max 100).
	 * ---
	 * default: 25
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate all demo data with defaults
	 *     $ wp apd demo generate
	 *
	 *     # Generate 50 listings with reviews
	 *     $ wp apd demo generate --types=categories,tags,listings,reviews --listings=50
	 *
	 *     # Generate only users and categories
	 *     $ wp apd demo generate --types=users,categories --users=10
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function generate( array $args, array $assoc_args ): void {
		$this->ensure_providers_initialized();

		$types_input = $assoc_args['types'] ?? 'all';
		$types       = array_map( 'trim', explode( ',', $types_input ) );
		$all         = in_array( 'all', $types, true );

		$users_count    = min( absint( $assoc_args['users'] ?? 5 ), 20 );
		$tags_count     = min( absint( $assoc_args['tags'] ?? 10 ), 10 );
		$listings_count = min( absint( $assoc_args['listings'] ?? 25 ), 100 );

		/**
		 * Fires before demo data generation begins.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_before_generate_demo_data' );

		$generator = DemoDataGenerator::get_instance();
		$results   = [];

		// Track created IDs for dependent operations.
		$user_ids    = [];
		$listing_ids = [];

		// Generate users.
		if ( $all || in_array( 'users', $types, true ) ) {
			WP_CLI::log( "Creating {$users_count} users..." );
			$user_ids         = $generator->generate_users( $users_count );
			$results['users'] = count( $user_ids );
			WP_CLI::log( "  Created {$results['users']} users." );
		}

		// Generate categories.
		if ( $all || in_array( 'categories', $types, true ) ) {
			WP_CLI::log( 'Creating categories...' );
			$category_ids          = $generator->generate_categories();
			$results['categories'] = count( $category_ids );
			WP_CLI::log( "  Created {$results['categories']} categories." );
		}

		// Generate tags.
		if ( $all || in_array( 'tags', $types, true ) ) {
			WP_CLI::log( "Creating {$tags_count} tags..." );
			$tag_ids         = $generator->generate_tags( $tags_count );
			$results['tags'] = count( $tag_ids );
			WP_CLI::log( "  Created {$results['tags']} tags." );
		}

		// Generate listings.
		if ( $all || in_array( 'listings', $types, true ) ) {
			WP_CLI::log( "Creating {$listings_count} listings..." );
			$listing_ids         = $generator->generate_listings( $listings_count );
			$results['listings'] = count( $listing_ids );
			WP_CLI::log( "  Created {$results['listings']} listings." );
		}

		// Generate reviews (requires listings).
		if ( ( $all || in_array( 'reviews', $types, true ) ) && ! empty( $listing_ids ) ) {
			WP_CLI::log( 'Creating reviews...' );
			$review_ids         = $generator->generate_reviews( $listing_ids, $user_ids );
			$results['reviews'] = count( $review_ids );
			WP_CLI::log( "  Created {$results['reviews']} reviews." );
		}

		// Generate inquiries (requires listings).
		if ( ( $all || in_array( 'inquiries', $types, true ) ) && ! empty( $listing_ids ) ) {
			WP_CLI::log( 'Creating inquiries...' );
			$inquiry_ids          = $generator->generate_inquiries( $listing_ids );
			$results['inquiries'] = count( $inquiry_ids );
			WP_CLI::log( "  Created {$results['inquiries']} inquiries." );
		}

		// Generate favorites (requires listings and users).
		if ( ( $all || in_array( 'favorites', $types, true ) ) && ! empty( $listing_ids ) && ! empty( $user_ids ) ) {
			WP_CLI::log( 'Creating favorites...' );
			$results['favorites'] = $generator->generate_favorites( $listing_ids, $user_ids );
			WP_CLI::log( "  Created {$results['favorites']} favorites." );
		}

		// Generate module provider data.
		$this->generate_module_data( $results, $user_ids, $listing_ids, $category_ids ?? [], $tag_ids ?? [] );

		/**
		 * Fires after demo data generation completes.
		 *
		 * @since 1.0.0
		 *
		 * @param array $results Number of items created by type.
		 */
		do_action( 'apd_after_generate_demo_data', $results );

		// Summary.
		WP_CLI::success( 'Demo data generated.' );
		$this->print_status();
	}

	/**
	 * Delete all demo data.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete with confirmation
	 *     $ wp apd demo delete
	 *
	 *     # Delete without confirmation
	 *     $ wp apd demo delete --yes
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function delete( array $args, array $assoc_args ): void {
		$this->ensure_providers_initialized();

		$tracker = DemoDataTracker::get_instance();
		$counts  = $tracker->count_demo_data();
		$total   = array_sum( $counts );

		if ( $total === 0 ) {
			WP_CLI::success( 'No demo data found. Nothing to delete.' );
			return;
		}

		WP_CLI::log( "Found {$total} demo data items." );
		WP_CLI::confirm( 'Are you sure you want to delete ALL demo data?', $assoc_args );

		WP_CLI::log( 'Deleting demo data...' );
		$deleted = $tracker->delete_all();

		// Report results.
		foreach ( $deleted as $type => $count ) {
			if ( $count > 0 ) {
				WP_CLI::log( "  Deleted {$count} {$type}." );
			}
		}

		WP_CLI::success( 'All demo data has been deleted.' );
	}

	/**
	 * Show current demo data status.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Show status as table
	 *     $ wp apd demo status
	 *
	 *     # Show status as JSON
	 *     $ wp apd demo status --format=json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function status( array $args, array $assoc_args ): void {
		$this->ensure_providers_initialized();

		$format = $assoc_args['format'] ?? 'table';

		$tracker = DemoDataTracker::get_instance();
		$counts  = $tracker->count_demo_data();

		// Add module counts.
		$provider_registry = DemoDataProviderRegistry::get_instance();
		$providers         = $provider_registry->get_all();

		foreach ( $providers as $slug => $provider ) {
			$provider_counts = $provider->count( $tracker );

			foreach ( $provider_counts as $type => $count ) {
				$counts[ $provider->get_name() . ' - ' . ucfirst( $type ) ] = $count;
			}
		}

		$total = array_sum( $counts );

		// Build table data.
		$items = [];
		foreach ( $counts as $type => $count ) {
			$items[] = [
				'type'  => ucfirst( str_replace( '_', ' ', $type ) ),
				'count' => $count,
			];
		}

		$items[] = [
			'type'  => '---',
			'count' => '---',
		];
		$items[] = [
			'type'  => 'Total',
			'count' => $total,
		];

		WP_CLI\Utils\format_items( $format, $items, [ 'type', 'count' ] );
	}

	/**
	 * Generate module provider demo data.
	 *
	 * @param array $results     Results array (passed by reference).
	 * @param int[] $user_ids    Created user IDs.
	 * @param int[] $listing_ids Created listing IDs.
	 * @param int[] $category_ids Created category term IDs.
	 * @param int[] $tag_ids     Created tag term IDs.
	 * @return void
	 */
	private function generate_module_data( array &$results, array $user_ids, array $listing_ids, array $category_ids, array $tag_ids ): void {
		$provider_registry = DemoDataProviderRegistry::get_instance();
		$providers         = $provider_registry->get_all();

		if ( empty( $providers ) ) {
			return;
		}

		$tracker = DemoDataTracker::get_instance();
		$context = [
			'user_ids'     => $user_ids,
			'listing_ids'  => $listing_ids,
			'category_ids' => $category_ids,
			'tag_ids'      => $tag_ids,
			'options'      => [],
		];

		foreach ( $providers as $slug => $provider ) {
			WP_CLI::log( "Creating {$provider->get_name()} data..." );
			$provider_results = $provider->generate( $context, $tracker );

			foreach ( $provider_results as $type => $count ) {
				$key             = 'module_' . $slug . '_' . $type;
				$results[ $key ] = $count;
				WP_CLI::log( "  Created {$count} {$type}." );
			}
		}
	}

	/**
	 * Print the current status table.
	 *
	 * @return void
	 */
	private function print_status(): void {
		$tracker = DemoDataTracker::get_instance();
		$counts  = $tracker->count_demo_data();

		$provider_registry = DemoDataProviderRegistry::get_instance();
		$providers         = $provider_registry->get_all();

		foreach ( $providers as $slug => $provider ) {
			$provider_counts = $provider->count( $tracker );

			foreach ( $provider_counts as $type => $count ) {
				$counts[ $provider->get_name() . ' - ' . ucfirst( $type ) ] = $count;
			}
		}

		$total = array_sum( $counts );

		WP_CLI::log( '' );
		WP_CLI::log( 'Current demo data:' );

		foreach ( $counts as $type => $count ) {
			$label = ucfirst( str_replace( '_', ' ', $type ) );
			WP_CLI::log( "  {$label}: {$count}" );
		}

		WP_CLI::log( "  Total: {$total}" );
	}
}
