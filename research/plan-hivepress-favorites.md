# HivePress Favorites Plugin - Feature Analysis

**Plugin Name:** HivePress Favorites
**Version:** 1.2.2
**Author:** HivePress
**License:** GPLv3
**Requirements:** WordPress 5.0+, PHP 7.4+, HivePress plugin

**Description:** An extension for the HivePress plugin that allows users to maintain a personalized list of favorite listings.

---

## Core Features

### Favorite Listings Management
- Users can add/remove listings from their personal favorites
- Favorites stored as custom comment-type data in WordPress
- Favorites persist across sessions (user-specific)

### Favorites Dashboard Page
- Dedicated "Favorites" page in user account menu
- Displays all favorited listings with pagination
- Only shows when user has active favorite listings
- Route: `/account/favorites` (paginated)

### Favorite Toggle Component
- Heart-shaped icon button on listings
- Appears on both listing block view and listing detail page
- Shows "Add to Favorites" / "Remove from Favorites" text
- Visual feedback with active state styling

### User Account Integration
- Automatically adds "Favorites" menu item to user account navigation
- Only visible when user has at least one published favorite listing
- Conditionally displays based on user authentication

### Performance Optimization
- Favorite IDs cached per user for improved performance
- Cache limit: 1000 favorites per user
- Uses HivePress native caching system

### User Data Cleanup
- Automatically deletes all user favorites when user account is deleted

## REST API Endpoints

### Favorite Toggle Endpoint
| Property | Value |
|----------|-------|
| Route | `listing_favorite_action` |
| Path | `/listing/{listing_id}/favorite` |
| Method | POST |
| Authentication | Required |

**Response:**
- Status 200: Returns `{ "id": listing_id }`
- Status 401: Not authenticated
- Status 404: Listing not found or not published
- Status 400: Save error with validation messages

### Favorites Listing Page Route
| Property | Value |
|----------|-------|
| Route | `listings_favorite_page` |
| Path | `/account/favorites` |
| Pagination | Enabled |

**Redirect Rules:**
- Redirect to login if user not authenticated
- Redirect to account dashboard if no published favorites exist
- Otherwise render the favorites page

## Database Model: Favorite

### Storage
- **Type:** Comment-based model
- **Table:** `wp_comments` with `comment_type = 'favorite'`
- **Public:** No (non-public comment type)

### Fields

| Field | Type | Description | Database Alias |
|-------|------|-------------|----------------|
| `added_date` | Date | When the favorite was added | `comment_date` |
| `user` | Number (ID) | User who favorited | `user_id` |
| `listing` | Number (ID) | Favorited listing | `comment_post_ID` |

## Frontend Blocks & Components

### Favorite Toggle Block
**Class:** `HivePress\Blocks\Favorite_Toggle`
**Type:** `favorite_toggle`

**Rendering Locations:**
- **Listing Block View:** Added to `listing_actions_primary` block
  - View mode: `icon`
  - Order: 20
  - CSS classes: `hp-listing__action hp-listing__action--favorite`

- **Listing Detail Page:** Added to `listing_actions_secondary` block
  - View mode: Default
  - Order: 20
  - CSS classes: `hp-listing__action hp-listing__action--favorite`

**Properties:**
- Icon: `heart`
- Captions: "Add to Favorites" / "Remove from Favorites"
- Active state: Dynamically set based on user's favorite list

## Templates

### Listings Favorite Page Template
**Class:** `HivePress\Templates\Listings_Favorite_Page`
**Extends:** `User_Account_Page`

**Block Structure:**
```
page_content
├── listings (type: listings, columns: 2, order: 10)
└── listing_pagination (type: part, path: page/pagination, order: 20)
```

**Features:**
- 2-column grid layout for favorite listings
- Pagination support using HivePress pagination component
- Inherits user account page styling and layout

## Component Architecture

### Main Component: `HivePress\Components\Favorite`

**Initialization Hooks:**
- `hivepress/v1/models/user/delete` - Cleanup on user deletion
- `init` (priority 100) - Set favorite IDs in request context
- `hivepress/v1/menus/user_account` - Add favorites menu item
- `hivepress/v1/templates/listing_view_block` - Inject favorite toggle
- `hivepress/v1/templates/listing_view_page` - Inject favorite toggle

**Key Methods:**
- `delete_favorites($user_id)` - Deletes all favorites for a user
- `set_favorites()` - Loads user's favorite listing IDs into request context
- `alter_account_menu($menu)` - Adds/removes favorites menu item
- `alter_listing_view_block($template)` - Adds favorite toggle to block view
- `alter_listing_view_page($template)` - Adds favorite toggle to detail page

## Hooks & Filters

### HivePress Hooks Used
- `hivepress/v1/extensions` - Register plugin as HivePress extension
- `hivepress/v1/models/user/delete` - User deletion action
- `hivepress/v1/menus/user_account` - Modify user account menu
- `hivepress/v1/templates/listing_view_block` - Modify listing block template
- `hivepress/v1/templates/listing_view_page` - Modify listing page template

### WordPress Hooks
- `init` (priority 100) - Initialize favorites for current user

## Authentication & Authorization

### Frontend Features
- Favorite toggle available only when user is logged in
- Favorite addition/removal requires authentication (401 otherwise)

### Access Control
- Users can only see their own favorites
- Favorites page redirects non-authenticated users to login
- Favorites menu item only visible to authenticated users with published favorites

### Data Isolation
- Each user's favorites stored with their user_id
- Favorite queries filtered by current user ID

## Internationalization

- **Text Domain:** `hivepress-favorites`
- **Language Files Location:** `/languages/`

**Translatable Strings:**
- Plugin name: "HivePress Favorites"
- Description: "Allow users to keep a list of favorite listings."
- Button text: "Add to Favorites" / "Remove from Favorites"
- Page title: "Favorites"

## Technical Implementation

### Architecture Pattern
- **Extension Pattern:** Leverages HivePress extension architecture
- **ORM Model:** Uses HivePress Model system extending `Comment` model
- **Block System:** Integrates with HivePress block rendering system
- **Template System:** Uses HivePress template hierarchy
- **Routing:** Uses HivePress router with REST API support
- **Component Pattern:** Factory component architecture with hooks

### Performance Considerations
- User-specific favorite IDs cached
- Cache limit prevents excessive memory usage (1000 favorites)
- Cache invalidated during user deletion
- Pagination prevents large result sets
- Single query for favorites page results

### Security Features
- User ID verification when creating/deleting favorites
- Listing publication status check before allowing favorite operations
- Authentication verification on all favorite endpoints
- Nonce/security handled by HivePress framework

## Integration Points with HivePress

- **Models:** Extends `Comment` model with `Favorite` model
- **Blocks:** Uses `Toggle` block base, registers `Favorite_Toggle` custom block
- **Routes:** Registers routes in HivePress router with REST support
- **Menus:** Integrates with user account menu system
- **Templates:** Extends base user account templates
- **Caching:** Uses HivePress cache system with user-specific scope
- **Requests:** Uses HivePress request context for data passing

## Limitations & Notes

- Favorites cascade-delete when user is deleted
- Favorites remain if listing is deleted (but won't display if unpublished)
- Cache disabled for users with 1000+ favorites (direct queries used instead)
- Favorites menu item only shows if user has published favorites
- No admin interface - plugin has no WordPress admin settings or pages

## File Structure

```
hivepress-favorites/
├── hivepress-favorites.php (Main plugin file)
├── readme.txt
├── license.txt (GPLv3)
├── languages/
│   └── hivepress-favorites.pot
├── includes/
│   ├── blocks/
│   │   └── class-favorite-toggle.php
│   ├── models/
│   │   └── class-favorite.php
│   ├── components/
│   │   └── class-favorite.php
│   ├── controllers/
│   │   └── class-favorite.php
│   ├── templates/
│   │   └── class-listings-favorite-page.php
│   └── configs/
│       └── comment-types.php
└── vendor/
```
