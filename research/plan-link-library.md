# Link Library Plugin - Feature Analysis

**Plugin Name:** Link Library
**Version:** 7.8.6
**Author:** Yannick Lefebvre
**License:** GPLv2+
**Requirements:** WordPress 4.4+

**Description:** A comprehensive plugin designed to display lists of links/URLs on WordPress pages with extensive customization options. Enables site administrators to create and manage link directories with categories, search capabilities, user submissions, and RSS feed integration.

---

## Custom Post Types & Taxonomies

| Type | Slug | Description |
|------|------|-------------|
| CPT | `link_library_links` | Custom post type for storing links |
| Taxonomy | `link_library_category` | Hierarchical taxonomy for organizing links |
| Taxonomy | `link_library_tags` | Non-hierarchical taxonomy for tagging links |

## Core Display Features

- **Multiple Configuration Sets**: Create up to 5 different library configurations
- **List/Table Display**: Render links as unordered lists or HTML tables
- **Category Display**: Show links organized by categories with hierarchical support
- **Masonry Layout**: Grid-based display with customizable columns

### Link Metadata Display Options
- Link descriptions and notes
- Link dates (publication and update)
- Link hit counts
- RSS feed information
- Link images/thumbnails
- Secondary web addresses
- Telephone numbers
- Email addresses

## Category Management

- **Category Filtering**: Display or exclude specific categories
- **Single Category Mode**: Show one category at a time with AJAX/HTML GET switching
- **Hierarchical Categories**: Support for nested/hierarchical category structures
- **Category Lists**: Dropdowns, unordered lists, visibility toggles, simple divs, breadcrumbs
- **Category Descriptions**: Show above or beside category names
- **Empty Category Hiding**: Option to hide categories with no links
- **Category Link Counts**: Display number of links in each category

## Link Tagging & Filtering

- **Link Tags**: Support for non-hierarchical tags
- **Tag Filtering**: Filter links by tag selection
- **Tag Cloud**: Display tag cloud for link filtering
- **Alpha Filter**: Filter links by first letter of link name

## Search Functionality

- **Link Search**: Search by name, URL content, or description
- **Search Results**: Display with category organization
- **Empty Search Results**: Option to suppress output when no matches
- **Category-Specific Search**: Search within selected or all categories

## User Link Submission

### Form Fields Configuration
- Link name, address (URL), description, notes
- Link RSS feed
- Category selection (single or multiple)
- User-defined custom categories
- Tags
- Secondary address, telephone, email
- Image upload, file upload
- Custom text/list fields (up to 5 each)

### Submission Features
- **Moderation System**: Pending link approval before publication
- **Bookmarklet**: Browser bookmarklet for quick submission
- **Submission Notifications**: Email to moderators and submitters
- **Captcha Protection**: Easy Captcha, reCAPTCHA v2, Custom Captcha
- **Popup Form Mode**: Display as popup dialog
- **Duplicate Detection**: Check for duplicate links

## Image & Thumbnail Management

### Thumbnail Generator Options
- Robot Thumb
- WordPress Mshots
- Shrink the Web
- Thumbshots.com
- Page Peeker
- Manual/Local images

### Image Features
- Link image association
- Automatic thumbnail generation
- Thumbnail upload in submission form
- Image positioning (before/after link name)
- Featured image support
- AJAX image generation

## RSS Feed Features

- **RSS Feed Publishing**: Generate RSS feed of links
- **RSS Preview**: Display preview of latest items from link's RSS feed
- **Combined RSS Library**: Aggregate feeds using `[rss-library]` shortcode
- **RSS Cache**: Configurable cache delay
- **Feed in Main Site Feed**: Include links in WordPress site's main RSS feed
- **New Links Display**: Show only updated/new links for specified days

## Link Checking & Validation Tools

- **Broken Link Checker**: Identify links with HTTP errors
- **Reciprocal Link Checker**: Verify if linked sites link back
- **Duplicate Link Checker**: Find duplicate links
- **Empty Category Checker**: Identify empty categories
- **Secondary Links Checker**: Validate secondary addresses
- **Image Links Checker**: Verify image URLs
- **RSS Feed Checker**: Validate RSS feed URLs

## Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[link-library settings=#]` | Main shortcode to display links list |
| `[link-library-cats settings=#]` | Display category list/navigation |
| `[link-library-search]` | Display search box |
| `[link-library-addlink settings=#]` | Display user link submission form |
| `[link-library-count settings=#]` | Display count of links |
| `[link-library-filters atts]` | Display filter interface for tags/categories |
| `[link-library-tagcloud]` | Display tag cloud for filtering |
| `[rss-library]` | Display combined RSS feed from all links |

### Shortcode Parameters
- `categorylistoverride` - Override category list
- `excludecategoryoverride` - Override excluded categories
- `notesoverride` - Override notes display
- `descoverride` - Override description display
- `rssoverride` - Override RSS display
- `tableoverride` - Override table display
- `showapplybutton` - Display apply/filter button

## Admin Features

### Admin Menu Structure
- Global Options
- Library Configurations
- Moderate (with pending count badge)
- Stylesheet
- Link Checking Tools
- Donate page
- FAQ page

### Library Configuration Tabs
- Common
- Display (layout, columns, image settings)
- Link Display (metadata options)
- Categories
- Link Ordering
- Pagination
- User Form Configuration
- RSS Feed Options
- Search Configuration
- Advanced Options

### Management Features
- Settings duplication
- Settings import/export (CSV)
- Link editor with tags, categories, images, custom fields
- Moderation dashboard
- Stylesheet editor
- Admin dashboard widget

## Block Editor Support

- **Link Library Block**: Gutenberg block for displaying links
- **Link Library Categories Block**: Gutenberg block for category navigation

## Widget Support

- **Link Library Widget**: Configurable widget with title, library selection, category filter

## Advanced Display Options

### Ordering
- By name, publication date, update date, number of hits
- Ascending or descending
- Article ignoring (a, an, the) when sorting

### Pagination
- Split large link lists across multiple pages
- Configurable items per page
- Works with all display modes

### Link Options
- Link target (_blank, _self, etc.)
- No-follow option
- Hit tracking
- Admin edit links from frontend

## Content Management

- **Large Descriptions**: HTML and embed shortcode support
- **Excerpt Support**: Optional excerpt field
- **Single Item Layout**: Customizable HTML template
- **Custom Text/List Fields**: Display custom metadata
- **Category Anchors**: Links to jump to category sections

## Display Customization

### HTML Tags Control
Fine-grained control for:
- Links, notes, descriptions
- RSS information, dates
- Images, custom fields, link hits

### Table Customization
- Table width, column headers
- Custom column headers
- Column HTML tags

### Styling
- Color presets for category displays
- Inline stylesheet editor
- CSS validation via CSS Tidy
- Library-specific stylesheets

## Integration Features

- **Simple Custom Post Order Plugin**: Compatible
- **BuddyPress Integration**: Activity logging for new links
- **Akismet Integration**: Spam checking for submissions
- **WPGraphQL Support**: Query links via GraphQL API
- **REST API Support**: All CPTs and taxonomies available
- **Multisite Support**: Network configuration
- **Import/Export**: CSV and OPML formats

## Performance Features

- RSS caching
- Thumbnail caching
- Database optimization for bulk imports
- Scheduled thumbnail generation
- Scheduled link imports from remote CSV

## Security & Validation

- Nonce verification
- Data sanitization
- HTML sanitization with whitelisting
- Referer checks
- User capability checks
- Configurable admin roles
- Configurable edit roles

## Internationalization

- Translation-ready with .pot file
- Translations: French, Danish, Italian, Serbian

## Additional Features

- Legacy support for pre-6.0 Link Manager data
- Update channel selection
- Debug mode
- Mobile responsive forms and displays
