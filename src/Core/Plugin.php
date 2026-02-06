<?php
/**
 * Main plugin class.
 *
 * @package APD\Core
 */

declare(strict_types=1);

namespace APD\Core;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 *
 * Main plugin singleton that bootstraps all functionality.
 */
final class Plugin {

	/**
	 * Plugin version.
	 */
	public const VERSION = APD_VERSION;

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Assets manager instance.
	 *
	 * @var Assets
	 */
	private Assets $assets;

	/**
	 * Template loader instance.
	 *
	 * @var TemplateLoader|null
	 */
	private ?TemplateLoader $template_loader = null;

	/**
	 * Search query instance.
	 *
	 * @var \APD\Search\SearchQuery|null
	 */
	private ?\APD\Search\SearchQuery $search_query = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_components();
		$this->init_hooks();

		/**
		 * Fires after the plugin is fully initialized.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_init' );
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \Exception Always throws exception.
	 */
	public function __wakeup(): void {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	/**
	 * Initialize plugin components.
	 *
	 * @return void
	 */
	private function init_components(): void {
		// Initialize assets manager.
		$this->assets = new Assets( self::VERSION, APD_PLUGIN_URL );
		$this->assets->init();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Load text domain for translations (early priority).
		add_action( 'init', [ $this, 'load_textdomain' ], 0 );

		// Initialize module registry (priority 1, after text domain).
		add_action( 'init', [ $this, 'init_modules' ], 1 );

		// Register post types and taxonomies.
		add_action( 'init', [ $this, 'register_post_types' ], 5 );
		add_action( 'init', [ $this, 'register_taxonomies' ], 5 );

		// Initialize admin columns for listings.
		$admin_columns = new \APD\Listing\AdminColumns();
		$admin_columns->init();

		// Initialize listing meta box for custom fields.
		$meta_box = new \APD\Admin\ListingMetaBox();
		$meta_box->init();

		// Initialize search query handler.
		$this->search_query = new \APD\Search\SearchQuery();
		$this->search_query->init();

		// Initialize template loader for archive/single templates.
		$this->template_loader = new TemplateLoader();
		$this->template_loader->init();

		// Register default filters on init.
		add_action( 'init', [ $this, 'register_default_filters' ], 15 );

		// Register shortcodes on init.
		add_action( 'init', [ $this, 'register_shortcodes' ], 20 );

		// Initialize Gutenberg blocks.
		$block_manager = \APD\Blocks\BlockManager::get_instance();
		$block_manager->init();

		// Initialize submission handler for frontend form processing.
		$submission_handler = new \APD\Frontend\Submission\SubmissionHandler();
		$submission_handler->init();

		// Register AJAX handlers.
		add_action( 'wp_ajax_apd_filter_listings', [ $this, 'ajax_filter_listings' ] );
		add_action( 'wp_ajax_nopriv_apd_filter_listings', [ $this, 'ajax_filter_listings' ] );

		// Register AJAX handlers for dashboard listing actions.
		add_action( 'wp_ajax_apd_delete_listing', [ $this, 'ajax_delete_listing' ] );
		add_action( 'wp_ajax_apd_update_listing_status', [ $this, 'ajax_update_listing_status' ] );

		// Initialize My Listings action handling.
		$my_listings = \APD\Frontend\Dashboard\MyListings::get_instance();
		$my_listings->init();

		// Initialize Favorites system.
		$favorites = \APD\User\Favorites::get_instance();
		$favorites->init();

		// Initialize Favorite Toggle UI.
		$favorite_toggle = \APD\User\FavoriteToggle::get_instance();
		$favorite_toggle->init();

		// Initialize Review Manager.
		$review_manager = \APD\Review\ReviewManager::get_instance();
		$review_manager->init();

		// Initialize Rating Calculator.
		$rating_calculator = \APD\Review\RatingCalculator::get_instance();
		$rating_calculator->init();

		// Initialize Review Form.
		$review_form = \APD\Review\ReviewForm::get_instance();
		$review_form->init();

		// Initialize Review Handler.
		$review_handler = \APD\Review\ReviewHandler::get_instance();
		$review_handler->init();

		// Initialize Review Display.
		$review_display = \APD\Review\ReviewDisplay::get_instance();
		$review_display->init();

		// Initialize Review Moderation admin page.
		$review_moderation = \APD\Admin\ReviewModeration::get_instance();
		$review_moderation->init();

		// Initialize Contact Form system.
		$contact_form = \APD\Contact\ContactForm::get_instance();
		$contact_form->init();

		// Initialize Contact Handler for AJAX processing.
		$contact_handler = \APD\Contact\ContactHandler::get_instance();
		$contact_handler->init();

		// Initialize Inquiry Tracker for logging contact inquiries.
		$inquiry_tracker = \APD\Contact\InquiryTracker::get_instance();
		$inquiry_tracker->init();

		// Initialize Email Manager for notifications.
		$email_manager = \APD\Email\EmailManager::get_instance();
		$email_manager->init();

		// Initialize Admin Settings page.
		$settings = \APD\Admin\Settings::get_instance();
		$settings->init();

		// Initialize Modules admin page.
		$modules_page = \APD\Module\ModulesAdminPage::get_instance();
		$modules_page->init();

		// Initialize Demo Data page (admin only).
		$demo_data_page = \APD\Admin\DemoData\DemoDataPage::get_instance();
		$demo_data_page->init();

		// Initialize REST API controller.
		$rest_controller = \APD\Api\RestController::get_instance();
		$rest_controller->init();

		// Register REST API endpoints.
		add_action( 'apd_register_rest_routes', [ $this, 'register_rest_endpoints' ] );

		// Initialize Performance manager for caching.
		Performance::get_instance();

		/**
		 * Fires after plugin hooks are initialized.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_loaded' );
	}

	/**
	 * Register post types.
	 *
	 * @return void
	 */
	/**
	 * Load plugin text domain for translations.
	 *
	 * Loads translations from:
	 * 1. WP_LANG_DIR/plugins/all-purpose-directory-{locale}.mo (global)
	 * 2. plugin/languages/all-purpose-directory-{locale}.mo (plugin)
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		/*
		 * Since WordPress 4.6, translations for plugins hosted on WordPress.org
		 * are loaded automatically. The apd_textdomain_loaded action fires here
		 * for extensions that need to hook into translation loading.
		 *
		 * For self-hosted distributions requiring custom translations, use:
		 * add_action( 'apd_textdomain_loaded', function() {
		 *     load_plugin_textdomain( 'all-purpose-directory', false, 'all-purpose-directory/languages' );
		 * } );
		 *
		 * @link https://make.wordpress.org/core/2016/07/06/i18n-improvements-in-4-6/
		 */

		/**
		 * Fires after the plugin text domain is loaded.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_textdomain_loaded' );
	}

	/**
	 * Initialize the module registry.
	 *
	 * Fires the apd_modules_init action allowing external modules to register.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init_modules(): void {
		$module_registry = \APD\Module\ModuleRegistry::get_instance();
		$module_registry->init();
	}

	/**
	 * Register post types.
	 *
	 * @return void
	 */
	public function register_post_types(): void {
		$post_type = new \APD\Listing\PostType();
		$post_type->register();
		$post_type->register_statuses();
	}

	/**
	 * Register taxonomies.
	 *
	 * @return void
	 */
	public function register_taxonomies(): void {
		// Register category taxonomy.
		$category_taxonomy = new \APD\Taxonomy\CategoryTaxonomy();
		$category_taxonomy->register();
		$category_taxonomy->init_admin();

		// Register tag taxonomy.
		$tag_taxonomy = new \APD\Taxonomy\TagTaxonomy();
		$tag_taxonomy->register();
	}

	/**
	 * Get the assets manager instance.
	 *
	 * @return Assets
	 */
	public function get_assets(): Assets {
		return $this->assets;
	}

	/**
	 * Get the plugin directory path.
	 *
	 * @param string $path Optional path to append.
	 * @return string
	 */
	public function get_plugin_dir( string $path = '' ): string {
		return APD_PLUGIN_DIR . ltrim( $path, '/' );
	}

	/**
	 * Get the plugin URL.
	 *
	 * @param string $path Optional path to append.
	 * @return string
	 */
	public function get_plugin_url( string $path = '' ): string {
		return APD_PLUGIN_URL . ltrim( $path, '/' );
	}

	/**
	 * Get the search query instance.
	 *
	 * @return \APD\Search\SearchQuery|null
	 */
	public function get_search_query(): ?\APD\Search\SearchQuery {
		return $this->search_query;
	}

	/**
	 * Get the template loader instance.
	 *
	 * @return TemplateLoader|null
	 */
	public function get_template_loader(): ?TemplateLoader {
		return $this->template_loader;
	}

	/**
	 * Register default search filters.
	 *
	 * @return void
	 */
	public function register_default_filters(): void {
		$registry = \APD\Search\FilterRegistry::get_instance();

		// Register keyword filter.
		$registry->register_filter( new \APD\Search\Filters\KeywordFilter() );

		// Register category filter.
		$registry->register_filter( new \APD\Search\Filters\CategoryFilter() );

		// Register tag filter.
		$registry->register_filter( new \APD\Search\Filters\TagFilter() );

		/**
		 * Fires after default filters are registered.
		 *
		 * Use this hook to register additional custom filters.
		 *
		 * @since 1.0.0
		 *
		 * @param \APD\Search\FilterRegistry $registry The filter registry instance.
		 */
		do_action( 'apd_register_filters', $registry );
	}

	/**
	 * Register plugin shortcodes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_shortcodes(): void {
		$manager = \APD\Shortcode\ShortcodeManager::get_instance();
		$manager->init();
	}

	/**
	 * AJAX handler for filtering listings.
	 *
	 * @return void
	 */
	public function ajax_filter_listings(): void {
		// Verify nonce (required for all AJAX requests).
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce = isset( $_REQUEST['_apd_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_apd_nonce'] ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'apd_filter_listings' ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'all-purpose-directory' ) ], 403 );
		}

		/**
		 * Fires before AJAX filtering starts.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_before_ajax_filter' );

		// Get paged parameter.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 1;

		// Run filtered query.
		$query = $this->search_query->get_filtered_listings(
			[
				'paged' => $paged,
			]
		);

		// Get current view mode.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$view = isset( $_REQUEST['apd_view'] ) ? sanitize_key( $_REQUEST['apd_view'] ) : 'grid';
		if ( ! in_array( $view, [ 'grid', 'list' ], true ) ) {
			$view = 'grid';
		}

		// Build HTML output.
		ob_start();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				// Load the appropriate card template.
				$template_name = $view === 'list' ? 'listing-card-list' : 'listing-card';

				\apd_get_template_part(
					$template_name,
					null,
					[
						'listing_id'   => get_the_ID(),
						'current_view' => $view,
					]
				);
			}
			wp_reset_postdata();
		} else {
			$renderer = new \APD\Search\FilterRenderer();
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $renderer->render_no_results();
		}
		$html = ob_get_clean();

		// Get active filters.
		$registry       = \APD\Search\FilterRegistry::get_instance();
		$active_filters = $registry->get_active_filters();
		$active_data    = [];

		foreach ( $active_filters as $name => $data ) {
			$active_data[ $name ] = [
				'label' => $data['filter']->getLabel(),
				'value' => $data['filter']->getDisplayValue( $data['value'] ),
			];
		}

		$response = [
			'html'           => $html,
			'found_posts'    => $query->found_posts,
			'max_pages'      => $query->max_num_pages,
			'current_page'   => $paged,
			'active_filters' => $active_data,
		];

		/**
		 * Filter the AJAX response data.
		 *
		 * @since 1.0.0
		 *
		 * @param array    $response The response data.
		 * @param WP_Query $query    The query object.
		 */
		$response = apply_filters( 'apd_ajax_filter_response', $response, $query );

		/**
		 * Fires after AJAX filtering completes.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_after_ajax_filter' );

		wp_send_json_success( $response );
	}

	/**
	 * AJAX handler for deleting a listing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_delete_listing(): void {
		// Verify nonce.
		if ( ! check_ajax_referer( \APD\Frontend\Dashboard\MyListings::NONCE_ACTION, '_apd_nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'all-purpose-directory' ) ], 403 );
		}

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'You must be logged in.', 'all-purpose-directory' ) ], 401 );
		}

		// Get listing ID.
		$listing_id = isset( $_POST['listing_id'] ) ? absint( $_POST['listing_id'] ) : 0;
		if ( $listing_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid listing ID.', 'all-purpose-directory' ) ], 400 );
		}

		// Get action type (trash or delete).
		$delete_type = isset( $_POST['delete_type'] ) && $_POST['delete_type'] === 'permanent' ? 'permanent' : 'trash';

		$my_listings = \APD\Frontend\Dashboard\MyListings::get_instance();

		if ( $delete_type === 'permanent' ) {
			$result = $my_listings->delete_listing( $listing_id );
		} else {
			$result = $my_listings->trash_listing( $listing_id );
		}

		if ( $result ) {
			wp_send_json_success(
				[
					'message'    => $delete_type === 'permanent'
						? __( 'Listing permanently deleted.', 'all-purpose-directory' )
						: __( 'Listing moved to trash.', 'all-purpose-directory' ),
					'listing_id' => $listing_id,
				]
			);
		} else {
			wp_send_json_error(
				[
					'message' => __( 'Failed to delete listing. You may not have permission.', 'all-purpose-directory' ),
				],
				403
			);
		}
	}

	/**
	 * AJAX handler for updating listing status.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_update_listing_status(): void {
		// Verify nonce.
		if ( ! check_ajax_referer( \APD\Frontend\Dashboard\MyListings::NONCE_ACTION, '_apd_nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'all-purpose-directory' ) ], 403 );
		}

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'You must be logged in.', 'all-purpose-directory' ) ], 401 );
		}

		// Get listing ID.
		$listing_id = isset( $_POST['listing_id'] ) ? absint( $_POST['listing_id'] ) : 0;
		if ( $listing_id <= 0 ) {
			wp_send_json_error( [ 'message' => __( 'Invalid listing ID.', 'all-purpose-directory' ) ], 400 );
		}

		// Get new status.
		$new_status     = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : '';
		$valid_statuses = [ 'publish', 'draft', 'pending', 'expired' ];
		if ( ! in_array( $new_status, $valid_statuses, true ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid status.', 'all-purpose-directory' ) ], 400 );
		}

		$my_listings = \APD\Frontend\Dashboard\MyListings::get_instance();
		$result      = $my_listings->update_listing_status( $listing_id, $new_status );

		if ( $result ) {
			$status_badge = $my_listings->get_status_badge( $new_status );

			wp_send_json_success(
				[
					'message'      => __( 'Listing status updated.', 'all-purpose-directory' ),
					'listing_id'   => $listing_id,
					'new_status'   => $new_status,
					'status_badge' => $status_badge,
				]
			);
		} else {
			wp_send_json_error(
				[
					'message' => __( 'Failed to update listing status. You may not have permission.', 'all-purpose-directory' ),
				],
				403
			);
		}
	}

	/**
	 * Render a basic listing card for AJAX output.
	 *
	 * @return void
	 */
	private function render_listing_card(): void {
		?>
		<article id="listing-<?php the_ID(); ?>" <?php post_class( 'apd-listing-card' ); ?>>
			<h2 class="apd-listing-card__title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h2>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="apd-listing-card__thumbnail">
					<a href="<?php the_permalink(); ?>">
						<?php the_post_thumbnail( 'medium' ); ?>
					</a>
				</div>
			<?php endif; ?>

			<div class="apd-listing-card__excerpt">
				<?php the_excerpt(); ?>
			</div>

			<?php
			$categories = \apd_get_listing_categories( get_the_ID() );
			if ( ! empty( $categories ) ) :
				?>
				<div class="apd-listing-card__categories">
					<?php
					foreach ( $categories as $category ) {
						printf(
							'<a href="%s" class="apd-listing-card__category">%s</a>',
							esc_url( get_term_link( $category ) ),
							esc_html( $category->name )
						);
					}
					?>
				</div>
			<?php endif; ?>
		</article>
		<?php
	}

	/**
	 * Register REST API endpoints.
	 *
	 * @since 1.0.0
	 *
	 * @param \APD\Api\RestController $controller REST controller instance.
	 * @return void
	 */
	public function register_rest_endpoints( \APD\Api\RestController $controller ): void {
		// Register Listings endpoint.
		$controller->register_endpoint(
			'listings',
			new \APD\Api\Endpoints\ListingsEndpoint( $controller )
		);

		// Register Taxonomies endpoint.
		$controller->register_endpoint(
			'taxonomies',
			new \APD\Api\Endpoints\TaxonomiesEndpoint( $controller )
		);

		// Register Favorites endpoint.
		$controller->register_endpoint(
			'favorites',
			new \APD\Api\Endpoints\FavoritesEndpoint( $controller )
		);

		// Register Reviews endpoint.
		$controller->register_endpoint(
			'reviews',
			new \APD\Api\Endpoints\ReviewsEndpoint( $controller )
		);

		// Register Inquiries endpoint.
		$controller->register_endpoint(
			'inquiries',
			new \APD\Api\Endpoints\InquiriesEndpoint( $controller )
		);
	}
}
