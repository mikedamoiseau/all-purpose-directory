# Demo Data Redesign - Implementation Phases

## Decisions Made

1. **Category scoping**: Focus on demo data page + generation only. Defer admin/frontend category filtering to follow-up.
2. **Existing demo data**: No backward compat for old `'1'` tracking. Users delete and regenerate.
3. **Meta key bug**: Fix `_apd_icon`/`_apd_color` → `_apd_category_icon`/`_apd_category_color` in this feature.
4. **Interface changes**: New `DemoDataModuleProviderInterface` extending existing interface (Option A).
5. **General tab**: Includes reviews, inquiries, favorites checkboxes. Users section is shared above tabs.
6. **Favorites tracking**: Track which module's listings each favorite references for per-module cleanup.
7. **Implementation**: Phased approach.

## Implementation Phases

### Phase A: Tracker & Generator Changes (Backend Foundation)
- [ ] `DemoDataTracker` - Change meta value from `'1'` to module slug
- [ ] Add `$module` parameter to mark methods and query methods
- [ ] Add `delete_by_module($module)` method
- [ ] Add `count_by_module($module)` method
- [ ] `DemoDataGenerator` - Accept `$module` and `$listing_type` parameters
- [ ] `DemoDataGenerator` - Accept custom category dataset
- [ ] `DemoDataGenerator` - Set `_apd_listing_type` term meta on generated categories
- [ ] `DemoDataGenerator` - Set `apd_listing_type` taxonomy term on generated listings
- [ ] Fix meta key bug: `_apd_icon`/`_apd_color` → `_apd_category_icon`/`_apd_category_color`
- [ ] New `DemoDataModuleProviderInterface` with `get_category_data()` method
- [ ] Update helper functions in `demo-data-functions.php`
- [ ] Update unit tests

### Phase B: Admin Page UI (Tabbed Interface)
- [ ] Shared Users section above tab bar (own generate/delete)
- [ ] Tab bar: General tab + one per active module provider
- [ ] Per-tab content: status counts, generation form, Generate/Delete buttons
- [ ] AJAX handlers: `apd_generate_demo` accepts `module` parameter
- [ ] AJAX handlers: `apd_delete_demo` accepts `module` parameter
- [ ] Per-tab progress indicators and result messages
- [ ] URL hash state for active tab
- [ ] CSS: tab styles matching Settings page pattern
- [ ] JS: complete rewrite for tabbed AJAX

### Phase C: WP-CLI Updates
- [ ] `--module=<slug>` flag for `generate` command
- [ ] `--module=<slug>` flag for `delete` command
- [ ] `status` shows counts grouped by module
- [ ] `status --module=<slug>` for single module
- [ ] Invalid module slug error handling

### Phase D: Category Admin UI (DEFERRED - Follow-up)
- [ ] `_apd_listing_type` column on edit-tags.php for apd_category
- [ ] Filter dropdown to filter categories by listing type
- [ ] Category picker filtering on listing edit screen
- [ ] Frontend search category filter respects listing type
- [ ] REST API category endpoint includes listing_type

## PRD Reference
See `.claude-bw/planning/prd/prd-demo-data-redesign.md`
