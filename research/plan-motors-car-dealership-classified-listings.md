# Motors - Car Dealership & Classified Listings - Feature Analysis

**Plugin Name:** Motors – Car Dealer, Classifieds & Listing
**Version:** 1.4.102
**Author:** StylemixThemes
**License:** GPLv2+
**Requirements:** WordPress 4.6+, PHP 5.6+

**Description:** A comprehensive WordPress plugin for managing vehicle classified listings and creating fully-functional car dealership websites.

---

## Custom Post Types

| Post Type | Slug | Description |
|-----------|------|-------------|
| Listings | `st_listings_post_type` | Main vehicle listing post type |
| Test Drive Requests | `test_drive_request` | Test drive request management |
| Dealer Reviews | `dealer_review` | Dealer/seller reviews |
| Listing Templates | `motors_listing_template` | Elementor-based single listing templates |

## Custom Taxonomies

### Dynamic Custom Field Taxonomies
- Plugin creates taxonomies dynamically based on configured custom fields
- Each custom field becomes a registrable taxonomy (make, model, year, etc.)
- Supports parent-child hierarchy (numeric fields)
- Full filtering and search capabilities

### Additional Features Taxonomy (`stm_additional_features`)
- Separate taxonomy for vehicle features/options
- Non-hierarchical structure
- Used across all listing types

## Shortcodes

| Shortcode | Description | Parameters |
|-----------|-------------|------------|
| `[motors_listing_inventory]` | Displays filterable vehicle listings | `inventory_skin`, `posts_per_page`, `custom_img_size` |
| `[motors_add_listing_form]` | Frontend listing submission form | - |
| `[motors_login_page]` | User login/registration form | - |
| `[motors_compare_page]` | Side-by-side vehicle comparison | - |
| `[motors_listing_search]` | Quick search form with dropdowns | `show_amount`, `filter_fields` |
| `[motors_listing_icon_filter]` | Icon-based category filtering | `title`, `as_carousel`, `filter_selected`, `columns` |
| `[motors_listings_tabs]` | Tabbed listing display | `title`, `columns`, `popular_tab`, `recent_tab`, `featured_tab` |

## Elementor Widgets (30+)

### Free Widgets

#### Inventory Widgets
- Search Filter (InventorySearchFilter)
- Search Results (InventorySearchResults)
- View Type Switcher (InventoryViewType)
- Sort By (InventorySortBy)

#### Listing Display
- Listing Search Tabs (ListingSearchTabs)
- Listing Grid Tabs (ListingsGridTabs)
- Listings Compare (ListingsCompare)
- Image Categories (ImageCategories)

#### User & Account
- Login/Register (LoginRegister)
- Add Listing Form (AddListing)
- Dealers List (DealersList)

#### Single Listing Widgets
- Title, Price, Gallery
- Listing Description
- Listing Data
- Features
- Similar Listings
- Dealer Email/Phone
- User Data
- Offer Price Button

### Pro/Premium Widgets
- Pricing Plans (PricingPlan)
- Additional single listing variants

## Admin Features

### Listing Manager Dashboard
- Centralized inventory control interface
- Quick access listings management
- User-friendly design

### Custom Fields Management
- Create unlimited custom fields
- Field types: text, textarea, number, checkbox, select, multiselect, date, datetime
- Numeric vs. non-numeric field support
- Parent-child field dependencies
- Field sorting and ordering

### Category Management
- Create and manage listing categories
- Category icons and images
- Parent-child category structure
- Bulk import/export (JSON format)

### Admin Menu Structure
- Motors Plugin Settings
- Listing Manager
- Listing Categories
- Pages Bindings
- Add-ons Library
- Support/Help Center

## Settings & Configuration

### Listing Settings
- General listing configuration
- Currency settings (multi-currency support in Pro)
- Listing card customization
- Certified logo display options

### Search & Filter Settings
- Filter position and layout
- Sorting options configuration
- Custom field filtering
- Feature-based filtering
- Inventory page skins (Pro)
- Keyword search (Pro)
- Location-based filtering (Pro)

### Single Listing Page Settings
- General layout options
- Listing template selection
- Loan calculator configuration (Pro)
- WhatsApp integration (Pro)

### User & Profile Settings
- User registration options
- Dealer role and permissions
- Profile page customization

### Monetization Settings
- Charge for listing submissions (Pay-per-submit)
- Featured listing monetization
- Dealer registration fees
- WooCommerce integration
- Subscription models (with Subscriptio plugin)

### Pages Binding
- Inventory/Listings page
- Add Car/Listing page
- User Profile/Account page
- Login page
- Compare page

## AJAX Handlers

### Listing Operations
- `stm_listings_ajax_save_user_data` - Save user info
- `listings-result` - Filter and display listings
- `listings-result-load` - Load additional listings (pagination)
- `listings-sold` - Display sold vehicles

### Comparison
- `stm_ajax_get_compare_list` - Retrieve compared items
- `motors_compare_page` - Manage comparison interface

### User Interactions
- `stm_ajax_add_to_favourites` - Add/remove from favorites
- `stm_ajax_get_favourites` - Retrieve saved favorites
- `stm_ajax_get_seller_phone` - Display seller phone

### Test Drives & Contact
- `stm_ajax_add_test_drive` - Submit test drive request
- `stm_ajax_get_car_price` - Get pricing information
- `stm_trade_in_form` - Trade-in value calculator
- `stm_ajax_buy_car_online` - Online purchase handler

## Integration Capabilities

### WooCommerce Integration
- Payment processing for paid features
- Listing checkout hooks
- Online selling capability (Pro)
- Multiple payment gateway support

### Subscriptio Integration (Pro)
- Subscription-based dealer plans
- Recurring billing support

### Elementor Integration
- Full Elementor builder support
- Custom widgets library (30+)
- Template builder for single listings

### WPML & Multilingual Support
- Translation-ready plugin
- Compatible with Loco Translate
- 11+ languages included
- RTL language support

### Google Maps API
- Location-based search
- Map display on listings
- Distance calculation

### reCAPTCHA
- Form protection
- Bot prevention

### Social Integration
- Social login (Pro - Google, Facebook)
- Social sharing buttons
- WhatsApp contact button (Pro)

## Pro/Premium Features

### Inventory Management
- Multiple inventory skins/designs
- Listing card skins (grid & list view variants)
- Custom field filtering enhancements

### Search & Discovery
- Keyword/text search
- Location-based filtering with Google Maps
- Distance-based search
- Saved searches with email alerts

### Listing Templates
- 4 native single listing templates (Classic, Modern, Mosaic Gallery, Carousel Gallery)
- Custom Elementor template creation

### Financial Features
- Loan/Finance Calculator
- Monthly payment estimation
- Customizable interest rates and terms

### Dealer Features
- Dealer registration system
- Multi-dealer management
- Dealer ratings and reviews
- Public dealer profiles

### Monetization
- Pay-per-listing submission charges
- Featured listing premium pricing
- WooCommerce checkout integration
- Subscription models

### VIN Decoder Integration (5 services)
- CarApi
- OpenDataSoft
- NHTSA
- Vindecoder
- Marketcheck

### Email Management
- Email Template Manager
- Customizable email templates
- Trigger-based email sending
- Mobile preview for templates

## Frontend Features

### User Accounts & Profiles
- User registration form
- User login page
- User dashboard/profile page
- Edit profile information
- View/manage user listings
- Public dealer profiles

### Listing Management
- Add/create listing form (frontend)
- Edit existing listings
- Delete listings
- Listing moderation
- Draft/publish status management

### Search & Filtering
- Advanced search filters
- Multi-select filtering
- Price range sliders
- Date range filters
- Category-based filtering
- Feature-based filtering

### Comparison
- Add listings to comparison
- Remove from comparison
- Side-by-side comparison view
- Multi-vehicle comparison (up to 3)

### Favorites/Saved Listings
- Add/remove from favorites
- Persistent favorites (user accounts)
- Guest user favorites (cookies)

### Contact & Inquiry
- Request price/information
- Test drive booking
- Trade-in value calculator
- WhatsApp direct contact (Pro)
- Seller phone display

## Technical Architecture

### Class Structure
- 147+ PHP class files
- Namespaced architecture (MotorsVehiclesListing)
- Service-oriented design
- Hook-based extensibility

### File Organization
- `/includes/class/` - Core classes and features
- `/includes/admin/` - Admin functionality
- `/includes/listing-templates/` - Template files
- `/elementor/` - Elementor widget integration
- `/templates/` - Frontend templates
- `/assets/` - CSS, JS, images

### Security Features
- Nonce verification for AJAX
- Role-based access control
- Input sanitization
- Output escaping
- File upload validation
- reCAPTCHA integration

### Localization
- Full translation support (11+ languages)
- Translation-ready text domains
