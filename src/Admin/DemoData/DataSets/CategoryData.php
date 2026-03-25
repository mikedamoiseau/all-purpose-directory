<?php
/**
 * Category Data Set for Demo Data.
 *
 * Provides category hierarchy data for demo generation.
 *
 * @package APD\Admin\DemoData\DataSets
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Admin\DemoData\DataSets;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CategoryData
 *
 * Provides static category hierarchy data with icons and colors.
 *
 * @since 1.0.0
 */
final class CategoryData {

	/**
	 * Get the full category hierarchy.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array{name: string, description: string, icon: string, color: string, children?: array<string, array{name: string, description: string, icon: string, color: string}>}>
	 */
	public static function get_categories(): array {
		$categories = [
			'restaurants'   => [
				'name'        => __( 'Restaurants', 'damdir-directory' ),
				'description' => __( 'Places to eat and drink', 'damdir-directory' ),
				'icon'        => 'dashicons-food',
				'color'       => '#FF5722',
				'children'    => [
					'cafes-coffee' => [
						'name'        => __( 'Cafes & Coffee', 'damdir-directory' ),
						'description' => __( 'Coffee shops and cafes', 'damdir-directory' ),
						'icon'        => 'dashicons-coffee',
						'color'       => '#8D6E63',
					],
					'fine-dining'  => [
						'name'        => __( 'Fine Dining', 'damdir-directory' ),
						'description' => __( 'Upscale dining experiences', 'damdir-directory' ),
						'icon'        => 'dashicons-star-filled',
						'color'       => '#D4AF37',
					],
					'fast-food'    => [
						'name'        => __( 'Fast Food', 'damdir-directory' ),
						'description' => __( 'Quick service restaurants', 'damdir-directory' ),
						'icon'        => 'dashicons-food',
						'color'       => '#FF9800',
					],
				],
			],
			'hotels'        => [
				'name'        => __( 'Hotels & Lodging', 'damdir-directory' ),
				'description' => __( 'Places to stay', 'damdir-directory' ),
				'icon'        => 'dashicons-building',
				'color'       => '#3F51B5',
				'children'    => [
					'bed-breakfast'    => [
						'name'        => __( 'Bed & Breakfast', 'damdir-directory' ),
						'description' => __( 'Cozy B&B accommodations', 'damdir-directory' ),
						'icon'        => 'dashicons-admin-home',
						'color'       => '#E91E63',
					],
					'vacation-rentals' => [
						'name'        => __( 'Vacation Rentals', 'damdir-directory' ),
						'description' => __( 'Short-term rental properties', 'damdir-directory' ),
						'icon'        => 'dashicons-palmtree',
						'color'       => '#00BCD4',
					],
				],
			],
			'shopping'      => [
				'name'        => __( 'Shopping', 'damdir-directory' ),
				'description' => __( 'Retail stores and malls', 'damdir-directory' ),
				'icon'        => 'dashicons-cart',
				'color'       => '#4CAF50',
				'children'    => [
					'clothing'    => [
						'name'        => __( 'Clothing & Apparel', 'damdir-directory' ),
						'description' => __( 'Fashion and clothing stores', 'damdir-directory' ),
						'icon'        => 'dashicons-tag',
						'color'       => '#673AB7',
					],
					'electronics' => [
						'name'        => __( 'Electronics', 'damdir-directory' ),
						'description' => __( 'Tech and electronics stores', 'damdir-directory' ),
						'icon'        => 'dashicons-laptop',
						'color'       => '#2196F3',
					],
					'grocery'     => [
						'name'        => __( 'Grocery & Markets', 'damdir-directory' ),
						'description' => __( 'Food and grocery stores', 'damdir-directory' ),
						'icon'        => 'dashicons-carrot',
						'color'       => '#8BC34A',
					],
				],
			],
			'services'      => [
				'name'        => __( 'Services', 'damdir-directory' ),
				'description' => __( 'Local services and businesses', 'damdir-directory' ),
				'icon'        => 'dashicons-hammer',
				'color'       => '#795548',
				'children'    => [
					'auto-repair'   => [
						'name'        => __( 'Auto Repair', 'damdir-directory' ),
						'description' => __( 'Auto mechanics and repair shops', 'damdir-directory' ),
						'icon'        => 'dashicons-car',
						'color'       => '#607D8B',
					],
					'home-services' => [
						'name'        => __( 'Home Services', 'damdir-directory' ),
						'description' => __( 'Plumbers, electricians, contractors', 'damdir-directory' ),
						'icon'        => 'dashicons-admin-tools',
						'color'       => '#CDDC39',
					],
					'professional'  => [
						'name'        => __( 'Professional Services', 'damdir-directory' ),
						'description' => __( 'Legal, accounting, consulting', 'damdir-directory' ),
						'icon'        => 'dashicons-businessperson',
						'color'       => '#455A64',
					],
				],
			],
			'entertainment' => [
				'name'        => __( 'Entertainment', 'damdir-directory' ),
				'description' => __( 'Fun and leisure activities', 'damdir-directory' ),
				'icon'        => 'dashicons-tickets-alt',
				'color'       => '#9C27B0',
				'children'    => [
					'nightlife'         => [
						'name'        => __( 'Nightlife', 'damdir-directory' ),
						'description' => __( 'Bars, clubs, nightlife venues', 'damdir-directory' ),
						'icon'        => 'dashicons-drumstick',
						'color'       => '#311B92',
					],
					'movies-theater'    => [
						'name'        => __( 'Movies & Theater', 'damdir-directory' ),
						'description' => __( 'Cinemas and performing arts', 'damdir-directory' ),
						'icon'        => 'dashicons-video-alt3',
						'color'       => '#B71C1C',
					],
					'sports-recreation' => [
						'name'        => __( 'Sports & Recreation', 'damdir-directory' ),
						'description' => __( 'Gyms, sports facilities, parks', 'damdir-directory' ),
						'icon'        => 'dashicons-universal-access',
						'color'       => '#1B5E20',
					],
				],
			],
			'healthcare'    => [
				'name'        => __( 'Healthcare', 'damdir-directory' ),
				'description' => __( 'Medical and health services', 'damdir-directory' ),
				'icon'        => 'dashicons-heart',
				'color'       => '#F44336',
				'children'    => [
					'doctors'    => [
						'name'        => __( 'Doctors & Clinics', 'damdir-directory' ),
						'description' => __( 'Medical doctors and clinics', 'damdir-directory' ),
						'icon'        => 'dashicons-heart',
						'color'       => '#C62828',
					],
					'dentists'   => [
						'name'        => __( 'Dentists', 'damdir-directory' ),
						'description' => __( 'Dental care providers', 'damdir-directory' ),
						'icon'        => 'dashicons-smiley',
						'color'       => '#00ACC1',
					],
					'pharmacies' => [
						'name'        => __( 'Pharmacies', 'damdir-directory' ),
						'description' => __( 'Pharmacies and drug stores', 'damdir-directory' ),
						'icon'        => 'dashicons-plus-alt',
						'color'       => '#43A047',
					],
				],
			],
		];

		/**
		 * Filter the demo category hierarchy data.
		 *
		 * @since 1.0.0
		 *
		 * @param array $categories Category hierarchy.
		 */
		return apply_filters( 'apd_demo_category_data', $categories );
	}

	/**
	 * Get flat list of all category slugs.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $include_parents Whether to include parent categories.
	 * @return string[]
	 */
	public static function get_category_slugs( bool $include_parents = true ): array {
		$categories = self::get_categories();
		$slugs      = [];

		foreach ( $categories as $slug => $category ) {
			if ( $include_parents ) {
				$slugs[] = $slug;
			}

			if ( ! empty( $category['children'] ) ) {
				$slugs = array_merge( $slugs, array_keys( $category['children'] ) );
			}
		}

		return $slugs;
	}

	/**
	 * Get random category slugs for a listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int $count Number of categories to return (1-3).
	 * @return string[]
	 */
	public static function get_random_category_slugs( int $count = 1 ): array {
		$slugs = self::get_category_slugs( false ); // Only child categories.
		shuffle( $slugs );

		return array_slice( $slugs, 0, min( $count, count( $slugs ) ) );
	}

	/**
	 * Get the parent slug for a child category.
	 *
	 * @since 1.0.0
	 *
	 * @param string $child_slug Child category slug.
	 * @return string|null Parent slug or null if not found.
	 */
	public static function get_parent_slug( string $child_slug ): ?string {
		$categories = self::get_categories();

		foreach ( $categories as $parent_slug => $category ) {
			if ( ! empty( $category['children'] ) && isset( $category['children'][ $child_slug ] ) ) {
				return $parent_slug;
			}
		}

		return null;
	}
}
