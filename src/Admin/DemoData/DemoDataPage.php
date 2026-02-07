<?php
/**
 * Demo Data Admin Page Class.
 *
 * Provides admin interface for generating and deleting demo data.
 *
 * @package APD\Admin\DemoData
 * @since   1.0.0
 */

declare(strict_types=1);

namespace APD\Admin\DemoData;

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DemoDataPage
 *
 * Admin page for demo data management.
 *
 * @since 1.0.0
 */
final class DemoDataPage {

	/**
	 * Admin page slug.
	 */
	public const PAGE_SLUG = 'apd-demo-data';

	/**
	 * Parent menu slug.
	 */
	public const PARENT_MENU = 'edit.php?post_type=apd_listing';

	/**
	 * Capability required to manage demo data.
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * Nonce action for generate.
	 */
	public const NONCE_GENERATE = 'apd_generate_demo';

	/**
	 * Nonce action for delete.
	 */
	public const NONCE_DELETE = 'apd_delete_demo';

	/**
	 * Singleton instance.
	 *
	 * @var DemoDataPage|null
	 */
	private static ?DemoDataPage $instance = null;

	/**
	 * Default quantities for generation.
	 *
	 * @var array<string, int>
	 */
	private array $defaults = [
		'users'    => 5,
		'tags'     => 10,
		'listings' => 25,
	];

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return DemoDataPage
	 */
	public static function get_instance(): DemoDataPage {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {}

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
	 * Initialize the demo data page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		// Only run in admin context.
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// AJAX handlers.
		add_action( 'wp_ajax_apd_generate_demo', [ $this, 'ajax_generate' ] );
		add_action( 'wp_ajax_apd_delete_demo', [ $this, 'ajax_delete' ] );

		/**
		 * Fires after demo data page is initialized.
		 *
		 * @since 1.0.0
		 *
		 * @param DemoDataPage $page The demo data page instance.
		 */
		do_action( 'apd_demo_data_init', $this );

		// Initialize demo data provider registry so modules can register providers.
		DemoDataProviderRegistry::get_instance()->init();
	}

	/**
	 * Register the admin menu page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_admin_page(): void {
		add_submenu_page(
			self::PARENT_MENU,
			__( 'Demo Data', 'all-purpose-directory' ),
			__( 'Demo Data', 'all-purpose-directory' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// Only load on our page.
		if ( 'apd_listing_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'apd-admin-demo-data',
			APD_PLUGIN_URL . 'assets/css/admin-demo-data.css',
			[],
			APD_VERSION
		);

		wp_enqueue_script(
			'apd-admin-demo-data',
			APD_PLUGIN_URL . 'assets/js/admin-demo-data.js',
			[ 'jquery' ],
			APD_VERSION,
			true
		);

		// Build module labels map from registered providers.
		$module_labels = [];
		$providers     = DemoDataProviderRegistry::get_instance()->get_all();

		foreach ( $providers as $slug => $provider ) {
			$module_labels[ 'module_' . $slug ] = $provider->get_name();
		}

		wp_localize_script(
			'apd-admin-demo-data',
			'apdDemoData',
			[
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'generateNonce' => wp_create_nonce( self::NONCE_GENERATE ),
				'deleteNonce'   => wp_create_nonce( self::NONCE_DELETE ),
				'moduleLabels'  => $module_labels,
				'strings'       => [
					'generating'        => __( 'Generating demo data...', 'all-purpose-directory' ),
					'deleting'          => __( 'Deleting demo data...', 'all-purpose-directory' ),
					'confirmDelete'     => __( 'Are you sure you want to delete ALL demo data? This cannot be undone.', 'all-purpose-directory' ),
					'success'           => __( 'Operation completed successfully!', 'all-purpose-directory' ),
					'error'             => __( 'An error occurred. Please try again.', 'all-purpose-directory' ),
					'generatingUsers'   => __( 'Creating users...', 'all-purpose-directory' ),
					'generatingCats'    => __( 'Creating categories...', 'all-purpose-directory' ),
					'generatingTags'    => __( 'Creating tags...', 'all-purpose-directory' ),
					'generatingList'    => __( 'Creating listings...', 'all-purpose-directory' ),
					'generatingReviews' => __( 'Creating reviews...', 'all-purpose-directory' ),
					'generatingInq'     => __( 'Creating inquiries...', 'all-purpose-directory' ),
					'generatingFavs'    => __( 'Creating favorites...', 'all-purpose-directory' ),
				],
			]
		);
	}

	/**
	 * Render the admin page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_page(): void {
		// Check user capabilities.
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'all-purpose-directory' ) );
		}

		$tracker           = DemoDataTracker::get_instance();
		$counts            = $tracker->count_demo_data();
		$total             = array_sum( $counts );
		$provider_registry = DemoDataProviderRegistry::get_instance();
		$providers         = $provider_registry->get_all();
		$module_counts     = [];

		foreach ( $providers as $slug => $provider ) {
			$provider_counts = $provider->count( $tracker );

			foreach ( $provider_counts as $type => $count ) {
				$key                   = 'module_' . $slug . '_' . $type;
				$module_counts[ $key ] = $count;
				$total                += $count;
			}
		}

		/**
		 * Filter the default demo data counts.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, int> $defaults Default quantities.
		 */
		$defaults = apply_filters( 'apd_demo_default_counts', $this->defaults );

		?>
		<div class="wrap apd-demo-data-wrap">
			<h1><?php esc_html_e( 'Demo Data Generator', 'all-purpose-directory' ); ?></h1>

			<p class="description">
				<?php esc_html_e( 'Generate sample data to test your directory. All demo data can be deleted later without affecting your real content.', 'all-purpose-directory' ); ?>
			</p>

			<!-- Current Status -->
			<div class="apd-demo-section apd-demo-status">
				<h2><?php esc_html_e( 'Current Demo Data', 'all-purpose-directory' ); ?></h2>

				<table class="apd-demo-stats">
					<tbody>
						<tr>
							<td><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e( 'Users', 'all-purpose-directory' ); ?></td>
							<td class="apd-stat-count" data-type="users"><?php echo esc_html( number_format_i18n( $counts['users'] ) ); ?></td>
						</tr>
						<tr>
							<td><span class="dashicons dashicons-category"></span> <?php esc_html_e( 'Categories', 'all-purpose-directory' ); ?></td>
							<td class="apd-stat-count" data-type="categories"><?php echo esc_html( number_format_i18n( $counts['categories'] ) ); ?></td>
						</tr>
						<tr>
							<td><span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'Tags', 'all-purpose-directory' ); ?></td>
							<td class="apd-stat-count" data-type="tags"><?php echo esc_html( number_format_i18n( $counts['tags'] ) ); ?></td>
						</tr>
						<tr>
							<td><span class="dashicons dashicons-location"></span> <?php esc_html_e( 'Listings', 'all-purpose-directory' ); ?></td>
							<td class="apd-stat-count" data-type="listings"><?php echo esc_html( number_format_i18n( $counts['listings'] ) ); ?></td>
						</tr>
						<tr>
							<td><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Reviews', 'all-purpose-directory' ); ?></td>
							<td class="apd-stat-count" data-type="reviews"><?php echo esc_html( number_format_i18n( $counts['reviews'] ) ); ?></td>
						</tr>
						<tr>
							<td><span class="dashicons dashicons-email"></span> <?php esc_html_e( 'Inquiries', 'all-purpose-directory' ); ?></td>
							<td class="apd-stat-count" data-type="inquiries"><?php echo esc_html( number_format_i18n( $counts['inquiries'] ) ); ?></td>
						</tr>
						<?php foreach ( $providers as $slug => $provider ) : ?>
							<?php
							$provider_counts = $provider->count( $tracker );

							foreach ( $provider_counts as $type => $count ) :
								$data_type = 'module_' . $slug . '_' . $type;
								?>
								<tr>
									<td>
										<span class="dashicons <?php echo esc_attr( $provider->get_icon() ); ?>"></span>
										<?php echo esc_html( $provider->get_name() ); ?> &mdash; <?php echo esc_html( ucfirst( $type ) ); ?>
									</td>
									<td class="apd-stat-count" data-type="<?php echo esc_attr( $data_type ); ?>">
										<?php echo esc_html( number_format_i18n( $count ) ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</tbody>
					<tfoot>
						<tr>
							<th><?php esc_html_e( 'Total Items', 'all-purpose-directory' ); ?></th>
							<th class="apd-stat-total"><?php echo esc_html( number_format_i18n( $total ) ); ?></th>
						</tr>
					</tfoot>
				</table>
			</div>

			<!-- Generate Form -->
			<div class="apd-demo-section apd-demo-generate">
				<h2><?php esc_html_e( 'Generate Demo Data', 'all-purpose-directory' ); ?></h2>

				<form id="apd-generate-form" class="apd-demo-form">
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e( 'Select data to generate', 'all-purpose-directory' ); ?></legend>

						<div class="apd-form-row">
							<label class="apd-checkbox-label">
								<input type="checkbox" name="generate_users" value="1" checked>
								<?php esc_html_e( 'Users', 'all-purpose-directory' ); ?>
							</label>
							<input type="number" name="users_count" value="<?php echo esc_attr( (string) $defaults['users'] ); ?>" min="1" max="20" class="small-text">
							<span class="description"><?php esc_html_e( 'demo users (max 20)', 'all-purpose-directory' ); ?></span>
						</div>

						<div class="apd-form-row">
							<label class="apd-checkbox-label">
								<input type="checkbox" name="generate_categories" value="1" checked>
								<?php esc_html_e( 'Categories', 'all-purpose-directory' ); ?>
							</label>
							<span class="description"><?php esc_html_e( '21 categories (6 parent + 15 child) with icons and colors', 'all-purpose-directory' ); ?></span>
						</div>

						<div class="apd-form-row">
							<label class="apd-checkbox-label">
								<input type="checkbox" name="generate_tags" value="1" checked>
								<?php esc_html_e( 'Tags', 'all-purpose-directory' ); ?>
							</label>
							<input type="number" name="tags_count" value="<?php echo esc_attr( (string) $defaults['tags'] ); ?>" min="1" max="10" class="small-text">
							<span class="description"><?php esc_html_e( 'tags (max 10)', 'all-purpose-directory' ); ?></span>
						</div>

						<div class="apd-form-row">
							<label class="apd-checkbox-label">
								<input type="checkbox" name="generate_listings" value="1" checked>
								<?php esc_html_e( 'Listings', 'all-purpose-directory' ); ?>
							</label>
							<input type="number" name="listings_count" value="<?php echo esc_attr( (string) $defaults['listings'] ); ?>" min="1" max="100" class="small-text">
							<span class="description"><?php esc_html_e( 'listings (max 100)', 'all-purpose-directory' ); ?></span>
						</div>

						<div class="apd-form-row">
							<label class="apd-checkbox-label">
								<input type="checkbox" name="generate_reviews" value="1" checked>
								<?php esc_html_e( 'Reviews', 'all-purpose-directory' ); ?>
							</label>
							<span class="description"><?php esc_html_e( '2-4 reviews per listing', 'all-purpose-directory' ); ?></span>
						</div>

						<div class="apd-form-row">
							<label class="apd-checkbox-label">
								<input type="checkbox" name="generate_inquiries" value="1" checked>
								<?php esc_html_e( 'Inquiries', 'all-purpose-directory' ); ?>
							</label>
							<span class="description"><?php esc_html_e( '0-2 inquiries per listing (random)', 'all-purpose-directory' ); ?></span>
						</div>

						<div class="apd-form-row">
							<label class="apd-checkbox-label">
								<input type="checkbox" name="generate_favorites" value="1" checked>
								<?php esc_html_e( 'Favorites', 'all-purpose-directory' ); ?>
							</label>
							<span class="description"><?php esc_html_e( '1-5 favorites per user', 'all-purpose-directory' ); ?></span>
						</div>

						<?php if ( ! empty( $providers ) ) : ?>
							<div class="apd-form-divider"><?php esc_html_e( 'Module Data', 'all-purpose-directory' ); ?></div>

							<?php foreach ( $providers as $slug => $provider ) : ?>
								<div class="apd-form-row">
									<label class="apd-checkbox-label">
										<input type="checkbox" name="generate_module_<?php echo esc_attr( $slug ); ?>" value="1" checked>
										<?php echo esc_html( $provider->get_name() ); ?>
									</label>
									<?php foreach ( $provider->get_form_fields() as $field ) : ?>
										<input
											type="<?php echo esc_attr( $field['type'] ); ?>"
											name="module_<?php echo esc_attr( $slug ); ?>_<?php echo esc_attr( $field['name'] ); ?>"
											value="<?php echo esc_attr( (string) $field['default'] ); ?>"
											min="<?php echo esc_attr( (string) ( $field['min'] ?? 1 ) ); ?>"
											max="<?php echo esc_attr( (string) ( $field['max'] ?? 100 ) ); ?>"
											class="small-text"
										>
									<?php endforeach; ?>
									<span class="description"><?php echo esc_html( $provider->get_description() ); ?></span>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</fieldset>

					<div class="apd-form-actions">
						<button type="submit" class="button button-primary button-large" id="apd-generate-btn">
							<span class="dashicons dashicons-database-add"></span>
							<?php esc_html_e( 'Generate Demo Data', 'all-purpose-directory' ); ?>
						</button>
					</div>
				</form>

				<!-- Progress Indicator -->
				<div id="apd-progress" class="apd-progress" style="display: none;">
					<div class="apd-progress-bar">
						<div class="apd-progress-bar-fill"></div>
					</div>
					<p class="apd-progress-text"></p>
				</div>

				<!-- Results -->
				<div id="apd-results" class="apd-results" style="display: none;"></div>
			</div>

			<!-- Delete Section -->
			<div class="apd-demo-section apd-demo-delete">
				<h2><?php esc_html_e( 'Delete Demo Data', 'all-purpose-directory' ); ?></h2>

				<?php if ( $total > 0 ) : ?>
					<div class="apd-warning">
						<span class="dashicons dashicons-warning"></span>
						<p>
							<?php
							printf(
								/* translators: %s: Number of demo data items */
								esc_html__( 'You have %s demo data items. Deleting will permanently remove all demo users, categories, tags, listings, reviews, inquiries, and favorites.', 'all-purpose-directory' ),
								'<strong>' . esc_html( number_format_i18n( $total ) ) . '</strong>'
							);
							?>
						</p>
					</div>

					<form id="apd-delete-form" class="apd-demo-form">
						<button type="submit" class="button button-link-delete button-large" id="apd-delete-btn">
							<span class="dashicons dashicons-trash"></span>
							<?php esc_html_e( 'Delete All Demo Data', 'all-purpose-directory' ); ?>
						</button>
					</form>
				<?php else : ?>
					<p class="apd-no-data">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'No demo data found. Your directory contains only real content.', 'all-purpose-directory' ); ?>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for generating demo data.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_generate(): void {
		// Verify nonce.
		if ( ! check_ajax_referer( self::NONCE_GENERATE, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'all-purpose-directory' ) ], 403 );
		}

		// Check capability.
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'all-purpose-directory' ) ], 403 );
		}

		/**
		 * Fires before demo data generation begins.
		 *
		 * @since 1.0.0
		 */
		do_action( 'apd_before_generate_demo_data' );

		$generator = DemoDataGenerator::get_instance();
		$results   = [];

		// Parse options.
		$generate_users      = ! empty( $_POST['generate_users'] );
		$generate_categories = ! empty( $_POST['generate_categories'] );
		$generate_tags       = ! empty( $_POST['generate_tags'] );
		$generate_listings   = ! empty( $_POST['generate_listings'] );
		$generate_reviews    = ! empty( $_POST['generate_reviews'] );
		$generate_inquiries  = ! empty( $_POST['generate_inquiries'] );
		$generate_favorites  = ! empty( $_POST['generate_favorites'] );

		$users_count    = isset( $_POST['users_count'] ) ? absint( $_POST['users_count'] ) : 5;
		$tags_count     = isset( $_POST['tags_count'] ) ? absint( $_POST['tags_count'] ) : 10;
		$listings_count = isset( $_POST['listings_count'] ) ? absint( $_POST['listings_count'] ) : 25;

		// Enforce limits.
		$users_count    = min( $users_count, 20 );
		$tags_count     = min( $tags_count, 10 );
		$listings_count = min( $listings_count, 100 );

		// Track created IDs for dependent operations.
		$user_ids    = [];
		$listing_ids = [];

		// Generate users.
		if ( $generate_users ) {
			$user_ids         = $generator->generate_users( $users_count );
			$results['users'] = count( $user_ids );
		}

		// Generate categories.
		if ( $generate_categories ) {
			$category_ids          = $generator->generate_categories();
			$results['categories'] = count( $category_ids );
		}

		// Generate tags.
		if ( $generate_tags ) {
			$tag_ids         = $generator->generate_tags( $tags_count );
			$results['tags'] = count( $tag_ids );
		}

		// Generate listings.
		if ( $generate_listings ) {
			$listing_ids         = $generator->generate_listings( $listings_count );
			$results['listings'] = count( $listing_ids );
		}

		// Generate reviews (requires listings).
		if ( $generate_reviews && ! empty( $listing_ids ) ) {
			$review_ids         = $generator->generate_reviews( $listing_ids, $user_ids );
			$results['reviews'] = count( $review_ids );
		}

		// Generate inquiries (requires listings).
		if ( $generate_inquiries && ! empty( $listing_ids ) ) {
			$inquiry_ids          = $generator->generate_inquiries( $listing_ids );
			$results['inquiries'] = count( $inquiry_ids );
		}

		// Generate favorites (requires listings and users).
		if ( $generate_favorites && ! empty( $listing_ids ) && ! empty( $user_ids ) ) {
			$results['favorites'] = $generator->generate_favorites( $listing_ids, $user_ids );
		}

		// Generate module provider data.
		$context = [
			'user_ids'     => $user_ids,
			'listing_ids'  => $listing_ids,
			'category_ids' => isset( $category_ids ) ? $category_ids : [],
			'tag_ids'      => isset( $tag_ids ) ? $tag_ids : [],
			'options'      => [],
		];

		$tracker            = DemoDataTracker::get_instance();
		$provider_registry  = DemoDataProviderRegistry::get_instance();
		$module_providers   = $provider_registry->get_all();

		foreach ( $module_providers as $slug => $provider ) {
			if ( empty( $_POST[ 'generate_module_' . $slug ] ) ) {
				continue;
			}

			// Extract provider-specific options from POST fields.
			$provider_options = [];

			foreach ( $provider->get_form_fields() as $field ) {
				$post_key = 'module_' . $slug . '_' . $field['name'];

				if ( ! isset( $_POST[ $post_key ] ) ) {
					continue;
				}

				if ( $field['type'] === 'number' ) {
					$provider_options[ $field['name'] ] = absint( $_POST[ $post_key ] );
				} else {
					$provider_options[ $field['name'] ] = sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) );
				}
			}

			$context['options']  = $provider_options;
			$provider_results    = $provider->generate( $context, $tracker );

			foreach ( $provider_results as $type => $count ) {
				$results[ 'module_' . $slug . '_' . $type ] = $count;
			}
		}

		// Get updated counts.
		$updated_counts = $tracker->count_demo_data();

		// Merge module counts into updated counts.
		foreach ( $module_providers as $slug => $provider ) {
			$provider_counts = $provider->count( $tracker );

			foreach ( $provider_counts as $type => $count ) {
				$updated_counts[ 'module_' . $slug . '_' . $type ] = $count;
			}
		}

		/**
		 * Fires after demo data generation completes.
		 *
		 * @since 1.0.0
		 *
		 * @param array $results Number of items created by type.
		 */
		do_action( 'apd_after_generate_demo_data', $results );

		wp_send_json_success(
			[
				'message' => __( 'Demo data generated successfully!', 'all-purpose-directory' ),
				'created' => $results,
				'counts'  => $updated_counts,
			]
		);
	}

	/**
	 * AJAX handler for deleting demo data.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_delete(): void {
		// Verify nonce.
		if ( ! check_ajax_referer( self::NONCE_DELETE, 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token.', 'all-purpose-directory' ) ], 403 );
		}

		// Check capability.
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'all-purpose-directory' ) ], 403 );
		}

		$tracker = DemoDataTracker::get_instance();
		$deleted = $tracker->delete_all();

		// Get updated counts (should all be 0).
		$updated_counts = $tracker->count_demo_data();

		wp_send_json_success(
			[
				'message' => __( 'All demo data has been deleted.', 'all-purpose-directory' ),
				'deleted' => $deleted,
				'counts'  => $updated_counts,
			]
		);
	}

	/**
	 * Get the default generation quantities.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, int>
	 */
	public function get_defaults(): array {
		return $this->defaults;
	}

	/**
	 * Reset singleton instance for testing.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function reset_instance(): void {
		self::$instance = null;
	}
}
