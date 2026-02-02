# Simple Business Directory Plugin - Feature Analysis

**Plugin Name:** Business Directory - Simple Business Directory
**Slug:** phone-directory
**Version:** 6.9.2
**Author:** QuantumCloud
**License:** GPLv2
**Requirements:** WordPress 4.6+, PHP 7.4+

---

## Custom Post Type & Taxonomy

| Type | Slug | Description |
|------|------|-------------|
| CPT | `pnd` | Simple Business Directory Items |
| Taxonomy | `pnd_cat` | List Categories (Hierarchical) |

Each "List" is a custom post type that contains multiple business/listing items.

## Frontend Listing Items (Repeatable Meta Fields)

Each business item can contain:
- Title
- Link/Website URL
- Main Phone Number
- Full Address (for map location)
- Latitude (auto-generateable)
- Longitude (auto-generateable)
- Image (100x100px preferred)
- Open in new window (checkbox)
- Description
- Location/Subtitle
- Upvote count

## Display Modes

- **All Lists Mode:** Display all created lists on one page
- **Single List Mode:** Display one specific list
- **Category Mode:** Display lists filtered by category
- **Map Only Mode:** Display only the map with markers, no list items

## Display Templates (4 Responsive Designs)

1. **Simple Template** - Default minimalist design
2. **Style 1** - Alternative layout
3. **Style 2** - Enhanced visual design with more styling
4. **Style 3** - Advanced layout with additional features
5. **Map Only** - Dedicated map-only display

All templates are fully responsive and use Packery.js for masonry grid layout.

## Shortcode: `[qcpnd-directory]`

### Supported Parameters

| Parameter | Description | Values |
|-----------|-------------|--------|
| `mode` | Display mode | "all", "one", "category", "maponly" |
| `list_id` | ID of specific list | (for mode="one") |
| `style` | Template | "simple", "style-1", "style-2", "style-3" |
| `column` | Column layout | 1, 2, 3, 4 |
| `min_width` | Minimum width in pixels | number |
| `orderby` | List order | "date", "ID", "title", "modified", "rand", "menu_order" |
| `order` | Sort direction | "ASC", "DESC" |
| `search` | Enable search | "true", "false" |
| `item_count` | Show item count | "on", "off" |
| `top_area` | Show top area | "on", "off" |
| `upvote` | Show upvotes | "on", "off" |
| `list_img` | Show list images | "true", "false" |
| `category` | Filter by category slug | slug |
| `item_orderby` | Sort items by | "", "title" |
| `mask_url` | Mask URLs | "on", "off" |
| `enable_embedding` | Enable embed option | "true", "false" |
| `show_phone_icon` | Show phone icon | 1, 0 |
| `show_link_icon` | Show link icon | 1, 0 |
| `main_click_action` | Main action | 1 (website), 0 (call), 3 (nothing) |
| `phone_number` | Show phone | 1, 0 |
| `map` | Map display | "show", "hide" |
| `showmaponly` | Map-only mode | "yes", "no" |

## Map Integration

### Google Maps Support
- Requires API key setup
- Uses Google Maps JavaScript API
- Supports Places API (new version)
- Map ID support (Pro feature)
- Auto-complete address suggestions
- Marker clustering
- Geolocation detection

### OpenStreetMap (OSM) Support
- Free alternative to Google Maps
- Uses Leaflet.js library
- Marker clustering with MarkerCluster plugin
- No API key required

### Map Features
- Address auto-completion with geocoding
- Automatic latitude/longitude generation
- Map markers with bouncing animation on hover
- Info windows display on hover
- GDPR privacy policy acceptance before map load
- Distance-based search (Pro feature)

## Admin Settings Pages

### General Settings Tab
- Google Map API Key configuration
- Google Map ID setup (Pro feature)
- Places API (New) option
- OpenStreetMap toggle
- Enable/Disable top area
- Add New button toggle
- Add New button link configuration
- RTL (Right-to-Left) direction support
- GDPR privacy policy acceptance

### Language Settings Tab
Customizable button labels:
- "Add New"
- "Share List"
- "View Site"
- "Please provide location"
- "Please provide Distance value"
- "No result found"
- GDPR policies text

### Custom CSS Tab
- Inline custom CSS editor

### Custom JavaScript Tab
- Inline custom JavaScript editor

## Shortcode Generator Feature

### Visual Editor Integration
- Pop-up modal interface in page/post editor
- [SBD] button in editor toolbar
- Generates shortcodes with interactive UI

### Generator Options
- Mode selection dropdown
- List selector for single list mode
- Category selector for category mode
- Map display checkbox
- Template style selector
- Column selector (1-4 columns)
- Minimum width input
- List ordering options
- Item ordering options
- Phone icon toggle
- Link icon toggle
- Main click action selector

## Gutenberg Block Support

**Block Type:** `qcpd-sbd/render-shortcode-button`
- Native Gutenberg block integration
- Shortcode generator button in block editor
- Full support for modern block editor

## Embed Feature

### Embed/Share Functionality
- Embed button for sharing listings
- Modal dialog for embed code generation
- Customizable embed sizes (responsive options)
- Auto-generated embed code
- Separate "Embed List" page creation
- Share button on frontend listings

## Bulk Import Feature

### CSV Import Module
- Admin menu under Settings > Import
- CSV file upload capability
- Bulk import for creating new lists
- Sample CSV file download
- UTF-8 encoding support

## Frontend Display Features

- **Search Functionality:** Live on-page search
- **Filtering:** Category and item filtering options
- **Upvote System:** Users can upvote listings
- **Tap to Call:** Click-to-call functionality for phone numbers
- **Click to Visit:** Direct links to business websites
- **Responsive Grid:** Masonry layout using Packery.js
- **Image Display:** Business thumbnail images
- **Item Count:** Display count of items in list
- **Location Display:** Show business location/subtitle

## Admin Features

### Custom Columns in Admin List
- Title
- Number of Elements (item count)
- Shortcode display (both single list and all lists examples)
- Date

### Custom Menu Structure
- Main menu: "Simple Business Directory"
- Submenus: All Lists, New List, List Categories, Settings, Import, Help

## Assets & Resources

### CSS
- `directory-style.css` - Main styles
- `directory-style-rwd.css` - Responsive design
- `font-awesome.min.css` - Icons
- `admin-style.css` - Admin panel styles

### JavaScript
- `directory-script.js` - Main functionality
- `directory-script-for-map.js` - Google Maps integration
- `directory-openstreet-script-for-map.js` - OpenStreetMap integration
- `packery.pkgd.js` - Grid layout library

### External Libraries
- jQuery
- Packery (masonry layout)
- Leaflet.js (OpenStreetMap)
- MarkerCluster (map markers)
- Font Awesome
- Google Maps API
- Select2 (dropdown enhancement)

## Pro Features (Available in Premium)

- Auto-generate title, subtitle, thumbnail from website URL
- Auto-generate latitude/longitude from address
- Multi-page mode (vs. single-page)
- 16+ templates (vs. 4 in free)
- Front-end user dashboard
- User registration and login
- Paid/Free listing options
- Monetization (PayPal, Stripe integration)
- Radius/distance search
- Live instant search
- Live instant filtering
- Featured items
- Custom fields (up to 5)
- Widgets (3 widgets: Latest, Most Popular, Random)
- CSV import/export
- Schema.org rich snippets (JSON-LD)
- Pagination
- Rating/review system with custom icons
- Advanced filtering options

## Compatibility & Integration

- Compatible with all WordPress themes
- Works with page builders:
  - Gutenberg (native block)
  - Elementor
  - Visual Composer
  - Beaver Builder

## Security Features

- Input sanitization (sanitize_text_field, sanitize_email)
- Nonce verification for AJAX calls
- Proper capability checks
- Safe SQL queries using WP_Query
- XSS protection with esc_html(), esc_attr(), esc_url()
