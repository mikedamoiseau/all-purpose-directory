# Simple Link Directory Plugin - Feature Analysis

**Plugin Name:** Link Directory - Simple Link Directory
**Version:** 8.8.4
**Author:** QuantumCloud
**License:** GPLv2
**Requirements:** WordPress 4.6+, PHP 7.4+

**Description:** A free WordPress plugin for creating curated link collections organized as "Lists." Designed for resource pages, local business directories, vendor directories, partner lists, video galleries, and other link-curation scenarios.

---

## Custom Post Types & Taxonomies

| Type | Slug | Description |
|------|------|-------------|
| CPT | `sld` | Simple Link Directory |
| Taxonomy | `sld_cat` | List Categories (hierarchical) |

## List Item Structure

Each list item can contain:
- Item Title
- Item Link (with http://)
- Item Image (100x100px preferred)
- Item Subtitle
- Item Background Color
- Upvote Count
- Entry Time
- Time Laps (for new item expiration)
- No Follow checkbox
- Featured flag
- New Tab flag

## Display Modes

- **"all" mode:** Display all lists with multiple columns
- **"one" mode:** Display a single list (single column only)

## Built-in Templates (6)

1. **simple** - Default template
2. **style-1** - Displays subtitles, style-1 specific features
3. **style-2** - Alternative card layout
4. **style-3** - Updated layout
5. **style-4** - Additional layout option
6. **style-5** - Card-based layout
7. **style-16** - Modern layout

### Template Features
- Responsive grid layout using Packery masonry library
- Column customization (1-3+ columns)
- RTL (Right-to-Left) support
- Customizable highlight colors per list
- Minimum width configuration

## Shortcode: `[qcopd-directory]`

### Parameters

| Parameter | Description | Default |
|-----------|-------------|---------|
| `mode` | "all" or "one" | "all" |
| `list_id` | Specific list ID for single list | - |
| `column` | Number of columns | "1" |
| `style` | Template style selection | "simple" |
| `orderby` | Ordering method for lists | "menu_order" |
| `order` | ASC or DESC | - |
| `category` | Filter by category slug | - |
| `search` | Enable/disable search | - |
| `upvote` | Enable/disable upvote | - |
| `item_count` | Show/hide item count | "on" |
| `top_area` | Show/hide top section | "on" |
| `item_orderby` | Order items by title, upvotes, or date | - |
| `item_order` | Item ordering direction | - |
| `min_width` | Custom minimum width | - |
| `list_img` | Show/hide list images | - |
| `mask_url` | URL masking for affiliate links (Pro) | - |
| `enable_embedding` | Allow list embedding | - |
| `title_font_size` | Customize title font size | - |
| `subtitle_font_size` | Customize subtitle font size | - |
| `enable_image` | Enable/disable images display | - |

## Shortcode Generator

- Visual modal interface in post/page editor
- One-click shortcode generation with preview
- TinyMCE button (blue [SLD] button in toolbar)
- Gutenberg block integration
- Shortcode generator metabox on pages

## Search & Filtering

- **Live Search:** Real-time search functionality
- **Search Parameter:** Customizable "No Results Found" text
- **Enable/Disable Toggle:** Admin settings control
- **AJAX-powered:** Non-intrusive search implementation

## Upvoting System

### Features
- Enable/disable globally and per shortcode
- Vote counter for each item
- Upvote icons customizable (Thumbs up, Fire, Heart, Star, Smiley)
- Cookie-based vote restriction (prevents duplicate votes)
- 30-day cookie persistence
- AJAX voting system
- Upvote count stored in meta data
- Manual upvote count field in editor

### Statistics
- Click-through tracking
- Total votes per link
- Admin-accessible vote management

## Admin Features

### Menu Structure
- Main Menu: "Simple Link Directory"
- Submenus: Manage Lists, New List, List Categories, Settings, Import, AddOns, Shortcodes and Help

### Custom Columns
- List Title
- Number of Elements (item count)
- Single List Shortcode (auto-generated)
- All Lists Shortcode (auto-generated)
- Date

### Capabilities
- Custom post type interface for list management
- Repeatable fields for list items via CMB framework
- Drag-and-drop reordering of items
- Color picker for item and list highlighting
- Media uploader integration

## Settings Tabs (6)

### 1. Getting Started
- Tutorial carousel with setup steps

### 2. General Settings
- Enable top section (search/filter area)
- Enable upvoting system
- Add New button functionality
- Custom "Add Link" button link
- Click tracking via Google Analytics
- Embed credit customization
- Scroll to top button
- RTL language support toggle
- Live search enable/disable
- Default list ordering

### 3. Language Settings
- "Add Link" button text
- "Share List" button text
- "Live Search" placeholder
- "No Results Found" message

### 4. Custom CSS
- Global custom CSS editor

### 5. Custom JavaScript
- Global custom JS editor

### 6. Help & Shortcodes
- Documentation and quick help

## Data Import/Export

### Bulk Import
- CSV file import support
- Create new lists from CSV
- Sample CSV template provided
- UTF-8 encoding required
- Error handling for encoding issues

### Export
- Pro version feature only

## Embedding System

### Features
- Embed button in frontend
- Automatic embed page creation
- Custom embed form styling
- Embed credit configuration
- JS-based embedding system
- Share lists on other websites
- Backlink to original directory

## Outbound Link Tracking

### Google Analytics Integration
- Click tracking for external links
- Separate tracking script
- Option to enable/disable in settings
- Skips tracking for logged-in administrators

## Sorting & Ordering

### List Ordering Options
- menu_order (backend drag-drop order)
- date (publication date)
- title (alphabetical)
- random/none (custom order)

### Item Ordering Options
- By title (alphabetical)
- By upvote count (most voted first)
- By date (newest/oldest)
- By custom menu order

## Gutenberg Integration

- Native Gutenberg block for Simple Link Directory
- Shortcode generation from block interface
- Full backward compatibility

## AJAX Functionality

- `wp_ajax_qcopd_upvote_action` (logged-in users)
- `wp_ajax_nopriv_qcopd_upvote_action` (non-logged-in users)
- Nonce verification for security
- JSON response format

## Pro Features & Add-ons

### Available Add-ons
1. **Link Exchange AddOn** - Enable link exchange monetization
2. **Broken Link Checker AddOn** - Check broken links, scheduled scanning, email notifications
3. **Review & Rating AddOn** - User reviews, star ratings, moderation
4. **Modern Multi-Page Mode AddOn** - Multi-page directory layout, category-based pagination

### Pro Features
- Auto-generate title, description, thumbnail
- Multi-page mode with automatic pagination
- Front-end user registration and link submission
- Monetization with PayPal/Stripe integration
- Paid listing packages
- Multi-language support
- Visual Composer/Elementor integration
- Link masking for affiliate URLs
- User role "SLD User"
- Featured link management
- Advanced filtering and search

## Security Features

- Nonce verification
- User capability checks
- Data sanitization
- Cookie-based vote restriction
- Admin user check for tracking script exclusion

## Compatibility

- WordPress 4.6+ support
- PHP 7.4+ requirement
- Theme compatibility (works with any theme)
- Visual Composer compatible
- Elementor compatible
- Beaver Builder compatible
- Language file support (mo/pot files)
- RTL language support

## Asset Libraries

### JavaScript
- jQuery
- Packery (masonry grid layout)
- ImagesLoaded
- Slick carousel (admin only)
- Font Awesome icons

### CSS
- Font Awesome CSS
- Directory styles
- Responsive design CSS
- Template-specific CSS
- RTL-specific CSS
- Embed form CSS

## Technical Implementation

- **Database Storage:** Custom post meta using CMB framework
- **Architecture:** Hook-based (actions and filters throughout)
- **Template System:** PHP template files in `/templates/` directory
- **Internationalization:** Full i18n support with text domain `qc-opd`
- **Admin UI:** WordPress native settings API
- **Frontend:** Shortcode-based content display
- **Grid Layout:** CSS Packery for responsive masonry layout
