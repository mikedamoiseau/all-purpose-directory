# Review System

The Review system (`src/Review/ReviewManager.php`) manages reviews for listings using WordPress comments with custom meta.

## Comment Type

`apd_review` - Distinguishes reviews from regular comments.

## Meta Keys

- `_apd_rating` - Integer 1-5 star rating
- `_apd_review_title` - Optional review title string

## Review Data Structure

```php
$review = [
    'id'             => 123,                    // Comment ID
    'listing_id'     => 456,                    // Post ID
    'author_id'      => 789,                    // User ID (0 for guests)
    'author_name'    => 'John Doe',
    'author_email'   => 'john@example.com',
    'rating'         => 5,                      // 1-5 stars
    'title'          => 'Great listing!',       // Optional
    'content'        => 'Review text...',
    'status'         => 'approved',             // approved, pending, spam, trash
    'date'           => '2024-01-15 10:30:00',
    'date_formatted' => 'January 15, 2024',
];
```

## Helper Functions

```php
apd_review_manager(): ReviewManager                         // Get ReviewManager instance
apd_create_review( int $listing_id, array $data ): int|WP_Error  // Create review
apd_get_review( int $review_id ): ?array                    // Get single review
apd_get_listing_reviews( int $listing_id, array $args ): array   // Returns ['reviews' => array, 'total' => int, 'pages' => int]
apd_get_user_review( int $listing_id, int $user_id ): ?array     // Get user's review
apd_has_user_reviewed( int $listing_id, int $user_id ): bool     // Check if reviewed
apd_get_review_count( int $listing_id, string $status ): int     // Count reviews
apd_delete_review( int $review_id, bool $force ): bool      // Delete/trash review
apd_approve_review( int $review_id ): bool                  // Approve pending review
apd_reviews_require_login(): bool                           // Check if login required
```

## Hooks

**Actions:**
- `apd_reviews_init` - Fired when ReviewManager initializes
- `apd_before_review_create` - Before review is created (`$listing_id`, `$data`)
- `apd_review_created` - After review is created (`$review_id`, `$listing_id`, `$data`)
- `apd_before_review_update` - Before review is updated (`$review_id`, `$data`)
- `apd_review_updated` - After review is updated (`$review_id`, `$data`)
- `apd_before_review_delete` - Before review is deleted (`$review_id`)
- `apd_review_deleted` - After review is deleted (`$review_id`)
- `apd_review_approved` - After review is approved (`$review_id`)
- `apd_review_rejected` - After review is rejected (`$review_id`)

**Filters:**
- `apd_reviews_require_login` - Whether login is required (default: true)
- `apd_review_min_content_length` - Minimum content length (default: 10)
- `apd_review_data` - Filter review data before save
- `apd_review_default_status` - Default status for new reviews

## Validation

- Rating required (1-5)
- Content required (min 10 characters, configurable via `apd_review_min_content_length` filter)
- One review per user per listing
- Login required by default (configurable via `apd_reviews_require_login` filter)

**Comment Filtering:** Reviews are automatically excluded from regular comment queries via `comments_clauses` filter.

---

# Review Moderation Admin

The Review Moderation admin page (`src/Admin/ReviewModeration.php`) provides an admin interface for managing reviews.

## Menu Location

All Purpose Directory > Reviews (with pending count badge)

## Features

- List all reviews with columns: Listing, Author, Rating, Review, Status, Date
- Status tabs: All, Pending, Approved, Spam, Trash
- Filters: By listing, by rating, search
- Row actions: Approve, Unapprove, Spam, Trash, Restore, Delete Permanently, View Listing
- Bulk actions: Approve, Mark as Spam, Move to Trash, Restore, Delete Permanently
- Pending review count badge on menu item

## Constants

```php
ReviewModeration::PAGE_SLUG       // 'apd-reviews'
ReviewModeration::NONCE_ACTION    // 'apd_review_moderation'
ReviewModeration::NONCE_NAME      // 'apd_review_nonce'
ReviewModeration::PER_PAGE        // 20 reviews per page
ReviewModeration::CAPABILITY      // 'moderate_comments' required
```

## Key Methods

```php
ReviewModeration::get_instance(): ReviewModeration     // Singleton accessor
$moderation->get_pending_count(): int                  // Count pending reviews
$moderation->get_reviews( array $args ): array         // Get filtered reviews
```

## Admin URL

`edit.php?post_type=apd_listing&page=apd-reviews`
