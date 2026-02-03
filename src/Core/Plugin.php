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
        // Taxonomy registration will be implemented in Taxonomy classes.
        // This is a placeholder for the hook connection.
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
}
