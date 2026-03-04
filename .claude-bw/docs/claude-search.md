# Search & Filter System Reference

This document covers the Filter Registry, Search Query, and available filter types.

## Filter Registry

The Filter Registry (`src/Search/FilterRegistry.php`) manages search filters. Filters modify WP_Query to filter listings by criteria.

### Filter Configuration Structure

```php
[
    'name'           => 'filter_name',    // Unique identifier
    'type'           => 'select',         // select, checkbox, range, date_range, text
    'label'          => 'Filter Label',   // Display label
    'source'         => 'custom',         // taxonomy, field, custom
    'source_key'     => '',               // Taxonomy name or field name
    'options'        => [],               // Static options
    'multiple'       => false,            // Allow multiple selections
    'empty_option'   => '',               // Empty option text
    'query_callback' => null,             // Custom query callback
    'priority'       => 10,               // Display order
    'active'         => true,             // Whether filter is enabled
    'class'          => '',               // Additional CSS classes
    'attributes'     => [],               // Additional HTML attributes
]
```

### Filter Registry Functions

```php
apd_filter_registry(): FilterRegistry           // Get registry instance
apd_register_filter( FilterInterface $filter ): bool
apd_unregister_filter( string $name ): bool
apd_get_filter( string $name ): ?FilterInterface
apd_get_filters( array $args = [] ): array      // Filter by type, source
apd_has_filter( string $name ): bool
```

### Filter Registry Hooks

- Filters: `apd_register_filter_config`
- Actions: `apd_filter_registered`, `apd_filter_unregistered`

---

## Search Query

The Search Query (`src/Search/SearchQuery.php`) handles listing search and filtering via WP_Query hooks.

### Search Query Functions

```php
apd_search_query(): SearchQuery                 // Get instance
apd_get_filtered_listings( array $args = [] ): WP_Query
apd_get_orderby_options(): array
```

### Query Modification

- Hooks into `pre_get_posts` for main listing archives
- Keyword search via `s` param + meta field search
- Filter application via `modifyQuery()` on each active filter
- Custom orderby: date, title, views, random

### Filter Renderer Functions

```php
apd_filter_renderer(): FilterRenderer           // Get renderer instance
apd_render_search_form( array $args = [] ): string
apd_render_filter( string $name, mixed $value = null ): string
apd_render_active_filters( array $request = [] ): string
apd_render_no_results(): string
```

### Search Query Hooks

- Filters: `apd_search_query_args`, `apd_filter_options`, `apd_search_form_classes`, `apd_searchable_fields`, `apd_filterable_fields`, `apd_ajax_filter_response`
- Actions: `apd_before_search_form`, `apd_after_search_form`, `apd_before_filters`, `apd_after_filters`, `apd_before_ajax_filter`, `apd_after_ajax_filter`, `apd_search_query_modified`

---

## Available Filter Types

All filter types are in `src/Search/Filters/` and extend `AbstractFilter`:

| Type | Class | Source | Notes |
|------|-------|--------|-------|
| `text` | KeywordFilter | custom | Searches title, content, searchable meta fields |
| `select` | CategoryFilter | taxonomy | Hierarchical dropdown for apd_category |
| `checkbox` | TagFilter | taxonomy | Checkbox list for apd_tag |
| `range` | RangeFilter | field | Min/max number inputs with prefix/suffix |
| `date_range` | DateRangeFilter | field | Start/end date inputs |

### Filter URL Parameters

- Keyword: `apd_keyword`
- Category: `apd_category`
- Tag: `apd_tag[]`
- Range: `apd_{name}_min`, `apd_{name}_max`
- Date Range: `apd_{name}_start`, `apd_{name}_end`

---

## Creating Custom Filters

```php
use APD\Contracts\FilterInterface;
use APD\Search\Filters\AbstractFilter;

class CustomFilter extends AbstractFilter {
    public function __construct( array $config = [] ) {
        $defaults = [
            'name'   => 'custom',
            'label'  => 'Custom Filter',
            'source' => 'custom',
        ];
        parent::__construct( wp_parse_args( $config, $defaults ) );
    }

    public function getType(): string {
        return 'custom';
    }

    public function render( mixed $value ): string {
        // Return filter HTML
    }

    public function sanitize( mixed $value ): mixed {
        // Sanitize input
    }

    public function modifyQuery( WP_Query $query, mixed $value ): void {
        // Modify WP_Query based on filter value
    }
}

// Register the filter
apd_register_filter( new CustomFilter() );
```

---

## Search Templates

Search templates are located in `templates/search/` and can be overridden in theme at `all-purpose-directory/search/`:

- `search-form.php` - Main search form
- `filter-select.php` - Dropdown filter
- `filter-checkbox.php` - Checkbox list filter
- `filter-range.php` - Numeric range filter
- `filter-date-range.php` - Date range filter
- `active-filters.php` - Active filter chips
- `no-results.php` - Empty state message
