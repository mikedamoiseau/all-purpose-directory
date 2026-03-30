<?php
/**
 * Category taxonomy registration and management.
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
 * Class CategoryTaxonomy
 *
 * Handles registration and management of the apd_category taxonomy.
 * This is a hierarchical taxonomy (like WordPress categories) for
 * organizing listings into categories.
 *
 * @since 1.0.0
 */
final class CategoryTaxonomy {

	/**
	 * Taxonomy slug.
	 */
	public const TAXONOMY = 'apd_category';

	/**
	 * Meta key for category icon.
	 */
	public const META_ICON = '_apd_category_icon';

	/**
	 * Meta key for category color.
	 */
	public const META_COLOR = '_apd_category_color';

	/**
	 * Register the category taxonomy.
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
	 * Initialize admin hooks for term meta fields.
	 *
	 * @return void
	 */
	public function init_admin(): void {
		// Only run in admin context.
		if ( ! is_admin() ) {
			return;
		}

		// Add form fields.
		add_action( self::TAXONOMY . '_add_form_fields', [ $this, 'render_add_form_fields' ] );
		add_action( self::TAXONOMY . '_edit_form_fields', [ $this, 'render_edit_form_fields' ] );

		// Save term meta.
		add_action( 'created_' . self::TAXONOMY, [ $this, 'save_term_meta' ] );
		add_action( 'edited_' . self::TAXONOMY, [ $this, 'save_term_meta' ] );

		// Admin columns.
		add_filter( 'manage_edit-' . self::TAXONOMY . '_columns', [ $this, 'add_columns' ] );
		add_filter( 'manage_' . self::TAXONOMY . '_custom_column', [ $this, 'render_column' ], 10, 3 );

		// Enqueue admin assets.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Get taxonomy labels.
	 *
	 * @return array<string, string>
	 */
	private function get_labels(): array {
		return [
			'name'                       => _x( 'Categories', 'taxonomy general name', 'damdir-directory' ),
			'singular_name'              => _x( 'Category', 'taxonomy singular name', 'damdir-directory' ),
			'menu_name'                  => _x( 'Categories', 'admin menu', 'damdir-directory' ),
			'all_items'                  => __( 'All Categories', 'damdir-directory' ),
			'parent_item'                => __( 'Parent Category', 'damdir-directory' ),
			'parent_item_colon'          => __( 'Parent Category:', 'damdir-directory' ),
			'new_item_name'              => __( 'New Category Name', 'damdir-directory' ),
			'add_new_item'               => __( 'Add New Category', 'damdir-directory' ),
			'edit_item'                  => __( 'Edit Category', 'damdir-directory' ),
			'update_item'                => __( 'Update Category', 'damdir-directory' ),
			'view_item'                  => __( 'View Category', 'damdir-directory' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'damdir-directory' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'damdir-directory' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'damdir-directory' ),
			'popular_items'              => __( 'Popular Categories', 'damdir-directory' ),
			'search_items'               => __( 'Search Categories', 'damdir-directory' ),
			'not_found'                  => __( 'No categories found.', 'damdir-directory' ),
			'no_terms'                   => __( 'No categories', 'damdir-directory' ),
			'items_list'                 => __( 'Categories list', 'damdir-directory' ),
			'items_list_navigation'      => __( 'Categories list navigation', 'damdir-directory' ),
			'back_to_items'              => __( '&larr; Back to Categories', 'damdir-directory' ),
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
			'description'        => __( 'Listing categories for organizing directory entries.', 'damdir-directory' ),
			'hierarchical'       => true,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_tagcloud'      => true,
			'show_in_quick_edit' => true,
			'show_admin_column'  => false, // We handle this in AdminColumns.
			'rewrite'            => [
				'slug'         => 'listing-category',
				'with_front'   => false,
				'hierarchical' => true,
			],
			'query_var'          => true,
			'show_in_rest'       => true,
			'rest_base'          => 'apd_category',
		];
	}

	/**
	 * Render fields for the add term form.
	 *
	 * @return void
	 */
	public function render_add_form_fields(): void {
		?>
		<div class="form-field term-icon-wrap">
			<label for="apd-category-icon"><?php esc_html_e( 'Icon', 'damdir-directory' ); ?></label>
			<select name="apd_category_icon" id="apd-category-icon" class="apd-dashicon-select">
				<option value=""><?php esc_html_e( '&mdash; No Icon &mdash;', 'damdir-directory' ); ?></option>
				<?php $this->render_dashicon_options(); ?>
			</select>
			<p class="description"><?php esc_html_e( 'Select a dashicon to represent this category.', 'damdir-directory' ); ?></p>
		</div>

		<div class="form-field term-color-wrap">
			<label for="apd-category-color"><?php esc_html_e( 'Color', 'damdir-directory' ); ?></label>
			<input type="text" name="apd_category_color" id="apd-category-color" class="apd-color-picker" value="" data-default-color="">
			<p class="description"><?php esc_html_e( 'Choose a color to represent this category.', 'damdir-directory' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render fields for the edit term form.
	 *
	 * @param \WP_Term $term Current term object.
	 * @return void
	 */
	public function render_edit_form_fields( \WP_Term $term ): void {
		$icon  = get_term_meta( $term->term_id, self::META_ICON, true );
		$color = get_term_meta( $term->term_id, self::META_COLOR, true );
		?>
		<tr class="form-field term-icon-wrap">
			<th scope="row">
				<label for="apd-category-icon"><?php esc_html_e( 'Icon', 'damdir-directory' ); ?></label>
			</th>
			<td>
				<select name="apd_category_icon" id="apd-category-icon" class="apd-dashicon-select">
					<option value=""><?php esc_html_e( '&mdash; No Icon &mdash;', 'damdir-directory' ); ?></option>
					<?php $this->render_dashicon_options( $icon ); ?>
				</select>
				<p class="description"><?php esc_html_e( 'Select a dashicon to represent this category.', 'damdir-directory' ); ?></p>
				<?php if ( $icon ) : ?>
					<p class="apd-icon-preview">
						<span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
						<span class="screen-reader-text"><?php esc_html_e( 'Current icon preview', 'damdir-directory' ); ?></span>
					</p>
				<?php endif; ?>
			</td>
		</tr>

		<tr class="form-field term-color-wrap">
			<th scope="row">
				<label for="apd-category-color"><?php esc_html_e( 'Color', 'damdir-directory' ); ?></label>
			</th>
			<td>
				<input type="text" name="apd_category_color" id="apd-category-color" class="apd-color-picker" value="<?php echo esc_attr( $color ); ?>" data-default-color="">
				<p class="description"><?php esc_html_e( 'Choose a color to represent this category.', 'damdir-directory' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save term meta when a term is created or updated.
	 *
	 * Nonce verification is handled by WordPress core for taxonomy term forms.
	 * See: wp-admin/edit-tags.php which calls check_admin_referer() before
	 * wp_insert_term() or wp_update_term() which fire our hook.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function save_term_meta( int $term_id ): void {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by WordPress core in edit-tags.php

		// Save icon.
		if ( isset( $_POST['apd_category_icon'] ) ) {
			$icon = sanitize_text_field( wp_unslash( $_POST['apd_category_icon'] ) );

			// Validate it's a valid dashicon class.
			if ( empty( $icon ) || str_starts_with( $icon, 'dashicons-' ) ) {
				update_term_meta( $term_id, self::META_ICON, $icon );
			}
		}

		// Save color.
		if ( isset( $_POST['apd_category_color'] ) ) {
			$color = sanitize_hex_color( wp_unslash( $_POST['apd_category_color'] ) );
			update_term_meta( $term_id, self::META_COLOR, $color ? $color : '' );
		}

        // phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Add custom columns to the taxonomy admin list.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string> Modified columns.
	 */
	public function add_columns( array $columns ): array {
		$new_columns = [];

		// Checkbox first.
		if ( isset( $columns['cb'] ) ) {
			$new_columns['cb'] = $columns['cb'];
		}

		// Icon column.
		$new_columns['icon'] = __( 'Icon', 'damdir-directory' );

		// Name.
		if ( isset( $columns['name'] ) ) {
			$new_columns['name'] = $columns['name'];
		}

		// Description.
		if ( isset( $columns['description'] ) ) {
			$new_columns['description'] = $columns['description'];
		}

		// Slug.
		if ( isset( $columns['slug'] ) ) {
			$new_columns['slug'] = $columns['slug'];
		}

		// Color column.
		$new_columns['color'] = __( 'Color', 'damdir-directory' );

		// Posts count.
		if ( isset( $columns['posts'] ) ) {
			$new_columns['posts'] = $columns['posts'];
		}

		return $new_columns;
	}

	/**
	 * Render content for custom columns.
	 *
	 * @param string $content     Column content.
	 * @param string $column_name Column name.
	 * @param int    $term_id     Term ID.
	 * @return string Modified column content.
	 */
	public function render_column( string $content, string $column_name, int $term_id ): string {
		switch ( $column_name ) {
			case 'icon':
				$icon = get_term_meta( $term_id, self::META_ICON, true );
				if ( $icon ) {
					$content = sprintf(
						'<span class="dashicons %s" aria-hidden="true"></span><span class="screen-reader-text">%s</span>',
						esc_attr( $icon ),
						esc_html( $icon )
					);
				} else {
					$content = '<span aria-hidden="true">—</span><span class="screen-reader-text">' . esc_html__( 'No icon', 'damdir-directory' ) . '</span>';
				}
				break;

			case 'color':
				$color = get_term_meta( $term_id, self::META_COLOR, true );
				if ( $color ) {
					$content = sprintf(
						'<span class="apd-color-swatch" style="background-color: %s;" title="%s" aria-label="%s"></span>',
						esc_attr( $color ),
						esc_attr( $color ),
						/* translators: %s: hex color code */
						esc_attr( sprintf( __( 'Color: %s', 'damdir-directory' ), $color ) )
					);
				} else {
					$content = '<span aria-hidden="true">—</span><span class="screen-reader-text">' . esc_html__( 'No color', 'damdir-directory' ) . '</span>';
				}
				break;
		}

		return $content;
	}

	/**
	 * Enqueue admin assets for taxonomy screens.
	 *
	 * @param string $hook_suffix The current admin page.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		// Only enqueue on taxonomy screens.
		$screen = get_current_screen();
		if ( ! $screen || $screen->taxonomy !== self::TAXONOMY ) {
			return;
		}

		// Enqueue WordPress color picker.
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		// Add inline script to initialize color picker.
		wp_add_inline_script(
			'wp-color-picker',
			"jQuery(document).ready(function($) {
                $('.apd-color-picker').wpColorPicker();
            });"
		);

		// Add inline styles for admin columns.
		wp_add_inline_style(
			'wp-admin',
			'.apd-color-swatch {
                display: inline-block;
                width: 20px;
                height: 20px;
                border-radius: 3px;
                border: 1px solid #ddd;
                vertical-align: middle;
            }
            .apd-icon-preview .dashicons {
                font-size: 24px;
                width: 24px;
                height: 24px;
                margin-top: 8px;
            }
            .column-icon { width: 50px; text-align: center; }
            .column-color { width: 60px; text-align: center; }'
		);
	}

	/**
	 * Render dashicon options for select dropdown.
	 *
	 * @param string $selected Currently selected icon.
	 * @return void
	 */
	private function render_dashicon_options( string $selected = '' ): void {
		$icons = $this->get_dashicons();

		foreach ( $icons as $group => $group_icons ) {
			printf( '<optgroup label="%s">', esc_attr( $group ) );

			foreach ( $group_icons as $icon => $label ) {
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $icon ),
					selected( $selected, $icon, false ),
					esc_html( $label )
				);
			}

			echo '</optgroup>';
		}
	}

	/**
	 * Get available dashicons organized by category.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function get_dashicons(): array {
		return [
			__( 'Business', 'damdir-directory' )           => [
				'dashicons-store'          => __( 'Store', 'damdir-directory' ),
				'dashicons-cart'           => __( 'Cart', 'damdir-directory' ),
				'dashicons-portfolio'      => __( 'Portfolio', 'damdir-directory' ),
				'dashicons-building'       => __( 'Building', 'damdir-directory' ),
				'dashicons-money-alt'      => __( 'Money', 'damdir-directory' ),
				'dashicons-bank'           => __( 'Bank', 'damdir-directory' ),
				'dashicons-businessman'    => __( 'Businessman', 'damdir-directory' ),
				'dashicons-businessperson' => __( 'Business Person', 'damdir-directory' ),
				'dashicons-businesswoman'  => __( 'Businesswoman', 'damdir-directory' ),
			],
			__( 'Food & Drink', 'damdir-directory' )       => [
				'dashicons-food'   => __( 'Food', 'damdir-directory' ),
				'dashicons-coffee' => __( 'Coffee', 'damdir-directory' ),
				'dashicons-carrot' => __( 'Carrot', 'damdir-directory' ),
				'dashicons-beer'   => __( 'Beer', 'damdir-directory' ),
			],
			__( 'Location', 'damdir-directory' )           => [
				'dashicons-location'        => __( 'Location', 'damdir-directory' ),
				'dashicons-location-alt'    => __( 'Location (alt)', 'damdir-directory' ),
				'dashicons-admin-site'      => __( 'Globe', 'damdir-directory' ),
				'dashicons-admin-site-alt3' => __( 'Globe (alt)', 'damdir-directory' ),
			],
			__( 'Transportation', 'damdir-directory' )     => [
				'dashicons-car'      => __( 'Car', 'damdir-directory' ),
				'dashicons-airplane' => __( 'Airplane', 'damdir-directory' ),
			],
			__( 'Activities', 'damdir-directory' )         => [
				'dashicons-palmtree'    => __( 'Palm Tree', 'damdir-directory' ),
				'dashicons-pets'        => __( 'Pets', 'damdir-directory' ),
				'dashicons-games'       => __( 'Games', 'damdir-directory' ),
				'dashicons-tickets-alt' => __( 'Tickets', 'damdir-directory' ),
				'dashicons-art'         => __( 'Art', 'damdir-directory' ),
				'dashicons-camera'      => __( 'Camera', 'damdir-directory' ),
				'dashicons-video-alt3'  => __( 'Video', 'damdir-directory' ),
			],
			__( 'Education & Health', 'damdir-directory' ) => [
				'dashicons-book'               => __( 'Book', 'damdir-directory' ),
				'dashicons-book-alt'           => __( 'Book (alt)', 'damdir-directory' ),
				'dashicons-welcome-learn-more' => __( 'Learn', 'damdir-directory' ),
				'dashicons-heart'              => __( 'Heart', 'damdir-directory' ),
				'dashicons-plus-alt'           => __( 'Medical Plus', 'damdir-directory' ),
			],
			__( 'Technology', 'damdir-directory' )         => [
				'dashicons-laptop'     => __( 'Laptop', 'damdir-directory' ),
				'dashicons-smartphone' => __( 'Smartphone', 'damdir-directory' ),
				'dashicons-tablet'     => __( 'Tablet', 'damdir-directory' ),
				'dashicons-desktop'    => __( 'Desktop', 'damdir-directory' ),
			],
			__( 'Services', 'damdir-directory' )           => [
				'dashicons-hammer'        => __( 'Hammer', 'damdir-directory' ),
				'dashicons-admin-tools'   => __( 'Tools', 'damdir-directory' ),
				'dashicons-admin-home'    => __( 'Home', 'damdir-directory' ),
				'dashicons-admin-generic' => __( 'Cog', 'damdir-directory' ),
				'dashicons-shield'        => __( 'Shield', 'damdir-directory' ),
				'dashicons-shield-alt'    => __( 'Shield (alt)', 'damdir-directory' ),
			],
			__( 'Communication', 'damdir-directory' )      => [
				'dashicons-phone'      => __( 'Phone', 'damdir-directory' ),
				'dashicons-email'      => __( 'Email', 'damdir-directory' ),
				'dashicons-email-alt'  => __( 'Email (alt)', 'damdir-directory' ),
				'dashicons-share'      => __( 'Share', 'damdir-directory' ),
				'dashicons-networking' => __( 'Networking', 'damdir-directory' ),
			],
			__( 'Other', 'damdir-directory' )              => [
				'dashicons-star-filled' => __( 'Star (filled)', 'damdir-directory' ),
				'dashicons-star-empty'  => __( 'Star (empty)', 'damdir-directory' ),
				'dashicons-flag'        => __( 'Flag', 'damdir-directory' ),
				'dashicons-tag'         => __( 'Tag', 'damdir-directory' ),
				'dashicons-awards'      => __( 'Award', 'damdir-directory' ),
				'dashicons-megaphone'   => __( 'Megaphone', 'damdir-directory' ),
				'dashicons-lightbulb'   => __( 'Lightbulb', 'damdir-directory' ),
				'dashicons-info'        => __( 'Info', 'damdir-directory' ),
			],
		];
	}

	/**
	 * Get category icon.
	 *
	 * @param int $term_id Term ID.
	 * @return string Dashicon class or empty string.
	 */
	public static function get_icon( int $term_id ): string {
		return (string) get_term_meta( $term_id, self::META_ICON, true );
	}

	/**
	 * Get category color.
	 *
	 * @param int $term_id Term ID.
	 * @return string Hex color or empty string.
	 */
	public static function get_color( int $term_id ): string {
		return (string) get_term_meta( $term_id, self::META_COLOR, true );
	}
}
