# Dashboard & Profile System

Dashboard provides a frontend user dashboard with tabs for managing listings and profile settings.

## Dashboard Helper Functions

```php
apd_dashboard( array $config = [] ): \APD\Frontend\Dashboard\Dashboard
apd_render_dashboard( array $config = [] ): string
apd_get_dashboard_url(): string
apd_get_dashboard_tab_url( string $tab ): string
apd_get_user_listing_stats( int $user_id = 0 ): array
apd_get_user_listings_count( int $user_id, string $status = 'any' ): int
apd_is_dashboard(): bool
apd_get_current_dashboard_tab(): string
apd_my_listings( array $config = [] ): \APD\Frontend\Dashboard\MyListings
apd_get_user_listings( int $user_id = 0, array $args = [] ): \WP_Query
```

## Dashboard Stats Array

```php
$stats = apd_get_user_listing_stats( $user_id );
// Returns:
[
    'total'             => 10,    // Total listings
    'published'         => 7,     // Published listings
    'pending'           => 2,     // Pending review
    'draft'             => 1,     // Drafts
    'expired'           => 0,     // Expired listings
    'favorites'         => 5,     // Total favorites received
    'reviews'           => 12,    // Total reviews received
    'inquiries'         => 8,     // Total inquiries received
    'unread_inquiries'  => 3,     // Unread inquiries
]
```

## Dashboard Hooks

**Actions:**
- `apd_before_dashboard` - Before dashboard renders
- `apd_after_dashboard` - After dashboard renders
- `apd_dashboard_start` - At start of dashboard content
- `apd_dashboard_end` - At end of dashboard content
- `apd_dashboard_before_content` - Before tab content
- `apd_dashboard_after_content` - After tab content

**Filters:**
- `apd_dashboard_tabs` - Modify available tabs
- `apd_dashboard_stats` - Modify stats array
- `apd_dashboard_url` - Filter dashboard URL
- `apd_dashboard_args` - Filter dashboard arguments
- `apd_dashboard_html` - Filter final HTML output
- `apd_dashboard_classes` - Modify CSS classes

---

## Profile Helper Functions

```php
apd_profile( array $config = [] ): \APD\Frontend\Dashboard\Profile
apd_get_user_profile_data( int $user_id = 0 ): array
apd_save_user_profile( array $data, ?int $user_id = null ): true|\WP_Error
apd_get_user_avatar_url( int $user_id = 0, int $size = 96 ): string
apd_get_user_social_links( int $user_id = 0 ): array
apd_user_has_custom_avatar( int $user_id = 0 ): bool
```

## Profile User Meta Keys

- `_apd_phone` - Phone number
- `_apd_avatar` - Custom avatar attachment ID
- `_apd_social_facebook` - Facebook profile URL
- `_apd_social_twitter` - Twitter/X profile URL
- `_apd_social_linkedin` - LinkedIn profile URL
- `_apd_social_instagram` - Instagram profile URL

## Profile Hooks

**Actions:**
- `apd_profile_start` - At start of profile form
- `apd_profile_end` - At end of profile form
- `apd_profile_saved` - After profile saved successfully
- `apd_before_save_profile` - Before profile data saved
- `apd_after_save_profile` - After profile data saved
- `apd_avatar_uploaded` - After avatar uploaded

**Filters:**
- `apd_profile_args` - Filter profile form arguments
- `apd_profile_user_data` - Filter user data array
- `apd_validate_profile` - Validate profile data
- `apd_user_social_links` - Modify social links array
