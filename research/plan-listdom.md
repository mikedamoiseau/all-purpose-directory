# Listdom Plugin - Feature Analysis

**Plugin Name:** Listdom
**Version:** 5.2.1
**Author:** Webilia
**License:** GPLv2+
**Requirements:** WordPress 4.2+, PHP 7.4+

**Description:** An AI-powered WordPress directory and classifieds plugin with 80+ responsive skins/views for creating business directories, classified ad sites, store locators, and any listing-based website.

---

## Custom Post Types

| Post Type | Slug | Description |
|-----------|------|-------------|
| Listings | `listdom-listing` | Main listing/directory items |
| Shortcodes | `listdom-shortcode` | Shortcode configurations |
| Search | `listdom-search` | Search form configurations |
| Notifications | `listdom-notification` | Email notification management |
| Orders | `listdom-order` | Payment orders |
| Recurring | `listdom-recurring` | Recurring payment subscriptions |
| Plans | `listdom-plan` | Pricing plan configurations |
| Coupons | `listdom-coupon` | Discount coupon management |

## Custom Taxonomies

| Taxonomy | Slug | Description |
|----------|------|-------------|
| Categories | `listdom-category` | Listing categories (hierarchical) |
| Locations | `listdom-location` | Geographic locations |
| Tags | `listdom-tag` | Listing tags |
| Features | `listdom-feature` | Amenities/features |
| Attributes | `listdom-attribute` | Custom attributes |
| Labels | `listdom-label` | Listing labels |
| Tax | `listdom-tax` | Tax settings |

## Display Skins & Views (15+)

### Free Skins
- **List View** - Traditional vertical listing
- **Grid View** - Multi-column grid layout
- **List + Grid** - Switchable views
- **Carousel** - Horizontal scrolling slider
- **Table View** - Tabular data display
- **Masonry** - Pinterest-style layout
- **Cover View** - Large featured image cards
- **Slider View** - Full-screen image slider
- **Half Map / Split View** - Side-by-side map and listings
- **Single Map** - Full-width interactive map
- **Simple Map** - Lightweight map display

### Pro/Premium Skins
- Side by Side, Mosaic, Accordion, Timeline, Gallery

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[listdom]` | Main directory/listing display |
| `[listdom-search]` | Search and filter form |
| `[listdom-login]` | User login form |
| `[listdom-register]` | User registration form |
| `[listdom-forgot-password]` | Password recovery |
| `[listdom-auth]` | All authentication forms |
| `[listdom-user-profile]` | User profile page |
| `[listdom-dashboard]` | Frontend user dashboard |
| `[listdom-category]` | Listings by category |
| `[listdom-location]` | Listings by location |
| `[listdom-tag]` | Listings by tag |
| `[listdom-feature]` | Listings by feature |
| `[listdom-label]` | Listings by label |
| `[listdom-taxonomy]` | Generic taxonomy display |
| `[listdom-taxonomy-cloud]` | Taxonomy cloud |
| `[listdom-terms]` | Display taxonomy items |
| `[listdom-checkout]` | Payment checkout (Pro) |
| `[listdom-order-summary]` | Order summary (Pro) |

## Widgets (6)

1. **Search Widget** - Sidebar search form
2. **Shortcode Widget** - Display shortcodes in sidebars
3. **All Listings Widget** - Show recent/random listings
4. **Taxonomy Cloud Widget** - Display category/tag cloud
5. **Terms Widget** - Display taxonomy terms
6. **Simple Map Widget** - Display map in sidebar

## Listing Elements (28 Single Page Elements)

- Title, Image/Gallery, Description/Content, Excerpt
- Categories, Locations, Tags, Features, Labels
- Price (with advanced options)
- Address (with geocoding)
- Map (interactive, multiple styles)
- Availability/Work Hours
- Contact Info (phone, email, website)
- Owner/Author Info
- Contact Form, Report Abuse Form
- Remark/Review (with addon)
- Social Share Buttons
- Related Listings
- Breadcrumb, Info Window
- Video, FAQ, Attributes
- CTA Button, Price Class, Embed Content

## Custom Fields Support

### Field Types
- Text input, Text area/HTML editor
- Number, Date picker, Time picker
- Email, URL, Telephone
- Select dropdown, Multi-select
- Checkbox, Radio buttons
- Image upload, File attachment
- Hierarchical dropdowns (Pro)

### Features
- Category-specific custom fields
- Field visibility and requirement management
- Conditional display per category
- Export/import custom fields

## Search & Filtering Features

### Search Builder
- Create complex search forms
- Responsive layouts for all devices

### Search Field Types
- Text search
- Taxonomy filters (categories, locations, tags, features, labels)
- Price range slider
- Date range picker
- Radius/geographic search with GPS
- Custom field filters
- Hierarchical dropdown filters
- Hidden fields

### Search Widget Features
- Popup search forms
- Dropdown vs list styling
- Clear all button
- Connected shortcodes
- "More Options" expandable sections

## Admin Menu Structure

1. **Home** - Dashboard with overview
2. **Shortcodes** - Manage shortcode configurations
3. **Search Builder** - Create search forms
4. **Notifications** - Email notification management
5. **Payments** - Order management
6. **Settings** - Global configuration
7. **Import / Export** - Data tools
8. **Wizard** - Setup wizard
9. **Addons** - Add-on management
10. **Licenses** - License activation

## AJAX Handlers

- `lsd_ajax_search` - Real-time search/filtering
- `lsd_autosuggest` - Address autocomplete
- `lsd_hierarchical_terms` - Hierarchical dropdown loading
- `lsd_ai_availability` - AI work hours generation
- `lsd_ai_content` - AI content generation
- `lsd_login/register/forgot_password/reset_password` - Auth handlers
- `lsd_resend_verification` - Email verification resend

## Map Integration

### Providers
- Google Maps
- OpenStreetMap/Leaflet (completely free)

### Map Features
- Map position controls (top, bottom, left, right)
- Marker clustering
- Multiple styles (Apple, Facebook, Dark, Ultralight, etc.)
- GPS location support
- Address to geocoding conversion
- Address autocomplete

## AI Features

### Supported AI Providers
- OpenAI (GPT-5 Mini, GPT-5 Nano, GPT-4o Mini)
- Anthropic Claude (Claude Sonnet 4, Claude Haiku 3.5)
- Google Gemini (Gemini 2.5 Flash, Gemini 2.5 Flash Lite)

### AI Capabilities
- CSV Import Auto Mapping
- Text/Description Generation
- Work Hours Generation
- AI Profiles for different tasks

## Payment & Monetization

### Built-in Payment Engine
- Order management
- Invoice generation
- Tax handling (global and regional)
- Pricing tiers
- Coupon/discount codes
- Recurring payment support

### Payment Gateways
- Free (for free listings)
- On-site (internal payments)
- Stripe (Pro)
- PayPal (Pro)
- Wire Transfer (Pro)

### Monetization Options
- Charge for listing submission
- Premium listing options
- Subscription/membership models
- Featured listing upgrades
- Sponsored listings

## Import & Export

### CSV/Excel Support
- Listing import with auto-mapping
- Bulk listing creation
- Export listings to CSV
- Import/export taxonomies
- Import/export custom fields
- Working hours export/import

### JSON Support (Pro)
- JSON import/export
- API support for programmatic integration

## Notification System

- Admin and user notifications
- Custom email triggers
- HTML editor for templates
- Variable support (user name, listing title, etc.)
- Trigger-based notifications
- Multiple recipients
- Conditional sending

## Page Builder Integrations

- **Elementor** - Full integration
- **Divi Builder** - Full integration
- **Visual Composer**
- **King Composer**
- **Gutenberg Block Editor**

## Third-Party Integrations

- Mailchimp (newsletter signup)
- WooCommerce support
- BuddyPress compatibility
- ACF (Advanced Custom Fields)
- Yoast SEO, RankMath, AIOSEO compatible
- WPML and Polylang (multilingual)

## SEO Features

- Friendly Slug Manager
- Schema/Structured Data (Pro addon)
- Hierarchical URLs (Pro)
- Meta Management
- Sitemap Support

## User Management

- Frontend dashboard for listing management
- User registration/login/password recovery
- Email verification
- User profiles with public viewing
- Role-based access control
- Separate login forms per role
- Block roles from WordPress dashboard
- Guest user submissions (Pro)

## Design Customization

- UI Customizer with live preview
- Color schemes and predefined palettes
- Font manager
- Custom CSS support
- Single listing page design builder
- 4 pre-made single listing styles plus custom builder
- Module management on single listing

## REST API Endpoints

- Listing resources
- Taxonomy resources
- User/profile resources
- Search module endpoints
- Attachment/image endpoints
- Availability endpoints
- Field endpoints
- Addon endpoints

## Addon Ecosystem (30+)

Key addons:
- Bridge (migration tool)
- Pro Addon (premium features)
- Claim Listings
- Connect Addon (contact management)
- Topup Listings (featured upgrades)
- Bookmarks/Favorites
- Excel Addon, CSV Auto Importer
- Rank Addon, Reviews
- Subscriptions (WooCommerce)
- Booking, Team, Auction
- Advanced Map, Visibility
- BuddyPress, ACF Integration
- Elementor/Divi Addons

## Technical Implementation

### Architecture
- Singleton pattern for main class
- Autoloader system
- Composer for dependencies
- Actions/Hooks system for extensibility

### Main Classes
- LSD_Main, LSD_Menus, LSD_Shortcodes
- LSD_Skins, LSD_PTypes, LSD_Taxonomies
- LSD_Ajax, LSD_API, LSD_Admin
- LSD_Dashboard, LSD_Payments
- LSD_AI, LSD_Search, LSD_Notifications

### Localization
- Full i18n support
- RTL Support
- WPML and Polylang Integration
