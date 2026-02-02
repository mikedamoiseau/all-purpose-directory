# Business Directory Plugin - Feature Analysis

**Plugin Name:** Business Directory Plugin
**Version:** 6.4.20
**Author:** Business Directory Team
**License:** GPLv2 or later
**Requirements:** WordPress 5.9+, PHP 7.4+

**Description:** A comprehensive WordPress directory plugin that enables users to create free or paid business directories, listings systems, and member/staff directories with customizable form fields, payment processing, and advanced features.

---

## Listing Management

- Submit/create new listings (frontend form)
- Edit listings (user-friendly frontend editor)
- Delete/manage listings with bulk actions
- Listing status management (published, pending, draft, trash)
- Featured/sticky listings (promoted listings)
- Listing expiration and renewal system
- Access key system for anonymous user management
- CSV import/export for bulk operations
- Listing flagging/reporting system
- Contact form on listing pages
- Comments on listings (configurable)

## Directory Pages & Views

- Main directory page (category-based listing display)
- All listings view (searchable, paginated)
- Category listing pages
- Tag-based listing pages
- Single listing detail pages
- Search results page
- Manage listings page (for users to see their listings)
- Request access keys page
- Checkout page (for paid listings)
- Flag/report listing page
- Renew listing page

## Form Fields & Customization

### Available Field Types
- Text field (single line)
- Textarea (multi-line)
- Date field
- URL field
- Phone number field
- Image field (with drag-drop support)
- Select dropdown
- Multi-select dropdown
- Radio buttons
- Checkbox
- Social media fields (Facebook, Twitter, LinkedIn)

### Field Features
- Fully customizable form fields
- Required/optional field configuration
- Field visibility controls (search, admin, frontend)
- Field association with standard posts (title, excerpt, content)
- Field ordering and management
- Field validation
- Custom field support beyond standard listings

## Payment & Monetization

### Payment Gateways
- Stripe (native support in v6.4.9+)
- PayPal
- Authorize.net
- PayFast
- Multiple gateway support

### Pricing Features
- Create multiple pricing plans/tiers
- Per-category plan assignment
- Recurring payment support
- One-time payment support
- Free listings option
- Featured/sticky listing upsells
- Test mode for payment processing
- Payment abandonment notifications
- Payment completion emails

### Currency Management
- Support for 200+ currencies
- Custom currency support
- Currency symbol positioning (left/right/none)
- Multiple currency handling

## Search & Filtering

- Quick search bar (single search box)
- Advanced search with field-level filtering
- Field-level search visibility control
- High-performance search mode (for large directories)
- Search result sorting options
- Pagination for search results
- Category and tag filtering
- Region-based filtering (with add-on)
- ZIP code/proximity search (with add-on)

## Categories & Organization

- Hierarchical categories (parent-child relationships)
- Category ordering (by name, slug, ID, or listing count)
- Empty category hiding option
- Category listing counts display
- Category-specific plan assignment
- Enhanced category images/icons (with add-on)

## Shortcodes

### Main Directory Shortcodes
| Shortcode | Description |
|-----------|-------------|
| `[businessdirectory]` | Main directory page |
| `[businessdirectory-submit-listing]` | Submit listing page |
| `[businessdirectory-manage-listings]` | User's listing management |
| `[businessdirectory-listings]` | Display listings by category/tag |
| `[businessdirectory-search]` | Advanced search form |
| `[businessdirectory-quick-search]` | Quick search box |
| `[businessdirectory-latest-listings]` | Display latest listings |
| `[businessdirectory-random-listings]` | Random listings display |
| `[businessdirectory-featured-listings]` | Featured listings display |

### Single Listing Shortcodes
| Shortcode | Description |
|-----------|-------------|
| `[businessdirectory-listing]` | Display single listing by ID/slug |
| `[businessdirectory-images]` | Listing images display |
| `[businessdirectory-socials]` | Social media links display |
| `[businessdirectory-buttons]` | Listing action buttons |
| `[businessdirectory-details]` | Listing details section |
| `[businessdirectory-section]` | Display specific listing section |

### Organization Shortcodes
| Shortcode | Description |
|-----------|-------------|
| `[businessdirectory-categories]` | Category listing |
| `[businessdirectory-listing-count]` | Display listing count |

## Widgets

- **Featured Listings Widget** - Display featured listings in sidebars
- **Latest Listings Widget** - Show recently submitted listings
- **Random Listings Widget** - Random listing display
- **Search Widget** - Quick search functionality

## Custom Post Types & Taxonomies

| Type | Name | Description |
|------|------|-------------|
| CPT | `wpbdp_listing` | Business Directory Listings |
| Taxonomy | `wpbdp_category` | Directory Categories |
| Taxonomy | `wpbdp_tag` | Directory Tags |

## Email & Notifications

### Admin Notifications
- New listing submission
- Listing edits
- Listing expiration
- Listing renewal
- Payment completion
- Listing reporting/flagging
- Contact form messages
- CC address for notifications

### User Notifications
- Listing submission confirmation
- Listing published/approved
- Payment completed
- Listing expiration/renewal warnings

### Email Configuration
- HTML, plain text, or multipart content-type
- Email template customization
- Placeholders support (listing, fee_name, listing-url, access_key, etc.)
- Custom sender options

## Spam & Security

- Google reCAPTCHA integration (v2 and v3 support)
- V3 threshold score configuration
- reCAPTCHA for:
  - Listing creation
  - Listing editing
  - Contact form submissions
  - Comments
  - Listing reporting/flagging
- Option to hide reCAPTCHA for logged-in users
- Listing flagging with customizable reasons
- Admin moderation of inappropriate listings

## User Management & Authentication

- Registration requirement for listing submission (configurable)
- Account creation during listing submission
- Anonymous listing submission support
- Access key system for anonymous listing management
- Login/registration URL customization
- Login redirect functionality
- Support for membership plugins integration
- Role-based capabilities

## Listing Expiration

- Configurable listing expiration periods
- Automatic status change on expiration
- Listing renewal process
- Renewal reminders
- Expiration notifications

## Appearance & Styling

- Built-in theme system
- Button style override (theme vs. plugin)
- Primary color customization
- Image sizing and quality control
- Thumbnail generation
- Default image settings
- Responsive design support

## Admin Features

### Dashboard & Management
- Admin menu integration
- Listings management table
- Batch operations
- Payment history tracking
- Fee/plan management
- Form field administration
- Settings pages
- CSV import/export interface
- Themes management
- Module/add-on management

### Admin Menus
- Business Directory main menu
- Listings submenu
- Categories submenu
- Form Fields submenu
- Fees/Plans submenu
- Payments submenu
- Settings submenu
- Modules/Add-ons submenu

## Sorting & Ordering

### Listing Sort Options
- By title
- By author
- By date posted
- By date modified
- Random order
- Paid listings first
- Plan custom order
- Sticky listings first

### Category Sorting
- By name
- By slug
- By listing count
- Ascending/descending order
- Hide empty categories option
- Show only parent categories option

## Administrative Settings

### Settings Groups
- **General Settings:** Permalinks, license key, spam/reCAPTCHA, registration, terms, search, advanced
- **Listings Settings:** General options, categories, contact form, reporting, sorting, messages, status defaults
- **Payment Settings:** Currency, payment gateways, test mode, fee/plan management
- **Email Settings:** Notifications, templates, content-type, email addresses
- **Appearance Settings:** Button display, styling, images, thumbnails, themes

## Advanced Features

- WPML support for multilingual sites
- Custom permalinks integration
- NavXT breadcrumbs integration
- WP PageNavi integration
- Beaver Themer compatibility
- Cornerstone/Elementor compatibility
- Advanced Excerpt plugin integration
- ACF (Advanced Custom Fields) compatibility
- Social sharing buttons integration
- SEO optimization (Yoast SEO, Rank Math, All in One SEO compatible)
- Schema.org markup support

## Premium/Add-on Features

- File Upload Module (manage attachments on listings)
- Restrictions Module (feature access control)
- ZIP Code Search Module (radius/proximity search)
- Regions Module (location-based filtering)
- Ratings and Reviews Module (Star ratings with schema markup)
- Google Maps Module (geo-location display)
- Discount Codes Module (offer discounts)
- Claim Listings Module (Allow users to claim listings)
- Enhanced Categories Module (Images/icons on categories)
- Premium Themes (7+ professionally designed themes)

## Technical Implementation

### Database Structure
- Uses WordPress custom post type system
- Custom post meta for listing data
- Custom taxonomies for categories and tags
- Flagging data stored in post meta
- Payment and fee records in custom tables

### Class Architecture
- Main WPBDP class orchestrates all modules
- Model-based architecture for listings and entities
- View pattern for display logic
- Controller pattern for business logic
- Helper classes for specific features
- Field type abstraction for form fields
- Payment gateway abstraction for payments

### Performance Features
- Query optimization for large directories
- Pagination system
- High-performance search mode
- Caching integration
- Elementor caching prevention

### Localization
- Full translation support
- 8+ language translations included
- WPML compatibility
- Translation-ready strings throughout
