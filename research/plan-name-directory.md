# Name Directory Plugin - Feature Analysis

**Plugin Name:** Name Directory
**Current Version:** 1.32.0
**Author:** Jeroen Peters
**License:** GPL2
**Requires:** WordPress 3.0.1+, PHP 5.3+

---

## Core Directory Features
- **Multiple Directories** - Create unlimited separate directories with independent settings
- **Customizable Directory Names** - Use custom terminology (e.g., "movies" instead of "names")
- **Directory Descriptions** - Per-directory admin-only descriptions
- **Published/Unpublished Status** - Control visibility of individual entries
- **Directory-Specific Email Notifications** - Custom email recipient per directory

## Frontend Display Features
- **Alphabetical Index** - A-Z with # for numbers
- **Dynamic Index Letters** - Show only letters with entries or all letters
- **Show/Hide Titles** - Toggle directory title display
- **Show/Hide Descriptions** - Toggle entry descriptions
- **Show/Hide Index Instructions** - Contextual helper text
- **Show/Hide Entry Count** - Display total number of entries
- **Multi-Column Layout** - 1-4 columns selectable
- **Character Headers** - Show letter separators between sections
- **Horizontal Rules** - Visual separators between entries
- **"Latest/Most Recent" Feature** - Display X newest entries (configurable: 3, 5, 10, 25, 50, 100)
- **Description Truncation with "Read More"** - Limit description words and show expandable text

## Search Functionality
- **Built-in Directory Search** - Per-directory search form
- **Search by Name** - Basic name matching
- **Search in Descriptions** - Optional description searching (per-directory)
- **Wildcard Search** - Partial string matching support
- **Exact Search** - Use quoted searches for exact matches
- **Search Highlighting** - Highlight search terms in results (uses Mark.js library)
- **WordPress Sitewide Search Integration** - Include Name Directory in WordPress search
- **Relevanssi Plugin Support** - Compatible with Relevanssi search plugin
- **Jump to Search Results** - Auto-scroll to directory on search

## Submission Features
- **Frontend Submission Form** - Visitors can submit new entries
- **Submitter Name Field** - Optional submitter attribution
- **Description Field** - Allow submitter to add descriptions
- **Moderation Option** - Require admin approval before publishing
- **Email Notifications** - Admin notification when submissions arrive
- **Google reCAPTCHA v2 Protection** - Spam protection on submission forms
- **Duplicate Detection** - Prevent duplicate entries (configurable)

## Import/Export Features
- **CSV Import** - Bulk add entries from CSV files
- **CSV Export** - Download directory as CSV
- **Quick Import** - Create new directory and import in one step
- **UTF-8 Support** - Handles special characters during import
- **UTF-8 Special Import Option** - Fallback for encoding issues
- **Optional Directory Clearing** - Empty directory before import

## Editor & Admin Features
- **Visual HTML Editor** - Optional WYSIWYG editor for descriptions
- **HTML Support** - Allow HTML markup in descriptions (with sanitization)
- **Simple Textarea Editor** - Default plain text editor
- **Admin Pagination** - Page large directories at 50 entries per page
- **One-Click Publish Toggle** - Quick published/unpublished switching via checkbox
- **Name Search in Admin** - Search entries in admin panel
- **Filter by Status** - View all/published/unpublished entries
- **Ajax Form Submission** - Add/edit entries without page reload
- **Edit Mode Toggle** - Quick add vs full form views

## Display Customization
- **Custom HTML Heading Tags** - Choose h2, h3, h4, h5, h6, or strong for entry titles
- **CSS Classes for Styling** - Comprehensive class naming throughout
  - `.name_directory_index` - Index container
  - `.name_directory_name_box` - Entry container
  - `.name_directory_active` - Active letter indicator
  - `.name_directory_empty` - Empty letter indicator
  - `.name_directory_character_header` - Section headers
  - `.name_directory_readmore_trigger` - Expand/collapse buttons

## Security Features
- **Nonce Verification** - CSRF protection on all forms
- **Permission Checks** - Role-based capability system
- **Data Sanitization** - Deep sanitization of user input
- **HTML Whitelist** - Only allow safe HTML in descriptions
- **Script Tag Stripping** - Malicious script removal during import
- **SQL Escaping** - Prepared statements for database queries
- **reCAPTCHA Integration** - Bot protection

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[namedirectory dir="X"]` | Display full directory |
| `[namedirectory dir="X" start_with="J"]` | Start with specific letter |
| `[namedirectory_random dir="X"]` | Display random entry |
| `[namedirectory_single id="X"]` | Display single entry by ID |

## Database Structure

### Table: `wp_name_directory` (Directory configurations)
23 fields including:
- Basic info: name, description
- Display settings: show_title, show_description, show_submit_form
- Layout: nr_columns, nr_most_recent, nr_words_description
- Search/Index: search_in_description, search_highlight, show_all_index_letters
- Submissions: email_for_submission, check_submitted_names_first
- Naming: name_term, name_term_singular

### Table: `wp_name_directory_name` (Directory entries)
7 fields:
- Entry data: name, description, letter (first character)
- Status: published (boolean)
- Attribution: submitted_by
- Relations: directory (FK)

## Plugin Integrations
- **Members Plugin** - Compatible with Members role system (`manage_name_directory` capability)
- **Relevanssi Plugin** - Compatible search plugin integration
- **WordPress Search** - Integrated into native WordPress search
- **Google reCAPTCHA** - Third-party spam protection

## Technical Specifications
- Database Version: 1.29.2
- Character Set: UTF-8 / UTF-8MB4 Unicode support
- Multibyte String Support: MB_string PHP extension (optional)
- jQuery Dependency for admin functionality
- External Libraries: Mark.js (search highlighting), Google reCAPTCHA
- WordPress Multisite Support
