# Frontend Submission System

SubmissionForm and SubmissionHandler manage frontend listing submissions.

## Key Classes

- `APD\Frontend\Submission\SubmissionForm` - Renders the submission form with fields, taxonomies, and image upload
- `APD\Frontend\Submission\SubmissionHandler` - Processes form submissions, validates, creates listings

## Shortcode

`[apd_submission_form]`

| Attribute | Default | Description |
|-----------|---------|-------------|
| `require_login` | "true" | Require user login |
| `redirect` | "" | URL after successful submission |
| `show_title` | "true" | Show title field |
| `show_content` | "true" | Show content field |
| `show_excerpt` | "false" | Show excerpt field |
| `show_categories` | "true" | Show category selector |
| `show_tags` | "true" | Show tag selector |
| `show_featured_image` | "true" | Show image upload |
| `show_terms` | "false" | Show terms checkbox |
| `terms_text` | "" | Terms acceptance text |
| `terms_link` | "" | Link to terms page |
| `terms_required` | "true" | Require terms acceptance |

## Helper Functions

```php
apd_submission_handler( array $config = [] ): SubmissionHandler
apd_process_submission( array $data, int $listing_id = 0 ): int|WP_Error
apd_get_default_listing_status(): string
apd_is_submission_success(): bool
apd_get_submitted_listing_id(): int
apd_render_submission_success( int $listing_id = 0, string $submit_url = '', bool $is_update = false ): string
apd_set_submission_errors( array|WP_Error $errors, int $user_id = 0 ): bool
apd_set_submission_values( array $values, int $user_id = 0 ): bool
```

## Edit Listing Functions

```php
apd_user_can_edit_listing( int $listing_id, int|null $user_id = null ): bool
apd_get_edit_listing_url( int $listing_id, string $submission_url = '' ): string
apd_is_edit_mode(): bool
apd_get_edit_listing_id(): int
```

## Hooks

**Actions:**
- `apd_before_submission` - Before submission is processed
- `apd_after_submission` - After submission is processed
- `apd_validate_submission` - During submission validation
- `apd_listing_saved` - After listing post is saved
- `apd_listing_fields_saved` - After custom fields are saved
- `apd_listing_taxonomies_assigned` - After taxonomies assigned
- `apd_before_listing_update` - Before existing listing updated
- `apd_after_edit_not_allowed` - After edit permission denied

**Filters:**
- `apd_submission_form_data` - Filter form data
- `apd_new_listing_post_data` - Filter post data before insert
- `apd_submission_default_status` - Default status for new listings
- `apd_edit_listing_status` - Status when editing (default: preserve current)
- `apd_user_can_submit_listing` - Whether user can submit
- `apd_user_can_edit_listing` - Whether user can edit listing
- `apd_submission_admin_notification` - Whether to notify admin
- `apd_submission_success_redirect` - Redirect URL after success
- `apd_submission_error_redirect` - Redirect URL after error
- `apd_edit_not_allowed_args` - Args for permission denied template
- `apd_submission_page_url` - Submission page URL
- `apd_edit_listing_url` - Edit listing URL

## Templates

- `templates/submission/submission-form.php` - Main form template
- `templates/submission/submission-success.php` - Success message template
- `templates/submission/edit-not-allowed.php` - Permission denied message
- `templates/submission/category-selector.php` - Category dropdown
- `templates/submission/tag-selector.php` - Tag checkboxes
- `templates/submission/image-upload.php` - Featured image upload

## Edit Mode

- **URL:** `/submit-listing/?edit_listing=123` - Edit existing listing
- **Shortcode:** `[apd_submission_form listing_id="123"]` - Pre-load specific listing
- Ownership verified before displaying form or processing submission
- Current post status preserved by default (filterable via `apd_edit_listing_status`)
- Different success message shown for updates vs new submissions

---

# Spam Protection

Built-in spam protection for frontend submissions with multiple layers:

1. **Honeypot Field** - Hidden field that bots fill but humans don't see
2. **Time-based Protection** - Rejects submissions faster than 3 seconds
3. **Rate Limiting** - Default 5 submissions per hour per user/IP
4. **Custom Checks** - Filter hook for reCAPTCHA integration

## Configuration Filters

```php
// Customize honeypot field name (default: 'website_url')
add_filter( 'apd_honeypot_field_name', fn() => 'company_phone' );

// Change minimum submission time (default: 3 seconds)
add_filter( 'apd_submission_min_time', fn() => 5 );

// Change rate limit (default: 5 submissions)
add_filter( 'apd_submission_rate_limit', fn() => 10 );

// Change rate limit period (default: 3600 seconds / 1 hour)
add_filter( 'apd_submission_rate_period', fn() => 1800 );

// Bypass spam protection for specific users
add_filter( 'apd_bypass_spam_protection', function( $bypass, $user_id ) {
    return current_user_can( 'manage_options' ); // Admins bypass
}, 10, 2 );

// Add custom spam check (e.g., reCAPTCHA)
add_filter( 'apd_submission_spam_check', function( $result, $post_data, $user_id ) {
    // Return WP_Error to block submission
    if ( ! verify_recaptcha( $post_data['recaptcha_token'] ) ) {
        return new WP_Error( 'captcha_failed', 'Please complete the captcha.' );
    }
    return $result;
}, 10, 3 );
```

## Spam Protection Helper Functions

```php
apd_get_submission_rate_limit(): int
apd_get_submission_rate_period(): int
apd_check_submission_rate_limit( string $identifier ): bool
apd_get_submission_count( string $identifier ): int
apd_increment_submission_count( string $identifier ): int
apd_reset_submission_count( string $identifier ): bool
apd_get_rate_limit_identifier(): string
apd_get_client_ip(): string
apd_is_submission_spam( array $post_data = [] ): bool|WP_Error
apd_get_submission_min_time(): int
apd_get_honeypot_field_name(): string
```

## Spam Logging Action

```php
// Log spam attempts for admin review
add_action( 'apd_spam_attempt_detected', function( $type, $ip, $user_id, $post_data ) {
    error_log( "Spam attempt detected: type={$type}, ip={$ip}, user={$user_id}" );
}, 10, 4 );
```
