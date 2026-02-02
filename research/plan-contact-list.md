# Contact List Plugin - Feature Analysis

**Plugin Name:** Contact List - Online Staff Directory & Address Book
**Version:** 3.0.17
**Author:** flavor
**License:** GPL
**Description:** A comprehensive WordPress directory plugin for creating staff directories, address books, business listings, and member directories

---

## Contact Management

- Add/edit/manage contacts in WordPress admin
- Custom post type "Contact" (CPT) with unique ID
- Support for contact status (Published, Pending Review)
- Bulk operations on contacts
- Contact search in admin area
- Filtering contacts by category/group in admin list

## Contact Fields/Attributes

### Basic Information
- First Name, Last Name, Middle Name
- Name Prefix and Suffix
- Job Title
- Email (with notify emails feature)
- Primary phone, Phone 2, Phone 3

### Social Media & Custom URLs
- LinkedIn URL
- X (Twitter) URL
- Facebook URL
- Instagram URL
- Custom URL 1 & 2 (with customizable link text)

### Address Information
- Address Line 1 & 2
- City, State, Country
- Zip/Postal Code

### Additional Fields
- Contact Photo/Image (with custom sizing)
- Google Maps iframe code
- Custom Fields (1-20+ depending on plan)
- Categories/Groups (taxonomy-based)

## Shortcodes

| Shortcode | Description | Key Parameters |
|-----------|-------------|----------------|
| `[contact_list]` | Full contact directory | `layout`, `hide_search`, `hide_filters`, `order_by`, `card_height`, `exclude`, `group`, `contact` |
| `[contact_list_simple]` | Simplified table view | `show_filters`, `ajax`, `send_group_email`, `group`, `contacts_per_page`, `fields` |
| `[contact_list_groups]` | Display groups with interactive selection | `order_by`, `hide_breadcrumbs`, `group`, `hide_group_title`, `show_filters` |
| `[contact_list_form]` | Public form for visitors to submit contacts | - |
| `[contact_list_search]` | Standalone search form | `group` |
| `[contact_list_send_email group=X]` | Send emails to group members | `group` |
| `[contact_list contact=X]` | Display individual contact | `contact` |

## Layout & Display Options

### Pre-built Layouts
- Default (2 columns with images)
- 2-columns layout
- 3-columns layout (no images)
- 4-columns layout (no images)

### Display Settings
- Customizable card height (minimum pixels)
- Contact image styles (circle, square, etc.)
- Shadow effects below images
- Card background and border customization
- Show/hide contact groups on cards
- Last name before first name option
- Show comma after last name

## Search & Filtering

### Automatic Filters
- Country dropdown (auto-populated)
- State dropdown (auto-populated)
- City dropdown (auto-populated)
- Category/Group filter
- Custom field value filters (Pro feature)

### Search Capabilities
- Real-time search in contact list
- AJAX-based search (beta feature)
- Search logging with analytics (Pro feature)
- Option to require search button click
- Search term minimum character setting

## Contact Organization

### Taxonomy System
- `contact-group` taxonomy for grouping/categorizing contacts
- Support for hierarchical groups (parent/child)
- Subgroup functionality
- Option to show/exclude contacts from subgroups

## Admin Features

### Contact Management Pages
- All Contacts list with customizable columns
- Individual contact editor with all custom fields
- Bulk operations toolbar

### Import & Export
- CSV import functionality
- CSV export functionality
- Excel file support
- Import log with detailed tracking
- Scheduled/recurring imports (Pro feature)
- Options:
  - Update existing contacts by email
  - Delete all contacts before import
  - Skip first line of CSV
  - Set import chunk size
  - Configure separator (comma/semicolon)
  - Customize field order

### Email Management
- Send email to all contacts or specific group
- Bulk email interface in admin
- Default message/subject settings
- Email log with tracking
- Mail recipient selection by group

### Additional Admin Pages
- Settings page with extensive configuration
- Shortcodes reference page
- Help/Support page
- Printable contact list
- Statistics page
- Import log viewer

## Frontend Features

### Email Sending
- Send message to individual contact from frontend
- Email obfuscation/spam prevention
- reCAPTCHA support (v2/v3)
- Customizable email modal
- Subject line customization

### Interactive Features
- Contact cards with expandable content
- Lightbox for viewing additional information (Pro)
- Single contact pages with custom permalinks (Pro)
- Pagination support
- Breadcrumb navigation

## Settings Tabs

1. **General:** Sort order, pagination, breadcrumb titles, form options
2. **Layout:** Card height, background, borders, image styles, typography
3. **Field Configuration:** Custom fields setup, titles, visibility
4. **Search & Filters:** Filter configuration, dropdown options
5. **Simple List:** Field display, layout options, pagination
6. **Contact Card:** Title format, column layout, content positioning
7. **Advanced:** Post type settings, taxonomy settings, API settings
8. **reCAPTCHA & Email:** reCAPTCHA configuration, email settings
9. **Import & Export:** Field mapping, separator options, scheduling
10. **Logs:** Mail log, search log, import log settings
11. **User Roles:** Front-end editor permissions (Pro)

## Form Features

### Contact Submission Form
- Customizable fields per form
- Required field settings
- Group selection option
- Auto-publish or pending review option
- AJAX-based submission
- Field visibility control

## Developer Features

### REST API Integration
- Custom fields exposed to REST API
- Contact post type supports REST
- Programmatic access to contact data

### Gutenberg Block
- Native WordPress block support
- Contact List block for editor
- Block parameters for customization

### Custom Templates
- Template override system
- Contact card PHP template customization
- Custom template support for layouts

## Custom Post Types & Taxonomies

| Type | Name | Description |
|------|------|-------------|
| CPT | `contact` | Main contact post type |
| Taxonomy | `contact-group` | Categories/groups for contacts |

## Database Structure

### Custom Tables
- `wp_contact_list_search_log` - Search tracking
- `wp_contact_list_mail_log` - Email tracking
- `wp_contact_list_import_log` - Import tracking

### Post Meta
All contact fields stored as post meta with `_cl_` prefix

## AJAX Handlers

- `cl_send_mail_public` - Send message to contact
- `cl_get_contacts` - Fetch contacts for filters
- `cl_get_contacts_simple` - Simple list contact fetch
- `contact_list_search_log` - Log search activity

## Admin Menu Structure

- Contact List
  - All Contacts
  - Add New Contact
  - Categories (Groups)
  - Shortcodes
  - Import Contacts
  - Import Log
  - Export Contacts
  - Send Email
  - Mail Log
  - Search Log
  - Printable
  - Settings
  - Help/Support

## Premium Features

- Front-end contact editor
- Request update feature
- Single contact pages with permalinks
- Custom field filtering (7-20 fields)
- Bulk email operations
- Search log with country detection
- City detector for search log
- Lightbox display
- Include contacts in site search results
- Advanced sorting options
- Scheduled imports
- Permanent update URLs
