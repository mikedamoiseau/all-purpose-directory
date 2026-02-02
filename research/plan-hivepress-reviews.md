# HivePress Reviews Plugin - Feature Analysis

**Plugin Name:** HivePress Reviews
**Version:** 1.4.0
**Author:** HivePress
**License:** GPLv3
**Requirements:** WordPress 5.0+, PHP 7.4+

**Description:** HivePress Reviews is an extension for the HivePress plugin that allows users to rate and review listings.

---

## Core Features

### Review Management System
- **Review Model**: Comment-based review system with custom fields
- **Review Comments**: Uses WordPress comment infrastructure with custom comment type `hp_review`
- **Rating System**: Star-based rating system (1-5 scale with 0.1 decimal precision)
- **Review Status**: Moderation support with approval workflow
- **Review Drafts**: Automatic draft creation for in-progress reviews with attachment support

### Review Rating & Display
- **Listing Ratings**: Average rating calculation and display for individual listings
- **Vendor/Business Ratings**: Aggregated rating across all vendor's listings
- **Rating Count**: Tracks total number of reviews per listing and vendor
- **Rating Sort Order**: Reviews can be sorted by rating (descending) or date added (descending)

### Review Submission Features
- **Write Review Form**: User-friendly form for submitting reviews
- **Single/Multiple Reviews**: Optional setting to allow multiple reviews per user per listing
- **Anonymous Reviews**: Optional anonymous review submission to hide reviewer identity
- **Review Criteria**: Configurable criteria-based multi-rating system
- **Image Attachments**: Support for attaching images to reviews
- **Moderation Queue**: Optional manual approval before review publication

### Review Reply System
- **Reply to Reviews**: Listing owners can reply to reviews
- **Nested Comments**: Replies are nested as child comments to parent reviews
- **Reply Moderation**: Replies also subject to approval workflow
- **Vendor-Only Replies**: Only listing owners can reply to reviews

## Email Notifications

| Email Type | Description |
|------------|-------------|
| Review Added | Notifies vendor when new review is submitted |
| Review Approved | Notifies reviewer when review is approved |
| Reply Email | Notifies reviewer when vendor replies to their review |

**Available Tokens:** user name, listing title, review URL, etc.

## Admin Settings & Configuration

### Display Settings
- Default review sorting (by rating or date added)
- Reviews per page (configurable, default: 3 or 10)

### Submission Settings
- Allow multiple reviews per user per listing
- Allow anonymous reviews
- Allow image attachments
- Enable/disable review moderation
- Enable/disable review replies
- Configurable review criteria for multi-dimensional ratings

## Frontend Blocks & Components

### Review Display Blocks
- **Related Reviews Block**: Display approved reviews for a listing with pagination
- **Reviews Block**: Generic reviews display with configurable columns (1-3), number of items, and sort order
- **Review Pagination**: Load more functionality for infinite scroll pagination

### Form Blocks
- **Review Submit Form Block**: Modal for submitting new reviews
- **Review Reply Form Block**: Modal for replying to existing reviews

### Display Components
- Review Rating Display (star visualization using Raty.js)
- Review Author Info (reviewer name and anonymity status)
- Review Created Date
- Review Text (full review content)
- Review Attachment (attached images)
- Review Criteria Display (multi-dimensional ratings)
- Review Status Badge (moderation status)

## REST API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/reviews` | Retrieve reviews for a listing with pagination |
| POST | `/reviews` | Submit new review or reply (auth required) |

### GET Parameters
- `listing` - Listing ID
- `_page` - Page number
- `_render` - Include HTML rendering
- `_columns` - Number of columns

## Form System

### Review Submit Form
- Rating field (required)
- Review text field
- Hidden listing ID field
- Conditional fields (criteria, attachment, anonymous)
- AJAX submission support

### Review Reply Form
- Reply text field (required)
- Hidden parent review ID field

## Review Model Fields

| Field | Description |
|-------|-------------|
| `text` | Review content (required, max 2048 chars) |
| `rating` | Overall rating (1-5 scale) |
| `created_date` | Review submission timestamp |
| `approved` | Moderation status (0 or 1) |
| `author` | User ID of reviewer |
| `author__display_name` | Reviewer display name |
| `author__email` | Reviewer email |
| `parent` | Parent review ID (for replies) |
| `listing` | Listing ID being reviewed |
| `anonymous` | Anonymous submission flag |
| `criteria` | Array of criterion ratings |
| `attachment` | Image attachment ID |

## Model Extensions

### Listing Model
- `rating`: Overall average rating (protected, sortable)
- `rating_count`: Number of reviews for the listing

### Vendor/User Model
- `rating`: Average rating across all vendor's listings
- `rating_count`: Total review count for all vendor's listings

## Template System

### Listing View Templates
- `listing-rating`: Rating display in listing details
- `review-submit-link`: Button to open review form modal
- Reviews section on listing view page

### Vendor View Templates
- `vendor-rating`: Rating display for vendor/business profile

### Review View Templates
- `review-image`: Reviewer avatar or attached image
- `review-author`: Author name and anonymous badge
- `review-rating`: Star rating display
- `review-created-date`: Submission date
- `review-text`: Review body text
- `review-attachment`: Attached images gallery
- `review-criteria`: Multi-dimensional ratings display
- `review-listing`: Reference back to reviewed listing
- `review-reply-link`: Button to open reply modal
- `review-status-badge`: Moderation status indicator

## Business Logic Features

### Rating Calculations
- Real-time average rating calculation using database queries
- Separate ratings for listings and vendors
- Excludes unapproved reviews from calculations
- Only counts parent reviews (not replies)

### Validation & Permissions
- Authenticated users only for review submission
- Users cannot review their own listings
- Prevent duplicate reviews (unless multiple reviews enabled)
- Parent review validation for replies
- Only listing owner can reply to reviews

### Draft Management
- Automatic draft creation for users with unsaved reviews
- Draft attachment storage for images
- Daily cleanup of old drafts
- Per-user draft caching

### Moderation Workflow
- Optional review approval by site admin
- Separate approval status for replies
- Automatic email notifications at each stage
- Status badges on front-end

## Database Structure

### Uses WordPress Comments Table
- `wp_comments.comment_type = 'hp_review'`
- `comment_karma` = Rating value
- `comment_post_ID` = Listing ID
- `comment_parent` = Parent review ID (for replies)
- `user_id` = Author user ID
- `comment_approved` = Status (0 or 1)

### Metadata Storage
- Review criteria stored as JSON in comment meta
- Anonymous flag stored as comment meta
- Attachment ID stored as comment meta

## Security Features

- Comment type filtering (`hp_review` comment type)
- User authentication checks on review submission
- Permission validation for reply posting
- Listing ownership verification
- HTML sanitization in email notifications
- Data validation and escaping throughout

## Performance Features

- Result caching for review queries (up to 1000 reviews)
- Pagination to limit queries
- Database-level rating aggregation
- Per-user draft caching
- Indexed queries on listing and approval status

## JavaScript Dependencies

- jQuery
- Raty.js (rating widget library)
- HivePress core scripts
- Custom frontend script
