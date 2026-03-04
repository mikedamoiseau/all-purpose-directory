# Block System Reference

The Block System (`src/Blocks/`) provides Gutenberg blocks for the WordPress block editor.

## Core Components

- `BlockManager` - Singleton managing block registration
- `AbstractBlock` - Base class with server-side rendering support
- Individual block classes for each `apd/*` block

## Available Blocks

| Block | Name | Description |
|-------|------|-------------|
| `apd/listings` | Listings | Display listings in grid/list view with filtering |
| `apd/search-form` | Search Form | Search and filter form |
| `apd/categories` | Categories | Display category grid/list |

---

## Block Helper Functions

```php
apd_block_manager(): BlockManager    // Get manager instance
apd_get_block( string $name ): ?AbstractBlock
apd_has_block( string $name ): bool
apd_get_blocks(): array              // Get all registered blocks
```

---

## Listings Block Attributes

```php
[
    'view'           => 'grid',     // grid, list
    'columns'        => 3,          // 2, 3, 4 (grid only)
    'count'          => 12,         // posts per page
    'category'       => '',         // category slug
    'tag'            => '',         // tag slug
    'orderby'        => 'date',     // date, title, views, rand
    'order'          => 'DESC',     // ASC, DESC
    'ids'            => '',         // specific IDs (comma-separated)
    'exclude'        => '',         // exclude IDs
    'showImage'      => true,
    'showExcerpt'    => true,
    'excerptLength'  => 20,
    'showCategory'   => true,
    'showPagination' => true,
]
```

---

## Search Form Block Attributes

```php
[
    'filters'      => '',           // custom filters
    'showKeyword'  => true,
    'showCategory' => true,
    'showTag'      => false,
    'showSubmit'   => true,
    'submitText'   => '',           // defaults to "Search"
    'action'       => '',           // form action URL
    'layout'       => 'horizontal', // horizontal, vertical, inline
    'showActive'   => false,        // show active filter chips
]
```

---

## Categories Block Attributes

```php
[
    'layout'          => 'grid',    // grid, list
    'columns'         => 4,         // 2-6 (grid only)
    'count'           => 0,         // 0 = all
    'parent'          => '',        // parent ID, 0 for top-level
    'include'         => '',        // include IDs
    'exclude'         => '',        // exclude IDs
    'hideEmpty'       => true,
    'orderby'         => 'name',    // name, count, id
    'order'           => 'ASC',
    'showCount'       => true,
    'showIcon'        => true,
    'showDescription' => false,
]
```

---

## Creating Custom Blocks

```php
use APD\Blocks\AbstractBlock;

class MyBlock extends AbstractBlock {
    protected string $name = 'my-block';
    protected string $title = 'My Block';
    protected string $description = 'A custom block.';
    protected string $category = 'widgets';
    protected string $icon = 'admin-generic';
    protected array $keywords = [ 'custom', 'block' ];
    protected bool $uses_ssr = true;

    protected array $attributes = [
        'title' => [
            'type'    => 'string',
            'default' => '',
        ],
    ];

    protected function output( array $attributes, string $content, \WP_Block $block ): string {
        return sprintf( '<div class="my-block">%s</div>', esc_html( $attributes['title'] ) );
    }
}

// Register the block
$manager = apd_block_manager();
$manager->register( new MyBlock() );
```

---

## Block Hooks

- Actions: `apd_block_registered`, `apd_before_block_{name}`, `apd_after_block_{name}`
- Filters: `apd_block_args`, `apd_block_{name}_args`, `apd_block_{name}_output`
