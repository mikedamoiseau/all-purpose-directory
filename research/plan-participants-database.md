# Participants Database Plugin - Feature Analysis

**Plugin Name:** Participants Database
**Version:** 2.7.8.1
**Author:** Roland Barker, xnau webdesign
**License:** GPLv3
**Requirements:** WordPress 5.0+, PHP 7.4+

**Description:** A fully configurable database plugin for managing participants, members, volunteers, or any group of people. Includes signup forms, record management, CSV import/export, and customizable frontend displays.

---

## Database Management

### Core Features
- Fully configurable database schema
- Custom field definitions with flexible naming
- Support for unlimited fields
- Field organization into groups
- Field-level permissions and visibility controls

### Pre-configured Standard Fields
- Participant name, address, phone
- Email, website, social media
- Extensible with custom fields

### Database Tables
- Main participants table (wp_participants_database)
- Fields definitions table
- Field groups table

## Form Types

### Available Forms
1. **Signup Form** (`[pdb_signup]`)
   - Customizable short form for quick registration
   - Configurable fields per form
   - Optional redirect to thank you page
   - Email confirmation/notification integration

2. **Record Edit Form** (`[pdb_record]`)
   - Frontend form for users to edit their own records
   - Secure private link access (no WordPress account needed)
   - Differentiated view between admin and user-editable fields
   - Support for field groups in organized sections

3. **Backend Admin Record Edit Form**
   - Full CRUD operations for administrators
   - Rapid manual entry of multiple records
   - Admin-only fields support
   - Rich text editor support

## Field Types (21 Types)

### Basic Fields
- Text-line (single line text)
- Text-area (multi-line text)
- Rich-text (with editor)
- Numeric field
- Decimal field
- Currency field
- Date field (with optional time)
- Password field
- Hidden field

### Selection Fields
- Checkbox (single and multi-select)
- Radio buttons
- Dropdown lists (single and multi-select)
- Dropdown/Other option
- Radio buttons/Other option
- Multiselect/Other option

### Special Fields
- Link field (URL with label)
- Image upload
- File upload
- CAPTCHA (simple math question)

### Field Features
- Required/not required settings
- Regex pattern validation
- Email validation
- Unique field constraints
- Custom validation messages
- Icons per field
- Field ordering via drag-and-drop

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[pdb_signup]` | Signup form display |
| `[pdb_record]` | Record edit form |
| `[pdb_signup_thanks]` | Thank you message after signup |
| `[pdb_update_thanks]` | Thank you message after update |
| `[pdb_request_link]` | Lost private link recovery |
| `[pdb_list]` | Display participant records |
| `[pdb_single]` | Display individual record |
| `[pdb_search]` | Search form display |
| `[pdb_total]` | Record count display |

## List Display Features

### Display Options
- Configurable columns
- Pagination support
- Search functionality
- Sort/filter controls
- Export to CSV from list
- Multiple template options (default, bootstrap, detailed, responsive, flexbox)
- AJAX search integration

## CSV Import/Export

### Export Features
- Export all or filtered records
- Customizable column selection
- Filter by field values
- Sort by any column
- Background export for large datasets
- UTF-8 format support

### Import Features
- Batch record addition from spreadsheets
- Column mapping interface
- Error reporting and validation
- Background import with progress tracking
- Import status display
- Blank template generation for offline entry
- Encoding detection and correction

## Email System

### Email Types
- Signup receipt emails
- Admin notification emails
- Record update notification emails
- Lost link recovery emails

### Email Features
- Customizable from address and name
- Dynamic subject and body templates
- HTML or plain text format
- Private link inclusion in email
- Enable/disable per event

## Private Link System

### Features
- Secure individual access codes
- No WordPress user registration needed
- Optional link resend functionality
- Link recovery page configuration

## Display Templates

### List Templates
- pdb-list-default
- pdb-list-bootstrap
- pdb-list-detailed
- pdb-list-responsive
- pdb-list-flexbox

### Record Templates
- pdb-record-default
- pdb-record-bootstrap
- pdb-record-tbody

### Single Templates
- pdb-single-default
- pdb-single-bootstrap
- pdb-single-flexbox
- pdb-single-single-value
- pdb-single-bare-value

### Other Templates
- pdb-search-default
- pdb-retrieve-default/bootstrap
- pdb-signup-default/bootstrap
- pdb-thanks-default
- pdb-total-default

## Admin Features

### Admin Menu Structure
- Participants Database (main)
- List Participants
- Add Participant
- Manage Database Fields
- Manage List Columns
- Upload CSV
- Plugin Settings
- Setup Guide

### Admin List Features
- Searchable and sortable record listing
- Full-text search across multiple fields
- Column-based sorting
- Filter by field values
- Per-user column preferences
- Mass edit functionality
- Bulk operations

## Settings Categories (60+ Options)

### General Settings
- File upload location and limits
- Allowed file types
- Default image settings
- Required field marker style

### Signup Form Settings
- Button text customization
- Thank you page selection
- Unique field enforcement
- Email receipt settings

### Record Form Settings
- Registration page selection
- Save button customization
- Update notification settings

### List Display Settings
- Default records per page
- Single record link field
- No records message
- Default sort field/order
- Empty search behavior

### Advanced Settings
- Rich text editor toggle
- HTML tag allowance
- API enablement
- AJAX search toggle
- Session management
- Debug mode

## Security Features

### Access Control
- User capability checking
- Admin-only field hiding from frontend
- Field group visibility controls
- Record-specific access via private links

### Data Protection
- Email address protection option
- HTML tag filtering
- Secure private link codes
- CAPTCHA form protection
- Input sanitization

## AJAX Features

### AJAX Handlers
- pdb_list_filter - Frontend list filtering
- Import status display
- Mass edit field input
- Manage fields updates
- Manage list columns

## Technical Implementation

### Architecture
- Object-Oriented Design with static main class
- Base class inheritance
- Template-based display system
- Service-oriented components

### Database
- Custom WordPress tables
- Standard table prefix support
- Version-based schema updates

### Performance
- Background CSV import for large datasets
- Pagination for large record sets
- Cache system for participant data
- Page cache clearing
- AJAX-based list filtering

## Internationalization

- Full translation support
- 30+ language translations
- Multi-language site support
- Non-English character search support

## Developer Features

### Customization
- Comprehensive hook system (filters and actions)
- Plugin settings extension
- Custom field type development
- Template customization
- Auxiliary plugin system for add-ons
