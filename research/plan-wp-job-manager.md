# WP Job Manager Plugin - Feature Analysis

**Plugin Name:** WP Job Manager
**Version:** 2.4.0
**Author:** Automattic
**License:** GPL2+
**Requirements:** WordPress 6.4+, PHP 7.4+

**Description:** A lightweight, shortcode-based job listing plugin that enables users to add, manage, and categorize job listings using the familiar WordPress UI. Supports frontend job submission, AJAX-powered filterable job listings, and employer dashboards.

---

## Custom Post Types

| Post Type | Slug | Description |
|-----------|------|-------------|
| Job Listings | `job_listing` | Main job listings with custom capabilities |
| Guest Users | `job_guest_user` | Store guest user data for non-logged-in submitters |

## Custom Taxonomies

| Taxonomy | Slug | Description |
|----------|------|-------------|
| Job Categories | `job_listing_category` | Hierarchical job categories |
| Job Types | `job_listing_type` | Non-hierarchical job types |

## Job Listing Management

### Admin Features
- Job listing management from WordPress admin
- Bulk actions (approve, expire, mark filled/not filled)
- Customizable admin list view columns with sortable fields
- Post status control (draft, pending, published, custom)
- Expiry management with automatic job expiration
- Filled status tracking
- Preview system before publication

### Frontend Job Submission
- Submit job form for guest and registered users
- Job dashboard for employers to manage listings
- Job editing from frontend
- Job duplication
- Preview before submit
- Scheduled listings
- Job renewal with configurable window
- Multi-step submission

### Job Application Handling
- Email or URL-based applications
- Company logo upload
- Application details display
- RSS feeds for job listings and search results

## Search and Filtering

### AJAX-Powered Search
- Keyword search (searchable meta keys configurable)
- Location search with Google Maps integration
- Category and type filtering
- Remote position filtering
- Live AJAX filtering
- Featured first option

### Filter Display Options
- Show/hide filters
- Category multi-select
- Pagination types (numbered or "Load More")

## Job Dashboard

### Dashboard Features
- Job listing display for logged-in users
- Job statistics (views, search impressions)
- Action menu (edit, mark filled, delete, duplicate)
- Pagination
- Stats overlay with daily charts
- View count and search impression tracking

### Available Actions
- Edit job listing
- Mark as filled/not filled
- Delete job listing
- Duplicate job listing
- View statistics
- Renew expiring jobs

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[jobs]` | Display filterable job listings |
| `[job]` | Display single job |
| `[submit_job_form]` | Frontend job submission form |
| `[job_dashboard]` | Employer job management dashboard |
| `[job_summary]` | Display job summary box |
| `[job_apply]` | Display job application area |

### [jobs] Shortcode Attributes
- `per_page` - Number of listings per page
- `orderby` - Order by featured, date, title
- `order` - ASC or DESC
- `show_filters` - Display filter UI
- `show_categories` - Display category filters
- `show_category_multiselect` - Multi-category selection
- `show_pagination` - Show pagination links
- `show_more` - Show "Load More" button
- `categories` - Limit to specific categories
- `job_types` - Limit to specific job types
- `featured` - Filter for featured only
- `filled` - Filter for filled status
- `remote_position` - Filter for remote positions
- `location` - Default location filter
- `keywords` - Default keyword filter
- `featured_first` - Show featured first

## Widgets

### Recent Jobs Widget
- Display recent job listings in sidebar
- Keyword and location filters
- Remote position filter
- Number of listings setting
- Show company logo option

### Featured Jobs Widget
- Display featured job listings
- Sort by date, title, author, random
- Sort direction setting
- Show company logo option

## Email Notifications

### Email Types
- Admin new job notification
- Admin updated job notification
- Admin expiring job notification
- Employer expiring job notification
- Daily email notices

### Email Features
- Rich HTML email templates
- Plain-text fallback
- Custom styling
- Scheduled delivery via cron

## Job Statistics & Analytics

### Statistics Features
- Page views tracking
- Search impressions counting
- Historical data (up to 180 days)
- Dashboard display
- Admin display
- Job overlay stats
- Anonymous data collection
- AJAX-based stats logging

## Advanced Features

### Promoted Jobs
- Mark jobs as promoted
- Promotion status handling
- Webhook-based status updates
- REST API integration
- Promotion filtering

### Salary Fields
- Enable salary field
- Salary currency
- Salary unit (year, month, week, hour)
- Currency customization
- Default currency and unit

### Additional Fields
- Remote position marking
- Display location address
- Scheduled publishing

### Security
- Google reCAPTCHA v2/v3
- File upload validation
- AJAX file upload
- Guest user sessions
- Access tokens
- Cookie management

## Submission Settings

### Requirements
- Account required toggle
- Account creation during submission
- Auto-generate usernames
- Standard password email
- Registration role assignment

### Moderation Options
- Submission requires approval
- Allow pending edits
- Allow published edits (none, without approval, with moderation)

### Submission Rules
- Listing duration
- Listing limit per user
- Renewal window
- Terms & conditions

## Display & Formatting

### Content Display
- Relative dates or WordPress default
- Hide filled positions
- Hide expired content
- Hide expired listings
- Strip shortcodes from description

### Location Display
- Google Maps integration
- Full address display
- API key configuration

## REST API Support

### REST Features
- Full REST API for job_listing post type
- Field authorization
- Response filtering
- Meta field exposure

## Third-Party Integrations

### Supported Plugins
- Jetpack (sitemap integration)
- WPML (multi-language)
- Polylang (multi-language)
- Yoast SEO
- All in One SEO Pack
- Related Posts for WP
- WP All Import
- WordPress.com

## AJAX Handlers

### Endpoints
- `job_manager_get_listings` - Fetch filtered listings
- `job_manager_upload_file` - Handle file uploads
- `job_manager_search_users` - User search
- `job_dashboard_overlay` - Load stats overlay
- `job_manager_log_stat` - Log statistics

## Cron Jobs

### Scheduled Tasks
- `job_manager_check_for_expired_jobs` - Hourly expiration check
- `job_manager_delete_old_previews` - Daily preview cleanup
- `job_manager_email_daily_notices` - Daily email notices
- `wp_job_manager_promoted_jobs_notification` - Promoted job updates

## Post Meta Fields

### Job Metadata
- `_job_location` - Job location string
- `_application` - Application email or URL
- `_company_name` - Company name
- `_company_website` - Company website URL
- `_company_tagline` - Company tagline
- `_company_twitter` - Company Twitter handle
- `_featured` - Featured job indicator
- `_filled` - Job filled status
- `_remote_position` - Remote position indicator
- `_job_expires` - Job expiration date
- `_salary` - Job salary
- `_salary_currency` - Currency code
- `_salary_unit` - Pay period unit
- `_promoted` - Promotion status

## Technical Implementation

### Main Classes
- `WP_Job_Manager` - Main plugin class
- `WP_Job_Manager_Post_Types` - Post type registration
- `WP_Job_Manager_Shortcodes` - Shortcode handlers
- `WP_Job_Manager_Ajax` - AJAX handlers
- `WP_Job_Manager_Forms` - Form processing
- `WP_Job_Manager_Settings` - Settings management

### Feature Classes
- `Job_Dashboard_Shortcode` - Employer dashboard
- `WP_Job_Manager_Stats` - Statistics system
- `WP_Job_Manager_Promoted_Jobs` - Job promotion
- `WP_Job_Manager_Email_Notifications` - Email system
- `WP_Job_Manager_Geocode` - Location geocoding
- `WP_Job_Manager_Recaptcha` - CAPTCHA support

## Premium Extensions

### Available Add-ons
- Applications - Candidate application management
- WooCommerce Paid Listings - Monetize with paid packages
- Resume Manager - Resume submission and listings
- Job Alerts - Email job alerts for users
- Job Manager Pro Bundle
