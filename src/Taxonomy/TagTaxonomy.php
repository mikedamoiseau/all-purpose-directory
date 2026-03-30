<?php
/**
 * Tag taxonomy registration.
 *
 * @package APD\Taxonomy
 */

declare(strict_types=1);

namespace APD\Taxonomy;

use APD\Listing\PostType;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TagTaxonomy
 *
 * Handles registration of the apd_tag taxonomy.
 * This is a non-hierarchical taxonomy (like WordPress tags) for
 * adding flexible labels/tags to listings.
 *
 * @since 1.0.0
 */
final class TagTaxonomy {

	/**
	 * Taxonomy slug.
	 */
	public const TAXONOMY = 'apd_tag';

	/**
	 * Register the tag taxonomy.
	 *
	 * @return void
	 */
	public function register(): void {
		register_taxonomy(
			self::TAXONOMY,
			PostType::POST_TYPE,
			$this->get_args()
		);
	}

	/**
	 * Get taxonomy labels.
	 *
	 * @return array<string, string>
	 */
	private function get_labels(): array {
		return [
			'name'                       => _x( 'Tags', 'taxonomy general name', 'damdir-directory' ),
			'singular_name'              => _x( 'Tag', 'taxonomy singular name', 'damdir-directory' ),
			'menu_name'                  => _x( 'Tags', 'admin menu', 'damdir-directory' ),
			'all_items'                  => __( 'All Tags', 'damdir-directory' ),
			'new_item_name'              => __( 'New Tag Name', 'damdir-directory' ),
			'add_new_item'               => __( 'Add New Tag', 'damdir-directory' ),
			'edit_item'                  => __( 'Edit Tag', 'damdir-directory' ),
			'update_item'                => __( 'Update Tag', 'damdir-directory' ),
			'view_item'                  => __( 'View Tag', 'damdir-directory' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'damdir-directory' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'damdir-directory' ),
			'choose_from_most_used'      => __( 'Choose from the most used tags', 'damdir-directory' ),
			'popular_items'              => __( 'Popular Tags', 'damdir-directory' ),
			'search_items'               => __( 'Search Tags', 'damdir-directory' ),
			'not_found'                  => __( 'No tags found.', 'damdir-directory' ),
			'no_terms'                   => __( 'No tags', 'damdir-directory' ),
			'items_list'                 => __( 'Tags list', 'damdir-directory' ),
			'items_list_navigation'      => __( 'Tags list navigation', 'damdir-directory' ),
			'back_to_items'              => __( '&larr; Back to Tags', 'damdir-directory' ),
		];
	}

	/**
	 * Get taxonomy arguments.
	 *
	 * @return array<string, mixed>
	 */
	private function get_args(): array {
		return [
			'labels'             => $this->get_labels(),
			'description'        => __( 'Tags for flexible labeling of directory listings.', 'damdir-directory' ),
			'hierarchical'       => false,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_tagcloud'      => true,
			'show_in_quick_edit' => true,
			'show_admin_column'  => true,
			'rewrite'            => [
				'slug'       => 'listing-tag',
				'with_front' => false,
			],
			'query_var'          => true,
			'show_in_rest'       => true,
			'rest_base'          => 'apd_tag',
		];
	}
}
