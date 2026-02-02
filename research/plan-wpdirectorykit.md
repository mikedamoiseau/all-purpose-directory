# WP Directory Kit Plugin - Feature Analysis

**Plugin Name:** WP Directory Kit
**Version:** 1.5.0
**Author:** wpdirectorykit.com (SWIT Sandi Winter IT)
**License:** GPL-2.0+
**Requirements:** WordPress 5.0+, PHP 7.0+, Elementor compatibility

**Description:** Build your Directory portal with demos for Real Estate Agencies and Car Dealership included. A comprehensive directory solution with Elementor integration and extensive customization options.

---

## Custom Post Type

| Post Type | Slug | Description |
|-----------|------|-------------|
| WDK-Listing | `wdk-listing` | Main directory listings |

### Post Type Features
- Supports: title, editor, excerpt, author, thumbnail, custom-fields, elementor
- Public, queryable, shows in admin menu
- Enabled in REST API

## Custom User Roles & Capabilities

### User Roles
- **Agent Role:** Basic read capability, edit own listings, edit own profile, upload files
- **Listing Admin Role:** Manage listings and perform agent functions
- **Administrator Role:** Full capability including `wdk_listings_manage`

### Custom Capabilities
- `edit_own_listings`
- `edit_own_profile`
- `upload_files`
- `wdk_listings_manage`

## Database Models

### Core Models
- **Listing_m** - Main listing data
- **Listingfield_m** - Field values for listings
- **Category_m** - Listing categories
- **Location_m** - Geographical locations
- **Field_m** - Custom field definitions
- **Messages_m** - User messaging system
- **User_m** - Extended user data
- **Editlog_m** - Track listing edits
- **Dependfields_m** - Field dependencies
- **Cachedusers_m** - User caching
- **Token_m** - Token management
- **Searchform_m** - Saved search configurations
- **Resultitem_m** - Search result layouts

## Admin Menu Structure

### Main Menu: Directory Kit
- Listings
- Add Listing
- Fields
- Categories
- Locations
- Search Form
- Result Card
- Change Currency
- Messages
- Settings
- Documentation

### Addon Pages
- Currencies Addon
- Membership Addon
- Booking Addon
- More Addons

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[wdk_listing_field_value]` | Display specific listing field value |
| `[wdk_listing_field_value_text]` | Display field value as text |
| `[wdk_listing_field_value_suffix]` | Field value with suffix |
| `[wdk_listing_field_value_prefix]` | Field value with prefix |
| `[wdk_listing_field_label]` | Display field label |
| `[wdk_listing_fields_section]` | Display grouped fields |
| `[wdk_return_post_id]` | Return current post ID |
| `[latest_listings]` | Display latest listings list |

## Elementor Elements (40+)

### Search & Results
- Listing Results Display
- Search Form
- Search Popup
- Last Search

### Listing Display
- Listing Carousel
- Listings List
- Listings Carousel
- Listing Slider
- Listing Sliders (with gallery)
- Listing Grid Images
- Listing More Grid Images
- Related Listings
- Similar Listings
- Related Listings Table

### Categories
- Categories Carousel
- Categories Grid
- Categories Grid Cover
- Categories List
- Categories Tree
- Categories Tree Top

### Locations
- Locations Carousel
- Locations Grid
- Locations Grid Cover
- Locations List
- Locations Tree

### Listing Details
- Listing Field Value
- Listing Field Label
- Listing Field Files
- Listing Field Files List
- Listing Field Images
- Listing Field Icon
- Listing Map
- Listing Agent
- Listing Agent Avatar
- Listing Agent Field
- Listing Agent Listings

### Interactive Elements
- Map (OpenStreetMap with Leaflet)
- Tabs
- Language Switcher
- Button (Generic)
- Button Add Listing
- Button Login
- Button Share

## Dashboard Widgets

- News/Updates Widget
- Latest Listings Widget
- Map of Listings Widget
- Statistics - Usage Widget
- Statistics - Earnings Widget
- Statistics - Listings Widget

## Frontend Features

### Mobile Features
- Mobile bottom navbar (customizable)
- Mobile footer menu with hamburger toggle
- Touch-friendly interface

### Search Features
- Smart location search with auto-suggestion
- Category filtering
- Location filtering
- Advanced field filtering
- Dependent field filtering
- Search form customization
- Saved searches

### Listing Features
- Listing views counter
- Favorites/Bookmarking system
- Listing approval workflow
- Listing activation control
- Featured listings support
- Rich content editor integration (Elementor)

### User Features
- User messaging system
- Contact owner functionality
- User profiles
- User registration/login integration
- Edit log tracking

## SEO Features

- Custom meta tags for listings
- OpenGraph support
- Yoast SEO integration
- Custom title formatting
- SEO-friendly URL slugs
- Sitemap support

## Third-Party Integrations

### Plugin Integrations
- Elementor & Elementor Pro (full integration)
- Yoast SEO (meta tags and OpenGraph)
- WooCommerce compatibility
- WordPress Importer
- WPML/Polylang (multilingual support)

### Add-ons (Extension System)
- WDK Currency Conversion
- WDK Membership
- WDK Bookings
- Via Freemius SDK

## AJAX Handlers

### Admin AJAX
- `wp_ajax_wdk_admin_action` - Admin panel operations

### Public AJAX
- `wp_ajax_wdk_public_action` - Authenticated frontend operations
- `wp_ajax_nopriv_wdk_public_action` - Non-authenticated operations
- `map_infowindow` - Map popup content loading
- `treefieldid` - Dependent field loading

## Email Templates (30+)

- Contact form emails
- Listing approval notifications
- Membership subscription emails
- Reservation management emails
- Message notifications
- Review notifications
- User account creation
- Payment/Package expiration
- Search alerts

## Settings Categories

- General listing settings
- Field management options
- Search form configuration
- Result card styling
- Category/Location management
- Currency settings
- Email notification templates
- SEO configuration
- Membership integration
- Booking integration
- Payment options
- Date/time formatting
- Content editor options

## Map Integration

### OpenStreetMap with Leaflet
- Interactive maps
- Marker display
- Info windows
- Map in dashboard widgets

## Technical Implementation

### Architecture
- MVC Architecture using Winter_MVC framework
- Custom database tables with prefixed names
- REST API support for listings post type
- Built-in caching mechanisms

### Core Classes
- Winter_MVC_Controller (base controller)
- Wdk_frontendajax (AJAX handling)
- WDK_DashWidgets (dashboard widgets)

### Security
- Nonce verification for forms
- User capability checks
- Sanitization of user inputs
- Permission checks for edit operations

### Internationalization
- Full i18n support with translation files
- WPML/Polylang integration

### Extensibility
- Hook system with `do_action()` and `apply_filters()`
- Action hooks for addon integration
- Filter hooks for customization

### Hooks & Extensibility
- `wdk/settings/import/run` - Settings import
- `wdk-membership/dash/homepage/widgets` - Dashboard widgets
- `wdk-membership/listing/saved` - Listing save hook
- `wpdirectorykit/dash-widgets/` - Dashboard widget system
- `wdk_fs_loaded` - Freemius SDK loaded

## Premium Features (via Freemius)

- Pro version support
- Additional addons
- Extended functionality for membership, bookings, currency conversion

## Demo Templates

### Included Demos
- Real Estate Agency
- Car Dealership
- General Directory
