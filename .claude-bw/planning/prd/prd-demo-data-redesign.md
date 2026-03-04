# PRD: Demo Data Page Redesign

## Introduction

The demo data system currently treats all modules as enhancements to a shared pool of core listings. When multiple modules are active, every provider runs against every listing, resulting in a "last module wins" bug where listings accumulate meta from all modules but only display one type. The page layout is a single monolithic form with no separation between module concerns.

This redesign replaces the single-form page with a tabbed, per-module architecture. Each tab independently generates and manages its own listings, categories, and module-specific data. A new category scoping mechanism ensures categories are associated with the correct listing type.

## Goals

- Each module generates its own typed listings with module-appropriate categories and fields
- Per-tab generation and deletion so modules don't interfere with each other
- Category scoping via term meta so categories are filtered by listing type in the admin
- WP-CLI commands updated with a `--module` flag for targeted generation/deletion
- Shared demo users above tabs to avoid duplication
- One generate/delete operation per run (single active tab)

## User Stories

### US-001: Shared Users Section Above Tabs
**Description:** As an admin, I want demo users to be shared across all modules so that I don't create duplicate users when generating data for multiple tabs.

**Acceptance Criteria:**
- [ ] Users section renders above the tab bar, outside of any tab panel
- [ ] Users section has its own "Generate Users" button and count display
- [ ] Users section has a "Delete Users" button (only shown when demo users exist AND no module demo data remains)
- [ ] "Delete Users" button is disabled/hidden with message "Delete all module demo data first" when any tab still has demo data
- [ ] Generating users does not trigger any tab-specific generation
- [ ] Demo users are tracked with `_apd_demo_data = 'users'` (not module-scoped)
- [ ] All tabs can reference shared demo users when generating listings, reviews, etc.

### US-002: Tabbed Interface with General Tab
**Description:** As an admin, I want the first tab to be "General" so that I can generate generic directory listings without any module-specific fields.

**Acceptance Criteria:**
- [ ] "General" tab is always present as the first tab
- [ ] General tab generates listings with `listing_type = 'general'` taxonomy term
- [ ] General tab has checkboxes for: Categories, Tags, Listings, Reviews, Inquiries, Favorites
- [ ] General tab has quantity inputs for: Tags count, Listings count
- [ ] Categories generated are scoped to the `general` listing type via `_apd_listing_type` term meta
- [ ] General tab shows its own status counts (categories, tags, listings, reviews, inquiries created by this tab)
- [ ] General tab has its own Generate and Delete buttons
- [ ] Category dataset uses the existing generic categories (Restaurants, Hotels, Shopping, etc.)

### US-003: Module Tabs for Active Modules
**Description:** As an admin, I want each active module to have its own tab so that I can generate module-specific demo data independently.

**Acceptance Criteria:**
- [ ] One tab appears per registered demo data provider (URL Directory, Job Board, etc.)
- [ ] Tabs only appear for active/registered modules
- [ ] Tab label matches the module's display name and icon
- [ ] Each module tab has its own: status counts, generation form, Generate button, Delete button
- [ ] Module tabs include module-specific form fields from `get_form_fields()` (e.g., Job Board's company count)
- [ ] Each module tab generates listings with the correct `listing_type` taxonomy term
- [ ] Module tabs include checkboxes for: Categories, Tags, Listings, plus module-specific extras
- [ ] Module-specific extras (e.g., Job Board: Companies, Applications, Alerts) appear in the tab form

### US-004: Per-Tab Generation (One Module Per Run)
**Description:** As an admin, I want clicking "Generate" on a tab to only generate data for that tab so that modules don't interfere with each other.

**Acceptance Criteria:**
- [ ] Generate button submits AJAX with the active tab's module slug
- [ ] Only the active tab's data types are generated in a single run
- [ ] Generated listings receive the correct `listing_type` taxonomy term for that tab
- [ ] Generated categories receive `_apd_listing_type` term meta matching the tab's module slug
- [ ] Tags are shared (no listing type scoping) but tracked to the generating module for deletion
- [ ] Reviews, inquiries, favorites reference only listings created in the same tab run
- [ ] Module providers receive only their own listing IDs in the context (not all listings)
- [ ] Status counts update after generation to reflect the new data for that tab only
- [ ] Progress indicator shows within the active tab panel

### US-005: Per-Tab Deletion
**Description:** As an admin, I want to delete demo data for a single module without affecting other modules' data.

**Acceptance Criteria:**
- [ ] Each tab has a Delete button that only removes data tracked to that module
- [ ] Confirmation dialog shows count of items to be deleted for that specific module
- [ ] Deleting "General" tab data does not delete Job Board or URL Directory data, and vice versa
- [ ] Deleting a module's data also removes: its scoped categories, its tags, its listings (and their reviews/inquiries/favorites)
- [ ] Module-specific extras are deleted (e.g., Job Board companies, applications, alerts)
- [ ] Status counts update to zero for the deleted tab; other tabs remain unchanged
- [ ] Delete button is hidden when no demo data exists for that tab

### US-006: Demo Data Tracking by Module Slug
**Description:** As a developer, I need demo data items tracked by module slug so that per-tab deletion and counting works correctly.

**Acceptance Criteria:**
- [ ] `_apd_demo_data` meta value changes from `'1'` to the module slug (e.g., `'general'`, `'url-directory'`, `'job-board'`)
- [ ] `DemoDataTracker` methods accept an optional `$module` parameter to filter by slug
- [ ] `mark_post_as_demo()`, `mark_term_as_demo()`, `mark_comment_as_demo()` accept a `$module` parameter (default: `'general'`)
- [ ] `mark_user_as_demo()` continues to use `'users'` as the value (users are shared)
- [ ] `count_demo_data()` accepts an optional `$module` parameter; when provided, counts only that module's data
- [ ] `get_demo_post_ids()` accepts an optional `$module` parameter for filtered queries
- [ ] `get_demo_term_ids()` accepts an optional `$module` parameter for filtered queries
- [ ] `delete_all()` is updated or deprecated in favor of per-module `delete_by_module($module)` method
- [ ] Backwards compatibility: queries for `META_VALUE = '1'` still work for any legacy data

### US-007: Category Scoping via Term Meta
**Description:** As an admin, I want categories to be associated with a listing type so that when I edit a Job Board listing I only see job-related categories, not restaurant categories.

**Acceptance Criteria:**
- [ ] New term meta key `_apd_listing_type` on `apd_category` taxonomy terms
- [ ] Categories with no `_apd_listing_type` meta are shown for all listing types (backwards compatible)
- [ ] Categories with `_apd_listing_type` set are only shown when editing a listing of that type
- [ ] Category picker in the listing edit screen filters categories by the listing's current type
- [ ] Category filter on the frontend search also respects listing type scoping
- [ ] Demo data generation sets `_apd_listing_type` on generated categories to match the tab's module slug
- [ ] Admin category editor (edit-tags.php) shows which listing type a category is scoped to
- [ ] Admin category list table (edit-tags.php) has a "Listing Type" column showing the scoped type(s)
- [ ] Admin category list table has a filter dropdown to filter categories by listing type
- [ ] Categories can be scoped to multiple listing types (store as array or multiple meta entries)

### US-008: Module-Specific Category Datasets
**Description:** As a developer, I need each module to provide its own category dataset so that generated categories are contextually appropriate (e.g., "Engineering" for Job Board, not "Restaurants").

**Acceptance Criteria:**
- [ ] `DemoDataProviderInterface` gains a new optional method or the provider can supply category data
- [ ] General tab uses existing `CategoryData::get_categories()` (Restaurants, Hotels, Shopping, etc.)
- [ ] Each module provider can define its own category hierarchy with names, icons, and colors
- [ ] Module category data is used when the module's tab generates categories
- [ ] Category slugs are prefixed or namespaced to avoid collisions between modules (e.g., `jb-engineering` vs `re-residential`)
- [ ] The `apd_demo_category_data` filter still works for customization

### US-009: AJAX Tab Switching (No Page Reload)
**Description:** As an admin, I want tab switching to be instant without reloading the page.

**Acceptance Criteria:**
- [ ] Tabs use JavaScript to show/hide tab panels without a page reload
- [ ] All tab content is rendered server-side on initial page load (not lazy-loaded via AJAX)
- [ ] Active tab state is stored in the URL hash (e.g., `#url-directory`) for bookmarking/refresh
- [ ] Tab styling matches the existing Settings page tab pattern
- [ ] Generate and Delete AJAX operations work independently within each tab panel
- [ ] Progress indicators and result messages are scoped to the active tab panel

### US-010: WP-CLI Module Flag
**Description:** As a developer, I want to use `--module` with WP-CLI commands so that I can generate or delete demo data for a specific module from the command line.

**Acceptance Criteria:**
- [ ] `wp apd demo generate --module=general` generates only General tab data
- [ ] `wp apd demo generate --module=url-directory` generates only URL Directory data
- [ ] `wp apd demo generate --module=all` generates data for all available modules (one after another)
- [ ] `wp apd demo generate` without `--module` defaults to `general` for backwards compatibility
- [ ] `wp apd demo delete --module=job-board --yes` deletes only Job Board demo data
- [ ] `wp apd demo delete --yes` without `--module` deletes all demo data across all modules
- [ ] `wp apd demo status` shows counts grouped by module
- [ ] `wp apd demo status --module=real-estate` shows counts for a single module
- [ ] Invalid module slugs produce a helpful error message listing available modules
- [ ] `--listings`, `--users`, `--tags` count flags still work within each module context

## Functional Requirements

- FR-1: The page must render a shared Users section above the tab bar with its own generate/delete/count controls
- FR-2: The tab bar must always start with a "General" tab, followed by one tab per active module provider
- FR-3: Each tab panel must contain: status count table, generation form with checkboxes and quantity inputs, Generate button, Delete button, progress indicator, results area
- FR-4: Clicking "Generate" must submit an AJAX request scoped to the active tab's module slug
- FR-5: The AJAX generate handler must create listings with the correct `listing_type` taxonomy term for the active module
- FR-6: The AJAX generate handler must pass only newly-created listing IDs to the module provider (not all listings)
- FR-7: The AJAX delete handler must accept a module slug and only delete data tracked to that module
- FR-8: `_apd_demo_data` meta must store the module slug instead of `'1'`
- FR-9: `_apd_demo_data` for users must store `'users'` (shared across all modules)
- FR-10: Tags must be shared (no `_apd_listing_type` scoping) but tracked to their generating module for deletion purposes
- FR-11: `apd_category` terms must support an `_apd_listing_type` term meta to scope them to a listing type
- FR-12: The listing edit screen category picker must filter categories by the listing's `listing_type`
- FR-13: Categories with no `_apd_listing_type` meta must appear for all listing types
- FR-14: Each module provider must be able to supply its own category dataset (hierarchy, icons, colors)
- FR-15: Tab switching must use JavaScript show/hide without page reload; active tab stored in URL hash
- FR-16: `wp apd demo generate` must support `--module=<slug>` flag (default: `general`)
- FR-17: `wp apd demo delete` must support `--module=<slug>` flag (default: all modules)
- FR-18: `wp apd demo status` must display counts grouped by module

## Non-Goals

- No drag-and-drop reordering of tabs
- No lazy-loading of tab content via AJAX (all tabs rendered on page load)
- No per-listing-type tag scoping (tags remain shared)
- No migration of existing demo data from old `'1'` tracking format (legacy data can be bulk-deleted, then regenerated)
- No global "Delete All" button (each tab manages its own data independently)
- No cross-module dependencies (generating Job Board data does not require General data to exist first)
- No changes to the frontend display or shortcodes in this feature

## Design Considerations

- Tab styling should match the existing Settings page pattern (`apd-admin-settings.css` nav-tab classes)
- Each tab panel should follow the same card-based layout currently used (status section, form section, delete section stacked vertically)
- Users section above tabs should be visually distinct (e.g., a card/box with a subtle background) so it's clear it's shared
- Module tabs should show the module's dashicon in the tab label for quick visual identification
- Generate/Delete buttons and progress indicators are self-contained within each tab panel
- Results messages (success/error) display inline within the tab that triggered them

## Technical Considerations

- **DemoDataProviderInterface changes:** Adding optional methods for category data may require a new interface (e.g., `DemoDataCategoryProviderInterface`) to maintain backwards compatibility, or provide a default implementation
- **DemoDataTracker changes:** All query methods need a `$module` parameter. SQL queries must filter by `meta_value = $module` instead of `meta_value = '1'`
- **DemoDataGenerator changes:** `generate_listings()` must accept a `$listing_type` parameter and set the taxonomy term. `generate_categories()` must accept a category dataset and set `_apd_listing_type` term meta
- **CategoryTaxonomy changes:** The category admin columns and edit form should display the listing type scope. The category picker meta box on listings needs JS filtering
- **CSS:** New file or extend `admin-demo-data.css` with tab styles. Reuse `admin-base.css` tab classes if available
- **JS:** Significant rewrite of `admin-demo-data.js` to handle tab switching, per-tab AJAX, per-tab progress/results, and scoped form submission
- **Module providers:** Each module needs to supply a category dataset class (similar to core's `DataSets/CategoryData.php`). Existing `generate()` methods need refactoring — they should create their own listings instead of enhancing core listings
- **Backwards compatibility:** The `META_VALUE = '1'` constant should remain for reference. Tracker queries should handle both old `'1'` and new slug-based values during transition

## Success Metrics

- Generating data on one tab produces zero side effects on other tabs
- Deleting one module's demo data leaves all other modules' data intact
- Category pickers show only relevant categories when editing a typed listing
- WP-CLI `--module` flag works for all three subcommands (generate, delete, status)
- All existing unit tests pass after refactoring (with updates to match new API)

## Resolved Decisions

1. **General listing type:** General tab listings receive a `general` taxonomy term on `apd_listing_type`. This keeps tracking and deletion consistent across all tabs.
2. **Deactivated modules:** When a module is deactivated, its tab disappears. Demo data is orphaned but remains in the database. It can be cleaned up via WP-CLI (`wp apd demo delete --module=<slug> --yes`) or by reactivating the module and using the UI.
3. **User deletion:** The "Delete Users" button is blocked (disabled/hidden) as long as any module tab still has demo data. Users are always the last thing cleaned up. Message: "Delete all module demo data first."
4. **Category admin UI:** The `edit-tags.php` screen for `apd_category` gains a "Listing Type" column and a filter dropdown to filter categories by their scoped listing type.
