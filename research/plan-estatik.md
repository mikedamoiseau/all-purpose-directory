# Estatik Plugin - Feature Analysis

**Plugin Name:** Estatik
**Version:** 4.3.0
**Author:** suspended
**License:** GPLv2+
**Requirements:** WordPress 5.4+, PHP 5.6+

**Description:** A full-featured WordPress real estate plugin with clean design, flexible functionality, and smooth Elementor integration.

---

## Custom Post Types

### Properties (`properties`)
- Main property listings post type
- Custom rewrite slug
- Archive support (configurable)
- REST API support
- Elementor support
- Custom capabilities: `create_es_properties`
- Supports: title, editor, author, excerpt, thumbnail, elementor

### Saved Search (`saved_search`)
- User saved searches
- Not publicly visible
- REST API support
- Private post type for internal use

## Custom Taxonomies (14 total)

| Taxonomy | Slug | Description |
|----------|------|-------------|
| Locations | `es_location` | Property locations/regions |
| Categories | `es_category` | Property categories |
| Types | `es_type` | Property types |
| Rent Period | `es_rent_period` | Rental duration periods |
| Labels | `es_label` | Featured, hot, openhouse, etc. |
| Status | `es_status` | Active, Sold, Pending, etc. |
| Parking | `es_parking` | Parking type information |
| Roof | `es_roof` | Roof types |
| Exterior Material | `es_exterior_material` | Exterior material types |
| Basement | `es_basement` | Basement information |
| Floor Covering | `es_floor_covering` | Floor covering types |
| Features | `es_feature` | Swimming pool, garage, etc. |
| Amenities | `es_amenity` | Community amenities |
| Neighborhoods | `es_neighborhood` | Neighborhoods |
| Tags | `es_tag` | Property tags |

All taxonomies are REST API enabled with configurable rewrite slugs.

## Shortcodes

### Search & Filtering
| Shortcode | Description |
|-----------|-------------|
| `[es_search_form]` | Search form (simple, main, advanced types) |

### Property Display
| Shortcode | Description |
|-----------|-------------|
| `[es_single_property]` | Single property display |
| `[es_property_single_gallery]` | Property gallery viewer |
| `[es_property_single_map]` | Property map display |
| `[es_property_field]` | Display individual property fields |

### User Features
| Shortcode | Description |
|-----------|-------------|
| `[es_my_listings]` | User's listings management |
| `[es_my_entities]` | My properties dashboard |
| `[es_profile]` | User profile page |
| `[es_authentication]` | Login/Register shortcode |
| `[es_login]` | Login form |
| `[es_register]` | Registration form |
| `[es_reset_pwd]` | Password reset form |

### Property Browsing
| Shortcode | Description |
|-----------|-------------|
| `[es_properties_slider]` | Properties slider widget |
| `[es_request_form]` | Contact/request information form |

## Admin Pages & Menu Structure

**Main Menu:** Estatik (es_dashboard)

### Subpages
1. **Dashboard** - Overview and quick stats
2. **My listings** - Redirect to properties post type archive
3. **Add new property** - Quick add property link
4. **Data manager** - Manage all taxonomy terms
5. **Fields Builder** - Create unlimited custom fields
6. **Settings** - Global plugin configuration
7. **Demo content** - Set up demo listings
8. **Migration** - Migrate from v3.x to v4.x

## Fields Builder - Custom Field Types (30+)

### Text Fields
- Text input
- Textarea
- Editor (WYSIWYG)
- Phone field
- Date field
- Date/Time field
- Link field

### Selection Fields
- Select dropdown
- Checkboxes
- Radio buttons
- Multi-select field
- Checkboxes with borders
- Radio with borders
- Radio with images
- Radio with text

### Visual Fields
- Color picker
- Icon picker
- Images field
- Media field (file upload)
- Avatar field

### Specialized Fields
- Repeater field (for multiple entries)
- Incrementer field (numeric)
- Hidden field
- Switcher/Toggle field
- Fields list selector

All custom fields support labels, descriptions, required validation, placeholders, and default values.

## AJAX Handlers

- `es_save_field` - Save settings fields via AJAX
- `es_get_terms_creator` - Load terms creator interface
- `es_get_locations` - Get location options (with dependencies)
- `es_wishlist_action` - Add/remove from wishlist
- `es_get_property_item` - Load individual property item
- `es_search_address_components` - Search locations/addresses
- `es_save_search` - Save search criteria
- `es_remove_saved_search` - Delete saved search
- `get_listings` - Load properties listing

## Authentication & User Management

### Native Authentication
- Email/password registration
- Email/password login
- Password reset functionality
- Social network login (Facebook, Google)
- User profile management
- Custom user types (buyers, etc.)
- ReCAPTCHA verification (v2 & v3)
- Honeypot spam protection

### User Features
- Profile page with avatar upload
- Profile information updates
- Password change functionality

### Email System
- `new_user_registered_admin` - Admin notification
- `new_user_info` - User credentials email
- `reset_password` - Password reset email
- `request_property_info` - Property inquiry email
- HTML email templates with customizable styling

## Widget System

### WordPress Widgets
1. **Search Form Widget** - Advanced search with filters
2. **Request Form Widget** - Property inquiry form
3. **Properties Slider Widget** - Carousel of featured properties
4. **Listings Widget** - Property listings grid/list
5. **Properties Filter Widget** - Advanced filtering controls

### Elementor Widgets
- Search Form Widget
- Request Form Widget
- Properties Slider Widget
- Listings Widget
- Authentication Widget
- Half Map Widget
- Query (custom query builder)

## Gutenberg Blocks

1. **Es_Block** - Default block for property display
2. **Es_My_Listing_Block** - User listings management block

## Page Builder Integrations

### Elementor Integration
- Full Elementor widget library
- Custom Elementor controls (Select2 dropdown)
- Elementor document type for single properties
- Theme builder compatibility

### Divi Support
- Native Divi integration module

### Gutenberg Support
- Native Gutenberg blocks

## Data Managers

1. **Locations Manager** - Create/edit location hierarchy
2. **Features Manager** - Property features with icons
3. **Features Icons Manager** - Icon assignment for features
4. **Labels Manager** - Property labels (Featured, Hot, etc.)
5. **Terms Creator** - Generic taxonomy term management

## Advanced Search Features

### Search Types
- Simple search (basic fields)
- Main search (balanced)
- Advanced search (full field set)

### Search Field Types
- Price range search
- Location hierarchy search
- Multiple taxonomy filtering
- Address component search
- Custom field search
- Area/size range search

### Map Integration
- AJAX-based map search
- Location-based filtering
- Address component dependencies

## Multilingual Support

### Built-in Integration
- **WPML** (WordPress Multilingual Plugin)
- **Polylang** (Polylang plugin)
- **Loco Translate** (local translations)

### Translations Available
Italian, Spanish, French, German, Hungarian, Dutch, Romanian, Polish

## Value Formatting & Display

### Built-in Formatters
- **Price formatting** - Currency with custom symbols, decimals, positioning
- **Area/Lot Size** - Unit display (sq ft, sq m, acres, hectares)
- **Beds/Baths** - Plural handling
- **Date Added** - Relative time display
- **Image** - Gallery display
- **Document** - File downloads
- **URL/Links** - HTML link formatting
- **Location Fields** - Taxonomy term links
- **Country/State/City** - Hierarchical display

## Property Entity System

### Property Features
- Featured property flag
- Hot property indicator
- Property status tracking
- Rent/sale property types
- Price formatting
- Area calculations
- Social sharing metadata
- Image gallery handling
- Video embedding (YouTube, Vimeo)
- Document/file attachments
- Address field management
- Custom field values

## Migration System

### Version Migration
- Migration from Estatik 3.x to 4.x
- Property data migration
- Field configuration migration
- Settings migration
- Image/attachment handling

## Security Features

- Nonce verification on all forms
- ReCAPTCHA integration
- Honeypot spam protection
- Permission checking (`current_user_can()`)
- Input sanitization and validation
- Safe redirect handling
- User capability mapping

## Premium/PRO Features

### Estatik PRO
- Agent/Agency support
- Private fields for admin/agents
- CSV/XLS import (WP ALL IMPORT)
- PDF generation
- Subscription/payment processing (PayPal)
- Saved search notifications
- Compare properties feature
- Locations widget
- Slider widget
- Full-width slideshow widget
- Advanced front-end management

### Estatik Premium
- RETS/RESO Web API import
- MLS data import

## Technical Implementation

### Architecture
- Singleton pattern for main plugin class
- Factory pattern for field creation
- MVC-like template loading system
- Hook-based extensibility
- Action/filter hooks throughout

### Key Classes
- `Estatik` - Main plugin initializer
- `Es_Post_Types` - Custom post type registration
- `Es_Taxonomies` - Custom taxonomy registration
- `Es_Shortcodes_List` - Shortcode registration
- `Es_Admin_Menu` - Admin menu registration
- `Es_Fields_Builder` - Custom field system
- `Es_Property` - Property entity
- `Es_Auth` - Authentication system
- `Es_Profile_Page` - Profile management

### Template System
- Template loader from plugin templates/
- Customizable via child themes
- Multiple template types (admin, front, emails)
