# WP Google Map Plugin - Feature Analysis

**Plugin Name:** WP Maps (WP Google Map Plugin)
**Version:** 4.9.1
**Author:** WePlugins (flippercode)
**License:** GPLv2 or later
**Requirements:** WordPress 4.5+, PHP 5.6+

**Description:** A fully customizable WordPress plugin for creating unlimited Google Maps and OpenStreetMap/Leaflet maps with custom markers, filterable location listings, store locators, and dynamic infowindows.

---

## Map Creation & Display

### Map Features
- Create unlimited maps with shortcode support
- Display maps in posts, pages, widgets, and custom post types
- Auto-center maps by visitor location or assigned locations
- Show/hide markers on load

### Map Providers
- Google Maps (API key required)
- OpenStreetMap/Leaflet (no API key required)
- Mapbox support

### Map Types
- Roadmap
- Satellite
- Hybrid
- Terrain

## Marker & Icon Management

### Marker Features
- Custom marker icons (PNG and SVG support)
- 100+ pre-built colorful marker icons
- SVG icon customization (fill color, stroke color/width)
- Marker clustering for dense areas
- Marker animations (bounce, drop)
- Advanced marker settings with z-index control
- Marker categories for organization

## Location Management

### Location Features
- Add unlimited locations with title and address
- Latitude/longitude coordinates
- City, state, country, postal code
- Custom infowindow messages (HTML support)
- Location-specific settings
- Extra/custom fields
- Draggable marker option
- Default infowindow open setting
- Author tracking

### Location Import/Export
- Bulk import locations via CSV
- Sample CSV download for template
- Export to CSV, JSON, XML, Excel
- Location permissions management

## Infowindow Features

### Infowindow Options
- Click or hover triggered infowindows
- CodeMirror editor for HTML content
- Multiple infowindow designs/layouts
- Custom HTML support with placeholders
- Infowindow positioning and sizing control

### Available Placeholders
- {location_title}
- {location_address}
- {get_directions_link}
- Custom extra fields

## Listing & Filtering

### Listing Features
- Display location listings below maps
- Built-in search functionality
- Category-based filtering
- Sort options (ascending/descending)
- Per-page item limit selection
- Customizable listing layout
- Click listing item to show infowindow
- Multiple listing item skin designs

## Map Customization

### Style Options
- Custom map dimensions (width/height)
- Zoom level control
- Custom color schema and themes
- Snazzy Maps style integration
- CSS customization support
- Primary and secondary color settings
- Map UI customization

## Map Controls & Interactions

### Available Controls
- Zoom control (enable/disable)
- Fullscreen button
- Map type selector
- Scale display
- Street View support
- 45° imagery support
- Search box/place search control
- "Locate Me" (geolocation) button
- Drag/pan control
- Scroll zoom
- Keyboard shortcuts
- Camera control with position settings

## Advanced Map Features

### Special Features
- GeoJSON Support (upload and display shapes/regions)
- Traffic, Transit, Bicycling layers
- Limit Panning (restrict map movement)
- Drawing Tools (polygons, polylines)
- Overlapping Marker Spider Effect
- Route Direction between locations
- Post Geotags (display posts on maps)

## Route Management

### Route Features
- Create routes between locations
- Start and end locations
- Waypoints (up to 8 locations)
- Stroke color, opacity, weight
- Travel mode selection
- Unit system settings
- Optimize waypoints option

## Shortcode

### Main Shortcode
```
[put_wpgm id="map_id"]
[put_wpgm id="1" category="2,3"]
```

- Map ID parameter
- Category filtering via shortcode attributes
- Customizable shortcode attributes

## Widgets

### WP Maps Pro Widget
- Display maps in sidebars
- Select map from dropdown
- Widget-specific settings
- Support for all post types

## Page Builder Integration

### Supported Builders
- WPBakery (Visual Composer)
- Elementor
- Divi Builder
- Gutenberg Blocks (v4.6.1+)
- Brizy
- Beaver Builder

### Theme Compatibility
- Astra, Avada, OceanWP, GeneratePress, Hello Elementor

## Admin Features

### Admin Menu Structure
- Overview (Dashboard)
- All Locations
- Add Location
- Import Locations
- Create Map
- All Maps
- Marker Categories
- Manage Routes
- Settings
- Integrations
- Permissions

### Settings Categories
- Map Provider Selection
- API Key Management
- Language/Localization
- Script Placement (header/footer)
- Script Minification
- Meta Box Display Control
- Advanced Marker Support
- GDPR Compliance Mode

## Permissions & Role Management

### Configurable Permissions
- Map Overview access
- Add/Edit Locations
- Manage Locations
- Import Locations
- Create Maps
- Manage Maps
- Drawing management
- Add/Manage Marker Categories
- Add/Manage Routes
- Plugin Settings

## Third-Party Integrations

### Analytics Integrations
- Google Analytics 4 (GA4)
- Microsoft Clarity
- Meta Pixel (Facebook Pixel)

### Automation
- Zapier integration with webhook support
- Marker click event tracking
- Data payload sending

## Database Tables

### Custom Tables
- `wp_create_map` - Map configuration
- `wp_map_locations` - Location data
- `wp_group_map` - Marker categories
- `wp_map_routes` - Route information

## Security & Compliance

### Security Features
- Nonce verification on forms
- User capability checks
- Input sanitization and validation
- WP_Filesystem usage for file operations

### GDPR Features
- Cookie acceptance checks
- Privacy-friendly data handling
- Optional GDPR mode in settings

## Premium Features (Pro Version)

### Pro Features
- CSV import/export
- Advanced styling options
- Additional themes
- Extended marker customization
- Premium integrations
- Pro feature UI with upgrade prompts

## Internationalization

### Language Support
- Fully translatable interface (15+ languages)
- Text domain: 'wp-google-map-plugin'
- RTL support ready
- Translation hooks and filters

## Developer Features

### Hooks & Filters
- `wpgmp_extensions`
- `wpgmp_map_navigation`
- `wpgmp_location_update_permission`
- `wpgmp_route_navigation`
- `wpgmp_settings_navigation`
- `wpgmp_integrations_list`
- `wpgmp_apply_placeholders`
- `wpgmp_is_feature_available`

## Technical Implementation

### Architecture
- MVC pattern using custom FlipperCode framework
- Model-View-Controller separation
- Factory pattern for object creation
- Modular design with 14+ core modules

### Database
- Custom tables with proper charset collation
- Serialized data storage for complex settings
- Base64 encoding for sensitive infowindow messages
- Multisite support with proper table prefixes

### Performance
- Script minification option
- Conditional script loading
- Lazy loading support
- jQuery masonry support for layouts
