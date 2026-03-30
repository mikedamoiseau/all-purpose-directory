# Frontend Display Reference

This document covers the View System and Template System.

## View System

The View System (`src/Frontend/Display/`) manages how listings are displayed. Views encapsulate rendering logic and configuration for different display modes.

### Core Components

- `ViewInterface` - Contract for view implementations
- `AbstractView` - Base class with common functionality
- `GridView` - Grid layout with configurable columns
- `ListView` - Horizontal list layout with more details
- `ViewRegistry` - Singleton registry managing views

### ViewInterface Contract

```php
interface ViewInterface {
    public function getType(): string;
    public function getLabel(): string;
    public function getIcon(): string;
    public function getTemplate(): string;
    public function renderListing( int $listing_id, array $args = [] ): string;
    public function renderListings( \WP_Query|array $listings, array $args = [] ): string;
    public function getContainerClasses(): array;
    public function getContainerAttributes(): array;
    public function supports( string $feature ): bool;
    public function getConfig(): array;
    public function setConfig( array $config ): self;
    public function getConfigValue( string $key, mixed $default = null ): mixed;
    public function setConfigValue( string $key, mixed $value ): self;
}
```

### View Helper Functions

```php
apd_view_registry(): ViewRegistry                                      // Get registry instance
apd_register_view( ViewInterface $view ): bool                         // Register custom view
apd_unregister_view( string $type ): bool                              // Remove a view
apd_get_view( string $type ): ?ViewInterface                           // Get view instance
apd_has_view( string $type ): bool                                     // Check if view exists
apd_get_views(): array                                                 // Get all views
apd_get_view_options(): array                                          // Get type => label mapping
apd_get_default_view(): string                                         // Get default view type
apd_set_default_view( string $type ): void                             // Set default view
apd_create_view( string $type, array $config = [] ): ?ViewInterface    // Create configured view
apd_grid_view( array $config = [] ): GridView                          // Create GridView
apd_list_view( array $config = [] ): ListView                          // Create ListView
apd_render_grid( \WP_Query|array $listings, array $args = [] ): string // Render as grid
apd_render_list( \WP_Query|array $listings, array $args = [] ): string // Render as list
apd_render_listings( \WP_Query|array $listings, string $view_type = 'grid', array $args = [] ): string
```

### GridView Configuration

```php
$view = apd_grid_view( [
    'columns'        => 3,           // 2, 3, or 4 columns
    'show_image'     => true,
    'show_excerpt'   => true,
    'excerpt_length' => 15,          // Words
    'show_category'  => true,
    'show_badge'     => true,
    'show_price'     => true,
    'show_rating'    => true,
    'show_favorite'  => true,
    'show_view_details' => true,
    'image_size'     => 'medium',
    'card_hover'     => true,
] );
$view->setColumns( 4 );
```

### ListView Configuration

```php
$view = apd_list_view( [
    'show_image'     => true,
    'image_width'    => 280,         // 100-400 px
    'show_excerpt'   => true,
    'excerpt_length' => 30,          // More than grid
    'show_category'  => true,
    'show_tags'      => true,
    'max_tags'       => 5,           // 0-20
    'show_date'      => true,
    'show_price'     => true,
    'show_rating'    => true,
    'show_favorite'  => true,
    'show_view_details' => true,
    'image_size'     => 'medium',
] );
$view->setImageWidth( 300 )->setMaxTags( 8 );
```

### Supported Features

| View | Features |
|------|----------|
| GridView | columns, image, excerpt, badge, hover_effect |
| ListView | image, excerpt, tags, date, sidebar |

### Rendering Listings

```php
// Simple rendering
echo apd_render_grid( $query );
echo apd_render_list( $query, [ 'show_date' => false ] );

// With view type selection
echo apd_render_listings( $query, 'grid', [ 'columns' => 4 ] );

// Using view objects
$view = apd_create_view( 'grid', [ 'columns' => 2 ] );
echo $view->renderListings( $query );
echo $view->renderListing( $post_id );
```

### Creating Custom Views

```php
use APD\Contracts\ViewInterface;
use APD\Frontend\Display\AbstractView;

class MapView extends AbstractView {
    protected string $type = 'map';
    protected string $template = 'listing-map';
    protected array $supports = [ 'image', 'location', 'cluster' ];
    protected array $defaults = [
        'zoom'         => 12,
        'cluster'      => true,
        'show_popup'   => true,
    ];

    public function getLabel(): string {
        return __( 'Map', 'damdir-directory' );
    }

    public function getIcon(): string {
        return 'dashicons-location';
    }
}

// Register the view
apd_register_view( new MapView() );
```

### View Hooks

- Actions: `apd_views_init`, `apd_view_registered`, `apd_view_unregistered`
- Filters: `apd_{type}_view_container_classes`, `apd_{type}_view_container_attributes`

---

## Template System

The Template class (`src/Core/Template.php`) provides a WooCommerce-style template loading system with theme override support.

### Template Lookup Order

1. Child theme: `{child-theme}/damdir-directory/{template_name}`
2. Parent theme: `{parent-theme}/damdir-directory/{template_name}`
3. Plugin: `{plugin}/templates/{template_name}`

### Template Functions

```php
apd_template(): Template                                        // Get Template instance
apd_locate_template( string $template_name ): string|false      // Find template path
apd_get_template( string $name, array $args = [], bool $require_once = false ): void
apd_get_template_html( string $template_name, array $args = [] ): string
apd_get_template_part( string $slug, ?string $name = null, array $args = [] ): void
apd_get_template_part_html( string $slug, ?string $name = null, array $args = [] ): string
apd_template_exists( string $template_name ): bool
apd_is_template_overridden( string $template_name ): bool
apd_get_plugin_template_path(): string                          // Returns plugin templates/ path
apd_get_theme_template_dir(): string                            // Returns 'damdir-directory/'
```

### Template Variables

When using `apd_get_template()`, pass variables in the `$args` array. They are extracted and available both as individual variables and in the `$args` array:

```php
apd_get_template( 'listing-card.php', [
    'listing_id' => 123,
    'show_image' => true,
] );
// In template: use $listing_id or $args['listing_id']
```

### Template Hooks

- Filters: `apd_locate_template`, `apd_get_template_part`
- Actions: `apd_before_get_template`, `apd_after_get_template`
