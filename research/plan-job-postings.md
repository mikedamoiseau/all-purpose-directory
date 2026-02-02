# Job Postings Plugin - Feature Analysis

**Plugin Name:** Job Postings
**Version:** 2.8.1
**Author:** BlueGlass Interactive
**License:** GPLv2 or later
**Requirements:** WordPress 5.0+, Tested up to 6.8

**Description:** A powerful WordPress solution for managing job listings with automatic schema.org/JSON-LD structured data for Google Jobs visibility, customizable application forms, and comprehensive job management features.

---

## Custom Post Types

| Post Type | Slug | Description |
|-----------|------|-------------|
| Jobs | `jobs` | Public job listings |
| Job Entries | `job-entry` | Application submissions (private) |

## Custom Taxonomies

| Taxonomy | Slug | Description |
|----------|------|-------------|
| Job Categories | `jobs_category` | Hierarchical job categories |

## Job Management

### Job Features
- Create, edit, and delete job postings
- Drag-and-drop field customization and reordering
- Job duplication (duplicate as draft)
- Job categorization with hierarchical taxonomy
- Enable/disable field visibility while maintaining structured data
- Completeness indicator showing required/recommended/all fields status
- Job expiration with "Valid Through" date

### Job Fields (30+)
- Position Title
- Job Location (street, city, state, postal code, country)
- Employment Type (Full-time, Part-time, Contractor, Temporary, Intern, Volunteer, Per Diem, Other)
- Description
- Hiring Organization
- Beginning/Duration of Employment
- Industry
- Responsibilities
- Qualifications
- Job Benefits
- Contacts
- Working Hours
- Base Salary (with currency and unit selection)
- Date Posted (auto-filled)
- Valid Through (expiration date)
- Education Requirements
- Experience Requirements
- Skills
- Custom Button (CTA linking to external site)
- Attachment/File
- Custom Text Fields (3 customizable sections)

## Application Management

### Application Features
- Application form with customizable fields
- Modal popup form for applications
- Inline embedded application form
- Form field editor (no-code form builder)
- Entry management dashboard with filtering
- Entry search by applicant data
- Unread entry counter bubble
- Entry download/export capability

### Form Field Types
- Text input
- Email (with validation)
- Phone (with validation)
- Textarea
- File upload (single and multiple)
- Checkboxes
- Radio buttons
- Select dropdowns
- Section headers
- Datalists with auto-complete

## Spam Protection

### Security Features
- Invisible honeypot spam filter
- Google reCAPTCHA V2 (checkbox)
- Google reCAPTCHA V3 (invisible risk scoring)
- Input sanitization and validation
- File type and size validation
- Nonce verification on forms
- Permission-based PDF access control

## Email Notifications

### Email Types
- Automatic notifications for new applications
- Custom email message per job
- Merge tags for dynamic field insertion
- Attachment inclusion in notification emails
- Custom confirmation messages
- Confirmation page redirect capability

### Email Configuration
- Configurable reply-to email
- Configurable from email and from name
- Email header customization

## SEO Features

### Structured Data (JSON-LD)
- Automatic JSON-LD generation
- Full schema.org JobPosting compliance
- Google Jobs listing optimization
- Yoast SEO plugin integration
- Direct link to Google Structured Data Testing Tool

## PDF Export

### PDF Features
- Export jobs to formatted PDF documents
- TCPDF library (PHP 8+ compatible)
- Custom font selection (filter hook)
- Company logo inclusion
- Footer with organization details
- Field exclusion from PDF (CSS class)
- Multi-language PDF generation
- Cyrillic character support

## Shortcodes

| Shortcode | Parameters | Description |
|-----------|------------|-------------|
| `[job-postings]` | category, showcategory, hide_empty, show_filters, limit, posts_per_page, hide_past, orderby, order, target | Main job listing display |
| `[job-categories]` | category, aligncategory, hide_empty, show_count, multiselect | Inline category filter |
| `[job-categories-tree]` | show_count | Hierarchical category tree |
| `[job-search]` | - | Standalone search form |
| `[job-single id="123"]` | id (required) | Display single job |

## Styling & Customization

### Style Options
- Live CSS preview in settings
- Color picker for 8+ UI elements
- Button background, hover state, and text color
- Heading and subheading colors
- List item background and border colors
- Content heading and text colors
- Border radius controls for buttons and boxes
- Custom CSS area

## Accessibility (WCAG 2.2)

### Accessibility Features
- Semantic HTML structure
- ARIA labels and roles
- ARIA modal dialogs
- Screen reader support
- Keyboard navigation support
- Sufficient color contrast
- Form accessibility
- Skip links for navigation
- Touch-friendly controls

## Multilingual Support

### Language Features
- WPML plugin integration
- Polylang plugin support
- Language-specific settings (currency, organization, etc.)
- Multi-language slugs
- Translation-ready strings
- Multi-language archive pages

## Admin Features

### Admin Menu
- Jobs (top-level menu)
- All Positions
- Add new Position
- Job entries (with unread count bubble)
- Settings
- Help

### Settings Tabs
1. Settings Tab - Per-language job configuration
2. Apply Form Tab - Application form builder
3. Styles Tab - Visual customization with live preview
4. Global Options Tab - Site-wide defaults (logo, email)
5. Default Fields Tab - Pre-selection and ordering of fields

## Developer Features

### Action Hooks
- `job-postings-loaded` - After plugin initialization
- `job-entry/after_submit` - After form submission
- `job-posting/entry_meta_box` - Custom entry metabox

### Filter Hooks
- `job-postings/position_fields` - Modify available job fields
- `job-postings/json_ld` - Customize JSON-LD output
- `job-postings/email/merge_tags` - Replace tags in emails
- `jobs_post_type/slug` - Change post type slug
- `job-postings/allow-script-in-html` - Allow scripts in specific fields

## Technical Implementation

### Architecture
- Object-Oriented PHP with static method classes
- WordPress hooks (actions/filters) extensively used
- Template hierarchy system for theme customization
- Custom REST API endpoints

### Libraries & Dependencies
- TCPDF - PDF generation
- Select2 - Enhanced dropdown selection
- jQuery UI - Date picker and sortable elements
- Google reCAPTCHA - Spam protection

### Database
- Post meta for job details
- Entry metadata storage
- Meta query searching
- Hierarchical taxonomy support
- REST API endpoints

### Performance
- Conditional script loading
- Asset versioning for cache busting
- Optimized database queries
