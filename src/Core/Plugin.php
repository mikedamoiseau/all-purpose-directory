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
	 * Reset singleton instance (for testing).
	 *
	 * @return void
	 */
	public static function reset_instance(): void {
		self::$instance = null;
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

		// Register built-in field types (priority 2, after modules).
		add_action( 'init', [ $this, 'register_field_types' ], 2 );

		// Load external fields via apd_listing_fields filter (priority 3, after field types).
		add_action( 'init', [ \APD\Fields\FieldRegistry::get_instance(), 'load_external_fields' ], 3 );

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
		$ajax_handler = new \APD\Api\AjaxHandler( $this->search_query );
		$ajax_handler->init();

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

		// Fire apd_listing_status_changed when listing post status transitions.
		add_action( 'transition_post_status', [ $this, 'handle_listing_status_transition' ], 10, 3 );

		// Register cron event handlers.
		add_action( 'apd_check_expired_listings', [ $this, 'cron_check_expired_listings' ] );
		add_action( 'apd_cleanup_transients', [ $this, 'cron_cleanup_transients' ] );

		/**
		 * Fires after plugin hooks are initialized.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_loaded' );
	}

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
	 * Register built-in field types.
	 *
	 * Registers all field type handlers that ship with the plugin.
	 * These handlers define how each field type is rendered, sanitized,
	 * and validated.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_field_types(): void {
		$registry = \APD\Fields\FieldRegistry::get_instance();

		$field_types = [
			new \APD\Fields\Types\TextField(),
			new \APD\Fields\Types\TextareaField(),
			new \APD\Fields\Types\EmailField(),
			new \APD\Fields\Types\UrlField(),
			new \APD\Fields\Types\PhoneField(),
			new \APD\Fields\Types\NumberField(),
			new \APD\Fields\Types\DecimalField(),
			new \APD\Fields\Types\CurrencyField(),
			new \APD\Fields\Types\DateField(),
			new \APD\Fields\Types\TimeField(),
			new \APD\Fields\Types\DateTimeField(),
			new \APD\Fields\Types\DateRangeField(),
			new \APD\Fields\Types\SelectField(),
			new \APD\Fields\Types\MultiSelectField(),
			new \APD\Fields\Types\RadioField(),
			new \APD\Fields\Types\CheckboxField(),
			new \APD\Fields\Types\CheckboxGroupField(),
			new \APD\Fields\Types\SwitchField(),
			new \APD\Fields\Types\ColorField(),
			new \APD\Fields\Types\FileField(),
			new \APD\Fields\Types\ImageField(),
			new \APD\Fields\Types\GalleryField(),
			new \APD\Fields\Types\HiddenField(),
			new \APD\Fields\Types\RichTextField(),
		];

		foreach ( $field_types as $field_type ) {
			$registry->register_field_type( $field_type );
		}
	}

	/**
	 * Handle listing post status transitions.
	 *
	 * Fires the apd_listing_status_changed action when an apd_listing
	 * post transitions between statuses. This bridges the WordPress
	 * transition_post_status hook with the plugin's custom action.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 * @return void
	 */
	public function handle_listing_status_transition( string $new_status, string $old_status, \WP_Post $post ): void {
		if ( $post->post_type !== \APD\Listing\PostType::POST_TYPE ) {
			return;
		}

		if ( $new_status === $old_status ) {
			return;
		}

		/**
		 * Fires when a listing's status changes.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $listing_id Listing post ID.
		 * @param string $new_status New post status.
		 * @param string $old_status Previous post status.
		 */
		do_action( 'apd_listing_status_changed', $post->ID, $new_status, $old_status );
	}

	/**
	 * Cron handler: check for expired listings.
	 *
	 * Queries published listings that have passed their expiration date
	 * and transitions them to 'expired' status. Also sends expiring-soon
	 * notifications for listings expiring within 7 days.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function cron_check_expired_listings(): void {
		$expiration_days = (int) \apd_get_setting( 'expiration_days', 0 );

		// 0 means listings never expire.
		if ( $expiration_days <= 0 ) {
			return;
		}

		$now             = current_time( 'mysql' );
		$expiration_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$expiration_days} days", strtotime( $now ) ) );
		$warning_date    = gmdate( 'Y-m-d H:i:s', strtotime( '-' . ( $expiration_days - 7 ) . ' days', strtotime( $now ) ) );

		// Find published listings that have expired (published before the expiration cutoff).
		$expired_listings = get_posts(
			[
				'post_type'      => \APD\Listing\PostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'date_query'     => [
					[
						'before' => $expiration_date,
					],
				],
				'fields'         => 'ids',
			]
		);

		foreach ( $expired_listings as $listing_id ) {
			wp_update_post(
				[
					'ID'          => $listing_id,
					'post_status' => 'expired',
				]
			);
			// The transition_post_status hook will fire apd_listing_status_changed,
			// which triggers the expired email notification via EmailManager.
		}

		// Find published listings expiring within 7 days (for warning emails).
		if ( function_exists( '\apd_email_manager' ) ) {
			$email_manager = \apd_email_manager();

			if ( $email_manager->is_notification_enabled( 'listing_expiring' ) ) {
				$expiring_soon = get_posts(
					[
						'post_type'      => \APD\Listing\PostType::POST_TYPE,
						'post_status'    => 'publish',
						'posts_per_page' => 50,
						'date_query'     => [
							[
								'after'  => $expiration_date,
								'before' => $warning_date,
							],
						],
						'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							[
								'key'     => '_apd_expiring_notified',
								'compare' => 'NOT EXISTS',
							],
						],
						'fields'         => 'ids',
					]
				);

				foreach ( $expiring_soon as $listing_id ) {
					$post_date  = get_post_field( 'post_date', $listing_id );
					$expires_at = strtotime( "+{$expiration_days} days", strtotime( $post_date ) );
					$days_left  = max( 1, (int) ceil( ( $expires_at - time() ) / DAY_IN_SECONDS ) );

					$email_manager->send_listing_expiring( $listing_id, $days_left );

					// Mark as notified so we don't send duplicate warnings.
					update_post_meta( $listing_id, '_apd_expiring_notified', $now );
				}
			}
		}
	}

	/**
	 * Cron handler: clean up expired transients.
	 *
	 * WordPress normally cleans up expired transients on its own,
	 * but this ensures APD-prefixed transients are cleaned promptly.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function cron_cleanup_transients(): void {
		global $wpdb;

		// Delete expired APD transients. WordPress stores transient expiration
		// in a separate _transient_timeout_ option. If the timeout has passed,
		// both the transient and timeout option can be cleaned up.
		$time = time();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$expired = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
				$wpdb->esc_like( '_transient_timeout_apd_cache_' ) . '%',
				$time
			)
		);

		foreach ( $expired as $timeout_option ) {
			// Extract the transient name from the timeout option name.
			$transient_name = str_replace( '_transient_timeout_', '', $timeout_option );
			delete_transient( $transient_name );
		}
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

		// Register listing type taxonomy (hidden, for module association).
		$listing_type_taxonomy = new \APD\Taxonomy\ListingTypeTaxonomy();
		$listing_type_taxonomy->register();
		$listing_type_taxonomy->init();
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
