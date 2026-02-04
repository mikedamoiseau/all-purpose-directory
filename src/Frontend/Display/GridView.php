<?php
/**
 * Grid View Class.
 *
 * Displays listings in a responsive grid layout with configurable columns.
 *
 * @package APD\Frontend\Display
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Frontend\Display;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GridView
 *
 * @since 1.0.0
 */
final class GridView extends AbstractView {

	/**
	 * View type identifier.
	 *
	 * @var string
	 */
	protected string $type = 'grid';

	/**
	 * View label.
	 *
	 * @var string
	 */
	protected string $label = 'Grid';

	/**
	 * Dashicon class.
	 *
	 * @var string
	 */
	protected string $icon = 'dashicons-grid-view';

	/**
	 * Template name.
	 *
	 * @var string
	 */
	protected string $template = 'listing-card';

	/**
	 * Supported features.
	 *
	 * @var array<string>
	 */
	protected array $supports = [
		'columns',
		'image',
		'excerpt',
		'badge',
		'hover_effect',
	];

	/**
	 * Default configuration values.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults = [
		'columns'           => 3,
		'show_image'        => true,
		'show_excerpt'      => true,
		'excerpt_length'    => 15,
		'show_category'     => true,
		'show_badge'        => true,
		'show_price'        => true,
		'show_rating'       => true,
		'show_favorite'     => true,
		'show_view_details' => true,
		'image_size'        => 'medium',
		'card_hover'        => true,
	];

	/**
	 * Valid column values.
	 *
	 * @var array<int>
	 */
	private const VALID_COLUMNS = [ 2, 3, 4 ];

	/**
	 * Get the view label.
	 *
	 * @since 1.0.0
	 *
	 * @return string Human-readable label.
	 */
	public function getLabel(): string {
		return __( 'Grid', 'all-purpose-directory' );
	}

	/**
	 * Get the number of columns.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of columns (2, 3, or 4).
	 */
	public function getColumns(): int {
		$columns = (int) $this->getConfigValue( 'columns', 3 );

		// Validate columns.
		if ( ! in_array( $columns, self::VALID_COLUMNS, true ) ) {
			$columns = 3;
		}

		return $columns;
	}

	/**
	 * Set the number of columns.
	 *
	 * @since 1.0.0
	 *
	 * @param int $columns Number of columns (2, 3, or 4).
	 * @return self
	 */
	public function setColumns( int $columns ): self {
		if ( in_array( $columns, self::VALID_COLUMNS, true ) ) {
			$this->setConfigValue( 'columns', $columns );
		}

		return $this;
	}

	/**
	 * Get the CSS classes for the listings container.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string> CSS classes.
	 */
	public function getContainerClasses(): array {
		$classes   = parent::getContainerClasses();
		$classes[] = 'apd-listings--columns-' . $this->getColumns();

		return $classes;
	}

	/**
	 * Get data attributes for the listings container.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Data attributes.
	 */
	public function getContainerAttributes(): array {
		$attributes            = parent::getContainerAttributes();
		$attributes['columns'] = (string) $this->getColumns();

		return $attributes;
	}

	/**
	 * Render a single listing.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $listing_id Listing post ID.
	 * @param array $args       Additional arguments.
	 * @return string Rendered HTML.
	 */
	public function renderListing( int $listing_id, array $args = [] ): string {
		// Add grid-specific config to args.
		$args['show_image']        = $this->getConfigValue( 'show_image', true );
		$args['show_excerpt']      = $this->getConfigValue( 'show_excerpt', true );
		$args['excerpt_length']    = $this->getConfigValue( 'excerpt_length', 15 );
		$args['show_category']     = $this->getConfigValue( 'show_category', true );
		$args['show_badge']        = $this->getConfigValue( 'show_badge', true );
		$args['show_price']        = $this->getConfigValue( 'show_price', true );
		$args['show_rating']       = $this->getConfigValue( 'show_rating', true );
		$args['show_favorite']     = $this->getConfigValue( 'show_favorite', true );
		$args['show_view_details'] = $this->getConfigValue( 'show_view_details', true );
		$args['image_size']        = $this->getConfigValue( 'image_size', 'medium' );

		return parent::renderListing( $listing_id, $args );
	}

	/**
	 * Get responsive breakpoint information.
	 *
	 * Returns the column count at each breakpoint for responsive design.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, int> Breakpoint => columns mapping.
	 */
	public function getResponsiveColumns(): array {
		$desktop_columns = $this->getColumns();

		// CSS handles responsiveness, but this provides the logic.
		$responsive = [
			'desktop' => $desktop_columns,
			'tablet'  => min( 2, $desktop_columns ),
			'mobile'  => 1,
		];

		// Special case for 4 columns - goes to 3 on tablet.
		if ( $desktop_columns === 4 ) {
			$responsive['tablet'] = 3;
		}

		/**
		 * Filter the responsive column configuration.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, int> $responsive Column counts per breakpoint.
		 * @param GridView           $view       The view instance.
		 */
		return apply_filters( 'apd_grid_responsive_columns', $responsive, $this );
	}

	/**
	 * Render grid with specific query arguments.
	 *
	 * Convenience method for rendering a grid with custom query args.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query_args WP_Query arguments.
	 * @param array $render_args Render arguments.
	 * @return string Rendered HTML.
	 */
	public function render( array $query_args = [], array $render_args = [] ): string {
		$query = $this->getListings( $query_args );
		return $this->renderListings( $query, $render_args );
	}

	/**
	 * Render grid with pagination.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query_args WP_Query arguments.
	 * @param array $render_args Render arguments.
	 * @return string Rendered HTML with pagination.
	 */
	public function renderWithPagination( array $query_args = [], array $render_args = [] ): string {
		$query  = $this->getListings( $query_args );
		$output = $this->renderListings( $query, $render_args );
		$output .= $this->renderPagination( $query );

		return $output;
	}
}
