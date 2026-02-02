# Advanced Classifieds and Directory Pro - Feature Analysis

**Plugin Name:** Advanced Classifieds and Directory Pro
**Version:** 3.3.0
**Author:** PluginsWare
**License:** GPL-2.0+
**Description:** Provides an ability to build any kind of business directory site: classifieds, cars, bikes, boats and other vehicles dealers site, pets, real estate portal, wedding site, yellow pages, etc.

---

## Shortcodes (21 Total)

### Registration & User Management
| Shortcode | Description |
|-----------|-------------|
| `[acadp_login]` | User login form |
| `[acadp_logout]` | Logout button |
| `[acadp_register]` | User registration form |
| `[acadp_user_account]` | User account page |
| `[acadp_forgot_password]` | Password recovery form |
| `[acadp_password_reset]` | Password reset form |

### Listings Display
| Shortcode | Description |
|-----------|-------------|
| `[acadp_listings]` | Display listings with multiple view options |
| `[acadp_location]` | Single location display |
| `[acadp_category]` | Single category display |
| `[acadp_categories]` | Display categories |
| `[acadp_locations]` | Display locations |

### Search & Filtering
| Shortcode | Description |
|-----------|-------------|
| `[acadp_search_form]` | Search form (inline/vertical layouts) |
| `[acadp_search]` | Search results display |

### User Dashboard
| Shortcode | Description |
|-----------|-------------|
| `[acadp_user_listings]` | User's listings |
| `[acadp_user_dashboard]` | User dashboard |
| `[acadp_listing_form]` | Frontend listing submission form |
| `[acadp_manage_listings]` | Manage user's listings |
| `[acadp_favourite_listings]` | Favorite listings |

### Payments
| Shortcode | Description |
|-----------|-------------|
| `[acadp_checkout]` | Payment checkout form |
| `[acadp_payment_errors]` | Payment error messages |
| `[acadp_payment_receipt]` | Payment receipt |
| `[acadp_payment_history]` | Payment history |

## Gutenberg Blocks (5 Total)

- **Locations Block** - Display locations with settings for columns, depth, ordering, hide empty
- **Categories Block** - Display categories with grid/list layouts
- **Listings Block** - Advanced listings display (list/grid/map views)
- **Search Form Block** - Customizable search form
- **Listing Form Block** - Frontend listing submission form

All blocks support custom styling options, responsive layouts, and advanced filtering.

## Custom Post Types

### acadp_listings (Main Listing Post Type)
- Supports: title, editor, author, revisions, optional comments
- Hierarchical: No
- Public: Yes
- Capability type: acadp_listing (with meta caps mapping)

### acadp_fields (Custom Fields Management)
- Supports: title only
- Custom taxonomy support: acadp_categories
- Exclude from search: Yes
- Capability type: acadp_field

### acadp_payments (Payment/Order Tracking)
- Supports: title, author
- Public: No
- Exclude from search: Yes
- Capability type: acadp_payment

## Custom Taxonomies

### acadp_categories
- Attached to: acadp_listings
- Hierarchical: Yes
- Show in REST: Yes
- Supports image/media per category
- Order by: ID, count, name, slug

### acadp_locations
- Attached to: acadp_listings
- Hierarchical: Yes
- Show in REST: Yes
- Parent-child relationships supported

## Custom Field Types (10 Total)

1. Text
2. Textarea
3. Select (dropdown)
4. Checkbox
5. Radio Button
6. Number
7. Range
8. Date
9. DateTime
10. URL

**Field Properties:**
- Can be required/optional
- Searchable flag
- Display in listings archive
- Category association or general form
- Default values support

## Admin Menu Structure

- Main Menu: "Classifieds & Directory"
  - Listings (All Listings, Add New)
  - Categories
  - Locations
  - Custom Fields
  - Payment History
  - Settings (General, Display, Monetize, Payment Gateways, Email, Advanced)

## Settings Tabs

### General Tab
- Front-end Listing Submission settings
- Login/Registration settings
- reCAPTCHA configuration
- Currency settings
- Map settings
- GDPR Compliance (Terms, Privacy Policy, Cookie Consent)

### Display Tab
- Listings display options (view, columns, sorting)
- Categories display settings
- Locations display settings
- Headers and pagination

### Monetize Tab
- Featured listing settings
- Badges settings
- Pricing configuration

### Payment Gateways Tab
- Gateway selection and configuration
- Offline payment options
- Currency for payments

### Email Tab
- Email notification settings
- Template configuration
- Notification recipients

### Advanced Tab
- Misc advanced settings
- Performance options
- Integration settings

## Widgets (7 Total)

1. **Categories Widget** - Display categories (list/dropdown layouts)
2. **Locations Widget** - Display locations (list/dropdown layouts)
3. **Listings Widget** - Display listings (grid/list/map layouts)
4. **Search Widget** - Search form widget
5. **Listing Address Widget** - Show listing address/location
6. **Listing Contact Widget** - Show listing contact information
7. **Listing Video Widget** - Embed listing video

## Frontend Features

### Listing Display
- Multiple view options: List, Grid, Map
- Listing cards with customizable info display
- Pagination support
- Sorting options (title, date posted, price, views count, random)
- Filtering by category, location, featured status
- Responsive layouts (configurable columns)

### Search Functionality
- Keyword search
- Category filtering
- Location filtering
- Custom fields search
- Price range filtering
- Inline and vertical form layouts
- AJAX-based search

### User Dashboard
- User account management
- Create/edit listings
- Manage existing listings
- Renew listings functionality
- Delete listings
- Favorites/Wishlist management
- View payment history

### Listing Features
- Image gallery with multiple images
- Video embedding support
- Map display with listing location
- Contact form for inquiries
- Report abuse functionality
- Share buttons
- Contact details display
- Price display
- Category and location badges

### User Features
- Registration system (optional)
- Login/Logout
- Password reset/forgot password
- User roles and capabilities
- Favorites/Wishlist system
- Account profile management

### Payment System
- Checkout functionality
- Featured listing purchases
- Multiple payment gateways support
- Payment history tracking
- Payment receipts
- Order management

## AJAX Handlers (13+)

- `acadp_get_child_terms` - Get child categories/locations
- `acadp_public_dropdown_terms` - Dropdown term fetching
- `acadp_set_cookie` - GDPR cookie management
- `acadp_custom_fields_search` - Dynamic custom fields for search
- `acadp_custom_fields_listings` - Custom fields for listings
- `acadp_public_add_remove_favorites` - Favorites management
- `acadp_public_report_abuse` - Abuse reporting
- `acadp_public_send_contact_email` - Contact form submission
- `acadp_public_image_upload` - Frontend image uploading
- `acadp_public_delete_attachment_listings` - Delete uploaded images
- `acadp_checkout_format_total_amount` - Payment amount formatting
- `acadp_delete_attachment` - Admin attachment deletion

## Integration & Compatibility

### SEO Integration
- Yoast SEO compatibility
- Meta tags customization
- Open Graph tags
- Canonical URLs
- Title and description management

### Security Features
- Nonce verification
- User capability checking
- Meta capability mapping
- Spam detection/honeypot
- reCAPTCHA support

### API/REST
- REST endpoint support for taxonomies
- Block editor integration
- Custom post type REST support

### Standards Compliance
- GDPR compliance features
- SSL/HTTPS support
- Gutenberg block editor support

## Roles & Capabilities

### Custom Capabilities
- edit_acadp_listings
- edit_acadp_listing
- delete_acadp_listing
- manage_acadp_options
- edit_acadp_fields
- edit_acadp_payments

## Cron Jobs

- `acadp_hourly_scheduled_events` - Handles listing expiration, renewal checks

## Rewrite Rules & Permalinks

- Custom rewrite rules for listings, categories, locations
- Customizable slugs via settings
- Query variables: `acadp_action`, `acadp_listing`, `acadp_location`, `acadp_category`

## Template System

### Frontend Templates
- Listings layouts (grid, list, map)
- Categories layouts (grid, list)
- Locations layouts (list, dropdown)
- Search form layouts (inline, vertical)
- Custom fields display (inline, vertical)
- Single listing templates
- User dashboard templates
- Payment templates (checkout, receipt, history)
- Registration templates (login, register, password reset)
- Listing form template

### Template Override
- Custom template loading system
- Filter hooks for customization
- Backward compatibility layer

## Plugin Architecture

### Main Classes
- `ACADP` - Core plugin class
- `ACADP_Loader` - Hook orchestration
- `ACADP_Admin` - Admin functionality
- `ACADP_Admin_Listings` - Listings management
- `ACADP_Admin_Categories` - Categories management
- `ACADP_Admin_Locations` - Locations management
- `ACADP_Admin_Fields` - Custom fields management
- `ACADP_Admin_Payments` - Payment management
- `ACADP_Admin_Settings` - Settings pages
- `ACADP_Public` - Public-facing core
- `ACADP_Public_Listings` - Listings display
- `ACADP_Public_Categories` - Categories display
- `ACADP_Public_Locations` - Locations display
- `ACADP_Public_Search` - Search functionality
- `ACADP_Public_Listing` - Single listing display
- `ACADP_Public_Registration` - User registration
- `ACADP_Public_User` - User dashboard
- `ACADP_Public_Payments` - Payment processing
- `ACADP_Blocks` - Gutenberg blocks
- `ACADP_Cron` - Scheduled events

## Premium Features

- Anti-spam honeypot
- Monetization enhancements
- Advanced gateway integrations
- Additional field types and options
