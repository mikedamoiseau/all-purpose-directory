# Fix All Disconnected Plugin Settings

## Context

During browser-based verification of all 37 admin settings across 6 tabs, we discovered that **19 settings** are saved correctly but never consumed by the frontend runtime. Additionally, a **critical bug** in `sanitize_settings()` wipes checkbox defaults on first save. This plan fixes all discovered bugs, organized by priority.

---

## Priority 1: Critical — Cross-Tab Checkbox Reset on First Save

**Bug**: When saving any tab on a fresh install, all checkbox settings from *other* tabs are set to `false` because `$existing` has no keys for them, and `!empty(null)` = `false`.

**File**: `src/Admin/Settings.php` — `sanitize_settings()` method (lines 1709-1822)

**Fix**: Add `?? $defaults['key']` inside each `!empty()` in every `else` branch:

```php
// Before:
$sanitized['enable_reviews'] = ! empty( $existing['enable_reviews'] );
// After:
$sanitized['enable_reviews'] = ! empty( $existing['enable_reviews'] ?? $defaults['enable_reviews'] );
```

**All affected lines** (17 checkbox lines across 5 tab groups):
- Lines 1710-1712: `enable_reviews`, `enable_favorites`, `enable_contact_form`
- Line 1736: `guest_submission`
- Lines 1770-1774: `show_thumbnail`, `show_excerpt`, `show_category`, `show_rating`, `show_favorite`
- Lines 1807-1812: `notify_submission`, `notify_approved`, `notify_rejected`, `notify_expiring`, `notify_review`, `notify_inquiry`
- Lines 1820-1821: `delete_data`, `debug_mode`

**Tests**:
- Update `tests/unit/Admin/SettingsTest.php` line 413: change `assertFalse` to `assertTrue` for `enable_reviews`
- Add `test_fresh_install_preserves_checkbox_defaults_across_tabs` test

---

## Priority 2: Wire Display/Listing Settings to Frontend

### 2a. Shortcode defaults from admin settings

**File**: `src/Shortcode/ListingsShortcode.php`

Add a constructor that reads admin settings into `$this->defaults`:
```php
public function __construct() {
    $this->defaults['view']          = apd_get_default_view();      // was 'grid'
    $this->defaults['columns']       = apd_get_default_grid_columns(); // was 3
    $this->defaults['count']         = apd_get_listings_per_page();    // was 12
    $this->defaults['show_image']    = apd_get_option('show_thumbnail', true) ? 'true' : 'false';
    $this->defaults['show_excerpt']  = apd_get_option('show_excerpt', true) ? 'true' : 'false';
    $this->defaults['show_category'] = apd_get_option('show_category', true) ? 'true' : 'false';
}
```

This preserves shortcode override behavior — user-supplied `[apd_listings view="list"]` still wins.

**Existing helpers** (already defined, just unused):
- `apd_get_default_view()` — `includes/functions.php:4439`
- `apd_get_default_grid_columns()` — `includes/functions.php:4450`
- `apd_get_listings_per_page()` — `includes/functions.php:4428`

### 2b. View class defaults from admin settings

**Files**: `src/Frontend/Display/GridView.php`, `src/Frontend/Display/ListView.php`

Add constructors that read display toggle settings as baseline defaults:
```php
public function __construct( array $config = [] ) {
    $this->defaults['show_image']    = (bool) apd_get_option('show_thumbnail', true);
    $this->defaults['show_excerpt']  = (bool) apd_get_option('show_excerpt', true);
    $this->defaults['show_category'] = (bool) apd_get_option('show_category', true);
    $this->defaults['show_rating']   = (bool) apd_get_option('show_rating', true);
    $this->defaults['show_favorite'] = (bool) apd_get_option('show_favorite', true);
    // GridView also: $this->defaults['columns'] = (int) apd_get_option('grid_columns', 3);
    parent::__construct( $config );
}
```

### 2c. Template conditional rendering

**Files**: `templates/listing-card.php`, `templates/listing-card-list.php`

Wrap each display section in conditionals using the extracted template variables:
- Image section: `if ( ($show_image ?? true) && $card_data['has_thumbnail'] )`
- Category badges: `if ( ($show_category ?? true) && ... )`
- Excerpt: `if ( ($show_excerpt ?? true) && ... )`
- Rating: `if ( ($show_rating ?? true) && ... )`

Use `?? true` fallback so direct template calls (without view config) still render everything.

### 2d. `listings_per_page` in SearchQuery

**File**: `src/Search/SearchQuery.php`

- Line 411: Change `get_option('posts_per_page', 10)` to `apd_get_listings_per_page()`
- Line 144 (in `modify_main_query`): Add `$query->set('posts_per_page', apd_get_listings_per_page());`

**File**: `src/Frontend/Display/AbstractView.php`
- Line 439: Change `get_option('posts_per_page', 10)` to `apd_get_listings_per_page()`

### 2e. `enable_favorites` guard on card buttons

**File**: `src/User/FavoriteToggle.php`

Add early return in `render_card_button()` (line 213), `render_card_button_fallback()` (line 237), and `render_single_button()`:
```php
if ( ! apd_favorites_enabled() ) {
    return;
}
```

---

## Priority 3: Wire Remaining Display Settings

### 3a. `custom_css` output to frontend

**File**: `src/Core/Assets.php` — in `enqueue_frontend_assets()` after `wp_enqueue_style`:
```php
$custom_css = apd_get_option( 'custom_css', '' );
if ( ! empty( trim( $custom_css ) ) ) {
    wp_add_inline_style( 'apd-frontend', $custom_css );
}
```

### 3b. `archive_title` in TemplateLoader

**File**: `src/Core/TemplateLoader.php` — `get_archive_title()` (line 255)

For post type archive, check custom title first:
```php
if ( is_post_type_archive( 'apd_listing' ) ) {
    $custom_title = apd_get_option( 'archive_title', '' );
    if ( ! empty( $custom_title ) ) {
        $title = $custom_title;
    } else {
        // existing fallback logic
    }
}
```

### 3c. `single_layout` in single listing template

**File**: `templates/single-listing.php`

- Read `$single_layout = apd_get_option('single_layout', 'sidebar');`
- Add layout modifier class: `apd-single-listing__layout--{$single_layout}`
- Wrap sidebar `<aside>` in `<?php if ( 'sidebar' === $single_layout ) : ?>`
- Add CSS for `.apd-single-listing__layout--full` (full-width main content)

### 3d. `date_format` for listings

**File**: `includes/functions.php` — Add helpers:
```php
function apd_get_listing_date_format(): string {
    $format = apd_get_setting('date_format', 'default');
    return ('default' === $format || empty($format))
        ? (string) get_option('date_format', 'Y-m-d')
        : $format;
}

function apd_get_listing_date( int|\WP_Post|null $post = null ): string {
    return get_the_date( apd_get_listing_date_format(), $post );
}
```

**Templates to update**:
- `templates/single-listing.php` line 70: `get_the_date()` → `apd_get_listing_date()`
- `templates/listing-card-list.php` lines 42-43: use `apd_get_listing_date($listing_id)`
- `templates/dashboard/listing-row.php` line 132: use `apd_get_listing_date($listing_id)`

---

## Priority 4: Submission Settings Enforcement

### 4a. `who_can_submit` / `submission_roles` / `guest_submission`

**File**: `src/Frontend/Submission/SubmissionHandler.php` — `check_permissions()` (line 331)

Replace with settings-aware logic:
- `who_can_submit = 'logged_in'` → require `is_user_logged_in()`
- `who_can_submit = 'specific_roles'` → require matching role (admins always pass)
- `who_can_submit = 'anyone'` + `guest_submission = false` → require login
- `who_can_submit = 'anyone'` + `guest_submission = true` → allow all
- Preserve `apd_user_can_submit_listing` filter as final override

**File**: `src/Shortcode/SubmissionFormShortcode.php` — Add matching check in `output()` to hide the form for unauthorized users.

### 4b. `terms_page` wiring

**File**: `src/Shortcode/SubmissionFormShortcode.php`

In form config building: when `terms_page` is set and `show_terms` is at default `false`, auto-enable terms with the terms page permalink.

**File**: `src/Frontend/Submission/SubmissionHandler.php`

Add terms validation in `validate_submission()`: if `terms_page` is configured, require `terms_accepted`.

### 4c. `currency_symbol` / `currency_position` as global defaults

**File**: `src/Field/Types/CurrencyField.php` (verify exact path)

Change fallback values:
```php
// Before: $field['currency_symbol'] ?? '$'
// After:  $field['currency_symbol'] ?? apd_get_currency_symbol()
```

---

## Deferred (No Code Changes)

| Setting | Reason |
|---------|--------|
| `distance_unit` | No geolocation module exists yet. Helper `apd_get_distance_unit()` already defined. |

---

## Implementation Order

1. **P1**: Cross-tab checkbox reset fix (Settings.php + tests)
2. **P2a-b**: Shortcode + View defaults from admin settings
3. **P2c**: Template conditional rendering
4. **P2d**: SearchQuery `listings_per_page`
5. **P2e**: FavoriteToggle guard
6. **P3a**: Custom CSS output
7. **P3b**: Archive title
8. **P3c**: Single layout
9. **P3d**: Date format helpers + template updates
10. **P4a**: Submission permission enforcement
11. **P4b**: Terms page wiring
12. **P4c**: Currency field global defaults

---

## Files to Modify (Summary)

| File | Changes |
|------|---------|
| `src/Admin/Settings.php` | P1: Add `?? $defaults[key]` to 17 checkbox lines |
| `src/Shortcode/ListingsShortcode.php` | P2a: Add constructor reading admin settings |
| `src/Frontend/Display/GridView.php` | P2b: Add constructor reading display toggles |
| `src/Frontend/Display/ListView.php` | P2b: Add constructor reading display toggles |
| `templates/listing-card.php` | P2c: Conditional rendering wrappers |
| `templates/listing-card-list.php` | P2c: Conditional rendering + P3d: date format |
| `src/Search/SearchQuery.php` | P2d: Use `apd_get_listings_per_page()` in 2 places |
| `src/Frontend/Display/AbstractView.php` | P2d: Use `apd_get_listings_per_page()` |
| `src/User/FavoriteToggle.php` | P2e: Guard with `apd_favorites_enabled()` |
| `src/Core/Assets.php` | P3a: `wp_add_inline_style` for custom CSS |
| `src/Core/TemplateLoader.php` | P3b: Read `archive_title` setting |
| `templates/single-listing.php` | P3c: Layout class + conditional sidebar + P3d: date |
| `includes/functions.php` | P3d: Add `apd_get_listing_date_format()` + `apd_get_listing_date()` |
| `src/Frontend/Submission/SubmissionHandler.php` | P4a: Settings-aware permissions + P4b: terms validation |
| `src/Shortcode/SubmissionFormShortcode.php` | P4a: Permission display + P4b: terms_page config |
| `src/Field/Types/CurrencyField.php` | P4c: Global currency defaults |
| `templates/dashboard/listing-row.php` | P3d: Date format |

**Tests to update**:
- `tests/unit/Admin/SettingsTest.php` — Fix assertion + add fresh-install test
- `tests/unit/Shortcode/ListingsShortcodeTest.php` — Mock settings helpers
- `tests/unit/Search/SearchQueryTest.php` — Update `posts_per_page` expectations
- `tests/unit/Core/TemplateLoaderTest.php` — Add archive_title test cases

---

## Verification

1. `composer test:unit` — all tests pass
2. `composer phpcs` — no style violations
3. Browser verification in Docker (localhost:8085):
   - Fresh install: save General tab, verify Listings/Display/Email checkboxes preserved
   - Set `default_view` to 'list' → verify directory page shows list view
   - Set `grid_columns` to 2 → verify 2-column grid
   - Set `listings_per_page` to 6 → verify 6 listings shown
   - Toggle `show_thumbnail` off → verify no images on cards
   - Set `custom_css` → verify it appears in page source
   - Set `archive_title` → verify on archive page
   - Set `single_layout` to 'full' → verify no sidebar on single listing
   - Set `who_can_submit` to 'specific_roles' → verify non-matching role blocked
   - Set `enable_favorites` off → verify no heart buttons on cards
