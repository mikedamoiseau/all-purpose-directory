# Regions for WP Job Manager Plugin - Feature Analysis

**Plugin Name:** Regions for WP Job Manager
**Slug:** wp-job-manager-locations
**Version:** 1.18.4
**Author:** Astoundify
**License:** GPL
**Requirements:** WordPress 4.7+, WP Job Manager

---

## Taxonomy & Organization

### Job Listing Regions Taxonomy (`job_listing_region`)
- Hierarchical taxonomy for organizing job listings
- Customizable region names and hierarchy
- REST API enabled for Gutenberg editor compatibility
- Capability mapping to `manage_job_listings`
- Dynamic URL slugs: `/job-region/` with customizable permalinks

### Resume Regions Taxonomy (`resume_region`)
- Parallel taxonomy for resume listings
- Identical hierarchy and features
- URL slugs: `/resume-region/`
- Introduced in version 1.17.7

## Admin Settings & Configuration

| Setting | Description | Default |
|---------|-------------|---------|
| `job_manager_enable_regions_filter` | Replace entered address with selected region on output | Enabled |
| `job_manager_regions_filter` | Convert text location search to dropdown menu | Disabled |
| `resume_manager_enable_regions_filter` | Controls region display filtering for resumes | Enabled |

## Frontend Features

### Job Submission Form
- **Region Selection Field** (job_region)
  - Field Type: `term-select` (custom field type)
  - Label: "Job Region"
  - Required: Yes
  - Priority: 2.5
  - Uses WP Job Manager's custom field system
  - Renders with Select2 or Chosen dropdown library

### Resume Submission Form
- **Region Selection Field** (resume_region)
  - Field Type: `term-select`
  - Label: "Region"
  - Required: Yes
  - Priority: 2.5
  - Identical to job region implementation

### Search Filters

#### Job Listings Filter Dropdown
- Displays region dropdown at end of job filters
- Shows "All Regions" as default option
- Hierarchical display
- Dynamic update of results on region selection
- Only active if `job_manager_regions_filter` setting is enabled

#### Resume Listings Filter Dropdown
- Parallel implementation for resumes
- Shows "All Regions" as default option
- Only active if `resume_manager_regions_filter` setting is enabled

### Location Display

#### Job Listing Location Output
- Filter: `the_job_location`
- On single job pages: displays as linked region names
- On listing pages: displays as comma-separated region names
- Replaces text location with region name when setting enabled

#### Resume Location Output
- Filter: `the_candidate_location`
- Parallel implementation for resumes
- Same output formatting as jobs

### Region Archive Pages
- **Job regions URL:** `/job-region/{region-name}/`
- **Resume regions URL:** `/resume-region/{region-name}/`
- Customizable via permalink settings
- Auto-presets selected region in dropdown
- Pre-filters listings to selected region
- Can be used alongside job categories and types

## Widgets

### Job Regions Widget
- **Class:** `Astoundify_Job_Manager_Regions_Widget`
- **Widget ID:** `ajmr_widget_regions`
- **Widget Name:** "Job Regions"
- **Description:** "Display a list of job regions."
- Customizable title field
- Displays category list of job regions
- Hierarchical display support
- Hides empty regions by default (configurable)
- Output cached for performance

## Advanced Filtering & Queries

### AJAX Form Filtering
- **Search by Region Parameter:** `search_region`
- Intercepts `form_data` from AJAX requests
- Builds tax_query for region filtering
- Operator: `IN` (allows multiple regions)
- Supports single integer or array of term IDs

### URL Query Parameter Support
- **Parameter:** `selected_region`
- GET parameter handling for region pre-selection
- Persists across AJAX updates
- Works with shortcode attributes

### WPJM Alerts Integration
- Supports `search_region` argument for alert subscriptions
- Handles empty array checks to prevent query pollution
- Custom filter text: "in {region-name}"
- RSS feed arguments updated with region parameter

### RSS Feed Support
- Filter: `job_feed_args`
- Parameter: `job_region` (query string)
- Creates region-specific RSS feeds
- Tax query applied to feed queries

## Hooks & Filters

### Action Hooks
- `plugins_loaded` - Load textdomain
- `init` - Register taxonomies (priority 0)
- `wp` - Initialize template features
- `wp_enqueue_scripts` - Load frontend scripts
- `job_manager_job_filters_search_jobs_end` - Add region filter dropdown
- `resume_manager_resume_filters_search_resumes_end` - Add resume region dropdown
- `widgets_init` - Register regions widget

### Filter Hooks (Customizable)
- `job_manager_regions_dropdown_args` - Customize job region dropdown HTML args
- `resume_manager_regions_dropdown_args` - Customize resume region dropdown HTML args
- `job_manager_term_select_field_wp_dropdown_categories_args` - Customize submission form dropdown
- `job_manager_locations_get_terms` - Modify terms query args
- `job_manager_locations_get_term_list_separator` - Change term separator (default: ', ')

## JavaScript Functionality

### Job Regions Handler
- **Object:** `jobRegions`
- Initializes on document ready
- Converts hidden region select to visible filter
- Moves region dropdown to location field placeholder
- Applies Chosen.js or Select2.js library
- Search contains support for better UX
- Minimum results threshold: 10
- Clear button enabled
- Updates listing results on region change
- Resets region selection on form reset

### Resume Regions Handler
- **Object:** `resumeRegions`
- Parallel implementation for resume listings
- Updates `.resumes` container on change
- Triggers `update_results` event

### Library Support
- Auto-detects Select2.js or Chosen.js
- Prefers Select2 if both available
- Loads appropriate library based on WP Job Manager version

## Data Persistence & Form Handling

### Getting Existing Data
- Uses `wp_get_object_terms()` with `fields='ids'`
- Populates edit forms with existing region assignments
- Hooks: `submit_job_form_fields_get_job_data`, `submit_resume_form_fields_get_resume_data`

### Saving Data
- WP Job Manager handles data save via native form processing
- Plugin adds field to form structure
- Uses native WordPress taxonomy assignment

## Plugin Compatibility & Dependencies

### Required Plugins
- WP Job Manager (core plugin)
- Resume Manager (optional, for resume features)

### Compatible Themes
- Any theme declaring `current_theme_supports( 'job-manager-templates' )`
- Specifically built for Jobify and Listify themes

### Library Compatibility
- Select2.js (preferred, modern)
- Chosen.js (fallback, legacy)
- jQuery required

## Technical Implementation

### Class Architecture
- **Main Class:** `Astoundify_Job_Manager_Regions` (Singleton pattern)
- **Child Classes:**
  - `Astoundify_Job_Manager_Regions_Taxonomy` - Registers taxonomies
  - `Astoundify_Job_Manager_Regions_Template` - Frontend features
  - `Astoundify_Job_Manager_Regions_Widgets` - Widget registration

### Security Considerations
- Uses `esc_attr()` for output escaping
- Uses `absint()` for integer validation
- Uses `wp_dropdown_categories()` for safe rendering
- Properly escapes term URLs with `esc_url()`

### Performance Optimizations
- Widget caching via Jobify_Widget class
- Lazy loading of JavaScript
- Minified JS file available (`main.min.js`)
- Select2 library registration deferred
- Hide empty option for widgets

## Internationalization

- **Text Domain:** `wp-job-manager-locations`
- **Language Path:** `/languages`
- Checks WP_LANG_DIR first, then plugin directory
- Supports multiple language files (.mo)

## Known Limitations

- Regions are organization tools, NOT hard filters
- Listings aren't strictly filtered by regions in some contexts
- Radius search disabled when region dropdown enabled
- Resume search by region filter (partially implemented)
