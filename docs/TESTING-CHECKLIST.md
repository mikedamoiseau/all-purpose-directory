# All Purpose Directory - Manual Testing Checklist

Use this checklist when testing the plugin before a release. Run automated tests first, then perform manual testing on supported PHP and WordPress versions.

## Automated Testing (Run First)

Before manual testing, run automated tests to catch issues early:

```bash
# PHP Linting (no syntax errors)
composer lint

# WordPress Coding Standards
composer phpcs

# Unit Tests (2,419 tests)
composer test:unit

# Integration Tests (requires Docker)
composer test:integration

# E2E Tests (requires Docker + npm)
npm run test:e2e
```

**Current Status:**
- [ ] PHP linting passes
- [ ] PHPCS passes (0 errors, 0 warnings)
- [ ] Unit tests pass (2,419 tests)
- [ ] Integration tests pass
- [ ] E2E tests pass

---

## Test Environment Matrix

| PHP Version | WordPress Version | Theme | Status |
|-------------|-------------------|-------|--------|
| PHP 8.0 | WP 6.0 (minimum) | Twenty Twenty-Three | [ ] |
| PHP 8.1 | WP 6.4 | Twenty Twenty-Four | [ ] |
| PHP 8.2 | WP 6.6 | Twenty Twenty-Four | [ ] |
| PHP 8.3 | WP 6.7 (latest) | Twenty Twenty-Four | [ ] |

## Pre-Testing Setup

- [ ] Fresh WordPress installation
- [ ] Plugin activated successfully
- [ ] Demo data generated (Listings → Demo Data)
- [ ] WP_DEBUG enabled in wp-config.php
- [ ] No PHP errors in debug.log
- [ ] No JavaScript console errors

---

## 1. Plugin Activation/Deactivation

### Activation
- [ ] Plugin activates without errors
- [ ] Default options are set (`apd_options` in wp_options)
- [ ] Rewrite rules are flushed (permalinks work)
- [ ] Menu item appears under "Listings"
- [ ] Submenus appear: All Listings, Add New, Categories, Tags, Reviews, Settings, Demo Data, Modules

### Deactivation
- [ ] Plugin deactivates cleanly
- [ ] No PHP errors during deactivation

### Uninstall (with delete_data enabled)
- [ ] All listings deleted
- [ ] All categories/tags deleted
- [ ] All reviews deleted
- [ ] All plugin options removed
- [ ] Custom capabilities removed

---

## 2. Demo Data Generator

### Generate Demo Data
- [ ] Demo Data page loads (Listings → Demo Data)
- [ ] Can configure quantities (users, listings, reviews)
- [ ] Generate button starts AJAX generation
- [ ] Progress displays during generation
- [ ] Success message shows counts
- [ ] Data is created:
  - [ ] Users with demo meta
  - [ ] Categories with icons/colors
  - [ ] Tags
  - [ ] Listings with varied content
  - [ ] Reviews with ratings
  - [ ] Inquiries
  - [ ] Favorites

### Delete Demo Data
- [ ] Delete button appears after generation
- [ ] Confirmation dialog works
- [ ] Deletion removes only demo data
- [ ] Real content is preserved
- [ ] Counts reset to zero

---

## 3. Listings Management

### Create Listing (Admin)
- [ ] Add New Listing works
- [ ] Title field saves
- [ ] Content editor works (Classic + Block)
- [ ] Excerpt field works
- [ ] Featured image can be set
- [ ] Categories can be assigned (hierarchical)
- [ ] Tags can be assigned
- [ ] Custom fields render in meta box
- [ ] All field types work:
  - [ ] Text, Textarea, Rich Text
  - [ ] Number, Decimal, Currency
  - [ ] Email, URL, Phone
  - [ ] Date, Time, DateTime, Date Range
  - [ ] Select, Multi-select
  - [ ] Checkbox, Checkbox Group, Radio, Switch
  - [ ] File upload, Image upload, Gallery
  - [ ] Color picker, Hidden
- [ ] Publish works
- [ ] Save as Draft works
- [ ] Set to Pending works

### Edit Listing (Admin)
- [ ] All fields pre-populate correctly
- [ ] Updates save correctly
- [ ] Featured image can be changed/removed
- [ ] Categories/tags can be modified
- [ ] Field values persist after save

### Admin List View
- [ ] Custom columns display (thumbnail, category, status, views)
- [ ] Columns are sortable (title, date, views)
- [ ] Category dropdown filter works
- [ ] Status filter works (All, Published, Pending, Draft)
- [ ] Search by title/content works
- [ ] Bulk actions work (Edit, Trash, Restore, Delete)
- [ ] Row actions work (Edit, Quick Edit, Trash, View)

---

## 4. Categories & Tags

### Categories
- [ ] Can create category
- [ ] Can set parent category (hierarchical)
- [ ] Icon selector shows dashicons
- [ ] Color picker works
- [ ] Description field works
- [ ] Custom columns show icon/color
- [ ] Category edit screen works
- [ ] Category displays on listing cards
- [ ] Category archive page works (`/listing-category/slug/`)

### Tags
- [ ] Can create tag
- [ ] Tags display on listings
- [ ] Tag archive page works (`/listing-tag/slug/`)

---

## 5. Frontend Display

### Archive Page (`/listings/`)
- [ ] Grid view displays correctly
- [ ] List view displays correctly
- [ ] View switcher toggles views
- [ ] View preference saved (cookie/session)
- [ ] Pagination works
- [ ] Results count displays ("Showing X of Y listings")
- [ ] Empty state when no listings
- [ ] Responsive on mobile (< 768px)
- [ ] Responsive on tablet (768-1024px)

### Single Listing Page
- [ ] Title displays
- [ ] Featured image displays (not lazy loaded - above fold)
- [ ] Content displays
- [ ] Custom fields display in sidebar
- [ ] Categories display with icons/colors
- [ ] Tags display as links
- [ ] Author box displays (name, avatar, listing count)
- [ ] View count increments (once per session)
- [ ] View count skips bots
- [ ] Related listings display (same category)
- [ ] Reviews section displays
- [ ] Contact form displays
- [ ] Responsive layout works

### Listing Cards
- [ ] Thumbnail displays with lazy loading
- [ ] Title links to listing
- [ ] Excerpt displays (if enabled)
- [ ] Category badge with icon/color
- [ ] Rating stars display (if reviews exist)
- [ ] Favorite button displays (heart icon)
- [ ] Favorite button toggles state
- [ ] Hover effects work

---

## 6. Search & Filtering

### Search Form
- [ ] Keyword search returns results
- [ ] Category dropdown filter works
- [ ] Tag checkbox filter works
- [ ] Range filter works (min/max inputs)
- [ ] Date range filter works
- [ ] Submit redirects to archive with params

### AJAX Filtering
- [ ] Results update without page reload
- [ ] Loading indicator shows
- [ ] URL updates with filter state (History API)
- [ ] Browser back/forward works
- [ ] Clear filters button works
- [ ] Active filters display with remove buttons
- [ ] No results message when empty

### Sort Options
- [ ] Sort by date (newest first)
- [ ] Sort by date (oldest first)
- [ ] Sort by title (A-Z)
- [ ] Sort by title (Z-A)
- [ ] Sort by views (most viewed)
- [ ] Sort by random
- [ ] Sort preference in URL

---

## 7. Frontend Submission

### Submission Form (`[apd_submission_form]`)
- [ ] Form renders on page with shortcode
- [ ] All configured fields display
- [ ] Required fields marked with asterisk
- [ ] Category selector (hierarchical dropdown)
- [ ] Tag selector (checkboxes or input)
- [ ] Featured image upload via media library
- [ ] Terms checkbox (if configured)
- [ ] Client-side validation works

### Form Submission
- [ ] Logged-in user can submit
- [ ] Guest can submit (if enabled in settings)
- [ ] Validation errors display inline
- [ ] Server-side validation works
- [ ] Success redirect works
- [ ] Listing created with correct status (pending/publish)
- [ ] Admin notification email sent
- [ ] Spam protection:
  - [ ] Honeypot field blocks bots
  - [ ] Rate limiting (5/hour) works
  - [ ] Time-based check (3 sec minimum) works

### Edit Listing (Frontend)
- [ ] Edit link in dashboard works
- [ ] URL parameter: `?edit_listing=123`
- [ ] Form pre-populates with existing data
- [ ] Featured image shows preview
- [ ] Updates save correctly
- [ ] Only author can edit (or admin)
- [ ] Unauthorized users see error

---

## 8. User Dashboard

### Dashboard Access (`[apd_dashboard]`)
- [ ] Dashboard renders on page with shortcode
- [ ] Redirects to login if not logged in
- [ ] Shows login-required message for guests
- [ ] Navigation tabs display

### My Listings Tab
- [ ] Lists current user's listings
- [ ] Shows stats (views, favorites, inquiries)
- [ ] Status badges display (Published, Pending, Draft)
- [ ] Edit action links to frontend form
- [ ] Delete action with confirmation
- [ ] Pagination works (12 per page)
- [ ] Status filter dropdown works
- [ ] Empty state when no listings

### Favorites Tab
- [ ] Lists favorited listings
- [ ] Grid/List view toggle
- [ ] Pagination works
- [ ] Empty state with browse link
- [ ] Remove favorite works
- [ ] Count badge updates

### Profile Tab
- [ ] Display name field
- [ ] Email field (read-only or editable based on config)
- [ ] Avatar upload via media library
- [ ] Bio/description textarea
- [ ] Phone number field
- [ ] Website URL field
- [ ] Social links (Facebook, Twitter, LinkedIn, Instagram)
- [ ] Save changes works
- [ ] Validation errors display

---

## 9. Favorites System

### Adding/Removing Favorites
- [ ] Heart button on listing cards
- [ ] Heart button on single listing
- [ ] Click toggles filled/outline state
- [ ] Optimistic UI update (immediate)
- [ ] AJAX request succeeds
- [ ] Listing favorite count updates
- [ ] Works when logged in

### Guest Favorites (if enabled)
- [ ] Works without login
- [ ] Stored in cookie (30-day expiry)
- [ ] Merged to user meta on login
- [ ] Login prompt when required

---

## 10. Reviews & Ratings

### Review Form
- [ ] Form appears below listing content
- [ ] Star rating input (1-5 stars, interactive)
- [ ] Review title field
- [ ] Review content textarea
- [ ] Minimum content length enforced
- [ ] Submit creates review
- [ ] One review per user per listing
- [ ] Edit own review (form pre-fills)
- [ ] Login required message for guests

### Review Display
- [ ] Reviews list with pagination
- [ ] Star rating displays (filled stars)
- [ ] Author name and avatar
- [ ] Review date
- [ ] Review title and content
- [ ] Rating summary (average, breakdown)
- [ ] Empty state when no reviews

### Review Moderation (Admin)
- [ ] Reviews menu shows pending count badge
- [ ] Reviews list table
- [ ] Status tabs (All, Pending, Approved, Spam, Trash)
- [ ] Approve action works
- [ ] Reject/Unapprove action works
- [ ] Spam action works
- [ ] Trash action works
- [ ] Bulk actions work
- [ ] Filters: listing dropdown, rating dropdown, search
- [ ] View listing link in row actions

---

## 11. Contact Form

### Contact Form Display
- [ ] Form appears in single listing sidebar
- [ ] Fields: Name, Email, Phone (optional), Subject, Message
- [ ] Required fields marked
- [ ] Minimum message length hint

### Form Submission
- [ ] Client-side validation
- [ ] Server-side validation
- [ ] Nonce verification
- [ ] Email sent to listing owner
- [ ] Admin copy sent (if enabled)
- [ ] Success message via AJAX
- [ ] Error messages display

### Inquiry Tracking (if enabled)
- [ ] Inquiry saved as `apd_inquiry` post
- [ ] Shows in listing owner's dashboard
- [ ] Inquiry count in stats
- [ ] Mark as read/unread
- [ ] Delete inquiry

---

## 12. Email Notifications

### Test Each Email Type
- [ ] **New Listing Submitted** (to admin) - on pending submission
- [ ] **Listing Approved** (to author) - on status change to publish
- [ ] **Listing Rejected** (to author) - with rejection reason
- [ ] **Listing Expiring Soon** (to author) - X days before expiry
- [ ] **Listing Expired** (to author) - on expiry
- [ ] **New Review** (to listing author) - on approved review
- [ ] **New Inquiry** (to listing author) - on contact form submit

### Email Formatting
- [ ] HTML template renders (check email client)
- [ ] Placeholders replaced ({site_name}, {listing_title}, etc.)
- [ ] Links work and point to correct URLs
- [ ] Button styling works
- [ ] Mobile-friendly layout
- [ ] From name/email correct

---

## 13. Admin Settings

### General Tab
- [ ] Currency symbol saves and displays
- [ ] Currency position (before/after) works
- [ ] Date format saves
- [ ] Distance unit (km/miles) saves

### Listings Tab
- [ ] Listings per page (affects frontend)
- [ ] Default listing status (pending/publish/draft)
- [ ] Expiration days (0 = never)
- [ ] Enable/disable reviews
- [ ] Enable/disable favorites
- [ ] Enable/disable contact form

### Submission Tab
- [ ] Who can submit (anyone/logged-in/roles)
- [ ] Allow guest submission
- [ ] Terms & conditions page selector
- [ ] Redirect after submission (listing/dashboard/custom)

### Display Tab
- [ ] Default view (grid/list)
- [ ] Grid columns (2/3/4)
- [ ] Show/hide card elements (thumbnail, excerpt, category, rating, favorite)
- [ ] Archive page title
- [ ] Single listing layout (full/sidebar)

### Email Tab
- [ ] From name
- [ ] From email (validates email format)
- [ ] Admin email(s) for notifications
- [ ] Toggle each notification type

### Advanced Tab
- [ ] Delete data on uninstall (checkbox)
- [ ] Custom CSS (applies to frontend)
- [ ] Debug mode (enables logging)

---

## 14. Shortcodes

### `[apd_listings]`
- [ ] Basic usage displays listings
- [ ] `view="grid"` / `view="list"`
- [ ] `columns="2"` / `columns="3"` / `columns="4"`
- [ ] `count="10"` limits results
- [ ] `category="slug"` filters by category
- [ ] `tag="slug"` filters by tag
- [ ] `orderby="date"` / `orderby="title"` / `orderby="views"`
- [ ] `order="ASC"` / `order="DESC"`
- [ ] `show_pagination="true"` / `show_pagination="false"`

### `[apd_search_form]`
- [ ] Renders search form
- [ ] `show_keyword="true"`
- [ ] `show_category="true"`
- [ ] `filters="keyword,category,tag"`
- [ ] `layout="horizontal"` / `layout="vertical"`

### `[apd_categories]`
- [ ] Displays category list/grid
- [ ] `layout="list"` / `layout="grid"`
- [ ] `columns="3"`
- [ ] `show_count="true"`
- [ ] `show_icon="true"`
- [ ] `hide_empty="true"`

### Other Shortcodes
- [ ] `[apd_submission_form]` - tested in Section 7
- [ ] `[apd_dashboard]` - tested in Section 8
- [ ] `[apd_favorites]` - displays favorites (alias for dashboard favorites)
- [ ] `[apd_login_form]` - WordPress login form
- [ ] `[apd_register_form]` - WordPress registration form

---

## 15. Gutenberg Blocks

### Listings Block
- [ ] Block inserts from inserter
- [ ] Block preview in editor
- [ ] Settings panel: view, columns, count, category, orderby
- [ ] Frontend renders correctly

### Search Form Block
- [ ] Block inserts
- [ ] Settings: filters to show, layout
- [ ] Frontend form works

### Categories Block
- [ ] Block inserts
- [ ] Settings: layout, columns, show count/icon
- [ ] Frontend renders correctly

---

## 16. REST API

### Public Endpoints
- [ ] `GET /wp-json/apd/v1/listings` - returns listings
- [ ] `GET /wp-json/apd/v1/listings/{id}` - returns single listing
- [ ] `GET /wp-json/apd/v1/categories` - returns categories
- [ ] `GET /wp-json/apd/v1/tags` - returns tags
- [ ] `GET /wp-json/apd/v1/reviews` - returns approved reviews
- [ ] `GET /wp-json/apd/v1/listings/{id}/reviews` - listing reviews with rating summary

### Authenticated Endpoints
- [ ] `GET /wp-json/apd/v1/favorites` - requires auth
- [ ] `POST /wp-json/apd/v1/favorites` - add favorite
- [ ] `DELETE /wp-json/apd/v1/favorites/{id}` - remove favorite
- [ ] `POST /wp-json/apd/v1/listings` - create listing
- [ ] `PUT /wp-json/apd/v1/listings/{id}` - update (owner only)
- [ ] `DELETE /wp-json/apd/v1/listings/{id}` - delete (owner only)

### Response Format
- [ ] Correct JSON structure
- [ ] Pagination headers (X-WP-Total, X-WP-TotalPages)
- [ ] Error responses include code and message

---

## 17. Modules Admin Page

### Modules Page
- [ ] Modules page loads (Listings → Modules)
- [ ] Shows "No modules installed" when empty
- [ ] Module registration API works
- [ ] Module displays name, description, version, author
- [ ] Module icon displays
- [ ] Feature badges display

---

## 18. Theme Compatibility

### Twenty Twenty-Four
- [ ] Archive page layout
- [ ] Single listing layout
- [ ] Dashboard styling
- [ ] Forms styling
- [ ] No CSS conflicts

### Twenty Twenty-Three
- [ ] Archive page layout
- [ ] Single listing layout
- [ ] Dashboard styling
- [ ] Forms styling
- [ ] No CSS conflicts

### Other Themes (optional)
- [ ] Astra
- [ ] GeneratePress
- [ ] Flavor (if applicable)

### Check For
- [ ] Layout breaks
- [ ] CSS conflicts (inspect element)
- [ ] JavaScript errors (console)
- [ ] Responsive issues

---

## 19. Plugin Compatibility

### Yoast SEO
- [ ] No PHP conflicts
- [ ] SEO meta boxes appear
- [ ] Sitemap includes listings
- [ ] Breadcrumbs work

### WooCommerce (if installed)
- [ ] No conflicts on admin pages
- [ ] No conflicts on frontend
- [ ] Scripts don't conflict

### Caching Plugins
- [ ] WP Super Cache - AJAX works
- [ ] W3 Total Cache - AJAX works
- [ ] LiteSpeed Cache - AJAX works

---

## 20. Performance

### Page Load
- [ ] Archive page < 3 seconds
- [ ] Single listing < 2 seconds
- [ ] Dashboard < 2 seconds

### Database Queries
- [ ] Query Monitor shows no slow queries (> 0.05s)
- [ ] No duplicate queries
- [ ] Caching working (transients used)

### Assets
- [ ] CSS only loads on relevant pages
- [ ] JS only loads on relevant pages
- [ ] Images use lazy loading
- [ ] No render-blocking issues

---

## 21. Security

### Form Security
- [ ] All forms have nonce fields
- [ ] Nonce verification on submission
- [ ] CSRF protection working

### Input/Output
- [ ] All input sanitized
- [ ] All output escaped
- [ ] File uploads validated (type, size)

### Capabilities
- [ ] Only admins see admin pages
- [ ] Only authors can edit own listings
- [ ] Review moderation requires capability

---

## 22. Internationalization

### Translation Ready
- [ ] All strings use `__()` or `_e()`
- [ ] Text domain is `all-purpose-directory`
- [ ] POT file current (`languages/all-purpose-directory.pot`)
- [ ] Placeholders have translator comments
- [ ] No hardcoded English strings

---

## Issue Tracking

| # | Description | Severity | File/Location | Status |
|---|-------------|----------|---------------|--------|
| 1 | | | | |
| 2 | | | | |
| 3 | | | | |

**Severity:** Critical / High / Medium / Low

---

## Sign-Off

| Field | Value |
|-------|-------|
| **Tested By** | |
| **Date** | |
| **PHP Version** | |
| **WordPress Version** | |
| **Theme** | |
| **Browser** | |

**Automated Tests:** [ ] All Pass [ ] Failures (list in issues)

**Manual Tests:** [ ] All Pass [ ] Issues Found

**Overall Result:** [ ] APPROVED FOR RELEASE [ ] NEEDS FIXES

**Notes:**


