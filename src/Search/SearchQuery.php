<?php
/**
 * Search Query handler.
 *
 * Handles search and filtering for listing queries by hooking into
 * WP_Query lifecycle to apply filters from URL parameters.
 *
 * @package APD\Search
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Search;

use WP_Query;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SearchQuery
 *
 * Manages search queries and filter application for listings.
 *
 * @since 1.0.0
 */
final class SearchQuery {

	/**
	 * Filter registry instance.
	 *
	 * @var FilterRegistry
	 */
	private FilterRegistry $registry;

	/**
	 * Whether hooks have been initialized.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Current query being modified.
	 *
	 * @var WP_Query|null
	 */
	private ?WP_Query $current_query = null;

	/**
	 * Searchable meta keys for keyword search.
	 *
	 * @var array<string>
	 */
	private array $searchable_meta_keys = [];

	/**
	 * Valid orderby options.
	 *
	 * @var array<string, string>
	 */
	private const ORDERBY_OPTIONS = [
		'date'   => 'post_date',
		'title'  => 'post_title',
		'views'  => '_apd_views_count',
		'random' => 'rand',
	];

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param FilterRegistry|null $registry Optional. Filter registry instance.
	 */
	public function __construct( ?FilterRegistry $registry = null ) {
		$this->registry = $registry ?? FilterRegistry::get_instance();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		add_action( 'pre_get_posts', [ $this, 'modify_main_query' ], 10 );
		add_filter( 'posts_join', [ $this, 'add_meta_join' ], 10, 2 );
		add_filter( 'posts_where', [ $this, 'add_meta_where' ], 10, 2 );
		add_filter( 'posts_distinct', [ $this, 'add_distinct' ], 10, 2 );

		$this->initialized = true;
	}

	/**
	 * Modify the main query for listing archives.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The query to modify.
	 * @return void
	 */
	public function modify_main_query( WP_Query $query ): void {
		// Only modify main query on frontend.
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Only modify listing queries.
		if ( ! $this->is_listing_query( $query ) ) {
			return;
		}

		$this->apply_filters( $query );
		$this->apply_orderby( $query );
		$this->apply_keyword_search( $query );
	}

	/**
	 * Apply registered filters to the query.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The query to modify.
	 * @return void
	 */
	public function apply_filters( WP_Query $query ): void {
		$active_filters = $this->registry->get_active_filters();

		foreach ( $active_filters as $name => $data ) {
			$filter = $data['filter'];
			$value  = $data['value'];

			$filter->modifyQuery( $query, $value );
		}

		/**
		 * Filter the query args after filters are applied.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Query $query          The modified query.
		 * @param array    $active_filters Active filters with values.
		 */
		do_action( 'apd_search_query_modified', $query, $active_filters );
	}

	/**
	 * Apply orderby parameter.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The query to modify.
	 * @return void
	 */
	public function apply_orderby( WP_Query $query ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby = isset( $_GET['apd_orderby'] ) ? sanitize_key( $_GET['apd_orderby'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order = isset( $_GET['apd_order'] ) ? strtoupper( sanitize_key( $_GET['apd_order'] ) ) : 'DESC';

		if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
			$order = 'DESC';
		}

		if ( empty( $orderby ) || ! isset( self::ORDERBY_OPTIONS[ $orderby ] ) ) {
			return;
		}

		$orderby_value = self::ORDERBY_OPTIONS[ $orderby ];

		if ( $orderby === 'views' ) {
			// Order by meta value.
			$query->set( 'meta_key', $orderby_value );
			$query->set( 'orderby', 'meta_value_num' );
		} elseif ( $orderby === 'random' ) {
			$query->set( 'orderby', 'rand' );
		} else {
			$query->set( 'orderby', $orderby_value );
		}

		$query->set( 'order', $order );
	}

	/**
	 * Apply keyword search to title, content, and meta fields.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The query to modify.
	 * @return void
	 */
	public function apply_keyword_search( WP_Query $query ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$keyword = isset( $_GET['apd_keyword'] ) ? sanitize_text_field( wp_unslash( $_GET['apd_keyword'] ) ) : '';

		if ( empty( $keyword ) ) {
			return;
		}

		// Set the search term for title/content search.
		$query->set( 's', $keyword );

		// Store the query for meta search hooks.
		$this->current_query        = $query;
		$this->searchable_meta_keys = $this->get_searchable_meta_keys();

		// Set a flag for the join/where hooks.
		$query->set( 'apd_meta_search', true );
		$query->set( 'apd_keyword', $keyword );
	}

	/**
	 * Add JOIN clause for meta search.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $join  The JOIN clause.
	 * @param WP_Query $query The query.
	 * @return string Modified JOIN clause.
	 */
	public function add_meta_join( string $join, WP_Query $query ): string {
		if ( ! $query->get( 'apd_meta_search' ) ) {
			return $join;
		}

		if ( empty( $this->searchable_meta_keys ) ) {
			return $join;
		}

		global $wpdb;

		// Add join to postmeta for searchable fields.
		$join .= " LEFT JOIN {$wpdb->postmeta} AS apd_pm ON ({$wpdb->posts}.ID = apd_pm.post_id)";

		return $join;
	}

	/**
	 * Add WHERE clause for meta search.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $where The WHERE clause.
	 * @param WP_Query $query The query.
	 * @return string Modified WHERE clause.
	 */
	public function add_meta_where( string $where, WP_Query $query ): string {
		if ( ! $query->get( 'apd_meta_search' ) ) {
			return $where;
		}

		if ( empty( $this->searchable_meta_keys ) ) {
			return $where;
		}

		global $wpdb;

		$keyword = $query->get( 'apd_keyword' );

		if ( empty( $keyword ) ) {
			return $where;
		}

		// Prepare meta key conditions.
		$meta_key_placeholders = implode( ',', array_fill( 0, count( $this->searchable_meta_keys ), '%s' ) );
		$like_keyword          = '%' . $wpdb->esc_like( $keyword ) . '%';

		// Build the meta search condition.
		// $meta_key_placeholders is a string of %s placeholders generated above.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $meta_key_placeholders contains safe %s placeholders.
		$meta_condition = $wpdb->prepare( "(apd_pm.meta_key IN ($meta_key_placeholders) AND apd_pm.meta_value LIKE %s)", array_merge( $this->searchable_meta_keys, [ $like_keyword ] ) );

		// Find and modify the existing search condition to include meta.
		// WordPress adds: AND (((post_title LIKE '%keyword%') OR (post_excerpt LIKE '%keyword%') OR (post_content LIKE '%keyword%')))
		// We want to add OR (meta condition) inside the parentheses.
		if ( preg_match( '/AND\s+\(\(\(.*?post_title.*?LIKE.*?\)\)\)/s', $where, $matches ) ) {
			$original_search = $matches[0];
			$modified_search = str_replace(
				')))',
				")) OR ($meta_condition))",
				$original_search
			);
			$where           = str_replace( $original_search, $modified_search, $where );
		}

		return $where;
	}

	/**
	 * Add DISTINCT to prevent duplicate results.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $distinct The DISTINCT clause.
	 * @param WP_Query $query    The query.
	 * @return string Modified DISTINCT clause.
	 */
	public function add_distinct( string $distinct, WP_Query $query ): string {
		if ( ! $query->get( 'apd_meta_search' ) ) {
			return $distinct;
		}

		if ( empty( $this->searchable_meta_keys ) ) {
			return $distinct;
		}

		return 'DISTINCT';
	}

	/**
	 * Check if this is a listing query.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query The query to check.
	 * @return bool True if this is a listing query.
	 */
	private function is_listing_query( WP_Query $query ): bool {
		$post_type = $query->get( 'post_type' );

		// Check if querying apd_listing post type.
		if ( $post_type === 'apd_listing' ) {
			return true;
		}

		// Check if on listing archive or taxonomy archive.
		if ( $query->is_post_type_archive( 'apd_listing' ) ) {
			return true;
		}

		if ( $query->is_tax( 'apd_category' ) || $query->is_tax( 'apd_tag' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get searchable meta keys from field registry.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string> Meta keys for searchable fields.
	 */
	private function get_searchable_meta_keys(): array {
		if ( ! function_exists( 'apd_field_registry' ) ) {
			return [];
		}

		$field_registry    = \apd_field_registry();
		$searchable_fields = $field_registry->get_searchable_fields();

		$meta_keys = [];
		foreach ( $searchable_fields as $field_name => $field ) {
			// Sanitize meta keys to prevent SQL injection.
			$meta_keys[] = sanitize_key( $field_registry->get_meta_key( $field_name ) );
		}

		/**
		 * Filter the searchable meta keys.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string> $meta_keys Searchable meta keys.
		 */
		$filtered_keys = apply_filters( 'apd_searchable_meta_keys', $meta_keys );

		// Sanitize any keys added via the filter to prevent SQL injection.
		return array_map( 'sanitize_key', $filtered_keys );
	}

	/**
	 * Get filtered listings.
	 *
	 * Convenience method to run a filtered query.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $args Additional query args.
	 * @return WP_Query The query result.
	 */
	public function get_filtered_listings( array $args = [] ): WP_Query {
		$defaults = [
			'post_type'      => 'apd_listing',
			'post_status'    => 'publish',
			'posts_per_page' => get_option( 'posts_per_page', 10 ),
		];

		$query_args = wp_parse_args( $args, $defaults );

		/**
		 * Filter the query args before running filtered query.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $query_args Query arguments.
		 */
		$query_args = apply_filters( 'apd_search_query_args', $query_args );

		$query = new WP_Query( $query_args );

		// Apply filters manually for non-main queries.
		$this->apply_keyword_search( $query );
		$this->apply_filters( $query );
		$this->apply_orderby( $query );

		return $query;
	}

	/**
	 * Get available orderby options.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, string> Orderby options with labels.
	 */
	public function get_orderby_options(): array {
		$options = [
			'date'   => __( 'Newest First', 'all-purpose-directory' ),
			'title'  => __( 'Title A-Z', 'all-purpose-directory' ),
			'views'  => __( 'Most Viewed', 'all-purpose-directory' ),
			'random' => __( 'Random', 'all-purpose-directory' ),
		];

		/**
		 * Filter the orderby options.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $options Orderby options.
		 */
		return apply_filters( 'apd_orderby_options', $options );
	}

	/**
	 * Get current orderby value.
	 *
	 * @since 1.0.0
	 *
	 * @return string Current orderby value.
	 */
	public function get_current_orderby(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby = isset( $_GET['apd_orderby'] ) ? sanitize_key( $_GET['apd_orderby'] ) : 'date';

		if ( ! isset( self::ORDERBY_OPTIONS[ $orderby ] ) ) {
			return 'date';
		}

		return $orderby;
	}

	/**
	 * Get current order direction.
	 *
	 * @since 1.0.0
	 *
	 * @return string Current order direction (ASC or DESC).
	 */
	public function get_current_order(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order = isset( $_GET['apd_order'] ) ? strtoupper( sanitize_key( $_GET['apd_order'] ) ) : 'DESC';

		return in_array( $order, [ 'ASC', 'DESC' ], true ) ? $order : 'DESC';
	}

	/**
	 * Get current keyword search term.
	 *
	 * @since 1.0.0
	 *
	 * @return string Current search keyword.
	 */
	public function get_current_keyword(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['apd_keyword'] ) ? sanitize_text_field( wp_unslash( $_GET['apd_keyword'] ) ) : '';
	}
}
