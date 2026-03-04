# Shortcode System Reference

The Shortcode System (`src/Shortcode/`) provides WordPress shortcodes for displaying listings, search forms, categories, and user features.

## Core Components

- `ShortcodeManager` - Singleton managing shortcode registration
- `AbstractShortcode` - Base class with attribute parsing and sanitization
- Individual shortcode classes for each `[apd_*]` tag

## Available Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[apd_listings]` | Display listings in grid/list view |
| `[apd_search_form]` | Display search/filter form |
| `[apd_categories]` | Display category grid/list |
| `[apd_login_form]` | Display login form |
| `[apd_register_form]` | Display registration form |
| `[apd_submission_form]` | Listing submission (placeholder) |
| `[apd_dashboard]` | User dashboard (placeholder) |
| `[apd_favorites]` | User favorites (placeholder) |

---

## Listings Shortcode

```php
[apd_listings
    view="grid"           // grid, list
    columns="3"           // 2, 3, 4 (grid only)
    count="12"            // posts per page
    category="restaurants" // category slug(s)
    tag="featured"        // tag slug(s)
    orderby="date"        // date, title, modified, rand, views
    order="DESC"          // ASC, DESC
    ids="1,2,3"          // specific IDs
    exclude="4,5"        // exclude IDs
    author="john"        // author ID or username
    show_image="true"
    show_excerpt="true"
    show_pagination="true"
]
```

---

## Search Form Shortcode

```php
[apd_search_form
    layout="horizontal"   // horizontal, vertical, inline
    show_keyword="true"
    show_category="true"
    show_tag="false"
    show_submit="true"
    submit_text="Search"
    action=""            // defaults to listings archive
    show_active="false"  // show active filter chips
]
```

---

## Categories Shortcode

```php
[apd_categories
    layout="grid"        // grid, list
    columns="4"          // 2-6 (grid only)
    count="0"            // 0 = all
    parent=""            // parent ID, 0 for top-level only
    hide_empty="true"
    orderby="name"       // name, count, id, slug
    show_count="true"
    show_icon="true"
    show_description="false"
]
```

---

## Shortcode Helper Functions

```php
apd_shortcode_manager(): ShortcodeManager    // Get manager instance
apd_get_shortcode( string $tag ): ?AbstractShortcode
apd_has_shortcode( string $tag ): bool
apd_get_shortcodes(): array                  // Get all registered
apd_get_shortcode_docs(): array              // Get documentation
```

---

## Creating Custom Shortcodes

```php
use APD\Shortcode\AbstractShortcode;

class MyShortcode extends AbstractShortcode {
    protected string $tag = 'apd_my_shortcode';
    protected string $description = 'My custom shortcode.';

    protected array $defaults = [
        'title' => '',
        'count' => 5,
    ];

    protected array $attribute_docs = [
        'title' => [ 'type' => 'string', 'description' => 'The title.' ],
        'count' => [ 'type' => 'integer', 'description' => 'Number of items.' ],
    ];

    protected function output( array $atts, ?string $content ): string {
        return sprintf( '<div>%s: %d items</div>', $atts['title'], $atts['count'] );
    }
}

// Register on init
add_action( 'apd_shortcodes_init', function( $manager ) {
    $manager->register( new MyShortcode() );
} );
```

### Attribute Types

`string`, `integer`, `boolean`, `slug`, `ids`, `id`, `array`

---

## Shortcode Hooks

- Actions: `apd_shortcodes_init`, `apd_shortcode_registered`, `apd_shortcode_unregistered`, `apd_before_shortcode_{tag}`, `apd_after_shortcode_{tag}`
- Filters: `apd_shortcode_{tag}_atts`, `apd_shortcode_{tag}_output`, `apd_listings_shortcode_query_args`, `apd_listings_shortcode_pagination_args`, `apd_categories_shortcode_query_args`, `apd_search_form_shortcode_args`, `apd_login_form_shortcode_args`, `apd_register_form_errors`
