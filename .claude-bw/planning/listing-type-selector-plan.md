# Plan: Listing Type Selector + Type-Aware Fields

## Context

The `apd_listing_type` taxonomy is hidden and auto-assigns "General" at save priority 99. With multiple modules active, admins have no way to choose a listing's type, and all fields show on all listings regardless of type. This plan adds:

1. A sidebar meta box for selecting listing type
2. Type-aware field filtering (fields only show for their assigned type)
3. Dynamic JS switching when the type changes in the editor

## Part 1: Field Config Extension

### `src/Fields/FieldRegistry.php`

**Add `listing_type` to `DEFAULT_FIELD_CONFIG` (line 58):**
```php
'listing_type' => null,  // null = all types, string = specific type, array = multiple types
```

**Add `listing_type` filter to `get_fields()` (line 333 area):**
```php
// Filter by listing_type.
if ( $args['listing_type'] !== null ) {
    $fields = array_filter(
        $fields,
        fn( $field ) => $field['listing_type'] === null
            || $field['listing_type'] === $args['listing_type']
            || ( is_array( $field['listing_type'] ) && in_array( $args['listing_type'], $field['listing_type'], true ) )
    );
}
```

Add `'listing_type' => null` to the `$defaults` array in `get_fields()`.

**Impact:** Core default fields (phone, email, website, etc.) have `listing_type => null` by default, so they remain global. Modules register fields with `listing_type => 'their-slug'` to make them type-specific.

### `tests/unit/Fields/FieldRegistryTest.php`

Add tests for the new `listing_type` filter arg in `get_fields()`.

## Part 1b: Module `hidden_fields` Config

### `src/Module/ModuleRegistry.php`

**Add `hidden_fields` to `DEFAULT_CONFIG` (line 55):**
```php
'hidden_fields' => [],  // Array of core field names to hide for this module's listing type
```

This lets modules declaratively hide global fields that overlap with their own fields. Example: a URL Directory module registers its own specialized `website_url` field and hides the core `website` field:

```php
apd_register_module( 'url-directory', [
    'name'          => 'URL Directory',
    'hidden_fields' => [ 'website' ],  // Hide core "website" field for url-directory listings
    'features'      => [ 'link_checker' ],
] );
```

### Integration with field display

The `apd_should_display_field` filter callback (Part 3) checks both:
1. Does the field's `listing_type` match? (type-specific fields)
2. Is the field hidden by a module for this listing type? (global fields hidden by module)

For #2, the callback looks up which modules have `hidden_fields` containing the field name, and if the listing's type matches that module's slug, the field is hidden. Uses existing `apd_get_modules()` helper.

### JS mapping update

The field-to-type JSON mapping (Part 4) also includes hidden fields with a special marker so JS can hide them when switching types:

```json
{
    "website_url": "url-directory",
    "website": {"hidden_by": ["url-directory"]}
}
```

The JS `toggleFieldsByType()` function handles both cases: type-specific fields (show only for matching types) and hidden fields (hide for specific types, show for all others).

## Part 2: Listing Type Meta Box

### Create `src/Admin/ListingTypeMetaBox.php`

New `final class ListingTypeMetaBox`:
- **Position:** `'side'` context, `'default'` priority
- **Conditional:** Only registers when 2+ listing type terms exist
- **UI:** Radio buttons in `<fieldset>` with screen-reader `<legend>`
- **Own nonce:** `apd_save_listing_type` / `apd_listing_type_nonce`
- **Save priority:** 20 on `save_post_apd_listing` (after fields at 10, before default at 99)
- **Save logic:** Standard guards, then `apd_set_listing_type()` for valid terms
- **Preselects** current type via `apd_get_listing_type()`
- **Outputs field-to-type mapping** as a `data-field-types` JSON attribute on a hidden element for JS consumption

### `src/Core/Plugin.php` (~line 151)

Add after existing `ListingMetaBox`:
```php
$listing_type_meta_box = new \APD\Admin\ListingTypeMetaBox();
$listing_type_meta_box->init();
```

### Create `tests/unit/Admin/ListingTypeMetaBoxTest.php`

Tests: hook registration, conditional meta box (2+ types vs 1), render (nonce, radios, fieldset, preselection, field-type mapping output), save guards, save valid/invalid type, constants.

## Part 3: Type-Aware Field Display (PHP)

### `src/Fields/FieldRenderer.php`

**Add `data-listing-types` attribute to field wrappers** (in `render_field_input()` ~line 308):

When a field has a `listing_type` value, add `data-listing-types="type1,type2"` to the wrapper div. Fields with `listing_type => null` get no attribute (always visible).

**Hook into `apd_should_display_field`** - Rather than modifying the FieldRenderer directly, register a filter callback in the `ListingTypeMetaBox` class (or a new lightweight class) that:
1. Gets the listing's current type via `apd_get_listing_type($listing_id)`
2. Checks if the field's `listing_type` config matches
3. Returns `false` if the field doesn't belong to this listing's type

This keeps the filtering logic decoupled. For new listings (`$listing_id === 0`), all fields show (type not set yet).

### `src/Admin/ListingMetaBox.php`

**Filter extracted values by listing type during save** (~line 175 area, after `extract_field_values()`):

Before calling `apd_process_fields($values)`, filter out values for fields that don't match the selected listing type. This prevents required-field validation failures for hidden type-specific fields:

```php
// Get the selected listing type from POST data.
$selected_type = isset( $_POST['apd_listing_type'] )
    ? sanitize_key( wp_unslash( $_POST['apd_listing_type'] ) )
    : apd_get_listing_type( $post_id );

// Filter values to only include fields matching the listing type.
if ( $selected_type ) {
    $values = $this->filter_values_by_listing_type( $values, $selected_type );
}
```

New private method `filter_values_by_listing_type()`:
- Gets field config for each value via `apd_get_field()`
- Keeps fields where `listing_type` is null or matches the selected type
- Also excludes fields listed in the module's `hidden_fields` for this type
- Removes values for non-matching fields (they stay in meta from previous saves, just not re-validated)

## Part 4: Dynamic JS Switching

### `assets/js/admin.js`

Replace the placeholder with listing type switching logic:

```javascript
(function($) {
    'use strict';

    $(document).ready(function() {
        initListingTypeSwitch();
    });

    function initListingTypeSwitch() {
        var $typeRadios = $('input[name="apd_listing_type"]');
        if (!$typeRadios.length) return;

        // Get field-to-type mapping from meta box data attribute
        var $mappingEl = $('#apd-field-type-mapping');
        if (!$mappingEl.length) return;
        var fieldTypes = JSON.parse($mappingEl.attr('data-field-types') || '{}');

        $typeRadios.on('change', function() {
            var selectedType = $(this).val();
            toggleFieldsByType(selectedType, fieldTypes);
        });

        // Initial state
        var currentType = $typeRadios.filter(':checked').val();
        if (currentType) {
            toggleFieldsByType(currentType, fieldTypes);
        }
    }

    function toggleFieldsByType(selectedType, fieldTypes) {
        $.each(fieldTypes, function(fieldName, config) {
            var $field = $('[data-field-name="' + fieldName + '"]');
            if (!$field.length) return;

            var visible;
            if (config && config.hidden_by) {
                // Global field hidden by specific modules
                visible = config.hidden_by.indexOf(selectedType) === -1;
            } else if (config === null) {
                // Global field, always visible
                visible = true;
            } else {
                // Type-specific field
                visible = (config === selectedType)
                    || (Array.isArray(config) && config.indexOf(selectedType) !== -1);
            }

            $field.toggle(visible);
        });
    }
})(jQuery);
```

The `ListingTypeMetaBox` render method outputs a hidden element with the JSON mapping:
```html
<div id="apd-field-type-mapping" data-field-types='{"website_url":"url-directory","website":{"hidden_by":["url-directory"]}}'></div>
```

Only fields with non-null `listing_type` or hidden-by-module entries are included. Global fields with no restrictions are omitted.

## Part 5: Admin List Table Column

### `src/Listing/AdminColumns.php`

**`add_columns()`** (~line 75): Insert `listing_type` column after `apd_category` when 2+ types exist.

**`render_column()`** (~line 104): Add `listing_type` case rendering term name as a filter link (same pattern as category column at line 155).

**New methods:** `has_multiple_listing_types()` and `render_listing_type_column()`.

## Files Summary

| Action | File | What |
|--------|------|------|
| Create | `src/Admin/ListingTypeMetaBox.php` | Type selector meta box + field display filter |
| Create | `tests/unit/Admin/ListingTypeMetaBoxTest.php` | Unit tests |
| Modify | `src/Fields/FieldRegistry.php` | Add `listing_type` to config + `get_fields()` filter |
| Modify | `src/Fields/FieldRenderer.php` | Add `data-listing-types` attribute to field wrappers |
| Modify | `src/Admin/ListingMetaBox.php` | Filter save values by listing type + hidden fields |
| Modify | `src/Module/ModuleRegistry.php` | Add `hidden_fields` to DEFAULT_CONFIG |
| Modify | `src/Listing/AdminColumns.php` | Add type column |
| Modify | `src/Core/Plugin.php` | Initialize ListingTypeMetaBox |
| Modify | `assets/js/admin.js` | Type-switching show/hide logic (incl. hidden fields) |
| Modify | `tests/unit/Fields/FieldRegistryTest.php` | Tests for listing_type filter |
| Modify | `tests/unit/Admin/ListingMetaBoxTest.php` | Tests for type-filtered save |

## Save Priority Flow

| Priority | Handler | Purpose |
|----------|---------|---------|
| 10 | `ListingMetaBox::save_meta_box()` | Custom fields (filtered by type) |
| 20 | `ListingTypeMetaBox::save_meta_box()` | Type selection |
| 99 | `ListingTypeTaxonomy::assign_default_term()` | Fallback default |

**Note:** Fields save at priority 10 reads the type from POST data (not from the taxonomy, which is set at priority 20). This works because the submitted radio value is available in POST immediately.

## Data Preservation

When changing a listing's type, existing field data is **preserved** in post meta. The data simply isn't displayed or re-validated. If the type is changed back, values reappear. No data deletion occurs.

## Scope Boundaries

- **Frontend submission form:** Not modified in this plan. Currently shows all non-admin fields. Can be extended to filter by type in a future iteration.
- **Block editor native panel:** Not needed. WordPress auto-converts side meta boxes to Gutenberg settings panels.

## Verification

1. `composer test:unit` - all tests pass (including new tests)
2. Sync + Plugin Check - no new warnings
3. Manual testing:
   - With 2+ modules: verify radio buttons appear in sidebar, selection persists on save
   - Change type: verify fields dynamically show/hide
   - Save with type-specific required field hidden: verify no validation error
   - With only "General" type: verify no meta box or column appears
   - Verify "Type" column in list table with filter links
   - Verify data preserved when switching types back and forth
   - Module with `hidden_fields: ['website']`: verify core "website" field hides when that module's type is selected, reappears for other types
   - Verify JS mapping includes both type-specific and hidden-by-module fields
