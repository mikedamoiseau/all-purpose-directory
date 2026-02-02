# WP Job Openings Plugin - Feature Analysis

**Plugin Name:** WP Job Openings
**Version:** 3.5.4
**Author:** AWSM Innovations
**License:** GPLv2
**Requirements:** WordPress 4.8+, PHP 5.6+

**Description:** Super simple Job Listing plugin to manage Job Openings and Applicants on your WordPress site.

---

## Custom Post Types

| Post Type | Slug | Description |
|-----------|------|-------------|
| Job Openings | `awsm_job_openings` | Job listings management |
| Job Applications | `awsm_job_application` | Application storage (non-public) |

## Job Management

### Job Features
- Create/edit/delete job openings
- Set job expiry dates with automatic expiration
- Job title, description, and detailed specifications
- Featured image support (configurable)
- Unlimited job specifications
- Job status tracking (publish, draft, private, expired)

## Listing & Display

### Layout Options
- Grid view layout
- List view layout
- Customizable columns for grid layouts

### Display Features
- Modern, responsive design
- Job search functionality with AJAX
- AJAX-powered filtering and load more
- Recent Jobs widget
- Job view counter
- Conversion rate tracking (applications vs views)

## Application Management

### Default Form Fields
- Full Name
- Email
- Phone
- Cover Letter (textarea)
- CV/Resume upload (file upload)

### Application Features
- Customizable file upload types
- Application listing in admin panel
- View all applications per job
- Bulk application management

### Admin Columns
- Applicant photo/avatar
- Applicant name
- Application ID
- Job applied for
- Submission date/time

## Filtering System

### Filter Options
- Custom job specifications as taxonomy filters
- Configurable filter icons
- Frontend AJAX filtering
- Search across jobs
- Filter reset functionality

### Filter Management
- Create/edit/delete filter categories in settings
- Assign filter options (tags) to specifications
- Icon selection for filters
- Option to make filters clickable

## User Roles & Permissions

### Custom HR Role
- Dedicated HR role for recruitment staff
- HR-specific capabilities for job and application management
- Upload file permissions for application documents

### Capability Management
- Granular permissions for job operations (edit, publish, delete)
- Granular permissions for application operations
- Administrator, Editor, Author, Contributor role support
- Custom role capabilities

## Email Notifications

### Application Notifications
- Applicant confirmation email (customizable templates)
- Admin notification when new application received
- Notification customization in settings

### Email Digest Feature
- Daily email digest with recent applications
- Configurable HR email recipient
- Customizable email subjects and content
- HTML email templates with header/footer
- Template tags for personalization

### Job Expiry Notifications
- Automatic notification when job is about to expire
- Customizable notification template
- Author notification system
- Timezone support for expiry calculations

### Email Customization
- HTML template support with built-in editor
- Custom template tags
- Company name/logo in emails
- Font and styling customization

## Settings & Configuration

### General Settings
- Company name configuration
- HR email address
- Admin notification toggle
- Email digest frequency
- File upload settings
- Acknowledgment message for applicants
- Hide uploaded files from media library

### Appearance Settings
- Job listing page styles
- Job detail page styles
- Layout customization
- Font adjustments

### Job Specifications Settings
- Create unlimited filter specifications
- Assign options/tags to specifications
- Icon selection for each specification
- Make specifications clickable

### Form Settings
- Form field customization
- reCAPTCHA integration support
- File upload type restrictions
- Required field configuration

### Notification Settings
- Applicant notification templates
- Admin notification templates
- Job expiry notification templates
- Custom email templates
- Email header/footer customization

## Shortcode: `[awsmjobs]`

| Parameter | Description | Values |
|-----------|-------------|--------|
| `layout` | Display layout | "grid", "list" |
| `number_of_columns` | Grid columns | number |
| `listing_per_page` | Jobs per page | number |
| `enable_job_filter` | Show filters | "enable", "disable" |
| `search` | Show search | "enable", "disable" |
| `hide_expired_jobs` | Hide expired | "expired" |
| `filter_options` | Specific filters | comma-separated slugs |

## Gutenberg Block

**Job Listing Block** (v3.5.0+):
- Render job listings in Gutenberg editor
- Dynamic block with PHP rendering
- Supports all shortcode attributes
- Filter options configuration
- Search toggle
- Layout selection
- Load more pagination

## AJAX Handlers

### Frontend AJAX
- `jobfilter` - Filter jobs via AJAX
- `loadmore` - Load more jobs
- `awsm_applicant_form_submission` - Submit applications
- `awsm_view_count` - Track job views
- `block_jobfilter` - Filter jobs from block
- `block_loadmore` - Load more from block

### Admin AJAX
- `settings_switch` - Toggle settings
- `awsm_jobs_setup` - Setup wizard
- `awsm_plugin_rating` - Plugin rating

## Third-Party Integrations

### Multilingual Support
- WPML integration
- Polylang integration
- Multi-language job specifications
- Language-aware email notifications

### Spam Protection
- Akismet integration for application forms
- Configurable Akismet protection
- Spam detection and filtering

## SEO Features

### Structured Data (JSON-LD)
- Job posting schema for Google/search engines
- Proper schema.org markup
- Filterable structured data
- Breadcrumb support

### Permalink Management
- Customizable job post type slug
- Archive page options
- Permalink front base customization

## Scheduling & Cron Jobs

### Automatic Job Expiry
- Hourly cron job for checking expired jobs
- Automatic status change to 'expired'
- Timezone-aware expiry calculations
- Configurable expiry dates per job

### Email Digest
- Daily scheduled email digest
- Automatic application summary
- Scheduled via WordPress cron system

## Template System

### Custom Templates
- Single job template
- Archive/listing template
- Job content template
- Block-specific templates
- Mail templates (basic and HTML)
- Theme compatibility templates
- Widget templates

## Widgets

- **Recent Jobs Widget** - Display recent job openings, sidebar-ready
- **Dashboard Widget** - Quick access to job/application metrics

## Admin Dashboard Features

### Overview/Dashboard
- Total jobs count
- Total applications count
- Recent applications display
- Quick stats
- Add-ons information

### Admin Columns (Job Listing)
- Job ID
- Application count (with filter link)
- Job expiry date
- View count
- Conversion rate calculation

## Security Features

### File Management
- Dedicated upload directory for application files
- .htaccess protection (Options -Indexes)
- Hidden file listing
- Configurable file type restrictions
- File access controls

### Nonce & Sanitization
- AJAX request nonce verification
- Input sanitization
- Output escaping
- XHTML compliance

### GDPR Compliance
- Auto-Delete Applications add-on (free)
- Scheduled data cleanup
- Privacy-respecting design

## Developer Features

### Hook System (50+ hooks)
- `awsm_job_openings_args` - Filter job CPT args
- `awsm_job_application_args` - Filter application CPT args
- `awsm_application_form_fields` - Customize form fields
- `awsm_application_form_fields_order` - Reorder form fields
- `awsm_job_structured_data` - Modify JSON-LD schema
- `awsm_jobs_mail_template_tags` - Add custom email tags
- `awsm_jobs_login_redirect` - Customize login redirects

## Premium Features (PRO Pack)

- Form Builder - Create custom application forms
- Shortlist/Reject/Select candidates
- Rate and filter applications
- Email CC for notifications
- Notes and activity logs
- Application export functionality
- Attach uploaded files to notifications
- Shortcode generator with custom settings
- Third-party form integration
- Custom application URLs

## Add-ons

### Free Add-ons
- Docs Viewer Add-on
- Auto-Delete Applications for GDPR Compliance

### Premium Add-ons
- PRO Pack (all-in-one premium features)
- User Access Control Add-on
- Job Alerts Add-on

## Technical Implementation

### Architecture
- Singleton pattern for main classes
- Modular design with separate classes
- Custom post type and taxonomy system
- Meta-based storage for job/application data

### Compatibility
- Tested with 50+ top WordPress themes
- WooCommerce compatibility
- REST API support (show_in_rest)

### Performance
- Transient caching
- Efficient queries
- AJAX-based loading
- Load more pagination
- View counter with AJAX
