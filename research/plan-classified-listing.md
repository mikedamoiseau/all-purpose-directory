# Classified Listing Plugin - Feature Analysis

**Plugin Name:** Classified Listing – AI-Powered Classified ads & Business Directory Plugin
**Version:** 5.3.5
**Author:** Business Directory Team by RadiusTheme
**License:** GPLv2 or later
**Requirements:** WordPress 6.7+, PHP 7.4+

**Description:** A comprehensive WordPress plugin for creating classified ads marketplaces and business directories with AI-powered features including form generation, semantic search, and intelligent content suggestions.

---

## Custom Post Types

| Post Type | Slug | Description |
|-----------|------|-------------|
| Listings | `rtcl_listing` | Main classified listings |
| Field Groups | `rtcl_cfg` | Custom field groups |
| Custom Fields | `rtcl_cf` | Individual custom fields |
| Payments | `rtcl_payment` | Payment records |
| Pricing | `rtcl_pricing` | Pricing packages |

## Custom Taxonomies

| Taxonomy | Slug | Description |
|----------|------|-------------|
| Categories | `rtcl_category` | Listing categories (hierarchical) |
| Locations | `rtcl_location` | Geographic locations (hierarchical, multi-level) |
| Tags | `rtcl_tag` | Listing tags |

## Listing Management System

### Frontend Submission
- AI-powered form builder with intelligent field suggestions
- Drag & drop form builder for manual creation
- Multi-directory support with different forms per niche
- Category-specific custom fields
- Conditional logic for field display
- Field dependencies (e.g., car model based on make)
- Multi-image gallery with drag-and-drop sorting
- Video support (YouTube, Vimeo, YouTube Shorts)
- Mixed image/video galleries

### Listing Features
- Mark as Sold feature (keep visible but disable communications)
- Featured Ads highlighting
- Top Ads pinning [Pro]
- Bump-Up Ads daily refresh [Pro]
- Automatic listing expiration with renewal options
- Draft saving capability
- Listing status workflow (Published, Reviewed, Expired, Temporary, Pending Payment)

### Backend Features
- Listing approval workflow
- Bulk actions for multiple listings
- Quick edit capabilities
- Meta columns display
- Admin import interface

## Custom Fields System

### Field Types (20+)
- Text Box, Text Area, Number, URL
- Date, Time, Date Range, Time Range
- Dropdown, Radio, Checkbox, Switch
- Color Picker, File Upload, Hidden Field
- Custom HTML, Repeater Field [Pro]

### Field Features
- Category-specific field assignments
- Conditional logic based on field values
- Field-level validation with patterns
- Required/optional configuration
- Field ordering via drag-and-drop
- Admin-only field marking

## Search and Discovery

### Search Implementations
- AJAX-powered real-time search
- Advanced filters (10+ filter types)
- Category, Location, Tag filtering
- Price Range slider
- Custom field filters
- Radius/Geolocation search with Google Places API
- OpenStreetMap support
- Inline and vertical search forms
- Autocomplete search suggestions

### AI Search Features
- AI Semantic Search [v5.3.0+]
- Vector embeddings for advanced search
- Context-aware searching

## User Dashboard & Account Management

### Dashboard Endpoints
- Main Dashboard hub
- My Listings management
- Favorites (bookmarked listings)
- Edit Account (profile settings)
- Payments history
- Profile Settings (privacy)

### User Features
- Social profile integration (LinkedIn, Facebook, Twitter)
- User type support (Individual, Business, Organization)
- Profile picture and bio management

## Payment & Monetization

### Payment Models
- Pay Per Ad (fixed fee)
- Featured Listings
- Top Ads [Pro]
- Bump-Up Ads [Pro]
- Subscriptions [Pro]
- Membership Plans [Addon]
- Claim Listing [Addon]

### Payment Gateways
- Offline Payment
- PayPal (built-in)
- Stripe [Pro]
- Authorize.net [Pro]
- WooCommerce [Pro]
- Razorpay [Addon]

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[rtcl_my_account]` | User account dashboard |
| `[rtcl_checkout]` | Payment checkout |
| `[rtcl_categories]` | Category display |
| `[rtcl_listing_form]` | Listing submission form |
| `[rtcl_listings]` | Listings grid/list display |
| `[rtcl_filter_listings]` | Listings with filters |
| `[rtcl_listing_page]` | Single listing display |

## Page Builder Integration

### Gutenberg (Block Editor)
- Custom blocks for listings, categories, locations, filters
- Block theme support with header/footer blocks
- Full site editing compatibility

### Elementor
- Custom widgets for all features
- Pro addon for advanced features
- Archive/single page builders [Pro]

### DIVI Builder
- Custom DIVI modules
- Drag & drop compatibility

## Map Features

### Google Maps Integration
- Places API for autocomplete
- Map display with markers
- Location details

### OpenStreetMap
- Free alternative to Google Maps

### Business Hours System
- Daily operating hours
- Multiple time slots per day
- Holiday support
- Hour templates

## Email System

### Email Types
- Listing submitted, published, updated, expired
- Listing renewal reminders
- Listing moderation notifications
- Listing contact notifications
- New registration, password reset
- Order created, completed

### Email Features
- Customizable templates with variables
- SMTP configuration
- HTML templates

## AI-Powered Features

### AI Form Generation
- Intelligent form creation from descriptions
- Directory type detection
- Custom field suggestions
- Time-saving automation

### Write with AI
- Title generation
- Description writing
- Content enhancement
- ChatGPT, Gemini, DeepSeek integration
- Custom API key support

### Additional AI Features
- AI Semantic Search [v5.3.0+]
- AI Image Enhancement [v5.3.0+]
- Vector embedding support

## Live Chat Feature

### Chat Functionality
- Real-time messaging between buyers and sellers
- Pusher integration for live updates
- Chat notifications

## AJAX Handlers

### Core Operations
- ListingAdminAjax - Admin listing operations
- AjaxGallery - Image gallery operations
- FilterAjax - Filter functionality
- FormBuilderAjax - Form builder operations
- PublicUser - User dashboard operations
- Checkout - Checkout process
- InlineSearchAjax - Search functionality
- AIController - AI feature handlers
- Import/Export - Data import/export

## Technical Architecture

### Core Classes
- `Rtcl` - Main singleton class
- Factory pattern for model creation
- Query builder for custom queries
- Session handler for user sessions
- Cart manager for shopping cart

### Database
- Uses post meta for listing details
- Term meta for taxonomy data
- User meta for preferences
- Custom Eloquent ORM implementation

### Performance
- Caching mechanisms
- Pagination for large datasets
- AJAX filtering prevents page reloads
- Optimized media handling
- REST API support for apps

## Internationalization

- Full translation support
- WPML Addon for multilingual
- Right-to-Left (RTL) language support
- Spanish translation included

## Addon Ecosystem (19+)

- Store & Membership Addon
- Elementor & Gutenberg Builder
- Mobile No Verification
- MultiCurrency Addon
- WPML Addon
- Seller Verification
- Booking Addon
- Claim Listing Addon
- Marketplace Addon
- Mobile APP
- And 9+ more specialized addons
