# All Purpose Directory - User Guide

This guide helps you set up and use All Purpose Directory to create your own directory or listing website.

## Table of Contents

1. [Getting Started](#getting-started)
2. [Creating Listings](#creating-listings)
3. [Categories and Tags](#categories-and-tags)
4. [Frontend Submission](#frontend-submission)
5. [User Dashboard](#user-dashboard)
6. [Search and Filtering](#search-and-filtering)
7. [Reviews and Ratings](#reviews-and-ratings)
8. [Favorites](#favorites)
9. [Contact Forms](#contact-forms)
10. [Email Notifications](#email-notifications)
11. [Shortcodes Reference](#shortcodes-reference)
12. [Settings Reference](#settings-reference)
13. [Template Customization](#template-customization)
14. [Troubleshooting](#troubleshooting)

---

## Getting Started

### Installation

1. **Automatic Installation**
   - Go to Plugins > Add New in your WordPress admin
   - Search for "All Purpose Directory"
   - Click "Install Now" then "Activate"

2. **Manual Installation**
   - Download the plugin zip file
   - Go to Plugins > Add New > Upload Plugin
   - Upload and activate

### Initial Setup

After activation, follow these steps:

1. **Configure Settings**
   - Go to Listings > Settings
   - Set your currency symbol and position
   - Choose default listing status (pending/published)
   - Enable/disable features (reviews, favorites, contact form)

2. **Create Categories**
   - Go to Listings > Categories
   - Add your main categories (e.g., "Restaurants", "Hotels", "Services")
   - Set icons and colors for visual distinction

3. **Create Required Pages**
   Create WordPress pages for each feature:

   | Page | Shortcode | Purpose |
   |------|-----------|---------|
   | All Listings | `[apd_listings]` | Main listings archive |
   | Submit Listing | `[apd_submission_form]` | Frontend submission |
   | My Dashboard | `[apd_dashboard]` | User dashboard |
   | My Favorites | `[apd_favorites]` | Saved listings |

4. **Add Menu Items**
   - Go to Appearance > Menus
   - Add your new pages to the navigation menu

---

## Creating Listings

### From the Admin Panel

1. Go to Listings > Add New
2. Enter the listing title
3. Add description in the content editor
4. Set a featured image (recommended size: 800x600px)
5. Select categories and tags
6. Fill in custom fields in the "Listing Fields" meta box
7. Set the publish status
8. Click "Publish" or "Save Draft"

### Listing Fields

The plugin includes these field types by default. Your site may have additional custom fields:

- **Text fields** - Single line text (name, address, etc.)
- **Textarea** - Multi-line text (description, hours)
- **Email** - Email address with validation
- **Phone** - Phone number with formatting
- **URL** - Website links
- **Number/Price** - Numeric values with currency formatting
- **Date/Time** - Date and time pickers
- **Select/Checkbox/Radio** - Option selections
- **File/Image** - File uploads and images
- **Gallery** - Multiple images

### Listing Statuses

| Status | Description |
|--------|-------------|
| Published | Visible to everyone |
| Pending Review | Awaiting admin approval |
| Draft | Only visible to author and admins |
| Expired | Time-limited listing that has ended |

---

## Categories and Tags

### Categories

Categories are hierarchical (can have parent/child relationships).

**Creating Categories:**
1. Go to Listings > Categories
2. Enter name and optional description
3. Choose a parent category (optional)
4. Select an icon (dashicons)
5. Pick a color for the category badge

**Category Icons:**
Icons use WordPress Dashicons. Popular choices:
- `dashicons-location-alt` - Location pin
- `dashicons-store` - Store/shop
- `dashicons-building` - Building
- `dashicons-food` - Food/restaurant
- `dashicons-hammer` - Services
- `dashicons-calendar` - Events

### Tags

Tags are flat (no hierarchy) and useful for features, amenities, or attributes.

Examples:
- "Free WiFi", "Parking", "Pet Friendly"
- "Open 24/7", "Delivery Available"

---

## Frontend Submission

Allow users to submit listings without admin access.

### Setup

1. Create a page with the shortcode `[apd_submission_form]`
2. Configure submission settings in Listings > Settings > Submission:
   - **Who can submit**: Anyone, logged-in users, or specific roles
   - **Guest submission**: Allow submissions without login
   - **Default status**: Usually "Pending Review" for moderation
   - **Terms page**: Require acceptance of terms
   - **Redirect after**: Where to send users after submission

### Submission Form Features

- All enabled fields are displayed
- Required fields are marked with asterisks
- Client-side validation prevents incomplete submissions
- Image uploads use the WordPress media library
- Category and tag selection included

### Spam Protection

The plugin includes built-in spam protection:
- Honeypot fields (invisible to humans)
- Time-based checks (too-fast submissions blocked)
- Rate limiting (prevents mass submissions)

### Editing Listings

Users can edit their own listings:
1. From the dashboard, click "Edit" on any listing
2. Or use the URL parameter: `/submit-listing/?edit_listing=123`

---

## User Dashboard

The dashboard lets users manage their listings without admin access.

### Dashboard Tabs

| Tab | Description |
|-----|-------------|
| My Listings | View and manage submitted listings |
| Add New | Submit a new listing |
| Favorites | View saved favorite listings |
| Profile | Update profile settings |

### My Listings Features

- View all listings with status badges
- See view counts and statistics
- **Actions available:**
  - Edit - Modify listing details
  - Delete - Remove listing permanently
  - Mark as Sold - Change status for sold items

### Profile Settings

Users can update:
- Display name
- Email address
- Profile photo/avatar
- Bio/description
- Phone number
- Website
- Social links (Facebook, Twitter, LinkedIn, Instagram, YouTube)

---

## Search and Filtering

### Search Form

Add search to any page with `[apd_search_form]`.

**Available Filters:**
- **Keyword** - Search title, content, and searchable fields
- **Category** - Filter by category (dropdown)
- **Tags** - Filter by multiple tags (checkboxes)
- **Range** - Filter by numeric fields (price, etc.)
- **Date Range** - Filter by date fields

### AJAX Filtering

Filters update results without page reload:
1. Select filter options
2. Results update automatically
3. URL updates for shareable links

### Sort Options

Users can sort results by:
- Date (newest/oldest)
- Title (A-Z, Z-A)
- View count (most/least viewed)
- Random

### View Options

Switch between display modes:
- **Grid View** - Card layout with columns
- **List View** - Horizontal layout with more details

---

## Reviews and Ratings

### For Listing Owners

- Receive email notifications for new reviews
- View reviews on your listing pages
- Contact admin to moderate inappropriate reviews

### For Reviewers

1. Go to a listing page
2. Scroll to the reviews section
3. Click "Write a Review"
4. Select star rating (1-5)
5. Enter review title and content
6. Submit for moderation

**Rules:**
- One review per user per listing
- Users can edit their own reviews
- Reviews may require admin approval

### Review Moderation (Admins)

1. Go to Listings > Reviews
2. View pending reviews
3. Actions: Approve, Reject, Spam, Trash
4. Bulk actions available for multiple reviews

---

## Favorites

### Saving Favorites

- Click the heart icon on any listing card
- Logged-in users: Saved to account permanently
- Guests: Saved to browser cookies (30 days)

### Viewing Favorites

- Go to your dashboard's Favorites tab
- Or use the `[apd_favorites]` shortcode

### Guest to User Migration

When a guest logs in or registers, their cookie-based favorites are automatically merged with their account.

---

## Contact Forms

### For Visitors

1. Go to a listing page
2. Find the contact form (usually in sidebar)
3. Fill in your name, email, and message
4. Optional: Include phone number
5. Click Send

### For Listing Owners

- Receive email notifications for inquiries
- View inquiry stats in dashboard
- Optional: View inquiry history

### For Admins

Enable/disable contact forms in Settings > Listings:
- "Enable Contact Form" checkbox
- Optional admin copy of all inquiries

---

## Email Notifications

The plugin sends these automatic emails:

### To Admins
| Email | When Sent |
|-------|-----------|
| New Submission | A listing is submitted |

### To Listing Authors
| Email | When Sent |
|-------|-----------|
| Listing Approved | Admin approves a pending listing |
| Listing Rejected | Admin rejects a listing (includes reason) |
| Expiring Soon | Listing will expire soon |
| Expired | Listing has expired |
| New Review | Someone reviews their listing |
| New Inquiry | Someone contacts them via form |

### Configuration

Go to Listings > Settings > Email:
- Set From name and email
- Set admin notification email
- Enable/disable individual notification types

---

## Shortcodes Reference

### [apd_listings]

Display listings with various options.

```
[apd_listings view="grid" columns="3" count="12" category="restaurants"]
```

| Attribute | Values | Default | Description |
|-----------|--------|---------|-------------|
| view | grid, list | grid | Display style |
| columns | 2, 3, 4 | 3 | Grid columns |
| count | number | 12 | Listings per page |
| category | slug | - | Filter by category |
| tag | slug | - | Filter by tag |
| author | ID | - | Filter by author |
| orderby | date, title, views, random | date | Sort order |
| order | ASC, DESC | DESC | Sort direction |
| show_pagination | true, false | true | Show pagination |

### [apd_search_form]

Display the search and filter form.

```
[apd_search_form filters="keyword,category,tag" layout="horizontal"]
```

| Attribute | Values | Default | Description |
|-----------|--------|---------|-------------|
| filters | comma-separated | all | Which filters to show |
| layout | vertical, horizontal | vertical | Form layout |
| show_submit | true, false | true | Show submit button |

### [apd_categories]

Display category list or grid.

```
[apd_categories layout="grid" columns="4" show_count="true"]
```

| Attribute | Values | Default | Description |
|-----------|--------|---------|-------------|
| layout | list, grid | grid | Display style |
| columns | 2, 3, 4 | 3 | Grid columns |
| show_count | true, false | true | Show listing count |
| show_icon | true, false | true | Show category icon |
| parent | ID | 0 | Only show children of this category |
| hide_empty | true, false | true | Hide categories with no listings |

### [apd_submission_form]

Frontend listing submission form.

```
[apd_submission_form]
```

| Attribute | Values | Default | Description |
|-----------|--------|---------|-------------|
| listing_id | ID | - | Pre-populate for editing |

### [apd_dashboard]

User dashboard with tabs.

```
[apd_dashboard]
```

| Attribute | Values | Default | Description |
|-----------|--------|---------|-------------|
| tab | listings, favorites, profile | listings | Default active tab |

### [apd_favorites]

Display user's favorite listings.

```
[apd_favorites view="grid" columns="3"]
```

| Attribute | Values | Default | Description |
|-----------|--------|---------|-------------|
| view | grid, list | grid | Display style |
| columns | 2, 3, 4 | 3 | Grid columns |

### [apd_login_form]

WordPress login form.

```
[apd_login_form redirect="/dashboard/"]
```

### [apd_register_form]

WordPress registration form.

```
[apd_register_form redirect="/dashboard/"]
```

---

## Settings Reference

Access settings at Listings > Settings.

### General Tab

| Setting | Description |
|---------|-------------|
| Currency Symbol | Symbol to display (e.g., $, €, £) |
| Currency Position | Before or after amount |
| Date Format | How dates are displayed |
| Distance Unit | Kilometers or miles |

### Listings Tab

| Setting | Description |
|---------|-------------|
| Listings Per Page | Number on archive pages |
| Default Status | Status for new submissions |
| Expiration Days | Days until listing expires (0 = never) |
| Enable Reviews | Allow reviews on listings |
| Enable Favorites | Allow users to save favorites |
| Enable Contact Form | Show contact form on listings |

### Submission Tab

| Setting | Description |
|---------|-------------|
| Who Can Submit | Anyone, logged-in, or specific roles |
| Guest Submission | Allow without login |
| Terms Page | Page with terms to accept |
| Redirect After | Where to go after submission |

### Display Tab

| Setting | Description |
|---------|-------------|
| Default View | Grid or list |
| Grid Columns | 2, 3, or 4 columns |
| Show Thumbnail | Display featured image |
| Show Excerpt | Display listing excerpt |
| Show Category | Display category badge |
| Show Rating | Display star rating |
| Show Favorite | Display favorite button |
| Archive Title | Custom title for archive |
| Single Layout | Full width or sidebar |

### Email Tab

| Setting | Description |
|---------|-------------|
| From Name | Sender name on emails |
| From Email | Sender email address |
| Admin Email | Where to send admin notifications |
| Notification toggles | Enable/disable each email type |

### Advanced Tab

| Setting | Description |
|---------|-------------|
| Delete Data | Remove all data on uninstall |
| Custom CSS | Add custom styles |
| Debug Mode | Enable debug logging |

---

## Template Customization

### Override Templates

Copy templates from the plugin to your theme to customize:

```
plugins/all-purpose-directory/templates/
    → your-theme/all-purpose-directory/
```

### Available Templates

| Template | Purpose |
|----------|---------|
| archive-listing.php | Listings archive page |
| single-listing.php | Single listing page |
| listing-card.php | Grid view card |
| listing-card-list.php | List view card |
| submission-form.php | Submission form |
| dashboard/*.php | Dashboard templates |
| review/*.php | Review templates |
| search/*.php | Search form templates |
| emails/*.php | Email templates |

### Template Priority

1. Child theme: `your-child-theme/all-purpose-directory/`
2. Parent theme: `your-theme/all-purpose-directory/`
3. Plugin: `all-purpose-directory/templates/`

---

## Troubleshooting

### Listings Not Appearing

1. Check the listing status (must be "Published")
2. Verify permalink settings (Settings > Permalinks, click Save)
3. Clear any caching plugins
4. Check category assignments

### Search Not Working

1. Ensure the search form is on a page
2. Check that fields are marked as "searchable"
3. Verify AJAX is not blocked by security plugins

### Emails Not Sending

1. Install a mail plugin (WP Mail SMTP)
2. Check spam folders
3. Verify email settings in Listings > Settings > Email
4. Enable debug mode to see errors

### Images Not Uploading

1. Check file size limits (php.ini: upload_max_filesize)
2. Verify folder permissions (/wp-content/uploads/)
3. Check allowed file types in settings

### Performance Issues

1. Reduce listings per page
2. Enable caching plugin
3. Optimize images before upload
4. Check server resources

### 404 Errors on Listings

1. Go to Settings > Permalinks
2. Click "Save Changes" (this flushes rewrite rules)
3. Deactivate and reactivate the plugin

### Contact Admin

If you need further help:
1. Check the [WordPress.org support forum](https://wordpress.org/support/plugin/all-purpose-directory/)
2. Include: WordPress version, PHP version, error messages, steps to reproduce

---

## Quick Reference Card

### Essential Shortcodes

```
[apd_listings]              - Show listings
[apd_submission_form]       - Submission form
[apd_dashboard]             - User dashboard
[apd_search_form]           - Search form
[apd_categories]            - Category list
[apd_favorites]             - User favorites
```

### Key Settings Locations

- **General settings**: Listings > Settings
- **Categories**: Listings > Categories
- **Tags**: Listings > Tags
- **Reviews**: Listings > Reviews
- **All Listings**: Listings > All Listings

### Meta Keys (for developers)

All meta uses the `_apd_` prefix:
- `_apd_views_count` - View count
- `_apd_average_rating` - Average review rating
- `_apd_favorite_count` - Times favorited
- `_apd_{field_name}` - Custom field values
