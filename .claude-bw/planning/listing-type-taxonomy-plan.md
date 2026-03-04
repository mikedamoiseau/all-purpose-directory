# Plan: Hidden `apd_listing_type` Taxonomy

## Context

All listings share a single `apd_listing` post type regardless of which module created them. There's no way to filter listings by module type (e.g., show only URL directory listings vs venue listings). This adds a hidden taxonomy `apd_listing_type` that associates each listing with a module, enabling filtering in the admin, shortcodes, blocks, and REST API.

## Files to Modify/Create

| Action | File | Change |
|--------|------|--------|
| **Create** | `src/Taxonomy/ListingTypeTaxonomy.php` | New taxonomy class |
| **Edit** | `src/Core/Plugin.php` (line ~531) | Register taxonomy in `register_taxonomies()` |
| **Edit** | `includes/functions.php` | Add helper functions |
| **Edit** | `src/Listing/AdminColumns.php` (lines ~281, ~365) | Add filter dropdown + query handling |
| **Edit** | `src/Shortcode/ListingsShortcode.php` (lines ~48, ~301) | Add `type` attribute + tax_query |
| **Edit** | `src/Blocks/ListingsBlock.php` (lines ~76, ~260) | Add `type` attribute + tax_query |
| **Edit** | `src/Api/Endpoints/ListingsEndpoint.php` | Add `type` param (collection, create, update, response) |
| **Create** | `tests/unit/Taxonomy/ListingTypeTaxonomyTest.php` | Unit tests for taxonomy class |
| **Create** | `tests/unit/Taxonomy/ListingTypeFunctionsTest.php` | Unit tests for helper functions |

## Implementation Steps

### Step 1: Create `ListingTypeTaxonomy` class

**File:** `src/Taxonomy/ListingTypeTaxonomy.php`

Follows the exact pattern of `CategoryTaxonomy` and `TagTaxonomy`:

- Constants: `TAXONOMY = 'apd_listing_type'`, `DEFAULT_TERM = 'general'`
- `register()` - calls `register_taxonomy()` with `PostType::POST_TYPE` and hidden args:
  - `public => false`, `publicly_queryable => false`, `show_ui => false`
  - `show_in_rest => true` (for block editor / API)
  - `rewrite => false`, `query_var => false`
  - `hierarchical => false`
- `init()` - sets up hooks:
  - Calls `ensure_default_term()` to create "General" term
  - Calls `sync_existing_modules()` to retroactively create terms for modules that registered at priority 1 (before taxonomy exists at priority 5)
  - Hooks `apd_module_registered` for future modules
  - Hooks `save_post_apd_listing` at priority 99 to auto-assign "General" to untyped listings
- `ensure_default_term()` - creates "General" term if missing
- `sync_existing_modules()` - iterates `apd_get_modules()` and creates terms
- `on_module_registered($slug, $config)` - creates a term using module name/slug
- `assign_default_term($post_id)` - assigns "General" if no listing type set (skips revisions/autosaves). Uses `apd_default_listing_type` filter.

### Step 2: Register in Plugin.php

Add to `register_taxonomies()` (after tag taxonomy, ~line 539):

```php
$listing_type_taxonomy = new \APD\Taxonomy\ListingTypeTaxonomy();
$listing_type_taxonomy->register();
$listing_type_taxonomy->init();
```

### Step 3: Helper functions in `includes/functions.php`

Add these functions (following existing naming conventions):

- `apd_get_listing_type_taxonomy(): string` - returns taxonomy slug
- `apd_get_listing_type(int $listing_id): string` - returns type slug (defaults to 'general')
- `apd_set_listing_type(int $listing_id, string $type): bool` - sets listing type via `wp_set_object_terms()`
- `apd_listing_is_type(int $listing_id, string $type): bool` - type check
- `apd_get_listing_types(bool $hide_empty = false): WP_Term[]` - all type terms
- `apd_get_listing_type_term(string $type_slug): ?WP_Term` - single term object
- `apd_get_listing_type_count(string $type_slug): int` - listing count for type

### Step 4: Admin filter in AdminColumns.php

**render_filters()** (~line 281) - add `$this->render_listing_type_filter()` between category and status filters.

**render_listing_type_filter()** - new private method:
- Only renders when 2+ listing type terms exist (i.e., at least one module besides "General")
- Uses custom `<select>` (matching status filter pattern) since it's a flat list
- Query param: `apd_listing_type` (the taxonomy slug)
- Shows count next to each term name

**apply_filters()** (~line 365) - add listing type `tax_query` handling:
- Read `$_GET['apd_listing_type']`, sanitize, append to `tax_query` array
- Required because `query_var => false` means WP won't auto-handle it (unlike the category filter)

### Step 5: Shortcode `type` attribute

**ListingsShortcode.php:**
- Add `'type' => ''` to `$defaults` array (~line 64)
- Add matching entry to `$attribute_docs`
- In `build_query_args()` (~line 319), add tax_query block matching the category/tag pattern:

```php
if ( ! empty( $atts['type'] ) ) {
    $types               = array_map( 'trim', explode( ',', $atts['type'] ) );
    $args['tax_query'][] = [
        'taxonomy' => ListingTypeTaxonomy::TAXONOMY,
        'field'    => 'slug',
        'terms'    => $types,
    ];
}
```

Usage: `[apd_listings type="url-directory"]`

### Step 6: Block `type` attribute

**ListingsBlock.php:**
- Add `'type'` to `$attributes` array (~line 133):
  ```php
  'type' => ['type' => 'string', 'default' => ''],
  ```
- In `build_query_args()` (~line 280), add tax_query block matching the category/tag pattern (using `sanitize_key`)

### Step 7: REST API support

**ListingsEndpoint.php:**

1. **`get_collection_params()`** (~line 715) - add `type` parameter:
   ```php
   'type' => ['description' => '...', 'type' => 'string'],
   ```

2. **`get_items()`** (~line 175) - add type tax_query (matching category/tag pattern):
   ```php
   $type = $request->get_param('type');
   if (!empty($type)) { /* append tax_query */ }
   ```

3. **`get_create_params()`** (~line 773) - add `type` string parameter

4. **`create_item()`** (~line 327) - after categories/tags handling:
   ```php
   $type = $request->get_param('type');
   if (!empty($type)) {
       wp_set_object_terms($listing_id, sanitize_key($type), ListingTypeTaxonomy::TAXONOMY);
   }
   ```

5. **`update_item()`** (~line 445) - after categories/tags handling:
   ```php
   $type = $request->get_param('type');
   if ($type !== null) {
       wp_set_object_terms($listing_id, sanitize_key($type), ListingTypeTaxonomy::TAXONOMY);
   }
   ```

6. **`prepare_item_for_response()`** (~line 590) - add `type` to response data:
   ```php
   $listing_types = wp_get_object_terms($listing->ID, ListingTypeTaxonomy::TAXONOMY);
   $data['type'] = (!is_wp_error($listing_types) && !empty($listing_types))
       ? $listing_types[0]->slug : ListingTypeTaxonomy::DEFAULT_TERM;
   ```

### Step 8: Unit tests

**`tests/unit/Taxonomy/ListingTypeTaxonomyTest.php`** - test the class:
- Constants (`TAXONOMY`, `DEFAULT_TERM`)
- `register()` calls `register_taxonomy()` with correct hidden args
- `ensure_default_term()` creates/skips "General" term
- `on_module_registered()` creates/skips module terms
- `assign_default_term()` assigns "General" to untyped, skips typed, skips revisions

**`tests/unit/Taxonomy/ListingTypeFunctionsTest.php`** - test helper functions:
- `apd_get_listing_type()` returns slug or default
- `apd_set_listing_type()` calls `wp_set_object_terms()`
- `apd_listing_is_type()` comparison
- `apd_get_listing_types()` returns terms or empty on error

### Step 9: Run tests + plugin check

- `composer test:unit` - verify all tests pass
- Sync + `composer install --no-dev` + plugin check

## Hooks Introduced

- **Action:** `apd_listing_type_registered` - fires when a listing type term is auto-created from module registration
- **Filter:** `apd_default_listing_type` - override the default type assigned to new listings (default: 'general')

## What Module Authors Do

Modules call `apd_set_listing_type($listing_id, 'url-directory')` on their listing save hooks. Example for URL Directory:

```php
add_action('apd_after_listing_save', function($post_id, $values) {
    $url = apd_get_listing_field($post_id, 'website_url');
    if (!empty($url)) {
        apd_set_listing_type($post_id, 'url-directory');
    }
}, 10, 2);
```

Or override the default for all new listings:

```php
add_filter('apd_default_listing_type', fn() => 'url-directory');
```

## Existing Listings

After activation, existing listings won't have a type term. The `apd_get_listing_type()` helper returns `'general'` for untyped listings. The `assign_default_term` hook assigns "General" on next save. Admin filter count for "General" may undercount until listings are re-saved - acceptable tradeoff vs a migration script.
