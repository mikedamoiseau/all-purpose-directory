# All Purpose Directory - Manual Testing Checklist

Use this checklist when testing the plugin before a release. Each section should be tested on the supported PHP and WordPress versions.

## Test Environment Matrix

| PHP Version | WordPress Version | Status |
|-------------|-------------------|--------|
| PHP 8.0 | WP 6.0 | [ ] |
| PHP 8.0 | WP 6.4 | [ ] |
| PHP 8.1 | WP 6.4 | [ ] |
| PHP 8.2 | WP 6.4 | [ ] |
| PHP 8.2 | WP Latest | [ ] |
| PHP 8.3 | WP Latest | [ ] |

## Pre-Testing Setup

- [ ] Fresh WordPress installation
- [ ] Plugin activated successfully
- [ ] No PHP errors in debug.log
- [ ] No JavaScript console errors

---

## 1. Plugin Activation/Deactivation

### Activation
- [ ] Plugin activates without errors
- [ ] Default options are set
- [ ] Rewrite rules are flushed
- [ ] Admin notices work (if any)
- [ ] Menu item appears (Listings)

### Deactivation
- [ ] Plugin deactivates cleanly
- [ ] No orphaned database entries (on uninstall with delete_data option)
- [ ] Rewrite rules are flushed

---

## 2. Listings Management

### Create Listing (Admin)
- [ ] Add New Listing menu works
- [ ] Title field works
- [ ] Content editor works
- [ ] Excerpt field works
- [ ] Featured image can be set
- [ ] Categories can be assigned
- [ ] Tags can be assigned
- [ ] Custom fields render correctly
- [ ] All field types work:
  - [ ] Text
  - [ ] Textarea
  - [ ] Number
  - [ ] Email
  - [ ] URL
  - [ ] Phone
  - [ ] Date
  - [ ] Select
  - [ ] Checkbox
  - [ ] Radio
  - [ ] File upload
  - [ ] Image upload
  - [ ] Gallery
- [ ] Publish works
- [ ] Save as Draft works
- [ ] Set to Pending works

### Edit Listing (Admin)
- [ ] All fields pre-populate correctly
- [ ] Updates save correctly
- [ ] Featured image can be changed
- [ ] Categories/tags can be modified

### Admin List View
- [ ] Custom columns display (thumbnail, category, status, views)
- [ ] Columns are sortable
- [ ] Category filter works
- [ ] Status filter works
- [ ] Search works
- [ ] Bulk actions work
- [ ] Row actions work (Edit, Trash, View)

---

## 3. Categories & Tags

### Categories
- [ ] Can create category
- [ ] Can set parent category
- [ ] Icon selector works
- [ ] Color picker works
- [ ] Category displays on listings
- [ ] Category archive page works

### Tags
- [ ] Can create tag
- [ ] Tags display on listings
- [ ] Tag archive page works

---

## 4. Frontend Display

### Archive Page
- [ ] Grid view displays correctly
- [ ] List view displays correctly
- [ ] View switcher works
- [ ] Pagination works
- [ ] Results count displays
- [ ] Responsive on mobile
- [ ] Responsive on tablet

### Single Listing Page
- [ ] Title displays
- [ ] Featured image displays
- [ ] Content displays
- [ ] Custom fields display
- [ ] Categories display with icons/colors
- [ ] Tags display
- [ ] Author info displays
- [ ] View count increments
- [ ] Related listings display
- [ ] Responsive layout works

### Listing Cards
- [ ] Thumbnail displays
- [ ] Title links to listing
- [ ] Excerpt displays (if enabled)
- [ ] Category badge displays
- [ ] Rating stars display (if reviews exist)
- [ ] Favorite button displays
- [ ] Hover effects work

---

## 5. Search & Filtering

### Search Form
- [ ] Keyword search works
- [ ] Category filter works
- [ ] Tag filter works
- [ ] Range filter works (if configured)
- [ ] Date range filter works (if configured)
- [ ] Form submits correctly

### AJAX Filtering
- [ ] Results update without page reload
- [ ] Loading indicator shows
- [ ] URL updates with filter state
- [ ] Browser back/forward works
- [ ] Clear filters works
- [ ] Active filters display

### Sort Options
- [ ] Sort by date (newest)
- [ ] Sort by date (oldest)
- [ ] Sort by title (A-Z)
- [ ] Sort by title (Z-A)
- [ ] Sort by views
- [ ] Sort by random

---

## 6. Frontend Submission

### Submission Form
- [ ] Form renders correctly
- [ ] All fields display
- [ ] Required fields are marked
- [ ] Field groups work (if configured)
- [ ] Category selector works
- [ ] Tag selector works
- [ ] Image upload works
- [ ] Terms checkbox works (if configured)
- [ ] Client-side validation works

### Form Submission
- [ ] Logged-in submission works
- [ ] Guest submission works (if enabled)
- [ ] Validation errors display
- [ ] Success redirect works
- [ ] Listing is created with correct status
- [ ] Admin notification is sent
- [ ] Spam protection works:
  - [ ] Honeypot blocks bots
  - [ ] Rate limiting works
  - [ ] Time-based check works

### Edit Listing (Frontend)
- [ ] Form pre-populates with existing data
- [ ] Updates save correctly
- [ ] Permission check works (only author can edit)

---

## 7. User Dashboard

### Dashboard Access
- [ ] Dashboard page renders
- [ ] Requires login
- [ ] Navigation tabs work

### My Listings Tab
- [ ] Lists user's listings
- [ ] Shows correct stats (views, favorites)
- [ ] Status badges display
- [ ] Edit action works
- [ ] Delete action works
- [ ] Pagination works
- [ ] Status filter works

### Favorites Tab
- [ ] Lists favorited listings
- [ ] View toggle works
- [ ] Pagination works
- [ ] Empty state displays correctly
- [ ] Remove favorite works

### Profile Tab
- [ ] Display name field works
- [ ] Email field works
- [ ] Avatar upload works
- [ ] Bio field works
- [ ] Phone field works
- [ ] Social links work
- [ ] Save changes works

---

## 8. Favorites System

### Adding Favorites
- [ ] Heart button appears on listings
- [ ] Click toggles favorite state
- [ ] Visual feedback is immediate
- [ ] AJAX call succeeds
- [ ] Favorite count updates

### Guest Favorites
- [ ] Works without login (if enabled)
- [ ] Stored in cookie
- [ ] Merged on login

### Favorites Page
- [ ] Shows all favorites
- [ ] Links to listings work

---

## 9. Reviews & Ratings

### Review Form
- [ ] Form appears on single listing
- [ ] Star rating input works
- [ ] Title field works
- [ ] Content field works
- [ ] Submit creates review
- [ ] One review per user enforced
- [ ] Edit own review works

### Review Display
- [ ] Reviews list on single listing
- [ ] Star rating displays
- [ ] Author name displays
- [ ] Date displays
- [ ] Content displays
- [ ] Pagination works
- [ ] Average rating summary displays

### Review Moderation (Admin)
- [ ] Reviews menu item shows count
- [ ] Pending reviews list
- [ ] Approve action works
- [ ] Reject action works
- [ ] Spam action works
- [ ] Trash action works
- [ ] Bulk actions work
- [ ] Filters work (status, listing, rating)

---

## 10. Contact Form

### Contact Form Display
- [ ] Form appears on single listing
- [ ] All fields display
- [ ] Required fields marked
- [ ] Submit button works

### Form Submission
- [ ] Validation works
- [ ] Email sent to listing owner
- [ ] Admin copy sent (if enabled)
- [ ] Success message displays
- [ ] Inquiry logged (if enabled)

### Inquiry Dashboard
- [ ] Inquiry count shows for owner
- [ ] Inquiry list displays
- [ ] Mark as read works

---

## 11. Email Notifications

### Test Each Email Type
- [ ] New listing submitted (to admin)
- [ ] Listing approved (to author)
- [ ] Listing rejected (to author)
- [ ] New review (to listing author)
- [ ] New inquiry (to listing author)

### Email Formatting
- [ ] HTML template renders correctly
- [ ] Placeholders are replaced
- [ ] Links work
- [ ] Mobile-friendly layout

---

## 12. Admin Settings

### General Tab
- [ ] Currency symbol saves
- [ ] Currency position saves
- [ ] Date format saves
- [ ] Distance unit saves

### Listings Tab
- [ ] Listings per page saves
- [ ] Default status saves
- [ ] Expiration days saves
- [ ] Feature toggles work (reviews, favorites, contact)

### Submission Tab
- [ ] Who can submit saves
- [ ] Guest submission toggle works
- [ ] Terms page selector works
- [ ] Redirect option saves

### Display Tab
- [ ] Default view saves
- [ ] Grid columns saves
- [ ] Card element toggles work
- [ ] Archive title saves
- [ ] Single layout saves

### Email Tab
- [ ] From name saves
- [ ] From email saves
- [ ] Admin email saves
- [ ] Notification toggles work

### Advanced Tab
- [ ] Delete data option saves
- [ ] Custom CSS saves and applies
- [ ] Debug mode works

---

## 13. Shortcodes

### Test Each Shortcode
- [ ] `[apd_listings]` with various attributes
- [ ] `[apd_search_form]` with various attributes
- [ ] `[apd_categories]` with various attributes
- [ ] `[apd_submission_form]`
- [ ] `[apd_dashboard]`
- [ ] `[apd_favorites]`
- [ ] `[apd_login_form]`
- [ ] `[apd_register_form]`

---

## 14. Gutenberg Blocks

### Test Each Block
- [ ] Listings block inserts
- [ ] Listings block preview works
- [ ] Listings block settings work
- [ ] Search Form block inserts
- [ ] Search Form block settings work
- [ ] Categories block inserts
- [ ] Categories block settings work

---

## 15. REST API

### Test Endpoints
- [ ] GET /listings returns data
- [ ] GET /listings/{id} returns single listing
- [ ] GET /categories returns data
- [ ] GET /favorites requires auth
- [ ] GET /reviews returns data
- [ ] Authentication works correctly

---

## 16. Theme Compatibility

### Test with Themes
- [ ] Twenty Twenty-Four
- [ ] Twenty Twenty-Three
- [ ] Astra (popular theme)
- [ ] GeneratePress (popular theme)

### Check For
- [ ] Layout issues
- [ ] CSS conflicts
- [ ] JavaScript conflicts
- [ ] Responsive issues

---

## 17. Plugin Compatibility

### Test with Plugins
- [ ] Yoast SEO - No conflicts
- [ ] WooCommerce - No conflicts (if installed)
- [ ] Contact Form 7 - No conflicts
- [ ] Elementor - Shortcodes work
- [ ] Popular caching plugins

---

## 18. Performance

### Check Performance
- [ ] Page load time < 3 seconds
- [ ] No memory leaks
- [ ] Database queries optimized
- [ ] Assets load conditionally
- [ ] Caching works correctly

---

## 19. Security

### Verify Security
- [ ] Nonce verification on all forms
- [ ] Capability checks on actions
- [ ] Input sanitization
- [ ] Output escaping
- [ ] No SQL injection vulnerabilities
- [ ] No XSS vulnerabilities

---

## 20. Internationalization

### Translation Ready
- [ ] All strings translatable
- [ ] POT file is current
- [ ] No hardcoded strings
- [ ] RTL layout works (if applicable)

---

## Issue Tracking

| Issue # | Description | Severity | Status |
|---------|-------------|----------|--------|
| | | | |
| | | | |
| | | | |

## Sign-Off

**Tested By:** _________________
**Date:** _________________
**PHP Version:** _________________
**WordPress Version:** _________________
**Browser:** _________________

**Result:** [ ] PASS [ ] FAIL

**Notes:**
