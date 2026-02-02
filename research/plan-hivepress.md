# HivePress Plugin - Feature Analysis

**Plugin Name:** HivePress
**Version:** 1.7.20
**Author:** HivePress
**License:** GPLv3
**Requirements:** WordPress 5.0+, PHP 7.4+

**Description:** A multipurpose directory, listing & classifieds plugin for WordPress that provides a complete solution for building any type of directory website with customizable listings, vendors, and user management.

---

## Custom Post Types

| Post Type | Slug | Description |
|-----------|------|-------------|
| Listings | `hp_listing` | Main directory listings |
| Vendors | `hp_vendor` | Seller/business profiles |
| Emails | `hp_email` | Email templates (non-public) |
| Templates | `hp_template` | Page templates (non-public) |

## Custom Taxonomies

| Taxonomy | Slug | Description |
|----------|------|-------------|
| Listing Categories | `hp_listing_category` | Hierarchical categories for listings |
| Vendor Categories | `hp_vendor_category` | Hierarchical categories for vendors |

## Listing Management

### Listing Features
- Frontend listing submission with customizable forms
- Listing editing from frontend
- Listing management dashboard
- Listing renewal for expired listings
- Listing deletion by owners
- Listing categories organization
- Featured listings with time tracking
- Automatic listing expiration (configurable)
- Listing status management (publish, draft, pending, hidden)
- Listing verification badges
- Listing hiding/unhiding
- Listing reporting for inappropriate content
- Related listings display

### Listing Fields
- Title (max 256 characters)
- Description with HTML support
- Slug/URL
- Status (publish, future, draft, pending, private, trash)
- Featured flag
- Verified badge
- Creation/modification dates
- Expiration time
- Categories (multiple)
- Images gallery

## Vendor Management

### Vendor Features
- Vendor registration (optional)
- Public vendor profiles
- Vendor display settings
- Vendor categories
- Vendor search functionality
- Vendor attribute syncing to listings

### Vendor Fields
- Profile information
- Business details
- Contact information
- Listing portfolio

## User Authentication & Accounts

### Authentication Features
- User registration with optional terms acceptance
- User login functionality
- Password reset/recovery
- Email verification for registrations
- User online status indicators
- Display name customization options
- User profile deletion

### User Display Options
- Username
- First name only
- Full name
- Custom display name

## Search & Filtering

### Search Features
- Full-text listing search
- Listing filtering by categories
- Custom attribute filters
- Vendor search functionality
- Vendor filtering by categories
- Location-based search capability

### Sorting Options
- Configurable sort options
- Custom field sorting

## Frontend Templates (38+)

### Listing Templates
- listing-categories-view-page
- listing-category-view-block
- listing-edit-page & block
- listing-manage-page
- listing-renew-page & complete-page
- listing-submit-page, profile-page, details-page
- listing-view-page & block
- listings-view-page & edit-page

### User Templates
- user-account-page
- user-edit-settings-page
- user-email-verify-page
- user-login-page
- user-password-reset-page
- user-view-page & block

### Vendor Templates
- vendor-register-page & profile-page
- vendor-view-page & block
- vendors-view-page

### Site Templates
- site-header & footer blocks
- page templates (narrow, sidebar-left, sidebar-right, wide)

## Block System (25+ Blocks)

### Container Blocks
- Section blocks
- Container blocks

### Form Blocks
- Listing search form
- Vendor search form
- User login form
- User password reset form
- User registration form

### Display Blocks
- Listings display
- Vendors display
- Listing categories
- Attributes display
- Results and result count

### Functional Blocks
- Related listings
- Template blocks
- Modal blocks
- Menu blocks
- Toggle blocks
- Content blocks
- Callback blocks
- User profile blocks

## Custom Fields System (31 Field Types)

### Text Fields
- Text, textarea, email, password, URL, phone, number

### Date/Time Fields
- Date, time, date-range, number-range

### Selection Fields
- Select, checkbox, checkboxes, radio

### File Fields
- File upload, attachment upload, attachment select

### Special Fields
- Image size selection
- Currency fields
- Embed fields
- Hidden fields
- ID fields
- Regex validation fields
- Repeater fields
- Google OAuth button
- CAPTCHA protection

## Forms Available

### Listing Forms
- listing_submit - Submit new listings
- listing_update - Edit listings
- listing_search - Search/filter listings
- listing_filter - Advanced filtering
- listing_sort - Configure sorting
- listing_delete - Delete listings
- listing_report - Report listings

### Vendor Forms
- vendor_submit - Submit vendor profile
- vendor_update - Edit vendor profile
- vendor_search - Search vendors
- vendor_filter - Filter vendors
- vendor_sort - Sort vendors

### User Forms
- user_register - User registration
- user_login - User login
- user_update - Update user profile
- user_delete - Delete user account
- user_password_request - Request password reset
- user_password_reset - Reset password

## REST API

### Endpoints
- Full REST API support for all resources
- Base path: /wp-json/hivepress/v1
- OpenAPI documentation support
- Endpoints for listings, vendors, users

## Integrations

### Plugin Integrations
- WooCommerce compatibility
- Elementor custom widgets
- Google OAuth authentication
- MailChimp email list sync
- LiteSpeed Cache optimization
- Action Scheduler for background tasks

## Email Notifications

### Email Types
- User registration emails
- Email verification emails
- Password reset request emails
- Listing submission confirmation
- Listing approval emails
- Listing rejection emails
- Listing expiration warnings
- Listing update notifications
- Listing report emails
- Vendor registration emails

## Settings Configuration

### Listings Settings
- Display page selection
- Listings per page
- Featured listings count
- Related listings
- Search fields configuration
- Submission settings (terms, moderation, reporting)
- Expiration settings (auto-expiration, storage period)

### Vendors Settings
- Enable/disable vendor display
- Vendors page selection
- Vendors per page
- Display name options
- Search fields
- Registration settings

### Users Settings
- Profile visibility
- Online status display
- Display name format options
- Registration page settings

## Technical Architecture

### Architecture Patterns
- Component-based architecture
- Hooks API for extensibility (actions and filters)
- Model-View-Controller pattern
- Namespace structure: HivePress\Components, Models, Forms, Blocks, Controllers, Fields, Emails, Templates
- Helper functions with HivePress prefix (hp_)

### Database
- Uses WordPress custom post types and taxonomies
- Post meta for listing metadata
- Automatic user association with listings
- Vendor hierarchy support (vendors can be parent of listings)

### Security
- CAPTCHA field support
- Email verification for registrations
- Nonce validation
- Input sanitization and escaping
- Role-based access control

### Performance
- Caching component
- LiteSpeed integration
- Action Scheduler for background tasks

## Premium Extensions

### Communication
- Private Messages
- Real-time Notifications
- Notices

### Social Features
- Social Login
- Followers
- Friends
- Groups
- Social Activity

### Profile Enhancements
- User Photos
- User Bookmarks
- Verified Users
- User Reviews
- User Tags
- User Locations
- Profile Tabs
- Profile Completeness

### E-commerce
- Stripe integration
- WooCommerce integration
- myCRED points system

### Security
- Google reCAPTCHA
- Terms & Conditions

### Other Integrations
- MailChimp
- bbPress
- Private Content
