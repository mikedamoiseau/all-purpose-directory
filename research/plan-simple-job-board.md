# Simple Job Board Plugin - Feature Analysis

**Plugin Name:** Simple Job Board
**Version:** 2.14.1
**Author:** PressTigers
**License:** GPL-3.0+
**Requirements:** WordPress 5.1+, PHP 7.4+

**Description:** A lightweight WordPress job portal plugin for displaying job listings, managing applications, and creating professional career pages.

---

## Custom Post Types

### Job Post (`jobpost`)
- Main post type for job listings
- Supports: title, editor, excerpt, author, thumbnail, page-attributes
- Public, publicly queryable, REST API enabled
- Customizable slug (default: "jobs")

### Job Applications (`jobpost_applicants`)
- Stores submitted job applications
- Not publicly queryable
- Shown in admin under Job Board menu
- Manages application data and resume storage

## Taxonomies

| Taxonomy | Slug | Description |
|----------|------|-------------|
| Job Categories | `jobpost_category` | Hierarchical taxonomy for organizing jobs |
| Job Types | `jobpost_job_type` | Full-time, Part-time, Contract, etc. |
| Job Locations | `jobpost_location` | Geographic taxonomy for job locations |
| Job Tags | `jobpost_tag` | Flat taxonomy for flexible job tagging |

## Shortcodes

### Main Listing Shortcode: `[jobpost]`

| Parameter | Description | Values |
|-----------|-------------|--------|
| `layout` | Display layout | "grid", "list" (default) |
| `category` | Filter by category | slug |
| `type` | Filter by job type | slug |
| `location` | Filter by location | slug |
| `tag` | Filter by tag | slug |
| `posts` | Jobs per page | number |
| `show_search_form` | Show/hide search | "yes", "no" |
| `show_pagination` | Show/hide pagination | "yes", "no" |
| `order` | Listing order | "ASC", "DESC" |

### Job Details Shortcode: `[job_details]`

| Parameter | Description | Values |
|-----------|-------------|--------|
| `job_id` | Job post ID | number |
| `show_job_form` | Show application form | "yes", "no" |
| `show_job_features` | Show job features | "yes", "no" |
| `show_job_meta` | Show job metadata | "yes", "no" |
| `job_form_description` | Show description | "yes", "no" |

## Admin Features

### Admin Menu Structure
- Job Board (main menu)
  - All Jobs
  - Add New Job
  - Applications
  - Job Categories
  - Job Types
  - Job Locations
  - Job Tags
  - Wizard (setup wizard)
  - Settings
  - Add-ons (marketplace)

### Meta Boxes for Job Posts
- **Job Data Box** - Company information, job details, salary
- **Job Features** - Customizable job specifications/benefits
- **Job Application Fields** - Custom form field builder
- **Application Status** - Track application statuses

## Settings Pages

### General Settings
- Default company logo and details
- Resume maximum file size
- Date format configuration
- CSRF protection settings

### Appearance Settings
- Layout selection (Classical/Modern)
- Container class/ID customization
- Font settings (Google fonts)
- Typography settings
- Theme compatibility options

### Job Features Settings
- Configure default job features
- Merge features into existing jobs
- Customizable feature templates

### Application Form Fields Settings
- Global default form fields
- Field types: text, email, textarea, file, date, phone, select, checkbox, radio
- Required field settings
- Section headings for organization

### Filters Settings
- Enable/disable search form
- Configure visible filters
- Search form layout options

### Email Notifications Settings
- Admin email configuration
- HR email notifications
- Applicant confirmation emails
- Email template customization
- From/Reply-to parameters

### Upload File Extensions Settings
- Allowed file types (.pdf, .doc, .docx, .txt, .rtf, .odt)
- File size limits
- Custom file extension support

### Privacy Settings
- Privacy policy text
- Terms and conditions text
- GDPR compliance options

## Frontend Features

### Job Listing Display
- List view (traditional)
- Grid view (card-based)
- Responsive design
- View More/View Less functionality
- Quick Apply popup
- Quick View functionality
- Search filters (keywords, category, type, location, tag)
- Pagination
- Posted date display

### Job Detail Page
- Job title, description, specifications
- Job metadata (posted date, location, type, category)
- Company details and logo
- Job features display (Classical: bullets, Modern: icons)
- Application form
- Responsive layout

### Application Form
- Customizable fields
- File upload for resumes with validation
- Required field validation (client-side)
- Phone number international formatting (intlTelInput)
- Datepicker for date fields
- Pre-filled data for logged-in users
- Privacy policy acceptance checkbox
- AJAX submission

### Search and Filtering
- Keyword search
- Category dropdown filter
- Job type filter
- Location filter
- Tag filter
- Multi-taxonomy filtering
- Pagination with search state preservation

## Widgets

- **Recent Jobs Widget** - Configurable title, category filter, number of jobs
- **Dashboard Stats Widget** - Quick statistics overview

## Gutenberg Block

- **"SJB Job Listing" block** for page/post editor
- Attributes: Layout, posts, order, search toggle
- Full block editor integration

## AJAX Functionality

- `process_applicant_form` - Application submission, resume upload, email notifications
- `fetch_quick_job` - Quick apply popup loading

## Notification System

### Email Types
- **Admin Notification** - Applicant details, resume reference
- **HR Notification** - Location/category-based notifications
- **Applicant Notification** - Confirmation email

### Features
- From/Reply-to customization
- Customizable templates
- Multipart HTML email support
- Template filters for customization

## Privacy and GDPR Compliance

### Privacy Features
- Privacy policy and terms text fields
- Privacy exporter integration with WP core
- Privacy eraser integration with WP core
- Email-based data export/deletion

### Security Features
- Nonce verification
- CSRF token validation
- Referer validation on AJAX requests
- Input sanitization and output escaping
- Resume file hotlinking protection
- Authentication checks for resume downloads
- File upload validation
- Secure file naming
- XSS protection
- Directory traversal protection

## Internationalization

- Full translation support
- 14+ languages included (English, French, Arabic, Portuguese, Italian, Russian, Chinese, Dutch, Serbian, Swedish, Urdu, Japanese, Polish, Galician)
- RTL language support
- WPML compatibility

## Technical Implementation

### REST API
- Job post type fully exposed to REST API
- REST base: 'jobpost'

### Template System
- Custom template loading system
- Two layout versions (Classical v1, Modern v2)
- Theme override support
- Template hook system

### Hooks and Filters
- `sjb_enqueue_scripts` - Script loading
- `sjb_job_filters_before/after` - Filter timing
- `sjb_job_listing_before/after` - Listing timing
- `sjb_single_job_content_start/end` - Single page content
- `sjb_job_listing_features` - Job features display
- `sjb_job_listing_application_form` - Form display
- `sjb_admin_notification_*` - Admin email customization
- `sjb_hr_notification_*` - HR email customization
- `sjb_applicant_notification_*` - Applicant email customization
- `sjb_uploaded_resume_validation` - Resume validation
- `sjb_shortcode_atts` - Shortcode attribute filtering

## Admin Columns

### Job Listing Columns
- Custom data columns

### Application Listing Columns
- Job Applied For
- Applicant Name (with selected form fields)
- Date Applied
- Status
- Actions (resume download)

### Features
- Sortable columns
- Job application filter by job title

## Premium Add-ons Ecosystem

### Application Management (18 add-ons)
- Email attachments, Resume preview, PDF export
- CSV export, Job alerts, Bulk resume download
- Custom application statuses, Application deadline
- Unique applications, Import/export

### Job Management (5 add-ons)
- Frontend job posting dashboard
- Job expirator, Content replacement
- Job listings import/export

### User Experience (13 add-ons)
- Company details and filtering
- Google Job Search integration
- Salary range filter, Job level filter
- AJAX job search, Featured jobs
- Geolocation search, Refer a friend

### Integration (6 add-ons)
- Divi Builder, Elementor, WPBakery
- Mobile App Connector
- Cloud Storage (Dropbox, Google Drive)

### Communication (2 add-ons)
- Email notification templates
- Individual job email notifications
