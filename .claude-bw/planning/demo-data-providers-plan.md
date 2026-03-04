# Plan: Extensible Demo Data for Module Plugins

## Context

The demo data system currently has a fixed generation sequence (users, categories, tags, listings, reviews, inquiries, favorites) with no way for module plugins to participate. The URL Directory module at `/Users/mike/Documents/www/private/apd-url-directory/` needs to add website URLs, favicons, nofollow flags, and click counts to generated demo listings. Other future modules (job boards, real estate, etc.) will have similar needs.

This plan adds a `DemoDataProviderInterface` and a `DemoDataProviderRegistry` following the existing registry patterns (ModuleRegistry, FieldRegistry, FilterRegistry), allowing modules to register demo data providers that generate, count, and clean up module-specific data.

## Files to Create

### 1. `src/Contracts/DemoDataProviderInterface.php` (new)

Interface for module demo data providers. Methods:

- `get_slug(): string` - unique identifier (matches module slug)
- `get_name(): string` - display name for admin UI
- `get_description(): string` - description shown next to checkbox
- `get_icon(): string` - dashicon class for stats table
- `get_form_fields(): array` - optional extra form fields (number inputs for quantities)
- `generate(array $context, DemoDataTracker $tracker): array` - generate data, return `['type_label' => count]`
- `delete(DemoDataTracker $tracker): array` - clean up data, return `['type_label' => count]`
- `count(DemoDataTracker $tracker): array` - count existing data, return `['type_label' => count]`

The `$context` array passed to `generate()`:
```php
[
    'user_ids'     => int[],   // generated demo users
    'listing_ids'  => int[],   // generated demo listings
    'category_ids' => int[],   // generated demo categories
    'tag_ids'      => int[],   // generated demo tags
    'options'      => array,   // provider-specific form data from POST
]
```

### 2. `src/Admin/DemoData/DemoDataProviderRegistry.php` (new)

Singleton registry following the ModuleRegistry pattern:

- `register(DemoDataProviderInterface $provider): bool` - validate slug, check duplicates, store, fire hook
- `unregister(string $slug): bool`
- `get(string $slug): ?DemoDataProviderInterface`
- `get_all(): array`
- `has(string $slug): bool`
- `count(): int`
- `init(): void` - fires `apd_demo_providers_init` action
- `reset_instance(): void` - for testing

Hooks:
- `apd_demo_providers_init` action (fired during `init()`)
- `apd_demo_provider_registered` action
- `apd_demo_provider_unregistered` action

### 3. `tests/unit/Admin/DemoData/DemoDataProviderRegistryTest.php` (new)

Unit tests covering registration, unregistration, retrieval, duplicates, hooks.

## Files to Modify

### 4. `src/Admin/DemoData/DemoDataPage.php`

**`init()` (line 113):** After the existing `apd_demo_data_init` action (line 133), initialize the provider registry:
```php
DemoDataProviderRegistry::get_instance()->init();
```

**`enqueue_assets()` (line 162):** Add module provider labels to the localized JS data so results display correctly. Build an associative array of `module_{slug}_{type}` => label from registered providers.

**`render_page()` (line 215):** Three additions:
1. **Stats table** (after line 271, the inquiries row): Loop over registered providers and render a stats row for each type they report via `count()`.
2. **Generate form** (after line 347, the favorites row): If providers exist, render a "Module Data" divider and a checkbox row per provider with any extra form fields from `get_form_fields()`.
3. **Total calculation** (line 223): Include module counts in the total.

**`ajax_generate()` (line 412):** After core generation (line 494) and before the `apd_after_generate_demo_data` action (line 507):
1. Build `$context` array from generated IDs
2. Loop over registered providers, check if `generate_module_{slug}` is in POST
3. Extract provider-specific options from POST, sanitize
4. Call `$provider->generate($context, $tracker)` and merge results with `module_{slug}_{type}` prefix
5. Include module counts in the `counts` response via a new helper on DemoDataTracker

### 5. `src/Admin/DemoData/DemoDataTracker.php`

**`delete_all()` (line 550):** Before core deletion (line 558), call each provider's `delete()` method and merge counts into the return array. Module data that references core data (like post meta on listings) must be deleted before core deletes the listings.

**New method `count_module_demo_data(): array`:** Returns `['slug' => ['type' => count, ...], ...]` by calling `count()` on each registered provider. Used by `render_page()` and the AJAX response.

### 6. `includes/demo-data-functions.php`

Add helper functions:
- `apd_demo_provider_registry(): DemoDataProviderRegistry`
- `apd_register_demo_provider(DemoDataProviderInterface $provider): bool`
- `apd_unregister_demo_provider(string $slug): bool`
- `apd_has_demo_provider(string $slug): bool`
- `apd_get_demo_provider(string $slug): ?DemoDataProviderInterface`

### 7. `assets/js/admin-demo-data.js`

**`handleGenerate()` (line 47):** Collect `generate_module_*` checkboxes and `module_*` number inputs from the form dynamically, adding them to the AJAX `formData`.

**`showResults()` (line 250):** After checking the hardcoded `labels` map, handle unknown keys with `module_` prefix by looking up names from a `moduleLabels` config property (passed via `wp_localize_script`).

**`showDeleteResults()` (line 287):** Same treatment - handle `module_` prefixed keys.

**`updateStats()` (line 321):** Already works with dynamic `data-type` attributes, no change needed since module rows will have matching `data-type` values.

### 8. `assets/css/admin-demo-data.css`

Add a `.apd-form-divider` style for the "Module Data" separator between core and module form rows:
```css
.apd-form-divider {
    padding: 14px 0 6px;
    margin-top: 4px;
    border-top: 2px solid #c3c4c7;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #646970;
}
```

## Deletion Order

Module providers' `delete()` runs **before** core deletion, because:
- Module meta on listings gets deleted when `wp_delete_post()` runs
- If modules created their own post types, those should be cleaned before core listings go away
- This matches the "dependents first" pattern used by core (reviews before listings)

## Usage Example (URL Directory Module)

The URL Directory module at `/Users/mike/Documents/www/private/apd-url-directory/` would create a class implementing `DemoDataProviderInterface` and register it:

```php
add_action( 'apd_demo_providers_init', function() {
    apd_register_demo_provider( new UrlDirectoryDemoProvider() );
});
```

Its `generate()` method iterates `$context['listing_ids']` and adds `_apd_website_url`, `_apd_nofollow`, `_apd_click_count` meta. Its `delete()` is a no-op (meta is deleted with listings). Its `count()` queries demo listings that have `_apd_website_url`.

**Note:** The actual URL Directory module implementation is out of scope for this PR -- this plan only covers the core plugin's extensibility API.

## Implementation Sequence

1. Create `DemoDataProviderInterface`
2. Create `DemoDataProviderRegistry`
3. Modify `DemoDataPage::init()` to initialize registry
4. Modify `DemoDataPage::render_page()` for module UI rows
5. Modify `DemoDataPage::enqueue_assets()` for module labels
6. Modify `DemoDataPage::ajax_generate()` for provider execution
7. Modify `DemoDataTracker` for deletion and counting
8. Add helper functions
9. Update JS for dynamic module results
10. Update CSS for divider
11. Write unit tests for registry

## Verification

1. `composer test:unit` - all existing + new tests pass
2. Sync to Docker and run Plugin Check
3. Manual: visit Demo Data page - should look identical (no providers registered)
4. Manual: create a mock provider in a test mu-plugin to verify the full flow (generate, count, delete)
