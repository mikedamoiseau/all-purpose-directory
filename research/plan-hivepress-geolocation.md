# HivePress Geolocation Plugin - Feature Analysis

**Plugin Name:** HivePress Geolocation
**Version:** 1.3.9
**Author:** HivePress
**License:** GPLv3
**Requirements:** WordPress 5.0+, PHP 7.4+

---

## Location Search Features

- **Location-based Search:** Search listings, vendors, and requests by geographic location
- **Radius Search:** Configurable search radius (default 15 km/miles)
  - Default radius: 15 units
  - Maximum radius: 100 units (configurable)
  - Optional user adjustment of radius
- **Distance Sorting:** Sort search results by distance from specified location
- **Coordinate-based Filtering:** Uses latitude/longitude metadata for precise location queries
- **Location Field:** Custom location input field with autocomplete support

## Map Features

### Interactive Maps
- Display listings/vendors on interactive maps with multiple providers

### Map Providers
- Google Maps (default with new Places API support)
- Mapbox (alternative provider)
- Legacy Google Places API support

### Map Display Blocks
- Listing map block (single listing view and list page)
- Vendor map block (single vendor view and list page)
- Request map block (if Requests extension is installed)

### Map Markers
- Customizable marker positions
- Marker clustering (with MarkerClusterer Plus)
- Marker spiderfier for overlapping markers
- Info windows/popups with listing/vendor details
- Optional marker icon customization

### Map Controls
- Zoom control
- Fullscreen control
- Navigation controls
- Language-aware map display
- Configurable maximum zoom level (default 18, range 2-20)

### Privacy Feature
- **Location Scatter:** Randomly offset exact coordinates for privacy

## Region/Hierarchy Features

- **Automatic Region Generation:** Generate hierarchical regions from location data
- **Region Taxonomy:** Create term-based regions for countries, regions, districts, cities, and postcodes

### Supported Region Types
- Country
- Region (State/Province)
- Subregion/District (Administrative Area Level 2)
- City/Place (Locality)
- District/Locality (Sublocality)
- Postcode/Postal Code

### Region Features
- Region-specific Search: Filter listings by region
- Hierarchical Navigation: Navigate through region hierarchy
- Region Pages: Automatically create archive pages for regions

## Geocoding & Address Features

### Geocoding Integration
- Google Maps Geocoding API integration
- Mapbox Geocoding API integration
- Reverse geocoding for coordinate to address conversion
- Forward geocoding for address to coordinate conversion

### Address Features
- Address Format Options: Configurable address display format
- Address Hiding (Privacy): Option to hide exact address and show only region
- Address Components: Extract and store address components from geocoding results
- Autocomplete Address Input: Real-time address suggestions during typing

## Browser Geolocation Features

- **"Locate Me" Button:** Automatic location detection
- **GPS Coordinates:** Capture and store latitude/longitude
- **Location Accuracy:** 6 decimal places precision for coordinates
- **User Permission:** Request permission to access device location
- **Fallback Support:** Works with multiple geolocation APIs

## Content Type Support

- **Supported Models:** Listings, Vendors, and Requests (if extension installed)
- **Configurable Activation:** Enable/disable location features per content type
- **Location Attributes:** Automatically adds location fields to supported models

## Search & Filter Features

### Search Forms
Location field added to:
- Listing search forms
- Vendor search forms
- Request search forms (if available)

### Additional Features
- Filter Forms: Location filtering options in filter/sidebar
- Sort Forms: Distance sorting option in sort controls
- Region Filter: Filter by region taxonomy
- Radius Adjustment: Dynamic radius filter in search results

## Admin Settings

### Content Types Selection
- Choose which models use geolocation (Listings/Vendors/Requests)

### Map Configuration
- Map Provider Selection: Choose between Google Maps and Mapbox
- Country Restrictions: Limit searches to specific countries
- Zoom Configuration: Set maximum allowed zoom level (2-20)

### Radius Settings
- Default radius value
- Maximum radius value
- Enable/disable user radius adjustment
- Units: Toggle between kilometers and miles

### Additional Settings
- Sorting: Enable/disable sorting results by distance
- Region Generation: Enable/disable automatic region creation from locations
- Region Type Selection: Choose which region hierarchies to generate
- Address Privacy: Hide exact address display option

### API Configuration

#### Google Maps
- API Key (for frontend geocoding and maps)
- Secret Key (for server-side geocoding)
- Legacy API version option

#### Mapbox
- Public API Key (for frontend maps and geocoding)
- Secret Key (for server-side geocoding)

## Custom Fields

| Field | Type | Description |
|-------|------|-------------|
| Location | Text with autocomplete | Address input with "Locate Me" button |
| Latitude | Hidden numeric | Range: -90 to 90, 6 decimal places |
| Longitude | Hidden numeric | Range: -180 to 180, 6 decimal places |
| Region | Select | Region taxonomy selection |

## Frontend Components & Blocks

- **Listing Map Block:** Display listing on map
- **Vendor Map Block:** Display vendor on map
- **Request Map Block:** Display request on map
- **Map Widget:** Sidebar map display for browse pages

## Related Listings Feature

- **Related by Location:** Show related listings based on location proximity
- **Configurable Criteria:** Admin can set location as default related criteria
- **Installation-aware:** Automatically enables for new installations

## Technical Implementation

### Location Data Storage
- Stored as post meta (hp_latitude, hp_longitude, hp_location)
- Supports decimal precision for accurate positioning
- Hierarchical region assignment via taxonomy

### Search Query Processing
- URL parameters: `location`, `latitude`, `longitude`, `_radius`, `_region`, `_sort`
- Coordinate validation (latitude: -90 to 90, longitude: -180 to 180)
- Radius calculations using geographic formulas

### Distance Calculation
- Haversine formula for latitude/longitude filtering
- Server-side SQL calculation for result ordering
- Per-model radius conversion (km to degrees)

### JavaScript Dependencies
- MarkerClusterer Plus (marker clustering)
- Overlapping Marker Spiderfier (OMS - marker overlap handling)
- jQuery Geocomplete (legacy Google Places support)
- Mapbox GL JS v2.7.0
- Mapbox GL Geocoder v5.0.0
- Mapbox GL Language plugin
- Google Maps JavaScript API

## User-Facing Features

1. Location search with autocomplete
2. Browser-based geolocation detection
3. Interactive map display
4. Region navigation
5. Adjustable search radius (if enabled)
6. Distance-sorted results
7. Map marker popups with entity details
