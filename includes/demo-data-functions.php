<?php
/**
 * Demo Data Helper Functions.
 *
 * Provides helper functions for the demo data system.
 *
 * @package APD
 * @since   1.0.0
 */

declare(strict_types=1);

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the DemoDataPage instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Admin\DemoData\DemoDataPage
 */
function apd_demo_data_page(): \APD\Admin\DemoData\DemoDataPage {
	return \APD\Admin\DemoData\DemoDataPage::get_instance();
}

/**
 * Get the DemoDataGenerator instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Admin\DemoData\DemoDataGenerator
 */
function apd_demo_generator(): \APD\Admin\DemoData\DemoDataGenerator {
	return \APD\Admin\DemoData\DemoDataGenerator::get_instance();
}

/**
 * Get the DemoDataTracker instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Admin\DemoData\DemoDataTracker
 */
function apd_demo_tracker(): \APD\Admin\DemoData\DemoDataTracker {
	return \APD\Admin\DemoData\DemoDataTracker::get_instance();
}

/**
 * Get counts of all demo data items.
 *
 * @since 1.0.0
 *
 * @return array{users: int, categories: int, tags: int, listings: int, reviews: int, inquiries: int}
 */
function apd_get_demo_data_counts(): array {
	return apd_demo_tracker()->count_demo_data();
}

/**
 * Delete all demo data.
 *
 * @since 1.0.0
 *
 * @return array{users: int, categories: int, tags: int, listings: int, reviews: int, inquiries: int, favorites: int}
 */
function apd_delete_demo_data(): array {
	return apd_demo_tracker()->delete_all();
}

/**
 * Check if an item is demo data.
 *
 * @since 1.0.0
 *
 * @param string $type Type of item ('post', 'term', 'user', 'comment').
 * @param int    $id   Item ID.
 * @return bool
 */
function apd_is_demo_data( string $type, int $id ): bool {
	$tracker = apd_demo_tracker();

	switch ( $type ) {
		case 'post':
		case 'listing':
		case 'inquiry':
			return $tracker->is_demo_post( $id );

		case 'term':
		case 'category':
		case 'tag':
			return $tracker->is_demo_term( $id );

		case 'user':
			return $tracker->is_demo_user( $id );

		case 'comment':
		case 'review':
			return $tracker->is_demo_comment( $id );

		default:
			return false;
	}
}

/**
 * Check if demo data exists.
 *
 * @since 1.0.0
 *
 * @return bool True if any demo data exists.
 */
function apd_has_demo_data(): bool {
	$counts = apd_get_demo_data_counts();
	return array_sum( $counts ) > 0;
}

/**
 * Get the demo data admin page URL.
 *
 * @since 1.0.0
 *
 * @return string
 */
function apd_get_demo_data_url(): string {
	return admin_url( 'edit.php?post_type=apd_listing&page=apd-demo-data' );
}

/**
 * Get the DemoDataProviderRegistry instance.
 *
 * @since 1.0.0
 *
 * @return \APD\Admin\DemoData\DemoDataProviderRegistry
 */
function apd_demo_provider_registry(): \APD\Admin\DemoData\DemoDataProviderRegistry {
	return \APD\Admin\DemoData\DemoDataProviderRegistry::get_instance();
}

/**
 * Register a demo data provider.
 *
 * @since 1.0.0
 *
 * @param \APD\Contracts\DemoDataProviderInterface $provider The provider instance.
 * @return bool True if registered successfully.
 */
function apd_register_demo_provider( \APD\Contracts\DemoDataProviderInterface $provider ): bool {
	return \APD\Admin\DemoData\DemoDataProviderRegistry::get_instance()->register( $provider );
}

/**
 * Unregister a demo data provider.
 *
 * @since 1.0.0
 *
 * @param string $slug Provider slug to unregister.
 * @return bool True if unregistered.
 */
function apd_unregister_demo_provider( string $slug ): bool {
	return \APD\Admin\DemoData\DemoDataProviderRegistry::get_instance()->unregister( $slug );
}

/**
 * Check if a demo data provider is registered.
 *
 * @since 1.0.0
 *
 * @param string $slug Provider slug.
 * @return bool True if registered.
 */
function apd_has_demo_provider( string $slug ): bool {
	return \APD\Admin\DemoData\DemoDataProviderRegistry::get_instance()->has( $slug );
}

/**
 * Get a registered demo data provider.
 *
 * @since 1.0.0
 *
 * @param string $slug Provider slug.
 * @return \APD\Contracts\DemoDataProviderInterface|null The provider or null.
 */
function apd_get_demo_provider( string $slug ): ?\APD\Contracts\DemoDataProviderInterface {
	return \APD\Admin\DemoData\DemoDataProviderRegistry::get_instance()->get( $slug );
}
