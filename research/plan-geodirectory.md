# GeoDirectory Plugin - Feature Analysis

**Plugin Name:** GeoDirectory
**Version:** 2.8.151
**Author:** AyeCode Ltd
**License:** GPLv2+
**Requirements:** WordPress 5.0+, PHP 5.6+, MySQL 5.0+

**Description:** A professional, scalable WordPress Business Directory Plugin for creating location-based listing directories. Supports classified ads, real estate listings, job boards, restaurant directories, and many other use cases.

---

## Custom Post Types & Taxonomies

### Post Types
| Post Type | Slug | Description |
|-----------|------|-------------|
| Places | `gd_place` | Default business listing post type |

### Taxonomies
| Taxonomy | Slug | Description |
|----------|------|-------------|
| Categories | `gd_placecategory` | Hierarchical category system |
| Tags | `gd_place_tags` | Flat tag taxonomy |

Multi-CPT Support: Create unlimited custom listing post types via Custom Post Types add-on.

## Core Directory Features

### Listing Management
- Front-End Listing Submission with drag-and-drop form builder
- Bulk CSV Import/Export
- Draft Management with auto-save and revisions
- Multiple approval statuses and workflows
- Private Listing Option (hide address)
- Online-Only Listings support
- Bulk Actions for managing multiple listings

### Listing Images & Media
- Multiple Image Gallery with lightbox
- Image Masonry Layout
- Featured Images
- Image Lazy Loading
- Configurable file size restrictions
- External Image Support
- Video Embedding (Matterport, YouTube, etc.)
- Image Format Support: JPG, PNG, GIF, WebP, AVIF, SVG

### Listing Metadata
- Business Hours with timezone support
- Address Fields (street, city, region, country, postal code, lat/lng)
- Featured Listing Badge
- Custom Badge System
- Special Offers Support
- Service Distance (geo-radius)

## Mapping & Location Features

### Google Maps Integration
- Interactive Maps
- Custom Map Styles (add-on)
- Street View Support
- Custom Markers (icons, colors, sizing)
- Map Popup Info Windows
- Multiple Maps per page
- Marker Clustering (add-on)
- Zoom Controls
- Directions Integration

### OpenStreetMap (OSM) Support
- Leaflet-based mapping
- Custom layer styles
- Server-side marker clustering
- Routing Integration
- Keyboard navigation support

### Location Search
- Proximity Search with distance radius
- "Near Me" Search (client geolocation)
- Multiple Location Support (add-on)
- Country/Region/City Filters
- Haversine formula for distances
- Location Autocomplete
- Manual Location Entry

## Search & Filtering

### Search Functionality
- Full-Text Search
- Advanced Search Filters (add-on)
- AJAX Search with real-time suggestions
- Smart Autocomplete
- Multiple Field Search
- Fast AJAX (mu-plugin option)
- Category & Tag Filtering

### Sorting Options
- Relevance
- Newest/Recently Updated
- Rating (highest rated first)
- Distance (nearest first)
- Modified Date
- Alphabetical (A-Z)
- Featured First
- Custom sort fields per post type

### Filter Widgets
- Rating Filter
- Business Hours Filter ("Open Now")
- Custom Field Filters
- Price Range Filter (add-on)
- Proximity Filters
- Multi-Select Filters
- Filter Count Badges

## Reviews & Ratings System

### Review Management
- 1-5 Star Rating System
- FontAwesome Icon Styles
- Multiple Rating Categories (add-on)
- Review Images
- Review Moderation
- Review Limits (require account, prevent duplicates)
- Email Notifications
- Review Editing
- Review Archive

### Rating Display
- Aggregate Ratings
- Rating Count Display
- Customizable Star Rendering
- Review Carousel
- Schema Markup for rich snippets
- Embeddable Rating Badge (add-on)

## User & Submission Management

### User Functionality
- Guest Submissions
- Front-End User Registration
- User Dashboard
- Profile Management
- Email Verification
- User Roles Support
- Favorites/Saved Lists (add-on)

### Listing Submission Workflow
- Drag-Drop Form Builder
- Conditional Fields
- Field Validation with custom patterns
- Required/Optional Fields
- 40+ Input Field Types
- Duplicate Prevention (add-on)
- Image Upload in Forms
- Automatic Geolocation
- Auto-publish or require approval

### Claim & Verification
- Claim Listing Manager (add-on)
- Owner Verification Badge
- Claimed Listing Status
- Franchise Support (add-on)

## Custom Fields System (40+ Field Types)

### Available Field Types
- Text input, Textarea, URL, Email, Phone
- Number, Price, Date picker, Time picker
- Checkbox, Radio buttons, Dropdown select, Multi-select
- File upload, Image upload, Multifile upload
- Textarea with editor
- Social media links
- Google Places address
- Business hours
- Repeater fields
- Location field

### Field Management
- Field Groups by sections
- Drag-drop field reordering
- Field Visibility (by post type or user role)
- Field Permissions (admin use only, exclude from search)
- Field Icons
- Predefined Fields
- Field Validation (regex, required, type checking)
- Field Defaults
- CSS Classes
- Conditional Logic
- Field Sorting

## Widgets & Blocks (40+)

### Archive/Loop Widgets
- GD > Listings Block
- GD > Simple Archive Item
- GD > Archive Item Section
- GD > Loop
- GD > Loop Paging
- GD > Loop Actions

### Search & Navigation
- GD > Search
- GD > Categories
- GD > Tags
- GD > A-Z Search
- GD > Output Location
- GD > Single Taxonomies

### Map Widgets
- GD > Map
- GD > Map Pinpoint
- GD > Near Me Map

### Listing Detail Widgets
- GD > Post Title, Content, Images, Address, Meta
- GD > Post Rating, Features, Badge, Distance
- GD > Post Directions, Fav
- GD > Single Reviews, Next Prev
- GD > Single Tabs, Closed Text

### Form & Action Widgets
- GD > Add Listing
- GD > Ninja Forms
- GD > Author Actions
- GD > Best Of
- GD > Recently Viewed
- GD > Recent Reviews
- GD > Notifications
- GD > Dynamic Content
- GD > Dashboard
- GD > Report Post

## Page Builder Compatibility

### Supported Page Builders
- **Gutenberg** - Native block support and custom blocks
- **Elementor** - Deep integration with widgets and theme builder
- **Bricks Builder** - Native element support
- **Beaver Builder** - Compatible
- **Divi** - Works with layouts
- **Breakdance** - Supported
- **Oxygen** - Compatibility support
- **GenerateBlocks** - Integration support

### Theme Compatibility
- Works with any WordPress theme
- Bootstrap 5 Integration (AUI CSS framework)
- Theme-Specific Templates
- BlockStrap Support
- Theme Overrides

## Design Features

- Design Styles Mode (traditional/modern UI)
- Bootstrap 5 Classes
- Responsive Design
- Dark Mode Support
- Custom Colors
- Typography options
- Spacing utilities
- Borders & Shadows
- Rounded Corners
- Animations
- FontAwesome icon integration

## Administration & Settings

### Dashboard
- Admin Dashboard with statistics
- Quick Links
- Notifications
- Setup Wizard

### Settings Pages
- General Settings
- Design Settings
- API Settings
- Email Settings
- Permalink Settings
- Import/Export
- Status Report

### CPT Management
- Create Custom Post Types (no-code)
- Custom Fields Builder
- Field Ordering
- Field Groups
- Tab Builder
- Sorting Fields

## Advanced Features

### Business Hours Management
- Week Schedule
- Multiple Hours per Day
- Timezone Support
- "Open Now" Filter
- Customizable Display
- Holidays/Closures

### SEO Features
- Schema Markup (LocalBusiness, Review)
- Open Graph Meta Tags
- XML Sitemaps
- SEO Permalink Settings
- Meta Title/Description
- Yoast SEO / All in One SEO Compatible

### Privacy & GDPR
- Privacy Policy Support
- GDPR Compliance (data export/deletion)
- Complianz Integration
- Data Retention Policies
- Borlabs Cookie Support

## API & Developer Features

### REST API
- Full REST API support
- CRUD operations on listings
- Category and tag management
- Custom API endpoints
- API key authentication
- Role-based access control
- Dynamic data exposure
- Query parameters
- JSON response with field filtering

### WordPress Hooks
- 100+ custom action hooks
- 150+ custom filter hooks
- Template hooks
- Admin hooks
- Query hooks
- Output hooks

### Plugin Integrations
- Elementor
- BuddyPress (add-on)
- WP All Import
- Invoicing Plugin
- WPML
- Yoast SEO / All in One SEO
- LearnDash
- Advanced Custom Fields

## Email Notifications

- Custom Email Templates
- Admin Notifications (submissions, reviews, reports)
- User Notifications (status changes)
- Dynamic Variables
- HTML Email Support
- Configurable Frequency

## Database Structure

### Custom Tables
- `geodir_api_keys` - REST API authentication
- `geodir_attachments` - Image/file metadata
- `geodir_custom_fields` - Field configuration
- `geodir_custom_sort_fields` - Sortable field definitions
- `geodir_tabs_layout` - Tab configuration
- `geodir_post_review` - Review data
- `geodir_post_reports` - Abuse reports

## Performance Optimizations

- Object Caching
- Query Optimization
- Big Data Mode (50k+ listings)
- Lazy Script Loading
- Post Revision Control
- Image Optimization
- Redis Cache Support
- Query Result Caching

## Premium Add-ons

1. **Location Manager** - Multi-location/global directory
2. **Pricing Manager** - Monetization with payments
3. **Custom Post Types** - Additional listing types
4. **MultiRatings & Reviews** - Multiple rating categories
5. **Advanced Search Filters** - Complex field filters
6. **BuddyPress Integration** - Social networking
7. **Claim Manager** - Business owner verification
8. **Marker Cluster** - Map clustering
9. **Duplicate Alert** - Prevent duplicates
10. **Custom Map Styles** - Advanced maps
11. **Social Importer** - Import from Facebook/Yelp/Google
12. **reCAPTCHA** - Spam prevention
13. **Franchise Manager** - Multi-location chains
14. **List Manager** - User-created collections
15. **Compare Listings** - Side-by-side comparison
