# Favorites System

The Favorites system (`src/User/Favorites.php`) manages user favorites for listings.

## Meta Keys

- `_apd_favorites` - User meta storing array of favorite listing IDs
- `_apd_favorite_count` - Listing post meta storing total favorite count

## Helper Functions

```php
apd_favorites(): Favorites                                  // Get Favorites instance
apd_add_favorite( int $listing_id, ?int $user_id ): bool    // Add to favorites
apd_remove_favorite( int $listing_id, ?int $user_id ): bool // Remove from favorites
apd_toggle_favorite( int $listing_id, ?int $user_id ): ?bool // Toggle, returns new state
apd_is_favorite( int $listing_id, ?int $user_id ): bool     // Check if favorited
apd_get_user_favorites( ?int $user_id ): int[]              // Get favorite listing IDs
apd_get_favorites_count( ?int $user_id ): int               // Get user's favorite count
apd_get_listing_favorites_count( int $listing_id ): int     // Get listing's favorite count
apd_favorites_require_login(): bool                         // Check if login required
apd_clear_favorites( ?int $user_id ): bool                  // Clear all favorites
apd_get_favorite_listings( ?int $user_id, array $args ): WP_Post[] // Get favorite posts
```

## Hooks

**Actions:**
- `apd_favorite_added` - Fired after favorite added
- `apd_favorite_removed` - Fired after favorite removed
- `apd_favorites_cleared` - Fired after all favorites cleared
- `apd_favorites_init` - Fired when Favorites initializes

**Filters:**
- `apd_favorites_require_login` - Whether login is required (default: true)
- `apd_guest_favorites_enabled` - Enable guest favorites (default: false)
- `apd_favorite_listings_query_args` - Modify favorite listings query args

## Guest Favorites

Disabled by default. Enable via `apd_guest_favorites_enabled` filter. Guest favorites stored in cookie (`apd_guest_favorites`) and can be merged on login.

```php
// Enable guest favorites
add_filter( 'apd_guest_favorites_enabled', '__return_true' );
```
