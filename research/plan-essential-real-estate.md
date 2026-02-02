# Essential Real Estate Plugin - Feature Analysis

**Plugin Name:** Essential Real Estate
**Version:** 5.2.5
**Author:** G5Theme
**License:** GPLv2+
**Requirements:** WordPress 4.5+

**Description:** A feature-rich WordPress plugin that provides a complete real estate marketplace system with property submission, agent management, payment processing, and comprehensive admin controls.

---

## Custom Post Types

| Post Type | Slug | Description |
|-----------|------|-------------|
| Properties | `property` | Main property listings |
| Agents | `agent` | Agent profiles |
| Packages | `package` | Membership packages |
| User Packages | `user_package` | User subscriptions/purchases |
| Invoices | `invoice` | Payment invoices |
| Transaction Logs | `trans_log` | Transaction history |

## Custom Taxonomies

| Taxonomy | Slug | Description |
|----------|------|-------------|
| Property Type | `property-type` | Property classification (hierarchical) |
| Property Status | `property-status` | Sale/rent status |
| Property Feature | `property-feature` | Amenities/features |
| Property Label | `property-label` | Special labels |
| Property State | `property-state` | Geographic regions (hierarchical) |
| Property City | `property-city` | Geographic cities |
| Property Neighborhood | `property-neighborhood` | Geographic neighborhoods |
| Agency | `agency` | Agency classification |

## Property Management System

### Frontend Submission
- Step-by-step property submission wizard
- Multi-image gallery upload with drag-and-drop sorting
- Video URL embedding
- 360° virtual tour support
- Document/attachment upload with validation
- Form-based property editor with preview
- Required field enforcement
- Draft saving capability

### Property Fields
- Title, description, ID
- Price (with "Price on Call" option)
- Property type, status, features, labels
- Bedroom, bathroom, garage counts
- Land area with custom units
- Living area/size with custom units
- Year built
- Full location hierarchy (country/state/city/neighborhood)
- Custom dynamic fields (text, textarea, dropdown, checkboxes, radio)
- Private notes (visible to agent only)

### Backend Features
- Property approval workflow
- Visibility control (visible/hidden)
- Featured property marking
- Bulk filtering by status, type, city, agent
- Custom admin columns
- Property expiration management
- Relisting/reactivation system

## Search and Discovery

### Search Implementations
- Basic keyword search
- Advanced search with 20+ filter fields
- Price slider (configurable range)
- Property characteristic filters
- Location-based search with autocomplete
- Map-based property search
- Mini search widget for sidebars
- Saved search feature with email alerts

### Location Features
- Country dropdown filtering
- State/province selection
- City selection
- Neighborhood selection
- Hierarchical location relationships
- Google autocomplete for addresses
- Geolocation-based search

### Listing Features
- Grid, list, carousel, zigzag layouts
- Sortable by date, price, popularity, featured
- AJAX pagination
- Category filtering
- Search form on archive pages

## Property Display & Browsing

### Single Property Page
- Overview tab with key details
- Features tab (list or category layout)
- Gallery/media tab with lightbox
- Floor plans tab with zoom
- Reviews/ratings tab
- Address with embedded Google Map
- Recent view counter (optional)
- Creation date (optional)

### Property Listings
- Multiple layout options (grid, list, carousel, slider)
- Featured properties highlighted
- Related properties
- Image galleries
- Quick view modals
- Agent information display

## Comparison & Favorites

### Favorites System
- Add/remove properties from wishlist
- Personal favorites page
- Quick favorite toggle
- Session-based and persistent storage

### Comparison Feature
- Add multiple properties to compare
- Side-by-side comparison table
- Custom field comparison
- Print comparison results

## Agent Management

### Agent Features
- Agent profiles with avatar
- Agent descriptions
- Agent contact information
- Property portfolio display
- Agent ratings and reviews
- License information
- Agency affiliation
- Statistics (property count, etc.)

### Agent Display
- Agent directory/archive
- Single agent detail pages
- Agent portfolio cards
- Related agents section
- Top agents widget
- Agency listing page

## Agency Management

### Agency Features
- Agency profiles
- Logo upload
- Multiple agents per agency
- Agency description
- Contact information
- Agency taxonomy for filtering

### Display Options
- Agency directory
- Single agency pages
- Agent listings within agency
- Properties by agency

## User Authentication & Accounts

### Login System
- Custom login form
- Email-based login
- Modal/popup login interface
- Password reset functionality
- Google reCAPTCHA support
- Social login integration (configurable)

### Registration
- New user registration
- Email verification
- Captcha protection
- Field validation
- Configurable registration fields

### User Profiles
- Profile photo/avatar upload
- Editable user information
- Password management
- Email preferences
- Contact details (phone, website, social)
- Field visibility settings

### User Roles
- Administrator
- Real Estate Agent (`ere_agent`)
- Customer (`ere_customer`)
- Role-based capabilities

## Payment & Subscription System

### Package Types
- Free listings
- Per-listing charges
- Per-package subscriptions
- Feature-based pricing
- Duration-based validity

### Payment Methods
1. **PayPal** - Full integration, IPN support
2. **Stripe** - Credit card processing, tokenization
3. **Wire Transfer** - Manual bank transfer option
4. **Free Packages** - Zero-cost activation

### Invoice Management
- Automatic invoice generation
- Invoice printing
- Invoice history
- Payment status tracking
- Invoice detail page

## Shortcodes (23+)

### Authentication
| Shortcode | Description |
|-----------|-------------|
| `[ere_login]` | Login form |
| `[ere_register]` | Registration form |
| `[ere_profile]` | User profile |
| `[ere_reset_password]` | Password reset |

### Property Management
| Shortcode | Description |
|-----------|-------------|
| `[ere_my_properties]` | User's properties |
| `[ere_submit_property]` | Property submission form |
| `[ere_my_favorites]` | User favorites |
| `[ere_my_save_search]` | Saved searches |
| `[ere_compare]` | Property comparison |

### Property Display
| Shortcode | Description |
|-----------|-------------|
| `[ere_property]` | Property listings |
| `[ere_property_carousel]` | Carousel display |
| `[ere_property_slider]` | Slider display |
| `[ere_property_gallery]` | Gallery display |
| `[ere_property_featured]` | Featured properties |
| `[ere_property_type]` | By property type |

### Search
| Shortcode | Description |
|-----------|-------------|
| `[ere_property_search]` | Basic search |
| `[ere_property_advanced_search]` | Advanced search |
| `[ere_property_search_map]` | Map search |
| `[ere_property_mini_search]` | Mini search widget |

### Agents & Agencies
| Shortcode | Description |
|-----------|-------------|
| `[ere_agent]` | Agent listings |
| `[ere_agency]` | Agency listings |

### Payment & Packages
| Shortcode | Description |
|-----------|-------------|
| `[ere_package]` | Package display |
| `[ere_payment]` | Payment form |
| `[ere_payment_completed]` | Payment confirmation |
| `[ere_my_invoices]` | Invoice history |

### Other
| Shortcode | Description |
|-----------|-------------|
| `[ere_nearby_places]` | Nearby points of interest |

## Widget Library

1. **Recent Properties** - Latest property listings
2. **Featured Properties** - Highlighted properties
3. **Top Agents** - Top-performing agents
4. **Property Search Form** - Search widget
5. **Listing Property by Taxonomy** - Taxonomy-filtered listings
6. **Login Menu** - Login/logout with stats
7. **Mortgage Calculator** - Payment calculator
8. **My Package Status** - User's package info

## Map Features

### Interactive Maps
- Single/multiple property markers
- Property search on map
- Info boxes with property details
- Custom marker icons
- Zoom controls
- Street view
- Directions
- Configurable map styles

## Nearby Places Feature

- Search for nearby points of interest
- Display schools, hospitals, parks, restaurants
- Distance calculation
- Custom search radius
- Unit configuration (km/miles)
- Google Places API integration

## Dashboard Features

### User Dashboard Sections
- Property listings overview
- Package management
- Invoice history
- Favorites management
- Saved searches
- Profile settings

### Dashboard Widgets
- Property count
- Favorite count
- Active packages
- Recent invoices

## Global Settings & Configuration

### General Settings
- Currency configuration
- Price formatting
- Number formatting
- Measurement units
- Display preferences

### Feature Toggle
- Enable/disable favorites
- Enable/disable social sharing
- Enable/disable property printing
- Enable/disable frontend submission
- Enable/disable agent registration
- Enable/disable comments
- Enable/disable ratings

### Map Configuration
- Google Maps API key
- Map styling
- Zoom levels
- SSL support

### Security Settings
- reCAPTCHA keys
- CSRF protection
- Capability enforcement

## Email System

### Email Notifications
- Property submission confirmation
- Approval notifications
- Payment receipts
- Saved search alerts
- Agent contact notifications
- Background email queue processing
- Template customization

## Multi-Language & Localization

- Full WPML compatibility
- Translation-ready strings
- Language-specific AJAX handling
- RTL language support
- Multiple language switching

## AJAX Handlers (30+)

### Authentication
- login, register, reset_password
- profile update, password change
- agent registration

### Properties
- image upload, attachment upload
- review submission, favorite toggle
- gallery view, property printing
- featured toggling

### Search
- property search, map search
- pagination, location hierarchy loading
- price updates

### Comparison
- compare toggle, list management

### Agents
- agent pagination, agent reviews
- agent contact

### Payments
- PayPal, Stripe, wire transfer
- free package activation
- invoice printing

## Technical Architecture

### Design Patterns
- Singleton pattern for main classes
- Factory pattern for widgets
- Hook-based architecture
- Template hierarchy system

### Core Classes
- `Essential_Real_Estate` - Main orchestrator
- `ERE_Public` - Frontend functionality
- `ERE_Admin` - Admin functionality
- `ERE_Shortcodes` - Shortcode management
- `ERE_Forms` - Form handling

### Frontend Framework
- Bootstrap 3+
- Owl Carousel 2.3.4
- jQuery UI
- Select2
- Light Gallery
- Star Rating

## Setup Pages Created Automatically

1. Listing Properties
2. Property Search
3. Advanced Search
4. Agent Directory
5. Agency Directory
6. Payment
7. Packages
8. User Profile
9. Login/Register
10. My Properties
11. My Favorites
12. My Invoices
13. Property Comparison
