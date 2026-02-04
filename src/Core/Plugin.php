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

        // Register AJAX handlers.
        add_action( 'wp_ajax_apd_filter_listings', [ $this, 'ajax_filter_listings' ] );
        add_action( 'wp_ajax_nopriv_apd_filter_listings', [ $this, 'ajax_filter_listings' ] );

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
}
